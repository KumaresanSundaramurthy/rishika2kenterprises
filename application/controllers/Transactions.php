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

            $this->EndReturnData->Error         = FALSE;
            $this->EndReturnData->Header        = $header;
            $this->EndReturnData->Items         = $items;
            $this->EndReturnData->OrgInfo       = $orgInfo->Data ?? null;
            $this->EndReturnData->ThermalConfig = $thermalCfgResult->Data ?? null;
            $this->EndReturnData->PrintTheme    = $printThemeResult->Data ?? null;
            $this->EndReturnData->PrintHtml     = $this->_renderA4Html($header, $items, $orgInfo->Data ?? null, $printThemeResult->Data ?? null);

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
    private function _renderA4Html($h, $items, $org, $theme) {

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
        $addr = fn($l1,$l2,$city,$state,$pin) => implode(', ', array_filter([$l1,$l2,$city,$state,$pin]));

        // ── Items table ──────────────────────────────────────────────
        $itemRows = '';
        foreach ($items as $i => $item) {
            $taxAmt = round((float)($item->CgstAmount ?? 0) + (float)($item->SgstAmount ?? 0) + (float)($item->IgstAmount ?? 0), $dec);
            $itemRows .=
                '<tr>' .
                '<td style="text-align:center">' . ($i + 1) . '</td>' .
                '<td>' . $e($item->ProductName) . '</td>' .
                '<td style="text-align:center">' . $e($item->HSNCode ?? '') . '</td>' .
                '<td style="text-align:right">'  . $cur . number_format((float)($item->UnitPrice ?? 0), $dec) . '</td>' .
                '<td style="text-align:center">' . $e($item->Quantity) . ' ' . $e($item->PrimaryUnitName ?? '') . '</td>' .
                '<td style="text-align:right">'  . $cur . number_format((float)($item->UnitPrice ?? 0) * (float)($item->Quantity ?? 0), $dec) . '</td>' .
                '<td style="text-align:right">'  . $cur . number_format($taxAmt, $dec) . '</td>' .
                '<td style="text-align:right">'  . $cur . number_format((float)($item->NetAmount ?? 0), $dec) . '</td>' .
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

        // ── Addresses ────────────────────────────────────────────────
        $billAddr = $addr($h->BillLine1 ?? '', $h->BillLine2 ?? '', $h->BillCity ?? '', $h->BillState ?? '', $h->BillPincode ?? '')
                 ?: $addr($h->ShipLine1 ?? '', $h->ShipLine2 ?? '', $h->ShipCity ?? '', $h->ShipState ?? '', $h->ShipPincode ?? '');
        $shipAddr = $addr($h->ShipLine1 ?? '', $h->ShipLine2 ?? '', $h->ShipCity ?? '', $h->ShipState ?? '', $h->ShipPincode ?? '')
                 ?: $billAddr;

        // ── Org logo ─────────────────────────────────────────────────
        $logoHtml = !empty($org->Logo) ? '<img src="' . $e($org->Logo) . '" style="max-width:80px;max-height:60px;" alt="Logo">' : '';

        // ── Org address lines ────────────────────────────────────────
        $orgAddr1     = $e($org->Line1 ?? '');
        $orgAddr2     = $e($org->Line2 ?? '');
        $orgCityState = implode(', ', array_filter([$org->CityText ?? '', $org->StateText ?? '']));
        $orgInfoLines = implode('<br>', array_filter([$orgAddr1, $orgAddr2, $orgCityState, $e($org->Pincode ?? ''), $e($org->GSTIN ?? '')]));

        // ── Notes + Terms ────────────────────────────────────────────
        $notesPart = !empty($h->Notes)           ? '<p style="font-size:8pt;margin-top:4px;"><strong>Notes:</strong> ' . $e($h->Notes) . '</p>' : '';
        $termsPart = !empty($h->TermsConditions) ? '<p style="font-size:8pt;margin-top:4px;"><strong>Terms:</strong> ' . $e($h->TermsConditions) . '</p>' : '';

        // ── Token map ────────────────────────────────────────────────
        $tokens = [
            '{{PRIMARY_COLOR}}'        => $theme->PrimaryColor  ?? '#1a3c6e',
            '{{ACCENT_COLOR}}'         => $theme->AccentColor   ?? '#f59e0b',
            '{{FONT_FAMILY}}'          => $theme->FontFamily    ?? 'Arial',
            '{{FONT_SIZE_PX}}'         => ($theme->FontSizePx   ?? 11) . 'px',
            '{{FONT_SIZE}}'            => ($theme->FontSizePx   ?? 11) . 'px',
            '{{ORG_NAME}}'             => $e($org->BrandName ?? $org->Name ?? ''),
            '{{ORG_GSTIN}}'            => $e($org->GSTIN ?? ''),
            '{{ORG_ADDRESS_1}}'        => $orgAddr1,
            '{{ORG_ADDRESS_2}}'        => $orgAddr2,
            '{{ORG_CITY_STATE}}'       => $e($orgCityState),
            '{{ORG_PINCODE}}'          => $e($org->Pincode ?? ''),
            '{{ORG_PHONE}}'            => $e($org->MobileNumber ?? ''),
            '{{ORG_EMAIL}}'            => $e($org->EmailAddress ?? ''),
            '{{ORG_LOGO}}'             => $logoHtml,
            '{{ORG_BANK_NAME}}'        => $e($org->BankName ?? ''),
            '{{ORG_ACCOUNT_NO}}'       => $e($org->AccountNo ?? ''),
            '{{ORG_IFSC}}'             => $e($org->IFSC ?? ''),
            '{{ORG_BRANCH}}'           => $e($org->Branch ?? ''),
            '{{ORG_UPI_ID}}'           => $e($org->UpiId ?? ''),
            '{{ORG_INFO_LINES}}'       => $orgInfoLines,
            '{{DOC_TYPE}}'             => $e($h->TransType ?? 'Document'),
            '{{DOC_NUMBER}}'           => $e($h->UniqueNumber ?? '—'),
            '{{DOC_DATE}}'             => $fmt($h->TransDate ?? ''),
            '{{DOC_VALID_UNTIL}}'      => $fmt($h->ValidityDate ?? ''),
            '{{DUE_DATE}}'             => $fmt($h->ValidityDate ?? ''),
            '{{PARTY_NAME}}'           => $e($h->PartyName ?? '—'),
            '{{CUSTOMER_NAME}}'        => $e($h->PartyName ?? '—'),
            '{{PARTY_PHONE}}'          => $e($h->PartyMobile ?? ''),
            '{{CUSTOMER_PHONE_LINE}}'  => !empty($h->PartyMobile) ? 'Ph: ' . $e($h->PartyMobile) : '',
            '{{PARTY_GSTIN}}'          => $e($h->PartyGSTIN ?? ''),
            '{{BILLING_ADDRESS}}'      => $e($billAddr),
            '{{BILLING_ADDRESS_LINES}}'=> $e($billAddr),
            '{{SHIPPING_ADDRESS}}'     => $e($shipAddr),
            '{{SHIPPING_ADDRESS_LINES}}' => $e($shipAddr),
            '{{PLACE_OF_SUPPLY}}'      => $e($h->PlaceOfSupply ?? $org->StateText ?? ''),
            '{{ITEMS_TABLE}}'          => $itemsTable,
            '{{ITEMS_TABLE_ROWS}}'     => $itemRows,
            '{{TOTALS_SECTION}}'       => $totals,
            '{{TOTALS_BLOCK}}'         => $totals,
            '{{AMOUNT_IN_WORDS}}'      => $e($h->AmountInWords ?? ''),
            '{{NOTES_TERMS}}'          => $notesPart . $termsPart,
            '{{FOOTER_TEXT}}'          => $e($theme->FooterText ?? 'Thank you for your business!'),
            '{{TERMS_CONDITIONS}}'     => $e($h->TermsConditions ?? ''),
            '{{HSN_TAX_TABLE}}'        => '',
            '{{BANK_DETAILS_LINES}}'   => implode('<br>', array_filter([$e($org->BankName ?? ''), !empty($org->AccountNo) ? 'A/C: ' . $e($org->AccountNo) : '', !empty($org->IFSC) ? 'IFSC: ' . $e($org->IFSC) : ''])),
            '{{UPI_QR_CODE}}'          => $e($org->UpiId ?? ''),
        ];

        $html = str_replace(array_keys($tokens), array_values($tokens), $tplHtml);

        return '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>' .
            'body{margin:0;padding:0;background:#fff;}' .
            '@media print{body{background:#fff;}}' .
            '</style></head><body>' . $html . '</body></html>';
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
            'body{font-family:Arial,sans-serif;font-size:12px;margin:0;padding:0;}' .
            '.page{padding:20px;}' .
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