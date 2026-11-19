'use strict';

/* ── POS Billing JS ─────────────────────────────────────────────────────────
 * Depends on globals injected by trans_footer_script.php:
 *   JwtToken, JwtData, global_services_url, genSettings
 * Also depends on: ProductAppend (productappend.js), jQuery, Select2, flatpickr
 * Config object: window._posCfg (injected by pos.php)
 * ─────────────────────────────────────────────────────────────────────────── */

var _posCfg         = window._posCfg || {};
var _posProducts    = [];   // full product list from ProductAppend
var _posCustomers      = null;  // null = not loaded, Array = ready
var _posCustQueue      = [];    // callbacks waiting for first customer load
var _posCustAttempts   = 0;     // retry counter — stop caching empty after 3 misses
var _posCart        = [];   // [{id, name, partNumber, unitPrice, sellingPrice, taxPercent, taxAmount, taxDetailsUID, primaryUnit, discPercent, qty, lineTotal}]
var _posDecimal     = parseInt(genSettings.DecimalPoints ?? 2, 10);
var _posCurrency    = genSettings.CurrenySymbol ?? '₹';
var _posSelectedRow = -1;  // index of highlighted table row
var _posScreens     = [];  // array of held-bill screen state objects
var _posActiveIdx   = 0;   // index into _posScreens for the visible screen
var _posNextBillId  = 1;   // auto-increment label counter
var _posAcCharges   = {};  // {chargeUID: {netAmount,taxPercent,taxAmount,grossAmount,inPercent,taxUID,name,displayName}}
var _posAcSnapshot  = null; // snapshot of _posAcCharges taken when charge modal opens (for Cancel revert)
var _posTaxSummary  = null; // last computed TransCalc summary (for tax breakup modal)

/**
 * Load customers from Upstash. Retries on empty until attempt 3, then locks.
 * @param {Function} cb  called with the customer array when ready
 * @returns {void}
 */
function _posCustLoad(cb) {
    if (_posCustomers !== null) { cb(_posCustomers); return; }
    _posCustQueue.push(cb);
    if (_posCustQueue.length > 1) return;

    function _flush(list) {
        var cbs = _posCustQueue.slice();
        _posCustQueue = [];
        cbs.forEach(function (fn) { fn(list); });
    }

    function _done(list) {
        _posCustAttempts++;
        if (list.length || _posCustAttempts >= 3) { _posCustomers = list; }
        _flush(list);
    }

    if (typeof UpstashService === 'undefined' || !UpstashService.isEnabled()) { _posCustomers = []; _flush([]); return; }

    var _custKey = UpstashService.orgKey('customers');
    UpstashService.hgetall(_custKey).then(function (map) {
        if (!map || typeof map !== 'object' || !Object.keys(map).length) { _done([]); return; }
        var list = Object.keys(map).map(function (uid) {
            var c = map[uid];
            return {
                id     : parseInt(c.CustomerUID || uid, 10),
                text   : c.Name          || '',
                name   : c.Name          || '',
                area   : c.Area          || '',
                mobile : c.MobileNumber  || '',
                balance: parseFloat(c.ClosingBalance || 0),
                balType: c.ClosingBalType || 'Debit',
            };
        }).filter(function (c) { return c.id && c.name; })
          .sort(function (a, b) { return a.name.localeCompare(b.name); });
        _done(list);
    }).catch(function () { _done([]); });
}


/**
 * Show customer autocomplete dropdown below the search input.
 * @param {string} term
 * @returns {void}
 */
function _posShowCustDropdown(term) {
    var $drop  = $('#posCustDropdown');
    var lcTerm = (term || '').toLowerCase();
    $drop.html('<div class="pos-sdr-loading"><i class="bx bx-loader-alt bx-spin"></i></div>').show();

    /** @param {Array} list @returns {void} */
    function _render(list) {
        var filtered = lcTerm
            ? list.filter(function (c) {
                return c.name.toLowerCase().indexOf(lcTerm) > -1 ||
                       (c.area   && c.area.toLowerCase().indexOf(lcTerm)   > -1) ||
                       (c.mobile && c.mobile.toLowerCase().indexOf(lcTerm) > -1);
              })
            : list;
        filtered = filtered.slice(0, 25);
        if (!filtered.length) {
            $drop.html('<div class="pos-sdr-empty">No customers found</div>');
            return;
        }
        var html = '';
        filtered.forEach(function (c) {
            var w      = (c.name || '').trim().split(/\s+/);
            var ini    = (((w[0]||'')[0]||'') + ((w[1]||'')[0]||'')).toUpperCase() || '?';
            var bal     = parseFloat(c.balance || 0);
            var isDebit = (c.balType || 'Debit') === 'Debit';
            var balCls  = bal === 0 ? 'bal-zero' : (isDebit ? 'bal-due' : 'bal-adv');
            var balLbl  = bal === 0 ? ''          : (isDebit ? 'Bal '   : 'Adv ');
            var balHtml = '<span class="pos-cust-bal ' + balCls + '">' + balLbl + _posCurrency + ' ' + bal.toFixed(_posDecimal) + '</span>';
            html += '<div class="pos-sdr-item pos-cust-item" data-uid="' + c.id + '" data-name="' + _posEsc(c.name) + '">';
            html += '<span class="pos-cust-av">' + ini + '</span>';
            html += '<div class="pos-cust-info"><span class="pos-cust-name">' + _posEsc(c.name) + '</span>';
            if (c.area || c.mobile) {
                html += '<span class="pos-cust-meta">' + [c.area, c.mobile].filter(Boolean).map(_posEsc).join(' · ') + '</span>';
            }
            html += '</div>' + balHtml + '</div>';
        });
        $drop.html(html);
    }

    _posCustLoad(function (list) { _render(list); });
}

