<?php defined('BASEPATH') or exit('No direct script access allowed');
/** @var array $Income */  $Income  = $Income  ?? [];
/** @var array $Expense */ $Expense = $Expense ?? [];
/** @var array $Totals */  $Totals  = $Totals  ?? [];
/** @var int   $FY */      $FY      = (int)($FY ?? date('Y'));
$cur     = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
$dec     = (int)($JwtData->GenSettings->DecimalPoints ?? 2);
$dateFmt = $JwtData->GenSettings->ListDateFormat ?? 'd M Y';
$orgName = htmlspecialchars($JwtData->Org->OrgName ?? '');
$dfDisp  = date($dateFmt, strtotime($DateFrom));
$dtDisp  = date($dateFmt, strtotime($DateTo));

/**
 * @param float|null $n
 * @param string     $cur
 * @param int        $dec
 * @returns string
 */
function _bvaFmt(?float $n, string $cur, int $dec): string {
    if ($n === null || abs($n) < 0.005) return '<span class="text-muted bva-dash">—</span>';
    return $cur . ' ' . number_format(abs($n), $dec);
}

/**
 * @param float|null $pct
 * @param string     $type  'income' or 'expense'
 * @returns string
 */
function _bvaPctBadge(?float $pct, string $type): string {
    if ($pct === null) return '<span class="text-muted" style="font-size:.72rem;">—</span>';
    if ($type === 'income') {
        $cls = $pct >= 100 ? 'bg-success' : ($pct >= 75 ? 'bg-primary' : 'bg-warning');
    } else {
        $cls = $pct <= 100 ? 'bg-success' : ($pct <= 115 ? 'bg-warning' : 'bg-danger');
    }
    return '<span class="badge ' . $cls . ' bva-pct-badge">' . $pct . '%</span>';
}

/**
 * @param float $pct
 * @param string $type
 * @returns string
 */
