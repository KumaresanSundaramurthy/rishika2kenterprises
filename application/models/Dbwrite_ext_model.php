<?php defined('BASEPATH') or exit('No direct script access allowed');

class Dbwrite_ext_model extends CI_Model {

    private $WriteDB;

    function __construct() {
        parent::__construct();
        $this->WriteDB = $this->load->database('WriteDB', TRUE);
    }

    // ── Generic helpers ───────────────────────────────────────────────────────

    /**
     * Generic UPSERT: INSERT ... ON DUPLICATE KEY UPDATE col = VALUES(col).
     * @param string $db
     * @param string $table
     * @param array  $insertData    Column => value pairs to insert
     * @param array  $updateColumns Column names to update on duplicate key
     * @returns void
     */
    public function upsertData(string $db, string $table, array $insertData, array $updateColumns): void {
        if (empty($insertData) || empty($updateColumns)) return;
        $this->WriteDB->db_debug = FALSE;
        $cols         = implode(', ', array_keys($insertData));
        $placeholders = implode(', ', array_fill(0, count($insertData), '?'));
        $updates      = implode(', ', array_map(fn(string $col): string => "{$col} = VALUES({$col})", $updateColumns));
        $this->WriteDB->query(
            "INSERT INTO {$db}.{$table} ({$cols}) VALUES ({$placeholders}) ON DUPLICATE KEY UPDATE {$updates}",
            array_values($insertData)
        );
    }

    /**
     * Execute any raw write SQL with optional bindings.
     * Use for complex writes that upsertData/insertData/updateData cannot express.
     * @param string $sql
     * @param array  $bindings
     * @returns void
     */
    public function execWrite(string $sql, array $bindings = []): void {
        $this->WriteDB->db_debug = FALSE;
        $this->WriteDB->query($sql, $bindings);
    }

    /**
     * Execute a raw write query and return the number of affected rows.
     * Use for optimistic-lock UPDATE patterns where the caller checks affected_rows() === 1.
     * @param string $sql
     * @param array  $bindings
     * @returns int
     */
    public function execWriteAffected(string $sql, array $bindings = []): int {
        $this->WriteDB->db_debug = FALSE;
        $this->WriteDB->query($sql, $bindings);
        return $this->WriteDB->affected_rows();
    }

    /**
     * Run a SELECT query on the write connection and return the first row.
     * Required for SELECT ... FOR UPDATE row-level locking (must share the write
     * connection with the surrounding transaction) and for connection-local functions
     * like LAST_INSERT_ID() that must be read from the same connection that wrote.
     * @param string $sql
     * @param array  $bindings
     * @returns object|null
     */
    public function queryWriteRow(string $sql, array $bindings = []): ?object {
        $this->WriteDB->db_debug = FALSE;
        $result = $this->WriteDB->query($sql, $bindings);
        return ($result && $result->num_rows() > 0) ? $result->row() : null;
    }

    // ── Transaction Number Helpers ────────────────────────────────────────────

    /**
     * @param mixed $prefixUID
     * @param mixed $transNumber
     * @param mixed $orgUID
     * @returns object|null
     */
    public function checkTransactionNumberExists($prefixUID, $transNumber, $orgUID) {
        $this->WriteDB->db_debug = FALSE;
        $this->WriteDB->select('TransUID');
        $this->WriteDB->from('Transaction.TransactionsTbl');
        $this->WriteDB->where([
            'PrefixUID'   => (int) $prefixUID,
            'TransNumber' => (int) $transNumber,
            'OrgUID'      => (int) $orgUID,
            'IsDeleted'   => 0,
        ]);
        $this->WriteDB->limit(1);
        return $this->WriteDB->get()->row();
    }

    /**
     * @param mixed $prefixUID
     * @param mixed $orgUID
     * @returns int
     */
    public function getNextAvailableTransNumber($prefixUID, $orgUID): int {
        $this->WriteDB->db_debug = FALSE;
        $this->WriteDB->select_max('TransNumber', 'MaxNumber');
        $this->WriteDB->from('Transaction.TransactionsTbl');
        $this->WriteDB->where(['PrefixUID' => (int) $prefixUID, 'OrgUID' => (int) $orgUID]);
        $result = $this->WriteDB->get()->row();
        $next   = $result ? ((int)($result->MaxNumber ?? 0) + 1) : 1;

        if ($next > 2147483647) return -1;

        $maxAttempts = 100;
        while ($maxAttempts-- > 0) {
            if ($next > 2147483647) return -1;
            $this->WriteDB->select('TransUID');
            $this->WriteDB->from('Transaction.TransactionsTbl');
            $this->WriteDB->where(['PrefixUID' => (int)$prefixUID, 'TransNumber' => $next, 'OrgUID' => (int)$orgUID, 'IsDeleted' => 0]);
            $this->WriteDB->limit(1);
            if (!$this->WriteDB->get()->row()) break;
            $next++;
        }
        return $next;
    }

    // ── Stock Movement Methods ────────────────────────────────────────────────

    /* IN = stock increases: Purchases(105), Sales Returns(106), Credit Notes(107)
       OUT = stock decreases: Invoices(103), Purchase Returns(108) */
    private static $stockMovementMap = [
        103 => 'OUT',
        105 => 'IN',
        106 => 'IN',
        107 => 'IN',
        108 => 'OUT',
        112 => 'OUT',
    ];

