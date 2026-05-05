<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('common/transactions/header'); ?>

<!-- Layout wrapper -->
<div class="layout-wrapper layout-horizontal transactionPage layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <!-- Layout container -->
        <div class="layout-page">
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

            <?php $FormAttribute = ['id' => 'addPurchForm', 'name' => 'addPurchForm', 'autocomplete' => 'off', 'data-csrf' => $this->security->get_csrf_token_name(), 'data-csrf-value' => $this->security->get_csrf_hash()];
                    echo form_open('purchases/addPurchase', $FormAttribute); ?>

                    <div class="card mb-3">

                        <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2 trans-header-static trans-theme modal-header-center-sticky">
                            <div class="d-flex align-items-center gap-3" id="transHeaderInfo">
                                <div class="trans-doc-icon" style="background-color:#f0ebff;">
                                    <i class="bx bx-cart" style="font-size:1.1rem;color:#6f42c1;"></i>
                                </div>
                                <div>
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <span class="fw-bold" style="font-size:.92rem;">Record Purchase Bill</span>
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
                                <a href="/purchases" class="btn btn-sm btn-outline-danger px-3"><i class="bx bx-x me-1"></i>Close</a>
                            </div>
                        </div>

                        <div class="card-body card-body-form-static p-4">

                            <div class="card-header modal-header-center-sticky p-1 mb-3">
                                <h5 class="modal-title mb-0"><i class="bx bx-store me-1"></i> Vendor Details</h5>
                            </div>
                            <!-- Row 1: Vendor | Type | Dispatch To | Supplier Invoice Date | Supplier Payment By -->
                            <div class="row g-2 align-items-end">
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <label for="vendorSearch" class="trans-field-label mb-0">Select Vendor <span class="text-danger">*</span></label>
                                        <button type="button" id="addTransVendor" class="trans-add-btn btn btn-outline-primary" aria-label="Add new vendor"><i class="bx bx-plus-circle me-1"></i> Vendor</button>
                                    </div>
                                    <select id="vendorSearch" name="vendorSearch" class="form-select form-select-sm"></select>
                                </div>
                                <div class="col-md-2">
                                    <label for="purchaseType" class="trans-field-label">Type <span class="text-danger">*</span></label>
                                    <select id="purchaseType" name="purchaseType" class="form-select form-select-sm" required>
                                        <option value="Regular" selected>Regular</option>
                                        <option value="Without_GST">Without GST</option>
                                    </select>
                                </div>
                                <?php if (!empty($DispatchAddress)): ?>
                                <?php
                                    $addrParts = array_filter([
                                        htmlspecialchars($DispatchAddress->Line1  ?? ''),
                                        htmlspecialchars($DispatchAddress->Line2  ?? ''),
                                    ]);
                                    $cityPin = trim(implode(' - ', array_filter([
                                        htmlspecialchars($DispatchAddress->CityText ?? ''),
                                        htmlspecialchars($DispatchAddress->Pincode  ?? ''),
                                    ])));
                                    if ($cityPin) $addrParts[] = $cityPin;
                                    if (!empty($DispatchAddress->StateText)) $addrParts[] = htmlspecialchars($DispatchAddress->StateText);
                                ?>
                                <div class="col-md-2">
                                    <label class="trans-field-label">Dispatch To <span class="text-danger">*</span></label>
                                    <select id="dispatchTo" name="dispatchTo" class="form-select form-select-sm" required>
                                        <option value="<?php echo (int)$DispatchAddress->OrgAddressUID; ?>" selected><?php echo implode(', ', $addrParts); ?></option>
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
                                        <input type="text" class="form-control form-control-sm" id="transDate" name="transDate" readonly="readonly" value="<?php echo format_datedisplay(time(), 'Y-m-d'); ?>" required />
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
                                        <input type="text" class="form-control form-control-sm" id="billDueDate" name="billDueDate" readonly="readonly" />
                                    </div>
                                </div>
                            </div>
                            <!-- Row 2: Vendor address box + Supplier Invoice No + Reference -->
                            <div class="row g-2 mt-2">
                                <div class="col-md-4">
                                    <div id="vendorAddressBox" class="p-2 border border-secondary trans-border-dotted rounded small d-none"></div>
                                </div>
                                <div class="col-md-2">
                                    <label for="supplierInvoiceNo" class="trans-field-label">Supplier Invoice No.</label>
                                    <input type="text" id="supplierInvoiceNo" name="supplierInvoiceNo" class="form-control form-control-sm"
                                        placeholder="e.g. INV-2025-0042"
                                        maxlength="100" />
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
                                        value="<?php echo !empty($POData->UniqueNumber) ? htmlspecialchars($POData->UniqueNumber) : ''; ?>" />
                                </div>
                            </div>
                            <hr class="mt-3"/>

                            <?php $this->load->view('transactions/partials/form_products_add', [
                                'transNotesPlaceholder' => 'Enter notes or anything else',
                            ]); ?>

                        <?php
                            $paymentPartyType = 'V';
                            $this->load->view('transactions/partials/payment_section', [
                                'PaymentTypes'     => $PaymentTypes ?? [],
                                'BankAccounts'     => $BankAccounts ?? [],
                                'JwtData'          => $JwtData,
                                'paymentPartyType' => $paymentPartyType,
                            ]);
                        ?>

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
<script src="/js/transactions/payment_section.js"></script>

