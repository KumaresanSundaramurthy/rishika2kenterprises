<?php defined('BASEPATH') or exit('No direct script access allowed');

class Inventory_model extends CI_Model {

    private $ReadDb;
    private $EndReturnData;

    public function __construct() {
        parent::__construct();
        $this->ReadDb = $this->load->database('ReadDB', TRUE);
    }

    // ── Stat cards ────────────────────────────────────────────────────────────

    public function getInventoryStats($orgUID) {

        $this->ReadDb->db_debug = FALSE;
        $sql = "
            SELECT
                SUM(CASE WHEN p.AvailableQuantity > 0 THEN 1 ELSE 0 END)                        AS positiveCount,
                SUM(CASE WHEN p.AvailableQuantity > 0 THEN p.AvailableQuantity ELSE 0 END)       AS positiveQty,
                SUM(CASE WHEN p.AvailableQuantity <= p.LowStockAlertAt THEN 1 ELSE 0 END)        AS lowStockCount,
                SUM(CASE WHEN p.AvailableQuantity <= p.LowStockAlertAt
                         THEN p.AvailableQuantity ELSE 0 END)                                    AS lowStockQty,
                SUM(CASE WHEN p.AvailableQuantity > 0
                         THEN p.AvailableQuantity * p.SellingPrice ELSE 0 END)                   AS saleValue,
                SUM(CASE WHEN p.AvailableQuantity > 0
                         THEN p.AvailableQuantity * p.PurchasePrice ELSE 0 END)                  AS purchaseValue
            FROM Products.ProductTbl p
            WHERE p.OrgUID = ? AND p.IsDeleted = 0 AND p.IsActive = 1
              AND p.ProductType = 'Product'
              AND NOT EXISTS (
                  SELECT 1 FROM Products.ProductBOMTbl b
                  WHERE b.ParentProductUID = p.ProductUID AND b.IsDeleted = 0
              )
        ";
        $query = $this->ReadDb->query($sql, [(int)$orgUID]);
        return $query->row();

    }

    // ── Paginated inventory list ──────────────────────────────────────────────

    public function getInventoryList($orgUID, $filter, $limit, $offset) {

        $this->ReadDb->db_debug = FALSE;

        $this->ReadDb->select([
            'p.ProductUID',
            'p.ItemName',
            'p.PartNumber',
            'p.AvailableQuantity',
            'p.LowStockAlertAt',
            'p.SellingPrice',
            'p.PurchasePrice',
            'p.TaxPercentage',
            'p.ProductType',
            'p.IsRentable',
            'p.UpdatedOn',
            'p.HSNSACCode',
            'p.Description',
            'c.Name AS CategoryName',
            'u.ShortName AS UnitName',
        ]);
        $this->ReadDb->select("CONCAT(IFNULL(usr.FirstName,''), ' ', IFNULL(usr.LastName,'')) AS UpdatedByName", FALSE);
        $this->ReadDb->from('Products.ProductTbl p');
        $this->ReadDb->join('Products.CategoryTbl c', 'c.CategoryUID = p.CategoryUID AND c.IsDeleted = 0', 'left');
        $this->ReadDb->join('Global.PrimaryUnitTbl u', 'u.PrimaryUnitUID = p.PrimaryUnitUID', 'left');
        $this->ReadDb->join('Users.UserTbl usr', 'usr.UserUID = p.UpdatedBy', 'left');
        $this->ReadDb->where(['p.OrgUID' => (int)$orgUID, 'p.IsDeleted' => 0, 'p.IsActive' => 1]);
        $this->ReadDb->where('NOT EXISTS (SELECT 1 FROM Products.ProductBOMTbl b WHERE b.ParentProductUID = p.ProductUID AND b.IsDeleted = 0)', null, false);

        $this->_applyFilter($filter);
        $this->_applySort($filter);

        if ($limit > 0) {
            $this->ReadDb->limit((int)$limit, (int)$offset);
        }

        $query = $this->ReadDb->get();
        $error = $this->ReadDb->error();
        if ($error['code']) throw new Exception($error['message']);
        return $query->result();

    }

