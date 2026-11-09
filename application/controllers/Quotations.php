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
        try {
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;
            $orgUID = (int)$this->pageData['JwtData']->Org->OrgUID;
            $this->load->model('organisation_model');
            $orgResult = $this->organisation_model->getOrgInfoCached($orgUID);
            $this->pageData['CommOrgContext'] = $orgResult->Data ?? null;
            $templates = $this->organisation_model->getModuleMessageTemplates($orgUID, $this->pageModuleUID);
            $this->pageData['CommEmailTemplate'] = isset($templates['Email'])
                ? ['Subject' => $templates['Email']->Subject ?? '', 'Body' => $templates['Email']->Body ?? '']
                : null;
            $this->_loadTransactionIndexPage([
                'datePrefKey'       => 'quotations',
                'skipList'          => true,
                'tabSlugMap'        => ['all' => 'All', 'open' => 'Open', 'accepted' => 'Accepted', 'converted' => 'Converted', 'cancelled' => 'Cancelled', 'draft' => 'Draft'],
                'listViewPath'      => 'transactions/quotations/list',
                'paginationUrl'     => '/transactions/getPageDetails/101',
                'listViewExtraData' => ['WhatsAppTemplate' => $templates['WhatsApp'] ?? null],
            ]);
            $this->load->view('transactions/quotations/view', $this->pageData);
        } catch (ValidationException $e) {
            redirect('dashboard', 'refresh');
        } catch (Exception $e) {
            notifyError('Quotations::index', $e);
            redirect('dashboard', 'refresh');
        }
    }

    public function create() {

        try {

            $orgUID = $this->pageData['JwtData']->Org->OrgUID;
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;

            $this->load->model('transactions_model');

            $prefixResult                         = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID, 'Prefix.ModuleUID' => $this->pageModuleUID]);
            $this->pageData['PrefixData']         = $prefixResult->Data ?? [];

            $nextNumberMap = [];
            foreach ($this->pageData['PrefixData'] as $pd) {
                $nextNumberMap[(int)$pd->PrefixUID] = $this->transactions_model->getNextTransactionNumber(
                    $pd->PrefixUID, $orgUID, $this->pageModuleUID
                );
            }
            $this->pageData['NextNumberMap'] = $nextNumberMap;

            // Clone: pre-fill from source quotation if ?fromQuotation= is in URL
            $fromUID = (int) $this->input->get('fromQuotation');
            $this->pageData['CloneData']  = null;
            $this->pageData['CloneItems'] = [];
            if ($fromUID > 0) {
                $cloneData  = $this->transactions_model->getTransactionById($fromUID, $orgUID, $this->pageModuleUID);
                $cloneItems = $cloneData ? $this->transactions_model->getTransactionItems($fromUID, $orgUID) : [];
                $this->pageData['CloneData']  = $cloneData;
                $this->pageData['CloneItems'] = $cloneItems;
            }

            $this->_getDispatchAddresses($orgUID);

            $this->pageData['fltStorageData'] = [];
            if (!empty($this->pageData['JwtData']->GenSettings->EnableStorage)) {
                $this->load->model('storage_model');
                $this->pageData['fltStorageData'] = $this->storage_model->getStorageDetails([], 0, 0, '', $this->_uiLang()) ?? [];
            }

            $defaultValidityDays = (int)($this->pageData['JwtData']->TransSettings->QuotValidityDays ?? 7);
            if ($defaultValidityDays < 1) $defaultValidityDays = 7;
            $this->pageData['DefaultValidityDays'] = $defaultValidityDays;
            $this->pageData['DefaultValidityDate'] = date('Y-m-d', strtotime("+{$defaultValidityDays} days"));

            $this->pageData['AdditionalCharges']  = $this->_getAdditionalChargesForOrg((int)$orgUID, true);
            $this->pageData['TaxList']            = $this->_getTaxList();
            $this->pageData['TransactionCharges'] = [];
            $this->pageData['IsEditMode']         = false;

            $this->_loadUpstashConfig();

            $this->load->view('transactions/quotations/forms/form', $this->pageData);

        } catch (ValidationException $e) {
            redirect('dashboard', 'refresh');
        } catch (Exception $e) {
            notifyError('Quotations::create', $e);
            redirect('dashboard', 'refresh');
        }

    }

    public function edit($transUID = 0) {

        try {

            $transUID = (int) $transUID;
            if ($transUID <= 0) redirect('quotations', 'refresh');

            $orgUID = $this->pageData['JwtData']->Org->OrgUID;
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;

            $this->load->model('transactions_model');

            // Load the quotation header + detail fields
            $quotData = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID, $this->_uiLang());
            if (!$quotData) redirect('quotations', 'refresh');

            // Load the line items
            $quotItems = $this->transactions_model->getTransactionItems($transUID, $orgUID);

            $this->pageData['QuotData']  = $quotData;
            $this->pageData['QuotItems'] = $quotItems;

            // Prefix data
            $prefixResult                        = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID, 'Prefix.ModuleUID' => $this->pageModuleUID]);
            $this->pageData['PrefixData']        = $prefixResult->Data ?? [];

            $nextNumberMap = [];
            foreach ($this->pageData['PrefixData'] as $pd) {
                $nextNumberMap[(int)$pd->PrefixUID] = $this->transactions_model->getNextTransactionNumber(
                    $pd->PrefixUID, $orgUID, $this->pageModuleUID
                );
            }
            $this->pageData['NextNumberMap'] = $nextNumberMap;

            // Dispatch address — use same method as create so DispatchAddresses (plural) is set
            $this->_getDispatchAddresses($orgUID);

            // Attachments — load server-side to avoid AJAX call on page load
            $this->pageData['QuotAttachments'] = $this->transactions_model->getTransactionAttachments($transUID, $orgUID);

            $this->pageData['fltStorageData'] = [];
            if (!empty($this->pageData['JwtData']->GenSettings->EnableStorage)) {
                $this->load->model('storage_model');
                $this->pageData['fltStorageData'] = $this->storage_model->getStorageDetails([], 0, 0, '', $this->_uiLang()) ?? [];
            }

            $defaultValidityDays = (int)($this->pageData['JwtData']->TransSettings->QuotValidityDays ?? 7);
            if ($defaultValidityDays < 1) $defaultValidityDays = 7;
            $this->pageData['DefaultValidityDays'] = $defaultValidityDays;
            $this->pageData['DefaultValidityDate'] = date('Y-m-d', strtotime("+{$defaultValidityDays} days"));

            $this->pageData['AdditionalCharges']  = $this->_getAdditionalChargesForOrg((int)$orgUID, true);
            $this->pageData['TransactionCharges'] = $this->transactions_model->getTransactionCharges($transUID, (int)$orgUID);
            $this->pageData['TaxList']            = $this->_getTaxList();
            $this->pageData['IsEditMode']         = true;

            $this->load->view('transactions/quotations/forms/form', $this->pageData);

        } catch (ValidationException $e) {
            redirect('quotations', 'refresh');
        } catch (Exception $e) {
            notifyError('Quotations::edit', $e);
            redirect('quotations', 'refresh');
        }

    }

}
