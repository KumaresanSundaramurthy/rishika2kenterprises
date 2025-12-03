<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Customers_model extends CI_Model {
    
    private $EndReturnData;
    private $CustomerDb;

	function __construct() {
        parent::__construct();
        
        $this->CustomerDb = $this->load->database('Customers', TRUE);

    }

    public function getCustomersList($limit, $offset, $Filter, $Flag = 0) {

        $this->EndReturnData = new StdClass();
        try {

            $this->CustomerDb->db_debug = FALSE;
            if($Flag == 0) {
                $select_ary = array(
                    'Customers.CustomerUID AS CustomerUID',
                    'Customers.OrgUID AS OrgUID',
                    'Customers.Name AS Name',
                    'Customers.Area AS Area',
                    'Customers.CountryISO2 as CountryISO2',
                    'Customers.CountryCode as CountryCode',
                    'Customers.MobileNumber as MobileNumber',
                    'Customers.EmailAddress as EmailAddress',
                    'Customers.GSTIN as GSTIN',
                    'Customers.CompanyName as CompanyName',
                    'Customers.DebitCreditType as DebitCreditType',
                    'Customers.DebitCreditAmount as DebitCreditAmount',
                    'Customers.Image as Image',
                    'Customers.PANNumber as PANNumber',
                    'Customers.DiscountPercent as DiscountPercent',
                    'Customers.CreditLimit as CreditLimit',
                    'Customers.Notes as Notes',
                    'Customers.Tags as Tags',
                    'Customers.CCEmails as CCEmails',
                    'Customers.CreatedOn as CreatedOn',
                    'Customers.UpdatedOn as UpdatedOn',
                );
            } else {
                $select_ary = array(
                    'Customers.CustomerUID AS CustomerUID',
                );
            }
            $WhereCondition = array(
                'Customers.IsDeleted' => 0,
                'Customers.IsActive' => 1,
            );
            $this->CustomerDb->select($select_ary);
            $this->CustomerDb->from('Customers.CustomerTbl as Customers');
            $this->CustomerDb->where($WhereCondition);
            if (!empty($Filter)) {
                if (array_key_exists('Name', $Filter)) {
                    $this->CustomerDb->like("Customers.Name", $Filter['Name'], 'Both');
                }
            }
            $this->CustomerDb->group_by('Customers.CustomerUID');
            if($Flag == 0) {
                $this->CustomerDb->order_by('Customers.CustomerUID', 'DESC');
                $this->CustomerDb->limit($limit, $offset);
            }
            
            $query = $this->CustomerDb->get();
            $error = $this->CustomerDb->error();
            if ($error['code']) {
                throw new Exception($error['message']);
            } else {
                if($Flag == 0) {
                    $this->EndReturnData->Data = $query->result();
                } else {
                    $this->EndReturnData->Data = $query->num_rows();
                }
            }
            return $this->EndReturnData->Data;

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);
        }

    }

    public function getCustomers($FilterArray) {

        $this->EndReturnData = new StdClass();
        try {

            $this->CustomerDb->db_debug = FALSE;

            $select_ary = array(
                'Customers.CustomerUID AS CustomerUID',
                'Customers.OrgUID AS OrgUID',
                'Customers.Name AS Name',
                'Customers.Area AS Area',
                'Customers.CountryISO2 as CountryISO2',
                'Customers.CountryCode as CountryCode',
                'Customers.MobileNumber as MobileNumber',
                'Customers.EmailAddress as EmailAddress',
                'Customers.GSTIN as GSTIN',
                'Customers.CompanyName as CompanyName',
                'Customers.DebitCreditType as DebitCreditType',
                'Customers.DebitCreditAmount as DebitCreditAmount',
                'Customers.Image as Image',
                'Customers.PANNumber as PANNumber',
                'Customers.DiscountPercent as DiscountPercent',
                'Customers.CreditLimit as CreditLimit',
                'Customers.Notes as Notes',
                'Customers.Tags as Tags',
                'Customers.CCEmails as CCEmails',
                'Customers.CreatedOn as CreatedOn',
                'Customers.UpdatedOn as UpdatedOn',
            );
            $WhereCondition = array(
                'Customers.IsDeleted' => 0,
                'Customers.IsActive' => 1,
            );

            $this->CustomerDb->select($select_ary);
            $this->CustomerDb->from('Customers.CustomerTbl as Customers');
            $this->CustomerDb->where($WhereCondition);
            if(sizeof($FilterArray) > 0) {
                $this->CustomerDb->where($FilterArray);
            }
            $this->CustomerDb->group_by('Customers.CustomerUID');
            $query = $this->CustomerDb->get();
            $error = $this->CustomerDb->error();
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

    public function getCustomerAddress($FilterArray) {

        $this->EndReturnData = new StdClass();
        try {

            $this->CustomerDb->db_debug = FALSE;

            $select_ary = array(
                'CustAddress.CustAddressUID AS CustAddressUID',
                'CustAddress.OrgUID AS OrgUID',
                'CustAddress.CustomerUID AS CustomerUID',
                'CustAddress.AddressType as AddressType',
                'CustAddress.Line1 as Line1',
                'CustAddress.Line2 as Line2',
                'CustAddress.Pincode as Pincode',
                'CustAddress.City as City',
                'CustAddress.CityText as CityText',
                'CustAddress.State as State',
                'CustAddress.StateText as StateText',
            );
            $WhereCondition = array(
                'CustAddress.IsDeleted' => 0,
                'CustAddress.IsActive' => 1,
            );

            $this->CustomerDb->select($select_ary);
            $this->CustomerDb->from('Customers.CustAddressTbl as CustAddress');
            $this->CustomerDb->where($WhereCondition);
            if(sizeof($FilterArray) > 0) {
                $this->CustomerDb->where($FilterArray);
            }
            $this->CustomerDb->group_by('CustAddress.CustAddressUID');
            $query = $this->CustomerDb->get();
            $error = $this->CustomerDb->error();
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

    public function getCustomersDetails(string $Term, $WhereCondition = []) {
        
        $this->EndReturnData = new StdClass();
        try {

            $this->CustomerDb->db_debug = FALSE;
            $select_ary = array(
                'Customers.CustomerUID AS CustomerUID',
                'Customers.Name AS Name',
                'Customers.Area AS Area',
            );
            $where_ary = array(
                'Customers.IsDeleted' => 0,
                'Customers.IsActive' => 1,
            );
            $this->CustomerDb->select($select_ary);
            $this->CustomerDb->from('Customers.CustomerTbl as Customers');
            $this->CustomerDb->group_start();
            $this->CustomerDb->or_like('Customers.Name', $Term, 'both');
            $this->CustomerDb->or_like('Customers.Area', $Term, 'both');
            $this->CustomerDb->group_end();
            $this->CustomerDb->where($where_ary);
            if(sizeof($WhereCondition) > 0) {
                $this->CustomerDb->where($WhereCondition);
            }
            $this->CustomerDb->limit(10);

            $query = $this->CustomerDb->get();
            $error = $this->CustomerDb->error();
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