'use strict';

/**
 * CountryPicker — reusable country selection overlay.
 *
 * Renders a fixed overlay appended to <body> (z-index 9999) so it works
 * correctly when triggered from inside a Bootstrap modal or any other
 * stacking context.
 *
 * Data source: Upstash globalKey('loc-countries') → AJAX /globally/getCountryInfo
 * Country shape: { id, name, iso2, phonecode, emoji }
 *
 * Imperative API:
 *   CountryPicker.open({
 *     currentISO2 : 'IN',
 *     onSelect    : function (iso2, code, name, emoji) { ... }
 *   });
 *
 * Data-attribute API (zero JS in calling form):
 *   <button class="country-code-trigger"
 *       data-code-target="#CM_CountryCode"
 *       data-iso2-target="#CM_CountryISO2"
 *       data-hint-target="#CM_CountryHint">+91</button>
 */
window.CountryPicker = (function ($) {

    var _cache    = null;
    var _onSelect = null;
    var _$wrap    = null;   // the overlay DOM node (created once, reused)

    // ── Build overlay DOM (once) ──────────────────────────────────────────────

    function _ensureOverlay() {
        if (_$wrap && document.body.contains(_$wrap[0])) return;

        _$wrap = $(
            '<div id="cpOverlay">' +
                '<div id="cpBox">' +
                    '<div id="cpHeader">' +
                        '<span id="cpTitle"><i class="bx bx-globe"></i> Select Country</span>' +
                        '<button type="button" id="cpClose" aria-label="Close">&times;</button>' +
                    '</div>' +
                    '<div id="cpBody">' +
                        '<input type="text" id="cpSearch" autocomplete="off" placeholder="Search country or code..." />' +
                        '<div id="cpList"></div>' +
                    '</div>' +
                '</div>' +
            '</div>'
        );
        $('body').append(_$wrap);

        // Close on backdrop click
        _$wrap.on('click', function (e) {
            if ($(e.target).is('#cpOverlay')) _close();
        });
        _$wrap.on('click', '#cpClose', _close);

        // Live search
        _$wrap.on('input', '#cpSearch', function () {
            _render(_cache || [], $(this).val());
        });

        // Country selection
        _$wrap.on('click', '.cp-item', function () {
            var iso2  = $(this).data('iso2')  || '';
            var code  = $(this).data('code')  || '';
            var name  = $(this).data('name')  || '';
            var emoji = $(this).data('emoji') || '';
            if (typeof _onSelect === 'function') _onSelect(iso2, code, name, emoji);
            _close();
        });
    }

    // ── Load countries: Upstash globalKey → AJAX fallback ────────────────────

    /**
     * @param {Function} cb
     * @returns {void}
     */
    function _load(cb) {
        if (_cache) { cb(_cache); return; }
        if (typeof UpstashService === 'undefined' || !UpstashService.isEnabled()) {
            _ajax(cb); return;
        }
        UpstashService.get(UpstashService.globalKey('loc-countries'))
            .then(function (data) {
                if (Array.isArray(data) && data.length) {
                    _cache = data;
                    cb(_cache);
                } else {
                    _ajax(cb);
                }
            })
            .catch(function () { _ajax(cb); });
    }

    /**
     * @param {Function} cb
     * @returns {void}
     */
    function _ajax(cb) {
        $.ajax({
            url      : '/globally/getCountryInfo',
            dataType : 'json',
            success  : function (res) {
                _cache = (res && res.Data) ? res.Data : [];
                cb(_cache);
            },
            error    : function () { _cache = []; cb([]); }
        });
    }

    // ── Render list ───────────────────────────────────────────────────────────

    /**
     * @param {Array}  countries
     * @param {string} term
     * @returns {void}
     */
    function _render(countries, term) {
        var activeISO2 = (_$wrap ? _$wrap.data('activeISO2') : '') || '';
        var q = (term || '').toLowerCase().trim();
        var list = q
            ? countries.filter(function (c) {
                return (c.name       || '').toLowerCase().indexOf(q) >= 0 ||
                       (c.iso2       || '').toLowerCase().indexOf(q) >= 0 ||
                       String(c.phonecode || '').indexOf(q) >= 0;
              })
            : countries;

        if (!list.length) {
            $('#cpList').html('<p class="cp-empty">No countries found</p>');
            return;
        }

        var html = list.map(function (c) {
            var code     = '+' + String(c.phonecode || '');
            var isActive = activeISO2 && (c.iso2 || '').toUpperCase() === activeISO2.toUpperCase();
            return '<button type="button" class="cp-item' + (isActive ? ' cp-active' : '') + '"' +
                ' data-iso2="' + _esc(c.iso2) + '"' +
                ' data-code="' + _esc(code) + '"' +
                ' data-name="' + _esc(c.name) + '"' +
                ' data-emoji="' + _esc(c.emoji || '') + '">' +
                '<span class="cp-emoji">' + (c.emoji || '') + '</span>' +
                '<span class="cp-name">' + _esc(c.name) + '</span>' +
                '<span class="cp-code">' + _esc(code) + '</span>' +
                '</button>';
        }).join('');

        $('#cpList').html(html);

        // Scroll active item into view
        if (activeISO2) {
            var $a = $('#cpList .cp-active');
            if ($a.length) { $('#cpList').scrollTop($a[0].offsetTop - 60); }
        }
    }

    /** @param {string} s @returns {string} */
    function _esc(s) {
        return String(s || '')
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // ── Close overlay ─────────────────────────────────────────────────────────

    /** @returns {void} */
    function _close() {
        if (_$wrap) _$wrap.hide();
    }

    // ── Public: open ─────────────────────────────────────────────────────────

    /**
     * @param {{currentISO2?: string, onSelect?: Function}} options
     * @returns {void}
     */
    function open(options) {
        options   = options || {};
        _onSelect = options.onSelect || null;
        var iso2  = (options.currentISO2 || '').toUpperCase();

        _ensureOverlay();
        _$wrap.data('activeISO2', iso2);

        $('#cpSearch').val('');
        $('#cpList').html('<p class="cp-loading"><i class="bx bx-loader-alt bx-spin"></i></p>');
        _$wrap.css('display', 'flex');

        _load(function (countries) {
            _render(countries, '');
            setTimeout(function () { $('#cpSearch').trigger('focus'); }, 50);
        });
    }

    // ── Data-attribute trigger (wired once on document) ───────────────────────

    $(document).on('click', '.country-code-trigger', function () {
            var $btn       = $(this);
            var codeTarget = $btn.attr('data-code-target');
            var iso2Target = $btn.attr('data-iso2-target');
            var hintTarget = $btn.attr('data-hint-target');
            var currentISO2 = iso2Target ? $(iso2Target).val() : '';

            open({
                currentISO2: currentISO2,
                /**
                 * @param {string} iso2
                 * @param {string} code
                 * @returns {void}
                 */
                onSelect: function (iso2, code) {
                    if (codeTarget) $(codeTarget).val(code);
                    if (iso2Target) $(iso2Target).val(iso2);
                    $btn.text(code);
                    if (hintTarget) {
                        $(hintTarget).html('Click <strong>' + _esc(code) + '</strong> to change country');
                    }
                }
            });
        });

        // ESC key closes the overlay
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape' && _$wrap && _$wrap.is(':visible')) {
                _close();
            }
        });

    return { open: open };

}(jQuery));
