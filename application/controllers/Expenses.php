<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Expenses extends MY_Controller {

    public  $pageData      = [];
    private $EndReturnData;
    private $pageModuleUID = 114;

    public function __construct() {
        parent::__construct();
        $this->load->helper('transaction');
        $this->load->model('expenses_model');
        $this->load->model('dbwrite_model');
        $this->load->model('transactions_model');
    }

    // ── List page ────────────────────────────────────────────────────────────
    public function index() {
        if (!$this->_loadPageTitle($this->pageModuleUID)) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }
        try {
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;
            $GeneralSettings = $this->pageData['JwtData']->GenSettings ?? new stdClass();
            $limit  = $GeneralSettings->RowLimit ?? 10;
            $orgUID = $this->pageData['JwtData']->Org->OrgUID;

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

            // Org users for column filter
            $this->load->model('users_model');
            $this->pageData['OrgUsers'] = $this->users_model->getOrgUsersForCache($orgUID);

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

            $orgUID = $this->pageData['JwtData']->Org->OrgUID;

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
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;

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

            if ($data['IsPaid']) {
                $this->_insertExpensePayment($PostData, $orgUID, $userUID, $expenseUID, $expenseNumber, $data['NetAmount'], $data['ExpenseDate']);
            }

            $this->dbwrite_model->commitTransaction();

            $this->_saveAttachments($expenseUID, 'Expense');

            $this->EndReturnData->Error         = FALSE;
            $this->EndReturnData->Message       = 'Expense recorded successfully.';
            $this->EndReturnData->ExpenseUID    = $expenseUID;
            $this->EndReturnData->ExpenseNumber = $expenseNumber;

        } catch (Throwable $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        if (!$this->EndReturnData->Error) {
            $this->_appendListResponse($orgUID);
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
            $orgUID     = $this->pageData['JwtData']->Org->OrgUID;

            if ($expenseUID <= 0) throw new Exception('Invalid expense record.');

            $existing = $this->expenses_model->getExpenseById($expenseUID, $orgUID);
            if (!$existing) throw new Exception('Expense not found.');
            if ($existing->DocStatus === 'Cancelled') throw new Exception('This expense cannot be edited.');

            $data = $this->_buildExpenseData($PostData, $userUID, $orgUID, false);
            unset($data['CreatedBy'], $data['CreatedOn'], $data['OrgUID'], $data['ModuleUID']);

            // Preserve DocStatus/IsPaid for Paid expenses — only edit allowed, not payment reversal
            if ($existing->DocStatus === 'Paid') {
                $data['DocStatus'] = 'Paid';
                $data['IsPaid']    = 1;
            }

            $resp = $this->dbwrite_model->updateData(
                'Transaction', 'ExpensesTbl', $data,
                ['ExpenseUID' => $expenseUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );
            if ($resp->Error) throw new Exception($resp->Message);

            $this->_saveAttachments($expenseUID, 'Expense');

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Expense updated successfully.';

        } catch (Throwable $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        if (!$this->EndReturnData->Error) {
            $this->_appendListResponse($orgUID);
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
            $orgUID     = $this->pageData['JwtData']->Org->OrgUID;

            if ($expenseUID <= 0) throw new Exception('Invalid expense record.');

            $existing = $this->expenses_model->getExpenseById($expenseUID, $orgUID);
            if (!$existing) throw new Exception('Expense not found.');
            if ($existing->DocStatus === 'Cancelled') throw new Exception('Cancelled expenses cannot be deleted.');

            $deleteData = $this->globalservice->baseDeleteArrayDetails();
            $deleteData['IsActive'] = 0;

            $resp = $this->dbwrite_model->updateData(
                'Transaction', 'ExpensesTbl', $deleteData,
                ['ExpenseUID' => $expenseUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );
            if ($resp->Error) throw new Exception($resp->Message);

            // Soft-delete the linked payment record (if any)
            if (!empty($existing->PaymentUID)) {
                $this->dbwrite_model->updateData(
                    'Transaction', 'PaymentsTbl',
                    ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID, 'UpdatedOn' => date('Y-m-d H:i:s')],
                    ['PaymentUID' => (int)$existing->PaymentUID, 'OrgUID' => $orgUID]
                );
            }

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Expense deleted.';

        } catch (Throwable $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        if (!$this->EndReturnData->Error) {
            $this->_appendListResponse($orgUID);
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Duplicate expense (creates a Pending copy dated today) ───────────────
    public function duplicateExpense() {
        $this->EndReturnData = new stdClass();
        try {
            $PostData   = $this->input->post();
            $expenseUID = (int)getPostValue($PostData, 'ExpenseUID');
            $userUID    = $this->pageData['JwtData']->User->UserUID;
            $orgUID     = $this->pageData['JwtData']->Org->OrgUID;

            if ($expenseUID <= 0) throw new Exception('Invalid expense record.');

            $src = $this->expenses_model->getExpenseById($expenseUID, $orgUID);
            if (!$src) throw new Exception('Expense not found.');

            $data = [
                'OrgUID'        => $orgUID,
                'ModuleUID'     => $this->pageModuleUID,
                'ExpenseDate'   => date('Y-m-d'),
                'Amount'        => $src->Amount,
                'TaxApplicable' => $src->TaxApplicable,
                'TaxPercentage' => $src->TaxPercentage ?? 0,
                'TaxAmount'     => $src->TaxAmount,
                'TDSApplicable' => $src->TDSApplicable,
                'TDSPercentage' => $src->TDSPercentage ?? 0,
                'TDSAmount'     => $src->TDSAmount,
                'NetAmount'     => $src->NetAmount,
                'CategoryUID'   => $src->CategoryUID,
                'Notes'         => $src->Notes,
                'DocStatus'     => 'Pending',
                'IsPaid'        => 0,
                'IsActive'      => 1,
                'IsDeleted'     => 0,
                'CreatedBy'     => $userUID,
                'UpdatedBy'     => $userUID,
                'CreatedOn'     => date('Y-m-d H:i:s'),
                'UpdatedOn'     => date('Y-m-d H:i:s'),
            ];

            $resp = $this->dbwrite_model->insertData('Transaction', 'ExpensesTbl', $data);
            if ($resp->Error) throw new Exception($resp->Message);

            $newUID    = (int)$resp->ID;
            $newNumber = 'EXP-' . str_pad($newUID, 4, '0', STR_PAD_LEFT);
            $this->dbwrite_model->updateData(
                'Transaction', 'ExpensesTbl',
                ['ExpenseNumber' => $newNumber],
                ['ExpenseUID' => $newUID, 'OrgUID' => $orgUID]
            );

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = $newNumber . ' created as a duplicate.';

        } catch (Throwable $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        if (!$this->EndReturnData->Error) {
            $this->_appendListResponse($orgUID);
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Record payment via shared modal ─────────────────────────────────────────
    public function recordPayment() {
        $this->EndReturnData = new stdClass();
        try {
            $PostData   = $this->input->post();
            $expenseUID = (int)getPostValue($PostData, 'TransUID');
            $userUID    = $this->pageData['JwtData']->User->UserUID;
            $orgUID     = $this->pageData['JwtData']->Org->OrgUID;

            if ($expenseUID <= 0) throw new Exception('Invalid expense record.');

            $existing = $this->expenses_model->getExpenseById($expenseUID, $orgUID);
            if (!$existing) throw new Exception('Expense not found.');
            if (!in_array($existing->DocStatus, ['Pending', 'Partial'])) {
                throw new Exception('Payment can only be recorded for Pending or Partially Paid expenses.');
            }

            $paymentTypeUID = (int)getPostValue($PostData, 'PaymentTypeUID') ?: NULL;
            $bankAccountUID = (int)getPostValue($PostData, 'BankAccountUID') ?: NULL;
            $paymentDate    = getPostValue($PostData, 'PaymentDate') ?: $existing->ExpenseDate;
            $referenceNo    = getPostValue($PostData, 'ReferenceNo') ?: NULL;
            $notes          = getPostValue($PostData, 'Notes')       ?: NULL;
            $paymentAmount  = round((float)getPostValue($PostData, 'Amount'), 2);

            if (!$paymentTypeUID) throw new Exception('Please select a payment type.');
            if ($paymentAmount <= 0) throw new Exception('Payment amount must be greater than 0.');

            $netAmount     = round((float)$existing->NetAmount, 2);
            $existingPaid  = round((float)($existing->PaidAmount ?? 0), 2);
            $newPaidAmount = round($existingPaid + $paymentAmount, 2);

            if ($newPaidAmount > $netAmount + 0.01) {
                throw new Exception('Total payments (' . $newPaidAmount . ') cannot exceed the expense amount (' . $netAmount . ').');
            }

            $newPaidAmount  = min($newPaidAmount, $netAmount);
            $balanceAmount  = max(0, round($netAmount - $newPaidAmount, 2));
            $isFullyPaid    = ($balanceAmount <= 0) ? 1 : 0;
            $newStatus      = $isFullyPaid ? 'Paid' : 'Partial';

            $this->dbwrite_model->startTransaction();

            $resp = $this->dbwrite_model->updateData(
                'Transaction', 'ExpensesTbl',
                [
                    'DocStatus'     => $newStatus,
                    'IsPaid'        => $isFullyPaid,
                    'PaidAmount'    => $newPaidAmount,
                    'BalanceAmount' => $balanceAmount,
                    'UpdatedBy'     => $userUID,
                    'UpdatedOn'     => date('Y-m-d H:i:s'),
                ],
                ['ExpenseUID' => $expenseUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );
            if ($resp->Error) throw new Exception($resp->Message);

            $payTransYear  = (int)date('Y', strtotime($paymentDate));
            $payPrefixData = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID, 'Prefix.ModuleUID' => $this->pageModuleUID]);
            $payPrefix     = !empty($payPrefixData->Data) ? $payPrefixData->Data[0] : null;
            $payPrefixUID  = $payPrefix ? (int)$payPrefix->PrefixUID : null;
            $paymentNumber = $payPrefixUID ? (int)$this->transactions_model->getNextPaymentNumber($payPrefixUID, $orgUID, $payTransYear) : 0;
            $token         = $this->transactions_model->_generateReceiptToken();

            $pmtCount     = $this->expenses_model->getPaymentCount($expenseUID, 'Expense', $orgUID);
            $uniqueNumber = $pmtCount === 0 ? $existing->ExpenseNumber : $existing->ExpenseNumber . '-' . $pmtCount;

            $pmtResp = $this->dbwrite_model->insertData('Transaction', 'PaymentsTbl', [
                'OrgUID'           => $orgUID,
                'PaymentDate'      => $paymentDate,
                'PaymentModuleUID' => 111,
                'PrefixUID'        => $payPrefixUID,
                'PaymentNumber'    => $paymentNumber,
                'UniqueNumber'     => $uniqueNumber,
                'ReceiptToken'     => $token,
                'TransYear'        => $payTransYear,
                'TransUID'         => $expenseUID,
                'ModuleUID'        => $this->pageModuleUID,
                'SourceType'       => 'Expense',
                'PartyType'        => NULL,
                'PartyUID'         => NULL,
                'PaymentTypeUID'   => $paymentTypeUID,
                'Amount'           => $paymentAmount,
                'BankAccountUID'   => $bankAccountUID ?: NULL,
                'Notes'            => $notes,
                'PaymentSource'    => 'Create',
                'PaymentDirection' => 'Out',
                'IsFullyPaid'      => $isFullyPaid,
                'ExcessAmount'     => 0,
                'IsActive'         => 1,
                'IsDeleted'        => 0,
                'CreatedBy'        => $userUID,
                'UpdatedBy'        => $userUID,
            ]);
            if ($pmtResp->Error) throw new Exception('Payment record failed: ' . $pmtResp->Message);

            $ledgerBankUID = $bankAccountUID;
            if (!$ledgerBankUID) {
                $cashAcc = $this->expenses_model->getCashAccount($orgUID);
                $ledgerBankUID = $cashAcc ? (int)$cashAcc->BankAccountUID : null;
            }
            if ($ledgerBankUID) {
                $ledgerResp = $this->dbwrite_model->insertData('Transaction', 'AccountLedgerTbl', [
                    'OrgUID'         => $orgUID,
                    'BankAccountUID' => $ledgerBankUID,
                    'EntryDate'      => $paymentDate,
                    'EntryType'      => 'DR',
                    'Amount'         => $paymentAmount,
                    'SourceType'     => 'Expense',
                    'SourceUID'      => $expenseUID,
                    'ModuleUID'      => $this->pageModuleUID,
                    'ReferenceNo'    => $referenceNo,
                    'Narration'      => ($isFullyPaid ? 'Expense paid' : 'Expense partially paid') . ' — ' . $existing->ExpenseNumber,
                    'IsActive'       => 1,
                    'IsDeleted'      => 0,
                    'CreatedBy'      => $userUID,
                    'UpdatedBy'      => $userUID,
                    'CreatedOn'      => date('Y-m-d H:i:s'),
                    'UpdatedOn'      => date('Y-m-d H:i:s'),
                ]);
                if ($ledgerResp->Error) throw new Exception('Ledger entry failed: ' . $ledgerResp->Message);
            }

            $this->dbwrite_model->commitTransaction();
            $this->_savePaymentAttachments((int)$pmtResp->ID);
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = $isFullyPaid
                ? 'Expense marked as paid.'
                : 'Partial payment recorded. Balance remaining: ' . $balanceAmount . '.';

        } catch (Throwable $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        if (!$this->EndReturnData->Error) {
            $this->_appendListResponse($orgUID);
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Payment history popup ────────────────────────────────────────────────
    public function getPaymentHistory() {
        $this->EndReturnData = new stdClass();
        try {
            $expenseUID = (int)$this->input->post('TransUID');
            $orgUID     = $this->pageData['JwtData']->Org->OrgUID;
            if ($expenseUID <= 0) throw new Exception('Invalid expense.');

            $this->load->model('transactions_model');
            $payments = $this->transactions_model->getTransactionPayments($expenseUID, $orgUID);

            $list = [];
            foreach ($payments as $p) {
                $list[] = [
                    'Amount'          => (float)$p->Amount,
                    'PaymentTypeName' => $p->PaymentTypeName ?? '',
                    'CreatedOn'       => $p->CreatedOn       ?? '',
                    'ReferenceNo'     => $p->ReferenceNo     ?? '',
                ];
            }

            $this->EndReturnData->Error    = FALSE;
            $this->EndReturnData->Payments = $list;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Payment attachments ──────────────────────────────────────────────────
    public function getPaymentAttachments() {
        $this->EndReturnData = new stdClass();
        try {
            $expenseUID = (int)$this->input->post('TransUID');
            $orgUID     = $this->pageData['JwtData']->Org->OrgUID;
            if ($expenseUID <= 0) throw new Exception('Invalid expense.');

            $this->load->model('transactions_model');
            $payments    = $this->transactions_model->getTransactionPayments($expenseUID, $orgUID);
            $attachments = [];
            foreach ($payments as $payment) {
                $payAttachments = $this->transactions_model->getPaymentAttachments($payment->PaymentUID, $orgUID);
                foreach ($payAttachments as $attach) {
                    $attach->PaymentTypeName      = $payment->PaymentTypeName;
                    $attach->PaymentAmount        = $payment->Amount;
                    $attach->PaymentUniqueNumber  = $payment->UniqueNumber ?? null;
                    $attachments[]                = $attach;
                }
            }

            $this->EndReturnData->Error       = FALSE;
            $this->EndReturnData->Attachments = $attachments;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Update status (Pending → Paid / Cancelled, Paid → Cancelled) ─────────
    public function updateExpenseStatus() {
        $this->EndReturnData = new stdClass();
        try {
            $PostData   = $this->input->post();
            $expenseUID = (int)getPostValue($PostData, 'ExpenseUID');
            $newStatus  = trim(getPostValue($PostData, 'Status'));
            $userUID    = $this->pageData['JwtData']->User->UserUID;
            $orgUID     = $this->pageData['JwtData']->Org->OrgUID;

            if ($expenseUID <= 0)  throw new Exception('Invalid expense record.');
            if (empty($newStatus)) throw new Exception('Status is required.');

            $existing = $this->expenses_model->getExpenseById($expenseUID, $orgUID);
            if (!$existing) throw new Exception('Expense not found.');

            $allowed = [
                'Pending' => ['Paid', 'Cancelled'],
                'Paid'    => ['Cancelled'],
            ];

            if (!isset($allowed[$existing->DocStatus]) || !in_array($newStatus, $allowed[$existing->DocStatus])) {
                throw new Exception('Invalid status transition.');
            }

            $this->dbwrite_model->startTransaction();

            $updateData = [
                'DocStatus' => $newStatus,
                'IsPaid'    => ($newStatus === 'Paid') ? 1 : 0,
                'UpdatedBy' => $userUID,
                'UpdatedOn' => date('Y-m-d H:i:s'),
            ];

            $resp = $this->dbwrite_model->updateData(
                'Transaction', 'ExpensesTbl', $updateData,
                ['ExpenseUID' => $expenseUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );
            if ($resp->Error) throw new Exception($resp->Message);

            if ($newStatus === 'Paid') {
                // Payment details come from the "Mark as Paid" modal in the list
                $this->_insertExpensePayment(
                    $PostData, $orgUID, $userUID,
                    $expenseUID, $existing->ExpenseNumber,
                    $existing->NetAmount, $existing->ExpenseDate
                );
            } elseif ($newStatus === 'Cancelled' && !empty($existing->PaymentUID)) {
                // Void the linked payment record
                $this->dbwrite_model->updateData(
                    'Transaction', 'PaymentsTbl',
                    ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID, 'UpdatedOn' => date('Y-m-d H:i:s')],
                    ['PaymentUID' => (int)$existing->PaymentUID, 'OrgUID' => $orgUID]
                );
            }

            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Status updated to ' . $newStatus . '.';

        } catch (Throwable $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        if (!$this->EndReturnData->Error) {
            $this->_appendListResponse($orgUID);
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Get attachments for a single expense ────────────────────────────────
    public function getAttachments() {
        $this->EndReturnData = new stdClass();
        try {
            $expenseUID = (int)getPostValue($this->input->post(), 'TransUID');
            $orgUID     = $this->pageData['JwtData']->Org->OrgUID;
            if ($expenseUID <= 0) throw new Exception('Invalid expense.');
            $this->EndReturnData->Error       = FALSE;
            $this->EndReturnData->Attachments = $this->expenses_model->getExpenseAttachments($expenseUID, $orgUID);
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
            $orgUID     = $this->pageData['JwtData']->Org->OrgUID;

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
            $orgUID = $this->pageData['JwtData']->Org->OrgUID;
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
            $orgUID       = $this->pageData['JwtData']->Org->OrgUID;
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
            $this->_appendCategoryListResponse($orgUID);

        } catch (Throwable $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Category list (paginated, for manager modal) ─────────────────────────
    public function getCategoryList() {
        $this->EndReturnData = new stdClass();
        try {
            $orgUID = $this->pageData['JwtData']->Org->OrgUID;
            $pageNo = max(1, (int)($this->input->post('PageNo') ?: 1));
            $limit  = 30;
            $search = trim($this->input->post('Search') ?: '');
            $list   = $this->expenses_model->getCategoryList($orgUID, $search, $limit, ($pageNo - 1) * $limit);
            $total  = $this->expenses_model->getCategoryCount($orgUID, $search);

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->RecordHtmlData = $this->_buildCategoryListHtml($list);
            $this->EndReturnData->Pagination     = $this->globalservice->buildPagePaginationHtml('/expenses/getCategoryList', $total, $pageNo, $limit);
            $this->EndReturnData->TotalCount     = $total;
        } catch (Throwable $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Update category name ──────────────────────────────────────────────────
    public function updateCategory() {
        $this->EndReturnData = new stdClass();
        try {
            $PostData    = $this->input->post();
            $categoryUID = (int)getPostValue($PostData, 'CategoryUID');
            $orgUID      = $this->pageData['JwtData']->Org->OrgUID;
            $userUID     = $this->pageData['JwtData']->User->UserUID;
            $name        = trim(getPostValue($PostData, 'CategoryName'));

            if ($categoryUID <= 0) throw new Exception('Invalid category.');
            if (empty($name))      throw new Exception('Category name is required.');

            $resp = $this->dbwrite_model->updateData(
                'Transaction', 'ExpenseCategoryTbl',
                ['CategoryName' => $name, 'UpdatedBy' => $userUID, 'UpdatedOn' => date('Y-m-d H:i:s')],
                ['CategoryUID' => $categoryUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );
            if ($resp->Error) throw new Exception($resp->Message);

            $this->EndReturnData->Error        = FALSE;
            $this->EndReturnData->Message      = 'Category updated.';
            $this->EndReturnData->CategoryUID  = $categoryUID;
            $this->EndReturnData->CategoryName = $name;
            $this->_appendCategoryListResponse($orgUID);
        } catch (Throwable $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Delete category ───────────────────────────────────────────────────────
    public function deleteCategory() {
        $this->EndReturnData = new stdClass();
        try {
            $PostData    = $this->input->post();
            $categoryUID = (int)getPostValue($PostData, 'CategoryUID');
            $orgUID      = $this->pageData['JwtData']->Org->OrgUID;
            $userUID     = $this->pageData['JwtData']->User->UserUID;

            if ($categoryUID <= 0) throw new Exception('Invalid category.');

            if ($this->expenses_model->isCategoryLinked($categoryUID, $orgUID)) {
                throw new Exception('This category is linked to one or more expenses and cannot be deleted. Please reassign those expenses first.');
            }

            $resp = $this->dbwrite_model->updateData(
                'Transaction', 'ExpenseCategoryTbl',
                ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID, 'UpdatedOn' => date('Y-m-d H:i:s')],
                ['CategoryUID' => $categoryUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );
            if ($resp->Error) throw new Exception($resp->Message);

            $this->EndReturnData->Error       = FALSE;
            $this->EndReturnData->Message     = 'Category deleted.';
            $this->EndReturnData->CategoryUID = $categoryUID;
            $this->_appendCategoryListResponse($orgUID);
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
        $GeneralSettings = $this->pageData['JwtData']->GenSettings ?? new stdClass();

        $filterRaw = $this->input->post('Filter');
        $filter = is_array($filterRaw) ? $filterRaw : (($filterRaw && ($decoded = json_decode($filterRaw, true))) ? $decoded : ['Status' => 'All']);
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

    // Rebuilds category list HTML and appends to EndReturnData
    private function _appendCategoryListResponse($orgUID) {
        $pageNo = max(1, (int)($this->input->post('PageNo') ?: 1));
        $limit  = 30;
        $search = trim($this->input->post('Search') ?: '');
        $list   = $this->expenses_model->getCategoryList($orgUID, $search, $limit, ($pageNo - 1) * $limit);
        $total  = $this->expenses_model->getCategoryCount($orgUID, $search);

        if (empty($list) && $pageNo > 1) {
            $pageNo--;
            $list = $this->expenses_model->getCategoryList($orgUID, $search, $limit, ($pageNo - 1) * $limit);
        }

        $this->EndReturnData->CatRecordHtmlData = $this->_buildCategoryListHtml($list);
        $this->EndReturnData->CatPagination     = $this->globalservice->buildPagePaginationHtml('/expenses/getCategoryList', $total, $pageNo, $limit);
        $this->EndReturnData->CatTotalCount     = $total;
    }

    // Renders category rows as list-group HTML
    private function _buildCategoryListHtml($list) {
        if (empty($list)) {
            return '<div class="text-center py-5 text-muted" style="font-size:.88rem;">No categories found.</div>';
        }
        $html = '';
        foreach ($list as $cat) {
            $isSystem = is_null($cat->OrgUID) || $cat->OrgUID === '';
            $eName    = htmlspecialchars($cat->CategoryName);
            $uid      = (int)$cat->CategoryUID;
            $html .= '<li class="list-group-item d-flex align-items-center justify-content-between px-3 py-2">';
            $html .= '<div class="d-flex align-items-center gap-2">';
            $html .= '<span class="fw-medium" style="font-size:.88rem;">' . $eName . '</span>';
            if ($isSystem || (int)$cat->IsDefault) {
                $html .= '<span class="badge bg-label-secondary" style="font-size:.65rem;">System</span>';
            }
            $html .= '</div>';
            $html .= '<div class="d-flex align-items-center gap-1">';
            if (!$isSystem) {
                $html .= '<button class="btn btn-icon btn-sm text-primary catEditBtn" data-uid="' . $uid . '" data-name="' . $eName . '" title="Edit"><i class="bx bx-edit" style="font-size:1rem;"></i></button>';
                $html .= '<button class="btn btn-icon btn-sm text-danger catDeleteBtn" data-uid="' . $uid . '" data-name="' . $eName . '" title="Delete"><i class="bx bx-trash" style="font-size:1rem;"></i></button>';
            } else {
                $html .= '<span class="text-muted px-2" title="System category — cannot be modified"><i class="bx bx-lock-alt" style="font-size:.85rem;"></i></span>';
            }
            $html .= '</div>';
            $html .= '</li>';
        }
        return $html;
    }

    // Validates POST, computes amounts, returns data array for ExpensesTbl only
    private function _buildExpenseData($PostData, $userUID, $orgUID, $isCreate) {
        $amount      = (float) getPostValue($PostData, 'Amount');
        $isPaid      = (int)   getPostValue($PostData, 'IsPaid') === 1 ? 1 : 0;
        $categoryUID = (int)   getPostValue($PostData, 'CategoryUID') ?: NULL;
        $expenseDate =         getPostValue($PostData, 'ExpenseDate') ?: date('Y-m-d');
        $notes       =         getPostValue($PostData, 'Notes') ?: NULL;

        if ($amount <= 0)        throw new Exception('Expense amount must be greater than 0.');
        if (empty($expenseDate)) throw new Exception('Expense date is required.');
        if ($isPaid && !(int)getPostValue($PostData, 'PaymentTypeUID')) {
            throw new Exception('Please select a payment type.');
        }

        $data = [
            'OrgUID'        => $orgUID,
            'ModuleUID'     => $this->pageModuleUID,
            'ExpenseDate'   => $expenseDate,
            'Amount'        => $amount,
            'TaxApplicable' => 0,
            'TaxPercentage' => 0,
            'TaxAmount'     => 0,
            'TDSApplicable' => 0,
            'TDSPercentage' => 0,
            'TDSAmount'     => 0,
            'NetAmount'     => $amount,
            'CategoryUID'   => $categoryUID,
            'Notes'         => $notes,
            'DocStatus'     => $isPaid ? 'Paid' : 'Pending',
            'IsPaid'        => $isPaid,
            'IsActive'      => 1,
            'IsDeleted'     => 0,
            'UpdatedBy'     => $userUID,
            'UpdatedOn'     => date('Y-m-d H:i:s'),
        ];

        if ($isCreate) {
            $data['CreatedBy'] = $userUID;
            $data['CreatedOn'] = date('Y-m-d H:i:s');
        }

        return $data;
    }

    // Uploads files from $_FILES['Attachments'] and saves rows to ExpenseIncomeAttachmentsTbl
    private function _saveAttachments($sourceUID, $sourceType) {
        $files = $_FILES['Attachments'] ?? null;
        if (empty($files) || empty($files['name'][0])) return;
        $userUID = $this->pageData['JwtData']->User->UserUID;
        $orgUID  = $this->pageData['JwtData']->Org->OrgUID;
        $this->load->library('fileupload');
        $folder = ($sourceType === 'Expense') ? 'expenses' : 'indirectincome';
        $count  = count($files['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK || empty($files['name'][$i])) continue;
            $origName    = basename($files['name'][$i]);
            $safeName    = time() . '_' . $i . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $origName);
            $storagePath = $folder . '/' . $sourceUID . '/' . $safeName;
            $uploadResult = $this->fileupload->fileUpload('file', $storagePath, $files['tmp_name'][$i]);
            if ($uploadResult->Error) continue;
            $this->dbwrite_model->insertData('Transaction', 'ExpenseIncomeAttachmentsTbl', [
                'OrgUID'     => $orgUID,
                'SourceUID'  => $sourceUID,
                'SourceType' => $sourceType,
                'FileName'   => $origName,
                'FilePath'   => '/' . ltrim($uploadResult->Path, '/'),
                'FileType'   => $files['type'][$i],
                'FileSize'   => $files['size'][$i],
                'SortOrder'  => $i,
                'IsActive'   => 1,
                'IsDeleted'  => 0,
                'CreatedBy'  => $userUID,
            ]);
        }
    }

    // Saves files from $_FILES['PaymentFiles'] to Transaction.PaymentAttachmentsTbl
    private function _savePaymentAttachments($paymentUID) {
        $files = $_FILES['PaymentFiles'] ?? null;
        if (empty($files) || empty($files['name'][0])) return;
        $userUID = $this->pageData['JwtData']->User->UserUID;
        $orgUID  = $this->pageData['JwtData']->Org->OrgUID;
        $this->load->library('fileupload');
        $allowed = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
        $count   = min(count($files['name']), 3);
        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK || empty($files['name'][$i])) continue;
            if ($files['size'][$i] > 3 * 1024 * 1024) continue;
            if (!in_array($files['type'][$i], $allowed)) continue;
            $origName    = basename($files['name'][$i]);
            $safeName    = time() . '_' . $i . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $origName);
            $storagePath = 'payments/' . $paymentUID . '/' . $safeName;
            $uploadResult = $this->fileupload->fileUpload('file', $storagePath, $files['tmp_name'][$i]);
            if ($uploadResult->Error) continue;
            $this->dbwrite_model->insertData('Transaction', 'PaymentAttachmentsTbl', [
                'OrgUID'     => $orgUID,
                'PaymentUID' => $paymentUID,
                'FileName'   => $origName,
                'FilePath'   => '/' . ltrim($uploadResult->Path, '/'),
                'FileType'   => $files['type'][$i],
                'FileSize'   => $files['size'][$i],
                'SortOrder'  => $i,
                'IsActive'   => 1,
                'IsDeleted'  => 0,
                'CreatedBy'  => $userUID,
            ]);
        }
    }

    // Inserts a PaymentsTbl record + AccountLedgerTbl debit for a paid expense
    private function _insertExpensePayment($PostData, $orgUID, $userUID, $expenseUID, $expenseNumber, $netAmount, $fallbackDate) {
        $paymentTypeUID = (int)getPostValue($PostData, 'PaymentTypeUID') ?: NULL;
        $bankAccountUID = (int)getPostValue($PostData, 'BankAccountUID') ?: NULL;
        $paymentDate    = getPostValue($PostData, 'PaymentDate') ?: $fallbackDate;
        $paymentNotes   = getPostValue($PostData, 'PaymentNotes') ?: NULL;

        if (!$paymentTypeUID) throw new Exception('Please select a payment type.');

        $payTransYear  = (int)date('Y', strtotime($paymentDate));
        $payPrefixData = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID, 'Prefix.ModuleUID' => $this->pageModuleUID]);
        $payPrefix     = !empty($payPrefixData->Data) ? $payPrefixData->Data[0] : null;
        $payPrefixUID  = $payPrefix ? (int)$payPrefix->PrefixUID : null;
        $paymentNumber = $payPrefixUID ? (int)$this->transactions_model->getNextPaymentNumber($payPrefixUID, $orgUID, $payTransYear) : 0;
        $token         = $this->transactions_model->_generateReceiptToken();

        $pmtCount     = $this->expenses_model->getPaymentCount($expenseUID, 'Expense', $orgUID);
        $uniqueNumber = $pmtCount === 0 ? $expenseNumber : $expenseNumber . '-' . $pmtCount;

        $pmtResp = $this->dbwrite_model->insertData('Transaction', 'PaymentsTbl', [
            'OrgUID'           => $orgUID,
            'PaymentDate'      => $paymentDate,
            'PaymentModuleUID' => 111,
            'PrefixUID'        => $payPrefixUID,
            'PaymentNumber'    => $paymentNumber,
            'UniqueNumber'     => $uniqueNumber,
            'ReceiptToken'     => $token,
            'TransYear'        => $payTransYear,
            'TransUID'         => $expenseUID,
            'ModuleUID'        => $this->pageModuleUID,
            'SourceType'       => 'Expense',
            'PartyType'        => NULL,
            'PartyUID'         => NULL,
            'PaymentTypeUID'   => $paymentTypeUID,
            'Amount'           => $netAmount,
            'BankAccountUID'   => $bankAccountUID ?: NULL,
            'Notes'            => $paymentNotes,
            'PaymentSource'    => 'Create',
            'PaymentDirection' => 'Out',
            'IsFullyPaid'      => 1,
            'ExcessAmount'     => 0,
            'IsActive'         => 1,
            'IsDeleted'        => 0,
            'CreatedBy'        => $userUID,
            'UpdatedBy'        => $userUID,
        ]);
        if ($pmtResp->Error) throw new Exception('Payment record failed: ' . $pmtResp->Message);

        // Ledger debit entry
        $ledgerBankUID = $bankAccountUID;
        if (!$ledgerBankUID) {
            $cashAcc = $this->expenses_model->getCashAccount($orgUID);
            $ledgerBankUID = $cashAcc ? (int)$cashAcc->BankAccountUID : null;
        }
        if ($ledgerBankUID) {
            $ledgerResp = $this->dbwrite_model->insertData('Transaction', 'AccountLedgerTbl', [
                'OrgUID'         => $orgUID,
                'BankAccountUID' => $ledgerBankUID,
                'EntryDate'      => $paymentDate,
                'EntryType'      => 'DR',
                'Amount'         => $netAmount,
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
}
