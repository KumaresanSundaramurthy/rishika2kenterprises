<?php defined('BASEPATH') or exit('No direct script access allowed');
/** @var object $Journal */
$cur     = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
$dateFmt = $JwtData->GenSettings->ListDateFormat ?? 'd M Y';
$lines   = $Journal->Lines ?? [];
$totDr   = 0;
$totCr   = 0;
?>
<!-- Journal Header Info -->
<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="text-muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.3px;">Journal #</div>
        <div class="fw-semibold" style="color:#7c3aed;"><?php echo htmlspecialchars($Journal->JournalNo ?? ''); ?></div>
    </div>
    <div class="col-md-3">
        <div class="text-muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.3px;">Date</div>
        <div class="fw-semibold"><?php echo !empty($Journal->JournalDate) ? date($dateFmt, strtotime($Journal->JournalDate)) : '—'; ?></div>
    </div>
    <div class="col-md-3">
        <div class="text-muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.3px;">Reference Type</div>
        <div class="fw-semibold"><?php echo htmlspecialchars($Journal->ReferenceType ?? '—'); ?></div>
    </div>
    <div class="col-md-3">
        <div class="text-muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.3px;">Reference #</div>
        <div class="fw-semibold"><?php echo htmlspecialchars($Journal->ReferenceNo ?? '—'); ?></div>
    </div>
    <?php if (!empty($Journal->Narration)): ?>
    <div class="col-12">
        <div class="text-muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.3px;">Narration</div>
        <div style="font-size:.84rem;"><?php echo htmlspecialchars($Journal->Narration); ?></div>
    </div>
    <?php endif; ?>
</div>

<hr class="my-3">

<!-- Journal Lines Table -->
<div class="table-responsive">
    <table class="table table-sm table-bordered align-middle mb-0" style="font-size:.82rem;">
        <thead class="r2k-thead">
            <tr>
                <th>Account</th>
                <th style="width:90px;">Type</th>
                <th>Particulars</th>
                <th class="text-end" style="width:120px;">Debit (Dr)</th>
                <th class="text-end" style="width:120px;">Credit (Cr)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lines as $ln):
                $isDr = $ln->TransactionType === 'Debit';
                $amt  = (float)$ln->Amount;
                if ($isDr) $totDr += $amt; else $totCr += $amt;
            ?>
            <tr>
                <td>
                    <div class="fw-semibold"><?php echo htmlspecialchars($ln->LedgerName ?? ''); ?></div>
                    <code style="font-size:.72rem;color:#7c3aed;"><?php echo htmlspecialchars($ln->LedgerCode ?? ''); ?></code>
                    <span class="text-muted" style="font-size:.7rem;"> · <?php echo htmlspecialchars($ln->LedgerType ?? ''); ?></span>
                </td>
                <td>
                    <span class="badge <?php echo $isDr ? 'bg-label-success' : 'bg-label-danger'; ?>" style="font-size:.7rem;">
                        <?php echo $ln->TransactionType; ?>
                    </span>
                </td>
                <td class="text-muted"><?php echo htmlspecialchars($ln->Particulars ?? ''); ?></td>
                <td class="text-end fw-semibold text-success">
                    <?php echo $isDr ? ($cur . ' ' . number_format($amt, 2)) : '—'; ?>
                </td>
                <td class="text-end fw-semibold text-danger">
                    <?php echo !$isDr ? ($cur . ' ' . number_format($amt, 2)) : '—'; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="background:#f8f5ff;font-weight:600;">
                <td colspan="3" class="text-end text-muted" style="font-size:.8rem;">Totals</td>
                <td class="text-end text-success"><?php echo $cur . ' ' . number_format($totDr, 2); ?></td>
                <td class="text-end text-danger"><?php echo $cur . ' ' . number_format($totCr, 2); ?></td>
            </tr>
        </tfoot>
    </table>
</div>

<?php
$isBalanced = abs($totDr - $totCr) < 0.01;
$balClass   = $isBalanced ? 'alert-success' : 'alert-danger';
$balMsg     = $isBalanced
    ? '<i class="bx bx-check-circle me-1"></i>Journal is balanced (Dr = Cr)'
    : '<i class="bx bx-error me-1"></i>Journal is NOT balanced — Dr: ' . number_format($totDr,2) . ' / Cr: ' . number_format($totCr,2);
?>
<div class="alert <?php echo $balClass; ?> d-flex align-items-center py-2 px-3 mt-3 mb-0" style="font-size:.8rem;">
    <?php echo $balMsg; ?>
</div>
