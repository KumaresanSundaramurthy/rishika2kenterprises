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

            // Insert ledger row
            $this->WriteDB->db_debug = FALSE;
            $this->WriteDB->insert('Products.StockLedgerTbl', [
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

            // Update AvailableQuantity (never go below 0)
            if ($movementType === 'IN') {
                $this->WriteDB->set('AvailableQuantity', 'AvailableQuantity + ' . $qty, false);
            } else {
                $this->WriteDB->set('AvailableQuantity', 'GREATEST(0, AvailableQuantity - ' . $qty . ')', false);
            }
            $this->WriteDB->where(['ProductUID' => $productUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]);
            $this->WriteDB->update('Products.ProductTbl');
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
        $ledgerRows = $query ? $query->result() : [];

        foreach ($ledgerRows as $row) {
            // Reverse quantity: an IN entry was +qty, so reverse is -qty (and vice versa)
            if ($row->MovementType === 'IN') {
                $this->WriteDB->set('AvailableQuantity', 'GREATEST(0, AvailableQuantity - ' . (float)$row->Quantity . ')', false);
            } else {
                $this->WriteDB->set('AvailableQuantity', 'AvailableQuantity + ' . (float)$row->Quantity, false);
            }
            $this->WriteDB->where(['ProductUID' => (int)$row->ProductUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]);
            $this->WriteDB->update('Products.ProductTbl');

            // Soft-delete ledger entry
            $this->WriteDB->update('Products.StockLedgerTbl',
                ['IsDeleted' => 1, 'UpdatedBy' => $userUID],
                ['LedgerUID' => (int)$row->LedgerUID]
            );
        }

    }

}