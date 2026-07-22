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
            }
            $this->EndReturnData->Error             = false;
            $this->EndReturnData->SearchDirectQuery = $SearchDirectQuery;
            $this->EndReturnData->sortOperation     = $sortOperation;
        } catch (Exception $e) {
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
                '0 AS ProductCount',
            ]);
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
            return null;
        }
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
            return null;
        }
    }

}
