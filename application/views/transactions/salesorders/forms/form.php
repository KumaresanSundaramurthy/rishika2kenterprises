<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$isEdit      = isset($SOData);
$isDraftEdit = $isEdit && ($SOData->DocStatus === 'Draft');
$transUID    = $isEdit ? (int)$SOData->TransUID : 0;
$formId      = 'soForm';
$formAction  = $isEdit ? 'salesorders/updateSalesOrder' : 'salesorders/addSalesOrder';
extract(initTransFormCommon($isEdit, $SOData ?? null, '/salesorders', $JwtData));

$_prefix          = resolveTransPrefix($isEdit, $isDraftEdit, $PrefixData ?? [], $isEdit ? (int)($SOData->PrefixUID ?? 0) : 0, $isEdit ? (int)($SOData->TransNumber ?? 0) : 0, $NextNumberMap ?? []);
$editPrefixConfig = $_prefix['config'];
$editTransNumber  = $_prefix['transNumber'];
$editPrefixSeg    = $_prefix['seg'];

$_deliveryDate = '';
if (!$isEdit) {
    // New / conversion: Order Date = today (set by transDatePickr), Delivery Date = today + 7 days
    $_deliveryDate = date('Y-m-d', strtotime('+7 days'));
} elseif ($isEdit && !empty($SOData->ValidityDate)) {
    $_deliveryDate = htmlspecialchars(format_datedisplay($SOData->ValidityDate, 'Y-m-d'));
}

$_nt       = resolveTransNotesTerms($isEdit, $SOData ?? null, $JwtData, $isEdit ? [] : [$QuotationData ?? null]);
$_notesVal = $_nt['notesVal'];
$_termsVal = $_nt['termsVal'];

