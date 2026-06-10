/**
 * TransColFilter — OOP column-level filter for transaction list pages.
 *
 * One instance per filter column. Handles toggle, apply, reset,
 * outside-click close, and filter-icon active highlight.
 *
 * Usage:
 *   var payStatusFilter = new TransColFilter({
 *       boxId     : 'invPayStatusFilterBox',   // id of the .trans-col-filterbox div
 *       triggerId : 'invPayStatusFilter',       // id of the <a> icon in the <th>
 *       filterKey : 'PaymentStatus',            // key written into the Filter object
 *       onApply   : function () { PageNo = 1; getInvoicesDetails(); }
 *   });
 *
 *   // Before each AJAX call merge state:
 *   var f = $.extend({}, Filter, payStatusFilter.getState());
 *
 * Public API:
 *   .getState()   → plain object  e.g. { PaymentStatus: 'Pending' }  or {}
 *   .reset()      → clears selection + icon highlight (no AJAX)
 */
(function ($, global) {
    'use strict';

    /* ===================================================================
     * Constructor
     * =================================================================== */
    function TransColFilter(opts) {
        if (!opts || !opts.boxId) {
            console.warn('TransColFilter: boxId is required.');
            return;
        }

        this._boxId     = opts.boxId;
        this._triggerId = opts.triggerId  || '';
        this._filterKey = opts.filterKey  || 'Filter';
        this._onApply   = opts.onApply    || null;

        this._$box      = $('#' + this._boxId);
        this._$trigger  = this._triggerId ? $('#' + this._triggerId) : $();
        this._chkClass  = this._$box.data('chk-class') || 'col-filter-chk';

        this._state     = {};   // holds current applied selection

        if (!this._$box.length) {
            console.warn('TransColFilter: box #' + this._boxId + ' not found.');
            return;
        }

        this._bind();
    }

    /* ===================================================================
     * Public API
     * =================================================================== */

    /**
     * Returns the current filter state as a plain object.
     * Empty object when no filter is active.
     */
    TransColFilter.prototype.getState = function () {
        return $.extend({}, this._state);
    };

    /**
     * Programmatically clears the filter (does NOT fire onApply).
     */
    TransColFilter.prototype.reset = function () {
        this._clearSelection();
        this._state = {};
        this._setTriggerActive(false);
        this._$box.hide();
    };

    /* ===================================================================
     * Private — event binding
     * =================================================================== */
    TransColFilter.prototype._bind = function () {
        var self = this;

        // ── Toggle open/close via the column header icon ─────────────
        $(document).on('click', '#' + this._triggerId, function (e) {
            e.stopPropagation();
            self._toggle(this);
        });

        // ── Apply button ─────────────────────────────────────────────
        this._$box.on('click', '.tcf-apply-btn', function () {
            self._apply();
        });

        // ── Reset button ─────────────────────────────────────────────
        this._$box.on('click', '.tcf-reset-btn', function () {
            self._clearSelection();
            self._state = {};
            self._setTriggerActive(false);
            self._$box.hide();
            if (typeof self._onApply === 'function') self._onApply();
        });

        // ── Close (×) button ─────────────────────────────────────────
        this._$box.on('click', '.tcf-close-btn', function () {
            self._$box.hide();
        });

        // ── Search — live-filter visible items ───────────────────────
        this._$box.on('input', '.tcf-search-input', function () {
            var q = $(this).val().trim().toLowerCase();
            self._$box.find('.catg-list-item').each(function () {
                var text = $(this).find('span').text().toLowerCase();
                $(this).toggle(q === '' || text.indexOf(q) !== -1);
            });
            self._syncSelectAll();
        });

        // ── Select All toggle ────────────────────────────────────────
        this._$box.on('change', '.tcf-select-all', function () {
            var checked = $(this).prop('checked');
            self._$box.find('.catg-list-item:visible .' + self._chkClass).prop('checked', checked);
        });

        // ── Individual checkbox → keep Select All in sync ────────────
        this._$box.on('change', '.' + this._chkClass, function () {
            self._syncSelectAll();
        });

        // ── Outside click closes the box ─────────────────────────────
        $(document).on('click', function (e) {
            if (!self._$box.is(':visible')) return;
            if ($(e.target).closest('#' + self._boxId + ', #' + self._triggerId).length) return;
            self._$box.hide();
        });

        // ── ESC key closes the box ───────────────────────────────────
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape') self._$box.hide();
        });

    };

    /* ===================================================================
     * Private — toggle open / position box under trigger icon
     * =================================================================== */
    TransColFilter.prototype._toggle = function (triggerEl) {
        if (this._$box.is(':visible')) {
            this._$box.hide();
            return;
        }

        // Close any other open col-filter boxes first
        $('.trans-col-filterbox').not(this._$box).hide();

        var rect   = triggerEl.getBoundingClientRect();
        var boxW   = this._$box.outerWidth() || 190;
        var left   = rect.left;
        var top    = rect.bottom + 4;

        // Keep box inside viewport horizontally
        if (left + boxW + 16 > window.innerWidth) {
            left = window.innerWidth - boxW - 16;
        }

        this._$box.css({ top: top + 'px', left: left + 'px' }).show();
    };

    /* ===================================================================
     * Private — apply selected checkboxes → state → callback
     * =================================================================== */
    TransColFilter.prototype._apply = function () {
        var selected = [];
        this._$box.find('.' + this._chkClass + ':checked').each(function () {
            selected.push($(this).val());
        });

        if (selected.length > 0) {
            // Store as array in state; single-value pages can read [0] if needed
            this._state[this._filterKey] = selected;
        } else {
            delete this._state[this._filterKey];
        }

        this._setTriggerActive(selected.length > 0);
        this._$box.hide();

        if (typeof this._onApply === 'function') this._onApply();
    };

    /* ===================================================================
     * Private — helpers
     * =================================================================== */
    TransColFilter.prototype._clearSelection = function () {
        this._$box.find('.' + this._chkClass).prop('checked', false);
        this._$box.find('.tcf-select-all').prop('checked', false).prop('indeterminate', false);
        this._$box.find('.tcf-search-input').val('');
        this._$box.find('.catg-list-item').show();
    };

    TransColFilter.prototype._syncSelectAll = function () {
        var $visible = this._$box.find('.catg-list-item:visible');
        var $chks    = $visible.find('.' + this._chkClass);
        var total    = $chks.length;
        var checked  = $chks.filter(':checked').length;
        var $sa      = this._$box.find('.tcf-select-all');
        if (total === 0 || checked === 0) {
            $sa.prop('checked', false).prop('indeterminate', false);
        } else if (checked === total) {
            $sa.prop('checked', true).prop('indeterminate', false);
        } else {
            $sa.prop('checked', false).prop('indeterminate', true);
        }
    };

    TransColFilter.prototype._setTriggerActive = function (active) {
        if (!this._$trigger.length) return;
        this._$trigger.toggleClass('text-primary', active);
    };

    /* ===================================================================
     * Export to global scope
     * =================================================================== */
    global.TransColFilter = TransColFilter;

}(jQuery, window));
