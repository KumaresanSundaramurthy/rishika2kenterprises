<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Salaryadvances extends MY_Controller {

    public  $pageData     = [];

    public function __construct() { parent::__construct(); }

    public function index(): void {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $this->load->view('hrms/salaryadvances/view', $this->pageData);
        } catch (Exception $e) {
            notifyError('Salaryadvances::index', $e);
            redirect('dashboard', 'refresh');
        }
    }

}
