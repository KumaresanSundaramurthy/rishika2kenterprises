<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Customer Profile Modal — Overview Tab
 *
 * Variables provided by Customers::getCustomerProfileTab():
 * @var object      $Cust
 * @var object|null $BillingAddr
 * @var object|null $ShippingAddr
 * @var float       $TotalInvoiced
 * @var float       $TotalReceived
 * @var float       $TotalReturned
 * @var float       $ClosingBalance
 * @var string      $ClosingBalType   'Debit'|'Credit'
 * @var array       $MonthlySales
 * @var object|null $GroupMembership
 * @var object|null $OpeningBal
 * @var array       $HealthData       from getCustomerHealthData()
 * @var array       $Ageing           from getCustomerAgeing()
 * @var array       $Profitability    from getCustomerProfitability()
 * @var object      $JwtData
 * @var string      $Cur
 * @var int         $Dec
 * @var string      $DateFormat
 */

$fmt     = function (float $v) use ($Cur): string {
    return $Cur . ' ' . smartDecimal($v);
};
$tags    = !empty($Cust->Tags) ? array_filter(explode(',', $Cust->Tags)) : [];
$name    = htmlspecialchars($Cust->Name ?? '');
$words   = preg_split('/\s+/', trim($Cust->Name ?? ''));
$initials = strtoupper(substr($words[0] ?? '', 0, 1)) . strtoupper(substr($words[1] ?? '', 0, 1));

// Update modal header via JS (avatar initials + subtitle)
?>
<script>
    (function () {
        document.getElementById('cpAvatarInitials').textContent = <?php echo json_encode($initials ?: '?'); ?>;
        document.getElementById('cpModalTitle').textContent     = <?php echo json_encode($name); ?>;
        <?php
        $_mobile   = !empty($Cust->MobileNumber)
            ? ((!empty($Cust->CountryCode) ? $Cust->CountryCode . ' ' : '') . $Cust->MobileNumber)
            : '';
        $_subtitle = $_mobile . (!empty($Cust->EmailAddress) ? ($_mobile ? ' · ' : '') . $Cust->EmailAddress : '');
        ?>
        document.getElementById('cpModalSubtitle').textContent  = <?php echo json_encode($_subtitle); ?>;
        document.getElementById('cpBtnEdit').dataset.uid        = <?php echo (int)$Cust->CustomerUID; ?>;
    })();
</script>

