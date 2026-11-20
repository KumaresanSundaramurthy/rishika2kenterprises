<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Expenses extends MY_Controller {

    public  $pageData      = [];
    protected $pageModuleUID = 114;

    public function __construct() {
        parent::__construct();
        $this->load->helper('transaction');
    }

    // ── List page ────────────────────────────────────────────────────────────
    public function index() {
        if (!$this->_loadPageTitle($this->pageModuleUID)) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }
        try {
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;
            $datePref = $this->getDateFilterPreference('expenses');
            $this->pageData['SavedDateRange'] = $datePref['range'];
            $this->pageData['SavedDateLabel'] = $datePref['label'];

            $pageResp = services_get(
                'api/expenses/getCategories?orgId=' . $orgUID,
                withJwt: true
            );
            $this->pageData['Categories'] = is_array($pageResp?->Data) ? $pageResp->Data : [];

            $this->load->view('transactions/expenses/view', $this->pageData);

        } catch (ValidationException $e) {
            redirect('dashboard', 'refresh');
        } catch (Throwable $e) {
            notifyError('Expenses::index', $e);
            redirect('dashboard', 'refresh');
        }
    }

    // ── Add Expense page ─────────────────────────────────────────────────────
    public function openCreate(): void {
        if (!$this->_loadPageTitle($this->pageModuleUID)) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }
        try {
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;
            $catResp  = services_get(
                'api/expenses/getCategories?orgId=' . $orgUID,
                withJwt: true
            );
            $this->_loadUpstashConfig();
            $this->pageData['Categories']  = is_array($catResp?->Data) ? $catResp->Data : [];
            $this->pageData['ExpenseData'] = null;
            $this->pageData['ExpenseItems']        = [];
            $this->pageData['ExpenseAttachments']  = [];
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

            $formResp = services_get(
                'api/expenses/getFormData?orgId=' . $orgUID . '&ExpenseUID=' . $expenseUID,
                withJwt: true
            );
            if (!$formResp || !empty($formResp->Error) || empty($formResp->ExpenseData)) {
                redirect('expenses', 'refresh'); return;
            }

            $this->_loadUpstashConfig();
            $this->pageData['Categories']         = is_array($formResp->Categories)        ? $formResp->Categories        : [];
            $this->pageData['PaymentTypes']        = is_array($formResp->PaymentTypes)      ? $formResp->PaymentTypes      : [];
            $this->pageData['BankAccounts']        = is_array($formResp->BankAccounts)      ? $formResp->BankAccounts      : [];
            $this->pageData['ExpenseData']         = $formResp->ExpenseData;
            $this->pageData['ExpenseItems']        = (array)($formResp->ExpenseItems        ?? []);
            $this->pageData['ExpenseAttachments']  = (array)($formResp->ExpenseAttachments  ?? []);
            $this->pageData['VendorStateCode']     = $formResp->VendorStateCode             ?? '';

            $this->load->view('transactions/expenses/forms/form', $this->pageData);
        } catch (ValidationException $e) {
            notifyError('Expenses::openEdit', $e);
            redirect('expenses', 'refresh');
        } catch (Throwable $e) {
            notifyError('Expenses::openEdit', $e);
            redirect('expenses', 'refresh');
        }
    }

}