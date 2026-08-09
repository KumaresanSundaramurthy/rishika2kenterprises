<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Vendor Profile Modal — Overview Tab
 *
 * @var object      $Vend
 * @var object|null $BillingAddr
 * @var object|null $ShippingAddr
 * @var float       $TotalPurchased
 * @var float       $TotalPaid
 * @var float       $TotalReturned
 * @var int|null    $DaysSinceLastTx
 * @var float       $ClosingBalance
 * @var string      $ClosingBalType    'Debit'|'Credit'
 * @var array       $MonthlyPurchase
 * @var object|null $GroupMembership
 * @var object|null $OpeningBal
 * @var object      $JwtData
 * @var string      $Cur
 * @var int         $Dec
 * @var string      $DateFormat
 */

$fmt      = function (float $v) use ($Cur, $Dec): string { return $Cur . ' ' . number_format($v, $Dec); };
$tags     = !empty($Vend->Tags) ? array_filter(explode(',', $Vend->Tags)) : [];
$name     = htmlspecialchars($Vend->Name ?? '');
$words    = preg_split('/\s+/', trim($Vend->Name ?? ''));
$initials = strtoupper(substr($words[0] ?? '', 0, 1)) . strtoupper(substr($words[1] ?? '', 0, 1));
?>
<script>
(function () {
    document.getElementById('vpAvatarInitials').textContent = <?php echo json_encode($initials ?: '?'); ?>;
    document.getElementById('vpModalTitle').textContent     = <?php echo json_encode($name); ?>;
    <?php
    $_mobile   = !empty($Vend->MobileNumber)
        ? ((!empty($Vend->CountryCode) ? $Vend->CountryCode . ' ' : '') . $Vend->MobileNumber)
        : '';
    $_subtitle = $_mobile . (!empty($Vend->EmailAddress) ? ($_mobile ? ' · ' : '') . $Vend->EmailAddress : '');
    ?>
    document.getElementById('vpModalSubtitle').textContent = <?php echo json_encode($_subtitle); ?>;
    document.getElementById('vpBtnEdit').dataset.uid       = <?php echo (int)$Vend->VendorUID; ?>;
})();
</script>

