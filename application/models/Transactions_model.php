<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Transactions_model extends CI_Model {
    
    private $EndReturnData;
    private $ReadDb;

	function __construct() {
        parent::__construct();

		$this->ReadDb = $this->load->database('ReadDB', TRUE);

    }

    public function getTransactionsPrefixDetails($FilterArray) {

        $this->EndReturnData = new stdClass();
        try {

            $this->ReadDb->select('Prefix.PrefixUID as PrefixUID, Prefix.ModuleUID as ModuleUID, Prefix.Name as Name');
            $this->ReadDb->from('Transaction.TransactionPrefixTbl as Prefix');
            if (sizeof($FilterArray) > 0) {
                $this->ReadDb->where($FilterArray);
            }
            $this->ReadDb->where(['Prefix.IsDeleted' => 0, 'Prefix.IsActive' => 1]);
            $query = $this->ReadDb->get();
            $error = $this->ReadDb->error();
            if ($error['code']) {
                throw new Exception($error['message']);
            } else {
                $this->EndReturnData->Data = $query->result();
            }

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Success';
            

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        return $this->EndReturnData;

    }

    public function getCustomersDetails(string $Term = '', $WhereCondition = []) {
        
        $this->EndReturnData = new StdClass();
        try {

            $this->ReadDb->db_debug = FALSE;
            $select_ary = array(
                'Customers.CustomerUID AS CustomerUID',
                'Customers.Name AS Name',
                'Customers.Area AS Area',
                'Customers.MobileNumber AS MobileNumber',
                'MAX(COALESCE(Ship.CustAddressUID, Bill.CustAddressUID)) AS AddrUID',
                'MAX(COALESCE(Ship.Line1, Bill.Line1)) AS Line1',
                'MAX(COALESCE(Ship.Line2, Bill.Line2)) AS Line2',
                'MAX(COALESCE(Ship.Pincode, Bill.Pincode)) AS Pincode',
                'MAX(COALESCE(Ship.CityText, Bill.CityText)) AS CityText',
                'MAX(COALESCE(Ship.StateText, Bill.StateText)) AS StateText'
            );
            $where_ary = array(
                'Customers.IsDeleted' => 0,
                'Customers.IsActive' => 1,
            );
            $this->ReadDb->select($select_ary);
            $this->ReadDb->from('Customers.CustomerTbl as Customers');
            $this->ReadDb->join('Customers.CustAddressTbl as Bill', 'Bill.CustomerUID = Customers.CustomerUID AND Bill.IsDeleted = 0 AND Bill.IsActive = 1', 'LEFT');
            $this->ReadDb->join('Customers.CustAddressTbl as Ship', 'Ship.CustomerUID = Customers.CustomerUID AND Ship.IsDeleted = 0 AND Ship.IsActive = 1', 'LEFT');
            if(!empty($Term)) {
                $this->ReadDb->group_start();
                $this->ReadDb->or_like('Customers.Name', $Term, 'both');
                $this->ReadDb->or_like('Customers.Area', $Term, 'both');
                $this->ReadDb->or_like('Customers.MobileNumber', $Term, 'both');
                $this->ReadDb->or_like('Customers.GSTIN', $Term, 'both');
                $this->ReadDb->or_like('Customers.CompanyName', $Term, 'both');
                $this->ReadDb->or_like('Customers.ContactPerson', $Term, 'both');
                $this->ReadDb->group_end();
            }
            $this->ReadDb->where($where_ary);
            if(sizeof($WhereCondition) > 0) {
                $this->ReadDb->where($WhereCondition);
            }
            $this->ReadDb->group_by('Customers.CustomerUID');
            $this->ReadDb->limit(10);

            $query = $this->ReadDb->get();
            $error = $this->ReadDb->error();
            if ($error['code']) {
                throw new Exception($error['message']);
            } else {
                $this->EndReturnData->Data = $query->result();
            }
            return $this->EndReturnData->Data;

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);
        }

    }

}