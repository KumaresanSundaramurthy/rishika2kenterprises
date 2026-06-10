<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Salaryadvances extends MY_Controller {

    public  $pageData = [];
    private $EndReturnData;

    public function __construct() { parent::__construct(); }

    private function _orgUID()    { return (int)$this->pageData['JwtData']->Org->OrgUID; }
    private function _branchUID() { return (int)($this->pageData['JwtData']->Org->BranchUID ?? 1); }
    private function _userUID()   { return (int)$this->pageData['JwtData']->User->UserUID; }
    private function _limit()     { return (int)($this->pageData['JwtData']->GenSettings->RowLimit ?? 10); }

    private function _fetchTableData($pageNo, $limit, $filter = []) {
        $offset = max(0, ($pageNo - 1) * $limit);
        $this->load->model('attendance_model');
        $result  = $this->attendance_model->getAdvanceListPaginated($this->_orgUID(), $limit, $offset, $filter);
        $rowHtml = $this->load->view('hrms/salaryadvances/list', ['DataLists' => $result->rows, 'SerialNumber' => $offset, 'JwtData' => $this->pageData['JwtData']], TRUE);
        $r = new stdClass();
        $r->RecordHtmlData = $rowHtml;
        $r->Pagination     = $this->globalservice->buildPagePaginationHtml('/salaryadvances/getPageDetails', $result->totalCount, $pageNo, $limit);
        $r->TotalCount     = $result->totalCount;
        return $r;
    }

    public function index() {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $pd = $this->_fetchTableData(1, $this->_limit());
            $this->pageData['ModRowData']    = $pd->RecordHtmlData;
            $this->pageData['ModPagination'] = $pd->Pagination;
            $this->load->model('employees_model');
            $this->pageData['EmployeeList'] = $this->employees_model->getEmployeeDropdownList($this->_orgUID());
            $this->load->view('hrms/salaryadvances/view', $this->pageData);
        } catch (Exception $e) { redirect('dashboard', 'refresh'); }
    }

    public function getPageDetails($pageNo = 0) {
        $this->EndReturnData = new stdClass();
        try {
            $pd = $this->_fetchTableData(max(1, (int)$pageNo), $this->_limit(), $this->input->post('Filter') ?: []);
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
            $uid = (int)($p['AdvanceUID'] ?? 0);
            if (empty($p['EmployeeUID']))   throw new Exception('Employee is required.');
            if (empty($p['AdvanceDate']))   throw new Exception('Date is required.');
            $amt = (float)($p['AdvanceAmount'] ?? 0);
            if ($amt <= 0) throw new Exception('Amount must be greater than 0.');
            $this->load->model('dbwrite_model');
            $data = ['OrgUID' => $this->_orgUID(), 'BranchUID' => $this->_branchUID(), 'EmployeeUID' => (int)$p['EmployeeUID'], 'AdvanceDate' => $p['AdvanceDate'], 'AdvanceAmount' => $amt, 'Reason' => trim($p['Reason'] ?? ''), 'BalancePending' => $amt, 'IsSettled' => 0, 'UpdatedBy' => $this->_userUID()];
            if ($uid === 0) {
                $data['CreatedBy'] = $this->_userUID();
                $res = $this->dbwrite_model->insertData('Transaction', 'SalaryAdvanceTbl', $data);
            } else {
                unset($data['BalancePending']);
                $res = $this->dbwrite_model->updateData('Transaction', 'SalaryAdvanceTbl', $data, ['AdvanceUID' => $uid, 'OrgUID' => $this->_orgUID()]);
            }
            if ($res->Error) throw new Exception($res->Message);
            $this->EndReturnData->Error = FALSE; $this->EndReturnData->Message = $uid ? 'Updated.' : 'Advance recorded.';
        } catch (Exception $e) { $this->EndReturnData->Error = TRUE; $this->EndReturnData->Message = $e->getMessage(); }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function delete() {
        $this->EndReturnData = new stdClass();
        try {
            $uid = (int)$this->input->post('AdvanceUID');
            if (!$uid) throw new Exception('Invalid.');
            $this->load->model('dbwrite_model');
            $res = $this->dbwrite_model->updateData('Transaction', 'SalaryAdvanceTbl', ['IsDeleted' => 1, 'UpdatedBy' => $this->_userUID()], ['AdvanceUID' => $uid, 'OrgUID' => $this->_orgUID()]);
            if ($res->Error) throw new Exception($res->Message);
            $this->EndReturnData->Error = FALSE; $this->EndReturnData->Message = 'Deleted.';
        } catch (Exception $e) { $this->EndReturnData->Error = TRUE; $this->EndReturnData->Message = $e->getMessage(); }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }
}
