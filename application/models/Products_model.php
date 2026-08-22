<?php defined('BASEPATH') or exit('No direct script access allowed');

class Products_model extends CI_Model {

    private $EndReturnData;
    private $ReadDb;

    function __construct() {
        parent::__construct();

        $this->ReadDb = $this->load->database('ReadDB', TRUE);

    }

    public function itemFilterFormation(object $ModuleInfoData, array $Filter): object {

        $this->EndReturnData = new StdClass();
        try {

            $SearchDirectQuery = '';
            $SearchFilter = [];
            $sortOperation = [];
            if(!empty($Filter)) {
                if (array_key_exists('SearchAllData', $Filter)) {
                    $s = $this->ReadDb->escape_like_str($Filter['SearchAllData']);
                    $t = $ModuleInfoData->TableAliasName;
                    // Description excluded — it stores Quill HTML (can be KB per row) and leading-wildcard LIKE on it is a full scan killer
                    $SearchDirectQuery .= "({$t}.ItemName LIKE '%{$s}%' OR {$t}.HSNSACCode LIKE '%{$s}%' OR {$t}.PartNumber LIKE '%{$s}%')";
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
                if (array_key_exists('Tax', $Filter)) {
                    if ($SearchDirectQuery != '') {
                        $SearchDirectQuery .= ' AND ';
                    }
                    $safeVals = array_map('intval', (array) $Filter['Tax']);
                    $SearchDirectQuery .= $ModuleInfoData->TableAliasName . '.TaxDetailsUID IN (' . implode(',', $safeVals) . ')';
                }
                if (array_key_exists('NameSorting', $Filter)) {
                    $sortOperation[$ModuleInfoData->TableAliasName . '.ItemName'] = $Filter['NameSorting'] == 1 ? 'ASC' : 'DESC';
                }
                if (array_key_exists('StatusSorting', $Filter)) {
                    $sortOperation[$ModuleInfoData->TableAliasName . '.IsActive'] = $Filter['StatusSorting'] == 1 ? 'ASC' : 'DESC';
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
                if (array_key_exists('LastUpdatedFilter', $Filter) && !empty($Filter['LastUpdatedFilter'])) {
                    $uids = array_values(array_filter(array_map('intval', (array) $Filter['LastUpdatedFilter'])));
                    if (!empty($uids)) {
                        $SearchDirectQuery .= ($SearchDirectQuery ? ' AND ' : '') . $ModuleInfoData->TableAliasName . '.UpdatedBy IN (' . implode(',', $uids) . ')';
                    }
                }
            }

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->SearchDirectQuery = $SearchDirectQuery;
            $this->EndReturnData->SearchFilter = $SearchFilter;
            $this->EndReturnData->sortOperation = $sortOperation;

        } catch (Exception $e) {
            notifyError('Products_model::itemFilterFormation', $e);
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            $this->EndReturnData->SearchDirectQuery = '';
            $this->EndReturnData->SearchFilter = [];
            $this->EndReturnData->sortOperation = [];
        }

        return $this->EndReturnData;

    }

    public function getProductUIDsByFilter(int $orgUID, array $filter = []): array {

        try {
            $this->ReadDb->db_debug = FALSE;

            $filterResult = $this->itemFilterFormation((object)['TableAliasName' => 'Products'], $filter);
            $searchQuery  = 'Products.IsComposite = 0';
            if ($filterResult->SearchDirectQuery) {
                $searchQuery .= ' AND (' . $filterResult->SearchDirectQuery . ')';
            }

            $this->ReadDb->select('Products.ProductUID');
            $this->ReadDb->from('Products.ProductTbl as Products');
            $this->ReadDb->where(['Products.IsDeleted' => 0, 'Products.OrgUID' => $orgUID]);
            $this->ReadDb->where($searchQuery, null, false);

            $q = $this->ReadDb->get();
            if (!$q) return [];

            return array_column($q->result_array(), 'ProductUID');

        } catch (Exception $e) {
            notifyError('Products_model::getProductUIDsByFilter', $e);
            return [];
        }

    }

    public function getProductsDetails(array $FilterArray = [], string $OrderBy = 'ASC', array $whereInCondition = []): array {

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
            notifyError('Products_model::getProductsDetails', $e);
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);
        }
    }

