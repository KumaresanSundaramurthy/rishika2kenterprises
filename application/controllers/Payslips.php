<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Payslips extends MY_Controller {

    public  $pageData = [];
    private $EndReturnData;

    public function __construct() { parent::__construct(); }

    private function _orgUID()  { return (int)$this->pageData['JwtData']->Org->OrgUID; }
    private function _limit()   { return (int)($this->pageData['JwtData']->GenSettings->RowLimit ?? 10); }

    private function _fetchTableData($pageNo, $limit, $filter = []) {
        $offset = max(0, ($pageNo - 1) * $limit);
        $this->load->model('payroll_model');
        $result  = $this->payroll_model->getPayslipListPaginated($this->_orgUID(), $limit, $offset, $filter);
        $rowHtml = $this->load->view('hrms/payslips/list', ['DataLists' => $result->rows, 'SerialNumber' => $offset, 'JwtData' => $this->pageData['JwtData']], TRUE);
        $r = new stdClass();
        $r->RecordHtmlData = $rowHtml;
        $r->Pagination     = $this->globalservice->buildPagePaginationHtml('/payslips/getPageDetails', $result->totalCount, $pageNo, $limit);
        $r->TotalCount     = $result->totalCount;
        return $r;
    }

    public function index() {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $pd = $this->_fetchTableData(1, $this->_limit());
            $this->pageData['ModRowData']    = $pd->RecordHtmlData;
            $this->pageData['ModPagination'] = $pd->Pagination;
            $this->load->model('users_model');
            $this->pageData['EmployeeList'] = $this->users_model->getEmployeeDropdownList($this->_orgUID());
            $this->load->view('hrms/payslips/view', $this->pageData);
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

    public function viewPayslip($uid = 0) {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $this->load->model('payroll_model');
            $slip = $this->payroll_model->getPayslipDetail((int)$uid, $this->_orgUID());
            if (!$slip) { redirect('payslips', 'refresh'); return; }
            $this->pageData['Slip']    = $slip;
            $this->pageData['OrgInfo'] = $this->pageData['JwtData']->Org ?? new stdClass();
            $this->load->view('hrms/payslips/detail', $this->pageData);
        } catch (Exception $e) { redirect('payslips', 'refresh'); }
    }

    public function printPayslip($uid = 0) {
        if (!$this->_loadPageTitle()) { echo 'Not found'; return; }
        try {
            $this->load->model('payroll_model');
            $slip = $this->payroll_model->getPayslipDetail((int)$uid, $this->_orgUID());
            if (!$slip) { echo 'Payslip not found.'; return; }
            $this->pageData['Slip']    = $slip;
            $this->pageData['OrgInfo'] = $this->pageData['JwtData']->Org ?? new stdClass();
            $this->load->view('hrms/payslips/print', $this->pageData);
        } catch (Exception $e) { echo 'Error: ' . $e->getMessage(); }
    }
}
