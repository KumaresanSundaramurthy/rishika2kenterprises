<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Signup extends CI_Controller {

    public function index(): void {
        $this->load->view('signup/view');
    }

    public function doSignup(): void {
        header('Content-Type: application/json');

        $rawInput = file_get_contents('php://input');
        $payload  = json_decode($rawInput, true) ?: [];

        $httpCode = null;
        $rawBody  = null;
        $resp = services_post('api/signup/doSignup', $payload, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . getenv('PUBLIC_SERVICE_TOKEN'),
            'X-Forwarded-For: ' . $this->input->ip_address(),
            'User-Agent: '      . $this->input->user_agent(),
        ], 15, true, $httpCode, $rawBody);

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

        // TEMP DEBUG — remove after production issue resolved
        $this->load->library('telegramnotifier');
        Telegramnotifier::alert('Signup::doSignup DEBUG', [
            'httpCode'   => $httpCode,
            'tokenSet'   => !empty($resp->token)       ? 'YES' : 'NO',
            'refreshSet' => !empty($resp->refreshToken) ? 'YES' : 'NO',
            'isHttps'    => $isHttps ? '1' : '0',
            'cookieName' => getenv('JWT_COOKIE_NAME') ?: '(not set)',
        ]);

        /* ciSuccess() spreads fields flat — token/refreshToken are top-level, not under Data */
        if (!empty($resp->token)) {
            setcookie(getenv('JWT_COOKIE_NAME'), $resp->token, [
                'expires'  => time() + $sessionTtl,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure'   => $isHttps,
            ]);
        }

        if (!empty($resp->refreshToken)) {
            setcookie(getenv('JWT_REFRESH_COOKIE_NAME') ?: 'r2k_refresh', $resp->refreshToken, [
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