$(function () {

    /* ── 1. Flatpickr for date ────────────────────────────────────────────── */
    if (typeof flatpickr === 'function') {
        flatpickr('#posDate', {
            dateFormat  : _posCfg.fpFormat || 'Y-m-d',
            altInput    : true,
            altFormat   : _posCfg.fpAltFormat || 'd M Y',
            defaultDate : 'today',
            static      : false,
            allowInput  : false,
        });
    }

    /* ── 2. Customer autocomplete ───────────────────────────────────────────── */
    var _custDropTimer;

    $('#posCustomerSearch').on('focus input', function () {
        clearTimeout(_custDropTimer);
        var term = $(this).val().trim();
        _custDropTimer = setTimeout(function () { _posShowCustDropdown(term); }, 160);
    });

    $(document).on('mousedown', function (e) {
        if (!$(e.target).closest('.pos-cust-wrap').length) {
            $('#posCustDropdown').hide().empty();
        }
    });

    $('#posCustomerSearch').on('keydown', function (e) {
        if (e.key === 'Escape') { $('#posCustDropdown').hide().empty(); $(this).blur(); }
    });

    $('#posCustClear').on('click', function () {
        $('#posCustomerSearch').val('');
        $('#posCustomerUID').val('0');
        $(this).addClass('d-none');
        $('#posCustDropdown').hide().empty();
    });

    $(document).on('click', '.pos-cust-item', function () {
        $('#posCustomerSearch').val($(this).data('name'));
        $('#posCustomerUID').val($(this).data('uid'));
        $('#posCustClear').removeClass('d-none');
        $('#posCustDropdown').hide().empty();
    });

    /* ── 3. Load products + categories into memory ───────────────────────── */
    ProductAppend.load(function (products) {
        _posProducts = products;
    }, function () {
        showToastNotification('Could not load product list. Try refreshing.', 'error');
    });

    CategoryAppend.populateSelect('#posCatFilter');

    /* ── 4. Product search → floating dropdown ───────────────────────────── */
    var _searchTimer;
    $('#posProductSearch').on('input focus', function () {
        if (!parseInt($('#posCustomerUID').val(), 10)) {
            showToastNotification('Please select a customer before adding products.', 'warning');
            $(this).blur();
            return;
        }
        clearTimeout(_searchTimer);
        var term = $(this).val().trim();
        _searchTimer = setTimeout(function () { _posShowSearchDropdown(term); }, 160);
    });

    $('#posProductSearch').on('keydown', function (e) {
        /* Enter key — add first result */
        if (e.key === 'Enter') {
            var $first = $('#posSearchDropdown .pos-sdr-item').first();
            if ($first.length) { $first.trigger('click'); }
        }
        if (e.key === 'Escape') { $('#posSearchDropdown').hide().empty(); $(this).val(''); }
    });

    /* Hide dropdown when clicking outside */
    $(document).on('click.posSearch', function (e) {
        if (!$(e.target).closest('#posSearchWrap').length) {
            $('#posSearchDropdown').hide().empty();
        }
    });

    $('#posCatFilter').on('change', function () {
        var term = $('#posProductSearch').val().trim();
        _posShowSearchDropdown(term);
    });

    /* Delegated click on search result row */
    $('#posSearchDropdown').on('click', '.pos-sdr-item', function () {
        var id = parseInt($(this).data('id'), 10);
        var p  = _posProducts.find(function (x) { return x.id === id; });
        if (p) {
            _posAddToCart(p);
            /* exact barcode match — auto-advance; otherwise clear and refocus */
            var term = $('#posProductSearch').val().trim();
            if (p.partNumber && p.partNumber.toLowerCase() === term.toLowerCase()) {
                $('#posProductSearch').val('');
            }
        }
        $('#posSearchDropdown').hide().empty();
        $('#posProductSearch').val('').focus();
    });

    /* ── 5. Table cart interactions (delegated) ──────────────────────────── */
    $('#posCartBody')
        .on('click', 'tr.pos-cart-row', function (e) {
            /* Don't select row when clicking an input or delete */
            if ($(e.target).is('input, .pct-del-btn, .pct-del-btn *')) return;
            _posSelectedRow = parseInt($(this).data('idx'), 10);
            _posRenderCart();
        })
        .on('input change blur', '.pct-inp[data-field="sp"]', function () {
            var idx = parseInt($(this).closest('tr').data('idx'), 10);
            var raw = parseFloat($(this).val());
            /* blank field (focus handler cleared it) — restore from stored SP, don't zero */
            if (isNaN(raw)) return;
            _posUpdateItemSP(idx, raw);
        })
        .on('input change blur', '.pct-inp[data-field="disc"]', function () {
            var idx = parseInt($(this).closest('tr').data('idx'), 10);
            var raw = parseFloat($(this).val());
            if (isNaN(raw)) return;
            _posUpdateItemDisc(idx, raw);
        })
        .on('input change blur', '.pct-inp[data-field="qty"]', function (e) {
            var idx = parseInt($(this).closest('tr').data('idx'), 10);
            var qty = parseFloat($(this).val()) || 0;
            /* remove row only on blur/change with zero — not while still typing */
            if (qty <= 0 && e.type !== 'input') _posRemoveFromCart(idx);
            else if (qty > 0) _posChangeQty(idx, qty);
        })
        .on('focus', '.pct-inp', function () {
            var val = parseFloat($(this).val()) || 0;
            if (val > 0) {
                $(this).select();
            } else {
                $(this).val('');
            }
        })
        .on('blur', '.pct-inp', function () {
            /* restore 0 display if user left the field empty */
            if ($(this).val() === '') {
                $(this).val(0);
            }
        })
        .on('click', '.pct-del-btn', function (e) {
            e.stopPropagation();
            var idx = parseInt($(this).data('idx'), 10);
            _posRemoveFromCart(idx);
        });

    /* ── 6. Clear cart ───────────────────────────────────────────────────── */
    $('#posClearCart').on('click', function () {
        if (!_posCart.length) return;
        Swal.fire({
            title            : 'Clear all items?',
            icon             : 'question',
            showCancelButton : true,
            confirmButtonText: 'Clear',
            cancelButtonText : 'Cancel',
            position         : 'center',
            buttonsStyling   : false,
            customClass      : { confirmButton: 'btn btn-danger me-2', cancelButton: 'btn btn-outline-secondary' },
        }).then(function (r) {
            if (r.isConfirmed) {
                _posCart        = [];
                _posSelectedRow = -1;
                _posRenderCart();
            }
        });
    });

    /* ── 7. Discount — modal-based flow ─────────────────────────────────── */

    /**
     * Open the discount modal, pre-filled with any current value.
     * @returns {void}
     */
    function _posOpenDiscModal() {
        var cur = parseFloat($('#posDiscValue').val()) || 0;
        $('#posDiscModalValue').val(cur > 0 ? cur : '');
        $('#posDiscModalType').val($('#posDiscType').val() || 'Percentage');
        $('#posDiscModal').removeClass('d-none');
        setTimeout(function () { $('#posDiscModalValue').trigger('focus').trigger('select'); }, 60);
    }

    /**
     * Close the discount modal without applying.
     * @returns {void}
     */
    function _posCloseDiscModal() {
        $('#posDiscModal').addClass('d-none');
    }

    /**
     * Apply discount from modal inputs to the hidden data fields and re-render.
     * @returns {void}
     */
    function _posApplyDisc() {
        var val  = parseFloat($('#posDiscModalValue').val()) || 0;
        var type = $('#posDiscModalType').val() || 'Percentage';
        $('#posDiscValue').val(val);
        $('#posDiscType').val(type);
        _posCloseDiscModal();
        _posRenderTotals();
    }

    $('#posToggleDiscBtn').on('click', function () { _posOpenDiscModal(); });

    $('#posDiscModalApply').on('click', function () { _posApplyDisc(); });
    $('#posDiscModalCancel').on('click', function () { _posCloseDiscModal(); });

    /* Backdrop click */
    $('#posDiscModal').on('click', function (e) {
        if ($(e.target).is('#posDiscModal')) { _posCloseDiscModal(); }
    });

    /* Enter / Escape inside modal */
    $('#posDiscModalValue').on('keydown', function (e) {
        if (e.key === 'Enter')  { _posApplyDisc(); }
        if (e.key === 'Escape') { _posCloseDiscModal(); }
    });

    /* Remove applied discount */
    $('#posDiscRemove').on('click', function () {
        $('#posDiscValue').val(0);
        $('#posDiscType').val('Percentage');
        _posRenderTotals();
    });

    /* ── 7b. Add Additional Charge — opens modal ────────────────────────── */
    $('#posAddChargeBtn').on('click', function () { _posOpenChargeModal(); });

    $('#posChargeModalApply').on('click', function () { _posCloseChargeModal(true); });
    $('#posChargeModalCancel').on('click', function () { _posCloseChargeModal(false); });

    $('#posChargeModal').on('click', function (e) {
        if ($(e.target).is('#posChargeModal')) { _posCloseChargeModal(false); }
    });

    /* ── 7c. Charge row events (delegated on the section) ─────────────── */
    $('#posChargeSection')
        .on('change input', '.poc-pct-inp', function () {
            var $row = $(this).closest('.poc-row');
            $row.data('driver', 'pct');
            _posAcRecalcRow($row, 'pct');
        })
        .on('change input', '.poc-wot-inp', function () {
            var $row = $(this).closest('.poc-row');
            $row.data('driver', 'wot');
            _posAcRecalcRow($row, 'wot');
        })
        .on('change input', '.poc-wt-inp', function () {
            var $row = $(this).closest('.poc-row');
            $row.data('driver', 'wt');
            _posAcRecalcRow($row, 'wt');
        })
        .on('change', '.poc-tax-sel', function () {
            _posAcRecalcRow($(this).closest('.poc-row'), 'tax');
        })
        .on('focus', '.poc-pct-inp, .poc-wot-inp, .poc-wt-inp', function () {
            var v = parseFloat($(this).val()) || 0;
            if (v === 0) { $(this).val(''); } else { this.select(); }
        })
        .on('blur', '.poc-pct-inp, .poc-wot-inp, .poc-wt-inp', function () {
            if ($(this).val() === '' || isNaN(parseFloat($(this).val()))) {
                $(this).val('0');
                var src = $(this).hasClass('poc-pct-inp') ? 'pct'
                        : $(this).hasClass('poc-wot-inp') ? 'wot' : 'wt';
                _posAcRecalcRow($(this).closest('.poc-row'), src);
            }
        })
        .on('click', '.poc-del-btn', function () {
            var uid = parseInt($(this).data('uid'), 10);
            $(this).closest('.poc-row').remove();
            delete _posAcCharges[uid];
            _posAcSyncVisibility();
            _posAcUpdateSectionTotal();
            _posRenderTotals();
        });

    /* Remove applied charge from Bill Details × button */
    $(document).on('click', '.poc-del-applied', function () {
        var uid = parseInt($(this).data('uid'), 10);
        if (_posAcCharges[uid]) {
            _posAcCharges[uid].grossAmount = 0;
            _posAcCharges[uid].netAmount   = 0;
            _posAcCharges[uid].taxAmount   = 0;
            _posAcCharges[uid].inPercent   = 0;
        }
        /* clear inputs in the charge table if the row exists */
        var $row = $('#posChargeBody .poc-row[data-uid="' + uid + '"]');
        $row.find('.poc-pct-inp, .poc-wot-inp, .poc-wt-inp').val('0');
        _posAcUpdateSectionTotal();
        _posRenderTotals();
    });

    /* ── 8. Payment section — split payment ─────────────────────────────── */
    $('#posIsFullyPaid').on('change', function () {
        var checked   = $(this).is(':checked');
        var s         = _posScreens[_posActiveIdx];
        if (checked) {
            var firstType  = parseInt($('#posPaymentRows .pos-pay-row').first().find('.pos-pay-type-sel').val(), 10) || 0;
            s.payments     = [{payTypeUID: firstType, amount: 0}];
            _posRenderPayRows();
        } else {
            $('#posPaymentRows .pos-pay-amt-inp').first().prop('readonly', false);
        }
        _posUpdatePaySummary();
    });

    $(document).on('click', '.pos-pay-row-del', function () {
        var idx    = parseInt($(this).data('idx'), 10);
        var s      = _posScreens[_posActiveIdx];
        s.payments = _posGetPaymentRows();
        if (s.payments.length > 1) { s.payments.splice(idx, 1); }
        _posRenderPayRows();
        _posUpdatePaySummary();
    });

    $(document).on('input', '.pos-pay-amt-inp', function () {
        if (!$(this).prop('readonly')) {
            $('#posIsFullyPaid').prop('checked', false);
            _posUpdatePaySummary();
        }
    });

    $(document).on('change', '.pos-pay-type-sel', function () {
        _posUpdatePaySummary();
    });

    $('#posAddSplitBtn').on('click', function () {
        var s      = _posScreens[_posActiveIdx];
        s.payments = _posGetPaymentRows();
        if (s.payments.length < 3) {
            s.payments.push({payTypeUID: 0, amount: 0});
            _posRenderPayRows();
            _posUpdatePaySummary();
        }
    });

    /* Tax breakup modal */
    $(document).on('click', '#posTaxInfoBtn', function () {
        _posBuildTaxBreakup();
        $('#posTaxModal').removeClass('d-none');
    });
    $('#posTaxModal').on('click', function (e) {
        if ($(e.target).is('#posTaxModal')) { $('#posTaxModal').addClass('d-none'); }
    });
    $('#posTaxModalClose').on('click', function () { $('#posTaxModal').addClass('d-none'); });

    /* ── 10. Save bill ───────────────────────────────────────────────────── */
    $('#posSaveBill').on('click', function () { _posSubmit('save'); });
    $('#posSavePrint').on('click', function () { _posSubmit('print'); });

    /* Keyboard shortcuts: F2 = focus search, Ctrl+H = hold & new */
    $(document).on('keydown', function (e) {
        if (e.key === 'F2') { e.preventDefault(); $('#posProductSearch').focus().select(); }
        if (e.ctrlKey && (e.key === 'h' || e.key === 'H')) { e.preventDefault(); _posAddScreen(); }
    });

    /* ── 11. Multi-screen tab strip ─────────────────────────────────────── */
    $('#posScreenTabs')
        .on('click', '.pos-tab:not(.active)', function () {
            var idx = parseInt($(this).data('idx'), 10);
            _posSaveCurrentScreen();
            _posRestoreScreen(idx);
        })
        .on('click', '.pos-tab-close', function (e) {
            e.stopPropagation();
            var idx = parseInt($(this).data('idx'), 10);
            _posCloseScreen(idx);
        })
        .on('click', '#posHoldNew', function () {
            _posAddScreen();
        });

    _posInitScreens();
    _posRenderCart();

});

