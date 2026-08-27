<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$isEdit      = isset($PRData);
$isDraftEdit = $isEdit && ($PRData->DocStatus === 'Draft');
$transUID    = $isEdit ? (int)$PRData->TransUID : 0;
$formId      = 'prForm';
$formAction  = $isEdit ? 'purchasereturns/updatePurchaseReturn' : 'purchasereturns/addPurchaseReturn';
extract(initTransFormCommon($isEdit, $PRData ?? null, '/purchasereturns', $JwtData));
$_prMethod = $JwtData->TransSettings->PurchaseReturnItemMethod ?? 'Manual';

$_prefix          = resolveTransPrefix($isEdit, $isDraftEdit, $PrefixData ?? [], $isEdit ? (int)($PRData->PrefixUID ?? 0) : 0, $isEdit ? (int)($PRData->TransNumber ?? 0) : 0, $NextNumberMap ?? []);
$editPrefixConfig = $_prefix['config'];
$editTransNumber  = $_prefix['transNumber'];
$editPrefixSeg    = $_prefix['seg'];

if ($isEdit) {
    $_b        = calcTransStatusBadge($PRData, ['Approved' => 'primary', 'Partial' => 'info', 'Paid' => 'success', 'Cancelled' => 'danger', 'Draft' => 'secondary'], $JwtData);
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

                        <!-- Card Header -->
                        <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2 trans-header-static trans-theme modal-header-center-sticky">
                            <div class="d-flex align-items-center gap-3" id="transHeaderInfo">
                                <?php $this->load->view('transactions/partials/form_back_button'); ?>
                                <div class="trans-doc-icon" style="background-color:#e8f5e9;">
                                    <i class="bx bx-undo" style="font-size:1.1rem;color:#28a745;"></i>
                                </div>
                                <div>
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <span class="fw-bold" style="font-size:.92rem;">
                                            <?php echo $isEdit ? (($isDraftEdit ? '' : 'Edit ') . 'Purchase Return') : 'Create Purchase Return'; ?>
                                        </span>
                                        <?php if ($isEdit && !$isDraftEdit && !empty($PRData->UniqueNumber)): ?>
                                            <span class="trans-form-doc-number"><?php echo htmlspecialchars($PRData->UniqueNumber); ?></span>
                                        <?php endif; ?>

                                        <!-- Prefix / number block -->
                                        <?php if (!$isEdit): ?>
                                            <?php $this->load->view('transactions/partials/form_prefix_add'); ?>
                                        <?php else: ?>
                                            <?php $this->load->view('transactions/partials/form_prefix_edit', [
                                                '_editPrefixUID'  => (int)($PRData->PrefixUID   ?? 0),
                                                'editTransNumber' => $editTransNumber,
                                                'editPrefixSeg'   => $editPrefixSeg,
                                                'isDraftEdit'     => $isDraftEdit,
                                            ]); ?>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($isEdit && !$isDraftEdit): ?>
                                    <div class="d-flex align-items-center gap-3 mt-1">
                                        <div class="d-flex align-items-center gap-1">
                                            <span style="font-size:.7rem;color:#8592a3;">Return Amount</span>
                                            <span style="font-size:.82rem;font-weight:600;"><?php echo $hCurrency . ' ' . smartDecimal($hNetAmt, $hDecimals, true); ?></span>
                                        </div>
                                        <?php if ($hPaidAmt > 0): ?>
                                        <div class="d-flex align-items-center gap-1">
                                            <span style="font-size:.7rem;color:#8592a3;">Received</span>
                                            <span style="font-size:.82rem;font-weight:600;color:#28a745;"><?php echo $hCurrency . ' ' . smartDecimal($hPaidAmt, $hDecimals, true); ?></span>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($hBalAmt > 0.009): ?>
                                        <div class="d-flex align-items-center gap-1">
                                            <span style="font-size:.7rem;color:#8592a3;">Balance</span>
                                            <span style="font-size:.82rem;font-weight:600;color:#dc3545;"><?php echo $hCurrency . ' ' . smartDecimal($hBalAmt, $hDecimals, true); ?></span>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($PRData->TransDate)): ?>
                                        <div class="d-flex align-items-center gap-1">
                                            <span style="font-size:.7rem;color:#8592a3;">Date</span>
                                            <span style="font-size:.78rem;color:#566a7f;"><?php echo htmlspecialchars(format_datedisplay($PRData->TransDate)); ?></span>
                                        </div>
                                        <?php endif; ?>
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
                            $_prType    = !empty($PRData->DocType ?? '') ? $PRData->DocType : $_tsDefault;
                            ?>
                            <!-- ── Toolbar: Type & Dispatch From ──────────────────────────────── -->
                            <?php $this->load->view('transactions/partials/trans_toolbar_type', ['_tbTypeValue' => $_prType, '_tbFieldId' => 'purchaseType', '_tbFieldName' => 'purchaseType', '_tbEditGuardStrict' => true, '_tbDispatchLabel' => 'Dispatch From', '_tbShowOnAccount' => false, '_tbIsEdit' => $isEdit, '_tbIsDraftEdit' => $isDraftEdit]); ?>

                            <!-- ── Row 1: Vendor | Return Date | Reference ── -->
                            <div class="row g-2 align-items-end mb-2">

                                <div class="col-md-4">
                                    <?php if ($isEdit && !$isDraftEdit): ?>
                                        <label class="trans-field-label mb-1">Vendor</label>
                                        <select id="vendorSearch" name="vendorSearch" class="form-select form-select-sm"></select>
                                    <?php else: ?>
                                        <label for="vendorSearch" class="trans-field-label">Vendor <span class="text-danger">*</span></label>
                                        <select id="vendorSearch" name="vendorSearch" class="form-select form-select-sm"></select>
                                    <?php endif; ?>
                                </div>

                                <?php if ($_prMethod !== 'Manual'): ?>
                                <div class="col-md-3">
                                    <label for="fromPurchaseUID" class="trans-field-label">Purchase From</label>
                                    <select id="fromPurchaseUID" name="fromPurchaseUID" class="form-select form-select-sm" disabled>
                                        <option value="">-- Select Vendor First --</option>
                                    </select>
                                </div>
                                <?php endif; ?>

                                <!-- Return Date -->
                                <div class="col-auto" style="min-width:160px;">
                                    <label for="transDate" class="trans-field-label">
                                        <?php echo t('lbl_return_date', 'Return Date'); ?> <span class="text-danger">*</span>
                                    </label>
                                    <?php $_fmt = $JwtData->GenSettings->FormDateFormat ?? 'd-m-Y'; ?>
                                    <?php if ($isEdit && !$isDraftEdit): ?>
                                        <input type="hidden" name="transDate" value="<?php echo htmlspecialchars(format_datedisplay($PRData->TransDate, 'Y-m-d')); ?>" />
                                        <div class="input-group input-group-sm input-group-merge">
                                            <span class="input-group-text bg-white"><i class="icon-base bx bx-calendar"></i></span>
                                            <input type="text" class="form-control form-control-sm bg-white text-muted" style="cursor:default;" value="<?php echo htmlspecialchars(format_datedisplay($PRData->TransDate, $_fmt)); ?>" readonly tabindex="-1" />
                                        </div>
                                    <?php else: ?>
                                        <div class="input-group input-group-sm input-group-merge">
                                            <span class="input-group-text bg-white"><i class="icon-base bx bx-calendar"></i></span>
                                            <input type="text" class="form-control form-control-sm bg-white" id="transDate_disp" readonly="readonly"
                                                value="<?php echo $isEdit ? format_datedisplay($PRData->TransDate, $_fmt) : format_datedisplay(time(), $_fmt); ?>"
                                                required />
                                            <input type="hidden" id="transDate" name="transDate" value="<?php echo $isEdit ? htmlspecialchars(format_datedisplay($PRData->TransDate, 'Y-m-d')) : format_datedisplay(time(), 'Y-m-d'); ?>" />
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Reference -->
                                <div class="col">
                                    <label for="referenceDetails" class="trans-field-label"><?php echo t('lbl_reference_bill', 'Reference / Bill No.'); ?></label>
                                    <input type="text" id="referenceDetails" name="referenceDetails" class="form-control form-control-sm"
                                        placeholder="e.g. INV-2025-0042, PO No..."
                                        maxlength="100"
                                        value="<?php echo $isEdit ? htmlspecialchars($PRData->Reference ?? '') : ''; ?>" />
                                </div>

                            </div>

                            <!-- Vendor address box -->
                            <?php
                            $_vAddrText = '';
                            if ($isEdit && isset($VendorAddr) && !empty($VendorAddr)) {
                                $_vLineParts = array_filter([trim($VendorAddr->Line1 ?? ''), trim($VendorAddr->Line2 ?? '')]);
                                $_vLocParts  = array_filter([trim($VendorAddr->CityText ?? ''), trim($VendorAddr->StateText ?? '')]);
                                $_vLoc = implode(', ', $_vLocParts);
                                if (!empty(trim($VendorAddr->Pincode ?? ''))) $_vLoc .= ' – ' . trim($VendorAddr->Pincode);
                                $_vAddrParts = array_filter([implode(', ', $_vLineParts), $_vLoc]);
                                $_vAddrText  = implode(' · ', $_vAddrParts);
                            }
                            ?>
                            <div class="row g-2 mb-3">
                                <div class="col-md-4">
                                    <div id="vendorAddressBox" class="trans-addr-strip <?php echo !empty($_vAddrText) ? '' : 'd-none'; ?>">
                                        <i class="bx bx-map-pin"></i>
                                        <span><?php echo htmlspecialchars($_vAddrText); ?></span><button type="button" id="btnEditVendAddr" class="trans-addr-edit-btn" title="Edit billing address"><i class="bx bx-edit"></i></button>
                                    </div>
                                </div>
                            </div>
                            <hr class="mt-2 mb-3"/>

                            <?php $this->load->view('transactions/partials/form_products_add', [
                                'transProductSectionTitle' => 'Returned Products',
                                'transNotesPlaceholder'    => 'Reason for return',
                                'transNotesContent'        => $isEdit ? ($PRData->Notes ?? '') : '',
                                'transHideTerms'           => empty($JwtData->TransSettings->PurchaseShowTerms),
                                'transTermsContent'        => $isEdit ? ($PRData->TermsConditions ?? '') : ($JwtData->TransSettings->TermsAndConditions ?? ''),
                                'transShowDropzone'        => true,
                                'transShowSignature'       => !empty($JwtData->TransSettings->PurchaseShowSignature),
                                'transSignatureUID'        => $isEdit ? (int)($PRData->SignatureUID ?? 0) : 0,
                                'transHideAddProduct'      => true,
                                'transHideProductSearch'   => $_prMethod === 'Automatic',
                                'transPaymentVars'         => !$isEdit ? [
                                    'PaymentTypes'     => $PaymentTypes ?? [],
                                    'BankAccounts'     => $BankAccounts ?? [],
                                    'JwtData'          => $JwtData,
                                    'paymentPartyType' => 'V',
                                ] : null,
                                'transEditItems'           => $isEdit ? ($PRItems ?? []) : [],
                            ]); ?>

                            <?php $this->load->view('transactions/partials/trans_summary_bar', ['_barIsSticky' => false, '_barSections' => 'paid_balance', '_barButtonLayout' => 'split', '_barShowPrint' => 'draft_or_create', '_barUseDcClasses' => false, '_barIsEdit' => $isEdit, '_barIsDraftEdit' => $isDraftEdit]); ?>

                        </div>
                    </div>

                    <?php $this->load->view('transactions/partials/trans_summary_bar', ['_barIsSticky' => true, '_barSections' => 'paid_balance', '_barButtonLayout' => 'split', '_barShowPrint' => 'draft_or_create', '_barUseDcClasses' => false, '_barIsEdit' => $isEdit, '_barIsDraftEdit' => $isDraftEdit]); ?>

                    <?php echo form_close(); ?>

                </div>
            </div>

            <?php $this->load->view('common/transactions/transprefix'); ?>
            <?php $this->load->view('transactions/modals/vendor_search'); ?>
            <?php if ($_prMethod !== 'Manual'): ?>
            <?php $this->load->view('transactions/modals/purchase_items_select'); ?>
            <?php endif; ?>
            <?php $this->load->view('common/modals/vendor_form'); ?>
            <?php $this->load->view('transactions/partials/form_common_modals'); ?>

        </div>

    </div>
