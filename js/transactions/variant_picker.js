/**
 * VariantPicker — modal for selecting a product variant (Brand + Size combination)
 * when adding a brand-applicable product that has cached variants.
 *
 * Usage:
 *   VariantPicker.open(productData, qty, function(variantUID, variantLabel) { … });
 *
 * Cancel closes the modal without calling the callback, so the item is not added.
 * Variants are read directly from productData.variants (already in cache — no AJAX).
 */
var VariantPicker = (function ($) {
    'use strict';

    var _onConfirm      = null;
    var _selectedUID    = null;
    var _selectedLabel  = null;
    var _variants       = [];

    // ── Public ──────────────────────────────────────────────────────────────

    /**
     * Open the variant picker for a product.
     * @param {Object}   productData  Full product object (must have .variants array)
     * @param {number}   qty          Quantity already set
     * @param {Function} onConfirm    Called with (variantUID, variantLabel) on confirmation
     * @returns {void}
     */
    function open(productData, qty, onConfirm) {
        _onConfirm     = onConfirm;
        _selectedUID   = null;
        _selectedLabel = null;
        _variants      = (productData && Array.isArray(productData.variants)) ? productData.variants : [];

        var productLabel = productData.itemName || productData.text || '';
        $('#variantPickerProductName').text(productLabel ? 'for: ' + productLabel : '');
        $('#variantPickerSelectedLabel').empty();
        $('#variantPickerConfirm').prop('disabled', true);

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

        _render();
        $('#variantPickerModal').modal('show');
    }

    // ── Private ──────────────────────────────────────────────────────────────

    /**
     * Render the variant list rows.
     * @returns {void}
     */
    function _render() {
        var $list = $('#variantPickerList');
        $list.empty();

        _variants.forEach(function (v) {
            var uid       = parseInt(v.VariantUID, 10);
            var label     = v.Label || ('Variant #' + uid);
            var brand     = v.BrandName || '';
            var size      = v.SizeName  || '';
            var isSelected = _selectedUID !== null && uid === _selectedUID;

            var brandHtml = brand ? '<span class="vp-brand">' + _esc(brand) + '</span>' : '';
            var sizeHtml  = size  ? '<span class="vp-size">'  + _esc(size)  + '</span>' : '';
            var checkHtml = '<i class="bx bx-check-circle vp-check' + (isSelected ? ' visible' : '') + '"></i>';

            $list.append(
                $('<div>')
                    .addClass('vp-item' + (isSelected ? ' selected' : ''))
                    .attr('data-uid',   uid)
                    .attr('data-label', label)
                    .html(
                        '<div class="vp-avatar"><i class="bx bx-purchase-tag-alt"></i></div>'
                        + '<div class="vp-info">'
                        +   '<span class="vp-label">' + _esc(label) + '</span>'
                        +   '<div class="vp-meta">' + brandHtml + (brand && size ? '<span class="vp-sep">/</span>' : '') + sizeHtml + '</div>'
                        + '</div>'
                        + checkHtml
                    )
            );
        });
    }

    /**
     * @param {string} str
     * @returns {string}
     */
    function _esc(str) {
        return $('<div>').text(str || '').html();
    }

    // ── Event wiring ─────────────────────────────────────────────────────────

    $(document).ready(function () {

        // Row select
        $(document).on('click', '#variantPickerList .vp-item', function () {
            var $item = $(this);
            var uid   = parseInt($item.data('uid'), 10);
            var label = String($item.data('label'));

            $('#variantPickerList .vp-item').removeClass('selected');
            $('#variantPickerList .vp-check').removeClass('visible');
            $item.addClass('selected');
            $item.find('.vp-check').addClass('visible');

            _selectedUID   = uid;
            _selectedLabel = label;
            $('#variantPickerConfirm').prop('disabled', false);

            $('#variantPickerSelectedLabel').html(
                '<span class="vp-selected-chip"><i class="bx bx-layer me-1"></i>' + _esc(label) + '</span>'
            );
        });

        // Confirm
        $(document).on('click', '#variantPickerConfirm', function () {
            if (!_selectedUID) return;
            $('#variantPickerModal').modal('hide');
            if (typeof _onConfirm === 'function') {
                _onConfirm(_selectedUID, _selectedLabel);
            }
        });

        // Cancel / close — item is NOT added
        $(document).on('click', '#variantPickerCancel, #variantPickerClose', function () {
            $('#variantPickerModal').modal('hide');
        });

        // Reset on hide so stale selection never carries over
        $('#variantPickerModal').on('hidden.bs.modal', function () {
            _selectedUID   = null;
            _selectedLabel = null;
            _variants      = [];
            $('#variantPickerList').empty();
            $('#variantPickerConfirm').prop('disabled', true);
            $('#variantPickerSelectedLabel').empty();
            $('#variantPickerProductName').text('');
        });
    });

    return { open: open };

}(jQuery));
