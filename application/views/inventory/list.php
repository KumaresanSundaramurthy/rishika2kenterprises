<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
$currency  = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
$decimals  = (int)($JwtData->GenSettings->DecimalPoints ?? 2);
$showSno   = $JwtData->GenSettings->SerialNoDisplay == 1;
$sno       = (int)($SerialNumber ?? 0);

$moduleMap = [
    103 => ['label' => 'Invoice',         'color' => 'text-danger'],
    105 => ['label' => 'Purchase',        'color' => 'text-success'],
    106 => ['label' => 'Sales Return',    'color' => 'text-info'],
    107 => ['label' => 'Credit Note',     'color' => 'text-info'],
    108 => ['label' => 'Purchase Return', 'color' => 'text-warning'],
    118 => ['label' => 'Manual Adj.',     'color' => 'text-primary'],
];

if (!empty($DataLists)):
    foreach ($DataLists as $row):
        $sno++;
        $qty = (float)$row->AvailableQuantity;
        $threshold = (float)($row->LowStockAlertAt ?? 0);

        if ($qty <= 0) {
            $statusClass = 'trans-badge-Cancelled';
            $statusIcon  = 'bx-x-circle';
            $statusLabel = 'Out of Stock';
        } elseif ($qty <= $threshold) {
            $statusClass = 'trans-badge-Pending';
            $statusIcon  = 'bx-error-circle';
            $statusLabel = 'Low Stock';
        } else {
            $statusClass = 'trans-badge-Paid';
            $statusIcon  = 'bx-check-circle';
            $statusLabel = 'In Stock';
        }

        $qtyColor = $qty <= 0 ? 'color:#dc2626;' : ($qty <= $threshold ? 'color:#d97706;' : 'color:#16a34a;');
