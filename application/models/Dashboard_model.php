<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->ReadDb = $this->load->database('ReadDB', TRUE);
        $this->ReadDb->db_debug = FALSE;
    }

    // Safe query helper — returns row or null, never throws
    private function _row($default = null) {
        $q = $this->ReadDb->get();
        return ($q !== false) ? ($q->row() ?? $default) : $default;
    }

    private function _result() {
        $q = $this->ReadDb->get();
        return ($q !== false) ? $q->result() : [];
    }

    // ── Total Receivable: customers who owe us (Debit balance) ──────────────
    public function getTotalReceivable($orgUID) {
        try {
            $this->ReadDb->select('COALESCE(SUM(PendingBalance), 0) AS total');
            $this->ReadDb->from('Customers.CustOpeningBalanceTbl');
            $this->ReadDb->where(['OrgUID' => (int)$orgUID, 'PendingBalType' => 'Debit', 'IsDeleted' => 0]);
            $row = $this->_row();
            return (float)($row->total ?? 0);
        } catch (Exception $e) { return 0; }
    }

    // ── Total Payable: we owe vendors (Credit balance) ───────────────────────
    public function getTotalPayable($orgUID) {
        try {
            $this->ReadDb->select('COALESCE(SUM(PendingBalance), 0) AS total');
            $this->ReadDb->from('Vendors.VendOpeningBalanceTbl');
            $this->ReadDb->where(['OrgUID' => (int)$orgUID, 'PendingBalType' => 'Credit', 'IsDeleted' => 0]);
            $row = $this->_row();
            return (float)($row->total ?? 0);
        } catch (Exception $e) { return 0; }
    }

    // ── Today's sales total & invoice count ──────────────────────────────────
    public function getTodaySales($orgUID) {
        try {
            $this->ReadDb->select('COALESCE(SUM(NetAmount), 0) AS total, COUNT(*) AS count');
            $this->ReadDb->from('Transaction.TransactionsTbl');
            $this->ReadDb->where(['OrgUID' => (int)$orgUID, 'ModuleUID' => 103, 'IsDeleted' => 0]);
            $this->ReadDb->where('DATE(TransDate)', date('Y-m-d'));
            $this->ReadDb->where_not_in('DocStatus', ['Draft', 'Cancelled', 'Rejected']);
            $row = $this->_row();
            return ['total' => (float)($row->total ?? 0), 'count' => (int)($row->count ?? 0)];
        } catch (Exception $e) { return ['total' => 0, 'count' => 0]; }
    }

    // ── This month vs last month sales ───────────────────────────────────────
    public function getMonthlySalesComparison($orgUID) {
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
            $this->ReadDb->where_not_in('DocStatus', ['Draft', 'Cancelled', 'Rejected']);
            $this->ReadDb->where('TransDate >=', $lastStart);
            $row = $this->_row();
            return [
                'this_month' => (float)($row->this_month ?? 0),
                'last_month' => (float)($row->last_month ?? 0),
            ];
        } catch (Exception $e) { return ['this_month' => 0, 'last_month' => 0]; }
    }

    // ── Sales chart: last 30 days grouped by date ────────────────────────────
    public function getSalesChartData($orgUID) {
        try {
            $this->ReadDb->select('DATE(TransDate) AS sale_date, COALESCE(SUM(NetAmount), 0) AS total');
            $this->ReadDb->from('Transaction.TransactionsTbl');
            $this->ReadDb->where(['OrgUID' => (int)$orgUID, 'ModuleUID' => 103, 'IsDeleted' => 0]);
            $this->ReadDb->where('TransDate >=', date('Y-m-d', strtotime('-29 days')));
            $this->ReadDb->where_not_in('DocStatus', ['Draft', 'Cancelled', 'Rejected']);
            $this->ReadDb->group_by('DATE(TransDate)');
            $this->ReadDb->order_by('sale_date', 'ASC');
            return $this->_result();
        } catch (Exception $e) { return []; }
    }

    // ── Overdue invoices: past ValidityDate, still has balance ───────────────
    public function getOverdueInvoices($orgUID) {
        try {
            $this->ReadDb->select('T.TransUID, T.UniqueNumber, T.NetAmount, T.BalanceAmount, T.TransDate, D.ValidityDate, COALESCE(C.Name,"") AS PartyName');
            $this->ReadDb->from('Transaction.TransactionsTbl T');
            $this->ReadDb->join('Transaction.TransDetailTbl D', 'D.TransUID = T.TransUID AND D.FinancialYear = YEAR(T.TransDate)', 'left');
            $this->ReadDb->join('Customers.CustomerTbl C', 'C.CustomerUID = T.PartyUID', 'left');
            $this->ReadDb->where(['T.OrgUID' => (int)$orgUID, 'T.ModuleUID' => 103, 'T.IsDeleted' => 0]);
            $this->ReadDb->where_in('T.DocStatus', ['Issued', 'Partial']);
            $this->ReadDb->where('D.ValidityDate <', date('Y-m-d'));
            $this->ReadDb->where('D.ValidityDate IS NOT NULL', null, false);
            $this->ReadDb->where('T.BalanceAmount >', 0);
            $this->ReadDb->order_by('D.ValidityDate', 'ASC');
            $this->ReadDb->limit(6);
            return $this->_result();
        } catch (Exception $e) { return []; }
    }

    // ── Top 5 customers by outstanding receivable ────────────────────────────
    public function getTopCustomers($orgUID) {
        try {
            $this->ReadDb->select('C.Name, C.MobileNumber, COB.PendingBalance');
            $this->ReadDb->from('Customers.CustOpeningBalanceTbl COB');
            $this->ReadDb->join('Customers.CustomerTbl C', 'C.CustomerUID = COB.CustomerUID', 'left');
            $this->ReadDb->where(['COB.OrgUID' => (int)$orgUID, 'COB.PendingBalType' => 'Debit', 'COB.IsDeleted' => 0]);
            $this->ReadDb->where('COB.PendingBalance >', 0);
            $this->ReadDb->order_by('COB.PendingBalance', 'DESC');
            $this->ReadDb->limit(5);
            return $this->_result();
        } catch (Exception $e) { return []; }
    }

    // ── Recent 10 transactions across all modules ────────────────────────────
    public function getRecentTransactions($orgUID) {
        try {
            $this->ReadDb->select('T.UniqueNumber, T.TransType, T.NetAmount, T.DocStatus, T.TransDate, T.ModuleUID, COALESCE(C.Name, V.Name, "") AS PartyName');
            $this->ReadDb->from('Transaction.TransactionsTbl T');
            $this->ReadDb->join('Customers.CustomerTbl C', "C.CustomerUID = T.PartyUID AND T.PartyType = 'C'", 'left');
            $this->ReadDb->join('Vendors.VendorTbl V', "V.VendorUID = T.PartyUID AND T.PartyType = 'S'", 'left');
            $this->ReadDb->where(['T.OrgUID' => (int)$orgUID, 'T.IsDeleted' => 0]);
            $this->ReadDb->where_not_in('T.DocStatus', ['Draft', 'Cancelled', 'Rejected']);
            $this->ReadDb->order_by('T.TransUID', 'DESC');
            $this->ReadDb->limit(10);
            return $this->_result();
        } catch (Exception $e) { return []; }
    }
}
