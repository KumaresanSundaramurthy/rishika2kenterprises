<?php defined('BASEPATH') or exit('No direct script access allowed');
/** @var array  $AssetGroups */      $AssetGroups      = $AssetGroups      ?? [];
/** @var array  $LiabGroups */       $LiabGroups       = $LiabGroups       ?? [];
/** @var float  $TotalAssets */      $TotalAssets      = (float)($TotalAssets      ?? 0);
/** @var float  $TotalLiabilities */ $TotalLiabilities = (float)($TotalLiabilities ?? 0);
/** @var float  $NetProfit */        $NetProfit        = (float)($NetProfit        ?? 0);
$cur     = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
$dateFmt = $JwtData->GenSettings->ListDateFormat ?? 'd M Y';
$orgName = htmlspecialchars($JwtData->Org->OrgName ?? '');
$asOfDisp = date($dateFmt, strtotime($AsOfDate));
$pnlDisp  = date($dateFmt, strtotime($PnlFrom));
$totalLE  = $TotalLiabilities + $NetProfit;
$diff     = abs($TotalAssets - $totalLE);
$balanced = $diff < 0.01;
$isLoss   = $NetProfit < 0;

/**
 * @param float  $n
 * @param string $cur
 * @param int    $dec
 * @returns string
 */
