<?php defined('BASEPATH') or exit('No direct script access allowed');
/** @var int $SerialNumber */ $SerialNumber = $SerialNumber ?? 0;
$cur     = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
$dateFmt = $JwtData->GenSettings->ListDateFormat ?? 'd M Y';

$statusBadge = [
    'Requested' => 'bg-label-info',
    'Approved'  => 'bg-label-warning',
    'Settled'   => 'bg-label-success',
    'Rejected'  => 'bg-label-danger',
];

if (!empty($DataLists)):
    foreach ($DataLists as $row):
        $SerialNumber++;
        $uid       = (int)$row->TablePrimaryUID;
        $status    = $row->AdvanceStatus ?? 'Approved'; // fallback for old rows
        $badge     = $statusBadge[$status] ?? 'bg-label-secondary';
        $amount    = (float)($row->AdvanceAmount ?? 0);
        $recovered = $amount - (float)($row->BalancePending ?? $amount);
        $balance   = (float)($row->BalancePending ?? $amount);
        $dt        = !empty($row->AdvanceDate) ? date($dateFmt, strtotime($row->AdvanceDate)) : '—';
        $code      = trim($row->EmployeeCode ?? '');
?>
<tr>
  <td class="text-muted" style="font-size:.8rem;"><?php echo $SerialNumber; ?></td>
  <td>
    <div class="fw-semibold"><?php echo htmlspecialchars($row->EmployeeName ?? ''); ?></div>
    <?php if ($code): ?>
    <div class="text-muted" style="font-size:.75rem;"><?php echo htmlspecialchars($code); ?></div>
    <?php endif; ?>
  </td>
  <td style="font-size:.875rem;"><?php echo $dt; ?></td>
  <td class="fw-semibold"><?php echo $cur . ' ' . number_format($amount, 2); ?></td>
  <td class="text-success"><?php echo $cur . ' ' . number_format($recovered, 2); ?></td>
  <td class="<?php echo $balance > 0 ? 'text-danger fw-semibold' : 'text-success'; ?>"><?php echo $cur . ' ' . number_format($balance, 2); ?></td>
  <td><span class="badge <?php echo $badge; ?>"><?php echo $status; ?></span></td>
  <td class="text-muted" style="font-size:.8rem;"><?php echo htmlspecialchars($row->Remarks ?? '—'); ?></td>
  <td>
    <?php if ($status === 'Requested'): ?>
    <div class="d-flex align-items-center justify-content-center gap-1">
      <button class="btn btn-icon btn-sm text-warning adv-edit-btn"
        data-uid="<?php echo $uid; ?>"
        data-employee="<?php echo (int)($row->EmployeeUID ?? 0); ?>"
        data-date="<?php echo htmlspecialchars($row->AdvanceDate ?? ''); ?>"
        data-amount="<?php echo $amount; ?>"
        data-remarks="<?php echo htmlspecialchars($row->Remarks ?? ''); ?>" title="Edit">
        <i class="bx bx-edit"></i>
      </button>
      <div class="dropdown">
        <button class="trans-actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="bx bx-dots-vertical-rounded fs-5"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:.82rem;min-width:160px;">
          <li>
            <button class="dropdown-item adv-approve-btn" data-uid="<?php echo $uid; ?>">
              <i class="bx bx-check-circle me-2 text-success"></i>Approve
            </button>
          </li>
          <li>
            <button class="dropdown-item adv-reject-btn" data-uid="<?php echo $uid; ?>">
              <i class="bx bx-x-circle me-2 text-warning"></i>Reject
            </button>
          </li>
          <li><hr class="dropdown-divider my-1"></li>
          <li>
            <button class="dropdown-item text-danger adv-delete-btn" data-uid="<?php echo $uid; ?>">
              <i class="bx bx-trash me-2"></i>Delete
            </button>
          </li>
        </ul>
      </div>
    </div>
    <?php elseif ($status === 'Rejected'): ?>
    <div class="d-flex align-items-center justify-content-center gap-1">
      <div class="dropdown">
        <button class="trans-actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="bx bx-dots-vertical-rounded fs-5"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:.82rem;min-width:160px;">
          <li>
            <button class="dropdown-item text-danger adv-delete-btn" data-uid="<?php echo $uid; ?>">
              <i class="bx bx-trash me-2"></i>Delete
            </button>
          </li>
        </ul>
      </div>
    </div>
    <?php else: ?>
      <span class="text-muted ps-2" style="font-size:.8rem;">—</span>
    <?php endif; ?>
  </td>
</tr>
<?php endforeach; else: ?>
<tr>
  <td colspan="9" style="padding:0;border:none;">
    <div class="d-flex flex-column align-items-center py-5">
      <img src="/assets/img/elements/no-record-found.png" alt="No Records" class="img-fluid mb-3" style="max-height:150px;object-fit:contain;">
      <span class="text-muted" style="font-size:.9rem;">No advance records found</span>
    </div>
  </td>
</tr>
<?php endif; ?>
