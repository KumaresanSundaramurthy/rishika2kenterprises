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
            $p   = $this->input->post();
            $uid = (int)($p['HolidayUID'] ?? 0);
            if (empty(trim($p['HolidayName'] ?? ''))) throw new Exception('Holiday name is required.');
            if (empty($p['HolidayDate']))              throw new Exception('Holiday date is required.');
            $this->load->model('dbwrite_model'); $this->load->model('dbwrite_ext_model');
            $data = [
                'OrgUID'      => $this->_orgUID(),
                'HolidayName' => trim($p['HolidayName']),
                'HolidayDate' => $p['HolidayDate'],
                'Description' => trim($p['Description'] ?? ''),
                'IsOptional'  => (int)($p['IsOptional'] ?? 0),
                'IsActive'    => 1,
                'UpdatedBy'   => $this->_userUID(),
            ];
            if ($uid === 0) {
                $data['CreatedBy'] = $this->_userUID();
                $res = $this->dbwrite_model->insertData('Organisation', 'HolidayTbl', $data);
            } else {
                $res = $this->dbwrite_model->updateData('Organisation', 'HolidayTbl', $data, ['HolidayUID' => $uid, 'OrgUID' => $this->_orgUID()]);
            }
            if ($res->Error) throw new Exception($res->Message);
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = $uid ? 'Updated.' : 'Holiday added.';
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
            $uid = (int)$this->input->post('HolidayUID');
            if (!$uid) throw new Exception('Invalid.');
            $this->load->model('dbwrite_model'); $this->load->model('dbwrite_ext_model');
            $res = $this->dbwrite_model->updateData('Organisation', 'HolidayTbl',
                ['IsDeleted' => 1, 'UpdatedBy' => $this->_userUID()],
                ['HolidayUID' => $uid, 'OrgUID' => $this->_orgUID()]
            );
            if ($res->Error) throw new Exception($res->Message);
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Deleted.';
        } catch (Exception $e) {
            notifyError('Holidays::delete', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }
}
