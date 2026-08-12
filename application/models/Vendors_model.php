<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Vendors_model extends CI_Model {

    private $EndReturnData;
    private $ReadDb;

    function __construct() {
        parent::__construct();
        $this->ReadDb = $this->load->database('ReadDB', TRUE);
    }

    public function getVendors(array $FilterArray): array {

        $this->EndReturnData = new StdClass();
        try {

            $this->ReadDb->db_debug = FALSE;

            $select_ary = array(
                'Vendors.VendorUID AS VendorUID',
                'Vendors.SalutationUID AS SalutationUID',
                'Vendors.OrgUID AS OrgUID',
                'Vendors.Name AS Name',
                'Vendors.Area AS Area',
                'Vendors.CountryISO2 as CountryISO2',
                'Vendors.CountryCode as CountryCode',
                'Vendors.MobileNumber as MobileNumber',
                'Vendors.EmailAddress as EmailAddress',
                'Vendors.CustomerUID as CustomerUID',
                'Vendors.GSTIN as GSTIN',
                'Vendors.GSTINValidated as GSTINValidated',
                'Vendors.VendorNumber as VendorNumber',
                'Vendors.CompanyName as CompanyName',
                'Vendors.WorkPhone as WorkPhone',
                'Vendors.LandlineNumber as LandlineNumber',
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

    public function getVendorBankInfo(array $FilterArray): array {

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

    public function getVendorAddress(array $FilterArray): array {

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


    public function getVendorListPaginated(int $orgUID, int $limit, int $offset, array $filter = []): object {

        try {
            $this->ReadDb->db_debug = FALSE;

            $baseWhere = ['Vendors.IsDeleted' => 0, 'Vendors.OrgUID' => $orgUID];

            // ToCollect / ToPay filter — subquery, applied to both count and data queries
            $balanceSubquery = null;
            if (!empty($filter['BalanceType'])) {
                $balType        = ($filter['BalanceType'] === 'Credit') ? 'Credit' : 'Debit';
                $balanceSubquery = "Vendors.VendorUID IN (
                    SELECT VendorUID FROM Vendors.VendOpeningBalanceTbl
                    WHERE OrgUID = {$orgUID} AND PendingBalType = '{$balType}'
                      AND PendingBalance > 0 AND IsDeleted = 0
                )";
            }

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
                $this->ReadDb->where('Vendors.IsActive', (int) $filter['IsActive']);
            }
            if (!empty($filter['UpdatedByUIDs'])) {
                $uids = array_filter(array_map('intval', (array)$filter['UpdatedByUIDs']));
                if (!empty($uids)) $this->ReadDb->where_in('Vendors.UpdatedBy', $uids);
            }
            if ($balanceSubquery) $this->ReadDb->where($balanceSubquery, null, false);
            $cntQuery = $this->ReadDb->get();
            if (!$cntQuery) throw new Exception($this->ReadDb->error()['message'] ?? 'DB error');
            $totalCount = (int) $cntQuery->row()->cnt;

            // Data query — balance via correlated subqueries to prevent row multiplication
            // from multi-ledger ELM rows (ELM+COA JOINs caused N rows per vendor if >1 ledger)
            $this->ReadDb->select([
                'Vendors.VendorUID AS TablePrimaryUID',
                'Vendors.VendorUID AS VendorUID',
                'Vendors.OrgUID AS OrgUID',
                'Vendors.SalutationUID AS SalutationUID',
                'Sal.SalutationName AS SalutationName',
                'Vendors.Name AS Name',
                'Vendors.Area AS Area',
                'Vendors.CountryISO2 AS CountryISO2',
                'Vendors.CountryCode AS CountryCode',
                'Vendors.MobileNumber AS MobileNumber',
                'Vendors.EmailAddress AS EmailAddress',
                'Vendors.GSTIN AS GSTIN',
                'Vendors.GSTINValidated AS GSTINValidated',
                'Vendors.VendorNumber AS VendorNumber',
                'Vendors.CompanyName AS CompanyName',
                'Vendors.WorkPhone AS WorkPhone',
                'Vendors.LandlineNumber AS LandlineNumber',
                'Vendors.DebitCreditType AS DebitCreditType',
                'Vendors.DebitCreditAmount AS DebitCreditAmount',
                'Vendors.Image AS Image',
                'Vendors.PANNumber AS PANNumber',
                'Vendors.Notes AS Notes',
                'Vendors.Tags AS Tags',
                'Vendors.CCEmails AS CCEmails',
                'Vendors.IsActive AS IsActive',
                'Vendors.CreatedOn AS CreatedOn',
                'Vendors.UpdatedOn AS UpdatedOn',
                "CONCAT(User.FirstName, ' ', User.LastName) AS UpdatedBy",
            ]);
            $this->ReadDb->select(
                "IFNULL((SELECT COA2.CurrentBalance FROM Accounting.EntityLedgerMap ELM2 JOIN Accounting.ChartOfAccounts COA2 ON COA2.LedgerUID = ELM2.LedgerUID AND COA2.IsDeleted = 0 WHERE ELM2.VendorUID = Vendors.VendorUID AND ELM2.EntityType = 'Vendor' AND ELM2.IsDeleted = 0 LIMIT 1), 0.00) AS ClosingBalance,
                 IFNULL((SELECT COA2.CurrentBalanceType FROM Accounting.EntityLedgerMap ELM2 JOIN Accounting.ChartOfAccounts COA2 ON COA2.LedgerUID = ELM2.LedgerUID AND COA2.IsDeleted = 0 WHERE ELM2.VendorUID = Vendors.VendorUID AND ELM2.EntityType = 'Vendor' AND ELM2.IsDeleted = 0 LIMIT 1), 'Credit') AS ClosingBalanceType",
                false
            );
            $this->ReadDb->from('Vendors.VendorTbl as Vendors');
            $this->ReadDb->join('Users.UserTbl as User', 'User.UserUID = Vendors.UpdatedBy', 'left');
            $this->ReadDb->join(
                'Global.SalutationTbl as Sal',
                'Sal.SalutationUID = Vendors.SalutationUID AND Sal.IsDeleted = 0',
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
            // UpdatedBy filter
            if (!empty($filter['UpdatedByUIDs'])) {
                $uids = array_filter(array_map('intval', (array)$filter['UpdatedByUIDs']));
                if (!empty($uids)) $this->ReadDb->where_in('Vendors.UpdatedBy', $uids);
            }
            if ($balanceSubquery) $this->ReadDb->where($balanceSubquery, null, false);
            // Sorting
            if (!empty($filter['NameSorting'])) {
                $this->ReadDb->order_by('Vendors.Name', (int)$filter['NameSorting'] === 1 ? 'ASC' : 'DESC');
            } elseif (!empty($filter['AreaSorting'])) {
                $this->ReadDb->order_by('Vendors.Area', (int)$filter['AreaSorting'] === 1 ? 'ASC' : 'DESC');
            } elseif (!empty($filter['BalanceSorting'])) {
                $this->ReadDb->order_by('ClosingBalance', (int)$filter['BalanceSorting'] === 1 ? 'ASC' : 'DESC');
            } else {
                $this->ReadDb->order_by('Vendors.VendorUID', 'DESC');
            }
            if ($limit > 0) { $this->ReadDb->limit($limit, $offset); }
            $dataQuery = $this->ReadDb->get();
            if (!$dataQuery) throw new Exception($this->ReadDb->error()['message'] ?? 'DB error');

            $rows = $dataQuery->result();

            // Batch-fetch first attachment + full gallery per vendor for list view
            if (!empty($rows)) {
                $vendUIDs = array_column((array)$rows, 'VendorUID');
                $cdnUrl   = rtrim(getenv('FILE_UPLOAD') == 'amazonaws' ? getenv('CDN_URL') : getenv('CFLARE_R2_CDN'), '/');
                $ph       = implode(',', array_fill(0, count($vendUIDs), '?'));
                $attQ     = $this->ReadDb->query(
                    "SELECT VendorUID, FilePath, FileName FROM Vendors.VendorAttachmentsTbl
                      WHERE VendorUID IN ({$ph}) AND IsDeleted = 0
                      ORDER BY VendorUID, SortOrder ASC",
                    $vendUIDs
                );
                $primaryMap = [];
                $galleryMap = [];
                if ($attQ) {
                    foreach ($attQ->result() as $att) {
                        $uid = (int)$att->VendorUID;
                        $entry = ['url' => $cdnUrl . '/' . ltrim($att->FilePath, '/'), 'name' => $att->FileName];
                        if (!isset($primaryMap[$uid])) $primaryMap[$uid] = $entry;
                        $galleryMap[$uid][] = $entry;
                    }
                }
                foreach ($rows as $row) {
                    $uid = (int)$row->VendorUID;
                    $row->PrimaryImageUrl = isset($primaryMap[$uid]) ? $primaryMap[$uid]['url'] : null;
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

    public function getVendorUIDsByFilter(int $orgUID, array $filter = []): array {

        try {
            $this->ReadDb->db_debug = FALSE;

            $this->ReadDb->select('Vendors.VendorUID');
            $this->ReadDb->from('Vendors.VendorTbl as Vendors');
            $this->ReadDb->where(['Vendors.IsDeleted' => 0, 'Vendors.OrgUID' => $orgUID]);

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
                $this->ReadDb->where('Vendors.IsActive', (int) $filter['IsActive']);
            }
            if (!empty($filter['BalanceType'])) {
                $balType = ($filter['BalanceType'] === 'Credit') ? 'Credit' : 'Debit';
                $this->ReadDb->where(
                    "Vendors.VendorUID IN (SELECT VendorUID FROM Vendors.VendOpeningBalanceTbl WHERE OrgUID = {$orgUID} AND PendingBalType = '{$balType}' AND PendingBalance > 0 AND IsDeleted = 0)",
                    null, false
                );
            }
            if (!empty($filter['UpdatedByUIDs'])) {
                $uids = array_filter(array_map('intval', (array)$filter['UpdatedByUIDs']));
                if (!empty($uids)) $this->ReadDb->where_in('Vendors.UpdatedBy', $uids);
            }

            $q = $this->ReadDb->get();
            if (!$q) return [];

            return array_column($q->result_array(), 'VendorUID');

        } catch (Exception $e) {
            return [];
        }

    }

    public function getVendorStats(int $OrgUID): object {

        try {
            $this->ReadDb->db_debug = FALSE;

            // Range conditions so index on CreatedOn is usable (not MONTH()/YEAR())
            $thisMonthStart = date('Y-m-01');
            $nextMonthStart = date('Y-m-01', strtotime('+1 month'));
            $lastMonthStart = date('Y-m-01', strtotime('-1 month'));
            $fyStart        = (date('n') >= 4)
                              ? date('Y') . '-04-01'
                              : (date('Y') - 1) . '-04-01';

            $this->ReadDb->select([
                'COUNT(V.VendorUID) AS TotalCount',
                'SUM(CASE WHEN V.IsActive = 1 THEN 1 ELSE 0 END) AS ActiveCount',
                "SUM(CASE WHEN V.CreatedOn >= '{$thisMonthStart}' AND V.CreatedOn < '{$nextMonthStart}' THEN 1 ELSE 0 END) AS MonthCount",
                "SUM(CASE WHEN V.CreatedOn >= '{$fyStart}' THEN 1 ELSE 0 END) AS FYCount",
                "SUM(CASE WHEN V.CreatedOn >= '{$lastMonthStart}' AND V.CreatedOn < '{$thisMonthStart}' THEN 1 ELSE 0 END) AS LastMonthCount",
                // To Collect: vendors who owe us (Debit balance — advance paid to vendor)
                "SUM(CASE WHEN VOB.PendingBalType = 'Debit' AND VOB.PendingBalance > 0 THEN 1 ELSE 0 END) AS ToCollectCount",
                "COALESCE(SUM(CASE WHEN VOB.PendingBalType = 'Debit' AND VOB.PendingBalance > 0 THEN VOB.PendingBalance ELSE 0 END), 0) AS ToCollectAmount",
                // To Pay: we owe vendors (Credit balance — standard payables)
                "SUM(CASE WHEN VOB.PendingBalType = 'Credit' AND VOB.PendingBalance > 0 THEN 1 ELSE 0 END) AS ToPayCount",
                "COALESCE(SUM(CASE WHEN VOB.PendingBalType = 'Credit' AND VOB.PendingBalance > 0 THEN VOB.PendingBalance ELSE 0 END), 0) AS ToPayAmount",
            ]);
            $this->ReadDb->from('Vendors.VendorTbl V');
            $this->ReadDb->join(
                'Vendors.VendOpeningBalanceTbl VOB',
                'VOB.VendorUID = V.VendorUID AND VOB.OrgUID = V.OrgUID AND VOB.IsDeleted = 0',
                'left'
            );
            $this->ReadDb->where(['V.IsDeleted' => 0, 'V.OrgUID' => (int)$OrgUID]);
            $query = $this->ReadDb->get();
            if (!$query) throw new Exception('DB error');
            return $query->row();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }

    public function getVendorTags(int $OrgUID): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('Vendors.Tags AS Tags');
            $this->ReadDb->from('Vendors.VendorTbl as Vendors');
            $this->ReadDb->where(['Vendors.OrgUID' => (int)$OrgUID, 'Vendors.IsDeleted' => 0, 'Vendors.IsActive' => 1]);
            $this->ReadDb->where('Vendors.Tags IS NOT NULL');
            $this->ReadDb->where('Vendors.Tags !=', '');
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

    // ── VendOpeningBalanceTbl (one row per vendor, no year) ───────────────────

    public function getVendorOpeningBalance(int $orgUID, int $vendorUID): ?object {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'VendBalUID', 'OpeningBalance', 'OpeningBalType',
                'PendingBalance', 'PendingBalType', 'Notes',
            ]);
            $this->ReadDb->from('Vendors.VendOpeningBalanceTbl');
            $this->ReadDb->where([
                'OrgUID'    => (int)$orgUID,
                'VendorUID' => (int)$vendorUID,
                'IsDeleted' => 0,
            ]);
            $this->ReadDb->limit(1);
            $query = $this->ReadDb->get();
            if (!$query) throw new Exception($this->ReadDb->error()['message'] ?? 'DB error');
            return $query->row();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function saveVendorOpeningBalance(int $orgUID, int $vendorUID, float $openingBalance, string $openingBalType, ?string $notes, int $userUID): int {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('VendBalUID');
            $this->ReadDb->from('Vendors.VendOpeningBalanceTbl');
            $this->ReadDb->where(['OrgUID' => (int)$orgUID, 'VendorUID' => (int)$vendorUID, 'IsDeleted' => 0]);
            $existing = $this->ReadDb->get()->row();

            if ($existing) {
                $this->dbwrite_model->updateData('Vendors', 'VendOpeningBalanceTbl', [
                    'OpeningBalance' => (float)$openingBalance,
                    'OpeningBalType' => $openingBalType,
                    'Notes'          => $notes ?: NULL,
                    'UpdatedBy'      => (int)$userUID,
                ], ['VendBalUID' => (int)$existing->VendBalUID]);
                return (int)$existing->VendBalUID;
            }

            $res = $this->dbwrite_model->insertData('Vendors', 'VendOpeningBalanceTbl', [
                'OrgUID'         => (int)$orgUID,
                'VendorUID'      => (int)$vendorUID,
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
            if ($res->Error) throw new Exception($res->Message ?? 'Vendor opening balance insert failed.');
            return (int)$res->ID;

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function updateVendorPendingBalance(int $orgUID, int $vendorUID, float $pendingBalance, string $pendingBalType, int $userUID): void {
        try {
            $res = $this->dbwrite_model->updateData('Vendors', 'VendOpeningBalanceTbl', [
                'PendingBalance' => (float)$pendingBalance,
                'PendingBalType' => $pendingBalType,
                'UpdatedBy'      => (int)$userUID,
            ], ['OrgUID' => (int)$orgUID, 'VendorUID' => (int)$vendorUID, 'IsDeleted' => 0]);
            if ($res->Error) throw new Exception($res->Message ?? 'Vendor pending balance update failed.');
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    // Purchase Returns already covered by a pending/applied debit note — excluded from effectiveReturned to avoid double-counting.
    public function getVendorPRCoveredByDebitNote(int $orgUID, int $vendorUID): float {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('COALESCE(SUM(COALESCE(T.BalanceAmount, T.NetAmount)), 0) AS total');
            $this->ReadDb->from('`Transaction`.TransactionsTbl T');
            $this->ReadDb->join(
                '`Transaction`.TransDebitNoteTbl DN',
                'DN.SourceTransUID = T.TransUID AND DN.SourceModuleUID = 108 AND DN.IsDeleted = 0 AND DN.OrgUID = ' . (int)$orgUID,
                'inner'
            );
            $this->ReadDb->where([
                'T.OrgUID'    => (int)$orgUID,
                'T.PartyUID'  => (int)$vendorUID,
                'T.PartyType' => 'V',
                'T.IsDeleted' => 0,
                'T.ModuleUID' => 108,
            ]);
            $this->ReadDb->where_not_in('T.DocStatus', ['Cancelled', 'Rejected']);
            $this->ReadDb->where('DN.IsCancelled', 0);
            $query = $this->ReadDb->get();
            if (!$query) throw new Exception($this->ReadDb->error()['message'] ?? 'DB error');
            return (float)$query->row()->total;
        } catch (Exception $e) {
            log_message('error', 'Vendors_model::getVendorPRCoveredByDebitNote failed: ' . $e->getMessage());
            return 0.0;
        }
    }

    // Returns [pendingDebitNotes, pendingCreditNotes] totals for a vendor.
    // Debit notes  (TransDebitNoteTbl, PartyType='S') = vendor owes us  → reduces our payable
    // Credit notes (TransCreditNoteTbl, PartyType='S') = we owe vendor  → increases our payable
    public function getVendorPendingNoteTotals(int $orgUID, int $vendorUID): array {
        try {
            $this->ReadDb->db_debug = FALSE;

            $this->ReadDb->select('COALESCE(SUM(Amount), 0) AS total');
            $this->ReadDb->from('Transaction.TransDebitNoteTbl');
            $this->ReadDb->where([
                'OrgUID'    => (int)$orgUID,
                'PartyUID'  => (int)$vendorUID,
                'PartyType' => 'S',
                'Status'    => 'Pending',
                'IsDeleted' => 0,
            ]);
            $dnRow          = $this->ReadDb->get()->row();
            $pendingDebit   = $dnRow ? (float)$dnRow->total : 0.0;

            $this->ReadDb->select('COALESCE(SUM(Amount), 0) AS total');
            $this->ReadDb->from('Transaction.TransCreditNoteTbl');
            $this->ReadDb->where([
                'OrgUID'    => (int)$orgUID,
                'PartyUID'  => (int)$vendorUID,
                'PartyType' => 'S',
                'Status'    => 'Pending',
                'IsDeleted' => 0,
            ]);
            $cnRow          = $this->ReadDb->get()->row();
            $pendingCredit  = $cnRow ? (float)$cnRow->total : 0.0;

            return [$pendingDebit, $pendingCredit];
        } catch (Exception $e) {
            log_message('error', 'Vendors_model::getVendorPendingNoteTotals failed: ' . $e->getMessage());
            return [0.0, 0.0];
        }
    }

    // Returns opening balance as signed float: Credit=+, Debit=-.
    public function getVendorOpeningBalanceSigned(int $orgUID, int $vendorUID): float {
        $row = $this->getVendorOpeningBalance($orgUID, $vendorUID);
        if (!$row) return 0.0;
        $amt = (float)$row->OpeningBalance;
        return ($row->OpeningBalType === 'Credit') ? $amt : -$amt;
    }

    public function getVendorDebitCreditRaw(int $vendorUID): ?object {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select(['DebitCreditAmount', 'DebitCreditType']);
            $this->ReadDb->from('Vendors.VendorTbl');
            $this->ReadDb->where(['VendorUID' => (int)$vendorUID, 'IsDeleted' => 0]);
            $this->ReadDb->limit(1);
            return $this->ReadDb->get()->row();
        } catch (Exception $e) {
            return null;
        }
    }

    // Applies a signed numeric delta (+/-) to the vendor running opening balance.
    // Returns ['balance' => float, 'type' => 'Debit'|'Credit'].
    // ── VendYearOpeningBalanceTbl (year-wise opening balance snapshot) ─────────

    // $onlyIfNew=true: insert-only, preserving the year-start snapshot.
    public function saveVendorYearOpening(int $orgUID, int $vendorUID, int $financialYear, float $openingBalance, string $openingBalType, int $userUID, bool $onlyIfNew = false): int {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('YearBalUID');
            $this->ReadDb->from('Vendors.VendYearOpeningBalanceTbl');
            $this->ReadDb->where([
                'OrgUID'        => (int)$orgUID,
                'VendorUID'     => (int)$vendorUID,
                'FinancialYear' => (int)$financialYear,
                'IsDeleted'     => 0,
            ]);
            $existing = $this->ReadDb->get()->row();

            if ($existing) {
                if ($onlyIfNew) return (int)$existing->YearBalUID;
                $this->dbwrite_model->updateData('Vendors', 'VendYearOpeningBalanceTbl', [
                    'OpeningBalance' => (float)$openingBalance,
                    'OpeningBalType' => $openingBalType,
                    'UpdatedBy'      => (int)$userUID,
                ], ['YearBalUID' => (int)$existing->YearBalUID]);
                return (int)$existing->YearBalUID;
            }

            $res = $this->dbwrite_model->insertData('Vendors', 'VendYearOpeningBalanceTbl', [
                'OrgUID'         => (int)$orgUID,
                'VendorUID'      => (int)$vendorUID,
                'FinancialYear'  => (int)$financialYear,
                'OpeningBalance' => (float)$openingBalance,
                'OpeningBalType' => $openingBalType,
                'IsActive'       => 1,
                'IsDeleted'      => 0,
                'CreatedBy'      => (int)$userUID,
                'UpdatedBy'      => (int)$userUID,
            ]);
            if ($res->Error) throw new Exception($res->Message ?? 'Vendor year opening balance insert failed.');
            return (int)$res->ID;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getVendorYearOpening(int $orgUID, int $vendorUID, int $financialYear): ?object {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select(['YearBalUID', 'FinancialYear', 'OpeningBalance', 'OpeningBalType']);
            $this->ReadDb->from('Vendors.VendYearOpeningBalanceTbl');
            $this->ReadDb->where([
                'OrgUID'        => (int)$orgUID,
                'VendorUID'     => (int)$vendorUID,
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

    // ── Balance recalculation helpers ─────────────────────────────────────────

    public function getVendorsWithLedgerForBalance(int $orgUID, int $vendorUID = 0): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'V.VendorUID',
                'IFNULL(VOB.OpeningBalance, 0.00)    AS OpeningBalance',
                "IFNULL(VOB.OpeningBalType, 'Credit') AS OpeningBalType",
                'ELM.LedgerUID',
                'COA.CurrentBalance     AS LedgerCurrentBalance',
                'COA.CurrentBalanceType AS LedgerCurrentType',
            ]);
            $this->ReadDb->from('Vendors.VendorTbl V');
            $this->ReadDb->join(
                'Vendors.VendOpeningBalanceTbl VOB',
                'VOB.VendorUID = V.VendorUID AND VOB.IsDeleted = 0',
                'left'
            );
            $this->ReadDb->join(
                'Accounting.EntityLedgerMap ELM',
                "ELM.VendorUID = V.VendorUID AND ELM.EntityType = 'Vendor' AND ELM.IsDeleted = 0",
                'left'
            );
            $this->ReadDb->join(
                'Accounting.ChartOfAccounts COA',
                'COA.LedgerUID = ELM.LedgerUID AND COA.IsDeleted = 0',
                'left'
            );
            $this->ReadDb->where(['V.OrgUID' => (int)$orgUID, 'V.IsDeleted' => 0, 'V.IsActive' => 1]);
            if ($vendorUID > 0) {
                $this->ReadDb->where('V.VendorUID', (int)$vendorUID);
            }
            $query = $this->ReadDb->get();
            if (!$query) throw new Exception($this->ReadDb->error()['message'] ?? 'DB error');
            return $query->result();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Return the vendor's current ChartOfAccounts balance using a fresh DB read.
     * Commits any open REPEATABLE READ transaction on ReadDb first so the next
     * query starts a new consistent-read snapshot and sees data written by WriteDb
     * in the same HTTP request (e.g. from postExpenseJournal / reverseJournal).
     *
     * @param int $vendorUID
     * @return array{balance: float, balType: string}
     */
    public function getVendorClosingBalanceFresh(int $vendorUID): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            // End any stale REPEATABLE READ snapshot so the next SELECT sees committed data.
            $this->ReadDb->simple_query('COMMIT');
            $row = $this->ReadDb->query(
                "SELECT
                    IFNULL((SELECT COA.CurrentBalance
                            FROM Accounting.EntityLedgerMap ELM
                            JOIN Accounting.ChartOfAccounts COA
                              ON COA.LedgerUID = ELM.LedgerUID AND COA.IsDeleted = 0
                            WHERE ELM.VendorUID = ? AND ELM.EntityType = 'Vendor' AND ELM.IsDeleted = 0
                            LIMIT 1), 0.00) AS ClosingBalance,
                    IFNULL((SELECT COA.CurrentBalanceType
                            FROM Accounting.EntityLedgerMap ELM
                            JOIN Accounting.ChartOfAccounts COA
                              ON COA.LedgerUID = ELM.LedgerUID AND COA.IsDeleted = 0
                            WHERE ELM.VendorUID = ? AND ELM.EntityType = 'Vendor' AND ELM.IsDeleted = 0
                            LIMIT 1), 'Credit') AS ClosingBalanceType",
                [(int)$vendorUID, (int)$vendorUID]
            )->row();
            $result = [
                'balance' => $row ? (float)$row->ClosingBalance      : 0.0,
                'balType' => $row ? (string)$row->ClosingBalanceType  : 'Credit',
            ];
            log_message('debug', '[EXP-BAL] getVendorClosingBalanceFresh → vendorUID=' . $vendorUID . ' ChartOfAccounts.CurrentBalance=' . $result['balance'] . ' ' . $result['balType']);
            return $result;
        } catch (Exception $e) {
            log_message('error', '[VENDOR-CACHE] getVendorClosingBalanceFresh: ' . $e->getMessage());
            return ['balance' => 0.0, 'balType' => 'Credit'];
        }
    }

    // Total net amount billed by vendor (Purchases, ModuleUID=105).
    public function getVendorTotalPurchased(int $orgUID, int $vendorUID): float {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('COALESCE(SUM(NetAmount), 0) AS total');
            $this->ReadDb->from('`Transaction`.TransactionsTbl');
            $this->ReadDb->where([
                'OrgUID'    => (int)$orgUID,
                'PartyUID'  => (int)$vendorUID,
                'PartyType' => 'V',
                'ModuleUID' => 105,
                'IsDeleted' => 0,
            ]);
            $this->ReadDb->where_not_in('DocStatus', ['Cancelled', 'Rejected']);
            $query = $this->ReadDb->get();
            if (!$query) throw new Exception($this->ReadDb->error()['message'] ?? 'DB error');
            return (float) $query->row()->total;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    // Total amount paid out to vendor.
    public function getVendorTotalPaid(int $orgUID, int $vendorUID): float {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('COALESCE(SUM(Amount), 0) AS total');
            $this->ReadDb->from('`Transaction`.PaymentsTbl');
            $this->ReadDb->where([
                'OrgUID'    => (int)$orgUID,
                'PartyUID'  => (int)$vendorUID,
                'PartyType' => 'V',
                'IsDeleted' => 0,
            ]);
            $query = $this->ReadDb->get();
            if (!$query) throw new Exception($this->ReadDb->error()['message'] ?? 'DB error');
            return (float) $query->row()->total;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    // Total net amount returned to vendor (Purchase Returns=108, Debit Notes=109).
    public function getVendorTotalReturned(int $orgUID, int $vendorUID): float {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('COALESCE(SUM(NetAmount), 0) AS total');
            $this->ReadDb->from('`Transaction`.TransactionsTbl');
            $this->ReadDb->where([
                'OrgUID'    => (int)$orgUID,
                'PartyUID'  => (int)$vendorUID,
                'PartyType' => 'V',
                'IsDeleted' => 0,
            ]);
            $this->ReadDb->where_in('ModuleUID', [108, 109]);
            $this->ReadDb->where_not_in('DocStatus', ['Cancelled', 'Rejected']);
            $query = $this->ReadDb->get();
            if (!$query) throw new Exception($this->ReadDb->error()['message'] ?? 'DB error');
            return (float) $query->row()->total;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function updateVendorBalanceInLedger(int $ledgerUID, float $balance, string $balanceType, int $userUID): void {
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

    // ══════════════════════════════════════════════════════════════════
    // Vendor Group model methods
    // ══════════════════════════════════════════════════════════════════

    public function searchVendorsForGroup(string $term, int $orgUID = 0, int $excludeGroupUID = 0): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('V.VendorUID, V.Name, V.Area');
            $this->ReadDb->from('Vendors.VendorTbl V');
            if ($orgUID > 0) {
                $this->ReadDb->join(
                    'Vendors.VendorGroupMemberTbl VGM',
                    'VGM.VendorUID = V.VendorUID AND VGM.OrgUID = V.OrgUID AND VGM.IsDeleted = 0',
                    'left'
                );
            }
            $this->ReadDb->group_start();
            $this->ReadDb->or_like('V.Name',         $term, 'both');
            $this->ReadDb->or_like('V.MobileNumber', $term, 'both');
            $this->ReadDb->or_like('V.Area',         $term, 'both');
            $this->ReadDb->group_end();
            $this->ReadDb->where(['V.IsDeleted' => 0, 'V.IsActive' => 1]);
            if ($orgUID > 0) {
                $this->ReadDb->where('V.OrgUID', (int)$orgUID);
                // Exclude vendors already in a different group
                $this->ReadDb->group_start();
                $this->ReadDb->where('VGM.MemberUID IS NULL', null, false);
                if ($excludeGroupUID > 0) {
                    $this->ReadDb->or_where('VGM.GroupUID', (int)$excludeGroupUID);
                }
                $this->ReadDb->group_end();
            }
            $this->ReadDb->limit(10);
            $query = $this->ReadDb->get();
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getVendorGroupListPaginated(int $orgUID, int $limit, int $offset, array $filter = []): object {
        try {
            $this->ReadDb->db_debug = FALSE;

            $tblChk       = $this->ReadDb->query("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA='Vendors' AND TABLE_NAME='VendorGroupMemberTbl' LIMIT 1");
            $hasMemberTbl = $tblChk && $tblChk->num_rows() > 0;

            // ── Count query ──
            $this->ReadDb->select('COUNT(*) AS cnt', false);
            $this->ReadDb->from('Vendors.VendorGroupTbl VG');
            $this->ReadDb->where(['VG.OrgUID' => (int)$orgUID, 'VG.IsDeleted' => 0]);
            if (!empty($filter['SearchAllData'])) {
                $s = $filter['SearchAllData'];
                $this->ReadDb->group_start();
                $this->ReadDb->like('VG.GroupName', $s);
                $this->ReadDb->or_like('VG.GroupCode', $s);
                $this->ReadDb->or_like('VG.GroupType', $s);
                $this->ReadDb->or_like('VG.Mobile', $s);
                $this->ReadDb->group_end();
            }
            if (isset($filter['IsActive']) && $filter['IsActive'] !== '') {
                $this->ReadDb->where('VG.IsActive', (int)$filter['IsActive']);
            }
            if (!empty($filter['GroupType'])) {
                $this->ReadDb->where_in('VG.GroupType', (array)$filter['GroupType']);
            }
            if ($hasMemberTbl && !empty($filter['VendorUID'])) {
                $vuid = (int)$filter['VendorUID'];
                $this->ReadDb->where("VG.GroupUID IN (SELECT GroupUID FROM Vendors.VendorGroupMemberTbl WHERE VendorUID = {$vuid} AND OrgUID = {$orgUID} AND IsDeleted = 0)", null, false);
            }
            $countRow   = $this->ReadDb->get()->row();
            $totalCount = (int)($countRow->cnt ?? 0);

            // ── Step 1: Paginated groups ──
            if ($hasMemberTbl) {
                $this->ReadDb->select(
                    'VG.GroupUID, VG.GroupCode, VG.GroupName, VG.GroupType,
                     VG.ContactPerson, VG.Mobile, VG.Email, VG.IsActive,
                     COUNT(V.VendorUID) AS MemberCount,
                     MAX(CASE WHEN VGM.IsGroupPrimary = 1 THEN V.Name ELSE NULL END) AS PrimaryName,
                     0 AS TotalReceivable, 0 AS TotalPayable',
                    false
                );
                $this->ReadDb->from('Vendors.VendorGroupTbl VG');
                $this->ReadDb->join('Vendors.VendorGroupMemberTbl VGM', 'VGM.GroupUID = VG.GroupUID AND VGM.OrgUID = VG.OrgUID AND VGM.IsDeleted = 0', 'left');
                $this->ReadDb->join('Vendors.VendorTbl V', 'V.VendorUID = VGM.VendorUID AND V.IsDeleted = 0', 'left');
            } else {
                $this->ReadDb->select(
                    'VG.GroupUID, VG.GroupCode, VG.GroupName, VG.GroupType,
                     VG.ContactPerson, VG.Mobile, VG.Email, VG.IsActive,
                     0 AS MemberCount, NULL AS PrimaryName,
                     0 AS TotalReceivable, 0 AS TotalPayable',
                    false
                );
                $this->ReadDb->from('Vendors.VendorGroupTbl VG');
            }
            $this->ReadDb->where(['VG.OrgUID' => (int)$orgUID, 'VG.IsDeleted' => 0]);
            if (!empty($filter['SearchAllData'])) {
                $s = $filter['SearchAllData'];
                $this->ReadDb->group_start();
                $this->ReadDb->like('VG.GroupName', $s);
                $this->ReadDb->or_like('VG.GroupCode', $s);
                $this->ReadDb->or_like('VG.GroupType', $s);
                $this->ReadDb->or_like('VG.Mobile', $s);
                $this->ReadDb->group_end();
            }
            if (isset($filter['IsActive']) && $filter['IsActive'] !== '') {
                $this->ReadDb->where('VG.IsActive', (int)$filter['IsActive']);
            }
            if (!empty($filter['GroupType'])) {
                $this->ReadDb->where_in('VG.GroupType', (array)$filter['GroupType']);
            }
            if ($hasMemberTbl && !empty($filter['VendorUID'])) {
                $vuid = (int)$filter['VendorUID'];
                $this->ReadDb->where("VG.GroupUID IN (SELECT GroupUID FROM Vendors.VendorGroupMemberTbl WHERE VendorUID = {$vuid} AND OrgUID = {$orgUID} AND IsDeleted = 0)", null, false);
            }
            $this->ReadDb->group_by('VG.GroupUID');
            $this->ReadDb->order_by('VG.GroupName', 'ASC');
            $this->ReadDb->limit($limit, $offset);
            $query = $this->ReadDb->get();
            $rows  = $query ? $query->result() : [];

            // ── Step 2: Balance totals (only when member table exists) ──
            if ($hasMemberTbl && !empty($rows)) {
                $groupUIDs    = [];
                foreach ($rows as $row) { $groupUIDs[] = (int)$row->GroupUID; }
                $placeholders = implode(',', array_fill(0, count($groupUIDs), '?'));
                $balQuery = $this->ReadDb->query(
                    "SELECT VGM.GroupUID,
                            COALESCE(SUM(CASE WHEN VOB.PendingBalType = 'Debit'  AND VOB.PendingBalance > 0 THEN VOB.PendingBalance ELSE 0 END), 0) AS TotalReceivable,
                            COALESCE(SUM(CASE WHEN VOB.PendingBalType = 'Credit' AND VOB.PendingBalance > 0 THEN VOB.PendingBalance ELSE 0 END), 0) AS TotalPayable
                     FROM Vendors.VendorGroupMemberTbl VGM
                     JOIN Vendors.VendorTbl V
                          ON V.VendorUID = VGM.VendorUID AND V.IsDeleted = 0
                     JOIN Vendors.VendOpeningBalanceTbl VOB
                          ON VOB.VendorUID = V.VendorUID AND VOB.OrgUID = V.OrgUID AND VOB.IsDeleted = 0
                     WHERE VGM.OrgUID = ? AND VGM.GroupUID IN ({$placeholders}) AND VGM.IsDeleted = 0
                     GROUP BY VGM.GroupUID",
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

    public function getVendorGroupStats(int $orgUID): ?object {
        try {
            $this->ReadDb->db_debug = FALSE;
            $tblChk       = $this->ReadDb->query("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA='Vendors' AND TABLE_NAME='VendorGroupMemberTbl' LIMIT 1");
            $hasMemberTbl = $tblChk && $tblChk->num_rows() > 0;
            if ($hasMemberTbl) {
                $query = $this->ReadDb->query(
                    "SELECT COUNT(*) AS TotalCount, SUM(IsActive=1) AS ActiveCount, SUM(IsActive=0) AS InactiveCount,
                            (SELECT COUNT(*) FROM Vendors.VendorGroupMemberTbl WHERE OrgUID=? AND IsDeleted=0) AS TotalMembers
                     FROM Vendors.VendorGroupTbl WHERE OrgUID=? AND IsDeleted=0",
                    [(int)$orgUID, (int)$orgUID]
                );
            } else {
                $query = $this->ReadDb->query(
                    "SELECT COUNT(*) AS TotalCount, SUM(IsActive=1) AS ActiveCount, SUM(IsActive=0) AS InactiveCount,
                            0 AS TotalMembers
                     FROM Vendors.VendorGroupTbl WHERE OrgUID=? AND IsDeleted=0",
                    [(int)$orgUID]
                );
            }
            return $query ? $query->row() : null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function getGroupTypes(string $module = 'vendors'): array {
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

    public function getVendorGroupByUID(int $orgUID, int $groupUID): ?object {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('VG.*');
            $this->ReadDb->from('Vendors.VendorGroupTbl VG');
            $this->ReadDb->where(['VG.OrgUID' => (int)$orgUID, 'VG.GroupUID' => (int)$groupUID, 'VG.IsDeleted' => 0]);
            $query = $this->ReadDb->get();
            return $query ? $query->row() : null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function getVendorGroupMembers(int $orgUID, int $groupUID): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'V.VendorUID', 'V.Name', 'V.Area', 'V.MobileNumber',
                'VGM.IsGroupPrimary',
                "IFNULL(VOB.PendingBalance, 0)        AS Balance",
                "IFNULL(VOB.PendingBalType, 'Credit') AS BalanceType",
            ]);
            $this->ReadDb->from('Vendors.VendorGroupMemberTbl VGM');
            $this->ReadDb->join('Vendors.VendorTbl V', 'V.VendorUID = VGM.VendorUID AND V.IsDeleted = 0');
            $this->ReadDb->join('Vendors.VendOpeningBalanceTbl VOB', 'VOB.VendorUID = V.VendorUID AND VOB.OrgUID = V.OrgUID AND VOB.IsDeleted = 0', 'left');
            $this->ReadDb->where(['VGM.OrgUID' => (int)$orgUID, 'VGM.GroupUID' => (int)$groupUID, 'VGM.IsDeleted' => 0]);
            $this->ReadDb->order_by('VGM.IsGroupPrimary', 'DESC');
            $this->ReadDb->order_by('V.Name', 'ASC');
            $query = $this->ReadDb->get();
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            return [];
        }
    }

    public function getActiveVendorGroupsForDropdown(int $orgUID): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select(['GroupUID', 'GroupName', 'GroupCode', 'GroupType']);
            $this->ReadDb->from('Vendors.VendorGroupTbl');
            $this->ReadDb->where(['OrgUID' => (int)$orgUID, 'IsActive' => 1, 'IsDeleted' => 0]);
            $this->ReadDb->order_by('GroupName', 'ASC');
            $query = $this->ReadDb->get();
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            return [];
        }
    }

    public function assignVendorGroupMembers(int $orgUID, int $groupUID, array $memberUIDs, int $primaryUID, int $userUID): void {
        if (empty($memberUIDs)) return;
        foreach ($memberUIDs as $vendUID) {
            $this->saveVendorGroupMembership(
                $orgUID, (int)$vendUID, $groupUID,
                ((int)$vendUID === (int)$primaryUID) ? 1 : 0,
                $userUID
            );
        }
    }

    public function syncVendorGroupMembers(int $orgUID, int $groupUID, array $newMemberUIDs, int $primaryUID, int $userUID): void {
        $db = $this->dbwrite_model->getWriteDb();
        // Soft-delete members removed from the group
        $db->where('OrgUID',   (int)$orgUID);
        $db->where('GroupUID', (int)$groupUID);
        $db->where('IsDeleted', 0);
        if (!empty($newMemberUIDs)) {
            $db->where_not_in('VendorUID', array_map('intval', $newMemberUIDs));
        }
        $db->update('Vendors.VendorGroupMemberTbl', ['IsDeleted' => 1, 'UpdatedBy' => (int)$userUID]);
        // Upsert all current members
        if (!empty($newMemberUIDs)) {
            $this->assignVendorGroupMembers($orgUID, $groupUID, $newMemberUIDs, $primaryUID, $userUID);
        }
    }

    public function unlinkAllVendorGroupMembers(int $orgUID, int $groupUID, int $userUID): void {
        $db = $this->dbwrite_model->getWriteDb();
        $db->where('OrgUID',   (int)$orgUID);
        $db->where('GroupUID', (int)$groupUID);
        $db->where('IsDeleted', 0);
        $db->update('Vendors.VendorGroupMemberTbl', ['IsDeleted' => 1, 'UpdatedBy' => (int)$userUID]);
    }

    public function getVendorGroupMembership(int $orgUID, int $vendorUID): ?object {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('VGM.MemberUID, VGM.GroupUID, VGM.IsGroupPrimary');
            $this->ReadDb->from('Vendors.VendorGroupMemberTbl VGM');
            $this->ReadDb->where(['VGM.OrgUID' => $orgUID, 'VGM.VendorUID' => $vendorUID, 'VGM.IsDeleted' => 0]);
            $this->ReadDb->limit(1);
            $query = $this->ReadDb->get();
            return $query ? $query->row() : null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function saveVendorGroupMembership(int $orgUID, int $vendorUID, int $groupUID, int $isGroupPrimary, int $userUID): void {
        $db = $this->dbwrite_model->getWriteDb();
        $db->query(
            "INSERT INTO Vendors.VendorGroupMemberTbl
                (OrgUID, VendorUID, GroupUID, IsGroupPrimary, IsDeleted, CreatedBy, UpdatedBy)
             VALUES (?, ?, ?, ?, 0, ?, ?)
             ON DUPLICATE KEY UPDATE
                GroupUID       = VALUES(GroupUID),
                IsGroupPrimary = VALUES(IsGroupPrimary),
                IsDeleted      = 0,
                UpdatedBy      = VALUES(UpdatedBy)",
            [(int)$orgUID, (int)$vendorUID, (int)$groupUID, (int)$isGroupPrimary, (int)$userUID, (int)$userUID]
        );
    }

    public function removeVendorFromGroup(int $orgUID, int $vendorUID, int $userUID): void {
        $db = $this->dbwrite_model->getWriteDb();
        $db->where('OrgUID',    (int)$orgUID);
        $db->where('VendorUID', (int)$vendorUID);
        $db->where('IsDeleted', 0);
        $db->update('Vendors.VendorGroupMemberTbl', ['IsDeleted' => 1, 'UpdatedBy' => (int)$userUID]);
    }

    public function getVendorsInOtherGroups(int $orgUID, int $excludeGroupUID = 0): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('VendorUID');
            $this->ReadDb->from('Vendors.VendorGroupMemberTbl');
            $this->ReadDb->where(['OrgUID' => (int)$orgUID, 'IsDeleted' => 0]);
            if ($excludeGroupUID > 0) {
                $this->ReadDb->where('GroupUID !=', (int)$excludeGroupUID);
            }
            $query = $this->ReadDb->get();
            if (!$query) return [];
            return array_column($query->result_array(), 'VendorUID');
        } catch (Exception $e) {
            return [];
        }
    }

    public function getVendorGroupOverview(int $orgUID, int $groupUID): ?object {
        try {
            $this->ReadDb->db_debug = FALSE;
            $query = $this->ReadDb->query(
                "SELECT
                    COUNT(VGM.VendorUID) AS MemberCount,
                    COALESCE(SUM(CASE WHEN VOB.PendingBalType = 'Debit'  AND VOB.PendingBalance > 0 THEN VOB.PendingBalance ELSE 0 END), 0) AS TotalReceivable,
                    COALESCE(SUM(CASE WHEN VOB.PendingBalType = 'Credit' AND VOB.PendingBalance > 0 THEN VOB.PendingBalance ELSE 0 END), 0) AS TotalPayable
                 FROM Vendors.VendorGroupMemberTbl VGM
                 JOIN Vendors.VendorTbl V ON V.VendorUID = VGM.VendorUID AND V.IsDeleted = 0
                 LEFT JOIN Vendors.VendOpeningBalanceTbl VOB
                      ON VOB.VendorUID = V.VendorUID AND VOB.OrgUID = V.OrgUID AND VOB.IsDeleted = 0
                 WHERE VGM.OrgUID = ? AND VGM.GroupUID = ? AND VGM.IsDeleted = 0",
                [(int)$orgUID, (int)$groupUID]
            );
            return $query ? $query->row() : null;
        } catch (Exception $e) {
            return null;
        }
    }

    // ── Vendor Attachments ────────────────────────────────────────────────────

    public function getVendorAttachments(int $vendorUID, int $orgUID): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('AttachUID, FileName, FilePath, FileSize, SortOrder, CreatedOn');
            $this->ReadDb->from('Vendors.VendorAttachmentsTbl');
            $this->ReadDb->where(['VendorUID' => $vendorUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]);
            $this->ReadDb->order_by('SortOrder', 'ASC');
            $query = $this->ReadDb->get();
            return $query ? $query->result_array() : [];
        } catch (Exception $e) {
            log_message('error', 'getVendorAttachments failed: ' . $e->getMessage());
            return [];
        }
    }

    public function getVendorPrimaryImage(int $vendorUID, int $orgUID): ?string {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('FilePath');
            $this->ReadDb->from('Vendors.VendorAttachmentsTbl');
            $this->ReadDb->where(['VendorUID' => $vendorUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]);
            $this->ReadDb->order_by('SortOrder', 'ASC');
            $this->ReadDb->limit(1);
            $row = $this->ReadDb->get()->row();
            return $row ? $row->FilePath : null;
        } catch (Exception $e) { return null; }
    }

    // ── Vendor Profile Modal methods ───────────────────────────────────────

    /**
     * Monthly purchase totals for the last 6 months (bar chart on overview).
     *
     * @param int $orgUID
     * @param int $vendorUID
     * @return array
     */
    public function getVendorMonthlyPurchaseData(int $orgUID, int $vendorUID): array {
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
                'PartyUID'  => $vendorUID,
                'PartyType' => 'V',
                'ModuleUID' => 105,
                'IsDeleted' => 0,
            ]);
            $this->ReadDb->where_not_in('DocStatus', ['Draft', 'Cancelled', 'Rejected']);
            $this->ReadDb->where('TransDate >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)', null, false);
            $this->ReadDb->group_by("DATE_FORMAT(TransDate, '%Y-%m'), DATE_FORMAT(TransDate, '%b %Y')");
            $this->ReadDb->order_by('MonthKey', 'ASC');
            $query = $this->ReadDb->get();
            return $query ? $query->result_array() : [];
        } catch (Exception $e) {
            log_message('error', 'getVendorMonthlyPurchaseData: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Returns the most recent N transactions for a vendor in a given module.
     *
     * @param int $orgUID
     * @param int $vendorUID
     * @param int $moduleUID
     * @param int $limit
     * @return array
     */
    public function getVendorRecentTransactions(int $orgUID, int $vendorUID, int $moduleUID, int $limit = 5): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'Ts.TransUID', 'Ts.TransNumber AS TransNo', 'Ts.TransDate AS DocDate',
                'Ts.NetAmount', 'Ts.DocStatus', 'Ts.BalanceAmount',
            ]);
            $this->ReadDb->from('`Transaction`.TransactionsTbl Ts');
            $this->ReadDb->where([
                'Ts.OrgUID'    => $orgUID,
                'Ts.PartyUID'  => $vendorUID,
                'Ts.PartyType' => 'V',
                'Ts.ModuleUID' => $moduleUID,
                'Ts.IsDeleted' => 0,
            ]);
            $this->ReadDb->order_by('Ts.TransUID', 'DESC');
            $this->ReadDb->limit($limit);
            $query = $this->ReadDb->get();
            return $query ? $query->result_array() : [];
        } catch (Exception $e) {
            log_message('error', 'getVendorRecentTransactions: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Payables statement: purchases (credit), payments-out (debit), purchase returns (debit).
     *
     * @param int    $orgUID
     * @param int    $vendorUID
     * @param string $fromDate  Y-m-d
     * @param string $toDate    Y-m-d
     * @return array
     */
    public function getVendorStatementData(int $orgUID, int $vendorUID, string $fromDate, string $toDate): array {
        try {
            $this->ReadDb->db_debug = FALSE;

            // Purchases (credit — we owe vendor)
            $this->ReadDb->select("'Purchase' AS TxType, TransNumber AS RefNo, TransDate AS TxDate, 0 AS Debit, NetAmount AS Credit, DocStatus");
            $this->ReadDb->from('`Transaction`.TransactionsTbl');
            $this->ReadDb->where(['OrgUID' => $orgUID, 'PartyUID' => $vendorUID, 'PartyType' => 'V', 'ModuleUID' => 105, 'IsDeleted' => 0]);
            $this->ReadDb->where_not_in('DocStatus', ['Cancelled', 'Rejected']);
            $this->ReadDb->where("TransDate BETWEEN '{$fromDate}' AND '{$toDate}'", null, false);
            $q = $this->ReadDb->get();
            $purchases = $q ? $q->result_array() : [];

            // Payments to vendor (debit — we pay off what we owe)
            $this->ReadDb->select("'Payment' AS TxType, '' AS RefNo, DATE(PaymentDate) AS TxDate, Amount AS Debit, 0 AS Credit, 'Paid' AS DocStatus");
            $this->ReadDb->from('`Transaction`.PaymentsTbl');
            $this->ReadDb->where(['OrgUID' => $orgUID, 'PartyUID' => $vendorUID, 'PartyType' => 'V', 'IsDeleted' => 0, 'IsCancelled' => 0]);
            $this->ReadDb->where("DATE(PaymentDate) BETWEEN '{$fromDate}' AND '{$toDate}'", null, false);
            $q = $this->ReadDb->get();
            $payments = $q ? $q->result_array() : [];

            // Purchase Returns (debit — vendor credits us back)
            $this->ReadDb->select("'Return' AS TxType, TransNumber AS RefNo, TransDate AS TxDate, NetAmount AS Debit, 0 AS Credit, DocStatus");
            $this->ReadDb->from('`Transaction`.TransactionsTbl');
            $this->ReadDb->where(['OrgUID' => $orgUID, 'PartyUID' => $vendorUID, 'PartyType' => 'V', 'IsDeleted' => 0]);
            $this->ReadDb->where_in('ModuleUID', [108, 109]);
            $this->ReadDb->where_not_in('DocStatus', ['Cancelled', 'Rejected']);
            $this->ReadDb->where("TransDate BETWEEN '{$fromDate}' AND '{$toDate}'", null, false);
            $q = $this->ReadDb->get();
            $returns = $q ? $q->result_array() : [];

            $all = array_merge($purchases, $payments, $returns);
            usort($all, function (array $a, array $b): int { return strcmp($a['TxDate'], $b['TxDate']); });
            return $all;
        } catch (Exception $e) {
            log_message('error', 'getVendorStatementData: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Returns internal notes for a vendor (requires VendorNotesTbl).
     *
     * @param int $orgUID
     * @param int $vendorUID
     * @return array
     */
    public function getVendorNotes(int $orgUID, int $vendorUID): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('N.NoteUID, N.Note, N.CreatedOn, CONCAT(U.FirstName, \' \', U.LastName) AS UserName');
            $this->ReadDb->from('Vendors.VendorNotesTbl N');
            $this->ReadDb->join('Users.UserTbl U', 'U.UserUID = N.UserUID', 'left');
            $this->ReadDb->where(['N.OrgUID' => $orgUID, 'N.VendorUID' => $vendorUID, 'N.IsDeleted' => 0]);
            $this->ReadDb->order_by('N.NoteUID', 'DESC');
            $query = $this->ReadDb->get();
            return $query ? $query->result_array() : [];
        } catch (Exception $e) {
            log_message('error', 'getVendorNotes: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Inserts an internal note for a vendor.
     *
     * @param int    $orgUID
     * @param int    $vendorUID
     * @param string $note
     * @param int    $userUID
     * @return void
     */
    public function saveVendorNote(int $orgUID, int $vendorUID, string $note, int $userUID): void {
        $this->load->model('dbwrite_model');
        $res = $this->dbwrite_model->insertData('Vendors', 'VendorNotesTbl', [
            'OrgUID'     => $orgUID,
            'VendorUID'  => $vendorUID,
            'Note'       => $note,
            'UserUID'    => $userUID,
            'IsDeleted'  => 0,
            'CreatedOn'  => date('Y-m-d H:i:s'),
        ]);
        if ($res->Error) throw new Exception($res->Message ?? 'Note insert failed.');
    }

    /**
     * Aggregate summary for the vendor overview tab.
     *
     * @param int $orgUID
     * @param int $vendorUID
     * @return array{TotalPurchased:float,TotalReturned:float,DaysSinceLastTx:int|null}
     */
    public function getVendorFinancialSummary(int $orgUID, int $vendorUID): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $result = $this->ReadDb->query(
                "SELECT
                    COALESCE(SUM(CASE WHEN ModuleUID = 105
                        AND DocStatus NOT IN ('Draft','Cancelled','Rejected')
                        THEN NetAmount ELSE 0 END), 0)                         AS TotalPurchased,
                    COALESCE(SUM(CASE WHEN ModuleUID IN (108, 109)
                        AND DocStatus NOT IN ('Cancelled','Rejected')
                        THEN NetAmount ELSE 0 END), 0)                         AS TotalReturned,
                    DATEDIFF(CURDATE(), MAX(CASE WHEN DocStatus NOT IN ('Draft','Cancelled','Rejected')
                        THEN TransDate END))                                    AS DaysSinceLastTx
                 FROM `Transaction`.TransactionsTbl
                 WHERE OrgUID = ? AND PartyUID = ? AND PartyType = 'V' AND IsDeleted = 0",
                [$orgUID, $vendorUID]
            );
            $row = $result ? $result->row() : null;
            return [
                'TotalPurchased'   => (float) ($row->TotalPurchased   ?? 0),
                'TotalReturned'    => (float) ($row->TotalReturned    ?? 0),
                'DaysSinceLastTx'  => isset($row->DaysSinceLastTx) ? (int) $row->DaysSinceLastTx : null,
            ];
        } catch (Exception $e) {
            log_message('error', 'getVendorFinancialSummary: ' . $e->getMessage());
            return ['TotalPurchased' => 0.0, 'TotalReturned' => 0.0, 'DaysSinceLastTx' => null];
        }
    }

    // ── Vendor Number — sequence claim (mirrors claimNextCustomerNumber) ───────

    /**
     * @param int    $fyStartMonth
     * @param string $timezone
     * @return int  last 2 digits of the financial year (e.g. 26 for FY2026-27)
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
     * Ensures OrgCreditSettingsTbl has a row for $orgUID with vendor columns initialized.
     * Handles: no row, NULL vendor columns, and 0-year (unseeded) vendor columns.
     * @param int    $orgUID
     * @param int    $fyStartMonth
     * @param string $timezone
     * @return object|null
     */
    public function getOrInitVendorCreditSettings(int $orgUID, int $fyStartMonth, string $timezone): ?object {
        try {
            $existing  = $this->getCreditSettings($orgUID);
            $fyYear2   = $this->_calcFYYear($fyStartMonth, $timezone);
            $yrPad     = str_pad($fyYear2, 2, '0', STR_PAD_LEFT);
            $this->load->model('dbwrite_model');

            if (!$existing) {
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

            $vendorSeq  = $existing->VendorSeq     ?? null;
            $vendorYear = $existing->VendorSeqYear  ?? null;
            $needsInit  = ($vendorSeq === null || $vendorYear === null ||
                           ((int) $vendorSeq === 0 && (int) $vendorYear === 0));
            if ($needsInit) {
                $db = $this->dbwrite_model->getWriteDb();
                $db->db_debug = FALSE;
                $db->where('OrgUID', $orgUID)
                   ->update('Settings.OrgCreditSettingsTbl', [
                       'VendorSeq'        => 1,
                       'VendorSeqYear'    => $fyYear2,
                       'VendorNextNumber' => 'V-' . $yrPad . '0001',
                       'UpdatedAt'        => date('Y-m-d H:i:s'),
                   ]);
                return $this->getCreditSettings($orgUID);
            }

            if (empty($existing->VendorNextNumber)) {
                $db = $this->dbwrite_model->getWriteDb();
                $db->db_debug = FALSE;
                $nextNum = 'V-' . $yrPad . str_pad((int) $vendorSeq, 4, '0', STR_PAD_LEFT);
                $db->where('OrgUID', $orgUID)
                   ->update('Settings.OrgCreditSettingsTbl', [
                       'VendorNextNumber' => $nextNum,
                       'UpdatedAt'        => date('Y-m-d H:i:s'),
                   ]);
                return $this->getCreditSettings($orgUID);
            }

            return $existing;
        } catch (Exception $e) {
            log_message('error', 'getOrInitVendorCreditSettings: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Atomically claims the next vendor number for $orgUID.
     * Mirrors claimNextCustomerNumber() — uses VendorSeq / VendorSeqYear / VendorNextNumber.
     * Returns ['claimed' => 'V-260001', 'next' => 'V-260002'] on success, null on failure.
     * @param int    $orgUID
     * @param int    $fyStartMonth
     * @param string $timezone
     * @return array{claimed:string,next:string}|null
     */
    public function claimNextVendorNumber(int $orgUID, int $fyStartMonth, string $timezone): ?array {
        try {
            $this->load->model('dbwrite_model');
            $db = $this->dbwrite_model->getWriteDb();

            for ($attempt = 1; $attempt <= 5; $attempt++) {
                $row = ($attempt === 1)
                    ? $this->getOrInitVendorCreditSettings($orgUID, $fyStartMonth, $timezone)
                    : $this->getCreditSettings($orgUID);
                if (!$row) return null;

                $currentFYYear = $this->_calcFYYear($fyStartMonth, $timezone);
                $storedFYYear  = (int) $row->VendorSeqYear;
                $storedSeq     = (int) $row->VendorSeq;
                $yrPad         = str_pad($currentFYYear, 2, '0', STR_PAD_LEFT);

                $isLegitRollover = ($storedFYYear === (($currentFYYear - 1 + 100) % 100));

                if ($currentFYYear === $storedFYYear) {
                    $claimedSeq = $storedSeq;
                    $nextSeq    = $storedSeq + 1;
                } elseif ($isLegitRollover) {
                    $claimedSeq = 1;
                    $nextSeq    = 2;
                } else {
                    $claimedSeq = $storedSeq;
                    $nextSeq    = $storedSeq + 1;
                }

                $claimedNum = 'V-' . $yrPad . str_pad($claimedSeq, 4, '0', STR_PAD_LEFT);
                $nextNum    = 'V-' . $yrPad . str_pad($nextSeq,    4, '0', STR_PAD_LEFT);

                $db->db_debug = FALSE;
                $db->where('OrgUID',        $orgUID)
                   ->where('VendorSeq',     $storedSeq)
                   ->where('VendorSeqYear', $storedFYYear)
                   ->update('Settings.OrgCreditSettingsTbl', [
                       'VendorSeq'        => $nextSeq,
                       'VendorSeqYear'    => $currentFYYear,
                       'VendorNextNumber' => $nextNum,
                       'UpdatedAt'        => date('Y-m-d H:i:s'),
                   ]);

                if ($db->affected_rows() === 1) {
                    return ['claimed' => $claimedNum, 'next' => $nextNum];
                }
            }
            return null;
        } catch (Exception $e) {
            log_message('error', 'claimNextVendorNumber: ' . $e->getMessage());
            return null;
        }
    }
}