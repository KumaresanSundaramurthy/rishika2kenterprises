<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Departments extends MY_Controller {

    public $pageData = [];

    public function __construct() { parent::__construct(); }

    public function index(): void {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $this->load->view('hrms/departments/view', $this->pageData);
        } catch (Exception $e) {
            $this->notifyError('Departments::index', $e);
            redirect('dashboard', 'refresh');
        }
    }

}
