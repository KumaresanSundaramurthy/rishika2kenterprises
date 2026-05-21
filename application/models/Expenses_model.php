<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Expenses_model extends CI_Model {

    private $ReadDb;

    public function __construct() {
        parent::__construct();
        $this->ReadDb = $this->load->database('ReadDB', TRUE);
    }

    // ── Paginated list ───────────────────────────────────────────────────────
    public function getExpenseList($orgUID, $filter, $limit, $offset) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select(
                'e.ExpenseUID, e.ExpenseNumber, e.ExpenseDate, e.Amount, e.NetAmount,
                 e.TaxApplicable, e.TaxAmount, e.TDSApplicable, e.TDSAmount,
                 e.CategoryUID, ec.CategoryName,
                 e.Notes, e.DocStatus, e.IsPaid,
                 e.PaymentTypeUID, pt.PaymentTypeName,
                 e.BankAccountUID, ba.AccountName AS BankAccountName,
                 e.PaymentDate,
                 e.UpdatedOn,
                 CONCAT(IFNULL(u.FirstName,\'\'), \' \', IFNULL(u.LastName,\'\')) AS UpdatedBy'
            );
            $this->ReadDb->from('Transaction.ExpensesTbl e');
            $this->ReadDb->join('Transaction.ExpenseCategoryTbl ec', 'ec.CategoryUID = e.CategoryUID AND ec.IsDeleted = 0', 'left');
            $this->ReadDb->join('Transaction.PaymentTypesTbl pt',    'pt.PaymentTypeUID = e.PaymentTypeUID',              'left');
            $this->ReadDb->join('Transaction.OrgBankAccountsTbl ba', 'ba.BankAccountUID = e.BankAccountUID AND ba.IsDeleted = 0', 'left');
            $this->ReadDb->join('Users.UserTbl u',                   'u.UserUID = e.UpdatedBy',                          'left');
            $this->ReadDb->where('e.OrgUID',    $orgUID);
            $this->ReadDb->where('e.IsDeleted', 0);
            $this->_applyFilters($filter);
            $this->ReadDb->order_by('e.ExpenseUID', 'DESC');
            $this->ReadDb->limit($limit, $offset);
            $query = $this->ReadDb->get();
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            log_message('error', 'Expenses_model::getExpenseList — ' . $e->getMessage());
            return [];
        }
    }

    // ── Count ────────────────────────────────────────────────────────────────
    public function getExpenseCount($orgUID, $filter) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('COUNT(*) AS cnt');
            $this->ReadDb->from('Transaction.ExpensesTbl e');
            $this->ReadDb->join('Transaction.ExpenseCategoryTbl ec', 'ec.CategoryUID = e.CategoryUID AND ec.IsDeleted = 0', 'left');
            $this->ReadDb->where('e.OrgUID',    $orgUID);
            $this->ReadDb->where('e.IsDeleted', 0);
            $this->_applyFilters($filter);
            $query = $this->ReadDb->get();
            $row   = $query ? $query->row() : null;
            return $row ? (int)$row->cnt : 0;
        } catch (Exception $e) {
            log_message('error', 'Expenses_model::getExpenseCount — ' . $e->getMessage());
            return 0;
        }
    }

    // ── Single record ────────────────────────────────────────────────────────
    public function getExpenseById($expenseUID, $orgUID) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select(
                'e.ExpenseUID, e.ExpenseNumber, e.ExpenseDate, e.Amount, e.NetAmount,
                 e.TaxApplicable, e.TaxPercentage, e.TaxAmount,
                 e.TDSApplicable, e.TDSPercentage, e.TDSAmount,
                 e.CategoryUID, ec.CategoryName,
                 e.Notes, e.DocStatus, e.IsPaid,
                 e.PaymentTypeUID, pt.PaymentTypeName,
                 e.BankAccountUID, ba.AccountName AS BankAccountName,
                 e.PaymentDate, e.PaymentNotes,
                 e.CreatedOn, e.UpdatedOn,
                 CONCAT(u.FirstName, \' \', IFNULL(u.LastName, \'\')) AS UpdatedByName'
            );
            $this->ReadDb->from('Transaction.ExpensesTbl e');
            $this->ReadDb->join('Transaction.ExpenseCategoryTbl ec', 'ec.CategoryUID = e.CategoryUID AND ec.IsDeleted = 0', 'left');
            $this->ReadDb->join('Transaction.PaymentTypesTbl pt',    'pt.PaymentTypeUID = e.PaymentTypeUID',              'left');
            $this->ReadDb->join('Transaction.OrgBankAccountsTbl ba', 'ba.BankAccountUID = e.BankAccountUID AND ba.IsDeleted = 0', 'left');
            $this->ReadDb->join('Users.UserTbl u',                   'u.UserUID = e.UpdatedBy',                          'left');
            $this->ReadDb->where('e.ExpenseUID', $expenseUID);
            $this->ReadDb->where('e.OrgUID',     $orgUID);
            $this->ReadDb->where('e.IsDeleted',  0);
            $query = $this->ReadDb->get();
            return $query ? $query->row() : null;
        } catch (Exception $e) {
            log_message('error', 'Expenses_model::getExpenseById — ' . $e->getMessage());
            return null;
        }
    }

    // ── Summary stats for stat cards ─────────────────────────────────────────
    public function getExpenseSummaryStats($orgUID) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('e.DocStatus, COUNT(*) AS cnt, SUM(e.NetAmount) AS total');
            $this->ReadDb->from('Transaction.ExpensesTbl e');
            $this->ReadDb->where('e.OrgUID',    $orgUID);
            $this->ReadDb->where('e.IsDeleted', 0);
            $this->ReadDb->group_by('e.DocStatus');
            $query  = $this->ReadDb->get();
            $rows   = $query ? $query->result() : [];
            $result = [];
            foreach ($rows as $r) {
                $result[$r->DocStatus] = ['count' => (int)$r->cnt, 'amount' => (float)$r->total];
            }
            return $result;
        } catch (Exception $e) {
            log_message('error', 'Expenses_model::getExpenseSummaryStats — ' . $e->getMessage());
            return [];
        }
    }

    // ── Categories (org-specific + system defaults) ──────────────────────────
    public function getCategories($orgUID) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('CategoryUID, CategoryName');
            $this->ReadDb->from('Transaction.ExpenseCategoryTbl');
            $this->ReadDb->where('IsDeleted', 0);
            $this->ReadDb->where('IsActive',  1);
            $this->ReadDb->group_start();
            $this->ReadDb->where('OrgUID', $orgUID);
            $this->ReadDb->or_where('OrgUID IS NULL');
            $this->ReadDb->group_end();
            $this->ReadDb->order_by('CategoryName', 'ASC');
            $query = $this->ReadDb->get();
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            log_message('error', 'Expenses_model::getCategories — ' . $e->getMessage());
            return [];
        }
    }

    // ── Payment types ────────────────────────────────────────────────────────
    public function getPaymentTypes() {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('PaymentTypeUID, PaymentTypeName');
            $this->ReadDb->from('Transaction.PaymentTypesTbl');
            $this->ReadDb->where('IsActive', 1);
            $this->ReadDb->order_by('SortOrder', 'ASC');
            $query = $this->ReadDb->get();
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            log_message('error', 'Expenses_model::getPaymentTypes — ' . $e->getMessage());
            return [];
        }
    }

    // ── Bank accounts for org ────────────────────────────────────────────────
    public function getBankAccounts($orgUID) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('BankAccountUID, AccountName, BankName, IsDefault');
            $this->ReadDb->from('Transaction.OrgBankAccountsTbl');
            $this->ReadDb->where('OrgUID',    $orgUID);
            $this->ReadDb->where('IsDeleted', 0);
            $this->ReadDb->where('IsActive',  1);
            $this->ReadDb->order_by('IsDefault', 'DESC');
            $query = $this->ReadDb->get();
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            log_message('error', 'Expenses_model::getBankAccounts — ' . $e->getMessage());
            return [];
        }
    }

    // ── Default cash account for ledger entries ──────────────────────────────
    public function getCashAccount($orgUID) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('BankAccountUID');
            $this->ReadDb->from('Transaction.OrgBankAccountsTbl');
            $this->ReadDb->where('OrgUID',    $orgUID);
            $this->ReadDb->where('IsCash',    1);
            $this->ReadDb->where('IsDeleted', 0);
            $this->ReadDb->where('IsActive',  1);
            $this->ReadDb->limit(1);
            $query = $this->ReadDb->get();
            return $query ? $query->row() : null;
        } catch (Exception $e) {
            log_message('error', 'Expenses_model::getCashAccount — ' . $e->getMessage());
            return null;
        }
    }

    // ── Private filter helper ────────────────────────────────────────────────
    private function _applyFilters($filter) {
        $status = $filter['Status'] ?? 'All';
        if ($status && $status !== 'All') {
            $this->ReadDb->where('e.DocStatus', $status);
        }
        if (!empty($filter['DateFrom'])) {
            $this->ReadDb->where('e.ExpenseDate >=', $filter['DateFrom']);
        }
        if (!empty($filter['DateTo'])) {
            $this->ReadDb->where('e.ExpenseDate <=', $filter['DateTo']);
        }
        if (!empty($filter['Name'])) {
            $term = $this->ReadDb->escape_like_str($filter['Name']);
            $this->ReadDb->group_start();
            $this->ReadDb->like('e.ExpenseNumber', $term, 'both');
            $this->ReadDb->or_like('ec.CategoryName', $term, 'both');
            $this->ReadDb->or_like('e.Notes', $term, 'both');
            $this->ReadDb->group_end();
        }
    }
}
