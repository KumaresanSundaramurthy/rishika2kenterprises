// ── Invoice form — init, submit, and sticky bar ───────────────────────────────
// PHP data is injected by form.php as window._transFormData before this file loads.

var _cfg = window._transFormData || {};

// Globals — kept at file scope because other transaction scripts reference them
const EnableStorage    = !!_cfg.enableStorage;
var _formModuleUID     = _cfg.moduleUID  || 0;
var _isEdit            = !!_cfg.isEdit;
var _isDraftEdit       = !!_cfg.isDraftEdit;
var _orgState          = _cfg.orgState   || '';
var _upstashUrl        = _cfg.upstashUrl || '';
var _upstashReadToken  = _cfg.upstashToken  || '';
var _custCacheKey      = _cfg.custCacheKey  || '';
var _returnTab         = _cfg.returnTab     || '';
var _returnPage        = _cfg.returnPage    || 1;
let imgData;

// Derived locals
var _editData         = _isEdit ? (_cfg.editData || {}) : {};
var _custState        = _isEdit ? (_editData.custState || '') : '';
var _editItems        = _isEdit ? (_editData.items     || []) : [];
var _fromSO           = !_isEdit ? (_cfg.fromSO           || null) : null;
var _fromSOItems      = !_isEdit ? (_cfg.fromSOItems       || [])  : [];
var _fromQuotation    = !_isEdit ? (_cfg.fromQuotation     || null) : null;
var _fromQuotItems    = !_isEdit ? (_cfg.fromQuotItems     || [])   : [];
var _fromChallan      = !_isEdit ? (_cfg.fromChallan       || null) : null;
var _fromChallanItems = !_isEdit ? (_cfg.fromChallanItems  || [])   : [];

