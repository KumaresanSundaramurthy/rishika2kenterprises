'use strict';

/**
 * ItemFilter — product item filter panel, mirrors CategoryAppend pattern.
 *
 * Uses ProductAppend (Upstash orgKey('products') → AJAX fallback) to load the
 * org's product list. Only items with productType === 'Product' are shown.
 *
 * Public API:
 *   ItemFilter.filterBox(boxSel, cfg, selectedUids)
 *     — renders the floating filter panel into an empty div.
 *       On subsequent calls the list is already rendered; only selections sync.
 *       cfg: { checkClass, applyFn, resetFn, uid }
 */
window.ItemFilter = (function () {

    var _cache = null;

    // ── Load via ProductAppend (cached after first call) ──────────────────────

    function _load(onSuccess, onFail) {
        if (_cache !== null) { onSuccess(_cache); return; }
        ProductAppend.load(function (list) {
            _cache = (list || [])
                .filter(function (p) { return p.productType === 'Product'; })
                .sort(function (a, b) {
                    return (a.itemName || a.text || '').localeCompare(b.itemName || b.text || '');
                });
            onSuccess(_cache);
        }, onFail || function () {});
    }

    // ── Render panel HTML into $box ───────────────────────────────────────────

    function _render($box, products, cfg, selectedUids) {
        var checkCls = cfg.checkClass || 'if-check';
        var uid      = cfg.uid || ($box.attr('id') || 'if');
        selectedUids = (selectedUids || []).map(String);

        if (!products.length) {
            $box.html(
                '<div class="d-flex flex-column align-items-center justify-content-center py-4 text-muted">' +
                '<i class="bx bx-info-circle fs-2 mb-2"></i>' +
                '<span style="font-size:.8rem;">No items found</span></div>'
            );
            return;
        }

        var boxId    = '#' + $box.attr('id');
        var listHtml = '';
        products.forEach(function (p) {
            var val  = String(p.id);
            var name = p.itemName || p.text || '';
            var chk  = selectedUids.indexOf(val) !== -1 ? ' checked' : '';
            listHtml +=
                '<label class="catg-list-item">' +
                '<input class="form-check-input if-check ' + checkCls + '" type="checkbox" value="' + val + '"' + chk + '>' +
                '<span>' + $('<span>').text(name).html() + '</span>' +
                '</label>';
        });

        var allChecked = selectedUids.length > 0 && selectedUids.length === products.length;

        $box.html(
            '<div class="catg-filter-header">' +
                '<span class="catg-filter-title"><i class="bx bx-package me-1"></i>Item Filter</span>' +
                '<div class="d-flex align-items-center gap-2">' +
                    '<span class="badge">' + products.length + '</span>' +
                    '<button type="button" class="catg-filter-close-btn if-close-btn" data-box="' + boxId + '" title="Close">&times;</button>' +
                '</div>' +
            '</div>' +
            '<div class="catg-filter-search-wrap">' +
                '<div class="input-group input-group-sm">' +
                    '<span class="input-group-text"><i class="bx bx-search"></i></span>' +
                    '<input type="text" class="form-control if-search" data-box="' + boxId + '" placeholder="Search items...">' +
                '</div>' +
            '</div>' +
            '<div class="catg-select-all-wrap">' +
                '<input type="checkbox" class="form-check-input if-sel-all" id="ifSelAll_' + uid + '" ' +
                    'data-box="' + boxId + '" data-check-class="' + checkCls + '"' + (allChecked ? ' checked' : '') + '>' +
                '<label class="small fw-semibold mb-0 if-sel-all-label" for="ifSelAll_' + uid + '">' +
                    (allChecked ? 'Clear All' : 'Select All') + '</label>' +
            '</div>' +
            '<div class="catg-list if-list" style="max-height:200px;">' + listHtml + '</div>' +
            '<div class="catg-filter-footer">' +
                '<button type="button" class="btn btn-primary" onclick="' + cfg.applyFn + '()"><i class="bx bx-check me-1"></i>Apply</button>' +
                '<button type="button" class="btn btn-outline-secondary" onclick="' + cfg.resetFn + '()"><i class="bx bx-reset me-1"></i>Reset</button>' +
            '</div>'
        );
    }

    // ── Public: filterBox ─────────────────────────────────────────────────────

    function filterBox(boxSel, cfg, selectedUids) {
        var $box = $(boxSel);

        // Already rendered — just sync checkbox ticks to current selection
        if ($box.find('.if-check').length) {
            var sel = (selectedUids || []).map(String);
            $box.find('.if-check').each(function () {
                $(this).prop('checked', sel.indexOf($(this).val()) !== -1);
            });
            return;
        }

        $box.html(
            '<div class="d-flex justify-content-center align-items-center p-3">' +
            '<div class="spinner-border spinner-border-sm text-primary" role="status"></div></div>'
        );

        _load(
            function (products) { _render($box, products, cfg, selectedUids); },
            function ()         { $box.html('<div class="p-3 text-center text-danger small">Failed to load items.</div>'); }
        );
    }

    // ── Delegated events (active on every page that loads this file) ──────────

    $(document).on('input', '.if-search', function () {
        var term = $(this).val().toLowerCase();
        $($(this).data('box')).find('.catg-list-item').each(function () {
            $(this).toggle($(this).find('span').text().toLowerCase().indexOf(term) !== -1);
        });
    });

    $(document).on('change', '.if-sel-all', function () {
        var checked = $(this).is(':checked');
        var $box    = $($(this).data('box'));
        var cls     = $(this).data('check-class') || 'if-check';
        $box.find('.' + cls).prop('checked', checked);
        $(this).siblings('.if-sel-all-label').text(checked ? 'Clear All' : 'Select All');
    });

    $(document).on('click', '.if-close-btn', function () {
        $($(this).data('box')).hide();
    });

    return { filterBox: filterBox };

}());
