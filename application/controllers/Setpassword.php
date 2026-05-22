<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Setpassword extends CI_Controller {

    private $WriteDb;

    public function __construct() {
        parent::__construct();
        $this->WriteDb = $this->load->database('WriteDB', TRUE);
        $this->load->helper('url');
    }

    // ── Show form or status page ──────────────────────────────────────────────
    public function index($token = '') {
        if (empty($token)) {
            $this->load->view('setpassword/index', ['state' => 'invalid']);
            return;
        }

        $user = $this->_getUserByToken($token);

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

        $user = $token ? $this->_getUserByToken($token) : null;

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

        $this->WriteDb->where('UserUID', $user->UserUID);
        $this->WriteDb->update('Users.UserTbl', [
            'Password'      => base64_encode($pwd),
            'IsPasswordSet' => 1,
            'UpdatedOn'     => date('Y-m-d H:i:s'),
        ]);

        $this->load->view('setpassword/index', [
            'state' => 'success',
            'user'  => $user,
        ]);
    }

    // ── Private ───────────────────────────────────────────────────────────────
    private function _getUserByToken($token) {
        try {
            $this->WriteDb->select('UserUID, FirstName, EmailAddress, IsPasswordSet');
            $this->WriteDb->from('Users.UserTbl');
            $this->WriteDb->where('PasswordSetToken', $token);
            $this->WriteDb->where('IsDeleted', 0);
            $this->WriteDb->limit(1);
            return $this->WriteDb->get()->row();
        } catch (Throwable $e) {
            log_message('error', 'Setpassword::_getUserByToken — ' . $e->getMessage());
            return null;
        }
    }
}
