<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Transactions_model extends CI_Model {
    
    private $EndReturnData;
    private $ReadDb;

	function __construct() {
        parent::__construct();

		$this->ReadDb = $this->load->database('ReadDB', TRUE);

    }

    public function getTransactionPageList($limit, $offset, $ModuleUID, $filter, $isCount = false) {

        try {

            $this->ReadDb->db_debug = FALSE;

            // ── Smart COUNT path ──────────────────────────────────────────────────
            // No expensive JOINs or correlated subqueries — only join what the
            // active filter actually needs. Everything else uses Ts.* columns only.
            if ($isCount) {
                $this->ReadDb->from('Transaction.TransactionsTbl as Ts');
                if (!empty($filter['Name'])) {
                    // Name search references Cust.Name and Cust.MobileNumber
                    $this->ReadDb->join('Customers.CustomerTbl as Cust', "Cust.CustomerUID = Ts.PartyUID AND Ts.PartyType = 'C'", 'LEFT');
                    $this->ReadDb->join('Vendors.VendorTbl as Vend',     "Vend.VendorUID   = Ts.PartyUID AND Ts.PartyType = 'S'", 'LEFT');
                }
                $this->ReadDb->where(['Ts.IsDeleted' => 0, 'Ts.IsActive' => 1, 'Ts.ModuleUID' => $ModuleUID]);
                $this->applyFilters($filter);
                return (int) $this->ReadDb->count_all_results();
            }
            // ─────────────────────────────────────────────────────────────────────

            $this->ReadDb->select([
                'Ts.TransUID AS TransUID',
                'Ts.TransToken AS TransToken',
                'Ts.ModuleUID AS ModuleUID',
                'Ts.PartyUID AS PartyUID',
                'Ts.UniqueNumber AS UniqueNumber',
                'Ts.TransNumber AS TransNumber',
                'Ts.TransDate AS TransDate',
                'Ts.DocStatus AS Status',
                'Ts.NetAmount AS NetAmount',
                'COALESCE(Cust.Name, Vend.Name) AS PartyName',
                'COALESCE(Cust.Area, Vend.Area) AS PartyArea',
                'COALESCE(Cust.MobileNumber, Vend.MobileNumber) AS MobileNumber',
                'COALESCE(Cust.CountryCode, Vend.CountryCode) AS CountryCode',
                'COALESCE(Cust.EmailAddress, Vend.EmailAddress) AS EmailAddress',
                'COALESCE(Cust.Image, Vend.Image) AS PartyImage',
                'Td.ValidityDate AS ValidityDate',
                'Ts.UpdatedOn AS UpdatedOn',
                "CONCAT(User.FirstName, ' ', User.LastName) AS UpdatedBy",
                'IFNULL(PaidSum.PaidAmount, 0) AS PaidAmount',
                "CONCAT(CreatedUser.FirstName, ' ', CreatedUser.LastName) AS CreatedBy",
                'IFNULL(PayInfo.PaymentCount, 0) AS PaymentCount',
                'PayInfo.PaymentModes AS PaymentModes',
                'PayInfo.PayBankName AS PayBankName',
                'PayInfo.PayAccountNumber AS PayAccountNumber',
                'IFNULL(PayInfo.PaymentAttachmentCount, 0) AS PaymentAttachmentCount',
                '(SELECT COUNT(*) FROM Transaction.TransAttachmentsTbl AT WHERE AT.TransUID = Ts.TransUID AND AT.IsDeleted = 0 AND AT.IsActive = 1) AS AttachmentCount',
            ]);
            $this->ReadDb->from('Transaction.TransactionsTbl as Ts');
            $this->ReadDb->join('Customers.CustomerTbl as Cust', 'Cust.CustomerUID = Ts.PartyUID AND Ts.PartyType = \'C\'', 'LEFT');
            $this->ReadDb->join('Vendors.VendorTbl as Vend', 'Vend.VendorUID = Ts.PartyUID AND Ts.PartyType = \'S\'', 'LEFT');
            $this->ReadDb->join('Transaction.TransDetailTbl as Td', 'Td.TransUID = Ts.TransUID AND Td.FinancialYear = YEAR(Ts.TransDate)', 'LEFT');
            $this->ReadDb->join('Users.UserTbl as User', 'User.UserUID = Ts.UpdatedBy', 'left');
            $this->ReadDb->join('Users.UserTbl as CreatedUser', 'CreatedUser.UserUID = Ts.CreatedBy', 'left');
            $this->ReadDb->join(
                '(SELECT TransUID, SUM(Amount) AS PaidAmount FROM Transaction.PaymentsTbl WHERE IsDeleted = 0 AND IsActive = 1 GROUP BY TransUID) AS PaidSum',
                'PaidSum.TransUID = Ts.TransUID',
                'LEFT'
            );
            $this->ReadDb->join(
                "(SELECT P.TransUID, COUNT(*) AS PaymentCount, GROUP_CONCAT(PT.Name ORDER BY P.PaymentUID ASC SEPARATOR ',') AS PaymentModes, MAX(CASE WHEN PT.IsCash = 0 THEN BA.BankName ELSE NULL END) AS PayBankName, MAX(CASE WHEN PT.IsCash = 0 THEN BA.AccountNumber ELSE NULL END) AS PayAccountNumber, (SELECT COUNT(*) FROM Transaction.PaymentAttachmentsTbl PA WHERE PA.PaymentUID IN (SELECT PaymentUID FROM Transaction.PaymentsTbl P2 WHERE P2.TransUID = P.TransUID AND P2.IsDeleted = 0 AND P2.IsActive = 1) AND PA.IsDeleted = 0 AND PA.IsActive = 1) AS PaymentAttachmentCount FROM Transaction.PaymentsTbl P JOIN Transaction.PaymentTypesTbl PT ON PT.PaymentTypeUID = P.PaymentTypeUID LEFT JOIN Transaction.OrgBankAccountsTbl BA ON BA.BankAccountUID = P.BankAccountUID WHERE P.IsDeleted = 0 AND P.IsActive = 1 GROUP BY P.TransUID) AS PayInfo",
                'PayInfo.TransUID = Ts.TransUID',
                'LEFT'
            );
            $this->ReadDb->where(['Ts.IsDeleted' => 0, 'Ts.IsActive' => 1, 'Ts.ModuleUID' => $ModuleUID]);
            $this->applyFilters($filter);
            $sortMap = ['Date' => 'Ts.TransDate', 'Amount' => 'Ts.NetAmount', 'Number' => 'Ts.TransNumber'];
            $sortCol = $sortMap[$filter['SortBy'] ?? ''] ?? 'Ts.TransUID';
            $sortDir = strtoupper($filter['SortDir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
            $this->ReadDb->order_by($sortCol, $sortDir);
            if ($limit > 0) {
                $this->ReadDb->limit($limit, $offset);
            }
            // print_r($this->ReadDb->get_compiled_select()); die;
            $query = $this->ReadDb->get();
            if (!$query) {
                $error = $this->ReadDb->error();
                throw new Exception($error['message']);
            }

            return $query->result();

        } catch (Exception $e) {
            return [];
        }

    }

    public function getTransactionCount($ModuleUID, $filter = []) {
        return $this->getTransactionPageList(0, 0, $ModuleUID, $filter, true);
    }

    /**
     * Returns per-status summary (count + total amount) for the stat cards.
     * Used by all module index() methods.
     */
    public function getTransactionSummaryStats($moduleUID, $orgUID) {
        try {
            $this->ReadDb->db_debug = FALSE;

            // Compute payment status from actual PaymentsTbl amounts so stats
            // match the list-view badges (which also use PaidAmount vs NetAmount).
            $sql = "
                SELECT
                    CASE
                        WHEN Ts.DocStatus IN ('Draft', 'Cancelled', 'Rejected') THEN Ts.DocStatus
                        WHEN IFNULL(PaidSum.PaidAmount, 0) >= Ts.NetAmount AND Ts.NetAmount > 0 THEN 'Paid'
                        WHEN IFNULL(PaidSum.PaidAmount, 0) > 0                                  THEN 'Partial'
                        ELSE Ts.DocStatus
                    END AS ComputedStatus,
                    COUNT(*)          AS TotalCount,
                    SUM(Ts.NetAmount) AS TotalAmount
                FROM Transaction.TransactionsTbl AS Ts
                LEFT JOIN (
                    SELECT TransUID, SUM(Amount) AS PaidAmount
                    FROM   Transaction.PaymentsTbl
                    WHERE  IsDeleted = 0 AND IsActive = 1
                    GROUP  BY TransUID
                ) AS PaidSum ON PaidSum.TransUID = Ts.TransUID
                WHERE Ts.ModuleUID = ? AND Ts.OrgUID = ? AND Ts.IsDeleted = 0
                GROUP BY ComputedStatus
            ";
            $query = $this->ReadDb->query($sql, [(int) $moduleUID, (int) $orgUID]);
            if (!$query) return [];

            $out = [];
            foreach ($query->result() as $r) {
                $out[$r->ComputedStatus] = ['count' => (int)$r->TotalCount, 'amount' => (float)$r->TotalAmount];
            }

            // Expired count: Pending + ValidityDate < today
            $this->ReadDb->select('COUNT(*) AS ExpiredCount, SUM(Ts.NetAmount) AS ExpiredAmount');
            $this->ReadDb->from('Transaction.TransactionsTbl AS Ts');
            $this->ReadDb->join('Transaction.TransDetailTbl AS Td', 'Td.TransUID = Ts.TransUID AND Td.FinancialYear = YEAR(Ts.TransDate)', 'LEFT');
            $this->ReadDb->where(['Ts.ModuleUID' => $moduleUID, 'Ts.OrgUID' => $orgUID, 'Ts.IsDeleted' => 0, 'Ts.DocStatus' => 'Pending']);
            $this->ReadDb->where('Td.ValidityDate <', date('Y-m-d'));
            $this->ReadDb->where('Td.ValidityDate IS NOT NULL', null, false);
            $expQuery = $this->ReadDb->get();
            if ($expQuery) {
                $expRow = $expQuery->row();
                $out['Expired'] = ['count' => (int)($expRow->ExpiredCount ?? 0), 'amount' => (float)($expRow->ExpiredAmount ?? 0)];
            }

            return $out;

        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Tab label → DB DocStatus mapping.
     * 'All' (or empty) excludes Draft.
     * Any other tab filters to a specific DocStatus.
     */
    private function applyFilters($filter) {

        if (empty($filter)) {
            $this->ReadDb->where_not_in('Ts.DocStatus', ['Draft', 'Rejected', 'Cancelled']);
            return;
        }

        if (!empty($filter['Name'])) {
            $this->ReadDb->group_start()
                ->like('Cust.Name', $filter['Name'], 'both')
                ->or_like('Cust.MobileNumber', $filter['Name'], 'both')
                ->or_like('Ts.TransNumber', $filter['Name'], 'both')
                ->or_like('Ts.UniqueNumber', $filter['Name'], 'both')
                ->group_end();
        }

        if (!empty($filter['DateFrom']) && !empty($filter['DateTo'])) {
            $this->ReadDb->where('Ts.TransDate >=', $filter['DateFrom']);
            $this->ReadDb->where('Ts.TransDate <=', $filter['DateTo']);
        }

        $tab = $filter['Status'] ?? 'All';

        if ($tab === 'InvPending') {
            // Invoice Pending = Issued + Partial (not yet fully paid)
            $this->ReadDb->where_in('Ts.DocStatus', ['Issued', 'Partial']);
        } elseif ($tab === 'SRPending' || $tab === 'PRPending') {
            // Sales/Purchase Return Pending = Approved + Partial (refund not yet settled)
            $this->ReadDb->where_in('Ts.DocStatus', ['Approved', 'Partial']);
        } elseif ($tab === 'Pending') {
            // Pending = Received + Partial (bills not yet fully paid)
            $this->ReadDb->where_in('Ts.DocStatus', ['Received', 'Partial']);
        } elseif ($tab === 'Received') {
            $this->ReadDb->where('Ts.DocStatus', 'Received');
        } elseif ($tab === 'Partial') {
            $this->ReadDb->where('Ts.DocStatus', 'Partial');
        } elseif ($tab === 'Open') {
            $this->ReadDb->where('Ts.DocStatus', 'Pending');
        } elseif ($tab === 'Accepted') {
            $this->ReadDb->where('Ts.DocStatus', 'Accepted');
        } elseif ($tab === 'Converted') {
            $this->ReadDb->where('Ts.DocStatus', 'Converted');
        } elseif ($tab === 'Paid') {
            $this->ReadDb->where('Ts.DocStatus', 'Paid');
        } elseif ($tab === 'Cancelled') {
            $this->ReadDb->where_in('Ts.DocStatus', ['Cancelled', 'Rejected']);
        } elseif ($tab === 'Draft') {
            $this->ReadDb->where('Ts.DocStatus', 'Draft');
        } else {
            // All — exclude Draft and Cancelled
            $this->ReadDb->where_not_in('Ts.DocStatus', ['Draft', 'Cancelled', 'Rejected']);
        }

        if (!empty($filter['StatusList']) && is_array($filter['StatusList'])) {
            $allowed = array_intersect($filter['StatusList'], ['Pending', 'Accepted', 'Converted', 'Draft', 'Cancelled', 'Rejected']);
            if (!empty($allowed)) {
                $this->ReadDb->where_in('Ts.DocStatus', array_values($allowed));
            }
        }

        if (!empty($filter['MinAmount'])) {
            $this->ReadDb->where('Ts.NetAmount >=', $filter['MinAmount']);
        }
        if (!empty($filter['MaxAmount'])) {
            $this->ReadDb->where('Ts.NetAmount <=', $filter['MaxAmount']);
        }

    }

    /** Full transaction header row by TransToken + OrgUID (token-based edit URL). */
    public function getTransactionByToken($token, $orgUID, $moduleUID) {
        $this->ReadDb->select([
            'Ts.*',
            'COALESCE(Cust.Name, Vend.Name) AS PartyName',
            'COALESCE(Cust.Area, Vend.Area) AS PartyArea',
            'COALESCE(Cust.CountryCode, Vend.CountryCode) AS PartyCountryCode',
            'COALESCE(Cust.MobileNumber, Vend.MobileNumber) AS PartyMobile',
            'COALESCE(Cust.GSTIN, Vend.GSTIN) AS PartyGSTIN',
            'BillAddr.Line1 AS BillLine1', 'BillAddr.Line2 AS BillLine2',
            'BillAddr.CityText AS BillCity', 'BillAddr.StateText AS BillState', 'BillAddr.Pincode AS BillPincode',
            'ShipAddr.Line1 AS ShipLine1', 'ShipAddr.Line2 AS ShipLine2',
            'ShipAddr.CityText AS ShipCity', 'ShipAddr.StateText AS ShipState', 'ShipAddr.Pincode AS ShipPincode',
            'Td.ValidityDays', 'Td.ValidityDate', 'Td.Reference', 'Td.SupplierInvoiceNo',
            'Td.Notes', 'Td.TermsConditions', 'Td.AdditionalCharges AS AdditionalChargesJson', 'Td.PlaceOfSupply',
        ]);
        $this->ReadDb->from('Transaction.TransactionsTbl AS Ts');
        $this->ReadDb->join('Customers.CustomerTbl AS Cust', 'Cust.CustomerUID = Ts.PartyUID AND Ts.PartyType = \'C\'', 'LEFT');
        $this->ReadDb->join('Vendors.VendorTbl AS Vend', 'Vend.VendorUID = Ts.PartyUID AND Ts.PartyType = \'S\'', 'LEFT');
        $this->ReadDb->join('Transaction.TransDetailTbl AS Td', 'Td.TransUID = Ts.TransUID AND Td.FinancialYear = YEAR(Ts.TransDate)', 'LEFT');
        $this->ReadDb->join('Customers.CustAddressTbl AS BillAddr', "BillAddr.CustomerUID = Ts.PartyUID AND BillAddr.AddressType = 'Billing' AND BillAddr.IsDeleted = 0 AND BillAddr.IsActive = 1", 'LEFT');
        $this->ReadDb->join('Customers.CustAddressTbl AS ShipAddr', "ShipAddr.CustomerUID = Ts.PartyUID AND ShipAddr.AddressType = 'Shipping' AND ShipAddr.IsDeleted = 0 AND ShipAddr.IsActive = 1", 'LEFT');
        $this->ReadDb->where(['Ts.TransToken' => $token, 'Ts.OrgUID' => $orgUID, 'Ts.ModuleUID' => $moduleUID, 'Ts.IsDeleted' => 0]);
        $row = $this->ReadDb->get()->row();
        return $row ?: null;
    }

    /** Full transaction header row by TransUID + OrgUID. */
    public function getTransactionById($transUID, $orgUID, $moduleUID) {
        $this->ReadDb->select([
            'Ts.*',
            'COALESCE(Cust.Name, Vend.Name) AS PartyName',
            'COALESCE(Cust.Area, Vend.Area) AS PartyArea',
            'COALESCE(Cust.CountryCode, Vend.CountryCode) AS PartyCountryCode',
            'COALESCE(Cust.MobileNumber, Vend.MobileNumber) AS PartyMobile',
            'COALESCE(Cust.GSTIN, Vend.GSTIN) AS PartyGSTIN',
            'BillAddr.Line1 AS BillLine1', 'BillAddr.Line2 AS BillLine2',
            'BillAddr.CityText AS BillCity', 'BillAddr.StateText AS BillState', 'BillAddr.Pincode AS BillPincode',
            'ShipAddr.Line1 AS ShipLine1', 'ShipAddr.Line2 AS ShipLine2',
            'ShipAddr.CityText AS ShipCity', 'ShipAddr.StateText AS ShipState', 'ShipAddr.Pincode AS ShipPincode',
            'Td.ValidityDays', 'Td.ValidityDate', 'Td.Reference', 'Td.SupplierInvoiceNo',
            'Td.Notes', 'Td.TermsConditions', 'Td.AdditionalCharges AS AdditionalChargesJson', 'Td.PlaceOfSupply',
            'Td.SignatureUID',
        ]);
        $this->ReadDb->from('Transaction.TransactionsTbl AS Ts');
        $this->ReadDb->join('Customers.CustomerTbl AS Cust', 'Cust.CustomerUID = Ts.PartyUID AND Ts.PartyType = \'C\'', 'LEFT');
        $this->ReadDb->join('Vendors.VendorTbl AS Vend', 'Vend.VendorUID = Ts.PartyUID AND Ts.PartyType = \'S\'', 'LEFT');
        $this->ReadDb->join('Transaction.TransDetailTbl AS Td', 'Td.TransUID = Ts.TransUID AND Td.FinancialYear = YEAR(Ts.TransDate)', 'LEFT');
        $this->ReadDb->join('Customers.CustAddressTbl AS BillAddr', "BillAddr.CustomerUID = Ts.PartyUID AND BillAddr.AddressType = 'Billing' AND BillAddr.IsDeleted = 0 AND BillAddr.IsActive = 1", 'LEFT');
        $this->ReadDb->join('Customers.CustAddressTbl AS ShipAddr', "ShipAddr.CustomerUID = Ts.PartyUID AND ShipAddr.AddressType = 'Shipping' AND ShipAddr.IsDeleted = 0 AND ShipAddr.IsActive = 1", 'LEFT');
        $this->ReadDb->where(['Ts.TransUID' => $transUID, 'Ts.OrgUID' => $orgUID, 'Ts.ModuleUID' => $moduleUID, 'Ts.IsDeleted' => 0]);
        return $this->ReadDb->get()->row();

    }

    /** Returns true if any active transaction with a higher TransUID exists for this org+module. */
    public function hasNewerTransactions($transUID, $orgUID, $moduleUID) {
        $this->ReadDb->select('TransUID');
        $this->ReadDb->from('Transaction.TransactionsTbl');
        $this->ReadDb->where(['OrgUID' => $orgUID, 'ModuleUID' => $moduleUID, 'IsDeleted' => 0]);
        $this->ReadDb->where('TransUID >', $transUID);
        $this->ReadDb->limit(1);
        $row = $this->ReadDb->get()->row();
        return $row !== null;
    }

    /** Max ItemSequence ever used for a transaction (including soft-deleted rows). */
    public function getMaxItemSequence($transUID) {
        $this->ReadDb->select_max('ItemSequence', 'MaxSeq');
        $this->ReadDb->from('Transaction.TransProductsTbl');
        $this->ReadDb->where('TransUID', $transUID);
        $row = $this->ReadDb->get()->row();
        return $row ? (int) $row->MaxSeq : 0;
    }

    /** All active line items for a transaction. */
    public function getTransactionItems($transUID, $orgUID) {

        $this->ReadDb->select([
            'Tprod.*',
            'Product.HSNSACCode AS HSNCode',
        ]);
        $this->ReadDb->from('Transaction.TransProductsTbl as Tprod');
        $this->ReadDb->join('Products.ProductTbl AS Product', 'Product.ProductUID = Tprod.ProductUID', 'LEFT');
        $this->ReadDb->where(['Tprod.TransUID' => $transUID, 'Tprod.OrgUID' => $orgUID, 'Tprod.IsDeleted' => 0]);
        $this->ReadDb->order_by('Tprod.ItemSequence', 'ASC');
        return $this->ReadDb->get()->result();

    }

    /** All tax rows for a transaction's items. */
    public function getTransactionItemTaxes($transUID) {

        $this->ReadDb->from('Transaction.TransProdTaxesTbl');
        $this->ReadDb->where(['TransUID' => $transUID, 'IsDeleted' => 0]);
        return $this->ReadDb->get()->result();
        
    }

    /**
     * Fetch prefix rows.
     * $FilterArray may include OrgUID (required for org-scoped queries).
     * ModuleUID is NO LONGER used as a filter — prefixes are org-level and
     * shared across all transaction types.  The caller should pass at minimum:
     *   ['Prefix.OrgUID' => $orgUID]
     * For a specific prefix by PK, pass:
     *   ['Prefix.PrefixUID' => $prefixUID]
     */
    public function getTransactionsPrefixDetails($FilterArray) {

        $this->EndReturnData = new stdClass();
        try {

            $this->ReadDb->select([
                'Prefix.PrefixUID          as PrefixUID',
                'Prefix.OrgUID             as OrgUID',
                'Prefix.Name               as Name',
                'Prefix.IncludeFiscalYear  as IncludeFiscalYear',
                'Prefix.FiscalYearFormat   as FiscalYearFormat',
                'Prefix.IncludeShortName   as IncludeShortName',
                'Prefix.ShortName          as ShortName',
                'Prefix.Separator          as Separator',
                'Prefix.NumberPadding      as NumberPadding',
                'Prefix.IsDefault          as IsDefault',
            ]);
            $this->ReadDb->from('Transaction.TransactionPrefixTbl as Prefix');
            $cleanFilter = $FilterArray;
            if (!empty($cleanFilter)) {
                $this->ReadDb->where($cleanFilter);
            }
            $this->ReadDb->where(['Prefix.IsDeleted' => 0, 'Prefix.IsActive' => 1]);
            $this->ReadDb->order_by('Prefix.IsDefault', 'DESC');
            $this->ReadDb->order_by('Prefix.PrefixUID', 'ASC');

            $query = $this->ReadDb->get();
            $error = $this->ReadDb->error();
            if ($error['code']) {
                throw new Exception($error['message']);
            }

            $this->EndReturnData->Data    = $query->result();
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Success';

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        return $this->EndReturnData;

    }

    /**
     * Returns active locations for an org, default first.
     */
    public function getOrgLocations($orgUID) {

        $this->EndReturnData = new stdClass();
        try {

            $this->ReadDb->select([
                'LocationUID',
                'BranchName',
                'LocationCode',
                'LocationName',
                'IsDefault',
            ]);
            $this->ReadDb->from('Global.OrgLocationsTbl');
            $this->ReadDb->where(['OrgUID' => (int)$orgUID, 'IsDeleted' => 0, 'IsActive' => 1]);
            $this->ReadDb->order_by('IsDefault', 'DESC');
            $this->ReadDb->order_by('LocationName', 'ASC');

            $query = $this->ReadDb->get();
            $error = $this->ReadDb->error();
            if ($error['code']) {
                throw new Exception($error['message']);
            }

            $this->EndReturnData->Data    = $query->result();
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Success';

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        return $this->EndReturnData;

    }

    /**
     * Returns the next sequential TransNumber for a given prefix + org + module.
     * Result = MAX(TransNumber) + 1, or 1 if no records exist yet.
     */
    public function getNextTransactionNumber($prefixUID, $orgUID, $moduleUID) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select_max('TransNumber', 'MaxNumber');
            $this->ReadDb->from('Transaction.TransactionsTbl');
            $this->ReadDb->where([
                'OrgUID'    => (int) $orgUID,
                'ModuleUID' => (int) $moduleUID,
                'PrefixUID' => (int) $prefixUID,
            ]);
            $result = $this->ReadDb->get()->row();
            return $result ? ((int)($result->MaxNumber ?? 0) + 1) : 1;
        } catch (Exception $e) {
            return 1;
        }
    }

    /**
     * Returns the next sequential PaymentNumber for a given prefix + org + year.
     * Scans ALL rows (including soft-deleted) so a previously-used number is
     * never re-issued.
     */
    public function getNextPaymentNumber($prefixUID, $orgUID, $transYear) {

        try {

            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->reset_query();

            $this->ReadDb->select_max('PaymentNumber', 'MaxNumber');
            $this->ReadDb->from('Transaction.PaymentsTbl');
            $this->ReadDb->where([
                'PrefixUID' => (int) $prefixUID,
                'OrgUID'    => (int) $orgUID,
                'TransYear' => (int) $transYear,
            ]);
            $query  = $this->ReadDb->get();
            $result = $query->row();
            $next   = $result ? ((int)($result->MaxNumber ?? 0) + 1) : 1;

            // Safety loop: skip any number that still has an active row
            $maxAttempts = 100;
            while ($maxAttempts-- > 0) {
                $this->ReadDb->reset_query();
                $this->ReadDb->select('PaymentUID');
                $this->ReadDb->from('Transaction.PaymentsTbl');
                $this->ReadDb->where([
                    'PrefixUID'     => (int) $prefixUID,
                    'OrgUID'        => (int) $orgUID,
                    'TransYear'     => (int) $transYear,
                    'PaymentNumber' => $next,
                    'IsDeleted'     => 0,
                ]);
                $this->ReadDb->limit(1);
                if (!$this->ReadDb->get()->row()) break;
                $next++;
            }

            return $next;

        } catch (Exception $e) {
            return 1;
        }

    }

    /**
     * Checks whether a TransNumber already exists for a given prefix within
     * the same org+module.  Returns the matching row or NULL.
     */
    public function getTransactionByPrefixAndNumber($prefixUID, $transNumber, $orgUID, $moduleUID) {

        try {

            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->reset_query();
            $this->ReadDb->select('TransUID');
            $this->ReadDb->from('Transaction.TransactionsTbl');
            $this->ReadDb->where([
                'PrefixUID'   => (int) $prefixUID,
                'TransNumber' => (int) $transNumber,
                'OrgUID'      => (int) $orgUID,
                'IsDeleted'   => 0,
            ]);
            $this->ReadDb->limit(1);
            $query = $this->ReadDb->get();
            return $query->row();

        } catch (Exception $e) {
            return NULL;
        }

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
                'MAX(COALESCE(Ship.StateText, Bill.StateText)) AS StateText',
                'MAX(COALESCE(COA.CurrentBalance, 0)) AS CustomerBalance',
                "MAX(COALESCE(COA.CurrentBalanceType, 'Debit')) AS BalanceType",
            );
            $where_ary = array(
                'Customers.IsDeleted' => 0,
                'Customers.IsActive' => 1,
            );
            $this->ReadDb->select($select_ary);
            $this->ReadDb->from('Customers.CustomerTbl as Customers');
            $this->ReadDb->join('Customers.CustAddressTbl as Bill', 'Bill.CustomerUID = Customers.CustomerUID AND Bill.IsDeleted = 0 AND Bill.IsActive = 1', 'LEFT');
            $this->ReadDb->join('Customers.CustAddressTbl as Ship', 'Ship.CustomerUID = Customers.CustomerUID AND Ship.IsDeleted = 0 AND Ship.IsActive = 1', 'LEFT');
            $this->ReadDb->join('Accounting.EntityLedgerMap AS ELM', "ELM.CustomerUID = Customers.CustomerUID AND ELM.EntityType = 'Customer' AND ELM.IsDeleted = 0", 'LEFT');
            $this->ReadDb->join('Accounting.ChartOfAccounts AS COA', 'COA.LedgerUID = ELM.LedgerUID', 'LEFT');
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

    // Returns the ISO-2 country code for a customer (e.g. 'IN', 'US'). NULL if not found.
    // Uses CountryISO2 — CountryCode stores the full country name, not the ISO code.
    public function getCustomerCountryCode(int $customerUID) {
        if ($customerUID <= 0) return NULL;
        $this->ReadDb->select('CountryISO2');
        $this->ReadDb->from('Customers.CustomerTbl');
        $this->ReadDb->where('CustomerUID', $customerUID);
        $this->ReadDb->where('IsDeleted', 0);
        $this->ReadDb->limit(1);
        $row = $this->ReadDb->get()->row();
        return $row ? ($row->CountryISO2 ?: NULL) : NULL;
    }

    // Returns the billing address StateText for a customer — used as PlaceOfSupply on invoices.
    public function getCustomerBillingState(int $customerUID): ?string {
        if ($customerUID <= 0) return NULL;
        $this->ReadDb->select('StateText');
        $this->ReadDb->from('Customers.CustAddressTbl');
        $this->ReadDb->where([
            'CustomerUID' => $customerUID,
            'AddressType' => 'Billing',
            'IsDeleted'   => 0,
            'IsActive'    => 1,
        ]);
        $this->ReadDb->limit(1);
        $row = $this->ReadDb->get()->row();
        return $row ? ($row->StateText ?: NULL) : NULL;
    }

    public function getTransProductsDetails(string $Term = '', $WhereCondition = []) {

        $this->EndReturnData = new StdClass();
        try {

            $this->ReadDb->db_debug = FALSE;
            $select_ary = array(
                'product.ProductUID AS ProductUID',
                'product.ItemName AS ItemName',
                'product.ProductType AS ProductType',
                'product.SellingPrice AS UnitPrice',
                'product.SellingPrice AS SellingPrice',
                'product.PurchasePrice AS PurchasePrice',
                'product.TaxPercentage AS TaxPercentage',
                'product.CGST AS CGST',
                'product.SGST AS SGST',
                'product.IGST AS IGST',
                'product.CategoryUID AS CategoryUID',
                'category.Name AS CatgName',
                'product.HSNSACCode AS HSNSACCode',
                'COALESCE(productStock.AvailableQty, 0) AS AvailableQuantity',
                'product.Discount AS Discount',
                'product.DiscountTypeUID AS DiscountTypeUID',
                'discountType.Name AS DiscountTypeName',
                'primaryUnit.ShortName AS priUnitShortName',
                'product.PartNumber AS PartNumber',
                'product.Description AS Description',
                'product.IsComboItem AS IsComboItem',
                '(SELECT COUNT(*) FROM Products.ProductBOMTbl pc WHERE pc.ParentProductUID = product.ProductUID AND pc.IsDeleted = 0 AND pc.IsActive = 1) AS ComboItemCount',
            );
            $where_ary = array(
                'product.IsDeleted' => 0,
                'product.IsActive' => 1,
            );
            $this->ReadDb->select($select_ary);
            $this->ReadDb->from('Products.ProductTbl as product');
            $this->ReadDb->join('Products.ProductStockTbl as productStock', 'productStock.ProductUID = product.ProductUID', 'LEFT');
            $this->ReadDb->join('Products.CategoryTbl as category', 'category.CategoryUID = product.CategoryUID AND category.IsDeleted = 0 AND category.IsActive = 1', 'LEFT');
            $this->ReadDb->join('Global.DiscountTypeTbl as discountType', 'discountType.DiscountTypeUID = product.DiscountTypeUID AND discountType.IsDeleted = 0 AND discountType.IsActive = 1', 'LEFT');
            $this->ReadDb->join('Global.PrimaryUnitTbl as primaryUnit', 'primaryUnit.PrimaryUnitUID = product.PrimaryUnitUID', 'LEFT');
            if(!empty($Term)) {
                $this->ReadDb->group_start();
                $this->ReadDb->or_like('product.ItemName', $Term, 'both');
                $this->ReadDb->or_like('product.PartNumber', $Term, 'both');
                $this->ReadDb->group_end();
            }
            if(sizeof($WhereCondition) > 0) {
                $this->ReadDb->where($WhereCondition);
            }
            $this->ReadDb->where($where_ary);
            $this->ReadDb->group_by('product.ProductUID');
            $this->ReadDb->limit(30);

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

    public function getEntityInvoices($entityId, $entityType) {

        try {

            $this->ReadDb->select('TransUID, UniqueNumber, ModuleUID, TransType, PartyType');
            $this->ReadDb->from('Transaction.TransactionsTbl');
            $this->ReadDb->where(['PartyUID' => $entityId, 'IsDeleted' => 0, 'IsActive' => 1]);
            if($entityType == 'Customer') {
                $this->ReadDb->where('PartyType', 'C');
            } else if($entityType == 'Vendor') {
                $this->ReadDb->where('PartyType', 'S');
            }
            $this->ReadDb->limit(10);
            $query = $this->ReadDb->get();
            
            return $query->result();
            
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }

    public function getCustomerPayments($customerId) {

        try {

            $this->ReadDb->select('PaymentUID, PaymentNo, Amount, PaymentDate');
            $this->ReadDb->from('Accounting.PaymentsTbl');
            $this->ReadDb->where(['CustomerUID' => $customerId, 'IsDeleted' => 0, 'IsActive' => 1]);
            $this->ReadDb->limit(10);
            $query = $this->ReadDb->get();
            
            return $query->result();
            
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }

    public function getCustomerOrders($customerId) {

        try {

            $this->ReadDb->select('OrderUID, OrderNo, TotalAmount, OrderDate');
            $this->ReadDb->from('Orders.OrderTbl');
            $this->ReadDb->where(['CustomerUID' => $customerId, 'IsDeleted' => 0, 'IsActive' => 1]);
            $this->ReadDb->limit(10);
            $query = $this->ReadDb->get();

            return $query->result();

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }

    // ── Payment Methods ──────────────────────────────────────────

    public function getPaymentTypesList() {
        try {
            $key    = $this->redisservice->orgKey('payment-types');
            $cached = $this->upstashservice->get($key);
            if ($cached !== null) return array_map(fn($r) => is_array($r) ? (object) $r : $r, $cached);

            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('PaymentTypeUID, Name, Code, IsCash');
            $this->ReadDb->from('Transaction.PaymentTypesTbl');
            $this->ReadDb->where(['IsDeleted' => 0, 'IsActive' => 1]);
            $this->ReadDb->order_by('PaymentTypeUID', 'ASC');
            $query = $this->ReadDb->get();
            if (!$query) return [];
            $data = $query->result();
            $this->upstashservice->set($key, $data, 0);
            return $data;
        } catch (Exception $e) {
            return [];
        }
    }

    // Returns ONE bank account for print templates.
    // Priority: IsDefault=1 & IsCash=0  →  any IsCash=0  →  NULL
    public function getPrintBankAccount($orgUID) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $base = ['OrgUID' => $orgUID, 'IsDeleted' => 0, 'IsActive' => 1, 'IsCash' => 0];

            // 1. Default non-cash account
            $this->ReadDb->select('BankAccountUID, AccountName, BankName, AccountNumber, IFSC, BranchName, UPIId, UPINumber, IsDefault, IsCash');
            $this->ReadDb->from('Transaction.OrgBankAccountsTbl');
            $this->ReadDb->where(array_merge($base, ['IsDefault' => 1]));
            $this->ReadDb->limit(1);
            $row = $this->ReadDb->get()->row();
            if ($row) return $row;

            // 2. Any non-cash account
            $this->ReadDb->select('BankAccountUID, AccountName, BankName, AccountNumber, IFSC, BranchName, UPIId, UPINumber, IsDefault, IsCash');
            $this->ReadDb->from('Transaction.OrgBankAccountsTbl');
            $this->ReadDb->where($base);
            $this->ReadDb->order_by('IsDefault', 'DESC');
            $this->ReadDb->limit(1);
            $row = $this->ReadDb->get()->row();
            return $row ?: NULL;

        } catch (Exception $e) {
            return NULL;
        }
    }

    public function getOrgBankAccounts($orgUID) {
        try {
            $key    = $this->redisservice->orgKey('org-bank-accounts');
            $cached = $this->upstashservice->get($key);
            if ($cached !== null) return array_map(fn($r) => is_array($r) ? (object) $r : $r, $cached);

            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('BankAccountUID, AccountName, BankName, AccountNumber, IFSC, BranchName, UPIId, UPINumber, IsDefault, IsCash');
            $this->ReadDb->from('Transaction.OrgBankAccountsTbl');
            $this->ReadDb->where(['OrgUID' => $orgUID, 'IsDeleted' => 0, 'IsActive' => 1]);
            $this->ReadDb->order_by('IsDefault', 'DESC');
            $this->ReadDb->order_by('AccountName', 'ASC');
            $query = $this->ReadDb->get();
            if (!$query) return [];
            $data = $query->result();
            $this->upstashservice->set($key, $data, 0);
            return $data;
        } catch (Exception $e) {
            return [];
        }
    }

    public function getTransactionPayments($transUID, $orgUID) {

        try {

            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'P.PaymentUID',
                'P.Amount',
                'P.ReferenceNo',
                'P.Notes',
                'P.IsFullyPaid',
                'P.ExcessAmount',
                'P.CreatedOn',
                'PT.Name AS PaymentTypeName',
                'PT.IsCash',
                'BA.AccountName',
                'BA.BankName',
                'BA.AccountNumber',
            ]);
            $this->ReadDb->from('Transaction.PaymentsTbl AS P');
            $this->ReadDb->join('Transaction.PaymentTypesTbl AS PT', 'PT.PaymentTypeUID = P.PaymentTypeUID', 'LEFT');
            $this->ReadDb->join('Transaction.OrgBankAccountsTbl AS BA', 'BA.BankAccountUID = P.BankAccountUID', 'LEFT');
            $this->ReadDb->where(['P.TransUID' => $transUID, 'P.OrgUID' => $orgUID, 'P.IsDeleted' => 0]);
            $this->ReadDb->order_by('P.PaymentUID', 'ASC');
            $query = $this->ReadDb->get();
            if (!$query) return [];
            return $query->result();

        } catch (Exception $e) {
            return [];
        }

    }

    /** Single payment row for pre-deletion checks (no joins). */
    public function getPaymentRow($paymentUID, $orgUID) {
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->select('PaymentUID, TransUID, Amount, PartyType, PartyUID, IsOnAccount, OnAccountAppliedTransUID, OnAccountSourcePaymentUID');
        $this->ReadDb->from('Transaction.PaymentsTbl');
        $this->ReadDb->where(['PaymentUID' => $paymentUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]);
        $query = $this->ReadDb->get();
        return ($query && $query->num_rows() > 0) ? $query->row() : null;
    }

    /** SUM of active (IsDeleted=0, IsActive=1) payments for a transaction. */
    public function getSumPaidForTransaction($transUID, $orgUID) {
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->select('COALESCE(SUM(Amount), 0) AS TotalPaid');
        $this->ReadDb->from('Transaction.PaymentsTbl');
        $this->ReadDb->where(['TransUID' => $transUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0, 'IsActive' => 1]);
        $query = $this->ReadDb->get();
        $row   = ($query && $query->num_rows() > 0) ? $query->row() : null;
        return $row ? (float) $row->TotalPaid : 0;
    }

    /** Minimal transaction header: NetAmount + DocStatus + UniqueNumber (for balance recalculation). */
    public function getTransactionBasicInfo($transUID, $orgUID) {
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->select('TransUID, NetAmount, DocStatus, UniqueNumber');
        $this->ReadDb->from('Transaction.TransactionsTbl');
        $this->ReadDb->where(['TransUID' => $transUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]);
        $query = $this->ReadDb->get();
        return ($query && $query->num_rows() > 0) ? $query->row() : null;
    }

    /** Full payment detail with all joins for the view modal. */
    public function getPaymentDetailById($paymentUID, $orgUID) {
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->select([
            'P.PaymentUID', 'P.TransUID', 'P.PartyType', 'P.Amount', 'P.ExcessAmount',
            'P.IsFullyPaid', 'P.ReferenceNo', 'P.Notes', 'P.CreatedOn',
            'P.PaymentDate', 'P.UniqueNumber', 'P.PaymentNumber', 'P.TransYear', 'P.ReceiptToken', 'P.ModuleUID',
            'PT.Name AS PaymentTypeName', 'PT.IsCash',
            'T.UniqueNumber AS TransNumber', 'T.TransDate', 'T.NetAmount AS BillAmount', 'T.BalanceAmount',
            "CASE WHEN P.PartyType = 'C' THEN C.Name ELSE V.Name END AS PartyName",
            "CASE WHEN P.PartyType = 'C' THEN C.MobileNumber ELSE V.MobileNumber END AS PartyMobile",
            'BA.AccountName', 'BA.BankName', 'BA.AccountNumber', 'BA.IFSC', 'BA.BranchName', 'BA.UPIId', 'BA.UPINumber',
            "CONCAT(CrUser.FirstName, ' ', CrUser.LastName) AS CreatedByName",
        ]);
        $this->ReadDb->from('Transaction.PaymentsTbl AS P');
        $this->ReadDb->join('Transaction.PaymentTypesTbl AS PT', 'PT.PaymentTypeUID = P.PaymentTypeUID', 'LEFT');
        $this->ReadDb->join('Transaction.TransactionsTbl AS T', 'T.TransUID = P.TransUID AND T.IsDeleted = 0', 'LEFT');
        $this->ReadDb->join('Customers.CustomerTbl AS C', "C.CustomerUID = P.PartyUID AND P.PartyType = 'C'", 'LEFT');
        $this->ReadDb->join('Vendors.VendorTbl AS V', "V.VendorUID = P.PartyUID AND P.PartyType = 'S'", 'LEFT');
        $this->ReadDb->join('Transaction.OrgBankAccountsTbl AS BA', 'BA.BankAccountUID = P.BankAccountUID', 'LEFT');
        $this->ReadDb->join('Users.UserTbl AS CrUser', 'CrUser.UserUID = P.CreatedBy', 'LEFT');
        $this->ReadDb->where(['P.PaymentUID' => $paymentUID, 'P.OrgUID' => $orgUID]);
        $query = $this->ReadDb->get();
        return ($query && $query->num_rows() > 0) ? $query->row() : null;
    }

    public function getTransactionAttachments($transUID, $orgUID) {
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->select('AttachUID, FileName, FilePath, FileType, FileSize, CreatedOn');
        $this->ReadDb->from('Transaction.TransAttachmentsTbl');
        $this->ReadDb->where(['TransUID' => $transUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]);
        $this->ReadDb->order_by('SortOrder', 'ASC');
        $this->ReadDb->order_by('AttachUID', 'ASC');
        $query = $this->ReadDb->get();
        return ($query && $query->num_rows() > 0) ? $query->result() : [];
    }

    public function getPaymentAttachments($paymentUID, $orgUID) {
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->select('AttachUID, FileName, FilePath, FileType, FileSize, CreatedOn');
        $this->ReadDb->from('Transaction.PaymentAttachmentsTbl');
        $this->ReadDb->where(['PaymentUID' => $paymentUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]);
        $this->ReadDb->order_by('SortOrder', 'ASC');
        $this->ReadDb->order_by('AttachUID', 'ASC');
        $query = $this->ReadDb->get();
        return ($query && $query->num_rows() > 0) ? $query->result() : [];
    }

    public function getPaymentsList($limit, $offset, $orgUID, $filter) {

        try {

            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'P.PaymentUID',
                'P.TransUID',
                'P.PartyUID',
                'P.ModuleUID',
                'P.PartyType',
                'P.Amount',
                'P.IsFullyPaid',
                'P.ExcessAmount',
                'P.UniqueNumber as PaymentUniqueNumber',
                'P.Notes',
                'P.CreatedOn',
                'P.ReceiptToken',
                'PT.Name AS PaymentTypeName',
                'PT.IsCash',
                'PT.Code AS PaymentTypeCode',
                'T.UniqueNumber as TransNumber',
                'T.TransDate',
                'T.NetAmount AS BillAmount',
                "CASE WHEN P.PartyType = 'C' THEN C.Name ELSE V.Name END AS PartyName",
                "CASE WHEN P.PartyType = 'C' THEN C.Area ELSE V.Area END AS PartyArea",
                "CASE WHEN P.PartyType = 'C' THEN C.MobileNumber ELSE V.MobileNumber END AS PartyMobile",
                "CASE WHEN P.PartyType = 'C' THEN C.CountryCode ELSE V.CountryCode END AS PartyCountryCode",
                "CASE WHEN P.PartyType = 'C' THEN C.EmailAddress ELSE V.EmailAddress END AS PartyEmail",
                'BA.AccountName',
                'BA.BankName',
                'BA.AccountNumber',
                "CONCAT(CrUser.FirstName, ' ', CrUser.LastName) AS CreatedByName",
            ]);
            $this->ReadDb->from('Transaction.PaymentsTbl AS P');
            $this->ReadDb->join('Transaction.PaymentTypesTbl AS PT', 'PT.PaymentTypeUID = P.PaymentTypeUID', 'LEFT');
            $this->ReadDb->join('Transaction.TransactionsTbl AS T', 'T.TransUID = P.TransUID AND T.IsDeleted = 0', 'LEFT');
            $this->ReadDb->join('Customers.CustomerTbl AS C', "C.CustomerUID = P.PartyUID AND P.PartyType = 'C'", 'LEFT');
            $this->ReadDb->join('Vendors.VendorTbl AS V', "V.VendorUID = P.PartyUID AND P.PartyType = 'S'", 'LEFT');
            $this->ReadDb->join('Transaction.OrgBankAccountsTbl AS BA', 'BA.BankAccountUID = P.BankAccountUID', 'LEFT');
            $this->ReadDb->join('Users.UserTbl AS CrUser', 'CrUser.UserUID = P.CreatedBy', 'LEFT');

            $isCancelled = (!empty($filter['Status']) && $filter['Status'] === 'Cancelled');
            if ($isCancelled) {
                $this->ReadDb->where(['P.OrgUID' => $orgUID, 'P.IsDeleted' => 1]);
            } else {
                $this->ReadDb->where(['P.OrgUID' => $orgUID, 'P.IsDeleted' => 0, 'P.IsActive' => 1]);
            }
            if (!empty($filter['PaymentDirection'])) {
                $this->ReadDb->where('P.PaymentDirection', $filter['PaymentDirection']);
            }
            if (!empty($filter['PartyType'])) {
                $this->ReadDb->where('P.PartyType', $filter['PartyType']);
            }
            if (!empty($filter['ModuleUID'])) {
                $this->ReadDb->where('P.PaymentModuleUID', (int)$filter['ModuleUID']);
            }
            if (!empty($filter['PaymentSource'])) {
                $this->ReadDb->where('P.PaymentSource', $filter['PaymentSource']);
            }
            if (!empty($filter['DateFrom'])) {
                $this->ReadDb->where('DATE(P.CreatedOn) >=', $filter['DateFrom']);
            }
            if (!empty($filter['DateTo'])) {
                $this->ReadDb->where('DATE(P.CreatedOn) <=', $filter['DateTo']);
            }
            if (!empty($filter['Search'])) {
                $s = $filter['Search'];
                $this->ReadDb->group_start();
                $this->ReadDb->or_like('T.UniqueNumber', $s, 'both');
                $this->ReadDb->or_like('C.Name', $s, 'both');
                $this->ReadDb->or_like('V.Name', $s, 'both');
                $this->ReadDb->group_end();
            }

            $this->ReadDb->order_by('P.PaymentUID', 'DESC');
            if ($limit > 0) {
                $this->ReadDb->limit($limit, $offset);
            }
            $query = $this->ReadDb->get();
            if (!$query) return [];
            return $query->result();

        } catch (Exception $e) {
            return [];
        }

    }

    public function getPaymentsCount($orgUID, $filter) {

        try {

            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->from('Transaction.PaymentsTbl AS P');

            $isCancelled = (!empty($filter['Status']) && $filter['Status'] === 'Cancelled');
            if ($isCancelled) {
                $this->ReadDb->where(['P.OrgUID' => $orgUID, 'P.IsDeleted' => 1]);
            } else {
                $this->ReadDb->where(['P.OrgUID' => $orgUID, 'P.IsDeleted' => 0, 'P.IsActive' => 1]);
            }
            if (!empty($filter['PaymentDirection'])) {
                $this->ReadDb->where('P.PaymentDirection', $filter['PaymentDirection']);
            }
            if (!empty($filter['PartyType'])) {
                $this->ReadDb->where('P.PartyType', $filter['PartyType']);
            }
            if (!empty($filter['ModuleUID'])) {
                $this->ReadDb->where('P.PaymentModuleUID', (int)$filter['ModuleUID']);
            }
            if (!empty($filter['PaymentSource'])) {
                $this->ReadDb->where('P.PaymentSource', $filter['PaymentSource']);
            }
            if (!empty($filter['DateFrom'])) {
                $this->ReadDb->where('DATE(P.CreatedOn) >=', $filter['DateFrom']);
            }
            if (!empty($filter['DateTo'])) {
                $this->ReadDb->where('DATE(P.CreatedOn) <=', $filter['DateTo']);
            }
            if (!empty($filter['Search'])) {
                $s = $filter['Search'];
                $this->ReadDb->join('Transaction.TransactionsTbl AS TS2', 'TS2.TransUID = P.TransUID AND TS2.IsDeleted = 0', 'LEFT');
                $this->ReadDb->join('Customers.CustomerTbl AS CS2', "CS2.CustomerUID = P.PartyUID AND P.PartyType = 'C'", 'LEFT');
                $this->ReadDb->join('Vendors.VendorTbl AS VS2', "VS2.VendorUID = P.PartyUID AND P.PartyType = 'S'", 'LEFT');
                $this->ReadDb->group_start();
                $this->ReadDb->or_like('TS2.UniqueNumber', $s, 'both');
                $this->ReadDb->or_like('CS2.Name', $s, 'both');
                $this->ReadDb->or_like('VS2.Name', $s, 'both');
                $this->ReadDb->group_end();
            }

            return $this->ReadDb->count_all_results();

        } catch (Exception $e) {
            return 0;
        }

    }

    public function getPaymentMethodSummary($orgUID, $filter = []) {

        try {

            $this->ReadDb->db_debug = FALSE;

            // All non-aggregated columns must use aggregate functions to satisfy
            // MySQL ONLY_FULL_GROUP_BY mode. MAX() is safe here because each
            // BankAccountUID has exactly one AccountName/BankName.
            $this->ReadDb->select([
                "IF(MAX(PT.IsCash) = 1, 'Cash', COALESCE(MAX(BA.AccountName), 'Unknown')) AS AccountLabel",
                "IF(MAX(PT.IsCash) = 1, '', COALESCE(MAX(BA.BankName), '')) AS BankName",
                'MAX(PT.IsCash) AS IsCash',
                'MAX(BA.BankAccountUID) AS BankAccountUID',
                "SUM(CASE WHEN P.PartyType = 'C' THEN P.Amount ELSE 0 END) AS TotalReceived",
                "SUM(CASE WHEN P.PartyType = 'S' THEN P.Amount ELSE 0 END) AS TotalPaid",
                "(SUM(CASE WHEN P.PartyType = 'C' THEN P.Amount ELSE 0 END) - SUM(CASE WHEN P.PartyType = 'S' THEN P.Amount ELSE 0 END)) AS NetBalance",
            ]);
            $this->ReadDb->from('Transaction.PaymentsTbl AS P');
            $this->ReadDb->join('Transaction.PaymentTypesTbl AS PT', 'PT.PaymentTypeUID = P.PaymentTypeUID', 'LEFT');
            $this->ReadDb->join('Transaction.OrgBankAccountsTbl AS BA', 'BA.BankAccountUID = P.BankAccountUID', 'LEFT');
            $this->ReadDb->where(['P.OrgUID' => $orgUID, 'P.IsDeleted' => 0, 'P.IsActive' => 1]);

            if (!empty($filter['PartyType']))        $this->ReadDb->where('P.PartyType', $filter['PartyType']);
            if (!empty($filter['PaymentDirection'])) $this->ReadDb->where('P.PaymentDirection', $filter['PaymentDirection']);
            if (!empty($filter['ModuleUID']))         $this->ReadDb->where('P.PaymentModuleUID', (int)$filter['ModuleUID']);
            if (!empty($filter['PaymentSource']))     $this->ReadDb->where('P.PaymentSource', $filter['PaymentSource']);
            if (!empty($filter['DateFrom']))         $this->ReadDb->where('DATE(P.CreatedOn) >=', $filter['DateFrom']);
            if (!empty($filter['DateTo']))           $this->ReadDb->where('DATE(P.CreatedOn) <=', $filter['DateTo']);

            // Cash payments: BankAccountUID IS NULL → one group; each bank account gets its own row
            $this->ReadDb->group_by('BA.BankAccountUID, PT.IsCash');
            $this->ReadDb->order_by('MAX(PT.IsCash)', 'DESC');
            $this->ReadDb->order_by('SUM(P.Amount)', 'DESC');
            $query = $this->ReadDb->get();
            if (!$query) return [];
            return $query->result();

        } catch (Exception $e) {
            return [];
        }

    }

    public function getPaymentsTotals($orgUID, $filter = []) {

        try {

            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                "SUM(CASE WHEN P.PartyType = 'C' THEN P.Amount ELSE 0 END) AS TotalReceived",
                "SUM(CASE WHEN P.PartyType = 'S' THEN P.Amount ELSE 0 END) AS TotalPaid",
            ]);
            $this->ReadDb->from('Transaction.PaymentsTbl AS P');
            $this->ReadDb->where(['P.OrgUID' => $orgUID, 'P.IsDeleted' => 0, 'P.IsActive' => 1]);

            if (!empty($filter['DateFrom']))         $this->ReadDb->where('DATE(P.CreatedOn) >=', $filter['DateFrom']);
            if (!empty($filter['DateTo']))           $this->ReadDb->where('DATE(P.CreatedOn) <=', $filter['DateTo']);
            if (!empty($filter['PartyType']))        $this->ReadDb->where('P.PartyType', $filter['PartyType']);
            if (!empty($filter['PaymentDirection'])) $this->ReadDb->where('P.PaymentDirection', $filter['PaymentDirection']);
            if (!empty($filter['ModuleUID']))         $this->ReadDb->where('P.PaymentModuleUID', (int)$filter['ModuleUID']);
            if (!empty($filter['PaymentSource']))     $this->ReadDb->where('P.PaymentSource', $filter['PaymentSource']);

            $query = $this->ReadDb->get();
            if (!$query) return (object)['TotalReceived' => 0, 'TotalPaid' => 0];
            return $query->row() ?: (object)['TotalReceived' => 0, 'TotalPaid' => 0];

        } catch (Exception $e) {
            return (object)['TotalReceived' => 0, 'TotalPaid' => 0];
        }

    }

    // ----------------------------------------------------------------
    // Server-side A4 HTML renderer
    // Reads the template file, replaces {{}} tokens, returns HTML string
    // ----------------------------------------------------------------
    public function _renderA4Html($modId, $h, $items, $org, $theme, $bankAccount = null) {

        $org   = $org   ?? new stdClass();
        $theme = $theme ?? new stdClass();

        // ── Load template ────────────────────────────────────────────
        $tplHtml = $theme->TemplateHtmlContent ?? null;
        if (!$tplHtml) {
            // No template assigned — use built-in generic layout
            return $this->_renderGenericA4Html($h, $items, $org);
        }

        // ── Helpers ──────────────────────────────────────────────────
        $cur = '₹ ';
        $dec = 2;
        $e   = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES);
        // Read PrintDateFormat from GenSettings JWT (stored in OrgSettingsTbl)
        try {
            $CI = &get_instance();
            $_printFmt = $CI->pageData['JwtData']->GenSettings->PrintDateFormat ?? 'd M Y';
        } catch (Exception $_) {
            $_printFmt = 'd M Y';
        }
        $fmt = function($date) use ($_printFmt) {
            if (!$date) return '—';
            $d = date_create($date);
            return $d ? date_format($d, $_printFmt) : $date;
        };
        $addr    = fn($l1,$l2,$city,$state,$pin) => implode(', ', array_filter([$l1,$l2,$city,$state,$pin]));
        $addrHtml = fn($l1,$l2,$city,$state,$pin) => implode('<br>', array_filter(array_map('htmlspecialchars', array_filter([$l1,$l2,$city,$state,$pin]))));

        // ── Items table ──────────────────────────────────────────────
        $itemRows = '';
        foreach ($items as $i => $item) {
            $taxAmt = round((float)($item->CgstAmount ?? 0) + (float)($item->SgstAmount ?? 0) + (float)($item->IgstAmount ?? 0), $dec);
            $itemRows .=
                '<tr>' .
                    '<td style="text-align:center">' . ($i + 1) . '</td>' .
                    '<td>' . $e($item->ProductName) . '</td>' .
                    '<td style="text-align:center">' . $e($item->HSNCode ?? '-') . '</td>' .
                    '<td style="text-align:right">'  . number_format((float)($item->UnitPrice ?? 0), $dec) . '</td>' .
                    '<td style="text-align:center">' . $e($item->Quantity) . ' ' . $e($item->PrimaryUnitName ?? '') . '</td>' .
                    '<td style="text-align:right">'  . number_format((float)($item->UnitPrice ?? 0) * (float)($item->Quantity ?? 0), $dec) . '</td>' .
                    '<td style="text-align:right">'  . ($taxAmt ? number_format($taxAmt, $dec) . ' (' . number_format((float)($item->TaxPercentage ?? 0), 0) . '%)' : '') . '</td>' .
                    '<td style="text-align:right">'  . number_format((float)($item->NetAmount ?? 0), $dec) . '</td>' .
                '</tr>';
        }
        $itemsTable =
            '<table style="width:100%;border-collapse:collapse;font-size:8.5pt;margin-bottom:8px;">' .
            '<thead><tr style="background:#f5f5f5;">' .
            '<th style="border:1px solid #ddd;padding:5px;text-align:center;width:28px">#</th>' .
            '<th style="border:1px solid #ddd;padding:5px;">Item</th>' .
            '<th style="border:1px solid #ddd;padding:5px;">HSN/SAC</th>' .
            '<th style="border:1px solid #ddd;padding:5px;text-align:right;">Rate</th>' .
            '<th style="border:1px solid #ddd;padding:5px;text-align:center;">Qty</th>' .
            '<th style="border:1px solid #ddd;padding:5px;text-align:right;">Taxable Value</th>' .
            '<th style="border:1px solid #ddd;padding:5px;text-align:right;">Tax Amt</th>' .
            '<th style="border:1px solid #ddd;padding:5px;text-align:right;">Amount</th>' .
            '</tr></thead><tbody>' . $itemRows . '</tbody></table>';

        // ── Totals ───────────────────────────────────────────────────
        $totals =
            '<table style="width:100%;border-collapse:collapse;font-size:8.5pt;margin-bottom:8px;">' .
            '<tr><td style="border:1px solid #ddd;padding:5px;text-align:right;font-weight:600;">Sub Total</td>' .
            '<td style="border:1px solid #ddd;padding:5px;text-align:right;width:120px;">' . $cur . number_format((float)($h->SubTotal ?? 0), $dec) . '</td></tr>' .
            ((float)($h->DiscountAmount ?? 0) > 0 ? '<tr><td style="border:1px solid #ddd;padding:5px;text-align:right;color:#c00;">Discount</td><td style="border:1px solid #ddd;padding:5px;text-align:right;color:#c00;">- ' . $cur . number_format((float)$h->DiscountAmount, $dec) . '</td></tr>' : '') .
            ((float)($h->TaxAmount ?? 0) > 0 ? '<tr><td style="border:1px solid #ddd;padding:5px;text-align:right;">Tax</td><td style="border:1px solid #ddd;padding:5px;text-align:right;">' . $cur . number_format((float)$h->TaxAmount, $dec) . '</td></tr>' : '') .
            '<tr><td style="border:1px solid #ddd;padding:5px;text-align:right;font-weight:700;">Net Amount</td>' .
            '<td style="border:1px solid #ddd;padding:5px;text-align:right;font-weight:700;">' . $cur . number_format((float)($h->NetAmount ?? 0), $dec) . '</td></tr>' .
            '</table>';

        // ── Customer Addresses ────────────────────────────────────────────────
        $billAddr = $addr($h->BillLine1 ?? '', $h->BillLine2 ?? '', $h->BillCity ?? '', $h->BillState ?? '', $h->BillPincode ?? '') ?: '';
        $shipAddr = $addr($h->ShipLine1 ?? '', $h->ShipLine2 ?? '', $h->ShipCity ?? '', $h->ShipState ?? '', $h->ShipPincode ?? '') ?: '';
        // \u2500\u2500 Customer address (billing first, fallback to shipping) \u2500\u2500
        $custAddrHtml = $addrHtml($h->BillLine1 ?? '', $h->BillLine2 ?? '', $h->BillCity ?? '', $h->BillState ?? '', $h->BillPincode ?? '');
        if (empty($custAddrHtml)) {
            $custAddrHtml = $addrHtml($h->ShipLine1 ?? '', $h->ShipLine2 ?? '', $h->ShipCity ?? '', $h->ShipState ?? '', $h->ShipPincode ?? '');
        }

        // ── Org logo ─────────────────────────────────────────────────
        $logoHtml = !empty($org->Logo)
            ? '<img src="' . $e($org->Logo) . '" style="max-width:100px;max-height:100px;" alt="Logo">'
            : '';

        // ── Org address lines ────────────────────────────────────────
        $orgAddr1     = $e($org->Line1 ?? '');
        $orgAddr2     = $e($org->Line2 ?? '');
        $orgCityState = implode(', ', array_filter([$org->CityText ?? '', $org->StateText ?? '']));
        $orgGstinLine  = !empty($org->GSTIN) ? '<b>GSTIN:</b> ' . $e($org->GSTIN) : '';
        $orgCityPin   = implode(' - ', array_filter([$e($orgCityState), $e($org->Pincode ?? '')]));
        $orgInfoLines = implode('<br>', array_filter([$orgAddr1, $orgAddr2, $orgCityPin, $orgGstinLine]));

        // ── Notes + Terms ────────────────────────────────────────────
        $notesPart = !empty($h->Notes)           ? '<p style="font-size:8pt;margin-top:4px;"><strong>Notes:</strong> ' . nl2br($e($h->Notes)) . '</p>' : '';
        $termsPart = !empty($h->TermsConditions) ? '<p style="font-size:8pt;margin-top:4px;"><strong>Terms:</strong> ' . nl2br($e($h->TermsConditions)) . '</p>' : '';

        // ── Bank Account (for print templates) ───────────────────────
        $bank        = $bankAccount ?? null;
        $bankName    = $bank ? $e($bank->BankName      ?? '') : '';
        $bankAccName = $bank ? $e($bank->AccountName   ?? '') : '';
        $bankAccNo   = $bank ? $e($bank->AccountNumber ?? '') : '';
        $bankIfsc    = $bank ? $e($bank->IFSC          ?? '') : '';
        $bankBranch  = $bank ? $e($bank->BranchName    ?? '') : '';
        $bankUpiId   = $bank ? $e($bank->UPIId         ?? '') : '';

        $bankQrHtml = print_build_qr_html(
            $bank->UPIId ?? '',
            (float)($h->NetAmount ?? 0),
            $org->BrandName ?? $org->Name ?? '',
            $org->Logo ?? ''
        );

        // Signature block: show actual signature if selected, otherwise empty space
        $signatureSpaceHtml = $this->_buildSignatureHtml((int)($h->SignatureUID ?? 0));

        // ── Summary totals — read directly from TransactionsTbl (no item-level summing) ──
        $totalItemsCount = (int)($h->TotalItems    ?? count($items));
        $totalQty        = (float)($h->TotalQuantity ?? 0);
        $totalCgst       = (float)($h->CgstAmount    ?? 0);
        $totalSgst       = (float)($h->SgstAmount    ?? 0);
        $totalIgst       = (float)($h->IgstAmount    ?? 0);

        // ── HSN summary totals — computed from item-level data (matches HSN loop rows) ──
        $hsnTotalTaxable = array_sum(array_map(
            fn($it) => round((float)($it->UnitPrice ?? 0) * (float)($it->Quantity ?? 0), 2),
            $items
        ));
        $hsnTotalCgst    = array_sum(array_map(fn($it) => (float)($it->CgstAmount ?? 0), $items));
        $hsnTotalSgst    = array_sum(array_map(fn($it) => (float)($it->SgstAmount ?? 0), $items));
        $hsnTotalIgst    = array_sum(array_map(fn($it) => (float)($it->IgstAmount ?? 0), $items));
        $hsnTotalTax     = round($hsnTotalCgst + $hsnTotalSgst + $hsnTotalIgst, 2);
        $dec2 = 2;

        // ── Token map ────────────────────────────────────────────────
        $tokens = [
            '{{PRIMARY_COLOR}}'        => $theme->PrimaryColor  ?? '#1a3c6e',
            '{{ACCENT_COLOR}}'         => $theme->AccentColor   ?? '#f59e0b',
            '{{FONT_FAMILY}}'          => $theme->FontFamily    ?? 'Arial',
            '{{FONT_SIZE_PX}}'         => ($theme->FontSizePx   ?? 11) . 'px',
            '{{FONT_SIZE}}'            => ($theme->FontSizePx   ?? 11) . 'px',
            /** Organisation Details */
            '{{ORG_LOGO}}'             => $logoHtml,
            '{{ORG_NAME}}'             => $e($org->BrandName ?? $org->Name ?? ''),
            '{{ORG_GSTIN}}'            => $e($org->GSTIN ?? ''),
            '{{ORG_ADDRESS_1}}'        => $orgAddr1,
            '{{ORG_ADDRESS_2}}'        => $orgAddr2,
            '{{ORG_CITY_STATE}}'       => $e($orgCityState),
            '{{ORG_PINCODE}}'          => $e($org->Pincode ?? ''),
            '{{ORG_PHONE}}'            => $e($org->MobileNumber ?? ''),
            '{{ORG_EMAIL}}'            => $e($org->EmailAddress ?? ''),
            '{{ORG_BANK_NAME}}'        => $e($org->BankName ?? ''),
            '{{ORG_ACCOUNT_NO}}'       => $e($org->AccountNo ?? ''),
            '{{ORG_IFSC}}'             => $e($org->IFSC ?? ''),
            '{{ORG_BRANCH}}'           => $e($org->Branch ?? ''),
            '{{ORG_UPI_ID}}'           => $e($org->UpiId ?? ''),
            '{{ORG_INFO_LINES}}'       => $orgInfoLines,
            '{{PLACE_OF_SUPPLY}}'      => $e($h->PlaceOfSupply ?? $org->StateText ?? ''),
            '{{BANK_DETAILS_LINES}}'   => implode('<br>', array_filter([$e($org->BankName ?? ''), !empty($org->AccountNo) ? 'A/C: ' . $e($org->AccountNo) : '', !empty($org->IFSC) ? 'IFSC: ' . $e($org->IFSC) : ''])),
            '{{CURRENCY}}'             => $cur,
            /** Customer Details */
            '{{CUSTOMER_NAME}}'        => $e($h->PartyName ?? '—'),
            '{{CUSTOMER_PHONE}}'       => $e($h->PartyMobile ?? ''),
            '{{CUSTOMER_GSTIN}}'       => $e($h->PartyGSTIN ?? ''),
            '{{BILLING_ADDRESS}}'      => $e($billAddr),
            '{{SHIPPING_ADDRESS}}'     => $e($shipAddr),
            '{{CUSTOMER_ADDRESS}}'     => $custAddrHtml,
            '{{PARTY_GSTIN}}'          => $e($h->PartyGSTIN ?? ''),
            '{{PARTY_PHONE}}'          => $e($h->PartyMobile ?? ''),
            '{{CUSTOMER_PHONE_LINE}}'  => !empty($h->PartyMobile) ? 'Ph: ' . $e($h->PartyMobile) : '',
            '{{PARTY_GSTIN_LINE}}'     => !empty($h->PartyGSTIN) ? 'GSTIN: ' . $e($h->PartyGSTIN) : '',
            /** Transaction Type Details */
            '{{DOC_TYPE}}'             => $e($h->TransType ?? 'Document'),
            '{{DOC_NUMBER}}'           => $e($h->UniqueNumber ?? '—'),
            '{{DOC_DATE}}'             => $fmt($h->TransDate ?? ''),
            '{{DUE_DATE}}'             => $fmt($h->ValidityDate ?? ''),
            '{{ITEMS_TABLE}}'          => $itemsTable,
            '{{ITEMS_TABLE_ROWS}}'     => $itemRows,
            '{{TOTALS_SECTION}}'       => $totals,
            '{{TOTALS_BLOCK}}'         => $totals,
            '{{NOTES_TERMS}}'          => $notesPart . $termsPart,
            '{{FOOTER_TEXT}}'          => $e($theme->FooterText ?? 'Thank you for your business!'),
            '{{TERMS_CONDITIONS}}'     => nl2br($e($h->TermsConditions ?? '')),
            '{{HSN_TAX_TABLE}}'        => '',
            /** Summary Totals */
            '{{TOTAL_ITEMS_COUNT}}'    => $totalItemsCount,
            '{{TOTAL_QTY}}'            => number_format($totalQty, 2),
            '{{TOTAL_TAXABLE_AMOUNT}}' => number_format((float)($h->SubTotal ?? 0), $dec),
            '{{TOTAL_CGST}}'           => number_format(round($totalCgst, $dec), $dec),
            '{{TOTAL_SGST}}'           => number_format(round($totalSgst, $dec), $dec),
            '{{TOTAL_IGST}}'           => number_format(round($totalIgst, $dec), $dec),
            '{{TOTAL_TAX}}'            => number_format((float)($h->TaxAmount ?? 0), $dec),
            '{{TOTAL_DISCOUNT}}'       => number_format((float)($h->DiscountAmount ?? 0), $dec),
            '{{NET_AMOUNT}}'           => number_format((float)($h->NetAmount ?? 0), $dec),
            '{{AMOUNT_IN_WORDS}}'      => print_number_to_words((float)($h->NetAmount ?? 0)),
            '{{UPI_QR_CODE}}'          => $e($org->UpiId ?? ''),
            /** Bank Account */
            '{{BANK_NAME}}'            => $bankName,
            '{{BANK_ACCOUNT_NAME}}'    => $bankAccName,
            '{{BANK_ACCOUNT_NO}}'      => $bankAccNo,
            '{{BANK_IFSC}}'            => $bankIfsc,
            '{{BANK_BRANCH}}'          => $bankBranch,
            '{{BANK_UPI_ID}}'          => $bankUpiId,
            '{{BANK_QR_HTML}}'         => $bankQrHtml,
            /** Signature */
            '{{SIGNATURE_SPACE}}'      => $signatureSpaceHtml,
            /** Copy label — JS replaces __COPY_LABEL__ client-side based on user selection */
            '{{COPY_LABEL}}'           => '__COPY_LABEL__',
            /** HSN Summary TOTAL row tokens (match the summed rows in the loop) */
            '{{HSN_TOTAL_TAXABLE}}'    => number_format($hsnTotalTaxable, $dec2),
            '{{HSN_TOTAL_CGST}}'       => number_format(round($hsnTotalCgst, $dec2), $dec2),
            '{{HSN_TOTAL_SGST}}'       => number_format(round($hsnTotalSgst, $dec2), $dec2),
            '{{HSN_TOTAL_IGST}}'       => number_format(round($hsnTotalIgst, $dec2), $dec2),
            '{{HSN_TOTAL_TAX}}'        => number_format($hsnTotalTax, $dec2),
        ];

        $html = $this->_processLoops($tplHtml, $items);
        $html = $this->_processHsnSummary($html, $items);
        $html = print_apply_tokens($html, $tokens);

        $systemFonts   = ['Arial', 'Helvetica', 'Verdana', 'Tahoma', 'Trebuchet MS', 'Times New Roman', 'Georgia', 'Palatino Linotype', 'Calibri'];
        $fontFamily    = $theme->FontFamily ?? 'Arial';
        $fontFamilyEsc = str_replace("'", "\\'", $fontFamily);
        $fontSizePx    = (int) ($theme->FontSizePx ?? 11);

        $orgNameForWatermark = addslashes($org->BrandName ?? $org->Name ?? '');
        $headInject = '<style>'
            . '@page{size:A4;margin:0;}'
            . '@media print{body{background:#fff;}}'
            . "body{padding:0 5mm;box-sizing:border-box;font-size:{$fontSizePx}px !important;}"
            . '.invoice{width:100%!important;max-width:100%!important;box-sizing:border-box!important;margin:10px 0!important;}'
            . "body,body *{font-family:'{$fontFamilyEsc}',Arial,Helvetica,sans-serif !important;}"
            . 'body::before{'
            . "content:'{$orgNameForWatermark}';"
            . 'position:fixed;top:50%;left:50%;'
            . 'transform:translate(-50%,-50%) rotate(-45deg);'
            . 'font-size:72px;font-weight:800;letter-spacing:4px;'
            . 'color:rgba(0,0,0,0.045);white-space:nowrap;'
            . 'pointer-events:none;z-index:9999;'
            . '}'
            . '</style>';

        // For Google Fonts: inject <link> tag — rendered via Blob URL so external requests load correctly
        if (!in_array($fontFamily, $systemFonts)) {
            $headInject .= '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family='. str_replace(' ', '+', $fontFamily). ':wght@400;600;700&display=swap">';
        }

        $html = str_replace('</head>', $headInject . '</head>', $html);
        return $html;

    }

    private function _processLoops($html, $items) {
        $cur = '₹ ';
        $dec = 2;
        $e   = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES);

        return preg_replace_callback(
            '/\{\{FOREACH:ITEMS\}\}(.*?)\{\{\/FOREACH:ITEMS\}\}/s',
            function ($m) use ($items, $cur, $dec, $e) {
                $rowTpl = $m[1];
                $rows   = '';
                foreach ($items as $i => $item) {
                    $taxPct      = (float)($item->TaxPercentage ?? 0);
                    $taxAmt      = round(
                        (float)($item->CgstAmount ?? 0) +
                        (float)($item->SgstAmount ?? 0) +
                        (float)($item->IgstAmount ?? 0), $dec
                    );
                    $unitPrice   = (float)($item->UnitPrice ?? 0);
                    $qty         = (float)($item->Quantity ?? 0);
                    $taxableVal  = round($unitPrice * $qty, $dec);

                    $map = [
                        '{{ITEM.SNO}}'          => $i + 1,
                        '{{ITEM.PRODUCT_NAME}}' => $e($item->ProductName ?? ''),
                        '{{ITEM.HSN_CODE}}'     => $e($item->HSNCode ?? $item->HSNSACCode ?? ''),
                        '{{ITEM.UNIT_PRICE}}'   => $cur . number_format($unitPrice, $dec),
                        '{{ITEM.QTY}}'          => $e($item->Quantity ?? ''),
                        '{{ITEM.UNIT}}'         => $e($item->PrimaryUnitName ?? ''),
                        '{{ITEM.TAXABLE_VALUE}}'=> $cur . number_format($taxableVal, $dec),
                        '{{ITEM.TAX_PCT}}'      => number_format($taxPct, 2),
                        '{{ITEM.TAX_AMT}}'      => $cur . number_format($taxAmt, $dec),
                        '{{ITEM.NET_AMOUNT}}'   => $cur . number_format((float)($item->NetAmount ?? 0), $dec),
                        '{{ITEM.DISCOUNT}}'     => $cur . number_format((float)($item->DiscountAmount ?? 0), $dec),
                        '{{ITEM.PART_NUMBER}}'  => $e($item->PartNumber ?? ''),
                    ];
                    $rows .= str_replace(array_keys($map), array_values($map), $rowTpl);
                }
                return $rows;
            },
            $html
        );
    }

    private function _processHsnSummary($html, $items) {
        $cur = '₹ ';
        $dec = 2;
        $e   = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES);

        return preg_replace_callback(
            '/\{\{FOREACH:HSN_SUMMARY\}\}(.*?)\{\{\/FOREACH:HSN_SUMMARY\}\}/s',
            function ($m) use ($items, $cur, $dec, $e) {
                $rowTpl = $m[1];

                // Group line items by HSN code + tax rate
                $groups = [];
                foreach ($items as $item) {
                    $hsn    = (string)($item->HSNCode ?? $item->HSNSACCode ?? '');
                    $taxPct = (float)($item->TaxPercentage ?? 0);
                    $key    = $hsn . '||' . $taxPct;
                    if (!isset($groups[$key])) {
                        $groups[$key] = [
                            'hsn'          => $hsn,
                            'taxPct'       => $taxPct,
                            'taxableValue' => 0.0,
                            'cgstAmt'      => 0.0,
                            'sgstAmt'      => 0.0,
                            'igstAmt'      => 0.0,
                        ];
                    }
                    $groups[$key]['taxableValue'] += round((float)($item->UnitPrice ?? 0) * (float)($item->Quantity ?? 0), $dec);
                    $groups[$key]['cgstAmt']      += (float)($item->CgstAmount ?? 0);
                    $groups[$key]['sgstAmt']      += (float)($item->SgstAmount ?? 0);
                    $groups[$key]['igstAmt']      += (float)($item->IgstAmount ?? 0);
                }

                $rows = '';
                $sno  = 1;
                foreach ($groups as $g) {
                    $cgstAmt  = round($g['cgstAmt'], $dec);
                    $sgstAmt  = round($g['sgstAmt'], $dec);
                    $igstAmt  = round($g['igstAmt'], $dec);
                    $totalTax = round($cgstAmt + $sgstAmt + $igstAmt, $dec);
                    // Split rate: CGST = SGST = half of total tax %
                    $splitRate = $g['taxPct'] / 2;
                    $map = [
                        '{{HSN.SNO}}'           => $sno++,
                        '{{HSN.CODE}}'          => $e($g['hsn']),
                        '{{HSN.TAXABLE_VALUE}}' => number_format($g['taxableValue'], $dec),
                        // Rate tokens — plain numbers, no % suffix (add % in template if needed)
                        '{{HSN.TAX_RATE}}'      => number_format($g['taxPct'], 0),
                        '{{HSN.CGST_RATE}}'     => number_format($splitRate, 0),
                        '{{HSN.SGST_RATE}}'     => number_format($splitRate, 0),
                        '{{HSN.IGST_RATE}}'     => number_format($g['taxPct'], 0),
                        // Amount tokens
                        '{{HSN.CGST_AMT}}'      => number_format($cgstAmt, $dec),
                        '{{HSN.SGST_AMT}}'      => number_format($sgstAmt, $dec),
                        '{{HSN.IGST_AMT}}'      => number_format($igstAmt, $dec),
                        // Combined tax for this HSN row (CGST+SGST OR IGST — whichever applies)
                        '{{HSN.TAX_AMT}}'       => number_format($totalTax, $dec),
                        '{{HSN.TOTAL_TAX}}'     => number_format($totalTax, $dec),
                    ];
                    $rows .= str_replace(array_keys($map), array_values($map), $rowTpl);
                }
                return $rows;
            },
            $html
        );
    }

    private function _processConditionals($html, $tokens) {
        return preg_replace_callback(
            '/\{\{IF:([A-Z0-9_]+)\}\}(.*?)\{\{\/IF:\1\}\}/s',
            function ($m) use ($tokens) {
                $value = trim($tokens['{{' . $m[1] . '}}'] ?? '');
                return $value !== '' ? $m[2] : '';
            },
            $html
        );
    }

    private function _renderGenericA4Html($h, $items, $org) {
        $cur   = '₹ ';
        $dec   = 2;
        $e     = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES);
        $fmt   = function($date) { if (!$date) return '—'; $d = date_create($date); return $d ? date_format($d, 'd M Y') : $date; };
        $label = strtoupper($h->TransType ?? 'Document');
        $partyLabel = in_array($label, ['PURCHASE ORDER', 'PURCHASE BILL']) ? 'Vendor' : 'Customer';

        $rows = '';
        foreach ($items as $i => $item) {
            $rows .= '<tr>' .
                '<td style="text-align:center">' . ($i + 1) . '</td>' .
                '<td>' . $e($item->ProductName) . '</td>' .
                '<td style="text-align:center">' . $e($item->Quantity) . ' ' . $e($item->PrimaryUnitName ?? '') . '</td>' .
                '<td style="text-align:right">' . $cur . number_format((float)($item->UnitPrice ?? 0), $dec) . '</td>' .
                '<td style="text-align:right">' . $cur . number_format((float)($item->NetAmount ?? 0), $dec) . '</td>' .
                '</tr>';
        }

        return '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>' .
            '@page{size:A4;margin:0;}' .
            'body{font-family:Arial,sans-serif;font-size:12px;margin:0;padding:0;background:#fff;}' .
            '.page{padding:15mm;}' .
            'table{width:100%;border-collapse:collapse;}th,td{border:1px solid #ddd;padding:6px 8px;font-size:11px;}' .
            'th{background:#f5f5f5;font-weight:bold;}' .
            '@media print{body{background:#fff;}}' .
            '</style></head><body><div class="page">' .
            '<div style="display:flex;justify-content:space-between;margin-bottom:12px">' .
                '<div><strong style="font-size:14px">' . $e($org->BrandName ?? $org->Name ?? '') . '</strong>' .
                (!empty($org->GSTIN) ? '<br><span style="color:#666">GSTIN: ' . $e($org->GSTIN) . '</span>' : '') . '</div>' .
                '<div style="text-align:right"><strong style="font-size:16px">' . $label . '</strong><br>' .
                '<span style="color:#666">' . $e($h->UniqueNumber ?? '—') . '</span><br>' .
                '<span style="color:#666">Date: ' . $fmt($h->TransDate ?? '') . '</span>' .
                (!empty($h->ValidityDate) ? '<br><span style="color:#666">Valid Until: ' . $fmt($h->ValidityDate) . '</span>' : '') . '</div>' .
            '</div>' .
            '<div style="background:#f9f9f9;padding:8px;border-radius:4px;margin-bottom:12px">' .
                '<strong>' . $partyLabel . ':</strong> ' . $e($h->PartyName ?? '—') . '</div>' .
            '<table><thead><tr><th style="width:30px">#</th><th>Product</th>' .
                '<th style="width:60px;text-align:center">Qty</th>' .
                '<th style="width:90px;text-align:right">Unit Price</th>' .
                '<th style="width:90px;text-align:right">Amount</th></tr></thead>' .
            '<tbody>' . $rows . '</tbody><tfoot>' .
                '<tr><td colspan="4" style="text-align:right;font-weight:bold">Sub Total</td><td style="text-align:right">' . $cur . number_format((float)($h->SubTotal ?? 0), $dec) . '</td></tr>' .
                ((float)($h->DiscountAmount ?? 0) > 0 ? '<tr><td colspan="4" style="text-align:right;color:#c00">Discount</td><td style="text-align:right;color:#c00">- ' . $cur . number_format((float)$h->DiscountAmount, $dec) . '</td></tr>' : '') .
                ((float)($h->TaxAmount ?? 0) > 0 ? '<tr><td colspan="4" style="text-align:right">Tax</td><td style="text-align:right">' . $cur . number_format((float)$h->TaxAmount, $dec) . '</td></tr>' : '') .
                '<tr><td colspan="4" style="text-align:right;font-weight:bold">Net Amount</td><td style="text-align:right;font-weight:bold">' . $cur . number_format((float)($h->NetAmount ?? 0), $dec) . '</td></tr>' .
            '</tfoot></table>' .
            (!empty($h->Notes) ? '<p style="margin-top:12px;font-size:11px;color:#666"><strong>Notes:</strong> ' . $e($h->Notes) . '</p>' : '') .
            (!empty($h->TermsConditions) ? '<p style="font-size:11px;color:#666"><strong>Terms:</strong> ' . $e($h->TermsConditions) . '</p>' : '') .
        '</div></body></html>';
    }

    public function _renderPaymentReceiptHtml($p, $org, $theme, $bankAccount = null) {
        
        $org   = $org   ?? new stdClass();
        $theme = $theme ?? new stdClass();
        $e      = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES);
        $fmt    = function($d) { if (!$d) return '—'; $dt = date_create($d); return $dt ? date_format($dt, 'd M Y') : $d; };
        $cur    = $org->CurrenySymbol ?? '₹';
        $dec    = 2;
        $fmtAmt = fn($v) => number_format((float)$v, $dec, '.', ',');

        $direction  = ($p->PartyType === 'C') ? 'Payment Received' : 'Payment Made';
        $partyLabel = ($p->PartyType === 'C') ? 'Customer' : 'Vendor';
        $orgAddr    = implode(', ', array_filter([$org->Line1 ?? '', $org->Line2 ?? '', $org->CityText ?? '', $org->StateText ?? '', $org->Pincode ?? '']));
        $bankLine   = (!$p->IsCash && !empty($p->BankName))
            ? $e($p->BankName) . (!empty($p->AccountName) ? ' (' . $e($p->AccountName) . ')' : '')
            : '';
        
        // ── Org logo ─────────────────────────────────────────────────
        $logoHtml = !empty($org->Logo)
            ? '<img src="' . $e($org->Logo) . '" style="max-width:100px;max-height:100px;" alt="Logo">'
            : '';

        $orgAddr1     = $e($org->Line1    ?? '');
        $orgAddr2     = $e($org->Line2    ?? '');
        $orgCityState = implode(', ', array_filter([$org->CityText ?? '', $org->StateText ?? '']));
        $orgCityPin   = implode(' - ', array_filter([$e($orgCityState), $e($org->Pincode ?? '')]));
        $orgGstinLine = !empty($org->GSTIN) ? '<b>GSTIN:</b> ' . $e($org->GSTIN) : '';
        $orgInfoLines = implode('<br>', array_filter([$orgAddr1, $orgAddr2, $orgCityPin, $orgGstinLine]));

        // Org print bank account tokens (separate from the payment's own bank)
        $bank        = $bankAccount ?? null;
        $bankAccNo   = $bank ? $e($bank->AccountNumber ?? '') : '';
        $bankUpiId   = $bank ? ($bank->UPIId ?? '') : '';
        $bankQrHtml  = print_build_qr_html($bankUpiId, (float)($p->Amount ?? 0), $org->BrandName ?? $org->Name ?? '', $org->Logo ?? '');

        // Signature block: show actual signature if selected, otherwise empty space
        $signatureSpaceHtml = $this->_buildSignatureHtml((int)($p->SignatureUID ?? 0));

        // Payment reference text: either linked document number or payment reference number
        $payRefNo = $p->TransNumber ?? $p->TransNumber ?? '';
        if (!empty($payRefNo)) {
            $payRefText = 'Amount received against the linked document as <b>' . $e($payRefNo) . '</b>';
        } else {
            $payRefText = 'Amount received as <b>' . $e($p->TransNumber ?? '') . '</b>';
        }

        // 1st preference: template HTML from DB — replace {{}} tokens and return
        if (!empty($theme->TemplateHtmlContent)) {
            $tokens = [
                /** Theme */
                '{{PRIMARY_COLOR}}'      => $theme->PrimaryColor ?? '#1a3c6e',
                '{{ACCENT_COLOR}}'       => $theme->AccentColor  ?? '#f59e0b',
                '{{FONT_FAMILY}}'        => $theme->FontFamily   ?? 'Arial',
                '{{FONT_SIZE_PX}}'       => ($theme->FontSizePx  ?? 11) . 'px',
                '{{FONT_SIZE}}'          => ($theme->FontSizePx  ?? 11) . 'px',
                /** Organisation */
                '{{ORG_LOGO}}'           => $logoHtml,
                '{{ORG_NAME}}'           => $e($org->BrandName ?? $org->Name ?? ''),
                '{{ORG_GSTIN}}'          => $e($org->GSTIN ?? ''),
                '{{ORG_ADDRESS}}'        => $e($orgAddr),
                '{{ORG_ADDRESS_1}}'      => $orgAddr1,
                '{{ORG_ADDRESS_2}}'      => $orgAddr2,
                '{{ORG_CITY_STATE}}'     => $e($orgCityState),
                '{{ORG_PINCODE}}'        => $e($org->Pincode ?? ''),
                '{{ORG_INFO_LINES}}'     => $orgInfoLines,
                '{{ORG_PHONE}}'          => $e($org->MobileNumber ?? ''),
                '{{ORG_EMAIL}}'          => $e($org->EmailAddress ?? ''),
                '{{ORG_BANK_NAME}}'      => $e($org->BankName  ?? ''),
                '{{ORG_ACCOUNT_NO}}'     => $e($org->AccountNo ?? ''),
                '{{ORG_IFSC}}'           => $e($org->IFSC      ?? ''),
                '{{ORG_BRANCH}}'         => $e($org->Branch    ?? ''),
                '{{ORG_UPI_ID}}'         => $e($org->UpiId     ?? ''),
                '{{BANK_DETAILS_LINES}}' => implode('<br>', array_filter([
                    $e($bank->BankName      ?? ''),
                    !empty($bank->AccountNumber) ? 'A/C: ' . $e($bank->AccountNumber) : '',
                    !empty($bank->IFSC)          ? 'IFSC: ' . $e($bank->IFSC)         : '',
                    !empty($bank->BranchName)    ? 'Branch: ' . $e($bank->BranchName) : '',
                ])),
                '{{PLACE_OF_SUPPLY}}'    => $e($org->StateText ?? ''),
                /** Document */
                '{{DOC_TYPE}}'           => $e($direction),
                '{{DOC_NUMBER}}'         => $e($p->UniqueNumber ?? ('PMT-' . $p->PaymentUID)),
                '{{DOC_DATE}}'           => $fmt($p->PaymentDate ?? $p->CreatedOn),
                /** Party */
                '{{CUSTOMER_NAME}}'      => $e($p->PartyName   ?? '—'),
                '{{PARTY_LABEL}}'        => $e($partyLabel),
                '{{PARTY_NAME}}'         => $e($p->PartyName   ?? '—'),
                '{{PARTY_PHONE}}'        => $e($p->PartyMobile ?? ''),
                '{{PARTY_GSTIN}}'        => $e($p->PartyGSTIN  ?? ''),
                '{{BILLING_ADDRESS}}'    => '',
                '{{SHIPPING_ADDRESS}}'   => '',
                /** Amounts */
                '{{LINKED_DOC}}'         => $e($p->TransNumber ?? ''),
                '{{BILL_AMOUNT}}'        => !empty($p->BillAmount) ? $fmtAmt($p->BillAmount) : '',
                '{{AMOUNT}}'             => $fmtAmt($p->Amount),
                '{{NET_AMOUNT}}'         => $fmtAmt($p->Amount),
                '{{TOTAL_AMOUNT}}'       => $fmtAmt($p->Amount),
                '{{AMOUNT_IN_WORDS}}'    => print_number_to_words((float)($p->Amount ?? 0)),
                /** Payment bank (the account that received/made the payment) */
                '{{PAYMENT_MODE}}'       => $e($p->PaymentTypeName ?? '—'),
                '{{BANK_LINE}}'          => $bankLine,
                '{{BANK_NAME}}'          => $e($p->BankName      ?? ''),
                '{{BANK_ACCOUNT_NAME}}'  => $e($p->AccountName   ?? ''),
                '{{ACCOUNT_NUMBER}}'     => $e($p->AccountNumber ?? ''),
                '{{BANK_IFSC}}'          => $e($p->IFSC          ?? ''),
                '{{BANK_BRANCH}}'        => $e($p->BranchName    ?? ''),
                '{{REFERENCE_NO}}'       => $e($p->ReferenceNo   ?? ''),
                '{{RECORDED_BY}}'        => $e($p->CreatedByName ?? '—'),
                /** Org print bank account (for "Pay to" QR / bank details) */
                '{{BANK_ACCOUNT_NO}}'    => $bankAccNo,
                '{{BANK_UPI_ID}}'        => $e($bankUpiId),
                '{{BANK_QR_HTML}}'       => $bankQrHtml,
                /** Signature */
                '{{SIGNATURE_SPACE}}'    => $signatureSpaceHtml,
                /** Misc */
                '{{NOTES}}'              => $e($p->Notes ?? ''),
                '{{FOOTER_TEXT}}'        => $e($theme->FooterText ?? 'Thank you for your business!'),
                '{{CURRENCY}}'           => $cur,
                '{{PAYMENTS_REF}}'       => $payRefText,
            ];
            $html = print_apply_tokens($theme->TemplateHtmlContent, $tokens);
            $fontFamily = str_replace("'", "\\'", $theme->FontFamily ?? 'Arial');
            $fontSizePx = (int)($theme->FontSizePx ?? 11);
            $headInject = '<style>@page{size:A4;margin:0;}body{font-family:\'' . $fontFamily . '\',Arial,sans-serif;font-size:' . $fontSizePx . 'px;}@media print{body{background:#fff;}}</style>';
            return str_replace('</head>', $headInject . '</head>', $html);

        }

        return $this->_getStaticPaymentReceiptTemplate($p, $org, $theme, $logoHtml, $direction, $partyLabel, $orgAddr, $fmt, $fmtAmt, $bankLine, $bankQrHtml, $signatureSpaceHtml);

    }

    private function _getStaticPaymentReceiptTemplate($p, $org, $theme, $logoHtml, $direction, $partyLabel, $orgAddr, $fmt, $fmtAmt, $bankLine, $bankQrHtml, $signatureSpaceHtml) {

        $e = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES);

        $primary = $theme->PrimaryColor ?? '#1a3c6e';
        $font    = $theme->FontFamily   ?? 'Arial';
        $footer  = $theme->FooterText   ?? 'Thank you for your business!';

        // Table-based layout — dompdf cannot render flex/grid reliably
        return '<!DOCTYPE html><html><head><meta charset="UTF-8">'
            . '<style>
                @page{size:A4;margin:10mm 5mm;}
                body{font-family:\'' . $e($font) . '\',Arial,sans-serif;font-size:11px;margin:0;padding:0;background:#fff;}
                table{border-collapse:collapse;width:100%;}
                .page{padding:6mm 8mm;}
                .org-name{font-size:16px;font-weight:700;color:' . $primary . ';}
                .receipt-title{font-size:20px;font-weight:800;color:' . $primary . ';}
                .amount-box{background:' . $primary . ';color:#fff;border-radius:4px;padding:10px;text-align:center;margin:10px 0;}
                .amount-val{font-size:24px;font-weight:800;}
                .info-card{border:1px solid #e5e7eb;border-radius:4px;padding:8px;vertical-align:top;}
                .footer-row{margin-top:14px;text-align:center;font-size:10px;color:#888;}
            </style></head><body>
            <div class="page">

                <table style="border-bottom:3px solid ' . $primary . ';padding-bottom:8px;margin-bottom:10px;">
                    <tr>
                        <td style="vertical-align:top;">'
                            . ($logoHtml ? $logoHtml . '<br>' : '') .
                            '<div class="org-name">' . $e($org->BrandName ?? $org->Name ?? '') . '</div>
                            <div style="font-size:10px;">' . $e($orgAddr) . '</div>
                        </td>
                        <td style="text-align:right;vertical-align:top;">
                            <div class="receipt-title">' . $e($direction) . '</div>
                            <div>' . $e($p->UniqueNumber ?? '') . '</div>
                            <div>' . $fmt($p->PaymentDate ?? $p->CreatedOn) . '</div>
                        </td>
                    </tr>
                </table>

                <div class="amount-box">
                    <div>Amount</div>
                    <div class="amount-val">' . $fmtAmt($p->Amount) . '</div>
                </div>

                <table style="margin-bottom:8px;">
                    <tr>
                        <td width="49%" class="info-card">
                            <b>' . $e($partyLabel) . '</b><br>
                            ' . $e($p->PartyName ?? '—') . '<br>'
                            . (!empty($p->PartyMobile) ? 'Ph: ' . $e($p->PartyMobile) : '') . '
                        </td>
                        <td width="2%"></td>
                        <td width="49%" class="info-card">
                            <b>Payment</b><br>
                            Mode: ' . $e($p->PaymentTypeName ?? '') . '<br>'
                            . ($bankLine ? 'Bank: ' . $bankLine . '<br>' : '') . '
                            Ref: ' . $e($p->ReferenceNo ?? '') . '
                        </td>
                    </tr>
                </table>

                ' . (!empty($p->Notes) ? '<div style="margin-top:8px;"><b>Notes:</b> ' . $e($p->Notes) . '</div>' : '') . '

                <table style="margin-top:14px;">
                    <tr>
                        <td style="vertical-align:bottom;">' . $bankQrHtml . '</td>
                        <td style="text-align:right;vertical-align:bottom;">
                            For ' . $e($org->BrandName ?? $org->Name ?? '') . '<br><br>'
                            . $signatureSpaceHtml . '
                            Authorized Signatory
                        </td>
                    </tr>
                </table>

                <div class="footer-row">' . $e($footer) . '</div>

            </div>
        </body></html>';
    }

    // ── Shared PDF generation ─────────────────────────────────────────────────
    public function generatePaymentReceiptPdfBytes($paymentUID, $orgUID, $paperSize = 'A4') {

        $payment = $this->getPaymentDetailById($paymentUID, $orgUID);
        if (!$payment) return null;

        $this->load->model('organisation_model');
        $orgInfo    = $this->organisation_model->getOrgInfoCached($orgUID);
        $org        = $orgInfo->Data ?? null;
        $printTheme = $this->organisation_model->getPrintThemeByType($orgUID, 'Payment');
        $themeData  = $printTheme->Data ?? null;

        $html = $this->_renderPaymentReceiptHtml($payment, $org, $themeData);
        $html = $this->_applyPaymentPdfCssFixes($html, strtoupper(trim($paperSize)));

        require_once FCPATH . 'vendor/autoload.php';
        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Arial');
        $options->set('chroot', FCPATH);

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper(strtolower($paperSize), 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    // ── Invoice PDF generation (used by getInvoicePdfBase64 for email attachment) ─
    public function generateInvoicePdfBytes($transUID, $orgUID, $paperSize = 'A4') {

        $paperSize = strtoupper(trim($paperSize));
        $moduleUID = 103; // Sales Invoice

        return $this->generateTransactionPdfBytes($transUID, $orgUID, $moduleUID, $paperSize);
    }

    // ── Generic transaction PDF generation (works for any moduleUID) ──────────
    public function generateTransactionPdfBytes($transUID, $orgUID, $moduleUID, $paperSize = 'A4') {

        $paperSize = strtoupper(trim($paperSize));

        $header = $this->getTransactionById($transUID, $orgUID, $moduleUID);
        if (!$header) return null;

        $items = $this->getTransactionItems($transUID, $orgUID);

        $this->load->model('organisation_model');
        $orgInfo          = $this->organisation_model->getOrgInfoCached($orgUID);
        $printThemeResult = $this->organisation_model->getPrintThemeByType($orgUID, $header->TransType);
        $printBankAccount = $this->getPrintBankAccount($orgUID);

        $html = $this->_renderA4Html($moduleUID, $header, $items, $orgInfo->Data ?? null, $printThemeResult->Data ?? null, $printBankAccount);

        $html = preg_replace('/<link[^>]*fonts\.googleapis\.com[^>]*>/i', '', $html);
        $html = str_replace('</head>',
            '<style>body{padding:0!important;margin:0!important;}.print-content{margin:0!important;}#trans-type-header td{border-left:none!important;border-right:none!important;}</style></head>',
            $html);
        $html = $this->_compositeQrForPdf($html);
        $html = preg_replace('/\bdisplay\s*:\s*flex\s*;?/i',                       'display:block;', $html);
        $html = preg_replace('/\bflex-direction\s*:[^;"}]+;?/i',                   '', $html);
        $html = preg_replace('/\bjustify-content\s*:[^;"}]+;?/i',                  '', $html);
        $html = preg_replace('/\balign-items\s*:[^;"}]+;?/i',                      '', $html);
        $html = preg_replace('/\bheight\s*:\s*100%\s*;?/i',                        '', $html);
        $html = preg_replace('/\bposition\s*:\s*(absolute|relative|fixed)\s*;?/i', '', $html);
        $html = preg_replace('/\btransform\s*:[^;"}]+;?/i',                        '', $html);
        $html = preg_replace('/\btop\s*:\s*[^;"}]+;?/i',                           '', $html);
        $html = preg_replace('/\bleft\s*:\s*[^;"}]+;?/i',                          '', $html);
        $html = preg_replace('/@page\s*\{[^}]*\}/', "@page{size:{$paperSize};margin:10mm 5mm;}", $html);

        require_once FCPATH . 'vendor/autoload.php';
        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Arial');
        $options->set('chroot', FCPATH);

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper(strtolower($paperSize), 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    // Composites the QR code + logo overlay into a single base64 PNG so dompdf
    // can render it without position:absolute support.
    private function _compositeQrForPdf(string $html): string {
        $pattern = '/<div[^>]*>\s*<img[^>]+src="(https:\/\/api\.qrserver\.com[^"]+)"[^>]*>\s*<div[^>]*class="qr-logo-overlay"[^>]*>\s*<img[^>]+src="([^"]+)"[^>]*>\s*<\/div>\s*<\/div>/is';

        return preg_replace_callback($pattern, function ($m) {
            $qrUrl   = $m[1];
            $logoUrl = $m[2];

            $qrData = @file_get_contents($qrUrl);
            if (!$qrData) return '<img src="' . htmlspecialchars($qrUrl) . '" width="150" height="150">';

            $qrImg = @imagecreatefromstring($qrData);
            if (!$qrImg) return '<img src="' . htmlspecialchars($qrUrl) . '" width="150" height="150">';

            $logoData = @file_get_contents($logoUrl);
            if ($logoData) {
                $logoImg = @imagecreatefromstring($logoData);
                if ($logoImg) {
                    $qrW         = imagesx($qrImg);
                    $qrH         = imagesy($qrImg);
                    $logoSize    = (int)($qrW * 0.25);
                    $logoResized = imagecreatetruecolor($logoSize, $logoSize);
                    imagefill($logoResized, 0, 0, imagecolorallocate($logoResized, 255, 255, 255));
                    imagecopyresampled($logoResized, $logoImg, 0, 0, 0, 0, $logoSize, $logoSize, imagesx($logoImg), imagesy($logoImg));
                    $x       = (int)(($qrW - $logoSize) / 2);
                    $y       = (int)(($qrH - $logoSize) / 2);
                    $padding = 4;
                    $white   = imagecolorallocate($qrImg, 255, 255, 255);
                    imagefilledrectangle($qrImg, $x - $padding, $y - $padding, $x + $logoSize + $padding, $y + $logoSize + $padding, $white);
                    imagecopy($qrImg, $logoResized, $x, $y, 0, 0, $logoSize, $logoSize);
                    imagedestroy($logoResized);
                    imagedestroy($logoImg);
                }
            }

            ob_start();
            imagepng($qrImg);
            $pngData = ob_get_clean();
            imagedestroy($qrImg);

            $b64 = base64_encode($pngData);
            return '<img src="data:image/png;base64,' . $b64 . '" width="150" height="150">';
        }, $html);
    }

    private function _applyPaymentPdfCssFixes($html, $paperSize) {
        // Strip Google Fonts — dompdf cannot load WOFF2/web fonts
        $html = preg_replace('/<link[^>]*fonts\.googleapis\.com[^>]*>/i', '', $html);
        // Override body padding — @page margin handles spacing in PDF
        $html = str_replace('</head>',
            '<style>body{padding:0!important;margin:0!important;}.page{margin:0!important;}</style></head>',
            $html);
        // Dompdf CSS compatibility
        $html = preg_replace('/\bdisplay\s*:\s*flex\s*;?/i',                       'display:block;', $html);
        $html = preg_replace('/\bflex-direction\s*:[^;"}]+;?/i',                   '', $html);
        $html = preg_replace('/\bjustify-content\s*:[^;"}]+;?/i',                  '', $html);
        $html = preg_replace('/\balign-items\s*:[^;"}]+;?/i',                      '', $html);
        $html = preg_replace('/\bheight\s*:\s*100%\s*;?/i',                        '', $html);
        $html = preg_replace('/\bposition\s*:\s*(absolute|relative|fixed)\s*;?/i', '', $html);
        $html = preg_replace('/\btransform\s*:[^;"}]+;?/i',                        '', $html);
        $html = preg_replace('/\btop\s*:\s*[^;"}]+;?/i',                           '', $html);
        $html = preg_replace('/\bleft\s*:[^;"}]+;?/i',                             '', $html);
        // Page size
        $html = preg_replace('/@page\s*\{[^}]*\}/', "@page{size:{$paperSize};margin:10mm 5mm;}", $html);
        return $html;
    }

    public function _generateUniqueToken($table, $column) {

        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        do {

            $token = '';
            $bytes = random_bytes(10);
            for ($i = 0; $i < 10; $i++) {
                $token .= $chars[ord($bytes[$i]) % 62];
            }
            $exists = $this->ReadDb
                ->where($column, $token)
                ->count_all_results($table);

        } while ($exists > 0);
        return $token;
        
    }

    public function _generateReceiptToken() {

        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        do {

            $token = '';
            $bytes = random_bytes(10);

            for ($i = 0; $i < 10; $i++) {
                $token .= $chars[ord($bytes[$i]) % 62];
            }

            $exists = $this->ReadDb
                ->where('ReceiptToken', $token)
                ->count_all_results('Transaction.PaymentsTbl');

        } while ($exists > 0);

        return $token;

    }

    public function _uniqueTransToken() {

        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        do {

            $token = '';
            $bytes = random_bytes(10);

            for ($i = 0; $i < 10; $i++) {
                $token .= $chars[ord($bytes[$i]) % 62];
            }

            $exists = $this->ReadDb
                ->where('TransToken', $token)
                ->count_all_results('Transaction.TransactionsTbl');

        } while ($exists > 0);

        return $token;

    }

    // ── Build signature HTML for {{SIGNATURE_SPACE}} token ────────────────────
    // Returns the actual signature (image + label) if SignatureUID is set,
    // otherwise returns an empty space div (same as before).

    private function _buildSignatureHtml($signatureUID) {
        if ($signatureUID <= 0) {
            return '<div style="min-height:65px;"></div>';
        }

        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('SignatureUID, Label, SignatureType, ImagePath, DrawData, MimeType');
            $this->ReadDb->from('Users.UserSignaturesTbl');
            $this->ReadDb->where(['SignatureUID' => $signatureUID, 'IsDeleted' => 0]);
            $sig = $this->ReadDb->get()->row();

            if (!$sig) {
                return '<div style="min-height:65px;"></div>';
            }

            // Resolve image source — same logic as Profile.php getSignaturesJson()
            $sigType = strtolower($sig->SignatureType ?? '');
            if ($sigType === 'draw' && !empty($sig->DrawData)) {
                // DrawData is already a full data URL (data:image/png;base64,...)
                $drawRaw = $sig->DrawData;
                // Ensure it's a valid data URL — prefix if stored as raw base64
                if (strpos($drawRaw, 'data:image/') === 0) {
                    $imgSrc = $drawRaw;
                } else {
                    $mime   = $sig->MimeType ?: 'image/png';
                    $imgSrc = 'data:' . $mime . ';base64,' . $drawRaw;
                }
            } elseif (!empty($sig->ImagePath)) {
                // Uploaded image — build CDN URL from environment (same as Profile controller)
                $cdnBase = getenv('FILE_UPLOAD') === 'amazonaws'
                    ? getenv('CDN_URL')
                    : getenv('CFLARE_R2_CDN');
                $imgSrc = rtrim($cdnBase ?? '', '/') . '/' . ltrim($sig->ImagePath, '/');
            } else {
                return '<div style="min-height:65px;"></div>';
            }

            return '<div style="text-align:center;padding-top:8px;min-height:65px;">'
                 . '<img src="' . $imgSrc . '" alt="Signature" style="max-height:55px;max-width:160px;display:block;margin:0 auto;" />'
                 . '</div>';

        } catch (Exception $e) {
            return '<div style="min-height:65px;"></div>';
        }
    }

}


