<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Products extends CI_Controller {

    public $pageData = array();
    private $EndReturnData;

    public function __construct() {
        parent::__construct();

    }

    public function index() {
        $this->load->view('products/view', $this->pageData);
    }

    public function getProductDetails($pageNo = 0) {

		$this->EndReturnData = new stdClass();
		try {

			$limit = $this->input->post('RowLimit');
            $offset = ($pageNo != 0) ? (($pageNo - 1) * $limit) : $pageNo;
            $Filter = $this->input->post('Filter');

			$this->load->model('products_model');
            $this->pageData['ProductsList'] = $this->products_model->getProductsList($limit, $offset, $Filter, 0);
            $ProductsCount = $this->products_model->getProductsList($limit, $offset, $Filter, 1);

			$config['base_url'] = '/products/getProductDetails/';
            $config['use_page_numbers'] = TRUE;
            $config['total_rows'] = $ProductsCount;
            $config['per_page'] = $limit;

            $config['result_count'] = pageResultCount($pageNo, $limit, $ProductsCount);
            $this->pagination->initialize($config);

            $this->EndReturnData->Error = false;
            $this->EndReturnData->List = $this->load->view('products/items/list', $this->pageData, TRUE);
			$this->EndReturnData->ProductsCount = $ProductsCount;
            $this->EndReturnData->Pagination = $this->pagination->create_links();

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

    public function add() {

        $this->load->model('global_model');

        $this->pageData['PrimaryUnitInfo'] = [];
        $this->pageData['DiscTypeInfo'] = [];
        $this->pageData['ProdTypeInfo'] = [];
        $this->pageData['ProdTaxInfo'] = [];
        $this->pageData['TaxDetInfo'] = [];
        
        $GetPrimaryUnitInfo = $this->global_model->getPrimaryUnitInfo();
        if($GetPrimaryUnitInfo->Error === FALSE) {
            $this->pageData['PrimaryUnitInfo'] = $GetPrimaryUnitInfo->Data;
        }
        $GetDiscTypeInfo = $this->global_model->getDiscountTypeInfo();
        if($GetDiscTypeInfo->Error === FALSE) {
            $this->pageData['DiscTypeInfo'] = $GetDiscTypeInfo->Data;
        }
        $GetProdTypeInfo = $this->global_model->getProductTypeInfo();
        if($GetProdTypeInfo->Error === FALSE) {
            $this->pageData['ProdTypeInfo'] = $GetProdTypeInfo->Data;
        }
        $GetProdTaxInfo = $this->global_model->getProductTaxInfo();
        if($GetProdTaxInfo->Error === FALSE) {
            $this->pageData['ProdTaxInfo'] = $GetProdTaxInfo->Data;
        }
        $GetTaxDetInfo = $this->global_model->getTaxDetailsInfo();
        if($GetTaxDetInfo->Error === FALSE) {
            $this->pageData['TaxDetInfo'] = $GetTaxDetInfo->Data;
        }

        $this->load->model('products_model');
        $this->pageData['CategoriesInfo'] = $this->products_model->getCategoriesDetails([]);
        $this->pageData['BrandInfo'] = $this->products_model->getBrandDetails([]);

        $this->load->view('products/items/forms/add', $this->pageData);

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
                    'OpeningQuantity' => isset($PostData['OpeningQuantity']) ? $PostData['OpeningQuantity'] : 0,
                    'OpeningPurchasePrice' => isset($PostData['OpeningPurchasePrice']) ? $PostData['OpeningPurchasePrice'] : 0,
                    'OpeningStockValue' => isset($PostData['OpeningStockValue']) ? $PostData['OpeningStockValue'] : 0,
                    'Discount' => isset($PostData['Discount']) ? $PostData['Discount'] : 0,
                    'DiscountTypeUID' => isset($PostData['DiscountOption']) ? $PostData['DiscountOption'] : 0,
                    'LowStockAlertAt' => isset($PostData['LowStockAlert']) ? $PostData['LowStockAlert'] : 0,
                    'NotForSale' => isset($PostData['NotForSale']) ? 'Yes' : 'No',
                    'BrandUID' => isset($PostData['BrandUID']) ? $PostData['BrandUID'] : NULL,
                    'Standard' => (isset($PostData['Standard']) && !empty($PostData['Standard'])) ? $PostData['Standard'] : NULL,
                    'Model' => (isset($PostData['Model']) && !empty($PostData['Model'])) ? $PostData['Model'] : NULL,
                    'IsSizeApplicable' => (isset($PostData['IsSizeApplicable']) && !empty($PostData['IsSizeApplicable'])) ? 1 : 0,
                    'SizeUID' => (isset($PostData['IsSizeApplicable']) && !empty($PostData['IsSizeApplicable'])) && isset($PostData['SizeUID']) ? $PostData['SizeUID'] : NULL,
                    'CreatedBy' => $this->pageData['JwtData']->User->UserUID,
                    'UpdatedBy' => $this->pageData['JwtData']->User->UserUID,
                    'CreatedOn' => time(),
                    'UpdatedOn' => time(),
                ];

                $InsertDataResp = $this->dbwrite_model->insertData('Products', 'ProductTbl', $ProductFormData);
                if($InsertDataResp->Error) {
                    throw new Exception($InsertDataResp->Message);
                }

                $ProductUID = $InsertDataResp->ID;

                // Image Upload
                if(isset($_FILES['UploadImage']) && $_FILES['UploadImage']['error'] == 0) {

                    $imagePath = NULL;

                    if (isset($_FILES['UploadImage']['tmp_name']) && !empty($_FILES['UploadImage']['tmp_name'])) {

                        $ext = pathinfo($_FILES['UploadImage']['name'], PATHINFO_EXTENSION);
                        $fileName = substr(str_replace('.'.$ext, '', str_replace(' ', '_', $_FILES['UploadImage']['name'])), 0, 50).'_'.uniqid().'.'.$ext;
                        $imagePath = $this->imageUpload($_FILES['UploadImage']['tmp_name'], $fileName);

                    }

                    if($imagePath) {
                        $updateCatgImgData = [
                            'Image' => $imagePath,
                        ];
                        $UpdateImgResp = $this->dbwrite_model->updateData('Products', 'ProductTbl', $updateCatgImgData, array('ProductUID' => $ProductUID));
                        if($UpdateImgResp->Error) {
                            throw new Exception($UpdateImgResp->Message);
                        }
                    }

                }

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = 'Created Successfully';

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

    public function edit($ProductUID) {

        $ProductUID = (int) $ProductUID;
		if($ProductUID > 0) {

            $this->load->model('products_model');
            $GetProductData = $this->products_model->getProductsDetails(['Products.ProductUID' => $ProductUID]);
            if((sizeof($GetProductData) > 0) && sizeof($GetProductData) == 1) {

                $this->load->model('global_model');

                $this->pageData['PrimaryUnitInfo'] = [];
                $this->pageData['DiscTypeInfo'] = [];
                $this->pageData['ProdTypeInfo'] = [];
                $this->pageData['ProdTaxInfo'] = [];
                $this->pageData['TaxDetInfo'] = [];
                
                $GetPrimaryUnitInfo = $this->global_model->getPrimaryUnitInfo();
                if($GetPrimaryUnitInfo->Error === FALSE) {
                    $this->pageData['PrimaryUnitInfo'] = $GetPrimaryUnitInfo->Data;
                }
                $GetDiscTypeInfo = $this->global_model->getDiscountTypeInfo();
                if($GetDiscTypeInfo->Error === FALSE) {
                    $this->pageData['DiscTypeInfo'] = $GetDiscTypeInfo->Data;
                }
                $GetProdTypeInfo = $this->global_model->getProductTypeInfo();
                if($GetProdTypeInfo->Error === FALSE) {
                    $this->pageData['ProdTypeInfo'] = $GetProdTypeInfo->Data;
                }
                $GetProdTaxInfo = $this->global_model->getProductTaxInfo();
                if($GetProdTaxInfo->Error === FALSE) {
                    $this->pageData['ProdTaxInfo'] = $GetProdTaxInfo->Data;
                }
                $GetTaxDetInfo = $this->global_model->getTaxDetailsInfo();
                if($GetTaxDetInfo->Error === FALSE) {
                    $this->pageData['TaxDetInfo'] = $GetTaxDetInfo->Data;
                }

                $this->load->model('products_model');
                $this->pageData['CategoriesInfo'] = $this->products_model->getCategoriesDetails([]);
                $this->pageData['BrandInfo'] = $this->products_model->getBrandDetails([]);

                $this->pageData['EditData'] = $GetProductData[0];

                $this->load->view('products/items/forms/edit', $this->pageData);

            } else {
                redirect('products');
            }

        } else {
            redirect('products');
        }

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
                    'OpeningQuantity' => isset($PostData['OpeningQuantity']) ? $PostData['OpeningQuantity'] : 0,
                    'OpeningPurchasePrice' => isset($PostData['OpeningPurchasePrice']) ? $PostData['OpeningPurchasePrice'] : 0,
                    'OpeningStockValue' => isset($PostData['OpeningStockValue']) ? $PostData['OpeningStockValue'] : 0,
                    'Discount' => isset($PostData['Discount']) ? $PostData['Discount'] : 0,
                    'DiscountTypeUID' => isset($PostData['DiscountOption']) ? $PostData['DiscountOption'] : 0,
                    'LowStockAlertAt' => isset($PostData['LowStockAlert']) ? $PostData['LowStockAlert'] : 0,
                    'NotForSale' => isset($PostData['NotForSale']) ? 'Yes' : 'No',
                    'BrandUID' => isset($PostData['BrandUID']) ? $PostData['BrandUID'] : NULL,
                    'Standard' => (isset($PostData['Standard']) && !empty($PostData['Standard'])) ? $PostData['Standard'] : NULL,
                    'Model' => (isset($PostData['Model']) && !empty($PostData['Model'])) ? $PostData['Model'] : NULL,
                    'IsSizeApplicable' => (isset($PostData['IsSizeApplicable']) && !empty($PostData['IsSizeApplicable'])) ? 1 : 0,
                    'SizeUID' => (isset($PostData['IsSizeApplicable']) && !empty($PostData['IsSizeApplicable'])) && isset($PostData['SizeUID']) ? $PostData['SizeUID'] : NULL,
                    'UpdatedBy' => $this->pageData['JwtData']->User->UserUID,
                    'UpdatedOn' => time(),
                ];

                $UpdateDataResp = $this->dbwrite_model->updateData('Products', 'ProductTbl', $ProductFormData, array('ProductUID' => $ProductUID));
                if($UpdateDataResp->Error) {
                    throw new Exception($UpdateDataResp->Message);
                }

                // Image Upload
                if(isset($_FILES['UploadImage']) && $_FILES['UploadImage']['error'] == 0) {

                    $imagePath = NULL;

                    if (isset($_FILES['UploadImage']['tmp_name']) && !empty($_FILES['UploadImage']['tmp_name'])) {

                        $ext = pathinfo($_FILES['UploadImage']['name'], PATHINFO_EXTENSION);
                        $fileName = substr(str_replace('.'.$ext, '', str_replace(' ', '_', $_FILES['UploadImage']['name'])), 0, 50).'_'.uniqid().'.'.$ext;
                        $imagePath = $this->imageUpload($_FILES['UploadImage']['tmp_name'], $fileName);

                    }

                    if($imagePath) {
                        $updateCatgImgData = [
                            'Image' => $imagePath,
                        ];
                        $UpdateImgResp = $this->dbwrite_model->updateData('Products', 'ProductTbl', $updateCatgImgData, array('ProductUID' => $ProductUID));
                        if($UpdateImgResp->Error) {
                            throw new Exception($UpdateImgResp->Message);
                        }
                    }

                }

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = 'Updated Successfully';

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

    /** Categories Details Starts Here */
    public function getCategoriesDetails($pageNo = 0) {

		$this->EndReturnData = new stdClass();
		try {

			$limit = $this->input->post('RowLimit');
            $offset = ($pageNo != 0) ? (($pageNo - 1) * $limit) : $pageNo;
            $Filter = $this->input->post('Filter');

			$this->load->model('products_model');
            $this->pageData['CategoriesList'] = $this->products_model->getCategoriesList($limit, $offset, $Filter, 0);
            $CategoriesCount = $this->products_model->getCategoriesList($limit, $offset, $Filter, 1);

			$config['base_url'] = '/products/getCategoriesDetails/';
            $config['use_page_numbers'] = TRUE;
            $config['total_rows'] = $CategoriesCount;
            $config['per_page'] = $limit;

            $config['result_count'] = pageResultCount($pageNo, $limit, $CategoriesCount);
            $this->pagination->initialize($config);

            $this->EndReturnData->Error = false;
            $this->EndReturnData->List = $this->load->view('products/categories/list', $this->pageData, TRUE);
			$this->EndReturnData->CategoriesCount = $CategoriesCount;
            $this->EndReturnData->Pagination = $this->pagination->create_links();

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
                if(isset($_FILES['UploadImage']) && $_FILES['UploadImage']['error'] == 0) {

                    $imagePath = NULL;

                    if (isset($_FILES['UploadImage']['tmp_name']) && !empty($_FILES['UploadImage']['tmp_name'])) {

                        $ext = pathinfo($_FILES['UploadImage']['name'], PATHINFO_EXTENSION);
                        $fileName = substr(str_replace('.'.$ext, '', str_replace(' ', '_', $_FILES['UploadImage']['name'])), 0, 50).'_'.uniqid().'.'.$ext;
                        $imagePath = $this->imageUpload($_FILES['UploadImage']['tmp_name'], $fileName);

                    }

                    if($imagePath) {
                        $updateCatgImgData = [
                            'Image' => $imagePath,
                        ];
                        $UpdateImgResp = $this->dbwrite_model->updateData('Products', 'CategoryTbl', $updateCatgImgData, array('CategoryUID' => $CategoryUID));
                        if($UpdateImgResp->Error) {
                            throw new Exception($UpdateImgResp->Message);
                        }
                    }

                }

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = 'Created Successfully';

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

    private function imageUpload($tempName, $fullPath) {

        $uploadPath = 'products/images/' . $fullPath;

        $this->load->library('fileupload');
        $uploadDetail = $this->fileupload->fileUpload('file', $uploadPath, $tempName);

        if ($uploadDetail->Error === false) {
			return '/'.$uploadDetail->Path;
        } else {
            throw new Exception('File upload failed');
        }

    }

    public function checkImageType() {

        $allowed = array('image/jpeg', 'image/jpg', 'image/png');
        $type_not_match = false;
        if (isset($_FILES['Thumbnail']['name']) && !empty($_FILES['Thumbnail']['name'])) {
            if (!in_array($_FILES['Thumbnail']['type'], $allowed) || $_FILES['Thumbnail']['size'] > 1048576) {
                $type_not_match = true;
            }
        }
        if ($type_not_match) {
            $this->form_validation->set_message('checkImageType', 'Invalid File. Please upload allowed format and size will be below 1MB');
            return false;
        } else {
            return true;
        }

    }

}