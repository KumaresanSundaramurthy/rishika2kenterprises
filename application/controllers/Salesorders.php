<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Salesorders extends MY_Controller {

    public $pageData = array();
    protected $pageModuleUID;

    public function __construct() {
        parent::__construct();

        $this->pageModuleUID = 102;
        $this->load->helper('transaction');

    }

    public function index(): void {
        if (!$this->_loadPageTitle($this->pageModuleUID)) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }
        $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;
        $orgUID = (int)$this->pageData['JwtData']->Org->OrgUID;
        $this->pageData['CommOrgContext']    = $this->_getOrgInfo($orgUID)->Data ?? null;
        $this->pageData['CommEmailTemplate'] = null;
        $this->pageData['ModAllCount']       = 0;
        $this->getDateFilterPreference('salesorders');
        $this->load->view('transactions/salesorders/view', $this->pageData);
    }

    public function create(): void {
        try {
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;
            $orgUID   = (int)$this->pageData['JwtData']->Org->OrgUID;
            $url          = 'api/salesorders/getFormData?orgId=' . $orgUID . '&TransUID=0&langCode=' . $this->_uiLang();
            $fromQuotUID  = (int)$this->input->get('fromQuotation');
            if ($fromQuotUID > 0) $url .= '&fromQuotation=' . $fromQuotUID;

            $formResp = services_get($url, withJwt: true);
            if (!$formResp || !empty($formResp->Error)) {
                throw new Exception($formResp->Message ?? 'getFormData failed');
            }

            $this->pageData['PrefixData']          = (array)($formResp->PrefixData           ?? []);
            $this->pageData['NextNumberMap']        = (array)($formResp->NextNumberMap        ?? (object)[]);
            $this->pageData['DispatchAddresses']    = (array)($formResp->DispatchAddresses    ?? []);
            $this->pageData['DispatchAddress']      = $formResp->DispatchAddress              ?? null;
            $this->pageData['fltStorageData']       = (array)($formResp->fltStorageData       ?? []);
            $this->pageData['AdditionalCharges']    = (array)($formResp->AdditionalCharges    ?? []);
            $this->pageData['TaxList']              = (array)($formResp->TaxList              ?? []);
            $this->pageData['FromQuotationUID']     = (int)  ($formResp->FromQuotationUID     ?? 0);
            $this->pageData['QuotationData']        = $formResp->QuotationData                ?? null;
            $this->pageData['QuotationItems']       = (array)($formResp->QuotationItems       ?? []);
            $this->pageData['QuotationAttachments'] = (array)($formResp->QuotationAttachments ?? []);
            $this->pageData['TransactionCharges']   = [];
            $this->pageData['IsEditMode']           = false;

            $this->load->view('transactions/salesorders/forms/form', $this->pageData);

        } catch (ValidationException $e) {
            redirect('salesorders', 'refresh');
        } catch (Exception $e) {
            $this->notifyError('Salesorders::create', $e);
            redirect('salesorders', 'refresh');
        }
    }

    public function edit(int $transUID = 0): void {
        try {
            if ($transUID <= 0) { redirect('salesorders', 'refresh'); return; }

            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;
            $orgUID   = (int)$this->pageData['JwtData']->Org->OrgUID;
            $url      = 'api/salesorders/getFormData?orgId=' . $orgUID . '&TransUID=' . $transUID . '&langCode=' . $this->_uiLang();
            $formResp = services_get($url, withJwt: true);
            if (!$formResp || !empty($formResp->Error) || empty($formResp->SOData)) {
                redirect('salesorders', 'refresh'); return;
            }

            $this->pageData['SOData']             = $formResp->SOData;
            $this->pageData['SOItems']            = (array)($formResp->SOItems            ?? []);
            $this->pageData['CustAddr']           = $formResp->CustAddr                   ?? null;
            $this->pageData['SOAttachments']      = (array)($formResp->SOAttachments      ?? []);
            $this->pageData['TransactionCharges'] = (array)($formResp->TransactionCharges ?? []);
            $this->pageData['PrefixData']         = (array)($formResp->PrefixData         ?? []);
            $this->pageData['NextNumberMap']      = (array)($formResp->NextNumberMap      ?? (object)[]);
            $this->pageData['DispatchAddress']    = $formResp->DispatchAddress            ?? null;
            $this->pageData['fltStorageData']     = (array)($formResp->fltStorageData     ?? []);
            $this->pageData['AdditionalCharges']  = (array)($formResp->AdditionalCharges  ?? []);
            $this->pageData['TaxList']            = (array)($formResp->TaxList            ?? []);
            $this->pageData['IsEditMode']         = true;

            $this->load->view('transactions/salesorders/forms/form', $this->pageData);

        } catch (ValidationException $e) {
            redirect('salesorders', 'refresh');
        } catch (Exception $e) {
            $this->notifyError('Salesorders::edit', $e);
            redirect('salesorders', 'refresh');
        }
    }

}