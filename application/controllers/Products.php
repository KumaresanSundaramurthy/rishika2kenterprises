<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Products extends MY_Controller {

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

            $tabRaw    = strtolower($this->input->get('tab', TRUE) ?: 'item');
            $tabMap    = ['item' => 'item', 'group' => 'group', 'category' => 'category', 'pricelist' => 'pricelist', 'brand' => 'brand', 'size' => 'size', 'items' => 'item', 'groups' => 'group', 'categories' => 'category', 'pricelists' => 'pricelist', 'brands' => 'brand', 'sizes' => 'size'];
            $activeTab = $tabMap[$tabRaw] ?? 'item';
            $initSearch = $this->input->get('search', TRUE) ?: '';

            $OrgUID = (int) $this->pageData['JwtData']->Org->OrgUID;

            $this->pageData['ActiveTabData']  = $activeTab;
            $tabNameMap = ['item' => 'Item', 'group' => 'Groups', 'pricelist' => 'PriceLists', 'category' => 'Categories', 'brand' => 'Brands', 'size' => 'Sizes'];
            $this->pageData['ActiveTabName']  = $tabNameMap[$activeTab] ?? ucfirst($activeTab);
            $this->pageData['InitSearch']     = $initSearch;
            $this->pageData['ActiveModuleId'] = 4;

            $resp = $this->_servicesGet('api/users/getOrgUsers', ['orgId' => $OrgUID]);
            $this->pageData['OrgUsers'] = ($resp && !$resp->Error) ? ($resp->Users ?? []) : [];

            $this->load->view('products/view', $this->pageData);

        } catch (Exception $e) {
            notifyError('Products::index', $e);
            redirect('dashboard', 'refresh');
        }

    }

}