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
var _isDirty      = false;
var _isPopulating = false;

// Derived locals
var _editData           = _isEdit ? (_cfg.editData || {}) : {};
var _custState          = _isEdit ? (_editData.custState || '') : '';
var _editItems          = _isEdit ? (_editData.items     || []) : [];
var _fromQuotation      = !_isEdit ? (_cfg.fromQuotation      || null) : null;
var _fromQuotAttachments = !_isEdit ? (_cfg.fromQuotAttachments || []) : [];
var _fromQuotItems      = !_isEdit ? (_cfg.fromQuotItems      || [])   : [];

$(function () {
    'use strict';
    _isPopulating = true;

    searchCustomers('customerSearch');
    transDatePickr('#transDate_disp',   '#transDate',    false, false, true,  true,  '');
    transDatePickr('#deliveryDate_disp','#deliveryDate', false, false, false, false, '#transDate');

    // Credits badges handled by shared _showOnAccountBanner in transactions.js

    if (_isEdit) {
        renderTransAttachmentsFromData(_editData.attachments || []);

        if (_editData.custUID > 0) {
            var _custLabel = _editData.custName || '';
            if (_editData.custArea)   _custLabel += ', ' + _editData.custArea;
            if (_editData.custMobile) _custLabel += ' (' + _editData.custMobile + ')';
            $('#customerSearch')
                .append(new Option(_custLabel, _editData.custUID, true, true))
                .trigger('change');

            // Populate address strip from PHP-supplied billing address
            (function () {
                var _aLines = [_editData.custBillLine1, _editData.custBillLine2].filter(Boolean).join(', ');
                var _aLoc   = [_editData.custBillCity, _editData.custBillState].filter(Boolean).join(', ');
                if (_editData.custBillPincode) _aLoc += ' – ' + _editData.custBillPincode;
                var _aText  = [_aLines, _aLoc].filter(Boolean).join(' · ');
                if (_aText) { $('#customerAddressBox').find('span').text(_aText).end().removeClass('d-none'); }
            }());
            window._currentCustAddr = {
                customerUID : _editData.custUID         || 0,
                Line1       : _editData.custBillLine1   || '',
                Line2       : _editData.custBillLine2   || '',
                City        : _editData.custBillCity    || '',
                State       : _editData.custBillState   || '',
                Pincode     : _editData.custBillPincode || '',
            };
            if (!_isDraftEdit) {
                $('#customerSearch')
                    .on('select2:opening',  function (e) { e.preventDefault(); })
                    .on('select2:clearing', function (e) { e.preventDefault(); });
                $('#customerSearch').data('select2').$container.addClass('select2-party-readonly');
            }
        }

        $('#extraDiscount').val(_editData.extraDiscAmount || 0);
        var $extDT = $('#extDiscountType');
        if ($extDT.children('option').length) {
            if (typeof billManager !== 'undefined' && billManager && billManager.summary && billManager.summary.extra) {
                billManager.summary.extra.discountType = (_editData.extraDiscType || '').toLowerCase();
            }
            $extDT.val(_editData.extraDiscType || '').trigger('change');
        } else {
            $extDT.data('r2kPendingVal', _editData.extraDiscType || '');
        }
        $('#globalDiscount').val(_editData.globalDiscPercent || 0).trigger('input');

        if (typeof billManager !== 'undefined' && _orgState && _custState) {
            billManager.isInterState = (_custState.trim().toLowerCase() !== _orgState.trim().toLowerCase());
        }

        if (typeof billManager !== 'undefined' && Array.isArray(_editItems) && _editItems.length > 0) {
            billManager.batchAdd(_editItems, null);
            $('#btnClearCart').removeClass('d-none');
            if (_editItems.length >= 2) { $('#chkReverseOrder').closest('.form-check-inline').removeClass('d-none'); }
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

    _isPopulating = false;

    var _formId = _cfg.formId || 'soForm';
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
            if (typeof validateBrandItems === 'function' && !validateBrandItems()) return;

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
                isInterState           : $('#isInterStateHidden').val(),
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

            var _autoDraftUid = (!_isEdit && typeof AutoDraft !== 'undefined') ? AutoDraft.getDraftUid() : 0;
            if (_isEdit) {
                postData.TransUID = parseInt($('input[name="TransUID"]').val(), 10);
            } else {
                if (_autoDraftUid > 0) postData.TransUID = _autoDraftUid;
                postData.fromQuotationUID = parseInt($('#fromQuotationUID').val(), 10) || 0;
            }

            var formData = new FormData();
            $.each(postData, function (k, v) { formData.append(k, v); });
            collectTransAttachData(formData);
            if (typeof _plTransInjectFormData === 'function') _plTransInjectFormData(formData);

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
                        _setPendingToast('_soPendingToast', _isEdit ? response.Message : 'Sales Order created successfully.', 'success');
                        window.location.href = _buildReturnUrl('/salesorders');
                    } else {
                        window._r2kRedirecting = true;
                        showUIBlock();
                        _setPendingToast('_soPendingToast', _isEdit ? response.Message : 'Sales Order created successfully.', 'success');
                        _isDirty = false;
                        window.location.href = _buildReturnUrl('/salesorders', action === 'draft' ? 'Draft' : '');
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

    // ── Billing address edit — open #addEditAddressModal ────────────────────
    var _custAddrModalActive = false;

    $('#AddrSaveBtn').on('click.soBillAddr', function (e) {
        if (!_custAddrModalActive) return;
        e.stopImmediatePropagation();
        var addr = window._currentCustAddr || {};
        if (!addr.customerUID) return;
        var line1     = $.trim($('#ModalAddrLine1').val());
        var line2     = $.trim($('#ModalAddrLine2').val());
        var pincode   = $.trim($('#ModalAddrPincode').val());
        var stateText = $.trim($('#ModalAddrState option:selected').text());
        var cityText  = $.trim($('#ModalAddrCity  option:selected').text());
        if (stateText === '-- Select State --') stateText = '';
        if (cityText  === '-- Select City --')  cityText  = '';
        var stateId   = $('#ModalAddrState option:selected').val() || '';
        var cityId    = $('#ModalAddrCity  option:selected').val() || '';
        if (!line1) { showToastNotification('Address Line 1 is required.', 'error'); return; }
        ajaxLoading(1);
        $.ajax({
            url    : '/customers/updateBillingAddress',
            method : 'POST',
            data   : { CustomerUID: addr.customerUID, Line1: line1, Line2: line2, StateId: stateId, StateText: stateText, CityId: cityId, CityText: cityText, Pincode: pincode },
            success: function (resp) {
                ajaxLoading(0);
                if (resp.Error) { showToastNotification(resp.Message || 'Failed to update address.', 'error'); return; }
                var _lines = [line1, line2].filter(Boolean).join(', ');
                var _loc   = [cityText, stateText].filter(Boolean).join(', ');
                if (pincode) _loc += ' – ' + pincode;
                var _text  = [_lines, _loc].filter(Boolean).join(' · ');
                $('#customerAddressBox').find('span').text(_text).end().removeClass('d-none');
                window._currentCustAddr = { customerUID: addr.customerUID, Line1: line1, Line2: line2, City: cityText, State: stateText, Pincode: pincode };
                var $custOpt = $('#customerSearch').find('option[value="' + addr.customerUID + '"]');
                var _s2d = $custOpt.data('data');
                if (_s2d && _s2d.address) { _s2d.address.Line1 = line1; _s2d.address.Line2 = line2; _s2d.address.City = cityText; _s2d.address.State = stateText; _s2d.address.Pincode = pincode; $custOpt.data('data', _s2d); }
                if (typeof _showCustTypeIndicator === 'function') {
                    _showCustTypeIndicator({ countryISO2: 'IN', address: { State: stateText } });
                }
                _custAddrModalActive = false;
                $('#addEditAddressModal').modal('hide');
                showToastNotification(resp.Message || 'Address updated.', 'success');
            },
            error: function () { ajaxLoading(0); showToastNotification('Server error. Please try again.', 'error'); }
        });
    });

    $(document).on('hide.bs.modal', '#addEditAddressModal', function () { _custAddrModalActive = false; });

    $(document).on('click', '#btnEditCustAddr', function () {
        var addr = window._currentCustAddr || {};
        if (!addr.customerUID) { showToastNotification('Please select a customer first.', 'error'); return; }
        $('#addrModalTitle').text('Edit Billing Address');
        $('#AddrUID').val(0);
        $('#AddrType').val('Billing');
        $('#ModalAddrLine1').val(addr.Line1 || '');
        $('#ModalAddrLine2').val(addr.Line2 || '');
        $('#ModalAddrPincode').val(addr.Pincode || '');
        csc_loadStates('ModalAddrState', 'IN', '', function () {
            var stateText = $.trim(addr.State || '').toLowerCase();
            var stateISO2 = '';
            if (stateText) {
                $('#ModalAddrState option').each(function () {
                    if ($.trim($(this).text()).toLowerCase() === stateText) {
                        stateISO2 = $(this).data('iso2') || '';
                        $('#ModalAddrState').val($(this).val());
                        if ($('#ModalAddrState').hasClass('select2')) {
                            $('#ModalAddrState').data('skipCityLoad', true);
                            $('#ModalAddrState').trigger('change.select2');
                        }
                        return false;
                    }
                });
            }
            if (stateISO2) {
                csc_loadCities('ModalAddrCity', 'IN', stateISO2, '', addr.City || '', null);
            } else {
                $('#ModalAddrCity').empty().append('<option value="">-- Select City --</option>');
                if ($('#ModalAddrCity').hasClass('select2')) { $('#ModalAddrCity').select2({ width: '100%', dropdownParent: $('#addEditAddressModal .modal-content') }); }
            }
        });
        _custAddrModalActive = true;
        $('#addEditAddressModal').modal('show');
    });

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

    var _totEl = document.querySelector('.bill_tot_amt');
    if (_totEl) new MutationObserver(_sync).observe(_totEl, { childList: true, subtree: true, characterData: true });
    _sync();
}());

// ── Auto-Draft ────────────────────────────────────────────────────────────────
if (!_isEdit) AutoDraft.initFromCfg(_cfg, '#customerSearch', 'soForm');
