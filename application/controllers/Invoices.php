<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Invoices extends MY_Controller {

    public $pageData = array();
    protected $pageModuleUID;

    public function __construct() {
        parent::__construct();

        $this->pageModuleUID = 103;
        $this->load->helper('transaction');

    }

    public function index(): void {
        if (!$this->_loadPageTitle($this->pageModuleUID)) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }
        $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;
        $this->getDateFilterPreference('invoices');
        $orgUsersResp = services_get('api/users/getOrgUsers?orgId=' . $this->_orgUID(), withJwt: true);
        $this->pageData['OrgUsers'] = ($orgUsersResp && !$orgUsersResp->Error) ? (array)($orgUsersResp->Users ?? []) : [];
        $this->load->view('transactions/invoices/view', $this->pageData);
    }

    public function create(): void {
        if (!$this->_loadPageTitle($this->pageModuleUID)) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }
        try {
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;
            $orgUID   = (int)$this->pageData['JwtData']->Org->OrgUID;
            $url = 'api/invoices/getFormData?orgId=' . $orgUID . '&TransUID=0&langCode=' . $this->_uiLang();

            $fromSOUID       = (int) $this->input->get('fromSalesOrder');
            $fromQuotUID     = (int) $this->input->get('fromQuotation');
            $fromProFormaUID = (int) $this->input->get('fromProForma');
            $fromChallanUID  = (int) $this->input->get('fromChallan');

            if ($fromSOUID > 0)       $url .= '&fromSalesOrder=' . $fromSOUID;
            if ($fromQuotUID > 0)     $url .= '&fromQuotation='  . $fromQuotUID;
            if ($fromProFormaUID > 0) $url .= '&fromProForma='   . $fromProFormaUID;
            if ($fromChallanUID > 0)  $url .= '&fromChallan='    . $fromChallanUID;

            $formResp = services_get($url, withJwt: true);
            if (!$formResp || !empty($formResp->Error)) {
                throw new Exception($formResp->Message ?? 'getFormData failed');
            }

            $this->pageData['PrefixData']        = (array)($formResp->PrefixData        ?? []);
            $this->pageData['NextNumberMap']      = (array)($formResp->NextNumberMap     ?? (object)[]);
            $this->pageData['DispatchAddresses']  = (array)($formResp->DispatchAddresses ?? []);
            $this->pageData['DispatchAddress']    = $formResp->DispatchAddress           ?? null;
            $this->pageData['AdditionalCharges']  = (array)($formResp->AdditionalCharges ?? []);
            $this->pageData['TaxList']            = (array)($formResp->TaxList           ?? []);
            $this->pageData['PaymentTypes']       = (array)($formResp->PaymentTypes      ?? []);
            $this->pageData['BankAccounts']       = (array)($formResp->BankAccounts      ?? []);
            $this->pageData['FromSalesOrderUID']  = (int)  ($formResp->FromSalesOrderUID ?? 0);
            $this->pageData['SalesOrderData']     = $formResp->SalesOrderData            ?? null;
            $this->pageData['SalesOrderItems']    = (array)($formResp->SalesOrderItems   ?? []);
            $this->pageData['FromQuotationUID']   = (int)  ($formResp->FromQuotationUID  ?? 0);
            $this->pageData['QuotationData']      = $formResp->QuotationData             ?? null;
            $this->pageData['QuotationItems']     = (array)($formResp->QuotationItems    ?? []);
            $this->pageData['FromProFormaUID']    = (int)  ($formResp->FromProFormaUID   ?? 0);
            $this->pageData['ProFormaData']       = $formResp->ProFormaData              ?? null;
            $this->pageData['ProFormaItems']      = (array)($formResp->ProFormaItems     ?? []);
            $this->pageData['FromChallanUID']     = (int)  ($formResp->FromChallanUID    ?? 0);
            $this->pageData['ChallanData']        = $formResp->ChallanData               ?? null;
            $this->pageData['ChallanItems']       = (array)($formResp->ChallanItems      ?? []);
            $this->pageData['TransactionCharges'] = [];
            $this->pageData['IsEditMode']         = false;

            $this->load->view('transactions/invoices/forms/form', $this->pageData);

        } catch (ValidationException $e) {
            redirect('invoices', 'refresh');
        } catch (Exception $e) {
            notifyError('Invoices::create', $e);
            redirect('invoices', 'refresh');
        }
    }

    public function pos(): void {
        if (!$this->_loadPageTitle($this->pageModuleUID)) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }
        try {
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;
            $orgUID   = (int)$this->pageData['JwtData']->Org->OrgUID;
            $url      = 'api/invoices/getFormData?orgId=' . $orgUID . '&TransUID=0&langCode=' . $this->_uiLang();

            $formResp = services_get($url, withJwt: true);
            if (!$formResp || !empty($formResp->Error)) {
                throw new Exception($formResp->Message ?? 'getFormData failed');
            }

            /* POS only needs prefix, payment types, bank accounts, next number, charges */
            $this->pageData['PrefixData']        = (array)($formResp->PrefixData        ?? []);
            $this->pageData['NextNumberMap']     = (array)($formResp->NextNumberMap     ?? (object)[]);
            $this->pageData['PaymentTypes']      = (array)($formResp->PaymentTypes      ?? []);
            $this->pageData['BankAccounts']      = (array)($formResp->BankAccounts      ?? []);
            $this->pageData['Categories']        = (array)($formResp->Categories        ?? []);
            $this->pageData['AdditionalCharges'] = (array)($formResp->AdditionalCharges ?? []);
            $this->pageData['TaxList']           = (array)($formResp->TaxList           ?? []);

            /* Resolve auto-increment invoice number for the first / default prefix */
            $prefixData     = $this->pageData['PrefixData'];
            $nextNumberMap  = $this->pageData['NextNumberMap'];
            $defaultPrefix  = null;
            foreach ($prefixData as $px) {
                if (!empty($px->IsDefault)) { $defaultPrefix = $px; break; }
            }
            if (!$defaultPrefix && !empty($prefixData)) {
                $defaultPrefix = $prefixData[0];
            }
            $this->pageData['NextInvNumber'] = '';
            if ($defaultPrefix) {
                $uid = (int)($defaultPrefix->PrefixUID ?? 0);
                $this->pageData['NextInvNumber'] = (string)($nextNumberMap[$uid] ?? '');
            }

            $this->load->view('transactions/invoices/pos', $this->pageData);

        } catch (ValidationException $e) {
            redirect('invoices', 'refresh');
        } catch (Exception $e) {
            notifyError('Invoices::pos', $e);
            redirect('invoices', 'refresh');
        }
    }

    public function edit(string $token = ''): void {
        try {
            $token = trim($token);
            if (empty($token)) { redirect('invoices'); return; }

            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;
            $orgUID   = (int)$this->pageData['JwtData']->Org->OrgUID;
            $url      = 'api/invoices/getFormData?orgId=' . $orgUID . '&Token=' . rawurlencode($token) . '&langCode=' . $this->_uiLang();
            $formResp = services_get($url, withJwt: true);
            if (!$formResp || !empty($formResp->Error) || empty($formResp->InvData)) {
                redirect('invoices'); return;
            }

            $this->pageData['InvData']            = $formResp->InvData;
            $this->pageData['InvItems']           = (array)($formResp->InvItems           ?? []);
            $this->pageData['ReturnedQtyMap']     = (array)($formResp->ReturnedQtyMap     ?? (object)[]);
            $this->pageData['HasSalesReturns']    = !empty($formResp->HasSalesReturns);
            $this->pageData['DraftReservedCN']    = $formResp->DraftReservedCN            ?? null;
            $this->pageData['CustAddr']           = $formResp->CustAddr                   ?? null;
            $this->pageData['InvAttachments']     = (array)($formResp->InvAttachments     ?? []);
            $this->pageData['TransactionCharges'] = (array)($formResp->TransactionCharges ?? []);
            $this->pageData['InvSerialsByProd']   = (array)($formResp->InvSerialsByProd   ?? (object)[]);
            $this->pageData['PrefixData']         = (array)($formResp->PrefixData         ?? []);
            $this->pageData['NextNumberMap']      = (array)($formResp->NextNumberMap      ?? (object)[]);
            $this->pageData['DispatchAddresses']  = (array)($formResp->DispatchAddresses  ?? []);
            $this->pageData['DispatchAddress']    = $formResp->DispatchAddress            ?? null;
            $this->pageData['AdditionalCharges']  = (array)($formResp->AdditionalCharges  ?? []);
            $this->pageData['TaxList']            = (array)($formResp->TaxList            ?? []);
            $this->pageData['PaymentTypes']       = (array)($formResp->PaymentTypes       ?? []);
            $this->pageData['BankAccounts']       = (array)($formResp->BankAccounts       ?? []);
            $this->pageData['IsEditMode']         = true;

            $this->load->view('transactions/invoices/forms/form', $this->pageData);

        } catch (ValidationException $e) {
            redirect('invoices', 'refresh');
        } catch (Exception $e) {
            notifyError('Invoices::edit', $e);
            redirect('invoices', 'refresh');
        }
    }

}