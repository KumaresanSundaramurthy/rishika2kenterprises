<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$isEdit      = isset($QuotData);
$isDraftEdit = $isEdit && ($QuotData->DocStatus === 'Draft');
$transUID    = $isEdit ? (int)$QuotData->TransUID : 0;
$formId      = 'quotationForm';
$formAction  = $isEdit ? 'quotations/updateQuotation' : 'quotations/addQuotation';
extract(initTransFormCommon($isEdit, $QuotData ?? null, '/quotations', $JwtData));

$_prefix          = resolveTransPrefix($isEdit, $isDraftEdit, $PrefixData ?? [], $isEdit ? (int)($QuotData->PrefixUID ?? 0) : 0, $isEdit ? (int)($QuotData->TransNumber ?? 0) : 0, $NextNumberMap ?? []);
$editPrefixConfig = $_prefix['config'];
$editTransNumber  = $_prefix['transNumber'];
$editPrefixSeg    = $_prefix['seg'];

$_addrLines = buildDispatchAddressLines($DispatchAddress ?? null);

$_nt       = resolveTransNotesTerms($isEdit, $QuotData ?? null, $JwtData, $isEdit ? [] : [$CloneData ?? null]);
$_notesVal = $_nt['notesVal'];
$_termsVal = $_nt['termsVal'];

