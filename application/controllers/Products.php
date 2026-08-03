<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Products extends MY_Controller {

    public $pageData = array();
    protected $EndReturnData;

    public function __construct() {
        parent::__construct();

        $this->load->model(['products_model']);

    }

    private function sanitizeTabInput($tab): string {

        $tab = strtolower($tab ?: 'item');
        $map = ['item' => 'item', 'group' => 'group', 'category' => 'category', 'pricelist' => 'pricelist', 'brand' => 'brand', 'items' => 'item', 'groups' => 'group', 'categories' => 'category', 'pricelists' => 'pricelist', 'brands' => 'brand'];
        return $map[$tab] ?? 'item';

    }

    private function fetchProductStats() {
        $OrgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
        return $this->products_model->getProductStats($OrgUID);
    }

    private function fetchProductTableData($pageNo, $limit = 0, $isComposite = null) {

        $OrgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
        if (!$limit) {
            $postLimit     = (int) $this->input->post('RowLimit');
            $settingsLimit = (int) (($this->pageData['JwtData']->GenSettings ?? null)?->RowLimit ?? 0);
            $limit = $postLimit ?: ($settingsLimit ?: 10);
        }
        $pageNo = (int) $pageNo;
        if ($pageNo < 1) { $pageNo = 1; }
        $offset = ($pageNo - 1) * $limit;

        // Determine which tab type to refresh (0 = items, 1 = groups)
        if ($isComposite === null) {
            $isComposite = (int) $this->input->post('IsComposite');
        }
        $isComposite = (int) $isComposite;

        $rawFilter = $this->input->post('Filter');
        $filter    = is_array($rawFilter) ? $rawFilter : (json_decode($rawFilter ?: '{}', true) ?? []);
        $filterResult = $this->products_model->itemFilterFormation((object)['TableAliasName' => 'Products'], $filter);

        $baseQuery   = 'Products.IsComposite = ' . $isComposite;
        $searchQuery = $filterResult->SearchDirectQuery
            ? $baseQuery . ' AND (' . $filterResult->SearchDirectQuery . ')'
            : $baseQuery;

        $result = $this->products_model->getProductListPaginated($OrgUID, $limit, $offset, $searchQuery, $filterResult->sortOperation);


        $rowHtml = $this->load->view('products/items/list', [
            'DataLists' => $result->rows,
            'StartFrom' => $offset,
            'JwtData'   => $this->pageData['JwtData'],
            'HideType'  => (bool) $isComposite,
            'ShowUnit'  => (bool) $isComposite,
        ], TRUE);

        $paginationUrl = $isComposite ? '/products/getGroupList' : '/products/getProductList';

        $resp                 = new stdClass();
        $resp->RecordHtmlData = $rowHtml;
        $resp->List           = $rowHtml;
        $resp->Pagination     = $this->globalservice->buildPagePaginationHtml($paginationUrl, $result->totalCount, $pageNo, $limit);
        $resp->TotalCount     = $result->totalCount;
        return $resp;

    }

    private function renderViewHtml($viewFile, $data = []) {
        if (!file_exists($viewFile)) return '';
        extract($data);
        ob_start();
        include($viewFile);
        $html = ob_get_clean();
        return ($html !== false && $html !== '') ? $html : '';
    }

    private function fetchCategoryTableData($pageNo, $limit = 0) {

        $OrgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
        if (!$limit) {
            $postLimit     = (int) $this->input->post('RowLimit');
            $settingsLimit = (int) (($this->pageData['JwtData']->GenSettings ?? null)?->RowLimit ?? 0);
            $limit = $postLimit ?: ($settingsLimit ?: 10);
        }
        $pageNo = (int) $pageNo;
        if ($pageNo < 1) {
            $pageNo = 1;
        }
        $offset = ($pageNo - 1) * $limit;


        $result  = $this->products_model->getCategoryListPaginated($OrgUID, $limit, $offset);
        $rowHtml = $this->load->view('products/categories/list', [
            'DataLists' => $result->rows,
            'StartFrom' => $offset,
            'JwtData'   => $this->pageData['JwtData'],
        ], TRUE);

        $resp                 = new stdClass();
        $resp->RecordHtmlData = $rowHtml;
        $resp->Pagination     = $this->globalservice->buildPagePaginationHtml('/products/getCategoryList', $result->totalCount, $pageNo, $limit);
        return $resp;

    }

    private function fetchBrandTableData(int $pageNo, int $limit = 0): object {

        $OrgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
        if (!$limit) {
            $postLimit     = (int) $this->input->post('RowLimit');
            $settingsLimit = (int) (($this->pageData['JwtData']->GenSettings ?? null)?->RowLimit ?? 0);
            $limit = $postLimit ?: ($settingsLimit ?: 10);
        }
        $pageNo = max(1, $pageNo);
        $offset = ($pageNo - 1) * $limit;

        $result  = $this->products_model->getBrandListPaginated($OrgUID, $limit, $offset);
        $rowHtml = $this->load->view('products/brands/list', [
            'DataLists' => $result->rows,
            'StartFrom' => $offset,
            'JwtData'   => $this->pageData['JwtData'],
        ], TRUE);

        $resp                 = new stdClass();
        $resp->RecordHtmlData = $rowHtml;
        $resp->Pagination     = $this->globalservice->buildPagePaginationHtml('/products/getBrandList', $result->totalCount, $pageNo, $limit);
        $resp->TotalCount     = $result->totalCount;
        return $resp;

    }

    public function index() {

        if (!$this->_loadPageTitle()) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }

        try {

            $activeTab = $this->sanitizeTabInput($this->input->get('tab', TRUE));
            $initSearch = $this->input->get('search', TRUE) ?: '';

            $limit = (int) ($GeneralSettings->RowLimit ?? 10);

            // Use dedicated paginated functions for active-tab row data
            $OrgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            $this->pageData['ProductTotalCount'] = 0;
            $this->pageData['ModTotalCount']     = 0;
            $initFilter = $initSearch !== '' ? ['SearchAllData' => $initSearch] : [];
            if ($activeTab === 'item') {
                $fr        = $this->products_model->itemFilterFormation((object)['TableAliasName' => 'Products'], $initFilter);
                $baseQuery = 'Products.IsComposite = 0';
                $sqry      = $fr->SearchDirectQuery ? $baseQuery . ' AND (' . $fr->SearchDirectQuery . ')' : $baseQuery;
                $tableData = $this->products_model->getProductListPaginated($OrgUID, $limit, 0, $sqry);
                $this->pageData['ModRowData'] = $this->load->view('products/items/list', [
                    'DataLists' => $tableData->rows,
                    'StartFrom' => 0,
                    'JwtData'   => $this->pageData['JwtData'],
                ], TRUE);
                $this->pageData['ModPagination']     = $this->globalservice->buildPagePaginationHtml('/products/getProductList', $tableData->totalCount, 1, $limit);
                $this->pageData['ProductTotalCount'] = $tableData->totalCount;
                $this->pageData['ModTotalCount']     = $tableData->totalCount;
            } elseif ($activeTab === 'group') {
                $fr        = $this->products_model->itemFilterFormation((object)['TableAliasName' => 'Products'], $initFilter);
                $baseQuery = 'Products.IsComposite = 1';
                $sqry      = $fr->SearchDirectQuery ? $baseQuery . ' AND (' . $fr->SearchDirectQuery . ')' : $baseQuery;
                $tableData = $this->products_model->getProductListPaginated($OrgUID, $limit, 0, $sqry);
                $this->pageData['ModRowData'] = $this->load->view('products/items/list', [
                    'DataLists' => $tableData->rows,
                    'StartFrom' => 0,
                    'JwtData'   => $this->pageData['JwtData'],
                    'HideType'  => true,
                    'ShowUnit'  => true,
                ], TRUE);
                $this->pageData['ModPagination'] = $this->globalservice->buildPagePaginationHtml('/products/getGroupList', $tableData->totalCount, 1, $limit);
                $this->pageData['ModTotalCount'] = $tableData->totalCount;
            } elseif ($activeTab === 'pricelist') {
                $this->load->model('pricelists_model');
                $tableData = $this->pricelists_model->getPriceListPaginated($OrgUID, $limit, 0, $initFilter);
                $this->pageData['ModRowData'] = $this->load->view('products/pricelists/list', [
                    'DataLists' => $tableData->rows,
                    'StartFrom' => 0,
                    'JwtData'   => $this->pageData['JwtData'],
                ], TRUE);
                $this->pageData['ModPagination'] = $this->globalservice->buildPagePaginationHtml('/products/getPriceListData', $tableData->totalCount, 1, $limit);
                $this->pageData['ModTotalCount'] = $tableData->totalCount;
            } elseif ($activeTab === 'category') {
                $fr        = $this->products_model->catgFilterFormation((object)['TableAliasName' => 'Category'], $initFilter);
                $tableData = $this->products_model->getCategoryListPaginated($OrgUID, $limit, 0, $fr->SearchDirectQuery);
                $this->pageData['ModRowData'] = $this->load->view('products/categories/list', [
                    'DataLists' => $tableData->rows,
                    'StartFrom' => 0,
                    'JwtData'   => $this->pageData['JwtData'],
                ], TRUE);
                $this->pageData['ModPagination'] = $this->globalservice->buildPagePaginationHtml('/products/getCategoryList', $tableData->totalCount, 1, $limit);
                $this->pageData['ModTotalCount'] = $tableData->totalCount;
            } elseif ($activeTab === 'brand') {
                $fr        = $this->products_model->brandFilterFormation((object)['TableAliasName' => 'Brand'], $initFilter);
                $tableData = $this->products_model->getBrandListPaginated($OrgUID, $limit, 0, $fr->SearchDirectQuery);
                $this->pageData['ModRowData'] = $this->load->view('products/brands/list', [
                    'DataLists' => $tableData->rows,
                    'StartFrom' => 0,
                    'JwtData'   => $this->pageData['JwtData'],
                ], TRUE);
                $this->pageData['ModPagination'] = $this->globalservice->buildPagePaginationHtml('/products/getBrandList', $tableData->totalCount, 1, $limit);
                $this->pageData['ModTotalCount'] = $tableData->totalCount;
            } else {
                $this->pageData['ModRowData']    = '';
                $this->pageData['ModPagination'] = '';
            }

            $OrgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            
            $this->pageData['ActiveTabData']  = $activeTab;
            // Must match the data-id attribute values on the tab nav links in view.php
            $tabNameMap = ['item' => 'Item', 'group' => 'Groups', 'pricelist' => 'PriceLists', 'category' => 'Categories', 'brand' => 'Brands'];
            $this->pageData['ActiveTabName']  = $tabNameMap[$activeTab] ?? ucfirst($activeTab);
            $this->pageData['InitSearch']     = $initSearch;
            $this->pageData['ActiveModuleId'] = 4;

            $this->pageData['ProductStats'] = $this->products_model->getProductStats($OrgUID);

            $this->load->model('users_model');
            $this->pageData['OrgUsers'] = $this->users_model->getOrgUsersForCache($OrgUID);

            $this->load->view('products/view', $this->pageData);

        } catch (Exception $e) {
            redirect('dashboard', 'refresh');
        }

    }

    private function buildProductFormData($postData, $TaxDetails = null, $isCreate = false) {

        $Selling_Price = (float) getPostValue($postData, 'SellingPrice', '', 0);
        $Tax_Percentage = isset($TaxDetails->Percentage) ? (float) $TaxDetails->Percentage : 0;
        $TaxOption = (int) getPostValue($postData, 'SellingTaxOption');
        
        $Unit_Price = $Selling_Price;
        if ($TaxOption == 1) {
            if ($Tax_Percentage > 0) {
                $Unit_Price = $Selling_Price / (1 + ($Tax_Percentage / 100));
            }
        }

        $data = [
            'OrgUID'                     => (int) $this->pageData['JwtData']->Org->OrgUID,
            'ItemName'                   => getPostValue($postData, 'ItemName'),
            'ProductType'                => in_array(getPostValue($postData, 'ProductType', '', 'Product'), ['Product', 'Service']) ? getPostValue($postData, 'ProductType', '', 'Product') : 'Product',
            'MRP'                        => (float) getPostValue($postData, 'MRP', '', 0),
            'UnitPrice'                  => round($Unit_Price, 5),
            'SellingPrice'               => $Selling_Price,
            'SellingProductTaxUID'       => (int) getPostValue($postData, 'SellingTaxOption'),
            'TaxDetailsUID'              => (int) getPostValue($postData, 'TaxPercentage'),
            'TaxPercentage'              => isset($TaxDetails->Percentage) ? $TaxDetails->Percentage : null,
            'CGST'                       => isset($TaxDetails->CGST) ? $TaxDetails->CGST : null,
            'SGST'                       => isset($TaxDetails->SGST) ? $TaxDetails->SGST : null,
            'IGST'                       => isset($TaxDetails->IGST) ? $TaxDetails->IGST : null,
            'PrimaryUnitUID'             => (int) getPostValue($postData, 'PrimaryUnit'),
            'CategoryUID'                => (int) getPostValue($postData, 'Category'),
            'HSNSACCode'                 => getPostValue($postData, 'HSNCode'),
            'PurchasePrice'              => (float) getPostValue($postData, 'PurchasePrice', '', 0),
            'PurchasePriceProductTaxUID' => (int) getPostValue($postData, 'PurchaseTaxOption'),
            'PartNumber'                 => getPostValue($postData, 'PartNumber'),
            'SKU'                        => isset($TaxDetails->SKU) ? $TaxDetails->SKU : null,
            'Description'                => getPostValue($postData, 'Description'),
            'OpeningQuantity'            => ($postData['ProductType'] ?? 'Product') === 'Product' ? (float) getPostValue($postData, 'OpeningQuantity', '', 0) : 0,
            'OpeningPurchasePrice'       => ($postData['ProductType'] ?? 'Product') === 'Product' ? (float) getPostValue($postData, 'OpeningPurchasePrice', '', 0) : 0,
            'OpeningStockValue'          => ($postData['ProductType'] ?? 'Product') === 'Product' ? (float) getPostValue($postData, 'OpeningStockValue', '', 0) : 0,
            'Discount'                   => (float) getPostValue($postData, 'Discount', '', 0),
            'DiscountTypeUID'            => (int) getPostValue($postData, 'DiscountOption', '', 0),
            'LowStockAlertAt'            => (int) getPostValue($postData, 'LowStockAlert', '', 0),
            'NotForSale'                 => (!empty($postData['NotForSale']) && $postData['NotForSale'] == 1) ? 'Yes' : 'No',
            'IsRentable'                 => (!empty($postData['IsRentable'])  && $postData['IsRentable']  == 1) ? 1 : 0,
            'IsSizeApplicable'           => (!empty($postData['IsSizeApplicable']) && $postData['IsSizeApplicable'] == 1) ? 1 : 0,
            'IsComboItem'                => 0,
            'IsComposite'                => 0,
            'IsBrandApplicable'          => (!empty($postData['IsBrandApplicable']) && $postData['IsBrandApplicable'] == 1) ? 1 : 0,
            'IsSerialTracked'            => (!empty($postData['IsSerialTracked']) && $postData['IsSerialTracked'] == 1) ? 1 : 0,
            'UpdatedBy'                  => (int) $this->pageData['JwtData']->User->UserUID,
        ];
        if ($this->pageData['JwtData']->GenSettings->EnableStorage == 1) {
            $data['StorageUID'] = getPostValue($postData, 'StorageUID');
        }
        if ($isCreate) {
            $data['ProdToken'] = generate_uuid4();
            $data['CreatedBy'] = $this->pageData['JwtData']->User->UserUID;
        }

        return $data;
        
    }


    private function saveProductBOM($ParentProductUID, $PostData) {

        $userUID = (int) $this->pageData['JwtData']->User->UserUID;

        // Soft-delete existing BOM rows before re-inserting
        $this->dbwrite_model->updateData('Products', 'ProductBOMTbl', ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID], ['ParentProductUID' => (int) $ParentProductUID]);

        $componentsJson = getPostValue($PostData, 'ComboComponentsData', '', '[]');
        $components = json_decode($componentsJson, true);

        if (!empty($components) && is_array($components)) {
            foreach ($components as $comp) {
                $childUID = (int) ($comp['ItemUID'] ?? 0);
                $qty      = (float) ($comp['Qty'] ?? 1);
                if ($childUID > 0 && $qty > 0 && $childUID !== (int) $ParentProductUID) {
                    $this->dbwrite_model->insertData('Products', 'ProductBOMTbl', [
                        'OrgUID'           => (int) $this->pageData['JwtData']->Org->OrgUID,
                        'ParentProductUID' => (int) $ParentProductUID,
                        'ChildProductUID'  => $childUID,
                        'Quantity'         => $qty,
                        'CreatedBy'        => $userUID,
                        'UpdatedBy'        => $userUID,
                    ]);
                }
            }
        }

    }

    private function _saveRentalConfig($ProductUID, $PostData) {

        $isRentable = (!empty($PostData['IsRentable']) && $PostData['IsRentable'] == 1) ? 1 : 0;
        if (!$isRentable) return;

        $userUID = (int) $this->pageData['JwtData']->User->UserUID;
        $orgUID  = (int) $this->pageData['JwtData']->Org->OrgUID;

        $configData = [
            'SecurityDeposit'         => (float) getPostValue($PostData, 'rc_SecurityDeposit',   '', 0),
            'HourlyRate'              => (float) getPostValue($PostData, 'rc_HourlyRate',         '', 0),
            'HalfDayRate'             => (float) getPostValue($PostData, 'rc_HalfDayRate',        '', 0),
            'FullDayRate'             => (float) getPostValue($PostData, 'rc_FullDayRate',        '', 0),
            'FixedPackageRate'        => (float) getPostValue($PostData, 'rc_FixedPackageRate',   '', 0),
            'ExtraHourRate'           => (float) getPostValue($PostData, 'rc_ExtraHourRate',      '', 0),
            'LateReturnChargePerHour' => (float) getPostValue($PostData, 'rc_LateReturnCharge',  '', 0),
            'DamagePenaltyRate'       => (float) getPostValue($PostData, 'rc_DamagePenaltyRate', '', 0),
            'MinRentalHours'          => max(1, (int) getPostValue($PostData, 'rc_MinRentalHours', '', 1)),
            'UpdatedBy'               => $userUID,
        ];

        $existing = $this->products_model->getRentalConfig($ProductUID, $orgUID);
        if ($existing) {
            $this->dbwrite_model->updateData('Products', 'ProductRentalConfigTbl', $configData, [
                'ProductUID' => (int) $ProductUID,
                'OrgUID'     => $orgUID,
                'IsDeleted'  => 0,
            ]);
        } else {
            $configData['ProductUID'] = (int) $ProductUID;
            $configData['OrgUID']     = $orgUID;
            $configData['IsActive']   = 1;
            $configData['IsDeleted']  = 0;
            $configData['CreatedBy']  = $userUID;
            $this->dbwrite_model->insertData('Products', 'ProductRentalConfigTbl', $configData);
        }

    }

    public function getRentalConfig() {

        $this->EndReturnData = new stdClass();
        try {
            $ProductUID = (int) $this->input->post('ProductUID');
            $orgUID     = (int) $this->pageData['JwtData']->Org->OrgUID;
            if (!$ProductUID) throw new Exception('Invalid product');
            $config = $this->products_model->getRentalConfig($ProductUID, $orgUID);
            $this->EndReturnData->Error  = false;
            $this->EndReturnData->Config = $config ?: null;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    /** Product List (AJAX pagination) — dedicated query */
    public function getProductList() {

        $this->EndReturnData = new stdClass();
        try {

            $OrgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            $pageNo = (int) $this->input->post('PageNo') ?: 1;
            $limit  = (int) $this->input->post('RowLimit') ?: 10;
            $offset = ($pageNo - 1) * $limit;
            $rawFilter = $this->input->post('Filter');
            $filter    = is_array($rawFilter) ? $rawFilter : (json_decode($rawFilter ?: '{}', true) ?? []);

            $filterResult = $this->products_model->itemFilterFormation((object)['TableAliasName' => 'Products'], $filter);

            $baseQuery   = 'Products.IsComposite = 0';
            $searchQuery = $filterResult->SearchDirectQuery
                ? $baseQuery . ' AND (' . $filterResult->SearchDirectQuery . ')'
                : $baseQuery;
            $result  = $this->products_model->getProductListPaginated($OrgUID, $limit, $offset, $searchQuery, $filterResult->sortOperation);


            $rowHtml = $this->load->view('products/items/list', [
                'DataLists' => $result->rows,
                'StartFrom' => $offset,
                'JwtData'   => $this->pageData['JwtData'],
            ], TRUE);

            $this->EndReturnData->Error       = false;
            $this->EndReturnData->List        = $rowHtml;
            $this->EndReturnData->Pagination  = $this->globalservice->buildPagePaginationHtml('/products/getProductList', $result->totalCount, $pageNo, $limit);
            $this->EndReturnData->UIDs        = array_column($result->rows, 'ProductUID');
            $this->EndReturnData->TotalCount  = $result->totalCount;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function getGroupList() {

        $this->EndReturnData = new stdClass();
        try {

            $OrgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            $pageNo = (int) $this->input->post('PageNo') ?: 1;
            $limit  = (int) $this->input->post('RowLimit') ?: 10;
            $offset = ($pageNo - 1) * $limit;
            $rawFilter = $this->input->post('Filter');
            $filter    = is_array($rawFilter) ? $rawFilter : (json_decode($rawFilter ?: '{}', true) ?? []);

            $filterResult = $this->products_model->itemFilterFormation((object)['TableAliasName' => 'Products'], $filter);

            $baseQuery   = 'Products.IsComposite = 1';
            $searchQuery = $filterResult->SearchDirectQuery
                ? $baseQuery . ' AND (' . $filterResult->SearchDirectQuery . ')'
                : $baseQuery;
            $result = $this->products_model->getProductListPaginated($OrgUID, $limit, $offset, $searchQuery, $filterResult->sortOperation);


            $rowHtml = $this->load->view('products/items/list', [
                'DataLists' => $result->rows,
                'StartFrom' => $offset,
                'JwtData'   => $this->pageData['JwtData'],
                'HideType'  => true,
                'ShowUnit'  => true,
            ], TRUE);

            $this->EndReturnData->Error      = false;
            $this->EndReturnData->List       = $rowHtml;
            $this->EndReturnData->Pagination = $this->globalservice->buildPagePaginationHtml('/products/getGroupList', $result->totalCount, $pageNo, $limit);
            $this->EndReturnData->UIDs       = array_column($result->rows, 'ProductUID');
            $this->EndReturnData->TotalCount = $result->totalCount;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function addProductData() {

        $this->EndReturnData = new stdClass();
        $ErrorInForm = '';
		try {

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();
            
            $GeneralSettings = $this->pageData['JwtData']->GenSettings ?? new stdClass();

            $PostData = $this->input->post();

            $this->load->model('formvalidation_model');
            $ErrorInForm = $this->formvalidation_model->productValidateForm($PostData);
            if(!empty($ErrorInForm)) {
                throw new InvalidArgumentException('VALIDATION_ERROR');
            }

            $this->load->model('global_model');
            $TaxDetails = $this->global_model->getTaxPercentageDetailsInfo(['TaxDetail.TaxDetailsUID' => $PostData['TaxPercentage']])->Data[0] ?? null;

            $prodFormData = $this->buildProductFormData($PostData, $TaxDetails, true);

            $InsertDataResp = $this->dbwrite_model->insertData('Products', 'ProductTbl', $prodFormData);
            if($InsertDataResp->Error) {
                throw new Exception($InsertDataResp->Message);
            }

            $ProductUID = $InsertDataResp->ID;

            // Rental configuration (only when IsRentable = 1)
            $this->_saveRentalConfig($ProductUID, $PostData);

            $this->dbwrite_model->commitTransaction();

            // Create initial stock row in ProductStockTbl — seed with OpeningQuantity
            $openingQty = (float)($prodFormData['OpeningQuantity'] ?? 0);
            $this->dbwrite_model->initProductStock($ProductUID, (int) $this->pageData['JwtData']->Org->OrgUID, $openingQty);

            // Sync new product into the Upstash bulk cache
            $this->cachehelper->upsertProduct($ProductUID);

            // Handle attachment uploads + deletes (part of this request, after commit)
            $this->_handleAttachments('Product', $ProductUID, (int)$this->pageData['JwtData']->Org->OrgUID, (int)$this->pageData['JwtData']->User->UserUID, 'ProdAttachFiles', 'ProdAttachDeleteUIDs');

            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Message    = 'Created Successfully';
            $this->auditlog->log(
                (int) $this->pageData['JwtData']->Org->OrgUID,
                (int) $this->pageData['JwtData']->User->UserUID,
                'CREATE_PRODUCT', 'Product', (int) $ProductUID, (string) getPostValue($PostData, 'ItemName'),
                [], "Created product " . getPostValue($PostData, 'ItemName'), 'Products',
                'MASTER', 'SUCCESS', '', 'WEB',
                [],
                $prodFormData
            );
            $this->EndReturnData->ProductUID = $ProductUID;

            // Build product data object from POST + TaxDetails — no extra DB query needed
            $sellingPrice  = (float) getPostValue($PostData, 'SellingPrice', '', 0);
            $taxPercent    = (float) ($TaxDetails->Percentage ?? 0);
            $taxOptSell    = (int) getPostValue($PostData, 'SellingTaxOption');
            // SellingTaxOption=1 → entered price is tax-inclusive; back-calc ex-tax unit price
            $unitPriceResp = ($taxOptSell === 1 && $taxPercent > 0)
                ? $sellingPrice / (1 + $taxPercent / 100)
                : $sellingPrice;
            $taxAmount     = round($unitPriceResp * $taxPercent / 100, $this->_decimals());
            $this->EndReturnData->Product = [
                'id'               => $ProductUID,
                'text'             => getPostValue($PostData, 'ItemName'),
                'itemName'         => getPostValue($PostData, 'ItemName'),
                'productType'      => getPostValue($PostData, 'ProductType', '', 'Product'),
                'unitPrice'        => $unitPriceResp,
                'taxAmount'        => $taxAmount,
                'sellingPrice'     => $sellingPrice,
                'purchasePrice'    => (float) getPostValue($PostData, 'PurchasePrice', '', 0),
                'availableQuantity'=> (float) getPostValue($PostData, 'OpeningQuantity', '', 0),
                'hsnCode'          => getPostValue($PostData, 'HSNCode'),
                'partNumber'       => getPostValue($PostData, 'PartNumber'),
                'taxPercent'       => $taxPercent,
                'taxDetailsUID'    => (int) getPostValue($PostData, 'TaxPercentage', '', 0),
                'cgstPercent'      => (float) ($TaxDetails->CGST ?? 0),
                'sgstPercent'      => (float) ($TaxDetails->SGST ?? 0),
                'igstPercent'      => (float) ($TaxDetails->IGST ?? 0),
                'discount'         => (float) getPostValue($PostData, 'Discount', '', 0),
                'discountTypeUID'  => (int) getPostValue($PostData, 'DiscountOption', '', 0),
                'primaryUnit'      => '',
                'isComboItem'      => 0,
                'comboItemCount'   => 0,
            ];


        } catch (InvalidArgumentException $e) {
            $this->dbwrite_model->rollbackTransaction();
            if ($e->getMessage() === 'VALIDATION_ERROR') {
                $this->EndReturnData->Error = true;
                $this->EndReturnData->Message = strip_tags($ErrorInForm);
                $this->EndReturnData->Errors = 'Please correct the highlighted errors.';
            } else {
                throw $e;
            }
        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		$this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function retrieveProductDetails() {

        $this->EndReturnData = new stdClass();
		try {

            $ProductUID = (int) $this->input->post('ItemUID');
            if (!$ProductUID || $ProductUID <= 0) {
                throw new Exception('Invalid Product Information');
            }

            // Cache-Aside READ — check Upstash before hitting the database
            $cacheKey = Upstashservice::keyProduct($ProductUID);
            $cached   = $this->upstashservice->get($cacheKey);

            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            // Always fetch attachments fresh — not cached (they change independently)
            $attachments = $this->_getAttachmentsWithUrl('Product', $ProductUID, $orgUID);

            if ($cached !== null) {
                $this->EndReturnData->Error        = FALSE;
                $this->EndReturnData->Message      = 'Retrieved Successfully';
                $this->EndReturnData->Data         = (object)$cached['Data'];
                $this->EndReturnData->RentalConfig = isset($cached['RentalConfig']) ? (object)$cached['RentalConfig'] : null;
                $this->EndReturnData->Attachments  = $attachments;
            } else {
                $this->load->model('products_model');
                $GetProductData = $this->products_model->getProductsDetails(['Products.ProductUID' => $ProductUID]);
                if (count($GetProductData) != 1) {
                    throw new Exception('Product not found');
                }
                $rentalConfig = $this->products_model->getRentalConfig($ProductUID, $orgUID);

                $this->EndReturnData->Error        = FALSE;
                $this->EndReturnData->Message      = 'Retrieved Successfully';
                $this->EndReturnData->Data         = $GetProductData[0];
                $this->EndReturnData->RentalConfig = $rentalConfig;
                $this->EndReturnData->Attachments  = $attachments;

                // Populate cache for next request
                $this->upstashservice->set($cacheKey, [
                    'Data'         => $GetProductData[0],
                    'RentalConfig' => $rentalConfig,
                ], Upstashservice::TTL_PRODUCT);
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function updateProductData() {

        $this->EndReturnData = new stdClass();
        $ErrorInForm = '';
		try {

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();
            
            $GeneralSettings = $this->pageData['JwtData']->GenSettings ?? new stdClass();

            $PostData = $this->input->post();

            $this->load->model('formvalidation_model');
            $ErrorInForm = $this->formvalidation_model->productValidateForm($PostData);
            if(!empty($ErrorInForm)) {
                throw new InvalidArgumentException('VALIDATION_ERROR');
            }

            $this->load->model('global_model');
            $TaxDetails = $this->global_model->getTaxPercentageDetailsInfo(['TaxDetail.TaxDetailsUID' => $PostData['TaxPercentage']])->Data[0] ?? null;

            $ProductUID = (int) getPostValue($PostData, 'ProductUID');
            $orgUID     = (int) $this->pageData['JwtData']->Org->OrgUID;

            // Block marking as Not for Sale if the item is a combo component
            $newNotForSale = (!empty($PostData['NotForSale']) && $PostData['NotForSale'] == 1) ? 1 : 0;
            if ($newNotForSale === 1) {
                $this->load->model('products_model');
                if ($this->products_model->isProductLinkedToCombo($ProductUID)) {
                    throw new InvalidArgumentException('This item is already linked with a combo. You cannot mark it as Not for Sale.');
                }
            }

            // Read current OpeningQuantity BEFORE update to compute delta later
            $readDb = $this->load->database('ReadDB', TRUE);
            $readDb->db_debug = FALSE;
            $readDb->select('OpeningQuantity, ProductType');
            $readDb->from('Products.ProductTbl');
            $readDb->where('ProductUID', $ProductUID);
            $currentProd    = $readDb->get()->row();
            $oldOpeningQty  = (float)($currentProd->OpeningQuantity ?? 0);
            $isPhysicalItem = ($currentProd->ProductType ?? 'Product') === 'Product';

            $prodFormData = $this->buildProductFormData($PostData, $TaxDetails, false);

            $UpdateDataResp = $this->dbwrite_model->updateData('Products', 'ProductTbl', $prodFormData, array('ProductUID' => $ProductUID));
            if($UpdateDataResp->Error) {
                throw new Exception($UpdateDataResp->Message);
            }

            // Apply opening qty delta to ProductStockTbl inside the same transaction
            if ($isPhysicalItem) {
                $newOpeningQty = (float)($prodFormData['OpeningQuantity'] ?? 0);
                $delta         = round($newOpeningQty - $oldOpeningQty, 4);
                if ($delta != 0.0) {
                    $this->dbwrite_model->applyOpeningQtyDelta($ProductUID, $orgUID, $delta);
                }
            }

            // Rental configuration (only when IsRentable = 1)
            $this->_saveRentalConfig($ProductUID, $PostData);

            $this->dbwrite_model->commitTransaction();

            // Sync updated product into the Upstash bulk cache
            $this->cachehelper->upsertProduct($ProductUID);

            // Handle attachment uploads + deletes (same request, after commit)
            $this->_handleAttachments('Product', $ProductUID, (int)$this->pageData['JwtData']->Org->OrgUID, (int)$this->pageData['JwtData']->User->UserUID, 'ProdAttachFiles', 'ProdAttachDeleteUIDs');

            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Message    = 'Updated Successfully';
            $this->auditlog->log(
                (int) $this->pageData['JwtData']->Org->OrgUID,
                (int) $this->pageData['JwtData']->User->UserUID,
                'UPDATE_PRODUCT', 'Product', (int) $ProductUID, (string) getPostValue($PostData, 'ItemName'),
                [], "Updated product " . getPostValue($PostData, 'ItemName'), 'Products',
                'MASTER', 'SUCCESS', '', 'WEB',
                $currentProd ? (array) $currentProd : [],
                $prodFormData
            );
            $this->EndReturnData->ProductUID = $ProductUID;

        } catch (InvalidArgumentException $e) {
            $this->dbwrite_model->rollbackTransaction();
            if ($e->getMessage() === 'VALIDATION_ERROR') {
                $this->EndReturnData->Error = true;
                $this->EndReturnData->Message = strip_tags($ErrorInForm);
                $this->EndReturnData->Errors = 'Please correct the highlighted errors.';
            } else {
                throw $e;
            }
        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function deleteProductDetails() {

        $this->EndReturnData = new stdClass();
		try {

            $ProductUID = (int) $this->input->post('ProductUID');
            if (!$ProductUID || $ProductUID <= 0) {
                throw new Exception('Invalid Product Information');
            }

            // if ($this->productHasTransactions($ProductUID)) {
            //     throw new Exception('Product has existing transactions (Invoices/Purchase Orders)');
            // }

            $this->load->model('products_model');
            if ($this->products_model->isProductLinkedToCombo($ProductUID)) {
                throw new Exception('This item is already linked with a combo. You cannot delete it.');
            }
            if ($this->products_model->productHasTransactions($ProductUID)) {
                throw new Exception('This item has been used in transactions. You cannot delete it.');
            }
            if ($this->products_model->productUsedInComboWithTransactions($ProductUID)) {
                throw new Exception('This item has been used in a combo that has transactions. You cannot delete it.');
            }
            $oldProductRows = $this->products_model->getProductsDetails(['Products.ProductUID' => $ProductUID]);
            $oldProductData = !empty($oldProductRows) ? (array) $oldProductRows[0] : [];
            $this->load->model('dbwrite_model');
            $UpdateResp = $this->dbwrite_model->updateData('Products', 'ProductTbl', $this->globalservice->baseDeleteArrayDetails(), array('ProductUID' => $ProductUID));
            if($UpdateResp->Error) {
                throw new Exception($UpdateResp->Message);
            }

            // Remove deleted product from the Upstash bulk cache
            $this->cachehelper->removeProduct($ProductUID);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Deleted Successfully';
            $this->auditlog->log(
                (int) $this->pageData['JwtData']->Org->OrgUID,
                (int) $this->pageData['JwtData']->User->UserUID,
                'DELETE_PRODUCT', 'Product', (int) $ProductUID, (string) ($oldProductData['ItemName'] ?? ''),
                [], "Deleted product #{$ProductUID}", 'Products',
                'MASTER', 'SUCCESS', '', 'WEB',
                $oldProductData,
                []
            );

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function deleteBulkProduct() {

        $this->EndReturnData = new stdClass();
		try {

            $selectAll = (int) $this->input->post('SelectAll');
            if ($selectAll === 1) {
                $orgUID    = (int) $this->pageData['JwtData']->Org->OrgUID;
                $rawFilter = $this->input->post('Filter');
                $filter    = (!empty($rawFilter) && is_string($rawFilter)) ? (array) json_decode($rawFilter, true) : [];
                $this->load->model('products_model');
                $ProductUIDs = $this->products_model->getProductUIDsByFilter($orgUID, $filter);
                $ProductUIDs = array_filter(array_map('intval', $ProductUIDs), fn($id) => $id > 0);
            } else {
                $ProductUIDs = $this->input->post('ProductUIDs[]');
                if(empty($ProductUIDs)) {
                    throw new Exception('No products selected for deletion');
                }
                if (!is_array($ProductUIDs)) {
                    $ProductUIDs = [$ProductUIDs];
                }
                $ProductUIDs = array_filter(array_map('intval', $ProductUIDs), fn($id) => $id > 0);
            }

            if (empty($ProductUIDs)) {
                throw new Exception('Invalid product IDs provided');
            }

            // Check if any product has transactions
            // foreach ($ProductUIDs as $productId) {
            //     if ($this->productHasTransactions($productId)) {
            //         throw new Exception("Product ID {$productId} has existing transactions");
            //     }
            // }
            
            $this->load->model('products_model');
            foreach ($ProductUIDs as $uid) {
                if ($this->products_model->isProductLinkedToCombo($uid)) {
                    throw new Exception('One or more selected items are linked to a combo. Remove them from the combo before deleting.');
                }
                if ($this->products_model->productHasTransactions($uid)) {
                    throw new Exception('One or more selected items have been used in transactions and cannot be deleted.');
                }
                if ($this->products_model->productUsedInComboWithTransactions($uid)) {
                    throw new Exception('One or more selected items have been used in a combo that has transactions and cannot be deleted.');
                }
            }

            $this->load->model('dbwrite_model');
            $UpdateResp = $this->dbwrite_model->updateData('Products', 'ProductTbl', $this->globalservice->baseDeleteArrayDetails(), [], array('ProductUID' => $ProductUIDs));
            if($UpdateResp->Error) {
                throw new Exception($UpdateResp->Message);
            }

            // Invalidate each deleted product + products list
            $keysToDelete = array_map(
                fn($id) => Upstashservice::keyProduct($id),
                $ProductUIDs
            );
            $keysToDelete[] = Upstashservice::keyProductsAll();
            $this->upstashservice->delMany($keysToDelete);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = count($ProductUIDs) . ' product(s) deleted successfully';
            $this->auditlog->log(
                (int) $this->pageData['JwtData']->Org->OrgUID,
                (int) $this->pageData['JwtData']->User->UserUID,
                'BULK_DELETE_PRODUCTS', 'Product', 0, implode(',', $ProductUIDs),
                ['count' => count($ProductUIDs)], 'Bulk deleted ' . count($ProductUIDs) . ' product(s)', 'Products',
                'MASTER', 'SUCCESS', '', 'WEB',
                ['deletedUIDs' => $ProductUIDs],
                []
            );

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }
    
    // COMBO / COMPOSITE PRODUCT METHODS
    
    /** Return non-composite products for BOM component search (Select2 AJAX) */
    public function getItemsForBOM() {

        $this->EndReturnData = new stdClass();
        try {
            $search = trim($this->input->post('search') ?? '');
            $OrgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            $items  = $this->products_model->getItemsForBOM($OrgUID, $search);
            $this->EndReturnData->Error = false;
            $this->EndReturnData->Items = $items;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
            $this->EndReturnData->Items   = [];
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    /** Add a composite product (IsComposite=1) + BOM rows */
    public function addComboItem() {

        $this->EndReturnData = new stdClass();
        try {
            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $GeneralSettings = $this->pageData['JwtData']->GenSettings ?? new stdClass();

            $PostData = $this->input->post();

            $comboName      = trim(getPostValue($PostData, 'ComboName'));
            $comboPrice     = (float) getPostValue($PostData, 'ComboSellingPrice', '', 0);
            $comboPurchasePrice = (float) getPostValue($PostData, 'ComboPurchasePrice', '', -1);

            if (empty($comboName)) {
                throw new InvalidArgumentException('Combo name is required.');
            }
            if ($comboPrice < 0) {
                throw new InvalidArgumentException('Selling price must be 0 or greater.');
            }
            if ($comboPurchasePrice < 0) {
                throw new InvalidArgumentException('Purchase price is required and must be 0 or greater.');
            }

            $componentsJson = getPostValue($PostData, 'ComboComponentsData', '', '[]');
            $components = json_decode($componentsJson, true);
            if (!is_array($components) || count($components) < 2) {
                throw new InvalidArgumentException('A combo item must have at least 2 component items.');
            }

            $userUID = (int) $this->pageData['JwtData']->User->UserUID;
            $orgUID  = (int) $this->pageData['JwtData']->Org->OrgUID;

            $taxUID     = (int) getPostValue($PostData, 'ComboTaxPercentage');
            $TaxDetails = null;
            if ($taxUID > 0) {
                $this->load->model('global_model');
                $TaxDetails = $this->global_model->getTaxPercentageDetailsInfo(['TaxDetail.TaxDetailsUID' => $taxUID])->Data[0] ?? null;
            }

            
            $Tax_Percentage  = isset($TaxDetails->Percentage) ? (float) $TaxDetails->Percentage : 0;
            $sellingTaxUID   = (int) getPostValue($PostData, 'ComboSellingTaxOption');
            $purchaseTaxUID  = (int) getPostValue($PostData, 'ComboPurchaseTaxOption');

            $Unit_Price = $comboPrice;
            if ($sellingTaxUID == 1 && $Tax_Percentage > 0) {
                // With Tax — entered price is tax-inclusive; back-calculate unit price
                $Unit_Price = $comboPrice / (1 + ($Tax_Percentage / 100));
            } elseif ($sellingTaxUID != 1 && $Tax_Percentage > 0) {
                // Without Tax — entered price is tax-exclusive; gross up to get SellingPrice
                $Unit_Price = $comboPrice;
                $comboPrice = round($comboPrice * (1 + ($Tax_Percentage / 100)), 4);
            }

            $primaryUnitUID = (int) getPostValue($PostData, 'ComboPrimaryUnit') ?: null;

            $comboData = [
                'OrgUID'                     => $orgUID,
                'ProdToken'                  => generate_uuid4(),
                'ItemName'                   => $comboName,
                'ProductType'                => 'Product',
                'UnitPrice'                  => round($Unit_Price, 5),
                'SellingPrice'               => $comboPrice,
                'SellingProductTaxUID'       => $sellingTaxUID ?: null,
                'PurchasePrice'              => $comboPurchasePrice,
                'PurchasePriceProductTaxUID' => $purchaseTaxUID ?: null,
                'MRP'           => (float) getPostValue($PostData, 'ComboMRP', '', 0),
                'TaxDetailsUID' => $taxUID ?: null,
                'TaxPercentage' => isset($TaxDetails->Percentage) ? $TaxDetails->Percentage : null,
                'CGST'          => isset($TaxDetails->CGST) ? $TaxDetails->CGST : null,
                'SGST'          => isset($TaxDetails->SGST) ? $TaxDetails->SGST : null,
                'IGST'          => isset($TaxDetails->IGST) ? $TaxDetails->IGST : null,
                'Description'   => getPostValue($PostData, 'ComboDescription'),
                'PrimaryUnitUID'=> $primaryUnitUID,
                'IsComposite'   => 1,
                'IsComboItem'   => 0,
                'CreatedBy'     => $userUID,
                'UpdatedBy'     => $userUID,
            ];

            $InsertResp = $this->dbwrite_model->insertData('Products', 'ProductTbl', $comboData);
            if ($InsertResp->Error) {
                throw new Exception($InsertResp->Message);
            }

            $this->saveProductBOM($InsertResp->ID, $PostData);
            $this->dbwrite_model->commitTransaction();

            $this->cachehelper->upsertComboProduct($InsertResp->ID);

            $this->EndReturnData->Error   = false;
            $this->EndReturnData->Message = 'Combo item created successfully';
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'CREATE_COMBO_ITEM', 'ComboItem', (int) $InsertResp->ID, (string) $comboName,
                [], "Created combo item {$comboName}", 'Products',
                'MASTER', 'SUCCESS', '', 'WEB',
                [],
                $comboData
            );

            if (getPostValue($PostData, 'getTableDetails') == 1) {
                $pageNo  = (int) $this->input->post('PageNo') ?: 1;
                $getResp = $this->fetchProductTableData($pageNo, 0, 1); // addComboItem — always groups
                $this->EndReturnData->List       = $getResp->RecordHtmlData;
                $this->EndReturnData->Pagination = $getResp->Pagination;
                $this->EndReturnData->Stats      = $this->fetchProductStats();
            }

        } catch (\Throwable $e) {
            if (isset($this->dbwrite_model)) $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    /** Edit a composite product + re-sync BOM rows */
    public function editComboItem() {

        $this->EndReturnData = new stdClass();
        try {
            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $GeneralSettings = $this->pageData['JwtData']->GenSettings ?? new stdClass();

            $PostData = $this->input->post();
            $comboUID = (int) getPostValue($PostData, 'ComboUID');

            if (!$comboUID) {
                throw new InvalidArgumentException('Invalid combo item.');
            }

            $comboName          = trim(getPostValue($PostData, 'ComboName'));
            $comboPrice         = (float) getPostValue($PostData, 'ComboSellingPrice', '', 0);
            $comboPurchasePrice = (float) getPostValue($PostData, 'ComboPurchasePrice', '', -1);

            if (empty($comboName)) {
                throw new InvalidArgumentException('Combo name is required.');
            }
            if ($comboPurchasePrice < 0) {
                throw new InvalidArgumentException('Purchase price is required and must be 0 or greater.');
            }

            $componentsJson = getPostValue($PostData, 'ComboComponentsData', '', '[]');
            $components = json_decode($componentsJson, true);
            if (!is_array($components) || count($components) < 2) {
                throw new InvalidArgumentException('A combo item must have at least 2 component items.');
            }

            $userUID = (int) $this->pageData['JwtData']->User->UserUID;

            $taxUID     = (int) getPostValue($PostData, 'ComboTaxPercentage');
            $TaxDetails = null;
            if ($taxUID > 0) {
                $this->load->model('global_model');
                $TaxDetails = $this->global_model->getTaxPercentageDetailsInfo(['TaxDetail.TaxDetailsUID' => $taxUID])->Data[0] ?? null;
            }

            $Tax_Percentage  = isset($TaxDetails->Percentage) ? (float) $TaxDetails->Percentage : 0;
            $sellingTaxUID   = (int) getPostValue($PostData, 'ComboSellingTaxOption');
            $purchaseTaxUID  = (int) getPostValue($PostData, 'ComboPurchaseTaxOption');

            $Unit_Price = $comboPrice;
            if ($sellingTaxUID == 1 && $Tax_Percentage > 0) {
                $Unit_Price = $comboPrice / (1 + ($Tax_Percentage / 100));
            } elseif ($sellingTaxUID != 1 && $Tax_Percentage > 0) {
                $Unit_Price = $comboPrice;
                $comboPrice = round($comboPrice * (1 + ($Tax_Percentage / 100)), 4);
            }

            $primaryUnitUID = (int) getPostValue($PostData, 'ComboPrimaryUnit') ?: null;

            $comboData = [
                'ItemName'                   => $comboName,
                'UnitPrice'                  => round($Unit_Price, 5),
                'SellingPrice'               => $comboPrice,
                'SellingProductTaxUID'       => $sellingTaxUID ?: null,
                'PurchasePrice'              => $comboPurchasePrice,
                'PurchasePriceProductTaxUID' => $purchaseTaxUID ?: null,
                'MRP'           => (float) getPostValue($PostData, 'ComboMRP', '', 0),
                'TaxDetailsUID' => $taxUID ?: null,
                'TaxPercentage' => isset($TaxDetails->Percentage) ? $TaxDetails->Percentage : null,
                'CGST'          => isset($TaxDetails->CGST) ? $TaxDetails->CGST : null,
                'SGST'          => isset($TaxDetails->SGST) ? $TaxDetails->SGST : null,
                'IGST'          => isset($TaxDetails->IGST) ? $TaxDetails->IGST : null,
                'Description'   => getPostValue($PostData, 'ComboDescription'),
                'PrimaryUnitUID'=> $primaryUnitUID,
                'UpdatedBy'     => $userUID,
            ];

            $this->load->model('products_model');
            $oldComboRows = $this->products_model->getProductsDetails(['Products.ProductUID' => $comboUID, 'Products.IsComposite' => 1]);
            $oldComboData = !empty($oldComboRows) ? (array) $oldComboRows[0] : [];
            $UpdateResp = $this->dbwrite_model->updateData('Products', 'ProductTbl', $comboData, ['ProductUID' => $comboUID]);
            if ($UpdateResp->Error) {
                throw new Exception($UpdateResp->Message);
            }

            $this->saveProductBOM($comboUID, $PostData);
            $this->dbwrite_model->commitTransaction();

            $this->cachehelper->upsertComboProduct($comboUID);

            if (getPostValue($PostData, 'getTableDetails') == 1) {
                $pageNo  = (int) $this->input->post('PageNo') ?: 1;
                $getResp = $this->fetchProductTableData($pageNo, 0, 1); // editComboItem — always groups
                $this->EndReturnData->List       = $getResp->RecordHtmlData;
                $this->EndReturnData->Pagination = $getResp->Pagination;
                $this->EndReturnData->Stats      = $this->fetchProductStats();
            }

            $this->EndReturnData->Error   = false;
            $this->EndReturnData->Message = 'Combo item updated successfully';
            $this->auditlog->log(
                (int) $this->pageData['JwtData']->Org->OrgUID,
                (int) $userUID,
                'UPDATE_COMBO_ITEM', 'ComboItem', (int) $comboUID, (string) $comboName,
                [], "Updated combo item {$comboName}", 'Products',
                'MASTER', 'SUCCESS', '', 'WEB',
                $oldComboData,
                $comboData
            );

        } catch (\Throwable $e) {
            if (isset($this->dbwrite_model)) $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    /** Retrieve combo product + BOM for edit modal */
    public function retrieveComboDetails() {

        $this->EndReturnData = new stdClass();
        try {
            $comboUID = (int) $this->input->post('ComboUID');
            if (!$comboUID) {
                throw new Exception('Invalid combo item.');
            }

            $data = $this->products_model->getProductsDetails([
                'Products.ProductUID'   => $comboUID,
                'Products.IsComposite'  => 1,
            ]);
            if (empty($data)) {
                throw new Exception('Combo item not found.');
            }

            $this->EndReturnData->Error      = false;
            $this->EndReturnData->Data       = $data[0];
            $this->EndReturnData->Components = $this->products_model->getProductBOM($comboUID);

        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    /** Lightweight BOM fetch for transaction forms — returns components with SellingPrice */
    public function getTransComboComponents() {
        $this->EndReturnData = new stdClass();
        try {
            $productUID = (int) $this->input->post('ProductUID');
            if (!$productUID) throw new Exception('Invalid product.');
            $this->EndReturnData->Error      = false;
            $this->EndReturnData->Components = $this->products_model->getProductBOM($productUID);
        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    /** Delete combo (soft delete) */
    public function deleteComboItem() {

        $this->EndReturnData = new stdClass();
        try {
            $comboUID = (int) $this->input->post('ComboUID');
            if (!$comboUID) {
                throw new Exception('Invalid combo item.');
            }

            $this->load->model('products_model');
            $oldComboRows = $this->products_model->getProductsDetails(['Products.ProductUID' => $comboUID, 'Products.IsComposite' => 1]);
            $oldComboData = !empty($oldComboRows) ? (array) $oldComboRows[0] : [];
            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $this->dbwrite_model->updateData(
                'Products', 'ProductTbl',
                $this->globalservice->baseDeleteArrayDetails(),
                ['ProductUID' => $comboUID, 'IsComposite' => 1]
            );
            $this->dbwrite_model->updateData(
                'Products', 'ProductBOMTbl',
                ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => (int) $this->pageData['JwtData']->User->UserUID],
                ['ParentProductUID' => $comboUID]
            );

            $this->dbwrite_model->commitTransaction();

            $this->cachehelper->removeProduct($comboUID);

            $this->EndReturnData->Error   = false;
            $this->EndReturnData->Message = 'Combo item deleted successfully';
            $this->auditlog->log(
                (int) $this->pageData['JwtData']->Org->OrgUID,
                (int) $this->pageData['JwtData']->User->UserUID,
                'DELETE_COMBO_ITEM', 'ComboItem', (int) $comboUID, (string) ($oldComboData['ItemName'] ?? ''),
                [], "Deleted combo item #{$comboUID}", 'Products',
                'MASTER', 'SUCCESS', '', 'WEB',
                $oldComboData,
                []
            );

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }
    
    // END COMBO METHODS

    public function toggleProductStatus() {

        $this->EndReturnData = new stdClass();
        try {

            $ProductUID = (int) $this->input->post('ProductUID');
            $newStatus  = (int) $this->input->post('IsActive');
            if (!$ProductUID) throw new Exception('Product ID is missing');
            if (!in_array($newStatus, [0, 1])) throw new Exception('Invalid status value');

            $this->load->model('dbwrite_model');
            $resp = $this->dbwrite_model->updateData(
                'Products', 'ProductTbl',
                ['IsActive' => $newStatus, 'UpdatedBy' => $this->pageData['JwtData']->User->UserUID],
                ['ProductUID' => $ProductUID]
            );
            if ($resp->Error) throw new Exception($resp->Message);

            $GeneralSettings = $this->pageData['JwtData']->GenSettings ?? new stdClass();

            $this->EndReturnData->Error   = false;
            $this->EndReturnData->Message = 'Status updated successfully';
            $this->auditlog->log(
                (int) $this->pageData['JwtData']->Org->OrgUID,
                (int) $this->pageData['JwtData']->User->UserUID,
                'TOGGLE_PRODUCT_STATUS', 'Product', (int) $ProductUID, '',
                ['isActive' => $newStatus], ($newStatus ? 'Activated' : 'Deactivated') . " product #{$ProductUID}", 'Products',
                'MASTER', 'SUCCESS', '', 'WEB',
                ['IsActive' => 1 - $newStatus],
                ['IsActive' => $newStatus]
            );

        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    /** Category List (AJAX pagination) — dedicated query */
    public function getCategoryList() {

        $this->EndReturnData = new stdClass();
        try {

            $OrgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            $pageNo = (int) $this->input->post('PageNo') ?: 1;
            $limit  = (int) $this->input->post('RowLimit') ?: 10;
            $offset = ($pageNo - 1) * $limit;
            $rawFilter = $this->input->post('Filter');
            $filter    = is_array($rawFilter) ? $rawFilter : (json_decode($rawFilter ?: '{}', true) ?? []);

            $filterResult = $this->products_model->catgFilterFormation((object)['TableAliasName' => 'Category'], $filter);

            $result  = $this->products_model->getCategoryListPaginated($OrgUID, $limit, $offset, $filterResult->SearchDirectQuery, $filterResult->sortOperation);


            $rowHtml = $this->load->view('products/categories/list', [
                'DataLists' => $result->rows,
                'StartFrom' => $offset,
                'JwtData'   => $this->pageData['JwtData'],
            ], TRUE);

            $this->EndReturnData->Error      = false;
            $this->EndReturnData->List       = $rowHtml;
            $this->EndReturnData->Pagination = $this->globalservice->buildPagePaginationHtml('/products/getCategoryList', $result->totalCount, $pageNo, $limit);
            $this->EndReturnData->TotalCount = $result->totalCount;
            $this->EndReturnData->UIDs       = array_column($result->rows, 'CategoryUID');

        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function getAllCategories() {

        $this->EndReturnData = new stdClass();
		try {

            $this->load->model('products_model');
            $getAllCatgs['Categories'] = $this->products_model->getCategoriesDetails([]);
            $this->EndReturnData->HtmlData = $this->load->view('products/items/catgfilter', $getAllCatgs, TRUE);
            
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Retrieved Successfully';

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = 'Unable to fetch categories';
        }
        
        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function getCategoryOptions() {

        $this->EndReturnData = new stdClass();
        try {
            $this->load->model('products_model');
            $rows = $this->products_model->getCategoriesDetails([]) ?? [];
            $this->EndReturnData->Error   = false;
            $this->EndReturnData->Options = array_values(array_map(function ($c) {
                return ['uid' => (int) $c->CategoryUID, 'name' => $c->Name];
            }, $rows));
        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Options = [];
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function getDropdownCache() {

        $this->EndReturnData = new stdClass();
        try {
            // Global keys — shared across all orgs/clients; literal key, never written from code
            $globalKeyMap = [
                'primaryUnit' => 'r2k-primary-units',
                'discType'    => 'r2k-disc-type',
                'prodType'    => 'r2k-prod-type',
                'prodTax'     => 'r2k-prod-tax',
                'taxDetails'  => 'r2k-tax-details',
            ];

            // Org keys — per-org, prefixed via orgKey(); writable from code
            $orgKeyMap = [
                'categories'  => $this->redisservice->orgKey('categories'),
                'brands'      => $this->redisservice->orgKey('brands'),
            ];

            $allKeyMap = array_merge($globalKeyMap, $orgKeyMap);

            // JS sends fields[] when only some keys were missing in the client-side
            // Upstash check — limit processing to only those fields.
            $requestedFields = $this->input->post('fields');
            if (!empty($requestedFields) && is_array($requestedFields)) {
                $keys = array_intersect_key($allKeyMap, array_flip(
                    array_intersect($requestedFields, array_keys($allKeyMap))
                ));
            } else {
                $keys = $allKeyMap;
            }

            $fieldNames   = array_keys($keys);
            $data         = [];
            $missingFields = $fieldNames; // assume all missing until pipeline says otherwise

            // Try Upstash pipeline for the requested fields
            // 'categories' is a Redis hash â†’ HGETALL; all others are strings â†’ GET
            if (!empty($fieldNames)) {
                $hashFields = ['categories', 'brands'];
                $cmds = array_map(function ($field, $key) use ($hashFields) {
                    return in_array($field, $hashFields) ? ['HGETALL', $key] : ['GET', $key];
                }, $fieldNames, array_values($keys));
                $pipeResults = $this->upstashservice->pipeline($cmds);

                if (!empty($pipeResults)) {
                    $missingFields = [];
                    foreach ($pipeResults as $i => $result) {
                        $field = $fieldNames[$i] ?? null;
                        if ($field === null) continue;
                        try {
                            $raw = (is_array($result) ? $result : [])[‘result’] ?? null;

                            if (in_array($field, [‘categories’, ‘brands’], true)) {
                                // HGETALL → flat [uid, jsonStr, uid, jsonStr, ...] array
                                if (is_array($raw) && count($raw) >= 2) {
                                    $items = [];
                                    for ($j = 0; $j + 1 < count($raw); $j += 2) {
                                        $decoded = json_decode($raw[$j + 1], true);
                                        if ($decoded) $items[] = (object)$decoded;
                                    }
                                    if (!empty($items)) { $data[$field] = $items; continue; }
                                }
                                $missingFields[] = $field;
                            } else {
                                if ($raw !== null) {
                                    $decoded      = json_decode($raw, true);
                                    $data[$field] = is_array($decoded)
                                        ? array_map(fn($r) => is_array($r) ? (object) $r : $r, $decoded)
                                        : $decoded;
                                } else {
                                    $missingFields[] = $field;
                                }
                            }
                        } catch (Throwable $th) {
                            $missingFields[] = $field;
                        }
                    }
                }
                // If pipeResults is empty â†’ Upstash disabled/error â†’ missingFields = all requested
            }

            // DB fallback for every field not found in Upstash
            // (model methods also write the result back to Upstash)
            if (!empty($missingFields)) {
                $this->load->model('global_model');
                foreach ($missingFields as $field) {
                    switch ($field) {
                        case 'primaryUnit': $data['primaryUnit'] = $this->global_model->getPrimaryUnitInfo()->Data  ?? []; break;
                        case 'discType':    $data['discType']    = $this->global_model->getDiscountTypeInfo()->Data ?? []; break;
                        case 'prodType':    $data['prodType']    = $this->global_model->getProductTypeInfo()->Data  ?? []; break;
                        case 'prodTax':     $data['prodTax']     = $this->global_model->getProductTaxInfo()->Data   ?? []; break;
                        case 'taxDetails':  $data['taxDetails']  = $this->global_model->getTaxDetailsInfo()->Data   ?? []; break;
                        case 'categories':  $data['categories']  = $this->products_model->getCategoriesDetails([])  ?? []; break;
                        case 'brands':      $data['brands']      = $this->products_model->getBrandsForCache((int)$this->pageData['JwtData']->Org->OrgUID) ?? []; break;
                    }
                }
            }

            $this->EndReturnData->Error = false;
            $this->EndReturnData->Data  = $data;

        } catch (Throwable $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function getProductsByCategory() {

        $this->EndReturnData = new stdClass();
        try {
            $CategoryUID = (int) $this->input->post('CategoryUID');
            $OrgUID      = (int) $this->pageData['JwtData']->Org->OrgUID;
            if ($CategoryUID <= 0) throw new Exception('Invalid Category.');

            $products = $this->products_model->getProductsByCategoryUID($CategoryUID, $OrgUID);

            $this->EndReturnData->Error    = false;
            $this->EndReturnData->Products = $products;
            $this->EndReturnData->Count    = count($products);
        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    private function buildCategoryFormData($postData, $isCreate = false) {
        $data = [
            'Name'        => getPostValue($postData, 'CategoryName'),
            'OrgUID'      => (int) $this->pageData['JwtData']->Org->OrgUID,
            'Description' => getPostValue($postData, 'CategoryDescription'),
            'UpdatedBy'   => $this->pageData['JwtData']->User->UserUID,
        ];
        if ($isCreate) {
            $data['CategToken'] = generate_uuid4();
            $data['CreatedBy']  = $this->pageData['JwtData']->User->UserUID;
        }
        return $data;
    }

    public function addCategoryDetails() {

        $this->EndReturnData = new stdClass();
        $ErrorInForm = '';
		try {

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData = $this->input->post();

            $this->load->model('formvalidation_model');
            $ErrorInForm = $this->formvalidation_model->categoryValidateForm($PostData);
            if (!empty($ErrorInForm)) {
                throw new InvalidArgumentException('VALIDATION_ERROR');
            }

            $catgFormData = $this->buildCategoryFormData($PostData, true);

            $insDataResp = $this->dbwrite_model->insertData('Products', 'CategoryTbl', $catgFormData);
            if($insDataResp->Error) {
                throw new Exception($insDataResp->Message);
            }
            
            $CategoryUID = $insDataResp->ID;

            $this->dbwrite_model->commitTransaction();

            // Handle attachment uploads + deletes (same request, after commit)
            $orgUID  = (int)$this->pageData['JwtData']->Org->OrgUID;
            $userUID = (int)$this->pageData['JwtData']->User->UserUID;
            $this->_handleAttachments('Category', $CategoryUID, $orgUID, $userUID, 'CatgAttachFiles', 'CatgAttachDeleteUIDs');

            // Cache update must be after commit so ReadDB can see the new row
            $this->cachehelper->upsertCategory($CategoryUID);

            $this->EndReturnData->Error        = FALSE;
            $this->EndReturnData->Message      = 'Created Successfully';
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'CREATE_PRODUCT_CATEGORY', 'ProductCategory', (int) $CategoryUID, (string) getPostValue($PostData, 'CategoryName'),
                [], "Created product category " . getPostValue($PostData, 'CategoryName'), 'Products',
                'MASTER', 'SUCCESS', '', 'WEB',
                [],
                $catgFormData
            );
            $this->EndReturnData->InsertId     = $CategoryUID;
            $this->EndReturnData->CategoryName = getPostValue($PostData, 'CategoryName');

        } catch (InvalidArgumentException $e) {
            $this->dbwrite_model->rollbackTransaction();
            if ($e->getMessage() === 'VALIDATION_ERROR') {
                $this->EndReturnData->Error = true;
                $this->EndReturnData->Message = strip_tags($ErrorInForm);
                $this->EndReturnData->Errors = 'Please correct the highlighted errors.';
            } else {
                throw $e;
            }
        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function retrieveCategoryDetails() {

        $this->EndReturnData = new stdClass();
		try {

            $CategoryUID = (int) $this->input->post('CategoryUID');
            if (!$CategoryUID || $CategoryUID <= 0) {
                throw new Exception('Invalid Category ID');
            }

            // Cache-Aside READ
            $cacheKey = Upstashservice::keyCategory($CategoryUID);
            $cached   = $this->upstashservice->get($cacheKey);

            if ($cached !== null) {
                $this->EndReturnData->Error   = FALSE;
                $this->EndReturnData->Message = 'Retrieved Successfully';
                $this->EndReturnData->Data    = (object)$cached['Data'];
            } else {
                $this->load->model('products_model');
                $GetCatgData = $this->products_model->getCategoriesDetails(['Category.CategoryUID' => $CategoryUID]);
                if (count($GetCatgData) != 1) {
                    throw new Exception('Category not found');
                }
                $this->EndReturnData->Error   = FALSE;
                $this->EndReturnData->Message = 'Retrieved Successfully';
                $this->EndReturnData->Data    = $GetCatgData[0];

                $this->upstashservice->set($cacheKey, ['Data' => $GetCatgData[0]], Upstashservice::TTL_CATEGORY);
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        
        $this->globalservice->sendJsonResponse($this->EndReturnData);

	}

    public function updateCategoryDetails() {

        $this->EndReturnData = new stdClass();
        $ErrorInForm = '';
		try {

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData = $this->input->post();

            $this->load->model('formvalidation_model');
            $ErrorInForm = $this->formvalidation_model->categoryValidateForm($PostData);
            if (!empty($ErrorInForm)) {
                throw new InvalidArgumentException('VALIDATION_ERROR');
            }

            $CategoryUID = (int) getPostValue($PostData, 'CategoryUID');

            $this->load->model('products_model');
            $oldCatgRows = $this->products_model->getCategoriesDetails(['Category.CategoryUID' => $CategoryUID]);
            $oldCatgData = !empty($oldCatgRows) ? (array) $oldCatgRows[0] : [];

            $catgFormData = $this->buildCategoryFormData($PostData, false);

            $UpdateDataResp = $this->dbwrite_model->updateData('Products', 'CategoryTbl', $catgFormData, array('CategoryUID' => $CategoryUID));
            if($UpdateDataResp->Error) {
                throw new Exception($UpdateDataResp->Message);
            }

            $this->dbwrite_model->commitTransaction();

            // Handle attachment uploads + deletes (same request, after commit)
            $orgUID  = (int)$this->pageData['JwtData']->Org->OrgUID;
            $userUID = (int)$this->pageData['JwtData']->User->UserUID;
            $this->_handleAttachments('Category', $CategoryUID, $orgUID, $userUID, 'CatgAttachFiles', 'CatgAttachDeleteUIDs');

            // Sync updated category into the Upstash bulk cache
            $this->cachehelper->upsertCategory($CategoryUID);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Updated Successfully';
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'UPDATE_PRODUCT_CATEGORY', 'ProductCategory', (int) $CategoryUID, (string) getPostValue($PostData, 'CategoryName'),
                [], "Updated product category " . getPostValue($PostData, 'CategoryName'), 'Products',
                'MASTER', 'SUCCESS', '', 'WEB',
                $oldCatgData,
                $catgFormData
            );

        } catch (InvalidArgumentException $e) {
            $this->dbwrite_model->rollbackTransaction();
            if ($e->getMessage() === 'VALIDATION_ERROR') {
                $this->EndReturnData->Error = true;
                $this->EndReturnData->Message = strip_tags($ErrorInForm);
                $this->EndReturnData->Errors = 'Please correct the highlighted errors.';
            } else {
                throw $e;
            }
        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function deleteCategoryDetails() {

        $this->EndReturnData = new stdClass();
		try {

            $CategoryUID = (int) $this->input->post('CategoryUID');
            if (!$CategoryUID || $CategoryUID <= 0) {
                throw new Exception('Invalid Category ID');
            }
                
            $this->load->model('products_model');
            $ExistsInProducts = $this->products_model->getProductsDetails(['Category.CategoryUID' => $CategoryUID]);
            if (!empty($ExistsInProducts) && count($ExistsInProducts) > 0) {
                throw new Exception('Category is linked to Product(s). Cannot delete.');
            }
            $oldCatgRows = $this->products_model->getCategoriesDetails(['Category.CategoryUID' => $CategoryUID]);
            $oldCatgData = !empty($oldCatgRows) ? (array) $oldCatgRows[0] : [];

            $this->load->model('dbwrite_model');
            $UpdateResp = $this->dbwrite_model->updateData('Products', 'CategoryTbl', $this->globalservice->baseDeleteArrayDetails(), array('CategoryUID' => $CategoryUID));
            if($UpdateResp->Error) {
                throw new Exception($UpdateResp->Message);
            }

            // Remove deleted category from the Upstash bulk cache
            $this->cachehelper->removeCategory($CategoryUID);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Deleted Successfully';
            $this->auditlog->log(
                (int) $this->pageData['JwtData']->Org->OrgUID,
                (int) $this->pageData['JwtData']->User->UserUID,
                'DELETE_PRODUCT_CATEGORY', 'ProductCategory', (int) $CategoryUID, (string) ($oldCatgData['Name'] ?? ''),
                [], "Deleted product category #{$CategoryUID}", 'Products',
                'MASTER', 'SUCCESS', '', 'WEB',
                $oldCatgData,
                []
            );

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function deleteBulkCategory() {

        $this->EndReturnData = new stdClass();
		try {

            $CategoryUIDs = $this->input->post('CategoryUIDs[]');
            if(empty($CategoryUIDs)) {
                throw new Exception('No categories selected for deletion');
            }

            // Validate and sanitize IDs
            if (!is_array($CategoryUIDs)) {
                $CategoryUIDs = [$CategoryUIDs];
            }
            $CategoryUIDs = array_map('intval', $CategoryUIDs);
            $CategoryUIDs = array_filter($CategoryUIDs, function($id) {
                return $id > 0;
            });

            if (empty($CategoryUIDs)) {
                throw new Exception('Invalid category IDs provided');
            }

            /** Cross Check with Products */
            $this->load->model('products_model');
            $ExistsInProducts = $this->products_model->getProductsDetails([], '', ['Products.CategoryUID' => $CategoryUIDs]);
            if(!empty($ExistsInProducts) && count($ExistsInProducts) > 0) {
                throw new Exception('One or more categories are linked to Product(s). Cannot delete.');
            }

            $this->load->model('dbwrite_model');
            $UpdateResp = $this->dbwrite_model->updateData('Products', 'CategoryTbl', $this->globalservice->baseDeleteArrayDetails(), [], array('CategoryUID' => $CategoryUIDs));
            if($UpdateResp->Error) {
                throw new Exception($UpdateResp->Message);
            }

            // Remove each deleted category from the Upstash bulk cache
            foreach ($CategoryUIDs as $id) {
                $this->cachehelper->removeCategory($id);
            }

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Deleted Successfully';
            $this->auditlog->log(
                (int) $this->pageData['JwtData']->Org->OrgUID,
                (int) $this->pageData['JwtData']->User->UserUID,
                'BULK_DELETE_PRODUCT_CATEGORIES', 'ProductCategory', 0, implode(',', $CategoryUIDs),
                ['count' => count($CategoryUIDs)], 'Bulk deleted ' . count($CategoryUIDs) . ' product categor' . (count($CategoryUIDs) === 1 ? 'y' : 'ies'), 'Products',
                'MASTER', 'SUCCESS', '', 'WEB',
                ['deletedUIDs' => $CategoryUIDs],
                []
            );

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    // ── Brands ───────────────────────────────────────────────────────────────

    public function getBrandList(): void {

        $this->EndReturnData = new stdClass();
        try {
            $OrgUID    = (int) $this->pageData['JwtData']->Org->OrgUID;
            $pageNo    = (int) $this->input->post('PageNo') ?: 1;
            $limit     = (int) $this->input->post('RowLimit') ?: 10;
            $offset    = ($pageNo - 1) * $limit;
            $rawFilter = $this->input->post('Filter');
            $filter    = is_array($rawFilter) ? $rawFilter : (json_decode($rawFilter ?: '{}', true) ?? []);

            $filterResult = $this->products_model->brandFilterFormation((object)['TableAliasName' => 'Brand'], $filter);

            $result  = $this->products_model->getBrandListPaginated($OrgUID, $limit, $offset, $filterResult->SearchDirectQuery, $filterResult->sortOperation);
            $rowHtml = $this->load->view('products/brands/list', [
                'DataLists' => $result->rows,
                'StartFrom' => $offset,
                'JwtData'   => $this->pageData['JwtData'],
            ], TRUE);

            $this->EndReturnData->Error      = false;
            $this->EndReturnData->List       = $rowHtml;
            $this->EndReturnData->Pagination = $this->globalservice->buildPagePaginationHtml('/products/getBrandList', $result->totalCount, $pageNo, $limit);
            $this->EndReturnData->TotalCount = $result->totalCount;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    private function buildBrandFormData(array $postData, bool $isCreate = false): array {

        $data = [
            'OrgUID'      => (int) $this->pageData['JwtData']->Org->OrgUID,
            'BrandName'   => trim($postData['BrandName'] ?? ''),
            'BrandCode'   => trim($postData['BrandCode'] ?? '') ?: null,
            'Description' => trim($postData['Description'] ?? '') ?: null,
            'UpdatedBy'   => (int) $this->pageData['JwtData']->User->UserUID,
        ];
        if ($isCreate) {
            $data['BrandToken'] = generate_uuid4();
            $data['CreatedBy']  = (int) $this->pageData['JwtData']->User->UserUID;
        }
        return $data;

    }

    public function addBrandDetails(): void {

        $this->EndReturnData = new stdClass();
        try {
            $orgUID  = (int) $this->pageData['JwtData']->Org->OrgUID;
            $userUID = (int) $this->pageData['JwtData']->User->UserUID;
            $post    = $this->input->post(null, true);

            $this->load->model('formvalidation_model');
            $valErr = $this->formvalidation_model->brandValidateForm([
                'BrandUID'    => 0,
                'BrandName'   => $post['BrandName'] ?? '',
                'BrandCode'   => $post['BrandCode'] ?? '',
                'Description' => $post['Description'] ?? '',
            ]);
            if ($valErr) throw new Exception($valErr);

            $this->load->model('products_model');
            if ($this->products_model->isDuplicateBrandName($orgUID, $post['BrandName'] ?? '')) {
                throw new Exception('A brand with this name already exists.');
            }

            $data = $this->buildBrandFormData($post, true);

            $this->load->model('dbwrite_model');
            $resp = $this->dbwrite_model->insertData('Products', 'BrandTbl', $data);
            if ($resp->Error) throw new Exception($resp->Message);

            $brandUID = (int) $resp->ID;

            $this->_handleAttachments('Brand', $brandUID, $orgUID, $userUID, 'BrandAttachFiles', 'BrandAttachDeleteUIDs');
            $this->cachehelper->upsertBrand($brandUID);

            $this->auditlog->log(
                $orgUID, $userUID,
                'ADD_BRAND', 'Brand', $brandUID, $data['BrandName'],
                ['BrandName' => $data['BrandName'], 'BrandCode' => $data['BrandCode']],
                'Created brand "' . $data['BrandName'] . '"', 'Products',
                'MASTER', 'SUCCESS', '', 'WEB', [], $data
            );

            $this->EndReturnData->Error    = false;
            $this->EndReturnData->Message  = 'Brand created successfully.';
            $this->EndReturnData->InsertId = $brandUID;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function retrieveBrandDetails(): void {

        $this->EndReturnData = new stdClass();
        try {
            $brandUID = (int) $this->input->post('BrandUID');
            $orgUID   = (int) $this->pageData['JwtData']->Org->OrgUID;
            if ($brandUID <= 0) throw new Exception('Invalid brand.');

            $this->load->model('products_model');
            $brand = $this->products_model->getBrandByUID($brandUID, $orgUID);
            if (!$brand) throw new Exception('Brand not found.');

            $attachments = $this->_getAttachmentsWithUrl('Brand', $brandUID, $orgUID);

            $this->EndReturnData->Error       = false;
            $this->EndReturnData->Data        = $brand;
            $this->EndReturnData->Attachments = $attachments;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function updateBrandDetails(): void {

        $this->EndReturnData = new stdClass();
        try {
            $orgUID   = (int) $this->pageData['JwtData']->Org->OrgUID;
            $userUID  = (int) $this->pageData['JwtData']->User->UserUID;
            $post     = $this->input->post(null, true);
            $brandUID = (int) ($post['BrandUID'] ?? 0);
            if ($brandUID <= 0) throw new Exception('Invalid brand.');

            $this->load->model('formvalidation_model');
            $valErr = $this->formvalidation_model->brandValidateForm([
                'BrandUID'    => $brandUID,
                'BrandName'   => $post['BrandName'] ?? '',
                'BrandCode'   => $post['BrandCode'] ?? '',
                'Description' => $post['Description'] ?? '',
            ]);
            if ($valErr) throw new Exception($valErr);

            $this->load->model('products_model');
            if ($this->products_model->isDuplicateBrandName($orgUID, $post['BrandName'] ?? '', $brandUID)) {
                throw new Exception('A brand with this name already exists.');
            }

            $oldBrand = $this->products_model->getBrandByUID($brandUID, $orgUID);
            $oldRec   = $oldBrand ? (array) $oldBrand : [];

            $data = $this->buildBrandFormData($post, false);

            $this->load->model('dbwrite_model');
            $resp = $this->dbwrite_model->updateData('Products', 'BrandTbl', $data, ['BrandUID' => $brandUID, 'OrgUID' => $orgUID]);
            if ($resp->Error) throw new Exception($resp->Message);

            $this->_handleAttachments('Brand', $brandUID, $orgUID, $userUID, 'BrandAttachFiles', 'BrandAttachDeleteUIDs');
            $this->cachehelper->upsertBrand($brandUID);

            $this->auditlog->log(
                $orgUID, $userUID,
                'UPDATE_BRAND', 'Brand', $brandUID, $data['BrandName'],
                [], 'Updated brand "' . $data['BrandName'] . '"', 'Products',
                'MASTER', 'SUCCESS', '', 'WEB', $oldRec, $data
            );

            $this->EndReturnData->Error    = false;
            $this->EndReturnData->Message  = 'Brand updated successfully.';

        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function deleteBrandDetails(): void {

        $this->EndReturnData = new stdClass();
        try {
            $brandUID = (int) $this->input->post('BrandUID');
            $orgUID   = (int) $this->pageData['JwtData']->Org->OrgUID;
            $userUID  = (int) $this->pageData['JwtData']->User->UserUID;
            if ($brandUID <= 0) throw new Exception('Invalid brand.');

            $this->load->model('dbwrite_model');
            $resp = $this->dbwrite_model->updateData('Products', 'BrandTbl', $this->globalservice->baseDeleteArrayDetails(), ['BrandUID' => $brandUID, 'OrgUID' => $orgUID]);
            if ($resp->Error) throw new Exception($resp->Message);

            $this->cachehelper->removeBrand($brandUID);

            $this->auditlog->log(
                $orgUID, $userUID,
                'DELETE_BRAND', 'Brand', $brandUID, '',
                ['brandUID' => $brandUID], 'Deleted brand #' . $brandUID, 'Products',
                'MASTER', 'SUCCESS', '', 'WEB',
                ['brandUID' => $brandUID], []
            );

            $this->EndReturnData->Error    = false;
            $this->EndReturnData->Message  = 'Brand deleted successfully.';

        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function deleteBulkBrand(): void {

        $this->EndReturnData = new stdClass();
        try {
            $brandUIDs = $this->input->post('BrandUIDs[]');
            if (empty($brandUIDs)) throw new Exception('No brands selected for deletion.');

            if (!is_array($brandUIDs)) { $brandUIDs = [$brandUIDs]; }
            $brandUIDs = array_filter(array_map('intval', $brandUIDs), fn($id) => $id > 0);
            if (empty($brandUIDs)) throw new Exception('Invalid brand IDs provided.');

            $orgUID  = (int) $this->pageData['JwtData']->Org->OrgUID;
            $userUID = (int) $this->pageData['JwtData']->User->UserUID;

            $this->load->model('dbwrite_model');
            $resp = $this->dbwrite_model->updateData('Products', 'BrandTbl', $this->globalservice->baseDeleteArrayDetails(), [], ['BrandUID' => array_values($brandUIDs)]);
            if ($resp->Error) throw new Exception($resp->Message);

            foreach ($brandUIDs as $id) { $this->cachehelper->removeBrand($id); }

            $this->auditlog->log(
                $orgUID, $userUID,
                'BULK_DELETE_BRANDS', 'Brand', 0, implode(',', $brandUIDs),
                ['count' => count($brandUIDs)], 'Bulk deleted ' . count($brandUIDs) . ' brand' . (count($brandUIDs) > 1 ? 's' : ''), 'Products',
                'MASTER', 'SUCCESS', '', 'WEB',
                ['deletedUIDs' => array_values($brandUIDs)], []
            );

            $this->EndReturnData->Error    = false;
            $this->EndReturnData->Message  = 'Deleted Successfully.';

        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function syncBrandsCache(): void {

        $this->EndReturnData = new stdClass();
        try {
            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            $brands = $this->products_model->getBrandsForCache($orgUID);
            if (empty($brands)) throw new Exception('No active brands found.');

            $cacheKey = $this->redisservice->orgKey('brands');
            $this->upstashservice->del($cacheKey);
            $newMap = [];
            foreach ($brands as $b) {
                $uid                  = (int) $b->BrandUID;
                $newMap[(string)$uid] = [
                    'BrandUID'    => $uid,
                    'BrandName'   => $b->BrandName   ?? '',
                    'BrandCode'   => $b->BrandCode   ?? '',
                    'Description' => $b->Description ?? '',
                    'Image'       => $b->Image       ?? '',
                ];
            }
            $this->upstashservice->hmset($cacheKey, $newMap);

            $this->EndReturnData->Error   = false;
            $this->EndReturnData->Message = count($brands) . ' brand(s) synced to cache.';
            $this->EndReturnData->Count   = count($brands);

        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    // ── Cache Sync ────────────────────────────────────────────────────────────

    /**
     * Rebuild the org-level items cache map in Upstash.
     * Stores every active product keyed by ProductUID with TTL 0 (no expiry).
     */
    public function syncProductsCache() {

        $this->EndReturnData = new stdClass();
        try {
            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;

            $this->load->model('products_model');
            $products = $this->products_model->getProductsForCache($orgUID);
            if (empty($products)) throw new Exception('No active items found.');

            // Build BOM map: parentUID => [{uid, qty}, ...] — one query for all composites
            $bomRows      = $this->products_model->getAllProductBOMsForSync($orgUID);
            $bomByParent  = [];
            foreach ($bomRows as $row) {
                $pUID = (string)(int)$row->ParentProductUID;
                $bomByParent[$pUID][] = [
                    'uid' => (int) $row->ChildProductUID,
                    'qty' => (float) $row->Quantity,
                ];
            }

            $cacheKey = $this->redisservice->orgKey('products');
            $this->upstashservice->del($cacheKey);
            $newMap = [];

            foreach ($products as $prod) {
                if ((int)($prod->NotForSale ?? 0) === 1) continue;
                $uid         = (int) $prod->ProductUID;
                $isComposite = (int)($prod->IsComposite ?? 0);
                $entry = [
                    'ProductUID'                  => $uid,
                    'ItemName'                    => $prod->ItemName                   ?? '',
                    'ProductType'                 => $prod->ProductType                ?? '',
                    'CategoryUID'                 => (int)($prod->CategoryUID          ?? 0),
                    'CategoryName'                => $prod->CategoryName               ?? '',
                    'HSNSACCode'                  => $prod->HSNSACCode                 ?? '',
                    'PartNumber'                  => $prod->PartNumber                 ?? '',
                    'SKU'                         => $prod->SKU                        ?? '',
                    'Description'                 => $prod->Description                ?? '',
                    'PrimaryUnitUID'              => (int)($prod->PrimaryUnitUID       ?? 0),
                    'PrimaryUnitName'             => $prod->PrimaryUnitName            ?? '',
                    'MRP'                         => (float)($prod->MRP                ?? 0),
                    'SellingPrice'                => (float)($prod->SellingPrice       ?? 0),
                    'PurchasePrice'               => (float)($prod->PurchasePrice      ?? 0),
                    'SellingProductTaxUID'        => (int)($prod->SellingProductTaxUID ?? 0),
                    'PurchasePriceProductTaxUID'  => (int)($prod->PurchasePriceProductTaxUID ?? 0),
                    'TaxDetailsUID'               => (int)($prod->TaxDetailsUID        ?? 0),
                    'TaxPercentage'               => (float)($prod->TaxPercentage      ?? 0),
                    'CGST'                        => (float)($prod->CGST               ?? 0),
                    'SGST'                        => (float)($prod->SGST               ?? 0),
                    'IGST'                        => (float)($prod->IGST               ?? 0),
                    'AvailableQuantity'           => (float)($prod->AvailableQuantity  ?? 0),
                    'Discount'                    => (float)($prod->Discount           ?? 0),
                    'DiscountTypeUID'             => (int)($prod->DiscountTypeUID      ?? 0),
                    'LowStockAlertAt'             => (float)($prod->LowStockAlertAt    ?? 0),
                    'NotForSale'                  => (int)($prod->NotForSale           ?? 0),
                    'IsComboItem'                 => (int)($prod->IsComboItem          ?? 0),
                    'IsComposite'                 => $isComposite,
                    'IsSerialTracked'             => (int)($prod->IsSerialTracked      ?? 0),
                ];
                if ($isComposite) {
                    $entry['items'] = $bomByParent[(string)$uid] ?? [];
                }
                $newMap[(string)$uid] = $entry;
            }

            $this->upstashservice->hmset($cacheKey, $newMap);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = count($products) . ' item(s) synced to cache.';
            $this->EndReturnData->Count   = count($products);

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    /**
     * Rebuild the org-level categories cache map in Upstash.
     * Stores every active category keyed by CategoryUID with TTL 0 (no expiry).
     */
    public function syncCategoriesCache() {

        $this->EndReturnData = new stdClass();
        try {
            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;

            $this->load->model('products_model');
            $categories = $this->products_model->getCategoriesForCache($orgUID);
            if (empty($categories)) throw new Exception('No active categories found.');

            $cacheKey = $this->redisservice->orgKey('categories');
            $this->upstashservice->del($cacheKey);
            $newMap = [];
            foreach ($categories as $cat) {
                $uid = (int)$cat->CategoryUID;
                $newMap[(string)$uid] = [
                    'CategoryUID' => $uid,
                    'Name'        => $cat->Name        ?? '',
                    'Description' => $cat->Description ?? '',
                ];
            }
            $this->upstashservice->hmset($cacheKey, $newMap);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = count($categories) . ' categorie(s) synced to cache.';
            $this->EndReturnData->Count   = count($categories);

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    // â”€â”€ Product / Category Attachments â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * Handles new file uploads and pending deletes in one call.
     * Called inside addProductData / updateProductData / addCategoryDetails / updateCategoryDetails
     * right after commitTransaction — runs outside the main transaction (non-fatal).
     *
     * Files key   : ProdAttachFiles[] for products, CatgAttachFiles[] for categories
     * Deletes key : ProdAttachDeleteUIDs (comma list) / CatgAttachDeleteUIDs
     */
    private function _handleAttachments(string $entityType, int $entityUID, int $orgUID, int $userUID, string $filesKey, string $deleteKey): void {
        $this->load->model('dbwrite_model');
        $this->load->library('fileupload');

        $maxFiles   = in_array($entityType, ['Category', 'Brand']) ? 3 : 5;
        $maxTotalMB = in_array($entityType, ['Category', 'Brand']) ? 3 : 5;
        $allowed    = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if ($entityType === 'Category') {
            $folder = 'categories/attachments/' . $entityUID;
        } elseif ($entityType === 'Brand') {
            $folder = 'brands/attachments/' . $entityUID;
        } else {
            $folder = 'products/attachments/' . $entityUID;
        }

        // Use WriteDb directly — avoids ReadDb replication lag
        $wdb = $this->dbwrite_model->getWriteDb();
        $wdb->db_debug = FALSE;

        // 1. Auto-migrate legacy Image field into the attachment table (once per entity)
        //    Prevents the old single image from being silently replaced on first new upload.
        if ($entityType === 'Product')       { $legacyTbl = 'ProductTbl';  $legacyPkCol = 'ProductUID'; }
        elseif ($entityType === 'Brand')    { $legacyTbl = 'BrandTbl';    $legacyPkCol = 'BrandUID'; }
        else                                { $legacyTbl = 'CategoryTbl'; $legacyPkCol = 'CategoryUID'; }
        $existCountQ = $wdb->query(
            "SELECT COUNT(*) AS cnt FROM Products.EntityAttachmentsTbl
              WHERE EntityType = ? AND EntityUID = ? AND OrgUID = ? AND IsDeleted = 0",
            [$entityType, $entityUID, $orgUID]
        );
        $existCount = $existCountQ ? (int)($existCountQ->row()->cnt ?? 0) : 0;

        // 2. Process pending deletions
        $deleteRaw = $this->input->post($deleteKey) ?: '';
        if ($deleteRaw) {
            foreach (array_filter(array_map('intval', explode(',', $deleteRaw))) as $attachUID) {
                $wdb->query(
                    "UPDATE Products.EntityAttachmentsTbl SET IsDeleted=1, IsActive=0, UpdatedBy=? WHERE AttachUID=? AND OrgUID=? AND IsDeleted=0",
                    [$userUID, $attachUID, $orgUID]
                );
            }
        }

        // 3. Upload new files — determine next SortOrder from WriteDb (not ReadDb)
        $files = $_FILES[$filesKey] ?? null;
        if (empty($files) || !isset($files['name']) || (is_array($files['name']) ? empty($files['name'][0]) : empty($files['name']))) {
            $this->_syncPrimaryImage($entityType, $entityUID, $orgUID, $userUID);
            return;
        }

        // MAX(SortOrder) via WriteDb — the only reliable source after a just-committed write
        $maxSortQ  = $wdb->query(
            "SELECT COALESCE(MAX(SortOrder), 0) AS maxSort, COUNT(*) AS cnt, COALESCE(SUM(FileSize), 0) AS totalSize
               FROM Products.EntityAttachmentsTbl
              WHERE EntityType = ? AND EntityUID = ? AND OrgUID = ? AND IsDeleted = 0",
            [$entityType, $entityUID, $orgUID]
        );
        $maxSortRow = $maxSortQ ? $maxSortQ->row() : null;
        $sortStart  = (int)($maxSortRow->maxSort ?? 0) + 1;
        $existSlots = (int)($maxSortRow->cnt      ?? 0);
        $totalSize  = (float)($maxSortRow->totalSize ?? 0);

        $count = is_array($files['name']) ? count($files['name']) : 1;
        $added = 0;

        for ($i = 0; $i < $count; $i++) {
            $err  = is_array($files['error'])    ? $files['error'][$i]    : $files['error'];
            $name = is_array($files['name'])     ? $files['name'][$i]     : $files['name'];
            $type = is_array($files['type'])     ? $files['type'][$i]     : $files['type'];
            $size = is_array($files['size'])     ? $files['size'][$i]     : $files['size'];
            $tmp  = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];

            if ($err !== UPLOAD_ERR_OK || !$name || empty($tmp)) continue;
            if (!in_array($type, $allowed)) continue;
            if (($existSlots + $added) >= $maxFiles) break;
            if (($totalSize + $size) > $maxTotalMB * 1024 * 1024) break;

            $totalSize += $size;

            $safe   = time() . '_' . $i . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
            $result = $this->fileupload->fileUpload('file', $folder . '/' . $safe, $tmp);
            if ($result->Error) continue;

            $wdb->insert('Products.EntityAttachmentsTbl', [
                'OrgUID'     => $orgUID,
                'EntityType' => $entityType,
                'EntityUID'  => $entityUID,
                'FileName'   => $name,
                'FilePath'   => '/' . ltrim($result->Path, '/'),
                'FileSize'   => (int)$size,
                'SortOrder'  => $sortStart + $added,
                'IsDeleted'  => 0,
                'IsActive'   => 1,
                'CreatedBy'  => $userUID,
                'UpdatedBy'  => $userUID,
            ]);
            $added++;
        }

        $this->_syncPrimaryImage($entityType, $entityUID, $orgUID, $userUID);
    }

    /**
     * Returns CDN-prefixed attachment array for a given entity.
     * Used by retrieve endpoints to embed attachments in the edit response.
     */
    private function _getAttachmentsWithUrl(string $entityType, int $entityUID, int $orgUID): array {
        $this->load->model('products_model');
        $cdnUrl = rtrim(getenv('FILE_UPLOAD') == 'amazonaws' ? getenv('CDN_URL') : getenv('CFLARE_R2_CDN'), '/');
        $rows   = $this->products_model->getEntityAttachments($entityType, $entityUID, $orgUID);
        foreach ($rows as &$r) {
            $r['Url'] = $cdnUrl . '/' . ltrim($r['FilePath'], '/');
        }
        return $rows;
    }

    /** GET: fetch all attachments for a product or category */
    public function getAttachments() {
        $this->EndReturnData = new stdClass();
        try {
            $entityType = $this->input->get_post('EntityType');
            $entityUID  = (int) $this->input->get_post('EntityUID');
            $orgUID     = (int) $this->pageData['JwtData']->Org->OrgUID;

            if (!in_array($entityType, ['Product', 'Category', 'Brand'])) throw new Exception('Invalid entity type.');
            if ($entityUID <= 0) throw new Exception('Invalid entity ID.');

            $this->load->model('products_model');
            $attachments = $this->products_model->getEntityAttachments($entityType, $entityUID, $orgUID);

            $cdnUrl = getenv('FILE_UPLOAD') == 'amazonaws' ? getenv('CDN_URL') : getenv('CFLARE_R2_CDN');
            foreach ($attachments as &$att) {
                $att['Url'] = rtrim($cdnUrl, '/') . '/' . ltrim($att['FilePath'], '/');
            }

            $this->EndReturnData->Error       = false;
            $this->EndReturnData->Attachments = $attachments;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    /** POST: upload one or more images for a product or category */
    public function saveAttachments() {
        $this->EndReturnData = new stdClass();
        try {
            $entityType   = $this->input->post('EntityType');
            $entityUID    = (int) $this->input->post('EntityUID');
            $orgUID       = (int) $this->pageData['JwtData']->Org->OrgUID;
            $userUID      = (int) $this->pageData['JwtData']->User->UserUID;
            $maxFiles     = in_array($entityType, ['Category', 'Brand']) ? 3 : 5;
            $maxTotalMB   = in_array($entityType, ['Category', 'Brand']) ? 3 : 5;

            if (!in_array($entityType, ['Product', 'Category', 'Brand'])) throw new Exception('Invalid entity type.');
            if ($entityUID <= 0) throw new Exception('Invalid entity ID.');

            $files = $_FILES['Attachments'] ?? null;
            if (empty($files) || empty($files['name'][0])) {
                $this->EndReturnData->Error   = false;
                $this->EndReturnData->Message = 'No files uploaded.';
                $this->globalservice->sendJsonResponse($this->EndReturnData);
                return;
            }

            $this->load->model('products_model');
            $existing = $this->products_model->getEntityAttachments($entityType, $entityUID, $orgUID);
            $existingCount = count($existing);

            $count     = count($files['name']);
            $allowed   = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $totalSize = 0;
            $saved     = [];

            $this->load->model('dbwrite_model');
            $this->load->library('fileupload');

            if ($entityType === 'Category')     { $folder = 'categories/attachments/' . $entityUID; }
            elseif ($entityType === 'Brand')    { $folder = 'brands/attachments/' . $entityUID; }
            else                                { $folder = 'products/attachments/' . $entityUID; }
            $sortStart = $existingCount + 1;

            for ($i = 0; $i < min($count, $maxFiles); $i++) {
                if ($files['error'][$i] !== UPLOAD_ERR_OK || empty($files['name'][$i])) continue;
                if (!in_array($files['type'][$i], $allowed)) continue;
                if (($existingCount + count($saved) + 1) > $maxFiles) break;

                $totalSize += $files['size'][$i];
                if ($totalSize > $maxTotalMB * 1024 * 1024) break;

                $origName   = basename($files['name'][$i]);
                $safeName   = time() . '_' . $i . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $origName);
                $storagePath = $folder . '/' . $safeName;

                $uploadResult = $this->fileupload->fileUpload('file', $storagePath, $files['tmp_name'][$i]);
                if ($uploadResult->Error) continue;

                $resp = $this->dbwrite_model->insertData('Products', 'EntityAttachmentsTbl', [
                    'OrgUID'     => $orgUID,
                    'EntityType' => $entityType,
                    'EntityUID'  => $entityUID,
                    'FileName'   => $origName,
                    'FilePath'   => '/' . ltrim($uploadResult->Path, '/'),
                    'FileSize'   => $files['size'][$i],
                    'SortOrder'  => $sortStart + count($saved),
                    'IsDeleted'  => 0,
                    'IsActive'   => 1,
                    'CreatedBy'  => $userUID,
                    'UpdatedBy'  => $userUID,
                ]);

                if (!$resp->Error) {
                    $saved[] = ['AttachUID' => (int)$resp->ID, 'FileName' => $origName, 'FilePath' => '/' . ltrim($uploadResult->Path, '/')];
                }
            }

            // Sync primary image to EntityTbl.Image
            $this->_syncPrimaryImage($entityType, $entityUID, $orgUID, $userUID);

            $this->EndReturnData->Error   = false;
            $this->EndReturnData->Message = count($saved) . ' image(s) saved.';
            $this->EndReturnData->Saved   = $saved;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    /** POST: soft-delete one attachment */
    public function deleteAttachment() {
        $this->EndReturnData = new stdClass();
        try {
            $attachUID  = (int) $this->input->post('AttachUID');
            $entityType = $this->input->post('EntityType');
            $entityUID  = (int) $this->input->post('EntityUID');
            $orgUID     = (int) $this->pageData['JwtData']->Org->OrgUID;
            $userUID    = (int) $this->pageData['JwtData']->User->UserUID;

            if ($attachUID <= 0) throw new Exception('Invalid attachment.');

            $this->load->model('dbwrite_model');
            $resp = $this->dbwrite_model->updateData('Products', 'EntityAttachmentsTbl',
                ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID],
                ['AttachUID' => $attachUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );
            if ($resp->Error) throw new Exception($resp->Message);

            // Sync primary image after deletion
            if (in_array($entityType, ['Product', 'Category']) && $entityUID > 0) {
                $this->_syncPrimaryImage($entityType, $entityUID, $orgUID, $userUID);
            }

            $this->EndReturnData->Error   = false;
            $this->EndReturnData->Message = 'Attachment deleted.';
        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    /** Syncs EntityTbl.Image with the first remaining attachment's FilePath */
    private function _syncPrimaryImage(string $entityType, int $entityUID, int $orgUID, int $userUID): void {
        try {
            $this->load->model('products_model');
            $primary = $this->products_model->getEntityPrimaryImage($entityType, $entityUID, $orgUID);
            $this->load->model('dbwrite_model');
            if ($entityType === 'Product') {
                $this->dbwrite_model->updateData('Products', 'ProductTbl',
                    ['Image' => $primary, 'UpdatedBy' => $userUID],
                    ['ProductUID' => $entityUID, 'OrgUID' => $orgUID]
                );
                $this->cachehelper->upsertProduct($entityUID);
            } elseif ($entityType === 'Category') {
                $this->dbwrite_model->updateData('Products', 'CategoryTbl',
                    ['Image' => $primary, 'UpdatedBy' => $userUID],
                    ['CategoryUID' => $entityUID, 'OrgUID' => $orgUID]
                );
            }
        } catch (Exception $e) {
            log_message('error', '_syncPrimaryImage failed: ' . $e->getMessage());
        }
    }

    // ── Price Lists ───────────────────────────────────────────────────────────
    public function getPriceListData(): void {
        $this->EndReturnData = new stdClass();
        try {
            $orgUID    = (int) $this->pageData['JwtData']->Org->OrgUID;
            $pageNo    = (int) $this->input->post('PageNo') ?: 1;
            $limit     = (int) $this->input->post('RowLimit') ?: 10;
            $offset    = ($pageNo - 1) * $limit;
            $rawFilter = $this->input->post('Filter');
            $filter    = is_array($rawFilter) ? $rawFilter : (json_decode($rawFilter ?: '{}', true) ?? []);

            $this->load->model('pricelists_model');
            $result = $this->pricelists_model->getPriceListPaginated($orgUID, $limit, $offset, $filter);

            $this->EndReturnData->Error      = false;
            $this->EndReturnData->List       = $this->_buildPriceListHtml($result->rows, $offset);
            $this->EndReturnData->Pagination = $this->globalservice->buildPagePaginationHtml('/products/getPriceListData', $result->totalCount, $pageNo, $limit);
            $this->EndReturnData->TotalCount = $result->totalCount;
        } catch (Throwable $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    /**
     * @param array $rows
     * @param int   $offset
     * @return string
     */
    private function _buildPriceListHtml(array $rows, int $offset): string {
        return $this->load->view('products/pricelists/list', [
            'DataLists' => $rows,
            'StartFrom' => $offset,
            'JwtData'   => $this->pageData['JwtData'],
        ], TRUE);
    }

    /**
     * @param int $orgUID
     * @param int $pageNo
     * @param int $limit
     * @param array $filter
     * @return void  populates $this->EndReturnData with List/Pagination/TotalCount
     */
    private function _appendPriceListRefresh(int $orgUID, int $pageNo, int $limit, array $filter): void {
        $offset  = ($pageNo - 1) * $limit;
        $result  = $this->pricelists_model->getPriceListPaginated($orgUID, $limit, $offset, $filter);
        $this->EndReturnData->List       = $this->_buildPriceListHtml($result->rows, $offset);
        $this->EndReturnData->Pagination = $this->globalservice->buildPagePaginationHtml('/products/getPriceListData', $result->totalCount, $pageNo, $limit);
        $this->EndReturnData->TotalCount = $result->totalCount;
    }

    public function getPriceListForEdit(): void {
        $this->EndReturnData = new stdClass();
        try {
            $orgUID       = (int) $this->pageData['JwtData']->Org->OrgUID;
            $priceListUID = (int) $this->input->get('uid', true);
            if ($priceListUID <= 0) throw new InvalidArgumentException('Invalid price list ID.');

            $this->load->model('pricelists_model');
            $data = $this->pricelists_model->getForEdit($orgUID, $priceListUID);
            if (!$data) throw new Exception('Price list not found.');

            $this->EndReturnData->Error = false;
            $this->EndReturnData->Data  = $data;
        } catch (InvalidArgumentException $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        } catch (Exception $e) {
            log_message('error', 'getPriceListForEdit: ' . $e->getMessage());
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = 'Failed to load price list data.';
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function savePriceList(): void {
        $this->EndReturnData = new stdClass();
        try {
            $jwt     = $this->pageData['JwtData'];
            $orgUID  = (int) $jwt->Org->OrgUID;
            $userUID = (int) $jwt->User->UserUID;
            $post    = $this->input->post(null, true);

            $plUID = (int) ($post['PLUID'] ?? 0);
            $isEdit = $plUID > 0;

            $name = trim($post['Name'] ?? '');
            if ($name === '') throw new InvalidArgumentException('Price list name is required.');

            $assignedTo = $post['AssignedToType'] ?? 'All';
            if (!in_array($assignedTo, ['All', 'Groups', 'Customers'], true))
                throw new InvalidArgumentException('Invalid Assigned To value.');

            $scope = $post['Scope'] ?? 'All';
            if (!in_array($scope, ['All', 'Specific'], true))
                throw new InvalidArgumentException('Invalid Scope value.');

            $discounts   = json_decode($post['Discounts']   ?? '[]', true) ?: [];
            $rules       = json_decode($post['Rules']       ?? '[]', true) ?: [];
            $assignments = json_decode($post['Assignments'] ?? '[]', true) ?: [];

            if ($scope === 'Specific' && empty($rules))
                throw new InvalidArgumentException('Add at least one product rule for Specific Products scope.');

            $this->load->model(['pricelists_model', 'dbwrite_model']);

            if ($isEdit && !$this->pricelists_model->getByUID($orgUID, $plUID))
                throw new Exception('Price list not found.');

            $headerData = [
                'Name'           => $name,
                'Status'         => (int) ($post['Status'] ?? 1),
                'Priority'       => max(1, (int) ($post['Priority'] ?? 1)),
                'ValidFrom'      => $post['ValidFrom'] ?? '',
                'ValidTo'        => $post['ValidTo']   ?? '',
                'Description'    => $post['Description'] ?? '',
                'AssignedToType' => $assignedTo,
                'Scope'          => $scope,
                'GlobalBasedOn'  => $post['GlobalBasedOn'] ?? 'SellingPrice',
            ];

            // Capture old state before any writes (for audit log)
            $oldData = $isEdit ? $this->pricelists_model->getForEdit($orgUID, $plUID) : null;

            $this->dbwrite_model->startTransaction();

            if ($isEdit) {
                $this->pricelists_model->updateHeader($plUID, $orgUID, $userUID, $headerData);
                $this->pricelists_model->softDeleteChildRecords($plUID, $orgUID);
            } else {
                $plUID = $this->pricelists_model->createHeader($orgUID, $userUID, $headerData);
            }

            if ($assignedTo !== 'All' && !empty($assignments)) {
                $refType = ($assignedTo === 'Groups') ? 'Group' : 'Customer';
                $this->pricelists_model->saveAssignments($plUID, $orgUID, $refType, $assignments);
            }

            if ($scope === 'All' && !empty($discounts)) {
                $this->pricelists_model->saveDiscounts($plUID, $orgUID, $discounts);
            }

            if ($scope === 'Specific' && !empty($rules)) {
                $this->pricelists_model->saveRules($plUID, $orgUID, $rules);
            }

            $this->dbwrite_model->commitTransaction();
            $this->_upsertOnePriceListCache($orgUID, $plUID);

            $newValues = [
                'Header'      => $headerData,
                'Assignments' => $assignments,
                'Discounts'   => $discounts,
                'Rules'       => $rules,
            ];
            $oldValues = $oldData ? $this->_priceListToAuditArray($oldData) : [];
            $this->auditlog->log(
                $orgUID, $userUID,
                $isEdit ? 'UPDATE' : 'CREATE',
                'PriceList', $plUID, $name,
                [], $isEdit ? 'Price list updated: ' . $name : 'Price list created: ' . $name,
                'PriceList', 'MASTER', 'SUCCESS', '', 'WEB',
                $oldValues, $newValues
            );

            $pageNo    = max(1, (int) ($post['PageNo']   ?? 1));
            $limit     = (int) ($post['RowLimit'] ?? 10);
            $rawFilter = $post['Filter'] ?? '{}';
            $filter    = is_array($rawFilter) ? $rawFilter : (json_decode($rawFilter ?: '{}', true) ?? []);

            $this->EndReturnData->Error        = false;
            $this->EndReturnData->Message      = $isEdit ? 'Price list updated successfully.' : 'Price list created successfully.';
            $this->EndReturnData->PriceListUID = $plUID;
            $this->_appendPriceListRefresh($orgUID, $pageNo, $limit, $filter);

        } catch (InvalidArgumentException $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        } catch (Exception $e) {
            if (isset($this->dbwrite_model)) $this->dbwrite_model->rollbackTransaction();
            log_message('error', 'savePriceList: ' . $e->getMessage());
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = 'Failed to save price list. Please try again.';
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function deletePriceList(): void {
        $this->EndReturnData = new stdClass();
        try {
            $jwt          = $this->pageData['JwtData'];
            $orgUID       = (int) $jwt->Org->OrgUID;
            $userUID      = (int) $jwt->User->UserUID;
            $post         = $this->input->post(null, true);
            $priceListUID = (int) ($post['PriceListUID'] ?? 0);
            if ($priceListUID <= 0) throw new InvalidArgumentException('Invalid price list ID.');

            $this->load->model(['pricelists_model', 'dbwrite_model']);
            if (!$this->pricelists_model->getByUID($orgUID, $priceListUID))
                throw new Exception('Price list not found.');

            // Capture old state before soft-delete (for audit log)
            $oldData = $this->pricelists_model->getForEdit($orgUID, $priceListUID);

            $this->pricelists_model->softDelete($orgUID, $priceListUID, $userUID);
            $this->_removeOnePriceListCache($orgUID, $priceListUID);

            $oldValues = $oldData ? $this->_priceListToAuditArray($oldData) : [];
            $this->auditlog->log(
                $orgUID, $userUID,
                'DELETE',
                'PriceList', $priceListUID, $oldData->Name ?? '',
                [], 'Price list deleted: ' . ($oldData->Name ?? ''),
                'PriceList', 'MASTER', 'SUCCESS', '', 'WEB',
                $oldValues, []
            );

            $pageNo    = max(1, (int) ($post['PageNo']   ?? 1));
            $limit     = (int) ($post['RowLimit'] ?? 10);
            $rawFilter = $post['Filter'] ?? '{}';
            $filter    = is_array($rawFilter) ? $rawFilter : (json_decode($rawFilter ?: '{}', true) ?? []);

            $this->EndReturnData->Error   = false;
            $this->EndReturnData->Message = 'Price list deleted successfully.';
            $this->_appendPriceListRefresh($orgUID, $pageNo, $limit, $filter);

        } catch (InvalidArgumentException $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        } catch (Exception $e) {
            log_message('error', 'deletePriceList: ' . $e->getMessage());
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = 'Failed to delete price list. Please try again.';
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function deleteBulkPriceList(): void {
        $this->EndReturnData = new stdClass();
        try {
            $jwt      = $this->pageData['JwtData'];
            $orgUID   = (int) $jwt->Org->OrgUID;
            $userUID  = (int) $jwt->User->UserUID;
            $post     = $this->input->post(null, true);
            $rawUIDs  = $post['PriceListUIDs'] ?? '';
            $uidList  = array_filter(array_map('intval', explode(',', $rawUIDs)));
            if (empty($uidList)) throw new InvalidArgumentException('No price list IDs provided.');

            $this->load->model(['pricelists_model', 'dbwrite_model']);
            foreach ($uidList as $plUID) {
                $oldData = $this->pricelists_model->getForEdit($orgUID, $plUID);
                if (!$oldData) continue;
                $this->pricelists_model->softDelete($orgUID, $plUID, $userUID);
                $this->_removeOnePriceListCache($orgUID, $plUID);
                $oldValues = $this->_priceListToAuditArray($oldData);
                $this->auditlog->log(
                    $orgUID, $userUID,
                    'DELETE',
                    'PriceList', $plUID, $oldData->Name ?? '',
                    [], 'Price list deleted: ' . ($oldData->Name ?? ''),
                    'PriceList', 'MASTER', 'SUCCESS', '', 'WEB',
                    $oldValues, []
                );
            }

            $pageNo    = max(1, (int) ($post['PageNo']   ?? 1));
            $limit     = (int) ($post['RowLimit'] ?? 10);
            $rawFilter = $post['Filter'] ?? '{}';
            $filter    = is_array($rawFilter) ? $rawFilter : (json_decode($rawFilter ?: '{}', true) ?? []);

            $this->EndReturnData->Error   = false;
            $this->EndReturnData->Message = count($uidList) . ' price list(s) deleted successfully.';
            $this->_appendPriceListRefresh($orgUID, $pageNo, $limit, $filter);

        } catch (InvalidArgumentException $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        } catch (Exception $e) {
            log_message('error', 'deleteBulkPriceList: ' . $e->getMessage());
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = 'Failed to delete price lists. Please try again.';
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    /**
     * Upsert a single price list entry in orgKey('price-lists').
     * If the list is inactive or deleted, it is removed from cache instead.
     * Fire-and-forget — failure is logged, never surfaced to the user.
     */
    private function _upsertOnePriceListCache(int $orgUID, int $plUID): void {
        try {
            $this->load->model('pricelists_model');
            $entry    = $this->pricelists_model->getOneForCache($orgUID, $plUID);
            $cacheKey = $this->redisservice->orgKey('price-lists');
            $current  = $this->upstashservice->get($cacheKey) ?? [];
            if (!is_array($current)) $current = [];

            $current = array_values(array_filter($current, fn($pl) => (int)($pl['PriceListUID'] ?? 0) !== $plUID));

            if ($entry !== null) {
                $current[] = $entry;
                usort($current, fn($a, $b) => (int)($b['Priority'] ?? 0) <=> (int)($a['Priority'] ?? 0));
            }

            $this->upstashservice->set($cacheKey, $current, 0);
        } catch (Exception $e) {
            log_message('error', '_upsertOnePriceListCache: ' . $e->getMessage());
        }
    }

    /**
     * Remove a single price list entry from orgKey('price-lists').
     * Fire-and-forget — failure is logged, never surfaced to the user.
     */
    private function _removeOnePriceListCache(int $orgUID, int $plUID): void {
        try {
            $cacheKey = $this->redisservice->orgKey('price-lists');
            $current  = $this->upstashservice->get($cacheKey) ?? [];
            if (!is_array($current)) $current = [];

            $filtered = array_values(array_filter($current, fn($pl) => (int)($pl['PriceListUID'] ?? 0) !== $plUID));
            $this->upstashservice->set($cacheKey, $filtered, 0);
        } catch (Exception $e) {
            log_message('error', '_removeOnePriceListCache: ' . $e->getMessage());
        }
    }

    /**
     * Full rebuild of orgKey('price-lists') from DB.
     * Called by the sync button and as fallback.
     * Fire-and-forget — failure is logged, never surfaced to the user.
     */
    private function _syncPriceListCache(int $orgUID): void {
        try {
            $this->load->model('pricelists_model');
            $lists    = $this->pricelists_model->getAllForCache($orgUID);
            $cacheKey = $this->redisservice->orgKey('price-lists');
            $this->upstashservice->set($cacheKey, $lists, 0);
        } catch (Exception $e) {
            log_message('error', '_syncPriceListCache: ' . $e->getMessage());
        }
    }

    /**
     * Full resync endpoint — called by the globe button on the Price Lists tab.
     */
    public function syncPriceListCache(): void {
        $this->EndReturnData = new stdClass();
        try {
            $orgUID = (int)$this->pageData['JwtData']->Org->OrgUID;
            $this->load->model('pricelists_model');
            $lists    = $this->pricelists_model->getAllForCache($orgUID);
            $cacheKey = $this->redisservice->orgKey('price-lists');
            $this->upstashservice->set($cacheKey, $lists, 0);
            $this->EndReturnData->Error   = false;
            $this->EndReturnData->Lists   = $lists;
            $this->EndReturnData->Message = count($lists) . ' price list(s) synced to cache.';
        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    private function _priceListToAuditArray(?object $data): array {
        if (!$data) return [];
        return [
            'Name'           => $data->Name           ?? '',
            'Status'         => $data->Status          ?? '',
            'Priority'       => $data->Priority        ?? '',
            'ValidFrom'      => $data->ValidFrom       ?? '',
            'ValidTo'        => $data->ValidTo         ?? '',
            'Description'    => $data->Description     ?? '',
            'AssignedToType' => $data->AssignedToType  ?? '',
            'Scope'          => $data->Scope           ?? '',
            'GlobalBasedOn'  => $data->GlobalBasedOn   ?? '',
            'Assignments'    => array_map(function ($r) { return (array) $r; }, (array) ($data->Assignments ?? [])),
            'Discounts'      => array_map(function ($r) { return (array) $r; }, (array) ($data->Discounts   ?? [])),
            'Rules'          => array_map(function ($r) { return (array) $r; }, (array) ($data->Rules       ?? [])),
        ];
    }

}
