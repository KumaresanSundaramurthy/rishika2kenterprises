<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Products extends CI_Controller {

    public $pageData = array();
    private $EndReturnData;

    public function __construct() {
        parent::__construct();

    }

    public function index() {

        // $this->output->enable_profiler(TRUE);

        // $this->benchmark->mark('model_start');

        $activeTab = $_GET['tab'] ?? 'item';

        $ControllerName = strtolower($this->router->fetch_class());
        $this->load->model(['global_model', 'products_model']);

        // Filter module info
        $this->pageData['ModuleInfo'] = array_filter($this->pageData['JwtData']->ModuleInfo, function ($module) use ($ControllerName) {
            return $module->ControllerName === $ControllerName;
        });

        $limit = $this->pageData['JwtData']->GenSettings->RowLimit ?? 10;

        $tabConfigs = [
            'item' => [
                'moduleName' => 'Products',
                'listPage' => 'products/items/list',
                'tableDetails' => '/products/getProductDetails/',
                'tabName' => 'Item'
            ],
            'category' => [
                'moduleName' => 'Category',
                'listPage' => 'products/categories/list',
                'tableDetails' => '/products/getCategoriesDetails/',
                'tabName' => 'Categories'
            ],
            'size' => [
                'moduleName' => 'Sizes',
                'listPage' => 'products/sizes/list',
                'tableDetails' => '/products/getSizesDetails/',
                'tabName' => 'Sizes'
            ],
            'brand' => [
                'moduleName' => 'Brands',
                'listPage' => 'products/brands/list',
                'tableDetails' => '/products/getBrandsDetails/',
                'tabName' => 'Brands'
            ]
        ];

        $activeTabConfig = $tabConfigs[$activeTab] ?? $tabConfigs['item'];

        $moduleName = $activeTabConfig['moduleName'];
        $ListPage = $activeTabConfig['listPage'];
        $TableDetails = $activeTabConfig['tableDetails'];
        $ActiveTabName = $activeTabConfig['tabName'];
        
        $modules = [
            'Item' => 'Products',
            'Category' => 'Category',
            'Size' => 'Sizes',
            'Brand' => 'Brands'
        ];

        foreach ($modules as $key => $modName) {
            $this->pageData[$key . 'ModuleId'] = getModuleUIDByName($this->pageData['ModuleInfo'], $modName);
        }

        $ModuleId = $this->pageData[ucfirst($activeTab) . 'ModuleId'];

        $ReturnResponse = $this->globalservice->getBaseMainPageTablePagination($ModuleId, $TableDetails, $ListPage, 0, $limit, 0, [], []);
        if ($ReturnResponse->Error) {
            throw new Exception($ReturnResponse->Message);
        }

        $this->pageData['ModActiveList'] = $ReturnResponse->List;
        $this->pageData['ModActiveUIDs'] = $ReturnResponse->UIDs;
        $this->pageData['ModActivePagination'] = $ReturnResponse->Pagination;
        $this->pageData['ColumnDetails'] = $ReturnResponse->AllViewColumns;

        // Initialize all tab column arrays
        foreach (array_keys($modules) as $key) {
            $this->pageData[$key . 'Columns'] = [];
        }

        foreach ($modules as $key => $modName) {
            $modId = $this->pageData[$key . 'ModuleId'];
            $colData = $this->global_model->getModuleViewColumnDetails(['ViewColmn.ModuleUID' => $modId, 'IsMainPageApplicable' => 1], true, ['MainPageOrder' => 'ASC']);
            if (strtolower($key) === $activeTab) {
                $ModuleColumns = array_filter($this->pageData['ColumnDetails'], fn($col) => $col->IsMainPageApplicable == 1);
                usort($ModuleColumns, fn($a, $b) => $a->MainPageOrder <=> $b->MainPageOrder);
                $this->pageData[$key . 'Columns'] = $ModuleColumns;
            } else {
                $this->pageData[$key . 'Columns'] = $colData;
            }
        }

        $this->pageData['ActiveTabData'] = $activeTab;
        $this->pageData['ActiveTabName'] = $ActiveTabName;
        $this->pageData['ActiveModuleId'] = $ModuleId;

        $this->pageData['PrimaryUnitInfo'] = [];
        $GetPrimaryUnitInfo = $this->global_model->getPrimaryUnitInfo();
        if (!$GetPrimaryUnitInfo->Error) {
            $this->pageData['PrimaryUnitInfo'] = $GetPrimaryUnitInfo->Data;
        }

        $this->pageData['DiscTypeInfo'] = [];
        $GetDiscTypeInfo = $this->global_model->getDiscountTypeInfo();
        if (!$GetDiscTypeInfo->Error) {
            $this->pageData['DiscTypeInfo'] = $GetDiscTypeInfo->Data;
        }

        $this->pageData['ProdTypeInfo'] = [];
        $GetProdTypeInfo = $this->global_model->getProductTypeInfo();
        if (!$GetProdTypeInfo->Error) {
            $this->pageData['ProdTypeInfo'] = $GetProdTypeInfo->Data;
        }

        $this->pageData['ProdTaxInfo'] = [];
        $GetProdTaxInfo = $this->global_model->getProductTaxInfo();
        if (!$GetProdTaxInfo->Error) {
            $this->pageData['ProdTaxInfo'] = $GetProdTaxInfo->Data;
        }

        $this->pageData['TaxDetInfo'] = [];
        $GetTaxDetInfo = $this->global_model->getTaxDetailsInfo();
        if (!$GetTaxDetInfo->Error) {
            $this->pageData['TaxDetInfo'] = $GetTaxDetInfo->Data;
        }

        $this->pageData['Categories'] = $this->products_model->getCategoriesDetails([]);
        $this->pageData['SizeInfo'] = $this->products_model->getSizeDetails([]);
        $this->pageData['BrandInfo'] = $this->products_model->getBrandDetails([]);

        if (!empty($this->pageData['JwtData']->GenSettings->EnableStorage)) {
            $this->load->model('storage_model');
            $this->pageData['Storage'] = $this->storage_model->getStorageDetails([]);
        }

        // $this->benchmark->mark('model_end');
        
        // $this->benchmark->mark('view_start');
        $this->load->view('products/view', $this->pageData);
        // $this->benchmark->mark('view_end');

        // echo 'Model time: ' . $this->benchmark->elapsed_time('model_start', 'model_end');
        // echo 'View time: ' . $this->benchmark->elapsed_time('view_start', 'view_end');

    }

    public function checkImageType($str = '') {
        return $this->globalservice->checkImageType($str);
    }

    public function getProductDetails($pageNo = 0) {

		$this->EndReturnData = new stdClass();
		try {

			$tablePagDataResp = $this->commonProductTablePagination($pageNo);

            $this->EndReturnData->Error = false;
            $this->EndReturnData->List = $tablePagDataResp->List;
			$this->EndReturnData->UIDs = $tablePagDataResp->UIDs;
            $this->EndReturnData->Pagination = $tablePagDataResp->Pagination;

		} catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		$this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;

	}

    public function commonProductTablePagination($pageNo = 0) {

        $ModuleId = $this->input->post('ModuleId');
        $limit = $this->input->post('RowLimit');
        $offset = ($pageNo != 0) ? (($pageNo - 1) * $limit) : $pageNo;
        $Filter = $this->input->post('Filter');

        $ReturnResponse = $this->globalservice->getBaseMainPageTablePagination($ModuleId, '/products/getProductDetails/', 'products/items/list', $pageNo, $limit, $offset, $Filter, []);
        if($ReturnResponse->Error) {
            throw new Exception($ReturnResponse->Message);
        }

        return $ReturnResponse;

    }

    public function addProductData() {

        $this->EndReturnData = new stdClass();
		try {

            $this->load->model('formvalidation_model');

            $PostData = $this->input->post();
            $ErrorInForm = $this->formvalidation_model->productValidateForm($PostData);
            if(empty($ErrorInForm)) {

                $this->load->model('global_model');
                $TaxDetails = $this->global_model->getTaxPercentageDetailsInfo(['TaxDetail.TaxDetailsUID' => $PostData['TaxPercentage']])->Data[0];

                $this->load->model('dbwrite_model');

                $ProductUID = 0;
                $ProductFormData = [
                    'ItemName' => $PostData['ItemName'],
                    'OrgUID' => $this->pageData['JwtData']->User->OrgUID,
                    'ProductType' => $PostData['ProductType'] ? $PostData['ProductType'] : 'Product',
                    'SellingPrice' => (isset($PostData['SellingPrice']) && !empty($PostData['SellingPrice'])) ? $PostData['SellingPrice'] : 0,
                    'SellingProductTaxUID' => (isset($PostData['SellingTaxOption']) && !empty($PostData['SellingTaxOption'])) ? $PostData['SellingTaxOption'] : NULL,
                    'TaxDetailsUID' => (isset($PostData['TaxPercentage']) && !empty($PostData['TaxPercentage'])) ? $PostData['TaxPercentage'] : NULL,
                    'TaxPercentage' => isset($TaxDetails->Percentage) ? $TaxDetails->Percentage : NULL,
                    'CGST' => isset($TaxDetails->CGST) ? $TaxDetails->CGST : NULL,
                    'SGST' => isset($TaxDetails->SGST) ? $TaxDetails->SGST : NULL,
                    'IGST' => isset($TaxDetails->IGST) ? $TaxDetails->IGST : NULL,
                    'PrimaryUnitUID' => (isset($PostData['PrimaryUnit']) && !empty($PostData['PrimaryUnit'])) ? $PostData['PrimaryUnit'] : NULL,
                    'CategoryUID' => (isset($PostData['Category']) && !empty($PostData['Category'])) ? $PostData['Category'] : NULL,
                    'HSNSACCode' => (isset($PostData['HSNCode']) && !empty($PostData['HSNCode'])) ? $PostData['HSNCode'] : NULL,
                    'PurchasePrice' => isset($PostData['PurchasePrice']) ? $PostData['PurchasePrice'] : 0,
                    'PurchasePriceProductTaxUID' => isset($PostData['PurchaseTaxOption']) ? $PostData['PurchaseTaxOption'] : NULL,
                    'PartNumber' => (isset($PostData['PartNumber']) && !empty($PostData['PartNumber'])) ? $PostData['PartNumber'] : NULL,
                    'Description' => (isset($PostData['Description']) && !empty($PostData['Description'])) ? $PostData['Description'] : NULL,
                    'OpeningQuantity' => $PostData['ProductType'] == 'Product' ? (isset($PostData['OpeningQuantity']) ? $PostData['OpeningQuantity'] : 0) : 0,
                    'OpeningPurchasePrice' => $PostData['ProductType'] == 'Product' ? (isset($PostData['OpeningPurchasePrice']) ? $PostData['OpeningPurchasePrice'] : 0) : 0,
                    'OpeningStockValue' => $PostData['ProductType'] == 'Product' ? (isset($PostData['OpeningStockValue']) ? $PostData['OpeningStockValue'] : 0) : 0,
                    'Discount' => isset($PostData['Discount']) ? $PostData['Discount'] : 0,
                    'DiscountTypeUID' => isset($PostData['DiscountOption']) ? $PostData['DiscountOption'] : 0,
                    'LowStockAlertAt' => isset($PostData['LowStockAlert']) ? $PostData['LowStockAlert'] : 0,
                    'NotForSale' => isset($PostData['NotForSale']) && $PostData['NotForSale'] == 1 ? 'Yes' : 'No',
                    'BrandUID' => isset($PostData['BrandUID']) & !empty($PostData['BrandUID']) ? $PostData['BrandUID'] : NULL,
                    'Standard' => (isset($PostData['Standard']) && !empty($PostData['Standard'])) ? $PostData['Standard'] : NULL,
                    'Model' => (isset($PostData['Model']) && !empty($PostData['Model'])) ? $PostData['Model'] : NULL,
                    'IsSizeApplicable' => (isset($PostData['IsSizeApplicable']) && $PostData['IsSizeApplicable'] == 1) ? 1 : 0,
                    'SizeUID' => (isset($PostData['IsSizeApplicable']) && $PostData['IsSizeApplicable'] == 1) && isset($PostData['SizeUID']) & !empty($PostData['SizeUID']) ? $PostData['SizeUID'] : NULL,
                    'CreatedBy' => $this->pageData['JwtData']->User->UserUID,
                    'UpdatedBy' => $this->pageData['JwtData']->User->UserUID,
                    'CreatedOn' => time(),
                    'UpdatedOn' => time(),
                ];

                if($this->pageData['JwtData']->GenSettings->EnableStorage == 1) {
                    $ProductFormData['StorageUID'] = isset($PostData['StorageUID']) ? $PostData['StorageUID'] : NULL;
                }

                $InsertDataResp = $this->dbwrite_model->insertData('Products', 'ProductTbl', $ProductFormData);
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

                $pageNo = $this->input->post('PageNo');
                $tablePagDataResp = $this->commonProductTablePagination($pageNo);
                
                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = 'Created Successfully';
                $this->EndReturnData->List = $tablePagDataResp->List;
                $this->EndReturnData->Pagination = $tablePagDataResp->Pagination;
                $this->EndReturnData->UIDs = $tablePagDataResp->UIDs;

            } else {
                throw new Exception($ErrorInForm);
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;

    }

    public function CheckSizeRequired($IsSizeApplicable) {

        $SizeUID = $this->input->post('SizeUID', true) ?? NULL;

        if ($IsSizeApplicable && empty($SizeUID)) {
            $this->form_validation->set_message('CheckSizeRequired', 'The Size field is required when Size Applicable is checked.');
            return false;
        }

        return true;

    }

    public function retrieveProductDetails() {

        $this->EndReturnData = new stdClass();
		try {

            $ProductUID = $this->input->post('ItemUID');
            if($ProductUID) {

                $this->load->model('products_model');
                $GetProductData = $this->products_model->getProductsDetails(['Products.ProductUID' => $ProductUID]);
                if((sizeof($GetProductData) > 0) && sizeof($GetProductData) == 1) {

                    $this->EndReturnData->Error = FALSE;
                    $this->EndReturnData->Message = 'Retrieved Successfully';
                    $this->EndReturnData->Data = $GetProductData[0];

                } else {
                    throw new Exception('Missing Product Information');
                }

            } else {
                throw new Exception('Missing Product Information');
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;

    }

    public function updateProductData() {

        $this->EndReturnData = new stdClass();
		try {

            $this->load->model('formvalidation_model');

            $PostData = $this->input->post();
            $ErrorInForm = $this->formvalidation_model->productValidateForm($PostData);
            if(empty($ErrorInForm)) {

                $this->load->model('global_model');
                $TaxDetails = $this->global_model->getTaxPercentageDetailsInfo(['TaxDetail.TaxDetailsUID' => $PostData['TaxPercentage']])->Data[0];

                $this->load->model('dbwrite_model');

                $ProductUID = (isset($PostData['ProductUID']) && !empty($PostData['ProductUID'])) ? $PostData['ProductUID'] : 0;

                if($ProductUID == 0) {
                    throw new Exception('Product edit information is missing.!');
                }

                $ProductFormData = [
                    'ItemName' => $PostData['ItemName'],
                    'ProductType' => $PostData['ProductType'] ? $PostData['ProductType'] : 'Product',
                    'SellingPrice' => (isset($PostData['SellingPrice']) && !empty($PostData['SellingPrice'])) ? $PostData['SellingPrice'] : 0,
                    'SellingProductTaxUID' => (isset($PostData['SellingTaxOption']) && !empty($PostData['SellingTaxOption'])) ? $PostData['SellingTaxOption'] : NULL,
                    'TaxDetailsUID' => (isset($PostData['TaxPercentage']) && !empty($PostData['TaxPercentage'])) ? $PostData['TaxPercentage'] : NULL,
                    'TaxPercentage' => isset($TaxDetails->Percentage) ? $TaxDetails->Percentage : NULL,
                    'CGST' => isset($TaxDetails->CGST) ? $TaxDetails->CGST : NULL,
                    'SGST' => isset($TaxDetails->SGST) ? $TaxDetails->SGST : NULL,
                    'IGST' => isset($TaxDetails->IGST) ? $TaxDetails->IGST : NULL,
                    'PrimaryUnitUID' => (isset($PostData['PrimaryUnit']) && !empty($PostData['PrimaryUnit'])) ? $PostData['PrimaryUnit'] : NULL,
                    'CategoryUID' => (isset($PostData['Category']) && !empty($PostData['Category'])) ? $PostData['Category'] : NULL,
                    'HSNSACCode' => (isset($PostData['HSNCode']) && !empty($PostData['HSNCode'])) ? $PostData['HSNCode'] : NULL,
                    'PurchasePrice' => isset($PostData['PurchasePrice']) ? $PostData['PurchasePrice'] : 0,
                    'PurchasePriceProductTaxUID' => isset($PostData['PurchaseTaxOption']) ? $PostData['PurchaseTaxOption'] : NULL,
                    'PartNumber' => (isset($PostData['PartNumber']) && !empty($PostData['PartNumber'])) ? $PostData['PartNumber'] : NULL,
                    'Description' => (isset($PostData['Description']) && !empty($PostData['Description'])) ? $PostData['Description'] : NULL,
                    'OpeningQuantity' => $PostData['ProductType'] == 'Product' ? (isset($PostData['OpeningQuantity']) ? $PostData['OpeningQuantity'] : 0) : 0,
                    'OpeningPurchasePrice' => $PostData['ProductType'] == 'Product' ? (isset($PostData['OpeningPurchasePrice']) ? $PostData['OpeningPurchasePrice'] : 0) : 0,
                    'OpeningStockValue' => $PostData['ProductType'] == 'Product' ? (isset($PostData['OpeningStockValue']) ? $PostData['OpeningStockValue'] : 0) : 0,
                    'Discount' => isset($PostData['Discount']) ? $PostData['Discount'] : 0,
                    'DiscountTypeUID' => isset($PostData['DiscountOption']) ? $PostData['DiscountOption'] : 0,
                    'LowStockAlertAt' => isset($PostData['LowStockAlert']) ? $PostData['LowStockAlert'] : 0,
                    'NotForSale' => isset($PostData['NotForSale']) && $PostData['NotForSale'] == 1 ? 'Yes' : 'No',
                    'BrandUID' => isset($PostData['BrandUID']) & !empty($PostData['BrandUID']) ? $PostData['BrandUID'] : NULL,
                    'Standard' => (isset($PostData['Standard']) && !empty($PostData['Standard'])) ? $PostData['Standard'] : NULL,
                    'Model' => (isset($PostData['Model']) && !empty($PostData['Model'])) ? $PostData['Model'] : NULL,
                    'IsSizeApplicable' => (isset($PostData['IsSizeApplicable']) && $PostData['IsSizeApplicable'] == 1) ? 1 : 0,
                    'SizeUID' => (isset($PostData['IsSizeApplicable']) && $PostData['IsSizeApplicable'] == 1) && isset($PostData['SizeUID']) & !empty($PostData['SizeUID']) ? $PostData['SizeUID'] : NULL,
                    'UpdatedBy' => $this->pageData['JwtData']->User->UserUID,
                    'UpdatedOn' => time(),
                ];
                if($this->pageData['JwtData']->GenSettings->EnableStorage == 1) {
                    $ProductFormData['StorageUID'] = isset($PostData['StorageUID']) ? $PostData['StorageUID'] : NULL;
                }

                $UpdateDataResp = $this->dbwrite_model->updateData('Products', 'ProductTbl', $ProductFormData, array('ProductUID' => $ProductUID));
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
                $tablePagDataResp = $this->commonProductTablePagination($pageNo);
                
                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = 'Updated Successfully';
                $this->EndReturnData->List = $tablePagDataResp->List;
                $this->EndReturnData->Pagination = $tablePagDataResp->Pagination;
                $this->EndReturnData->UIDs = $tablePagDataResp->UIDs;

            } else {
                throw new Exception($ErrorInForm);
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;

    }

    public function deleteProductDetails() {

        $this->EndReturnData = new stdClass();
		try {

            $ProductUID = $this->input->post('ProductUID');
            if($ProductUID) {

                $updateProdData = [
                    'IsDeleted' => 1,
                    'UpdatedBy' => $this->pageData['JwtData']->User->UserUID,
                    'UpdatedOn' => time(),
                ];

                $this->load->model('dbwrite_model');
                
                $UpdateResp = $this->dbwrite_model->updateData('Products', 'ProductTbl', $updateProdData, array('ProductUID' => $ProductUID));
                if($UpdateResp->Error) {
                    throw new Exception($UpdateResp->Message);
                }

                $limit = $this->input->post('RowLimit');
                $pageNo = $this->input->post('PageNo') ? $this->input->post('PageNo') : 0;
                $offset = ($pageNo != 0) ? (($pageNo - 1) * $limit) : $pageNo;
                $Filter = $this->input->post('Filter') ? $this->input->post('Filter') : [];
                $ModuleId = $this->input->post('ModuleId');

                $ReturnResponse = $this->globalservice->getBaseMainPageTablePagination($ModuleId, '/products/getProductDetails/', 'products/items/list', $pageNo, $limit, $offset, $Filter, []);
                if($ReturnResponse->Error) {
                    throw new Exception($ReturnResponse->Message);
                }

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = 'Deleted Successfully';
                $this->EndReturnData->List = $ReturnResponse->List;
                $this->EndReturnData->Pagination = $ReturnResponse->Pagination;
                $this->EndReturnData->UIDs = $ReturnResponse->UIDs;

            } else {
                throw new Exception('Product Information is Missing to Delete');
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		$this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;

    }

    public function deleteBulkProduct() {

        $this->EndReturnData = new stdClass();
		try {

            $ProductUIDs = $this->input->post('ProductUIDs');
            if($ProductUIDs) {

                $updateProdData = [
                    'IsDeleted' => 1,
                    'UpdatedBy' => $this->pageData['JwtData']->User->UserUID,
                    'UpdatedOn' => time(),
                ];

                $this->load->model('dbwrite_model');
                
                $UpdateResp = $this->dbwrite_model->updateData('Products', 'ProductTbl', $updateProdData, [], array('ProductUID' => $ProductUIDs));
                if($UpdateResp->Error) {
                    throw new Exception($UpdateResp->Message);
                }

                $limit = $this->input->post('RowLimit');
                $pageNo = $this->input->post('PageNo') ? $this->input->post('PageNo') : 0;
                $offset = ($pageNo != 0) ? (($pageNo - 1) * $limit) : $pageNo;
                $Filter = $this->input->post('Filter') ? $this->input->post('Filter') : [];
                $ModuleId = $this->input->post('ModuleId');

                $ReturnResponse = $this->globalservice->getBaseMainPageTablePagination($ModuleId, '/products/getProductDetails/', 'products/items/list', $pageNo, $limit, $offset, $Filter, []);
                if($ReturnResponse->Error) {
                    throw new Exception($ReturnResponse->Message);
                }

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = 'Deleted Successfully';
                $this->EndReturnData->List = $ReturnResponse->List;
                $this->EndReturnData->Pagination = $ReturnResponse->Pagination;
                $this->EndReturnData->UIDs = $ReturnResponse->UIDs;

            } else {
                throw new Exception('Product Information is Missing to Delete');
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		$this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;

    }

    /** Categories Details Starts Here */
    public function getCategoriesDetails($pageNo = 0) {

		$this->EndReturnData = new stdClass();
		try {

			$limit = $this->input->post('RowLimit');
            $offset = ($pageNo != 0) ? (($pageNo - 1) * $limit) : $pageNo;
            $Filter = $this->input->post('Filter');
            $ModuleId = $this->input->post('ModuleId');

			$ReturnResponse = $this->globalservice->getBaseMainPageTablePagination($ModuleId, '/products/getCategoriesDetails/', 'products/categories/list', $pageNo, $limit, $offset, $Filter, []);
            if($ReturnResponse->Error) {
                throw new Exception($ReturnResponse->Message);
            }

            $this->EndReturnData->Error = false;
            $this->EndReturnData->List = $ReturnResponse->List;
			$this->EndReturnData->UIDs = $ReturnResponse->UIDs;
            $this->EndReturnData->Pagination = $ReturnResponse->Pagination;

		} catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		$this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;

	}

    public function addCategoryDetails() {

        $this->EndReturnData = new stdClass();
		try {

            $this->load->model('formvalidation_model');

            $PostData = $this->input->post();
            $ErrorInForm = $this->formvalidation_model->categoryValidateForm($PostData);
            if(empty($ErrorInForm)) {

                $this->load->model('dbwrite_model');

                $CategoryUID = 0;
                $categoryFormData = [
                    'Name' => $PostData['CategoryName'],
                    'OrgUID' => $this->pageData['JwtData']->User->OrgUID,
                    'Description' => (isset($PostData['CategoryDescription']) && !empty($PostData['CategoryDescription'])) ? $PostData['CategoryDescription'] : NULL,
                    'CreatedBy' => $this->pageData['JwtData']->User->UserUID,
                    'UpdatedBy' => $this->pageData['JwtData']->User->UserUID,
                    'CreatedOn' => time(),
                    'UpdatedOn' => time(),
                ];

                $InsertDataResp = $this->dbwrite_model->insertData('Products', 'CategoryTbl', $categoryFormData);
                if($InsertDataResp->Error) {
                    throw new Exception($InsertDataResp->Message);
                } else {
                    $CategoryUID = $InsertDataResp->ID;
                }

                // Image Upload
                if(isset($_FILES['UploadImage'])) {
                    $UploadResp = $this->globalservice->fileUploadService($_FILES['UploadImage'], 'products/category/images/', 'Image', ['Products', 'CategoryTbl', array('CategoryUID' => $CategoryUID)]);
                    if($UploadResp->Error === TRUE) {
                        throw new Exception($UploadResp->Message);
                    }
                }

                $limit = $this->input->post('RowLimit');
                $pageNo = $this->input->post('PageNo');
                $offset = ($pageNo != 0) ? (($pageNo - 1) * $limit) : $pageNo;
                $Filter = $this->input->post('Filter') ? $this->input->post('Filter') : [];
                $ModuleId = $this->input->post('ModuleId');

                $ReturnResponse = $this->globalservice->getBaseMainPageTablePagination($ModuleId, '/products/getCategoriesDetails/', 'products/categories/list', $pageNo, $limit, $offset, $Filter, []);
                if($ReturnResponse->Error) {
                    throw new Exception($ReturnResponse->Message);
                }

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = 'Created Successfully';
                $this->EndReturnData->List = $ReturnResponse->List;
                $this->EndReturnData->Pagination = $ReturnResponse->Pagination;
                $this->EndReturnData->UIDs = $ReturnResponse->UIDs;

                $this->load->model('products_model');
                $this->EndReturnData->CatgList = $this->products_model->getCategoriesDetails([]);

            } else {
                throw new Exception($ErrorInForm);
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		$this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;

    }

    public function retrieveCategoryDetails() {

        $this->EndReturnData = new stdClass();
		try {

            $CategoryUID = $this->input->post('CategoryUID');
            if($CategoryUID) {

                $this->load->model('products_model');
                $GetCatgData = $this->products_model->getCategoriesDetails(['Category.CategoryUID' => $CategoryUID]);
                if((sizeof($GetCatgData) > 0) && sizeof($GetCatgData) == 1) {

                    $this->EndReturnData->Error = FALSE;
                    $this->EndReturnData->Message = 'Retrieved Successfully';
                    $this->EndReturnData->Data = $GetCatgData[0];

                } else {
                    throw new Exception('Something went wrong. Please try again.!');
                }

            } else {
                throw new Exception('Category UID is Missing');
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;

	}

    public function updateCategoryDetails() {

        $this->EndReturnData = new stdClass();
		try {

            $this->load->model('formvalidation_model');

            $PostData = $this->input->post();
            $ErrorInForm = $this->formvalidation_model->categoryValidateForm($PostData);
            if(empty($ErrorInForm)) {

                $this->load->model('dbwrite_model');

                $CategoryUID = $PostData['CategoryUID'] ? $PostData['CategoryUID'] : 0;

                $categoryFormData = [
                    'Name' => $PostData['CategoryName'],
                    'OrgUID' => $this->pageData['JwtData']->User->OrgUID,
                    'Description' => (isset($PostData['CategoryDescription']) && !empty($PostData['CategoryDescription'])) ? $PostData['CategoryDescription'] : NULL,
                    'UpdatedBy' => $this->pageData['JwtData']->User->UserUID,
                    'UpdatedOn' => time(),
                ];

                // Stored Image Removed
                if(isset($PostData['RemovedImage']) && $PostData['RemovedImage'] == TRUE) {
                    $categoryFormData['Image'] = NULL;
                }

                $UpdateDataResp = $this->dbwrite_model->updateData('Products', 'CategoryTbl', $categoryFormData, array('CategoryUID' => $CategoryUID));
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

                $limit = $this->input->post('RowLimit');
                $pageNo = $this->input->post('PageNo');
                $offset = ($pageNo != 0) ? (($pageNo - 1) * $limit) : $pageNo;
                $Filter = $this->input->post('Filter') ? $this->input->post('Filter') : [];
                $ModuleId = $this->input->post('ModuleId');

                $ReturnResponse = $this->globalservice->getBaseMainPageTablePagination($ModuleId, '/products/getCategoriesDetails/', 'products/categories/list', $pageNo, $limit, $offset, $Filter, []);
                if($ReturnResponse->Error) {
                    throw new Exception($ReturnResponse->Message);
                }

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = 'Updated Successfully';
                $this->EndReturnData->List = $ReturnResponse->List;
                $this->EndReturnData->Pagination = $ReturnResponse->Pagination;
                $this->EndReturnData->UIDs = $ReturnResponse->UIDs;

                $this->load->model('products_model');
                $this->EndReturnData->CatgList = $this->products_model->getCategoriesDetails([]);

            } else {
                throw new Exception($ErrorInForm);
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		$this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;

    }

    public function deleteCategoryDetails() {

        $this->EndReturnData = new stdClass();
		try {

            $CategoryUID = $this->input->post('CategoryUID');
            if($CategoryUID) {

                /** Cross Check with Products */
                $this->load->model('products_model');
                $ExistsInProducts = $this->products_model->getProductsDetails(['Category.CategoryUID' => $CategoryUID]);
                if(!empty($ExistsInProducts) && sizeof($ExistsInProducts) > 0) {
                    throw new Exception('Category is linked to Product.');
                }

                $updateCategoryData = [
                    'IsDeleted' => 1,
                    'UpdatedBy' => $this->pageData['JwtData']->User->UserUID,
                    'UpdatedOn' => time(),
                ];

                $this->load->model('dbwrite_model');
                
                $UpdateResp = $this->dbwrite_model->updateData('Products', 'CategoryTbl', $updateCategoryData, array('CategoryUID' => $CategoryUID));
                if($UpdateResp->Error) {
                    throw new Exception($UpdateResp->Message);
                }

                $limit = $this->input->post('RowLimit');
                $pageNo = $this->input->post('PageNo') ? $this->input->post('PageNo') : 0;
                $offset = ($pageNo != 0) ? (($pageNo - 1) * $limit) : $pageNo;
                $Filter = $this->input->post('Filter') ? $this->input->post('Filter') : [];
                $ModuleId = $this->input->post('ModuleId');

                $ReturnResponse = $this->globalservice->getBaseMainPageTablePagination($ModuleId, '/products/getCategoriesDetails/', 'products/categories/list', $pageNo, $limit, $offset, $Filter, []);
                if($ReturnResponse->Error) {
                    throw new Exception($ReturnResponse->Message);
                }

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = 'Deleted Successfully';
                $this->EndReturnData->List = $ReturnResponse->List;
                $this->EndReturnData->Pagination = $ReturnResponse->Pagination;
                $this->EndReturnData->UIDs = $ReturnResponse->UIDs;

                $this->load->model('products_model');
                $this->EndReturnData->CatgList = $this->products_model->getCategoriesDetails([]);

            } else {
                throw new Exception('Category Information is Missing to Delete');
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		$this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;

    }

    public function deleteBulkCategory() {

        $this->EndReturnData = new stdClass();
		try {

            $CategoryUIDs = $this->input->post('CategoryUIDs');
            if($CategoryUIDs) {

                /** Cross Check with Products */
                $this->load->model('products_model');
                $ExistsInProducts = $this->products_model->getProductsDetails([], '', ['Products.CategoryUID' => $CategoryUIDs]);
                if(!empty($ExistsInProducts) && sizeof($ExistsInProducts) > 0) {
                    throw new Exception('Category is linked to Product.');
                }

                $updateCategData = [
                    'IsDeleted' => 1,
                    'UpdatedBy' => $this->pageData['JwtData']->User->UserUID,
                    'UpdatedOn' => time(),
                ];

                $this->load->model('dbwrite_model');
                
                $UpdateResp = $this->dbwrite_model->updateData('Products', 'CategoryTbl', $updateCategData, [], array('CategoryUID' => $CategoryUIDs));
                if($UpdateResp->Error) {
                    throw new Exception($UpdateResp->Message);
                }

                $limit = $this->input->post('RowLimit');
                $pageNo = $this->input->post('PageNo') ? $this->input->post('PageNo') : 0;
                $offset = ($pageNo != 0) ? (($pageNo - 1) * $limit) : $pageNo;
                $Filter = $this->input->post('Filter') ? $this->input->post('Filter') : [];
                $ModuleId = $this->input->post('ModuleId');

                $ReturnResponse = $this->globalservice->getBaseMainPageTablePagination($ModuleId, '/products/getCategoriesDetails/', 'products/categories/list', $pageNo, $limit, $offset, $Filter, []);
                if($ReturnResponse->Error) {
                    throw new Exception($ReturnResponse->Message);
                }

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = 'Deleted Successfully';
                $this->EndReturnData->List = $ReturnResponse->List;
                $this->EndReturnData->Pagination = $ReturnResponse->Pagination;
                $this->EndReturnData->UIDs = $ReturnResponse->UIDs;

                $this->load->model('products_model');
                $this->EndReturnData->CatgList = $this->products_model->getCategoriesDetails([]);

            } else {
                throw new Exception('Category Information is Missing to Delete');
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		$this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;

    }

    /** Sizes Details Starts Here */
    public function getSizesDetails($pageNo = 0) {

		$this->EndReturnData = new stdClass();
		try {

            $limit = $this->input->post('RowLimit');
            $offset = ($pageNo != 0) ? (($pageNo - 1) * $limit) : $pageNo;
            $Filter = $this->input->post('Filter') ? $this->input->post('Filter') : [];
            $ModuleId = $this->input->post('ModuleId');

            $ReturnResponse = $this->globalservice->getBaseMainPageTablePagination($ModuleId, '/products/getSizesDetails/', 'products/sizes/list', $pageNo, $limit, $offset, $Filter, []);
            if($ReturnResponse->Error) {
                throw new Exception($ReturnResponse->Message);
            }

            $this->EndReturnData->Error = false;
            $this->EndReturnData->List = $ReturnResponse->List;
			$this->EndReturnData->UIDs = $ReturnResponse->UIDs;
            $this->EndReturnData->Pagination = $ReturnResponse->Pagination;

		} catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		$this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;

	}

    public function addSizeDetails() {

        $this->EndReturnData = new stdClass();
		try {

            $this->load->model('formvalidation_model');

            $PostData = $this->input->post();
            $ErrorInForm = $this->formvalidation_model->sizesValidateForm($PostData);
            if(empty($ErrorInForm)) {

                $this->load->model('dbwrite_model');

                $SizeFormData = [
                    'Name' => $PostData['SizesName'],
                    'OrgUID' => $this->pageData['JwtData']->User->OrgUID,
                    'Description' => (isset($PostData['SizesDescription']) && !empty($PostData['SizesDescription'])) ? $PostData['SizesDescription'] : NULL,
                    'CreatedBy' => $this->pageData['JwtData']->User->UserUID,
                    'UpdatedBy' => $this->pageData['JwtData']->User->UserUID,
                    'CreatedOn' => time(),
                    'UpdatedOn' => time(),
                ];

                $InsertDataResp = $this->dbwrite_model->insertData('Products', 'SizeTbl', $SizeFormData);
                if($InsertDataResp->Error) {
                    throw new Exception($InsertDataResp->Message);
                }

                $limit = $this->input->post('RowLimit');
                $pageNo = $this->input->post('PageNo');
                $offset = ($pageNo != 0) ? (($pageNo - 1) * $limit) : $pageNo;
                $Filter = $this->input->post('Filter') ? $this->input->post('Filter') : [];
                $ModuleId = $this->input->post('ModuleId');

                $ReturnResponse = $this->globalservice->getBaseMainPageTablePagination($ModuleId, '/products/getSizesDetails/', 'products/sizes/list', $pageNo, $limit, $offset, $Filter, []);
                if($ReturnResponse->Error) {
                    throw new Exception($ReturnResponse->Message);
                }

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = 'Created Successfully';
                $this->EndReturnData->List = $ReturnResponse->List;
                $this->EndReturnData->UIDs = $ReturnResponse->UIDs;
                $this->EndReturnData->Pagination = $ReturnResponse->Pagination;

                $this->load->model('products_model');
                $this->EndReturnData->SizeList = $this->products_model->getSizeDetails([]);

            } else {
                throw new Exception($ErrorInForm);
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		$this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;

    }

    public function retrieveSizeDetails() {

        $this->EndReturnData = new stdClass();
		try {

            $SizeUID = $this->input->post('SizeUID');
            if($SizeUID) {

                $this->load->model('products_model');
                $GetSizeData = $this->products_model->getSizeDetails(['Size.SizeUID' => $SizeUID]);
                if((sizeof($GetSizeData) > 0) && sizeof($GetSizeData) == 1) {

                    $this->EndReturnData->Error = FALSE;
                    $this->EndReturnData->Message = 'Retrieved Successfully';
                    $this->EndReturnData->Data = $GetSizeData[0];

                } else {
                    throw new Exception('Something went wrong. Please try again.!');
                }
            } else {
                throw new Exception('Size UID is Missing');
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;

	}

    public function updateSizeDetails() {

        $this->EndReturnData = new stdClass();
		try {

            $this->load->model('formvalidation_model');

            $PostData = $this->input->post();
            $ErrorInForm = $this->formvalidation_model->sizesValidateForm($PostData);
            if(empty($ErrorInForm)) {

                $this->load->model('dbwrite_model');

                $SizeUID = $PostData['SizeUID'] ? $PostData['SizeUID'] : 0;

                $SizeFormData = [
                    'Name' => $PostData['SizesName'],
                    'Description' => (isset($PostData['SizesDescription']) && !empty($PostData['SizesDescription'])) ? $PostData['SizesDescription'] : NULL,
                    'UpdatedBy' => $this->pageData['JwtData']->User->UserUID,
                    'UpdatedOn' => time(),
                ];

                $UpdateDataResp = $this->dbwrite_model->updateData('Products', 'SizeTbl', $SizeFormData, array('SizeUID' => $SizeUID));
                if($UpdateDataResp->Error) {
                    throw new Exception($UpdateDataResp->Message);
                }

                $limit = $this->input->post('RowLimit');
                $pageNo = $this->input->post('PageNo');
                $offset = ($pageNo != 0) ? (($pageNo - 1) * $limit) : $pageNo;
                $Filter = $this->input->post('Filter') ? $this->input->post('Filter') : [];
                $ModuleId = $this->input->post('ModuleId');

                $ReturnResponse = $this->globalservice->getBaseMainPageTablePagination($ModuleId, '/products/getSizesDetails/', 'products/sizes/list', $pageNo, $limit, $offset, $Filter, []);
                if($ReturnResponse->Error) {
                    throw new Exception($ReturnResponse->Message);
                }

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = 'Updated Successfully';
                $this->EndReturnData->List = $ReturnResponse->List;
                $this->EndReturnData->UIDs = $ReturnResponse->UIDs;
                $this->EndReturnData->Pagination = $ReturnResponse->Pagination;

                $this->load->model('products_model');
                $this->EndReturnData->SizeList = $this->products_model->getSizeDetails([]);

            } else {
                throw new Exception($ErrorInForm);
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		$this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;

    }

    public function deleteSizeDetails() {

        $this->EndReturnData = new stdClass();
		try {

            $SizeUID = $this->input->post('SizeUID');
            if($SizeUID) {

                $updateSizeData = [
                    'IsDeleted' => 1,
                    'UpdatedBy' => $this->pageData['JwtData']->User->UserUID,
                    'UpdatedOn' => time(),
                ];

                $this->load->model('dbwrite_model');
                
                $UpdateResp = $this->dbwrite_model->updateData('Products', 'SizeTbl', $updateSizeData, array('SizeUID' => $SizeUID));
                if($UpdateResp->Error) {
                    throw new Exception($UpdateResp->Message);
                }

                $limit = $this->input->post('RowLimit');
                $pageNo = $this->input->post('PageNo');
                $offset = ($pageNo != 0) ? (($pageNo - 1) * $limit) : $pageNo;
                $Filter = $this->input->post('Filter') ? $this->input->post('Filter') : [];
                $ModuleId = $this->input->post('ModuleId');

                $ReturnResponse = $this->globalservice->getBaseMainPageTablePagination($ModuleId, '/products/getSizesDetails/', 'products/sizes/list', $pageNo, $limit, $offset, $Filter, []);
                if($ReturnResponse->Error) {
                    throw new Exception($ReturnResponse->Message);
                }

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = 'Deleted Successfully';
                $this->EndReturnData->List = $ReturnResponse->List;
                $this->EndReturnData->UIDs = $ReturnResponse->UIDs;
                $this->EndReturnData->Pagination = $ReturnResponse->Pagination;

                $this->load->model('products_model');
                $this->EndReturnData->SizeList = $this->products_model->getSizeDetails([]);

            } else {
                throw new Exception('Size Information is Missing to Delete');
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		$this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;

    }

    public function deleteBulkSize() {

        $this->EndReturnData = new stdClass();
		try {

            $SizeUIDs = $this->input->post('SizeUIDs');
            if($SizeUIDs) {

                /** Cross Check with Products */
                $this->load->model('products_model');
                $ExistsInProducts = $this->products_model->getProductsDetails([], '', ['Products.SizeUID' => $SizeUIDs]);
                if(!empty($ExistsInProducts) && sizeof($ExistsInProducts) > 0) {
                    throw new Exception('Size is linked to Product.');
                }

                $updateSizeData = [
                    'IsDeleted' => 1,
                    'UpdatedBy' => $this->pageData['JwtData']->User->UserUID,
                    'UpdatedOn' => time(),
                ];

                $this->load->model('dbwrite_model');
                
                $UpdateResp = $this->dbwrite_model->updateData('Products', 'SizeTbl', $updateSizeData, [], array('SizeUID' => $SizeUIDs));
                if($UpdateResp->Error) {
                    throw new Exception($UpdateResp->Message);
                }

                $limit = $this->input->post('RowLimit');
                $pageNo = $this->input->post('PageNo') ? $this->input->post('PageNo') : 0;
                $offset = ($pageNo != 0) ? (($pageNo - 1) * $limit) : $pageNo;
                $Filter = $this->input->post('Filter') ? $this->input->post('Filter') : [];
                $ModuleId = $this->input->post('ModuleId');

                $ReturnResponse = $this->globalservice->getBaseMainPageTablePagination($ModuleId, '/products/getSizesDetails/', 'products/sizes/list', $pageNo, $limit, $offset, $Filter, []);
                if($ReturnResponse->Error) {
                    throw new Exception($ReturnResponse->Message);
                }

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = 'Deleted Successfully';
                $this->EndReturnData->List = $ReturnResponse->List;
                $this->EndReturnData->Pagination = $ReturnResponse->Pagination;
                $this->EndReturnData->UIDs = $ReturnResponse->UIDs;

                $this->load->model('products_model');
                $this->EndReturnData->SizeList = $this->products_model->getSizeDetails([]);

            } else {
                throw new Exception('Size Information is Missing to Delete');
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		$this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;

    }

    /** Brands Details Starts Here */
    public function getBrandsDetails($pageNo = 0) {

		$this->EndReturnData = new stdClass();
		try {

            $limit = $this->input->post('RowLimit');
            $offset = ($pageNo != 0) ? (($pageNo - 1) * $limit) : $pageNo;
            $Filter = $this->input->post('Filter') ? $this->input->post('Filter') : [];
            $ModuleId = $this->input->post('ModuleId');

            $ReturnResponse = $this->globalservice->getBaseMainPageTablePagination($ModuleId, '/products/getBrandsDetails/', 'products/brands/list', $pageNo, $limit, $offset, $Filter, []);
            if($ReturnResponse->Error) {
                throw new Exception($ReturnResponse->Message);
            }

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->List = $ReturnResponse->List;
            $this->EndReturnData->UIDs = $ReturnResponse->UIDs;
            $this->EndReturnData->Pagination = $ReturnResponse->Pagination;

		} catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		$this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;

	}

    public function addBrandDetails() {

        $this->EndReturnData = new stdClass();
		try {

            $this->load->model('formvalidation_model');

            $PostData = $this->input->post();
            $ErrorInForm = $this->formvalidation_model->brandsValidateForm($PostData);
            if(empty($ErrorInForm)) {

                $this->load->model('dbwrite_model');

                $BrandFormData = [
                    'Name' => $PostData['BrandsName'],
                    'OrgUID' => $this->pageData['JwtData']->User->OrgUID,
                    'Description' => (isset($PostData['BrandsDescription']) && !empty($PostData['BrandsDescription'])) ? $PostData['BrandsDescription'] : NULL,
                    'CreatedBy' => $this->pageData['JwtData']->User->UserUID,
                    'UpdatedBy' => $this->pageData['JwtData']->User->UserUID,
                    'CreatedOn' => time(),
                    'UpdatedOn' => time(),
                ];

                $InsertDataResp = $this->dbwrite_model->insertData('Products', 'BrandTbl', $BrandFormData);
                if($InsertDataResp->Error) {
                    throw new Exception($InsertDataResp->Message);
                }

                $limit = $this->input->post('RowLimit');
                $pageNo = $this->input->post('PageNo');
                $offset = ($pageNo != 0) ? (($pageNo - 1) * $limit) : $pageNo;
                $Filter = $this->input->post('Filter') ? $this->input->post('Filter') : [];
                $ModuleId = $this->input->post('ModuleId');

                $ReturnResponse = $this->globalservice->getBaseMainPageTablePagination($ModuleId, '/products/getBrandsDetails/', 'products/brands/list', $pageNo, $limit, $offset, $Filter, []);
                if($ReturnResponse->Error) {
                    throw new Exception($ReturnResponse->Message);
                }

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = 'Created Successfully';
                $this->EndReturnData->List = $ReturnResponse->List;
                $this->EndReturnData->UIDs = $ReturnResponse->UIDs;
                $this->EndReturnData->Pagination = $ReturnResponse->Pagination;

                $this->load->model('products_model');
                $this->EndReturnData->BrandList = $this->products_model->getBrandDetails([]);

            } else {
                throw new Exception($ErrorInForm);
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		$this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;

    }

    public function retrieveBrandDetails() {

        $this->EndReturnData = new stdClass();
		try {

            $BrandUID = $this->input->post('BrandUID');
            if($BrandUID) {

                $this->load->model('products_model');
                $GetBrandData = $this->products_model->getBrandDetails(['Brand.BrandUID' => $BrandUID]);
                if((sizeof($GetBrandData) > 0) && sizeof($GetBrandData) == 1) {

                    $this->EndReturnData->Error = FALSE;
                    $this->EndReturnData->Message = 'Retrieved Successfully';
                    $this->EndReturnData->Data = $GetBrandData[0];

                } else {
                    throw new Exception('Something went wrong. Please try again.!');
                }
            } else {
                throw new Exception('Brand UID is Missing');
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;

	}

    public function updateBrandDetails() {

        $this->EndReturnData = new stdClass();
		try {

            $this->load->model('formvalidation_model');

            $PostData = $this->input->post();
            $ErrorInForm = $this->formvalidation_model->brandsValidateForm($PostData);
            if(empty($ErrorInForm)) {

                $this->load->model('dbwrite_model');

                $BrandUID = $PostData['BrandUID'] ? $PostData['BrandUID'] : 0;

                $BrandFormData = [
                    'Name' => $PostData['BrandsName'],
                    'Description' => (isset($PostData['BrandsDescription']) && !empty($PostData['BrandsDescription'])) ? $PostData['BrandsDescription'] : NULL,
                    'UpdatedBy' => $this->pageData['JwtData']->User->UserUID,
                    'UpdatedOn' => time(),
                ];

                $UpdateDataResp = $this->dbwrite_model->updateData('Products', 'BrandTbl', $BrandFormData, array('BrandUID' => $BrandUID));
                if($UpdateDataResp->Error) {
                    throw new Exception($UpdateDataResp->Message);
                }

                $limit = $this->input->post('RowLimit');
                $pageNo = $this->input->post('PageNo');
                $offset = ($pageNo != 0) ? (($pageNo - 1) * $limit) : $pageNo;
                $Filter = $this->input->post('Filter') ? $this->input->post('Filter') : [];
                $ModuleId = $this->input->post('ModuleId');

                $ReturnResponse = $this->globalservice->getBaseMainPageTablePagination($ModuleId, '/products/getBrandsDetails/', 'products/brands/list', $pageNo, $limit, $offset, $Filter, []);
                if($ReturnResponse->Error) {
                    throw new Exception($ReturnResponse->Message);
                }

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = 'Updated Successfully';
                $this->EndReturnData->List = $ReturnResponse->List;
                $this->EndReturnData->UIDs = $ReturnResponse->UIDs;
                $this->EndReturnData->Pagination = $ReturnResponse->Pagination;

                $this->load->model('products_model');
                $this->EndReturnData->BrandList = $this->products_model->getBrandDetails([]);

            } else {
                throw new Exception($ErrorInForm);
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		$this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;

    }

    public function deleteBrandDetails() {

        $this->EndReturnData = new stdClass();
		try {

            $BrandUID = $this->input->post('BrandUID');
            if($BrandUID) {

                $updateBrandData = [
                    'IsDeleted' => 1,
                    'UpdatedBy' => $this->pageData['JwtData']->User->UserUID,
                    'UpdatedOn' => time(),
                ];

                $this->load->model('dbwrite_model');
                
                $UpdateResp = $this->dbwrite_model->updateData('Products', 'BrandTbl', $updateBrandData, array('BrandUID' => $BrandUID));
                if($UpdateResp->Error) {
                    throw new Exception($UpdateResp->Message);
                }

                $limit = $this->input->post('RowLimit');
                $pageNo = $this->input->post('PageNo') ? $this->input->post('PageNo') : 0;
                $offset = ($pageNo != 0) ? (($pageNo - 1) * $limit) : $pageNo;
                $Filter = $this->input->post('Filter') ? $this->input->post('Filter') : [];
                $ModuleId = $this->input->post('ModuleId');

                $ReturnResponse = $this->globalservice->getBaseMainPageTablePagination($ModuleId, '/products/getBrandsDetails/', 'products/brands/list', $pageNo, $limit, $offset, $Filter, []);
                if($ReturnResponse->Error) {
                    throw new Exception($ReturnResponse->Message);
                }

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = 'Deleted Successfully';
                $this->EndReturnData->List = $ReturnResponse->List;
                $this->EndReturnData->UIDs = $ReturnResponse->UIDs;
                $this->EndReturnData->Pagination = $ReturnResponse->Pagination;

                $this->load->model('products_model');
                $this->EndReturnData->BrandList = $this->products_model->getBrandDetails([]);

            } else {
                throw new Exception('Size Information is Missing to Delete');
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		$this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;

    }

    public function deleteBulkBrand() {

        $this->EndReturnData = new stdClass();
		try {

            $BrandUIDs = $this->input->post('BrandUIDs');
            if($BrandUIDs) {

                /** Cross Check with Products */
                $this->load->model('products_model');
                $ExistsInProducts = $this->products_model->getProductsDetails([], '', ['Products.BrandUID' => $BrandUIDs]);
                if(!empty($ExistsInProducts) && sizeof($ExistsInProducts) > 0) {
                    throw new Exception('Brand is linked to Product.');
                }

                $updateBrandData = [
                    'IsDeleted' => 1,
                    'UpdatedBy' => $this->pageData['JwtData']->User->UserUID,
                    'UpdatedOn' => time(),
                ];

                $this->load->model('dbwrite_model');
                
                $UpdateResp = $this->dbwrite_model->updateData('Products', 'BrandTbl', $updateBrandData, [], array('BrandUID' => $BrandUIDs));
                if($UpdateResp->Error) {
                    throw new Exception($UpdateResp->Message);
                }

                $limit = $this->input->post('RowLimit');
                $pageNo = $this->input->post('PageNo') ? $this->input->post('PageNo') : 0;
                $offset = ($pageNo != 0) ? (($pageNo - 1) * $limit) : $pageNo;
                $Filter = $this->input->post('Filter') ? $this->input->post('Filter') : [];
                $ModuleId = $this->input->post('ModuleId');

                $ReturnResponse = $this->globalservice->getBaseMainPageTablePagination($ModuleId, '/products/getBrandsDetails/', 'products/brands/list', $pageNo, $limit, $offset, $Filter, []);
                if($ReturnResponse->Error) {
                    throw new Exception($ReturnResponse->Message);
                }

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = 'Deleted Successfully';
                $this->EndReturnData->List = $ReturnResponse->List;
                $this->EndReturnData->Pagination = $ReturnResponse->Pagination;
                $this->EndReturnData->UIDs = $ReturnResponse->UIDs;

                $this->load->model('products_model');
                $this->EndReturnData->BrandList = $this->products_model->getBrandDetails([]);

            } else {
                throw new Exception('Brand Information is Missing to Delete');
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		$this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;

    }

}