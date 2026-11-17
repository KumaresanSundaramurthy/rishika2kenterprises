<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Holidays extends MY_Controller {

    public  $pageData = [];
    protected $EndReturnData;

    public function __construct() { parent::__construct(); }

    public function index(): void {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $this->load->view('hrms/holidays/view', $this->pageData);
        } catch (Exception $e) {
            notifyError('Holidays::index', $e);
            redirect('dashboard', 'refresh');
        }
    }

    public function save(): void {
        $this->EndReturnData = new stdClass();
        try {
            $p        = $this->input->post();
            $resp = services_post('api/holidays/save', [
                'orgId'       => $this->_orgUID(),
                'userId'      => $this->_userUID(),
                'HolidayUID'  => (int)($p['HolidayUID']  ?? 0),
                'HolidayName' => trim($p['HolidayName']  ?? ''),
                'HolidayDate' => $p['HolidayDate']       ?? '',
                'Description' => trim($p['Description']  ?? ''),
                'IsOptional'  => (int)($p['IsOptional']  ?? 0),
            ], withJwt: true);
            if (!$resp || !empty($resp->Error)) throw new Exception($resp->Message ?? 'Service call failed.');
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = $resp->Message ?? 'Saved.';
        } catch (Exception $e) {
            notifyError('Holidays::save', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function delete(): void {
        $this->EndReturnData = new stdClass();
        try {
            $resp = services_post('api/holidays/delete', [
                'orgId'      => $this->_orgUID(),
                'userId'     => $this->_userUID(),
                'HolidayUID' => (int)$this->input->post('HolidayUID'),
            ], withJwt: true);
            if (!$resp || !empty($resp->Error)) throw new Exception($resp->Message ?? 'Service call failed.');
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = $resp->Message ?? 'Deleted.';
        } catch (Exception $e) {
            notifyError('Holidays::delete', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }
}
