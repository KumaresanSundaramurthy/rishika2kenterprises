/**
 * VariantPicker — modal for selecting product variants (Brand / Size / Both).
 *
 * Modes:
 *   Multi (default): checkboxes + per-row qty; callback receives
 *     [{variantUID, variantLabel, qty, variantSellingPrice}]
 *   Single (singleMode:true): radio-style for re-picking an existing cart row;
 *     callback receives (variantUID, variantLabel)
 *
 * Usage — multi add (called automatically on product select):
 *   VariantPicker.open(productData, defaultQty, function(selections) { … });
 *
 * Usage — single edit (variant-chip re-pick on existing row):
 *   VariantPicker.open(item, qty, function(variantUID, variantLabel) { … }, { singleMode: true });
 */
var VariantPicker = (function ($) {
    'use strict';

    var _onConfirm   = null;
    var _variants    = [];
    var _productData = null;
    var _defaultQty  = 1;
    var _singleMode  = false;
    var _hasBrand    = false;
    var _hasSize     = false;
    var _hasPartNo   = false;

    // single-mode
    var _selectedUID   = null;
    var _selectedLabel = null;

    // multi-mode: uid -> {variantUID, variantLabel, qty, variantSellingPrice}
    var _selections = {};

    // -- Helpers ---------------------------------------------------------------

    /** @returns {string} */
    function _cur() {
        return (typeof CurrencySymbol !== 'undefined' && CurrencySymbol) ? CurrencySymbol : '₹';
    }

    /** @returns {number} */
    function _dec() {
        return (typeof decimalPlaces !== 'undefined') ? decimalPlaces : 2;
    }

    /**
     * @param {string} str
     * @returns {string}
     */
    function _esc(str) {
        return $('<div>').text(str || '').html();
    }

    /**
     * Per-variant price breakdown. SellingPrice is treated as tax-inclusive.
     * @param {Object} v - variant from cache
     * @returns {{sellP:number, unitP:number, taxAmt:number, taxPct:number}}
     */
    function _prices(v) {
        var sellP  = parseFloat(v.SellingPrice || _productData.sellingPrice || 0);
        var taxPct = parseFloat(_productData.taxPercent || 0);
        var unitP  = taxPct > 0 ? sellP / (1 + taxPct / 100) : sellP;
        return { sellP: sellP, unitP: unitP, taxAmt: sellP - unitP, taxPct: taxPct };
    }

    /**
     * Build and inject the <thead> row.
     * @returns {void}
     */
    function _renderHead() {
        var ths = '<tr>';
        ths += '<th class="vp-th vp-col-check">'
            + (!_singleMode ? '<input type="checkbox" id="vpSelectAll" class="form-check-input" title="' + t('lbl_select_all', 'Select All') + '">' : '')
            + '</th>';
        if (_hasBrand)  ths += '<th class="vp-th vp-col-brand">'  + t('lbl_brand',   'Brand')    + '</th>';
        if (_hasSize)   ths += '<th class="vp-th vp-col-size">'   + t('lbl_size',    'Size')     + '</th>';
        if (_hasPartNo) ths += '<th class="vp-th vp-col-partno">' + t('lbl_part_no', 'Part No.') + '</th>';
        ths += '<th class="vp-th vp-col-uprice">' + t('lbl_unit_price', 'Unit Price') + '</th>';
        ths += '<th class="vp-th vp-col-tax">'    + t('lbl_tax',        'Tax')        + '</th>';
        ths += '<th class="vp-th vp-col-sprice">' + t('lbl_with_tax',   'With Tax')   + '</th>';
        ths += '<th class="vp-th vp-col-qty">'    + t('lbl_qty',        'Qty')        + '</th>';
        ths += '</tr>';
        $('#variantPickerTableHead').html(ths);
    }

    // -- Public ----------------------------------------------------------------

    /**
     * Open the variant picker modal.
     * @param {Object}   productData  Full product object (.variants, .sellingPrice, .taxPercent, ...)
     * @param {number}   qty          Default quantity per variant
     * @param {Function} onConfirm    Multi: selections[]; Single: (variantUID, variantLabel)
     * @param {Object}   [options]    { singleMode: boolean }
     * @returns {void}
     */
    function open(productData, qty, onConfirm, options) {
        options        = options || {};
        _singleMode    = !!options.singleMode;
        _onConfirm     = onConfirm;
        _productData   = productData;
        _defaultQty    = Math.max(1, parseInt(qty, 10) || 1);
        _variants      = (productData && Array.isArray(productData.variants)) ? productData.variants : [];
        _selectedUID   = null;
        _selectedLabel = null;
        _selections    = {};

        if (!_variants.length) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon : 'warning',
                    title: t('swal_no_variants_title', 'No Variants Found'),
                    text : t('swal_no_variants_text', 'This product has no variants in cache. Try syncing Items Cache first.'),
                });
            }
            return;
        }

        _hasBrand  = _variants.some(function (v) { return !!(v.BrandName  || '').trim(); });
        _hasSize   = _variants.some(function (v) { return !!(v.SizeName   || '').trim(); });
        _hasPartNo = _variants.some(function (v) { return !!(v.PartNumber || '').trim(); });

        // Product name
        $('#variantPickerProductName').text(productData.itemName || productData.text || '');

        // Modal width: xl only if both brand + size exist
        if (_hasBrand && _hasSize) {
            $('#variantPickerDialog').removeClass('modal-lg').addClass('modal-xl');
        } else {
            $('#variantPickerDialog').removeClass('modal-xl').addClass('modal-lg');
        }

        // Confirm button
        if (_singleMode) {
            $('#variantPickerConfirm')
                .html('<i class="bx bx-check me-1"></i>' + t('btn_select_variant', 'Select Variant'))
                .removeClass('btn-success').addClass('btn-primary');
        } else {
            $('#variantPickerConfirm')
                .html('<i class="bx bx-cart-add me-1"></i>' + t('btn_add_to_bill', 'Add to Bill'))
                .removeClass('btn-primary').addClass('btn-success');
        }

        _renderHead();
        _render();
        _updateFooter();
        $('#variantPickerModal').modal('show');
    }

    // -- Private ---------------------------------------------------------------

    /**
     * Render variant rows as <tr> inside the <tbody>.
     * @returns {void}
     */
    function _render() {
        var $tbody = $('#variantPickerList');
        $tbody.empty();

        var cur = _cur();
        var dec = _dec();

        _variants.forEach(function (v) {
            var uid       = parseInt(v.VariantUID, 10);
            var label     = v.Label     || ('Variant #' + uid);
            var brand     = (v.BrandName || '').trim();
            var size      = (v.SizeName  || '').trim();
            var isChecked = _singleMode ? (uid === _selectedUID) : !!_selections[uid];
            var rowQty    = (isChecked && _selections[uid]) ? _selections[uid].qty : _defaultQty;
            var pr        = _prices(v);

            var checkInput = _singleMode
                ? '<input type="radio" name="vpVariantRadio" class="form-check-input vp-row-check" value="' + uid + '"' + (isChecked ? ' checked' : '') + '>'
                : '<input type="checkbox" class="form-check-input vp-row-check" value="' + uid + '"' + (isChecked ? ' checked' : '') + '>';

            var tds = '<td class="vp-col-check">' + checkInput + '</td>';

            if (_hasBrand) {
                tds += '<td class="vp-col-brand">'
                    + (brand ? '<span class="vp-tag vp-tag-brand">' + _esc(brand) + '</span>' : '<span class="vp-tag-none">-</span>')
                    + '</td>';
            }
            if (_hasSize) {
                tds += '<td class="vp-col-size">'
                    + (size ? '<span class="vp-tag vp-tag-size">' + _esc(size) + '</span>' : '<span class="vp-tag-none">-</span>')
                    + '</td>';
            }

            if (_hasPartNo) {
                var partNo = (v.PartNumber || '').trim();
                tds += '<td class="vp-col-partno">'
                    + (partNo ? '<span class="vp-partno-badge">' + _esc(partNo) + '</span>' : '<span class="vp-tag-none">—</span>')
                    + '</td>';
            }

            tds +=
                '<td class="vp-col-uprice">' + cur + ' ' + pr.unitP.toFixed(dec) + '</td>'
                + '<td class="vp-col-tax">'
                +   '<span class="vp-tax-pct">' + pr.taxPct.toFixed(1) + '%</span>'
                +   '<span class="vp-tax-amt">' + cur + ' ' + pr.taxAmt.toFixed(dec) + '</span>'
                + '</td>'
                + '<td class="vp-col-sprice">' + cur + ' ' + pr.sellP.toFixed(dec) + '</td>'
                + '<td class="vp-col-qty">'
                +   '<input type="number" class="vp-qty-input" min="1" value="' + rowQty + '"'
                +   (!isChecked && !_singleMode ? ' disabled' : '')
                +   (_singleMode ? ' readonly' : '')
                +   '>'
                + '</td>';

            $tbody.append(
                $('<tr>')
                    .addClass('vp-row' + (isChecked ? ' vp-checked' : ''))
                    .attr('data-uid',   uid)
                    .attr('data-label', _esc(label))
                    .html(tds)
            );
        });
    }

    /**
     * Recompute footer summary.
     * @returns {void}
     */
    function _updateFooter() {
        var cur      = _cur();
        var dec      = _dec();
        var selCount = 0;
        var total    = 0;

        if (_singleMode) {
            selCount = (_selectedUID !== null) ? 1 : 0;
            if (selCount) {
                var sv = _variants.find(function (v) { return parseInt(v.VariantUID, 10) === _selectedUID; });
                if (sv) total = _prices(sv).sellP * _defaultQty;
            }
        } else {
            var sels = Object.values(_selections);
            selCount = sels.length;
            sels.forEach(function (s) { total += s.variantSellingPrice * (s.qty || 1); });
        }

        if (selCount > 0) {
            $('#vpSelCount').text(selCount + ' ' + t('lbl_selected', 'selected'));
            $('#vpTotalAmount').text(cur + ' ' + total.toFixed(dec));
            $('.vp-total-label').show();
        } else {
            $('#vpSelCount').text(t('lbl_select_a_variant', 'Select one or more variants to continue'));
            $('#vpTotalAmount').text('');
            $('.vp-total-label').hide();
        }

        $('#variantPickerConfirm').prop('disabled', selCount === 0);
    }

    // -- Events ----------------------------------------------------------------

    $(document).ready(function () {

        // Row click
        $(document).on('click', '#variantPickerList .vp-row', function (e) {
            if ($(e.target).is('input[type=number], .vp-qty-input')) {
                e.stopPropagation();
                return;
            }
            var $item  = $(this);
            var uid    = parseInt($item.data('uid'),   10);
            var label  = String($item.data('label'));
            var $check = $item.find('.vp-row-check');

            if (_singleMode) {
                $('#variantPickerList .vp-row').removeClass('vp-checked');
                $item.addClass('vp-checked');
                $check.prop('checked', true);
                _selectedUID   = uid;
                _selectedLabel = label;
            } else {
                var nowChecked = !$check.prop('checked');
                $check.prop('checked', nowChecked);
                $item.toggleClass('vp-checked', nowChecked);

                var $qty = $item.find('.vp-qty-input');
                $qty.prop('disabled', !nowChecked);

                if (nowChecked) {
                    var qty = Math.max(1, parseInt($qty.val(), 10) || _defaultQty);
                    $qty.val(qty);
                    var v      = _variants.find(function (vv) { return parseInt(vv.VariantUID, 10) === uid; });
                    var sp     = v ? _prices(v).sellP : 0;
                    var bUID   = v ? (parseInt(v.BrandUID, 10) || 0) : 0;
                    var bName  = v ? (v.BrandName  || '') : '';
                    var partNo = v ? (v.PartNumber || '') : '';
                    _selections[uid] = { variantUID: uid, variantLabel: label, qty: qty, variantSellingPrice: sp, brandUID: bUID, brandName: bName, partNumber: partNo };
                } else {
                    delete _selections[uid];
                }

                var total   = $('#variantPickerList .vp-row').length;
                var checked = $('#variantPickerList .vp-row.vp-checked').length;
                $('#vpSelectAll')
                    .prop('checked',       total > 0 && checked === total)
                    .prop('indeterminate', checked > 0 && checked < total);
            }
            _updateFooter();
        });

        // Qty change
        $(document).on('input change', '#variantPickerList .vp-qty-input', function (e) {
            e.stopPropagation();
            var $input = $(this);
            var uid    = parseInt($input.closest('.vp-row').data('uid'), 10);
            var qty    = Math.max(1, parseInt($input.val(), 10) || 1);
            $input.val(qty);
            if (_singleMode)          { _defaultQty = qty; }
            else if (_selections[uid]) { _selections[uid].qty = qty; }
            _updateFooter();
        });

        // Select all
        $(document).on('change', '#vpSelectAll', function () {
            var checked = $(this).prop('checked');
            $('#variantPickerList .vp-row').each(function () {
                var $item  = $(this);
                var uid    = parseInt($item.data('uid'),   10);
                var label  = String($item.data('label'));
                var $check = $item.find('.vp-row-check');
                var $qty   = $item.find('.vp-qty-input');
                $check.prop('checked', checked);
                $item.toggleClass('vp-checked', checked);
                $qty.prop('disabled', !checked);
                if (checked) {
                    var qty    = Math.max(1, parseInt($qty.val(), 10) || _defaultQty);
                    $qty.val(qty);
                    var v      = _variants.find(function (vv) { return parseInt(vv.VariantUID, 10) === uid; });
                    var sp     = v ? _prices(v).sellP : 0;
                    var bUID   = v ? (parseInt(v.BrandUID, 10) || 0) : 0;
                    var bName  = v ? (v.BrandName  || '') : '';
                    var partNo = v ? (v.PartNumber || '') : '';
                    _selections[uid] = { variantUID: uid, variantLabel: label, qty: qty, variantSellingPrice: sp, brandUID: bUID, brandName: bName, partNumber: partNo };
                } else {
                    delete _selections[uid];
                }
            });
            _updateFooter();
        });

        // Confirm
        $(document).on('click', '#variantPickerConfirm', function () {
            if (_singleMode) {
                if (_selectedUID === null) return;
                $('#variantPickerModal').modal('hide');
                if (typeof _onConfirm === 'function') _onConfirm(_selectedUID, _selectedLabel);
            } else {
                var sels = Object.values(_selections);
                if (!sels.length) return;
                $('#variantPickerModal').modal('hide');
                if (typeof _onConfirm === 'function') _onConfirm(sels);
            }
        });

        // Cancel / close
        $(document).on('click', '#variantPickerCancel, #variantPickerClose', function () {
            $('#variantPickerModal').modal('hide');
        });

        // Reset on hide
        $('#variantPickerModal').on('hidden.bs.modal', function () {
            _selectedUID   = null;
            _selectedLabel = null;
            _selections    = {};
            _variants      = [];
            _productData   = null;
            _singleMode    = false;
            _hasBrand      = false;
            _hasSize       = false;
            _hasPartNo     = false;
            $('#variantPickerList').empty();
            $('#variantPickerTableHead').empty();
            $('#variantPickerConfirm').prop('disabled', true);
            $('#variantPickerProductName').text('');
            $('#vpSelCount').text('');
            $('#vpTotalAmount').text('');
            $('#variantPickerDialog').removeClass('modal-xl').addClass('modal-lg');
        });
    });

    return { open: open };

}(jQuery));
