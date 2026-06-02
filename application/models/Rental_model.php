<?php defined('BASEPATH') or exit('No direct script access allowed');

class Rental_model extends CI_Model {

    private $ReadDb;

    public function __construct() {
        parent::__construct();
        $this->ReadDb = $this->load->database('ReadDB', TRUE);
    }

    // ── Stat cards ────────────────────────────────────────────────────────────

    public function getRentalStats($orgUID) {
        $this->ReadDb->db_debug = FALSE;
        $sql = "
            SELECT
                COUNT(*) AS totalCount,
                SUM(CASE WHEN r.RentalStatus IN ('Active','PartiallyReturned','Overdue') THEN 1 ELSE 0 END) AS activeCount,
                SUM(CASE WHEN r.RentalStatus = 'Overdue' THEN 1 ELSE 0 END)  AS overdueCount,
                SUM(CASE WHEN r.RentalStatus = 'Closed'  THEN 1 ELSE 0 END)  AS closedCount,
                SUM(r.GrandTotal)         AS totalRevenue,
                SUM(r.BalanceAmount)      AS totalBalance,
                SUM(r.DepositCollected)   AS totalDeposit
            FROM Transaction.RentalMasterTbl r
            WHERE r.OrgUID = ? AND r.IsDeleted = 0
        ";
        $query = $this->ReadDb->query($sql, [(int)$orgUID]);
        return $query->row();
    }

    // ── Paginated list ────────────────────────────────────────────────────────

    public function getRentalList($orgUID, $filter, $limit, $offset) {
        $this->ReadDb->db_debug = FALSE;

        $this->ReadDb->select([
            'r.RentalUID',
            'r.RentalNumber',
            'r.RentalStartDateTime',
            'r.ReturnDueDateTime',
            'r.ActualReturnDateTime',
            'r.RentalStatus',
            'r.PaymentStatus',
            'r.TotalRentalAmount',
            'r.ExtraCharges',
            'r.GrandTotal',
            'r.TotalPaid',
            'r.BalanceAmount',
            'r.DepositCollected',
            'r.Notes',
            'r.UpdatedOn',
            'c.Name      AS CustomerName',
            'c.MobileNumber AS CustomerMobile',
        ]);
        $this->ReadDb->from('Transaction.RentalMasterTbl r');
        $this->ReadDb->join('Customers.CustomerTbl c', 'c.CustomerUID = r.CustomerUID AND c.IsDeleted = 0', 'left');
        $this->ReadDb->where(['r.OrgUID' => (int)$orgUID, 'r.IsDeleted' => 0]);

        $this->_applyFilter($filter);

        $this->ReadDb->order_by('r.RentalUID', 'DESC');
        $this->ReadDb->limit((int)$limit, (int)$offset);

        $query = $this->ReadDb->get();
        $error = $this->ReadDb->error();
        if ($error['code']) throw new Exception($error['message']);
        $rows = $query->result();

        foreach ($rows as $row) {
            $row->Machines = $this->_getMachineSummary($row->RentalUID);
        }
        return $rows;
    }

    public function getRentalCount($orgUID, $filter) {
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->select('COUNT(*) AS cnt');
        $this->ReadDb->from('Transaction.RentalMasterTbl r');
        $this->ReadDb->join('Customers.CustomerTbl c', 'c.CustomerUID = r.CustomerUID AND c.IsDeleted = 0', 'left');
        $this->ReadDb->where(['r.OrgUID' => (int)$orgUID, 'r.IsDeleted' => 0]);
        $this->_applyFilter($filter);
        $row = $this->ReadDb->get()->row();
        return $row ? (int)$row->cnt : 0;
    }

    private function _applyFilter($filter) {
        if (!empty($filter['Status']) && $filter['Status'] !== 'All') {
            if ($filter['Status'] === 'Active') {
                $this->ReadDb->where_in('r.RentalStatus', ['Active', 'PartiallyReturned', 'Overdue']);
            } else {
                $this->ReadDb->where('r.RentalStatus', $filter['Status']);
            }
        }
        if (!empty($filter['Search'])) {
            $term = $this->ReadDb->escape_like_str($filter['Search']);
            $this->ReadDb->group_start();
            $this->ReadDb->like('r.RentalNumber', $term, 'both');
            $this->ReadDb->or_like('c.Name', $term, 'both');
            $this->ReadDb->or_like('c.MobileNumber', $term, 'both');
            $this->ReadDb->group_end();
        }
    }

    private function _getMachineSummary($rentalUID) {
        $this->ReadDb->db_debug = FALSE;
        $sql = "
            SELECT ri.RentalItemUID, p.ItemName, ri.Qty, ri.RentalType,
                   ri.BaseRentalCharge, ri.TotalCharge, ri.ItemStatus,
                   ri.HourlyRate, ri.HalfDayRate, ri.FullDayRate,
                   ri.FixedPackageRate, ri.ExtraHourRate,
                   ri.LateReturnChargePerHour, ri.SecurityDeposit,
                   ri.ReturnedQty, ri.DamagedQty,
                   ri.ActualReturnDateTime, ri.ActualHours,
                   ri.ExtraHourCharge, ri.LateReturnCharge, ri.DamageCharge
            FROM Transaction.RentalItemsTbl ri
            JOIN Products.ProductTbl p ON p.ProductUID = ri.ProductUID AND p.IsDeleted = 0
            WHERE ri.RentalUID = ? AND ri.IsDeleted = 0
        ";
        return $this->ReadDb->query($sql, [(int)$rentalUID])->result();
    }

