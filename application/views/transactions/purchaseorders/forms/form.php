<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$isEdit      = isset($POData);
$isDraftEdit = $isEdit && ($POData->DocStatus === 'Draft');
$transUID    = $isEdit ? (int)$POData->TransUID : 0;
$formId      = 'poForm';
$formAction  = $isEdit ? 'purchaseorders/updatePurchaseOrder' : 'purchaseorders/addPurchaseOrder';
$_posCode    = $isEdit ? ($POData->PlaceOfSupplyCode  ?? '') : ($JwtData->Org->StateCode  ?? '');
$_posName    = $isEdit ? ($POData->PlaceOfSupplyName  ?? '') : ($JwtData->Org->StateName  ?? '');

$_returnTab  = $this->input->get('returnTab')  ?: 'All';
$_returnPage = (int)($this->input->get('returnPage') ?: 1);
$_closeUrl   = trans_build_close_url('/purchaseorders', $_returnTab, $_returnPage);

$editPrefixConfig = null;
if ($isEdit && !empty($PrefixData)) {
    foreach ($PrefixData as $_pd) {
        if ((int)$_pd->PrefixUID === (int)$POData->PrefixUID) { $editPrefixConfig = $_pd; break; }
    }
    if (!$editPrefixConfig) $editPrefixConfig = $PrefixData[0];
}

if ($isEdit && !function_exists('buildPOPrefixSegment')) {
    function buildPOPrefixSegment($cfg) {
        if (!$cfg) return '';
        $sep   = $cfg->Separator ?? '-';
        $parts = [$cfg->Name];
        if (!empty($cfg->IncludeShortName) && !empty($cfg->ShortName)) $parts[] = strtoupper($cfg->ShortName);
        if (!empty($cfg->IncludeFiscalYear)) {
            $m  = (int)date('m'); $yr = (int)date('Y'); $fy = $m >= 4 ? $yr : $yr - 1;
            $parts[] = ($cfg->FiscalYearFormat ?? 'SHORT') === 'LONG'
                ? $fy . '-' . ($fy + 1)
                : str_pad($fy % 100, 2, '0', STR_PAD_LEFT) . '-' . str_pad(($fy + 1) % 100, 2, '0', STR_PAD_LEFT);
        }
        return implode($sep, $parts) . $sep;
    }
}

$editTransNumber = ($isEdit && $isDraftEdit)
    ? (int)($NextNumberMap[(int)($editPrefixConfig->PrefixUID ?? 0)] ?? 1)
    : ($isEdit ? (int)$POData->TransNumber : 0);
$editPrefixSeg = ($isEdit && $isDraftEdit) ? buildPOPrefixSegment($editPrefixConfig) : '';
?>

