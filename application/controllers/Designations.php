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
            $p   = $this->input->post();
            $uid = (int)($p['DesignationUID'] ?? 0);
            if (empty(trim($p['DesignationName'] ?? ''))) throw new Exception('Designation name is required.');
            $this->load->model('dbwrite_model'); $this->load->model('dbwrite_ext_model');
            $data = [
                'OrgUID'          => $this->_orgUID(),
                'DesignationName' => trim($p['DesignationName']),
                'Description'     => trim($p['Description'] ?? ''),
                'IsActive'        => 1,
                'UpdatedBy'       => $this->_userUID(),
            ];
            if ($uid === 0) {
                $data['CreatedBy'] = $this->_userUID();
                $res = $this->dbwrite_model->insertData('Organisation', 'DesignationTbl', $data);
            } else {
                $res = $this->dbwrite_model->updateData('Organisation', 'DesignationTbl', $data, ['DesignationUID' => $uid, 'OrgUID' => $this->_orgUID()]);
            }
            if ($res->Error) throw new Exception($res->Message);
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = $uid ? 'Updated.' : 'Created.';
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
            $uid = (int)$this->input->post('DesignationUID');
            if (!$uid) throw new Exception('Invalid.');
            $this->load->model('dbwrite_model'); $this->load->model('dbwrite_ext_model');
            $res = $this->dbwrite_model->updateData('Organisation', 'DesignationTbl',
                ['IsDeleted' => 1, 'UpdatedBy' => $this->_userUID()],
                ['DesignationUID' => $uid, 'OrgUID' => $this->_orgUID()]
            );
            if ($res->Error) throw new Exception($res->Message);
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Deleted.';
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
