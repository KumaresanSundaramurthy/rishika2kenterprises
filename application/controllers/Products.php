<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Products extends MY_Controller {

    public $pageData = array();

    public function __construct() {
        parent::__construct();

    }

    private function sanitizeTabInput($tab): string {

        $tab = strtolower($tab ?: 'item');
        $map = ['item' => 'item', 'group' => 'group', 'category' => 'category', 'pricelist' => 'pricelist', 'brand' => 'brand', 'size' => 'size', 'items' => 'item', 'groups' => 'group', 'categories' => 'category', 'pricelists' => 'pricelist', 'brands' => 'brand', 'sizes' => 'size'];
        return $map[$tab] ?? 'item';

    }

    public function index() {

        if (!$this->_loadPageTitle()) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }

        try {

            $activeTab = $this->sanitizeTabInput($this->input->get('tab', TRUE));
            $initSearch = $this->input->get('search', TRUE) ?: '';

            $OrgUID = (int) $this->pageData['JwtData']->Org->OrgUID;

            $this->pageData['ActiveTabData']  = $activeTab;
            $tabNameMap = ['item' => 'Item', 'group' => 'Groups', 'pricelist' => 'PriceLists', 'category' => 'Categories', 'brand' => 'Brands', 'size' => 'Sizes'];
            $this->pageData['ActiveTabName']  = $tabNameMap[$activeTab] ?? ucfirst($activeTab);
            $this->pageData['InitSearch']     = $initSearch;
            $this->pageData['ActiveModuleId'] = 4;

            $this->load->model('users_model');
            $this->pageData['OrgUsers'] = $this->users_model->getOrgUsersForCache($OrgUID);

            $this->load->view('products/view', $this->pageData);

        } catch (Exception $e) {
            notifyError('Products::index', $e);
            redirect('dashboard', 'refresh');
        }

    }

}