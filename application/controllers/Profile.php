<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Profile extends CI_Controller {

    public $pageData = array();
    private $EndReturnData;

    public function __construct() {
        parent::__construct();

    }

    public function index() {

        try {

            $this->load->model('global_model');
            $GetCountryInfo = $this->global_model->getCountryInfo();
            $this->pageData['CountryInfo'] = $GetCountryInfo->Error === FALSE ? $GetCountryInfo->Data : [];

            $this->load->model('user_model');
            $this->pageData['userInfo'] = $this->user_model->getUserByUserInfo(['User.UserUID' => $this->pageData['JwtData']->User->UserUID])->Data[0];

            $this->load->view('profile/view', $this->pageData);

        } catch (Exception $e) {
            redirect('dashboard', 'refresh');
        }

    }

    public function updateProfileDetails() {

        $this->EndReturnData = new stdClass();
		try {

            $this->load->model('formvalidation_model');

            $PostData = $this->input->post();
            $ErrorInForm = $this->formvalidation_model->profValidateForm($PostData);
            if (!empty($ErrorInForm)) {
                throw new Exception($ErrorInForm);
            }

            $userUID = $this->pageData['JwtData']->User->UserUID;
            $now     = time();

            if (!empty($PostData['IsPasswordUpdate']) && $PostData['IsPasswordUpdate'] == 1) {
                $this->load->model('global_model');
                $userData = $this->global_model->getSingleRow('Users', 'UserTbl', ['UserUID' => $userUID]);
                if (!$userData) {
                    throw new Exception('User not found');
                }
                if($PostData['oldPassword'] !== base64_decode($userData->Password)) {
                    throw new Exception('Old password is incorrect');
                }
                if ($PostData['newPassword'] !== $PostData['confirmPassword']) {
                    throw new Exception('New password and Confirm password do not match');
                }
                if ($PostData['oldPassword'] === $PostData['newPassword']) {
                    throw new Exception('Old Password and New Password cannot be the same');
                }
            }

            $updateProfData = [
                'FirstName'         => getPostValue($PostData, 'fistName'),
                'LastName'          => getPostValue($PostData, 'lastName'),
                'CountryCode'       => getPostValue($PostData, 'CountryCode'),
                'CountryISO2'       => getPostValue($PostData, 'CountryISO2'),
                'MobileNumber'      => getPostValue($PostData, 'MobileNumber', 'Array', NULL, false),
                'UpdatedBy'         => $userUID,
                'UpdatedOn'         => $now,
            ];
            if (!empty($PostData['ImageRemoved'])) $updateProfData['Image'] = NULL;
            if (!empty($PostData['IsPasswordUpdate']) && $PostData['IsPasswordUpdate'] == 1) {
                $updateProfData['Password'] = base64_encode($PostData['newPassword']);
            }

            $this->load->model('dbwrite_model');
            $updateResp = $this->dbwrite_model->updateData('Users', 'UserTbl', $updateProfData, array('UserUID' => $PostData['userUid']));
            if ($updateResp->Error) {
                throw new Exception($updateResp->Message);
            }

            if(isset($_FILES['UploadImage'])) {
                $UploadResp = $this->globalservice->fileUploadService($_FILES['UploadImage'], 'profile/images/', 'Image', ['Users', 'UserTbl', array('UserUID' => $PostData['userUid'])]);
                if($UploadResp->Error === TRUE) {
                    throw new Exception($UploadResp->Message);
                }
            }

            $this->globalservice->refreshUserCache();
            
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Updated Successfully';

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        
        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function check_new_password($newPassword) {
        $oldPassword = $this->input->post('oldPassword');
        if ($oldPassword === $newPassword) {
            $this->form_validation->set_message('check_new_password', 'Old Password and New Password cannot be the same');
            return FALSE;
        }
        return TRUE;
    }

}