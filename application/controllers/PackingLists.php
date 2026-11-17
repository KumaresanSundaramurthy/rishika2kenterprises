<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Packinglists extends MY_Controller {

    /** @var int — no dedicated sidebar module; auth inherited from source transaction */
    protected $pageModuleUID = 119;

    public function __construct() {
        parent::__construct();
    }

    // ── Index (list all packing lists) ──────────────────────────
    public function index(): void {
        try {
            $this->_loadPageTitle($this->pageModuleUID);
        } catch (Exception $e) {
            notifyError('Packinglists::index', $e);
        }
        $this->load->view('transactions/packing_lists/index', $this->pageData);
    }

    // ── Form (create or edit) ────────────────────────────────────
    public function form(int $transUID = 0): void {
        try {
            $transUID = (int) $transUID;
            if ($transUID <= 0) redirect('/', 'refresh');

            $resp     = services_get(
                'api/packinglists/getFormData?orgId=' . $this->_orgUID() . '&TransUID=' . $transUID,
                withJwt: true
            );
            if (!$resp || !empty($resp->Error)) redirect('/', 'refresh');

            $this->pageData['SrcHeader'] = $resp->SrcHeader ?? null;
            $this->pageData['SrcItems']        = (array)($resp->SrcItems ?? []);
            $this->pageData['PL']              = $resp->PL        ?? null;
            $this->pageData['PLItems']         = (array)($resp->PLItems  ?? []);
            $this->pageData['OrgInfo']         = $resp->OrgInfo   ?? null;

            $this->load->view('transactions/packing_lists/form', $this->pageData);
        } catch (Exception) {
            redirect('/', 'refresh');
        }
    }

    // ── Print ────────────────────────────────────────────────────
    public function printPL(int $transUID = 0): void {
        try {
            $transUID = (int) $transUID;
            if ($transUID <= 0) redirect('/', 'refresh');

            $resp     = services_get(
                'api/packinglists/getPrintData?orgId=' . $this->_orgUID() . '&TransUID=' . $transUID,
                withJwt: true
            );
            if (!$resp || !empty($resp->Error)) redirect('/', 'refresh');

            $this->pageData['DCHeader'] = $resp->DCHeader ?? null;
            $this->pageData['PL']       = $resp->PL       ?? null;
            $this->pageData['PLItems']  = (array)($resp->PLItems ?? []);
            $this->pageData['OrgInfo']  = $resp->OrgInfo  ?? null;

            $this->load->view('transactions/packing_lists/print', $this->pageData);
        } catch (Exception) {
            redirect('/', 'refresh');
        }
    }
}
