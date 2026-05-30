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
        $this->load->view('login/view', ['OrgLogo' => $this->_getDefaultOrgLogo()]);
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

                            $newPayload   = clone $jwtPayload;
                            $orgShortCode = $newPayload->JWTData['Org']['OrgShortCode'] ?? '';
                            $orgToken     = $newPayload->JWTData['Org']['OrgToken']     ?? '';

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
                                $this->redisservice->setUserCache('menus',       $userUID, $newPayload->JWTData['UserMainModule'] ?? [], $loginExpiry, $orgShortCode, $orgToken);
                                $this->redisservice->setUserCache('submenus',    $userUID, $newPayload->JWTData['UserSubModule']  ?? [], $loginExpiry, $orgShortCode, $orgToken);
                                $this->redisservice->setUserCache('modules',     $userUID, $newPayload->JWTData['ModuleInfo']     ?? [], $loginExpiry, $orgShortCode, $orgToken);
                                $this->redisservice->setUserCache('permissions', $userUID, $newPayload->JWTData['Permissions']    ?? [], $loginExpiry, $orgShortCode, $orgToken);
                                $this->redisservice->setUserCache('userinfo',    $userUID, $UserData->Data[0],                         $loginExpiry, $orgShortCode, $orgToken);

                                $orgUID = $UserData->Data[0]->UserOrgUID ?? null;
                                if ($orgUID) {
                                    $this->load->model('users_model');
                                    $orgUsers = $this->users_model->getOrgUsersForCache((int)$orgUID);
                                    $this->redisservice->setCache($this->redisservice->orgKey('org_users', $orgShortCode, $orgToken), $orgUsers, $loginExpiry);

                                    // Pre-warm org info cache (full CDN-resolved URL stored)
                                    $this->load->model('organisation_model');
                                    $this->organisation_model->getOrgInfoCached((int)$orgUID, $orgShortCode, $orgToken);

                                    // Cache all active dispatch addresses
                                    $dispatchAddresses = $this->organisation_model->getAllOrgDispatchAddresses((int)$orgUID);
                                    $this->redisservice->setCache(
                                        $this->redisservice->orgKey('org_dispatch_addresses', $orgShortCode, $orgToken),
                                        $dispatchAddresses,
                                        $loginExpiry
                                    );
                                }

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

    // ── Forgot / Reset password (public, unauthenticated) ────────────────────

    public function forgotPassword() {
        $this->load->helper('auth');
        if (is_authenticated()) { redirect('dashboard', 'refresh'); return; }
        $this->load->view('login/forgot_password', ['OrgLogo' => $this->_getDefaultOrgLogo()]);
    }

    public function sendResetLink() {
        try {
            $this->load->helper('auth');
            if (is_authenticated()) { redirect('dashboard', 'refresh'); return; }

            $email = trim((string)$this->input->post('EmailAddress'));
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->session->set_flashdata('danger', 'Please enter a valid email address.');
                redirect('forgot-password', 'refresh');
                return;
            }

            $this->load->model('passwordreset_model');
            $user = $this->passwordreset_model->getUserByEmail($email);

            if (!$user) {
                $this->session->set_flashdata('danger', 'This email address is not registered with us. Please check and try again.');
                redirect('forgot-password', 'refresh');
                return;
            }

            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->insertData('Users', 'PasswordResetTbl', [
                'UserUID'   => $user->UserUID,
                'Token'     => $token,
                'ExpiresAt' => $expires,
                'IPAddress' => $this->input->ip_address(),
            ]);

            $this->_sendResetEmail($user, base_url('reset-password/' . $token));

            $this->session->set_flashdata('success', 'A password reset link has been sent to <strong>' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</strong>. It expires in 15 minutes.');
            redirect('forgot-password', 'refresh');

        } catch (Exception $e) {
            $this->session->set_flashdata('danger', 'Something went wrong. Please try again.');
            redirect('forgot-password', 'refresh');
        }
    }

    public function showResetForm($token = null) {
        $this->load->helper('auth');
        if (is_authenticated()) { redirect('dashboard', 'refresh'); return; }
        if (empty($token)) { redirect('forgot-password', 'refresh'); return; }

        $this->load->model('passwordreset_model');
        $tokenInfo = $this->passwordreset_model->getValidToken($token);

        if (!$tokenInfo) {
            $existing = $this->passwordreset_model->tokenExists($token);
            $this->session->set_flashdata('token_error', $existing ? 'expired' : 'invalid');
            redirect('forgot-password', 'refresh');
            return;
        }

        $this->PageData['token']         = $token;
        $this->PageData['remainingSecs']  = max(0, strtotime($tokenInfo->ExpiresAt) - time());
        $this->PageData['OrgLogo']        = $this->_getDefaultOrgLogo();
        $this->load->view('login/reset_password', $this->PageData);
    }

    public function doForgotReset() {
        $token = trim((string)$this->input->post('ResetToken'));
        try {
            $this->load->helper('auth');
            if (is_authenticated()) { redirect('dashboard', 'refresh'); return; }

            $password = (string)$this->input->post('NewPassword');
            $confirm  = (string)$this->input->post('ConfirmPassword');

            if (empty($token) || empty($password) || empty($confirm)) {
                $this->session->set_flashdata('danger', 'All fields are required.');
                redirect('reset-password/' . $token, 'refresh');
                return;
            }

            if ($password !== $confirm) {
                $this->session->set_flashdata('danger', 'Passwords do not match.');
                redirect('reset-password/' . $token, 'refresh');
                return;
            }

            if (strlen($password) < 6) {
                $this->session->set_flashdata('danger', 'Password must be at least 6 characters.');
                redirect('reset-password/' . $token, 'refresh');
                return;
            }

            $this->load->model('passwordreset_model');
            $tokenInfo = $this->passwordreset_model->getValidToken($token);

            if (!$tokenInfo) {
                $this->session->set_flashdata('token_error', 'expired');
                redirect('forgot-password', 'refresh');
                return;
            }

            $this->load->model('dbwrite_model');

            $this->dbwrite_model->updateData('Users', 'UserTbl',
                ['Password' => base64_encode($password)],
                ['UserUID'  => $tokenInfo->UserUID]
            );

            $this->dbwrite_model->updateData('Users', 'PasswordResetTbl',
                ['IsUsed' => 1],
                ['Token'  => $token]
            );

            $this->_sendPasswordChangedEmail($tokenInfo);

            $this->session->set_flashdata('success', 'Password updated successfully. You can now sign in.');
            redirect('portal', 'refresh');

        } catch (Exception $e) {
            $this->session->set_flashdata('danger', 'Something went wrong. Please try again.');
            if (!empty($token)) {
                redirect('reset-password/' . $token, 'refresh');
            } else {
                redirect('forgot-password', 'refresh');
            }
        }
    }

    private function _sendResetEmail($user, string $resetLink): void {
        try {
            $firstName = htmlspecialchars($user->FirstName ?? 'User', ENT_QUOTES, 'UTF-8');

            $body = '<!DOCTYPE html>
<html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#040b18;font-family:\'Segoe UI\',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#040b18;padding:40px 16px;">
  <tr><td align="center">
    <table width="560" cellpadding="0" cellspacing="0" style="background:#060e20;border-radius:16px;overflow:hidden;border:1px solid rgba(245,158,11,0.22);max-width:560px;width:100%;">
      <tr><td style="padding:30px 40px 24px;border-bottom:1px solid rgba(245,158,11,0.12);">
        <span style="font-size:20px;font-weight:800;color:#f59e0b;letter-spacing:-0.3px;">RISHIKA 2K</span>
        <span style="font-size:20px;font-weight:300;color:#e2e8f0;"> ENTERPRISES</span>
        <p style="margin:5px 0 0;font-size:11px;color:#475569;text-transform:uppercase;letter-spacing:1.5px;">Billing Management System</p>
      </td></tr>
      <tr><td style="background:linear-gradient(90deg,#f59e0b,#d97706);padding:8px 40px;">
        <span style="font-size:12px;font-weight:700;color:#040b18;letter-spacing:1px;">PASSWORD RESET REQUEST</span>
      </td></tr>
      <tr><td style="padding:32px 40px;">
        <p style="margin:0 0 6px;font-size:15px;font-weight:600;color:#f1f5f9;">Hi ' . $firstName . ',</p>
        <p style="margin:0 0 24px;font-size:14px;color:#94a3b8;line-height:1.7;">We received a request to reset your account password. Click the button below — this link is valid for <strong style="color:#f59e0b;">15 minutes only</strong> and can be used once.</p>
        <table cellpadding="0" cellspacing="0" style="margin:0 0 28px;">
          <tr><td>
            <a href="' . $resetLink . '" style="display:inline-block;padding:13px 30px;background:linear-gradient(135deg,#f59e0b,#d97706);color:#040b18;font-size:14px;font-weight:700;text-decoration:none;border-radius:10px;">Reset My Password</a>
          </td></tr>
        </table>
        <p style="margin:0 0 6px;font-size:12px;color:#64748b;">Button not working? Copy this link into your browser:</p>
        <p style="margin:0 0 24px;font-size:12px;color:#f59e0b;word-break:break-all;">' . $resetLink . '</p>
        <div style="border-top:1px solid rgba(255,255,255,0.06);padding-top:18px;">
          <p style="margin:0;font-size:12px;color:#475569;line-height:1.6;">If you did not request this, ignore this email — your password will not change. For security, this link can only be used once.</p>
        </div>
      </td></tr>
      <tr><td style="padding:16px 40px;border-top:1px solid rgba(245,158,11,0.1);background:rgba(245,158,11,0.03);">
        <p style="margin:0;font-size:11px;color:#334155;text-align:center;">&copy; ' . date('Y') . ' Rishika 2K Enterprises &middot; Agricultural Machinery &middot; Tamil Nadu</p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body></html>';

            $this->_sendViaBrevoApi(
                $user->EmailAddress,
                $user->FirstName ?? 'User',
                'Password Reset — Rishika 2K Enterprises',
                $body
            );

        } catch (Exception $e) {
            error_log('[ForgotPassword] Email send failed: ' . $e->getMessage());
        }
    }

    private function _sendPasswordChangedEmail($tokenInfo): void {
        try {
            $firstName = htmlspecialchars($tokenInfo->FirstName ?? 'User', ENT_QUOTES, 'UTF-8');
            $email     = $tokenInfo->EmailAddress;
            $changedAt = date('d M Y, h:i A');
            $loginUrl  = base_url('portal');

            $body = '<!DOCTYPE html>
<html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#040b18;font-family:\'Segoe UI\',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#040b18;padding:40px 16px;">
  <tr><td align="center">
    <table width="560" cellpadding="0" cellspacing="0" style="background:#060e20;border-radius:16px;overflow:hidden;border:1px solid rgba(16,185,129,0.22);max-width:560px;width:100%;">

      <tr><td style="padding:30px 40px 24px;border-bottom:1px solid rgba(16,185,129,0.12);">
        <span style="font-size:20px;font-weight:800;color:#f59e0b;letter-spacing:-0.3px;">RISHIKA 2K</span>
        <span style="font-size:20px;font-weight:300;color:#e2e8f0;"> ENTERPRISES</span>
        <p style="margin:5px 0 0;font-size:11px;color:#475569;text-transform:uppercase;letter-spacing:1.5px;">Billing Management System</p>
      </td></tr>

      <tr><td style="background:linear-gradient(90deg,#10b981,#059669);padding:8px 40px;">
        <span style="font-size:12px;font-weight:700;color:#ffffff;letter-spacing:1px;">&#10003;&nbsp; PASSWORD CHANGED SUCCESSFULLY</span>
      </td></tr>

      <tr><td style="padding:32px 40px;">
        <p style="margin:0 0 6px;font-size:15px;font-weight:600;color:#f1f5f9;">Hi ' . $firstName . ',</p>
        <p style="margin:0 0 24px;font-size:14px;color:#94a3b8;line-height:1.7;">
          Your account password was successfully changed on <strong style="color:#f1f5f9;">' . $changedAt . '</strong>.<br>
          You can now sign in with your new password.
        </p>

        <table cellpadding="0" cellspacing="0" style="margin:0 0 28px;">
          <tr><td>
            <a href="' . $loginUrl . '" style="display:inline-block;padding:13px 30px;background:linear-gradient(135deg,#10b981,#059669);color:#ffffff;font-size:14px;font-weight:700;text-decoration:none;border-radius:10px;">Sign In Now</a>
          </td></tr>
        </table>

        <div style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);border-radius:10px;padding:16px 20px;">
          <p style="margin:0 0 6px;font-size:13px;font-weight:600;color:#fca5a5;">&#9888;&nbsp; Did not make this change?</p>
          <p style="margin:0;font-size:13px;color:#94a3b8;line-height:1.6;">
            If you did not reset your password, your account may be at risk. Please contact your administrator immediately or request another password reset.
          </p>
        </div>
      </td></tr>

      <tr><td style="padding:16px 40px;border-top:1px solid rgba(16,185,129,0.1);background:rgba(16,185,129,0.03);">
        <p style="margin:0;font-size:11px;color:#334155;text-align:center;">&copy; ' . date('Y') . ' Rishika 2K Enterprises &middot; Agricultural Machinery &middot; Tamil Nadu</p>
      </td></tr>

    </table>
  </td></tr>
</table>
</body></html>';

            $this->_sendViaBrevoApi(
                $email,
                $tokenInfo->FirstName ?? 'User',
                'Your Password Has Been Changed — Rishika 2K Enterprises',
                $body
            );

        } catch (Exception $e) {
            error_log('[PasswordChanged] Email send failed: ' . $e->getMessage());
        }
    }

    private function _sendViaBrevoApi(string $toEmail, string $toName, string $subject, string $htmlBody): void {
        $apiKey    = getenv('BREVO_API_KEY');
        $fromEmail = getenv('MAIL_FROM_EMAIL') ?: 'noreply@rishika2kenterprises.com';
        $fromName  = getenv('MAIL_FROM_NAME')  ?: 'Rishika 2K Enterprises';

        $payload = json_encode([
            'sender'      => ['name' => $fromName, 'email' => $fromEmail],
            'to'          => [['email' => $toEmail, 'name' => $toName]],
            'subject'     => $subject,
            'htmlContent' => $htmlBody,
        ]);

        $ch = curl_init('https://api.brevo.com/v3/smtp/email');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => [
                'api-key: ' . $apiKey,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            throw new Exception('Email delivery failed: ' . $curlErr);
        }
        if ($httpCode < 200 || $httpCode >= 300) {
            error_log('[Brevo API Error] HTTP ' . $httpCode . ': ' . $response);
            throw new Exception('Email delivery failed (HTTP ' . $httpCode . ').');
        }
    }

    // ── In-app password change (authenticated user) ───────────────────────────

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

            // Org object exists in new JWT structure; fall back to User for old cached JWTs
            $logoutShortCode = $getAuditInfo->Value->Org->OrgShortCode ?? ($getAuditInfo->Value->User->OrgShortCode ?? '');
            $logoutOrgToken  = $getAuditInfo->Value->Org->OrgToken     ?? ($getAuditInfo->Value->User->OrgToken     ?? '');
            if ($userUID) {
                $this->redisservice->deleteAllUserCache($userUID, $logoutShortCode, $logoutOrgToken);
            }

            $orgUID = $getAuditInfo->Value->Org->OrgUID ?? ($getAuditInfo->Value->User->OrgUID ?? null);
            if ($orgUID) {
                $this->redisservice->deleteCache($this->redisservice->orgKey('org_users', $logoutShortCode, $logoutOrgToken));
            }

			delete_cookie(getenv('JWT_COOKIE_NAME'));

		}

		redirect('portal', 'refresh');

    }

    private function _getDefaultOrgLogo() {
        try {
            $this->load->model('organisation_model');
            return $this->organisation_model->getDefaultOrgLogo();
        } catch (Exception $e) {
            return '';
        }
    }

}