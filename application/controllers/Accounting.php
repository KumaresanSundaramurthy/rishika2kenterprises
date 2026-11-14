<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Accounting pages. Data comes from the services (api/accounting); this controller renders the same views and
 * returns the same JSON fields the accounting JS reads.
 *
 * @property object $globalservice
 * @property object $input
 */
class Accounting extends MY_Controller {

    public  $pageData  = [];
    /** @var object|null */
    protected $EndReturnData;

    public function __construct() {
        parent::__construct();
    }

    // ── Services calls ────────────────────────────────────────────────────────

    /**
     * GET api/accounting/{endpoint} for this organisation; throws the service's message on an error.
     * @param string $endpoint
     * @param array  $params
     * @returns object
     */
    private function _get(string $endpoint, array $params = []): object {
        $params = array_filter($params, fn($v) => $v !== null && $v !== '');
        $resp   = $this->_servicesGet('api/accounting/' . ltrim($endpoint, '/'), ['orgId' => $this->_orgUID()] + $params);
        return $this->_checked($resp);
    }

    /**
     * POST api/accounting/{endpoint} as the logged-in user; throws the service's message on an error.
     * @param string $endpoint
     * @param array  $payload
     * @returns object
     */
    private function _post(string $endpoint, array $payload = []): object {
        $token = $_COOKIE[getenv('JWT_COOKIE_NAME') ?: ''] ?? '';
        $resp  = services_post(
            'api/accounting/' . ltrim($endpoint, '/'),
            ['orgId' => $this->_orgUID(), 'userId' => $this->_userUID()] + $payload,
            ['Authorization: Bearer ' . $token],
            20
        );
        return $this->_checked($resp);
    }

    /**
     * @param object|null $resp
     * @returns object
     */
    private function _checked($resp): object {
        if (!is_object($resp)) throw new Exception('Accounting service is unavailable. Please try again.');
        if (!empty($resp->Error)) throw new Exception($resp->Message ?? 'Request failed.');
        return $resp;
    }

    /** The list Filter the JS posted (array or JSON) as JSON for the services */
    private function _filterJson(): string {
        $raw = $this->input->post('Filter') ?: [];
        if (is_string($raw)) $raw = json_decode($raw, true) ?: [];
        return json_encode((object)$raw);
    }

    /** Decoded JSON objects as PHP arrays (for views that read arrays) */
    private function _arr($value): array {
        return json_decode(json_encode($value), true) ?: [];
    }

    private function _send(): void {
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Chart of Accounts ─────────────────────────────────────────────────────

    public function chartofaccounts(): void {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $r = $this->_get('getChartOfAccountsPageData', ['RowLimit' => $this->_rowLimit()]);
            $this->pageData['ParentLedgers'] = (array)$r->ParentLedgers;
            $this->load->view('accounting/chart_of_accounts/view', $this->pageData);
        } catch (Exception $e) {
            notifyError('Accounting::chartofaccounts', $e);
            redirect('dashboard', 'refresh');
        }
    }

