<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$isEdit      = isset($PurchData);
$isDraftEdit = $isEdit && ($PurchData->DocStatus === 'Draft');
$transUID    = $isEdit ? (int)$PurchData->TransUID : 0;
$formId      = 'purchForm';
$formAction  = $isEdit ? 'purchases/updatePurchase' : 'purchases/addPurchase';
extract(initTransFormCommon($isEdit, $PurchData ?? null, '/purchases', $JwtData));

$_prefix          = resolveTransPrefix($isEdit, $isDraftEdit, $PrefixData ?? [], $isEdit ? (int)($PurchData->PrefixUID ?? 0) : 0, $isEdit ? (int)($PurchData->TransNumber ?? 0) : 0, $NextNumberMap ?? []);
$editPrefixConfig = $_prefix['config'];
$editTransNumber  = $_prefix['transNumber'];
$editPrefixSeg    = $_prefix['seg'];

// Edit: parse stored Notes to split out PO Ref
$_poRef     = '';
$_userNotes = '';
if ($isEdit) {
    $_rawNotes  = $PurchData->Notes ?? '';
    $_userNotes = $_rawNotes;
    if (preg_match('/^\[PO Ref: (.*?)\]\s*(.*)$/s', $_rawNotes, $_m)) {
        $_poRef     = $_m[1];
        $_userNotes = trim($_m[2]);
    }
}

$_addrParts = buildDispatchAddressLines($DispatchAddress ?? null);

