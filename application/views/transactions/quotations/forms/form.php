<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$isEdit      = isset($QuotData);
$isDraftEdit = $isEdit && ($QuotData->DocStatus === 'Draft');
$transUID    = $isEdit ? (int)$QuotData->TransUID : 0;
$formId      = 'quotationForm';
$formAction  = $isEdit ? 'quotations/updateQuotation' : 'quotations/addQuotation';

if ($isEdit && !function_exists('buildQuotPrefixSegment')) {
    function buildQuotPrefixSegment($cfg) {
        if (!$cfg) return '';
        $sep   = $cfg->Separator ?? '-';
        $parts = [$cfg->Name];
        if (!empty($cfg->IncludeShortName) && !empty($cfg->ShortName)) {
            $parts[] = strtoupper($cfg->ShortName);
        }
        if (!empty($cfg->IncludeFiscalYear)) {
            $m  = (int)date('m');
            $yr = (int)date('Y');
            $fy = $m >= 4 ? $yr : $yr - 1;
            $parts[] = ($cfg->FiscalYearFormat ?? 'SHORT') === 'LONG'
                ? $fy . '-' . ($fy + 1)
                : str_pad($fy % 100, 2, '0', STR_PAD_LEFT) . '-' . str_pad(($fy + 1) % 100, 2, '0', STR_PAD_LEFT);
        }
        return implode($sep, $parts) . $sep;
    }
}

$editPrefixConfig = null;
if ($isEdit && !empty($PrefixData)) {
    foreach ($PrefixData as $_pd) {
        if ((int)$_pd->PrefixUID === (int)$QuotData->PrefixUID) {
            $editPrefixConfig = $_pd;
            break;
        }
    }
    if (!$editPrefixConfig) $editPrefixConfig = $PrefixData[0];
}
$editTransNumber = $isEdit ? ($isDraftEdit ? (int)($NextNumberMap[(int)($editPrefixConfig->PrefixUID ?? 0)] ?? 1) : (int)$QuotData->TransNumber) : 0;
$editPrefixSeg   = ($isEdit && $isDraftEdit) ? buildQuotPrefixSegment($editPrefixConfig) : '';

$_addrLines = [];
if (!empty($DispatchAddress)) {
    $_addrLines = array_filter([
        htmlspecialchars($DispatchAddress->Line1 ?? ''),
        htmlspecialchars($DispatchAddress->Line2 ?? ''),
    ]);
    $_cityPin = trim(implode(' - ', array_filter([
        htmlspecialchars($DispatchAddress->CityText ?? ''),
        htmlspecialchars($DispatchAddress->Pincode  ?? ''),
    ])));
    if ($_cityPin) $_addrLines[] = $_cityPin;
    if (!empty($DispatchAddress->StateText)) $_addrLines[] = htmlspecialchars($DispatchAddress->StateText);
}

