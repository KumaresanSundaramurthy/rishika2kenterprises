<?php defined('BASEPATH') OR exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Dompdf;

class Products extends CI_Controller {

    public $pageData = array();
    private $PagReturnData;
    private $EndReturnData;

    public function __construct() {
        parent::__construct();

    }

    public function index() {

        $ControllerName = strtolower($this->router->fetch_class());

        $this->pageData['ModuleInfo'] = array_filter($this->pageData['JwtData']->ModuleInfo, function($module) use ($ControllerName) {
            return $module->ControllerName === $ControllerName;
        });

        $pageNo = $offset = 0;
        $limit = isset($this->pageData['JwtData']->GenSettings->RowLimit) ? $this->pageData['JwtData']->GenSettings->RowLimit : 10;
        $Filter = [];

        $ReturnResponse = $this->ItemTablePagination($pageNo, $limit, $offset, $Filter);
        if($ReturnResponse->Error) {
            throw new Exception($ReturnResponse->Message);
        }

        $this->pageData['ItemList'] = $ReturnResponse->List;
        $this->pageData['ItemUIDs'] = $ReturnResponse->UIDs;
        $this->pageData['ItemPagination'] = $ReturnResponse->Pagination;

        // ModuleID information
        $this->pageData['ItemModuleId'] = $this->getModuleUIDByName($this->pageData['ModuleInfo'], 'Products');
        $this->pageData['CategoryModuleId'] = $this->getModuleUIDByName($this->pageData['ModuleInfo'], 'Category');
        $this->pageData['SizeModuleId'] = $this->getModuleUIDByName($this->pageData['ModuleInfo'], 'Sizes');
        $this->pageData['BrandModuleId'] = $this->getModuleUIDByName($this->pageData['ModuleInfo'], 'Brands');
        
        $this->load->view('products/view', $this->pageData);

    }

    public function getModuleUIDByName($modules, $name) {

        $filtered = array_filter($modules, function($module) use ($name) {
            return $module->Name === $name;
        });

        // If found, return the first match's ModuleUID
        if (!empty($filtered)) {
            return array_values($filtered)[0]->ModuleUID;
        }

        return 0;

    }

