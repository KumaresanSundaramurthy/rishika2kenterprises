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
            $orgUID = (int) $this->pageData['JwtData']->User->OrgUID;
            $GeneralSettings = ($this->redisservice->getUserCache('settings')) ?? new stdClass();
            $this->pageData['JwtData']->GenSettings = $GeneralSettings;
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

            $orgUID = (int) $this->pageData['JwtData']->User->OrgUID;
            $this->pageData['JwtData']->GenSettings = ($this->redisservice->getUserCache('settings')) ?? new stdClass();

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
            $orgUID  = (int) $this->pageData['JwtData']->User->OrgUID;
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

            $adjUID = $insertResp->InsertId;
            $this->dbwrite_model->applyManualStockAdjustment($adjUID, $orgUID, $userUID, $productUID, $qty, $price, 'IN');

            $this->dbwrite_model->commitTransaction();

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
            $orgUID  = (int) $this->pageData['JwtData']->User->OrgUID;
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

            $adjUID = $insertResp->InsertId;
            $this->dbwrite_model->applyManualStockAdjustment($adjUID, $orgUID, $userUID, $productUID, $qty, $price, 'OUT');

            $this->dbwrite_model->commitTransaction();

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

    // Stock timeline (AJAX)

    public function getTimeline() {

        $this->EndReturnData = new stdClass();
        try {
            $productUID = (int) $this->input->post('ProductUID');
            $orgUID     = (int) $this->pageData['JwtData']->User->OrgUID;

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

        if (!$this->_loadPageTitle($this->pageModuleUID)) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }

        try {
            $orgUID = (int) $this->pageData['JwtData']->User->OrgUID;
            $GeneralSettings = ($this->redisservice->getUserCache('settings')) ?? new stdClass();
            $this->pageData['JwtData']->GenSettings = $GeneralSettings;
            $limit = (int)($GeneralSettings->RowLimit ?? 10);

            $defaultFilter = [
                'DateFrom' => date('Y') . '-01-01',
                'DateTo'   => date('Y') . '-12-31',
            ];

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
            log_message('error', 'Inventory::timelinePage â€” ' . $e->getMessage());
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
            $orgUID = (int) $this->pageData['JwtData']->User->OrgUID;
            $this->pageData['JwtData']->GenSettings = ($this->redisservice->getUserCache('settings')) ?? new stdClass();

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
            $orgUID  = (int) $this->pageData['JwtData']->User->OrgUID;
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
            $orgUID = (int) $this->pageData['JwtData']->User->OrgUID;
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

            $orgUID = (int)$this->pageData['JwtData']->User->OrgUID;
            $this->load->model('organisation_model');
            $orgResult = $this->organisation_model->getOrgInfoCached($orgUID);
            $orgInfo   = ($orgResult->Error === FALSE) ? $orgResult->Data : null;

            $data   = $this->inventory_model->getInventoryList($orgUID, $filter, 0, 0);

            $headers = ['#', 'Item Name', 'Category', 'Unit', 'Qty', 'Status', 'Purchase Price', 'Sale Price', 'HSN/SAC', 'Last Updated', 'Updated By'];
            $rows    = [];
            foreach ($data as $i => $row) {
                $rows[] = $this->_mapInventoryRow($i + 1, $row);
            }

            $timezone = $this->pageData['JwtData']->User->Timezone ?? 'UTC';
            $this->_sendExport($type, 'Inventory_Data', 'Inventory', 'Inventory / Stock Report', $headers, $rows, $orgInfo, $timezone);

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

            $orgUID = (int)$this->pageData['JwtData']->User->OrgUID;
            $this->load->model('organisation_model');
            $orgResult = $this->organisation_model->getOrgInfoCached($orgUID);
            $orgInfo   = ($orgResult->Error === FALSE) ? $orgResult->Data : null;

            $data   = $this->inventory_model->getGlobalTimelineExport($orgUID, $filter);

            $moduleLabels = [
                103 => 'Invoice', 105 => 'Purchase', 106 => 'Sales Return',
                107 => 'Credit Note', 108 => 'Purchase Return', 118 => 'Manual Adj.',
            ];

            $headers = ['#', 'Date', 'Item Name', 'Category', 'Source', 'Reference', 'Type', 'Qty', 'Unit Cost', 'Notes', 'Created By'];
            $rows    = [];
            foreach ($data as $i => $row) {
                $rows[] = $this->_mapTimelineRow($i + 1, $row, $moduleLabels);
            }

            $timezone = $this->pageData['JwtData']->User->Timezone ?? 'UTC';
            $this->_sendExport($type, 'Inventory_Timeline', 'Timeline', 'Inventory Stock Timeline', $headers, $rows, $orgInfo, $timezone);

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
        $ref       = ($moduleUID === 118) ? ($row->AdjCategory ?: 'Manual') : ($row->TransNumber ?: 'â€”');
        $date      = ($moduleUID === 118)
                   ? ($row->AdjDate   ? date('d M Y', strtotime($row->AdjDate))   : 'â€”')
                   : ($row->TransDate ? date('d M Y', strtotime($row->TransDate)) : 'â€”');
        return [
            $i,
            $date,
            $row->ItemName      ?? '',
            $row->CategoryName  ?? '',
            $source,
            $ref,
            $row->MovementType  ?? '',
            $row->Quantity      ?? '',
            $row->UnitCost      ?? '',
            $row->AdjNotes      ?? 'â€”',
            $row->CreatedByName ?? '',
        ];
    }

    private function _sendExport($type, $fileName, $sheetName, $previewName, $headers, $rows, $org = null, $timezone = 'UTC') {
        if ($type === 'Print') {
            $html = $this->_buildPrintHtml($previewName, $headers, $rows, $org, $timezone);
            header('Content-Type: application/json');
            echo json_encode(['Error' => false, 'HtmlData' => $html]);
            exit;
        }

        if (ob_get_length()) ob_end_clean();

        // Build org header lines (used by CSV and spreadsheet formats)
        $str = function($v) { $s = trim((string)$v); return ($v !== null && $s !== '' && $s !== 'null') ? $s : ''; };
        $orgName    = $org ? ($str($org->BrandName ?? '') ?: $str($org->Name ?? '')) : '';
        $addrParts  = [];
        if ($org) {
            if ($str($org->Line1 ?? ''))   $addrParts[] = $str($org->Line1 ?? '');
            if ($str($org->Line2 ?? ''))   $addrParts[] = $str($org->Line2 ?? '');
            $ct = $str($org->CityText ?? '');
            $st = $str($org->StateText ?? '');
            $cityState = trim($ct . ($st ? ' - ' . $st : ''));
            if ($cityState)                $addrParts[] = $cityState;
            if ($str($org->Pincode ?? '')) $addrParts[] = 'PIN: ' . $str($org->Pincode ?? '');
        }
        $addrLine   = implode(', ', $addrParts);
        $contactLine = implode('  |  ', array_filter([
            !empty($org->MobileNumber) ? 'Ph: ' . $org->MobileNumber  : '',
            !empty($org->EmailAddress) ? 'Email: ' . $org->EmailAddress : '',
            !empty($org->GSTIN)        ? 'GSTIN: ' . $org->GSTIN       : '',
        ]));

        if ($type === 'CSV') {
            header('Content-Type: text/csv; charset=utf-8');
            header("Content-Disposition: attachment; filename=\"{$fileName}.csv\"");
            header('Pragma: no-cache');
            $f = fopen('php://output', 'w');
            fputs($f, chr(0xEF) . chr(0xBB) . chr(0xBF));
            if ($orgName)    fputcsv($f, [$orgName]);
            if ($addrLine)   fputcsv($f, [$addrLine]);
            if ($contactLine) fputcsv($f, [$contactLine]);
            $genDate = (new DateTime('now', new DateTimeZone($timezone ?: 'UTC')))->format('d M Y, h:i A');
            fputcsv($f, [$previewName . ' â€” Generated: ' . $genDate]);
            fputcsv($f, []);
            fputcsv($f, $headers);
            foreach ($rows as $row) { fputcsv($f, $row); }
            fclose($f);
            exit;
        }

        // Excel / PDF via PhpSpreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle($sheetName);

        $colCount = count($headers);
        $lastCol  = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount);

        // ── Company header rows ───────────────────────────────────────────────
        $r = 1;

        // Logo (if accessible local path or valid URL readable by GD)
        $logoOffset = 0;
        if ($org && !empty($org->Logo)) {
            try {
                $logoSrc = $org->Logo;
                // Attempt to fetch remote logo into a temp file
                $tmpLogo = tempnam(sys_get_temp_dir(), 'inv_logo_');
                $imgData = @file_get_contents($logoSrc);
                if ($imgData) {
                    file_put_contents($tmpLogo, $imgData);
                    $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                    $drawing->setPath($tmpLogo);
                    $drawing->setHeight(48);
                    $drawing->setCoordinates('A1');
                    $drawing->setOffsetX(4);
                    $drawing->setOffsetY(4);
                    $drawing->setWorksheet($sheet);
                    $logoOffset = 4; // rows occupied by the logo block
                    $r = $logoOffset + 1;
                }
            } catch (\Exception $e) { /* skip logo silently */ }
        }

        // Org name (bold, 14pt)
        $sheet->setCellValue("A{$r}", $orgName);
        $sheet->getStyle("A{$r}")->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells("A{$r}:{$lastCol}{$r}");
        $r++;

        if ($addrLine) {
            $sheet->setCellValue("A{$r}", $addrLine);
            $sheet->mergeCells("A{$r}:{$lastCol}{$r}");
            $r++;
        }
        if ($contactLine) {
            $sheet->setCellValue("A{$r}", $contactLine);
            $sheet->mergeCells("A{$r}:{$lastCol}{$r}");
            $r++;
        }

        // Report title + date
        $genDate = (new DateTime('now', new DateTimeZone($timezone ?: 'UTC')))->format('d M Y, h:i A');
        $sheet->setCellValue("A{$r}", $previewName . '   |   Generated: ' . $genDate);
        $sheet->getStyle("A{$r}")->getFont()->setBold(true)->setColor(
            (new \PhpOffice\PhpSpreadsheet\Style\Color())->setARGB('FF334155')
        );
        $sheet->mergeCells("A{$r}:{$lastCol}{$r}");
        $r++;

        // Blank separator
        $r++;

        // ── Column headers ───────────────────────────────────────────────
        $headerRow = $r;
        $sheet->fromArray($headers, null, "A{$headerRow}");
        $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->getFont()->setBold(true);
        $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE2E8F0');
        $r++;

        // ── Data rows ───────────────────────────────────────────────
        foreach ($rows as $row) {
            $sheet->fromArray($row, null, "A{$r}");
            $r++;
        }

        for ($c = 1; $c <= $colCount; $c++) {
            $sheet->getColumnDimensionByColumn($c)->setAutoSize(true);
        }

        if ($type === 'Excel') {
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename=\"{$fileName}.xlsx\"");
            header('Pragma: no-cache');
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
        }

        if ($type === 'Pdf') {
            $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
            $sheet->getPageSetup()->setFitToWidth(1);
            header('Content-Type: application/pdf');
            header("Content-Disposition: attachment; filename=\"{$fileName}.pdf\"");
            \PhpOffice\PhpSpreadsheet\IOFactory::registerWriter('Pdf', \PhpOffice\PhpSpreadsheet\Writer\Pdf\Dompdf::class);
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Pdf');
            $writer->save('php://output');
            exit;
        }
    }

    private function _buildPrintHtml($previewName, $headers, $rows, $org = null, $timezone = 'UTC') {
        $str = function($v) { $s = trim((string)$v); return ($v !== null && $s !== '' && $s !== 'null') ? $s : ''; };
        $orgName    = $org ? ($str($org->BrandName ?? '') ?: $str($org->Name ?? '')) : '';
        $addrParts  = [];
        if ($org) {
            if ($str($org->Line1 ?? ''))   $addrParts[] = htmlspecialchars($str($org->Line1 ?? ''));
            if ($str($org->Line2 ?? ''))   $addrParts[] = htmlspecialchars($str($org->Line2 ?? ''));
            $ct = $str($org->CityText ?? '');
            $st = $str($org->StateText ?? '');
            $cs = trim($ct . ($st ? ' - ' . $st : ''));
            if ($cs)                       $addrParts[] = htmlspecialchars($cs);
            if ($str($org->Pincode ?? '')) $addrParts[] = 'PIN: ' . htmlspecialchars($str($org->Pincode ?? ''));
        }
        $logoHtml = ($org && !empty($org->Logo))
            ? '<img src="' . htmlspecialchars($org->Logo) . '" style="height:52px;object-fit:contain;" alt="Logo">'
            : '';
        $contactItems = array_filter([
            !empty($org->MobileNumber) ? '<span>&#128222; ' . htmlspecialchars($org->MobileNumber)  . '</span>' : '',
            !empty($org->EmailAddress) ? '<span>&#9993; ' . htmlspecialchars($org->EmailAddress) . '</span>' : '',
            !empty($org->GSTIN)        ? '<span>GSTIN: <strong>' . htmlspecialchars($org->GSTIN) . '</strong></span>' : '',
        ]);
        $date = (new DateTime('now', new DateTimeZone($timezone ?: 'UTC')))->format('d M Y, h:i A');

        $th = '';
        foreach ($headers as $h) {
            $th .= '<th>' . htmlspecialchars($h) . '</th>';
        }
        $tb = '';
        foreach ($rows as $row) {
            $tb .= '<tr>';
            foreach ($row as $cell) {
                $tb .= '<td>' . htmlspecialchars((string)$cell) . '</td>';
            }
            $tb .= '</tr>';
        }

        return '<!DOCTYPE html><html><head><meta charset="utf-8">
<title>' . htmlspecialchars($orgName ?: $previewName) . '</title>
<style>
*{box-sizing:border-box;}
body{font-family:Arial,sans-serif;font-size:11px;margin:0;padding:14px;}
.org-header{display:flex;align-items:flex-start;gap:14px;border-bottom:2px solid #334155;padding-bottom:10px;margin-bottom:10px;}
.org-logo{flex-shrink:0;}
.org-details{flex:1;}
.org-name{font-size:16px;font-weight:700;color:#1e293b;margin:0 0 3px;}
.org-addr{color:#475569;font-size:10px;margin:0 0 3px;}
.org-contact{display:flex;flex-wrap:wrap;gap:10px;color:#475569;font-size:10px;}
.report-title{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;}
.report-title h4{margin:0;font-size:13px;color:#1e293b;}
.report-title .gen-date{font-size:10px;color:#94a3b8;}
table{width:100%;border-collapse:collapse;}
th{background:#e2e8f0;padding:6px 8px;text-align:left;border:1px solid #cbd5e1;font-size:11px;color:#1e293b;}
td{padding:5px 8px;border:1px solid #e2e8f0;font-size:10.5px;color:#334155;}
tr:nth-child(even) td{background:#f8fafc;}
@media print{body{padding:5mm;}@page{margin:8mm;}}
</style>
</head><body>
<div class="org-header">
  <div class="org-logo">' . $logoHtml . '</div>
  <div class="org-details">
    <p class="org-name">' . htmlspecialchars($orgName) . '</p>
    <p class="org-addr">' . implode(', ', $addrParts) . '</p>
    <div class="org-contact">' . implode('', $contactItems) . '</div>
  </div>
</div>
<div class="report-title">
  <h4>' . htmlspecialchars($previewName) . '</h4>
  <span class="gen-date">Generated: ' . $date . '</span>
</div>
<table><thead><tr>' . $th . '</tr></thead><tbody>' . $tb . '</tbody></table>
</body></html>';
    }

}
