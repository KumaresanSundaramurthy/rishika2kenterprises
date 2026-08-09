<?php defined('BASEPATH') or exit('No direct script access allowed');
/** @var array  $Income  */ $Income  = $Income  ?? [];
/** @var array  $Expense */ $Expense = $Expense ?? [];
/** @var array  $Totals  */ $Totals  = $Totals  ?? [];
$cur     = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
$dec     = (int)($JwtData->GenSettings->DecimalPoints ?? 2);
$dateFmt = $JwtData->GenSettings->ListDateFormat ?? 'd M Y';
$orgName = htmlspecialchars($JwtData->Org->OrgName ?? '');

$p1FDisp = date($dateFmt, strtotime($P1From));
$p1TDisp = date($dateFmt, strtotime($P1To));
$p2FDisp = date($dateFmt, strtotime($P2From));
$p2TDisp = date($dateFmt, strtotime($P2To));
$p1Label = htmlspecialchars($P1Label ?? 'Period 1');
$p2Label = htmlspecialchars($P2Label ?? 'Period 2');

/**
 * @param float  $n
 * @param string $cur
 * @param int    $dec
 * @returns string
 */
function _cmpFmt(float $n, string $cur, int $dec): string {
    if (abs($n) < 0.005) return '<span class="text-muted comp-dash">—</span>';
    return $cur . ' ' . number_format(abs($n), $dec);
}

/**
 * @param float|null $pct
 * @param bool       $higherIsBetter
 * @returns string
 */
function _cmpPct(?float $pct, bool $higherIsBetter): string {
    if ($pct === null) return '<span class="text-muted">—</span>';
    $good = $higherIsBetter ? $pct >= 0 : $pct <= 0;
    $cls  = $good ? 'text-success' : 'text-danger';
    $sign = $pct >= 0 ? '+' : '';
    return '<span class="' . $cls . ' fw-semibold comp-pct">' . $sign . $pct . '%</span>';
}

/**
 * @param float $var
 * @param bool  $higherIsBetter
 * @param string $cur
 * @param int    $dec
 * @returns string
 */