    public function catgFilterFormation(object $ModuleInfoData, array $Filter): object {

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
                if (array_key_exists('ProductFilter', $Filter) && !empty($Filter['ProductFilter'])) {
                    $rawUIDs = is_array($Filter['ProductFilter']) ? $Filter['ProductFilter'] : [$Filter['ProductFilter']];
                    $uids    = array_values(array_filter(array_map('intval', $rawUIDs)));
                    if (!empty($uids)) {
                        $uidStr = implode(',', $uids);
                        $sub    = "Category.CategoryUID IN (SELECT fp.CategoryUID FROM Products.ProductTbl AS fp WHERE fp.IsDeleted = 0 AND fp.ProductUID IN ({$uidStr}))";
                        $SearchDirectQuery .= ($SearchDirectQuery ? ' AND ' : '') . $sub;
                    }
                }
                if (array_key_exists('LastUpdatedFilter', $Filter) && !empty($Filter['LastUpdatedFilter'])) {
                    $uids = array_values(array_filter(array_map('intval', (array) $Filter['LastUpdatedFilter'])));
                    if (!empty($uids)) {
                        $SearchDirectQuery .= ($SearchDirectQuery ? ' AND ' : '') . $ModuleInfoData->TableAliasName . '.UpdatedBy IN (' . implode(',', $uids) . ')';
                    }
                }
            }

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->SearchDirectQuery = $SearchDirectQuery;
            $this->EndReturnData->SearchFilter = $SearchFilter;
            $this->EndReturnData->sortOperation = $sortOperation;

        } catch (Exception $e) {
            notifyError('Products_model::catgFilterFormation', $e);
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            $this->EndReturnData->SearchDirectQuery = '';
            $this->EndReturnData->SearchFilter = [];
            $this->EndReturnData->sortOperation = [];
        }

        return $this->EndReturnData;

    }

    public function getCategoriesDetails1(array $FilterArray = [], string $OrderBy = 'ASC', array $whereInCondition = []): array {

        $this->EndReturnData = new StdClass();
        try {

            $this->ReadDb->db_debug = FALSE;

            $select_ary = array(
                'Category.CategoryUID AS CategoryUID',
                'Category.OrgUID AS OrgUID',
                'Category.Name AS Name',
                'Category.Description AS Description',
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
            notifyError('Products_model::getCategoriesDetails1', $e);
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);
        }

    }

    public function getCategoriesDetails(array $FilterArray): array {

        $this->EndReturnData = new StdClass();
        try {

            // Cache all-categories (no filter) in Upstash as a hash keyed by CategoryUID
            $cacheKey = null;
            if (empty($FilterArray)) {
                $cacheKey = $this->redisservice->orgKey('categories');
                $cached   = $this->upstashservice->hgetall($cacheKey);
                if (!empty($cached)) {
                    $this->EndReturnData->Data = array_values(array_map(
                        fn($v) => is_array($v) ? (object)$v : $v,
                        $cached
                    ));
                    return $this->EndReturnData->Data;
                }
            }

            $this->ReadDb->db_debug = FALSE;
            $select_ary = array(
                'Category.CategoryUID AS CategoryUID',
                'Category.OrgUID AS OrgUID',
                'Category.Name AS Name',
                'Category.Description AS Description',
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
                $newMap = [];
                foreach ($this->EndReturnData->Data as $cat) {
                    $uid = (int)($cat->CategoryUID ?? 0);
                    if ($uid > 0) {
                        $newMap[(string)$uid] = [
                            'CategoryUID' => $uid,
                            'Name'        => $cat->Name        ?? '',
                            'Description' => $cat->Description ?? '',
                        ];
                    }
                }
                if (!empty($newMap)) {
                    $this->upstashservice->hmset($cacheKey, $newMap);
                }
            }

            return $this->EndReturnData->Data;
        } catch (Exception $e) {
            notifyError('Products_model::getCategoriesDetails', $e);
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);
        }

    }


    public function getProductBOM(int $ParentProductUID): array {

        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'Comp.ComponentUID AS ComponentUID',
                'Comp.ChildProductUID AS ChildProductUID',
                'Prod.ItemName AS ItemName',
                'Comp.Quantity AS Quantity',
                'Prod.MRP AS MRP',
                'Prod.SellingPrice AS SellingPrice',
                'Prod.PurchasePrice AS PurchasePrice',
                'Prod.TaxPercentage AS TaxPercentage',
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
            notifyError('Products_model::getProductBOM', $e);
            throw new Exception($e->getMessage());
        }

    }

    public function getItemsForBOM(int $OrgUID, string $search = '', int $excludeUID = 0): array {

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
            notifyError('Products_model::getItemsForBOM', $e);
            throw new Exception($e->getMessage());
        }

    }

    // ─────────────────────────────────────────────────────────
    // Dedicated paginated list queries (replacing generic service)
    // ─────────────────────────────────────────────────────────
    public function getProductListPaginated(int $OrgUID, int $limit, int $offset, string $searchQuery = '', array $sortArr = []): object {

        try {
            
            $this->ReadDb->db_debug = FALSE;
            $baseWhere = [
                'Products.IsDeleted' => 0,
                'Products.OrgUID'    => (int) $OrgUID,
            ];

            // Count query — no CategoryTbl join needed; category filter uses Products.CategoryUID directly
            $this->ReadDb->select('COUNT(*) AS TotalCount');
            $this->ReadDb->from('Products.ProductTbl as Products');
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
                'Products.IsComposite AS IsComposite',
                'Products.IsBrandApplicable AS IsBrandApplicable',
                'Products.IsSizeApplicable AS IsSizeApplicable',
                'Products.LowStockAlertAt AS LowStockAlertAt',
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

            $rows = $dataQuery->result();

            // Batch-fetch all attachments for this page of products in one query
            if (!empty($rows)) {
                $productUIDs = array_column((array)$rows, 'ProductUID');
                $cdnUrl = rtrim(getenv('FILE_UPLOAD') == 'amazonaws' ? getenv('CDN_URL') : getenv('CFLARE_R2_CDN'), '/');
                $placeholders = implode(',', array_fill(0, count($productUIDs), '?'));
                $attQuery = $this->ReadDb->query(
                    "SELECT EntityUID, FilePath, FileName FROM Products.EntityAttachmentsTbl
                      WHERE EntityType = 'Product' AND EntityUID IN ({$placeholders}) AND IsDeleted = 0
                      ORDER BY EntityUID, SortOrder ASC",
                    $productUIDs
                );
                $attMap = [];
                if ($attQuery) {
                    foreach ($attQuery->result() as $att) {
                        $attMap[(int)$att->EntityUID][] = [
                            'url'  => $cdnUrl . '/' . ltrim($att->FilePath, '/'),
                            'name' => $att->FileName,
                        ];
                    }
                }
                foreach ($rows as $row) {
                    $row->AttachmentsJson = json_encode($attMap[(int)$row->ProductUID] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                }
            }

            $result             = new stdClass();
            $result->rows       = $rows;
            $result->totalCount = $totalCount;
            return $result;

        } catch (Exception $e) {
            notifyError('Products_model::getProductListPaginated', $e);
            throw new Exception($e->getMessage());
        }

    }

    public function getProductsForExport(int $OrgUID): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'Products.ItemName AS ItemName',
                'Category.Name AS CategoryName',
                'Products.HSNSACCode AS HSNSACCode',
                'Products.PartNumber AS PartNumber',
                'Products.SellingPrice AS SellingPrice',
                'Products.TaxPercentage AS TaxPercentage',
                'Products.PurchasePrice AS PurchasePrice',
                'COALESCE(ProductStock.AvailableQty, 0) AS AvailableQuantity',
                'Products.IsActive AS IsActive',
                'Products.UpdatedOn AS UpdatedOn',
                "CONCAT(User.FirstName, ' ', User.LastName) AS UpdatedBy",
            ]);
            $this->ReadDb->from('Products.ProductTbl as Products');
            $this->ReadDb->join('Products.CategoryTbl as Category', 'Category.CategoryUID = Products.CategoryUID', 'left');
            $this->ReadDb->join('Products.ProductStockTbl as ProductStock', 'ProductStock.ProductUID = Products.ProductUID', 'left');
            $this->ReadDb->join('Users.UserTbl as User', 'User.UserUID = Products.UpdatedBy', 'left');
            $this->ReadDb->where(['Products.IsDeleted' => 0, 'Products.OrgUID' => $OrgUID]);
            $this->ReadDb->order_by('Products.ItemName', 'ASC');
            $query = $this->ReadDb->get();
            $error = $this->ReadDb->error();
            if ($error['code']) throw new Exception($error['message']);
            return $query->result();
        } catch (Exception $e) {
            notifyError('Products_model::getProductsForExport', $e);
            throw new Exception($e->getMessage());
        }
    }

    public function getCategoryListPaginated(int $OrgUID, int $limit, int $offset, string $searchQuery = '', array $sortArr = []): object {

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

            $rows = $dataQuery->result();

            // Batch-fetch all attachments for this page of categories in one query
            if (!empty($rows)) {
                $categoryUIDs = array_column((array)$rows, 'CategoryUID');
                $cdnUrl = rtrim(getenv('FILE_UPLOAD') == 'amazonaws' ? getenv('CDN_URL') : getenv('CFLARE_R2_CDN'), '/');
                $placeholders = implode(',', array_fill(0, count($categoryUIDs), '?'));
                $attQuery = $this->ReadDb->query(
                    "SELECT EntityUID, FilePath, FileName FROM Products.EntityAttachmentsTbl
                      WHERE EntityType = 'Category' AND EntityUID IN ({$placeholders}) AND IsDeleted = 0
                      ORDER BY EntityUID, SortOrder ASC",
                    $categoryUIDs
                );
                $attMap = [];
                if ($attQuery) {
                    foreach ($attQuery->result() as $att) {
                        $attMap[(int)$att->EntityUID][] = [
                            'url'  => $cdnUrl . '/' . ltrim($att->FilePath, '/'),
                            'name' => $att->FileName,
                        ];
                    }
                }
                foreach ($rows as $row) {
                    $row->AttachmentsJson = json_encode($attMap[(int)$row->CategoryUID] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                }
            }

            $result             = new stdClass();
            $result->rows       = $rows;
            $result->totalCount = $totalCount;
            return $result;

        } catch (Exception $e) {
            notifyError('Products_model::getCategoryListPaginated', $e);
            throw new Exception($e->getMessage());
        }

    }

    public function getProductsByCategoryUID(int $CategoryUID, int $OrgUID): array {

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
            notifyError('Products_model::getProductsByCategoryUID', $e);
            throw new Exception($e->getMessage());
        }

    }

    public function getProductStats(int $OrgUID): ?object {

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
            notifyError('Products_model::getProductStats', $e);
            throw new Exception($e->getMessage());
        }

    }

    public function getRentalConfig(int $productUID, int $orgUID): ?object {

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


    // ── Cache helpers ─────────────────────────────────────────────────────────

    /**
     * Fetch all active products for org-level cache rebuild.
     * Joins CategoryTbl and PrimaryUnitTbl so entries are self-contained.
     */
    public function getProductsForCache(int $orgUID): array {

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
            notifyError('Products_model::getProductsForCache', $e);
            throw new Exception($e->getMessage());
        }

    }

    /**
     * Fetch every active BOM row for the org in one query.
     * Used by syncProductsCache to embed items arrays into composite entries.
     * Returns flat array of rows: ParentProductUID, ChildProductUID, Quantity.
     */
    public function getAllProductBOMsForSync(int $orgUID): array {

        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'Comp.ParentProductUID',
                'Comp.ChildProductUID',
                'Comp.Quantity',
            ]);
            $this->ReadDb->from('Products.ProductBOMTbl Comp');
            $this->ReadDb->join('Products.ProductTbl p', 'p.ProductUID = Comp.ParentProductUID', 'inner');
            $this->ReadDb->where([
                'p.OrgUID'       => (int) $orgUID,
                'p.IsDeleted'    => 0,
                'p.IsActive'     => 1,
                'Comp.IsDeleted' => 0,
                'Comp.IsActive'  => 1,
            ]);

            $query = $this->ReadDb->get();
            $error = $this->ReadDb->error();
            if ($error['code']) throw new Exception($error['message']);
            return $query->result();

        } catch (Exception $e) {
            notifyError('Products_model::getAllProductBOMsForSync', $e);
            throw new Exception($e->getMessage());
        }

    }

    /**
     * Returns true if the product appears in any active transaction line.
     * Used to block delete when the item has transaction history.
     */
    public function productHasTransactions(int $productUID): bool {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('TransProdUID');
            $this->ReadDb->from('Transaction.TransProductsTbl');
            $this->ReadDb->where('ProductUID', $productUID);
            $this->ReadDb->where('IsDeleted', 0);
            $this->ReadDb->limit(1);
            return $this->ReadDb->get()->row() !== null;
        } catch (Exception $e) {
            notifyError('Products_model::productHasTransactions', $e);
            return false;
        }
    }

    /**
     * Returns true if the product is a BOM component of a combo that itself
     * has active transaction lines — i.e. the item was used "hidden" through a combo.
     */
    public function productUsedInComboWithTransactions(int $productUID): bool {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('t.TransProdUID');
            $this->ReadDb->from('Transaction.TransProductsTbl t');
            $this->ReadDb->join('Products.ProductBOMTbl b', 'b.ParentProductUID = t.ProductUID', 'inner');
            $this->ReadDb->where('b.ChildProductUID', $productUID);
            $this->ReadDb->where('b.IsDeleted', 0);
            $this->ReadDb->where('t.IsDeleted', 0);
            $this->ReadDb->limit(1);
            return $this->ReadDb->get()->row() !== null;
        } catch (Exception $e) {
            notifyError('Products_model::productUsedInComboWithTransactions', $e);
            return false;
        }
    }

    /**
     * Returns true if the product is an active component in any combo (ProductBOMTbl).
     * Used to block NotForSale / delete when the item is linked to a combo.
     */
    public function isProductLinkedToCombo(int $productUID): bool {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('ChildProductUID');
            $this->ReadDb->from('Products.ProductBOMTbl');
            $this->ReadDb->where('ChildProductUID', $productUID);
            $this->ReadDb->where('IsDeleted', 0);
            $this->ReadDb->where('IsActive', 1);
            $this->ReadDb->limit(1);
            return $this->ReadDb->get()->row() !== null;
        } catch (Exception $e) {
            notifyError('Products_model::isProductLinkedToCombo', $e);
            return false;
        }
    }

    /**
     * Fetch a single active product for Upstash HSET (avoids loading the full catalogue).
     * Same columns as getProductsForCache but scoped to one ProductUID.
     */
    public function getProductForCache(int $orgUID, int $productUID): ?object {

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
                'p.IsBrandApplicable',
            ]);
            $this->ReadDb->from('Products.ProductTbl p');
            $this->ReadDb->join('Products.CategoryTbl cat',    'cat.CategoryUID = p.CategoryUID',      'left');
            $this->ReadDb->join('Global.PrimaryUnitTbl pu',    'pu.PrimaryUnitUID = p.PrimaryUnitUID', 'left');
            $this->ReadDb->join('Products.ProductStockTbl ps', 'ps.ProductUID = p.ProductUID',         'left');
            $this->ReadDb->where([
                'p.ProductUID' => (int)$productUID,
                'p.OrgUID'     => (int)$orgUID,
                'p.IsDeleted'  => 0,
            ]);
            $this->ReadDb->limit(1);

            $query = $this->ReadDb->get();
            $error = $this->ReadDb->error();
            if ($error['code']) throw new Exception($error['message']);
            return $query->row();

        } catch (Exception $e) {
            notifyError('Products_model::getProductForCache', $e);
            throw new Exception($e->getMessage());
        }

    }

    /**
     * Fetch all active categories for org-level cache rebuild.
     */
    public function getCategoriesForCache(int $orgUID): array {

        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'CategoryUID',
                'Name',
                'Description',
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
            notifyError('Products_model::getCategoriesForCache', $e);
            throw new Exception($e->getMessage());
        }

    }

    // ── Brands ────────────────────────────────────────────────────────────────

    public function brandFilterFormation(object $ModuleInfoData, array $Filter): object {

        $this->EndReturnData = new StdClass();
        try {
            $SearchDirectQuery = '';
            $sortOperation     = [];
            if (!empty($Filter)) {
                if (array_key_exists('SearchAllData', $Filter)) {
                    $s = $this->ReadDb->escape_like_str($Filter['SearchAllData']);
                    $a = $ModuleInfoData->TableAliasName;
                    $SearchDirectQuery = "(({$a}.BrandName LIKE '%{$s}%') OR ({$a}.BrandCode LIKE '%{$s}%') OR ({$a}.Description LIKE '%{$s}%'))";
                }
                if (array_key_exists('NameSorting', $Filter)) {
                    $sortOperation[$ModuleInfoData->TableAliasName . '.BrandName'] = $Filter['NameSorting'] == 1 ? 'ASC' : 'DESC';
                }
                if (array_key_exists('ProductFilter', $Filter) && !empty($Filter['ProductFilter'])) {
                    $rawUIDs = is_array($Filter['ProductFilter']) ? $Filter['ProductFilter'] : [$Filter['ProductFilter']];
                    $uids    = array_values(array_filter(array_map('intval', $rawUIDs)));
                    if (!empty($uids)) {
                        $uidStr = implode(',', $uids);
                        $a      = $ModuleInfoData->TableAliasName;
                        $sub    = "{$a}.BrandUID IN (SELECT fp.BrandUID FROM Products.ProductTbl AS fp WHERE fp.IsDeleted = 0 AND fp.BrandUID IS NOT NULL AND fp.ProductUID IN ({$uidStr}))";
                        $SearchDirectQuery .= ($SearchDirectQuery ? ' AND ' : '') . $sub;
                    }
                }
                if (array_key_exists('LastUpdatedFilter', $Filter) && !empty($Filter['LastUpdatedFilter'])) {
                    $uids = array_values(array_filter(array_map('intval', (array) $Filter['LastUpdatedFilter'])));
                    if (!empty($uids)) {
                        $a = $ModuleInfoData->TableAliasName;
                        $SearchDirectQuery .= ($SearchDirectQuery ? ' AND ' : '') . $a . '.UpdatedBy IN (' . implode(',', $uids) . ')';
                    }
                }
            }
            $this->EndReturnData->Error             = false;
            $this->EndReturnData->SearchDirectQuery = $SearchDirectQuery;
            $this->EndReturnData->sortOperation     = $sortOperation;
        } catch (Exception $e) {
            notifyError('Products_model::brandFilterFormation', $e);
            $this->EndReturnData->Error             = true;
            $this->EndReturnData->SearchDirectQuery = '';
            $this->EndReturnData->sortOperation     = [];
        }
        return $this->EndReturnData;

    }

    public function getBrandListPaginated(int $orgUID, int $limit, int $offset, string $searchQuery = '', array $sortArr = []): object {

        try {
            $this->ReadDb->db_debug = FALSE;
            $baseWhere = [
                'Brand.IsDeleted' => 0,
                'Brand.IsActive'  => 1,
                'Brand.OrgUID'    => (int) $orgUID,
            ];

            // Count query
            $this->ReadDb->select('COUNT(Brand.BrandUID) AS TotalCount');
            $this->ReadDb->from('Products.BrandTbl as Brand');
            $this->ReadDb->where($baseWhere);
            if (!empty($searchQuery)) { $this->ReadDb->where($searchQuery, null, false); }
            $countQuery = $this->ReadDb->get();
            $countError = $this->ReadDb->error();
            if ($countError['code']) throw new Exception($countError['message']);
            $totalCount = (int) ($countQuery->row()->TotalCount ?? 0);

            // Data query
            $this->ReadDb->select([
                'Brand.BrandUID AS BrandUID',
                'Brand.BrandName AS BrandName',
                'Brand.BrandCode AS BrandCode',
                'Brand.Description AS Description',
                'Brand.UpdatedOn AS UpdatedOn',
                "CONCAT(User.FirstName, ' ', User.LastName) AS UpdatedBy",
            ]);
            $this->ReadDb->select(
                '(SELECT COUNT(DISTINCT pv.ProductUID) FROM Products.ProductVariantTbl pv WHERE pv.BrandUID = Brand.BrandUID AND pv.OrgUID = Brand.OrgUID AND pv.IsActive = 1) AS ProductCount',
                false
            );
            $this->ReadDb->from('Products.BrandTbl as Brand');
            $this->ReadDb->join('Users.UserTbl as User', 'User.UserUID = Brand.UpdatedBy', 'left');
            $this->ReadDb->where($baseWhere);
            if (!empty($searchQuery)) { $this->ReadDb->where($searchQuery, null, false); }
            $this->ReadDb->group_by('Brand.BrandUID');
            if (!empty($sortArr)) {
                foreach ($sortArr as $col => $dir) { $this->ReadDb->order_by($col, $dir); }
            } else {
                $this->ReadDb->order_by('Brand.BrandUID', 'DESC');
            }
            $this->ReadDb->limit($limit, $offset);
            $dataQuery = $this->ReadDb->get();
            $dataError = $this->ReadDb->error();
            if ($dataError['code']) throw new Exception($dataError['message']);

            $rows = $dataQuery->result();

            // Batch-fetch attachments for this page of brands
            if (!empty($rows)) {
                $brandUIDs    = array_column((array)$rows, 'BrandUID');
                $cdnUrl       = rtrim(getenv('FILE_UPLOAD') == 'amazonaws' ? getenv('CDN_URL') : getenv('CFLARE_R2_CDN'), '/');
                $placeholders = implode(',', array_fill(0, count($brandUIDs), '?'));
                $attQuery     = $this->ReadDb->query(
                    "SELECT EntityUID, FilePath, FileName FROM Products.EntityAttachmentsTbl
                      WHERE EntityType = 'Brand' AND EntityUID IN ({$placeholders}) AND IsDeleted = 0
                      ORDER BY EntityUID, SortOrder ASC",
                    $brandUIDs
                );
                $attMap = [];
                if ($attQuery) {
                    foreach ($attQuery->result() as $att) {
                        $attMap[(int)$att->EntityUID][] = [
                            'url'  => $cdnUrl . '/' . ltrim($att->FilePath, '/'),
                            'name' => $att->FileName,
                        ];
                    }
                }
                foreach ($rows as $row) {
                    $row->AttachmentsJson = json_encode($attMap[(int)$row->BrandUID] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                }
            }

            $result             = new stdClass();
            $result->rows       = $rows;
            $result->totalCount = $totalCount;
            return $result;

        } catch (Exception $e) {
            notifyError('Products_model::getBrandListPaginated', $e);
            throw new Exception($e->getMessage());
        }

    }

    public function getSizesForCache(int $orgUID): array {

        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'SizeUID',
                'Name AS SizeName',
                'SizeCode',
                'Length', 'Width', 'Height', 'Depth',
                'Diameter', 'Thickness',
                'Weight', 'DimensionUOM', 'WeightUOM',
                'Description',
            ]);
            $this->ReadDb->from('Products.SizeTbl');
            $this->ReadDb->where(['OrgUID' => (int)$orgUID, 'IsActive' => 1]);
            $this->ReadDb->order_by('Name', 'ASC');
            $query = $this->ReadDb->get();
            $error = $this->ReadDb->error();
            if ($error['code']) throw new Exception($error['message']);
            return $query->result();
        } catch (Exception $e) {
            notifyError('Products_model::getSizesForCache', $e);
            throw new Exception($e->getMessage());
        }

    }

    public function getBrandsForCache(int $orgUID): array {

        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select(['BrandUID', 'BrandName', 'BrandCode', 'Description']);
            $this->ReadDb->from('Products.BrandTbl');
            $this->ReadDb->where(['OrgUID' => (int)$orgUID, 'IsDeleted' => 0, 'IsActive' => 1]);
            $this->ReadDb->order_by('BrandUID', 'ASC');
            $query = $this->ReadDb->get();
            $error = $this->ReadDb->error();
            if ($error['code']) throw new Exception($error['message']);
            return $query->result();
        } catch (Exception $e) {
            notifyError('Products_model::getBrandsForCache', $e);
            throw new Exception($e->getMessage());
        }

    }

    public function getBrandByUID(int $brandUID, int $orgUID): ?object {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select(['BrandUID', 'BrandName', 'BrandCode', 'Description']);
            $this->ReadDb->from('Products.BrandTbl');
            $this->ReadDb->where(['BrandUID' => $brandUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]);
            $q = $this->ReadDb->get();
            return $q ? $q->row() : null;
        } catch (Exception $e) {
            notifyError('Products_model::getBrandByUID', $e);
            return null;
        }
    }

    public function isSizeInUse(int $sizeUID, int $orgUID): bool {
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->select('1');
        $this->ReadDb->from('Products.ProductVariantTbl');
        $this->ReadDb->where(['SizeUID' => $sizeUID, 'OrgUID' => $orgUID, 'IsActive' => 1]);
        $this->ReadDb->limit(1);
        return (int) $this->ReadDb->get()->num_rows() > 0;
    }

    public function isBrandInUse(int $brandUID, int $orgUID): bool {
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->select('1');
        $this->ReadDb->from('Products.ProductVariantTbl');
        $this->ReadDb->where(['BrandUID' => $brandUID, 'OrgUID' => $orgUID, 'IsActive' => 1]);
        $this->ReadDb->limit(1);
        return (int) $this->ReadDb->get()->num_rows() > 0;
    }

    public function getInUseSizeUIDs(array $sizeUIDs, int $orgUID): array {
        if (empty($sizeUIDs)) return [];
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->distinct();
        $this->ReadDb->select('SizeUID');
        $this->ReadDb->from('Products.ProductVariantTbl');
        $this->ReadDb->where('OrgUID', $orgUID);
        $this->ReadDb->where('IsActive', 1);
        $this->ReadDb->where_in('SizeUID', $sizeUIDs);
        return array_column($this->ReadDb->get()->result_array(), 'SizeUID');
    }

    public function getInUseBrandUIDs(array $brandUIDs, int $orgUID): array {
        if (empty($brandUIDs)) return [];
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->distinct();
        $this->ReadDb->select('BrandUID');
        $this->ReadDb->from('Products.ProductVariantTbl');
        $this->ReadDb->where('OrgUID', $orgUID);
        $this->ReadDb->where('IsActive', 1);
        $this->ReadDb->where_in('BrandUID', $brandUIDs);
        return array_column($this->ReadDb->get()->result_array(), 'BrandUID');
    }

    public function isDuplicateBrandName(int $orgUID, string $name, int $excludeUID = 0): bool {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('COUNT(*) AS cnt');
            $this->ReadDb->from('Products.BrandTbl');
            $this->ReadDb->where(['OrgUID' => $orgUID, 'IsDeleted' => 0, 'IsActive' => 1]);
            $this->ReadDb->where('LOWER(BrandName)', strtolower(trim($name)));
            if ($excludeUID > 0) { $this->ReadDb->where('BrandUID !=', $excludeUID); }
            $q = $this->ReadDb->get();
            return $q ? ((int)($q->row()->cnt ?? 0) > 0) : false;
        } catch (Exception $e) {
            notifyError('Products_model::isDuplicateBrandName', $e);
            return false;
        }
    }

    // ── Product / Category Attachments ────────────────────────────────────────

    /** Get all non-deleted attachments for an entity, ordered by SortOrder */
    public function getEntityAttachments(string $entityType, int $entityUID, int $orgUID): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('AttachUID, FileName, FilePath, FileSize, SortOrder, CreatedOn');
            $this->ReadDb->from('Products.EntityAttachmentsTbl');
            $this->ReadDb->where([
                'EntityType' => $entityType,
                'EntityUID'  => $entityUID,
                'OrgUID'     => $orgUID,
                'IsDeleted'  => 0,
            ]);
            $this->ReadDb->order_by('SortOrder', 'ASC');
            $query = $this->ReadDb->get();
            return $query ? $query->result_array() : [];
        } catch (Exception $e) {
            notifyError('Products_model::getEntityAttachments', $e);
            log_message('error', 'getEntityAttachments failed: ' . $e->getMessage());
            return [];
        }
    }

    /** Get the primary (first) attachment FilePath for an entity — used for list thumbnail */
    public function getEntityPrimaryImage(string $entityType, int $entityUID, int $orgUID): ?string {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('FilePath');
            $this->ReadDb->from('Products.EntityAttachmentsTbl');
            $this->ReadDb->where([
                'EntityType' => $entityType,
                'EntityUID'  => $entityUID,
                'OrgUID'     => $orgUID,
                'IsDeleted'  => 0,
            ]);
            $this->ReadDb->order_by('SortOrder', 'ASC');
            $this->ReadDb->limit(1);
            $q   = $this->ReadDb->get();
            $row = $q ? $q->row() : null;
            return $row ? $row->FilePath : null;
        } catch (Exception $e) {
            notifyError('Products_model::getEntityPrimaryImage', $e);
            return null;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Product Profile Modal
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Returns full product details plus live quick-stats for the profile overview tab.
     *
     * @param  int $productUID
     * @param  int $orgUID
     * @return object|null
     */
    public function getProductProfile(int $productUID, int $orgUID): ?object
    {
        try {
            $this->ReadDb->db_debug = FALSE;

            $this->ReadDb->select([
                'P.ProductUID', 'P.ItemName', 'P.PartNumber', 'P.SKU', 'P.ProductType',
                'P.IsComposite', 'P.Description', 'P.SellingPrice', 'P.PurchasePrice',
                'P.MRP', 'P.TaxPercentage', 'P.LowStockAlertAt', 'P.OpeningQuantity',
                'P.IsRentable', 'P.NotForSale', 'P.HSNSACCode', 'P.CreatedOn', 'P.UpdatedOn',
                'Cat.Name AS CategoryName',
                'SelTax.Name AS SellingTaxType',
                'PurTax.Name AS PurchaseTaxType',
                'Unit.ShortName AS UnitShortName',
                'COALESCE(PS.AvailableQty, 0) AS AvailableQty',
            ]);
            $this->ReadDb->from('Products.ProductTbl AS P');
            $this->ReadDb->join('Products.CategoryTbl AS Cat', 'Cat.CategoryUID = P.CategoryUID', 'LEFT');
            $this->ReadDb->join('Products.ProductStockTbl AS PS', 'PS.ProductUID = P.ProductUID', 'LEFT');
            $this->ReadDb->join('Global.ProductTaxTbl AS SelTax', 'SelTax.ProductTaxUID = P.SellingProductTaxUID', 'LEFT');
            $this->ReadDb->join('Global.ProductTaxTbl AS PurTax', 'PurTax.ProductTaxUID = P.PurchasePriceProductTaxUID', 'LEFT');
            $this->ReadDb->join('Global.PrimaryUnitTbl AS Unit', 'Unit.PrimaryUnitUID = P.PrimaryUnitUID', 'LEFT');
            $this->ReadDb->where(['P.ProductUID' => $productUID, 'P.OrgUID' => $orgUID, 'P.IsDeleted' => 0]);
            $q   = $this->ReadDb->get();
            $row = $q ? $q->row() : null;
            if (!$row) return null;

            // Quick stats: single aggregation over all confirmed transactions
            $stats = $this->ReadDb->query(
                "SELECT
                    COALESCE(SUM(CASE WHEN Ts.ModuleUID IN (103,112) AND Ts.DocStatus NOT IN ('Draft','Cancelled') THEN Tp.Quantity ELSE 0 END), 0) AS TotalUnitsSold,
                    COALESCE(SUM(CASE WHEN Ts.ModuleUID IN (103,112) AND Ts.DocStatus NOT IN ('Draft','Cancelled') THEN Tp.UnitPrice * Tp.Quantity ELSE 0 END), 0) AS TotalRevenue,
                    COALESCE(SUM(CASE WHEN Ts.ModuleUID = 105 AND Ts.DocStatus NOT IN ('Draft','Cancelled') THEN Tp.Quantity ELSE 0 END), 0) AS TotalUnitsPurchased,
                    COUNT(DISTINCT CASE WHEN Ts.ModuleUID IN (103,112) AND Ts.DocStatus NOT IN ('Draft','Cancelled') THEN Ts.TransUID ELSE NULL END) AS SalesTxCount
                 FROM Transaction.TransProductsTbl AS Tp
                 JOIN Transaction.TransactionsTbl AS Ts ON Ts.TransUID = Tp.TransUID AND Ts.IsDeleted = 0
                 WHERE Tp.ProductUID = ? AND Tp.OrgUID = ? AND Tp.IsDeleted = 0",
                [(int) $productUID, (int) $orgUID]
            );
            if ($stats) {
                $sr = $stats->row();
                $row->TotalUnitsSold      = (float) ($sr->TotalUnitsSold      ?? 0);
                $row->TotalRevenue        = (float) ($sr->TotalRevenue        ?? 0);
                $row->TotalUnitsPurchased = (float) ($sr->TotalUnitsPurchased ?? 0);
                $row->SalesTxCount        = (int)   ($sr->SalesTxCount        ?? 0);
            }

            // First product image (CDN-resolved URL)
            $cdnUrl = rtrim(getenv('FILE_UPLOAD') == 'amazonaws' ? getenv('CDN_URL') : getenv('CFLARE_R2_CDN'), '/');
            $imgQ = $this->ReadDb->query(
                "SELECT FilePath FROM Products.EntityAttachmentsTbl
                  WHERE EntityType = 'Product' AND EntityUID = ? AND OrgUID = ? AND IsDeleted = 0
                  ORDER BY SortOrder ASC LIMIT 1",
                [(int) $productUID, (int) $orgUID]
            );
            if ($imgQ && $imgQ->num_rows() > 0) {
                $row->ImageUrl = $cdnUrl . '/' . ltrim($imgQ->row()->FilePath, '/');
            } else {
                $row->ImageUrl = null;
            }

            return $row;
        } catch (Throwable $e) {
            notifyError('Products_model::getProductProfile', $e);
            log_message('error', 'getProductProfile failed for UID=' . $productUID . ': ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Returns top 5 customers by quantity purchased for a product (confirmed invoices + delivery challans).
     *
     * @param  int $productUID
     * @param  int $orgUID
     * @return array
     */
    public function getProductTopCustomers(int $productUID, int $orgUID): array
    {
        try {
            $this->ReadDb->db_debug = FALSE;
            $q = $this->ReadDb->query(
                "SELECT
                    COALESCE(Cust.DisplayName, Cust.CompanyName) AS CustomerName,
                    COUNT(DISTINCT Ts.TransUID) AS TxCount,
                    SUM(Tp.Quantity) AS TotalQty
                 FROM Transaction.TransProductsTbl AS Tp
                 JOIN Transaction.TransactionsTbl AS Ts
                      ON Ts.TransUID = Tp.TransUID AND Ts.ModuleUID IN (103,112)
                         AND Ts.DocStatus NOT IN ('Draft','Cancelled') AND Ts.IsDeleted = 0
                 JOIN Customers.CustomerTbl AS Cust ON Cust.CustomerUID = Ts.PartyUID
                 WHERE Tp.ProductUID = ? AND Tp.OrgUID = ? AND Tp.IsDeleted = 0
                 GROUP BY Cust.CustomerUID
                 ORDER BY TotalQty DESC
                 LIMIT 5",
                [(int) $productUID, (int) $orgUID]
            );
            return $q ? $q->result_array() : [];
        } catch (Throwable $e) {
            notifyError('Products_model::getProductTopCustomers', $e);
            return [];
        }
    }

    /**
     * Returns all transactions containing this product, newest first, capped at 200 rows.
     *
     * @param  int $productUID
     * @param  int $orgUID
     * @return array
     */
    public function getProductTransactionHistory(int $productUID, int $orgUID): array
    {
        try {
            $this->ReadDb->db_debug = FALSE;
            $q = $this->ReadDb->query(
                "SELECT
                    Ts.TransUID, Ts.UniqueNumber, Ts.TransDate, Ts.ModuleUID, Ts.DocStatus,
                    Ts.PartyType,
                    COALESCE(Cust.DisplayName, Cust.CompanyName) AS CustomerName,
                    COALESCE(Vend.DisplayName, Vend.CompanyName) AS VendorName,
                    Tp.Quantity, Tp.UnitPrice,
                    (Tp.Quantity * Tp.UnitPrice) AS LineAmount
                 FROM Transaction.TransProductsTbl AS Tp
                 JOIN Transaction.TransactionsTbl AS Ts
                      ON Ts.TransUID = Tp.TransUID AND Ts.IsDeleted = 0 AND Ts.IsActive = 1
                 LEFT JOIN Customers.CustomerTbl AS Cust
                      ON Cust.CustomerUID = Ts.PartyUID AND Ts.PartyType = 'C'
                 LEFT JOIN Vendors.VendorTbl AS Vend
                      ON Vend.VendorUID = Ts.PartyUID AND Ts.PartyType = 'S'
                 WHERE Tp.ProductUID = ? AND Tp.OrgUID = ? AND Tp.IsDeleted = 0
                 ORDER BY Ts.TransDate DESC, Ts.TransUID DESC
                 LIMIT 200",
                [(int) $productUID, (int) $orgUID]
            );
            return $q ? $q->result_array() : [];
        } catch (Throwable $e) {
            notifyError('Products_model::getProductTransactionHistory', $e);
            log_message('error', 'getProductTransactionHistory failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Returns confirmed stock-affecting transactions in chronological order for the running stock ledger.
     * Stock IN: 105 Purchases, 106 Sales Returns. Stock OUT: 103 Invoices, 108 Purchase Returns, 112 Delivery Challans.
     *
     * @param  int $productUID
     * @param  int $orgUID
     * @return array
     */
    public function getProductStockMovements(int $productUID, int $orgUID): array
    {
        try {
            $this->ReadDb->db_debug = FALSE;
            $q = $this->ReadDb->query(
                "SELECT
                    Ts.TransUID, Ts.UniqueNumber, Ts.TransDate, Ts.ModuleUID, Ts.DocStatus,
                    Ts.PartyType,
                    COALESCE(Cust.DisplayName, Cust.CompanyName) AS CustomerName,
                    COALESCE(Vend.DisplayName, Vend.CompanyName) AS VendorName,
                    Tp.Quantity
                 FROM Transaction.TransProductsTbl AS Tp
                 JOIN Transaction.TransactionsTbl AS Ts
                      ON Ts.TransUID = Tp.TransUID AND Ts.IsDeleted = 0 AND Ts.IsActive = 1
                         AND Ts.ModuleUID IN (103,105,106,108,112)
                         AND Ts.DocStatus NOT IN ('Draft','Cancelled')
                 LEFT JOIN Customers.CustomerTbl AS Cust
                      ON Cust.CustomerUID = Ts.PartyUID AND Ts.PartyType = 'C'
                 LEFT JOIN Vendors.VendorTbl AS Vend
                      ON Vend.VendorUID = Ts.PartyUID AND Ts.PartyType = 'S'
                 WHERE Tp.ProductUID = ? AND Tp.OrgUID = ? AND Tp.IsDeleted = 0
                 ORDER BY Ts.TransDate ASC, Ts.TransUID ASC",
                [(int) $productUID, (int) $orgUID]
            );
            return $q ? $q->result_array() : [];
        } catch (Throwable $e) {
            notifyError('Products_model::getProductStockMovements', $e);
            log_message('error', 'getProductStockMovements failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Returns audit log entries for a product from Security.UserAuditLogTbl, newest first.
     *
     * @param  int $productUID
     * @param  int $orgUID
     * @return array
     */
    public function getProductAuditHistory(int $productUID, int $orgUID): array
    {
        try {
            $this->ReadDb->db_debug = FALSE;
            $q = $this->ReadDb->query(
                "SELECT Action, EntityType, Module, EntityRef, Summary,
                        UserName, IPAddress, Source, DeviceType, AuditCategory,
                        OldValues, NewValues, CreatedAt
                 FROM Security.UserAuditLogTbl
                 WHERE EntityType = 'Product' AND EntityUID = ? AND OrgUID = ?
                 ORDER BY CreatedAt DESC
                 LIMIT 100",
                [(int) $productUID, (int) $orgUID]
            );
            return $q ? $q->result_array() : [];
        } catch (Throwable $e) {
            notifyError('Products_model::getProductAuditHistory', $e);
            log_message('error', 'getProductAuditHistory failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all active sizes for an org (for the variant size picker).
     * @param int $orgUID
     * @return array
     */
    public function getOrgSizes(int $orgUID): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select(['SizeUID', 'Name AS SizeName']);
            $this->ReadDb->from('Products.SizeTbl');
            $this->ReadDb->where(['OrgUID' => $orgUID, 'IsActive' => 1]);
            $this->ReadDb->order_by('Name', 'ASC');
            return $this->ReadDb->get()->result();
        } catch (Throwable $e) {
            notifyError('Products_model::getOrgSizes', $e);
            log_message('error', 'getOrgSizes failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all variants with their stock for a product (used in modal + edit form).
     * @param int $productUID
     * @param int $orgUID
     * @return array
     */
    public function getProductVariants(int $productUID, int $orgUID): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'pv.VariantUID',
                'pv.BrandUID',
                'pv.SizeUID',
                'pv.PartNumber',
                'pv.PurchasePrice',
                'pv.PurchaseTaxUID',
                'pv.SellingPrice',
                'pv.SellingTaxUID',
                'COALESCE(b.BrandName, \'\') AS BrandName',
                'COALESCE(b.BrandCode, \'\') AS BrandCode',
                'COALESCE(s.Name, \'\')      AS SizeName',
                'COALESCE(pvs.OpeningQty, 0)   AS OpeningQty',
                'COALESCE(pvs.AvailableQty, 0) AS AvailableQty',
            ]);
            $this->ReadDb->from('Products.ProductVariantTbl pv');
            $this->ReadDb->join('Products.BrandTbl b',                 'b.BrandUID = pv.BrandUID AND pv.BrandUID > 0', 'LEFT');
            $this->ReadDb->join('Products.SizeTbl s',                  's.SizeUID  = pv.SizeUID  AND pv.SizeUID  > 0', 'LEFT');
            $this->ReadDb->join('Products.ProductVariantStockTbl pvs', 'pvs.VariantUID = pv.VariantUID AND pvs.OrgUID = pv.OrgUID', 'LEFT');
            $this->ReadDb->where(['pv.ProductUID' => $productUID, 'pv.OrgUID' => $orgUID, 'pv.IsActive' => 1]);
            $this->ReadDb->order_by('s.Name', 'ASC');
            $this->ReadDb->order_by('b.BrandName', 'ASC');
            return $this->ReadDb->get()->result();
        } catch (Throwable $e) {
            notifyError('Products_model::getProductVariants', $e);
            log_message('error', 'getProductVariants failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Slim variant list for the Price List rule builder.
     * Returns VariantUID + human-readable Label (BrandName / SizeName).
     * @param int $productUID
     * @param int $orgUID
     * @return array
     */
    public function getProductVariantsForPricelist(int $productUID, int $orgUID): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('pv.VariantUID, COALESCE(b.BrandName, \'\') AS BrandName, COALESCE(s.Name, \'\') AS SizeName', false);
            $this->ReadDb->from('Products.ProductVariantTbl pv');
            $this->ReadDb->join('Products.BrandTbl b', 'b.BrandUID = pv.BrandUID AND pv.BrandUID > 0', 'LEFT');
            $this->ReadDb->join('Products.SizeTbl s',  's.SizeUID  = pv.SizeUID  AND pv.SizeUID  > 0', 'LEFT');
            $this->ReadDb->where(['pv.ProductUID' => $productUID, 'pv.OrgUID' => $orgUID, 'pv.IsActive' => 1]);
            $this->ReadDb->order_by('s.Name', 'ASC');
            $this->ReadDb->order_by('b.BrandName', 'ASC');
            $rows   = $this->ReadDb->get()->result();
            $result = [];
            foreach ($rows as $row) {
                $parts    = array_filter([$row->BrandName, $row->SizeName]);
                $result[] = [
                    'VariantUID' => (int) $row->VariantUID,
                    'BrandName'  => $row->BrandName,
                    'SizeName'   => $row->SizeName,
                    'Label'      => $parts ? implode(' / ', $parts) : ('Variant #' . $row->VariantUID),
                ];
            }
            return $result;
        } catch (Throwable $e) {
            notifyError('Products_model::getProductVariantsForPricelist', $e);
            log_message('error', 'getProductVariantsForPricelist failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Bulk-fetch all active variants for an org in one query for cache sync.
     * Returns [productUID => [{VariantUID,BrandName,SizeName,Label}, ...], ...]
     * @param int $orgUID
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function getAllProductVariantsForSync(int $orgUID): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('pv.ProductUID, pv.VariantUID, COALESCE(b.BrandName, \'\') AS BrandName, COALESCE(s.Name, \'\') AS SizeName', false);
            $this->ReadDb->from('Products.ProductVariantTbl pv');
            $this->ReadDb->join('Products.BrandTbl b', 'b.BrandUID = pv.BrandUID AND pv.BrandUID > 0', 'LEFT');
            $this->ReadDb->join('Products.SizeTbl s',  's.SizeUID  = pv.SizeUID  AND pv.SizeUID  > 0', 'LEFT');
            $this->ReadDb->where(['pv.OrgUID' => $orgUID, 'pv.IsActive' => 1]);
            $this->ReadDb->order_by('pv.ProductUID', 'ASC');
            $this->ReadDb->order_by('s.Name', 'ASC');
            $this->ReadDb->order_by('b.BrandName', 'ASC');
            $rows   = $this->ReadDb->get()->result();
            $result = [];
            foreach ($rows as $row) {
                $pKey  = (string)(int)$row->ProductUID;
                $parts = array_filter([$row->BrandName, $row->SizeName]);
                $result[$pKey][] = [
                    'VariantUID' => (int) $row->VariantUID,
                    'BrandName'  => $row->BrandName,
                    'SizeName'   => $row->SizeName,
                    'Label'      => $parts ? implode(' / ', $parts) : ('Variant #' . (int)$row->VariantUID),
                ];
            }
            return $result;
        } catch (Throwable $e) {
            notifyError('Products_model::getAllProductVariantsForSync', $e);
            log_message('error', 'getAllProductVariantsForSync failed: ' . $e->getMessage());
            return [];
        }
    }

    public function getSizeListPaginated(int $orgUID, int $limit, int $offset, string $searchQuery = '', array $sortArr = []): object {

        try {
            $this->ReadDb->db_debug = FALSE;
            $baseWhere = [
                'Size.OrgUID'   => (int) $orgUID,
                'Size.IsActive' => 1,
            ];

            $this->ReadDb->select('COUNT(Size.SizeUID) AS TotalCount');
            $this->ReadDb->from('Products.SizeTbl as Size');
            $this->ReadDb->where($baseWhere);
            if (!empty($searchQuery)) { $this->ReadDb->where($searchQuery, null, false); }
            $countQuery = $this->ReadDb->get();
            $countError = $this->ReadDb->error();
            if ($countError['code']) throw new Exception($countError['message']);
            $totalCount = (int) ($countQuery->row()->TotalCount ?? 0);

            $this->ReadDb->select([
                'Size.SizeUID      AS SizeUID',
                'Size.Name         AS SizeName',
                'Size.SizeCode     AS SizeCode',
                'Size.Length       AS Length',
                'Size.Width        AS Width',
                'Size.Height       AS Height',
                'Size.Depth        AS Depth',
                'Size.Diameter     AS Diameter',
                'Size.Thickness    AS Thickness',
                'Size.Weight       AS Weight',
                'Size.DimensionUOM AS DimensionUOM',
                'Size.WeightUOM    AS WeightUOM',
                'Size.Description  AS Description',
                'Size.UpdatedOn    AS UpdatedOn',
                "CONCAT(COALESCE(User.FirstName,''), ' ', COALESCE(User.LastName,'')) AS UpdatedBy",
            ]);
            $this->ReadDb->select(
                '(SELECT COUNT(DISTINCT pv.ProductUID) FROM Products.ProductVariantTbl pv WHERE pv.SizeUID = Size.SizeUID AND pv.OrgUID = Size.OrgUID AND pv.IsActive = 1) AS ProductCount',
                false
            );
            $this->ReadDb->from('Products.SizeTbl as Size');
            $this->ReadDb->join('Users.UserTbl as User', 'User.UserUID = Size.UpdatedBy', 'left');
            $this->ReadDb->where($baseWhere);
            if (!empty($searchQuery)) { $this->ReadDb->where($searchQuery, null, false); }
            $this->ReadDb->group_by('Size.SizeUID');
            if (!empty($sortArr)) {
                foreach ($sortArr as $col => $dir) { $this->ReadDb->order_by($col, $dir); }
            } else {
                $this->ReadDb->order_by('Size.SizeUID', 'DESC');
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
            notifyError('Products_model::getSizeListPaginated', $e);
            throw new Exception($e->getMessage());
        }

    }

    public function sizeFilterFormation(object $ModuleInfoData, array $Filter): object {

        $this->EndReturnData = new stdClass();
        try {
            $SearchDirectQuery = '';
            $sortOperation     = [];
            if (!empty($Filter)) {
                if (array_key_exists('SearchAllData', $Filter)) {
                    $s = $this->ReadDb->escape_like_str($Filter['SearchAllData']);
                    $a = $ModuleInfoData->TableAliasName;
                    $SearchDirectQuery = "({$a}.Name LIKE '%{$s}%')";
                }
                if (array_key_exists('NameSorting', $Filter)) {
                    $sortOperation[$ModuleInfoData->TableAliasName . '.Name'] = $Filter['NameSorting'] == 1 ? 'ASC' : 'DESC';
                }
                if (array_key_exists('LastUpdatedFilter', $Filter) && !empty($Filter['LastUpdatedFilter'])) {
                    $uids = array_values(array_filter(array_map('intval', (array) $Filter['LastUpdatedFilter'])));
                    if (!empty($uids)) {
                        $a = $ModuleInfoData->TableAliasName;
                        $SearchDirectQuery .= ($SearchDirectQuery ? ' AND ' : '') . $a . '.UpdatedBy IN (' . implode(',', $uids) . ')';
                    }
                }
            }
            $this->EndReturnData->Error             = false;
            $this->EndReturnData->SearchDirectQuery = $SearchDirectQuery;
            $this->EndReturnData->sortOperation     = $sortOperation;
        } catch (Exception $e) {
            notifyError('Products_model::sizeFilterFormation', $e);
            $this->EndReturnData->Error             = true;
            $this->EndReturnData->SearchDirectQuery = '';
            $this->EndReturnData->sortOperation     = [];
        }
        return $this->EndReturnData;

    }

    public function isDuplicateSizeName(int $orgUID, string $sizeName, int $excludeUID = 0): bool {

        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('SizeUID');
            $this->ReadDb->from('Products.SizeTbl');
            $this->ReadDb->where(['OrgUID' => $orgUID, 'Name' => $sizeName, 'IsActive' => 1]);
            if ($excludeUID > 0) { $this->ReadDb->where('SizeUID !=', $excludeUID); }
            $q = $this->ReadDb->get();
            return $q && $q->num_rows() > 0;
        } catch (Exception $e) {
            notifyError('Products_model::isDuplicateSizeName', $e);
            return false;
        }

    }

    public function getSizeByUID(int $sizeUID, int $orgUID): ?object {

        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select(['SizeUID', 'Name AS SizeName']);
            $this->ReadDb->from('Products.SizeTbl');
            $this->ReadDb->where(['SizeUID' => $sizeUID, 'OrgUID' => $orgUID, 'IsActive' => 1]);
            $q = $this->ReadDb->get();
            return $q ? $q->row() : null;
        } catch (Exception $e) {
            notifyError('Products_model::getSizeByUID', $e);
            return null;
        }

    }

}