<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Shared inline / sticky summary bar for all 9 transaction form pages.
 *
 * Parameters (passed via $this->load->view(..., [...])):
 *   bool   $_barIsSticky      true = stickyBottomBar, false = inlineSummaryBar
 *   string $_barSections      'full4' = Total+Paid+Balance+Excess
 *                             'paid_balance' = Total+Paid+Balance
 *                             '1' = Total only
 *   string $_barButtonLayout  'invoice' = draft+print-dropdown, save (INV only)
 *                             'split'   = draft, save+split-dropdown
 *   string $_barShowPrint     'draft_or_create' (default) = !$isEdit || $isDraftEdit
 *                             'create_only'               = !$isEdit (QT only)
 *   bool   $_barUseDcClasses   true = use dc-* CSS classes (delivery challan only)
 *   bool   $_barIsEdit         true when editing an existing transaction
 *   bool   $_barIsDraftEdit    true when editing a Draft-status transaction
 */
$_bSticky    = $_barIsSticky     ?? false;
$_bSections  = $_barSections     ?? '1';
$_bBtnLayout = $_barButtonLayout ?? 'split';
$_bShowPrint = $_barShowPrint    ?? 'draft_or_create';
$_bDcCls     = $_barUseDcClasses ?? false;
$_bIsEdit    = $_barIsEdit       ?? false;
$_bIsDraft   = $_barIsDraftEdit  ?? false;

$_bCur       = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '&#8377;');
$_bId        = $_bSticky ? 'stickyBottomBar' : 'inlineSummaryBar';
$_bAction    = $_bSticky ? 'data-sticky-action' : 'data-inline-action';
$_bDraftId   = $_bSticky ? 'stickyDraftBtn'    : 'inlineDraftBtn';
$_bSaveId    = $_bSticky ? 'stickySaveBtn'     : 'inlineSaveBtn';
$_bPfx       = $_bSticky ? 'sticky'            : 'inline';

// Outer wrapper classes + styles
if ($_bDcCls) {
    $_bWrapCls   = 'sticky-bottom-bar' . ($_bSticky ? ' dc-summary-bar dc-sticky-bar' : ' mt-3 dc-summary-bar');
    $_bWrapStyle = '';
} elseif ($_bSticky) {
    $_bWrapCls   = 'sticky-bottom-bar';
    $_bWrapStyle = 'position:fixed;bottom:0;right:0;z-index:1040;padding:10px 24px;display:flex;align-items:center;justify-content:space-between;gap:16px;';
} else {
    $_bWrapCls   = 'sticky-bottom-bar mt-3';
    $_bWrapStyle = 'padding:10px 24px;display:flex;align-items:center;justify-content:space-between;gap:16px;border-radius:8px;';
}

