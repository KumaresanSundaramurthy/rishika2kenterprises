/**
 * PhoneCCDropdown — reusable country-code picker for phone input fields.
 *
 * Usage:
 *   var cfg = {
 *     btn      : '#CG_MobileCCBtn',
 *     dropdown : '#CG_CCDropdown',
 *     search   : '#CG_CCSearch',
 *     list     : '#CG_CCList',
 *     codeInput: '#CG_MobileCountryCode',
 *     iso2Input: '#CG_CountryISO2',
 *   };
 *   PhoneCCDropdown.init(cfg);               // once, at module load
 *   PhoneCCDropdown.setFromISO2(cfg, iso2);  // on form open / populate
 */
(function (window, $) {
    'use strict';

    var _cache = null; // shared countries cache across all instances

    window.PhoneCCDropdown = {
        /**
         * Bind all events for a CC dropdown instance. Call once at module load.
         * @param {Object} cfg
         * @returns {void}
         */
        init: function (cfg) {
            $(document).on('click', cfg.btn, function (e) {
                e.stopPropagation();
                var open = $(cfg.dropdown).is(':visible');
                $(cfg.dropdown).toggle(!open);
                if (!open) {
                    $(cfg.search).val('').focus();
                    _renderList(cfg, '');
                }
            });

            $(document).on('input', cfg.search, function () {
                _renderList(cfg, $(this).val());
            });

            $(document).on('click', cfg.list + ' .r2k-cc-item', function () {
                _select(cfg, $(this).data('iso2'), $(this).data('code'));
            });

            $(document).on('click', function (e) {
                if (!$(e.target).closest(cfg.btn + ',' + cfg.dropdown).length) {
                    $(cfg.dropdown).hide();
                }
            });
        },

        /**
         * Set button text and hidden input from an ISO2 country code.
         * @param {Object} cfg
         * @param {string} iso2
         * @returns {void}
         */
        setFromISO2: function (cfg, iso2) {
            _load(function (countries) {
                var found = countries.find(function (c) {
                    return (c.iso2 || '').toUpperCase() === iso2.toUpperCase();
                });
                var code = found ? '+' + String(found.phonecode) : ($(cfg.codeInput).val() || '+91');
                $(cfg.btn).text(code);
                $(cfg.codeInput).val(code);
            });
        },
    };

    /**
     * @param {Object} cfg
     * @param {string} iso2
     * @param {string} code
     * @returns {void}
     */
    function _select(cfg, iso2, code) {
        $(cfg.btn).text(code);
        $(cfg.codeInput).val(code);
        $(cfg.iso2Input).val(iso2);
        $(cfg.dropdown).hide();
    }

    /**
     * @param {Object} cfg
     * @param {string} query
     * @returns {void}
     */
    function _renderList(cfg, query) {
        _load(function (countries) {
            var q        = $.trim(query).toLowerCase();
            var filtered = q
                ? countries.filter(function (c) {
                    return (c.name || '').toLowerCase().indexOf(q) >= 0
                        || String(c.phonecode || '').indexOf(q) >= 0;
                })
                : countries;

            var html = filtered.map(function (c) {
                return '<div class="r2k-cc-item" data-iso2="' + _esc(c.iso2) +
                    '" data-code="+' + _esc(String(c.phonecode)) + '">' +
                    _esc(c.name) + ' <span class="text-muted">(+' + _esc(String(c.phonecode)) + ')</span>' +
                    '</div>';
            }).join('');

            $(cfg.list).html(html || '<div class="r2k-cc-no-results">No results</div>');
        });
    }

    /**
     * @param {function} cb
     * @returns {void}
     */
    function _load(cb) {
        if (_cache) { cb(_cache); return; }
        if (!UpstashService.isEnabled()) { _fetchAjax(cb); return; }
        UpstashService.get(UpstashService.globalKey('loc-countries')).then(function (data) {
            if (Array.isArray(data) && data.length) { _cache = data; cb(data); }
            else { _fetchAjax(cb); }
        }).catch(function () { _fetchAjax(cb); });
    }

    /**
     * @param {function} cb
     * @returns {void}
     */
    function _fetchAjax(cb) {
        $.ajax({
            url: '/globally/getCountryInfo', dataType: 'json',
            success: function (res) { _cache = (res && res.Data) ? res.Data : []; cb(_cache); },
            error:   function ()    { _cache = []; cb([]); }
        });
    }

    /**
     * @param {*} s
     * @returns {string}
     */
    function _esc(s) {
        return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

}(window, jQuery));
