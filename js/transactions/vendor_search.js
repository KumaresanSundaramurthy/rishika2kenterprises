/**
 * Vendor Search — shared across all purchase-side transaction pages.
 *
 * searchVendors(key)  — initialises the vendor Select2 dropdown on the element
 *                       with id=key.  Loads from Upstash cache; AJAX fallback.
 *
 * Vendor Search Modal — popup table for searching / picking a vendor.
 */

// ── Vendor Select2 dropdown ───────────────────────────────────────────────────
function searchVendors(key) {
    var $el         = $('#' + key);
    var wrapId      = 'vendorGroup_' + key;
    var vendorCache = null; // local — freed after vendor is selected

    if (!$el.closest('.vendor-search-group').length) {
        $el.wrap('<div class="input-group input-group-sm input-group-merge vendor-search-group" id="' + wrapId + '"></div>');
        $('<span class="input-group-text p-2 cursor-pointer" id="openVendorSearchModal" style="background:#f0ebff;border-color:#d9d0ff;color:#6f42c1;"><i class="icon-base bx bx-search"></i></span>').insertBefore($el);
    }

    $el.select2({
        placeholder: 'Search Vendor by Name, Mobile, GSTIN, Company.',
        minimumInputLength: 0,
        allowClear: true,
        dropdownParent: $('#' + wrapId),
        escapeMarkup: function (markup) { return markup; },
        templateResult: function (d) {
            if (d.loading || !d.name) return d.text;
            var nameHtml   = d.name + (d.mobile ? ' <span style="color:#6c757d;font-weight:400;font-size:.85rem;">(' + d.mobile + ')</span>' : '');
            var balHtml    = _custBalanceHtml(d.balance, d.balanceType);
            var line2Parts = [];
            if (d.area)    line2Parts.push('<span style="color:#8a8d93;font-style:italic;font-size:.78rem;">'         + d.area        + '</span>');
            if (d.company) line2Parts.push('<span style="color:#696cff;font-style:italic;font-size:.78rem;">(' + d.company + ')</span>');
            var line2Html  = line2Parts.length
                ? line2Parts.join(' ')
                : '<span style="font-size:.78rem;">&nbsp;</span>';
            return $(
                '<div style="display:flex;align-items:flex-start;gap:6px;">' +
                    '<div style="flex:1;min-width:0;">' +
                        '<div style="font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + nameHtml + '</div>' +
                        '<div style="margin-top:2px;">' + line2Html + '</div>' +
                    '</div>' +
                    '<div style="text-align:right;white-space:nowrap;flex-shrink:0;padding-top:1px;">' + balHtml + '</div>' +
                '</div>'
            );
        },
        templateSelection: function (d) {
            if (!d.id) return d.text;
            if (d.name) return d.area ? d.name + ' (' + d.area + ')' : d.name;
            return d.text;
        },
        ajax: {
            transport: function (params, success, failure) {
                var term     = ((params.data && params.data.term) || '').toLowerCase().trim();
                var page     = (params.data && params.data.page) || 1;
                var pageSize = 15;

                function _paginate(list) {
                    var filtered = term
                        ? list.filter(function (v) {
                            return (v.name    && v.name.toLowerCase().includes(term))    ||
                                   (v.area    && v.area.toLowerCase().includes(term))    ||
                                   (v.mobile  && v.mobile.toLowerCase().includes(term))  ||
                                   (v.gstin   && v.gstin.toLowerCase().includes(term))   ||
                                   (v.company && v.company.toLowerCase().includes(term));
                          })
                        : list;
                    var start = (page - 1) * pageSize;
                    AjaxLoading = 1;
                    success({ Lists: filtered.slice(start, start + pageSize), more: (start + pageSize) < filtered.length });
                }

                if (vendorCache) { _paginate(vendorCache); return; }

                if (typeof _upstashUrl !== 'undefined' && _upstashUrl && typeof _vendorCacheKey !== 'undefined' && _vendorCacheKey) {
                    $.ajax({
                        url: _upstashUrl + '/hgetall/' + encodeURIComponent(_vendorCacheKey),
                        headers: { 'Authorization': 'Bearer ' + _upstashReadToken },
                        dataType: 'json',
                        success: function (resp) {
                            var raw = resp.result;
                            var map = {};
                            if (Array.isArray(raw)) {
                                for (var i = 0; i + 1 < raw.length; i += 2) {
                                    try { map[raw[i]] = JSON.parse(raw[i + 1]); }
                                    catch (e) { map[raw[i]] = raw[i + 1]; }
                                }
                            } else if (raw && typeof raw === 'object') {
                                map = raw;
                            }
                            vendorCache = Object.keys(map).map(function (uid) {
                                var v = map[uid];
                                return {
                                    id:          parseInt(v.VendorUID || uid, 10),
                                    text:        v.Area ? (v.Name + ' (' + v.Area + ')') : (v.Name || ''),
                                    name:        v.Name             || '',
                                    area:        v.Area             || '',
                                    mobile:      v.MobileNumber     || '',
                                    gstin:       v.GSTIN            || '',
                                    company:     v.CompanyName      || '',
                                    balance:     parseFloat(v.OpeningBalance || 0),
                                    balanceType: v.OpeningBalType   || 'Debit',
                                    lastTxAt:    v.LastTransactionAt || '',
                                };
                            });
                            vendorCache.sort(function (a, b) {
                                if (a.lastTxAt && b.lastTxAt) return new Date(b.lastTxAt) - new Date(a.lastTxAt);
                                if (a.lastTxAt) return -1;
                                if (b.lastTxAt) return 1;
                                return a.name.localeCompare(b.name);
                            });
                            _paginate(vendorCache);
                        },
                        error: function () {
                            $.ajax({
                                url: '/transactions/searchVendors',
                                dataType: 'json',
                                data: { term: (params.data && params.data.term) || '', type: 'public' },
                                success: function (data) { AjaxLoading = 1; success(data); },
                                error: function () { AjaxLoading = 1; failure(); }
                            });
                        }
                    });
                } else {
                    $.ajax({
                        url: '/transactions/searchVendors',
                        dataType: 'json',
                        data: { term: (params.data && params.data.term) || '', type: 'public' },
                        success: function (data) { AjaxLoading = 1; success(data); },
                        error: function () { AjaxLoading = 1; failure(); }
                    });
                }
            },
            delay: 250,
            data: function (params) {
                AjaxLoading = 0;
                return { term: params.term, page: params.page || 1 };
            },
            processResults: function (data) {
                AjaxLoading = 1;
                return { results: data.Lists, pagination: { more: data.more || false } };
            },
            cache: false
        }
    }).on('select2:select', function () {
        vendorCache = null;
    }).on('select2:close', function () {
        AjaxLoading = 1;
    });
}

