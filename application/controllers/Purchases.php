<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Purchases extends MY_Controller {

    public $pageData = array();
    protected $pageModuleUID;

    public function __construct() {
        parent::__construct();

        $this->pageModuleUID = 105;
        $this->load->helper('transaction');

    }

    public function index(): void {
        if (!$this->_loadPageTitle($this->pageModuleUID)) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }
        $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;
        $this->pageData['ModAllCount']        = 0;
        $this->_loadUpstashConfig();
        $this->getDateFilterPreference('purchases');
        $this->load->view('transactions/purchases/view', $this->pageData);
    }

    public function purchasePayments(): void {
        try {
            $this->load->view('transactions/purchases/payments', $this->pageData);
        } catch (ValidationException $e) {
            redirect('purchases', 'refresh');
        } catch (Exception $e) {
            notifyError('Purchases::purchasePayments', $e);
            redirect('purchases', 'refresh');
        }
    }

    public function create(): void {
        try {
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;
            $orgUID   = (int)$this->pageData['JwtData']->Org->OrgUID;
            $url       = 'api/purchases/getFormData?orgId=' . $orgUID . '&TransUID=0&langCode=' . $this->_uiLang();
            $fromPOUID = (int)$this->input->get('fromPurchaseOrder');
            if ($fromPOUID > 0) $url .= '&fromPurchaseOrder=' . $fromPOUID;

            $formResp = services_get($url, withJwt: true);
            if (!$formResp || !empty($formResp->Error)) {
                throw new Exception($formResp->Message ?? 'getFormData failed');
            }

            $this->pageData['PrefixData']        = (array)($formResp->PrefixData        ?? []);
            $this->pageData['NextNumberMap']     = (array)($formResp->NextNumberMap     ?? (object)[]);
            $this->pageData['DispatchAddresses'] = (array)($formResp->DispatchAddresses ?? []);
            $this->pageData['DispatchAddress']   = $formResp->DispatchAddress           ?? null;
            $this->pageData['fltStorageData']    = (array)($formResp->fltStorageData    ?? []);
            $this->pageData['AdditionalCharges'] = (array)($formResp->AdditionalCharges ?? []);
            $this->pageData['TaxList']           = (array)($formResp->TaxList           ?? []);
            $this->pageData['PaymentTypes']      = (array)($formResp->PaymentTypes      ?? []);
            $this->pageData['BankAccounts']      = (array)($formResp->BankAccounts      ?? []);
            $this->pageData['FromPOUID']         = (int)  ($formResp->FromPOUID         ?? 0);
            $this->pageData['POData']            = $formResp->POData                    ?? null;
            $this->pageData['POItems']           = (array)($formResp->POItems           ?? []);
            $this->pageData['TransactionCharges']= [];
            $this->pageData['IsEditMode']        = false;

            $this->load->view('transactions/purchases/forms/form', $this->pageData);

        } catch (ValidationException $e) {
            redirect('purchases', 'refresh');
        } catch (Exception $e) {
            notifyError('Purchases::create', $e);
            redirect('purchases', 'refresh');
        }
    }

    public function edit(int $transUID = 0): void {
        try {
            if ($transUID <= 0) { redirect('purchases', 'refresh'); return; }

            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;
            $orgUID   = (int)$this->pageData['JwtData']->Org->OrgUID;
            $url      = 'api/purchases/getFormData?orgId=' . $orgUID . '&TransUID=' . $transUID . '&langCode=' . $this->_uiLang();
            $formResp = services_get($url, withJwt: true);
            if (!$formResp || !empty($formResp->Error) || empty($formResp->PurchData)) {
                redirect('purchases', 'refresh'); return;
            }

            $this->pageData['PurchData']          = $formResp->PurchData;
            $this->pageData['PurchItems']         = (array)($formResp->PurchItems         ?? []);
            $this->pageData['PurchAttachments']   = (array)($formResp->PurchAttachments   ?? []);
            $this->pageData['PurchSerialsByProd'] = (array)($formResp->PurchSerialsByProd ?? (object)[]);
            $this->pageData['VendorAddr']         = $formResp->VendorAddr                 ?? null;
            $this->pageData['TransactionCharges'] = (array)($formResp->TransactionCharges ?? []);
            $this->pageData['PrefixData']         = (array)($formResp->PrefixData         ?? []);
            $this->pageData['NextNumberMap']      = (array)($formResp->NextNumberMap      ?? (object)[]);
            $this->pageData['DispatchAddresses']  = (array)($formResp->DispatchAddresses  ?? []);
            $this->pageData['DispatchAddress']    = $formResp->DispatchAddress            ?? null;
            $this->pageData['fltStorageData']     = (array)($formResp->fltStorageData     ?? []);
            $this->pageData['AdditionalCharges']  = (array)($formResp->AdditionalCharges  ?? []);
            $this->pageData['TaxList']            = (array)($formResp->TaxList            ?? []);
            $this->pageData['PaymentTypes']       = (array)($formResp->PaymentTypes       ?? []);
            $this->pageData['BankAccounts']       = (array)($formResp->BankAccounts       ?? []);
            $this->pageData['IsEditMode']         = true;

            $this->load->view('transactions/purchases/forms/form', $this->pageData);

        } catch (ValidationException $e) {
            redirect('purchases', 'refresh');
        } catch (Exception $e) {
            notifyError('Purchases::edit', $e);
            redirect('purchases', 'refresh');
        }
    }
    
}