<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Proformainvoices extends MY_Controller {

    public $pageData = array();
    protected $pageModuleUID;

    public function __construct() {
        parent::__construct();
        $this->pageModuleUID = 113;
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
                'datePrefKey'  => 'proformainvoices',
                'skipList'     => true,
                'tabSlugMap'   => ['all' => 'All', 'sent' => 'Sent', 'converted' => 'Converted', 'expired' => 'Expired', 'cancelled' => 'Cancelled', 'drafts' => 'Draft'],
                'listViewPath' => 'transactions/proformainvoices/list',
                'paginationUrl'=> '/transactions/getPageDetails/113',
            ]);
            $this->load->view('transactions/proformainvoices/view', $this->pageData);
        } catch (Exception $e) {
            notifyError('Proformainvoices::index', $e);
            redirect('dashboard', 'refresh');
        }
    }

    // ── Create form ──────────────────────────────────────────────
    public function create() {
        try {

            $orgUID = $this->pageData['JwtData']->Org->OrgUID;
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;

            $this->load->model('transactions_model');
            $this->pageData['AdditionalCharges']  = $this->_getAdditionalChargesForOrg((int)$orgUID, true);
            $this->pageData['TaxList']            = $this->_getTaxList();
            $this->pageData['TransactionCharges'] = [];
            $this->pageData['IsEditMode']         = false;
            $prefixResult                    = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID, 'Prefix.ModuleUID' => $this->pageModuleUID]);
            $this->pageData['PrefixData']    = $prefixResult->Data ?? [];
            $nextNumberMap = [];
            foreach ($this->pageData['PrefixData'] as $pd) {
                $nextNumberMap[(int)$pd->PrefixUID] = $this->transactions_model->getNextTransactionNumber($pd->PrefixUID, $orgUID, $this->pageModuleUID);
            }
            $this->pageData['NextNumberMap'] = $nextNumberMap;

            $this->_getDispatchAddresses($orgUID);

            $this->pageData['fltStorageData']  = [];
            if (!empty($this->pageData['JwtData']->GenSettings->EnableStorage)) {
                $this->load->model('storage_model');
                $this->pageData['fltStorageData'] = $this->storage_model->getStorageDetails([], 0, 0, '', $this->_uiLang()) ?? [];
            }

            $this->load->view('transactions/proformainvoices/forms/form', $this->pageData);
        } catch (Exception $e) {
            notifyError('Proformainvoices::create', $e);
            redirect('proforma', 'refresh');
        }
    }

    //── Edit form ────────────────────────────────────────────────
    public function edit($transUID = 0) {
        try {
            $transUID = (int) $transUID;
            if ($transUID <= 0) redirect('proforma', 'refresh');


            $orgUID = $this->pageData['JwtData']->Org->OrgUID;
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;

            $this->load->model('transactions_model');
            $pfData = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID, $this->_uiLang());
            if (!$pfData) redirect('proforma', 'refresh');

            $pfItems = $this->transactions_model->getTransactionItems($transUID, $orgUID);

            $this->load->model('customers_model');
            $custAddr = $this->customers_model->getCustomerAddress(['CustAddress.CustomerUID' => $pfData->PartyUID, 'CustAddress.OrgUID' => $orgUID], $this->_uiLang());
            $shipping = current(array_filter($custAddr, fn($a) => $a->AddressType === 'Shipping'));
            $billing  = current(array_filter($custAddr, fn($a) => $a->AddressType === 'Billing'));
            $this->pageData['CustAddr'] = $shipping ?: ($billing ?: ($custAddr[0] ?? null));

            $this->pageData['PFData']  = $pfData;
            $this->pageData['PFItems'] = $pfItems;

            $this->pageData['AdditionalCharges']  = $this->_getAdditionalChargesForOrg((int)$orgUID, true);
            $this->pageData['TransactionCharges'] = $this->transactions_model->getTransactionCharges($transUID, (int)$orgUID);
            $this->pageData['TaxList']            = $this->_getTaxList();
            $this->pageData['IsEditMode']         = true;

            $prefixResult                    = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID, 'Prefix.ModuleUID' => $this->pageModuleUID]);
            $this->pageData['PrefixData']    = $prefixResult->Data ?? [];
            $nextNumberMap = [];
            foreach ($this->pageData['PrefixData'] as $pd) {
                $nextNumberMap[(int)$pd->PrefixUID] = $this->transactions_model->getNextTransactionNumber($pd->PrefixUID, $orgUID, $this->pageModuleUID);
            }
            $this->pageData['NextNumberMap'] = $nextNumberMap;

            $this->_getDispatchAddresses($orgUID);

            $this->pageData['fltStorageData']  = [];
            if (!empty($this->pageData['JwtData']->GenSettings->EnableStorage)) {
                $this->load->model('storage_model');
                $this->pageData['fltStorageData'] = $this->storage_model->getStorageDetails([], 0, 0, '', $this->_uiLang()) ?? [];
            }

            // Attachments — load server-side to avoid AJAX call on page load
            $this->pageData['PFAttachments'] = $this->transactions_model->getTransactionAttachments($transUID, $orgUID);

            $this->load->view('transactions/proformainvoices/forms/form', $this->pageData);
        } catch (Exception $e) {
            notifyError('Proformainvoices::edit', $e);
            redirect('proforma', 'refresh');
        }
    }

}
