<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Indirectincome extends MY_Controller {

    public  $pageData      = [];
    private $EndReturnData;
    private $pageModuleUID = 115;

    public function __construct() {
        parent::__construct();
        $this->load->model('indirectincome_model');
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

            // Credit the bank/cash account when income is received
            if ($data['IsReceived']) {
                $this->_createCreditLedger($orgUID, $userUID, $incomeUID, $incomeNumber, $data);
            }

            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error        = FALSE;
            $this->EndReturnData->Message      = 'Income recorded successfully.';
            $this->EndReturnData->IncomeUID    = $incomeUID;
            $this->EndReturnData->IncomeNumber = $incomeNumber;

            $this->_appendListResponse($orgUID);

        } catch (Throwable $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
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
            if (in_array($existing->DocStatus, ['Received', 'Cancelled'])) throw new Exception('This income cannot be edited.');

            $data = $this->_buildIncomeData($PostData, $userUID, $orgUID, false);
            unset($data['CreatedBy'], $data['CreatedOn'], $data['OrgUID'], $data['ModuleUID']);

            $resp = $this->dbwrite_model->updateData(
                'Transaction', 'IndirectIncomeTbl', $data,
                ['IncomeUID' => $incomeUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );
            if ($resp->Error) throw new Exception($resp->Message);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Income updated successfully.';

            $this->_appendListResponse($orgUID);

        } catch (Throwable $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
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
            if ($existing->DocStatus === 'Received') throw new Exception('Received income cannot be deleted.');

            $deleteData = $this->globalservice->baseDeleteArrayDetails();
            $deleteData['IsActive'] = 0;

            $resp = $this->dbwrite_model->updateData(
                'Transaction', 'IndirectIncomeTbl', $deleteData,
                ['IncomeUID' => $incomeUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );
            if ($resp->Error) throw new Exception($resp->Message);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Income deleted.';

            $this->_appendListResponse($orgUID);

        } catch (Throwable $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Update status (Pending → Received / Cancelled) ───────────────────────
    public function updateIncomeStatus() {
        $this->EndReturnData = new stdClass();
        try {
            $this->dbwrite_model->startTransaction();

            $PostData  = $this->input->post();
            $incomeUID = (int)getPostValue($PostData, 'IncomeUID');
            $newStatus = trim(getPostValue($PostData, 'Status'));
            $userUID   = $this->pageData['JwtData']->User->UserUID;
            $orgUID    = $this->pageData['JwtData']->User->OrgUID;

            if ($incomeUID <= 0)  throw new Exception('Invalid income record.');
            if (empty($newStatus)) throw new Exception('Status is required.');

            $existing = $this->indirectincome_model->getIncomeById($incomeUID, $orgUID);
            if (!$existing) throw new Exception('Income not found.');

            $allowed = ['Pending' => ['Received', 'Cancelled']];

            if (!isset($allowed[$existing->DocStatus]) || !in_array($newStatus, $allowed[$existing->DocStatus])) {
                throw new Exception('Invalid status transition.');
            }

            $updateData = [
                'DocStatus'  => $newStatus,
                'IsReceived' => ($newStatus === 'Received') ? 1 : $existing->IsReceived,
                'UpdatedBy'  => $userUID,
                'UpdatedOn'  => date('Y-m-d H:i:s'),
            ];

            $resp = $this->dbwrite_model->updateData(
                'Transaction', 'IndirectIncomeTbl', $updateData,
                ['IncomeUID' => $incomeUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );
            if ($resp->Error) throw new Exception($resp->Message);

            // Create CR ledger entry when status changes to Received
            if ($newStatus === 'Received') {
                $incomeData = [
                    'IsReceived'     => 1,
                    'BankAccountUID' => $existing->BankAccountUID,
                    'PaymentDate'    => $existing->PaymentDate,
                    'IncomeDate'     => $existing->IncomeDate,
                    'NetAmount'      => $existing->NetAmount,
                ];
                $this->_createCreditLedger($orgUID, $userUID, $incomeUID, $existing->IncomeNumber, $incomeData);
            }

            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Status updated to ' . $newStatus . '.';

            $this->_appendListResponse($orgUID);

        } catch (Throwable $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
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

    // ═══════════════════════════════════════════════════════════════════════
    // Private helpers
    // ═══════════════════════════════════════════════════════════════════════

    private function _appendListResponse($orgUID) {
        $GeneralSettings = ($this->redisservice->getUserCache('settings')) ?? new stdClass();
        $this->pageData['JwtData']->GenSettings = $GeneralSettings;

        $filterJson = $this->input->post('Filter');
        $filter = ($filterJson && ($decoded = json_decode($filterJson, true))) ? $decoded : ['Status' => 'All'];
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

    private function _buildIncomeData($PostData, $userUID, $orgUID, $isCreate) {
        $amount         = (float) getPostValue($PostData, 'Amount');
        $isReceived     = (int)   getPostValue($PostData, 'IsReceived')     === 1 ? 1 : 0;
        $categoryUID    = (int)   getPostValue($PostData, 'CategoryUID')    ?: NULL;
        $paymentTypeUID = (int)   getPostValue($PostData, 'PaymentTypeUID') ?: NULL;
        $bankAccountUID = (int)   getPostValue($PostData, 'BankAccountUID') ?: NULL;
        $incomeDate     =         getPostValue($PostData, 'IncomeDate')     ?: date('Y-m-d');
        $paymentDate    =         getPostValue($PostData, 'PaymentDate')    ?: NULL;
        $notes          =         getPostValue($PostData, 'Notes')          ?: NULL;
        $paymentNotes   =         getPostValue($PostData, 'PaymentNotes')   ?: NULL;

        if ($amount <= 0)                    throw new Exception('Income amount must be greater than 0.');
        if (empty($incomeDate))              throw new Exception('Income date is required.');
        if ($isReceived && !$paymentTypeUID) throw new Exception('Please select a payment type.');

        $data = [
            'OrgUID'         => $orgUID,
            'ModuleUID'      => $this->pageModuleUID,
            'IncomeDate'     => $incomeDate,
            'Amount'         => $amount,
            'TaxApplicable'  => 0,
            'TaxPercentage'  => 0,
            'TaxAmount'      => 0,
            'NetAmount'      => $amount,
            'CategoryUID'    => $categoryUID,
            'Notes'          => $notes,
            'DocStatus'      => $isReceived ? 'Received' : 'Pending',
            'IsReceived'     => $isReceived,
            'PaymentTypeUID' => $isReceived ? $paymentTypeUID : NULL,
            'BankAccountUID' => ($isReceived && $bankAccountUID) ? $bankAccountUID : NULL,
            'PaymentDate'    => $isReceived ? ($paymentDate ?: $incomeDate) : NULL,
            'PaymentNotes'   => $isReceived ? $paymentNotes : NULL,
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

    private function _createCreditLedger($orgUID, $userUID, $incomeUID, $incomeNumber, $data) {
        $ledgerBankUID = $data['BankAccountUID'] ?? null;
        if (!$ledgerBankUID) {
            $cashAcc = $this->indirectincome_model->getCashAccount($orgUID);
            $ledgerBankUID = $cashAcc ? (int)$cashAcc->BankAccountUID : null;
        }
        if (!$ledgerBankUID) return;

        $entryDate = (!empty($data['PaymentDate']) ? $data['PaymentDate'] : null)
                   ?: (!empty($data['IncomeDate'])  ? $data['IncomeDate']  : date('Y-m-d'));

        $ledgerResp = $this->dbwrite_model->insertData('Transaction', 'AccountLedgerTbl', [
            'OrgUID'         => $orgUID,
            'BankAccountUID' => $ledgerBankUID,
            'EntryDate'      => $entryDate,
            'EntryType'      => 'CR',
            'Amount'         => $data['NetAmount'],
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
