<?php defined('BASEPATH') or exit('No direct script access allowed');

class Dbwrite_model extends CI_Model {

    private $EndReturnData;
    private $WriteDB;

    function __construct() {

        parent::__construct();
        $this->EndReturnData = new stdclass();
        
    }

    public function insertData($Database, $Table, $Data) {

        $this->EndReturnData = new stdclass();

        try {

            $this->WriteDB = $this->load->database($Database, TRUE);
            $this->WriteDB->db_debug = FALSE;

            $res = $this->WriteDB->insert($Table, $Data);

            if (!$res) {

                $this->EndReturnData->Error = TRUE;
                $this->EndReturnData->Table = $Table;
                $this->EndReturnData->Data = $Data;
                $this->EndReturnData->Message = $this->WriteDB->error()['message'];

            } else {

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->ID = $this->WriteDB->insert_id();
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

    public function updateData($Database, $Table, $Data, $Condition, $whereInCondition = array()) {

        $this->EndReturnData = new stdclass();

        try {

            $this->WriteDB = $this->load->database($Database, TRUE);
            $this->WriteDB->db_debug = FALSE;

            if (!empty($whereInCondition)) {
                foreach ($whereInCondition as $wkey => $wval) {
                    $this->WriteDB->where_in($wkey, $wval);
                }
            }
            $res = $this->WriteDB->update($Table, $Data, $Condition);

            if (!$res) {

                $this->EndReturnData->Error = TRUE;
                $this->EndReturnData->Table = $Table;
                $this->EndReturnData->Data = $Data;
                $this->EndReturnData->Condition = $Condition;
                if (!empty($whereInCondition)) {
                    $this->EndReturnData->WhereInCondition = $whereInCondition;
                }
                $this->EndReturnData->Message = $this->WriteDB->error()['message'];

            } else {

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Table = $Table;
                $this->EndReturnData->Data = $Data;
                $this->EndReturnData->Condition = $Condition;
                if (!empty($whereInCondition)) {
                    $this->EndReturnData->WhereInCondition = $whereInCondition;
                }
                $this->EndReturnData->Message = 'Success';
                
            }

        } catch (Exception $e) {

            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Table = $Table;
            $this->EndReturnData->Data = $Data;
            $this->EndReturnData->Condition = $Condition;
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