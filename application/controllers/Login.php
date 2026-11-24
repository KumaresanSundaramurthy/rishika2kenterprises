<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Login extends CI_Controller {

    public $PageData = array();

    public function __construct() {
        parent::__construct();
        $this->load->library('upstashservice');
    }

    public function index() {
        redirect('login', 'refresh');
    }

    public function login(): void {
        $this->load->helper('auth');
        if (is_authenticated()) {
            redirect('dashboard', 'refresh');
            return;
        }
        $this->load->view('login/view', [
            'OrgLogo'        => $this->_getDefaultOrgLogo(),
            'TwoStepEnabled' => true,
            'CdnBase'        => rtrim(getenv('CFLARE_R2_CDN'), '/'),
        ]);
    }

    public function verifyEmail(string $token = ''): void {
        $token   = trim($token ?: $this->input->get('token'));
        $status  = 'invalid';
        $message = 'This verification link is invalid. It may have already been used or the link is incorrect.';

        if (!empty($token)) {
            $httpCode = null;
            $resp     = services_post('api/auth/email/verify', ['token' => $token], [], 10, false, $httpCode);
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

    public function doLoginForm(): void {

        try {

            $PostData = $this->input->post();
            if (empty(trim($PostData['UserName'] ?? ''))) {
                $this->session->set_flashdata('danger', 'Username is required.');
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
            ], 10, false, $httpCode, $rawBody);

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

            /* 180-day password expiry — store changeToken in session; NestJS validates it on submit. */
            if ($nextStep === 'FORCE_PASSWORD_CHANGE' && !empty($resp->changeToken)) {
                $this->session->set_userdata('force_pw_token', $resp->changeToken);
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

    // OAuth cross-domain handoff: NestJS passes the token via URL; we set the cookie here
    public function accept(): void {
        $token = (string)($this->input->get('t', true) ?: '');
        $exp   = max(1, (int)($this->input->get('exp', true) ?: 43200));
        $next  = (string)($this->input->get('next', true) ?: 'dashboard');

        if (empty($token)) {
            redirect('login');
            return;
        }

        if (preg_match('/^https?:\/\//i', $next)) {
            $next = 'dashboard';
        }

        $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        setcookie((string)(getenv('JWT_COOKIE_NAME') ?: 'r2k-enterprises-portal'), $token, [
            'expires'  => time() + $exp,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => $isHttps,
        ]);
        redirect($next, 'refresh');
    }

    // ── Forgot / Reset password (public, unauthenticated) ────────────────────

    public function forgotPassword() {
        $this->load->helper('auth');
        if (is_authenticated()) { redirect('dashboard', 'refresh'); return; }
        $this->load->view('login/forgot_password', ['OrgLogo' => $this->_getDefaultOrgLogo()]);
    }

    public function showResetForm($token = null): void {
        $this->load->helper('auth');
        if (is_authenticated()) { redirect('dashboard', 'refresh'); return; }
        if (empty($token)) { redirect('forgot-password', 'refresh'); return; }

        $httpCode  = null;
        $tokenInfo = services_get('api/auth/password/reset/' . urlencode($token), services_server_headers(), 10, false, $httpCode);

        if (!$tokenInfo) {
            /* 410 = expired token; anything else (404, network error) = invalid */
            $this->session->set_flashdata('token_error', $httpCode === 410 ? 'expired' : 'invalid');
            redirect('forgot-password', 'refresh');
            return;
        }

        $this->PageData['token']        = $token;
        $this->PageData['remainingSecs'] = max(0, (int)($tokenInfo->remainingSeconds ?? 0));
        $this->PageData['OrgLogo']       = $this->_getDefaultOrgLogo();
        $this->load->view('login/reset_password', $this->PageData);
    }

    public function doForgotReset(): void {
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

            /* NestJS handles token validation, password history check, DB update,
               session revocation and the password-changed email. */
            $httpCode = null;
            $resp     = services_post('api/auth/password/reset', [
                'token'           => $token,
                'newPassword'     => $password,
                'confirmPassword' => $confirm,
            ], services_server_headers(), 10, false, $httpCode);

            if ($httpCode !== 200) {
                $msg = !empty($resp->message)
                    ? (is_array($resp->message) ? implode(', ', $resp->message) : $resp->message)
                    : 'Something went wrong. Please try again.';
                /* 410 = expired token — send back to forgot-password to request a fresh link */
                if ($httpCode === 410) {
                    $this->session->set_flashdata('token_error', 'expired');
                    redirect('forgot-password', 'refresh');
                    return;
                }
                $this->session->set_flashdata('danger', $msg);
                redirect('reset-password/' . $token, 'refresh');
                return;
            }

            $this->session->set_flashdata('success', 'Password updated successfully. You can now sign in.');
            redirect('login', 'refresh');

        } catch (Exception $e) {
            notifyError('Login::doForgotReset', $e);
            $this->session->set_flashdata('danger', 'Something went wrong. Please try again.');
            redirect(!empty($token) ? 'reset-password/' . $token : 'forgot-password', 'refresh');
        }
    }

    // ── Forced password change (expired after 180 days) ──────────────────────

    public function forcedPasswordChange(): void {
        $this->load->helper('auth');
        if (is_authenticated()) { redirect('dashboard', 'refresh'); return; }

        $changeToken = (string)($this->session->userdata('force_pw_token') ?? '');
        if (empty($changeToken)) {
            redirect('login', 'refresh');
            return;
        }

        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $httpCode = null;
            $resp = services_post('api/auth/password/forced-change', [
                'changeToken'     => $changeToken,
                'newPassword'     => (string)$this->input->post('NewPassword'),
                'confirmPassword' => (string)$this->input->post('ConfirmPassword'),
            ], services_server_headers(), 10, false, $httpCode);

            if ($httpCode !== 200) {
                $msg = !empty($resp->message)
                    ? (is_array($resp->message) ? implode(', ', $resp->message) : $resp->message)
                    : 'Something went wrong. Please try again.';
                $this->session->set_flashdata('danger', $msg);
                redirect('change-password/forced', 'refresh');
                return;
            }

            $this->session->unset_userdata('force_pw_token');
            $this->session->set_flashdata('success', 'Password updated. Please sign in with your new password.');
            redirect('login', 'refresh');
            return;
        }

        $this->PageData['pageTitle'] = 'Update Your Password';
        $this->load->view('login/header', ['pageTitle' => 'Change Password']);
        $this->load->view('login/forced_pw_change', $this->PageData);
        $this->load->view('login/footer');
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
        $jwtToken = get_cookie(getenv('JWT_COOKIE_NAME'));
        if (!empty($jwtToken)) {
            services_post('api/auth/logout', [], ['Authorization: Bearer ' . $jwtToken], 5);
        }
        delete_cookie(getenv('JWT_COOKIE_NAME'));
        delete_cookie(getenv('JWT_REFRESH_COOKIE_NAME') ?: 'r2k_refresh');
        redirect('login', 'refresh');
    }

    /* Checks Upstash first (TTL 1 h) to avoid a cold-start NestJS call on every page load. */
    private function _getDefaultOrgLogo(): string {
        try {
            $cached = $this->upstashservice->get('r2k-login-config');
            if ($cached !== null && !empty($cached['orgLogo'])) {
                return (string)$cached['orgLogo'];
            }
            $resp    = services_get('api/auth/login/config', [], 5);
            $orgLogo = $resp->orgLogo ?? '';
            if ($orgLogo) {
                $this->upstashservice->set('r2k-login-config', ['orgLogo' => $orgLogo], 3600);
            }
            return $orgLogo;
        } catch (Exception $e) {
            return '';
        }
    }

}