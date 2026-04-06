<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$cdnUrl = getenv('FILE_UPLOAD') == 'amazonaws' ? getenv('CDN_URL') : getenv('CFLARE_R2_CDN');
if (!empty($DataLists)):
    foreach ($DataLists as $i => $row):
        $preview   = !empty($row->PreviewImage) ? $row->PreviewImage : null;
        $updatedOn = !empty($row->UpdatedOn) ? date('d M Y', strtotime($row->UpdatedOn)) : '—';
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
                 style="width:80px;height:58px;object-fit:cover;border-radius:4px;border:1px solid #ddd;cursor:pointer;"
                 onclick="window.open('<?php echo htmlspecialchars($preview); ?>','_blank')">
        <?php else: ?>
            <div style="width:80px;height:58px;border-radius:4px;border:1px solid #ddd;background:#f5f5f5;
                        display:flex;align-items:center;justify-content:center;">
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
    <td><span class="text-muted small"><?php echo $updatedOn; ?></span></td>
    <td class="text-center">
        <div class="d-flex justify-content-center gap-2">
            <button class="btn btn-sm btn-outline-primary editTemplateBtn"
                    data-uid="<?php echo (int)$row->TemplateUID; ?>"
                    title="Edit">
                <i class="bx bx-edit"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger deleteTemplateBtn"
                    data-uid="<?php echo (int)$row->TemplateUID; ?>"
                    data-label="<?php echo htmlspecialchars($row->TemplateName); ?>"
                    title="Delete">
                <i class="bx bx-trash"></i>
            </button>
        </div>
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
