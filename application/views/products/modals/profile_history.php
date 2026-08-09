<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Product Profile Modal — History Tab
 *
 * @var array  $Rows        Audit log rows from Security.UserAuditLogTbl
 * @var object $JwtData
 * @var string $DateFormat
 * @var int    $ProductUID
 */

$actionColors = [
    'CREATE' => 'success',
    'UPDATE' => 'primary',
    'DELETE' => 'danger',
    'VIEW'   => 'secondary',
    'UPLOAD' => 'info',
    'REMOVE' => 'warning',
];
?>
<div class="p-3 p-md-4">

<?php if (empty($Rows)): ?>
    <div class="d-flex flex-column align-items-center justify-content-center py-5 text-muted">
        <i class="bx bx-history pp-empty-icon mb-2"></i>
        <div>No history recorded for this product yet.</div>
    </div>
<?php else: ?>

    <div class="pp-history-timeline">
    <?php foreach ($Rows as $row):
        $action    = strtoupper($row['Action'] ?? 'UPDATE');
        $badgeCls  = $actionColors[$action] ?? 'secondary';
        $oldVals   = !empty($row['OldValues']) ? json_decode($row['OldValues'], true) : null;
        $newVals   = !empty($row['NewValues']) ? json_decode($row['NewValues'], true) : null;
        $hasDiff   = is_array($oldVals) && is_array($newVals) && !empty($newVals);
        $ts        = !empty($row['CreatedAt']) ? date($DateFormat . ' h:i A', strtotime($row['CreatedAt'])) : '—';
        $userName  = htmlspecialchars($row['UserName'] ?? 'System');
        $summary   = htmlspecialchars($row['Summary'] ?? '');
    ?>
    <div class="pp-history-item">
        <div class="pp-history-dot bg-<?php echo $badgeCls; ?>"></div>
        <div class="pp-history-body">
            <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                <span class="badge bg-label-<?php echo $badgeCls; ?> pp-history-badge"><?php echo htmlspecialchars($action); ?></span>
                <span class="pp-history-user"><i class="bx bx-user me-1"></i><?php echo $userName; ?></span>
                <span class="pp-history-time ms-auto"><i class="bx bx-time-five me-1"></i><?php echo $ts; ?></span>
            </div>
            <?php if ($summary): ?>
                <div class="pp-history-summary"><?php echo $summary; ?></div>
            <?php endif; ?>
            <?php if (!empty($row['Source']) || !empty($row['DeviceType'])): ?>
            <div class="pp-history-meta">
                <?php if (!empty($row['Source'])): ?>
                    <span class="badge bg-label-secondary pp-meta-badge"><?php echo htmlspecialchars($row['Source']); ?></span>
                <?php endif; ?>
                <?php if (!empty($row['DeviceType']) && $row['DeviceType'] !== 'Unknown'): ?>
                    <span class="badge bg-label-secondary pp-meta-badge"><?php echo htmlspecialchars($row['DeviceType']); ?></span>
                <?php endif; ?>
                <?php if (!empty($row['IPAddress'])): ?>
                    <span class="pp-history-ip"><?php echo htmlspecialchars($row['IPAddress']); ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php if ($hasDiff):
                $changedFields = array_intersect_key($newVals, $oldVals);
                $changedFields = array_filter($changedFields, function ($v, $k) use ($oldVals) {
                    return (string) $v !== (string) ($oldVals[$k] ?? '');
                }, ARRAY_FILTER_USE_BOTH);
                if (!empty($changedFields)):
            ?>
            <div class="table-responsive mt-2">
                <table class="table table-sm pp-diff-table mb-0">
                    <thead>
                        <tr>
                            <th>Field</th>
                            <th>Before</th>
                            <th>After</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($changedFields as $field => $newVal):
                        $oldVal = $oldVals[$field] ?? '';
                        $fLabel = ucwords(str_replace(['_', 'UID'], [' ', ' ID'], $field));
                    ?>
                    <tr>
                        <td class="fw-semibold"><?php echo htmlspecialchars($fLabel); ?></td>
                        <td class="text-danger pp-diff-old"><?php echo htmlspecialchars((string) $oldVal ?: '—'); ?></td>
                        <td class="text-success pp-diff-new"><?php echo htmlspecialchars((string) $newVal ?: '—'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    </div>

<?php endif; ?>
</div>
