<?php defined('BASEPATH') OR exit('No direct script access allowed');

class PrintThemes extends MY_Controller {

    public $pageData = [];
    protected $pageModuleUID = 16;

    public function __construct() {
        parent::__construct();
    }

    public function index(): void {
        try {
            $this->_loadPageTitle($this->pageModuleUID);
        } catch (Exception $e) {
            notifyError('PrintThemes::index', $e);
        }
        $this->load->view('settings/printthemes/view', $this->pageData);
    }

}