if ($isEdit) {
    $_b        = calcTransStatusBadge($PurchData, ['Issued' => 'primary', 'Partial' => 'info', 'Paid' => 'success', 'Cancelled' => 'danger', 'Rejected' => 'secondary', 'Draft' => 'secondary'], $JwtData);
    $hNetAmt   = $_b['netAmt'];   $hPaidAmt  = $_b['paidAmt'];   $hBalAmt   = $_b['balAmt'];
    $hDecimals = $_b['decimals']; $hCurrency = $_b['currency'];  $hStatus   = $_b['status'];  $hStatusClr = $_b['statusClr'];
}
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
                    'id'               => $formId,
                    'name'             => $formId,
                    'autocomplete'     => 'off',
                    'data-csrf'        => $this->security->get_csrf_token_name(),
                    'data-csrf-value'  => $this->security->get_csrf_hash(),
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
                                <div class="trans-doc-icon" style="background-color:#f0ebff;">
                                    <i class="bx bx-cart" style="font-size:1.1rem;color:#6f42c1;"></i>
                                </div>
                                <div>
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <span class="fw-bold" style="font-size:.92rem;">
                                            <?php echo $isEdit ? (($isDraftEdit ? '' : 'Edit ') . 'Purchase Bill') : 'Record Purchase Bill'; ?>
                                        </span>
                                        <?php if ($isEdit && !$isDraftEdit && !empty($PurchData->UniqueNumber)): ?>
                                            <span class="trans-form-doc-number"><?php echo htmlspecialchars($PurchData->UniqueNumber); ?></span>
                                        <?php endif; ?>

                                        <!-- Prefix / number block -->
                                        <?php if (!$isEdit): ?>
                                            <?php $this->load->view('transactions/partials/form_prefix_add'); ?>
                                        <?php else: ?>
                                            <?php $this->load->view('transactions/partials/form_prefix_edit', [
                                                '_editPrefixUID'  => (int)($PurchData->PrefixUID ?? 0),
                                                'editTransNumber' => $editTransNumber,
                                                'editPrefixSeg'   => $editPrefixSeg,
                                                'isDraftEdit'     => $isDraftEdit,
                                            ]); ?>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($isEdit && !$isDraftEdit): ?>
                                    <div class="d-flex align-items-center gap-3 mt-1">
                                        <div class="d-flex align-items-center gap-1">
                                            <span style="font-size:.7rem;color:#8592a3;">Bill Amount</span>
                                            <span style="font-size:.82rem;font-weight:600;"><?php echo $hCurrency . ' ' . smartDecimal($hNetAmt, $hDecimals, true); ?></span>
                                        </div>
                                        <?php if ($hPaidAmt > 0): ?>
                                        <div class="d-flex align-items-center gap-1">
                                            <span style="font-size:.7rem;color:#8592a3;">Paid</span>
                                            <span style="font-size:.82rem;font-weight:600;color:#28a745;"><?php echo $hCurrency . ' ' . smartDecimal($hPaidAmt, $hDecimals, true); ?></span>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($hBalAmt > 0.009): ?>
                                        <div class="d-flex align-items-center gap-1">
                                            <span style="font-size:.7rem;color:#8592a3;">Balance</span>
                                            <span style="font-size:.82rem;font-weight:600;color:#dc3545;"><?php echo $hCurrency . ' ' . smartDecimal($hBalAmt, $hDecimals, true); ?></span>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($PurchData->TransDate)): ?>
                                        <div class="d-flex align-items-center gap-1">
                                            <span style="font-size:.7rem;color:#8592a3;">Date</span>
                                            <span style="font-size:.78rem;color:#566a7f;"><?php echo htmlspecialchars(format_datedisplay($PurchData->TransDate)); ?></span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php $this->load->view('transactions/partials/trans_form_header_btns', ['_hBtnLayout' => 'split', '_hDcMenu' => false, '_hEditSavePx3' => false, '_hIsEdit' => $isEdit, '_hIsDraftEdit' => $isDraftEdit, '_hCloseUrl' => $_closeUrl]); ?>
                        </div>

                        <div class="card-body card-body-form-static p-3">

                            <?php
                            $_tsSetting = strtolower($JwtData->TransSettings->DefaultTransactionType ?? 'regular');
                            $_tsDefault = ($_tsSetting === 'without_tax') ? 'Without_GST' : 'Regular';
                            $_purchType = !empty($PurchData->DocType ?? '') ? $PurchData->DocType : $_tsDefault;
                            ?>
                            <!-- ── Toolbar: Type & Deliver To ──────────────────────────────── -->
                            <?php $this->load->view('transactions/partials/trans_toolbar_type', ['_tbTypeValue' => $_purchType, '_tbFieldId' => 'purchaseType', '_tbFieldName' => 'purchaseType', '_tbEditGuardStrict' => true, '_tbDispatchLabel' => 'Deliver To', '_tbShowOnAccount' => false, '_tbIsEdit' => $isEdit, '_tbIsDraftEdit' => $isDraftEdit]); ?>

                            <!-- ── Row 1: Vendor | Supplier Invoice Date | Payment By | Reference ── -->
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

                                <!-- Supplier Invoice Date -->
                                <div class="col-auto" style="min-width:160px;">
                                    <label for="transDate" class="trans-field-label">
                                        <?php echo t('lbl_supplier_invoice_date', 'Supplier Invoice Date'); ?> <span class="text-danger">*</span>
                                        <i class="bx bx-help-circle ms-1 text-muted" style="font-size:.82rem;cursor:pointer;"
                                           data-bs-toggle="tooltip" data-bs-placement="top"
                                           title="The date printed on the supplier's invoice. Used for GST reporting and payment tracking."></i>
                                    </label>
                                    <?php $_fmt = $JwtData->GenSettings->FormDateFormat ?? 'd-m-Y'; ?>
                                    <?php if ($isEdit && !$isDraftEdit): ?>
                                        <input type="hidden" name="transDate" value="<?php echo htmlspecialchars(format_datedisplay($PurchData->TransDate, 'Y-m-d')); ?>" />
                                        <div class="input-group input-group-sm input-group-merge">
                                            <span class="input-group-text bg-white"><i class="icon-base bx bx-calendar"></i></span>
                                            <input type="text" class="form-control form-control-sm bg-white text-muted" style="cursor:default;" value="<?php echo htmlspecialchars(format_datedisplay($PurchData->TransDate, $_fmt)); ?>" readonly tabindex="-1" />
                                        </div>
                                    <?php else: ?>
                                        <div class="input-group input-group-sm input-group-merge">
                                            <span class="input-group-text bg-white"><i class="icon-base bx bx-calendar"></i></span>
                                            <input type="text" class="form-control form-control-sm bg-white" id="transDate_disp" readonly="readonly"
                                                value="<?php echo $isEdit ? format_datedisplay($PurchData->TransDate, $_fmt) : format_datedisplay(time(), $_fmt); ?>"
                                                required />
                                            <input type="hidden" id="transDate" name="transDate" value="<?php echo $isEdit ? htmlspecialchars(format_datedisplay($PurchData->TransDate, 'Y-m-d')) : format_datedisplay(time(), 'Y-m-d'); ?>" />
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Supplier Payment By -->
                                <div class="col-auto" style="min-width:150px;">
                                    <label for="billDueDate" class="trans-field-label">
                                        Payment By
                                        <i class="bx bx-help-circle ms-1 text-muted" style="font-size:.82rem;cursor:pointer;"
                                           data-bs-toggle="tooltip" data-bs-placement="top"
                                           title="The deadline by which you must pay your vendor."></i>
                                    </label>
                                    <div class="input-group input-group-sm input-group-merge">
                                        <span class="input-group-text bg-white"><i class="icon-base bx bx-calendar"></i></span>
                                        <input type="text" class="form-control form-control-sm bg-white" id="billDueDate_disp" readonly="readonly"
                                            value="<?php echo ($isEdit && !empty($PurchData->ValidityDate)) ? format_datedisplay($PurchData->ValidityDate, $_fmt) : format_datedisplay(date('Y-m-d'), $_fmt); ?>" />
                                        <input type="hidden" id="billDueDate" name="billDueDate" value="<?php echo ($isEdit && !empty($PurchData->ValidityDate)) ? htmlspecialchars(format_datedisplay($PurchData->ValidityDate, 'Y-m-d')) : date('Y-m-d'); ?>" />
                                    </div>
                                </div>

                                <!-- Supplier Invoice No -->
                                <div class="col-auto" style="min-width:150px;">
                                    <label for="supplierInvoiceNo" class="trans-field-label"><?php echo t('lbl_supplier_invoice_no', 'Supplier Invoice No.'); ?></label>
                                    <input type="text" id="supplierInvoiceNo" name="supplierInvoiceNo" class="form-control form-control-sm"
                                        placeholder="e.g. INV-2025-0042" maxlength="100"
                                        value="<?php echo $isEdit ? htmlspecialchars($PurchData->SupplierInvoiceNo ?? '') : ''; ?>" />
                                </div>

                                <!-- Reference — takes remaining width -->
                                <div class="col">
                                    <label for="referenceDetails" class="trans-field-label">
                                        <?php echo t('lbl_reference_po', 'Reference / PO No.'); ?>
                                        <i class="bx bx-help-circle ms-1 text-muted" style="font-size:.82rem;cursor:pointer;"
                                           data-bs-toggle="tooltip" data-bs-placement="top"
                                           title="Link the bill to a PO number, shipment reference, or sales person name."></i>
                                    </label>
                                    <input type="text" id="referenceDetails" name="referenceDetails" class="form-control form-control-sm"
                                        placeholder="e.g. PO-2025-001, Shipment #TRK456, Indent No: IND-88"
                                        maxlength="100"
                                        value="<?php echo $isEdit ? htmlspecialchars($PurchData->Reference ?? '') : (!empty($POData->UniqueNumber) ? htmlspecialchars($POData->UniqueNumber) : ''); ?>" />
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
                                'transNotesContent'     => $isEdit ? $_userNotes : '',
                                'transHideTerms'        => empty($JwtData->TransSettings->PurchaseShowTerms),
                                'transTermsContent'     => $isEdit ? ($PurchData->TermsConditions ?? '') : ($JwtData->TransSettings->TermsAndConditions ?? ''),
                                'transShowDropzone'     => true,
                                'transShowSignature'    => !empty($JwtData->TransSettings->PurchaseShowSignature),
                                'transSignatureUID'     => $isEdit ? (int)($PurchData->SignatureUID ?? 0) : 0,
                                'transPaymentVars'      => !$isEdit ? [
                                    'PaymentTypes'     => $PaymentTypes ?? [],
                                    'BankAccounts'     => $BankAccounts ?? [],
                                    'JwtData'          => $JwtData,
                                    'paymentPartyType' => 'V',
                                ] : null,
                                'transEditItems'        => $isEdit ? ($PurchItems ?? []) : [],
                            ]); ?>

                            <?php $this->load->view('transactions/partials/trans_summary_bar', ['_barIsSticky' => false, '_barSections' => 'full4', '_barButtonLayout' => 'split', '_barShowPrint' => 'draft_or_create', '_barUseDcClasses' => false, '_barIsEdit' => $isEdit, '_barIsDraftEdit' => $isDraftEdit]); ?>

                        </div> <!-- /card-body -->
                    </div> <!-- /card -->

                    <?php $this->load->view('transactions/partials/trans_summary_bar', ['_barIsSticky' => true, '_barSections' => 'full4', '_barButtonLayout' => 'split', '_barShowPrint' => 'draft_or_create', '_barUseDcClasses' => false, '_barIsEdit' => $isEdit, '_barIsDraftEdit' => $isDraftEdit]); ?>

                    <?php echo form_close(); ?>

                </div>
            </div>

            <?php $this->load->view('common/transactions/transprefix'); ?>
            <?php $this->load->view('transactions/modals/vendor'); ?>
            <?php $this->load->view('transactions/modals/vendor_search'); ?>
            <?php $this->load->view('transactions/partials/form_common_modals'); ?>

        </div>

    </div>
