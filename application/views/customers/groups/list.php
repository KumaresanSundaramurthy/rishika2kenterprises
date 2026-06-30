<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
$currency = $JwtData->GenSettings->CurrenySymbol ?? '₹';
$dec      = (int)($JwtData->GenSettings->DecimalPoints ?? 2);

if (!empty($DataLists)):
    foreach ($DataLists as $list):
        $SerialNumber++;
        $uid        = (int)$list->GroupUID;
        $isActive   = (int)($list->IsActive ?? 1);
        $groupType  = htmlspecialchars($list->GroupType ?? 'Business Group');
        $receivable = (float)($list->TotalReceivable ?? 0);
        $payable    = (float)($list->TotalPayable    ?? 0);
        $members    = (int)($list->MemberCount ?? 0);
        $name       = htmlspecialchars($list->GroupName ?? '—');
        $code       = htmlspecialchars($list->GroupCode ?? '—');
        $primary    = htmlspecialchars($list->PrimaryName ?? '—');

        $typeColors = [
            'Business Group'  => ['bg' => '#f0efff', 'c' => '#696cff'],
            'Branch Group'    => ['bg' => '#e0f7fa', 'c' => '#0097a7'],
            'Family Group'    => ['bg' => '#fce8ff', 'c' => '#9333ea'],
            'Corporate Group' => ['bg' => '#e8f5e9', 'c' => '#2e7d32'],
            'Dealer Network'  => ['bg' => '#fff3e0', 'c' => '#ef6c00'],
            'Franchise Group' => ['bg' => '#fce4ec', 'c' => '#c62828'],
            'Custom'          => ['bg' => '#f5f5f5', 'c' => '#616161'],
        ];
        $tc = $typeColors[$list->GroupType ?? ''] ?? ['bg' => '#f5f5f5', 'c' => '#616161'];
?>
    <tr>
        <td>
            <div class="form-check mb-0">
                <input class="form-check-input table-chkbox grpCheck" type="checkbox" value="<?php echo $uid; ?>">
            </div>
        </td>
        <td>
            <div class="fw-semibold">
                <a href="/customers/groupDetail/<?php echo $uid; ?>" class="text-body" style="text-decoration:none;"><?php echo $name; ?></a>
            </div>
            <?php if ($list->PrimaryName): ?>
            <div class="text-muted" style="font-size:.74rem;">
                <i class="bx bx-star" style="color:#f59e0b;font-size:.72rem;"></i> <?php echo $primary; ?>
            </div>
            <?php endif; ?>
        </td>
        <td>
            <?php if ($list->GroupCode): ?>
                <span style="font-size:.8rem;font-family:monospace;"><?php echo $code; ?></span>
            <?php else: ?><span class="text-muted">—</span><?php endif; ?>
        </td>
        <td>
            <span class="badge" style="background:<?php echo $tc['bg']; ?>;color:<?php echo $tc['c']; ?>;font-size:.72rem;font-weight:600;"><?php echo $groupType; ?></span>
        </td>
        <td class="text-center">
            <span class="badge bg-label-primary" style="font-size:.78rem;"><?php echo $members; ?></span>
        </td>
        <td>
            <?php if (!empty($list->ContactPerson)): ?><div style="font-size:.82rem;"><?php echo htmlspecialchars($list->ContactPerson); ?></div><?php endif; ?>
            <?php if (!empty($list->Mobile)): ?><div class="text-muted" style="font-size:.74rem;"><?php echo htmlspecialchars($list->Mobile); ?></div><?php endif; ?>
            <?php if (empty($list->ContactPerson) && empty($list->Mobile)): ?><span class="text-muted">—</span><?php endif; ?>
        </td>
        <td class="text-end">
            <?php if ($receivable > 0): ?>
                <div style="font-size:.82rem;font-weight:600;color:#28a745;"><?php echo $currency . ' ' . number_format($receivable, $dec); ?></div>
                <div class="text-muted" style="font-size:.7rem;">Receivable</div>
            <?php elseif ($payable > 0): ?>
                <div style="font-size:.82rem;font-weight:600;color:#dc3545;"><?php echo $currency . ' ' . number_format($payable, $dec); ?></div>
                <div class="text-muted" style="font-size:.7rem;">Payable</div>
            <?php else: ?>
                <span class="text-muted" style="font-size:.8rem;">Settled</span>
            <?php endif; ?>
        </td>
        <td>
            <div class="dropdown d-inline-block">
                <span class="badge <?php echo $isActive ? 'bg-label-success' : 'bg-label-danger'; ?> cursor-pointer" style="font-size:.68rem;" data-bs-toggle="dropdown">
                    <?php echo $isActive ? 'Active' : 'Inactive'; ?> <i class="bx bx-chevron-down" style="font-size:.65rem;"></i>
                </span>
                <ul class="dropdown-menu shadow-sm" style="min-width:150px;font-size:.82rem;">
                    <li>
                        <button class="dropdown-item grp-status-toggle" data-uid="<?php echo $uid; ?>" data-newstatus="<?php echo $isActive ? 0 : 1; ?>">
                            <?php if ($isActive): ?>
                                <i class="bx bx-x-circle me-2 text-danger"></i>Mark Inactive
                            <?php else: ?>
                                <i class="bx bx-check-circle me-2 text-success"></i>Mark Active
                            <?php endif; ?>
                        </button>
                    </li>
                </ul>
            </div>
        </td>
        <td>
            <div class="d-flex align-items-center gap-1">
                <a href="/customers/groupDetail/<?php echo $uid; ?>" class="btn btn-icon btn-sm text-info" title="View Detail"><i class="bx bx-show fs-5"></i></a>
                <button type="button" class="btn btn-icon btn-sm text-warning grp-edit-btn" data-uid="<?php echo $uid; ?>" title="Edit"><i class="bx bx-edit fs-5"></i></button>
                <button type="button" class="btn btn-icon btn-sm text-danger grp-delete-btn" data-uid="<?php echo $uid; ?>" title="Delete"><i class="bx bx-trash fs-5"></i></button>
            </div>
        </td>
    </tr>
<?php
    endforeach;
else:
?>
    <tr>
        <td colspan="9">
            <div class="d-flex flex-column align-items-center justify-content-center py-5 text-muted">
                <i class="bx bxs-layer mb-2" style="font-size:3rem;color:#e2e8f0;"></i>
                <div style="font-size:.9rem;">No customer groups found.</div>
                <button type="button" class="btn btn-sm btn-primary mt-2 btn-new-group"><i class="bx bx-plus me-1"></i>Create First Group</button>
            </div>
        </td>
    </tr>
<?php endif; ?>
