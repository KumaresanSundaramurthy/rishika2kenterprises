<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('common/transactions/header'); ?>

<!-- Layout wrapper -->
<div class="layout-wrapper transactionPage layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <!-- Layout container -->
        <div class="layout-page">
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

            <?php $FormAttribute = ['id' => 'addQuotationForm', 'name' => 'addQuotationForm', 'autocomplete' => 'off', 'data-csrf' =>  $this->security->get_csrf_token_name(), 'data-csrf-value' => $this->security->get_csrf_hash()];
                    echo form_open('quotations/addQuotation', $FormAttribute); ?>

                    <div class="card mb-3">
                        
                        <div class="card-header bg-body-tertiary trans-header-static trans-theme modal-header-center-sticky d-flex justify-content-between align-items-center pb-3">
                            <div class="d-flex flex-wrap align-items-center gap-3" id="transHeaderInfo">
                                <h5 class="modal-title mb-0 ms-2">Create Quotation
                                <?php if (!empty($CloneData)): ?>
                                    <span class="badge bg-label-warning ms-2" style="font-size:.72rem;">
                                        <i class="bx bx-copy me-1"></i>Cloned from: <?php echo htmlspecialchars($CloneData->UniqueNumber ?? 'Draft'); ?>
                                    </span>
                                <?php endif; ?>
                                </h5>
                                <?php $this->load->view('transactions/partials/form_prefix_add'); ?>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="submit" name="action" value="save" class="btn btn-primary">Save</button>
                                <button type="submit" name="action" value="draft" class="btn btn-outline-secondary">Save as Draft</button>
                                <a href="/quotations" class="btn btn-label-danger">Close</a>
                            </div>
                        </div>

                        <div class="card-body card-body-form-static p-4">

                            <div class="card-header modal-header-center-sticky p-1 mb-3">
                                <h5 class="modal-title mb-0"><i class="bx bx-user me-1"></i> Customer Details</h5>
                            </div>
                            <div class="row">
                                <div class="col-md-3 trans-right-border">
                                    <div class="mb-2">
                                        <label for="quotationType" class="form-label small fw-semibold">Type <span style="color:red">*</span></label>
                                        <select id="quotationType" name="quotationType" class="form-select form-select-sm" required>
                                            <option value="Regular" selected>Regular</option>
                                            <option value="Without_GST">Without GST</option>
                                        </select>
                                    </div>
                                    <?php if (!empty($DispatchAddress)): ?>
                                        <?php
                                            $addrLines = array_filter([
                                                htmlspecialchars($DispatchAddress->Line1  ?? ''),
                                                htmlspecialchars($DispatchAddress->Line2  ?? ''),
                                            ]);
                                            $cityPin = trim(implode(' - ', array_filter([
                                                htmlspecialchars($DispatchAddress->CityText ?? ''),
                                                htmlspecialchars($DispatchAddress->Pincode  ?? ''),
                                            ])));
                                            if ($cityPin) $addrLines[] = $cityPin;
                                            if (!empty($DispatchAddress->StateText)) $addrLines[] = htmlspecialchars($DispatchAddress->StateText);
                                            ?>
                                    <div class="mb-2">
                                        <label class="form-label small fw-semibold">Dispatch From <span style="color:red">*</span></label>
                                        <select id="dispatchFrom" name="dispatchFrom" class="form-select form-select-sm" required>
                                            <option value="<?php echo (int)$DispatchAddress->OrgAddressUID; ?>" selected><?php echo implode(', ', $addrLines); ?></option>
                                        </select>
                                    </div>                                        
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6 border-end pe-3">
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <label for="customerSearch" class="form-label small fw-semibold">Select Customer <span class="text-danger">*</span></label>
                                            <button type="button" id="addTransCustomer" class="btn btn-sm btn-outline-primary mt-1" aria-label="Add new customer"><i class="bx bx-plus-circle me-1"></i> Customer</button>
                                        </div>
                                        <div class="flex-grow-1">
                                            <select id="customerSearch" name="customerSearch" class="form-select form-select-sm"></select>
                                        </div>
                                    </div>
                                    <div id="customerAddressBox" class="mt-2 p-2 border border-secondary trans-border-dotted rounded small d-none"></div>
                                </div>
                                <div class="col-md-3">
                                    <div class="d-flex gap-2 mb-2">
                                        <div class="flex-fill">
                                            <label for="transDate" class="form-label small fw-semibold">Quotation Date <span class="text-danger">*</span></label>
                                            <div class="input-group input-group-merge">
                                                <span class="input-group-text"><i class="icon-base bx bx-calendar"></i></span>
                                                <input type="text" class="form-control form-control-sm" id="transDate" name="transDate" readonly="readonly" value="<?php echo format_datedisplay(time(), 'Y-m-d'); ?>" required />
                                            </div>
                                        </div>
                                        <div class="flex-fill">
                                            <label for="validityDays" class="form-label small fw-semibold">Validity (Days)</label>
                                            <input type="number" id="validityDays" name="validityDays" class="form-control form-control-sm" min="0" step="1" oninput="this.value = this.value.replace(/[^0-9]/g, '')" value="7" />
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <label for="validityDate" class="form-label small fw-semibold">Validity Date</label>
                                        <div class="input-group input-group-merge">
                                            <span class="input-group-text"><i class="icon-base bx bx-calendar"></i></span>
                                            <input type="text" class="form-control form-control-sm" id="validityDate" name="validityDate" readonly="readonly" value="<?php echo format_datedisplay(time(), 'Y-m-d', '', null, '+7'); ?>" required />
                                        </div>
                                    </div>
                                    <div>
                                        <label for="referenceDetails" class="form-label small fw-semibold">Reference</label>
                                        <input type="text" id="referenceDetails" name="referenceDetails" class="form-control form-control-sm" placeholder="Reference, e.g. PO Number, Sales Person, Shipment No..." maxlength="100" value="<?php echo !empty($CloneData->Reference) ? htmlspecialchars($CloneData->Reference) : ''; ?>" />
                                    </div>
                                </div>
                            </div>
                            <hr/>

                            <?php $this->load->view('transactions/partials/form_products_add', [
                                'transNotesPlaceholder'     => 'Enter notes or anything else',
                                'transNotesContent'         => !empty($CloneData->Notes) ? $CloneData->Notes : '',
                                'transTermsContent'         => !empty($CloneData->TermsConditions) ? $CloneData->TermsConditions : "1. Goods once sold will not be taken back or exchanged\n2. All disputes are subject to Gingee jurisdiction only",
                                'transShowDropzone'         => true,
                                'transShowChargesBreakdown' => true,
                            ]); ?>

                        </div> <!-- /card-body -->
                    </div> <!-- /card -->

                    <?php echo form_close(); ?>

                </div>
            </div>
            <!-- Content wrapper -->
             
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

