// ── Sales Return form — init, submit, and sticky bar ─────────────────────────
// PHP data is injected by form.php as window._transFormData before this file loads.

var _cfg = window._transFormData || {};

// Globals — kept at file scope because other transaction scripts reference them
const EnableStorage    = !!_cfg.enableStorage;
var _formModuleUID     = _cfg.moduleUID  || 106;
var _isEdit            = !!_cfg.isEdit;
var _isDraftEdit       = !!_cfg.isDraftEdit;
var _upstashUrl        = _cfg.upstashUrl    || '';
var _upstashReadToken  = _cfg.upstashToken  || '';
var _custCacheKey      = _cfg.custCacheKey  || '';
var _returnTab         = _cfg.returnTab  || '';
var _returnPage        = _cfg.returnPage || 1;
let imgData;
var _isDirty      = false;
var _isPopulating = false;

// Derived locals
var _orgDateFmt    = _cfg.listDateFormat || 'd M Y';
var _srItemMethod  = _cfg.srItemMethod   || 'Manual';
var _editData      = _isEdit ? (_cfg.editData || {}) : {};
var _editItems     = _isEdit ? (_editData.items || []) : [];

$(function () {
    'use strict';
    _isPopulating = true;

    window._custSearchHideCreate = true;
    searchCustomers('customerSearch');
    transDatePickr('#transDate_disp', '#transDate', false, false, true, true, '');

    // Load invoices when customer is selected (Automatic / Both modes)
    $('#customerSearch').on('select2:select', function (e) {
        var custUID = parseInt($(this).val(), 10);
        if (!custUID || custUID <= 0) return;
        if (_srItemMethod !== 'Manual') loadCustomerInvoices(custUID);
        var data = e.params && e.params.data ? e.params.data : {};
        _showSROnAccountBadge(data.onAccountBalance || 0);
    }).on('select2:clear', function () {
        if (_srItemMethod !== 'Manual') resetInvoiceDropdown();
        $('#srOnAccountBadge').addClass('d-none');
    });

    function loadCustomerInvoices(custUID) {
        var $inv = $('#fromInvoiceUID');
        $inv.prop('disabled', true).html('<option value="">Loading...</option>');
        ajaxLoading(0);
        $.ajax({
            url    : '/salesreturns/getCustomerInvoices',
            method : 'POST',
            data   : { CustomerUID: custUID, [CsrfName]: CsrfToken },
            success: function (res) {
                ajaxLoading(1);
                if (res.Error || !res.Invoices || res.Invoices.length === 0) {
                    $inv.html('<option value="">-- No Invoices Found --</option>').prop('disabled', true);
                    return;
                }
                var _months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                var opts = '<option value="">-- Select Invoice --</option>';
                res.Invoices.forEach(function (inv) {
                    var d = inv.TransDate ? inv.TransDate.split('-') : [];
                    var dateLabel = (function () {
                        if (d.length !== 3) return inv.TransDate || '';
                        var fmt = _orgDateFmt || 'd M Y';
                        return fmt.replace('Y', d[0]).replace('m', d[1]).replace('d', d[2]).replace('M', _months[parseInt(d[1], 10) - 1]);
                    }());
                    var label = inv.UniqueNumber + ' | ' + dateLabel + ' | ' + currencySymbol + parseFloat(inv.NetAmount).toFixed(decimalPlaces);
                    opts += '<option value="' + inv.TransUID + '">' + label + '</option>';
                });
                $inv.html(opts).prop('disabled', false);
            },
            error: function () {
                ajaxLoading(1);
                $inv.html('<option value="">-- Error Loading --</option>').prop('disabled', true);
            }
        });
    }

    function resetInvoiceDropdown() {
        $('#fromInvoiceUID').html('<option value="">-- Select Customer First --</option>').prop('disabled', true);
    }

    function _showSROnAccountBadge(balance) {
        var $badge = $('#srOnAccountBadge');
        if (!$badge.length || !(parseFloat(balance) > 0)) { $badge.addClass('d-none'); return; }
        var cur = (typeof genSettings !== 'undefined' && genSettings.CurrenySymbol) ? genSettings.CurrenySymbol : '₹';
        $('#srOnAccountAmt').text(cur + ' ' + parseFloat(balance).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        $badge.removeClass('d-none');
    }

    // ── Invoice items modal ──────────────────────────────────────────────────
    var _invoiceItems       = [];
    var _lastInvoiceUID     = 0;
    var _invoiceTotalItems  = {};
    var _invoiceProductUIDs = {};

    $('#fromInvoiceUID').on('change', function () {
        var transUID = parseInt($(this).val(), 10);
        if (!transUID || transUID <= 0) return;
        _lastInvoiceUID = transUID;
        openInvoiceItemsModal(transUID, $(this).find('option:selected').text());
    });

    $('#fromInvoiceUID').on('mousedown', function () {
        $(this).data('pre-click-val', $(this).val());
    }).on('click', function () {
        var preVal = parseInt($(this).data('pre-click-val'), 10);
        var curVal = parseInt($(this).val(), 10);
        if (preVal && preVal === curVal && curVal > 0) {
            openInvoiceItemsModal(curVal, $(this).find('option:selected').text());
        }
    });

    function openInvoiceItemsModal(transUID, invoiceLabel) {
        _invoiceItems = [];
        $('#invItemsLoading').removeClass('d-none');
        $('#invItemsTableWrap').addClass('d-none');
        $('#invItemsTableBody').empty();
        $('#invItemsSelectAll').prop('checked', true).prop('disabled', false);
        $('#invItemsSelectedCount').text('0');
        $('#invItemsAddToCart').prop('disabled', true);
        $('#invItemsModalSubtitle').text(invoiceLabel);
        $('#invoiceItemsModal').modal('show');

        ajaxLoading(0);
        $.ajax({
            url    : '/salesreturns/getInvoiceItems',
            method : 'POST',
            data   : { TransUID: transUID, [CsrfName]: CsrfToken },
            success: function (res) {
                ajaxLoading(1);
                $('#invItemsLoading').addClass('d-none');
                if (res.Error || !res.Items || res.Items.length === 0) {
                    $('#invItemsTableBody').html('<tr><td colspan="7" class="text-center text-muted py-4">No items found in this invoice.</td></tr>');
                    $('#invItemsTableWrap').removeClass('d-none');
                    return;
                }
                _invoiceItems = res.Items;
                _invoiceTotalItems[_lastInvoiceUID]  = res.Items.length;
                _invoiceProductUIDs[_lastInvoiceUID] = res.Items.map(function (i) { return parseInt(i.ProductUID, 10); });
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
                    var inCart  = (typeof billManager !== 'undefined' && billManager.getItemById(item.ProductUID) !== null);
                    if (!inCart) availCount++;
                    var rowClass = inCart ? 'table-secondary' : '';
                    var chkAttr  = inCart ? 'disabled title="Already added to cart"' : 'checked';
                    rows += '<tr class="' + rowClass + '" data-idx="' + idx + '">' +
                        '<td><input type="checkbox" class="form-check-input inv-item-chk" data-idx="' + idx + '" data-transproduid="' + (parseInt(item.TransProdUID, 10) || 0) + '" ' + chkAttr + '></td>' +
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
                $('#invItemsTableBody').html(rows);
                $('#invItemsTableWrap').removeClass('d-none');
                $('#invItemsSelectAll').prop('checked', availCount > 0).prop('disabled', availCount === 0);
                updateInvItemsFooter();
            },
            error: function () {
                ajaxLoading(1);
                $('#invItemsLoading').addClass('d-none');
                $('#invItemsTableBody').html('<tr><td colspan="7" class="text-center text-danger py-4">Failed to load invoice items.</td></tr>');
                $('#invItemsTableWrap').removeClass('d-none');
            }
        });
    }

    $(document).on('change', '#invItemsSelectAll', function () {
        $('#invItemsTableBody .inv-item-chk:not(:disabled)').prop('checked', $(this).is(':checked'));
        updateInvItemsFooter();
    });

    $(document).on('change', '.inv-item-chk', function () {
        var total    = $('#invItemsTableBody .inv-item-chk:not(:disabled)').length;
        var selected = $('#invItemsTableBody .inv-item-chk:not(:disabled):checked').length;
        $('#invItemsSelectAll').prop('checked', total > 0 && selected === total);
        updateInvItemsFooter();
    });

    function updateInvItemsFooter() {
        var count = $('#invItemsTableBody .inv-item-chk:not(:disabled):checked').length;
        $('#invItemsSelectedCount').text(count);
        $('#invItemsAddToCart').prop('disabled', count === 0);
    }

    $(document).on('click', '#invItemsAddToCart', function () {
        var added = 0;
        $('#invItemsTableBody .inv-item-chk:checked').each(function () {
            var idx  = parseInt($(this).data('idx'), 10);
            var item = _invoiceItems[idx];
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
                line_total           : parseFloat(item.TaxableAmount)    || 0,
                net_total            : parseFloat(item.NetAmount)        || 0,
                sourceTransProdUID   : parseInt(item.TransProdUID, 10)   || null,
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

        $('#invoiceItemsModal').modal('hide');

        var $inv = $('#fromInvoiceUID');
        $inv.val(null).trigger('change');
        _lastInvoiceUID = 0;
    });

    $('#invoiceItemsModal').on('hidden.bs.modal', function () {
        var $inv = $('#fromInvoiceUID');
        if ($inv.val()) { $inv.val(null).trigger('change'); }
        _lastInvoiceUID = 0;
    });

    // ── Edit path ────────────────────────────────────────────────────────────
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
                if (_srItemMethod !== 'Manual') loadCustomerInvoices(_editData.custUID);
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

        if (typeof billManager !== 'undefined' && Array.isArray(_editItems) && _editItems.length > 0) {
            billManager.batchAdd(_editItems, null);
            $('#btnClearCart').removeClass('d-none');
            if (_editItems.length >= 2) { $('#chkReverseOrder').closest('.form-check-inline').removeClass('d-none'); }
        }
    }

    // ── Form submit ──────────────────────────────────────────────────────────
    _isPopulating = false;

    var _formId = _cfg.formId || 'srForm';
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
                if (!prefixUID || prefixUID <= 0) return showFormError('Please select a sales return prefix.');
                var transNumber = $.trim($('#transNumber').val());
                if (!transNumber || parseInt(transNumber, 10) <= 0) return showFormError('Transaction number must be greater than 0.');
            }

            var transDate = $.trim($('#transDate').val());
            if (!transDate || !/^\d{4}-\d{2}-\d{2}$/.test(transDate)) return showFormError('Please enter a valid return date.');

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
            var charges       = { AdditionalCharges: JSON.stringify(typeof collectAdditionalCharges === 'function' ? collectAdditionalCharges() : []) };

            var postData = $.extend({
                transPrefixSelect      : parseInt($('#transPrefixSelect').val(), 10) || 0,
                transNumber            : $.trim($('#transNumber').val()),
                transDate              : transDate,
                customerSearch         : customerUID,
                fromInvoiceUID         : parseInt($('#fromInvoiceUID').val(), 10) || 0,
                returnType             : $('[name="returnType"]').val() || 'Regular',
                dispatchFrom           : $('[name="dispatchFrom"]').val() || '',
                referenceDetails       : $.trim($('#referenceDetails').val()),
                transNotes             : $.trim($('#transNotes').val()),
                transTermsCond         : $.trim($('#transTermsCond').val()),
                placeOfSupplyCode      : $('#placeOfSupplyCode').val() || '',
                placeOfSupplyName      : $('#placeOfSupplyName').val() || '',
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
            }, charges);

            var _autoDraftUid = (!_isEdit && typeof AutoDraft !== 'undefined') ? AutoDraft.getDraftUid() : 0;
            if (_isEdit) {
                postData.TransUID = parseInt($('input[name="TransUID"]').val(), 10);
            } else if (_autoDraftUid > 0) {
                postData.TransUID = _autoDraftUid;
            }

            var formData = new FormData();
            $.each(postData, function (k, v) { formData.append(k, v); });
            collectTransAttachData(formData);

            if (!_isEdit && action !== 'draft') {
                if (typeof getPaymentAttachmentFiles === 'function') {
                    var _payFiles = getPaymentAttachmentFiles();
                    if (_payFiles && _payFiles.length > 0) {
                        var _totalPayAmt = 0;
                        $('#paymentRowsBody tr').each(function () {
                            _totalPayAmt += parseFloat($(this).find('.pay-amount-inp').val()) || 0;
                        });
                        if (_totalPayAmt <= 0) return showFormError('Payment attachments are added but no payment amount is entered.');
                    }
                }
                if (!serializePaymentRows()) return showFormError('Please enter a valid amount for every payment row.');
                formData.append('PaymentRows',   $('#PaymentRowsJson').val() || '');
                formData.append('IsFullyPaid',   $('#isFullyPaid').is(':checked') ? 1 : 0);
                formData.append('RecordPayment', 1);
                if (typeof getPaymentAttachmentFiles === 'function') {
                    getPaymentAttachmentFiles().forEach(function (f) { formData.append('PaymentFiles[]', f); });
                }
            }
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
                        _setPendingToast('_srPendingToast', response.Message, 'success');
                        window.location.href = _buildReturnUrl('/salesreturns');
                    } else {
                        window._r2kRedirecting = true;
                        showUIBlock();
                        _setPendingToast('_srPendingToast', response.Message, 'success');
                        _isDirty = false;
                        window.location.href = _buildReturnUrl('/salesreturns', action === 'draft' ? 'Draft' : '');
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

// ── Sticky bottom bar — total sync + button delegation ────────────────────────
(function () {
    var cur = (_cfg && _cfg.currency) ? _cfg.currency : '₹';
    var dec = (_cfg && _cfg.decimals) ? _cfg.decimals : 2;
    var _formId = (_cfg && _cfg.formId) ? _cfg.formId : 'srForm';

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
            if (e.target && e.target.classList.contains('pay-amount-inp')) _syncStickyTotals();
        });

        var oaJsonEl = document.getElementById('OnAccountApplyJson');
        if (oaJsonEl) {
            new MutationObserver(_syncStickyTotals).observe(oaJsonEl, { attributes: true, attributeFilter: ['value'] });
        }

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

// ── Auto-Draft ────────────────────────────────────────────────────────────────
if (!_isEdit) AutoDraft.initFromCfg(_cfg, '#customerSearch', 'srForm');
