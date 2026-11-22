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

    public function index(): void {
        redirect('accounting/chartofaccounts', 'refresh');
    }

    // ── Services calls ────────────────────────────────────────────────────────

    /**
     * GET api/accounting/{endpoint} for this organisation; throws the service's message on an error.
     * @param string $endpoint
     * @param array  $params
     * @returns object
     */
    private function _get(string $endpoint, array $params = []): object {
        $params   = array_filter($params, fn($v) => $v !== null && $v !== '');
        $query    = http_build_query(['orgId' => $this->_orgUID()] + $params);
        $resp     = services_get('api/accounting/' . ltrim($endpoint, '/') . '?' . $query, withJwt: true);
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

    // ── Chart of Accounts ─────────────────────────────────────────────────────

    public function chartofaccounts(): void {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        $this->load->view('accounting/chart_of_accounts/view', $this->pageData);
    }

    // ── Trial Balance ─────────────────────────────────────────────────────────

    public function trialbalance(): void {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        $this->load->view('accounting/trial_balance/view', $this->pageData);
    }

    // ── Journal Listing ───────────────────────────────────────────────────────

    public function journallist(): void {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        $this->load->view('accounting/journal_list/view', $this->pageData);
    }

    // ── General Ledger ────────────────────────────────────────────────────────

    public function generalledger(): void {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        $this->load->view('accounting/general_ledger/view', $this->pageData);
    }

    // ── Manual Journal ────────────────────────────────────────────────────────

    // ── Bank Reconciliation ───────────────────────────────────────────────────

    public function bankreconciliation(): void {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        $this->load->view('accounting/bank_reconciliation/view', $this->pageData);
    }

    // ── Profit & Loss ─────────────────────────────────────────────────────────

    public function profitloss(): void {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        $this->load->view('accounting/profit_loss/view', $this->pageData);
    }

    // ── Balance Sheet ─────────────────────────────────────────────────────────

    public function balancesheet(): void {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        $this->load->view('accounting/balance_sheet/view', $this->pageData);
    }

    // ── Recurring Journals ────────────────────────────────────────────────────

    public function recurringjournals(): void {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        $this->load->view('accounting/recurring_journals/view', $this->pageData);
    }

    // ── Period Lock ───────────────────────────────────────────────────────────

    public function periodlock(): void {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        $this->load->view('accounting/period_lock/view', $this->pageData);
    }

    // ── Comparative P&L ───────────────────────────────────────────────────────

    public function comparativepnl(): void {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        $this->load->view('accounting/comparative_pnl/view', $this->pageData);
    }

    // ── Financial Ratios ──────────────────────────────────────────────────────

    public function ratios(): void {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        $this->load->view('accounting/ratios/view', $this->pageData);
    }

    // ── Cash Flow ─────────────────────────────────────────────────────────────

    public function cashflow(): void {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        $this->load->view('accounting/cash_flow/view', $this->pageData);
    }

    // ── Budget vs Actual ──────────────────────────────────────────────────────

    public function budgetvactual(): void {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        $this->load->view('accounting/budget_vs_actual/view', $this->pageData);
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
            $totals = json_decode(json_encode($r->Totals), true) ?: [];
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
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Day Book ──────────────────────────────────────────────────────────────

    public function daybook(): void {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        $this->load->view('accounting/day_book/view', $this->pageData);
    }

}