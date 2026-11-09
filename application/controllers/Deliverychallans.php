<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property object $transactions_model
 * @property object $dbwrite_model
 * @property object $formvalidation_model
 * @property object $globalservice
 * @property object $redisservice
 */
class Deliverychallans extends MY_Controller {

    public  $pageData     = [];
    protected $pageModuleUID;

    public function __construct() {
        parent::__construct();
        $this->pageModuleUID = 112;
        $this->load->helper('transaction');
    }

    // ── List page ────────────────────────────────────────────────
    public function index(): void {
        if (!$this->_loadPageTitle($this->pageModuleUID)) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }
        try {
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;
            $this->_loadTransactionIndexPage([
                'datePrefKey'  => 'deliverychallans',
                'tabSlugMap'   => ['all' => 'All', 'dispatched' => 'Dispatched', 'delivered' => 'Delivered', 'converted' => 'Converted', 'cancelled' => 'Cancelled', 'draft' => 'Draft'],
                'listViewPath' => 'transactions/deliverychallans/list',
                'paginationUrl'=> '/transactions/getPageDetails/112',
            ]);
            $this->load->view('transactions/deliverychallans/view', $this->pageData);
        } catch (ValidationException $e) {
            redirect('dashboard', 'refresh');
        } catch (Exception $e) {
            $this->notifyError('Deliverychallans::index', $e);
            redirect('dashboard', 'refresh');
        }
    }

    // ── Create form ──────────────────────────────────────────────
    public function create() {
        try {
            $orgUID = $this->pageData['JwtData']->Org->OrgUID;
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;

            $this->load->model('transactions_model');

            $prefixResult                    = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID, 'Prefix.ModuleUID' => $this->pageModuleUID]);
            $this->pageData['PrefixData']    = $prefixResult->Data ?? [];
            $nextNumberMap = [];
            foreach ($this->pageData['PrefixData'] as $pd) {
                $nextNumberMap[(int)$pd->PrefixUID] = $this->transactions_model->getNextTransactionNumber($pd->PrefixUID, $orgUID, $this->pageModuleUID);
            }
            $this->pageData['NextNumberMap'] = $nextNumberMap;

            // Pre-fill from Sales Order if converting
            $fromSOUID = (int) $this->input->get('fromSalesOrder');
            $this->pageData['FromSOUID']    = $fromSOUID;
            $this->pageData['SOSourceData'] = null;
            $this->pageData['SOSourceItems']= [];
            if ($fromSOUID > 0) {
                $soData  = $this->transactions_model->getTransactionById($fromSOUID, $orgUID, 102);
                $soItems = $soData ? $this->transactions_model->getTransactionItems($fromSOUID, $orgUID) : [];
                $this->pageData['SOSourceData']  = $soData;
                $this->pageData['SOSourceItems'] = $soItems;
            }

            // Pre-fill from Clone (clone opens create form, not edit)
            $fromCloneUID = (int) $this->input->get('fromClone');
            $this->pageData['FromCloneUID'] = $fromCloneUID;
            $this->pageData['CloneData']    = null;
            $this->pageData['CloneItems']   = [];
            if ($fromCloneUID > 0) {
                $cloneData  = $this->transactions_model->getTransactionById($fromCloneUID, $orgUID, $this->pageModuleUID);
                $cloneItems = $cloneData ? $this->transactions_model->getTransactionItems($fromCloneUID, $orgUID) : [];
                $this->pageData['CloneData']  = $cloneData;
                $this->pageData['CloneItems'] = $cloneItems;
            }

            $this->pageData['AdditionalCharges']  = $this->_getAdditionalChargesForOrg((int)$orgUID, true);
            $this->pageData['TaxList']            = $this->_getTaxList();
            $this->pageData['TransactionCharges'] = [];
            $this->pageData['IsEditMode']         = false;

            $this->_getDispatchAddresses($orgUID);

            $this->pageData['fltStorageData']  = [];
            if (!empty($this->pageData['JwtData']->GenSettings->EnableStorage)) {
                $this->load->model('storage_model');
                $this->pageData['fltStorageData'] = $this->storage_model->getStorageDetails([], 0, 0, '', $this->_uiLang()) ?? [];
            }

            $this->load->view('transactions/deliverychallans/forms/form', $this->pageData);
        } catch (ValidationException $e) {
            redirect('deliverychallan', 'refresh');
        } catch (Exception $e) {
            $this->notifyError('Deliverychallans::create', $e);
            redirect('deliverychallan', 'refresh');
        }
    }

    //── Edit form ────────────────────────────────────────────────
    public function edit($transUID = 0) {
        try {
            $transUID = (int) $transUID;
            if ($transUID <= 0) redirect('deliverychallan', 'refresh');

            $orgUID = $this->pageData['JwtData']->Org->OrgUID;
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;

            $this->load->model('transactions_model');
            $dcData = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID, $this->_uiLang());
            if (!$dcData) redirect('deliverychallan', 'refresh');

            $dcItems = $this->transactions_model->getTransactionItems($transUID, $orgUID);

            // Pre-fetch attachments — eliminates the AJAX call on the edit form
            $attachments = $this->transactions_model->getTransactionAttachments($transUID, $orgUID);
            $cdnUrl = rtrim(getenv('FILE_UPLOAD') == 'amazonaws' ? getenv('CDN_URL') : getenv('CFLARE_R2_CDN'), '/');
            foreach ($attachments as &$a) {
                $a->Url = $cdnUrl . '/' . ltrim($a->FilePath ?? '', '/');
            }
            unset($a);

            $this->pageData['DCData']        = $dcData;
            $this->pageData['DCItems']       = $dcItems;
            $this->pageData['DCAttachments'] = $attachments;

            $prefixResult                    = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID, 'Prefix.ModuleUID' => $this->pageModuleUID]);
            $this->pageData['PrefixData']    = $prefixResult->Data ?? [];
            $nextNumberMap = [];
            foreach ($this->pageData['PrefixData'] as $pd) {
                $nextNumberMap[(int)$pd->PrefixUID] = $this->transactions_model->getNextTransactionNumber($pd->PrefixUID, $orgUID, $this->pageModuleUID);
            }
            $this->pageData['NextNumberMap'] = $nextNumberMap;

            $this->pageData['AdditionalCharges']  = $this->_getAdditionalChargesForOrg((int)$orgUID, true);
            $this->pageData['TransactionCharges'] = $this->transactions_model->getTransactionCharges($transUID, (int)$orgUID);
            $this->pageData['TaxList']            = $this->_getTaxList();
            $this->pageData['IsEditMode']         = true;

            $this->_getDispatchAddresses($orgUID);

            $this->pageData['fltStorageData']  = [];
            if (!empty($this->pageData['JwtData']->GenSettings->EnableStorage)) {
                $this->load->model('storage_model');
                $this->pageData['fltStorageData'] = $this->storage_model->getStorageDetails([], 0, 0, '', $this->_uiLang()) ?? [];
            }

            $this->load->view('transactions/deliverychallans/forms/form', $this->pageData);
        } catch (ValidationException $e) {
            redirect('deliverychallan', 'refresh');
        } catch (Exception $e) {
            $this->notifyError('Deliverychallans::edit', $e);
            redirect('deliverychallan', 'refresh');
        }
    }

}
