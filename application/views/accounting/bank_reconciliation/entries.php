<?php defined('BASEPATH') or exit('No direct script access allowed');
/** @var array $Entries */ $Entries = $Entries ?? [];
$cur     = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
$dateFmt = $JwtData->GenSettings->ListDateFormat ?? 'd M Y';

$refBadge = [
    'Invoice'          => 'bg-label-primary',
    'Purchase'         => 'bg-label-warning',
    'Payment-In'       => 'bg-label-success',
    'Payment-Out'      => 'bg-label-success',
    'Manual'           => 'bg-label-purple',
    'Expense'          => 'bg-label-warning',
    'IndirectIncome'   => 'bg-label-success',
    'Reversal-Invoice' => 'bg-label-danger',
    'Reversal-Purchase'=> 'bg-label-danger',
    'Reversal-Manual'  => 'bg-label-danger',
    'Recurring'        => 'bg-label-info',
];

if (!empty($Entries)):
    foreach ($Entries as $en):
        $uid    = (int)$en->EntryUID;
        $isDr   = $en->TransactionType === 'Debit';
        $amt    = (float)$en->Amount;
        $drAmt  = $isDr ? $amt : 0.0;
        $crAmt  = $isDr ? 0.0  : $amt;
        $ref    = $en->ReferenceType ?? '';
        $bCls   = $refBadge[$ref] ?? 'bg-label-secondary';
?>
<tr data-uid="<?php echo $uid; ?>"
    data-dr="<?php echo smartDecimal($drAmt); ?>"
    data-cr="<?php echo smartDecimal($crAmt); ?>"
    class="<?php echo $en->IsReconciled ? 'recon-row-cleared' : ''; ?>">
    <td class="text-center" style="width:44px;">
        <input type="checkbox" class="recon-chk form-check-input"
               value="<?php echo $uid; ?>"
               <?php echo $en->IsReconciled ? 'checked' : ''; ?>>
    </td>
    <td style="white-space:nowrap;font-size:var(--r2k-text-82);"><?php echo date($dateFmt, strtotime($en->JournalDate)); ?></td>
    <td>
        <code style="font-size:var(--r2k-text-78);color:#7c3aed;"><?php echo htmlspecialchars($en->JournalNo ?? ''); ?></code>
    </td>
    <td>
        <span class="badge <?php echo $bCls; ?>" style="font-size:var(--r2k-text-xs);"><?php echo htmlspecialchars($ref); ?></span>
        <?php if (!empty($en->ReferenceNo)): ?>
        <div class="text-muted" style="font-size:var(--r2k-text-70);"><?php echo htmlspecialchars($en->ReferenceNo); ?></div>
        <?php endif; ?>
    </td>
    <td class="text-muted" style="font-size:var(--r2k-text-md);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
        title="<?php echo htmlspecialchars($en->Narration ?? ''); ?>">
        <?php echo htmlspecialchars($en->Particulars ?: ($en->Narration ?? '—')); ?>
    </td>
    <td class="text-end fw-semibold text-success" style="font-size:var(--r2k-text-82);white-space:nowrap;">
        <?php echo $isDr ? ($cur . ' ' . smartDecimal($amt)) : '—'; ?>
    </td>
    <td class="text-end fw-semibold text-danger" style="font-size:var(--r2k-text-82);white-space:nowrap;">
        <?php echo !$isDr ? ($cur . ' ' . smartDecimal($amt)) : '—'; ?>
    </td>
</tr>
<?php endforeach; else: ?>
<tr class="r2k-empty-row">
    <td colspan="7">
        <div class="r2k-list-empty"><div class="r2k-list-empty-inner">
            <div class="r2k-empty-icon icon-info"><i class="bx bx-bank"></i></div>
            <div class="r2k-empty-title">No unreconciled entries</div>
            <div class="r2k-empty-sub">All transactions have been reconciled, or none match the current filter.</div>
        </div></div>
    </td>
</tr>
<?php endif; ?>
