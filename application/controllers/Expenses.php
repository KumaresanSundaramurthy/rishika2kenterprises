<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Expenses extends MY_Controller {

    public  $pageData      = [];
    private $EndReturnData;
    private $pageModuleUID = 114;

    public function __construct() {
        parent::__construct();
        $this->load->model('expenses_model');
        $this->load->model('dbwrite_model');
    }

    // ── List page ────────────────────────────────────────────────────────────
    public function index() {
        if (!$this->_loadPageTitle($this->pageModuleUID)) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }
        try {
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;
            $GeneralSettings = ($this->redisservice->getUserCache('settings')) ?? new stdClass();
            $this->pageData['JwtData']->GenSettings = $GeneralSettings;
            $limit  = $GeneralSettings->RowLimit ?? 10;
            $orgUID = $this->pageData['JwtData']->User->OrgUID;

            $filter = ['Status' => 'All'];

            $allData      = $this->expenses_model->getExpenseList($orgUID, $filter, $limit, 0);
            $allDataCount = $this->expenses_model->getExpenseCount($orgUID, $filter);
            $summaryStats = $this->expenses_model->getExpenseSummaryStats($orgUID);

            $this->pageData['ModRowData']    = $this->load->view('transactions/expenses/list', [
                'DataLists'    => $allData,
                'SerialNumber' => 0,
                'JwtData'      => $this->pageData['JwtData'],
            ], TRUE);
            $this->pageData['ModPagination'] = $this->globalservice->buildPagePaginationHtml('/expenses/getPageDetails', $allDataCount, 1, $limit);
            $this->pageData['ModAllCount']   = $allDataCount;
            $this->pageData['SummaryStats']  = $summaryStats;

            // Data for the Add/Edit modal
            $this->pageData['Categories']   = $this->expenses_model->getCategories($orgUID);
            $this->pageData['PaymentTypes'] = $this->expenses_model->getPaymentTypes();
            $this->pageData['BankAccounts'] = $this->expenses_model->getBankAccounts($orgUID);

            $this->load->view('transactions/expenses/view', $this->pageData);

        } catch (Throwable $e) {
            log_message('error', 'Expenses::index — ' . $e->getMessage());
            redirect('dashboard', 'refresh');
        }
    }

    // ── AJAX pagination ──────────────────────────────────────────────────────
    public function getPageDetails($pageNo = 1) {
        $this->EndReturnData = new stdClass();
        try {
            $pageNo = max(1, (int)$pageNo);
            $limit  = (int)($this->input->post('RowLimit') ?: 10);
            $offset = ($pageNo - 1) * $limit;
            $filter = $this->input->post('Filter') ?: [];

            $orgUID = $this->pageData['JwtData']->User->OrgUID;
            $this->pageData['JwtData']->GenSettings = ($this->redisservice->getUserCache('settings')) ?? new stdClass();

            $allData      = $this->expenses_model->getExpenseList($orgUID, $filter, $limit, $offset);
            $allDataCount = $this->expenses_model->getExpenseCount($orgUID, $filter);

            $rowHtml = $this->load->view('transactions/expenses/list', [
                'DataLists'    => $allData,
                'SerialNumber' => ($pageNo - 1) * $limit,
                'JwtData'      => $this->pageData['JwtData'],
            ], TRUE);

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->RecordHtmlData = $rowHtml;
            $this->EndReturnData->Pagination     = $this->globalservice->buildPagePaginationHtml('/expenses/getPageDetails', $allDataCount, $pageNo, $limit);
            $this->EndReturnData->TotalCount     = $allDataCount;
            $this->EndReturnData->SummaryStats   = $this->expenses_model->getExpenseSummaryStats($orgUID);

        } catch (Throwable $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Add expense (modal AJAX) ──────────────────────────────────────────────
    public function addExpense() {
        $this->EndReturnData = new stdClass();
        try {
            $this->dbwrite_model->startTransaction();

            $PostData = $this->input->post();
            $userUID  = $this->pageData['JwtData']->User->UserUID;
            $orgUID   = $this->pageData['JwtData']->User->OrgUID;

            $data = $this->_buildExpenseData($PostData, $userUID, $orgUID, true);

            $resp = $this->dbwrite_model->insertData('Transaction', 'ExpensesTbl', $data);
            if ($resp->Error) throw new Exception($resp->Message);

            $expenseUID    = (int)$resp->ID;
            $expenseNumber = 'EXP-' . str_pad($expenseUID, 4, '0', STR_PAD_LEFT);

            $this->dbwrite_model->updateData(
                'Transaction', 'ExpensesTbl',
                ['ExpenseNumber' => $expenseNumber],
                ['ExpenseUID' => $expenseUID, 'OrgUID' => $orgUID]
            );

            // Debit the bank / cash account when expense is paid
            if ($data['IsPaid']) {
                $ledgerBankUID = $data['BankAccountUID'];
                if (!$ledgerBankUID) {
                    $cashAcc = $this->expenses_model->getCashAccount($orgUID);
                    $ledgerBankUID = $cashAcc ? (int)$cashAcc->BankAccountUID : null;
                }
                if ($ledgerBankUID) {
                    $ledgerResp = $this->dbwrite_model->insertData('Transaction', 'AccountLedgerTbl', [
                        'OrgUID'         => $orgUID,
                        'BankAccountUID' => $ledgerBankUID,
                        'EntryDate'      => $data['PaymentDate'] ?: $data['ExpenseDate'],
                        'EntryType'      => 'DR',
                        'Amount'         => $data['NetAmount'],
                        'SourceType'     => 'Expense',
                        'SourceUID'      => $expenseUID,
                        'ModuleUID'      => $this->pageModuleUID,
                        'ReferenceNo'    => null,
                        'Narration'      => 'Expense paid — ' . $expenseNumber,
                        'IsActive'       => 1,
                        'IsDeleted'      => 0,
                        'CreatedBy'      => $userUID,
                        'UpdatedBy'      => $userUID,
                        'CreatedOn'      => date('Y-m-d H:i:s'),
                        'UpdatedOn'      => date('Y-m-d H:i:s'),
                    ]);
                    if ($ledgerResp->Error) throw new Exception('Ledger entry failed: ' . $ledgerResp->Message);
                }
            }

            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error         = FALSE;
            $this->EndReturnData->Message       = 'Expense recorded successfully.';
            $this->EndReturnData->ExpenseUID    = $expenseUID;
            $this->EndReturnData->ExpenseNumber = $expenseNumber;

            $this->_appendListResponse($orgUID);

        } catch (Throwable $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Update expense (modal AJAX) ───────────────────────────────────────────
    public function updateExpense() {
        $this->EndReturnData = new stdClass();
        try {
            $PostData   = $this->input->post();
            $expenseUID = (int)getPostValue($PostData, 'ExpenseUID');
            $userUID    = $this->pageData['JwtData']->User->UserUID;
            $orgUID     = $this->pageData['JwtData']->User->OrgUID;

            if ($expenseUID <= 0) throw new Exception('Invalid expense record.');

            $existing = $this->expenses_model->getExpenseById($expenseUID, $orgUID);
            if (!$existing) throw new Exception('Expense not found.');
            if (in_array($existing->DocStatus, ['Paid', 'Cancelled'])) throw new Exception('This expense cannot be edited.');

            $data = $this->_buildExpenseData($PostData, $userUID, $orgUID, false);
            unset($data['CreatedBy'], $data['CreatedOn'], $data['OrgUID'], $data['ModuleUID']);

            $resp = $this->dbwrite_model->updateData(
                'Transaction', 'ExpensesTbl', $data,
                ['ExpenseUID' => $expenseUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );
            if ($resp->Error) throw new Exception($resp->Message);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Expense updated successfully.';

            $this->_appendListResponse($orgUID);

        } catch (Throwable $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Delete expense ───────────────────────────────────────────────────────
    public function deleteExpense() {
        $this->EndReturnData = new stdClass();
        try {
            $PostData   = $this->input->post();
            $expenseUID = (int)getPostValue($PostData, 'ExpenseUID');
            $userUID    = $this->pageData['JwtData']->User->UserUID;
            $orgUID     = $this->pageData['JwtData']->User->OrgUID;

            if ($expenseUID <= 0) throw new Exception('Invalid expense record.');

            $existing = $this->expenses_model->getExpenseById($expenseUID, $orgUID);
            if (!$existing) throw new Exception('Expense not found.');
            if ($existing->DocStatus === 'Paid') throw new Exception('Paid expenses cannot be deleted.');

            $deleteData = $this->globalservice->baseDeleteArrayDetails();
            $deleteData['IsActive'] = 0;

            $resp = $this->dbwrite_model->updateData(
                'Transaction', 'ExpensesTbl', $deleteData,
                ['ExpenseUID' => $expenseUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );
            if ($resp->Error) throw new Exception($resp->Message);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Expense deleted.';

            $this->_appendListResponse($orgUID);

        } catch (Throwable $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Update status (Pending → Paid / Cancelled) ───────────────────────────
    public function updateExpenseStatus() {
        $this->EndReturnData = new stdClass();
        try {
            $PostData   = $this->input->post();
            $expenseUID = (int)getPostValue($PostData, 'ExpenseUID');
            $newStatus  = trim(getPostValue($PostData, 'Status'));
            $userUID    = $this->pageData['JwtData']->User->UserUID;
            $orgUID     = $this->pageData['JwtData']->User->OrgUID;

            if ($expenseUID <= 0)  throw new Exception('Invalid expense record.');
            if (empty($newStatus)) throw new Exception('Status is required.');

            $existing = $this->expenses_model->getExpenseById($expenseUID, $orgUID);
            if (!$existing) throw new Exception('Expense not found.');

            $allowed = ['Pending' => ['Paid', 'Cancelled']];

            if (!isset($allowed[$existing->DocStatus]) || !in_array($newStatus, $allowed[$existing->DocStatus])) {
                throw new Exception('Invalid status transition.');
            }

            $updateData = [
                'DocStatus' => $newStatus,
                'IsPaid'    => ($newStatus === 'Paid') ? 1 : $existing->IsPaid,
                'UpdatedBy' => $userUID,
                'UpdatedOn' => date('Y-m-d H:i:s'),
            ];

            $resp = $this->dbwrite_model->updateData(
                'Transaction', 'ExpensesTbl', $updateData,
                ['ExpenseUID' => $expenseUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );
            if ($resp->Error) throw new Exception($resp->Message);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Status updated to ' . $newStatus . '.';

            $this->_appendListResponse($orgUID);

        } catch (Throwable $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Get single expense detail ────────────────────────────────────────────
    public function getExpenseDetail() {
        $this->EndReturnData = new stdClass();
        try {
            $PostData   = $this->input->post();
            $expenseUID = (int)getPostValue($PostData, 'ExpenseUID');
            $orgUID     = $this->pageData['JwtData']->User->OrgUID;

            if ($expenseUID <= 0) throw new Exception('Invalid expense record.');

            $expense = $this->expenses_model->getExpenseById($expenseUID, $orgUID);
            if (!$expense) throw new Exception('Expense not found.');

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $expense;

        } catch (Throwable $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Get category list ─────────────────────────────────────────────────────
    public function getCategories() {
        $this->EndReturnData = new stdClass();
        try {
            $orgUID = $this->pageData['JwtData']->User->OrgUID;
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $this->expenses_model->getCategories($orgUID);
        } catch (Throwable $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Add new category ──────────────────────────────────────────────────────
    public function addCategory() {
        $this->EndReturnData = new stdClass();
        try {
            $PostData     = $this->input->post();
            $categoryName = trim(getPostValue($PostData, 'CategoryName'));
            $orgUID       = $this->pageData['JwtData']->User->OrgUID;
            $userUID      = $this->pageData['JwtData']->User->UserUID;

            if (empty($categoryName)) throw new Exception('Category name is required.');

            $resp = $this->dbwrite_model->insertData('Transaction', 'ExpenseCategoryTbl', [
                'OrgUID'       => $orgUID,
                'CategoryName' => $categoryName,
                'IsDefault'    => 0,
                'IsActive'     => 1,
                'IsDeleted'    => 0,
                'CreatedBy'    => $userUID,
                'UpdatedBy'    => $userUID,
                'CreatedOn'    => date('Y-m-d H:i:s'),
                'UpdatedOn'    => date('Y-m-d H:i:s'),
            ]);
            if ($resp->Error) throw new Exception($resp->Message);

            $this->EndReturnData->Error        = FALSE;
            $this->EndReturnData->Message      = 'Category added.';
            $this->EndReturnData->CategoryUID  = $resp->ID;
            $this->EndReturnData->CategoryName = $categoryName;

        } catch (Throwable $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // Private helpers
    // ═══════════════════════════════════════════════════════════════════════

    // Builds refreshed list HTML + pagination + stats and appends to EndReturnData
    private function _appendListResponse($orgUID) {
        $GeneralSettings = ($this->redisservice->getUserCache('settings')) ?? new stdClass();
        $this->pageData['JwtData']->GenSettings = $GeneralSettings;

        $filterJson = $this->input->post('Filter');
        $filter = ($filterJson && ($decoded = json_decode($filterJson, true))) ? $decoded : ['Status' => 'All'];
        $limit  = (int)($this->input->post('RowLimit') ?: ($GeneralSettings->RowLimit ?? 10));

        $allData  = $this->expenses_model->getExpenseList($orgUID, $filter, $limit, 0);
        $allCount = $this->expenses_model->getExpenseCount($orgUID, $filter);

        $rowHtml = $this->load->view('transactions/expenses/list', [
            'DataLists'    => $allData,
            'SerialNumber' => 0,
            'JwtData'      => $this->pageData['JwtData'],
        ], TRUE);

        $this->EndReturnData->RecordHtmlData = $rowHtml;
        $this->EndReturnData->TotalCount     = $allCount;
        $this->EndReturnData->Pagination     = $this->globalservice->buildPagePaginationHtml('/expenses/getPageDetails', $allCount, 1, $limit);
        $this->EndReturnData->SummaryStats   = $this->expenses_model->getExpenseSummaryStats($orgUID);
    }

    // Validates POST, computes amounts, returns data array for insert/update
    private function _buildExpenseData($PostData, $userUID, $orgUID, $isCreate) {
        $amount         = (float) getPostValue($PostData, 'Amount');
        $isPaid         = (int)   getPostValue($PostData, 'IsPaid')        === 1 ? 1 : 0;
        $categoryUID    = (int)   getPostValue($PostData, 'CategoryUID')   ?: NULL;
        $paymentTypeUID = (int)   getPostValue($PostData, 'PaymentTypeUID') ?: NULL;
        $bankAccountUID = (int)   getPostValue($PostData, 'BankAccountUID') ?: NULL;
        $expenseDate    =         getPostValue($PostData, 'ExpenseDate')   ?: date('Y-m-d');
        $paymentDate    =         getPostValue($PostData, 'PaymentDate')   ?: NULL;
        $notes          =         getPostValue($PostData, 'Notes')         ?: NULL;
        $paymentNotes   =         getPostValue($PostData, 'PaymentNotes')  ?: NULL;

        if ($amount <= 0)                throw new Exception('Expense amount must be greater than 0.');
        if (empty($expenseDate))         throw new Exception('Expense date is required.');
        if ($isPaid && !$paymentTypeUID) throw new Exception('Please select a payment type.');

        $data = [
            'OrgUID'         => $orgUID,
            'ModuleUID'      => $this->pageModuleUID,
            'ExpenseDate'    => $expenseDate,
            'Amount'         => $amount,
            'TaxApplicable'  => 0,
            'TaxPercentage'  => 0,
            'TaxAmount'      => 0,
            'TDSApplicable'  => 0,
            'TDSPercentage'  => 0,
            'TDSAmount'      => 0,
            'NetAmount'      => $amount,
            'CategoryUID'    => $categoryUID,
            'Notes'          => $notes,
            'DocStatus'      => $isPaid ? 'Paid' : 'Pending',
            'IsPaid'         => $isPaid,
            'PaymentTypeUID' => $isPaid ? $paymentTypeUID : NULL,
            'BankAccountUID' => ($isPaid && $bankAccountUID) ? $bankAccountUID : NULL,
            'PaymentDate'    => $isPaid ? ($paymentDate ?: $expenseDate) : NULL,
            'PaymentNotes'   => $isPaid ? $paymentNotes : NULL,
            'IsActive'       => 1,
            'IsDeleted'      => 0,
            'UpdatedBy'      => $userUID,
            'UpdatedOn'      => date('Y-m-d H:i:s'),
        ];

        if ($isCreate) {
            $data['CreatedBy'] = $userUID;
            $data['CreatedOn'] = date('Y-m-d H:i:s');
        }

        return $data;
    }
}
