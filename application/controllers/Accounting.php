<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property object $accountledger_model
 * @property object $accountledger
 * @property object $dbwrite_model
 * @property object $globalservice
 * @property object $redisservice
 * @property object $input
 */
class Accounting extends MY_Controller {

    public  $pageData  = [];
    /** @var object|null */
    protected $EndReturnData;

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
            $openBal    = round((float)($p['OpeningBalance'] ?? 0), $this->_decimals());
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

    // ── Manual Journal — save (POST) ─────────────────────────────────────────
    public function saveManualJournal(): void {
        $this->EndReturnData = new stdClass();
        try {
            $p       = $this->input->post();
            $date    = trim($p['JournalDate'] ?? '');
            $narr    = trim($p['Narration']   ?? '');
            $lines   = $p['Lines'] ?? [];

            if (!$date)                                   throw new Exception('Journal date is required.');
            if (!$narr)                                   throw new Exception('Narration is required.');
            if (!is_array($lines) || count($lines) < 2)  throw new Exception('At least 2 journal lines are required.');

            $dec     = $this->_decimals();
            $clean   = [];
            $totalDr = 0.0;
            $totalCr = 0.0;

            foreach ($lines as $line) {
                $ledgerUID = (int)($line['LedgerUID'] ?? 0);
                $type      = trim($line['Type']       ?? '');
                $amount    = round((float)($line['Amount'] ?? 0), $dec);
                if (!$ledgerUID || !in_array($type, ['Debit', 'Credit']) || $amount <= 0) continue;
                $clean[] = [
                    'LedgerUID'   => $ledgerUID,
                    'Type'        => $type,
                    'Amount'      => $amount,
                    'Particulars' => trim($line['Particulars'] ?? ''),
                ];
                if ($type === 'Debit') $totalDr += $amount;
                else                  $totalCr += $amount;
            }

            if (count($clean) < 2) throw new Exception('At least 2 valid journal lines are required.');
            if (abs($totalDr - $totalCr) > 0.01) {
                throw new Exception(
                    'Journal does not balance — Debit: ' . number_format($totalDr, $dec) .
                    ', Credit: ' . number_format($totalCr, $dec) . '.'
                );
            }

            $fy      = (int)date('Y', strtotime($date));
            $userUID = (int)$this->pageData['JwtData']->User->UserUID;
            $this->load->library('accountledger');
            $this->load->model('dbwrite_model');
            $jUID      = $this->accountledger->postManualJournal($date, $fy, $narr, $clean, $userUID);
            $journalNo = 'JRN-' . $fy . '-' . str_pad($jUID, 7, '0', STR_PAD_LEFT);

            $this->EndReturnData->Error     = FALSE;
            $this->EndReturnData->Message   = 'Journal entry ' . $journalNo . ' saved successfully.';
            $this->EndReturnData->JournalNo = $journalNo;
            $this->EndReturnData->JournalUID= $jUID;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Manual Journal — delete (reversal + soft-delete) ─────────────────────
    public function deleteManualJournal(): void {
        $this->EndReturnData = new stdClass();
        try {
            $journalUID = (int)$this->input->post('JournalUID');
            if (!$journalUID) throw new Exception('Invalid journal.');

            $userUID = (int)$this->pageData['JwtData']->User->UserUID;
            $this->load->library('accountledger');
            $this->load->model('dbwrite_model');
            $this->accountledger->reverseManualJournal($journalUID, $userUID);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Journal reversed and deleted successfully.';
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Bank Reconciliation — page ───────────────────────────────────────────
    public function bankreconciliation(): void {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $this->pageData['BankLedgers'] = $this->accountledger_model->getBankAndCashLedgers();
            $this->load->view('accounting/bank_reconciliation/view', $this->pageData);
        } catch (Exception $e) {
            log_message('error', 'bankreconciliation: ' . $e->getMessage());
            redirect('dashboard', 'refresh');
        }
    }

    // ── Bank Reconciliation — AJAX load entries ───────────────────────────────
    public function getBankReconAjax(): void {
        $this->EndReturnData = new stdClass();
        try {
            $ledgerUID = (int)$this->input->post('LedgerUID');
            $dateFrom  = $this->input->post('DateFrom') ?: null;
            $dateTo    = $this->input->post('DateTo')   ?: null;

            if (!$ledgerUID) throw new Exception('Please select a bank account.');

            $ledger = $this->accountledger_model->getLedgerById($ledgerUID);
            if (!$ledger) throw new Exception('Bank account not found.');

            $obBase = (float)$ledger->OpeningBalance;
            $obType = $ledger->OpeningBalanceType ?? 'Debit';
            if ($dateFrom) {
                $prior  = $this->accountledger_model->getLedgerActivityBefore($ledgerUID, $dateFrom);
                list($obBase, $obType) = $this->_calcBalance(
                    $obBase, $obType, (float)$prior->TotalDebit, (float)$prior->TotalCredit
                );
            }

            $entries   = $this->accountledger_model->getBankReconEntries($ledgerUID, $dateFrom, $dateTo);
            $JwtData   = $this->pageData['JwtData'];
            $html      = $this->load->view('accounting/bank_reconciliation/entries',
                ['Entries' => $entries, 'JwtData' => $JwtData], TRUE);

            $totalDr = $totalCr = $clDr = $clCr = 0.0;
            foreach ($entries as $en) {
                $amt = (float)$en->Amount;
                if ($en->TransactionType === 'Debit') {
                    $totalDr += $amt;
                    if ($en->IsReconciled) $clDr += $amt;
                } else {
                    $totalCr += $amt;
                    if ($en->IsReconciled) $clCr += $amt;
                }
            }

            list($bookBal,    $bookType)    = $this->_calcBalance($obBase, $obType, $totalDr, $totalCr);
            list($clearedBal, $clearedType) = $this->_calcBalance($obBase, $obType, $clDr, $clCr);

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->Html           = $html;
            $this->EndReturnData->LedgerName     = $ledger->LedgerName;
            $this->EndReturnData->OpeningBalance = $obBase;
            $this->EndReturnData->OpeningType    = $obType;
            $this->EndReturnData->TotalDr        = $totalDr;
            $this->EndReturnData->TotalCr        = $totalCr;
            $this->EndReturnData->BookBalance    = $bookBal;
            $this->EndReturnData->BookType       = $bookType;
            $this->EndReturnData->ClearedBalance = $clearedBal;
            $this->EndReturnData->ClearedType    = $clearedType;
            $this->EndReturnData->EntryCount     = count($entries);
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Bank Reconciliation — AJAX save cleared status ────────────────────────
    public function saveBankRecon(): void {
        $this->EndReturnData = new stdClass();
        try {
            $cleared   = array_values(array_filter(array_map('intval',
                (array)($this->input->post('ClearedUIDs')   ?: []))));
            $uncleared = array_values(array_filter(array_map('intval',
                (array)($this->input->post('UnclearedUIDs') ?: []))));

            $orgUID = (int)$this->pageData['JwtData']->Org->OrgUID;

            if (!empty($cleared))   $this->accountledger_model->bulkUpdateReconStatus($cleared,   1, $orgUID);
            if (!empty($uncleared)) $this->accountledger_model->bulkUpdateReconStatus($uncleared, 0, $orgUID);

            $this->EndReturnData->Error         = FALSE;
            $this->EndReturnData->Message       = count($cleared) . ' entries marked cleared, ' . count($uncleared) . ' uncleared.';
            $this->EndReturnData->ClearedCount  = count($cleared);
            $this->EndReturnData->UnclearedCount= count($uncleared);
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Ledger search dropdown for manual journal (GET, no CSRF) ─────────────
    public function getLedgersForJournal(): void {
        $this->EndReturnData = new stdClass();
        try {
            $search  = trim($this->input->get('q') ?: '');
            $rows    = $this->accountledger_model->getLedgersForJournalDropdown($search, 50);
            $results = [];
            foreach ($rows as $r) {
                $results[] = [
                    'id'   => (int)$r->LedgerUID,
                    'text' => $r->LedgerName . ' (' . $r->LedgerType . ')',
                    'code' => $r->LedgerCode,
                    'type' => $r->LedgerType,
                ];
            }
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->results = $results;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            $this->EndReturnData->results = [];
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Recurring Journals — page ─────────────────────────────────────────────
    public function recurringjournals(): void {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $stats = $this->accountledger_model->getRecurringJournalStats();
            $rows  = $this->accountledger_model->getRecurringJournalList($this->_rowLimit(), 0);
            $total = $this->accountledger_model->getRecurringJournalCount();

            $this->pageData['Stats']         = $stats;
            $this->pageData['ModRowData']    = $this->_buildRecurListHtml($rows, 0);
            $this->pageData['ModPagination'] = $this->globalservice->buildPagePaginationHtml(
                '/accounting/getRecurringJournalsPage', $total, 1, $this->_rowLimit()
            );
            $this->pageData['TotalCount']    = $total;
            $this->load->view('accounting/recurring_journals/view', $this->pageData);
        } catch (Exception $e) {
            log_message('error', 'recurringjournals: ' . $e->getMessage());
            redirect('dashboard', 'refresh');
        }
    }

    // ── Recurring Journals — AJAX paginated list ──────────────────────────────
    public function getRecurringJournalsPage(int $pageNo = 1): void {
        $this->EndReturnData = new stdClass();
        try {
            $pageNo   = max(1, $pageNo);
            $rawFilter= $this->input->post('Filter') ?: '{}';
            $filter   = is_array($rawFilter) ? $rawFilter : (json_decode($rawFilter, true) ?: []);
            $offset   = ($pageNo - 1) * $this->_rowLimit();
            $rows     = $this->accountledger_model->getRecurringJournalList($this->_rowLimit(), $offset, $filter);
            $total  = $this->accountledger_model->getRecurringJournalCount($filter);
            $stats  = $this->accountledger_model->getRecurringJournalStats();

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->RecordHtmlData = $this->_buildRecurListHtml($rows, $offset);
            $this->EndReturnData->Pagination     = $this->globalservice->buildPagePaginationHtml(
                '/accounting/getRecurringJournalsPage', $total, $pageNo, $this->_rowLimit()
            );
            $this->EndReturnData->TotalCount     = $total;
            $this->EndReturnData->Stats          = $stats;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Recurring Journals — GET single for edit modal ────────────────────────
    public function getRecurringJournalAjax(): void {
        $this->EndReturnData = new stdClass();
        try {
            $recurUID = (int)$this->input->get('RecurUID');
            if (!$recurUID) throw new Exception('Invalid recurring journal.');

            $journal = $this->accountledger_model->getRecurringJournalById($recurUID);
            if (!$journal) throw new Exception('Recurring journal not found.');

            $lines = [];
            foreach ($journal->Lines as $l) {
                $lines[] = [
                    'LedgerUID'       => (int)$l->LedgerUID,
                    'LedgerName'      => $l->LedgerName . ' (' . $l->LedgerType . ')',
                    'TransactionType' => $l->TransactionType,
                    'Amount'          => (float)$l->Amount,
                    'Particulars'     => $l->Particulars,
                ];
            }

            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->RecurUID   = (int)$journal->RecurUID;
            $this->EndReturnData->Title      = $journal->Title;
            $this->EndReturnData->Narration  = $journal->Narration;
            $this->EndReturnData->Frequency  = $journal->Frequency;
            $this->EndReturnData->StartDate  = $journal->StartDate;
            $this->EndReturnData->EndDate    = $journal->EndDate ?? '';
            $this->EndReturnData->Lines      = $lines;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Recurring Journals — POST save (create / update) ─────────────────────
    public function saveRecurringJournal(): void {
        $this->EndReturnData = new stdClass();
        try {
            $recurUID  = (int)$this->input->post('RecurUID');
            $title     = trim($this->input->post('Title') ?: '');
            $narration = trim($this->input->post('Narration') ?: '');
            $frequency = $this->input->post('Frequency') ?: 'Monthly';
            $startDate = $this->input->post('StartDate') ?: '';
            $endDate   = $this->input->post('EndDate') ?: null;
            $linesRaw  = $this->input->post('Lines') ?: '[]';

            if (!$title)                      throw new Exception('Title is required.');
            if (!$narration)                  throw new Exception('Narration is required.');
            if (!$startDate)                  throw new Exception('Start date is required.');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) throw new Exception('Invalid start date format.');

            $validFreq = ['Daily', 'Weekly', 'Monthly', 'Quarterly', 'Yearly'];
            if (!in_array($frequency, $validFreq, true)) throw new Exception('Invalid frequency.');

            $lines = is_array($linesRaw) ? $linesRaw : json_decode($linesRaw, true);
            if (empty($lines) || count($lines) < 2) throw new Exception('At least 2 journal lines required.');

            $totalDr = $totalCr = 0.0;
            foreach ($lines as $l) {
                $amt = (float)($l['Amount'] ?? 0);
                if ($amt <= 0)                throw new Exception('Line amount must be greater than zero.');
                if (empty($l['LedgerUID']))   throw new Exception('All lines must have a ledger account.');
                if (!in_array($l['Type'] ?? '', ['Debit', 'Credit'], true)) throw new Exception('Invalid line type.');
                if ($l['Type'] === 'Debit')  $totalDr += $amt;
                else                          $totalCr += $amt;
            }
            if (abs($totalDr - $totalCr) > 0.01) throw new Exception('Journal is not balanced. Debit total must equal credit total.');

            $orgUID  = (int)$this->pageData['JwtData']->Org->OrgUID;
            $userUID = (int)$this->pageData['JwtData']->User->UserUID;
            $this->load->model('dbwrite_model');

            if ($recurUID > 0) {
                // Update header
                $this->dbwrite_model->updateData('Accounting', 'RecurringJournals', [
                    'Title'     => $title,
                    'Narration' => $narration,
                    'Frequency' => $frequency,
                    'StartDate' => $startDate,
                    'EndDate'   => $endDate ?: null,
                    'UpdatedBy' => $userUID,
                    'UpdatedOn' => date('Y-m-d H:i:s'),
                ], ['RecurUID' => $recurUID, 'OrgUID' => $orgUID]);

                // Hard-delete old lines and re-insert
                $WriteDb = $this->load->database('WriteDB', TRUE);
                $WriteDb->where('RecurUID', $recurUID)->where('OrgUID', $orgUID)
                        ->delete('Accounting.RecurringJournalLines');
            } else {
                // Insert header
                $insertResult = $this->dbwrite_model->insertData('Accounting', 'RecurringJournals', [
                    'OrgUID'      => $orgUID,
                    'Title'       => $title,
                    'Narration'   => $narration,
                    'Frequency'   => $frequency,
                    'StartDate'   => $startDate,
                    'EndDate'     => $endDate ?: null,
                    'NextRunDate' => $startDate,
                    'IsActive'    => 1,
                    'CreatedBy'   => $userUID,
                    'CreatedOn'   => date('Y-m-d H:i:s'),
                ]);
                if ($insertResult->Error) throw new Exception('Failed to save recurring journal.');
                $recurUID = (int)$insertResult->ID;
                if (!$recurUID) throw new Exception('Failed to save recurring journal.');
            }

            foreach ($lines as $i => $l) {
                $this->dbwrite_model->insertData('Accounting', 'RecurringJournalLines', [
                    'RecurUID'        => $recurUID,
                    'OrgUID'          => $orgUID,
                    'LedgerUID'       => (int)$l['LedgerUID'],
                    'TransactionType' => $l['Type'],
                    'Amount'          => (float)$l['Amount'],
                    'Particulars'     => trim($l['Particulars'] ?? ''),
                    'SortOrder'       => $i,
                ]);
            }

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = $recurUID > 0 ? 'Recurring journal updated.' : 'Recurring journal created.';
            $this->EndReturnData->RecurUID= $recurUID;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Recurring Journals — POST run one journal now ─────────────────────────
    public function postRecurringJournal(): void {
        $this->EndReturnData = new stdClass();
        try {
            $recurUID = (int)$this->input->post('RecurUID');
            if (!$recurUID) throw new Exception('Invalid recurring journal.');

            $journal = $this->accountledger_model->getRecurringJournalById($recurUID);
            if (!$journal || (int)$journal->IsDeleted === 1) throw new Exception('Recurring journal not found.');
            if (!(int)$journal->IsActive)                    throw new Exception('This recurring journal is paused.');
            if (empty($journal->Lines))                      throw new Exception('Recurring journal has no lines configured.');

            $lines = [];
            $dr = $cr = 0.0;
            foreach ($journal->Lines as $l) {
                $amt = (float)$l->Amount;
                $lines[] = [
                    'LedgerUID'   => (int)$l->LedgerUID,
                    'Type'        => $l->TransactionType,
                    'Amount'      => $amt,
                    'Particulars' => $l->Particulars,
                ];
                if ($l->TransactionType === 'Debit') $dr += $amt;
                else                                  $cr += $amt;
            }
            if (abs($dr - $cr) > 0.01) throw new Exception('Recurring journal lines are not balanced.');

            $today   = date('Y-m-d');
            $fy      = (int)date('Y');
            $userUID = (int)$this->pageData['JwtData']->User->UserUID;
            $orgUID  = (int)$this->pageData['JwtData']->Org->OrgUID;

            $this->load->library('accountledger');
            $this->load->model('dbwrite_model');

            $jUID = $this->accountledger->postManualJournal(
                $today, $fy, $journal->Narration, $lines, $userUID, 'Recurring'
            );

            $newNext = $this->_advanceNextRunDate($journal->NextRunDate, $journal->Frequency);
            $ended   = $journal->EndDate && $newNext > $journal->EndDate;

            $this->dbwrite_model->updateData('Accounting', 'RecurringJournals', [
                'LastRunDate' => $today,
                'NextRunDate' => $newNext,
                'TotalRuns'   => (int)$journal->TotalRuns + 1,
                'IsActive'    => $ended ? 0 : (int)$journal->IsActive,
                'UpdatedBy'   => $userUID,
                'UpdatedOn'   => date('Y-m-d H:i:s'),
            ], ['RecurUID' => $recurUID, 'OrgUID' => $orgUID]);

            $this->EndReturnData->Error       = FALSE;
            $this->EndReturnData->Message     = 'Journal posted successfully.';
            $this->EndReturnData->JournalUID  = $jUID;
            $this->EndReturnData->NextRunDate = $newNext;
            $this->EndReturnData->Ended       = $ended;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Recurring Journals — POST run all due journals ────────────────────────
    public function postAllDueJournals(): void {
        $this->EndReturnData = new stdClass();
        try {
            $dueList = $this->accountledger_model->getDueRecurringJournals();
            if (empty($dueList)) {
                $this->EndReturnData->Error   = FALSE;
                $this->EndReturnData->Message = 'No journals are due.';
                $this->EndReturnData->Posted  = 0;
                $this->globalservice->sendJsonResponse($this->EndReturnData);
                return;
            }

            $this->load->library('accountledger');
            $this->load->model('dbwrite_model');

            $today   = date('Y-m-d');
            $fy      = (int)date('Y');
            $userUID = (int)$this->pageData['JwtData']->User->UserUID;
            $orgUID  = (int)$this->pageData['JwtData']->Org->OrgUID;
            $posted  = 0;
            $errors  = [];

            foreach ($dueList as $journal) {
                try {
                    $fullJournal = $this->accountledger_model->getRecurringJournalById((int)$journal->RecurUID);
                    if (!$fullJournal || empty($fullJournal->Lines)) continue;

                    $lines = [];
                    foreach ($fullJournal->Lines as $l) {
                        $lines[] = [
                            'LedgerUID'   => (int)$l->LedgerUID,
                            'Type'        => $l->TransactionType,
                            'Amount'      => (float)$l->Amount,
                            'Particulars' => $l->Particulars,
                        ];
                    }

                    $this->accountledger->postManualJournal(
                        $today, $fy, $fullJournal->Narration, $lines, $userUID, 'Recurring'
                    );

                    $newNext = $this->_advanceNextRunDate($fullJournal->NextRunDate, $fullJournal->Frequency);
                    $ended   = $fullJournal->EndDate && $newNext > $fullJournal->EndDate;
                    $this->dbwrite_model->updateData('Accounting', 'RecurringJournals', [
                        'LastRunDate' => $today,
                        'NextRunDate' => $newNext,
                        'TotalRuns'   => (int)$fullJournal->TotalRuns + 1,
                        'IsActive'    => $ended ? 0 : 1,
                        'UpdatedBy'   => $userUID,
                        'UpdatedOn'   => date('Y-m-d H:i:s'),
                    ], ['RecurUID' => (int)$fullJournal->RecurUID, 'OrgUID' => $orgUID]);

                    $posted++;
                } catch (Exception $ex) {
                    $errors[] = $journal->Title . ': ' . $ex->getMessage();
                }
            }

            $this->EndReturnData->Error   = !empty($errors) && $posted === 0;
            $this->EndReturnData->Message = $posted . ' journal(s) posted.' . (!empty($errors) ? ' Errors: ' . implode('; ', $errors) : '');
            $this->EndReturnData->Posted  = $posted;
            $this->EndReturnData->Errors  = $errors;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Recurring Journals — POST toggle pause/resume ─────────────────────────
    public function toggleRecurringStatus(): void {
        $this->EndReturnData = new stdClass();
        try {
            $recurUID = (int)$this->input->post('RecurUID');
            if (!$recurUID) throw new Exception('Invalid recurring journal.');

            $journal = $this->accountledger_model->getRecurringJournalById($recurUID);
            if (!$journal || (int)$journal->IsDeleted === 1) throw new Exception('Recurring journal not found.');

            $orgUID  = (int)$this->pageData['JwtData']->Org->OrgUID;
            $userUID = (int)$this->pageData['JwtData']->User->UserUID;
            $newStatus = (int)$journal->IsActive === 1 ? 0 : 1;

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->updateData('Accounting', 'RecurringJournals', [
                'IsActive'  => $newStatus,
                'UpdatedBy' => $userUID,
                'UpdatedOn' => date('Y-m-d H:i:s'),
            ], ['RecurUID' => $recurUID, 'OrgUID' => $orgUID]);

            $this->EndReturnData->Error     = FALSE;
            $this->EndReturnData->Message   = $newStatus ? 'Recurring journal resumed.' : 'Recurring journal paused.';
            $this->EndReturnData->IsActive  = $newStatus;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Recurring Journals — POST soft-delete ────────────────────────────────
    public function deleteRecurringJournal(): void {
        $this->EndReturnData = new stdClass();
        try {
            $recurUID = (int)$this->input->post('RecurUID');
            if (!$recurUID) throw new Exception('Invalid recurring journal.');

            $orgUID  = (int)$this->pageData['JwtData']->Org->OrgUID;
            $userUID = (int)$this->pageData['JwtData']->User->UserUID;

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->updateData('Accounting', 'RecurringJournals', [
                'IsDeleted' => 1,
                'IsActive'  => 0,
                'UpdatedBy' => $userUID,
                'UpdatedOn' => date('Y-m-d H:i:s'),
            ], ['RecurUID' => $recurUID, 'OrgUID' => $orgUID]);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Recurring journal deleted.';
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    /** Advance a date by one frequency period */
    private function _advanceNextRunDate(string $date, string $frequency): string {
        $map = [
            'Daily'     => '+1 day',
            'Weekly'    => '+7 days',
            'Monthly'   => '+1 month',
            'Quarterly' => '+3 months',
            'Yearly'    => '+1 year',
        ];
        $offset = $map[$frequency] ?? '+1 month';
        return date('Y-m-d', strtotime($offset, strtotime($date)));
    }

    /** Build recurring journals list HTML */
    private function _buildRecurListHtml(array $rows, int $offset): string {
        return $this->load->view('accounting/recurring_journals/list', [
            'DataLists'    => $rows,
            'SerialNumber' => $offset,
            'JwtData'      => $this->pageData['JwtData'],
        ], TRUE);
    }

    /** Calculate net balance after applying debit/credit amounts */
    private function _calcBalance(float $balance, string $balType, float $debit, float $credit): array {
        $net = $balance + ($balType === 'Debit' ? $debit - $credit : $credit - $debit);
        if ($net >= 0) return [$net, $balType];
        // Balance flipped sign
        $flipped = ['Debit' => 'Credit', 'Credit' => 'Debit'];
        return [abs($net), $flipped[$balType]];
    }

    // ── Period Lock — page ────────────────────────────────────────────────────
    public function periodlock(): void {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $this->pageData['CurrentLock'] = $this->accountledger_model->getPeriodLock();
            $this->load->view('accounting/period_lock/view', $this->pageData);
        } catch (Exception $e) {
            log_message('error', 'periodlock: ' . $e->getMessage());
            redirect('dashboard', 'refresh');
        }
    }

    // ── Period Lock — POST save / advance lock date ───────────────────────────
    public function savePeriodLock(): void {
        $this->EndReturnData = new stdClass();
        try {
            $lockDate = $this->input->post('LockDate') ?: '';
            if (!$lockDate || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $lockDate)) {
                throw new Exception('A valid lock date is required.');
            }

            $orgUID  = (int)$this->pageData['JwtData']->Org->OrgUID;
            $userUID = (int)$this->pageData['JwtData']->User->UserUID;
            $now     = date('Y-m-d H:i:s');

            $this->load->model('dbwrite_model');
            $existing = $this->accountledger_model->getPeriodLock();

            if ($existing) {
                $this->dbwrite_model->updateData('Accounting', 'PeriodLock', [
                    'LockedUpTo' => $lockDate,
                    'UpdatedBy'  => $userUID,
                    'UpdatedOn'  => $now,
                ], ['OrgUID' => $orgUID]);
            } else {
                $this->dbwrite_model->insertData('Accounting', 'PeriodLock', [
                    'OrgUID'     => $orgUID,
                    'LockedUpTo' => $lockDate,
                    'LockedBy'   => $userUID,
                    'LockedOn'   => $now,
                ]);
            }

            $display = date($this->pageData['JwtData']->GenSettings->ListDateFormat ?? 'd M Y',
                strtotime($lockDate));
            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Message    = "Books locked up to {$display}.";
            $this->EndReturnData->LockedUpTo = $lockDate;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Period Lock — POST remove lock ────────────────────────────────────────
    public function removePeriodLock(): void {
        $this->EndReturnData = new stdClass();
        try {
            $orgUID  = (int)$this->pageData['JwtData']->Org->OrgUID;
            $WriteDb = $this->load->database('WriteDB', TRUE);
            $WriteDb->where('OrgUID', $orgUID)->delete('Accounting.PeriodLock');

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Period lock removed. All periods are now open for posting.';
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Private: build list HTML ──────────────────────────────────────────────
    private function _buildListHtml(array $rows, int $offset): string {
        $JwtData = $this->pageData['JwtData'];
        return $this->load->view('accounting/chart_of_accounts/list',
            ['DataLists' => $rows, 'SerialNumber' => $offset, 'JwtData' => $JwtData], TRUE);
    }
}
