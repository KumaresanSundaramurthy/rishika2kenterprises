<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Oauth — Social login controller (Google & Facebook OAuth 2.0).
 *
 * Required .env variables:
 *   GOOGLE_CLIENT_ID      — from Google Cloud Console → Credentials → OAuth 2.0 Client ID
 *   GOOGLE_CLIENT_SECRET  — same credentials page
 *   FACEBOOK_APP_ID       — from Meta Developer Portal → App Dashboard
 *   FACEBOOK_APP_SECRET   — same app dashboard
 *
 * Google: add  <base_url>/auth/google/callback  as an Authorised redirect URI
 * Facebook: add  <base_url>/auth/facebook/callback  as a Valid OAuth Redirect URI
 *
 * No new composer packages needed — uses curl (already used by Brevo API).
 * No new DB tables needed — user must already exist; OAuth just verifies by email.
 */
class Oauth extends CI_Controller {

    public function __construct() {
        parent::__construct();
    }

    /* ===================================================================
     * Google — redirect
     * =================================================================== */
    public function googleRedirect() {
        $this->load->helper('auth');
        if (is_authenticated()) { redirect('dashboard', 'refresh'); return; }

        if (empty(getenv('GOOGLE_CLIENT_ID'))) {
            $this->session->set_flashdata('danger', 'Google sign-in is not configured.');
            redirect('portal', 'refresh');
            return;
        }

        $state = bin2hex(random_bytes(16));
        $this->session->set_userdata('oauth_state', $state);

        $params = http_build_query([
            'client_id'     => getenv('GOOGLE_CLIENT_ID'),
            'redirect_uri'  => base_url('auth/google/callback'),
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'state'         => $state,
            'access_type'   => 'online',
            'prompt'        => 'select_account',
        ]);

        redirect('https://accounts.google.com/o/oauth2/v2/auth?' . $params, 'location');
    }

    /* ===================================================================
     * Google — callback
     * =================================================================== */
    public function googleCallback() {
        $this->load->helper('auth');
        if (is_authenticated()) { redirect('dashboard', 'refresh'); return; }

        // Google sends ?error=access_denied when user cancels
        $googleError = $this->input->get('error');
        if (!empty($googleError)) {
            $msg = ($googleError === 'access_denied')
                ? 'Google sign-in was cancelled.'
                : 'Google returned an error: ' . htmlspecialchars($googleError, ENT_QUOTES);
            $this->session->set_flashdata('danger', $msg);
            redirect('portal', 'refresh');
            return;
        }

        $code       = $this->input->get('code');
        $state      = $this->input->get('state');
        $savedState = $this->session->userdata('oauth_state');
        $this->session->unset_userdata('oauth_state');

        if (empty($code)) {
            $this->session->set_flashdata('danger', 'Google did not return an authorisation code. Please try again.');
            redirect('portal', 'refresh');
            return;
        }

        if (empty($state) || $state !== $savedState) {
            $this->session->set_flashdata('danger', 'Security check failed (state mismatch). Please try again.');
            redirect('portal', 'refresh');
            return;
        }

        try {
            $tokenData = $this->_googleExchangeCode($code);

            if (!empty($tokenData['error'])) {
                throw new Exception(
                    'Google token error: ' . $tokenData['error'] .
                    (!empty($tokenData['error_description']) ? ' — ' . $tokenData['error_description'] : '') .
                    ' | redirect_uri used: ' . base_url('auth/google/callback')
                );
            }

            if (empty($tokenData['access_token'])) {
                throw new Exception('Google did not return an access token. redirect_uri used: ' . base_url('auth/google/callback'));
            }

            $googleUser = $this->_googleGetUser($tokenData['access_token']);

            if (!empty($googleUser['error'])) {
                throw new Exception('Google userinfo error: ' . json_encode($googleUser['error']));
            }

            if (empty($googleUser['email'])) {
                throw new Exception('Google did not return an email address. Response: ' . json_encode($googleUser));
            }

            if (empty($googleUser['email_verified'])) {
                throw new Exception('Your Google email address is not verified. Please verify it and try again.');
            }

            $this->_completeOAuthLogin($googleUser['email'], 'GOOGLE');

        } catch (Exception $e) {
            $this->session->set_flashdata('danger', $e->getMessage());
            redirect('portal', 'refresh');
        }
    }

    /* ===================================================================
     * Facebook — redirect
     * =================================================================== */
    public function facebookRedirect() {
        $this->load->helper('auth');
        if (is_authenticated()) { redirect('dashboard', 'refresh'); return; }

        if (empty(getenv('FACEBOOK_APP_ID'))) {
            $this->session->set_flashdata('danger', 'Facebook sign-in is not configured.');
            redirect('portal', 'refresh');
            return;
        }

        $state = bin2hex(random_bytes(16));
        $this->session->set_userdata('oauth_state', $state);

        $params = http_build_query([
            'client_id'     => getenv('FACEBOOK_APP_ID'),
            'redirect_uri'  => base_url('auth/facebook/callback'),
            'scope'         => 'email',
            'state'         => $state,
            'response_type' => 'code',
        ]);

        redirect('https://www.facebook.com/v18.0/dialog/oauth?' . $params, 'location');
    }

