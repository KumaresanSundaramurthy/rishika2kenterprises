<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Product Profile Modal — Transactions Tab
 *
 * @var array  $Rows       From getProductTransactionHistory()
 * @var object $JwtData
 * @var string $Cur
 * @var int    $Dec
 * @var string $DateFormat
 * @var int    $ProductUID
 */

$moduleMap = [
    101 => ['label' => 'Quotation',        'color' => 'warning',   'dir' => 'none'],
    102 => ['label' => 'Sales Order',      'color' => 'info',      'dir' => 'none'],
    103 => ['label' => 'Invoice',          'color' => 'primary',   'dir' => 'out'],
    104 => ['label' => 'Purchase Order',   'color' => 'secondary', 'dir' => 'none'],
    105 => ['label' => 'Purchase',         'color' => 'success',   'dir' => 'in'],
    106 => ['label' => 'Sales Return',     'color' => 'danger',    'dir' => 'in'],
    108 => ['label' => 'Purchase Return',  'color' => 'danger',    'dir' => 'out'],
    109 => ['label' => 'Debit Note',       'color' => 'secondary', 'dir' => 'none'],
    112 => ['label' => 'Delivery Challan', 'color' => 'info',      'dir' => 'out'],
    113 => ['label' => 'Proforma Invoice', 'color' => 'secondary', 'dir' => 'none'],
];

$statusColors = [
    'Draft'     => 'secondary',
    'Confirmed' => 'primary',
    'Issued'    => 'primary',
    'Partial'   => 'warning',
    'Completed' => 'success',
    'Paid'      => 'success',
    'Cancelled' => 'danger',
    'Rejected'  => 'danger',
    'Pending'   => 'warning',
];

