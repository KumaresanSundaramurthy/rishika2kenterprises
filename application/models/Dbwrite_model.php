<?php defined('BASEPATH') or exit('No direct script access allowed');

class Dbwrite_model extends CI_Model {

    private $EndReturnData;
    private $WriteDB;
    private $transaction_id;

    function __construct() {
        parent::__construct();

        $this->EndReturnData = new stdclass();
        $this->WriteDB = $this->load->database('WriteDB', TRUE);
        
    }

    public function startTransaction() {
        $this->WriteDB->trans_start();
        $this->transaction_id = uniqid('TXN_', true);
        return $this->transaction_id;
    }

    public function commitTransaction() {
        try {
            $this->WriteDB->trans_complete();
            
            if (!$this->WriteDB->trans_status()) {
                $error = $this->WriteDB->error();
                
                // Build detailed error message
                $errorMsg = 'Transaction commit failed. ';
                
                if (!empty($error['message'])) {
                    $errorMsg .= 'Database Error: ' . $error['message'];
                }
                
                if (!empty($error['code'])) {
                    $errorMsg .= ' (Code: ' . $error['code'] . ')';
                }

                if (method_exists($this->WriteDB, 'last_query')) {
                    $lastQuery = $this->WriteDB->last_query();
                    $errorMsg .= ' Last Query: ' . substr($lastQuery, 0, 200) . '...';
                }
                throw new Exception($errorMsg);

            }
            
            return true;
            
        } catch (Exception $e) {
            throw new Exception('Transaction failed: ' . $e->getMessage());
        }
    }

    public function rollbackTransaction() {
        $this->WriteDB->trans_rollback();
    }

