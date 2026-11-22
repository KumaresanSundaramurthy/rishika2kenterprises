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

}