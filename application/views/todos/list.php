<?php defined('BASEPATH') or exit('No direct script access allowed');
/** @var array       $DataLists      */
/** @var int         $SerialNumber   */
/** @var object      $JwtData        */
/** @var int         $CurrentUserUID */

$_showSerial = (int)($JwtData->GenSettings->SerialNoDisplay ?? 0) === 1;
$_today      = date('Y-m-d');

/**
 * @param string $priority
 * @return string
 */
function _todoPriBadge(string $priority): string {
    $map = [
        'Low'    => ['todo-pri todo-pri-low',    'Low'],
        'Medium' => ['todo-pri todo-pri-medium',  'Medium'],
        'High'   => ['todo-pri todo-pri-high',    'High'],
        'Urgent' => ['todo-pri todo-pri-urgent',  'Urgent'],
    ];
    $c = $map[$priority] ?? ['todo-pri todo-pri-low', $priority];
    return '<span class="' . $c[0] . '">' . htmlspecialchars($c[1]) . '</span>';
}

/**
 * @param string $status
 * @return string
 */
function _todoStatBadge(string $status): string {
    $map = [
        'Open'       => ['todo-stat-badge todo-stat-open',       'Open'],
        'InProgress' => ['todo-stat-badge todo-stat-inprogress', 'In Progress'],
        'OnHold'     => ['todo-stat-badge todo-stat-onhold',     'On Hold'],
        'Completed'  => ['todo-stat-badge todo-stat-completed',  'Completed'],
        'Cancelled'  => ['todo-stat-badge todo-stat-cancelled',  'Cancelled'],
    ];
    $c = $map[$status] ?? ['todo-stat-badge todo-stat-open', $status];
    return '<span class="' . $c[0] . '">' . htmlspecialchars($c[1]) . '</span>';
}

/**
 * @param string|null $dueDate
 * @param string      $status
 * @param string      $today
 * @return string
 */
function _todoDueCell(?string $dueDate, string $status, string $today): string {
    if (empty($dueDate)) return '<span class="text-muted">—</span>';
    $done = in_array($status, ['Completed', 'Cancelled']);
    if ($done) return '<span class="text-muted">' . htmlspecialchars(date('d M Y', strtotime($dueDate))) . '</span>';
    if ($dueDate < $today)  return '<span class="todo-due-overdue"><i class="bx bx-error-circle me-1"></i>' . htmlspecialchars(date('d M Y', strtotime($dueDate))) . '</span>';
    if ($dueDate === $today) return '<span class="todo-due-today"><i class="bx bx-calendar-event me-1"></i>Today</span>';
    $diff = (int)((strtotime($dueDate) - strtotime($today)) / 86400);
    if ($diff <= 3) return '<span class="todo-due-soon">' . htmlspecialchars(date('d M Y', strtotime($dueDate))) . '</span>';
    return '<span>' . htmlspecialchars(date('d M Y', strtotime($dueDate))) . '</span>';
}

if (empty($DataLists)):
?>
<tr><td colspan="7">
    <div class="rpt-empty">
        <i class="bx bx-task-x"></i>
        <div class="rpt-empty-title">No tasks found</div>
        <div class="text-muted" style="font-size:.80rem">Create your first task using the New button above.</div>
    </div>
</td></tr>
<?php else:
    $sno = (int)$SerialNumber;
    foreach ($DataLists as $row):
        $sno++;
        $isDone    = in_array($row->Status, ['Completed', 'Cancelled']);
        $isPrivate = (int)($row->IsPrivate ?? 0) === 1;
        $isOwner   = ((int)$row->CreatedByUID  === (int)$CurrentUserUID)
                  || ((int)$row->AssignedToUID === (int)$CurrentUserUID);
?>
<tr data-uid="<?php echo (int)$row->TodoUID; ?>">
    <td class="<?php echo $_showSerial ? '' : 'd-none'; ?>"><?php echo $sno; ?></td>

    <!-- Quick-complete checkbox -->
    <td>
        <?php if (!$isDone): ?>
        <input type="checkbox" class="todo-done-cb" title="Mark as completed"
               data-uid="<?php echo (int)$row->TodoUID; ?>">
        <?php else: ?>
        <i class="bx bx-check-circle text-success" style="font-size:1rem"></i>
        <?php endif; ?>
    </td>

    <!-- Priority -->
    <td><?php echo _todoPriBadge($row->Priority); ?></td>

    <!-- Title + ref chip + desc -->
    <td>
        <div class="todo-title-wrap">
            <span class="todo-title <?php echo $isDone ? 'todo-title-done' : ''; ?>">
                <?php echo htmlspecialchars($row->Title); ?>
                <?php if ($isPrivate): ?><i class="bx bx-lock ms-1 text-muted" title="Private" style="font-size:.75rem"></i><?php endif; ?>
            </span>
            <?php if (!empty($row->RefLabel) && $row->RefType !== 'General'): ?>
            <span class="todo-ref-chip"><i class="bx bx-link-alt me-1"></i><?php echo htmlspecialchars($row->RefType . ': ' . $row->RefLabel); ?></span>
            <?php endif; ?>
            <?php if (!empty($row->Description)): ?>
            <span class="todo-desc"><?php echo htmlspecialchars(mb_substr($row->Description, 0, 80)); ?></span>
            <?php endif; ?>
        </div>
    </td>

    <!-- Assigned To -->
    <td class="text-nowrap">
        <?php echo htmlspecialchars($row->AssignedName ?? '—'); ?>
        <?php if ((int)$row->AssignedToUID === (int)$CurrentUserUID): ?>
        <span class="badge bg-label-primary ms-1" style="font-size:.65rem">Me</span>
        <?php endif; ?>
    </td>

    <!-- Due Date -->
    <td class="text-nowrap"><?php echo _todoDueCell($row->DueDate ?? null, $row->Status, $_today); ?></td>

    <!-- Status -->
    <td>
        <?php if ($isOwner && !$isDone): ?>
        <select class="todo-status-select" data-uid="<?php echo (int)$row->TodoUID; ?>" title="Change status">
            <option value="Open"       <?php echo $row->Status === 'Open'       ? 'selected' : ''; ?>>Open</option>
            <option value="InProgress" <?php echo $row->Status === 'InProgress' ? 'selected' : ''; ?>>In Progress</option>
            <option value="OnHold"     <?php echo $row->Status === 'OnHold'     ? 'selected' : ''; ?>>On Hold</option>
        </select>
        <?php else: ?>
        <?php echo _todoStatBadge($row->Status); ?>
        <?php endif; ?>
    </td>

    <!-- Actions -->
    <td class="th-act">
        <div class="d-flex gap-1 align-items-center">
            <?php if ($isOwner): ?>
            <a href="#" class="r2k-icon-btn todo-edit-btn" data-uid="<?php echo (int)$row->TodoUID; ?>"
               title="Edit"><i class="bx bx-edit-alt"></i></a>
            <?php if ($isDone): ?>
            <a href="#" class="r2k-icon-btn todo-reopen-btn" data-uid="<?php echo (int)$row->TodoUID; ?>"
               title="Reopen"><i class="bx bx-refresh"></i></a>
            <?php endif; ?>
            <a href="#" class="r2k-icon-btn text-danger todo-delete-btn" data-uid="<?php echo (int)$row->TodoUID; ?>"
               title="Delete"><i class="bx bx-trash"></i></a>
            <?php else: ?>
            <span class="text-muted" style="font-size:.75rem">View only</span>
            <?php endif; ?>
        </div>
    </td>
</tr>
<?php
    endforeach;
endif;
?>