</div>

<?php $this->load->view('transactions/partials/additional_charges_modal'); ?>
<?php $this->load->view('common/imagepreview_modal'); ?>
<?php $this->load->view('common/transactions/footer'); ?>

<script src="/js/transactions/purchasereturns.js"></script>
<script src="/js/common/phone_cc_dropdown.js"></script>
<script src="/js/common/vendor_form.js"></script>
<script src="/js/transactions/forms/vendor_search.js"></script>
<script src="/js/common/address.js"></script>
<script src="/js/transactions/forms/bill_manager.js"></script>
<script src="/js/transactions/forms/transprefix.js"></script>
<script src="/js/transactions/forms/modaladdress.js"></script>
<script src="/js/common/category_form.js"></script>
<script src="/js/common/product_form.js"></script>
<?php if (!$isEdit): ?>
<script src="/js/transactions/forms/payment_section.js"></script>
<?php endif; ?>
<script src="/js/transactions/attachments.js"></script>
<?php $this->load->view('transactions/partials/additional_charges_data'); ?>

<script>
var _transFormData = <?php echo json_encode([
    'isEdit'        => $isEdit,
    'isDraftEdit'   => $isDraftEdit,
    'moduleUID'     => 108,
    'enableStorage' => (bool)$JwtData->GenSettings->EnableStorage,
    'formId'        => $formId,
    'formAction'    => $formAction,
    'updateAction'  => 'purchasereturns/updatePurchaseReturn',
    'upstashUrl'    => $UpstashReadUrl   ?? '',
    'upstashToken'  => $UpstashReadToken ?? '',
    'vendorCacheKey'=> $VendorCacheKey   ?? '',
    'returnTab'     => $_returnTab,
    'returnPage'    => (int)$_returnPage,
    'currency'      => $JwtData->GenSettings->CurrenySymbol ?? '₹',
    'decimals'      => 9,
    'prItemMethod'  => $_prMethod,
    'transType'     => 'PurchaseReturn',
    'editData'      => $isEdit ? [
        'transUID'          => $transUID,
        'vendorUID'         => (int)($PRData->PartyUID ?? 0),
        'vendorName'        => $PRData->PartyName  ?? '',
        'vendorArea'        => $PRData->PartyArea   ?? '',
        'vendorMobile'      => $PRData->PartyMobile ?? '',
        'vendBillLine1'     => isset($VendorAddr) ? ($VendorAddr->Line1     ?? '') : '',
        'vendBillLine2'     => isset($VendorAddr) ? ($VendorAddr->Line2     ?? '') : '',
        'vendBillCity'      => isset($VendorAddr) ? ($VendorAddr->CityText  ?? '') : '',
        'vendBillState'     => isset($VendorAddr) ? ($VendorAddr->StateText ?? '') : '',
        'vendBillPincode'   => isset($VendorAddr) ? ($VendorAddr->Pincode   ?? '') : '',
        'extraDiscAmount'   => (float)($PRData->ExtraDiscount ?? 0),
        'extraDiscType'     => $PRData->ExtraDiscountType ?? '',
        'globalDiscPercent' => (float)($PRData->GlobalDiscPercent ?? 0),
        'attachments'       => $PRAttachments ?? [],
        'items'             => array_map(function($item) use ($PRSerialsByProd) {
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
                'IsSerialTracked'  => (int)($item->IsSerialTracked  ?? 0),
                'serials'          => $PRSerialsByProd[(int)$item->ProductUID] ?? [],
            ];
        }, $PRItems ?? []),
    ] : null,
]); ?>;
</script>
<script src="/js/transactions/forms/serial_tracker.js"></script>
<script src="/js/transactions/forms/purchasereturn.js"></script>
