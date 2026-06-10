<?php defined('BASEPATH') or exit('No direct script access allowed');
$cur = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
$statusColors = ['Draft'=>'secondary','Processed'=>'primary','Paid'=>'success'];
$months = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
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
    <div class="fw-semibold"><?php echo $period; ?></div>
    <div class="text-muted" style="font-size:.75rem;"><?php echo !empty($row->ProcessedOn) ? date('d M Y', strtotime($row->ProcessedOn)) : '—'; ?></div>
  </td>
  <td class="text-center fw-semibold"><?php echo (int)($row->EmployeeCount ?? 0); ?></td>
  <td><?php echo $cur . ' ' . number_format((float)($row->TotalGross ?? 0), 2); ?></td>
  <td class="text-danger"><?php echo $cur . ' ' . number_format((float)($row->TotalDeductions ?? 0), 2); ?></td>
  <td class="text-success fw-semibold"><?php echo $cur . ' ' . number_format((float)($row->TotalNetPayable ?? 0), 2); ?></td>
  <td><span class="badge bg-label-<?php echo $badge; ?>"><?php echo $status; ?></span></td>
  <td class="text-muted" style="font-size:.8rem;"><?php echo htmlspecialchars($row->ProcessedByName ?? '—'); ?></td>
  <td>
    <div class="d-flex align-items-center gap-1">
      <a href="/payroll/detail/<?php echo $uid; ?>" class="btn btn-icon btn-sm text-primary" title="View Detail"><i class="bx bx-show"></i></a>
      <?php if ($status !== 'Paid'): ?>
      <a href="/payroll/process?id=<?php echo $uid; ?>" class="btn btn-icon btn-sm text-warning" title="Edit/Reprocess"><i class="bx bx-edit"></i></button>
      <?php endif; ?>
      <?php if ($status === 'Processed'): ?>
      <button class="btn btn-icon btn-sm text-success prl-mark-paid" data-uid="<?php echo $uid; ?>" title="Mark as Paid"><i class="bx bx-check-circle"></i></button>
      <?php endif; ?>
    </div>
  </td>
</tr>
<?php endforeach; else: ?>
<tr><td colspan="9" class="text-center text-muted py-4"><i class="bx bx-calculator me-1"></i>No payrolls found. <a href="/payroll/process">Process your first payroll</a>.</td></tr>
<?php endif; ?>
