<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Payslips extends MY_Controller {

    public  $pageData = [];
    protected $EndReturnData;

    public function __construct() { parent::__construct(); }

    private function _fetchTableData($pageNo, $limit, $filter = []) {
        $offset   = max(0, ($pageNo - 1) * $limit);
        $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';
        $resp     = services_get(
            'api/payslips/getPageDetails/' . $pageNo
                . '?orgId=' . $this->_orgUID()
                . '&RowLimit=' . $limit
                . '&Filter=' . urlencode(json_encode($filter)),
            ['Authorization: Bearer ' . $jwtToken]
        );
        $rows    = (array)($resp->Data->Data ?? []);
        $total   = (int)($resp->Data->TotalCount ?? 0);
        $rowHtml = $this->load->view('hrms/payslips/list', ['DataLists' => $rows, 'SerialNumber' => $offset, 'JwtData' => $this->pageData['JwtData']], TRUE);
        $r = new stdClass();
        $r->RecordHtmlData = $rowHtml;
        $r->Pagination     = $this->globalservice->buildPagePaginationHtml('/payslips/getPageDetails', $total, $pageNo, $limit);
        $r->TotalCount     = $total;
        return $r;
    }

    public function index() {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $pd = $this->_fetchTableData(1, $this->_rowLimit());
            $this->pageData['ModRowData']    = $pd->RecordHtmlData;
            $this->pageData['ModPagination'] = $pd->Pagination;
            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';
            $pageResp = services_get('api/payslips/getPageData?orgId=' . $this->_orgUID(), ['Authorization: Bearer ' . $jwtToken]);
            $this->pageData['EmployeeList'] = (array)($pageResp->Data->EmployeeList ?? []);
            $this->load->view('hrms/payslips/view', $this->pageData);
        } catch (Exception $e) {
            notifyError('Payslips::index', $e);
            redirect('dashboard', 'refresh');
        }
    }

    public function getPageDetails($pageNo = 0) {
        $this->EndReturnData = new stdClass();
        try {
            $pd = $this->_fetchTableData(max(1, (int)$pageNo), $this->_rowLimit(), $this->input->post('Filter') ?: []);
            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->RecordHtmlData = $pd->RecordHtmlData;
            $this->EndReturnData->Pagination     = $pd->Pagination;
            $this->EndReturnData->TotalCount     = $pd->TotalCount;
        } catch (Exception $e) {
            notifyError('Payslips::getPageDetails', $e);
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function viewPayslip($uid = 0) {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';
            $resp     = services_get(
                'api/payslips/viewPayslip/' . (int)$uid . '?orgId=' . $this->_orgUID(),
                ['Authorization: Bearer ' . $jwtToken]
            );
            if (!$resp || !($resp->Data->Slip ?? null)) { redirect('payslips', 'refresh'); return; }
            $this->pageData['Slip']    = $resp->Data->Slip    ?? null;
            $this->pageData['OrgInfo'] = $resp->Data->OrgInfo ?? new stdClass();
            $this->load->view('hrms/payslips/detail', $this->pageData);
        } catch (Exception $e) {
            notifyError('Payslips::viewPayslip', $e);
            redirect('payslips', 'refresh');
        }
    }

    public function printPayslip($uid = 0) {
        if (!$this->_loadPageTitle()) { echo 'Not found'; return; }
        try {
            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';
            $resp     = services_get(
                'api/payslips/viewPayslip/' . (int)$uid . '?orgId=' . $this->_orgUID(),
                ['Authorization: Bearer ' . $jwtToken]
            );
            if (!$resp || !($resp->Data->Slip ?? null)) { echo 'Payslip not found.'; return; }
            $this->pageData['Slip']    = $resp->Data->Slip    ?? null;
            $this->pageData['OrgInfo'] = $resp->Data->OrgInfo ?? new stdClass();
            $this->load->view('hrms/payslips/print', $this->pageData);
        } catch (Exception $e) {
            notifyError('Payslips::printPayslip', $e);
            echo 'Error: ' . $e->getMessage();
        }
    }
}
