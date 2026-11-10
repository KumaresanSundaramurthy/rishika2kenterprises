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
        $config         = services_get('api/auth/login/config', [], 5);
        $this->load->view('login/view', [
            'OrgLogo'        => $config->orgLogo ?? '',
            'TwoStepEnabled' => !empty($config->twoStepLogin),
            'CdnBase'        => rtrim(getenv('CFLARE_R2_CDN'), '/'),
        ]);
    }

    public function verifyEmail(string $token = ''): void {
        $token   = trim($token ?: $this->input->get('token'));
        $status  = 'invalid';
        $message = 'This verification link is invalid. It may have already been used or the link is incorrect.';

        if (!empty($token)) {
            $httpCode = null;
            $resp     = services_post('api/auth/email/verify', ['token' => $token], [], 10, $httpCode);
            if ($resp && !empty($resp->status)) {
                $status  = $resp->status;
                $message = $resp->message ?? $message;
            }
        }

        $this->load->view('login/verify_email', [
            'status'  => $status,
            'message' => $message,
        ]);
    }

    public function resendVerificationEmail(): void {
        header('Content-Type: application/json');

        $result          = new stdClass();
        $result->Error   = false;
        $result->Message = 'If this account is registered and unverified, a new verification link has been sent. Please check your inbox.';

        try {
            $identifier = strtolower(trim($this->input->post('identifier') ?? ''));
            if (empty($identifier)) {
                throw new Exception('Please enter your username or email.');
            }

            $httpCode = null;
            $resp     = services_post('api/auth/email/resend-verification', ['identifier' => $identifier], [], 10, $httpCode);
            if ($resp && !empty($resp->message)) {
                $result->Message = $resp->message;
            }
        } catch (Exception $e) {
            $result->Error   = true;
            $result->Message = $e->getMessage();
            notifyError('Login::resendVerificationEmail', $e);
        }

        echo json_encode($result);
    }

    public function doLoginForm(): void {

        try {

            $this->load->model('formvalidation_model');
            $PostData    = $this->input->post();
            $ErrorInForm = $this->formvalidation_model->validateForm($PostData);
            if (!empty($ErrorInForm)) {
                $this->session->set_flashdata('danger', $ErrorInForm);
                redirect('login', 'refresh');
                return;
            }

            $httpCode = null;
            $rawBody  = null;
            $resp     = services_post('api/auth/login', [
                'identifier' => $PostData['UserName']     ?? '',
                'password'   => $PostData['UserPassword'] ?? '',
            ], [
                'X-Forwarded-For: ' . $this->input->ip_address(),
                'User-Agent: '      . $this->input->user_agent(),
            ], 10, $httpCode, $rawBody);

            if (!$rawBody) {
                throw new Exception('Authentication service is unavailable. Please try again.');
            }

            if ($httpCode !== 200) {
                $msg = !empty($resp->message)
                    ? (is_array($resp->message) ? implode(', ', $resp->message) : $resp->message)
                    : 'Invalid username or password.';
                $this->session->set_flashdata('danger', $msg);
                redirect('login', 'refresh');
                return;
            }

            $isHttps     = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
            $refreshName = getenv('JWT_REFRESH_COOKIE_NAME') ?: 'r2k_refresh';
            $sessionTtl  = (int)(getenv('LOGIN_EXPIRE_SECS') ?: 43200);

            /* JWT cookie — set from JSON body so a services/CI3 cookie-name mismatch never breaks login. */
            if (!empty($resp->token)) {
                setcookie(getenv('JWT_COOKIE_NAME'), $resp->token, [
                    'expires'  => time() + (int)(getenv('LOGIN_EXPIRE_SECS') ?: 43200),
                    'path'     => '/',
                    'httponly' => true,
                    'samesite' => 'Lax',
                    'secure'   => $isHttps,
                ]);
            }

            /* Refresh cookie — extract value from the services Set-Cookie headers. */
            $headerSection = explode("\r\n\r\n", $rawBody, 2)[0] ?? '';
            foreach (explode("\r\n", $headerSection) as $hdr) {
                if (stripos($hdr, 'Set-Cookie:') !== 0) continue;
                $parts = array_map('trim', explode(';', trim(substr($hdr, strlen('Set-Cookie:')))));
                if (empty($parts[0])) continue;
                $eqPos = strpos($parts[0], '=');
                if ($eqPos === false) continue;
                if (trim(substr($parts[0], 0, $eqPos)) !== $refreshName) continue;
                setcookie($refreshName, substr($parts[0], $eqPos + 1), [
                    'expires'  => time() + $sessionTtl,
                    'path'     => '/',
                    'httponly' => true,
                    'samesite' => 'Lax',
                    'secure'   => $isHttps,
                ]);
                break;
            }

            /* Trigger services to populate user-scoped menu/permission caches in Redis. */
            if (!empty($resp->token)) {
                services_post('api/auth/refresh', [], ['Authorization: Bearer ' . $resp->token], 10);
            }

            $nextStep = $resp->nextStep ?? 'DASHBOARD';

            /* 180-day password expiry — decode changeToken to extract UID for the forced-change page. */
            if ($nextStep === 'FORCE_PASSWORD_CHANGE' && !empty($resp->changeToken)) {
                try {
                    $changeClaims = JWT::decode($resp->changeToken, new Key(getenv('JWT_KEY'), 'HS256'));
                    $this->session->set_userdata('force_pw_uid', (int)($changeClaims->uid ?? 0));
                    $this->session->set_userdata('force_pw_exp', time() + 900);
                } catch (Exception $e) {
                    $this->session->set_flashdata('danger', 'Session error. Please sign in again.');
                    redirect('login', 'refresh');
                    return;
                }
                redirect('change-password/forced', 'refresh');
                return;
            }

            $intendedUrl   = $this->session->userdata('intended_url') ?? '';
            $this->session->unset_userdata('intended_url');
            $_paymentPages = ['subscribe', 'signuppayment'];

            if (in_array($nextStep, ['SUBSCRIBE', 'ONBOARDING'])) {
                redirect('subscribe', 'refresh');
            } elseif ($nextStep === 'RENEW') {
                redirect('subscription/renew?sid=' . ($resp->renewToken ?? ''), 'refresh');
            } elseif (!empty($intendedUrl) && !in_array(explode('/', $intendedUrl)[0], $_paymentPages)) {
                redirect($intendedUrl, 'refresh');
            } else {
                redirect('dashboard', 'refresh');
            }

        } catch (Exception $e) {
            notifyError('Login::doLoginForm', $e);
            $this->session->set_flashdata('danger', $e->getMessage());
            redirect('login', 'refresh');
        }

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

            $this->load->model('dbwrite_model'); $this->load->model('dbwrite_ext_model');
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

            $this->load->model('dbwrite_model'); $this->load->model('dbwrite_ext_model');

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
            $this->load->model('users_model');
            $hashes = $this->users_model->getRecentPasswordHashes($uid, 3);
            foreach ($hashes as $row) {
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
            $this->load->model('dbwrite_model'); $this->load->model('dbwrite_ext_model');
            $this->dbwrite_model->insertData('Users', 'PasswordHistoryTbl', [
                'UserUID'  => $uid,
                'Password' => $oldHash,
            ]);
            $this->dbwrite_ext_model->prunePasswordHistory($uid);
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

            $this->load->model('dbwrite_model'); $this->load->model('dbwrite_ext_model');
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

                    $this->load->model('dbwrite_model'); $this->load->model('dbwrite_ext_model');
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

    public function logout(): void {

        /* Proxy to services — handles Redis cleanup, cache clearing and audit log. */
        $jwtToken = get_cookie(getenv('JWT_COOKIE_NAME'));
        if (!empty($jwtToken)) {
            $servicesBase = rtrim(getenv('SERVICES_BASE_URL') ?: '', '/');
            if ($servicesBase) {
                /* Send JWT as Authorization Bearer (services reads this before the cookie,
                   so a cookie-name mismatch between CI3 and services never prevents logout). */
                $refreshCookieName = getenv('JWT_REFRESH_COOKIE_NAME') ?: 'r2k_refresh';
                $refreshToken      = get_cookie($refreshCookieName);
                $headers = [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $jwtToken,
                ];
                $ch = curl_init($servicesBase . '/api/auth/logout');
                curl_setopt_array($ch, [
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => '',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => 5,
                    CURLOPT_HTTPHEADER     => $headers,
                    CURLOPT_COOKIE         => $refreshToken ? $refreshCookieName . '=' . urlencode($refreshToken) : '',
                ]);
                curl_exec($ch);
                curl_close($ch);
            }
        }

        delete_cookie(getenv('JWT_COOKIE_NAME'));
        delete_cookie(getenv('JWT_REFRESH_COOKIE_NAME') ?: 'r2k_refresh');
        redirect('login', 'refresh');

    }

    private function _getDefaultOrgLogo(): string {
        $resp = services_get('api/auth/login/config', [], 5);
        return $resp->orgLogo ?? '';
    }

}