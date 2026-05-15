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
        $this->load->helper('auth');
        if (is_authenticated()) {
            redirect('dashboard', 'refresh');
            return;
        }
        $this->load->view('login/view');
    }

    public function doLoginForm() {

        try {

            $this->load->model('formvalidation_model');

            $PostData = $this->input->post();
            $ErrorInForm = $this->formvalidation_model->validateForm($PostData);
            if(empty($ErrorInForm)) {

                $this->load->model('user_model');
                $UserData = $this->user_model->getUserByEmailOrUsername($PostData['UserName']);

                if($UserData->Error === FALSE && count($UserData->Data) > 0 && sizeof($UserData->Data) == 1) {

                    if ($UserData->Data[0]->IsLocked == 1) {
                        $this->logLoginFailure($PostData['UserName'], 'Account locked');
                        throw new Exception('Account is locked. Contact administrator.');
                    }

                    $UserPassword = base64_decode($UserData->Data[0]->UserPassword);
                    if($PostData['UserPassword'] === $UserPassword) {

                        // Check subscription status
                        $this->load->library('subscription');
                        $subscriptionCheck = $this->subscription->checkSubscription($UserData->Data[0]->UserUID);
                        
                        // Log login attempt with subscription status
                        $this->subscription->logLoginAttempt(
                            $UserData->Data[0]->UserUID,
                            $PostData['UserName'],
                            $subscriptionCheck->isValid ? 'Success' : 'Blocked_Expired',
                            $subscriptionCheck->status,
                            $subscriptionCheck->isValid ? null : $subscriptionCheck->message
                        );

                        // Block login if subscription is invalid
                        if (!$subscriptionCheck->isValid) {
                            $this->session->set_flashdata('subscription_expired', true);
                            $this->session->set_flashdata('subscription_message', $subscriptionCheck->message);
                            $this->session->set_flashdata('subscription_status', $subscriptionCheck->status);
                            throw new Exception($subscriptionCheck->message);
                        }

                        $this->load->model('login_model');
                        $jwtPayload = $this->login_model->formatJWTPayload($UserData->Data[0]);

                        if($jwtPayload->Error) {
                            $this->session->set_flashdata('danger', 'Oops! '.$jwtPayload->Message);
                        } else {

                            $newPayload = clone $jwtPayload;

                            $auditId = $this->logLoginSuccess($UserData->Data[0]);
                            $jwtPayload->JWTData['User']['auditId'] = $auditId ?? 0;

                            // Single-session token — embedded in JWT payload so every request can validate it
                            $sessionToken = bin2hex(random_bytes(32));
                            $jwtPayload->JWTData['User']['SessionToken'] = $sessionToken;

                            $JwtReturnData = $this->login_model->setJwtToken($UserData->Data[0], $jwtPayload);
                            if(!$JwtReturnData->Error) {

                                $this->load->model('dbwrite_model');
                                $deviceInfo = $this->getDeviceInfo();
                                $this->dbwrite_model->updateData('Users', 'UserTbl', [
                                    'LastLogin'           => date('Y-m-d H:i:s'),
                                    'CurrentSessionToken' => $sessionToken,
                                    'LastLoginOn'         => date('Y-m-d H:i:s'),
                                    'LastLoginIP'         => $this->input->ip_address(),
                                    'LastLoginDevice'     => $deviceInfo['browser'] . ' / ' . $deviceInfo['os'] . ' (' . $deviceInfo['device_type'] . ')',
                                ], ['UserUID' => $UserData->Data[0]->UserUID]);

                                // User-keyed Redis entry — new login overwrites old, invalidating previous session
                                $this->redisservice->setCache(
                                    'UserActiveSession_' . $UserData->Data[0]->UserUID,
                                    $sessionToken,
                                    (int) getenv('LOGIN_EXPIRE_SECS')
                                );

                                $loginExpiry = (int) getenv('LOGIN_EXPIRE_SECS');
                                $userUID     = $UserData->Data[0]->UserUID;
                                $this->redisservice->setUserCache('menus',       $userUID, $newPayload->JWTData['UserMainModule'] ?? [], $loginExpiry);
                                $this->redisservice->setUserCache('submenus',    $userUID, $newPayload->JWTData['UserSubModule']  ?? [], $loginExpiry);
                                $this->redisservice->setUserCache('modules',     $userUID, $newPayload->JWTData['ModuleInfo']     ?? [], $loginExpiry);
                                $this->redisservice->setUserCache('permissions', $userUID, $newPayload->JWTData['Permissions']    ?? [], $loginExpiry);
                                $this->redisservice->setUserCache('settings',    $userUID, $newPayload->JWTData['GenSettings']    ?? [], $loginExpiry);
                                $this->redisservice->setUserCache('userinfo',    $userUID, $UserData->Data[0],                         $loginExpiry);
                                
                                redirect('dashboard', 'refresh');

                            } else {
                                $this->session->set_flashdata('danger', 'Oops! '.$JwtReturnData->Message);  
                            }
                            
                        }

                    } else {
                        $this->logLoginFailure($PostData['UserName'], 'Invalid credentials');

                        $this->load->model('login_model');
                        $failedAttempts = $this->login_model->getFailedAttempts($PostData['UserName']);
                        if ($failedAttempts >= 5) {

                            $this->load->model('dbwrite_model');
                            $this->dbwrite_model->updateData('Users', 'UserTbl', ['IsLocked' => 1], array('UserName' => $PostData['UserName']));
                            
                            $this->logLoginFailure($PostData['UserName'], 'Account locked - too many attempts');
                        }

                        $this->session->set_flashdata('danger', 'Oops! Password is incorrect.');
                    }

                } else {
                    $this->session->set_flashdata('danger', 'Oops! User Account not found.');
                }

            } else {
                $this->session->set_flashdata('danger', $ErrorInForm);
            }

        } catch (Exception $e) {
            $this->session->set_flashdata('danger', $e->getMessage());
        }

        redirect('portal', 'refresh');

    }

    private function logLoginSuccess($userData) {

        try {

            $this->load->model('dbwrite_model');
            
            $deviceInfo = $this->getDeviceInfo();
            
            $auditData = [
                'UserUID' => $userData->UserUID,
                'OrgUID' => $userData->UserOrgUID,
                'BranchUID' => $userData->BranchUID,
                'LoginStatus' => 'SUCCESS',
                'LoginType' => 'WEB', // or detect from request
                'AttemptedUsername' => $this->input->post('UserName'),
                'IPAddress' => $this->input->ip_address(),
                'UserAgent' => $this->input->user_agent(),
                'DeviceType' => $deviceInfo['device_type'],
                'Browser' => $deviceInfo['browser'],
                'OS' => $deviceInfo['os'],
                'TokenIssued' => 1, // If JWT issued
            ];
            
            $result = $this->dbwrite_model->insertData('Security', 'UserLoginAudit', $auditData);
            
            return $result->ID;
            
        } catch (Exception $e) {
            return null;
        }

    }

    private function logLoginFailure($username, $reason) {

        try {

            $this->load->model('dbwrite_model');

            $deviceInfo = $this->getDeviceInfo();
            
            $auditData = [
                'UserUID' => NULL, // Unknown user
                'OrgUID' => NULL,
                'BranchUID' => NULL,
                'LoginStatus' => 'FAILED',
                'AttemptedUsername' => $username,
                'FailureReason' => $reason, // 'Invalid password', 'User not found', 'Account locked'
                'IPAddress' => $this->input->ip_address(),
                'UserAgent' => $this->input->user_agent(),
                'DeviceType' => $deviceInfo['device_type'],
                'Browser' => $deviceInfo['browser'],
                'OS' => $deviceInfo['os'],
            ];
            
            return $this->dbwrite_model->insertData('Security', 'UserLoginAudit', $auditData);
            
        } catch (Exception $e) {
            return null;
        }

    }

    private function getDeviceInfo() {
        $userAgent = $this->input->user_agent();
        
        $deviceInfo = [
            'device_type' => 'Desktop',
            'browser' => 'Unknown',
            'os' => 'Unknown'
        ];
        
        // Simple device detection
        if (preg_match('/(mobile|android|iphone|ipad)/i', $userAgent)) {
            $deviceInfo['device_type'] = 'Mobile';
        } elseif (preg_match('/(tablet|ipad)/i', $userAgent)) {
            $deviceInfo['device_type'] = 'Tablet';
        }
        
        // Browser detection
        if (preg_match('/Chrome/i', $userAgent)) {
            $deviceInfo['browser'] = 'Chrome';
        } elseif (preg_match('/Firefox/i', $userAgent)) {
            $deviceInfo['browser'] = 'Firefox';
        } elseif (preg_match('/Safari/i', $userAgent)) {
            $deviceInfo['browser'] = 'Safari';
        }
        
        // OS detection
        if (preg_match('/Windows/i', $userAgent)) {
            $deviceInfo['os'] = 'Windows';
        } elseif (preg_match('/Mac OS/i', $userAgent)) {
            $deviceInfo['os'] = 'Mac OS';
        } elseif (preg_match('/Linux/i', $userAgent)) {
            $deviceInfo['os'] = 'Linux';
        } elseif (preg_match('/Android/i', $userAgent)) {
            $deviceInfo['os'] = 'Android';
        } elseif (preg_match('/iOS/i', $userAgent)) {
            $deviceInfo['os'] = 'iOS';
        }
        
        return $deviceInfo;
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

            try {
                $JwtData = JWT::decode($JwtEncoded, new Key(getenv('JWT_KEY'), 'HS256'));
            } catch (Exception $e) {
                $JwtData = null;
            }

			if(isset($JwtData->key) && !empty($JwtData->key)) {

                $getAuditInfo = $this->redisservice->getCache($JwtData->key);
                if($getAuditInfo->Error === false) {

                    // Update audit log
                    $auditId = $getAuditInfo->Value->User->auditId ?? null;
                    if ($auditId) {
                        $this->load->model('login_model');
                        $UserData = $this->login_model->getUserAuditInfo(array('ula.AuditID' => $auditId));
                        if(isset($UserData) && count($UserData) > 0) {
                            $userData = $UserData[0];
                            $this->load->model('dbwrite_model');
                            $this->dbwrite_model->updateData('Security', 'UserLoginAudit', ['LogoutTime' => date('Y-m-d H:i:s'), 'SessionDuration' => time() - strtotime($userData->LoginTime)], ['AuditID' => $auditId]);
                        }
                    }

                    // Clear single-session token so the user-keyed entry is revoked
                    $userUID = $getAuditInfo->Value->User->UserUID ?? null;
                    if ($userUID) {
                        $this->redisservice->deleteCache('UserActiveSession_' . $userUID);
                        $this->load->model('dbwrite_model');
                        $this->dbwrite_model->updateData('Users', 'UserTbl', ['CurrentSessionToken' => null], ['UserUID' => $userUID]);
                    }

                }

				$this->redisservice->deleteCache($JwtData->key);

			}

            if ($userUID) {
                $this->redisservice->deleteAllUserCache($userUID);
            }

			delete_cookie(getenv('JWT_COOKIE_NAME'));

		}

		redirect('portal', 'refresh');

    }

}