$(function () {
    'use strict';

    searchCustomers('customerSearch');

    if (!_isEdit) {
        var _cur = _cfg.currency || '₹';
        var _oaCustomerUID = 0;

        window._showOnAccountBanner = function (total, records, customerUID) {
            _oaCustomerUID = parseInt(customerUID, 10) || 0;
            if (total > 0) {
                $('#onAccountTotal').text(_cur + ' ' + parseFloat(total).toFixed(2));
                $('#onAccountIndicator').removeClass('d-none');
            } else {
                $('#onAccountIndicator').addClass('d-none');
            }
            if (typeof window._loadOnAccountPanel === 'function') {
                window._loadOnAccountPanel(records || [], _oaCustomerUID, total);
            }
        };

        $('#customerSearch').on('select2:clear change', function () {
            if (!parseInt($(this).val(), 10)) {
                $('#onAccountIndicator').addClass('d-none');
                if (typeof window._clearOnAccountPanel === 'function') window._clearOnAccountPanel();
            }
        });
    }

    if (!_isEdit || _isDraftEdit) {
        transDatePickr('#transDate_disp', '#transDate', false, false, true, true, '');
    }
    transDatePickr('#dueDate_disp', '#dueDate', false, false, false, false, (_isEdit && !_isDraftEdit) ? '' : '#transDate');

    if (!_isEdit) {
        var _dueDatePicker   = document.querySelector('#dueDate')    ? document.querySelector('#dueDate')._flatpickr    : null;
        var _transDatePicker = document.querySelector('#transDate')  ? document.querySelector('#transDate')._flatpickr  : null;
        if (_dueDatePicker && _transDatePicker) {
            document.querySelector('#transDate').addEventListener('change', function () {
                if (_transDatePicker.selectedDates[0]) {
                    _dueDatePicker.setDate(_transDatePicker.selectedDates[0], true);
                }
            });
        }
    }

    if (_isEdit) {
        initTooltips();
        renderTransAttachmentsFromData(_editData.attachments || []);
        if (_editData.custUID > 0) {
            var _custLabel = _editData.custName || '';
            if (_editData.custArea)   _custLabel += ', ' + _editData.custArea;
            if (_editData.custMobile) _custLabel += ' (' + _editData.custMobile + ')';
            $('#customerSearch')
                .append(new Option(_custLabel, _editData.custUID, true, true))
                .trigger('change');
            if (!_isDraftEdit) {
                $('#customerSearch')
                    .on('select2:opening',  function (e) { e.preventDefault(); })
                    .on('select2:clearing', function (e) { e.preventDefault(); });
                $('#customerSearch').data('select2').$container.addClass('select2-party-readonly');
            }
        }
        $('#extraDiscount').val(_editData.extraDiscAmount || 0);
        $('#extDiscountType').val(_editData.extraDiscType || '').trigger('change');
        $('#globalDiscount').val(_editData.globalDiscPercent || 0).trigger('input');

        if (typeof billManager !== 'undefined' && _orgState && _custState) {
            billManager.isInterState = (_custState.trim().toLowerCase() !== _orgState.trim().toLowerCase());
        }

        if (typeof billManager !== 'undefined' && typeof formationTableBillItems === 'function'
                && Array.isArray(_editItems) && _editItems.length > 0) {
            $('#billTableBody').empty();
            billManager.batchAdd(_editItems, formationTableBillItems);
        }

    } else {
        var _sourceData  = _fromSO || _fromQuotation || _fromChallan;
        var _sourceItems = _fromSO ? _fromSOItems : (_fromQuotation ? _fromQuotItems : _fromChallanItems);

        if (_sourceData && _sourceData.uid > 0) {
            if (_sourceData.customer > 0) {
                var _invCustLabel = _sourceData.customerName;
                if (_sourceData.customerArea)   _invCustLabel += ', '   + _sourceData.customerArea;
                if (_sourceData.customerMobile) _invCustLabel += ' (' + _sourceData.customerMobile + ')';
                $('#customerSearch').append(new Option(_invCustLabel, _sourceData.customer, true, true)).trigger('change');
            }
            if (typeof billManager !== 'undefined' && typeof formationTableBillItems === 'function'
                    && Array.isArray(_sourceItems) && _sourceItems.length > 0) {
                $('#billTableBody').empty();
                billManager.batchAdd(_sourceItems, formationTableBillItems);
            }
        }
    }

    var _formId = _cfg.formId || 'invForm';
    var $form   = $('#' + _formId);
    if ($form.length) {

        $form.on('submit', function (e) {
            e.preventDefault();

            var $btn     = $('button[type="submit"][name="action"]:focus, button[type="submit"][name="action"].active-submit', $form);
            var action   = $btn.val() || 'save';
            var csrfName = $form.data('csrf');
            var csrfVal  = $form.data('csrf-value');

            var customerUID = parseInt($('#customerSearch').val(), 10);
            if (!customerUID || customerUID <= 0) return showFormError('Please select a customer.');

            if (!_isEdit && action !== 'draft') {
                var prefixUID = parseInt($('#transPrefixSelect').val(), 10);
                if (!prefixUID || prefixUID <= 0) return showFormError('Please select an invoice prefix.');

                var transNumber = $.trim($('#transNumber').val());
                if (!transNumber || parseInt(transNumber, 10) <= 0) return showFormError('Transaction number must be greater than 0.');
            }

            var transDate = $.trim($('[name="transDate"]').val());
            if (!transDate || !/^\d{4}-\d{2}-\d{2}$/.test(transDate)) return showFormError('Please enter a valid invoice date.');

            var items = typeof billManager !== 'undefined' ? billManager.getAllItems() : [];
            if (!items || items.length === 0) return showFormError('Please add at least one product.');

            if (!_isEdit) {
                for (var i = 0; i < items.length; i++) {
                    var item = items[i];
                    var qty  = parseFloat(item.quantity);
                    if (!qty || qty <= 0) return showFormError('Row ' + (i + 1) + ': Quantity must be greater than 0.');
                    if (parseFloat(item.unitPrice) < 0) return showFormError('Row ' + (i + 1) + ': Price cannot be negative.');
                }
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
                ['shipping', 'handling', 'packing', 'other'].forEach(function (t) {
                    var c = summary.additionalCharges[t];
                    if (c && c.grossAmount > 0) {
                        charges[t + 'Amount'] = c.grossAmount;
                        charges[t + 'Tax']    = c.taxPercent || 0;
                    }
                });
            }

            if (!_isEdit && action !== 'draft') {
                if (typeof getPaymentAttachmentFiles === 'function') {
                    var _payFiles = getPaymentAttachmentFiles();
                    if (_payFiles && _payFiles.length > 0) {
                        var _totalPayAmt = 0;
                        $('#paymentRowsBody tr').each(function () {
                            _totalPayAmt += parseFloat($(this).find('.pay-amount-inp').val()) || 0;
                        });
                        if (_totalPayAmt <= 0) return showFormError('Payment attachments are added but no payment amount is entered. Please enter a payment amount or remove the attachments.');
                    }
                }
                if (!serializePaymentRows()) return showFormError('Please enter a valid amount for every payment row.');
            }

            var fd = new FormData();
            fd.append(csrfName, csrfVal);
            if (_isEdit) fd.append('TransUID', parseInt($('input[name="TransUID"]').val(), 10));
            fd.append('transPrefixSelect',      parseInt($('#transPrefixSelect').val(), 10) || 0);
            fd.append('transNumber',            $.trim($('#transNumber').val()));
            fd.append('transDate',              transDate);
            fd.append('dueDate',                $.trim($('#dueDate').val()));
            fd.append('customerSearch',         customerUID);
            if (!_isEdit) {
                fd.append('fromSalesOrderUID',  parseInt($('#fromSalesOrderUID').val(), 10) || 0);
                fd.append('fromQuotationUID',   parseInt($('#fromQuotationUID').val(), 10)  || 0);
                fd.append('placeOfSupplyCode',  $('#placeOfSupplyCode').val() || '');
                fd.append('placeOfSupplyName',  $('#placeOfSupplyName').val() || '');
            }
            fd.append('invoiceType',            $('[name="invoiceType"]').val() || '');
            fd.append('dispatchFrom',           $('[name="dispatchFrom"]').val() || '');
            fd.append('referenceDetails',       $.trim($('#referenceDetails').val()));
            fd.append('transNotes',             $.trim($('#transNotes').val()));
            fd.append('transTermsCond',         $.trim($('#transTermsCond').val()));
            fd.append('extraDiscount',          extraDisc);
            fd.append('extDiscountType',        $('#extDiscountType').val() || '');
            fd.append('SubTotal',               subTotal);
            fd.append('DiscountAmount',         discountAmt);
            fd.append('TaxAmount',              taxAmt);
            fd.append('CgstAmount',             cgstAmt);
            fd.append('SgstAmount',             sgstAmt);
            fd.append('IgstAmount',             igstAmt);
            fd.append('AdditionalChargesTotal', addCharges);
            fd.append('AdditionalCharges', JSON.stringify(typeof collectAdditionalCharges === 'function' ? collectAdditionalCharges() : []));
            fd.append('GlobalDiscPercent',      globalDiscPct);
            fd.append('RoundOff',               roundOff);
            fd.append('NetAmount',              netAmount);
            fd.append('Items',                  JSON.stringify(items));
            fd.append('SignatureUID',           parseInt($('#transSignatureUID').val(), 10) || 0);
            fd.append('action',                 action);
            if (typeof _plTransInjectFormData === 'function') _plTransInjectFormData(fd);
            $.each(charges, function (k, v) { fd.append(k, v); });
            collectTransAttachData(fd);
            if (!_isEdit) {
                fd.append('PaymentRows',        $('#PaymentRowsJson').val()    || '');
                fd.append('IsFullyPaid',        $('#isFullyPaid').is(':checked') ? 1 : 0);
                fd.append('RecordPayment',      action !== 'draft' ? 1 : 0);
                fd.append('OnAccountApplyJson', $('#OnAccountApplyJson').val() || '');
                if (typeof getPaymentAttachmentFiles === 'function') {
                    var paymentFiles = getPaymentAttachmentFiles();
                    if (paymentFiles && paymentFiles.length > 0) {
                        paymentFiles.forEach(function (file) { fd.append('PaymentFiles[]', file); });
                    }
                }
            }

            setFormLoading('#' + _formId, true, action);

            $.ajax({
                url         : '/' + (_cfg.formAction || ''),
                method      : 'POST',
                data        : fd,
                processData : false,
                contentType : false,
                cache       : false,
                success: function (response) {
                    if (response.Error) {
                        setFormLoading('#' + _formId, false);
                        showFormError(response.Message);
                    } else {
                        _showSavedAndGo(_isEdit ? 'Invoice Updated' : 'Invoice Saved', response.Message || (_isEdit ? 'Invoice updated successfully.' : 'Invoice created successfully.'));
                    }
                },
                error: function () {
                    setFormLoading('#' + _formId, false);
                    showFormError('Server error. Please try again.');
                }
            });
        });

        $form.on('click', 'button[type="submit"][name="action"]', function () {
            $form.find('button[type="submit"][name="action"]').removeClass('active-submit');
            $(this).addClass('active-submit');
        });
    }

});

