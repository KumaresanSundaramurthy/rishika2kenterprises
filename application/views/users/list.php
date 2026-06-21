<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
$SerialNumber = $SerialNumber ?? 0;
$showSerial   = ($JwtData->GenSettings->SerialNoDisplay ?? 0) == 1;

$statusBadge = [
    'Active'     => 'bg-label-success',
    'Resigned'   => 'bg-label-warning',
    'Terminated' => 'bg-label-danger',
    'OnLeave'    => 'bg-label-info',
];

if (!empty($DataLists)):
    foreach ($DataLists as $list):
        $SerialNumber++;
        $uid          = (int)$list->UserUID;
        $isActive     = (int)($list->IsActive ?? 1);
        $hasLogin     = (int)($list->HasLoginAccess ?? 0);
        $empStatus    = $list->EmployeeStatus ?? 'Active';
        $updatedOn    = $list->UpdatedOn ?? null;
        $agoText      = ''; $within24h = false;
        if ($updatedOn) {
            $secondsAgo = time() - strtotime($updatedOn);
            $within24h  = $secondsAgo < 86400;
            if ($within24h) {
                if ($secondsAgo < 60)       $agoText = 'just now';
                elseif ($secondsAgo < 3600) $agoText = (int)($secondsAgo / 60) . 'm ago';
                else                        $agoText = (int)($secondsAgo / 3600) . 'h ago';
            }
        }
?>
    <tr>
        <td style="width:36px">
            <div class="form-check mb-0">
                <input class="form-check-input table-chkbox staffCheck" type="checkbox" value="<?php echo $uid; ?>">
            </div>
        </td>

        <td class="table-serialno <?php echo $showSerial ? '' : 'd-none'; ?>" style="width:44px">
            <span class="text-muted" style="font-size:.78rem;"><?php echo $SerialNumber; ?></span>
        </td>

        <!-- Staff Member -->
        <td>
            <div class="d-flex align-items-center gap-2">
                <div class="avatar avatar-sm flex-shrink-0">
                    <span class="avatar-initial rounded-circle bg-label-primary" style="font-size:.72rem;">
                        <?php echo strtoupper(substr($list->FirstName ?? '', 0, 1) . substr($list->LastName ?? '', 0, 1)); ?>
                    </span>
                </div>
                <div>
                    <div class="fw-semibold" style="font-size:.87rem;">
                        <?php echo htmlspecialchars(trim($list->FirstName . ' ' . $list->LastName)); ?>
                    </div>
                    <div class="text-muted" style="font-size:.72rem;">
                        <?php if (!empty($list->EmployeeCode)): ?>
                            <span class="me-1"><?php echo htmlspecialchars($list->EmployeeCode); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($list->UserCode)): ?>
                            <span class="text-muted">#<?php echo htmlspecialchars($list->UserCode); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </td>

        <!-- Department / Designation -->
        <td>
            <?php if (!empty($list->DepartmentName)): ?>
                <div style="font-size:.82rem;"><?php echo htmlspecialchars($list->DepartmentName); ?></div>
            <?php endif; ?>
            <?php if (!empty($list->DesignationName)): ?>
                <div class="text-muted" style="font-size:.72rem;"><?php echo htmlspecialchars($list->DesignationName); ?></div>
            <?php else: ?>
                <?php if (empty($list->DepartmentName)): ?><span class="text-muted">—</span><?php endif; ?>
            <?php endif; ?>
        </td>

        <!-- Contact -->
        <td>
            <?php if (!empty($list->EmailAddress)): ?>
                <div style="font-size:.8rem;"><?php echo htmlspecialchars($list->EmailAddress); ?></div>
            <?php endif; ?>
            <?php if (!empty($list->MobileNumber)): ?>
                <div class="text-muted" style="font-size:.72rem;"><?php echo htmlspecialchars($list->MobileNumber); ?></div>
            <?php elseif (empty($list->EmailAddress)): ?>
                <span class="text-muted">—</span>
            <?php endif; ?>
        </td>

        <!-- Employment Status -->
        <td>
            <div class="dropdown d-inline-block">
                <span class="badge <?php echo $statusBadge[$empStatus] ?? 'bg-label-secondary'; ?> cursor-pointer"
                      style="font-size:.72rem;" data-bs-toggle="dropdown">
                    <?php echo $empStatus === 'OnLeave' ? 'On Leave' : htmlspecialchars($empStatus); ?>
                    <i class="bx bx-chevron-down" style="font-size:.65rem;"></i>
                </span>
                <ul class="dropdown-menu shadow-sm" style="min-width:150px;font-size:.82rem;">
                    <li><button class="dropdown-item staff-status-toggle" data-uid="<?php echo $uid; ?>" data-newstatus="<?php echo $isActive ? 0 : 1; ?>">
                        <?php if ($isActive): ?>
                            <i class="bx bx-x-circle me-2 text-danger"></i>Mark Inactive
                        <?php else: ?>
                            <i class="bx bx-check-circle me-2 text-success"></i>Mark Active
                        <?php endif; ?>
                    </button></li>
                </ul>
            </div>
        </td>

        <!-- Joining Date -->
        <td>
            <?php if (!empty($list->DateOfJoining)): ?>
                <div style="font-size:.82rem;"><?php echo date('d M Y', strtotime($list->DateOfJoining)); ?></div>
            <?php else: ?>
                <span class="text-muted">—</span>
            <?php endif; ?>
        </td>

        <!-- Login Access -->
        <td>
            <?php if ($hasLogin): ?>
                <span class="badge bg-label-primary" style="font-size:.7rem;" title="@<?php echo htmlspecialchars($list->UserName ?? ''); ?>">
                    <i class="bx bx-log-in me-1"></i>Login
                </span>
                <?php if (!empty($list->RoleName)): ?>
                    <div class="text-muted" style="font-size:.7rem;"><?php echo htmlspecialchars($list->RoleName); ?></div>
                <?php endif; ?>
            <?php else: ?>
                <span class="badge bg-label-secondary" style="font-size:.7rem;">Staff Only</span>
            <?php endif; ?>
        </td>

        <!-- Actions -->
        <td style="width:60px">
            <div class="d-flex align-items-center justify-content-end gap-1">
                <button class="btn btn-icon btn-sm text-warning staffEditBtn"
                        data-uid="<?php echo $uid; ?>"
                        title="Edit">
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
        <td colspan="9">
            <div class="d-flex flex-column align-items-center py-5">
                <img src="/assets/img/elements/no-record-found.png" alt="No Records" class="img-fluid mb-3" style="max-height:140px;object-fit:contain;">
                <span class="text-muted mb-3" style="font-size:.9rem;">No staff found</span>
                <button type="button" class="btn btn-primary btn-sm px-4" id="addStaffBtnEmpty">
                    <i class="bx bx-plus me-1"></i>Add Staff
                </button>
            </div>
        </td>
    </tr>
<?php endif; ?>
