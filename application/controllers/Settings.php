<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Settings extends MY_Controller {

    public $pageData = [];

    public function __construct() {
        parent::__construct();
    }

    // ── General Settings page ────────────────────────────────────────────────
    public function generalsettings() {
        try {
            $this->_loadPageTitle(28);
        } catch (Exception $e) {
            notifyError('Settings::generalsettings', $e);
        }
        try {
            $orgUID   = (int) $this->pageData['JwtData']->Org->OrgUID;
            $resp     = services_get('api/settings/generalsettings?orgId=' . $orgUID, withJwt: true);
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

    // ── Thermal Print Config ─────────────────────────────────────────────────
    public function thermalconfig(): void {
        try {
            $this->_loadPageTitle(11);
        } catch (Exception $e) {
            notifyError('Settings::thermalconfig', $e);
        }
        $this->load->view('settings/thermalconfig/view', $this->pageData);
    }

    // ── Bank Accounts ────────────────────────────────────────────────────────
    public function banks(): void {
        try {
            $this->_loadPageTitle(12);
        } catch (Exception $e) {
            notifyError('Settings::banks', $e);
        }
        try {
            $this->load->view('settings/banks/view', $this->pageData);
        } catch (Exception $e) {
            notifyError('Settings::banks', $e);
            redirect('dashboard', 'refresh');
        }
    }

    // ── Message Templates ────────────────────────────────────────────────────
    public function msgtemplates(): void {
        try {
            $this->_loadPageTitle(13);
        } catch (Exception $e) {
            notifyError('Settings::msgtemplates', $e);
        }
        $this->load->view('settings/msgtemplates/view', $this->pageData);
    }

    // ── Prefix Configuration ─────────────────────────────────────────────────
    public function prefixconfig(): void {
        try {
            $this->_loadPageTitle(14);
        } catch (Exception $e) {
            notifyError('Settings::prefixconfig', $e);
        }
        $this->load->view('settings/prefixconfig/view', $this->pageData);
    }

    // ── Additional Charges ───────────────────────────────────────────────────
    public function additionalcharges(): void {
        try {
            $this->_loadPageTitle(17);
        } catch (Exception $e) {
            notifyError('Settings::additionalcharges', $e);
        }
        $this->load->view('settings/additionalcharges/view', $this->pageData);
    }

}