<!-- Product Module JS -->
<script src="/js/transactions/products.js"></script>
<script src="/js/combinemodules/products.js"></script>

<script>
const StateInfo = <?php echo json_encode($StateData); ?>;
const CityInfo = <?php echo json_encode($CityData); ?>;
const EnableStorage = <?php echo $JwtData->GenSettings->EnableStorage; ?>;
// Org's billing/shipping state — used by searchCustomers to detect inter-state customers
var _orgState = '<?php echo addslashes($DispatchAddress->StateText ?? ''); ?>';
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
let imgData;
$(function() {
    'use strict'

    searchCustomers('customerSearch');
    transDatePickr('#transDate', false, 'Y-m-d', false, true, true, true, 'd-m-Y');
    transDatePickr('#validityDate', false, 'Y-m-d', false, false, false, true, 'd-m-Y', '#transDate');
    
    setupTransactionValidity('#transDate', '#validityDays', '#validityDate');

    // ── Clone pre-fill ────────────────────────────────────
    // Customer: NOT auto-selected — user must choose manually
    // Items: loaded into billManager once it is ready
    if (_cloneItems && _cloneItems.length > 0) {
        var _cloneAttempts = 0;
        var _cloneInterval = setInterval(function () {
            _cloneAttempts++;
            if (typeof billManager !== 'undefined' && typeof billManager.addItem === 'function'
                && typeof formationTableBillItems === 'function') {
                clearInterval(_cloneInterval);
                _cloneItems.forEach(function (item) {
                    var added = billManager.addItem(item, item.quantity);
                    if (added) {
                        formationTableBillItems(billManager.getItemById(item.id));
                    }
                });
            }
            if (_cloneAttempts > 50) clearInterval(_cloneInterval);
        }, 100);
    }

    // ── Add Quotation form submit ─────────────────────────
    var $form = $('#addQuotationForm');
    if ($form.length) {

        $form.on('submit', function (e) {
            e.preventDefault();

            var $btn     = $('button[type="submit"][name="action"]:focus, button[type="submit"][name="action"].active-submit', $form);
            var action   = $btn.val() || 'save';
            var csrfName = $form.data('csrf');
            var csrfVal  = $form.data('csrf-value');

            // ── Client-side validation ──────────────────
            var customerUID = parseInt($('#customerSearch').val(), 10);
            if (!customerUID || customerUID <= 0) {
                return showFormError('Please select a customer.');
            }

            var prefixUID = parseInt($('#transPrefixSelect').val(), 10);
            if (!prefixUID || prefixUID <= 0) {
                return showFormError('Please select a quotation prefix.');
            }

            var transNumber = $.trim($('#transNumber').val());
            if (!transNumber || parseInt(transNumber, 10) <= 0) {
                return showFormError('Transaction number must be greater than 0.');
            }

            var transDate = $.trim($('#transDate').val());
            if (!transDate || !/^\d{4}-\d{2}-\d{2}$/.test(transDate)) {
                return showFormError('Please enter a valid transaction date.');
            }

            var validityDate = $.trim($('#validityDate').val());
            if (validityDate && !/^\d{4}-\d{2}-\d{2}$/.test(validityDate)) {
                return showFormError('Validity date format is invalid.');
            }

            var items = typeof billManager !== 'undefined' ? billManager.getAllItems() : [];
            if (!items || items.length === 0) {
                return showFormError('Please add at least one product.');
            }

            // Validate each item row
            for (var i = 0; i < items.length; i++) {
                var item = items[i];
                var qty  = parseFloat(item.quantity);
                if (!qty || qty <= 0 || !Number.isInteger(qty)) {
                    return showFormError('Row ' + (i + 1) + ': Quantity must be a positive whole number.');
                }
                if (parseFloat(item.unitPrice) < 0) {
                    return showFormError('Row ' + (i + 1) + ': Selling price cannot be negative.');
                }
                var tax = parseFloat(item.taxPercent || 0);
                if (tax < 0 || tax > 100) {
                    return showFormError('Row ' + (i + 1) + ': Tax percentage must be between 0 and 100.');
                }
            }

            var extraDiscount = parseFloat($('#extraDiscount').val()) || 0;
            if (extraDiscount < 0) {
                return showFormError('Extra discount cannot be negative.');
            }

            // ── Build summary from BillManager ─────────
            var bm       = typeof billManager !== 'undefined' ? billManager : null;
            var summary  = bm ? bm.summary : {};
            var netAmount = summary.totals ? (summary.totals.grandTotal || 0) : 0;

            var subTotal          = summary.items       ? (summary.items.taxableAmount    || 0) : 0;
            var discountAmtTotal  = summary.items       ? (summary.items.discountTotal    || 0) : 0;
            var taxAmtTotal       = summary.taxTotals   ? (summary.taxTotals.totalTax     || 0) : 0;
            var cgstAmtTotal      = summary.taxTotals   ? (summary.taxTotals.cgstTotal    || 0) : 0;
            var sgstAmtTotal      = summary.taxTotals   ? (summary.taxTotals.sgstTotal    || 0) : 0;
            var igstAmtTotal      = summary.taxTotals   ? (summary.taxTotals.igstTotal    || 0) : 0;
            var addChargesTotal   = (summary.additionalCharges && summary.additionalCharges.total)
                                        ? (summary.additionalCharges.total.grossAmount || 0) : 0;
            var globalDiscPct     = bm ? (bm.globalDiscountPercent || 0) : 0;
            var roundOff          = summary.extra       ? (summary.extra.roundOff          || 0) : 0;

            // ── Build POST data ─────────────────────────
            var charges = {};
            if (summary.additionalCharges) {
                ['shipping', 'handling', 'packing', 'other'].forEach(function (t) {
                    var c = summary.additionalCharges[t];
                    if (c && c.grossAmount > 0) {
                        charges[t + 'Amount'] = c.grossAmount;
                        charges[t + 'Tax']    = c.taxPercent || 0;
                    }
                });
            }

            var postData = $.extend({
                transPrefixSelect      : prefixUID,
                transNumber            : transNumber,
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
                DiscountAmount         : discountAmtTotal,
                TaxAmount              : taxAmtTotal,
                CgstAmount             : cgstAmtTotal,
                SgstAmount             : sgstAmtTotal,
                IgstAmount             : igstAmtTotal,
                AdditionalChargesTotal : addChargesTotal,
                GlobalDiscPercent      : globalDiscPct,
                RoundOff               : roundOff,
                NetAmount              : netAmount,
                Items                  : JSON.stringify(items),
                action                 : action,
                [csrfName]             : csrfVal,
            }, charges);

            setFormLoading('#addQuotationForm', true, action);

            $.ajax({
                url     : '/quotations/addQuotation',
                method  : 'POST',
                data    : postData,
                cache   : false,
                success : function (response) {
                    if (response.Error) {
                        setFormLoading('#addQuotationForm', false);
                        showFormError(response.Message);
                    } else {
                        // Keep buttons disabled — redirect is imminent; prevent any re-submission
                        Swal.fire({
                            icon             : 'success',
                            title            : 'Quotation Saved',
                            text             : response.Message || 'Quotation created successfully.',
                            confirmButtonText: 'OK',
                            timer            : 3000,
                            timerProgressBar : true,
                        }).then(function () {
                            window.location.href = '/quotations';
                        });
                    }
                },
                error: function () {
                    setFormLoading('#addQuotationForm', false);
                    showFormError('Server error. Please try again.');
                }
            });
        });

        // Track which submit button was clicked
        $form.on('click', 'button[type="submit"][name="action"]', function () {
            $form.find('button[type="submit"][name="action"]').removeClass('active-submit');
            $(this).addClass('active-submit');
        });

    }

});
</script>