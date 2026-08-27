<?php defined('BASEPATH') or exit('No direct script access allowed');
/** @var array $Entries */ $Entries = $Entries ?? [];
$cur     = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
$dateFmt = $JwtData->GenSettings->ListDateFormat ?? 'd M Y';
$dec     = (int)($JwtData->GenSettings->DecimalPoints ?? 2);

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
    <td style="white-space:nowrap;font-size:.82rem;"><?php echo date($dateFmt, strtotime($en->JournalDate)); ?></td>
    <td>
        <code style="font-size:.78rem;color:#7c3aed;"><?php echo htmlspecialchars($en->JournalNo ?? ''); ?></code>
    </td>
    <td>
        <span class="badge <?php echo $bCls; ?>" style="font-size:.68rem;"><?php echo htmlspecialchars($ref); ?></span>
        <?php if (!empty($en->ReferenceNo)): ?>
        <div class="text-muted" style="font-size:.7rem;"><?php echo htmlspecialchars($en->ReferenceNo); ?></div>
        <?php endif; ?>
    </td>
    <td class="text-muted" style="font-size:.8rem;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
        title="<?php echo htmlspecialchars($en->Narration ?? ''); ?>">
        <?php echo htmlspecialchars($en->Particulars ?: ($en->Narration ?? '—')); ?>
    </td>
    <td class="text-end fw-semibold text-success" style="font-size:.82rem;white-space:nowrap;">
        <?php echo $isDr ? ($cur . ' ' . smartDecimal($amt)) : '—'; ?>
    </td>
    <td class="text-end fw-semibold text-danger" style="font-size:.82rem;white-space:nowrap;">
        <?php echo !$isDr ? ($cur . ' ' . smartDecimal($amt)) : '—'; ?>
    </td>
</tr>
<?php endforeach; else: ?>
<tr>
    <td colspan="7" style="padding:0;border:none;">
        <div class="d-flex flex-column align-items-center py-5">
            <img src="/assets/img/elements/no-record-found.png" alt="No Records" class="img-fluid mb-3" style="max-height:110px;object-fit:contain;">
            <span class="text-muted" style="font-size:.9rem;">No transactions found for this account in the selected period</span>
        </div>
    </td>
</tr>
<?php endif; ?>
