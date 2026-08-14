<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Toolbar: doc-type select/chip + optional dispatch-from + optional credits indicator.
 *
 * Parameters:
 *   string $_tbTypeValue       Current doc type: 'Regular'|'Without_GST' (pre-computed in parent)
 *   string $_tbFieldId         select id attribute (e.g. 'invoiceType', 'orderType', 'poType')
 *   string $_tbFieldName       select name attribute (may differ — SR uses name='returnType')
 *   bool   $_tbEditGuardStrict true  = use ($isEdit && !$isDraftEdit) for readonly chip (default)
 *                              false = use $isEdit only (SO/QT/PF lock type even in draftEdit)
 *   string $_tbDispatchLabel   dispatch-from label ('Dispatch From','Deliver To','Accepted At')
 *   bool   $_tbShowOnAccount   true = render ms-auto credits block (customer modules only)
 *   bool   $_tbOnAccountGuard  true = wrap credits block in if(!$isEdit) — INV/SO; false = always — QT/PF/SR
 *   bool   $_tbOaSrStyle       true = use SR alternate single-badge layout (On Account only)
 *
 *   bool   $_tbIsEdit          true when editing an existing transaction
 *   bool   $_tbIsDraftEdit     true when editing a Draft-status transaction
 */
$_tbType      = $_tbTypeValue       ?? 'Regular';
$_tbId        = $_tbFieldId         ?? 'invoiceType';
$_tbName      = $_tbFieldName       ?? $_tbId;
$_tbStrict    = $_tbEditGuardStrict ?? true;
$_tbDispLabel = $_tbDispatchLabel   ?? 'Dispatch From';
$_tbOa        = $_tbShowOnAccount    ?? false;
$_tbOaGuard   = $_tbOnAccountGuard   ?? true;
$_tbOaSr      = $_tbOaSrStyle        ?? false;
$_tbIsEdit    = $_tbIsEdit           ?? false;
$_tbIsDraft   = $_tbIsDraftEdit      ?? false;
$_tbReadonly  = $_tbStrict ? ($_tbIsEdit && !$_tbIsDraft) : $_tbIsEdit;
?>
<div class="d-flex align-items-center gap-4 mb-3 pb-2 border-bottom">
    <div class="d-flex align-items-center gap-2">
        <span class="text-muted" style="font-size:.78rem;white-space:nowrap;">Type</span>
        <?php if ($_tbReadonly): ?>
        <span class="trans-type-readonly"><?php echo $_tbType === 'Without_GST' ? 'Without GST' : 'Regular'; ?></span>
        <input type="hidden" id="<?php echo $_tbId; ?>" name="<?php echo $_tbName; ?>" value="<?php echo htmlspecialchars($_tbType); ?>" />
        <?php else: ?>
        <select class="form-select form-select-sm border-0 bg-transparent fw-semibold trans-gst-type-select"
                id="<?php echo $_tbId; ?>" name="<?php echo $_tbName; ?>" style="min-width:110px;cursor:pointer;" required>
            <option value="Regular"     <?php echo $_tbType !== 'Without_GST' ? 'selected' : ''; ?>>Regular</option>
            <option value="Without_GST" <?php echo $_tbType === 'Without_GST'  ? 'selected' : ''; ?>>Without GST</option>
        </select>
        <?php endif; ?>
    </div>
    <?php if (!empty($DispatchAddresses) && $_tbDispLabel): ?>
    <div class="d-flex align-items-center gap-2 dispatch-from-grp" style="max-width:360px;">
        <span class="text-muted" style="font-size:.78rem;white-space:nowrap;"><?php echo htmlspecialchars($_tbDispLabel); ?></span>
        <?php $this->load->view('common/transactions/_dispatch_from'); ?>
    </div>
    <?php endif; ?>
    <?php if ($_tbOa): ?>
    <?php if (!$_tbOaGuard || !$_tbIsEdit): ?>
    <div class="ms-auto d-flex align-items-center gap-2">
        <div id="custTypeIndicator" class="d-none"></div>
        <div id="plChipWrap" class="d-none"></div>
        <?php if ($_tbOaSr): ?>
        <div id="srOnAccountBadge" class="d-none trans-credits-badge">
            <i class="bx bx-wallet trans-credits-badge__icon"></i>
            On Account: <strong id="srOnAccountAmt" class="trans-credits-badge__amt"></strong>
        </div>
        <?php else: ?>
        <div id="onAccountIndicator" class="d-none trans-credits-badge">
            <i class="bx bx-wallet trans-credits-badge__icon"></i>
            Credits: <strong id="onAccountTotal" class="trans-credits-badge__amt"></strong>
        </div>
        <div id="creditNoteBadge" class="d-none trans-cn-badge">
            <i class="bx bx-receipt"></i>
            Credit Notes: <strong id="creditNoteTotal" class="trans-credits-badge__amt"></strong>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>
