<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$isEdit      = isset($PurchData);
$isDraftEdit = $isEdit && ($PurchData->DocStatus === 'Draft');
$transUID    = $isEdit ? (int)$PurchData->TransUID : 0;
$formId      = 'purchForm';
$formAction  = $isEdit ? 'purchases/updatePurchase' : 'purchases/addPurchase';

// Edit: resolve prefix config for the existing transaction
$editPrefixConfig = null;
if ($isEdit && !empty($PrefixData)) {
    foreach ($PrefixData as $_pd) {
        if ((int)$_pd->PrefixUID === (int)$PurchData->PrefixUID) {
            $editPrefixConfig = $_pd;
            break;
        }
    }
    if (!$editPrefixConfig) $editPrefixConfig = $PrefixData[0];
}

if ($isEdit && !function_exists('buildPurchPrefixSegment')) {
    function buildPurchPrefixSegment($cfg) {
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

$editTransNumber = ($isEdit && $isDraftEdit)
    ? (int)($NextNumberMap[(int)($editPrefixConfig->PrefixUID ?? 0)] ?? 1)
    : ($isEdit ? (int)$PurchData->TransNumber : 0);
$editPrefixSeg   = ($isEdit && $isDraftEdit) ? buildPurchPrefixSegment($editPrefixConfig) : '';

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

// Dispatch address display string
$_addrParts = [];
if (!empty($DispatchAddress)) {
    $_addrParts = array_filter([
        htmlspecialchars($DispatchAddress->Line1 ?? ''),
        htmlspecialchars($DispatchAddress->Line2 ?? ''),
    ]);
    $_cityPin = trim(implode(' - ', array_filter([
        htmlspecialchars($DispatchAddress->CityText ?? ''),
        htmlspecialchars($DispatchAddress->Pincode  ?? ''),
    ])));
    if ($_cityPin) $_addrParts[] = $_cityPin;
    if (!empty($DispatchAddress->StateText)) $_addrParts[] = htmlspecialchars($DispatchAddress->StateText);
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

                    <div class="card mb-3">

                        <!-- ── Card Header ── -->
                        <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2 trans-header-static trans-theme modal-header-center-sticky">
                            <div class="d-flex align-items-center gap-3" id="transHeaderInfo">
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
                                            <div class="d-flex align-items-center gap-1">
                                                <div class="input-group w-auto <?php echo (!$isDraftEdit ? 'd-none' : ''); ?>">
                                                    <select id="transPrefixSelect" name="transPrefixSelect" class="select2 form-select form-select-sm" <?php echo (!$isDraftEdit ? 'disabled' : 'required'); ?>>
                                                    <?php try {
                                                            if (empty($PrefixData)) throw new Exception();
                                                            foreach ($PrefixData as $preData) {
                                                                $isSelected = (int)$preData->PrefixUID === (int)$PurchData->PrefixUID ? 'selected' : '';
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
                                                    <input type="number" id="transNumber" name="transNumber"
                                                        class="form-control transAutoGenNumber stop-incre-indicator"
                                                        maxLength="20"
                                                        onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))"
                                                        oninput="this.value=this.value.slice(0,this.maxLength)"
                                                        pattern="[0-9]*"
                                                        value="<?php echo $editTransNumber; ?>"
                                                        <?php echo (!$isDraftEdit ? 'disabled' : 'required'); ?> />
                                                </div>
                                                <?php if (!$isDraftEdit): ?>
                                                <input type="hidden" name="transPrefixSelect" value="<?php echo (int)$PurchData->PrefixUID; ?>" />
                                                <input type="hidden" name="transNumber" value="<?php echo (int)$PurchData->TransNumber; ?>" />
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <?php if (!$isEdit): ?>
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
                                <?php elseif ($isDraftEdit): ?>
                                    <button type="submit" name="action" value="save" class="btn btn-sm btn-primary"><i class="bx bx-check me-1"></i>Save</button>
                                    <button type="submit" name="action" value="draft" class="btn btn-sm btn-outline-secondary"><i class="bx bx-save me-1"></i>Save as Draft</button>
                                <?php else: ?>
                                    <button type="submit" name="action" value="save" class="btn btn-sm btn-primary"><i class="bx bx-check me-1"></i>Update</button>
                                <?php endif; ?>
                                <a href="/purchases" class="btn btn-sm btn-outline-danger px-3"><i class="bx bx-x me-1"></i>Close</a>
                            </div>
                        </div>

                        <div class="card-body card-body-form-static p-4">

                            <div class="card-header modal-header-center-sticky p-1 mb-3">
                                <h5 class="modal-title mb-0"><i class="bx bx-store me-1"></i> Vendor Details</h5>
                            </div>

                            <!-- Row 1: Vendor | Type | Dispatch To | Invoice Date | Payment By -->
                            <div class="row g-2 align-items-end">
                                <div class="col-md-4">
                                    <?php if ($isEdit): ?>
                                        <label class="trans-field-label mb-1">Vendor</label>
                                        <div class="trans-vendor-card">
                                            <div class="trans-vendor-card-name">
                                                <i class="bx bx-store me-1"></i><?php echo htmlspecialchars($PurchData->PartyName ?? '—'); ?>
                                            </div>
                                            <?php if (!empty($PurchData->PartyMobile)): ?>
                                            <div class="trans-vendor-card-meta"><i class="bx bx-phone me-1"></i><?php echo htmlspecialchars($PurchData->PartyMobile); ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($PurchData->PartyGSTIN)): ?>
                                            <div class="trans-vendor-card-meta"><i class="bx bx-id-card me-1"></i><?php echo htmlspecialchars($PurchData->PartyGSTIN); ?></div>
                                            <?php endif; ?>
                                            <?php
                                                $_vParts = array_filter([
                                                    $VendorAddr->Line1     ?? '',
                                                    $VendorAddr->CityText  ?? '',
                                                    $VendorAddr->StateText ?? '',
                                                ]);
                                                if (!empty($_vParts)):
                                            ?>
                                            <div class="trans-vendor-card-meta"><i class="bx bx-map me-1"></i><?php echo htmlspecialchars(implode(', ', $_vParts)); ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <input type="hidden" id="vendorSearch" name="vendorSearch" value="<?php echo (int)$PurchData->PartyUID; ?>" />
                                    <?php else: ?>
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <label for="vendorSearch" class="trans-field-label mb-1">Select Vendor <span class="text-danger">*</span></label>
                                            <button type="button" id="addTransVendor" class="trans-add-btn btn btn-outline-primary btn-sm" aria-label="Add new vendor" style="white-space:nowrap;"><i class="bx bx-plus-circle me-1"></i>Add Vendor</button>
                                        </div>
                                        <select id="vendorSearch" name="vendorSearch" class="form-select form-select-sm"></select>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-2">
                                    <label for="purchaseType" class="trans-field-label">Type <span class="text-danger">*</span></label>
                                    <select id="purchaseType" name="purchaseType" class="form-select form-select-sm" required>
                                        <option value="Regular" <?php echo (!$isEdit || $PurchData->QuotationType === 'Regular' || empty($PurchData->QuotationType)) ? 'selected' : ''; ?>>Regular</option>
                                        <option value="Without_GST" <?php echo ($isEdit && $PurchData->QuotationType === 'Without_GST') ? 'selected' : ''; ?>>Without GST</option>
                                    </select>
                                </div>
                                <?php if (!empty($DispatchAddress)): ?>
                                <div class="col-md-2">
                                    <label class="trans-field-label">Dispatch To <span class="text-danger">*</span></label>
                                    <select id="dispatchTo" name="dispatchTo" class="form-select form-select-sm" required>
                                        <option value="<?php echo (int)$DispatchAddress->OrgAddressUID; ?>" selected><?php echo implode(', ', $_addrParts); ?></option>
                                    </select>
                                </div>
                                <?php endif; ?>
                                <div class="col-md-2">
                                    <label for="transDate" class="trans-field-label">
                                        Supplier Invoice Date <span class="text-danger">*</span>
                                        <i class="bx bx-help-circle ms-1 text-muted" style="font-size:.82rem;cursor:pointer;"
                                           data-bs-toggle="tooltip" data-bs-placement="top"
                                           title="The date printed on the supplier's invoice. This is the official billing date from your vendor and is used for GST reporting and payment tracking."></i>
                                    </label>
                                    <div class="input-group input-group-sm input-group-merge">
                                        <span class="input-group-text"><i class="icon-base bx bx-calendar"></i></span>
                                        <input type="text" class="form-control form-control-sm" id="transDate" name="transDate" readonly="readonly"
                                            value="<?php echo $isEdit ? htmlspecialchars(format_datedisplay($PurchData->TransDate, 'Y-m-d')) : format_datedisplay(time(), 'Y-m-d'); ?>"
                                            required />
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label for="billDueDate" class="trans-field-label">
                                        Supplier Payment By
                                        <i class="bx bx-help-circle ms-1 text-muted" style="font-size:.82rem;cursor:pointer;"
                                           data-bs-toggle="tooltip" data-bs-placement="top"
                                           title="The deadline by which you must pay your vendor. Keeping track of this helps you avoid late payment penalties and maintain a good supplier relationship."></i>
                                    </label>
                                    <div class="input-group input-group-sm input-group-merge">
                                        <span class="input-group-text"><i class="icon-base bx bx-calendar"></i></span>
                                        <input type="text" class="form-control form-control-sm" id="billDueDate" name="billDueDate" readonly="readonly"
                                            value="<?php echo ($isEdit && !empty($PurchData->ValidityDate)) ? htmlspecialchars(format_datedisplay($PurchData->ValidityDate, 'Y-m-d')) : ''; ?>" />
                                    </div>
                                </div>
                            </div>

                            <!-- Row 2: Vendor address box | Supplier Invoice No | Reference -->
                            <div class="row g-2 mt-2">
                                <div class="col-md-4">
                                    <div id="vendorAddressBox" class="p-2 border border-secondary trans-border-dotted rounded small d-none"></div>
                                </div>
                                <div class="col-md-2">
                                    <label for="supplierInvoiceNo" class="trans-field-label">Supplier Invoice No.</label>
                                    <input type="text" id="supplierInvoiceNo" name="supplierInvoiceNo" class="form-control form-control-sm"
                                        placeholder="e.g. INV-2025-0042" maxlength="100"
                                        value="<?php echo $isEdit ? htmlspecialchars($PurchData->SupplierInvoiceNo ?? '') : ''; ?>" />
                                </div>
                                <div class="col-md-4">
                                    <label for="referenceDetails" class="trans-field-label">
                                        Reference / PO No.
                                        <i class="bx bx-help-circle ms-1 text-muted" style="font-size:.82rem;cursor:pointer;"
                                           data-bs-toggle="tooltip" data-bs-placement="top"
                                           title="Use this field to link the bill to a related document. You can enter the supplier's Purchase Order number, your internal order reference, a shipment or tracking number, or the name of the person who placed the order."></i>
                                    </label>
                                    <input type="text" id="referenceDetails" name="referenceDetails" class="form-control form-control-sm"
                                        placeholder="e.g. PO-2025-001, Shipment #TRK456, Sales Person: Ravi, Indent No: IND-88"
                                        maxlength="100"
                                        value="<?php echo $isEdit ? htmlspecialchars($PurchData->Reference ?? '') : (!empty($POData->UniqueNumber) ? htmlspecialchars($POData->UniqueNumber) : ''); ?>" />
                                </div>
                            </div>
                            <hr class="mt-3"/>

                            <?php $this->load->view('transactions/partials/form_products_add', [
                                'transNotesPlaceholder' => 'Enter notes or anything else',
                                'transHideTerms'        => true,
                                'transNotesContent'     => $isEdit ? $_userNotes : '',
                                'transShowDropzone'     => true,
                                'transPaymentVars'      => !$isEdit ? [
                                    'PaymentTypes'     => $PaymentTypes ?? [],
                                    'BankAccounts'     => $BankAccounts ?? [],
                                    'JwtData'          => $JwtData,
                                    'paymentPartyType' => 'V',
                                ] : null,
                            ]); ?>

                        </div> <!-- /card-body -->
                    </div> <!-- /card -->

                    <?php echo form_close(); ?>

                </div>
            </div>

            <?php $this->load->view('common/transactions/transprefix'); ?>
            <?php $this->load->view('transactions/modals/vendor'); ?>
            <?php $this->load->view('transactions/modals/taxdetails'); ?>
            <?php $this->load->view('products/modals/items'); ?>
            <?php $this->load->view('common/footer_desc'); ?>

        </div>

    </div>
</div>

<?php $this->load->view('common/transactions/footer'); ?>

<script src="/js/transactions/purchases.js"></script>
<script src="/js/transactions/transactions.js"></script>
<script src="/js/transactions/transprefix.js"></script>
<script src="/js/transactions/modaladdress.js"></script>
<script src="/js/transactions/products.js"></script>
<script src="/js/combinemodules/products.js"></script>
<?php if (!$isEdit): ?>
<script src="/js/transactions/payment_section.js"></script>
<?php endif; ?>
<script src="/js/transactions/attachments.js"></script>

<script>
const StateInfo     = <?php echo json_encode($StateData); ?>;
const CityInfo      = <?php echo json_encode($CityData); ?>;
const EnableStorage = <?php echo $JwtData->GenSettings->EnableStorage; ?>;
var _orgState       = '';
var _isEdit         = <?php echo $isEdit ? 'true' : 'false'; ?>;
var _transUID       = <?php echo $transUID; ?>;
var _vendorState    = '<?php echo isset($VendorAddr) ? addslashes($VendorAddr->StateText ?? '') : ''; ?>';

<?php if ($isEdit): ?>
var _editItems = <?php echo json_encode(array_map(function($item) {
    return [
        'id'               => (int)  $item->ProductUID,
        'text'             => $item->ProductName,
        'itemName'         => $item->ProductName,
        'unitPrice'        => (float)$item->UnitPrice,
        'taxAmount'        => (float)$item->TaxAmount,
        'sellingPrice'     => (float)$item->SellingPrice,
        'purchasePrice'    => 0,
        'availableQuantity'=> 0,
        'hsnCode'          => '',
        'categoryUID'      => $item->CategoryUID ? (int)$item->CategoryUID : null,
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
}, $PurchItems)); ?>;
<?php else: ?>
var _fromPO      = <?php echo !empty($POData) ? json_encode(['uid' => (int)$POData->TransUID, 'vendorUID' => (int)$POData->PartyUID, 'vendorName' => $POData->PartyName ?? '']) : 'null'; ?>;
var _fromPOItems = <?php echo !empty($POItems) ? json_encode(array_map(function($item) {
    return [
        'id'               => (int)  $item->ProductUID,
        'text'             => $item->ProductName,
        'itemName'         => $item->ProductName,
        'unitPrice'        => (float)$item->UnitPrice,
        'taxAmount'        => (float)$item->TaxAmount,
        'sellingPrice'     => (float)$item->SellingPrice,
        'purchasePrice'    => 0,
        'availableQuantity'=> 0,
        'hsnCode'          => '',
        'categoryUID'      => $item->CategoryUID ? (int)$item->CategoryUID : null,
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
}, $POItems)) : 'null'; ?>;
<?php endif; ?>

$(function() {
    'use strict';

    <?php if ($isEdit): ?>
    renderTransAttachmentsFromData(<?php echo json_encode($PurchAttachments ?? []); ?>);
    <?php endif; ?>

    <?php if (!$isEdit): ?>
    searchVendors('vendorSearch');
    <?php endif; ?>
    transDatePickr('#transDate', false, 'Y-m-d', false, true, true, true, 'd-m-Y');
    transDatePickr('#billDueDate', false, 'Y-m-d', false, false, <?php echo $isEdit ? 'false' : 'true'; ?>, true, 'd-m-Y', '#transDate');

    <?php if ($isEdit): ?>
    // Vendor is pre-set via hidden input — no select2 needed

    if (typeof billManager !== 'undefined' && _orgState && _vendorState) {
        billManager.isInterState = (_vendorState.trim().toLowerCase() !== _orgState.trim().toLowerCase());
    }

    if (typeof billManager !== 'undefined' && typeof formationTableBillItems === 'function'
            && Array.isArray(_editItems) && _editItems.length > 0) {
        $('#billTableBody').empty();
        _editItems.forEach(function(item) {
            var added = billManager.addItem(item, item.quantity);
            if (added !== false) formationTableBillItems(billManager.getItemById(item.id));
        });
        if (typeof updateItemTaxBreakdown === 'function') updateItemTaxBreakdown();
        billManager.updateSummary();
    }

    <?php if (!empty($PurchData->GlobalDiscPercent) && $PurchData->GlobalDiscPercent > 0): ?>
    $('#globalDiscount').val('<?php echo smartDecimal($PurchData->GlobalDiscPercent); ?>').trigger('input');
    <?php endif; ?>
    <?php if (!empty($PurchData->ExtraDiscAmount) && $PurchData->ExtraDiscAmount > 0): ?>
    $('#extraDiscount').val('<?php echo smartDecimal($PurchData->ExtraDiscAmount ?? 0); ?>');
    <?php endif; ?>
    <?php if (!empty($PurchData->ExtraDiscType)): ?>
    $('#extDiscountType').val('<?php echo addslashes($PurchData->ExtraDiscType); ?>');
    <?php endif; ?>

    <?php else: ?>
    // Pre-fill vendor and items from Purchase Order
    if (_fromPO && _fromPO.vendorUID) {
        $('#vendorSearch').append(new Option(
            _fromPO.vendorName || '',
            _fromPO.vendorUID, true, true
        )).trigger('change');
    }

    if (Array.isArray(_fromPOItems) && _fromPOItems.length > 0) {
        $(document).one('billmanager:ready', function() {
            $('#billTableBody').empty();
            _fromPOItems.forEach(function(item) {
                var added = billManager.addItem(item, item.quantity);
                if (added !== false) formationTableBillItems(billManager.getItemById(item.id));
            });
            if (typeof updateItemTaxBreakdown === 'function') updateItemTaxBreakdown();
            billManager.updateSummary();
        });
    }
    <?php endif; ?>

    // ── Form submit ────────────────────────────────────────────────
    var $form = $('#<?php echo $formId; ?>');
    if ($form.length) {

        $form.on('submit', function(e) {
            e.preventDefault();

            var $btn     = $('button[type="submit"][name="action"]:focus, button[type="submit"][name="action"].active-submit', $form);
            var action   = $btn.val() || 'save';
            var csrfName = $form.data('csrf');
            var csrfVal  = $form.data('csrf-value');

            var vendorUID = parseInt($('#vendorSearch').val(), 10);
            if (!vendorUID || vendorUID <= 0) return showFormError('Please select a vendor.');

            if (!_isEdit && action !== 'draft') {
                var prefixUID = parseInt($('#transPrefixSelect').val(), 10);
                if (!prefixUID || prefixUID <= 0) return showFormError('Please select a bill prefix.');
                var transNumber = $.trim($('#transNumber').val());
                if (!transNumber || parseInt(transNumber, 10) <= 0) return showFormError('Transaction number must be greater than 0.');
            }

            var transDate = $.trim($('#transDate').val());
            if (!transDate || !/^\d{4}-\d{2}-\d{2}$/.test(transDate)) return showFormError('Please enter a valid bill date.');

            var items = typeof billManager !== 'undefined' ? billManager.getAllItems() : [];
            if (!items || items.length === 0) return showFormError('Please add at least one product.');

            for (var i = 0; i < items.length; i++) {
                var item = items[i];
                var qty  = parseFloat(item.quantity);
                if (!qty || qty <= 0) return showFormError('Row ' + (i + 1) + ': Quantity must be greater than 0.');
                if (parseFloat(item.unitPrice) < 0) return showFormError('Row ' + (i + 1) + ': Price cannot be negative.');
            }

            var bm            = typeof billManager !== 'undefined' ? billManager : null;
            var summary       = bm ? bm.summary : {};
            var netAmount     = summary.totals    ? (summary.totals.grandTotal       || 0) : 0;
            var subTotal      = summary.items     ? (summary.items.taxableAmount     || 0) : 0;
            var discountAmt   = summary.items     ? (summary.items.discountTotal     || 0) : 0;
            var taxAmt        = summary.taxTotals ? (summary.taxTotals.totalTax      || 0) : 0;
            var cgstAmt       = summary.taxTotals ? (summary.taxTotals.cgstTotal     || 0) : 0;
            var sgstAmt       = summary.taxTotals ? (summary.taxTotals.sgstTotal     || 0) : 0;
            var igstAmt       = summary.taxTotals ? (summary.taxTotals.igstTotal     || 0) : 0;
            var addCharges    = (summary.additionalCharges && summary.additionalCharges.total) ? (summary.additionalCharges.total.grossAmount || 0) : 0;
            var globalDiscPct = bm ? (bm.globalDiscountPercent || 0) : 0;
            var roundOff      = summary.extra ? (summary.extra.roundOff || 0) : 0;
            var extraDisc     = parseFloat($('#extraDiscount').val()) || 0;

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

            if (!_isEdit && action !== 'draft') {
                if (typeof serializePaymentRows === 'function' && !serializePaymentRows()) return showFormError('Please enter a valid amount for every payment row.');
            }

            var postData = $.extend({
                transPrefixSelect      : parseInt($('#transPrefixSelect').val(), 10) || 0,
                transNumber            : $.trim($('#transNumber').val()),
                transDate              : transDate,
                billDueDate            : $.trim($('#billDueDate').val()),
                vendorSearch           : vendorUID,
                purchaseType           : $('#purchaseType').val() || '',
                supplierInvoiceNo      : $.trim($('#supplierInvoiceNo').val()),
                referenceDetails       : $.trim($('#referenceDetails').val()),
                transNotes             : $.trim($('#transNotes').val()),
                extraDiscount          : extraDisc,
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
                PaymentRows            : !_isEdit ? $('#PaymentRowsJson').val() : '',
                IsFullyPaid            : (!_isEdit && $('#isFullyPaid').is(':checked')) ? 1 : 0,
                RecordPayment          : (!_isEdit && action !== 'draft') ? 1 : 0,
                [csrfName]             : csrfVal,
            }, charges);

            if (_isEdit) postData.TransUID = _transUID;

            var formData = new FormData();
            $.each(postData, function(k, v) { formData.append(k, v); });
            if (typeof multiDropzone !== 'undefined' && multiDropzone.files.length > 0) {
                multiDropzone.files.forEach(function(f) { formData.append('AttachFiles[]', f); });
            }
            if (_isEdit) {
                formData.append('RemovedAttachIDs', JSON.stringify(typeof _removedAttachIDs !== 'undefined' ? _removedAttachIDs : []));
            }

            var ajaxUrl = _isEdit ? '/purchases/updatePurchase' : '/purchases/addPurchase';
            setFormLoading('#<?php echo $formId; ?>', true, action);

            $.ajax({
                url         : ajaxUrl,
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
                            title            : _isEdit ? 'Purchase Bill Updated' : 'Purchase Bill Saved',
                            text             : response.Message || (_isEdit ? 'Purchase bill updated successfully.' : 'Purchase bill recorded successfully.'),
                            confirmButtonText: 'OK',
                            timer            : 3000,
                            timerProgressBar : true,
                        }).then(function() {
                            window.location.href = '/purchases';
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
