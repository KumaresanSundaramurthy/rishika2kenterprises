<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Salesreturns extends MY_Controller {

    public $pageData = array();
    protected $pageModuleUID;

    public function __construct() {
        parent::__construct();
        $this->pageModuleUID = 106;
        $this->load->helper('transaction');
    }

    public function index(): void {
        if (!$this->_loadPageTitle($this->pageModuleUID)) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }
        $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;
        $this->pageData['ModAllCount']        = 0;
        $this->getDateFilterPreference('salesreturns');
        $this->load->view('transactions/salesreturns/view', $this->pageData);
    }


    public function create(): void {
        try {
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;
            $orgUID   = (int)$this->pageData['JwtData']->Org->OrgUID;
            $url      = 'api/salesreturns/getFormData?orgId=' . $orgUID . '&TransUID=0&langCode=' . $this->_uiLang();
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

            $this->load->view('transactions/salesreturns/forms/form', $this->pageData);

        } catch (ValidationException $e) {
            redirect('salesreturns', 'refresh');
        } catch (Exception $e) {
            notifyError('Salesreturns::create', $e);
            redirect('salesreturns', 'refresh');
        }
    }

    public function edit(int $transUID = 0): void {
        try {
            if ($transUID <= 0) { redirect('salesreturns', 'refresh'); return; }

            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;
            $orgUID   = (int)$this->pageData['JwtData']->Org->OrgUID;
            $url      = 'api/salesreturns/getFormData?orgId=' . $orgUID . '&TransUID=' . $transUID . '&langCode=' . $this->_uiLang();
            $formResp = services_get($url, withJwt: true);
            if (!$formResp || !empty($formResp->Error) || empty($formResp->SRData)) {
                redirect('salesreturns', 'refresh'); return;
            }

            $this->pageData['SRData']            = $formResp->SRData;
            $this->pageData['SRItems']           = (array)($formResp->SRItems           ?? []);
            $this->pageData['SRSerialsByProd']   = (array)($formResp->SRSerialsByProd   ?? (object)[]);
            $this->pageData['SRAttachments']     = (array)($formResp->SRAttachments     ?? []);
            $this->pageData['TransactionCharges']= (array)($formResp->TransactionCharges ?? []);
            $this->pageData['PrefixData']        = (array)($formResp->PrefixData        ?? []);
            $this->pageData['NextNumberMap']     = (array)($formResp->NextNumberMap     ?? (object)[]);
            $this->pageData['DispatchAddresses'] = (array)($formResp->DispatchAddresses ?? []);
            $this->pageData['DispatchAddress']   = $formResp->DispatchAddress           ?? null;
            $this->pageData['fltStorageData']    = (array)($formResp->fltStorageData    ?? []);
            $this->pageData['AdditionalCharges'] = (array)($formResp->AdditionalCharges ?? []);
            $this->pageData['TaxList']           = (array)($formResp->TaxList           ?? []);
            $this->pageData['IsEditMode']        = true;

            $this->load->view('transactions/salesreturns/forms/form', $this->pageData);

        } catch (ValidationException $e) {
            redirect('salesreturns', 'refresh');
        } catch (Exception $e) {
            notifyError('Salesreturns::edit', $e);
            redirect('salesreturns', 'refresh');
        }
    }
    

}
