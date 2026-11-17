<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Activitylog extends MY_Controller {

    protected $pageModuleUID = 18;

    public function __construct() {
        parent::__construct();
    }

    // ── Page load ────────────────────────────────────────────────────────────

    public function index(): void {
        try {
            $this->_loadPageTitle($this->pageModuleUID);
        } catch (Exception $e) {
            notifyError('Activitylog::index', $e);
        }

        $this->load->view('settings/activitylog/view', $this->pageData);
    }
    
}