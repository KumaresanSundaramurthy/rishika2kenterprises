<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Globally extends CI_Controller {

    public $pageData = array();
    private $EndReturnData;

    public function __construct() {
        parent::__construct();

    }

    public function index() {

    }

    public function getCountryInfo() {

        $this->EndReturnData = new stdClass();
		try {
            
            $this->load->model('global_model');
            $GetCountryInfo = $this->global_model->getCountryInfo();
            if($GetCountryInfo->Error === FALSE) {
                $this->EndReturnData->Data = $GetCountryInfo->Data;
            } else {
                throw new Exception($GetCountryInfo->Message);
            }

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Data Retrieved Successfully';

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		$this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;

    }

    public function getStateCityOfCountry() {

        $this->EndReturnData = new stdClass();
        try {

            $CountryCode = strtoupper(trim($this->input->post('CountryCode') ?? ''));
            if (!$CountryCode) {
                throw new Exception('Country Code information is missing.');
            }

            $this->load->model('location_model');

            $StateResult = $this->location_model->getStatesFromDB($CountryCode);
            $this->EndReturnData->StateInfo = ($StateResult->Error === FALSE) ? $StateResult->Data : [];

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Data Retrieved Successfully';

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function getStateofCountry() {

        $this->EndReturnData = new stdClass();
		try {

            $CountryCode = $this->input->post('CountryCode');
            if($CountryCode) {
                
                $this->load->model('global_model');
                $GetStateInfo = $this->global_model->getStateofCountry($CountryCode);
                if($GetStateInfo->Error === FALSE) {
                    $this->EndReturnData->Data = $GetStateInfo->Data;
                }

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = 'Data Retrieved Successfully';

            } else {
                throw new Exception('Country Code information is missing.');
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;

    }

    public function getCityofCountry() {

        $this->EndReturnData = new stdClass();
		try {

            $CountryCode = $this->input->post('CountryCode');
            if($CountryCode) {

                $this->load->model('global_model');
                $GetCityInfo = $this->global_model->getCityofCountry($CountryCode);
                if($GetCityInfo->Error === FALSE) {
                    $this->EndReturnData->Data = $GetCityInfo->Data;
                }

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = 'Data Retrieved Successfully';

            } else {
                throw new Exception('Country Code information is missing.');
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;

    }

    public function getCitiesOfState() {
        $this->EndReturnData = new stdClass();
        try {
            $countryISO2 = strtoupper(trim($this->input->post('CountryISO2') ?? ''));
            $stateISO2   = strtoupper(trim($this->input->post('StateISO2')   ?? ''));
            if (!$countryISO2 || !$stateISO2) throw new Exception('Country and State codes are required.');
            $this->load->model('location_model');
            $result = $this->location_model->getCitiesOfStateFromDB($countryISO2, $stateISO2);
            $this->EndReturnData->Error = $result->Error;
            $this->EndReturnData->Data  = ($result->Error === FALSE) ? $result->Data : [];
            if ($result->Error) $this->EndReturnData->Message = $result->Message;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function getStorageTypeInfo() {

        $this->EndReturnData = new stdClass();
		try {

            $this->load->model('global_model');
            $GetStorageType = $this->global_model->getStorageTypeData();
            if($GetStorageType->Error === FALSE) {
                $this->EndReturnData->Data = $GetStorageType->Data;
            }

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Data Retrieved Successfully';

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;

    }

    public function updatePageSettings() {

        $this->EndReturnData = new stdClass();
		try {

            $AllColumnsInPage = $this->input->post('InPageAllColumns');
            if(!empty($AllColumnsInPage)) {

                $updateDataArr = [];

                $AllCmnIds = explode(',', $AllColumnsInPage);
                foreach($AllCmnIds as $CKey => $CVal) {
                    $updateDataArr[] = [
                        'ViewDataUID' => $CVal,
                        'IsMainPageApplicable' => isset($this->input->post('MainPageFld')[$CVal]) ? 1 : 0,
                        'MainPageOrder' => (isset($this->input->post('MainPageFld')[$CVal]) && isset($this->input->post('MainPageFldSort')[$CVal])) ? $this->input->post('MainPageFldSort')[$CVal] : 1000,
                        'IsPrintPreviewApplicable' => isset($this->input->post('PrintPageFld')[$CVal]) ? 1 : 0,
                        'PrintPreviewOrder' => (isset($this->input->post('PrintPageFld')[$CVal]) && isset($this->input->post('PrintPageFldSort')[$CVal])) ? $this->input->post('PrintPageFldSort')[$CVal] : 1000,
                        'IsExportCsvApplicable' => isset($this->input->post('ExpCsvFld')[$CVal]) ? 1 : 0,
                        'ExportCsvOrder' => (isset($this->input->post('ExpCsvFld')[$CVal]) && isset($this->input->post('ExpCsvFldSort')[$CVal])) ? $this->input->post('ExpCsvFldSort')[$CVal] : 1000,
                        'IsExportExcelApplicable' => isset($this->input->post('ExpXlFld')[$CVal]) ? 1 : 0,
                        'ExportExcelOrder' => (isset($this->input->post('ExpXlFld')[$CVal]) && isset($this->input->post('ExpXlFldSort')[$CVal])) ? $this->input->post('ExpXlFldSort')[$CVal] : 1000,
                        'IsExportPdfApplicable' => isset($this->input->post('ExpPdfFld')[$CVal]) ? 1 : 0,
                        'ExportPdfOrder' => (isset($this->input->post('ExpPdfFld')[$CVal]) && isset($this->input->post('ExpPdfFldSort')[$CVal])) ? $this->input->post('ExpPdfFldSort')[$CVal] : 1000,
                    ];
                }

                $this->load->model('dbwrite_model');
                $InsertDataResp = $this->dbwrite_model->updateBatchData('Modules', 'ViewDataTbl', $updateDataArr, 'ViewDataUID');
                if($InsertDataResp->Error) {
                    throw new Exception($InsertDataResp->Message);
                }

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = "Successfully Updated";

            } else {
                throw new Exception('Oops! Something went wrong.');
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;

    }

    public function getPrintPreviewDetails() {

        $this->EndReturnData = new stdClass();
		try {

            $ModuleId = isset($_GET['ModuleId']) ? $_GET['ModuleId'] : 0;
            if($ModuleId > 0) {

                $Filter = isset($_GET['Filter']) ? json_decode($_GET['Filter'], TRUE) : [];
                $WhereInData = isset($_GET['ExportIds']) ? ['ExportIds' => explode(',', base64_decode($_GET['ExportIds']))] : [];

                $DataResp = $this->globalservice->getModulePageColumnDetails($ModuleId, 'PrintPage', $Filter, $WhereInData, 0, 0);
                if($DataResp->Error === FALSE) {
                    
                    $this->pageData['ViewColumns'] = $DataResp->DispViewColumns;
                    $this->pageData['Aggregates'] = $DataResp->Aggregates;
                    $this->pageData['List'] = $DataResp->DataLists;
                    $this->pageData['previewName'] = isset($_GET['previewName']) ? $_GET['previewName'] : 'Details';

                    $this->pageData['JwtData']->GenSettings = ($this->redisservice->getUserCache('settings')) ?? new stdClass();

                    $this->EndReturnData->Error = FALSE;
                    $this->EndReturnData->HtmlData = $this->load->view('common/print/printpreview', $this->pageData, TRUE);

                } else {
                    throw new Exception('No Records Found.!');
                }

            } else {
                throw new Exception('Oops! Missing Module Information.');
            }

		} catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		$this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;

    }

    public function exportModuleDataDetails() {

        $this->EndReturnData = new stdClass();
		try {

            $ModuleId = isset($_GET['ModuleId']) ? $_GET['ModuleId'] : 0;
            if($ModuleId > 0) {

                $Type = isset($_GET['Type']) ? $_GET['Type'] : '';
                if(!empty($Type)) {

                    if($Type == 'CSV') {
                        $PageType = 'CsvPage';
                    } else if($Type == 'Excel') {
                        $PageType = 'ExcelPage';
                    } else if($Type == 'Pdf') {
                        $PageType = 'PdfPage';
                    }

                    $Filter = isset($_GET['Filter']) ? json_decode($_GET['Filter'], TRUE) : [];
                    $WhereInData = isset($_GET['ExportIds']) ? ['ExportIds' => explode(',', base64_decode($_GET['ExportIds']))] : [];
                    
                    $DataResp = $this->globalservice->getModulePageColumnDetails($ModuleId, $PageType, $Filter, $WhereInData, 0, 0);
                    if($DataResp->Error === FALSE) {                        

                        $FileName = isset($_GET['FileName']) ? $_GET['FileName'] : 'NewFile';
                        $SheetName = isset($_GET['SheetName']) ? $_GET['SheetName'] : 'NewSheet';

                        $GeneralSettings = ($this->redisservice->getUserCache('settings')) ?? new stdClass();
                        $this->pageData['JwtData']->GenSettings = $GeneralSettings;
                        
                        if($Type == 'CSV') {
                            $this->globalservice->exportCSV($FileName, $DataResp->DispViewColumns, $DataResp->DataLists, $DataResp->Aggregates);
                            exit;
                        } else if($Type == 'Excel') {
                            $this->globalservice->exportExcel($FileName, $SheetName, $DataResp->DispViewColumns, $DataResp->DataLists, $DataResp->Aggregates);
                            exit;
                        } else if($Type == 'Pdf') {
                            $this->globalservice->exportPdf($FileName, $SheetName, $DataResp->DispViewColumns, $DataResp->DataLists, $DataResp->Aggregates);
                            exit;
                        }

                    } else {
                        throw new Exception($DataResp->Message);
                    }

                } else {
                    throw new Exception('Oops! Something went wrong.');
                }

            } else {
                throw new Exception('Oops! Missing Module Information.');
            }

        } catch (Exception $e) {
            redirect('dashboard', 'refresh');
        }

    }

    public function getModPageDataDetails($pageNo = 0) {

		$this->EndReturnData = new stdClass();
		try {

			$getResp = $this->globalservice->baseTableDataPaginationDetails($pageNo);
            $this->EndReturnData->List = $getResp->RecordHtmlData;
            $this->EndReturnData->Pagination = $getResp->Pagination;
            $this->EndReturnData->Error = false;

		} catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		$this->globalservice->sendJsonResponse($this->EndReturnData);

	}


    // ── GET /globally/fetchGstinDetails?gstin=XXXX ──────────────────────────
    // Fetches GSTIN details from free public API and returns structured data.
    public function fetchGstinDetails() {

        $this->EndReturnData = new stdClass();
        try {

            $gstin = strtoupper(trim($this->input->get('gstin') ?? ''));

            if (empty($gstin) || strlen($gstin) !== 15) {
                throw new Exception('Please enter a valid 15-character GSTIN.');
            }

            // Free public GSTIN lookup API (no key required)
            $url = 'https://sheet.gstincheck.co.in/check/'.getenv('GSTIN_API_KEY').'/' . urlencode($gstin);

            $this->load->library('curlservice');
            $response = $this->curlservice->retrieve($url, 'GET', null, [
                'Accept: application/json',
                'User-Agent: Mozilla/5.0',
            ]);

            if (!$response || $response->Error) {
                throw new Exception('Failed to reach GSTIN lookup service. Please try again.');
            }

            $data = is_array($response->Data) ? $response->Data : json_decode(json_encode($response->Data ?? []), true);

            if (empty($data) || ($data['flag'] ?? false) === false) {
                throw new Exception('GSTIN not found or invalid. Please verify the number.');
            }

            $d = $data['data'] ?? [];

            // Parse principal place of business address
            $pradr = $d['pradr']['addr'] ?? [];
            $addr  = array_filter([
                $pradr['bnm']  ?? '',   // building name
                $pradr['st']   ?? '',   // street
                $pradr['loc']  ?? '',   // locality
            ]);

            $this->EndReturnData->Error       = false;
            $this->EndReturnData->GSTIN       = $gstin;
            $this->EndReturnData->LegalName   = $d['lgnm']  ?? '';   // Legal name
            $this->EndReturnData->TradeName   = $d['tradeNam'] ?? ($d['lgnm'] ?? '');
            $this->EndReturnData->Status      = $d['sts']   ?? '';   // Active / Cancelled
            $this->EndReturnData->StateCode   = substr($gstin, 0, 2);
            $this->EndReturnData->StateName   = $pradr['stcd'] ?? '';
            $this->EndReturnData->City        = $pradr['dst']  ?? $pradr['loc'] ?? '';
            $this->EndReturnData->Pincode     = $pradr['pncd'] ?? '';
            $this->EndReturnData->AddressLine1 = implode(', ', $addr);
            $this->EndReturnData->AddressLine2 = '';

        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }


    // ── GET /globally/fetchIfscDetails?ifsc=XXXX ─────────────────────────────
    public function getCommTemplate() {
        $this->EndReturnData = new stdClass();
        try {
            $orgUID    = $this->pageData['JwtData']->User->OrgUID;
            $moduleUID = (int) $this->input->post('ModuleUID');
            $channel   = trim($this->input->post('Channel') ?: 'Email');
            $recordUID = (int) $this->input->post('RecordUID');
            if (!$moduleUID) throw new Exception('ModuleUID required.');

            // Build context from live DB data based on module
            $context = $this->_buildCommContext($moduleUID, $recordUID, $orgUID);

            $this->load->model('organisation_model');
            $result = $this->organisation_model->getMessageTemplate($orgUID, $moduleUID, $channel);
            if ($result->Error || empty($result->Data)) {
                $this->EndReturnData->Error      = FALSE;
                $this->EndReturnData->Found      = FALSE;
                $this->EndReturnData->Subject    = '';
                $this->EndReturnData->Body       = '';
                $this->EndReturnData->RawSubject = '';
                $this->EndReturnData->RawBody    = '';
                $this->globalservice->sendJsonResponse($this->EndReturnData);
                return;
            }

            $tpl        = $result->Data;
            $rawSubject = $tpl->Subject ?? '';
            $rawBody    = $tpl->Body    ?? '';

            $resolved = $this->_resolveCommTokens($moduleUID, $context, $rawSubject, $rawBody);

            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Found      = TRUE;
            $this->EndReturnData->Subject    = $resolved['subject'];
            $this->EndReturnData->Body       = $resolved['body'];
            $this->EndReturnData->RawSubject = $rawSubject;
            $this->EndReturnData->RawBody    = $rawBody;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    /**
     * Builds the token context array from live DB data for a given module + record.
     * Add a new case block here for each module that needs token replacement.
     */
    private function _buildCommContext($moduleUID, $recordUID, $orgUID) {
        $context = [];
        if ($recordUID <= 0) return $context;

        // ── Org info (common to all modules) ─────────────────────────────────
        $this->load->model('organisation_model');
        $orgInfo = $this->organisation_model->getOrgForReceipt($orgUID);
        $org     = $orgInfo->Data ?? null;
        if ($org) {
            $orgAddr = implode(', ', array_filter([
                $org->Line1 ?? '', $org->Line2 ?? '',
                $org->CityText ?? '', $org->StateText ?? '', $org->Pincode ?? '',
            ]));
            $context['OrgName']    = $org->BrandName ?? $org->Name ?? '';
            $context['OrgPhone']   = $org->MobileNumber  ?? '';
            $context['OrgEmail']   = $org->EmailAddress  ?? '';
            $context['OrgGSTIN']   = $org->GSTIN         ?? '';
            $context['OrgAddress'] = $orgAddr;
        }

        // ── Module 110: Payments ──────────────────────────────────────────────
        if ((int)$moduleUID === 110) {
            $this->load->model('transactions_model');
            $payment = $this->transactions_model->getPaymentDetailById($recordUID, $orgUID);
            if (!$payment) return $context;

            $appUrl      = rtrim(getenv('HTTP_HOST_URL') ?: '', '/');
            $receiptToken = trim($payment->ReceiptToken ?? '');

            $payDate      = $payment->PaymentDate ?? $payment->CreatedOn ?? '';
            $linkedDoc    = trim($payment->TransNumber ?? '');
            $receiptDesc  = $linkedDoc
                ? 'Amount received against the linked document ' . $linkedDoc
                : 'Amount received';

            $context += [
                'PartyName'          => $payment->PartyName          ?? '',
                'DocNumber'          => $linkedDoc,
                'DocDate'            => $payDate ? date('d M Y', strtotime($payDate)) : '',
                'Amount'             => (float)($payment->Amount      ?? 0),
                'AmountInWords'      => print_number_to_words((float)($payment->Amount ?? 0)),
                'ReceiptNumber'      => $payment->UniqueNumber        ?? '',
                'PaymentMode'        => $payment->PaymentTypeName     ?? '',
                'PaymentStatus'      => (int)($payment->IsFullyPaid ?? 0) ? 'Paid' : 'Partially Paid',
                'ReceiptLink'        => $receiptToken ? $appUrl . '/receipt/' . $receiptToken : '',
                'BalanceAmount'      => (float)($payment->BalanceAmount ?? 0),
                'ReceiptDescription' => $receiptDesc,
            ];
        }

        // ── Module 103: Sales Invoice ─────────────────────────────────────────
        if ((int)$moduleUID === 103) {
            $this->load->model('transactions_model');
            $invoice = $this->transactions_model->getTransactionById($recordUID, $orgUID, 103);
            if (!$invoice) return $context;

            $appUrl       = rtrim(getenv('HTTP_HOST_URL') ?: '', '/');
            $transToken   = trim($invoice->TransToken ?? '');
            $invoiceDate  = $invoice->TransDate ?? $invoice->CreatedOn ?? '';
            $dueDate      = $invoice->ValidityDate ?? '';
            $paidAmount   = (float)($invoice->PaidAmount ?? 0);
            $netAmount    = (float)($invoice->NetAmount ?? 0);
            $balanceAmt   = max(0, $netAmount - $paidAmount);
            
            $paymentStatus = 'Pending';
            if ($paidAmount > 0 && $balanceAmt <= 0.01) {
                $paymentStatus = 'Paid';
            } elseif ($paidAmount > 0) {
                $paymentStatus = 'Partially Paid';
            }

            $context += [
                'PartyName'      => $invoice->PartyName      ?? '',
                'DocNumber'      => $invoice->UniqueNumber   ?? '',
                'DocDate'        => $invoiceDate ? date('d M Y', strtotime($invoiceDate)) : '',
                'DocType'        => 'Invoice',
                'Amount'         => $netAmount,
                'AmountInWords'  => print_number_to_words($netAmount),
                'ValidUntil'     => $dueDate ? date('d M Y', strtotime($dueDate)) : '',
                'InvoiceNumber'  => $invoice->UniqueNumber   ?? '',
                'InvoiceDate'    => $invoiceDate ? date('d M Y', strtotime($invoiceDate)) : '',
                'DueDate'        => $dueDate ? date('d M Y', strtotime($dueDate)) : '',
                'PaymentStatus'  => $paymentStatus,
                'PaidAmount'     => $paidAmount,
                'BalanceAmount'  => $balanceAmt,
                'InvoiceLink'    => $transToken ? $appUrl . '/invoice/' . $transToken : '',
                'SubTotal'       => (float)($invoice->SubTotal ?? 0),
                'TaxAmount'      => (float)($invoice->TaxAmount ?? 0),
                'DiscountAmount' => (float)($invoice->DiscountAmount ?? 0),
            ];
        }

        // ── Add more modules here as needed ───────────────────────────────────

        return $context;
    }

    /**
     * Common token replacement function.
     * Replaces {{TOKEN}} placeholders in subject and body with real values.
     * Common tokens apply to all modules; module-specific tokens are added per module.
     *
     * @param  int    $moduleUID
     * @param  array  $context
     * @param  string $subject
     * @param  string $body
     * @return array  ['subject' => string, 'body' => string]
     */
    private function _resolveCommTokens($moduleUID, $context, $subject, $body) {
        $cur = $this->pageData['JwtData']->GenSettings->CurrenySymbol ?? '₹';
        $dec = (int)($this->pageData['JwtData']->GenSettings->DecimalPoints ?? 2);

        $orgName    = $context['OrgName']    ?? '';
        $orgPhone   = $context['OrgPhone']   ?? '';
        $orgEmail   = $context['OrgEmail']   ?? '';
        $orgGstin   = $context['OrgGSTIN']   ?? '';
        $orgAddress = $context['OrgAddress'] ?? '';

        $fmtAmt = isset($context['Amount'])
            ? $cur . ' ' . number_format((float)$context['Amount'], $dec)
            : '';

        // ── Common tokens — both UPPER_CASE and camelCase variants ────────────
        $map = [
            // UPPER_CASE (standard)
            '{{PARTY_NAME}}'      => $context['PartyName']    ?? '',
            '{{DOC_NUMBER}}'      => $context['DocNumber']     ?? '',
            '{{DOC_DATE}}'        => $context['DocDate']       ?? '',
            '{{DOC_TYPE}}'        => $context['DocType']       ?? '',
            '{{AMOUNT}}'          => $fmtAmt,
            '{{AMOUNT_IN_WORDS}}' => $context['AmountInWords'] ?? '',
            '{{CURRENCY}}'        => $cur,
            '{{VALID_UNTIL}}'     => $context['ValidUntil']    ?? '',
            '{{ORG_NAME}}'        => $orgName,
            '{{ORG_PHONE}}'       => $orgPhone,
            '{{ORG_EMAIL}}'       => $orgEmail,
            '{{ORG_GSTIN}}'       => $orgGstin,
            '{{ORG_ADDRESS}}'     => $orgAddress,
            // camelCase aliases used in message templates
            '{{PartyName}}'       => $context['PartyName']    ?? '',
            '{{DocNumber}}'       => $context['DocNumber']     ?? '',
            '{{DocDate}}'         => $context['DocDate']       ?? '',
            '{{Amount}}'          => $fmtAmt,
            '{{AmountInWords}}'   => $context['AmountInWords'] ?? '',
            '{{CompanyName}}'     => $orgName,
            '{{CompanyMobile}}'   => $orgPhone,
            '{{CompanyEmail}}'    => $orgEmail,
            '{{CompanyGSTIN}}'    => $orgGstin,
            '{{CompanyAddress}}'  => $orgAddress,
        ];

        // ── Module 110: Payments ──────────────────────────────────────────────
        if ((int)$moduleUID === 110) {
            $balFmt = isset($context['BalanceAmount'])
                ? $cur . ' ' . number_format((float)$context['BalanceAmount'], $dec)
                : '';

            // UPPER_CASE
            $map['{{RECEIPT_NUMBER}}']      = $context['ReceiptNumber']      ?? '';
            $map['{{PAYMENT_MODE}}']        = $context['PaymentMode']        ?? '';
            $map['{{PAYMENT_STATUS}}']      = $context['PaymentStatus']      ?? '';
            $map['{{RECEIPT_LINK}}']        = $context['ReceiptLink']        ?? '';
            $map['{{BALANCE_AMOUNT}}']      = $balFmt;
            $map['{{RECEIPT_DESCRIPTION}}'] = $context['ReceiptDescription'] ?? '';
            // camelCase aliases used in message templates
            $map['{{ReceiptNo}}']           = $context['ReceiptNumber']      ?? '';
            $map['{{ReceiptNumber}}']       = $context['ReceiptNumber']      ?? '';
            $map['{{ReceiptDate}}']         = $context['DocDate']            ?? '';
            $map['{{PaymentMode}}']         = $context['PaymentMode']        ?? '';
            $map['{{PaymentStatus}}']       = $context['PaymentStatus']      ?? '';
            // Plain number (no symbol) — templates that write "₹ {{AmountReceived}}" use this
            $map['{{AmountReceived}}']      = isset($context['Amount']) ? number_format((float)$context['Amount'], $dec) : '';
            $map['{{ReceiptLink}}']         = $context['ReceiptLink']        ?? '';
            $map['{{BalanceAmount}}']       = $balFmt;
            $map['{{ReceiptDescription}}']  = $context['ReceiptDescription'] ?? '';
            $map['{{CustomerName}}']        = $context['PartyName']          ?? '';
            $map['{{InvoiceNumber}}']       = $context['DocNumber']          ?? '';
        }

        // ── Module 103: Sales Invoice ───────────────────────────────────────────
        if ((int)$moduleUID === 103) {
            $balFmt = isset($context['BalanceAmount'])
                ? $cur . ' ' . number_format((float)$context['BalanceAmount'], $dec)
                : '';
            $paidFmt = isset($context['PaidAmount'])
                ? $cur . ' ' . number_format((float)$context['PaidAmount'], $dec)
                : '';
            $subTotalFmt = isset($context['SubTotal'])
                ? $cur . ' ' . number_format((float)$context['SubTotal'], $dec)
                : '';
            $taxFmt = isset($context['TaxAmount'])
                ? $cur . ' ' . number_format((float)$context['TaxAmount'], $dec)
                : '';
            $discountFmt = isset($context['DiscountAmount'])
                ? $cur . ' ' . number_format((float)$context['DiscountAmount'], $dec)
                : '';

            // UPPER_CASE
            $map['{{INVOICE_NUMBER}}']   = $context['InvoiceNumber']  ?? '';
            $map['{{INVOICE_DATE}}']     = $context['InvoiceDate']    ?? '';
            $map['{{DUE_DATE}}']         = $context['DueDate']        ?? '';
            $map['{{PAYMENT_STATUS}}']   = $context['PaymentStatus']  ?? '';
            $map['{{PAID_AMOUNT}}']      = $paidFmt;
            $map['{{BALANCE_AMOUNT}}']   = $balFmt;
            $map['{{INVOICE_LINK}}']     = $context['InvoiceLink']    ?? '';
            $map['{{SUB_TOTAL}}']        = $subTotalFmt;
            $map['{{TAX_AMOUNT}}']       = $taxFmt;
            $map['{{DISCOUNT_AMOUNT}}']  = $discountFmt;
            // camelCase aliases
            $map['{{InvoiceNumber}}']    = $context['InvoiceNumber']  ?? '';
            $map['{{InvoiceDate}}']      = $context['InvoiceDate']    ?? '';
            $map['{{DueDate}}']          = $context['DueDate']        ?? '';
            $map['{{PaymentStatus}}']    = $context['PaymentStatus']  ?? '';
            $map['{{PaidAmount}}']       = $paidFmt;
            $map['{{BalanceAmount}}']    = $balFmt;
            $map['{{InvoiceLink}}']      = $context['InvoiceLink']    ?? '';
            $map['{{SubTotal}}']         = $subTotalFmt;
            $map['{{TaxAmount}}']        = $taxFmt;
            $map['{{DiscountAmount}}']   = $discountFmt;
            $map['{{CustomerName}}']     = $context['PartyName']      ?? '';
            $map['{{BillAmount}}']       = $fmtAmt;
        }

        // ── Add more module-specific token blocks here ─────────────────────────

        return [
            'subject' => str_replace(array_keys($map), array_values($map), $subject),
            'body'    => str_replace(array_keys($map), array_values($map), $body),
        ];
    }

    public function fetchIfscDetails() {

        $this->EndReturnData = new stdClass();
        try {

            $ifsc = strtoupper(trim($this->input->get('ifsc') ?? ''));

            if (empty($ifsc) || !preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/', $ifsc)) {
                throw new Exception('Please enter a valid 11-character IFSC code.');
            }

            $url = 'https://ifsc.razorpay.com/' . urlencode($ifsc);

            $this->load->library('curlservice');
            $response = $this->curlservice->retrieve($url, 'GET', null, [
                'Accept: application/json',
                'User-Agent: Mozilla/5.0',
            ]);

            if (!$response || $response->Error) {
                throw new Exception('Failed to reach IFSC lookup service. Please try again.');
            }

            $data = is_array($response->Data) ? $response->Data : json_decode(json_encode($response->Data ?? []), true);

            if (empty($data) || empty($data['BANK'])) {
                throw new Exception('IFSC code not found. Please verify the code.');
            }

            $this->EndReturnData->Error  = false;
            $this->EndReturnData->IFSC   = $ifsc;
            $this->EndReturnData->Bank   = $data['BANK']   ?? '';
            $this->EndReturnData->Branch = $data['BRANCH'] ?? '';
            $this->EndReturnData->City   = $data['CITY']   ?? '';
            $this->EndReturnData->State  = $data['STATE']  ?? '';
            $this->EndReturnData->Address = $data['ADDRESS'] ?? '';

        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // public function importCities() {

    //         $this->load->model('dbwrite_model');

    //         $folderPath = APPPATH . 'data/cities/';
    //         $files = glob($folderPath . '*.json');

    //         if (empty($files)) {
    //             echo "No JSON files found";
    //             return;
    //         }

    //         $batch = [];

    //         foreach ($files as $file) {

    //             $json = file_get_contents($file);
    //             $cities = json_decode($json, true);

    //             if (!$cities) continue;

    //             foreach ($cities as $c) {

    //                 $batch[] = [
    //                     'id'            => $c['id'],
    //                     'name'          => $c['name'],
    //                     'state_id'      => $c['state_id'],
    //                     'state_code'    => $c['state_code'],
    //                     'country_id'    => $c['country_id'],
    //                     'country_code'  => $c['country_code'],
    //                     'type'          => $c['type'] ?? null,
    //                     'level'         => $c['level'] ?? null,
    //                     'parent_id'     => $c['parent_id'] ?? null,
    //                     'latitude'      => $c['latitude'],
    //                     'longitude'     => $c['longitude'],
    //                     'native'        => $c['native'] ?? null,
    //                     'population'    => $c['population'] ?? null,
    //                     'timezone'      => $c['timezone'] ?? null,
    //                     'translations'  => json_encode($c['translations']),
    //                     'created_at'    => date('Y-m-d H:i:s', strtotime($c['created_at'])),
    //                     'updated_at'    => date('Y-m-d H:i:s', strtotime($c['updated_at'])),
    //                     'flag'          => $c['flag'] ?? 1,
    //                     'wikiDataId'    => $c['wikiDataId'] ?? null,
    //                 ];

    //                 // 🔥 Insert every 1000 rows (IMPORTANT)
    //                 if (count($batch) >= 1000) {
    //                     $this->dbwrite_model->insertBatchData('Global', 'CitiesTbl', $batch);
    //                     $batch = [];
    //                 }
    //             }
    //         }

    //         // Insert remaining
    //         if (!empty($batch)) {
    //             $this->dbwrite_model->insertBatchData('Global', 'CitiesTbl', $batch);
    //         }

    //         echo "Import completed successfully!";
    //     }

}
