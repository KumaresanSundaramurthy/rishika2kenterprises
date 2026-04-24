<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper transactionPage layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

            <?php $FormAttribute = ['id' => 'addPRForm', 'name' => 'addPRForm', 'autocomplete' => 'off', 'data-csrf' => $this->security->get_csrf_token_name(), 'data-csrf-value' => $this->security->get_csrf_hash()];
                    echo form_open('purchasereturns/addPurchaseReturn', $FormAttribute); ?>

                    <div class="card mb-3">

                        <div class="card-header bg-body-tertiary trans-header-static trans-theme modal-header-center-sticky d-flex justify-content-between align-items-center pb-3">
                            <div class="d-flex flex-wrap align-items-center gap-3" id="transHeaderInfo">
                                <h5 class="modal-title mb-0 ms-2">Create Purchase Return</h5>
                                <?php $this->load->view('transactions/partials/form_prefix_add'); ?>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="submit" name="action" value="save" class="btn btn-primary">Save</button>
                                <button type="submit" name="action" value="draft" class="btn btn-outline-secondary">Save as Draft</button>
                                <a href="/purchasereturns" class="btn btn-label-danger">Close</a>
                            </div>
                        </div>

                        <div class="card-body card-body-form-static p-4">

                            <div class="card-header modal-header-center-sticky p-1 mb-3">
                                <h5 class="modal-title mb-0"><i class="bx bx-building me-1"></i> Vendor Details</h5>
                            </div>
                            <div class="row">
                                <div class="col-md-6 border-end pe-3">
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <label for="vendorSearch" class="form-label small fw-semibold">Select Vendor <span class="text-danger">*</span></label>
                                        </div>
                                        <div class="flex-grow-1">
                                            <select id="vendorSearch" name="vendorSearch" class="form-select form-select-sm"></select>
                                        </div>
                                    </div>
                                    <div id="vendorAddressBox" class="mt-2 p-2 border border-secondary trans-border-dotted rounded small d-none"></div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-2">
                                        <label for="transDate" class="form-label small fw-semibold">Return Date <span class="text-danger">*</span></label>
                                        <div class="input-group input-group-merge">
                                            <span class="input-group-text"><i class="icon-base bx bx-calendar"></i></span>
                                            <input type="text" class="form-control form-control-sm" id="transDate" name="transDate" readonly="readonly" value="<?php echo format_datedisplay(time(), 'Y-m-d'); ?>" required />
                                        </div>
                                    </div>
                                    <div>
                                        <label for="referenceDetails" class="form-label small fw-semibold">Reference</label>
                                        <input type="text" id="referenceDetails" name="referenceDetails" class="form-control form-control-sm" placeholder="PO No, Bill No..." maxlength="100" />
                                    </div>
                                </div>
                            </div>
                            <hr/>

                            <?php $this->load->view('transactions/partials/form_products_add', [
                                'transProductSectionTitle' => 'Returned Products',
                                'transNotesPlaceholder'    => 'Reason for return',
                            ]); ?>
                        </div>
                    </div>

                    <?php echo form_close(); ?>

                </div>
            </div>

            <?php $this->load->view('common/transactions/transprefix'); ?>
            <?php $this->load->view('transactions/modals/taxdetails'); ?>
            <?php $this->load->view('products/modals/items'); ?>
            <?php $this->load->view('common/footer_desc'); ?>

        </div>

    </div>
</div>

<?php $this->load->view('common/transactions/footer'); ?>

<script src="/js/transactions/purchasereturns.js"></script>
<script src="/js/transactions/transactions.js"></script>
<script src="/js/transactions/transprefix.js"></script>
<script src="/js/transactions/modaladdress.js"></script>
<script src="/js/transactions/products.js"></script>
<script src="/js/combinemodules/products.js"></script>

<script>
const StateInfo     = <?php echo json_encode($StateData); ?>;
const CityInfo      = <?php echo json_encode($CityData); ?>;
const EnableStorage = <?php echo $JwtData->GenSettings->EnableStorage; ?>;

