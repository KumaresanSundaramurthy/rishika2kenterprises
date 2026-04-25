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
                'Customers.CustomerTypeUID as CustomerTypeUID',
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

    public function getCustomerStats($OrgUID) {

        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'COUNT(*) AS TotalCount',
                'SUM(CASE WHEN IsActive = 1 THEN 1 ELSE 0 END) AS ActiveCount',
                'SUM(CASE WHEN MONTH(CreatedOn) = MONTH(NOW()) AND YEAR(CreatedOn) = YEAR(NOW()) THEN 1 ELSE 0 END) AS MonthCount',
                'SUM(CASE WHEN CreatedOn >= IF(MONTH(NOW())>=4, CONCAT(YEAR(NOW()),\'-04-01\'), CONCAT(YEAR(NOW())-1,\'-04-01\')) THEN 1 ELSE 0 END) AS FYCount',
                'SUM(CASE WHEN MONTH(CreatedOn) = MONTH(NOW() - INTERVAL 1 MONTH) AND YEAR(CreatedOn) = YEAR(NOW() - INTERVAL 1 MONTH) THEN 1 ELSE 0 END) AS LastMonthCount',
            ]);
            $this->ReadDb->from('Customers.CustomerTbl');
            $this->ReadDb->where(['IsDeleted' => 0, 'OrgUID' => $OrgUID]);
            $query = $this->ReadDb->get();
            if (!$query) throw new Exception('DB error');
            return $query->row();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }

    public function getCustomerListPaginated($orgUID, $limit, $offset, $filter = []) {

        try {
            $this->ReadDb->db_debug = FALSE;

            $baseWhere = ['Customers.IsDeleted' => 0, 'Customers.OrgUID' => $orgUID];
            if (isset($filter['IsActive'])) {
                $baseWhere['Customers.IsActive'] = (int)$filter['IsActive'];
            }

            // Count query
            $this->ReadDb->select('COUNT(*) AS cnt');
            $this->ReadDb->from('Customers.CustomerTbl as Customers');
            $this->ReadDb->where($baseWhere);
            if (!empty($filter['SearchAllData'])) {
                $s = $filter['SearchAllData'];
                $this->ReadDb->group_start();
                $this->ReadDb->or_like('Customers.Name', $s, 'both');
                $this->ReadDb->or_like('Customers.Area', $s, 'both');
                $this->ReadDb->or_like('Customers.MobileNumber', $s, 'both');
                $this->ReadDb->or_like('Customers.ContactPerson', $s, 'both');
                $this->ReadDb->group_end();
            }
            if (!empty($filter['Tags'])) {
                $tags = is_array($filter['Tags']) ? $filter['Tags'] : [$filter['Tags']];
                $this->ReadDb->group_start();
                foreach ($tags as $tag) { $this->ReadDb->or_like('Customers.Tags', $tag, 'both'); }
                $this->ReadDb->group_end();
            }
            if (!empty($filter['CustomerTypeUIDs'])) {
                $typeUIDs = array_filter(array_map('intval', (array)$filter['CustomerTypeUIDs']));
                if (!empty($typeUIDs)) $this->ReadDb->where_in('Customers.CustomerTypeUID', $typeUIDs);
            }
            $cntQuery = $this->ReadDb->get();
            if (!$cntQuery) throw new Exception($this->ReadDb->error()['message'] ?? 'DB error');
            $totalCount = (int) $cntQuery->row()->cnt;

            // Data query
            $this->ReadDb->select([
                'Customers.CustomerUID AS TablePrimaryUID',
                'Customers.CustomerUID AS CustomerUID',
                'Customers.OrgUID AS OrgUID',
                'Customers.Name AS Name',
                'Customers.Area AS Area',
                'Customers.CountryISO2 AS CountryISO2',
                'Customers.CountryCode AS CountryCode',
                'Customers.MobileNumber AS MobileNumber',
                'Customers.EmailAddress AS EmailAddress',
                'Customers.GSTIN AS GSTIN',
                'Customers.CompanyName AS CompanyName',
                'Customers.DebitCreditType AS DebitCreditType',
                'Customers.DebitCreditAmount AS DebitCreditAmount',
                'Customers.Image AS Image',
                'Customers.PANNumber AS PANNumber',
                'Customers.ContactPerson AS ContactPerson',
                'Customers.DateOfBirth AS DateOfBirth',
                'Customers.DiscountPercent AS DiscountPercent',
                'Customers.CreditPeriod AS CreditPeriod',
                'Customers.CreditLimit AS CreditLimit',
                'Customers.Notes AS Notes',
                'Customers.Tags AS Tags',
                'Customers.CCEmails AS CCEmails',
                'Customers.CustomerTypeUID AS CustomerTypeUID',
                'Customers.IsActive AS IsActive',
                'Customers.CreatedOn AS CreatedOn',
                'Customers.UpdatedOn AS UpdatedOn',
                "CONCAT(User.FirstName, ' ', User.LastName) AS UpdatedBy",
                'IFNULL(COA.CurrentBalance, 0.00) AS ClosingBalance',
                "IFNULL(COA.CurrentBalanceType, 'Debit') AS ClosingBalanceType",
                'CT.TypeName AS CustomerTypeName',
            ]);
            $this->ReadDb->from('Customers.CustomerTbl as Customers');
            $this->ReadDb->join('Users.UserTbl as User', 'User.UserUID = Customers.UpdatedBy', 'left');
            $this->ReadDb->join(
                'Accounting.EntityLedgerMap as ELM',
                "ELM.CustomerUID = Customers.CustomerUID AND ELM.EntityType = 'Customer' AND ELM.IsDeleted = 0",
                'left'
            );
            $this->ReadDb->join(
                'Accounting.ChartOfAccounts as COA',
                'COA.LedgerUID = ELM.LedgerUID AND COA.IsDeleted = 0',
                'left'
            );
            $this->ReadDb->join(
                'Customers.CustomerTypeTbl as CT',
                'CT.CustomerTypeUID = Customers.CustomerTypeUID AND CT.IsDeleted = 0',
                'left'
            );
            $this->ReadDb->where($baseWhere);
            if (!empty($filter['SearchAllData'])) {
                $s = $filter['SearchAllData'];
                $this->ReadDb->group_start();
                $this->ReadDb->or_like('Customers.Name', $s, 'both');
                $this->ReadDb->or_like('Customers.Area', $s, 'both');
                $this->ReadDb->or_like('Customers.MobileNumber', $s, 'both');
                $this->ReadDb->or_like('Customers.ContactPerson', $s, 'both');
                $this->ReadDb->group_end();
            }
            if (!empty($filter['Tags'])) {
                $tags = is_array($filter['Tags']) ? $filter['Tags'] : [$filter['Tags']];
                $this->ReadDb->group_start();
                foreach ($tags as $tag) {
                    $this->ReadDb->or_like('Customers.Tags', $tag, 'both');
                }
                $this->ReadDb->group_end();
            }
            if (!empty($filter['CustomerTypeUIDs'])) {
                $typeUIDs = array_filter(array_map('intval', (array)$filter['CustomerTypeUIDs']));
                if (!empty($typeUIDs)) $this->ReadDb->where_in('Customers.CustomerTypeUID', $typeUIDs);
            }
            if (!empty($filter['NameSorting'])) {
                $this->ReadDb->order_by('Customers.Name', (int)$filter['NameSorting'] === 1 ? 'ASC' : 'DESC');
            } elseif (!empty($filter['AreaSorting'])) {
                $this->ReadDb->order_by('Customers.Area', (int)$filter['AreaSorting'] === 1 ? 'ASC' : 'DESC');
            } elseif (!empty($filter['BalanceSorting'])) {
                $this->ReadDb->order_by('ClosingBalance', (int)$filter['BalanceSorting'] === 1 ? 'ASC' : 'DESC');
            } else {
                $this->ReadDb->order_by('Customers.CustomerUID', 'DESC');
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

    public function getCustomerTypeList($OrgUID) {

        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'CT.CustomerTypeUID AS CustomerTypeUID',
                'CT.TypeName AS TypeName',
                'CT.IsDefault AS IsDefault',
            ]);
            $this->ReadDb->from('Customers.CustomerTypeTbl as CT');
            $this->ReadDb->where([
                'CT.OrgUID'     => (int) $OrgUID,
                'CT.IsDeleted'  => 0,
                'CT.IsActive'   => 1,
            ]);
            $this->ReadDb->order_by('CT.CustomerTypeUID', 'ASC');
            $query = $this->ReadDb->get();
            $error = $this->ReadDb->error();
            if ($error['code']) throw new Exception($error['message']);
            return $query->result();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }

    public function getCustomerTags($OrgUID) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('Customers.Tags AS Tags');
            $this->ReadDb->from('Customers.CustomerTbl as Customers');
            $this->ReadDb->where(['Customers.OrgUID' => (int)$OrgUID, 'Customers.IsDeleted' => 0, 'Customers.IsActive' => 1]);
            $this->ReadDb->where('Customers.Tags IS NOT NULL');
            $this->ReadDb->where('Customers.Tags !=', '');
            $query = $this->ReadDb->get();
            if (!$query) throw new Exception($this->ReadDb->error()['message'] ?? 'DB error');
            $tags = [];
            foreach ($query->result() as $row) {
                foreach (explode(',', $row->Tags) as $t) {
                    $t = trim($t);
                    if ($t !== '') $tags[$t] = true;
                }
            }
            return array_keys($tags);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

}