$_addrLines = buildDispatchAddressLines($DispatchAddress ?? null);
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
                    <?php else: ?>
                    <input type="hidden" name="fromQuotationUID" id="fromQuotationUID" value="<?php echo (int)($FromQuotationUID ?? 0); ?>" />
                    <?php endif; ?>
                    <?php $this->load->view('transactions/partials/place_of_supply_inputs', ['_posCode' => $_posCode, '_posName' => $_posName]); ?>

                    <div class="card mb-3">

                        <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2 trans-header-static trans-theme modal-header-center-sticky">
                            <div class="d-flex align-items-center gap-3" id="transHeaderInfo">
                                <?php $this->load->view('transactions/partials/form_back_button'); ?>
                                <div class="trans-doc-icon bg-warning bg-opacity-10">
                                    <i class="bx bx-store-alt text-warning" style="font-size:1.1rem;"></i>
                                </div>
                                <div>
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <?php if (!$isEdit): ?>
                                            <span class="fw-bold" style="font-size:.92rem;">Create Sales Order</span>
                                            <?php if (!empty($QuotationData)): ?>
                                                <span class="badge text-bg-primary" style="font-size:.65rem;"><i class="bx bx-transfer-alt me-1"></i>From Quotation: <?php echo htmlspecialchars($QuotationData->UniqueNumber ?? ''); ?></span>
                                            <?php endif; ?>
                                            <?php $this->load->view('transactions/partials/form_prefix_add'); ?>
                                        <?php else: ?>
                                            <span class="fw-bold" style="font-size:.92rem;"><?php echo $isDraftEdit ? '' : 'Edit'; ?> Sales Order</span>
                                            <?php if (!$isDraftEdit && !empty($SOData->UniqueNumber)): ?>
                                                <span class="trans-form-doc-number"><?php echo htmlspecialchars($SOData->UniqueNumber); ?></span>
                                            <?php endif; ?>
                                            <?php $this->load->view('transactions/partials/form_prefix_edit', [
                                                '_editPrefixUID'  => (int)($SOData->PrefixUID ?? 0),
                                                'editTransNumber' => $editTransNumber,
                                                'editPrefixSeg'   => $editPrefixSeg,
                                                'isDraftEdit'     => $isDraftEdit,
                                            ]); ?>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($isEdit && !$isDraftEdit && !empty($SOData->TransDate)): ?>
                                    <div class="d-flex align-items-center gap-2 mt-1">
                                        <span style="font-size:.7rem;color:#8592a3;">Order Date</span>
                                        <span style="font-size:.78rem;color:#566a7f;"><?php echo htmlspecialchars(format_datedisplay($SOData->TransDate)); ?></span>
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
                            $_soType    = !empty($SOData->DocType ?? '') ? $SOData->DocType : $_tsDefault;
                            ?>
                            <!-- ── Toolbar: Type & Dispatch From ─────────────────────────── -->
                            <?php $this->load->view('transactions/partials/trans_toolbar_type', ['_tbTypeValue' => $_soType, '_tbFieldId' => 'orderType', '_tbFieldName' => 'orderType', '_tbEditGuardStrict' => false, '_tbDispatchLabel' => 'Dispatch From', '_tbShowOnAccount' => true, '_tbOnAccountGuard' => true, '_tbOaSrStyle' => false, '_tbIsEdit' => $isEdit, '_tbIsDraftEdit' => $isDraftEdit]); ?>

                            <!-- ── Row 1: Customer | Order Date | Expected Delivery Date | Reference ── -->
                            <div class="row g-2 align-items-end mb-2">
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <label for="customerSearch" class="trans-field-label mb-0">Select Customer <span class="text-danger">*</span></label>
                                        <?php if (!$isEdit): ?>
                                        <button type="button" id="addTransCustomer" class="trans-add-btn btn btn-outline-primary btn-sm" style="font-size:.72rem;white-space:nowrap;" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('tooltip_create_customer', 'Create Customer'); ?>"><i class="bx bx-plus-circle me-1"></i><?php echo t('btn_add_customer', 'Add Customer'); ?></button>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($isEdit && !$isDraftEdit): ?>
                                        <div class="input-group input-group-sm input-group-merge customer-search-group party-has-selection" id="customerGroup_customerSearch">
                                            <select id="customerSearch" name="customerSearch" class="form-select form-select-sm"></select>
                                            <span class="party-edit-icon" id="editCustomerBtn" title="Edit Customer"><i class="bx bx-edit"></i></span>
                                        </div>
                                    <?php else: ?>
                                        <div class="input-group input-group-sm input-group-merge customer-search-group" id="customerGroup_customerSearch">
                                            <span class="input-group-text p-2 cursor-pointer party-search-icon" id="openCustomerSearchModal" style="background:#f0efff;border-color:#d9d8ff;color:#696cff;"><i class="icon-base bx bx-search"></i></span>
                                            <select id="customerSearch" name="customerSearch" class="form-select form-select-sm"></select>
                                            <span class="party-edit-icon" id="editCustomerBtn" title="Edit Customer"><i class="bx bx-edit"></i></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-auto" style="min-width:155px;">
                                    <label for="transDate" class="trans-field-label"><?php echo t('lbl_order_date', 'Order Date'); ?> <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-sm input-group-merge">
                                        <span class="input-group-text bg-white"><i class="icon-base bx bx-calendar"></i></span>
                                        <?php $_fmt = $JwtData->GenSettings->FormDateFormat ?? 'd-m-Y'; ?>
                                        <input type="text" class="form-control form-control-sm bg-white" id="transDate_disp" readonly="readonly"
                                            value="<?php echo $isEdit ? format_datedisplay($SOData->TransDate, $_fmt) : format_datedisplay(time(), $_fmt); ?>"
                                            required />
                                        <input type="hidden" id="transDate" name="transDate"
                                            value="<?php echo $isEdit ? htmlspecialchars(format_datedisplay($SOData->TransDate, 'Y-m-d')) : format_datedisplay(time(), 'Y-m-d'); ?>" />
                                    </div>
                                </div>
                                <div class="col-auto" style="min-width:155px;">
                                    <label for="deliveryDate" class="trans-field-label">Expected Delivery Date</label>
                                    <div class="input-group input-group-sm input-group-merge">
                                        <span class="input-group-text bg-white"><i class="icon-base bx bx-calendar"></i></span>
                                        <input type="text" class="form-control form-control-sm bg-white" id="deliveryDate_disp" readonly="readonly"
                                            value="<?php echo $_deliveryDate ? format_datedisplay($_deliveryDate, $_fmt) : ''; ?>" />
                                        <input type="hidden" id="deliveryDate" name="deliveryDate"
                                            value="<?php echo $_deliveryDate; ?>" />
                                    </div>
                                </div>
                                <div class="col">
                                    <label for="referenceDetails" class="trans-field-label"><?php echo t('lbl_reference', 'Reference'); ?></label>
                                    <input type="text" id="referenceDetails" name="referenceDetails" class="form-control form-control-sm"
                                        placeholder="PO Number, Sales Person, Ref No..." maxlength="100"
                                        value="<?php echo $isEdit ? htmlspecialchars($SOData->Reference ?? '') : (!empty($QuotationData->Reference) ? htmlspecialchars($QuotationData->Reference) : ''); ?>" />
                                </div>
                            </div>

                            <div id="customerAddressBox" class="trans-addr-strip d-none"><i class="bx bx-map-pin"></i><span></span><button type="button" id="btnEditCustAddr" class="trans-addr-edit-btn" title="Edit billing address"><i class="bx bx-edit"></i></button></div>
                            <hr class="mt-3"/>

                            <?php $this->load->view('transactions/partials/form_products_add', [
                                'transNotesPlaceholder' => 'Enter notes or anything else',
                                'transNotesContent'     => $_notesVal,
                                'transTermsContent'     => $_termsVal,
                                'transShowDropzone'     => true,
                                'transSignatureUID'     => $isEdit ? (int)($SOData->SignatureUID ?? 0) : 0,
                                'transSignatures'       => $JwtData->User->Signatures ?? [],
                                'transEditItems'        => $isEdit ? ($SOItems ?? []) : [],
                                'transShowCompliment'   => true,
                            ]); ?>

                            <?php $this->load->view('transactions/partials/trans_summary_bar', ['_barIsSticky' => false, '_barSections' => '1', '_barButtonLayout' => 'save_only', '_barShowPrint' => 'draft_or_create', '_barUseDcClasses' => false, '_barIsEdit' => $isEdit, '_barIsDraftEdit' => $isDraftEdit]); ?>

                        </div>
                    </div>

                    <?php $this->load->view('transactions/partials/trans_summary_bar', ['_barIsSticky' => true, '_barSections' => '1', '_barButtonLayout' => 'save_only', '_barShowPrint' => 'draft_or_create', '_barUseDcClasses' => false, '_barIsEdit' => $isEdit, '_barIsDraftEdit' => $isDraftEdit]); ?>

                    <?php echo form_close(); ?>

                </div>
            </div>

            <?php $this->load->view('common/transactions/transprefix'); ?>
            <?php $this->load->view('common/modals/customer_form'); ?>
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
<script src="/js/transactions/salesorders.js"></script>
<script src="/js/transactions/forms/bill_manager.js"></script>
<?php $this->load->view('common/transactions/pricelist_select_modal'); ?>
<script src="/js/transactions/forms/pricelist_trans.js"></script>
<script src="/js/transactions/forms/transprefix.js"></script>
<script src="/js/transactions/forms/modaladdress.js"></script>
<script src="/js/common/category_form.js"></script>
<script src="/js/common/product_form.js"></script>
<script src="/js/transactions/attachments.js"></script>
<?php $this->load->view('transactions/partials/additional_charges_data'); ?>

