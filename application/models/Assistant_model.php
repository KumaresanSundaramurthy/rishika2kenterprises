<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Assistant_model — builds a business data snapshot used as Gemini context.
 * ALL queries use ReadDb. No writes. No free-form SQL generation.
 */
class Assistant_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->ReadDb = $this->load->database('ReadDB', TRUE);
        $this->ReadDb->db_debug = FALSE;
    }

    /**
     * @returns ?object
     */
    private function _row(): ?object {
        $q = $this->ReadDb->get();
        return ($q !== false) ? ($q->row() ?? null) : null;
    }

    /**
     * @returns array
     */
    private function _result(): array {
        $q = $this->ReadDb->get();
        return ($q !== false) ? $q->result() : [];
    }

    /**
     * Aggregate business snapshot for the AI prompt.
     * Returns all data in a single call; each sub-query is independent.
     *
     * @param int $orgUID
     * @param int $branchUID
     * @returns array
     */
    public function buildContext(int $orgUID, int $branchUID = 0): array {
        return [
            'sales'      => $this->_getSalesSummary($orgUID, $branchUID),
            'receivable' => $this->_getReceivable($orgUID),
            'payable'    => $this->_getPayable($orgUID),
            'overdue'    => $this->_getOverdueCount($orgUID, $branchUID),
            'lowstock'   => $this->_getLowStockCount($orgUID),
            'topCust'    => $this->_getTopCustomers($orgUID, $branchUID),
            'topProd'    => $this->_getTopProducts($orgUID, $branchUID),
            'today'      => $this->_getTodayActivity($orgUID, $branchUID),
        ];
    }

    /**
     * @param int $orgUID
     * @param int $branchUID
     * @returns array
     */
    private function _getSalesSummary(int $orgUID, int $branchUID): array {
        try {
            $thisStart = date('Y-m-01');
            $lastStart = date('Y-m-01', strtotime('first day of last month'));
            $lastEnd   = date('Y-m-t',  strtotime('last day of last month'));
            $this->ReadDb->select("
                COALESCE(SUM(CASE WHEN TransDate >= '{$thisStart}' THEN NetAmount ELSE 0 END), 0) AS this_month,
                COUNT(CASE WHEN TransDate >= '{$thisStart}' THEN 1 END)                           AS this_count,
                COALESCE(SUM(CASE WHEN TransDate BETWEEN '{$lastStart}' AND '{$lastEnd}' THEN NetAmount ELSE 0 END), 0) AS last_month
            ");
            $this->ReadDb->from('Transaction.TransactionsTbl');
            $this->ReadDb->where(['OrgUID' => $orgUID, 'ModuleUID' => 103, 'IsDeleted' => 0]);
            if ($branchUID > 0) { $this->ReadDb->where('BranchUID', $branchUID); }
            $this->ReadDb->where_not_in('DocStatus', ['Draft', 'Cancelled', 'Rejected']);
            $this->ReadDb->where('TransDate >=', $lastStart);
            $row = $this->_row();
            return [
                'this_month' => (float)($row->this_month ?? 0),
                'this_count' => (int)  ($row->this_count ?? 0),
                'last_month' => (float)($row->last_month ?? 0),
            ];
        } catch (Exception $e) {
            notifyError($e, 'Assistant_model::_getSalesSummary');
            return ['this_month' => 0, 'this_count' => 0, 'last_month' => 0];
        }
    }

    /**
     * @param int $orgUID
     * @returns float
     */
    private function _getReceivable(int $orgUID): float {
        try {
            $this->ReadDb->select('COALESCE(SUM(PendingBalance), 0) AS total');
            $this->ReadDb->from('Customers.CustOpeningBalanceTbl');
            $this->ReadDb->where(['OrgUID' => $orgUID, 'PendingBalType' => 'Debit', 'IsDeleted' => 0]);
            $this->ReadDb->where('PendingBalance >', 0);
            $row = $this->_row();
            return (float)($row->total ?? 0);
        } catch (Exception $e) {
            notifyError($e, 'Assistant_model::_getReceivable');
            return 0.0;
        }
    }

    /**
     * @param int $orgUID
     * @returns float
     */
    private function _getPayable(int $orgUID): float {
        try {
            $this->ReadDb->select('COALESCE(SUM(PendingBalance), 0) AS total');
            $this->ReadDb->from('Vendors.VendOpeningBalanceTbl');
            $this->ReadDb->where(['OrgUID' => $orgUID, 'PendingBalType' => 'Credit', 'IsDeleted' => 0]);
            $this->ReadDb->where('PendingBalance >', 0);
            $row = $this->_row();
            return (float)($row->total ?? 0);
        } catch (Exception $e) {
            notifyError($e, 'Assistant_model::_getPayable');
            return 0.0;
        }
    }

    /**
     * @param int $orgUID
     * @param int $branchUID
     * @returns int
     */
    private function _getOverdueCount(int $orgUID, int $branchUID): int {
        try {
            $this->ReadDb->select('COUNT(*) AS cnt');
            $this->ReadDb->from('Transaction.TransactionsTbl T');
            $this->ReadDb->join(
                'Transaction.TransDetailTbl D',
                'D.TransUID = T.TransUID AND D.FinancialYear = YEAR(T.TransDate)',
                'left'
            );
            $this->ReadDb->where(['T.OrgUID' => $orgUID, 'T.ModuleUID' => 103, 'T.IsDeleted' => 0]);
            if ($branchUID > 0) { $this->ReadDb->where('T.BranchUID', $branchUID); }
            $this->ReadDb->where_in('T.DocStatus', ['Issued', 'Partial']);
            $this->ReadDb->where('D.ValidityDate <', date('Y-m-d'));
            $this->ReadDb->where('D.ValidityDate IS NOT NULL', null, false);
            $this->ReadDb->where('T.BalanceAmount >', 0);
            $row = $this->_row();
            return (int)($row->cnt ?? 0);
        } catch (Exception $e) {
            notifyError($e, 'Assistant_model::_getOverdueCount');
            return 0;
        }
    }

    /**
     * @param int $orgUID
     * @returns int
     */
    private function _getLowStockCount(int $orgUID): int {
        try {
            $this->ReadDb->select('COUNT(*) AS cnt');
            $this->ReadDb->from('Products.ProductTbl P');
            $this->ReadDb->join('Products.ProductStockTbl PS', 'PS.ProductUID = P.ProductUID', 'left');
            $this->ReadDb->where(['P.OrgUID' => $orgUID, 'P.IsDeleted' => 0, 'P.IsActive' => 1]);
            $this->ReadDb->where('P.LowStockAlertAt >', 0);
            $this->ReadDb->where('COALESCE(PS.AvailableQty, 0) <= P.LowStockAlertAt', null, false);
            $row = $this->_row();
            return (int)($row->cnt ?? 0);
        } catch (Exception $e) {
            notifyError($e, 'Assistant_model::_getLowStockCount');
            return 0;
        }
    }

    /**
     * @param int $orgUID
     * @param int $branchUID
     * @returns array
     */
    private function _getTopCustomers(int $orgUID, int $branchUID): array {
        try {
            $thisStart = date('Y-m-01');
            $this->ReadDb->select('C.Name, COALESCE(SUM(T.NetAmount), 0) AS total');
            $this->ReadDb->from('Transaction.TransactionsTbl T');
            $this->ReadDb->join('Customers.CustomerTbl C', 'C.CustomerUID = T.PartyUID', 'left');
            $this->ReadDb->where([
                'T.OrgUID'    => $orgUID,
                'T.ModuleUID' => 103,
                'T.IsDeleted' => 0,
                'T.PartyType' => 'C',
            ]);
            if ($branchUID > 0) { $this->ReadDb->where('T.BranchUID', $branchUID); }
            $this->ReadDb->where_not_in('T.DocStatus', ['Draft', 'Cancelled', 'Rejected']);
            $this->ReadDb->where('T.TransDate >=', $thisStart);
            $this->ReadDb->group_by('T.PartyUID');
            $this->ReadDb->order_by('total', 'DESC');
            $this->ReadDb->limit(5);
            return $this->_result();
        } catch (Exception $e) {
            notifyError($e, 'Assistant_model::_getTopCustomers');
            return [];
        }
    }

    /**
     * @param int $orgUID
     * @param int $branchUID
     * @returns array
     */
    private function _getTopProducts(int $orgUID, int $branchUID): array {
        try {
            $thisStart = date('Y-m-01');
            $this->ReadDb->select('P.ItemName, COALESCE(SUM(TI.Quantity), 0) AS qty_sold');
            $this->ReadDb->from('Transaction.TransProductsTbl TI');
            $this->ReadDb->join('Transaction.TransactionsTbl T', 'T.TransUID = TI.TransUID', 'inner');
            $this->ReadDb->join('Products.ProductTbl P', 'P.ProductUID = TI.ProductUID', 'left');
            $this->ReadDb->where(['T.OrgUID' => $orgUID, 'T.ModuleUID' => 103, 'T.IsDeleted' => 0]);
            if ($branchUID > 0) { $this->ReadDb->where('T.BranchUID', $branchUID); }
            $this->ReadDb->where_not_in('T.DocStatus', ['Draft', 'Cancelled', 'Rejected']);
            $this->ReadDb->where('T.TransDate >=', $thisStart);
            $this->ReadDb->group_by('TI.ProductUID');
            $this->ReadDb->order_by('qty_sold', 'DESC');
            $this->ReadDb->limit(5);
            return $this->_result();
        } catch (Exception $e) {
            notifyError($e, 'Assistant_model::_getTopProducts');
            return [];
        }
    }

    /**
     * @param int $orgUID
     * @param int $branchUID
     * @returns array
     */
    private function _getTodayActivity(int $orgUID, int $branchUID): array {
        try {
            $this->ReadDb->select("
                COUNT(*) AS total,
                COUNT(CASE WHEN ModuleUID = 103 THEN 1 END) AS invoices,
                COUNT(CASE WHEN ModuleUID = 104 THEN 1 END) AS purchases,
                COUNT(CASE WHEN ModuleUID = 106 THEN 1 END) AS payments
            ");
            $this->ReadDb->from('Transaction.TransactionsTbl');
            $this->ReadDb->where(['OrgUID' => $orgUID, 'IsDeleted' => 0]);
            if ($branchUID > 0) { $this->ReadDb->where('BranchUID', $branchUID); }
            $this->ReadDb->where('DATE(CreatedOn)', date('Y-m-d'));
            $row = $this->_row();
            return [
                'total'    => (int)($row->total    ?? 0),
                'invoices' => (int)($row->invoices  ?? 0),
                'purchases'=> (int)($row->purchases ?? 0),
                'payments' => (int)($row->payments  ?? 0),
            ];
        } catch (Exception $e) {
            notifyError($e, 'Assistant_model::_getTodayActivity');
            return ['total' => 0, 'invoices' => 0, 'purchases' => 0, 'payments' => 0];
        }
    }
}
