<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Rental extends MY_Controller {

    public  $pageData      = [];
    protected $pageModuleUID = 116;

    public function __construct() {
        parent::__construct();
    }

    public function index(): void {
        if (!$this->_loadPageTitle($this->pageModuleUID)) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }
        $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;
        $this->load->view('transactions/rental/view', $this->pageData);
    }

}