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

    public function searchVendors() {

        $this->EndReturnData = new stdClass();
        try {

            $term = $this->input->get('term') ? trim($this->input->get('term')) : '';

            $this->load->model('vendors_model');
            $orgUID = $this->pageData['JwtData']->User->OrgUID;
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

            $transUID   = (int) $this->input->get_post('TransUID');
            $moduleUID  = (int) $this->input->get_post('ModuleUID');
            $printType  = $this->input->get_post('PrintType') ?: 'a4'; // 'thermal' | 'a4'
            $orgUID     = $this->pageData['JwtData']->User->OrgUID;
            $isThermal  = ($printType === 'thermal');

            if ($transUID  <= 0) throw new Exception('Invalid transaction.');
            if ($moduleUID <= 0) throw new Exception('ModuleUID is required.');

            $this->load->model('transactions_model');
            $header = $this->transactions_model->getTransactionById($transUID, $orgUID, $moduleUID);
            if (!$header) throw new Exception('Transaction not found.');

            $items     = $this->transactions_model->getTransactionItems($transUID, $orgUID);
            $payments  = $this->transactions_model->getTransactionPayments($transUID, $orgUID);
            $paidTotal = array_sum(array_map(function ($p) { return (float) $p->Amount; }, $payments));
            $attachments = $this->transactions_model->getTransactionAttachments($transUID, $orgUID);

            $this->load->model('organisation_model');
            $orgInfo          = $this->organisation_model->getOrgForReceipt($orgUID);
            $thermalCfgResult = $this->organisation_model->getThermalPrintConfigByModule($orgUID, $moduleUID);

            $this->EndReturnData->Error         = FALSE;
            $this->EndReturnData->Header        = $header;
            $this->EndReturnData->Items         = $items;
            $this->EndReturnData->Payments      = $payments;
            $this->EndReturnData->PaidTotal     = $paidTotal;
            $this->EndReturnData->Attachments   = $attachments;
            $this->EndReturnData->OrgInfo       = $orgInfo->Data ?? null;
            $this->EndReturnData->ThermalConfig = $thermalCfgResult->Data ?? null;

            // PrintTheme and PrintHtml are only needed for A4/A5 preview — skip for thermal
            if (!$isThermal) {
                $printThemeResult = $this->organisation_model->getPrintThemeByType($orgUID, $header->TransType);
                $printBankAccount = $this->transactions_model->getPrintBankAccount($orgUID);
                $this->EndReturnData->PrintTheme = $printThemeResult->Data ?? null;
                $this->EndReturnData->PrintHtml  = null;
                try {
                    $this->EndReturnData->PrintHtml = $this->transactions_model->_renderA4Html($moduleUID, $header, $items, $orgInfo->Data ?? null, $printThemeResult->Data ?? null, $printBankAccount);
                } catch (Exception $renderEx) {
                    $this->EndReturnData->PrintHtml = '<div style="padding:20px;color:#c00;">Preview error: ' . htmlspecialchars($renderEx->getMessage()) . '</div>';
                }
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

    // ----------------------------------------------------------------
    // POST /transactions/downloadA4Pdf
    // Renders the transaction as HTML, converts to PDF via DomPDF,
    // and streams it as a file download.
    // ----------------------------------------------------------------
    public function downloadA4Pdf() {

        try {

            $transUID  = (int) $this->input->get_post('TransUID');
            $moduleUID = (int) $this->input->get_post('ModuleUID');
            $orgUID    = $this->pageData['JwtData']->User->OrgUID;

            if ($transUID  <= 0) throw new Exception('Invalid transaction.');
            if ($moduleUID <= 0) throw new Exception('ModuleUID is required.');

            $this->load->model('transactions_model');
            $header = $this->transactions_model->getTransactionById($transUID, $orgUID, $moduleUID);
            if (!$header) throw new Exception('Transaction not found.');

            $this->load->model('organisation_model');
            $orgInfo          = $this->organisation_model->getOrgForReceipt($orgUID);
            $printThemeResult = $this->organisation_model->getPrintThemeByType($orgUID, $header->TransType);
            $printBankAccount = $this->transactions_model->getPrintBankAccount($orgUID);

            $items = $this->transactions_model->getTransactionItems($transUID, $orgUID);
            $html  = $this->transactions_model->_renderA4Html($moduleUID, $header, $items, $orgInfo->Data ?? null, $printThemeResult->Data ?? null, $printBankAccount);

            // ── PDF-specific HTML adjustments ────────────────────────
            $paperSize  = strtoupper(trim($this->input->get_post('PaperSize') ?: 'A4'));
            $fontFamily = $printThemeResult->Data->FontFamily ?? 'Arial';

            // 1. Strip Google Fonts link — Dompdf cannot load WOFF2/web fonts.
            //    The font-family !important rule in _renderA4Html falls back to Arial in Dompdf.
            $html = preg_replace('/<link[^>]*fonts\.googleapis\.com[^>]*>/i', '', $html);

            // 2. PDF layout overrides — body padding is for browser preview only;
            //    @page margin handles spacing in the PDF.
            $html = str_replace('</head>',
                '<style>'
                . 'body{padding:0!important;margin:0!important;}'
                . '.print-content{margin:0!important;}'
                . '#trans-type-header td{border-left:none!important;border-right:none!important;}'
                . '</style></head>',
                $html);

            // 3. Composite QR + logo into a single base64 PNG (Dompdf can't do CSS positioning)
            $html = $this->_compositeQrForPdf($html);

            // 4. Dompdf CSS compatibility fixes
            $html = preg_replace('/\bdisplay\s*:\s*flex\s*;?/i',                       'display:block;', $html);
            $html = preg_replace('/\bflex-direction\s*:[^;"}]+;?/i',                   '', $html);
            $html = preg_replace('/\bjustify-content\s*:[^;"}]+;?/i',                  '', $html);
            $html = preg_replace('/\balign-items\s*:[^;"}]+;?/i',                      '', $html);
            $html = preg_replace('/\bheight\s*:\s*100%\s*;?/i',                        '', $html);
            $html = preg_replace('/\bposition\s*:\s*(absolute|relative|fixed)\s*;?/i', '', $html);
            $html = preg_replace('/\btransform\s*:[^;"}]+;?/i',                        '', $html);
            $html = preg_replace('/\btop\s*:\s*[^;"}]+;?/i',                           '', $html);
            $html = preg_replace('/\bleft\s*:\s*[^;"}]+;?/i',                          '', $html);

            // 5. Page size — body padding removed above, so @page margin is the only spacing
            $html = preg_replace('/@page\s*\{[^}]*\}/', "@page{size:{$paperSize};margin:10mm 5mm;}", $html);

            // ── DomPDF ──────────────────────────────────────────────
            require_once FCPATH . 'vendor/autoload.php';

            $options = new \Dompdf\Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'Arial');
            $options->set('chroot', FCPATH);

            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper(strtolower($paperSize), 'portrait');
            $dompdf->render();

            $filename = preg_replace('/[^A-Za-z0-9_\-]/', '_', $header->UniqueNumber ?? ('Trans_' . $transUID)) . '.pdf';

            // Stream PDF to browser as download
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: private, max-age=0, must-revalidate');
            echo $dompdf->output();
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

            $GeneralSettings = $this->redisservice->getUserCache('settings') ?? NULL;

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

}