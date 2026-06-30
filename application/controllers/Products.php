<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Products extends MY_Controller {

    public $pageData = array();
    private $EndReturnData;

    public function __construct() {
        parent::__construct();

        $this->load->model(['products_model']);

    }

    private function sanitizeTabInput($tab) {

        $tab = strtolower($tab ?: 'item');
        $allowedTabs = ['item', 'group', 'category', 'size', 'brand'];
        return in_array($tab, $allowedTabs) ? $tab : 'item';

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

        $filter       = $this->input->post('Filter') ?: [];
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

    public function index() {

        if (!$this->_loadPageTitle()) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }

        try {

            $activeTab = $this->sanitizeTabInput($this->input->get('tab', TRUE));

            $limit = (int) ($GeneralSettings->RowLimit ?? 10);

            // Use dedicated paginated functions for active-tab row data
            $OrgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            $this->pageData['ProductTotalCount'] = 0;
            if ($activeTab === 'item') {
                $tableData = $this->products_model->getProductListPaginated($OrgUID, $limit, 0, 'Products.IsComposite = 0');
                $this->pageData['ModRowData'] = $this->load->view('products/items/list', [
                    'DataLists' => $tableData->rows,
                    'StartFrom' => 0,
                    'JwtData'   => $this->pageData['JwtData'],
                ], TRUE);
                $this->pageData['ModPagination'] = $this->globalservice->buildPagePaginationHtml('/products/getProductList', $tableData->totalCount, 1, $limit);
                $this->pageData['ProductTotalCount'] = $tableData->totalCount;
            } elseif ($activeTab === 'group') {
                $tableData = $this->products_model->getProductListPaginated($OrgUID, $limit, 0, 'Products.IsComposite = 1');
                $this->pageData['ModRowData'] = $this->load->view('products/items/list', [
                    'DataLists' => $tableData->rows,
                    'StartFrom' => 0,
                    'JwtData'   => $this->pageData['JwtData'],
                ], TRUE);
                $this->pageData['ModPagination'] = $this->globalservice->buildPagePaginationHtml('/products/getGroupList', $tableData->totalCount, 1, $limit);
            } elseif ($activeTab === 'category') {
                $tableData = $this->products_model->getCategoryListPaginated($OrgUID, $limit, 0);
                $this->pageData['ModRowData'] = $this->load->view('products/categories/list', [
                    'DataLists' => $tableData->rows,
                    'StartFrom' => 0,
                    'JwtData'   => $this->pageData['JwtData'],
                ], TRUE);
                $this->pageData['ModPagination'] = $this->globalservice->buildPagePaginationHtml('/products/getCategoryList', $tableData->totalCount, 1, $limit);
            } else {
                $this->pageData['ModRowData']    = '';
                $this->pageData['ModPagination'] = '';
            }

            $OrgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            
            $this->pageData['ActiveTabData']  = $activeTab;
            $this->pageData['ActiveTabName']  = ucfirst($activeTab);
            $this->pageData['ActiveModuleId'] = 4;

            $this->pageData['ProductStats'] = $this->products_model->getProductStats($OrgUID);

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

    private function saveCustomerTypePricing($ProductUID, $PostData) {

        $userUID = (int) $this->pageData['JwtData']->User->UserUID;
        $orgUID  = (int) $this->pageData['JwtData']->Org->OrgUID;

        $pricingJson = getPostValue($PostData, 'CustomerPricingData', '', '[]');
        $pricing = json_decode($pricingJson, true);

        if (!is_array($pricing)) return;

        
        $prvData = $this->products_model->getCustomerTypePricing($ProductUID);
        $existingRates = (count($prvData) > 0) ? array_column($prvData, 'RateUID') : [];

        $newRateUIDs = array_filter(array_column($pricing, 'RateUID'));
        $toDelete = array_diff($existingRates, $newRateUIDs);
        if (!empty($toDelete)) {
            foreach ($toDelete as $delRateUID) {
                if (is_numeric($delRateUID) && (int)$delRateUID > 0) {
                    $this->dbwrite_model->updateData('Products', 'ProductRateTbl', ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID], ['ProductUID' => (int) $ProductUID, 'OrgUID' => $orgUID, 'ProductRateUID' => $delRateUID]);
                }
            }
        }

        foreach ($pricing as $row) {
            $RateUID = (int) ($row['RateUID'] ?? 0);
            $ctUID = (int) ($row['CustomerTypeUID'] ?? 0);
            $price = (float) ($row['SellingPrice'] ?? 0);
            if ($ctUID > 0 && $price >= 0) {
                if ($RateUID > 0) {
                    $this->dbwrite_model->updateData('Products', 'ProductRateTbl',
                        [
                            'SellingPrice' => $price,
                            'UpdatedBy'    => $userUID
                        ],
                        ['ProductRateUID' => $RateUID, 'OrgUID' => $orgUID, 'ProductUID' => (int) $ProductUID]
                    );
                } else {
                    $this->dbwrite_model->insertData('Products', 'ProductRateTbl', [
                        'OrgUID'          => $orgUID,
                        'ProductUID'      => (int) $ProductUID,
                        'CustomerTypeUID' => $ctUID,
                        'SellingPrice'    => $price,
                        'CreatedBy'       => $userUID,
                        'UpdatedBy'       => $userUID,
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
            $filter = $this->input->post('Filter') ?: [];

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
            $filter = $this->input->post('Filter') ?: [];

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

            // Image Upload
            if(isset($_FILES['UploadImage'])) {
                $UploadResp = $this->globalservice->fileUploadService($_FILES['UploadImage'], 'products/items/images/', 'Image', ['Products', 'ProductTbl', array('ProductUID' => $ProductUID)]);
                if($UploadResp->Error === TRUE) {
                    throw new Exception($UploadResp->Message);
                }
            }

            // Customer type pricing
            $this->saveCustomerTypePricing($ProductUID, $PostData);

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
            $this->EndReturnData->ProductUID = $ProductUID;

            // Build product data object from POST + TaxDetails — no extra DB query needed
            $sellingPrice  = (float) getPostValue($PostData, 'SellingPrice', '', 0);
            $taxPercent    = (float) ($TaxDetails->Percentage ?? 0);
            $taxAmount     = round($sellingPrice * $taxPercent / 100, 2);
            $this->EndReturnData->Product = [
                'id'               => $ProductUID,
                'text'             => getPostValue($PostData, 'ItemName'),
                'itemName'         => getPostValue($PostData, 'ItemName'),
                'productType'      => getPostValue($PostData, 'ProductType', '', 'Product'),
                'unitPrice'        => $sellingPrice,
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

            if ($this->input->post('returnList') == 1) {
                $pageNo  = (int) $this->input->post('PageNo');
                $getResp = $this->fetchProductTableData($pageNo, 0, 0);
                $this->EndReturnData->List       = $getResp->RecordHtmlData;
                $this->EndReturnData->Pagination = $getResp->Pagination;
                $this->EndReturnData->Stats      = $this->fetchProductStats();
            } else if (getPostValue($PostData, 'getTableDetails') == 1) {
                // backward-compat for any callers still using getTableDetails
                $pageNo  = (int) $this->input->post('PageNo');
                $getResp = $this->fetchProductTableData($pageNo, 0, 0);
                $this->EndReturnData->List       = $getResp->RecordHtmlData;
                $this->EndReturnData->Pagination = $getResp->Pagination;
                $this->EndReturnData->Stats      = $this->fetchProductStats();
            }

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
                $this->EndReturnData->Error           = FALSE;
                $this->EndReturnData->Message         = 'Retrieved Successfully';
                $this->EndReturnData->Data            = (object)$cached['Data'];
                $this->EndReturnData->CustomerPricing = $cached['CustomerPricing'] ?? [];
                $this->EndReturnData->RentalConfig    = isset($cached['RentalConfig']) ? (object)$cached['RentalConfig'] : null;
                $this->EndReturnData->Attachments     = $attachments;
            } else {
                $this->load->model('products_model');
                $GetProductData = $this->products_model->getProductsDetails(['Products.ProductUID' => $ProductUID]);
                if (count($GetProductData) != 1) {
                    throw new Exception('Product not found');
                }
                $customerPricing = $this->products_model->getCustomerTypePricing($ProductUID);
                $rentalConfig    = $this->products_model->getRentalConfig($ProductUID, $orgUID);

                $this->EndReturnData->Error           = FALSE;
                $this->EndReturnData->Message         = 'Retrieved Successfully';
                $this->EndReturnData->Data            = $GetProductData[0];
                $this->EndReturnData->CustomerPricing = $customerPricing;
                $this->EndReturnData->RentalConfig    = $rentalConfig;
                $this->EndReturnData->Attachments     = $attachments;

                // Populate cache for next request
                $this->upstashservice->set($cacheKey, [
                    'Data'            => $GetProductData[0],
                    'CustomerPricing' => $customerPricing,
                    'RentalConfig'    => $rentalConfig,
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
            if (!empty($PostData['ImageRemoved'])) $prodFormData['Image'] = NULL;

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

            // Image Upload
            if(isset($_FILES['UploadImage'])) {
                $UploadResp = $this->globalservice->fileUploadService($_FILES['UploadImage'], 'products/items/images/', 'Image', ['Products', 'ProductTbl', array('ProductUID' => $ProductUID)]);
                if($UploadResp->Error === TRUE) {
                    throw new Exception($UploadResp->Message);
                }
            }

            // Customer type pricing
            $this->saveCustomerTypePricing($ProductUID, $PostData);

            // Rental configuration (only when IsRentable = 1)
            $this->_saveRentalConfig($ProductUID, $PostData);

            $this->dbwrite_model->commitTransaction();

            // Sync updated product into the Upstash bulk cache
            $this->cachehelper->upsertProduct($ProductUID);

            $pageNo  = (int) $this->input->post('PageNo');
            $getResp = $this->fetchProductTableData($pageNo, 0, 0); // updateProductData — always items

            // Handle attachment uploads + deletes (same request, after commit)
            $this->_handleAttachments('Product', $ProductUID, (int)$this->pageData['JwtData']->Org->OrgUID, (int)$this->pageData['JwtData']->User->UserUID, 'ProdAttachFiles', 'ProdAttachDeleteUIDs');

            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Message    = 'Updated Successfully';
            $this->EndReturnData->ProductUID = $ProductUID;
            $this->EndReturnData->List       = $getResp->RecordHtmlData;
            $this->EndReturnData->Pagination = $getResp->Pagination;
            $this->EndReturnData->Stats      = $this->fetchProductStats();

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

            $this->load->model('dbwrite_model');
            $UpdateResp = $this->dbwrite_model->updateData('Products', 'ProductTbl', $this->globalservice->baseDeleteArrayDetails(), array('ProductUID' => $ProductUID));
            if($UpdateResp->Error) {
                throw new Exception($UpdateResp->Message);
            }

            // Remove deleted product from the Upstash bulk cache
            $this->cachehelper->removeProduct($ProductUID);

            $pageNo  = (int) $this->input->post('PageNo') ?: 1;
            $getResp = $this->fetchProductTableData($pageNo);

            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Message    = 'Deleted Successfully';
            $this->EndReturnData->List       = $getResp->RecordHtmlData;
            $this->EndReturnData->Pagination = $getResp->Pagination;
            $this->EndReturnData->Stats      = $this->fetchProductStats();

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function deleteBulkProduct() {

        $this->EndReturnData = new stdClass();
		try {

            $ProductUIDs = $this->input->post('ProductUIDs[]');
            if(empty($ProductUIDs)) {
                throw new Exception('No products selected for deletion');
            }

            // Validate and sanitize IDs
            if (!is_array($ProductUIDs)) {
                $ProductUIDs = [$ProductUIDs];
            }
            $ProductUIDs = array_map('intval', $ProductUIDs);
            $ProductUIDs = array_filter($ProductUIDs, function($id) {
                return $id > 0;
            });

            if (empty($ProductUIDs)) {
                throw new Exception('Invalid product IDs provided');
            }

            // Check if any product has transactions
            // foreach ($ProductUIDs as $productId) {
            //     if ($this->productHasTransactions($productId)) {
            //         throw new Exception("Product ID {$productId} has existing transactions");
            //     }
            // }
            
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

            $pageNo  = (int) $this->input->post('PageNo') ?: 1;
            $getResp = $this->fetchProductTableData($pageNo);

            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Message    = count($ProductUIDs) . ' product(s) deleted successfully';
            $this->EndReturnData->List       = $getResp->RecordHtmlData;
            $this->EndReturnData->Pagination = $getResp->Pagination;
            $this->EndReturnData->Stats      = $this->fetchProductStats();

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    // ──────────────────────────────────────────────────────────
    // COMBO / COMPOSITE PRODUCT METHODS
    // ──────────────────────────────────────────────────────────

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

            $comboName  = trim(getPostValue($PostData, 'ComboName'));
            $comboPrice = (float) getPostValue($PostData, 'ComboSellingPrice', '', 0);

            if (empty($comboName)) {
                throw new InvalidArgumentException('Combo name is required.');
            }
            if ($comboPrice < 0) {
                throw new InvalidArgumentException('Selling price must be 0 or greater.');
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

            
            $Tax_Percentage = isset($TaxDetails->Percentage) ? (float) $TaxDetails->Percentage : 0;            
            $Unit_Price = $comboPrice;
            if ($Tax_Percentage > 0) {
                $Unit_Price = $comboPrice / (1 + ($Tax_Percentage / 100));
            }

            $primaryUnitUID = (int) getPostValue($PostData, 'ComboPrimaryUnit') ?: null;

            $comboData = [
                'OrgUID'        => $orgUID,
                'ItemName'      => $comboName,
                'ProductType'   => 'Product',
                'UnitPrice'     => round($Unit_Price, 5),
                'SellingPrice'  => $comboPrice,
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

            $comboName  = trim(getPostValue($PostData, 'ComboName'));
            $comboPrice = (float) getPostValue($PostData, 'ComboSellingPrice', '', 0);

            if (empty($comboName)) {
                throw new InvalidArgumentException('Combo name is required.');
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

            $primaryUnitUID = (int) getPostValue($PostData, 'ComboPrimaryUnit') ?: null;

            $comboData = [
                'ItemName'      => $comboName,
                'SellingPrice'  => $comboPrice,
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

            $pageNo  = (int) $this->input->post('PageNo') ?: 1;
            $getResp = $this->fetchProductTableData($pageNo, 0, 1); // deleteComboItem — always groups

            $this->EndReturnData->Error      = false;
            $this->EndReturnData->Message    = 'Combo item deleted successfully';
            $this->EndReturnData->List       = $getResp->RecordHtmlData;
            $this->EndReturnData->Pagination = $getResp->Pagination;
            $this->EndReturnData->Stats      = $this->fetchProductStats();

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    // ──────────────────────────────────────────────────────────
    // END COMBO METHODS
    // ──────────────────────────────────────────────────────────

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

            $pageNo  = (int) $this->input->post('PageNo') ?: 1;
            $getResp = $this->fetchProductTableData($pageNo);

            $this->EndReturnData->Error      = false;
            $this->EndReturnData->Message    = 'Status updated successfully';
            $this->EndReturnData->List       = $getResp->RecordHtmlData;
            $this->EndReturnData->Pagination = $getResp->Pagination;
            $this->EndReturnData->TotalCount = $getResp->TotalCount;
            $this->EndReturnData->Stats      = $this->fetchProductStats();

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
            $filter = $this->input->post('Filter') ?: [];

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
            // All known fields and their Upstash keys
            $allKeyMap = [
                'primaryUnit' => $this->redisservice->orgKey('primary-unit'),
                'discType'    => $this->redisservice->orgKey('disc-type'),
                'prodType'    => $this->redisservice->orgKey('prod-type'),
                'prodTax'     => $this->redisservice->orgKey('prod-tax'),
                'taxDetails'  => $this->redisservice->orgKey('tax-details'),
                'categories'  => $this->redisservice->orgKey('categories'),
            ];

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
            // 'categories' is a Redis hash → HGETALL; all others are strings → GET
            if (!empty($fieldNames)) {
                $cmds = array_map(function ($field, $key) {
                    return $field === 'categories' ? ['HGETALL', $key] : ['GET', $key];
                }, $fieldNames, array_values($keys));
                $pipeResults = $this->upstashservice->pipeline($cmds);

                if (!empty($pipeResults)) {
                    $missingFields = [];
                    foreach ($pipeResults as $i => $result) {
                        $field = $fieldNames[$i];
                        $raw   = $result['result'] ?? null;

                        if ($field === 'categories') {
                            // HGETALL → flat [uid, jsonStr, uid, jsonStr, ...] array
                            if (is_array($raw) && count($raw) >= 2) {
                                $cats = [];
                                for ($j = 0; $j + 1 < count($raw); $j += 2) {
                                    $decoded = json_decode($raw[$j + 1], true);
                                    if ($decoded) $cats[] = (object)$decoded;
                                }
                                if (!empty($cats)) { $data[$field] = $cats; continue; }
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
                    }
                }
                // If pipeResults is empty → Upstash disabled/error → missingFields = all requested
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
                    }
                }
            }

            $this->EndReturnData->Error = false;
            $this->EndReturnData->Data  = $data;

        } catch (Exception $e) {
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

            // Image Upload
            if(isset($_FILES['UploadImage'])) {
                $UploadResp = $this->globalservice->fileUploadService($_FILES['UploadImage'], 'products/category/images/', 'Image', ['Products', 'CategoryTbl', array('CategoryUID' => $CategoryUID)]);
                if($UploadResp->Error === TRUE) {
                    throw new Exception($UploadResp->Message);
                }
            }

            $this->dbwrite_model->commitTransaction();

            // Handle attachment uploads + deletes (same request, after commit)
            $orgUID  = (int)$this->pageData['JwtData']->Org->OrgUID;
            $userUID = (int)$this->pageData['JwtData']->User->UserUID;
            $this->_handleAttachments('Category', $CategoryUID, $orgUID, $userUID, 'CatgAttachFiles', 'CatgAttachDeleteUIDs');

            // Cache update must be after commit so ReadDB can see the new row
            $this->cachehelper->upsertCategory($CategoryUID);

            $this->EndReturnData->Error        = FALSE;
            $this->EndReturnData->Message      = 'Created Successfully';
            $this->EndReturnData->InsertId     = $CategoryUID;
            $this->EndReturnData->CategoryName = getPostValue($PostData, 'CategoryName');

            if ($this->input->post('returnList') == 1) {
                $pageNo  = (int) $this->input->post('PageNo');
                $getResp = $this->fetchCategoryTableData($pageNo);
                $this->EndReturnData->List       = $getResp->RecordHtmlData;
                $this->EndReturnData->Pagination = $getResp->Pagination;
            }

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

            $catgFormData = $this->buildCategoryFormData($PostData, false);
            if(isset($PostData['RemovedImage']) && $PostData['RemovedImage'] == TRUE) {
                $catgFormData['Image'] = NULL;
            }

            $UpdateDataResp = $this->dbwrite_model->updateData('Products', 'CategoryTbl', $catgFormData, array('CategoryUID' => $CategoryUID));
            if($UpdateDataResp->Error) {
                throw new Exception($UpdateDataResp->Message);
            }

            // Image Upload
            if(isset($_FILES['UploadImage'])) {
                $UploadResp = $this->globalservice->fileUploadService($_FILES['UploadImage'], 'products/category/images/', 'Image', ['Products', 'CategoryTbl', array('CategoryUID' => $CategoryUID)]);
                if($UploadResp->Error === TRUE) {
                    throw new Exception($UploadResp->Message);
                }
            }

            $this->dbwrite_model->commitTransaction();

            // Handle attachment uploads + deletes (same request, after commit)
            $orgUID  = (int)$this->pageData['JwtData']->Org->OrgUID;
            $userUID = (int)$this->pageData['JwtData']->User->UserUID;
            $this->_handleAttachments('Category', $CategoryUID, $orgUID, $userUID, 'CatgAttachFiles', 'CatgAttachDeleteUIDs');

            // Sync updated category into the Upstash bulk cache
            $this->cachehelper->upsertCategory($CategoryUID);

            $pageNo  = (int) $this->input->post('PageNo');
            $getResp = $this->fetchCategoryTableData($pageNo);

            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Message    = 'Updated Successfully';
            $this->EndReturnData->List       = $getResp->RecordHtmlData;
            $this->EndReturnData->Pagination = $getResp->Pagination;

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
            
            $this->load->model('dbwrite_model');
            $UpdateResp = $this->dbwrite_model->updateData('Products', 'CategoryTbl', $this->globalservice->baseDeleteArrayDetails(), array('CategoryUID' => $CategoryUID));
            if($UpdateResp->Error) {
                throw new Exception($UpdateResp->Message);
            }

            // Remove deleted category from the Upstash bulk cache
            $this->cachehelper->removeCategory($CategoryUID);

            $pageNo  = (int) $this->input->post('PageNo') ?: 1;
            $getResp = $this->fetchCategoryTableData($pageNo);

            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Message    = 'Deleted Successfully';
            $this->EndReturnData->List       = $getResp->RecordHtmlData;
            $this->EndReturnData->Pagination = $getResp->Pagination;

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

            $pageNo  = (int) $this->input->post('PageNo') ?: 1;
            $getResp = $this->fetchCategoryTableData($pageNo);

            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Message    = 'Deleted Successfully';
            $this->EndReturnData->List       = $getResp->RecordHtmlData;
            $this->EndReturnData->Pagination = $getResp->Pagination;

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    /** Sizes Details Starts Here */
    private function buildSizeFormData($postData, $isCreate = false) {
        $data = [
            'Name'        => getPostValue($postData, 'SizesName'),
            'OrgUID'      => (int) $this->pageData['JwtData']->Org->OrgUID,
            'Description' => getPostValue($postData, 'SizesDescription'),
            'UpdatedBy'   => (int) $this->pageData['JwtData']->User->UserUID,
        ];
        if ($isCreate) {
            $data['CreatedBy'] = $this->pageData['JwtData']->User->UserUID;
        }
        return $data;
    }

    /** Size List (AJAX pagination) */
    public function getSizeList() {

        $this->EndReturnData = new stdClass();
        try {

            $result = $this->getModuleListData('Sizes');
            if ($result->Error) {
                throw new Exception($result->Message);
            }

            $this->EndReturnData->Error      = false;
            $this->EndReturnData->List       = $result->RecordHtmlData;
            $this->EndReturnData->Pagination = $result->Pagination;
            $this->EndReturnData->UIDs       = $result->UIDs ?? [];

        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function addSizeDetails() {

        $this->EndReturnData = new stdClass();
        $ErrorInForm = '';
		try {

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData = $this->input->post();

            $this->load->model('formvalidation_model');
            $ErrorInForm = $this->formvalidation_model->sizesValidateForm($PostData);
            if (!empty($ErrorInForm)) {
                throw new InvalidArgumentException('VALIDATION_ERROR');
            }

            $insDataResp = $this->dbwrite_model->insertData('Products', 'SizeTbl', $this->buildSizeFormData($PostData, true));
            if($insDataResp->Error) {
                throw new Exception($insDataResp->Message);
            }

            $this->dbwrite_model->commitTransaction();

            $pageNo = $this->input->post('PageNo');
            $getResp = $this->globalservice->baseTableDataPaginationDetails($pageNo);

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Created Successfully';
            $this->EndReturnData->List = $getResp->RecordHtmlData;
            $this->EndReturnData->Pagination = $getResp->Pagination;
            $this->EndReturnData->InsertId = $insDataResp->ID;

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

    public function retrieveSizeDetails() {

        $this->EndReturnData = new stdClass();
		try {

            $SizeUID = (int) $this->input->post('SizeUID');
            if (!$SizeUID || $SizeUID <= 0) {
                throw new Exception('Invalid Size ID');
            }

            $this->load->model('products_model');
            $GetSizeData = $this->products_model->getSizeDetails(['Size.SizeUID' => $SizeUID]);
            if(count($GetSizeData) != 1) {
                throw new Exception('Size not found');
            }
            
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Retrieved Successfully';
            $this->EndReturnData->Data = $GetSizeData[0];

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        
        $this->globalservice->sendJsonResponse($this->EndReturnData);

	}

    public function updateSizeDetails() {

        $this->EndReturnData = new stdClass();
        $ErrorInForm = '';
		try {

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData = $this->input->post();

            $this->load->model('formvalidation_model');
            $ErrorInForm = $this->formvalidation_model->sizesValidateForm($PostData);
            if (!empty($ErrorInForm)) {
                throw new InvalidArgumentException('VALIDATION_ERROR');
            }

            $SizeUID = (int) getPostValue($PostData, 'SizeUID');

            $UpdateDataResp = $this->dbwrite_model->updateData('Products', 'SizeTbl', $this->buildSizeFormData($PostData, false), array('SizeUID' => $SizeUID));
            if($UpdateDataResp->Error) {
                throw new Exception($UpdateDataResp->Message);
            }

            $this->dbwrite_model->commitTransaction();

            $pageNo = (int) $this->input->post('PageNo');
            $getResp = $this->globalservice->baseTableDataPaginationDetails($pageNo);

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Updated Successfully';
            $this->EndReturnData->List = $getResp->RecordHtmlData;
            $this->EndReturnData->Pagination = $getResp->Pagination;

            $this->load->model('products_model');
            $this->EndReturnData->SizeList = $this->products_model->getSizeDetails([]);

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

    public function deleteSizeDetails() {

        $this->EndReturnData = new stdClass();
		try {

            $SizeUID = (int) $this->input->post('SizeUID');
            if(!$SizeUID) {
                throw new Exception('Invalid Size ID');
            }

            $this->load->model('products_model');
            $ExistsInProducts = $this->products_model->getProductsDetails([], '', ['Products.SizeUID' => $SizeUID]);
            if (!empty($ExistsInProducts) && count($ExistsInProducts) > 0) {
                throw new Exception('Size is linked to Product(s). Cannot delete.');
            }

            $this->load->model('dbwrite_model');
            $UpdateResp = $this->dbwrite_model->updateData('Products', 'SizeTbl', $this->globalservice->baseDeleteArrayDetails(), array('SizeUID' => $SizeUID));
            if($UpdateResp->Error) {
                throw new Exception($UpdateResp->Message);
            }

            $pageNo = $this->input->post('PageNo');
            $getResp = $this->globalservice->baseTableDataPaginationDetails($pageNo);

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Deleted Successfully';
            $this->EndReturnData->List = $getResp->RecordHtmlData;
            $this->EndReturnData->Pagination = $getResp->Pagination;

            $this->load->model('products_model');
            $this->EndReturnData->SizeList = $this->products_model->getSizeDetails([]);

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        
        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function deleteBulkSize() {

        $this->EndReturnData = new stdClass();
		try {

            $SizeUIDs = $this->input->post('SizeUIDs[]');
            if (empty($SizeUIDs)) {
                throw new Exception('No sizes selected for deletion');
            }

            // Validate and sanitize IDs
            if (!is_array($SizeUIDs)) {
                $SizeUIDs = [$SizeUIDs];
            }
            $SizeUIDs = array_map('intval', $SizeUIDs);
            $SizeUIDs = array_filter($SizeUIDs, function($id) {
                return $id > 0;
            });

            if (empty($SizeUIDs)) {
                throw new Exception('Invalid size IDs provided');
            }

            /** Cross Check with Products */
            $this->load->model('products_model');
            $ExistsInProducts = $this->products_model->getProductsDetails([], '', ['Products.SizeUID' => $SizeUIDs]);
            if (!empty($ExistsInProducts) && count($ExistsInProducts) > 0) {
                throw new Exception('One or more sizes are linked to Product(s). Cannot delete.');
            }

            $this->load->model('dbwrite_model');
            $UpdateResp = $this->dbwrite_model->updateData('Products', 'SizeTbl', $this->globalservice->baseDeleteArrayDetails(), [], ['SizeUID' => $SizeUIDs]);
            if($UpdateResp->Error) {
                throw new Exception($UpdateResp->Message);
            }

            $pageNo = (int) $this->input->post('PageNo');
            $getResp = $this->globalservice->baseTableDataPaginationDetails($pageNo);

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Deleted Successfully';
            $this->EndReturnData->List = $getResp->RecordHtmlData;
            $this->EndReturnData->Pagination = $getResp->Pagination;

            $this->load->model('products_model');
            $this->EndReturnData->SizeList = $this->products_model->getSizeDetails([]);

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        
        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    /** Brands Details Starts Here */
    private function buildBrandFormData($postData, $isCreate = false) {
        $data = [
            'Name'        => getPostValue($postData, 'BrandsName'),
            'OrgUID'      => (int) $this->pageData['JwtData']->Org->OrgUID,
            'Description' => getPostValue($postData, 'BrandsDescription'),
            'UpdatedBy'   => (int) $this->pageData['JwtData']->User->UserUID,
        ];
        if ($isCreate) {
            $data['CreatedBy'] = (int) $this->pageData['JwtData']->User->UserUID;
        }
        return $data;
    }

    /** Brand List (AJAX pagination) */
    public function getBrandList() {

        $this->EndReturnData = new stdClass();
        try {

            $result = $this->getModuleListData('Brands');
            if ($result->Error) {
                throw new Exception($result->Message);
            }

            $this->EndReturnData->Error      = false;
            $this->EndReturnData->List       = $result->RecordHtmlData;
            $this->EndReturnData->Pagination = $result->Pagination;
            $this->EndReturnData->UIDs       = $result->UIDs ?? [];

        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function addBrandDetails() {

        $this->EndReturnData = new stdClass();
        $ErrorInForm = '';
		try {

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData = $this->input->post();

            $this->load->model('formvalidation_model');
            $ErrorInForm = $this->formvalidation_model->brandsValidateForm($PostData);
            if(!empty($ErrorInForm)) {
                throw new InvalidArgumentException('VALIDATION_ERROR');
            }

            $brandFormData = $this->buildBrandFormData($PostData, true);

            $insDataResp = $this->dbwrite_model->insertData('Products', 'BrandTbl', $brandFormData);
            if($insDataResp->Error) {
                throw new Exception($insDataResp->Message);
            }

            $this->dbwrite_model->commitTransaction();

            $pageNo = $this->input->post('PageNo');
            $getResp = $this->globalservice->baseTableDataPaginationDetails($pageNo);

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Created Successfully';
            $this->EndReturnData->List = $getResp->RecordHtmlData;
            $this->EndReturnData->Pagination = $getResp->Pagination;
            $this->EndReturnData->InsertId = $insDataResp->ID;

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

    public function retrieveBrandDetails() {

        $this->EndReturnData = new stdClass();
		try {

            $BrandUID = (int) $this->input->post('BrandUID');
            if (!$BrandUID || $BrandUID <= 0) {
                throw new Exception('Invalid Brand ID');
            }

            $this->load->model('products_model');
            $GetBrandData = $this->products_model->getBrandDetails(['Brand.BrandUID' => $BrandUID]);
            if (count($GetBrandData) != 1) {
                throw new Exception('Brand not found');
            }

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Retrieved Successfully';
            $this->EndReturnData->Data = $GetBrandData[0];

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        
        $this->globalservice->sendJsonResponse($this->EndReturnData);

	}

    public function updateBrandDetails() {

        $this->EndReturnData = new stdClass();
        $ErrorInForm = '';
		try {

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData = $this->input->post();

            $this->load->model('formvalidation_model');
            $ErrorInForm = $this->formvalidation_model->brandsValidateForm($PostData);
            if(!empty($ErrorInForm)) {
                throw new InvalidArgumentException('VALIDATION_ERROR');
            }

            $BrandUID = (int) getPostValue($PostData, 'BrandUID');

            $UpdateDataResp = $this->dbwrite_model->updateData('Products', 'BrandTbl', $this->buildBrandFormData($PostData, false), array('BrandUID' => $BrandUID));
            if($UpdateDataResp->Error) {
                throw new Exception($UpdateDataResp->Message);
            }

            $this->dbwrite_model->commitTransaction();

            $pageNo = $this->input->post('PageNo');
            $getResp = $this->globalservice->baseTableDataPaginationDetails($pageNo);

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Updated Successfully';
            $this->EndReturnData->List = $getResp->RecordHtmlData;
            $this->EndReturnData->Pagination = $getResp->Pagination;

            $this->load->model('products_model');
            $this->EndReturnData->BrandList = $this->products_model->getBrandDetails([]);

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

    public function deleteBrandDetails() {

        $this->EndReturnData = new stdClass();
		try {

            $BrandUID = (int) $this->input->post('BrandUID');
            if (!$BrandUID || $BrandUID <= 0) {
                throw new Exception('Invalid Brand ID');
            }

            // Check if brand is linked to products
            $this->load->model('products_model');
            $ExistsInProducts = $this->products_model->getProductsDetails([], '', ['Products.BrandUID' => $BrandUID]);
            if (!empty($ExistsInProducts) && count($ExistsInProducts) > 0) {
                throw new Exception('Brand is linked to Product(s). Cannot delete.');
            }

            $UpdateResp = $this->dbwrite_model->updateData('Products', 'BrandTbl', $this->globalservice->baseDeleteArrayDetails(), array('BrandUID' => $BrandUID));
            if($UpdateResp->Error) {
                throw new Exception($UpdateResp->Message);
            }

            $pageNo = (int) $this->input->post('PageNo');
            $getResp = $this->globalservice->baseTableDataPaginationDetails($pageNo);

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Deleted Successfully';
            $this->EndReturnData->List = $getResp->RecordHtmlData;
            $this->EndReturnData->Pagination = $getResp->Pagination;

            $this->load->model('products_model');
            $this->EndReturnData->BrandList = $this->products_model->getBrandDetails([]);

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        
        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function deleteBulkBrand() {

        $this->EndReturnData = new stdClass();
		try {

            $BrandUIDs = $this->input->post('BrandUIDs[]');
            if (empty($BrandUIDs)) {
                throw new Exception('No brands selected for deletion');
            }

            // Validate and sanitize IDs
            if (!is_array($BrandUIDs)) {
                $BrandUIDs = [$BrandUIDs];
            }
            $BrandUIDs = array_map('intval', $BrandUIDs);
            $BrandUIDs = array_filter($BrandUIDs, function($id) {
                return $id > 0;
            });

            if (empty($BrandUIDs)) {
                throw new Exception('Invalid brand IDs provided');
            }

            /** Cross Check with Products */
            $this->load->model('products_model');
            $ExistsInProducts = $this->products_model->getProductsDetails([], '', ['Products.BrandUID' => $BrandUIDs]);
            if (!empty($ExistsInProducts) && count($ExistsInProducts) > 0) {
                throw new Exception('One or more brands are linked to Product(s). Cannot delete.');
            }

            $this->load->model('dbwrite_model');
            $UpdateResp = $this->dbwrite_model->updateData('Products', 'BrandTbl', $this->globalservice->baseDeleteArrayDetails(), [], array('BrandUID' => $BrandUIDs));
            if($UpdateResp->Error) {
                throw new Exception($UpdateResp->Message);
            }

            $pageNo = (int) $this->input->post('PageNo');
            $getResp = $this->globalservice->baseTableDataPaginationDetails($pageNo);

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Deleted Successfully';
            $this->EndReturnData->List = $getResp->RecordHtmlData;
            $this->EndReturnData->Pagination = $getResp->Pagination;

            $this->load->model('products_model');
            $this->EndReturnData->BrandList = $this->products_model->getBrandDetails([]);

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
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

            $cacheKey = $this->redisservice->orgKey('products');
            $this->upstashservice->del($cacheKey);
            $newMap = [];

            foreach ($products as $prod) {
                $isComposite = (int)($prod->IsComposite ?? 0);
                $notForSale  = (int)($prod->NotForSale  ?? 0);
                if (!$isComposite && $notForSale) continue;

                $uid = (int) $prod->ProductUID;
                $newMap[(string)$uid] = [
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
                    'IsComposite'                 => (int)($prod->IsComposite          ?? 0),
                    'IsSerialTracked'             => (int)($prod->IsSerialTracked      ?? 0),
                    'Image'                       => $prod->Image                      ?? '',
                ];
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
                    'Image'       => $cat->Image       ?? '',
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

    // ── Product / Category Attachments ────────────────────────────────────────

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

        $maxFiles   = $entityType === 'Category' ? 3 : 5;
        $maxTotalMB = $entityType === 'Category' ? 3 : 5;
        $allowed    = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $folder     = $entityType === 'Category'
            ? 'categories/attachments/' . $entityUID
            : 'products/attachments/'   . $entityUID;

        // Use WriteDb directly — avoids ReadDb replication lag
        $wdb = $this->dbwrite_model->getWriteDb();
        $wdb->db_debug = FALSE;

        // 1. Auto-migrate legacy Image field into the attachment table (once per entity)
        //    Prevents the old single image from being silently replaced on first new upload.
        $legacyTbl   = $entityType === 'Product' ? 'ProductTbl'  : 'CategoryTbl';
        $legacyPkCol = $entityType === 'Product' ? 'ProductUID'  : 'CategoryUID';
        $existCountQ = $wdb->query(
            "SELECT COUNT(*) AS cnt FROM Products.ProductCategoryAttachmentsTbl
              WHERE EntityType = ? AND EntityUID = ? AND OrgUID = ? AND IsDeleted = 0",
            [$entityType, $entityUID, $orgUID]
        );
        $existCount = $existCountQ ? (int)($existCountQ->row()->cnt ?? 0) : 0;

        if ($existCount === 0) {
            // Check if entity has a legacy Image value not yet migrated
            $legacyQ = $wdb->query(
                "SELECT Image FROM Products.{$legacyTbl} WHERE {$legacyPkCol} = ? AND OrgUID = ? AND IsDeleted = 0 LIMIT 1",
                [$entityUID, $orgUID]
            );
            $legacyRow = $legacyQ ? $legacyQ->row() : null;
            if ($legacyRow && !empty($legacyRow->Image)) {
                $wdb->insert('Products.ProductCategoryAttachmentsTbl', [
                    'OrgUID'     => $orgUID,
                    'EntityType' => $entityType,
                    'EntityUID'  => $entityUID,
                    'FileName'   => basename($legacyRow->Image),
                    'FilePath'   => $legacyRow->Image,
                    'FileSize'   => 0,
                    'SortOrder'  => 1,
                    'IsDeleted'  => 0,
                    'IsActive'   => 1,
                    'CreatedBy'  => $userUID,
                    'UpdatedBy'  => $userUID,
                ]);
                $existCount = 1;
            }
        }

        // 2. Process pending deletions
        $deleteRaw = $this->input->post($deleteKey) ?: '';
        if ($deleteRaw) {
            foreach (array_filter(array_map('intval', explode(',', $deleteRaw))) as $attachUID) {
                $wdb->query(
                    "UPDATE Products.ProductCategoryAttachmentsTbl SET IsDeleted=1, IsActive=0, UpdatedBy=? WHERE AttachUID=? AND OrgUID=? AND IsDeleted=0",
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
               FROM Products.ProductCategoryAttachmentsTbl
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

            $wdb->insert('Products.ProductCategoryAttachmentsTbl', [
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

            if (!in_array($entityType, ['Product', 'Category'])) throw new Exception('Invalid entity type.');
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
            $maxFiles     = $entityType === 'Category' ? 3 : 5;
            $maxTotalMB   = $entityType === 'Category' ? 3 : 5;

            if (!in_array($entityType, ['Product', 'Category'])) throw new Exception('Invalid entity type.');
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

            $folder    = $entityType === 'Category' ? 'categories/attachments/' . $entityUID : 'products/attachments/' . $entityUID;
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

                $resp = $this->dbwrite_model->insertData('Products', 'ProductCategoryAttachmentsTbl', [
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
            $resp = $this->dbwrite_model->updateData('Products', 'ProductCategoryAttachmentsTbl',
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
            } else {
                $this->dbwrite_model->updateData('Products', 'CategoryTbl',
                    ['Image' => $primary, 'UpdatedBy' => $userUID],
                    ['CategoryUID' => $entityUID, 'OrgUID' => $orgUID]
                );
            }
        } catch (Exception $e) {
            log_message('error', '_syncPrimaryImage failed: ' . $e->getMessage());
        }
    }

}