<?php $this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-horizontal transactionPage layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

            <?php
                $FormAttribute = [
                    'id'              => $formId,
                    'name'            => $formId,
                    'autocomplete'    => 'off',
                    'data-csrf'       => $this->security->get_csrf_token_name(),
                    'data-csrf-value' => $this->security->get_csrf_hash(),
                ];
                echo form_open($formAction, $FormAttribute);
            ?>
                <?php if ($isEdit): ?>
                <input type="hidden" name="TransUID" value="<?php echo $transUID; ?>" />
                <?php endif; ?>
                <?php $this->load->view('transactions/partials/place_of_supply_inputs', ['_posCode' => $_posCode, '_posName' => $_posName]); ?>

                    <div class="card mb-3">

                        <!-- ── Card Header ── -->
                        <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2 trans-header-static trans-theme modal-header-center-sticky">
                            <div class="d-flex align-items-center gap-3" id="transHeaderInfo">
                                <?php $this->load->view('transactions/partials/form_back_button'); ?>
                                <div class="trans-doc-icon" style="background-color:#e0f5f2;">
                                    <i class="bx bx-purchase-tag-alt" style="font-size:1.1rem;color:#0f766e;"></i>
                                </div>
                                <div>
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <span class="fw-bold" style="font-size:.92rem;">
                                            <?php echo $isEdit ? (($isDraftEdit ? '' : 'Edit ') . 'Purchase Order') : 'Create Purchase Order'; ?>
                                        </span>
                                        <?php if ($isEdit && !$isDraftEdit && !empty($POData->UniqueNumber)): ?>
                                            <span class="trans-form-doc-number"><?php echo htmlspecialchars($POData->UniqueNumber); ?></span>
                                        <?php endif; ?>

                                        <!-- Prefix / number block -->
                                        <?php if (!$isEdit): ?>
                                            <?php $this->load->view('transactions/partials/form_prefix_add'); ?>
                                        <?php else: ?>
                                            <?php $this->load->view('transactions/partials/form_prefix_edit', [
                                                '_editPrefixUID'  => (int)($POData->PrefixUID   ?? 0),
                                                'editTransNumber' => $editTransNumber,
                                                'editPrefixSeg'   => $editPrefixSeg,
                                                'isDraftEdit'     => $isDraftEdit,
                                            ]); ?>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($isEdit && !$isDraftEdit && !empty($POData->TransDate)): ?>
                                    <div class="d-flex align-items-center gap-2 mt-1">
                                        <span style="font-size:.7rem;color:#8592a3;">PO Date</span>
                                        <span style="font-size:.78rem;color:#566a7f;"><?php echo htmlspecialchars(format_datedisplay($POData->TransDate)); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <?php $_hideNav = (int)($JwtData->TransSettings->HideNavOnTransForm ?? 0); ?>
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
                                    <button type="submit" name="action" value="save" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('tooltip_save', 'Save transaction'); ?>"><i class="bx bx-check me-1"></i>Save</button>
                                <?php else: ?>
                                    <button type="submit" name="action" value="save" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('tooltip_save', 'Save transaction'); ?>"><i class="bx bx-check me-1"></i>Save</button>
                                <?php endif; ?>
                                <a href="<?php echo $_closeUrl; ?>" class="btn btn-sm btn-outline-danger px-3<?php echo $_hideNav ? ' d-none' : ''; ?>" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('tooltip_close', 'Return to list'); ?>"><i class="bx bx-x me-1"></i>Close</a>
                            </div>
                        </div>

                        <div class="card-body card-body-form-static p-3">

                            <!-- ── Toolbar: Type & Deliver To ──────────────────────────────── -->
                            <div class="d-flex align-items-center gap-4 mb-3 pb-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-muted" style="font-size:.78rem;white-space:nowrap;">Type</span>
                                    <?php if ($isEdit && !$isDraftEdit): ?>
                                    <?php
                                    $_tsSetting = strtolower($JwtData->TransSettings->DefaultTransactionType ?? 'regular');
                                    $_tsDefault = ($_tsSetting === 'without_tax') ? 'Without_GST' : 'Regular';
                                    $_poType    = !empty($POData->DocType) ? $POData->DocType : $_tsDefault;
                                    ?>
                                    <span class="trans-type-readonly"><?php echo $_poType === 'Without_GST' ? 'Without GST' : 'Regular'; ?></span>
                                    <input type="hidden" name="poType" value="<?php echo htmlspecialchars($_poType); ?>" />
                                    <?php else: ?>
                                    <select class="form-select form-select-sm border-0 bg-transparent fw-semibold trans-gst-type-select"
                                            id="poType" name="poType" style="min-width:110px;cursor:pointer;" required>
                                        <option value="Regular" <?php echo (!$isEdit || ($POData->DocType ?? '') === 'Regular' || empty($POData->DocType ?? '')) ? 'selected' : ''; ?>>Regular</option>
                                        <option value="Without_GST" <?php echo ($POData->DocType ?? '') === 'Without_GST' ? 'selected' : ''; ?>>Without GST</option>
                                    </select>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($DispatchAddresses)): ?>
                                <div class="d-flex align-items-center gap-2 dispatch-from-grp" style="max-width:360px;">
                                    <span class="text-muted" style="font-size:.78rem;white-space:nowrap;">Deliver To</span>
                                    <?php $this->load->view('common/transactions/_dispatch_from'); ?>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- ── Row 1: Vendor | PO Date | Expected Delivery Date | Reference ── -->
                            <div class="row g-2 align-items-end mb-2">

                                <div class="col-md-4">
                                    <?php if ($isEdit && !$isDraftEdit): ?>
                                        <label class="trans-field-label mb-1">Vendor</label>
                                        <select id="vendorSearch" name="vendorSearch" class="form-select form-select-sm"></select>
                                    <?php else: ?>
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <label for="vendorSearch" class="trans-field-label mb-0">Vendor <span class="text-danger">*</span></label>
                                            <button type="button" id="addTransVendor" class="trans-add-btn btn btn-outline-primary btn-sm" style="font-size:.72rem;white-space:nowrap;" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('tooltip_create_vendor', 'Create Vendor'); ?>"><i class="bx bx-plus-circle me-1"></i><?php echo t('btn_add_vendor', 'Add Vendor'); ?></button>
                                        </div>
                                        <select id="vendorSearch" name="vendorSearch" class="form-select form-select-sm"></select>
                                    <?php endif; ?>
                                </div>

                                <!-- PO Date -->
                                <div class="col-auto" style="min-width:160px;">
                                    <label for="transDate" class="trans-field-label"><?php echo t('lbl_po_date', 'PO Date'); ?> <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-sm input-group-merge">
                                        <span class="input-group-text bg-white"><i class="icon-base bx bx-calendar"></i></span>
                                        <?php $_fmt = $JwtData->GenSettings->FormDateFormat ?? 'd-m-Y'; ?>
                                        <input type="text" class="form-control form-control-sm bg-white" id="transDate_disp" readonly="readonly"
                                            value="<?php echo $isEdit ? format_datedisplay($POData->TransDate, $_fmt) : format_datedisplay(time(), $_fmt); ?>"
                                            required />
                                        <input type="hidden" id="transDate" name="transDate" value="<?php echo $isEdit ? htmlspecialchars(format_datedisplay($POData->TransDate, 'Y-m-d')) : format_datedisplay(time(), 'Y-m-d'); ?>" />
                                    </div>
                                </div>

                                <!-- Expected Delivery Date -->
                                <div class="col-auto" style="min-width:160px;">
                                    <label for="expectedDate" class="trans-field-label"><?php echo t('lbl_expected_delivery_date', 'Expected Delivery Date'); ?></label>
                                    <div class="input-group input-group-sm input-group-merge">
                                        <span class="input-group-text bg-white"><i class="icon-base bx bx-calendar"></i></span>
                                        <input type="text" class="form-control form-control-sm bg-white" id="expectedDate_disp" readonly="readonly"
                                            value="<?php echo ($isEdit && !empty($POData->ValidityDate)) ? format_datedisplay($POData->ValidityDate, $_fmt) : format_datedisplay(date('Y-m-d'), $_fmt); ?>" />
                                        <input type="hidden" id="expectedDate" name="expectedDate" value="<?php echo ($isEdit && !empty($POData->ValidityDate)) ? htmlspecialchars(format_datedisplay($POData->ValidityDate, 'Y-m-d')) : date('Y-m-d'); ?>" />
                                    </div>
                                </div>

                                <!-- Reference — takes remaining width -->
                                <div class="col">
                                    <label for="referenceDetails" class="trans-field-label"><?php echo t('lbl_reference', 'Reference'); ?></label>
                                    <input type="text" id="referenceDetails" name="referenceDetails" class="form-control form-control-sm"
                                        placeholder="Ref No, Order No..."
                                        maxlength="100"
                                        value="<?php echo $isEdit ? htmlspecialchars($POData->Reference ?? '') : ''; ?>" />
                                </div>

                            </div>

                            <!-- Vendor address box -->
                            <div class="row g-2 mb-3">
                                <div class="col-md-4">
                                    <div id="vendorAddressBox" class="p-2 border border-secondary trans-border-dotted rounded small d-none"></div>
                                </div>
                            </div>
                            <hr class="mt-2 mb-3"/>

                            <?php $this->load->view('transactions/partials/form_products_add', [
                                'transNotesPlaceholder' => 'Enter notes or anything else',
                                'transNotesContent'     => $isEdit ? ($POData->Notes ?? '') : '',
                                'transTermsContent'     => $isEdit ? ($POData->TermsConditions ?? '') : ($JwtData->TransSettings->TermsAndConditions ?? ''),
                                'transShowDropzone'     => true,
                                'transSignatureUID'     => $isEdit ? (int)($POData->SignatureUID ?? 0) : 0,
                                'transEditItems'        => $isEdit ? ($POItems ?? []) : [],
                            ]); ?>

                            <!-- ── Inline full-width summary ── -->
                            <?php $cur = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹'); ?>
                            <div id="inlineSummaryBar" class="sticky-bottom-bar mt-3" style="padding:10px 24px;display:flex;align-items:center;justify-content:space-between;gap:16px;border-radius:8px;">
                                <div class="d-flex align-items-stretch gap-0">
                                    <div style="padding-right:20px;">
                                        <div class="fw-bold" style="font-size:.95rem;">TOTAL &nbsp;<span style="color:#0d6efd;" id="inlineGrandTotal"><?php echo $cur; ?> 0.00</span></div>
                                        <div class="text-muted" style="font-size:.74rem;">Includes Total Tax &nbsp;<span id="inlineTotalTax">0.00</span></div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <?php if (!$isEdit || $isDraftEdit): ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="inlineDraftBtn" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo t('tooltip_save_draft', 'Save and continue editing later'); ?>"><i class="bx bx-save me-1"></i><?php echo t('btn_save_draft', 'Save as Draft'); ?></button>
                                    <?php endif; ?>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-primary px-3" id="inlineSaveBtn">
                                            <i class="bx bx-check me-1"></i>Save
                                        </button>
                                        <?php if (!$isEdit || $isDraftEdit): ?>
                                        <button type="button" class="btn btn-sm btn-primary dropdown-toggle dropdown-toggle-split ps-2 pe-2" data-bs-toggle="dropdown" aria-expanded="false">
                                            <span class="visually-hidden">Save options</span>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow dropup" style="min-width:195px;font-size:.82rem;">
                                            <li><span class="dropdown-header py-1" style="font-size:.65rem;letter-spacing:.4px;">SAVE &amp; PRINT</span></li>
                                            <li><button type="button" class="dropdown-item py-1" data-inline-action="save_a4"><i class="bx bx-file text-primary me-2"></i><?php echo t('btn_save_a4', 'Save & Print A4'); ?></button></li>
                                            <li><button type="button" class="dropdown-item py-1" data-inline-action="save_a5"><i class="bx bx-file-blank text-info me-2"></i><?php echo t('btn_save_a5', 'Save & Print A5'); ?></button></li>
                                            <li><button type="button" class="dropdown-item py-1" data-inline-action="save_thermal"><i class="bx bx-receipt text-success me-2"></i><?php echo t('btn_save_thermal', 'Save & Print Thermal'); ?></button></li>
                                        </ul>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- ── Sticky bottom summary bar ── -->
                    <div id="stickyBottomBar" class="sticky-bottom-bar" style="position:fixed;bottom:0;right:0;z-index:1040;padding:10px 24px;display:flex;align-items:center;justify-content:space-between;gap:16px;">
                        <div class="d-flex align-items-stretch gap-0">
                            <div style="padding-right:20px;">
                                <div class="fw-bold" style="font-size:.95rem;">TOTAL &nbsp;<span style="color:#0d6efd;" id="stickyGrandTotal"><?php echo $cur; ?> 0.00</span></div>
                                <div class="text-muted" style="font-size:.74rem;">Includes Total Tax &nbsp;<span id="stickyTotalTax">0.00</span></div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <?php if (!$isEdit || $isDraftEdit): ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="stickyDraftBtn" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo t('tooltip_save_draft', 'Save and continue editing later'); ?>"><i class="bx bx-save me-1"></i><?php echo t('btn_save_draft', 'Save as Draft'); ?></button>
                            <?php endif; ?>
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-primary px-3" id="stickySaveBtn" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo t('tooltip_save', 'Save transaction'); ?>">
                                    <i class="bx bx-check me-1"></i>Save
                                </button>
                                <?php if (!$isEdit || $isDraftEdit): ?>
                                <button type="button" class="btn btn-sm btn-primary dropdown-toggle dropdown-toggle-split ps-2 pe-2" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="visually-hidden">Save options</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow dropup" style="min-width:195px;font-size:.82rem;">
                                    <li><span class="dropdown-header py-1" style="font-size:.65rem;letter-spacing:.4px;">SAVE &amp; PRINT</span></li>
                                    <li><button type="button" class="dropdown-item py-1" data-sticky-action="save_a4"><i class="bx bx-file text-primary me-2"></i><?php echo t('btn_save_a4', 'Save & Print A4'); ?></button></li>
                                    <li><button type="button" class="dropdown-item py-1" data-sticky-action="save_a5"><i class="bx bx-file-blank text-info me-2"></i><?php echo t('btn_save_a5', 'Save & Print A5'); ?></button></li>
                                    <li><button type="button" class="dropdown-item py-1" data-sticky-action="save_thermal"><i class="bx bx-receipt text-success me-2"></i><?php echo t('btn_save_thermal', 'Save & Print Thermal'); ?></button></li>
                                </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <?php echo form_close(); ?>

                </div>
            </div>

            <?php $this->load->view('common/transactions/transprefix'); ?>
            <?php $this->load->view('transactions/modals/vendor_search'); ?>
            <?php $this->load->view('transactions/partials/form_common_modals'); ?>

        </div>

    </div>