/* ── Search dropdown ─────────────────────────────────────────────────────── */
/**
 * @param {string} term
 * @returns {void}
 */
function _posShowSearchDropdown(term) {
    var catgUID = parseInt($('#posCatFilter').val() || 0, 10);
    var tl      = (term || '').toLowerCase();
    var list    = _posProducts.filter(function (p) {
        var matchTerm = !tl ||
                        (p.itemName     || '').toLowerCase().indexOf(tl) > -1 ||
                        (p.partNumber   || '').toLowerCase().indexOf(tl) > -1 ||
                        (p.categoryName || '').toLowerCase().indexOf(tl) > -1 ||
                        (p.hsnCode      || '').toLowerCase().indexOf(tl) > -1;
        var matchCat  = !catgUID || p.categoryUID === catgUID;
        return matchTerm && matchCat;
    }).slice(0, 20);

    var $dd = $('#posSearchDropdown');

    if (!list.length) {
        var msg = tl ? 'No products match "' + _posEsc(term) + '"' : 'No products available';
        $dd.html('<div class="pos-sdr-empty">' + msg + '</div>').show();
        return;
    }

    var html = '';
    list.forEach(function (p) {
        var sp    = parseFloat(p.sellingPrice)    || 0;
        var stock = parseFloat(p.availableQuantity) || 0;
        var unit  = p.primaryUnit || '';

        var stockCls = stock > 0 ? 'pos-sdr-stock-ok' : (stock < 0 ? 'pos-sdr-stock-neg' : 'pos-sdr-stock-zero');
        var stockLabel = stock + (unit ? ' ' + _posEsc(unit) : '');

        var meta = '';
        if (p.partNumber) { meta += '<span class="pos-sdr-chip pos-sdr-chip-code">' + _posEsc(p.partNumber) + '</span>'; }
        if (p.hsnCode)    { meta += '<span class="pos-sdr-chip pos-sdr-chip-hsn">HSN: '  + _posEsc(p.hsnCode)   + '</span>'; }
        if (p.taxPercent) { meta += '<span class="pos-sdr-chip pos-sdr-chip-tax">Tax: '  + p.taxPercent + '%</span>'; }
        meta += '<span class="pos-sdr-chip ' + stockCls + '">Stock: ' + stockLabel + '</span>';

        html += '<div class="pos-sdr-item" data-id="' + p.id + '">' +
            '<div class="pos-sdr-left">' +
                '<div class="pos-sdr-name">' + _posEsc(p.itemName) + '</div>' +
                '<div class="pos-sdr-meta">' + meta + '</div>' +
            '</div>' +
            '<div class="pos-sdr-right">' +
                '<div class="pos-sdr-price">' + _posCurrency + ' ' + _posFmt(sp) + '</div>' +
                (unit ? '<div class="pos-sdr-unit">/ ' + _posEsc(unit) + '</div>' : '') +
            '</div>' +
        '</div>';
    });
    $dd.html(html).show();

    /* Auto-add on exact barcode match */
    if (list.length === 1 && list[0].partNumber && list[0].partNumber.toLowerCase() === tl) {
        _posAddToCart(list[0]);
        $('#posProductSearch').val('');
        $dd.hide().empty();
    }
}

/* ── Cart operations ─────────────────────────────────────────────────────── */
/**
 * @param {Object} p  product object from ProductAppend
 * @returns {void}
 */
function _posAddToCart(p) {
    var existing = _posCart.findIndex(function (c) { return c.id === p.id; });
    if (existing > -1) {
        _posChangeQty(existing, _posCart[existing].qty + 1);
        _posSelectedRow = existing;
        return;
    }
    var selPrice  = parseFloat(p.sellingPrice) || 0;
    var unitPrice = parseFloat(p.unitPrice)    || 0;
    var taxPct    = parseFloat(p.taxPercent)   || 0;
    _posCart.push({
        id            : p.id,
        name          : p.itemName,
        partNumber    : p.partNumber    || '',
        unitPrice     : unitPrice,
        sellingPrice  : selPrice,
        taxPercent    : taxPct,
        taxAmount     : selPrice - unitPrice,
        taxDetailsUID : p.taxDetailsUID || 0,
        primaryUnit   : p.primaryUnit   || '',
        discPercent   : 0,
        qty           : 1,
        lineTotal     : selPrice,
    });
    _posSelectedRow = _posCart.length - 1;
    _posRenderCart();
}

