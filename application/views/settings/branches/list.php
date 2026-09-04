<?php defined('BASEPATH') or exit('No direct script access allowed');
$showSerial = ($JwtData->GenSettings->SerialNoDisplay ?? 0) == 1;
if (!empty($DataLists)):
    foreach ($DataLists as $row):
        $SerialNumber++;
        $uid          = (int)$row->BranchUID;
        $isHeadOffice = (bool)(int)$row->IsHeadOffice;
        $isActive     = (bool)(int)$row->IsActive;
        $isWarehouse  = (bool)(int)$row->IsWarehouse;
        $isDispatch   = (bool)(int)$row->IsDispatchPoint;
        $isSales      = (bool)(int)$row->IsSalesPoint;
        $isService    = (bool)(int)$row->IsServiceCenter;
?>
<tr data-uid="<?php echo $uid; ?>" data-hq="<?php echo $isHeadOffice ? 1 : 0; ?>">
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
  <td>
    <span class="badge bg-label-secondary"><?php echo htmlspecialchars($row->BranchCode ?? '—'); ?></span>
    <?php if (!empty($row->BranchTypeName)): ?>
      <div class="text-muted mt-1" style="font-size:.75rem;"><?php echo htmlspecialchars($row->BranchTypeName); ?></div>
    <?php endif; ?>
  </td>
  <td>
    <?php if (!empty($row->ContactPerson)): ?>
      <div style="font-size:.85rem;"><?php echo htmlspecialchars($row->ContactPerson); ?></div>
    <?php endif; ?>
    <?php if (!empty($row->MobileNumber)): ?>
      <div class="text-muted" style="font-size:.8rem;"><?php echo htmlspecialchars($row->MobileNumber); ?></div>
    <?php endif; ?>
  </td>
  <td class="text-muted" style="font-size:.83rem;"><?php echo !empty($row->GSTIN) ? htmlspecialchars($row->GSTIN) : '—'; ?></td>
  <td>
    <?php if ($isActive): ?>
      <span class="badge bg-label-success"><?php echo t('lbl_active', 'Active'); ?></span>
    <?php else: ?>
      <span class="badge bg-label-danger"><?php echo t('lbl_inactive', 'Inactive'); ?></span>
    <?php endif; ?>
  </td>
  <td class="text-center">
    <div class="d-flex align-items-center justify-content-center gap-1">
      <button class="btn btn-icon btn-sm text-warning branch-edit-btn"
        data-uid="<?php echo $uid; ?>"
        data-name="<?php echo htmlspecialchars($row->Name ?? ''); ?>"
        data-code="<?php echo htmlspecialchars($row->BranchCode ?? ''); ?>"
        data-desc="<?php echo htmlspecialchars($row->ShortDescription ?? ''); ?>"
        data-branchtypeuid="<?php echo $row->BranchTypeUID ?? ''; ?>"
        data-contact="<?php echo htmlspecialchars($row->ContactPerson ?? ''); ?>"
        data-mobile="<?php echo htmlspecialchars($row->MobileNumber ?? ''); ?>"
        data-altno="<?php echo htmlspecialchars($row->AlternateNumber ?? ''); ?>"
        data-countrycode="<?php echo htmlspecialchars($row->CountryCode ?? ''); ?>"
        data-countryiso2="<?php echo htmlspecialchars($row->CountryISO2 ?? ''); ?>"
        data-email="<?php echo htmlspecialchars($row->EmailAddress ?? ''); ?>"
        data-pan="<?php echo htmlspecialchars($row->PANNumber ?? ''); ?>"
        data-gstin="<?php echo htmlspecialchars($row->GSTIN ?? ''); ?>"
        data-addr1="<?php echo htmlspecialchars($row->AddressLine1 ?? ''); ?>"
        data-addr2="<?php echo htmlspecialchars($row->AddressLine2 ?? ''); ?>"
        data-pincode="<?php echo htmlspecialchars($row->Pincode ?? ''); ?>"
        data-stateid="<?php echo htmlspecialchars($row->StateId ?? ''); ?>"
        data-statetext="<?php echo htmlspecialchars($row->StateText ?? ''); ?>"
        data-cityid="<?php echo htmlspecialchars($row->CityId ?? ''); ?>"
        data-citytext="<?php echo htmlspecialchars($row->CityText ?? ''); ?>"
        data-landmark="<?php echo htmlspecialchars($row->Landmark ?? ''); ?>"
        data-hq="<?php echo $isHeadOffice ? 1 : 0; ?>"
        data-warehouse="<?php echo $isWarehouse ? 1 : 0; ?>"
        data-dispatch="<?php echo $isDispatch ? 1 : 0; ?>"
        data-sales="<?php echo $isSales ? 1 : 0; ?>"
        data-service="<?php echo $isService ? 1 : 0; ?>"
        title="<?php echo t('vm_edit', 'Edit'); ?>">
        <i class="bx bx-edit"></i>
      </button>
      <div class="dropdown">
        <button class="trans-actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" data-bs-trigger="hover" title="More actions">
          <i class="bx bx-dots-vertical-rounded fs-5"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end r2k-action-menu">
          <?php if (!$isHeadOffice): ?>
          <li>
            <button class="dropdown-item branch-delete-btn"
              data-uid="<?php echo $uid; ?>"
              data-name="<?php echo htmlspecialchars($row->Name ?? ''); ?>">
              <i class="bx bx-trash text-danger me-2"></i><?php echo t('btn_delete', 'Delete'); ?>
            </button>
          </li>
          <li><hr class="dropdown-divider"></li>
          <?php endif; ?>
          <li>
            <button class="dropdown-item branch-toggle-status-btn"
              data-uid="<?php echo $uid; ?>"
              data-active="<?php echo $isActive ? 1 : 0; ?>"
              data-name="<?php echo htmlspecialchars($row->Name ?? ''); ?>">
              <?php if ($isActive): ?>
                <i class="bx bx-x-circle text-danger me-2"></i><?php echo t('lbl_set_inactive', 'Set Inactive'); ?>
              <?php else: ?>
                <i class="bx bx-check-circle text-success me-2"></i><?php echo t('lbl_set_active', 'Set Active'); ?>
              <?php endif; ?>
            </button>
          </li>
          <?php if (!$isHeadOffice): ?>
          <li>
            <button class="dropdown-item branch-set-hq-btn"
              data-uid="<?php echo $uid; ?>">
              <i class="bx bxs-star text-warning me-2"></i><?php echo t('tooltip_set_hq', 'Mark as Head Office'); ?>
            </button>
          </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </td>
</tr>
<?php endforeach; else: ?>
<tr>
  <td colspan="7" class="text-center text-muted py-4">
    <i class="bx bx-store me-1"></i><?php echo t('empty_branches', 'No branches found. Create your first branch.'); ?>
  </td>
</tr>
<?php endif; ?>