    public function getProductDetails($pageNo = 0) {

		$this->EndReturnData = new stdClass();
		try {

			$limit = $this->input->post('RowLimit');
            $offset = ($pageNo != 0) ? (($pageNo - 1) * $limit) : $pageNo;
            $Filter = $this->input->post('Filter');

			$ReturnResponse = $this->ItemTablePagination($pageNo, $limit, $offset, $Filter);
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

    public function ItemTablePagination($pageNo, $limit, $offset, $Filter) {

        $this->PagReturnData = new stdClass();
		try {

			$this->load->model('products_model');
            $this->pageData['SerialNumber'] = $offset;
            $this->pageData['ProductsList'] = $this->products_model->getProductsList($limit, $offset, $Filter, 0);
            $ProductsUIds = $this->products_model->getProductsList($limit, $offset, $Filter, 1);

            $ProductsCount = sizeof($ProductsUIds);

			$config['base_url'] = '/products/getProductDetails/';
            $config['use_page_numbers'] = TRUE;
            $config['total_rows'] = $ProductsCount;
            $config['per_page'] = $limit;

            $config['result_count'] = pageResultCount($pageNo, $limit, $ProductsCount);
            $this->pagination->initialize($config);

            $this->PagReturnData->Error = FALSE;
            $this->PagReturnData->List = $this->load->view('products/items/list', $this->pageData, TRUE);
            $this->PagReturnData->UIDs = sizeof($ProductsUIds) > 0 ? array_map('intval', array_column($ProductsUIds, 'ProductUID')) : [];
			$this->PagReturnData->Count = $ProductsCount;
            $this->PagReturnData->Pagination = $this->pagination->create_links();

        } catch (Exception $e) {
            $this->PagReturnData->Error = TRUE;
            $this->PagReturnData->Message = $e->getMessage();
        }

		return $this->PagReturnData;
        
    }

    public function getAllProductDetails() {

        $this->EndReturnData = new stdClass();
		try {

            $ModuleId = isset($_GET['ModuleId']) ? $_GET['ModuleId'] : 0;
            if($ModuleId > 0) {

                $this->load->model('global_model');
                $DataInfo = $this->global_model->getModuleViewColumnDetails(['ViewColmn.ModuleUID' => $ModuleId, 'ViewColmn.IsPrintPreviewApplicable' => 1], true, ['ViewColmn.IsPrintPreviewApplicable' => 'ASC']);

                if($DataInfo->Error === FALSE && sizeof($DataInfo->Data) > 0) {

                    $ModuleData = $DataInfo->Data;

                    $this->load->model('products_model');

                    $Filter = isset($_GET['Filter']) ? json_decode($_GET['Filter'], TRUE) : [];
                    $ExportIds = isset($_GET['ExportIds']) ? explode(',', base64_decode($_GET['ExportIds'])) : [];

                    $this->pageData['Columns'] = ['Unique_ID', 'Product Name'];
                    $this->pageData['List'] = $this->products_model->getProductReportDetails($ModuleData, 'DESC', ['Products.ProductUID' => $ExportIds]);

                    print_r($this->pageData['List']); die();

                    $this->EndReturnData->Error = FALSE;
                    $this->EndReturnData->HtmlData = $this->load->view('products/items/printpreview', $this->pageData, TRUE);

                } else {
                    throw new Exception('No Records Found.!');    
                }

            } else {
                throw new Exception('Oops! Something went wrong.');
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

    public function exportProductDetails() {

        $Type = isset($_GET['Type']) ? $_GET['Type'] : '';
        $Filter = isset($_GET['Filter']) ? json_decode($_GET['Filter'], TRUE) : [];
        $ExportIds = isset($_GET['ExportIds']) ? explode(',', base64_decode($_GET['ExportIds'])) : [];

        $this->load->model('products_model');
        $ProdData = $this->products_model->getProductsDetails($Filter, 'DESC', ['Products.ProductUID' => $ExportIds]);

        $FileName = 'Product_Report';
        $Headers = ['Unique_Id', 'Item Name'];

        if($Type == 'CSV') {

            if (ob_get_length()) ob_end_clean();

            header('Content-Type: text/csv; charset=utf-8');
            header("Content-Disposition: attachment; filename=\"$FileName.csv\"");
            header("Pragma: no-cache");
            header("Expires: 0");
            
            $file = fopen('php://output','w');

            fputs($file, $bom = chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, $Headers);

            foreach($ProdData as $List) {

                $data = [
                    $List->ProductUID,
                    $List->ItemName
                ];

                fputcsv($file, $data);

            }

            fclose($file);
            exit;

        } else if($Type == 'Excel') {

            if (ob_get_length()) ob_end_clean();

            // Create spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set sheet name
            $sheet->setTitle('Product Details');

            // Write headers
            $sheet->fromArray($Headers, null, 'A1');

            // Write data rows
            $rowNum = 2;
            foreach ($ProdData as $List) {
                $sheet->setCellValue("A$rowNum", $List->ProductUID);
                $sheet->setCellValue("B$rowNum", $List->ItemName);
                $rowNum++;
            }

            // Output headers
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename=\"$FileName.xlsx\"");
            header('Cache-Control: max-age=0');

            // Write file to output
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;

        } else if($Type == 'Pdf') {

            if (ob_get_length()) ob_end_clean();

            // Create spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set sheet name
            $sheet->setTitle('Product Details');

            // Write headers
            $sheet->fromArray($Headers, null, 'A1');

            // Write data rows
            $rowNum = 2;
            foreach ($ProdData as $List) {
                $sheet->setCellValue("A$rowNum", $List->ProductUID);
                $sheet->setCellValue("B$rowNum", $List->ItemName);
                $rowNum++;
            }

            // Output headers
            header('Content-Type: application/pdf');
            header("Content-Disposition: attachment; filename=\"$FileName.pdf\"");
            header('Cache-Control: max-age=0');

            // Use Dompdf writer
            \PhpOffice\PhpSpreadsheet\IOFactory::registerWriter('Pdf', Dompdf::class);
            $writer = new Dompdf($spreadsheet);
            $writer->save('php://output');
            exit;

        }

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
                    'OpeningQuantity' => $PostData['ProductType'] == 'Product' ? (isset($PostData['OpeningQuantity']) ? $PostData['OpeningQuantity'] : 0) : 0,
                    'OpeningPurchasePrice' => $PostData['ProductType'] == 'Product' ? (isset($PostData['OpeningPurchasePrice']) ? $PostData['OpeningPurchasePrice'] : 0) : 0,
                    'OpeningStockValue' => $PostData['ProductType'] == 'Product' ? (isset($PostData['OpeningStockValue']) ? $PostData['OpeningStockValue'] : 0) : 0,
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

    public function clone($ProductUID) {

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

                $this->load->view('products/items/forms/clone', $this->pageData);

            } else {
                redirect('products');
            }

        } else {
            redirect('products');
        }

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
                    'OpeningQuantity' => $PostData['ProductType'] == 'Product' ? (isset($PostData['OpeningQuantity']) ? $PostData['OpeningQuantity'] : 0) : 0,
                    'OpeningPurchasePrice' => $PostData['ProductType'] == 'Product' ? (isset($PostData['OpeningPurchasePrice']) ? $PostData['OpeningPurchasePrice'] : 0) : 0,
                    'OpeningStockValue' => $PostData['ProductType'] == 'Product' ? (isset($PostData['OpeningStockValue']) ? $PostData['OpeningStockValue'] : 0) : 0,
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

                $ReturnResponse = $this->ItemTablePagination($pageNo, $limit, $offset, $Filter);
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

                $ReturnResponse = $this->ItemTablePagination($pageNo, $limit, $offset, $Filter);
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

			$ReturnResponse = $this->CategoryTablePagination($pageNo, $limit, $offset, $Filter);
            if($ReturnResponse->Error) {
                throw new Exception($ReturnResponse->Message);
            }

            $this->EndReturnData->Error = false;
            $this->EndReturnData->List = $ReturnResponse->List;
			$this->EndReturnData->Count = $ReturnResponse->Count;
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

    public function CategoryTablePagination($pageNo, $limit, $offset, $Filter) {

        $this->PagReturnData = new stdClass();
		try {

			$this->load->model('products_model');
            $this->pageData['SerialNumber'] = $offset;
            $this->pageData['CategoriesList'] = $this->products_model->getCategoriesList($limit, $offset, $Filter, 0);
            $CategoriesCount = $this->products_model->getCategoriesList($limit, $offset, $Filter, 1);

			$config['base_url'] = '/products/getCategoriesDetails/';
            $config['use_page_numbers'] = TRUE;
            $config['total_rows'] = $CategoriesCount;
            $config['per_page'] = $limit;

            $config['result_count'] = pageResultCount($pageNo, $limit, $CategoriesCount);
            $this->pagination->initialize($config);

            $this->PagReturnData->Error = FALSE;
            $this->PagReturnData->List = $this->load->view('products/categories/list', $this->pageData, TRUE);
			$this->PagReturnData->Count = $CategoriesCount;
            $this->PagReturnData->Pagination = $this->pagination->create_links();

        } catch (Exception $e) {
            $this->PagReturnData->Error = TRUE;
            $this->PagReturnData->Message = $e->getMessage();
        }

		return $this->PagReturnData;
        
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

                $limit = $this->input->post('RowLimit');
                $pageNo = $this->input->post('PageNo');
                $offset = ($pageNo != 0) ? (($pageNo - 1) * $limit) : $pageNo;
                $Filter = $this->input->post('Filter') ? $this->input->post('Filter') : [];

                $ReturnResponse = $this->CategoryTablePagination($pageNo, $limit, $offset, $Filter);
                if($ReturnResponse->Error) {
                    throw new Exception($ReturnResponse->Message);
                }

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = 'Created Successfully';
                $this->EndReturnData->List = $ReturnResponse->List;
                $this->EndReturnData->Pagination = $ReturnResponse->Pagination;

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

                $limit = $this->input->post('RowLimit');
                $pageNo = $this->input->post('PageNo');
                $offset = ($pageNo != 0) ? (($pageNo - 1) * $limit) : $pageNo;
                $Filter = $this->input->post('Filter') ? $this->input->post('Filter') : [];

                $ReturnResponse = $this->CategoryTablePagination($pageNo, $limit, $offset, $Filter);
                if($ReturnResponse->Error) {
                    throw new Exception($ReturnResponse->Message);
                }

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = 'Updated Successfully';
                $this->EndReturnData->List = $ReturnResponse->List;
                $this->EndReturnData->Pagination = $ReturnResponse->Pagination;

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

                $ReturnResponse = $this->CategoryTablePagination($pageNo, $limit, $offset, $Filter);
                if($ReturnResponse->Error) {
                    throw new Exception($ReturnResponse->Message);
                }

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = 'Deleted Successfully';
                $this->EndReturnData->List = $ReturnResponse->List;
                $this->EndReturnData->Pagination = $ReturnResponse->Pagination;

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

            $ReturnResponse = $this->SizeTablePagination($pageNo, $limit, $offset, $Filter);
            if($ReturnResponse->Error) {
                throw new Exception($ReturnResponse->Message);
            }

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->List = $ReturnResponse->List;
            $this->EndReturnData->SizesCount = $ReturnResponse->SizesCount;
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

    public function SizeTablePagination($pageNo, $limit, $offset, $Filter) {

        $this->PagReturnData = new stdClass();
		try {

			$this->load->model('products_model');
            $this->pageData['SerialNumber'] = $offset;
            $this->pageData['SizesList'] = $this->products_model->getSizesList($limit, $offset, $Filter, 0);
            $SizesCount = $this->products_model->getSizesList($limit, $offset, $Filter, 1);

			$config['base_url'] = '/products/getSizesDetails/';
            $config['use_page_numbers'] = TRUE;
            $config['total_rows'] = $SizesCount;
            $config['per_page'] = $limit;

            $config['result_count'] = pageResultCount($pageNo, $limit, $SizesCount);
            $this->pagination->initialize($config);

            $this->PagReturnData->Error = false;
            $this->PagReturnData->List = $this->load->view('products/sizes/list', $this->pageData, TRUE);
			$this->PagReturnData->SizesCount = $SizesCount;
            $this->PagReturnData->Pagination = $this->pagination->create_links();

        } catch (Exception $e) {
            $this->PagReturnData->Error = TRUE;
            $this->PagReturnData->Message = $e->getMessage();
        }

		return $this->PagReturnData;

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

                $ReturnResponse = $this->SizeTablePagination($pageNo, $limit, $offset, $Filter);
                if($ReturnResponse->Error) {
                    throw new Exception($ReturnResponse->Message);
                }

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = 'Created Successfully';
                $this->EndReturnData->List = $ReturnResponse->List;
                $this->EndReturnData->Pagination = $ReturnResponse->Pagination;

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

                $ReturnResponse = $this->SizeTablePagination($pageNo, $limit, $offset, $Filter);
                if($ReturnResponse->Error) {
                    throw new Exception($ReturnResponse->Message);
                }

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = 'Updated Successfully';
                $this->EndReturnData->List = $ReturnResponse->List;
                $this->EndReturnData->Pagination = $ReturnResponse->Pagination;

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

                $ReturnResponse = $this->SizeTablePagination($pageNo, $limit, $offset, $Filter);
                if($ReturnResponse->Error) {
                    throw new Exception($ReturnResponse->Message);
                }

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = 'Deleted Successfully';
                $this->EndReturnData->List = $ReturnResponse->List;
                $this->EndReturnData->Pagination = $ReturnResponse->Pagination;

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

            $ReturnResponse = $this->BrandTablePagination($pageNo, $limit, $offset, $Filter);
            if($ReturnResponse->Error) {
                throw new Exception($ReturnResponse->Message);
            }

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->List = $ReturnResponse->List;
            $this->EndReturnData->BrandsCount = $ReturnResponse->BrandsCount;
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

    public function BrandTablePagination($pageNo, $limit, $offset, $Filter) {

        $this->PagReturnData = new stdClass();
		try {

			$this->load->model('products_model');
            $this->pageData['SerialNumber'] = $offset;
            $this->pageData['BrandsList'] = $this->products_model->getBrandsList($limit, $offset, $Filter, 0);
            $BrandsCount = $this->products_model->getBrandsList($limit, $offset, $Filter, 1);

			$config['base_url'] = '/products/getBrandsDetails/';
            $config['use_page_numbers'] = TRUE;
            $config['total_rows'] = $BrandsCount;
            $config['per_page'] = $limit;

            $config['result_count'] = pageResultCount($pageNo, $limit, $BrandsCount);
            $this->pagination->initialize($config);

            $this->PagReturnData->Error = FALSE;
            $this->PagReturnData->List = $this->load->view('products/brands/list', $this->pageData, TRUE);
			$this->PagReturnData->BrandsCount = $BrandsCount;
            $this->PagReturnData->Pagination = $this->pagination->create_links();

        } catch (Exception $e) {
            $this->PagReturnData->Error = TRUE;
            $this->PagReturnData->Message = $e->getMessage();
        }

		return $this->PagReturnData;

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

                $ReturnResponse = $this->BrandTablePagination($pageNo, $limit, $offset, $Filter);
                if($ReturnResponse->Error) {
                    throw new Exception($ReturnResponse->Message);
                }

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = 'Created Successfully';
                $this->EndReturnData->List = $ReturnResponse->List;
                $this->EndReturnData->Pagination = $ReturnResponse->Pagination;

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

                $ReturnResponse = $this->BrandTablePagination($pageNo, $limit, $offset, $Filter);
                if($ReturnResponse->Error) {
                    throw new Exception($ReturnResponse->Message);
                }

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = 'Updated Successfully';
                $this->EndReturnData->List = $ReturnResponse->List;
                $this->EndReturnData->Pagination = $ReturnResponse->Pagination;

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

                $ReturnResponse = $this->BrandTablePagination($pageNo, $limit, $offset, $Filter);
                if($ReturnResponse->Error) {
                    throw new Exception($ReturnResponse->Message);
                }

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = 'Deleted Successfully';
                $this->EndReturnData->List = $ReturnResponse->List;
                $this->EndReturnData->Pagination = $ReturnResponse->Pagination;            

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

}