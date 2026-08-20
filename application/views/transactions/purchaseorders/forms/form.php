<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$isEdit      = isset($POData);
$isDraftEdit = $isEdit && ($POData->DocStatus === 'Draft');
$transUID    = $isEdit ? (int)$POData->TransUID : 0;
$formId      = 'poForm';
$formAction  = $isEdit ? 'purchaseorders/updatePurchaseOrder' : 'purchaseorders/addPurchaseOrder';
extract(initTransFormCommon($isEdit, $POData ?? null, '/purchaseorders', $JwtData));

$_prefix          = resolveTransPrefix($isEdit, $isDraftEdit, $PrefixData ?? [], $isEdit ? (int)($POData->PrefixUID ?? 0) : 0, $isEdit ? (int)($POData->TransNumber ?? 0) : 0, $NextNumberMap ?? []);
$editPrefixConfig = $_prefix['config'];
$editTransNumber  = $_prefix['transNumber'];
$editPrefixSeg    = $_prefix['seg'];
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
                            <?php $this->load->view('transactions/partials/trans_form_header_btns', ['_hBtnLayout' => 'invoice', '_hDcMenu' => false, '_hEditSavePx3' => false, '_hIsEdit' => $isEdit, '_hIsDraftEdit' => $isDraftEdit, '_hCloseUrl' => $_closeUrl]); ?>
                        </div>

                        <div class="card-body card-body-form-static p-3">

                            <?php
                            $_tsSetting = strtolower($JwtData->TransSettings->DefaultTransactionType ?? 'regular');
                            $_tsDefault = ($_tsSetting === 'without_tax') ? 'Without_GST' : 'Regular';
                            $_poType    = !empty($POData->DocType ?? '') ? $POData->DocType : $_tsDefault;
                            ?>
                            <!-- ── Toolbar: Type & Deliver To ──────────────────────────────── -->
                            <?php $this->load->view('transactions/partials/trans_toolbar_type', ['_tbTypeValue' => $_poType, '_tbFieldId' => 'poType', '_tbFieldName' => 'poType', '_tbEditGuardStrict' => true, '_tbDispatchLabel' => 'Deliver To', '_tbShowOnAccount' => false, '_tbIsEdit' => $isEdit, '_tbIsDraftEdit' => $isDraftEdit]); ?>

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
                                    <div id="vendorAddressBox" class="trans-addr-strip d-none"><i class="bx bx-map-pin"></i><span></span></div>
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

                            <?php $this->load->view('transactions/partials/trans_summary_bar', ['_barIsSticky' => false, '_barSections' => '1', '_barButtonLayout' => 'split', '_barShowPrint' => 'draft_or_create', '_barUseDcClasses' => false, '_barIsEdit' => $isEdit, '_barIsDraftEdit' => $isDraftEdit]); ?>

                        </div>
                    </div>

                    <?php $this->load->view('transactions/partials/trans_summary_bar', ['_barIsSticky' => true, '_barSections' => '1', '_barButtonLayout' => 'split', '_barShowPrint' => 'draft_or_create', '_barUseDcClasses' => false, '_barIsEdit' => $isEdit, '_barIsDraftEdit' => $isDraftEdit]); ?>

                    <?php echo form_close(); ?>

                </div>
            </div>

            <?php $this->load->view('common/transactions/transprefix'); ?>
            <?php $this->load->view('transactions/modals/vendor_search'); ?>
            <?php $this->load->view('common/modals/vendor_form'); ?>
            <?php $this->load->view('transactions/partials/form_common_modals'); ?>

        </div>

    </div>
</div>

<?php $this->load->view('transactions/partials/additional_charges_modal'); ?>
<?php $this->load->view('common/imagepreview_modal'); ?>
<?php $this->load->view('common/transactions/footer'); ?>

<script src="/js/transactions/purchaseorders.js"></script>
<script src="/js/common/phone_cc_dropdown.js"></script>
<script src="/js/common/vendor_form.js"></script>
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
    'updateAction'  => 'purchaseorders/updatePurchaseOrder',
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
                'brandUID'         => $item->BrandUID          ? (int)$item->BrandUID            : null,
                'variantUID'       => $item->VariantUID         ? (int)$item->VariantUID          : null,
                'variantLabel'     => $item->VariantLabel        ?? '',
                'brandName'        => $item->BrandName         ?? '',
                'IsBrandApplicable'=> (int)($item->IsBrandApplicable ?? 0),
            ];
        }, $POItems ?? []),
    ] : null,
]); ?>;
</script>
<script src="/js/transactions/forms/purchaseorder.js"></script>
