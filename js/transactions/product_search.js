/**
 * Product Search Modal — Transaction pages only.
 *
 * Data strategy:
 *   Delegates entirely to ProductAppend.load() which chains:
 *     Upstash orgKey('products') HGETALL → AJAX /transactions/searchTransProducts
 *   Result cached in _cacheData; subsequent modal opens are zero-network.
 *
 *   Composite items are automatically excluded when window._productPurchaseMode = true
 *   (set by Purchase / Purchase Orders / Purchase Returns pages).
 *
 * On row click: calls pushBillItems(productData, 1) to add qty=1 straight to cart.
 *   If the item is already in the cart, pushBillItems returns false and the modal
 *   stays open so the user can pick a different product.
 */
(function ($) {
    'use strict';

    var _limit    = 20;
    var _page     = 1;
    var _term     = '';
    var _loading  = false;
    var _hasMore  = false;
    var _observer = null;

    // ── Cache (persists across opens within the same page session) ────────────
    var _cacheAttempted = false;
    var _cacheData      = null; // null = not yet loaded; Array = loaded product list

    // ── Open ──────────────────────────────────────────────────────────────────
    $(document).on('click', '#openProdSearchModal', function () {
        if (typeof pushBillItems === 'undefined') return; // not a transaction form page
        _page = 1; _term = ''; _loading = false; _hasMore = false;
        $('#prodSearchInput').val('');
        $('#prodSearchClear').addClass('d-none');
        _resetBody();
        $('#productSearchModal').modal('show');
    });

    $('#productSearchModal').on('shown.bs.modal', function () {
        _setupObserver();
        $('#prodSearchInput').trigger('focus');
        _load(false);
    });

    $('#productSearchModal').on('hidden.bs.modal', function () {
        _destroyObserver();
    });

    // ── Search (debounced 380 ms) ─────────────────────────────────────────────
    var _searchTimer;
    $(document).on('input', '#prodSearchInput', function () {
        clearTimeout(_searchTimer);
        _term = $.trim($(this).val());
        $('#prodSearchClear').toggleClass('d-none', _term === '');
        _searchTimer = setTimeout(function () {
            _page = 1; _hasMore = false; _loading = false;
            _resetBody();
            _load(false);
        }, 380);
    });

    $(document).on('click', '#prodSearchClear', function () {
        $('#prodSearchInput').val('').trigger('input');
    });

    // ── Row click → add to cart (qty = 1) ────────────────────────────────────
    $(document).on('click', '.prod-search-row', function () {
        var allfields = $(this).data('allfields');
        if (!allfields) return;
        // jQuery auto-parses JSON data attributes; guard in case it stayed a string
        if (typeof allfields === 'string') {
            try { allfields = JSON.parse(allfields); } catch (e) { return; }
        }
        if (typeof pushBillItems !== 'function') return;

        var result = pushBillItems(allfields, 1);
        if (result !== false) {
            $('#productSearchModal').modal('hide');
        }
    });

    // ── Infinite scroll sentinel ──────────────────────────────────────────────
    function _setupObserver() {
        _destroyObserver();
        var el   = document.getElementById('prodSearchSentinel');
        var root = document.getElementById('prodSearchScrollBody');
        if (!el || !root || !window.IntersectionObserver) return;
        _observer = new IntersectionObserver(function (entries) {
            if (entries[0].isIntersecting && _hasMore && !_loading) {
                _page++;
                _load(true);
            }
        }, { root: root, threshold: 0.1 });
        _observer.observe(el);
    }

    function _destroyObserver() {
        if (_observer) { _observer.disconnect(); _observer = null; }
    }

    // ── Main load dispatcher ──────────────────────────────────────────────────
    function _load(append) {
        if (_loading) return;

        if (_cacheAttempted) {
            if (_cacheData) { _renderFromCache(append); }
            else            { _showError('No products available. Please refresh the page.'); }
            return;
        }

        _loading = true;
        ProductAppend.load(
            function (products) {
                _loading = false;
                _cacheAttempted = true;
                _cacheData = products; // already filtered for _productPurchaseMode by ProductAppend
                _renderFromCache(append);
            },
            function () {
                _loading = false;
                _cacheAttempted = true;
                _cacheData = null;
                _showError('Failed to load products. Please try again.');
            }
        );
    }

    // ── Render from in-memory cache ───────────────────────────────────────────
    function _renderFromCache(append) {
        var term = _term.toLowerCase();
        var filtered = term
            ? _cacheData.filter(function (p) {
                return (p.text         && p.text.toLowerCase().includes(term))            ||
                       (p.categoryName && p.categoryName.toLowerCase().includes(term))    ||
                       (p.hsnCode      && p.hsnCode.toLowerCase().includes(term))         ||
                       (p.partNumber   && p.partNumber.toLowerCase().includes(term));
              })
            : _cacheData;

        var total  = filtered.length;
        var start  = (_page - 1) * _limit;
        var end    = start + _limit;
        var slice  = filtered.slice(start, end);
        _hasMore   = end < total;

        if (!append && slice.length === 0) {
            _showEmpty();
            $('#prodSearchPageInfo').text('');
            return;
        }

        var currency    = (typeof CurrencySymbol !== 'undefined' && CurrencySymbol) ? CurrencySymbol : '₹';
        var forPurchase = !!window._productPurchaseMode;
        var rows        = '';

        slice.forEach(function (p, idx) {
            var serial     = start + idx + 1;
            var price      = forPurchase ? (p.purchasePrice || 0) : (p.sellingPrice || 0);
            var stockCls   = (parseFloat(p.availableQuantity || 0) > 0) ? 'prod-stock-ok' : 'prod-stock-zero';
            var composite  = (!forPurchase && p.isComposite) ? '<span class="prod-badge-composite">Composite</span>' : '';
            var afAttr     = " data-allfields='" + JSON.stringify(p).replace(/'/g, '&#39;') + "'";

            rows += '<tr class="prod-search-row" data-uid="' + p.id + '"' + afAttr + '>';
            rows +=   '<td class="text-center"><span class="prod-serial">' + serial + '</span></td>';
            rows +=   '<td>' +
                          '<div class="prod-name">' + _esc(p.text || p.itemName) + composite + '</div>' +
                          (p.partNumber ? '<div class="prod-meta">Part: ' + _esc(p.partNumber) + '</div>' : '') +
                      '</td>';
            rows +=   '<td><span class="prod-meta">' + _esc(p.categoryName || '—') + '</span></td>';
            rows +=   '<td><span class="prod-meta">' + _esc(p.primaryUnit || '—') + '</span></td>';
            rows +=   '<td class="text-end"><div class="prod-price">' + currency + ' ' + _fmt(price) + '</div></td>';
            rows +=   '<td class="text-end"><div class="' + stockCls + '">' + _fmt(p.availableQuantity || 0) + '</div></td>';
            rows += '</tr>';
        });

        if (append) {
            $('#prodSearchResults').append(rows);
            $('#prodSearchLoadingMore').addClass('d-none');
        } else {
            $('#prodSearchResults').html(rows);
        }

        var shown = Math.min(end, total);
        $('#prodSearchPageInfo').text('Showing ' + shown + ' of ' + total + ' products');
    }

    // ── Shared helpers ────────────────────────────────────────────────────────
    function _resetBody() {
        $('#prodSearchResults').html(
            '<tr><td colspan="6" class="text-center py-5">' +
            '<div class="spinner-border text-success" role="status"><span class="visually-hidden">Loading...</span></div>' +
            '</td></tr>'
        );
        $('#prodSearchLoadingMore').addClass('d-none');
        $('#prodSearchPageInfo').text('Loading…');
    }

    function _showEmpty() {
        $('#prodSearchResults').html(
            '<tr><td colspan="6" class="text-center py-5 text-muted">' +
            '<i class="bx bx-package fs-3 d-block mb-2"></i>' +
            '<div style="font-size:.88rem;">No products found</div>' +
            '</td></tr>'
        );
    }

    function _showError(msg) {
        $('#prodSearchResults').html(
            '<tr><td colspan="6" class="text-center py-5 text-danger">' +
            '<i class="bx bx-error-circle fs-3 d-block mb-2"></i>' +
            '<div style="font-size:.88rem;">' + _esc(msg) + '</div>' +
            '</td></tr>'
        );
    }

    function _esc(s) {
        return String(s || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function _fmt(n) {
        return parseFloat(n || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

}(jQuery));
