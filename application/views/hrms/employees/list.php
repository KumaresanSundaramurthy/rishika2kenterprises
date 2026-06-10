<?php defined('BASEPATH') or exit('No direct script access allowed');
$showSN = isset($JwtData->GenSettings->SerialNoDisplay) && $JwtData->GenSettings->SerialNoDisplay == 1;
$cur    = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
$tz     = $JwtData->Org->TimeZone ?? 'Asia/Kolkata';

if (!empty($DataLists)):
    foreach ($DataLists as $row):
        $SerialNumber++;
        $uid  = (int)$row->TablePrimaryUID;
        $name = htmlspecialchars($row->EmployeeName ?? '');
        $code = htmlspecialchars($row->EmployeeCode ?? '');
        $initials = strtoupper(mb_substr($name, 0, 1));
        $statusColors = ['Active' => 'success', 'Resigned' => 'warning', 'Terminated' => 'danger', 'OnLeave' => 'info'];
        $badge = $statusColors[$row->EmployeeStatus] ?? 'secondary';
        $salaryTypeLabel = ['Monthly' => 'Monthly', 'Daily' => 'Daily Wage', 'Hourly' => 'Hourly'];
        $salLabel = $salaryTypeLabel[$row->SalaryType] ?? $row->SalaryType;
        $doj = !empty($row->DateOfJoining) ? date('d M Y', strtotime($row->DateOfJoining)) : '—';
?>
<tr>
  <td><div class="form-check mb-0"><input class="form-check-input table-chkbox empCheck" type="checkbox" value="<?php echo $uid; ?>"></div></td>
  <td class="table-serialno <?php echo $showSN ? '' : 'd-none'; ?>"><span class="text-muted"><?php echo $SerialNumber; ?></span></td>
  <td>
    <div class="d-flex align-items-center gap-2">
      <div class="avatar avatar-sm flex-shrink-0">
        <span class="avatar-initial rounded-circle" style="background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;font-size:.75rem;"><?php echo $initials; ?></span>
      </div>
      <div>
        <div class="fw-semibold" style="font-size:.875rem;"><?php echo $name; ?></div>
        <div class="text-muted" style="font-size:.75rem;"><?php echo $code; ?></div>
      </div>
    </div>
  </td>
  <td><span class="text-muted"><?php echo htmlspecialchars($row->DepartmentName ?? '—'); ?></span></td>
  <td><span class="text-muted"><?php echo htmlspecialchars($row->DesignationName ?? '—'); ?></span></td>
  <td><?php echo htmlspecialchars($row->Mobile ?? '—'); ?></td>
  <td><span class="badge bg-label-secondary"><?php echo $salLabel; ?></span></td>
  <td class="fw-semibold"><?php echo $cur . ' ' . number_format((float)($row->BasicSalary ?? 0), 2); ?></td>
  <td>
    <div class="dropdown d-inline-block">
      <span class="badge bg-label-<?php echo $badge; ?> cursor-pointer" data-bs-toggle="dropdown">
        <?php echo htmlspecialchars($row->EmployeeStatus); ?> <i class="bx bx-chevron-down"></i>
      </span>
      <ul class="dropdown-menu">
        <?php foreach (['Active','Resigned','Terminated','OnLeave'] as $st): if ($st === $row->EmployeeStatus) continue; ?>
        <li><button class="dropdown-item emp-status-change" data-uid="<?php echo $uid; ?>" data-status="<?php echo $st; ?>"><?php echo $st; ?></button></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </td>
  <td class="text-muted" style="font-size:.8rem;"><?php echo $doj; ?></td>
  <td>
    <div class="d-flex align-items-center gap-1">
      <button class="btn btn-icon btn-sm text-warning emp-edit-btn" data-uid="<?php echo $uid; ?>" title="Edit"><i class="bx bx-edit"></i></button>
      <button class="btn btn-icon btn-sm text-danger emp-delete-btn" data-uid="<?php echo $uid; ?>" title="Delete"><i class="bx bx-trash"></i></button>
    </div>
  </td>
</tr>
<?php endforeach; else: ?>
<tr><td colspan="11" class="text-center text-muted py-4"><i class="bx bx-group me-1"></i>No employees found.</td></tr>
<?php endif; ?>
