<?php defined('BASEPATH') OR exit('No direct script access allowed');

$JwtData      = $JwtData      ?? null;
$DataLists    = $DataLists    ?? [];
$SerialNumber = $SerialNumber ?? 0;

$fmt      = $JwtData->GenSettings->ListDateFormat ?? 'd M Y';
$dec      = (int)($JwtData->GenSettings->DecimalPoints ?? 2);
$currency = $JwtData->GenSettings->CurrenySymbol ?? '₹';

if (empty($DataLists)) : ?>
<tr>
    <td colspan="8" class="text-center py-5 text-muted">
        <i class="bx bx-history fs-3 d-block mb-2"></i>
        No purchase history found.
    </td>
</tr>
<?php return; endif;

foreach ($DataLists as $i => $row):
    $entryDate     = !empty($row->EntryDate) ? date($fmt, strtotime($row->EntryDate)) : '—';
    $purchasePrice = (float)($row->PurchasePrice  ?? 0);
    $prevPrice     = (float)($row->PreviousPrice  ?? 0);
    $priceDiff     = (float)($row->PriceDiff      ?? 0);
    $priceDiffPct  = (float)($row->PriceDiffPct   ?? 0);
    $direction     = strtolower(trim($row->Direction ?? ''));
    $qty           = (float)($row->Qty            ?? 0);
    $unit          = htmlspecialchars($row->Unit  ?? '');
    $transUID      = (int)($row->TransactionUID   ?? 0);
    $source        = htmlspecialchars($row->Source ?? '');
    $remarks       = htmlspecialchars($row->Remarks ?? '');

    // Direction badge
    if ($direction === 'up') {
        $dirBadge = '<span class="badge bg-label-danger"><i class="bx bx-up-arrow-alt me-1"></i>' . number_format($priceDiffPct, 2) . '%</span>';
    } elseif ($direction === 'down') {
        $dirBadge = '<span class="badge bg-label-success"><i class="bx bx-down-arrow-alt me-1"></i>' . number_format(abs($priceDiffPct), 2) . '%</span>';
    } else {
        $dirBadge = '<span class="badge bg-label-secondary">New</span>';
    }
?>
<tr>
    <td class="r2k-sl-col"><?php echo $SerialNumber + $i + 1; ?></td>

    <td><?php echo $entryDate; ?></td>

    <td class="text-end fw-semibold">
        <?php echo $currency . ' ' . number_format($purchasePrice, $dec, '.', ','); ?>
        <?php if ($unit): ?>
            <div class="text-muted tinysmall">per <?php echo $unit; ?></div>
        <?php endif; ?>
    </td>

    <td class="text-end">
        <?php if ($prevPrice > 0): ?>
            <?php echo $currency . ' ' . number_format($prevPrice, $dec, '.', ','); ?>
        <?php else: ?>
            <span class="text-muted">—</span>
        <?php endif; ?>
    </td>

    <td class="text-center">
        <?php echo $dirBadge; ?>
        <?php if ($priceDiff != 0): ?>
            <div class="tinysmall text-muted"><?php echo ($priceDiff > 0 ? '+' : '') . $currency . ' ' . number_format($priceDiff, $dec); ?></div>
        <?php endif; ?>
    </td>

    <td class="text-end">
        <?php echo number_format($qty, $dec, '.', ','); ?>
        <?php if ($unit): ?>
            <span class="text-muted tinysmall"><?php echo $unit; ?></span>
        <?php endif; ?>
    </td>

    <td>
        <?php if ($transUID > 0): ?>
            <a href="/purchases/view/<?php echo $transUID; ?>" class="text-primary fw-semibold small" target="_blank">
                <i class="bx bx-link-external me-1"></i>View Bill
            </a>
        <?php else: ?>
            <span class="text-muted">—</span>
        <?php endif; ?>
        <?php if ($source): ?>
            <div class="tinysmall text-muted"><?php echo $source; ?></div>
        <?php endif; ?>
    </td>

    <td class="text-muted small"><?php echo $remarks ?: '—'; ?></td>
</tr>
<?php endforeach; ?>