function _bsFmt(float $n, string $cur): string {
    if (abs($n) < 0.005) return '<span class="text-muted" style="font-size:.78rem;">—</span>';
    return $cur . ' ' . smartDecimal(abs($n));
}
?>
<div class="bs-statement" id="bsStatement">

    <!-- Statement Header -->
    <div class="text-center mb-4 pl-print-header">
        <div class="fw-bold" style="font-size:1rem;"><?php echo $orgName; ?></div>
        <div class="fw-semibold mt-1" style="font-size:.92rem;">Balance Sheet</div>
        <div class="text-muted" style="font-size:.8rem;">
            As of <?php echo $asOfDisp; ?>
            &nbsp;·&nbsp;
            P&amp;L from <?php echo $pnlDisp; ?>
        </div>
    </div>

    <div class="row g-3">

        <!-- ── ASSETS (left column) ───────────────────────────────────────── -->
        <div class="col-md-6">
            <table class="table table-sm table-bordered align-middle mb-0 bs-tbl h-100">
                <thead>
                    <tr class="pl-section-head" style="background:#1d4ed8;color:#fff;">
                        <th colspan="3">ASSETS</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($AssetGroups as $type => $grp):
                    if (empty($grp['items'])) continue;
                ?>
                    <tr class="bs-group-head">
                        <td colspan="3" style="padding:5px 12px;font-weight:600;font-size:.78rem;background:#eff6ff;color:#1d4ed8;">
                            <?php echo htmlspecialchars($grp['label']); ?>
                        </td>
                    </tr>
                    <?php foreach ($grp['items'] as $r):
                        $cb     = (float)$r->ClosingBalance;
                        $cbType = $r->ClosingBalanceType;
                        $isDr   = $cbType === 'Debit';
                    ?>
                    <tr <?php echo abs($cb) < 0.005 ? 'class="text-muted"' : ''; ?>>
                        <td style="width:16px;padding-left:20px;border-left:3px solid #bfdbfe;"></td>
                        <td style="font-size:.82rem;">
                            <?php echo htmlspecialchars($r->LedgerName); ?>
                            <?php if (!$isDr && abs($cb) > 0.005): ?>
                            <span class="badge bg-label-danger ms-1" style="font-size:.6rem;" title="Credit balance — unusual for asset">Cr</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end fw-semibold" style="font-size:.82rem;white-space:nowrap;width:130px;
                            <?php echo (!$isDr && abs($cb) > 0.005) ? 'color:#dc2626;' : ''; ?>">
                            <?php echo _bsFmt($cb, $cur); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="bs-group-subtotal">
                        <td colspan="2" style="font-weight:600;font-size:.78rem;padding-left:20px;">
                            Subtotal — <?php echo htmlspecialchars($grp['label']); ?>
                        </td>
                        <td class="text-end fw-bold" style="font-size:.8rem;white-space:nowrap;
                            <?php echo $grp['total'] < 0 ? 'color:#dc2626;' : 'color:#1d4ed8;'; ?>">
                            <?php echo _bsFmt($grp['total'], $cur); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background:#1d4ed8;color:#fff;font-weight:700;">
                        <td colspan="2" style="padding:10px 12px;">TOTAL ASSETS</td>
                        <td class="text-end" style="padding:10px 12px;font-size:.9rem;white-space:nowrap;">
                            <?php echo $cur . ' ' . smartDecimal($TotalAssets); ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- ── LIABILITIES + NET PROFIT (right column) ──────────────────── -->
        <div class="col-md-6">
            <table class="table table-sm table-bordered align-middle mb-0 bs-tbl">
                <thead>
                    <tr class="pl-section-head" style="background:#7c2d12;color:#fff;">
                        <th colspan="3">LIABILITIES &amp; EQUITY</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($LiabGroups as $type => $grp):
                    if (empty($grp['items'])) continue;
                ?>
                    <tr class="bs-group-head">
                        <td colspan="3" style="padding:5px 12px;font-weight:600;font-size:.78rem;background:#fff7ed;color:#9a3412;">
                            <?php echo htmlspecialchars($grp['label']); ?>
                        </td>
                    </tr>
                    <?php foreach ($grp['items'] as $r):
                        $cb     = (float)$r->ClosingBalance;
                        $cbType = $r->ClosingBalanceType;
                        $isCr   = $cbType === 'Credit';
                    ?>
                    <tr <?php echo abs($cb) < 0.005 ? 'class="text-muted"' : ''; ?>>
                        <td style="width:16px;padding-left:20px;border-left:3px solid #fdba74;"></td>
                        <td style="font-size:.82rem;">
                            <?php echo htmlspecialchars($r->LedgerName); ?>
                            <?php if (!$isCr && abs($cb) > 0.005): ?>
                            <span class="badge bg-label-info ms-1" style="font-size:.6rem;" title="Debit balance — unusual for liability">Dr</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end fw-semibold" style="font-size:.82rem;white-space:nowrap;width:130px;">
                            <?php echo _bsFmt($cb, $cur); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="bs-group-subtotal">
                        <td colspan="2" style="font-weight:600;font-size:.78rem;padding-left:20px;">
                            Subtotal — <?php echo htmlspecialchars($grp['label']); ?>
                        </td>
                        <td class="text-end fw-bold" style="font-size:.8rem;white-space:nowrap;color:#9a3412;">
                            <?php echo _bsFmt($grp['total'], $cur); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                    <!-- Net Profit / Loss separator -->
                    <tr style="background:#f0fdf4;">
                        <td style="width:16px;border-left:3px solid #86efac;"></td>
                        <td style="font-size:.82rem;font-weight:600;color:<?php echo $isLoss ? '#dc2626' : '#16a34a'; ?>;">
                            <?php echo $isLoss ? 'Net Loss for Period' : 'Net Profit for Period'; ?>
                            <div class="text-muted fw-normal" style="font-size:.7rem;">
                                <?php echo date($dateFmt, strtotime($PnlFrom)); ?> to <?php echo date($dateFmt, strtotime($AsOfDate)); ?>
                            </div>
                        </td>
                        <td class="text-end fw-bold" style="font-size:.82rem;white-space:nowrap;
                            color:<?php echo $isLoss ? '#dc2626' : '#16a34a'; ?>;">
                            <?php echo _bsFmt($NetProfit, $cur); ?>
                            <?php if ($isLoss): ?><div style="font-size:.7rem;">(Loss)</div><?php endif; ?>
                        </td>
                    </tr>

                </tbody>
                <tfoot>
                    <tr style="background:#7c2d12;color:#fff;font-weight:700;">
                        <td colspan="2" style="padding:10px 12px;">TOTAL LIABILITIES + NET PROFIT</td>
                        <td class="text-end" style="padding:10px 12px;font-size:.9rem;white-space:nowrap;">
                            <?php echo $cur . ' ' . smartDecimal($totalLE); ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- ── Balance Check ──────────────────────────────────────────────────── -->
    <div class="mt-3 py-3 px-4 rounded <?php echo $balanced ? 'bs-balanced' : 'bs-unbalanced'; ?>">
        <?php if ($balanced): ?>
            <i class="bx bx-check-circle me-2"></i>
            <strong>BALANCED</strong> — Total Assets equals Total Liabilities + Net Profit
        <?php else: ?>
            <i class="bx bx-error me-2"></i>
            <strong>UNBALANCED</strong> — Difference of
            <?php echo $cur . ' ' . smartDecimal($diff); ?>.
            Check if all transactions are posted through this system.
        <?php endif; ?>
    </div>

</div>
