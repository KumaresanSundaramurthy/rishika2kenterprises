<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Departments extends MY_Controller {

    public  $pageData = [];
    protected $EndReturnData;

    public function __construct() { parent::__construct(); }

    public function index(): void {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $this->load->view('hrms/departments/view', $this->pageData);
        } catch (Exception $e) {
            $this->notifyError('Departments::index', $e);
            redirect('dashboard', 'refresh');
        }
    }

    public function save(): void {
        $this->EndReturnData = new stdClass();
        try {
            $p        = $this->input->post();
            $resp = services_post('api/departments/save', [
                'orgId'          => $this->_orgUID(),
                'userId'         => $this->_userUID(),
                'DepartmentUID'  => (int)($p['DepartmentUID']  ?? 0),
                'DepartmentName' => trim($p['DepartmentName']  ?? ''),
                'Description'    => trim($p['Description']     ?? ''),
                'IsSystem'       => (int)($p['IsSystem']       ?? 0),
            ], withJwt: true);
            if (!$resp || !empty($resp->Error)) throw new Exception($resp->Message ?? 'Service call failed.');
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = $resp->Message ?? 'Saved.';
        } catch (Exception $e) {
            $this->notifyError('Departments::save', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function delete(): void {
        $this->EndReturnData = new stdClass();
        try {
            $resp = services_post('api/departments/delete', [
                'orgId'         => $this->_orgUID(),
                'userId'        => $this->_userUID(),
                'DepartmentUID' => (int)$this->input->post('DepartmentUID'),
            ], withJwt: true);
            if (!$resp || !empty($resp->Error)) throw new Exception($resp->Message ?? 'Service call failed.');
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = $resp->Message ?? 'Deleted.';
        } catch (Exception $e) {
            $this->notifyError('Departments::delete', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function getList(): void {
        $this->EndReturnData = new stdClass();
        try {
            $resp     = services_get('api/departments/getList?orgId=' . $this->_orgUID(), withJwt: true);
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $resp->Data->Data ?? [];
        } catch (Exception $e) {
            $this->notifyError('Departments::getList', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }
}