    /* ===================================================================
     * Facebook — callback
     * =================================================================== */
    public function facebookCallback() {
        $this->load->helper('auth');
        if (is_authenticated()) { redirect('dashboard', 'refresh'); return; }

        $code       = $this->input->get('code');
        $state      = $this->input->get('state');
        $savedState = $this->session->userdata('oauth_state');
        $this->session->unset_userdata('oauth_state');

        if (empty($code) || empty($state) || $state !== $savedState) {
            $this->session->set_flashdata('danger', 'Facebook sign-in was cancelled or failed. Please try again.');
            redirect('portal', 'refresh');
            return;
        }

        try {
            $tokenData = $this->_facebookExchangeCode($code);
            if (empty($tokenData['access_token'])) {
                throw new Exception('Unable to authenticate with Facebook. Please try again.');
            }

            $fbUser = $this->_facebookGetUser($tokenData['access_token']);
            if (empty($fbUser['email'])) {
                throw new Exception('Could not retrieve your email from Facebook. Make sure your Facebook account has a verified email address.');
            }

            $this->_completeOAuthLogin($fbUser['email'], 'FACEBOOK');

        } catch (Exception $e) {
            $this->session->set_flashdata('danger', $e->getMessage());
            redirect('portal', 'refresh');
        }
    }

    /* ===================================================================
     * Shared — complete login after OAuth email verified
     * =================================================================== */
    private function _completeOAuthLogin($email, $provider) {
        $this->load->model('user_model');
        $userData = $this->user_model->getUserByEmailOrUsername($email);

        if ($userData->Error || count($userData->Data) !== 1) {
            throw new Exception('No account found for ' . htmlspecialchars($email, ENT_QUOTES) . '. Please contact your administrator.');
        }

        $user = $userData->Data[0];

        if ((int)$user->IsLocked === 1) {
            throw new Exception('Account is locked. Please contact your administrator.');
        }

        // Subscription check — same as normal login
        $this->load->library('subscription');
        $subscriptionCheck = $this->subscription->checkSubscription($user->UserUID);
        $this->subscription->logLoginAttempt(
            $user->UserUID,
            $email,
            $subscriptionCheck->isValid ? 'Success' : 'Blocked_Expired',
            $subscriptionCheck->status,
            $subscriptionCheck->isValid ? null : $subscriptionCheck->message
        );

        if (!$subscriptionCheck->isValid) {
            $this->session->set_flashdata('subscription_expired',  true);
            $this->session->set_flashdata('subscription_message',  $subscriptionCheck->message);
            $this->session->set_flashdata('subscription_status',   $subscriptionCheck->status);
            throw new Exception($subscriptionCheck->message);
        }

        // Build JWT payload
        $this->load->model('login_model');
        $jwtPayload = $this->login_model->formatJWTPayload($user);
        if ($jwtPayload->Error) {
            throw new Exception('Oops! ' . $jwtPayload->Message);
        }

        $newPayload   = clone $jwtPayload;
        $orgShortCode = $newPayload->JWTData['Org']['OrgShortCode'] ?? '';
        $orgToken     = $newPayload->JWTData['Org']['OrgToken']     ?? '';

        $auditId = $this->_logOAuthSuccess($user, $provider, $email);
        $jwtPayload->JWTData['User']['auditId'] = $auditId ?? 0;

        $sessionToken = bin2hex(random_bytes(32));
        $jwtPayload->JWTData['User']['SessionToken'] = $sessionToken;

        $jwtResult = $this->login_model->setJwtToken($user, $jwtPayload);
        if ($jwtResult->Error) {
            throw new Exception('Oops! ' . $jwtResult->Message);
        }

        // Update user record
        $this->load->model('dbwrite_model');
        $deviceInfo = $this->_getDeviceInfo();
        $this->dbwrite_model->updateData('Users', 'UserTbl', [
            'LastLogin'           => date('Y-m-d H:i:s'),
            'CurrentSessionToken' => $sessionToken,
            'LastLoginOn'         => date('Y-m-d H:i:s'),
            'LastLoginIP'         => $this->input->ip_address(),
            'LastLoginDevice'     => $provider . ' / ' . $deviceInfo['browser'] . ' / ' . $deviceInfo['os'] . ' (' . $deviceInfo['device_type'] . ')',
        ], ['UserUID' => $user->UserUID]);

        // Warm Redis caches
        $loginExpiry = (int) getenv('LOGIN_EXPIRE_SECS');
        $userUID     = $user->UserUID;
        $this->redisservice->setCache('UserActiveSession_' . $userUID, $sessionToken, $loginExpiry);
        $this->redisservice->setUserCache('menus',       $userUID, $newPayload->JWTData['UserMainModule'] ?? [], $loginExpiry, $orgShortCode, $orgToken);
        $this->redisservice->setUserCache('submenus',    $userUID, $newPayload->JWTData['UserSubModule']  ?? [], $loginExpiry, $orgShortCode, $orgToken);
        $this->redisservice->setUserCache('modules',     $userUID, $newPayload->JWTData['ModuleInfo']     ?? [], $loginExpiry, $orgShortCode, $orgToken);
        $this->redisservice->setUserCache('permissions', $userUID, $newPayload->JWTData['Permissions']    ?? [], $loginExpiry, $orgShortCode, $orgToken);
        $this->redisservice->setUserCache('userinfo',    $userUID, $user,                                        $loginExpiry, $orgShortCode, $orgToken);

        $orgUID = $user->UserOrgUID ?? null;
        if ($orgUID) {
            $this->load->model('users_model');
            $orgUsers = $this->users_model->getOrgUsersForCache((int) $orgUID);
            $this->redisservice->setCache($this->redisservice->orgKey('org_users', $orgShortCode, $orgToken), $orgUsers, $loginExpiry);

            $this->load->model('organisation_model');
            $this->organisation_model->getOrgInfoCached((int) $orgUID, $orgShortCode, $orgToken);

            $dispatchAddresses = $this->organisation_model->getAllOrgDispatchAddresses((int) $orgUID);
            $this->redisservice->setCache(
                $this->redisservice->orgKey('org_dispatch_addresses', $orgShortCode, $orgToken),
                $dispatchAddresses,
                $loginExpiry
            );
        }

        $intendedUrl = $this->session->userdata('intended_url');
        $this->session->unset_userdata('intended_url');
        redirect(!empty($intendedUrl) ? $intendedUrl : 'dashboard', 'refresh');
    }

