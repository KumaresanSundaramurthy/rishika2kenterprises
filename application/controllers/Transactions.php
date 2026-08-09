<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Transactions extends MY_Controller {

    protected $EndReturnData;

    public function __construct() {
        parent::__construct();

    }

    // ----------------------------------------------------------------
    // GET /transactions/getPageDetails/{moduleUID}/{pageNo}
    // Single endpoint replacing get{Module}PageDetails in all 9 controllers.
    // Sets $this->pageModuleUID so MY_Controller helpers query the right module.
    // ----------------------------------------------------------------
    public function getPageDetails(int $moduleUID = 0, int $pageNo = 0): void {
        $this->pageModuleUID  = $moduleUID;
        $this->EndReturnData  = new stdClass();
        try {
            if ($moduleUID <= 0) throw new Exception('Invalid module.');

            $map = [
                101 => ['view' => 'transactions/quotations/list',       'wa' => true,  'stats' => true ],
                102 => ['view' => 'transactions/salesorders/list',      'wa' => true,  'stats' => true ],
                103 => ['view' => 'transactions/invoices/list',         'wa' => true,  'stats' => true ],
                104 => ['view' => 'transactions/purchaseorders/list',   'wa' => false, 'stats' => false],
                105 => ['view' => 'transactions/purchases/list',        'wa' => false, 'stats' => false],
                106 => ['view' => 'transactions/salesreturns/list',     'wa' => false, 'stats' => true ],
                108 => ['view' => 'transactions/purchasereturns/list',  'wa' => false, 'stats' => false],
                112 => ['view' => 'transactions/deliverychallans/list', 'wa' => false, 'stats' => false],
                113 => ['view' => 'transactions/proformainvoices/list', 'wa' => false, 'stats' => false],
            ];

            if (!isset($map[$moduleUID])) throw new Exception('Unknown module.');

            $cfg       = $map[$moduleUID];
            $extraData = [];

            if ($cfg['wa']) {
                $orgUID    = (int)$this->pageData['JwtData']->Org->OrgUID;
                $this->load->model('organisation_model');
                $templates = $this->organisation_model->getModuleMessageTemplates($orgUID, $moduleUID);
                $extraData['WhatsAppTemplate'] = $templates['WhatsApp'] ?? null;
            }

            $this->EndReturnData = $this->_buildTransactionPageDetailsResult([
                'pageNo'              => $pageNo,
                'listViewPath'        => $cfg['view'],
                'paginationUrl'       => '/transactions/getPageDetails/' . $moduleUID,
                'listViewExtraData'   => $extraData,
                'includeSummaryStats' => $cfg['stats'],
            ]);

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ----------------------------------------------------------------
    // GET  /transactions/getTransactionPrefixes
    // Returns all org-level prefixes (shared across all transaction types)
    // ----------------------------------------------------------------
    public function getTransactionPrefixes() {

        $this->EndReturnData = new stdClass();
        try {

            $orgUID = $this->pageData['JwtData']->Org->OrgUID;

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
                'OrgUID'           => $this->pageData['JwtData']->Org->OrgUID,
                'Name'             => strtoupper(getPostValue($PostData, 'transPrefixName')),
                'IncludeFiscalYear'=> getPostValue($PostData, 'includeFiscalYear') ? 1 : 0,
                'FiscalYearFormat' => in_array(getPostValue($PostData, 'fiscalYearFormat'), ['SHORT','LONG'])
                                        ? getPostValue($PostData, 'fiscalYearFormat') : 'SHORT',
                'IncludeShortName' => getPostValue($PostData, 'includeShortName') ? 1 : 0,
                'ShortName'        => strtoupper(substr(getPostValue($PostData, 'companyShortName') ?? '', 0, 20)),
                'Separator'        => getPostValue($PostData, 'prefixSeparator') ?: '-',
                'NumberPadding'    => (int)(getPostValue($PostData, 'numberPadding') ?: 1),
                'CreatedBy'        => $userUID,
                'UpdatedBy'        => $userUID,
            ];

            $this->load->model('dbwrite_model');
            $getResp = $this->dbwrite_model->insertData('Settings', 'TransactionPrefixTbl', $addFormData);
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
            $orgUID  = $this->pageData['JwtData']->Org->OrgUID;

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
                'Settings', 'TransactionPrefixTbl',
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
            $orgUID  = $this->pageData['JwtData']->Org->OrgUID;

            $this->load->model('dbwrite_model');
            $resp = $this->dbwrite_model->updateData(
                'Settings', 'TransactionPrefixTbl',
                ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID],
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

            $orgUID  = $this->pageData['JwtData']->Org->OrgUID;
            $userUID = $this->pageData['JwtData']->User->UserUID;

            $this->load->model('transactions_model');

            // Clear default flag for all org prefixes, then set the chosen one
            $resp = $this->dbwrite_model->updateData(
                'Settings', 'TransactionPrefixTbl',
                ['IsDefault' => 0, 'UpdatedBy' => $userUID],
                ['OrgUID' => $orgUID]
            );
            if ($resp->Error) throw new Exception($resp->Message);

            $updresp = $this->dbwrite_model->updateData(
                'Settings', 'TransactionPrefixTbl',
                ['IsDefault' => 1, 'UpdatedBy' => $userUID],
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

    public function searchVendors() {

        $this->EndReturnData = new stdClass();
        try {

            $term = $this->input->get('term') ? trim($this->input->get('term')) : '';

            $this->load->model('vendors_model');
            $orgUID = $this->pageData['JwtData']->Org->OrgUID;
            $filter = !empty($term) ? ['SearchAllData' => $term] : [];
            $result = $this->vendors_model->getVendorListPaginated($orgUID, 20, 0, $filter);

            $vendorDetails = [];
            foreach ($result->rows as $value) {
                $vendorDetails[] = [
                    'id'          => $value->VendorUID,
                    'text'        => !empty($value->Area)
                        ? $value->Name . ' (' . $value->Area . ')'
                        : $value->Name,
                    'name'        => $value->Name,
                    'area'        => $value->Area ?? '',
                    'companyName' => $value->CompanyName ?? '',
                    'balance'     => (float)($value->ClosingBalance ?? 0),
                    'balanceType' => $value->ClosingBalanceType ?? 'Credit',
                ];
            }

            $this->EndReturnData->Lists = $vendorDetails;
            $this->EndReturnData->Error = false;

        } catch (Exception $e) {
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
                $balance     = (float)($value->CustomerBalance ?? 0);
                $balanceType = $value->BalanceType ?? 'Debit';
                $formData = [
                    'id'          => $value->CustomerUID,
                    'text'        => $value->Area
                        ? $value->Name . ' (' . $value->Area . ')'
                        : $value->Name,
                    'name'        => $value->Name,
                    'area'        => $value->Area ?? '',
                    'balance'     => $balance,
                    'balanceType' => $balanceType,
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
            $printType = $this->input->get_post('PrintType') ?: 'a4'; // 'a4' | 'thermal' | 'view'
            $orgUID    = $this->pageData['JwtData']->Org->OrgUID;

            if ($transUID  <= 0) throw new Exception('Invalid transaction.');
            if ($moduleUID <= 0) throw new Exception('ModuleUID is required.');

            $this->load->model('transactions_model');
            $header = $this->transactions_model->getTransactionById($transUID, $orgUID, $moduleUID);
            if (!$header) throw new Exception('Transaction not found.');

            $this->EndReturnData->Error  = FALSE;
            $this->EndReturnData->Header = $header;

            if ($printType === 'a4') {
                // A4 / A5 print — server renders HTML; JS only needs Header + PrintHtml
                $items = $this->transactions_model->getTransactionItems($transUID, $orgUID);
                $this->load->model('organisation_model');
                $orgInfo          = $this->organisation_model->getOrgInfoCached($orgUID);
                $printThemeResult = $this->organisation_model->getPrintThemeByModule($orgUID, $moduleUID);
                $printBankAccount = $this->transactions_model->getPrintBankAccount($orgUID);
                $this->EndReturnData->PrintHtml = null;
                try {
                    $this->EndReturnData->PrintHtml = $this->transactions_model->_renderA4Html(
                        $moduleUID, $header, $items,
                        $orgInfo->Data ?? null,
                        $printThemeResult->Data ?? null,
                        $printBankAccount
                    );
                } catch (Exception $renderEx) {
                    $this->EndReturnData->PrintHtml = '<div style="padding:20px;color:#c00;">Preview error: ' . htmlspecialchars($renderEx->getMessage()) . '</div>';
                }

            } elseif ($printType === 'thermal') {
                // Thermal print — JS builds the receipt; needs Header + Items + OrgInfo + ThermalConfig
                $this->EndReturnData->Items = $this->transactions_model->getTransactionItems($transUID, $orgUID);
                $this->load->model('organisation_model');
                $orgInfo          = $this->organisation_model->getOrgInfoCached($orgUID);
                $thermalCfgResult = $this->organisation_model->getThermalPrintConfigByModule($orgUID, $moduleUID);
                $this->EndReturnData->OrgInfo       = $orgInfo->Data ?? null;
                $this->EndReturnData->ThermalConfig = $thermalCfgResult->Data ?? null;

            } elseif ($printType === 'view') {
                // View modal — JS renders detail panel; needs Header + Items + Payments + PaidTotal + Attachments + OrgInfo
                $this->EndReturnData->Items       = $this->transactions_model->getTransactionItems($transUID, $orgUID);
                $payments                          = $this->transactions_model->getTransactionPayments($transUID, $orgUID);
                $this->EndReturnData->Payments    = $payments;
                $this->EndReturnData->PaidTotal   = array_sum(array_map(fn($p) => (float)$p->Amount, $payments));
                $this->EndReturnData->Attachments = $this->transactions_model->getTransactionAttachments($transUID, $orgUID);
                $this->load->model('organisation_model');
                $orgInfo                          = $this->organisation_model->getOrgInfoCached($orgUID);
                $this->EndReturnData->OrgInfo     = $orgInfo->Data ?? null;

                // AmountInWords — for email template token replacement
                $header->AmountInWords = function_exists('print_number_to_words')
                    ? print_number_to_words((float)($header->NetAmount ?? 0))
                    : '';

                // Permissions — separate object so callers (viewTransModal, future modals) can gate UI actions
                $_nonEditableStatuses = ['Converted', 'Cancelled', 'Rejected'];
                $permissions          = new stdClass();
                $permissions->CanEdit = !in_array($header->DocStatus ?? '', $_nonEditableStatuses);
                $this->EndReturnData->Permissions = $permissions;
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    // Composites the QR code and logo overlay into a single base64 PNG using GD.
    // This is called only for PDF output — Dompdf cannot handle position:absolute overlays.
    private function _compositeQrForPdf(string $html): string {
        $pattern = '/<div[^>]*>\s*<img[^>]+src="(https:\/\/api\.qrserver\.com[^"]+)"[^>]*>\s*<div[^>]*class="qr-logo-overlay"[^>]*>\s*<img[^>]+src="([^"]+)"[^>]*>\s*<\/div>\s*<\/div>/is';

        return preg_replace_callback($pattern, function ($m) {
            $qrUrl   = $m[1];
            $logoUrl = $m[2];

            $qrData = @file_get_contents($qrUrl);
            if (!$qrData) return '<img src="' . htmlspecialchars($qrUrl) . '" width="150" height="150">';

            $qrImg = @imagecreatefromstring($qrData);
            if (!$qrImg) return '<img src="' . htmlspecialchars($qrUrl) . '" width="150" height="150">';

            $logoData = @file_get_contents($logoUrl);
            if ($logoData) {
                $logoImg = @imagecreatefromstring($logoData);
                if ($logoImg) {
                    $qrW      = imagesx($qrImg);
                    $qrH      = imagesy($qrImg);
                    $logoSize = (int)($qrW * 0.25);

                    $logoResized = imagecreatetruecolor($logoSize, $logoSize);
                    imagefill($logoResized, 0, 0, imagecolorallocate($logoResized, 255, 255, 255));
                    imagecopyresampled($logoResized, $logoImg, 0, 0, 0, 0, $logoSize, $logoSize, imagesx($logoImg), imagesy($logoImg));

                    $x       = (int)(($qrW - $logoSize) / 2);
                    $y       = (int)(($qrH - $logoSize) / 2);
                    $padding = 4;
                    $white   = imagecolorallocate($qrImg, 255, 255, 255);
                    imagefilledrectangle($qrImg, $x - $padding, $y - $padding, $x + $logoSize + $padding, $y + $logoSize + $padding, $white);
                    imagecopy($qrImg, $logoResized, $x, $y, 0, 0, $logoSize, $logoSize);

                    imagedestroy($logoResized);
                    imagedestroy($logoImg);
                }
            }

            ob_start();
            imagepng($qrImg);
            $imgData = ob_get_clean();
            imagedestroy($qrImg);

            return '<img src="data:image/png;base64,' . base64_encode($imgData) . '" width="150" height="150">';
        }, $html);
    }

    // ----------------------------------------------------------------
    // POST /transactions/downloadA4Pdf
    // Renders the transaction as HTML, converts to PDF via DomPDF,
    // ── Generic attachment fetch for all modules ─────────────────────────────
    // Single endpoint for all 9 modules: transactions, expenses, indirect income.
    // POST: TransUID, ModuleUID
    // ModuleUID 114 = Expenses, 115 = Indirect Income, all others = standard transactions.
    public function getAttachments() {
        $this->EndReturnData = new stdClass();
        try {
            $transUID  = (int) $this->input->post('TransUID');
            $moduleUID = (int) $this->input->post('ModuleUID');
            $orgUID    = $this->pageData['JwtData']->Org->OrgUID;

            if ($transUID <= 0) throw new Exception('Invalid record.');

            $this->load->model('transactions_model');

            if ($moduleUID === 114) {
                $attachments = $this->transactions_model->getExpenseIncomeAttachments($transUID, $orgUID, 'Expense');
            } elseif ($moduleUID === 115) {
                $attachments = $this->transactions_model->getExpenseIncomeAttachments($transUID, $orgUID, 'IndirectIncome');
            } else {
                $attachments = $this->transactions_model->getTransactionAttachments($transUID, $orgUID);
            }

            $this->EndReturnData->Error       = FALSE;
            $this->EndReturnData->Attachments = $attachments;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Generic PDF base64 for email auto-attach (all transaction modules) ───
    // Replaces the old per-module getQuotationPdfBase64 endpoint.
    // POST: TransUID, ModuleUID, PaperSize
    public function getTransactionPdfBase64() {

        $this->EndReturnData = new stdClass();
        try {

            $transUID  = (int) $this->input->post('TransUID');
            $moduleUID = (int) $this->input->post('ModuleUID');
            $paperSize = strtoupper(trim($this->input->post('PaperSize') ?: 'A4'));
            $orgUID    = $this->pageData['JwtData']->Org->OrgUID;

            if ($transUID  <= 0) throw new Exception('Invalid transaction.');
            if ($moduleUID <= 0) throw new Exception('ModuleUID is required.');

            $this->load->model('transactions_model');
            $header = $this->transactions_model->getTransactionById($transUID, $orgUID, $moduleUID);
            if (!$header) throw new Exception('Transaction not found.');

            // Verify a print template is configured for this transaction type
            $this->load->model('organisation_model');
            $themeResult = $this->organisation_model->getPrintThemeByModule($orgUID, $moduleUID);
            if (empty($themeResult->Data) || empty($themeResult->Data->TemplateHtmlContent)) {
                throw new Exception('Print template not configured for "' . $header->TransType . '". Please set it up in Settings → Print Templates before sending.');
            }

            $pdfBytes = $this->transactions_model->getOrGeneratePdfBytes($transUID, $orgUID, $moduleUID, $paperSize);
            if (!$pdfBytes) throw new Exception('Failed to generate PDF. Please try again.');

            $filename = preg_replace('/[^A-Za-z0-9_\-]/', '_', $header->UniqueNumber ?? ('Trans_' . $transUID)) . '.pdf';

            $this->EndReturnData->Error    = FALSE;
            $this->EndReturnData->Base64   = base64_encode($pdfBytes);
            $this->EndReturnData->Filename = $filename;
            $this->EndReturnData->Size     = strlen($pdfBytes);

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // and streams it as a file download.
    // ----------------------------------------------------------------
    public function downloadA4Pdf() {

        try {

            $transUID  = (int) $this->input->get_post('TransUID');
            $moduleUID = (int) $this->input->get_post('ModuleUID');
            $paperSize = strtoupper(trim($this->input->get_post('PaperSize') ?: 'A4'));
            $orgUID    = $this->pageData['JwtData']->Org->OrgUID;

            if ($transUID  <= 0) throw new Exception('Invalid transaction.');
            if ($moduleUID <= 0) throw new Exception('ModuleUID is required.');

            $this->load->model('transactions_model');
            $header = $this->transactions_model->getTransactionById($transUID, $orgUID, $moduleUID);
            if (!$header) throw new Exception('Transaction not found.');

            $pdfBytes = $this->transactions_model->getOrGeneratePdfBytes($transUID, $orgUID, $moduleUID, $paperSize);
            if (!$pdfBytes) throw new Exception('Failed to generate PDF.');

            $filename = preg_replace('/[^A-Za-z0-9_\-]/', '_', $header->UniqueNumber ?? ('Trans_' . $transUID)) . '.pdf';

            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: private, max-age=0, must-revalidate');
            echo $pdfBytes;
            exit;

        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['Error' => true, 'Message' => $e->getMessage()]);
            exit;
        }

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

            $GeneralSettings = $this->pageData['JwtData']->GenSettings ?? null;

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
                    'purchasePrice'     => (float) smartDecimal($value->PurchasePrice, $GeneralSettings->DecimalPoints, true),
                    'mrp'               => (float) smartDecimal($value->MRP, $GeneralSettings->DecimalPoints, true),
                    'purchasePriceTaxUID' => (int)($value->PurchasePriceProductTaxUID ?? 0),
                    'purchasePriceIsIncl' => ((int)($value->PurchasePriceProductTaxUID ?? 1)) === 1,
                    "availableQuantity" => (float) $value->AvailableQuantity,
                    "hsnCode" => $value->HSNSACCode,
                    "category" => $value->CatgName,
                    "categoryUID" => $value->CategoryUID ? (int) $value->CategoryUID : null,
                    "categoryName" => $value->CatgName ?? '',
                    "partNumber" => $value->PartNumber ?? '',
                    "taxPercent" => (float) $taxPercent,
                    "cgstPercent" => (float) $value->CGST,
                    "sgstPercent" => (float) $value->SGST,
                    "igstPercent" => (float) $value->IGST,
                    "discount" => (float) smartDecimal($value->Discount),
                    "discountType" => $value->DiscountTypeName,
                    "primaryUnit" => $value->priUnitShortName,
                    "description" => $value->Description ?? '',
                    "isComboItem" => (int) $value->IsComboItem,
                    "comboItemCount" => (int) $value->ComboItemCount,
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

    // ── Bulk delete transactions ─────────────────────────────────────────────
    public function deleteMultipleTransactions(int $moduleUID = 0): void {
        $this->EndReturnData = new stdClass();
        try {
            $validModules = [101, 102, 103, 104, 105, 106, 108, 112, 113];
            if (!in_array($moduleUID, $validModules, true)) throw new Exception('Module not supported for bulk delete.');

            $orgUID  = (int)$this->pageData['JwtData']->Org->OrgUID;
            $userUID = (int)$this->pageData['JwtData']->User->UserUID;

            $this->load->model(['transactions_model', 'dbwrite_model']);

            $selectAll = (int)$this->input->post('SelectAll');
            if ($selectAll === 1) {
                $filter = $this->input->post('Filter') ?: [];
                if (!is_array($filter)) $filter = (array)json_decode($filter, true);
                $filter['BranchUID'] = $this->_branchUID();
                $transUIDs = $this->transactions_model->getTransactionUIDsByFilter($orgUID, $moduleUID, $filter);
            } else {
                $raw       = $this->input->post('TransUIDs');
                $transUIDs = is_array($raw)
                    ? array_values(array_filter(array_map('intval', $raw), fn($u) => $u > 0))
                    : [];
            }

            if (empty($transUIDs)) throw new Exception('No records selected.');

            $deleted = 0;
            $errors  = [];
            foreach ($transUIDs as $transUID) {
                try {
                    $this->_deleteSingleTransaction((int)$transUID, $orgUID, $userUID, $moduleUID);
                    $deleted++;
                } catch (Throwable $e) {
                    $errors[] = '#' . $transUID . ': ' . $e->getMessage();
                }
            }

            if ($deleted === 0) throw new Exception(implode('; ', $errors) ?: 'No records deleted.');

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = $deleted . ' record(s) deleted.' .
                (!empty($errors) ? ' Skipped: ' . implode('; ', $errors) : '');
            $this->auditlog->log(
                $orgUID, $userUID,
                'BULK_DELETE_TRANSACTION', 'Transaction', $moduleUID, '',
                [], 'Bulk deleted ' . $deleted . ' transaction(s) for module ' . $moduleUID,
                'Transactions', 'TRANSACTION'
            );

        } catch (Throwable $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    /**
     * @param int $transUID
     * @param int $orgUID
     * @param int $userUID
     * @param int $moduleUID
     * @return void
     * @throws Exception
     */
    private function _deleteSingleTransaction(int $transUID, int $orgUID, int $userUID, int $moduleUID): void {

        $existing = $this->transactions_model->getTransactionById($transUID, $orgUID, $moduleUID);
        if (!$existing) throw new Exception("Transaction #$transUID not found.");

        // Sales Returns: pre-checks before any write
        if ($moduleUID === 106) {
            $creditApplied = $this->transactions_model->getSRCreditApplied($existing->UniqueNumber ?? '');
            if ($creditApplied > 0) {
                throw new Exception('SR has credit applied to invoices. Reverse allocations first.');
            }
            $readDb = $this->load->database('ReadDB', TRUE);
            $readDb->db_debug = FALSE;
            $readDb->from('Transaction.TransCreditNoteTbl');
            $readDb->where([
                'SourceTransUID'  => $transUID,
                'SourceModuleUID' => 106,
                'IsDeleted'       => 0,
                'IsCancelled'     => 0,
                'Status'          => 'Applied',
            ]);
            if ($readDb->get()->num_rows() > 0) {
                throw new Exception('SR credit note is applied to an invoice. Reverse it first.');
            }
        }

        $this->dbwrite_model->startTransaction();

        // Stock reversal (Invoices, Purchases, Sales Returns, Purchase Returns)
        $stockModules = [103, 105, 106, 108];
        if (in_array($moduleUID, $stockModules, true)) {
            $this->dbwrite_model->reverseStockMovements($transUID, $orgUID, $userUID);
            $this->_syncProductCacheByTransUID($transUID);
        } elseif ($moduleUID === 112) {
            // Delivery Challans: only reverse stock if goods were dispatched
            $status = $existing->DocStatus ?? '';
            if (in_array($status, ['Dispatched', 'Delivered', 'Partially Returned', 'Converted'], true)) {
                $this->dbwrite_model->reverseStockMovements($transUID, $orgUID, $userUID);
                $this->_syncProductCacheByTransUID($transUID);
            }
        }

        // Sales Returns: delete linked payments + pending credit notes
        if ($moduleUID === 106) {
            $this->dbwrite_model->updateData(
                'Transaction', 'PaymentsTbl',
                ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID],
                ['TransUID' => $transUID, 'IsDeleted' => 0]
            );
            $wdb = $this->dbwrite_model->getWriteDb();
            $wdb->db_debug = FALSE;
            $wdb->where([
                'SourceTransUID'  => $transUID,
                'SourceModuleUID' => 106,
                'Status'          => 'Pending',
                'IsCancelled'     => 0,
                'IsDeleted'       => 0,
            ])->update('Transaction.TransCreditNoteTbl', [
                'IsDeleted' => 1,
                'UpdatedBy' => $userUID,
            ]);
        }

        $this->dbwrite_model->softDeleteTransactionItems($transUID, $userUID);
        $this->dbwrite_model->softDeleteTransaction($transUID, $orgUID, $userUID);
        $this->dbwrite_model->commitTransaction();

        // Post-commit ledger operations (all non-fatal)

        if ($moduleUID === 103 && ($existing->DocStatus ?? '') !== 'Draft'
            && ($existing->PartyType ?? '') === 'C' && ($existing->PartyUID ?? 0) > 0) {
            try {
                $this->load->library('accountledger');
                $netAmount   = (float)($existing->NetAmount ?? 0);
                $payments    = $this->transactions_model->getTransactionPayments($transUID, $orgUID);
                $alreadyPaid = array_sum(array_column((array)$payments, 'Amount'));
                $remaining   = max(0, round($netAmount - $alreadyPaid, $this->_decimals()));
                if ($remaining > 0) {
                    $this->accountledger->applyLedgerEntry($existing->PartyUID, 'Customer', $remaining, 'Credit', $transUID);
                }
                $this->accountledger->reverseJournal('Invoice', $transUID, $userUID);
            } catch (Throwable $e) {
                log_message('error', 'Bulk inv delete ledger #' . $transUID . ': ' . $e->getMessage());
            }
        }

        if ($moduleUID === 105 && ($existing->DocStatus ?? '') !== 'Draft'
            && ($existing->PartyType ?? '') === 'S' && ($existing->PartyUID ?? 0) > 0) {
            try {
                $this->load->library('accountledger');
                $this->accountledger->applyLedgerEntry($existing->PartyUID, 'Vendor', (float)($existing->NetAmount ?? 0), 'Debit', $transUID);
                $this->accountledger->reverseJournal('Purchase', $transUID, $userUID);
            } catch (Throwable $e) {
                log_message('error', 'Bulk purch delete ledger #' . $transUID . ': ' . $e->getMessage());
            }
            $this->_recalcVendorBalance($orgUID, (int)$existing->PartyUID, $userUID);
        }

        if ($moduleUID === 106 && ($existing->PartyUID ?? 0) > 0) {
            $this->_recalcCustomerBalance($orgUID, (int)$existing->PartyUID, $userUID);
            try {
                $this->load->library('accountledger');
                $this->accountledger->reverseJournal('SalesReturn', $transUID, $userUID);
            } catch (Throwable $e) {
                log_message('error', 'Bulk SR delete ledger #' . $transUID . ': ' . $e->getMessage());
            }
        }

        if ($moduleUID === 108 && ($existing->PartyUID ?? 0) > 0) {
            $this->_recalcVendorBalance($orgUID, (int)$existing->PartyUID, $userUID);
            try {
                $this->load->library('accountledger');
                $this->accountledger->reverseJournal('PurchaseReturn', $transUID, $userUID);
            } catch (Throwable $e) {
                log_message('error', 'Bulk PR delete ledger #' . $transUID . ': ' . $e->getMessage());
            }
        }
    }

}