<script>
const StateInfo     = <?php echo json_encode($StateData); ?>;
const CityInfo      = <?php echo json_encode($CityData); ?>;
const EnableStorage = <?php echo $JwtData->GenSettings->EnableStorage; ?>;
var _orgState       = '';
var _fromPO         = <?php echo !empty($POData) ? json_encode(['uid' => (int)$POData->TransUID, 'vendorUID' => (int)$POData->PartyUID, 'vendorName' => $POData->PartyName ?? '']) : 'null'; ?>;
var _fromPOItems    = <?php echo !empty($POItems) ? json_encode(array_map(function($item) {
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

$(function() {
    'use strict'

    searchVendors('vendorSearch');
    transDatePickr('#transDate', false, 'Y-m-d', false, true, true, true, 'd-m-Y');
    transDatePickr('#billDueDate', false, 'Y-m-d', false, false, true, true, 'd-m-Y', '#transDate');

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
                if (added !== false) {
                    formationTableBillItems(billManager.getItemById(item.id));
                }
            });
            if (typeof updateItemTaxBreakdown === 'function') updateItemTaxBreakdown();
            billManager.updateSummary();
        });
    }

    // ── Add Purchase Bill form submit ───────────────────────
    var $form = $('#addPurchForm');
    if ($form.length) {

        $form.on('submit', function(e) {
            e.preventDefault();

            var $btn     = $('button[type="submit"][name="action"]:focus, button[type="submit"][name="action"].active-submit', $form);
            var action   = $btn.val() || 'save';
            var csrfName = $form.data('csrf');
            var csrfVal  = $form.data('csrf-value');

            var vendorUID = parseInt($('#vendorSearch').val(), 10);
            if (!vendorUID || vendorUID <= 0) return showFormError('Please select a vendor.');

            if (action !== 'draft') {
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

            // Serialize payment rows (skip for draft)
            if (action !== 'draft') {
                if (!serializePaymentRows()) return showFormError('Please enter a valid amount for every payment row.');
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
                transTermsCond         : $.trim($('#transTermsCond').val()),
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
                PaymentRows            : $('#PaymentRowsJson').val(),
                IsFullyPaid            : $('#isFullyPaid').is(':checked') ? 1 : 0,
                RecordPayment          : action !== 'draft' ? 1 : 0,
                [csrfName]             : csrfVal,
            }, charges);

            setFormLoading('#addPurchForm', true, action);

            $.ajax({
                url    : '/purchases/addPurchase',
                method : 'POST',
                data   : postData,
                cache  : false,
                success: function(response) {
                    if (response.Error) {
                        setFormLoading('#addPurchForm', false);
                        showFormError(response.Message);
                    } else {
                        Swal.fire({
                            icon             : 'success',
                            title            : 'Purchase Bill Saved',
                            text             : response.Message || 'Purchase bill recorded successfully.',
                            confirmButtonText: 'OK',
                            timer            : 3000,
                            timerProgressBar : true,
                        }).then(function() {
                            window.location.href = '/purchases';
                        });
                    }
                },
                error: function() {
                    setFormLoading('#addPurchForm', false);
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
