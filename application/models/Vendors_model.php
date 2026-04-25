<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Vendors_model extends CI_Model {
    
    private $EndReturnData;
    private $ReadDb;

	function __construct() {
        parent::__construct();

        $this->ReadDb = $this->load->database('ReadDB', TRUE);

    }

    public function vendFilterFormation($ModuleInfoData, $Filter) {

        $this->EndReturnData = new StdClass();
        try {

            $SearchDirectQuery = '';
            $SearchFilter = [];
            $sortOperation = [];
            if(!empty($Filter)) {
                if (array_key_exists('SearchAllData', $Filter)) {
                    $SearchDirectQuery .= "((". $ModuleInfoData->TableAliasName.".Name LIKE '%".$Filter['SearchAllData']."%' ) OR (".$ModuleInfoData->TableAliasName.".Area LIKE '%".$Filter['SearchAllData']."%') OR (".$ModuleInfoData->TableAliasName.".MobileNumber LIKE '%".$Filter['SearchAllData']."%') OR (".$ModuleInfoData->TableAliasName.".ContactPerson LIKE '%".$Filter['SearchAllData']."%'))";
                }
                if (array_key_exists('NameSorting', $Filter)) {
                    $sortOperation[$ModuleInfoData->TableAliasName . '.Name'] = $Filter['NameSorting'] == 1 ? 'ASC' : 'DESC';
                }
            }

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->SearchDirectQuery = $SearchDirectQuery;
            $this->EndReturnData->SearchFilter = $SearchFilter;
            $this->EndReturnData->sortOperation = $sortOperation;

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            $this->EndReturnData->SearchDirectQuery = '';
            $this->EndReturnData->SearchFilter = [];
            $this->EndReturnData->sortOperation = [];
        }

        return $this->EndReturnData;

    }

    public function getVendorsList($limit, $offset, $Filter, $Flag = 0) {

        $this->EndReturnData = new StdClass();
        try {

            $this->ReadDb->db_debug = FALSE;
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
            $this->ReadDb->select($select_ary);
            $this->ReadDb->from('Vendors.VendorTbl as Vendors');
            $this->ReadDb->where($WhereCondition);
            if (!empty($Filter)) {
                if (array_key_exists('Name', $Filter)) {
                    $this->ReadDb->like("Vendors.Name", $Filter['Name'], 'Both');
                }
            }
            $this->ReadDb->group_by('Vendors.VendorUID');
            if($Flag == 0) {
                $this->ReadDb->order_by('Vendors.VendorUID', 'DESC');
                $this->ReadDb->limit($limit, $offset);
            }
            
            $query = $this->ReadDb->get();
            $error = $this->ReadDb->error();
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

            $this->ReadDb->db_debug = FALSE;

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

            $this->ReadDb->select($select_ary);
            $this->ReadDb->from('Vendors.VendorTbl as Vendors');
            $this->ReadDb->join('Customers.CustomerTbl as Customers', 'Customers.CustomerUID = Vendors.CustomerUID', 'left');
            $this->ReadDb->where($WhereCondition);
            if(sizeof($FilterArray) > 0) {
                $this->ReadDb->where($FilterArray);
            }
            $this->ReadDb->group_by('Vendors.VendorUID');
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

    public function getVendorBankInfo($FilterArray) {

        $this->EndReturnData = new StdClass();
        try {

            $this->ReadDb->db_debug = FALSE;

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

            $this->ReadDb->select($select_ary);
            $this->ReadDb->from('Vendors.VendBankDetailsTbl as VendBankDetails');
            $this->ReadDb->where($WhereCondition);
            if(sizeof($FilterArray) > 0) {
                $this->ReadDb->where($FilterArray);
            }
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

    public function getVendorAddress($FilterArray) {

        $this->EndReturnData = new StdClass();
        try {

            $this->ReadDb->db_debug = FALSE;

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

            $this->ReadDb->select($select_ary);
            $this->ReadDb->from('Vendors.VendAddressTbl as VendAddress');
            $this->ReadDb->where($WhereCondition);
            if(sizeof($FilterArray) > 0) {
                $this->ReadDb->where($FilterArray);
            }
            $this->ReadDb->group_by('VendAddress.VendAddressUID');
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


    public function getVendorListPaginated($orgUID, $limit, $offset, $filter = []) {

        try {
            $this->ReadDb->db_debug = FALSE;

            $baseWhere = ['Vendors.IsDeleted' => 0, 'Vendors.IsActive' => 1, 'Vendors.OrgUID' => $orgUID];

            // Count query
            $this->ReadDb->select('COUNT(*) AS cnt');
            $this->ReadDb->from('Vendors.VendorTbl as Vendors');
            $this->ReadDb->where($baseWhere);
            if (!empty($filter['SearchAllData'])) {
                $s = $filter['SearchAllData'];
                $this->ReadDb->group_start();
                $this->ReadDb->or_like('Vendors.Name', $s, 'both');
                $this->ReadDb->or_like('Vendors.Area', $s, 'both');
                $this->ReadDb->or_like('Vendors.MobileNumber', $s, 'both');
                $this->ReadDb->or_like('Vendors.ContactPerson', $s, 'both');
                $this->ReadDb->group_end();
            }
            if (isset($filter['IsActive']) && $filter['IsActive'] !== '') {
                $this->ReadDb->where('Vendors.IsActive', (int)$filter['IsActive']);
            }
            $cntQuery = $this->ReadDb->get();
            if (!$cntQuery) throw new Exception($this->ReadDb->error()['message'] ?? 'DB error');
            $totalCount = (int) $cntQuery->row()->cnt;

            // Data query
            $this->ReadDb->select([
                'Vendors.VendorUID AS TablePrimaryUID',
                'Vendors.VendorUID AS VendorUID',
                'Vendors.OrgUID AS OrgUID',
                'Vendors.Name AS Name',
                'Vendors.Area AS Area',
                'Vendors.CountryISO2 AS CountryISO2',
                'Vendors.CountryCode AS CountryCode',
                'Vendors.MobileNumber AS MobileNumber',
                'Vendors.EmailAddress AS EmailAddress',
                'Vendors.GSTIN AS GSTIN',
                'Vendors.CompanyName AS CompanyName',
                'Vendors.DebitCreditType AS DebitCreditType',
                'Vendors.DebitCreditAmount AS DebitCreditAmount',
                'Vendors.Image AS Image',
                'Vendors.PANNumber AS PANNumber',
                'Vendors.Notes AS Notes',
                'Vendors.Tags AS Tags',
                'Vendors.CCEmails AS CCEmails',
                'Vendors.CreatedOn AS CreatedOn',
                'Vendors.UpdatedOn AS UpdatedOn',
                "CONCAT(User.FirstName, ' ', User.LastName) AS UpdatedBy",
                'IFNULL(COA.CurrentBalance, 0.00) AS ClosingBalance',
                "IFNULL(COA.CurrentBalanceType, 'Credit') AS ClosingBalanceType",
            ]);
            $this->ReadDb->from('Vendors.VendorTbl as Vendors');
            $this->ReadDb->join('Users.UserTbl as User', 'User.UserUID = Vendors.UpdatedBy', 'left');
            $this->ReadDb->join(
                'Accounting.EntityLedgerMap as ELM',
                "ELM.VendorUID = Vendors.VendorUID AND ELM.EntityType = 'Vendor' AND ELM.IsDeleted = 0",
                'left'
            );
            $this->ReadDb->join(
                'Accounting.ChartOfAccounts as COA',
                'COA.LedgerUID = ELM.LedgerUID AND COA.IsDeleted = 0',
                'left'
            );
            $this->ReadDb->where($baseWhere);
            if (!empty($filter['SearchAllData'])) {
                $s = $filter['SearchAllData'];
                $this->ReadDb->group_start();
                $this->ReadDb->or_like('Vendors.Name', $s, 'both');
                $this->ReadDb->or_like('Vendors.Area', $s, 'both');
                $this->ReadDb->or_like('Vendors.MobileNumber', $s, 'both');
                $this->ReadDb->or_like('Vendors.ContactPerson', $s, 'both');
                $this->ReadDb->group_end();
            }
            // IsActive filter
            if (isset($filter['IsActive']) && $filter['IsActive'] !== '') {
                $this->ReadDb->where('Vendors.IsActive', (int)$filter['IsActive']);
            }
            // Sorting
            if (!empty($filter['NameSorting'])) {
                $this->ReadDb->order_by('Vendors.Name', (int)$filter['NameSorting'] === 1 ? 'ASC' : 'DESC');
            } elseif (!empty($filter['AreaSorting'])) {
                $this->ReadDb->order_by('Vendors.Area', (int)$filter['AreaSorting'] === 1 ? 'ASC' : 'DESC');
            } elseif (!empty($filter['BalanceSorting'])) {
                $this->ReadDb->order_by('COA.CurrentBalance', (int)$filter['BalanceSorting'] === 1 ? 'ASC' : 'DESC');
            } else {
                $this->ReadDb->order_by('Vendors.VendorUID', 'DESC');
            }
            $this->ReadDb->limit($limit, $offset);
            $dataQuery = $this->ReadDb->get();
            if (!$dataQuery) throw new Exception($this->ReadDb->error()['message'] ?? 'DB error');

            $result             = new stdClass();
            $result->rows       = $dataQuery->result();
            $result->totalCount = $totalCount;
            return $result;

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }

    public function getVendorStats($OrgUID) {

        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'COUNT(*) AS TotalCount',
                'SUM(CASE WHEN IsActive = 1 THEN 1 ELSE 0 END) AS ActiveCount',
                'SUM(CASE WHEN MONTH(CreatedOn) = MONTH(NOW()) AND YEAR(CreatedOn) = YEAR(NOW()) THEN 1 ELSE 0 END) AS MonthCount',
                'SUM(CASE WHEN CreatedOn >= IF(MONTH(NOW())>=4, CONCAT(YEAR(NOW()),\'-04-01\'), CONCAT(YEAR(NOW())-1,\'-04-01\')) THEN 1 ELSE 0 END) AS FYCount',
                'SUM(CASE WHEN MONTH(CreatedOn) = MONTH(NOW() - INTERVAL 1 MONTH) AND YEAR(CreatedOn) = YEAR(NOW() - INTERVAL 1 MONTH) THEN 1 ELSE 0 END) AS LastMonthCount',
            ]);
            $this->ReadDb->from('Vendors.VendorTbl');
            $this->ReadDb->where(['IsDeleted' => 0, 'OrgUID' => $OrgUID]);
            $query = $this->ReadDb->get();
            if (!$query) throw new Exception('DB error');
            return $query->row();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }

}