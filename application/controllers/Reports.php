<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Reports extends MY_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function index() {
        if (empty($this->pageData['JwtData'])) {
            redirect('portal');
            return;
        }
        $this->pageData['PageTitle'] = 'Reports';
        $this->load->view('reports/index', $this->pageData);
    }
}
