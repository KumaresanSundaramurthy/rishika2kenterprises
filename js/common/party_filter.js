/**
 * TransPartyColFilter — OOP column-level party (customer / vendor) filter.
 *
 * Fetches all records from Upstash HGETALL on first open (one-time cache),
 * renders PAGE_SIZE items at a time via scroll, single-select (click applies
 * immediately without an Apply button).
 *
 * Usage:
 *   var soPartyFilter = new TransPartyColFilter({
 *       boxId     : 'soPartyFilterBox',
 *       triggerId : 'soPartyFilterTrigger',
 *       partyType : 'customer',          // 'customer' | 'vendor'
 *       filterKey : 'PartyUID',
 *       onApply   : function () { PageNo = 1; getSalesOrdersDetails(); }
 *   });
 *
 *   // Merge into every AJAX call:
 *   var f = $.extend({}, Filter, soPartyFilter ? soPartyFilter.getState() : {});
 *
 * Public API:
 *   .getState()  → {} or { PartyUID: 'uid-string' }
 *   .reset()     → clears selection silently (no AJAX)
 */
(function ($, global) {
    'use strict';

    var PAGE_SIZE = 20;

    /* ===================================================================
     * Constructor
     * =================================================================== */
    function TransPartyColFilter(opts) {
        if (!opts || !opts.boxId) {
            console.warn('TransPartyColFilter: boxId is required.');
            return;
        }

        this._boxId     = opts.boxId;
        this._triggerId = opts.triggerId  || '';
        this._partyType = opts.partyType  || 'customer';
        this._filterKey = opts.filterKey  || 'PartyUID';
        this._onApply   = opts.onApply    || null;

        this._$box      = $('#' + this._boxId);
        this._$trigger  = this._triggerId ? $('#' + this._triggerId) : $();

        this._cache         = null;   // full sorted array after Upstash fetch
        this._filteredCache = null;   // search-filtered view of _cache
        this._searchTerm    = '';
        this._page          = 0;      // pages already rendered
        this._selected      = null;   // { uid, name } or null

        this._state     = {};

        if (!this._$box.length) {
            console.warn('TransPartyColFilter: #' + this._boxId + ' not found.');
            return;
        }

        this._bind();
    }

    /* ===================================================================
     * Public API
     * =================================================================== */
    TransPartyColFilter.prototype.getState = function () {
        return $.extend({}, this._state);
    };

    TransPartyColFilter.prototype.reset = function () {
        this._selected      = null;
        this._state         = {};
        this._searchTerm    = '';
        this._filteredCache = this._cache;
        this._setTriggerActive(false);
        this._$box.find('.tpcf-item').removeClass('tpcf-selected');
        this._$box.find('.tpcf-search-input').val('');
        this._$box.find('.catg-search-clear').hide();
        this._$box.find('.tpcf-hint').text('Scroll to load more');
        this._$box.find('.tpcf-clear-btn').hide();
        this._$box.hide();
    };

    /* ===================================================================
     * Private — event binding
     * =================================================================== */
    TransPartyColFilter.prototype._bind = function () {
        var self = this;

        // Open / close via column-header trigger icon
        $(document).on('click', '#' + this._triggerId, function (e) {
            e.stopPropagation();
            self._toggle(this);
        });

        // Single-select: click a party row → apply immediately
        this._$box.on('click', '.tpcf-item', function () {
            var uid  = String($(this).data('uid'));
            var name = String($(this).data('name'));

            self._selected            = { uid: uid, name: name };
            self._state[self._filterKey] = uid;
            self._setTriggerActive(true);

            self._$box.find('.tpcf-item').removeClass('tpcf-selected');
            $(this).addClass('tpcf-selected');
            self._$box.find('.tpcf-selected-label').text(name);
            self._$box.find('.tpcf-clear-btn').show();
            self._$box.hide();

            if (typeof self._onApply === 'function') self._onApply();
        });

        // Clear filter
        this._$box.on('click', '.tpcf-clear-btn', function (e) {
            e.stopPropagation();
            self._selected = null;
            self._state    = {};
            self._setTriggerActive(false);
            self._$box.find('.tpcf-item').removeClass('tpcf-selected');
            self._$box.find('.tpcf-selected-label').text('');
            $(this).hide();
            self._$box.hide();
            if (typeof self._onApply === 'function') self._onApply();
        });

        // Close (×) button
        this._$box.on('click', '.tpcf-close-btn', function () {
            self._$box.hide();
        });

        // Search input — filter the in-memory cache, re-render list
        this._$box.on('input', '.tpcf-search-input', function () {
            self._searchTerm = $(this).val().trim().toLowerCase();
            $(this).siblings('.catg-search-clear').toggle(self._searchTerm !== '');
            if (self._cache !== null) self._applySearch();
        });

        // Clear search button
        this._$box.on('click', '.catg-search-clear', function () {
            $(this).siblings('.tpcf-search-input').val('').trigger('input');
        });

        // Scroll to bottom → load next page (respects filtered cache)
        this._$box.on('scroll', '.tpcf-list', function () {
            var el    = this;
            var cache = self._filteredCache || self._cache;
            if (!cache) return;
            if (el.scrollTop + el.clientHeight >= el.scrollHeight - 40) {
                self._appendPage();
            }
        });

        // Outside-click closes
        $(document).on('click', function (e) {
            if (!self._$box.is(':visible')) return;
            if ($(e.target).closest('#' + self._boxId + ', #' + self._triggerId).length) return;
            self._$box.hide();
        });

        // ESC closes
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape') self._$box.hide();
        });
    };

    /* ===================================================================
     * Private — toggle open / position
     * =================================================================== */
    TransPartyColFilter.prototype._toggle = function (triggerEl) {
        if (this._$box.is(':visible')) {
            this._$box.hide();
            return;
        }

        // Close other open filter boxes
        $('.trans-col-filterbox, .tpcf-box').not(this._$box).hide();

        var rect = triggerEl.getBoundingClientRect();
        var boxW = this._$box.outerWidth() || 300;
        var left = rect.left;
        var top  = rect.bottom + 4;

        if (left + boxW + 16 > window.innerWidth) {
            left = window.innerWidth - boxW - 16;
        }
        if (left < 8) left = 8;

        this._$box.css({ top: top + 'px', left: left + 'px' }).show();
        this._ensureCache();
    };

    /* ===================================================================
     * Private — Upstash fetch + render
     * =================================================================== */
    TransPartyColFilter.prototype._ensureCache = function () {
        if (this._cache !== null) {
            this._renderFromCache();
            return;
        }

        var self      = this;
        var url       = (typeof _upstashUrl       !== 'undefined') ? _upstashUrl       : '';
        var token     = (typeof _upstashReadToken  !== 'undefined') ? _upstashReadToken  : '';
        var cacheKey  = (this._partyType === 'vendor')
            ? ((typeof _vendorCacheKey !== 'undefined') ? _vendorCacheKey : '')
            : ((typeof _custCacheKey   !== 'undefined') ? _custCacheKey   : '');

        if (!url || !token || !cacheKey) {
            this._$box.find('.tpcf-list').html(
                '<div class="tpcf-empty">Cache not configured.</div>'
            );
            return;
        }

        this._$box.find('.tpcf-list').html(
            '<div class="tpcf-empty">' +
            '<span class="spinner-border spinner-border-sm me-1"></span>Loading…' +
            '</div>'
        );

        $.ajax({
            url    : url + '/hgetall/' + encodeURIComponent(cacheKey),
            method : 'GET',
            headers: { Authorization: 'Bearer ' + token },
            success: function (resp) {
                var raw  = (resp && resp.result != null) ? resp.result : {};
                var list = [];

                if (Array.isArray(raw)) {
                    // Upstash REST API returns alternating [key, value, key, value, ...]
                    for (var i = 0; i + 1 < raw.length; i += 2) {
                        try {
                            var c = (typeof raw[i + 1] === 'string') ? JSON.parse(raw[i + 1]) : raw[i + 1];
                            list.push({ uid: raw[i], name: c.Name || c.name || '', area: c.Area || c.area || '' });
                        } catch (ex) { /* skip malformed */ }
                    }
                } else {
                    Object.keys(raw).forEach(function (uid) {
                        try {
                            var c = (typeof raw[uid] === 'string') ? JSON.parse(raw[uid]) : raw[uid];
                            list.push({ uid: uid, name: c.Name || c.name || '', area: c.Area || c.area || '' });
                        } catch (ex) { /* skip malformed */ }
                    });
                }

                list.sort(function (a, b) { return a.name.localeCompare(b.name); });
                self._cache = list;
                self._renderFromCache();
            },
            error: function (xhr) {
                var msg = (xhr.status === 401)
                    ? 'Auth error — check Upstash token.'
                    : 'Failed to load records.';
                self._$box.find('.tpcf-list').html(
                    '<div class="tpcf-empty text-danger">' + msg + '</div>'
                );
            }
        });
    };

    TransPartyColFilter.prototype._renderFromCache = function () {
        // Update count badge
        var total = this._cache ? this._cache.length : 0;
        if (total > 0) {
            this._$box.find('.tpcf-count-badge').text(total).show();
        }

        // Reset search state
        this._searchTerm    = '';
        this._filteredCache = this._cache;
        this._$box.find('.tpcf-search-input').val('');
        this._$box.find('.catg-search-clear').hide();
        this._page = 0;
        this._$box.find('.tpcf-list').empty();

        if (!this._cache || this._cache.length === 0) {
            this._$box.find('.tpcf-list').html(
                '<div class="tpcf-empty">No records found.</div>'
            );
            return;
        }
        this._appendPage();
    };

    TransPartyColFilter.prototype._applySearch = function () {
        var q = this._searchTerm;
        this._filteredCache = q
            ? this._cache.filter(function (c) {
                return c.name.toLowerCase().indexOf(q) !== -1 ||
                       c.area.toLowerCase().indexOf(q) !== -1;
              })
            : this._cache;

        this._page = 0;
        this._$box.find('.tpcf-list').empty();

        if (this._filteredCache.length === 0) {
            this._$box.find('.tpcf-list').html(
                '<div class="tpcf-empty">No results for "' + _esc(q) + '"</div>'
            );
            return;
        }
        this._appendPage();

        // Update hint text
        var hint = (this._filteredCache.length < this._cache.length)
            ? this._filteredCache.length + ' of ' + this._cache.length + ' matched'
            : 'Scroll to load more';
        this._$box.find('.tpcf-hint').text(hint);
    };

    TransPartyColFilter.prototype._appendPage = function () {
        var cache = this._filteredCache || this._cache;
        if (!cache) return;
        var start = this._page * PAGE_SIZE;
        if (start >= cache.length) return;

        var items = cache.slice(start, start + PAGE_SIZE);
        var self  = this;
        var html  = '';

        items.forEach(function (c) {
            var isSel = self._selected && String(self._selected.uid) === String(c.uid);
            html += '<div class="tpcf-item' + (isSel ? ' tpcf-selected' : '') + '"';
            html += ' data-uid="'  + _esc(String(c.uid))  + '"';
            html += ' data-name="' + _esc(c.name)         + '">';
            html += '<span class="tpcf-name">'  + _esc(c.name) + '</span>';
            if (c.area) {
                html += '<span class="tpcf-area">' + _esc(c.area) + '</span>';
            }
            html += '</div>';
        });

        this._$box.find('.tpcf-list').append(html);
        this._page++;
    };

    /* ===================================================================
     * Private — helpers
     * =================================================================== */
    TransPartyColFilter.prototype._setTriggerActive = function (active) {
        if (!this._$trigger.length) return;
        this._$trigger.toggleClass('text-primary', active);
    };

    function _esc(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /* ===================================================================
     * Export
     * =================================================================== */
    global.TransPartyColFilter = TransPartyColFilter;

}(jQuery, window));
