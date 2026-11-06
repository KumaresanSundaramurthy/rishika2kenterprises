<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Expenses extends MY_Controller {

    public  $pageData      = [];
    protected $EndReturnData;
    protected $pageModuleUID = 114;

    public function __construct() {
        parent::__construct();
        $this->load->helper('transaction');
        $this->load->model('expenses_model');
        $this->load->model('transactions_model');
    }

    // ── Add Expense page ─────────────────────────────────────────────────────
    public function openCreate(): void {
        if (!$this->_loadPageTitle($this->pageModuleUID)) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }
        try {
            $orgUID = $this->pageData['JwtData']->Org->OrgUID;
            $this->_loadUpstashConfig();
            $this->pageData['Categories']        = $this->expenses_model->getCategories($orgUID);
            $this->pageData['PaymentTypes']      = $this->expenses_model->getPaymentTypes();
            $this->pageData['BankAccounts']      = $this->expenses_model->getBankAccounts($orgUID);
            $this->pageData['ExpenseData']       = null;
            $this->pageData['ExpenseItems']      = [];
            $this->pageData['ExpenseAttachments']= [];
            $this->load->view('transactions/expenses/forms/form', $this->pageData);
        } catch (ValidationException $e) {
            redirect('expenses', 'refresh');
        } catch (Throwable $e) {
            notifyError('Expenses::openCreate', $e);
            redirect('expenses', 'refresh');
        }
    }

    // ── Edit Expense page ─────────────────────────────────────────────────────
    public function openEdit(int $expenseUID = 0): void {
        if (!$this->_loadPageTitle($this->pageModuleUID)) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }
        try {
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;
            $expense  = $this->expenses_model->getExpenseById($expenseUID, $orgUID);
            if (!$expense) { redirect('expenses', 'refresh'); return; }

            $this->_loadUpstashConfig();
            $this->pageData['Categories']        = $this->expenses_model->getCategories($orgUID);
            $this->pageData['PaymentTypes']      = $this->expenses_model->getPaymentTypes();
            $this->pageData['BankAccounts']      = $this->expenses_model->getBankAccounts($orgUID);
            $this->pageData['ExpenseData']       = $expense;
            $this->pageData['ExpenseItems']      = $this->expenses_model->getExpenseItems($expenseUID, $orgUID);
            $this->load->model('transactions_model');
            $this->pageData['ExpenseAttachments'] = $this->transactions_model->getExpenseIncomeAttachments($expenseUID, $orgUID, 'Expense');

            // Vendor billing state — used by view to determine intra/inter-state GST
            $vendorStateCode = '';
            if ((int)($expense->VendorUID ?? 0) > 0) {
                $this->load->model('vendors_model');
                $vendAddr = $this->vendors_model->getVendorAddress([
                    'VendAddress.VendorUID'   => (int)$expense->VendorUID,
                    'VendAddress.AddressType' => 'Billing',
                ]);
                $vendorStateCode = !empty($vendAddr) ? ($vendAddr[0]->State ?? '') : '';
            }
            $this->pageData['VendorStateCode'] = $vendorStateCode;

            $this->load->view('transactions/expenses/forms/form', $this->pageData);
        } catch (ValidationException $e) {
            redirect('expenses', 'refresh');
        } catch (Throwable $e) {
            notifyError('Expenses::openEdit', $e);
            redirect('expenses', 'refresh');
        }
    }

    // ── List page ────────────────────────────────────────────────────────────
    public function index() {
        if (!$this->_loadPageTitle($this->pageModuleUID)) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }
        try {
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;
            $orgUID = $this->pageData['JwtData']->Org->OrgUID;

            $datePref = $this->getDateFilterPreference('expenses');
            $this->pageData['SavedDateRange'] = $datePref['range'];
            $this->pageData['SavedDateLabel'] = $datePref['label'];

            // Data for the Add/Edit modal
            $this->pageData['Categories']   = $this->expenses_model->getCategories($orgUID);
            $this->pageData['PaymentTypes'] = $this->expenses_model->getPaymentTypes();
            $this->pageData['BankAccounts'] = $this->expenses_model->getBankAccounts($orgUID);

            // Org users for column filter
            $orgUsers = $this->_requireCache($this->redisservice->orgKey('org-users'));
            if (!$orgUsers) return;
            $this->pageData['OrgUsers']      = $orgUsers;
            $this->pageData['ShowUserFilter'] = count($orgUsers) > 1;

            $this->load->view('transactions/expenses/view', $this->pageData);

        } catch (ValidationException $e) {
            redirect('dashboard', 'refresh');
        } catch (Throwable $e) {
            notifyError('Expenses::index', $e);
            redirect('dashboard', 'refresh');
        }
    }

}