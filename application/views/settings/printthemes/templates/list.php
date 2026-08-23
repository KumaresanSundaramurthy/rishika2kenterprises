<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
if (!empty($DataLists)):
    foreach ($DataLists as $i => $row):
        $preview   = !empty($row->PreviewImage) ? $row->PreviewImage : null;
        $updatedOn = !empty($row->UpdatedOn) ? format_datedisplay($row->UpdatedOn) : '—';
        $catBadge  = [
            'general' => 'bg-label-secondary',
            'gst'     => 'bg-label-success',
            'minimal' => 'bg-label-info',
            'formal'  => 'bg-label-warning',
            'modern'  => 'bg-label-primary',
        ][$row->Category] ?? 'bg-label-secondary';
?>
<tr>
    <td>
        <?php if ($preview): ?>
            <img src="<?php echo htmlspecialchars($preview); ?>"
                 alt="<?php echo htmlspecialchars($row->TemplateName); ?>"
                 class="tpl-preview-thumb"
                 data-src="<?php echo htmlspecialchars($preview); ?>">
        <?php else: ?>
            <div class="tpl-preview-placeholder">
                <i class="bx bx-image text-muted fs-4"></i>
            </div>
        <?php endif; ?>
    </td>
    <td>
        <div class="fw-semibold"><?php echo htmlspecialchars($row->TemplateName); ?></div>
        <?php if (!empty($row->Description)): ?>
            <div class="text-muted tinysmall"><?php echo htmlspecialchars(substr($row->Description, 0, 60)) . (strlen($row->Description) > 60 ? '…' : ''); ?></div>
        <?php endif; ?>
    </td>
    <td><code class="text-muted small"><?php echo htmlspecialchars($row->TemplateKey); ?></code></td>
    <td><span class="badge <?php echo $catBadge; ?>"><?php echo htmlspecialchars(ucfirst($row->Category)); ?></span></td>
    <td class="r2k-col-date"><span class="text-muted small"><?php echo $updatedOn; ?></span></td>
    <td class="text-center">
        <a href="javascript:void(0);" class="apex-icon-btn editTemplateBtn"
           data-uid="<?php echo (int)$row->TemplateUID; ?>" title="Edit">
            <i class="bx bx-edit"></i>
        </a>
        <a href="javascript:void(0);" class="apex-icon-btn text-danger deleteTemplateBtn"
           data-uid="<?php echo (int)$row->TemplateUID; ?>"
           data-label="<?php echo htmlspecialchars($row->TemplateName); ?>" title="Delete">
            <i class="bx bx-trash"></i>
        </a>
    </td>
</tr>
<?php endforeach; else: ?>
<tr>
    <td colspan="6" class="text-center py-4 text-muted">
        <i class="bx bx-file fs-1 d-block mb-2 opacity-50"></i>
        No templates found. Click <strong>Add Template</strong> to create one.
    </td>
</tr>
<?php endif; ?>
