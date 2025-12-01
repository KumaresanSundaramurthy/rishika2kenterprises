<?php defined('BASEPATH') OR exit('No direct script access allowed');

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Login extends CI_Controller {

    public $PageData = array();
    private $EndReturnData;

    public function __construct() {
        parent::__construct();
        
    }

    public function index() {
        redirect('portal', 'refresh');
    }

    public function login() {
        $this->load->view('login/view');
    }

    public function doLoginForm() {

        $this->load->model('formvalidation_model');

        $PostData = $this->input->post();
        $ErrorInForm = $this->formvalidation_model->validateForm($PostData);
        if(empty($ErrorInForm)) {

            $this->load->model('user_model');
            $UserData = $this->user_model->getUserByUserInfo(array('User.UserName' => $PostData['UserName']));

            if($UserData->Error === FALSE && count($UserData->Data) > 0 && sizeof($UserData->Data) == 1) {

                $UserPassword = base64_decode($UserData->Data[0]->UserPassword);
                if($PostData['UserPassword'] === $UserPassword) {

                    $this->load->model('login_model');
                    $jwtPayload = $this->login_model->formatJWTPayload($UserData->Data[0]);

                    if($jwtPayload->Error) {
                        $this->session->set_flashdata('danger', 'Oops! '.$jwtPayload->Message);
                    } else {

                        $newPayload = clone $jwtPayload;

                        $JwtReturnData = $this->login_model->setJwtToken($UserData->Data[0], $jwtPayload);
                        if(!$JwtReturnData->Error) {

                            $this->session->set_userdata('CachedUserMenuData', [
                                'MainModules' => $newPayload->JWTData['UserMainModule'] ?? [],
                                'SubModules'  => $newPayload->JWTData['UserSubModule'] ?? [],
                            ]);
                            $this->session->set_userdata('CachedUserModuleData', $newPayload->JWTData['ModuleInfo'] ?? []);
                            $this->session->set_userdata('CachedUserGenSettings', $newPayload->JWTData['GenSettings'] ?? []);
                            $this->session->set_userdata('CachedUserInfo', $UserData->Data[0]);
                            
                            redirect('dashboard', 'refresh');

                        } else {
                            $this->session->set_flashdata('danger', 'Oops! '.$JwtReturnData->Message);  
                        }
                        
                    }

                } else {
					$this->session->set_flashdata('danger', 'Oops! Password is incorrect.');
				}

            } else {
                $this->session->set_flashdata('danger', 'Oops! User Account not found.');
            }

        } else {
			$this->session->set_flashdata('danger', $ErrorInForm);
		}

        redirect('portal', 'refresh');

    }

    public function resetPassword() {

        $this->EndReturnData = new stdClass();
		try {

            $this->load->model('formvalidation_model');

            $PostData = $this->input->post();
            $ErrorInForm = $this->formvalidation_model->validateForm($PostData);
            if(empty($ErrorInForm)) {

                $this->load->model('user_model');
                $UserData = $this->user_model->getUserByUserInfo(array('User.UserUID' => $PostData['UserUID']));
                if($PostData['OldPassword'] !== base64_decode($UserData->Data[0]->UserPassword)) {

                    throw new Exception('Old Password do not match. Please try again.!', 200);

                } else {

                    $this->load->model('dbwrite_model');
                    $UpdateDataResp = $this->dbwrite_model->updateData('Users', 'UserTbl', ['Password' => base64_encode($PostData['ConfirmPassword'])], array('UserUID' => $PostData['UserUID']));

                    if($UpdateDataResp->Error === FALSE) {
                        $this->EndReturnData->Error = FALSE;
                        $this->EndReturnData->Message = 'Updated Successfully';
                    } else {
                        $this->EndReturnData->Error = TRUE;
                        $this->EndReturnData->Message = 'Error occured';
                    }

                }

            } else {
                $this->EndReturnData->Error = TRUE;
                $this->EndReturnData->Message = $ErrorInForm;
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		$this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;

    }

    public function logout() {

        $JwtEncoded = get_cookie(getenv('JWT_COOKIE_NAME'));

        if(isset($JwtEncoded)) {

			$JwtData = JWT::decode($JwtEncoded, new Key(getenv('JWT_KEY'), 'HS256'));
			if(isset($JwtData->key) && !empty($JwtData->key)) {
				$this->cacheservice->delete($JwtData->key);
			}

            // Deleted Unwanted Information Stored
            $this->session->unset_userdata('CachedUserMenuData');
            $this->session->unset_userdata('CachedUserModuleData');
            $this->session->unset_userdata('CachedUserGenSettings');
            $this->session->unset_userdata('CachedUserInfo');

			delete_cookie(getenv('JWT_COOKIE_NAME'));

		}

		redirect('portal', 'refresh');

    }

}