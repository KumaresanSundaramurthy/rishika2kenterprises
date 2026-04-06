<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$typeLabels = $TransactionTypes ?? [];
if (!empty($DataLists)):
    foreach ($DataLists as $i => $row):
        $typeLabel = $typeLabels[$row->TransactionType] ?? $row->TransactionType;
        $tplName   = !empty($row->TemplateName) ? htmlspecialchars($row->TemplateName) : '<span class="text-muted">—</span>';
        $preview   = !empty($row->TemplatePreviewImage) ? $row->TemplatePreviewImage : null;
        $updatedOn = !empty($row->UpdatedOn) ? date('d M Y', strtotime($row->UpdatedOn)) : '—';
?>
<tr>
    <td>
        <?php if ($preview): ?>
            <img src="<?php echo htmlspecialchars($preview); ?>" alt="<?php echo $tplName; ?>"
                 style="width:72px;height:52px;object-fit:cover;border-radius:4px;border:1px solid #ddd;">
        <?php else: ?>
            <div style="width:72px;height:52px;border-radius:4px;border:1px solid #ddd;background:#f5f5f5;display:flex;align-items:center;justify-content:center;">
                <i class="bx bx-image text-muted fs-4"></i>
            </div>
        <?php endif; ?>
    </td>
    <td>
        <div class="fw-semibold"><?php echo htmlspecialchars($typeLabel); ?></div>
        <div class="text-muted tinysmall"><?php echo htmlspecialchars($row->TransactionType); ?></div>
    </td>
    <td><?php echo $tplName; ?></td>
    <td>
        <div class="d-flex align-items-center gap-2">
            <div style="width:18px;height:18px;border-radius:3px;background:<?php echo htmlspecialchars($row->PrimaryColor); ?>;border:1px solid rgba(0,0,0,.12);" title="Primary: <?php echo htmlspecialchars($row->PrimaryColor); ?>"></div>
            <div style="width:18px;height:18px;border-radius:3px;background:<?php echo htmlspecialchars($row->AccentColor);  ?>;border:1px solid rgba(0,0,0,.12);" title="Accent: <?php echo htmlspecialchars($row->AccentColor); ?>"></div>
            <span class="text-muted tinysmall"><?php echo htmlspecialchars($row->PrimaryColor); ?></span>
        </div>
    </td>
    <td>
        <div class="d-flex flex-wrap gap-1">
            <?php if ($row->ShowLogo):        ?><span class="badge bg-label-secondary">Logo</span><?php endif; ?>
            <?php if ($row->ShowOrgAddress):  ?><span class="badge bg-label-secondary">Address</span><?php endif; ?>
            <?php if ($row->ShowGSTIN):       ?><span class="badge bg-label-secondary">GSTIN</span><?php endif; ?>
            <?php if ($row->ShowHSN):         ?><span class="badge bg-label-secondary">HSN</span><?php endif; ?>
            <?php if ($row->ShowTaxBreakdown):?><span class="badge bg-label-secondary">Tax</span><?php endif; ?>
        </div>
    </td>
    <td>
        <span class="text-muted small"><?php echo htmlspecialchars($row->FontFamily ?? 'Arial'); ?>, <?php echo (int)($row->FontSizePx ?? 11); ?>px</span>
    </td>
    <td><span class="text-muted small"><?php echo $updatedOn; ?></span></td>
    <td class="text-center">
        <div class="d-flex justify-content-center gap-2">
            <button class="btn btn-sm btn-outline-primary editThemeBtn" data-uid="<?php echo (int)$row->ThemeConfigUID; ?>">
                <i class="bx bx-edit"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger deleteThemeBtn"
                    data-uid="<?php echo (int)$row->ThemeConfigUID; ?>"
                    data-label="<?php echo htmlspecialchars($typeLabel); ?>">
                <i class="bx bx-trash"></i>
            </button>
        </div>
    </td>
</tr>
<?php endforeach; else: ?>
<tr>
    <td colspan="8" class="text-center py-4 text-muted">
        <i class="bx bx-palette fs-1 d-block mb-2 opacity-50"></i>
        No themes configured yet. Click <strong>Add Theme</strong> to get started.
    </td>
</tr>
<?php endif; ?>
