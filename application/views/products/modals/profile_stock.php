<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Product Profile Modal — Stock Movement Tab
 *
 * @var object|null $Prod        From getProductProfile()
 * @var array       $Moves       From getProductStockMovements() — chronological ASC
 * @var array       $Variants    From getProductVariants() — variant rows with AvailableQty
 * @var object      $JwtData
 * @var string      $Cur
 * @var int         $Dec
 * @var string      $DateFormat
 * @var int         $ProductUID
 */

$inModules = [105, 106];

$moduleLabels = [
    103 => 'Invoice',
    105 => 'Purchase',
    106 => 'Sales Return',
    108 => 'Purchase Return',
    112 => 'Delivery Challan',
];
$moduleColors = [
    103 => 'primary',
    105 => 'success',
    106 => 'info',
    108 => 'danger',
    112 => 'warning',
];

$openingQty = (float)($Prod->OpeningQuantity ?? 0);
$unitLabel  = htmlspecialchars($Prod->UnitShortName ?? '');

// When the product has variants, the authoritative current stock is the sum of all
// variant closing quantities — ProductVariantStockTbl is always updated correctly.
// Fall back to ProductStockTbl for plain (non-variant) products.
$hasVariants = !empty($Variants);
if ($hasVariants) {
    $currentStock = 0.0;
    foreach ($Variants as $vr) {
        $currentStock += (float)($vr->AvailableQty ?? 0);
    }
} else {
    $currentStock = (float)($Prod->AvailableQty ?? 0);
}

// Determine which columns are present in the variants
$hasSize   = false;
$hasBrand  = false;
$hasPartNo = false;
if ($hasVariants) {
    foreach ($Variants as $vr) {
        if (!empty($vr->SizeName))  $hasSize   = true;
        if (!empty($vr->BrandName)) $hasBrand  = true;
        if (!empty($vr->PartNumber)) $hasPartNo = true;
    }
}

// Build ledger with running balance
$runningBalance = $openingQty;
$ledger         = [];
foreach ($Moves as $m) {
    $muid = (int)($m['ModuleUID'] ?? 0);
    $qty  = (float)($m['Quantity'] ?? 0);
    $isIn = in_array($muid, $inModules, true);
    if ($isIn) {
        $runningBalance += $qty;
        $qtyIn  = $qty;
        $qtyOut = 0;
    } else {
        $runningBalance -= $qty;
        $qtyIn  = 0;
        $qtyOut = $qty;
    }
    $ledger[] = [
        'date'        => $m['TransDate'] ?? '',
        'refNo'       => $m['UniqueNumber'] ?? '—',
        'moduleUID'   => $muid,
        'moduleLabel' => $moduleLabels[$muid] ?? ('Module ' . $muid),
        'moduleColor' => $moduleColors[$muid] ?? 'secondary',
        'party'       => ($m['PartyType'] ?? '') === 'C'
                       ? ($m['CustomerName'] ?? '—')
                       : ($m['VendorName']   ?? '—'),
        'qtyIn'       => $qtyIn,
        'qtyOut'      => $qtyOut,
        'balance'     => $runningBalance,
        'isIn'        => $isIn,
    ];
}

