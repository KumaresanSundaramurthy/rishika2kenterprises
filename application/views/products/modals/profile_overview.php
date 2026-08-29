<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Product Profile Modal — Overview Tab
 *
 * @var object      $Prod          From getProductProfile()
 * @var array       $TopCustomers  From getProductTopCustomers()
 * @var object      $JwtData
 * @var string      $Cur
 * @var int         $Dec
 * @var string      $DateFormat
 * @var string      $DateTimeFormat
 * @var string      $Timezone
 * @var int         $ProductUID
 */

$fmt = function (float $v) use ($Cur, $Dec): string {
    return $Cur . ' ' . number_format($v, $Dec, '.', '');
};

$name    = htmlspecialchars($Prod->ItemName ?? '');
$words   = preg_split('/\s+/', trim($Prod->ItemName ?? ''));
$initials = strtoupper(substr($words[0] ?? '', 0, 1)) . strtoupper(substr($words[1] ?? '', 0, 1));

$stockQty      = (float) ($Prod->AvailableQty ?? 0);
$lowAlertAt    = (float) ($Prod->LowStockAlertAt ?? 0);
$isLowStock    = $lowAlertAt > 0 && $stockQty <= $lowAlertAt;

$selTaxLabel = '';
if (!empty($Prod->TaxPercentage)) {
    $selTaxType  = ($Prod->SellingTaxType === 'With Tax') ? 'incl.' : 'excl.';
    $selTaxLabel = $Prod->TaxPercentage . '% ' . $selTaxType . ' tax';
}
$purTaxLabel = '';
if (!empty($Prod->TaxPercentage) && !empty($Prod->PurchaseTaxType)) {
    $purTaxType  = ($Prod->PurchaseTaxType === 'With Tax') ? 'incl.' : 'excl.';
    $purTaxLabel = $Prod->TaxPercentage . '% ' . $purTaxType . ' tax';
}

$descText = !empty($Prod->Description)
    ? htmlspecialchars(mb_strimwidth(strip_tags($Prod->Description), 0, 200, '…'))
    : '';
?>
<script>
(function () {
    document.getElementById('ppAvatarInitials').textContent = <?php echo json_encode($initials ?: '?'); ?>;
    document.getElementById('ppModalTitle').textContent     = <?php echo json_encode($name); ?>;
    var sub = [];
    <?php if (!empty($Prod->PartNumber)): ?>
    sub.push('Part# <?php echo addslashes(htmlspecialchars($Prod->PartNumber)); ?>');
    <?php endif; ?>
    <?php if (!empty($Prod->CategoryName)): ?>
    sub.push('<?php echo addslashes(htmlspecialchars($Prod->CategoryName)); ?>');
    <?php endif; ?>
    document.getElementById('ppModalSubtitle').textContent = sub.join(' · ');
    document.getElementById('ppBtnEdit').dataset.uid = <?php echo (int) $ProductUID; ?>;
    <?php if (!empty($Prod->ImageUrl)): ?>
    (function () {
        var wrap = document.getElementById('ppAvatarWrap');
        var img  = document.createElement('img');
        img.src       = <?php echo json_encode($Prod->ImageUrl); ?>;
        img.className = 'pp-avatar-img';
        img.alt       = <?php echo json_encode($name); ?>;
        wrap.innerHTML = '';
        wrap.appendChild(img);
    })();
    <?php endif; ?>
})();
</script>

