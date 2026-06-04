'use strict';

/**
 * CategoryFilterBox — shared category filter module used across all pages.
 *
 * Each page keeps an EMPTY div in the HTML. On first open, this module
 * fetches from Upstash cache → falls back to /products/getCategoryOptions/,
 * then injects the complete filter box HTML including header, search,
 * select-all, list and footer buttons.
 *
 * Usage:
 *   CategoryFilterBox.open('#categoryFilterBox', {
 *       checkClass : 'category-checkbox',
 *       applyFn    : 'applyCategoryFilter',
 *       resetFn    : 'resetCategoryFilter',
 *       uid        : 'products'        // short unique string for generated IDs
 *   }, selectedUids);
 */
window.CategoryFilterBox = (function () {

    var _cache = null;

    // ── Cache ─────────────────────────────────────────────────────────────────

    function _buildFromCatgMap(map) {
        var keys = Object.keys(map || {});
        if (!keys.length) return null;
        return keys.map(function (uid) {
            return { uid: parseInt(uid, 10), name: map[uid].Name || '' };
        }).sort(function (a, b) { return a.name.localeCompare(b.name); });
    }

    function _buildFromProdMap(prodMap) {
        var catMap = {};
        Object.keys(prodMap || {}).forEach(function (uid) {
            var p = prodMap[uid];
            if (p.CategoryUID && p.CategoryName) {
                catMap[String(p.CategoryUID)] = p.CategoryName;
            }
        });
        var keys = Object.keys(catMap);
        if (!keys.length) return null;
        return keys.map(function (uid) {
            return { uid: parseInt(uid, 10), name: catMap[uid] };
        }).sort(function (a, b) { return a.name.localeCompare(b.name); });
    }

    function fetchCache(onSuccess, onMiss) {
        if (_cache !== null) { onSuccess(_cache); return; }
        if (!UpstashService.isEnabled()) { onMiss(); return; }

        // Tier 1: dedicated categories cache
        UpstashService.hgetall(UpstashService.orgKey('categories')).then(function (map) {
            var built = (map && typeof map === 'object' && !Array.isArray(map))
                ? _buildFromCatgMap(map) : null;
            if (built) { _cache = built; onSuccess(_cache); return; }

            // Tier 2: derive unique categories from the products cache
            UpstashService.hgetall(UpstashService.orgKey('products')).then(function (prodMap) {
                var built2 = (prodMap && typeof prodMap === 'object')
                    ? _buildFromProdMap(prodMap) : null;
                if (built2) { _cache = built2; onSuccess(_cache); return; }
                onMiss();
            }).catch(function () { onMiss(); });

        }).catch(function () { onMiss(); });
    }

    function _loadFromServer(boxSel, cfg, selectedUids) {
        $.ajax({
            url   : '/products/getCategoryOptions/',
            method: 'POST',
            cache : false,
            data  : { [CsrfName]: CsrfToken },
            success: function (resp) {
                if (!resp.Error && resp.Options && resp.Options.length) {
                    _cache = resp.Options.map(function (o) {
                        return { uid: o.uid, name: o.name };
                    }).sort(function (a, b) { return a.name.localeCompare(b.name); });
                    _render(boxSel, cfg, selectedUids);
                } else {
                    $(boxSel).html('<div class="p-3 text-center text-muted small">No categories found.</div>');
                }
            },
            error: function () {
                $(boxSel).html('<div class="p-3 text-center text-danger small">Failed to load categories.</div>');
            }
        });
    }

    // ── Render ────────────────────────────────────────────────────────────────

    function _render(boxSel, cfg, selectedUids) {
        selectedUids = (selectedUids || []).map(String);
        var $box      = $(boxSel);
        var checkCls  = cfg.checkClass || 'cfb-check';
        var uid       = cfg.uid || boxSel.replace(/[^a-z0-9]/gi, '_');

        if (!_cache || !_cache.length) {
            $box.html(
                '<div class="d-flex flex-column align-items-center justify-content-center py-4 text-muted">' +
                '<i class="bx bx-info-circle fs-2 mb-2"></i>' +
                '<span style="font-size:.8rem;">No categories found</span></div>'
            );
            return;
        }

        var listHtml = '';
        _cache.forEach(function (c) {
            var chk = selectedUids.indexOf(String(c.uid)) !== -1 ? ' checked' : '';
            listHtml +=
                '<label class="catg-list-item">' +
                '<input class="form-check-input cfb-check ' + checkCls + '" type="checkbox" value="' + c.uid + '"' + chk + '>' +
                '<span>' + $('<span>').text(c.name).html() + '</span>' +
                '</label>';
        });

        var allChecked = selectedUids.length > 0 && selectedUids.length === _cache.length;

        $box.html(
            '<div class="catg-filter-header">' +
                '<span class="catg-filter-title"><i class="bx bx-layer me-1"></i> Category Filter</span>' +
                '<div class="d-flex align-items-center gap-2">' +
                    '<span class="badge">' + _cache.length + '</span>' +
                    '<button type="button" class="catg-filter-close-btn cfb-close-btn" data-box="' + boxSel + '" title="Close">&times;</button>' +
                '</div>' +
            '</div>' +
            '<div class="catg-filter-search-wrap">' +
                '<div class="input-group input-group-sm">' +
                    '<span class="input-group-text"><i class="bx bx-search"></i></span>' +
                    '<input type="text" class="form-control cfb-search" data-box="' + boxSel + '" placeholder="Search categories...">' +
                '</div>' +
            '</div>' +
            '<div class="catg-select-all-wrap">' +
                '<input type="checkbox" class="form-check-input cfb-sel-all" id="cfbSelAll_' + uid + '" ' +
                    'data-box="' + boxSel + '" data-check-class="' + checkCls + '"' + (allChecked ? ' checked' : '') + '>' +
                '<label class="small fw-semibold mb-0 cfb-sel-all-label" for="cfbSelAll_' + uid + '">' +
                    (allChecked ? 'Clear All' : 'Select All') +
                '</label>' +
            '</div>' +
            '<div class="catg-list cfb-list" style="max-height:180px;">' + listHtml + '</div>' +
            '<div class="catg-filter-footer">' +
                '<button type="button" class="btn btn-primary" onclick="' + cfg.applyFn + '()"><i class="bx bx-check me-1"></i>Apply</button>' +
                '<button type="button" class="btn btn-outline-secondary" onclick="' + cfg.resetFn + '()"><i class="bx bx-reset me-1"></i>Reset</button>' +
            '</div>'
        );
    }

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Open the filter box. Renders from cache on first open; subsequent opens
     * just show the already-rendered box (no re-fetch).
     */
    function open(boxSel, cfg, selectedUids) {
        var $box = $(boxSel);
        if ($box.find('.cfb-check').length > 0) return;
        $box.html(
            '<div class="d-flex justify-content-center align-items-center p-3">' +
            '<div class="spinner-border spinner-border-sm text-primary" role="status"></div></div>'
        );
        fetchCache(
            function () { _render(boxSel, cfg, selectedUids); },
            function () { _loadFromServer(boxSel, cfg, selectedUids); }
        );
    }

    /** Clear cache + empty the box div so next open re-fetches. */
    function invalidate(boxSel) {
        _cache = null;
        if (boxSel) $(boxSel).empty();
    }

    function getCache()        { return _cache; }
    function setCache(catgs)   { _cache = catgs; }

    // ── Delegated event handlers (work for every page automatically) ──────────

    $(document).on('input', '.cfb-search', function () {
        var term = $(this).val().toLowerCase();
        $($(this).data('box')).find('.catg-list-item').each(function () {
            $(this).toggle($(this).find('span').text().toLowerCase().indexOf(term) !== -1);
        });
    });

    $(document).on('change', '.cfb-sel-all', function () {
        var checked  = $(this).is(':checked');
        var $box     = $($(this).data('box'));
        var cls      = $(this).data('check-class') || 'cfb-check';
        $box.find('.' + cls).prop('checked', checked);
        $(this).siblings('.cfb-sel-all-label').text(checked ? 'Clear All' : 'Select All');
    });

    $(document).on('click', '.cfb-close-btn', function () {
        $($(this).data('box')).hide();
    });

    return { open, invalidate, getCache, setCache, fetchCache };

}());