<div class="p-3 p-md-4">
    <div class="row g-4">

        <!-- ── LEFT: Vendor Details ─────────────────────────────────────── -->
        <div class="col-md-7">

            <!-- Last Activity -->
            <div class="d-flex align-items-center justify-content-end mb-3">
                <span class="text-muted" style="font-size:.74rem;">
                    <i class="bx bx-time-five me-1"></i>
                    <?php if ($DaysSinceLastTx === null): ?>
                        No transactions yet
                    <?php elseif ($DaysSinceLastTx === 0): ?>
                        Active today
                    <?php elseif ($DaysSinceLastTx === 1): ?>
                        Last activity yesterday
                    <?php else: ?>
                        Last activity <strong><?php echo (int)$DaysSinceLastTx; ?>d</strong> ago
                    <?php endif; ?>
                </span>
            </div>

            <!-- Contact Info -->
            <div class="card border shadow-none mb-3">
                <div class="card-header py-2 px-3 bg-light d-flex align-items-center gap-2">
                    <i class="bx bx-store text-warning"></i>
                    <span class="fw-semibold" style="font-size:.82rem;">CONTACT INFORMATION</span>
                </div>
                <div class="card-body p-3">
                    <div class="row g-2" style="font-size:.83rem;">

                        <?php if (!empty($Vend->MobileNumber)): ?>
                        <div class="col-sm-6">
                            <div class="text-muted mb-1" style="font-size:.72rem;">Mobile</div>
                            <div class="fw-semibold">
                                <?php if (!empty($Vend->CountryCode)): ?>
                                    <span class="text-muted"><?php echo htmlspecialchars($Vend->CountryCode); ?></span>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($Vend->MobileNumber); ?>
                                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $Vend->CountryCode . $Vend->MobileNumber); ?>?text=Hi"
                                   target="_blank" class="ms-1 text-success" title="WhatsApp">
                                    <i class="bx bxl-whatsapp"></i>
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($Vend->EmailAddress)): ?>
                        <div class="col-sm-6">
                            <div class="text-muted mb-1" style="font-size:.72rem;">Email</div>
                            <div class="fw-semibold text-truncate"><?php echo htmlspecialchars($Vend->EmailAddress); ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($Vend->CompanyName)): ?>
                        <div class="col-sm-6">
                            <div class="text-muted mb-1" style="font-size:.72rem;">Company</div>
                            <div><?php echo htmlspecialchars($Vend->CompanyName); ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($Vend->Area)): ?>
                        <div class="col-sm-6">
                            <div class="text-muted mb-1" style="font-size:.72rem;">Area</div>
                            <div><?php echo htmlspecialchars($Vend->Area); ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($Vend->ContactPerson)): ?>
                        <div class="col-sm-6">
                            <div class="text-muted mb-1" style="font-size:.72rem;">Contact Person</div>
                            <div><?php echo htmlspecialchars($Vend->ContactPerson); ?></div>
                        </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>

            <!-- GST & Financial Settings -->
            <div class="card border shadow-none mb-3">
                <div class="card-header py-2 px-3 bg-light d-flex align-items-center gap-2">
                    <i class="bx bx-buildings text-warning"></i>
                    <span class="fw-semibold" style="font-size:.82rem;">GST & FINANCIAL</span>
                </div>
                <div class="card-body p-3">
                    <div class="row g-2" style="font-size:.83rem;">

                        <?php if (!empty($Vend->GSTIN)): ?>
                        <div class="col-sm-6">
                            <div class="text-muted mb-1" style="font-size:.72rem;">GSTIN</div>
                            <div class="fw-semibold" style="font-family:monospace;"><?php echo htmlspecialchars($Vend->GSTIN); ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($Vend->PANNumber)): ?>
                        <div class="col-sm-6">
                            <div class="text-muted mb-1" style="font-size:.72rem;">PAN</div>
                            <div style="font-family:monospace;"><?php echo htmlspecialchars($Vend->PANNumber); ?></div>
                        </div>
                        <?php endif; ?>

                        <?php
                        $obVal  = (float) ($OpeningBal?->OpeningBalance ?? 0);
                        $obType = $OpeningBal?->OpeningBalType ?? 'Credit';
                        if ($obVal > 0):
                        ?>
                        <div class="col-sm-6">
                            <div class="text-muted mb-1" style="font-size:.72rem;">Opening Balance</div>
                            <div class="<?php echo $obType === 'Credit' ? 'text-danger' : 'text-success'; ?>">
                                <?php echo $fmt($obVal); ?>
                                <span class="badge bg-label-secondary ms-1" style="font-size:.65rem;"><?php echo $obType === 'Credit' ? 'To Pay' : 'Advance'; ?></span>
                            </div>
                        </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>

            <!-- Address -->
            <?php if ($BillingAddr || $ShippingAddr): ?>
            <div class="card border shadow-none mb-3">
                <div class="card-header py-2 px-3 bg-light d-flex align-items-center gap-2">
                    <i class="bx bx-map text-danger"></i>
                    <span class="fw-semibold" style="font-size:.82rem;">ADDRESS</span>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3" style="font-size:.82rem;">
                        <?php if ($BillingAddr): ?>
                        <div class="col-sm-6">
                            <div class="text-muted mb-1" style="font-size:.72rem;">Billing Address</div>
                            <div>
                                <?php
                                $bParts = array_filter([
                                    $BillingAddr->Line1     ?? null,
                                    $BillingAddr->Line2     ?? null,
                                    $BillingAddr->CityText  ?? null,
                                    $BillingAddr->StateText ?? null,
                                    $BillingAddr->Pincode   ?? null,
                                ]);
                                echo nl2br(htmlspecialchars(implode(', ', $bParts)));
                                ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if ($ShippingAddr): ?>
                        <div class="col-sm-6">
                            <div class="text-muted mb-1" style="font-size:.72rem;">Shipping Address</div>
                            <div>
                                <?php
                                $sParts = array_filter([
                                    $ShippingAddr->Line1     ?? null,
                                    $ShippingAddr->Line2     ?? null,
                                    $ShippingAddr->CityText  ?? null,
                                    $ShippingAddr->StateText ?? null,
                                    $ShippingAddr->Pincode   ?? null,
                                ]);
                                echo nl2br(htmlspecialchars(implode(', ', $sParts)));
                                ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Tags & Type & Group -->
            <?php if (!empty($tags) || !empty($Vend->VendorTypeName) || $GroupMembership): ?>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <?php if (!empty($Vend->VendorTypeName)): ?>
                    <span class="badge bg-label-warning"><i class="bx bx-store me-1"></i><?php echo htmlspecialchars($Vend->VendorTypeName); ?></span>
                <?php endif; ?>
                <?php if ($GroupMembership && !empty($GroupMembership->GroupName)): ?>
                    <span class="badge bg-label-info"><i class="bx bxs-group me-1"></i><?php echo htmlspecialchars($GroupMembership->GroupName); ?></span>
                <?php endif; ?>
                <?php foreach ($tags as $tag): ?>
                    <span class="badge bg-label-secondary"><?php echo htmlspecialchars(trim($tag)); ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($Vend->Notes)): ?>
            <div class="mt-3 p-3 rounded" style="background:#fffbe6;border:1px solid #ffe58f;font-size:.82rem;">
                <i class="bx bx-info-circle text-warning me-1"></i>
                <?php echo nl2br(htmlspecialchars($Vend->Notes)); ?>
            </div>
            <?php endif; ?>

        </div>

        <!-- ── RIGHT: Financial Summary + Chart ────────────────────────── -->
        <div class="col-md-5">

            <!-- KPI Cards (2×2 grid) -->
            <div class="row g-2 mb-3">
                <?php
                $balClass = $ClosingBalance == 0 ? 'secondary' : ($ClosingBalType === 'Credit' ? 'danger' : 'success');
                $balLabel = $ClosingBalance == 0 ? 'No Balance' : ($ClosingBalType === 'Credit' ? 'To Pay' : 'Advance Paid');
                $cards = [
                    ['label' => 'Outstanding',      'value' => $fmt($ClosingBalance),   'icon' => 'bx-wallet',         'color' => $balClass,  'sub' => $balLabel],
                    ['label' => 'Total Purchased',  'value' => $fmt($TotalPurchased),   'icon' => 'bx-package',        'color' => 'primary',  'sub' => 'Lifetime'],
                    ['label' => 'Total Paid',       'value' => $fmt($TotalPaid),        'icon' => 'bx-up-arrow-alt',   'color' => 'success',  'sub' => 'Payments out'],
                    ['label' => 'Total Returns',    'value' => $fmt($TotalReturned),    'icon' => 'bx-undo',           'color' => 'warning',  'sub' => 'Purchase returns'],
                ];
                foreach ($cards as $card):
                ?>
                <div class="col-6">
                    <div class="card border shadow-none h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <div class="avatar avatar-xs">
                                    <span class="avatar-initial rounded bg-label-<?php echo $card['color']; ?>">
                                        <i class="bx <?php echo $card['icon']; ?>" style="font-size:.9rem;"></i>
                                    </span>
                                </div>
                                <span class="text-muted" style="font-size:.72rem;"><?php echo $card['label']; ?></span>
                            </div>
                            <div class="fw-bold text-<?php echo $card['color']; ?>" style="font-size:.92rem;"><?php echo $card['value']; ?></div>
                            <div class="text-muted" style="font-size:.7rem;"><?php echo $card['sub']; ?></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- 6-Month Purchase Chart -->
            <div class="card border shadow-none">
                <div class="card-header py-2 px-3 bg-light d-flex align-items-center gap-2">
                    <i class="bx bx-bar-chart-alt-2 text-warning"></i>
                    <span class="fw-semibold" style="font-size:.82rem;">PURCHASES — LAST 6 MONTHS</span>
                </div>
                <div class="card-body p-3">
                    <?php if (empty($MonthlyPurchase)): ?>
                    <div class="d-flex flex-column align-items-center justify-content-center py-4 text-muted" style="font-size:.82rem;">
                        <i class="bx bx-bar-chart-alt fs-3 mb-1"></i>
                        No purchase data in this period
                    </div>
                    <?php else:
                        $maxVal = max(array_column($MonthlyPurchase, 'Total'));
                        $maxVal = $maxVal > 0 ? $maxVal : 1;
                    ?>
                    <div class="d-flex align-items-flex-end gap-1 justify-content-between" style="height:100px;">
                        <?php foreach ($MonthlyPurchase as $row):
                            $pct      = min(100, round(($row['Total'] / $maxVal) * 100));
                            $monthLbl = substr($row['MonthLabel'], 0, 3);
                        ?>
                        <div class="d-flex flex-column align-items-center gap-1 flex-grow-1">
                            <div class="text-muted" style="font-size:.65rem;" title="<?php echo $Cur . ' ' . number_format($row['Total'], $Dec); ?>">
                                <?php echo $Cur . number_format($row['Total'] / 1000, 1); ?>k
                            </div>
                            <div class="w-100 rounded-top" style="height:<?php echo max(4, $pct); ?>px;background:#f59e0b;opacity:.8;min-width:20px;"></div>
                            <div class="text-muted" style="font-size:.68rem;"><?php echo htmlspecialchars($monthLbl); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- /.col-md-5 -->

    </div><!-- /.row -->
</div>
