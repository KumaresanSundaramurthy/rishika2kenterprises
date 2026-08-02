// ── Quotation form — init, submit, and sticky bar ────────────────────────────
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

// Derived locals from config
var _editData          = _isEdit ? (_cfg.editData  || {}) : {};
var _custState         = _isEdit ? (_editData.custState     || '') : '';
var _editCustUID       = _isEdit ? (_editData.custUID       || 0)  : 0;
var _editPriceListUID  = _isEdit ? (_editData.priceListUID  || 0)  : 0;
var _editPriceListData = _isEdit ? (_editData.priceListData || null) : null;
var _editItems         = _isEdit ? (_editData.items         || []) : [];
var _cloneData         = !_isEdit ? (_cfg.cloneData  || null) : null;
var _cloneItems        = !_isEdit ? (_cfg.cloneItems || [])   : [];

$(function () {
    'use strict';

    searchCustomers('customerSearch');

    var _qCur = _cfg.currency || '₹';
    window._showOnAccountBanner = function (total) {
        if ((parseFloat(total) || 0) > 0) {
            $('#onAccountTotal').text(_qCur + ' ' + parseFloat(total).toFixed(typeof decimalPlaces !== 'undefined' ? decimalPlaces : 2));
            $('#onAccountIndicator').removeClass('d-none');
        } else {
            $('#onAccountIndicator').addClass('d-none');
        }
    };
    $('#customerSearch').on('select2:clear change', function () {
        if (!parseInt($(this).val(), 10)) $('#onAccountIndicator').addClass('d-none');
    });

    transDatePickr('#transDate_disp',    '#transDate',    false, false, true,  true,  '');
    transDatePickr('#validityDate_disp', '#validityDate', false, false, false, false, '#transDate');
    setupTransactionValidity('#transDate_disp', '#validityDays', '#validityDate_disp');

    if (_isEdit) {
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

        var _savedCharges = _editData.savedCharges || [];
        if (_savedCharges.length) {
            (function () {
                var chargeMap = {};
                _savedCharges.forEach(function (ch) { if (ch.type) chargeMap[ch.type] = ch; });
                ['shipping', 'packing'].forEach(function (type) {
                    var ch = chargeMap[type];
                    if (!ch) return;
                    if (ch.tax != null) $('#' + type + 'Charges').val(ch.tax).trigger('change');
                    if (ch.percent != null) $('#' + type + 'Percent').val(ch.percent);
                    if (ch.withoutTax != null && parseFloat(ch.withoutTax) > 0) {
                        $('#' + type + 'ChargeWOutTax').val(ch.withoutTax).trigger('input');
                        $('#additionalChargesBox').removeClass('d-none');
                    }
                });
            }());
        }

        if (typeof billManager !== 'undefined' && _orgState && _custState) {
            billManager.isInterState = (_custState.trim().toLowerCase() !== _orgState.trim().toLowerCase());
        }

        if (typeof billManager !== 'undefined' && Array.isArray(_editItems) && _editItems.length > 0) {
            billManager.batchAdd(_editItems, null);
            $('#btnClearCart').removeClass('d-none');
            if (_editItems.length >= 2) { $('#chkReverseOrder').closest('.form-check-inline').removeClass('d-none'); }
        }

        if (_editCustUID > 0 && typeof UpstashService !== 'undefined' && UpstashService.isEnabled()) {
            UpstashService.hgetall(UpstashService.orgKey('customers')).then(function (map) {
                if (!map || typeof map !== 'object') return;
                var c = map[String(_editCustUID)];
                if (!c) {
                    c = Object.values(map).find(function (v) {
                        return parseInt(v.CustomerUID, 10) === _editCustUID;
                    });
                }
                if (!c) return;
                var custData = {
                    id:               parseInt(c.CustomerUID || _editCustUID, 10),
                    name:             c.Name             || '',
                    onAccountBalance: parseFloat(c.OnAccountBalance || 0),
                    onAccountRecords: c.OnAccountRecords || [],
                    customerTypeUID:  parseInt(c.CustomerTypeUID || 0, 10),
                    groupUID:         c.GroupUID ? parseInt(c.GroupUID, 10) : null,
                    countryISO2:      c.CountryISO2 || 'IN',
                };
                if (c.Address && c.Address.length) {
                    var addr = c.Address[0];
                    c.Address.forEach(function (a) { if (a.AddressType === 'Billing') addr = a; });
                    custData.address = {
                        Line1:   addr.Line1     || '',
                        Line2:   addr.Line2     || '',
                        Pincode: addr.Pincode   || '',
                        City:    addr.CityText  || '',
                        State:   addr.StateText || '',
                    };
                }
                if (typeof _showOnAccountBanner   === 'function') _showOnAccountBanner(custData.onAccountBalance);
                if (typeof _showCustTypeIndicator === 'function') _showCustTypeIndicator(custData);
                if (typeof _plTransEditRestore    === 'function') _plTransEditRestore(custData);
            }).catch(function () {});
        }

    } else {
        if (_cloneItems && _cloneItems.length > 0) {
            var _cloneAttempts = 0;
            var _cloneInterval = setInterval(function () {
                _cloneAttempts++;
                if (typeof billManager !== 'undefined' && typeof billManager.addItem === 'function'
                        && typeof formationTableBillItems === 'function') {
                    clearInterval(_cloneInterval);
                    _cloneItems.forEach(function (item) {
                        var added = billManager.addItem(item, item.quantity);
                        if (added) formationTableBillItems(billManager.getItemById(item.id));
                    });
                }
                if (_cloneAttempts > 50) clearInterval(_cloneInterval);
            }, 100);
        }
    }

    var _formId = _cfg.formId || 'quotationForm';
    var $form   = $('#' + _formId);
    if ($form.length) {

        $form.on('submit', function (e) {
            e.preventDefault();

            var $btn     = $('button[type="submit"][name="action"]:focus, button[type="submit"][name="action"].active-submit', $form);
            var action   = $btn.val() || 'save';
            action       = _resolveFormAction(action);
            var csrfName = $form.data('csrf');
            var csrfVal  = $form.data('csrf-value');

            var customerUID = parseInt($('#customerSearch').val(), 10);
            if (!customerUID || customerUID <= 0) return showFormError('Please select a customer.');

            if (!_isEdit) {
                var prefixUID = parseInt($('#transPrefixSelect').val(), 10);
                if (!prefixUID || prefixUID <= 0) return showFormError('Please select a quotation prefix.');

                var transNumber = $.trim($('#transNumber').val());
                if (!transNumber || parseInt(transNumber, 10) <= 0) return showFormError('Transaction number must be greater than 0.');
                if (parseInt(transNumber, 10) > 2147483647) return showFormError('Transaction number exceeds the maximum allowed value of 2,147,483,647. Please use a smaller number or create a new prefix series.');
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

            var bm            = typeof billManager !== 'undefined' ? billManager : null;
            var summary       = bm ? bm.summary : {};
            var netAmount     = summary.totals    ? (summary.totals.grandTotal          || 0) : 0;
            var subTotal      = summary.items     ? (summary.items.taxableAmount         || 0) : 0;
            var discountAmt   = summary.items     ? (summary.items.discountTotal         || 0) : 0;
            var taxAmt        = summary.taxTotals ? (summary.taxTotals.totalTax          || 0) : 0;
            var cgstAmt       = summary.taxTotals ? (summary.taxTotals.cgstTotal         || 0) : 0;
            var sgstAmt       = summary.taxTotals ? (summary.taxTotals.sgstTotal         || 0) : 0;
            var igstAmt       = summary.taxTotals ? (summary.taxTotals.igstTotal         || 0) : 0;
            var addCharges    = (summary.additionalCharges && summary.additionalCharges.total) ? (summary.additionalCharges.total.grossAmount || 0) : 0;
            var globalDiscPct = bm ? (bm.globalDiscountPercent || 0) : 0;
            var roundOff      = summary.extra ? (summary.extra.roundOff || 0) : 0;

            var charges = { AdditionalCharges: JSON.stringify(typeof collectAdditionalCharges === 'function' ? collectAdditionalCharges() : []) };

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
                placeOfSupplyCode      : $('#placeOfSupplyCode').val() || '',
                placeOfSupplyName      : $('#placeOfSupplyName').val() || '',
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
                SignatureUID           : parseInt($('#transSignatureUID').val(), 10) || 0,
                action                 : action,
                [csrfName]             : csrfVal,
            }, charges);

            if (_isEdit) {
                postData.TransUID = parseInt($('input[name="TransUID"]').val(), 10);
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
                    } else if (_pendingPrintFormat) {
                        var fmt = _pendingPrintFormat;
                        _pendingPrintFormat = null;
                        clearTransactionForm(_formModuleUID);
                        _setPendingToast('_quotPendingToast', response.Message, 'success');
                        _openTransactionPrint(response.TransUID, _formModuleUID, fmt, function () {
                            window.location.href = _buildReturnUrl(_moduleListUrls[_formModuleUID] || '/quotations');
                        });
                    } else {
                        $(document).one('ajaxStop', function () { showUIBlock(); });
                        _setPendingToast('_quotPendingToast', response.Message, 'success');
                        window.location.href = _buildReturnUrl('/quotations');
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
    var cur     = (_cfg && _cfg.currency) ? _cfg.currency : '₹';
    var dec     = (_cfg && _cfg.decimals) ? _cfg.decimals : 2;
    var _formId = (_cfg && _cfg.formId)   ? _cfg.formId   : 'quotationForm';

    function _alignStickyBar() {
        var bar  = document.getElementById('stickyBottomBar');
        var form = document.getElementById(_formId);
        if (bar && form) {
            var rect = form.getBoundingClientRect();
            var cw   = document.documentElement.clientWidth;
            bar.style.left  = Math.round(rect.left)       + 'px';
            bar.style.right = Math.round(cw - rect.right) + 'px';
        }
    }
    document.addEventListener('DOMContentLoaded', _alignStickyBar);
    window.addEventListener('resize', _alignStickyBar);

    function _syncTotals() {
        if (typeof billManager === 'undefined') return;
        var grand = billManager.summary && billManager.summary.totals    ? (billManager.summary.totals.grandTotal    || 0) : 0;
        var tax   = billManager.summary && billManager.summary.taxTotals ? (billManager.summary.taxTotals.totalTax   || 0) : 0;
        ['stickyGrandTotal', 'inlineGrandTotal'].forEach(function (id) { var el = document.getElementById(id); if (el) el.textContent = cur + ' ' + grand.toFixed(dec); });
        ['stickyTotalTax',   'inlineTotalTax'  ].forEach(function (id) { var el = document.getElementById(id); if (el) el.textContent = cur + ' ' + tax.toFixed(dec); });
    }

    document.addEventListener('DOMContentLoaded', function () {
        _alignStickyBar();

        var inlineBar = document.getElementById('inlineSummaryBar');
        if (inlineBar) {
            new IntersectionObserver(function (entries) {
                var s = document.getElementById('stickyBottomBar');
                if (s) s.style.display = entries[0].isIntersecting ? 'none' : 'flex';
            }, { threshold: 0.1 }).observe(inlineBar);
        }

        var target = document.querySelector('.bill_tot_amt');
        if (target) new MutationObserver(_syncTotals).observe(target, { childList: true, characterData: true, subtree: true });
        _syncTotals();

        var sd = document.getElementById('stickyDraftBtn'),  ss = document.getElementById('stickySaveBtn');
        var id = document.getElementById('inlineDraftBtn'),   is = document.getElementById('inlineSaveBtn');
        if (sd) sd.addEventListener('click', function () { var o = document.querySelector('[name="action"][value="draft"]'); if (o) o.click(); });
        if (ss) ss.addEventListener('click', function () { var o = document.querySelector('[name="action"][value="save"]');  if (o) o.click(); });
        if (id) id.addEventListener('click', function () { var o = document.querySelector('[name="action"][value="draft"]'); if (o) o.click(); });
        if (is) is.addEventListener('click', function () { var o = document.querySelector('[name="action"][value="save"]');  if (o) o.click(); });
        document.querySelectorAll('[data-sticky-action],[data-inline-action]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var attr = this.getAttribute('data-sticky-action') || this.getAttribute('data-inline-action');
                var o = document.querySelector('[name="action"][value="' + attr + '"]');
                if (o) o.click();
            });
        });

        var card = document.querySelector('.card.mb-3');
        if (card) card.style.marginBottom = '70px';
    });
}());
