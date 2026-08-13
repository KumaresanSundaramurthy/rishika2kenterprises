<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Customers_model extends CI_Model {

    private $ReadDb;

	function __construct() {
        parent::__construct();

        $this->ReadDb = $this->load->database('ReadDB', TRUE);

    }

    public function getReadDb(): object { return $this->ReadDb; }

    public function getCustomerPendingNoteTotals(int $orgUID, int $customerUID): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $result = $this->ReadDb->query(
                "SELECT
                    COALESCE((SELECT SUM(Amount) FROM Transaction.TransCreditNoteTbl
                              WHERE OrgUID=? AND PartyUID=? AND PartyType='C' AND Status='Pending' AND IsCancelled=0 AND IsDeleted=0 AND PaymentCleared=0), 0) AS CreditTotal,
                    COALESCE((SELECT SUM(Amount) FROM Transaction.TransDebitNoteTbl
                              WHERE OrgUID=? AND PartyUID=? AND PartyType='C' AND Status='Pending' AND IsDeleted=0), 0) AS DebitTotal",
                [(int)$orgUID, (int)$customerUID, (int)$orgUID, (int)$customerUID]
            );
            $row = $result ? $result->row() : null;
            return [(float)($row->CreditTotal ?? 0), (float)($row->DebitTotal ?? 0)];
        } catch (Exception $e) {
            log_message('error', 'Customers_model::getCustomerPendingNoteTotals failed: ' . $e->getMessage());
            return [0.0, 0.0];
        }
    }

    public function getCustomers(array $FilterArray): array {

        try {

            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'Customers.CustomerUID AS CustomerUID',
                'Customers.OrgUID AS OrgUID',
                'Customers.SalutationUID AS SalutationUID',
                'Customers.Name AS Name',
                'Customers.Area AS Area',
                'Customers.CountryISO2 as CountryISO2',
                'Customers.CountryCode as CountryCode',
                'Customers.MobileNumber as MobileNumber',
                'Customers.EmailAddress as EmailAddress',
                'Customers.GSTIN as GSTIN',
                'Customers.CompanyName as CompanyName',
                'Customers.WorkPhone as WorkPhone',
                'Customers.LandlineNumber as LandlineNumber',
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
                'Customers.AllowPortalAccess as AllowPortalAccess',
                'Customers.CustomerTypeUID as CustomerTypeUID',
                'COALESCE(CGM.GroupUID, NULL) as GroupUID',
                'COALESCE(CGM.IsGroupPrimary, 0) as IsGroupPrimary',
                'Customers.CreatedOn as CreatedOn',
                'Customers.CustomerNumber as CustomerNumber',
                'Customers.UpdatedOn as UpdatedOn',
                // Actual opening balance from CustOpeningBalanceTbl (source of truth)
                'COALESCE(COB.OpeningBalance, Customers.DebitCreditAmount) as OpeningBalance',
                'COALESCE(COB.OpeningBalType, Customers.DebitCreditType, \'Debit\') as OpeningBalType',
            ]);
            $this->ReadDb->from('Customers.CustomerTbl as Customers');
            $this->ReadDb->join(
                'Customers.CustOpeningBalanceTbl as COB',
                'COB.CustomerUID = Customers.CustomerUID AND COB.OrgUID = Customers.OrgUID AND COB.IsDeleted = 0',
                'left'
            );
            $this->ReadDb->join(
                'Customers.CustGroupMemberTbl as CGM',
                'CGM.CustomerUID = Customers.CustomerUID AND CGM.OrgUID = Customers.OrgUID AND CGM.IsDeleted = 0',
                'left'
            );
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

    public function getCustomerAddress(array $FilterArray): array {

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

    public function getCustomerBankInfo(array $FilterArray): array {

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

    public function getCustomersDetails(string $Term, array $WhereCondition = []): array {
        
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

    public function getCustomerStats(int $OrgUID): object {

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

    public function getCustomerUIDsByFilter(int $orgUID, array $filter = []): array {
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->select('Customers.CustomerUID');
        $this->ReadDb->from('Customers.CustomerTbl as Customers');
        $baseWhere = ['Customers.IsDeleted' => 0, 'Customers.OrgUID' => $orgUID];
        if (isset($filter['IsActive'])) {
            $baseWhere['Customers.IsActive'] = (int) $filter['IsActive'];
        }
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
        if (!empty($filter['BalanceType'])) {
            $balType = ($filter['BalanceType'] === 'Credit') ? 'Credit' : 'Debit';
            $this->ReadDb->where("Customers.CustomerUID IN (
                SELECT CustomerUID FROM Customers.CustOpeningBalanceTbl
                WHERE OrgUID = {$orgUID} AND PendingBalType = '{$balType}'
                  AND PendingBalance > 0 AND IsDeleted = 0
            )", null, false);
        }
        $query = $this->ReadDb->get();
        if (!$query) return [];
        return array_column($query->result_array(), 'CustomerUID');
    }

    public function getCustomerListPaginated(int $orgUID, int $limit, int $offset, array $filter = []): object {

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
                'Customers.SalutationUID AS SalutationUID',
                'Sal.SalutationName AS SalutationName',
                'Customers.Name AS Name',
                'Customers.Area AS Area',
                'Customers.CountryISO2 AS CountryISO2',
                'Customers.CountryCode AS CountryCode',
                'Customers.MobileNumber AS MobileNumber',
                'Customers.EmailAddress AS EmailAddress',
                'Customers.GSTIN AS GSTIN',
                'Customers.GSTINValidated AS GSTINValidated',
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
                'IFNULL(COB.PendingBalance, 0.00) AS ClosingBalance',
                "IFNULL(COB.PendingBalType, 'Debit') AS ClosingBalanceType",
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
                'Customers.CustomerTypeTbl as CT',
                'CT.CustomerTypeUID = Customers.CustomerTypeUID AND CT.IsDeleted = 0',
                'left'
            );
            $this->ReadDb->join(
                'Customers.CustAddressTbl as ShipAddr',
                "ShipAddr.CustomerUID = Customers.CustomerUID AND ShipAddr.AddressType = 'Shipping' AND ShipAddr.IsDeleted = 0 AND ShipAddr.IsActive = 1",
                'left'
            );
            $this->ReadDb->join(
                'Customers.CustOpeningBalanceTbl as COB',
                'COB.CustomerUID = Customers.CustomerUID AND COB.OrgUID = Customers.OrgUID AND COB.IsDeleted = 0',
                'left'
            );
            $this->ReadDb->join(
                'Global.SalutationTbl as Sal',
                'Sal.SalutationUID = Customers.SalutationUID AND Sal.IsDeleted = 0',
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

            $rows = $dataQuery->result();

            // Batch-fetch all attachments per customer in one query; build thumbnail + gallery maps together
            if (!empty($rows)) {
                $custUIDs   = array_column((array)$rows, 'CustomerUID');
                $cdnUrl     = rtrim(getenv('FILE_UPLOAD') == 'amazonaws' ? getenv('CDN_URL') : getenv('CFLARE_R2_CDN'), '/');
                $ph         = implode(',', array_fill(0, count($custUIDs), '?'));
                $attQ       = $this->ReadDb->query(
                    "SELECT CustomerUID, FilePath, FileName FROM Customers.CustomerAttachmentsTbl
                      WHERE CustomerUID IN ({$ph}) AND IsDeleted = 0
                      ORDER BY CustomerUID, SortOrder ASC",
                    $custUIDs
                );
                $attMap     = [];
                $galleryMap = [];
                if ($attQ) {
                    foreach ($attQ->result() as $att) {
                        $uid  = (int)$att->CustomerUID;
                        $entry = ['url' => $cdnUrl . '/' . ltrim($att->FilePath, '/'), 'name' => $att->FileName];
                        if (!isset($attMap[$uid])) { $attMap[$uid] = $entry; }
                        $galleryMap[$uid][] = $entry;
                    }
                }
                foreach ($rows as $row) {
                    $uid = (int)$row->CustomerUID;
                    $row->PrimaryImageUrl = isset($attMap[$uid]) ? $attMap[$uid]['url'] : null;
                    $row->AttachmentsJson = json_encode($galleryMap[$uid] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                }
            }

            $result             = new stdClass();
            $result->rows       = $rows;
            $result->totalCount = $totalCount;
            return $result;

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }

    public function getCustomerTypeList(int $OrgUID): array {

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

    // Pure DB fetch — no OrgUID filter (global data); used by AJAX endpoint
    public function getCustomerTypesFromDB(): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'CT.CustomerTypeUID AS CustomerTypeUID',
                'CT.TypeName AS TypeName',
                'CT.IsDefault AS IsDefault',
            ]);
            $this->ReadDb->from('Customers.CustomerTypeTbl as CT');
            $this->ReadDb->where(['CT.IsDeleted' => 0, 'CT.IsActive' => 1]);
            $this->ReadDb->order_by('CT.CustomerTypeUID', 'ASC');
            $query = $this->ReadDb->get();
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            return [];
        }
    }

    public function getCustomerTags(int $OrgUID): array {
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

    public function getCustomersWithLedgerForBalance(int $orgUID, int $customerUID = 0): array {
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

    public function getCustomerTotalInvoiced(int $orgUID, int $customerUID): float {
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

    public function getCustomerTotalReceived(int $orgUID, int $customerUID): float {
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
                'IsExcessApplied'              => 0,   // exclude advance allocation memo rows (no new cash)
            ]);
            $query = $this->ReadDb->get();
            if (!$query) throw new Exception($this->ReadDb->error()['message'] ?? 'DB error');
            return (float) $query->row()->total;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getCustomerTotalReturned(int $orgUID, int $customerUID): float {
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

    public function getCustomerSRCoveredByCreditNote(int $orgUID, int $customerUID): float {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('COALESCE(SUM(COALESCE(T.BalanceAmount, T.NetAmount)), 0) AS total');
            $this->ReadDb->from('`Transaction`.TransactionsTbl T');
            $this->ReadDb->join(
                '`Transaction`.TransCreditNoteTbl CN',
                'CN.SourceTransUID = T.TransUID AND CN.SourceModuleUID = 106 AND CN.IsDeleted = 0 AND CN.OrgUID = ' . (int)$orgUID,
                'inner'
            );
            $this->ReadDb->where([
                'T.OrgUID'    => (int)$orgUID,
                'T.PartyUID'  => (int)$customerUID,
                'T.PartyType' => 'C',
                'T.IsDeleted' => 0,
                'T.ModuleUID' => 106,
            ]);
            $this->ReadDb->where_not_in('T.DocStatus', ['Cancelled', 'Rejected']);
            $this->ReadDb->where('CN.IsCancelled', 0);
            $query = $this->ReadDb->get();
            if (!$query) throw new Exception($this->ReadDb->error()['message'] ?? 'DB error');
            return (float)$query->row()->total;
        } catch (Exception $e) {
            log_message('error', 'Customers_model::getCustomerSRCoveredByCreditNote failed: ' . $e->getMessage());
            return 0.0;
        }
    }

    public function updateCustomerBalanceInLedger(int $ledgerUID, float $balance, string $balanceType, int $userUID): void {
        try {
            $res = $this->dbwrite_model->updateData('Accounting', 'ChartOfAccounts', [
                'CurrentBalance'     => $balance,
                'CurrentBalanceType' => $balanceType,
                'UpdatedBy'          => (int)$userUID,
            ], ['LedgerUID' => (int)$ledgerUID]);
            if ($res->Error) throw new Exception($res->Message ?? 'Ledger update failed.');
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function updateCustomerBalanceInCustomerTbl(int $customerUID, float $balance, string $balanceType, int $userUID): void {
        try {
            $res = $this->dbwrite_model->updateData('Customers', 'CustomerTbl', [
                'DebitCreditAmount' => $balance,
                'DebitCreditType'   => $balanceType,
                'UpdatedBy'         => (int)$userUID,
            ], ['CustomerUID' => (int)$customerUID]);
            if ($res->Error) throw new Exception($res->Message ?? 'Customer balance update failed.');
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }


    // â”€â”€ CustOpeningBalanceTbl (one row per customer, no year) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function getCustomerOpeningBalance(int $orgUID, int $customerUID): ?object {
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

    public function saveCustomerOpeningBalance(int $orgUID, int $customerUID, float $openingBalance, string $openingBalType, ?string $notes, int $userUID, bool $isNew = false): int {
        try {
            if (!$isNew) {
                $this->ReadDb->db_debug = FALSE;
                $this->ReadDb->select('OpeningBalUID');
                $this->ReadDb->from('Customers.CustOpeningBalanceTbl');
                $this->ReadDb->where(['OrgUID' => (int)$orgUID, 'CustomerUID' => (int)$customerUID, 'IsDeleted' => 0]);
                $existing = $this->ReadDb->get()->row();

                if ($existing) {
                    $this->dbwrite_model->updateData('Customers', 'CustOpeningBalanceTbl', [
                        'OpeningBalance' => (float)$openingBalance,
                        'OpeningBalType' => $openingBalType,
                        'Notes'          => $notes ?: NULL,
                        'UpdatedBy'      => (int)$userUID,
                    ], ['OpeningBalUID' => (int)$existing->OpeningBalUID]);
                    return (int)$existing->OpeningBalUID;
                }
            }

            // CustomerUID was inserted in the caller's open transaction (different connection).
            // FK check would wait 50s for the uncommitted row → disable for this insert only.
            $this->dbwrite_model->setForeignKeyChecks(false);
            $res = $this->dbwrite_model->insertData('Customers', 'CustOpeningBalanceTbl', [
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
            $this->dbwrite_model->setForeignKeyChecks(true);
            if ($res->Error) throw new Exception($res->Message ?? 'Opening balance insert failed.');
            return (int)$res->ID;

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function updateCustomerPendingBalance(int $orgUID, int $customerUID, float $pendingBalance, string $pendingBalType, int $userUID): void {
        try {
            // UPSERT: inserts if no row exists, updates if it does (handles "no change" case without duplicate key error)
            $this->dbwrite_model->getWriteDb()->query(
                "INSERT INTO Customers.CustOpeningBalanceTbl
                    (OrgUID, CustomerUID, OpeningBalance, OpeningBalType, PendingBalance, PendingBalType, IsActive, IsDeleted, CreatedBy, UpdatedBy)
                 VALUES (?, ?, 0.00, 'Debit', ?, ?, 1, 0, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    PendingBalance = VALUES(PendingBalance),
                    PendingBalType = VALUES(PendingBalType),
                    UpdatedBy      = VALUES(UpdatedBy)",
                [
                    (int)$orgUID,
                    (int)$customerUID,
                    (float)$pendingBalance,
                    $pendingBalType,
                    (int)$userUID,
                    (int)$userUID,
                ]
            );
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getCustomerOpeningBalanceSigned(int $orgUID, int $customerUID): float {
        // Returns opening balance as signed float: Debit=+, Credit=-
        $row = $this->getCustomerOpeningBalance($orgUID, $customerUID);
        if (!$row) return 0.0;
        $amt = (float)$row->OpeningBalance;
        return ($row->OpeningBalType === 'Debit') ? $amt : -$amt;
    }

    public function getCustomerDebitCreditRaw(int $customerUID): ?object {
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
    // ── CustYearOpeningBalanceTbl (year-wise opening balance snapshot) ─────────

    // $onlyIfNew=true: insert-only, preserving the year-start snapshot.
    public function saveCustomerYearOpening(int $orgUID, int $customerUID, int $financialYear, float $openingBalance, string $openingBalType, int $userUID, bool $onlyIfNew = false, bool $isNew = false): int {
        try {
            if (!$isNew) {
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
                    $this->dbwrite_model->updateData('Customers', 'CustYearOpeningBalanceTbl', [
                        'OpeningBalance' => (float)$openingBalance,
                        'OpeningBalType' => $openingBalType,
                        'UpdatedBy'      => (int)$userUID,
                    ], ['YearBalUID' => (int)$existing->YearBalUID]);
                    return (int)$existing->YearBalUID;
                }
            }

            $this->dbwrite_model->setForeignKeyChecks(false);
            $res = $this->dbwrite_model->insertData('Customers', 'CustYearOpeningBalanceTbl', [
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
            $this->dbwrite_model->setForeignKeyChecks(true);
            if ($res->Error) throw new Exception($res->Message ?? 'Year opening balance insert failed.');
            return (int)$res->ID;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getCustomerYearOpening(int $orgUID, int $customerUID, int $financialYear): ?object {
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


    public function getCustomerOnAccountPayments(int $orgUID, int $customerUID): array {
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

    // ══════════════════════════════════════════════════════════════════
    // Customer Group methods
    // ══════════════════════════════════════════════════════════════════

    public function getGroupTypes(string $module = 'customers'): array {
        try {
            $query = $this->ReadDb->query(
                "SELECT TypeName FROM Global.GroupTypesTbl WHERE Module=? AND IsActive=1 ORDER BY SortOrder",
                [$module]
            );
            $rows = $query ? $query->result_array() : [];
            if ($rows) return array_column($rows, 'TypeName');
        } catch (Exception $e) {}
        return ['Business Group', 'Branch Group', 'Family Group',
                'Corporate Group', 'Dealer Network', 'Franchise Group', 'Custom'];
    }

    public function getGroupListPaginated(int $orgUID, int $limit, int $offset, array $filter = []): object {
        try {
            $this->ReadDb->db_debug = false;

            // ── Count (no joins needed; all filters are on CG) ──
            $this->ReadDb->select('COUNT(*) AS cnt', false);
            $this->ReadDb->from('Customers.CustomerGroupTbl CG');
            $this->ReadDb->where(['CG.OrgUID' => (int)$orgUID, 'CG.IsDeleted' => 0]);
            if (!empty($filter['SearchAllData'])) {
                $s = $filter['SearchAllData'];
                $this->ReadDb->group_start();
                $this->ReadDb->like('CG.GroupName', $s);
                $this->ReadDb->or_like('CG.GroupCode', $s);
                $this->ReadDb->or_like('CG.GroupType', $s);
                $this->ReadDb->or_like('CG.Mobile', $s);
                $this->ReadDb->group_end();
            }
            if (isset($filter['IsActive']) && $filter['IsActive'] !== '') {
                $this->ReadDb->where('CG.IsActive', (int)$filter['IsActive']);
            }
            if (!empty($filter['GroupType'])) {
                $this->ReadDb->where_in('CG.GroupType', (array)$filter['GroupType']);
            }
            if (!empty($filter['CustomerUID'])) {
                $cuid = (int)$filter['CustomerUID'];
                $this->ReadDb->where("CG.GroupUID IN (SELECT GroupUID FROM Customers.CustGroupMemberTbl WHERE CustomerUID = {$cuid} AND OrgUID = {$orgUID} AND IsDeleted = 0)", null, false);
            }
            $countRow   = $this->ReadDb->get()->row();
            $totalCount = (int)($countRow->cnt ?? 0);

            // ── Step 1: Paginated groups with member count + primary name (no COB join) ──
            $this->ReadDb->select(
                'CG.GroupUID, CG.GroupCode, CG.GroupName, CG.GroupType,
                 CG.ContactPerson, CG.Mobile, CG.Email, CG.IsActive, CG.CreatedOn,
                 COUNT(CGM.CustomerUID) AS MemberCount,
                 MAX(CASE WHEN CGM.IsGroupPrimary = 1 THEN C.Name ELSE NULL END) AS PrimaryName,
                 0 AS TotalReceivable, 0 AS TotalPayable',
                false
            );
            $this->ReadDb->from('Customers.CustomerGroupTbl CG');
            $this->ReadDb->join('Customers.CustGroupMemberTbl CGM', 'CGM.GroupUID = CG.GroupUID AND CGM.OrgUID = CG.OrgUID AND CGM.IsDeleted = 0', 'left');
            $this->ReadDb->join('Customers.CustomerTbl C', 'C.CustomerUID = CGM.CustomerUID AND C.IsDeleted = 0', 'left');
            $this->ReadDb->where(['CG.OrgUID' => (int)$orgUID, 'CG.IsDeleted' => 0]);
            if (!empty($filter['SearchAllData'])) {
                $s = $filter['SearchAllData'];
                $this->ReadDb->group_start();
                $this->ReadDb->like('CG.GroupName', $s);
                $this->ReadDb->or_like('CG.GroupCode', $s);
                $this->ReadDb->or_like('CG.GroupType', $s);
                $this->ReadDb->or_like('CG.Mobile', $s);
                $this->ReadDb->group_end();
            }
            if (isset($filter['IsActive']) && $filter['IsActive'] !== '') {
                $this->ReadDb->where('CG.IsActive', (int)$filter['IsActive']);
            }
            if (!empty($filter['GroupType'])) {
                $this->ReadDb->where_in('CG.GroupType', (array)$filter['GroupType']);
            }
            if (!empty($filter['CustomerUID'])) {
                $cuid = (int)$filter['CustomerUID'];
                $this->ReadDb->where("CG.GroupUID IN (SELECT GroupUID FROM Customers.CustGroupMemberTbl WHERE CustomerUID = {$cuid} AND OrgUID = {$orgUID} AND IsDeleted = 0)", null, false);
            }
            $this->ReadDb->group_by('CG.GroupUID');
            $this->ReadDb->order_by('CG.GroupName', 'ASC');
            $this->ReadDb->limit($limit, $offset);
            $query = $this->ReadDb->get();
            $rows  = $query ? $query->result() : [];

            // ── Step 2: Balance totals scoped only to this page's group UIDs ──
            if (!empty($rows)) {
                $groupUIDs = [];
                foreach ($rows as $row) { $groupUIDs[] = (int)$row->GroupUID; }
                $placeholders = implode(',', array_fill(0, count($groupUIDs), '?'));
                $balQuery = $this->ReadDb->query(
                    "SELECT CGM.GroupUID,
                            COALESCE(SUM(CASE WHEN COB.PendingBalType = 'Debit'  AND COB.PendingBalance > 0 THEN COB.PendingBalance ELSE 0 END), 0) AS TotalReceivable,
                            COALESCE(SUM(CASE WHEN COB.PendingBalType = 'Credit' AND COB.PendingBalance > 0 THEN COB.PendingBalance ELSE 0 END), 0) AS TotalPayable
                     FROM Customers.CustGroupMemberTbl CGM
                     JOIN Customers.CustOpeningBalanceTbl COB
                          ON COB.CustomerUID = CGM.CustomerUID AND COB.OrgUID = CGM.OrgUID AND COB.IsDeleted = 0
                     WHERE CGM.OrgUID = ? AND CGM.GroupUID IN ({$placeholders}) AND CGM.IsDeleted = 0
                     GROUP BY CGM.GroupUID",
                    array_merge([(int)$orgUID], $groupUIDs)
                );
                $balMap = [];
                if ($balQuery) {
                    foreach ($balQuery->result() as $b) { $balMap[(int)$b->GroupUID] = $b; }
                }
                foreach ($rows as $row) {
                    $b = $balMap[(int)$row->GroupUID] ?? null;
                    $row->TotalReceivable = $b ? $b->TotalReceivable : 0;
                    $row->TotalPayable    = $b ? $b->TotalPayable    : 0;
                }
            }

            $result             = new stdClass();
            $result->rows       = $rows;
            $result->totalCount = $totalCount;
            return $result;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getGroupStats(int $orgUID): object {
        try {
            $this->ReadDb->db_debug = false;
            $query = $this->ReadDb->query(
                "SELECT COUNT(*) AS TotalCount, SUM(IsActive=1) AS ActiveCount, SUM(IsActive=0) AS InactiveCount,
                        (SELECT COUNT(*) FROM Customers.CustGroupMemberTbl WHERE OrgUID=? AND IsDeleted=0) AS TotalMembers
                 FROM Customers.CustomerGroupTbl WHERE OrgUID=? AND IsDeleted=0",
                [(int)$orgUID, (int)$orgUID]
            );
            return $query ? $query->row() : new stdClass();
        } catch (Exception $e) {
            return new stdClass();
        }
    }

    public function getGroupByUID(int $orgUID, int $groupUID): ?object {
        try {
            $this->ReadDb->db_debug = false;
            $this->ReadDb->select('CG.*');
            $this->ReadDb->from('Customers.CustomerGroupTbl CG');
            $this->ReadDb->where(['CG.OrgUID' => (int)$orgUID, 'CG.GroupUID' => (int)$groupUID, 'CG.IsDeleted' => 0]);
            $query = $this->ReadDb->get();
            return $query ? $query->row() : null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function getGroupMembers(int $orgUID, int $groupUID): array {
        try {
            $this->ReadDb->db_debug = false;
            $this->ReadDb->select([
                'C.CustomerUID', 'C.Name', 'C.Area', 'C.MobileNumber', 'CGM.IsGroupPrimary',
                "IFNULL(COB.PendingBalance, 0)       AS Balance",
                "IFNULL(COB.PendingBalType, 'Debit') AS BalanceType",
            ]);
            $this->ReadDb->from('Customers.CustomerTbl C');
            $this->ReadDb->join('Customers.CustGroupMemberTbl CGM', 'CGM.CustomerUID = C.CustomerUID AND CGM.OrgUID = C.OrgUID AND CGM.IsDeleted = 0', 'inner');
            $this->ReadDb->join('Customers.CustOpeningBalanceTbl COB', 'COB.CustomerUID = C.CustomerUID AND COB.OrgUID = C.OrgUID AND COB.IsDeleted = 0', 'left');
            $this->ReadDb->where(['C.OrgUID' => (int)$orgUID, 'CGM.GroupUID' => (int)$groupUID, 'C.IsDeleted' => 0]);
            $this->ReadDb->order_by('CGM.IsGroupPrimary', 'DESC');
            $this->ReadDb->order_by('C.Name', 'ASC');
            $query = $this->ReadDb->get();
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            return [];
        }
    }

    public function getGroupOverview(int $orgUID, int $groupUID): object {
        try {
            $this->ReadDb->db_debug = false;
            $query = $this->ReadDb->query(
                "SELECT COUNT(C.CustomerUID) AS MemberCount,
                        COALESCE(SUM(CASE WHEN COB.PendingBalType='Debit'  THEN COB.PendingBalance END),0) AS TotalReceivable,
                        COALESCE(SUM(CASE WHEN COB.PendingBalType='Credit' THEN COB.PendingBalance END),0) AS TotalPayable
                 FROM Customers.CustomerTbl C
                 INNER JOIN Customers.CustGroupMemberTbl CGM ON CGM.CustomerUID=C.CustomerUID AND CGM.OrgUID=C.OrgUID AND CGM.IsDeleted=0
                 LEFT JOIN Customers.CustOpeningBalanceTbl COB ON COB.CustomerUID=C.CustomerUID AND COB.OrgUID=C.OrgUID AND COB.IsDeleted=0
                 WHERE CGM.GroupUID=? AND C.OrgUID=? AND C.IsDeleted=0",
                [(int)$groupUID, (int)$orgUID]
            );
            return $query ? $query->row() : new stdClass();
        } catch (Exception $e) {
            return new stdClass();
        }
    }

    public function getGroupOutstanding(int $orgUID, int $groupUID): array {
        try {
            $this->ReadDb->db_debug = false;
            $query = $this->ReadDb->query(
                "SELECT C.CustomerUID, C.Name, C.Area, C.MobileNumber, CGM.IsGroupPrimary,
                        IFNULL(COB.PendingBalance,0)       AS Balance,
                        IFNULL(COB.PendingBalType,'Debit') AS BalanceType
                 FROM Customers.CustomerTbl C
                 INNER JOIN Customers.CustGroupMemberTbl CGM ON CGM.CustomerUID=C.CustomerUID AND CGM.OrgUID=C.OrgUID AND CGM.IsDeleted=0
                 LEFT JOIN Customers.CustOpeningBalanceTbl COB ON COB.CustomerUID=C.CustomerUID AND COB.OrgUID=C.OrgUID AND COB.IsDeleted=0
                 WHERE CGM.GroupUID=? AND C.OrgUID=? AND C.IsDeleted=0
                 ORDER BY CGM.IsGroupPrimary DESC, C.Name ASC",
                [(int)$groupUID, (int)$orgUID]
            );
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            return [];
        }
    }

    public function getCustomerGroupsForExport(int $orgUID, array $filter = []): array {
        try {
            $this->ReadDb->db_debug = false;
            $this->ReadDb->select(
                'CG.GroupUID, CG.GroupCode, CG.GroupName, CG.GroupType,
                 CG.ContactPerson, CG.Mobile, CG.Email, CG.IsActive,
                 COUNT(CGM.CustomerUID) AS MemberCount',
                false
            );
            $this->ReadDb->from('Customers.CustomerGroupTbl CG');
            $this->ReadDb->join('Customers.CustGroupMemberTbl CGM', 'CGM.GroupUID = CG.GroupUID AND CGM.OrgUID = CG.OrgUID AND CGM.IsDeleted = 0', 'left');
            $this->ReadDb->where(['CG.OrgUID' => $orgUID, 'CG.IsDeleted' => 0]);
            if (!empty($filter['SearchAllData'])) {
                $s = $filter['SearchAllData'];
                $this->ReadDb->group_start();
                $this->ReadDb->like('CG.GroupName', $s);
                $this->ReadDb->or_like('CG.GroupCode', $s);
                $this->ReadDb->or_like('CG.GroupType', $s);
                $this->ReadDb->or_like('CG.Mobile', $s);
                $this->ReadDb->group_end();
            }
            if (isset($filter['IsActive']) && $filter['IsActive'] !== '') {
                $this->ReadDb->where('CG.IsActive', (int)$filter['IsActive']);
            }
            if (!empty($filter['GroupType'])) {
                $this->ReadDb->where_in('CG.GroupType', (array)$filter['GroupType']);
            }
            if (!empty($filter['CustomerUID'])) {
                $cuid = (int)$filter['CustomerUID'];
                $this->ReadDb->where("CG.GroupUID IN (SELECT GroupUID FROM Customers.CustGroupMemberTbl WHERE CustomerUID = {$cuid} AND OrgUID = {$orgUID} AND IsDeleted = 0)", null, false);
            }
            $this->ReadDb->group_by('CG.GroupUID');
            $this->ReadDb->order_by('CG.GroupName', 'ASC');
            $query = $this->ReadDb->get();
            $rows  = $query ? $query->result() : [];

            if (!empty($rows)) {
                $groupUIDs    = array_map(fn($r) => (int)$r->GroupUID, $rows);
                $placeholders = implode(',', array_fill(0, count($groupUIDs), '?'));
                $balQuery     = $this->ReadDb->query(
                    "SELECT CGM.GroupUID,
                            COALESCE(SUM(CASE WHEN COB.PendingBalType = 'Debit'  AND COB.PendingBalance > 0 THEN COB.PendingBalance ELSE 0 END), 0) AS TotalReceivable,
                            COALESCE(SUM(CASE WHEN COB.PendingBalType = 'Credit' AND COB.PendingBalance > 0 THEN COB.PendingBalance ELSE 0 END), 0) AS TotalPayable
                     FROM Customers.CustGroupMemberTbl CGM
                     JOIN Customers.CustOpeningBalanceTbl COB
                          ON COB.CustomerUID = CGM.CustomerUID AND COB.OrgUID = CGM.OrgUID AND COB.IsDeleted = 0
                     WHERE CGM.OrgUID = ? AND CGM.GroupUID IN ({$placeholders}) AND CGM.IsDeleted = 0
                     GROUP BY CGM.GroupUID",
                    array_merge([$orgUID], $groupUIDs)
                );
                $balMap = [];
                if ($balQuery) {
                    foreach ($balQuery->result() as $b) { $balMap[(int)$b->GroupUID] = $b; }
                }
                foreach ($rows as $row) {
                    $b = $balMap[(int)$row->GroupUID] ?? null;
                    $row->TotalReceivable = $b ? (float)$b->TotalReceivable : 0.0;
                    $row->TotalPayable    = $b ? (float)$b->TotalPayable    : 0.0;
                }
            }
            return $rows;
        } catch (Exception $e) {
            return [];
        }
    }

    public function getActiveGroupsForDropdown(int $orgUID): array {
        try {
            $this->ReadDb->db_debug = false;
            $this->ReadDb->select(['GroupUID', 'GroupName', 'GroupCode', 'GroupType']);
            $this->ReadDb->from('Customers.CustomerGroupTbl');
            $this->ReadDb->where(['OrgUID' => (int)$orgUID, 'IsActive' => 1, 'IsDeleted' => 0]);
            $this->ReadDb->order_by('GroupName', 'ASC');
            $query = $this->ReadDb->get();
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            return [];
        }
    }

    public function getActiveCustomerGroupsForDropdown(int $orgUID): array {
        try {
            $this->ReadDb->db_debug = false;
            $this->ReadDb->select(['GroupUID', 'GroupName', 'GroupCode', 'GroupType']);
            $this->ReadDb->from('Customers.CustomerGroupTbl');
            $this->ReadDb->where(['OrgUID' => $orgUID, 'IsActive' => 1, 'IsDeleted' => 0]);
            $this->ReadDb->order_by('GroupName', 'ASC');
            $q = $this->ReadDb->get();
            return $q ? $q->result() : [];
        } catch (Exception $e) {
            return [];
        }
    }

    public function getGroupDropdownDataByUID(int $orgUID, int $groupUID): ?object {
        try {
            $this->ReadDb->db_debug = false;
            $this->ReadDb->select(['GroupUID', 'GroupName', 'GroupCode', 'GroupType']);
            $this->ReadDb->from('Customers.CustomerGroupTbl');
            $this->ReadDb->where(['OrgUID' => $orgUID, 'GroupUID' => $groupUID, 'IsActive' => 1, 'IsDeleted' => 0]);
            $q = $this->ReadDb->get();
            return $q ? ($q->row() ?: null) : null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function assignGroupMembers(int $orgUID, int $groupUID, array $memberUIDs, int $primaryUID, int $userUID): void {
        if (empty($memberUIDs)) return;
        $db = $this->dbwrite_model->getWriteDb();
        foreach ($memberUIDs as $custUID) {
            $isPrimary = ((int)$custUID === (int)$primaryUID) ? 1 : 0;
            $db->query(
                "INSERT INTO Customers.CustGroupMemberTbl
                    (OrgUID, CustomerUID, GroupUID, IsGroupPrimary, IsDeleted, CreatedBy, UpdatedBy)
                 VALUES (?, ?, ?, ?, 0, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    GroupUID=VALUES(GroupUID), IsGroupPrimary=VALUES(IsGroupPrimary),
                    IsDeleted=0, UpdatedBy=VALUES(UpdatedBy)",
                [(int)$orgUID, (int)$custUID, (int)$groupUID, $isPrimary, (int)$userUID, (int)$userUID]
            );
        }
    }

    public function syncGroupMembers(int $orgUID, int $groupUID, array $newMemberUIDs, int $primaryUID, int $userUID): void {
        $db = $this->dbwrite_model->getWriteDb();
        // Soft-delete members removed from the group
        $db->where('OrgUID', (int)$orgUID);
        $db->where('GroupUID', (int)$groupUID);
        $db->where('IsDeleted', 0);
        if (!empty($newMemberUIDs)) {
            $db->where_not_in('CustomerUID', array_map('intval', $newMemberUIDs));
        }
        $db->update('Customers.CustGroupMemberTbl', ['IsDeleted' => 1, 'UpdatedBy' => (int)$userUID]);
        if (!empty($newMemberUIDs)) {
            $this->assignGroupMembers($orgUID, $groupUID, $newMemberUIDs, $primaryUID, $userUID);
        }
    }

    public function unlinkAllGroupMembers(int $orgUID, int $groupUID, int $userUID): void {
        $db = $this->dbwrite_model->getWriteDb();
        $db->where(['OrgUID' => (int)$orgUID, 'GroupUID' => (int)$groupUID, 'IsDeleted' => 0]);
        $db->update('Customers.CustGroupMemberTbl', ['IsDeleted' => 1, 'UpdatedBy' => (int)$userUID]);
    }

    public function getCustomerGroupMembership(int $orgUID, int $customerUID): ?object {
        try {
            $this->ReadDb->db_debug = false;
            $this->ReadDb->select(['MemberUID', 'GroupUID', 'IsGroupPrimary']);
            $this->ReadDb->from('Customers.CustGroupMemberTbl');
            $this->ReadDb->where(['OrgUID' => (int)$orgUID, 'CustomerUID' => (int)$customerUID, 'IsDeleted' => 0]);
            $q = $this->ReadDb->get();
            return $q ? ($q->row() ?: null) : null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function saveCustomerGroupMembership(int $orgUID, int $customerUID, int $groupUID, int $isGroupPrimary, int $userUID): void {
        $db = $this->dbwrite_model->getWriteDb();
        $db->query(
            "INSERT INTO Customers.CustGroupMemberTbl
                (OrgUID, CustomerUID, GroupUID, IsGroupPrimary, IsDeleted, CreatedBy, UpdatedBy)
             VALUES (?, ?, ?, ?, 0, ?, ?)
             ON DUPLICATE KEY UPDATE
                GroupUID=VALUES(GroupUID), IsGroupPrimary=VALUES(IsGroupPrimary),
                IsDeleted=0, UpdatedBy=VALUES(UpdatedBy)",
            [(int)$orgUID, (int)$customerUID, (int)$groupUID, (int)$isGroupPrimary, (int)$userUID, (int)$userUID]
        );
    }

    public function removeCustomerFromGroup(int $orgUID, int $customerUID, int $userUID): void {
        $db = $this->dbwrite_model->getWriteDb();
        $db->where(['OrgUID' => (int)$orgUID, 'CustomerUID' => (int)$customerUID, 'IsDeleted' => 0]);
        $db->update('Customers.CustGroupMemberTbl', ['IsDeleted' => 1, 'UpdatedBy' => (int)$userUID]);
    }

    public function getCustomersInOtherGroups(int $orgUID, int $excludeGroupUID = 0): array {
        try {
            $this->ReadDb->db_debug = false;
            $this->ReadDb->select('CustomerUID');
            $this->ReadDb->from('Customers.CustGroupMemberTbl');
            $this->ReadDb->where(['OrgUID' => (int)$orgUID, 'IsDeleted' => 0]);
            if ($excludeGroupUID > 0) {
                $this->ReadDb->where('GroupUID !=', (int)$excludeGroupUID);
            }
            $q = $this->ReadDb->get();
            return $q ? array_column($q->result_array(), 'CustomerUID') : [];
        } catch (Exception $e) {
            return [];
        }
    }

    // ── Customer Attachments ──────────────────────────────────────────────────

    public function getCustomerAttachments(int $customerUID, int $orgUID): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('AttachUID, FileName, FilePath, FileSize, SortOrder, CreatedOn');
            $this->ReadDb->from('Customers.CustomerAttachmentsTbl');
            $this->ReadDb->where(['CustomerUID' => $customerUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]);
            $this->ReadDb->order_by('SortOrder', 'ASC');
            $query = $this->ReadDb->get();
            return $query ? $query->result_array() : [];
        } catch (Exception $e) {
            log_message('error', 'getCustomerAttachments failed: ' . $e->getMessage());
            return [];
        }
    }

    public function getCustomerPrimaryImage(int $customerUID, int $orgUID): ?string {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('FilePath');
            $this->ReadDb->from('Customers.CustomerAttachmentsTbl');
            $this->ReadDb->where(['CustomerUID' => $customerUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]);
            $this->ReadDb->order_by('SortOrder', 'ASC');
            $this->ReadDb->limit(1);
            $row = $this->ReadDb->get()->row();
            return $row ? $row->FilePath : null;
        } catch (Exception $e) { return null; }
    }

    // ── Customer Profile Modal methods ─────────────────────────────────────

    /**
     * Returns last 6 months of invoice totals per month for the sales bar chart.
     * @return array [['MonthLabel'=>'Aug 2026','MonthKey'=>'2026-08','Total'=>float], ...]
     */
    public function getMonthlySalesData(int $orgUID, int $customerUID): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select(
                "DATE_FORMAT(TransDate, '%b %Y') AS MonthLabel,
                 DATE_FORMAT(TransDate, '%Y-%m') AS MonthKey,
                 COALESCE(SUM(NetAmount), 0) AS Total"
            );
            $this->ReadDb->from('`Transaction`.TransactionsTbl');
            $this->ReadDb->where([
                'OrgUID'    => $orgUID,
                'PartyUID'  => $customerUID,
                'PartyType' => 'C',
                'ModuleUID' => 103,
                'IsDeleted' => 0,
            ]);
            $this->ReadDb->where_not_in('DocStatus', ['Draft', 'Cancelled', 'Rejected']);
            $this->ReadDb->where('TransDate >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)', null, false);
            $this->ReadDb->group_by("DATE_FORMAT(TransDate, '%Y-%m'), DATE_FORMAT(TransDate, '%b %Y')");
            $this->ReadDb->order_by('MonthKey', 'ASC');
            $query = $this->ReadDb->get();
            return $query ? $query->result_array() : [];
        } catch (Exception $e) {
            log_message('error', 'getMonthlySalesData: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Returns the most recent N transactions for a customer in a given module.
     */
    public function getCustomerRecentTransactions(int $orgUID, int $customerUID, int $moduleUID, int $limit = 5): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'Ts.TransUID', 'Ts.TransNumber AS TransNo', 'Ts.TransDate AS DocDate', 'Ts.NetAmount',
                'Ts.DocStatus', 'Ts.BalanceAmount',
            ]);
            $this->ReadDb->from('`Transaction`.TransactionsTbl Ts');
            $this->ReadDb->where([
                'Ts.OrgUID'    => $orgUID,
                'Ts.PartyUID'  => $customerUID,
                'Ts.PartyType' => 'C',
                'Ts.ModuleUID' => $moduleUID,
                'Ts.IsDeleted' => 0,
            ]);
            $this->ReadDb->order_by('Ts.TransUID', 'DESC');
            $this->ReadDb->limit($limit);
            $query = $this->ReadDb->get();
            return $query ? $query->result_array() : [];
        } catch (Exception $e) {
            log_message('error', 'getCustomerRecentTransactions: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Returns ledger rows (invoices + payments + returns) for statement tab.
     */
    public function getCustomerStatementData(int $orgUID, int $customerUID, string $fromDate, string $toDate): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            // Invoices (debit)
            $this->ReadDb->select("'Invoice' AS TxType, TransNumber AS RefNo, TransDate AS TxDate, NetAmount AS Debit, 0 AS Credit, DocStatus");
            $this->ReadDb->from('`Transaction`.TransactionsTbl');
            $this->ReadDb->where(['OrgUID' => $orgUID, 'PartyUID' => $customerUID, 'PartyType' => 'C', 'ModuleUID' => 103, 'IsDeleted' => 0]);
            $this->ReadDb->where_not_in('DocStatus', ['Cancelled', 'Rejected']);
            $this->ReadDb->where("TransDate BETWEEN '{$fromDate}' AND '{$toDate}'", null, false);
            $q = $this->ReadDb->get();
            $invoices = $q ? $q->result_array() : [];

            // Receipts (credit)
            $this->ReadDb->select("'Receipt' AS TxType, '' AS RefNo, DATE(PaymentDate) AS TxDate, 0 AS Debit, Amount AS Credit, 'Paid' AS DocStatus");
            $this->ReadDb->from('`Transaction`.PaymentsTbl');
            $this->ReadDb->where(['OrgUID' => $orgUID, 'PartyUID' => $customerUID, 'PartyType' => 'C', 'PaymentDirection' => 'In', 'IsDeleted' => 0, 'IsCancelled' => 0, 'IsTransferredToCreditNote' => 0]);
            $this->ReadDb->where("DATE(PaymentDate) BETWEEN '{$fromDate}' AND '{$toDate}'", null, false);
            $q = $this->ReadDb->get();
            $receipts = $q ? $q->result_array() : [];

            // Sales Returns (credit)
            $this->ReadDb->select("'Return' AS TxType, TransNumber AS RefNo, TransDate AS TxDate, 0 AS Debit, NetAmount AS Credit, DocStatus");
            $this->ReadDb->from('`Transaction`.TransactionsTbl');
            $this->ReadDb->where(['OrgUID' => $orgUID, 'PartyUID' => $customerUID, 'PartyType' => 'C', 'IsDeleted' => 0]);
            $this->ReadDb->where_in('ModuleUID', [106]);
            $this->ReadDb->where_not_in('DocStatus', ['Cancelled', 'Rejected']);
            $this->ReadDb->where("TransDate BETWEEN '{$fromDate}' AND '{$toDate}'", null, false);
            $q = $this->ReadDb->get();
            $returns = $q ? $q->result_array() : [];

            $all = array_merge($invoices, $receipts, $returns);
            usort($all, function ($a, $b) { return strcmp($a['TxDate'], $b['TxDate']); });
            return $all;
        } catch (Exception $e) {
            log_message('error', 'getCustomerStatementData: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Returns internal notes for a customer (requires CustomerNotesTbl).
     */
    public function getCustomerNotes(int $orgUID, int $customerUID): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('N.NoteUID, N.Note, N.CreatedOn, CONCAT(U.FirstName, \' \', U.LastName) AS UserName');
            $this->ReadDb->from('Customers.CustomerNotesTbl N');
            $this->ReadDb->join('Users.UserTbl U', 'U.UserUID = N.UserUID', 'left');
            $this->ReadDb->where(['N.OrgUID' => $orgUID, 'N.CustomerUID' => $customerUID, 'N.IsDeleted' => 0]);
            $this->ReadDb->order_by('N.NoteUID', 'DESC');
            $query = $this->ReadDb->get();
            return $query ? $query->result_array() : [];
        } catch (Exception $e) {
            log_message('error', 'getCustomerNotes: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Inserts an internal note for a customer.
     */
    public function saveCustomerNote(int $orgUID, int $customerUID, string $note, int $userUID): void {
        $this->load->model('dbwrite_model');
        $res = $this->dbwrite_model->insertData('Customers', 'CustomerNotesTbl', [
            'OrgUID'      => $orgUID,
            'CustomerUID' => $customerUID,
            'Note'        => $note,
            'UserUID'     => $userUID,
            'IsDeleted'   => 0,
            'CreatedOn'   => date('Y-m-d H:i:s'),
        ]);
        if ($res->Error) throw new Exception($res->Message ?? 'Note insert failed.');
    }

    // ── Innovative additions for Customer Profile ──────────────────────────

    /**
     * Single-query aggregation that replaces getCustomerTotalInvoiced, getCustomerTotalReturned,
     * getCustomerHealthData, and getCustomerAgeing — all in one pass over TransactionsTbl.
     * @return array{TotalInvoiced:float,TotalReturned:float,HealthData:array,Ageing:array}
     */
    public function getCustomerFinancialSummary(int $orgUID, int $customerUID): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $result = $this->ReadDb->query(
                "SELECT
                    COALESCE(SUM(CASE WHEN ModuleUID = 103
                        AND DocStatus NOT IN ('Draft','Cancelled','Rejected')
                        THEN NetAmount ELSE 0 END), 0)                          AS TotalInvoiced,
                    COALESCE(SUM(CASE WHEN ModuleUID = 103
                        AND DocStatus NOT IN ('Draft','Cancelled','Rejected')
                        THEN (NetAmount - BalanceAmount) ELSE 0 END), 0)        AS TotalCollected,
                    COALESCE(SUM(CASE WHEN ModuleUID IN (106, 107)
                        AND DocStatus NOT IN ('Cancelled','Rejected')
                        THEN COALESCE(BalanceAmount, NetAmount) ELSE 0 END), 0) AS TotalReturned,
                    COALESCE(SUM(CASE WHEN ModuleUID = 103
                        AND DocStatus IN ('Issued','Partial')
                        AND BalanceAmount > 0
                        AND TransDate < DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                        THEN 1 ELSE 0 END), 0)                                  AS OverdueCount,
                    MAX(CASE WHEN DocStatus NOT IN ('Draft','Cancelled','Rejected')
                        THEN TransDate ELSE NULL END)                           AS LastTxDate,
                    COALESCE(SUM(CASE WHEN ModuleUID = 103
                        AND DocStatus IN ('Issued','Partial') AND BalanceAmount > 0
                        AND DATEDIFF(CURDATE(), TransDate) BETWEEN 0  AND 30
                        THEN BalanceAmount ELSE 0 END), 0)                      AS Bucket_0_30,
                    COALESCE(SUM(CASE WHEN ModuleUID = 103
                        AND DocStatus IN ('Issued','Partial') AND BalanceAmount > 0
                        AND DATEDIFF(CURDATE(), TransDate) BETWEEN 31 AND 60
                        THEN BalanceAmount ELSE 0 END), 0)                      AS Bucket_31_60,
                    COALESCE(SUM(CASE WHEN ModuleUID = 103
                        AND DocStatus IN ('Issued','Partial') AND BalanceAmount > 0
                        AND DATEDIFF(CURDATE(), TransDate) BETWEEN 61 AND 90
                        THEN BalanceAmount ELSE 0 END), 0)                      AS Bucket_61_90,
                    COALESCE(SUM(CASE WHEN ModuleUID = 103
                        AND DocStatus IN ('Issued','Partial') AND BalanceAmount > 0
                        AND DATEDIFF(CURDATE(), TransDate) > 90
                        THEN BalanceAmount ELSE 0 END), 0)                      AS Bucket_90Plus
                 FROM `Transaction`.TransactionsTbl
                 WHERE OrgUID = ? AND PartyUID = ? AND PartyType = 'C' AND IsDeleted = 0",
                [(int)$orgUID, (int)$customerUID]
            );
            $row = $result ? $result->row() : null;

            $totalInvoiced   = (float) ($row->TotalInvoiced  ?? 0);
            $totalCollected  = (float) ($row->TotalCollected ?? 0);
            $overdueCount    = (int)   ($row->OverdueCount   ?? 0);
            $lastTxDate      = $row->LastTxDate ?? null;
            $daysSinceLastTx = $lastTxDate
                ? (int) floor((time() - strtotime($lastTxDate)) / 86400)
                : null;
            $collectionRate  = $totalInvoiced > 0
                ? round(($totalCollected / $totalInvoiced) * 100, 1)
                : 100.0;

            if ($collectionRate >= 90.0 && $overdueCount === 0) {
                $healthScore = 'Good Payer'; $healthColor = 'success';
            } elseif ($overdueCount >= 3 || $collectionRate < 50.0) {
                $healthScore = 'At Risk';    $healthColor = 'danger';
            } else {
                $healthScore = 'Slow Payer'; $healthColor = 'warning';
            }

            return [
                'TotalInvoiced' => $totalInvoiced,
                'TotalReturned' => (float) ($row->TotalReturned ?? 0),
                'HealthData'    => [
                    'CollectionRate'  => $collectionRate,
                    'OverdueCount'    => $overdueCount,
                    'HealthScore'     => $healthScore,
                    'HealthColor'     => $healthColor,
                    'LastTxDate'      => $lastTxDate,
                    'DaysSinceLastTx' => $daysSinceLastTx,
                ],
                'Ageing' => [
                    'Bucket_0_30'  => (float) ($row->Bucket_0_30  ?? 0),
                    'Bucket_31_60' => (float) ($row->Bucket_31_60 ?? 0),
                    'Bucket_61_90' => (float) ($row->Bucket_61_90 ?? 0),
                    'Bucket_90Plus'=> (float) ($row->Bucket_90Plus ?? 0),
                ],
            ];
        } catch (Exception $e) {
            log_message('error', 'getCustomerFinancialSummary: ' . $e->getMessage());
            return [
                'TotalInvoiced' => 0.0,
                'TotalReturned' => 0.0,
                'HealthData'    => [
                    'CollectionRate' => 100.0, 'OverdueCount' => 0,
                    'HealthScore'    => 'Good Payer', 'HealthColor' => 'success',
                    'LastTxDate'     => null, 'DaysSinceLastTx' => null,
                ],
                'Ageing'        => ['Bucket_0_30' => 0.0, 'Bucket_31_60' => 0.0, 'Bucket_61_90' => 0.0, 'Bucket_90Plus' => 0.0],
            ];
        }
    }

    /**
     * Returns health metrics: collection rate, overdue count, last tx date, health score.
     * @return array{CollectionRate:float,OverdueCount:int,HealthScore:string,HealthColor:string,LastTxDate:string|null,DaysSinceLastTx:int|null}
     * @deprecated Use getCustomerFinancialSummary()['HealthData'] — kept for direct calls outside overview tab.
     */
    public function getCustomerHealthData(int $orgUID, int $customerUID): array {
        try {
            $this->ReadDb->db_debug = FALSE;

            $finResult = $this->ReadDb->query(
                "SELECT
                    COALESCE(SUM(NetAmount), 0)                 AS TotalInvoiced,
                    COALESCE(SUM(NetAmount - BalanceAmount), 0) AS TotalCollected
                 FROM `Transaction`.TransactionsTbl
                 WHERE OrgUID = ? AND PartyUID = ? AND PartyType = 'C'
                 AND ModuleUID = 103 AND IsDeleted = 0
                 AND DocStatus NOT IN ('Draft','Cancelled','Rejected')",
                [(int)$orgUID, (int)$customerUID]
            );
            $finRow         = $finResult ? $finResult->row() : null;
            $totalInvoiced  = (float) ($finRow->TotalInvoiced  ?? 0);
            $totalCollected = (float) ($finRow->TotalCollected ?? 0);
            $collectionRate = $totalInvoiced > 0
                ? round(($totalCollected / $totalInvoiced) * 100, 1)
                : 100.0;

            $overdueResult = $this->ReadDb->query(
                "SELECT COUNT(*) AS OverdueCount
                 FROM `Transaction`.TransactionsTbl
                 WHERE OrgUID = ? AND PartyUID = ? AND PartyType = 'C'
                 AND ModuleUID = 103 AND IsDeleted = 0
                 AND DocStatus IN ('Issued','Partial')
                 AND BalanceAmount > 0
                 AND TransDate < DATE_SUB(CURDATE(), INTERVAL 30 DAY)",
                [(int)$orgUID, (int)$customerUID]
            );
            $overdueCount = (int) ($overdueResult ? ($overdueResult->row()->OverdueCount ?? 0) : 0);

            $lastTxResult = $this->ReadDb->query(
                "SELECT MAX(TransDate) AS LastTxDate
                 FROM `Transaction`.TransactionsTbl
                 WHERE OrgUID = ? AND PartyUID = ? AND PartyType = 'C' AND IsDeleted = 0
                 AND DocStatus NOT IN ('Draft','Cancelled','Rejected')",
                [(int)$orgUID, (int)$customerUID]
            );
            $lastTxDate      = $lastTxResult ? ($lastTxResult->row()->LastTxDate ?? null) : null;
            $daysSinceLastTx = $lastTxDate
                ? (int) floor((time() - strtotime($lastTxDate)) / 86400)
                : null;

            if ($collectionRate >= 90.0 && $overdueCount === 0) {
                $healthScore = 'Good Payer';
                $healthColor = 'success';
            } elseif ($overdueCount >= 3 || $collectionRate < 50.0) {
                $healthScore = 'At Risk';
                $healthColor = 'danger';
            } else {
                $healthScore = 'Slow Payer';
                $healthColor = 'warning';
            }

            return [
                'CollectionRate'  => $collectionRate,
                'OverdueCount'    => $overdueCount,
                'HealthScore'     => $healthScore,
                'HealthColor'     => $healthColor,
                'LastTxDate'      => $lastTxDate,
                'DaysSinceLastTx' => $daysSinceLastTx,
            ];
        } catch (Exception $e) {
            log_message('error', 'getCustomerHealthData: ' . $e->getMessage());
            return [
                'CollectionRate'  => 100.0,
                'OverdueCount'    => 0,
                'HealthScore'     => 'Good Payer',
                'HealthColor'     => 'success',
                'LastTxDate'      => null,
                'DaysSinceLastTx' => null,
            ];
        }
    }

    /**
     * Returns outstanding invoice amounts bucketed by age (0-30, 31-60, 61-90, 90+ days).
     * @return array{Bucket_0_30:float,Bucket_31_60:float,Bucket_61_90:float,Bucket_90Plus:float}
     */
    public function getCustomerAgeing(int $orgUID, int $customerUID): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $result = $this->ReadDb->query(
                "SELECT
                    COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), TransDate) BETWEEN 0  AND 30 THEN BalanceAmount ELSE 0 END), 0) AS Bucket_0_30,
                    COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), TransDate) BETWEEN 31 AND 60 THEN BalanceAmount ELSE 0 END), 0) AS Bucket_31_60,
                    COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), TransDate) BETWEEN 61 AND 90 THEN BalanceAmount ELSE 0 END), 0) AS Bucket_61_90,
                    COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), TransDate) > 90             THEN BalanceAmount ELSE 0 END), 0) AS Bucket_90Plus
                 FROM `Transaction`.TransactionsTbl
                 WHERE OrgUID = ? AND PartyUID = ? AND PartyType = 'C'
                 AND ModuleUID = 103 AND IsDeleted = 0
                 AND DocStatus IN ('Issued','Partial')
                 AND BalanceAmount > 0",
                [(int)$orgUID, (int)$customerUID]
            );
            $row = $result ? $result->row() : null;
            return [
                'Bucket_0_30'  => (float) ($row->Bucket_0_30  ?? 0),
                'Bucket_31_60' => (float) ($row->Bucket_31_60 ?? 0),
                'Bucket_61_90' => (float) ($row->Bucket_61_90 ?? 0),
                'Bucket_90Plus'=> (float) ($row->Bucket_90Plus ?? 0),
            ];
        } catch (Exception $e) {
            log_message('error', 'getCustomerAgeing: ' . $e->getMessage());
            return ['Bucket_0_30' => 0.0, 'Bucket_31_60' => 0.0, 'Bucket_61_90' => 0.0, 'Bucket_90Plus' => 0.0];
        }
    }

    /**
     * Returns lifetime revenue, estimated COGS (current purchase price × qty), gross profit and margin %.
     * Pass $revenue from getCustomerFinancialSummary()['TotalInvoiced'] to skip the redundant revenue SELECT.
     * Note: COGS uses current ProductTbl.PurchasePrice — not the price at the time of the invoice.
     * @return array{Revenue:float,COGS:float,Profit:float,Margin:float}
     */
    public function getCustomerProfitability(int $orgUID, int $customerUID, float $revenue = -1.0): array {
        try {
            $this->ReadDb->db_debug = FALSE;

            if ($revenue < 0.0) {
                $revResult = $this->ReadDb->query(
                    "SELECT COALESCE(SUM(NetAmount), 0) AS Revenue
                     FROM `Transaction`.TransactionsTbl
                     WHERE OrgUID = ? AND PartyUID = ? AND PartyType = 'C'
                     AND ModuleUID = 103 AND IsDeleted = 0
                     AND DocStatus NOT IN ('Draft','Cancelled','Rejected')",
                    [(int)$orgUID, (int)$customerUID]
                );
                $revenue = (float) ($revResult ? $revResult->row()->Revenue : 0);
            }

            $cogsResult = $this->ReadDb->query(
                "SELECT COALESCE(SUM(Tp.Quantity * P.PurchasePrice), 0) AS COGS
                 FROM `Transaction`.TransactionsTbl Ts
                 JOIN `Transaction`.TransProductsTbl Tp ON Tp.TransUID = Ts.TransUID AND Tp.IsDeleted = 0
                 LEFT JOIN `Products`.ProductTbl P ON P.ProductUID = Tp.ProductUID AND P.IsDeleted = 0
                 WHERE Ts.OrgUID = ? AND Ts.PartyUID = ? AND Ts.PartyType = 'C'
                 AND Ts.ModuleUID = 103 AND Ts.IsDeleted = 0
                 AND Ts.DocStatus NOT IN ('Draft','Cancelled','Rejected')",
                [(int)$orgUID, (int)$customerUID]
            );
            $cogs   = (float) ($cogsResult ? $cogsResult->row()->COGS : 0);
            $profit = $revenue - $cogs;
            $margin = $revenue > 0 ? round(($profit / $revenue) * 100, 1) : 0.0;

            return [
                'Revenue' => $revenue,
                'COGS'    => $cogs,
                'Profit'  => $profit,
                'Margin'  => $margin,
            ];
        } catch (Exception $e) {
            log_message('error', 'getCustomerProfitability: ' . $e->getMessage());
            return ['Revenue' => 0.0, 'COGS' => 0.0, 'Profit' => 0.0, 'Margin' => 0.0];
        }
    }

    // ── Credit Settings (customer sequence numbers) ───────────────────────────

    /**
     * Returns 2-digit financial year start year based on org's FY start month and timezone.
     * e.g. FYStartMonth=4 (April), date=Aug 2026 → FY starts 2026 → returns 26
     *      FYStartMonth=4 (April), date=Feb 2026 → FY starts 2025 → returns 25
     * @param int    $fyStartMonth  1–12, from GenSettings->FYStartMonth
     * @param string $timezone      IANA timezone string, from JwtData->User->Timezone
     * @return int
     */
    private function _calcFYYear(int $fyStartMonth, string $timezone): int {
        try {
            $now = new DateTime('now', new DateTimeZone($timezone ?: 'UTC'));
        } catch (Exception $e) {
            $now = new DateTime('now');
        }
        $month = (int) $now->format('n');
        $year  = (int) $now->format('Y');
        return (($month >= $fyStartMonth) ? $year : $year - 1) % 100;
    }

    /**
     * @param int $orgUID
     * @return object|null
     */
    public function getCreditSettings(int $orgUID): ?object {
        try {
            $this->ReadDb->db_debug = FALSE;
            return $this->ReadDb->get_where('Settings.OrgCreditSettingsTbl', ['OrgUID' => $orgUID])->row() ?: null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Returns a credit-settings row guaranteed to reflect the current financial year.
     * — No row: inserts fresh row with correct FY year and pre-formatted next numbers.
     * — Row exists, FY year matches: returns as-is.
     * — Row exists, FY year stale: corrects DB (seq=1, new year, new CustomerNextNumber)
     *   using an optimistic WHERE so concurrent callers don't double-reset.
     * @param int    $orgUID
     * @param int    $userUID
     * @param int    $fyStartMonth  from GenSettings->FYStartMonth
     * @param string $timezone      from JwtData->User->Timezone
     * @return object|null
     */
    public function getOrInitCreditSettings(int $orgUID, int $userUID, int $fyStartMonth = 4, string $timezone = 'UTC'): ?object {
        $existing = $this->getCreditSettings($orgUID);
        $fyYear2  = $this->_calcFYYear($fyStartMonth, $timezone);
        $yrPad    = str_pad($fyYear2, 2, '0', STR_PAD_LEFT);

        if ($existing) {
            if ((int) $existing->CustomerSeqYear !== $fyYear2) {
                // Financial year rolled over — reset Customer sequence for new FY.
                // Optimistic WHERE on CustomerSeqYear: only one concurrent caller wins;
                // losers fall through to getCreditSettings() and get the corrected row.
                $this->load->model('dbwrite_model');
                $db = $this->dbwrite_model->getWriteDb();
                $db->db_debug = FALSE;
                $db->where('OrgUID',          $orgUID)
                   ->where('CustomerSeqYear', (int) $existing->CustomerSeqYear)
                   ->update('Settings.OrgCreditSettingsTbl', [
                       'CustomerSeq'        => 1,
                       'CustomerSeqYear'    => $fyYear2,
                       'CustomerNextNumber' => 'C-' . $yrPad . '0001',
                       'UpdatedAt'          => date('Y-m-d H:i:s'),
                   ]);
                return $this->getCreditSettings($orgUID);
            }
            if (empty($existing->CustomerNextNumber)) {
                // Row exists but CustomerNextNumber is NULL — column was added after row was
                // created (ALTER TABLE migration). Backfill next number AND correct the year
                // so claimNextCustomerNumber never sees a stale CustomerSeqYear mismatch.
                $nextNum = 'C-' . $yrPad . str_pad((int) $existing->CustomerSeq, 4, '0', STR_PAD_LEFT);
                $this->load->model('dbwrite_model');
                $db = $this->dbwrite_model->getWriteDb();
                $db->db_debug = FALSE;
                $db->where('OrgUID', $orgUID)
                   ->update('Settings.OrgCreditSettingsTbl', [
                       'CustomerNextNumber' => $nextNum,
                       'CustomerSeqYear'    => $fyYear2,
                       'UpdatedAt'          => date('Y-m-d H:i:s'),
                   ]);
                return $this->getCreditSettings($orgUID);
            }
            return $existing;
        }

        // No row yet — insert with correct FY year and pre-formatted next numbers.
        $this->load->model('dbwrite_model');
        $this->dbwrite_model->insertData('Settings', 'OrgCreditSettingsTbl', [
            'OrgUID'             => $orgUID,
            'CustomerSeq'        => 1,
            'CustomerSeqYear'    => $fyYear2,
            'CustomerNextNumber' => 'C-' . $yrPad . '0001',
            'VendorSeq'          => 1,
            'VendorSeqYear'      => $fyYear2,
            'VendorNextNumber'   => 'V-' . $yrPad . '0001',
            'GstinPoints'        => 0,
        ]);
        return $this->getCreditSettings($orgUID);
    }

    /**
     * Atomically claims the next customer number for $orgUID.
     * Handles financial year rollover: if the stored FY year differs from the
     * current FY year (calculated from $fyStartMonth + $timezone), seq resets to 1
     * for the new year automatically — no manual intervention ever needed.
     * Uses a 5-retry optimistic lock (WHERE CustomerSeq + CustomerSeqYear) so
     * concurrent saves cannot produce duplicate numbers.
     * Returns ['claimed' => 'C-260001', 'next' => 'C-260002'] on success, null on failure.
     * @param int    $orgUID
     * @param int    $fyStartMonth  from GenSettings->FYStartMonth
     * @param string $timezone      from JwtData->User->Timezone
     * @return array{claimed:string,next:string}|null
     */
    public function claimNextCustomerNumber(int $orgUID, int $fyStartMonth, string $timezone): ?array {
        try {
            $this->load->model('dbwrite_model');
            $db = $this->dbwrite_model->getWriteDb();

            for ($attempt = 1; $attempt <= 5; $attempt++) {
                $row = $this->getCreditSettings($orgUID);
                if (!$row) return null;

                $currentFYYear = $this->_calcFYYear($fyStartMonth, $timezone);
                $storedFYYear  = (int) $row->CustomerSeqYear;
                $storedSeq     = (int) $row->CustomerSeq;
                $yrPad         = str_pad($currentFYYear, 2, '0', STR_PAD_LEFT);

                // Determine if this is a real 1-year FY rollover (e.g. 25→26, or 99→0)
                // vs stale initialisation data (e.g. 93 stored when current year is 26).
                // Only a difference of exactly 1 (mod 100) counts as a genuine rollover.
                $isLegitRollover = ($storedFYYear === (($currentFYYear - 1 + 100) % 100));

                if ($currentFYYear === $storedFYYear) {
                    $claimedSeq = $storedSeq;
                    $nextSeq    = $storedSeq + 1;
                } elseif ($isLegitRollover) {
                    // New financial year: first customer gets 0001
                    $claimedSeq = 1;
                    $nextSeq    = 2;
                } else {
                    // Stale year (e.g. 93 vs 26): treat as same-year continuation,
                    // align year to current FY but keep the existing seq intact.
                    $claimedSeq = $storedSeq;
                    $nextSeq    = $storedSeq + 1;
                }

                $claimedNum = 'C-' . $yrPad . str_pad($claimedSeq, 4, '0', STR_PAD_LEFT);
                $nextNum    = 'C-' . $yrPad . str_pad($nextSeq,    4, '0', STR_PAD_LEFT);

                $db->db_debug = FALSE;
                $db->where('OrgUID',          $orgUID)
                   ->where('CustomerSeq',     $storedSeq)
                   ->where('CustomerSeqYear', $storedFYYear)
                   ->update('Settings.OrgCreditSettingsTbl', [
                       'CustomerSeq'        => $nextSeq,
                       'CustomerSeqYear'    => $currentFYYear,
                       'CustomerNextNumber' => $nextNum,
                       'UpdatedAt'          => date('Y-m-d H:i:s'),
                   ]);

                if ($db->affected_rows() === 1) {
                    return ['claimed' => $claimedNum, 'next' => $nextNum];
                }
                // Lost optimistic race — retry with fresh row values
            }
            return null;
        } catch (Exception $e) {
            log_message('error', 'claimNextCustomerNumber: ' . $e->getMessage());
            return null;
        }
    }
}