    /**
     * Record stock movements for a saved (non-draft) transaction's items.
     * @param int    $transUID
     * @param int    $moduleUID
     * @param int    $orgUID
     * @param int    $userUID
     * @param array  $items
     * @param int    $branchUID
     * @returns void
     */
    public function saveStockMovements($transUID, $moduleUID, $orgUID, $userUID, array $items, int $branchUID = 0) {

        $movementType = self::$stockMovementMap[$moduleUID] ?? null;
        if (!$movementType) return;

        $this->WriteDB->db_debug = FALSE;
        $tpQuery = $this->WriteDB->query(
            "SELECT TransProdUID, ProductUID, VariantUID, UnitPrice, FinancialYear,
                    (COALESCE(CgstAmount,0)+COALESCE(SgstAmount,0)+COALESCE(IgstAmount,0)) AS TaxAmt
             FROM Transaction.TransProductsTbl
             WHERE TransUID = ? AND IsDeleted = 0
             ORDER BY TransProdUID ASC",
            [(int)$transUID]
        );
        $tpMap           = [];
        $txFinancialYear = '';
        if ($tpQuery) {
            foreach ($tpQuery->result() as $tp) {
                $mapKey = ((int)$tp->ProductUID) . '-' . ((int)($tp->VariantUID ?? 0));
                if (!isset($tpMap[$mapKey])) {
                    $tpMap[$mapKey] = [
                        'transProdUID' => (int)$tp->TransProdUID,
                        'sellingPrice' => (float)$tp->UnitPrice,
                        'taxAmount'    => (float)$tp->TaxAmt,
                    ];
                }
                if ($txFinancialYear === '') {
                    $txFinancialYear = $tp->FinancialYear ?? '';
                }
            }
        }

        $productUIDs = [];
        foreach ($items as $item) {
            $pid = (isset($item['productUID']) && (int)$item['productUID'] > 0)
                ? (int)$item['productUID']
                : (isset($item['id']) ? (int)$item['id'] : 0);
            if ($pid > 0 && strtolower($item['productType'] ?? '') !== 'service') {
                $productUIDs[] = $pid;
            }
        }
        $productDataMap = [];
        if (!empty($productUIDs)) {
            $ph = implode(',', array_fill(0, count($productUIDs), '?'));
            $pdQuery = $this->WriteDB->query(
                "SELECT p.ProductUID, p.IsComposite,
                        p.ItemName, p.MRP, p.CGST, p.SGST, p.IGST, p.TaxPercentage,
                        p.CategoryUID, c.Name AS CategoryName,
                        p.PurchasePrice, p.PartNumber, p.Description
                 FROM Products.ProductTbl p
                 LEFT JOIN Products.CategoryTbl c
                        ON c.CategoryUID = p.CategoryUID AND c.IsDeleted = 0
                 WHERE p.ProductUID IN ({$ph}) AND p.OrgUID = ? AND p.IsDeleted = 0",
                array_merge($productUIDs, [(int)$orgUID])
            );
            if ($pdQuery) {
                foreach ($pdQuery->result() as $pd) {
                    $productDataMap[(int)$pd->ProductUID] = $pd;
                }
            }
        }

        foreach ($items as $item) {
            $productUID  = (isset($item['productUID']) && (int)$item['productUID'] > 0)
                ? (int)$item['productUID']
                : (isset($item['id']) ? (int)$item['id'] : 0);
            $qty         = isset($item['quantity'])     ? (float) $item['quantity']    : 0;
            $unitCost    = isset($item['unitPrice'])    ? (float) $item['unitPrice']   : 0;
            $productType = isset($item['productType'])  ?         $item['productType'] : 'Product';
            $variantUID  = isset($item['variantUID'])   ? (int)   $item['variantUID']  : 0;

            if ($productUID <= 0 || $qty <= 0)         continue;
            if (strtolower($productType) === 'service') continue;

            $tpMapKey     = $productUID . '-' . $variantUID;
            $tpInfo       = $tpMap[$tpMapKey] ?? null;
            $transProdUID = $tpInfo ? $tpInfo['transProdUID'] : null;
            $sellingPrice = $tpInfo ? $tpInfo['sellingPrice'] : null;
            $taxAmount    = $tpInfo ? $tpInfo['taxAmount']    : null;

            $prodData    = $productDataMap[$productUID] ?? null;
            $isComposite = $prodData && (int)$prodData->IsComposite === 1;

            if ($isComposite) {
                $this->WriteDB->select('ChildProductUID, Quantity');
                $this->WriteDB->from('Products.ProductBOMTbl');
                $this->WriteDB->where(['ParentProductUID' => $productUID, 'IsDeleted' => 0, 'IsActive' => 1]);
                $bomRows = $this->WriteDB->get()->result();

                if (!empty($bomRows)) {
                    $componentUIDs = [];
                    foreach ($bomRows as $b) { $componentUIDs[] = (int)$b->ChildProductUID; }
                    $ph = implode(',', array_fill(0, count($componentUIDs), '?'));

                    $compQuery = $this->WriteDB->query(
                        "SELECT p.ProductUID, p.ItemName, p.PartNumber, p.Description,
                                p.CategoryUID, cat.Name AS CategoryName,
                                p.StorageUID, pu.ShortName AS PrimaryUnitName,
                                p.TaxDetailsUID, p.TaxPercentage,
                                p.CGST, p.SGST, p.IGST,
                                p.SellingPrice, p.PurchasePrice
                         FROM Products.ProductTbl p
                         LEFT JOIN Products.CategoryTbl cat
                                ON cat.CategoryUID = p.CategoryUID AND cat.IsDeleted = 0
                         LEFT JOIN Global.PrimaryUnitTbl pu
                                ON pu.PrimaryUnitUID = p.PrimaryUnitUID
                         WHERE p.ProductUID IN ({$ph}) AND p.OrgUID = ? AND p.IsDeleted = 0",
                        array_merge($componentUIDs, [(int)$orgUID])
                    );

                    $compDetails = [];
                    if ($compQuery) {
                        foreach ($compQuery->result() as $cd) {
                            $compDetails[(int)$cd->ProductUID] = $cd;
                        }
                    }

                    $passedCompPrices     = [];
                    $passedCompSellPrices = [];
                    if (!empty($item['bomComponents']) && is_array($item['bomComponents'])) {
                        foreach ($item['bomComponents'] as $pc) {
                            $pcUID = (int)($pc['childProductUID'] ?? 0);
                            if ($pcUID > 0) {
                                $passedCompPrices[$pcUID]     = (float)($pc['unitPrice']    ?? 0);
                                $passedCompSellPrices[$pcUID] = (float)($pc['sellingPrice'] ?? $pc['unitPrice'] ?? 0);
                            }
                        }
                    }

                    foreach ($bomRows as $bom) {
                        $componentUID = (int)$bom->ChildProductUID;
                        $componentQty = round((float)$bom->Quantity * $qty, 5);
                        $cd           = $compDetails[$componentUID] ?? null;

                        $this->_applyStockMovement($transUID, $moduleUID, $orgUID, $userUID, $componentUID, $componentQty, $unitCost, $movementType, $transProdUID, $sellingPrice, $taxAmount, null, $branchUID);

                        if ($transProdUID !== null && $cd !== null) {
                            $sp      = isset($passedCompPrices[$componentUID])
                                        ? $passedCompPrices[$componentUID]
                                        : (float)($cd->SellingPrice ?? 0);
                            $dbSp    = isset($passedCompSellPrices[$componentUID])
                                        ? $passedCompSellPrices[$componentUID]
                                        : (float)($cd->SellingPrice ?? 0);
                            $pp      = (float)($cd->PurchasePrice  ?? 0);
                            $origSp  = (float)($cd->SellingPrice   ?? 0);
                            $taxPct  = (float)($cd->TaxPercentage  ?? 0);
                            $origUp  = $taxPct > 0 ? round($origSp / (1 + $taxPct / 100), 8) : $origSp;
                            $cgstPct = (float)($cd->CGST           ?? 0);
                            $sgstPct = (float)($cd->SGST           ?? 0);
                            $igstPct = (float)($cd->IGST           ?? 0);
                            $taxable = round($sp * $componentQty, 4);
                            $cgstAmt = round($taxable * $cgstPct / 100, 4);
                            $sgstAmt = round($taxable * $sgstPct / 100, 4);
                            $igstAmt = round($taxable * $igstPct / 100, 4);
                            $taxAmt  = round($cgstAmt + $sgstAmt + $igstAmt, 4);
                            $netAmt  = round($taxable + $taxAmt, 4);

                            $insOk = $this->WriteDB->insert('Transaction.TransProductBOMTbl', [
                                'ParentTransProdUID' => $transProdUID,
                                'OrgUID'             => $orgUID,
                                'FinancialYear'      => $txFinancialYear,
                                'TransUID'           => $transUID,
                                'ProductUID'         => $componentUID,
                                'ProductName'        => substr($cd->ItemName ?? '', 0, 100),
                                'Description'        => $cd->Description  ?? null,
                                'PartNumber'         => $cd->PartNumber   ?? null,
                                'CategoryUID'        => $cd->CategoryUID  ?? null,
                                'CategoryName'       => $cd->CategoryName ?? null,
                                'StorageUID'         => $cd->StorageUID   ?? null,
                                'Quantity'           => $componentQty,
                                'PrimaryUnitName'    => $cd->PrimaryUnitName ?? null,
                                'TaxDetailsUID'      => $cd->TaxDetailsUID  ?? null,
                                'TaxPercentage'      => (float)($cd->TaxPercentage ?? 0),
                                'CGST'               => $cgstPct,
                                'SGST'               => $sgstPct,
                                'IGST'               => $igstPct,
                                'UnitPrice'          => $sp,
                                'SellingPrice'       => $dbSp,
                                'PurchasePrice'      => $pp,
                                'OrigUnitPrice'      => $origUp,
                                'OrigSellingPrice'   => $origSp,
                                'TaxableAmount'      => $taxable,
                                'CgstAmount'         => $cgstAmt,
                                'SgstAmount'         => $sgstAmt,
                                'IgstAmount'         => $igstAmt,
                                'TaxAmount'          => $taxAmt,
                                'DiscountTypeUID'    => null,
                                'Discount'           => 0,
                                'DiscountAmount'     => 0,
                                'NetAmount'          => $netAmt,
                                'QuantityConverted'  => 0,
                                'IsActive'           => 1,
                                'IsDeleted'          => 0,
                                'CreatedBy'          => $userUID,
                                'UpdatedBy'          => $userUID,
                            ]);
                            if ($insOk === false) {
                                $err = $this->WriteDB->error();
                                throw new Exception('BOM snapshot insert failed (ComponentUID=' . $componentUID . '): ' . ($err['message'] ?? 'unknown DB error'));
                            }
                        }
                    }
                }
            } else {
                $this->_applyStockMovement($transUID, $moduleUID, $orgUID, $userUID, $productUID, $qty, $unitCost, $movementType, $transProdUID, $sellingPrice, $taxAmount, $prodData, $branchUID, $variantUID);
            }
        }

    }