?>
<tr>

    <td class="<?php echo $showSno ? '' : 'd-none'; ?> table-serialno" style="width:44px;">
        <span class="text-muted" style="font-size:.78rem;"><?php echo $sno; ?></span>
    </td>

    <!-- Item -->
    <td>
        <div class="fw-semibold" style="font-size:.85rem;"><?php echo htmlspecialchars($row->ItemName); ?></div>
        <?php if (!empty($row->PartNumber)): ?>
        <div class="text-muted" style="font-size:.7rem;">Part# <?php echo htmlspecialchars($row->PartNumber); ?></div>
        <?php endif; ?>
        <div class="d-flex align-items-center flex-wrap gap-1 mt-1" style="font-size:.72rem;">
            <?php if ($row->ProductType === 'Service'): ?>
                <span class="badge bg-label-info" style="font-size:.65rem;">Service</span>
            <?php else: ?>
                <span class="badge bg-label-primary" style="font-size:.65rem;">Product</span>
            <?php endif; ?>
            <?php if (!empty($row->HSNSACCode)): ?>
                <span class="text-muted" style="font-size:.68rem;">HSN: <?php echo htmlspecialchars($row->HSNSACCode); ?></span>
            <?php endif; ?>
            <?php if (!empty($row->PartNumber)): ?>
                <span title="Barcode — <?php echo htmlspecialchars($row->PartNumber); ?>" style="cursor:default;line-height:1;">
                    <i class="bx bx-barcode text-primary" style="font-size:1.1rem;vertical-align:middle;"></i>
                </span>
                <span title="QR Code — <?php echo htmlspecialchars($row->PartNumber); ?>" style="cursor:default;line-height:1;">
                    <i class="bx bx-qr text-info" style="font-size:.95rem;vertical-align:middle;"></i>
                </span>
            <?php endif; ?>
            <?php if (!empty($row->Description)): ?>
                <?php $descPlain = strip_tags($row->Description);
                      $descTip   = htmlspecialchars(mb_strimwidth($descPlain, 0, 160, '…')); ?>
                <i class="bx bx-info-circle text-warning inv-desc-btn"
                   style="font-size:.95rem;cursor:pointer;vertical-align:middle;"
                   data-name="<?php echo htmlspecialchars($row->ItemName); ?>"
                   data-desc="<?php echo htmlspecialchars($row->Description); ?>"
                   data-bs-toggle="tooltip"
                   data-bs-placement="top"
                   data-bs-title="<?php echo $descTip; ?>"
                   title="<?php echo $descTip; ?>"></i>
            <?php endif; ?>
        </div>
    </td>

    <!-- Category -->
    <td>
        <?php if (!empty($row->CategoryName)): ?>
        <span class="badge text-bg-light border" style="font-size:.7rem;font-weight:500;">
            <?php echo htmlspecialchars($row->CategoryName); ?>
        </span>
        <?php else: ?>
        <span class="text-muted">—</span>
        <?php endif; ?>
    </td>

    <!-- Qty -->
    <td>
        <div class="fw-semibold" style="font-size:.9rem;<?php echo $qtyColor; ?>">
            <?php echo number_format($qty, 2); ?>
            <span style="font-size:.72rem;font-weight:400;color:#6c757d;"><?php echo htmlspecialchars($row->UnitName ?? ''); ?></span>
        </div>
        <?php if ($threshold > 0): ?>
        <div class="text-muted" style="font-size:.68rem;">Alert at <?php echo number_format($threshold, 0); ?></div>
        <?php endif; ?>
    </td>

    <!-- Status -->
    <td>
        <span class="trans-badge <?php echo $statusClass; ?>">
            <i class="bx <?php echo $statusIcon; ?>" style="font-size:.8rem;"></i>
            <?php echo $statusLabel; ?>
        </span>
    </td>

    <!-- Purchase Price -->
    <td>
        <div style="font-size:.85rem;"><?php echo $currency . ' ' . number_format((float)$row->PurchasePrice, $decimals); ?></div>
        <?php if ($row->TaxPercentage > 0): ?>
        <div class="text-muted" style="font-size:.7rem;"><?php echo $row->TaxPercentage; ?>% tax</div>
        <?php endif; ?>
    </td>

    <!-- Sale Price -->
    <td>
        <div class="trans-amount-main"><?php echo $currency . ' ' . number_format((float)$row->SellingPrice, $decimals); ?></div>
    </td>

    <!-- Last Updated -->
    <td>
        <?php
            $updatedOn  = $row->UpdatedOn ?? null;
            $secondsAgo = $updatedOn ? (time() - strtotime($updatedOn)) : null;
            $within24h  = $secondsAgo !== null && $secondsAgo < 86400;
            if ($within24h) {
                if ($secondsAgo < 60)       $agoText = 'just now';
                elseif ($secondsAgo < 3600) $agoText = (int)($secondsAgo / 60) . ' min' . ((int)($secondsAgo / 60) > 1 ? 's' : '') . ' ago';
                else                        $agoText = (int)($secondsAgo / 3600) . ' hr'  . ((int)($secondsAgo / 3600) > 1 ? 's' : '') . ' ago';
            }
        ?>
        <div style="font-size:.78rem;"><?php echo $updatedOn ? changeTimeZonefromDateTime($updatedOn, $JwtData->User->Timezone, 2) : '—'; ?></div>
        <?php if ($within24h): ?>
        <div style="font-size:.68rem;color:#0d6efd;font-weight:500;"><?php echo $agoText; ?></div>
        <?php endif; ?>
        <?php $updatedByName = trim($row->UpdatedByName ?? ''); ?>
        <?php if (!empty($updatedByName)): ?>
        <div class="text-muted" style="font-size:.7rem;">by <?php echo htmlspecialchars($updatedByName); ?></div>
        <?php endif; ?>
    </td>

    <!-- Actions -->
    <td style="width:130px;text-align:right;">
        <div class="d-flex align-items-center justify-content-end gap-1">
            <button class="btn btn-sm btn-success invStockInBtn px-2 py-1"
                    data-uid="<?php echo (int)$row->ProductUID; ?>"
                    data-name="<?php echo htmlspecialchars($row->ItemName); ?>"
                    data-unit="<?php echo htmlspecialchars($row->UnitName ?? ''); ?>"
                    data-purchase-price="<?php echo (float)$row->PurchasePrice; ?>"
                    title="Stock In"
                    style="font-size:.75rem;">
                <i class="bx bx-plus me-1"></i>In
            </button>
            <button class="btn btn-sm btn-outline-danger invStockOutBtn px-2 py-1"
                    data-uid="<?php echo (int)$row->ProductUID; ?>"
                    data-name="<?php echo htmlspecialchars($row->ItemName); ?>"
                    data-unit="<?php echo htmlspecialchars($row->UnitName ?? ''); ?>"
                    data-selling-price="<?php echo (float)$row->SellingPrice; ?>"
                    title="Stock Out"
                    style="font-size:.75rem;">
                <i class="bx bx-minus me-1"></i>Out
            </button>
            <button class="btn btn-icon btn-sm text-primary invTimelineBtn"
                    data-uid="<?php echo (int)$row->ProductUID; ?>"
                    data-name="<?php echo htmlspecialchars($row->ItemName); ?>"
                    title="View History">
                <i class="bx bx-history"></i>
            </button>
        </div>
    </td>

</tr>
<?php
    endforeach;
else:
?>
<tr>
    <td colspan="10">
        <div class="d-flex flex-column align-items-center py-5">
            <img src="/assets/img/elements/no-record-found.png" alt="No Records" class="img-fluid mb-3" style="max-height:150px;object-fit:contain;">
            <span class="text-muted" style="font-size:.9rem;">No inventory items found</span>
        </div>
    </td>
</tr>
<?php endif; ?>
