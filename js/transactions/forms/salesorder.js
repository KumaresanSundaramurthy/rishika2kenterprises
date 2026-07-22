// ── Sales Order form — init, submit, and sticky bar ──────────────────────────
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
var _editData           = _isEdit ? (_cfg.editData || {}) : {};
var _custState          = _isEdit ? (_editData.custState || '') : '';
var _editItems          = _isEdit ? (_editData.items     || []) : [];
var _fromQuotation      = !_isEdit ? (_cfg.fromQuotation      || null) : null;
var _fromQuotAttachments = !_isEdit ? (_cfg.fromQuotAttachments || []) : [];
var _fromQuotItems      = !_isEdit ? (_cfg.fromQuotItems      || [])   : [];

$(function () {
    'use strict';

    searchCustomers('customerSearch');
    transDatePickr('#transDate_disp',   '#transDate',    false, false, true,  true,  '');
    transDatePickr('#deliveryDate_disp','#deliveryDate', false, false, false, false, '#transDate');

    if (!_isEdit) {
        var _soCur = _cfg.currency || '₹';
        window._showOnAccountBanner = function (total) {
            if ((parseFloat(total) || 0) > 0) {
                $('#onAccountTotal').text(_soCur + ' ' + parseFloat(total).toFixed(2));
                $('#onAccountIndicator').removeClass('d-none');
            } else {
                $('#onAccountIndicator').addClass('d-none');
            }
        };
        $('#customerSearch').on('select2:clear change', function () {
            if (!parseInt($(this).val(), 10)) $('#onAccountIndicator').addClass('d-none');
        });
    }

    if (_isEdit) {
        initTransAttachments(_editData.transUID, '/transactions/getAttachments', _formModuleUID || 102);

        if (_editData.custUID > 0) {
            $('#customerSearch')
                .append(new Option(_editData.custName, _editData.custUID, true, true))
                .trigger('change');
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
            _editItems.forEach(function (item) {
                var added = billManager.addItem(item, item.quantity);
                if (added !== false) {
                    formationTableBillItems(billManager.getItemById(item.id));
                }
            });
            if (typeof updateItemTaxBreakdown === 'function') updateItemTaxBreakdown();
            billManager.updateSummary();
        }

    } else {
        if (_fromQuotation && _fromQuotation.uid > 0) {

            if (_fromQuotation.customer > 0) {
                var _soCustLabel = _fromQuotation.customerName;
                if (_fromQuotation.customerArea)   _soCustLabel += ', '   + _fromQuotation.customerArea;
                if (_fromQuotation.customerMobile) _soCustLabel += ' (' + _fromQuotation.customerMobile + ')';
                $('#customerSearch')
                    .append(new Option(_soCustLabel, _fromQuotation.customer, true, true))
                    .trigger('change')
                    .prop('disabled', true);
            }
            $('#addTransCustomer').hide();
            $('#openCustomerSearchModal').hide();

            if (typeof billManager !== 'undefined' && typeof formationTableBillItems === 'function'
                    && Array.isArray(_fromQuotItems) && _fromQuotItems.length > 0) {
                $('#billTableBody').empty();
                var _convIds = [];
                _fromQuotItems.forEach(function (item) {
                    var added = billManager.addItem(item, item.quantity);
                    if (added !== false) {
                        formationTableBillItems(billManager.getItemById(item.id));
                        _convIds.push(item.id);
                    }
                });
                _convIds.forEach(function (id) {
                    $('button.deleteBillItem[data-id="' + id + '"]').addClass('d-none');
                });
                if (typeof updateItemTaxBreakdown === 'function') updateItemTaxBreakdown();
                billManager.updateSummary();

                var _clearBtn = document.getElementById('btnClearCart');
                if (_clearBtn) _clearBtn.setAttribute('style', 'display:none!important');

                var _cur    = _cfg.currency || '₹';
                var _dec    = _cfg.decimals || 2;
                var _grand  = (billManager.summary && billManager.summary.totals)   ? (billManager.summary.totals.grandTotal   || 0) : 0;
                var _tax    = (billManager.summary && billManager.summary.taxTotals) ? (billManager.summary.taxTotals.totalTax  || 0) : 0;
                var _fmtAmt = function (n) { return _cur + ' ' + parseFloat(n).toFixed(_dec); };
                ['stickyGrandTotal', 'inlineGrandTotal'].forEach(function (id) {
                    var el = document.getElementById(id); if (el) el.textContent = _fmtAmt(_grand);
                });
                ['stickyTotalTax', 'inlineTotalTax'].forEach(function (id) {
                    var el = document.getElementById(id); if (el) el.textContent = parseFloat(_tax).toFixed(_dec);
                });
            }

            if (typeof renderTransAttachmentsFromData === 'function' && Array.isArray(_fromQuotAttachments)) {
                renderTransAttachmentsFromData(_fromQuotAttachments);
            }
        }
    }

    var _formId = _cfg.formId || 'soForm';
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
                if (!prefixUID || prefixUID <= 0) return showFormError('Please select a sales order prefix.');

                var transNumber = $.trim($('#transNumber').val());
                if (!transNumber || parseInt(transNumber, 10) <= 0) return showFormError('Transaction number must be greater than 0.');
            }

            var transDate = $.trim($('#transDate').val());
            if (!transDate || !/^\d{4}-\d{2}-\d{2}$/.test(transDate)) return showFormError('Please enter a valid order date.');

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

            var charges = { AdditionalCharges: JSON.stringify(typeof collectAdditionalCharges === 'function' ? collectAdditionalCharges() : []) };

            var postData = $.extend({
                transPrefixSelect      : parseInt($('#transPrefixSelect').val(), 10) || 0,
                transNumber            : $.trim($('#transNumber').val()),
                transDate              : transDate,
                deliveryDate           : $.trim($('#deliveryDate').val()),
                customerSearch         : customerUID,
                orderType              : $('#orderType').val() || '',
                dispatchFrom           : $('#dispatchFrom').val() || '',
                referenceDetails       : $.trim($('#referenceDetails').val()),
                transNotes             : $.trim($('#transNotes').val()),
                transTermsCond         : $.trim($('#transTermsCond').val()),
                placeOfSupplyCode      : $('#placeOfSupplyCode').val() || '',
                placeOfSupplyName      : $('#placeOfSupplyName').val() || '',
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
                SignatureUID           : parseInt($('#transSignatureUID').val(), 10) || 0,
                action                 : action,
                [csrfName]             : csrfVal,
            }, charges);

            if (_isEdit) {
                postData.TransUID = parseInt($('input[name="TransUID"]').val(), 10);
            } else {
                postData.fromQuotationUID = parseInt($('#fromQuotationUID').val(), 10) || 0;
            }

            var formData = new FormData();
            $.each(postData, function (k, v) { formData.append(k, v); });
            collectTransAttachData(formData);
            if (typeof _plTransInjectFormData === 'function') _plTransInjectFormData(formData);

            setFormLoading('#' + _formId, true, action);

            $.ajax({
                url         : '/' + (_cfg.formAction || ''),
                method      : 'POST',
                data        : formData,
                processData : false,
                contentType : false,
                cache       : false,
                success: function (response) {
                    if (response.Error) {
                        setFormLoading('#' + _formId, false);
                        showFormError(response.Message);
                    } else {
                        _setPendingToast('_soPendingToast', response.Message, 'success');
                        window.location.href = _buildReturnUrl('/salesorders');
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

// ── Sticky + inline summary bars ─────────────────────────────────────────────
(function () {
    var _formId   = (_cfg && _cfg.formId)   ? _cfg.formId   : 'soForm';
    var _formEl   = document.getElementById(_formId);
    var _barEl    = document.getElementById('stickyBottomBar');
    var _inlineEl = document.getElementById('inlineSummaryBar');
    if (!_barEl || !_inlineEl) return;

    var cur = (_cfg && _cfg.currency) ? _cfg.currency : '₹';
    var dec = (_cfg && _cfg.decimals) ? _cfg.decimals : 2;
    function _r2(n) { return parseFloat((+n || 0).toFixed(dec)); }
    function _fmt(n) { return cur + ' ' + _r2(n).toFixed(dec); }

    function _alignStickyBar() {
        if (!_formEl) return;
        var rect = _formEl.getBoundingClientRect();
        var vpW  = document.documentElement.clientWidth;
        _barEl.style.left  = rect.left + 'px';
        _barEl.style.right = (vpW - rect.right) + 'px';
        _barEl.style.width = 'auto';
    }

    function _sync() {
        if (typeof billManager === 'undefined') return;
        var grand = (billManager.summary && billManager.summary.totals)
            ? (billManager.summary.totals.grandTotal || 0) : 0;
        var tax   = (billManager.summary && billManager.summary.taxTotals)
            ? (billManager.summary.taxTotals.totalTax || 0) : 0;
        ['stickyGrandTotal', 'inlineGrandTotal'].forEach(function (id) {
            var el = document.getElementById(id); if (el) el.textContent = _fmt(grand);
        });
        ['stickyTotalTax', 'inlineTotalTax'].forEach(function (id) {
            var el = document.getElementById(id); if (el) el.textContent = _fmt(tax);
        });
    }

    var _obs = new IntersectionObserver(function (entries) {
        if (!entries[0].isIntersecting) { _alignStickyBar(); _barEl.style.display = 'flex'; }
        else { _barEl.style.display = 'none'; }
    }, { threshold: 0.1 });
    _obs.observe(_inlineEl);
    _barEl.style.display = 'none';
    window.addEventListener('resize', _alignStickyBar);

    function _delegate(val) {
        var sel = (val === 'save' || !val)
            ? 'button[name="action"][value="save"][type="submit"]'
            : 'button[name="action"][value="' + val + '"]';
        var btn = _formEl && _formEl.querySelector(sel);
        if (!btn && (val === 'save' || !val)) btn = _formEl && _formEl.querySelector('button[name="action"][value="save"]');
        if (btn) btn.click();
    }

    ['stickySaveBtn', 'inlineSaveBtn'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('click', function () { _delegate('save'); });
    });
    ['stickyDraftBtn', 'inlineDraftBtn'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('click', function () { _delegate('draft'); });
    });
    document.addEventListener('click', function (e) {
        var t = e.target.closest('[data-sticky-action],[data-inline-action]');
        if (!t) return;
        _delegate(t.dataset.stickyAction || t.dataset.inlineAction);
    });

    var _totEl = document.getElementById('bill_tot_amt');
    if (_totEl) new MutationObserver(_sync).observe(_totEl, { childList: true, subtree: true, characterData: true });
    _sync();
}());