/**
 * Updates only the amount cell + footer counts + totals for a single row,
 * avoiding a full re-render so the focused input keeps its cursor position.
 * @param {number} idx
 * @returns {void}
 */
function _posUpdateRowDisplay(idx) {
    var item = _posCart[idx];
    var $row = $('#posCartBody tr[data-idx="' + idx + '"]');
    $row.find('.pct-amt').text(_posCurrency + ' ' + _posFmt(item.lineTotal));
    /* also refresh MRP cell (SP column header shows latest selling price) */
    $row.find('.pct-mrp').text(_posCurrency + ' ' + _posFmt(item.sellingPrice));

    /* footer counts */
    $('#posTotalItems').text(_posCart.length);
    var totalQty = _posCart.reduce(function (s, r) { return s + (parseFloat(r.qty) || 0); }, 0);
    $('#posTotalQty').text(totalQty % 1 === 0 ? totalQty : totalQty.toFixed(_posDecimal));

    _posRenderTotals();
    _posRenderTabs();
}

/**
 * @param {number} idx
 * @param {number} newSP
 * @returns {void}
 */
function _posUpdateItemSP(idx, newSP) {
    if (idx < 0 || idx >= _posCart.length) return;
    var taxPct = _posCart[idx].taxPercent || 0;
    _posCart[idx].sellingPrice = newSP;
    /* recalculate base price and per-unit tax from new SP */
    _posCart[idx].unitPrice  = taxPct > 0 ? newSP / (1 + taxPct / 100) : newSP;
    _posCart[idx].taxAmount  = newSP - _posCart[idx].unitPrice;
    var disc = _posCart[idx].discPercent || 0;
    _posCart[idx].lineTotal = (newSP - newSP * disc / 100) * _posCart[idx].qty;
    _posUpdateRowDisplay(idx);
}

/**
 * @param {number} idx
 * @param {number} disc  percentage 0–100
 * @returns {void}
 */
function _posUpdateItemDisc(idx, disc) {
    if (idx < 0 || idx >= _posCart.length) return;
    _posCart[idx].discPercent = Math.min(disc, 100);
    var sp = _posCart[idx].sellingPrice;
    _posCart[idx].lineTotal = (sp - sp * disc / 100) * _posCart[idx].qty;
    _posUpdateRowDisplay(idx);
}

/**
 * @param {number} idx
 * @param {number} qty
 * @returns {void}
 */
function _posChangeQty(idx, qty) {
    if (idx < 0 || idx >= _posCart.length) return;
    qty = Math.max(0, parseFloat(qty) || 0);
    if (qty === 0) { _posRemoveFromCart(idx); return; }
    _posCart[idx].qty       = qty;
    var sp   = _posCart[idx].sellingPrice;
    var disc = _posCart[idx].discPercent || 0;
    _posCart[idx].lineTotal = (sp - sp * disc / 100) * qty;
    _posUpdateRowDisplay(idx);
}

/**
 * @param {number} idx
 * @returns {void}
 */
function _posRemoveFromCart(idx) {
    _posCart.splice(idx, 1);
    if (_posSelectedRow >= _posCart.length) _posSelectedRow = _posCart.length - 1;
    _posRenderCart();
}

/** @returns {void} */
function _posRenderCart() {
    var $body  = $('#posCartBody');
    var $empty = $('#posCartEmpty');

    $('#posTotalItems').text(_posCart.length);
    var _totalQty = _posCart.reduce(function (s, r) { return s + (parseFloat(r.qty) || 0); }, 0);
    $('#posTotalQty').text(_totalQty % 1 === 0 ? _totalQty : _totalQty.toFixed(_posDecimal));

    if (!_posCart.length) {
        $body.empty();
        $empty.show();
        $('#posSaveBill, #posSavePrint, #posToggleDiscBtn, #posAddChargeBtn').prop('disabled', true);
        _posRenderTotals();
        _posRenderTabs();
        return;
    }
    $('#posSaveBill, #posSavePrint, #posToggleDiscBtn, #posAddChargeBtn').prop('disabled', false);
    $empty.hide();

    var cur  = _posCurrency;
    var html = '';
    _posCart.forEach(function (item, idx) {
        var isSel = idx === _posSelectedRow;
        html += '<tr class="pos-cart-row' + (isSel ? ' selected' : '') + '" data-idx="' + idx + '">' +
            '<td class="pct-no">' + (idx + 1) + '</td>' +
            '<td class="pct-name"><span class="pct-name-text" title="' + _posEsc(item.name) + '">' + _posEsc(item.name) + '</span></td>' +
            '<td class="pct-code">' + _posEsc(item.partNumber || '—') + '</td>' +
            '<td class="pct-mrp">' + cur + ' ' + _posFmt(item.sellingPrice) + '</td>' +
            '<td><input type="number" class="pct-inp" data-field="sp" value="' + item.sellingPrice + '" min="0" step="0.01"></td>' +
            '<td><input type="number" class="pct-inp" data-field="disc" value="' + (item.discPercent || 0) + '" min="0" max="100" step="0.01"></td>' +
            '<td><div class="pct-qty-wrap"><input type="number" class="pct-inp" data-field="qty" value="' + item.qty + '" min="0.01" step="1">' +
                (item.primaryUnit ? '<span class="pct-unit">' + _posEsc(item.primaryUnit) + '</span>' : '') + '</div></td>' +
            '<td class="pct-amt">' + cur + ' ' + _posFmt(item.lineTotal) + '</td>' +
            '<td><div class="pct-del-btn" data-idx="' + idx + '" title="Remove"><i class="bx bx-trash"></i></div></td>' +
        '</tr>';
    });
    $body.html(html);
    _posRenderTotals();
    _posRenderTabs();
}

/* ── Additional Charges helpers ─────────────────────────────────────────── */

/**
 * Escape a string for HTML attribute values.
 * @param {string} s
 * @returns {string}
 */
