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
            $uid      = (int)($p['DepartmentUID'] ?? 0);
            $isSystem = (int)($p['IsSystem'] ?? 0);

            $this->load->model('dbwrite_model'); $this->load->model('dbwrite_ext_model');

            if ($isSystem && $uid > 0) {
                $data = ['Description' => trim($p['Description'] ?? ''), 'UpdatedBy' => $this->_userUID()];
                $res  = $this->dbwrite_model->updateData('Organisation', 'DepartmentTbl', $data, ['DepartmentUID' => $uid, 'OrgUID' => 0]);
                if ($res->Error) throw new Exception($res->Message);
                $this->EndReturnData->Message = 'Description updated.';
            } else {
                if (empty(trim($p['DepartmentName'] ?? ''))) throw new Exception('Department name is required.');
                $data = [
                    'OrgUID'         => $this->_orgUID(),
                    'DepartmentName' => trim($p['DepartmentName']),
                    'Description'    => trim($p['Description'] ?? ''),
                    'IsActive'       => 1,
                    'UpdatedBy'      => $this->_userUID(),
                ];
                if ($uid === 0) {
                    $data['CreatedBy'] = $this->_userUID();
                    $res = $this->dbwrite_model->insertData('Organisation', 'DepartmentTbl', $data);
                } else {
                    $res = $this->dbwrite_model->updateData('Organisation', 'DepartmentTbl', $data, ['DepartmentUID' => $uid, 'OrgUID' => $this->_orgUID()]);
                }
                if ($res->Error) throw new Exception($res->Message);
                $this->EndReturnData->Message = $uid ? 'Updated.' : 'Created.';
            }

            $this->EndReturnData->Error = FALSE;
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
            $uid = (int)$this->input->post('DepartmentUID');
            if (!$uid) throw new Exception('Invalid.');
            $this->load->model('dbwrite_model'); $this->load->model('dbwrite_ext_model');
            $res = $this->dbwrite_model->updateData('Organisation', 'DepartmentTbl',
                ['IsDeleted' => 1, 'UpdatedBy' => $this->_userUID()],
                ['DepartmentUID' => $uid, 'OrgUID' => $this->_orgUID()]
            );
            if ($res->Error) throw new Exception($res->Message);
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Deleted.';
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
            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';
            $resp     = services_get('api/departments/getList?orgId=' . $this->_orgUID(), ['Authorization: Bearer ' . $jwtToken]);
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
