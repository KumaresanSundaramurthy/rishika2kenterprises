<?php defined('BASEPATH') or exit('No direct script access allowed');
/** @var object $Ratios */ $Ratios = $Ratios ?? (object)[];
$cur     = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
$dateFmt = $JwtData->GenSettings->ListDateFormat ?? 'd M Y';
$orgName = htmlspecialchars($JwtData->Org->OrgName ?? '');
$asDisp  = date($dateFmt, strtotime($AsOfDate));
$pfDisp  = date($dateFmt, strtotime($PnlFrom));
$ptDisp  = date($dateFmt, strtotime($PnlTo));

/**
 * @param float|null $v
 * @param int        $dp
 * @param string     $suffix
 * @returns string
 */
function _ratioVal(?float $v, int $dp = 2, string $suffix = ''): string {
    if ($v === null) return '<span class="ratio-na">N/A</span>';
    return '<span class="ratio-number">' . number_format($v, $dp) . $suffix . '</span>';
}

/**
 * @param float|null $v
 * @param float      $good
 * @param float      $warn
 * @param bool       $higherIsBetter
 * @returns string
 */
function _ratioBadge(?float $v, float $good, float $warn, bool $higherIsBetter = true): string {
    if ($v === null) return '';
    if ($higherIsBetter) {
        $cls = $v >= $good ? 'ratio-badge-good' : ($v >= $warn ? 'ratio-badge-warn' : 'ratio-badge-bad');
        $lbl = $v >= $good ? 'Strong' : ($v >= $warn ? 'Adequate' : 'Weak');
    } else {
        $cls = $v <= $good ? 'ratio-badge-good' : ($v <= $warn ? 'ratio-badge-warn' : 'ratio-badge-bad');
        $lbl = $v <= $good ? 'Good' : ($v <= $warn ? 'Moderate' : 'High');
    }
    return '<span class="ratio-badge ' . $cls . '">' . $lbl . '</span>';
}
?>
<div id="ratioDashboard">

    <div class="text-center mb-4 pl-print-header">
        <div class="fw-bold" style="font-size:1rem;"><?php echo $orgName; ?></div>
        <div class="fw-semibold" style="font-size:.92rem;">Financial Ratios Dashboard</div>
        <div class="text-muted" style="font-size:.78rem;">
            Balance Sheet: <?php echo $asDisp; ?>
            &nbsp;·&nbsp;
            P&amp;L: <?php echo $pfDisp; ?> – <?php echo $ptDisp; ?>
        </div>
    </div>

    <div class="row g-3 mb-4">

        <!-- ── Summary Row ─────────────────────────────────────────────────── -->
        <div class="col-12">
            <div class="row g-2">
                <?php $items = [
                    ['label'=>'Total Assets',     'val'=>$cur.' '.smartDecimal($Ratios->totalAssets), 'icon'=>'bx-building-house', 'col'=>'#1d4ed8'],
                    ['label'=>'Total Liabilities','val'=>$cur.' '.smartDecimal($Ratios->totalLiab), 'icon'=>'bx-credit-card',    'col'=>'#dc2626'],
                    ['label'=>'Equity',           'val'=>$cur.' '.smartDecimal($Ratios->equity), 'icon'=>'bx-dollar-circle',  'col'=>$Ratios->equity>=0?'#059669':'#dc2626'],
                    ['label'=>'Net Profit',       'val'=>$cur.' '.smartDecimal($Ratios->netProfit), 'icon'=>'bx-trending-up',    'col'=>$Ratios->netProfit>=0?'#059669':'#dc2626'],
                ]; foreach ($items as $item): ?>
                <div class="col-md-3">
                    <div class="card ratio-summary-card">
                        <div class="card-body p-3 d-flex align-items-center gap-2">
                            <i class="bx <?php echo $item['icon']; ?> fs-3" style="color:<?php echo $item['col']; ?>;"></i>
                            <div>
                                <div class="text-muted" style="font-size:.72rem;"><?php echo $item['label']; ?></div>
                                <div class="fw-bold" style="font-size:.9rem;color:<?php echo $item['col']; ?>;"><?php echo $item['val']; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ── Liquidity ───────────────────────────────────────────────────── -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header ratio-group-head ratio-liquidity-head">
                    <i class="bx bx-water me-2"></i>Liquidity Ratios
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm ratio-tbl mb-0">
                        <thead>
                            <tr class="ratio-col-head">
                                <th>Ratio</th>
                                <th class="text-end">Value</th>
                                <th class="text-center">Signal</th>
                                <th class="text-muted ratio-bench-col">Benchmark</th>
                                <th class="ratio-explain-col">Explanation</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="ratio-name">Current Ratio</td>
                                <td class="text-end"><?php echo _ratioVal($Ratios->currentRatio); ?></td>
                                <td class="text-center"><?php echo _ratioBadge($Ratios->currentRatio, 2.0, 1.0); ?></td>
                                <td class="ratio-bench">≥ 2.0</td>
                                <td class="ratio-explain">Assets ÷ Liabilities. &gt;2 = strong buffer.</td>
                            </tr>
                            <tr>
                                <td class="ratio-name">Quick Ratio</td>
                                <td class="text-end"><?php echo _ratioVal($Ratios->quickRatio); ?></td>
                                <td class="text-center"><?php echo _ratioBadge($Ratios->quickRatio, 1.0, 0.5); ?></td>
                                <td class="ratio-bench">≥ 1.0</td>
                                <td class="ratio-explain">(Cash+Receivables) ÷ Liabilities. Excludes stock.</td>
                            </tr>
                            <tr>
                                <td class="ratio-name">Cash Ratio</td>
                                <td class="text-end"><?php echo _ratioVal($Ratios->cashRatio); ?></td>
                                <td class="text-center"><?php echo _ratioBadge($Ratios->cashRatio, 0.5, 0.2); ?></td>
                                <td class="ratio-bench">≥ 0.5</td>
                                <td class="ratio-explain">Cash &amp; Bank ÷ Liabilities. Most conservative.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ── Profitability ────────────────────────────────────────────────── -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header ratio-group-head ratio-profit-head">
                    <i class="bx bx-line-chart me-2"></i>Profitability Ratios
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm ratio-tbl mb-0">
                        <thead>
                            <tr class="ratio-col-head">
                                <th>Ratio</th>
                                <th class="text-end">Value</th>
                                <th class="text-center">Signal</th>
                                <th class="text-muted ratio-bench-col">Benchmark</th>
                                <th class="ratio-explain-col">Explanation</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="ratio-name">Net Profit Margin</td>
                                <td class="text-end"><?php echo _ratioVal($Ratios->netProfitMargin, 1, '%'); ?></td>
                                <td class="text-center"><?php echo _ratioBadge($Ratios->netProfitMargin, 10.0, 5.0); ?></td>
                                <td class="ratio-bench">≥ 10%</td>
                                <td class="ratio-explain">Net Profit ÷ Revenue × 100.</td>
                            </tr>
                            <tr>
                                <td class="ratio-name">Return on Assets (ROA)</td>
                                <td class="text-end"><?php echo _ratioVal($Ratios->roa, 1, '%'); ?></td>
                                <td class="text-center"><?php echo _ratioBadge($Ratios->roa, 5.0, 2.0); ?></td>
                                <td class="ratio-bench">≥ 5%</td>
                                <td class="ratio-explain">Net Profit ÷ Total Assets × 100.</td>
                            </tr>
                            <tr>
                                <td class="ratio-name">Return on Equity (ROE)</td>
                                <td class="text-end"><?php echo _ratioVal($Ratios->roe, 1, '%'); ?></td>
                                <td class="text-center"><?php echo _ratioBadge($Ratios->roe, 15.0, 8.0); ?></td>
                                <td class="ratio-bench">≥ 15%</td>
                                <td class="ratio-explain">Net Profit ÷ Equity × 100.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ── Activity ─────────────────────────────────────────────────────── -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header ratio-group-head ratio-activity-head">
                    <i class="bx bx-refresh me-2"></i>Activity Ratios
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm ratio-tbl mb-0">
                        <thead>
                            <tr class="ratio-col-head">
                                <th>Ratio</th>
                                <th class="text-end">Value</th>
                                <th class="text-center">Signal</th>
                                <th class="text-muted ratio-bench-col">Benchmark</th>
                                <th class="ratio-explain-col">Explanation</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="ratio-name">Receivables Days (DRO)</td>
                                <td class="text-end"><?php echo _ratioVal($Ratios->receivablesDays, 0, ' days'); ?></td>
                                <td class="text-center"><?php echo _ratioBadge($Ratios->receivablesDays, 30.0, 60.0, false); ?></td>
                                <td class="ratio-bench">≤ 30 days</td>
                                <td class="ratio-explain">Avg days to collect from customers.</td>
                            </tr>
                            <tr>
                                <td class="ratio-name">Payables Days (DPO)</td>
                                <td class="text-end"><?php echo _ratioVal($Ratios->payablesDays, 0, ' days'); ?></td>
                                <td class="text-center"><?php echo _ratioBadge($Ratios->payablesDays, 45.0, 30.0, false); ?></td>
                                <td class="ratio-bench">≥ 30 days</td>
                                <td class="ratio-explain">Avg days before paying vendors.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ── Leverage ─────────────────────────────────────────────────────── -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header ratio-group-head ratio-leverage-head">
                    <i class="bx bx-buildings me-2"></i>Leverage Ratios
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm ratio-tbl mb-0">
                        <thead>
                            <tr class="ratio-col-head">
                                <th>Ratio</th>
                                <th class="text-end">Value</th>
                                <th class="text-center">Signal</th>
                                <th class="text-muted ratio-bench-col">Benchmark</th>
                                <th class="ratio-explain-col">Explanation</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="ratio-name">Debt to Equity</td>
                                <td class="text-end"><?php echo _ratioVal($Ratios->debtToEquity); ?></td>
                                <td class="text-center"><?php echo _ratioBadge($Ratios->debtToEquity, 1.0, 2.0, false); ?></td>
                                <td class="ratio-bench">≤ 1.0</td>
                                <td class="ratio-explain">Total Liabilities ÷ Equity. Lower = less leveraged.</td>
                            </tr>
                            <tr>
                                <td class="ratio-name">Equity Ratio</td>
                                <td class="text-end"><?php echo _ratioVal($Ratios->equityRatio, 1, '%'); ?></td>
                                <td class="text-center"><?php echo _ratioBadge($Ratios->equityRatio, 50.0, 30.0); ?></td>
                                <td class="ratio-bench">≥ 50%</td>
                                <td class="ratio-explain">Equity ÷ Total Assets × 100.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <div class="text-muted mb-3 ratio-footnote">
        <i class="bx bx-info-circle me-1"></i>
        Benchmarks are general industry guidelines. Actual targets vary by sector and business model.
        Receivables and Payables ratios use annualised P&amp;L income/expense figures.
    </div>

</div>