$_notesVal = '';
$_termsVal = "1. Goods once sold will not be taken back or exchanged\n2. All disputes are subject to Gingee jurisdiction only";
if (!$isEdit) {
    $_notesVal = !empty($CloneData->Notes) ? $CloneData->Notes : '';
    $_termsVal = !empty($CloneData->TermsConditions) ? $CloneData->TermsConditions : $_termsVal;
} else {
    $_notesVal = $QuotData->Notes ?? '';
    $_termsVal = $QuotData->TermsConditions ?? $_termsVal;
}

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

                    <div class="card mb-3">

                        <?php if (!$isEdit): ?>
                        <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2 trans-header-static trans-theme modal-header-center-sticky">
                            <div class="d-flex align-items-center gap-3" id="transHeaderInfo">
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
                                <button type="submit" name="action" value="draft" class="btn btn-sm btn-outline-secondary"><i class="bx bx-save me-1"></i>Draft</button>
                                <div class="btn-group">
                                    <button type="submit" name="action" value="save" class="btn btn-sm btn-primary px-3"><i class="bx bx-check me-1"></i>Save</button>
                                    <button type="button" class="btn btn-sm btn-primary dropdown-toggle dropdown-toggle-split ps-2 pe-2" data-bs-toggle="dropdown" aria-expanded="false">
                                        <span class="visually-hidden">Save options</span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width:195px;font-size:.82rem;">
                                        <li><span class="dropdown-header py-1" style="font-size:.65rem;letter-spacing:.4px;">SAVE &amp; PRINT</span></li>
                                        <li><button type="submit" class="dropdown-item py-1" name="action" value="save_a4"><i class="bx bx-file text-primary me-2"></i>Save &amp; Print A4</button></li>
                                        <li><button type="submit" class="dropdown-item py-1" name="action" value="save_a5"><i class="bx bx-file-blank text-info me-2"></i>Save &amp; Print A5</button></li>
                                        <li><button type="submit" class="dropdown-item py-1" name="action" value="save_thermal"><i class="bx bx-receipt text-success me-2"></i>Save &amp; Print Thermal</button></li>
                                    </ul>
                                </div>
                                <a href="/quotations" class="btn btn-sm btn-outline-danger px-3"><i class="bx bx-x me-1"></i>Close</a>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="card-header bg-body-tertiary trans-header-static trans-theme modal-header-center-sticky d-flex justify-content-between align-items-center pb-3">
                            <div class="d-flex flex-wrap align-items-center gap-3" id="transHeaderInfo">
                                <h5 class="modal-title mb-0 ms-2"><?php echo $isDraftEdit ? '' : 'Edit'; ?> Quotation</h5>
                                <?php if (!$isDraftEdit && !empty($QuotData->UniqueNumber)): ?>
                                    <span class="trans-form-doc-number"><?php echo htmlspecialchars($QuotData->UniqueNumber); ?></span>
                                <?php endif; ?>
                                <div class="d-flex align-items-center gap-1">
                                    <div class="input-group w-auto <?php echo (!$isDraftEdit ? 'd-none' : ''); ?>">
                                        <select id="transPrefixSelect" name="transPrefixSelect" class="select2 form-select form-select-sm" <?php echo (!$isDraftEdit ? 'disabled' : 'required'); ?>>
                                            <?php try {
                                                if (empty($PrefixData)) throw new Exception('Prefix data not loaded');
                                                foreach ($PrefixData as $preData) {
                                                    $isSelected = (int)$preData->PrefixUID === (int)$QuotData->PrefixUID ? 'selected' : '';
                                                ?>
                                                <option value="<?php echo (int)$preData->PrefixUID; ?>"
                                                    data-sep="<?php echo htmlspecialchars($preData->Separator ?? '-'); ?>"
                                                    data-fiscal="<?php echo !empty($preData->IncludeFiscalYear) ? '1' : '0'; ?>"
                                                    data-fiscal-format="<?php echo htmlspecialchars($preData->FiscalYearFormat ?? 'SHORT'); ?>"
                                                    data-inc-short="<?php echo !empty($preData->IncludeShortName) ? '1' : '0'; ?>"
                                                    data-short-name="<?php echo htmlspecialchars($preData->ShortName ?? ''); ?>"
                                                    data-padding="<?php echo (int)($preData->NumberPadding ?? 3); ?>"
                                                    data-next-number="<?php echo (int)($NextNumberMap[(int)$preData->PrefixUID] ?? 1); ?>"
                                                    <?php echo $isSelected; ?>
                                                ><?php echo htmlspecialchars($preData->Name); ?></option>
                                            <?php }
                                            } catch (Exception $e) { ?>
                                                <option value="">Error loading prefixes</option>
                                            <?php } ?>
                                        </select>
                                        <?php if ($isDraftEdit): ?>
                                        <button type="button" class="btn btn-outline-secondary" id="addTransPrefixBtn" title="Configure Prefix"><i class="bx bx-cog"></i></button>
                                        <?php endif; ?>
                                    </div>
                                    <div class="input-group input-group-sm w-auto <?php echo (!$isDraftEdit ? 'd-none' : ''); ?>">
                                        <span class="input-group-text cursor-pointer fw-semibold text-primary" id="appendPrefixVal"><?php echo htmlspecialchars($editPrefixSeg); ?></span>
                                        <input type="number" id="transNumber" name="transNumber" class="form-control transAutoGenNumber stop-incre-indicator" maxLength="20"
                                            onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))"
                                            oninput="this.value=this.value.slice(0,this.maxLength)"
                                            pattern="[0-9]*" value="<?php echo $editTransNumber; ?>"
                                            <?php echo (!$isDraftEdit ? 'disabled' : 'required'); ?> />
                                    </div>
                                    <?php if (!$isDraftEdit): ?>
                                    <input type="hidden" name="transPrefixSelect" value="<?php echo (int)$QuotData->PrefixUID; ?>" />
                                    <input type="hidden" name="transNumber" value="<?php echo (int)$QuotData->TransNumber; ?>" />
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="submit" name="action" value="save" class="btn btn-primary"><?php echo $isDraftEdit ? 'Save' : 'Update'; ?></button>
                                <?php if ($isDraftEdit): ?>
                                <button type="submit" name="action" value="draft" class="btn btn-outline-secondary">Save as Draft</button>
                                <?php endif; ?>
                                <a href="/quotations" class="btn btn-label-danger">Close</a>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="card-body card-body-form-static p-4">

                            <div class="card-header modal-header-center-sticky p-1 mb-3">
                                <h5 class="modal-title mb-0"><i class="bx bx-user me-1"></i> Customer Details</h5>
                            </div>

                            <!-- Row 1: Customer | Type | Dispatch From | Quotation Date | Validity (Days) -->
                            <div class="row g-2 align-items-end">
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <label for="customerSearch" class="trans-field-label mb-0">Select Customer <span class="text-danger">*</span></label>
                                        <button type="button" id="addTransCustomer" class="trans-add-btn btn btn-outline-primary btn-sm" aria-label="Add new customer" style="white-space:nowrap;"><i class="bx bx-plus-circle me-1"></i>Add Customer</button>
                                    </div>
                                    <select id="customerSearch" name="customerSearch" class="form-select form-select-sm">
                                        <?php if ($isEdit && !empty($QuotData->PartyUID)): ?>
                                        <option value="<?php echo (int)$QuotData->PartyUID; ?>" selected><?php echo htmlspecialchars($QuotData->PartyName ?? ''); ?></option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="trans-field-label">Type</label>
                                    <select id="quotationType" name="quotationType" class="form-select form-select-sm" required>
                                        <option value="Regular" <?php echo ($isEdit && ($QuotData->QuotationType === 'Regular' || empty($QuotData->QuotationType))) || !$isEdit ? 'selected' : ''; ?>>Regular</option>
                                        <option value="Without_GST" <?php echo $isEdit && $QuotData->QuotationType === 'Without_GST' ? 'selected' : ''; ?>>Without GST</option>
                                    </select>
                                </div>
                                <?php if (!empty($DispatchAddresses)): ?>
                                <div class="col-md-2">
                                    <label class="trans-field-label">Dispatch From</label>
                                    <?php $this->load->view('common/transactions/_dispatch_from'); ?>
                                </div>
                                <?php endif; ?>
                                <div class="col-md-2">
                                    <label for="transDate" class="trans-field-label">Quotation Date <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-sm input-group-merge">
                                        <span class="input-group-text"><i class="icon-base bx bx-calendar"></i></span>
                                        <input type="text" class="form-control form-control-sm" id="transDate" name="transDate" readonly="readonly"
                                            value="<?php echo $isEdit ? htmlspecialchars($QuotData->TransDate ?? '') : format_datedisplay(time(), 'Y-m-d'); ?>"
                                            required />
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label for="validityDays" class="trans-field-label">Validity (Days)</label>
                                    <input type="number" id="validityDays" name="validityDays" class="form-control form-control-sm" min="0" step="1"
                                        oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                        value="<?php echo $isEdit ? (int)($QuotData->ValidityDays ?? 7) : 7; ?>" />
                                </div>
                            </div>

                            <!-- Row 2: Customer address | Validity Date | Reference -->
                            <div class="row g-2 mt-2">
                                <div class="col-md-4">
                                    <div id="customerAddressBox" class="p-2 border border-secondary trans-border-dotted rounded small <?php echo ($isEdit && isset($CustAddr) && !empty($CustAddr)) ? '' : 'd-none'; ?>">
                                        <?php if ($isEdit && isset($CustAddr) && !empty($CustAddr)): ?>
                                        <div><strong>Shipping Address:</strong></div>
                                        <div><?php echo htmlspecialchars($CustAddr->Line1 ?? ''); ?></div>
                                        <div><?php echo htmlspecialchars($CustAddr->Line2 ?? ''); ?></div>
                                        <div><?php echo trim(implode(' - ', array_filter([htmlspecialchars($CustAddr->CityText ?? ''), htmlspecialchars($CustAddr->Pincode ?? '')]))); ?></div>
                                        <div><?php echo htmlspecialchars($CustAddr->StateText ?? ''); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="validityDate" class="trans-field-label">Validity Date</label>
                                    <div class="input-group input-group-sm input-group-merge">
                                        <span class="input-group-text"><i class="icon-base bx bx-calendar"></i></span>
                                        <input type="text" class="form-control form-control-sm" id="validityDate" name="validityDate" readonly="readonly"
                                            value="<?php echo $isEdit ? htmlspecialchars($QuotData->ValidityDate ?? '') : format_datedisplay(time(), 'Y-m-d', '', null, '+7'); ?>"
                                            required />
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="referenceDetails" class="trans-field-label">Reference</label>
                                    <input type="text" id="referenceDetails" name="referenceDetails" class="form-control form-control-sm"
                                        placeholder="PO Number, Sales Person, Ref No..." maxlength="100"
                                        value="<?php echo $isEdit ? htmlspecialchars($QuotData->Reference ?? '') : (!empty($CloneData->Reference) ? htmlspecialchars($CloneData->Reference) : ''); ?>" />
                                </div>
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
                            ]); ?>

                        </div>
                    </div>

                    <?php echo form_close(); ?>

                </div>
            </div>

            <?php $this->load->view('common/transactions/transprefix'); ?>
            <?php $this->load->view('transactions/modals/customer'); ?>
            <?php $this->load->view('transactions/modals/taxdetails'); ?>
            <?php $this->load->view('products/modals/items'); ?>
            <?php $this->load->view('common/footer_desc'); ?>

        </div>

    </div>
