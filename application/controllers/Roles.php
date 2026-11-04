<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Roles extends MY_Controller {

    public $pageData = array();
    protected $EndReturnData;

    public function __construct() {
        parent::__construct();
    }

    // ── Page ─────────────────────────────────────────────────────────

    public function index(): void {
        if (!$this->_loadPageTitle()) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }
        $this->load->view('settings/roles/view', $this->pageData);
    }

}