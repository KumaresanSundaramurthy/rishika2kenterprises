<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property object $inventory_model
 * @property object $dbwrite_model
 * @property object $cachehelper
 * @property object $globalservice
 * @property object $input
 * @property object $redisservice
 */
class Inventory extends MY_Controller {

    public  $pageData      = [];
    protected $pageModuleUID = 117;

    public function __construct() {
        parent::__construct();
        $this->load->helper('transaction');
        $this->load->model(['inventory_model', 'dbwrite_model', 'dbwrite_ext_model']);
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
            $this->pageData['Categories']     = $this->inventory_model->getCategories($orgUID);
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
            $this->pageData['Categories']           = $this->inventory_model->getCategories($orgUID);
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

    // ── Export inventory list ───────────────────────────────────────────────
    public function export() {
        try {
            $type   = $this->input->get('Type')   ?: 'CSV';
            $filter = $this->input->get('Filter') ?: '{}';
            $filter = json_decode($filter, true)  ?: [];

            $orgUID = (int)$this->pageData['JwtData']->Org->OrgUID;
            $this->load->model('organisation_model');
            $orgResult = $this->organisation_model->getOrgInfoCached($orgUID);
            $orgInfo   = ($orgResult->Error === FALSE) ? $orgResult->Data : null;

            $data   = $this->inventory_model->getInventoryList($orgUID, $filter, 0, 0);

            $headers = ['#', 'Item Name', 'Category', 'Unit', 'Qty', 'Status', 'Purchase Price', 'Sale Price', 'HSN/SAC', 'Last Updated', 'Updated By'];
            $rows    = [];
            foreach ($data as $i => $row) {
                $rows[] = $this->_mapInventoryRow($i + 1, $row);
            }

            $timezone  = $this->pageData['JwtData']->User->Timezone ?? 'UTC';
            $colWidths = ['3%','18%','10%','5%','5%','9%','9%','9%','9%','12%','11%'];
            $this->_sendExport($type, 'Inventory_Data', 'Inventory', 'Inventory / Stock Report', $headers, $rows, $orgInfo, $timezone, $colWidths);

        } catch (Exception $e) {
            notifyError('Inventory::export', $e);
            echo json_encode(['Error' => true, 'Message' => $e->getMessage()]);
        }
    }

    // ── Export stock timeline ───────────────────────────────────────────────
    public function exportTimeline() {
        try {
            $type   = $this->input->get('Type')   ?: 'CSV';
            $filter = $this->input->get('Filter') ?: '{}';
            $filter = json_decode($filter, true)  ?: [];

            $orgUID = (int)$this->pageData['JwtData']->Org->OrgUID;
            $this->load->model('organisation_model');
            $orgResult = $this->organisation_model->getOrgInfoCached($orgUID);
            $orgInfo   = ($orgResult->Error === FALSE) ? $orgResult->Data : null;

            $data   = $this->inventory_model->getGlobalTimelineExport($orgUID, $filter);

            $moduleLabels = [
                103 => 'Invoice', 105 => 'Purchase', 106 => 'Sales Return',
                107 => 'Credit Note', 108 => 'Purchase Return', 118 => 'Manual Adj.',
            ];

            $headers = ['#', 'Date', 'Item Name', 'Category', 'Source', 'Reference', 'Type', 'Qty', 'Price', 'Remarks', 'Created By'];
            $rows    = [];
            foreach ($data as $i => $row) {
                $rows[] = $this->_mapTimelineRow($i + 1, $row, $moduleLabels);
            }

            $timezone  = $this->pageData['JwtData']->User->Timezone ?? 'UTC';
            $colWidths = ['3%','9%','16%','9%','7%','9%','7%','5%','7%','16%','12%'];
            $this->_sendExport($type, 'Inventory_Timeline', 'Timeline', 'Inventory Stock Timeline', $headers, $rows, $orgInfo, $timezone, $colWidths);

        } catch (Exception $e) {
            notifyError('Inventory::exportTimeline', $e);
            echo json_encode(['Error' => true, 'Message' => $e->getMessage()]);
        }
    }

    // ── Private export helpers ───────────────────────────────────────────────
    private function _mapInventoryRow($i, $row) {
        $qty    = (float)($row->AvailableQuantity ?? 0);
        $low    = (float)($row->LowStockAlertAt   ?? 0);
        $status = ($qty <= 0) ? 'Out of Stock'
                : (($low > 0 && $qty <= $low) ? 'Low Stock' : 'In Stock');
        return [
            $i,
            $row->ItemName       ?? '',
            $row->CategoryName   ?? '',
            $row->UnitName       ?? '',
            $qty,
            $status,
            $row->PurchasePrice  ?? '',
            $row->SellingPrice   ?? '',
            $row->HSNSACCode     ?? '',
            $row->UpdatedOn      ? date('d M Y, h:i A', strtotime($row->UpdatedOn)) : '',
            $row->UpdatedByName  ?? '',
        ];
    }

    private function _mapTimelineRow($i, $row, $moduleLabels) {
        $moduleUID = (int)$row->ModuleUID;
        $source    = $moduleLabels[$moduleUID] ?? 'Unknown';
        $ref       = ($moduleUID === 118)
                   ? (!empty($row->AdjUID) ? 'ADJ-' . (int)$row->AdjUID : ($row->AdjCategory ?: 'Manual'))
                   : (!empty($row->UniqueNumber) ? $row->UniqueNumber : ($row->TransNumber ?: '—'));
        $date      = ($moduleUID === 118)
                   ? ($row->AdjDate   ? format_datedisplay($row->AdjDate)   : '—')
                   : ($row->TransDate ? format_datedisplay($row->TransDate) : '—');
        return [
            $i,
            $date,
            $row->ItemName      ?? '',
            $row->CategoryName  ?? '',
            $source,
            $ref,
            $row->MovementType  ?? '',
            $row->Quantity      ?? '',
            isset($row->SellingPrice) && $row->SellingPrice !== null ? $row->SellingPrice : ($row->UnitCost ?? ''),
            $row->Remarks       ?? '—',
            $row->CreatedByName ?? '',
        ];
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