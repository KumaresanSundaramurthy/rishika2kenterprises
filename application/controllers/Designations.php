<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Designations extends MY_Controller {

    public  $pageData = [];
    private $EndReturnData;

    public function __construct() { parent::__construct(); }

    private function _fetchTableData($pageNo, $limit, $filter = []) {
        $offset = max(0, ($pageNo - 1) * $limit);
        $this->load->model('users_model');
        $result  = $this->users_model->getDesignationListPaginated($this->_orgUID(), $limit, $offset, $filter);
        $rowHtml = $this->load->view('hrms/designations/list', ['DataLists' => $result->rows, 'SerialNumber' => $offset, 'JwtData' => $this->pageData['JwtData']], TRUE);
        $r = new stdClass();
        $r->RecordHtmlData = $rowHtml;
        $r->Pagination     = $this->globalservice->buildPagePaginationHtml('/designations/getPageDetails', $result->totalCount, $pageNo, $limit);
        $r->TotalCount     = $result->totalCount;
        return $r;
    }

    public function index() {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $limit = $this->_rowLimit();
            $pd    = $this->_fetchTableData(1, $limit);
            $this->pageData['ModRowData']    = $pd->RecordHtmlData;
            $this->pageData['ModPagination'] = $pd->Pagination;
            $this->load->view('hrms/designations/view', $this->pageData);
        } catch (Exception $e) { redirect('dashboard', 'refresh'); }
    }

    public function getPageDetails($pageNo = 0) {
        $this->EndReturnData = new stdClass();
        try {
            $pd = $this->_fetchTableData(max(1, (int)$pageNo), $this->_rowLimit(), $this->input->post('Filter') ?: []);
            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->RecordHtmlData = $pd->RecordHtmlData;
            $this->EndReturnData->Pagination     = $pd->Pagination;
            $this->EndReturnData->TotalCount     = $pd->TotalCount;
        } catch (Exception $e) { $this->EndReturnData->Error = TRUE; $this->EndReturnData->Message = $e->getMessage(); }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function save() {
        $this->EndReturnData = new stdClass();
        try {
            $p   = $this->input->post();
            $uid = (int)($p['DesignationUID'] ?? 0);
            if (empty(trim($p['DesignationName'] ?? ''))) throw new Exception('Designation name is required.');
            $this->load->model('dbwrite_model');
            $data = ['OrgUID' => $this->_orgUID(), 'DesignationName' => trim($p['DesignationName']), 'Description' => trim($p['Description'] ?? ''), 'IsActive' => 1, 'UpdatedBy' => $this->_userUID()];
            if ($uid === 0) {
                $data['CreatedBy'] = $this->_userUID();
                $res = $this->dbwrite_model->insertData('Organisation', 'DesignationTbl', $data);
            } else {
                $res = $this->dbwrite_model->updateData('Organisation', 'DesignationTbl', $data, ['DesignationUID' => $uid, 'OrgUID' => $this->_orgUID()]);
            }
            if ($res->Error) throw new Exception($res->Message);
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = $uid ? 'Updated.' : 'Created.';
        } catch (Exception $e) { $this->EndReturnData->Error = TRUE; $this->EndReturnData->Message = $e->getMessage(); }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function delete() {
        $this->EndReturnData = new stdClass();
        try {
            $uid = (int)$this->input->post('DesignationUID');
            if (!$uid) throw new Exception('Invalid.');
            $this->load->model('dbwrite_model');
            $res = $this->dbwrite_model->updateData('Organisation', 'DesignationTbl', ['IsDeleted' => 1, 'UpdatedBy' => $this->_userUID()], ['DesignationUID' => $uid, 'OrgUID' => $this->_orgUID()]);
            if ($res->Error) throw new Exception($res->Message);
            $this->EndReturnData->Error = FALSE; $this->EndReturnData->Message = 'Deleted.';
        } catch (Exception $e) { $this->EndReturnData->Error = TRUE; $this->EndReturnData->Message = $e->getMessage(); }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function getList() {
        $this->EndReturnData = new stdClass();
        try {
            $this->load->model('users_model');
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $this->users_model->getDesignationList($this->_orgUID());
        } catch (Exception $e) { $this->EndReturnData->Error = TRUE; $this->EndReturnData->Message = $e->getMessage(); }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }
}
