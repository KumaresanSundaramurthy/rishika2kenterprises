<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Expenses extends MY_Controller {

    public  $pageData      = [];
    protected $EndReturnData;
    protected $pageModuleUID = 114;

    public function __construct() {
        parent::__construct();
        $this->load->helper('transaction');
    }

    // ── Add Expense page ─────────────────────────────────────────────────────
    public function openCreate(): void {
        if (!$this->_loadPageTitle($this->pageModuleUID)) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }
        try {
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;
            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';
            $formResp = services_get(
                'api/expenses/getFormData?orgId=' . $orgUID . '&ExpenseUID=0',
                ['Authorization: Bearer ' . $jwtToken]
            );
            $this->_loadUpstashConfig();
            $this->pageData['Categories']         = $formResp->Data->Categories  ?? [];
            $this->pageData['PaymentTypes']        = $formResp->Data->PaymentTypes ?? [];
            $this->pageData['BankAccounts']        = $formResp->Data->BankAccounts ?? [];
            $this->pageData['ExpenseData']         = null;
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
            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';
            $formResp = services_get(
                'api/expenses/getFormData?orgId=' . $orgUID . '&ExpenseUID=' . $expenseUID,
                ['Authorization: Bearer ' . $jwtToken]
            );
            if (!$formResp || $formResp->Error || empty($formResp->Data->ExpenseData)) {
                redirect('expenses', 'refresh'); return;
            }

            $this->_loadUpstashConfig();
            $this->pageData['Categories']         = $formResp->Data->Categories         ?? [];
            $this->pageData['PaymentTypes']        = $formResp->Data->PaymentTypes        ?? [];
            $this->pageData['BankAccounts']        = $formResp->Data->BankAccounts        ?? [];
            $this->pageData['ExpenseData']         = $formResp->Data->ExpenseData;
            $this->pageData['ExpenseItems']        = (array)($formResp->Data->ExpenseItems       ?? []);
            $this->pageData['ExpenseAttachments']  = (array)($formResp->Data->ExpenseAttachments ?? []);
            $this->pageData['VendorStateCode']     = $formResp->Data->VendorStateCode     ?? '';

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
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;
            $userUID  = (int)$this->pageData['JwtData']->User->UserUID;
            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';

            $datePref = $this->getDateFilterPreference('expenses');
            $this->pageData['SavedDateRange'] = $datePref['range'];
            $this->pageData['SavedDateLabel'] = $datePref['label'];

            $pageResp = services_get(
                'api/expenses/getPageData?orgId=' . $orgUID
                    . '&branchId=' . $this->_branchUID()
                    . '&userId=' . $userUID,
                ['Authorization: Bearer ' . $jwtToken]
            );
            $this->pageData['Categories']     = $pageResp->Data->Categories  ?? [];
            $this->pageData['PaymentTypes']   = $pageResp->Data->PaymentTypes ?? [];
            $this->pageData['BankAccounts']   = $pageResp->Data->BankAccounts ?? [];
            $orgUsers                         = (array)($pageResp->Data->OrgUsers ?? []);
            $this->pageData['OrgUsers']       = $orgUsers;
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