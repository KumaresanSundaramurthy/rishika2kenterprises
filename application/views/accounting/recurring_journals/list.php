<?php defined('BASEPATH') or exit('No direct script access allowed');
/** @var int $SerialNumber */ $SerialNumber = $SerialNumber ?? 0;
$dateFmt = $JwtData->GenSettings->ListDateFormat ?? 'd M Y';
$today   = date('Y-m-d');

$freqBadge = [
    'Daily'     => 'bg-label-primary',
    'Weekly'    => 'bg-label-info',
    'Monthly'   => 'bg-label-success',
    'Quarterly' => 'bg-label-warning',
    'Yearly'    => 'bg-label-danger',
];

if (!empty($DataLists)):
    foreach ($DataLists as $row):
        $SerialNumber++;
        $uid  = (int)$row->RecurUID;
        $freq = $row->Frequency ?? 'Monthly';
        $fCls = $freqBadge[$freq] ?? 'bg-label-secondary';

        // Compute status
        if (!(int)$row->IsActive) {
            $status = 'Paused'; $sCls = 'bg-label-secondary';
        } elseif ($row->EndDate && $row->NextRunDate > $row->EndDate) {
            $status = 'Ended';    $sCls = 'bg-secondary';
        } elseif ($row->NextRunDate < $today) {
            $status = 'Overdue';  $sCls = 'bg-label-danger';
        } elseif ($row->NextRunDate === $today) {
            $status = 'Due Today';$sCls = 'bg-label-warning';
        } else {
            $status = 'Scheduled';$sCls = 'bg-label-info';
        }

        $isDue     = in_array($status, ['Due Today', 'Overdue'], true);
        $isActive  = (int)$row->IsActive;
        $lastRun   = $row->LastRunDate ? date($dateFmt, strtotime($row->LastRunDate)) : '—';
        $nextRun   = date($dateFmt, strtotime($row->NextRunDate));
        $startDate = date($dateFmt, strtotime($row->StartDate));
        $endDate   = $row->EndDate ? date($dateFmt, strtotime($row->EndDate)) : '—';
        $runs      = (int)$row->TotalRuns;
?>
<tr class="<?php echo $isDue && $isActive ? 'rj-row-due' : ''; ?>">
    <td class="text-muted" style="font-size:.8rem;width:40px;"><?php echo $SerialNumber; ?></td>
    <td>
        <div class="fw-semibold" style="font-size:.88rem;"><?php echo htmlspecialchars($row->Title); ?></div>
        <div class="text-muted" style="font-size:.75rem;max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
             title="<?php echo htmlspecialchars($row->Narration); ?>">
            <?php echo htmlspecialchars($row->Narration); ?>
        </div>
    </td>
    <td>
        <span class="badge <?php echo $fCls; ?>" style="font-size:.72rem;"><?php echo htmlspecialchars($freq); ?></span>
    </td>
    <td style="font-size:.82rem;white-space:nowrap;">
        <?php echo $startDate; ?>
        <?php if ($row->EndDate): ?>
        <div class="text-muted" style="font-size:.72rem;">Until <?php echo $endDate; ?></div>
        <?php endif; ?>
    </td>
    <td class="<?php echo $isDue && $isActive ? 'fw-semibold text-danger' : ''; ?>" style="font-size:.82rem;white-space:nowrap;">
        <?php echo $nextRun; ?>
    </td>
    <td style="font-size:.82rem;">
        <?php echo $lastRun; ?>
        <?php if ($runs > 0): ?>
        <div class="text-muted" style="font-size:.72rem;"><?php echo $runs; ?> run<?php echo $runs !== 1 ? 's' : ''; ?></div>
        <?php endif; ?>
    </td>
    <td>
        <span class="badge <?php echo $sCls; ?>" style="font-size:.72rem;"><?php echo $status; ?></span>
    </td>
    <td class="text-center" style="white-space:nowrap;width:130px;">
        <?php if ($isActive && $status !== 'Ended'): ?>
        <button type="button"
                class="btn btn-icon btn-sm text-success rj-post-btn"
                data-uid="<?php echo $uid; ?>"
                data-title="<?php echo htmlspecialchars($row->Title); ?>"
                title="Post Now">
            <i class="bx bx-play-circle"></i>
        </button>
        <?php endif; ?>
        <button type="button"
                class="btn btn-icon btn-sm text-primary rj-edit-btn"
                data-uid="<?php echo $uid; ?>"
                title="Edit">
            <i class="bx bx-edit"></i>
        </button>
        <button type="button"
                class="btn btn-icon btn-sm <?php echo $isActive ? 'text-warning' : 'text-success'; ?> rj-toggle-btn"
                data-uid="<?php echo $uid; ?>"
                data-active="<?php echo $isActive; ?>"
                title="<?php echo $isActive ? 'Pause' : 'Resume'; ?>">
            <i class="bx <?php echo $isActive ? 'bx-pause' : 'bx-play'; ?>"></i>
        </button>
        <button type="button"
                class="btn btn-icon btn-sm text-danger rj-delete-btn"
                data-uid="<?php echo $uid; ?>"
                data-title="<?php echo htmlspecialchars($row->Title); ?>"
                title="Delete">
            <i class="bx bx-trash"></i>
        </button>
    </td>
</tr>
<?php endforeach; else: ?>
<tr>
    <td colspan="8" style="padding:0;border:none;">
        <div class="d-flex flex-column align-items-center py-5">
            <img src="/assets/img/elements/no-record-found.png" alt="No Records" class="img-fluid mb-3" style="max-height:110px;object-fit:contain;">
            <span class="text-muted" style="font-size:.9rem;">No recurring journals configured yet</span>
        </div>
    </td>
</tr>
<?php endif; ?>
