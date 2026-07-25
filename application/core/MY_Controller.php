<?php defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Controller extends CI_Controller {

    public    $pageData      = [];
    protected $pageModuleUID = 0;

    // ── Bank / Cash ledger entry (available in every controller) ─────────────
    // Writes a single CR or DR row to AccountLedgerTbl for a given bank account.
    // Non-fatal — logs and returns on failure so callers are never blocked.
    // Skip entirely when $bankAccountUID is 0/null (cash payment with no account linked).
    protected function _writeBankLedgerEntry(
        int    $orgUID,
        ?int   $bankAccountUID,
        string $entryType,      // 'CR' or 'DR'
        float  $amount,
        string $sourceType,     // 'Invoice' | 'Purchase' | 'Expense' | 'IndirectIncome' | 'FundTransfer' | 'Payment' | ...
        ?int   $sourceUID,
        ?int   $moduleUID,
        ?string $referenceNo,
        string $narration,
        string $entryDate,
        int    $userUID
    ): void {
        if (!$bankAccountUID || $amount <= 0) return;
        try {
            $this->load->model('dbwrite_model');
            $this->dbwrite_model->insertData('Transaction', 'AccountLedgerTbl', [
                'OrgUID'         => $orgUID,
                'BankAccountUID' => $bankAccountUID,
                'EntryDate'      => $entryDate,
                'EntryType'      => $entryType,
                'Amount'         => round($amount, 4),
                'SourceType'     => $sourceType,
                'SourceUID'      => $sourceUID,
                'ModuleUID'      => $moduleUID,
                'ReferenceNo'    => $referenceNo ?: null,
                'Narration'      => $narration,
                'IsActive'       => 1,
                'IsDeleted'      => 0,
                'CreatedBy'      => $userUID,
                'UpdatedBy'      => $userUID,
            ]);
        } catch (Exception $e) {
            log_message('error', 'Bank ledger entry failed [' . $sourceType . '#' . $sourceUID . ']: ' . $e->getMessage());
        }
    }

    // ── Product cache sync helpers (available in every controller) ───────────

    // Call after saveStockMovements — syncs AvailableQty from ProductStockTbl into Upstash for each item.
    protected function _syncProductCacheFromItems(array $items): void {
        $seen = [];
        foreach ($items as $item) {
            $uid = (int)($item['id'] ?? 0);
            if ($uid > 0 && !isset($seen[$uid])) {
                $seen[$uid] = true;
                $this->cachehelper->upsertProduct($uid);
            }
        }
    }

    // Call after reverseStockMovements — looks up affected products from TransProductsTbl and syncs each.
    protected function _syncProductCacheByTransUID(int $transUID): void {
        try {
            // DB query lives in MY_Model — controller only orchestrates
            $this->load->model('transactions_model');
            $uids = $this->transactions_model->getProductUIDsByTransUID($transUID);
            foreach ($uids as $uid) {
                $this->cachehelper->upsertProduct($uid);
            }
        } catch (Throwable $e) {
            log_message('error', '_syncProductCacheByTransUID failed for TransUID=' . $transUID . ': ' . $e->getMessage());
        }
    }

    // ── Balance recalc helpers (available in every controller) ──────────────

    protected function _recalcVendorBalance(int $orgUID, int $vendorUID, int $userUID): void {
        try {
            $this->load->library('vendorbalance');
            $this->vendorbalance->recalcAndSync($orgUID, $vendorUID, $userUID);
        } catch (Exception $e) {
            log_message('error', 'Vendor balance recalc failed for VendorUID=' . $vendorUID . ': ' . $e->getMessage());
        }
    }

    protected function _recalcCustomerBalance(int $orgUID, int $customerUID, int $userUID): void {
        try {
            $this->load->library('customerbalance');
            $this->customerbalance->recalcAndSync($orgUID, $customerUID, $userUID);
        } catch (Exception $e) {
            log_message('error', 'Customer balance recalc failed for CustomerUID=' . $customerUID . ': ' . $e->getMessage());
        }
    }

    // ── JWT shorthand accessors (available in every controller) ──────────────

    protected function _orgUID(): int        { return (int)($this->pageData['JwtData']->Org->OrgUID              ?? 0); }
    protected function _userUID(): int       { return (int)($this->pageData['JwtData']->User->UserUID             ?? 0); }
    protected function _branchUID(): int     { return (int)($this->pageData['JwtData']->Org->BranchUID            ?? 1); }
    protected function _rowLimit(): int      { return (int)($this->pageData['JwtData']->GenSettings->RowLimit      ?? 25); }
    protected function _dateFormat(): string { return       $this->pageData['JwtData']->GenSettings->ListDateFormat ?? 'd M Y'; }
    protected function _currency(): string   { return       $this->pageData['JwtData']->GenSettings->CurrenySymbol  ?? '₹'; }
    protected function _decimals(): int      { return (int)($this->pageData['JwtData']->GenSettings->DecimalPoints  ?? 2); }
    protected function _uiLang(): string     { return       $this->pageData['JwtData']->User->UILanguage            ?? 'en'; }

    /**
     * Looks up the module record from the Redis module cache and sets:
     *   $this->pageData['PageTitle']  — DisplayName (falls back to Name)
     *   $this->pageData['PageIcon']   — Icon class string
     *
     * @param int|null $moduleUID  Pass a UID to search by ID; omit to search by controller name.
     * @return bool  true = found, false = module not configured in ModuleTbl
     */
    protected function _loadPageTitle($moduleUID = null) {
        $modules = (array)($this->redisservice->getUserCache('modules') ?? []);

        // Cache miss — don't block; title stays empty
        if (empty($modules)) {
            $this->pageData['PageTitle'] = '';
            $this->pageData['PageIcon']  = '';
            return true;
        }

        $found = null;
        if ($moduleUID !== null) {
            foreach ($modules as $m) {
                if ((int)$m->ModuleUID === (int)$moduleUID) { $found = $m; break; }
            }
        } else {
            $controllerName = strtolower($this->router->fetch_class());
            foreach ($modules as $m) {
                if ($m->ControllerName === $controllerName) { $found = $m; break; }
            }
        }

        if (!$found) return false;

        $this->pageData['PageTitle']       = !empty($found->DisplayName) ? $found->DisplayName : ($found->Name ?? '');
        $this->pageData['PageIcon']        = $found->Icon      ?? '';
        $this->pageData['PageIconBg']      = $found->IconBg    ?? '';
        $this->pageData['PageIconColor']   = $found->IconColor ?? '';
        $this->pageData['PageDescription'] = $found->Description ?? '';

        // Load attachment config for all slots — 1-year Upstash cache, negligible overhead
        $this->pageData['AttachCfg'] = $this->_loadAttachCfg();

        return true;
    }

    // ── Dispatch address helper ───────────────────────────────────────────────

    /**
     * Loads active org SHIPPING addresses from Redis cache (Shipping type only).
     * Falls back to DB if cache is cold, then re-caches for 24 h.
     * Sets pageData['DispatchAddresses'] (array) and pageData['DispatchAddress'] (first/default).
     */
    protected function _getDispatchAddresses($orgUID) {
        $cacheKey  = $this->redisservice->orgKey('org_dispatch_addresses_shipping');
        $addresses = $this->redisservice->getCache($cacheKey)->Value ?? null;
        if (!is_array($addresses)) {
            $this->load->model('organisation_model');
            $addresses = $this->organisation_model->getAllOrgDispatchAddresses((int) $orgUID);
            $this->redisservice->setCache($cacheKey, $addresses ?: [], 86400);
        }
        $this->pageData['DispatchAddresses'] = $addresses ?: [];
        $this->pageData['DispatchAddress']   = !empty($addresses) ? $addresses[0] : null;
    }

    // ── Shared export helpers (used by Inventory, Customers, etc.) ────────────

    protected function _sendExport($type, $fileName, $sheetName, $previewName, $headers, $rows, $org = null, $timezone = 'UTC', $colWidths = null) {
        if ($type === 'Print') {
            $html = $this->_buildPrintHtml($previewName, $headers, $rows, $org, $timezone, $colWidths);
            header('Content-Type: application/json');
            echo json_encode(['Error' => false, 'HtmlData' => $html]);
            exit;
        }

        if ($type === 'Pdf') {
            if (ob_get_length()) ob_end_clean();
            $html = $this->_buildPrintHtml($previewName, $headers, $rows, $org, $timezone, $colWidths);
            header('Content-Type: application/pdf');
            header("Content-Disposition: attachment; filename=\"{$fileName}.pdf\"");
            $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true]);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            $dompdf->stream("{$fileName}.pdf", ['Attachment' => true]);
            exit;
        }

        if (ob_get_length()) ob_end_clean();

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
            fputcsv($f, [$previewName . ' — Generated: ' . $genDate]);
            fputcsv($f, []);
            fputcsv($f, $headers);
            foreach ($rows as $row) { fputcsv($f, $row); }
            fclose($f);
            exit;
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle($sheetName);

        $colCount = count($headers);
        $lastCol  = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount);

        $r = 1;
        $logoOffset = 0;
        if ($org && !empty($org->Logo)) {
            try {
                $tmpLogo = tempnam(sys_get_temp_dir(), 'exp_logo_');
                $imgData = @file_get_contents($org->Logo);
                if ($imgData) {
                    file_put_contents($tmpLogo, $imgData);
                    $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                    $drawing->setPath($tmpLogo);
                    $drawing->setHeight(48);
                    $drawing->setCoordinates('A1');
                    $drawing->setOffsetX(4);
                    $drawing->setOffsetY(4);
                    $drawing->setWorksheet($sheet);
                    $logoOffset = 4;
                    $r = $logoOffset + 1;
                }
            } catch (\Exception $e) { /* skip logo */ }
        }

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

        $genDate = (new DateTime('now', new DateTimeZone($timezone ?: 'UTC')))->format('d M Y, h:i A');
        $sheet->setCellValue("A{$r}", $previewName . '   |   Generated: ' . $genDate);
        $sheet->getStyle("A{$r}")->getFont()->setBold(true)->setColor(
            (new \PhpOffice\PhpSpreadsheet\Style\Color())->setARGB('FF334155')
        );
        $sheet->mergeCells("A{$r}:{$lastCol}{$r}");
        $r++;
        $r++;

        $headerRow = $r;
        $sheet->fromArray($headers, null, "A{$headerRow}");
        $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->getFont()->setBold(true);
        $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE2E8F0');
        $r++;

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
    }

    protected function _buildPrintHtml($previewName, $headers, $rows, $org = null, $timezone = 'UTC', $colWidths = null) {
        $str = function($v) { $s = trim((string)$v); return ($v !== null && $s !== '' && $s !== 'null') ? $s : ''; };
        $orgName   = $org ? ($str($org->BrandName ?? '') ?: $str($org->Name ?? '')) : '';
        $addrParts = [];
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
            ? '<img src="' . htmlspecialchars($org->Logo) . '" style="height:48px;object-fit:contain;" alt="Logo">'
            : '';
        $contactParts = array_filter([
            !empty($org->MobileNumber) ? 'Ph: ' . htmlspecialchars($org->MobileNumber)    : '',
            !empty($org->EmailAddress) ? 'Email: ' . htmlspecialchars($org->EmailAddress) : '',
            !empty($org->GSTIN)        ? 'GSTIN: ' . htmlspecialchars($org->GSTIN)        : '',
        ]);
        $date = (new DateTime('now', new DateTimeZone($timezone ?: 'UTC')))->format('d M Y, h:i A');

        $colGroup    = '';
        $tableLayout = 'auto';
        if (!empty($colWidths)) {
            $tableLayout = 'fixed';
            $colGroup    = '<colgroup>';
            foreach ($colWidths as $w) {
                $colGroup .= '<col style="width:' . htmlspecialchars($w) . '">';
            }
            $colGroup .= '</colgroup>';
        }

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
                *{box-sizing:border-box;margin:0;padding:0;}
                @page{size:A4 landscape;margin:10mm 15mm;}
                body{font-family:Arial,Helvetica,sans-serif;font-size:9px;}
                .org-header{text-align:center;border-bottom:2px solid #334155;padding-bottom:8px;margin-bottom:8px;}
                .org-logo{margin-bottom:4px;}
                .org-name{font-size:14px;font-weight:700;color:#1e293b;margin-bottom:2px;}
                .org-addr{color:#475569;font-size:8px;margin-bottom:2px;}
                .org-contact{color:#475569;font-size:8px;}
                .report-bar{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;}
                .report-bar h4{margin:0;font-size:11px;font-weight:700;color:#1e293b;}
                .report-bar .gen-date{font-size:8px;color:#64748b;}
                table{width:100%;border-collapse:collapse;table-layout:' . $tableLayout . ';}
                th{background:#e2e8f0;padding:5px 6px;text-align:left;border:1px solid #cbd5e1;font-size:8.5px;color:#1e293b;font-weight:700;white-space:nowrap;overflow:hidden;}
                td{padding:4px 6px;border:1px solid #e2e8f0;font-size:8px;color:#334155;word-break:break-word;overflow-wrap:break-word;}
                tr:nth-child(even) td{background:#f8fafc;}
                @media print{@page{size:A4 landscape;margin:10mm 15mm;}}
                </style>
                </head><body>
                <div class="org-header">
                ' . ($logoHtml ? '<div class="org-logo">' . $logoHtml . '</div>' : '') . '
                <div class="org-name">' . htmlspecialchars($orgName) . '</div>
                ' . (!empty($addrParts) ? '<div class="org-addr">' . implode(', ', $addrParts) . '</div>' : '') . '
                ' . (!empty($contactParts) ? '<div class="org-contact">' . implode(' &nbsp;|&nbsp; ', $contactParts) . '</div>' : '') . '
                </div>
                <div class="report-bar">
                <h4>' . htmlspecialchars($previewName) . '</h4>
                <span class="gen-date">Generated: ' . $date . '</span>
                </div>
                <table>' . $colGroup . '<thead><tr>' . $th . '</tr></thead><tbody>' . $tb . '</tbody></table>
                </body></html>';
    }


    // ── Upstash client config ────────────────────────────────────────────────
    // Sets UpstashReadUrl, UpstashReadToken, CustomerCacheKey, VendorCacheKey
    // in pageData so every transaction list view has the JS vars it needs.
    // Prefers the read-only token; falls back to the main token if not set.
    protected function _loadUpstashConfig() {
        $this->pageData['UpstashReadUrl']   = rtrim(getenv('UPSTASH_REDIS_REST_URL') ?: '', '/');
        $this->pageData['UpstashReadToken'] = getenv('UPSTASH_REDIS_REST_READONLY_TOKEN')
                                            ?: getenv('UPSTASH_REDIS_REST_TOKEN')
                                            ?: '';
        $this->pageData['CustomerCacheKey'] = $this->redisservice->orgKey('customers');
        $this->pageData['VendorCacheKey']   = $this->redisservice->orgKey('vendors');
    }

    // ── Cache guard ─────────────────────────────────────────────────────────
    // Returns the cached value if present, otherwise renders the cache-refresh
    // error page and returns null so the caller can do: if (!$v) return;
    protected function _requireCache($cacheKey) {
        $value = $this->redisservice->getCache($cacheKey)->Value;
        if ($value !== null && (!is_array($value) || !empty($value))) {
            return $value;
        }
        $this->load->view('common/cache_refresh', $this->pageData);
        return null;
    }

    // ── Unified attachment save ──────────────────────────────────────────────
    // $uid        — TransUID for standard transactions, SourceUID for expenses/income
    // $sourceType — null for standard transactions; 'Expense' or 'IndirectIncome' for those pages
    //
    // Callers (unchanged):
    //   $this->_saveAttachments($transUID);                     ← 7 transaction controllers
    //   $this->_saveAttachments($expenseUID, 'Expense');        ← Expenses
    //   $this->_saveAttachments($incomeUID,  'IndirectIncome'); ← IndirectIncome
    protected function _saveAttachments($uid, $sourceType = null) {
        $files = $_FILES['AttachFiles'] ?? $_FILES['Attachments'] ?? null;
        if (empty($files) || empty($files['name'][0])) return;

        $userUID = $this->pageData['JwtData']->User->UserUID;
        $orgUID  = $this->pageData['JwtData']->Org->OrgUID;

        $this->load->library('fileupload');
        $this->load->model('dbwrite_model');

        // Determine storage folder
        if ($sourceType === 'Expense') {
            $folder = 'expenses';
        } elseif ($sourceType === 'IndirectIncome') {
            $folder = 'indirectincome';
        } else {
            static $folderMap = [
                101 => 'quotations',      102 => 'salesorders',   103 => 'invoices',
                104 => 'purchaseorders',  105 => 'purchases',     106 => 'salesreturns',
                108 => 'purchasereturns', 112 => 'deliverychallans', 113 => 'proformainvoices',
            ];
            $folder = $folderMap[(int)($this->pageModuleUID ?? 0)] ?? 'transactions';
        }

        $this->load->model('transactions_model');
        $sortOffset = $this->transactions_model->getMaxAttachmentSortOrder((int)$uid, (int)$orgUID, $sourceType) + 1;

        $count = count($files['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK || empty($files['name'][$i])) continue;

            $origName    = basename($files['name'][$i]);
            $safeName    = time() . '_' . $i . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $origName);
            $storagePath = $folder . '/' . $uid . '/' . $safeName;

            $uploadResult = $this->fileupload->fileUpload('file', $storagePath, $files['tmp_name'][$i]);
            if ($uploadResult->Error) continue;

            $filePath = '/' . ltrim($uploadResult->Path, '/');

            if ($sourceType !== null) {
                // Expenses or IndirectIncome
                $this->dbwrite_model->insertData('Transaction', 'ExpenseIncomeAttachmentsTbl', [
                    'OrgUID'     => $orgUID,
                    'SourceUID'  => $uid,
                    'SourceType' => $sourceType,
                    'FileName'   => $origName,
                    'FilePath'   => $filePath,
                    'FileType'   => $files['type'][$i],
                    'FileSize'   => $files['size'][$i],
                    'SortOrder'  => $sortOffset + $i,
                    'IsActive'   => 1,
                    'IsDeleted'  => 0,
                    'CreatedBy'  => $userUID,
                ]);
            } else {
                // Standard transaction
                $this->dbwrite_model->insertData('Transaction', 'TransAttachmentsTbl', [
                    'OrgUID'    => $orgUID,
                    'TransUID'  => $uid,
                    'ModuleUID' => (int)($this->pageModuleUID ?? 0),
                    'FileName'  => $origName,
                    'FilePath'  => $filePath,
                    'FileType'  => $files['type'][$i],
                    'FileSize'  => $files['size'][$i],
                    'SortOrder' => $sortOffset + $i,
                    'IsActive'  => 1,
                    'IsDeleted' => 0,
                    'CreatedBy' => $userUID,
                ]);
            }
        }
    }

    // ── Transaction number helpers ───────────────────────────────────────────

    // Builds the formatted UniqueNumber from prefix config + transaction number + date.
    // e.g. EST/26-27/001, INV-2026-2027-0042
    protected function buildUniqueNumber($prefix, $transNumber, $transDate) {
        $sep   = $prefix->Separator ?? '-';
        $parts = [strtoupper($prefix->Name)];
        if (!empty($prefix->IncludeShortName) && !empty($prefix->ShortName)) {
            $parts[] = strtoupper($prefix->ShortName);
        }
        if (!empty($prefix->IncludeFiscalYear)) {
            $txMonth = (int) date('m', strtotime($transDate));
            $txYear  = (int) date('Y', strtotime($transDate));
            $fyStart = $txMonth >= 4 ? $txYear : $txYear - 1;
            $parts[] = ($prefix->FiscalYearFormat ?? 'SHORT') === 'LONG'
                ? $fyStart . '-' . ($fyStart + 1)
                : str_pad($fyStart % 100, 2, '0', STR_PAD_LEFT) . '-' . str_pad(($fyStart + 1) % 100, 2, '0', STR_PAD_LEFT);
        }
        $padding = (int)($prefix->NumberPadding ?? 1);
        $parts[] = $padding > 1 ? str_pad($transNumber, $padding, '0', STR_PAD_LEFT) : (string)$transNumber;
        return [implode($sep, $parts), $transNumber];
    }

    // Inserts into TransactionsTbl and retries up to 5 times on duplicate number conflict.
    // $headerData is passed by reference so TransNumber/UniqueNumber stay in sync with caller.
    // On 6th failure returns a user-friendly message instead of a raw DB error.
    protected function _insertTransactionWithRetry(&$headerData, $prefixUID, $orgUID, $prefix, $transDate) {
        $this->load->model('dbwrite_model');
        $this->load->model('transactions_model');
        for ($attempt = 0; $attempt <= 5; $attempt++) {
            $result = $this->dbwrite_model->insertData('Transaction', 'TransactionsTbl', $headerData);
            if (!$result->Error) {
                return $result;
            }
            // Non-duplicate error — return immediately, let caller handle
            if (stripos($result->Message ?? '', 'Duplicate entry') === false) {
                return $result;
            }
            // Draft or no prefix — cannot retry, return as-is
            if ($prefix === null) return $result;
            // All retries exhausted — user-friendly message
            if ($attempt >= 5) {
                $result->Message = 'Could not assign a transaction number. Please try again.';
                return $result;
            }
            // Fetch next free number, rebuild UniqueNumber and retry
            $transNumber = $this->transactions_model->getNextTransactionNumber(
                $prefixUID, $orgUID, (int)($this->pageModuleUID ?? 0)
            );
            if ($transNumber === -1) {
                $result->Message = 'This prefix series has reached its maximum (2,147,483,647). Please create a new prefix to continue.';
                return $result;
            }
            list($uniqueNumber) = $this->buildUniqueNumber($prefix, $transNumber, $transDate);
            $headerData['TransNumber']  = $transNumber;
            $headerData['UniqueNumber'] = $uniqueNumber;
        }
    }

    protected function _softDeleteAttachments($removedJson, $sourceType = null) {
        if (empty($removedJson)) return;
        // Accept both JSON array (new zone format) and comma-separated string (old format)
        $trimmed = trim($removedJson);
        if ($trimmed[0] === '[') {
            $uids = json_decode($trimmed, true);
        } else {
            $uids = array_filter(array_map('intval', explode(',', $trimmed)));
        }
        if (empty($uids) || !is_array($uids)) return;
        $orgUID  = $this->pageData['JwtData']->Org->OrgUID;
        $userUID = $this->pageData['JwtData']->User->UserUID;
        $this->load->model('dbwrite_model');
        foreach ($uids as $attachUID) {
            $attachUID = (int) $attachUID;
            if ($attachUID <= 0) continue;
            if ($sourceType !== null) {
                $this->dbwrite_model->updateData(
                    'Transaction', 'ExpenseIncomeAttachmentsTbl',
                    ['IsDeleted' => 1, 'IsActive' => 0],
                    ['AttachUID' => $attachUID, 'OrgUID' => $orgUID]
                );
            } else {
                $this->dbwrite_model->updateData(
                    'Transaction', 'TransAttachmentsTbl',
                    ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID],
                    ['AttachUID' => $attachUID, 'OrgUID' => $orgUID]
                );
            }
        }
    }

    protected function _savePaymentAttachments($paymentUID) {
        $files = $_FILES['PaymentFiles'] ?? null;
        if (empty($files) || empty($files['name'][0])) return;

        $userUID = $this->pageData['JwtData']->User->UserUID;
        $orgUID  = $this->pageData['JwtData']->Org->OrgUID;

        $this->load->library('fileupload');
        $this->load->model('dbwrite_model');

        $allowed = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
        $count   = min(count($files['name']), 3);

        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK || empty($files['name'][$i])) continue;
            if ($files['size'][$i] > 3 * 1024 * 1024) continue;
            if (!in_array($files['type'][$i], $allowed)) continue;

            $origName    = basename($files['name'][$i]);
            $safeName    = time() . '_' . $i . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $origName);
            $storagePath = 'payments/' . $paymentUID . '/' . $safeName;

            $uploadResult = $this->fileupload->fileUpload('file', $storagePath, $files['tmp_name'][$i]);
            if ($uploadResult->Error) continue;

            $this->dbwrite_model->insertData('Transaction', 'PaymentAttachmentsTbl', [
                'OrgUID'     => $orgUID,
                'PaymentUID' => $paymentUID,
                'FileName'   => $origName,
                'FilePath'   => '/' . ltrim($uploadResult->Path, '/'),
                'FileType'   => $files['type'][$i],
                'FileSize'   => $files['size'][$i],
                'SortOrder'  => $i,
                'IsActive'   => 1,
                'IsDeleted'  => 0,
                'CreatedBy'  => $userUID,
            ]);
        }
    }

    // ── Attachment config loader ──────────────────────────────────────────────

    /**
     * Loads attachment config for all slots from Global.ModuleAttachmentCfgTbl.
     * Result is cached in Upstash under 'global:attach_cfg' with a 1-year TTL.
     * To force reload after a DB change: DEL the key from Upstash.
     * Returns array keyed by SlotKey.
     */
    protected function _loadAttachCfg(): array {
        // AttachCfg is loaded at login and stored in the JWT/Redis session.
        // It's already in JwtData — no extra DB or cache query needed.
        $cfg = $this->pageData['JwtData']->AttachCfg ?? null;
        if (!empty($cfg)) return (array)$cfg;
        return [];
    }

    // ── Date filter preference helper ─────────────────────────────────────────

    /**
     * Reads the saved date filter preference for a page from user_preferences,
     * falling back to $default ('this_month') if none is saved.
     *
     * Returns: ['range'=>string, 'from'=>string, 'to'=>string, 'label'=>string]
     */
    protected function getDateFilterPreference($pageKey, $default = 'this_month') {

        $jwtData   = $this->pageData['JwtData'] ?? null;
        $orgUID    = (int)($jwtData->Org->OrgUID    ?? 0);
        $branchUID = (int)($jwtData->Org->BranchUID ?? 0);
        $userUID   = (int)($jwtData->User->UserUID  ?? 0);

        $range = $default;
        if ($orgUID && $userUID) {
            $this->load->model('UserPreferences_model');
            $saved = $this->UserPreferences_model->getUserPreference(
                $orgUID, $branchUID, $userUID, 'df_' . $pageKey
            );
            if ($saved !== null && $saved !== '') $range = $saved;
        }

        $result = $this->_buildDateRange($range);
        $this->pageData['SavedDateFrom'] = $result['from'];
        $this->pageData['SavedDateTo']   = $result['to'];

        $fmt = $jwtData->GenSettings->ListDateFormat ?? 'd M Y';
        $this->pageData['SavedDateFromDisplay'] = $result['from'] ? date($fmt, strtotime($result['from'])) : '';
        $this->pageData['SavedDateToDisplay']   = $result['to']   ? date($fmt, strtotime($result['to']))   : '';

        return $result;
        
    }

    protected function _buildDateRange(string $range): array {
        $today = date('Y-m-d');
        $y     = (int)date('Y');
        $m     = (int)date('m');
        $from  = '';
        $to    = $today;
        $label = 'This Month';

        // Handle custom range stored as 'custom|YYYY-MM-DD|YYYY-MM-DD'
        if (strncmp($range, 'custom|', 7) === 0) {
            $parts = explode('|', $range);
            if (count($parts) === 3 && $parts[1] && $parts[2]) {
                return [
                    'range' => $range,
                    'from'  => $parts[1],
                    'to'    => $parts[2],
                    'label' => 'Custom',
                ];
            }
        }

        switch ($range) {
            case 'today':
                $from = $to = $today; $label = 'Today'; break;
            case 'yesterday':
                $d = date('Y-m-d', strtotime('-1 day'));
                $from = $to = $d; $label = 'Yesterday'; break;
            case 'this_week':
                $dow = (int)date('w');
                $from = date('Y-m-d', strtotime('-' . $dow . ' days')); $label = 'This Week'; break;
            case 'last_week':
                $dow  = (int)date('w');
                $from = date('Y-m-d', strtotime('-' . ($dow + 7) . ' days'));
                $to   = date('Y-m-d', strtotime('-' . ($dow + 1) . ' days'));
                $label = 'Last Week'; break;
            case 'last_7_days':
                $from = date('Y-m-d', strtotime('-6 days')); $label = 'Last 7 Days'; break;
            case 'this_month':
            default:
                $from = date('Y-m-01'); $label = 'This Month'; $range = 'this_month'; break;
            case 'previous_month':
                $from  = date('Y-m-01', strtotime('first day of last month'));
                $to    = date('Y-m-t',  strtotime('last month'));
                $label = 'Previous Month'; break;
            case 'last_30_days':
                $from = date('Y-m-d', strtotime('-29 days')); $label = 'Last 30 Days'; break;
            case 'this_quarter':
                $qStart = (int)(floor(($m - 1) / 3) * 3) + 1;
                $from   = $y . '-' . str_pad($qStart, 2, '0', STR_PAD_LEFT) . '-01';
                $label  = 'This Quarter'; break;
            case 'previous_quarter':
                $pqStart = (int)(floor(($m - 1) / 3) * 3) - 3;
                $pqYear  = $y;
                if ($pqStart < 1) { $pqStart += 12; $pqYear--; }
                $from   = $pqYear . '-' . str_pad($pqStart, 2, '0', STR_PAD_LEFT) . '-01';
                $to     = date('Y-m-t', strtotime($from . ' +2 months'));
                $label  = 'Previous Quarter'; break;
            case 'this_year':
                $from = $y . '-01-01'; $label = 'This Year'; break;
            case 'last_year':
                $from = ($y - 1) . '-01-01'; $to = ($y - 1) . '-12-31'; $label = 'Last Year'; break;
            case 'last_365_days':
                $from = date('Y-m-d', strtotime('-364 days')); $label = 'Last 365 Days'; break;
            case 'current_fy':
                $fyYear = ($m >= 4) ? $y : $y - 1;
                $from   = $fyYear . '-04-01';
                $to     = ($fyYear + 1) . '-03-31';
                $label  = 'Current FY'; break;
            case 'previous_fy':
                $fyYear = (($m >= 4) ? $y : $y - 1) - 1;
                $from   = $fyYear . '-04-01';
                $to     = ($fyYear + 1) . '-03-31';
                $label  = 'Previous FY'; break;
            case 'custom':
                // Stored as 'custom' without dates — treat as no range
                $from = ''; $to = ''; $label = 'This Month'; $range = 'this_month'; break;
            case '':
            case 'all':
                $from = ''; $to = ''; $label = 'All Dates'; $range = ''; break;
        }

        return ['range' => $range, 'from' => $from, 'to' => $to, 'label' => $label];
    }

    /**
     * Upstash read-through for org's additional charges list.
     * Returns the raw array of charge objects (same shape as getAdditionalChargesList()->Data).
     * @param int $orgUID
     * @return array
     */
    protected function _getAdditionalChargesForOrg(int $orgUID, bool $showByDefault = false): array {
        $key    = $this->redisservice->orgKey('additional-charges');
        $cached = $this->upstashservice->get($key);
        if ($cached !== null && $cached !== false) {
            $rows = is_array($cached) ? $cached : [];
        } else {
            $this->load->model('organisation_model');
            $result = $this->organisation_model->getAdditionalChargesList($orgUID);
            $rows   = ($result->Error === FALSE) ? ($result->Data ?? []) : [];
            if (!empty($rows)) {
                $this->upstashservice->set($key, $rows, 0);
            }
        }
        // Compute next sort order from the full (unfiltered) list and expose it to views.
        $maxSort = 0;
        foreach ($rows as $r) {
            $s = (int)(is_array($r) ? ($r['SortOrder'] ?? 0) : ($r->SortOrder ?? 0));
            if ($s > $maxSort) { $maxSort = $s; }
        }
        if (is_array($this->pageData)) {
            $this->pageData['AcNextSortOrder'] = $maxSort + 1;
        }
        if ($showByDefault) {
            return array_values(array_filter($rows, fn($c) => (int)(is_array($c) ? ($c['ShowByDefault'] ?? 0) : ($c->ShowByDefault ?? 0)) === 1));
        }
        return $rows;
    }

    protected function _getTaxList(): array {
        $cacheKey = $this->redisservice->globalKey('tax-details');
        $cached   = $this->upstashservice->get($cacheKey);
        if ($cached !== null && $cached !== false) {
            return array_map(fn($r) => is_array($r) ? (object)$r : $r, (array)$cached);
        }
        $this->load->model('global_model');
        $result = $this->global_model->getTaxDetailsInfo();
        $data   = ($result->Error === FALSE) ? (array)($result->Data ?? []) : [];
        if (!empty($data)) {
            $this->upstashservice->set($cacheKey, $data, (int)getenv('ONEYEAR_EXPIRE_SECS'));
        }
        return $data;
    }

    protected function _annotatePRListWithCancelDeps(array &$rows, int $orgUID): void
    {
        if (empty($rows)) return;
        $transUIDs = array_map(fn($r) => (int)$r->TransUID, $rows);
        foreach ($rows as $r) { $r->CancelCashRefunded = 0; }
        $this->load->model('transactions_model');
        $deps = $this->transactions_model->getPRCancelDepsMap($transUIDs);
        foreach ($rows as $r) {
            $r->CancelCashRefunded = $deps['cashMap'][(int)$r->TransUID] ?? 0;
        }
    }

    protected function _annotateSRListWithCancelDeps(array &$rows, int $orgUID): void
    {
        if (empty($rows)) return;
        $transUIDs     = array_map(fn($r) => (int)$r->TransUID, $rows);
        $uniqueNumbers = array_values(array_filter(array_map(fn($r) => $r->UniqueNumber ?? null, $rows)));
        foreach ($rows as $r) {
            $r->CancelCashRefunded  = 0;
            $r->CancelCreditApplied = 0;
        }
        $this->load->model('transactions_model');
        $deps = $this->transactions_model->getSRCancelDepsMap($transUIDs, $uniqueNumbers);
        foreach ($rows as $r) {
            $r->CancelCashRefunded  = $deps['cashMap'][(int)$r->TransUID]        ?? 0;
            $r->CancelCreditApplied = $deps['creditMap'][$r->UniqueNumber ?? ''] ?? 0;
        }
    }

    // ── Private helpers shared by index-page loader and AJAX pagination ─────

    /**
     * Queries the transaction list, runs any module annotation, renders the
     * list partial, and returns the results in one call.
     * SerialNumber == $offset so page-1 (offset=0) and subsequent AJAX pages
     * both work correctly with the same helper.
     */
    private function _fetchAndRenderTransList(int $limit, int $offset, array $filter, string $viewPath, array $extraViewData = []): array
    {
        $this->load->model('transactions_model');
        $data  = $this->transactions_model->getTransactionPageList($limit, $offset, $this->pageModuleUID, $filter, 0);
        $count = $this->transactions_model->getTransactionCount($this->pageModuleUID, $filter);
        $orgUID = (int)($this->pageData['JwtData']->Org->OrgUID);
        if ($this->pageModuleUID === 108) { $this->_annotatePRListWithCancelDeps($data, $orgUID); }
        if ($this->pageModuleUID === 106) { $this->_annotateSRListWithCancelDeps($data, $orgUID); }
        $html = $this->load->view($viewPath,
            array_merge(['DataLists' => $data, 'SerialNumber' => $offset, 'JwtData' => $this->pageData['JwtData']], $extraViewData),
            true
        );
        return ['data' => $data, 'count' => $count, 'html' => $html];
    }

    /**
     * Returns summary stats for the current module, respecting the
     * ShowTransactionStats user setting. Always returns an array.
     */
    private function _computeTransSummaryStats(int $orgUID, array $filter = []): array
    {
        if (!($this->pageData['JwtData']->TransSettings->ShowTransactionStats ?? 1)) { return []; }
        $this->load->model('transactions_model');
        return $this->transactions_model->getTransactionSummaryStats($this->pageModuleUID, $orgUID, $filter);
    }

    // ── Transaction page load helpers ────────────────────────────────────

    /**
     * Populates $this->pageData for the initial transaction list page render.
     * Controller calls $this->load->view() after this returns (Option B).
     *
     * Required keys: datePrefKey, tabSlugMap, listViewPath, paginationUrl
     * Optional keys: skipListForTabs (array), listViewExtraData (array)
     */
    protected function _loadTransactionIndexPage(array $config): void
    {
        $GeneralSettings = $this->pageData['JwtData']->GenSettings ?? new stdClass();
        $limit           = (int)($GeneralSettings->RowLimit ?? 10);
        $orgUID          = (int)($this->pageData['JwtData']->Org->OrgUID);

        $datePref    = $this->getDateFilterPreference($config['datePrefKey']);
        $statsFilter = $datePref['from'] ? ['DateFrom' => $datePref['from'], 'DateTo' => $datePref['to']] : [];

        $tabSlugMap = $config['tabSlugMap'];
        $tabSlug    = strtolower(trim($this->input->get('tab') ?: 'all'));
        $initTab    = $tabSlugMap[$tabSlug] ?? 'All';
        $initSearch = trim($this->input->get('search') ?: '');
        $initPage   = max(1, (int)($this->input->get('page') ?: 1));
        $initFilter = $statsFilter;
        if ($initTab !== 'All') { $initFilter['Status'] = $initTab; }
        if ($initSearch !== '') { $initFilter['Name']   = $initSearch; }

        $skipTabs   = $config['skipListForTabs'] ?? [];
        $listExtras = $config['listViewExtraData'] ?? [];

        if (in_array($initTab, $skipTabs, true)) {
            $allDataCount = 0;
            $initPage     = 1;
            $listHtml     = $this->load->view($config['listViewPath'],
                array_merge(['DataLists' => [], 'SerialNumber' => 0, 'JwtData' => $this->pageData['JwtData']], $listExtras), true);
        } else {
            $initOffset   = ($initPage - 1) * $limit;
            $res          = $this->_fetchAndRenderTransList($limit, $initOffset, $initFilter, $config['listViewPath'], $listExtras);
            $allDataCount = $res['count'];
            $listHtml     = $res['html'];
            // Clamp page to valid range if it's beyond the last page
            $maxPage  = $allDataCount > 0 ? (int)ceil($allDataCount / $limit) : 1;
            if ($initPage > $maxPage) { $initPage = $maxPage; }
        }

        $this->pageData['SavedDateRange'] = $datePref['range'];
        $this->pageData['SavedDateLabel'] = $datePref['label'];
        $this->pageData['ModRowData']     = $listHtml;
        $this->pageData['ModPagination']  = $this->globalservice->buildPagePaginationHtml($config['paginationUrl'], $allDataCount, $initPage, $limit);
        $this->pageData['ModAllCount']    = $allDataCount;
        $this->pageData['InitPage']       = $initPage;
        $this->pageData['SummaryStats']   = $this->_computeTransSummaryStats($orgUID, $statsFilter);

        // PaymentTypes + BankAccounts: Invoices(103), Purchases(105), SR(106), PR(108)
        if (in_array($this->pageModuleUID, [103, 105, 106, 108], true)) {
            $this->load->model('transactions_model');
            $this->pageData['PaymentTypes'] = $this->transactions_model->getPaymentTypesList();
            $this->pageData['BankAccounts'] = $this->transactions_model->getOrgBankAccounts($orgUID);
        }

        $this->_loadUpstashConfig();

        // OrgUsers: all except DC(112) and Proforma(113)
        if (in_array($this->pageModuleUID, [101, 102, 103, 104, 105, 106, 108], true)) {
            $this->load->model('users_model');
            $this->pageData['OrgUsers'] = $this->users_model->getOrgUsersForCache($orgUID);
        }

        $this->pageData['InitTab']    = $initTab;
        $this->pageData['InitSearch'] = $initSearch;
    }

    /**
     * Builds and returns the paginated result object for a transaction AJAX request.
     * Controller is responsible for try/catch and sending the JSON response.
     *
     * Required keys: pageNo, listViewPath, paginationUrl
     * Optional keys: listViewExtraData (array), includeSummaryStats (bool)
     */
    protected function _buildTransactionPageDetailsResult(array $config): stdClass
    {
        $pageNo = max(1, (int)($config['pageNo'] ?? 1));
        $limit  = (int)$this->input->post('RowLimit') ?: 10;
        $offset = ($pageNo - 1) * $limit;
        $filter = $this->input->post('Filter') ?: [];

        $res = $this->_fetchAndRenderTransList($limit, $offset, $filter, $config['listViewPath'], $config['listViewExtraData'] ?? []);

        $result                 = new stdClass();
        $result->Error          = FALSE;
        $result->RecordHtmlData = $res['html'];
        $result->Pagination     = $this->globalservice->buildPagePaginationHtml($config['paginationUrl'], $res['count'], $pageNo, $limit);
        $result->TotalCount     = $res['count'];

        if ($config['includeSummaryStats'] ?? false) {
            $result->SummaryStats = $this->_computeTransSummaryStats((int)$this->pageData['JwtData']->Org->OrgUID, $filter);
        }

        return $result;
    }

    /**
     * Builds the TransactionsTbl insert array from module config + computed amounts.
     *
     * $cfg keys: TransType, PartyType, PartyUID, DocTypePostKey, DocTypeDefault (optional),
     *            DispatchPostKey (empty string = always NULL), InitialStatus,
     *            hasPaidAmount (bool), hasBalanceAmount (bool), hasIsFullyPaid (bool)
     *
     * $amounts keys: moduleUID, prefixUID, uniqueNumber, transNumber, transDate, financialYear,
     *                totalQty, totalItems, subTotal, discountAmount, taxAmount, cgstAmount,
     *                sgstAmount, igstAmount, additionalChargesTotal, roundOff,
     *                globalDiscPercent, extraDiscount, netAmount
     *
     * @param array $cfg
     * @param array $amounts
     * @param array $post
     * @param int $orgUID
     * @param int $userUID
     * @return array
     */
    protected function _buildTransHeader(array $cfg, array $amounts, array $post, int $orgUID, int $userUID): array {
        $isDraft   = getPostValue($post, 'action') === 'draft';
        $subTotal  = (float) $amounts['subTotal'];
        $extraDisc = (float) $amounts['extraDiscount'];
        $dispatch  = !empty($cfg['DispatchPostKey'])
            ? (getPostValue($post, $cfg['DispatchPostKey']) ?: NULL)
            : NULL;
        $data = [
            'OrgUID'            => $orgUID,
            'ModuleUID'         => (int) $amounts['moduleUID'],
            'PrefixUID'         => $amounts['prefixUID'],
            'UniqueNumber'      => $amounts['uniqueNumber'],
            'TransType'         => $cfg['TransType'],
            'TransNumber'       => $amounts['transNumber'],
            'PartyType'         => $cfg['PartyType'],
            'PartyUID'          => (int) $cfg['PartyUID'],
            'TransDate'         => $amounts['transDate'],
            'TransYear'         => (int) $amounts['financialYear'],
            'DocType'           => getPostValue($post, $cfg['DocTypePostKey']) ?: ($cfg['DocTypeDefault'] ?? NULL),
            'DispatchFrom'      => $dispatch,
            'TotalQuantity'     => (float) $amounts['totalQty'],
            'TotalItems'        => (int) $amounts['totalItems'],
            'GrossAmount'       => $subTotal + (float) $amounts['discountAmount'],
            'SubTotal'          => $subTotal,
            'TaxableAmount'     => $subTotal,
            'DiscountAmount'    => (float) $amounts['discountAmount'],
            'AdditionalCharges' => (float) $amounts['additionalChargesTotal'],
            'TaxAmount'         => (float) $amounts['taxAmount'],
            'CgstAmount'        => (float) $amounts['cgstAmount'],
            'SgstAmount'        => (float) $amounts['sgstAmount'],
            'IgstAmount'        => (float) $amounts['igstAmount'],
            'RoundOff'          => (float) $amounts['roundOff'],
            'GlobalDiscPercent' => (float) $amounts['globalDiscPercent'],
            'ExtraDiscApplied'  => $extraDisc > 0 ? 1 : 0,
            'ExtraDiscAmount'   => $extraDisc,
            'ExtraDiscType'     => getPostValue($post, 'extDiscountType') ?: NULL,
            'NetAmount'         => (float) $amounts['netAmount'],
            'DocStatus'         => $isDraft ? 'Draft' : $cfg['InitialStatus'],
            'TransToken'        => generate_uuid4(),
            'IsActive'          => 1,
            'IsDeleted'         => 0,
            'CreatedBy'         => $userUID,
            'UpdatedBy'         => $userUID,
        ];
        if (!empty($cfg['hasPaidAmount'])) {
            $data['PaidAmount']    = 0;
            $data['BalanceAmount'] = (float) $amounts['netAmount'];
        } elseif (!empty($cfg['hasBalanceAmount'])) {
            $data['BalanceAmount'] = NULL;
        }
        if (!empty($cfg['hasIsFullyPaid'])) {
            $data['IsFullyPaid'] = 0;
        }
        return $data;
    }

    /**
     * Builds the TransDetailTbl insert array.
     *
     * $cfg keys: PartyType, PartyUID, ValidityDatePostKey ('' = always NULL),
     *            ValidityDaysPostKey (optional), ReferencePostKey (default: 'referenceDetails'),
     *            SupplierInvoiceNoPostKey (optional), extraDetailFields (array col=>postKey, optional)
     *
     * $amounts keys: financialYear, cgstAmount, sgstAmount, igstAmount
     *
     * @param array $cfg
     * @param array $amounts
     * @param array $post
     * @param int $transUID
     * @return array
     */
    protected function _buildTransDetail(array $cfg, array $amounts, array $post, int $transUID): array {
        $igst         = (float) $amounts['igstAmount'];
        $cgst         = (float) $amounts['cgstAmount'];
        $sgst         = (float) $amounts['sgstAmount'];
        $isInterState = $igst > 0 ? 1 : ($cgst > 0 || $sgst > 0 ? 0 : NULL);

        $isForeignCustomer = NULL;
        if (($cfg['PartyType'] ?? '') === 'C' && !empty($cfg['PartyUID'])) {
            $this->load->model('transactions_model');
            $_cc               = $this->transactions_model->getCustomerCountryCode((int) $cfg['PartyUID']);
            $isForeignCustomer = $_cc !== NULL ? ($_cc === 'IN' ? 0 : 1) : NULL;
        }

        $validityDays = NULL;
        if (!empty($cfg['ValidityDaysPostKey'])) {
            $vd           = (int) getPostValue($post, $cfg['ValidityDaysPostKey'], 'Array', 0);
            $validityDays = $vd > 0 ? $vd : NULL;
        }

        $validityDateKey = $cfg['ValidityDatePostKey'] ?? '';
        $validityDate    = $validityDateKey !== '' ? (getPostValue($post, $validityDateKey) ?: NULL) : NULL;
        $refKey          = !empty($cfg['ReferencePostKey']) ? $cfg['ReferencePostKey'] : 'referenceDetails';

        $data = [
            'FinancialYear'     => (int) $amounts['financialYear'],
            'TransUID'          => (int) $transUID,
            'ValidityDays'      => $validityDays,
            'ValidityDate'      => $validityDate,
            'Reference'         => getPostValue($post, $refKey) ?: NULL,
            'Notes'             => getPostValue($post, 'transNotes') ?: NULL,
            'TermsConditions'   => getPostValue($post, 'transTermsCond') ?: NULL,
            'SignatureUID'      => (int) getPostValue($post, 'SignatureUID') ?: NULL,
            'PlaceOfSupplyCode' => getPostValue($post, 'placeOfSupplyCode') ?: NULL,
            'PlaceOfSupplyName' => getPostValue($post, 'placeOfSupplyName') ?: NULL,
            'IsInterState'      => $isInterState,
            'IsForeignCustomer' => $isForeignCustomer,
            'PriceListUID'      => (int) getPostValue($post, 'PriceListUID') ?: NULL,
            'PriceListData'     => getPostValue($post, 'PriceListData') ?: NULL,
        ];
        if (!empty($cfg['SupplierInvoiceNoPostKey'])) {
            $data['SupplierInvoiceNo'] = getPostValue($post, $cfg['SupplierInvoiceNoPostKey']) ?: NULL;
        }
        if (!empty($cfg['extraDetailFields']) && is_array($cfg['extraDetailFields'])) {
            foreach ($cfg['extraDetailFields'] as $col => $postKey) {
                $data[$col] = getPostValue($post, $postKey) ?: NULL;
            }
        }
        return $data;
    }

    /**
     * Builds the TransactionsTbl UPDATE array.
     * Identical field set to _buildTransHeader() except insert-only columns are excluded:
     * PrefixUID, TransNumber, UniqueNumber, TransToken, IsActive, IsDeleted, CreatedBy.
     * Callers must merge PrefixUID/TransNumber/UniqueNumber separately when promoting Draft → Pending.
     *
     * $cfg keys: TransType, PartyType, PartyUID, DocTypePostKey, DocTypeDefault (optional),
     *            DispatchPostKey (empty = always NULL), InitialStatus
     *
     * $amounts keys: moduleUID, isDraft, transDate, financialYear, totalQty, totalItems,
     *                subTotal, discountAmount, taxAmount, cgstAmount, sgstAmount, igstAmount,
     *                additionalChargesTotal, roundOff, globalDiscPercent, extraDiscount, netAmount
     *
     * @param array $cfg
     * @param array $amounts
     * @param array $post
     * @param int $orgUID
     * @param int $userUID
     * @return array
     */
    protected function _buildTransUpdateHeader(array $cfg, array $amounts, array $post, int $orgUID, int $userUID): array {
        $subTotal  = (float) $amounts['subTotal'];
        $extraDisc = (float) $amounts['extraDiscount'];
        $dispatch  = !empty($cfg['DispatchPostKey'])
            ? (getPostValue($post, $cfg['DispatchPostKey']) ?: NULL)
            : NULL;
        return [
            'OrgUID'            => $orgUID,
            'ModuleUID'         => (int) $amounts['moduleUID'],
            'PartyType'         => $cfg['PartyType'],
            'PartyUID'          => (int) $cfg['PartyUID'],
            'TransDate'         => $amounts['transDate'],
            'TransYear'         => (int) $amounts['financialYear'],
            'TransType'         => $cfg['TransType'],
            'DocType'           => getPostValue($post, $cfg['DocTypePostKey']) ?: ($cfg['DocTypeDefault'] ?? NULL),
            'DispatchFrom'      => $dispatch,
            'TotalQuantity'     => (float) $amounts['totalQty'],
            'TotalItems'        => (int) $amounts['totalItems'],
            'GrossAmount'       => $subTotal + (float) $amounts['discountAmount'],
            'SubTotal'          => $subTotal,
            'TaxableAmount'     => $subTotal,
            'DiscountAmount'    => (float) $amounts['discountAmount'],
            'AdditionalCharges' => (float) $amounts['additionalChargesTotal'],
            'TaxAmount'         => (float) $amounts['taxAmount'],
            'CgstAmount'        => (float) $amounts['cgstAmount'],
            'SgstAmount'        => (float) $amounts['sgstAmount'],
            'IgstAmount'        => (float) $amounts['igstAmount'],
            'RoundOff'          => (float) $amounts['roundOff'],
            'GlobalDiscPercent' => (float) $amounts['globalDiscPercent'],
            'ExtraDiscApplied'  => $extraDisc > 0 ? 1 : 0,
            'ExtraDiscAmount'   => $extraDisc,
            'ExtraDiscType'     => getPostValue($post, 'extDiscountType') ?: NULL,
            'NetAmount'         => (float) $amounts['netAmount'],
            'DocStatus'         => $amounts['isDraft'] ? 'Draft' : $cfg['InitialStatus'],
            'UpdatedBy'         => $userUID,
            'PdfPath'           => NULL,
        ];
    }

    /**
     * Inserts all line items into TransProductsTbl. Identical logic across all 9 transaction modules.
     *
     * @param int $transUID
     * @param int $financialYear
     * @param int $orgUID
     * @param int $userUID
     * @param array $items
     * @param int $seqOffset
     * @return void
     */
    protected function _insertTransItems(int $transUID, int $financialYear, int $orgUID, int $userUID, array $items, int $seqOffset = 0): void {
        $this->load->model('dbwrite_model');
        $rows = [];
        foreach ($items as $seq => $item) {
            $productUID = isset($item['id'])       ? (int)   $item['id']       : 0;
            $qty        = isset($item['quantity'])  ? (float) $item['quantity']  : 0;
            $unitPrice  = isset($item['unitPrice']) ? (float) $item['unitPrice'] : 0;
            if ($productUID <= 0 || $qty <= 0) continue;
            $rows[] = [
                'OrgUID'            => $orgUID,
                'FinancialYear'     => $financialYear,
                'TransUID'          => $transUID,
                'ItemSequence'      => $seqOffset + $seq + 1,
                'ProductUID'        => $productUID,
                'ProductName'       => substr(strip_tags($item['itemName'] ?? ''), 0, 100),
                'Description'       => !empty($item['description'])  ? substr($item['description'],  0, 500) : NULL,
                'PartNumber'        => !empty($item['partNumber'])   ? substr($item['partNumber'],   0, 50)  : NULL,
                'CategoryUID'       => !empty($item['categoryUID'])  ? (int) $item['categoryUID']             : NULL,
                'CategoryName'      => !empty($item['categoryName']) ? substr($item['categoryName'], 0, 100) : NULL,
                'StorageUID'        => isset($item['storageUID'])    ? (int) $item['storageUID']               : NULL,
                'Quantity'          => $qty,
                'PrimaryUnitName'   => isset($item['primaryUnit'])    ? substr($item['primaryUnit'],    0, 20) : NULL,
                'TaxDetailsUID'     => isset($item['taxDetailsUID'])  ? (int) $item['taxDetailsUID']            : 1,
                'TaxPercentage'     => (float) ($item['taxPercent']    ?? 0),
                'CGST'              => (float) ($item['cgstPercent']   ?? 0),
                'SGST'              => (float) ($item['sgstPercent']   ?? 0),
                'IGST'              => (float) ($item['igstPercent']   ?? 0),
                'DiscountTypeUID'   => isset($item['discountTypeUID']) ? (int) $item['discountTypeUID']  : NULL,
                'Discount'          => (float) ($item['discount']        ?? 0),
                'UnitPrice'         => $unitPrice,
                'SellingPrice'      => (float) ($item['sellingPrice']    ?? $unitPrice),
                'PurchasePrice'     => (float) ($item['purchasePrice']   ?? 0),
                'MRP'               => (float) ($item['mrp']             ?? 0),
                'DistributionMode'  => !empty($item['isComposite']) ? ($item['distributionMode'] ?? null) : null,
                'TaxableAmount'     => (float) ($item['line_total']      ?? 0),
                'CgstAmount'        => (float) ($item['cgstAmount']      ?? 0),
                'SgstAmount'        => (float) ($item['sgstAmount']      ?? 0),
                'IgstAmount'        => (float) ($item['igstAmount']      ?? 0),
                'TaxAmount'         => (float) ($item['taxAmount']       ?? 0),
                'DiscountAmount'    => (float) ($item['discount_amount']  ?? 0),
                'NetAmount'         => (float) ($item['net_total']        ?? 0),
                'QuantityConverted' => 0,
                'IsActive'          => 1,
                'IsDeleted'         => 0,
                'CreatedBy'         => $userUID,
                'UpdatedBy'         => $userUID,
            ];
        }
        if (empty($rows)) return;
        $batchResp = $this->dbwrite_model->insertBatchInTransaction('Transaction', 'TransProductsTbl', $rows);
        if ($batchResp->Error) throw new Exception($batchResp->Message);
    }

    /**
     * Smart item diff for updateXxx(): soft-delete removed items, update existing in place,
     * batch-insert genuinely new ones. Must be called inside an open DB transaction.
     * SourceTransProdUID is read from $item['sourceTransProdUID'] when present (Sales Returns);
     * it is stored as NULL for all other modules.
     *
     * @param int   $transUID
     * @param array $items         Decoded items array from POST
     * @param int   $orgUID
     * @param int   $financialYear
     * @param int   $userUID
     * @return void
     */
    protected function _updateTransItems(int $transUID, array $items, int $orgUID, int $financialYear, int $userUID): void {
        $this->load->model(['transactions_model', 'dbwrite_model']);

        $existingItems     = $this->transactions_model->getTransactionItems($transUID, $orgUID);
        $existingByProduct = [];
        foreach ($existingItems as $ei) { $existingByProduct[(int)$ei->ProductUID] = $ei; }

        $submittedProductUIDs = [];
        foreach ($items as $item) {
            $pid = isset($item['id']) ? (int)$item['id'] : 0;
            if ($pid > 0) $submittedProductUIDs[] = $pid;
        }

        $removedProductUIDs = array_diff(array_keys($existingByProduct), $submittedProductUIDs);
        if (!empty($removedProductUIDs)) {
            $this->dbwrite_model->softDeleteTransactionItemsByProductUIDs($transUID, array_values($removedProductUIDs), $userUID);
        }

        // Pass 1: shift updating items to vacate sequence slots before renumbering.
        $updatingProductUIDs = array_values(array_intersect(array_keys($existingByProduct), $submittedProductUIDs));
        if (!empty($updatingProductUIDs)) {
            $this->dbwrite_model->shiftTransProductSequences($transUID, $updatingProductUIDs);
        }

        $newRows = [];
        foreach ($items as $seq => $item) {
            $productUID = isset($item['id'])       ? (int)   $item['id']       : 0;
            $qty        = isset($item['quantity'])  ? (float) $item['quantity']  : 0;
            $unitPrice  = isset($item['unitPrice']) ? (float) $item['unitPrice'] : 0;
            if ($productUID <= 0 || $qty <= 0) continue;

            $rowData = [
                'ItemSequence'    => $seq + 1,
                'ProductName'     => substr(strip_tags($item['itemName'] ?? ''), 0, 100),
                'Description'     => !empty($item['description'])   ? substr($item['description'],  0, 500) : NULL,
                'PartNumber'      => !empty($item['partNumber'])    ? substr($item['partNumber'],   0, 50)  : NULL,
                'CategoryUID'     => !empty($item['categoryUID'])   ? (int) $item['categoryUID']             : NULL,
                'CategoryName'    => !empty($item['categoryName'])  ? substr($item['categoryName'], 0, 100) : NULL,
                'StorageUID'      => isset($item['storageUID'])     ? (int) $item['storageUID']               : NULL,
                'Quantity'        => $qty,
                'PrimaryUnitName' => isset($item['primaryUnit'])    ? substr($item['primaryUnit'],    0, 20) : NULL,
                'TaxDetailsUID'   => isset($item['taxDetailsUID'])  ? (int) $item['taxDetailsUID']            : 1,
                'TaxPercentage'   => (float) ($item['taxPercent']    ?? 0),
                'CGST'            => (float) ($item['cgstPercent']   ?? 0),
                'SGST'            => (float) ($item['sgstPercent']   ?? 0),
                'IGST'            => (float) ($item['igstPercent']   ?? 0),
                'DiscountTypeUID' => isset($item['discountTypeUID']) ? (int) $item['discountTypeUID']  : NULL,
                'Discount'        => (float) ($item['discount']        ?? 0),
                'UnitPrice'       => $unitPrice,
                'SellingPrice'    => (float) ($item['sellingPrice']    ?? $unitPrice),
                'PurchasePrice'   => (float) ($item['purchasePrice']   ?? 0),
                'MRP'             => (float) ($item['mrp']             ?? 0),
                'DistributionMode'=> !empty($item['isComposite']) ? ($item['distributionMode'] ?? null) : null,
                'TaxableAmount'   => (float) ($item['line_total']      ?? 0),
                'CgstAmount'      => (float) ($item['cgstAmount']      ?? 0),
                'SgstAmount'      => (float) ($item['sgstAmount']      ?? 0),
                'IgstAmount'      => (float) ($item['igstAmount']      ?? 0),
                'TaxAmount'       => (float) ($item['taxAmount']       ?? 0),
                'DiscountAmount'  => (float) ($item['discount_amount']  ?? 0),
                'NetAmount'       => (float) ($item['net_total']        ?? 0),
                'UpdatedBy'       => $userUID,
            ];

            if (isset($existingByProduct[$productUID])) {
                $this->dbwrite_model->updateTransProductItem($transUID, $productUID, $rowData);
            } else {
                $sourceProdUID = isset($item['sourceTransProdUID']) && $item['sourceTransProdUID'] > 0
                    ? (int) $item['sourceTransProdUID'] : NULL;
                $newRows[] = array_merge($rowData, [
                    'OrgUID'             => $orgUID,
                    'FinancialYear'      => $financialYear,
                    'TransUID'           => $transUID,
                    'ProductUID'         => $productUID,
                    'QuantityConverted'  => 0,
                    'IsActive'           => 1,
                    'IsDeleted'          => 0,
                    'CreatedBy'          => $userUID,
                    'SourceTransProdUID' => $sourceProdUID,
                ]);
            }
        }

        if (!empty($newRows)) {
            $batchResp = $this->dbwrite_model->insertBatchInTransaction('Transaction', 'TransProductsTbl', $newRows);
            if ($batchResp->Error) throw new Exception($batchResp->Message);
        }
    }

    /**
     * Validates transaction header + items. Throws on any error.
     * Returns the raw items JSON string for further processing.
     *
     * @param array $post
     * @return string $itemsJson
     */
    protected function _validateTransForm(array $post): string {
        $this->load->model('formvalidation_model');
        $headerError = $this->formvalidation_model->transactionValidateForm($post);
        if (!empty($headerError)) throw new Exception($headerError);

        $itemsJson  = getPostValue($post, 'Items');
        $itemsError = $this->formvalidation_model->validateQuotationItems($itemsJson);
        if (!empty($itemsError)) throw new Exception($itemsError);

        return $itemsJson;
    }

    /**
     * Extracts all financial amounts + computed fields from POST into the $amounts
     * array expected by _buildTransHeader() and _buildTransDetail().
     *
     * @param array $post
     * @param string $itemsJson  Already-validated items JSON from _validateTransForm()
     * @return array{
     *   prefixUID: int, transNumber: int, transDate: string, financialYear: int,
     *   isDraft: bool, items: array, totalQty: float, totalItems: int,
     *   subTotal: float, discountAmount: float, taxAmount: float,
     *   cgstAmount: float, sgstAmount: float, igstAmount: float,
     *   additionalChargesTotal: float, roundOff: float,
     *   globalDiscPercent: float, extraDiscount: float, netAmount: float
     * }
     */
    protected function _extractTransAmounts(array $post, string $itemsJson): array {
        $transDate = getPostValue($post, 'transDate');
        $items     = json_decode($itemsJson, true);
        return [
            'prefixUID'              => (int)   getPostValue($post, 'transPrefixSelect'),
            'transNumber'            => (int)   getPostValue($post, 'transNumber'),
            'transDate'              =>         $transDate,
            'financialYear'          => (int)   date('Y', strtotime($transDate)),
            'isDraft'                =>         getPostValue($post, 'action') === 'draft',
            'items'                  =>         $items,
            'totalQty'               => (float) array_sum(array_column($items, 'quantity')),
            'totalItems'             =>         count($items),
            'subTotal'               => (float) getPostValue($post, 'SubTotal',               'Array', 0),
            'discountAmount'         => (float) getPostValue($post, 'DiscountAmount',         'Array', 0),
            'taxAmount'              => (float) getPostValue($post, 'TaxAmount',              'Array', 0),
            'cgstAmount'             => (float) getPostValue($post, 'CgstAmount',             'Array', 0),
            'sgstAmount'             => (float) getPostValue($post, 'SgstAmount',             'Array', 0),
            'igstAmount'             => (float) getPostValue($post, 'IgstAmount',             'Array', 0),
            'additionalChargesTotal' => (float) getPostValue($post, 'AdditionalChargesTotal', 'Array', 0),
            'roundOff'               => (float) getPostValue($post, 'RoundOff',               'Array', 0),
            'globalDiscPercent'      => (float) getPostValue($post, 'GlobalDiscPercent',      'Array', 0),
            'extraDiscount'          => (float) getPostValue($post, 'extraDiscount',          'Array', 0),
            'netAmount'              => (float) getPostValue($post, 'NetAmount',              'Array', 0),
        ];
    }

    /**
     * Handles the draft/prefix/duplicate-check/uniqueNumber resolution block.
     * Returns an array with resolved values; throws on any validation failure.
     *
     * @param bool   $isDraft
     * @param int    $prefixUID
     * @param int    $transNumber
     * @param string $transDate
     * @param int    $orgUID
     * @return array{ prefix: object|null, prefixUID: int|null, transNumber: int|null, uniqueNumber: string|null }
     */
    protected function _resolveTransPrefix(bool $isDraft, int $prefixUID, int $transNumber, string $transDate, int $orgUID): array {
        if ($isDraft) {
            return ['prefix' => NULL, 'prefixUID' => NULL, 'transNumber' => NULL, 'uniqueNumber' => NULL];
        }

        if ($transNumber <= 0) throw new Exception('Transaction number must be greater than 0.');
        if ($transNumber > 2147483647) throw new Exception('Transaction number exceeds the maximum allowed value of 2,147,483,647. Please use a smaller number or create a new prefix series.');

        $this->load->model('transactions_model');
        $prefixData = $this->transactions_model->getTransactionsPrefixDetails([
            'Prefix.PrefixUID' => $prefixUID,
            'Prefix.OrgUID'    => $orgUID,
        ]);
        if (empty($prefixData->Data)) throw new Exception('Invalid prefix selected.');
        $prefix = $prefixData->Data[0];

        $dupCheck = $this->transactions_model->getTransactionByPrefixAndNumber($prefixUID, $transNumber, $orgUID, $this->pageModuleUID);
        if ($dupCheck) {
            $next = $this->transactions_model->getNextTransactionNumber($prefixUID, $orgUID, $this->pageModuleUID);
            throw new Exception("Transaction number {$transNumber} already exists for this prefix. Next available: {$next}.");
        }

        list($uniqueNumber) = $this->buildUniqueNumber($prefix, $transNumber, $transDate);
        return ['prefix' => $prefix, 'prefixUID' => $prefixUID, 'transNumber' => $transNumber, 'uniqueNumber' => $uniqueNumber];
    }

    /**
     * Extracts AdditionalCharges from POST and saves them via transactions_model.
     * No-op if there are no charges.
     *
     * @param int   $transUID
     * @param int   $orgUID
     * @param int   $userUID
     * @param array $post
     * @return void
     */
    protected function _saveTransCharges(int $transUID, int $orgUID, int $userUID, array $post): void {
        $json = getPostValue($post, 'AdditionalCharges') ?: '[]';
        $list = json_decode($json, true) ?: [];
        if (!empty($list)) {
            $this->load->model('transactions_model');
            $this->transactions_model->saveTransactionCharges($transUID, $orgUID, $userUID, $list);
        }
    }

    /**
     * Updates TransactionsTbl paid/balance/isFullyPaid fields after a payment.
     * Throws if the DB update fails.
     *
     * @param int   $transUID
     * @param float $netAmount
     * @param float $paidAmount
     * @param int   $userUID
     * @return void
     */
    protected function _updateTransactionBalance(int $transUID, float $netAmount, float $paidAmount, int $userUID): void {
        $isFullyPaid   = ($netAmount > 0 && round($netAmount - $paidAmount, 4) <= 0) ? 1 : 0;
        $balanceAmount = max(0, round($netAmount - $paidAmount, $this->_decimals()));
        $ok = $this->dbwrite_model->updateTransIsFullyPaid($transUID, $isFullyPaid, $paidAmount, $balanceAmount, $userUID);
        if ($ok === false) {
            throw new Exception('Failed to update transaction balance for TransUID ' . $transUID);
        }
    }

    /**
     * Builds a formatted UniqueNumber for a payment entry using prefix formatting rules.
     *
     * @param object $prefix
     * @param string $paymentDate
     * @param int    $paymentNumber
     * @return string
     */
    protected function _buildPaymentUniqueNumber(object $prefix, string $paymentDate, int $paymentNumber): string {
        $sep   = $prefix->Separator ?? '-';
        $parts = [strtoupper($prefix->Name)];
        if (!empty($prefix->IncludeShortName) && !empty($prefix->ShortName)) {
            $parts[] = strtoupper($prefix->ShortName);
        }
        if (!empty($prefix->IncludeFiscalYear)) {
            $m       = (int) date('m', strtotime($paymentDate));
            $yr      = (int) date('Y', strtotime($paymentDate));
            $fy      = $m >= 4 ? $yr : $yr - 1;
            $parts[] = ($prefix->FiscalYearFormat ?? 'SHORT') === 'LONG'
                ? $fy . '-' . ($fy + 1)
                : str_pad($fy % 100, 2, '0', STR_PAD_LEFT) . '-' . str_pad(($fy + 1) % 100, 2, '0', STR_PAD_LEFT);
        }
        $pad     = (int) ($prefix->NumberPadding ?? 1);
        $parts[] = $pad > 1 ? str_pad($paymentNumber, $pad, '0', STR_PAD_LEFT) : (string) $paymentNumber;
        return implode($sep, $parts);
    }

    /**
     * Rebuilds the transaction list response after a mutation (delete, status update, duplicate).
     * Reads PageNo, RowLimit, Filter from POST; populates RecordHtmlData, Pagination, TotalCount
     * on $this->EndReturnData.
     *
     * @param string $viewPath      CI view path, e.g. 'transactions/invoices/list'
     * @param string $paginationUrl Route for pagination links, e.g. '/invoices/getInvoicesPageDetails'
     * @return void
     */
    protected function _buildListResponse(string $viewPath, string $paginationUrl): void {
        $pageNo       = max(1, (int) $this->input->post('PageNo'));
        $limit        = (int) $this->input->post('RowLimit') ?: 10;
        $filter       = $this->input->post('Filter') ?: [];
        $offset       = ($pageNo - 1) * $limit;
        $allData      = $this->transactions_model->getTransactionPageList($limit, $offset, $this->pageModuleUID, $filter, 0);
        $allDataCount = $this->transactions_model->getTransactionCount($this->pageModuleUID, $filter);
        $this->EndReturnData->RecordHtmlData = $this->load->view($viewPath, ['DataLists' => $allData, 'SerialNumber' => $offset, 'JwtData' => $this->pageData['JwtData']], true);
        $this->EndReturnData->Pagination     = $this->globalservice->buildPagePaginationHtml($paginationUrl, $allDataCount, $pageNo, $limit);
        $this->EndReturnData->TotalCount     = $allDataCount;
    }

    /**
     * Rebuilds the transaction list response after recording a payment.
     * Reads CurrentPage from POST; reads RowLimit from JWT GenSettings (not POST).
     * Also populates SummaryStats — needed after payment recording changes totals.
     *
     * @param string $viewPath      CI view path, e.g. 'transactions/invoices/list'
     * @param string $paginationUrl Route for pagination links, e.g. '/transactions/getPageDetails/103'
     * @return void
     */
    protected function _buildPaymentListResponse(string $viewPath, string $paginationUrl): void {
        $genSettings  = $this->pageData['JwtData']->GenSettings ?? new stdClass();
        $limit        = max(1, (int) ($genSettings->RowLimit ?? 10));
        $pageNo       = max(1, (int) $this->input->post('CurrentPage'));
        $filter       = $this->input->post('Filter') ?: [];
        $offset       = ($pageNo - 1) * $limit;
        $orgUID       = (int) $this->pageData['JwtData']->Org->OrgUID;
        $allData      = $this->transactions_model->getTransactionPageList($limit, $offset, $this->pageModuleUID, $filter, 0);
        $allDataCount = $this->transactions_model->getTransactionCount($this->pageModuleUID, $filter);
        $this->pageData['JwtData']->GenSettings  = $genSettings;
        $this->EndReturnData->RecordHtmlData = $this->load->view($viewPath, ['DataLists' => $allData, 'SerialNumber' => $offset, 'JwtData' => $this->pageData['JwtData']], true);
        $this->EndReturnData->Pagination     = $this->globalservice->buildPagePaginationHtml($paginationUrl, $allDataCount, $pageNo, $limit);
        $this->EndReturnData->TotalCount     = $allDataCount;
        $this->EndReturnData->SummaryStats   = $this->transactions_model->getTransactionSummaryStats($this->pageModuleUID, $orgUID, $filter);
    }

    /**
     * Saves payment rows submitted with a transaction form.
     * Direction 'In'  = customer payment received (module 110, CR ledger) — used by Invoices.
     * Direction 'Out' = vendor payment made     (module 111, DR ledger) — used by Purchases.
     *
     * @param int         $transUID
     * @param int         $orgUID
     * @param int         $userUID
     * @param string      $partyType        'C' customer | 'S' supplier
     * @param int         $partyUID
     * @param float       $billTotal        Net amount of the parent transaction
     * @param array       $PostData
     * @param string      $paymentDirection 'In' or 'Out'
     * @param string|null $transDate
     * @return array{totalPaid: float, firstPaymentUID: int|null}
     */
    protected function _savePaymentRecord(
        int $transUID, int $orgUID, int $userUID,
        string $partyType, int $partyUID, float $billTotal,
        array $PostData, string $paymentDirection,
        ?string $transDate = null
    ): array {
        $rowsJson    = getPostValue($PostData, 'PaymentRows') ?: '';
        $isFullyPaid = (int) getPostValue($PostData, 'IsFullyPaid') === 1 ? 1 : 0;

        if (empty($rowsJson)) return ['totalPaid' => 0.0, 'firstPaymentUID' => null];
        $rows = json_decode($rowsJson, true);
        if (!is_array($rows) || empty($rows)) return ['totalPaid' => 0.0, 'firstPaymentUID' => null];

        $this->load->model(['transactions_model', 'dbwrite_model']);

        // 110 = Payments In (received); 111 = Payments Out (paid to vendor)
        $paymentModuleUID = $paymentDirection === 'In' ? 110 : 111;
        $ledgerSide       = $paymentDirection === 'In' ? 'CR' : 'DR';
        $ledgerContext    = $paymentDirection === 'In' ? 'Invoice' : 'Purchase';
        $ledgerDescPrefix = $paymentDirection === 'In' ? 'Payment received – ' : 'Payment made to vendor – ';

        $defaultPaymentDate = $transDate ?: date('Y-m-d');
        $totalPaid          = array_sum(array_column($rows, 'amount'));

        $payPrefixData = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID, 'Prefix.ModuleUID' => $paymentModuleUID]);
        $payPrefix     = !empty($payPrefixData->Data) ? $payPrefixData->Data[0] : null;
        $payPrefixUID  = $payPrefix ? (int) $payPrefix->PrefixUID : null;

        $firstPaymentUID = null;
        foreach ($rows as $idx => $row) {
            $paymentTypeUID = (int)   ($row['paymentTypeUID'] ?? 0);
            $amount         = (float) ($row['amount']         ?? 0);
            $bankAccountUID = !empty($row['bankAccountUID']) ? (int) $row['bankAccountUID'] : NULL;
            $referenceNo    = !empty($row['referenceNo'])    ? $row['referenceNo'] : NULL;
            $notes          = !empty($row['notes'])          ? $row['notes']       : NULL;
            $rowDate        = !empty($row['paymentDate']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $row['paymentDate'])
                                ? $row['paymentDate'] : $defaultPaymentDate;
            $rowTransYear   = (int) date('Y', strtotime($rowDate));

            if ($paymentTypeUID <= 0 || $amount <= 0) continue;

            $rowExcess = 0;
            if ($idx === count($rows) - 1 && $billTotal > 0 && $totalPaid > $billTotal) {
                $rowExcess = round($totalPaid - $billTotal, 4);
            }

            $paymentNumber = $payPrefixUID ? $this->transactions_model->getNextPaymentNumber($payPrefixUID, $orgUID, $rowTransYear) : 0;
            $payUniqueNum  = ($payPrefix && $paymentNumber > 0) ? $this->_buildPaymentUniqueNumber($payPrefix, $rowDate, $paymentNumber) : null;
            $receiptToken  = $this->transactions_model->_generateReceiptToken();

            $paymentData = [
                'OrgUID'            => $orgUID,
                'PaymentDate'       => $rowDate,
                'PaymentModuleUID'  => $paymentModuleUID,
                'PrefixUID'         => $payPrefixUID,
                'PaymentNumber'     => $paymentNumber,
                'UniqueNumber'      => $payUniqueNum,
                'ReceiptToken'      => $receiptToken,
                'TransYear'         => $rowTransYear,
                'TransUID'          => $transUID,
                'ModuleUID'         => $this->pageModuleUID,
                'PartyType'         => $partyType,
                'PartyUID'          => $partyUID,
                'PaymentTypeUID'    => $paymentTypeUID,
                'Amount'            => $amount,
                'BankAccountUID'    => $bankAccountUID,
                'ReferenceNo'       => $referenceNo,
                'Notes'             => $notes,
                'PaymentSource'     => 'Create',
                'PaymentDirection'  => $paymentDirection,
                'IsFullyPaid'       => ($idx === count($rows) - 1) ? $isFullyPaid : 0,
                'ExcessAmount'      => $rowExcess,
                'AppliedToTransUID' => NULL,
                'IsActive'          => 1,
                'IsDeleted'         => 0,
                'CreatedBy'         => $userUID,
                'UpdatedBy'         => $userUID,
            ];

            $resp = $this->dbwrite_model->insertBatchInTransaction('Transaction', 'PaymentsTbl', [$paymentData]);
            if ($resp->Error) throw new Exception('Payment save failed: ' . $resp->Message);
            if ($idx === 0) $firstPaymentUID = $resp->ID ?? null;

            $this->_writeBankLedgerEntry(
                $orgUID, $bankAccountUID, $ledgerSide, $amount,
                $ledgerContext, $transUID, $this->pageModuleUID,
                $referenceNo, $ledgerDescPrefix . ($payUniqueNum ?? '#' . $transUID),
                $rowDate, $userUID
            );
        }

        return ['totalPaid' => (float) $totalPaid, 'firstPaymentUID' => $firstPaymentUID];
    }

}
