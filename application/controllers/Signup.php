<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Signup extends CI_Controller {

    public function index(): void {
        $this->load->view('signup/view');
    }

    public function doSignup(): void {
        header('Content-Type: application/json');

        $rawInput = file_get_contents('php://input');
        $payload  = json_decode($rawInput, true) ?: [];

        $proto                = (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ? 'https' : 'http';
        $payload['AppBaseUrl'] = $proto . '://' . ($_SERVER['HTTP_HOST'] ?? '');

        $httpCode    = null;
        $rawBody     = null;
        $publicToken = getenv('PUBLIC_SERVICE_TOKEN');
        $headers     = [
            'Content-Type: application/json',
            'X-Forwarded-For: ' . $this->input->ip_address(),
            'User-Agent: '      . $this->input->user_agent(),
        ];
        if ($publicToken) {
            $headers[] = 'Authorization: Bearer ' . $publicToken;
        }
        $resp = services_post('api/signup/doSignup', $payload, $headers, 15, false, $httpCode, $rawBody);

        if (!$resp || $httpCode !== 200) {
            $msg = !empty($resp->Message) ? $resp->Message : 'Registration failed. Please try again.';
            echo json_encode(['Error' => true, 'Message' => $msg]);
            return;
        }

        if (!empty($resp->Error)) {
            echo json_encode(['Error' => true, 'Message' => $resp->Message ?? 'Registration failed.']);
            return;
        }

        $isHttps    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                   || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        $sessionTtl = (int)(getenv('LOGIN_EXPIRE_SECS') ?: 43200);

        $cookieName  = getenv('JWT_COOKIE_NAME') ?: '';
        $refreshName = getenv('JWT_REFRESH_COOKIE_NAME') ?: 'r2k_refresh';

        /* ciSuccess() spreads fields flat — token/refreshToken are top-level, not under Data */
        if (!empty($resp->token) && $cookieName) {
            setcookie($cookieName, $resp->token, [
                'expires'  => time() + $sessionTtl,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure'   => $isHttps,
            ]);
        }

        if (!empty($resp->refreshToken)) {
            setcookie($refreshName, $resp->refreshToken, [
                'expires'  => time() + $sessionTtl,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure'   => $isHttps,
            ]);
        }

        echo json_encode([
            'Error'   => false,
            'Message' => $resp->Message ?? 'Organisation registered successfully!',
            'Data'    => ['IsPaidPlan' => !empty($resp->IsPaidPlan)],
        ]);
    }

}