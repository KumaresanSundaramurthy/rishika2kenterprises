<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Activitylog extends MY_Controller {

    public function __construct() {
        parent::__construct();
    }

    // ── Page load ────────────────────────────────────────────────────────────

    public function index(): void {
        $this->_loadPageTitle();

        if (empty($this->pageData['PageTitle'])) {
            $this->pageData['PageTitle']       = 'Activity Log';
            $this->pageData['PageDescription'] = 'Full audit trail of all user actions';
            $this->pageData['PageIcon']        = 'bx-history';
            $this->pageData['PageIconBg']      = '#ede9fe';
            $this->pageData['PageIconColor']   = '#7c3aed';
        }

        $this->load->view('settings/activitylog/view', $this->pageData);
    }
    
}