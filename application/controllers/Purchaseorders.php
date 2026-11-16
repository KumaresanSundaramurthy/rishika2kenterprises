<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Purchaseorders extends MY_Controller {

    public $pageData = array();
    protected $pageModuleUID;

    public function __construct() {
        parent::__construct();

        $this->pageModuleUID = 104;
        $this->load->helper('transaction');

    }

    public function index(): void {
        if (!$this->_loadPageTitle($this->pageModuleUID)) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }
        $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;
        $this->pageData['ModAllCount']        = 0;
        $this->getDateFilterPreference('purchaseorders');
        $this->load->view('transactions/purchaseorders/view', $this->pageData);
    }

    public function create(): void {
        try {
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;
            $orgUID   = (int)$this->pageData['JwtData']->Org->OrgUID;
            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';

            $url      = 'api/purchaseorders/getFormData?orgId=' . $orgUID . '&TransUID=0&langCode=' . $this->_uiLang();
            $formResp = services_get($url, ['Authorization: Bearer ' . $jwtToken]);
            if (!$formResp || !empty($formResp->Error)) {
                throw new Exception($formResp->Message ?? 'getFormData failed');
            }

            $this->pageData['PrefixData']        = (array)($formResp->PrefixData        ?? []);
            $this->pageData['NextNumberMap']     = (array)($formResp->NextNumberMap     ?? (object)[]);
            $this->pageData['fltStorageData']    = (array)($formResp->fltStorageData    ?? []);
            $this->pageData['AdditionalCharges'] = (array)($formResp->AdditionalCharges ?? []);
            $this->pageData['TaxList']           = (array)($formResp->TaxList           ?? []);
            $this->pageData['TransactionCharges']= [];
            $this->pageData['IsEditMode']        = false;

            $this->load->view('transactions/purchaseorders/forms/form', $this->pageData);

        } catch (ValidationException $e) {
            redirect('purchaseorders', 'refresh');
        } catch (Exception $e) {
            $this->notifyError('Purchaseorders::create', $e);
            redirect('purchaseorders', 'refresh');
        }
    }

    public function edit(int $transUID = 0): void {
        try {
            if ($transUID <= 0) { redirect('purchaseorders', 'refresh'); return; }

            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;
            $orgUID   = (int)$this->pageData['JwtData']->Org->OrgUID;
            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';

            $url      = 'api/purchaseorders/getFormData?orgId=' . $orgUID . '&TransUID=' . $transUID . '&langCode=' . $this->_uiLang();
            $formResp = services_get($url, ['Authorization: Bearer ' . $jwtToken]);
            if (!$formResp || !empty($formResp->Error) || empty($formResp->POData)) {
                redirect('purchaseorders', 'refresh'); return;
            }

            $this->pageData['POData']             = $formResp->POData;
            $this->pageData['POItems']            = (array)($formResp->POItems            ?? []);
            $this->pageData['VendorAddr']         = $formResp->VendorAddr                 ?? null;
            $this->pageData['TransactionCharges'] = (array)($formResp->TransactionCharges ?? []);
            $this->pageData['POAttachments']      = (array)($formResp->POAttachments      ?? []);
            $this->pageData['PrefixData']         = (array)($formResp->PrefixData         ?? []);
            $this->pageData['NextNumberMap']      = (array)($formResp->NextNumberMap      ?? (object)[]);
            $this->pageData['fltStorageData']     = (array)($formResp->fltStorageData     ?? []);
            $this->pageData['AdditionalCharges']  = (array)($formResp->AdditionalCharges  ?? []);
            $this->pageData['TaxList']            = (array)($formResp->TaxList            ?? []);
            $this->pageData['IsEditMode']         = true;

            $this->load->view('transactions/purchaseorders/forms/form', $this->pageData);

        } catch (ValidationException $e) {
            redirect('purchaseorders', 'refresh');
        } catch (Exception $e) {
            $this->notifyError('Purchaseorders::edit', $e);
            redirect('purchaseorders', 'refresh');
        }
    }


}
