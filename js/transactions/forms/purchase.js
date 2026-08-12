// ── Purchase form — init, submit, and sticky bar ─────────────────────────────
// PHP data is injected by form.php as window._transFormData before this file loads.

var _cfg = window._transFormData || {};

// Globals — kept at file scope because other transaction scripts reference them
const EnableStorage    = !!_cfg.enableStorage;
var _formModuleUID     = _cfg.moduleUID  || 105;
var _isEdit            = !!_cfg.isEdit;
var _isDraftEdit       = !!_cfg.isDraftEdit;
var _orgState          = _cfg.orgState   || '';
var _upstashUrl        = _cfg.upstashUrl    || '';
var _upstashReadToken  = _cfg.upstashToken  || '';
var _vendorCacheKey    = _cfg.vendorCacheKey || '';
var _returnTab         = _cfg.returnTab  || '';
var _returnPage        = _cfg.returnPage || 1;
let imgData;
var _isDirty      = false;
var _isPopulating = false;

// Purchase mode flag — must be at file scope; read by billManager / product search
window._productPurchaseMode = true;

// Derived locals
var _editData      = _isEdit ? (_cfg.editData || {}) : {};
var _transUID      = _isEdit ? (_editData.transUID || 0) : 0;
var _vendorState   = _isEdit ? (_editData.vendorState || '') : '';
var _editItems     = _isEdit ? (_editData.items || [])       : [];
var _fromPO        = !_isEdit ? (_cfg.fromPO      || null) : null;
var _fromPOItems   = !_isEdit ? (_cfg.fromPOItems || null) : null;