<div class="p-3 p-md-4">

    <?php if ($isLowStock): ?>
    <div class="alert alert-warning d-flex align-items-center gap-2 py-2 mb-3">
        <i class="bx bx-error fs-5"></i>
        <span>Low stock alert — only <strong><?php echo smartDecimal($stockQty); ?> <?php echo htmlspecialchars($Prod->UnitShortName ?? ''); ?></strong> remaining
        <?php if ($lowAlertAt > 0): ?>(threshold: <?php echo smartDecimal($lowAlertAt); ?>)<?php endif; ?></span>
    </div>
    <?php endif; ?>

    <?php if (($Prod->NotForSale ?? '') === 'Yes'): ?>
    <div class="alert alert-secondary d-flex align-items-center gap-2 py-2 mb-3">
        <i class="bx bx-block fs-5"></i>
        <span>This item is marked <strong>Not For Sale</strong>.</span>
    </div>
    <?php endif; ?>

    <!-- ── Quick Stats ──────────────────────────────────────────────────── -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="pp-stat-card pp-stat-green">
                <div class="pp-stat-icon"><i class="bx bx-up-arrow-alt"></i></div>
                <div class="pp-stat-val"><?php echo smartDecimal((float)$Prod->TotalUnitsSold); ?></div>
                <div class="pp-stat-label">Units Sold</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="pp-stat-card pp-stat-blue">
                <div class="pp-stat-icon"><i class="bx bx-rupee"></i></div>
                <div class="pp-stat-val"><?php echo $fmt((float)$Prod->TotalRevenue); ?></div>
                <div class="pp-stat-label">Revenue</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="pp-stat-card pp-stat-purple">
                <div class="pp-stat-icon"><i class="bx bx-down-arrow-alt"></i></div>
                <div class="pp-stat-val"><?php echo smartDecimal((float)$Prod->TotalUnitsPurchased); ?></div>
                <div class="pp-stat-label">Units Purchased</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="pp-stat-card <?php echo $isLowStock ? 'pp-stat-red' : 'pp-stat-teal'; ?>">
                <div class="pp-stat-icon"><i class="bx bx-cube"></i></div>
                <div class="pp-stat-val"><?php echo smartDecimal($stockQty); ?> <?php echo htmlspecialchars($Prod->UnitShortName ?? ''); ?></div>
                <div class="pp-stat-label">In Stock</div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        <!-- ── LEFT: Product Details ──────────────────────────────────── -->
        <div class="col-md-7">
            <div class="pp-detail-card">
                <div class="pp-detail-title">Product Details</div>

                <div class="pp-detail-grid">
                    <div class="pp-detail-row">
                        <span class="pp-detail-key">Type</span>
                        <span class="pp-detail-val">
                            <?php if ($Prod->IsComposite): ?>
                                <span class="badge bg-label-warning">Combo / Group</span>
                            <?php elseif ($Prod->ProductType === 'Service'): ?>
                                <span class="badge bg-label-info">Service</span>
                            <?php else: ?>
                                <span class="badge bg-label-primary">Product</span>
                            <?php endif; ?>
                            <?php if ($Prod->IsRentable): ?>
                                <span class="badge bg-label-secondary ms-1">Rentable</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php if (!empty($Prod->CategoryName)): ?>
                    <div class="pp-detail-row">
                        <span class="pp-detail-key">Category</span>
                        <span class="pp-detail-val"><?php echo htmlspecialchars($Prod->CategoryName); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($Prod->UnitShortName)): ?>
                    <div class="pp-detail-row">
                        <span class="pp-detail-key">Unit</span>
                        <span class="pp-detail-val"><?php echo htmlspecialchars($Prod->UnitShortName); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($Prod->HSNSACCode)): ?>
                    <div class="pp-detail-row">
                        <span class="pp-detail-key">HSN / SAC</span>
                        <span class="pp-detail-val"><code><?php echo htmlspecialchars($Prod->HSNSACCode); ?></code></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($Prod->PartNumber)): ?>
                    <div class="pp-detail-row">
                        <span class="pp-detail-key">Part No.</span>
                        <span class="pp-detail-val"><code><?php echo htmlspecialchars($Prod->PartNumber); ?></code></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($Prod->SKU)): ?>
                    <div class="pp-detail-row">
                        <span class="pp-detail-key">SKU</span>
                        <span class="pp-detail-val"><code><?php echo htmlspecialchars($Prod->SKU); ?></code></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pricing -->
            <div class="pp-detail-card mt-3">
                <div class="pp-detail-title">Pricing</div>
                <div class="pp-detail-grid">
                    <div class="pp-detail-row">
                        <span class="pp-detail-key">Selling Price</span>
                        <span class="pp-detail-val fw-semibold">
                            <?php echo $fmt((float)($Prod->SellingPrice ?? 0)); ?>
                            <?php if ($selTaxLabel): ?>
                                <span class="pp-tax-label"><?php echo $selTaxLabel; ?></span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php if (!empty($Prod->MRP) && (float)$Prod->MRP > 0): ?>
                    <div class="pp-detail-row">
                        <span class="pp-detail-key">MRP</span>
                        <span class="pp-detail-val"><?php echo $fmt((float)$Prod->MRP); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="pp-detail-row">
                        <span class="pp-detail-key">Purchase Price</span>
                        <span class="pp-detail-val">
                            <?php echo $fmt((float)($Prod->PurchasePrice ?? 0)); ?>
                            <?php if ($purTaxLabel): ?>
                                <span class="pp-tax-label"><?php echo $purTaxLabel; ?></span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php
                    $sellingPrice  = (float)($Prod->SellingPrice ?? 0);
                    $purchasePrice = (float)($Prod->PurchasePrice ?? 0);
                    if ($sellingPrice > 0 && $purchasePrice > 0):
                        $margin    = $sellingPrice - $purchasePrice;
                        $marginPct = round(($margin / $purchasePrice) * 100, 1);
                    ?>
                    <div class="pp-detail-row">
                        <span class="pp-detail-key">Margin</span>
                        <span class="pp-detail-val <?php echo $margin >= 0 ? 'text-success' : 'text-danger'; ?> fw-semibold">
                            <?php echo $fmt($margin); ?> (<?php echo $marginPct; ?>%)
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($descText): ?>
            <div class="pp-detail-card mt-3">
                <div class="pp-detail-title">Description</div>
                <p class="mb-0 pp-desc-text"><?php echo $descText; ?></p>
            </div>
            <?php endif; ?>
        </div>

        <!-- ── RIGHT: Top Customers ────────────────────────────────────── -->
        <div class="col-md-5">

            <?php if ($Prod->SalesTxCount > 0): ?>
            <div class="pp-detail-card">
                <div class="pp-detail-title">Sales Activity</div>
                <div class="pp-detail-grid">
                    <div class="pp-detail-row">
                        <span class="pp-detail-key">Sales Transactions</span>
                        <span class="pp-detail-val fw-semibold"><?php echo number_format($Prod->SalesTxCount); ?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($TopCustomers)): ?>
            <div class="pp-detail-card <?php echo $Prod->SalesTxCount > 0 ? 'mt-3' : ''; ?>">
                <div class="pp-detail-title">Top Customers</div>
                <div class="pp-top-customers">
                <?php foreach ($TopCustomers as $i => $tc): ?>
                    <div class="pp-tc-row">
                        <div class="pp-tc-rank"><?php echo $i + 1; ?></div>
                        <div class="pp-tc-name"><?php echo htmlspecialchars($tc['CustomerName'] ?? '—'); ?></div>
                        <div class="pp-tc-qty">
                            <?php echo smartDecimal((float)$tc['TotalQty']); ?> <?php echo htmlspecialchars($Prod->UnitShortName ?? ''); ?>
                            <span class="pp-tc-txcount"><?php echo (int)$tc['TxCount']; ?> orders</span>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Created / Updated info -->
            <div class="pp-detail-card mt-3">
                <div class="pp-detail-title">Record Info</div>
                <div class="pp-detail-grid">
                    <?php if (!empty($Prod->CreatedOn)): ?>
                    <div class="pp-detail-row">
                        <span class="pp-detail-key">Created</span>
                        <span class="pp-detail-val"><?php
                            $dt = new DateTime($Prod->CreatedOn, new DateTimeZone('UTC'));
                            $dt->setTimezone(new DateTimeZone($Timezone));
                            echo $dt->format($DateTimeFormat);
                        ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($Prod->UpdatedOn) && $Prod->UpdatedOn !== $Prod->CreatedOn): ?>
                    <div class="pp-detail-row">
                        <span class="pp-detail-key">Last Updated</span>
                        <span class="pp-detail-val"><?php
                            $dt = new DateTime($Prod->UpdatedOn, new DateTimeZone('UTC'));
                            $dt->setTimezone(new DateTimeZone($Timezone));
                            echo $dt->format($DateTimeFormat);
                        ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </div><!-- /row -->
</div>
