<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Designations extends MY_Controller {

    public $pageData = [];

    public function __construct() { parent::__construct(); }

    public function index(): void {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $this->load->view('hrms/designations/view', $this->pageData);
        } catch (Exception $e) {
            $this->notifyError('Designations::index', $e);
            redirect('dashboard', 'refresh');
        }
    }

}