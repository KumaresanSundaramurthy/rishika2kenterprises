<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Payroll extends MY_Controller {

    public  $pageData = [];
    protected $EndReturnData;

    public function __construct() { parent::__construct(); }

    private function _fetchTableData($pageNo, $limit, $filter = []) {
        $offset   = max(0, ($pageNo - 1) * $limit);
        $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';
        $resp     = services_get(
            'api/payroll/getPageDetails/' . $pageNo
                . '?orgId=' . $this->_orgUID()
                . '&RowLimit=' . $limit
                . '&Filter=' . urlencode(json_encode($filter)),
            ['Authorization: Bearer ' . $jwtToken]
        );
        $rows    = (array)($resp->Data->Data ?? []);
        $total   = (int)($resp->Data->TotalCount ?? 0);
        $rowHtml = $this->load->view('hrms/payroll/list', ['DataLists' => $rows, 'SerialNumber' => $offset, 'JwtData' => $this->pageData['JwtData']], TRUE);
        $r = new stdClass();
        $r->RecordHtmlData = $rowHtml;
        $r->Pagination     = $this->globalservice->buildPagePaginationHtml('/payroll/getPageDetails', $total, $pageNo, $limit);
        $r->TotalCount     = $total;
        return $r;
    }

    // ── Payroll list page ─────────────────────────────────────────────────────

    public function index() {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $pd = $this->_fetchTableData(1, $this->_rowLimit());
            $this->pageData['ModRowData']    = $pd->RecordHtmlData;
            $this->pageData['ModPagination'] = $pd->Pagination;
            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';
            $pageResp = services_get('api/payroll/getPageData?orgId=' . $this->_orgUID(), ['Authorization: Bearer ' . $jwtToken]);
            $this->pageData['PayrollStats']  = $pageResp->Data->PayrollStats ?? new stdClass();
            $this->load->view('hrms/payroll/view', $this->pageData);
        } catch (Exception $e) { $this->notifyError('Payroll::index', $e); redirect('dashboard', 'refresh'); }
    }

    public function getPageDetails($pageNo = 0) {
        $this->EndReturnData = new stdClass();
        try {
            $pd = $this->_fetchTableData(max(1, (int)$pageNo), $this->_rowLimit(), $this->input->post('Filter') ?: []);
            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->RecordHtmlData = $pd->RecordHtmlData;
            $this->EndReturnData->Pagination     = $pd->Pagination;
            $this->EndReturnData->TotalCount     = $pd->TotalCount;
        } catch (Exception $e) { $this->notifyError('Payroll::getPageDetails', $e); $this->EndReturnData->Error = TRUE; $this->EndReturnData->Message = $e->getMessage(); }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Process page (select month + view employee calculations) ─────────────

    public function process() {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';
            $resp     = services_get('api/payroll/getProcessData?orgId=' . $this->_orgUID(), ['Authorization: Bearer ' . $jwtToken]);
            $this->pageData['EmployeeCount'] = (int)($resp->Data->EmployeeCount ?? 0);
            $this->pageData['CurrentMonth']  = (int)($resp->Data->CurrentMonth  ?? date('m'));
            $this->pageData['CurrentYear']   = (int)($resp->Data->CurrentYear   ?? date('Y'));
            $this->load->view('hrms/payroll/process', $this->pageData);
        } catch (Exception $e) { $this->notifyError('Payroll::process', $e); redirect('payroll', 'refresh'); }
    }

    // ── AJAX — compute employee lines for selected month ──────────────────────

    public function getPayrollEmployees() {
        $this->EndReturnData = new stdClass();
        try {
            $month  = (int)$this->input->post('Month');
            $year   = (int)$this->input->post('Year');
            $orgUID = $this->_orgUID();
            if (!$month || !$year) throw new Exception('Month and Year are required.');

            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';
            $resp     = services_get(
                'api/payroll/getPayrollEmployees?orgId=' . $orgUID . '&month=' . $month . '&year=' . $year,
                ['Authorization: Bearer ' . $jwtToken]
            );
            $this->EndReturnData->Error       = FALSE;
            $this->EndReturnData->Lines       = $resp->Data->Lines      ?? [];
            $this->EndReturnData->WorkDays    = $resp->Data->WorkDays   ?? 0;
            $this->EndReturnData->Existing    = $resp->Data->Existing   ?? 0;
            $this->EndReturnData->ExistStatus = $resp->Data->ExistStatus ?? '';
        } catch (Exception $e) { $this->notifyError('Payroll::getPayrollEmployees', $e); $this->EndReturnData->Error = TRUE; $this->EndReturnData->Message = $e->getMessage(); }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── AJAX — save processed payroll ─────────────────────────────────────────

    public function savePayroll() {
        $this->EndReturnData = new stdClass();
        try {
            $month     = (int)$this->input->post('Month');
            $year      = (int)$this->input->post('Year');
            $lines     = $this->input->post('Lines');
            $notes     = trim($this->input->post('Notes') ?? '');
            $orgUID    = $this->_orgUID();
            $branchUID = $this->_branchUID();
            $userUID   = $this->_userUID();

            if (!$month || !$year)             throw new Exception('Month and Year are required.');
            if (empty($lines) || !is_array($lines)) throw new Exception('No payroll lines provided.');

            $this->load->model('dbwrite_model'); $this->load->model('dbwrite_ext_model');

            // Totals
            $totalGross = 0; $totalDed = 0; $totalNet = 0;
            foreach ($lines as $l) {
                $totalGross += (float)($l['GrossSalary']    ?? 0);
                $totalDed   += (float)($l['SalaryDeduction'] ?? 0) + (float)($l['FixedDeductions'] ?? 0) + (float)($l['AdvanceRecovery'] ?? 0);
                $totalNet   += (float)($l['NetSalary']       ?? 0);
            }

            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';
            $resp     = services_get('api/payroll/getPayrollExists?' . http_build_query([
                'orgId'    => $orgUID,
                'branchId' => $branchUID,
                'Month'    => $month,
                'Year'     => $year,
            ]), ['Authorization: Bearer ' . $jwtToken]);
            $existing = (!($resp->Error ?? true)) ? ($resp->Data ?? null) : null;

            if ($existing) {
                if (in_array($existing->PayrollStatus, ['Paid'])) throw new Exception('This payroll is already marked as Paid and cannot be reprocessed.');
                $payrollUID = (int)$existing->PayrollUID;
                $headerData = ['TotalEmployees' => count($lines), 'TotalGrossSalary' => $totalGross, 'TotalDeductions' => $totalDed, 'TotalNetSalary' => $totalNet, 'PayrollStatus' => 'Processed', 'ProcessedOn' => date('Y-m-d H:i:s'), 'Notes' => $notes, 'UpdatedBy' => $userUID];
                $res = $this->dbwrite_model->updateData('Transaction', 'PayrollTbl', $headerData, ['PayrollUID' => $payrollUID]);
                if ($res->Error) throw new Exception($res->Message);
                // Delete old lines
                $this->dbwrite_model->updateData('Transaction', 'PayrollLineTbl', ['IsDeleted' => 1], ['PayrollUID' => $payrollUID]);
            } else {
                $headerData = ['OrgUID' => $orgUID, 'BranchUID' => $branchUID, 'PayrollMonth' => $month, 'PayrollYear' => $year, 'PayrollStatus' => 'Processed', 'TotalEmployees' => count($lines), 'TotalGrossSalary' => $totalGross, 'TotalDeductions' => $totalDed, 'TotalNetSalary' => $totalNet, 'ProcessedOn' => date('Y-m-d H:i:s'), 'Notes' => $notes, 'CreatedBy' => $userUID, 'UpdatedBy' => $userUID];
                $res = $this->dbwrite_model->insertData('Transaction', 'PayrollTbl', $headerData);
                if ($res->Error) throw new Exception($res->Message);
                $payrollUID = (int)$res->ID;
            }

            // Insert lines
            foreach ($lines as $l) {
                $lineData = ['PayrollUID' => $payrollUID, 'OrgUID' => $orgUID, 'EmployeeUID' => (int)$l['EmployeeUID'], 'SalaryType' => $l['SalaryType'], 'BasicSalary' => (float)$l['BasicSalary'], 'Allowances' => (float)$l['Allowances'], 'Incentives' => (float)$l['Incentives'], 'GrossSalary' => (float)$l['GrossSalary'], 'WorkingDays' => (int)$l['WorkingDays'], 'PresentDays' => (float)$l['PresentDays'], 'AbsentDays' => (float)$l['AbsentDays'], 'HoursWorked' => (float)$l['HoursWorked'], 'SalaryDeduction' => (float)$l['SalaryDeduction'], 'FixedDeductions' => (float)$l['FixedDeductions'], 'AdvanceRecovery' => (float)$l['AdvanceRecovery'], 'NetSalary' => (float)$l['NetSalary'], 'CreatedBy' => $userUID, 'UpdatedBy' => $userUID];
                $this->dbwrite_model->insertData('Transaction', 'PayrollLineTbl', $lineData);

                // Reduce advance balance
                if (!empty($l['AdvanceRecovery']) && (float)$l['AdvanceRecovery'] > 0) {
                    $this->_applyAdvanceRecovery((int)$l['EmployeeUID'], $orgUID, (float)$l['AdvanceRecovery'], $userUID);
                }
            }

            // ── Post payroll journal entry ────────────────────────────────
            try {
                $this->load->library('accountledger');
                $payrollDate = date('Y-m-d', mktime(0, 0, 0, $month, date('t', mktime(0,0,0,$month,1,$year)), $year));
                $payrollFY   = (int)date('Y', strtotime($payrollDate));
                $totalAdv    = 0;
                foreach ($lines as $l) { $totalAdv += (float)($l['AdvanceRecovery'] ?? 0); }
                // If reprocessing, reverse the previous journal first
                if ($existing) {
                    $this->accountledger->reverseJournal('Payroll', $payrollUID, $userUID);
                }
                $this->accountledger->postPayrollJournal(
                    $payrollUID, $payrollDate, $payrollFY,
                    $totalGross, $totalNet, $totalDed, $totalAdv, $userUID
                );
            } catch (Exception $ledgerEx) {
            }

            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->PayrollUID = $payrollUID;
            $this->EndReturnData->Message    = 'Payroll processed successfully.';
            $this->auditlog->log(
                (int) $this->_orgUID(), (int) $this->_userUID(),
                'SAVE_PAYROLL', 'Payroll', (int) $payrollUID, '',
                ['Month' => $month, 'Year' => $year], 'Processed payroll for ' . $month . '/' . $year, 'Payroll', 'TRANSACTION'
            );
        } catch (Exception $e) { $this->notifyError('Payroll::savePayroll', $e); $this->EndReturnData->Error = TRUE; $this->EndReturnData->Message = $e->getMessage(); }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    private function _applyAdvanceRecovery($empUID, $orgUID, $amount, $userUID) {
        $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';
        $resp     = services_get('api/payroll/getUnsettledAdvances?' . http_build_query([
            'orgId'       => $orgUID,
            'EmployeeUID' => $empUID,
        ]), ['Authorization: Bearer ' . $jwtToken]);
        $advances = (!($resp->Error ?? true)) ? ($resp->Data ?? []) : [];

        $remaining = $amount;
        foreach ($advances as $adv) {
            if ($remaining <= 0) break;
            $deduct  = min($remaining, (float)$adv->BalancePending);
            $newBal  = round((float)$adv->BalancePending - $deduct, $this->_decimals());
            $settled = $newBal <= 0 ? 1 : 0;
            $this->load->model('dbwrite_model'); $this->load->model('dbwrite_ext_model');
            $this->dbwrite_model->updateData('Transaction', 'SalaryAdvanceTbl', ['BalancePending' => $newBal, 'IsSettled' => $settled, 'UpdatedBy' => $userUID], ['AdvanceUID' => $adv->AdvanceUID]);
            $remaining -= $deduct;
        }
    }

    public function updateStatus() {
        $this->EndReturnData = new stdClass();
        try {
            $uid    = (int)$this->input->post('PayrollUID');
            $status = $this->input->post('Status');
            $valid  = ['Draft','Processed','Paid','Cancelled'];
            if (!$uid || !in_array($status, $valid)) throw new Exception('Invalid request.');
            $this->load->model('dbwrite_model'); $this->load->model('dbwrite_ext_model');
            $res = $this->dbwrite_model->updateData('Transaction', 'PayrollTbl', ['PayrollStatus' => $status, 'UpdatedBy' => $this->_userUID()], ['PayrollUID' => $uid, 'OrgUID' => $this->_orgUID()]);
            if ($res->Error) throw new Exception($res->Message);

            // ── Reverse journal when payroll is cancelled ─────────────────
            if ($status === 'Cancelled') {
                try {
                    $this->load->library('accountledger');
                    $this->accountledger->reverseJournal('Payroll', $uid, $this->_userUID());
                } catch (Exception $ledgerEx) {
                }
            }

            $this->EndReturnData->Error = FALSE; $this->EndReturnData->Message = 'Status updated to ' . $status . '.';
            $this->auditlog->log(
                (int) $this->_orgUID(), (int) $this->_userUID(),
                'UPDATE_PAYROLL_STATUS', 'Payroll', (int) $uid, '',
                ['NewStatus' => $status], 'Updated payroll status #' . $uid, 'Payroll', 'TRANSACTION'
            );
        } catch (Exception $e) { $this->notifyError('Payroll::updateStatus', $e); $this->EndReturnData->Error = TRUE; $this->EndReturnData->Message = $e->getMessage(); }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function delete() {
        $this->EndReturnData = new stdClass();
        try {
            $uid = (int)$this->input->post('PayrollUID');
            if (!$uid) throw new Exception('Invalid.');
            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';
            $resp     = services_get('api/payroll/getPayrollByUID?' . http_build_query([
                'orgId'      => $this->_orgUID(),
                'PayrollUID' => $uid,
            ]), ['Authorization: Bearer ' . $jwtToken]);
            $payroll = (!($resp->Error ?? true)) ? ($resp->Data ?? null) : null;
            if (!$payroll) throw new Exception('Payroll not found.');
            if ($payroll->PayrollStatus === 'Paid') throw new Exception('Paid payroll cannot be deleted.');
            $this->load->model('dbwrite_model'); $this->load->model('dbwrite_ext_model');
            $this->dbwrite_model->updateData('Transaction', 'PayrollLineTbl', ['IsDeleted' => 1], ['PayrollUID' => $uid]);
            $res = $this->dbwrite_model->updateData('Transaction', 'PayrollTbl', ['IsDeleted' => 1, 'UpdatedBy' => $this->_userUID()], ['PayrollUID' => $uid, 'OrgUID' => $this->_orgUID()]);
            if ($res->Error) throw new Exception($res->Message);

            // ── Reverse payroll journal entry ─────────────────────────────
            try {
                $this->load->library('accountledger');
                $this->accountledger->reverseJournal('Payroll', $uid, $this->_userUID());
            } catch (Exception $ledgerEx) {
            }

            $this->EndReturnData->Error = FALSE; $this->EndReturnData->Message = 'Deleted.';
            $this->auditlog->log(
                (int) $this->_orgUID(), (int) $this->_userUID(),
                'DELETE_PAYROLL', 'Payroll', (int) $uid, '',
                [], 'Deleted payroll #' . $uid, 'Payroll', 'TRANSACTION'
            );
        } catch (Exception $e) { $this->notifyError('Payroll::delete', $e); $this->EndReturnData->Error = TRUE; $this->EndReturnData->Message = $e->getMessage(); }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function viewPayroll($uid = 0) {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';
            $resp     = services_get(
                'api/payroll/viewPayroll/' . (int)$uid . '?orgId=' . $this->_orgUID(),
                ['Authorization: Bearer ' . $jwtToken]
            );
            if (!$resp || !($resp->Data->Payroll ?? null)) { redirect('payroll', 'refresh'); return; }
            $this->pageData['Payroll']      = $resp->Data->Payroll      ?? null;
            $this->pageData['PayrollLines'] = (array)($resp->Data->PayrollLines ?? []);
            $this->load->view('hrms/payroll/detail', $this->pageData);
        } catch (Exception $e) { $this->notifyError('Payroll::viewPayroll', $e); redirect('payroll', 'refresh'); }
    }
}
