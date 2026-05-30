<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Inventory extends MY_Controller {

    public  $pageData      = [];
    private $EndReturnData;
    private $pageModuleUID = 117;

    public function __construct() {
        parent::__construct();
        $this->load->model(['inventory_model', 'dbwrite_model']);
    }

    // ── Main page ───────────────────────────────────────────────────────

    public function index() {

        if (!$this->_loadPageTitle($this->pageModuleUID)) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }

        try {
            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            $GeneralSettings = $this->pageData['JwtData']->GenSettings ?? new stdClass();
            $limit = (int)($GeneralSettings->RowLimit ?? 10);

            $filter = $this->input->post('Filter') ?: [];

            $listData   = $this->inventory_model->getInventoryList($orgUID, $filter, $limit, 0);
            $totalCount = $this->inventory_model->getInventoryCount($orgUID, $filter);
            $stats      = $this->inventory_model->getInventoryStats($orgUID);
            $categories = $this->inventory_model->getCategories($orgUID);

            $this->pageData['ModRowData']    = $this->load->view('inventory/list', [
                'DataLists'    => $listData,
                'SerialNumber' => 0,
                'JwtData'      => $this->pageData['JwtData'],
            ], TRUE);
            $this->pageData['ModPagination'] = $this->globalservice->buildPagePaginationHtml('/inventory/getPageDetails', $totalCount, 1, $limit);
            $this->pageData['ModAllCount']   = $totalCount;
            $this->pageData['Stats']         = $stats;
            $this->pageData['Categories']    = $categories;

            $this->load->view('inventory/view', $this->pageData);

        } catch (Throwable $e) {
            log_message('error', 'Inventory::index â€” ' . $e->getMessage());
            redirect('dashboard', 'refresh');
        }

    }

    // ── AJAX pagination ─────────────────────────────────────────────────

    public function getPageDetails($pageNo = 1) {

        $this->EndReturnData = new stdClass();
        try {
            $pageNo = max(1, (int)$pageNo);
            $limit  = (int)($this->input->post('RowLimit') ?: 10);
            $offset = ($pageNo - 1) * $limit;
            $filter = $this->input->post('Filter') ?: [];

            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;

            $listData   = $this->inventory_model->getInventoryList($orgUID, $filter, $limit, $offset);
            $totalCount = $this->inventory_model->getInventoryCount($orgUID, $filter);

            $rowHtml = $this->load->view('inventory/list', [
                'DataLists'    => $listData,
                'SerialNumber' => $offset,
                'JwtData'      => $this->pageData['JwtData'],
            ], TRUE);

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->RecordHtmlData = $rowHtml;
            $this->EndReturnData->Pagination     = $this->globalservice->buildPagePaginationHtml('/inventory/getPageDetails', $totalCount, $pageNo, $limit);
            $this->EndReturnData->TotalCount     = $totalCount;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    // ── Stock In ───────────────────────────────────────────────────────

    public function stockIn() {

        $this->EndReturnData = new stdClass();
        try {
            $orgUID  = (int) $this->pageData['JwtData']->Org->OrgUID;
            $userUID = (int) $this->pageData['JwtData']->User->UserUID;

            $productUID = (int)   $this->input->post('ProductUID');
            $qty        = (float) $this->input->post('Qty');
            $category   = $this->input->post('AdjCategory') ?: 'Miscellaneous';
            $price      = (float) $this->input->post('Price');
            $priceType  = in_array($this->input->post('PriceType'), ['PurchasePrice', 'SellingPrice'])
                          ? $this->input->post('PriceType') : 'PurchasePrice';
            $stockValue = round($qty * $price, 2);
            $recordDate = $this->input->post('RecordDate') ?: date('Y-m-d');
            $notes      = $this->input->post('Notes') ?: null;

            if ($productUID <= 0) throw new Exception('Invalid product.');
            if ($qty <= 0)        throw new Exception('Quantity must be greater than zero.');

            $this->dbwrite_model->startTransaction();

            $adjData = [
                'OrgUID'      => $orgUID,
                'ProductUID'  => $productUID,
                'ModuleUID'   => 118,
                'AdjType'     => 'IN',
                'Qty'         => $qty,
                'AdjCategory' => $category,
                'Price'       => $price,
                'PriceType'   => $priceType,
                'StockValue'  => $stockValue,
                'RecordDate'  => $recordDate,
                'Notes'       => $notes,
                'CreatedBy'   => $userUID,
                'UpdatedBy'   => $userUID,
            ];

            $insertResp = $this->dbwrite_model->insertData('Products', 'StockAdjustmentTbl', $adjData);
            if ($insertResp->Error) throw new Exception($insertResp->Message);

            $adjUID = (int) $insertResp->ID;
            if ($adjUID <= 0) throw new Exception('Failed to retrieve adjustment ID after insert.');
            $this->dbwrite_model->applyManualStockAdjustment($adjUID, $orgUID, $userUID, $productUID, $qty, $price, 'IN');

            $this->dbwrite_model->commitTransaction();

            // Sync updated AvailableQuantity into the Upstash bulk cache
            $this->cachehelper->upsertProduct($productUID);

            $stats = $this->inventory_model->getInventoryStats($orgUID);
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Stock added successfully.';
            $this->EndReturnData->Stats   = $stats;

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    // Stock Out

    public function stockOut() {

        $this->EndReturnData = new stdClass();
        try {
            $orgUID  = (int) $this->pageData['JwtData']->Org->OrgUID;
            $userUID = (int) $this->pageData['JwtData']->User->UserUID;

            $productUID = (int)   $this->input->post('ProductUID');
            $qty        = (float) $this->input->post('Qty');
            $category   = $this->input->post('AdjCategory') ?: 'Miscellaneous';
            $price      = (float) $this->input->post('Price');
            $priceType  = in_array($this->input->post('PriceType'), ['PurchasePrice', 'SellingPrice'])
                          ? $this->input->post('PriceType') : 'SellingPrice';
            $stockValue = round($qty * $price, 2);
            $recordDate = $this->input->post('RecordDate') ?: date('Y-m-d');
            $notes      = $this->input->post('Notes') ?: null;

            if ($productUID <= 0) throw new Exception('Invalid product.');
            if ($qty <= 0)        throw new Exception('Quantity must be greater than zero.');

            $this->dbwrite_model->startTransaction();

            $adjData = [
                'OrgUID'      => $orgUID,
                'ProductUID'  => $productUID,
                'ModuleUID'   => 118,
                'AdjType'     => 'OUT',
                'Qty'         => $qty,
                'AdjCategory' => $category,
                'Price'       => $price,
                'PriceType'   => $priceType,
                'StockValue'  => $stockValue,
                'RecordDate'  => $recordDate,
                'Notes'       => $notes,
                'CreatedBy'   => $userUID,
                'UpdatedBy'   => $userUID,
            ];

            $insertResp = $this->dbwrite_model->insertData('Products', 'StockAdjustmentTbl', $adjData);
            if ($insertResp->Error) throw new Exception($insertResp->Message);

            $adjUID = (int) $insertResp->ID;
            if ($adjUID <= 0) throw new Exception('Failed to retrieve adjustment ID after insert.');
            $this->dbwrite_model->applyManualStockAdjustment($adjUID, $orgUID, $userUID, $productUID, $qty, $price, 'OUT');

            $this->dbwrite_model->commitTransaction();

            // Sync updated AvailableQuantity into the Upstash bulk cache
            $this->cachehelper->upsertProduct($productUID);

            $stats = $this->inventory_model->getInventoryStats($orgUID);
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Stock removed successfully.';
            $this->EndReturnData->Stats   = $stats;

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    // Update existing manual stock adjustment

    public function updateAdj() {

        $this->EndReturnData = new stdClass();
        try {
            $orgUID  = (int) $this->pageData['JwtData']->Org->OrgUID;
            $userUID = (int) $this->pageData['JwtData']->User->UserUID;

            $adjUID = (int) $this->input->post('AdjUID');
            $notes  = $this->input->post('Notes') ?: null;

            if ($adjUID <= 0) throw new Exception('Invalid adjustment.');

            $existing = $this->inventory_model->getAdjustmentById($adjUID, $orgUID);
            if (!$existing) throw new Exception('Adjustment not found or access denied.');

            $this->dbwrite_model->updateData('Products', 'StockAdjustmentTbl',
                ['Notes' => $notes, 'UpdatedBy' => $userUID],
                ['AdjUID' => $adjUID, 'OrgUID' => $orgUID]
            );

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Remarks updated successfully.';

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    // Update remarks on any stock ledger row

    public function updateLedgerRemarks() {

        $this->EndReturnData = new stdClass();
        try {
            $orgUID    = (int) $this->pageData['JwtData']->Org->OrgUID;
            $userUID   = (int) $this->pageData['JwtData']->User->UserUID;
            $ledgerUID = (int) $this->input->post('LedgerUID');
            $remarks   = $this->input->post('Remarks') ?: null;

            if ($ledgerUID <= 0) throw new Exception('Invalid record.');

            $existing = $this->inventory_model->getLedgerById($ledgerUID, $orgUID);
            if (!$existing) throw new Exception('Record not found or access denied.');

            $this->dbwrite_model->updateData('Products', 'StockLedgerTbl',
                ['Remarks' => $remarks, 'UpdatedBy' => $userUID],
                ['LedgerUID' => $ledgerUID, 'OrgUID' => $orgUID]
            );

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Remarks updated successfully.';

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    // Stock timeline (AJAX)

    public function getTimeline() {

        $this->EndReturnData = new stdClass();
        try {
            $productUID = (int) $this->input->post('ProductUID');
            $orgUID     = (int) $this->pageData['JwtData']->Org->OrgUID;

            if ($productUID <= 0) throw new Exception('Invalid product.');

            $timeline = $this->inventory_model->getStockTimeline($productUID, $orgUID);

            $this->EndReturnData->Error    = FALSE;
            $this->EndReturnData->Timeline = $timeline;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    // ── Global Timeline page ───────────────────────────────────────────────

    public function timelinePage() {

        if (!$this->_loadPageTitle(118)) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }

        try {
            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            $GeneralSettings = $this->pageData['JwtData']->GenSettings ?? new stdClass();
            $limit = (int) ($GeneralSettings->RowLimit ?? 10);

            $defaultFilter = ['DateFrom' => date('Y') . '-01-01', 'DateTo'   => date('Y') . '-12-31'];

            $listData   = $this->inventory_model->getGlobalTimeline($orgUID, $defaultFilter, $limit, 0);
            $totalCount = $this->inventory_model->getGlobalTimelineCount($orgUID, $defaultFilter);

            $this->pageData['ModRowData']    = $this->load->view('inventory/timeline_list', [
                'DataLists' => $listData,
                'SerialNo'  => 0,
                'JwtData'   => $this->pageData['JwtData'],
            ], TRUE);
            $this->pageData['ModPagination'] = $this->globalservice->buildPagePaginationHtml('/inventory/timeline/getPageDetails', $totalCount, 1, $limit);
            $this->pageData['ModAllCount']   = $totalCount;
            $this->pageData['DefaultFilter'] = $defaultFilter;

            $this->load->view('inventory/timeline_view', $this->pageData);

        } catch (Throwable $e) {
            redirect('dashboard', 'refresh');
        }

    }

    // ── Global Timeline AJAX pagination ─────────────────────────────────

    public function getTimelinePageDetails($pageNo = 1) {

        $this->EndReturnData = new stdClass();
        try {
            $pageNo = max(1, (int)$pageNo);
            $limit  = (int)($this->input->post('RowLimit') ?: 10);
            $offset = ($pageNo - 1) * $limit;
            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;

            $filterRaw = $this->input->post('Filter');
            $filter    = $filterRaw ? (json_decode($filterRaw, true) ?: []) : [];

            $listData   = $this->inventory_model->getGlobalTimeline($orgUID, $filter, $limit, $offset);
            $totalCount = $this->inventory_model->getGlobalTimelineCount($orgUID, $filter);

            $rowHtml = $this->load->view('inventory/timeline_list', [
                'DataLists' => $listData,
                'SerialNo'  => $offset,
                'JwtData'   => $this->pageData['JwtData'],
            ], TRUE);

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->RecordHtmlData = $rowHtml;
            $this->EndReturnData->Pagination     = $this->globalservice->buildPagePaginationHtml('/inventory/timeline/getPageDetails', $totalCount, $pageNo, $limit);
            $this->EndReturnData->TotalCount     = $totalCount;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    // ── Product search (for timeline item filter) ─────────────────────────

    public function searchProducts() {

        $this->EndReturnData = new stdClass();
        try {
            $orgUID  = (int) $this->pageData['JwtData']->Org->OrgUID;
            $term    = trim($this->input->post('Term') ?: '');
            $results = $this->inventory_model->searchProducts($orgUID, $term);
            $this->EndReturnData->Error    = FALSE;
            $this->EndReturnData->Products = $results;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    // ── Refresh stats only (AJAX) ─────────────────────────────────────────

    public function getStats() {

        $this->EndReturnData = new stdClass();
        try {
            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Stats = $this->inventory_model->getInventoryStats($orgUID);
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

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
                   ? ($row->AdjDate   ? date('d M Y', strtotime($row->AdjDate))   : '—')
                   : ($row->TransDate ? date('d M Y', strtotime($row->TransDate)) : '—');
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


}