</div>

<?php $this->load->view('transactions/partials/additional_charges_modal'); ?>
<?php $this->load->view('common/imagepreview_modal'); ?>
<?php $this->load->view('common/transactions/footer'); ?>

<script src="/js/transactions/purchaseorders.js"></script>
<script src="/js/transactions/vendor_search.js"></script>
<script src="/js/transactions/transactions.js"></script>
<script src="/js/transactions/transprefix.js"></script>
<script src="/js/transactions/modaladdress.js"></script>
<script src="/js/common/category_form.js"></script>
<script src="/js/common/product_form.js"></script>
<script src="/js/transactions/attachments.js"></script>
<?php $this->load->view('transactions/partials/additional_charges_data'); ?>

<script>
var _transFormData = <?php echo json_encode([
    'isEdit'        => $isEdit,
    'isDraftEdit'   => $isDraftEdit,
    'moduleUID'     => 104,
    'enableStorage' => (bool)$JwtData->GenSettings->EnableStorage,
    'formId'        => $formId,
    'formAction'    => $formAction,
    'upstashUrl'    => $UpstashReadUrl   ?? '',
    'upstashToken'  => $UpstashReadToken ?? '',
    'vendorCacheKey'=> $VendorCacheKey   ?? '',
    'returnTab'     => $_returnTab,
    'returnPage'    => (int)$_returnPage,
    'currency'      => $JwtData->GenSettings->CurrenySymbol ?? '₹',
    'decimals'      => (int)($JwtData->GenSettings->DecimalPoints ?? 2),
    'editData'      => $isEdit ? [
        'transUID'          => $transUID,
        'vendorUID'         => (int)($POData->PartyUID ?? 0),
        'vendorName'        => $POData->PartyName  ?? '',
        'vendorArea'        => $POData->PartyArea   ?? '',
        'vendorMobile'      => $POData->PartyMobile ?? '',
        'vendorState'       => isset($VendorAddr) ? ($VendorAddr->StateText ?? '') : '',
        'extraDiscAmount'   => (float)($POData->ExtraDiscAmount ?? 0),
        'extraDiscType'     => $POData->ExtraDiscType ?? '',
        'globalDiscPercent' => (float)($POData->GlobalDiscPercent ?? 0),
        'attachments'       => $POAttachments ?? [],
        'items'             => array_map(function($item) {
            return [
                'id'               => (int)  $item->ProductUID,
                'text'             => $item->ProductName,
                'itemName'         => $item->ProductName,
                'description'      => $item->Description   ?? '',
                'unitPrice'        => (float)$item->UnitPrice,
                'taxAmount'        => (float)$item->TaxAmount,
                'sellingPrice'     => (float)$item->SellingPrice,
                'purchasePrice'    => (float)($item->PurchasePrice ?? 0),
                'mrp'             => (float)($item->MRP ?? 0),
                'purchasePriceIsIncl' => (bool)($item->IsPurchasePriceIncl ?? 1),
                'availableQuantity'=> 0,
                'hsnCode'          => '',
                'categoryUID'      => $item->CategoryUID ? (int)$item->CategoryUID : null,
                'categoryName'     => $item->CategoryName  ?? '',
                'storageUID'       => $item->StorageUID  ? (int)$item->StorageUID  : null,
                'taxPercent'       => (float)$item->TaxPercentage,
                'cgstPercent'      => (float)$item->CGST,
                'sgstPercent'      => (float)$item->SGST,
                'igstPercent'      => (float)$item->IGST,
                'taxDetailsUID'    => (int)  $item->TaxDetailsUID,
                'quantity'         => (float)$item->Quantity,
                'partNumber'       => $item->PartNumber      ?? '',
                'primaryUnit'      => $item->PrimaryUnitName ?? '',
                'discount'         => (float)$item->Discount,
                'discountType'     => 'Percentage',
                'discountTypeUID'  => $item->DiscountTypeUID ? (int)$item->DiscountTypeUID : null,
                'discount_amount'  => (float)$item->DiscountAmount,
                'line_total'       => (float)$item->TaxableAmount,
                'net_total'        => (float)$item->NetAmount,
            ];
        }, $POItems ?? []),
    ] : null,
]); ?>;
</script>
<script src="/js/transactions/forms/purchaseorder.js"></script>
