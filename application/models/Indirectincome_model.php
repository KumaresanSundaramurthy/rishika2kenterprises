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
                 i.PaidAmount, i.BalanceAmount,
                 i.TaxApplicable, i.TaxAmount,
                 i.CategoryUID, ic.CategoryName,
                 i.Notes, i.DocStatus, i.IsReceived,
                 i.UpdatedOn,
                 CONCAT(IFNULL(u.FirstName,\'\'), \' \', IFNULL(u.LastName,\'\')) AS UpdatedBy,
                 CONCAT(IFNULL(uc.FirstName,\'\'), \' \', IFNULL(uc.LastName,\'\')) AS CreatedByName,
                 (SELECT COUNT(*) FROM Transaction.ExpenseIncomeAttachmentsTbl a WHERE a.SourceUID = i.IncomeUID AND a.SourceType = \'IndirectIncome\' AND a.IsDeleted = 0) AS AttachCount,
                 IFNULL(PayInfo.PaymentCount, 0) AS PaymentCount,
                 PayInfo.PaymentModes,
                 PayInfo.PayBankName,
                 PayInfo.PayAccountNumber,
                 IFNULL(PayInfo.PaymentAttachmentCount, 0) AS PaymentAttachmentCount',
                FALSE
            );
            $this->ReadDb->from('Transaction.IndirectIncomeTbl i');
            $this->ReadDb->join('Transaction.IndirectIncomeCategoryTbl ic', 'ic.CategoryUID = i.CategoryUID AND ic.IsDeleted = 0', 'left');
            $this->ReadDb->join('Users.UserTbl u',                          'u.UserUID = i.UpdatedBy',                            'left');
            $this->ReadDb->join('Users.UserTbl uc',                         'uc.UserUID = i.CreatedBy',                           'left');
            $this->ReadDb->join(
                "(SELECT P.TransUID,
                    COUNT(*) AS PaymentCount,
                    GROUP_CONCAT(PT.Name ORDER BY P.PaymentUID ASC SEPARATOR ',') AS PaymentModes,
                    MAX(CASE WHEN PT.IsCash = 0 THEN BA.BankName ELSE NULL END) AS PayBankName,
                    MAX(CASE WHEN PT.IsCash = 0 THEN BA.AccountNumber ELSE NULL END) AS PayAccountNumber,
                    (SELECT COUNT(*) FROM Transaction.PaymentAttachmentsTbl PA
                     WHERE PA.PaymentUID IN (SELECT PaymentUID FROM Transaction.PaymentsTbl P2 WHERE P2.TransUID = P.TransUID AND P2.SourceType = 'IndirectIncome' AND P2.IsDeleted = 0 AND P2.IsActive = 1)
                     AND PA.IsDeleted = 0 AND PA.IsActive = 1) AS PaymentAttachmentCount
                 FROM Transaction.PaymentsTbl P
                 JOIN Global.PaymentTypesTbl PT ON PT.PaymentTypeUID = P.PaymentTypeUID
                 LEFT JOIN Organisation.OrgBankAccountsTbl BA ON BA.BankAccountUID = P.BankAccountUID
                 WHERE P.IsDeleted = 0 AND P.IsActive = 1 AND P.SourceType = 'IndirectIncome'
                 GROUP BY P.TransUID) AS PayInfo",
                'PayInfo.TransUID = i.IncomeUID',
                'left'
            );
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
                 i.PaidAmount, i.BalanceAmount,
                 i.TaxApplicable, i.TaxPercentage, i.TaxAmount,
                 i.CategoryUID, ic.CategoryName,
                 i.Notes, i.DocStatus, i.IsReceived,
                 py.PaymentUID, py.PaymentTypeUID, pt.Name AS PaymentTypeName,
                 py.BankAccountUID, ba.AccountName AS BankAccountName, ba.BankName, ba.AccountNumber,
                 py.PaymentDate, py.UniqueNumber AS PaymentReference, py.Notes AS PaymentNotes,
                 i.CreatedOn, i.UpdatedOn,
                 CONCAT(u.FirstName, \' \', IFNULL(u.LastName, \'\')) AS UpdatedByName'
            );
            $this->ReadDb->from('Transaction.IndirectIncomeTbl i');
            $this->ReadDb->join('Transaction.IndirectIncomeCategoryTbl ic', 'ic.CategoryUID = i.CategoryUID AND ic.IsDeleted = 0',                                    'left');
            $this->ReadDb->join('Transaction.PaymentsTbl py',               'py.TransUID = i.IncomeUID AND py.SourceType = \'IndirectIncome\' AND py.IsDeleted = 0', 'left');
            $this->ReadDb->join('Global.PaymentTypesTbl pt',           'pt.PaymentTypeUID = py.PaymentTypeUID',                                                   'left');
            $this->ReadDb->join('Organisation.OrgBankAccountsTbl ba',        'ba.BankAccountUID = py.BankAccountUID AND ba.IsDeleted = 0',                             'left');
            $this->ReadDb->join('Users.UserTbl u',                          'u.UserUID = i.UpdatedBy',                                                                'left');
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
            $this->ReadDb->select('PaymentTypeUID, Name AS PaymentTypeName, IsCash');
            $this->ReadDb->from('Global.PaymentTypesTbl');
            $this->ReadDb->where('IsActive', 1);
            $this->ReadDb->order_by('PaymentTypeUID', 'ASC');
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
            $this->ReadDb->from('Organisation.OrgBankAccountsTbl');
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
            $this->ReadDb->from('Organisation.OrgBankAccountsTbl');
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

    // ── Category list (paginated, for manager modal) ─────────────────────────
    public function getCategoryList($orgUID, $search, $limit, $offset) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('CategoryUID, CategoryName, OrgUID, IsDefault');
            $this->ReadDb->from('Transaction.IndirectIncomeCategoryTbl');
            $this->ReadDb->where('IsDeleted', 0);
            $this->ReadDb->where('IsActive',  1);
            $this->ReadDb->group_start();
            $this->ReadDb->where('OrgUID', $orgUID);
            $this->ReadDb->or_where('OrgUID IS NULL');
            $this->ReadDb->group_end();
            if (!empty($search)) {
                $this->ReadDb->like('CategoryName', $this->ReadDb->escape_like_str($search), 'both');
            }
            $this->ReadDb->order_by('IsDefault', 'DESC');
            $this->ReadDb->order_by('CategoryName', 'ASC');
            $this->ReadDb->limit($limit, $offset);
            $query = $this->ReadDb->get();
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            log_message('error', 'Indirectincome_model::getCategoryList — ' . $e->getMessage());
            return [];
        }
    }

    // ── Category count ───────────────────────────────────────────────────────
    public function getCategoryCount($orgUID, $search) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('COUNT(*) AS cnt');
            $this->ReadDb->from('Transaction.IndirectIncomeCategoryTbl');
            $this->ReadDb->where('IsDeleted', 0);
            $this->ReadDb->where('IsActive',  1);
            $this->ReadDb->group_start();
            $this->ReadDb->where('OrgUID', $orgUID);
            $this->ReadDb->or_where('OrgUID IS NULL');
            $this->ReadDb->group_end();
            if (!empty($search)) {
                $this->ReadDb->like('CategoryName', $this->ReadDb->escape_like_str($search), 'both');
            }
            $query = $this->ReadDb->get();
            $row   = $query ? $query->row() : null;
            return $row ? (int)$row->cnt : 0;
        } catch (Exception $e) {
            log_message('error', 'Indirectincome_model::getCategoryCount — ' . $e->getMessage());
            return 0;
        }
    }

    // ── Attachments for a single income record ───────────────────────────────
    public function getIncomeAttachments($incomeUID, $orgUID) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('AttachUID, FileName, FilePath, FileType, FileSize, SortOrder');
            $this->ReadDb->from('Transaction.ExpenseIncomeAttachmentsTbl');
            $this->ReadDb->where('SourceUID',  $incomeUID);
            $this->ReadDb->where('SourceType', 'IndirectIncome');
            $this->ReadDb->where('OrgUID',     $orgUID);
            $this->ReadDb->where('IsDeleted',  0);
            $this->ReadDb->where('IsActive',   1);
            $this->ReadDb->order_by('SortOrder', 'ASC');
            $query = $this->ReadDb->get();
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            log_message('error', 'Indirectincome_model::getIncomeAttachments — ' . $e->getMessage());
            return [];
        }
    }

    // ── Count existing payment rows for an income (for UniqueNumber suffix) ────
    public function getPaymentCount($transUID, $sourceType, $orgUID) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('COUNT(*) AS cnt');
            $this->ReadDb->from('Transaction.PaymentsTbl');
            $this->ReadDb->where('TransUID',   $transUID);
            $this->ReadDb->where('SourceType', $sourceType);
            $this->ReadDb->where('OrgUID',     $orgUID);
            $this->ReadDb->where('IsDeleted',  0);
            $query = $this->ReadDb->get();
            $row   = $query ? $query->row() : null;
            return $row ? (int)$row->cnt : 0;
        } catch (Exception $e) {
            log_message('error', 'Indirectincome_model::getPaymentCount — ' . $e->getMessage());
            return 0;
        }
    }

    // ── Check if category is in use ──────────────────────────────────────────
    public function isCategoryLinked($categoryUID, $orgUID) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('COUNT(*) AS cnt');
            $this->ReadDb->from('Transaction.IndirectIncomeTbl');
            $this->ReadDb->where('CategoryUID', $categoryUID);
            $this->ReadDb->where('OrgUID',      $orgUID);
            $this->ReadDb->where('IsDeleted',   0);
            $query = $this->ReadDb->get();
            $row   = $query ? $query->row() : null;
            return $row && (int)$row->cnt > 0;
        } catch (Exception $e) {
            log_message('error', 'Indirectincome_model::isCategoryLinked — ' . $e->getMessage());
            return true;
        }
    }

    // ── Private filter helper ────────────────────────────────────────────────
    private function _applyFilters($filter) {
        // StatusList (multi-select) overrides single Status tab
        $statusList = (!empty($filter['StatusList']) && is_array($filter['StatusList']))
            ? array_values(array_filter($filter['StatusList'], function($s) { return !empty(trim($s)); }))
            : [];
        if (!empty($statusList)) {
            $this->ReadDb->where_in('i.DocStatus', $statusList);
        } else {
            $status = $filter['Status'] ?? 'All';
            if ($status && $status !== 'All') {
                $this->ReadDb->where('i.DocStatus', $status);
            }
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
        if (!empty($filter['PaymentTypeUIDs'])) {
            $uids = array_values(array_filter(array_map('intval', (array)$filter['PaymentTypeUIDs']), function($u) { return $u > 0; }));
            if (!empty($uids)) {
                $in = implode(',', $uids);
                $this->ReadDb->where("EXISTS (SELECT 1 FROM Transaction.PaymentsTbl _py WHERE _py.TransUID = i.IncomeUID AND _py.SourceType = 'IndirectIncome' AND _py.IsDeleted = 0 AND _py.PaymentTypeUID IN ($in))", NULL, FALSE);
            }
        }
        if (!empty($filter['UpdatedByUIDs'])) {
            $uids = array_values(array_filter(array_map('intval', (array)$filter['UpdatedByUIDs']), function($u) { return $u > 0; }));
            if (!empty($uids)) {
                $this->ReadDb->where_in('i.UpdatedBy', $uids);
            }
        }
    }
}
