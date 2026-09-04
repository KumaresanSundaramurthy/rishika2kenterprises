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
    /** @var object|null */
    protected $EndReturnData;
    protected $pageModuleUID = 117;

    public function __construct() {
        parent::__construct();
        $this->load->helper('transaction');
        $this->load->model(['inventory_model', 'dbwrite_model']);
    }

    // â”€â”€ Main page â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

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
            $filter['BranchUID'] = $this->_branchUID();

            $listData   = $this->inventory_model->getInventoryList($orgUID, $filter, $limit, 0);
            $totalCount = $this->inventory_model->getInventoryCount($orgUID, $filter);
            $stats      = $this->inventory_model->getInventoryStats($orgUID);
            $categories = $this->inventory_model->getCategories($orgUID);
            $orgUsers   = $this->_requireCache($this->redisservice->orgKey('org-users'));

            $this->pageData['ModRowData']    = $this->load->view('inventory/list', [
                'DataLists'    => $listData,
                'SerialNumber' => 0,
                'JwtData'      => $this->pageData['JwtData'],
            ], TRUE);
            $this->pageData['ModPagination'] = $this->globalservice->buildPagePaginationHtml('/inventory/getPageDetails', $totalCount, 1, $limit);
            $this->pageData['ModAllCount']   = $totalCount;
            $this->pageData['Stats']         = $stats;
            $this->pageData['Categories']    = $categories;
            $this->pageData['OrgUsers']      = $orgUsers ?? [];
            $this->pageData['ShowUserFilter'] = !empty($orgUsers) && count($orgUsers) > 1;

            $this->load->view('inventory/view', $this->pageData);

        } catch (Throwable $e) {
            notifyError('Inventory::index', $e);
            redirect('dashboard', 'refresh');
        }

    }

    // â”€â”€ AJAX pagination â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function getPageDetails($pageNo = 1) {

        $this->EndReturnData = new stdClass();
        try {
            $pageNo = max(1, (int)$pageNo);
            $limit  = (int)($this->input->post('RowLimit') ?: 10);
            $offset = ($pageNo - 1) * $limit;
            $filter = $this->input->post('Filter') ?: [];
            $filter['BranchUID'] = $this->_branchUID();

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
            notifyError('Inventory::getPageDetails', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    // â”€â”€ Stock In â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function stockIn() {

        $this->EndReturnData = new stdClass();
        try {
            $orgUID    = (int) $this->pageData['JwtData']->Org->OrgUID;
            $userUID   = (int) $this->pageData['JwtData']->User->UserUID;
            $branchUID = $this->_branchUID();

            $productUID = (int)   $this->input->post('ProductUID');
            $variantUID = (int)   ($this->input->post('VariantUID') ?: 0);
            $qty        = (float) $this->input->post('Qty');
            $category   = $this->input->post('AdjCategory') ?: 'Miscellaneous';
            $price      = (float) $this->input->post('Price');
            $priceType  = in_array($this->input->post('PriceType'), ['PurchasePrice', 'SellingPrice'])
                          ? $this->input->post('PriceType') : 'PurchasePrice';
            $stockValue = round($qty * $price, $this->_decimals());
            $recordDate = $this->input->post('RecordDate') ?: date('Y-m-d');
            $notes      = $this->input->post('Notes') ?: null;

            if ($productUID <= 0) throw new Exception('Invalid product.');
            if ($qty <= 0)        throw new Exception('Quantity must be greater than zero.');

            $this->dbwrite_model->startTransaction();

            $adjData = [
                'OrgUID'      => $orgUID,
                'BranchUID'   => $branchUID,
                'ProductUID'  => $productUID,
                'VariantUID'  => $variantUID > 0 ? $variantUID : null,
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
            $this->dbwrite_model->applyManualStockAdjustment($adjUID, $orgUID, $userUID, $productUID, $qty, $price, 'IN', $branchUID, $variantUID);

            $this->dbwrite_model->commitTransaction();

            // Post inventory journal entry (non-fatal â€” stock is already committed)
            try {
                $this->load->library('accountledger');
                $adjFY = (int)date('Y', strtotime($recordDate));
                $this->accountledger->postStockAdjustmentJournal($adjUID, $recordDate, $adjFY, 'IN', $stockValue, $userUID);
            } catch (Exception $ledgerEx) {
            }

            // Sync updated AvailableQuantity into the Upstash bulk cache
            $this->cachehelper->upsertProduct($productUID);

            $stats = $this->inventory_model->getInventoryStats($orgUID);
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Stock added successfully.';
            $this->EndReturnData->Stats   = $stats;

        } catch (Exception $e) {
            notifyError('Inventory::stockIn', $e);
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
            $orgUID    = (int) $this->pageData['JwtData']->Org->OrgUID;
            $userUID   = (int) $this->pageData['JwtData']->User->UserUID;
            $branchUID = $this->_branchUID();

            $productUID = (int)   $this->input->post('ProductUID');
            $variantUID = (int)   ($this->input->post('VariantUID') ?: 0);
            $qty        = (float) $this->input->post('Qty');
            $category   = $this->input->post('AdjCategory') ?: 'Miscellaneous';
            $price      = (float) $this->input->post('Price');
            $priceType  = in_array($this->input->post('PriceType'), ['PurchasePrice', 'SellingPrice'])
                          ? $this->input->post('PriceType') : 'SellingPrice';
            $stockValue = round($qty * $price, $this->_decimals());
            $recordDate = $this->input->post('RecordDate') ?: date('Y-m-d');
            $notes      = $this->input->post('Notes') ?: null;

            if ($productUID <= 0) throw new Exception('Invalid product.');
            if ($qty <= 0)        throw new Exception('Quantity must be greater than zero.');

            $this->dbwrite_model->startTransaction();

            $adjData = [
                'OrgUID'      => $orgUID,
                'BranchUID'   => $branchUID,
                'ProductUID'  => $productUID,
                'VariantUID'  => $variantUID > 0 ? $variantUID : null,
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
            $this->dbwrite_model->applyManualStockAdjustment($adjUID, $orgUID, $userUID, $productUID, $qty, $price, 'OUT', $branchUID, $variantUID);

            $this->dbwrite_model->commitTransaction();

            // Post inventory journal entry (non-fatal â€” stock is already committed)
            try {
                $this->load->library('accountledger');
                $adjFY = (int)date('Y', strtotime($recordDate));
                $this->accountledger->postStockAdjustmentJournal($adjUID, $recordDate, $adjFY, 'OUT', $stockValue, $userUID);
            } catch (Exception $ledgerEx) {
            }

            // Sync updated AvailableQuantity into the Upstash bulk cache
            $this->cachehelper->upsertProduct($productUID);

            $stats = $this->inventory_model->getInventoryStats($orgUID);
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Stock removed successfully.';
            $this->EndReturnData->Stats   = $stats;

        } catch (Exception $e) {
            notifyError('Inventory::stockOut', $e);
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
            notifyError('Inventory::updateAdj', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    // Delete a manual stock adjustment â€” reverses stock movement + accounting journal

    public function deleteAdj() {

        $this->EndReturnData = new stdClass();
        try {
            $orgUID  = (int) $this->pageData['JwtData']->Org->OrgUID;
            $userUID = (int) $this->pageData['JwtData']->User->UserUID;
            $adjUID  = (int) $this->input->post('AdjUID');

            if ($adjUID <= 0) throw new Exception('Invalid adjustment.');

            $existing = $this->inventory_model->getAdjustmentById($adjUID, $orgUID);
            if (!$existing) throw new Exception('Adjustment not found or access denied.');

            $productUID = (int)$existing->ProductUID;

            $this->dbwrite_model->startTransaction();

            // Reverse the stock ledger entry + restore product quantity
            $this->dbwrite_model->reverseStockMovements($adjUID, $orgUID, $userUID);

            // Soft-delete the adjustment record
            $resp = $this->dbwrite_model->updateData(
                'Products', 'StockAdjustmentTbl',
                ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID],
                ['AdjUID' => $adjUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );
            if ($resp->Error) throw new Exception($resp->Message);

            $this->dbwrite_model->commitTransaction();

            // Reverse accounting journal (non-fatal)
            try {
                $this->load->library('accountledger');
                $this->accountledger->reverseJournal('StockAdjustment', $adjUID, $userUID);
            } catch (Exception $ledgerEx) {
            }

            // Sync updated AvailableQuantity into Upstash cache
            $this->cachehelper->upsertProduct($productUID);

            $stats = $this->inventory_model->getInventoryStats($orgUID);
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Stock adjustment deleted successfully.';
            $this->EndReturnData->Stats   = $stats;

        } catch (Exception $e) {
            notifyError('Inventory::deleteAdj', $e);
            $this->dbwrite_model->rollbackTransaction();
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
            notifyError('Inventory::updateLedgerRemarks', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    // Variant stock breakdown for a product (AJAX)

    public function getVariantStock(): void {

        $this->EndReturnData = new stdClass();
        try {
            $orgUID     = (int) $this->pageData['JwtData']->Org->OrgUID;
            $productUID = (int) $this->input->post('ProductUID');

            if ($productUID <= 0) throw new ValidationException('Invalid product.');

            $variants = $this->inventory_model->getVariantStockByProduct($productUID, $orgUID);

            $this->EndReturnData->Error    = FALSE;
            $this->EndReturnData->Variants = $variants;

        } catch (ValidationException $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Inventory::getVariantStock', $e);
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

            $timeline = $this->inventory_model->getStockTimeline($productUID, $orgUID, $this->_branchUID());

            $this->EndReturnData->Error    = FALSE;
            $this->EndReturnData->Timeline = $timeline;

        } catch (Exception $e) {
            notifyError('Inventory::getTimeline', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    // â”€â”€ Global Timeline page â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function timelinePage() {

        if (!$this->_loadPageTitle(118)) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }

        try {
            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            $GeneralSettings = $this->pageData['JwtData']->GenSettings ?? new stdClass();
            $limit = (int) ($GeneralSettings->RowLimit ?? 10);

            $fmt      = $GeneralSettings->ListDateFormat ?? 'd M Y';
            $datePref = $this->getDateFilterPreference('inventory');

            $defaultFilter = ['DateFrom' => $datePref['from'], 'DateTo' => $datePref['to']];

            $listData   = $this->inventory_model->getGlobalTimeline($orgUID, $defaultFilter, $limit, 0);
            $totalCount = $this->inventory_model->getGlobalTimelineCount($orgUID, $defaultFilter);
            $categories = $this->inventory_model->getCategories($orgUID);

            $this->pageData['ModRowData']    = $this->load->view('inventory/timeline_list', [
                'DataLists' => $listData,
                'SerialNo'  => 0,
                'JwtData'   => $this->pageData['JwtData'],
            ], TRUE);
            $this->pageData['ModPagination']       = $this->globalservice->buildPagePaginationHtml('/inventory/timeline/getPageDetails', $totalCount, 1, $limit);
            $this->pageData['ModAllCount']          = $totalCount;
            $this->pageData['DefaultFilter']        = $defaultFilter;
            $this->pageData['Categories']           = $categories;
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

    // â”€â”€ Global Timeline AJAX pagination â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

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
            notifyError('Inventory::getTimelinePageDetails', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    // â”€â”€ Product search (for timeline item filter) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function searchProducts() {

        $this->EndReturnData = new stdClass();
        try {
            $orgUID  = (int) $this->pageData['JwtData']->Org->OrgUID;
            $term    = trim($this->input->post('Term') ?: '');
            $results = $this->inventory_model->searchProducts($orgUID, $term);
            $this->EndReturnData->Error    = FALSE;
            $this->EndReturnData->Products = $results;
        } catch (Exception $e) {
            notifyError('Inventory::searchProducts', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    // â”€â”€ Refresh stats only (AJAX) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function getStats() {

        $this->EndReturnData = new stdClass();
        try {
            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Stats = $this->inventory_model->getInventoryStats($orgUID);
        } catch (Exception $e) {
            notifyError('Inventory::getStats', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    // â”€â”€ Export inventory list â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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

    // â”€â”€ Export stock timeline â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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

    // â”€â”€ Private export helpers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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
                   : (!empty($row->UniqueNumber) ? $row->UniqueNumber : ($row->TransNumber ?: 'â€”'));
        $date      = ($moduleUID === 118)
                   ? ($row->AdjDate   ? format_datedisplay($row->AdjDate)   : 'â€”')
                   : ($row->TransDate ? format_datedisplay($row->TransDate) : 'â€”');
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
            $row->Remarks       ?? 'â€”',
            $row->CreatedByName ?? '',
        ];
    }

    // ── Search serial-tracked products (for Add Serial modal) ────────────────

    public function searchSerialProducts(): void {

        $this->EndReturnData = new stdClass();
        try {
            $orgUID  = (int) $this->pageData['JwtData']->Org->OrgUID;
            $term    = trim($this->input->post('search') ?: '');
            $results = $this->inventory_model->searchSerialProducts($orgUID, $term);
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $results;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Serial Numbers Page ───────────────────────────────────────────────────

    public function serialsPage(): void {

        if (!$this->_loadPageTitle($this->pageModuleUID)) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }

        try {
            $orgUID     = (int) $this->pageData['JwtData']->Org->OrgUID;
            $limit      = (int)($this->pageData['JwtData']->GenSettings->RowLimit ?? 10);
            $_showStats = (bool)($this->pageData['JwtData']->GenSettings->ShowStats ?? 1);

            $validStatuses = ['Available', 'Sold', 'Returned', 'Damaged'];
            $initStatus    = ucfirst(strtolower($this->input->get('status') ?: ''));
            if (!in_array($initStatus, $validStatuses, true)) $initStatus = '';
            $initSearch    = trim($this->input->get('search') ?: '');

            $filter = [];
            if ($initStatus) $filter['Status'] = $initStatus;
            if ($initSearch) $filter['search'] = $initSearch;

            $listData   = $this->inventory_model->getSerialsList($orgUID, $filter, $limit, 0);
            $totalCount = $this->inventory_model->getSerialsCount($orgUID, $filter);
            $stats      = $_showStats ? $this->inventory_model->getSerialsStats($orgUID) : null;

            $this->pageData['ModRowData']    = $this->load->view('inventory/serials_list', [
                'DataLists'    => $listData,
                'SerialNumber' => 0,
                'JwtData'      => $this->pageData['JwtData'],
            ], TRUE);
            $this->pageData['ModPagination'] = $this->globalservice->buildPagePaginationHtml('/inventory/serials/getPageDetails', $totalCount, 1, $limit);
            $this->pageData['ModAllCount']   = $totalCount;
            $this->pageData['Stats']         = $stats;
            $this->pageData['InitStatus']    = $initStatus;
            $this->pageData['InitSearch']    = $initSearch;

            $this->load->view('inventory/serials_view', $this->pageData);

        } catch (Throwable $e) {
            notifyError('Inventory::serialsPage', $e);
            redirect('inventory', 'refresh');
        }
    }

    /**
     * AJAX pagination for serials page.
     * @param int $pageNo
     * @return void
     */
    public function getSerialsPageDetails(int $pageNo = 1): void {

        $this->EndReturnData = new stdClass();
        try {
            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
            $limit  = (int)($this->input->post('RowLimit') ?: 10);
            $offset = (max(1, $pageNo) - 1) * $limit;
            $filter = [];
            $rawFilter = $this->input->post('Filter');
            if (is_array($rawFilter)) $filter = $rawFilter;

            $_showStats = (bool)($this->pageData['JwtData']->GenSettings->ShowStats ?? 1);
            $listData   = $this->inventory_model->getSerialsList($orgUID, $filter, $limit, $offset);
            $totalCount = $this->inventory_model->getSerialsCount($orgUID, $filter);

            $rowHtml = $this->load->view('inventory/serials_list', [
                'DataLists'    => $listData,
                'SerialNumber' => $offset,
                'JwtData'      => $this->pageData['JwtData'],
            ], TRUE);

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->RecordHtmlData = $rowHtml;
            $this->EndReturnData->Pagination     = $this->globalservice->buildPagePaginationHtml('/inventory/serials/getPageDetails', $totalCount, $pageNo, $limit);
            $this->EndReturnData->TotalCount     = $totalCount;
            $this->EndReturnData->Stats          = $_showStats ? $this->inventory_model->getSerialsStats($orgUID) : null;

        } catch (Exception $e) {
            notifyError('Inventory::getSerialsPageDetails', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    /**
     * AJAX — manually add a serial number (opening stock / ad-hoc entry).
     * POST: ProductUID, SerialNumber, Notes
     * @return void
     */
    public function addSerial(): void {

        $this->EndReturnData = new stdClass();
        try {
            $orgUID       = (int) $this->pageData['JwtData']->Org->OrgUID;
            $userUID      = (int) $this->pageData['JwtData']->User->UserUID;
            $productUID   = (int) $this->input->post('ProductUID');
            $serialNumber = trim((string)($this->input->post('SerialNumber') ?? ''));
            $notes        = trim((string)($this->input->post('Notes') ?? ''));

            if ($productUID <= 0)    throw new ValidationException('Please select a product.');
            if ($serialNumber === '') throw new ValidationException('Serial number is required.');

            if ($this->inventory_model->serialExists($orgUID, $productUID, $serialNumber)) {
                throw new ValidationException('Serial number “' . $serialNumber . '” already exists for this product.');
            }

            $this->load->model('dbwrite_model');
            $resp = $this->dbwrite_model->insertData('Transaction', 'ProductSerialsTbl', [
                'OrgUID'       => $orgUID,
                'ProductUID'   => $productUID,
                'SerialNumber' => $serialNumber,
                'Status'       => 'Available',
                'Notes'        => $notes ?: null,
                'IsDeleted'    => 0,
                'CreatedBy'    => $userUID,
                'UpdatedBy'    => $userUID,
            ]);
            if ($resp->Error) throw new Exception($resp->Message);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Serial number added successfully.';

        } catch (ValidationException $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Inventory::addSerial', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    /**
     * AJAX — update a serial's status (mark Damaged / restore Available).
     * POST: SerialUID, Status
     * @return void
     */
    public function updateSerialStatus(): void {

        $this->EndReturnData = new stdClass();
        try {
            $orgUID    = (int) $this->pageData['JwtData']->Org->OrgUID;
            $userUID   = (int) $this->pageData['JwtData']->User->UserUID;
            $serialUID = (int) $this->input->post('SerialUID');
            $status    = trim((string)($this->input->post('Status') ?? ''));

            $allowed = ['Available', 'Damaged'];
            if (!in_array($status, $allowed, true)) {
                throw new ValidationException('Invalid status. Allowed: ' . implode(', ', $allowed));
            }
            if ($serialUID <= 0) throw new ValidationException('SerialUID is required.');

            $this->load->model('dbwrite_model');
            $resp = $this->dbwrite_model->updateData(
                'Transaction', 'ProductSerialsTbl',
                ['Status' => $status, 'UpdatedBy' => $userUID],
                ['SerialUID' => $serialUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );
            if ($resp->Error) throw new Exception($resp->Message);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Status updated.';

        } catch (ValidationException $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Inventory::updateSerialStatus', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

}
