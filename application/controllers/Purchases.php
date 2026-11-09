<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Purchases extends MY_Controller {

    public $pageData = array();
    protected $pageModuleUID;

    public function __construct() {
        parent::__construct();

        $this->pageModuleUID = 105;
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
                'datePrefKey'  => 'purchases',
                'skipList'     => true,
                'tabSlugMap'   => ['all' => 'All', 'pending' => 'Pending', 'paid' => 'Paid', 'cancelled' => 'Cancelled', 'draft' => 'Draft'],
                'listViewPath' => 'transactions/purchases/list',
                'paginationUrl'=> '/transactions/getPageDetails/105',
            ]);
            $this->load->view('transactions/purchases/view', $this->pageData);
        } catch (ValidationException $e) {
            redirect('dashboard', 'refresh');
        } catch (Exception $e) {
            notifyError('Purchases::index', $e);
            redirect('dashboard', 'refresh');
        }
    }

    public function purchasePayments(): void {
        try {
            $this->load->view('transactions/purchases/payments', $this->pageData);
        } catch (ValidationException $e) {
            redirect('purchases', 'refresh');
        } catch (Exception $e) {
            notifyError('Purchases::purchasePayments', $e);
            redirect('purchases', 'refresh');
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

            // Pre-fill from Purchase Order if converting
            $fromPOUID = (int) $this->input->get('fromPurchaseOrder');
            $this->pageData['FromPOUID'] = $fromPOUID;
            $this->pageData['POData']    = null;
            $this->pageData['POItems']   = [];
            $this->pageData['AdditionalCharges']  = $this->_getAdditionalChargesForOrg((int)$orgUID, true);
            $this->pageData['TaxList']            = $this->_getTaxList();
            $this->pageData['TransactionCharges'] = [];
            $this->pageData['IsEditMode']         = false;
            if ($fromPOUID > 0) {
                $poData  = $this->transactions_model->getTransactionById($fromPOUID, $orgUID, 104);
                $poItems = $poData ? $this->transactions_model->getTransactionItems($fromPOUID, $orgUID) : [];
                $this->pageData['POData']  = $poData;
                $this->pageData['POItems'] = $poItems;
            }

            $this->pageData['PaymentTypes'] = $this->transactions_model->getPaymentTypesList();
            $this->pageData['BankAccounts'] = $this->transactions_model->getOrgBankAccounts($orgUID);

            $this->_getDispatchAddresses($orgUID);
            $this->_loadUpstashConfig();

            $this->load->view('transactions/purchases/forms/form', $this->pageData);

        } catch (ValidationException $e) {
            redirect('purchases', 'refresh');
        } catch (Exception $e) {
            notifyError('Purchases::create', $e);
            redirect('purchases', 'refresh');
        }

    }

    public function edit($transUID = 0) {

        try {

            $transUID = (int) $transUID;
            if ($transUID <= 0) redirect('purchases');

            $orgUID = $this->pageData['JwtData']->Org->OrgUID;
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;

            $this->load->model('transactions_model');

            $purchData  = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID, $this->_uiLang());
            if (!$purchData) redirect('purchases');

            $purchItems = $this->transactions_model->getTransactionItems($transUID, $orgUID);

            $this->pageData['PurchData']          = $purchData;
            $this->pageData['PurchItems']         = $purchItems;
            $this->pageData['PurchAttachments']   = $this->transactions_model->getTransactionAttachments($transUID, $orgUID);
            $this->pageData['PurchSerialsByProd'] = $this->_getTransSerialsGrouped($transUID, $orgUID, 'Purchase');

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
            $this->load->model('vendors_model');
            $vendorAddrArr                = $this->vendors_model->getVendorAddress(['VendAddress.VendorUID' => (int)$purchData->PartyUID, 'VendAddress.OrgUID' => $orgUID], $this->_uiLang());
            $this->pageData['VendorAddr'] = !empty($vendorAddrArr) ? $vendorAddrArr[0] : null;

            $this->pageData['AdditionalCharges']  = $this->_getAdditionalChargesForOrg((int)$orgUID, true);
            $this->pageData['TransactionCharges'] = $this->transactions_model->getTransactionCharges($transUID, (int)$orgUID);
            $this->pageData['TaxList']            = $this->_getTaxList();
            $this->pageData['IsEditMode']         = true;

            $this->pageData['PaymentTypes'] = $this->transactions_model->getPaymentTypesList();
            $this->pageData['BankAccounts'] = $this->transactions_model->getOrgBankAccounts($orgUID);

            $this->_getDispatchAddresses($orgUID);
            $this->_loadUpstashConfig();

            $this->load->view('transactions/purchases/forms/form', $this->pageData);

        } catch (ValidationException $e) {
            redirect('purchases', 'refresh');
        } catch (Exception $e) {
            notifyError('Purchases::edit', $e);
            redirect('purchases', 'refresh');
        }

    }
    
}