    /**
     * @param mixed      $transUID
     * @param mixed      $moduleUID
     * @param mixed      $orgUID
     * @param mixed      $userUID
     * @param mixed      $productUID
     * @param mixed      $qty
     * @param mixed      $unitCost
     * @param string     $movementType
     * @param mixed|null $transProdUID
     * @param mixed|null $sellingPrice
     * @param mixed|null $taxAmount
     * @param mixed|null $snap
     * @param int        $branchUID
     * @param int        $variantUID
     * @returns void
     */
    private function _applyStockMovement($transUID, $moduleUID, $orgUID, $userUID, $productUID, $qty, $unitCost, $movementType, $transProdUID = null, $sellingPrice = null, $taxAmount = null, $snap = null, int $branchUID = 0, int $variantUID = 0) {

        $this->WriteDB->db_debug = FALSE;

        if ($snap === null) {
            $snap = $this->WriteDB->query(
                "SELECT p.ItemName, p.MRP, p.CGST, p.SGST, p.IGST, p.TaxPercentage,
                        p.CategoryUID, c.Name AS CategoryName,
                        p.PurchasePrice, p.PartNumber, p.Description
                 FROM Products.ProductTbl p
                 LEFT JOIN Products.CategoryTbl c
                        ON c.CategoryUID = p.CategoryUID AND c.IsDeleted = 0
                 WHERE p.ProductUID = ? AND p.IsDeleted = 0
                 LIMIT 1",
                [(int)$productUID]
            )->row();
        }

        $insOk = $this->WriteDB->insert('Products.StockLedgerTbl', [
            'OrgUID'             => $orgUID,
            'ProductUID'         => $productUID,
            'VariantUID'         => $variantUID > 0 ? $variantUID : null,
            'TransUID'           => $transUID,
            'TransProdUID'       => $transProdUID,
            'ModuleUID'          => $moduleUID,
            'MovementType'       => $movementType,
            'Quantity'           => $qty,
            'UnitCost'           => $unitCost,
            'SellingPrice'       => $sellingPrice,
            'TaxAmount'          => $taxAmount,
            'Remarks'            => null,
            'SnapItemName'       => $snap->ItemName      ?? null,
            'SnapMRP'            => $snap->MRP           ?? null,
            'SnapCGST'           => $snap->CGST          ?? null,
            'SnapSGST'           => $snap->SGST          ?? null,
            'SnapIGST'           => $snap->IGST          ?? null,
            'SnapTaxPercentage'  => $snap->TaxPercentage ?? null,
            'SnapCategoryUID'    => $snap->CategoryUID   ?? null,
            'SnapCategoryName'   => $snap->CategoryName  ?? null,
            'SnapPurchasePrice'  => $snap->PurchasePrice ?? null,
            'SnapPartNumber'     => $snap->PartNumber    ?? null,
            'SnapDescription'    => $snap->Description   ?? null,
            'IsDeleted'          => 0,
            'CreatedBy'          => $userUID,
            'UpdatedBy'          => $userUID,
        ]);
        if ($insOk === false) {
            $err = $this->WriteDB->error();
            throw new Exception('Stock ledger insert failed (ProductUID=' . $productUID . '): ' . ($err['message'] ?? 'unknown DB error'));
        }

        if ($movementType === 'IN') {
            $this->WriteDB->set('AvailableQty', 'CAST(AvailableQty AS SIGNED) + ' . $qty, false);
        } else {
            $this->WriteDB->set('AvailableQty', 'CAST(AvailableQty AS SIGNED) - ' . $qty, false);
        }
        $this->WriteDB->where(['ProductUID' => $productUID]);
        $updOk = $this->WriteDB->update('Products.ProductStockTbl');
        if ($updOk === false) {
            $err = $this->WriteDB->error();
            throw new Exception('Stock quantity update failed (ProductUID=' . $productUID . '): ' . ($err['message'] ?? 'unknown DB error'));
        }

        if ($variantUID > 0) {
            if ($movementType === 'IN') {
                $this->WriteDB->query(
                    "INSERT INTO Products.ProductVariantStockTbl (VariantUID, OrgUID, OpeningQty, AvailableQty)
                     VALUES (?, ?, 0, ?)
                     ON DUPLICATE KEY UPDATE AvailableQty = CAST(AvailableQty AS SIGNED) + ?",
                    [$variantUID, (int)$orgUID, $qty, $qty]
                );
            } else {
                $this->WriteDB->query(
                    "INSERT INTO Products.ProductVariantStockTbl (VariantUID, OrgUID, OpeningQty, AvailableQty)
                     VALUES (?, ?, 0, ?)
                     ON DUPLICATE KEY UPDATE AvailableQty = CAST(AvailableQty AS SIGNED) - ?",
                    [$variantUID, (int)$orgUID, -$qty, $qty]
                );
            }
        }

    }

    /**
     * @param mixed $productUID
     * @param mixed $orgUID
     * @param float $openingQty
     * @returns void
     */
    public function initProductStock($productUID, $orgUID, $openingQty = 0.0) {
        $this->WriteDB->db_debug = FALSE;
        $this->WriteDB->insert('Products.ProductStockTbl', [
            'ProductUID'   => (int)$productUID,
            'OrgUID'       => (int)$orgUID,
            'AvailableQty' => (float)$openingQty,
        ]);
    }

    /**
     * @param int   $productUID
     * @param int   $orgUID
     * @param float $delta
     * @returns void
     */
    public function applyOpeningQtyDelta(int $productUID, int $orgUID, float $delta) {
        if ($delta == 0.0) return;
        $this->WriteDB->db_debug = FALSE;
        $abs  = abs($delta);
        $expr = $delta > 0
            ? 'CAST(AvailableQty AS SIGNED) + ' . $abs
            : 'CAST(AvailableQty AS SIGNED) - ' . $abs;
        $this->WriteDB->query(
            "UPDATE Products.ProductStockTbl SET AvailableQty = {$expr} WHERE ProductUID = ? AND OrgUID = ?",
            [$productUID, $orgUID]
        );
    }

    /**
     * @param int    $orgUID
     * @param string $sizeName
     * @param int    $userUID
     * @returns int
     */
    public function addSize(int $orgUID, string $sizeName, int $userUID): int {
        $this->WriteDB->db_debug = FALSE;
        $this->WriteDB->query(
            "INSERT INTO Products.SizeTbl (OrgUID, Name)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE SizeUID = LAST_INSERT_ID(SizeUID)",
            [$orgUID, $sizeName]
        );
        $row = $this->WriteDB->query("SELECT LAST_INSERT_ID() AS id")->row();
        return (int)($row->id ?? 0);
    }

