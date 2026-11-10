<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Purchaseorders extends MY_Controller {

    public $pageData = array();
    protected $pageModuleUID;

    public function __construct() {
        parent::__construct();

        $this->pageModuleUID = 104;
        $this->load->helper('transaction');

    }

    public function index(): void {
        if (!$this->_loadPageTitle($this->pageModuleUID)) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }
        try {
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;
            $this->_loadTransactionIndexPage([
                'datePrefKey'  => 'purchaseorders',
                'skipList'     => true,
                'tabSlugMap'   => ['all' => 'All', 'received' => 'Received', 'closed' => 'Closed', 'cancelled' => 'Cancelled', 'draft' => 'Draft'],
                'listViewPath' => 'transactions/purchaseorders/list',
                'paginationUrl'=> '/transactions/getPageDetails/104',
            ]);
            $this->load->view('transactions/purchaseorders/view', $this->pageData);
        } catch (ValidationException $e) {
            redirect('dashboard', 'refresh');
        } catch (Exception $e) {
            $this->notifyError('Purchaseorders::index', $e);
            redirect('dashboard', 'refresh');
        }
    }

    public function create() {

        try {

            $orgUID = $this->pageData['JwtData']->Org->OrgUID;
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;

            $this->load->model('transactions_model');

            $prefixResult                    = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID, 'Prefix.ModuleUID' => $this->pageModuleUID]);
            $this->pageData['PrefixData']    = $prefixResult->Data ?? [];

            $nextNumberMap = [];
            foreach ($this->pageData['PrefixData'] as $pd) {
                $nextNumberMap[(int)$pd->PrefixUID] = $this->transactions_model->getNextTransactionNumber(
                    $pd->PrefixUID, $orgUID, $this->pageModuleUID
                );
            }
            $this->pageData['NextNumberMap'] = $nextNumberMap;

            $this->pageData['AdditionalCharges']  = $this->_getAdditionalChargesForOrg((int)$orgUID, true);
            $this->pageData['TaxList']            = $this->_getTaxList();
            $this->pageData['TransactionCharges'] = [];
            $this->pageData['IsEditMode']         = false;

            $this->_loadUpstashConfig();

            $this->load->view('transactions/purchaseorders/forms/form', $this->pageData);

        } catch (ValidationException $e) {
            redirect('purchaseorders', 'refresh');
        } catch (Exception $e) {
            $this->notifyError('Purchaseorders::create', $e);
            redirect('purchaseorders', 'refresh');
        }

    }

    public function edit($transUID = 0) {

        try {

            $transUID = (int) $transUID;
            if ($transUID <= 0) redirect('purchaseorders');

            $orgUID = $this->pageData['JwtData']->Org->OrgUID;
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;

            $this->load->model('transactions_model');

            $poData  = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID, $this->_uiLang());
            if (!$poData) redirect('purchaseorders');

            $poItems = $this->transactions_model->getTransactionItems($transUID, $orgUID);

            $this->pageData['POData']  = $poData;
            $this->pageData['POItems'] = $poItems;

            $prefixResult                    = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID, 'Prefix.ModuleUID' => $this->pageModuleUID]);
            $this->pageData['PrefixData']    = $prefixResult->Data ?? [];

            $nextNumberMap = [];
            foreach ($this->pageData['PrefixData'] as $pd) {
                $nextNumberMap[(int)$pd->PrefixUID] = $this->transactions_model->getNextTransactionNumber(
                    $pd->PrefixUID, $orgUID, $this->pageModuleUID
                );
            }
            $this->pageData['NextNumberMap'] = $nextNumberMap;

            // Load vendor address for inter-state detection
            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';
            $vendResp  = services_get(
                'api/vendors/getVendorForModal/' . (int)$poData->PartyUID . '?orgId=' . $orgUID,
                ['Authorization: Bearer ' . $jwtToken]
            );
            $this->pageData['VendorAddr'] = $vendResp->Data->BillingAddr ?? null;

            $this->pageData['AdditionalCharges']  = $this->_getAdditionalChargesForOrg((int)$orgUID, true);
            $this->pageData['TransactionCharges'] = $this->transactions_model->getTransactionCharges($transUID, (int)$orgUID);
            $this->pageData['TaxList']            = $this->_getTaxList();
            $this->pageData['IsEditMode']         = true;

            $this->_loadUpstashConfig();

            // Attachments — load server-side to avoid AJAX call on page load
            $this->pageData['POAttachments'] = $this->transactions_model->getTransactionAttachments($transUID, $orgUID);

            $this->load->view('transactions/purchaseorders/forms/form', $this->pageData);

        } catch (ValidationException $e) {
            redirect('purchaseorders', 'refresh');
        } catch (Exception $e) {
            $this->notifyError('Purchaseorders::edit', $e);
            redirect('purchaseorders', 'refresh');
        }

    }


}
