<?php defined('BASEPATH') or exit('No direct script access allowed');
/** @var array       $Lines */
/** @var object      $Ledger */
/** @var float       $OpeningBalance */
/** @var string      $OpeningBalanceType */
/** @var float       $ClosingBalance */
/** @var string      $ClosingBalanceType */
/** @var float       $TotalDebit */
/** @var float       $TotalCredit */
$cur     = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
$dateFmt = $JwtData->GenSettings->ListDateFormat ?? 'd M Y';

function _glFmt($n, $cur) {
    return $cur . ' ' . number_format((float)$n, 2, '.', ',');
}
?>
<table class="table table-sm table-bordered align-middle mb-0" style="font-size:.82rem;">
    <thead class="r2k-thead">
        <tr>
            <th style="width:100px;">Date</th>
            <th style="width:120px;">Journal #</th>
            <th style="width:110px;">Reference</th>
            <th>Particulars</th>
            <th class="text-end" style="width:120px;">Debit</th>
            <th class="text-end" style="width:120px;">Credit</th>
            <th class="text-end" style="width:130px;">Balance</th>
        </tr>
    </thead>
    <tbody>
        <!-- Opening Balance Row -->
        <tr style="background:#f8f5ff;">
            <td colspan="4" class="fw-semibold" style="color:#7c3aed;">
                Opening Balance
                <?php if ($DateFrom): ?>
                <span class="text-muted fw-normal" style="font-size:.75rem;">as of <?php echo date($dateFmt, strtotime($DateFrom)); ?></span>
                <?php endif; ?>
            </td>
            <td class="text-end">—</td>
            <td class="text-end">—</td>
            <td class="text-end fw-semibold" style="color:#7c3aed;">
                <?php echo _glFmt($OpeningBalance, $cur); ?>
                <div style="font-size:.68rem;color:#8b8bcc;"><?php echo htmlspecialchars($OpeningBalanceType); ?></div>
            </td>
        </tr>

        <?php if (empty($Lines)): ?>
        <tr>
            <td colspan="7" class="text-center text-muted py-4">No transactions in this period.</td>
        </tr>
        <?php else: ?>
        <?php foreach ($Lines as $ln):
            $isDebit  = $ln->TransactionType === 'Debit';
            $balClass = ($ln->RunningBalanceType ?? 'Debit') === 'Debit' ? 'text-success' : 'text-danger';
        ?>
        <tr>
            <td style="white-space:nowrap;"><?php echo date($dateFmt, strtotime($ln->JournalDate)); ?></td>
            <td>
                <code style="font-size:.75rem;color:#7c3aed;"><?php echo htmlspecialchars($ln->JournalNo ?? ''); ?></code>
            </td>
            <td class="text-muted" style="font-size:.76rem;">
                <?php echo htmlspecialchars($ln->ReferenceType ?? ''); ?>
                <?php if (!empty($ln->ReferenceNo)): ?>
                <span class="d-block"><?php echo htmlspecialchars($ln->ReferenceNo); ?></span>
                <?php endif; ?>
            </td>
            <td>
                <div><?php echo htmlspecialchars($ln->Particulars ?? $ln->Narration ?? ''); ?></div>
                <?php if (!empty($ln->Narration) && $ln->Narration !== $ln->Particulars): ?>
                <div class="text-muted" style="font-size:.72rem;"><?php echo htmlspecialchars($ln->Narration); ?></div>
                <?php endif; ?>
            </td>
            <td class="text-end text-success fw-semibold">
                <?php echo $isDebit ? _glFmt($ln->Amount, $cur) : '—'; ?>
            </td>
            <td class="text-end text-danger fw-semibold">
                <?php echo !$isDebit ? _glFmt($ln->Amount, $cur) : '—'; ?>
            </td>
            <td class="text-end fw-semibold <?php echo $balClass; ?>">
                <?php echo _glFmt($ln->RunningBalance, $cur); ?>
                <div style="font-size:.68rem;font-weight:400;opacity:.75;"><?php echo htmlspecialchars($ln->RunningBalanceType ?? ''); ?></div>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>

        <!-- Totals Row -->
        <tr style="background:#f8f5ff;font-weight:600;">
            <td colspan="4" class="text-end" style="color:#566a7f;font-size:.8rem;">Period Totals</td>
            <td class="text-end text-success"><?php echo _glFmt($TotalDebit,  $cur); ?></td>
            <td class="text-end text-danger"><?php echo _glFmt($TotalCredit, $cur); ?></td>
            <td class="text-end" style="color:#7c3aed;">
                <?php echo _glFmt($ClosingBalance, $cur); ?>
                <div style="font-size:.68rem;font-weight:400;color:#8b8bcc;"><?php echo htmlspecialchars($ClosingBalanceType); ?></div>
            </td>
        </tr>
    </tbody>
</table>
