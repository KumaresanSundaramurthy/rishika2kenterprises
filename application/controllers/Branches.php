<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Branches extends MY_Controller {

    public  $pageData = [];
    protected $EndReturnData;
    protected $pageModuleUID;

    public function __construct() {
        parent::__construct();

        $this->pageModuleUID = 50;

    }

    public function index(): void {
        $this->_loadPageTitle($this->pageModuleUID);
        if (empty($this->pageData['PageTitle'])) $this->pageData['PageTitle'] = 'Branches';
        try {
            $this->load->model('branches_model');
            $this->pageData['BranchTypes'] = $this->branches_model->getBranchTypesList();
            $this->load->view('settings/branches/view', $this->pageData);
        } catch (Exception $e) { notifyError('Branches::index', $e); redirect('dashboard', 'refresh'); }
    }
    
}