<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Purchasereturns extends MY_Controller {

    public $pageData = array();
    protected $pageModuleUID;

    public function __construct() {
        parent::__construct();
        $this->pageModuleUID = 108;
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
                'datePrefKey'  => 'purchasereturns',
                'skipList'     => true,
                'tabSlugMap'   => ['all' => 'All', 'pending' => 'PRPending', 'settled' => 'Paid', 'cancelled' => 'Cancelled', 'drafts' => 'Draft'],
                'listViewPath' => 'transactions/purchasereturns/list',
                'paginationUrl'=> '/transactions/getPageDetails/108',
            ]);
            $this->load->view('transactions/purchasereturns/view', $this->pageData);
        } catch (ValidationException $e) {
            redirect('dashboard', 'refresh');
        } catch (Exception $e) {
            notifyError('Purchasereturns::index', $e);
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

            $this->pageData['PaymentTypes'] = $this->transactions_model->getPaymentTypesList();
            $this->pageData['BankAccounts'] = $this->transactions_model->getOrgBankAccounts($orgUID);

            $this->_getDispatchAddresses($orgUID);
            $this->_loadUpstashConfig();

            $this->pageData['AdditionalCharges']  = $this->_getAdditionalChargesForOrg((int)$orgUID, true);
            $this->pageData['TaxList']            = $this->_getTaxList();
            $this->pageData['TransactionCharges'] = [];
            $this->pageData['IsEditMode']         = false;

            $this->load->view('transactions/purchasereturns/forms/form', $this->pageData);
        } catch (ValidationException $e) {
            redirect('purchasereturns', 'refresh');
        } catch (Exception $e) {
            notifyError('Purchasereturns::create', $e);
            redirect('purchasereturns', 'refresh');
        }
    }

    public function edit($transUID = 0) {
        try {
            $transUID = (int) $transUID;
            if ($transUID <= 0) redirect('purchasereturns');

            $orgUID = $this->pageData['JwtData']->Org->OrgUID;
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;

            $this->load->model('transactions_model');
            $transData  = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID, $this->_uiLang());
            if (!$transData) redirect('purchasereturns');
            $transItems = $this->transactions_model->getTransactionItems($transUID, $orgUID);
            $this->pageData['PRData']    = $transData;
            $this->pageData['PRItems']   = $transItems;

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
            $this->load->model('vendors_model');
            $vendorAddrArr                = $this->vendors_model->getVendorAddress(['VendAddress.VendorUID' => (int)$transData->PartyUID, 'VendAddress.OrgUID' => $orgUID], $this->_uiLang());
            $this->pageData['VendorAddr'] = !empty($vendorAddrArr) ? $vendorAddrArr[0] : null;

            $this->pageData['IsEditMode']         = true;
            $this->pageData['PRSerialsByProd']    = $this->_getTransSerialsGrouped($transUID, $orgUID, 'PurchaseReturn');

            $this->_getDispatchAddresses($orgUID);
            $this->_loadUpstashConfig();

            // Attachments — load server-side to avoid AJAX call on page load
            $this->pageData['PRAttachments'] = $this->transactions_model->getTransactionAttachments($transUID, $orgUID);

            $this->load->view('transactions/purchasereturns/forms/form', $this->pageData);
        } catch (ValidationException $e) {
            redirect('purchasereturns', 'refresh');
        } catch (Exception $e) {
            notifyError('Purchasereturns::edit', $e);
            redirect('purchasereturns', 'refresh');
        }
    }

}