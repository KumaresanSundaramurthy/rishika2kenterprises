<?php defined('BASEPATH') or exit('No direct script access allowed');
$days       = ['','Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
$showSerial = ($JwtData->GenSettings->SerialNoDisplay ?? 0) == 1;
$listFmt    = $JwtData->GenSettings->ListDateFormat ?? 'd-m-Y';
if (!empty($DataLists)):
    foreach ($DataLists as $row):
        $SerialNumber++;
        $uid     = (int)$row->TablePrimaryUID;
        $dt      = $row->HolidayDate ?? '';
        $dayNum  = !empty($dt) ? (int)date('w', strtotime($dt)) + 1 : 0;
        $dayName = $days[$dayNum] ?? '—';
        $dtFmt   = !empty($dt) ? date($listFmt, strtotime($dt)) : '—';
        $opt     = (int)($row->IsOptional ?? 0);
?>
<tr>
  <td class="text-muted <?php echo $showSerial ? '' : 'd-none'; ?>" style="font-size:.8rem;"><?php echo $SerialNumber; ?></td>
  <td><div class="fw-semibold"><?php echo htmlspecialchars($row->HolidayName ?? ''); ?></div><?php if (!empty($row->Description)): ?><div class="text-muted" style="font-size:.76rem;"><?php echo htmlspecialchars($row->Description); ?></div><?php endif; ?></td>
  <td class="fw-semibold" style="font-size:.875rem;"><?php echo $dtFmt; ?></td>
  <td><span class="badge bg-label-secondary"><?php echo $dayName; ?></span></td>
  <td><?php echo $opt ? '<span class="badge bg-label-warning">Optional</span>' : '<span class="badge bg-label-success">Mandatory</span>'; ?></td>
  <td>
    <div class="d-flex align-items-center gap-1">
      <button class="btn btn-icon btn-sm text-warning holiday-edit-btn" data-uid="<?php echo $uid; ?>"
        data-name="<?php echo htmlspecialchars($row->HolidayName ?? ''); ?>"
        data-date="<?php echo $dt; ?>"
        data-optional="<?php echo $opt; ?>"
        data-desc="<?php echo htmlspecialchars($row->Description ?? ''); ?>" title="Edit"><i class="bx bx-edit"></i></button>
      <button class="btn btn-icon btn-sm text-danger holiday-delete-btn" data-uid="<?php echo $uid; ?>" title="Delete"><i class="bx bx-trash"></i></button>
    </div>
  </td>
</tr>
<?php endforeach; else: ?>
<tr><td colspan="6" class="text-center text-muted py-4"><i class="bx bx-calendar-event me-1"></i>No holidays found.</td></tr>
<?php endif; ?>
