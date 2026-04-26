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

            <?php $FormAttribute = ['id' => 'addInvForm', 'name' => 'addInvForm', 'autocomplete' => 'off', 'data-csrf' => $this->security->get_csrf_token_name(), 'data-csrf-value' => $this->security->get_csrf_hash()];
                    echo form_open('invoices/addInvoice', $FormAttribute); ?>

                    <!-- Hidden: source UIDs for conversion tracking -->
                    <input type="hidden" name="fromSalesOrderUID" id="fromSalesOrderUID" value="<?php echo (int)($FromSalesOrderUID ?? 0); ?>" />
                    <input type="hidden" name="fromQuotationUID" id="fromQuotationUID" value="<?php echo (int)($FromQuotationUID ?? 0); ?>" />

                    <div class="card mb-3">

                        <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2 trans-header-static trans-theme modal-header-center-sticky">
                            <div class="d-flex align-items-center gap-3" id="transHeaderInfo">
                                <div class="trans-doc-icon bg-primary bg-opacity-10">
                                    <i class="bx bx-receipt text-primary" style="font-size:1.1rem;"></i>
                                </div>
                                <div>
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <span class="fw-bold" style="font-size:.92rem;">Create Invoice</span>
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
                                <a href="/invoices" class="btn btn-sm btn-outline-danger px-3"><i class="bx bx-x me-1"></i>Close</a>
                            </div>
                        </div>

                        <div class="card-body card-body-form-static p-4">

                            <div class="card-header modal-header-center-sticky p-1 mb-3">
                                <h5 class="modal-title mb-0"><i class="bx bx-user me-1"></i> Customer Details</h5>
                            </div>
                            <div class="row">
                                <div class="col-md-3 trans-right-border">
                                    <div class="mb-2">
                                        <label for="invoiceType" class="form-label small fw-semibold">Type <span style="color:red">*</span></label>
                                        <select id="invoiceType" name="invoiceType" class="form-select form-select-sm" required>
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
                                    <div class="mb-2">
                                        <label for="transDate" class="form-label small fw-semibold">Invoice Date <span class="text-danger">*</span></label>
                                        <div class="input-group input-group-merge">
                                            <span class="input-group-text"><i class="icon-base bx bx-calendar"></i></span>
                                            <input type="text" class="form-control form-control-sm" id="transDate" name="transDate" readonly="readonly" value="<?php echo format_datedisplay(time(), 'Y-m-d'); ?>" required />
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <label for="dueDate" class="form-label small fw-semibold">Due Date</label>
                                        <div class="input-group input-group-merge">
                                            <span class="input-group-text"><i class="icon-base bx bx-calendar"></i></span>
                                            <input type="text" class="form-control form-control-sm" id="dueDate" name="dueDate" readonly="readonly" />
                                        </div>
                                    </div>
                                    <div>
                                        <label for="referenceDetails" class="form-label small fw-semibold">Reference</label>
                                        <input type="text" id="referenceDetails" name="referenceDetails" class="form-control form-control-sm" placeholder="PO Number, Sales Person, Ref No..." maxlength="100"
                                        <?php if (!empty($SalesOrderData)): ?>
                                            value="<?php echo htmlspecialchars($SalesOrderData->Reference ?? ''); ?>"
                                        <?php elseif (!empty($QuotationData)): ?>
                                            value="<?php echo htmlspecialchars($QuotationData->Reference ?? ''); ?>"
                                        <?php endif; ?> />
                                    </div>
                                </div>
                            </div>
                            <hr/>

                            <?php $this->load->view('transactions/partials/form_products_add', [
                                'transNotesPlaceholder' => 'Enter notes or anything else',
                                'transNotesContent'     => !empty($SalesOrderData->Notes) ? $SalesOrderData->Notes : (!empty($QuotationData->Notes) ? $QuotationData->Notes : ''),
                                'transTermsContent'     => !empty($SalesOrderData->TermsConditions) ? $SalesOrderData->TermsConditions : (!empty($QuotationData->TermsConditions) ? $QuotationData->TermsConditions : "1. Goods once sold will not be taken back or exchanged\n2. All disputes are subject to Gingee jurisdiction only"),
                            ]); ?>

                        <?php
                            $paymentPartyType = 'C';
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
            <?php $this->load->view('transactions/modals/customer'); ?>
            <?php $this->load->view('transactions/modals/taxdetails'); ?>
            <?php $this->load->view('products/modals/items'); ?>
            <?php $this->load->view('common/footer_desc'); ?>

        </div>

    </div>
</div>

<?php $this->load->view('common/transactions/footer'); ?>

<script src="/js/transactions/invoices.js"></script>
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
var _orgState       = '<?php echo addslashes($DispatchAddress->StateText ?? ''); ?>';

<?php if (!empty($SalesOrderData)): ?>
var _fromSO      = <?php echo json_encode(['uid' => (int)$FromSalesOrderUID, 'customer' => (int)$SalesOrderData->PartyUID, 'customerName' => $SalesOrderData->PartyName ?? '']); ?>;
var _fromSOItems = <?php echo json_encode(array_map(function($item) {
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
}, $SalesOrderItems ?? [])); ?>;
<?php else: ?>
var _fromSO      = null;
var _fromSOItems = [];
<?php endif; ?>

