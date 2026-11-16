<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Reports extends MY_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function index(): void {
        if (empty($this->pageData['JwtData'])) {
            redirect('login');
            return;
        }
        if (!$this->_loadPageTitle(49)) { $this->load->view('common/module_error', $this->pageData); return; }
        $this->load->view('reports/index', $this->pageData);
    }

    public function daybook(): void {
        if (empty($this->pageData['JwtData'])) {
            redirect('login');
            return;
        }
        $this->pageData['PageTitle'] = 'Day Book';
        $rawDate = $this->input->get('date') ?? '';
        $this->pageData['_initDate']   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDate) ? $rawDate : date('Y-m-d');
        $this->pageData['_initSearch'] = $this->input->get('search') ?? '';
        $this->load->view('reports/daybook', $this->pageData);
    }

    // ── Sales Summary ─────────────────────────────────────────────────────────

    public function salesSummary(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('login'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $rawGrp  = $this->input->get('groupby') ?? '';
        $this->pageData['_initFrom']    = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-01-01');
        $this->pageData['_initTo']      = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->pageData['_initGroupBy'] = in_array($rawGrp, ['month','quarter','year'])  ? $rawGrp  : 'month';
        $this->load->view('reports/sales_summary', $this->pageData);
    }

    // ── Purchase Summary ──────────────────────────────────────────────────────

    public function purchaseSummary(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('login'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $rawGrp  = $this->input->get('groupby') ?? '';
        $this->pageData['_initFrom']    = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-01-01');
        $this->pageData['_initTo']      = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->pageData['_initGroupBy'] = in_array($rawGrp, ['month','quarter','year'])  ? $rawGrp  : 'month';
        $this->load->view('reports/purchase_summary', $this->pageData);
    }

    // ── Monthly Summary ───────────────────────────────────────────────────────

    public function monthlySummary(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('login'); return; }
        $rawYear = $this->input->get('year') ?? '';
        $initYear = (preg_match('/^\d{4}$/', $rawYear) && (int)$rawYear >= 2000 && (int)$rawYear <= 2099)
                    ? (int)$rawYear : (int)date('Y');
        $this->pageData['_initYear']    = $initYear;
        $this->pageData['_currentYear'] = (int)date('Y');
        $this->load->view('reports/monthly_summary', $this->pageData);
    }

    // ── Payment Received ──────────────────────────────────────────────────────

    public function paymentReceived(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('login'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $this->pageData['_initFrom'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-01-01');
        $this->pageData['_initTo']   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->load->view('reports/payment_received', $this->pageData);
    }

    // ── Payment Made ──────────────────────────────────────────────────────────

    public function paymentMade(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('login'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $this->pageData['_initFrom'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-01-01');
        $this->pageData['_initTo']   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->load->view('reports/payment_made', $this->pageData);
    }

    // ── P&L Statement ────────────────────────────────────────────────────────

    public function plStatement(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('login'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $this->pageData['_initFrom'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-01-01');
        $this->pageData['_initTo']   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->load->view('reports/pl_statement', $this->pageData);
    }

    // ── Balance Sheet ─────────────────────────────────────────────────────────

    public function balanceSheet(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('login'); return; }
        $rawAsOf = $this->input->get('asof') ?? '';
        $this->pageData['_initAsOf'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawAsOf) ? $rawAsOf : date('Y-m-d');
        $this->load->view('reports/balance_sheet', $this->pageData);
    }

    // ── Trial Balance ─────────────────────────────────────────────────────────

    public function trialBalance(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('login'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $this->pageData['_initFrom'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-01-01');
        $this->pageData['_initTo']   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->load->view('reports/trial_balance', $this->pageData);
    }

    // ── Customer Outstanding ─────────────────────────────────────────────────

    public function customerOutstanding(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('login'); return; }
        $this->load->view('reports/customer_outstanding', $this->pageData);
    }

    // ── Customer Ledger ───────────────────────────────────────────────────────

    public function customerLedger(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('login'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $this->pageData['_initFrom'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-01-01');
        $this->pageData['_initTo']   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->load->view('reports/customer_ledger', $this->pageData);
    }

    // ── Customer Ageing ───────────────────────────────────────────────────────

    public function customerAgeing(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('login'); return; }
        $this->load->view('reports/customer_ageing', $this->pageData);
    }

    // ── Supplier Outstanding ──────────────────────────────────────────────────

    public function supplierOutstanding(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('login'); return; }
        $this->load->view('reports/supplier_outstanding', $this->pageData);
    }

    // ── Supplier Ledger ───────────────────────────────────────────────────────

    public function supplierLedger(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('login'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $this->pageData['_initFrom'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-01-01');
        $this->pageData['_initTo']   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->load->view('reports/supplier_ledger', $this->pageData);
    }

    // ── Item Wise Sales ───────────────────────────────────────────────────────

    public function itemWiseSales(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('login'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $this->pageData['_iwsInitFrom'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-01-01');
        $this->pageData['_iwsInitTo']   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->load->view('reports/item_wise_sales', $this->pageData);
    }

    // ── Item Wise Purchase ────────────────────────────────────────────────────

    public function itemWisePurchase(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('login'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $this->pageData['_iwpInitFrom'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-01-01');
        $this->pageData['_iwpInitTo']   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->load->view('reports/item_wise_purchase', $this->pageData);
    }

    // ── Low Stock Alert ───────────────────────────────────────────────────────

    public function lowStockAlert(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('login'); return; }
        $this->load->view('reports/low_stock_alert', $this->pageData);
    }

    // ── Stock Summary ─────────────────────────────────────────────────────────

    public function stockSummary(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('login'); return; }
        $this->load->view('reports/stock_summary', $this->pageData);
    }

    // ── Invoice Item-wise ─────────────────────────────────────────────────────

    public function invoiceItemwise(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('login'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $this->pageData['_iiInitFrom'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-01-01');
        $this->pageData['_iiInitTo']   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->load->view('reports/invoice_itemwise', $this->pageData);
    }

    // ── Purchase Item-wise ────────────────────────────────────────────────────

    public function purchaseItemwise(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('login'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $this->pageData['_piInitFrom'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-01-01');
        $this->pageData['_piInitTo']   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->load->view('reports/purchase_itemwise', $this->pageData);
    }

    // ── Sales Return Item-wise ────────────────────────────────────────────────

    public function salesReturnItemwise(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('login'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $this->pageData['_srInitFrom'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-01-01');
        $this->pageData['_srInitTo']   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->load->view('reports/sales_return_itemwise', $this->pageData);
    }

    // ── Purchase Return Item-wise ─────────────────────────────────────────────

    public function purchaseReturnItemwise(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('login'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $this->pageData['_prInitFrom'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-01-01');
        $this->pageData['_prInitTo']   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->load->view('reports/purchase_return_itemwise', $this->pageData);
    }

    // ── Sales Register ───────────────────────────────────────────────────────

    public function salesRegister(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('login'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $this->pageData['_sregInitFrom'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-01-01');
        $this->pageData['_sregInitTo']   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->load->view('reports/sales_register', $this->pageData);
    }

    // ── Purchase Register ─────────────────────────────────────────────────────

    public function purchaseRegister(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('login'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $this->pageData['_pregInitFrom'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-01-01');
        $this->pageData['_pregInitTo']   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->load->view('reports/purchase_register', $this->pageData);
    }

    // ── Sales Return Register ─────────────────────────────────────────────────

    public function salesReturnRegister(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('login'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $this->pageData['_srrInitFrom'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-01-01');
        $this->pageData['_srrInitTo']   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->load->view('reports/sales_return_register', $this->pageData);
    }

    // ── Purchase Return Register ──────────────────────────────────────────────

    public function purchaseReturnRegister(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('login'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $this->pageData['_prrInitFrom'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-01-01');
        $this->pageData['_prrInitTo']   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->load->view('reports/purchase_return_register', $this->pageData);
    }

    // ── Delivery Challan Register ─────────────────────────────────────────────

    public function deliveryChallanRegister(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('login'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $this->pageData['_dcrInitFrom'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-01-01');
        $this->pageData['_dcrInitTo']   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->load->view('reports/delivery_challan_register', $this->pageData);
    }

    // ── Expense Register ──────────────────────────────────────────────────────

    public function expenseRegister(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('login'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $this->pageData['_exrInitFrom'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-01-01');
        $this->pageData['_exrInitTo']   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->load->view('reports/expense_register', $this->pageData);
    }

    // ── Tax Reports ───────────────────────────────────────────────────────────

    public function gstr1(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('login'); return; }
        $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
        $month  = (int) ($this->input->get('month') ?? date('n'));
        $year   = (int) ($this->input->get('year')  ?? date('Y'));
        $month  = ($month >= 1 && $month <= 12) ? $month : (int) date('n');
        $year   = ($year  >= 2000 && $year <= 2099) ? $year : (int) date('Y');
        $this->pageData['_gstrMonth'] = $month;
        $this->pageData['_gstrYear']  = $year;
        $this->load->view('reports/gstr1', $this->pageData);
    }

    public function gstr2b(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('login'); return; }
        $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
        $month  = (int) ($this->input->get('month') ?? date('n'));
        $year   = (int) ($this->input->get('year')  ?? date('Y'));
        $month  = ($month >= 1 && $month <= 12) ? $month : (int) date('n');
        $year   = ($year  >= 2000 && $year <= 2099) ? $year : (int) date('Y');
        $this->pageData['_gstrMonth'] = $month;
        $this->pageData['_gstrYear']  = $year;
        $this->load->view('reports/gstr2b', $this->pageData);
    }

    public function gstr3b(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('login'); return; }
        $orgUID = (int) $this->pageData['JwtData']->Org->OrgUID;
        $month  = (int) ($this->input->get('month') ?? date('n'));
        $year   = (int) ($this->input->get('year')  ?? date('Y'));
        $month  = ($month >= 1 && $month <= 12) ? $month : (int) date('n');
        $year   = ($year  >= 2000 && $year <= 2099) ? $year : (int) date('Y');
        $this->pageData['_gstrMonth'] = $month;
        $this->pageData['_gstrYear']  = $year;
        $this->load->view('reports/gstr3b', $this->pageData);
    }

    public function gstr7(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('login'); return; }
        $orgUID  = (int) $this->pageData['JwtData']->Org->OrgUID;
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $_from   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-m-01');
        $_to     = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->pageData['_from'] = $_from;
        $this->pageData['_to']   = $_to;
        $this->load->view('reports/gstr7', $this->pageData);
    }

    public function hsnSummary(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('login'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $_from   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-m-01');
        $_to     = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->pageData['_from'] = $_from;
        $this->pageData['_to']   = $_to;
        $this->load->view('reports/hsn_summary', $this->pageData);
    }

    public function tdsReceivable(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('login'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $_from   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-m-01');
        $_to     = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->pageData['_from'] = $_from;
        $this->pageData['_to']   = $_to;
        $this->load->view('reports/tds_receivable', $this->pageData);
    }

    public function tdsPayable(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('login'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $_from   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-m-01');
        $_to     = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->pageData['_from'] = $_from;
        $this->pageData['_to']   = $_to;
        $this->load->view('reports/tds_payable', $this->pageData);
    }

    public function tcsReceivable(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('login'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $_from   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-m-01');
        $_to     = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->pageData['_from'] = $_from;
        $this->pageData['_to']   = $_to;
        $this->load->view('reports/tcs_receivable', $this->pageData);
    }

    public function tcsPayable(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('login'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $_from   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-m-01');
        $_to     = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->pageData['_from'] = $_from;
        $this->pageData['_to']   = $_to;
        $this->load->view('reports/tcs_payable', $this->pageData);
    }

    // ── Bank Statement ────────────────────────────────────────────────────────

    public function bankStatement(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('login'); return; }
        $orgUID  = (int) $this->pageData['JwtData']->Org->OrgUID;
        $rawFrom = $this->input->get('from')    ?? '';
        $rawTo   = $this->input->get('to')      ?? '';
        $rawAcct = $this->input->get('account') ?? '';
        $this->pageData['_initFrom']       = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-01-01');
        $this->pageData['_initTo']         = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->pageData['_initAccountUID'] = (preg_match('/^\d+$/', $rawAcct) && (int)$rawAcct > 0) ? (int)$rawAcct : 0;
        $resp = $this->_servicesGet('api/reports/getBankAccounts', ['orgId' => $orgUID]);
        $this->pageData['_bankAccounts']   = ($resp && empty($resp->Error)) ? json_decode(json_encode($resp->Data ?? []), true) : [];
        $this->load->view('reports/bank_statement', $this->pageData);
    }

    // ── Brand Wise Sales ──────────────────────────────────────────────────────

    public function brandWiseSales(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('login'); return; }
        $rawFrom = $this->input->get('from') ?? '';
        $rawTo   = $this->input->get('to')   ?? '';
        $this->pageData['_bwsInitFrom'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom) ? $rawFrom : date('Y-01-01');
        $this->pageData['_bwsInitTo']   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTo)   ? $rawTo   : date('Y-m-d');
        $this->load->view('reports/brand_wise_sales', $this->pageData);
    }

    // ── Variant Stock ─────────────────────────────────────────────────────────

    public function variantStock(): void
    {
        if (empty($this->pageData['JwtData'])) { redirect('login'); return; }
        $this->load->view('reports/variant_stock', $this->pageData);
    }

}
