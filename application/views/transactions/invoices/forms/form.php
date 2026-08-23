<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$isEdit      = isset($InvData);
$isDraftEdit = $isEdit && ($InvData->DocStatus === 'Draft');
$transUID    = $isEdit ? (int)$InvData->TransUID : 0;
$formId      = 'invForm';
$formAction  = $isEdit ? 'invoices/updateInvoice' : 'invoices/addInvoice';
extract(initTransFormCommon($isEdit, $InvData ?? null, '/invoices', $JwtData));

$_prefix          = resolveTransPrefix($isEdit, $isDraftEdit, $PrefixData ?? [], $isEdit ? (int)($InvData->PrefixUID ?? 0) : 0, $isEdit ? (int)($InvData->TransNumber ?? 0) : 0, $NextNumberMap ?? []);
$editPrefixConfig = $_prefix['config'];
$editTransNumber  = $_prefix['transNumber'];
$editPrefixSeg    = $_prefix['seg'];

$_addrLines = buildDispatchAddressLines($DispatchAddress ?? null);

$_nt       = resolveTransNotesTerms($isEdit, $InvData ?? null, $JwtData, $isEdit ? [] : [$SalesOrderData ?? null, $QuotationData ?? null, $ChallanData ?? null]);
$_notesVal = $_nt['notesVal'];
$_termsVal = $_nt['termsVal'];

