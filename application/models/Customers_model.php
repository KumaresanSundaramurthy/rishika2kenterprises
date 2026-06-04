<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Customers_model extends CI_Model {
    
    private $ReadDb;
    private $WriteDb;

	function __construct() {
        parent::__construct();
        
        $this->ReadDb  = $this->load->database('ReadDB',  TRUE);
        $this->WriteDb = $this->load->database('WriteDB', TRUE);

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

            // Range conditions so idx_customers_created is usable (not MONTH()/YEAR())
            $thisMonthStart = date('Y-m-01');
            $nextMonthStart = date('Y-m-01', strtotime('+1 month'));
            $lastMonthStart = date('Y-m-01', strtotime('-1 month'));
            $fyStart        = (date('n') >= 4)
                              ? date('Y') . '-04-01'
                              : (date('Y') - 1) . '-04-01';

            $this->ReadDb->select([
                'COUNT(C.CustomerUID) AS TotalCount',
                'SUM(CASE WHEN C.IsActive = 1 THEN 1 ELSE 0 END) AS ActiveCount',
                "SUM(CASE WHEN C.CreatedOn >= '{$thisMonthStart}' AND C.CreatedOn < '{$nextMonthStart}' THEN 1 ELSE 0 END) AS MonthCount",
                "SUM(CASE WHEN C.CreatedOn >= '{$fyStart}' THEN 1 ELSE 0 END) AS FYCount",
                "SUM(CASE WHEN C.CreatedOn >= '{$lastMonthStart}' AND C.CreatedOn < '{$thisMonthStart}' THEN 1 ELSE 0 END) AS LastMonthCount",
                // To Collect: customers who owe us (Debit balance)
                "SUM(CASE WHEN COB.PendingBalType = 'Debit' AND COB.PendingBalance > 0 THEN 1 ELSE 0 END) AS ToCollectCount",
                "COALESCE(SUM(CASE WHEN COB.PendingBalType = 'Debit' AND COB.PendingBalance > 0 THEN COB.PendingBalance ELSE 0 END), 0) AS ToCollectAmount",
                // To Pay: we owe customers (Credit balance)
                "SUM(CASE WHEN COB.PendingBalType = 'Credit' AND COB.PendingBalance > 0 THEN 1 ELSE 0 END) AS ToPayCount",
                "COALESCE(SUM(CASE WHEN COB.PendingBalType = 'Credit' AND COB.PendingBalance > 0 THEN COB.PendingBalance ELSE 0 END), 0) AS ToPayAmount",
            ]);
            $this->ReadDb->from('Customers.CustomerTbl C');
            $this->ReadDb->join(
                'Customers.CustOpeningBalanceTbl COB',
                'COB.CustomerUID = C.CustomerUID AND COB.OrgUID = C.OrgUID AND COB.IsDeleted = 0',
                'left'
            );
            $this->ReadDb->where(['C.IsDeleted' => 0, 'C.OrgUID' => (int)$OrgUID]);
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
                $baseWhere['Customers.IsActive'] = (int) $filter['IsActive'];
            }

            // ToCollect / ToPay filter — subquery approach works for both count and data queries
            $balanceSubquery = null;
            if (!empty($filter['BalanceType'])) {
                $balType        = ($filter['BalanceType'] === 'Credit') ? 'Credit' : 'Debit';
                $balanceSubquery = "Customers.CustomerUID IN (
                    SELECT CustomerUID FROM Customers.CustOpeningBalanceTbl
                    WHERE OrgUID = {$orgUID} AND PendingBalType = '{$balType}'
                      AND PendingBalance > 0 AND IsDeleted = 0
                )";
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
            if ($balanceSubquery) $this->ReadDb->where($balanceSubquery, null, false);
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
                'ShipAddr.Line1 AS ShipLine1',
                'ShipAddr.Line2 AS ShipLine2',
                'ShipAddr.CityText AS ShipCity',
                'ShipAddr.StateText AS ShipState',
                'ShipAddr.Pincode AS ShipPincode',
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
            $this->ReadDb->join(
                'Customers.CustAddressTbl as ShipAddr',
                "ShipAddr.CustomerUID = Customers.CustomerUID AND ShipAddr.AddressType = 'Shipping' AND ShipAddr.IsDeleted = 0 AND ShipAddr.IsActive = 1",
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
            if (!empty($filter['UpdatedByUIDs'])) {
                $uids = array_filter(array_map('intval', (array)$filter['UpdatedByUIDs']));
                if (!empty($uids)) $this->ReadDb->where_in('Customers.UpdatedBy', $uids);
            }
            if ($balanceSubquery) $this->ReadDb->where($balanceSubquery, null, false);
            if (!empty($filter['NameSorting'])) {
                $this->ReadDb->order_by('Customers.Name', (int)$filter['NameSorting'] === 1 ? 'ASC' : 'DESC');
            } elseif (!empty($filter['AreaSorting'])) {
                $this->ReadDb->order_by('Customers.Area', (int)$filter['AreaSorting'] === 1 ? 'ASC' : 'DESC');
            } elseif (!empty($filter['BalanceSorting'])) {
                $this->ReadDb->order_by('ClosingBalance', (int)$filter['BalanceSorting'] === 1 ? 'ASC' : 'DESC');
            } else {
                $this->ReadDb->order_by('Customers.CustomerUID', 'DESC');
            }
            if ($limit > 0) {
                $this->ReadDb->limit($limit, $offset);
            }
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

    // ── Balance recalculation queries ─────────────────────────────────────────

    public function getCustomersWithLedgerForBalance($orgUID, $customerUID = 0) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'C.CustomerUID',
                'IFNULL(COB.OpeningBalance, 0.00)   AS OpeningBalance',
                "IFNULL(COB.OpeningBalType, 'Debit') AS OpeningBalType",
                'ELM.LedgerUID',
                'COA.CurrentBalance     AS LedgerCurrentBalance',
                'COA.CurrentBalanceType AS LedgerCurrentType',
            ]);
            $this->ReadDb->from('Customers.CustomerTbl C');
            $this->ReadDb->join(
                'Customers.CustOpeningBalanceTbl COB',
                'COB.CustomerUID = C.CustomerUID AND COB.IsDeleted = 0',
                'left'
            );
            $this->ReadDb->join(
                'Accounting.EntityLedgerMap ELM',
                "ELM.CustomerUID = C.CustomerUID AND ELM.EntityType = 'Customer' AND ELM.IsDeleted = 0",
                'left'
            );
            $this->ReadDb->join(
                'Accounting.ChartOfAccounts COA',
                'COA.LedgerUID = ELM.LedgerUID AND COA.IsDeleted = 0',
                'left'
            );
            $this->ReadDb->where(['C.OrgUID' => (int)$orgUID, 'C.IsDeleted' => 0, 'C.IsActive' => 1]);
            if ($customerUID > 0) {
                $this->ReadDb->where('C.CustomerUID', (int)$customerUID);
            }
            $query = $this->ReadDb->get();
            if (!$query) throw new Exception($this->ReadDb->error()['message'] ?? 'DB error');
            return $query->result();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getCustomerTotalInvoiced($orgUID, $customerUID) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('COALESCE(SUM(NetAmount), 0) AS total');
            $this->ReadDb->from('`Transaction`.TransactionsTbl');
            $this->ReadDb->where([
                'OrgUID'    => (int)$orgUID,
                'PartyUID'  => (int)$customerUID,
                'PartyType' => 'C',
                'ModuleUID' => 103,
                'IsDeleted' => 0,
            ]);
            // Draft invoices do not affect customer balance — exclude along with Cancelled/Rejected
            $this->ReadDb->where_not_in('DocStatus', ['Draft', 'Cancelled', 'Rejected']);
            $query = $this->ReadDb->get();
            if (!$query) throw new Exception($this->ReadDb->error()['message'] ?? 'DB error');
            return (float) $query->row()->total;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getCustomerTotalReceived($orgUID, $customerUID) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('COALESCE(SUM(Amount), 0) AS total');
            $this->ReadDb->from('`Transaction`.PaymentsTbl');
            $this->ReadDb->where([
                'OrgUID'                       => (int)$orgUID,
                'PartyUID'                     => (int)$customerUID,
                'PartyType'                    => 'C',
                'PaymentDirection'             => 'In',
                'IsDeleted'                    => 0,
                'IsTransferredToCreditNote'    => 0,   // exclude payments moved to credit note
                'IsCancelled'                  => 0,   // exclude voided/reversed payments
            ]);
            $query = $this->ReadDb->get();
            if (!$query) throw new Exception($this->ReadDb->error()['message'] ?? 'DB error');
            return (float) $query->row()->total;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getCustomerTotalReturned($orgUID, $customerUID) {
        try {
            $this->ReadDb->db_debug = FALSE;
            // Use BalanceAmount (outstanding after partial refunds) so that paying back 100 of a 500
            // return correctly reduces the outstanding liability to 400, not the full 500.
            $this->ReadDb->select('COALESCE(SUM(COALESCE(BalanceAmount, NetAmount)), 0) AS total');
            $this->ReadDb->from('`Transaction`.TransactionsTbl');
            $this->ReadDb->where([
                'OrgUID'    => (int)$orgUID,
                'PartyUID'  => (int)$customerUID,
                'PartyType' => 'C',
                'IsDeleted' => 0,
            ]);
            $this->ReadDb->where_in('ModuleUID', [106, 107]);
            $this->ReadDb->where_not_in('DocStatus', ['Cancelled', 'Rejected']);
            $query = $this->ReadDb->get();
            if (!$query) throw new Exception($this->ReadDb->error()['message'] ?? 'DB error');
            return (float) $query->row()->total;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getCustomerTotalPendingCreditNotes($orgUID, $customerUID) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('COALESCE(SUM(Amount), 0) AS total');
            $this->ReadDb->from('Transaction.CustomerCreditNoteTbl');
            $this->ReadDb->where([
                'OrgUID'      => (int)$orgUID,
                'CustomerUID' => (int)$customerUID,
                'Status'      => 'Pending',
                'IsDeleted'   => 0,
            ]);
            $query = $this->ReadDb->get();
            if (!$query) throw new Exception($this->ReadDb->error()['message'] ?? 'DB error');
            return (float) $query->row()->total;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function updateCustomerBalanceInLedger($ledgerUID, $balance, $balanceType, $userUID) {
        try {
            $this->WriteDb->db_debug = FALSE;
            $this->WriteDb->where('LedgerUID', (int)$ledgerUID);
            $res = $this->WriteDb->update('Accounting.ChartOfAccounts', [
                'CurrentBalance'     => $balance,
                'CurrentBalanceType' => $balanceType,
                'UpdatedBy'          => (int)$userUID,
            ]);
            if ($res === false) throw new Exception('Ledger update failed.');
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function updateCustomerBalanceInCustomerTbl($customerUID, $balance, $balanceType, $userUID) {
        try {
            $this->WriteDb->db_debug = FALSE;
            $this->WriteDb->where('CustomerUID', (int)$customerUID);
            $res = $this->WriteDb->update('Customers.CustomerTbl', [
                'DebitCreditAmount' => $balance,
                'DebitCreditType'   => $balanceType,
                'UpdatedBy'         => (int)$userUID,
            ]);
            if ($res === false) throw new Exception('Customer balance update failed.');
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }


    // â”€â”€ CustOpeningBalanceTbl (one row per customer, no year) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function getCustomerOpeningBalance($orgUID, $customerUID) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'OpeningBalUID', 'OpeningBalance', 'OpeningBalType',
                'PendingBalance', 'PendingBalType', 'Notes',
            ]);
            $this->ReadDb->from('Customers.CustOpeningBalanceTbl');
            $this->ReadDb->where([
                'OrgUID'      => (int)$orgUID,
                'CustomerUID' => (int)$customerUID,
                'IsDeleted'   => 0,
            ]);
            $this->ReadDb->limit(1);
            $query = $this->ReadDb->get();
            if (!$query) throw new Exception($this->ReadDb->error()['message'] ?? 'DB error');
            return $query->row();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function saveCustomerOpeningBalance($orgUID, $customerUID, $openingBalance, $openingBalType, $notes, $userUID) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('OpeningBalUID');
            $this->ReadDb->from('Customers.CustOpeningBalanceTbl');
            $this->ReadDb->where(['OrgUID' => (int)$orgUID, 'CustomerUID' => (int)$customerUID, 'IsDeleted' => 0]);
            $existing = $this->ReadDb->get()->row();

            if ($existing) {
                $this->WriteDb->db_debug = FALSE;
                $this->WriteDb->where('OpeningBalUID', (int)$existing->OpeningBalUID);
                $this->WriteDb->update('Customers.CustOpeningBalanceTbl', [
                    'OpeningBalance' => (float)$openingBalance,
                    'OpeningBalType' => $openingBalType,
                    'Notes'          => $notes ?: NULL,
                    'UpdatedBy'      => (int)$userUID,
                ]);
                return (int)$existing->OpeningBalUID;
            }

            $this->WriteDb->db_debug = FALSE;
            $this->WriteDb->insert('Customers.CustOpeningBalanceTbl', [
                'OrgUID'         => (int)$orgUID,
                'CustomerUID'    => (int)$customerUID,
                'OpeningBalance' => (float)$openingBalance,
                'OpeningBalType' => $openingBalType,
                'PendingBalance' => (float)$openingBalance,
                'PendingBalType' => $openingBalType,
                'Notes'          => $notes ?: NULL,
                'IsActive'       => 1,
                'IsDeleted'      => 0,
                'CreatedBy'      => (int)$userUID,
                'UpdatedBy'      => (int)$userUID,
            ]);
            return (int)$this->WriteDb->insert_id();

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function updateCustomerPendingBalance($orgUID, $customerUID, $pendingBalance, $pendingBalType, $userUID) {
        try {
            $this->WriteDb->db_debug = FALSE;
            $this->WriteDb->where(['OrgUID' => (int)$orgUID, 'CustomerUID' => (int)$customerUID, 'IsDeleted' => 0]);
            $this->WriteDb->update('Customers.CustOpeningBalanceTbl', [
                'PendingBalance' => (float)$pendingBalance,
                'PendingBalType' => $pendingBalType,
                'UpdatedBy'      => (int)$userUID,
            ]);
            // If no row existed, seed one so the pending balance is visible on the customer page
            if ($this->WriteDb->affected_rows() === 0) {
                $this->WriteDb->insert('Customers.CustOpeningBalanceTbl', [
                    'OrgUID'         => (int)$orgUID,
                    'CustomerUID'    => (int)$customerUID,
                    'OpeningBalance' => 0.00,
                    'OpeningBalType' => 'Debit',
                    'PendingBalance' => (float)$pendingBalance,
                    'PendingBalType' => $pendingBalType,
                    'IsActive'       => 1,
                    'IsDeleted'      => 0,
                    'CreatedBy'      => (int)$userUID,
                    'UpdatedBy'      => (int)$userUID,
                ]);
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getCustomerOpeningBalanceSigned($orgUID, $customerUID) {
        // Returns opening balance as signed float: Debit=+, Credit=-
        $row = $this->getCustomerOpeningBalance($orgUID, $customerUID);
        if (!$row) return 0.0;
        $amt = (float)$row->OpeningBalance;
        return ($row->OpeningBalType === 'Debit') ? $amt : -$amt;
    }

    public function getCustomerDebitCreditRaw($customerUID) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select(['Name', 'DebitCreditAmount', 'DebitCreditType']);
            $this->ReadDb->from('Customers.CustomerTbl');
            $this->ReadDb->where(['CustomerUID' => (int)$customerUID, 'IsDeleted' => 0]);
            $this->ReadDb->limit(1);
            return $this->ReadDb->get()->row();
        } catch (Exception $e) {
            return null;
        }
    }

    // Applies a signed numeric delta (+/-) to the customer running opening balance.
    // Returns ['balance' => float, 'type' => 'Debit'|'Credit'].
    public function applyOpeningBalanceDelta($orgUID, $customerUID, $delta, $userUID) {
        $row           = $this->getCustomerOpeningBalance($orgUID, $customerUID);
        $currentSigned = 0.0;
        if ($row) {
            $currentSigned = ($row->OpeningBalType === 'Debit') ? (float)$row->OpeningBalance : -(float)$row->OpeningBalance;
        }
        $newSigned  = round($currentSigned + $delta, 2);
        $newBalance = abs($newSigned);
        $newType    = ($newSigned >= 0) ? 'Debit' : 'Credit';
        $this->saveCustomerOpeningBalance($orgUID, $customerUID, $newBalance, $newType, null, $userUID);
        return ['balance' => $newBalance, 'type' => $newType];
    }

    // ── CustYearOpeningBalanceTbl (year-wise opening balance snapshot) ─────────

    // $onlyIfNew=true: insert-only, preserving the year-start snapshot.
    public function saveCustomerYearOpening($orgUID, $customerUID, $financialYear, $openingBalance, $openingBalType, $userUID, $onlyIfNew = false) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('YearBalUID');
            $this->ReadDb->from('Customers.CustYearOpeningBalanceTbl');
            $this->ReadDb->where([
                'OrgUID'        => (int)$orgUID,
                'CustomerUID'   => (int)$customerUID,
                'FinancialYear' => (int)$financialYear,
                'IsDeleted'     => 0,
            ]);
            $existing = $this->ReadDb->get()->row();

            if ($existing) {
                if ($onlyIfNew) return (int)$existing->YearBalUID;
                $this->WriteDb->db_debug = FALSE;
                $this->WriteDb->where('YearBalUID', (int)$existing->YearBalUID);
                $this->WriteDb->update('Customers.CustYearOpeningBalanceTbl', [
                    'OpeningBalance' => (float)$openingBalance,
                    'OpeningBalType' => $openingBalType,
                    'UpdatedBy'      => (int)$userUID,
                ]);
                return (int)$existing->YearBalUID;
            }

            $this->WriteDb->db_debug = FALSE;
            $this->WriteDb->insert('Customers.CustYearOpeningBalanceTbl', [
                'OrgUID'         => (int)$orgUID,
                'CustomerUID'    => (int)$customerUID,
                'FinancialYear'  => (int)$financialYear,
                'OpeningBalance' => (float)$openingBalance,
                'OpeningBalType' => $openingBalType,
                'IsActive'       => 1,
                'IsDeleted'      => 0,
                'CreatedBy'      => (int)$userUID,
                'UpdatedBy'      => (int)$userUID,
            ]);
            return (int)$this->WriteDb->insert_id();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getCustomerYearOpening($orgUID, $customerUID, $financialYear) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select(['YearBalUID', 'FinancialYear', 'OpeningBalance', 'OpeningBalType']);
            $this->ReadDb->from('Customers.CustYearOpeningBalanceTbl');
            $this->ReadDb->where([
                'OrgUID'        => (int)$orgUID,
                'CustomerUID'   => (int)$customerUID,
                'FinancialYear' => (int)$financialYear,
                'IsDeleted'     => 0,
            ]);
            $this->ReadDb->limit(1);
            $query = $this->ReadDb->get();
            if (!$query) throw new Exception($this->ReadDb->error()['message'] ?? 'DB error');
            return $query->row();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    // â”€â”€ CustBalanceHistoryTbl (year-wise snapshots) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function getCustomerBalanceHistory($orgUID, $customerUID) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'BalHistoryUID', 'FinancialYear',
                'OpeningBalance', 'OpeningBalType',
                'TotalInvoiced',  'TotalReceived', 'TotalReturned',
                'ClosingBalance', 'ClosingBalType',
                'SnapshotOn',
            ]);
            $this->ReadDb->from('Customers.CustBalanceHistoryTbl');
            $this->ReadDb->where(['OrgUID' => (int)$orgUID, 'CustomerUID' => (int)$customerUID]);
            $this->ReadDb->order_by('FinancialYear', 'DESC');
            $query = $this->ReadDb->get();
            if (!$query) throw new Exception($this->ReadDb->error()['message'] ?? 'DB error');
            return $query->result();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function saveCustomerBalanceHistory($orgUID, $customerUID, $financialYear, $openingBalance, $openingBalType, $totalInvoiced, $totalReceived, $totalReturned, $closingBalance, $closingBalType, $userUID) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('BalHistoryUID');
            $this->ReadDb->from('Customers.CustBalanceHistoryTbl');
            $this->ReadDb->where(['OrgUID' => (int)$orgUID, 'CustomerUID' => (int)$customerUID, 'FinancialYear' => (int)$financialYear]);
            $existing = $this->ReadDb->get()->row();

            $data = [
                'OpeningBalance' => (float)$openingBalance,
                'OpeningBalType' => $openingBalType,
                'TotalInvoiced'  => (float)$totalInvoiced,
                'TotalReceived'  => (float)$totalReceived,
                'TotalReturned'  => (float)$totalReturned,
                'ClosingBalance' => (float)$closingBalance,
                'ClosingBalType' => $closingBalType,
                'SnapshotOn'     => date('Y-m-d H:i:s'),
                'UpdatedBy'      => (int)$userUID,
            ];

            $this->WriteDb->db_debug = FALSE;
            if ($existing) {
                $this->WriteDb->where('BalHistoryUID', (int)$existing->BalHistoryUID);
                $this->WriteDb->update('Customers.CustBalanceHistoryTbl', $data);
                return (int)$existing->BalHistoryUID;
            }

            $this->WriteDb->insert('Customers.CustBalanceHistoryTbl', array_merge($data, [
                'OrgUID'        => (int)$orgUID,
                'CustomerUID'   => (int)$customerUID,
                'FinancialYear' => (int)$financialYear,
                'CreatedBy'     => (int)$userUID,
            ]));
            return (int)$this->WriteDb->insert_id();

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getCustomerOnAccountPayments($orgUID, $customerUID) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('P.PaymentUID, P.Amount, P.CreatedOn, P.Notes,
                T.UniqueNumber AS SourceInvoiceNumber, T.NetAmount AS SourceInvoiceAmount,
                T.TransDate AS SourceInvoiceDate');
            $this->ReadDb->from('Transaction.PaymentsTbl P');
            $this->ReadDb->join('Transaction.TransactionsTbl T', 'T.TransUID = P.TransUID', 'left');
            $this->ReadDb->where([
                'P.OrgUID'           => (int)$orgUID,
                'P.PartyUID'         => (int)$customerUID,
                'P.PartyType'        => 'C',
                'P.PaymentDirection' => 'In',
                'P.IsOnAccount'      => 1,
                'P.IsDeleted'        => 0,
                'P.IsCancelled'      => 0,
            ]);
            // FIFO — oldest first
            $this->ReadDb->order_by('P.CreatedOn', 'ASC');
            $result = $this->ReadDb->get()->result_array();
            return $result ?: [];
        } catch (Exception $e) {
            return [];
        }
    }

}
