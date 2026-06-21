<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Payroll extends MY_Controller {

    public  $pageData = [];
    private $EndReturnData;

    public function __construct() { parent::__construct(); }

    private function _orgUID()    { return (int)$this->pageData['JwtData']->Org->OrgUID; }
    private function _branchUID() { return (int)($this->pageData['JwtData']->Org->BranchUID ?? 1); }
    private function _userUID()   { return (int)$this->pageData['JwtData']->User->UserUID; }
    private function _limit()     { return (int)($this->pageData['JwtData']->GenSettings->RowLimit ?? 10); }
    private function _cur()       { return $this->pageData['JwtData']->GenSettings->CurrenySymbol ?? '₹'; }

    private function _fetchTableData($pageNo, $limit, $filter = []) {
        $offset = max(0, ($pageNo - 1) * $limit);
        $this->load->model('payroll_model');
        $result  = $this->payroll_model->getPayrollListPaginated($this->_orgUID(), $limit, $offset, $filter);
        $rowHtml = $this->load->view('hrms/payroll/list', ['DataLists' => $result->rows, 'SerialNumber' => $offset, 'JwtData' => $this->pageData['JwtData']], TRUE);
        $r = new stdClass();
        $r->RecordHtmlData = $rowHtml;
        $r->Pagination     = $this->globalservice->buildPagePaginationHtml('/payroll/getPageDetails', $result->totalCount, $pageNo, $limit);
        $r->TotalCount     = $result->totalCount;
        return $r;
    }

    // ── Payroll list page ─────────────────────────────────────────────────────

    public function index() {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $pd = $this->_fetchTableData(1, $this->_limit());
            $this->pageData['ModRowData']    = $pd->RecordHtmlData;
            $this->pageData['ModPagination'] = $pd->Pagination;
            $this->load->model('payroll_model');
            $this->pageData['PayrollStats']  = $this->payroll_model->getPayrollStats($this->_orgUID());
            $this->load->view('hrms/payroll/view', $this->pageData);
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

    // ── Process page (select month + view employee calculations) ─────────────

    public function process() {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $this->load->model('users_model');
            $this->pageData['EmployeeCount'] = count($this->users_model->getEmployeeDropdownList($this->_orgUID()));
            $this->pageData['CurrentMonth']  = (int)date('m');
            $this->pageData['CurrentYear']   = (int)date('Y');
            $this->load->view('hrms/payroll/process', $this->pageData);
        } catch (Exception $e) { redirect('payroll', 'refresh'); }
    }

    // ── AJAX — compute employee lines for selected month ──────────────────────

    public function getPayrollEmployees() {
        $this->EndReturnData = new stdClass();
        try {
            $month     = (int)$this->input->post('Month');
            $year      = (int)$this->input->post('Year');
            $orgUID    = $this->_orgUID();
            $branchUID = $this->_branchUID();
            if (!$month || !$year) throw new Exception('Month and Year are required.');

            $this->load->model('users_model');
            $this->load->model('attendance_model');
            $this->load->model('payroll_model');

            // Check if payroll already exists
            $existing = $this->payroll_model->getPayrollExists($orgUID, $branchUID, $month, $year);

            $employees  = $this->users_model->getEmployeeDropdownList($orgUID);
            $attSummary = $this->attendance_model->getAttendanceSummaryForPayroll($orgUID, $year, $month);
            $workDays   = $this->payroll_model->getWorkingDaysInMonth($year, $month);

            // Key attendance data by EmployeeUID
            $attMap = [];
            foreach ($attSummary as $a) $attMap[(int)$a->EmployeeUID] = $a;

            $lines = [];
            foreach ($employees as $emp) {
                $euid   = (int)$emp->EmployeeUID;
                $att    = $attMap[$euid] ?? null;
                $present= (float)($att->PresentDays ?? 0);
                $absent = (float)($att->AbsentDays  ?? 0);
                $hours  = (float)($att->TotalHours  ?? 0);

                // Salary calc by type
                $basic  = (float)$emp->BasicSalary;
                $allw   = (float)$emp->Allowances;
                $inc    = (float)$emp->Incentives;
                $fixed  = (float)$emp->FixedDeductions;
                $gross  = $basic + $allw + $inc;

                $deduction = 0;
                if ($emp->SalaryType === 'Monthly') {
                    $perDay    = $workDays > 0 ? $gross / $workDays : 0;
                    $deduction = round($absent * $perDay, 2);
                    $net       = round($gross - $deduction - $fixed, 2);
                } elseif ($emp->SalaryType === 'Daily') {
                    $gross     = round($basic * $present, 2);
                    $net       = round($gross - $fixed, 2);
                } else { // Hourly
                    $gross     = round($basic * $hours, 2);
                    $net       = round($gross - $fixed, 2);
                }
                if ($net < 0) $net = 0;

                // Pending advance
                $advPending = $this->attendance_model->getPendingAdvanceBalance($euid, $orgUID);
                $advRecovery = min($advPending, $net);
                $net = round($net - $advRecovery, 2);
                if ($net < 0) $net = 0;

                $lines[] = [
                    'EmployeeUID'    => $euid,
                    'EmployeeName'   => $emp->EmployeeName,
                    'EmployeeCode'   => $emp->EmployeeCode,
                    'SalaryType'     => $emp->SalaryType,
                    'BasicSalary'    => $basic,
                    'Allowances'     => $allw,
                    'Incentives'     => $inc,
                    'GrossSalary'    => $gross,
                    'WorkingDays'    => $workDays,
                    'PresentDays'    => $present,
                    'AbsentDays'     => $absent,
                    'HoursWorked'    => $hours,
                    'SalaryDeduction'=> $deduction,
                    'FixedDeductions'=> $fixed,
                    'AdvanceRecovery'=> $advRecovery,
                    'NetSalary'      => $net,
                ];
            }

            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Lines      = $lines;
            $this->EndReturnData->WorkDays   = $workDays;
            $this->EndReturnData->Existing   = $existing ? $existing->PayrollUID : 0;
            $this->EndReturnData->ExistStatus= $existing ? $existing->PayrollStatus : '';
        } catch (Exception $e) { $this->EndReturnData->Error = TRUE; $this->EndReturnData->Message = $e->getMessage(); }
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

            $this->load->model('payroll_model');
            $this->load->model('dbwrite_model');

            // Totals
            $totalGross = 0; $totalDed = 0; $totalNet = 0;
            foreach ($lines as $l) {
                $totalGross += (float)($l['GrossSalary']    ?? 0);
                $totalDed   += (float)($l['SalaryDeduction'] ?? 0) + (float)($l['FixedDeductions'] ?? 0) + (float)($l['AdvanceRecovery'] ?? 0);
                $totalNet   += (float)($l['NetSalary']       ?? 0);
            }

            $existing = $this->payroll_model->getPayrollExists($orgUID, $branchUID, $month, $year);

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

            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->PayrollUID = $payrollUID;
            $this->EndReturnData->Message    = 'Payroll processed successfully.';
        } catch (Exception $e) { $this->EndReturnData->Error = TRUE; $this->EndReturnData->Message = $e->getMessage(); }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    private function _applyAdvanceRecovery($empUID, $orgUID, $amount, $userUID) {
        $this->load->model('attendance_model');
        $this->load->model('payroll_model');
        $advances = $this->payroll_model->getUnsettledAdvances($empUID, $orgUID);

        $remaining = $amount;
        foreach ($advances as $adv) {
            if ($remaining <= 0) break;
            $deduct  = min($remaining, (float)$adv->BalancePending);
            $newBal  = round((float)$adv->BalancePending - $deduct, 2);
            $settled = $newBal <= 0 ? 1 : 0;
            $this->load->model('dbwrite_model');
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
            $this->load->model('dbwrite_model');
            $res = $this->dbwrite_model->updateData('Transaction', 'PayrollTbl', ['PayrollStatus' => $status, 'UpdatedBy' => $this->_userUID()], ['PayrollUID' => $uid, 'OrgUID' => $this->_orgUID()]);
            if ($res->Error) throw new Exception($res->Message);
            $this->EndReturnData->Error = FALSE; $this->EndReturnData->Message = 'Status updated to ' . $status . '.';
        } catch (Exception $e) { $this->EndReturnData->Error = TRUE; $this->EndReturnData->Message = $e->getMessage(); }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function delete() {
        $this->EndReturnData = new stdClass();
        try {
            $uid = (int)$this->input->post('PayrollUID');
            if (!$uid) throw new Exception('Invalid.');
            $this->load->model('payroll_model');
            $payroll = $this->payroll_model->getPayrollByUID($uid, $this->_orgUID());
            if (!$payroll) throw new Exception('Payroll not found.');
            if ($payroll->PayrollStatus === 'Paid') throw new Exception('Paid payroll cannot be deleted.');
            $this->load->model('dbwrite_model');
            $this->dbwrite_model->updateData('Transaction', 'PayrollLineTbl', ['IsDeleted' => 1], ['PayrollUID' => $uid]);
            $res = $this->dbwrite_model->updateData('Transaction', 'PayrollTbl', ['IsDeleted' => 1, 'UpdatedBy' => $this->_userUID()], ['PayrollUID' => $uid, 'OrgUID' => $this->_orgUID()]);
            if ($res->Error) throw new Exception($res->Message);
            $this->EndReturnData->Error = FALSE; $this->EndReturnData->Message = 'Deleted.';
        } catch (Exception $e) { $this->EndReturnData->Error = TRUE; $this->EndReturnData->Message = $e->getMessage(); }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function viewPayroll($uid = 0) {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $this->load->model('payroll_model');
            $payroll = $this->payroll_model->getPayrollByUID((int)$uid, $this->_orgUID());
            if (!$payroll) { redirect('payroll', 'refresh'); return; }
            $this->pageData['Payroll']      = $payroll;
            $this->pageData['PayrollLines'] = $this->payroll_model->getPayrollLines((int)$uid);
            $this->load->view('hrms/payroll/detail', $this->pageData);
        } catch (Exception $e) { redirect('payroll', 'refresh'); }
    }
}
