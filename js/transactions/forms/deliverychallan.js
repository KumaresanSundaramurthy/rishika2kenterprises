// ── Delivery Challan form — init, submit, and sticky bar ─────────────────────
// PHP data is injected by form.php as window._transFormData before this file loads.

var _cfg = window._transFormData || {};

// Globals — kept at file scope because other transaction scripts reference them
const EnableStorage    = !!_cfg.enableStorage;
var _formModuleUID     = _cfg.moduleUID  || 112;
var _isEdit            = !!_cfg.isEdit;
var _isDraftEdit       = !!_cfg.isDraftEdit;
var _orgState          = _cfg.orgState   || '';
var _upstashUrl        = _cfg.upstashUrl    || '';
var _upstashReadToken  = _cfg.upstashToken  || '';
var _custCacheKey      = _cfg.custCacheKey  || '';
var _returnTab         = _cfg.returnTab  || '';
var _returnPage        = _cfg.returnPage || 1;
let imgData;
var _isDirty      = false;
var _isPopulating = false;

// Derived locals
var _editData      = _isEdit ? (_cfg.editData || {}) : {};
var _custState     = _isEdit ? (_editData.custState || '') : '';
var _editItems     = _isEdit ? (_editData.items || []) : [];
var _fromSO        = !_isEdit ? (_cfg.fromSO      || null) : null;
var _fromSOItems   = !_isEdit ? (_cfg.fromSOItems || [])   : [];
var _fromClone     = !_isEdit ? (_cfg.fromClone      || null) : null;
var _fromCloneItems= !_isEdit ? (_cfg.fromCloneItems || [])   : [];

