<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Customers extends MY_Controller {

    public $pageData = array();
    protected $EndReturnData;

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

            $this->pageData['GrpTotal']       = 0;
            // Table data is no longer server-rendered; JS fetches via services endpoints on load
            $this->pageData['InitTotalCount'] = 0;

            $this->pageData['InitTab']    = $initTab;
            $this->pageData['InitSearch'] = $initSearch;

            $this->pageData['CustStats'] = null; // fetched client-side via services

            // Resolve org phone country code from JwtData (sourced from OrganisationTbl at login)
            $this->pageData['OrgCCode'] = $this->pageData['JwtData']->Org->OrgCCode  ?? '';
            $this->pageData['OrgCISO2'] = $this->pageData['JwtData']->Org->OrgCISO2  ?? '';

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