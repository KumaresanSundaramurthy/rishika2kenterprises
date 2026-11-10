<?php defined('BASEPATH') or exit('No direct script access allowed');

class Vendors extends MY_Controller {

    public $pageData = array();

    public function __construct() {
        parent::__construct();
    }
    
    public function index() {
        if (!$this->_loadPageTitle()) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }
        try {
            
            $tabSlugMap = ['all' => 'All', 'groups' => 'Groups'];
            $tabSlug    = strtolower(trim($this->input->get('tab') ?: 'all'));
            $initTab    = $tabSlugMap[$tabSlug] ?? 'All';
            $initSearch = trim($this->input->get('search') ?: '');

            $this->pageData['InitTab']    = $initTab;
            $this->pageData['InitSearch'] = $initSearch;

            $this->pageData['VendShowStats'] = (int)(
                ($this->pageData['JwtData']->GenSettings->ShowStats ?? 1) &&
                ($this->pageData['JwtData']->TransSettings->ShowTransactionStats ?? 1)
            );

            $orgUsers = $this->_requireCache($this->redisservice->orgKey('org-users'));
            if (!$orgUsers) return;
            $this->pageData['OrgUsers']      = $orgUsers;
            $this->pageData['ShowUserFilter'] = count($orgUsers) > 1;


            $this->_loadUpstashConfig();
            $this->load->view('vendors/view', $this->pageData);

        } catch (Exception $e) {
            notifyError('Vendors::index', $e);
            redirect('dashboard', 'refresh');
        }
    }
}