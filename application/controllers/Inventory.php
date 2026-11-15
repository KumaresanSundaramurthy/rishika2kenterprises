<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property object $inventory_model
 * @property object $dbwrite_model
 * @property object $globalservice
 * @property object $input
 * @property object $redisservice
 */
class Inventory extends MY_Controller {

    public  $pageData      = [];
    protected $pageModuleUID = 117;

    public function __construct() {
        parent::__construct();
    }

    private function _getCategories(): array {
        $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';
        $resp     = services_get('api/products/getCategoryOptions?' . http_build_query([
            'orgId'    => $this->_orgUID(),
            'langCode' => $this->_uiLang(),
        ]), ['Authorization: Bearer ' . $jwtToken]);
        if (!$resp || $resp->Error || empty($resp->Options)) return [];
        return array_map(function(object $o): object {
            $c = new stdClass();
            $c->CategoryUID  = $o->uid;
            $c->CategoryName = $o->name;
            return $c;
        }, (array)$resp->Options);
    }

    // ── Main page ───────────────────────────────────────────────────────
    public function index(): void {

        if (!$this->_loadPageTitle($this->pageModuleUID)) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }

        try {
            $orgUID   = (int) $this->pageData['JwtData']->Org->OrgUID;
            $orgUsers = $this->_requireCache($this->redisservice->orgKey('org-users'));

            $this->pageData['ModAllCount']    = 0;
            $this->pageData['Categories']     = $this->_getCategories();
            $this->pageData['OrgUsers']       = $orgUsers ?? [];
            $this->pageData['ShowUserFilter'] = !empty($orgUsers) && count($orgUsers) > 1;

            $this->load->view('inventory/view', $this->pageData);

        } catch (Throwable $e) {
            notifyError('Inventory::index', $e);
            redirect('dashboard', 'refresh');
        }

    }

    // ── Global Timeline page ───────────────────────────────────────────────
    public function timelinePage(): void {

        if (!$this->_loadPageTitle(118)) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }

        try {
            $orgUID          = (int) $this->pageData['JwtData']->Org->OrgUID;
            $GeneralSettings = $this->pageData['JwtData']->GenSettings ?? new stdClass();
            $fmt             = $GeneralSettings->ListDateFormat ?? 'd M Y';
            $datePref        = $this->getDateFilterPreference('inventory');

            $defaultFilter = ['DateFrom' => $datePref['from'], 'DateTo' => $datePref['to']];

            $this->pageData['DefaultFilter']        = $defaultFilter;
            $this->pageData['Categories']           = $this->_getCategories();
            $this->pageData['SavedDateRange']       = $datePref['range'];
            $this->pageData['SavedDateLabel']       = $datePref['label'];
            $this->pageData['SavedDateFromDisplay'] = date($fmt, strtotime($datePref['from']));
            $this->pageData['SavedDateToDisplay']   = date($fmt, strtotime($datePref['to']));

            $this->load->view('inventory/timeline_view', $this->pageData);

        } catch (Throwable $e) {
            notifyError('Inventory::timelinePage', $e);
            redirect('dashboard', 'refresh');
        }

    }

    // ── Serial Numbers Page ───────────────────────────────────────────────────

    public function serialsPage(): void {

        if (!$this->_loadPageTitle($this->pageModuleUID)) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }

        try {
            $validStatuses = ['Available', 'Sold', 'Returned', 'Damaged'];
            $initStatus    = ucfirst(strtolower($this->input->get('status') ?: ''));
            if (!in_array($initStatus, $validStatuses, true)) $initStatus = '';

            $this->pageData['InitStatus'] = $initStatus;
            $this->pageData['InitSearch'] = trim($this->input->get('search') ?: '');

            $this->load->view('inventory/serials_view', $this->pageData);

        } catch (Throwable $e) {
            notifyError('Inventory::serialsPage', $e);
            redirect('inventory', 'refresh');
        }
    }
}