// ── Vendor Search Modal ───────────────────────────────────────────────────────
(function () {
    'use strict';

    var currentPage = 1;
    var searchTerm  = '';
    var PAGE_SIZE   = 15;
    var vendCache   = null; // populated once from Upstash; null = not yet fetched

    // ── Open modal ────────────────────────────────────────────────────────────
    $(document).on('click', '#openVendorSearchModal', function () {
        currentPage = 1;
        searchTerm  = '';
        $('#vendSearchInput').val('');
        $('#vendSearchClear').addClass('d-none');
        $('#vendorSearchModal').modal('show');
        loadVendors();
    });

    // ── Search input ──────────────────────────────────────────────────────────
    var _timer;
    $(document).on('input', '#vendSearchInput', function () {
        clearTimeout(_timer);
        searchTerm = $.trim($(this).val());
        $('#vendSearchClear').toggleClass('d-none', searchTerm === '');
        _timer = setTimeout(function () {
            currentPage = 1;
            loadVendors();
        }, 300);
    });

    $(document).on('click', '#vendSearchClear', function () {
        $('#vendSearchInput').val('').trigger('input');
    });

    // ── Pagination ────────────────────────────────────────────────────────────
    $(document).on('click', '#vendSearchPagination .page-link', function (e) {
        e.preventDefault();
        var p = parseInt($(this).data('page'), 10);
        if (!p || p < 1) return;
        currentPage = p;
        loadVendors();
    });

    // ── Row selection ─────────────────────────────────────────────────────────
    $(document).on('click', '.vend-search-item', function () {
        var vendUID  = $(this).data('uid');
        var vendName = $(this).data('name');
        var address  = $(this).data('address');
        var state    = $(this).data('state');
        if (!vendUID || !vendName) return;

        var $sel = $('#vendorSearch');
        if ($sel.find('option[value="' + vendUID + '"]').length === 0) {
            $sel.append(new Option(vendName, vendUID, true, true));
        } else {
            $sel.val(vendUID);
        }
        $sel.trigger('change');

        if (address) {
            var addrHtml = '<div><strong>Billing Address:</strong></div>'
                + '<div>' + (address.Line1 || '') + '</div>'
                + '<div>' + (address.Line2 || '') + '</div>'
                + '<div>' + [address.City, address.State].filter(Boolean).join(', ')
                + (address.Pincode ? ' - ' + address.Pincode : '') + '</div>';
            $('#vendorAddressBox').html(addrHtml).removeClass('d-none');
        } else {
            $('#vendorAddressBox').addClass('d-none').empty();
        }

        if (typeof billManager !== 'undefined' && typeof _orgState !== 'undefined' && state) {
            billManager.setInterState(state.trim().toLowerCase() !== _orgState.trim().toLowerCase());
        }

        $('#vendorSearchModal').modal('hide');
    });

    // ── Load (Upstash first, AJAX fallback) ───────────────────────────────────
    function loadVendors() {
        // Cache hit — filter and paginate instantly, no network call
        if (vendCache) {
            _render(vendCache);
            return;
        }

        _showSpinner();

        if (typeof UpstashService !== 'undefined' && UpstashService.isEnabled()) {
            UpstashService.hgetall(UpstashService.orgKey('vendors'))
                .then(function (map) {
                    var keys = Object.keys(map || {});
                    if (!keys.length) { _fallbackAjax(); return; }

                    vendCache = keys.map(function (uid) {
                        var v = map[uid];
                        var billing = null;
                        (v.Address || []).forEach(function (a) {
                            if (a.AddressType === 'Billing') billing = a;
                        });
                        return {
                            uid:     parseInt(v.VendorUID || uid, 10),
                            name:    v.Name         || '',
                            area:    v.Area         || '',
                            mobile:  v.MobileNumber || '',
                            gstin:   v.GSTIN        || '',
                            company: v.CompanyName  || '',
                            balance: parseFloat(v.OpeningBalance || 0),
                            balType: v.OpeningBalType || 'Credit',
                            lastTx:  v.LastTransactionAt || '',
                            address: billing ? {
                                Line1:   billing.Line1     || '',
                                Line2:   billing.Line2     || '',
                                City:    billing.CityText  || '',
                                State:   billing.StateText || '',
                                Pincode: billing.Pincode   || '',
                            } : null,
                        };
                    });

                    // Recent transactions first, then A–Z
                    vendCache.sort(function (a, b) {
                        if (a.lastTx && b.lastTx) return new Date(b.lastTx) - new Date(a.lastTx);
                        if (a.lastTx) return -1;
                        if (b.lastTx) return 1;
                        return a.name.localeCompare(b.name);
                    });

                    _render(vendCache);
                })
                .catch(function () { _fallbackAjax(); });
        } else {
            _fallbackAjax();
        }
    }

    // ── Render from in-memory cache ───────────────────────────────────────────
    function _render(all) {
        var term = searchTerm.toLowerCase();
        var filtered = term
            ? all.filter(function (v) {
                return v.name.toLowerCase().indexOf(term)   !== -1
                    || v.mobile.toLowerCase().indexOf(term) !== -1
                    || v.area.toLowerCase().indexOf(term)   !== -1
                    || (v.gstin   && v.gstin.toLowerCase().indexOf(term)   !== -1)
                    || (v.company && v.company.toLowerCase().indexOf(term) !== -1);
              })
            : all;

        var total = filtered.length;
        var start = (currentPage - 1) * PAGE_SIZE;
        var page  = filtered.slice(start, start + PAGE_SIZE);

        if (!page.length) {
            $('#vendSearchResults').html(
                '<div class="text-center py-5 text-muted">' +
                    '<i class="bx bx-store" style="font-size:2.5rem;display:block;margin-bottom:10px;"></i>' +
                    '<div style="font-size:.9rem;margin-bottom:14px;">No vendors found</div>' +
                    '<button type="button" class="btn btn-primary btn-sm px-3" id="vendSearchCreateBtn">' +
                        '<i class="bx bx-plus me-1"></i>Create Vendor' +
                    '</button>' +
                '</div>'
            );
            _hidePagination();
            return;
        }

        var cur  = (typeof CurrencySymbol !== 'undefined' && CurrencySymbol) ? CurrencySymbol : '₹';
        var html = '<table class="vend-search-table">'
            + '<thead><tr>'
            + '<th class="col-serial">#</th>'
            + '<th>Vendor</th>'
            + '<th class="col-mobile">Mobile</th>'
            + '<th class="col-balance">Balance</th>'
            + '</tr></thead><tbody>';

        page.forEach(function (v, i) {
            var serial   = start + i + 1;
            var balClass = v.balType === 'Credit' ? 'credit' : 'debit';
            var balLabel = v.balType === 'Credit' ? 'Payable' : 'Receivable';
            var addrJson = v.address ? JSON.stringify(v.address).replace(/'/g, '&#39;') : '';
            var stateVal = v.address ? escapeHtml(v.address.State || '') : '';

            html += '<tr class="vend-search-item"'
                  + ' data-uid="'  + v.uid + '"'
                  + ' data-name="' + escapeHtml(v.name) + '"'
                  + (addrJson ? ' data-address=\'' + addrJson + '\'' : '')
                  + ' data-state="' + stateVal + '">';
            html += '<td class="col-serial"><div class="vend-serial">' + serial + '</div></td>';
            html += '<td class="col-vendor"><div class="vend-name">' + escapeHtml(v.name) + '</div>';
            var meta = [];
            if (v.area)    meta.push('<i class="bx bx-map me-1"></i>'       + escapeHtml(v.area));
            if (v.company) meta.push('<i class="bx bx-buildings me-1"></i>' + escapeHtml(v.company));
            if (meta.length) html += '<div class="vend-meta">' + meta.join('<span class="vend-sep"> · </span>') + '</div>';
            html += '</td>';
            html += '<td class="col-mobile"><div class="vend-meta">'
                  + (v.mobile ? escapeHtml(v.mobile) : '<span style="color:#bbb;">—</span>')
                  + '</div></td>';
            html += '<td class="col-balance">'
                  + '<div class="vend-balance ' + balClass + '">' + cur + ' ' + formatNumber(v.balance, 2) + '</div>'
                  + '<div class="vend-meta">' + balLabel + '</div>'
                  + '</td>';
            html += '</tr>';
        });

        html += '</tbody></table>';
        $('#vendSearchResults').html(html);
        _renderPagination(total);
    }

    // ── AJAX fallback (Upstash unavailable or empty) ──────────────────────────
    function _fallbackAjax() {
        $.ajax({
            url:    '/vendors/getVendorSearchList',
            method: 'POST',
            data:   { PageNo: currentPage, RowLimit: PAGE_SIZE, Search: searchTerm, [CsrfName]: CsrfToken },
            success: function (r) {
                if (r.Error || !r.Vendors || !r.Vendors.length) {
                    $('#vendSearchResults').html(
                        '<div class="text-center py-5 text-muted">' +
                            '<i class="bx bx-store" style="font-size:2.5rem;display:block;margin-bottom:10px;"></i>' +
                            '<div style="font-size:.9rem;">' + (r.Message || 'No vendors found') + '</div>' +
                        '</div>'
                    );
                    _hidePagination();
                    return;
                }
                var cur  = (typeof CurrencySymbol !== 'undefined' && CurrencySymbol) ? CurrencySymbol : '₹';
                var html = '<table class="vend-search-table">'
                    + '<thead><tr>'
                    + '<th class="col-serial">#</th>'
                    + '<th>Vendor</th>'
                    + '<th class="col-mobile">Mobile</th>'
                    + '<th class="col-balance">Balance</th>'
                    + '</tr></thead><tbody>';

                r.Vendors.forEach(function (v, i) {
                    var serial   = ((currentPage - 1) * PAGE_SIZE) + i + 1;
                    var bt       = v.BalanceType || 'Credit';
                    var balClass = bt === 'Credit' ? 'credit' : 'debit';
                    var balLabel = bt === 'Credit' ? 'Payable' : 'Receivable';
                    var addrJson = v.address ? JSON.stringify(v.address).replace(/'/g, '&#39;') : '';
                    var stateVal = v.address ? escapeHtml(v.address.State || '') : '';

                    html += '<tr class="vend-search-item"'
                          + ' data-uid="'  + v.VendorUID + '"'
                          + ' data-name="' + escapeHtml(v.Name) + '"'
                          + (addrJson ? ' data-address=\'' + addrJson + '\'' : '')
                          + ' data-state="' + stateVal + '">';
                    html += '<td class="col-serial"><div class="vend-serial">' + serial + '</div></td>';
                    html += '<td class="col-vendor"><div class="vend-name">' + escapeHtml(v.Name) + '</div>'
                          + (v.Area ? '<div class="vend-meta"><i class="bx bx-map me-1"></i>' + escapeHtml(v.Area) + '</div>' : '')
                          + '</td>';
                    html += '<td class="col-mobile"><div class="vend-meta">'
                          + (v.MobileNumber ? escapeHtml(v.MobileNumber) : '<span style="color:#bbb;">—</span>')
                          + '</div></td>';
                    html += '<td class="col-balance">'
                          + '<div class="vend-balance ' + balClass + '">' + cur + ' ' + formatNumber(parseFloat(v.Balance || 0), 2) + '</div>'
                          + '<div class="vend-meta">' + balLabel + '</div>'
                          + '</td>';
                    html += '</tr>';
                });

                html += '</tbody></table>';
                $('#vendSearchResults').html(html);
                _renderPagination(r.TotalCount || 0);
            },
            error: function () {
                $('#vendSearchResults').html(
                    '<div class="text-center py-5 text-danger">' +
                        '<i class="bx bx-error-circle fs-3 d-block mb-2"></i>Failed to load vendors' +
                    '</div>'
                );
                _hidePagination();
            }
        });
    }

    // ── Pagination renderer ───────────────────────────────────────────────────
    function _renderPagination(total) {
        var totalPages = Math.ceil(total / PAGE_SIZE);
        var from = ((currentPage - 1) * PAGE_SIZE) + 1;
        var to   = Math.min(currentPage * PAGE_SIZE, total);
        $('#vendSearchPageInfo').text(total > 0 ? 'Showing ' + from + ' – ' + to + ' of ' + total + ' Results' : '');
        var html = '';
        if (totalPages > 1) {
            var s = Math.max(1, currentPage - 2);
            var e = Math.min(totalPages, s + 4);
            s = Math.max(1, e - 4);
            html += '<li class="page-item' + (currentPage <= 1 ? ' disabled' : '') + '">'
                  + '<a class="page-link" href="#" data-page="' + (currentPage - 1) + '">&lsaquo;</a></li>';
            for (var p = s; p <= e; p++) {
                html += '<li class="page-item' + (p === currentPage ? ' active' : '') + '">'
                      + '<a class="page-link" href="#" data-page="' + p + '">' + p + '</a></li>';
            }
            html += '<li class="page-item' + (currentPage >= totalPages ? ' disabled' : '') + '">'
                  + '<a class="page-link" href="#" data-page="' + (currentPage + 1) + '">&rsaquo;</a></li>';
        }
        $('#vendSearchPagination').html(html);
        $('#vendSearchPaginationWrap').toggleClass('d-none', total === 0);
    }

    function _showSpinner() {
        $('#vendSearchResults').html(
            '<div class="text-center py-5">' +
                '<div class="spinner-border text-primary" role="status">' +
                    '<span class="visually-hidden">Loading...</span>' +
                '</div>' +
            '</div>'
        );
        _hidePagination();
    }

    function _hidePagination() {
        $('#vendSearchPagination').html('');
        $('#vendSearchPageInfo').text('');
        $('#vendSearchPaginationWrap').addClass('d-none');
    }

    // ── Create vendor buttons ─────────────────────────────────────────────────
    function _openCreate() {
        $('#vendorSearchModal').modal('hide');
        setTimeout(function () { $('#addTransVendor').trigger('click'); }, 300);
    }
    $(document).on('click', '#btnCreateVendorFromSearch', function () { _openCreate(); });
    $(document).on('click', '#vendSearchCreateBtn',       function () { _openCreate(); });

    // ── Helpers ───────────────────────────────────────────────────────────────
    function escapeHtml(t) {
        var m = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(t || '').replace(/[&<>"']/g, function (c) { return m[c]; });
    }
    function formatNumber(n, d) {
        return parseFloat(n || 0).toFixed(d).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

}());