<?php if (!empty($QuotationData)): ?>
var _fromQuotation = <?php echo json_encode(['uid' => (int)$FromQuotationUID, 'customer' => (int)$QuotationData->PartyUID, 'customerName' => $QuotationData->PartyName ?? '']); ?>;
var _fromQuotItems = <?php echo json_encode(array_map(function($item) {
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
}, $QuotationItems ?? [])); ?>;
<?php else: ?>
var _fromQuotation = null;
var _fromQuotItems = [];
<?php endif; ?>

let imgData;

$(function() {
    'use strict'

    searchCustomers('customerSearch');
    transDatePickr('#transDate', false, 'Y-m-d', false, true, true, true, 'd-m-Y');
    transDatePickr('#dueDate', false, 'Y-m-d', false, false, true, true, 'd-m-Y', '#transDate');

    // Default due date = invoice date; sync when invoice date changes
    var _dueDatePicker = document.querySelector('#dueDate')._flatpickr;
    var _transDatePicker = document.querySelector('#transDate')._flatpickr;
    if (_dueDatePicker && _transDatePicker) {
        _dueDatePicker.setDate(_transDatePicker.selectedDates[0], true);
        document.querySelector('#transDate').addEventListener('change', function () {
            if (_transDatePicker.selectedDates[0]) {
                _dueDatePicker.setDate(_transDatePicker.selectedDates[0], true);
            }
        });
    }

    // Pre-fill from Sales Order / Quotation conversion
    var _sourceData  = _fromSO || _fromQuotation;
    var _sourceItems = _fromSO ? _fromSOItems : _fromQuotItems;

    if (_sourceData && _sourceData.uid > 0) {
        if (_sourceData.customer > 0) {
            $('#customerSearch').append(new Option(_sourceData.customerName, _sourceData.customer, true, true)).trigger('change');
        }
        if (typeof billManager !== 'undefined' && typeof formationTableBillItems === 'function'
                && Array.isArray(_sourceItems) && _sourceItems.length > 0) {
            $('#billTableBody').empty();
            _sourceItems.forEach(function(item) {
                var added = billManager.addItem(item, item.quantity);
                if (added !== false) {
                    formationTableBillItems(billManager.getItemById(item.id));
                }
            });
            if (typeof updateItemTaxBreakdown === 'function') updateItemTaxBreakdown();
            billManager.updateSummary();
        }
    }

    // ── Add Invoice form submit ───────────────────────
    var $form = $('#addInvForm');
    if ($form.length) {

        $form.on('submit', function(e) {
            e.preventDefault();

            var $btn     = $('button[type="submit"][name="action"]:focus, button[type="submit"][name="action"].active-submit', $form);
            var action   = $btn.val() || 'save';
            var csrfName = $form.data('csrf');
            var csrfVal  = $form.data('csrf-value');

            var customerUID = parseInt($('#customerSearch').val(), 10);
            if (!customerUID || customerUID <= 0) return showFormError('Please select a customer.');

            if (action !== 'draft') {
                var prefixUID = parseInt($('#transPrefixSelect').val(), 10);
                if (!prefixUID || prefixUID <= 0) return showFormError('Please select an invoice prefix.');

                var transNumber = $.trim($('#transNumber').val());
                if (!transNumber || parseInt(transNumber, 10) <= 0) return showFormError('Transaction number must be greater than 0.');
            }

            var transDate = $.trim($('#transDate').val());
            if (!transDate || !/^\d{4}-\d{2}-\d{2}$/.test(transDate)) return showFormError('Please enter a valid invoice date.');

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

            // Serialize payment rows (always present, skip for draft)
            if (action !== 'draft') {
                if (!serializePaymentRows()) return showFormError('Please enter a valid amount for every payment row.');
            }

            var postData = $.extend({
                transPrefixSelect      : parseInt($('#transPrefixSelect').val(), 10) || 0,
                transNumber            : $.trim($('#transNumber').val()),
                transDate              : transDate,
                dueDate                : $.trim($('#dueDate').val()),
                customerSearch         : customerUID,
                fromSalesOrderUID      : parseInt($('#fromSalesOrderUID').val(), 10) || 0,
                fromQuotationUID       : parseInt($('#fromQuotationUID').val(), 10) || 0,
                invoiceType            : $('#invoiceType').val() || '',
                dispatchFrom           : $('#dispatchFrom').val() || '',
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

            setFormLoading('#addInvForm', true, action);

            $.ajax({
                url    : '/invoices/addInvoice',
                method : 'POST',
                data   : postData,
                cache  : false,
                success: function(response) {
                    if (response.Error) {
                        setFormLoading('#addInvForm', false);
                        showFormError(response.Message);
                    } else {
                        Swal.fire({
                            icon             : 'success',
                            title            : 'Invoice Saved',
                            text             : response.Message || 'Invoice created successfully.',
                            confirmButtonText: 'OK',
                            timer            : 3000,
                            timerProgressBar : true,
                        }).then(function() {
                            window.location.href = '/invoices';
                        });
                    }
                },
                error: function() {
                    setFormLoading('#addInvForm', false);
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
