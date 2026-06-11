<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Setpassword extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('users_model');
        $this->load->helper('url');
    }

    // ── Show form or status page ──────────────────────────────────────────────
    public function index($token = '') {
        if (empty($token)) {
            $this->load->view('setpassword/index', ['state' => 'invalid']);
            return;
        }

        $user = $this->users_model->getUserByPasswordToken($token);

        if (!$user) {
            $this->load->view('setpassword/index', ['state' => 'invalid']);
            return;
        }

        if ((int)$user->IsPasswordSet === 1) {
            $this->load->view('setpassword/index', [
                'state' => 'already_set',
                'user'  => $user,
            ]);
            return;
        }

        $this->load->view('setpassword/index', [
            'state' => 'form',
            'token' => $token,
            'user'  => $user,
        ]);
    }

    // ── Handle form submission ────────────────────────────────────────────────
    public function submit() {
        $token = $this->input->post('token');
        $pwd   = $this->input->post('Password');
        $cpwd  = $this->input->post('ConfirmPassword');

        $user = $token ? $this->users_model->getUserByPasswordToken($token) : null;

        if (!$user) {
            $this->load->view('setpassword/index', ['state' => 'invalid']);
            return;
        }

        if ((int)$user->IsPasswordSet === 1) {
            $this->load->view('setpassword/index', [
                'state' => 'already_set',
                'user'  => $user,
            ]);
            return;
        }

        if (empty($pwd) || strlen($pwd) < 6) {
            $this->load->view('setpassword/index', [
                'state' => 'form',
                'token' => $token,
                'user'  => $user,
                'error' => 'Password must be at least 6 characters.',
            ]);
            return;
        }

        if ($pwd !== $cpwd) {
            $this->load->view('setpassword/index', [
                'state' => 'form',
                'token' => $token,
                'user'  => $user,
                'error' => 'Passwords do not match.',
            ]);
            return;
        }

        $this->users_model->updateUserPassword($user->UserUID, $pwd);

        $this->load->view('setpassword/index', [
            'state' => 'success',
            'user'  => $user,
        ]);
    }
}
