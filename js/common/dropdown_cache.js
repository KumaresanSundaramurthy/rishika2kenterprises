/**
 * DropdownCache — lazy-loads product-form dropdown data after page render.
 *
 * On page ready: fires a single background AJAX GET to /products/getDropdownCache
 * and immediately populates extra-discount and additional-charges tax selects.
 *
 * Before the product modal opens: call DropdownCache.ready().then(fn) to ensure
 * all modal selects are populated.
 */
window.DropdownCache = (function ($) {
    'use strict';

    var _data    = null;
    var _promise = null;

    // ── Upstash key map (mirrors PHP getDropdownCache) ────────────────────────

    var _keyMap = {
        primaryUnit : 'primary-unit',
        discType    : 'disc-type',
        prodType    : 'prod-type',
        prodTax     : 'prod-tax',
        taxDetails  : 'tax-details',
        categories  : 'org-categories',
    };
    var _fields = Object.keys(_keyMap);

    // ── Tier 2: AJAX fallback (server fetches missing from DB → writes Upstash) ─

    function _fetchFromServer(resolve) {
        $.ajax({
            url      : '/products/getDropdownCache',
            type     : 'GET',
            dataType : 'json',
            cache    : false,
            success  : function (res) {
                if (res && !res.Error && res.Data) {
                    _data = res.Data;
                    _populatePageSelects(_data);
                    resolve(_data);
                } else {
                    resolve(null);
                }
            },
            error: function () { resolve(null); }
        });
    }

    // ── Tier 1: pipeline-read all keys from Upstash in one HTTP call ──────────

    function _fetch() {
        if (_promise) return _promise;

        _promise = new Promise(function (resolve) {

            if (!UpstashService.isEnabled()) {
                _fetchFromServer(resolve);
                return;
            }

            var cmds = _fields.map(function (f) {
                return ['GET', UpstashService.orgKey(_keyMap[f])];
            });

            UpstashService.pipeline(cmds).then(function (results) {
                var data   = {};
                var allHit = true;

                _fields.forEach(function (field, i) {
                    var raw = results[i];
                    if (raw !== null && raw !== undefined) {
                        try   { data[field] = JSON.parse(raw); }
                        catch { data[field] = raw; }
                        if (!Array.isArray(data[field])) data[field] = [];
                    } else {
                        allHit = false;
                    }
                });

                if (allHit) {
                    _data = data;
                    _populatePageSelects(_data);
                    resolve(_data);
                } else {
                    // One or more keys are cold — server will DB-fetch missing
                    // fields and write them back to Upstash automatically
                    _fetchFromServer(resolve);
                }
            }).catch(function () {
                _fetchFromServer(resolve);
            });
        });

        return _promise;
    }

    // ── Page-level selects (visible before modal opens) ───────────────────────

    function _populatePageSelects(data) {
        _populateExtDiscountType(data.discType || []);
        _populateAdditionalChargesTax(data.taxDetails || []);
    }

    function _populateExtDiscountType(discTypes) {
        var $sel = $('#extDiscountType');
        if (!$sel.length || $sel.children('option').length) return;
        var html = '';
        $.each(discTypes, function (_, d) {
            html += '<option value="' + _esc(d.Name) + '">' + _esc(d.Symbol) + '</option>';
        });
        $sel.html(html);
    }

    function _populateAdditionalChargesTax(taxDetails) {
        $.each(['#shippingCharges', '#packingCharges'], function (_, sel) {
            var $el = $(sel);
            if (!$el.length || $el.children('option').length) return;
            var html = '';
            $.each(taxDetails, function (_, t) {
                var pct      = parseFloat(t.Percentage) || 0;
                var selected = pct === 0 ? ' selected' : '';
                html += '<option value="' + t.TaxDetailsUID + '" data-percent="' + pct + '"' + selected + '>' + pct + '</option>';
            });
            $el.html(html);
        });
    }

    // ── Product modal selects (called before modal shows) ─────────────────────

    function populateProductModal(data) {
        if (!data) return;

        // ProductType — value is the name string, not UID
        var $pt = $('#ProductType');
        if ($pt.length && !$pt.find('option').length) {
            var html = '';
            $.each(data.prodType || [], function (_, t) {
                html += '<option value="' + _esc(t.Name) + '">' + _esc(t.Name) + '</option>';
            });
            $pt.html(html);
        }

        // SellingTaxOption + PurchaseTaxOption
        $.each(['#SellingTaxOption', '#PurchaseTaxOption'], function (_, sel) {
            var $s = $(sel);
            if ($s.length && !$s.find('option').length) {
                var html = '';
                $.each(data.prodTax || [], function (_, t) {
                    html += '<option value="' + t.ProductTaxUID + '">' + _esc(t.Name) + '</option>';
                });
                $s.html(html);
            }
        });

        // TaxPercentage — value is TaxDetailsUID, data-left/right for template
        var $tax = $('#TaxPercentage');
        if ($tax.length && $tax.find('option[value!=""]').length === 0) {
            var html = '<option value=""></option>';
            $.each(data.taxDetails || [], function (_, t) {
                var pct = parseFloat(t.Percentage) || 0;
                html += '<option value="' + t.TaxDetailsUID
                      + '" data-left="' + pct
                      + '" data-right="' + _esc(t.TaxName) + '">'
                      + _esc(t.TaxName) + '</option>';
            });
            $tax.html(html);
            if ($tax.hasClass('select2-hidden-accessible')) $tax.trigger('change');
        }

        // PrimaryUnit
        var $pu = $('#PrimaryUnit');
        if ($pu.length && $pu.find('option[value!=""]').length === 0) {
            var html = '<option value=""></option>';
            $.each(data.primaryUnit || [], function (_, u) {
                html += '<option value="' + u.PrimaryUnitUID + '">'
                      + _esc(u.Name) + ' (' + _esc(u.ShortName) + ')</option>';
            });
            $pu.html(html);
        }

        // Category
        var $cat = $('#Category');
        if ($cat.length && $cat.find('option[value!=""]').length === 0) {
            var cats = (data.categories || []).slice().sort(function (a, b) {
                return (a.Name || '').localeCompare(b.Name || '');
            });
            var html = '<option value=""></option>';
            $.each(cats, function (_, c) {
                html += '<option value="' + parseInt(c.CategoryUID) + '">' + _esc(c.Name) + '</option>';
            });
            $cat.html(html);
            if ($cat.hasClass('select2-hidden-accessible')) $cat.trigger('change');
        }

        // ComboTaxPercentage (combo modal — same data as TaxPercentage)
        var $ctax = $('#ComboTaxPercentage');
        if ($ctax.length && $ctax.find('option[value!=""]').length === 0) {
            var html = '<option value=""></option>';
            $.each(data.taxDetails || [], function (_, t) {
                var pct = parseFloat(t.Percentage) || 0;
                html += '<option value="' + t.TaxDetailsUID
                      + '" data-left="' + pct
                      + '" data-right="' + _esc(t.TaxName) + '">'
                      + _esc(t.TaxName) + '</option>';
            });
            $ctax.html(html);
            if ($ctax.hasClass('select2-hidden-accessible')) $ctax.trigger('change');
        }

        // ComboPrimaryUnit (combo modal — same data as PrimaryUnit)
        var $cpu = $('#ComboPrimaryUnit');
        if ($cpu.length && $cpu.find('option[value!=""]').length === 0) {
            var html = '<option value=""></option>';
            $.each(data.primaryUnit || [], function (_, u) {
                html += '<option value="' + u.PrimaryUnitUID + '">'
                      + _esc(u.Name) + ' (' + _esc(u.ShortName) + ')</option>';
            });
            $cpu.html(html);
            if ($cpu.hasClass('select2-hidden-accessible')) $cpu.trigger('change');
        }

        // DiscountOption
        var $do = $('#DiscountOption');
        if ($do.length && !$do.find('option').length) {
            var html = '';
            $.each(data.discType || [], function (_, d) {
                html += '<option value="' + d.DiscountTypeUID + '">' + _esc(d.DisplayName) + '</option>';
            });
            $do.html(html);
        }

        // Resolve defaults that required data lookups
        _resolveDefaults(data);
    }

    // Set _pfDefProductType (name string) from UID, and fill fallback UIDs when 0
    function _resolveDefaults(data) {
        var defTypeUID = (typeof _pfDefProdTypeUID !== 'undefined') ? parseInt(_pfDefProdTypeUID) : 0;
        var defDiscUID = (typeof _pfDefDiscTypeUID !== 'undefined') ? parseInt(_pfDefDiscTypeUID) : 0;
        var defTaxUID  = (typeof _pfDefProdTaxUID  !== 'undefined') ? parseInt(_pfDefProdTaxUID)  : 0;

        // Product type name from UID
        if (!window._pfDefProductType || window._pfDefProductType === '') {
            var found = '';
            $.each(data.prodType || [], function (_, t) {
                if (defTypeUID > 0 && parseInt(t.ProductTypeUID) === defTypeUID) { found = t.Name; return false; }
            });
            if (!found) {
                $.each(data.prodType || [], function (_, t) { if (t.Name === 'Product') { found = 'Product'; return false; } });
            }
            if (!found && data.prodType && data.prodType.length) found = data.prodType[0].Name;
            window._pfDefProductType = found || 'Product';
        }

        // Fallback discount UID to first "Percentage" type
        if (!defDiscUID || defDiscUID === 0) {
            $.each(data.discType || [], function (_, d) {
                if (d.Name && d.Name.toLowerCase().indexOf('percentage') !== -1) {
                    window._pfDefDiscTypeUID = parseInt(d.DiscountTypeUID); return false;
                }
            });
        }

        // Fallback prod-tax UID to first "With Tax" type
        if (!defTaxUID || defTaxUID === 0) {
            $.each(data.prodTax || [], function (_, t) {
                if (t.Name && t.Name.toLowerCase().indexOf('with tax') !== -1) {
                    window._pfDefProdTaxUID = parseInt(t.ProductTaxUID); return false;
                }
            });
        }
    }

    // ── Public API ────────────────────────────────────────────────────────────

    function init() {
        _fetch();
    }

    function ready() {
        if (!_promise) init();
        return _promise;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    function _esc(str) {
        return $('<span>').text(str || '').html();
    }

    return { init: init, ready: ready, populateProductModal: populateProductModal };

}(jQuery));
