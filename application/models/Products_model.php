<?php defined('BASEPATH') or exit('No direct script access allowed');

class Products_model extends CI_Model
{

    private $EndReturnData;
    private $ProductDb;

    function __construct()
    {
        parent::__construct();

        $this->ProductDb = $this->load->database('Products', TRUE);
    }

    public function itemFilterFormation($ModuleInfoData, $Filter) {

        $this->EndReturnData = new StdClass();
        try {

            $SearchDirectQuery = '';
            $SearchFilter = [];
            if(!empty($Filter)) {
                if (array_key_exists('SearchAllData', $Filter)) {
                    $SearchDirectQuery .= '(('. $ModuleInfoData->TableAliasName.'.ItemName LIKE "%'.$Filter['SearchAllData'].'%" ) OR ('.$ModuleInfoData->TableAliasName.'.HSNSACCode LIKE "%'.$Filter['SearchAllData'].'%") OR ('.$ModuleInfoData->TableAliasName.'.PartNumber LIKE "%'.$Filter['SearchAllData'].'%") OR ('.$ModuleInfoData->TableAliasName.'.Description LIKE "%'.$Filter['SearchAllData'].'%"))';
                }
                if (array_key_exists('Category', $Filter)) {
                    if($SearchDirectQuery != '') {
                        $SearchDirectQuery .= ' AND ';    
                    }
                    $SearchDirectQuery .= $ModuleInfoData->TableAliasName.'.CategoryUID IN ('.implode($Filter['Category']).')';
                }
                if (array_key_exists('Storage', $Filter)) {
                    if($SearchDirectQuery != '') {
                        $SearchDirectQuery .= ' AND ';    
                    }
                    $SearchDirectQuery .= $ModuleInfoData->TableAliasName.'.StorageUID IN ('.implode($Filter['Storage']).')';
                }
            }

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->SearchDirectQuery = $SearchDirectQuery;
            $this->EndReturnData->SearchFilter = $SearchFilter;

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            $this->EndReturnData->SearchDirectQuery = '';
            $this->EndReturnData->SearchFilter = [];
        }

        return $this->EndReturnData;

    }

    public function getProductsDetails($FilterArray, $OrderBy = 'ASC', $whereInCondition = [])
    {

        $this->EndReturnData = new StdClass();
        try {

            $this->ProductDb->db_debug = FALSE;

            $select_ary = array(
                'Products.ProductUID AS ProductUID',
                'Products.OrgUID AS OrgUID',
                'Products.ItemName AS ItemName',
                'Category.Name as CategoryName',
                'Products.ProductType AS ProductType',
                'Products.SellingPrice AS SellingPrice',
                'Products.SellingProductTaxUID AS SellingProductTaxUID',
                'Products.TaxDetailsUID AS TaxDetailsUID',
                'Products.TaxPercentage AS TaxPercentage',
                'Products.CGST AS CGST',
                'Products.SGST AS SGST',
                'Products.IGST AS IGST',
                'Products.PrimaryUnitUID AS PrimaryUnitUID',
                'Products.CategoryUID AS CategoryUID',
                'Products.HSNSACCode AS HSNSACCode',
                'Products.PurchasePrice AS PurchasePrice',
                'Products.PurchasePriceProductTaxUID AS PurchasePriceProductTaxUID',
                'Products.StorageUID AS StorageUID',
                'Products.PartNumber AS PartNumber',
                'Products.Description AS Description',
                'Products.Image AS Image',
                'Products.OpeningQuantity AS OpeningQuantity',
                'Products.OpeningPurchasePrice AS OpeningPurchasePrice',
                'Products.OpeningStockValue AS OpeningStockValue',
                'Products.Discount AS Discount',
                'Products.DiscountTypeUID AS DiscountTypeUID',
                'Products.LowStockAlertAt AS LowStockAlertAt',
                'Products.NotForSale AS NotForSale',
                'Products.BrandUID AS BrandUID',
                'Products.Standard AS Standard',
                'Products.Model AS Model',
                'Products.IsSizeApplicable AS IsSizeApplicable',
                'Products.SizeUID AS SizeUID',
                'Products.CreatedOn as CreatedOn',
                'Products.UpdatedOn as UpdatedOn',
            );
            $WhereCondition = array(
                'Products.IsDeleted' => 0,
                'Products.IsActive' => 1,
            );
            $this->ProductDb->select($select_ary);
            $this->ProductDb->from('Products.ProductTbl as Products');
            $this->ProductDb->join('Products.CategoryTbl as Category', 'Category.CategoryUID = Products.CategoryUID', 'left');
            $this->ProductDb->where($WhereCondition);
            if (!empty($FilterArray)) {
                $this->ProductDb->where($FilterArray);
            }
            if (!empty($whereInCondition)) {
                foreach ($whereInCondition as $wkey => $wval) {
                    $this->ProductDb->where_in($wkey, $wval);
                }
            }
            $this->ProductDb->group_by('Products.ProductUID');
            $this->ProductDb->order_by('Products.ProductUID', $OrderBy);

            $query = $this->ProductDb->get();
            $error = $this->ProductDb->error();
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
            if(!empty($Filter)) {
                if (array_key_exists('SearchAllData', $Filter)) {
                    $SearchDirectQuery .= '(('. $ModuleInfoData->TableAliasName.'.Name LIKE "%'.$Filter['SearchAllData'].'%" ) OR ('.$ModuleInfoData->TableAliasName.'.Description LIKE "%'.$Filter['SearchAllData'].'%"))';
                }
            }

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->SearchDirectQuery = $SearchDirectQuery;
            $this->EndReturnData->SearchFilter = $SearchFilter;

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            $this->EndReturnData->SearchDirectQuery = '';
            $this->EndReturnData->SearchFilter = [];
        }

        return $this->EndReturnData;

    }