if ($isEdit) {
    $_b        = calcTransStatusBadge($InvData, ['Issued' => 'primary', 'Partial' => 'info', 'Paid' => 'success', 'Cancelled' => 'danger', 'Rejected' => 'secondary', 'Draft' => 'secondary'], $JwtData);
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
                    <?php else: ?>
                    <input type="hidden" name="fromSalesOrderUID" id="fromSalesOrderUID" value="<?php echo (int)($FromSalesOrderUID ?? 0); ?>" />
                    <input type="hidden" name="fromQuotationUID" id="fromQuotationUID" value="<?php echo (int)($FromQuotationUID ?? 0); ?>" />
                    <input type="hidden" name="fromChallanUID" id="fromChallanUID" value="<?php echo (int)($FromChallanUID ?? 0); ?>" />
                    <?php endif; ?>
                    <?php $this->load->view('transactions/partials/place_of_supply_inputs', ['_posCode' => $_posCode, '_posName' => $_posName]); ?>

                    <div class="card mb-3">

                        <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2 trans-header-static trans-theme modal-header-center-sticky">
                            <div class="d-flex align-items-center gap-3" id="transHeaderInfo">
                                <?php $this->load->view('transactions/partials/form_back_button'); ?>
                                <div class="trans-doc-icon bg-primary bg-opacity-10">
                                    <i class="bx bx-receipt text-primary" style="font-size:1.1rem;"></i>
                                </div>
                                <div>
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <?php if (!$isEdit): ?>
                                            <span class="fw-bold" style="font-size:.92rem;">Create Invoice</span>
                                            <?php $this->load->view('transactions/partials/form_prefix_add'); ?>
                                        <?php else: ?>
                                            <span class="fw-bold" style="font-size:.92rem;"><?php echo $isDraftEdit ? '' : 'Edit'; ?> Invoice</span>
                                            <?php if (!$isDraftEdit && !empty($InvData->UniqueNumber)): ?>
                                                <span class="trans-form-doc-number"><?php echo htmlspecialchars($InvData->UniqueNumber); ?></span>
                                                <span class="badge bg-label-<?php echo $hStatusClr; ?>" style="font-size:.7rem;"><?php echo $hStatus; ?></span>
                                            <?php endif; ?>
                                            <?php $this->load->view('transactions/partials/form_prefix_edit', [
                                                '_editPrefixUID'  => (int)($InvData->PrefixUID  ?? 0),
                                                'editTransNumber' => $editTransNumber,
                                                'editPrefixSeg'   => $editPrefixSeg,
                                                'isDraftEdit'     => $isDraftEdit,
                                            ]); ?>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($isEdit && !$isDraftEdit): ?>
                                    <div class="d-flex align-items-center gap-3 mt-1">
                                        <div class="d-flex align-items-center gap-1">
                                            <span style="font-size:.7rem;color:#8592a3;">Invoice Amount</span>
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
                                        <?php if (!empty($InvData->TransDate)): ?>
                                        <div class="d-flex align-items-center gap-1">
                                            <span style="font-size:.7rem;color:#8592a3;">Date</span>
                                            <span style="font-size:.78rem;color:#566a7f;"><?php echo htmlspecialchars(format_datedisplay($InvData->TransDate)); ?></span>
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
                            $_invType   = !empty($InvData->DocType) ? $InvData->DocType : $_tsDefault;
                            ?>

                            <!-- ── Toolbar: Type & Dispatch From ─────────────────────────────── -->
                            <?php $this->load->view('transactions/partials/trans_toolbar_type', ['_tbTypeValue' => $_invType, '_tbFieldId' => 'invoiceType', '_tbFieldName' => 'invoiceType', '_tbEditGuardStrict' => true, '_tbDispatchLabel' => 'Dispatch From', '_tbShowOnAccount' => true, '_tbOnAccountGuard' => true, '_tbOaSrStyle' => false, '_tbIsEdit' => $isEdit, '_tbIsDraftEdit' => $isDraftEdit]); ?>

                            <!-- ── Row 1: Customer | Invoice Date | Due Date | Reference ─────── -->
                            <div class="row g-2 align-items-end mb-2">

                                <!-- Customer -->
                                <div class="col-md-4">
                                    <?php if ($isEdit && !$isDraftEdit): ?>
                                        <label class="trans-field-label mb-1">Customer</label>
                                        <select id="customerSearch" name="customerSearch" class="form-select form-select-sm"></select>
                                    <?php else: ?>
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <label for="customerSearch" class="trans-field-label mb-0">Customer <span class="text-danger">*</span></label>
                                            <button type="button" id="addTransCustomer" class="trans-add-btn btn btn-outline-primary btn-sm" style="font-size:.72rem;white-space:nowrap;" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('tooltip_create_customer', 'Create Customer'); ?>">
                                                <i class="bx bx-plus-circle me-1"></i><?php echo t('btn_add_customer', 'Add Customer'); ?>
                                            </button>
                                        </div>
                                        <div class="input-group input-group-sm input-group-merge customer-search-group" id="customerGroup_customerSearch">
                                            <span class="input-group-text p-2 cursor-pointer party-search-icon" id="openCustomerSearchModal" style="background:#f0efff;border-color:#d9d8ff;color:#696cff;"><i class="icon-base bx bx-search"></i></span>
                                            <select id="customerSearch" name="customerSearch" class="form-select form-select-sm"></select>
                                            <span class="party-edit-icon" id="editCustomerBtn" title="Edit Customer"><i class="bx bx-edit-alt"></i></span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Invoice Date — narrow (date width only) -->
                                <div class="col-auto" style="min-width:155px;">
                                    <label for="transDate" class="trans-field-label"><?php echo t('lbl_invoice_date', 'Invoice Date'); ?> <span class="text-danger">*</span></label>
                                    <?php $_fmt = $JwtData->GenSettings->FormDateFormat ?? 'd-m-Y'; ?>
                                    <?php if ($isEdit && !$isDraftEdit): ?>
                                        <input type="hidden" name="transDate" value="<?php echo htmlspecialchars(format_datedisplay($InvData->TransDate, 'Y-m-d')); ?>" />
                                        <div class="input-group input-group-sm input-group-merge">
                                            <span class="input-group-text bg-white"><i class="icon-base bx bx-calendar"></i></span>
                                            <input type="text" class="form-control form-control-sm bg-white text-muted" style="cursor:default;" value="<?php echo htmlspecialchars(format_datedisplay($InvData->TransDate, $_fmt)); ?>" readonly tabindex="-1" />
                                        </div>
                                    <?php else: ?>
                                        <div class="input-group input-group-sm input-group-merge">
                                            <span class="input-group-text bg-white"><i class="icon-base bx bx-calendar"></i></span>
                                            <input type="text" class="form-control form-control-sm bg-white" id="transDate_disp" readonly="readonly" value="<?php echo $isEdit ? format_datedisplay($InvData->TransDate, $_fmt) : format_datedisplay(time(), $_fmt); ?>" required />
                                            <input type="hidden" id="transDate" name="transDate" value="<?php echo $isEdit ? htmlspecialchars(format_datedisplay($InvData->TransDate, 'Y-m-d')) : format_datedisplay(time(), 'Y-m-d'); ?>" />
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Due Date — narrow (date width only) -->
                                <div class="col-auto" style="min-width:145px;">
                                    <label for="dueDate" class="trans-field-label"><?php echo t('lbl_due_date', 'Due Date'); ?></label>
                                    <div class="input-group input-group-sm input-group-merge">
                                        <span class="input-group-text bg-white"><i class="icon-base bx bx-calendar"></i></span>
                                        <input type="text" class="form-control form-control-sm bg-white" id="dueDate_disp" readonly="readonly" value="<?php echo ($isEdit && !empty($InvData->ValidityDate)) ? format_datedisplay($InvData->ValidityDate, $_fmt) : format_datedisplay(date('Y-m-d'), $_fmt); ?>" />
                                        <input type="hidden" id="dueDate" name="dueDate" value="<?php echo ($isEdit && !empty($InvData->ValidityDate)) ? htmlspecialchars(format_datedisplay($InvData->ValidityDate, 'Y-m-d')) : date('Y-m-d'); ?>" />
                                    </div>
                                </div>

                                <!-- Reference — takes remaining width -->
                                <div class="col">
                                    <label for="referenceDetails" class="trans-field-label"><?php echo t('lbl_reference', 'Reference'); ?></label>
                                    <input type="text" id="referenceDetails" name="referenceDetails" class="form-control form-control-sm"
                                        placeholder="PO Number, Sales Person, Ref No..." maxlength="100"
                                        value="<?php echo $isEdit ? htmlspecialchars($InvData->Reference ?? '') : (!empty($SalesOrderData->Reference) ? htmlspecialchars($SalesOrderData->Reference) : (!empty($QuotationData->Reference) ? htmlspecialchars($QuotationData->Reference) : (!empty($ChallanData->UniqueNumber) ? htmlspecialchars($ChallanData->UniqueNumber) : ''))); ?>" />
                                </div>

                            </div>

                            <div id="customerAddressBox" class="trans-addr-strip d-none"><i class="bx bx-map-pin"></i><span></span></div>
                            <hr class="mt-3"/>

                            <?php $this->load->view('transactions/partials/form_products_add', [
                                'transNotesPlaceholder' => 'Enter notes or anything else',
                                'transNotesContent'     => $_notesVal,
                                'transTermsContent'     => $_termsVal,
                                'transShowDropzone'     => true,
                                'transSignatureUID'     => $isEdit ? (int)($InvData->SignatureUID ?? 0) : 0,
                                'transSignatures'       => $JwtData->User->Signatures ?? [],
                                'transPaymentVars'      => (!$isEdit || $isDraftEdit) ? [
                                    'PaymentTypes'     => $PaymentTypes ?? [],
                                    'BankAccounts'     => $BankAccounts ?? [],
                                    'JwtData'          => $JwtData,
                                    'paymentPartyType' => 'C',
                                ] : null,
                                'transEditItems'        => $isEdit ? ($InvItems ?? []) : [],
                                'transShowCompliment'   => true,
                            ]); ?>

                            <?php $this->load->view('transactions/partials/trans_summary_bar', ['_barIsSticky' => false, '_barSections' => 'full4', '_barButtonLayout' => 'invoice', '_barShowPrint' => 'draft_or_create', '_barUseDcClasses' => false, '_barIsEdit' => $isEdit, '_barIsDraftEdit' => $isDraftEdit]); ?>

                        </div>
                    </div>

                    <?php $this->load->view('transactions/partials/trans_summary_bar', ['_barIsSticky' => true, '_barSections' => 'full4', '_barButtonLayout' => 'invoice', '_barShowPrint' => 'draft_or_create', '_barUseDcClasses' => false, '_barIsEdit' => $isEdit, '_barIsDraftEdit' => $isDraftEdit]); ?>

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
<?php $this->load->view('common/transactions/print_modals'); ?>
<?php $this->load->view('common/imagepreview_modal'); ?>
<?php $this->load->view('common/transactions/footer'); ?>

<script src="/js/common/address.js"></script>
<script src="/js/common/bankdetails.js"></script>
<script src="/js/common/gstin_fetch.js"></script>
<script src="/js/common/phone_cc_dropdown.js"></script>
<script src="/js/common/customer_form.js"></script>
<script src="/js/transactions/invoices.js"></script>
<script src="/js/transactions/forms/bill_manager.js"></script>
<?php $this->load->view('common/transactions/pricelist_select_modal'); ?>
<script src="/js/transactions/forms/pricelist_trans.js"></script>
<script src="/js/transactions/forms/transprefix.js"></script>
<script src="/js/transactions/forms/modaladdress.js"></script>
<script src="/js/common/category_form.js"></script>
<script src="/js/common/product_form.js"></script>
<?php if (!$isEdit || $isDraftEdit): ?>
<script src="/js/transactions/forms/payment_section.js"></script>
<?php endif; ?>
<script src="/js/transactions/attachments.js"></script>
<script src="/js/core/a4_print.js"></script>
<?php $this->load->view('transactions/partials/additional_charges_data'); ?>

<script>
var _transFormData = <?php echo json_encode([
    'isEdit'       => $isEdit,
    'isDraftEdit'  => $isDraftEdit,
    'transType'    => 'Invoice',
    'moduleUID'    => (int)($JwtData->ModuleUID ?? 0),
    'enableStorage'=> (bool)$JwtData->GenSettings->EnableStorage,
    'formId'       => $formId,
    'formAction'   => $formAction,
    'updateAction' => 'invoices/updateInvoice',
    'orgState'     => $DispatchAddress->StateText ?? '',
    'upstashUrl'   => $UpstashReadUrl   ?? '',
    'upstashToken' => $UpstashReadToken ?? '',
    'custCacheKey' => $CustomerCacheKey ?? '',
    'returnTab'    => $_returnTab,
    'returnPage'   => (int)$_returnPage,
    'currency'     => $JwtData->GenSettings->CurrenySymbol ?? '₹',
    'decimals'     => (int)($JwtData->GenSettings->DecimalPoints ?? 2),
    'editData'     => $isEdit ? [
        'transUID'          => (int)($InvData->TransUID ?? 0),
        'custUID'           => (int)($InvData->PartyUID ?? 0),
        'custName'          => $InvData->PartyName  ?? '',
        'custArea'          => $InvData->PartyArea   ?? '',
        'custMobile'        => $InvData->PartyMobile ?? '',
        'custState'         => $CustAddr->StateText ?? '',
        'extraDiscAmount'   => (float)($InvData->ExtraDiscAmount ?? 0),
        'extraDiscType'     => $InvData->ExtraDiscType ?? '',
        'globalDiscPercent' => (float)($InvData->GlobalDiscPercent ?? 0),
        'paidAmount'        => (float)($InvData->PaidAmount ?? 0),
        'attachments'       => $InvAttachments ?? [],
        'items'             => array_map(function($item) use ($InvSerialsByProd) {
            return [
                'id'               => (int)  $item->ProductUID,
                'text'             => $item->ProductName,
                'itemName'         => $item->ProductName,
                'description'      => $item->Description ?? '',
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
                'brandName'            => $item->BrandName          ?? '',
                'variantUID'           => $item->VariantUID         ? (int)$item->VariantUID          : null,
                'variantLabel'         => $item->VariantLabel        ?? '',
                'IsBrandApplicable'    => (int)($item->IsBrandApplicable ?? 0),
                'IsSerialTracked'      => (int)($item->IsSerialTracked  ?? 0),
                'serials'              => $InvSerialsByProd[(int)$item->ProductUID] ?? [],
            ];
        }, $InvItems ?? []),
        'draftCN' => ($isDraftEdit && !empty($DraftReservedCN)) ? [
            'uid'    => (int)$DraftReservedCN->CreditNoteUID,
            'number' => $DraftReservedCN->CreditNoteNumber ?? '',
            'amount' => (float)$DraftReservedCN->Amount,
            'type'   => $DraftReservedCN->CreditNoteType   ?? '',
        ] : null,
    ] : null,
    'fromSO'      => (!$isEdit && !empty($SalesOrderData)) ? [
        'uid'            => (int)($FromSalesOrderUID ?? 0),
        'customer'       => (int)$SalesOrderData->PartyUID,
        'customerName'   => $SalesOrderData->PartyName   ?? '',
        'customerArea'   => $SalesOrderData->PartyArea   ?? '',
        'customerMobile' => $SalesOrderData->PartyMobile ?? '',
    ] : null,
    'fromSOItems'      => (!$isEdit && !empty($SalesOrderData)) ? array_map(function($item) {
        return [
            'id'               => (int)   $item->ProductUID,
            'text'             => $item->ProductName,
            'itemName'         => $item->ProductName,
            'unitPrice'        => (float) $item->UnitPrice,
            'sellingPrice'     => (float) $item->SellingPrice,
            'taxAmount'        => (float) $item->TaxAmount,
            'purchasePrice'    => (float) ($item->PurchasePrice ?? 0),
            'mrp'             => (float)($item->MRP ?? 0),
            'purchasePriceIsIncl' => (bool)($item->IsPurchasePriceIncl ?? 1),
            'availableQuantity'=> 0,
            'hsnCode'          => '',
            'categoryUID'      => $item->CategoryUID ? (int)$item->CategoryUID : null,
            'categoryName'     => $item->CategoryName  ?? '',
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
    }, $SalesOrderItems ?? []) : [],
    'fromQuotation'    => (!$isEdit && !empty($QuotationData)) ? [
        'uid'            => (int)($FromQuotationUID ?? 0),
        'customer'       => (int)$QuotationData->PartyUID,
        'customerName'   => $QuotationData->PartyName   ?? '',
        'customerArea'   => $QuotationData->PartyArea   ?? '',
        'customerMobile' => $QuotationData->PartyMobile ?? '',
    ] : null,
    'fromQuotItems'    => (!$isEdit && !empty($QuotationData)) ? array_map(function($item) {
        return [
            'id'               => (int)   $item->ProductUID,
            'text'             => $item->ProductName,
            'itemName'         => $item->ProductName,
            'unitPrice'        => (float) $item->UnitPrice,
            'sellingPrice'     => (float) $item->SellingPrice,
            'taxAmount'        => (float) $item->TaxAmount,
            'purchasePrice'    => (float) ($item->PurchasePrice ?? 0),
            'mrp'             => (float)($item->MRP ?? 0),
            'purchasePriceIsIncl' => (bool)($item->IsPurchasePriceIncl ?? 1),
            'availableQuantity'=> 0,
            'hsnCode'          => '',
            'categoryUID'      => $item->CategoryUID ? (int)$item->CategoryUID : null,
            'categoryName'     => $item->CategoryName  ?? '',
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
    'fromChallan'      => (!$isEdit && !empty($ChallanData)) ? [
        'uid'            => (int)($FromChallanUID ?? 0),
        'customer'       => (int)$ChallanData->PartyUID,
        'customerName'   => $ChallanData->PartyName   ?? '',
        'customerArea'   => $ChallanData->PartyArea   ?? '',
        'customerMobile' => $ChallanData->PartyMobile ?? '',
        'reference'      => $ChallanData->UniqueNumber ?? '',
    ] : null,
    'fromChallanItems' => (!$isEdit && !empty($ChallanData)) ? array_map(function($item) {
        return [
            'id'               => (int)   $item->ProductUID,
            'text'             => $item->ProductName,
            'itemName'         => $item->ProductName,
            'unitPrice'        => (float) $item->UnitPrice,
            'sellingPrice'     => (float) $item->SellingPrice,
            'taxAmount'        => (float) $item->TaxAmount,
            'purchasePrice'    => (float) ($item->PurchasePrice ?? 0),
            'mrp'             => (float)($item->MRP ?? 0),
            'purchasePriceIsIncl' => (bool)($item->IsPurchasePriceIncl ?? 1),
            'availableQuantity'=> 0,
            'hsnCode'          => $item->HSNCode ?? '',
            'categoryUID'      => $item->CategoryUID ? (int)$item->CategoryUID : null,
            'categoryName'     => $item->CategoryName  ?? '',
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
    }, $ChallanItems ?? []) : [],
]); ?>;
</script>
<?php $this->load->view('common/transactions/credits_detail_modal'); ?>
<?php $this->load->view('common/transactions/creditnote_detail_modal'); ?>
<script src="/js/transactions/forms/serial_tracker.js"></script>
<script src="/js/transactions/forms/invoice.js"></script>
