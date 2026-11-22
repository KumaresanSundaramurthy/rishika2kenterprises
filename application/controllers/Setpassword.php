<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Setpassword extends CI_Controller {

    // ── Show form or status page ──────────────────────────────────────────────
    public function index($token = ''): void {
        if (empty($token)) {
            $this->load->view('login/setpassword/index', ['state' => 'invalid']);
            return;
        }

        $resp = services_get('api/auth/password/set/' . rawurlencode($token));

        if (!$resp) {
            $this->load->view('login/setpassword/index', ['state' => 'invalid']);
            return;
        }

        $state = $resp->state ?? 'invalid';

        if ($state === 'already_set') {
            $this->load->view('login/setpassword/index', [
                'state' => 'already_set',
                'user'  => $resp,
            ]);
            return;
        }

        if ($state === 'form') {
            $this->load->view('login/setpassword/index', [
                'state' => 'form',
                'token' => $token,
                'user'  => $resp,
            ]);
            return;
        }

        $this->load->view('login/setpassword/index', ['state' => 'invalid']);
    }

}