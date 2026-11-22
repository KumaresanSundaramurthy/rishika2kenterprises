<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Payroll extends MY_Controller {

    public $pageData = [];

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