    public function insertData($Database, $Table, $Data) {

        $this->EndReturnData = new stdclass();
        $this->EndReturnData->Error = FALSE;
        $this->EndReturnData->Message = 'Success';
        try {

            $this->WriteDB->db_debug = FALSE;
            $res = $this->WriteDB->insert($Database.'.'.$Table, $Data);
            if ($res === false) {

                $dbError = $this->WriteDB->error();

                $this->EndReturnData->Error = TRUE;
                $this->EndReturnData->Table = $Table;
                $this->EndReturnData->Data = $Data;
                $this->EndReturnData->Message = $dbError['message'] ?? 'Database error';

            } else {                
                $this->EndReturnData->ID = $this->WriteDB->insert_id();
                $this->EndReturnData->Table = $Table;
                $this->EndReturnData->Data = $Data;
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Table = $Table;
            $this->EndReturnData->Data = $Data;
            $this->EndReturnData->Message = $e->getMessage();
        }

        return $this->EndReturnData;

    }

    /** Batch insert using the existing WriteDB connection (safe inside a transaction). */
    public function insertBatchInTransaction($Database, $Table, array $Data) {

        $this->EndReturnData = new stdclass();
        $this->EndReturnData->Error = FALSE;
        $this->EndReturnData->Message = 'Success';
        try {

            $this->WriteDB->db_debug = FALSE;
            $res = $this->WriteDB->insert_batch($Database . '.' . $Table, $Data);
            if ($res === false) {
                $this->EndReturnData->Error   = TRUE;
                $this->EndReturnData->Message = $this->WriteDB->error()['message'] ?? 'Batch insert failed';
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        return $this->EndReturnData;

    }

    public function insertBatchData($Database, $Table, $Data) {

        $this->EndReturnData = new stdclass();

        try {

            $this->WriteDB = $this->load->database($Database, TRUE);
            $this->WriteDB->db_debug = FALSE;

            $res = $this->WriteDB->insert_batch($Table, $Data);

            if (!$res) {

                $this->EndReturnData->Error = TRUE;
                $this->EndReturnData->Table = $Table;
                $this->EndReturnData->Data = $Data;
                $this->EndReturnData->Message = $this->WriteDB->error()['message'];

            } else {

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Table = $Table;
                $this->EndReturnData->Data = $Data;
                $this->EndReturnData->Message = 'Success';

            }

        } catch (Exception $e) {

            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Table = $Table;
            $this->EndReturnData->Data = $Data;
            $this->EndReturnData->Message = $e->getMessage();
            
        }

        return $this->EndReturnData;

    }

    public function updateData($Database, $Table, $Data, $Condition = [], $whereInCondition = array()) {

        $this->EndReturnData = new stdclass();
        $this->EndReturnData->Error = FALSE;
        $this->EndReturnData->Message = 'Success';
        try {

            $this->WriteDB->db_debug = FALSE;

            if (!empty($whereInCondition)) {
                foreach ($whereInCondition as $wkey => $wval) {
                    $this->WriteDB->where_in($wkey, $wval);
                }
            }
            if (!empty($Condition)) {
                $res = $this->WriteDB->update($Database.'.'.$Table, $Data, $Condition);
            } else {
                $res = $this->WriteDB->update($Database.'.'.$Table, $Data);
            }

            if (!$res) {

                $this->EndReturnData->Error = TRUE;
                $this->EndReturnData->Table = $Table;
                $this->EndReturnData->Data = $Data;
                if (!empty($Condition)) {
                    $this->EndReturnData->Condition = $Condition;
                }
                if (!empty($whereInCondition)) {
                    $this->EndReturnData->WhereInCondition = $whereInCondition;
                }
                $this->EndReturnData->Message = $this->WriteDB->error()['message'];

            } else {

                $this->EndReturnData->Table = $Table;
                $this->EndReturnData->Data = $Data;
                if (!empty($Condition)) {
                    $this->EndReturnData->Condition = $Condition;
                }
                if (!empty($whereInCondition)) {
                    $this->EndReturnData->WhereInCondition = $whereInCondition;
                }
                
            }

        } catch (Exception $e) {

            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Table = $Table;
            $this->EndReturnData->Data = $Data;
            if (!empty($Condition)) {
                $this->EndReturnData->Condition = $Condition;
            }
            if (!empty($whereInCondition)) {
                $this->EndReturnData->WhereInCondition = $whereInCondition;
            }
            $this->EndReturnData->Message = $e->getMessage();

        }

        return $this->EndReturnData;

    }

    public function updateBatchData($Database, $Table, $Data, $Field) {

        $this->EndReturnData = new stdclass();

        try {

            $this->WriteDB = $this->load->database($Database, TRUE);
            $this->WriteDB->db_debug = FALSE;

            $res = $this->WriteDB->update_batch($Table, $Data, $Field);

            if ($res === FALSE) {

                $this->EndReturnData->Error = TRUE;
                $this->EndReturnData->Table = $Table;
                $this->EndReturnData->Data = $Data;
                $this->EndReturnData->Field = $Field;
                $this->EndReturnData->Message = $this->WriteDB->error()['message'];

            } else {

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Table = $Table;
                $this->EndReturnData->Data = $Data;
                $this->EndReturnData->Field = $Field;
                $this->EndReturnData->Message = 'Success';
                
            }

        } catch (Exception $e) {

            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Table = $Table;
            $this->EndReturnData->Data = $Data;
            $this->EndReturnData->Field = $Field;
            $this->EndReturnData->Message = $e->getMessage();

        }

        return $this->EndReturnData;

    }

    /** Hard-delete using the existing WriteDB connection (safe inside a transaction). */
    public function deleteInTransaction($Database, $Table, array $Condition) {

        $this->EndReturnData = new stdclass();
        $this->EndReturnData->Error   = FALSE;
        $this->EndReturnData->Message = 'Success';
        try {

            $this->WriteDB->db_debug = FALSE;
            $res = $this->WriteDB->delete($Database . '.' . $Table, $Condition);
            if ($res === false) {
                $this->EndReturnData->Error   = TRUE;
                $this->EndReturnData->Message = $this->WriteDB->error()['message'] ?? 'Delete failed';
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        return $this->EndReturnData;

    }

    public function deleteData($Database, $Table, $Condition) {

        $this->EndReturnData = new stdclass();

        try {

            $this->WriteDB = $this->load->database($Database, TRUE);
            $this->WriteDB->db_debug = FALSE;

            $res = $this->WriteDB->delete($Table, $Condition);

            if (!$res) {

                $this->EndReturnData->Error = TRUE;
                $this->EndReturnData->Table = $Table;
                $this->EndReturnData->Condition = $Condition;
                $this->EndReturnData->Message = $this->WriteDB->error()['message'];

            } else {

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Table = $Table;
                $this->EndReturnData->Condition = $Condition;
                $this->EndReturnData->Message = 'Success';

            }

        } catch (Exception $e) {

            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Table = $Table;
            $this->EndReturnData->Condition = $Condition;
            $this->EndReturnData->Message = $e->getMessage();

        }

        return $this->EndReturnData;

    }

    // ── Transaction Number Helpers (WriteDB — avoids read-replica lag) ─────────

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

    public function getNextAvailableTransNumber($prefixUID, $orgUID) {
        $this->WriteDB->db_debug = FALSE;
        $this->WriteDB->select_max('TransNumber', 'MaxNumber');
        $this->WriteDB->from('Transaction.TransactionsTbl');
        $this->WriteDB->where(['PrefixUID' => (int) $prefixUID, 'OrgUID' => (int) $orgUID]);
        $result = $this->WriteDB->get()->row();
        $next   = $result ? ((int)($result->MaxNumber ?? 0) + 1) : 1;

        $maxAttempts = 100;
        while ($maxAttempts-- > 0) {
            $this->WriteDB->select('TransUID');
            $this->WriteDB->from('Transaction.TransactionsTbl');
            $this->WriteDB->where(['PrefixUID' => (int)$prefixUID, 'TransNumber' => $next, 'OrgUID' => (int)$orgUID, 'IsDeleted' => 0]);
            $this->WriteDB->limit(1);
            if (!$this->WriteDB->get()->row()) break;
            $next++;
        }
        return $next;
    }

    // ── Stock Movement Methods ─────────────────────────────────────────────────

    /**
     * Stock movement direction by ModuleUID.
     *   IN  = stock increases: Purchases(105), Sales Returns(106), Credit Notes(107)
     *   OUT = stock decreases: Invoices(103), Purchase Returns(108)
     */
    private static $stockMovementMap = [
        103 => 'OUT',   // Invoices
        105 => 'IN',    // Purchases
        106 => 'IN',    // Sales Returns
        107 => 'IN',    // Credit Notes
        108 => 'OUT',   // Purchase Returns
    ];

    /**
     * Record stock movements for a saved (non-draft) transaction's items.
     * Inserts rows into StockLedgerTbl and adjusts AvailableQty in ProductStockTbl.
     * Only 'Product' type items are tracked; 'Service' items are skipped.
     *
     * @param int    $transUID   Transaction UID
     * @param int    $moduleUID  Module UID (determines IN vs OUT)
     * @param int    $orgUID     Organisation UID
     * @param int    $userUID    User performing the action
     * @param array  $items      Items array from form (each has 'id', 'quantity', 'unitPrice', 'productType')
     */
    public function saveStockMovements($transUID, $moduleUID, $orgUID, $userUID, array $items) {

        $movementType = self::$stockMovementMap[$moduleUID] ?? null;
        if (!$movementType) return; // module does not affect stock

        // Bulk-fetch TransProdUID, SellingPrice, TaxAmount, FinancialYear for this transaction
        $this->WriteDB->db_debug = FALSE;
        $tpQuery = $this->WriteDB->query(
            "SELECT TransProdUID, ProductUID, UnitPrice, FinancialYear,
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
                $pid = (int)$tp->ProductUID;
                if (!isset($tpMap[$pid])) {
                    $tpMap[$pid] = [
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

        foreach ($items as $item) {
            $productUID  = isset($item['id'])          ? (int)   $item['id']          : 0;
            $qty         = isset($item['quantity'])     ? (float) $item['quantity']    : 0;
            $unitCost    = isset($item['unitPrice'])    ? (float) $item['unitPrice']   : 0;
            $productType = isset($item['productType'])  ?         $item['productType'] : 'Product';

            if ($productUID <= 0 || $qty <= 0)         continue;
            if (strtolower($productType) === 'service') continue;

            $tpInfo       = $tpMap[$productUID] ?? null;
            $transProdUID = $tpInfo ? $tpInfo['transProdUID'] : null;
            $sellingPrice = $tpInfo ? $tpInfo['sellingPrice'] : null;
            $taxAmount    = $tpInfo ? $tpInfo['taxAmount']    : null;

            // For composite products expand to BOM components; reduce components, not the combo itself
            $this->WriteDB->db_debug = FALSE;
            $this->WriteDB->select('IsComposite');
            $this->WriteDB->from('Products.ProductTbl');
            $this->WriteDB->where(['ProductUID' => $productUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]);
            $prodRow     = $this->WriteDB->get()->row();
            $isComposite = $prodRow && (int)$prodRow->IsComposite === 1;

            if ($isComposite) {
                $this->WriteDB->select('ChildProductUID, Quantity');
                $this->WriteDB->from('Products.ProductBOMTbl');
                $this->WriteDB->where(['ParentProductUID' => $productUID, 'IsDeleted' => 0, 'IsActive' => 1]);
                $bomRows = $this->WriteDB->get()->result();

                if (!empty($bomRows)) {
                    // Batch-fetch full product details for all components in one query
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

                    foreach ($bomRows as $bom) {
                        $componentUID = (int)$bom->ChildProductUID;
                        $componentQty = round((float)$bom->Quantity * $qty, 5);
                        $cd           = $compDetails[$componentUID] ?? null;

                        // Record stock movement for this component
                        $this->_applyStockMovement($transUID, $moduleUID, $orgUID, $userUID, $componentUID, $componentQty, $unitCost, $movementType, $transProdUID, $sellingPrice, $taxAmount);

                        // Insert BOM snapshot — full component details frozen at transaction time
                        if ($transProdUID !== null && $cd !== null) {
                            $sp      = (float)($cd->SellingPrice  ?? 0);
                            $pp      = (float)($cd->PurchasePrice ?? 0);
                            $cgstPct = (float)($cd->CGST          ?? 0);
                            $sgstPct = (float)($cd->SGST          ?? 0);
                            $igstPct = (float)($cd->IGST          ?? 0);
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
                                'SellingPrice'       => $sp,
                                'PurchasePrice'      => $pp,
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
                $this->_applyStockMovement($transUID, $moduleUID, $orgUID, $userUID, $productUID, $qty, $unitCost, $movementType, $transProdUID, $sellingPrice, $taxAmount);
            }
        }

    }

    private function _applyStockMovement($transUID, $moduleUID, $orgUID, $userUID, $productUID, $qty, $unitCost, $movementType, $transProdUID = null, $sellingPrice = null, $taxAmount = null) {

        $this->WriteDB->db_debug = FALSE;

        // Fetch product snapshot at the moment of this movement
        $snap = $this->WriteDB->query(
            "SELECT p.ItemName, p.MRP, p.CGST, p.SGST, p.IGST, p.TaxPercentage,
                    p.CategoryUID, c.Name AS CategoryName,
                    p.PurchasePrice, p.PartNumber, p.Description, p.Image
             FROM Products.ProductTbl p
             LEFT JOIN Products.CategoryTbl c
                    ON c.CategoryUID = p.CategoryUID AND c.IsDeleted = 0
             WHERE p.ProductUID = ? AND p.IsDeleted = 0
             LIMIT 1",
            [(int)$productUID]
        )->row();

        $insOk = $this->WriteDB->insert('Products.StockLedgerTbl', [
            'OrgUID'             => $orgUID,
            'ProductUID'         => $productUID,
            'TransUID'           => $transUID,
            'TransProdUID'       => $transProdUID,
            'ModuleUID'          => $moduleUID,
            'MovementType'       => $movementType,
            'Quantity'           => $qty,
            'UnitCost'           => $unitCost,
            'SellingPrice'       => $sellingPrice,
            'TaxAmount'          => $taxAmount,
            'Remarks'            => null,
            // Product snapshot fields
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
            'SnapImage'          => $snap->Image         ?? null,
            'IsDeleted'          => 0,
            'CreatedBy'          => $userUID,
            'UpdatedBy'          => $userUID,
        ]);
        if ($insOk === false) {
            $err = $this->WriteDB->error();
            throw new Exception('Stock ledger insert failed (ProductUID=' . $productUID . '): ' . ($err['message'] ?? 'unknown DB error'));
        }

        // Update ProductStockTbl — allow negative (oversold) values.
        // CAST to SIGNED prevents UNSIGNED underflow wrapping to a huge positive number.
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

    }

    /**
     * Write one manual stock adjustment row to StockLedgerTbl and update AvailableQty in ProductStockTbl.
     * Called after the StockAdjustmentTbl row is already inserted (AdjUID is known).
     *
     * @param int    $adjUID      AdjUID from StockAdjustmentTbl
     * @param int    $orgUID
     * @param int    $userUID
     * @param int    $productUID
     * @param float  $qty
     * @param float  $unitCost    Price entered in the form (used as UnitCost in ledger)
     * @param string $adjType     'IN' or 'OUT'
     */
    /**
     * Insert the initial ProductStockTbl row when a new product is created.
     */
    public function initProductStock($productUID, $orgUID) {
        $this->WriteDB->db_debug = FALSE;
        $this->WriteDB->insert('Products.ProductStockTbl', [
            'ProductUID'   => (int) $productUID,
            'OrgUID'       => (int) $orgUID,
            'AvailableQty' => 0,
        ]);
    }

    public function applyManualStockAdjustment($adjUID, $orgUID, $userUID, $productUID, $qty, $unitCost, $adjType) {

        $movementType = ($adjType === 'IN') ? 'IN' : 'OUT';
        $this->_applyStockMovement((int)$adjUID, 118, (int)$orgUID, (int)$userUID, (int)$productUID, (float)$qty, (float)$unitCost, $movementType);

    }

    /**
     * Reverse all stock movements for a transaction (used on edit of non-draft or on delete).
     * Soft-deletes the ledger rows and adds back / subtracts the quantities.
     * Safe to call on draft transactions — finds no ledger rows and does nothing.
     *
     * @param int $transUID  Transaction UID whose stock movements to reverse
     * @param int $orgUID    Organisation UID
     * @param int $userUID   User performing the action
     */
    public function reverseStockMovements($transUID, $orgUID, $userUID) {

        $this->WriteDB->db_debug = FALSE;
        $this->WriteDB->select('LedgerUID, ProductUID, MovementType, Quantity');
        $this->WriteDB->from('Products.StockLedgerTbl');
        $this->WriteDB->where(['TransUID' => $transUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]);
        $query      = $this->WriteDB->get();
        if (!$query) {
            $err = $this->WriteDB->error();
            throw new Exception('Stock reversal read failed: ' . ($err['message'] ?? 'unknown error'));
        }
        $ledgerRows = $query->result();

        foreach ($ledgerRows as $row) {
            if ($row->MovementType === 'IN') {
                $qtyExpr = 'CAST(AvailableQty AS SIGNED) - ' . (float)$row->Quantity;
            } else {
                $qtyExpr = 'AvailableQty + ' . (float)$row->Quantity;
            }

            $ok = $this->WriteDB->query(
                "UPDATE Products.ProductStockTbl SET AvailableQty = {$qtyExpr} WHERE ProductUID = ?",
                [(int)$row->ProductUID]
            );
            if ($ok === false) {
                $err = $this->WriteDB->error();
                throw new Exception('Stock quantity reversal failed (ProductUID=' . $row->ProductUID . '): ' . ($err['message'] ?? 'unknown error'));
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

        // Soft-delete BOM snapshots for this transaction (composite items only; no-op otherwise)
        $this->WriteDB->query(
            "UPDATE Transaction.TransProductBOMTbl
                SET IsDeleted = 1, UpdatedBy = ?
              WHERE TransUID = ? AND OrgUID = ? AND IsDeleted = 0",
            [(int)$userUID, (int)$transUID, (int)$orgUID]
        );

    }

    // Soft-deletes a transaction row using a raw parameterized query.
    // Required because CI3's Active Record query builder has escaping issues
    // with bit(1) fields and partitioned tables on TransactionsTbl.
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

    // Soft-deletes all line items for a transaction using a raw parameterized query.
    // CI3 Active Record silently fails on TransProductsTbl due to bit(1) IsDeleted in WHERE.
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
     * Soft-delete specific product rows for a transaction by ProductUID.
     * Only deletes rows that are currently active (IsDeleted = 0).
     */
    public function softDeleteTransactionItemsByProductUIDs($transUID, array $productUIDs, $userUID) {
        if (empty($productUIDs)) return;
        $this->WriteDB->db_debug = FALSE;
        $placeholders = implode(',', array_fill(0, count($productUIDs), '?'));
        $params = array_merge([(int)$userUID, (int)$transUID], array_map('intval', $productUIDs));
        $ok = $this->WriteDB->query(
            "UPDATE Transaction.TransProductsTbl
                SET IsDeleted = 1, IsActive = 0, UpdatedBy = ?, UpdatedOn = NOW()
              WHERE TransUID = ? AND ProductUID IN ({$placeholders}) AND IsDeleted = 0",
            $params
        );
        if ($ok === false) {
            $err = $this->WriteDB->error();
            throw new Exception('Failed to soft-delete removed items: ' . ($err['message'] ?? 'unknown error'));
        }
    }

    /**
     * Update an existing active TransProductsTbl row by TransUID + ProductUID.
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

    // ── Conversion Tracking ───────────────────────────────────────────────────

    /**
     * Record a document conversion in TransConversionTbl.
     * Module UIDs: 101=Quotation, 102=SalesOrder, 103=Invoice.
     * ConversionType: QuotToOrder | QuotToInvoice | OrderToInvoice
     * Skips silently if the exact same source→target pair is already recorded.
     */
    /**
     * Update DocStatus on a transaction row using a raw parameterized query.
     * Bypasses CI3's query builder to avoid bit(1) / partition / escaping issues
     * that cause the Active Record WHERE builder to silently match 0 rows.
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

    public function updateTransIsFullyPaid($transUID, $isFullyPaid, $paidAmount, $balanceAmount, $userUID) {
        $this->WriteDB->db_debug = FALSE;
        return $this->WriteDB->query(
            "UPDATE Transaction.TransactionsTbl
                SET IsFullyPaid = ?, PaidAmount = ?, BalanceAmount = ?, UpdatedBy = ?
              WHERE TransUID = ?",
            [(int) $isFullyPaid, (float) $paidAmount, (float) $balanceAmount, (int) $userUID, (int) $transUID]
        );
    }

    public function saveFormData($orgUID, $transUID, $moduleUID, $action, $formDataJson, $userUID) {
        $this->WriteDB->db_debug = FALSE;
        $this->WriteDB->insert('Transaction.TransFormDataTbl', [
            'OrgUID'       => (int) $orgUID,
            'TransUID'     => (int) $transUID,
            'ModuleUID'    => (int) $moduleUID,
            'Action'       => substr($action, 0, 30),
            'FormDataJson' => $formDataJson,
            'CreatedBy'    => (int) $userUID,
        ]);
    }

    public function insertAuditLog(array $data) {
        $this->WriteDB->db_debug = FALSE;
        $this->WriteDB->insert('Security.UserAuditLogTbl', $data);
    }

    public function insertConversionRecord($orgUID, $sourceUID, $sourceModuleUID, $targetUID, $targetModuleUID, $conversionType, $userUID) {

        $this->WriteDB->db_debug = FALSE;

        // Duplicate guard — uses WriteDB to avoid read-replica lag
        $this->WriteDB->select('ConversionUID');
        $this->WriteDB->from('Transaction.TransConversionTbl');
        $this->WriteDB->where(['SourceTransUID' => (int)$sourceUID, 'TargetTransUID' => (int)$targetUID]);
        $this->WriteDB->limit(1);
        if ($this->WriteDB->get()->row()) return; // already recorded

        $this->WriteDB->insert('Transaction.TransConversionTbl', [
            'OrgUID'          => (int) $orgUID,
            'SourceTransUID'  => (int) $sourceUID,
            'SourceModuleUID' => (int) $sourceModuleUID,
            'TargetTransUID'  => (int) $targetUID,
            'TargetModuleUID' => (int) $targetModuleUID,
            'ConversionType'  => $conversionType,
            'ConvertedBy'     => (int) $userUID,
        ]);

    }

}