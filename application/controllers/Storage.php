<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Storage extends CI_Controller {

    public $pageData = array();
    private $EndReturnData;

    public function __construct() {
        parent::__construct();

    }

    public function index() {

        try {

            $GeneralSettings = $this->redis_cache->get('Redis_UserGenSettings')->Value ?? new stdClass();
            if($GeneralSettings->EnableStorage != 1) {
                redirect('dashboard', 'refresh');
                return;
            }

            $controllerName = strtolower($this->router->fetch_class());
            $getModuleInfo = $this->redis_cache->get('Redis_UserModuleInfo')->Value ?? [];
            $ModuleInfo = array_values(array_filter($getModuleInfo, fn($m) => $m->ControllerName === $controllerName));
            if (empty($ModuleInfo)) {
                throw new Exception("Module information not found for controller: {$controllerName}");
            }

            $this->pageData['ModuleId'] = $ModuleInfo[0]->ModuleUID;

            $limit = $GeneralSettings->RowLimit ?? 10;
            $this->pageData['JwtData']->GenSettings = $GeneralSettings;

            $ReturnResponse = $this->globalservice->getBaseMainPageTablePagination($this->pageData['ModuleId'], 0, $limit, 0, [], [], 'Index');
            if ($ReturnResponse->Error) throw new Exception($ReturnResponse->Message);
            
            $this->pageData['ModColumnData'] = $ReturnResponse->DispViewColumns;
            $this->pageData['ModRowData'] = $ReturnResponse->RecordHtmlData;
            $this->pageData['ModPagination'] = $ReturnResponse->Pagination;
            $this->pageData['DispSettColumnDetails'] = $ReturnResponse->DispSettingsViewColumns;

            $this->load->model('global_model');
            $this->pageData['StorageTypeInfo'] = $this->global_model->getStorageTypeData()->Data ?? [];
            
            $this->load->view('storage/view', $this->pageData);

        } catch (Exception $e) {
            redirect('dashboard', 'refresh');
        }

    }

    public function getAllStorage() {

        $this->EndReturnData = new stdClass();
		try {

            $this->load->model('storage_model');
            $getAllStorage['Storage'] = $this->storage_model->getStorageDetails([]);
            $this->EndReturnData->HtmlData = $this->load->view('products/items/storagefilter', $getAllStorage, TRUE);
            
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Retrieved Successfully';

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		$this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    private function buildStorageFormData($postData, $isCreate = true) {
        $data = [
            'OrgUID'         => $this->pageData['JwtData']->User->OrgUID,
            'Name'           => getPostValue($postData, 'Name'),
            'ShortName'      => getPostValue($postData, 'ShortName') ?: null,
            'StorageTypeUID' => getPostValue($postData, 'StorageTypeUID', '', null),
            'Description'    => getPostValue($postData, 'Description') ?: null,
            'UpdatedBy'      => $this->pageData['JwtData']->User->UserUID,
            'UpdatedOn'      => time(),
        ];
        if ($isCreate) {
            $data['CreatedBy'] = $this->pageData['JwtData']->User->UserUID;
            $data['CreatedOn'] = time();
        }
        if (isset($data['StorageTypeUID']) && $data['StorageTypeUID'] !== null) {
            $data['StorageTypeUID'] = (int) $data['StorageTypeUID'];
        }
        return $data;
    }

    public function addStorageData() {

        $this->EndReturnData = new stdClass();
		try {

            $GeneralSettings = ($this->redis_cache->get('Redis_UserGenSettings')->Value) ?? new stdClass();
            $this->pageData['JwtData']->GenSettings = $GeneralSettings;

            $postData = $this->input->post();
            $this->load->model('formvalidation_model');
            $ErrorInForm = $this->formvalidation_model->storageValidateForm($postData);
            if(!empty($ErrorInForm)) {
                throw new Exception($ErrorInForm);
            }

            $this->load->model('dbwrite_model');
            $InsertDataResp = $this->dbwrite_model->insertData('Products', 'StorageTbl', $this->buildStorageFormData($postData, true));
            if($InsertDataResp->Error) {
                throw new Exception($InsertDataResp->Message);
            }

            $StorageUID = $InsertDataResp->ID;
            
            if(isset($_FILES['UploadImage'])) {
                $UploadResp = $this->globalservice->fileUploadService($_FILES['UploadImage'], 'products/storage/images/', 'Image', ['Products', 'StorageTbl', array('StorageUID' => $StorageUID)]);
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

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function updateStorageData() {

        $this->EndReturnData = new stdClass();
		try {

            $GeneralSettings = ($this->redis_cache->get('Redis_UserGenSettings')->Value) ?? new stdClass();
            $this->pageData['JwtData']->GenSettings = $GeneralSettings;

            $postData = $this->input->post();
            $this->load->model('formvalidation_model');
            $ErrorInForm = $this->formvalidation_model->storageValidateForm($postData);
            if(!empty($ErrorInForm)) {
                throw new Exception($ErrorInForm);
            }
            
            $StorageUID = getPostValue($postData, 'StorageUID');

            $storageFormData = $this->buildStorageFormData($postData, false);
            if (!empty($postData['ImageRemoved'])) $storageFormData['Image'] = NULL;

            $this->load->model('dbwrite_model');
            $updateDataResp = $this->dbwrite_model->updateData('Products', 'StorageTbl', $storageFormData, array('StorageUID' => $StorageUID));
            if($updateDataResp->Error) {
                throw new Exception($updateDataResp->Message);
            }
            
            if(isset($_FILES['UploadImage'])) {
                $UploadResp = $this->globalservice->fileUploadService($_FILES['UploadImage'], 'products/storage/images/', 'Image', ['Products', 'StorageTbl', array('StorageUID' => $StorageUID)]);
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

    public function deleteStorageDetails() {

        $this->EndReturnData = new stdClass();
		try {

            $StorageUID = $this->input->post('StorageUID');
            if(!$StorageUID) {
                throw new Exception('Storage Information is Missing to Delete');
            }

            /** Cross Check with Products */
            $this->load->model('products_model');
            $ExistsInProducts = $this->products_model->getProductsDetails(['Products.StorageUID' => $StorageUID]);
            if(!empty($ExistsInProducts) && sizeof($ExistsInProducts) > 0) {
                throw new Exception('Storage is linked to Product.');
            }
            
            $this->load->model('dbwrite_model');                
            $UpdateResp = $this->dbwrite_model->updateData('Products', 'StorageTbl', $this->globalservice->baseDeleteArrayDetails(), array('StorageUID' => $StorageUID));
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

    public function deleteBulkStorage() {

        $this->EndReturnData = new stdClass();
		try {

            $StorageUIDs = $this->input->post('StorageUIDs[]');
            if(empty($StorageUIDs)) {
                throw new Exception('Storage Information is Missing to Delete');
            }

            /** Cross Check with Products */
            $this->load->model('products_model');
            $ExistsInProducts = $this->products_model->getProductsDetails([], '', ['Products.StorageUID' => $StorageUIDs]);
            if(!empty($ExistsInProducts) && sizeof($ExistsInProducts) > 0) {
                throw new Exception('Storage is linked to Product.');
            }

            $this->load->model('dbwrite_model');
            $UpdateResp = $this->dbwrite_model->updateData('Products', 'StorageTbl', $this->globalservice->baseDeleteArrayDetails(), [], array('StorageUID' => $StorageUIDs));
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

}