/**
 * DropdownCache — product-form dropdown data manager.
 *
 * Implements the decided pattern (see memory: product_add_form_memory.md):
 *
 *   Case A (all 6 keys found in Upstash):
 *     Populate all dropdowns → then show modal. User sees a fully-loaded form instantly.
 *
 *   Case B (some or none found in Upstash):
 *     Show modal immediately → populate whatever was found → fire ONE server call
 *     for the missing fields only → when response arrives, fill the blanks.
 *
 *   Subsequent opens (JS in-memory cache warm):
 *     No network call at all. Populate + show in the same tick.
 *
 * Public API:
 *   DropdownCache.openForProductForm({ onReady, onPartial, onMissingReady })
 *   DropdownCache.populateProductModal(data)
 *   DropdownCache.ready()   ← legacy, resolves when ALL data available
 *   DropdownCache.init()    ← pre-warm in background (optional)
 */
window.DropdownCache = (function ($) {
    'use strict';

    var _data    = null;   // complete in-memory cache — null means cold
    var _promise = null;   // set once all data is complete; used by legacy ready()

    // Upstash key map — mirrors PHP getDropdownCache()
    var _keyMap = {
        primaryUnit : 'primary-unit',
        discType    : 'disc-type',
        prodType    : 'prod-type',
        prodTax     : 'prod-tax',
        taxDetails  : 'tax-details',
        categories  : 'categories',   // Redis HASH — HGETALL
    };
    var _fields = Object.keys(_keyMap);

    // ── Step 1: pipeline-check all 6 keys in one Upstash HTTP call ───────────

    function _checkUpstash() {
        if (!UpstashService.isEnabled()) {
            return Promise.resolve({ found: {}, missing: _fields.slice() });
        }

        // 'categories' is a Redis hash → HGETALL; all others are strings → GET
        var cmds = _fields.map(function (f) {
            return f === 'categories'
                ? ['HGETALL', UpstashService.orgKey(_keyMap[f])]
                : ['GET',     UpstashService.orgKey(_keyMap[f])];
        });

        return UpstashService.pipeline(cmds).then(function (results) {
            var found   = {};
            var missing = [];

            _fields.forEach(function (field, i) {
                var raw = results[i];

                if (field === 'categories') {
                    // HGETALL → flat [uid, jsonStr, uid, jsonStr, ...] array
                    if (Array.isArray(raw) && raw.length >= 2) {
                        var cats = [];
                        for (var j = 0; j + 1 < raw.length; j += 2) {
                            try {
                                var val = raw[j + 1];
                                cats.push(typeof val === 'string' ? JSON.parse(val) : val);
                            } catch (e) {}
                        }
                        if (cats.length > 0) { found[field] = cats; return; }
                    }
                    missing.push(field);
                    return;
                }

                if (raw !== null && raw !== undefined) {
                    try {
                        var parsed = typeof raw === 'string' ? JSON.parse(raw) : raw;
                        if (Array.isArray(parsed) && parsed.length > 0) {
                            found[field] = parsed;
                            return;
                        }
                    } catch (e) {}
                }
                missing.push(field);
            });

            return { found: found, missing: missing };
        }).catch(function () {
            return { found: {}, missing: _fields.slice() };
        });
    }

    // ── Step 2 (Case B only): fetch missing fields from server ────────────────
    // Server does DB query → writes each missing field back to Upstash → returns data.
    // Endpoint accepts fields[] POST param so only the missing fields are processed.

    function _fetchMissing(missingFields) {
        return new Promise(function (resolve) {
            if (!missingFields.length) { resolve({}); return; }

            var postData = {};
            postData[CsrfName] = CsrfToken;
            missingFields.forEach(function (f, i) {
                postData['fields[' + i + ']'] = f;
            });

            $.ajax({
                url      : '/products/getDropdownCache',
                type     : 'POST',
                data     : postData,
                dataType : 'json',
                cache    : false,
                success  : function (res) {
                    if (res && res.NewCsrfToken) CsrfToken = res.NewCsrfToken;
                    resolve((res && !res.Error && res.Data) ? res.Data : {});
                },
                error: function () { resolve({}); }
            });
        });
    }

    // ── Page-level selects (transaction page extras — ex-discount, tax) ───────

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

    // ── Product modal selects ─────────────────────────────────────────────────
    // Each block guards with a "no options yet" check so calling this twice
    // (once with partial data, once with the rest) is safe — found ones are skipped.

    function populateProductModal(data) {
        if (!data) return;

        // ProductType
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

        // TaxPercentage (Select2 with custom template)
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

        _resolveDefaults(data);
    }

    function _resolveDefaults(data) {
        var defTypeUID = (typeof _pfDefProdTypeUID !== 'undefined') ? parseInt(_pfDefProdTypeUID) : 0;
        var defDiscUID = (typeof _pfDefDiscTypeUID !== 'undefined') ? parseInt(_pfDefDiscTypeUID) : 0;
        var defTaxUID  = (typeof _pfDefProdTaxUID  !== 'undefined') ? parseInt(_pfDefProdTaxUID)  : 0;

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

        if (!defDiscUID || defDiscUID === 0) {
            $.each(data.discType || [], function (_, d) {
                if (d.Name && d.Name.toLowerCase().indexOf('percentage') !== -1) {
                    window._pfDefDiscTypeUID = parseInt(d.DiscountTypeUID); return false;
                }
            });
        }

        if (!defTaxUID || defTaxUID === 0) {
            $.each(data.prodTax || [], function (_, t) {
                if (t.Name && t.Name.toLowerCase().indexOf('with tax') !== -1) {
                    window._pfDefProdTaxUID = parseInt(t.ProductTaxUID); return false;
                }
            });
        }
    }

    // ── openForProductForm — the decided pattern ──────────────────────────────

    function openForProductForm(callbacks) {
        callbacks = callbacks || {};
        var onReady        = callbacks.onReady        || function () {};
        var onPartial      = callbacks.onPartial      || function () {};
        var onMissingReady = callbacks.onMissingReady || function () {};

        // JS cache warm — no network call at all
        if (_data) {
            onReady(_data);
            return;
        }

        _checkUpstash().then(function (result) {

            if (result.missing.length === 0) {
                // ── Case A: all 6 found — populate first, then open modal ──
                _data    = result.found;
                _promise = Promise.resolve(_data);
                _populatePageSelects(_data);
                onReady(_data);

            } else {
                // ── Case B: some/none found — open modal now ───────────────
                // Populate whatever was found immediately (guards in populateProductModal
                // ensure already-populated fields are never overwritten on the 2nd call).
                _populatePageSelects(result.found);
                onPartial(result.found);

                // Fetch ONLY the missing fields from the server in one batch call.
                _fetchMissing(result.missing).then(function (missingData) {
                    var complete = $.extend({}, result.found, missingData);
                    _data    = complete;
                    _promise = Promise.resolve(_data);
                    _populatePageSelects(_data);
                    onMissingReady(_data);
                });
            }
        });
    }

    // ── ready() — legacy API, resolves only when ALL data is complete ─────────

    function ready() {
        if (_promise) return _promise;
        _promise = new Promise(function (resolve) {
            if (_data) { resolve(_data); return; }
            _checkUpstash().then(function (result) {
                if (result.missing.length === 0) {
                    _data = result.found;
                    _populatePageSelects(_data);
                    resolve(_data);
                } else {
                    _populatePageSelects(result.found);
                    _fetchMissing(result.missing).then(function (missingData) {
                        _data = $.extend({}, result.found, missingData);
                        _populatePageSelects(_data);
                        resolve(_data);
                    });
                }
            });
        });
        return _promise;
    }

    function init() {
        if (!_promise) ready();
    }

    function _esc(str) {
        return $('<span>').text(str || '').html();
    }

    // ── patchCategories — keep _data.categories in sync after CRUD ────────────
    // Called by updateCategoryOptions so the in-memory cache stays consistent
    // without requiring a full re-fetch.
    function patchCategories(action, fields) {
        if (!_data || !Array.isArray(_data.categories)) return;
        if (action === 'insert') {
            _data.categories.push({ CategoryUID: parseInt(fields.InsertId), Name: fields.CategoryName });
        } else if (action === 'update') {
            var uid = String(fields.UpdateId).trim();
            _data.categories.forEach(function (c) {
                if (String(c.CategoryUID) === uid) { c.Name = fields.CategoryName; }
            });
        } else if (action === 'delete') {
            var ids = (Array.isArray(fields.UpdateId) ? fields.UpdateId : [fields.UpdateId]).map(String);
            _data.categories = _data.categories.filter(function (c) {
                return ids.indexOf(String(c.CategoryUID)) === -1;
            });
        }
    }

    return { init: init, ready: ready, openForProductForm: openForProductForm, populateProductModal: populateProductModal, patchCategories: patchCategories };

}(jQuery));