    /* Pure data proxy — JS builds rows and pagination client-side */
    public function getChartOfAccountsPage($pageNo = 1): void {
        $this->EndReturnData = new stdClass();
        try {
            $r = $this->_get('getChartOfAccountsPage/' . max(1, (int)$pageNo),
                             ['Filter' => $this->_filterJson(), 'RowLimit' => $this->_rowLimit()]);
            $this->EndReturnData = $r;
        } catch (Exception $e) {
            notifyError('Accounting::getChartOfAccountsPage', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->_send();
    }

    public function saveLedger(): void {
        $this->_chartWrite('saveLedger', ['LedgerUID', 'LedgerCode', 'LedgerName', 'LedgerType', 'ParentLedgerUID', 'OpeningBalance', 'OpeningBalanceType']);
    }

    public function toggleLedgerStatus(): void {
        $this->_chartWrite('toggleLedgerStatus', ['LedgerUID', 'IsActive']);
    }

    public function deleteLedger(): void {
        $this->_chartWrite('deleteLedger', ['LedgerUID']);
    }

    /* Ledger writes return {Error, Message} only — JS reloads the list */
    private function _chartWrite(string $endpoint, array $fields): void {
        $this->EndReturnData = new stdClass();
        try {
            $payload = [];
            foreach ($fields as $f) $payload[$f] = $this->input->post($f);
            $r = $this->_post($endpoint, $payload);
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = $r->Message ?? '';
        } catch (Exception $e) {
            notifyError('Accounting::' . $endpoint, $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->_send();
    }

    // ── Trial Balance ─────────────────────────────────────────────────────────

    public function trialbalance() {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $r = $this->_get('getTrialBalancePageData');
            $this->pageData['FinancialYears'] = (array)$r->FinancialYears;
            $this->pageData['DefaultFY']      = (int)$r->DefaultFY;
            $this->load->view('accounting/trial_balance/view', $this->pageData);
        } catch (Exception $e) {
            notifyError('Accounting::trialbalance', $e);
            redirect('dashboard', 'refresh');
        }
    }

    public function getTrialBalanceAjax() {
        $this->EndReturnData = new stdClass();
        try {
            $r = $this->_get('getTrialBalanceAjax', ['FinancialYear' => $this->input->post('FinancialYear')]);
            $this->EndReturnData->Error       = FALSE;
            $this->EndReturnData->Html        = $this->load->view('accounting/trial_balance/table', [
                'Rows' => (array)$r->Rows, 'JwtData' => $this->pageData['JwtData'],
                'GrandDebit' => $r->GrandDebit, 'GrandCredit' => $r->GrandCredit,
                'TotalObDr' => $r->TotalObDr, 'TotalObCr' => $r->TotalObCr, 'FinancialYear' => $r->FinancialYear,
            ], TRUE);
            $this->EndReturnData->GrandDebit  = $r->GrandDebit;
            $this->EndReturnData->GrandCredit = $r->GrandCredit;
            $this->EndReturnData->IsBalanced  = $r->IsBalanced;
            $this->EndReturnData->RowCount    = $r->RowCount;
        } catch (Exception $e) {
            notifyError('Accounting::getTrialBalanceAjax', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->_send();
    }

    // ── Journal Listing ───────────────────────────────────────────────────────

    public function journallist() {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $r = $this->_get('getJournalListPageData', ['RowLimit' => $this->_rowLimit()]);
            $this->pageData['Stats']         = $r->Stats;
            $this->pageData['ModRowData']    = $this->_buildJournalListHtml((array)$r->Data, 0);
            $this->pageData['ModPagination'] = $this->globalservice->buildPagePaginationHtml('/accounting/getJournalListPage', (int)$r->TotalCount, 1, $this->_rowLimit());
            $this->pageData['TotalCount']    = (int)$r->TotalCount;
            $this->load->view('accounting/journal_list/view', $this->pageData);
        } catch (Exception $e) {
            notifyError('Accounting::journallist', $e);
            redirect('dashboard', 'refresh');
        }
    }

    public function getJournalListPage($pageNo = 1) {
        $this->EndReturnData = new stdClass();
        try {
            $pageNo = max(1, (int)$pageNo);
            $limit  = $this->_rowLimit();
            $r = $this->_get('getJournalListPage/' . $pageNo, ['Filter' => $this->_filterJson(), 'RowLimit' => $limit]);
            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->RecordHtmlData = $this->_buildJournalListHtml((array)$r->Data, ($pageNo - 1) * $limit);
            $this->EndReturnData->Pagination     = $this->globalservice->buildPagePaginationHtml('/accounting/getJournalListPage', (int)$r->TotalCount, $pageNo, $limit);
            $this->EndReturnData->Stats          = $r->Stats;
        } catch (Exception $e) {
            notifyError('Accounting::getJournalListPage', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->_send();
    }

    public function getJournalDetail() {
        $this->EndReturnData = new stdClass();
        try {
            $journalUID = (int)$this->input->post('JournalUID');
            if (!$journalUID) throw new Exception('Invalid journal.');
            $r = $this->_get('getJournalDetail', ['JournalUID' => $journalUID]);
            $this->EndReturnData->Error     = FALSE;
            $this->EndReturnData->Html      = $this->load->view('accounting/journal_list/detail', ['Journal' => $r->Journal, 'JwtData' => $this->pageData['JwtData']], TRUE);
            $this->EndReturnData->JournalNo = $r->JournalNo;
        } catch (Exception $e) {
            notifyError('Accounting::getJournalDetail', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->_send();
    }

    private function _buildJournalListHtml(array $rows, int $offset): string {
        return $this->load->view('accounting/journal_list/list',
            ['DataLists' => $rows, 'SerialNumber' => $offset, 'JwtData' => $this->pageData['JwtData']], TRUE);
    }

    // ── General Ledger ────────────────────────────────────────────────────────

    public function generalledger() {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $this->pageData['AllLedgers'] = (array)$this->_get('getGeneralLedgerPageData')->AllLedgers;
            $this->load->view('accounting/general_ledger/view', $this->pageData);
        } catch (Exception $e) {
            notifyError('Accounting::generalledger', $e);
            redirect('dashboard', 'refresh');
        }
    }

    public function getLedgerStatementAjax() {
        $this->EndReturnData = new stdClass();
        try {
            $r = $this->_get('getLedgerStatementAjax', [
                'LedgerUID' => (int)$this->input->post('LedgerUID'),
                'DateFrom'  => $this->input->post('DateFrom'),
                'DateTo'    => $this->input->post('DateTo'),
            ]);
            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->Html           = $this->load->view('accounting/general_ledger/statement', [
                'Lines' => (array)$r->Lines, 'Ledger' => $r->Ledger, 'JwtData' => $this->pageData['JwtData'],
                'OpeningBalance' => $r->OpeningBalance, 'OpeningBalanceType' => $r->OpeningType,
                'ClosingBalance' => $r->ClosingBalance, 'ClosingBalanceType' => $r->ClosingType,
                'TotalDebit' => $r->TotalDebit, 'TotalCredit' => $r->TotalCredit,
                'DateFrom' => $r->DateFrom, 'DateTo' => $r->DateTo,
            ], TRUE);
            $this->EndReturnData->LedgerName     = $r->LedgerName;
            $this->EndReturnData->OpeningBalance = $r->OpeningBalance;
            $this->EndReturnData->OpeningType    = $r->OpeningType;
            $this->EndReturnData->ClosingBalance = $r->ClosingBalance;
            $this->EndReturnData->ClosingType    = $r->ClosingType;
            $this->EndReturnData->TotalDebit     = $r->TotalDebit;
            $this->EndReturnData->TotalCredit    = $r->TotalCredit;
        } catch (Exception $e) {
            notifyError('Accounting::getLedgerStatementAjax', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->_send();
    }

    // ── Manual Journal ────────────────────────────────────────────────────────

    public function saveManualJournal(): void {
        $this->EndReturnData = new stdClass();
        try {
            $r = $this->_post('saveManualJournal', [
                'JournalDate' => trim($this->input->post('JournalDate') ?? ''),
                'Narration'   => trim($this->input->post('Narration') ?? ''),
                'Lines'       => $this->input->post('Lines') ?: [],
            ]);
            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Message    = $r->Message;
            $this->EndReturnData->JournalNo  = $r->JournalNo;
            $this->EndReturnData->JournalUID = $r->JournalUID;
        } catch (Exception $e) {
            notifyError('Accounting::saveManualJournal', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->_send();
    }

    public function deleteManualJournal(): void {
        $this->_simplePost('deleteManualJournal', ['JournalUID' => (int)$this->input->post('JournalUID')]);
    }

    /** A change whose answer is just Error / Message (and any extra fields the service returns) */
    private function _simplePost(string $endpoint, array $payload, array $extra = []): void {
        $this->EndReturnData = new stdClass();
        try {
            $r = $this->_post($endpoint, $payload);
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = $r->Message ?? '';
            foreach ($extra as $field) {
                if (property_exists($r, $field)) $this->EndReturnData->{$field} = $r->{$field};
            }
        } catch (Exception $e) {
            notifyError('Accounting::' . $endpoint, $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->_send();
    }

    // ── Bank Reconciliation ───────────────────────────────────────────────────

    public function bankreconciliation(): void {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $this->pageData['BankLedgers'] = (array)$this->_get('getBankReconPageData')->BankLedgers;
            $this->load->view('accounting/bank_reconciliation/view', $this->pageData);
        } catch (Exception $e) {
            notifyError('Accounting::bankreconciliation', $e);
            redirect('dashboard', 'refresh');
        }
    }

    public function getBankReconAjax(): void {
        $this->EndReturnData = new stdClass();
        try {
            $r = $this->_get('getBankReconAjax', [
                'LedgerUID' => (int)$this->input->post('LedgerUID'),
                'DateFrom'  => $this->input->post('DateFrom'),
                'DateTo'    => $this->input->post('DateTo'),
            ]);
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Html  = $this->load->view('accounting/bank_reconciliation/entries',
                ['Entries' => (array)$r->Entries, 'JwtData' => $this->pageData['JwtData']], TRUE);
            foreach (['LedgerName', 'OpeningBalance', 'OpeningType', 'TotalDr', 'TotalCr', 'BookBalance', 'BookType', 'ClearedBalance', 'ClearedType', 'EntryCount'] as $f) {
                $this->EndReturnData->{$f} = $r->{$f};
            }
        } catch (Exception $e) {
            notifyError('Accounting::getBankReconAjax', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->_send();
    }

    public function saveBankRecon(): void {
        $this->_simplePost('saveBankRecon', [
            'ClearedUIDs'   => array_values(array_filter(array_map('intval', (array)($this->input->post('ClearedUIDs') ?: [])))),
            'UnclearedUIDs' => array_values(array_filter(array_map('intval', (array)($this->input->post('UnclearedUIDs') ?: [])))),
        ], ['ClearedCount', 'UnclearedCount']);
    }

    // ── Ledger search for the journal forms (GET) ─────────────────────────────

    public function getLedgersForJournal(): void {
        $this->EndReturnData = new stdClass();
        try {
            $r = $this->_get('getLedgersForJournal', ['q' => trim($this->input->get('q') ?: '')]);
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->results = $r->results;
        } catch (Exception $e) {
            notifyError('Accounting::getLedgersForJournal', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            $this->EndReturnData->results = [];
        }
        $this->_send();
    }

    // ── Profit & Loss ─────────────────────────────────────────────────────────

    public function profitloss(): void {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        $this->load->view('accounting/profit_loss/view', $this->pageData);
    }

    public function getPandLAjax(): void {
        $this->EndReturnData = new stdClass();
        try {
            $r = $this->_get('getPandLAjax', ['DateFrom' => $this->input->post('DateFrom'), 'DateTo' => $this->input->post('DateTo')]);
            $this->EndReturnData->Error        = FALSE;
            $this->EndReturnData->Html         = $this->load->view('accounting/profit_loss/statement', [
                'Income' => (array)$r->Income, 'Expense' => (array)$r->Expense,
                'TotalIncome' => $r->TotalIncome, 'TotalExpense' => $r->TotalExpense, 'NetProfit' => $r->NetProfit,
                'DateFrom' => $r->DateFrom, 'DateTo' => $r->DateTo, 'JwtData' => $this->pageData['JwtData'],
            ], TRUE);
            $this->EndReturnData->TotalIncome  = $r->TotalIncome;
            $this->EndReturnData->TotalExpense = $r->TotalExpense;
            $this->EndReturnData->NetProfit    = $r->NetProfit;
        } catch (Exception $e) {
            notifyError('Accounting::getPandLAjax', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->_send();
    }

    // ── Balance Sheet ─────────────────────────────────────────────────────────

    public function balancesheet(): void {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        $this->load->view('accounting/balance_sheet/view', $this->pageData);
    }

    public function getBalanceSheetAjax(): void {
        $this->EndReturnData = new stdClass();
        try {
            $r = $this->_get('getBalanceSheetAjax', ['AsOfDate' => $this->input->post('AsOfDate'), 'PnlFrom' => $this->input->post('PnlFrom')]);
            // The view reads each group as an array: ['label', 'items' => [row objects], 'total']
            $groups = function ($src): array {
                $out = [];
                foreach ((array)$src as $type => $g) {
                    $out[$type] = ['label' => $g->label, 'items' => (array)$g->items, 'total' => (float)$g->total];
                }
                return $out;
            };
            $this->EndReturnData->Error            = FALSE;
            $this->EndReturnData->Html             = $this->load->view('accounting/balance_sheet/statement', [
                'AssetGroups' => $groups($r->AssetGroups), 'LiabGroups' => $groups($r->LiabGroups),
                'TotalAssets' => $r->TotalAssets, 'TotalLiabilities' => $r->TotalLiabilities, 'NetProfit' => $r->NetProfit,
                'AsOfDate' => $r->AsOfDate, 'PnlFrom' => $r->PnlFrom, 'JwtData' => $this->pageData['JwtData'],
            ], TRUE);
            $this->EndReturnData->TotalAssets      = $r->TotalAssets;
            $this->EndReturnData->TotalLiabilities = $r->TotalLiabilities;
            $this->EndReturnData->NetProfit        = $r->NetProfit;
            $this->EndReturnData->IsBalanced       = $r->IsBalanced;
        } catch (Exception $e) {
            notifyError('Accounting::getBalanceSheetAjax', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->_send();
    }

    // ── Recurring Journals ────────────────────────────────────────────────────

    public function recurringjournals(): void {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $r = $this->_get('getRecurringJournalsPageData', ['RowLimit' => $this->_rowLimit()]);
            $this->pageData['Stats']         = $r->Stats;
            $this->pageData['ModRowData']    = $this->_buildRecurListHtml((array)$r->Data, 0);
            $this->pageData['ModPagination'] = $this->globalservice->buildPagePaginationHtml('/accounting/getRecurringJournalsPage', (int)$r->TotalCount, 1, $this->_rowLimit());
            $this->pageData['TotalCount']    = (int)$r->TotalCount;
            $this->load->view('accounting/recurring_journals/view', $this->pageData);
        } catch (Exception $e) {
            notifyError('Accounting::recurringjournals', $e);
            redirect('dashboard', 'refresh');
        }
    }

    public function getRecurringJournalsPage(int $pageNo = 1): void {
        $this->EndReturnData = new stdClass();
        try {
            $pageNo = max(1, $pageNo);
            $limit  = $this->_rowLimit();
            $r = $this->_get('getRecurringJournalsPage/' . $pageNo, ['Filter' => $this->_filterJson(), 'RowLimit' => $limit]);
            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->RecordHtmlData = $this->_buildRecurListHtml((array)$r->Data, ($pageNo - 1) * $limit);
            $this->EndReturnData->Pagination     = $this->globalservice->buildPagePaginationHtml('/accounting/getRecurringJournalsPage', (int)$r->TotalCount, $pageNo, $limit);
            $this->EndReturnData->TotalCount     = (int)$r->TotalCount;
            $this->EndReturnData->Stats          = $r->Stats;
        } catch (Exception $e) {
            notifyError('Accounting::getRecurringJournalsPage', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->_send();
    }

    public function getRecurringJournalAjax(): void {
        $this->EndReturnData = new stdClass();
        try {
            $r = $this->_get('getRecurringJournalAjax', ['RecurUID' => (int)$this->input->get('RecurUID')]);
            $this->EndReturnData->Error = FALSE;
            foreach (['RecurUID', 'Title', 'Narration', 'Frequency', 'StartDate', 'EndDate', 'Lines'] as $f) {
                $this->EndReturnData->{$f} = $r->{$f};
            }
        } catch (Exception $e) {
            notifyError('Accounting::getRecurringJournalAjax', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->_send();
    }

    public function saveRecurringJournal(): void {
        $payload = ['RecurUID' => (int)$this->input->post('RecurUID')];
        foreach (['Title', 'Narration', 'Frequency', 'StartDate', 'EndDate'] as $f) {
            $payload[$f] = trim((string)($this->input->post($f) ?? ''));
        }
        $payload['Lines'] = $this->input->post('Lines') ?: '[]';
        $this->_simplePost('saveRecurringJournal', $payload, ['RecurUID']);
    }

    public function postRecurringJournal(): void {
        $this->_simplePost('postRecurringJournal', ['RecurUID' => (int)$this->input->post('RecurUID')], ['JournalUID', 'NextRunDate', 'Ended']);
    }

    public function postAllDueJournals(): void {
        $this->EndReturnData = new stdClass();
        try {
            $r = $this->_post('postAllDueJournals');
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = $r->Message;
            $this->EndReturnData->Posted  = $r->Posted;
            $this->EndReturnData->Errors  = $r->Errors ?? [];
        } catch (Exception $e) {
            notifyError('Accounting::postAllDueJournals', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->_send();
    }

    public function toggleRecurringStatus(): void {
        $this->_simplePost('toggleRecurringStatus', ['RecurUID' => (int)$this->input->post('RecurUID')], ['IsActive']);
    }

    public function deleteRecurringJournal(): void {
        $this->_simplePost('deleteRecurringJournal', ['RecurUID' => (int)$this->input->post('RecurUID')]);
    }

    private function _buildRecurListHtml(array $rows, int $offset): string {
        return $this->load->view('accounting/recurring_journals/list', [
            'DataLists' => $rows, 'SerialNumber' => $offset, 'JwtData' => $this->pageData['JwtData'],
        ], TRUE);
    }

    // ── Period Lock ───────────────────────────────────────────────────────────

    public function periodlock(): void {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $this->pageData['CurrentLock'] = $this->_get('getPeriodLock')->CurrentLock ?? null;
            $this->load->view('accounting/period_lock/view', $this->pageData);
        } catch (Exception $e) {
            notifyError('Accounting::periodlock', $e);
            redirect('dashboard', 'refresh');
        }
    }

    public function savePeriodLock(): void {
        $this->_simplePost('savePeriodLock', ['LockDate' => $this->input->post('LockDate') ?: ''], ['LockedUpTo']);
    }

    public function removePeriodLock(): void {
        $this->_simplePost('removePeriodLock', []);
    }

    // ── Comparative P&L ───────────────────────────────────────────────────────

    public function comparativepnl(): void {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        $this->load->view('accounting/comparative_pnl/view', $this->pageData);
    }

    public function getComparativePnLAjax(): void {
        $this->EndReturnData = new stdClass();
        try {
            $params = [];
            foreach (['P1From', 'P1To', 'P2From', 'P2To', 'P1Label', 'P2Label'] as $f) $params[$f] = trim((string)($this->input->post($f) ?? ''));
            $r      = $this->_get('getComparativePnLAjax', $params);
            $totals = $this->_arr($r->Totals);
            $this->EndReturnData->Error  = FALSE;
            $this->EndReturnData->Html   = $this->load->view('accounting/comparative_pnl/statement', [
                'Income' => (array)$r->Income, 'Expense' => (array)$r->Expense, 'Totals' => $totals,
                'P1From' => $r->P1From, 'P1To' => $r->P1To, 'P2From' => $r->P2From, 'P2To' => $r->P2To,
                'P1Label' => $r->P1Label, 'P2Label' => $r->P2Label, 'JwtData' => $this->pageData['JwtData'],
            ], TRUE);
            $this->EndReturnData->Totals = $totals;
        } catch (Exception $e) {
            notifyError('Accounting::getComparativePnLAjax', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->_send();
    }

    // ── Financial Ratios ──────────────────────────────────────────────────────

    public function ratios(): void {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        $this->load->view('accounting/ratios/view', $this->pageData);
    }

    public function getRatiosAjax(): void {
        $this->EndReturnData = new stdClass();
        try {
            $params = [];
            foreach (['AsOfDate', 'PnlFrom', 'PnlTo'] as $f) $params[$f] = trim((string)($this->input->post($f) ?? ''));
            $r = $this->_get('getRatiosAjax', $params);
            $this->EndReturnData->Error  = FALSE;
            $this->EndReturnData->Html   = $this->load->view('accounting/ratios/dashboard', [
                'Ratios' => $r->Ratios, 'AsOfDate' => $r->AsOfDate, 'PnlFrom' => $r->PnlFrom, 'PnlTo' => $r->PnlTo, 'JwtData' => $this->pageData['JwtData'],
            ], TRUE);
            $this->EndReturnData->Ratios = $r->Ratios;
        } catch (Exception $e) {
            notifyError('Accounting::getRatiosAjax', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->_send();
    }

    // ── Cash Flow ─────────────────────────────────────────────────────────────

    public function cashflow(): void {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        $this->load->view('accounting/cash_flow/view', $this->pageData);
    }

    public function getCashFlowAjax(): void {
        $this->EndReturnData = new stdClass();
        try {
            $r = $this->_get('getCashFlowAjax', ['DateFrom' => trim($this->input->post('DateFrom') ?? ''), 'DateTo' => trim($this->input->post('DateTo') ?? '')]);
            // The view sorts Inflows / Outflows with arsort(), so they must be arrays
            $accounts = [];
            foreach ((array)$r->Accounts as $a) {
                $a->Inflows  = (array)$a->Inflows;
                $a->Outflows = (array)$a->Outflows;
                $accounts[]  = $a;
            }
            $this->EndReturnData->Error           = FALSE;
            $this->EndReturnData->Html            = $this->load->view('accounting/cash_flow/statement', [
                'Accounts' => $accounts, 'GrandOpeningBal' => $r->GrandOpeningBal, 'GrandPeriodDr' => $r->GrandPeriodDr,
                'GrandPeriodCr' => $r->GrandPeriodCr, 'GrandClosingBal' => $r->GrandClosingBal,
                'DateFrom' => $r->DateFrom, 'DateTo' => $r->DateTo, 'JwtData' => $this->pageData['JwtData'],
            ], TRUE);
            $this->EndReturnData->GrandOpeningBal = $r->GrandOpeningBal;
            $this->EndReturnData->GrandPeriodDr   = $r->GrandPeriodDr;
            $this->EndReturnData->GrandPeriodCr   = $r->GrandPeriodCr;
            $this->EndReturnData->GrandClosingBal = $r->GrandClosingBal;
            $this->EndReturnData->NetChange       = $r->NetChange;
        } catch (Exception $e) {
            notifyError('Accounting::getCashFlowAjax', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->_send();
    }

    // ── Budget vs Actual ──────────────────────────────────────────────────────

    public function budgetvactual(): void {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        $this->load->view('accounting/budget_vs_actual/view', $this->pageData);
    }

    public function getBudgetVActualAjax(): void {
        $this->EndReturnData = new stdClass();
        try {
            $r = $this->_get('getBudgetVActualAjax', [
                'FY'       => $this->input->post('FY'),
                'DateFrom' => trim($this->input->post('DateFrom') ?? ''),
                'DateTo'   => trim($this->input->post('DateTo') ?? ''),
            ]);
            $totals = $this->_arr($r->Totals);
            $this->EndReturnData->Error  = FALSE;
            $this->EndReturnData->Html   = $this->load->view('accounting/budget_vs_actual/report', [
                'Income' => (array)$r->Income, 'Expense' => (array)$r->Expense, 'Totals' => $totals,
                'FY' => $r->FY, 'DateFrom' => $r->DateFrom, 'DateTo' => $r->DateTo, 'JwtData' => $this->pageData['JwtData'],
            ], TRUE);
            $this->EndReturnData->Totals = $totals;
        } catch (Exception $e) {
            notifyError('Accounting::getBudgetVActualAjax', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->_send();
    }

    public function saveBudgetAmount(): void {
        $this->_simplePost('saveBudgetAmount', [
            'LedgerUID' => (int)($this->input->post('LedgerUID') ?? 0),
            'FY'        => (int)($this->input->post('FY') ?? 0),
            'Amount'    => (float)($this->input->post('Amount') ?? 0),
        ], ['Amount']);
    }

    // ── Aged Receivables / Payables ───────────────────────────────────────────

    public function agedreceivables(): void {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        $this->load->view('accounting/aged_receivables/view', $this->pageData);
    }

    public function getAgedReceivablesAjax(): void {
        $this->_aged('getAgedReceivablesAjax', 'accounting/aged_receivables/statement');
    }

    public function agedpayables(): void {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        $this->load->view('accounting/aged_payables/view', $this->pageData);
    }

    public function getAgedPayablesAjax(): void {
        $this->_aged('getAgedPayablesAjax', 'accounting/aged_payables/statement');
    }

    private function _aged(string $endpoint, string $view): void {
        $this->EndReturnData = new stdClass();
        try {
            $r      = $this->_get($endpoint, ['AsOfDate' => trim($this->input->post('AsOfDate') ?? '')]);
            $totals = $this->_arr($r->Totals);
            $this->EndReturnData->Error  = FALSE;
            $this->EndReturnData->Html   = $this->load->view($view, [
                'Rows' => (array)$r->Rows, 'Totals' => $totals, 'AsOfDate' => $r->AsOfDate, 'JwtData' => $this->pageData['JwtData'],
            ], TRUE);
            $this->EndReturnData->Totals = $totals;
        } catch (Exception $e) {
            notifyError('Accounting::' . $endpoint, $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->_send();
    }

    // ── Day Book ──────────────────────────────────────────────────────────────

    public function daybook(): void {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $this->pageData['CashBankLedgers'] = (array)$this->_get('getDayBookPageData')->CashBankLedgers;
        } catch (Exception $e) {
            notifyError('Accounting::daybook', $e);
            $this->pageData['CashBankLedgers'] = [];
        }
        $this->load->view('accounting/day_book/view', $this->pageData);
    }

    public function getDayBookAjax(): void {
        $this->EndReturnData = new stdClass();
        try {
            $r = $this->_get('getDayBookAjax', [
                'DateFrom'     => trim($this->input->post('DateFrom') ?? ''),
                'DateTo'       => trim($this->input->post('DateTo') ?? ''),
                'CashBankOnly' => (int)($this->input->post('CashBankOnly') ?? 0),
                'LedgerUID'    => (int)($this->input->post('LedgerUID') ?? 0),
            ]);
            // The view reads days and journals as arrays (keyed by date / JournalUID) and entries as objects
            $days = [];
            foreach ((array)$r->Days as $d) {
                $journals = [];
                foreach ((array)$d->journals as $j) {
                    $journals[(int)$j->JournalUID] = [
                        'JournalNo' => $j->JournalNo, 'ReferenceType' => $j->ReferenceType, 'ReferenceNo' => $j->ReferenceNo,
                        'Narration' => $j->Narration, 'entries' => (array)$j->entries,
                    ];
                }
                $days[$d->date] = ['date' => $d->date, 'journals' => $journals, 'dailyDr' => (float)$d->dailyDr, 'dailyCr' => (float)$d->dailyCr];
            }
            $this->EndReturnData->Error    = FALSE;
            $this->EndReturnData->Html     = $this->load->view('accounting/day_book/entries', [
                'Days' => $days, 'GrandDr' => $r->GrandDr, 'GrandCr' => $r->GrandCr,
                'DateFrom' => $r->DateFrom, 'DateTo' => $r->DateTo, 'JwtData' => $this->pageData['JwtData'],
            ], TRUE);
            $this->EndReturnData->GrandDr  = $r->GrandDr;
            $this->EndReturnData->GrandCr  = $r->GrandCr;
            $this->EndReturnData->DayCount = $r->DayCount;
        } catch (Exception $e) {
            notifyError('Accounting::getDayBookAjax', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->_send();
    }

}