    /**
     * @param int    $productUID
     * @param int    $orgUID
     * @param int    $brandUID
     * @param int    $sizeUID
     * @param string $partNumber
     * @param float  $purchasePrice
     * @param int    $purchaseTaxUID
     * @param float  $sellingPrice
     * @param int    $sellingTaxUID
     * @param int    $userUID
     * @returns int
     */
    public function upsertProductVariant(int $productUID, int $orgUID, int $brandUID, int $sizeUID, string $partNumber, float $purchasePrice, int $purchaseTaxUID, float $sellingPrice, int $sellingTaxUID, int $userUID): int {
        $this->WriteDB->db_debug = FALSE;
        $this->WriteDB->query(
            "INSERT INTO Products.ProductVariantTbl
                 (ProductUID, OrgUID, BrandUID, SizeUID, PartNumber, PurchasePrice, PurchaseTaxUID, SellingPrice, SellingTaxUID, UpdatedBy)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                 VariantUID     = LAST_INSERT_ID(VariantUID),
                 PartNumber     = VALUES(PartNumber),
                 PurchasePrice  = VALUES(PurchasePrice),
                 PurchaseTaxUID = VALUES(PurchaseTaxUID),
                 SellingPrice   = VALUES(SellingPrice),
                 SellingTaxUID  = VALUES(SellingTaxUID),
                 UpdatedBy      = VALUES(UpdatedBy)",
            [$productUID, $orgUID, $brandUID, $sizeUID, $partNumber, $purchasePrice, $purchaseTaxUID, $sellingPrice, $sellingTaxUID, $userUID]
        );
        $row = $this->WriteDB->query("SELECT LAST_INSERT_ID() AS id")->row();
        return (int)($row->id ?? 0);
    }

    /**
     * @param int   $variantUID
     * @param int   $orgUID
     * @param float $openingQty
     * @returns void
     */
    public function upsertVariantStock(int $variantUID, int $orgUID, float $openingQty): void {
        $this->WriteDB->db_debug = FALSE;
        $this->WriteDB->query(
            "INSERT INTO Products.ProductVariantStockTbl
                 (VariantUID, OrgUID, OpeningQty, AvailableQty)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                 AvailableQty = AvailableQty + (VALUES(OpeningQty) - OpeningQty),
                 OpeningQty   = VALUES(OpeningQty)",
            [$variantUID, $orgUID, $openingQty, $openingQty]
        );
    }

    /**
     * @param int $productUID
     * @param int $orgUID
     * @returns void
     */
    public function syncVariantStockTotal(int $productUID, int $orgUID): void {
        $this->WriteDB->db_debug = FALSE;
        $this->WriteDB->query(
            "UPDATE Products.ProductStockTbl pst
             SET pst.AvailableQty = (
                 SELECT COALESCE(SUM(pvs.AvailableQty), 0)
                 FROM Products.ProductVariantTbl pv
                 JOIN Products.ProductVariantStockTbl pvs ON pvs.VariantUID = pv.VariantUID AND pvs.OrgUID = pst.OrgUID
                 WHERE pv.ProductUID = ? AND pv.OrgUID = ?
             )
             WHERE pst.ProductUID = ? AND pst.OrgUID = ?",
            [$productUID, $orgUID, $productUID, $orgUID]
        );
    }

    /**
     * @param mixed $adjUID
     * @param mixed $orgUID
     * @param mixed $userUID
     * @param mixed $productUID
     * @param mixed $qty
     * @param mixed $unitCost
     * @param mixed $adjType
     * @param int   $branchUID
     * @param int   $variantUID
     * @returns void
     */
    public function applyManualStockAdjustment($adjUID, $orgUID, $userUID, $productUID, $qty, $unitCost, $adjType, int $branchUID = 0, int $variantUID = 0): void {
        $movementType = ($adjType === 'IN') ? 'IN' : 'OUT';
        $this->_applyStockMovement((int)$adjUID, 118, (int)$orgUID, (int)$userUID, (int)$productUID, (float)$qty, (float)$unitCost, $movementType, null, null, null, null, $branchUID, $variantUID);
    }

    /**
     * @param int   $transUID
     * @param int   $moduleUID
     * @param int   $orgUID
     * @param int   $userUID
     * @param int   $productUID
     * @param float $qty
     * @param int   $transProdUID
     * @param int   $branchUID
     * @returns void
     */
    public function applyDCReturnStockMovement(int $transUID, int $moduleUID, int $orgUID, int $userUID, int $productUID, float $qty, int $transProdUID, int $branchUID = 0): void {
        $this->_applyStockMovement($transUID, $moduleUID, $orgUID, $userUID, $productUID, $qty, 0.0, 'IN', $transProdUID, null, null, null, $branchUID);
    }

    /**
     * @param mixed $transUID
     * @param mixed $orgUID
     * @param mixed $userUID
     * @returns void
     */
    public function reverseStockMovements($transUID, $orgUID, $userUID) {

        $this->WriteDB->db_debug = FALSE;
        $this->WriteDB->select('LedgerUID, ProductUID, VariantUID, MovementType, Quantity');
        $this->WriteDB->from('Products.StockLedgerTbl');
        $this->WriteDB->where(['TransUID' => $transUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]);
        $query = $this->WriteDB->get();
        if (!$query) {
            $err = $this->WriteDB->error();
            throw new Exception('Stock reversal read failed: ' . ($err['message'] ?? 'unknown error'));
        }
        $ledgerRows = $query->result();

        foreach ($ledgerRows as $row) {
            if ($row->MovementType === 'IN') {
                $qtyExpr    = 'CAST(AvailableQty AS SIGNED) - ' . (float)$row->Quantity;
                $varQtyExpr = 'CAST(AvailableQty AS SIGNED) - ' . (float)$row->Quantity;
            } else {
                $qtyExpr    = 'AvailableQty + ' . (float)$row->Quantity;
                $varQtyExpr = 'AvailableQty + ' . (float)$row->Quantity;
            }

            $ok = $this->WriteDB->query(
                "UPDATE Products.ProductStockTbl SET AvailableQty = {$qtyExpr} WHERE ProductUID = ?",
                [(int)$row->ProductUID]
            );
            if ($ok === false) {
                $err = $this->WriteDB->error();
                throw new Exception('Stock quantity reversal failed (ProductUID=' . $row->ProductUID . '): ' . ($err['message'] ?? 'unknown error'));
            }

            if (!empty($row->VariantUID)) {
                $this->WriteDB->query(
                    "UPDATE Products.ProductVariantStockTbl SET AvailableQty = {$varQtyExpr} WHERE VariantUID = ? AND OrgUID = ?",
                    [(int)$row->VariantUID, (int)$orgUID]
                );
            }

            $ok = $this->WriteDB->query(
                "UPDATE Products.StockLedgerTbl SET IsDeleted = 1, UpdatedBy = ? WHERE LedgerUID = ?",
                [(int)$userUID, (int)$row->LedgerUID]
            );
            if ($ok === false) {
                $err = $this->WriteDB->error();
                throw new Exception('Stock ledger soft-delete failed (LedgerUID=' . $row->LedgerUID . '): ' . ($err['message'] ?? 'unknown error'));
            }
        }

        $this->WriteDB->query(
            "UPDATE Transaction.TransProductBOMTbl
                SET IsDeleted = 1, UpdatedBy = ?
              WHERE TransUID = ? AND OrgUID = ? AND IsDeleted = 0",
            [(int)$userUID, (int)$transUID, (int)$orgUID]
        );

    }

