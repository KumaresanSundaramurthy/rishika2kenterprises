<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Indirectincome extends MY_Controller {

    public  $pageData      = [];
    private $EndReturnData;
    private $pageModuleUID = 115;

    public function __construct() {
        parent::__construct();
        $this->load->helper('transaction');
        $this->load->model('indirectincome_model');
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
            $GeneralSettings = ($this->redisservice->getUserCache('settings')) ?? new stdClass();
            $this->pageData['JwtData']->GenSettings = $GeneralSettings;
            $limit  = $GeneralSettings->RowLimit ?? 10;
            $orgUID = $this->pageData['JwtData']->User->OrgUID;

            $filter = ['Status' => 'All'];

            $allData      = $this->indirectincome_model->getIncomeList($orgUID, $filter, $limit, 0);
            $allDataCount = $this->indirectincome_model->getIncomeCount($orgUID, $filter);
            $summaryStats = $this->indirectincome_model->getIncomeSummaryStats($orgUID);

            $this->pageData['ModRowData']    = $this->load->view('transactions/indirectincome/list', [
                'DataLists'    => $allData,
                'SerialNumber' => 0,
                'JwtData'      => $this->pageData['JwtData'],
            ], TRUE);
            $this->pageData['ModPagination'] = $this->globalservice->buildPagePaginationHtml('/indirectincome/getPageDetails', $allDataCount, 1, $limit);
            $this->pageData['ModAllCount']   = $allDataCount;
            $this->pageData['SummaryStats']  = $summaryStats;

            $this->pageData['Categories']   = $this->indirectincome_model->getCategories($orgUID);
            $this->pageData['PaymentTypes'] = $this->indirectincome_model->getPaymentTypes();
            $this->pageData['BankAccounts'] = $this->indirectincome_model->getBankAccounts($orgUID);

            $this->load->view('transactions/indirectincome/view', $this->pageData);

        } catch (Throwable $e) {
            log_message('error', 'Indirectincome::index — ' . $e->getMessage());
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

            $allData      = $this->indirectincome_model->getIncomeList($orgUID, $filter, $limit, $offset);
            $allDataCount = $this->indirectincome_model->getIncomeCount($orgUID, $filter);

            $rowHtml = $this->load->view('transactions/indirectincome/list', [
                'DataLists'    => $allData,
                'SerialNumber' => ($pageNo - 1) * $limit,
                'JwtData'      => $this->pageData['JwtData'],
            ], TRUE);

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->RecordHtmlData = $rowHtml;
            $this->EndReturnData->Pagination     = $this->globalservice->buildPagePaginationHtml('/indirectincome/getPageDetails', $allDataCount, $pageNo, $limit);
            $this->EndReturnData->TotalCount     = $allDataCount;
            $this->EndReturnData->SummaryStats   = $this->indirectincome_model->getIncomeSummaryStats($orgUID);

        } catch (Throwable $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Add income (modal AJAX) ───────────────────────────────────────────────
    public function addIncome() {
        $this->EndReturnData = new stdClass();
        try {
            $this->dbwrite_model->startTransaction();

            $PostData  = $this->input->post();
            $userUID   = $this->pageData['JwtData']->User->UserUID;
            $orgUID    = $this->pageData['JwtData']->User->OrgUID;

            $data = $this->_buildIncomeData($PostData, $userUID, $orgUID, true);

            $resp = $this->dbwrite_model->insertData('Transaction', 'IndirectIncomeTbl', $data);
            if ($resp->Error) throw new Exception($resp->Message);

            $incomeUID    = (int)$resp->ID;
            $incomeNumber = 'INC-' . str_pad($incomeUID, 4, '0', STR_PAD_LEFT);

            $this->dbwrite_model->updateData(
                'Transaction', 'IndirectIncomeTbl',
                ['IncomeNumber' => $incomeNumber],
                ['IncomeUID' => $incomeUID, 'OrgUID' => $orgUID]
            );

            if ($data['IsReceived']) {
                $this->_insertIncomePayment($PostData, $orgUID, $userUID, $incomeUID, $incomeNumber, $data['NetAmount'], $data['IncomeDate']);
            }

            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error        = FALSE;
            $this->EndReturnData->Message      = 'Income recorded successfully.';
            $this->EndReturnData->IncomeUID    = $incomeUID;
            $this->EndReturnData->IncomeNumber = $incomeNumber;

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

    // ── Update income (modal AJAX) ────────────────────────────────────────────
    public function updateIncome() {
        $this->EndReturnData = new stdClass();
        try {
            $PostData  = $this->input->post();
            $incomeUID = (int)getPostValue($PostData, 'IncomeUID');
            $userUID   = $this->pageData['JwtData']->User->UserUID;
            $orgUID    = $this->pageData['JwtData']->User->OrgUID;

            if ($incomeUID <= 0) throw new Exception('Invalid income record.');

            $existing = $this->indirectincome_model->getIncomeById($incomeUID, $orgUID);
            if (!$existing) throw new Exception('Income not found.');
            if ($existing->DocStatus === 'Cancelled') throw new Exception('This income cannot be edited.');

            $data = $this->_buildIncomeData($PostData, $userUID, $orgUID, false);
            unset($data['CreatedBy'], $data['CreatedOn'], $data['OrgUID'], $data['ModuleUID']);

            // Preserve DocStatus/IsReceived for Received entries
            if ($existing->DocStatus === 'Received') {
                $data['DocStatus']  = 'Received';
                $data['IsReceived'] = 1;
            }

            $resp = $this->dbwrite_model->updateData(
                'Transaction', 'IndirectIncomeTbl', $data,
                ['IncomeUID' => $incomeUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );
            if ($resp->Error) throw new Exception($resp->Message);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Income updated successfully.';

        } catch (Throwable $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        if (!$this->EndReturnData->Error) {
            $this->_appendListResponse($orgUID);
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Delete income ─────────────────────────────────────────────────────────
    public function deleteIncome() {
        $this->EndReturnData = new stdClass();
        try {
            $PostData  = $this->input->post();
            $incomeUID = (int)getPostValue($PostData, 'IncomeUID');
            $userUID   = $this->pageData['JwtData']->User->UserUID;
            $orgUID    = $this->pageData['JwtData']->User->OrgUID;

            if ($incomeUID <= 0) throw new Exception('Invalid income record.');

            $existing = $this->indirectincome_model->getIncomeById($incomeUID, $orgUID);
            if (!$existing) throw new Exception('Income not found.');
            if ($existing->DocStatus === 'Cancelled') throw new Exception('Cancelled income cannot be deleted.');

            $deleteData = $this->globalservice->baseDeleteArrayDetails();
            $deleteData['IsActive'] = 0;

            $resp = $this->dbwrite_model->updateData(
                'Transaction', 'IndirectIncomeTbl', $deleteData,
                ['IncomeUID' => $incomeUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
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
            $this->EndReturnData->Message = 'Income deleted.';

        } catch (Throwable $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        if (!$this->EndReturnData->Error) {
            $this->_appendListResponse($orgUID);
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Duplicate income (creates a Pending copy dated today) ────────────────
    public function duplicateIncome() {
        $this->EndReturnData = new stdClass();
        try {
            $PostData  = $this->input->post();
            $incomeUID = (int)getPostValue($PostData, 'IncomeUID');
            $userUID   = $this->pageData['JwtData']->User->UserUID;
            $orgUID    = $this->pageData['JwtData']->User->OrgUID;

            if ($incomeUID <= 0) throw new Exception('Invalid income record.');

            $src = $this->indirectincome_model->getIncomeById($incomeUID, $orgUID);
            if (!$src) throw new Exception('Income not found.');

            $data = [
                'OrgUID'        => $orgUID,
                'ModuleUID'     => $this->pageModuleUID,
                'IncomeDate'    => date('Y-m-d'),
                'Amount'        => $src->Amount,
                'TaxApplicable' => $src->TaxApplicable,
                'TaxPercentage' => $src->TaxPercentage ?? 0,
                'TaxAmount'     => $src->TaxAmount,
                'NetAmount'     => $src->NetAmount,
                'CategoryUID'   => $src->CategoryUID,
                'Notes'         => $src->Notes,
                'DocStatus'     => 'Pending',
                'IsReceived'    => 0,
                'IsActive'      => 1,
                'IsDeleted'     => 0,
                'CreatedBy'     => $userUID,
                'UpdatedBy'     => $userUID,
                'CreatedOn'     => date('Y-m-d H:i:s'),
                'UpdatedOn'     => date('Y-m-d H:i:s'),
            ];

            $resp = $this->dbwrite_model->insertData('Transaction', 'IndirectIncomeTbl', $data);
            if ($resp->Error) throw new Exception($resp->Message);

            $newUID    = (int)$resp->ID;
            $newNumber = 'INC-' . str_pad($newUID, 4, '0', STR_PAD_LEFT);
            $this->dbwrite_model->updateData(
                'Transaction', 'IndirectIncomeTbl',
                ['IncomeNumber' => $newNumber],
                ['IncomeUID' => $newUID, 'OrgUID' => $orgUID]
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
            $PostData  = $this->input->post();
            $incomeUID = (int)getPostValue($PostData, 'TransUID');
            $userUID   = $this->pageData['JwtData']->User->UserUID;
            $orgUID    = $this->pageData['JwtData']->User->OrgUID;

            if ($incomeUID <= 0) throw new Exception('Invalid income record.');

            $existing = $this->indirectincome_model->getIncomeById($incomeUID, $orgUID);
            if (!$existing) throw new Exception('Income not found.');
            if ($existing->DocStatus !== 'Pending') throw new Exception('Only pending income can be marked as received.');

            $paymentTypeUID = (int)getPostValue($PostData, 'PaymentTypeUID') ?: NULL;
            $bankAccountUID = (int)getPostValue($PostData, 'BankAccountUID') ?: NULL;
            $paymentDate    = getPostValue($PostData, 'PaymentDate') ?: $existing->IncomeDate;
            $referenceNo    = getPostValue($PostData, 'ReferenceNo') ?: NULL;
            $notes          = getPostValue($PostData, 'Notes')       ?: NULL;

            if (!$paymentTypeUID) throw new Exception('Please select a payment type.');

            $this->dbwrite_model->startTransaction();

            $resp = $this->dbwrite_model->updateData(
                'Transaction', 'IndirectIncomeTbl',
                ['DocStatus' => 'Received', 'IsReceived' => 1, 'UpdatedBy' => $userUID, 'UpdatedOn' => date('Y-m-d H:i:s')],
                ['IncomeUID' => $incomeUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );
            if ($resp->Error) throw new Exception($resp->Message);

            $payTransYear  = (int)date('Y', strtotime($paymentDate));
            $payPrefixData = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID, 'Prefix.ModuleUID' => 110]);
            $payPrefix     = !empty($payPrefixData->Data) ? $payPrefixData->Data[0] : null;
            $payPrefixUID  = $payPrefix ? (int)$payPrefix->PrefixUID : null;
            $paymentNumber = $payPrefixUID ? (int)$this->transactions_model->getNextPaymentNumber($payPrefixUID, $orgUID, $payTransYear) : 0;
            $token         = $this->transactions_model->_generateReceiptToken();

            $pmtResp = $this->dbwrite_model->insertData('Transaction', 'PaymentsTbl', [
                'OrgUID'           => $orgUID,
                'ReceiptToken'     => $token,
                'PaymentDate'      => $paymentDate,
                'PaymentModuleUID' => 110,
                'PrefixUID'        => $payPrefixUID,
                'PaymentNumber'    => $paymentNumber,
                'UniqueNumber'     => $existing->IncomeNumber,
                'TransYear'        => $payTransYear,
                'TransUID'         => $incomeUID,
                'ModuleUID'        => $this->pageModuleUID,
                'SourceType'       => 'IndirectIncome',
                'PartyType'        => NULL,
                'PartyUID'         => NULL,
                'PaymentTypeUID'   => $paymentTypeUID,
                'Amount'           => $existing->NetAmount,
                'BankAccountUID'   => $bankAccountUID ?: NULL,
                'Notes'            => $notes,
                'PaymentSource'    => 'Create',
                'PaymentDirection' => 'In',
                'IsFullyPaid'      => 1,
                'ExcessAmount'     => 0,
                'IsActive'         => 1,
                'IsDeleted'        => 0,
                'CreatedBy'        => $userUID,
                'UpdatedBy'        => $userUID,
            ]);
            if ($pmtResp->Error) throw new Exception('Payment record failed: ' . $pmtResp->Message);

            $ledgerBankUID = $bankAccountUID;
            if (!$ledgerBankUID) {
                $cashAcc = $this->indirectincome_model->getCashAccount($orgUID);
                $ledgerBankUID = $cashAcc ? (int)$cashAcc->BankAccountUID : null;
            }
            if ($ledgerBankUID) {
                $ledgerResp = $this->dbwrite_model->insertData('Transaction', 'AccountLedgerTbl', [
                    'OrgUID'         => $orgUID,
                    'BankAccountUID' => $ledgerBankUID,
                    'EntryDate'      => $paymentDate,
                    'EntryType'      => 'CR',
                    'Amount'         => $existing->NetAmount,
                    'SourceType'     => 'IndirectIncome',
                    'SourceUID'      => $incomeUID,
                    'ModuleUID'      => $this->pageModuleUID,
                    'ReferenceNo'    => $referenceNo,
                    'Narration'      => 'Indirect income received — ' . $existing->IncomeNumber,
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
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Income marked as received.';

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

    // ── Update status (Pending → Received / Cancelled, Received → Cancelled) ──
    public function updateIncomeStatus() {
        $this->EndReturnData = new stdClass();
        try {
            $this->dbwrite_model->startTransaction();

            $PostData  = $this->input->post();
            $incomeUID = (int)getPostValue($PostData, 'IncomeUID');
            $newStatus = trim(getPostValue($PostData, 'Status'));
            $userUID   = $this->pageData['JwtData']->User->UserUID;
            $orgUID    = $this->pageData['JwtData']->User->OrgUID;

            if ($incomeUID <= 0)   throw new Exception('Invalid income record.');
            if (empty($newStatus)) throw new Exception('Status is required.');

            $existing = $this->indirectincome_model->getIncomeById($incomeUID, $orgUID);
            if (!$existing) throw new Exception('Income not found.');

            $allowed = [
                'Pending'  => ['Received', 'Cancelled'],
                'Received' => ['Cancelled'],
            ];

            if (!isset($allowed[$existing->DocStatus]) || !in_array($newStatus, $allowed[$existing->DocStatus])) {
                throw new Exception('Invalid status transition.');
            }

            $updateData = [
                'DocStatus'  => $newStatus,
                'IsReceived' => ($newStatus === 'Received') ? 1 : 0,
                'UpdatedBy'  => $userUID,
                'UpdatedOn'  => date('Y-m-d H:i:s'),
            ];

            $resp = $this->dbwrite_model->updateData(
                'Transaction', 'IndirectIncomeTbl', $updateData,
                ['IncomeUID' => $incomeUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );
            if ($resp->Error) throw new Exception($resp->Message);

            if ($newStatus === 'Received') {
                $this->_insertIncomePayment(
                    $PostData, $orgUID, $userUID,
                    $incomeUID, $existing->IncomeNumber,
                    $existing->NetAmount, $existing->IncomeDate
                );
            } elseif ($newStatus === 'Cancelled' && !empty($existing->PaymentUID)) {
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

    // ── Get single income detail ──────────────────────────────────────────────
    public function getIncomeDetail() {
        $this->EndReturnData = new stdClass();
        try {
            $PostData  = $this->input->post();
            $incomeUID = (int)getPostValue($PostData, 'IncomeUID');
            $orgUID    = $this->pageData['JwtData']->User->OrgUID;

            if ($incomeUID <= 0) throw new Exception('Invalid income record.');

            $income = $this->indirectincome_model->getIncomeById($incomeUID, $orgUID);
            if (!$income) throw new Exception('Income not found.');

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $income;

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
            $this->EndReturnData->Data  = $this->indirectincome_model->getCategories($orgUID);
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

            $resp = $this->dbwrite_model->insertData('Transaction', 'IndirectIncomeCategoryTbl', [
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

    // ── Update category name ──────────────────────────────────────────────────
    public function updateCategory() {
        $this->EndReturnData = new stdClass();
        try {
            $PostData    = $this->input->post();
            $categoryUID = (int)getPostValue($PostData, 'CategoryUID');
            $name        = trim(getPostValue($PostData, 'CategoryName'));
            $orgUID      = $this->pageData['JwtData']->User->OrgUID;
            $userUID     = $this->pageData['JwtData']->User->UserUID;

            if ($categoryUID <= 0) throw new Exception('Invalid category.');
            if (empty($name))      throw new Exception('Category name is required.');

            $resp = $this->dbwrite_model->updateData('Transaction', 'IndirectIncomeCategoryTbl', [
                'CategoryName' => $name,
                'UpdatedBy'    => $userUID,
                'UpdatedOn'    => date('Y-m-d H:i:s'),
            ], ['CategoryUID' => $categoryUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]);

            if ($resp->Error) throw new Exception($resp->Message);

            $this->EndReturnData->Error        = FALSE;
            $this->EndReturnData->Message      = 'Category updated.';
            $this->EndReturnData->CategoryUID  = $categoryUID;
            $this->EndReturnData->CategoryName = $name;
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
            $orgUID      = $this->pageData['JwtData']->User->OrgUID;
            $userUID     = $this->pageData['JwtData']->User->UserUID;

            if ($categoryUID <= 0) throw new Exception('Invalid category.');
            if ($this->indirectincome_model->isCategoryLinked($categoryUID, $orgUID)) {
                throw new Exception('This category is used in existing income records and cannot be deleted.');
            }

            $resp = $this->dbwrite_model->updateData('Transaction', 'IndirectIncomeCategoryTbl', [
                'IsDeleted' => 1,
                'IsActive'  => 0,
                'UpdatedBy' => $userUID,
                'UpdatedOn' => date('Y-m-d H:i:s'),
            ], ['CategoryUID' => $categoryUID, 'OrgUID' => $orgUID]);

            if ($resp->Error) throw new Exception($resp->Message);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Category deleted.';
        } catch (Throwable $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Category list (paginated, for manager modal) ──────────────────────────
    public function getCategoryList() {
        $this->EndReturnData = new stdClass();
        try {
            $orgUID = $this->pageData['JwtData']->User->OrgUID;
            $pageNo = max(1, (int)($this->input->post('PageNo') ?: 1));
            $limit  = 30;
            $search = trim($this->input->post('Search') ?: '');
            $list   = $this->indirectincome_model->getCategoryList($orgUID, $search, $limit, ($pageNo - 1) * $limit);
            $total  = $this->indirectincome_model->getCategoryCount($orgUID, $search);

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->RecordHtmlData = $this->_buildCategoryListHtml($list);
            $this->EndReturnData->Pagination     = $this->globalservice->buildPagePaginationHtml('/indirectincome/getCategoryList', $total, $pageNo, $limit);
            $this->EndReturnData->TotalCount     = $total;
        } catch (Throwable $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Renders category rows as list-group HTML ──────────────────────────────
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
                $html .= '<button class="btn btn-icon btn-sm text-primary incCatEditBtn" data-uid="' . $uid . '" data-name="' . $eName . '" title="Edit"><i class="bx bx-edit" style="font-size:1rem;"></i></button>';
                $html .= '<button class="btn btn-icon btn-sm text-danger incCatDeleteBtn" data-uid="' . $uid . '" data-name="' . $eName . '" title="Delete"><i class="bx bx-trash" style="font-size:1rem;"></i></button>';
            } else {
                $html .= '<span class="text-muted px-2" title="System category — cannot be modified"><i class="bx bx-lock-alt" style="font-size:.85rem;"></i></span>';
            }
            $html .= '</div></li>';
        }
        return $html;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // Private helpers
    // ═══════════════════════════════════════════════════════════════════════

    private function _appendListResponse($orgUID) {
        $GeneralSettings = ($this->redisservice->getUserCache('settings')) ?? new stdClass();
        $this->pageData['JwtData']->GenSettings = $GeneralSettings;

        $filterRaw = $this->input->post('Filter');
        $filter = is_array($filterRaw) ? $filterRaw : (($filterRaw && ($decoded = json_decode($filterRaw, true))) ? $decoded : ['Status' => 'All']);
        $limit  = (int)($this->input->post('RowLimit') ?: ($GeneralSettings->RowLimit ?? 10));

        $allData  = $this->indirectincome_model->getIncomeList($orgUID, $filter, $limit, 0);
        $allCount = $this->indirectincome_model->getIncomeCount($orgUID, $filter);

        $rowHtml = $this->load->view('transactions/indirectincome/list', [
            'DataLists'    => $allData,
            'SerialNumber' => 0,
            'JwtData'      => $this->pageData['JwtData'],
        ], TRUE);

        $this->EndReturnData->RecordHtmlData = $rowHtml;
        $this->EndReturnData->TotalCount     = $allCount;
        $this->EndReturnData->Pagination     = $this->globalservice->buildPagePaginationHtml('/indirectincome/getPageDetails', $allCount, 1, $limit);
        $this->EndReturnData->SummaryStats   = $this->indirectincome_model->getIncomeSummaryStats($orgUID);
    }

    // Validates POST, computes amounts, returns data array for IndirectIncomeTbl only
    private function _buildIncomeData($PostData, $userUID, $orgUID, $isCreate) {
        $amount         = (float) getPostValue($PostData, 'Amount');
        $isReceived     = (int)   getPostValue($PostData, 'IsReceived')     === 1 ? 1 : 0;
        $categoryUID    = (int)   getPostValue($PostData, 'CategoryUID')    ?: NULL;
        $paymentTypeUID = (int)   getPostValue($PostData, 'PaymentTypeUID') ?: NULL;
        $incomeDate     =         getPostValue($PostData, 'IncomeDate')     ?: date('Y-m-d');
        $notes          =         getPostValue($PostData, 'Notes')          ?: NULL;

        if ($amount <= 0)                    throw new Exception('Income amount must be greater than 0.');
        if (empty($incomeDate))              throw new Exception('Income date is required.');
        if ($isReceived && !$paymentTypeUID) throw new Exception('Please select a payment type.');

        $data = [
            'OrgUID'        => $orgUID,
            'ModuleUID'     => $this->pageModuleUID,
            'IncomeDate'    => $incomeDate,
            'Amount'        => $amount,
            'TaxApplicable' => 0,
            'TaxPercentage' => 0,
            'TaxAmount'     => 0,
            'NetAmount'     => $amount,
            'CategoryUID'   => $categoryUID,
            'Notes'         => $notes,
            'DocStatus'     => $isReceived ? 'Received' : 'Pending',
            'IsReceived'    => $isReceived,
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

    // Inserts a PaymentsTbl record + AccountLedgerTbl credit for received income
    private function _insertIncomePayment($PostData, $orgUID, $userUID, $incomeUID, $incomeNumber, $netAmount, $fallbackDate) {
        $paymentTypeUID = (int)getPostValue($PostData, 'PaymentTypeUID') ?: NULL;
        $bankAccountUID = (int)getPostValue($PostData, 'BankAccountUID') ?: NULL;
        $paymentDate    = getPostValue($PostData, 'PaymentDate') ?: $fallbackDate;
        $paymentNotes   = getPostValue($PostData, 'PaymentNotes') ?: NULL;

        if (!$paymentTypeUID) throw new Exception('Please select a payment type.');

        $payTransYear  = (int)date('Y', strtotime($paymentDate));
        $payPrefixData = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID, 'Prefix.ModuleUID' => 110]);
        $payPrefix     = !empty($payPrefixData->Data) ? $payPrefixData->Data[0] : null;
        $payPrefixUID  = $payPrefix ? (int)$payPrefix->PrefixUID : null;
        $paymentNumber = $payPrefixUID ? (int)$this->transactions_model->getNextPaymentNumber($payPrefixUID, $orgUID, $payTransYear) : 0;
        $token         = $this->transactions_model->_generateReceiptToken();

        $pmtResp = $this->dbwrite_model->insertData('Transaction', 'PaymentsTbl', [
            'OrgUID'           => $orgUID,
            'ReceiptToken'     => $token,
            'PaymentDate'      => $paymentDate,
            'PaymentModuleUID' => 110,
            'PrefixUID'        => $payPrefixUID,
            'PaymentNumber'    => $paymentNumber,
            'UniqueNumber'     => $incomeNumber,
            'TransYear'        => $payTransYear,
            'TransUID'         => $incomeUID,
            'ModuleUID'        => $this->pageModuleUID,
            'SourceType'       => 'IndirectIncome',
            'PartyType'        => NULL,
            'PartyUID'         => NULL,
            'PaymentTypeUID'   => $paymentTypeUID,
            'Amount'           => $netAmount,
            'BankAccountUID'   => $bankAccountUID ?: NULL,
            'Notes'            => $paymentNotes,
            'PaymentSource'    => 'Create',
            'PaymentDirection' => 'In',
            'IsFullyPaid'      => 1,
            'ExcessAmount'     => 0,
            'IsActive'         => 1,
            'IsDeleted'        => 0,
            'CreatedBy'        => $userUID,
            'UpdatedBy'        => $userUID,
        ]);
        if ($pmtResp->Error) throw new Exception('Payment record failed: ' . $pmtResp->Message);

        // Ledger credit entry
        $ledgerBankUID = $bankAccountUID;
        if (!$ledgerBankUID) {
            $cashAcc = $this->indirectincome_model->getCashAccount($orgUID);
            $ledgerBankUID = $cashAcc ? (int)$cashAcc->BankAccountUID : null;
        }
        if ($ledgerBankUID) {
            $ledgerResp = $this->dbwrite_model->insertData('Transaction', 'AccountLedgerTbl', [
                'OrgUID'         => $orgUID,
                'BankAccountUID' => $ledgerBankUID,
                'EntryDate'      => $paymentDate,
                'EntryType'      => 'CR',
                'Amount'         => $netAmount,
                'SourceType'     => 'IndirectIncome',
                'SourceUID'      => $incomeUID,
                'ModuleUID'      => $this->pageModuleUID,
                'ReferenceNo'    => null,
                'Narration'      => 'Indirect income received — ' . $incomeNumber,
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
