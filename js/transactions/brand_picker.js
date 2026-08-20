/**
 * BrandPicker — modal for selecting a brand when adding a brand-applicable product.
 *
 * Usage:
 *   BrandPicker.open(productData, qty, function(brandUID, brandName) { … });
 *
 * Cancel closes the modal without calling the callback, so the item is not added.
 */
var BrandPicker = (function ($) {
    'use strict';

    var _onConfirm       = null;
    var _selectedUID     = null;
    var _selectedName    = null;
    var _allBrands       = [];

    /** 8-slot avatar palette — deterministic, picked by first-letter char code */
    var _avatarPalette = [
        'bp-avatar--0', 'bp-avatar--1', 'bp-avatar--2', 'bp-avatar--3',
        'bp-avatar--4', 'bp-avatar--5', 'bp-avatar--6', 'bp-avatar--7',
    ];

    // ── Public ──────────────────────────────────────────────────────────────

    /**
     * Open the brand picker for a product row.
     * @param {Object}   productData  Full product object from the search result
     * @param {number}   qty          Quantity already set in the qty input
     * @param {Function} onConfirm    Called with (brandUID, brandName) on confirmation
     * @returns {void}
     */
    function open(productData, qty, onConfirm) {
        _onConfirm    = onConfirm;
        _selectedUID  = null;
        _selectedName = null;

        // Show which product is being branded
        var productLabel = productData.itemName || productData.text || '';
        $('#brandPickerProductName').text(productLabel ? 'for: ' + productLabel : '');
        $('#brandPickerSelectedLabel').empty();

        DropdownCache.ready().then(function (data) {
            _allBrands = (data && Array.isArray(data.brands)) ? data.brands : [];

            if (_allBrands.length === 0) {
                Swal.fire({
                    icon : 'warning',
                    title: t('swal_no_brands_title', 'No Brands Available'),
                    text : t('swal_no_brands_text', 'No brands have been added yet. Please add brands under Products → Brands before adding this item.'),
                });
                return;
            }

            $('#brandPickerSearch').val('');
            $('#brandPickerConfirm').prop('disabled', true);
            _render('');
            $('#brandPickerModal').modal('show');
        });
    }

    // ── Private ──────────────────────────────────────────────────────────────

    /**
     * Render brand list rows filtered by search term.
     * @param {string} term
     * @returns {void}
     */
    function _render(term) {
        var lower    = (term || '').toLowerCase();
        var filtered = lower
            ? _allBrands.filter(function (b) {
                return (b.BrandName || '').toLowerCase().indexOf(lower) >= 0 ||
                       (b.BrandCode || '').toLowerCase().indexOf(lower) >= 0;
              })
            : _allBrands;

        var count = filtered.length;
        $('#brandPickerCount').text(count + ' brand' + (count !== 1 ? 's' : ''));

        var $list = $('#brandPickerList');
        $list.empty();

        if (count === 0) {
            $list.html(
                '<div class="bp-empty">' +
                    '<i class="bx bx-search-alt"></i>' +
                    '<span>' + t('lbl_no_brands_found', 'No brands found.') + '</span>' +
                '</div>'
            );
            return;
        }

        var cdnBase = (typeof CDN_URL !== 'undefined' && CDN_URL) ? CDN_URL : '';

        filtered.forEach(function (b) {
            var uid        = parseInt(b.BrandUID, 10);
            var name       = b.BrandName || '';
            var code       = b.BrandCode || '';
            var isSelected = !!_selectedUID && uid === parseInt(_selectedUID, 10);
            var colorClass = _avatarPalette[(name.toUpperCase().charCodeAt(0) || 0) % 8];

            var leftHtml;
            if (b.Images && b.Images.length && b.Images[0].path) {
                leftHtml = '<img src="' + _esc(cdnBase + b.Images[0].path) + '" class="bp-thumb" alt="">';
            } else {
                leftHtml = '<div class="bp-avatar ' + colorClass + '">' + _esc((name[0] || '?').toUpperCase()) + '</div>';
            }

            var codeHtml  = code ? '<span class="bp-code">' + _esc(code) + '</span>' : '';
            var checkHtml = '<i class="bx bx-check-circle bp-check' + (isSelected ? ' visible' : '') + '"></i>';

            $list.append(
                $('<div>')
                    .addClass('bp-item' + (isSelected ? ' selected' : ''))
                    .attr('data-uid',  uid)
                    .attr('data-name', name)
                    .html(
                        leftHtml +
                        '<div class="bp-info"><span class="bp-name">' + _esc(name) + '</span>' + codeHtml + '</div>' +
                        checkHtml
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
        $(document).on('click', '#brandPickerList .bp-item', function () {
            var $item = $(this);
            var uid   = parseInt($item.data('uid'), 10);
            var name  = String($item.data('name'));

            $('#brandPickerList .bp-item').removeClass('selected');
            $('#brandPickerList .bp-check').removeClass('visible');
            $item.addClass('selected');
            $item.find('.bp-check').addClass('visible');

            _selectedUID  = uid;
            _selectedName = name;
            $('#brandPickerConfirm').prop('disabled', false);

            // Show selected chip in footer
            var code = '';
            _allBrands.forEach(function (b) {
                if (parseInt(b.BrandUID, 10) === uid) { code = b.BrandCode || ''; }
            });
            $('#brandPickerSelectedLabel').html(
                '<span class="bp-selected-chip">' +
                    '<i class="bx bx-purchase-tag-alt"></i>' +
                    _esc(name) +
                    (code ? ' <small class="bp-selected-code">(' + _esc(code) + ')</small>' : '') +
                '</span>'
            );
        });

        // Search filter
        $(document).on('input', '#brandPickerSearch', function () {
            _render($(this).val().trim());
        });

        // Confirm
        $(document).on('click', '#brandPickerConfirm', function () {
            if (!_selectedUID) return;
            $('#brandPickerModal').modal('hide');
            if (typeof _onConfirm === 'function') {
                _onConfirm(_selectedUID, _selectedName);
            }
        });

        // Cancel / close — do nothing; item is NOT added
        $(document).on('click', '#brandPickerCancel, #brandPickerClose', function () {
            $('#brandPickerModal').modal('hide');
        });

        // Reset on hide so stale selection never carries over
        $('#brandPickerModal').on('hidden.bs.modal', function () {
            _selectedUID  = null;
            _selectedName = null;
            $('#brandPickerList').empty();
            $('#brandPickerSearch').val('');
            $('#brandPickerCount').text('');
            $('#brandPickerConfirm').prop('disabled', true);
            $('#brandPickerSelectedLabel').empty();
            $('#brandPickerProductName').text('');
        });
    });

    return { open: open };

}(jQuery));