    /* ===================================================================
     * Google API helpers
     * =================================================================== */
    private function _googleExchangeCode($code) {
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'code'          => $code,
                'client_id'     => getenv('GOOGLE_CLIENT_ID'),
                'client_secret' => getenv('GOOGLE_CLIENT_SECRET'),
                'redirect_uri'  => base_url('auth/google/callback'),
                'grant_type'    => 'authorization_code',
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);
        return json_decode($resp, true) ?: [];
    }

    private function _googleGetUser($accessToken) {
        $ch = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $accessToken],
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);
        return json_decode($resp, true) ?: [];
    }

    /* ===================================================================
     * Facebook API helpers
     * =================================================================== */
    private function _facebookExchangeCode($code) {
        $ch = curl_init('https://graph.facebook.com/v18.0/oauth/access_token?' . http_build_query([
            'client_id'     => getenv('FACEBOOK_APP_ID'),
            'client_secret' => getenv('FACEBOOK_APP_SECRET'),
            'redirect_uri'  => base_url('auth/facebook/callback'),
            'code'          => $code,
        ]));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);
        return json_decode($resp, true) ?: [];
    }

    private function _facebookGetUser($accessToken) {
        $ch = curl_init('https://graph.facebook.com/v18.0/me?' . http_build_query([
            'fields'       => 'id,name,email',
            'access_token' => $accessToken,
        ]));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);
        return json_decode($resp, true) ?: [];
    }

    /* ===================================================================
     * Helpers
     * =================================================================== */
    private function _logOAuthSuccess($userData, $provider, $email) {
        try {
            $this->load->model('dbwrite_model');
            $deviceInfo = $this->_getDeviceInfo();
            $result = $this->dbwrite_model->insertData('Security', 'UserLoginAudit', [
                'UserUID'           => $userData->UserUID,
                'OrgUID'            => $userData->UserOrgUID,
                'BranchUID'         => $userData->BranchUID,
                'LoginStatus'       => 'SUCCESS',
                'LoginType'         => $provider,
                'AttemptedUsername' => $email,
                'IPAddress'         => $this->input->ip_address(),
                'UserAgent'         => $this->input->user_agent(),
                'DeviceType'        => $deviceInfo['device_type'],
                'Browser'           => $deviceInfo['browser'],
                'OS'                => $deviceInfo['os'],
                'TokenIssued'       => 1,
            ]);
            return $result->ID ?? null;
        } catch (Exception $e) {
            return null;
        }
    }

    private function _getDeviceInfo() {
        $ua   = $this->input->user_agent();
        $info = ['device_type' => 'Desktop', 'browser' => 'Unknown', 'os' => 'Unknown'];
        if (preg_match('/(mobile|android|iphone|ipad)/i', $ua)) $info['device_type'] = 'Mobile';
        if (preg_match('/Chrome/i', $ua))      $info['browser'] = 'Chrome';
        elseif (preg_match('/Firefox/i', $ua)) $info['browser'] = 'Firefox';
        elseif (preg_match('/Safari/i', $ua))  $info['browser'] = 'Safari';
        if (preg_match('/Windows/i', $ua))     $info['os'] = 'Windows';
        elseif (preg_match('/Mac OS/i', $ua))  $info['os'] = 'Mac OS';
        elseif (preg_match('/Linux/i', $ua))   $info['os'] = 'Linux';
        elseif (preg_match('/Android/i', $ua)) $info['os'] = 'Android';
        elseif (preg_match('/iOS/i', $ua))     $info['os'] = 'iOS';
        return $info;
    }
}
