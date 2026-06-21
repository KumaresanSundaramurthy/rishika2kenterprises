<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Holidays extends MY_Controller {

    public  $pageData = [];
    private $EndReturnData;

    public function __construct() { parent::__construct(); }

    private function _orgUID()  { return (int)$this->pageData['JwtData']->Org->OrgUID; }
    private function _userUID() { return (int)$this->pageData['JwtData']->User->UserUID; }
    private function _limit()   { return (int)($this->pageData['JwtData']->GenSettings->RowLimit ?? 10); }

    private function _fetchTableData($pageNo, $limit, $filter = []) {
        $offset = max(0, ($pageNo - 1) * $limit);
        $this->load->model('users_model');
        $result  = $this->users_model->getHolidayListPaginated($this->_orgUID(), $limit, $offset, $filter);
        $rowHtml = $this->load->view('hrms/holidays/list', ['DataLists' => $result->rows, 'SerialNumber' => $offset, 'JwtData' => $this->pageData['JwtData']], TRUE);
        $r = new stdClass();
        $r->RecordHtmlData = $rowHtml;
        $r->Pagination     = $this->globalservice->buildPagePaginationHtml('/holidays/getPageDetails', $result->totalCount, $pageNo, $limit);
        $r->TotalCount     = $result->totalCount;
        return $r;
    }

    public function index() {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $pd = $this->_fetchTableData(1, $this->_limit(), ['Year' => date('Y')]);
            $this->pageData['ModRowData']    = $pd->RecordHtmlData;
            $this->pageData['ModPagination'] = $pd->Pagination;
            $this->pageData['CurrentYear']   = date('Y');
            $this->load->view('hrms/holidays/view', $this->pageData);
        } catch (Exception $e) { redirect('dashboard', 'refresh'); }
    }

    public function getPageDetails($pageNo = 0) {
        $this->EndReturnData = new stdClass();
        try {
            $filter = $this->input->post('Filter') ?: [];
            if (empty($filter['Year'])) $filter['Year'] = date('Y');
            $pd = $this->_fetchTableData(max(1, (int)$pageNo), $this->_limit(), $filter);
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
            $p      = $this->input->post();
            $uid    = (int)($p['HolidayUID'] ?? 0);
            $pageNo = max(1, (int)($p['CurrentPage'] ?? 1));
            $filter = is_array($p['Filter'] ?? null) ? $p['Filter'] : [];
            if (empty($filter['Year'])) $filter['Year'] = date('Y');
            if (empty(trim($p['HolidayName'] ?? ''))) throw new Exception('Holiday name is required.');
            if (empty($p['HolidayDate']))              throw new Exception('Holiday date is required.');
            $this->load->model('dbwrite_model');
            $data = ['OrgUID' => $this->_orgUID(), 'HolidayName' => trim($p['HolidayName']), 'HolidayDate' => $p['HolidayDate'], 'Description' => trim($p['Description'] ?? ''), 'IsOptional' => (int)($p['IsOptional'] ?? 0), 'IsActive' => 1, 'UpdatedBy' => $this->_userUID()];
            if ($uid === 0) {
                $data['CreatedBy'] = $this->_userUID();
                $res = $this->dbwrite_model->insertData('Organisation', 'HolidayTbl', $data);
            } else {
                $res = $this->dbwrite_model->updateData('Organisation', 'HolidayTbl', $data, ['HolidayUID' => $uid, 'OrgUID' => $this->_orgUID()]);
            }
            if ($res->Error) throw new Exception($res->Message);
            $pd = $this->_fetchTableData($pageNo, $this->_limit(), $filter);
            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->Message        = $uid ? 'Updated.' : 'Holiday added.';
            $this->EndReturnData->RecordHtmlData = $pd->RecordHtmlData;
            $this->EndReturnData->Pagination     = $pd->Pagination;
            $this->EndReturnData->TotalCount     = $pd->TotalCount;
        } catch (Exception $e) { $this->EndReturnData->Error = TRUE; $this->EndReturnData->Message = $e->getMessage(); }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function delete() {
        $this->EndReturnData = new stdClass();
        try {
            $p      = $this->input->post();
            $uid    = (int)($p['HolidayUID'] ?? 0);
            $pageNo = max(1, (int)($p['CurrentPage'] ?? 1));
            $filter = is_array($p['Filter'] ?? null) ? $p['Filter'] : [];
            if (empty($filter['Year'])) $filter['Year'] = date('Y');
            if (!$uid) throw new Exception('Invalid.');
            $this->load->model('dbwrite_model');
            $res = $this->dbwrite_model->updateData('Organisation', 'HolidayTbl', ['IsDeleted' => 1, 'UpdatedBy' => $this->_userUID()], ['HolidayUID' => $uid, 'OrgUID' => $this->_orgUID()]);
            if ($res->Error) throw new Exception($res->Message);
            $pd = $this->_fetchTableData($pageNo, $this->_limit(), $filter);
            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->Message        = 'Deleted.';
            $this->EndReturnData->RecordHtmlData = $pd->RecordHtmlData;
            $this->EndReturnData->Pagination     = $pd->Pagination;
            $this->EndReturnData->TotalCount     = $pd->TotalCount;
        } catch (Exception $e) { $this->EndReturnData->Error = TRUE; $this->EndReturnData->Message = $e->getMessage(); }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }
}
