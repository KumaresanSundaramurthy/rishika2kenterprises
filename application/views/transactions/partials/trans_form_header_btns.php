<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Card-header action button panel for transaction forms.
 *
 * Parameters:
 *   string $_hBtnLayout   'split'        — create: draft+split; edit: [draft]+save (PUR/PR/PO/SR)
 *                         'always_split' — draft conditional; always: split-group (PF/DC)
 *                         'invoice'      — create: draft+print-first; edit: [draft]+save (INV)
 *   bool   $_hDcMenu      true = dc-save-menu class + no inline style on dropdown (DC only)
 *   bool   $_hEditSavePx3 true = add px-3 to edit-mode save btn (SR only; default false)
 *
 * Uses $isEdit, $isDraftEdit, $JwtData, $_closeUrl from parent view scope.
 */
$_hLayout  = $_hBtnLayout    ?? 'split';
$_hDcMenu  = $_hDcMenu       ?? false;
$_hSavePx3 = $_hEditSavePx3  ?? false;
$_hHideNav = (int)($JwtData->TransSettings->HideNavOnTransForm ?? 0);
$_hMenuCls = 'dropdown-menu dropdown-menu-end shadow' . ($_hDcMenu ? ' dc-save-menu' : '');
$_hMenuSty = $_hDcMenu ? '' : 'min-width:195px;font-size:.82rem;';
$_hHdrSty  = $_hDcMenu ? '' : 'font-size:.65rem;letter-spacing:.4px;';
?>
<div class="d-flex align-items-center gap-2">

<?php if ($_hLayout === 'always_split'): ?>

    <?php if (!$isEdit || $isDraftEdit): ?>
    <button type="submit" name="action" value="draft" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('tooltip_save_draft', 'Save and continue editing later'); ?>"><i class="bx bx-save me-1"></i><?php echo t('btn_save_draft', 'Save as Draft'); ?></button>
    <?php endif; ?>
    <div class="btn-group">
        <button type="submit" name="action" value="save" class="btn btn-sm btn-primary px-3" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('tooltip_save', 'Save transaction'); ?>">
            <i class="bx bx-check me-1"></i>Save
        </button>
        <button type="button" class="btn btn-sm btn-primary dropdown-toggle dropdown-toggle-split ps-2 pe-2" data-bs-toggle="dropdown" aria-expanded="false">
            <span class="visually-hidden">Save options</span>
        </button>
        <ul class="<?php echo $_hMenuCls; ?>"<?php if ($_hMenuSty): ?> style="<?php echo $_hMenuSty; ?>"<?php endif; ?>>
            <li><span class="dropdown-header py-1"<?php if ($_hHdrSty): ?> style="<?php echo $_hHdrSty; ?>"<?php endif; ?>>SAVE &amp; PRINT</span></li>
            <li><button type="submit" class="dropdown-item py-1" name="action" value="save_a4"><i class="bx bx-file text-primary me-2"></i><?php echo t('btn_save_a4', 'Save & Print A4'); ?></button></li>
            <li><button type="submit" class="dropdown-item py-1" name="action" value="save_a5"><i class="bx bx-file-blank text-info me-2"></i><?php echo t('btn_save_a5', 'Save & Print A5'); ?></button></li>
            <li><button type="submit" class="dropdown-item py-1" name="action" value="save_thermal"><i class="bx bx-receipt text-success me-2"></i><?php echo t('btn_save_thermal', 'Save & Print Thermal'); ?></button></li>
        </ul>
    </div>

