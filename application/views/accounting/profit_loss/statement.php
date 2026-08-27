<?php defined('BASEPATH') or exit('No direct script access allowed');
/** @var array $Income */   $Income      = $Income      ?? [];
/** @var array $Expense */  $Expense     = $Expense     ?? [];
/** @var float $TotalIncome */  $TotalIncome  = (float)($TotalIncome  ?? 0);
/** @var float $TotalExpense */ $TotalExpense = (float)($TotalExpense ?? 0);
/** @var float $NetProfit */    $NetProfit    = (float)($NetProfit    ?? 0);
$cur     = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
$dateFmt = $JwtData->GenSettings->ListDateFormat ?? 'd M Y';
$orgName = htmlspecialchars($JwtData->Org->OrgName ?? '');
$dfDisp  = date($dateFmt, strtotime($DateFrom));
$dtDisp  = date($dateFmt, strtotime($DateTo));
$isLoss  = $NetProfit < 0;

/**
 * @param float  $n
 * @param string $cur
 * @param int    $dec
 * @returns string
 */
function _plFmt(float $n, string $cur): string {
    if (abs($n) < 0.005) return '<span class="text-muted">—</span>';
    return $cur . ' ' . smartDecimal(abs($n));
}
?>
<div class="pl-statement" id="plStatement">

    <!-- Statement Header -->
    <div class="text-center mb-4 pl-print-header">
        <div class="fw-bold" style="font-size:1rem;"><?php echo $orgName; ?></div>
        <div class="fw-semibold mt-1" style="font-size:.92rem;">Profit &amp; Loss Statement</div>
        <div class="text-muted" style="font-size:.8rem;">
            Period: <?php echo $dfDisp; ?> to <?php echo $dtDisp; ?>
        </div>
    </div>

    <!-- ── INCOME ─────────────────────────────────────────────────────────── -->
    <table class="table table-sm table-bordered align-middle mb-0 pl-tbl" id="plPrintTable">
        <thead>
            <tr class="pl-section-head income-head">
                <th colspan="3">INCOME</th>
            </tr>
            <tr class="pl-col-head">
                <th style="width:60px;">Code</th>
                <th>Account</th>
                <th class="text-end" style="width:150px;">Amount</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($Income)): ?>
            <?php foreach ($Income as $r):
                $net = (float)$r->NetAmount;
                $muted = abs($net) < 0.005;
            ?>
            <tr <?php echo $muted ? 'class="text-muted"' : ''; ?>>
                <td><code style="font-size:.75rem;color:#7c3aed;"><?php echo htmlspecialchars($r->LedgerCode ?? ''); ?></code></td>
                <td><?php echo htmlspecialchars($r->LedgerName); ?></td>
                <td class="text-end fw-semibold <?php echo $net < 0 ? 'text-danger' : 'text-success'; ?>">
                    <?php echo _plFmt($net, $cur); ?>
                    <?php if ($net < 0): ?><small class="ms-1" style="font-size:.7rem;">(Dr)</small><?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="3" class="text-center text-muted py-3" style="font-size:.82rem;">No income accounts found</td></tr>
        <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="pl-subtotal income-subtotal">
                <td colspan="2" class="fw-semibold">TOTAL INCOME</td>
                <td class="text-end fw-bold text-success"><?php echo $cur . ' ' . smartDecimal(max(0, $TotalIncome)); ?></td>
            </tr>
        </tfoot>
    </table>

    <div style="height:16px;"></div>

    <!-- ── EXPENSES ───────────────────────────────────────────────────────── -->
    <table class="table table-sm table-bordered align-middle mb-0 pl-tbl">
        <thead>
            <tr class="pl-section-head expense-head">
                <th colspan="3">EXPENSES</th>
            </tr>
            <tr class="pl-col-head">
                <th style="width:60px;">Code</th>
                <th>Account</th>
                <th class="text-end" style="width:150px;">Amount</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($Expense)): ?>
            <?php foreach ($Expense as $r):
                $net = (float)$r->NetAmount;
                $muted = abs($net) < 0.005;
            ?>
            <tr <?php echo $muted ? 'class="text-muted"' : ''; ?>>
                <td><code style="font-size:.75rem;color:#7c3aed;"><?php echo htmlspecialchars($r->LedgerCode ?? ''); ?></code></td>
                <td><?php echo htmlspecialchars($r->LedgerName); ?></td>
                <td class="text-end fw-semibold <?php echo $net < 0 ? 'text-success' : 'text-danger'; ?>">
                    <?php echo _plFmt($net, $cur); ?>
                    <?php if ($net < 0): ?><small class="ms-1" style="font-size:.7rem;">(Cr)</small><?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="3" class="text-center text-muted py-3" style="font-size:.82rem;">No expense accounts found</td></tr>
        <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="pl-subtotal expense-subtotal">
                <td colspan="2" class="fw-semibold">TOTAL EXPENSES</td>
                <td class="text-end fw-bold text-danger"><?php echo $cur . ' ' . smartDecimal(max(0, $TotalExpense)); ?></td>
            </tr>
        </tfoot>
    </table>

    <div style="height:16px;"></div>

    <!-- ── NET PROFIT / LOSS ──────────────────────────────────────────────── -->
    <table class="table table-sm table-bordered align-middle mb-0 pl-tbl">
        <tbody>
            <tr class="pl-net <?php echo $isLoss ? 'pl-net-loss' : 'pl-net-profit'; ?>">
                <td class="fw-bold" style="font-size:.95rem;padding:12px 16px;">
                    <?php echo $isLoss ? 'NET LOSS' : 'NET PROFIT'; ?>
                    <div class="text-muted fw-normal" style="font-size:.72rem;margin-top:2px;">
                        Income <?php echo $TotalIncome >= $TotalExpense ? '−' : '+'; ?> Expenses
                    </div>
                </td>
                <td class="text-end fw-bold" style="font-size:1rem;padding:12px 16px;width:150px;
                    <?php echo $isLoss ? 'color:#dc2626;' : 'color:#16a34a;'; ?>">
                    <?php echo $cur . ' ' . smartDecimal(abs($NetProfit)); ?>
                    <?php if ($isLoss): ?><div style="font-size:.72rem;font-weight:400;">(Loss)</div><?php endif; ?>
                </td>
            </tr>
        </tbody>
    </table>

</div>
