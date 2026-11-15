<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Payslips extends MY_Controller {

    public $pageData = [];

    public function __construct() { parent::__construct(); }

    public function index(): void {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $this->load->view('hrms/payslips/view', $this->pageData);
        } catch (Exception $e) {
            notifyError('Payslips::index', $e);
            redirect('dashboard', 'refresh');
        }
    }

    public function viewPayslip(int $uid = 0): void {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';
            $resp     = services_get(
                'api/payslips/viewPayslip/' . $uid . '?orgId=' . $this->_orgUID(),
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

    public function printPayslip(int $uid = 0): void {
        if (!$this->_loadPageTitle()) { echo 'Not found'; return; }
        try {
            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';
            $resp     = services_get(
                'api/payslips/viewPayslip/' . $uid . '?orgId=' . $this->_orgUID(),
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
