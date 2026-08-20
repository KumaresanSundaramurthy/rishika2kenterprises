<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Purchasepricelist_model extends MY_Model {

    private $ReadDb;
    private $WriteDB;

    public function __construct() {
        parent::__construct();
        $this->ReadDb  = $this->load->database('ReadDB',  TRUE);
        $this->WriteDB = $this->load->database('WriteDB', TRUE);
    }

    /**
     * UPSERT one row per ProductUID+VendorUID with the latest purchase data.
     * Called after every finalized (non-draft) purchase is committed.
     *
     * @param int    $orgUID
     * @param int    $branchUID
     * @param int    $vendorUID
     * @param int    $transUID
     * @param string $transDate      Y-m-d
     * @param string $uniqueNumber
     * @param string $financialYear
     * @param int    $userUID
     * @param array  $items
     * @returns void
     */
    public function upsertFromPurchase(
        int $orgUID, int $branchUID, int $vendorUID,
        int $transUID, string $transDate, string $uniqueNumber,
        string $financialYear, int $userUID, array $items
    ): void {
        if (empty($items)) return;

        $now = date('Y-m-d H:i:s');

        foreach ($items as $item) {
            $productUID = (int)($item['id'] ?? 0);
            if ($productUID <= 0) continue;

            $qty        = (float)($item['quantity']          ?? 0);
            if ($qty <= 0) continue;

            $purchasePrice  = (float)($item['unitPrice']          ?? 0);
            $isPriceIncl    = (int)(bool)($item['purchasePriceIsIncl'] ?? 0);
            $taxPct         = (float)($item['taxPercent']         ?? 0);
            $cgstAmt        = (float)($item['cgstAmount']         ?? 0);
            $sgstAmt        = (float)($item['sgstAmount']         ?? 0);
            $igstAmt        = (float)($item['igstAmount']         ?? 0);
            $discountPct    = (float)($item['discount']           ?? 0);
            $discountAmt    = (float)($item['discount_amount']    ?? 0);
            $totalAmt       = (float)($item['net_total']          ?? 0);
            $unit           = substr((string)($item['primaryUnit'] ?? ''), 0, 50);
            $hsnCode        = substr((string)($item['hsnCode']    ?? ''), 0, 50);
            $variantUID     = !empty($item['variantUID']) ? (int)$item['variantUID'] : null;

            // PurchasePrice stored is always the exclusive unit price.
            // Total tax for this line spans CGST + SGST + IGST (one of the two pairs will be 0
            // depending on whether the purchase is intra-state or inter-state — both are correct).
            $totalLineTax = $cgstAmt + $sgstAmt + $igstAmt;

            // Fallback: if taxPercent was 0 (product uses a tax-slab UID rather than a plain %),
            // derive it from the actual tax amounts: tax / (excl_price × qty) × 100.
            if ($taxPct == 0.0 && $totalLineTax > 0.0 && $purchasePrice > 0.0 && $qty > 0.0) {
                $taxPct = round($totalLineTax / ($purchasePrice * $qty) * 100, 4);
            }

            // Fallback: if net_total was missing, reconstruct as excl_total + total_tax − discount.
            if ($totalAmt == 0.0 && $purchasePrice > 0.0 && $qty > 0.0) {
                $totalAmt = round(($purchasePrice * $qty) + $totalLineTax - $discountAmt, 4);
            }

            $this->WriteDB->query(
                "INSERT INTO Products.PurchasePriceListTbl
                    (OrgUID, BranchUID, ProductUID, VendorUID,
                     PurchasePrice, IsPurchasePriceIncl, TaxPct,
                     CGSTAmt, SGSTAmt, IGSTAmt,
                     Qty, Unit,
                     DiscountPct, DiscountAmt, TotalAmt,
                     HSNCode, VariantUID,
                     LastTransUID, LastUniqueNumber, LastPurchaseDate, FinancialYear,
                     CreatedBy, CreatedOn, UpdatedBy, UpdatedOn)
                 VALUES (?,?,?,?, ?,?,?, ?,?,?, ?,?, ?,?,?, ?,?, ?,?,?,?, ?,?,?,?)
                 ON DUPLICATE KEY UPDATE
                     PurchasePrice       = VALUES(PurchasePrice),
                     IsPurchasePriceIncl = VALUES(IsPurchasePriceIncl),
                     TaxPct              = VALUES(TaxPct),
                     CGSTAmt             = VALUES(CGSTAmt),
                     SGSTAmt             = VALUES(SGSTAmt),
                     IGSTAmt             = VALUES(IGSTAmt),
                     Qty                 = VALUES(Qty),
                     Unit                = VALUES(Unit),
                     DiscountPct         = VALUES(DiscountPct),
                     DiscountAmt         = VALUES(DiscountAmt),
                     TotalAmt            = VALUES(TotalAmt),
                     HSNCode             = VALUES(HSNCode),
                     VariantUID          = VALUES(VariantUID),
                     LastTransUID        = VALUES(LastTransUID),
                     LastUniqueNumber    = VALUES(LastUniqueNumber),
                     LastPurchaseDate    = VALUES(LastPurchaseDate),
                     FinancialYear       = VALUES(FinancialYear),
                     UpdatedBy           = VALUES(UpdatedBy),
                     UpdatedOn           = VALUES(UpdatedOn)",
                [
                    $orgUID, $branchUID, $productUID, $vendorUID,
                    $purchasePrice, $isPriceIncl, $taxPct,
                    $cgstAmt, $sgstAmt, $igstAmt,
                    $qty, $unit,
                    $discountPct, $discountAmt, $totalAmt,
                    $hsnCode, $variantUID,
                    $transUID, $uniqueNumber, $transDate, $financialYear,
                    $userUID, $now, $userUID, $now,
                ]
            );
        }
    }

    /**
     * Paginated purchase price list with live product + vendor names via JOIN.
     *
     * @param int   $orgUID
     * @param int   $branchUID
     * @param int   $limit
     * @param int   $offset
     * @param array $filter  keys: search, ProductUID, VendorUID
     * @returns object{rows: object[], totalCount: int}
     */
    public function getPriceList(int $orgUID, int $branchUID, int $limit, int $offset, array $filter = []): object {
        $result             = new stdClass();
        $result->rows       = [];
        $result->totalCount = 0;

        $where = ['L.OrgUID' => $orgUID, 'L.BranchUID' => $branchUID];
        if (!empty($filter['ProductUID'])) $where['L.ProductUID'] = (int)$filter['ProductUID'];
        if (!empty($filter['VendorUID']))  $where['L.VendorUID']  = (int)$filter['VendorUID'];

        $applySearch = function () use ($filter): void {
            $search = trim((string)($filter['search'] ?? ''));
            if ($search === '') return;
            foreach (preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY) as $t) {
                $this->ReadDb->group_start();
                $this->ReadDb->like('P.ItemName', $t, 'both');
                $this->ReadDb->or_like('P.SKU',   $t, 'both');
                $this->ReadDb->or_like('V.Name',   $t, 'both');
                $this->ReadDb->or_like('L.HSNCode', $t, 'both');
                $this->ReadDb->or_like('L.LastUniqueNumber', $t, 'both');
                $this->ReadDb->group_end();
            }
        };

        // Count
        $this->ReadDb->select('COUNT(L.PriceListUID) AS Total');
        $this->ReadDb->from('Products.PurchasePriceListTbl L');
        $this->ReadDb->join('Products.ProductTbl P',  'P.ProductUID = L.ProductUID AND P.IsDeleted = 0', 'left');
        $this->ReadDb->join('Vendors.VendorTbl V',    'V.VendorUID  = L.VendorUID',                     'left');
        $this->ReadDb->where($where);
        $applySearch();
        $cq = $this->ReadDb->get();
        $result->totalCount = ($cq && $cq->num_rows()) ? (int)$cq->row()->Total : 0;

        if ($result->totalCount === 0) return $result;

        // Data
        $this->ReadDb->select([
            'L.PriceListUID', 'L.ProductUID', 'L.VendorUID', 'L.BranchUID',
            'L.PurchasePrice', 'L.IsPurchasePriceIncl', 'L.TaxPct',
            'L.CGSTAmt', 'L.SGSTAmt', 'L.IGSTAmt',
            'L.Qty', 'L.Unit',
            'L.DiscountPct', 'L.DiscountAmt', 'L.TotalAmt',
            'L.HSNCode', 'L.VariantUID',
            'L.LastTransUID', 'L.LastUniqueNumber', 'L.LastPurchaseDate', 'L.FinancialYear',
            'L.UpdatedOn',
            'P.ItemName', 'P.SKU',
            'V.Name AS VendorName',
            "CONCAT(U.FirstName, ' ', U.LastName) AS UpdatedByName",
        ]);
        $this->ReadDb->from('Products.PurchasePriceListTbl L');
        $this->ReadDb->join('Products.ProductTbl P',  'P.ProductUID = L.ProductUID AND P.IsDeleted = 0', 'left');
        $this->ReadDb->join('Vendors.VendorTbl V',    'V.VendorUID  = L.VendorUID',                     'left');
        $this->ReadDb->join('Users.UserTbl U',        'U.UserUID    = L.UpdatedBy',                     'left');
        $this->ReadDb->where($where);
        $applySearch();
        $this->ReadDb->order_by('L.UpdatedOn', 'DESC');
        $this->ReadDb->order_by('L.PriceListUID', 'DESC');
        $this->ReadDb->limit($limit, $offset);
        $dq = $this->ReadDb->get();
        $result->rows = ($dq && $dq->num_rows()) ? $dq->result() : [];

        return $result;
    }

    /**
     * Single price list entry with product + vendor names (for detail page header).
     *
     * @param int $orgUID
     * @param int $priceListUID
     * @returns object|null
     */
    public function getDetailEntry(int $orgUID, int $priceListUID): ?object {
        $this->ReadDb->select([
            'L.PriceListUID', 'L.ProductUID', 'L.VendorUID', 'L.BranchUID',
            'L.PurchasePrice', 'L.IsPurchasePriceIncl', 'L.TaxPct',
            'L.CGSTAmt', 'L.SGSTAmt', 'L.IGSTAmt',
            'L.Qty', 'L.Unit',
            'L.DiscountPct', 'L.DiscountAmt', 'L.TotalAmt',
            'L.HSNCode', 'L.VariantUID',
            'L.LastTransUID', 'L.LastUniqueNumber', 'L.LastPurchaseDate', 'L.FinancialYear',
            'L.CreatedOn', 'L.UpdatedOn',
            'P.ItemName', 'P.SKU', 'P.Description AS ProductDescription',
            'V.Name AS VendorName',
        ]);
        $this->ReadDb->from('Products.PurchasePriceListTbl L');
        $this->ReadDb->join('Products.ProductTbl P',  'P.ProductUID = L.ProductUID AND P.IsDeleted = 0', 'left');
        $this->ReadDb->join('Vendors.VendorTbl V',    'V.VendorUID  = L.VendorUID',                     'left');
        $this->ReadDb->where(['L.PriceListUID' => $priceListUID, 'L.OrgUID' => $orgUID]);
        $this->ReadDb->limit(1);
        $q = $this->ReadDb->get();
        return ($q && $q->num_rows()) ? $q->row() : null;
    }

    /**
     * Paginated purchase price history for a specific product + vendor (for detail page).
     *
     * @param int $orgUID
     * @param int $productUID
     * @param int $vendorUID
     * @param int $limit
     * @param int $offset
     * @returns object{rows: object[], totalCount: int}
     */
    public function getPriceHistory(int $orgUID, int $productUID, int $vendorUID, int $limit, int $offset): object {
        $result             = new stdClass();
        $result->rows       = [];
        $result->totalCount = 0;

        $where = [
            'L.OrgUID'     => $orgUID,
            'L.ProductUID' => $productUID,
            'L.VendorUID'  => $vendorUID,
            'L.IsDeleted'  => 0,
        ];

        // Count
        $this->ReadDb->select('COUNT(L.PriceLogUID) AS Total');
        $this->ReadDb->from('Products.PurchasePriceLogTbl L');
        $this->ReadDb->where($where);
        $cq = $this->ReadDb->get();
        $result->totalCount = ($cq && $cq->num_rows()) ? (int)$cq->row()->Total : 0;

        if ($result->totalCount === 0) return $result;

        // Data
        $this->ReadDb->select([
            'L.PriceLogUID', 'L.EntryDate', 'L.PurchasePrice', 'L.Qty', 'L.Unit',
            'L.PreviousPrice', 'L.PriceDiff', 'L.PriceDiffPct', 'L.Direction',
            'L.Source', 'L.TransactionUID', 'L.Remarks', 'L.CreatedAt',
        ]);
        $this->ReadDb->from('Products.PurchasePriceLogTbl L');
        $this->ReadDb->where($where);
        $this->ReadDb->order_by('L.EntryDate',    'DESC');
        $this->ReadDb->order_by('L.PriceLogUID',  'DESC');
        $this->ReadDb->limit($limit, $offset);
        $dq = $this->ReadDb->get();
        $result->rows = ($dq && $dq->num_rows()) ? $dq->result() : [];

        return $result;
    }
}