function _cmpVar(float $var, bool $higherIsBetter, string $cur, int $dec): string {
    if (abs($var) < 0.005) return '<span class="text-muted">—</span>';
    $good = $higherIsBetter ? $var >= 0 : $var <= 0;
    $cls  = $good ? 'text-success' : 'text-danger';
    $sign = $var >= 0 ? '+' : '−';
    return '<span class="' . $cls . ' fw-semibold">' . $sign . $cur . ' ' . number_format(abs($var), $dec) . '</span>';
}
?>
<div id="cmpStatement">

    <div class="text-center mb-3 pl-print-header">
        <div class="fw-bold" style="font-size:1rem;"><?php echo $orgName; ?></div>
        <div class="fw-semibold" style="font-size:.92rem;">Comparative Profit &amp; Loss Statement</div>
        <div class="text-muted" style="font-size:.78rem;">
            <?php echo $p1Label; ?>: <?php echo $p1FDisp; ?> – <?php echo $p1TDisp; ?>
            &nbsp;|&nbsp;
            <?php echo $p2Label; ?>: <?php echo $p2FDisp; ?> – <?php echo $p2TDisp; ?>
        </div>
    </div>

    <!-- ── INCOME ──────────────────────────────────────────────────────────── -->
    <table class="table table-sm table-bordered align-middle comp-tbl mb-3">
        <thead>
            <tr class="comp-section-head comp-income-head">
                <th colspan="5">INCOME</th>
            </tr>
            <tr class="comp-col-head">
                <th>Account</th>
                <th class="text-end" style="width:130px;"><?php echo $p1Label; ?></th>
                <th class="text-end" style="width:130px;"><?php echo $p2Label; ?></th>
                <th class="text-end" style="width:120px;">Variance</th>
                <th class="text-center" style="width:80px;">% Change</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($Income)): ?>
        <?php foreach ($Income as $r): ?>
        <tr class="comp-row">
            <td style="font-size:.82rem;">
                <?php echo htmlspecialchars($r->LedgerName); ?>
                <?php if (!empty($r->LedgerCode)): ?>
                <code style="font-size:.7rem;color:#7c3aed;display:block;"><?php echo htmlspecialchars($r->LedgerCode); ?></code>
                <?php endif; ?>
            </td>
            <td class="text-end fw-semibold comp-p1-val" style="font-size:.82rem;"><?php echo _cmpFmt($r->Net1, $cur, $dec); ?></td>
            <td class="text-end comp-p2-val" style="font-size:.82rem;"><?php echo _cmpFmt($r->Net2, $cur, $dec); ?></td>
            <td class="text-end" style="font-size:.82rem;"><?php echo _cmpVar($r->Variance, true, $cur, $dec); ?></td>
            <td class="text-center" style="font-size:.82rem;"><?php echo _cmpPct($r->PctChange, true); ?></td>
        </tr>
        <?php endforeach; ?>
        <?php else: ?>
        <tr><td colspan="5" class="text-center text-muted py-3" style="font-size:.82rem;">No income accounts</td></tr>
        <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="comp-tfoot comp-income-tfoot">
                <td class="fw-bold">TOTAL INCOME</td>
                <td class="text-end fw-bold comp-p1-val"><?php echo _cmpFmt($Totals['income1'], $cur, $dec); ?></td>
                <td class="text-end fw-bold comp-p2-val"><?php echo _cmpFmt($Totals['income2'], $cur, $dec); ?></td>
                <td class="text-end fw-bold"><?php echo _cmpVar($Totals['incVar'], true, $cur, $dec); ?></td>
                <td class="text-center"><?php echo _cmpPct($Totals['incPct'], true); ?></td>
            </tr>
        </tfoot>
    </table>

    <!-- ── EXPENSES ────────────────────────────────────────────────────────── -->
    <table class="table table-sm table-bordered align-middle comp-tbl mb-3">
        <thead>
            <tr class="comp-section-head comp-expense-head">
                <th colspan="5">EXPENSES</th>
            </tr>
            <tr class="comp-col-head">
                <th>Account</th>
                <th class="text-end" style="width:130px;"><?php echo $p1Label; ?></th>
                <th class="text-end" style="width:130px;"><?php echo $p2Label; ?></th>
                <th class="text-end" style="width:120px;">Variance</th>
                <th class="text-center" style="width:80px;">% Change</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($Expense)): ?>
        <?php foreach ($Expense as $r): ?>
        <tr class="comp-row">
            <td style="font-size:.82rem;">
                <?php echo htmlspecialchars($r->LedgerName); ?>
                <?php if (!empty($r->LedgerCode)): ?>
                <code style="font-size:.7rem;color:#7c3aed;display:block;"><?php echo htmlspecialchars($r->LedgerCode); ?></code>
                <?php endif; ?>
            </td>
            <td class="text-end fw-semibold comp-p1-val" style="font-size:.82rem;"><?php echo _cmpFmt($r->Net1, $cur, $dec); ?></td>
            <td class="text-end comp-p2-val" style="font-size:.82rem;"><?php echo _cmpFmt($r->Net2, $cur, $dec); ?></td>
            <td class="text-end" style="font-size:.82rem;"><?php echo _cmpVar($r->Variance, false, $cur, $dec); ?></td>
            <td class="text-center" style="font-size:.82rem;"><?php echo _cmpPct($r->PctChange, false); ?></td>
        </tr>
        <?php endforeach; ?>
        <?php else: ?>
        <tr><td colspan="5" class="text-center text-muted py-3" style="font-size:.82rem;">No expense accounts</td></tr>
        <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="comp-tfoot comp-expense-tfoot">
                <td class="fw-bold">TOTAL EXPENSES</td>
                <td class="text-end fw-bold comp-p1-val"><?php echo _cmpFmt($Totals['expense1'], $cur, $dec); ?></td>
                <td class="text-end fw-bold comp-p2-val"><?php echo _cmpFmt($Totals['expense2'], $cur, $dec); ?></td>
                <td class="text-end fw-bold"><?php echo _cmpVar($Totals['expVar'], false, $cur, $dec); ?></td>
                <td class="text-center"><?php echo _cmpPct($Totals['expPct'], false); ?></td>
            </tr>
        </tfoot>
    </table>

    <!-- ── NET PROFIT / LOSS ────────────────────────────────────────────────── -->
    <?php
    $net1IsLoss = $Totals['net1'] < 0;
    $net2IsLoss = $Totals['net2'] < 0;
    ?>
    <table class="table table-sm table-bordered align-middle comp-tbl mb-0">
        <tbody>
            <tr class="comp-net-row <?php echo $net1IsLoss ? 'comp-net-loss' : 'comp-net-profit'; ?>">
                <td class="fw-bold" style="font-size:.9rem;">NET <?php echo $net1IsLoss ? 'DEFICIT' : 'SURPLUS'; ?> (Profit)</td>
                <td class="text-end fw-bold" style="font-size:.88rem;width:130px;color:<?php echo $net1IsLoss ? '#dc2626' : '#16a34a'; ?>;">
                    <?php echo _cmpFmt($Totals['net1'], $cur, $dec); ?>
                </td>
                <td class="text-end fw-bold" style="font-size:.88rem;width:130px;color:<?php echo $net2IsLoss ? '#dc2626' : '#16a34a'; ?>;">
                    <?php echo _cmpFmt($Totals['net2'], $cur, $dec); ?>
                </td>
                <td class="text-end fw-bold" style="font-size:.88rem;width:120px;">
                    <?php echo _cmpVar($Totals['netVar'], true, $cur, $dec); ?>
                </td>
                <td class="text-center" style="width:80px;">
                    <?php echo _cmpPct($Totals['netPct'], true); ?>
                </td>
            </tr>
        </tbody>
    </table>

    <div class="comp-footnote mt-2 text-muted">
        <i class="bx bx-info-circle me-1"></i>
        Variance = <?php echo $p2Label; ?> minus <?php echo $p1Label; ?>.
        Positive variance on income is favourable; positive variance on expenses is unfavourable.
    </div>

</div>
