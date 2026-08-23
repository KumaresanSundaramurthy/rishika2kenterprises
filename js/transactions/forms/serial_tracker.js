// ── Serial Number Tracker — Purchase (manual entry) and Invoice (pick from stock) ─
// Loaded only on Purchase and Invoice form pages.
// Requires bill_manager.js and window._transFormData.transType ('Purchase'|'Invoice').

var SerialTracker = (function ($) {
    'use strict';

    /** @type {Object.<number, string[]>} productId → fetched available serial list */
    var _availableCache = {};

    // ── Internal helpers ───────────────────────────────────────────────────────

    /**
     * Escape a string for safe insertion into an HTML attribute value.
     * @param {string} str
     * @returns {string}
     */
    function _esc(str) {
        return $('<div>').text(String(str || '')).html();
    }

    /**
     * Read all entered/selected serial values from a product's panel and update
     * the corresponding billManager item's serials array.
     * @param {number} productId
     * @returns {string[]} current serials list
     */
    function _syncToBillManager(productId) {
        var serials = [];
        $('#bm_' + productId + '_serialInputs .bm-serial-text-input').each(function () {
            var v = $.trim($(this).val());
            if (v) serials.push(v);
        });
        $('#bm_' + productId + '_serialInputs .bm-serial-select-input').each(function () {
            var v = $.trim($(this).val());
            if (v) serials.push(v);
        });
        if (typeof billManager !== 'undefined') {
            var item = billManager.getItemById(productId);
            if (item) item.serials = serials;
        }
        return serials;
    }

    /**
     * Refresh the "X / Y" count badge for a product's serial panel.
     * @param {number} productId
     */
    function _refreshCount(productId) {
        var serials = _syncToBillManager(productId);
        var qty     = 0;
        if (typeof billManager !== 'undefined') {
            var item = billManager.getItemById(productId);
            if (item) qty = Math.round(parseFloat(item.quantity) || 0);
        }
        var count  = serials.length;
        var $badge = $('#bm_' + productId + '_serialCount');
        $badge
            .text('(' + count + ' / ' + qty + ')')
            .toggleClass('text-danger',  count !== qty || count === 0)
            .toggleClass('text-success', count === qty && count > 0)
            .removeClass(count !== qty || count === 0 ? 'text-success' : 'text-danger');
    }

    // ── Public API ─────────────────────────────────────────────────────────────

    /**
     * Append one text-input row to a product's serial panel (Purchase mode).
     * @param {number} productId
     * @param {string} [value=''] - pre-fill value (used when re-populating on edit)
     * @returns {void}
     */
    function addTextInput(productId, value) {
        var escaped = value ? _esc(value) : '';
        var html =
            '<div class="bm-serial-row">' +
                '<input type="text" class="form-control form-control-sm bm-serial-text-input"' +
                    ' data-id="' + productId + '"' +
                    ' placeholder="Serial number"' +
                    ' autocomplete="off"' +
                    (escaped ? ' value="' + escaped + '"' : '') +
                ' />' +
                '<button type="button"' +
                    ' class="btn btn-sm btn-link text-danger bm-serial-remove-btn p-0"' +
                    ' data-id="' + productId + '" title="Remove">' +
                    '<i class="bx bx-x"></i>' +
                '</button>' +
            '</div>';
        $('#bm_' + productId + '_serialInputs').append(html);
        _refreshCount(productId);
        // Focus the new input
        $('#bm_' + productId + '_serialInputs .bm-serial-row:last .bm-serial-text-input').focus();
    }

    /**
     * Fetch available serials from backend (if not cached) then append a select row.
     * Invoice mode only.
     * @param {number} productId
     * @returns {void}
     */
    function addSelectInput(productId) {
        var $btn = $('[data-id="' + productId + '"].bm-serial-add-btn[data-mode="select"]');
        $btn.prop('disabled', true).find('i').attr('class', 'bx bx-loader-alt bx-spin');

        /**
         * Build and append the select row from a list of serials.
         * @param {string[]} serials
         * @returns {void}
         */
        function _appendRow(serials) {
            $btn.prop('disabled', false).find('i').attr('class', 'bx bx-plus-circle');

            // Serials already chosen in this panel
            var used = [];
            $('#bm_' + productId + '_serialInputs .bm-serial-select-input').each(function () {
                var v = $.trim($(this).val());
                if (v) used.push(v);
            });

            var options = '<option value="">— select —</option>';
            serials.forEach(function (sn) {
                if (used.indexOf(sn) === -1) {
                    options += '<option value="' + _esc(sn) + '">' + _esc(sn) + '</option>';
                }
            });

            var html =
                '<div class="bm-serial-row">' +
                    '<select class="form-select form-select-sm bm-serial-select-input"' +
                        ' data-id="' + productId + '">' +
                        options +
                    '</select>' +
                    '<button type="button"' +
                        ' class="btn btn-sm btn-link text-danger bm-serial-remove-btn p-0"' +
                        ' data-id="' + productId + '" title="Remove">' +
                        '<i class="bx bx-x"></i>' +
                    '</button>' +
                '</div>';
            $('#bm_' + productId + '_serialInputs').append(html);
            _refreshCount(productId);
        }

        if (_availableCache[productId]) {
            _appendRow(_availableCache[productId]);
            return;
        }

        var postData = { ProductUID: productId };
        if (typeof CsrfName !== 'undefined') postData[CsrfName] = CsrfToken;

        $.post('/products/getAvailableSerials', postData, function (resp) {
            if (resp && !resp.Error) {
                _availableCache[productId] = resp.Serials || [];
                _appendRow(_availableCache[productId]);
            } else {
                $btn.prop('disabled', false).find('i').attr('class', 'bx bx-plus-circle');
                if (typeof showFormError === 'function') {
                    showFormError('Could not load available serials: ' + (resp ? resp.Message : 'Server error'));
                }
            }
        }, 'json').fail(function () {
            $btn.prop('disabled', false).find('i').attr('class', 'bx bx-plus-circle');
        });
    }

    /**
     * Remove a serial row when its × button is clicked and re-sync counts.
     * @param {Element} btnEl - the remove button element
     * @returns {void}
     */
    function removeRow(btnEl) {
        var productId = parseInt($(btnEl).data('id'), 10);
        $(btnEl).closest('.bm-serial-row').remove();
        _refreshCount(productId);
    }

    /**
     * Refresh the count badge after a quantity change on a serial-tracked item.
     * @param {number} productId
     * @returns {void}
     */
    function syncQtyHint(productId) {
        _refreshCount(productId);
    }

    /**
     * Pre-populate serial inputs for an item that already has serials loaded (edit/returns mode).
     * Call this after the row is in the DOM. Skips if inputs are already present.
     * Uses text inputs regardless of mode (edit serials were previously validated on save).
     * @param {number} productId
     * @param {string[]} serials - pre-loaded serial values
     * @returns {void}
     */
    function prePopulate(productId, serials) {
        var $inputsDiv = $('#bm_' + productId + '_serialInputs');
        if (!$inputsDiv.length) return;
        if ($inputsDiv.children('.bm-serial-row').length > 0) return; // already populated
        (serials || []).forEach(function (sn) {
            if ($.trim(String(sn || ''))) addTextInput(productId, sn);
        });
    }

    /**
     * Validate all serial-tracked items in the cart.
     * Returns the first error message found, or null if everything is valid.
     * @param {Array} items  - billManager.getAllItems() result
     * @returns {string|null}
     */
    function getValidationError(items) {
        for (var i = 0; i < (items || []).length; i++) {
            var item = items[i];
            if (parseInt(item.IsSerialTracked, 10) !== 1) continue;

            var qty     = Math.round(parseFloat(item.quantity) || 0);
            var serials = Array.isArray(item.serials) ? item.serials : [];
            var name    = String(item.itemName || item.text || ('Row ' + (i + 1)));

            if (qty > 0 && serials.length === 0) {
                return name + ': Please add ' + qty + ' serial number(s).';
            }
            if (serials.length !== qty) {
                return name + ': ' + serials.length + ' serial(s) provided but quantity is ' + qty + '.';
            }

            var seen = {};
            for (var j = 0; j < serials.length; j++) {
                var sn = $.trim(serials[j]);
                if (!sn)      return name + ': Serial number cannot be blank.';
                if (seen[sn]) return name + ': Duplicate serial number "' + sn + '".';
                seen[sn] = true;
            }
        }
        return null;
    }

    // ── Event Delegation ───────────────────────────────────────────────────────

    $(function () {

        // "Add Serial" button
        $(document).on('click', '.bm-serial-add-btn', function () {
            var productId = parseInt($(this).data('id'), 10);
            if (!productId) return;
            var mode = $(this).data('mode') || 'text';
            if (mode === 'select') {
                addSelectInput(productId);
            } else {
                addTextInput(productId);
            }
        });

        // "×" remove button
        $(document).on('click', '.bm-serial-remove-btn', function () {
            removeRow(this);
        });

        // Text input typing
        $(document).on('input', '.bm-serial-text-input', function () {
            _refreshCount(parseInt($(this).data('id'), 10));
        });

        // Select dropdown change
        $(document).on('change', '.bm-serial-select-input', function () {
            _refreshCount(parseInt($(this).data('id'), 10));
        });

        // Qty field change — update required-serial count badge
        $(document).on('change input', '.updateAllBillAmounts', function () {
            var name  = $(this).attr('name') || '';
            var match = name.match(/^bm_(\d+)_qty$/);
            if (!match) return;
            var productId = parseInt(match[1], 10);
            var item = (typeof billManager !== 'undefined') ? billManager.getItemById(productId) : null;
            if (!item || parseInt(item.IsSerialTracked, 10) !== 1) return;
            _refreshCount(productId);
        });

    });

    // ── Public surface ─────────────────────────────────────────────────────────

    return {
        addTextInput       : addTextInput,
        addSelectInput     : addSelectInput,
        removeRow          : removeRow,
        syncQtyHint        : syncQtyHint,
        prePopulate        : prePopulate,
        getValidationError : getValidationError,
    };

}(jQuery));
