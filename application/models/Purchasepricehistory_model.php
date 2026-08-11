<?php defined('BASEPATH') or exit('No direct script access allowed');

class Purchasepricehistory_model extends CI_Model {

    /** @var CI_DB_query_builder */
    private $ReadDb;

    public function __construct() {
        parent::__construct();
        $this->ReadDb = $this->load->database('ReadDB', TRUE);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Most recent active entry for a product+vendor on or before $beforeDate.
     * Pass $excludeLogUID to skip the entry being updated/re-evaluated.
     *
     * @param int    $orgUID
     * @param int    $productUID
     * @param int    $vendorUID
     * @param string $beforeDate  'Y-m-d'
     * @param int    $excludeLogUID
     * @returns object|null
     */
    private function _getLastEntryBefore(int $orgUID, int $productUID, int $vendorUID, string $beforeDate, int $excludeLogUID = 0): ?object {
        $this->ReadDb->select('PriceLogUID, PurchasePrice, EntryDate');
        $this->ReadDb->from('Products.PurchasePriceLogTbl');
        $this->ReadDb->where(['OrgUID' => $orgUID, 'ProductUID' => $productUID, 'VendorUID' => $vendorUID, 'IsDeleted' => 0]);
        $this->ReadDb->where('EntryDate <=', $beforeDate);
        if ($excludeLogUID > 0) $this->ReadDb->where('PriceLogUID !=', $excludeLogUID);
        $this->ReadDb->order_by('EntryDate', 'DESC');
        $this->ReadDb->order_by('PriceLogUID', 'DESC');
        $this->ReadDb->limit(1);
        $q = $this->ReadDb->get();
        return ($q && $q->num_rows()) ? $q->row() : null;
    }

    /**
     * Active price log entry linked to a specific transaction + product.
     *
     * @param int $orgUID
     * @param int $transUID
     * @param int $productUID
     * @returns object|null
     */
    private function _getEntryByTransaction(int $orgUID, int $transUID, int $productUID): ?object {
        $this->ReadDb->select('PriceLogUID, PurchasePrice, EntryDate, Direction, PreviousPrice');
        $this->ReadDb->from('Products.PurchasePriceLogTbl');
        $this->ReadDb->where(['OrgUID' => $orgUID, 'TransactionUID' => $transUID, 'ProductUID' => $productUID, 'IsDeleted' => 0]);
        $this->ReadDb->limit(1);
        $q = $this->ReadDb->get();
        return ($q && $q->num_rows()) ? $q->row() : null;
    }

    /**
     * All active price log entries linked to a transaction (for edit diff).
     *
     * @param int $orgUID
     * @param int $transUID
     * @returns object[]
     */
    private function _getEntriesByTransaction(int $orgUID, int $transUID): array {
        $this->ReadDb->select('PriceLogUID, ProductUID');
        $this->ReadDb->from('Products.PurchasePriceLogTbl');
        $this->ReadDb->where(['OrgUID' => $orgUID, 'TransactionUID' => $transUID, 'IsDeleted' => 0]);
        $q = $this->ReadDb->get();
        return ($q && $q->num_rows()) ? $q->result() : [];
    }

    /**
     * Whether any active entries exist for a product+vendor AFTER $afterDate.
     *
     * @param int    $orgUID
     * @param int    $productUID
     * @param int    $vendorUID
     * @param string $afterDate
     * @param int    $excludeLogUID
     * @returns bool
     */
    private function _hasEntriesAfterDate(int $orgUID, int $productUID, int $vendorUID, string $afterDate, int $excludeLogUID = 0): bool {
        $this->ReadDb->select('PriceLogUID');
        $this->ReadDb->from('Products.PurchasePriceLogTbl');
        $this->ReadDb->where(['OrgUID' => $orgUID, 'ProductUID' => $productUID, 'VendorUID' => $vendorUID, 'IsDeleted' => 0]);
        $this->ReadDb->where('EntryDate >', $afterDate);
        if ($excludeLogUID > 0) $this->ReadDb->where('PriceLogUID !=', $excludeLogUID);
        $this->ReadDb->limit(1);
        $q = $this->ReadDb->get();
        return (bool)($q && $q->num_rows());
    }

    /**
     * Soft-delete one price log entry.
     *
     * @param int $priceLogUID
     * @param int $userUID
     * @returns void
     */
    private function _softDeleteEntry(int $priceLogUID, int $userUID): void {
        $this->load->model('dbwrite_model');
        $this->dbwrite_model->updateData('Products', 'PurchasePriceLogTbl', [
            'IsDeleted' => 1,
            'DeletedOn' => date('Y-m-d H:i:s'),
            'DeletedBy' => $userUID,
            'UpdatedBy' => $userUID,
        ], ['PriceLogUID' => $priceLogUID]);
    }

    /**
     * Direction constant: 0=First, 1=Up, 2=Down.
     *
     * @param float|null $prevPrice
     * @param float      $newPrice
     * @returns int
     */
    private function _direction(?float $prevPrice, float $newPrice): int {
        if ($prevPrice === null) return 0;
        return ($newPrice > $prevPrice) ? 1 : 2;
    }

    /**
     * Batch-fetch product meta (name, SKU, HSN, brand) keyed by ProductUID.
     *
     * @param int   $orgUID
     * @param int[] $productUIDs
     * @returns array<int,object>
     */
    private function _getProductMeta(int $orgUID, array $productUIDs): array {
        $productUIDs = array_values(array_unique(array_filter(array_map('intval', $productUIDs))));
        if (empty($productUIDs)) return [];
        $placeholders = implode(',', array_fill(0, count($productUIDs), '?'));
        $q = $this->ReadDb->query(
            "SELECT P.ProductUID, P.ItemName, P.SKU, P.HSNSACCode AS HSNCode,
                    COALESCE(B.BrandName, '') AS BrandName
             FROM Products.ProductTbl P
             LEFT JOIN Products.BrandTbl B ON B.BrandUID = P.BrandUID AND B.IsDeleted = 0
             WHERE P.ProductUID IN ($placeholders) AND P.OrgUID = ? AND P.IsDeleted = 0",
            array_merge($productUIDs, [$orgUID])
        );
        if (!$q) return [];
        $map = [];
        foreach ($q->result() as $r) { $map[(int)$r->ProductUID] = $r; }
        return $map;
    }

    /**
     * Fetch current selling price + unit for a product (used in manual save).
     *
     * @param int $orgUID
     * @param int $productUID
     * @returns object|null
     */
    private function _getProductInfo(int $orgUID, int $productUID): ?object {
        $q = $this->ReadDb->query(
            "SELECT P.ProductUID, P.SellingPrice,
                    COALESCE(U.ShortName, '') AS PrimaryUnitName
             FROM Products.ProductTbl P
             LEFT JOIN Global.PrimaryUnitTbl U ON U.PrimaryUnitUID = P.PrimaryUnitUID
             WHERE P.ProductUID = ? AND P.OrgUID = ? AND P.IsDeleted = 0
             LIMIT 1",
            [$productUID, $orgUID]
        );
        return ($q && $q->num_rows()) ? $q->row() : null;
    }

    /**
     * Fetch vendor display name.
     *
     * @param int $orgUID
     * @param int $vendorUID
     * @returns string
     */
    private function _getVendorName(int $orgUID, int $vendorUID): string {
        $this->ReadDb->select('Name');
        $this->ReadDb->from('Vendors.VendorTbl');
        $this->ReadDb->where(['VendorUID' => $vendorUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]);
        $this->ReadDb->limit(1);
        $q = $this->ReadDb->get();
        $r = ($q && $q->num_rows()) ? $q->row() : null;
        return $r ? (string)$r->Name : '';
    }

    /**
     * Insert a log row + snapshot row. Returns new PriceLogUID.
     *
     * @param array $logRow
     * @param array $snapshotRow
     * @returns int
     */
    private function _insert(array $logRow, array $snapshotRow): int {
        $this->load->model('dbwrite_model');
        $resp = $this->dbwrite_model->insertData('Products', 'PurchasePriceLogTbl', $logRow);
        if ($resp->Error) throw new Exception($resp->Message);
        $uid = (int)$resp->ID;
        $this->dbwrite_model->insertData('Products', 'PurchasePriceLogSnapshotTbl', array_merge($snapshotRow, ['PriceLogUID' => $uid]));
        return $uid;
    }

    /**
     * Update an existing log row + snapshot row.
     *
     * @param int   $priceLogUID
     * @param array $logFields
     * @param array $snapshotFields
     * @param int   $userUID
     * @returns void
     */
    private function _update(int $priceLogUID, array $logFields, array $snapshotFields, int $userUID): void {
        $this->load->model('dbwrite_model');
        $this->dbwrite_model->updateData('Products', 'PurchasePriceLogTbl',
            array_merge($logFields, ['UpdatedBy' => $userUID]),
            ['PriceLogUID' => $priceLogUID]
        );
        if (!empty($snapshotFields)) {
            $this->dbwrite_model->updateData('Products', 'PurchasePriceLogSnapshotTbl',
                $snapshotFields,
                ['PriceLogUID' => $priceLogUID]
            );
        }
    }

    /**
     * Build a snapshot array from item data + fetched meta.
     *
     * @param array       $item        transaction line item
     * @param string      $vendorName
     * @param object|null $meta        from _getProductMeta()
     * @returns array
     */
    private function _buildSnapshot(array $item, string $vendorName, ?object $meta): array {
        return [
            'ProductName'  => substr((string)($item['itemName'] ?? ($meta->ItemName ?? '')), 0, 255),
            'SKU'          => substr((string)($meta->SKU ?? ''), 0, 100),
            'VendorName'   => substr($vendorName, 0, 255),
            'CategoryName' => substr((string)($item['categoryName'] ?? ''), 0, 255),
            'BrandName'    => substr((string)($meta->BrandName ?? ''), 0, 255),
            'TaxPct'       => (float)($item['taxPercent'] ?? 0),
            'HSNCode'      => substr((string)($meta->HSNCode ?? ''), 0, 50),
        ];
    }

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Auto-sync price log from a newly created purchase.
     * Non-fatal — caller must wrap in try-catch and log on failure.
     *
     * @param int    $orgUID
     * @param int    $userUID
     * @param int    $transUID
     * @param string $transDate
     * @param int    $vendorUID
     * @param array  $items      from _extractTransAmounts()
     * @returns void
     */
    public function syncFromPurchase(int $orgUID, int $userUID, int $transUID, string $transDate, int $vendorUID, array $items): void {
        if (empty($items)) return;

        $vendorName  = $this->_getVendorName($orgUID, $vendorUID);
        $productUIDs = array_map(fn($i) => (int)($i['id'] ?? 0), $items);
        $productMeta = $this->_getProductMeta($orgUID, $productUIDs);

        foreach ($items as $item) {
            $productUID = (int)($item['id'] ?? 0);
            $qty        = (float)($item['quantity']  ?? 0);
            $unitPrice  = (float)($item['unitPrice'] ?? 0);
            if ($productUID <= 0 || $qty <= 0) continue;

            $prev      = $this->_getLastEntryBefore($orgUID, $productUID, $vendorUID, $transDate);
            $prevPrice = $prev ? (float)$prev->PurchasePrice : null;

            if ($prevPrice !== null && abs($unitPrice - $prevPrice) < 0.0001) continue;

            $diff    = $prevPrice !== null ? round($unitPrice - $prevPrice, 4) : null;
            $diffPct = ($prevPrice !== null && $prevPrice != 0)
                       ? round((($unitPrice - $prevPrice) / $prevPrice) * 100, 4) : null;

            $meta = $productMeta[$productUID] ?? null;

            $this->_insert([
                'OrgUID'         => $orgUID,
                'ProductUID'     => $productUID,
                'VendorUID'      => $vendorUID,
                'EntryDate'      => $transDate,
                'PurchasePrice'  => $unitPrice,
                'SellingPrice'   => (float)($item['sellingPrice'] ?? 0),
                'Qty'            => $qty,
                'Unit'           => substr((string)($item['primaryUnit'] ?? ''), 0, 50),
                'PreviousPrice'  => $prevPrice,
                'PriceDiff'      => $diff,
                'PriceDiffPct'   => $diffPct,
                'Direction'      => $this->_direction($prevPrice, $unitPrice),
                'Source'         => 1,
                'TransactionUID' => $transUID,
                'Remarks'        => null,
                'CreatedBy'      => $userUID,
                'UpdatedBy'      => $userUID,
            ], $this->_buildSnapshot($item, $vendorName, $meta));
        }
    }

    /**
     * Sync price log when a purchase is edited.
     * Handles insert / update / soft-delete per item based on whether price changed.
     * Non-fatal — caller must wrap in try-catch and log on failure.
     *
     * @param int    $orgUID
     * @param int    $userUID
     * @param int    $activeTransUID  final TransUID after edit (may differ from origTransUID on Draft→Finalized promotion)
     * @param int    $origTransUID    TransUID from the form POST (used to find existing log entries)
     * @param string $transDate
     * @param int    $vendorUID
     * @param array  $items
     * @returns void
     */
    public function updateFromPurchase(int $orgUID, int $userUID, int $activeTransUID, int $origTransUID, string $transDate, int $vendorUID, array $items): void {
        if (empty($items)) return;

        $vendorName  = $this->_getVendorName($orgUID, $vendorUID);
        $productUIDs = array_map(fn($i) => (int)($i['id'] ?? 0), $items);
        $productMeta = $this->_getProductMeta($orgUID, $productUIDs);

        // Soft-delete entries for items removed from the purchase
        $existingEntries    = $this->_getEntriesByTransaction($orgUID, $origTransUID);
        $currentProductUIDs = array_fill_keys(array_filter($productUIDs), true);
        foreach ($existingEntries as $e) {
            if (!isset($currentProductUIDs[(int)$e->ProductUID])) {
                $this->_softDeleteEntry((int)$e->PriceLogUID, $userUID);
            }
        }

        foreach ($items as $item) {
            $productUID = (int)($item['id'] ?? 0);
            $qty        = (float)($item['quantity']  ?? 0);
            $unitPrice  = (float)($item['unitPrice'] ?? 0);
            if ($productUID <= 0 || $qty <= 0) continue;

            $existing      = $this->_getEntryByTransaction($orgUID, $origTransUID, $productUID);
            $excludeLogUID = $existing ? (int)$existing->PriceLogUID : 0;
            $prev          = $this->_getLastEntryBefore($orgUID, $productUID, $vendorUID, $transDate, $excludeLogUID);
            $prevPrice     = $prev ? (float)$prev->PurchasePrice : null;
            $meta          = $productMeta[$productUID] ?? null;
            $snapshot      = $this->_buildSnapshot($item, $vendorName, $meta);

            if ($prevPrice !== null && abs($unitPrice - $prevPrice) < 0.0001) {
                // Price now matches previous — this entry should not exist
                if ($existing) $this->_softDeleteEntry($excludeLogUID, $userUID);
                continue;
            }

            $diff    = $prevPrice !== null ? round($unitPrice - $prevPrice, 4) : null;
            $diffPct = ($prevPrice !== null && $prevPrice != 0)
                       ? round((($unitPrice - $prevPrice) / $prevPrice) * 100, 4) : null;

            $logFields = [
                'EntryDate'      => $transDate,
                'PurchasePrice'  => $unitPrice,
                'SellingPrice'   => (float)($item['sellingPrice'] ?? 0),
                'Qty'            => $qty,
                'Unit'           => substr((string)($item['primaryUnit'] ?? ''), 0, 50),
                'PreviousPrice'  => $prevPrice,
                'PriceDiff'      => $diff,
                'PriceDiffPct'   => $diffPct,
                'Direction'      => $this->_direction($prevPrice, $unitPrice),
                'TransactionUID' => $activeTransUID,
            ];

            if ($existing) {
                $this->_update($excludeLogUID, $logFields, $snapshot, $userUID);
            } else {
                $this->_insert(array_merge($logFields, [
                    'OrgUID'     => $orgUID,
                    'ProductUID' => $productUID,
                    'VendorUID'  => $vendorUID,
                    'Source'     => 1,
                    'Remarks'    => null,
                    'CreatedBy'  => $userUID,
                    'UpdatedBy'  => $userUID,
                ]), $snapshot);
            }
        }
    }

    /**
     * Soft-delete all price log entries linked to a deleted purchase.
     * Non-fatal — caller must wrap in try-catch and log on failure.
     *
     * @param int $orgUID
     * @param int $transUID
     * @param int $userUID
     * @returns void
     */
    public function softDeleteByTransaction(int $orgUID, int $transUID, int $userUID): void {
        $this->load->model('dbwrite_model');
        $this->dbwrite_model->updateData('Products', 'PurchasePriceLogTbl', [
            'IsDeleted' => 1,
            'DeletedOn' => date('Y-m-d H:i:s'),
            'DeletedBy' => $userUID,
            'UpdatedBy' => $userUID,
        ], ['TransactionUID' => $transUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]);
    }

    /**
     * Save a manual price log entry.
     * Returns BACKDATED_REQUIRES_REMARKS error code when notes are missing for backdated entries.
     *
     * @param int   $orgUID
     * @param int   $userUID
     * @param array $data  keys: ProductUID, VendorUID, EntryDate, PurchasePrice, Qty, Remarks
     * @returns array{Error: bool, Message: string, PriceLogUID: int|null}
     */
    public function saveManual(int $orgUID, int $userUID, array $data): array {
        $productUID    = (int)($data['ProductUID'] ?? 0);
        $vendorUID     = (int)($data['VendorUID']  ?? 0);
        $entryDate     = (string)($data['EntryDate'] ?? '');
        $purchasePrice = (float)($data['PurchasePrice'] ?? 0);
        $qty           = (float)($data['Qty'] ?? 0);
        $remarks       = trim((string)($data['Remarks'] ?? ''));

        if ($productUID <= 0)      return ['Error' => true, 'Message' => 'Please select a product.', 'PriceLogUID' => null];
        if ($vendorUID <= 0)       return ['Error' => true, 'Message' => 'Please select a vendor.',  'PriceLogUID' => null];
        if (empty($entryDate))     return ['Error' => true, 'Message' => 'Entry date is required.',  'PriceLogUID' => null];
        if ($purchasePrice <= 0)   return ['Error' => true, 'Message' => 'Purchase price must be greater than zero.', 'PriceLogUID' => null];

        if ($this->_hasEntriesAfterDate($orgUID, $productUID, $vendorUID, $entryDate) && empty($remarks)) {
            return ['Error' => true, 'Message' => 'BACKDATED_REQUIRES_REMARKS', 'PriceLogUID' => null];
        }

        $prev      = $this->_getLastEntryBefore($orgUID, $productUID, $vendorUID, $entryDate);
        $prevPrice = $prev ? (float)$prev->PurchasePrice : null;

        $diff    = $prevPrice !== null ? round($purchasePrice - $prevPrice, 4) : null;
        $diffPct = ($prevPrice !== null && $prevPrice != 0)
                   ? round((($purchasePrice - $prevPrice) / $prevPrice) * 100, 4) : null;

        $productInfo  = $this->_getProductInfo($orgUID, $productUID);
        $vendorName   = $this->_getVendorName($orgUID, $vendorUID);
        $productMeta  = $this->_getProductMeta($orgUID, [$productUID]);
        $meta         = $productMeta[$productUID] ?? null;

        $sellingPrice = (float)($data['SellingPrice'] ?? ($productInfo ? $productInfo->SellingPrice : 0));
        $unit         = $productInfo ? (string)($productInfo->PrimaryUnitName ?? '') : '';

        try {
            $uid = $this->_insert([
                'OrgUID'         => $orgUID,
                'ProductUID'     => $productUID,
                'VendorUID'      => $vendorUID,
                'EntryDate'      => $entryDate,
                'PurchasePrice'  => $purchasePrice,
                'SellingPrice'   => $sellingPrice,
                'Qty'            => $qty,
                'Unit'           => substr($unit, 0, 50),
                'PreviousPrice'  => $prevPrice,
                'PriceDiff'      => $diff,
                'PriceDiffPct'   => $diffPct,
                'Direction'      => $this->_direction($prevPrice, $purchasePrice),
                'Source'         => 2,
                'TransactionUID' => null,
                'Remarks'        => $remarks ?: null,
                'CreatedBy'      => $userUID,
                'UpdatedBy'      => $userUID,
            ], [
                'ProductName'  => substr((string)($meta->ItemName ?? ''), 0, 255),
                'SKU'          => substr((string)($meta->SKU ?? ''), 0, 100),
                'VendorName'   => substr($vendorName, 0, 255),
                'CategoryName' => '',
                'BrandName'    => substr((string)($meta->BrandName ?? ''), 0, 255),
                'TaxPct'       => 0,
                'HSNCode'      => substr((string)($meta->HSNCode ?? ''), 0, 50),
            ]);
            return ['Error' => false, 'Message' => 'Price log entry saved successfully.', 'PriceLogUID' => $uid];
        } catch (Exception $e) {
            return ['Error' => true, 'Message' => $e->getMessage(), 'PriceLogUID' => null];
        }
    }

    /**
     * Update a manual (Source=2) price log entry. Auto entries are read-only.
     *
     * @param int   $orgUID
     * @param int   $userUID
     * @param int   $logUID
     * @param array $data  keys: EntryDate, PurchasePrice, Qty, Remarks
     * @returns array{Error: bool, Message: string}
     */
    public function updateManual(int $orgUID, int $userUID, int $logUID, array $data): array {
        $this->ReadDb->select('PriceLogUID, Source, ProductUID, VendorUID');
        $this->ReadDb->from('Products.PurchasePriceLogTbl');
        $this->ReadDb->where(['PriceLogUID' => $logUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]);
        $this->ReadDb->limit(1);
        $q     = $this->ReadDb->get();
        $entry = ($q && $q->num_rows()) ? $q->row() : null;

        if (!$entry)                   return ['Error' => true, 'Message' => 'Entry not found.'];
        if ((int)$entry->Source !== 2) return ['Error' => true, 'Message' => 'Auto entries cannot be edited.'];

        $purchasePrice = (float)($data['PurchasePrice'] ?? 0);
        $entryDate     = (string)($data['EntryDate'] ?? '');
        $qty           = (float)($data['Qty'] ?? 0);
        $remarks       = trim((string)($data['Remarks'] ?? ''));

        if ($purchasePrice <= 0) return ['Error' => true, 'Message' => 'Purchase price must be greater than zero.'];
        if (empty($entryDate))   return ['Error' => true, 'Message' => 'Entry date is required.'];

        if ($this->_hasEntriesAfterDate($orgUID, (int)$entry->ProductUID, (int)$entry->VendorUID, $entryDate, $logUID) && empty($remarks)) {
            return ['Error' => true, 'Message' => 'BACKDATED_REQUIRES_REMARKS'];
        }

        $prev      = $this->_getLastEntryBefore($orgUID, (int)$entry->ProductUID, (int)$entry->VendorUID, $entryDate, $logUID);
        $prevPrice = $prev ? (float)$prev->PurchasePrice : null;
        $diff      = $prevPrice !== null ? round($purchasePrice - $prevPrice, 4) : null;
        $diffPct   = ($prevPrice !== null && $prevPrice != 0)
                     ? round((($purchasePrice - $prevPrice) / $prevPrice) * 100, 4) : null;

        try {
            $this->_update($logUID, [
                'EntryDate'     => $entryDate,
                'PurchasePrice' => $purchasePrice,
                'Qty'           => $qty,
                'PreviousPrice' => $prevPrice,
                'PriceDiff'     => $diff,
                'PriceDiffPct'  => $diffPct,
                'Direction'     => $this->_direction($prevPrice, $purchasePrice),
                'Remarks'       => $remarks ?: null,
            ], [], $userUID);
            return ['Error' => false, 'Message' => 'Entry updated successfully.'];
        } catch (Exception $e) {
            return ['Error' => true, 'Message' => $e->getMessage()];
        }
    }

    /**
     * Check whether an entry would be backdated (used by the AJAX check endpoint).
     *
     * @param int    $orgUID
     * @param int    $productUID
     * @param int    $vendorUID
     * @param string $entryDate
     * @param int    $excludeLogUID  pass current log UID when editing
     * @returns bool
     */
    public function isBackdated(int $orgUID, int $productUID, int $vendorUID, string $entryDate, int $excludeLogUID = 0): bool {
        return $this->_hasEntriesAfterDate($orgUID, $productUID, $vendorUID, $entryDate, $excludeLogUID);
    }

    /**
     * Get current selling price + unit for the manual entry form auto-fill.
     *
     * @param int $orgUID
     * @param int $productUID
     * @returns object|null
     */
    public function getProductInfoForForm(int $orgUID, int $productUID): ?object {
        return $this->_getProductInfo($orgUID, $productUID);
    }

    /**
     * Paginated list of price log entries with snapshot join.
     *
     * @param int   $orgUID
     * @param int   $limit
     * @param int   $offset
     * @param array $filter  keys: ProductUID, VendorUID, Source, Direction, DateFrom, DateTo, Search
     * @returns object{rows: object[], totalCount: int}
     */
    public function getLogList(int $orgUID, int $limit, int $offset, array $filter): object {
        $result = new stdClass();

        $where = ['L.OrgUID' => $orgUID, 'L.IsDeleted' => 0];
        if (!empty($filter['ProductUID'])) $where['L.ProductUID'] = (int)$filter['ProductUID'];
        if (!empty($filter['VendorUID']))  $where['L.VendorUID']  = (int)$filter['VendorUID'];
        if (!empty($filter['Source']))     $where['L.Source']     = (int)$filter['Source'];
        if (!empty($filter['Direction']))  $where['L.Direction']  = (int)$filter['Direction'];

        $applyDateSearch = function () use ($filter) {
            if (!empty($filter['DateFrom'])) $this->ReadDb->where('L.EntryDate >=', $filter['DateFrom']);
            if (!empty($filter['DateTo']))   $this->ReadDb->where('L.EntryDate <=', $filter['DateTo']);
            if (!empty($filter['Search'])) {
                $t = $this->ReadDb->escape_like_str(trim($filter['Search']));
                $this->ReadDb->group_start();
                $this->ReadDb->like('S.ProductName', $t, 'both');
                $this->ReadDb->or_like('S.VendorName', $t, 'both');
                $this->ReadDb->or_like('S.SKU', $t, 'both');
                $this->ReadDb->group_end();
            }
        };

        // Count
        $this->ReadDb->select('COUNT(L.PriceLogUID) AS Total');
        $this->ReadDb->from('Products.PurchasePriceLogTbl L');
        $this->ReadDb->join('Products.PurchasePriceLogSnapshotTbl S', 'S.PriceLogUID = L.PriceLogUID', 'left');
        $this->ReadDb->where($where);
        $applyDateSearch();
        $cq = $this->ReadDb->get();
        $result->totalCount = ($cq && $cq->num_rows()) ? (int)$cq->row()->Total : 0;

        if ($result->totalCount === 0) { $result->rows = []; return $result; }

        // Data
        $this->ReadDb->select([
            'L.PriceLogUID', 'L.ProductUID', 'L.VendorUID', 'L.EntryDate',
            'L.PurchasePrice', 'L.SellingPrice', 'L.Qty', 'L.Unit',
            'L.PreviousPrice', 'L.PriceDiff', 'L.PriceDiffPct', 'L.Direction',
            'L.Source', 'L.TransactionUID', 'L.Remarks', 'L.CreatedAt',
            'S.ProductName', 'S.SKU', 'S.VendorName', 'S.CategoryName',
            'S.BrandName', 'S.TaxPct', 'S.HSNCode',
        ]);
        $this->ReadDb->from('Products.PurchasePriceLogTbl L');
        $this->ReadDb->join('Products.PurchasePriceLogSnapshotTbl S', 'S.PriceLogUID = L.PriceLogUID', 'left');
        $this->ReadDb->where($where);
        $applyDateSearch();
        $this->ReadDb->order_by('L.EntryDate', 'DESC');
        $this->ReadDb->order_by('L.PriceLogUID', 'DESC');
        $this->ReadDb->limit($limit, $offset);
        $dq = $this->ReadDb->get();
        $result->rows = ($dq && $dq->num_rows()) ? $dq->result() : [];
        return $result;
    }

    /**
     * Single entry for manual edit form.
     *
     * @param int $orgUID
     * @param int $logUID
     * @returns object|null
     */
    public function getLogByUID(int $orgUID, int $logUID): ?object {
        $this->ReadDb->select([
            'L.PriceLogUID', 'L.ProductUID', 'L.VendorUID', 'L.EntryDate',
            'L.PurchasePrice', 'L.SellingPrice', 'L.Qty', 'L.Unit',
            'L.PreviousPrice', 'L.PriceDiff', 'L.PriceDiffPct', 'L.Direction',
            'L.Source', 'L.Remarks',
            'S.ProductName', 'S.VendorName',
        ]);
        $this->ReadDb->from('Products.PurchasePriceLogTbl L');
        $this->ReadDb->join('Products.PurchasePriceLogSnapshotTbl S', 'S.PriceLogUID = L.PriceLogUID', 'left');
        $this->ReadDb->where(['L.PriceLogUID' => $logUID, 'L.OrgUID' => $orgUID, 'L.IsDeleted' => 0]);
        $this->ReadDb->limit(1);
        $q = $this->ReadDb->get();
        return ($q && $q->num_rows()) ? $q->row() : null;
    }
}
