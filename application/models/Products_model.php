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

    public function getProductsList($limit, $offset, $Filter, $Flag = 0)
    {

        $this->EndReturnData = new StdClass();
        try {

            $this->ProductDb->db_debug = FALSE;
            if ($Flag == 0) {
                $select_ary = array(
                    'Products.ProductUID AS ProductUID',
                    'Products.OrgUID AS OrgUID',
                    'Products.ItemName AS ItemName',
                    'Category.Name as CategoryName',
                    'Products.ProductType AS ProductType',
                    'Products.SellingPrice AS SellingPrice',
                    'Products.PurchasePrice AS PurchasePrice',
                    'Products.CreatedOn as CreatedOn',
                    'Products.UpdatedOn as UpdatedOn',
                );
            } else {
                $select_ary = array(
                    'Products.ProductUID AS ProductUID',
                );
            }
            $WhereCondition = array(
                'Products.IsDeleted' => 0,
                'Products.IsActive' => 1,
            );
            $this->ProductDb->select($select_ary);
            $this->ProductDb->from('Products.ProductTbl as Products');
            $this->ProductDb->join('Products.CategoryTbl as Category', 'Category.CategoryUID = Products.CategoryUID', 'left');
            $this->ProductDb->where($WhereCondition);
            if (!empty($Filter)) {
                if (array_key_exists('Name', $Filter)) {
                    $this->ProductDb->like("Products.ItemName", $Filter['Name'], 'Both');
                }
            }
            $this->ProductDb->group_by('Products.ProductUID');
            if ($Flag == 0) {
                $this->ProductDb->order_by('Products.ProductUID', 'DESC');
                $this->ProductDb->limit($limit, $offset);
            }

            $query = $this->ProductDb->get();
            $error = $this->ProductDb->error();
            if ($error['code']) {
                throw new Exception($error['message']);
            } else {
                if ($Flag == 0) {
                    $this->EndReturnData->Data = $query->result();
                } else {
                    $this->EndReturnData->Data = $query->num_rows();
                }
            }
            return $this->EndReturnData->Data;
        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);
        }
    }

    public function getProductsDetails($FilterArray)
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
                if (array_key_exists('Name', $FilterArray)) {
                    $this->ProductDb->like("Products.ItemName", $FilterArray['Name'], 'Both');
                }
            }
            $this->ProductDb->group_by('Products.ProductUID');

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

    public function getCategoriesList($limit, $offset, $Filter, $Flag = 0)
    {

        $this->EndReturnData = new StdClass();
        try {

            $this->ProductDb->db_debug = FALSE;
            if ($Flag == 0) {
                $select_ary = array(
                    'Category.CategoryUID AS CategoryUID',
                    'Category.OrgUID AS OrgUID',
                    'Category.Name AS Name',
                    'Category.Description AS Description',
                    'Category.CreatedOn as CreatedOn',
                    'Category.UpdatedOn as UpdatedOn',
                );
            } else {
                $select_ary = array(
                    'Category.CategoryUID AS CategoryUID',
                );
            }
            $WhereCondition = array(
                'Category.IsDeleted' => 0,
                'Category.IsActive' => 1,
            );
            $this->ProductDb->select($select_ary);
            $this->ProductDb->from('Products.CategoryTbl as Category');
            $this->ProductDb->where($WhereCondition);
            if (!empty($Filter)) {
                if (array_key_exists('Name', $Filter)) {
                    $this->ProductDb->like("Category.Name", $Filter['Name'], 'Both');
                }
            }
            $this->ProductDb->group_by('Category.CategoryUID');
            if ($Flag == 0) {
                $this->ProductDb->order_by('Category.CategoryUID', 'DESC');
                $this->ProductDb->limit($limit, $offset);
            }

            $query = $this->ProductDb->get();
            $error = $this->ProductDb->error();
            if ($error['code']) {
                throw new Exception($error['message']);
            } else {
                if ($Flag == 0) {
                    $this->EndReturnData->Data = $query->result();
                } else {
                    $this->EndReturnData->Data = $query->num_rows();
                }
            }
            return $this->EndReturnData->Data;
        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);
        }
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