$(function () {
    'use strict';
    _isPopulating = true;

    if (_isEdit) {
        initTooltips();
        renderTransAttachmentsFromData(_editData.attachments || []);
    }
    searchVendors('vendorSearch');

    transDatePickr('#transDate_disp',   '#transDate',   false, false, true,  true,  '');
    transDatePickr('#billDueDate_disp', '#billDueDate', false, false, false, false, '#transDate');

    // Keep billDueDate minDate in sync whenever supplier invoice date changes
    (function () {
        var transEl = document.querySelector('#transDate');
        var dueEl   = document.querySelector('#billDueDate');
        if (!transEl || !dueEl || !transEl._flatpickr || !dueEl._flatpickr) return;
        var transFp = transEl._flatpickr;
        var dueFp   = dueEl._flatpickr;
        transFp.config.onChange.push(function (selectedDates) {
            if (!selectedDates.length) return;
            dueFp.set('minDate', selectedDates[0]);
            var current = dueFp.selectedDates[0];
            if (current && current < selectedDates[0]) { dueFp.clear(); }
        });
    }());

    if (_isEdit) {
        if (_editData.vendorUID > 0) {
            var _vendorLabel = _editData.vendorName || '';
            if (_editData.vendorArea) _vendorLabel += ' (' + _editData.vendorArea + ')';
            $('#vendorSearch')
                .append(new Option(_vendorLabel, _editData.vendorUID, true, true))
                .trigger('change');
            if (!_isDraftEdit) {
                $('#vendorSearch')
                    .on('select2:opening',  function (e) { e.preventDefault(); })
                    .on('select2:clearing', function (e) { e.preventDefault(); });
                $('#vendorSearch').data('select2').$container.addClass('select2-party-readonly');
            }
        }
        if (typeof billManager !== 'undefined' && _orgState && _vendorState) {
            billManager.isInterState = (_vendorState.trim().toLowerCase() !== _orgState.trim().toLowerCase());
        }

        if (typeof billManager !== 'undefined' && Array.isArray(_editItems) && _editItems.length > 0) {
            billManager.batchAdd(_editItems, null);
            $('#btnClearCart').removeClass('d-none');
            if (_editItems.length >= 2) { $('#chkReverseOrder').closest('.form-check-inline').removeClass('d-none'); }
        }

        if (_editData.globalDiscPercent > 0) {
            $('#globalDiscount').val(_editData.globalDiscPercent).trigger('input');
        }
        if (_editData.extraDiscAmount > 0) {
            $('#extraDiscount').val(_editData.extraDiscAmount);
        }
        var $extDT = $('#extDiscountType');
        if ($extDT.children('option').length) {
            if (_editData.extraDiscType) { $extDT.val(_editData.extraDiscType); }
        } else {
            $extDT.data('r2kPendingVal', _editData.extraDiscType || '');
        }

    } else {
        // Pre-fill vendor and items from Purchase Order
        if (_fromPO && _fromPO.vendorUID) {
            $('#vendorSearch').append(new Option(
                _fromPO.vendorName || '',
                _fromPO.vendorUID, true, true
            )).trigger('change');
        }

        if (Array.isArray(_fromPOItems) && _fromPOItems.length > 0) {
            $(document).one('billmanager:ready', function () {
                _isPopulating = true;
                $('#billTableBody').empty();
                _fromPOItems.forEach(function (item) {
                    var added = billManager.addItem(item, item.quantity);
                    if (added !== false) formationTableBillItems(billManager.getItemById(item.id));
                });
                if (typeof updateItemTaxBreakdown === 'function') updateItemTaxBreakdown();
                billManager.updateSummary();
                _isPopulating = false;
            });
        }
    }

    _isPopulating = false;

    var _formId = _cfg.formId || 'purchForm';
    var $form   = $('#' + _formId);
    if ($form.length) {

        $form.on('submit', function (e) {
            e.preventDefault();

            if (typeof AutoDraft !== 'undefined') AutoDraft.cancel();
            if (typeof AutoDraft !== 'undefined' && AutoDraft.isBusy()) return;

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

            if (!_isEdit && action !== 'draft') {
                if (typeof serializePaymentRows === 'function' && !serializePaymentRows()) {
                    return showFormError('Please enter a valid amount for every payment row.');
                }
            }

            var bm            = typeof billManager !== 'undefined' ? billManager : null;
            var summary       = bm ? bm.summary : {};
            var charges       = { AdditionalCharges: JSON.stringify(typeof collectAdditionalCharges === 'function' ? collectAdditionalCharges() : []) };

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
                placeOfSupplyCode      : $('#placeOfSupplyCode').val() || '',
                placeOfSupplyName      : $('#placeOfSupplyName').val() || '',
                isInterState           : $('#isInterStateHidden').val(),
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
                SignatureUID           : parseInt($('#transSignatureUID').val(), 10) || 0,
                action                 : action,
                PaymentRows            : !_isEdit ? ($('#PaymentRowsJson').val() || '') : '',
                IsFullyPaid            : (!_isEdit && $('#isFullyPaid').is(':checked')) ? 1 : 0,
                RecordPayment          : (!_isEdit && action !== 'draft') ? 1 : 0,
                [csrfName]             : csrfVal,
            }, charges);

            var _autoDraftUid = (!_isEdit && typeof AutoDraft !== 'undefined') ? AutoDraft.getDraftUid() : 0;
            if (_isEdit) postData.TransUID = _transUID;
            else if (_autoDraftUid > 0) postData.TransUID = _autoDraftUid;

            var formData = new FormData();
            $.each(postData, function (k, v) { formData.append(k, v); });
            collectTransAttachData(formData);

            ajaxLoading(1);
            setFormLoading('#' + _formId, true, action);

            $.ajax({
                url         : '/' + (_autoDraftUid > 0 ? (_cfg.updateAction || _cfg.formAction || '') : (_cfg.formAction || '')),
                method      : 'POST',
                data        : formData,
                processData : false,
                contentType : false,
                cache       : false,
                success: function (response) {
                    if (response.Error) {
                        ajaxLoading(0);
                        setFormLoading('#' + _formId, false);
                        showFormError(response.Message);
                    } else if (_pendingPrintFormat) {
                        var _fmt = _pendingPrintFormat;
                        _pendingPrintFormat = null;
                        _isDirty = false;
                        if (response.Token) {
                            window.open('/flow/doc/' + response.Token + '?format=' + _fmt, '_blank');
                        }
                        window._r2kRedirecting = true;
                        showUIBlock();
                        _setPendingToast('_purPendingToast', _isEdit ? response.Message : 'Purchase Bill created successfully.', 'success');
                        window.location.href = _buildReturnUrl('/purchases');
                    } else {
                        window._r2kRedirecting = true;
                        showUIBlock();
                        _setPendingToast('_purPendingToast', _isEdit ? response.Message : 'Purchase Bill created successfully.', 'success');
                        _isDirty = false;
                        window.location.href = _buildReturnUrl('/purchases', action === 'draft' ? 'Draft' : '');
                    }
                },
                error: function () {
                    ajaxLoading(0);
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

    // ── Unsaved-changes guard ─────────────────────────────────────────────────
    $(document).on('input change', function (e) {
        if (_isPopulating) return;
        var t = e.target;
        if (t && t.type !== 'hidden' && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.tagName === 'SELECT')) {
            _isDirty = true;
        }
    });
    $(window).on('beforeunload', function (e) {
        if (_isDirty) { e.preventDefault(); e.returnValue = ''; }
    });
    $(document).on('click', 'a.btn-outline-danger, a[href="javascript:history.back()"]', function (e) {
        if (!_isDirty) return;
        e.preventDefault();
        var $a = $(this);
        Swal.fire({
            title             : t('swal_unsaved_title',   'Unsaved Changes'),
            text              : t('swal_unsaved_msg',     'Your changes will be lost if you close now.'),
            icon              : 'warning',
            showCancelButton  : true,
            confirmButtonText : t('swal_unsaved_confirm', 'Close Anyway'),
            cancelButtonText  : t('swal_unsaved_cancel',  'Stay'),
            confirmButtonColor: '#d33',
            cancelButtonColor : '#3085d6',
        }).then(function (result) {
            if (result.isConfirmed) {
                _isDirty = false;
                var href = $a.attr('href');
                if (!href || href === 'javascript:history.back()') {
                    history.back();
                } else {
                    window.location.href = href;
                }
            }
        });
    });

});

// ── Sticky + inline summary bars ─────────────────────────────────────────────
(function () {
    var _formId   = (_cfg && _cfg.formId)   ? _cfg.formId   : 'purchForm';
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

    function _setGroup(id, show) {
        var el = document.getElementById(id);
        if (!el) return;
        if (show) { el.classList.remove('d-none'); el.classList.add('d-flex'); }
        else      { el.classList.remove('d-flex'); el.classList.add('d-none'); }
    }

    function _sync() {
        if (typeof billManager === 'undefined') return;
        var grand = (billManager.summary && billManager.summary.totals)
            ? (billManager.summary.totals.grandTotal || 0) : 0;
        var tax   = (billManager.summary && billManager.summary.taxTotals)
            ? (billManager.summary.taxTotals.totalTax || 0) : 0;

        var paid = 0;
        if (_formEl) {
            _formEl.querySelectorAll('#paymentRowsBody tr .pay-amount-inp').forEach(function (inp) {
                paid += parseFloat(inp.value) || 0;
            });
        }
        var balance = grand > 0 ? Math.max(0, _r2(grand - paid)) : 0;
        var excess  = grand > 0 ? Math.max(0, _r2(paid - grand)) : 0;
        var hasPay  = paid > 0;

        ['stickyGrandTotal', 'inlineGrandTotal'].forEach(function (id) {
            var el = document.getElementById(id); if (el) el.textContent = _fmt(grand);
        });
        ['stickyTotalTax', 'inlineTotalTax'].forEach(function (id) {
            var el = document.getElementById(id); if (el) el.textContent = _fmt(tax);
        });
        ['sticky', 'inline'].forEach(function (pfx) {
            _setGroup(pfx + 'PaidGroup',    hasPay);
            _setGroup(pfx + 'BalanceGroup', hasPay && balance > 0);
            _setGroup(pfx + 'ExcessGroup',  hasPay && excess > 0);
            var pEl = document.getElementById(pfx + 'TotalPaid');
            if (pEl) pEl.textContent = _fmt(paid);
            var bEl = document.getElementById(pfx + 'BalanceAmt');
            if (bEl) bEl.textContent = _fmt(balance);
            var eEl = document.getElementById(pfx + 'ExcessAmt');
            if (eEl) eEl.textContent = _fmt(excess);
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

    var _totEl  = document.querySelector('.bill_tot_amt');
    if (_totEl) new MutationObserver(_sync).observe(_totEl, { childList: true, subtree: true, characterData: true });
    var _payRows = document.getElementById('paymentRowsBody');
    if (_payRows) new MutationObserver(_sync).observe(_payRows, { childList: true, subtree: true, characterData: true });
    _sync();
}());

// ── Auto-Draft ────────────────────────────────────────────────────────────────
if (!_isEdit) AutoDraft.initFromCfg(_cfg, '#vendorSearch', 'purchForm');
