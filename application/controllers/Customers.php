<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Customers extends MY_Controller {

    public $pageData = array();

    public function __construct() {
        parent::__construct();
    }

    // ── Page routes ───────────────────────────────────────────────────────────
    public function index() {
        if (!$this->_loadPageTitle()) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }
        try {

            $orgUID = $this->pageData['JwtData']->Org->OrgUID;

            $this->load->model('customers_model');

            $tabSlugMap = ['all' => 'All', 'groups' => 'Groups'];
            $tabSlug    = strtolower(trim($this->input->get('tab') ?: 'all'));
            $initTab    = $tabSlugMap[$tabSlug] ?? 'All';
            $initSearch = trim($this->input->get('search') ?: '');

            $this->pageData['InitTab']    = $initTab;
            $this->pageData['InitSearch'] = $initSearch;

            $orgUsers = $this->_requireCache($this->redisservice->orgKey('org-users'));
            if (!$orgUsers) return;
            
            $this->pageData['OrgUsers']      = $orgUsers;
            $this->pageData['ShowUserFilter'] = count($orgUsers) > 1;

            $this->_loadUpstashConfig();

            $this->load->view('customers/view', $this->pageData);

        } catch (Exception $e) {
            notifyError('Customers::index', $e);
            redirect('dashboard', 'refresh');
        }
    }

}