<?php defined('BASEPATH') or exit('No direct script access allowed');
/** @var array  $Rows */
/** @var float  $GrandDebit */
/** @var float  $GrandCredit */
/** @var float  $TotalObDr */
/** @var float  $TotalObCr */
/** @var int    $FinancialYear */
$cur     = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');

function _tbFmt($n, $cur) {
    if (abs((float)$n) < 0.005) return '<span class="text-muted">—</span>';
    return $cur . ' ' . number_format((float)$n, 2);
}

// Group rows by type
$grouped = [];
foreach ($Rows as $row) {
    $grouped[$row->LedgerType][] = $row;
}
$typeOrder = ['Asset', 'Liability', 'Income', 'Expense', 'Customer', 'Vendor', 'Employee', 'Bank', 'Cash'];
$typeBadge = [
    'Asset'     => 'bg-label-primary',   'Liability' => 'bg-label-danger',
    'Income'    => 'bg-label-success',   'Expense'   => 'bg-label-warning',
    'Customer'  => 'bg-label-info',      'Vendor'    => 'bg-label-secondary',
    'Employee'  => 'bg-label-secondary', 'Bank'      => 'bg-label-primary',
    'Cash'      => 'bg-label-success',
];
$typeOrder = array_filter($typeOrder, fn($t) => isset($grouped[$t]));
foreach (array_keys($grouped) as $t) {
    if (!in_array($t, $typeOrder)) $typeOrder[] = $t;
}
?>
<table class="table table-sm table-bordered align-middle mb-0" style="font-size:.82rem;" id="tbPrintTable">
    <thead class="r2k-thead">
        <tr>
            <th style="width:44px;">#</th>
            <th style="width:120px;">Code</th>
            <th>Account Name</th>
            <th style="width:90px;">Type</th>
            <th class="text-end" style="width:130px;">Opening (Dr)</th>
            <th class="text-end" style="width:130px;">Opening (Cr)</th>
            <th class="text-end" style="width:130px;">Period Debit</th>
            <th class="text-end" style="width:130px;">Period Credit</th>
            <th class="text-end" style="width:130px;">Closing (Dr)</th>
            <th class="text-end" style="width:130px;">Closing (Cr)</th>
        </tr>
    </thead>
    <tbody>
    <?php
    $serial = 0;
    foreach ($typeOrder as $type):
        $items = $grouped[$type] ?? [];
        if (empty($items)) continue;
        $badge = $typeBadge[$type] ?? 'bg-label-secondary';
    ?>
        <!-- Group header -->
        <tr style="background:#f8f5ff;">
            <td colspan="10" style="padding:6px 12px;">
                <span class="badge <?php echo $badge; ?>" style="font-size:.72rem;"><?php echo htmlspecialchars($type); ?></span>
                <span class="text-muted ms-2" style="font-size:.72rem;"><?php echo count($items); ?> account<?php echo count($items) > 1 ? 's' : ''; ?></span>
            </td>
        </tr>
        <?php foreach ($items as $r):
            $serial++;
            $ob    = (float)$r->OpeningBalance;
            $obDr  = ($r->OpeningBalanceType === 'Debit')  ? $ob : 0;
            $obCr  = ($r->OpeningBalanceType === 'Credit') ? $ob : 0;
            $cbDr  = ($r->ClosingBalanceType === 'Debit')  ? (float)$r->ClosingBalance : 0;
            $cbCr  = ($r->ClosingBalanceType === 'Credit') ? (float)$r->ClosingBalance : 0;
            $dr    = (float)$r->PeriodDebit;
            $cr    = (float)$r->PeriodCredit;
            $hasActivity = ($dr + $cr + $ob) > 0;
        ?>
        <tr <?php echo !$hasActivity ? 'class="text-muted"' : ''; ?>>
            <td style="font-size:.75rem;color:#a4afc5;"><?php echo $serial; ?></td>
            <td><code style="font-size:.75rem;color:#7c3aed;"><?php echo htmlspecialchars($r->LedgerCode ?? ''); ?></code></td>
            <td>
                <span class="fw-semibold"><?php echo htmlspecialchars($r->LedgerName ?? ''); ?></span>
                <?php if (strpos($r->LedgerCode ?? '', 'SYS-') === 0): ?>
                <span class="badge bg-label-secondary ms-1" style="font-size:.6rem;">System</span>
                <?php endif; ?>
            </td>
            <td><span class="badge <?php echo $badge; ?>" style="font-size:.68rem;"><?php echo htmlspecialchars($type); ?></span></td>
            <td class="text-end text-success"><?php echo $obDr > 0 ? (_tbFmt($obDr, $cur)) : '—'; ?></td>
            <td class="text-end text-danger"> <?php echo $obCr > 0 ? (_tbFmt($obCr, $cur)) : '—'; ?></td>
            <td class="text-end text-success fw-semibold"><?php echo $dr > 0 ? (_tbFmt($dr, $cur)) : '—'; ?></td>
            <td class="text-end text-danger fw-semibold"> <?php echo $cr > 0 ? (_tbFmt($cr, $cur)) : '—'; ?></td>
            <td class="text-end text-success"><?php echo $cbDr > 0 ? (_tbFmt($cbDr, $cur)) : '—'; ?></td>
            <td class="text-end text-danger"> <?php echo $cbCr > 0 ? (_tbFmt($cbCr, $cur)) : '—'; ?></td>
        </tr>
        <?php endforeach; ?>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
        <!-- Grand Totals -->
        <tr style="background:#ede9ff;font-weight:700;font-size:.84rem;">
            <td colspan="4" class="text-end" style="color:#7c3aed;">Grand Totals</td>
            <td class="text-end text-success"><?php echo $cur . ' ' . number_format($TotalObDr, 2); ?></td>
            <td class="text-end text-danger"> <?php echo $cur . ' ' . number_format($TotalObCr, 2); ?></td>
            <td class="text-end text-success"><?php echo $cur . ' ' . number_format($GrandDebit, 2); ?></td>
            <td class="text-end text-danger"> <?php echo $cur . ' ' . number_format($GrandCredit, 2); ?></td>
            <td colspan="2"></td>
        </tr>
        <!-- Balance Check -->
        <?php $diff = abs($GrandDebit - $GrandCredit); $isBalanced = $diff < 0.01; ?>
        <tr class="<?php echo $isBalanced ? 'table-success' : 'table-danger'; ?>">
            <td colspan="10" class="text-center py-2" style="font-size:.82rem;font-weight:600;">
                <?php if ($isBalanced): ?>
                    <i class="bx bx-check-circle me-1 text-success"></i>
                    Trial Balance is <strong>BALANCED</strong> — Total Debit equals Total Credit
                <?php else: ?>
                    <i class="bx bx-error me-1 text-danger"></i>
                    Trial Balance is <strong>UNBALANCED</strong> — Difference: <?php echo $cur . ' ' . number_format($diff, 2); ?>
                <?php endif; ?>
            </td>
        </tr>
    </tfoot>
</table>