</div>

<?php $this->load->view('common/transactions/footer'); ?>

<script src="/js/transactions/quotations.js"></script>
<script src="/js/transactions/transactions.js"></script>
<script src="/js/transactions/transprefix.js"></script>
<script src="/js/transactions/modaladdress.js"></script>
<script src="/js/transactions/products.js"></script>
<script src="/js/combinemodules/products.js"></script>
<script src="/js/transactions/attachments.js"></script>

<script>
const StateInfo     = <?php echo json_encode($StateData); ?>;
const CityInfo      = <?php echo json_encode($CityData); ?>;
const EnableStorage = <?php echo $JwtData->GenSettings->EnableStorage; ?>;
var _isEdit    = <?php echo $isEdit ? 'true' : 'false'; ?>;
var _orgState         = '<?php echo addslashes($DispatchAddress->StateText ?? ''); ?>';
var _upstashUrl       = '<?php echo addslashes($UpstashReadUrl   ?? ''); ?>';
var _upstashReadToken = '<?php echo addslashes($UpstashReadToken ?? ''); ?>';
var _custCacheKey     = '<?php echo addslashes($CustomerCacheKey ?? ''); ?>';
let imgData;

<?php if ($isEdit): ?>
var _custState = '<?php echo addslashes($CustAddr->StateText ?? ''); ?>';
var _editItems = <?php echo json_encode(array_map(function($item) {
    return [
        'id'               => (int)  $item->ProductUID,
        'text'             => $item->ProductName,
        'itemName'         => $item->ProductName,
        'description'      => $item->Description   ?? '',
        'unitPrice'        => (float)$item->UnitPrice,
        'taxAmount'        => (float)$item->TaxAmount,
        'sellingPrice'     => (float)$item->SellingPrice,
        'purchasePrice'    => (float)($item->PurchasePrice ?? 0),
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
}, $QuotItems)); ?>;
<?php else: ?>
<?php if (!empty($CloneData)): ?>
var _cloneData  = <?php echo json_encode(['customerUID' => (int)$CloneData->PartyUID, 'customerName' => $CloneData->PartyName ?? '']); ?>;
var _cloneItems = <?php echo json_encode(array_map(function($item) {
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
}, $CloneItems)); ?>;
<?php else: ?>
var _cloneData  = null;
var _cloneItems = [];
<?php endif; ?>
<?php endif; ?>

$(function() {
    'use strict'

    searchCustomers('customerSearch');
    transDatePickr('#transDate', false, 'Y-m-d', false, true, true, true, 'd-m-Y');
    transDatePickr('#validityDate', false, 'Y-m-d', false, false, false, true, 'd-m-Y', '#transDate');
    setupTransactionValidity('#transDate', '#validityDays', '#validityDate');

    <?php if ($isEdit): ?>
    initTransAttachments(<?php echo $transUID; ?>, '/quotations/getAttachments');

    $('#extraDiscount').val('<?php echo smartDecimal($QuotData->ExtraDiscAmount ?? 0); ?>');
    $('#extDiscountType').val('<?php echo addslashes($QuotData->ExtraDiscType ?? ''); ?>').trigger('change');
    $('#globalDiscount').val('<?php echo smartDecimal($QuotData->GlobalDiscPercent ?? 0); ?>').trigger('input');

    <?php if (!empty($_savedCharges)): ?>
    (function() {
        var _savedCharges = <?php echo json_encode(array_values($_savedCharges)); ?>;
        var chargeMap = {};
        _savedCharges.forEach(function(ch) { if (ch.type) chargeMap[ch.type] = ch; });
        ['shipping', 'packing'].forEach(function(type) {
            var ch = chargeMap[type];
            if (!ch) return;
            if (ch.tax != null) $('#' + type + 'Charges').val(ch.tax).trigger('change');
            if (ch.percent != null) $('#' + type + 'Percent').val(ch.percent);
            if (ch.withoutTax != null && parseFloat(ch.withoutTax) > 0) {
                $('#' + type + 'ChargeWOutTax').val(ch.withoutTax).trigger('input');
                $('#additionalChargesBox').removeClass('d-none');
            }
        });
    })();
    <?php endif; ?>

    if (typeof billManager !== 'undefined' && _orgState && _custState) {
        billManager.isInterState = (_custState.trim().toLowerCase() !== _orgState.trim().toLowerCase());
    }

    if (typeof billManager !== 'undefined' && typeof formationTableBillItems === 'function'
            && Array.isArray(_editItems) && _editItems.length > 0) {
        $('#billTableBody').empty();
        _editItems.forEach(function(item) {
            var added = billManager.addItem(item, item.quantity);
            if (added !== false) {
                formationTableBillItems(billManager.getItemById(item.id));
            }
        });
        if (typeof updateItemTaxBreakdown === 'function') updateItemTaxBreakdown();
        billManager.updateSummary();
    }
    <?php else: ?>
    if (_cloneItems && _cloneItems.length > 0) {
        var _cloneAttempts = 0;
        var _cloneInterval = setInterval(function() {
            _cloneAttempts++;
            if (typeof billManager !== 'undefined' && typeof billManager.addItem === 'function'
                    && typeof formationTableBillItems === 'function') {
                clearInterval(_cloneInterval);
                _cloneItems.forEach(function(item) {
                    var added = billManager.addItem(item, item.quantity);
                    if (added) {
                        formationTableBillItems(billManager.getItemById(item.id));
                    }
                });
            }
            if (_cloneAttempts > 50) clearInterval(_cloneInterval);
        }, 100);
    }
    <?php endif; ?>

    var $form = $('#<?php echo $formId; ?>');
    if ($form.length) {

        $form.on('submit', function(e) {
            e.preventDefault();

            var $btn     = $('button[type="submit"][name="action"]:focus, button[type="submit"][name="action"].active-submit', $form);
            var action   = $btn.val() || 'save';
            var csrfName = $form.data('csrf');
            var csrfVal  = $form.data('csrf-value');

            var customerUID = parseInt($('#customerSearch').val(), 10);
            if (!customerUID || customerUID <= 0) return showFormError('Please select a customer.');

            if (!_isEdit) {
                var prefixUID = parseInt($('#transPrefixSelect').val(), 10);
                if (!prefixUID || prefixUID <= 0) return showFormError('Please select a quotation prefix.');

                var transNumber = $.trim($('#transNumber').val());
                if (!transNumber || parseInt(transNumber, 10) <= 0) return showFormError('Transaction number must be greater than 0.');
            }

            var transDate = $.trim($('#transDate').val());
            if (!transDate || !/^\d{4}-\d{2}-\d{2}$/.test(transDate)) return showFormError('Please enter a valid transaction date.');

            var validityDate = $.trim($('#validityDate').val());
            if (validityDate && !/^\d{4}-\d{2}-\d{2}$/.test(validityDate)) return showFormError('Validity date format is invalid.');

            var items = typeof billManager !== 'undefined' ? billManager.getAllItems() : [];
            if (!items || items.length === 0) return showFormError('Please add at least one product.');

            for (var i = 0; i < items.length; i++) {
                var item = items[i];
                if (!_isEdit) {
                    var qty = parseFloat(item.quantity);
                    if (!qty || qty <= 0) return showFormError('Row ' + (i + 1) + ': Quantity must be greater than zero.');
                }
                if (parseFloat(item.unitPrice) < 0) return showFormError('Row ' + (i + 1) + ': Selling price cannot be negative.');
                var tax = parseFloat(item.taxPercent || 0);
                if (tax < 0 || tax > 100) return showFormError('Row ' + (i + 1) + ': Tax percentage must be between 0 and 100.');
            }

            var extraDiscount = parseFloat($('#extraDiscount').val()) || 0;
            if (extraDiscount < 0) return showFormError('Extra discount cannot be negative.');

            var bm             = typeof billManager !== 'undefined' ? billManager : null;
            var summary        = bm ? bm.summary : {};
            var netAmount      = summary.totals    ? (summary.totals.grandTotal          || 0) : 0;
            var subTotal       = summary.items     ? (summary.items.taxableAmount         || 0) : 0;
            var discountAmt    = summary.items     ? (summary.items.discountTotal         || 0) : 0;
            var taxAmt         = summary.taxTotals ? (summary.taxTotals.totalTax          || 0) : 0;
            var cgstAmt        = summary.taxTotals ? (summary.taxTotals.cgstTotal         || 0) : 0;
            var sgstAmt        = summary.taxTotals ? (summary.taxTotals.sgstTotal         || 0) : 0;
            var igstAmt        = summary.taxTotals ? (summary.taxTotals.igstTotal         || 0) : 0;
            var addCharges     = (summary.additionalCharges && summary.additionalCharges.total) ? (summary.additionalCharges.total.grossAmount || 0) : 0;
            var globalDiscPct  = bm ? (bm.globalDiscountPercent || 0) : 0;
            var roundOff       = summary.extra     ? (summary.extra.roundOff              || 0) : 0;

            var charges = {};
            if (summary.additionalCharges) {
                ['shipping', 'handling', 'packing', 'other'].forEach(function(t) {
                    var c = summary.additionalCharges[t];
                    if (c && c.grossAmount > 0) {
                        charges[t + 'Amount'] = c.grossAmount;
                        charges[t + 'Tax']    = c.taxPercent || 0;
                    }
                });
            }

            var postData = $.extend({
                transPrefixSelect      : parseInt($('#transPrefixSelect').val(), 10) || 0,
                transNumber            : $.trim($('#transNumber').val()) || 0,
                transDate              : transDate,
                validityDate           : validityDate,
                validityDays           : parseInt($('#validityDays').val(), 10) || 0,
                customerSearch         : customerUID,
                quotationType          : $('#quotationType').val() || '',
                dispatchFrom           : $('#dispatchFrom').val() || '',
                referenceDetails       : $.trim($('#referenceDetails').val()),
                transNotes             : $.trim($('#transNotes').val()),
                transTermsCond         : $.trim($('#transTermsCond').val()),
                extraDiscount          : extraDiscount,
                extDiscountType        : $('#extDiscountType').val() || '',
                SubTotal               : subTotal,
                DiscountAmount         : discountAmt,
                TaxAmount              : taxAmt,
                CgstAmount             : cgstAmt,
                SgstAmount             : sgstAmt,
                IgstAmount             : igstAmt,
                AdditionalChargesTotal : addCharges,
                GlobalDiscPercent      : globalDiscPct,
                RoundOff               : roundOff,
                NetAmount              : netAmount,
                Items                  : JSON.stringify(items),
                action                 : action,
                [csrfName]             : csrfVal,
            }, charges);

            if (_isEdit) {
                postData.TransUID = parseInt($('input[name="TransUID"]').val(), 10);
            }

            var formData = new FormData();
            $.each(postData, function(k, v) { formData.append(k, v); });
            if (typeof multiDropzone !== 'undefined' && multiDropzone.files.length > 0) {
                multiDropzone.files.forEach(function(f) { formData.append('AttachFiles[]', f); });
            }
            if (_isEdit) {
                formData.append('RemovedAttachIDs', JSON.stringify(typeof _removedAttachIDs !== 'undefined' ? _removedAttachIDs : []));
            }

            setFormLoading('#<?php echo $formId; ?>', true, action);

            $.ajax({
                url         : '/<?php echo $formAction; ?>',
                method      : 'POST',
                data        : formData,
                processData : false,
                contentType : false,
                cache       : false,
                success: function(response) {
                    if (response.Error) {
                        setFormLoading('#<?php echo $formId; ?>', false);
                        showFormError(response.Message);
                    } else {
                        Swal.fire({
                            icon             : 'success',
                            title            : _isEdit ? 'Quotation Updated' : 'Quotation Saved',
                            text             : response.Message || (_isEdit ? 'Quotation updated successfully.' : 'Quotation created successfully.'),
                            confirmButtonText: 'OK',
                            timer            : 3000,
                            timerProgressBar : true,
                        }).then(function() {
                            window.location.href = '/quotations';
                        });
                    }
                },
                error: function() {
                    setFormLoading('#<?php echo $formId; ?>', false);
                    showFormError('Server error. Please try again.');
                }
            });
        });

        $form.on('click', 'button[type="submit"][name="action"]', function() {
            $form.find('button[type="submit"][name="action"]').removeClass('active-submit');
            $(this).addClass('active-submit');
        });

    }

});
</script>
