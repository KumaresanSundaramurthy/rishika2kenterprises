<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Subscription extends CI_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function index(): void {
        redirect('subscription/expired', 'refresh');
    }

    public function expired(): void {
        $this->load->view('subscription/expired');
    }

}