<?php elseif ($_hLayout === 'invoice'): ?>

    <?php if (!$isEdit): ?>
    <button type="submit" name="action" value="draft" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('tooltip_save_draft', 'Save and continue editing later'); ?>"><i class="bx bx-save me-1"></i><?php echo t('btn_save_draft', 'Save as Draft'); ?></button>
    <div class="btn-group">
        <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bx bx-printer me-1"></i><?php echo t('btn_save_print', 'Save &amp; Print'); ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width:175px;font-size:.82rem;">
            <li><button type="submit" class="dropdown-item py-1" name="action" value="save_a4"><i class="bx bx-file text-primary me-2"></i><?php echo t('btn_save_a4', 'Save & Print A4'); ?></button></li>
            <li><button type="submit" class="dropdown-item py-1" name="action" value="save_a5"><i class="bx bx-file-blank text-info me-2"></i><?php echo t('btn_save_a5', 'Save & Print A5'); ?></button></li>
            <li><button type="submit" class="dropdown-item py-1" name="action" value="save_thermal"><i class="bx bx-receipt text-success me-2"></i><?php echo t('btn_save_thermal', 'Save & Print Thermal'); ?></button></li>
        </ul>
    </div>
    <button type="submit" name="action" value="save" class="btn btn-sm btn-primary px-3" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('tooltip_save', 'Save transaction'); ?>"><i class="bx bx-check me-1"></i>Save</button>
    <?php else: ?>
    <?php if ($isDraftEdit): ?>
    <button type="submit" name="action" value="draft" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('tooltip_save_draft', 'Save and continue editing later'); ?>"><i class="bx bx-save me-1"></i><?php echo t('btn_save_draft', 'Save as Draft'); ?></button>
    <?php endif; ?>
    <button type="submit" name="action" value="save" class="btn btn-sm btn-primary px-3" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('tooltip_save', 'Save transaction'); ?>"><i class="bx bx-check me-1"></i>Save</button>
    <?php endif; ?>

<?php else: /* split — PUR/PR/PO/SR */ ?>

    <?php if (!$isEdit): ?>
    <button type="submit" name="action" value="draft" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('tooltip_save_draft', 'Save and continue editing later'); ?>"><i class="bx bx-save me-1"></i><?php echo t('btn_save_draft', 'Save as Draft'); ?></button>
    <div class="btn-group">
        <button type="submit" name="action" value="save" class="btn btn-sm btn-primary px-3" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('tooltip_save', 'Save transaction'); ?>"><i class="bx bx-check me-1"></i>Save</button>
        <button type="button" class="btn btn-sm btn-primary dropdown-toggle dropdown-toggle-split ps-2 pe-2" data-bs-toggle="dropdown" aria-expanded="false">
            <span class="visually-hidden">Save options</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width:195px;font-size:.82rem;">
            <li><span class="dropdown-header py-1" style="font-size:.65rem;letter-spacing:.4px;">SAVE &amp; PRINT</span></li>
            <li><button type="submit" class="dropdown-item py-1" name="action" value="save_a4"><i class="bx bx-file text-primary me-2"></i><?php echo t('btn_save_a4', 'Save & Print A4'); ?></button></li>
            <li><button type="submit" class="dropdown-item py-1" name="action" value="save_a5"><i class="bx bx-file-blank text-info me-2"></i><?php echo t('btn_save_a5', 'Save & Print A5'); ?></button></li>
            <li><button type="submit" class="dropdown-item py-1" name="action" value="save_thermal"><i class="bx bx-receipt text-success me-2"></i><?php echo t('btn_save_thermal', 'Save & Print Thermal'); ?></button></li>
        </ul>
    </div>
    <?php elseif ($isDraftEdit): ?>
    <button type="submit" name="action" value="draft" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('tooltip_save_draft', 'Save and continue editing later'); ?>"><i class="bx bx-save me-1"></i><?php echo t('btn_save_draft', 'Save as Draft'); ?></button>
    <button type="submit" name="action" value="save" class="btn btn-sm btn-primary<?php echo $_hSavePx3 ? ' px-3' : ''; ?>" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('tooltip_save', 'Save transaction'); ?>"><i class="bx bx-check me-1"></i>Save</button>
    <?php else: ?>
    <button type="submit" name="action" value="save" class="btn btn-sm btn-primary<?php echo $_hSavePx3 ? ' px-3' : ''; ?>" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('tooltip_save', 'Save transaction'); ?>"><i class="bx bx-check me-1"></i>Save</button>
    <?php endif; ?>

<?php endif; ?>

    <a href="<?php echo $_closeUrl; ?>" class="btn btn-sm btn-outline-danger px-3<?php echo $_hHideNav ? ' d-none' : ''; ?>" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('tooltip_close', 'Return to list'); ?>"><i class="bx bx-x me-1"></i>Close</a>
</div>
