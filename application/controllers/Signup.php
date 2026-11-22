<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Signup extends CI_Controller {

    public function index(): void {
        $this->load->helper('auth');
        if (is_authenticated()) {
            redirect('dashboard', 'refresh');
            return;
        }
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

        $isHttps     = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $refreshName = getenv('JWT_REFRESH_COOKIE_NAME') ?: 'r2k_refresh';
        $sessionTtl  = (int)(getenv('LOGIN_EXPIRE_SECS') ?: 43200);
        $data        = $resp->Data ?? (object)[];

        /* JWT cookie — set on CI3's domain */
        if (!empty($data->token)) {
            setcookie(getenv('JWT_COOKIE_NAME'), $data->token, [
                'expires'  => time() + $sessionTtl,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure'   => $isHttps,
            ]);
        }

        /* Refresh cookie — read from NestJS Set-Cookie header and re-set on CI3's domain */
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

        echo json_encode([
            'Error'   => false,
            'Message' => $resp->Message ?? 'Organisation registered successfully!',
            'Data'    => ['IsPaidPlan' => !empty($data->IsPaidPlan)],
        ]);
    }

}