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
class PackingLists extends MY_Controller {

    /** @var object|null */
    private $EndReturnData;
    /** @var int — no dedicated sidebar module; auth inherited from source transaction */
    protected $pageModuleUID = 119;

    public function __construct() {
        parent::__construct();
    }

    // ── Index (list all packing lists) ──────────────────────────
    public function index(): void {
        $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
        $this->load->model('PackingList_model');
        $this->pageData['PageTitle']       = 'Packing Lists';
        $this->pageData['PageDescription'] = 'All packing lists across transactions';
        $this->pageData['PLList']          = $this->PackingList_model->getAll($orgUID);
        $this->load->view('packing_lists/index', $this->pageData);
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
            $this->load->model('PackingList_model');
            $pl      = $this->PackingList_model->getByTransUID($transUID, $orgUID);
            $plItems = $pl ? $this->PackingList_model->getItems((int) $pl->PackingListUID, $orgUID) : [];

            $this->load->model('organisation_model');
            $orgInfo = $this->organisation_model->getOrgInfoCached($orgUID);

            $this->pageData['PageTitle']       = 'Packing List';
            $this->pageData['PageDescription'] = 'Create or edit a packing list';
            $this->pageData['SrcHeader']       = $srcHeader;
            $this->pageData['SrcItems']        = $srcItems;
            $this->pageData['PL']              = $pl;
            $this->pageData['PLItems']         = $plItems;
            $this->pageData['OrgInfo']         = $orgInfo->Data ?? null;

            $this->load->view('packing_lists/form', $this->pageData);
        } catch (Exception) {
            redirect('/', 'refresh');
        }
    }

    // ── Save (AJAX POST) ─────────────────────────────────────────
    public function save(): void {
        $this->EndReturnData = new stdClass();
        try {
            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $post     = $this->input->post();
            $orgUID   = (int) $this->pageData['JwtData']->Org->OrgUID;
            $userUID  = (int) $this->pageData['JwtData']->User->UserUID;
            $transUID = (int) ($post['TransUID'] ?? 0);
            $plUID    = (int) ($post['PLUID']    ?? 0);

            if ($transUID <= 0) throw new Exception('Invalid source transaction.');

            // Verify source transaction belongs to this org
            $this->load->model('transactions_model');
            $src = $this->transactions_model->getTransactionById($transUID, $orgUID);
            if (!$src) throw new Exception('Source transaction not found.');

            $header = [
                'TransUID'        => $transUID,
                'PLDate'          => trim($post['PLDate']          ?? date('Y-m-d')),
                'VehicleNumber'   => trim($post['VehicleNumber']   ?? ''),
                'LRNumber'        => trim($post['LRNumber']        ?? ''),
                'TransporterName' => trim($post['TransporterName'] ?? ''),
                'Notes'           => trim($post['Notes']           ?? ''),
            ];

            $rawItems = isset($post['items']) ? json_decode($post['items'], true) : [];
            if (!is_array($rawItems)) $rawItems = [];

            $items = [];
            foreach ($rawItems as $item) {
                $tpUID  = (int) ($item['TransProductUID'] ?? 0);
                $pUID   = (int) ($item['ProductUID']      ?? 0);
                if ($tpUID <= 0 || $pUID <= 0) continue;
                $items[] = [
                    'TransProductUID'  => $tpUID,
                    'ProductUID'       => $pUID,
                    'Quantity'         => (float) ($item['Quantity']         ?? 0),
                    'PackageKind'      => trim($item['PackageKind']          ?? ''),
                    'NumberOfPackages' => (int)   ($item['NumberOfPackages'] ?? 0),
                    'NetWeight'        => (float) ($item['NetWeight']        ?? 0),
                    'GrossWeight'      => (float) ($item['GrossWeight']      ?? 0),
                    'CBM'              => (float) ($item['CBM']              ?? 0),
                ];
            }

            $this->load->model('PackingList_model');
            $savedUID = $this->PackingList_model->save($plUID, $header, $items, $orgUID, $userUID);

            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error    = false;
            $this->EndReturnData->Message  = 'Packing list saved successfully.';
            $this->EndReturnData->PLUID    = $savedUID;
            $this->EndReturnData->PrintURL = '/packing-list/' . $transUID . '/print';
        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
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

            $this->load->model('PackingList_model');
            $pl = $this->PackingList_model->getByTransUID($transUID, $orgUID);

            // Graceful fallback: no PL saved yet → use raw DC/Invoice items
            $plItems = $pl
                ? $this->PackingList_model->getItems((int) $pl->PackingListUID, $orgUID)
                : $this->transactions_model->getTransactionItems($transUID, $orgUID);

            $this->load->model('organisation_model');
            $orgInfo = $this->organisation_model->getOrgInfoCached($orgUID);

            $this->pageData['DCHeader'] = $srcHeader;
            $this->pageData['PL']       = $pl;
            $this->pageData['PLItems']  = $plItems;
            $this->pageData['OrgInfo']  = $orgInfo->Data ?? null;

            $this->load->view('packing_lists/print', $this->pageData);
        } catch (Exception) {
            redirect('/', 'refresh');
        }
    }
}
