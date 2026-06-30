<?php defined('BASEPATH') or exit('No direct script access allowed');
/** @var int $SerialNumber */ $SerialNumber = $SerialNumber ?? 0;
$cur     = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
$dateFmt = $JwtData->GenSettings->ListDateFormat ?? 'd M Y';

$refBadge = [
    'Invoice'        => ['bg-label-primary',   'bx-receipt'],
    'Purchase'       => ['bg-label-warning',   'bx-cart'],
    'Payment-In'     => ['bg-label-success',   'bx-money'],
    'Payment-Out'    => ['bg-label-success',   'bx-money'],
    'SalesReturn'    => ['bg-label-info',      'bx-undo'],
    'PurchaseReturn' => ['bg-label-info',      'bx-undo'],
    'Expense'        => ['bg-label-warning',   'bx-wallet'],
    'IndirectIncome' => ['bg-label-success',   'bx-trending-up'],
    'Reversal-Invoice' => ['bg-label-danger',  'bx-undo'],
    'Reversal-Purchase' => ['bg-label-danger', 'bx-undo'],
];

if (!empty($DataLists)):
    foreach ($DataLists as $row):
        $SerialNumber++;
        $uid    = (int)$row->JournalUID;
        $ref    = $row->ReferenceType ?? '';
        $badge  = $refBadge[$ref] ?? ['bg-label-secondary', 'bx-file'];
        $dr     = (float)($row->TotalDebit ?? 0);
        $cr     = (float)($row->TotalCredit ?? 0);
?>
<tr>
    <td class="text-muted" style="font-size:.78rem;"><?php echo $SerialNumber; ?></td>
    <td style="white-space:nowrap;font-size:.82rem;"><?php echo date($dateFmt, strtotime($row->JournalDate)); ?></td>
    <td>
        <code style="font-size:.78rem;color:#7c3aed;"><?php echo htmlspecialchars($row->JournalNo ?? ''); ?></code>
    </td>
    <td>
        <span class="badge <?php echo $badge[0]; ?>" style="font-size:.7rem;">
            <i class="bx <?php echo $badge[1]; ?> me-1"></i><?php echo htmlspecialchars($ref); ?>
        </span>
        <?php if (!empty($row->ReferenceNo)): ?>
        <div class="text-muted" style="font-size:.72rem;margin-top:2px;"><?php echo htmlspecialchars($row->ReferenceNo); ?></div>
        <?php endif; ?>
    </td>
    <td class="text-muted" style="font-size:.8rem;"><?php echo htmlspecialchars($row->Narration ?? '—'); ?></td>
    <td class="text-end fw-semibold text-success" style="font-size:.82rem;"><?php echo $cur . ' ' . number_format($dr, 2); ?></td>
    <td class="text-end fw-semibold text-danger"  style="font-size:.82rem;"><?php echo $cur . ' ' . number_format($cr, 2); ?></td>
    <td class="text-center">
        <span class="badge bg-label-secondary" style="font-size:.7rem;"><?php echo (int)($row->LineCount ?? 0); ?></span>
    </td>
    <td class="text-center">
        <button class="btn btn-icon btn-sm text-warning jl-view-btn" data-uid="<?php echo $uid; ?>" title="View Details">
            <i class="bx bx-show"></i>
        </button>
    </td>
</tr>
<?php endforeach; else: ?>
<tr>
    <td colspan="9" style="padding:0;border:none;">
        <div class="d-flex flex-column align-items-center py-5">
            <img src="/assets/img/elements/no-record-found.png" alt="No Records" class="img-fluid mb-3" style="max-height:130px;object-fit:contain;">
            <span class="text-muted" style="font-size:.9rem;">No journal entries found</span>
        </div>
    </td>
</tr>
<?php endif; ?>