    /**
     * @param mixed $transUID
     * @param mixed $orgUID
     * @returns void
     */
    public function checkStockForReversal($transUID, $orgUID) {
        $this->WriteDB->db_debug = FALSE;
        $sql = "
            SELECT SL.SnapItemName, SL.Quantity, PS.AvailableQty
              FROM Products.StockLedgerTbl SL
              JOIN Products.ProductStockTbl PS ON PS.ProductUID = SL.ProductUID
             WHERE SL.TransUID     = ?
               AND SL.OrgUID       = ?
               AND SL.IsDeleted    = 0
               AND SL.MovementType = 'IN'
               AND (CAST(PS.AvailableQty AS SIGNED) - SL.Quantity) < 0
        ";
        $query = $this->WriteDB->query($sql, [(int)$transUID, (int)$orgUID]);
        if (!$query) {
            $err = $this->WriteDB->error();
            throw new Exception('Stock pre-check failed: ' . ($err['message'] ?? 'unknown error'));
        }
        $rows = $query->result();
        if (!empty($rows)) {
            $details = array_map(
                fn($r) => ($r->SnapItemName ?? 'Unknown') . ' (available: ' . $r->AvailableQty . ', to reverse: ' . $r->Quantity . ')',
                $rows
            );
            throw new Exception(
                'Cannot cancel: reversing stock would result in negative quantity for - ' .
                implode('; ', $details) . '. Please adjust stock manually first.'
            );
        }
    }

    /**
     * @param mixed $transUID
     * @param mixed $orgUID
     * @param mixed $userUID
     * @returns void
     */
    public function softDeleteTransaction($transUID, $orgUID, $userUID) {
        $this->WriteDB->db_debug = FALSE;
        $ok = $this->WriteDB->query(
            "UPDATE Transaction.TransactionsTbl
                SET IsDeleted = 1, IsActive = 0, UpdatedBy = ?, UpdatedOn = NOW()
              WHERE TransUID = ? AND OrgUID = ? AND IsDeleted = 0",
            [(int)$userUID, (int)$transUID, (int)$orgUID]
        );
        if ($ok === false) {
            $err = $this->WriteDB->error();
            throw new Exception('Failed to delete invoice: ' . ($err['message'] ?? 'unknown error'));
        }
    }

    /**
     * @param mixed $transUID
     * @param mixed $userUID
     * @returns void
     */
    public function softDeleteTransactionItems($transUID, $userUID) {
        $this->WriteDB->db_debug = FALSE;
        $ok = $this->WriteDB->query(
            "UPDATE Transaction.TransProductsTbl
                SET IsDeleted = 1, IsActive = 0, UpdatedBy = ?, UpdatedOn = NOW()
              WHERE TransUID = ? AND IsDeleted = 0",
            [(int)$userUID, (int)$transUID]
        );
        if ($ok === false) {
            $err = $this->WriteDB->error();
            throw new Exception('Failed to delete invoice items: ' . ($err['message'] ?? 'unknown error'));
        }
    }

    /**
     * @param mixed $transUID
     * @param array $productUIDs
     * @param mixed $userUID
     * @returns void
     */
    public function softDeleteTransactionItemsByProductUIDs($transUID, array $productUIDs, $userUID) {
        if (empty($productUIDs)) return;
        $this->WriteDB->db_debug = FALSE;
        $placeholders = implode(',', array_fill(0, count($productUIDs), '?'));
        $params = array_merge([(int)$userUID, (int)$transUID], array_map('intval', $productUIDs));
        $ok = $this->WriteDB->query(
            "UPDATE Transaction.TransProductsTbl
                SET IsDeleted = 1, IsActive = 0, UpdatedBy = ?, UpdatedOn = NOW(),
                    ItemSequence = NULL
              WHERE TransUID = ? AND ProductUID IN ({$placeholders}) AND IsDeleted = 0",
            $params
        );
        if ($ok === false) {
            $err = $this->WriteDB->error();
            throw new Exception('Failed to soft-delete removed items: ' . ($err['message'] ?? 'unknown error'));
        }
    }

    /**
     * @param int   $transUID
     * @param array $productUIDs
     * @returns void
     */
    public function shiftTransProductSequences(int $transUID, array $productUIDs): void {
        if (empty($productUIDs)) return;
        $this->WriteDB->db_debug = FALSE;
        $placeholders = implode(',', array_fill(0, count($productUIDs), '?'));
        $params = array_merge([(int)$transUID], array_map('intval', $productUIDs));
        $ok = $this->WriteDB->query(
            "UPDATE Transaction.TransProductsTbl
                SET ItemSequence = NULL
              WHERE TransUID = ? AND ProductUID IN ({$placeholders}) AND IsDeleted = 0",
            $params
        );
        if ($ok === false) {
            $err = $this->WriteDB->error();
            throw new Exception('Failed to clear item sequences: ' . ($err['message'] ?? 'unknown error'));
        }
    }

    /**
     * @param mixed $transUID
     * @param mixed $productUID
     * @param array $data
     * @returns void
     */
    public function updateTransProductItem($transUID, $productUID, array $data) {
        $this->WriteDB->db_debug = FALSE;
        $this->WriteDB->where(['TransUID' => (int)$transUID, 'ProductUID' => (int)$productUID, 'IsDeleted' => 0]);
        $ok = $this->WriteDB->update('Transaction.TransProductsTbl', $data);
        if ($ok === false) {
            $err = $this->WriteDB->error();
            throw new Exception('Failed to update item (ProductUID=' . $productUID . '): ' . ($err['message'] ?? 'unknown error'));
        }
    }

    /**
     * @param mixed $transUID
     * @param mixed $orgUID
     * @param mixed $newStatus
     * @param mixed $userUID
     * @returns mixed
     */
    public function updateTransDocStatus($transUID, $orgUID, $newStatus, $userUID) {
        $this->WriteDB->db_debug = FALSE;
        return $this->WriteDB->query(
            "UPDATE Transaction.TransactionsTbl
                SET DocStatus = ?, UpdatedBy = ?, UpdatedOn = NOW()
              WHERE TransUID = ? AND OrgUID = ?",
            [$newStatus, (int) $userUID, (int) $transUID, (int) $orgUID]
        );
    }

    /**
     * @param mixed $transUID
     * @param mixed $isFullyPaid
     * @param mixed $paidAmount
     * @param mixed $balanceAmount
     * @param mixed $userUID
     * @returns mixed
     */
    public function updateTransIsFullyPaid($transUID, $isFullyPaid, $paidAmount, $balanceAmount, $userUID) {
        $this->WriteDB->db_debug = FALSE;
        return $this->WriteDB->query(
            "UPDATE Transaction.TransactionsTbl
                SET IsFullyPaid = ?, PaidAmount = ?, BalanceAmount = ?, UpdatedBy = ?
              WHERE TransUID = ?",
            [(int) $isFullyPaid, (float) $paidAmount, (float) $balanceAmount, (int) $userUID, (int) $transUID]
        );
    }

    /**
     * @param array $data
     * @returns void
     */
    public function insertAuditLog(array $data): void {
        $this->WriteDB->db_debug = FALSE;
        $this->WriteDB->insert('Security.UserAuditLogTbl', $data);
    }

    // ── Payment read helpers (WriteDB to avoid read-replica lag) ──────────────

    /**
     * @param mixed $paymentUID
     * @param mixed $orgUID
     * @returns object|null
     */
    public function getOnAccountPayment($paymentUID, $orgUID) {
        $this->WriteDB->db_debug = FALSE;
        $this->WriteDB->from('Transaction.PaymentsTbl');
        $this->WriteDB->where(['PaymentUID' => (int)$paymentUID, 'OrgUID' => (int)$orgUID, 'IsOnAccount' => 1, 'IsDeleted' => 0, 'IsCancelled' => 0]);
        return $this->WriteDB->get()->row();
    }

