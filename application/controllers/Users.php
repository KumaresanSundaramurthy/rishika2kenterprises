<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Users extends CI_Controller {

    public $pageData = array();
    private $EndReturnData;

    public function __construct() {
        parent::__construct();

    }

    public function index() {

        try {

            $this->load->model('roles_model');
            $JwtData = $this->pageData['JwtData'];
            $rolesResult = $this->roles_model->getRolesList($JwtData->User->OrgUID);
            $this->pageData['RolesList'] = $rolesResult->Error === FALSE ? $rolesResult->Data : [];

            $this->load->view('users/view', $this->pageData);

        } catch (Exception $e) {
            redirect('dashboard', 'refresh');
        }

    }

    public function saveUser() {

        $this->EndReturnData = new stdClass();
        try {

            $PostData = $this->input->post();
            $UserUID  = (int)($PostData['UserUID'] ?? 0);
            $JwtData  = $this->pageData['JwtData'];

            $FirstName = trim($PostData['FirstName'] ?? '');
            $LastName  = trim($PostData['LastName']  ?? '');
            $UserName  = trim($PostData['UserName']  ?? '');
            $Email     = trim($PostData['Email']     ?? '');
            $Mobile    = trim($PostData['Mobile']    ?? '');
            $RoleUID   = (int)($PostData['RoleUID']  ?? 0);
            $IsActive  = (int)($PostData['IsActive'] ?? 1);

            if (empty($FirstName)) throw new Exception('First name is required.');
            if (empty($UserName))  throw new Exception('Username is required.');
            if (empty($Email))     throw new Exception('Email address is required.');
            if (!$RoleUID)         throw new Exception('Role is required.');

            $this->load->model('dbwrite_model');

            $userData = [
                'FirstName'     => $FirstName,
                'LastName'      => $LastName,
                'UserName'      => $UserName,
                'EmailAddress'  => $Email,
                'MobileNumber'  => $Mobile,
                'RoleUID'       => $RoleUID,
                'OrgUID'        => $JwtData->User->OrgUID,
                'BranchUID'     => $JwtData->User->BranchUID,
                'IsActive'      => $IsActive,
                'IsDeleted'     => 0,
            ];

            if ($UserUID > 0) {
                $userData['UpdatedBy'] = $JwtData->User->UserUID;
                $userData['UpdatedOn'] = date('Y-m-d H:i:s');
                $result = $this->dbwrite_model->updateData('Users', 'UserTbl', $userData, ['UserUID' => $UserUID]);
                $msg = 'User updated successfully.';
            } else {
                $Password = $PostData['Password'] ?? '';
                if (empty($Password)) throw new Exception('Password is required.');
                $userData['Password']  = base64_encode($Password);
                $userData['CreatedBy'] = $JwtData->User->UserUID;
                $userData['CreatedOn'] = date('Y-m-d H:i:s');
                $result = $this->dbwrite_model->insertData('Users', 'UserTbl', $userData);
                $msg = 'User created successfully.';
            }

            if ($result->Error) throw new Exception($result->Message);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = $msg;
            $this->EndReturnData->UID     = $UserUID > 0 ? $UserUID : ($result->ID ?? 0);

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

}