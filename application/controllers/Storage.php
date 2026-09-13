<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Storage extends MY_Controller {

    public $pageData = array();
    protected $EndReturnData;

    public function __construct() {
        parent::__construct();
    }

    private function buildStorageFilter(array $filter, int $orgUID): array {
        $where = ['Storage.OrgUID' => $orgUID];
        if (!empty($filter['StorageType'])) {
            /* handled as direct_query below — kept here for future expansion */
        }
        return $where;
    }

    private function buildStorageDirectQuery(array $filter, string $alias = 'Storage'): string {
        $parts = [];
        if (!empty($filter['SearchAllData'])) {
            $s = $this->db->escape_like_str($filter['SearchAllData']);
            $parts[] = "($alias.Name LIKE '%$s%' OR $alias.ShortName LIKE '%$s%' OR $alias.Description LIKE '%$s%')";
        }
        if (!empty($filter['StorageType'])) {
            $ids = implode(',', array_map('intval', (array)$filter['StorageType']));
            $parts[] = "$alias.StorageTypeUID IN ($ids)";
        }
        return implode(' AND ', $parts);
    }

    private function renderStorageList(int $orgUID, array $filter, int $pageNo, int $limit): object {
        $offset    = $pageNo > 0 ? ($pageNo - 1) * $limit : 0;
        $where     = $this->buildStorageFilter($filter, $orgUID);
        $directQ   = $this->buildStorageDirectQuery($filter);

        $this->load->model('storage_model');
        $data  = $this->storage_model->getStorageDetails($where, $limit, $offset, $directQ);
        $total = $this->storage_model->getTotalStorageCount($where, $directQ);

        $rowHtml = $this->load->view('storage/list/storage_list', [
            'DataLists'   => $data,
            'StartFrom'   => $offset,
            'GenSettings' => $this->pageData['JwtData']->GenSettings ?? new stdClass(),
        ], TRUE);

        $pagination = $this->globalservice->buildPagePaginationHtml('/storage/getStorageList', $total, $pageNo ?: 1, $limit);

        $result = new stdClass();
        $result->List       = $rowHtml;
        $result->Pagination = $pagination;
        $result->TotalCount = $total;
        return $result;
    }

    public function index(): void {

        if (!$this->_loadPageTitle()) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }

        try {

            $GeneralSettings = $this->pageData['JwtData']->GenSettings ?? new stdClass();
            if ($GeneralSettings->EnableStorage != 1) {
                redirect('dashboard', 'refresh');
                return;
            }

            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            $limit  = (int) ($GeneralSettings->RowLimit ?? 10);

            $rendered = $this->renderStorageList($orgUID, [], 1, $limit);

            $this->load->model('global_model');
            $this->pageData['StorageTypeInfo'] = $this->global_model->getStorageTypeData()->Data ?? [];
            $this->pageData['ModRowData']      = $rendered->List;
            $this->pageData['ModPagination']   = $rendered->Pagination;
            $this->pageData['ModAllCount']     = $rendered->TotalCount;

            $this->load->view('storage/view', $this->pageData);

        } catch (Exception $e) {
            $this->notifyError('Storage::index', $e);
            redirect('dashboard', 'refresh');
        }

    }

    public function getStorageList(int $pageNo = 1): void {

        $this->EndReturnData = new stdClass();
        try {

            $orgUID  = (int) $this->pageData['JwtData']->Org->OrgUID;
            $limit   = (int) ($this->input->post('RowLimit') ?? $this->pageData['JwtData']->GenSettings->RowLimit ?? 10);
            $filter  = $this->input->post('Filter') ?? [];

            $rendered = $this->renderStorageList($orgUID, $filter, (int)$pageNo, $limit);

            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->List       = $rendered->List;
            $this->EndReturnData->Pagination = $rendered->Pagination;
            $this->EndReturnData->TotalCount = $rendered->TotalCount;

        } catch (Exception $e) {
            $this->notifyError('Storage::getStorageList', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function getAllStorage(): void {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('storage_model');
            $getAllStorage['Storage'] = $this->storage_model->getStorageDetails([]);
            $this->EndReturnData->HtmlData = $this->load->view('products/items/storagefilter', $getAllStorage, TRUE);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Retrieved Successfully';

        } catch (Exception $e) {
            $this->notifyError('Storage::getAllStorage', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    private function buildStorageFormData(array $postData, bool $isCreate = true): array {
        $data = [
            'OrgUID'         => $this->pageData['JwtData']->Org->OrgUID,
            'Name'           => getPostValue($postData, 'Name'),
            'ShortName'      => getPostValue($postData, 'ShortName') ?: null,
            'StorageTypeUID' => getPostValue($postData, 'StorageTypeUID', '', null),
            'Description'    => getPostValue($postData, 'Description') ?: null,
            'UpdatedBy'      => $this->pageData['JwtData']->User->UserUID,
        ];
        if ($isCreate) {
            $data['CreatedBy'] = $this->pageData['JwtData']->User->UserUID;
        }
        if (isset($data['StorageTypeUID']) && $data['StorageTypeUID'] !== null) {
            $data['StorageTypeUID'] = (int) $data['StorageTypeUID'];
        }
        return $data;
    }

    public function addStorageData(): void {

        $this->EndReturnData = new stdClass();
        try {

            $postData = $this->input->post();
            $this->load->model('formvalidation_model');
            $ErrorInForm = $this->formvalidation_model->storageValidateForm($postData);
            if (!empty($ErrorInForm)) {
                throw new Exception($ErrorInForm);
            }

            $this->load->model('dbwrite_model');
            $InsertDataResp = $this->dbwrite_model->insertData('Products', 'StorageTbl', $this->buildStorageFormData($postData, true));
            if ($InsertDataResp->Error) {
                throw new Exception($InsertDataResp->Message);
            }

            $StorageUID = $InsertDataResp->ID;

            if (isset($_FILES['UploadImage'])) {
                $UploadResp = $this->globalservice->fileUploadService($_FILES['UploadImage'], 'products/storage/images/', 'Image', ['Products', 'StorageTbl', ['StorageUID' => $StorageUID]]);
                if ($UploadResp->Error === TRUE) {
                    throw new Exception($UploadResp->Message);
                }
            }

            $orgUID  = (int) $this->pageData['JwtData']->Org->OrgUID;
            $limit   = (int) ($this->input->post('RowLimit') ?? $this->pageData['JwtData']->GenSettings->RowLimit ?? 10);
            $pageNo  = (int) ($this->input->post('PageNo') ?? 1);
            $filter  = $this->input->post('Filter') ?? [];

            $rendered = $this->renderStorageList($orgUID, $filter, $pageNo, $limit);

            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Message    = 'Created Successfully';
            $this->EndReturnData->List       = $rendered->List;
            $this->EndReturnData->Pagination = $rendered->Pagination;

        } catch (Exception $e) {
            $this->notifyError('Storage::addStorageData', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function updateStorageData(): void {

        $this->EndReturnData = new stdClass();
        try {

            $postData   = $this->input->post();
            $this->load->model('formvalidation_model');
            $ErrorInForm = $this->formvalidation_model->storageValidateForm($postData);
            if (!empty($ErrorInForm)) {
                throw new Exception($ErrorInForm);
            }

            $StorageUID       = getPostValue($postData, 'StorageUID');
            $storageFormData  = $this->buildStorageFormData($postData, false);
            if (!empty($postData['ImageRemoved'])) {
                $storageFormData['Image'] = NULL;
            }

            $this->load->model('dbwrite_model');
            $updateDataResp = $this->dbwrite_model->updateData('Products', 'StorageTbl', $storageFormData, ['StorageUID' => $StorageUID]);
            if ($updateDataResp->Error) {
                throw new Exception($updateDataResp->Message);
            }

            if (isset($_FILES['UploadImage'])) {
                $UploadResp = $this->globalservice->fileUploadService($_FILES['UploadImage'], 'products/storage/images/', 'Image', ['Products', 'StorageTbl', ['StorageUID' => $StorageUID]]);
                if ($UploadResp->Error === TRUE) {
                    throw new Exception($UploadResp->Message);
                }
            }

            $orgUID  = (int) $this->pageData['JwtData']->Org->OrgUID;
            $limit   = (int) ($this->input->post('RowLimit') ?? $this->pageData['JwtData']->GenSettings->RowLimit ?? 10);
            $pageNo  = (int) ($this->input->post('PageNo') ?? 1);
            $filter  = $this->input->post('Filter') ?? [];

            $rendered = $this->renderStorageList($orgUID, $filter, $pageNo, $limit);

            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Message    = 'Updated Successfully';
            $this->EndReturnData->List       = $rendered->List;
            $this->EndReturnData->Pagination = $rendered->Pagination;

        } catch (Exception $e) {
            $this->notifyError('Storage::updateStorageData', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function deleteStorageDetails(): void {

        $this->EndReturnData = new stdClass();
        try {

            $StorageUID = $this->input->post('StorageUID');
            if (!$StorageUID) {
                throw new Exception('Storage Information is Missing to Delete');
            }

            $this->load->model('products_model');
            $ExistsInProducts = $this->products_model->getProductsDetails(['Products.StorageUID' => $StorageUID]);
            if (!empty($ExistsInProducts) && sizeof($ExistsInProducts) > 0) {
                throw new Exception('Storage is linked to Product.');
            }

            $this->load->model('dbwrite_model');
            $UpdateResp = $this->dbwrite_model->updateData('Products', 'StorageTbl', $this->globalservice->baseDeleteArrayDetails(), ['StorageUID' => $StorageUID]);
            if ($UpdateResp->Error) {
                throw new Exception($UpdateResp->Message);
            }

            $orgUID  = (int) $this->pageData['JwtData']->Org->OrgUID;
            $limit   = (int) ($this->input->post('RowLimit') ?? $this->pageData['JwtData']->GenSettings->RowLimit ?? 10);
            $pageNo  = (int) ($this->input->post('PageNo') ?? 1);
            $filter  = $this->input->post('Filter') ?? [];

            $rendered = $this->renderStorageList($orgUID, $filter, $pageNo, $limit);

            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Message    = 'Deleted Successfully';
            $this->EndReturnData->List       = $rendered->List;
            $this->EndReturnData->Pagination = $rendered->Pagination;

        } catch (Exception $e) {
            $this->notifyError('Storage::deleteStorageDetails', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function deleteBulkStorage(): void {

        $this->EndReturnData = new stdClass();
        try {

            $StorageUIDs = $this->input->post('StorageUIDs[]');
            if (empty($StorageUIDs)) {
                throw new Exception('Storage Information is Missing to Delete');
            }

            $this->load->model('products_model');
            $ExistsInProducts = $this->products_model->getProductsDetails([], '', ['Products.StorageUID' => $StorageUIDs]);
            if (!empty($ExistsInProducts) && sizeof($ExistsInProducts) > 0) {
                throw new Exception('Storage is linked to Product.');
            }

            $this->load->model('dbwrite_model');
            $UpdateResp = $this->dbwrite_model->updateData('Products', 'StorageTbl', $this->globalservice->baseDeleteArrayDetails(), [], ['StorageUID' => $StorageUIDs]);
            if ($UpdateResp->Error) {
                throw new Exception($UpdateResp->Message);
            }

            $orgUID  = (int) $this->pageData['JwtData']->Org->OrgUID;
            $limit   = (int) ($this->input->post('RowLimit') ?? $this->pageData['JwtData']->GenSettings->RowLimit ?? 10);
            $pageNo  = (int) ($this->input->post('PageNo') ?? 1);
            $filter  = $this->input->post('Filter') ?? [];

            $rendered = $this->renderStorageList($orgUID, $filter, $pageNo, $limit);

            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Message    = 'Deleted Successfully';
            $this->EndReturnData->List       = $rendered->List;
            $this->EndReturnData->Pagination = $rendered->Pagination;

        } catch (Exception $e) {
            $this->notifyError('Storage::deleteBulkStorage', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

}