    public function getInventoryCount($orgUID, $filter) {

        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->select('COUNT(*) AS cnt');
        $this->ReadDb->from('Products.ProductTbl p');
        $this->ReadDb->join('Products.CategoryTbl c', 'c.CategoryUID = p.CategoryUID AND c.IsDeleted = 0', 'left');
        $this->ReadDb->where(['p.OrgUID' => (int)$orgUID, 'p.IsDeleted' => 0, 'p.IsActive' => 1]);
        $this->ReadDb->where('NOT EXISTS (SELECT 1 FROM Products.ProductBOMTbl b WHERE b.ParentProductUID = p.ProductUID AND b.IsDeleted = 0)', null, false);

        $this->_applyFilter($filter);

        $row = $this->ReadDb->get()->row();
        return $row ? (int)$row->cnt : 0;

    }

    private function _applyFilter($filter) {

        if (!empty($filter['Search'])) {
            $term = $this->ReadDb->escape_like_str($filter['Search']);
            $this->ReadDb->group_start();
            $this->ReadDb->like('p.ItemName',   $term, 'both');
            $this->ReadDb->or_like('p.PartNumber', $term, 'both');
            $this->ReadDb->group_end();
        }
        if (!empty($filter['CategoryUID'])) {
            $uids = is_array($filter['CategoryUID'])
                ? array_map('intval', $filter['CategoryUID'])
                : [(int)$filter['CategoryUID']];
            $this->ReadDb->where_in('p.CategoryUID', $uids);
        }
        if (!empty($filter['ProductType'])) {
            $allowed = ['Product', 'Service'];
            $types   = array_values(array_intersect(
                is_array($filter['ProductType']) ? $filter['ProductType'] : [$filter['ProductType']],
                $allowed
            ));
            if (!empty($types)) {
                $this->ReadDb->where_in('p.ProductType', $types);
            }
        }
        if (!empty($filter['StockStatus'])) {
            switch ($filter['StockStatus']) {
                case 'LowStock':
                    $this->ReadDb->where('p.AvailableQuantity <=', 'p.LowStockAlertAt', false);
                    break;
                case 'Positive':
                    $this->ReadDb->where('p.AvailableQuantity >', 0);
                    break;
                case 'OutOfStock':
                    $this->ReadDb->where('p.AvailableQuantity <=', 0);
                    break;
            }
        }

    }

    private function _applySort($filter) {

        $allowed = [
            'ItemName'      => 'p.ItemName',
            'CategoryName'  => 'c.Name',
            'Qty'           => 'p.AvailableQuantity',
            'PurchasePrice' => 'p.PurchasePrice',
            'SellingPrice'  => 'p.SellingPrice',
        ];

        $col = $allowed[$filter['SortBy'] ?? ''] ?? 'p.ItemName';
        $dir = (isset($filter['SortDir']) && strtoupper($filter['SortDir']) === 'DESC') ? 'DESC' : 'ASC';
        $this->ReadDb->order_by($col, $dir);

    }

    // ── Full stock timeline for a product ────────────────────────────────────

    public function getStockTimeline($productUID, $orgUID) {

        $this->ReadDb->db_debug = FALSE;
        $sql = "
            SELECT
                sl.LedgerUID,
                sl.ModuleUID,
                sl.MovementType,
                sl.Quantity,
                sl.UnitCost,
                sl.CreatedOn,
                -- Transaction-based movements (Invoice, Purchase, Returns)
                ts.TransNumber,
                ts.TransDate,
                ts.DocStatus   AS TransStatus,
                -- Manual adjustment fields
                sa.AdjCategory,
                sa.AdjType,
                sa.RecordDate  AS AdjDate,
                sa.Notes       AS AdjNotes
            FROM Products.StockLedgerTbl sl
            LEFT JOIN Transaction.TransactionsTbl ts
                ON  ts.TransUID  = sl.TransUID
                AND sl.ModuleUID NOT IN (118)
                AND ts.IsDeleted = 0
            LEFT JOIN Products.StockAdjustmentTbl sa
                ON  sa.AdjUID    = sl.TransUID
                AND sl.ModuleUID = 118
                AND sa.IsDeleted = 0
            WHERE sl.ProductUID = ? AND sl.OrgUID = ? AND sl.IsDeleted = 0
            ORDER BY sl.LedgerUID DESC
            LIMIT 200
        ";
        $query = $this->ReadDb->query($sql, [(int)$productUID, (int)$orgUID]);
        $error = $this->ReadDb->error();
        if ($error['code']) throw new Exception($error['message']);
        return $query->result();

    }

    // ── Global timeline (all products, all movements) ────────────────────────