function _pocEsc(s) {
    return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

/**
 * Format a money value — strips trailing zeros, '0' for zero.
 * @param {number} v
 * @returns {string}
 */
function _pocFmt(v) {
    var n = parseFloat(v) || 0;
    return n === 0 ? '0' : parseFloat(n.toFixed(8)).toString();
}

/**
 * Format a percentage value with up to 8 decimal places.
 * @param {number} v
 * @returns {string}
 */
function _pocFmtPct(v) {
    var n = parseFloat(v) || 0;
    return n === 0 ? '0' : parseFloat(n.toFixed(8)).toString();
}

/**
 * Build tax <option> HTML for a charge row.
 * @param {number} selectedUID
 * @returns {string}
 */
function _posAcBuildTaxOptions(selectedUID) {
    var html = '<option value="0" data-pct="0">0</option>';
    $.each(window._posTaxOpts || [], function (_, t) {
        var pct = parseFloat(t.Percentage) || 0;
        if (pct === 0) return;
        var sel = parseInt(t.TaxDetailsUID, 10) === parseInt(selectedUID, 10) ? ' selected' : '';
        html += '<option value="' + t.TaxDetailsUID + '" data-pct="' + pct + '"' + sel + '>' + pct + '</option>';
    });
    return html;
}

/**
 * Build a charge table row HTML string.
 * @param {{ChargeUID:number, Name:string, DisplayName:string, DefaultTaxUID:number, SortOrder:number}} charge
 * @returns {string}
 */
function _posAcBuildRow(charge) {
    var uid  = parseInt(charge.ChargeUID, 10);
    var name = charge.Name        || '';
    var disp = charge.DisplayName || name;
    var dTax = parseInt(charge.DefaultTaxUID, 10) || 0;
    var sort = parseInt(charge.SortOrder, 10)     || 0;
    return '<tr class="poc-row" data-uid="' + uid + '" data-name="' + _pocEsc(name) + '"' +
        ' data-display-name="' + _pocEsc(disp) + '" data-sort="' + sort + '" data-driver="pct">' +
        '<td class="poc-td-name">' +
            '<div class="poc-name-wrap">' +
                '<span class="poc-charge-label">' + _pocEsc(disp) + '</span>' +
                '<span class="poc-plus-tag">+</span>' +
            '</div>' +
        '</td>' +
        '<td class="poc-td-tax">' +
            '<select class="poc-tax-sel">' + _posAcBuildTaxOptions(dTax) + '</select>' +
        '</td>' +
        '<td class="poc-td-pct">' +
            '<input type="number" class="poc-inp poc-pct-inp" min="0" max="100" step="any" value="0">' +
        '</td>' +
        '<td class="poc-td-wot">' +
            '<input type="number" class="poc-inp poc-wot-inp" min="0" step="any" value="0">' +
        '</td>' +
        '<td class="poc-td-wt">' +
            '<input type="number" class="poc-inp poc-wt-inp" min="0" step="any" value="0">' +
        '</td>' +
        '<td class="poc-td-del">' +
            '<button type="button" class="poc-del-btn" data-uid="' + uid + '" title="Remove">' +
                '<i class="bx bx-trash"></i>' +
            '</button>' +
        '</td>' +
        '</tr>';
}

/**
 * Append a charge row to the table if not already present.
 * @param {{ChargeUID:number, Name:string, DisplayName:string, DefaultTaxUID:number}} charge
 * @returns {void}
 */
function _posAcAppendRow(charge) {
    var uid = parseInt(charge.ChargeUID, 10);
    if ($('#posChargeBody .poc-row[data-uid="' + uid + '"]').length) return;
    $('#posChargeBody').append(_posAcBuildRow(charge));
    _posAcSyncVisibility();
}

/**
 * Load all pre-defined charges into the table on first open.
 * @returns {void}
 */
function _posAcLoadAllRows() {
    var charges = window._posChargesData || [];
    charges.forEach(function (c) { _posAcAppendRow(c); });
}

/**
 * Open the Additional Charges modal and snapshot current state for Cancel revert.
 * @returns {void}
 */
function _posOpenChargeModal() {
    if (!$('#posChargeBody .poc-row').length) {
        _posAcLoadAllRows();
    }
    /* snapshot current charges so Cancel can revert */
    _posAcSnapshot = {};
    Object.keys(_posAcCharges).forEach(function (uid) {
        _posAcSnapshot[uid] = $.extend({}, _posAcCharges[uid]);
    });
    $('#posChargeModal').removeClass('d-none');
    _posAcSyncVisibility();
}

/**
 * Close the Additional Charges modal.
 * @param {boolean} apply  true = keep changes; false = revert to snapshot
 * @returns {void}
 */
function _posCloseChargeModal(apply) {
    if (!apply && _posAcSnapshot !== null) {
        /* restore charge data from snapshot */
        _posAcCharges = {};
        Object.keys(_posAcSnapshot).forEach(function (uid) {
            _posAcCharges[uid] = $.extend({}, _posAcSnapshot[uid]);
        });
        /* restore input values in table rows */
        $('#posChargeBody .poc-row').each(function () {
            var uid = parseInt($(this).data('uid'), 10);
            var c   = _posAcCharges[uid] || {};
            $(this).find('.poc-pct-inp').val(c.inPercent   || 0);
            $(this).find('.poc-wot-inp').val(c.netAmount   || 0);
            $(this).find('.poc-wt-inp') .val(c.grossAmount || 0);
        });
        _posAcUpdateSectionTotal();
        _posRenderTotals();
    }
    _posAcSnapshot = null;
    $('#posChargeModal').addClass('d-none');
    if (apply) {
        _posRenderTotals();
    }
}

/**
 * Show/hide the table vs. empty state.
 * @returns {void}
 */
function _posAcSyncVisibility() {
    var hasRows    = $('#posChargeBody .poc-row').length > 0;
    var modalOpen  = !$('#posChargeModal').hasClass('d-none');
    $('#posChargeTable').toggleClass('d-none', !hasRows);
    /* empty state only shown when modal is open but no charges exist */
    $('#posChargeEmpty').toggleClass('d-none', hasRows || !modalOpen);
}

/**
 * Get the cartable taxable amount (subtotal ex-tax) for IN% calculations.
 * @returns {number}
 */
function _posAcGetSubtotal() {
    return _posCart.reduce(function (s, c) {
        return s + c.unitPrice * (1 - (c.discPercent || 0) / 100) * c.qty;
    }, 0);
}

/**
 * Recalculate a charge row and update _posAcCharges.
 * @param {jQuery} $row
 * @param {string} [source]  'pct' | 'wot' | 'wt' | 'tax'
 * @returns {void}
 */
function _posAcRecalcRow($row, source) {
    var uid      = parseInt($row.data('uid'), 10);
    var taxPct   = parseFloat($row.find('.poc-tax-sel option:selected').data('pct')) || 0;
    var taxUID   = parseInt($row.find('.poc-tax-sel').val(), 10) || 0;
    var taxMult  = 1 + taxPct / 100;
    var pctVal   = parseFloat($row.find('.poc-pct-inp').val()) || 0;
    var wotVal   = parseFloat($row.find('.poc-wot-inp').val()) || 0;
    var wtVal    = parseFloat($row.find('.poc-wt-inp').val())  || 0;
    var subtotal = _posAcGetSubtotal();
    var netAmt   = 0;
    var grossAmt = 0;
    var calcPct  = 0;

    if (source === 'pct') {
        netAmt   = subtotal > 0 ? subtotal * pctVal / 100 : 0;
        grossAmt = netAmt * taxMult;
        $row.find('.poc-wot-inp').val(_pocFmt(netAmt));
        $row.find('.poc-wt-inp').val(_pocFmt(grossAmt));
        calcPct  = pctVal;
    } else if (source === 'wot') {
        netAmt   = wotVal;
        grossAmt = netAmt * taxMult;
        calcPct  = subtotal > 0 ? netAmt * 100 / subtotal : 0;
        $row.find('.poc-pct-inp').val(_pocFmtPct(calcPct));
        $row.find('.poc-wt-inp').val(_pocFmt(grossAmt));
    } else if (source === 'wt') {
        netAmt   = wtVal / taxMult;
        grossAmt = wtVal;
        calcPct  = subtotal > 0 ? netAmt * 100 / subtotal : 0;
        $row.find('.poc-pct-inp').val(_pocFmtPct(calcPct));
        $row.find('.poc-wot-inp').val(_pocFmt(netAmt));
    } else {
        /* tax changed — preserve whichever field the user last edited (data-driver) */
        var driver = $row.data('driver') || 'pct';
        if (driver === 'wt') {
            /* keep WITH TAX as entered; recalculate W/O TAX with new rate */
            grossAmt = wtVal;
            netAmt   = grossAmt / taxMult;
            calcPct  = subtotal > 0 ? netAmt * 100 / subtotal : 0;
            $row.find('.poc-pct-inp').val(_pocFmtPct(calcPct));
            $row.find('.poc-wot-inp').val(_pocFmt(netAmt));
        } else if (driver === 'wot') {
            /* keep W/O TAX as entered; recalculate WITH TAX with new rate */
            netAmt   = wotVal;
            grossAmt = netAmt * taxMult;
            calcPct  = subtotal > 0 ? netAmt * 100 / subtotal : 0;
            $row.find('.poc-pct-inp').val(_pocFmtPct(calcPct));
            $row.find('.poc-wt-inp').val(_pocFmt(grossAmt));
        } else {
            /* driver === 'pct' — keep IN(%) as entered; recalculate both amounts */
            netAmt   = subtotal > 0 ? subtotal * pctVal / 100 : 0;
            grossAmt = netAmt * taxMult;
            calcPct  = pctVal;
            $row.find('.poc-wot-inp').val(_pocFmt(netAmt));
            $row.find('.poc-wt-inp').val(_pocFmt(grossAmt));
        }
    }

    _posAcCharges[uid] = {
        netAmount   : netAmt,
        taxPercent  : taxPct,
        taxAmount   : netAmt * taxPct / 100,
        grossAmount : grossAmt,
        inPercent   : calcPct,
        taxUID      : taxUID,
        name        : String($row.data('name')         || ''),
        displayName : String($row.data('display-name') || ''),
    };
    _posAcUpdateSectionTotal();
    /* Only update Bill Details immediately if the modal is not open (Apply commits it) */
    if ($('#posChargeModal').hasClass('d-none')) {
        _posRenderTotals();
    }
}

/**
 * Refresh the total amount shown inside the Additional Charges section.
 * @returns {void}
 */
function _posAcUpdateSectionTotal() {
    var total = _posAcGetTotal();
    $('#posChargeSectionTotal').text(_posCurrency + ' ' + _posFmt(total));
    $('#posChargeSectionTotalRow').toggleClass('d-none', total <= 0);
}

/**
 * Sum of all charge grossAmounts.
 * @returns {number}
 */
function _posAcGetTotal() {
    return Object.values(_posAcCharges).reduce(function (s, c) {
        return s + (parseFloat(c.grossAmount) || 0);
    }, 0);
}


/* ── Totals ──────────────────────────────────────────────────────────────── */
/** @returns {void} */
function _posRenderTotals() {
    var discVal  = parseFloat($('#posDiscValue').val()) || 0;
    var discType = $('#posDiscType').val();

    /* Build per-item calc inputs — derive from sellingPrice (authoritative) */
    var _tcItems = _posCart.map(function (c) {
        var dp     = c.discPercent || 0;
        var factor = (1 - dp / 100) * c.qty;
        var netT   = c.sellingPrice * factor;
        var lineT  = c.taxPercent > 0 ? netT / (1 + c.taxPercent / 100) : netT;
        /* keep lineTotal in sync so AMOUNT column stays consistent */
        c.lineTotal = netT;
        var taxAmt = netT - lineT;
        var split  = TransCalc.splitTax(taxAmt, c.taxPercent / 2, c.taxPercent / 2, false);
        return {
            line_total      : lineT,
            net_total       : netT,
            taxAmount       : taxAmt,
            cgstAmount      : split.cgstAmount,
            sgstAmount      : split.sgstAmount,
            igstAmount      : split.igstAmount,
            discount_amount : c.sellingPrice * dp / 100 * c.qty,
            taxPercent      : c.taxPercent,
            cgstPercent     : c.taxPercent / 2,
            sgstPercent     : c.taxPercent / 2,
            igstPercent     : 0,
            quantity        : c.qty,
        };
    });

    var chargeAmt = _posAcGetTotal();

    var _s = TransCalc.calcSummary(_tcItems, {
        extraDiscValue        : discVal,
        extraDiscType         : discType === 'Percentage' ? 'percentage' : 'amount',
        roundOffEnabled       : true,
        additionalChargesTotal: chargeAmt,
        decimals              : _posDecimal,
    });

    var grossSubtotal = _s.items.taxableAmount;
    var discAmt       = _s.extra.discountAmount;
    var taxAmt        = _s.taxTotals.totalTax;
    var roundOff      = parseFloat(_s.extra.roundOff) || 0;
    var grand         = _s.totals.grandTotal;
    _posTaxSummary    = _s;

    $('#posSubtotal').text(_posCurrency + ' ' + _posFmt(grossSubtotal));
    $('#posTaxAmt').text(_posCurrency + ' ' + _posFmt(taxAmt));
    $('#posGrandTotal').text(_posCurrency + ' ' + _posFmt(grand));

    /* Tax info icon — only when cart has items and tax > 0 */
    $('#posTaxInfoBtn').toggleClass('d-none', !(_posCart.length > 0 && taxAmt > 0));

    _posUpdatePaySummary(grand);

    /* Discount applied row */
    if (discVal > 0 && discAmt > 0) {
        var discTypeVal = $('#posDiscType').val();
        var discLabel   = discTypeVal === 'Percentage'
            ? 'Discount (' + parseFloat(discVal) + '%)'
            : 'Discount (' + _posCurrency + ' ' + _posFmt(discVal) + ')';
        $('#posDiscAppliedLabel').text(discLabel);
        $('#posDiscAmt').text('− ' + _posCurrency + ' ' + _posFmt(discAmt));
        $('#posDiscAppliedRow').removeClass('d-none');
    } else {
        $('#posDiscAppliedRow').addClass('d-none');
    }
    $('#posToggleDiscBtn').toggleClass('active', discVal > 0);

    /* Round off row */
    if (roundOff !== 0) {
        var roSign = roundOff > 0 ? '+ ' : '− ';
        $('#posRoundOffAmt').text(roSign + _posCurrency + ' ' + _posFmt(Math.abs(roundOff)));
        $('#posRoundOffRow').removeClass('d-none');
    } else {
        $('#posRoundOffRow').addClass('d-none');
    }

    /* Applied charge rows — one row per charge with non-zero grossAmount */
    var $chargeList  = $('#posChargeAppliedList');
    $chargeList.empty();
    var anyCharge = false;
    Object.keys(_posAcCharges).forEach(function (uid) {
        var c = _posAcCharges[uid];
        if (!c || (c.grossAmount || 0) <= 0) return;
        anyCharge = true;
        var label = _posEsc(c.displayName || c.name || 'Additional Charge');
        $chargeList.append(
            '<div class="prs-row">' +
            '<div class="prs-disc-left">' +
            '<span class="prs-disc-applied-label">' + label + '</span>' +
            '<button type="button" class="pos-disc-remove-btn poc-del-applied" data-uid="' + uid + '" title="Remove">&times;</button>' +
            '</div>' +
            '<span class="prs-charge-applied-amt">+ ' + _posCurrency + ' ' + _posFmt(c.grossAmount) + '</span>' +
            '</div>'
        );
    });
    $('#posAddChargeBtn').toggleClass('active', anyCharge);
}

/** @returns {number} */
function _posGetGrandTotal() {
    return parseFloat($('#posGrandTotal').text().replace(/[^0-9.]/g, '')) || 0;
}

/* ── Submit ──────────────────────────────────────────────────────────────── */
/**
 * @param {string} action  'save' | 'print'
 * @returns {void}
 */
function _posSubmit(action) {
    if (!_posCart.length) {
        showToastNotification('Add at least one product to the cart.', 'error');
        return;
    }

    var prefixUID   = parseInt($('#posPrefixSelect').val(), 10) || 0;
    var transNumber = $('#posTransNumber').val().trim();
    if (!prefixUID)                              { showToastNotification('Please select an invoice prefix.', 'error'); return; }
    if (!transNumber || parseInt(transNumber, 10) <= 0) { showToastNotification('Invalid transaction number.', 'error'); return; }

    var _payRows = _posGetPaymentRows().filter(function (r) { return r.payTypeUID > 0 && r.amount > 0; });
    if (!_payRows.length) {
        showToastNotification('Please select a payment type and enter an amount.', 'error');
        return;
    }

    var orgId    = parseInt(JwtData.Org.OrgUID, 10);
    var userId   = parseInt(JwtData.User.UserUID, 10);
    var branchId = parseInt(JwtData.Org.BranchUID, 10) || 1;

    var customerUID = parseInt($('#posCustomerUID').val(), 10) || 0;
    var transDate   = $('#posDate').val() || new Date().toISOString().split('T')[0];

    /* ── Totals via TransCalc ── */
    var discVal  = parseFloat($('#posDiscValue').val()) || 0;
    var discType = $('#posDiscType').val();

    /* Build per-item calc inputs (ex-tax line totals + tax split) */
    var _tcItems = _posCart.map(function (c) {
        var dp       = c.discPercent || 0;
        var lineT    = c.unitPrice * (1 - dp / 100) * c.qty;    // ex-tax after item disc
        var netT     = c.lineTotal;                               // inc-tax after item disc
        var taxAmt   = netT - lineT;
        var split    = TransCalc.splitTax(taxAmt, c.taxPercent / 2, c.taxPercent / 2, false);
        return {
            line_total      : lineT,
            net_total       : netT,
            taxAmount       : taxAmt,
            cgstAmount      : split.cgstAmount,
            sgstAmount      : split.sgstAmount,
            igstAmount      : split.igstAmount,
            discount_amount : c.sellingPrice * dp / 100 * c.qty,
            taxPercent      : c.taxPercent,
            cgstPercent     : c.taxPercent / 2,
            sgstPercent     : c.taxPercent / 2,
            igstPercent     : 0,
            quantity        : c.qty,
        };
    });

    var _acTotal = _posAcGetTotal();
    var _summary = TransCalc.calcSummary(_tcItems, {
        extraDiscValue        : discVal,
        extraDiscType         : discType === 'Percentage' ? 'percentage' : 'amount',
        roundOffEnabled       : true,
        additionalChargesTotal: _acTotal,
        decimals              : _posDecimal,
    });

    var grand = _summary.totals.grandTotal;

    /* Items array — merge cart fields with TransCalc values */
    var items = _posCart.map(function (c, i) {
        var tc = _tcItems[i];
        return {
            id               : c.id,
            itemName         : c.name,
            unitPrice        : c.unitPrice,
            sellingPrice     : c.sellingPrice,
            taxAmount        : tc.taxAmount,
            cgstAmount       : tc.cgstAmount,
            sgstAmount       : tc.sgstAmount,
            igstAmount       : tc.igstAmount,
            taxPercent       : c.taxPercent,
            taxDetailsUID    : c.taxDetailsUID || null,
            quantity         : c.qty,
            discount         : c.discPercent || 0,
            discountType     : 'Percentage',
            discountTypeUID  : null,
            discount_amount  : tc.discount_amount,
            line_total       : tc.line_total,
            net_total        : tc.net_total,
            cgstPercent      : tc.cgstPercent,
            sgstPercent      : tc.sgstPercent,
            igstPercent      : tc.igstPercent,
        };
    });

    var isFullyPaid = $('#posIsFullyPaid').is(':checked') ? 1 : 0;
    var paymentRows = _payRows.map(function (r) {
        return {
            paymentTypeUID : r.payTypeUID,
            amount         : r.amount,
            notes          : null,
            bankAccountUID : null,
            referenceNo    : null,
            paymentDate    : transDate,
        };
    });

    var fd = new FormData();
    fd.append('orgId',               orgId);
    fd.append('userId',              userId);
    fd.append('branchId',            branchId);
    fd.append('transPrefixSelect',   prefixUID);
    fd.append('transNumber',         transNumber);
    fd.append('transDate',           transDate);
    fd.append('dueDate',             transDate);
    fd.append('customerSearch',      customerUID);
    fd.append('placeOfSupplyCode',   _posCfg.orgStateCode   || '');
    fd.append('placeOfSupplyName',   _posCfg.orgStateName   || '');
    fd.append('isInterState',        '0');  // POS is always counter/intrastate sale
    fd.append('invoiceType',         _posCfg.defaultInvType || 'Regular');
    fd.append('dispatchFrom',        '');
    fd.append('referenceDetails',    '');
    fd.append('transNotes',          '');
    fd.append('transTermsCond',      '');
    fd.append('extraDiscount',       _summary.extra.discountAmount);
    fd.append('extDiscountType',     discType);
    fd.append('SubTotal',            _summary.items.taxableAmount);
    fd.append('DiscountAmount',      _summary.items.discountTotal);
    fd.append('TaxAmount',           _summary.taxTotals.totalTax);
    fd.append('CgstAmount',          _summary.taxTotals.cgstTotal);
    fd.append('SgstAmount',          _summary.taxTotals.sgstTotal);
    fd.append('IgstAmount',          _summary.taxTotals.igstTotal);
    var _charges = Object.entries(_posAcCharges)
        .filter(function (e) { return (e[1].grossAmount || 0) > 0; })
        .map(function (e) {
            var uid = parseInt(e[0], 10);
            var c   = e[1];
            return {
                chargeUID       : uid,
                name            : c.name        || '',
                displayName     : c.displayName || '',
                inPercent       : c.inPercent   || 0,
                withoutTaxAmount: c.netAmount   || 0,
                taxUID          : c.taxUID      || 0,
                taxPercent      : c.taxPercent  || 0,
                taxAmount       : c.taxAmount   || 0,
                withTaxAmount   : c.grossAmount || 0,
                grossAmount     : c.grossAmount || 0,
            };
        });
    fd.append('AdditionalChargesTotal', _acTotal);
    fd.append('AdditionalCharges',      JSON.stringify(_charges));
    fd.append('GlobalDiscPercent',   discType === 'Percentage' ? discVal : 0);
    fd.append('RoundOff',            _summary.extra.roundOff);
    fd.append('NetAmount',           grand);
    fd.append('Items',               JSON.stringify(items));
    fd.append('SignatureUID',        0);
    fd.append('action',              action);
    fd.append('fromSalesOrderUID',   0);
    fd.append('fromQuotationUID',    0);
    fd.append('CreditNoteUID',       0);
    fd.append('RecordPayment',       _payRows.length > 0 ? 1 : 0);
    fd.append('IsFullyPaid',         isFullyPaid);
    fd.append('PaymentRows',         JSON.stringify(paymentRows));

    _posSetLoading(true);

    $.ajax({
        url         : global_services_url + 'api/invoices/addInvoice',
        method      : 'POST',
        headers     : { Authorization: 'Bearer ' + JwtToken },
        data        : fd,
        processData : false,
        contentType : false,
        cache       : false,
        success: function (resp) {
            _posSetLoading(false);
            if (resp && resp.Error) {
                showToastNotification(resp.Message || 'Could not save invoice.', 'error');
                return;
            }
            var msg = action === 'draft' ? 'Draft saved.' : 'Invoice saved successfully.';
            if (_posScreens.length > 1) {
                /* More bills waiting — close this screen and stay in POS */
                if (typeof showToastNotification === 'function') showToastNotification(msg, 'success');
                _posCloseScreen(_posActiveIdx);
            } else {
                if (typeof showToastNotification === 'function') showToastNotification(msg, 'success');
                if (typeof _setPendingToast === 'function') _setPendingToast('_invPendingToast', msg, 'success');
                setTimeout(function () { window.location.href = '/invoices'; }, 400);
            }
        },
        error: function () {
            _posSetLoading(false);
            showToastNotification('Server error. Please try again.', 'error');
        },
    });
}

/* ── Helpers ─────────────────────────────────────────────────────────────── */
/**
 * @param {number} val
 * @returns {string}
 */
function _posFmt(val) {
    return parseFloat(val || 0).toFixed(_posDecimal).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

/**
 * @param {string} str
 * @returns {string}
 */
function _posEsc(str) {
    return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

/**
 * @param {boolean} on
 * @returns {void}
 */
function _posSetLoading(on) {
    $('#posOverlay').toggle(on);
    $('#posSaveBill, #posSaveDraft').prop('disabled', on);
}

/* ── Multi-screen (Hold Bill) ────────────────────────────────────────────── */

/**
 * @returns {Object}
 */
function _posMakeScreen() {
    return {
        id          : _posNextBillId++,
        cart        : [],
        customerUID : 0,
        customerText: '',
        discValue   : 0,
        discType    : 'Percentage',
        payments    : [{payTypeUID: 0, amount: 0}],
        isFullyPaid : true,
    };
}

/** @returns {void} */
function _posInitScreens() {
    _posScreens   = [_posMakeScreen()];
    _posActiveIdx = 0;
    _posRenderPayRows();
    _posRenderTabs();
}

/** @returns {void} */
function _posSaveCurrentScreen() {
    var s = _posScreens[_posActiveIdx];
    if (!s) return;
    s.cart        = _posCart.map(function (c) { return $.extend({}, c); });
    s.customerUID  = parseInt($('#posCustomerUID').val(), 10) || 0;
    s.customerText = $('#posCustomerSearch').val() || '';
    s.discValue    = parseFloat($('#posDiscValue').val()) || 0;
    s.discType     = $('#posDiscType').val() || 'Percentage';
    s.payments     = _posGetPaymentRows();
    s.isFullyPaid  = $('#posIsFullyPaid').is(':checked');
}

/**
 * @param {number} idx
 * @returns {void}
 */
function _posRestoreScreen(idx) {
    _posActiveIdx = idx;
    var s         = _posScreens[idx];
    _posCart      = s.cart.map(function (c) { return $.extend({}, c); });

    /* Customer */
    $('#posCustomerSearch').val(s.customerText || '');
    $('#posCustomerUID').val(s.customerUID || 0);
    if (s.customerUID) { $('#posCustClear').removeClass('d-none'); } else { $('#posCustClear').addClass('d-none'); }

    /* Discount */
    $('#posDiscValue').val(s.discValue || 0);
    $('#posDiscType').val(s.discType || 'Percentage');

    /* Payment */
    $('#posIsFullyPaid').prop('checked', s.isFullyPaid !== false);
    _posRenderPayRows();

    _posRenderCart();
    _posRenderTabs();
}

/** @returns {void} */
function _posAddScreen() {
    _posSaveCurrentScreen();
    _posScreens.push(_posMakeScreen());
    _posRestoreScreen(_posScreens.length - 1);
    showToastNotification('Bill ' + _posScreens[_posActiveIdx].id + ' — new screen opened. Previous bill is held.', 'info');
}

/**
 * @param {number} idx
 * @returns {void}
 */
function _posCloseScreen(idx) {
    if (_posScreens.length === 1) return;
    _posScreens.splice(idx, 1);
    var nextIdx = Math.min(idx, _posScreens.length - 1);
    _posRestoreScreen(nextIdx);
}

/* ── Payment rows ────────────────────────────────────────────────────────── */

/**
 * Read current payment rows from DOM.
 * @returns {Array<{payTypeUID:number, amount:number}>}
 */
function _posGetPaymentRows() {
    var rows = [];
    $('#posPaymentRows .pos-pay-row').each(function () {
        rows.push({
            payTypeUID: parseInt($(this).find('.pos-pay-type-sel').val(), 10) || 0,
            amount    : parseFloat($(this).find('.pos-pay-amt-inp').val())    || 0,
        });
    });
    return rows.length ? rows : [{payTypeUID: 0, amount: 0}];
}

/**
 * Render payment rows from screen state into DOM.
 * @returns {void}
 */
function _posRenderPayRows() {
    var s           = _posScreens[_posActiveIdx];
    var payments    = (s && s.payments) || [{payTypeUID: 0, amount: 0}];
    var isFullyPaid = $('#posIsFullyPaid').is(':checked');
    var html        = '';

    payments.forEach(function (row, i) {
        var canDelete = payments.length > 1;
        var opts      = '';
        $('#posPayTypeTemplate option').each(function () {
            var v   = $(this).val();
            var sel = parseInt(v, 10) === row.payTypeUID ? ' selected' : '';
            opts   += '<option value="' + v + '"' + sel + '>' + _posEsc($(this).text()) + '</option>';
        });
        var ro  = (isFullyPaid && i === 0 && payments.length === 1) ? ' readonly' : '';
        html   += '<div class="pos-pay-row" data-idx="' + i + '">' +
            '<select class="pos-pay-type-sel">' + opts + '</select>' +
            '<input type="number" class="pos-pay-amt-inp" min="0" step="0.01" value="' +
                _posFmt(row.amount) + '" placeholder="0.00"' + ro + '>' +
            (canDelete
                ? '<button type="button" class="pos-pay-row-del" data-idx="' + i + '" title="Remove">&times;</button>'
                : '') +
        '</div>';
    });

    $('#posPaymentRows').html(html);
}

/**
 * Update total paid and balance due; auto-fills amount when fully paid.
 * @param {number} [grand]  current grand total (read from DOM if omitted)
 * @returns {void}
 */
function _posUpdatePaySummary(grand) {
    var grandAmt    = grand !== undefined ? grand : _posGetGrandTotal();
    var isFullyPaid = $('#posIsFullyPaid').is(':checked');
    var $rows       = $('#posPaymentRows .pos-pay-row');

    if (isFullyPaid && $rows.length === 1) {
        $rows.first().find('.pos-pay-amt-inp').val(_posFmt(grandAmt)).prop('readonly', true);
    }

    var totalPaid   = 0;
    var anyTypeSet  = false;
    $('#posPaymentRows .pos-pay-row').each(function () {
        var typeUID = parseInt($(this).find('.pos-pay-type-sel').val(), 10) || 0;
        var amt     = parseFloat($(this).find('.pos-pay-amt-inp').val()) || 0;
        if (typeUID > 0) { anyTypeSet = true; }
        totalPaid  += amt;
    });

    /* Hide totals summary until a payment type is chosen */
    $('.pos-pay-totals').toggleClass('d-none', !anyTypeSet && !isFullyPaid);

    $('#posTotalPaid').text(_posCurrency + ' ' + _posFmt(totalPaid));

    var rowCount = $rows.length;
    var balance  = grandAmt - totalPaid;
    if (balance > 0.001) {
        $('#posPayBalance').text(_posCurrency + ' ' + _posFmt(balance));
        $('#posPayBalRow').removeClass('d-none');
    } else {
        $('#posPayBalRow').addClass('d-none');
    }

    /* Show Add Split only when not fully paid and < 3 rows */
    $('#posAddSplitBtn').toggleClass('d-none', isFullyPaid || rowCount >= 3);
}

/* ── Tax breakup modal ───────────────────────────────────────────────────── */

/**
 * Populate tax breakup table from last computed summary and show it.
 * @returns {void}
 */
function _posBuildTaxBreakup() {
    if (!_posTaxSummary) return;
    var _s      = _posTaxSummary;
    var html    = '';
    var totTaxable = 0, totCgst = 0, totSgst = 0, totTax = 0;

    _s.taxRates.forEach(function (rk) {
        var ts = _s.taxSummary[rk];
        if (!ts || ts.totalTax <= 0) return;
        totTaxable += ts.taxableAmount;
        totCgst    += ts.cgstAmount;
        totSgst    += ts.sgstAmount;
        totTax     += ts.totalTax;
        var halfPct = parseFloat((parseFloat(rk) / 2).toFixed(4)).toString();
        html += '<tr>' +
            '<td>' + parseFloat(rk) + '%</td>' +
            '<td>' + _posCurrency + ' ' + _posFmt(ts.taxableAmount) + '</td>' +
            '<td>' + _posCurrency + ' ' + _posFmt(ts.cgstAmount) + ' <span class="ptb-pct">(' + halfPct + '%)</span></td>' +
            '<td>' + _posCurrency + ' ' + _posFmt(ts.sgstAmount) + ' <span class="ptb-pct">(' + halfPct + '%)</span></td>' +
            '<td>' + _posCurrency + ' ' + _posFmt(ts.totalTax)   + '</td>' +
        '</tr>';
    });

    if (!html) {
        html = '<tr><td colspan="5" style="text-align:center;padding:14px;color:var(--pos-muted)">No taxable items</td></tr>';
    }
    $('#posTaxBreakBody').html(html);
    $('#posTaxBreakFoot').html(
        '<tr>' +
        '<td><strong>Total</strong></td>' +
        '<td><strong>' + _posCurrency + ' ' + _posFmt(totTaxable) + '</strong></td>' +
        '<td><strong>' + _posCurrency + ' ' + _posFmt(totCgst)    + '</strong></td>' +
        '<td><strong>' + _posCurrency + ' ' + _posFmt(totSgst)    + '</strong></td>' +
        '<td><strong>' + _posCurrency + ' ' + _posFmt(totTax)     + '</strong></td>' +
        '</tr>'
    );
}

/**
 * Re-render the bill tab strip.
 * @returns {void}
 */
function _posRenderTabs() {
    var html = '';
    _posScreens.forEach(function (s, i) {
        var isActive  = i === _posActiveIdx;
        var itemCount = isActive ? _posCart.length : s.cart.length;
        html += '<div class="pos-tab' + (isActive ? ' active' : '') + '" data-idx="' + i + '">' +
            '<i class="bx bx-receipt"></i>' +
            '<span class="pos-tab-label">Bill ' + s.id + '</span>' +
            (itemCount ? '<span class="pos-tab-badge">' + itemCount + '</span>' : '') +
            (_posScreens.length > 1 ? '<span class="pos-tab-close" data-idx="' + i + '" title="Close this bill">×</span>' : '') +
        '</div>';
    });
    html += '<div class="pos-tab-new" id="posHoldNew" title="Hold this bill and start a new one [Ctrl+H]">' +
        '<i class="bx bx-plus"></i> Hold &amp; New <span class="pos-tab-shortcut">Ctrl+H</span>' +
    '</div>';
    $('#posScreenTabs').html(html);
}