function _bvaBar(float $pct, string $type): string {
    $capped = min(150, abs($pct));
    if ($type === 'income') {
        $color = $pct >= 100 ? '#22c55e' : ($pct >= 75 ? '#3b82f6' : '#f59e0b');
    } else {
        $color = $pct <= 100 ? '#22c55e' : ($pct <= 115 ? '#f59e0b' : '#ef4444');
    }
    return '<div class="bva-mini-bar-wrap"><div class="bva-mini-bar" style="width:' . round($capped / 1.5, 1) . '%;background:' . $color . ';"></div></div>';
}
?>
<div id="bvaReport">

    <div class="text-center mb-3 pl-print-header">
        <div class="fw-bold" style="font-size:1rem;"><?php echo $orgName; ?></div>
        <div class="fw-semibold" style="font-size:.92rem;">Budget vs Actual</div>
        <div class="text-muted" style="font-size:.8rem;">
            FY <?php echo $FY; ?>–<?php echo $FY + 1; ?>
            &nbsp;·&nbsp;
            Actuals: <?php echo $dfDisp; ?> to <?php echo $dtDisp; ?>
        </div>
    </div>

    <!-- ── INCOME ──────────────────────────────────────────────────────────── -->
    <table class="table table-sm table-bordered align-middle bva-tbl mb-3">
        <thead>
            <tr class="bva-section-head bva-income-head">
                <th colspan="6">INCOME</th>
            </tr>
            <tr class="bva-col-head">
                <th>Account</th>
                <th class="text-end bva-col-budget" style="width:140px;">
                    Budget (FY <?php echo $FY; ?>)
                    <div class="text-muted fw-normal" style="font-size:.65rem;">Click to edit</div>
                </th>
                <th class="text-end" style="width:130px;">Actual</th>
                <th class="text-end" style="width:120px;">Variance</th>
                <th class="text-center" style="width:80px;">%</th>
                <th style="width:100px;"></th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($Income)): ?>
        <?php foreach ($Income as $r): ?>
        <tr>
            <td style="font-size:.82rem;">
                <?php echo htmlspecialchars($r->LedgerName); ?>
                <?php if (!empty($r->LedgerCode)): ?>
                <code style="font-size:.7rem;color:#7c3aed;display:block;"><?php echo htmlspecialchars($r->LedgerCode); ?></code>
                <?php endif; ?>
            </td>
            <td class="text-end bva-budget-cell" style="font-size:.82rem;cursor:pointer;"
                data-ledger="<?php echo (int)$r->LedgerUID; ?>"
                data-fy="<?php echo $FY; ?>"
                data-value="<?php echo number_format((float)$r->Budgeted, $dec, '.', ''); ?>"
                title="Click to set budget">
                <?php echo abs((float)$r->Budgeted) > 0.005 ? _bvaFmt((float)$r->Budgeted, $cur, $dec) : '<span class="text-muted">— Set —</span>'; ?>
            </td>
            <td class="text-end fw-semibold text-success" style="font-size:.82rem;">
                <?php echo _bvaFmt((float)$r->Actual, $cur, $dec); ?>
            </td>
            <td class="text-end" style="font-size:.82rem;">
                <?php
                $var = (float)$r->Variance;
                $cls = $var >= 0 ? 'text-success' : 'text-danger';
                $sign= $var >= 0 ? '+' : '−';
                echo abs($var) < 0.005 ? '<span class="text-muted">—</span>' : '<span class="' . $cls . ' fw-semibold">' . $sign . $cur . ' ' . number_format(abs($var), $dec) . '</span>';
                ?>
            </td>
            <td class="text-center" style="font-size:.82rem;">
                <?php echo _bvaPctBadge($r->Pct, 'income'); ?>
            </td>
            <td>
                <?php if ($r->Pct !== null) echo _bvaBar($r->Pct, 'income'); ?>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php else: ?>
        <tr><td colspan="6" class="text-center text-muted py-3" style="font-size:.82rem;">No income accounts</td></tr>
        <?php endif; ?>
        </tbody>
        <tfoot>
            <?php
            $incVar  = $Totals['actualIncome'] - $Totals['budgetIncome'];
            $incVarCls = $incVar >= 0 ? 'text-success' : 'text-danger';
            $incPct  = ($Totals['budgetIncome'] > 0.005) ? round(($Totals['actualIncome'] / $Totals['budgetIncome']) * 100, 1) : null;
            ?>
            <tr class="bva-tfoot bva-income-tfoot">
                <td class="fw-bold">TOTAL INCOME</td>
                <td class="text-end fw-bold"><?php echo _bvaFmt($Totals['budgetIncome'], $cur, $dec); ?></td>
                <td class="text-end fw-bold text-success"><?php echo _bvaFmt($Totals['actualIncome'], $cur, $dec); ?></td>
                <td class="text-end fw-bold <?php echo $incVarCls; ?>">
                    <?php echo abs($incVar) > 0.005 ? ($incVar >= 0 ? '+' : '−') . $cur . ' ' . number_format(abs($incVar), $dec) : '—'; ?>
                </td>
                <td class="text-center"><?php echo _bvaPctBadge($incPct, 'income'); ?></td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <!-- ── EXPENSES ────────────────────────────────────────────────────────── -->
    <table class="table table-sm table-bordered align-middle bva-tbl mb-3">
        <thead>
            <tr class="bva-section-head bva-expense-head">
                <th colspan="6">EXPENSES</th>
            </tr>
            <tr class="bva-col-head">
                <th>Account</th>
                <th class="text-end bva-col-budget" style="width:140px;">
                    Budget (FY <?php echo $FY; ?>)
                    <div class="text-muted fw-normal" style="font-size:.65rem;">Click to edit</div>
                </th>
                <th class="text-end" style="width:130px;">Actual</th>
                <th class="text-end" style="width:120px;">Variance</th>
                <th class="text-center" style="width:80px;">%</th>
                <th style="width:100px;"></th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($Expense)): ?>
        <?php foreach ($Expense as $r): ?>
        <tr>
            <td style="font-size:.82rem;">
                <?php echo htmlspecialchars($r->LedgerName); ?>
                <?php if (!empty($r->LedgerCode)): ?>
                <code style="font-size:.7rem;color:#7c3aed;display:block;"><?php echo htmlspecialchars($r->LedgerCode); ?></code>
                <?php endif; ?>
            </td>
            <td class="text-end bva-budget-cell" style="font-size:.82rem;cursor:pointer;"
                data-ledger="<?php echo (int)$r->LedgerUID; ?>"
                data-fy="<?php echo $FY; ?>"
                data-value="<?php echo number_format((float)$r->Budgeted, $dec, '.', ''); ?>"
                title="Click to set budget">
                <?php echo abs((float)$r->Budgeted) > 0.005 ? _bvaFmt((float)$r->Budgeted, $cur, $dec) : '<span class="text-muted">— Set —</span>'; ?>
            </td>
            <td class="text-end fw-semibold text-danger" style="font-size:.82rem;">
                <?php echo _bvaFmt((float)$r->Actual, $cur, $dec); ?>
            </td>
            <td class="text-end" style="font-size:.82rem;">
                <?php
                $var = (float)$r->Variance; // positive = saved
                $cls = $var >= 0 ? 'text-success' : 'text-danger';
                $sign= $var >= 0 ? '−' : '+'; // saved shows minus (good), over shows plus (bad)
                echo abs($var) < 0.005 ? '<span class="text-muted">—</span>' : '<span class="' . $cls . ' fw-semibold">' . $sign . $cur . ' ' . number_format(abs($var), $dec) . '</span>';
                ?>
            </td>
            <td class="text-center" style="font-size:.82rem;">
                <?php echo _bvaPctBadge($r->Pct, 'expense'); ?>
            </td>
            <td>
                <?php if ($r->Pct !== null) echo _bvaBar($r->Pct, 'expense'); ?>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php else: ?>
        <tr><td colspan="6" class="text-center text-muted py-3" style="font-size:.82rem;">No expense accounts</td></tr>
        <?php endif; ?>
        </tbody>
        <tfoot>
            <?php
            $expVar  = $Totals['budgetExpense'] - $Totals['actualExpense']; // positive = saved
            $expVarCls = $expVar >= 0 ? 'text-success' : 'text-danger';
            $expPct  = ($Totals['budgetExpense'] > 0.005) ? round(($Totals['actualExpense'] / $Totals['budgetExpense']) * 100, 1) : null;
            ?>
            <tr class="bva-tfoot bva-expense-tfoot">
                <td class="fw-bold">TOTAL EXPENSES</td>
                <td class="text-end fw-bold"><?php echo _bvaFmt($Totals['budgetExpense'], $cur, $dec); ?></td>
                <td class="text-end fw-bold text-danger"><?php echo _bvaFmt($Totals['actualExpense'], $cur, $dec); ?></td>
                <td class="text-end fw-bold <?php echo $expVarCls; ?>">
                    <?php echo abs($expVar) > 0.005 ? ($expVar >= 0 ? '−' : '+') . $cur . ' ' . number_format(abs($expVar), $dec) : '—'; ?>
                </td>
                <td class="text-center"><?php echo _bvaPctBadge($expPct, 'expense'); ?></td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <!-- ── NET SURPLUS / DEFICIT ────────────────────────────────────────────── -->
    <?php
    $netVar   = $Totals['netVariance'];
    $netIsPos = $netVar >= 0;
    $netIsLoss= $Totals['actualNet'] < 0;
    $netBudLoss = $Totals['budgetNet'] < 0;
    ?>
    <table class="table table-sm table-bordered align-middle bva-tbl mb-0">
        <tbody>
            <tr class="bva-net-row <?php echo $netIsLoss ? 'bva-net-loss' : 'bva-net-profit'; ?>">
                <td class="fw-bold" style="font-size:.9rem;">
                    <?php echo $netIsLoss ? 'NET DEFICIT (Budget: ' . ($netBudLoss ? 'Deficit' : 'Profit') . ')' : 'NET SURPLUS (Profit)'; ?>
                </td>
                <td class="text-end fw-bold" style="font-size:.88rem;width:140px;
                    color:<?php echo $netBudLoss ? '#dc2626' : '#16a34a'; ?>;">
                    <?php echo _bvaFmt($Totals['budgetNet'], $cur, $dec); ?>
                </td>
                <td class="text-end fw-bold" style="font-size:.88rem;width:130px;
                    color:<?php echo $netIsLoss ? '#dc2626' : '#16a34a'; ?>;">
                    <?php echo _bvaFmt($Totals['actualNet'], $cur, $dec); ?>
                </td>
                <td class="text-end fw-bold <?php echo $netIsPos ? 'text-success' : 'text-danger'; ?>" style="font-size:.88rem;width:120px;">
                    <?php echo abs($netVar) > 0.005 ? ($netIsPos ? '+' : '−') . $cur . ' ' . number_format(abs($netVar), $dec) : '—'; ?>
                </td>
                <td colspan="2"></td>
            </tr>
        </tbody>
    </table>

</div>