    public function getGlobalTimeline($orgUID, $filter, $limit, $offset) {

        $this->ReadDb->db_debug = FALSE;
        $params = [(int)$orgUID];
        $where  = $this->_buildTimelineWhere($filter, $params);

        $sql = "
            SELECT
                sl.LedgerUID,
                sl.ModuleUID,
                sl.MovementType,
                sl.Quantity,
                sl.UnitCost,
                sl.CreatedOn,
                sl.TransUID,
                p.ProductUID,
                p.ItemName,
                pu.ShortName AS UnitName,
                cat.Name AS CategoryName,
                ts.TransUID AS TransactionUID,
                ts.TransNumber,
                ts.TransDate,
                sa.AdjUID,
                sa.AdjCategory,
                sa.RecordDate AS AdjDate,
                sa.Notes AS AdjNotes,
                CONCAT(IFNULL(usr.FirstName,''), ' ', IFNULL(usr.LastName,'')) AS CreatedByName,
                COALESCE(ts.TransDate, sa.RecordDate, DATE(sl.CreatedOn)) AS EffectiveDate
            FROM Products.StockLedgerTbl sl
            INNER JOIN Products.ProductTbl p
                ON p.ProductUID = sl.ProductUID AND p.IsDeleted = 0
                AND NOT EXISTS (
                    SELECT 1 FROM Products.ProductBOMTbl b
                    WHERE b.ParentProductUID = p.ProductUID AND b.IsDeleted = 0
                )
            LEFT JOIN Products.CategoryTbl cat ON cat.CategoryUID = p.CategoryUID AND cat.IsDeleted = 0
            LEFT JOIN Global.PrimaryUnitTbl pu ON pu.PrimaryUnitUID = p.PrimaryUnitUID
            LEFT JOIN Transaction.TransactionsTbl ts
                ON ts.TransUID = sl.TransUID AND sl.ModuleUID NOT IN (118) AND ts.IsDeleted = 0
            LEFT JOIN Products.StockAdjustmentTbl sa
                ON sa.AdjUID = sl.TransUID AND sl.ModuleUID = 118 AND sa.IsDeleted = 0
            LEFT JOIN Users.UserTbl usr ON usr.UserUID = sl.CreatedBy
            WHERE sl.OrgUID = ? AND sl.IsDeleted = 0
            {$where}
            ORDER BY sl.LedgerUID DESC
            LIMIT ? OFFSET ?
        ";

        $params[] = (int)$limit;
        $params[] = (int)$offset;

        $query = $this->ReadDb->query($sql, $params);
        $error = $this->ReadDb->error();
        if ($error['code']) throw new Exception($error['message']);
        return $query->result();

    }

    public function getGlobalTimelineCount($orgUID, $filter) {

        $this->ReadDb->db_debug = FALSE;
        $params = [(int)$orgUID];
        $where  = $this->_buildTimelineWhere($filter, $params);

        $sql = "
            SELECT COUNT(*) AS cnt
            FROM Products.StockLedgerTbl sl
            INNER JOIN Products.ProductTbl p
                ON p.ProductUID = sl.ProductUID AND p.IsDeleted = 0
                AND NOT EXISTS (
                    SELECT 1 FROM Products.ProductBOMTbl b
                    WHERE b.ParentProductUID = p.ProductUID AND b.IsDeleted = 0
                )
            LEFT JOIN Transaction.TransactionsTbl ts
                ON ts.TransUID = sl.TransUID AND sl.ModuleUID NOT IN (118) AND ts.IsDeleted = 0
            LEFT JOIN Products.StockAdjustmentTbl sa
                ON sa.AdjUID = sl.TransUID AND sl.ModuleUID = 118 AND sa.IsDeleted = 0
            WHERE sl.OrgUID = ? AND sl.IsDeleted = 0
            {$where}
        ";

        $query = $this->ReadDb->query($sql, $params);
        $error = $this->ReadDb->error();
        if ($error['code']) throw new Exception($error['message']);
        $row = $query->row();
        return $row ? (int)$row->cnt : 0;

    }