// Print-dropdown visibility condition
$_bShowPrintBtn = ($_bShowPrint === 'create_only') ? !$_bIsEdit : (!$_bIsEdit || $_bIsDraft);
?>
<!-- ── <?php echo $_bSticky ? 'Sticky bottom' : 'Inline'; ?> summary bar ── -->
<div id="<?php echo $_bId; ?>" class="<?php echo $_bWrapCls; ?>"<?php if ($_bWrapStyle): ?> style="<?php echo $_bWrapStyle; ?>"<?php endif; ?>>

    <!-- Left info sections -->
    <div class="d-flex align-items-stretch gap-0">

        <!-- Section 1: Total + Tax (always shown) -->
        <?php if ($_bDcCls): ?>
        <div class="dc-bar-left">
            <div class="fw-bold dc-bar-total">TOTAL &nbsp;<span class="dc-bar-grand" id="<?php echo $_bPfx; ?>GrandTotal"><?php echo $_bCur; ?> 0.00</span></div>
            <div class="text-muted dc-bar-tax">Includes Total Tax &nbsp;<span id="<?php echo $_bPfx; ?>TotalTax">0.00</span></div>
        </div>
        <?php else: ?>
        <div style="padding-right:20px;">
            <div class="fw-bold" style="font-size:.95rem;">TOTAL &nbsp;<span style="color:#0d6efd;" id="<?php echo $_bPfx; ?>GrandTotal"><?php echo $_bCur; ?> 0.00</span></div>
            <div class="text-muted" style="font-size:.74rem;">Includes Total Tax &nbsp;<span id="<?php echo $_bPfx; ?>TotalTax">0.00</span></div>
        </div>
        <?php endif; ?>

        <?php if ($_bSections !== '1'): ?>
        <!-- Section 2: Total Paid -->
        <div id="<?php echo $_bPfx; ?>PaidGroup" class="d-none d-flex align-items-stretch">
            <div style="width:1px;background:#c5dcff;margin:0 20px;flex-shrink:0;"></div>
            <div>
                <div style="font-size:.74rem;color:#198754;font-weight:600;"><i class="bx bx-check-circle me-1"></i>Total Paid</div>
                <div class="fw-bold" style="font-size:.92rem;color:#198754;"><span id="<?php echo $_bPfx; ?>TotalPaid"><?php echo $_bCur; ?> 0.00</span></div>
            </div>
        </div>

        <!-- Section 3: Balance -->
        <div id="<?php echo $_bPfx; ?>BalanceGroup" class="d-none d-flex align-items-stretch">
            <div style="width:1px;background:#c5dcff;margin:0 20px;flex-shrink:0;"></div>
            <div>
                <div style="font-size:.74rem;color:#dc3545;font-weight:600;"><i class="bx bx-wallet me-1"></i>Balance</div>
                <div class="fw-bold" style="font-size:.92rem;color:#dc3545;"><span id="<?php echo $_bPfx; ?>BalanceAmt"><?php echo $_bCur; ?> 0.00</span></div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($_bSections === 'full4'): ?>
        <!-- Section 4: Excess -->
        <div id="<?php echo $_bPfx; ?>ExcessGroup" class="d-none d-flex align-items-stretch">
            <div style="width:1px;background:#c5dcff;margin:0 20px;flex-shrink:0;"></div>
            <div>
                <div style="font-size:.74rem;color:#f59e0b;font-weight:600;"><i class="bx bx-error-circle me-1"></i>Excess</div>
                <div class="fw-bold" style="font-size:.92rem;color:#f59e0b;"><span id="<?php echo $_bPfx; ?>ExcessAmt"><?php echo $_bCur; ?> 0.00</span></div>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- Action buttons -->
    <div class="d-flex align-items-center gap-2">

        <?php if (!$_bIsEdit || $_bIsDraft): ?>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="<?php echo $_bDraftId; ?>"
                data-bs-toggle="tooltip" data-bs-placement="top"
                title="<?php echo t('tooltip_save_draft', 'Save and continue editing later'); ?>">
            <i class="bx bx-save me-1"></i><?php echo t('btn_save_draft', 'Save as Draft'); ?>
        </button>
        <?php endif; ?>

        <?php if ($_bBtnLayout === 'invoice'): ?>
        <?php if ($_bShowPrintBtn): ?>
        <div class="btn-group">
            <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle"
                    data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bx bx-printer me-1"></i><?php echo t('btn_save_print', 'Save &amp; Print'); ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow dropup" style="min-width:175px;font-size:.82rem;">
                <li><button type="button" class="dropdown-item py-1" <?php echo $_bAction; ?>="save_a4"><i class="bx bx-file text-primary me-2"></i><?php echo t('btn_save_a4', 'Save & Print A4'); ?></button></li>
                <li><button type="button" class="dropdown-item py-1" <?php echo $_bAction; ?>="save_a5"><i class="bx bx-file-blank text-info me-2"></i><?php echo t('btn_save_a5', 'Save & Print A5'); ?></button></li>
                <li><button type="button" class="dropdown-item py-1" <?php echo $_bAction; ?>="save_thermal"><i class="bx bx-receipt text-success me-2"></i><?php echo t('btn_save_thermal', 'Save & Print Thermal'); ?></button></li>
            </ul>
        </div>
        <?php endif; ?>
        <button type="button" class="btn btn-sm btn-primary px-3" id="<?php echo $_bSaveId; ?>"
                <?php if ($_bSticky): ?>data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo t('tooltip_save', 'Save transaction'); ?>"<?php endif; ?>>
            <i class="bx bx-check me-1"></i>Save
        </button>

        <?php else: /* split layout */ ?>
        <div class="btn-group">
            <button type="button" class="btn btn-sm btn-primary px-3" id="<?php echo $_bSaveId; ?>"
                    <?php if ($_bSticky): ?>data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo t('tooltip_save', 'Save transaction'); ?>"<?php endif; ?>>
                <i class="bx bx-check me-1"></i>Save
            </button>
            <?php if ($_bShowPrintBtn): ?>
            <button type="button" class="btn btn-sm btn-primary dropdown-toggle dropdown-toggle-split ps-2 pe-2"
                    data-bs-toggle="dropdown" aria-expanded="false">
                <span class="visually-hidden">Save options</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow dropup<?php echo $_bDcCls ? ' dc-save-menu' : ''; ?>"<?php if (!$_bDcCls): ?> style="min-width:195px;font-size:.82rem;"<?php endif; ?>>
                <li><span class="dropdown-header py-1"<?php if (!$_bDcCls): ?> style="font-size:.65rem;letter-spacing:.4px;"<?php endif; ?>>SAVE &amp; PRINT</span></li>
                <li><button type="button" class="dropdown-item py-1" <?php echo $_bAction; ?>="save_a4"><i class="bx bx-file text-primary me-2"></i><?php echo t('btn_save_a4', 'Save & Print A4'); ?></button></li>
                <li><button type="button" class="dropdown-item py-1" <?php echo $_bAction; ?>="save_a5"><i class="bx bx-file-blank text-info me-2"></i><?php echo t('btn_save_a5', 'Save & Print A5'); ?></button></li>
                <li><button type="button" class="dropdown-item py-1" <?php echo $_bAction; ?>="save_thermal"><i class="bx bx-receipt text-success me-2"></i><?php echo t('btn_save_thermal', 'Save & Print Thermal'); ?></button></li>
            </ul>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>
</div>