// Collect distinct modules and statuses present in the data
$presentModules  = [];
$presentStatuses = [];
$hasVariants     = false;
foreach ($Rows as $r) {
    $mid = (int)($r['ModuleUID'] ?? 0);
    if ($mid && !isset($presentModules[$mid])) {
        $presentModules[$mid] = $moduleMap[$mid] ?? ['label' => 'Module ' . $mid, 'color' => 'secondary', 'dir' => 'none'];
    }
    $st = $r['DocStatus'] ?? '';
    if ($st) $presentStatuses[$st] = true;
    if (!empty($r['VariantLabel'])) $hasVariants = true;
}
?>
<style>
.pp-tx-bar{display:flex;align-items:center;gap:8px;flex-wrap:wrap;padding:10px 16px;border-bottom:1px solid rgba(0,0,0,.06)}
.pp-tx-bar .form-select{font-size:.78rem;padding:4px 28px 4px 10px;width:auto;min-width:130px;max-width:190px}
.pp-tx-count{font-size:.75rem;color:#94a3b8;margin-left:auto;white-space:nowrap}

.pp-tx-wrap{overflow-x:auto}
.pp-tx-tbl{width:100%;border-collapse:collapse;font-size:.81rem}
.pp-tx-tbl thead th{background:#f8fafc;padding:9px 12px;font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#64748b;border-bottom:1px solid #e2e8f0;white-space:nowrap;position:sticky;top:0;z-index:1}
.pp-tx-tbl tbody td{padding:9px 12px;border-bottom:1px solid #f1f5f9;vertical-align:middle}
.pp-tx-tbl tbody tr:last-child td{border-bottom:none}
.pp-tx-tbl tbody tr:hover td{background:#f8fafc}

.pp-tx-ref{font-weight:700;color:#374151;font-size:.82rem}
.pp-tx-date{font-size:.72rem;color:#94a3b8;margin-top:2px}
.pp-tx-party{font-size:.82rem;color:#374151;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:150px}
.pp-tx-qty-in{color:#16a34a;font-weight:700}
.pp-tx-qty-out{color:#ef4444;font-weight:700}
.pp-tx-qty-neutral{color:#374151;font-weight:600}
.pp-tx-amt{font-weight:700;color:#1e293b;white-space:nowrap}
.pp-tx-variant-chip{display:inline-block;padding:2px 8px;border-radius:5px;font-size:.7rem;font-weight:600;background:#dbeafe;color:#1d4ed8;white-space:nowrap}
.pp-tx-footer-note{padding:8px 14px;font-size:.72rem;color:#94a3b8;border-top:1px solid #f1f5f9;background:#f8fafc}
.pp-tx-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:48px 20px;color:#94a3b8;text-align:center}
.pp-tx-empty i{font-size:2.2rem;margin-bottom:10px;opacity:.35}
.pp-tx-empty-title{font-size:.88rem;font-weight:600;color:#64748b}

@media(prefers-color-scheme:dark){
    .pp-tx-bar{border-color:rgba(255,255,255,.08)}
    .pp-tx-tbl thead th{background:#0f172a;color:#94a3b8;border-color:#334155}
    .pp-tx-tbl tbody td{border-color:#1e293b}
    .pp-tx-tbl tbody tr:hover td{background:#0f172a}
    .pp-tx-ref{color:#e2e8f0}
    .pp-tx-party{color:#e2e8f0}
    .pp-tx-amt{color:#f1f5f9}
    .pp-tx-variant-chip{background:#1e3a5f;color:#93c5fd}
    .pp-tx-footer-note{background:#0f172a;border-color:#334155;color:#64748b}
}
:root[data-theme="dark"] .pp-tx-bar{border-color:rgba(255,255,255,.08)}
:root[data-theme="dark"] .pp-tx-tbl thead th{background:#0f172a;color:#94a3b8;border-color:#334155}
:root[data-theme="dark"] .pp-tx-tbl tbody td{border-color:#1e293b}
:root[data-theme="dark"] .pp-tx-tbl tbody tr:hover td{background:#0f172a}
:root[data-theme="dark"] .pp-tx-ref{color:#e2e8f0}
:root[data-theme="dark"] .pp-tx-party{color:#e2e8f0}
:root[data-theme="dark"] .pp-tx-amt{color:#f1f5f9}
:root[data-theme="dark"] .pp-tx-variant-chip{background:#1e3a5f;color:#93c5fd}
:root[data-theme="dark"] .pp-tx-footer-note{background:#0f172a;border-color:#334155;color:#64748b}
:root[data-theme="light"] .pp-tx-bar{border-color:rgba(0,0,0,.06)}
</style>

<!-- ── Filter bar ─────────────────────────────────────────────────────── -->
<div class="pp-tx-bar">
    <select class="form-select pp-tx-filter" id="ppTxModuleFilter">
        <option value="">All Modules</option>
        <?php foreach ($presentModules as $mid => $m): ?>
        <option value="<?php echo $mid; ?>"><?php echo htmlspecialchars($m['label']); ?></option>
        <?php endforeach; ?>
    </select>
    <select class="form-select pp-tx-filter" id="ppTxStatusFilter">
        <option value="">All Status</option>
        <?php foreach (array_keys($presentStatuses) as $st): ?>
        <option value="<?php echo htmlspecialchars($st); ?>"><?php echo htmlspecialchars($st); ?></option>
        <?php endforeach; ?>
    </select>
    <span class="pp-tx-count" id="ppTxVisibleCount"><?php echo count($Rows); ?> record<?php echo count($Rows) !== 1 ? 's' : ''; ?></span>
</div>

<?php if (empty($Rows)): ?>
<div class="pp-tx-empty">
    <i class="bx bx-receipt"></i>
    <div class="pp-tx-empty-title">No transactions found for this product</div>
</div>
<?php else: ?>
<div class="pp-tx-wrap">
    <table class="pp-tx-tbl" id="ppTxTable">
        <thead>
            <tr>
                <th>Date &amp; Ref</th>
                <th>Module</th>
                <th>Party</th>
                <?php if ($hasVariants): ?><th>Variant</th><?php endif; ?>
                <th class="text-end">Qty</th>
                <th class="text-end">Unit Price</th>
                <th class="text-end">Amount</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($Rows as $r):
            $muid     = (int)($r['ModuleUID'] ?? 0);
            $mMeta    = $moduleMap[$muid] ?? ['label' => 'Tx', 'color' => 'secondary', 'dir' => 'none'];
            $status   = $r['DocStatus'] ?? '—';
            $badgeCls = $statusColors[$status] ?? 'secondary';
            $party    = $r['PartyType'] === 'C'
                      ? htmlspecialchars($r['CustomerName'] ?? '—')
                      : htmlspecialchars($r['VendorName']   ?? '—');
            $qty      = (float)($r['Quantity']   ?? 0);
            $up       = (float)($r['UnitPrice']  ?? 0);
            $amount   = (float)($r['LineAmount'] ?? 0);
            if ($amount == 0) $amount = $qty * $up;
            $dir      = $mMeta['dir'];
            $variant  = htmlspecialchars(trim($r['VariantLabel'] ?? ''));
        ?>
        <tr data-module="<?php echo $muid; ?>" data-status="<?php echo htmlspecialchars($status); ?>">
            <td>
                <div class="pp-tx-ref"><?php echo htmlspecialchars($r['UniqueNumber'] ?? '—'); ?></div>
                <div class="pp-tx-date"><?php echo !empty($r['TransDate']) ? date($DateFormat, strtotime($r['TransDate'])) : '—'; ?></div>
            </td>
            <td>
                <span class="badge bg-label-<?php echo $mMeta['color']; ?>" style="font-size:.68rem;">
                    <?php echo htmlspecialchars($mMeta['label']); ?>
                </span>
            </td>
            <td><div class="pp-tx-party" title="<?php echo $party; ?>"><?php echo $party; ?></div></td>
            <?php if ($hasVariants): ?>
            <td><?php echo $variant ? '<span class="pp-tx-variant-chip">' . $variant . '</span>' : '<span class="text-muted">—</span>'; ?></td>
            <?php endif; ?>
            <td class="text-end text-nowrap">
                <?php if ($dir === 'in'): ?>
                    <span class="pp-tx-qty-in">+<?php echo smartDecimal($qty); ?></span>
                <?php elseif ($dir === 'out'): ?>
                    <span class="pp-tx-qty-out">−<?php echo smartDecimal($qty); ?></span>
                <?php else: ?>
                    <span class="pp-tx-qty-neutral"><?php echo smartDecimal($qty); ?></span>
                <?php endif; ?>
            </td>
            <td class="text-end text-nowrap text-muted" style="font-size:.78rem;"><?php echo $Cur . ' ' . number_format($up, $Dec, '.', ''); ?></td>
            <td class="text-end text-nowrap pp-tx-amt"><?php echo $Cur . ' ' . number_format($amount, $Dec, '.', ''); ?></td>
            <td><span class="badge bg-label-<?php echo $badgeCls; ?>" style="font-size:.68rem;"><?php echo htmlspecialchars($status); ?></span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<div class="pp-tx-footer-note">Showing up to 100 most recent transactions &mdash; use the transaction pages for full history.</div>
<?php endif; ?>

<script>
(function () {
    var $modSel = document.getElementById('ppTxModuleFilter');
    var $stSel  = document.getElementById('ppTxStatusFilter');
    var $count  = document.getElementById('ppTxVisibleCount');
    var $rows   = document.querySelectorAll('#ppTxTable tbody tr');

    function applyFilter() {
        var mod = $modSel ? $modSel.value : '';
        var st  = $stSel  ? $stSel.value  : '';
        var visible = 0;
        $rows.forEach(function (tr) {
            var show = (!mod || tr.dataset.module === mod) && (!st || tr.dataset.status === st);
            tr.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        if ($count) $count.textContent = visible + ' record' + (visible !== 1 ? 's' : '');
    }

    if ($modSel) $modSel.addEventListener('change', applyFilter);
    if ($stSel)  $stSel.addEventListener('change', applyFilter);
})();
</script>
