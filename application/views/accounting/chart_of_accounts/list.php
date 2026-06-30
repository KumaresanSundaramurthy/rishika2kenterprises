<?php defined('BASEPATH') or exit('No direct script access allowed');
/** @var int $SerialNumber */ $SerialNumber = $SerialNumber ?? 0;
$cur     = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
$dateFmt = $JwtData->GenSettings->ListDateFormat ?? 'd M Y';

$typeBadge = [
    'Asset'     => 'bg-label-primary',
    'Liability' => 'bg-label-danger',
    'Income'    => 'bg-label-success',
    'Expense'   => 'bg-label-warning',
    'Customer'  => 'bg-label-info',
    'Vendor'    => 'bg-label-secondary',
    'Employee'  => 'bg-label-secondary',
    'Bank'      => 'bg-label-primary',
    'Cash'      => 'bg-label-success',
];

$isSystem = function($code) {
    return strpos($code ?? '', 'SYS-') === 0;
};

if (!empty($DataLists)):
    foreach ($DataLists as $row):
        $SerialNumber++;
        $uid        = (int)$row->LedgerUID;
        $badge      = $typeBadge[$row->LedgerType ?? ''] ?? 'bg-label-secondary';
        $balClass   = ($row->CurrentBalanceType ?? 'Debit') === 'Debit' ? 'text-success' : 'text-danger';
        $isActive   = (int)($row->IsActive ?? 1);
        $isSys      = $isSystem($row->LedgerCode ?? '');
?>
<tr data-uid="<?php echo $uid; ?>"
    data-code="<?php echo htmlspecialchars($row->LedgerCode ?? ''); ?>"
    data-name="<?php echo htmlspecialchars($row->LedgerName ?? ''); ?>"
    data-type="<?php echo htmlspecialchars($row->LedgerType ?? ''); ?>"
    data-parent="<?php echo (int)($row->ParentLedgerUID ?? 0); ?>"
    data-opening="<?php echo (float)($row->OpeningBalance ?? 0); ?>"
    data-openingtype="<?php echo htmlspecialchars($row->OpeningBalanceType ?? 'Debit'); ?>">
    <td class="text-muted" style="font-size:.78rem;"><?php echo $SerialNumber; ?></td>
    <td>
        <code style="font-size:.78rem;color:#7c3aed;"><?php echo htmlspecialchars($row->LedgerCode ?? ''); ?></code>
        <?php if ($isSys): ?>
        <span class="badge bg-label-secondary ms-1" style="font-size:.62rem;">System</span>
        <?php endif; ?>
    </td>
    <td>
        <div class="fw-semibold" style="font-size:.84rem;"><?php echo htmlspecialchars($row->LedgerName ?? ''); ?></div>
        <?php if (!empty($row->ParentLedgerName)): ?>
        <div class="text-muted" style="font-size:.72rem;"><i class="bx bx-subdirectory-right me-1"></i><?php echo htmlspecialchars($row->ParentLedgerName); ?></div>
        <?php endif; ?>
    </td>
    <td><span class="badge <?php echo $badge; ?>" style="font-size:.72rem;"><?php echo htmlspecialchars($row->LedgerType ?? ''); ?></span></td>
    <td class="text-muted" style="font-size:.8rem;"><?php echo htmlspecialchars($row->ParentLedgerName ?? '—'); ?></td>
    <td class="text-end fw-semibold <?php echo $balClass; ?>" style="font-size:.84rem;">
        <?php echo $cur . ' ' . number_format((float)($row->CurrentBalance ?? 0), 2); ?>
        <div class="text-muted" style="font-size:.68rem;font-weight:400;"><?php echo htmlspecialchars($row->CurrentBalanceType ?? 'Debit'); ?></div>
    </td>
    <td class="text-center">
        <span class="badge <?php echo $isActive ? 'bg-label-success' : 'bg-label-danger'; ?>" style="font-size:.7rem;">
            <?php echo $isActive ? 'Active' : 'Inactive'; ?>
        </span>
    </td>
    <td>
        <div class="d-flex align-items-center justify-content-end gap-1">
            <?php if (!$isSys): ?>
            <button class="btn btn-icon btn-sm text-warning coa-edit-btn" title="Edit"><i class="bx bx-edit"></i></button>
            <div class="dropdown">
                <button class="trans-actions-btn" type="button" data-bs-toggle="dropdown"><i class="bx bx-dots-vertical-rounded fs-5"></i></button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:.82rem;min-width:160px;">
                    <li>
                        <button class="dropdown-item coa-toggle-btn"
                            data-uid="<?php echo $uid; ?>"
                            data-newstatus="<?php echo $isActive ? 0 : 1; ?>">
                            <?php if ($isActive): ?>
                                <i class="bx bx-x-circle me-2 text-warning"></i>Deactivate
                            <?php else: ?>
                                <i class="bx bx-check-circle me-2 text-success"></i>Activate
                            <?php endif; ?>
                        </button>
                    </li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <button class="dropdown-item text-danger coa-delete-btn" data-uid="<?php echo $uid; ?>">
                            <i class="bx bx-trash me-2"></i>Delete
                        </button>
                    </li>
                </ul>
            </div>
            <?php else: ?>
            <span class="text-muted" style="font-size:.75rem;">—</span>
            <?php endif; ?>
        </div>
    </td>
</tr>
<?php endforeach; else: ?>
<tr>
    <td colspan="8" style="padding:0;border:none;">
        <div class="d-flex flex-column align-items-center py-5">
            <img src="/assets/img/elements/no-record-found.png" alt="No Records" class="img-fluid mb-3" style="max-height:130px;object-fit:contain;">
            <span class="text-muted" style="font-size:.9rem;">No ledger accounts found</span>
            <button class="btn btn-primary btn-sm mt-3" onclick="$('#btnNewLedger').click()">
                <i class="bx bx-plus me-1"></i>Create First Account
            </button>
        </div>
    </td>
</tr>
<?php endif; ?>
