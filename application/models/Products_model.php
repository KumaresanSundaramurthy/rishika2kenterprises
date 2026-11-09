<?php defined('BASEPATH') or exit('No direct script access allowed');

class Products_model extends CI_Model {

    private $EndReturnData;
    private $ReadDb;

    function __construct() {
        parent::__construct();

        $this->ReadDb = $this->load->database('ReadDB', TRUE);

    }

    public function getProductsDetails(array $FilterArray = [], string $OrderBy = 'ASC', array $whereInCondition = [], string $langCode = 'en'): array {

        $this->EndReturnData = new StdClass();
        try {

            $useLang = $langCode !== 'en';
            $lc      = $this->ReadDb->escape_str($langCode);
            $this->ReadDb->db_debug = FALSE;

            $select_ary = array(
                'Products.ProductUID AS ProductUID',
                'Products.OrgUID AS OrgUID',
                $useLang ? 'COALESCE(PL.ItemName, Products.ItemName) AS ItemName' : 'Products.ItemName AS ItemName',
                $useLang ? 'COALESCE(CL.Name, Category.Name) AS CategoryName' : 'Category.Name as CategoryName',
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
                'CASE WHEN (Products.IsSizeApplicable = 1 OR Products.IsBrandApplicable = 1) THEN COALESCE((SELECT SUM(pvs2.AvailableQty) FROM Products.ProductVariantStockTbl pvs2 JOIN Products.ProductVariantTbl pv2 ON pv2.VariantUID = pvs2.VariantUID AND pv2.OrgUID = pvs2.OrgUID WHERE pv2.ProductUID = Products.ProductUID AND pvs2.OrgUID = Products.OrgUID AND pv2.IsActive = 1), 0) ELSE COALESCE(ProductStock.AvailableQty, 0) END AS AvailableQuantity',
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
            if ($useLang) {
                $this->ReadDb->join("Products.ProductTbl_Lang AS PL", "PL.ProductUID = Products.ProductUID AND PL.LangCode = '{$lc}'", 'left');
                $this->ReadDb->join("Products.CategoryTbl_Lang AS CL", "CL.CategoryUID = Category.CategoryUID AND CL.LangCode = '{$lc}'", 'left');
            }
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

}