<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$channels = [
    'Email'     => ['icon' => 'bx-envelope',      'color' => '#1565c0', 'bg' => '#e3f2fd'],
    'WhatsApp'  => ['icon' => 'bxl-whatsapp',     'color' => '#25d366', 'bg' => '#e8f5e9'],
    'SMS'       => ['icon' => 'bx-message-dots',  'color' => '#0097a7', 'bg' => '#e0f7fa'],
];

$grouped = [];
foreach ($DataLists as $row) {
    $grouped[$row->ModuleUID][$row->Channel] = $row;
}
?>

<?php if (empty($DataLists)): ?>
<tr>
    <td colspan="4">
        <div class="d-flex flex-column align-items-center justify-content-center py-5" style="gap:8px;">
            <i class="bx bx-message-square-edit" style="font-size:2.2rem;color:#bbb;"></i>
            <div class="text-muted" style="font-size:.88rem;">No message templates yet.</div>
            <div class="text-muted" style="font-size:.78rem;">Click <strong>+ Add Template</strong> to create one.</div>
        </div>
    </td>
</tr>
<?php return; endif; ?>

<?php foreach ($grouped as $moduleUID => $channelRows): ?>
<?php $moduleName = $Modules[$moduleUID] ?? ('Module ' . $moduleUID); ?>
<tr class="table-light">
    <td colspan="4" class="fw-semibold ps-3" style="font-size:.8rem;color:#555;letter-spacing:.3px;text-transform:uppercase;">
        <i class="bx bx-transfer me-1 text-primary"></i><?php echo htmlspecialchars($moduleName); ?>
    </td>
</tr>
<?php foreach ($channels as $channel => $ch): ?>
<?php $row = $channelRows[$channel] ?? null; ?>
<tr>
    <td class="ps-4" style="width:130px;">
        <span class="badge" style="background:<?php echo $ch['bg']; ?>;color:<?php echo $ch['color']; ?>;font-size:.78rem;padding:5px 10px;">
            <i class="bx <?php echo $ch['icon']; ?> me-1"></i><?php echo $channel; ?>
        </span>
    </td>
    <td style="font-size:.82rem;">
        <?php if ($row): ?>
            <?php if ($channel === 'Email' && !empty($row->Subject)): ?>
            <div class="fw-semibold text-dark mb-1" style="font-size:.8rem;"><?php echo htmlspecialchars($row->Subject); ?></div>
            <?php endif; ?>
            <?php
            // Strip HTML tags for Email channel preview (body is HTML from editor)
            $bodyPreview = $channel === 'Email'
                ? strip_tags($row->Body)
                : $row->Body;
            $bodyPreview = mb_substr(trim($bodyPreview), 0, 120) . (mb_strlen(trim($bodyPreview)) > 120 ? '…' : '');
            ?>
            <div class="text-muted" style="font-size:.76rem;white-space:pre-wrap;max-height:48px;overflow:hidden;"><?php echo htmlspecialchars($bodyPreview); ?></div>
        <?php else: ?>
            <span class="text-muted fst-italic" style="font-size:.78rem;">No template configured</span>
        <?php endif; ?>
    </td>
    <td style="width:130px;">
        <?php if ($row): ?>
        <div style="font-size:.72rem;color:#666;"><?php echo !empty($row->UpdatedOn) ? date('d M Y', strtotime($row->UpdatedOn)) : '—'; ?></div>
        <div style="font-size:.68rem;color:#aaa;">by <?php echo htmlspecialchars($row->UpdatedByName ?? '—'); ?></div>
        <?php else: ?>
        <span class="text-muted">—</span>
        <?php endif; ?>
    </td>
    <td class="text-center" style="width:90px;">
        <?php if ($row): ?>
        <a href="javascript:void(0);" class="btn btn-icon btn-sm text-warning EditMsgTemplate"
           data-uid="<?php echo (int)$row->TemplateUID; ?>"
           title="Edit"><i class="bx bx-edit"></i></a>
        <a href="javascript:void(0);" class="btn btn-icon btn-sm text-danger DeleteMsgTemplate"
           data-uid="<?php echo (int)$row->TemplateUID; ?>"
           data-label="<?php echo htmlspecialchars($moduleName . ' — ' . $channel); ?>"
           title="Delete"><i class="bx bx-trash"></i></a>
        <?php else: ?>
        <a href="javascript:void(0);" class="btn btn-sm btn-outline-secondary AddMsgTemplate"
           style="font-size:.72rem;padding:2px 8px;"
           data-module-uid="<?php echo (int)$moduleUID; ?>"
           data-module-name="<?php echo htmlspecialchars($moduleName); ?>"
           data-channel="<?php echo $channel; ?>">
            + Add
        </a>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
<?php endforeach; ?>
