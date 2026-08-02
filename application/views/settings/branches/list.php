<?php defined('BASEPATH') or exit('No direct script access allowed');
$showSerial = ($JwtData->GenSettings->SerialNoDisplay ?? 0) == 1;
if (!empty($DataLists)):
    foreach ($DataLists as $row):
        $SerialNumber++;
        $uid          = (int)$row->BranchUID;
        $isHeadOffice = (bool)(int)$row->IsHeadOffice;
        $isActive     = (bool)(int)$row->IsActive;
?>
<tr>
  <td class="text-muted <?php echo $showSerial ? '' : 'd-none'; ?>" style="font-size:.8rem;"><?php echo $SerialNumber; ?></td>
  <td>
    <div class="d-flex align-items-center gap-2">
      <span class="fw-semibold"><?php echo htmlspecialchars($row->Name ?? ''); ?></span>
      <?php if ($isHeadOffice): ?>
        <span class="badge bg-label-primary" style="font-size:.7rem;"><?php echo t('lbl_hq', 'HQ'); ?></span>
      <?php endif; ?>
    </div>
    <?php if (!empty($row->ShortDescription)): ?>
      <div class="text-muted" style="font-size:.8rem;"><?php echo htmlspecialchars($row->ShortDescription); ?></div>
    <?php endif; ?>
  </td>
  <td><span class="badge bg-label-secondary"><?php echo htmlspecialchars($row->BranchCode ?? '—'); ?></span></td>
  <td>
    <?php if (!empty($row->ContactPerson)): ?>
      <div style="font-size:.85rem;"><?php echo htmlspecialchars($row->ContactPerson); ?></div>
    <?php endif; ?>
    <?php if (!empty($row->MobileNumber)): ?>
      <div class="text-muted" style="font-size:.8rem;"><?php echo htmlspecialchars($row->MobileNumber); ?></div>
    <?php endif; ?>
  </td>
  <td class="text-muted" style="font-size:.83rem;"><?php echo !empty($row->GSTIN) ? htmlspecialchars($row->GSTIN) : '—'; ?></td>
  <td class="text-muted" style="font-size:.83rem;"><?php echo !empty($row->BranchTypeName) ? htmlspecialchars($row->BranchTypeName) : '—'; ?></td>
  <td>
    <?php if ($isActive): ?>
      <span class="badge bg-label-success"><?php echo t('lbl_active', 'Active'); ?></span>
    <?php else: ?>
      <span class="badge bg-label-danger"><?php echo t('lbl_inactive', 'Inactive'); ?></span>
    <?php endif; ?>
  </td>
  <td>
    <div class="d-flex align-items-center gap-1">
      <button class="btn btn-icon btn-sm text-warning branch-edit-btn"
        data-uid="<?php echo $uid; ?>"
        data-name="<?php echo htmlspecialchars($row->Name ?? ''); ?>"
        data-code="<?php echo htmlspecialchars($row->BranchCode ?? ''); ?>"
        data-desc="<?php echo htmlspecialchars($row->ShortDescription ?? ''); ?>"
        data-contact="<?php echo htmlspecialchars($row->ContactPerson ?? ''); ?>"
        data-mobile="<?php echo htmlspecialchars($row->MobileNumber ?? ''); ?>"
        data-email="<?php echo htmlspecialchars($row->EmailAddress ?? ''); ?>"
        data-gstin="<?php echo htmlspecialchars($row->GSTIN ?? ''); ?>"
        data-addr1="<?php echo htmlspecialchars($row->AddressLine1 ?? ''); ?>"
        data-addr2="<?php echo htmlspecialchars($row->AddressLine2 ?? ''); ?>"
        data-pincode="<?php echo htmlspecialchars($row->Pincode ?? ''); ?>"
        data-hq="<?php echo $isHeadOffice ? 1 : 0; ?>"
        title="<?php echo t('vm_edit', 'Edit'); ?>">
        <i class="bx bx-edit"></i>
      </button>
      <?php if (!$isHeadOffice): ?>
      <button class="btn btn-icon btn-sm text-danger branch-delete-btn"
        data-uid="<?php echo $uid; ?>"
        data-name="<?php echo htmlspecialchars($row->Name ?? ''); ?>"
        title="<?php echo t('btn_delete', 'Delete'); ?>">
        <i class="bx bx-trash"></i>
      </button>
      <?php endif; ?>
    </div>
  </td>
</tr>
<?php endforeach; else: ?>
<tr>
  <td colspan="8" class="text-center text-muted py-4">
    <i class="bx bx-store me-1"></i><?php echo t('empty_branches', 'No branches found. Create your first branch.'); ?>
  </td>
</tr>
<?php endif; ?>
