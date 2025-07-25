<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Globally extends CI_Controller {

    public $pageData = array();
    private $EndReturnData;

    public function __construct() {
        parent::__construct();

    }

    public function index() {

    }

    public function getCountryInfo() {

        $this->EndReturnData = new stdClass();
		try {
            
            $this->load->model('global_model');
            $GetCountryInfo = $this->global_model->getCountryInfo();
            if($GetCountryInfo->Error === FALSE) {
                $this->EndReturnData->Data = $GetCountryInfo->Data;
            } else {
                throw new Exception($GetCountryInfo->Message);
            }

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Data Retrieved Successfully';

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

    public function getStateCityOfCountry() {

        $this->EndReturnData = new stdClass();
		try {

            $CountryCode = $this->input->post('CountryCode');
            if($CountryCode) {

                $this->load->model('global_model');

                $StateInfo = $this->global_model->getStateofCountry($CountryCode);
                if($StateInfo->Error === FALSE) {
                    $this->EndReturnData->StateInfo = $StateInfo->Data;
                }

                $CityInfo = $this->global_model->getCityofCountry($CountryCode);
                if($CityInfo->Error === FALSE) {
                    $this->EndReturnData->CityInfo = $CityInfo->Data;
                }

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = 'Data Retrieved Successfully';

            } else {
                throw new Exception('Country Code information is missing.');
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

    public function getStateofCountry() {

        $this->EndReturnData = new stdClass();
		try {

            $CountryCode = $this->input->post('CountryCode');
            if($CountryCode) {
                
                $this->load->model('global_model');
                $GetStateInfo = $this->global_model->getStateofCountry($CountryCode);
                if($GetStateInfo->Error === FALSE) {
                    $this->EndReturnData->Data = $GetStateInfo->Data;
                }

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = 'Data Retrieved Successfully';

            } else {
                throw new Exception('Country Code information is missing.');
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

    public function getCityofCountry() {

        $this->EndReturnData = new stdClass();
		try {

            $CountryCode = $this->input->post('CountryCode');
            if($CountryCode) {

                $this->load->model('global_model');
                $GetCityInfo = $this->global_model->getCityofCountry($CountryCode);
                if($GetCityInfo->Error === FALSE) {
                    $this->EndReturnData->Data = $GetCityInfo->Data;
                }

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = 'Data Retrieved Successfully';

            } else {
                throw new Exception('Country Code information is missing.');
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

    public function getStorageTypeInfo() {

        $this->EndReturnData = new stdClass();
		try {

            $this->load->model('global_model');
            $GetStorageType = $this->global_model->getStorageTypeData();
            if($GetStorageType->Error === FALSE) {
                $this->EndReturnData->Data = $GetStorageType->Data;
            }

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Data Retrieved Successfully';

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

    public function updatePageSettings() {

        $this->EndReturnData = new stdClass();
		try {

            $AllColumnsInPage = $this->input->post('InPageAllColumns');
            if(!empty($AllColumnsInPage)) {

                $updateDataArr = [];

                $AllCmnIds = explode(',', $AllColumnsInPage);
                foreach($AllCmnIds as $CKey => $CVal) {
                    $updateDataArr[] = [
                        'ViewDataUID' => $CVal,
                        'IsMainPageApplicable' => isset($this->input->post('MainPageFld')[$CVal]) ? 1 : 0,
                        'MainPageOrder' => (isset($this->input->post('MainPageFld')[$CVal]) && isset($this->input->post('MainPageFldSort')[$CVal])) ? $this->input->post('MainPageFldSort')[$CVal] : 1000,
                        'IsPrintPreviewApplicable' => isset($this->input->post('PrintPageFld')[$CVal]) ? 1 : 0,
                        'PrintPreviewOrder' => (isset($this->input->post('PrintPageFld')[$CVal]) && isset($this->input->post('PrintPageFldSort')[$CVal])) ? $this->input->post('PrintPageFldSort')[$CVal] : 1000,
                        'IsExportCsvApplicable' => isset($this->input->post('ExpCsvFld')[$CVal]) ? 1 : 0,
                        'ExportCsvOrder' => (isset($this->input->post('ExpCsvFld')[$CVal]) && isset($this->input->post('ExpCsvFldSort')[$CVal])) ? $this->input->post('ExpCsvFldSort')[$CVal] : 1000,
                        'IsExportExcelApplicable' => isset($this->input->post('ExpXlFld')[$CVal]) ? 1 : 0,
                        'ExportExcelOrder' => (isset($this->input->post('ExpXlFld')[$CVal]) && isset($this->input->post('ExpXlFldSort')[$CVal])) ? $this->input->post('ExpXlFldSort')[$CVal] : 1000,
                        'IsExportPdfApplicable' => isset($this->input->post('ExpPdfFld')[$CVal]) ? 1 : 0,
                        'ExportPdfOrder' => (isset($this->input->post('ExpPdfFld')[$CVal]) && isset($this->input->post('ExpPdfFldSort')[$CVal])) ? $this->input->post('ExpPdfFldSort')[$CVal] : 1000,
                    ];
                }

                $this->load->model('dbwrite_model');
                $InsertDataResp = $this->dbwrite_model->updateBatchData('Modules', 'ViewDataTbl', $updateDataArr, 'ViewDataUID');
                if($InsertDataResp->Error) {
                    throw new Exception($InsertDataResp->Message);
                }

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = "Successfully Updated";

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

    public function getPrintPreviewDetails() {

        $this->EndReturnData = new stdClass();
		try {

            $ModuleId = isset($_GET['ModuleId']) ? $_GET['ModuleId'] : 0;
            if($ModuleId > 0) {

                $Filter = isset($_GET['Filter']) ? json_decode($_GET['Filter'], TRUE) : [];
                $WhereInData = isset($_GET['ExportIds']) ? ['ExportIds' => explode(',', base64_decode($_GET['ExportIds']))] : [];

                $DataResp = $this->globalservice->getModulePageColumnDetails($ModuleId, 'PrintPage', $Filter, $WhereInData, 0, 0, 0);
                if($DataResp->Error === FALSE) {

                    $this->pageData['ModuleInfo'] = $DataResp->ModuleInfo;
                    $this->pageData['ViewColumns'] = $DataResp->ViewColumns;
                    $this->pageData['Aggregates'] = $DataResp->Aggregates;
                    $this->pageData['List'] = $DataResp->DataLists;

                    $this->EndReturnData->Error = FALSE;
                    $this->EndReturnData->HtmlData = $this->load->view('common/print/printpreview', $this->pageData, TRUE);

                } else {
                    throw new Exception('No Records Found.!');
                }

            } else {
                throw new Exception('Oops! Missing Module Information.');
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

    public function exportModuleDataDetails() {

        $this->EndReturnData = new stdClass();
		try {

            $ModuleId = isset($_GET['ModuleId']) ? $_GET['ModuleId'] : 0;
            if($ModuleId > 0) {

                $Type = isset($_GET['Type']) ? $_GET['Type'] : '';
                if(!empty($Type)) {

                    if($Type == 'CSV') {
                        $PageType = 'CsvPage';
                    } else if($Type == 'Excel') {
                        $PageType = 'ExcelPage';
                    } else if($Type == 'Pdf') {
                        $PageType = 'PdfPage';
                    }

                    $Filter = isset($_GET['Filter']) ? json_decode($_GET['Filter'], TRUE) : [];
                    $WhereInData = isset($_GET['ExportIds']) ? ['ExportIds' => explode(',', base64_decode($_GET['ExportIds']))] : [];
                    
                    $DataResp = $this->globalservice->getModulePageColumnDetails($ModuleId, $PageType, $Filter, $WhereInData, 0, 0, 0);
                    if($DataResp->Error === FALSE) {                        

                        $FileName = isset($_GET['FileName']) ? $_GET['FileName'] : 'NewFile';
                        $SheetName = isset($_GET['SheetName']) ? $_GET['SheetName'] : 'NewSheet';
                        
                        if($Type == 'CSV') {

                            $this->globalservice->exportCSV($FileName, $DataResp->ViewColumns, $DataResp->DataLists, $DataResp->Aggregates);
                            exit;

                        } else if($Type == 'Excel') {

                            $this->globalservice->exportExcel($FileName, $SheetName, $DataResp->ViewColumns, $DataResp->DataLists, $DataResp->Aggregates);
                            exit;

                        } else if($Type == 'Pdf') {

                            $this->globalservice->exportPdf($FileName, $SheetName, $DataResp->ViewColumns, $DataResp->DataLists, $DataResp->Aggregates);
                            exit;

                        }

                    } else {
                        throw new Exception($DataResp->Message);
                    }

                } else {
                    throw new Exception('Oops! Something went wrong.');
                }

            } else {
                throw new Exception('Oops! Missing Module Information.');
            }

        } catch (Exception $e) {
            redirect('dashboard');
        }

    }

}