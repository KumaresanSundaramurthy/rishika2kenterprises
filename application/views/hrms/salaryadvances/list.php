<?php defined('BASEPATH') or exit('No direct script access allowed');
$cur = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
if (!empty($DataLists)):
    foreach ($DataLists as $row):
        $SerialNumber++;
        $uid       = (int)$row->TablePrimaryUID;
        $settled   = (int)($row->IsSettled ?? 0);
        $badge     = $settled ? 'bg-label-success' : 'bg-label-warning';
        $label     = $settled ? 'Settled' : 'Pending';
        $amount    = (float)($row->AdvanceAmount ?? 0);
        $recovered = $amount - (float)($row->BalancePending ?? $amount);
        $balance   = (float)($row->BalancePending ?? $amount);
        $dt        = !empty($row->AdvanceDate) ? date('d M Y', strtotime($row->AdvanceDate)) : '—';
?>
<tr>
  <td class="text-muted" style="font-size:.8rem;"><?php echo $SerialNumber; ?></td>
  <td>
    <div class="fw-semibold"><?php echo htmlspecialchars($row->EmployeeName ?? ''); ?></div>
    <div class="text-muted" style="font-size:.75rem;"><?php echo htmlspecialchars($row->EmployeeCode ?? ''); ?></div>
  </td>
  <td style="font-size:.875rem;"><?php echo $dt; ?></td>
  <td class="fw-semibold"><?php echo $cur . ' ' . number_format($amount, 2); ?></td>
  <td class="text-success"><?php echo $cur . ' ' . number_format($recovered, 2); ?></td>
  <td class="<?php echo $balance > 0 ? 'text-danger fw-semibold' : 'text-success'; ?>"><?php echo $cur . ' ' . number_format($balance, 2); ?></td>
  <td><span class="badge <?php echo $badge; ?>"><?php echo $label; ?></span></td>
  <td class="text-muted" style="font-size:.8rem;"><?php echo htmlspecialchars($row->Remarks ?? '—'); ?></td>
  <td>
    <?php if (!$settled): ?>
    <div class="d-flex align-items-center gap-1">
      <button class="btn btn-icon btn-sm text-warning adv-edit-btn" data-uid="<?php echo $uid; ?>"
        data-employee="<?php echo (int)($row->EmployeeUID ?? 0); ?>"
        data-date="<?php echo htmlspecialchars($row->AdvanceDate ?? ''); ?>"
        data-amount="<?php echo $amount; ?>"
        data-remarks="<?php echo htmlspecialchars($row->Remarks ?? ''); ?>" title="Edit"><i class="bx bx-edit"></i></button>
      <button class="btn btn-icon btn-sm text-danger adv-delete-btn" data-uid="<?php echo $uid; ?>" title="Delete"><i class="bx bx-trash"></i></button>
    </div>
    <?php else: ?><span class="text-muted" style="font-size:.8rem;">—</span><?php endif; ?>
  </td>
</tr>
<?php endforeach; else: ?>
<tr><td colspan="9" class="text-center text-muted py-4"><i class="bx bx-money-withdraw me-1"></i>No advances found.</td></tr>
<?php endif; ?>
