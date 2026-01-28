<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Customers_model extends CI_Model {
    
    private $ReadDb;

	function __construct() {
        parent::__construct();
        
        $this->ReadDb = $this->load->database('ReadDB', TRUE);

    }

    public function custFilterFormation($moduleInfo, array $filter = []) {

        $result = new stdClass();
        $result->SearchDirectQuery = [];
        $result->SearchFilter = [];
        $result->sortOperation = [];

        $alias = $moduleInfo->TableAliasName;
        
        if (!empty($filter['SearchAllData'])) {

            $searchValue = $filter['SearchAllData'];

            $result->SearchDirectQuery = [
                "{$alias}.Name LIKE"          => "%{$searchValue}%",
                "{$alias}.Area LIKE"          => "%{$searchValue}%",
                "{$alias}.MobileNumber LIKE"  => "%{$searchValue}%",
                "{$alias}.ContactPerson LIKE" => "%{$searchValue}%"
            ];
        }
        
        if (isset($filter['NameSorting'])) {
            $result->sortOperation["{$alias}.Name"] = ((int)$filter['NameSorting'] === 1) ? 'ASC' : 'DESC';
        }

        return $result;

    }


    public function getCustomers($FilterArray) {

        try {

            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
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
                'Customers.ContactPerson as ContactPerson',
                'Customers.DateOfBirth as DateOfBirth',
                'Customers.DiscountPercent as DiscountPercent',
                'Customers.CreditPeriod as CreditPeriod',
                'Customers.CreditLimit as CreditLimit',
                'Customers.Notes as Notes',
                'Customers.Tags as Tags',
                'Customers.CCEmails as CCEmails',
                'Customers.CreatedOn as CreatedOn',
                'Customers.UpdatedOn as UpdatedOn',
            ]);
            $this->ReadDb->from('Customers.CustomerTbl as Customers');
            $this->ReadDb->where(['Customers.IsDeleted' => 0, 'Customers.IsActive' => 1]);
            if(sizeof($FilterArray) > 0) {
                $this->ReadDb->where($FilterArray);
            }
            $query = $this->ReadDb->get();
            if (!$query) {
                $error = $this->ReadDb->error();
                throw new Exception($error['message'] ?? 'Database error occurred');
            }

            return $query->result();

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }

    public function getCustomerAddress($FilterArray) {

        try {

            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
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
            ]);
            $this->ReadDb->from('Customers.CustAddressTbl as CustAddress');
            $this->ReadDb->where(['CustAddress.IsDeleted' => 0, 'CustAddress.IsActive' => 1]);
            if(sizeof($FilterArray) > 0) {
                $this->ReadDb->where($FilterArray);
            }
            $query = $this->ReadDb->get();
            if (!$query) {
                $error = $this->ReadDb->error();
                throw new Exception($error['message'] ?? 'Database error occurred');
            }
            
            return $query->result();

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }

    public function getCustomerBankInfo($FilterArray) {

        try {

            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'CustBankDetails.CustBankDetUID AS CustBankDetUID',
                'CustBankDetails.CustomerUID AS CustomerUID',
                'CustBankDetails.Type as Type',
                'CustBankDetails.BankAccountNumber as BankAccountNumber',
                'CustBankDetails.BankIFSC_Code as BankIFSC_Code',
                'CustBankDetails.BankBranchName as BankBranchName',
                'CustBankDetails.BankAccountHolderName as BankAccountHolderName',
                'CustBankDetails.UPI_Id as UPI_Id',
            ]);
            $this->ReadDb->from('Customers.CustBankDetailsTbl as CustBankDetails');
            $this->ReadDb->where(['CustBankDetails.IsDeleted' => 0, 'CustBankDetails.IsActive' => 1]);
            if(sizeof($FilterArray) > 0) {
                $this->ReadDb->where($FilterArray);
            }
            $query = $this->ReadDb->get();
            if (!$query) {
                $error = $this->ReadDb->error();
                throw new Exception($error['message'] ?? 'Database error occurred');
            }
            
            return $query->result();

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }

    public function getCustomersDetails(string $Term, $WhereCondition = []) {
        
        try {

            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'Customers.CustomerUID AS CustomerUID',
                'Customers.Name AS Name',
                'Customers.Area AS Area',
            ]);
            $this->ReadDb->from('Customers.CustomerTbl as Customers');
            if($Term) {
                $this->ReadDb->group_start();
                $this->ReadDb->or_like('Customers.Name', $Term, 'both');
                $this->ReadDb->or_like('Customers.Area', $Term, 'both');
                $this->ReadDb->or_like('Customers.MobileNumber', $Term, 'both');
                $this->ReadDb->group_end();
            }
            $this->ReadDb->where(['Customers.IsDeleted' => 0, 'Customers.IsActive' => 1]);
            if(sizeof($WhereCondition) > 0) {
                $this->ReadDb->where($WhereCondition);
            }
            $this->ReadDb->limit(10);

            $query = $this->ReadDb->get();
            if (!$query) {
                $error = $this->ReadDb->error();
                throw new Exception($error['message'] ?? 'Database error occurred');
            }
            return $query->result();

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }

}