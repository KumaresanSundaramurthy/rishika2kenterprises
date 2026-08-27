<?php defined('BASEPATH') OR exit('No direct script access allowed');

$JwtData      = $JwtData      ?? null;
$DataLists    = $DataLists    ?? [];
$SerialNumber = $SerialNumber ?? 0;

$fmt      = $JwtData->GenSettings->ListDateFormat ?? 'd M Y';
$currency = $JwtData->GenSettings->CurrenySymbol ?? '₹';

if (empty($DataLists)) : ?>
<tr>
    <td colspan="10" class="py-5 text-muted">
        <div class="text-center">
            <i class="bx bx-purchase-tag fs-3 mb-2 d-block"></i>
            No purchase price records found.
        </div>
    </td>
</tr>
<?php return; endif;

foreach ($DataLists as $i => $row):
    $purchasePrice = (float)($row->PurchasePrice ?? 0);
    $taxPct        = (float)($row->TaxPct        ?? 0);
    $discountPct   = (float)($row->DiscountPct   ?? 0);
    $totalAmt      = (float)($row->TotalAmt      ?? 0);
    $qty           = (float)($row->Qty           ?? 0);
    $isIncl        = (int)($row->IsPurchasePriceIncl ?? 0);
    $lastDate      = !empty($row->LastPurchaseDate) ? date($fmt, strtotime($row->LastPurchaseDate)) : '—';
    $updatedOn     = !empty($row->UpdatedOn)       ? date($fmt, strtotime($row->UpdatedOn))       : '—';
    $updatedByName = trim((string)($row->UpdatedByName ?? ''));

    // PurchasePrice in DB is always the exclusive unit price (even for Incl items).
    // Price-with-tax per unit = excl + (total line tax ÷ qty).
    // CGST/SGST apply for intra-state; IGST applies for inter-state — sum covers both.
    $totalLineTax = (float)($row->CGSTAmt ?? 0) + (float)($row->SGSTAmt ?? 0) + (float)($row->IGSTAmt ?? 0);
    if ($qty > 0) {
        $priceWithTax = $purchasePrice + ($totalLineTax / $qty);
    } elseif ($taxPct > 0) {
        $priceWithTax = $purchasePrice * (1 + $taxPct / 100);
    } else {
        $priceWithTax = $purchasePrice;
    }
?>
<tr>
    <td class="r2k-sl-col"><?php echo $SerialNumber + $i + 1; ?></td>

    <td>
        <a href="/purchasepricelist/view/<?php echo (int)$row->PriceListUID; ?>" class="fw-semibold small">
            <?php echo htmlspecialchars($row->ItemName ?? '—'); ?>
        </a>
        <?php if (!empty($row->SKU)): ?>
            <div class="text-muted tinysmall">SKU: <?php echo htmlspecialchars($row->SKU); ?></div>
        <?php endif; ?>
        <?php if (!empty($row->HSNCode)): ?>
            <div class="text-muted tinysmall">HSN: <?php echo htmlspecialchars($row->HSNCode); ?></div>
        <?php endif; ?>
    </td>

    <td class="small"><?php echo htmlspecialchars($row->VendorName ?? '—'); ?></td>

    <td class="text-end fw-semibold">
        <?php echo $currency . ' ' . smartDecimal($priceWithTax); ?>
        <span class="badge <?php echo $isIncl ? 'bg-label-info' : 'bg-label-secondary'; ?> ms-1 tinysmall">
            <?php echo $isIncl ? 'Incl' : 'Excl'; ?>
        </span>
        <?php if ($taxPct > 0): ?>
            <div class="text-muted fw-normal tinysmall"><?php echo $currency . ' ' . smartDecimal($purchasePrice); ?> excl + <?php echo number_format($taxPct, 2); ?>% tax</div>
        <?php elseif (!empty($row->Unit)): ?>
            <div class="text-muted fw-normal tinysmall">per <?php echo htmlspecialchars($row->Unit); ?></div>
        <?php endif; ?>
    </td>

    <td class="text-center small">
        <?php echo $taxPct > 0 ? number_format($taxPct, 2) . '%' : '—'; ?>
    </td>

    <td class="text-end small">
        <?php echo smartDecimal($qty); ?>
        <?php if (!empty($row->Unit)): ?>
            <span class="text-muted tinysmall"><?php echo htmlspecialchars($row->Unit); ?></span>
        <?php endif; ?>
    </td>

    <td class="text-end small">
        <?php if ($discountPct > 0): ?>
            <?php echo number_format($discountPct, 2); ?>%
        <?php else: ?>
            <span class="text-muted">—</span>
        <?php endif; ?>
    </td>

    <td class="text-end fw-semibold">
        <?php echo $currency . ' ' . smartDecimal($totalAmt); ?>
    </td>

    <td>
        <?php if (!empty($row->LastTransUID)): ?>
            <a href="/purchases/view/<?php echo (int)$row->LastTransUID; ?>" class="text-primary fw-semibold small" target="_blank">
                <?php echo htmlspecialchars($row->LastUniqueNumber ?? '—'); ?>
            </a>
        <?php else: ?>
            <span class="text-muted">—</span>
        <?php endif; ?>
        <div class="text-muted tinysmall"><?php echo $lastDate; ?></div>
    </td>

    <td class="r2k-col-date">
        <?php echo $updatedOn; ?>
        <?php if ($updatedByName !== ''): ?>
            <div class="text-muted r2k-col-date-by">by <?php echo htmlspecialchars($updatedByName); ?></div>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
