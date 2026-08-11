<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$JwtData      = $JwtData      ?? null;
$DataLists    = $DataLists    ?? [];
$SerialNumber = $SerialNumber ?? 0;

$fmt      = $JwtData->GenSettings->ListDateFormat ?? 'd M Y';
$dec      = (int)($JwtData->GenSettings->DecimalPoints ?? 2);
$currency = $JwtData->GenSettings->CurrenySymbol ?? '₹';

$dirLabel = ['First', 'Up', 'Down'];
$dirClass = ['text-secondary', 'text-success', 'text-danger'];
$dirIcon  = ['bx-minus', 'bx-up-arrow-alt', 'bx-down-arrow-alt'];

if (empty($DataLists)) : ?>
<tr>
    <td colspan="10" class="text-center py-5 text-muted">
        <i class="bx bx-history fs-3 d-block mb-2"></i>
        No price history records found.
    </td>
</tr>
<?php return; endif;

foreach ($DataLists as $i => $row):
    $dir       = (int)($row->Direction ?? 0);
    $isManual  = (int)($row->Source ?? 1) === 2;
    $hasDiff   = $row->PreviousPrice !== null && $row->PreviousPrice !== '';
    $diffPct   = $hasDiff ? round((float)($row->PriceDiffPct ?? 0), 2) : null;
    $diff      = $hasDiff ? (float)($row->PriceDiff ?? 0) : null;
    $entryDate = !empty($row->EntryDate) ? date($fmt, strtotime($row->EntryDate)) : '—';
?>
<tr>
    <td class="r2k-sl-col"><?php echo $SerialNumber + $i + 1; ?></td>
    <td>
        <div class="fw-semibold" style="font-size:.875rem;"><?php echo htmlspecialchars($row->ProductName ?? '—'); ?></div>
        <?php if (!empty($row->SKU)): ?>
            <div class="text-muted" style="font-size:.75rem;">SKU: <?php echo htmlspecialchars($row->SKU); ?></div>
        <?php endif; ?>
        <?php if (!empty($row->CategoryName)): ?>
            <div class="text-muted" style="font-size:.72rem;"><?php echo htmlspecialchars($row->CategoryName); ?></div>
        <?php endif; ?>
    </td>
    <td><?php echo htmlspecialchars($row->VendorName ?? '—'); ?></td>
    <td class="r2k-col-date"><?php echo $entryDate; ?></td>
    <td class="text-end fw-semibold">
        <?php echo $currency . ' ' . number_format((float)($row->PurchasePrice ?? 0), $dec, '.', ','); ?>
        <?php if (!empty($row->Unit)): ?>
            <span class="text-muted fw-normal" style="font-size:.75rem;">/ <?php echo htmlspecialchars($row->Unit); ?></span>
        <?php endif; ?>
    </td>
    <td class="text-end text-muted" style="font-size:.85rem;">
        <?php echo $currency . ' ' . number_format((float)($row->SellingPrice ?? 0), $dec, '.', ','); ?>
    </td>
    <td class="text-end" style="font-size:.85rem;">
        <?php echo number_format((float)($row->Qty ?? 0), $dec, '.', ','); ?>
    </td>
    <td class="text-center">
        <?php if ($hasDiff): ?>
            <span class="badge rounded-pill <?php echo $dir === 1 ? 'bg-label-success' : 'bg-label-danger'; ?>" style="font-size:.78rem;">
                <i class="bx <?php echo $dirIcon[$dir]; ?> me-1"></i>
                <?php echo ($diff >= 0 ? '+' : '') . number_format($diff, $dec, '.', ','); ?>
                <?php if ($diffPct !== null): ?>
                    <span class="opacity-75">(<?php echo ($diffPct >= 0 ? '+' : '') . $diffPct; ?>%)</span>
                <?php endif; ?>
            </span>
        <?php else: ?>
            <span class="badge rounded-pill bg-label-secondary" style="font-size:.78rem;">First Entry</span>
        <?php endif; ?>
    </td>
    <td class="text-center">
        <?php if ($isManual): ?>
            <span class="badge bg-label-primary" style="font-size:.75rem;">Manual</span>
        <?php else: ?>
            <span class="badge bg-label-secondary" style="font-size:.75rem;">Auto</span>
        <?php endif; ?>
    </td>
    <td class="text-center">
        <?php if ($isManual): ?>
            <button type="button" class="btn btn-sm btn-icon btn-text-secondary ppl-edit-btn"
                    data-log-uid="<?php echo (int)$row->PriceLogUID; ?>"
                    data-bs-toggle="tooltip" title="Edit">
                <i class="bx bx-edit-alt"></i>
            </button>
        <?php else: ?>
            <?php if (!empty($row->TransactionUID)): ?>
                <a href="/purchases/edit/<?php echo (int)$row->TransactionUID; ?>"
                   class="btn btn-sm btn-icon btn-text-secondary"
                   data-bs-toggle="tooltip" title="View Purchase">
                    <i class="bx bx-link-external"></i>
                </a>
            <?php endif; ?>
        <?php endif; ?>
        <?php if (!empty($row->Remarks)): ?>
            <button type="button" class="btn btn-sm btn-icon btn-text-secondary ppl-remark-btn"
                    data-remark="<?php echo htmlspecialchars($row->Remarks, ENT_QUOTES); ?>"
                    data-bs-toggle="tooltip" title="Has notes">
                <i class="bx bx-note"></i>
            </button>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
