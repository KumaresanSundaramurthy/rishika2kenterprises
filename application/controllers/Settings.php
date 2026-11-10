<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Settings extends MY_Controller {

    public  $pageData = [];
    protected $EndReturnData;


    public function __construct() {
        parent::__construct();
    }

    /** Returns [ModuleUID => Name] map from Modules.ModuleTbl where IsThermalPrint = 1 */
    private function getThermalTransTypes() {
        $this->load->model('organisation_model');
        $result = $this->organisation_model->getThermalPrintModules();
        $types  = [];
        foreach ($result->Data ?? [] as $row) {
            $types[(int)$row->ModuleUID] = $row->Name;
        }
        return $types;
    }

    /** Returns [ModuleUID => Name] map from Modules.ModuleTbl where IsPrefix = 1 */
    private function getPrefixModulesList() {
        $this->load->model('organisation_model');
        $result = $this->organisation_model->getPrefixModules();
        $types  = [];
        foreach ($result->Data ?? [] as $row) {
            $types[(int)$row->ModuleUID] = $row->Name;
        }
        return $types;
    }    

    // ── General Settings page ────────────────────────────────────────────────
    public function generalsettings() {
        $this->_loadPageTitle();
        if (empty($this->pageData['PageTitle'])) $this->pageData['PageTitle'] = 'Settings';
        try {
            
            $orgUID   = (int) $this->pageData['JwtData']->Org->OrgUID;
            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';
            $resp     = services_get('api/settings/generalsettings?orgId=' . $orgUID, ['Authorization: Bearer ' . $jwtToken]);
            $data     = $resp->Data ?? null;
            $this->pageData['GenSettings']    = $data->GenSettings    ?? ($this->pageData['JwtData']->GenSettings  ?? new stdClass());
            $this->pageData['ProdSettings']   = $data->ProdSettings   ?? ($this->pageData['JwtData']->ProdSettings ?? new stdClass());
            $this->pageData['TransSettings']  = $data->TransSettings  ?? new stdClass();
            $this->pageData['DiscTypeInfo']   = (array)($data->DiscTypeInfo   ?? []);
            $this->pageData['ProdTypeInfo']   = (array)($data->ProdTypeInfo   ?? []);
            $this->pageData['ProdTaxInfo']    = (array)($data->ProdTaxInfo    ?? []);
            $this->pageData['TaxDetInfo']     = (array)($data->TaxDetInfo     ?? []);
            $this->pageData['SalutationList'] = (array)($data->SalutationList ?? []);

            $this->load->view('settings/generalsettings/view', $this->pageData);
        } catch (Exception $e) {
            notifyError('Settings::generalsettings', $e);
            redirect('dashboard', 'refresh');
        }
    }

    // ── Separate settings pages ──────────────────────────────────────────────

    public function thermalconfig(): void {
        $this->pageData['PageTitle']       = 'Thermal Print Config';
        $this->pageData['PageDescription'] = 'Configure thermal receipt layout, paper width and receipt elements per transaction type.';
        $this->load->view('settings/thermalconfig/view', $this->pageData);
    }

    public function banks() {
        $this->pageData['PageTitle'] = 'Bank Accounts';
        try {
            $this->load->view('settings/banks/view', $this->pageData);
        } catch (Exception $e) {
            notifyError('Settings::banks', $e);
            redirect('dashboard', 'refresh');
        }
    }

    public function msgtemplates(): void {
        $this->pageData['PageTitle']       = 'Message Templates';
        $this->pageData['PageDescription'] = 'Create and manage Email, WhatsApp & SMS templates per transaction type. Use {{tokens}} to auto-fill real data when sending.';
        $this->load->view('settings/msgtemplates/view', $this->pageData);
    }


    // ── Prefix Configuration page ────────────────────────────────────────────

    public function prefixconfig(): void {
        $this->pageData['PageTitle']       = 'Prefix Configuration';
        $this->pageData['PageDescription'] = 'Prefixes define how your transaction numbers are formatted per module.';
        $this->load->view('settings/prefixconfig/view', $this->pageData);
    }

    // ── Additional Charges ───────────────────────────────────────────────────

    public function additionalcharges(): void {
        $this->pageData['PageTitle']       = 'Additional Charges';
        $this->pageData['PageDescription'] = 'Manage charge types that appear in transaction forms, such as delivery and packaging fees.';
        $this->load->view('settings/additionalcharges/view', $this->pageData);
    }
    
}