    /**
     * @param mixed $paymentUID
     * @param mixed $orgUID
     * @returns object|null
     */
    public function getAppliedChildPayment($paymentUID, $orgUID) {
        $this->WriteDB->db_debug = FALSE;
        $this->WriteDB->select('P.PaymentUID, T.UniqueNumber');
        $this->WriteDB->from('Transaction.PaymentsTbl P');
        $this->WriteDB->join('Transaction.TransactionsTbl T', 'T.TransUID = P.TransUID', 'left');
        $this->WriteDB->where(['P.OnAccountSourcePaymentUID' => (int)$paymentUID, 'P.OrgUID' => (int)$orgUID, 'P.IsCancelled' => 0, 'P.IsDeleted' => 0]);
        $this->WriteDB->limit(1);
        return $this->WriteDB->get()->row();
    }

    /**
     * @param mixed $paymentUID
     * @param mixed $orgUID
     * @returns object|null
     */
    public function getOnAccountSourcePayment($paymentUID, $orgUID) {
        $this->WriteDB->db_debug = FALSE;
        $this->WriteDB->select('PaymentUID, Amount, IsOnAccount');
        $this->WriteDB->from('Transaction.PaymentsTbl');
        $this->WriteDB->where(['PaymentUID' => (int)$paymentUID, 'OrgUID' => (int)$orgUID, 'IsDeleted' => 0]);
        return $this->WriteDB->get()->row();
    }

    // ── Payment write helpers ─────────────────────────────────────────────────

    /**
     * @param mixed $sourceOAUID
     * @param mixed $orgUID
     * @param mixed $restoredAmount
     * @param mixed $userUID
     * @returns void
     */
    public function restoreOnAccountPayment($sourceOAUID, $orgUID, $restoredAmount, $userUID) {
        $this->WriteDB->db_debug = FALSE;
        $this->WriteDB->where(['PaymentUID' => (int)$sourceOAUID, 'OrgUID' => (int)$orgUID]);
        $this->WriteDB->update('Transaction.PaymentsTbl', [
            'Amount'                   => $restoredAmount,
            'IsOnAccount'              => 1,
            'OnAccountAppliedTransUID' => NULL,
            'UpdatedBy'                => (int)$userUID,
        ]);
    }

    /**
     * @param mixed $paymentUID
     * @param mixed $orgUID
     * @param mixed $transUID
     * @param mixed $userUID
     * @returns void
     */
    public function applyOnAccountPayment($paymentUID, $orgUID, $transUID, $userUID) {
        $this->WriteDB->db_debug = FALSE;
        $this->WriteDB->where(['PaymentUID' => (int)$paymentUID, 'OrgUID' => (int)$orgUID]);
        $this->WriteDB->update('Transaction.PaymentsTbl', [
            'TransUID'                 => (int)$transUID,
            'IsOnAccount'              => 0,
            'OnAccountAppliedTransUID' => (int)$transUID,
            'UpdatedBy'                => (int)$userUID,
        ]);
    }

    /**
     * @param mixed $transUID
     * @param mixed $orgUID
     * @param mixed $userUID
     * @returns void
     */
    public function markPaymentsDeletedForTrans($transUID, $orgUID, $userUID) {
        $this->WriteDB->db_debug = FALSE;
        $this->WriteDB->where([
            'TransUID'         => (int)$transUID,
            'PartyType'        => 'C',
            'PaymentDirection' => 'In',
            'IsDeleted'        => 0,
        ])->update('Transaction.PaymentsTbl', [
            'IsDeleted' => 1,
            'UpdatedBy' => (int)$userUID,
        ]);
    }

    /**
     * @param int $transUID
     * @param int $orgUID
     * @param int $userUID
     * @returns void
     */
    public function markVendorPaymentsDeletedForTrans(int $transUID, int $orgUID, int $userUID): void {
        $this->WriteDB->db_debug = FALSE;
        $this->WriteDB->where([
            'TransUID'         => $transUID,
            'OrgUID'           => $orgUID,
            'PartyType'        => 'S',
            'PaymentDirection' => 'Out',
            'IsDeleted'        => 0,
        ])->update('Transaction.PaymentsTbl', [
            'IsDeleted' => 1,
            'UpdatedBy' => $userUID,
        ]);
    }

    /**
     * @param int $transUID
     * @param int $orgUID
     * @param int $userUID
     * @returns void
     */
    public function markVendorPaymentsCancelledForTrans(int $transUID, int $orgUID, int $userUID): void {
        $this->WriteDB->db_debug = FALSE;
        $this->WriteDB->where([
            'TransUID'         => $transUID,
            'OrgUID'           => $orgUID,
            'PartyType'        => 'S',
            'PaymentDirection' => 'Out',
            'IsCancelled'      => 0,
            'IsDeleted'        => 0,
        ])->update('Transaction.PaymentsTbl', [
            'IsCancelled' => 1,
            'UpdatedBy'   => $userUID,
        ]);
    }

    /**
     * @param mixed $transUID
     * @param mixed $userUID
     * @returns void
     */
    public function cancelTransactionChildRecords($transUID, $userUID) {
        $this->WriteDB->db_debug = FALSE;
        $flag   = ['IsCancelled' => 1, 'UpdatedBy' => (int)$userUID];
        $tables = [
            'Transaction.TransProductsTbl',
            'Transaction.TransProductBOMTbl',
            'Transaction.TransAttachmentsTbl',
        ];
        foreach ($tables as $table) {
            $this->WriteDB->where('TransUID', (int)$transUID)->update($table, $flag);
        }
    }

    /**
     * @param mixed $transUID
     * @param mixed $orgUID
     * @param mixed $userUID
     * @returns void
     */
    public function markPaymentsOnAccount($transUID, $orgUID, $userUID) {
        $this->WriteDB->db_debug = FALSE;
        $this->WriteDB->where([
            'TransUID'         => (int)$transUID,
            'PartyType'        => 'C',
            'PaymentDirection' => 'In',
            'IsDeleted'        => 0,
            'IsCancelled'      => 0,
        ])->update('Transaction.PaymentsTbl', [
            'IsOnAccount' => 1,
            'UpdatedBy'   => (int)$userUID,
        ]);
    }

    /**
     * @param mixed $transUID
     * @param mixed $orgUID
     * @param mixed $userUID
     * @returns void
     */
    public function markPaymentsRefunded($transUID, $orgUID, $userUID) {
        $this->WriteDB->db_debug = FALSE;
        $this->WriteDB->where([
            'TransUID'         => (int)$transUID,
            'PartyType'        => 'C',
            'PaymentDirection' => 'In',
            'IsDeleted'        => 0,
        ])->update('Transaction.PaymentsTbl', [
            'IsCancelled' => 1,
            'UpdatedBy'   => (int)$userUID,
        ]);
    }

    // ── Settings upserts ──────────────────────────────────────────────────────

    /**
     * @param mixed $orgUID
     * @param mixed $productTypeUID
     * @param mixed $discountTypeUID
     * @param mixed $productTaxUID
     * @param mixed $taxDetailUID
     * @param mixed $userUID
     * @returns bool
     */
    public function upsertProductSettings($orgUID, $productTypeUID, $discountTypeUID, $productTaxUID, $taxDetailUID, $userUID) {
        $this->WriteDB->db_debug = FALSE;
        $sql = "INSERT INTO Settings.OrgProductSettingsTbl
                    (OrgUID, DefaultProductTypeUID, DefaultDiscountTypeUID, DefaultProductTaxUID, DefaultTaxDetailUID, UpdatedBy)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    DefaultProductTypeUID  = VALUES(DefaultProductTypeUID),
                    DefaultDiscountTypeUID = VALUES(DefaultDiscountTypeUID),
                    DefaultProductTaxUID   = VALUES(DefaultProductTaxUID),
                    DefaultTaxDetailUID    = VALUES(DefaultTaxDetailUID),
                    UpdatedBy              = VALUES(UpdatedBy)";
        $ok = $this->WriteDB->query($sql, [
            (int)$orgUID, (int)$productTypeUID, (int)$discountTypeUID,
            (int)$productTaxUID, (int)$taxDetailUID, (int)$userUID,
        ]);
        if (!$ok) {
            $err = $this->WriteDB->error();
            throw new Exception($err['message'] ?? 'Failed to save product settings.');
        }
        return true;
    }