    // ── Global timeline export (no limit) ───────────────────────────────────
    public function getGlobalTimelineExport($orgUID, $filter) {

        $this->ReadDb->db_debug = FALSE;
        $params = [(int)$orgUID];
        $where  = $this->_buildTimelineWhere($filter, $params);

        $sql = "
            SELECT
                sl.LedgerUID,
                sl.ModuleUID,
                sl.MovementType,
                sl.Quantity,
                sl.UnitCost,
                sl.CreatedOn,
                p.ItemName,
                pu.ShortName AS UnitName,
                cat.Name AS CategoryName,
                ts.TransNumber,
                ts.TransDate,
                sa.AdjCategory,
                sa.RecordDate AS AdjDate,
                sa.Notes AS AdjNotes,
                CONCAT(IFNULL(usr.FirstName,''), ' ', IFNULL(usr.LastName,'')) AS CreatedByName,
                COALESCE(ts.TransDate, sa.RecordDate, DATE(sl.CreatedOn)) AS EffectiveDate
            FROM Products.StockLedgerTbl sl
            INNER JOIN Products.ProductTbl p
                ON p.ProductUID = sl.ProductUID AND p.IsDeleted = 0
                AND NOT EXISTS (
                    SELECT 1 FROM Products.ProductBOMTbl b
                    WHERE b.ParentProductUID = p.ProductUID AND b.IsDeleted = 0
                )
            LEFT JOIN Products.CategoryTbl cat ON cat.CategoryUID = p.CategoryUID AND cat.IsDeleted = 0
            LEFT JOIN Global.PrimaryUnitTbl pu ON pu.PrimaryUnitUID = p.PrimaryUnitUID
            LEFT JOIN Transaction.TransactionsTbl ts
                ON ts.TransUID = sl.TransUID AND sl.ModuleUID NOT IN (118) AND ts.IsDeleted = 0
            LEFT JOIN Products.StockAdjustmentTbl sa
                ON sa.AdjUID = sl.TransUID AND sl.ModuleUID = 118 AND sa.IsDeleted = 0
            LEFT JOIN Users.UserTbl usr ON usr.UserUID = sl.CreatedBy
            WHERE sl.OrgUID = ? AND sl.IsDeleted = 0
            {$where}
            ORDER BY sl.LedgerUID DESC
        ";

        $query = $this->ReadDb->query($sql, $params);
        $error = $this->ReadDb->error();
        if ($error['code']) throw new Exception($error['message']);
        return $query->result();

    }

    private function _buildTimelineWhere($filter, &$params) {

        $where = '';
        if (!empty($filter['ProductUID'])) {
            $where   .= ' AND sl.ProductUID = ?';
            $params[] = (int)$filter['ProductUID'];
        }
        if (!empty($filter['DateFrom'])) {
            $where   .= ' AND COALESCE(ts.TransDate, sa.RecordDate, DATE(sl.CreatedOn)) >= ?';
            $params[] = $filter['DateFrom'];
        }
        if (!empty($filter['DateTo'])) {
            $where   .= ' AND COALESCE(ts.TransDate, sa.RecordDate, DATE(sl.CreatedOn)) <= ?';
            $params[] = $filter['DateTo'];
        }
        if (!empty($filter['MovementType'])) {
            $where   .= ' AND sl.MovementType = ?';
            $params[] = $filter['MovementType'];
        }
        return $where;

    }

    // ── Product search (for timeline filter) ─────────────────────────────────

    public function searchProducts($orgUID, $term = '') {

        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->select('p.ProductUID, p.ItemName, cat.Name AS CategoryName');
        $this->ReadDb->from('Products.ProductTbl p');
        $this->ReadDb->join('Products.CategoryTbl cat', 'cat.CategoryUID = p.CategoryUID AND cat.IsDeleted = 0', 'left');
        $this->ReadDb->where(['p.OrgUID' => (int)$orgUID, 'p.IsDeleted' => 0, 'p.IsActive' => 1, 'p.ProductType' => 'Product']);
        $this->ReadDb->where('NOT EXISTS (SELECT 1 FROM Products.ProductBOMTbl b WHERE b.ParentProductUID = p.ProductUID AND b.IsDeleted = 0)', null, false);
        if ($term) {
            $this->ReadDb->like('p.ItemName', $this->ReadDb->escape_like_str($term), 'both');
        }
        $this->ReadDb->order_by('p.ItemName', 'ASC');
        $this->ReadDb->limit(50);
        return $this->ReadDb->get()->result();

    }

    // ── Category list (for filter dropdown) ──────────────────────────────────

    public function getCategories($orgUID) {

        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->select('CategoryUID, Name');
        $this->ReadDb->from('Products.CategoryTbl');
        $this->ReadDb->where(['OrgUID' => (int)$orgUID, 'IsDeleted' => 0, 'IsActive' => 1]);
        $this->ReadDb->order_by('Name', 'ASC');
        $query = $this->ReadDb->get();
        return $query->result();

    }

}
