<?php defined('BASEPATH') or exit('No direct script access allowed');
$showSerial = ($JwtData->GenSettings->SerialNoDisplay ?? 0) == 1;
if (!empty($DataLists)):
    foreach ($DataLists as $row):
        $SerialNumber++;
        $uid      = (int)$row->TablePrimaryUID;
        $isSystem = ($row->OrgUID ?? 0) == 0;
?>
<tr>
  <td class="text-muted <?php echo $showSerial ? '' : 'd-none'; ?>" style="font-size:.8rem;"><?php echo $SerialNumber; ?></td>
  <td>
    <div class="fw-semibold"><?php echo htmlspecialchars($row->DepartmentName ?? ''); ?></div>
  </td>
  <td class="text-muted" style="font-size:.83rem;"><?php echo htmlspecialchars($row->Description ?? '—'); ?></td>
  <td><?php if ($isSystem): ?><span class="badge bg-label-info">System</span><?php else: ?><span class="badge bg-label-secondary">Custom</span><?php endif; ?></td>
  <td>
    <div class="d-flex align-items-center gap-1">
      <button class="btn btn-icon btn-sm text-warning dept-edit-btn"
        data-uid="<?php echo $uid; ?>"
        data-name="<?php echo htmlspecialchars($row->DepartmentName ?? ''); ?>"
        data-desc="<?php echo htmlspecialchars($row->Description ?? ''); ?>"
        data-is-system="<?php echo $isSystem ? 1 : 0; ?>"
        title="<?php echo $isSystem ? 'Edit Description' : 'Edit'; ?>"><i class="bx bx-edit"></i></button>
      <?php if (!$isSystem): ?>
      <button class="btn btn-icon btn-sm text-danger dept-delete-btn" data-uid="<?php echo $uid; ?>" title="Delete"><i class="bx bx-trash"></i></button>
      <?php endif; ?>
    </div>
  </td>
</tr>
<?php endforeach; else: ?>
<tr><td colspan="5" class="text-center text-muted py-4"><i class="bx bx-buildings me-1"></i>No departments found.</td></tr>
<?php endif; ?>
