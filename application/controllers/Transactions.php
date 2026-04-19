<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Transactions extends CI_Controller {

    public $pageData = array();
    private $EndReturnData;

    public function __construct() {
        parent::__construct();

    }

    // ----------------------------------------------------------------
    // GET  /transactions/getTransactionPrefixes
    // Returns all org-level prefixes (shared across all transaction types)
    // ----------------------------------------------------------------
    public function getTransactionPrefixes() {

        $this->EndReturnData = new stdClass();
        try {

            $orgUID = $this->pageData['JwtData']->User->OrgUID;

            $this->load->model('transactions_model');
            $result = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID]);

            $this->EndReturnData->Data  = $result->Data ?? [];
            $this->EndReturnData->Error = FALSE;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    // ----------------------------------------------------------------
    // POST /transactions/addTransactionPrefix
    // ----------------------------------------------------------------
    public function addTransactionPrefix() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData = $this->input->post();

            $this->load->model('formvalidation_model');
            $ErrorInForm = $this->formvalidation_model->transPrefixValidateForm($PostData);
            if (!empty($ErrorInForm)) throw new Exception($ErrorInForm);

            $userUID = $this->pageData['JwtData']->User->UserUID;
            $now     = time();

            $addFormData = [
                'OrgUID'           => $this->pageData['JwtData']->User->OrgUID,
                'Name'             => strtoupper(getPostValue($PostData, 'transPrefixName')),
                'IncludeFiscalYear'=> getPostValue($PostData, 'includeFiscalYear') ? 1 : 0,
                'FiscalYearFormat' => in_array(getPostValue($PostData, 'fiscalYearFormat'), ['SHORT','LONG'])
                                        ? getPostValue($PostData, 'fiscalYearFormat') : 'SHORT',
                'IncludeShortName' => getPostValue($PostData, 'includeShortName') ? 1 : 0,
                'ShortName'        => strtoupper(substr(getPostValue($PostData, 'companyShortName') ?? '', 0, 20)),
                'Separator'        => getPostValue($PostData, 'prefixSeparator') ?: '-',
                'NumberPadding'    => (int)(getPostValue($PostData, 'numberPadding') ?: 1),
                'CreatedBy'        => $userUID,
                'CreatedOn'        => $now,
                'UpdatedBy'        => $userUID,
                'UpdatedOn'        => $now,
            ];

            $this->load->model('dbwrite_model');
            $getResp = $this->dbwrite_model->insertData('Transaction', 'TransactionPrefixTbl', $addFormData);
            if ($getResp->Error) throw new Exception($getResp->Message);

            // Return the new prefix data so the caller can update the UI
            $this->load->model('transactions_model');
            $newPrefix = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.PrefixUID' => $getResp->ID]);

            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Message    = 'Prefix added successfully.';
            $this->EndReturnData->PrefixUID  = $getResp->ID;
            $this->EndReturnData->PrefixData = $newPrefix->Data[0] ?? null;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    // ----------------------------------------------------------------
    // POST /transactions/updateTransactionPrefix
    // ----------------------------------------------------------------
    public function updateTransactionPrefix() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData  = $this->input->post();
            $prefixUID = (int) getPostValue($PostData, 'prePrefixUID');
            if ($prefixUID <= 0) throw new Exception('Invalid prefix.');

            $this->load->model('formvalidation_model');
            $ErrorInForm = $this->formvalidation_model->transPrefixValidateForm($PostData);
            if (!empty($ErrorInForm)) throw new Exception($ErrorInForm);

            $userUID = $this->pageData['JwtData']->User->UserUID;
            $orgUID  = $this->pageData['JwtData']->User->OrgUID;

            $updateData = [
                'Name'             => strtoupper(getPostValue($PostData, 'transPrefixName')),
                'IncludeFiscalYear'=> getPostValue($PostData, 'includeFiscalYear') ? 1 : 0,
                'FiscalYearFormat' => in_array(getPostValue($PostData, 'fiscalYearFormat'), ['SHORT','LONG'])
                                        ? getPostValue($PostData, 'fiscalYearFormat') : 'SHORT',
                'IncludeShortName' => getPostValue($PostData, 'includeShortName') ? 1 : 0,
                'ShortName'        => strtoupper(substr(getPostValue($PostData, 'companyShortName') ?? '', 0, 20)),
                'Separator'        => getPostValue($PostData, 'prefixSeparator') ?: '-',
                'NumberPadding'    => (int)(getPostValue($PostData, 'numberPadding') ?: 1),
                'UpdatedBy'        => $userUID,
            ];

            $this->load->model('dbwrite_model');
            $resp = $this->dbwrite_model->updateData(
                'Transaction', 'TransactionPrefixTbl',
                $updateData,
                ['PrefixUID' => $prefixUID, 'OrgUID' => $orgUID]
            );
            if ($resp->Error) throw new Exception($resp->Message);

            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Prefix updated successfully.';

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    // ----------------------------------------------------------------
    // POST /transactions/deleteTransactionPrefix
    // ----------------------------------------------------------------
    public function deleteTransactionPrefix() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData  = $this->input->post();
            $prefixUID = (int) getPostValue($PostData, 'prePrefixUID');
            if ($prefixUID <= 0) throw new Exception('Invalid prefix.');

            $userUID = $this->pageData['JwtData']->User->UserUID;
            $orgUID  = $this->pageData['JwtData']->User->OrgUID;

            $this->load->model('dbwrite_model');
            $resp = $this->dbwrite_model->updateData(
                'Transaction', 'TransactionPrefixTbl',
                ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID, 'UpdatedOn' => time()],
                ['PrefixUID' => $prefixUID, 'OrgUID' => $orgUID]
            );
            if ($resp->Error) throw new Exception($resp->Message);

            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Prefix deleted.';

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    // ----------------------------------------------------------------
    // POST /transactions/setDefaultTransactionPrefix
    // ----------------------------------------------------------------
    public function setDefaultTransactionPrefix() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData  = $this->input->post();
            $prefixUID = (int) getPostValue($PostData, 'prePrefixUID');
            if ($prefixUID <= 0) throw new Exception('Invalid prefix.');

            $orgUID  = $this->pageData['JwtData']->User->OrgUID;
            $userUID = $this->pageData['JwtData']->User->UserUID;

            $this->load->model('transactions_model');

            // Clear default flag for all org prefixes, then set the chosen one
            $resp = $this->dbwrite_model->updateData(
                'Transaction', 'TransactionPrefixTbl',
                ['IsDefault' => 0, 'UpdatedBy' => $userUID, 'UpdatedOn' => time()],
                ['OrgUID' => $orgUID]
            );
            if ($resp->Error) throw new Exception($resp->Message);

            $updresp = $this->dbwrite_model->updateData(
                'Transaction', 'TransactionPrefixTbl',
                ['IsDefault' => 1, 'UpdatedBy' => $userUID, 'UpdatedOn' => time()],
                ['PrefixUID' => $prefixUID, 'OrgUID' => $orgUID]
            );
            if ($updresp->Error) throw new Exception($updresp->Message);

            $allResults = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID]);

            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error         = FALSE;
            $this->EndReturnData->Message       = 'Default prefix updated.';
            $this->EndReturnData->PrefixUID     = $prefixUID;
            // $this->EndReturnData->PrefixData    = $prefixResult->Data[0];
            $this->EndReturnData->AllPrefixData = $allResults;

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function searchCustomers() {

        $this->EndReturnData = new stdClass();
		try {

            $term = $this->input->get('term') ? trim($this->input->get('term')) : '';

            $this->load->model('transactions_model');
            $customersData = $this->transactions_model->getCustomersDetails($term, []);

            $customersDetails = [];
            foreach ($customersData as $value) {
                $formData = [
                    'id'   => $value->CustomerUID,
                    'text' => $value->Area 
                        ? $value->Name . ' (' . $value->Area . ')' 
                        : $value->Name,
                ];
                if($value->AddrUID) {
                    $formData['address'] = [
                        'Line1' => $value->Line1,
                        'Line2' => $value->Line2,
                        'Pincode' => $value->Pincode,
                        'City' => $value->CityText,
                        'State' => $value->StateText,
                    ];
                }
                $customersDetails[] = $formData;
            }
            $this->EndReturnData->Lists = $customersDetails;
            $this->EndReturnData->Error = false;

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);
        
    }

    // ----------------------------------------------------------------
    // GET|POST /transactions/getTransactionDetail
    // Common function to fetch transaction header, items, org info,
    // thermal config and print theme — used by all transaction pages.
    // ----------------------------------------------------------------
    public function getTransactionDetail() {

        $this->EndReturnData = new stdClass();
        try {

            $transUID  = (int) $this->input->get_post('TransUID');
            $moduleUID = (int) $this->input->get_post('ModuleUID');
            $orgUID    = $this->pageData['JwtData']->User->OrgUID;

            if ($transUID  <= 0) throw new Exception('Invalid transaction.');
            if ($moduleUID <= 0) throw new Exception('ModuleUID is required.');

            $this->load->model('transactions_model');
            $header = $this->transactions_model->getTransactionById($transUID, $orgUID, $moduleUID);
            if (!$header) throw new Exception('Transaction not found.');

            $items = $this->transactions_model->getTransactionItems($transUID, $orgUID);

            $this->load->model('organisation_model');
            $orgInfo          = $this->organisation_model->getOrgForReceipt($orgUID);
            $thermalCfgResult = $this->organisation_model->getThermalPrintConfigByType($orgUID, $header->TransType);
            $printThemeResult = $this->organisation_model->getPrintThemeByType($orgUID, $header->TransType);
            $printBankAccount = $this->transactions_model->getPrintBankAccount($orgUID);

            $this->EndReturnData->Error         = FALSE;
            $this->EndReturnData->Header        = $header;
            $this->EndReturnData->Items         = $items;
            $this->EndReturnData->OrgInfo       = $orgInfo->Data ?? null;
            $this->EndReturnData->ThermalConfig = $thermalCfgResult->Data ?? null;
            $this->EndReturnData->PrintTheme    = $printThemeResult->Data ?? null;
            $this->EndReturnData->PrintHtml     = null;
            try {
                $this->EndReturnData->PrintHtml = $this->_renderA4Html($header, $items, $orgInfo->Data ?? null, $printThemeResult->Data ?? null, $printBankAccount);
            } catch (Exception $renderEx) {
                // Template rendering failed — preview unavailable but other data still returned
                $this->EndReturnData->PrintHtml = '<div style="padding:20px;color:#c00;">Preview error: ' . htmlspecialchars($renderEx->getMessage()) . '</div>';
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    // ----------------------------------------------------------------
    // Server-side A4 HTML renderer
    // Reads the template file, replaces {{}} tokens, returns HTML string
    // ----------------------------------------------------------------
    private function _renderA4Html($h, $items, $org, $theme, $bankAccount = null) {

        $org   = $org   ?? new stdClass();
        $theme = $theme ?? new stdClass();

        // ── Load template ────────────────────────────────────────────
        $tplHtml = $theme->TemplateHtmlContent ?? null;
        if (!$tplHtml) {
            // No template assigned — use built-in generic layout
            return $this->_renderGenericA4Html($h, $items, $org);
        }

        // ── Helpers ──────────────────────────────────────────────────
        $cur = '₹ ';
        $dec = 2;
        $e   = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES);
        $fmt = function($date) {
            if (!$date) return '—';
            $d = date_create($date);
            return $d ? date_format($d, 'd M Y') : $date;
        };
        $addr    = fn($l1,$l2,$city,$state,$pin) => implode(', ', array_filter([$l1,$l2,$city,$state,$pin]));
        $addrHtml = fn($l1,$l2,$city,$state,$pin) => implode('<br>', array_filter(array_map('htmlspecialchars', array_filter([$l1,$l2,$city,$state,$pin]))));

        // ── Items table ──────────────────────────────────────────────
        $itemRows = '';
        foreach ($items as $i => $item) {
            $taxAmt = round((float)($item->CgstAmount ?? 0) + (float)($item->SgstAmount ?? 0) + (float)($item->IgstAmount ?? 0), $dec);
            $itemRows .=
                '<tr>' .
                    '<td style="text-align:center">' . ($i + 1) . '</td>' .
                    '<td>' . $e($item->ProductName) . '</td>' .
                    '<td style="text-align:center">' . $e($item->HSNCode ?? '-') . '</td>' .
                    '<td style="text-align:right">'  . number_format((float)($item->UnitPrice ?? 0), $dec) . '</td>' .
                    '<td style="text-align:center">' . $e($item->Quantity) . ' ' . $e($item->PrimaryUnitName ?? '') . '</td>' .
                    '<td style="text-align:right">'  . number_format((float)($item->UnitPrice ?? 0) * (float)($item->Quantity ?? 0), $dec) . '</td>' .
                    '<td style="text-align:right">'  . ($taxAmt ? number_format($taxAmt, $dec) . ' (' . number_format((float)($item->TaxPercentage ?? 0), 0) . '%)' : '') . '</td>' .
                    '<td style="text-align:right">'  . number_format((float)($item->NetAmount ?? 0), $dec) . '</td>' .
                '</tr>';
        }
        $itemsTable =
            '<table style="width:100%;border-collapse:collapse;font-size:8.5pt;margin-bottom:8px;">' .
            '<thead><tr style="background:#f5f5f5;">' .
            '<th style="border:1px solid #ddd;padding:5px;text-align:center;width:28px">#</th>' .
            '<th style="border:1px solid #ddd;padding:5px;">Item</th>' .
            '<th style="border:1px solid #ddd;padding:5px;">HSN/SAC</th>' .
            '<th style="border:1px solid #ddd;padding:5px;text-align:right;">Rate</th>' .
            '<th style="border:1px solid #ddd;padding:5px;text-align:center;">Qty</th>' .
            '<th style="border:1px solid #ddd;padding:5px;text-align:right;">Taxable Value</th>' .
            '<th style="border:1px solid #ddd;padding:5px;text-align:right;">Tax Amt</th>' .
            '<th style="border:1px solid #ddd;padding:5px;text-align:right;">Amount</th>' .
            '</tr></thead><tbody>' . $itemRows . '</tbody></table>';

        // ── Totals ───────────────────────────────────────────────────
        $totals =
            '<table style="width:100%;border-collapse:collapse;font-size:8.5pt;margin-bottom:8px;">' .
            '<tr><td style="border:1px solid #ddd;padding:5px;text-align:right;font-weight:600;">Sub Total</td>' .
            '<td style="border:1px solid #ddd;padding:5px;text-align:right;width:120px;">' . $cur . number_format((float)($h->SubTotal ?? 0), $dec) . '</td></tr>' .
            ((float)($h->DiscountAmount ?? 0) > 0 ? '<tr><td style="border:1px solid #ddd;padding:5px;text-align:right;color:#c00;">Discount</td><td style="border:1px solid #ddd;padding:5px;text-align:right;color:#c00;">- ' . $cur . number_format((float)$h->DiscountAmount, $dec) . '</td></tr>' : '') .
            ((float)($h->TaxAmount ?? 0) > 0 ? '<tr><td style="border:1px solid #ddd;padding:5px;text-align:right;">Tax</td><td style="border:1px solid #ddd;padding:5px;text-align:right;">' . $cur . number_format((float)$h->TaxAmount, $dec) . '</td></tr>' : '') .
            '<tr><td style="border:1px solid #ddd;padding:5px;text-align:right;font-weight:700;">Net Amount</td>' .
            '<td style="border:1px solid #ddd;padding:5px;text-align:right;font-weight:700;">' . $cur . number_format((float)($h->NetAmount ?? 0), $dec) . '</td></tr>' .
            '</table>';

        // ── Customer Addresses ────────────────────────────────────────────────
        $billAddr = $addr($h->BillLine1 ?? '', $h->BillLine2 ?? '', $h->BillCity ?? '', $h->BillState ?? '', $h->BillPincode ?? '') ?: '';
        $shipAddr = $addr($h->ShipLine1 ?? '', $h->ShipLine2 ?? '', $h->ShipCity ?? '', $h->ShipState ?? '', $h->ShipPincode ?? '') ?: '';
        // \u2500\u2500 Customer address (billing first, fallback to shipping) \u2500\u2500
        $custAddrHtml = $addrHtml($h->BillLine1 ?? '', $h->BillLine2 ?? '', $h->BillCity ?? '', $h->BillState ?? '', $h->BillPincode ?? '');
        if (empty($custAddrHtml)) {
            $custAddrHtml = $addrHtml($h->ShipLine1 ?? '', $h->ShipLine2 ?? '', $h->ShipCity ?? '', $h->ShipState ?? '', $h->ShipPincode ?? '');
        }

        // ── Org logo ─────────────────────────────────────────────────
        $logoHtml = '';
        if(empty($org->Logo)) {
            $logoHtml = '<img src="https://pub-bb40942a33344637936ade1f3800ff8b.r2.dev/Global/favicon_io/android-chrome-512x512-1.png" style="max-width:100px;max-height:100px;" alt="Logo">';
        } else {
            $logoHtml = '<img src="' . $e($org->Logo) . '" style="max-width:100px;max-height:100px;" alt="Logo">';
        }

        // ── Org address lines ────────────────────────────────────────
        $orgAddr1     = $e($org->Line1 ?? '');
        $orgAddr2     = $e($org->Line2 ?? '');
        $orgCityState = implode(', ', array_filter([$org->CityText ?? '', $org->StateText ?? '']));
        $orgGstinLine  = !empty($org->GSTIN) ? '<b>GSTIN:</b> ' . $e($org->GSTIN) : '';
        $orgCityPin   = implode(' - ', array_filter([$e($orgCityState), $e($org->Pincode ?? '')]));
        $orgInfoLines = implode('<br>', array_filter([$orgAddr1, $orgAddr2, $orgCityPin, $orgGstinLine]));

        // ── Notes + Terms ────────────────────────────────────────────
        $notesPart = !empty($h->Notes)           ? '<p style="font-size:8pt;margin-top:4px;"><strong>Notes:</strong> ' . nl2br($e($h->Notes)) . '</p>' : '';
        $termsPart = !empty($h->TermsConditions) ? '<p style="font-size:8pt;margin-top:4px;"><strong>Terms:</strong> ' . nl2br($e($h->TermsConditions)) . '</p>' : '';

        // ── Bank Account (for print templates) ───────────────────────
        $bank        = $bankAccount ?? null;
        $bankName    = $bank ? $e($bank->BankName      ?? '') : '';
        $bankAccName = $bank ? $e($bank->AccountName   ?? '') : '';
        $bankAccNo   = $bank ? $e($bank->AccountNumber ?? '') : '';
        $bankIfsc    = $bank ? $e($bank->IFSC          ?? '') : '';
        $bankBranch  = $bank ? $e($bank->BranchName    ?? '') : '';
        $bankUpiId   = $bank ? $e($bank->UPIId         ?? '') : '';

        // QR Code HTML: UPI deep-link QR with org logo overlaid at centre
        // Fix 1: &am and &cu go INSIDE the upiStr (the QR payload), not as qrserver params.
        // Fix 2: always show logo — use default CDN logo when org has no custom logo.
        $bankQrHtml = '';
        if ($bank && !empty($bank->UPIId)) {
            $netAmt = number_format((float)($h->NetAmount ?? 0), 2, '.', ''); // no thousand-sep
            $upiStr = 'upi://pay?pa=' . rawurlencode($bank->UPIId)
                    . '&pn=' . rawurlencode($org->BrandName ?? $org->Name ?? '')
                    . '&am=' . $netAmt
                    . '&cu=INR';
            $qrUrl  = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&ecc=H&data=' . rawurlencode($upiStr);
            // Org logo: prefer custom logo, else fall back to default
            $orgLogoSrc = !empty($org->Logo)
                ? $e($org->Logo)
                : 'https://pub-bb40942a33344637936ade1f3800ff8b.r2.dev/Global/favicon_io/android-chrome-512x512-1.png';
            $bankQrHtml = '<div style="position:relative;display:inline-block;line-height:0;">'
                . '<img src="' . $qrUrl . '" width="150" height="150">'
                . '<div style="position:absolute;top:50%;left:50%;'
                    . 'transform:translate(-50%,-50%);width:38px;height:38px;'
                    . 'background:#fff;border-radius:4px;padding:3px;box-sizing:border-box;">'
                . '<img src="' . $orgLogoSrc . '" style="width:100%;height:100%;object-fit:contain;">'
                . '</div>'
                . '</div>';
        }

        // Signature block: space for physical stamp/signature + label
        $signatureSpaceHtml = '<div style="min-height:65px;"></div>';

        // ── Summary totals — read directly from TransactionsTbl (no item-level summing) ──
        $totalItemsCount = (int)($h->TotalItems    ?? count($items));
        $totalQty        = (float)($h->TotalQuantity ?? 0);
        $totalCgst       = (float)($h->CgstAmount    ?? 0);
        $totalSgst       = (float)($h->SgstAmount    ?? 0);
        $totalIgst       = (float)($h->IgstAmount    ?? 0);

        // ── HSN summary totals — computed from item-level data (matches HSN loop rows) ──
        $hsnTotalTaxable = array_sum(array_map(
            fn($it) => round((float)($it->UnitPrice ?? 0) * (float)($it->Quantity ?? 0), 2),
            $items
        ));
        $hsnTotalCgst    = array_sum(array_map(fn($it) => (float)($it->CgstAmount ?? 0), $items));
        $hsnTotalSgst    = array_sum(array_map(fn($it) => (float)($it->SgstAmount ?? 0), $items));
        $hsnTotalIgst    = array_sum(array_map(fn($it) => (float)($it->IgstAmount ?? 0), $items));
        $hsnTotalTax     = round($hsnTotalCgst + $hsnTotalSgst + $hsnTotalIgst, 2);
        $dec2 = 2;

        // ── Token map ────────────────────────────────────────────────
        $tokens = [
            '{{PRIMARY_COLOR}}'        => $theme->PrimaryColor  ?? '#1a3c6e',
            '{{ACCENT_COLOR}}'         => $theme->AccentColor   ?? '#f59e0b',
            '{{FONT_FAMILY}}'          => $theme->FontFamily    ?? 'Arial',
            '{{FONT_SIZE_PX}}'         => ($theme->FontSizePx   ?? 11) . 'px',
            '{{FONT_SIZE}}'            => ($theme->FontSizePx   ?? 11) . 'px',
            /** Organisation Details */
            '{{ORG_LOGO}}'             => $logoHtml,
            '{{ORG_NAME}}'             => $e($org->BrandName ?? $org->Name ?? ''),
            '{{ORG_GSTIN}}'            => $e($org->GSTIN ?? ''),
            '{{ORG_ADDRESS_1}}'        => $orgAddr1,
            '{{ORG_ADDRESS_2}}'        => $orgAddr2,
            '{{ORG_CITY_STATE}}'       => $e($orgCityState),
            '{{ORG_PINCODE}}'          => $e($org->Pincode ?? ''),
            '{{ORG_PHONE}}'            => $e($org->MobileNumber ?? ''),
            '{{ORG_EMAIL}}'            => $e($org->EmailAddress ?? ''),
            '{{ORG_BANK_NAME}}'        => $e($org->BankName ?? ''),
            '{{ORG_ACCOUNT_NO}}'       => $e($org->AccountNo ?? ''),
            '{{ORG_IFSC}}'             => $e($org->IFSC ?? ''),
            '{{ORG_BRANCH}}'           => $e($org->Branch ?? ''),
            '{{ORG_UPI_ID}}'           => $e($org->UpiId ?? ''),
            '{{ORG_INFO_LINES}}'       => $orgInfoLines,
            '{{PLACE_OF_SUPPLY}}'      => $e($h->PlaceOfSupply ?? $org->StateText ?? ''),
            '{{BANK_DETAILS_LINES}}'   => implode('<br>', array_filter([$e($org->BankName ?? ''), !empty($org->AccountNo) ? 'A/C: ' . $e($org->AccountNo) : '', !empty($org->IFSC) ? 'IFSC: ' . $e($org->IFSC) : ''])),
            '{{CURRENCY}}'             => $cur,
            /** Customer Details */
            '{{CUSTOMER_NAME}}'        => $e($h->PartyName ?? '—'),
            '{{CUSTOMER_PHONE}}'       => $e($h->PartyMobile ?? ''),
            '{{CUSTOMER_GSTIN}}'       => $e($h->PartyGSTIN ?? ''),
            '{{BILLING_ADDRESS}}'      => $e($billAddr),
            '{{SHIPPING_ADDRESS}}'     => $e($shipAddr),
            '{{CUSTOMER_ADDRESS}}'     => $custAddrHtml,
            '{{PARTY_GSTIN}}'          => $e($h->PartyGSTIN ?? ''),
            '{{PARTY_PHONE}}'          => $e($h->PartyMobile ?? ''),
            '{{CUSTOMER_PHONE_LINE}}'  => !empty($h->PartyMobile) ? 'Ph: ' . $e($h->PartyMobile) : '',
            '{{PARTY_GSTIN_LINE}}'     => !empty($h->PartyGSTIN) ? 'GSTIN: ' . $e($h->PartyGSTIN) : '',
            /** Transaction Type Details */
            '{{DOC_TYPE}}'             => $e($h->TransType ?? 'Document'),
            '{{DOC_NUMBER}}'           => $e($h->UniqueNumber ?? '—'),
            '{{DOC_DATE}}'             => $fmt($h->TransDate ?? ''),
            '{{DUE_DATE}}'             => $fmt($h->ValidityDate ?? ''),
            '{{ITEMS_TABLE}}'          => $itemsTable,
            '{{ITEMS_TABLE_ROWS}}'     => $itemRows,
            '{{TOTALS_SECTION}}'       => $totals,
            '{{TOTALS_BLOCK}}'         => $totals,
            '{{NOTES_TERMS}}'          => $notesPart . $termsPart,
            '{{FOOTER_TEXT}}'          => $e($theme->FooterText ?? 'Thank you for your business!'),
            '{{TERMS_CONDITIONS}}'     => nl2br($e($h->TermsConditions ?? '')),
            '{{HSN_TAX_TABLE}}'        => '',
            /** Summary Totals */
            '{{TOTAL_ITEMS_COUNT}}'    => $totalItemsCount,
            '{{TOTAL_QTY}}'            => number_format($totalQty, 2),
            '{{TOTAL_TAXABLE_AMOUNT}}' => number_format((float)($h->SubTotal ?? 0), $dec),
            '{{TOTAL_CGST}}'           => number_format(round($totalCgst, $dec), $dec),
            '{{TOTAL_SGST}}'           => number_format(round($totalSgst, $dec), $dec),
            '{{TOTAL_IGST}}'           => number_format(round($totalIgst, $dec), $dec),
            '{{TOTAL_TAX}}'            => number_format((float)($h->TaxAmount ?? 0), $dec),
            '{{TOTAL_DISCOUNT}}'       => number_format((float)($h->DiscountAmount ?? 0), $dec),
            '{{NET_AMOUNT}}'           => number_format((float)($h->NetAmount ?? 0), $dec),
            '{{AMOUNT_IN_WORDS}}'      => $this->_numberToWords((float)($h->NetAmount ?? 0)),
            '{{UPI_QR_CODE}}'          => $e($org->UpiId ?? ''),
            /** Bank Account */
            '{{BANK_NAME}}'            => $bankName,
            '{{BANK_ACCOUNT_NAME}}'    => $bankAccName,
            '{{BANK_ACCOUNT_NO}}'      => $bankAccNo,
            '{{BANK_IFSC}}'            => $bankIfsc,
            '{{BANK_BRANCH}}'          => $bankBranch,
            '{{BANK_UPI_ID}}'          => $bankUpiId,
            '{{BANK_QR_HTML}}'         => $bankQrHtml,
            /** Signature */
            '{{SIGNATURE_SPACE}}'      => $signatureSpaceHtml,
            /** HSN Summary TOTAL row tokens (match the summed rows in the loop) */
            '{{HSN_TOTAL_TAXABLE}}'    => number_format($hsnTotalTaxable, $dec2),
            '{{HSN_TOTAL_CGST}}'       => number_format(round($hsnTotalCgst, $dec2), $dec2),
            '{{HSN_TOTAL_SGST}}'       => number_format(round($hsnTotalSgst, $dec2), $dec2),
            '{{HSN_TOTAL_IGST}}'       => number_format(round($hsnTotalIgst, $dec2), $dec2),
            '{{HSN_TOTAL_TAX}}'        => number_format($hsnTotalTax, $dec2),
        ];

        $html = $this->_processLoops($tplHtml, $items);
        $html = $this->_processHsnSummary($html, $items);
        $html = $this->_processConditionals($html, $tokens);
        $html = str_replace(array_keys($tokens), array_values($tokens), $html);

        return '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>' .
                    '@page{size:A4;margin:0;}' .
                    'body{margin:10mm;padding:0;background:#fff;box-sizing:border-box;}' .
                    '@media print{body{background:#fff;}}' .
                    '</style></head><body>' . $html . '</body></html>';

    }

    private function _numberToWords(float $amount): string {
        $ones  = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
                  'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
                  'Seventeen', 'Eighteen', 'Nineteen'];
        $tens  = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        $convert = function(int $n) use (&$convert, $ones, $tens): string {
            if ($n === 0)        return '';
            if ($n < 20)         return $ones[$n] . ' ';
            if ($n < 100)        return $tens[(int)($n / 10)] . ' ' . $convert($n % 10);
            if ($n < 1000)       return $ones[(int)($n / 100)] . ' Hundred ' . $convert($n % 100);
            if ($n < 100000)     return $convert((int)($n / 1000)) . 'Thousand ' . $convert($n % 1000);
            if ($n < 10000000)   return $convert((int)($n / 100000)) . 'Lakh ' . $convert($n % 100000);
            return $convert((int)($n / 10000000)) . 'Crore ' . $convert($n % 10000000);
        };

        $rupees = (int) $amount;
        $paise  = (int) round(($amount - $rupees) * 100);

        $words = trim($convert($rupees));
        $result = $words ? $words . ' Rupees' : 'Zero Rupees';
        if ($paise > 0) {
            $result .= ' and ' . trim($convert($paise)) . ' Paise';
        }
        return $result . ' Only';
    }

    private function _processLoops($html, $items) {
        $cur = '₹ ';
        $dec = 2;
        $e   = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES);

        return preg_replace_callback(
            '/\{\{FOREACH:ITEMS\}\}(.*?)\{\{\/FOREACH:ITEMS\}\}/s',
            function ($m) use ($items, $cur, $dec, $e) {
                $rowTpl = $m[1];
                $rows   = '';
                foreach ($items as $i => $item) {
                    $taxPct      = (float)($item->TaxPercentage ?? 0);
                    $taxAmt      = round(
                        (float)($item->CgstAmount ?? 0) +
                        (float)($item->SgstAmount ?? 0) +
                        (float)($item->IgstAmount ?? 0), $dec
                    );
                    $unitPrice   = (float)($item->UnitPrice ?? 0);
                    $qty         = (float)($item->Quantity ?? 0);
                    $taxableVal  = round($unitPrice * $qty, $dec);

                    $map = [
                        '{{ITEM.SNO}}'          => $i + 1,
                        '{{ITEM.PRODUCT_NAME}}' => $e($item->ProductName ?? ''),
                        '{{ITEM.HSN_CODE}}'     => $e($item->HSNCode ?? $item->HSNSACCode ?? ''),
                        '{{ITEM.UNIT_PRICE}}'   => $cur . number_format($unitPrice, $dec),
                        '{{ITEM.QTY}}'          => $e($item->Quantity ?? ''),
                        '{{ITEM.UNIT}}'         => $e($item->PrimaryUnitName ?? ''),
                        '{{ITEM.TAXABLE_VALUE}}'=> $cur . number_format($taxableVal, $dec),
                        '{{ITEM.TAX_PCT}}'      => number_format($taxPct, 2),
                        '{{ITEM.TAX_AMT}}'      => $cur . number_format($taxAmt, $dec),
                        '{{ITEM.NET_AMOUNT}}'   => $cur . number_format((float)($item->NetAmount ?? 0), $dec),
                        '{{ITEM.DISCOUNT}}'     => $cur . number_format((float)($item->DiscountAmount ?? 0), $dec),
                        '{{ITEM.PART_NUMBER}}'  => $e($item->PartNumber ?? ''),
                    ];
                    $rows .= str_replace(array_keys($map), array_values($map), $rowTpl);
                }
                return $rows;
            },
            $html
        );
    }

    private function _processHsnSummary($html, $items) {
        $cur = '₹ ';
        $dec = 2;
        $e   = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES);

        return preg_replace_callback(
            '/\{\{FOREACH:HSN_SUMMARY\}\}(.*?)\{\{\/FOREACH:HSN_SUMMARY\}\}/s',
            function ($m) use ($items, $cur, $dec, $e) {
                $rowTpl = $m[1];

                // Group line items by HSN code + tax rate
                $groups = [];
                foreach ($items as $item) {
                    $hsn    = (string)($item->HSNCode ?? $item->HSNSACCode ?? '');
                    $taxPct = (float)($item->TaxPercentage ?? 0);
                    $key    = $hsn . '||' . $taxPct;
                    if (!isset($groups[$key])) {
                        $groups[$key] = [
                            'hsn'          => $hsn,
                            'taxPct'       => $taxPct,
                            'taxableValue' => 0.0,
                            'cgstAmt'      => 0.0,
                            'sgstAmt'      => 0.0,
                            'igstAmt'      => 0.0,
                        ];
                    }
                    $groups[$key]['taxableValue'] += round((float)($item->UnitPrice ?? 0) * (float)($item->Quantity ?? 0), $dec);
                    $groups[$key]['cgstAmt']      += (float)($item->CgstAmount ?? 0);
                    $groups[$key]['sgstAmt']      += (float)($item->SgstAmount ?? 0);
                    $groups[$key]['igstAmt']      += (float)($item->IgstAmount ?? 0);
                }

                $rows = '';
                $sno  = 1;
                foreach ($groups as $g) {
                    $cgstAmt  = round($g['cgstAmt'], $dec);
                    $sgstAmt  = round($g['sgstAmt'], $dec);
                    $igstAmt  = round($g['igstAmt'], $dec);
                    $totalTax = round($cgstAmt + $sgstAmt + $igstAmt, $dec);
                    // Split rate: CGST = SGST = half of total tax %
                    $splitRate = $g['taxPct'] / 2;
                    $map = [
                        '{{HSN.SNO}}'           => $sno++,
                        '{{HSN.CODE}}'          => $e($g['hsn']),
                        '{{HSN.TAXABLE_VALUE}}' => number_format($g['taxableValue'], $dec),
                        // Rate tokens — plain numbers, no % suffix (add % in template if needed)
                        '{{HSN.TAX_RATE}}'      => number_format($g['taxPct'], 0),
                        '{{HSN.CGST_RATE}}'     => number_format($splitRate, 0),
                        '{{HSN.SGST_RATE}}'     => number_format($splitRate, 0),
                        '{{HSN.IGST_RATE}}'     => number_format($g['taxPct'], 0),
                        // Amount tokens
                        '{{HSN.CGST_AMT}}'      => number_format($cgstAmt, $dec),
                        '{{HSN.SGST_AMT}}'      => number_format($sgstAmt, $dec),
                        '{{HSN.IGST_AMT}}'      => number_format($igstAmt, $dec),
                        // Combined tax for this HSN row (CGST+SGST OR IGST — whichever applies)
                        '{{HSN.TAX_AMT}}'       => number_format($totalTax, $dec),
                        '{{HSN.TOTAL_TAX}}'     => number_format($totalTax, $dec),
                    ];
                    $rows .= str_replace(array_keys($map), array_values($map), $rowTpl);
                }
                return $rows;
            },
            $html
        );
    }

    private function _processConditionals($html, $tokens) {
        return preg_replace_callback(
            '/\{\{IF:([A-Z0-9_]+)\}\}(.*?)\{\{\/IF:\1\}\}/s',
            function ($m) use ($tokens) {
                $value = trim($tokens['{{' . $m[1] . '}}'] ?? '');
                return $value !== '' ? $m[2] : '';
            },
            $html
        );
    }

    private function _renderGenericA4Html($h, $items, $org) {
        $cur   = '₹ ';
        $dec   = 2;
        $e     = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES);
        $fmt   = function($date) { if (!$date) return '—'; $d = date_create($date); return $d ? date_format($d, 'd M Y') : $date; };
        $label = strtoupper($h->TransType ?? 'Document');
        $partyLabel = in_array($label, ['PURCHASE ORDER', 'PURCHASE BILL']) ? 'Vendor' : 'Customer';

        $rows = '';
        foreach ($items as $i => $item) {
            $rows .= '<tr>' .
                '<td style="text-align:center">' . ($i + 1) . '</td>' .
                '<td>' . $e($item->ProductName) . '</td>' .
                '<td style="text-align:center">' . $e($item->Quantity) . ' ' . $e($item->PrimaryUnitName ?? '') . '</td>' .
                '<td style="text-align:right">' . $cur . number_format((float)($item->UnitPrice ?? 0), $dec) . '</td>' .
                '<td style="text-align:right">' . $cur . number_format((float)($item->NetAmount ?? 0), $dec) . '</td>' .
                '</tr>';
        }

        return '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>' .
            '@page{size:A4;margin:0;}' .
            'body{font-family:Arial,sans-serif;font-size:12px;margin:0;padding:0;background:#fff;}' .
            '.page{padding:15mm;}' .
            'table{width:100%;border-collapse:collapse;}th,td{border:1px solid #ddd;padding:6px 8px;font-size:11px;}' .
            'th{background:#f5f5f5;font-weight:bold;}' .
            '@media print{body{background:#fff;}}' .
            '</style></head><body><div class="page">' .
            '<div style="display:flex;justify-content:space-between;margin-bottom:12px">' .
                '<div><strong style="font-size:14px">' . $e($org->BrandName ?? $org->Name ?? '') . '</strong>' .
                (!empty($org->GSTIN) ? '<br><span style="color:#666">GSTIN: ' . $e($org->GSTIN) . '</span>' : '') . '</div>' .
                '<div style="text-align:right"><strong style="font-size:16px">' . $label . '</strong><br>' .
                '<span style="color:#666">' . $e($h->UniqueNumber ?? '—') . '</span><br>' .
                '<span style="color:#666">Date: ' . $fmt($h->TransDate ?? '') . '</span>' .
                (!empty($h->ValidityDate) ? '<br><span style="color:#666">Valid Until: ' . $fmt($h->ValidityDate) . '</span>' : '') . '</div>' .
            '</div>' .
            '<div style="background:#f9f9f9;padding:8px;border-radius:4px;margin-bottom:12px">' .
                '<strong>' . $partyLabel . ':</strong> ' . $e($h->PartyName ?? '—') . '</div>' .
            '<table><thead><tr><th style="width:30px">#</th><th>Product</th>' .
                '<th style="width:60px;text-align:center">Qty</th>' .
                '<th style="width:90px;text-align:right">Unit Price</th>' .
                '<th style="width:90px;text-align:right">Amount</th></tr></thead>' .
            '<tbody>' . $rows . '</tbody><tfoot>' .
                '<tr><td colspan="4" style="text-align:right;font-weight:bold">Sub Total</td><td style="text-align:right">' . $cur . number_format((float)($h->SubTotal ?? 0), $dec) . '</td></tr>' .
                ((float)($h->DiscountAmount ?? 0) > 0 ? '<tr><td colspan="4" style="text-align:right;color:#c00">Discount</td><td style="text-align:right;color:#c00">- ' . $cur . number_format((float)$h->DiscountAmount, $dec) . '</td></tr>' : '') .
                ((float)($h->TaxAmount ?? 0) > 0 ? '<tr><td colspan="4" style="text-align:right">Tax</td><td style="text-align:right">' . $cur . number_format((float)$h->TaxAmount, $dec) . '</td></tr>' : '') .
                '<tr><td colspan="4" style="text-align:right;font-weight:bold">Net Amount</td><td style="text-align:right;font-weight:bold">' . $cur . number_format((float)($h->NetAmount ?? 0), $dec) . '</td></tr>' .
            '</tfoot></table>' .
            (!empty($h->Notes) ? '<p style="margin-top:12px;font-size:11px;color:#666"><strong>Notes:</strong> ' . $e($h->Notes) . '</p>' : '') .
            (!empty($h->TermsConditions) ? '<p style="font-size:11px;color:#666"><strong>Terms:</strong> ' . $e($h->TermsConditions) . '</p>' : '') .
        '</div></body></html>';
    }

    public function searchTransProducts() {

        $this->EndReturnData = new stdClass();
		try {

            $term = $this->input->get('term') ? trim($this->input->get('term')) : '';
            $catgUid = $this->input->get('categuid') ? (int) $this->input->get('categuid') : 0;
            $whereArr = [];
            if($catgUid) {
                $whereArr['product.CategoryUID'] = $catgUid;
            }

            $this->load->model('transactions_model');
            $productData = $this->transactions_model->getTransProductsDetails($term, $whereArr);

            $GeneralSettings = $this->redis_cache->get('Redis_UserGenSettings')->Value ?? NULL;

            $retProdDetails = [];
            foreach ($productData as $value) {

                $sellingPrice = (float) $value->SellingPrice;
                $taxPercent = (float) $value->TaxPercentage;

                $unitPrice = smartDecimal($sellingPrice / (1 + ($taxPercent / 100)), 8);
                $taxAmount = smartDecimal($sellingPrice - $unitPrice, $GeneralSettings->DecimalPoints, true);

                $formData = [
                    'id'   => (int) $value->ProductUID,
                    'text' => $value->ItemName,
                    'itemName' => $value->ItemName,
                    'productType' => $value->ProductType,
                    'unitPrice' => (float) $unitPrice,
                    'taxAmount' => (float) $taxAmount,
                    'sellingPrice' => (float) smartDecimal($sellingPrice, $GeneralSettings->DecimalPoints, true),
                    'purchasePrice' => (float) smartDecimal($value->PurchasePrice, $GeneralSettings->DecimalPoints, true),
                    "availableQuantity" => (float) $value->AvailableQuantity,
                    "hsnCode" => $value->HSNSACCode,
                    "category" => $value->CatgName,
                    "taxPercent" => (float) $taxPercent,
                    "cgstPercent" => (float) $value->CGST,
                    "sgstPercent" => (float) $value->SGST,
                    "igstPercent" => (float) $value->IGST,
                    "discount" => (float) smartDecimal($value->Discount),
                    "discountType" => $value->DiscountTypeName,
                    "primaryUnit" => $value->priUnitShortName,
                ];

                $retProdDetails[] = $formData;

            }
            $this->EndReturnData->Lists = $retProdDetails;
            $this->EndReturnData->Error = false;

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);
        
    }

}