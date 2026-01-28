<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Products extends CI_Controller {

    public $pageData = array();
    private $EndReturnData;

    public function __construct() {
        parent::__construct();

        $this->load->model(['global_model', 'products_model']);

    }

    public function index() {

        try {

            $activeTab = strtolower($this->input->get('tab', TRUE) ?: 'item');
            $allowedTabs = ['item', 'category', 'size', 'brand'];
            if (!in_array($activeTab, $allowedTabs, true)) {
                $activeTab = 'item';
            }

            $controllerName = strtolower($this->router->fetch_class());
            $getModuleInfo = $this->redis_cache->get('Redis_UserModuleInfo')->Value ?? [];
            $ModuleInfo = array_values(array_filter($getModuleInfo, fn($m) => $m->ControllerName === $controllerName));
            if (empty($ModuleInfo)) {
                throw new Exception("Module information not found for controller: {$controllerName}");
            }

            $GeneralSettings = $this->redis_cache->get('Redis_UserGenSettings')->Value ?? NULL;
            $this->pageData['JwtData']->GenSettings = $GeneralSettings;
            $limit = (int) ($GeneralSettings->RowLimit ?? 10);
            $page = (int) ($this->input->get('page', TRUE) ?: 1);
            $offset = max(0, ($page-1)*$limit);

            $tabModules = [
                'item'     => 'Products',
                'category' => 'Category',
                'size'     => 'Sizes',
                'brand'    => 'Brands'
            ];
            
            foreach ($tabModules as $key=>$modName) {
                $this->pageData[ucfirst($key).'ModuleId'] = getModuleUIDByName($ModuleInfo, $modName);
            }
            $ModuleId = $this->pageData[ucfirst($activeTab).'ModuleId'] ?? null;
            if (!$ModuleId) {
                show_error('Module not configured for tab: '.$activeTab, 500);
                return;
            }

            $ReturnResponse = $this->globalservice->getBaseMainPageTablePagination($ModuleId, 0, $limit, 0, [], [], 'Index');
            if ($ReturnResponse->Error) {
                show_error($ReturnResponse->Message, 500);
                return;
            }
            
            $this->pageData['fltCategoryData'] = [];
            if (!empty($this->pageData['JwtData']->GenSettings->EnableStorage)) {
                $this->load->model('storage_model');
                $this->pageData['fltStorageData'] = [];
            }
            $filterColumns = array_values(array_filter($ReturnResponse->DispViewColumns, fn($col) => $col->MPFilterApplicable == 1));
            if($filterColumns) {
                foreach($filterColumns as $fltVal) {

                    if (!empty($fltVal->DbFieldName) && str_contains(strtolower((string)$fltVal->DbFieldName), 'category')) {
                        $this->pageData['fltCategoryData'] = $this->products_model->getCategoriesDetails([]) ?? [];
                    }
                    if (!empty($this->pageData['JwtData']->GenSettings->EnableStorage)) {
                        if (!empty($fltVal->DbFieldName) && str_contains(strtolower((string)$fltVal->DbFieldName), 'storage')) {
                            $this->pageData['fltStorageData'] = $this->storage_model->getStorageDetails([]) ?? [];
                        }
                    }

                }
            }
            
            $this->pageData['ModRowData'] = $ReturnResponse->RecordHtmlData;
            $this->pageData['ModPagination'] = $ReturnResponse->Pagination;
            $this->pageData['DispSettColumnDetails'] = $ReturnResponse->DispSettingsViewColumns;

            $ModuleColumns = array_filter($ReturnResponse->ViewAllColumns, fn($col) => $col->IsMainPageApplicable == 1);
            usort($ModuleColumns, fn($a,$b)=>$a->MainPageOrder <=> $b->MainPageOrder);

            $this->pageData['ItemColumns'] = ($activeTab === 'item') ? $ModuleColumns : $this->globalservice->getModuleViewColumnDetails($this->pageData['ItemModuleId'], 'IsMainPageApplicable', 'MainPageOrder');

            $this->pageData['CategoryColumns'] = ($activeTab === 'category') ? $ModuleColumns : $this->globalservice->getModuleViewColumnDetails($this->pageData['CategoryModuleId'], 'IsMainPageApplicable', 'MainPageOrder');

            $this->pageData['SizeColumns']     = ($activeTab === 'size') ? $ModuleColumns : $this->globalservice->getModuleViewColumnDetails($this->pageData['SizeModuleId'], 'IsMainPageApplicable', 'MainPageOrder');

            $this->pageData['BrandColumns']    = ($activeTab === 'brand') ? $ModuleColumns : $this->globalservice->getModuleViewColumnDetails($this->pageData['BrandModuleId'], 'IsMainPageApplicable', 'MainPageOrder');

            $this->pageData['PrimaryUnitInfo'] = $this->global_model->getPrimaryUnitInfo()->Data ?? [];
            $this->pageData['DiscTypeInfo']    = $this->global_model->getDiscountTypeInfo()->Data ?? [];
            $this->pageData['ProdTypeInfo']    = $this->global_model->getProductTypeInfo()->Data ?? [];
            $this->pageData['ProdTaxInfo']     = $this->global_model->getProductTaxInfo()->Data ?? [];
            $this->pageData['TaxDetInfo']      = $this->global_model->getTaxDetailsInfo()->Data ?? [];
            
            $this->pageData['SizeInfo']   = $this->products_model->getSizeDetails([]) ?? [];
            $this->pageData['BrandInfo']  = $this->products_model->getBrandDetails([]) ?? [];
            
            $this->pageData['ActiveTabData']  = $activeTab;
            $this->pageData['ActiveTabName']  = ucfirst($activeTab);
            $this->pageData['ActiveModuleId'] = $ModuleId;
            
            $this->load->view('products/view', $this->pageData);

        } catch (Exception $e) {
            redirect('dashboard', 'refresh');
        }

    }

    private function buildProductFormData($postData, $TaxDetails = null, $isCreate = false) {
        $data = [
            'OrgUID'                     => $this->pageData['JwtData']->User->OrgUID,
            'ItemName'                   => getPostValue($postData, 'ItemName'),
            'ProductType'                => getPostValue($postData, 'ProductType', '', 'Product'),
            'SellingPrice'               => getPostValue($postData, 'SellingPrice', '', 0),
            'SellingProductTaxUID'       => getPostValue($postData, 'SellingTaxOption'),
            'TaxDetailsUID'              => getPostValue($postData, 'TaxPercentage'),
            'TaxPercentage'              => isset($TaxDetails->Percentage) ? $TaxDetails->Percentage : null,
            'CGST'                       => isset($TaxDetails->CGST) ? $TaxDetails->CGST : null,
            'SGST'                       => isset($TaxDetails->SGST) ? $TaxDetails->SGST : null,
            'IGST'                       => isset($TaxDetails->IGST) ? $TaxDetails->IGST : null,
            'PrimaryUnitUID'             => getPostValue($postData, 'PrimaryUnit'),
            'CategoryUID'                => getPostValue($postData, 'Category'),
            'HSNSACCode'                 => getPostValue($postData, 'HSNCode'),
            'PurchasePrice'              => getPostValue($postData, 'PurchasePrice', '', 0),
            'PurchasePriceProductTaxUID' => getPostValue($postData, 'PurchaseTaxOption'),
            'PartNumber'                 => getPostValue($postData, 'PartNumber'),
            'Description'                => getPostValue($postData, 'Description'),
            'OpeningQuantity'            => ($postData['ProductType'] ?? 'Product') === 'Product' ? getPostValue($postData, 'OpeningQuantity', '', 0) : 0,
            'OpeningPurchasePrice'       => ($postData['ProductType'] ?? 'Product') === 'Product' ? getPostValue($postData, 'OpeningPurchasePrice', '', 0) : 0,
            'OpeningStockValue'          => ($postData['ProductType'] ?? 'Product') === 'Product' ? getPostValue($postData, 'OpeningStockValue', '', 0) : 0,
            'Discount'                   => getPostValue($postData, 'Discount', '', 0),
            'DiscountTypeUID'            => getPostValue($postData, 'DiscountOption', '', 0),
            'LowStockAlertAt'            => getPostValue($postData, 'LowStockAlert', '', 0),
            'NotForSale'                 => (!empty($postData['NotForSale']) && $postData['NotForSale'] == 1) ? 'Yes' : 'No',
            'BrandUID'                   => getPostValue($postData, 'BrandUID'),
            'Standard'                   => getPostValue($postData, 'Standard'),
            'Model'                      => getPostValue($postData, 'Model'),
            'IsSizeApplicable'           => (!empty($postData['IsSizeApplicable']) && $postData['IsSizeApplicable'] == 1) ? 1 : 0,
            'SizeUID'                    => (!empty($postData['IsSizeApplicable']) && $postData['IsSizeApplicable'] == 1) ? getPostValue($postData, 'SizeUID') : null,
            'UpdatedBy'                  => $this->pageData['JwtData']->User->UserUID,
            'UpdatedOn'                  => time(),
        ];
        if ($this->pageData['JwtData']->GenSettings->EnableStorage == 1) {
            $data['StorageUID'] = getPostValue($postData, 'StorageUID');
        }
        if ($isCreate) {
            $data['CreatedBy'] = $this->pageData['JwtData']->User->UserUID;
            $data['CreatedOn'] = time();
        }
        return $data;
    }


    public function addProductData() {

        $this->EndReturnData = new stdClass();
		try {
            
            $GeneralSettings = ($this->redis_cache->get('Redis_UserGenSettings')->Value) ?? new stdClass();
            $this->pageData['JwtData']->GenSettings = $GeneralSettings;

            $PostData = $this->input->post();
            $this->load->model('formvalidation_model');
            $ErrorInForm = $this->formvalidation_model->productValidateForm($PostData);
            if(!empty($ErrorInForm)) {
                throw new Exception($ErrorInForm);
            }

            $this->load->model('global_model');
            $TaxDetails = $this->global_model->getTaxPercentageDetailsInfo(['TaxDetail.TaxDetailsUID' => $PostData['TaxPercentage']])->Data[0];

            $prodFormData = $this->buildProductFormData($PostData, $TaxDetails, true);

            $this->load->model('dbwrite_model');
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

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Created Successfully';

            if(getPostValue($PostData, 'getTableDetails') == 1) {

                $pageNo = $this->input->post('PageNo');
                $getResp = $this->globalservice->baseTableDataPaginationDetails($pageNo);

                $this->EndReturnData->List = $getResp->RecordHtmlData;
                $this->EndReturnData->Pagination = $getResp->Pagination;

            } else if(getPostValue($PostData, 'getTableDetails') == 0) {
                $this->EndReturnData->Info = $ProductUID;
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function retrieveProductDetails() {

        $this->EndReturnData = new stdClass();
		try {

            $ProductUID = $this->input->post('ItemUID');
            if(!$ProductUID) {
                throw new Exception('Missing Product Information');
            }

            $this->load->model('products_model');
            $GetProductData = $this->products_model->getProductsDetails(['Products.ProductUID' => $ProductUID]);
            if(count($GetProductData) != 1) {
                throw new Exception('Something went wrong');
            }

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Retrieved Successfully';
            $this->EndReturnData->Data = $GetProductData[0];

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function updateProductData() {

        $this->EndReturnData = new stdClass();
		try {
            
            $GeneralSettings = ($this->redis_cache->get('Redis_UserGenSettings')->Value) ?? new stdClass();
            $this->pageData['JwtData']->GenSettings = $GeneralSettings;

            $PostData = $this->input->post();
            $this->load->model('formvalidation_model');
            $ErrorInForm = $this->formvalidation_model->productValidateForm($PostData);
            if(!empty($ErrorInForm)) {
                throw new Exception($ErrorInForm);
            }

            $this->load->model('global_model');
            $TaxDetails = $this->global_model->getTaxPercentageDetailsInfo(['TaxDetail.TaxDetailsUID' => $PostData['TaxPercentage']])->Data[0];

            $ProductUID = getPostValue($PostData, 'ProductUID');
            
            $prodFormData = $this->buildProductFormData($PostData, $TaxDetails, false);
            if (!empty($PostData['ImageRemoved'])) $prodFormData['Image'] = NULL;

            $this->load->model('dbwrite_model');
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

            $pageNo = $this->input->post('PageNo');
            $getResp = $this->globalservice->baseTableDataPaginationDetails($pageNo);
            
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Updated Successfully';
            $this->EndReturnData->List = $getResp->RecordHtmlData;
            $this->EndReturnData->Pagination = $getResp->Pagination;

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function deleteProductDetails() {

        $this->EndReturnData = new stdClass();
		try {

            $ProductUID = $this->input->post('ProductUID');
            if (!$ProductUID) {
                throw new Exception('Product Information is Missing to Delete');
            }

            $this->load->model('dbwrite_model');
            $UpdateResp = $this->dbwrite_model->updateData('Products', 'ProductTbl', $this->globalservice->baseDeleteArrayDetails(), array('ProductUID' => $ProductUID));
            if($UpdateResp->Error) {
                throw new Exception($UpdateResp->Message);
            }

            $pageNo = $this->input->post('PageNo') ? $this->input->post('PageNo') : 0;
            $getResp = $this->globalservice->baseTableDataPaginationDetails($pageNo);

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Deleted Successfully';
            $this->EndReturnData->List = $getResp->RecordHtmlData;
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
                throw new Exception('Product Information is Missing to Delete');
            }
            
            $this->load->model('dbwrite_model');
            $UpdateResp = $this->dbwrite_model->updateData('Products', 'ProductTbl', $this->globalservice->baseDeleteArrayDetails(), [], array('ProductUID' => $ProductUIDs));
            if($UpdateResp->Error) {
                throw new Exception($UpdateResp->Message);
            }

            $pageNo = $this->input->post('PageNo') ? $this->input->post('PageNo') : 0;
            $getResp = $this->globalservice->baseTableDataPaginationDetails($pageNo);

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Deleted Successfully';
            $this->EndReturnData->List = $getResp->RecordHtmlData;
            $this->EndReturnData->Pagination = $getResp->Pagination;

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    /** Categories Details Starts Here */
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
            $this->EndReturnData->Message = $e->getMessage();
        }
        
        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    private function buildCategoryFormData($postData, $isCreate = false) {
        $data = [
            'Name'        => getPostValue($postData, 'CategoryName'),
            'OrgUID'      => $this->pageData['JwtData']->User->OrgUID,
            'Description' => getPostValue($postData, 'CategoryDescription'),
            'UpdatedBy'   => $this->pageData['JwtData']->User->UserUID,
            'UpdatedOn'   => time(),
        ];
        if ($isCreate) {
            $data['CreatedBy'] = $this->pageData['JwtData']->User->UserUID;
            $data['CreatedOn'] = time();
        }
        return $data;
    }

    public function addCategoryDetails() {

        $this->EndReturnData = new stdClass();
		try {

            $PostData = $this->input->post();
            $this->load->model('formvalidation_model');
            $ErrorInForm = $this->formvalidation_model->categoryValidateForm($PostData);
            if(!empty($ErrorInForm)) {
                throw new Exception($ErrorInForm);
            }

            $catgFormData = $this->buildCategoryFormData($PostData, true);

            $this->load->model('dbwrite_model');
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
            
            $pageNo = $this->input->post('PageNo');
            $getResp = $this->globalservice->baseTableDataPaginationDetails($pageNo);

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Created Successfully';
            $this->EndReturnData->List = $getResp->RecordHtmlData;
            $this->EndReturnData->Pagination = $getResp->Pagination;
            $this->EndReturnData->InsertId = $insDataResp->ID;

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function retrieveCategoryDetails() {

        $this->EndReturnData = new stdClass();
		try {

            $CategoryUID = $this->input->post('CategoryUID');
            if(!$CategoryUID) {
                throw new Exception('Category UID is Missing');
            }

            $this->load->model('products_model');
            $GetCatgData = $this->products_model->getCategoriesDetails(['Category.CategoryUID' => $CategoryUID]);
            if(count($GetCatgData) != 1) {
                throw new Exception('Something went wrong. Please try again.!');
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
		try {

            $PostData = $this->input->post();
            $this->load->model('formvalidation_model');
            $ErrorInForm = $this->formvalidation_model->categoryValidateForm($PostData);
            if(!empty($ErrorInForm)) {
                throw new Exception($ErrorInForm);
            }

            $CategoryUID = getPostValue($PostData, 'CategoryUID');

            $catgFormData = $this->buildCategoryFormData($PostData, false);
            if(isset($PostData['RemovedImage']) && $PostData['RemovedImage'] == TRUE) {
                $catgFormData['Image'] = NULL;
            }

            $this->load->model('dbwrite_model');
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

            $pageNo = $this->input->post('PageNo');
            $getResp = $this->globalservice->baseTableDataPaginationDetails($pageNo);

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Updated Successfully';
            $this->EndReturnData->List = $getResp->RecordHtmlData;
            $this->EndReturnData->Pagination = $getResp->Pagination;

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function deleteCategoryDetails() {

        $this->EndReturnData = new stdClass();
		try {

            $CategoryUID = $this->input->post('CategoryUID');
            if(!$CategoryUID) {
                throw new Exception('Category Information is Missing to Delete');
            }
                
            $this->load->model('products_model');
            $ExistsInProducts = $this->products_model->getProductsDetails(['Category.CategoryUID' => $CategoryUID]);
            if(!empty($ExistsInProducts) && count($ExistsInProducts) > 0) {
                throw new Exception('Category is linked to Product.');
            }
            
            $this->load->model('dbwrite_model');
            $UpdateResp = $this->dbwrite_model->updateData('Products', 'CategoryTbl', $this->globalservice->baseDeleteArrayDetails(), array('CategoryUID' => $CategoryUID));
            if($UpdateResp->Error) {
                throw new Exception($UpdateResp->Message);
            }

            $pageNo = $this->input->post('PageNo');
            $getResp = $this->globalservice->baseTableDataPaginationDetails($pageNo);

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Deleted Successfully';
            $this->EndReturnData->List = $getResp->RecordHtmlData;
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
                throw new Exception('Category Information is Missing to Delete');
            }

            /** Cross Check with Products */
            $this->load->model('products_model');
            $ExistsInProducts = $this->products_model->getProductsDetails([], '', ['Products.CategoryUID' => $CategoryUIDs]);
            if(!empty($ExistsInProducts) && count($ExistsInProducts) > 0) {
                throw new Exception('Category is linked to Product.');
            }

            $this->load->model('dbwrite_model');
            $UpdateResp = $this->dbwrite_model->updateData('Products', 'CategoryTbl', $this->globalservice->baseDeleteArrayDetails(), [], array('CategoryUID' => $CategoryUIDs));
            if($UpdateResp->Error) {
                throw new Exception($UpdateResp->Message);
            }

            $pageNo = $this->input->post('PageNo');
            $getResp = $this->globalservice->baseTableDataPaginationDetails($pageNo);

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Deleted Successfully';
            $this->EndReturnData->List = $getResp->RecordHtmlData;
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
            'OrgUID'      => $this->pageData['JwtData']->User->OrgUID,
            'Description' => getPostValue($postData, 'SizesDescription'),
            'UpdatedBy'   => $this->pageData['JwtData']->User->UserUID,
            'UpdatedOn'   => time(),
        ];
        if ($isCreate) {
            $data['CreatedBy'] = $this->pageData['JwtData']->User->UserUID;
            $data['CreatedOn'] = time();
        }
        return $data;
    }

    public function addSizeDetails() {

        $this->EndReturnData = new stdClass();
		try {

            $PostData = $this->input->post();
            $this->load->model('formvalidation_model');
            $ErrorInForm = $this->formvalidation_model->sizesValidateForm($PostData);
            if(!empty($ErrorInForm)) {
                throw new Exception($ErrorInForm);
            }

            $this->load->model('dbwrite_model');
            $insDataResp = $this->dbwrite_model->insertData('Products', 'SizeTbl', $this->buildSizeFormData($PostData, true));
            if($insDataResp->Error) {
                throw new Exception($insDataResp->Message);
            }

            $pageNo = $this->input->post('PageNo');
            $getResp = $this->globalservice->baseTableDataPaginationDetails($pageNo);

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Created Successfully';
            $this->EndReturnData->List = $getResp->RecordHtmlData;
            $this->EndReturnData->Pagination = $getResp->Pagination;
            $this->EndReturnData->InsertId = $insDataResp->ID;

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        
        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function retrieveSizeDetails() {

        $this->EndReturnData = new stdClass();
		try {

            $SizeUID = $this->input->post('SizeUID');
            if(!$SizeUID) {
                throw new Exception('Size UID is Missing');
            }

            $this->load->model('products_model');
            $GetSizeData = $this->products_model->getSizeDetails(['Size.SizeUID' => $SizeUID]);
            if(count($GetSizeData) != 1) {
                throw new Exception('Something went wrong. Please try again.!');
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
		try {

            $PostData = $this->input->post();
            $this->load->model('formvalidation_model');
            $ErrorInForm = $this->formvalidation_model->sizesValidateForm($PostData);
            if(!empty($ErrorInForm)) {
                throw new Exception($ErrorInForm);
            }

            $SizeUID = getPostValue($PostData, 'SizeUID');

            $this->load->model('dbwrite_model');
            $UpdateDataResp = $this->dbwrite_model->updateData('Products', 'SizeTbl', $this->buildSizeFormData($PostData, false), array('SizeUID' => $SizeUID));
            if($UpdateDataResp->Error) {
                throw new Exception($UpdateDataResp->Message);
            }

            $pageNo = $this->input->post('PageNo');
            $getResp = $this->globalservice->baseTableDataPaginationDetails($pageNo);

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Updated Successfully';
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

    public function deleteSizeDetails() {

        $this->EndReturnData = new stdClass();
		try {

            $SizeUID = $this->input->post('SizeUID');
            if(!$SizeUID) {
                throw new Exception('Size Information is Missing to Delete');
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
            if(empty($SizeUIDs)) {
                throw new Exception('Size Information is Missing to Delete');
            }

            /** Cross Check with Products */
            $this->load->model('products_model');
            $ExistsInProducts = $this->products_model->getProductsDetails([], '', ['Products.SizeUID' => $SizeUIDs]);
            if(!empty($ExistsInProducts) && count($ExistsInProducts) > 0) {
                throw new Exception('Size is linked to Product.');
            }

            $this->load->model('dbwrite_model');
            $UpdateResp = $this->dbwrite_model->updateData('Products', 'SizeTbl', $this->globalservice->baseDeleteArrayDetails(), [], ['SizeUID' => $SizeUIDs]);
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

    /** Brands Details Starts Here */
    private function buildBrandFormData($postData, $isCreate = false) {
        $data = [
            'Name'        => getPostValue($postData, 'BrandsName'),
            'OrgUID'      => $this->pageData['JwtData']->User->OrgUID,
            'Description' => getPostValue($postData, 'BrandsDescription'),
            'UpdatedBy'   => $this->pageData['JwtData']->User->UserUID,
            'UpdatedOn'   => time(),
        ];
        if ($isCreate) {
            $data['CreatedBy'] = $this->pageData['JwtData']->User->UserUID;
            $data['CreatedOn'] = time();
        }
        return $data;
    }

    public function addBrandDetails() {

        $this->EndReturnData = new stdClass();
		try {

            $PostData = $this->input->post();
            $this->load->model('formvalidation_model');
            $ErrorInForm = $this->formvalidation_model->brandsValidateForm($PostData);
            if(!empty($ErrorInForm)) {
                throw new Exception($ErrorInForm);
            }

            $brandFormData = $this->buildBrandFormData($PostData, true);

            $this->load->model('dbwrite_model');
            $insDataResp = $this->dbwrite_model->insertData('Products', 'BrandTbl', $brandFormData);
            if($insDataResp->Error) {
                throw new Exception($insDataResp->Message);
            }

            $pageNo = $this->input->post('PageNo');
            $getResp = $this->globalservice->baseTableDataPaginationDetails($pageNo);

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Created Successfully';
            $this->EndReturnData->List = $getResp->RecordHtmlData;
            $this->EndReturnData->Pagination = $getResp->Pagination;
            $this->EndReturnData->InsertId = $insDataResp->ID;

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        
        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function retrieveBrandDetails() {

        $this->EndReturnData = new stdClass();
		try {

            $BrandUID = $this->input->post('BrandUID');
            if(!$BrandUID) {
                throw new Exception('Brand UID is Missing');
            }

            $this->load->model('products_model');
            $GetBrandData = $this->products_model->getBrandDetails(['Brand.BrandUID' => $BrandUID]);
            if(count($GetBrandData) != 1) {
                throw new Exception('Something went wrong. Please try again.!');
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
		try {

            $PostData = $this->input->post();
            $this->load->model('formvalidation_model');
            $ErrorInForm = $this->formvalidation_model->brandsValidateForm($PostData);
            if(!empty($ErrorInForm)) {
                throw new Exception($ErrorInForm);
            }

            $BrandUID = getPostValue($PostData, 'BrandUID');

            $this->load->model('dbwrite_model');
            $UpdateDataResp = $this->dbwrite_model->updateData('Products', 'BrandTbl', $this->buildBrandFormData($PostData, false), array('BrandUID' => $BrandUID));
            if($UpdateDataResp->Error) {
                throw new Exception($UpdateDataResp->Message);
            }

            $pageNo = $this->input->post('PageNo');
            $getResp = $this->globalservice->baseTableDataPaginationDetails($pageNo);

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Updated Successfully';
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

    public function deleteBrandDetails() {

        $this->EndReturnData = new stdClass();
		try {

            $BrandUID = $this->input->post('BrandUID');
            if(!$BrandUID) {
                throw new Exception('Size Information is Missing to Delete');
            }

            $this->load->model('dbwrite_model');
            $UpdateResp = $this->dbwrite_model->updateData('Products', 'BrandTbl', $this->globalservice->baseDeleteArrayDetails(), array('BrandUID' => $BrandUID));
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
            if(empty($BrandUIDs)) {
                throw new Exception('Brand Information is Missing to Delete');
            }

            /** Cross Check with Products */
            $this->load->model('products_model');
            $ExistsInProducts = $this->products_model->getProductsDetails([], '', ['Products.BrandUID' => $BrandUIDs]);
            if(!empty($ExistsInProducts) && count($ExistsInProducts) > 0) {
                throw new Exception('Brand is linked to Product.');
            }

            $this->load->model('dbwrite_model');
            $UpdateResp = $this->dbwrite_model->updateData('Products', 'BrandTbl', $this->globalservice->baseDeleteArrayDetails(), [], array('BrandUID' => $BrandUIDs));
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
            $this->EndReturnData->BrandList = $this->products_model->getBrandDetails([]);

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        
        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

}