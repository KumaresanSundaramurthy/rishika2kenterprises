<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Storage extends CI_Controller {

    public $pageData = array();
    private $EndReturnData;

    public function __construct() {
        parent::__construct();

    }

    public function index() {

        if($this->pageData['JwtData']->GenSettings->EnableStorage == 1) {

            $ControllerName = strtolower($this->router->fetch_class());

            $this->pageData['ModuleInfo'] = array_values(array_filter($this->pageData['JwtData']->ModuleInfo, function($module) use ($ControllerName) {
                return $module->ControllerName === $ControllerName;
            }));

            $this->pageData['ModuleId'] = $this->pageData['ModuleInfo'][0]->ModuleUID;

            $limit = isset($this->pageData['JwtData']->GenSettings->RowLimit) ? $this->pageData['JwtData']->GenSettings->RowLimit : 10;

            $ReturnResponse = $this->globalservice->getBaseMainPageTablePagination($this->pageData['ModuleId'], '/storage/getStorageDetails/', 'products/storage/list', 0, $limit, 0, [], []);
            if($ReturnResponse->Error) {
                throw new Exception($ReturnResponse->Message);
            }

            $this->pageData['ModDataList'] = $ReturnResponse->List;
            $this->pageData['ModDataUIDs'] = $ReturnResponse->UIDs;
            $this->pageData['ModDataPagination'] = $ReturnResponse->Pagination;
            $this->pageData['ColumnDetails'] = $ReturnResponse->AllViewColumns;
            
            $ItemColumns = array_filter($this->pageData['ColumnDetails'], function ($item) {
                return isset($item->IsMainPageApplicable) && $item->IsMainPageApplicable == 1;
            });
            usort($ItemColumns, function ($a, $b) {
                return $a->MainPageOrder <=> $b->MainPageOrder;
            });
            $this->pageData['ModuleColumns'] = $ItemColumns;

            $this->load->model('global_model');
            $getStrgTypeInfo = $this->global_model->getStorageTypeData();
            if($getStrgTypeInfo->Error === FALSE) {
                $this->pageData['StorageTypeInfo'] = $getStrgTypeInfo->Data;
            }
            
            $this->load->view('products/storage/view', $this->pageData);

        } else {
            redirect('dashboard', 'refresh');
        }

    }
    
    public function getStorageDetails($pageNo = 0) {

		$this->EndReturnData = new stdClass();
		try {

			$tablePagDataResp = $this->commonStorageTablePagination($pageNo);

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

    public function commonStorageTablePagination($pageNo = 0) {

        $limit = $this->input->post('RowLimit');
        $offset = ($pageNo != 0) ? (($pageNo - 1) * $limit) : $pageNo;
        $Filter = $this->input->post('Filter');
        $ModuleId = $this->input->post('ModuleId');

        $ReturnResponse = $this->globalservice->getBaseMainPageTablePagination($ModuleId, '/storage/getStorageDetails/', 'products/storage/list', $pageNo, $limit, $offset, $Filter, []);
        if($ReturnResponse->Error) {
            throw new Exception($ReturnResponse->Message);
        }

        return $ReturnResponse;

    }

    public function getAllStorage() {

        $this->EndReturnData = new stdClass();
		try {

            $this->load->model('storage_model');
            $getAllStorage['Storage'] = $this->storage_model->getStorageDetails([]);
            $this->EndReturnData->HtmlData = $this->load->view('products/storage/storagefilter', $getAllStorage, TRUE);
            
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Retrieved Successfully';

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

    public function addStorageData() {

        $this->EndReturnData = new stdClass();
		try {

            $this->load->model('formvalidation_model');

            $PostData = $this->input->post();
            $ErrorInForm = $this->formvalidation_model->storageValidateForm($PostData);
            if(empty($ErrorInForm)) {

                $this->load->model('dbwrite_model');

                $StorageUID = 0;
                $submitFormData = [
                    'Name' => $PostData['Name'],
                    'OrgUID' => $this->pageData['JwtData']->User->OrgUID,
                    'ShortName' => $PostData['ShortName'] ? $PostData['ShortName'] : NULL,
                    'StorageTypeUID' => $PostData['StorageTypeUID'],
                    'Description' => (isset($PostData['Description']) && !empty($PostData['Description'])) ? $PostData['Description'] : NULL,
                    'CreatedBy' => $this->pageData['JwtData']->User->UserUID,
                    'UpdatedBy' => $this->pageData['JwtData']->User->UserUID,
                    'CreatedOn' => time(),
                    'UpdatedOn' => time(),
                ];

                $InsertDataResp = $this->dbwrite_model->insertData('Products', 'StorageTbl', $submitFormData);
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
                $tablePagDataResp = $this->commonStorageTablePagination($pageNo);

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

    public function checkImageType($str = '') {
        return $this->globalservice->checkImageType($str);
    }

    public function updateStorageData() {

        $this->EndReturnData = new stdClass();
		try {

            $this->load->model('formvalidation_model');

            $PostData = $this->input->post();
            $ErrorInForm = $this->formvalidation_model->storageValidateForm($PostData);
            if(empty($ErrorInForm)) {

                $this->load->model('dbwrite_model');

                $StorageUID = $PostData['StorageUID'];
                $submitFormData = [
                    'Name' => $PostData['Name'],
                    'ShortName' => $PostData['ShortName'] ? $PostData['ShortName'] : NULL,
                    'StorageTypeUID' => $PostData['StorageTypeUID'],
                    'Description' => (isset($PostData['Description']) && !empty($PostData['Description'])) ? $PostData['Description'] : NULL,
                    'UpdatedBy' => $this->pageData['JwtData']->User->UserUID,
                    'UpdatedOn' => time(),
                ];

                $updateDataResp = $this->dbwrite_model->updateData('Products', 'StorageTbl', $submitFormData, array('StorageUID' => $StorageUID));
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
                $tablePagDataResp = $this->commonStorageTablePagination($pageNo);

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

    public function deleteStorageDetails() {

        $this->EndReturnData = new stdClass();
		try {

            $StorageUID = $this->input->post('StorageUID');
            if($StorageUID) {

                /** Cross Check with Products */
                $this->load->model('products_model');
                $ExistsInProducts = $this->products_model->getProductsDetails(['Products.StorageUID' => $StorageUID]);
                if(!empty($ExistsInProducts) && sizeof($ExistsInProducts) > 0) {
                    throw new Exception('Storage is linked to Product.');
                }

                $updateDelData = [
                    'IsDeleted' => 1,
                    'UpdatedBy' => $this->pageData['JwtData']->User->UserUID,
                    'UpdatedOn' => time(),
                ];

                $this->load->model('dbwrite_model');                
                $UpdateResp = $this->dbwrite_model->updateData('Products', 'StorageTbl', $updateDelData, array('StorageUID' => $StorageUID));
                if($UpdateResp->Error) {
                    throw new Exception($UpdateResp->Message);
                }

                $pageNo = $this->input->post('PageNo');
                $tablePagDataResp = $this->commonStorageTablePagination($pageNo);

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = 'Deleted Successfully';
                $this->EndReturnData->List = $tablePagDataResp->List;
                $this->EndReturnData->Pagination = $tablePagDataResp->Pagination;
                $this->EndReturnData->UIDs = $tablePagDataResp->UIDs;

            } else {
                throw new Exception('Storage Information is Missing to Delete');
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

    public function deleteBulkStorage() {

        $this->EndReturnData = new stdClass();
		try {

            $StorageUIDs = $this->input->post('StorageUIDs');
            if($StorageUIDs) {

                /** Cross Check with Products */
                $this->load->model('products_model');
                $ExistsInProducts = $this->products_model->getProductsDetails([], '', ['Products.StorageUID' => $StorageUIDs]);
                if(!empty($ExistsInProducts) && sizeof($ExistsInProducts) > 0) {
                    throw new Exception('Storage is linked to Product.');
                }

                $updateDelData = [
                    'IsDeleted' => 1,
                    'UpdatedBy' => $this->pageData['JwtData']->User->UserUID,
                    'UpdatedOn' => time(),
                ];

                $this->load->model('dbwrite_model');
                
                $UpdateResp = $this->dbwrite_model->updateData('Products', 'StorageTbl', $updateDelData, [], array('StorageUID' => $StorageUIDs));
                if($UpdateResp->Error) {
                    throw new Exception($UpdateResp->Message);
                }

                $pageNo = $this->input->post('PageNo');
                $tablePagDataResp = $this->commonStorageTablePagination($pageNo);

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = 'Deleted Successfully';
                $this->EndReturnData->List = $tablePagDataResp->List;
                $this->EndReturnData->Pagination = $tablePagDataResp->Pagination;
                $this->EndReturnData->UIDs = $tablePagDataResp->UIDs;

            } else {
                throw new Exception('Storage Information is Missing to Delete');
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