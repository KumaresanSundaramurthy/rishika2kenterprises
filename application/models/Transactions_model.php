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
                'Ts.Status AS Status',
                'Ts.NetAmount AS NetAmount',
                'Ts.IsFullyPaid AS IsFullyPaid',
                'Ts.PaidAmount AS PaidAmount',
                'Ts.BalanceAmount AS BalanceAmount',
                'Ts.CreatedOn AS CreatedOn',
                'Ts.UpdatedOn AS UpdatedOn',
                'Cust.Name AS PartyName',
                'Td.ValidityDate AS ValidityDate',
            ]);
            $this->ReadDb->from('Transaction.TransactionsTbl as Ts');
            $this->ReadDb->join('Customers.CustomerTbl as Cust', 'Cust.CustomerUID = Ts.PartyUID AND Ts.PartyType = \'C\'', 'LEFT');
            $this->ReadDb->join('Transaction.TransDetailTbl as Td', 'Td.TransUID = Ts.TransUID AND Td.FinancialYear = YEAR(Ts.TransDate)', 'LEFT');
            $this->ReadDb->where(['Ts.IsDeleted' => 0, 'Ts.IsActive' => 1, 'Ts.ModuleUID' => $ModuleUID]);
            $this->applyFilters($filter);
            if ($isCount) {
                return $this->ReadDb->count_all_results();
            }
            $this->ReadDb->order_by('Ts.TransUID', 'DESC');
            if ($limit > 0) {
                $this->ReadDb->limit($limit, $offset);
            }
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

    private function applyFilters($filter) {
        if (empty($filter)) return;

        if (!empty($filter['Name'])) {
            $this->ReadDb->group_start()
                        ->like('Cust.Name', $filter['Name'], 'both')
                        ->or_like('Ts.TransNumber', $filter['Name'], 'both')
                        ->or_like('Ts.UniqueNumber', $filter['Name'], 'both')
                        ->group_end();
        }

        if (!empty($filter['DateFrom']) && !empty($filter['DateTo'])) {
            $this->ReadDb->where('Ts.TransDate >=', $filter['DateFrom']);
            $this->ReadDb->where('Ts.TransDate <=', $filter['DateTo']);
        }

        if (!empty($filter['Status'])) {
            $validStatuses = ['Draft', 'Pending', 'Accepted', 'Rejected', 'Converted'];
            $status = ucfirst(strtolower($filter['Status']));
            if (in_array($status, $validStatuses)) {
                $this->ReadDb->where('Ts.Status', $status);
            }
        }
        
        if (!empty($filter['MinAmount'])) {
            $this->ReadDb->where('Ts.NetAmount >=', $filter['MinAmount']);
        }
        
        if (!empty($filter['MaxAmount'])) {
            $this->ReadDb->where('Ts.NetAmount <=', $filter['MaxAmount']);
        }
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
            $cleanFilter = array_filter($FilterArray, function ($key) {
                return stripos($key, 'ModuleUID') === FALSE;
            }, ARRAY_FILTER_USE_KEY);

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
                'PrefixUID' => (int) $prefixUID,
                'OrgUID'    => (int) $orgUID,
                'ModuleUID' => (int) $moduleUID,
                'IsDeleted' => 0,
            ]);
            $query  = $this->ReadDb->get();
            $result = $query->row();
            return $result ? ((int)($result->MaxNumber ?? 0) + 1) : 1;

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
            $this->ReadDb->select('TransUID');
            $this->ReadDb->from('Transaction.TransactionsTbl');
            $this->ReadDb->where([
                'PrefixUID'   => (int) $prefixUID,
                'TransNumber' => (int) $transNumber,
                'OrgUID'      => (int) $orgUID,
                'ModuleUID'   => (int) $moduleUID,
                'IsDeleted'   => 0,
            ]);
            $this->ReadDb->limit(1);
            $query = $this->ReadDb->get();
            return $query->row();

        } catch (Exception $e) {
            return NULL;
        }

    }

    public function getTransPageSettings($whereCondition = []) {

        $this->EndReturnData = new StdClass();
        try {

            $this->ReadDb->select('pageSettings.TransPageSetgUID as TransPageSetgUID, pageSettings.ModuleUID as ModuleUID, pageSettings.DefaultPrefix as DefaultPrefix, pageSettings.ShowFiscalYear as ShowFiscalYear, pageSettings.FiscalYearType as FiscalYearType, pageSettings.InvoiceSepText as InvoiceSepText, pageSettings.ValidityDays as ValidityDays');
            $this->ReadDb->from('Transaction.TransPageSettingsTbl as pageSettings');
            if (sizeof($whereCondition) > 0) {
                $this->ReadDb->where($whereCondition);
            }
            $this->ReadDb->limit(1);
            $query = $this->ReadDb->get();
            $error = $this->ReadDb->error();
            if ($error['code']) {
                throw new Exception($error['message']);
            } else {
                $this->EndReturnData->Data = $query->result()[0];
            }

            return $this->EndReturnData->Data;

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);
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

}