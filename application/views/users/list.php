<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
$SerialNumber = $SerialNumber ?? 0;
$showSerial   = ($JwtData->GenSettings->SerialNoDisplay ?? 0) == 1;

if (!empty($DataLists)):
    foreach ($DataLists as $list):
        $SerialNumber++;
        $isActive  = (int)($list->IsActive ?? 1);
        $uid       = (int)$list->UserUID;
        $updatedOn = $list->UpdatedOn ?? null;
        if ($updatedOn) {
            $secondsAgo = time() - strtotime($updatedOn);
            $within24h  = $secondsAgo < 86400;
            if ($within24h) {
                if ($secondsAgo < 60)       $agoText = 'just now';
                elseif ($secondsAgo < 3600) $agoText = (int)($secondsAgo / 60) . ' min' . ((int)($secondsAgo / 60) > 1 ? 's' : '') . ' ago';
                else                        $agoText = (int)($secondsAgo / 3600) . ' hr' . ((int)($secondsAgo / 3600) > 1 ? 's' : '') . ' ago';
            } else {
                $within24h = false;
                $agoText   = '';
            }
        }
?>
    <tr>

        <td style="width:36px">
            <div class="form-check mb-0">
                <input class="form-check-input table-chkbox userCheck" type="checkbox" value="<?php echo $uid; ?>">
            </div>
        </td>

        <td class="table-serialno <?php echo $showSerial ? '' : 'd-none'; ?>" style="width:44px">
            <span class="text-muted" style="font-size:.78rem;"><?php echo $SerialNumber; ?></span>
        </td>

        <!-- Name -->
        <td>
            <div class="fw-semibold" style="font-size:.88rem;">
                <?php echo htmlspecialchars(trim($list->FirstName . ' ' . $list->LastName)); ?>
                <?php if (!empty($list->UserCode)): ?>
                    <span class="text-muted fw-normal" style="font-size:.72rem;">&nbsp;#<?php echo htmlspecialchars($list->UserCode); ?></span>
                <?php endif; ?>
            </div>
            <div class="text-muted" style="font-size:.72rem;">@<?php echo htmlspecialchars($list->UserName); ?></div>
        </td>

        <!-- Email / Mobile -->
        <td>
            <div style="font-size:.82rem;"><?php echo htmlspecialchars($list->EmailAddress ?? '—'); ?></div>
            <?php if (!empty($list->MobileNumber)): ?>
                <div class="text-muted" style="font-size:.72rem;"><?php echo htmlspecialchars($list->MobileNumber); ?></div>
            <?php endif; ?>
        </td>

        <!-- Role -->
        <td>
            <?php if (!empty($list->RoleName)): ?>
                <span class="badge text-bg-light border" style="font-size:.72rem;font-weight:500;">
                    <?php echo htmlspecialchars($list->RoleName); ?>
                </span>
            <?php else: ?>
                <span class="text-muted">—</span>
            <?php endif; ?>
        </td>

        <!-- Status — clickable dropdown toggle -->
        <td>
            <div class="dropdown d-inline-block">
                <span class="badge <?php echo $isActive ? 'bg-label-success' : 'bg-label-secondary'; ?> cursor-pointer"
                      style="font-size:.72rem;" data-bs-toggle="dropdown">
                    <?php echo $isActive ? 'Active' : 'Inactive'; ?>
                    <i class="bx bx-chevron-down" style="font-size:.65rem;"></i>
                </span>
                <ul class="dropdown-menu shadow-sm" style="min-width:150px;font-size:.82rem;">
                    <li>
                        <button class="dropdown-item usr-status-toggle"
                                data-uid="<?php echo $uid; ?>"
                                data-newstatus="<?php echo $isActive ? 0 : 1; ?>">
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

        <!-- Last Updated -->
        <td>
            <?php if ($updatedOn): ?>
                <div style="font-size:.8rem;"><?php echo changeTimeZonefromDateTime($updatedOn, $JwtData->User->Timezone, 2); ?></div>
                <?php if ($within24h): ?>
                    <div style="font-size:.68rem;color:#0d6efd;font-weight:500;"><?php echo $agoText; ?></div>
                <?php endif; ?>
                <div class="text-muted" style="font-size:.7rem;">by <?php echo htmlspecialchars(trim($list->UpdatedBy ?? '—')); ?></div>
            <?php else: ?>
                <span class="text-muted">—</span>
            <?php endif; ?>
        </td>

        <!-- Actions -->
        <td style="width:60px">
            <div class="d-flex align-items-center justify-content-end gap-1">
                <button class="btn btn-icon btn-sm text-warning userEditBtn"
                        data-uid="<?php echo $uid; ?>"
                        title="Edit User">
                    <i class="bx bx-edit"></i>
                </button>
            </div>
        </td>

    </tr>
<?php
    endforeach;
else:
?>
    <tr>
        <td colspan="8">
            <div class="d-flex flex-column align-items-center py-5">
                <img src="/assets/img/elements/no-record-found.png" alt="No Records" class="img-fluid mb-3" style="max-height:140px;object-fit:contain;">
                <span class="text-muted mb-3" style="font-size:.9rem;">No users found</span>
                <button type="button" class="btn btn-primary btn-sm px-4" id="addUserBtnEmpty">
                    <i class="bx bx-plus me-1"></i>Create User
                </button>
            </div>
        </td>
    </tr>
<?php endif; ?>