    // ── Single rental detail ──────────────────────────────────────────────────

    public function getRentalById($rentalUID, $orgUID) {
        $this->ReadDb->db_debug = FALSE;
        $sql = "
            SELECT r.*, c.Name AS CustomerName, c.MobileNumber AS CustomerMobile,
                   c.EmailAddress AS CustomerEmail
            FROM Transaction.RentalMasterTbl r
            LEFT JOIN Customers.CustomerTbl c ON c.CustomerUID = r.CustomerUID AND c.IsDeleted = 0
            WHERE r.RentalUID = ? AND r.OrgUID = ? AND r.IsDeleted = 0
            LIMIT 1
        ";
        $rental = $this->ReadDb->query($sql, [(int)$rentalUID, (int)$orgUID])->row();
        if ($rental) {
            $rental->Items    = $this->_getMachineSummary($rentalUID);
            $rental->Payments = $this->getRentalPayments($rentalUID, $orgUID);
        }
        return $rental;
    }

    // ── Payments ──────────────────────────────────────────────────────────────

    public function getRentalPayments($rentalUID, $orgUID) {
        $this->ReadDb->db_debug = FALSE;
        $sql = "
            SELECT rp.*, pt.Name AS PaymentTypeName
            FROM Transaction.RentalPaymentsTbl rp
            LEFT JOIN Global.PaymentTypesTbl pt ON pt.PaymentTypeUID = rp.PaymentTypeUID
            WHERE rp.RentalUID = ? AND rp.OrgUID = ? AND rp.IsDeleted = 0
            ORDER BY rp.RentalPaymentUID ASC
        ";
        return $this->ReadDb->query($sql, [(int)$rentalUID, (int)$orgUID])->result();
    }

    // ── Rentable products (for create modal) ──────────────────────────────────

    public function searchRentableProducts($orgUID, $term = '') {
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->select([
            'p.ProductUID', 'p.ItemName', 'p.PartNumber', 'COALESCE(ps.AvailableQty, 0) AS AvailableQuantity',
            'rc.SecurityDeposit', 'rc.HourlyRate', 'rc.HalfDayRate',
            'rc.FullDayRate', 'rc.FixedPackageRate', 'rc.ExtraHourRate',
            'rc.LateReturnChargePerHour', 'rc.MinRentalHours',
        ]);
        $this->ReadDb->from('Products.ProductTbl p');
        $this->ReadDb->join('Products.ProductStockTbl ps', 'ps.ProductUID = p.ProductUID', 'left');
        $this->ReadDb->join(
            'Products.ProductRentalConfigTbl rc',
            'rc.ProductUID = p.ProductUID AND rc.OrgUID = p.OrgUID AND rc.IsDeleted = 0',
            'left'
        );
        $this->ReadDb->where(['p.OrgUID' => (int)$orgUID, 'p.IsDeleted' => 0, 'p.IsActive' => 1, 'p.IsRentable' => 1]);
        if (!empty($term)) {
            $t = $this->ReadDb->escape_like_str($term);
            $this->ReadDb->group_start();
            $this->ReadDb->like('p.ItemName', $t, 'both');
            $this->ReadDb->or_like('p.PartNumber', $t, 'both');
            $this->ReadDb->group_end();
        }
        $this->ReadDb->order_by('p.ItemName', 'ASC');
        $this->ReadDb->limit(50);
        return $this->ReadDb->get()->result();
    }

    // ── Payment types & bank accounts ─────────────────────────────────────────

    public function getPaymentTypes() {
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->select('PaymentTypeUID, Name AS PaymentTypeName, IsCash');
        $this->ReadDb->from('Global.PaymentTypesTbl');
        $this->ReadDb->where('IsActive', 1);
        $this->ReadDb->order_by('PaymentTypeUID', 'ASC');
        $query = $this->ReadDb->get();
        return $query ? $query->result() : [];
    }

    public function getBankAccounts($orgUID) {
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->select('BankAccountUID, AccountName, BankName, IsDefault');
        $this->ReadDb->from('Organisation.OrgBankAccountsTbl');
        $this->ReadDb->where('OrgUID',    (int)$orgUID);
        $this->ReadDb->where('IsCash',    0);
        $this->ReadDb->where('IsDeleted', 0);
        $this->ReadDb->where('IsActive',  1);
        $this->ReadDb->order_by('IsDefault', 'DESC');
        $query = $this->ReadDb->get();
        return $query ? $query->result() : [];
    }

    // ── Rental items (for return modal) ──────────────────────────────────────

    public function getRentalItems($rentalUID, $orgUID) {
        return $this->_getMachineSummary($rentalUID);
    }

}