<script>
var _transFormData = <?php echo json_encode([
    'isEdit'       => $isEdit,
    'isDraftEdit'  => $isDraftEdit,
    'moduleUID'    => (int)($JwtData->ModuleUID ?? 0),
    'enableStorage'=> (bool)$JwtData->GenSettings->EnableStorage,
    'formId'       => $formId,
    'formAction'   => $formAction,
    'updateAction' => 'salesorders/updateSalesOrder',
    'orgState'     => $DispatchAddress->StateText ?? '',
    'upstashUrl'   => $UpstashReadUrl   ?? '',
    'upstashToken' => $UpstashReadToken ?? '',
    'custCacheKey' => $CustomerCacheKey ?? '',
    'returnTab'    => $_returnTab,
    'returnPage'   => (int)$_returnPage,
    'currency'     => $JwtData->GenSettings->CurrenySymbol ?? '₹',
    'decimals'      => 2,
    'editData'     => $isEdit ? [
        'transUID'          => (int)$SOData->TransUID,
        'custUID'           => (int)($SOData->PartyUID ?? 0),
        'custName'          => $SOData->PartyName ?? '',
        'custArea'          => $SOData->PartyArea   ?? '',
        'custMobile'        => $SOData->PartyMobile ?? '',
        'custBillLine1'     => $SOData->BillLine1   ?? '',
        'custBillLine2'     => $SOData->BillLine2   ?? '',
        'custBillCity'      => $SOData->BillCity    ?? '',
        'custBillState'     => $SOData->BillState   ?? '',
        'custBillPincode'   => $SOData->BillPincode ?? '',
        'custState'         => $CustAddr->StateText ?? '',
        'extraDiscAmount'   => (float)($SOData->ExtraDiscAmount ?? 0),
        'extraDiscType'     => $SOData->ExtraDiscType ?? '',
        'globalDiscPercent' => (float)($SOData->GlobalDiscPercent ?? 0),
        'attachments'       => $SOAttachments ?? [],
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
            ];
        }, $SOItems ?? []),
    ] : null,
    'fromQuotation' => (!$isEdit && !empty($QuotationData)) ? [
        'uid'            => (int)($FromQuotationUID ?? 0),
        'customer'       => (int)$QuotationData->PartyUID,
        'customerName'   => $QuotationData->PartyName   ?? '',
        'customerArea'   => $QuotationData->PartyArea   ?? '',
        'customerMobile' => $QuotationData->PartyMobile ?? '',
    ] : null,
    'fromQuotAttachments' => (!$isEdit && !empty($QuotationData)) ? array_map(function($a) {
        return [
            'AttachUID' => (int)$a->AttachUID,
            'FileName'  => $a->FileName ?? '',
            'FilePath'  => $a->FilePath ?? '',
            'FileSize'  => (int)($a->FileSize ?? 0),
            'FileType'  => $a->FileType ?? '',
            'Url'       => $a->Url ?? '',
        ];
    }, $QuotationAttachments ?? []) : [],
    'fromQuotItems' => (!$isEdit && !empty($QuotationData)) ? array_map(function($item) {
        return [
            'id'               => (int)   $item->ProductUID,
            'text'             => $item->ProductName,
            'itemName'         => $item->ProductName,
            'unitPrice'        => (float) $item->UnitPrice,
            'sellingPrice'     => (float) $item->SellingPrice,
            'taxAmount'        => (float) $item->TaxAmount,
            'purchasePrice'    => 0,
            'availableQuantity'=> 0,
            'hsnCode'          => '',
            'categoryUID'      => $item->CategoryUID ? (int)$item->CategoryUID : null,
            'storageUID'       => $item->StorageUID  ? (int)$item->StorageUID  : null,
            'taxPercent'       => (float) $item->TaxPercentage,
            'cgstPercent'      => (float) $item->CGST,
            'sgstPercent'      => (float) $item->SGST,
            'igstPercent'      => (float) $item->IGST,
            'taxDetailsUID'    => (int)   $item->TaxDetailsUID,
            'quantity'         => (float) $item->Quantity,
            'partNumber'       => $item->PartNumber      ?? '',
            'primaryUnit'      => $item->PrimaryUnitName ?? '',
            'discount'         => (float) $item->Discount,
            'discountType'     => 'Percentage',
            'discountTypeUID'  => $item->DiscountTypeUID ? (int)$item->DiscountTypeUID : null,
            'discount_amount'  => (float) $item->DiscountAmount,
            'line_total'       => (float) $item->TaxableAmount,
            'net_total'        => (float) $item->NetAmount,
        ];
    }, $QuotationItems ?? []) : [],
]); ?>;
</script>
<script src="/js/transactions/forms/salesorder.js"></script>
