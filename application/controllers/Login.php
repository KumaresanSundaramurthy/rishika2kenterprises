<?php defined('BASEPATH') OR exit('No direct script access allowed');

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Login extends CI_Controller {

    public $PageData = array();
    protected $EndReturnData;

    public function __construct() {
        parent::__construct();
        
    }

    public function index() {
        redirect('login', 'refresh');
    }

    public function login() {
        $this->load->helper('auth');
        if (is_authenticated()) {
            redirect('dashboard', 'refresh');
            return;
        }
        $this->load->model('organisation_model');
        $orgSettings    = $this->organisation_model->getDefaultOrgSettings();
        $twoStepEnabled = (bool)$orgSettings->TwoStepLogin;
        $this->load->view('login/view', [
            'OrgLogo'        => $this->_getDefaultOrgLogo(),
            'TwoStepEnabled' => $twoStepEnabled,
            'CdnBase'        => rtrim(getenv('CFLARE_R2_CDN'), '/'),
        ]);
    }

    public function verifyEmail(string $token = ''): void {
        $token   = trim($token ?: $this->input->get('token'));
        $status  = 'invalid';
        $message = 'This verification link is invalid. It may have already been used or the link is incorrect.';

        if (!empty($token)) {
            try {
                $ReadDb = $this->load->database('ReadDB', TRUE);
                $ReadDb->db_debug = FALSE;

                /* Fetch the token row regardless of expiry to distinguish all cases */
                $row = $ReadDb->select('OrgUID, IsEmailVerified, EmailVerifyExpiry')
                    ->from('Organisation.OrganisationTbl')
                    ->where('EmailVerifyToken', $token)
                    ->limit(1)
                    ->get()->row();

                if (!$row) {
                    /* Token not found — invalid or already cleared after previous success */
                    $status  = 'invalid';
                    $message = 'This verification link is invalid. It may have already been used or the link is incorrect.';
                } elseif ((int)(bool)$row->IsEmailVerified === 1) {
                    /* Email already verified */
                    $status  = 'already_verified';
                    $message = 'Your email address has already been verified. You can log in now.';
                } elseif (strtotime($row->EmailVerifyExpiry) < time()) {
                    /* Token found but expired */
                    $status  = 'expired';
                    $message = 'This verification link has expired. Links are valid for 24 hours only. Please request a new one below.';
                } else {
                    /* Valid — verify now */
                    $this->load->model('dbwrite_model');
                    $WriteDb = $this->dbwrite_model->getWriteDb();
                    $WriteDb->db_debug = FALSE;
                    $WriteDb->where('OrgUID', (int)$row->OrgUID)->update('Organisation.OrganisationTbl', [
                        'IsEmailVerified'   => 1,
                        'EmailVerifyToken'  => null,
                        'EmailVerifyExpiry' => null,
                    ]);
                    $status  = 'success';
                    $message = 'Your email address has been verified successfully. You can now log in.';
                }
            } catch (Throwable $e) {
                notifyError('Login::verifyEmail', $e);
            }
        }

        $this->load->view('login/verify_email', [
            'status'  => $status,
            'message' => $message,
        ]);
    }

    public function resendVerificationEmail(): void {
        header('Content-Type: application/json');
        $this->load->library('telegramnotifier');

        $result          = new stdClass();
        $result->Error   = false;
        /* Generic success always returned — prevents account enumeration */
        $result->Message = 'If this account is registered and unverified, a new verification link has been sent. Please check your inbox.';

        try {
            $identifier = strtolower(trim($this->input->post('identifier') ?? ''));
            if (empty($identifier)) {
                throw new Exception('Please enter your username or email.');
            }

            $ReadDb = $this->load->database('ReadDB', TRUE);
            $ReadDb->db_debug = FALSE;

            /* Look up the org directly by its email address, then join to get admin's first name */
            $row = $ReadDb->select('O.OrgUID, O.EmailAddress AS OrgEmail, U.FirstName')
                ->from('Organisation.OrganisationTbl O')
                ->join('Users.UserTbl U', 'U.OrgUID = O.OrgUID AND U.IsActive = 1 AND U.IsDeleted = 0', 'left')
                ->where('O.EmailAddress',    $identifier)
                ->where('O.IsEmailVerified', 0)
                ->order_by('U.UserUID', 'ASC')
                ->limit(1)
                ->get()->row();

            if (!$row || empty($row->OrgUID)) {
                Telegramnotifier::alert('resendVerificationEmail: user/org not found', [
                    'Identifier' => $identifier,
                    'LastQuery'  => $ReadDb->last_query(),
                ]);
            } else {
                $this->load->model('signup_model');
                $this->signup_model->sendVerificationEmail(
                    (int) $row->OrgUID,
                    $row->FirstName ?: 'there',
                    strtolower(trim($row->OrgEmail))
                );
            }
        } catch (Exception $e) {
            $result->Error   = true;
            $result->Message = $e->getMessage();
            Telegramnotifier::error('Login::resendVerificationEmail', $e, ['Identifier' => $this->input->post('identifier') ?? '']);
        } catch (Throwable $e) {
            Telegramnotifier::error('Login::resendVerificationEmail', $e, ['Identifier' => $this->input->post('identifier') ?? '']);
        }

        echo json_encode($result);
    }

    public function doLoginForm() {

        try {

            $this->load->model('formvalidation_model');

            $PostData = $this->input->post();

            // Two-step flow: override UserName from session if a validated pending login exists
            $pendingUsername = $this->session->userdata('login_pending_username');
            $pendingUid      = (int)($this->session->userdata('login_pending_uid') ?: 0);
            $pendingExpires  = (int)($this->session->userdata('login_pending_expires') ?: 0);
            $isTwoStep       = ($pendingUsername && time() < $pendingExpires);
            if ($isTwoStep) {
                $this->session->unset_userdata('login_pending_username');
                $this->session->unset_userdata('login_pending_uid');
                $this->session->unset_userdata('login_pending_expires');
                $PostData['UserName'] = $pendingUsername;
            }

            // IP rate limiting — block after 10 failures within a 15-minute window
            $ipKey   = $this->redisservice->envKey('login-fail-ip-' . $this->input->ip_address());
            $ipCache = $this->redisservice->getCache($ipKey);
            $ipCount = (!$ipCache->Error && $ipCache->Value !== null) ? (int)$ipCache->Value : 0;
            if ($ipCount >= 10) {
                throw new Exception('Too many failed login attempts from your location. Please try again in 15 minutes.');
            }

            $ErrorInForm = $this->formvalidation_model->validateForm($PostData);
            if(empty($ErrorInForm)) {

                $this->load->model('user_model');
                $UserData = ($isTwoStep && $pendingUid > 0)
                    ? $this->user_model->getUserByUID($pendingUid)
                    : $this->user_model->getUserByEmailOrUsername($PostData['UserName']);

                if($UserData->Error === FALSE && count($UserData->Data) > 0 && sizeof($UserData->Data) == 1) {

                    if ($UserData->Data[0]->IsLocked == 1) {
                        $this->logLoginFailure($PostData['UserName'], 'Account locked');
                        throw new Exception('Account is locked. Contact administrator.');
                    }

                    $storedPassword  = $UserData->Data[0]->UserPassword;
                    $inputPassword   = $PostData['UserPassword'];
                    $isBcrypt        = (substr($storedPassword, 0, 4) === '$2y$');
                    $passwordMatches = $isBcrypt
                        ? password_verify($inputPassword, $storedPassword)
                        : ($inputPassword === base64_decode($storedPassword));

                    if ($passwordMatches) {

                        // Password expiry — force change after 180 days
                        $pwChangedOn = $UserData->Data[0]->PasswordChangedOn ?? null;
                        if (!empty($pwChangedOn) && ($UserData->Data[0]->AuthProvider ?? 'local') !== 'google') {
                            $daysSince = (time() - strtotime($pwChangedOn)) / 86400;
                            if ($daysSince > 180) {
                                $this->session->set_userdata('force_pw_uid',   (int)$UserData->Data[0]->UserUID);
                                $this->session->set_userdata('force_pw_exp',   time() + 900);
                                redirect('change-password/forced', 'refresh');
                                return;
                            }
                        }

                        // Lazy bcrypt migration — upgrade base64 hash on first successful login
                        if (!$isBcrypt) {
                            $this->load->model('dbwrite_model');
                            $this->dbwrite_model->updateData('Users', 'UserTbl',
                                ['Password' => password_hash($inputPassword, PASSWORD_BCRYPT)],
                                ['UserUID'  => $UserData->Data[0]->UserUID]
                            );
                        }

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
                            $orgToken   = $newPayload->JWTData['Org']['OrgToken'] ?? '';

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
                                    $this->redisservice->envKey('UserActiveSession_' . $UserData->Data[0]->UserUID),
                                    $sessionToken,
                                    (int) getenv('LOGIN_EXPIRE_SECS')
                                );

                                $loginExpiry = (int) getenv('LOGIN_EXPIRE_SECS');
                                $userUID     = $UserData->Data[0]->UserUID;
                                $this->redisservice->setUserCache('menus',       $userUID, $newPayload->JWTData['UserMainModule'] ?? [], $loginExpiry, $orgToken);
                                $this->redisservice->setUserCache('submenus',    $userUID, $newPayload->JWTData['UserSubModule']  ?? [], $loginExpiry, $orgToken);
                                $this->redisservice->setUserCache('modules',     $userUID, $newPayload->JWTData['ModuleInfo']     ?? [], $loginExpiry, $orgToken);
                                $this->redisservice->setUserCache('permissions', $userUID, $newPayload->JWTData['Permissions']    ?? [], $loginExpiry, $orgToken);
                                $this->redisservice->setUserCache('userinfo',    $userUID, $UserData->Data[0],                         $loginExpiry, $orgToken);

                                // Clear IP failure counter on successful login
                                $this->redisservice->deleteCache($ipKey);

                                // Redirect based on subscription state
                                $intendedUrl = $this->session->userdata('intended_url');
                                $this->session->unset_userdata('intended_url');
                                if ($subscriptionCheck->status === 'PendingPayment') {
                                    redirect('subscribe', 'refresh');
                                } elseif (!empty($intendedUrl)) {
                                    redirect($intendedUrl, 'refresh');
                                } else {
                                    redirect('dashboard', 'refresh');
                                }

                            } else {
                                $this->session->set_flashdata('danger', 'Oops! '.$JwtReturnData->Message);  
                            }
                            
                        }

                    } else {
                        $this->logLoginFailure($PostData['UserName'], 'Invalid credentials');
                        $this->redisservice->setCache($ipKey, $ipCount + 1, 900);

                        $this->load->model('login_model');
                        $failedAttempts = $this->login_model->getFailedAttempts($PostData['UserName']);
                        if ($failedAttempts >= 5) {

                            $this->load->model('dbwrite_model');
                            $this->dbwrite_model->updateData('Users', 'UserTbl', ['IsLocked' => 1], array('UserName' => $PostData['UserName']));

                            $this->logLoginFailure($PostData['UserName'], 'Account locked - too many attempts');
                        }

                        $this->session->set_flashdata('danger', 'Oops! Invalid username or password.');
                    }

                } else {
                    $this->redisservice->setCache($ipKey, $ipCount + 1, 900);
                    $this->session->set_flashdata('danger', 'Oops! Invalid username or password.');
                }

            } else {
                $this->session->set_flashdata('danger', $ErrorInForm);
            }

        } catch (Exception $e) {
            notifyError('Login::doLoginForm', $e);
            $this->session->set_flashdata('danger', $e->getMessage());
        }

        redirect('login', 'refresh');

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
            notifyError('Login::logLoginSuccess', $e);
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
            notifyError('Login::logLoginFailure', $e);
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
            notifyError('Login::sendResetLink', $e);
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

            if (strlen($password) < 8) {
                $this->session->set_flashdata('danger', 'Password must be at least 8 characters.');
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

            if (!empty($tokenInfo->IsLocked)) {
                $this->session->set_flashdata('danger', 'Your account is locked. Please contact your administrator.');
                redirect('forgot-password', 'refresh');
                return;
            }

            $this->load->model('user_model');
            $userData    = $this->user_model->getUserByUID((int)$tokenInfo->UserUID);
            $currentHash = (!$userData->Error && !empty($userData->Data)) ? $userData->Data[0]->UserPassword : '';

            if ($this->_isPasswordReused((int)$tokenInfo->UserUID, $password)) {
                $this->session->set_flashdata('danger', 'You cannot reuse one of your last 3 passwords.');
                redirect('reset-password/' . $token, 'refresh');
                return;
            }

            $this->load->model('dbwrite_model');

            $now = date('Y-m-d H:i:s');
            $this->dbwrite_model->updateData('Users', 'UserTbl',
                ['Password' => password_hash($password, PASSWORD_BCRYPT), 'PasswordChangedOn' => $now],
                ['UserUID'  => $tokenInfo->UserUID]
            );

            $this->_recordPasswordHistory((int)$tokenInfo->UserUID, $currentHash);

            $this->dbwrite_model->updateData('Users', 'PasswordResetTbl',
                ['IsUsed' => 1],
                ['Token'  => $token]
            );

            // Kill any active session so the old password can no longer be used
            $this->redisservice->deleteCache($this->redisservice->envKey('UserActiveSession_' . $tokenInfo->UserUID));
            $this->dbwrite_model->updateData('Users', 'UserTbl',
                ['CurrentSessionToken' => null],
                ['UserUID' => $tokenInfo->UserUID]
            );

            $this->_sendPasswordChangedEmail($tokenInfo);

            $this->session->set_flashdata('success', 'Password updated successfully. You can now sign in.');
            redirect('login', 'refresh');

        } catch (Exception $e) {
            notifyError('Login::doForgotReset', $e);
            $this->session->set_flashdata('danger', 'Something went wrong. Please try again.');
            if (!empty($token)) {
                redirect('reset-password/' . $token, 'refresh');
            } else {
                redirect('forgot-password', 'refresh');
            }
        }
    }

    /* ── Password history helpers ─────────────────────────────────── */

    /**
     * @param int    $uid
     * @param string $newPw plain-text candidate password
     * @return bool true if newPw matches any of the last 3 stored hashes for uid
     */
    private function _isPasswordReused(int $uid, string $newPw): bool {
        try {
            $db = $this->load->database('ReadDB', TRUE);
            $db->db_debug = FALSE;
            $db->select('Password');
            $db->from('Users.PasswordHistoryTbl');
            $db->where('UserUID', $uid);
            $db->order_by('CreatedOn', 'DESC');
            $db->limit(3);
            $query = $db->get();
            if (!$query) return false;
            foreach ($query->result() as $row) {
                if (password_verify($newPw, $row->Password)) return true;
            }
        } catch (Throwable $t) { /* table not yet migrated — skip silently */ }
        return false;
    }

    /**
     * Stores oldHash in PasswordHistoryTbl and prunes to the 5 most recent per user.
     * @param int    $uid
     * @param string $oldHash bcrypt hash that was just replaced
     * @return void
     */
    private function _recordPasswordHistory(int $uid, string $oldHash): void {
        if (empty($oldHash)) return;
        try {
            $db = $this->load->database('WriteDB', TRUE);
            $db->db_debug = FALSE;
            $db->insert('Users.PasswordHistoryTbl', [
                'UserUID'  => $uid,
                'Password' => $oldHash,
            ]);
            $db->query(
                "DELETE FROM Users.PasswordHistoryTbl
                  WHERE UserUID = ?
                    AND HistoryUID NOT IN (
                        SELECT h FROM (
                            SELECT HistoryUID AS h FROM Users.PasswordHistoryTbl
                             WHERE UserUID = ?
                             ORDER BY CreatedOn DESC
                             LIMIT 5
                        ) tmp
                    )",
                [$uid, $uid]
            );
        } catch (Throwable $t) {
            error_log('[PasswordHistory] ' . $t->getMessage());
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
            notifyError('Login::_sendResetEmail', $e);
            error_log('[ForgotPassword] Email send failed: ' . $e->getMessage());
        }
    }

    private function _sendPasswordChangedEmail($tokenInfo): void {
        try {
            $firstName = htmlspecialchars($tokenInfo->FirstName ?? 'User', ENT_QUOTES, 'UTF-8');
            $email     = $tokenInfo->EmailAddress;
            $changedAt = date('d M Y, h:i A');
            $loginUrl  = base_url('login');

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
            notifyError('Login::_sendPasswordChangedEmail', $e);
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

    // ── Forced password change (expired after 180 days) ──────────────────────

    public function forcedPasswordChange(): void {
        $this->load->helper('auth');
        if (is_authenticated()) { redirect('dashboard', 'refresh'); return; }

        $uid = (int)($this->session->userdata('force_pw_uid') ?? 0);
        $exp = (int)($this->session->userdata('force_pw_exp') ?? 0);

        if (!$uid || time() > $exp) {
            redirect('login', 'refresh');
            return;
        }

        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $newPw  = (string)$this->input->post('NewPassword');
            $confPw = (string)$this->input->post('ConfirmPassword');

            if (strlen($newPw) < 8) {
                $this->session->set_flashdata('danger', 'Password must be at least 8 characters.');
                redirect('change-password/forced', 'refresh');
                return;
            }
            if ($newPw !== $confPw) {
                $this->session->set_flashdata('danger', 'Passwords do not match.');
                redirect('change-password/forced', 'refresh');
                return;
            }

            $this->load->model('user_model');
            $userData    = $this->user_model->getUserByUID($uid);
            $currentHash = (!$userData->Error && !empty($userData->Data)) ? $userData->Data[0]->UserPassword : '';

            if ($this->_isPasswordReused($uid, $newPw)) {
                $this->session->set_flashdata('danger', 'You cannot reuse one of your last 3 passwords.');
                redirect('change-password/forced', 'refresh');
                return;
            }

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->updateData('Users', 'UserTbl',
                ['Password' => password_hash($newPw, PASSWORD_BCRYPT), 'PasswordChangedOn' => date('Y-m-d H:i:s')],
                ['UserUID'  => $uid]
            );

            $this->_recordPasswordHistory($uid, $currentHash);

            $this->session->unset_userdata('force_pw_uid');
            $this->session->unset_userdata('force_pw_exp');

            $this->session->set_flashdata('success', 'Password updated. Please sign in with your new password.');
            redirect('login', 'refresh');
            return;
        }

        $this->PageData['pageTitle'] = 'Update Your Password';
        $this->load->view('login/header', ['pageTitle' => 'Change Password']);
        $this->load->view('login/forced_pw_change', $this->PageData);
        $this->load->view('login/footer');
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
                $UserData    = $this->user_model->getUserByUserInfo(array('User.UserUID' => $PostData['UserUID']));
                $stored      = $UserData->Data[0]->UserPassword;
                $oldMatches  = (substr($stored, 0, 4) === '$2y$')
                    ? password_verify($PostData['OldPassword'], $stored)
                    : ($PostData['OldPassword'] === base64_decode($stored));

                if (!$oldMatches) {

                    throw new Exception('Old Password do not match. Please try again.!', 200);

                } elseif (strlen($PostData['ConfirmPassword'] ?? '') < 8) {

                    throw new Exception('New password must be at least 8 characters.', 200);

                } elseif ($this->_isPasswordReused((int)$PostData['UserUID'], (string)$PostData['ConfirmPassword'])) {

                    throw new Exception('You cannot reuse one of your last 3 passwords.', 200);

                } else {

                    $this->load->model('dbwrite_model');
                    $userUID        = (int)$PostData['UserUID'];
                    $UpdateDataResp = $this->dbwrite_model->updateData('Users', 'UserTbl', ['Password' => password_hash($PostData['ConfirmPassword'], PASSWORD_BCRYPT), 'PasswordChangedOn' => date('Y-m-d H:i:s')], ['UserUID' => $userUID]);

                    if($UpdateDataResp->Error === FALSE) {

                        $this->_recordPasswordHistory($userUID, $stored);

                        // Kill the active session — user must re-login with the new password
                        $this->redisservice->deleteCache($this->redisservice->envKey('UserActiveSession_' . $userUID));
                        $this->dbwrite_model->updateData('Users', 'UserTbl', ['CurrentSessionToken' => null], ['UserUID' => $userUID]);

                        // Also evict the JWT session blob from Redis if the cookie is readable
                        try {
                            $jwtEncoded = get_cookie(getenv('JWT_COOKIE_NAME'));
                            if ($jwtEncoded) {
                                $jwtDecoded = JWT::decode($jwtEncoded, new Key(getenv('JWT_KEY'), 'HS256'));
                                if (isset($jwtDecoded->key)) {
                                    $this->redisservice->deleteCache($jwtDecoded->key);
                                }
                            }
                        } catch (Throwable $e) { /* JWT expired or invalid — nothing to evict */ }

                        $this->EndReturnData->Error        = FALSE;
                        $this->EndReturnData->Message      = 'Updated Successfully';
                        $this->EndReturnData->RequireReLogin = TRUE;
                    } else {
                        $this->EndReturnData->Error   = TRUE;
                        $this->EndReturnData->Message = 'Error occured';
                    }

                }

            } else {
                $this->EndReturnData->Error = TRUE;
                $this->EndReturnData->Message = $ErrorInForm;
            }

        } catch (Exception $e) {
            notifyError('Login::resetPassword', $e);
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		$this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;

    }

    /**
     * Clears the server-side pending-login session created by Step 1.
     * Called fire-and-forget when the user clicks "Not you?" in the two-step UI.
     */
    public function clearPendingSession(): void {
        $this->session->unset_userdata('login_pending_username');
        $this->session->unset_userdata('login_pending_uid');
        $this->session->unset_userdata('login_pending_expires');
        $this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output('{"ok":true}')
            ->_display();
        exit;
    }

    /**
     * Step 1 of the two-step login flow.
     * Validates username existence, lock status, and subscription.
     * On success stores a 5-minute session token and returns the user's display name.
     */
    public function validateUsername(): void {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->helper('auth');
            if (is_authenticated()) {
                throw new Exception('Already authenticated.');
            }

            $username = trim($this->input->post('UserName') ?: '');
            if (!$username) {
                throw new Exception('Please enter your username or email.');
            }

            // Lean single-table query — no Roles/Org/Branch joins, no subscription check
            $this->load->model('user_model');
            $user = $this->user_model->getUserForStepOne($username);

            if (!$user) {
                throw new Exception('User account not found.');
            }

            if ($user->IsLocked == 1) {
                throw new Exception('Account is locked. Contact your administrator.');
            }

            if (($user->AuthProvider ?? 'local') === 'google') {
                throw new Exception('This account was created with Google. Please use Continue with Google to sign in.');
            }

            if (empty($user->Password)) {
                throw new Exception('No password has been set for this account. Please use Forgot Password to set one.');
            }

            // Check user-level portal access expiry (NULL = no expiry, inherits org subscription)
            if (!empty($user->LoginExpiryDateTime) && strtotime($user->LoginExpiryDateTime) < time()) {
                $expiryDate = date('d M Y', strtotime($user->LoginExpiryDateTime));
                throw new Exception('Your portal access expired on ' . $expiryDate . '. Please contact your administrator.');
            }

            // Check org email verification before allowing Step 2
            if (!(int)(bool)$user->IsEmailVerified) {
                $this->EndReturnData->Error                  = true;
                $this->EndReturnData->NeedsEmailVerification = true;
                $this->EndReturnData->OrgEmail               = strtolower(trim($user->OrgEmail ?? ''));
                $this->EndReturnData->Message                = 'Your organisation email address has not been verified. Please check your inbox for the verification link.';
                $this->output->set_status_header(200)
                    ->set_content_type('application/json', 'utf-8')
                    ->set_output(json_encode($this->EndReturnData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
                    ->_display();
                exit;
            }

            // Check subscription before allowing Step 2
            $this->load->library('subscription');
            $subscriptionCheck = $this->subscription->checkSubscription($user->UserUID);
            if (!$subscriptionCheck->isValid) {
                // Generate a short-lived renewal token so the renew page can identify the org
                $rToken = bin2hex(random_bytes(16));
                $this->redisservice->setCache($this->redisservice->envKey('rnt_' . $rToken), ['orgUID' => (int)$user->OrgUID, 'createdAt' => time()], 1800);

                // Fetch plan + end date for the modal display
                $subDb     = $this->load->database('ReadDB', TRUE);
                $subDb->db_debug = FALSE;
                $subDetail = $subDb->select('OS.EndDate, COALESCE(SP.PlanName, \'Trial\') AS PlanName, O.Name AS OrgName')
                    ->from('Billing.OrgSubscriptionTbl AS OS')
                    ->join('Organisation.OrganisationTbl AS O',  'O.OrgUID  = OS.OrgUID',             'left')
                    ->join('Billing.SectorPlanTbl AS SPT',       'SPT.SectorPlanUID = OS.SectorPlanUID', 'left')
                    ->join('Billing.SubscriptionPlansTbl AS SP', 'SP.PlanUID = SPT.PlanUID',             'left')
                    ->where('OS.OrgUID', (int)$user->OrgUID)
                    ->where_not_in('OS.Status', ['Cancelled'])
                    ->order_by('OS.StartDate', 'DESC')
                    ->limit(1)
                    ->get()->row();

                $this->EndReturnData->SubscriptionExpired = true;
                $this->EndReturnData->accessRef           = $rToken;
                $this->EndReturnData->subPlanName         = $subDetail ? ($subDetail->PlanName ?: 'Trial') : '';
                $this->EndReturnData->subEndDate          = $subDetail ? ($subDetail->EndDate  ?: '')      : '';
                $this->EndReturnData->subOrgName          = $subDetail ? ($subDetail->OrgName  ?: '')      : '';

                Telegramnotifier::alert('[LOGIN-STEP1] validateUsername BLOCKED at subscription check', [
                    'UserUID'   => $user->UserUID,
                    'UserName'  => $username,
                    'isValid'   => 'FALSE',
                    'status'    => $subscriptionCheck->status,
                    'message'   => $subscriptionCheck->message,
                ]);
                throw new Exception($subscriptionCheck->message);
            }

            // Store confirmed identity for Step 2 — expires in 5 minutes
            $this->session->set_userdata('login_pending_username', $user->UserName);
            $this->session->set_userdata('login_pending_uid',      (int)$user->UserUID);
            $this->session->set_userdata('login_pending_expires',  time() + 300);

            $displayName = trim(($user->FirstName ?? '') . ' ' . ($user->LastName ?? ''));
            if (!$displayName) $displayName = $user->UserName;

            $imageUrl = !empty($user->Image) ? resolveCdnUrl($user->Image) : '';

            $this->EndReturnData->Error       = false;
            $this->EndReturnData->Username    = $user->UserName;
            $this->EndReturnData->DisplayName = $displayName;
            $this->EndReturnData->ImageUrl    = $imageUrl;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;
    }

    public function logout() {

        $JwtEncoded = get_cookie(getenv('JWT_COOKIE_NAME'));
        if(isset($JwtEncoded)) {

            try {
                $JwtData = JWT::decode($JwtEncoded, new Key(getenv('JWT_KEY'), 'HS256'));
            } catch (Exception $e) {
                notifyError('Login::logout', $e);
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
                        $this->redisservice->deleteCache($this->redisservice->envKey('UserActiveSession_' . $userUID));
                        $this->load->model('dbwrite_model');
                        $this->dbwrite_model->updateData('Users', 'UserTbl', ['CurrentSessionToken' => null], ['UserUID' => $userUID]);
                    }

                    // Flush all org/user caches — Org object exists in new JWT structure;
                    // fall back to User for old cached JWTs
                    $logoutOrgToken = $getAuditInfo->Value->Org->OrgToken ?? ($getAuditInfo->Value->User->OrgToken ?? '');
                    if ($userUID) {
                        $this->redisservice->deleteAllUserCache($userUID, $logoutOrgToken);
                    }

                    // Delete role-level menu caches for this user's role
                    $logoutRoleUID = $getAuditInfo->Value->User->RoleUID ?? null;
                    if ($logoutRoleUID) {
                        $this->redisservice->deleteCache($this->redisservice->orgKey('role-menus-'    . $logoutRoleUID, $logoutOrgToken));
                        $this->redisservice->deleteCache($this->redisservice->orgKey('role-submenus-' . $logoutRoleUID, $logoutOrgToken));
                    }

                    // Delete global caches so they are rebuilt fresh on next login
                    $this->redisservice->deleteCache($this->redisservice->globalKey('global-modules'));
                    $this->redisservice->deleteCache($this->redisservice->globalKey('global-attach-cfg'));

                    $orgUID = $getAuditInfo->Value->Org->OrgUID ?? ($getAuditInfo->Value->User->OrgUID ?? null);
                    if ($orgUID) {
                        $this->redisservice->deleteCache($this->redisservice->orgKey('org-users', $logoutOrgToken));
                    }

                }

				$this->redisservice->deleteCache($JwtData->key);

			}

			delete_cookie(getenv('JWT_COOKIE_NAME'));

		}

		redirect('login', 'refresh');

    }

    private function _getDefaultOrgLogo() {
        try {
            $this->load->model('organisation_model');
            return $this->organisation_model->getDefaultOrgLogo();
        } catch (Exception $e) {
            notifyError('Login::_getDefaultOrgLogo', $e);
            return '';
        }
    }

}