<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Quotations extends MY_Controller {

    public $pageData = array();
    protected $pageModuleUID;

    public function __construct() {
        parent::__construct();

        $this->pageModuleUID = 101;
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
        $this->getDateFilterPreference('quotations');
        $this->load->view('transactions/quotations/view', $this->pageData);
    }

    public function create(): void {
        try {
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;
            $orgUID   = (int)$this->pageData['JwtData']->Org->OrgUID;
            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';

            $url     = 'api/quotations/getFormData?orgId=' . $orgUID . '&TransUID=0&langCode=' . $this->_uiLang();
            $fromUID = (int)$this->input->get('fromQuotation');
            if ($fromUID > 0) $url .= '&fromQuotation=' . $fromUID;

            $formResp = services_get($url, ['Authorization: Bearer ' . $jwtToken]);
            if (!$formResp || !empty($formResp->Error)) {
                throw new Exception($formResp->Message ?? 'getFormData failed');
            }

            $this->pageData['PrefixData']         = (array)($formResp->PrefixData         ?? []);
            $this->pageData['NextNumberMap']       = (array)($formResp->NextNumberMap      ?? (object)[]);
            $this->pageData['DispatchAddresses']   = (array)($formResp->DispatchAddresses  ?? []);
            $this->pageData['DispatchAddress']     = $formResp->DispatchAddress            ?? null;
            $this->pageData['fltStorageData']      = (array)($formResp->fltStorageData     ?? []);
            $this->pageData['AdditionalCharges']   = (array)($formResp->AdditionalCharges  ?? []);
            $this->pageData['TaxList']             = (array)($formResp->TaxList            ?? []);
            $this->pageData['DefaultValidityDays'] = (int)  ($formResp->DefaultValidityDays ?? 7);
            $this->pageData['DefaultValidityDate'] = $formResp->DefaultValidityDate        ?? '';
            $this->pageData['CloneData']           = $formResp->CloneData                  ?? null;
            $this->pageData['CloneItems']          = (array)($formResp->CloneItems         ?? []);
            $this->pageData['TransactionCharges']  = [];
            $this->pageData['IsEditMode']          = false;

            $this->load->view('transactions/quotations/forms/form', $this->pageData);

        } catch (ValidationException $e) {
            redirect('quotations', 'refresh');
        } catch (Exception $e) {
            notifyError('Quotations::create', $e);
            redirect('quotations', 'refresh');
        }
    }

    public function edit(int $transUID = 0): void {
        try {
            if ($transUID <= 0) { redirect('quotations', 'refresh'); return; }

            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;
            $orgUID   = (int)$this->pageData['JwtData']->Org->OrgUID;
            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';

            $url      = 'api/quotations/getFormData?orgId=' . $orgUID . '&TransUID=' . $transUID . '&langCode=' . $this->_uiLang();
            $formResp = services_get($url, ['Authorization: Bearer ' . $jwtToken]);
            if (!$formResp || !empty($formResp->Error) || empty($formResp->QuotData)) {
                redirect('quotations', 'refresh'); return;
            }

            $this->pageData['QuotData']            = $formResp->QuotData;
            $this->pageData['QuotItems']           = (array)($formResp->QuotItems           ?? []);
            $this->pageData['CustAddr']            = $formResp->CustAddr                    ?? null;
            $this->pageData['QuotAttachments']     = (array)($formResp->QuotAttachments     ?? []);
            $this->pageData['TransactionCharges']  = (array)($formResp->TransactionCharges  ?? []);
            $this->pageData['PrefixData']          = (array)($formResp->PrefixData          ?? []);
            $this->pageData['NextNumberMap']       = (array)($formResp->NextNumberMap       ?? (object)[]);
            $this->pageData['DispatchAddresses']   = (array)($formResp->DispatchAddresses   ?? []);
            $this->pageData['DispatchAddress']     = $formResp->DispatchAddress             ?? null;
            $this->pageData['fltStorageData']      = (array)($formResp->fltStorageData      ?? []);
            $this->pageData['AdditionalCharges']   = (array)($formResp->AdditionalCharges   ?? []);
            $this->pageData['TaxList']             = (array)($formResp->TaxList             ?? []);
            $this->pageData['DefaultValidityDays'] = (int)  ($formResp->DefaultValidityDays  ?? 7);
            $this->pageData['DefaultValidityDate'] = $formResp->DefaultValidityDate         ?? '';
            $this->pageData['IsEditMode']          = true;

            $this->load->view('transactions/quotations/forms/form', $this->pageData);

        } catch (ValidationException $e) {
            redirect('quotations', 'refresh');
        } catch (Exception $e) {
            notifyError('Quotations::edit', $e);
            redirect('quotations', 'refresh');
        }
    }

}