<div class="p-3 p-md-4">
    <div class="row g-4">

        <!-- ── LEFT: Customer Details ──────────────────────────────────── -->
        <div class="col-md-7">

            <!-- Health Score + Last Activity -->
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-<?php echo htmlspecialchars($HealthData['HealthColor']); ?> px-3 py-2" style="font-size:.8rem;">
                        <i class="bx bx-heart-circle me-1"></i><?php echo htmlspecialchars($HealthData['HealthScore']); ?>
                    </span>
                    <?php if ($HealthData['OverdueCount'] > 0): ?>
                    <span class="badge bg-label-danger" style="font-size:.72rem;">
                        <i class="bx bx-error-circle me-1"></i><?php echo $HealthData['OverdueCount']; ?> overdue
                    </span>
                    <?php endif; ?>
                    <?php if ($HealthData['CollectionRate'] < 100 && $HealthData['CollectionRate'] > 0): ?>
                    <span class="text-muted" style="font-size:.74rem;"><?php echo $HealthData['CollectionRate']; ?>% collected</span>
                    <?php endif; ?>
                </div>
                <span class="text-muted" style="font-size:.74rem;">
                    <i class="bx bx-time-five me-1"></i>
                    <?php if ($HealthData['DaysSinceLastTx'] === null): ?>
                        No transactions yet
                    <?php elseif ($HealthData['DaysSinceLastTx'] === 0): ?>
                        Active today
                    <?php elseif ($HealthData['DaysSinceLastTx'] === 1): ?>
                        Last activity yesterday
                    <?php else: ?>
                        Last activity <strong><?php echo $HealthData['DaysSinceLastTx']; ?>d</strong> ago
                    <?php endif; ?>
                </span>
            </div>

            <!-- Contact Info -->
            <div class="card border shadow-none mb-3">
                <div class="card-header py-2 px-3 bg-light d-flex align-items-center gap-2">
                    <i class="bx bx-user text-primary"></i>
                    <span class="fw-semibold" style="font-size:.82rem;">CONTACT INFORMATION</span>
                </div>
                <div class="card-body p-3">
                    <div class="row g-2" style="font-size:.83rem;">

                        <?php if (!empty($Cust->MobileNumber)): ?>
                        <div class="col-sm-6">
                            <div class="text-muted mb-1" style="font-size:.72rem;">Mobile</div>
                            <div class="fw-semibold">
                                <?php if (!empty($Cust->CountryCode)): ?>
                                    <span class="text-muted"><?php echo htmlspecialchars($Cust->CountryCode); ?></span>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($Cust->MobileNumber); ?>
                                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $Cust->CountryCode . $Cust->MobileNumber); ?>?text=Hi"
                                   target="_blank" class="ms-1 text-success" title="WhatsApp">
                                    <i class="bx bxl-whatsapp"></i>
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($Cust->EmailAddress)): ?>
                        <div class="col-sm-6">
                            <div class="text-muted mb-1" style="font-size:.72rem;">Email</div>
                            <div class="fw-semibold text-truncate"><?php echo htmlspecialchars($Cust->EmailAddress); ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($Cust->CompanyName)): ?>
                        <div class="col-sm-6">
                            <div class="text-muted mb-1" style="font-size:.72rem;">Company</div>
                            <div><?php echo htmlspecialchars($Cust->CompanyName); ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($Cust->Area)): ?>
                        <div class="col-sm-6">
                            <div class="text-muted mb-1" style="font-size:.72rem;">Area</div>
                            <div><?php echo htmlspecialchars($Cust->Area); ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($Cust->ContactPerson)): ?>
                        <div class="col-sm-6">
                            <div class="text-muted mb-1" style="font-size:.72rem;">Contact Person</div>
                            <div><?php echo htmlspecialchars($Cust->ContactPerson); ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($Cust->DateOfBirth)): ?>
                        <div class="col-sm-6">
                            <div class="text-muted mb-1" style="font-size:.72rem;">Date of Birth</div>
                            <div><?php echo date($DateFormat, strtotime($Cust->DateOfBirth)); ?></div>
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

                        <?php if (!empty($Cust->GSTIN)): ?>
                        <div class="col-sm-6">
                            <div class="text-muted mb-1" style="font-size:.72rem;">GSTIN</div>
                            <div class="fw-semibold" style="font-family:monospace;"><?php echo htmlspecialchars($Cust->GSTIN); ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($Cust->PANNumber)): ?>
                        <div class="col-sm-6">
                            <div class="text-muted mb-1" style="font-size:.72rem;">PAN</div>
                            <div style="font-family:monospace;"><?php echo htmlspecialchars($Cust->PANNumber); ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($Cust->CreditLimit) && $Cust->CreditLimit > 0): ?>
                        <div class="col-sm-6">
                            <div class="text-muted mb-1" style="font-size:.72rem;">Credit Limit</div>
                            <div><?php echo $fmt((float)$Cust->CreditLimit); ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($Cust->CreditPeriod) && $Cust->CreditPeriod > 0): ?>
                        <div class="col-sm-6">
                            <div class="text-muted mb-1" style="font-size:.72rem;">Credit Period</div>
                            <div><?php echo (int)$Cust->CreditPeriod; ?> days</div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($Cust->DiscountPercent) && $Cust->DiscountPercent > 0): ?>
                        <div class="col-sm-6">
                            <div class="text-muted mb-1" style="font-size:.72rem;">Default Discount</div>
                            <div><?php echo smartDecimal($Cust->DiscountPercent ?? 0); ?>%</div>
                        </div>
                        <?php endif; ?>

                        <?php
                        $obVal  = (float) ($OpeningBal?->OpeningBalance ?? 0);
                        $obType = $OpeningBal?->OpeningBalType ?? 'Debit';
                        if ($obVal > 0):
                        ?>
                        <div class="col-sm-6">
                            <div class="text-muted mb-1" style="font-size:.72rem;">Opening Balance</div>
                            <div class="<?php echo $obType === 'Debit' ? 'text-success' : 'text-danger'; ?>">
                                <?php echo $fmt($obVal); ?>
                                <span class="badge bg-label-secondary ms-1" style="font-size:.65rem;"><?php echo $obType === 'Debit' ? 'To Collect' : 'To Pay'; ?></span>
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
                                    $BillingAddr->Line1    ?? null,
                                    $BillingAddr->Line2    ?? null,
                                    $BillingAddr->CityText ?? null,
                                    $BillingAddr->StateText ?? null,
                                    $BillingAddr->Pincode  ?? null,
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
                                    $ShippingAddr->Line1    ?? null,
                                    $ShippingAddr->Line2    ?? null,
                                    $ShippingAddr->CityText ?? null,
                                    $ShippingAddr->StateText ?? null,
                                    $ShippingAddr->Pincode  ?? null,
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

            <!-- Tags & Type -->
            <?php if (!empty($tags) || !empty($Cust->CustomerTypeName) || $GroupMembership): ?>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <?php if (!empty($Cust->CustomerTypeName)): ?>
                    <span class="badge bg-label-primary"><i class="bx bx-user-pin me-1"></i><?php echo htmlspecialchars($Cust->CustomerTypeName); ?></span>
                <?php endif; ?>
                <?php if ($GroupMembership && !empty($GroupMembership->GroupName)): ?>
                    <span class="badge bg-label-info"><i class="bx bxs-group me-1"></i><?php echo htmlspecialchars($GroupMembership->GroupName); ?></span>
                <?php endif; ?>
                <?php foreach ($tags as $tag): ?>
                    <span class="badge bg-label-secondary"><?php echo htmlspecialchars(trim($tag)); ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($Cust->Notes)): ?>
            <div class="mt-3 p-3 rounded" style="background:#fffbe6;border:1px solid #ffe58f;font-size:.82rem;">
                <i class="bx bx-info-circle text-warning me-1"></i>
                <?php echo nl2br(htmlspecialchars($Cust->Notes)); ?>
            </div>
            <?php endif; ?>

        </div>

        <!-- ── RIGHT: Financial Summary + Chart ────────────────────────── -->
        <div class="col-md-5">

            <!-- KPI Cards (2×2 grid) -->
            <div class="row g-2 mb-3">
                <?php
                $balClass = $ClosingBalance == 0 ? 'secondary' : ($ClosingBalType === 'Debit' ? 'success' : 'danger');
                $balLabel = $ClosingBalance == 0 ? 'No Balance' : ($ClosingBalType === 'Debit' ? 'To Collect' : 'To Pay');
                $cards = [
                    ['label' => 'Outstanding',     'value' => $fmt($ClosingBalance),  'icon' => 'bx-wallet',         'color' => $balClass, 'sub' => $balLabel],
                    ['label' => 'Total Invoiced',  'value' => $fmt($TotalInvoiced),   'icon' => 'bx-receipt',        'color' => 'primary',  'sub' => 'Lifetime'],
                    ['label' => 'Total Received',  'value' => $fmt($TotalReceived),   'icon' => 'bx-down-arrow-alt', 'color' => 'success',  'sub' => 'Payments in'],
                    ['label' => 'Total Returns',   'value' => $fmt($TotalReturned),   'icon' => 'bx-undo',           'color' => 'warning',  'sub' => 'Sales returns'],
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

            <!-- Profitability Card -->
            <?php if ($Profitability['Revenue'] > 0): ?>
            <div class="card border shadow-none mb-3">
                <div class="card-header py-2 px-3 bg-light d-flex align-items-center gap-2">
                    <i class="bx bx-trending-up text-success"></i>
                    <span class="fw-semibold" style="font-size:.82rem;">PROFITABILITY</span>
                    <?php
                    $mrgColor = $Profitability['Margin'] >= 20 ? 'success' : ($Profitability['Margin'] >= 10 ? 'warning' : 'danger');
                    ?>
                    <span class="badge bg-label-<?php echo $mrgColor; ?> ms-auto" style="font-size:.7rem;">
                        <?php echo $Profitability['Margin']; ?>% margin
                    </span>
                </div>
                <div class="card-body p-3">
                    <div class="row g-2 text-center" style="font-size:.8rem;">
                        <div class="col-4">
                            <div class="text-muted mb-1" style="font-size:.68rem;">Revenue</div>
                            <div class="fw-bold text-primary" style="font-size:.82rem;"><?php echo $fmt($Profitability['Revenue']); ?></div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted mb-1" style="font-size:.68rem;">Est. COGS</div>
                            <div class="fw-bold text-danger" style="font-size:.82rem;"><?php echo $fmt($Profitability['COGS']); ?></div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted mb-1" style="font-size:.68rem;">Gross Profit</div>
                            <div class="fw-bold text-<?php echo $Profitability['Profit'] >= 0 ? 'success' : 'danger'; ?>" style="font-size:.82rem;">
                                <?php echo $fmt(abs($Profitability['Profit'])); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- 6-Month Sales Chart (CSS bar chart, server-side rendered) -->
            <div class="card border shadow-none">
                <div class="card-header py-2 px-3 bg-light d-flex align-items-center gap-2">
                    <i class="bx bx-bar-chart-alt-2 text-primary"></i>
                    <span class="fw-semibold" style="font-size:.82rem;">SALES — LAST 6 MONTHS</span>
                </div>
                <div class="card-body p-3">
                    <?php
                    if (empty($MonthlySales)):
                    ?>
                    <div class="d-flex flex-column align-items-center justify-content-center py-4 text-muted" style="font-size:.82rem;">
                        <i class="bx bx-bar-chart-alt fs-3 mb-1"></i>
                        No invoice data in this period
                    </div>
                    <?php
                    else:
                        $maxVal = max(array_column($MonthlySales, 'Total'));
                        $maxVal = $maxVal > 0 ? $maxVal : 1;
                    ?>
                    <div class="d-flex align-items-flex-end gap-1 justify-content-between" style="height:100px;">
                        <?php foreach ($MonthlySales as $row):
                            $pct     = min(100, round(($row['Total'] / $maxVal) * 100));
                            $monthLbl = substr($row['MonthLabel'], 0, 3);
                        ?>
                        <div class="d-flex flex-column align-items-center gap-1 flex-grow-1">
                            <div class="text-muted" style="font-size:.65rem;" title="<?php echo $Cur . ' ' . smartDecimal($row['Total']); ?>">
                                <?php echo $Cur . number_format($row['Total'] / 1000, 1); ?>k
                            </div>
                            <div class="w-100 rounded-top" style="height:<?php echo max(4, $pct); ?>px;background:#696cff;opacity:.8;min-width:20px;"></div>
                            <div class="text-muted" style="font-size:.68rem;"><?php echo htmlspecialchars($monthLbl); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Outstanding Ageing Buckets -->
            <?php
            $ageingTotal = $Ageing['Bucket_0_30'] + $Ageing['Bucket_31_60'] + $Ageing['Bucket_61_90'] + $Ageing['Bucket_90Plus'];
            if ($ageingTotal > 0):
            ?>
            <div class="card border shadow-none mt-3">
                <div class="card-header py-2 px-3 bg-light d-flex align-items-center gap-2">
                    <i class="bx bx-time text-danger"></i>
                    <span class="fw-semibold" style="font-size:.82rem;">OUTSTANDING AGEING</span>
                    <span class="text-muted ms-auto" style="font-size:.7rem;">Total: <?php echo $fmt($ageingTotal); ?></span>
                </div>
                <div class="card-body p-2">
                    <div class="row g-1 text-center" style="font-size:.74rem;">
                        <?php
                        $buckets = [
                            ['label' => '0–30 days',  'val' => $Ageing['Bucket_0_30'],   'color' => 'success'],
                            ['label' => '31–60 days', 'val' => $Ageing['Bucket_31_60'],  'color' => 'warning'],
                            ['label' => '61–90 days', 'val' => $Ageing['Bucket_61_90'],  'color' => 'warning'],
                            ['label' => '90+ days',   'val' => $Ageing['Bucket_90Plus'], 'color' => 'danger'],
                        ];
                        foreach ($buckets as $b):
                        ?>
                        <div class="col-3">
                            <div class="p-2 rounded bg-label-<?php echo $b['color']; ?>">
                                <div class="text-muted mb-1" style="font-size:.65rem;"><?php echo $b['label']; ?></div>
                                <div class="fw-bold" style="font-size:.75rem;"><?php echo $b['val'] > 0 ? $fmt($b['val']) : '—'; ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div><!-- /.col-md-5 -->

    </div><!-- /.row -->
</div>
