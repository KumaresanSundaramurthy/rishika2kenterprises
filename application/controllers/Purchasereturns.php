<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Purchasereturns extends MY_Controller {

    public $pageData = array();
    protected $pageModuleUID;

    public function __construct() {
        parent::__construct();
        $this->pageModuleUID = 108;
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
        $this->getDateFilterPreference('purchasereturns');
        $this->load->view('transactions/purchasereturns/view', $this->pageData);
    }

    public function create(): void {
        try {
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;
            $orgUID   = (int)$this->pageData['JwtData']->Org->OrgUID;
            $url      = 'api/purchasereturns/getFormData?orgId=' . $orgUID . '&TransUID=0&langCode=' . $this->_uiLang();
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
            $this->pageData['TransactionCharges']= [];
            $this->pageData['IsEditMode']        = false;

            $this->load->view('transactions/purchasereturns/forms/form', $this->pageData);

        } catch (ValidationException $e) {
            redirect('purchasereturns', 'refresh');
        } catch (Exception $e) {
            notifyError('Purchasereturns::create', $e);
            redirect('purchasereturns', 'refresh');
        }
    }

    public function edit(int $transUID = 0): void {
        try {
            if ($transUID <= 0) { redirect('purchasereturns', 'refresh'); return; }

            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;
            $orgUID   = (int)$this->pageData['JwtData']->Org->OrgUID;
            $url      = 'api/purchasereturns/getFormData?orgId=' . $orgUID . '&TransUID=' . $transUID . '&langCode=' . $this->_uiLang();
            $formResp = services_get($url, withJwt: true);
            if (!$formResp || !empty($formResp->Error) || empty($formResp->PRData)) {
                redirect('purchasereturns', 'refresh'); return;
            }

            $this->pageData['PRData']             = $formResp->PRData;
            $this->pageData['PRItems']            = (array)($formResp->PRItems            ?? []);
            $this->pageData['PRSerialsByProd']    = (array)($formResp->PRSerialsByProd    ?? (object)[]);
            $this->pageData['VendorAddr']         = $formResp->VendorAddr                 ?? null;
            $this->pageData['PRAttachments']      = (array)($formResp->PRAttachments      ?? []);
            $this->pageData['TransactionCharges'] = (array)($formResp->TransactionCharges ?? []);
            $this->pageData['PrefixData']         = (array)($formResp->PrefixData         ?? []);
            $this->pageData['NextNumberMap']      = (array)($formResp->NextNumberMap      ?? (object)[]);
            $this->pageData['DispatchAddresses']  = (array)($formResp->DispatchAddresses  ?? []);
            $this->pageData['DispatchAddress']    = $formResp->DispatchAddress            ?? null;
            $this->pageData['fltStorageData']     = (array)($formResp->fltStorageData     ?? []);
            $this->pageData['AdditionalCharges']  = (array)($formResp->AdditionalCharges  ?? []);
            $this->pageData['TaxList']            = (array)($formResp->TaxList            ?? []);
            $this->pageData['IsEditMode']         = true;

            $this->load->view('transactions/purchasereturns/forms/form', $this->pageData);

        } catch (ValidationException $e) {
            redirect('purchasereturns', 'refresh');
        } catch (Exception $e) {
            notifyError('Purchasereturns::edit', $e);
            redirect('purchasereturns', 'refresh');
        }
    }

}