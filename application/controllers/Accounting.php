<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property object $accountledger_model
 * @property object $dbwrite_model
 * @property object $globalservice
 * @property object $redisservice
 * @property object $input
 */
class Accounting extends MY_Controller {

    public  $pageData  = [];
    /** @var object|null */
    private $EndReturnData;

    public function __construct() {
        parent::__construct();
        $this->load->model('accountledger_model');
    }

    // ── Chart of Accounts — list page ────────────────────────────────────────
    public function chartofaccounts() {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $stats   = $this->accountledger_model->getChartOfAccountsStats();
            $rows    = $this->accountledger_model->getChartOfAccountsList($this->_rowLimit(), 0);
            $total   = $this->accountledger_model->getChartOfAccountsCount();
            $parents = $this->accountledger_model->getParentLedgers();

            $this->pageData['Stats']        = $stats;
            $this->pageData['ModRowData']   = $this->_buildListHtml($rows, 0);
            $this->pageData['ModPagination']= $this->globalservice->buildPagePaginationHtml(
                '/accounting/getChartOfAccountsPage', $total, 1, $this->_rowLimit()
            );
            $this->pageData['ParentLedgers']= $parents;
            $this->pageData['TotalCount']   = $total;
            $this->load->view('accounting/chart_of_accounts/view', $this->pageData);
        } catch (Exception $e) {
            redirect('dashboard', 'refresh');
        }
    }

    // ── AJAX — paginated list ─────────────────────────────────────────────────
    public function getChartOfAccountsPage($pageNo = 1) {
        $this->EndReturnData = new stdClass();
        try {
            $pageNo = max(1, (int)$pageNo);
            $filter = $this->input->post('Filter') ?: [];
            $offset = ($pageNo - 1) * $this->_rowLimit();
            $rows   = $this->accountledger_model->getChartOfAccountsList($this->_rowLimit(), $offset, $filter);
            $total  = $this->accountledger_model->getChartOfAccountsCount($filter);
            $stats  = $this->accountledger_model->getChartOfAccountsStats();

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->RecordHtmlData = $this->_buildListHtml($rows, $offset);
            $this->EndReturnData->Pagination     = $this->globalservice->buildPagePaginationHtml(
                '/accounting/getChartOfAccountsPage', $total, $pageNo, $this->_rowLimit()
            );
            $this->EndReturnData->TotalCount     = $total;
            $this->EndReturnData->Stats          = $stats;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Save ledger (create / update) ────────────────────────────────────────
    public function saveLedger() {
        $this->EndReturnData = new stdClass();
        try {
            $p          = $this->input->post();
            $ledgerUID  = (int)($p['LedgerUID'] ?? 0);
            $code       = trim($p['LedgerCode']  ?? '');
            $name       = trim($p['LedgerName']  ?? '');
            $type       = trim($p['LedgerType']  ?? '');
            $parentUID  = (int)($p['ParentLedgerUID'] ?? 0) ?: null;
            $openBal    = round((float)($p['OpeningBalance'] ?? 0), 2);
            $openType   = in_array($p['OpeningBalanceType'] ?? '', ['Debit','Credit'])
                ? $p['OpeningBalanceType'] : 'Debit';
            $userUID    = (int)$this->pageData['JwtData']->User->UserUID;

            $validTypes = ['Asset','Liability','Income','Expense','Customer','Vendor','Employee','Bank','Cash'];
            if (!$name) throw new Exception('Ledger name is required.');
            if (!in_array($type, $validTypes)) throw new Exception('Invalid ledger type.');

            $orgUID = (int)$this->pageData['JwtData']->Org->OrgUID;
            $this->load->model('dbwrite_model');
            $data = [
                'LedgerName'          => $name,
                'LedgerType'          => $type,
                'ParentLedgerUID'     => $parentUID,
                'OpeningBalance'      => $openBal,
                'OpeningBalanceType'  => $openType,
                'UpdatedBy'           => $userUID,
            ];

            if ($ledgerUID > 0) {
                // Update — code is immutable; scope to this org
                $res = $this->dbwrite_model->updateData('Accounting', 'ChartOfAccounts', $data,
                    ['LedgerUID' => $ledgerUID, 'OrgUID' => $orgUID]);
                if ($res->Error) throw new Exception($res->Message);
                $msg = 'Ledger updated successfully.';
            } else {
                if (!$code) throw new Exception('Ledger code is required.');
                $data['OrgUID']           = $orgUID;
                $data['LedgerCode']       = $code;
                $data['CurrentBalance']   = $openBal;
                $data['CurrentBalanceType'] = $openType;
                $data['IsActive']         = 1;
                $data['IsDeleted']        = 0;
                $data['CreatedBy']        = $userUID;
                $res = $this->dbwrite_model->insertData('Accounting', 'ChartOfAccounts', $data);
                if ($res->Error) throw new Exception($res->Message);
                $msg = 'Ledger created successfully.';
            }

            $filter = $this->input->post('Filter') ?: [];
            $rows   = $this->accountledger_model->getChartOfAccountsList($this->_rowLimit(), 0, $filter);
            $total  = $this->accountledger_model->getChartOfAccountsCount($filter);
            $stats  = $this->accountledger_model->getChartOfAccountsStats();

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->Message        = $msg;
            $this->EndReturnData->RecordHtmlData = $this->_buildListHtml($rows, 0);
            $this->EndReturnData->Pagination     = $this->globalservice->buildPagePaginationHtml(
                '/accounting/getChartOfAccountsPage', $total, 1, $this->_rowLimit()
            );
            $this->EndReturnData->Stats          = $stats;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Toggle active / inactive ──────────────────────────────────────────────
    public function toggleLedgerStatus() {
        $this->EndReturnData = new stdClass();
        try {
            $ledgerUID = (int)$this->input->post('LedgerUID');
            $newStatus = (int)$this->input->post('IsActive');
            if (!$ledgerUID) throw new Exception('Invalid ledger.');
            if (!in_array($newStatus, [0, 1])) throw new Exception('Invalid status.');

            $userUID = (int)$this->pageData['JwtData']->User->UserUID;
            $orgUID  = (int)$this->pageData['JwtData']->Org->OrgUID;
            $this->load->model('dbwrite_model');
            $res = $this->dbwrite_model->updateData('Accounting', 'ChartOfAccounts',
                ['IsActive' => $newStatus, 'UpdatedBy' => $userUID],
                ['LedgerUID' => $ledgerUID, 'OrgUID' => $orgUID]
            );
            if ($res->Error) throw new Exception($res->Message);

            $filter = $this->input->post('Filter') ?: [];
            $rows   = $this->accountledger_model->getChartOfAccountsList($this->_rowLimit(), 0, $filter);
            $total  = $this->accountledger_model->getChartOfAccountsCount($filter);
            $stats  = $this->accountledger_model->getChartOfAccountsStats();

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->Message        = $newStatus ? 'Ledger activated.' : 'Ledger deactivated.';
            $this->EndReturnData->RecordHtmlData = $this->_buildListHtml($rows, 0);
            $this->EndReturnData->Pagination     = $this->globalservice->buildPagePaginationHtml(
                '/accounting/getChartOfAccountsPage', $total, 1, $this->_rowLimit()
            );
            $this->EndReturnData->Stats          = $stats;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Soft-delete (only if no journal entries) ──────────────────────────────
    public function deleteLedger() {
        $this->EndReturnData = new stdClass();
        try {
            $ledgerUID = (int)$this->input->post('LedgerUID');
            if (!$ledgerUID) throw new Exception('Invalid ledger.');
            if ($this->accountledger_model->ledgerHasTransactions($ledgerUID)) {
                throw new Exception('Cannot delete a ledger that has journal entries.');
            }
            $userUID = (int)$this->pageData['JwtData']->User->UserUID;
            $orgUID  = (int)$this->pageData['JwtData']->Org->OrgUID;
            $this->load->model('dbwrite_model');
            $res = $this->dbwrite_model->updateData('Accounting', 'ChartOfAccounts',
                ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID],
                ['LedgerUID' => $ledgerUID, 'OrgUID' => $orgUID]
            );
            if ($res->Error) throw new Exception($res->Message);

            $filter = $this->input->post('Filter') ?: [];
            $rows   = $this->accountledger_model->getChartOfAccountsList($this->_rowLimit(), 0, $filter);
            $total  = $this->accountledger_model->getChartOfAccountsCount($filter);
            $stats  = $this->accountledger_model->getChartOfAccountsStats();

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->Message        = 'Ledger deleted.';
            $this->EndReturnData->RecordHtmlData = $this->_buildListHtml($rows, 0);
            $this->EndReturnData->Pagination     = $this->globalservice->buildPagePaginationHtml(
                '/accounting/getChartOfAccountsPage', $total, 1, $this->_rowLimit()
            );
            $this->EndReturnData->Stats          = $stats;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Trial Balance — page ─────────────────────────────────────────────────
    public function trialbalance() {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $years = $this->accountledger_model->getJournalFinancialYears();
            // Default to current year or first available
            $currentFY = (int)date('Y');
            $defaultFY = in_array($currentFY, $years) ? $currentFY : ($years[0] ?? $currentFY);

            $this->pageData['FinancialYears'] = $years;
            $this->pageData['DefaultFY']      = $defaultFY;
            $this->load->view('accounting/trial_balance/view', $this->pageData);
        } catch (Exception $e) {
            redirect('dashboard', 'refresh');
        }
    }

    // ── Trial Balance — AJAX ──────────────────────────────────────────────────
    public function getTrialBalanceAjax() {
        $this->EndReturnData = new stdClass();
        try {
            $fy = (int)$this->input->post('FinancialYear');
            if (!$fy) $fy = (int)date('Y');

            $rows = $this->accountledger_model->getTrialBalance($fy);

            // Calculate closing balance per row and build summary
            $grandDr   = 0.0;
            $grandCr   = 0.0;
            $totObDr   = 0.0;
            $totObCr   = 0.0;

            foreach ($rows as &$r) {
                $ob     = (float)$r->OpeningBalance;
                $obType = $r->OpeningBalanceType ?? 'Debit';
                $dr     = (float)$r->PeriodDebit;
                $cr     = (float)$r->PeriodCredit;

                // Closing = opening ± net movement
                list($cb, $cbType) = $this->_calcBalance($ob, $obType, $dr, $cr);
                $r->ClosingBalance     = $cb;
                $r->ClosingBalanceType = $cbType;

                // Grand totals (all Dr lines on Dr side, Cr lines on Cr side)
                $grandDr += $dr;
                $grandCr += $cr;
                if ($obType === 'Debit')  $totObDr += $ob; else $totObCr += $ob;
            }
            unset($r);

            $JwtData = $this->pageData['JwtData'];
            $html = $this->load->view('accounting/trial_balance/table',
                ['Rows' => $rows, 'JwtData' => $JwtData,
                 'GrandDebit' => $grandDr, 'GrandCredit' => $grandCr,
                 'TotalObDr'  => $totObDr,  'TotalObCr'   => $totObCr,
                 'FinancialYear' => $fy], TRUE);

            $this->EndReturnData->Error       = FALSE;
            $this->EndReturnData->Html        = $html;
            $this->EndReturnData->GrandDebit  = $grandDr;
            $this->EndReturnData->GrandCredit = $grandCr;
            $this->EndReturnData->IsBalanced  = abs($grandDr - $grandCr) < 0.01;
            $this->EndReturnData->RowCount    = count($rows);
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Journal Listing — page ───────────────────────────────────────────────
    public function journallist() {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $stats  = $this->accountledger_model->getJournalStats();
            $rows   = $this->accountledger_model->getJournalList($this->_rowLimit(), 0);
            $total  = $this->accountledger_model->getJournalCount();

            $this->pageData['Stats']         = $stats;
            $this->pageData['ModRowData']    = $this->_buildJournalListHtml($rows, 0);
            $this->pageData['ModPagination'] = $this->globalservice->buildPagePaginationHtml(
                '/accounting/getJournalListPage', $total, 1, $this->_rowLimit()
            );
            $this->pageData['TotalCount']    = $total;
            $this->load->view('accounting/journal_list/view', $this->pageData);
        } catch (Exception $e) {
            redirect('dashboard', 'refresh');
        }
    }

    // ── Journal Listing — AJAX paginated ─────────────────────────────────────
    public function getJournalListPage($pageNo = 1) {
        $this->EndReturnData = new stdClass();
        try {
            $pageNo = max(1, (int)$pageNo);
            $filter = $this->input->post('Filter') ?: [];
            $offset = ($pageNo - 1) * $this->_rowLimit();
            $rows   = $this->accountledger_model->getJournalList($this->_rowLimit(), $offset, $filter);
            $total  = $this->accountledger_model->getJournalCount($filter);
            $stats  = $this->accountledger_model->getJournalStats();

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->RecordHtmlData = $this->_buildJournalListHtml($rows, $offset);
            $this->EndReturnData->Pagination     = $this->globalservice->buildPagePaginationHtml(
                '/accounting/getJournalListPage', $total, $pageNo, $this->_rowLimit()
            );
            $this->EndReturnData->Stats          = $stats;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Journal detail modal — AJAX ───────────────────────────────────────────
    public function getJournalDetail() {
        $this->EndReturnData = new stdClass();
        try {
            $journalUID = (int)$this->input->post('JournalUID');
            if (!$journalUID) throw new Exception('Invalid journal.');
            $journal = $this->accountledger_model->getJournalWithEntries($journalUID);
            if (!$journal) throw new Exception('Journal not found.');

            $JwtData = $this->pageData['JwtData'];
            $html = $this->load->view('accounting/journal_list/detail',
                ['Journal' => $journal, 'JwtData' => $JwtData], TRUE);

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Html  = $html;
            $this->EndReturnData->JournalNo = $journal->JournalNo;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    private function _buildJournalListHtml(array $rows, int $offset): string {
        $JwtData = $this->pageData['JwtData'];
        return $this->load->view('accounting/journal_list/list',
            ['DataLists' => $rows, 'SerialNumber' => $offset, 'JwtData' => $JwtData], TRUE);
    }

    // ── General Ledger — page ────────────────────────────────────────────────
    public function generalledger() {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $this->pageData['AllLedgers'] = $this->accountledger_model->getAllActiveLedgers();
            $this->load->view('accounting/general_ledger/view', $this->pageData);
        } catch (Exception $e) {
            redirect('dashboard', 'refresh');
        }
    }

    // ── General Ledger — AJAX fetch statement ────────────────────────────────
    public function getLedgerStatementAjax() {
        $this->EndReturnData = new stdClass();
        try {
            $ledgerUID = (int)$this->input->post('LedgerUID');
            $dateFrom  = $this->input->post('DateFrom') ?: null;
            $dateTo    = $this->input->post('DateTo')   ?: null;

            if (!$ledgerUID) throw new Exception('Please select a ledger account.');

            // Fetch ledger info
            $ledger = $this->accountledger_model->getLedgerById($ledgerUID);
            if (!$ledger) throw new Exception('Ledger not found.');

            // Opening balance = ledger base opening + all activity before dateFrom
            $obBase  = (float)$ledger->OpeningBalance;
            $obType  = $ledger->OpeningBalanceType ?? 'Debit';

            if ($dateFrom) {
                $prior = $this->accountledger_model->getLedgerActivityBefore($ledgerUID, $dateFrom);
                list($obBase, $obType) = $this->_calcBalance($obBase, $obType,
                    (float)$prior->TotalDebit, (float)$prior->TotalCredit);
            }

            // Statement lines
            $lines = $this->accountledger_model->getLedgerStatement($ledgerUID, $dateFrom, $dateTo);

            // Build running balance for each line
            $runBal  = $obBase;
            $runType = $obType;
            $totDr   = 0.0;
            $totCr   = 0.0;

            foreach ($lines as &$ln) {
                $amt = (float)$ln->Amount;
                if ($ln->TransactionType === 'Debit') {
                    $totDr += $amt;
                    list($runBal, $runType) = $this->_calcBalance($runBal, $runType, $amt, 0);
                } else {
                    $totCr += $amt;
                    list($runBal, $runType) = $this->_calcBalance($runBal, $runType, 0, $amt);
                }
                $ln->RunningBalance     = $runBal;
                $ln->RunningBalanceType = $runType;
            }
            unset($ln);

            $JwtData = $this->pageData['JwtData'];
            $html = $this->load->view('accounting/general_ledger/statement',
                ['Lines' => $lines, 'Ledger' => $ledger, 'JwtData' => $JwtData,
                 'OpeningBalance' => $obBase, 'OpeningBalanceType' => $obType,
                 'ClosingBalance' => $runBal, 'ClosingBalanceType' => $runType,
                 'TotalDebit' => $totDr, 'TotalCredit' => $totCr,
                 'DateFrom' => $dateFrom, 'DateTo' => $dateTo], TRUE);

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->Html           = $html;
            $this->EndReturnData->LedgerName     = $ledger->LedgerName;
            $this->EndReturnData->OpeningBalance = $obBase;
            $this->EndReturnData->OpeningType    = $obType;
            $this->EndReturnData->ClosingBalance = $runBal;
            $this->EndReturnData->ClosingType    = $runType;
            $this->EndReturnData->TotalDebit     = $totDr;
            $this->EndReturnData->TotalCredit    = $totCr;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    /** Calculate net balance after applying debit/credit amounts */
    private function _calcBalance(float $balance, string $balType, float $debit, float $credit): array {
        $net = $balance + ($balType === 'Debit' ? $debit - $credit : $credit - $debit);
        if ($net >= 0) return [$net, $balType];
        // Balance flipped sign
        $flipped = ['Debit' => 'Credit', 'Credit' => 'Debit'];
        return [abs($net), $flipped[$balType]];
    }

    // ── Private: build list HTML ──────────────────────────────────────────────
    private function _buildListHtml(array $rows, int $offset): string {
        $JwtData = $this->pageData['JwtData'];
        return $this->load->view('accounting/chart_of_accounts/list',
            ['DataLists' => $rows, 'SerialNumber' => $offset, 'JwtData' => $JwtData], TRUE);
    }
}
