<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Expenses extends MY_Controller {

    public  $pageData      = [];
    private $EndReturnData;
    protected $pageModuleUID = 114;

    public function __construct() {
        parent::__construct();
        $this->load->helper('transaction');
        $this->load->model('expenses_model');
        $this->load->model('dbwrite_model');
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
        } catch (Throwable $e) {
            log_message('error', 'Expenses::createForm — ' . $e->getMessage());
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
        } catch (Throwable $e) {
            log_message('error', 'Expenses::editForm — ' . $e->getMessage());
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
            $GeneralSettings = $this->pageData['JwtData']->GenSettings ?? new stdClass();
            $limit  = $GeneralSettings->RowLimit ?? 10;
            $orgUID = $this->pageData['JwtData']->Org->OrgUID;

            $datePref = $this->getDateFilterPreference('expenses');
            $this->pageData['SavedDateRange'] = $datePref['range'];
            $this->pageData['SavedDateLabel'] = $datePref['label'];

            $filter = ['Status' => 'All', 'DateFrom' => $datePref['from'], 'DateTo' => $datePref['to']];

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
            $orgUsers = $this->_requireCache($this->redisservice->orgKey('org-users'));
            if (!$orgUsers) return;
            $this->pageData['OrgUsers']      = $orgUsers;
            $this->pageData['ShowUserFilter'] = count($orgUsers) > 1;

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

            if ((int)$this->input->post('ShowStats') === 1) {
                $this->EndReturnData->SummaryStats = $this->expenses_model->getExpenseSummaryStats($orgUID);
            }

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

            $pmtData = null;
            if ($data['IsPaid']) {
                $pmtData = $this->_insertExpensePayment($PostData, $orgUID, $userUID, $expenseUID, $expenseNumber, (float)$data['NetAmount'], (string)$data['ExpenseDate']);
            }

            $this->_saveExpenseItems($PostData, $expenseUID, $orgUID, $userUID);

            $this->dbwrite_model->commitTransaction();

            try {
                $this->load->library('accountledger');
                $expFY = (int) date('Y', strtotime($data['ExpenseDate']));
                $this->accountledger->postExpenseJournal(
                    $expenseUID, $data['ExpenseDate'], $expenseNumber, $expFY,
                    (float) $data['NetAmount'], $userUID,
                    (int)   ($data['VendorUID']  ?? 0),
                    (float) ($data['Amount']     ?? 0),
                    (float) ($data['TaxAmount']  ?? 0),
                    (float) ($data['CGSTTotal']  ?? 0),
                    (float) ($data['SGSTTotal']  ?? 0),
                    (float) ($data['IGSTTotal']  ?? 0)
                );
                if ($pmtData !== null && (int)($data['VendorUID'] ?? 0) > 0) {
                    $this->accountledger->postPaymentJournal(
                        'made', $pmtData['pmtUID'], $pmtData['paymentDate'], $pmtData['payTransYear'],
                        (float)$data['NetAmount'], (int)$data['VendorUID'], 'Vendor', $userUID
                    );
                }
            } catch (Exception $ledgerEx) {
                log_message('error', 'Ledger update failed after expense creation: ' . $ledgerEx->getMessage());
            }

            $vendorUID = (int)($data['VendorUID'] ?? 0);
            if ($vendorUID > 0) {
                try {
                    $this->load->library('cachehelper');
                    $this->cachehelper->upsertVendor($vendorUID);
                } catch (Exception $cacheEx) {
                    log_message('error', 'Vendor cache sync failed after expense creation: ' . $cacheEx->getMessage());
                }
            }

            $this->_saveAttachments($expenseUID, 'Expense');

            $this->EndReturnData->Error         = FALSE;
            $this->EndReturnData->Message       = 'Expense recorded successfully.';
            $this->EndReturnData->RedirectURL   = '/expenses';
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'ADD_EXPENSE', 'Expense', (int) $expenseUID, (string) $expenseNumber,
                [], 'Created expense ' . $expenseNumber, 'Expenses', 'TRANSACTION', 'SUCCESS', '', 'WEB', [], [], $PostData
            );
            $this->EndReturnData->ExpenseUID    = $expenseUID;
            $this->EndReturnData->ExpenseNumber = $expenseNumber;

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
            $orgUID     = $this->pageData['JwtData']->Org->OrgUID;

            if ($expenseUID <= 0) throw new Exception('Invalid expense record.');

            $existing = $this->expenses_model->getExpenseById($expenseUID, $orgUID);
            if (!$existing) throw new Exception('Expense not found.');
            if ($existing->DocStatus === 'Cancelled') throw new Exception('This expense cannot be edited.');

            log_message('debug', '[EXP-BAL] updateExpense START → expenseUID=' . $expenseUID
                . ' DocStatus=' . $existing->DocStatus
                . ' NetAmount=' . $existing->NetAmount
                . ' PaidAmount=' . ($existing->PaidAmount ?? 0)
                . ' BalanceAmount=' . ($existing->BalanceAmount ?? 0)
                . ' VendorUID=' . ($existing->VendorUID ?? 'NULL'));

            $data = $this->_buildExpenseData($PostData, $userUID, $orgUID, false);
            unset($data['CreatedBy'], $data['CreatedOn'], $data['OrgUID'], $data['ModuleUID']);

            // Recalculate payment status: PaidAmount in DB is what was actually paid,
            // the new NetAmount is the updated expense total — derive DocStatus from their difference.
            $dec        = $this->_decimals();
            $paidAmount = round((float)($existing->PaidAmount ?? 0), $dec);
            $newNetAmt  = (float)$data['NetAmount'];

            // Server-side guard: new amount cannot drop below what was already paid
            if ($paidAmount > 0 && $newNetAmt < $paidAmount - 0.001) {
                throw new Exception(
                    'Amount cannot be less than ' . $this->_currency() . ' ' .
                    number_format($paidAmount, $dec) . ' (already paid).'
                );
            }

            if ($paidAmount <= 0) {
                $data['DocStatus']     = 'Pending';
                $data['IsPaid']        = 0;
                $data['PaidAmount']    = 0;
                $data['BalanceAmount'] = $newNetAmt;
            } elseif (round($newNetAmt - $paidAmount, $dec) <= 0) {
                $data['DocStatus']     = 'Paid';
                $data['IsPaid']        = 1;
                $data['PaidAmount']    = $paidAmount;
                $data['BalanceAmount'] = 0;
            } else {
                $data['DocStatus']     = 'Partial';
                $data['IsPaid']        = 0;
                $data['PaidAmount']    = $paidAmount;
                $data['BalanceAmount'] = round($newNetAmt - $paidAmount, $dec);
            }

            $this->dbwrite_model->startTransaction();

            $resp = $this->dbwrite_model->updateData(
                'Transaction', 'ExpensesTbl', $data,
                ['ExpenseUID' => $expenseUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );
            if ($resp->Error) throw new Exception($resp->Message);

            $this->_saveExpenseItems($PostData, $expenseUID, $orgUID, $userUID);

            $this->dbwrite_model->commitTransaction();

            $this->_softDeleteAttachments(getPostValue($PostData, 'RemovedAttachIDs'), 'Expense');
            $this->_saveAttachments($expenseUID, 'Expense');

            // Reverse old journal and re-post with new amount/date (non-fatal)
            log_message('debug', '[EXP-BAL] updateExpense → new NetAmount=' . $data['NetAmount'] . ' BalanceAmount=' . ($data['BalanceAmount'] ?? 'n/a') . ' PaidAmount=' . ($data['PaidAmount'] ?? 'n/a') . ' VendorUID=' . ($data['VendorUID'] ?? 'NULL'));
            try {
                $this->load->library('accountledger');
                $this->accountledger->reverseJournal('Expense', $expenseUID, $userUID);
                $expFY = (int)date('Y', strtotime($data['ExpenseDate']));
                $this->accountledger->postExpenseJournal(
                    $expenseUID, $data['ExpenseDate'], $existing->ExpenseNumber, $expFY,
                    (float) $data['NetAmount'], $userUID,
                    (int)   ($data['VendorUID']  ?? 0),
                    (float) ($data['Amount']     ?? 0),
                    (float) ($data['TaxAmount']  ?? 0),
                    (float) ($data['CGSTTotal']  ?? 0),
                    (float) ($data['SGSTTotal']  ?? 0),
                    (float) ($data['IGSTTotal']  ?? 0)
                );
                // Back-fill any payment journals that predate the postPaymentJournal wiring,
                // so the vendor running balance reflects BalanceAmount, not NetAmount.
                $journalVendorUID = (int)($data['VendorUID'] ?? 0);
                if ($journalVendorUID > 0) {
                    $this->accountledger->ensurePaymentJournals($expenseUID, $journalVendorUID, $userUID);
                }
            } catch (Exception $ledgerEx) {
                log_message('error', 'Ledger update failed after expense edit #' . $expenseUID . ': ' . $ledgerEx->getMessage());
            }

            $vendorUID = (int)($data['VendorUID'] ?? 0);
            if ($vendorUID > 0) {
                try {
                    $this->load->library('cachehelper');
                    $this->cachehelper->upsertVendor($vendorUID);
                } catch (Exception $cacheEx) {
                    log_message('error', 'Vendor cache sync failed after expense update: ' . $cacheEx->getMessage());
                }
            }

            $this->EndReturnData->Error       = FALSE;
            $this->EndReturnData->Message     = 'Expense updated successfully.';
            $this->EndReturnData->RedirectURL = '/expenses';
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'UPDATE_EXPENSE', 'Expense', (int) $expenseUID, (string) ($existing->ExpenseNumber ?? ''),
                [], 'Updated expense ' . ($existing->ExpenseNumber ?? ''), 'Expenses', 'TRANSACTION', 'SUCCESS', '', 'WEB', [], [], $PostData
            );

        } catch (Throwable $e) {
            $this->dbwrite_model->rollbackTransaction();
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

            // Reverse journal entry (non-fatal)
            try {
                $this->load->library('accountledger');
                $this->accountledger->reverseJournal('Expense', $expenseUID, $userUID);
            } catch (Exception $ledgerEx) {
                log_message('error', 'Ledger reverse failed after expense delete #' . $expenseUID . ': ' . $ledgerEx->getMessage());
            }

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Expense deleted.';
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'DELETE_EXPENSE', 'Expense', (int) $expenseUID, (string) ($existing->ExpenseNumber ?? ''),
                [], 'Deleted expense ' . ($existing->ExpenseNumber ?? ''), 'Expenses', 'TRANSACTION'
            );

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
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'DUPLICATE_EXPENSE', 'Expense', (int) $newUID, (string) $newNumber,
                [], 'Duplicated expense ' . $newNumber, 'Expenses', 'TRANSACTION'
            );

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
            $paymentAmount  = round((float)getPostValue($PostData, 'Amount'), $this->_decimals());

            if (!$paymentTypeUID) throw new Exception('Please select a payment type.');
            if ($paymentAmount <= 0) throw new Exception('Payment amount must be greater than 0.');

            $netAmount     = round((float)$existing->NetAmount, $this->_decimals());
            $existingPaid  = round((float)($existing->PaidAmount ?? 0), $this->_decimals());
            $newPaidAmount = round($existingPaid + $paymentAmount, $this->_decimals());

            if ($newPaidAmount > $netAmount + 0.01) {
                throw new Exception('Total payments (' . $newPaidAmount . ') cannot exceed the expense amount (' . $netAmount . ').');
            }

            $newPaidAmount  = min($newPaidAmount, $netAmount);
            $balanceAmount  = max(0, round($netAmount - $newPaidAmount, $this->_decimals()));
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

            // Sync vendor ledger journals and Upstash cache.
            // ensurePaymentJournals back-fills any historical payments that predate
            // the journaling fix, then journals the current payment — so the vendor
            // balance always equals the true outstanding BalanceAmount.
            $vendorUID = (int)($existing->VendorUID ?? 0);
            if ($vendorUID > 0) {
                try {
                    $this->load->library('accountledger');
                    $this->accountledger->ensurePaymentJournals($expenseUID, $vendorUID, $userUID);
                } catch (Throwable $jEx) {
                    log_message('error', 'Expense payment journal failed: ' . $jEx->getMessage());
                }
                try {
                    $this->load->library('cachehelper');
                    $this->cachehelper->upsertVendor($vendorUID);
                } catch (Throwable $cEx) {
                    log_message('error', 'Vendor cache sync failed after expense payment: ' . $cEx->getMessage());
                }
            }

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = $isFullyPaid
                ? 'Expense marked as paid.'
                : 'Partial payment recorded. Balance remaining: ' . $balanceAmount . '.';
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'RECORD_EXPENSE_PAYMENT', 'Expense', (int) $expenseUID, (string) ($existing->ExpenseNumber ?? ''),
                ['Amount' => $paymentAmount], 'Recorded payment for expense ' . ($existing->ExpenseNumber ?? ''), 'Expenses', 'PAYMENT'
            );

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

            $pmtData = null;
            if ($newStatus === 'Paid') {
                // Payment details come from the "Mark as Paid" modal in the list
                $pmtData = $this->_insertExpensePayment(
                    $PostData, $orgUID, $userUID,
                    $expenseUID, $existing->ExpenseNumber,
                    (float)$existing->NetAmount, (string)$existing->ExpenseDate
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

            // Journal handling for status transitions (non-fatal)
            try {
                $this->load->library('accountledger');
                if ($newStatus === 'Cancelled') {
                    $this->accountledger->reverseJournal('Expense', $expenseUID, $userUID);
                } elseif ($newStatus === 'Paid' && $pmtData !== null && (int)($existing->VendorUID ?? 0) > 0) {
                    $this->accountledger->postPaymentJournal(
                        'made', $pmtData['pmtUID'], $pmtData['paymentDate'], $pmtData['payTransYear'],
                        (float)$existing->NetAmount, (int)$existing->VendorUID, 'Vendor', $userUID
                    );
                }
            } catch (Exception $ledgerEx) {
                log_message('error', 'Ledger failed on expense status change #' . $expenseUID . ': ' . $ledgerEx->getMessage());
            }

            $vendorUID = (int)($existing->VendorUID ?? 0);
            if ($vendorUID > 0) {
                try {
                    $this->load->library('cachehelper');
                    $this->cachehelper->upsertVendor($vendorUID);
                } catch (Exception $cacheEx) {
                    log_message('error', 'Vendor cache sync failed after expense status change: ' . $cacheEx->getMessage());
                }
            }

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Status updated to ' . $newStatus . '.';
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'UPDATE_EXPENSE_STATUS', 'Expense', (int) $expenseUID, (string) ($existing->ExpenseNumber ?? ''),
                ['NewStatus' => $newStatus], 'Updated expense status ' . ($existing->ExpenseNumber ?? ''), 'Expenses', 'TRANSACTION'
            );

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
            $this->EndReturnData->Items = $this->expenses_model->getExpenseItems($expenseUID, $orgUID);

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

    // ── TDS Sections dropdown data ────────────────────────────────────────────
    public function getTdsSections(): void {
        $this->EndReturnData = new stdClass();
        try {
            $sections = $this->expenses_model->getTdsSections();
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $sections;
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
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'CREATE_EXPENSE_CATEGORY', 'ExpenseCategory', (int) $resp->ID, (string) $categoryName,
                [], 'Created expense category ' . $categoryName, 'Expenses', 'MASTER'
            );
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
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'UPDATE_EXPENSE_CATEGORY', 'ExpenseCategory', (int) $categoryUID, (string) $name,
                [], 'Updated expense category ' . $name, 'Expenses', 'MASTER'
            );
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
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'DELETE_EXPENSE_CATEGORY', 'ExpenseCategory', (int) $categoryUID, '',
                [], 'Deleted expense category #' . $categoryUID, 'Expenses', 'MASTER'
            );
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

    // Builds refreshed list HTML + pagination + optional stats and appends to EndReturnData
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

        if ((int)$this->input->post('ShowStats') === 1) {
            $this->EndReturnData->SummaryStats = $this->expenses_model->getExpenseSummaryStats($orgUID);
        }
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
            $isSystem = is_null($cat->OrgUID) || $cat->OrgUID === '' || (int)$cat->IsDefault;
            $eName    = htmlspecialchars($cat->CategoryName);
            $uid      = (int)$cat->CategoryUID;
            $html .= '<li class="list-group-item px-0 py-0">';
            $html .= '<div class="d-flex align-items-center justify-content-between px-3 py-2">';
            $html .= '<div class="d-flex align-items-center gap-2">';
            $html .= '<span class="fw-medium" style="font-size:.88rem;">' . $eName . '</span>';
            if ($isSystem) {
                $html .= '<span class="badge bg-label-secondary" style="font-size:.65rem;">System</span>';
            }
            $html .= '</div>';
            $html .= '<div class="d-flex align-items-center gap-1">';
            if (!$isSystem) {
                $html .= '<button class="btn btn-icon btn-sm text-primary catEditBtn" data-uid="' . $uid . '" data-name="' . $eName . '" title="Edit"><i class="bx bx-edit" style="font-size:1rem;"></i></button>';
                $html .= '<button class="btn btn-icon btn-sm text-danger catDeleteBtn" data-uid="' . $uid . '" data-name="' . $eName . '" title="Delete"><i class="bx bx-trash" style="font-size:1rem;"></i></button>';
            }
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</li>';
        }
        return $html;
    }

    // Validates POST, computes amounts from items, returns data array for ExpensesTbl
    private function _buildExpenseData(array $PostData, int $userUID, int $orgUID, bool $isCreate): array {
        $dec         = $this->_decimals();
        $isPaid      = (int)getPostValue($PostData, 'IsPaid') === 1 ? 1 : 0;
        $categoryUID = (int)getPostValue($PostData, 'CategoryUID') ?: null;
        $expenseDate =      getPostValue($PostData, 'ExpenseDate') ?: date('Y-m-d');
        $notes       =      getPostValue($PostData, 'Notes') ?: null;
        $taxApplicable = (int)getPostValue($PostData, 'TaxApplicable') ? 1 : 0;
        $vendorUID   = (int)getPostValue($PostData, 'VendorUID') ?: null;
        if ($taxApplicable && !$vendorUID) throw new Exception('Please select a vendor.');
        $supplierDate=      getPostValue($PostData, 'SupplierInvoiceDate') ?: null;
        $supplierRef =      getPostValue($PostData, 'SupplierInvoiceSerialNo') ?: null;
        $amountType  =      getPostValue($PostData, 'AmountType') ?: 'TotalAmount';
        if ($amountType === 'TaxableAmount') $amountType = 'NetAmount'; // normalise legacy value
        if (!in_array($amountType, ['NetAmount', 'TotalAmount'])) $amountType = 'TotalAmount';

        if (empty($expenseDate)) throw new Exception('Expense date is required.');
        if ($isCreate && $isPaid && !(int)getPostValue($PostData, 'PaymentTypeUID')) {
            throw new Exception('Please select a payment type.');
        }

        // ── Tax mode branch ───────────────────────────────────────────────────
        if (!$taxApplicable) $amountType = null;
        $simpleAmount  = 0.0;

        if (!$taxApplicable) {
            $simpleAmount = round((float)getPostValue($PostData, 'SimpleAmount'), $dec);
            if ($simpleAmount <= 0) throw new Exception('Expense amount must be greater than 0.');
            $taxableSum = $simpleAmount;
            $taxAmtSum  = 0.0;
            $cgstSum    = 0.0;
            $sgstSum    = 0.0;
            $igstSum    = 0.0;
            $totalSum   = $simpleAmount;
        } else {
            $itemsJson = getPostValue($PostData, 'Items');
            $rawItems  = $itemsJson ? json_decode($itemsJson, true) : [];
            if (!is_array($rawItems) || empty($rawItems)) {
                throw new Exception('At least one expense item is required.');
            }
            $taxableSum = 0.0;
            $taxAmtSum  = 0.0;
            $cgstSum    = 0.0;
            $sgstSum    = 0.0;
            $igstSum    = 0.0;
            $totalSum   = 0.0;
            foreach ($rawItems as $row) {
                $taxableSum += (float)($row['Amount']     ?? 0);
                $taxAmtSum  += (float)($row['TaxAmount']  ?? 0);
                $cgstSum    += (float)($row['CGSTAmount'] ?? 0);
                $sgstSum    += (float)($row['SGSTAmount'] ?? 0);
                $igstSum    += (float)($row['IGSTAmount'] ?? 0);
                $totalSum   += (float)($row['TotalAmount'] ?? 0);
            }
            $taxableSum = round($taxableSum, $dec);
            $taxAmtSum  = round($taxAmtSum,  $dec);
            $cgstSum    = round($cgstSum,    $dec);
            $sgstSum    = round($sgstSum,    $dec);
            $igstSum    = round($igstSum,    $dec);
            $totalSum   = round($totalSum,   $dec);
            if ($taxableSum <= 0 && $totalSum <= 0) throw new Exception('Expense amount must be greater than 0.');
        }
        $taxPct        = $taxApplicable ? round((float)getPostValue($PostData, 'TaxPercentage'), 2) : 0.0;

        // ── TDS ───────────────────────────────────────────────────────────────
        $tdsApplicable  = (int)getPostValue($PostData, 'TDSApplicable') ? 1 : 0;
        $tdsSectionUID  = $tdsApplicable ? max(0, (int)getPostValue($PostData, 'TdsSectionUID')) : 0;
        if ($tdsApplicable && $tdsSectionUID <= 0) {
            throw new Exception('Please select a TDS section.');
        }
        $tdsPct         = $tdsApplicable ? round((float)getPostValue($PostData, 'TDSPercentage'), 2) : 0.0;
        $tdsAmt         = $tdsApplicable ? round($totalSum * $tdsPct / 100, $dec) : 0.0;

        // ── RCM ───────────────────────────────────────────────────────────────
        $rcmApplicable = (int)getPostValue($PostData, 'RCMApplicable') ? 1 : 0;
        $rcmAmount     = $rcmApplicable ? round((float)getPostValue($PostData, 'RCMAmount'), $dec) : 0.0;

        // ── Round Off ─────────────────────────────────────────────────────────
        $roundOff = round((float)getPostValue($PostData, 'RoundOff'), $dec);

        // ── Final amounts ─────────────────────────────────────────────────────
        $netAmount = round($totalSum + $roundOff - $tdsAmt, $dec);

        $data = [
            'OrgUID'                  => $orgUID,
            'ModuleUID'               => $this->pageModuleUID,
            'ExpenseDate'             => $expenseDate,
            'Amount'                  => $taxableSum,
            'TaxApplicable'           => $taxApplicable,
            'TaxPercentage'           => $taxPct,
            'TaxAmount'               => $taxAmtSum,
            'CGSTTotal'               => $cgstSum ?? 0.0,
            'SGSTTotal'               => $sgstSum ?? 0.0,
            'IGSTTotal'               => $igstSum ?? 0.0,
            'TDSApplicable'           => $tdsApplicable,
            'TdsSectionUID'           => $tdsSectionUID,
            'TDSPercentage'           => $tdsPct,
            'TDSAmount'               => $tdsAmt,
            'RCMApplicable'           => $rcmApplicable,
            'RCMAmount'               => $rcmAmount,
            'RoundOff'                => $roundOff,
            'NetAmount'               => $netAmount,
            'VendorUID'               => $vendorUID,
            'SupplierInvoiceDate'     => $supplierDate ?: null,
            'SupplierInvoiceSerialNo' => $supplierRef ?: null,
            'AmountType'              => $amountType,
            'CategoryUID'             => $categoryUID,
            'Notes'                   => $notes,
            'DocStatus'               => $isPaid ? 'Paid' : 'Pending',
            'IsPaid'                  => $isPaid,
            'IsActive'                => 1,
            'IsDeleted'               => 0,
            'UpdatedBy'               => $userUID,
            'UpdatedOn'               => date('Y-m-d H:i:s'),
        ];

        if ($isCreate) {
            $data['CreatedBy']     = $userUID;
            $data['CreatedOn']     = date('Y-m-d H:i:s');
            $data['PaidAmount']    = $isPaid ? $netAmount : 0;
            $data['BalanceAmount'] = $isPaid ? 0 : $netAmount;
        }

        return $data;
    }

    // Saves parsed items to ExpenseItemsTbl — skipped entirely for without-tax mode.
    // On create: all items have UID=0 → INSERT.
    // On update:
    //   - UID > 0 → UPDATE that specific row in place
    //   - UID = 0 → INSERT as new row
    //   - DeletedItemUIDs (sent explicitly by frontend) → soft-delete those rows
    private function _saveExpenseItems(array $PostData, int $expenseUID, int $orgUID, int $actorUID): void {
        if (!(int)getPostValue($PostData, 'TaxApplicable')) return;

        $dec       = $this->_decimals();
        $itemsJson = getPostValue($PostData, 'Items');
        $rawItems  = $itemsJson ? json_decode($itemsJson, true) : [];
        if (!is_array($rawItems)) return;

        // 1. Soft-delete items the user explicitly removed
        $deletedJson = getPostValue($PostData, 'DeletedItemUIDs');
        $deletedUIDs = $deletedJson ? json_decode($deletedJson, true) : [];
        if (is_array($deletedUIDs)) {
            foreach ($deletedUIDs as $dUID) {
                $dUID = (int)$dUID;
                if ($dUID > 0) {
                    $this->expenses_model->softDeleteExpenseItem($dUID, $orgUID, $actorUID);
                }
            }
        }

        // 2. INSERT new items / UPDATE existing items
        foreach ($rawItems as $i => $row) {
            $amount   = round((float)($row['Amount']        ?? 0), $dec);
            $taxPct   = round((float)($row['TaxPercentage'] ?? 0), 2);
            $taxAmt   = round((float)($row['TaxAmount']     ?? 0), $dec);
            $totalAmt = round((float)($row['TotalAmount']   ?? 0), $dec);
            if ($amount <= 0 && $totalAmt <= 0) continue;

            $uid  = (int)($row['ExpItemUID'] ?? 0);
            $item = [
                'CategoryUID'     => ($row['CategoryUID'] ?? 0) > 0 ? (int)$row['CategoryUID'] : null,
                'ItemDescription' => !empty($row['ItemDescription']) ? (string)$row['ItemDescription'] : null,
                'Amount'          => $amount,
                'TaxPercentage'   => $taxPct,
                'TaxAmount'       => $taxAmt,
                'CGSTAmount'      => round((float)($row['CGSTAmount'] ?? 0), $dec),
                'SGSTAmount'      => round((float)($row['SGSTAmount'] ?? 0), $dec),
                'IGSTAmount'      => round((float)($row['IGSTAmount'] ?? 0), $dec),
                'TotalAmount'     => $totalAmt,
                'SortOrder'       => $i,
            ];

            if ($uid > 0) {
                $this->expenses_model->updateExpenseItem($uid, $orgUID, $item, $actorUID);
            } else {
                $this->expenses_model->saveExpenseItems($expenseUID, $orgUID, [$item], $actorUID);
            }
        }
    }

    // Uploads files from $_FILES['Attachments'] and saves rows to ExpenseIncomeAttachmentsTbl


    // Inserts a PaymentsTbl record + AccountLedgerTbl debit for a paid expense
    private function _insertExpensePayment($PostData, int $orgUID, int $userUID, int $expenseUID, string $expenseNumber, float $netAmount, string $fallbackDate): array {

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
                'UpdatedBy'      => $userUID
            ]);
            if ($ledgerResp->Error) throw new Exception('Ledger entry failed: ' . $ledgerResp->Message);
        }

        return [
            'pmtUID'       => (int)$pmtResp->ID,
            'paymentDate'  => $paymentDate,
            'payTransYear' => $payTransYear,
        ];
    }
}
