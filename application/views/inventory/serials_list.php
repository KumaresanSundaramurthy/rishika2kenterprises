<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
$showSno  = $JwtData->GenSettings->SerialNoDisplay == 1;
$sno      = (int)($SerialNumber ?? 0);
$timezone = $JwtData->User->Timezone ?? 'UTC';

$statusConfig = [
    'Available' => ['label' => 'Available', 'class' => 'bg-label-success',   'icon' => 'bx-check-circle'],
    'Sold'      => ['label' => 'Sold',      'class' => 'bg-label-primary',   'icon' => 'bx-shopping-bag'],
    'Returned'  => ['label' => 'Returned',  'class' => 'bg-label-info',      'icon' => 'bx-undo'],
    'Damaged'   => ['label' => 'Damaged',   'class' => 'bg-label-danger',    'icon' => 'bx-error-circle'],
];

if (!empty($DataLists)):
    foreach ($DataLists as $row):
        $sno++;
        $uid    = (int)$row->SerialUID;
        $status = $row->Status ?? 'Available';
        $cfg    = $statusConfig[$status] ?? ['label' => $status, 'class' => 'bg-label-secondary', 'icon' => 'bx-circle'];
        $createdDate = !empty($row->CreatedAt) ? changeTimeZonefromDateTime($row->CreatedAt, $timezone, 1) : '—';
        $createdTime = !empty($row->CreatedAt) ? changeTimeZonefromDateTime($row->CreatedAt, $timezone, 4) : '';
        $isManual    = ($row->SourceType === 'Manual');
        $canEdit     = in_array($status, ['Available', 'Damaged'], true);
?>
<tr id="sn-row-<?php echo $uid; ?>">

    <td class="<?php echo $showSno ? '' : 'd-none'; ?> table-serialno" style="width:44px;">
        <span class="text-muted" style="font-size:.78rem;"><?php echo $sno; ?></span>
    </td>

    <!-- Serial Number -->
    <td>
        <div class="fw-semibold" style="font-size:.88rem;font-family:monospace;letter-spacing:.03em;">
            <?php echo htmlspecialchars($row->SerialNumber); ?>
        </div>
        <?php if (!empty($row->Notes)): ?>
        <div class="text-muted" style="font-size:.72rem;margin-top:2px;">
            <i class="bx bx-note me-1"></i><?php echo htmlspecialchars($row->Notes); ?>
        </div>
        <?php endif; ?>
    </td>

    <!-- Product -->
    <td>
        <div class="fw-medium" style="font-size:.83rem;"><?php echo htmlspecialchars($row->ItemName); ?></div>
        <?php if (!empty($row->PartNumber)): ?>
        <div class="text-muted" style="font-size:.7rem;">Part# <?php echo htmlspecialchars($row->PartNumber); ?></div>
        <?php endif; ?>
    </td>

    <!-- Status -->
    <td>
        <span class="badge <?php echo $cfg['class']; ?>" style="font-size:.7rem;">
            <i class="bx <?php echo $cfg['icon']; ?> me-1"></i><?php echo $cfg['label']; ?>
        </span>
    </td>

    <!-- Source -->
    <td>
        <?php if ($isManual): ?>
            <span class="badge bg-label-secondary" style="font-size:.7rem;"><i class="bx bx-pencil me-1"></i>Manual</span>
        <?php else: ?>
            <span class="badge bg-label-success" style="font-size:.7rem;"><i class="bx bx-shopping-bag me-1"></i>Purchase</span>
            <?php if (!empty($row->PurchaseTransNo)): ?>
            <div class="text-muted" style="font-size:.7rem;margin-top:2px;"><?php echo htmlspecialchars($row->PurchaseTransNo); ?></div>
            <?php endif; ?>
        <?php endif; ?>
    </td>

    <!-- Transaction -->
    <td>
        <?php if (!empty($row->SaleTransNo)): ?>
            <span class="text-danger" style="font-size:.78rem;font-weight:600;">
                <i class="bx bx-receipt me-1"></i><?php echo htmlspecialchars($row->SaleTransNo); ?>
            </span>
        <?php elseif (!empty($row->ReturnTransNo)): ?>
            <span class="text-info" style="font-size:.78rem;font-weight:600;">
                <i class="bx bx-undo me-1"></i><?php echo htmlspecialchars($row->ReturnTransNo); ?>
            </span>
        <?php else: ?>
            <span class="text-muted">—</span>
        <?php endif; ?>
    </td>

    <!-- Created On -->
    <td>
        <div style="font-size:.78rem;"><?php echo $createdDate; ?></div>
        <?php if ($createdTime): ?>
        <div class="text-muted" style="font-size:.72rem;margin-top:1px;">
            <i class="bx bx-time-five me-1" style="font-size:.7rem;"></i><?php echo $createdTime; ?>
        </div>
        <?php endif; ?>
    </td>

    <!-- Actions -->
    <td>
        <?php if ($canEdit): ?>
        <div class="dropdown">
            <button class="trans-actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bx bx-dots-vertical-rounded fs-5"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end r2k-action-menu">
                <?php if ($status === 'Available'): ?>
                <li>
                    <button class="dropdown-item sn-mark-damaged"
                            data-uid="<?php echo $uid; ?>" data-status="Damaged">
                        <i class="bx bx-error-circle me-2 text-danger"></i>Mark Damaged
                    </button>
                </li>
                <?php endif; ?>
                <?php if ($status === 'Damaged'): ?>
                <li>
                    <button class="dropdown-item sn-mark-available"
                            data-uid="<?php echo $uid; ?>" data-status="Available">
                        <i class="bx bx-check-circle me-2 text-success"></i>Mark Available
                    </button>
                </li>
                <?php endif; ?>
            </ul>
        </div>
        <?php else: ?>
            <span class="text-muted" style="font-size:.75rem;">—</span>
        <?php endif; ?>
    </td>

</tr>
<?php endforeach;
else: ?>
<tr>
    <td colspan="8">
        <div class="d-flex flex-column align-items-center justify-content-center py-5 text-muted">
            <i class="bx bx-barcode" style="font-size:2.5rem;opacity:.3;margin-bottom:8px;"></i>
            <div style="font-size:.85rem;font-weight:600;color:#64748b;">No serial numbers found</div>
            <div style="font-size:.75rem;margin-top:4px;">Add a serial number using the + Add Serial button above.</div>
        </div>
    </td>
</tr>
<?php endif; ?>