    /**
     * @param int    $orgUID
     * @param string $invoiceCancelAction
     * @param string $srCancelAction
     * @param string $srItemMethod
     * @param string $termsAndConditions
     * @param int    $hideNav
     * @param int    $purchaseShowSignature
     * @param int    $purchaseShowTerms
     * @param string $prCancelAction
     * @param string $prItemMethod
     * @param int    $showProductDescription
     * @param int    $userUID
     * @param int    $dcDefaultReturnDays
     * @param int    $quotValidityDays
     * @param int    $showTransactionStats
     * @param string $comboPriceDistribution
     * @param string $belowPurchasePriceAction
     * @param string $defaultTransactionType
     * @param int    $autoDraftSave
     * @param string $autoUpdatePurchasePrice
     * @param string $purchaseCancelAction
     * @returns bool
     */
    public function upsertTransactionSettings(int $orgUID, string $invoiceCancelAction, string $srCancelAction, string $srItemMethod, string $termsAndConditions, int $hideNav, int $purchaseShowSignature, int $purchaseShowTerms, string $prCancelAction, string $prItemMethod, int $showProductDescription, int $userUID, int $dcDefaultReturnDays = 7, int $quotValidityDays = 7, int $showTransactionStats = 1, string $comboPriceDistribution = 'ratio', string $belowPurchasePriceAction = 'warn', string $defaultTransactionType = 'regular', int $autoDraftSave = 1, string $autoUpdatePurchasePrice = 'off', string $purchaseCancelAction = 'ask'): bool {
        $this->WriteDB->db_debug = FALSE;
        $sql = "INSERT INTO Settings.TransactionSettingsTbl
                    (OrgUID, InvoiceCancelAction, SalesReturnCancelAction, SalesReturnItemMethod, TermsAndConditions, HideNavOnTransForm, PurchaseShowSignature, PurchaseShowTerms, PurchaseReturnCancelAction, PurchaseReturnItemMethod, ShowProductDescription, DCDefaultReturnDays, QuotValidityDays, ShowTransactionStats, ComboPriceDistribution, BelowPurchasePriceAction, DefaultTransactionType, AutoDraftSave, AutoUpdatePurchasePrice, PurchaseCancelAction, UpdatedBy)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    InvoiceCancelAction        = VALUES(InvoiceCancelAction),
                    SalesReturnCancelAction    = VALUES(SalesReturnCancelAction),
                    SalesReturnItemMethod      = VALUES(SalesReturnItemMethod),
                    TermsAndConditions         = VALUES(TermsAndConditions),
                    HideNavOnTransForm         = VALUES(HideNavOnTransForm),
                    PurchaseShowSignature      = VALUES(PurchaseShowSignature),
                    PurchaseShowTerms          = VALUES(PurchaseShowTerms),
                    PurchaseReturnCancelAction = VALUES(PurchaseReturnCancelAction),
                    PurchaseReturnItemMethod   = VALUES(PurchaseReturnItemMethod),
                    ShowProductDescription     = VALUES(ShowProductDescription),
                    DCDefaultReturnDays        = VALUES(DCDefaultReturnDays),
                    QuotValidityDays           = VALUES(QuotValidityDays),
                    ShowTransactionStats       = VALUES(ShowTransactionStats),
                    ComboPriceDistribution     = VALUES(ComboPriceDistribution),
                    BelowPurchasePriceAction   = VALUES(BelowPurchasePriceAction),
                    DefaultTransactionType     = VALUES(DefaultTransactionType),
                    AutoDraftSave              = VALUES(AutoDraftSave),
                    AutoUpdatePurchasePrice    = VALUES(AutoUpdatePurchasePrice),
                    PurchaseCancelAction       = VALUES(PurchaseCancelAction),
                    UpdatedBy                  = VALUES(UpdatedBy)";
        $ok = $this->WriteDB->query($sql, [
            $orgUID, $invoiceCancelAction, $srCancelAction,
            $srItemMethod, $termsAndConditions, $hideNav, $purchaseShowSignature, $purchaseShowTerms,
            $prCancelAction, $prItemMethod, $showProductDescription, $dcDefaultReturnDays, $quotValidityDays,
            $showTransactionStats, $comboPriceDistribution, $belowPurchasePriceAction, $defaultTransactionType, $autoDraftSave, $autoUpdatePurchasePrice, $purchaseCancelAction, $userUID,
        ]);
        if (!$ok) {
            $err = $this->WriteDB->error();
            throw new Exception($err['message'] ?? 'Failed to save transaction settings.');
        }
        return true;
    }

    /**
     * @param int    $orgUID
     * @param int    $branchUID
     * @param int    $userUID
     * @param string $key
     * @param string $value
     * @returns bool
     */
    public function upsertPreference(int $orgUID, int $branchUID, int $userUID, string $key, string $value): bool {
        $this->WriteDB->db_debug = FALSE;
        $ok = $this->WriteDB->query(
            "INSERT INTO Users.UserPreferencesTbl (OrgUID, BranchUID, UserUID, PreferenceKey, PreferenceValue)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE PreferenceValue = VALUES(PreferenceValue), UpdatedAt = NOW()",
            [(int)$orgUID, (int)$branchUID, (int)$userUID, $key, (string)$value]
        );
        if (!$ok) {
            $err = $this->WriteDB->error();
            throw new Exception($err['message'] ?? 'Failed to save preference.');
        }
        return true;
    }

    // ── Conversion Tracking ───────────────────────────────────────────────────

    /**
     * @param mixed $orgUID
     * @param mixed $sourceUID
     * @param mixed $sourceModuleUID
     * @param mixed $targetUID
     * @param mixed $targetModuleUID
     * @param mixed $conversionType
     * @param mixed $userUID
     * @returns void
     */
    public function insertConversionRecord($orgUID, $sourceUID, $sourceModuleUID, $targetUID, $targetModuleUID, $conversionType, $userUID) {
        $this->WriteDB->db_debug = FALSE;
        $this->WriteDB->select('ConversionUID');
        $this->WriteDB->from('Transaction.TransConversionTbl');
        $this->WriteDB->where(['SourceTransUID' => (int)$sourceUID, 'TargetTransUID' => (int)$targetUID]);
        $this->WriteDB->limit(1);
        if ($this->WriteDB->get()->row()) return;

        $this->WriteDB->insert('Transaction.TransConversionTbl', [
            'OrgUID'          => (int) $orgUID,
            'SourceTransUID'  => (int) $sourceUID,
            'SourceModuleUID' => (int) $sourceModuleUID,
            'TargetTransUID'  => (int) $targetUID,
            'TargetModuleUID' => (int) $targetModuleUID,
            'ConversionType'  => $conversionType,
            'CreatedBy'       => (int) $userUID,
        ]);
    }

    /**
     * @param int $targetTransUID
     * @param int $orgUID
     * @param int $userUID
     * @returns void
     */
    public function markConversionDeleted(int $targetTransUID, int $orgUID, int $userUID): void {
        $this->WriteDB->db_debug = FALSE;
        $this->WriteDB->where(['TargetTransUID' => $targetTransUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0])
                      ->update('Transaction.TransConversionTbl', [
                          'IsDeleted' => 1,
                          'UpdatedBy' => $userUID,
                      ]);
    }

