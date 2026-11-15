<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Payroll extends MY_Controller {

    public  $pageData = [];
    protected $EndReturnData;

    public function __construct() { parent::__construct(); }

    // ── Payroll list page ─────────────────────────────────────────────────────

    public function index(): void {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $this->load->view('hrms/payroll/view', $this->pageData);
        } catch (Exception $e) { $this->notifyError('Payroll::index', $e); redirect('dashboard', 'refresh'); }
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
            services_post('api/auditlog/log', [
                'orgId'         => (int) $this->_orgUID(),
                'userUID'       => (int) $this->_userUID(),
                'action'        => 'SAVE_PAYROLL',
                'entityType'    => 'Payroll',
                'entityUID'     => (int) $payrollUID,
                'details'       => ['Month' => $month, 'Year' => $year],
                'summary'       => 'Processed payroll for ' . $month . '/' . $year,
                'module'        => 'Payroll',
                'auditCategory' => 'TRANSACTION',
                'ipAddress'     => $this->input->ip_address(),
                'userAgent'     => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'sessionId'     => session_id(),
                'source'        => 'WEB',
            ], ['Authorization: Bearer ' . $jwtToken]);
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
            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';
            services_post('api/auditlog/log', [
                'orgId'         => (int) $this->_orgUID(),
                'userUID'       => (int) $this->_userUID(),
                'action'        => 'UPDATE_PAYROLL_STATUS',
                'entityType'    => 'Payroll',
                'entityUID'     => (int) $uid,
                'details'       => ['NewStatus' => $status],
                'summary'       => 'Updated payroll status #' . $uid,
                'module'        => 'Payroll',
                'auditCategory' => 'TRANSACTION',
                'ipAddress'     => $this->input->ip_address(),
                'userAgent'     => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'sessionId'     => session_id(),
                'source'        => 'WEB',
            ], ['Authorization: Bearer ' . $jwtToken]);
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
            services_post('api/auditlog/log', [
                'orgId'         => (int) $this->_orgUID(),
                'userUID'       => (int) $this->_userUID(),
                'action'        => 'DELETE_PAYROLL',
                'entityType'    => 'Payroll',
                'entityUID'     => (int) $uid,
                'details'       => [],
                'summary'       => 'Deleted payroll #' . $uid,
                'module'        => 'Payroll',
                'auditCategory' => 'TRANSACTION',
                'ipAddress'     => $this->input->ip_address(),
                'userAgent'     => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'sessionId'     => session_id(),
                'source'        => 'WEB',
            ], ['Authorization: Bearer ' . $jwtToken]);
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
