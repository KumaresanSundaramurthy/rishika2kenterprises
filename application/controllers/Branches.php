<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Branches extends MY_Controller {

    public  $pageData = [];
    protected $pageModuleUID = 50;

    public function __construct() {
        parent::__construct();
    }

    public function index(): void {
        try {
            $this->_loadPageTitle($this->pageModuleUID);
        } catch (Exception $e) {
            notifyError('Branches::index', $e);
        }
        try {
            $resp = services_get('api/branches/getBranchTypes', withJwt: true);
            $this->pageData['BranchTypes'] = $resp->Data ?? [];
            $this->load->view('settings/branches/view', $this->pageData);
        } catch (Exception $e) { notifyError('Branches::index', $e); redirect('dashboard', 'refresh'); }
    }
    
}