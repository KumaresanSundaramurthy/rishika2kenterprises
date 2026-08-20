// ── Purchase Return form — init, submit, and sticky bar ──────────────────────
// PHP data is injected by form.php as window._transFormData before this file loads.

var _cfg = window._transFormData || {};

// Globals — kept at file scope because other transaction scripts reference them
const EnableStorage    = !!_cfg.enableStorage;
var _formModuleUID     = _cfg.moduleUID  || 108;
var _isEdit            = !!_cfg.isEdit;
var _isDraftEdit       = !!_cfg.isDraftEdit;
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

// Item method: Manual | Automatic | Both
var _prItemMethod = _cfg.prItemMethod || 'Manual';

// Derived locals
var _editData  = _isEdit ? (_cfg.editData || {}) : {};
var _transUID  = _isEdit ? (_editData.transUID || 0) : 0;
var _editItems = _isEdit ? (_editData.items   || []) : [];

$(function () {
    'use strict';
    _isPopulating = true;

    if (_isEdit) {
        renderTransAttachmentsFromData(_editData.attachments || []);
    }

    window._custSearchHideCreate = true;
    searchVendors('vendorSearch');
    if (_isEdit && _editData.vendorUID > 0) {
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

    transDatePickr('#transDate_disp', '#transDate', false, false, true, true, '');

    // ── Vendor purchase items modal (non-Manual modes only) ───────────────────
    if (_prItemMethod !== 'Manual') {

        $('#vendorSearch').on('change', function () {
            var vendUID = parseInt($(this).val(), 10);
            resetPurchaseDropdown();
            if (vendUID > 0) loadVendorPurchases(vendUID);
        });

        /**
         * @param {number} vendUID
         * @returns {void}
         */
        function loadVendorPurchases(vendUID) {
            var $pur = $('#fromPurchaseUID');
            $pur.prop('disabled', true).html('<option value="">Loading...</option>');
            ajaxLoading(0);
            $.ajax({
                url    : '/purchasereturns/getVendorPurchases',
                method : 'POST',
                data   : { VendorUID: vendUID, [CsrfName]: CsrfToken },
                success: function (res) {
                    ajaxLoading(1);
                    if (res.Error || !res.Purchases || res.Purchases.length === 0) {
                        $pur.html('<option value="">No purchase bills found</option>');
                        return;
                    }
                    var opts = '<option value="">-- Select Purchase Bill --</option>';
                    res.Purchases.forEach(function (p) {
                        opts += '<option value="' + p.TransUID + '">' + p.UniqueNumber + ' — ' + p.TransDate + '</option>';
                    });
                    $pur.html(opts).prop('disabled', false);
                },
                error: function () {
                    ajaxLoading(1);
                    $pur.html('<option value="">Failed to load</option>');
                }
            });
        }

        /**
         * @returns {void}
         */
        function resetPurchaseDropdown() {
            $('#fromPurchaseUID').html('<option value="">-- Select Vendor First --</option>').prop('disabled', true);
        }

        var _lastPurchaseUID = 0;
        var _purchaseItems   = [];

        $('#fromPurchaseUID').on('change', function () {
            var uid = parseInt($(this).val(), 10);
            if (!uid || uid <= 0) return;
            _lastPurchaseUID = uid;
            openPurchaseItemsModal(uid, $(this).find('option:selected').text());
        });

        $('#fromPurchaseUID').on('mousedown', function () {
            $(this).data('pre-click-val', $(this).val());
        }).on('click', function () {
            var preVal = parseInt($(this).data('pre-click-val'), 10);
            var curVal = parseInt($(this).val(), 10);
            if (preVal && preVal === curVal && curVal > 0) {
                openPurchaseItemsModal(curVal, $(this).find('option:selected').text());
            }
        });

        /**
         * @param {number} transUID
         * @param {string} purchLabel
         * @returns {void}
         */
        function openPurchaseItemsModal(transUID, purchLabel) {
            _purchaseItems = [];
            $('#purchItemsLoading').removeClass('d-none');
            $('#purchItemsTableWrap').addClass('d-none');
            $('#purchItemsTableBody').empty();
            $('#purchItemsSelectAll').prop('checked', true).prop('disabled', false);
            $('#purchItemsSelectedCount').text('0');
            $('#purchItemsAddToCart').prop('disabled', true);
            $('#purchItemsModalSubtitle').text(purchLabel);
            $('#purchaseItemsModal').modal('show');

            ajaxLoading(0);
            $.ajax({
                url    : '/purchasereturns/getPurchaseItems',
                method : 'POST',
                data   : { TransUID: transUID, [CsrfName]: CsrfToken },
                success: function (res) {
                    ajaxLoading(1);
                    $('#purchItemsLoading').addClass('d-none');
                    if (res.Error || !res.Items || res.Items.length === 0) {
                        $('#purchItemsTableBody').html('<tr><td colspan="7" class="text-center text-muted py-4">No items found in this purchase bill.</td></tr>');
                        $('#purchItemsTableWrap').removeClass('d-none');
                        return;
                    }
                    _purchaseItems = res.Items;
                    var cur = (typeof genSettings !== 'undefined' && genSettings.CurrenySymbol) ? genSettings.CurrenySymbol : '₹';
                    var rows = '';
                    var availCount = 0;
                    res.Items.forEach(function (item, idx) {
                        var taxPct   = parseFloat(item.TaxPercentage) || 0;
                        var taxAmt   = parseFloat(item.TaxAmount)     || 0;
                        var disc     = parseFloat(item.Discount)      || 0;
                        var discAmt  = parseFloat(item.DiscountAmount) || 0;
                        var rowTotal = parseFloat(item.NetAmount)     || 0;
                        var taxCell  = taxPct > 0
                            ? taxPct + '%<br><span class="text-muted" style="font-size:.75rem;">' + cur + ' ' + smartDecimal(taxAmt, 2, true) + '</span>'
                            : '<span class="text-muted">—</span>';
                        var discCell = disc > 0
                            ? disc + '%<br><span class="text-muted" style="font-size:.75rem;">' + cur + ' ' + smartDecimal(discAmt, 2, true) + '</span>'
                            : '<span class="text-muted">—</span>';
                        var inCart   = (typeof billManager !== 'undefined' && billManager.getItemById(item.ProductUID) !== null);
                        if (!inCart) availCount++;
                        var rowClass = inCart ? 'table-secondary' : '';
                        var chkAttr  = inCart ? 'disabled title="Already added to cart"' : 'checked';
                        rows += '<tr class="' + rowClass + '" data-idx="' + idx + '">' +
                            '<td><input type="checkbox" class="form-check-input purch-item-chk" data-idx="' + idx + '" data-transproduid="' + (parseInt(item.TransProdUID, 10) || 0) + '" ' + chkAttr + '></td>' +
                            '<td>' +
                                '<div class="fw-semibold' + (inCart ? ' text-muted' : '') + '" style="' + (inCart ? '' : 'color:#696cff;') + '">' + item.ProductName + '</div>' +
                                (item.PartNumber ? '<div class="small text-muted">Part#: ' + item.PartNumber + '</div>' : '') +
                                (inCart ? '<div class="small text-success"><i class="bx bx-check-circle me-1"></i>Added to cart</div>' : '') +
                            '</td>' +
                            '<td class="text-center">' + smartDecimal(item.RemainingQty) + ' ' + (item.PrimaryUnitName || '') + '</td>' +
                            '<td class="text-end">' + cur + ' ' + smartDecimal(item.UnitPrice, 2, true) + '</td>' +
                            '<td class="text-end">' + taxCell + '</td>' +
                            '<td class="text-end">' + discCell + '</td>' +
                            '<td class="text-end fw-semibold">' + cur + ' ' + smartDecimal(rowTotal, 2, true) + '</td>' +
                        '</tr>';
                    });
                    $('#purchItemsTableBody').html(rows);
                    $('#purchItemsTableWrap').removeClass('d-none');
                    $('#purchItemsSelectAll').prop('checked', availCount > 0).prop('disabled', availCount === 0);
                    updatePurchItemsFooter();
                },
                error: function () {
                    ajaxLoading(1);
                    $('#purchItemsLoading').addClass('d-none');
                    $('#purchItemsTableBody').html('<tr><td colspan="7" class="text-center text-danger py-4">Failed to load purchase items.</td></tr>');
                    $('#purchItemsTableWrap').removeClass('d-none');
                }
            });
        }

        $(document).on('change', '#purchItemsSelectAll', function () {
            $('#purchItemsTableBody .purch-item-chk:not(:disabled)').prop('checked', $(this).is(':checked'));
            updatePurchItemsFooter();
        });

        $(document).on('change', '.purch-item-chk', function () {
            var total    = $('#purchItemsTableBody .purch-item-chk:not(:disabled)').length;
            var selected = $('#purchItemsTableBody .purch-item-chk:not(:disabled):checked').length;
            $('#purchItemsSelectAll').prop('checked', total > 0 && selected === total);
            updatePurchItemsFooter();
        });

        /**
         * @returns {void}
         */
        function updatePurchItemsFooter() {
            var count = $('#purchItemsTableBody .purch-item-chk:not(:disabled):checked').length;
            $('#purchItemsSelectedCount').text(count);
            $('#purchItemsAddToCart').prop('disabled', count === 0);
        }

        $(document).on('click', '#purchItemsAddToCart', function () {
            var added = 0;
            $('#purchItemsTableBody .purch-item-chk:checked').each(function () {
                var idx  = parseInt($(this).data('idx'), 10);
                var item = _purchaseItems[idx];
                if (!item) return;

                var productData = {
                    id               : parseInt(item.ProductUID, 10),
                    text             : item.ProductName,
                    itemName         : item.ProductName,
                    description      : item.Description      || '',
                    unitPrice        : parseFloat(item.UnitPrice)        || 0,
                    sellingPrice     : parseFloat(item.SellingPrice)     || 0,
                    purchasePrice    : parseFloat(item.PurchasePrice)    || 0,
                    taxAmount        : parseFloat(item.TaxAmount)        || 0,
                    availableQuantity: 0,
                    hsnCode          : item.HSNCode           || '',
                    categoryUID      : item.CategoryUID  ? parseInt(item.CategoryUID)  : null,
                    categoryName     : item.CategoryName     || '',
                    storageUID       : item.StorageUID   ? parseInt(item.StorageUID)   : null,
                    taxPercent       : parseFloat(item.TaxPercentage)    || 0,
                    cgstPercent      : parseFloat(item.CGST)             || 0,
                    sgstPercent      : parseFloat(item.SGST)             || 0,
                    igstPercent      : parseFloat(item.IGST)             || 0,
                    taxDetailsUID    : parseInt(item.TaxDetailsUID)      || 1,
                    partNumber       : item.PartNumber      || '',
                    primaryUnit      : item.PrimaryUnitName || '',
                    discount         : parseFloat(item.Discount)         || 0,
                    discountType     : 'Percentage',
                    discountTypeUID  : item.DiscountTypeUID ? parseInt(item.DiscountTypeUID) : null,
                    discount_amount  : parseFloat(item.DiscountAmount)   || 0,
                    line_total         : parseFloat(item.TaxableAmount)    || 0,
                    net_total          : parseFloat(item.NetAmount)        || 0,
                    sourceTransProdUID : parseInt(item.TransProdUID, 10)   || null,
                };

                if (typeof billManager !== 'undefined' && typeof formationTableBillItems === 'function') {
                    var qty    = parseFloat(item.RemainingQty > 0 ? item.RemainingQty : item.Quantity) || 1;
                    var result = billManager.addItem(productData, qty);
                    if (result !== false) {
                        formationTableBillItems(billManager.getItemById(productData.id));
                        added++;
                    }
                }
            });

            if (added > 0) {
                if (typeof updateItemTaxBreakdown === 'function') updateItemTaxBreakdown();
                billManager.updateSummary();
            }

            $('#purchaseItemsModal').modal('hide');

            var $pur = $('#fromPurchaseUID');
            $pur.val(null).trigger('change');
            _lastPurchaseUID = 0;
        });

        $('#purchaseItemsModal').on('hidden.bs.modal', function () {
            var $pur = $('#fromPurchaseUID');
            if ($pur.val()) { $pur.val(null).trigger('change'); }
            _lastPurchaseUID = 0;
        });

        // Pre-load vendor's purchases in non-draft edit mode
        if (_isEdit && !_isDraftEdit && _editData.vendorUID) {
            loadVendorPurchases(_editData.vendorUID);
        }

    } // end if (_prItemMethod !== 'Manual')

    // ── Edit: pre-fill items and discount fields ──────────────────────────────
    if (_isEdit) {
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
    }

    // ── Form submit ───────────────────────────────────────────────────────────
    _isPopulating = false;

    var _formId = _cfg.formId || 'prForm';
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
                if (!prefixUID || prefixUID <= 0) return showFormError('Please select a prefix.');
                var transNumber = $.trim($('#transNumber').val());
                if (!transNumber || parseInt(transNumber, 10) <= 0) return showFormError('Transaction number must be greater than 0.');
            }

            var transDate = $.trim($('#transDate').val());
            if (!transDate || !/^\d{4}-\d{2}-\d{2}$/.test(transDate)) return showFormError('Please enter a valid date.');

            var items = typeof billManager !== 'undefined' ? billManager.getAllItems() : [];
            if (!items || items.length === 0) return showFormError('Please add at least one product.');
            if (typeof validateBrandItems === 'function' && !validateBrandItems()) return;

            var bm      = typeof billManager !== 'undefined' ? billManager : null;
            var summary = bm ? bm.summary : {};
            var charges = { AdditionalCharges: JSON.stringify(typeof collectAdditionalCharges === 'function' ? collectAdditionalCharges() : []) };

            var _payRows = (typeof getPaymentSectionData === 'function') ? getPaymentSectionData() : {};
            var postData = $.extend({
                transPrefixSelect      : parseInt($('#transPrefixSelect').val(), 10) || 0,
                transNumber            : $.trim($('#transNumber').val()),
                transDate              : transDate,
                vendorSearch           : vendorUID,
                fromPurchaseUID        : parseInt($('#fromPurchaseUID').val(), 10) || 0,
                purchaseType           : $('#purchaseType').val() || 'Regular',
                dispatchTo             : $('#dispatchTo').val() || '',
                referenceDetails       : $.trim($('#referenceDetails').val()),
                transNotes             : $.trim($('#transNotes').val()),
                transTermsCond         : $.trim($('#transTermsCond').val()),
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
                [csrfName]             : csrfVal,
            }, charges, _payRows);

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
                        _setPendingToast('_prPendingToast', _isEdit ? response.Message : 'Purchase Return created successfully.', 'success');
                        window.location.href = _buildReturnUrl('/purchasereturns');
                    } else {
                        window._r2kRedirecting = true;
                        showUIBlock();
                        _setPendingToast('_prPendingToast', _isEdit ? response.Message : 'Purchase Return created successfully.', 'success');
                        _isDirty = false;
                        window.location.href = _buildReturnUrl('/purchasereturns', action === 'draft' ? 'Draft' : '');
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

// ── Sticky + inline summary bars ──────────────────────────────────────────────
(function () {
    var _formId   = (_cfg && _cfg.formId)   ? _cfg.formId   : 'prForm';
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

    var _totEl  = document.querySelector('.bill_tot_amt');
    if (_totEl) new MutationObserver(_sync).observe(_totEl, { childList: true, subtree: true, characterData: true });
    _sync();
}());

// ── Auto-Draft ────────────────────────────────────────────────────────────────
if (!_isEdit) AutoDraft.initFromCfg(_cfg, '#vendorSearch', 'prForm');
