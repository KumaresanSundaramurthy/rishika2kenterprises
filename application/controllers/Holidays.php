<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Holidays extends MY_Controller {

    public $pageData = [];

    public function __construct() { parent::__construct(); }

    public function index(): void {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $this->load->view('hrms/holidays/view', $this->pageData);
        } catch (Exception $e) {
            notifyError('Holidays::index', $e);
            redirect('dashboard', 'refresh');
        }
    }

}
