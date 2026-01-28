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

}