$(function () {
    'use strict';
    _isPopulating = true;

    // ── Customer search ────────────────────────────────────────────────────────
    if (!_fromSO) {
        searchCustomers('customerSearch');
        if (!_isEdit || _isDraftEdit) {
            var _dcOACur = _cfg.currency || '₹';
            window._showOnAccountBanner = function (total) {
                if ((parseFloat(total) || 0) > 0) {
                    $('#onAccountTotal').text(_dcOACur + ' ' + parseFloat(total).toFixed(typeof decimalPlaces !== 'undefined' ? decimalPlaces : 2));
                    $('#onAccountIndicator').removeClass('d-none');
                } else {
                    $('#onAccountIndicator').addClass('d-none');
                }
            };
            $('#customerSearch').on('select2:clear change', function () {
                if (!parseInt($(this).val(), 10)) $('#onAccountIndicator').addClass('d-none');
            });
            // Pre-fill customer address box and state in draft-edit mode
            if (_isDraftEdit && _editData.custUID && _editData.custAddr) {
                (function () {
                    var a = _editData.custAddr;
                    if (a.Line1 || a.City || a.State) {
                        var _dcLines = [a.Line1, a.Line2].filter(Boolean).join(', ');
                        var _dcLoc   = [a.City, a.State].filter(Boolean).join(', ');
                        if (a.Pincode) _dcLoc += ' – ' + a.Pincode;
                        $('#customerAddressBox').find('span').text([_dcLines, _dcLoc].filter(Boolean).join(' · '));
                        $('#customerAddressBox').removeClass('d-none');
                    }
                    if (typeof window._onCustStateSelected === 'function' && a.State) {
                        window._onCustStateSelected(a.State.trim());
                    }
                }());
            }
        }
        if (_isEdit && !_isDraftEdit && _editData.custUID > 0) {
            var _custLabel = _editData.custName || '';
            if (_editData.custArea)   _custLabel += ', ' + _editData.custArea;
            if (_editData.custMobile) _custLabel += ' (' + _editData.custMobile + ')';
            $('#customerSearch')
                .append(new Option(_custLabel, _editData.custUID, true, true))
                .trigger('change');
            $('#customerSearch')
                .on('select2:opening',  function (e) { e.preventDefault(); })
                .on('select2:clearing', function (e) { e.preventDefault(); });
            $('#customerSearch').data('select2').$container.addClass('select2-party-readonly');
        }
    }

    transDatePickr('#transDate_disp',      '#transDate',      false, false, true,  true,  '');
    transDatePickr('#returnDate_disp',     '#returnDate',     false, false, false, false, '#transDate');
    transDatePickr('#deliveryByDate_disp', '#deliveryByDate', false, false, false, false, '');

    // ── DC Expected Return Date auto-fill ─────────────────────────────────────
    // Reads DCDefaultReturnDays from user's settings.
    // Only auto-sets when the field is empty (new DC, not edit mode).
    // Tracks whether the date was auto-set so dispatch-date changes can recalculate it.

    var _dcReturnAutoSet  = false;
    var _dcReturnLocking  = false;

    var _dcGetReturnDays = function () {
        var days = (typeof JwtData !== 'undefined' && JwtData.TransSettings && JwtData.TransSettings.DCDefaultReturnDays !== undefined)
            ? parseInt(JwtData.TransSettings.DCDefaultReturnDays, 10)
            : 7;
        return isNaN(days) ? 7 : days;
    };

    var _dcCalcReturnDate = function () {
        var days = _dcGetReturnDays();
        if (days <= 0) return;

        var rawDispatch = $('#transDate').val();
        if (!rawDispatch) return;

        var dispatchDate = new Date(rawDispatch + 'T00:00:00');
        dispatchDate.setDate(dispatchDate.getDate() + days);

        var fp = document.getElementById('returnDate_disp') ? document.getElementById('returnDate_disp')._flatpickr : null;
        if (!fp) return;

        _dcReturnLocking = true;
        _dcReturnAutoSet = true;
        fp.setDate(dispatchDate, true);
        _dcReturnLocking = false;
    };

    (function () {
        var fpEl = document.getElementById('returnDate_disp');
        if (fpEl && fpEl._flatpickr && Array.isArray(fpEl._flatpickr.config.onChange)) {
            fpEl._flatpickr.config.onChange.push(function () {
                if (!_dcReturnLocking) { _dcReturnAutoSet = false; }
            });
        }
    }());

    (function () {
        var fpEl = document.getElementById('transDate_disp');
        if (fpEl && fpEl._flatpickr && Array.isArray(fpEl._flatpickr.config.onChange)) {
            fpEl._flatpickr.config.onChange.push(function (selectedDates) {
                var returnFp = document.getElementById('returnDate_disp') ? document.getElementById('returnDate_disp')._flatpickr : null;
                if (returnFp && selectedDates.length) {
                    returnFp.set('minDate', selectedDates[0]);
                    var currentReturn = returnFp.selectedDates[0];
                    if (currentReturn && currentReturn < selectedDates[0]) {
                        _dcReturnLocking = true;
                        returnFp.clear();
                        $('#returnDate').val('');
                        _dcReturnAutoSet = false;
                        _dcReturnLocking = false;
                    }
                }
                var type = $('#challanType').val();
                if ((type === 'Returnable' || type === 'Job Work') && _dcReturnAutoSet) {
                    _dcCalcReturnDate();
                }
            });
        }
    }());

    $('#challanType').on('change', function () {
        var type = $(this).val();
        if (type === 'Returnable' || type === 'Job Work') {
            $('#returnDateWrap').show();
            if (!$('#returnDate').val()) { _dcCalcReturnDate(); }
        } else {
            $('#returnDateWrap').hide();
            var fp = document.getElementById('returnDate_disp') ? document.getElementById('returnDate_disp')._flatpickr : null;
            if (fp) fp.clear();
            $('#returnDate').val('');
            _dcReturnAutoSet = false;
        }
    });

    // ── Edit mode pre-fill ────────────────────────────────────────────────────
    if (_isEdit) {
        renderTransAttachmentsFromData(_editData.attachments || []);

        $('#extraDiscount').val(_editData.extraDiscAmount || 0);
        var $extDT = $('#extDiscountType');
        if ($extDT.children('option').length) {
            $extDT.val(_editData.extraDiscType || '').trigger('change');
        } else {
            $extDT.data('r2kPendingVal', _editData.extraDiscType || '');
        }
        $('#globalDiscount').val(_editData.globalDiscPercent || 0).trigger('input');

        if (typeof billManager !== 'undefined' && _orgState && _custState) {
            billManager.setInterState(_custState.trim().toLowerCase() !== _orgState.trim().toLowerCase());
        }

        if (typeof billManager !== 'undefined' && Array.isArray(_editItems) && _editItems.length > 0) {
            billManager.batchAdd(_editItems, null);
            $('#btnClearCart').removeClass('d-none');
            if (_editItems.length >= 2) { $('#chkReverseOrder').closest('.form-check-inline').removeClass('d-none'); }
        }

    } else if (_fromSO && _fromSO.uid > 0) {

        // ── SO-linked DC ──────────────────────────────────────────────────────
        var _soNum = _fromSO.soNumber || 'SO';

        document.getElementById(_cfg.formId || 'dcForm').classList.add('so-linked-dc');

        if (typeof billManager !== 'undefined' && typeof formationTableBillItems === 'function'
                && Array.isArray(_fromSOItems) && _fromSOItems.length > 0) {
            $('#billTableBody').empty();
            _fromSOItems.forEach(function (item) {
                var added = billManager.addItem(item, item.quantity);
                if (added !== false) formationTableBillItems(billManager.getItemById(item.id));
            });
            if (typeof updateItemTaxBreakdown === 'function') updateItemTaxBreakdown();
            billManager.updateSummary();
        }

        var _soProductIds = {};
        _fromSOItems.forEach(function (item) { _soProductIds[item.id] = true; });

        if (typeof billManager !== 'undefined') {
            var _origAddItem = billManager.addItem.bind(billManager);
            billManager.addItem = function (item, qty) {
                if (item && item.id && !_soProductIds[item.id]) {
                    showToastNotification('Only items from ' + _soNum + ' can be dispatched on this challan.', 'warning');
                    return false;
                }
                return _origAddItem(item, qty);
            };
        }

        $('#addTransProduct').closest('.card-header').after(
            '<div class="alert dc-so-notice d-flex align-items-center gap-2 py-2 px-3 mx-3 mt-2">' +
            '<i class="bx bx-link-alt flex-shrink-0 dc-so-notice-icon"></i>' +
            '<span>Linked to <strong>' + _soNum + '</strong>. You may adjust quantities or remove items for a partial dispatch. Adding new products is not allowed.</span>' +
            '</div>'
        );

        var _soQtyMap = {};
        _fromSOItems.forEach(function (item) { _soQtyMap[item.id] = item.quantity; });

        $('#' + (_cfg.formId || 'dcForm')).on('change blur', '#billTableBody input[type="number"]', function () {
            var $row   = $(this).closest('tr[data-item-id]');
            var itemId = parseInt($row.data('item-id')) || 0;
            if (!itemId || !_soQtyMap.hasOwnProperty(itemId)) return;
            var maxQty  = _soQtyMap[itemId];
            var entered = parseFloat($(this).val()) || 0;
            if (entered > maxQty) {
                $(this).val(maxQty);
                showToastNotification('Quantity cannot exceed SO ordered qty (' + maxQty + ').', 'warning');
                $(this).trigger('input');
            }
        });

    } else if (_fromClone && _fromClone.uid > 0) {

        // ── Cloned DC ─────────────────────────────────────────────────────────
        if (_fromClone.invoiceType) {
            $('#dcInvoiceType').val(_fromClone.invoiceType).trigger('change');
        }
        if (_fromClone.challanType) {
            $('#challanType').val(_fromClone.challanType).trigger('change');
        }
        if (_fromClone.dispatchFrom > 0 && typeof window._setDispatchFrom === 'function') {
            window._setDispatchFrom(_fromClone.dispatchFrom);
        }
        if (_fromClone.notes)     $('#transNotes').val(_fromClone.notes);
        if (_fromClone.reference) $('#vehicleNumber').val(_fromClone.reference);

        if (typeof billManager !== 'undefined' && typeof formationTableBillItems === 'function'
                && Array.isArray(_fromCloneItems) && _fromCloneItems.length > 0) {
            $('#billTableBody').empty();
            _fromCloneItems.forEach(function (item) {
                var added = billManager.addItem(item, item.quantity);
                if (added !== false) formationTableBillItems(billManager.getItemById(item.id));
            });
            if (typeof updateItemTaxBreakdown === 'function') updateItemTaxBreakdown();
            billManager.updateSummary();
        }
    }

    // ── Form submit ───────────────────────────────────────────────────────────
    _isPopulating = false;

    var _formId = _cfg.formId || 'dcForm';
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
                if (!prefixUID || prefixUID <= 0) return showFormError('Please select a delivery challan prefix.');
                var transNumber = $.trim($('#transNumber').val());
                if (!transNumber || parseInt(transNumber, 10) <= 0) return showFormError('Transaction number must be greater than 0.');
            }

            var transDate = $.trim($('#transDate').val());
            if (!transDate || !/^\d{4}-\d{2}-\d{2}$/.test(transDate)) return showFormError('Please enter a valid dispatch date.');

            var items = typeof billManager !== 'undefined' ? billManager.getAllItems() : [];
            if (!items || items.length === 0) return showFormError('Please add at least one product.');

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
            var charges       = { AdditionalCharges: JSON.stringify(typeof collectAdditionalCharges === 'function' ? collectAdditionalCharges() : []) };

            var postData = $.extend({
                transPrefixSelect      : parseInt($('#transPrefixSelect').val(), 10) || 0,
                transNumber            : $.trim($('#transNumber').val()),
                transDate              : transDate,
                returnDate             : $.trim($('#returnDate').val()),
                customerSearch         : customerUID,
                invoiceType            : $('#dcInvoiceType').val() || 'Regular',
                challanType            : $('#challanType').val() || 'Non-Returnable',
                vehicleNumber          : $.trim($('#vehicleNumber').val()),
                deliveryBy             : $.trim($('#deliveryByDate').val()),
                dispatchFrom           : $('#dispatchFrom').val() || '',
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
                postData.fromSOUID = parseInt($('#fromSOUID').val(), 10) || 0;
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
                        $(document).one('ajaxStop', function () { showUIBlock(); });
                        _setPendingToast('_dcPendingToast', response.Message, 'success');
                        _isDirty = false;
                        window.location.href = _buildReturnUrl('/deliverychallan');
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

// ── Sticky + inline summary bars ──────────────────────────────────────────────
(function () {
    var _formId   = (_cfg && _cfg.formId)   ? _cfg.formId   : 'dcForm';
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

    var _totEl = document.querySelector('.bill_tot_amt');
    if (_totEl) new MutationObserver(_sync).observe(_totEl, { childList: true, subtree: true, characterData: true });
    _sync();
}());
