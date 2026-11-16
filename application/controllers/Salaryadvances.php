<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Salaryadvances extends MY_Controller {

    public  $pageData     = [];
    protected $EndReturnData;

    public function __construct() { parent::__construct(); }

    public function index(): void {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $this->load->view('hrms/salaryadvances/view', $this->pageData);
        } catch (Exception $e) {
            notifyError('Salaryadvances::index', $e);
            redirect('dashboard', 'refresh');
        }
    }

    public function save(): void {
        $this->EndReturnData = new stdClass();
        try {
            $p        = $this->input->post();
            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';
            $resp = services_post('api/salaryadvances/save', [
                'orgId'         => $this->_orgUID(),
                'userId'        => $this->_userUID(),
                'branchId'      => $this->_branchUID(),
                'AdvanceUID'    => (int)($p['AdvanceUID']    ?? 0),
                'EmployeeUID'   => (int)($p['EmployeeUID']   ?? 0),
                'AdvanceDate'   => $p['AdvanceDate']         ?? '',
                'AdvanceAmount' => (float)($p['AdvanceAmount'] ?? 0),
                'Remarks'       => trim($p['Remarks']        ?? $p['Reason'] ?? ''),
            ], ['Authorization: Bearer ' . $jwtToken]);
            if (!$resp || !empty($resp->Error)) throw new Exception($resp->Message ?? 'Service call failed.');
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = $resp->Message ?? 'Saved.';
        } catch (Exception $e) {
            $this->notifyError('Salaryadvances::save', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function approve(): void {
        $this->EndReturnData = new stdClass();
        try {
            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';
            $resp = services_post('api/salaryadvances/approve', [
                'orgId'      => $this->_orgUID(),
                'userId'     => $this->_userUID(),
                'AdvanceUID' => (int)$this->input->post('AdvanceUID'),
            ], ['Authorization: Bearer ' . $jwtToken]);
            if (!$resp || !empty($resp->Error)) throw new Exception($resp->Message ?? 'Service call failed.');
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = $resp->Message ?? 'Advance approved.';
        } catch (Exception $e) {
            $this->notifyError('Salaryadvances::approve', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function reject(): void {
        $this->EndReturnData = new stdClass();
        try {
            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';
            $resp = services_post('api/salaryadvances/reject', [
                'orgId'      => $this->_orgUID(),
                'userId'     => $this->_userUID(),
                'AdvanceUID' => (int)$this->input->post('AdvanceUID'),
            ], ['Authorization: Bearer ' . $jwtToken]);
            if (!$resp || !empty($resp->Error)) throw new Exception($resp->Message ?? 'Service call failed.');
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = $resp->Message ?? 'Advance rejected.';
        } catch (Exception $e) {
            $this->notifyError('Salaryadvances::reject', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function delete(): void {
        $this->EndReturnData = new stdClass();
        try {
            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';
            $resp = services_post('api/salaryadvances/delete', [
                'orgId'      => $this->_orgUID(),
                'userId'     => $this->_userUID(),
                'AdvanceUID' => (int)$this->input->post('AdvanceUID'),
            ], ['Authorization: Bearer ' . $jwtToken]);
            if (!$resp || !empty($resp->Error)) throw new Exception($resp->Message ?? 'Service call failed.');
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = $resp->Message ?? 'Advance deleted.';
        } catch (Exception $e) {
            $this->notifyError('Salaryadvances::delete', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }
}
