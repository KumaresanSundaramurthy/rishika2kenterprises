<?php defined('BASEPATH') or exit('No direct script access allowed');
$cur = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
$months = ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$statusColors = ['Draft'=>'secondary','Processed'=>'primary','Paid'=>'success'];
if (!empty($DataLists)):
    foreach ($DataLists as $row):
        $SerialNumber++;
        $uid    = (int)$row->TablePrimaryUID;
        $status = $row->PayrollStatus ?? 'Draft';
        $badge  = $statusColors[$status] ?? 'secondary';
        $mo     = (int)($row->PayrollMonth ?? 0);
        $yr     = (int)($row->PayrollYear ?? 0);
        $period = ($months[$mo] ?? '—') . ' ' . ($yr ?: '');
?>
<tr>
  <td class="text-muted" style="font-size:.8rem;"><?php echo $SerialNumber; ?></td>
  <td>
    <div class="fw-semibold"><?php echo htmlspecialchars($row->EmployeeName ?? ''); ?></div>
    <div class="text-muted" style="font-size:.75rem;"><?php echo htmlspecialchars($row->EmployeeCode ?? ''); ?></div>
  </td>
  <td class="fw-semibold"><?php echo $period; ?></td>
  <td><?php echo $cur . ' ' . number_format((float)($row->GrossSalary ?? 0), 2); ?></td>
  <td class="text-danger"><?php echo $cur . ' ' . number_format((float)($row->TotalDeductions ?? 0), 2); ?></td>
  <td class="text-success fw-semibold"><?php echo $cur . ' ' . number_format((float)($row->NetPayable ?? 0), 2); ?></td>
  <td><span class="badge bg-label-<?php echo $badge; ?>"><?php echo $status; ?></span></td>
  <td>
    <div class="d-flex align-items-center gap-1">
      <a href="/payslips/view/<?php echo $uid; ?>" class="btn btn-icon btn-sm text-primary" title="View"><i class="bx bx-file"></i></a>
      <a href="/payslips/print/<?php echo $uid; ?>" class="btn btn-icon btn-sm text-secondary" title="Print" target="_blank"><i class="bx bx-printer"></i></a>
    </div>
  </td>
</tr>
<?php endforeach; else: ?>
<tr><td colspan="8" class="text-center text-muted py-4"><i class="bx bx-file me-1"></i>No payslips found.</td></tr>
<?php endif; ?>
