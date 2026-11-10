<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Branches extends MY_Controller {

    public  $pageData = [];
    protected $pageModuleUID;

    public function __construct() {
        parent::__construct();

        $this->pageModuleUID = 50;

    }

    public function index(): void {
        $this->_loadPageTitle($this->pageModuleUID);
        if (empty($this->pageData['PageTitle'])) $this->pageData['PageTitle'] = 'Branches';
        try {
            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';
            $resp = services_get('api/branches/getBranchTypes', ['Authorization: Bearer ' . $jwtToken]);
            $this->pageData['BranchTypes'] = $resp->Data ?? [];
            $this->load->view('settings/branches/view', $this->pageData);
        } catch (Exception $e) { notifyError('Branches::index', $e); redirect('dashboard', 'refresh'); }
    }
    
}