$_savedCharges = [];
if ($isEdit && !empty($QuotData->AdditionalChargesJson)) {
    $_parsedCharges = json_decode($QuotData->AdditionalChargesJson, true);
    if (is_array($_parsedCharges)) {
        foreach ($_parsedCharges as $_ch) {
            $_savedCharges[$_ch['type']] = $_ch;
        }
    }
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

                        <?php $_hideNav = (int)($JwtData->TransSettings->HideNavOnTransForm ?? 0); ?>
                        <?php if (!$isEdit): ?>
                        <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2 trans-header-static trans-theme modal-header-center-sticky">
                            <div class="d-flex align-items-center gap-3" id="transHeaderInfo">
                                <?php $this->load->view('transactions/partials/form_back_button'); ?>
                                <div class="trans-doc-icon bg-primary bg-opacity-10">
                                    <i class="bx bx-file-blank text-primary" style="font-size:1.1rem;"></i>
                                </div>
                                <div>
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <span class="fw-bold" style="font-size:.92rem;">Create Quotation</span>
                                        <?php if (!empty($CloneData)): ?>
                                            <span class="badge text-bg-warning" style="font-size:.65rem;"><i class="bx bx-copy me-1"></i>Cloned from: <?php echo htmlspecialchars($CloneData->UniqueNumber ?? 'Draft'); ?></span>
                                        <?php endif; ?>
                                        <?php $this->load->view('transactions/partials/form_prefix_add'); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
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
                                <a href="<?php echo $_closeUrl; ?>" class="btn btn-sm btn-outline-danger px-3<?php echo $_hideNav ? ' d-none' : ''; ?>" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('tooltip_close', 'Return to list'); ?>"><i class="bx bx-x me-1"></i>Close</a>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="card-header bg-body-tertiary trans-header-static trans-theme modal-header-center-sticky d-flex justify-content-between align-items-center pb-3">
                            <div class="d-flex flex-wrap align-items-center gap-3" id="transHeaderInfo">
                                <?php $this->load->view('transactions/partials/form_back_button'); ?>
                                <h5 class="modal-title mb-0 ms-2"><?php echo $isDraftEdit ? '' : 'Edit'; ?> Quotation</h5>
                                <?php if (!$isDraftEdit && !empty($QuotData->UniqueNumber)): ?>
                                    <span class="trans-form-doc-number"><?php echo htmlspecialchars($QuotData->UniqueNumber); ?></span>
                                <?php endif; ?>
                                <?php $this->load->view('transactions/partials/form_prefix_edit', [
                                    '_editPrefixUID'  => (int)($QuotData->PrefixUID ?? 0),
                                    'editTransNumber' => $editTransNumber,
                                    'editPrefixSeg'   => $editPrefixSeg,
                                    'isDraftEdit'     => $isDraftEdit,
                                ]); ?>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <?php if ($isDraftEdit): ?>
                                <button type="submit" name="action" value="draft" class="btn btn-outline-secondary" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('tooltip_save_draft', 'Save and continue editing later'); ?>"><i class="bx bx-save me-1"></i><?php echo t('btn_save_draft', 'Save as Draft'); ?></button>
                                <?php endif; ?>
                                <button type="submit" name="action" value="save" class="btn btn-primary" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('tooltip_save', 'Save transaction'); ?>"><i class="bx bx-check me-1"></i>Save</button>
                                <a href="<?php echo $_closeUrl; ?>" class="btn btn-label-danger<?php echo $_hideNav ? ' d-none' : ''; ?>" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('tooltip_close', 'Return to list'); ?>">Close</a>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="card-body card-body-form-static p-3">

                            <?php
                            $_tsSetting = strtolower($JwtData->TransSettings->DefaultTransactionType ?? 'regular');
                            $_tsDefault = ($_tsSetting === 'without_tax') ? 'Without_GST' : 'Regular';
                            $_quotType  = !empty($QuotData->DocType ?? '') ? $QuotData->DocType : $_tsDefault;
                            ?>
                            <!-- ── Toolbar: Type & Dispatch From ─────────────────────────── -->
                            <?php $this->load->view('transactions/partials/trans_toolbar_type', ['_tbTypeValue' => $_quotType, '_tbFieldId' => 'quotationType', '_tbFieldName' => 'quotationType', '_tbEditGuardStrict' => false, '_tbDispatchLabel' => 'Dispatch From', '_tbShowOnAccount' => true, '_tbOnAccountGuard' => false, '_tbOaSrStyle' => false]); ?>

                            <!-- ── Row 1: Customer | Quotation Date | Validity Days | Validity Date | Reference ── -->
                            <div class="row g-2 align-items-end mb-2">
                                <div class="col-md-4">
                                    <?php if ($isEdit && !$isDraftEdit): ?>
                                    <label class="trans-field-label mb-1">Customer</label>
                                    <select id="customerSearch" name="customerSearch" class="form-select form-select-sm"></select>
                                    <?php else: ?>
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <label for="customerSearch" class="trans-field-label mb-0">Select Customer <span class="text-danger">*</span></label>
                                        <button type="button" id="addTransCustomer" class="trans-add-btn btn btn-outline-primary btn-sm" aria-label="Add new customer" style="font-size:.72rem;white-space:nowrap;" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('tooltip_create_customer', 'Create Customer'); ?>"><i class="bx bx-plus-circle me-1"></i><?php echo t('btn_add_customer', 'Add Customer'); ?></button>
                                    </div>
                                    <div class="input-group input-group-sm input-group-merge customer-search-group" id="customerGroup_customerSearch">
                                        <span class="input-group-text p-2 cursor-pointer" id="openCustomerSearchModal" style="background:#f0efff;border-color:#d9d8ff;color:#696cff;"><i class="icon-base bx bx-search"></i></span>
                                        <select id="customerSearch" name="customerSearch" class="form-select form-select-sm"></select>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-auto" style="min-width:155px;">
                                    <label for="transDate" class="trans-field-label"><?php echo t('lbl_quotation_date', 'Quotation Date'); ?> <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-sm input-group-merge">
                                        <span class="input-group-text bg-white"><i class="icon-base bx bx-calendar"></i></span>
                                        <?php $_fmt = $JwtData->GenSettings->FormDateFormat ?? 'd-m-Y'; ?>
                                        <input type="text" class="form-control form-control-sm bg-white" id="transDate_disp" readonly="readonly"
                                            value="<?php echo $isEdit ? format_datedisplay($QuotData->TransDate ?? '', $_fmt) : format_datedisplay(time(), $_fmt); ?>"
                                            required />
                                        <input type="hidden" id="transDate" name="transDate"
                                            value="<?php echo $isEdit ? htmlspecialchars(format_datedisplay($QuotData->TransDate ?? '', 'Y-m-d')) : format_datedisplay(time(), 'Y-m-d'); ?>" />
                                    </div>
                                </div>
                                <div class="col-auto" style="min-width:120px;">
                                    <label for="validityDays" class="trans-field-label">Validity (Days)</label>
                                    <input type="number" id="validityDays" name="validityDays" class="form-control form-control-sm" min="0" step="1"
                                        oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                        value="<?php echo $isEdit ? (int)($QuotData->ValidityDays ?? $DefaultValidityDays) : (int)$DefaultValidityDays; ?>" />
                                </div>
                                <div class="col-auto" style="min-width:145px;">
                                    <label for="validityDate" class="trans-field-label">Validity Date</label>
                                    <div class="input-group input-group-sm input-group-merge">
                                        <span class="input-group-text bg-white"><i class="icon-base bx bx-calendar"></i></span>
                                        <input type="text" class="form-control form-control-sm bg-white" id="validityDate_disp" readonly="readonly"
                                            value="<?php echo $isEdit ? format_datedisplay($QuotData->ValidityDate ?? '', $_fmt) : format_datedisplay($DefaultValidityDate, $_fmt); ?>"
                                            required />
                                        <input type="hidden" id="validityDate" name="validityDate"
                                            value="<?php echo $isEdit ? htmlspecialchars(format_datedisplay($QuotData->ValidityDate ?? '', 'Y-m-d')) : htmlspecialchars($DefaultValidityDate); ?>" />
                                    </div>
                                </div>
                                <div class="col">
                                    <label for="referenceDetails" class="trans-field-label"><?php echo t('lbl_reference', 'Reference'); ?></label>
                                    <input type="text" id="referenceDetails" name="referenceDetails" class="form-control form-control-sm"
                                        placeholder="PO Number, Sales Person, Ref No..." maxlength="100"
                                        value="<?php echo $isEdit ? htmlspecialchars($QuotData->Reference ?? '') : (!empty($CloneData->Reference) ? htmlspecialchars($CloneData->Reference) : ''); ?>" />
                                </div>
                            </div>

                            <?php
                            $_addrText = '';
                            if ($isEdit && isset($CustAddr) && !empty($CustAddr)) {
                                $_lineParts = array_filter([trim($CustAddr->Line1 ?? ''), trim($CustAddr->Line2 ?? '')]);
                                $_locParts  = array_filter([trim($CustAddr->CityText ?? ''), trim($CustAddr->StateText ?? '')]);
                                $_loc = implode(', ', $_locParts);
                                if (!empty(trim($CustAddr->Pincode ?? ''))) $_loc .= ' – ' . trim($CustAddr->Pincode);
                                $_addrParts = array_filter([implode(', ', $_lineParts), $_loc]);
                                $_addrText  = implode(' · ', $_addrParts);
                            }
                            ?>
                            <div id="customerAddressBox" class="trans-addr-strip <?php echo !empty($_addrText) ? '' : 'd-none'; ?>">
                                <i class="bx bx-map-pin"></i>
                                <span><?php echo htmlspecialchars($_addrText); ?></span>
                            </div>

                            <hr class="mt-3"/>

                            <?php $this->load->view('transactions/partials/form_products_add', [
                                'transNotesPlaceholder'     => 'Enter notes or anything else',
                                'transNotesContent'         => $_notesVal,
                                'transTermsContent'         => $_termsVal,
                                'transShowDropzone'         => true,
                                'transShowChargesBreakdown' => true,
                                'transSignatureUID'         => $isEdit ? (int)($QuotData->SignatureUID ?? 0) : 0,
                                'transSignatures'           => $JwtData->User->Signatures ?? [],
                                'transEditItems'            => $isEdit ? ($QuotItems ?? []) : [],
                            ]); ?>

                            <?php $this->load->view('transactions/partials/trans_summary_bar', ['_barIsSticky' => false, '_barSections' => '1', '_barButtonLayout' => 'split', '_barShowPrint' => 'create_only', '_barUseDcClasses' => false]); ?>

                        </div>
                    </div>

                    <?php $this->load->view('transactions/partials/trans_summary_bar', ['_barIsSticky' => true, '_barSections' => '1', '_barButtonLayout' => 'split', '_barShowPrint' => 'create_only', '_barUseDcClasses' => false]); ?>

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
<script src="/js/common/customer_form.js"></script>
<script src="/js/transactions/quotations.js"></script>
<script src="/js/transactions/transactions.js"></script>
<?php $this->load->view('common/transactions/pricelist_select_modal'); ?>
<script src="/js/transactions/pricelist_trans.js"></script>
<script src="/js/transactions/transprefix.js"></script>
<script src="/js/transactions/modaladdress.js"></script>
<script src="/js/common/category_form.js"></script>
<script src="/js/common/product_form.js"></script>
<script src="/js/transactions/attachments.js"></script>
<?php $this->load->view('transactions/partials/additional_charges_data'); ?>
<script src="/js/transactions/a4_print.js"></script>
<script src="/js/transactions/thermal_print.js"></script>

<script>
var _transFormData = <?php echo json_encode([
    'isEdit'       => $isEdit,
    'isDraftEdit'  => $isDraftEdit,
    'moduleUID'    => (int)($JwtData->ModuleUID ?? 0),
    'enableStorage'=> (bool)$JwtData->GenSettings->EnableStorage,
    'formId'       => $formId,
    'formAction'   => $formAction,
    'updateAction' => 'quotations/updateQuotation',
    'orgState'     => $DispatchAddress->StateText ?? '',
    'upstashUrl'   => $UpstashReadUrl   ?? '',
    'upstashToken' => $UpstashReadToken ?? '',
    'custCacheKey' => $CustomerCacheKey ?? '',
    'returnTab'    => $_returnTab,
    'returnPage'   => (int)$_returnPage,
    'currency'     => $JwtData->GenSettings->CurrenySymbol ?? '₹',
    'decimals'     => (int)($JwtData->GenSettings->DecimalPoints ?? 2),
    'editData'     => $isEdit ? [
        'custUID'           => (int)($QuotData->PartyUID ?? 0),
        'custName'          => $QuotData->PartyName  ?? '',
        'custArea'          => $QuotData->PartyArea   ?? '',
        'custMobile'        => $QuotData->PartyMobile ?? '',
        'custState'         => $CustAddr->StateText ?? '',
        'priceListUID'      => (int)($QuotData->PriceListUID ?? 0),
        'priceListData'     => !empty($QuotData->PriceListData) ? $QuotData->PriceListData : null,
        'extraDiscAmount'   => (float)($QuotData->ExtraDiscAmount ?? 0),
        'extraDiscType'     => $QuotData->ExtraDiscType ?? '',
        'globalDiscPercent' => (float)($QuotData->GlobalDiscPercent ?? 0),
        'savedCharges'      => array_values($_savedCharges),
        'attachments'       => $QuotAttachments ?? [],
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
        }, $QuotItems ?? []),
    ] : null,
    'cloneData'    => (!$isEdit && !empty($CloneData)) ? [
        'customerUID'  => (int)$CloneData->PartyUID,
        'customerName' => $CloneData->PartyName ?? '',
    ] : null,
    'cloneItems'   => (!$isEdit && !empty($CloneItems)) ? array_map(function($item) {
        return [
            'id'              => (int)$item->ProductUID,
            'text'            => $item->ProductName,
            'itemName'        => $item->ProductName,
            'partNumber'      => $item->PartNumber,
            'categoryUID'     => $item->CategoryUID,
            'storageUID'      => $item->StorageUID,
            'quantity'        => (float)$item->Quantity,
            'unitPrice'       => (float)$item->UnitPrice,
            'sellingPrice'    => (float)$item->SellingPrice,
            'taxDetailsUID'   => (int)$item->TaxDetailsUID,
            'taxPercent'      => (float)$item->TaxPercentage,
            'cgstPercent'     => (float)$item->CGST,
            'sgstPercent'     => (float)$item->SGST,
            'igstPercent'     => (float)$item->IGST,
            'discountTypeUID' => $item->DiscountTypeUID,
            'discountType'    => 'Percentage',
            'discount'        => (float)$item->Discount,
            'primaryUnit'     => $item->PrimaryUnitName,
            'productType'     => 'Product',
            'availableQuantity' => 0,
            'hsnCode'         => null,
        ];
    }, $CloneItems) : [],
]); ?>;
</script>
<script src="/js/transactions/forms/quotation.js"></script>