$(function() {
    'use strict'

    searchVendors('vendorSearch');
    transDatePickr('#transDate', false, 'Y-m-d', false, true, true, true, 'd-m-Y');

    var $form = $('#addPRForm');
    if ($form.length) {
        $form.on('submit', function(e) {
            e.preventDefault();
            var $btn = $('button[type="submit"][name="action"]:focus, button[type="submit"][name="action"].active-submit', $form);
            var action = $btn.val() || 'save';
            var csrfName = $form.data('csrf');
            var csrfVal  = $form.data('csrf-value');

            var vendorUID = parseInt($('#vendorSearch').val(), 10);
            if (!vendorUID || vendorUID <= 0) return showFormError('Please select a vendor.');

            if (action !== 'draft') {
                var prefixUID = parseInt($('#transPrefixSelect').val(), 10);
                if (!prefixUID || prefixUID <= 0) return showFormError('Please select a prefix.');
                var transNumber = $.trim($('#transNumber').val());
                if (!transNumber || parseInt(transNumber, 10) <= 0) return showFormError('Transaction number must be greater than 0.');
            }

            var transDate = $.trim($('#transDate').val());
            if (!transDate || !/^\d{4}-\d{2}-\d{2}$/.test(transDate)) return showFormError('Please enter a valid date.');

            var items = typeof billManager !== 'undefined' ? billManager.getAllItems() : [];
            if (!items || items.length === 0) return showFormError('Please add at least one product.');

            var bm = typeof billManager !== 'undefined' ? billManager : null;
            var summary = bm ? bm.summary : {};
            var charges = {};
            if (summary.additionalCharges) {
                ['shipping', 'handling', 'packing', 'other'].forEach(function(t) {
                    var c = summary.additionalCharges[t];
                    if (c && c.grossAmount > 0) { charges[t + 'Amount'] = c.grossAmount; charges[t + 'Tax'] = c.taxPercent || 0; }
                });
            }

            var postData = $.extend({
                transPrefixSelect      : parseInt($('#transPrefixSelect').val(), 10) || 0,
                transNumber            : $.trim($('#transNumber').val()),
                transDate              : transDate,
                vendorSearch           : vendorUID,
                referenceDetails       : $.trim($('#referenceDetails').val()),
                transNotes             : $.trim($('#transNotes').val()),
                transTermsCond         : $.trim($('#transTermsCond').val()),
                extraDiscount          : parseFloat($('#extraDiscount').val()) || 0,
                extDiscountType        : $('#extDiscountType').val() || '',
                SubTotal               : summary.items     ? (summary.items.taxableAmount     || 0) : 0,
                DiscountAmount         : summary.items     ? (summary.items.discountTotal      || 0) : 0,
                TaxAmount              : summary.taxTotals ? (summary.taxTotals.totalTax       || 0) : 0,
                CgstAmount             : summary.taxTotals ? (summary.taxTotals.cgstTotal      || 0) : 0,
                SgstAmount             : summary.taxTotals ? (summary.taxTotals.sgstTotal      || 0) : 0,
                IgstAmount             : summary.taxTotals ? (summary.taxTotals.igstTotal      || 0) : 0,
                AdditionalChargesTotal : (summary.additionalCharges && summary.additionalCharges.total) ? (summary.additionalCharges.total.grossAmount || 0) : 0,
                GlobalDiscPercent      : bm ? (bm.globalDiscountPercent || 0) : 0,
                RoundOff               : summary.extra ? (summary.extra.roundOff || 0) : 0,
                NetAmount              : summary.totals ? (summary.totals.grandTotal || 0) : 0,
                Items                  : JSON.stringify(items),
                action                 : action,
                [csrfName]             : csrfVal,
            }, charges);

            setFormLoading('#addPRForm', true, action);
            $.ajax({
                url: '/purchasereturns/addPurchaseReturn', method: 'POST', data: postData, cache: false,
                success: function(response) {
                    if (response.Error) { setFormLoading('#addPRForm', false); showFormError(response.Message); }
                    else { Swal.fire({ icon: 'success', title: 'Purchase Return Saved', text: response.Message || 'Purchase return created successfully.', confirmButtonText: 'OK', timer: 3000, timerProgressBar: true }).then(function() { window.location.href = '/purchasereturns'; }); }
                },
                error: function() { setFormLoading('#addPRForm', false); showFormError('Server error. Please try again.'); }
            });
        });
        $form.on('click', 'button[type="submit"][name="action"]', function() {
            $form.find('button[type="submit"][name="action"]').removeClass('active-submit');
            $(this).addClass('active-submit');
        });
    }
});
</script>
