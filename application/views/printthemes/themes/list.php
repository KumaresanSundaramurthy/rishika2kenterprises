<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$typeLabels = $TransactionTypes ?? [];
if (!empty($DataLists)):
    foreach ($DataLists as $i => $row):
        $typeLabel = $typeLabels[$row->TransactionType] ?? $row->TransactionType;
        $tplName   = !empty($row->TemplateName) ? htmlspecialchars($row->TemplateName) : 'Custom Theme';
        $primary   = htmlspecialchars($row->PrimaryColor ?? '#1a3c6e');
        $accent    = htmlspecialchars($row->AccentColor  ?? '#f59e0b');
        $themeKey  = htmlspecialchars($row->ThemeKey ?? 'classic');
        $updatedOn = !empty($row->UpdatedOn) ? format_datedisplay($row->UpdatedOn) : '—';
?>
<div class="col-xl-3 col-lg-4 col-md-6">
    <div class="card h-100 border-0 shadow-sm theme-card-wrap">

        <!-- Thumbnail with action overlay -->
        <div style="padding:10px 10px 0;background:#f4f5f7;border-radius:8px 8px 0 0;position:relative;">
            <div class="theme-card-thumb"
                 data-key="<?php echo $themeKey; ?>"
                 data-primary="<?php echo $primary; ?>"
                 data-accent="<?php echo $accent; ?>"
                 style="width:100%;height:108px;background:#fff;border:1px solid #e0e0e0;border-radius:4px;overflow:hidden;"></div>

            <!-- Action overlay (appears on hover) -->
            <div class="theme-card-actions">
                <button class="theme-card-action-btn editThemeBtn"
                        data-uid="<?php echo (int)$row->ThemeConfigUID; ?>"
                        title="Edit">
                    <i class="bx bx-edit"></i>
                </button>
                <button class="theme-card-action-btn danger deleteThemeBtn"
                        data-uid="<?php echo (int)$row->ThemeConfigUID; ?>"
                        data-label="<?php echo htmlspecialchars($typeLabel); ?>"
                        title="Delete">
                    <i class="bx bx-trash"></i>
                </button>
            </div>
        </div>

        <div class="card-body py-2 px-3">
            <div class="d-flex justify-content-between align-items-start mb-1">
                <div>
                    <span class="badge bg-label-primary mb-1"><?php echo htmlspecialchars($typeLabel); ?></span>
                    <div class="fw-semibold small"><?php echo $tplName; ?></div>
                </div>
                <div class="d-flex gap-1 mt-1 flex-shrink-0">
                    <div title="Primary: <?php echo $primary; ?>"
                         style="width:16px;height:16px;border-radius:50%;background:<?php echo $primary; ?>;border:1px solid rgba(0,0,0,.1);"></div>
                    <div title="Accent: <?php echo $accent; ?>"
                         style="width:16px;height:16px;border-radius:50%;background:<?php echo $accent; ?>;border:1px solid rgba(0,0,0,.1);"></div>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-1 mb-1">
                <?php if ($row->ShowLogo):        ?><span class="badge bg-label-secondary" style="font-size:.62rem;">Logo</span><?php endif; ?>
                <?php if ($row->ShowOrgAddress):  ?><span class="badge bg-label-secondary" style="font-size:.62rem;">Address</span><?php endif; ?>
                <?php if ($row->ShowGSTIN):       ?><span class="badge bg-label-secondary" style="font-size:.62rem;">GSTIN</span><?php endif; ?>
                <?php if ($row->ShowHSN):         ?><span class="badge bg-label-secondary" style="font-size:.62rem;">HSN</span><?php endif; ?>
                <?php if ($row->ShowTaxBreakdown):?><span class="badge bg-label-secondary" style="font-size:.62rem;">Tax</span><?php endif; ?>
            </div>
            <div class="text-muted" style="font-size:.67rem;">
                <?php echo htmlspecialchars($row->FontFamily ?? 'Arial'); ?>, <?php echo (int)($row->FontSizePx ?? 11); ?>px
                &nbsp;·&nbsp;<?php echo $updatedOn; ?>
            </div>
        </div>

    </div>
</div>
<?php endforeach; else: ?>
<div class="col-12">
    <div class="text-center py-5 text-muted">
        <i class="bx bx-palette fs-1 d-block mb-2 opacity-50"></i>
        <div class="mb-1">No themes configured yet.</div>
        <div class="small">Click <strong>Add Theme</strong> to get started.</div>
    </div>
</div>
<?php endif; ?>
