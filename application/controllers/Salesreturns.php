<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Salesreturns extends MY_Controller {

    public $pageData = array();
    protected $pageModuleUID;

    public function __construct() {
        parent::__construct();
        $this->pageModuleUID = 106;
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
                'datePrefKey'  => 'salesreturns',
                'skipList'     => true,
                'tabSlugMap'   => ['all' => 'All', 'pending' => 'SRPending', 'paid' => 'Paid', 'cancelled' => 'Cancelled', 'drafts' => 'Draft'],
                'listViewPath' => 'transactions/salesreturns/list',
                'paginationUrl'=> '/transactions/getPageDetails/106',
            ]);
            $this->load->view('transactions/salesreturns/view', $this->pageData);
        } catch (ValidationException $e) {
            redirect('dashboard', 'refresh');
        } catch (Exception $e) {
            notifyError('Salesreturns::index', $e);
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
                $nextNumberMap[(int)$pd->PrefixUID] = $this->transactions_model->getNextTransactionNumber($pd->PrefixUID, $orgUID, $this->pageModuleUID);
            }
            $this->pageData['NextNumberMap'] = $nextNumberMap;

            $this->_getDispatchAddresses($orgUID);

            $this->pageData['PaymentTypes']    = $this->transactions_model->getPaymentTypesList();
            $this->pageData['BankAccounts']    = $this->transactions_model->getOrgBankAccounts($orgUID);

            $this->pageData['AdditionalCharges']  = $this->_getAdditionalChargesForOrg((int)$orgUID, true);
            $this->pageData['TaxList']            = $this->_getTaxList();
            $this->pageData['TransactionCharges'] = [];
            $this->pageData['IsEditMode']         = false;

            $this->load->view('transactions/salesreturns/forms/form', $this->pageData);
        } catch (ValidationException $e) {
            redirect('salesreturns', 'refresh');
        } catch (Exception $e) {
            notifyError('Salesreturns::create', $e);
            redirect('salesreturns', 'refresh');
        }
    }

    public function edit($transUID = 0) {
        try {
            $transUID = (int) $transUID;
            if ($transUID <= 0) redirect('salesreturns');

            $orgUID = $this->pageData['JwtData']->Org->OrgUID;
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;

            $this->load->model('transactions_model');
            $transData  = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$transData) redirect('salesreturns');
            $transItems = $this->transactions_model->getTransactionItems($transUID, $orgUID);
            $this->pageData['SRData']    = $transData;
            $this->pageData['SRItems']   = $transItems;

            $prefixResult                    = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID, 'Prefix.ModuleUID' => $this->pageModuleUID]);
            $this->pageData['PrefixData']    = $prefixResult->Data ?? [];
            $nextNumberMap = [];
            foreach ($this->pageData['PrefixData'] as $pd) {
                $nextNumberMap[(int)$pd->PrefixUID] = $this->transactions_model->getNextTransactionNumber($pd->PrefixUID, $orgUID, $this->pageModuleUID);
            }
            $this->pageData['NextNumberMap'] = $nextNumberMap;

            $this->_getDispatchAddresses($orgUID);

            $this->pageData['AdditionalCharges']  = $this->_getAdditionalChargesForOrg((int)$orgUID, true);
            $this->pageData['TransactionCharges'] = $this->transactions_model->getTransactionCharges($transUID, (int)$orgUID);
            $this->pageData['TaxList']            = $this->_getTaxList();
            $this->pageData['IsEditMode']         = true;
            $this->pageData['SRSerialsByProd']    = $this->_getTransSerialsGrouped($transUID, $orgUID, 'SalesReturn');

            // Attachments — load server-side to avoid AJAX call on page load
            $this->pageData['SRAttachments'] = $this->transactions_model->getTransactionAttachments($transUID, $orgUID);

            $this->load->view('transactions/salesreturns/forms/form', $this->pageData);
        } catch (ValidationException $e) {
            redirect('salesreturns', 'refresh');
        } catch (Exception $e) {
            notifyError('Salesreturns::edit', $e);
            redirect('salesreturns', 'refresh');
        }
    }
    

}
