<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Salesorders extends MY_Controller {

    public $pageData = array();
    protected $pageModuleUID;

    public function __construct() {
        parent::__construct();

        $this->pageModuleUID = 102;
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
            $this->pageData['CommOrgContext'] = $this->_getOrgInfo($orgUID)->Data ?? null;
            $this->load->model('organisation_model');
            $templates = $this->organisation_model->getModuleMessageTemplates($orgUID, $this->pageModuleUID);
            $this->pageData['CommEmailTemplate'] = isset($templates['Email'])
                ? ['Subject' => $templates['Email']->Subject ?? '', 'Body' => $templates['Email']->Body ?? '']
                : null;
            $this->_loadTransactionIndexPage([
                'datePrefKey'       => 'salesorders',
                'skipList'          => true,
                'tabSlugMap'        => ['all' => 'All', 'pending' => 'Pending', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'draft' => 'Draft'],
                'listViewPath'      => 'transactions/salesorders/list',
                'paginationUrl'     => '/transactions/getPageDetails/102',
                'listViewExtraData' => ['WhatsAppTemplate' => $templates['WhatsApp'] ?? null],
            ]);
            $this->load->view('transactions/salesorders/view', $this->pageData);
        } catch (ValidationException $e) {
            redirect('dashboard', 'refresh');
        } catch (Exception $e) {
            $this->notifyError('Salesorders::index', $e);
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

            // Pre-fill from quotation if converting
            $fromQuotationUID = (int) $this->input->get('fromQuotation');
            $this->pageData['FromQuotationUID'] = $fromQuotationUID;
            $this->pageData['QuotationData']    = null;
            $this->pageData['QuotationItems']   = [];
            if ($fromQuotationUID > 0) {
                $quotData        = $this->transactions_model->getTransactionById($fromQuotationUID, $orgUID, 101);
                $quotItems       = $quotData ? $this->transactions_model->getTransactionItems($fromQuotationUID, $orgUID) : [];
                $quotAttachments = $this->transactions_model->getTransactionAttachments($fromQuotationUID, $orgUID);
                $cdnUrl = rtrim(getenv('FILE_UPLOAD') == 'amazonaws' ? getenv('CDN_URL') : getenv('CFLARE_R2_CDN'), '/');
                foreach ($quotAttachments as &$a) {
                    $a->Url = $cdnUrl . '/' . ltrim($a->FilePath ?? '', '/');
                }
                unset($a);
                $this->pageData['QuotationData']        = $quotData;
                $this->pageData['QuotationItems']       = $quotItems;
                $this->pageData['QuotationAttachments'] = $quotAttachments;
            }

            $this->_getDispatchAddresses($orgUID);

            $this->pageData['AdditionalCharges']  = $this->_getAdditionalChargesForOrg((int)$orgUID, true);
            $this->pageData['TaxList']            = $this->_getTaxList();
            $this->pageData['TransactionCharges'] = [];
            $this->pageData['IsEditMode']         = false;

            $this->pageData['fltStorageData'] = [];
            if (!empty($this->pageData['JwtData']->GenSettings->EnableStorage)) {
                $this->load->model('storage_model');
                $this->pageData['fltStorageData'] = $this->storage_model->getStorageDetails([], 0, 0, '', $this->_uiLang()) ?? [];
            }

            $this->load->view('transactions/salesorders/forms/form', $this->pageData);

        } catch (ValidationException $e) {
            redirect('salesorders', 'refresh');
        } catch (Exception $e) {
            $this->notifyError('Salesorders::create', $e);
            redirect('salesorders', 'refresh');
        }

    }

    public function edit($transUID = 0) {

        try {

            $transUID = (int) $transUID;
            if ($transUID <= 0) redirect('salesorders', 'refresh');

            $orgUID = $this->pageData['JwtData']->Org->OrgUID;
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;

            $this->load->model('transactions_model');

            $soData = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID, $this->_uiLang());
            if (!$soData) redirect('salesorders', 'refresh');

            $soItems = $this->transactions_model->getTransactionItems($transUID, $orgUID);

            $this->pageData['SOData']  = $soData;
            $this->pageData['SOItems'] = $soItems;

            $prefixResult                        = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID, 'Prefix.ModuleUID' => $this->pageModuleUID]);
            $this->pageData['PrefixData']        = $prefixResult->Data ?? [];

            $nextNumberMap = [];
            foreach ($this->pageData['PrefixData'] as $pd) {
                $nextNumberMap[(int)$pd->PrefixUID] = $this->transactions_model->getNextTransactionNumber(
                    $pd->PrefixUID, $orgUID, $this->pageModuleUID
                );
            }
            $this->pageData['NextNumberMap'] = $nextNumberMap;

            $this->_getDispatchAddresses($orgUID);

            $this->pageData['AdditionalCharges']  = $this->_getAdditionalChargesForOrg((int)$orgUID, true);
            $this->pageData['TransactionCharges'] = $this->transactions_model->getTransactionCharges($transUID, (int)$orgUID);
            $this->pageData['TaxList']            = $this->_getTaxList();
            $this->pageData['IsEditMode']         = true;

            $this->pageData['fltStorageData'] = [];
            if (!empty($this->pageData['JwtData']->GenSettings->EnableStorage)) {
                $this->load->model('storage_model');
                $this->pageData['fltStorageData'] = $this->storage_model->getStorageDetails([], 0, 0, '', $this->_uiLang()) ?? [];
            }

            // Attachments — load server-side to avoid AJAX call on page load
            $this->pageData['SOAttachments'] = $this->transactions_model->getTransactionAttachments($transUID, $orgUID);

            $this->load->view('transactions/salesorders/forms/form', $this->pageData);

        } catch (ValidationException $e) {
            redirect('salesorders', 'refresh');
        } catch (Exception $e) {
            $this->notifyError('Salesorders::edit', $e);
            redirect('salesorders', 'refresh');
        }

    }

}