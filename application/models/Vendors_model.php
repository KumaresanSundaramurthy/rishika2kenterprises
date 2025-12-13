<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Vendors_model extends CI_Model {
    
    private $EndReturnData;
    private $CustomerDb;
    private $VendorDb;

	function __construct() {
        parent::__construct();

        $this->CustomerDb = $this->load->database('Customers', TRUE);
        $this->VendorDb = $this->load->database('Vendors', TRUE);

    }

    public function getVendorsList($limit, $offset, $Filter, $Flag = 0) {

        $this->EndReturnData = new StdClass();
        try {

            $this->VendorDb->db_debug = FALSE;
            if($Flag == 0) {
                $select_ary = array(
                    'Vendors.VendorUID AS VendorUID',
                    'Vendors.OrgUID AS OrgUID',
                    'Vendors.Name AS Name',
                    'Vendors.Area AS Area',
                    'Vendors.CountryISO2 as CountryISO2',
                    'Vendors.CountryCode as CountryCode',
                    'Vendors.MobileNumber as MobileNumber',
                    'Vendors.EmailAddress as EmailAddress',
                    'Vendors.GSTIN as GSTIN',
                    'Vendors.CompanyName as CompanyName',
                    'Vendors.DebitCreditType as DebitCreditType',
                    'Vendors.DebitCreditAmount as DebitCreditAmount',
                    'Vendors.Image as Image',
                    'Vendors.PANNumber as PANNumber',
                    'Vendors.DebitLimit as DebitLimit',
                    'Vendors.Notes as Notes',
                    'Vendors.Tags as Tags',
                    'Vendors.CCEmails as CCEmails',
                    'Vendors.CreatedOn as CreatedOn',
                    'Vendors.UpdatedOn as UpdatedOn',
                );
            } else {
                $select_ary = array(
                    'Vendors.VendorUID AS VendorUID',
                );
            }
            $WhereCondition = array(
                'Vendors.IsDeleted' => 0,
                'Vendors.IsActive' => 1,
            );
            $this->VendorDb->select($select_ary);
            $this->VendorDb->from('Vendors.VendorTbl as Vendors');
            $this->VendorDb->where($WhereCondition);
            if (!empty($Filter)) {
                if (array_key_exists('Name', $Filter)) {
                    $this->VendorDb->like("Vendors.Name", $Filter['Name'], 'Both');
                }
            }
            $this->VendorDb->group_by('Vendors.VendorUID');
            if($Flag == 0) {
                $this->VendorDb->order_by('Vendors.VendorUID', 'DESC');
                $this->VendorDb->limit($limit, $offset);
            }
            
            $query = $this->VendorDb->get();
            $error = $this->VendorDb->error();
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

    public function getVendors($FilterArray) {

        $this->EndReturnData = new StdClass();
        try {

            $this->VendorDb->db_debug = FALSE;

            $select_ary = array(
                'Vendors.VendorUID AS VendorUID',
                'Vendors.OrgUID AS OrgUID',
                'Vendors.Name AS Name',
                'Vendors.Area AS Area',
                'Vendors.CountryISO2 as CountryISO2',
                'Vendors.CountryCode as CountryCode',
                'Vendors.MobileNumber as MobileNumber',
                'Vendors.EmailAddress as EmailAddress',
                'Vendors.CustomerUID as CustomerUID',
                'Vendors.GSTIN as GSTIN',
                'Vendors.CompanyName as CompanyName',
                'Vendors.DebitCreditType as DebitCreditType',
                'Vendors.DebitCreditAmount as DebitCreditAmount',
                'Vendors.Image as Image',
                'Vendors.PANNumber as PANNumber',
                'Vendors.ContactPerson as ContactPerson',
                'Vendors.DateOfBirth as DateOfBirth',
                'Vendors.Notes as Notes',
                'Customers.Name as CustomerName',
                'Vendors.CreatedOn as CreatedOn',
                'Vendors.UpdatedOn as UpdatedOn',
            );
            $WhereCondition = array(
                'Vendors.IsDeleted' => 0,
                'Vendors.IsActive' => 1,
            );

            $this->VendorDb->select($select_ary);
            $this->VendorDb->from('Vendors.VendorTbl as Vendors');
            $this->VendorDb->join($this->CustomerDb->database.'.CustomerTbl as Customers', 'Customers.CustomerUID = Vendors.CustomerUID', 'left');
            $this->VendorDb->where($WhereCondition);
            if(sizeof($FilterArray) > 0) {
                $this->VendorDb->where($FilterArray);
            }
            $this->VendorDb->group_by('Vendors.VendorUID');
            $query = $this->VendorDb->get();
            $error = $this->VendorDb->error();
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

    public function getVendorBankInfo($FilterArray) {

        $this->EndReturnData = new StdClass();
        try {

            $this->VendorDb->db_debug = FALSE;

            $select_ary = array(
                'VendBankDetails.VendBankDetUID AS VendBankDetUID',
                'VendBankDetails.VendorUID AS VendorUID',
                'VendBankDetails.Type as Type',
                'VendBankDetails.BankAccountNumber as BankAccountNumber',
                'VendBankDetails.BankIFSC_Code as BankIFSC_Code',
                'VendBankDetails.BankBranchName as BankBranchName',
                'VendBankDetails.BankAccountHolderName as BankAccountHolderName',
                'VendBankDetails.UPI_Id as UPI_Id',
            );
            $WhereCondition = array(
                'VendBankDetails.IsDeleted' => 0,
                'VendBankDetails.IsActive' => 1,
            );

            $this->VendorDb->select($select_ary);
            $this->VendorDb->from('Vendors.VendBankDetailsTbl as VendBankDetails');
            $this->VendorDb->where($WhereCondition);
            if(sizeof($FilterArray) > 0) {
                $this->VendorDb->where($FilterArray);
            }
            $query = $this->VendorDb->get();
            $error = $this->VendorDb->error();
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

    public function getVendorAddress($FilterArray) {

        $this->EndReturnData = new StdClass();
        try {

            $this->VendorDb->db_debug = FALSE;

            $select_ary = array(
                'VendAddress.VendAddressUID AS VendAddressUID',
                'VendAddress.OrgUID AS OrgUID',
                'VendAddress.VendorUID AS VendorUID',
                'VendAddress.AddressType AS AddressType',
                'VendAddress.Line1 as Line1',
                'VendAddress.Line2 as Line2',
                'VendAddress.Pincode as Pincode',
                'VendAddress.City as City',
                'VendAddress.CityText as CityText',
                'VendAddress.State as State',
                'VendAddress.StateText as StateText',
            );
            $WhereCondition = array(
                'VendAddress.IsDeleted' => 0,
                'VendAddress.IsActive' => 1,
            );

            $this->VendorDb->select($select_ary);
            $this->VendorDb->from('Vendors.VendAddressTbl as VendAddress');
            $this->VendorDb->where($WhereCondition);
            if(sizeof($FilterArray) > 0) {
                $this->VendorDb->where($FilterArray);
            }
            $this->VendorDb->group_by('VendAddress.VendAddressUID');
            $query = $this->VendorDb->get();
            $error = $this->VendorDb->error();
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