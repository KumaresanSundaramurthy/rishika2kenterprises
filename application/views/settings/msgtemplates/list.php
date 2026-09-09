<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$channels = [
    'Email'    => ['icon' => 'bx-envelope',     'cls' => 'mt-tpl-ch-email',    'label' => 'Email'],
    'WhatsApp' => ['icon' => 'bxl-whatsapp',    'cls' => 'mt-tpl-ch-whatsapp', 'label' => 'WhatsApp'],
    'SMS'      => ['icon' => 'bx-message-dots', 'cls' => 'mt-tpl-ch-sms',      'label' => 'SMS'],
];

$grouped = [];
foreach ($DataLists as $row) {
    $grouped[$row->ModuleUID][$row->Channel] = $row;
}

/**
 * Wrap {{TOKEN}} placeholders in a highlight span.
 *
 * @param string $text
 * @return string
 */
function _mtHighlightTokens(string $text): string {
    return preg_replace_callback('/\{\{([A-Z_]+)\}\}/', function ($m) {
        return '<span class="mt-tpl-token">' . htmlspecialchars($m[0]) . '</span>';
    }, $text);
}
?>

<?php if (empty($DataLists)): ?>
<tr>
    <td colspan="4">
        <div class="mt-tpl-empty-wrap">
            <div class="mt-tpl-empty-icon"><i class="bx bx-message-square-edit"></i></div>
            <div class="mt-tpl-empty-title"><?php echo t('empty_templates', 'No templates configured yet'); ?></div>
            <div class="mt-tpl-empty-sub">Click <strong>+ Add Template</strong> to create your first message template.</div>
        </div>
    </td>
</tr>
<?php return; endif; ?>

<?php $chKeys = array_keys($channels); $lastChKey = end($chKeys); ?>

<?php foreach ($grouped as $moduleUID => $channelRows): ?>
<?php
$moduleName    = $Modules[$moduleUID] ?? ('Module ' . $moduleUID);
$configuredCt  = count(array_filter($chKeys, fn($ch) => isset($channelRows[$ch])));
$totalCh       = count($channels);
?>

<!-- Module section header -->
<tr class="mt-tpl-module-row">
    <td colspan="4">
        <div class="mt-tpl-module-hdr">
            <div class="mt-tpl-module-icon-wrap"><i class="bx bx-transfer-alt"></i></div>
            <div class="mt-tpl-module-name"><?php echo htmlspecialchars($moduleName); ?></div>
            <span class="mt-tpl-module-badge"><?php echo $configuredCt; ?>/<?php echo $totalCh; ?> configured</span>
        </div>
    </td>
</tr>

<!-- Channel rows -->
<?php foreach ($channels as $channel => $ch): ?>
<?php
$row         = $channelRows[$channel] ?? null;
$isLastCh    = ($channel === $lastChKey);
$rowClass    = 'mt-tpl-ch-row' . ($isLastCh ? ' mt-tpl-last-ch' : '');
?>
<tr class="<?php echo $rowClass; ?>">

    <!-- Channel badge -->
    <td class="mt-tpl-ch-cell">
        <div class="mt-tpl-ch-badge <?php echo $ch['cls']; ?>">
            <i class="bx <?php echo $ch['icon']; ?>"></i>
            <?php echo $channel; ?>
        </div>
    </td>

    <!-- Preview / empty state -->
    <td class="mt-tpl-preview-cell">
        <?php if ($row): ?>
            <?php if ($channel === 'Email' && !empty($row->Subject)): ?>
                <div class="mt-tpl-subject"><?php echo htmlspecialchars($row->Subject); ?></div>
            <?php endif; ?>
            <?php
            /* Build truncated preview text */
            if ($channel === 'Email') {
                $bodyPreview = preg_replace('/<(br\s*\/?|\/?(p|div|li|h[1-6]))[^>]*>/i', "\n", $row->Body ?? '');
                $bodyPreview = strip_tags($bodyPreview);
            } else {
                $bodyPreview = $row->Body ?? '';
            }
            $bodyPreview = trim($bodyPreview);
            if (mb_strlen($bodyPreview) > 130) {
                $cut = mb_substr($bodyPreview, 0, 130);
                if (substr_count($cut, '{{') > substr_count($cut, '}}')) {
                    $cut = mb_substr($cut, 0, mb_strrpos($cut, '{{'));
                }
                $bodyPreview = rtrim($cut) . '…';
            }
            ?>
            <div class="mt-tpl-body-preview"><?php echo _mtHighlightTokens(htmlspecialchars($bodyPreview)); ?></div>
        <?php else: ?>
            <div class="mt-tpl-empty-state">
                <span class="mt-tpl-not-set"><i class="bx bx-minus-circle"></i><?php echo t('lbl_no_template', 'Not configured'); ?></span>
                <a href="javascript:void(0);" class="mt-tpl-add-btn AddMsgTemplate"
                   data-module-uid="<?php echo (int)$moduleUID; ?>"
                   data-module-name="<?php echo htmlspecialchars($moduleName); ?>"
                   data-channel="<?php echo $channel; ?>">
                    <i class="bx bx-plus"></i><?php echo t('btn_set_up', 'Set up'); ?>
                </a>
            </div>
        <?php endif; ?>
    </td>

    <!-- Last updated -->
    <td class="mt-tpl-date-cell">
        <?php if ($row && !empty($row->UpdatedOn)): ?>
            <div class="mt-tpl-date"><?php echo format_datedisplay($row->UpdatedOn); ?></div>
            <div class="mt-tpl-by">by <?php echo htmlspecialchars($row->UpdatedByName ?? '—'); ?></div>
        <?php else: ?>
            <span class="text-muted">—</span>
        <?php endif; ?>
    </td>

    <!-- Actions -->
    <td class="mt-tpl-action-cell">
        <?php if ($row): ?>
        <div class="d-flex align-items-center justify-content-center gap-1">
            <a href="javascript:void(0);" class="btn btn-icon btn-sm text-warning EditMsgTemplate"
               data-uid="<?php echo (int)$row->TemplateUID; ?>"
               title="<?php echo t('btn_edit', 'Edit'); ?>">
                <i class="bx bx-edit"></i>
            </a>
            <a href="javascript:void(0);" class="btn btn-icon btn-sm text-danger DeleteMsgTemplate"
               data-uid="<?php echo (int)$row->TemplateUID; ?>"
               data-label="<?php echo htmlspecialchars($moduleName . ' — ' . $channel); ?>"
               title="<?php echo t('btn_delete', 'Delete'); ?>">
                <i class="bx bx-trash"></i>
            </a>
        </div>
        <?php endif; ?>
    </td>

</tr>
<?php endforeach; ?>
<?php endforeach; ?>
