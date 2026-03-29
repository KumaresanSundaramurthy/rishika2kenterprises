<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Products extends CI_Controller {

    public $pageData = array();
    private $EndReturnData;

    public function __construct() {
        parent::__construct();

        $this->load->model(['global_model', 'products_model']);

    }

    private function sanitizeTabInput($tab) {

        $tab = strtolower($tab ?: 'item');
        $allowedTabs = ['item', 'category', 'size', 'brand'];
        return in_array($tab, $allowedTabs) ? $tab : 'item';

    }

    private function getModuleInfo($controllerName) {

        $getModuleInfo = $this->redis_cache->get('Redis_UserModuleInfo')->Value ?? [];
        $ModuleInfo = array_values(array_filter($getModuleInfo, fn($m) => $m->ControllerName === $controllerName));
        
        if (empty($ModuleInfo)) {
            throw new Exception("Module information not found for controller: {$controllerName}");
        }
        
        return $ModuleInfo;

    }

    private function loadModuleIds($ModuleInfo, $tabModules) {
        foreach ($tabModules as $key => $modName) {
            $this->pageData[ucfirst($key) . 'ModuleId'] = getModuleUIDByName($ModuleInfo, $modName);
        }
    }

    private function getModuleListData($moduleName) {

        $ModuleInfo = $this->getModuleInfo(strtolower($this->router->fetch_class()));
        $ModuleId   = getModuleUIDByName($ModuleInfo, $moduleName);

        if (!$ModuleId) {
            throw new Exception("Module not configured: {$moduleName}");
        }

        $pageNo = (int) $this->input->post('PageNo') ?: 1;
        $limit  = (int) $this->input->post('RowLimit') ?: 10;
        $offset = ($pageNo - 1) * $limit;
        $filter = $this->input->post('Filter') ?: [];

        return $this->globalservice->getBaseMainPageTablePagination($ModuleId, $pageNo, $limit, $offset, $filter, [], 'Ajax');

    }

    private function buildPagePaginationHtml($pageUrl, $totalCount, $pageNo, $limit) {

        $config['base_url']        = '/products/' . $pageUrl;
        $config['use_page_numbers'] = TRUE;
        $config['total_rows']      = $totalCount;
        $config['per_page']        = $limit;
        $config['cur_page']        = (int) $pageNo;
        $config['result_count']    = pageResultCount($pageNo, $limit, $totalCount);

        $this->load->library('pagination');
        $this->pagination->initialize($config);

        return $this->pagination->create_links();

        // if ($totalCount <= 0 || $limit <= 0) return '';
        // $totalPages = (int) ceil($totalCount / $limit);
        // $from = (($pageNo - 1) * $limit) + 1;
        // $to   = min($pageNo * $limit, $totalCount);

        // $html = '<div class="col-12 col-sm-6 d-flex align-items-center text-muted small">'
        //       . 'Showing <strong class="mx-1">' . $from . '</strong> - <strong class="mx-1">' . $to . '</strong>'
        //       . ' of <strong class="mx-1">' . $totalCount . '</strong></div>'
        //       . '<div class="col-12 col-sm-6 d-flex justify-content-sm-end mt-2 mt-sm-0">';

        // if ($totalPages > 1) {
        //     $html .= '<nav><ul class="pagination pagination-sm mb-0">';
        //     $html .= '<li class="page-item' . ($pageNo <= 1 ? ' disabled' : '') . '">'
        //            .   '<a class="page-link PaginationBtn" href="javascript:void(0);" data-page="' . max(1, $pageNo - 1) . '">&#8249;</a>'
        //            . '</li>';

        //     $start = max(1, $pageNo - 2);
        //     $end   = min($totalPages, $start + 4);
        //     $start = max(1, $end - 4);
        //     for ($p = $start; $p <= $end; $p++) {
        //         $html .= '<li class="page-item' . ($p === $pageNo ? ' active' : '') . '">'
        //                .   '<a class="page-link PaginationBtn" href="javascript:void(0);" data-page="' . $p . '">' . $p . '</a>'
        //                . '</li>';
        //     }

        //     $html .= '<li class="page-item' . ($pageNo >= $totalPages ? ' disabled' : '') . '">'
        //            .   '<a class="page-link PaginationBtn" href="javascript:void(0);" data-page="' . min($totalPages, $pageNo + 1) . '">&#8250;</a>'
        //            . '</li>';
        //     $html .= '</ul></nav>';
        // }
        // $html .= '</div>';
        // return $html;

    }

    private function fetchProductTableData($pageNo, $limit = 0) {

        $OrgUID = (int) $this->pageData['JwtData']->User->OrgUID;
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

        $result  = $this->products_model->getProductListPaginated($OrgUID, $limit, $offset);
        $rowHtml = $this->load->view('products/items/list', [
            'DataLists' => $result->rows,
            'StartFrom' => $offset,
            'JwtData'   => $this->pageData['JwtData'],
        ], TRUE);

        $resp                 = new stdClass();
        $resp->RecordHtmlData = $rowHtml;
        $resp->Pagination     = $this->buildPagePaginationHtml('getProductList', $result->totalCount, $pageNo, $limit);
        $resp->TotalCount     = $result->totalCount;
        return $resp;

    }

    private function fetchCategoryTableData($pageNo, $limit = 0) {

        $OrgUID = (int) $this->pageData['JwtData']->User->OrgUID;
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
        $resp->Pagination     = $this->buildPagePaginationHtml('getCategoryList', $result->totalCount, $pageNo, $limit);
        return $resp;

    }

    public function index() {

        try {

            $activeTab = $this->sanitizeTabInput($this->input->get('tab', TRUE));
            $GeneralSettings = $this->redis_cache->get('Redis_UserGenSettings')->Value ?? NULL;
            $this->pageData['JwtData']->GenSettings = $GeneralSettings;

            $limit = (int) ($GeneralSettings->RowLimit ?? 10);

            // Use dedicated paginated functions for active-tab row data
            $OrgUID = (int) $this->pageData['JwtData']->User->OrgUID;
            if ($activeTab === 'item') {
                $tableData = $this->products_model->getProductListPaginated($OrgUID, $limit, 0);
                $this->pageData['ModRowData'] = $this->load->view('products/items/list', [
                    'DataLists' => $tableData->rows,
                    'StartFrom' => 0,
                    'JwtData'   => $this->pageData['JwtData'],
                ], TRUE);
                $this->pageData['ModPagination'] = $this->buildPagePaginationHtml('getProductList', $tableData->totalCount, 1, $limit);
            } elseif ($activeTab === 'category') {
                $tableData = $this->products_model->getCategoryListPaginated($OrgUID, $limit, 0);
                $this->pageData['ModRowData'] = $this->load->view('products/categories/list', [
                    'DataLists' => $tableData->rows,
                    'StartFrom' => 0,
                    'JwtData'   => $this->pageData['JwtData'],
                ], TRUE);
                $this->pageData['ModPagination'] = $this->buildPagePaginationHtml('getCategoryList', $tableData->totalCount, 1, $limit);
            } else {
                $this->pageData['ModRowData']    = '';
                $this->pageData['ModPagination'] = '';
            }

            $this->pageData['PrimaryUnitInfo'] = $this->global_model->getPrimaryUnitInfo()->Data ?? [];
            $this->pageData['DiscTypeInfo']    = $this->global_model->getDiscountTypeInfo()->Data ?? [];
            $this->pageData['ProdTypeInfo']    = $this->global_model->getProductTypeInfo()->Data ?? [];
            $this->pageData['ProdTaxInfo']     = $this->global_model->getProductTaxInfo()->Data ?? [];
            $this->pageData['TaxDetInfo']      = $this->global_model->getTaxDetailsInfo()->Data ?? [];

            $OrgUID = (int) $this->pageData['JwtData']->User->OrgUID;

            $this->load->model('customers_model');
            $this->pageData['CustomerTypeInfo'] = $this->customers_model->getCustomerTypeList($OrgUID) ?? [];
            
            $this->pageData['ActiveTabData']  = $activeTab;
            $this->pageData['ActiveTabName']  = ucfirst($activeTab);
            $this->pageData['ActiveModuleId'] = 4;

            $this->pageData['fltCategoryData'] = $this->products_model->getCategoriesDetails([]) ?? [];
            if (!empty($this->pageData['JwtData']->GenSettings->EnableStorage)) {
                $this->load->model('storage_model');
                $this->pageData['fltStorageData'] = $this->storage_model->getStorageDetails([]) ?? [];
            }
            
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
            'OrgUID'                     => (int) $this->pageData['JwtData']->User->OrgUID,
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
                        'OrgUID'           => (int) $this->pageData['JwtData']->User->OrgUID,
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
        $orgUID  = (int) $this->pageData['JwtData']->User->OrgUID;

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

    /** Product List (AJAX pagination) — dedicated query */
    public function getProductList() {

        $this->EndReturnData = new stdClass();
        try {

            $OrgUID = (int) $this->pageData['JwtData']->User->OrgUID;
            $pageNo = (int) $this->input->post('PageNo') ?: 1;
            $limit  = (int) $this->input->post('RowLimit') ?: 10;
            $offset = ($pageNo - 1) * $limit;
            $filter = $this->input->post('Filter') ?: [];

            $filterResult = $this->products_model->itemFilterFormation((object)['TableAliasName' => 'Products'], $filter);

            $result  = $this->products_model->getProductListPaginated($OrgUID, $limit, $offset, $filterResult->SearchDirectQuery, $filterResult->sortOperation);

            $GeneralSettings = $this->redis_cache->get('Redis_UserGenSettings')->Value ?? NULL;
            $this->pageData['JwtData']->GenSettings = $GeneralSettings;
            
            $rowHtml = $this->load->view('products/items/list', [
                'DataLists' => $result->rows,
                'StartFrom' => $offset,
                'JwtData'   => $this->pageData['JwtData'],
            ], TRUE);

            $this->EndReturnData->Error       = false;
            $this->EndReturnData->List        = $rowHtml;
            $this->EndReturnData->Pagination  = $this->buildPagePaginationHtml('getProductList', $result->totalCount, $pageNo, $limit);
            $this->EndReturnData->UIDs        = array_column($result->rows, 'ProductUID');
            $this->EndReturnData->TotalCount  = $result->totalCount;

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
            
            $GeneralSettings = ($this->redis_cache->get('Redis_UserGenSettings')->Value) ?? new stdClass();
            $this->pageData['JwtData']->GenSettings = $GeneralSettings;

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

            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Created Successfully';

            if(getPostValue($PostData, 'getTableDetails') == 1) {

                $pageNo  = (int) $this->input->post('PageNo');
                $getResp = $this->fetchProductTableData($pageNo);

                $this->EndReturnData->List       = $getResp->RecordHtmlData;
                $this->EndReturnData->Pagination = $getResp->Pagination;

            } else if(getPostValue($PostData, 'getTableDetails') == 0) {
                $this->EndReturnData->Info = $ProductUID;
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

            $this->load->model('products_model');
            $GetProductData = $this->products_model->getProductsDetails(['Products.ProductUID' => $ProductUID]);
            if(count($GetProductData) != 1) {
                throw new Exception('Product not found');
            }

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Retrieved Successfully';
            $this->EndReturnData->Data = $GetProductData[0];
            $this->EndReturnData->CustomerPricing = $this->products_model->getCustomerTypePricing($ProductUID);

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
            
            $GeneralSettings = ($this->redis_cache->get('Redis_UserGenSettings')->Value) ?? new stdClass();
            $this->pageData['JwtData']->GenSettings = $GeneralSettings;

            $PostData = $this->input->post();

            $this->load->model('formvalidation_model');
            $ErrorInForm = $this->formvalidation_model->productValidateForm($PostData);
            if(!empty($ErrorInForm)) {
                throw new InvalidArgumentException('VALIDATION_ERROR');
            }

            $this->load->model('global_model');
            $TaxDetails = $this->global_model->getTaxPercentageDetailsInfo(['TaxDetail.TaxDetailsUID' => $PostData['TaxPercentage']])->Data[0] ?? null;

            $ProductUID = (int) getPostValue($PostData, 'ProductUID');
            
            $prodFormData = $this->buildProductFormData($PostData, $TaxDetails, false);
            if (!empty($PostData['ImageRemoved'])) $prodFormData['Image'] = NULL;

            $UpdateDataResp = $this->dbwrite_model->updateData('Products', 'ProductTbl', $prodFormData, array('ProductUID' => $ProductUID));
            if($UpdateDataResp->Error) {
                throw new Exception($UpdateDataResp->Message);
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

            $this->dbwrite_model->commitTransaction();

            $pageNo  = (int) $this->input->post('PageNo');
            $getResp = $this->fetchProductTableData($pageNo);

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

            $pageNo  = (int) $this->input->post('PageNo') ?: 1;
            $getResp = $this->fetchProductTableData($pageNo);

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

            $pageNo  = (int) $this->input->post('PageNo') ?: 1;
            $getResp = $this->fetchProductTableData($pageNo);

            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Message    = count($ProductUIDs) . ' product(s) deleted successfully';
            $this->EndReturnData->List       = $getResp->RecordHtmlData;
            $this->EndReturnData->Pagination = $getResp->Pagination;

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
            $OrgUID = (int) $this->pageData['JwtData']->User->OrgUID;
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

            $GeneralSettings = ($this->redis_cache->get('Redis_UserGenSettings')->Value) ?? new stdClass();
            $this->pageData['JwtData']->GenSettings = $GeneralSettings;

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
            $orgUID  = (int) $this->pageData['JwtData']->User->OrgUID;

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

            $comboData = [
                'OrgUID'       => $orgUID,
                'ItemName'     => $comboName,
                'ProductType'  => 'Product',
                'UnitPrice'    => round($Unit_Price, 5),
                'SellingPrice' => $comboPrice,
                'MRP'          => (float) getPostValue($PostData, 'ComboMRP', '', 0),
                'TaxDetailsUID'=> $taxUID ?: null,
                'TaxPercentage'=> isset($TaxDetails->Percentage) ? $TaxDetails->Percentage : null,
                'CGST'         => isset($TaxDetails->CGST) ? $TaxDetails->CGST : null,
                'SGST'         => isset($TaxDetails->SGST) ? $TaxDetails->SGST : null,
                'IGST'         => isset($TaxDetails->IGST) ? $TaxDetails->IGST : null,
                'Description'  => getPostValue($PostData, 'ComboDescription'),
                'IsComposite'  => 1,
                'IsComboItem'  => 0,
                'CreatedBy'    => $userUID,
                'UpdatedBy'    => $userUID,
            ];

            $InsertResp = $this->dbwrite_model->insertData('Products', 'ProductTbl', $comboData);
            if ($InsertResp->Error) {
                throw new Exception($InsertResp->Message);
            }

            $this->saveProductBOM($InsertResp->ID, $PostData);
            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error   = false;
            $this->EndReturnData->Message = 'Combo item created successfully';

            if (getPostValue($PostData, 'getTableDetails') == 1) {
                $pageNo  = (int) $this->input->post('PageNo') ?: 1;
                $getResp = $this->fetchProductTableData($pageNo);
                $this->EndReturnData->List       = $getResp->RecordHtmlData;
                $this->EndReturnData->Pagination = $getResp->Pagination;
            }

        } catch (InvalidArgumentException $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
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

            $GeneralSettings = ($this->redis_cache->get('Redis_UserGenSettings')->Value) ?? new stdClass();
            $this->pageData['JwtData']->GenSettings = $GeneralSettings;

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

            $comboData = [
                'ItemName'     => $comboName,
                'SellingPrice' => $comboPrice,
                'MRP'          => (float) getPostValue($PostData, 'ComboMRP', '', 0),
                'TaxDetailsUID'=> $taxUID ?: null,
                'TaxPercentage'=> isset($TaxDetails->Percentage) ? $TaxDetails->Percentage : null,
                'CGST'         => isset($TaxDetails->CGST) ? $TaxDetails->CGST : null,
                'SGST'         => isset($TaxDetails->SGST) ? $TaxDetails->SGST : null,
                'IGST'         => isset($TaxDetails->IGST) ? $TaxDetails->IGST : null,
                'Description'  => getPostValue($PostData, 'ComboDescription'),
                'UpdatedBy'    => $userUID,
            ];

            $UpdateResp = $this->dbwrite_model->updateData('Products', 'ProductTbl', $comboData, ['ProductUID' => $comboUID]);
            if ($UpdateResp->Error) {
                throw new Exception($UpdateResp->Message);
            }

            $this->saveProductBOM($comboUID, $PostData);
            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error   = false;
            $this->EndReturnData->Message = 'Combo item updated successfully';

            if (getPostValue($PostData, 'getTableDetails') == 1) {
                $pageNo  = (int) $this->input->post('PageNo') ?: 1;
                $getResp = $this->fetchProductTableData($pageNo);
                $this->EndReturnData->List       = $getResp->RecordHtmlData;
                $this->EndReturnData->Pagination = $getResp->Pagination;
            }

        } catch (InvalidArgumentException $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
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

            $pageNo  = (int) $this->input->post('PageNo') ?: 1;
            $getResp = $this->fetchProductTableData($pageNo);

            $this->EndReturnData->Error      = false;
            $this->EndReturnData->Message    = 'Combo item deleted successfully';
            $this->EndReturnData->List       = $getResp->RecordHtmlData;
            $this->EndReturnData->Pagination = $getResp->Pagination;

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

    /** Category List (AJAX pagination) — dedicated query */
    public function getCategoryList() {

        $this->EndReturnData = new stdClass();
        try {

            $OrgUID = (int) $this->pageData['JwtData']->User->OrgUID;
            $pageNo = (int) $this->input->post('PageNo') ?: 1;
            $limit  = (int) $this->input->post('RowLimit') ?: 10;
            $offset = ($pageNo - 1) * $limit;
            $filter = $this->input->post('Filter') ?: [];

            $filterResult = $this->products_model->catgFilterFormation((object)['TableAliasName' => 'Category'], $filter);

            $result  = $this->products_model->getCategoryListPaginated($OrgUID, $limit, $offset, $filterResult->SearchDirectQuery, $filterResult->sortOperation);

            $GeneralSettings = $this->redis_cache->get('Redis_UserGenSettings')->Value ?? NULL;
            $this->pageData['JwtData']->GenSettings = $GeneralSettings;

            $rowHtml = $this->load->view('products/categories/list', [
                'DataLists' => $result->rows,
                'StartFrom' => $offset,
                'JwtData'   => $this->pageData['JwtData'],
            ], TRUE);

            $this->EndReturnData->Error      = false;
            $this->EndReturnData->List       = $rowHtml;
            $this->EndReturnData->Pagination = $this->buildPagePaginationHtml('getCategoryList', $result->totalCount, $pageNo, $limit);
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

    private function buildCategoryFormData($postData, $isCreate = false) {
        $data = [
            'Name'        => getPostValue($postData, 'CategoryName'),
            'OrgUID'      => (int) $this->pageData['JwtData']->User->OrgUID,
            'Description' => getPostValue($postData, 'CategoryDescription'),
            'UpdatedBy'   => $this->pageData['JwtData']->User->UserUID,
        ];
        if ($isCreate) {
            $data['CreatedBy'] = $this->pageData['JwtData']->User->UserUID;
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

            $pageNo  = (int) $this->input->post('PageNo');
            $getResp = $this->fetchCategoryTableData($pageNo);

            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Message    = 'Created Successfully';
            $this->EndReturnData->List       = $getResp->RecordHtmlData;
            $this->EndReturnData->Pagination = $getResp->Pagination;
            $this->EndReturnData->InsertId   = $insDataResp->ID;

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

            $this->load->model('products_model');
            $GetCatgData = $this->products_model->getCategoriesDetails(['Category.CategoryUID' => $CategoryUID]);
            if(count($GetCatgData) != 1) {
                throw new Exception('Category not found');
            }

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Retrieved Successfully';
            $this->EndReturnData->Data = $GetCatgData[0];

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
            'OrgUID'      => (int) $this->pageData['JwtData']->User->OrgUID,
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
            'OrgUID'      => (int) $this->pageData['JwtData']->User->OrgUID,
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

}