$stockClass = $currentStock < 0 ? 'pp-sk-stat-red' : ($currentStock === 0.0 ? 'pp-sk-stat-amber' : 'pp-sk-stat-green');
?>
<style>
.pp-sk-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:20px}
.pp-sk-stat{border-radius:10px;padding:14px 16px;display:flex;flex-direction:column;gap:4px;border:1px solid transparent}
.pp-sk-stat-label{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;opacity:.7}
.pp-sk-stat-val{font-size:1.25rem;font-weight:800;line-height:1.1;white-space:nowrap}
.pp-sk-stat-sub{font-size:.72rem;opacity:.6;margin-top:1px}
.pp-sk-stat-green{background:rgba(22,163,74,.08);border-color:rgba(22,163,74,.15);color:#15803d}
.pp-sk-stat-red{background:rgba(220,38,38,.08);border-color:rgba(220,38,38,.15);color:#dc2626}
.pp-sk-stat-amber{background:rgba(217,119,6,.08);border-color:rgba(217,119,6,.15);color:#b45309}
.pp-sk-stat-blue{background:rgba(37,99,235,.08);border-color:rgba(37,99,235,.15);color:#2563eb}
.pp-sk-stat-slate{background:rgba(71,85,105,.07);border-color:rgba(71,85,105,.12);color:#475569}

.pp-sk-section-title{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#94a3b8;margin:0 0 10px}
.pp-sk-variant-grid{display:grid;gap:7px}
.pp-sk-variant-row{display:flex;align-items:center;justify-content:space-between;background:var(--bs-body-bg,#fff);border:1px solid rgba(0,0,0,.06);border-radius:8px;padding:9px 14px;font-size:.82rem}
.pp-sk-vr-chips{display:flex;gap:6px;align-items:center;flex-wrap:wrap}
.pp-sk-vr-qty{font-size:.88rem;font-weight:700;white-space:nowrap}
.pp-sk-vr-qty-in{color:#16a34a}
.pp-sk-vr-qty-zero{color:#94a3b8}
.pp-sk-vr-qty-neg{color:#dc2626}
.pp-sk-chip-size{display:inline-block;padding:2px 9px;border-radius:5px;font-size:.72rem;font-weight:600;background:#dbeafe;color:#1d4ed8}
.pp-sk-chip-brand{display:inline-block;padding:2px 9px;border-radius:5px;font-size:.72rem;font-weight:600;background:#ffedd5;color:#c2410c}
.pp-sk-open-label{font-size:.68rem;color:#94a3b8;margin-top:2px}

.pp-sk-ledger-wrap{border-radius:10px;overflow:hidden;border:1px solid rgba(0,0,0,.07)}
.pp-sk-ledger-table{width:100%;border-collapse:collapse;font-size:.8rem}
.pp-sk-ledger-table thead th{background:#f8fafc;padding:9px 12px;font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#64748b;border-bottom:1px solid #e2e8f0;white-space:nowrap}
.pp-sk-ledger-table tbody td{padding:9px 12px;border-bottom:1px solid #f1f5f9;vertical-align:middle}
.pp-sk-ledger-table tbody tr:last-child td{border-bottom:none}
.pp-sk-ledger-table tbody tr:hover td{background:#f8fafc}
.pp-sk-opening-row td{background:#f8fafc;font-weight:600;color:#64748b;font-size:.78rem}
.pp-sk-in-row td:first-child{border-left:3px solid #22c55e}
.pp-sk-out-row td:first-child{border-left:3px solid #f87171}
.pp-sk-qty-in{color:#16a34a;font-weight:700}
.pp-sk-qty-out{color:#ef4444;font-weight:700}
.pp-sk-balance-neg{color:#dc2626;font-weight:700}
.pp-sk-balance-ok{font-weight:700}

.pp-sk-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:36px 20px;color:#94a3b8;text-align:center;border:1px dashed #e2e8f0;border-radius:10px}
.pp-sk-empty i{font-size:2rem;margin-bottom:8px;opacity:.4}
.pp-sk-empty-title{font-size:.85rem;font-weight:600;color:#64748b}
.pp-sk-empty-sub{font-size:.75rem;margin-top:4px}

@media(max-width:480px){.pp-sk-stats{grid-template-columns:repeat(2,1fr)}.pp-sk-stats .pp-sk-stat:first-child{grid-column:1/-1}}

/* Dark mode */
@media(prefers-color-scheme:dark){
    .pp-sk-variant-row{border-color:rgba(255,255,255,.08)}
    .pp-sk-chip-size{background:#1e3a5f;color:#93c5fd}
    .pp-sk-chip-brand{background:#431407;color:#fb923c}
    .pp-sk-ledger-table thead th{background:#0f172a;border-color:#334155;color:#94a3b8}
    .pp-sk-ledger-table tbody td{border-color:#1e293b}
    .pp-sk-ledger-table tbody tr:hover td{background:#0f172a}
    .pp-sk-opening-row td{background:#0f172a;color:#64748b}
    .pp-sk-ledger-wrap{border-color:#334155}
    .pp-sk-empty{border-color:#334155}
}
:root[data-theme="dark"] .pp-sk-variant-row{border-color:rgba(255,255,255,.08)}
:root[data-theme="dark"] .pp-sk-chip-size{background:#1e3a5f;color:#93c5fd}
:root[data-theme="dark"] .pp-sk-chip-brand{background:#431407;color:#fb923c}
:root[data-theme="dark"] .pp-sk-ledger-table thead th{background:#0f172a;border-color:#334155;color:#94a3b8}
:root[data-theme="dark"] .pp-sk-ledger-table tbody td{border-color:#1e293b}
:root[data-theme="dark"] .pp-sk-ledger-table tbody tr:hover td{background:#0f172a}
:root[data-theme="dark"] .pp-sk-opening-row td{background:#0f172a;color:#64748b}
:root[data-theme="dark"] .pp-sk-ledger-wrap{border-color:#334155}
:root[data-theme="dark"] .pp-sk-empty{border-color:#334155}
:root[data-theme="light"] .pp-sk-variant-row{border-color:rgba(0,0,0,.06)}
</style>

<div class="p-3 p-md-4">

    <!-- ── Stats row ──────────────────────────────────────────────────────── -->
    <div class="pp-sk-stats">
        <div class="pp-sk-stat <?php echo $stockClass; ?>">
            <div class="pp-sk-stat-label">Current Stock</div>
            <div class="pp-sk-stat-val"><?php echo smartDecimal($currentStock); ?> <?php echo $unitLabel; ?></div>
            <?php if ($hasVariants): ?>
            <div class="pp-sk-stat-sub"><?php echo count($Variants); ?> variant<?php echo count($Variants) !== 1 ? 's' : ''; ?></div>
            <?php endif; ?>
        </div>
        <div class="pp-sk-stat pp-sk-stat-blue">
            <div class="pp-sk-stat-label">Opening Stock</div>
            <div class="pp-sk-stat-val"><?php echo smartDecimal($openingQty); ?> <?php echo $unitLabel; ?></div>
        </div>
        <div class="pp-sk-stat pp-sk-stat-slate">
            <div class="pp-sk-stat-label">Movements</div>
            <div class="pp-sk-stat-val"><?php echo count($ledger); ?></div>
        </div>
    </div>

    <?php if ($hasVariants): ?>
    <!-- ── Stock by Variant ───────────────────────────────────────────────── -->
    <div class="mb-4">
        <div class="pp-sk-section-title">Stock by Variant</div>
        <div class="pp-sk-variant-grid">
        <?php foreach ($Variants as $vr):
            $avail = (float)($vr->AvailableQty ?? 0);
            $open  = (float)($vr->OpeningQty   ?? 0);
            $qClass = $avail < 0 ? 'pp-sk-vr-qty-neg' : ($avail === 0.0 ? 'pp-sk-vr-qty-zero' : 'pp-sk-vr-qty-in');
        ?>
            <div class="pp-sk-variant-row">
                <div class="pp-sk-vr-chips">
                    <?php if ($hasBrand && !empty($vr->BrandName)): ?>
                        <span class="pp-sk-chip-brand"><?php echo htmlspecialchars($vr->BrandName); ?></span>
                    <?php endif; ?>
                    <?php if ($hasSize && !empty($vr->SizeName)): ?>
                        <span class="pp-sk-chip-size"><?php echo htmlspecialchars($vr->SizeName); ?></span>
                    <?php endif; ?>
                    <?php if ($hasPartNo && !empty($vr->PartNumber)): ?>
                        <span class="bill-partno-badge"><?php echo htmlspecialchars($vr->PartNumber); ?></span>
                    <?php endif; ?>
                </div>
                <div class="text-end">
                    <div class="pp-sk-vr-qty <?php echo $qClass; ?>"><?php echo smartDecimal($avail); ?> <?php echo $unitLabel; ?></div>
                    <div class="pp-sk-open-label">Opening: <?php echo smartDecimal($open); ?></div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── Movement Ledger ───────────────────────────────────────────────── -->
    <div class="pp-sk-section-title">Movement History</div>

    <?php if (empty($ledger)): ?>
    <div class="pp-sk-empty">
        <i class="bx bx-trending-up"></i>
        <div class="pp-sk-empty-title">No stock movements recorded yet</div>
        <div class="pp-sk-empty-sub">Opening stock: <?php echo smartDecimal($openingQty); ?> <?php echo $unitLabel; ?></div>
    </div>
    <?php else: ?>
    <div class="pp-sk-ledger-wrap">
        <div style="overflow-x:auto;">
        <table class="pp-sk-ledger-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Ref No</th>
                    <th>Type</th>
                    <th>Party</th>
                    <th class="text-center">In</th>
                    <th class="text-center">Out</th>
                    <th class="text-end">Balance</th>
                </tr>
            </thead>
            <tbody>
                <tr class="pp-sk-opening-row">
                    <td colspan="6">Opening Balance</td>
                    <td class="text-end"><?php echo smartDecimal($openingQty); ?> <?php echo $unitLabel; ?></td>
                </tr>
                <?php foreach ($ledger as $row): ?>
                <tr class="<?php echo $row['isIn'] ? 'pp-sk-in-row' : 'pp-sk-out-row'; ?>">
                    <td class="text-nowrap"><?php echo !empty($row['date']) ? date($DateFormat, strtotime($row['date'])) : '—'; ?></td>
                    <td class="fw-semibold text-nowrap"><?php echo htmlspecialchars($row['refNo']); ?></td>
                    <td>
                        <span class="badge bg-label-<?php echo $row['moduleColor']; ?>" style="font-size:.68rem;">
                            <?php echo htmlspecialchars($row['moduleLabel']); ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($row['party']); ?></td>
                    <td class="text-center pp-sk-qty-in"><?php echo $row['qtyIn'] > 0 ? '+' . smartDecimal($row['qtyIn']) : '<span class="text-muted">—</span>'; ?></td>
                    <td class="text-center pp-sk-qty-out"><?php echo $row['qtyOut'] > 0 ? '−' . smartDecimal($row['qtyOut']) : '<span class="text-muted">—</span>'; ?></td>
                    <td class="text-end <?php echo $row['balance'] < 0 ? 'pp-sk-balance-neg' : 'pp-sk-balance-ok'; ?>">
                        <?php echo smartDecimal($row['balance']); ?> <?php echo $unitLabel; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php endif; ?>

</div>
