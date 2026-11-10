<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Globally extends CI_Controller {

    public $pageData = array();
    protected $EndReturnData;

    public function __construct() {
        parent::__construct();

    }

    public function index() {

    }

    public function getCountryInfo() {

        $this->EndReturnData = new stdClass();
		try {
            
            $resp = services_get('api/globally/getCountryInfo');
            $this->EndReturnData->Data    = $resp->Data->Data ?? [];
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Data Retrieved Successfully';

        } catch (Exception $e) {
            $this->notifyError('Globally::getCountryInfo', $e);
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		$this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;

    }

    // ── GET /globally/geodata — all India states + cities as JSON ────────────
    // address.js calls this once and stores the result in localStorage (24h TTL).
    // Subsequent page loads read from localStorage — no AJAX, no server call.
    public function geodata() {
        $this->EndReturnData = new stdClass();
        try {
            $statesResp = services_get('api/globally/getStateofCountry?CountryCode=IN');
            $states     = (!($statesResp->Error ?? true)) ? ($statesResp->Data->Data ?? []) : [];

            $citiesResp = services_get('api/globally/getCityofCountry?CountryCode=IN');
            $allCities  = (!($citiesResp->Error ?? true)) ? ($citiesResp->Data->Data ?? []) : [];

            // Group cities by state ISO2 — mirrors window._cityCache key format
            $citiesByState = [];
            foreach ($allCities as $city) {
                $iso2 = strtoupper($city->state_code);
                $citiesByState[$iso2][] = ['id' => $city->id, 'name' => $city->name];
            }

            $this->EndReturnData->Error  = FALSE;
            $this->EndReturnData->States = $states;
            $this->EndReturnData->Cities = $citiesByState;

        } catch (Exception $e) {
            $this->notifyError('Globally::geodata', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function getStateCityOfCountry() {

        $this->EndReturnData = new stdClass();
        try {

            $CountryCode = strtoupper(trim($this->input->post('CountryCode') ?? ''));
            if (!$CountryCode) {
                throw new Exception('Country Code information is missing.');
            }

            $resp = services_get('api/globally/getStateCityOfCountry?CountryCode=' . urlencode($CountryCode));
            $this->EndReturnData->StateInfo = $resp->Data->StateInfo ?? [];

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Data Retrieved Successfully';

        } catch (Exception $e) {
            $this->notifyError('Globally::getStateCityOfCountry', $e);
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
                
                $resp = services_get('api/globally/getStateofCountry?CountryCode=' . urlencode($CountryCode));
                $this->EndReturnData->Data = $resp->Data->Data ?? [];

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = 'Data Retrieved Successfully';

            } else {
                throw new Exception('Country Code information is missing.');
            }

        } catch (Exception $e) {
            $this->notifyError('Globally::getStateofCountry', $e);
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
            if (!$CountryCode) throw new Exception('Country Code information is missing.');

            $resp = services_get('api/globally/getCityofCountry?CountryCode=' . urlencode($CountryCode));
            $this->EndReturnData->Data    = $resp->Data->Data ?? [];
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Data Retrieved Successfully';

        } catch (Exception $e) {
            $this->notifyError('Globally::getCityofCountry', $e);
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
            $resp = services_get('api/globally/getCitiesOfState?CountryISO2=' . urlencode($countryISO2) . '&StateISO2=' . urlencode($stateISO2));
            $this->EndReturnData->Data = $resp->Data->Data ?? [];
        } catch (Exception $e) {
            $this->notifyError('Globally::getCitiesOfState', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function getTimezones() {
        $this->EndReturnData = new stdClass();
        try {
            $resp = services_get('api/globally/getTimezones');
            $this->EndReturnData->Data = $resp->Data->Data ?? [];
        } catch (Exception $e) {
            $this->notifyError('Globally::getTimezones', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function getStorageTypeInfo() {

        $this->EndReturnData = new stdClass();
		try {

            $resp = services_get('api/globally/getStorageTypeInfo');
            $this->EndReturnData->Data    = $resp->Data->Data ?? [];
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Data Retrieved Successfully';

        } catch (Exception $e) {
            $this->notifyError('Globally::getStorageTypeInfo', $e);
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;

    }

    // ── GET /globally/fetchGstinDetails?gstin=XXXX ──────────────────────────
    public function fetchGstinDetails(): void {

        $this->EndReturnData = new stdClass();
        try {

            $gstin  = strtoupper(trim($this->input->get('gstin') ?? ''));
            $orgUID = (int) ($this->pageData['JwtData']->Org->OrgUID ?? 0);

            if (empty($gstin) || strlen($gstin) !== 15) {
                throw new Exception('Please enter a valid 15-character GSTIN.');
            }

            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';
            $resp     = services_get(
                'api/globally/fetchGstinDetails?gstin=' . urlencode($gstin) . '&orgId=' . $orgUID,
                ['Authorization: Bearer ' . $jwtToken]
            );
            if ($resp === null) throw new Exception('GSTIN lookup service is unavailable. Please try again.');
            if ($resp->Error) {
                $this->EndReturnData->Error   = true;
                $this->EndReturnData->Message = $resp->Message ?? 'GSTIN lookup failed.';
                if (!empty($resp->NoCreditPoints)) $this->EndReturnData->NoCreditPoints = true;
                $this->globalservice->sendJsonResponse($this->EndReturnData);
                return;
            }
            $this->EndReturnData->Error            = false;
            $this->EndReturnData->GSTIN            = $resp->Data->GSTIN            ?? $gstin;
            $this->EndReturnData->LegalName        = $resp->Data->LegalName        ?? '';
            $this->EndReturnData->TradeName        = $resp->Data->TradeName        ?? '';
            $this->EndReturnData->Status           = $resp->Data->Status           ?? '';
            $this->EndReturnData->PAN              = $resp->Data->PAN              ?? '';
            $this->EndReturnData->Constitution     = $resp->Data->Constitution     ?? '';
            $this->EndReturnData->TaxpayerType     = $resp->Data->TaxpayerType     ?? '';
            $this->EndReturnData->RegistrationDate = $resp->Data->RegistrationDate ?? '';
            $this->EndReturnData->StateCode        = $resp->Data->StateCode        ?? '';
            $this->EndReturnData->StateName        = $resp->Data->StateName        ?? '';
            $this->EndReturnData->City             = $resp->Data->City             ?? '';
            $this->EndReturnData->Pincode          = $resp->Data->Pincode          ?? '';
            $this->EndReturnData->AddressLine1     = $resp->Data->AddressLine1     ?? '';
            $this->EndReturnData->AddressLine2     = $resp->Data->AddressLine2     ?? '';
            if (isset($resp->Data->GstinPoints)) $this->EndReturnData->GstinPoints = (int)$resp->Data->GstinPoints;


        } catch (Exception $e) {
            $this->notifyError('Globally::fetchGstinDetails', $e);
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }


    // ── GET /globally/fetchIfscDetails?ifsc=XXXX ─────────────────────────────
    public function getCommTemplate() {
        $this->EndReturnData = new stdClass();
        try {
            $orgUID    = $this->pageData['JwtData']->Org->OrgUID;
            $moduleUID = (int) $this->input->post('ModuleUID');
            $channel   = trim($this->input->post('Channel') ?: 'Email');
            $recordUID = (int) $this->input->post('RecordUID');
            if (!$moduleUID) throw new Exception('ModuleUID required.');

            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';
            $resp     = services_get(
                'api/globally/getCommTemplate?orgId=' . $orgUID
                    . '&ModuleUID=' . $moduleUID
                    . '&Channel=' . urlencode($channel)
                    . '&RecordUID=' . $recordUID,
                ['Authorization: Bearer ' . $jwtToken]
            );
            $data = $resp->Data ?? null;
            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Found      = (bool)($data->Found ?? false);
            $this->EndReturnData->Subject    = $data->Subject    ?? '';
            $this->EndReturnData->Body       = $data->Body       ?? '';
            $this->EndReturnData->RawSubject = $data->RawSubject ?? '';
            $this->EndReturnData->RawBody    = $data->RawBody    ?? '';

        } catch (Exception $e) {
            $this->notifyError('Globally::getCommTemplate', $e);
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
        $org = $this->_getOrgInfo($orgUID)->Data ?? null;
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
                'DocDate'            => $payDate ? format_datedisplay($payDate) : '',
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
                'DocDate'        => $invoiceDate ? format_datedisplay($invoiceDate) : '',
                'DocType'        => 'Invoice',
                'Amount'         => $netAmount,
                'AmountInWords'  => print_number_to_words($netAmount),
                'ValidUntil'     => $dueDate ? format_datedisplay($dueDate) : '',
                'InvoiceNumber'  => $invoice->UniqueNumber   ?? '',
                'InvoiceDate'    => $invoiceDate ? format_datedisplay($invoiceDate) : '',
                'DueDate'        => $dueDate ? format_datedisplay($dueDate) : '',
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
        $cur = $this->pageData['JwtData']->GenSettings->CurrenySymbol ?? 'â‚¹';
        $dec = 2;

        $orgName    = $context['OrgName']    ?? '';
        $orgPhone   = $context['OrgPhone']   ?? '';
        $orgEmail   = $context['OrgEmail']   ?? '';
        $orgGstin   = $context['OrgGSTIN']   ?? '';
        $orgAddress = $context['OrgAddress'] ?? '';

        $fmtAmt = isset($context['Amount'])
            ? smartDecimal((float)$context['Amount'])
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
                ? smartDecimal((float)$context['BalanceAmount'])
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
            // Plain number (no symbol) — templates that write "â‚¹ {{AmountReceived}}" use this
            $map['{{AmountReceived}}']      = isset($context['Amount']) ? smartDecimal((float)$context['Amount']) : '';
            $map['{{ReceiptLink}}']         = $context['ReceiptLink']        ?? '';
            $map['{{BalanceAmount}}']       = $balFmt;
            $map['{{ReceiptDescription}}']  = $context['ReceiptDescription'] ?? '';
            $map['{{CustomerName}}']        = $context['PartyName']          ?? '';
            $map['{{InvoiceNumber}}']       = $context['DocNumber']          ?? '';
        }

        // ── Module 103: Sales Invoice ───────────────────────────────────────────
        if ((int)$moduleUID === 103) {
            $balFmt = isset($context['BalanceAmount'])
                ? smartDecimal((float)$context['BalanceAmount'])
                : '';
            $paidFmt = isset($context['PaidAmount'])
                ? smartDecimal((float)$context['PaidAmount'])
                : '';
            $subTotalFmt = isset($context['SubTotal'])
                ? smartDecimal((float)$context['SubTotal'])
                : '';
            $taxFmt = isset($context['TaxAmount'])
                ? smartDecimal((float)$context['TaxAmount'])
                : '';
            $discountFmt = isset($context['DiscountAmount'])
                ? smartDecimal((float)$context['DiscountAmount'])
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
            $this->notifyError('Globally::fetchIfscDetails', $e);
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // public function importCities() {

    //         $this->load->model('dbwrite_model'); $this->load->model('dbwrite_ext_model');

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

    //                 // ðŸ”¥ Insert every 1000 rows (IMPORTANT)
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
