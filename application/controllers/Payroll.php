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

    public function process(): void {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $resp     = services_get('api/payroll/getProcessData?orgId=' . $this->_orgUID(), withJwt: true);
            $this->pageData['EmployeeCount'] = (int)($resp->Data->EmployeeCount ?? 0);
            $this->pageData['CurrentMonth']  = (int)($resp->Data->CurrentMonth  ?? date('m'));
            $this->pageData['CurrentYear']   = (int)($resp->Data->CurrentYear   ?? date('Y'));
            $this->load->view('hrms/payroll/process', $this->pageData);
        } catch (Exception $e) { $this->notifyError('Payroll::process', $e); redirect('payroll', 'refresh'); }
    }

    // ── AJAX — compute employee lines for selected month ──────────────────────

    public function getPayrollEmployees(): void {
        $this->EndReturnData = new stdClass();
        try {
            $month  = (int)$this->input->post('Month');
            $year   = (int)$this->input->post('Year');
            if (!$month || !$year) throw new Exception('Month and Year are required.');
            $resp     = services_get(
                'api/payroll/getPayrollEmployees?orgId=' . $this->_orgUID() . '&month=' . $month . '&year=' . $year,
                withJwt: true
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

    public function savePayroll(): void {
        $this->EndReturnData = new stdClass();
        try {
            $resp = services_post('api/payroll/savePayroll', [
                'orgId'    => $this->_orgUID(),
                'userId'   => $this->_userUID(),
                'branchId' => $this->_branchUID(),
                'Month'    => (int)$this->input->post('Month'),
                'Year'     => (int)$this->input->post('Year'),
                'Notes'    => trim($this->input->post('Notes') ?? ''),
                'Lines'    => $this->input->post('Lines') ?? [],
            ], withJwt: true);
            if (!$resp || !empty($resp->Error)) throw new Exception($resp->Message ?? 'Service call failed.');
            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->PayrollUID = $resp->PayrollUID ?? ($resp->Data->PayrollUID ?? 0);
            $this->EndReturnData->Message    = $resp->Message ?? 'Payroll processed successfully.';
        } catch (Exception $e) { $this->notifyError('Payroll::savePayroll', $e); $this->EndReturnData->Error = TRUE; $this->EndReturnData->Message = $e->getMessage(); }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── AJAX — update payroll status ──────────────────────────────────────────

    public function updateStatus(): void {
        $this->EndReturnData = new stdClass();
        try {
            $resp = services_post('api/payroll/updateStatus', [
                'orgId'      => $this->_orgUID(),
                'userId'     => $this->_userUID(),
                'PayrollUID' => (int)$this->input->post('PayrollUID'),
                'Status'     => $this->input->post('Status'),
            ], withJwt: true);
            if (!$resp || !empty($resp->Error)) throw new Exception($resp->Message ?? 'Service call failed.');
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = $resp->Message ?? 'Status updated.';
        } catch (Exception $e) { $this->notifyError('Payroll::updateStatus', $e); $this->EndReturnData->Error = TRUE; $this->EndReturnData->Message = $e->getMessage(); }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── AJAX — delete payroll ─────────────────────────────────────────────────

    public function delete(): void {
        $this->EndReturnData = new stdClass();
        try {
            $resp = services_post('api/payroll/delete', [
                'orgId'      => $this->_orgUID(),
                'userId'     => $this->_userUID(),
                'PayrollUID' => (int)$this->input->post('PayrollUID'),
            ], withJwt: true);
            if (!$resp || !empty($resp->Error)) throw new Exception($resp->Message ?? 'Service call failed.');
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = $resp->Message ?? 'Deleted.';
        } catch (Exception $e) { $this->notifyError('Payroll::delete', $e); $this->EndReturnData->Error = TRUE; $this->EndReturnData->Message = $e->getMessage(); }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── View payroll detail page ──────────────────────────────────────────────

    public function viewPayroll(int $uid = 0): void {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $resp     = services_get(
                'api/payroll/viewPayroll/' . $uid . '?orgId=' . $this->_orgUID(),
                withJwt: true
            );
            if (!$resp || !($resp->Data->Payroll ?? null)) { redirect('payroll', 'refresh'); return; }
            $this->pageData['Payroll']      = $resp->Data->Payroll      ?? null;
            $this->pageData['PayrollLines'] = (array)($resp->Data->PayrollLines ?? []);
            $this->load->view('hrms/payroll/detail', $this->pageData);
        } catch (Exception $e) { $this->notifyError('Payroll::viewPayroll', $e); redirect('payroll', 'refresh'); }
    }
}
