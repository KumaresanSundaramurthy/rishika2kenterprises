<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->ReadDb = $this->load->database('ReadDB', TRUE);
        $this->ReadDb->db_debug = FALSE;
    }

    // Safe query helper — returns row or null, never throws
    private function _row(?object $default = null): ?object {
        $q = $this->ReadDb->get();
        return ($q !== false) ? ($q->row() ?? $default) : $default;
    }

    private function _result(): array {
        $q = $this->ReadDb->get();
        return ($q !== false) ? $q->result() : [];
    }

    // ── Total Receivable: customers who owe us (Debit balance) ──────────────
    public function getTotalReceivable(int $orgUID): float {
        try {
            $this->ReadDb->select('COALESCE(SUM(PendingBalance), 0) AS total');
            $this->ReadDb->from('Customers.CustOpeningBalanceTbl');
            $this->ReadDb->where(['OrgUID' => (int)$orgUID, 'PendingBalType' => 'Debit', 'IsDeleted' => 0]);
            $row = $this->_row();
            return (float)($row->total ?? 0);
        } catch (Exception $e) {
            notifyError('dashboard_model::getTotalReceivable', $e);
            return 0;
        }
    }

    // ── Total Payable: we owe vendors (Credit balance) ───────────────────────
    public function getTotalPayable(int $orgUID): float {
        try {
            $this->ReadDb->select('COALESCE(SUM(PendingBalance), 0) AS total');
            $this->ReadDb->from('Vendors.VendOpeningBalanceTbl');
            $this->ReadDb->where(['OrgUID' => (int)$orgUID, 'PendingBalType' => 'Credit', 'IsDeleted' => 0]);
            $row = $this->_row();
            return (float)($row->total ?? 0);
        } catch (Exception $e) {
            notifyError('dashboard_model::getTotalPayable', $e);
            return 0;
        }
    }

    // ── Today's sales total & invoice count ──────────────────────────────────
    public function getTodaySales(int $orgUID, int $branchUID = 0): array {
        try {
            $this->ReadDb->select('COALESCE(SUM(NetAmount), 0) AS total, COUNT(*) AS count');
            $this->ReadDb->from('Transaction.TransactionsTbl');
            $this->ReadDb->where(['OrgUID' => (int)$orgUID, 'ModuleUID' => 103, 'IsDeleted' => 0]);
            if ($branchUID > 0) { $this->ReadDb->where('BranchUID', $branchUID); }
            $this->ReadDb->where('DATE(TransDate)', date('Y-m-d'));
            $this->ReadDb->where_not_in('DocStatus', ['Draft', 'Cancelled', 'Rejected']);
            $row = $this->_row();
            return ['total' => (float)($row->total ?? 0), 'count' => (int)($row->count ?? 0)];
        } catch (Exception $e) {
            notifyError('dashboard_model::getTodaySales', $e);
            return ['total' => 0, 'count' => 0];
        }
    }

    // ── This month vs last month sales ───────────────────────────────────────
    public function getMonthlySalesComparison(int $orgUID, int $branchUID = 0): array {
        try {
            $thisStart = date('Y-m-01');
            $lastStart = date('Y-m-01', strtotime('first day of last month'));
            $lastEnd   = date('Y-m-t', strtotime('last day of last month'));
            $this->ReadDb->select("
                COALESCE(SUM(CASE WHEN TransDate >= '{$thisStart}' THEN NetAmount ELSE 0 END), 0) AS this_month,
                COALESCE(SUM(CASE WHEN TransDate BETWEEN '{$lastStart}' AND '{$lastEnd}' THEN NetAmount ELSE 0 END), 0) AS last_month
            ");
            $this->ReadDb->from('Transaction.TransactionsTbl');
            $this->ReadDb->where(['OrgUID' => (int)$orgUID, 'ModuleUID' => 103, 'IsDeleted' => 0]);
            if ($branchUID > 0) { $this->ReadDb->where('BranchUID', $branchUID); }
            $this->ReadDb->where_not_in('DocStatus', ['Draft', 'Cancelled', 'Rejected']);
            $this->ReadDb->where('TransDate >=', $lastStart);
            $row = $this->_row();
            return [
                'this_month' => (float)($row->this_month ?? 0),
                'last_month' => (float)($row->last_month ?? 0),
            ];
        } catch (Exception $e) {
            notifyError('dashboard_model::getMonthlySalesComparison', $e);
            return ['this_month' => 0, 'last_month' => 0];
        }
    }

    // ── Sales chart: last 30 days grouped by date ────────────────────────────
    public function getSalesChartData(int $orgUID, int $branchUID = 0): array {
        try {
            $this->ReadDb->select('DATE(TransDate) AS sale_date, COALESCE(SUM(NetAmount), 0) AS total');
            $this->ReadDb->from('Transaction.TransactionsTbl');
            $this->ReadDb->where(['OrgUID' => (int)$orgUID, 'ModuleUID' => 103, 'IsDeleted' => 0]);
            if ($branchUID > 0) { $this->ReadDb->where('BranchUID', $branchUID); }
            $this->ReadDb->where('TransDate >=', date('Y-m-d', strtotime('-29 days')));
            $this->ReadDb->where_not_in('DocStatus', ['Draft', 'Cancelled', 'Rejected']);
            $this->ReadDb->group_by('DATE(TransDate)');
            $this->ReadDb->order_by('sale_date', 'ASC');
            return $this->_result();
        } catch (Exception $e) {
            notifyError('dashboard_model::getSalesChartData', $e);
            return [];
        }
    }

    // ── Overdue invoices: past ValidityDate, still has balance ───────────────
    public function getOverdueInvoices(int $orgUID, int $branchUID = 0): array {
        try {
            $this->ReadDb->select('T.TransUID, T.UniqueNumber, T.NetAmount, T.BalanceAmount, T.TransDate, D.ValidityDate, COALESCE(C.Name,"") AS PartyName');
            $this->ReadDb->from('Transaction.TransactionsTbl T');
            $this->ReadDb->join('Transaction.TransDetailTbl D', 'D.TransUID = T.TransUID AND D.FinancialYear = YEAR(T.TransDate)', 'left');
            $this->ReadDb->join('Customers.CustomerTbl C', 'C.CustomerUID = T.PartyUID', 'left');
            $this->ReadDb->where(['T.OrgUID' => (int)$orgUID, 'T.ModuleUID' => 103, 'T.IsDeleted' => 0]);
            if ($branchUID > 0) { $this->ReadDb->where('T.BranchUID', $branchUID); }
            $this->ReadDb->where_in('T.DocStatus', ['Issued', 'Partial']);
            $this->ReadDb->where('D.ValidityDate <', date('Y-m-d'));
            $this->ReadDb->where('D.ValidityDate IS NOT NULL', null, false);
            $this->ReadDb->where('T.BalanceAmount >', 0);
            $this->ReadDb->order_by('D.ValidityDate', 'ASC');
            $this->ReadDb->limit(6);
            return $this->_result();
        } catch (Exception $e) {
            notifyError('dashboard_model::getOverdueInvoices', $e);
            return [];
        }
    }

    // ── Top 5 customers by outstanding receivable ────────────────────────────
    public function getTopCustomers(int $orgUID): array {
        try {
            $this->ReadDb->select('C.Name, C.MobileNumber, COB.PendingBalance');
            $this->ReadDb->from('Customers.CustOpeningBalanceTbl COB');
            $this->ReadDb->join('Customers.CustomerTbl C', 'C.CustomerUID = COB.CustomerUID', 'left');
            $this->ReadDb->where(['COB.OrgUID' => (int)$orgUID, 'COB.PendingBalType' => 'Debit', 'COB.IsDeleted' => 0]);
            $this->ReadDb->where('COB.PendingBalance >', 0);
            $this->ReadDb->order_by('COB.PendingBalance', 'DESC');
            $this->ReadDb->limit(5);
            return $this->_result();
        } catch (Exception $e) {
            notifyError('dashboard_model::getTopCustomers', $e);
            return [];
        }
    }

    // ── Recent 10 transactions across all modules ────────────────────────────
    public function getRecentTransactions(int $orgUID, int $branchUID = 0): array {
        try {
            $this->ReadDb->select('T.UniqueNumber, T.TransType, T.NetAmount, T.DocStatus, T.TransDate, T.ModuleUID, COALESCE(C.Name, V.Name, "") AS PartyName');
            $this->ReadDb->from('Transaction.TransactionsTbl T');
            $this->ReadDb->join('Customers.CustomerTbl C', "C.CustomerUID = T.PartyUID AND T.PartyType = 'C'", 'left');
            $this->ReadDb->join('Vendors.VendorTbl V', "V.VendorUID = T.PartyUID AND T.PartyType = 'S'", 'left');
            $this->ReadDb->where(['T.OrgUID' => (int)$orgUID, 'T.IsDeleted' => 0]);
            if ($branchUID > 0) { $this->ReadDb->where('T.BranchUID', $branchUID); }
            $this->ReadDb->where_not_in('T.DocStatus', ['Draft', 'Cancelled', 'Rejected']);
            $this->ReadDb->order_by('T.TransUID', 'DESC');
            $this->ReadDb->limit(10);
            return $this->_result();
        } catch (Exception $e) {
            notifyError('dashboard_model::getRecentTransactions', $e);
            return [];
        }
    }

    // ── Receivable trend: daily open invoice balance amounts (last 30 days) ──
    public function getReceivableTrend(int $orgUID, int $branchUID = 0): array {
        try {
            $this->ReadDb->select('DATE(TransDate) AS sale_date, COALESCE(SUM(BalanceAmount), 0) AS total');
            $this->ReadDb->from('Transaction.TransactionsTbl');
            $this->ReadDb->where(['OrgUID' => $orgUID, 'ModuleUID' => 103, 'IsDeleted' => 0]);
            if ($branchUID > 0) { $this->ReadDb->where('BranchUID', $branchUID); }
            $this->ReadDb->where('TransDate >=', date('Y-m-d', strtotime('-29 days')));
            $this->ReadDb->where_in('DocStatus', ['Issued', 'Partial']);
            $this->ReadDb->group_by('DATE(TransDate)');
            $this->ReadDb->order_by('sale_date', 'ASC');
            return $this->_result();
        } catch (Exception $e) {
            notifyError('dashboard_model::getReceivableTrend', $e);
            return [];
        }
    }

    // ── Payable trend: daily purchase bill amounts (last 30 days) ────────────
    public function getPayableTrend(int $orgUID, int $branchUID = 0): array {
        try {
            $this->ReadDb->select('DATE(TransDate) AS sale_date, COALESCE(SUM(NetAmount), 0) AS total');
            $this->ReadDb->from('Transaction.TransactionsTbl');
            $this->ReadDb->where(['OrgUID' => $orgUID, 'ModuleUID' => 105, 'IsDeleted' => 0]);
            if ($branchUID > 0) { $this->ReadDb->where('BranchUID', $branchUID); }
            $this->ReadDb->where('TransDate >=', date('Y-m-d', strtotime('-29 days')));
            $this->ReadDb->where_not_in('DocStatus', ['Draft', 'Cancelled', 'Rejected']);
            $this->ReadDb->group_by('DATE(TransDate)');
            $this->ReadDb->order_by('sale_date', 'ASC');
            return $this->_result();
        } catch (Exception $e) {
            notifyError('dashboard_model::getPayableTrend', $e);
            return [];
        }
    }

    // ── Monthly sales trend: last 12 months ──────────────────────────────────
    public function getMonthlySalesTrend(int $orgUID, int $branchUID = 0): array {
        try {
            $this->ReadDb->select("DATE_FORMAT(TransDate, '%Y-%m-01') AS sale_date, COALESCE(SUM(NetAmount), 0) AS total");
            $this->ReadDb->from('Transaction.TransactionsTbl');
            $this->ReadDb->where(['OrgUID' => $orgUID, 'ModuleUID' => 103, 'IsDeleted' => 0]);
            if ($branchUID > 0) { $this->ReadDb->where('BranchUID', $branchUID); }
            $this->ReadDb->where('TransDate >=', date('Y-m-01', strtotime('-11 months')));
            $this->ReadDb->where_not_in('DocStatus', ['Draft', 'Cancelled', 'Rejected']);
            $this->ReadDb->group_by("DATE_FORMAT(TransDate, '%Y-%m-01')");
            $this->ReadDb->order_by('sale_date', 'ASC');
            return $this->_result();
        } catch (Exception $e) {
            notifyError('dashboard_model::getMonthlySalesTrend', $e);
            return [];
        }
    }

    // ── Today's purchases total & count ──────────────────────────────────────
    public function getTodayPurchases(int $orgUID, int $branchUID = 0): array {
        try {
            $this->ReadDb->select('COALESCE(SUM(NetAmount), 0) AS total, COUNT(*) AS count');
            $this->ReadDb->from('Transaction.TransactionsTbl');
            $this->ReadDb->where(['OrgUID' => $orgUID, 'ModuleUID' => 105, 'IsDeleted' => 0]);
            if ($branchUID > 0) { $this->ReadDb->where('BranchUID', $branchUID); }
            $this->ReadDb->where('DATE(TransDate)', date('Y-m-d'));
            $this->ReadDb->where_not_in('DocStatus', ['Draft', 'Cancelled', 'Rejected']);
            $row = $this->_row();
            return ['total' => (float)($row->total ?? 0), 'count' => (int)($row->count ?? 0)];
        } catch (Exception $e) {
            notifyError('dashboard_model::getTodayPurchases', $e);
            return ['total' => 0, 'count' => 0];
        }
    }

    // ── This month vs last month purchases ───────────────────────────────────
    public function getMonthlyPurchasesComparison(int $orgUID, int $branchUID = 0): array {
        try {
            $thisStart = date('Y-m-01');
            $lastStart = date('Y-m-01', strtotime('first day of last month'));
            $lastEnd   = date('Y-m-t', strtotime('last day of last month'));
            $this->ReadDb->select("
                COALESCE(SUM(CASE WHEN TransDate >= '{$thisStart}' THEN NetAmount ELSE 0 END), 0) AS this_month,
                COALESCE(SUM(CASE WHEN TransDate BETWEEN '{$lastStart}' AND '{$lastEnd}' THEN NetAmount ELSE 0 END), 0) AS last_month
            ");
            $this->ReadDb->from('Transaction.TransactionsTbl');
            $this->ReadDb->where(['OrgUID' => $orgUID, 'ModuleUID' => 105, 'IsDeleted' => 0]);
            if ($branchUID > 0) { $this->ReadDb->where('BranchUID', $branchUID); }
            $this->ReadDb->where_not_in('DocStatus', ['Draft', 'Cancelled', 'Rejected']);
            $this->ReadDb->where('TransDate >=', $lastStart);
            $row = $this->_row();
            return [
                'this_month' => (float)($row->this_month ?? 0),
                'last_month' => (float)($row->last_month ?? 0),
            ];
        } catch (Exception $e) {
            notifyError('dashboard_model::getMonthlyPurchasesComparison', $e);
            return ['this_month' => 0, 'last_month' => 0];
        }
    }

    // ── Top 5 vendors by outstanding payable ─────────────────────────────────
    public function getTopVendors(int $orgUID): array {
        try {
            $this->ReadDb->select('V.Name, V.MobileNumber, VOB.PendingBalance');
            $this->ReadDb->from('Vendors.VendOpeningBalanceTbl VOB');
            $this->ReadDb->join('Vendors.VendorTbl V', 'V.VendorUID = VOB.VendorUID', 'left');
            $this->ReadDb->where(['VOB.OrgUID' => $orgUID, 'VOB.PendingBalType' => 'Credit', 'VOB.IsDeleted' => 0]);
            $this->ReadDb->where('VOB.PendingBalance >', 0);
            $this->ReadDb->order_by('VOB.PendingBalance', 'DESC');
            $this->ReadDb->limit(5);
            return $this->_result();
        } catch (Exception $e) {
            notifyError('dashboard_model::getTopVendors', $e);
            return [];
        }
    }

    // ── Expense summary this month: top 5 categories by amount ──────────────
    public function getExpenseSummary(int $orgUID): array {
        try {
            $this->ReadDb->select("COALESCE(ec.CategoryName, 'Uncategorized') AS category, COALESCE(SUM(e.NetAmount), 0) AS total");
            $this->ReadDb->from('Transaction.ExpensesTbl e');
            $this->ReadDb->join('Transaction.ExpenseCategoryTbl ec', 'ec.CategoryUID = e.CategoryUID AND ec.IsDeleted = 0', 'left');
            $this->ReadDb->where(['e.OrgUID' => $orgUID, 'e.IsDeleted' => 0]);
            $this->ReadDb->where('e.ExpenseDate >=', date('Y-m-01'));
            $this->ReadDb->where_not_in('e.DocStatus', ['Draft', 'Cancelled']);
            $this->ReadDb->group_by('e.CategoryUID');
            $this->ReadDb->order_by('total', 'DESC');
            $this->ReadDb->limit(5);
            return $this->_result();
        } catch (Exception $e) {
            notifyError('dashboard_model::getExpenseSummary', $e);
            return [];
        }
    }

    // ── Top 5 products by revenue this month (invoices only) ────────────────
    public function getTopProducts(int $orgUID, int $branchUID = 0): array {
        try {
            $this->ReadDb->select('P.ItemName AS ProductName, SUM(TI.Quantity) AS qty_sold, COALESCE(SUM(TI.NetAmount), 0) AS revenue');
            $this->ReadDb->from('Transaction.TransProductsTbl TI');
            $this->ReadDb->join('Transaction.TransactionsTbl T', 'T.TransUID = TI.TransUID', 'inner');
            $this->ReadDb->join('Products.ProductTbl P', 'P.ProductUID = TI.ProductUID', 'left');
            $this->ReadDb->where(['T.OrgUID' => $orgUID, 'T.ModuleUID' => 103, 'T.IsDeleted' => 0, 'TI.IsDeleted' => 0]);
            if ($branchUID > 0) { $this->ReadDb->where('T.BranchUID', $branchUID); }
            $this->ReadDb->where('T.TransDate >=', date('Y-m-01'));
            $this->ReadDb->where_not_in('T.DocStatus', ['Draft', 'Cancelled', 'Rejected']);
            $this->ReadDb->group_by('TI.ProductUID');
            $this->ReadDb->order_by('revenue', 'DESC');
            $this->ReadDb->limit(5);
            return $this->_result();
        } catch (Exception $e) {
            notifyError('dashboard_model::getTopProducts', $e);
            return [];
        }
    }

    // ── Pending document counts: drafts, open invoices, open POs, open purchases ─
    public function getPendingCounts(int $orgUID, int $branchUID = 0): object {
        try {
            $this->ReadDb->select("
                COALESCE(SUM(CASE WHEN DocStatus = 'Draft' THEN 1 ELSE 0 END), 0) AS draft_count,
                COALESCE(SUM(CASE WHEN ModuleUID = 103 AND DocStatus IN ('Issued','Partial') THEN 1 ELSE 0 END), 0) AS open_invoices,
                COALESCE(SUM(CASE WHEN ModuleUID = 104 AND DocStatus NOT IN ('Draft','Cancelled','Rejected','Closed','Received') THEN 1 ELSE 0 END), 0) AS open_pos,
                COALESCE(SUM(CASE WHEN ModuleUID = 105 AND DocStatus NOT IN ('Draft','Cancelled','Rejected','Closed') THEN 1 ELSE 0 END), 0) AS open_purchases
            ");
            $this->ReadDb->from('Transaction.TransactionsTbl');
            $this->ReadDb->where(['OrgUID' => $orgUID, 'IsDeleted' => 0]);
            if ($branchUID > 0) { $this->ReadDb->where('BranchUID', $branchUID); }
            $row = $this->_row();
            return $row ?? (object)['draft_count' => 0, 'open_invoices' => 0, 'open_pos' => 0, 'open_purchases' => 0];
        } catch (Exception $e) {
            notifyError('dashboard_model::getPendingCounts', $e);
            return (object)['draft_count' => 0, 'open_invoices' => 0, 'open_pos' => 0, 'open_purchases' => 0];
        }
    }
}
