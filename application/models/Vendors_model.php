<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Vendors_model extends CI_Model {

    private $EndReturnData;
    private $ReadDb;

    function __construct() {
        parent::__construct();
        $this->ReadDb = $this->load->database('ReadDB', TRUE);
    }

    /** Flush the ReadDb REPEATABLE READ snapshot so subsequent SELECTs see freshly committed data. */
    public function commitReadDb(): void {
        $this->ReadDb->simple_query('COMMIT');
    }

    public function getVendors(array $FilterArray, string $langCode = 'en'): array {

        $this->EndReturnData = new StdClass();
        try {

            $useLang = $langCode !== 'en';
            $lc      = $this->ReadDb->escape_str($langCode);
            $this->ReadDb->db_debug = FALSE;

            $select_ary = array(
                'Vendors.VendorUID AS VendorUID',
                'Vendors.SalutationUID AS SalutationUID',
                'Vendors.OrgUID AS OrgUID',
                $useLang ? 'COALESCE(VL.Name, Vendors.Name) AS Name' : 'Vendors.Name AS Name',
                $useLang ? 'COALESCE(VL.Area, Vendors.Area) AS Area' : 'Vendors.Area AS Area',
                'Vendors.CountryISO2 as CountryISO2',
                'Vendors.CountryCode as CountryCode',
                'Vendors.MobileNumber as MobileNumber',
                'Vendors.EmailAddress as EmailAddress',
                'Vendors.CustomerUID as CustomerUID',
                'Vendors.GSTIN as GSTIN',
                'Vendors.GSTINValidated as GSTINValidated',
                'Vendors.VendorNumber as VendorNumber',
                $useLang ? 'COALESCE(VL.CompanyName, Vendors.CompanyName) AS CompanyName' : 'Vendors.CompanyName as CompanyName',
                'Vendors.WorkPhone as WorkPhone',
                'Vendors.LandlineNumber as LandlineNumber',
                'Vendors.DebitCreditType as DebitCreditType',
                'Vendors.DebitCreditAmount as DebitCreditAmount',
                'Vendors.Image as Image',
                'Vendors.PANNumber as PANNumber',
                $useLang ? 'COALESCE(VL.ContactPerson, Vendors.ContactPerson) AS ContactPerson' : 'Vendors.ContactPerson as ContactPerson',
                'Vendors.DateOfBirth as DateOfBirth',
                $useLang ? 'COALESCE(VL.Notes, Vendors.Notes) AS Notes' : 'Vendors.Notes as Notes',
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
            if ($useLang) {
                $this->ReadDb->join("Vendors.VendorTbl_Lang AS VL", "VL.VendorUID = Vendors.VendorUID AND VL.LangCode = '{$lc}'", 'left');
            }
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
            notifyError('Vendors_model::getVendors', $e->getMessage());
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
            notifyError('Vendors_model::getVendorBankInfo', $e);
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);
        }

    }

    public function getVendorAddress(array $FilterArray, string $langCode = 'en'): array {

        $this->EndReturnData = new StdClass();
        try {

            $useLang = $langCode !== 'en';
            $lc      = $this->ReadDb->escape_str($langCode);
            $this->ReadDb->db_debug = FALSE;

            $select_ary = array(
                'VendAddress.VendAddressUID AS VendAddressUID',
                'VendAddress.OrgUID AS OrgUID',
                'VendAddress.VendorUID AS VendorUID',
                'VendAddress.AddressType AS AddressType',
                $useLang ? 'COALESCE(VAL.Line1, VendAddress.Line1) AS Line1' : 'VendAddress.Line1 as Line1',
                $useLang ? 'COALESCE(VAL.Line2, VendAddress.Line2) AS Line2' : 'VendAddress.Line2 as Line2',
                'VendAddress.Pincode as Pincode',
                'VendAddress.City as City',
                $useLang ? 'COALESCE(VAL.CityText, VendAddress.CityText) AS CityText' : 'VendAddress.CityText as CityText',
                'VendAddress.State as State',
                $useLang ? 'COALESCE(VAL.StateText, VendAddress.StateText) AS StateText' : 'VendAddress.StateText as StateText',
            );
            $WhereCondition = array(
                'VendAddress.IsDeleted' => 0,
                'VendAddress.IsActive' => 1,
            );

            $this->ReadDb->select($select_ary);
            $this->ReadDb->from('Vendors.VendAddressTbl as VendAddress');
            if ($useLang) {
                $this->ReadDb->join("Vendors.VendAddressTbl_Lang AS VAL", "VAL.VendAddressUID = VendAddress.VendAddressUID AND VAL.LangCode = '{$lc}'", 'left');
            }
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
            notifyError('Vendors_model::getVendorAddress', $e);
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
            $useLang          = (!empty($filter['LangCode']) && $filter['LangCode'] !== 'en');
            $nameField        = $useLang ? 'COALESCE(VL.Name,          Vendors.Name)          AS Name'          : 'Vendors.Name          AS Name';
            $contactField     = $useLang ? 'COALESCE(VL.ContactPerson, Vendors.ContactPerson) AS ContactPerson' : 'Vendors.ContactPerson  AS ContactPerson';
            $notesField       = $useLang ? 'COALESCE(VL.Notes,         Vendors.Notes)         AS Notes'         : 'Vendors.Notes          AS Notes';
            $areaField        = $useLang ? 'COALESCE(VL.Area,          Vendors.Area)          AS Area'          : 'Vendors.Area          AS Area';
            $companyNameField = $useLang ? 'COALESCE(VL.CompanyName,   Vendors.CompanyName)   AS CompanyName'   : 'Vendors.CompanyName   AS CompanyName';
            $this->ReadDb->select([
                'Vendors.VendorUID AS TablePrimaryUID',
                'Vendors.VendorUID AS VendorUID',
                'Vendors.OrgUID AS OrgUID',
                'Vendors.SalutationUID AS SalutationUID',
                'Sal.SalutationName AS SalutationName',
                $nameField,
                $areaField,
                'Vendors.CountryISO2 AS CountryISO2',
                'Vendors.CountryCode AS CountryCode',
                'Vendors.MobileNumber AS MobileNumber',
                'Vendors.EmailAddress AS EmailAddress',
                'Vendors.GSTIN AS GSTIN',
                'Vendors.GSTINValidated AS GSTINValidated',
                'Vendors.VendorNumber AS VendorNumber',
                $companyNameField,
                'Vendors.WorkPhone AS WorkPhone',
                'Vendors.LandlineNumber AS LandlineNumber',
                'Vendors.DebitCreditType AS DebitCreditType',
                'Vendors.DebitCreditAmount AS DebitCreditAmount',
                'Vendors.Image AS Image',
                'Vendors.PANNumber AS PANNumber',
                $contactField,
                $notesField,
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
            if ($useLang) {
                $lc = $this->ReadDb->escape($filter['LangCode']);
                $this->ReadDb->join(
                    'Vendors.VendorTbl_Lang AS VL',
                    "VL.VendorUID = Vendors.VendorUID AND VL.LangCode = {$lc}",
                    'left'
                );
            }
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
            notifyError('Vendors_model::getVendorListPaginated', $e);
            throw new Exception($e->getMessage());
        }

    }
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
            notifyError('Vendors_model::getVendorOpeningBalance', $e);
            throw new Exception($e->getMessage());
        }
    }
    public function updateVendorPendingBalance(int $orgUID, int $vendorUID, float $pendingBalance, string $pendingBalType, int $userUID): void {
        try {
            // COMMIT on ReadDb so the existence check sees the latest WriteDb state.
            $this->ReadDb->simple_query('COMMIT');
            $existing = $this->ReadDb->query(
                'SELECT VendBalUID FROM Vendors.VendOpeningBalanceTbl WHERE OrgUID = ? AND VendorUID = ? AND IsDeleted = 0 LIMIT 1',
                [(int)$orgUID, (int)$vendorUID]
            )->row();

            if ($existing) {
                $res = $this->dbwrite_model->updateData('Vendors', 'VendOpeningBalanceTbl', [
                    'PendingBalance' => (float)$pendingBalance,
                    'PendingBalType' => $pendingBalType,
                    'UpdatedBy'      => (int)$userUID,
                ], ['VendBalUID' => (int)$existing->VendBalUID]);
                if ($res->Error) throw new Exception($res->Message ?? 'Vendor pending balance update failed.');
            } else {
                // No opening balance row yet — create one so PendingBalance is always persisted.
                $res = $this->dbwrite_model->insertData('Vendors', 'VendOpeningBalanceTbl', [
                    'OrgUID'         => (int)$orgUID,
                    'VendorUID'      => (int)$vendorUID,
                    'OpeningBalance' => 0.0,
                    'OpeningBalType' => 'Credit',
                    'PendingBalance' => (float)$pendingBalance,
                    'PendingBalType' => $pendingBalType,
                    'IsActive'       => 1,
                    'IsDeleted'      => 0,
                    'CreatedBy'      => (int)$userUID,
                    'UpdatedBy'      => (int)$userUID,
                ]);
                if ($res->Error) throw new Exception($res->Message ?? 'Vendor pending balance insert failed.');
            }
        } catch (Exception $e) {
            notifyError('Vendors_model::updateVendorPendingBalance', $e);
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
                'T.PartyType' => 'S',
                'T.IsDeleted' => 0,
                'T.ModuleUID' => 108,
            ]);
            $this->ReadDb->where_not_in('T.DocStatus', ['Cancelled', 'Rejected']);
            $this->ReadDb->where('DN.IsCancelled', 0);
            $query = $this->ReadDb->get();
            if (!$query) throw new Exception($this->ReadDb->error()['message'] ?? 'DB error');
            return (float)$query->row()->total;
        } catch (Exception $e) {
            notifyError('Vendors_model::getVendorPRCoveredByDebitNote', $e);
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
            notifyError('Vendors_model::getVendorPendingNoteTotals', $e);
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
            notifyError('Vendors_model::getVendorDebitCreditRaw', $e);
            return null;
        }
    }

    // Applies a signed numeric delta (+/-) to the vendor running opening balance.
    // Returns ['balance' => float, 'type' => 'Debit'|'Credit'].
    // ── VendYearOpeningBalanceTbl (year-wise opening balance snapshot) ─────────

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
            notifyError('Vendors_model::getVendorsWithLedgerForBalance', $e);
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
            return $result;
        } catch (Exception $e) {
            notifyError('Vendors_model::getVendorClosingBalanceFresh', $e);
            return ['balance' => 0.0, 'balType' => 'Credit'];
        }
    }

    // Total net amount billed by vendor (Purchases, ModuleUID=105). PartyType='S' (Supplier).
    public function getVendorTotalPurchased(int $orgUID, int $vendorUID): float {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('COALESCE(SUM(NetAmount), 0) AS total');
            $this->ReadDb->from('`Transaction`.TransactionsTbl');
            $this->ReadDb->where([
                'OrgUID'    => (int)$orgUID,
                'PartyUID'  => (int)$vendorUID,
                'PartyType' => 'S',
                'ModuleUID' => 105,
                'IsDeleted' => 0,
            ]);
            $this->ReadDb->where_not_in('DocStatus', ['Cancelled', 'Rejected']);
            $query = $this->ReadDb->get();
            if (!$query) throw new Exception($this->ReadDb->error()['message'] ?? 'DB error');
            return (float) $query->row()->total;
        } catch (Exception $e) {
            notifyError('Vendors_model::getVendorTotalPurchased', $e);
            throw new Exception($e->getMessage());
        }
    }

    // Total amount paid out to vendor. PartyType='S' (Supplier).
    public function getVendorTotalPaid(int $orgUID, int $vendorUID): float {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('COALESCE(SUM(Amount), 0) AS total');
            $this->ReadDb->from('`Transaction`.PaymentsTbl');
            $this->ReadDb->where([
                'OrgUID'       => (int)$orgUID,
                'PartyUID'     => (int)$vendorUID,
                'PartyType'    => 'S',
                'IsCancelled'  => 0,
                'IsDeleted'    => 0,
            ]);
            $query = $this->ReadDb->get();
            if (!$query) throw new Exception($this->ReadDb->error()['message'] ?? 'DB error');
            return (float) $query->row()->total;
        } catch (Exception $e) {
            notifyError('Vendors_model::getVendorTotalPaid', $e);
            throw new Exception($e->getMessage());
        }
    }

    // Total net amount returned to vendor (Purchase Returns=108, Debit Notes=109). PartyType='S'.
    public function getVendorTotalReturned(int $orgUID, int $vendorUID): float {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('COALESCE(SUM(NetAmount), 0) AS total');
            $this->ReadDb->from('`Transaction`.TransactionsTbl');
            $this->ReadDb->where([
                'OrgUID'    => (int)$orgUID,
                'PartyUID'  => (int)$vendorUID,
                'PartyType' => 'S',
                'IsDeleted' => 0,
            ]);
            $this->ReadDb->where_in('ModuleUID', [108, 109]);
            $this->ReadDb->where_not_in('DocStatus', ['Cancelled', 'Rejected']);
            $query = $this->ReadDb->get();
            if (!$query) throw new Exception($this->ReadDb->error()['message'] ?? 'DB error');
            return (float) $query->row()->total;
        } catch (Exception $e) {
            notifyError('Vendors_model::getVendorTotalReturned', $e);
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
            notifyError('Vendors_model::updateVendorBalanceInLedger', $e);
            throw new Exception($e->getMessage());
        }
    }

    // Vendor Group model methods
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
            notifyError('Vendors_model::getVendorGroupStats', $e);
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
    public function getActiveVendorGroupsForDropdown(int $orgUID, string $langCode = 'en'): array {
        try {
            $useLang = $langCode !== 'en';
            $lc      = $this->ReadDb->escape_str($langCode);
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'vg.GroupUID',
                $useLang ? 'COALESCE(vgl.GroupName, vg.GroupName) AS GroupName' : 'vg.GroupName',
                'vg.GroupCode',
                'vg.GroupType',
            ]);
            $this->ReadDb->from('Vendors.VendorGroupTbl vg');
            if ($useLang) {
                $this->ReadDb->join("Vendors.VendorGroupTbl_Lang vgl", "vgl.GroupUID = vg.GroupUID AND vgl.LangCode = '{$lc}'", 'left');
            }
            $this->ReadDb->where(['vg.OrgUID' => (int)$orgUID, 'vg.IsActive' => 1, 'vg.IsDeleted' => 0]);
            $this->ReadDb->order_by('vg.GroupName', 'ASC');
            $query = $this->ReadDb->get();
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            notifyError('Vendors_model::getActiveVendorGroupsForDropdown', $e);
            return [];
        }
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
            notifyError('Vendors_model::getVendorGroupMembership', $e);
            return null;
        }
    }

    public function saveVendorGroupMembership(int $orgUID, int $vendorUID, int $groupUID, int $isGroupPrimary, int $userUID): void {
        $this->load->model('dbwrite_ext_model');
        $this->dbwrite_ext_model->execWrite(
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
        $this->dbwrite_model->updateData(
            'Vendors', 'VendorGroupMemberTbl',
            ['IsDeleted' => 1, 'UpdatedBy' => (int)$userUID],
            ['OrgUID' => (int)$orgUID, 'VendorUID' => (int)$vendorUID, 'IsDeleted' => 0]
        );
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
            notifyError('Vendors_model::getVendorsInOtherGroups', $e);
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
        } catch (Exception $e) {
            notifyError('Vendors_model::getVendorPrimaryImage', $e);
            return null;
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
            notifyError('Vendors_model::_calcFYYear', $e);
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
            notifyError('Vendors_model::getCreditSettings', $e);
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
    public function claimNextVendorNumber(int $orgUID, int $fyStartMonth, string $timezone): ?array {
        try {
            $this->load->model('dbwrite_ext_model');

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

                $affected = $this->dbwrite_ext_model->execWriteAffected(
                    "UPDATE Settings.OrgCreditSettingsTbl SET VendorSeq=?, VendorSeqYear=?, VendorNextNumber=?, UpdatedAt=? WHERE OrgUID=? AND VendorSeq=? AND VendorSeqYear=?",
                    [$nextSeq, $currentFYYear, $nextNum, date('Y-m-d H:i:s'), (int)$orgUID, $storedSeq, $storedFYYear]
                );

                if ($affected === 1) {
                    return ['claimed' => $claimedNum, 'next' => $nextNum];
                }
            }
            return null;
        } catch (Exception $e) {
            notifyError('Vendors_model::claimNextVendorNumber', $e);
            return null;
        }
    }

    /**
     * Upsert a translated row into VendorTbl_Lang.
     * LangCode 'en' is never passed — English lives in the base table only.
     * @param int         $vendorUID
     * @param string      $langCode
     * @param string|null $name
     * @param string|null $contactPerson
     * @param string|null $notes
     * @param int         $userUID
     * @returns void
     */
    public function saveVendLangRow(int $vendorUID, string $langCode, ?string $name, ?string $contactPerson, ?string $notes, ?string $area, ?string $companyName, int $userUID): void {
        $this->load->model('dbwrite_ext_model');
        $this->dbwrite_ext_model->execWrite(
            "INSERT INTO Vendors.VendorTbl_Lang
                (VendorUID, LangCode, Name, ContactPerson, Notes, Area, CompanyName, CreatedBy, UpdatedBy)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                Name=VALUES(Name), ContactPerson=VALUES(ContactPerson), Notes=VALUES(Notes),
                Area=VALUES(Area), CompanyName=VALUES(CompanyName), UpdatedBy=VALUES(UpdatedBy)",
            [$vendorUID, $langCode, $name, $contactPerson, $notes, $area, $companyName, $userUID, $userUID]
        );
    }

    /**
     * Upsert a translated row into VendAddressTbl_Lang.
     * @param int         $vendAddressUID
     * @param string      $langCode
     * @param string|null $line1
     * @param string|null $line2
     * @param string|null $cityText
     * @param string|null $stateText
     * @param int         $userUID
     * @returns void
     */
    public function saveVendAddrLangRow(int $vendAddressUID, string $langCode, ?string $line1, ?string $line2, ?string $cityText, ?string $stateText, int $userUID): void {
        $this->load->model('dbwrite_ext_model');
        $this->dbwrite_ext_model->execWrite(
            "INSERT INTO Vendors.VendAddressTbl_Lang
                (VendAddressUID, LangCode, Line1, Line2, CityText, StateText, CreatedBy, UpdatedBy)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                Line1=VALUES(Line1), Line2=VALUES(Line2), CityText=VALUES(CityText), StateText=VALUES(StateText), UpdatedBy=VALUES(UpdatedBy)",
            [$vendAddressUID, $langCode, $line1, $line2, $cityText, $stateText, $userUID, $userUID]
        );
    }

    /**
     * Upsert a translated row into VendorGroupTbl_Lang.
     * @param int         $groupUID
     * @param string      $langCode
     * @param string|null $groupName
     * @param string|null $contactPerson
     * @param string|null $notes
     * @param string|null $addrLine1
     * @param string|null $addrLine2
     * @param string|null $addrCity
     * @param string|null $addrState
     * @param int         $userUID
    public function getAttachmentSortStats(int $vendorUID, int $orgUID): ?object {
        try {
            $query = $this->ReadDb->query(
                "SELECT COALESCE(MAX(SortOrder), 0) AS ms, COUNT(*) AS cnt, COALESCE(SUM(FileSize), 0) AS ts
                   FROM Vendors.VendorAttachmentsTbl
                  WHERE VendorUID = ? AND OrgUID = ? AND IsDeleted = 0",
                [$vendorUID, $orgUID]
            );
            return $query ? $query->row() : null;
        } catch (Exception $e) {
            notifyError('Vendors_model::getAttachmentSortStats', $e);
            return null;
        }
    }
}