// ── Sticky bottom bar — total sync + button delegation ─────────────────────
(function () {
    var cur = (_cfg && _cfg.currency) ? _cfg.currency : '₹';
    var dec = (_cfg && _cfg.decimals) ? _cfg.decimals : 2;
    var _formId = (_cfg && _cfg.formId) ? _cfg.formId : 'invForm';

    function _alignStickyBar() {
        var bar  = document.getElementById('stickyBottomBar');
        var form = document.getElementById(_formId);
        if (bar && form) {
            var rect        = form.getBoundingClientRect();
            var clientWidth = document.documentElement.clientWidth;
            bar.style.left  = Math.round(rect.left)               + 'px';
            bar.style.right = Math.round(clientWidth - rect.right) + 'px';
        }
    }
    document.addEventListener('DOMContentLoaded', _alignStickyBar);
    window.addEventListener('resize', _alignStickyBar);

    document.addEventListener('DOMContentLoaded', function () {
        _alignStickyBar();

        var inlineBar = document.getElementById('inlineSummaryBar');
        if (inlineBar) {
            new IntersectionObserver(function (entries) {
                var sticky = document.getElementById('stickyBottomBar');
                if (sticky) sticky.style.display = entries[0].isIntersecting ? 'none' : 'flex';
            }, { threshold: 0.1 }).observe(inlineBar);
        }
    });

    function _r2(v) { return parseFloat((+v || 0).toFixed(dec)); }

    function _toggleGroup(ids, show, amtIds, amtValue) {
        ids.forEach(function (id) {
            var el = document.getElementById(id);
            if (!el) return;
            el.classList.toggle('d-none', !show);
            el.classList.toggle('d-flex', show);
        });
        if (amtIds) amtIds.forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.textContent = cur + ' ' + (amtValue || 0).toFixed(dec);
        });
    }

    function _syncStickyTotals() {
        if (typeof billManager === 'undefined') return;

        var grand = billManager.summary && billManager.summary.totals
            ? (billManager.summary.totals.grandTotal || 0) : 0;
        var tax = billManager.summary && billManager.summary.taxTotals
            ? (billManager.summary.taxTotals.totalTax || 0) : 0;

        var rowsPaid = 0;
        document.querySelectorAll('#paymentRowsBody tr .pay-amount-inp').forEach(function (inp) {
            rowsPaid += parseFloat(inp.value) || 0;
        });

        var oaPaid = 0;
        try {
            var oaEl = document.getElementById('OnAccountApplyJson');
            if (oaEl && oaEl.value) {
                (JSON.parse(oaEl.value) || []).forEach(function (x) { oaPaid += parseFloat(x.ApplyAmount) || 0; });
            }
        } catch (e) {}

        var paid    = _r2(rowsPaid + oaPaid);
        var balance = grand > 0 ? Math.max(0, _r2(grand - paid)) : 0;
        var excess  = grand > 0 ? Math.max(0, _r2(paid - grand)) : 0;

        ['stickyGrandTotal', 'inlineGrandTotal'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.textContent = cur + ' ' + grand.toFixed(dec);
        });
        ['stickyTotalTax', 'inlineTotalTax'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.textContent = cur + ' ' + tax.toFixed(dec);
        });

        _toggleGroup(['stickyPaidGroup', 'inlinePaidGroup'],
            paid > 0, ['stickyTotalPaid', 'inlineTotalPaid'], paid);
        _toggleGroup(['stickyBalanceGroup', 'inlineBalanceGroup'],
            grand > 0 && balance > 0 && excess === 0,
            ['stickyBalanceAmt', 'inlineBalanceAmt'], balance);
        _toggleGroup(['stickyExcessGroup', 'inlineExcessGroup'],
            excess > 0, ['stickyExcessAmt', 'inlineExcessAmt'], excess);
    }

    document.addEventListener('DOMContentLoaded', function () {
        var billTotEl = document.querySelector('.bill_tot_amt');
        if (billTotEl) {
            new MutationObserver(function () {
                if (typeof updatePaymentSummary === 'function') updatePaymentSummary();
                _syncStickyTotals();
            }).observe(billTotEl, { childList: true, characterData: true, subtree: true });
        }

        document.addEventListener('input', function (e) {
            if (e.target && e.target.classList.contains('pay-amount-inp')) {
                _syncStickyTotals();
            }
        });

        var oaJsonEl = document.getElementById('OnAccountApplyJson');
        if (oaJsonEl) {
            new MutationObserver(_syncStickyTotals).observe(oaJsonEl, { attributes: true, attributeFilter: ['value'] });
        }

        _syncStickyTotals();
        _syncStickyTotals();

        var d = document.getElementById('stickyDraftBtn');
        var s = document.getElementById('stickySaveBtn');
        if (d) d.addEventListener('click', function () { var o = document.querySelector('[name="action"][value="draft"]'); if (o) o.click(); });
        if (s) s.addEventListener('click', function () { var o = document.querySelector('[name="action"][value="save"]');  if (o) o.click(); });
        document.querySelectorAll('[data-sticky-action]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var o = document.querySelector('[name="action"][value="' + this.getAttribute('data-sticky-action') + '"]');
                if (o) o.click();
            });
        });

        var id = document.getElementById('inlineDraftBtn');
        var is = document.getElementById('inlineSaveBtn');
        if (id) id.addEventListener('click', function () { var o = document.querySelector('[name="action"][value="draft"]'); if (o) o.click(); });
        if (is) is.addEventListener('click', function () { var o = document.querySelector('[name="action"][value="save"]');  if (o) o.click(); });
        document.querySelectorAll('[data-inline-action]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var o = document.querySelector('[name="action"][value="' + this.getAttribute('data-inline-action') + '"]');
                if (o) o.click();
            });
        });
    });
}());

function _showSavedAndGo(title, msg) {
    _setPendingToast('_invPendingToast', msg, 'success');
    window.location.href = _buildReturnUrl('/invoices');
}
