<?php defined('BASEPATH') or exit('No direct script access allowed');
$statusOptions = ['Present','Absent','HalfDay','Leave','Holiday','WeekOff'];
$statusColors  = ['Present'=>'success','Absent'=>'danger','HalfDay'=>'warning','Leave'=>'info','Holiday'=>'secondary','WeekOff'=>'secondary'];
if (!empty($DataLists)):
    foreach ($DataLists as $row):
        $SerialNumber++;
        $uid      = (int)($row->AttendanceUID ?? 0);
        $empUID   = (int)($row->EmployeeUID ?? 0);
        $status   = $row->Status ?? 'Absent';
        $badge    = $statusColors[$status] ?? 'secondary';
        $initials = strtoupper(mb_substr($row->EmployeeName ?? '?', 0, 1));
        $checkIn  = !empty($row->CheckIn)  ? date('H:i', strtotime($row->CheckIn))  : '';
        $checkOut = !empty($row->CheckOut) ? date('H:i', strtotime($row->CheckOut)) : '';
        $hours    = !empty($row->WorkingHours) ? number_format((float)$row->WorkingHours, 1) . 'h' : '—';
?>
<tr data-emp-uid="<?php echo $empUID; ?>" data-att-uid="<?php echo $uid; ?>">
  <td class="text-muted" style="font-size:.8rem;"><?php echo $SerialNumber; ?></td>
  <td>
    <div class="d-flex align-items-center gap-2">
      <div class="avatar avatar-sm flex-shrink-0">
        <span class="avatar-initial rounded-circle" style="background:linear-gradient(135deg,#10b981,#059669);color:#fff;font-size:.75rem;"><?php echo $initials; ?></span>
      </div>
      <div>
        <div class="fw-semibold" style="font-size:.875rem;"><?php echo htmlspecialchars($row->EmployeeName ?? ''); ?></div>
        <div class="text-muted" style="font-size:.75rem;"><?php echo htmlspecialchars($row->EmployeeCode ?? ''); ?></div>
      </div>
    </div>
  </td>
  <td class="text-muted" style="font-size:.83rem;"><?php echo htmlspecialchars($row->DepartmentName ?? '—'); ?></td>
  <td>
    <select class="form-select form-select-sm att-status-select" data-emp-uid="<?php echo $empUID; ?>" style="width:110px;">
      <?php foreach ($statusOptions as $so): ?>
      <option value="<?php echo $so; ?>" <?php echo $so === $status ? 'selected' : ''; ?>><?php echo $so; ?></option>
      <?php endforeach; ?>
    </select>
  </td>
  <td><input type="time" class="form-control form-control-sm att-checkin" data-emp-uid="<?php echo $empUID; ?>" value="<?php echo $checkIn; ?>" style="width:100px;"></td>
  <td><input type="time" class="form-control form-control-sm att-checkout" data-emp-uid="<?php echo $empUID; ?>" value="<?php echo $checkOut; ?>" style="width:100px;"></td>
  <td class="text-muted att-hours"><?php echo $hours; ?></td>
  <td><input type="text" class="form-control form-control-sm att-remarks" data-emp-uid="<?php echo $empUID; ?>" value="<?php echo htmlspecialchars($row->Remarks ?? ''); ?>" placeholder="Remarks" style="width:140px;"></td>
</tr>
<?php endforeach; else: ?>
<tr><td colspan="8" class="text-center text-muted py-4"><i class="bx bx-calendar-check me-1"></i>No employees found.</td></tr>
<?php endif; ?>
