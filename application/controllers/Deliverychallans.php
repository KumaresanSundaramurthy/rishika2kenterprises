<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Deliverychallans extends MY_Controller {

    public  $pageData     = [];
    protected $pageModuleUID;

    public function __construct() {
        parent::__construct();
        $this->pageModuleUID = 112;
    }

    // ── List page — data loaded client-side from NestJS services ────────────
    public function index(): void {
        if (!$this->_loadPageTitle($this->pageModuleUID)) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }
        $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;
        $this->getDateFilterPreference('deliverychallans');
        $this->load->view('transactions/deliverychallans/view', $this->pageData);
    }

    // ── Create form ──────────────────────────────────────────────
    public function create(): void {
        if (!$this->_loadPageTitle($this->pageModuleUID)) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }
        try {
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;
            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';

            $fromSOUID    = (int) $this->input->get('fromSalesOrder');
            $fromCloneUID = (int) $this->input->get('fromClone');

            $url = 'api/deliverychallan/getFormData?orgId=' . $orgUID
                 . '&TransUID=0&langCode=' . $this->_uiLang();
            if ($fromSOUID    > 0) $url .= '&fromSalesOrder=' . $fromSOUID;
            if ($fromCloneUID > 0) $url .= '&fromClone='      . $fromCloneUID;

            $formResp = services_get($url, ['Authorization: Bearer ' . $jwtToken]);
            if (!$formResp || !empty($formResp->Error)) {
                throw new Exception($formResp->Message ?? 'getFormData failed');
            }

            $this->pageData['PrefixData']         = (array)($formResp->PrefixData        ?? []);
            $this->pageData['NextNumberMap']       = (array)($formResp->NextNumberMap     ?? (object)[]);
            $this->pageData['DispatchAddresses']   = (array)($formResp->DispatchAddresses ?? []);
            $this->pageData['DispatchAddress']     = $formResp->DispatchAddress           ?? null;
            $this->pageData['fltStorageData']      = (array)($formResp->fltStorageData    ?? []);
            $this->pageData['AdditionalCharges']   = (array)($formResp->AdditionalCharges ?? []);
            $this->pageData['TaxList']             = (array)($formResp->TaxList           ?? []);
            $this->pageData['FromSOUID']           = (int)  ($formResp->FromSOUID         ?? 0);
            $this->pageData['SOSourceData']        = $formResp->SOSourceData              ?? null;
            $this->pageData['SOSourceItems']       = (array)($formResp->SOSourceItems     ?? []);
            $this->pageData['FromCloneUID']        = (int)  ($formResp->FromCloneUID      ?? 0);
            $this->pageData['CloneData']           = $formResp->CloneData                 ?? null;
            $this->pageData['CloneItems']          = (array)($formResp->CloneItems        ?? []);
            $this->pageData['TransactionCharges']  = [];
            $this->pageData['IsEditMode']          = false;

            $this->load->view('transactions/deliverychallans/forms/form', $this->pageData);
        } catch (ValidationException $e) {
            redirect('deliverychallan', 'refresh');
        } catch (Exception $e) {
            $this->notifyError('Deliverychallans::create', $e);
            redirect('deliverychallan', 'refresh');
        }
    }

    //── Edit form ────────────────────────────────────────────────
    public function edit(int $transUID = 0): void {
        try {
            if ($transUID <= 0) redirect('deliverychallan', 'refresh');

            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;
            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';

            $url      = 'api/deliverychallan/getFormData?orgId=' . $orgUID
                      . '&TransUID=' . $transUID . '&langCode=' . $this->_uiLang();
            $formResp = services_get($url, ['Authorization: Bearer ' . $jwtToken]);
            if (!$formResp || !empty($formResp->Error) || empty($formResp->DCData)) {
                redirect('deliverychallan', 'refresh'); return;
            }

            $this->pageData['DCData']             = $formResp->DCData;
            $this->pageData['DCItems']            = (array)($formResp->DCItems            ?? []);
            $this->pageData['CustAddr']           = $formResp->CustAddr                   ?? null;
            $this->pageData['DCAttachments']      = (array)($formResp->DCAttachments      ?? []);
            $this->pageData['TransactionCharges'] = (array)($formResp->TransactionCharges ?? []);
            $this->pageData['PrefixData']         = (array)($formResp->PrefixData         ?? []);
            $this->pageData['NextNumberMap']      = (array)($formResp->NextNumberMap      ?? (object)[]);
            $this->pageData['DispatchAddresses']  = (array)($formResp->DispatchAddresses  ?? []);
            $this->pageData['DispatchAddress']    = $formResp->DispatchAddress            ?? null;
            $this->pageData['fltStorageData']     = (array)($formResp->fltStorageData     ?? []);
            $this->pageData['AdditionalCharges']  = (array)($formResp->AdditionalCharges  ?? []);
            $this->pageData['TaxList']            = (array)($formResp->TaxList            ?? []);
            $this->pageData['IsEditMode']         = true;

            $this->load->view('transactions/deliverychallans/forms/form', $this->pageData);
        } catch (ValidationException $e) {
            redirect('deliverychallan', 'refresh');
        } catch (Exception $e) {
            $this->notifyError('Deliverychallans::edit', $e);
            redirect('deliverychallan', 'refresh');
        }
    }

}
