<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Designations extends MY_Controller {

    public  $pageData = [];
    protected $EndReturnData;

    public function __construct() { parent::__construct(); }

    public function index(): void {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $this->load->view('hrms/designations/view', $this->pageData);
        } catch (Exception $e) {
            $this->notifyError('Designations::index', $e);
            redirect('dashboard', 'refresh');
        }
    }

    public function save(): void {
        $this->EndReturnData = new stdClass();
        try {
            $p        = $this->input->post();
            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';
            $resp = services_post('api/designations/save', [
                'orgId'           => $this->_orgUID(),
                'userId'          => $this->_userUID(),
                'DesignationUID'  => (int)($p['DesignationUID']  ?? 0),
                'DesignationName' => trim($p['DesignationName']  ?? ''),
                'Description'     => trim($p['Description']      ?? ''),
            ], ['Authorization: Bearer ' . $jwtToken]);
            if (!$resp || !empty($resp->Error)) throw new Exception($resp->Message ?? 'Service call failed.');
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = $resp->Message ?? 'Saved.';
        } catch (Exception $e) {
            $this->notifyError('Designations::save', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function delete(): void {
        $this->EndReturnData = new stdClass();
        try {
            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';
            $resp = services_post('api/designations/delete', [
                'orgId'          => $this->_orgUID(),
                'userId'         => $this->_userUID(),
                'DesignationUID' => (int)$this->input->post('DesignationUID'),
            ], ['Authorization: Bearer ' . $jwtToken]);
            if (!$resp || !empty($resp->Error)) throw new Exception($resp->Message ?? 'Service call failed.');
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = $resp->Message ?? 'Deleted.';
        } catch (Exception $e) {
            $this->notifyError('Designations::delete', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function getList(): void {
        $this->EndReturnData = new stdClass();
        try {
            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';
            $resp     = services_get('api/designations/getList?orgId=' . $this->_orgUID(), ['Authorization: Bearer ' . $jwtToken]);
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $resp->Data->Data ?? [];
        } catch (Exception $e) {
            $this->notifyError('Designations::getList', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }
}
