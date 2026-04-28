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
     *   OUT = stock decreases: Invoices(103), Purchase Returns(108), Debit Notes(109)
     */
    private static $stockMovementMap = [
        103 => 'OUT',   // Invoices
        105 => 'IN',    // Purchases
        106 => 'IN',    // Sales Returns
        107 => 'IN',    // Credit Notes
        108 => 'OUT',   // Purchase Returns
        109 => 'OUT',   // Debit Notes
    ];

    /**
     * Record stock movements for a saved (non-draft) transaction's items.
     * Inserts rows into StockLedgerTbl and adjusts AvailableQuantity in ProductTbl.
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

        foreach ($items as $item) {
            $productUID  = isset($item['id'])          ? (int)   $item['id']          : 0;
            $qty         = isset($item['quantity'])     ? (float) $item['quantity']    : 0;
            $unitCost    = isset($item['unitPrice'])    ? (float) $item['unitPrice']   : 0;
            $productType = isset($item['productType'])  ?         $item['productType'] : 'Product';

            if ($productUID <= 0 || $qty <= 0)               continue; // invalid row
            if (strtolower($productType) === 'service')       continue; // services have no stock

            // For composite products expand to BOM components; reduce components, not the combo itself
            $this->WriteDB->db_debug = FALSE;
            $this->WriteDB->select('IsComposite');
            $this->WriteDB->from('Products.ProductTbl');
            $this->WriteDB->where(['ProductUID' => $productUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]);
            $prodRow = $this->WriteDB->get()->row();
            $isComposite = $prodRow && (int)$prodRow->IsComposite === 1;

            if ($isComposite) {
                $this->WriteDB->select('ChildProductUID, Quantity');
                $this->WriteDB->from('Products.ProductBOMTbl');
                $this->WriteDB->where(['ParentProductUID' => $productUID, 'IsDeleted' => 0, 'IsActive' => 1]);
                $bomRows = $this->WriteDB->get()->result();

                foreach ($bomRows as $bom) {
                    $componentUID = (int)$bom->ChildProductUID;
                    $componentQty = round((float)$bom->Quantity * $qty, 5);
                    $this->_applyStockMovement($transUID, $moduleUID, $orgUID, $userUID, $componentUID, $componentQty, $unitCost, $movementType);
                }
            } else {
                $this->_applyStockMovement($transUID, $moduleUID, $orgUID, $userUID, $productUID, $qty, $unitCost, $movementType);
            }
        }

    }

    private function _applyStockMovement($transUID, $moduleUID, $orgUID, $userUID, $productUID, $qty, $unitCost, $movementType) {

        $this->WriteDB->db_debug = FALSE;
        $insOk = $this->WriteDB->insert('Products.StockLedgerTbl', [
            'OrgUID'       => $orgUID,
            'ProductUID'   => $productUID,
            'TransUID'     => $transUID,
            'ModuleUID'    => $moduleUID,
            'MovementType' => $movementType,
            'Quantity'     => $qty,
            'UnitCost'     => $unitCost,
            'IsDeleted'    => 0,
            'CreatedBy'    => $userUID,
            'UpdatedBy'    => $userUID,
        ]);
        if ($insOk === false) {
            $err = $this->WriteDB->error();
            throw new Exception('Stock ledger insert failed (ProductUID=' . $productUID . '): ' . ($err['message'] ?? 'unknown DB error'));
        }

        // Update AvailableQuantity — allow negative (oversold) values.
        // CAST to SIGNED prevents UNSIGNED underflow wrapping to a huge positive number.
        if ($movementType === 'IN') {
            $this->WriteDB->set('AvailableQuantity', 'CAST(AvailableQuantity AS SIGNED) + ' . $qty, false);
        } else {
            $this->WriteDB->set('AvailableQuantity', 'CAST(AvailableQuantity AS SIGNED) - ' . $qty, false);
        }
        $this->WriteDB->where(['ProductUID' => $productUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]);
        $updOk = $this->WriteDB->update('Products.ProductTbl');
        if ($updOk === false) {
            $err = $this->WriteDB->error();
            throw new Exception('Stock quantity update failed (ProductUID=' . $productUID . '): ' . ($err['message'] ?? 'unknown DB error'));
        }

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
                $qtyExpr = 'CAST(AvailableQuantity AS SIGNED) - ' . (float)$row->Quantity;
            } else {
                $qtyExpr = 'AvailableQuantity + ' . (float)$row->Quantity;
            }

            $ok = $this->WriteDB->query(
                "UPDATE Products.ProductTbl SET AvailableQuantity = {$qtyExpr} WHERE ProductUID = ? AND OrgUID = ? AND IsDeleted = 0",
                [(int)$row->ProductUID, (int)$orgUID]
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