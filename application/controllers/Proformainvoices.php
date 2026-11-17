<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Proformainvoices extends MY_Controller {

    public $pageData = array();
    protected $pageModuleUID;

    public function __construct() {
        parent::__construct();
        $this->pageModuleUID = 113;
        $this->load->helper('transaction');
    }

    // ── List page ────────────────────────────────────────────────
    public function index(): void {
        if (!$this->_loadPageTitle($this->pageModuleUID)) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }
        $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;
        $this->pageData['ModAllCount']        = 0;
        $this->getDateFilterPreference('proformainvoices');
        $this->load->view('transactions/proformainvoices/view', $this->pageData);
    }

    // ── Create form ──────────────────────────────────────────────
    public function create(): void {
        try {
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;
            $orgUID   = (int)$this->pageData['JwtData']->Org->OrgUID;
            $url      = 'api/proforma/getFormData?orgId=' . $orgUID . '&TransUID=0&langCode=' . $this->_uiLang();
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
            $this->pageData['TransactionCharges']= [];
            $this->pageData['IsEditMode']        = false;

            $this->load->view('transactions/proformainvoices/forms/form', $this->pageData);

        } catch (Exception $e) {
            notifyError('Proformainvoices::create', $e);
            redirect('proforma', 'refresh');
        }
    }

    //── Edit form ────────────────────────────────────────────────
    public function edit(int $transUID = 0): void {
        try {
            if ($transUID <= 0) { redirect('proforma', 'refresh'); return; }

            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;
            $orgUID   = (int)$this->pageData['JwtData']->Org->OrgUID;
            $url      = 'api/proforma/getFormData?orgId=' . $orgUID . '&TransUID=' . $transUID . '&langCode=' . $this->_uiLang();
            $formResp = services_get($url, withJwt: true);
            if (!$formResp || !empty($formResp->Error) || empty($formResp->PFData)) {
                redirect('proforma', 'refresh'); return;
            }

            $this->pageData['PFData']             = $formResp->PFData;
            $this->pageData['PFItems']            = (array)($formResp->PFItems            ?? []);
            $this->pageData['CustAddr']           = $formResp->CustAddr                   ?? null;
            $this->pageData['PFAttachments']      = (array)($formResp->PFAttachments      ?? []);
            $this->pageData['TransactionCharges'] = (array)($formResp->TransactionCharges ?? []);
            $this->pageData['PrefixData']         = (array)($formResp->PrefixData         ?? []);
            $this->pageData['NextNumberMap']      = (array)($formResp->NextNumberMap      ?? (object)[]);
            $this->pageData['DispatchAddresses']  = (array)($formResp->DispatchAddresses  ?? []);
            $this->pageData['DispatchAddress']    = $formResp->DispatchAddress            ?? null;
            $this->pageData['fltStorageData']     = (array)($formResp->fltStorageData     ?? []);
            $this->pageData['AdditionalCharges']  = (array)($formResp->AdditionalCharges  ?? []);
            $this->pageData['TaxList']            = (array)($formResp->TaxList            ?? []);
            $this->pageData['IsEditMode']         = true;

            $this->load->view('transactions/proformainvoices/forms/form', $this->pageData);

        } catch (Exception $e) {
            notifyError('Proformainvoices::edit', $e);
            redirect('proforma', 'refresh');
        }
    }

}
