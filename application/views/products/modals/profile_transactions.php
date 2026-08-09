<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Product Profile Modal — Transactions Tab
 * All modules in one table; client-side filter by module and status.
 *
 * @var array  $Rows       From getProductTransactionHistory()
 * @var object $JwtData
 * @var string $Cur
 * @var int    $Dec
 * @var string $DateFormat
 * @var int    $ProductUID
 */

// Module metadata: uid => [label, badge colour, direction]
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

// Collect distinct modules and statuses that actually appear in the data
$presentModules  = [];
$presentStatuses = [];
foreach ($Rows as $r) {
    $mid = (int)($r['ModuleUID'] ?? 0);
    if ($mid && !isset($presentModules[$mid])) {
        $presentModules[$mid] = $moduleMap[$mid] ?? ['label' => 'Module ' . $mid, 'color' => 'secondary', 'dir' => 'none'];
    }
    $st = $r['DocStatus'] ?? '';
    if ($st) $presentStatuses[$st] = true;
}
?>

<div class="p-3 pb-0 pp-tx-filter-bar">
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <select class="form-select form-select-sm pp-tx-filter" id="ppTxModuleFilter" style="width:auto;min-width:140px;">
            <option value="">All Modules</option>
            <?php foreach ($presentModules as $mid => $m): ?>
            <option value="<?php echo $mid; ?>"><?php echo htmlspecialchars($m['label']); ?></option>
            <?php endforeach; ?>
        </select>
        <select class="form-select form-select-sm pp-tx-filter" id="ppTxStatusFilter" style="width:auto;min-width:120px;">
            <option value="">All Status</option>
            <?php foreach (array_keys($presentStatuses) as $st): ?>
            <option value="<?php echo htmlspecialchars($st); ?>"><?php echo htmlspecialchars($st); ?></option>
            <?php endforeach; ?>
        </select>
        <span class="text-muted pp-tx-count-label ms-auto" id="ppTxVisibleCount">
            <?php echo count($Rows); ?> record<?php echo count($Rows) !== 1 ? 's' : ''; ?>
        </span>
    </div>
</div>

<?php if (empty($Rows)): ?>
<div class="d-flex flex-column align-items-center justify-content-center py-5 text-muted">
    <i class="bx bx-receipt pp-empty-icon mb-2"></i>
    <div>No transactions found for this product.</div>
</div>
<?php else: ?>
<div class="table-responsive pp-tx-table-wrap">
    <table class="table table-sm table-hover mb-0 pp-tx-table" id="ppTxTable">
        <thead class="table-light">
            <tr>
                <th class="px-3">Date</th>
                <th>Ref No</th>
                <th>Module</th>
                <th>Party</th>
                <th class="text-end">Qty</th>
                <th class="text-end">Unit Price</th>
                <th class="text-end">Amount</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($Rows as $r):
            $muid      = (int)($r['ModuleUID'] ?? 0);
            $mMeta     = $moduleMap[$muid] ?? ['label' => 'Tx', 'color' => 'secondary', 'dir' => 'none'];
            $status    = $r['DocStatus'] ?? '—';
            $badgeCls  = $statusColors[$status] ?? 'secondary';
            $party     = $r['PartyType'] === 'C'
                       ? htmlspecialchars($r['CustomerName'] ?? '—')
                       : htmlspecialchars($r['VendorName']   ?? '—');
            $qty       = (float)($r['Quantity']    ?? 0);
            $unitPrice = (float)($r['UnitPrice']   ?? 0);
            $amount    = (float)($r['LineAmount']  ?? $qty * $unitPrice);
            $dir       = $mMeta['dir'];
        ?>
        <tr data-module="<?php echo $muid; ?>" data-status="<?php echo htmlspecialchars($status); ?>">
            <td class="px-3 text-nowrap">
                <?php echo !empty($r['TransDate']) ? date($DateFormat, strtotime($r['TransDate'])) : '—'; ?>
            </td>
            <td class="fw-semibold text-nowrap"><?php echo htmlspecialchars($r['UniqueNumber'] ?? '—'); ?></td>
            <td>
                <span class="badge bg-label-<?php echo $mMeta['color']; ?> pp-module-badge">
                    <?php echo htmlspecialchars($mMeta['label']); ?>
                </span>
            </td>
            <td><?php echo $party; ?></td>
            <td class="text-end text-nowrap">
                <?php if ($dir === 'in'): ?>
                    <span class="pp-dir-in">+<?php echo smartDecimal($qty); ?></span>
                <?php elseif ($dir === 'out'): ?>
                    <span class="pp-dir-out">-<?php echo smartDecimal($qty); ?></span>
                <?php else: ?>
                    <?php echo smartDecimal($qty); ?>
                <?php endif; ?>
            </td>
            <td class="text-end text-nowrap"><?php echo $Cur . ' ' . number_format($unitPrice, $Dec); ?></td>
            <td class="text-end text-nowrap fw-semibold"><?php echo $Cur . ' ' . number_format($amount, $Dec); ?></td>
            <td><span class="badge bg-label-<?php echo $badgeCls; ?>"><?php echo htmlspecialchars($status); ?></span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="px-3 py-2 border-top text-muted pp-tx-footer">
    Showing up to 200 most recent transactions. Use the transaction pages for full history.
</div>
<?php endif; ?>