    /**
     * @param int $targetTransUID
     * @param int $orgUID
     * @param int $userUID
     * @returns void
     */
    public function markConversionCancelled(int $targetTransUID, int $orgUID, int $userUID): void {
        $this->WriteDB->db_debug = FALSE;
        $this->WriteDB->where(['TargetTransUID' => $targetTransUID, 'OrgUID' => $orgUID, 'IsCancelled' => 0, 'IsDeleted' => 0])
                      ->update('Transaction.TransConversionTbl', [
                          'IsCancelled' => 1,
                          'UpdatedBy'   => $userUID,
                      ]);
    }

    // ── User / Access helpers ─────────────────────────────────────────────────

    /**
     * @param int    $userUID
     * @param int    $orgUID
     * @param array  $branches
     * @param int    $callerUID
     * @param string $now
     * @returns void
     */
    public function syncUserBranchAccess(int $userUID, int $orgUID, array $branches, int $callerUID, string $now): void {
        $this->WriteDB->db_debug = FALSE;
        $this->WriteDB->query(
            'DELETE FROM Users.UserBranchAccessTbl WHERE UserUID = ? AND OrgUID = ?',
            [(int)$userUID, (int)$orgUID]
        );
        foreach ($branches as $b) {
            $branchUID = (int)($b['BranchUID'] ?? 0);
            if ($branchUID <= 0) continue;
            $this->WriteDB->insert('Users.UserBranchAccessTbl', [
                'UserUID'   => $userUID,
                'OrgUID'    => $orgUID,
                'BranchUID' => $branchUID,
                'IsDefault' => (int)($b['IsDefault'] ?? 0),
                'IsActive'  => 1,
            ]);
        }
    }

    // ── Password history ──────────────────────────────────────────────────────

    /**
     * @param int $uid
     * @param int $keepLast
     * @returns void
     */
    public function prunePasswordHistory(int $uid, int $keepLast = 5): void {
        $this->WriteDB->db_debug = FALSE;
        $this->WriteDB->query(
            "DELETE FROM Users.PasswordHistoryTbl
              WHERE UserUID = ?
                AND HistoryUID NOT IN (
                    SELECT h FROM (
                        SELECT HistoryUID AS h FROM Users.PasswordHistoryTbl
                         WHERE UserUID = ?
                         ORDER BY CreatedOn DESC
                         LIMIT ?
                    ) tmp
                )",
            [$uid, $uid, $keepLast]
        );
    }

    // ── Subscription helpers ──────────────────────────────────────────────────

    /**
     * @param int $orgUID
     * @returns void
     */
    public function activateLatestOrgSubscription(int $orgUID): void {
        $this->WriteDB->db_debug = FALSE;
        $this->WriteDB->query(
            "UPDATE Billing.OrgSubscriptionTbl
                SET Status = 'Active'
              WHERE OrgUID = ?
                AND Status <> 'Cancelled'
              ORDER BY StartDate DESC
              LIMIT 1",
            [$orgUID]
        );
    }

    // ── GSTIN credit deduction ────────────────────────────────────────────────

    /**
     * @param int $orgUID
     * @returns void
     */
    public function decrementGstinPoints(int $orgUID): void {
        $this->WriteDB->db_debug = FALSE;
        $this->WriteDB->query(
            "UPDATE Settings.OrgCreditSettingsTbl
                SET GstinPoints = GstinPoints - 1, UpdatedAt = NOW()
              WHERE OrgUID = ? AND GstinPoints > 0",
            [$orgUID]
        );
    }

    // ── Library wrappers ──────────────────────────────────────────────────────

    /**
     * @param int    $orgUID
     * @param int    $partyUID
     * @param int    $transUID
     * @param string $uniqueNumber
     * @param float  $amount
     * @param int    $userUID
     * @returns void
     */
    public function callCustomerDebitNote(int $orgUID, int $partyUID, int $transUID, string $uniqueNumber, float $amount, int $userUID): void {
        $this->load->library('customerbalance');
        $this->customerbalance->createDebitNote($orgUID, $partyUID, $transUID, $uniqueNumber, $amount, $userUID, $this->WriteDB);
    }

    /**
     * @param int    $orgUID
     * @param int    $partyUID
     * @param int    $transUID
     * @param string $uniqueNumber
     * @param float  $amount
     * @param int    $userUID
     * @returns void
     */
    public function callVendorCreditNote(int $orgUID, int $partyUID, int $transUID, string $uniqueNumber, float $amount, int $userUID): void {
        $this->load->library('vendorbalance');
        $this->vendorbalance->createVendorCreditNote($orgUID, $partyUID, $transUID, $uniqueNumber, $amount, $userUID, $this->WriteDB);
    }

    // ── Sales-return conversion link cleanup ──────────────────────────────────

    /**
     * @param int   $targetTransUID
     * @param int   $orgUID
     * @param array $excludeSourceUIDs
     * @param int   $userUID
     * @returns void
     */
    public function softDeleteSalesReturnConversionLinks(int $targetTransUID, int $orgUID, array $excludeSourceUIDs, int $userUID): void {
        $this->WriteDB->db_debug = FALSE;
        $this->WriteDB->where(['TargetTransUID' => $targetTransUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]);
        if (!empty($excludeSourceUIDs)) {
            $this->WriteDB->where_not_in('SourceTransUID', $excludeSourceUIDs);
        }
        $this->WriteDB->update('Transaction.TransConversionTbl', ['IsDeleted' => 1, 'UpdatedBy' => $userUID]);
    }

    // ── Refund payment status helpers ─────────────────────────────────────────

    /**
     * @param int $transUID
     * @param int $userUID
     * @returns void
     */
    public function cancelRefundPaymentsForTrans(int $transUID, int $userUID): void {
        $this->WriteDB->db_debug = FALSE;
        $this->WriteDB->where(['TransUID' => $transUID, 'IsDeleted' => 0])
                      ->where('PaymentTypeUID !=', 0)
                      ->update('Transaction.PaymentsTbl', ['IsCancelled' => 1, 'UpdatedBy' => $userUID]);
    }

    /**
     * @param int $transUID
     * @param int $userUID
     * @returns void
     */
    public function deleteRefundPaymentsForTrans(int $transUID, int $userUID): void {
        $this->WriteDB->db_debug = FALSE;
        $this->WriteDB->where(['TransUID' => $transUID, 'IsDeleted' => 0])
                      ->where('PaymentTypeUID !=', 0)
                      ->update('Transaction.PaymentsTbl', ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID]);
    }

    // ── Transaction locking helpers (WriteDB — avoids read-replica lag) ───────

    /**
     * @param int $transUID
     * @param int $orgUID
     * @returns bool
     */
    public function lockTransactionRow(int $transUID, int $orgUID): bool {
        $query = $this->WriteDB->query(
            'SELECT TransUID FROM Transaction.TransactionsTbl WHERE TransUID = ? AND OrgUID = ? LIMIT 1 FOR UPDATE',
            [$transUID, $orgUID]
        );
        return $query && $query->num_rows() > 0;
    }

    /**
     * @param int $transUID
     * @param int $orgUID
     * @returns float
     */
    public function sumTransactionPayments(int $transUID, int $orgUID): float {
        $query = $this->WriteDB->query(
            'SELECT COALESCE(SUM(Amount), 0) AS TotalPaid FROM Transaction.PaymentsTbl WHERE TransUID = ? AND OrgUID = ? AND IsDeleted = 0 AND IsActive = 1 AND IsCancelled = 0',
            [$transUID, $orgUID]
        );
        if (!$query) return 0.0;
        $row = $query->row();
        return $row ? (float) $row->TotalPaid : 0.0;
    }

}