</div>

<?php $this->load->view('transactions/partials/additional_charges_modal'); ?>
<?php $this->load->view('common/imagepreview_modal'); ?>
<?php $this->load->view('common/transactions/footer'); ?>

<script src="/js/transactions/purchases.js"></script>
<script src="/js/transactions/vendor_search.js"></script>
<script src="/js/transactions/transactions.js"></script>
<script src="/js/transactions/transprefix.js"></script>
<script src="/js/transactions/modaladdress.js"></script>
<script src="/js/common/category_form.js"></script>
<script src="/js/common/product_form.js"></script>
<?php if (!$isEdit): ?>
<script src="/js/transactions/payment_section.js"></script>
<?php endif; ?>
<script src="/js/transactions/attachments.js"></script>
<?php $this->load->view('transactions/partials/additional_charges_data'); ?>

<script>
var _transFormData = <?php echo json_encode([
    'isEdit'        => $isEdit,
    'isDraftEdit'   => $isDraftEdit,
    'moduleUID'     => 105,
    'enableStorage' => (bool)$JwtData->GenSettings->EnableStorage,
    'formId'        => $formId,
    'formAction'    => $formAction,
    'updateAction'  => 'purchases/updatePurchase',
    'orgState'      => $DispatchAddress->StateText ?? '',
    'upstashUrl'    => $UpstashReadUrl   ?? '',
    'upstashToken'  => $UpstashReadToken ?? '',
    'vendorCacheKey'=> $VendorCacheKey   ?? '',
    'returnTab'     => $_returnTab,
    'returnPage'    => (int)$_returnPage,
    'currency'      => $JwtData->GenSettings->CurrenySymbol ?? '₹',
    'decimals'      => (int)($JwtData->GenSettings->DecimalPoints ?? 2),
    'editData'      => $isEdit ? [
        'transUID'          => $transUID,
        'vendorUID'         => (int)($PurchData->PartyUID ?? 0),
        'vendorName'        => $PurchData->PartyName  ?? '',
        'vendorArea'        => $PurchData->PartyArea   ?? '',
        'vendorMobile'      => $PurchData->PartyMobile ?? '',
        'vendorState'       => isset($VendorAddr) ? ($VendorAddr->StateText ?? '') : '',
        'extraDiscAmount'   => (float)($PurchData->ExtraDiscAmount ?? 0),
        'extraDiscType'     => $PurchData->ExtraDiscType ?? '',
        'globalDiscPercent' => (float)($PurchData->GlobalDiscPercent ?? 0),
        'attachments'       => $PurchAttachments ?? [],
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
        }, $PurchItems ?? []),
    ] : null,
    'fromPO'      => (!$isEdit && !empty($POData)) ? [
        'uid'        => (int)$POData->TransUID,
        'vendorUID'  => (int)$POData->PartyUID,
        'vendorName' => $POData->PartyName ?? '',
    ] : null,
    'fromPOItems' => (!$isEdit && !empty($POItems)) ? array_map(function($item) {
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
    }, $POItems) : null,
]); ?>;
</script>
<script src="/js/transactions/forms/purchase.js"></script>