    public function getCategoriesDetails($FilterArray)
    {

        $this->EndReturnData = new StdClass();
        try {

            $this->ProductDb->db_debug = FALSE;
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
            $this->ProductDb->select($select_ary);
            $this->ProductDb->from('Products.CategoryTbl as Category');
            $this->ProductDb->where($WhereCondition);
            if (!empty($FilterArray)) {
                $this->ProductDb->where($FilterArray);
            }
            $this->ProductDb->group_by('Category.CategoryUID');
            $this->ProductDb->order_by('Category.CategoryUID', 'ASC');

            $query = $this->ProductDb->get();
            $error = $this->ProductDb->error();
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

    public function sizeFilterFormation($ModuleInfoData, $Filter) {

        $this->EndReturnData = new StdClass();
        try {

            $SearchDirectQuery = '';
            $SearchFilter = [];
            if(!empty($Filter)) {
                if (array_key_exists('SearchAllData', $Filter)) {
                    $SearchDirectQuery .= '(('. $ModuleInfoData->TableAliasName.'.Name LIKE "%'.$Filter['SearchAllData'].'%" ) OR ('.$ModuleInfoData->TableAliasName.'.Description LIKE "%'.$Filter['SearchAllData'].'%"))';
                }
            }

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->SearchDirectQuery = $SearchDirectQuery;
            $this->EndReturnData->SearchFilter = $SearchFilter;

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            $this->EndReturnData->SearchDirectQuery = '';
            $this->EndReturnData->SearchFilter = [];
        }

        return $this->EndReturnData;

    }

    public function getSizeDetails($Filter)
    {

        $this->EndReturnData = new StdClass();
        try {

            $this->ProductDb->db_debug = FALSE;

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
            $this->ProductDb->select($select_ary);
            $this->ProductDb->from('Products.SizeTbl as Size');
            $this->ProductDb->where($WhereCondition);
            if (!empty($Filter)) {
                $this->ProductDb->where($Filter);
            }
            $this->ProductDb->group_by('Size.SizeUID');

            $query = $this->ProductDb->get();
            $error = $this->ProductDb->error();
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
            if(!empty($Filter)) {
                if (array_key_exists('SearchAllData', $Filter)) {
                    $SearchDirectQuery .= '(('. $ModuleInfoData->TableAliasName.'.Name LIKE "%'.$Filter['SearchAllData'].'%" ) OR ('.$ModuleInfoData->TableAliasName.'.Description LIKE "%'.$Filter['SearchAllData'].'%"))';
                }
            }

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->SearchDirectQuery = $SearchDirectQuery;
            $this->EndReturnData->SearchFilter = $SearchFilter;

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            $this->EndReturnData->SearchDirectQuery = '';
            $this->EndReturnData->SearchFilter = [];
        }

        return $this->EndReturnData;

    }

    public function getBrandDetails($FilterArray)
    {

        $this->EndReturnData = new StdClass();
        try {

            $this->ProductDb->db_debug = FALSE;
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
            $this->ProductDb->select($select_ary);
            $this->ProductDb->from('Products.BrandTbl as Brand');
            $this->ProductDb->where($WhereCondition);
            if (!empty($FilterArray)) {
                $this->ProductDb->where($FilterArray);
            }
            $this->ProductDb->group_by('Brand.BrandUID');

            $query = $this->ProductDb->get();
            $error = $this->ProductDb->error();
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
}
