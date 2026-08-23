<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$isEdit      = isset($SRData);
$isDraftEdit = $isEdit && ($SRData->DocStatus === 'Draft');
$transUID    = $isEdit ? (int)$SRData->TransUID : 0;
$formId      = 'srForm';
$formAction  = $isEdit ? 'salesreturns/updateSalesReturn' : 'salesreturns/addSalesReturn';
extract(initTransFormCommon($isEdit, $SRData ?? null, '/salesreturns', $JwtData));
$_srMethod = $JwtData->TransSettings->SalesReturnItemMethod ?? 'Manual';

$_prefix          = resolveTransPrefix($isEdit, $isDraftEdit, $PrefixData ?? [], $isEdit ? (int)($SRData->PrefixUID ?? 0) : 0, $isEdit ? (int)($SRData->TransNumber ?? 0) : 0, $NextNumberMap ?? []);
$editPrefixConfig = $_prefix['config'];
$editTransNumber  = $_prefix['transNumber'];
$editPrefixSeg    = $_prefix['seg'];
?>

<?php
if ($isEdit) {
    $_b        = calcTransStatusBadge($SRData, ['Issued' => 'primary', 'Draft' => 'secondary', 'Cancelled' => 'danger', 'Rejected' => 'secondary'], $JwtData);
    $hNetAmt   = $_b['netAmt'];   $hDecimals = $_b['decimals']; $hCurrency = $_b['currency'];
    $hStatus   = $_b['status'];   $hStatusClr = $_b['statusClr'];
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
                        'id'           => $formId,
                        'name'         => $formId,
                        'autocomplete' => 'off',
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

                        <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2 trans-header-static trans-theme modal-header-center-sticky">
                            <div class="d-flex align-items-center gap-3" id="transHeaderInfo">
                                <?php $this->load->view('transactions/partials/form_back_button'); ?>
                                <div class="trans-doc-icon bg-danger bg-opacity-10">
                                    <i class="bx bx-undo text-danger" style="font-size:1.1rem;"></i>
                                </div>
                                <div>
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <?php if (!$isEdit): ?>
                                            <span class="fw-bold" style="font-size:.92rem;">Create Sales Return</span>
                                            <?php $this->load->view('transactions/partials/form_prefix_add'); ?>
                                        <?php else: ?>
                                            <span class="fw-bold" style="font-size:.92rem;"><?php echo $isDraftEdit ? '' : 'Edit'; ?> Sales Return</span>
                                            <?php if (!$isDraftEdit && !empty($SRData->UniqueNumber)): ?>
                                                <span class="trans-form-doc-number"><?php echo htmlspecialchars($SRData->UniqueNumber); ?></span>
                                                <span class="badge bg-label-<?php echo $hStatusClr; ?>" style="font-size:.7rem;"><?php echo $hStatus; ?></span>
                                            <?php endif; ?>
                                            <?php $this->load->view('transactions/partials/form_prefix_edit', [
                                                '_editPrefixUID'  => (int)($SRData->PrefixUID   ?? 0),
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
                                        <?php if (!empty($SRData->TransDate)): ?>
                                        <div class="d-flex align-items-center gap-1">
                                            <span style="font-size:.7rem;color:#8592a3;">Date</span>
                                            <span style="font-size:.78rem;color:#566a7f;"><?php echo htmlspecialchars(format_datedisplay($SRData->TransDate)); ?></span>
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
                            $_srType    = !empty($SRData->DocType ?? '') ? $SRData->DocType : $_tsDefault;
                            ?>
                            <!-- ── Toolbar: Type & Accepted At ─────────────────────────────── -->
                            <?php $this->load->view('transactions/partials/trans_toolbar_type', ['_tbTypeValue' => $_srType, '_tbFieldId' => 'invoiceType', '_tbFieldName' => 'returnType', '_tbEditGuardStrict' => true, '_tbDispatchLabel' => 'Accepted At', '_tbShowOnAccount' => true, '_tbOnAccountGuard' => false, '_tbOaSrStyle' => true, '_tbIsEdit' => $isEdit, '_tbIsDraftEdit' => $isDraftEdit]); ?>

                            <!-- ── Row 1: Customer | From Invoice | Return Date | Reference ── -->
                            <div class="row g-2 align-items-end mb-2">
                                <div class="col-md-4">
                                    <?php if ($isEdit && !$isDraftEdit): ?>
                                        <label class="trans-field-label mb-1">Customer</label>
                                        <select id="customerSearch" name="customerSearch" class="form-select form-select-sm"></select>
                                    <?php else: ?>
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <label for="customerSearch" class="trans-field-label mb-0">Customer <span class="text-danger">*</span></label>
                                        </div>
                                        <div class="input-group input-group-sm input-group-merge customer-search-group" id="customerGroup_customerSearch">
                                            <span class="input-group-text p-2 cursor-pointer party-search-icon" id="openCustomerSearchModal" style="background:#f0efff;border-color:#d9d8ff;color:#696cff;"><i class="icon-base bx bx-search"></i></span>
                                            <select id="customerSearch" name="customerSearch" class="form-select form-select-sm"></select>
                                            <span class="party-edit-icon" id="editCustomerBtn" title="Edit Customer"><i class="bx bx-edit-alt"></i></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php if ($_srMethod !== 'Manual'): ?>
                                <div class="col-md-3">
                                    <label for="fromInvoiceUID" class="trans-field-label">From Invoice</label>
                                    <select id="fromInvoiceUID" name="fromInvoiceUID" class="form-select form-select-sm" disabled>
                                        <option value="">-- Select Customer First --</option>
                                    </select>
                                </div>
                                <?php endif; ?>
                                <div class="col-auto" style="min-width:155px;">
                                    <label for="transDate" class="trans-field-label"><?php echo t('lbl_return_date', 'Return Date'); ?> <span class="text-danger">*</span></label>
                                    <?php $_fmt = $JwtData->GenSettings->FormDateFormat ?? 'd-m-Y'; ?>
                                    <?php if ($isEdit && !$isDraftEdit): ?>
                                        <input type="hidden" name="transDate" value="<?php echo htmlspecialchars(format_datedisplay($SRData->TransDate, 'Y-m-d')); ?>" />
                                        <div class="input-group input-group-sm input-group-merge">
                                            <span class="input-group-text bg-white"><i class="icon-base bx bx-calendar"></i></span>
                                            <input type="text" class="form-control form-control-sm bg-white text-muted" style="cursor:default;"
                                                value="<?php echo htmlspecialchars(format_datedisplay($SRData->TransDate, $_fmt)); ?>" readonly tabindex="-1" />
                                        </div>
                                    <?php else: ?>
                                        <div class="input-group input-group-sm input-group-merge">
                                            <span class="input-group-text bg-white"><i class="icon-base bx bx-calendar"></i></span>
                                            <input type="text" class="form-control form-control-sm bg-white" id="transDate_disp" readonly="readonly"
                                                value="<?php echo $isEdit ? format_datedisplay($SRData->TransDate, $_fmt) : format_datedisplay(time(), $_fmt); ?>"
                                                required />
                                            <input type="hidden" id="transDate" name="transDate" value="<?php echo $isEdit ? htmlspecialchars(format_datedisplay($SRData->TransDate, 'Y-m-d')) : format_datedisplay(time(), 'Y-m-d'); ?>" />
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col">
                                    <label for="referenceDetails" class="trans-field-label"><?php echo t('lbl_reference', 'Reference'); ?></label>
                                    <input type="text" id="referenceDetails" name="referenceDetails" class="form-control form-control-sm"
                                        placeholder="Invoice No, Ref No..." maxlength="100"
                                        value="<?php echo $isEdit ? htmlspecialchars($SRData->Reference ?? '') : ''; ?>" />
                                </div>
                            </div>

                            <div id="customerAddressBox" class="trans-addr-strip d-none"><i class="bx bx-map-pin"></i><span></span></div>
                            <hr class="mt-3"/>

                            <?php $this->load->view('transactions/partials/form_products_add', [
                                'transProductSectionTitle' => 'Returned Products',
                                'transNotesPlaceholder'    => 'Enter notes or reason for return',
                                'transShowDropzone'        => true,
                                'transHideAddProduct'      => true,
                                'transHideProductSearch'    => $_srMethod === 'Automatic',
                                'transNotesContent'        => $isEdit ? ($SRData->Notes ?? '') : '',
                                'transTermsContent'        => $isEdit ? ($SRData->TermsConditions ?? '') : ($JwtData->TransSettings->TermsAndConditions ?? ''),
                                'transSignatureUID'        => $isEdit ? (int)($SRData->SignatureUID ?? 0) : 0,
                                'transSignatures'          => $JwtData->User->Signatures ?? [],
                                'transPaymentVars'         => !$isEdit ? [
                                    'PaymentTypes'     => $PaymentTypes ?? [],
                                    'BankAccounts'     => $BankAccounts ?? [],
                                    'JwtData'          => $JwtData,
                                    'paymentPartyType' => 'C',
                                ] : null,
                                'transEditItems'           => $isEdit ? ($SRItems ?? []) : [],
                                'transShowCompliment'      => true,
                            ]); ?>

                            <?php $this->load->view('transactions/partials/trans_summary_bar', ['_barIsSticky' => false, '_barSections' => 'full4', '_barButtonLayout' => 'split', '_barShowPrint' => 'draft_or_create', '_barUseDcClasses' => false, '_barIsEdit' => $isEdit, '_barIsDraftEdit' => $isDraftEdit]); ?>

                        </div>
                    </div>

                    <?php $this->load->view('transactions/partials/trans_summary_bar', ['_barIsSticky' => true, '_barSections' => 'full4', '_barButtonLayout' => 'split', '_barShowPrint' => 'draft_or_create', '_barUseDcClasses' => false, '_barIsEdit' => $isEdit, '_barIsDraftEdit' => $isDraftEdit]); ?>

                    <?php echo form_close(); ?>

                </div>
            </div>

            <?php $this->load->view('common/transactions/transprefix'); ?>
            <?php $this->load->view('common/modals/customer_form'); ?>
            <?php $this->load->view('transactions/modals/invoice_items_select'); ?>
            <?php $this->load->view('transactions/partials/form_common_modals'); ?>

        </div>

    </div>
</div>

<?php $this->load->view('transactions/partials/additional_charges_modal'); ?>
<?php $this->load->view('common/imagepreview_modal'); ?>
<?php $this->load->view('common/transactions/footer'); ?>

<script src="/js/common/address.js"></script>
<script src="/js/common/bankdetails.js"></script>
<script src="/js/common/gstin_fetch.js"></script>
<script src="/js/common/phone_cc_dropdown.js"></script>
<script src="/js/common/customer_form.js"></script>
<script src="/js/transactions/salesreturns.js"></script>
<script src="/js/transactions/forms/bill_manager.js"></script>

<script>window.R2K_CUST_HIDE_CREATE = true;</script>
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
    'isEdit'         => $isEdit,
    'isDraftEdit'    => $isDraftEdit,
    'moduleUID'      => 106,
    'enableStorage'  => (bool)$JwtData->GenSettings->EnableStorage,
    'formId'         => $formId,
    'formAction'     => $formAction,
    'updateAction'   => 'salesreturns/updateSalesReturn',
    'upstashUrl'     => $UpstashReadUrl   ?? '',
    'upstashToken'   => $UpstashReadToken ?? '',
    'custCacheKey'   => $CustomerCacheKey ?? '',
    'returnTab'      => $_returnTab,
    'returnPage'     => (int)$_returnPage,
    'currency'       => $JwtData->GenSettings->CurrenySymbol ?? '₹',
    'decimals'       => (int)($JwtData->GenSettings->DecimalPoints ?? 2),
    'listDateFormat' => $JwtData->GenSettings->ListDateFormat ?? 'd M Y',
    'srItemMethod'   => $_srMethod,
    'transType'      => 'SalesReturn',
    'editData'       => $isEdit ? [
        'transUID'          => $transUID,
        'custUID'           => (int)($SRData->PartyUID ?? 0),
        'custName'          => $SRData->PartyName  ?? '',
        'custArea'          => $SRData->PartyArea   ?? '',
        'custMobile'        => $SRData->PartyMobile ?? '',
        'extraDiscAmount'   => (float)($SRData->ExtraDiscount ?? 0),
        'extraDiscType'     => $SRData->ExtraDiscountType ?? '',
        'globalDiscPercent' => (float)($SRData->GlobalDiscPercent ?? 0),
        'attachments'       => $SRAttachments ?? [],
        'items'             => array_map(function($item) use ($SRSerialsByProd) {
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
                'discount_amount'       => (float)$item->DiscountAmount,
                'line_total'           => (float)$item->TaxableAmount,
                'net_total'            => (float)$item->NetAmount,
                'isCompliment'         => (int)($item->IsCompliment ?? 0),
                'catalogSellingPrice'  => (float)($item->CatalogSellingPrice ?? 0),
                'brandUID'             => $item->BrandUID          ? (int)$item->BrandUID            : null,
                'variantUID'           => $item->VariantUID         ? (int)$item->VariantUID          : null,
                'variantLabel'         => $item->VariantLabel        ?? '',
                'brandName'            => $item->BrandName         ?? '',
                'IsBrandApplicable'    => (int)($item->IsBrandApplicable ?? 0),
                'IsSerialTracked'      => (int)($item->IsSerialTracked  ?? 0),
                'serials'              => $SRSerialsByProd[(int)$item->ProductUID] ?? [],
            ];
        }, $SRItems ?? []),
    ] : null,
]); ?>;
</script>
<script src="/js/transactions/forms/serial_tracker.js"></script>
<script src="/js/transactions/forms/salesreturn.js"></script>
