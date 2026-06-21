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
                SUM(CASE WHEN COALESCE(ps.AvailableQty, 0) > 0 THEN 1 ELSE 0 END)                                    AS positiveCount,
                SUM(CASE WHEN COALESCE(ps.AvailableQty, 0) > 0 THEN COALESCE(ps.AvailableQty, 0) ELSE 0 END)         AS positiveQty,
                SUM(CASE WHEN COALESCE(ps.AvailableQty, 0) <= p.LowStockAlertAt THEN 1 ELSE 0 END)                   AS lowStockCount,
                SUM(CASE WHEN COALESCE(ps.AvailableQty, 0) <= p.LowStockAlertAt
                         THEN COALESCE(ps.AvailableQty, 0) ELSE 0 END)                                               AS lowStockQty,
                SUM(CASE WHEN COALESCE(ps.AvailableQty, 0) > 0
                         THEN COALESCE(ps.AvailableQty, 0) * p.SellingPrice ELSE 0 END)                              AS saleValue,
                SUM(CASE WHEN COALESCE(ps.AvailableQty, 0) > 0
                         THEN COALESCE(ps.AvailableQty, 0) * p.PurchasePrice ELSE 0 END)                             AS purchaseValue
            FROM Products.ProductTbl p
            LEFT JOIN Products.ProductStockTbl ps ON ps.ProductUID = p.ProductUID
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
            'COALESCE(ps.AvailableQty, 0) AS AvailableQuantity',
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
        $this->ReadDb->join('Products.ProductStockTbl ps', 'ps.ProductUID = p.ProductUID', 'left');
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
        $this->ReadDb->join('Products.ProductStockTbl ps', 'ps.ProductUID = p.ProductUID', 'left');
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
        if (!empty($filter['ProductUID'])) {
            $uids = array_filter(array_map('intval',
                is_array($filter['ProductUID']) ? $filter['ProductUID'] : [$filter['ProductUID']]
            ));
            if (!empty($uids)) {
                $this->ReadDb->where_in('p.ProductUID', $uids);
            }
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
                    $this->ReadDb->where('COALESCE(ps.AvailableQty, 0) <=', 'p.LowStockAlertAt', false);
                    break;
                case 'Positive':
                    $this->ReadDb->where('COALESCE(ps.AvailableQty, 0) >', 0);
                    break;
                case 'OutOfStock':
                    $this->ReadDb->where('COALESCE(ps.AvailableQty, 0) <=', 0);
                    break;
            }
        }

    }

    private function _applySort($filter) {

        $allowed = [
            'ItemName'      => 'p.ItemName',
            'CategoryName'  => 'c.Name',
            'Qty'           => 'ps.AvailableQty',
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
                sl.SellingPrice,
                sl.TaxAmount,
                sl.CreatedOn,
                -- NULLIF guards against empty-string Remarks bypassing the fallback
                COALESCE(
                    NULLIF(sl.Remarks, ''),
                    NULLIF(COALESCE(sa.Notes, sa_fb.Notes), '')
                ) AS Remarks,
                -- Transaction-based movements (Invoice, Purchase, Returns)
                ts.TransNumber,
                ts.UniqueNumber,
                ts.DocStatus AS TransStatus,
                -- Manual adjustment fields (primary JOIN wins; fallback covers old broken records)
                COALESCE(sa.AdjUID,      sa_fb.AdjUID)      AS AdjUID,
                COALESCE(sa.AdjCategory, sa_fb.AdjCategory) AS AdjCategory,
                COALESCE(sa.AdjType,     sa_fb.AdjType)     AS AdjType,
                -- Single merged date column for all movement types
                COALESCE(ts.TransDate, sa.RecordDate, sa_fb.RecordDate, DATE(sl.CreatedOn)) AS EffectiveDate
            FROM Products.StockLedgerTbl sl
            LEFT JOIN Transaction.TransactionsTbl ts
                ON  ts.TransUID  = sl.TransUID
                AND sl.ModuleUID NOT IN (118)
                AND ts.IsDeleted = 0
            -- Primary adjustment JOIN: correct for records saved after the TransUID fix
            LEFT JOIN Products.StockAdjustmentTbl sa
                ON  sa.AdjUID    = sl.TransUID
                AND sl.ModuleUID = 118
                AND sa.IsDeleted = 0
            -- Fallback adjustment JOIN: recovers Notes/Date for old records where TransUID was 0
            LEFT JOIN Products.StockAdjustmentTbl sa_fb
                ON  sl.ModuleUID = 118
                AND sl.TransUID  = 0
                AND sa.AdjUID    IS NULL
                AND sa_fb.AdjUID = (
                    SELECT MAX(sa2.AdjUID)
                    FROM Products.StockAdjustmentTbl sa2
                    WHERE sa2.IsDeleted  = 0
                      AND sa2.OrgUID     = sl.OrgUID
                      AND sa2.ProductUID = sl.ProductUID
                      AND sa2.AdjType COLLATE utf8mb4_unicode_ci = sl.MovementType COLLATE utf8mb4_unicode_ci
                      AND sa2.Qty        = sl.Quantity
                )
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
        $sort   = $this->_buildTimelineSort($filter);

        $sql = "
            SELECT
                sl.LedgerUID,
                sl.ModuleUID,
                sl.MovementType,
                sl.Quantity,
                sl.UnitCost,
                sl.SellingPrice,
                sl.TaxAmount,
                sl.CreatedOn,
                sl.TransUID,
                sl.TransProdUID,
                COALESCE(
                    NULLIF(sl.Remarks, ''),
                    NULLIF(COALESCE(sa.Notes, sa_fb.Notes), '')
                ) AS Remarks,
                p.ProductUID,
                COALESCE(sl.SnapItemName, p.ItemName)        AS ItemName,
                sl.SnapItemName,
                p.HSNSACCode,
                p.ProductType,
                COALESCE(tp.PartNumber, p.PartNumber)        AS PartNumber,
                COALESCE(tp.Description, p.Description)      AS Description,
                COALESCE(tp.PrimaryUnitName, pu.ShortName)   AS UnitName,
                cat.Name AS CategoryName,
                ts.TransUID AS TransactionUID,
                ts.TransNumber,
                ts.UniqueNumber,
                ts.TransDate,
                ts.DocStatus,
                COALESCE(sa.AdjUID,      sa_fb.AdjUID)      AS AdjUID,
                COALESCE(sa.AdjCategory, sa_fb.AdjCategory) AS AdjCategory,
                COALESCE(sa.RecordDate,  sa_fb.RecordDate)  AS AdjDate,
                CONCAT(IFNULL(usr.FirstName,''), ' ', IFNULL(usr.LastName,'')) AS CreatedByName,
                COALESCE(ts.TransDate, sa.RecordDate, sa_fb.RecordDate, DATE(sl.CreatedOn)) AS EffectiveDate
            FROM Products.StockLedgerTbl sl
            INNER JOIN Products.ProductTbl p
                ON p.ProductUID = sl.ProductUID AND p.IsDeleted = 0
                AND NOT EXISTS (
                    SELECT 1 FROM Products.ProductBOMTbl b
                    WHERE b.ParentProductUID = p.ProductUID AND b.IsDeleted = 0
                )
            LEFT JOIN Products.CategoryTbl cat ON cat.CategoryUID = p.CategoryUID AND cat.IsDeleted = 0
            LEFT JOIN Global.PrimaryUnitTbl pu ON pu.PrimaryUnitUID = p.PrimaryUnitUID
            LEFT JOIN Transaction.TransProductsTbl tp ON tp.TransProdUID = sl.TransProdUID AND tp.IsDeleted = 0
            LEFT JOIN Transaction.TransactionsTbl ts
                ON ts.TransUID = sl.TransUID AND sl.ModuleUID NOT IN (118) AND ts.IsDeleted = 0
            LEFT JOIN Products.StockAdjustmentTbl sa
                ON sa.AdjUID = sl.TransUID AND sl.ModuleUID = 118 AND sa.IsDeleted = 0
            LEFT JOIN Products.StockAdjustmentTbl sa_fb
                ON  sl.ModuleUID = 118
                AND sl.TransUID  = 0
                AND sa.AdjUID    IS NULL
                AND sa_fb.AdjUID = (
                    SELECT MAX(sa2.AdjUID)
                    FROM Products.StockAdjustmentTbl sa2
                    WHERE sa2.IsDeleted  = 0
                      AND sa2.OrgUID     = sl.OrgUID
                      AND sa2.ProductUID = sl.ProductUID
                      AND sa2.AdjType COLLATE utf8mb4_unicode_ci = sl.MovementType COLLATE utf8mb4_unicode_ci
                      AND sa2.Qty        = sl.Quantity
                )
            LEFT JOIN Users.UserTbl usr ON usr.UserUID = sl.CreatedBy
            WHERE sl.OrgUID = ? AND sl.IsDeleted = 0
            {$where}
            ORDER BY {$sort}
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
                sl.SellingPrice,
                sl.TaxAmount,
                sl.CreatedOn,
                sl.TransProdUID,
                COALESCE(
                    NULLIF(sl.Remarks, ''),
                    NULLIF(COALESCE(sa.Notes, sa_fb.Notes), '')
                ) AS Remarks,
                COALESCE(sl.SnapItemName, p.ItemName) AS ItemName,
                sl.SnapItemName,
                COALESCE(tp.PrimaryUnitName, pu.ShortName) AS UnitName,
                cat.Name AS CategoryName,
                ts.TransNumber,
                ts.UniqueNumber,
                ts.TransDate,
                COALESCE(sa.AdjUID,      sa_fb.AdjUID)      AS AdjUID,
                COALESCE(sa.AdjCategory, sa_fb.AdjCategory) AS AdjCategory,
                COALESCE(sa.RecordDate,  sa_fb.RecordDate)  AS AdjDate,
                CONCAT(IFNULL(usr.FirstName,''), ' ', IFNULL(usr.LastName,'')) AS CreatedByName,
                COALESCE(ts.TransDate, sa.RecordDate, sa_fb.RecordDate, DATE(sl.CreatedOn)) AS EffectiveDate
            FROM Products.StockLedgerTbl sl
            INNER JOIN Products.ProductTbl p
                ON p.ProductUID = sl.ProductUID AND p.IsDeleted = 0
                AND NOT EXISTS (
                    SELECT 1 FROM Products.ProductBOMTbl b
                    WHERE b.ParentProductUID = p.ProductUID AND b.IsDeleted = 0
                )
            LEFT JOIN Products.CategoryTbl cat ON cat.CategoryUID = p.CategoryUID AND cat.IsDeleted = 0
            LEFT JOIN Global.PrimaryUnitTbl pu ON pu.PrimaryUnitUID = p.PrimaryUnitUID
            LEFT JOIN Transaction.TransProductsTbl tp ON tp.TransProdUID = sl.TransProdUID AND tp.IsDeleted = 0
            LEFT JOIN Transaction.TransactionsTbl ts
                ON ts.TransUID = sl.TransUID AND sl.ModuleUID NOT IN (118) AND ts.IsDeleted = 0
            LEFT JOIN Products.StockAdjustmentTbl sa
                ON sa.AdjUID = sl.TransUID AND sl.ModuleUID = 118 AND sa.IsDeleted = 0
            LEFT JOIN Products.StockAdjustmentTbl sa_fb
                ON  sl.ModuleUID = 118
                AND sl.TransUID  = 0
                AND sa.AdjUID    IS NULL
                AND sa_fb.AdjUID = (
                    SELECT MAX(sa2.AdjUID)
                    FROM Products.StockAdjustmentTbl sa2
                    WHERE sa2.IsDeleted  = 0
                      AND sa2.OrgUID     = sl.OrgUID
                      AND sa2.ProductUID = sl.ProductUID
                      AND sa2.AdjType COLLATE utf8mb4_unicode_ci = sl.MovementType COLLATE utf8mb4_unicode_ci
                      AND sa2.Qty        = sl.Quantity
                )
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
            $uids = is_array($filter['ProductUID']) ? array_map('intval', $filter['ProductUID']) : [(int)$filter['ProductUID']];
            $uids = array_values(array_filter($uids));
            if ($uids) {
                $where .= ' AND sl.ProductUID IN (' . implode(',', array_fill(0, count($uids), '?')) . ')';
                foreach ($uids as $u) $params[] = $u;
            }
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
        if (!empty($filter['CategoryUID'])) {
            $uids = is_array($filter['CategoryUID']) ? array_map('intval', $filter['CategoryUID']) : [(int)$filter['CategoryUID']];
            $uids = array_values(array_filter($uids));
            if ($uids) {
                $where .= ' AND p.CategoryUID IN (' . implode(',', array_fill(0, count($uids), '?')) . ')';
                foreach ($uids as $u) $params[] = $u;
            }
        }
        if (!empty($filter['ModuleUID'])) {
            $uids = is_array($filter['ModuleUID']) ? array_map('intval', $filter['ModuleUID']) : [(int)$filter['ModuleUID']];
            $uids = array_values(array_filter($uids));
            if ($uids) {
                $where .= ' AND sl.ModuleUID IN (' . implode(',', array_fill(0, count($uids), '?')) . ')';
                foreach ($uids as $u) $params[] = $u;
            }
        }
        if (!empty($filter['CreatedByUID'])) {
            $uids = is_array($filter['CreatedByUID']) ? array_map('intval', $filter['CreatedByUID']) : [(int)$filter['CreatedByUID']];
            $uids = array_values(array_filter($uids));
            if ($uids) {
                $where .= ' AND sl.CreatedBy IN (' . implode(',', array_fill(0, count($uids), '?')) . ')';
                foreach ($uids as $u) $params[] = $u;
            }
        }
        return $where;

    }

    private function _buildTimelineSort($filter) {

        $colMap = [
            'ItemName'  => 'p.ItemName',
            'Price'     => 'COALESCE(sl.SellingPrice, sl.UnitCost)',
            'Category'  => 'cat.Name',
            'Date'      => 'COALESCE(ts.TransDate, sa.RecordDate, DATE(sl.CreatedOn))',
        ];
        $col = isset($filter['SortBy'], $colMap[$filter['SortBy']]) ? $colMap[$filter['SortBy']] : 'sl.LedgerUID';
        $dir = (isset($filter['SortDir']) && strtoupper($filter['SortDir']) === 'ASC') ? 'ASC' : 'DESC';
        return "{$col} {$dir}";

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

    // ── Fetch a single manual adjustment (for update) ─────────────────────────

    public function getAdjustmentById($adjUID, $orgUID) {

        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->select('AdjUID, ProductUID, AdjType, Qty, Price, Notes, RecordDate');
        $this->ReadDb->from('Products.StockAdjustmentTbl');
        $this->ReadDb->where(['AdjUID' => (int)$adjUID, 'OrgUID' => (int)$orgUID, 'IsDeleted' => 0]);
        $query = $this->ReadDb->get();
        return $query->row();

    }

    // ── Fetch a single ledger row (for remarks update) ────────────────────────

    public function getLedgerById($ledgerUID, $orgUID) {

        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->select('LedgerUID, ProductUID, ModuleUID');
        $this->ReadDb->from('Products.StockLedgerTbl');
        $this->ReadDb->where(['LedgerUID' => (int)$ledgerUID, 'OrgUID' => (int)$orgUID, 'IsDeleted' => 0]);
        $query = $this->ReadDb->get();
        return $query->row();

    }

}
