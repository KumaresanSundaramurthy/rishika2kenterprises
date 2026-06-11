<?php defined('BASEPATH') or exit('No direct script access allowed');

class Products_model extends CI_Model {

    private $EndReturnData;
    private $ReadDb;

    function __construct() {
        parent::__construct();

        $this->ReadDb = $this->load->database('ReadDB', TRUE);

    }

    public function itemFilterFormation($ModuleInfoData, $Filter) {

        $this->EndReturnData = new StdClass();
        try {

            $SearchDirectQuery = '';
            $SearchFilter = [];
            $sortOperation = [];
            if(!empty($Filter)) {
                if (array_key_exists('SearchAllData', $Filter)) {
                    $SearchDirectQuery .= "((". $ModuleInfoData->TableAliasName.".ItemName LIKE '%".$Filter['SearchAllData']."%' ) OR (".$ModuleInfoData->TableAliasName.".HSNSACCode LIKE '%".$Filter['SearchAllData']."%') OR (".$ModuleInfoData->TableAliasName.".PartNumber LIKE '%".$Filter['SearchAllData']."%') OR (".$ModuleInfoData->TableAliasName.".Description LIKE '%".$Filter['SearchAllData']."%'))";
                }
                if (array_key_exists('ProductType', $Filter)) {
                    if($SearchDirectQuery != '') {
                        $SearchDirectQuery .= ' AND ';
                    }
                    $quotedTypes = array_map(function($v) {
                        return "'" . str_replace("'", "''", $v) . "'";
                    }, $Filter['ProductType']);
                    $SearchDirectQuery .= $ModuleInfoData->TableAliasName.'.ProductType IN ('.implode(',', $quotedTypes).')';
                }
                if (array_key_exists('Category', $Filter)) {
                    if($SearchDirectQuery != '') {
                        $SearchDirectQuery .= ' AND ';    
                    }
                    $SearchDirectQuery .= $ModuleInfoData->TableAliasName.'.CategoryUID IN ('.implode(',', $Filter['Category']).')';
                }
                if (array_key_exists('Storage', $Filter)) {
                    if($SearchDirectQuery != '') {
                        $SearchDirectQuery .= ' AND ';    
                    }
                    $SearchDirectQuery .= $ModuleInfoData->TableAliasName.'.StorageUID IN ('.implode(',', $Filter['Storage']).')';
                }
                if (array_key_exists('StatusFilter', $Filter)) {
                    if($SearchDirectQuery != '') {
                        $SearchDirectQuery .= ' AND ';
                    }
                    $safeVals = array_map('intval', $Filter['StatusFilter']);
                    $SearchDirectQuery .= $ModuleInfoData->TableAliasName.'.IsActive IN ('.implode(',', $safeVals).')';
                }
                if (array_key_exists('NameSorting', $Filter)) {
                    $sortOperation[$ModuleInfoData->TableAliasName . '.ItemName'] = $Filter['NameSorting'] == 1 ? 'ASC' : 'DESC';
                }
                if (array_key_exists('CategorySorting', $Filter)) {
                    $sortOperation['Category.Name'] = $Filter['CategorySorting'] == 1 ? 'ASC' : 'DESC';
                }
                if (array_key_exists('QtySorting', $Filter)) {
                    $sortOperation['ProductStock.AvailableQty'] = $Filter['QtySorting'] == 1 ? 'ASC' : 'DESC';
                }
                if (array_key_exists('MRPSorting', $Filter)) {
                    $sortOperation['Products.MRP'] = $Filter['MRPSorting'] == 1 ? 'ASC' : 'DESC';
                }
                if (array_key_exists('SellingPriceSorting', $Filter)) {
                    $sortOperation['Products.SellingPrice'] = $Filter['SellingPriceSorting'] == 1 ? 'ASC' : 'DESC';
                }
                if (array_key_exists('PurchasePriceSorting', $Filter)) {
                    $sortOperation['Products.PurchasePrice'] = $Filter['PurchasePriceSorting'] == 1 ? 'ASC' : 'DESC';
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

    public function getProductsDetails($FilterArray = [], $OrderBy = 'ASC', $whereInCondition = []) {

        $this->EndReturnData = new StdClass();
        try {

            $this->ReadDb->db_debug = FALSE;

            $select_ary = array(
                'Products.ProductUID AS ProductUID',
                'Products.OrgUID AS OrgUID',
                'Products.ItemName AS ItemName',
                'Category.Name as CategoryName',
                'Products.ProductType AS ProductType',
                'Products.MRP AS MRP',
                'Products.SellingPrice AS SellingPrice',
                'Products.SellingProductTaxUID AS SellingProductTaxUID',
                'Products.TaxDetailsUID AS TaxDetailsUID',
                'Products.TaxPercentage AS TaxPercentage',
                'Products.CGST AS CGST',
                'Products.SGST AS SGST',
                'Products.IGST AS IGST',
                'Products.PrimaryUnitUID AS PrimaryUnitUID',
                'Products.CategoryUID AS CategoryUID',
                'Products.StorageUID AS StorageUID',
                'Products.HSNSACCode AS HSNSACCode',
                'Products.PurchasePrice AS PurchasePrice',
                'Products.PurchasePriceProductTaxUID AS PurchasePriceProductTaxUID',
                'Products.PartNumber AS PartNumber',
                'Products.SKU AS SKU',
                'Products.Description AS Description',
                'Products.Image AS Image',
                'Products.OpeningQuantity AS OpeningQuantity',
                'COALESCE(ProductStock.AvailableQty, 0) AS AvailableQuantity',
                'Products.OpeningPurchasePrice AS OpeningPurchasePrice',
                'Products.OpeningStockValue AS OpeningStockValue',
                'Products.Discount AS Discount',
                'Products.DiscountTypeUID AS DiscountTypeUID',
                'Products.LowStockAlertAt AS LowStockAlertAt',
                'Products.NotForSale AS NotForSale',
                'Products.IsRentable AS IsRentable',
                'Products.IsSizeApplicable AS IsSizeApplicable',
                'Products.IsComboItem AS IsComboItem',
                'Products.IsComposite AS IsComposite',
                'Products.IsBrandApplicable AS IsBrandApplicable',
                'Products.IsSerialTracked AS IsSerialTracked',
                'Products.CreatedOn as CreatedOn',
                'Products.UpdatedOn as UpdatedOn',
            );
            $WhereCondition = array(
                'Products.IsDeleted' => 0,
                'Products.IsActive' => 1,
            );
            $this->ReadDb->select($select_ary);
            $this->ReadDb->from('Products.ProductTbl as Products');
            $this->ReadDb->join('Products.CategoryTbl as Category', 'Category.CategoryUID = Products.CategoryUID', 'left');
            $this->ReadDb->join('Products.ProductStockTbl as ProductStock', 'ProductStock.ProductUID = Products.ProductUID', 'left');
            $this->ReadDb->where($WhereCondition);
            if (!empty($FilterArray)) {
                $this->ReadDb->where($FilterArray);
            }
            if (!empty($whereInCondition)) {
                foreach ($whereInCondition as $wkey => $wval) {
                    $this->ReadDb->where_in($wkey, $wval);
                }
            }
            $this->ReadDb->group_by('Products.ProductUID');
            $this->ReadDb->order_by('Products.ProductUID', $OrderBy);

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

    public function catgFilterFormation($ModuleInfoData, $Filter) {

        $this->EndReturnData = new StdClass();
        try {

            $SearchDirectQuery = '';
            $SearchFilter = [];
            $sortOperation = [];
            if(!empty($Filter)) {
                if (array_key_exists('SearchAllData', $Filter)) {
                    $SearchDirectQuery .= "((". $ModuleInfoData->TableAliasName.".Name LIKE '%".$Filter['SearchAllData']."%' ) OR (".$ModuleInfoData->TableAliasName.".Description LIKE '%".$Filter['SearchAllData']."%'))";
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

    public function getCategoriesDetails1($FilterArray = [], $OrderBy = 'ASC', $whereInCondition = []) {

        $this->EndReturnData = new StdClass();
        try {

            $this->ReadDb->db_debug = FALSE;

            $select_ary = array(
                'Category.CategoryUID AS CategoryUID',
                'Category.OrgUID AS OrgUID',
                'Category.Name AS Name',
                'Category.Description AS Description',
                'Category.Image AS Image',
                'Category.CreatedOn as CreatedOn',
                'Category.UpdatedOn as UpdatedOn',
            );

            $WhereCondition = array(
                'Category.IsDeleted' => 0,
                'Category.IsActive' => 1,
            );

            $this->ReadDb->select($select_ary);
            $this->ReadDb->from('Products.CategoryTbl as Category');
            $this->ReadDb->where($WhereCondition);

            // Standard Array Filtering
            if (!empty($FilterArray)) {
                $this->ReadDb->where($FilterArray);
            }

            // Where In Condition (Added to match getProductsDetails)
            if (!empty($whereInCondition)) {
                foreach ($whereInCondition as $wkey => $wval) {
                    $this->ReadDb->where_in($wkey, $wval);
                }
            }

            $this->ReadDb->order_by('Category.CategoryUID', $OrderBy);

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

    public function getCategoriesDetails($FilterArray) {

        $this->EndReturnData = new StdClass();
        try {

            // Cache all-categories (no filter) in Upstash for 1 hour
            $cacheKey = null;
            if (empty($FilterArray)) {
                $cacheKey = $this->redisservice->orgKey('org-categories');
                $cached   = $this->upstashservice->get($cacheKey);
                if ($cached !== null) {
                    $this->EndReturnData->Data = array_map(fn($r) => is_array($r) ? (object) $r : $r, (array)$cached);
                    return $this->EndReturnData->Data;
                }
            }

            $this->ReadDb->db_debug = FALSE;
            $select_ary = array(
                'Category.CategoryUID AS CategoryUID',
                'Category.OrgUID AS OrgUID',
                'Category.Name AS Name',
                'Category.Description AS Description',
                'Category.Image AS Image',
                'Category.CreatedOn as CreatedOn',
                'Category.UpdatedOn as UpdatedOn',
            );
            $WhereCondition = array(
                'Category.IsDeleted' => 0,
                'Category.IsActive' => 1,
            );
            $this->ReadDb->select($select_ary);
            $this->ReadDb->from('Products.CategoryTbl as Category');
            $this->ReadDb->where($WhereCondition);
            if (!empty($FilterArray)) {
                $this->ReadDb->where($FilterArray);
            }
            $this->ReadDb->order_by('Category.CategoryUID', 'ASC');

            $query = $this->ReadDb->get();
            $error = $this->ReadDb->error();
            if ($error['code']) {
                throw new Exception($error['message']);
            } else {
                $this->EndReturnData->Data = $query->result();
            }

            if ($cacheKey !== null) {
                $this->upstashservice->set($cacheKey, $this->EndReturnData->Data, 3600);
            }

            return $this->EndReturnData->Data;
        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);
        }

    }

    public function sizeFilterFormation($ModuleInfoData, $Filter) {

        $this->EndReturnData = new StdClass();
        try {

            $SearchDirectQuery = '';
            $SearchFilter = [];
            $sortOperation = [];
            if(!empty($Filter)) {
                if (array_key_exists('SearchAllData', $Filter)) {
                    $SearchDirectQuery .= "((". $ModuleInfoData->TableAliasName.".Name LIKE '%".$Filter['SearchAllData']."%' ) OR (".$ModuleInfoData->TableAliasName.".Description LIKE '%".$Filter['SearchAllData']."%'))";
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

    public function getSizeDetails($Filter) {

        $this->EndReturnData = new StdClass();
        try {

            $this->ReadDb->db_debug = FALSE;

            $select_ary = array(
                'Size.SizeUID AS SizeUID',
                'Size.OrgUID AS OrgUID',
                'Size.Name AS Name',
                'Size.Description AS Description',
                'Size.CreatedOn as CreatedOn',
                'Size.UpdatedOn as UpdatedOn',
            );
            $WhereCondition = array(
                'Size.IsDeleted' => 0,
                'Size.IsActive' => 1,
            );
            $this->ReadDb->select($select_ary);
            $this->ReadDb->from('Products.SizeTbl as Size');
            $this->ReadDb->where($WhereCondition);
            if (!empty($Filter)) {
                $this->ReadDb->where($Filter);
            }
            $this->ReadDb->group_by('Size.SizeUID');

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

    public function brandFilterFormation($ModuleInfoData, $Filter) {

        $this->EndReturnData = new StdClass();
        try {

            $SearchDirectQuery = '';
            $SearchFilter = [];
            $sortOperation = [];
            if(!empty($Filter)) {
                if (array_key_exists('SearchAllData', $Filter)) {
                    $SearchDirectQuery .= "((". $ModuleInfoData->TableAliasName.".Name LIKE '%".$Filter['SearchAllData']."%' ) OR (".$ModuleInfoData->TableAliasName.".Description LIKE '%".$Filter['SearchAllData']."%'))";
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

    public function getProductBOM($ParentProductUID) {

        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'Comp.ComponentUID AS ComponentUID',
                'Comp.ChildProductUID AS ChildProductUID',
                'Prod.ItemName AS ItemName',
                'Comp.Quantity AS Quantity',
            ]);
            $this->ReadDb->from('Products.ProductBOMTbl as Comp');
            $this->ReadDb->join('Products.ProductTbl as Prod', 'Prod.ProductUID = Comp.ChildProductUID', 'left');
            $this->ReadDb->where([
                'Comp.ParentProductUID' => (int) $ParentProductUID,
                'Comp.IsDeleted'        => 0,
                'Comp.IsActive'         => 1,
            ]);
            $query = $this->ReadDb->get();
            $error = $this->ReadDb->error();
            if ($error['code']) throw new Exception($error['message']);
            return $query->result();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }

    public function getItemsForBOM($OrgUID, $search = '', $excludeUID = 0) {

        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select(['Products.ProductUID', 'Products.ItemName']);
            $this->ReadDb->from('Products.ProductTbl as Products');
            $this->ReadDb->where([
                'Products.IsDeleted'  => 0,
                'Products.IsActive'   => 1,
                'Products.OrgUID'     => (int) $OrgUID,
                'Products.IsComposite'=> 0,
            ]);
            if (!empty($search)) {
                $this->ReadDb->like('Products.ItemName', $search);
            }
            if ($excludeUID > 0) {
                $this->ReadDb->where('Products.ProductUID !=', (int) $excludeUID);
            }
            $this->ReadDb->order_by('Products.ItemName', 'ASC');
            $this->ReadDb->limit(50);
            $query = $this->ReadDb->get();
            $error = $this->ReadDb->error();
            if ($error['code']) throw new Exception($error['message']);
            return $query->result();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }

    public function getCustomerTypePricing($ProductUID) {

        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'PR.ProductRateUID AS RateUID',
                'PR.CustomerTypeUID AS CustomerTypeUID',
                'CT.TypeName AS TypeName',
                'PR.SellingPrice AS SellingPrice',
            ]);
            $this->ReadDb->from('Products.ProductRateTbl as PR');
            $this->ReadDb->join('Customers.CustomerTypeTbl as CT', 'CT.CustomerTypeUID = PR.CustomerTypeUID', 'left');
            $this->ReadDb->where([
                'PR.ProductUID' => (int) $ProductUID,
                'PR.IsDeleted'  => 0,
                'PR.IsActive'   => 1,
            ]);
            $query = $this->ReadDb->get();
            $error = $this->ReadDb->error();
            if ($error['code']) throw new Exception($error['message']);
            return $query->result();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }

    // ─────────────────────────────────────────────────────────
    // Dedicated paginated list queries (replacing generic service)
    // ─────────────────────────────────────────────────────────
    public function getProductListPaginated($OrgUID, $limit, $offset, $searchQuery = '', $sortArr = []) {

        try {
            
            $this->ReadDb->db_debug = FALSE;
            $baseWhere = [
                'Products.IsDeleted' => 0,
                'Products.OrgUID'    => (int) $OrgUID,
            ];

            // Count query
            $this->ReadDb->select('COUNT(DISTINCT Products.ProductUID) AS TotalCount');
            $this->ReadDb->from('Products.ProductTbl as Products');
            $this->ReadDb->join('Products.CategoryTbl as Category', 'Category.CategoryUID = Products.CategoryUID', 'left');
            $this->ReadDb->where($baseWhere);
            if (!empty($searchQuery)) { $this->ReadDb->where($searchQuery, null, false); }
            $countQuery = $this->ReadDb->get();
            $countError = $this->ReadDb->error();
            if ($countError['code']) throw new Exception($countError['message']);
            $totalCount = (int) ($countQuery->row()->TotalCount ?? 0);

            // Data query
            $this->ReadDb->select([
                'Products.ProductUID AS ProductUID',
                'Products.ItemName AS ItemName',
                'Products.ProductType AS ProductType',
                'Category.Name AS CategoryName',
                'Products.SellingPrice AS SellingPrice',
                'Products.MRP AS MRP',
                'Products.PurchasePrice AS PurchasePrice',
                'Products.HSNSACCode AS HSNSACCode',
                'Products.PartNumber AS PartNumber',
                'Products.Image AS Image',
                'Products.IsComposite AS IsComposite',
                'COALESCE(ProductStock.AvailableQty, 0) AS AvailableQuantity',
                'Products.UpdatedOn AS UpdatedOn',
                'Products.IsActive AS IsActive',
                "CONCAT(User.FirstName, ' ', User.LastName) AS UpdatedBy",
                'Products.TaxPercentage AS TaxPercentage',
                'SelTaxType.Name AS SellingTaxType',
                'PurTaxType.Name AS PurchaseTaxType',
                'puid.ShortName AS PUShortName',
            ]);
            $this->ReadDb->from('Products.ProductTbl as Products');
            $this->ReadDb->join('Products.CategoryTbl as Category', 'Category.CategoryUID = Products.CategoryUID', 'left');
            $this->ReadDb->join('Products.ProductStockTbl as ProductStock', 'ProductStock.ProductUID = Products.ProductUID', 'left');
            $this->ReadDb->join('Global.ProductTaxTbl as SelTaxType', 'SelTaxType.ProductTaxUID = Products.SellingProductTaxUID', 'left');
            $this->ReadDb->join('Global.ProductTaxTbl as PurTaxType', 'PurTaxType.ProductTaxUID = Products.PurchasePriceProductTaxUID', 'left');
            $this->ReadDb->join('Global.PrimaryUnitTbl as puid', 'puid.PrimaryUnitUID = Products.PrimaryUnitUID', 'left');
            $this->ReadDb->join('Users.UserTbl as User', 'User.UserUID = Products.UpdatedBy', 'left');
            $this->ReadDb->where($baseWhere);
            if (!empty($searchQuery)) { $this->ReadDb->where($searchQuery, null, false); }
            if (!empty($sortArr)) {
                foreach ($sortArr as $col => $dir) { $this->ReadDb->order_by($col, $dir); }
            } else {
                $this->ReadDb->order_by('Products.ProductUID', 'DESC');
            }
            $this->ReadDb->limit($limit, $offset);
            $dataQuery = $this->ReadDb->get();
            $dataError = $this->ReadDb->error();
            if ($dataError['code']) throw new Exception($dataError['message']);

            $result             = new stdClass();
            $result->rows       = $dataQuery->result();
            $result->totalCount = $totalCount;
            return $result;

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }

    public function getCategoryListPaginated($OrgUID, $limit, $offset, $searchQuery = '', $sortArr = []) {

        try {
            $this->ReadDb->db_debug = FALSE;
            $baseWhere = [
                'Category.IsDeleted' => 0,
                'Category.IsActive'  => 1,
                'Category.OrgUID'    => (int) $OrgUID,
            ];

            // Count query
            $this->ReadDb->select('COUNT(Category.CategoryUID) AS TotalCount');
            $this->ReadDb->from('Products.CategoryTbl as Category');
            $this->ReadDb->where($baseWhere);
            if (!empty($searchQuery)) { $this->ReadDb->where($searchQuery, null, false); }
            $countQuery = $this->ReadDb->get();
            $countError = $this->ReadDb->error();
            if ($countError['code']) throw new Exception($countError['message']);
            $totalCount = (int) ($countQuery->row()->TotalCount ?? 0);

            // Data query
            $this->ReadDb->select([
                'Category.CategoryUID AS CategoryUID',
                'Category.Name AS Name',
                'Category.Description AS Description',
                'Category.Image AS Image',
                'Category.UpdatedOn AS UpdatedOn',
                "CONCAT(User.FirstName, ' ', User.LastName) AS UpdatedBy",
                'COUNT(CASE WHEN Products.IsDeleted = 0 AND Products.IsActive = 1 THEN 1 END) AS ProductCount',
            ]);
            $this->ReadDb->from('Products.CategoryTbl as Category');
            $this->ReadDb->join('Users.UserTbl as User', 'User.UserUID = Category.UpdatedBy', 'left');
            $this->ReadDb->join('Products.ProductTbl as Products', 'Products.CategoryUID = Category.CategoryUID', 'left');
            $this->ReadDb->where($baseWhere);
            if (!empty($searchQuery)) { $this->ReadDb->where($searchQuery, null, false); }
            $this->ReadDb->group_by('Category.CategoryUID');
            if (!empty($sortArr)) {
                foreach ($sortArr as $col => $dir) { $this->ReadDb->order_by($col, $dir); }
            } else {
                $this->ReadDb->order_by('Category.CategoryUID', 'DESC');
            }
            $this->ReadDb->limit($limit, $offset);
            $dataQuery = $this->ReadDb->get();
            $dataError = $this->ReadDb->error();
            if ($dataError['code']) throw new Exception($dataError['message']);

            $result             = new stdClass();
            $result->rows       = $dataQuery->result();
            $result->totalCount = $totalCount;
            return $result;

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }

    public function getProductsByCategoryUID($CategoryUID, $OrgUID) {

        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'Products.ProductUID AS ProductUID',
                'Products.ItemName AS ItemName',
                'Products.SellingPrice AS SellingPrice',
                'Products.MRP AS MRP',
                'Products.PurchasePrice AS PurchasePrice',
                'COALESCE(ProductStock.AvailableQty, 0) AS AvailableQuantity',
                'Products.ProductType AS ProductType',
                'Products.IsComposite AS IsComposite',
            ]);
            $this->ReadDb->from('Products.ProductTbl as Products');
            $this->ReadDb->join('Products.ProductStockTbl as ProductStock', 'ProductStock.ProductUID = Products.ProductUID', 'left');
            $this->ReadDb->where([
                'Products.CategoryUID' => (int) $CategoryUID,
                'Products.OrgUID'      => (int) $OrgUID,
                'Products.IsDeleted'   => 0,
                'Products.IsActive'    => 1,
            ]);
            $this->ReadDb->order_by('Products.ItemName', 'ASC');
            $query = $this->ReadDb->get();
            $error = $this->ReadDb->error();
            if ($error['code']) throw new Exception($error['message']);
            return $query->result();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }

    public function getProductStats($OrgUID) {

        try {
            $this->ReadDb->db_debug = FALSE;

            // Financial year start: April 1st
            $month   = (int) date('m');
            $year    = (int) date('Y');
            $fyStart = ($month >= 4) ? $year . '-04-01' : ($year - 1) . '-04-01';
            $sevenDaysAgo = date('Y-m-d', strtotime('-7 days'));
            $monthStart   = date('Y-m-01');

            $this->ReadDb->select([
                'COUNT(*)                                                                                                                                                                                                                       AS TotalProducts',
                'SUM(CASE WHEN Products.IsActive = 1 THEN 1 ELSE 0 END)                                                                                                                                                                        AS ActiveCount',
                'SUM(CASE WHEN Products.IsActive = 0 THEN 1 ELSE 0 END)                                                                                                                                                                        AS InActiveCount',
                'SUM(CASE WHEN Products.ProductType = \'Product\' AND Products.IsComposite = 0 AND Products.IsActive = 1 THEN COALESCE(ProductStock.AvailableQty, 0) * Products.PurchasePrice ELSE 0 END)                                         AS TotalStockValue',
                'SUM(CASE WHEN Products.CreatedOn >= \'' . $monthStart . '\' AND Products.IsActive = 1 THEN 1 ELSE 0 END)                                                                                                                       AS AddedThisMonth',
                'SUM(CASE WHEN Products.CreatedOn >= \'' . $fyStart . '\' AND Products.IsActive = 1 THEN 1 ELSE 0 END)                                                                                                                         AS AddedThisFY',
                'SUM(CASE WHEN Products.UpdatedOn >= \'' . $sevenDaysAgo . '\' AND Products.IsActive = 1 THEN 1 ELSE 0 END)                                                                                                                    AS RecentlyUpdated',
                'SUM(CASE WHEN Products.LowStockAlertAt > 0 AND COALESCE(ProductStock.AvailableQty, 0) <= Products.LowStockAlertAt AND COALESCE(ProductStock.AvailableQty, 0) > 0 AND Products.ProductType = \'Product\' AND Products.IsComposite = 0 AND Products.IsActive = 1 THEN 1 ELSE 0 END) AS LowStockItems',
                'SUM(CASE WHEN Products.NotForSale = \'Yes\' AND Products.IsActive = 1 THEN 1 ELSE 0 END)                                                                                                                                      AS NotForSale',
            ]);
            $this->ReadDb->from('Products.ProductTbl as Products');
            $this->ReadDb->join('Products.ProductStockTbl as ProductStock', 'ProductStock.ProductUID = Products.ProductUID', 'left');
            $this->ReadDb->where([
                'Products.IsDeleted' => 0,
                'Products.OrgUID'    => (int) $OrgUID,
            ]);
            $query = $this->ReadDb->get();
            $error = $this->ReadDb->error();
            if ($error['code']) throw new Exception($error['message']);
            return $query->row();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }

    public function getRentalConfig($productUID, $orgUID) {

        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->select('*');
        $this->ReadDb->from('Products.ProductRentalConfigTbl');
        $this->ReadDb->where([
            'ProductUID' => (int) $productUID,
            'OrgUID'     => (int) $orgUID,
            'IsDeleted'  => 0,
        ]);
        $this->ReadDb->limit(1);
        $query = $this->ReadDb->get();
        $error = $this->ReadDb->error();
        if ($error['code']) throw new Exception($error['message']);
        $rows = $query->result();
        return !empty($rows) ? $rows[0] : null;

    }

    public function getBrandDetails($FilterArray) {

        $this->EndReturnData = new StdClass();
        try {

            $this->ReadDb->db_debug = FALSE;
            $select_ary = array(
                'Brand.BrandUID AS BrandUID',
                'Brand.OrgUID AS OrgUID',
                'Brand.Name AS Name',
                'Brand.Description AS Description',
                'Brand.CreatedOn as CreatedOn',
                'Brand.UpdatedOn as UpdatedOn',
            );
            $WhereCondition = array(
                'Brand.IsDeleted' => 0,
                'Brand.IsActive' => 1,
            );
            $this->ReadDb->select($select_ary);
            $this->ReadDb->from('Products.BrandTbl as Brand');
            $this->ReadDb->where($WhereCondition);
            if (!empty($FilterArray)) {
                $this->ReadDb->where($FilterArray);
            }
            $this->ReadDb->group_by('Brand.BrandUID');

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

    // ── Cache helpers ─────────────────────────────────────────────────────────

    /**
     * Fetch all active products for org-level cache rebuild.
     * Joins CategoryTbl and PrimaryUnitTbl so entries are self-contained.
     */
    public function getProductsForCache($orgUID) {

        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'p.ProductUID',
                'p.ItemName',
                'p.ProductType',
                'p.CategoryUID',
                'cat.Name              AS CategoryName',
                'p.HSNSACCode',
                'p.PartNumber',
                'p.SKU',
                'p.Description',
                'p.PrimaryUnitUID',
                'pu.ShortName          AS PrimaryUnitName',
                'p.MRP',
                'p.SellingPrice',
                'p.PurchasePrice',
                'p.SellingProductTaxUID',
                'p.PurchasePriceProductTaxUID',
                'p.TaxDetailsUID',
                'p.TaxPercentage',
                'p.CGST',
                'p.SGST',
                'p.IGST',
                'COALESCE(ps.AvailableQty, 0) AS AvailableQuantity',
                'p.Discount',
                'p.DiscountTypeUID',
                'p.LowStockAlertAt',
                'p.NotForSale',
                'p.IsComboItem',
                'p.IsComposite',
                'p.IsSerialTracked',
                'p.Image',
            ]);
            $this->ReadDb->from('Products.ProductTbl p');
            $this->ReadDb->join('Products.CategoryTbl cat',  'cat.CategoryUID = p.CategoryUID',      'left');
            $this->ReadDb->join('Global.PrimaryUnitTbl pu',  'pu.PrimaryUnitUID = p.PrimaryUnitUID', 'left');
            $this->ReadDb->join('Products.ProductStockTbl ps', 'ps.ProductUID = p.ProductUID',       'left');
            $this->ReadDb->where([
                'p.OrgUID'    => (int)$orgUID,
                'p.IsDeleted' => 0,
                'p.IsActive'  => 1,
            ]);
            $this->ReadDb->order_by('p.ProductUID', 'ASC');

            $query = $this->ReadDb->get();
            $error = $this->ReadDb->error();
            if ($error['code']) throw new Exception($error['message']);
            return $query->result();

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }

    /**
     * Fetch all active categories for org-level cache rebuild.
     */
    public function getCategoriesForCache($orgUID) {

        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'CategoryUID',
                'Name',
                'Description',
                'Image',
            ]);
            $this->ReadDb->from('Products.CategoryTbl');
            $this->ReadDb->where([
                'OrgUID'    => (int)$orgUID,
                'IsDeleted' => 0,
                'IsActive'  => 1,
            ]);
            $this->ReadDb->order_by('CategoryUID', 'ASC');

            $query = $this->ReadDb->get();
            $error = $this->ReadDb->error();
            if ($error['code']) throw new Exception($error['message']);
            return $query->result();

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }

}
