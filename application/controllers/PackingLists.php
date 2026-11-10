<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * PackingLists — standalone controller for packing list documents.
 *
 * Packing Lists are cross-module sub-documents: a DC, Invoice, or any
 * dispatching transaction can own one. Access is governed by the source
 * transaction's own module (no separate module nav entry needed).
 *
 * @property object $PackingList_model
 * @property object $transactions_model
 * @property object $organisation_model
 * @property object $dbwrite_model
 * @property object $globalservice
 * @property object $input
 */
class Packinglists extends MY_Controller {

    /** @var int — no dedicated sidebar module; auth inherited from source transaction */
    protected $pageModuleUID = 119;

    public function __construct() {
        parent::__construct();
    }

    // ── Index (list all packing lists) ──────────────────────────
    public function index(): void {
        $this->pageData['PageTitle']       = 'Packing Lists';
        $this->pageData['PageDescription'] = 'All packing lists across transactions';
        $this->load->view('transactions/packing_lists/index', $this->pageData);
    }

    // ── Form (create or edit) ────────────────────────────────────
    public function form(int $transUID = 0): void {
        try {
            $transUID = (int) $transUID;
            if ($transUID <= 0) redirect('/', 'refresh');

            $orgUID  = (int) $this->pageData['JwtData']->Org->OrgUID;

            // Load source transaction (module-agnostic — works for DC, Invoice, etc.)
            $this->load->model('transactions_model');
            $srcHeader = $this->transactions_model->getTransactionById($transUID, $orgUID);
            if (!$srcHeader) redirect('/', 'refresh');

            $srcItems = $this->transactions_model->getTransactionItems($transUID, $orgUID);

            // Load existing PL if one already exists for this transaction
            $this->load->model('Packinglist_model');
            $pl      = $this->Packinglist_model->getByTransUID($transUID, $orgUID);
            $plItems = $pl ? $this->Packinglist_model->getItems((int) $pl->PackingListUID, $orgUID) : [];

            $orgInfo = $this->_getOrgInfo($orgUID);

            $this->pageData['PageTitle']       = 'Packing List';
            $this->pageData['PageDescription'] = 'Create or edit a packing list';
            $this->pageData['SrcHeader']       = $srcHeader;
            $this->pageData['SrcItems']        = $srcItems;
            $this->pageData['PL']              = $pl;
            $this->pageData['PLItems']         = $plItems;
            $this->pageData['OrgInfo']         = $orgInfo->Data ?? null;

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

            $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;

            $this->load->model('transactions_model');
            $srcHeader = $this->transactions_model->getTransactionById($transUID, $orgUID);
            if (!$srcHeader) redirect('/', 'refresh');

            $this->load->model('Packinglist_model');
            $pl = $this->Packinglist_model->getByTransUID($transUID, $orgUID);

            // Graceful fallback: no PL saved yet → use raw DC/Invoice items
            $plItems = $pl
                ? $this->Packinglist_model->getItems((int) $pl->PackingListUID, $orgUID)
                : $this->transactions_model->getTransactionItems($transUID, $orgUID);

            $orgInfo = $this->_getOrgInfo($orgUID);

            $this->pageData['DCHeader'] = $srcHeader;
            $this->pageData['PL']       = $pl;
            $this->pageData['PLItems']  = $plItems;
            $this->pageData['OrgInfo']  = $orgInfo->Data ?? null;

            $this->load->view('transactions/packing_lists/print', $this->pageData);
        } catch (Exception) {
            redirect('/', 'refresh');
        }
    }
}
