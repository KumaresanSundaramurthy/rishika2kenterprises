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
            $this->ReadDb->select([
                'Ts.TransUID AS TransUID',
                'Ts.UniqueNumber AS UniqueNumber',
                'Ts.TransNumber AS TransNumber',
                'Ts.TransDate AS TransDate',
                'Ts.DocStatus AS Status',
                'Ts.NetAmount AS NetAmount',
                'Cust.Name AS PartyName',
                'Cust.MobileNumber AS MobileNumber',
                'Td.ValidityDate AS ValidityDate',
                'Ts.UpdatedOn AS UpdatedOn',
                "CONCAT(User.FirstName, ' ', User.LastName) AS UpdatedBy",
            ]);
            $this->ReadDb->from('Transaction.TransactionsTbl as Ts');
            $this->ReadDb->join('Customers.CustomerTbl as Cust', 'Cust.CustomerUID = Ts.PartyUID', 'LEFT');
            $this->ReadDb->join('Transaction.TransDetailTbl as Td', 'Td.TransUID = Ts.TransUID AND Td.FinancialYear = YEAR(Ts.TransDate)', 'LEFT');
            $this->ReadDb->join('Users.UserTbl as User', 'User.UserUID = Ts.UpdatedBy', 'left');
            $this->ReadDb->where(['Ts.IsDeleted' => 0, 'Ts.IsActive' => 1, 'Ts.ModuleUID' => $ModuleUID]);
            $this->applyFilters($filter);
            if ($isCount) {
                return $this->ReadDb->count_all_results();
            }
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

            // Regular status counts
            $this->ReadDb->select('Ts.DocStatus, COUNT(*) AS TotalCount, SUM(Ts.NetAmount) AS TotalAmount');
            $this->ReadDb->from('Transaction.TransactionsTbl AS Ts');
            $this->ReadDb->join('Transaction.TransDetailTbl AS Td', 'Td.TransUID = Ts.TransUID AND Td.FinancialYear = YEAR(Ts.TransDate)', 'LEFT');
            $this->ReadDb->where(['Ts.ModuleUID' => $moduleUID, 'Ts.OrgUID' => $orgUID, 'Ts.IsDeleted' => 0]);
            $this->ReadDb->group_by('Ts.DocStatus');
            $query = $this->ReadDb->get();
            if (!$query) return [];

            $out = [];
            foreach ($query->result() as $r) {
                $out[$r->DocStatus] = ['count' => (int)$r->TotalCount, 'amount' => (float)$r->TotalAmount];
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
            $this->ReadDb->where_not_in('Ts.DocStatus', ['Draft', 'Rejected']);
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

        if ($tab === 'Open') {
            // Open = Pending (not expired check — show all pending regardless of validity)
            $this->ReadDb->where('Ts.DocStatus', 'Pending');
        } elseif ($tab === 'Accepted') {
            $this->ReadDb->where('Ts.DocStatus', 'Accepted');
        } elseif ($tab === 'Converted') {
            $this->ReadDb->where('Ts.DocStatus', 'Converted');
        } elseif ($tab === 'Cancelled') {
            // Cancelled tab shows both Cancelled and Rejected records
            $this->ReadDb->where_in('Ts.DocStatus', ['Cancelled', 'Rejected']);
        } elseif ($tab === 'Draft') {
            $this->ReadDb->where('Ts.DocStatus', 'Draft');
        } else {
            // All — exclude Draft, Cancelled, Rejected
            $this->ReadDb->where_not_in('Ts.DocStatus', ['Draft', 'Cancelled', 'Rejected']);
        }

        if (!empty($filter['MinAmount'])) {
            $this->ReadDb->where('Ts.NetAmount >=', $filter['MinAmount']);
        }
        if (!empty($filter['MaxAmount'])) {
            $this->ReadDb->where('Ts.NetAmount <=', $filter['MaxAmount']);
        }

    }

    /** Full transaction header row by TransUID + OrgUID. */
    public function getTransactionById($transUID, $orgUID, $moduleUID) {

        $this->ReadDb->select([
            'Ts.*',
            'Cust.Name AS PartyName',
            'Cust.CountryCode AS PartyCountryCode',
            'Cust.MobileNumber AS PartyMobile',
            'Td.ValidityDays', 'Td.ValidityDate', 'Td.Reference',
            'Td.Notes', 'Td.TermsConditions', 'Td.AdditionalCharges AS AdditionalChargesJson',
        ]);
        $this->ReadDb->from('Transaction.TransactionsTbl AS Ts');
        $this->ReadDb->join('Customers.CustomerTbl AS Cust', 'Cust.CustomerUID = Ts.PartyUID AND Ts.PartyType = \'C\'', 'LEFT');
        $this->ReadDb->join('Transaction.TransDetailTbl AS Td', 'Td.TransUID = Ts.TransUID AND Td.FinancialYear = YEAR(Ts.TransDate)', 'LEFT');
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

            // Strip any ModuleUID filter passed by legacy callers — prefixes are now org-level
            // $cleanFilter = array_filter($FilterArray, function ($key) {
            //     return stripos($key, 'ModuleUID') === FALSE;
            // }, ARRAY_FILTER_USE_KEY);

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
            $this->ReadDb->reset_query();

            // Get MAX across ALL records (including soft-deleted) so we never
            // suggest a number that was previously used, even if now deleted.
            $this->ReadDb->select_max('TransNumber', 'MaxNumber');
            $this->ReadDb->from('Transaction.TransactionsTbl');
            $this->ReadDb->where([
                'PrefixUID' => (int) $prefixUID,
                'OrgUID'    => (int) $orgUID,
            ]);
            $query  = $this->ReadDb->get();
            $result = $query->row();
            $next   = $result ? ((int)($result->MaxNumber ?? 0) + 1) : 1;

            // Safety loop: keep incrementing until we find a number
            // that has no active (IsDeleted = 0) record.
            $maxAttempts = 100;
            while ($maxAttempts-- > 0) {
                $this->ReadDb->reset_query();
                $this->ReadDb->select('TransUID');
                $this->ReadDb->from('Transaction.TransactionsTbl');
                $this->ReadDb->where([
                    'PrefixUID'   => (int) $prefixUID,
                    'TransNumber' => $next,
                    'OrgUID'      => (int) $orgUID,
                    'IsDeleted'   => 0,
                ]);
                $this->ReadDb->limit(1);
                $check = $this->ReadDb->get()->row();
                if (!$check) break;
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
                'MAX(COALESCE(Ship.StateText, Bill.StateText)) AS StateText'
            );
            $where_ary = array(
                'Customers.IsDeleted' => 0,
                'Customers.IsActive' => 1,
            );
            $this->ReadDb->select($select_ary);
            $this->ReadDb->from('Customers.CustomerTbl as Customers');
            $this->ReadDb->join('Customers.CustAddressTbl as Bill', 'Bill.CustomerUID = Customers.CustomerUID AND Bill.IsDeleted = 0 AND Bill.IsActive = 1', 'LEFT');
            $this->ReadDb->join('Customers.CustAddressTbl as Ship', 'Ship.CustomerUID = Customers.CustomerUID AND Ship.IsDeleted = 0 AND Ship.IsActive = 1', 'LEFT');
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
                'product.AvailableQuantity AS AvailableQuantity',
                'product.Discount AS Discount',
                'product.DiscountTypeUID AS DiscountTypeUID',
                'discountType.Name AS DiscountTypeName',
                'primaryUnit.ShortName AS priUnitShortName',
            );
            $where_ary = array(
                'product.IsDeleted' => 0,
                'product.IsActive' => 1,
            );
            $this->ReadDb->select($select_ary);
            $this->ReadDb->from('Products.ProductTbl as product');
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

            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('PaymentTypeUID, Name, Code, IsCash');
            $this->ReadDb->from('Transaction.PaymentTypesTbl');
            $this->ReadDb->where(['IsDeleted' => 0, 'IsActive' => 1]);
            $this->ReadDb->order_by('PaymentTypeUID', 'ASC');
            $query = $this->ReadDb->get();
            if (!$query) return [];
            return $query->result();

        } catch (Exception $e) {
            return [];
        }

    }

    public function getOrgBankAccounts($orgUID) {

        try {

            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('BankAccountUID, AccountName, BankName, AccountNumber, IFSC, BranchName, UPIId, UPINumber, IsDefault');
            $this->ReadDb->from('Transaction.OrgBankAccountsTbl');
            $this->ReadDb->where(['OrgUID' => $orgUID, 'IsDeleted' => 0, 'IsActive' => 1]);
            $this->ReadDb->order_by('IsDefault', 'DESC');
            $this->ReadDb->order_by('AccountName', 'ASC');
            $query = $this->ReadDb->get();
            if (!$query) return [];
            return $query->result();

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

    public function getPaymentsList($limit, $offset, $orgUID, $filter) {

        try {

            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'P.PaymentUID',
                'P.TransUID',
                'P.ModuleUID',
                'P.PartyType',
                'P.Amount',
                'P.IsFullyPaid',
                'P.ExcessAmount',
                'P.ReferenceNo',
                'P.CreatedOn',
                'PT.Name AS PaymentTypeName',
                'PT.IsCash',
                'T.UniqueNumber AS TransNumber',
                'T.TransDate',
                'T.NetAmount AS BillAmount',
                "CASE WHEN P.PartyType = 'C' THEN C.Name ELSE V.Name END AS PartyName",
                'BA.AccountName',
                'BA.BankName',
            ]);
            $this->ReadDb->from('Transaction.PaymentsTbl AS P');
            $this->ReadDb->join('Transaction.PaymentTypesTbl AS PT', 'PT.PaymentTypeUID = P.PaymentTypeUID', 'LEFT');
            $this->ReadDb->join('Transaction.TransactionsTbl AS T', 'T.TransUID = P.TransUID', 'LEFT');
            $this->ReadDb->join('Customers.CustomerTbl AS C', "C.CustomerUID = P.PartyUID AND P.PartyType = 'C'", 'LEFT');
            $this->ReadDb->join('Vendors.VendorTbl AS V', "V.VendorUID = P.PartyUID AND P.PartyType = 'V'", 'LEFT');
            $this->ReadDb->join('Transaction.OrgBankAccountsTbl AS BA', 'BA.BankAccountUID = P.BankAccountUID', 'LEFT');
            $this->ReadDb->where(['P.OrgUID' => $orgUID, 'P.IsDeleted' => 0, 'P.IsActive' => 1]);

            if (!empty($filter['PartyType'])) {
                $this->ReadDb->where('P.PartyType', $filter['PartyType']);
            }
            if (!empty($filter['ModuleUID'])) {
                $this->ReadDb->where('P.ModuleUID', (int)$filter['ModuleUID']);
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
            $this->ReadDb->where(['P.OrgUID' => $orgUID, 'P.IsDeleted' => 0, 'P.IsActive' => 1]);

            if (!empty($filter['PartyType'])) {
                $this->ReadDb->where('P.PartyType', $filter['PartyType']);
            }
            if (!empty($filter['ModuleUID'])) {
                $this->ReadDb->where('P.ModuleUID', (int)$filter['ModuleUID']);
            }

            return $this->ReadDb->count_all_results();

        } catch (Exception $e) {
            return 0;
        }

    }

}