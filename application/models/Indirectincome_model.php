<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Indirectincome_model extends CI_Model {

    private $ReadDb;

    public function __construct() {
        parent::__construct();
        $this->ReadDb = $this->load->database('ReadDB', TRUE);
    }

    // ── Paginated list ───────────────────────────────────────────────────────
    public function getIncomeList($orgUID, $filter, $limit, $offset) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select(
                'i.IncomeUID, i.IncomeNumber, i.IncomeDate, i.Amount, i.NetAmount,
                 i.TaxApplicable, i.TaxAmount,
                 i.CategoryUID, ic.CategoryName,
                 i.Notes, i.DocStatus, i.IsReceived,
                 i.PaymentTypeUID, pt.PaymentTypeName,
                 i.BankAccountUID, ba.AccountName AS BankAccountName,
                 i.PaymentDate,
                 i.UpdatedOn,
                 CONCAT(IFNULL(u.FirstName,\'\'), \' \', IFNULL(u.LastName,\'\')) AS UpdatedBy'
            );
            $this->ReadDb->from('Transaction.IndirectIncomeTbl i');
            $this->ReadDb->join('Transaction.IndirectIncomeCategoryTbl ic', 'ic.CategoryUID = i.CategoryUID AND ic.IsDeleted = 0', 'left');
            $this->ReadDb->join('Transaction.PaymentTypesTbl pt',           'pt.PaymentTypeUID = i.PaymentTypeUID',              'left');
            $this->ReadDb->join('Transaction.OrgBankAccountsTbl ba',        'ba.BankAccountUID = i.BankAccountUID AND ba.IsDeleted = 0', 'left');
            $this->ReadDb->join('Users.UserTbl u',                          'u.UserUID = i.UpdatedBy',                          'left');
            $this->ReadDb->where('i.OrgUID',    $orgUID);
            $this->ReadDb->where('i.IsDeleted', 0);
            $this->_applyFilters($filter);
            $this->ReadDb->order_by('i.IncomeUID', 'DESC');
            $this->ReadDb->limit($limit, $offset);
            $query = $this->ReadDb->get();
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            log_message('error', 'Indirectincome_model::getIncomeList — ' . $e->getMessage());
            return [];
        }
    }

    // ── Count ────────────────────────────────────────────────────────────────
    public function getIncomeCount($orgUID, $filter) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('COUNT(*) AS cnt');
            $this->ReadDb->from('Transaction.IndirectIncomeTbl i');
            $this->ReadDb->join('Transaction.IndirectIncomeCategoryTbl ic', 'ic.CategoryUID = i.CategoryUID AND ic.IsDeleted = 0', 'left');
            $this->ReadDb->where('i.OrgUID',    $orgUID);
            $this->ReadDb->where('i.IsDeleted', 0);
            $this->_applyFilters($filter);
            $query = $this->ReadDb->get();
            $row   = $query ? $query->row() : null;
            return $row ? (int)$row->cnt : 0;
        } catch (Exception $e) {
            log_message('error', 'Indirectincome_model::getIncomeCount — ' . $e->getMessage());
            return 0;
        }
    }

    // ── Single record ────────────────────────────────────────────────────────
    public function getIncomeById($incomeUID, $orgUID) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select(
                'i.IncomeUID, i.IncomeNumber, i.IncomeDate, i.Amount, i.NetAmount,
                 i.TaxApplicable, i.TaxPercentage, i.TaxAmount,
                 i.CategoryUID, ic.CategoryName,
                 i.Notes, i.DocStatus, i.IsReceived,
                 i.PaymentTypeUID, pt.PaymentTypeName,
                 i.BankAccountUID, ba.AccountName AS BankAccountName,
                 i.PaymentDate, i.PaymentNotes,
                 i.CreatedOn, i.UpdatedOn,
                 CONCAT(u.FirstName, \' \', IFNULL(u.LastName, \'\')) AS UpdatedByName'
            );
            $this->ReadDb->from('Transaction.IndirectIncomeTbl i');
            $this->ReadDb->join('Transaction.IndirectIncomeCategoryTbl ic', 'ic.CategoryUID = i.CategoryUID AND ic.IsDeleted = 0', 'left');
            $this->ReadDb->join('Transaction.PaymentTypesTbl pt',           'pt.PaymentTypeUID = i.PaymentTypeUID',              'left');
            $this->ReadDb->join('Transaction.OrgBankAccountsTbl ba',        'ba.BankAccountUID = i.BankAccountUID AND ba.IsDeleted = 0', 'left');
            $this->ReadDb->join('Users.UserTbl u',                          'u.UserUID = i.UpdatedBy',                          'left');
            $this->ReadDb->where('i.IncomeUID', $incomeUID);
            $this->ReadDb->where('i.OrgUID',    $orgUID);
            $this->ReadDb->where('i.IsDeleted', 0);
            $query = $this->ReadDb->get();
            return $query ? $query->row() : null;
        } catch (Exception $e) {
            log_message('error', 'Indirectincome_model::getIncomeById — ' . $e->getMessage());
            return null;
        }
    }

    // ── Summary stats for stat cards ─────────────────────────────────────────
    public function getIncomeSummaryStats($orgUID) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('i.DocStatus, COUNT(*) AS cnt, SUM(i.NetAmount) AS total');
            $this->ReadDb->from('Transaction.IndirectIncomeTbl i');
            $this->ReadDb->where('i.OrgUID',    $orgUID);
            $this->ReadDb->where('i.IsDeleted', 0);
            $this->ReadDb->group_by('i.DocStatus');
            $query  = $this->ReadDb->get();
            $rows   = $query ? $query->result() : [];
            $result = [];
            foreach ($rows as $r) {
                $result[$r->DocStatus] = ['count' => (int)$r->cnt, 'amount' => (float)$r->total];
            }
            return $result;
        } catch (Exception $e) {
            log_message('error', 'Indirectincome_model::getIncomeSummaryStats — ' . $e->getMessage());
            return [];
        }
    }

    // ── Categories ───────────────────────────────────────────────────────────
    public function getCategories($orgUID) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('CategoryUID, CategoryName');
            $this->ReadDb->from('Transaction.IndirectIncomeCategoryTbl');
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
            log_message('error', 'Indirectincome_model::getCategories — ' . $e->getMessage());
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
            log_message('error', 'Indirectincome_model::getPaymentTypes — ' . $e->getMessage());
            return [];
        }
    }

    // ── Bank accounts ────────────────────────────────────────────────────────
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
            log_message('error', 'Indirectincome_model::getBankAccounts — ' . $e->getMessage());
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
            log_message('error', 'Indirectincome_model::getCashAccount — ' . $e->getMessage());
            return null;
        }
    }

    // ── Private filter helper ────────────────────────────────────────────────
    private function _applyFilters($filter) {
        $status = $filter['Status'] ?? 'All';
        if ($status && $status !== 'All') {
            $this->ReadDb->where('i.DocStatus', $status);
        }
        if (!empty($filter['DateFrom'])) {
            $this->ReadDb->where('i.IncomeDate >=', $filter['DateFrom']);
        }
        if (!empty($filter['DateTo'])) {
            $this->ReadDb->where('i.IncomeDate <=', $filter['DateTo']);
        }
        if (!empty($filter['Name'])) {
            $term = $this->ReadDb->escape_like_str($filter['Name']);
            $this->ReadDb->group_start();
            $this->ReadDb->like('i.IncomeNumber', $term, 'both');
            $this->ReadDb->or_like('ic.CategoryName', $term, 'both');
            $this->ReadDb->or_like('i.Notes', $term, 'both');
            $this->ReadDb->group_end();
        }
    }
}
