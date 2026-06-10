/**
 * TransFilterBar — reusable filter pill system for transaction list pages.
 *
 * Each list page declares which filters it needs by including the
 * common/transactions/filter_bar PHP partial.  That partial renders a
 * #transFilterBar container with data-filter-config JSON and pre-built
 * dropdown markup.  This class wires up all interactivity once.
 *
 * Usage per page:
 *   var tfb = new TransFilterBar({
 *       onChange: function () { getInvoicesDetails(1); }
 *   });
 *
 *   // In getInvoicesDetails, merge tfb state before sending:
 *   var f = $.extend({}, Filter, tfb ? tfb.getState() : {});
 *   loadTransactionList({ ... }, pageNo, rowLimit, f);
 */
(function ($, global) {
    'use strict';

    /* ------------------------------------------------------------------ */
    /* Constructor                                                          */
    /* ------------------------------------------------------------------ */

    function TransFilterBar(opts) {
        this._opts     = opts  || {};
        this._onChange = this._opts.onChange || null;
        this._state    = {};
        this._$bar     = $('#transFilterBar');
        this._$clear   = $('#tfbClearAll');

        if (!this._$bar.length) return;

        this._config = {};
        try { this._config = JSON.parse(this._$bar.attr('data-filter-config') || '{}'); } catch (e) {}

        this._partyCache = null;   // loaded lazily from Upstash
        this._partyType  = this._config.partyType || 'customer';   // 'customer'|'vendor'

        this._bind();
        this._syncClearAll();
    }

    /* ------------------------------------------------------------------ */
    /* Public API                                                           */
    /* ------------------------------------------------------------------ */

    TransFilterBar.prototype.getState = function () {
        return $.extend({}, this._state);
    };

    TransFilterBar.prototype.reset = function () {
        this._state = {};
        this._$bar.find('.tfb-pill').each(function () {
            var $p = $(this);
            $p.removeClass('tfb-active');
            $p.find('.tfb-pill-val').text('');
            $p.find('.tfb-clear-x').addClass('d-none');
            $p.find('.tfb-label').text($p.data('default-label'));
        });
        this._syncClearAll();
    };

    /* ------------------------------------------------------------------ */
    /* Internal binding                                                     */
    /* ------------------------------------------------------------------ */

    TransFilterBar.prototype._bind = function () {
        var self = this;

        // ── Payment Status ──────────────────────────────────────────────
        $(document).on('click', '.tfb-status-item', function (e) {
            e.preventDefault();
            var val = $(this).data('val');
            self._toggleSingle('paymentStatus', 'PaymentStatus', val, val);
        });

        // ── Payment Mode ────────────────────────────────────────────────
        $(document).on('click', '.tfb-mode-item', function (e) {
            e.preventDefault();
            var val = $(this).data('val');
            self._toggleSingle('paymentMode', 'PaymentMode', val, val);
        });

        // ── Party search input (Customer / Vendor) ──────────────────────
        $(document).on('input', '#tfbPartySearch', debounce(function () {
            var q = $(this).val().trim().toLowerCase();
            self._renderPartyList(q);
        }, 280));

        // ── Party dropdown opening — lazy-load cache ────────────────────
        $(document).on('show.bs.dropdown', '#tfbPartyWrap', function () {
            self._ensurePartyCache(function () {
                self._renderPartyList($('#tfbPartySearch').val().trim().toLowerCase());
            });
        });

        // ── Party selection ─────────────────────────────────────────────
        $(document).on('click', '.tfb-party-item', function (e) {
            e.preventDefault();
            var uid  = $(this).data('uid');
            var name = $(this).data('name');
            if (self._state.PartyUID === uid) {
                delete self._state.PartyUID;
                self._resetPill('party');
            } else {
                self._state.PartyUID = uid;
                self._setPill('party', name);
            }
            bootstrap.Dropdown.getOrCreateInstance(document.getElementById('tfbPartyBtn')).hide();
            self._syncClearAll();
            self._fireChange();
        });

        // ── Last Updated (User) ─────────────────────────────────────────
        $(document).on('click', '.tfb-user-item', function (e) {
            e.preventDefault();
            var uid  = $(this).data('uid');
            var name = $(this).data('name');
            if (self._state.UpdatedBy === uid) {
                delete self._state.UpdatedBy;
                self._resetPill('updatedBy');
            } else {
                self._state.UpdatedBy = uid;
                self._setPill('updatedBy', name);
            }
            self._syncClearAll();
            self._fireChange();
        });

        // ── Individual ✕ clear ──────────────────────────────────────────
        $(document).on('click', '.tfb-clear-x', function (e) {
            e.stopPropagation();
            e.preventDefault();
            var type = $(this).closest('.tfb-pill').data('filter-type');
            self._clearByType(type);
            self._syncClearAll();
            self._fireChange();
        });

        // ── Clear All ───────────────────────────────────────────────────
        $(document).on('click', '#tfbClearAll', function (e) {
            e.preventDefault();
            self.reset();
            self._fireChange();
        });
    };

    /* ------------------------------------------------------------------ */
    /* Pill helpers                                                         */
    /* ------------------------------------------------------------------ */

    TransFilterBar.prototype._toggleSingle = function (filterType, stateKey, val, label) {
        if (this._state[stateKey] === val) {
            delete this._state[stateKey];
            this._resetPill(filterType);
        } else {
            this._state[stateKey] = val;
            this._setPill(filterType, label);
        }
        this._syncClearAll();
        this._fireChange();
    };

    TransFilterBar.prototype._setPill = function (filterType, label) {
        var $pill = this._$bar.find('.tfb-pill[data-filter-type="' + filterType + '"]');
        $pill.addClass('tfb-active');
        $pill.find('.tfb-label').text(label);
        $pill.find('.tfb-clear-x').removeClass('d-none');
    };

    TransFilterBar.prototype._resetPill = function (filterType) {
        var $pill = this._$bar.find('.tfb-pill[data-filter-type="' + filterType + '"]');
        $pill.removeClass('tfb-active');
        $pill.find('.tfb-label').text($pill.data('default-label'));
        $pill.find('.tfb-clear-x').addClass('d-none');
    };

    TransFilterBar.prototype._clearByType = function (type) {
        var keyMap = {
            paymentStatus : 'PaymentStatus',
            paymentMode   : 'PaymentMode',
            party         : 'PartyUID',
            updatedBy     : 'UpdatedBy',
        };
        var key = keyMap[type];
        if (key) delete this._state[key];
        this._resetPill(type);
        if (type === 'party') {
            $('#tfbPartySearch').val('');
        }
    };

    TransFilterBar.prototype._syncClearAll = function () {
        var hasAny = Object.keys(this._state).length > 0;
        if (hasAny) {
            this._$clear.removeClass('d-none');
        } else {
            this._$clear.addClass('d-none');
        }
        var count = Object.keys(this._state).length;
        var $badge = $('#tfbActiveBadge');
        if (count > 0) {
            $badge.text(count).removeClass('d-none');
        } else {
            $badge.addClass('d-none');
        }
    };

    /* ------------------------------------------------------------------ */
    /* Party cache + rendering                                             */
    /* ------------------------------------------------------------------ */

    TransFilterBar.prototype._ensurePartyCache = function (cb) {
        if (this._partyCache) { cb(); return; }

        var upstashUrl   = (typeof _upstashUrl   !== 'undefined') ? _upstashUrl   : '';
        var upstashToken = (typeof _upstashReadToken !== 'undefined') ? _upstashReadToken : '';
        var cacheKey     = (this._partyType === 'vendor')
            ? ((typeof _vendorCacheKey !== 'undefined') ? _vendorCacheKey : '')
            : ((typeof _custCacheKey   !== 'undefined') ? _custCacheKey   : '');

        if (!upstashUrl || !cacheKey) {
            $('#tfbPartyList').html('<div class="tfb-party-empty">Not available</div>');
            return;
        }

        var self = this;
        $('#tfbPartyList').html('<div class="tfb-party-empty"><span class="spinner-border spinner-border-sm me-1"></span>Loading…</div>');

        $.ajax({
            url    : upstashUrl + '/hgetall/' + encodeURIComponent(cacheKey),
            method : 'GET',
            headers: { Authorization: 'Bearer ' + upstashToken },
            success: function (resp) {
                var map = resp && resp.result ? resp.result : {};
                var list = [];
                Object.keys(map).forEach(function (uid) {
                    var raw = map[uid];
                    try {
                        var c = (typeof raw === 'string') ? JSON.parse(raw) : raw;
                        list.push({
                            uid  : uid,
                            name : c.Name || c.name || '',
                            area : c.Area || c.area || '',
                        });
                    } catch (ex) {}
                });
                list.sort(function (a, b) { return a.name.localeCompare(b.name); });
                self._partyCache = list;
                cb();
            },
            error: function () {
                $('#tfbPartyList').html('<div class="tfb-party-empty text-danger">Failed to load</div>');
            },
        });
    };

    TransFilterBar.prototype._renderPartyList = function (query) {
        var list = this._partyCache || [];
        var filtered = query
            ? list.filter(function (c) {
                return c.name.toLowerCase().indexOf(query) !== -1 ||
                       c.area.toLowerCase().indexOf(query) !== -1;
            })
            : list;

        if (!filtered.length) {
            $('#tfbPartyList').html('<div class="tfb-party-empty">No results</div>');
            return;
        }

        var selectedUID = this._state.PartyUID || null;
        var html = '';
        var max  = Math.min(filtered.length, 80);
        for (var i = 0; i < max; i++) {
            var c   = filtered[i];
            var sel = (String(c.uid) === String(selectedUID)) ? ' tfb-party-selected' : '';
            html += '<a href="javascript:void(0);" class="tfb-party-item' + sel + '" data-uid="' + _escHtml(String(c.uid)) + '" data-name="' + _escHtml(c.name) + '">';
            html += '<span class="tfb-party-name">' + _escHtml(c.name) + '</span>';
            if (c.area) html += '<span class="tfb-party-area">' + _escHtml(c.area) + '</span>';
            html += '</a>';
        }
        if (filtered.length > max) {
            html += '<div class="tfb-party-empty text-muted" style="font-size:.75rem;">+ ' + (filtered.length - max) + ' more — type to narrow</div>';
        }
        $('#tfbPartyList').html(html);
    };

    /* ------------------------------------------------------------------ */
    /* Fire change                                                          */
    /* ------------------------------------------------------------------ */

    TransFilterBar.prototype._fireChange = function () {
        if (typeof this._onChange === 'function') this._onChange(this.getState());
    };

    /* ------------------------------------------------------------------ */
    /* Utility                                                              */
    /* ------------------------------------------------------------------ */

    function _escHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    /* ------------------------------------------------------------------ */
    /* Export                                                               */
    /* ------------------------------------------------------------------ */

    global.TransFilterBar = TransFilterBar;

}(jQuery, window));
