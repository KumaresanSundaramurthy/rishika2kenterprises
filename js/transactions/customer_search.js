/**
 * Customer Search Modal — Common
 *
 * Data strategy:
 *   1. First open: HGETALL from Upstash → build in-memory list → filter + paginate locally
 *   2. Infinite scroll: slices next 20 from in-memory list (zero extra network calls)
 *   3. Cache miss / Upstash disabled: falls back to /customers/getCustomerSearchList (paginated AJAX)
 *
 * _cacheData persists across modal opens within the same page session.
 *
 * Page-level flags:
 *   window.R2K_CUST_HIDE_CREATE = true  →  hide "+ Create Customer" on this page
 */
(function ($) {
    'use strict';

    var _limit   = 20;
    var _page    = 1;
    var _term    = '';
    var _loading = false;
    var _hasMore = false;
    var _observer = null;

    // ── Cache state (persists across modal opens in the same page session) ─────
    var _cacheAttempted = false; // true after first Upstash HGETALL attempt completes
    var _cacheData      = null;  // null = miss/error; Array = hit (full customer list)

    // ── Open ──────────────────────────────────────────────────────────────────
    $(document).on('click', '#openCustomerSearchModal', function () {
        _page = 1; _term = ''; _loading = false; _hasMore = false;
        $('#custSearchInput').val('');
        $('#custSearchClear').addClass('d-none');
        $('#btnCreateCustomerFromSearch').toggleClass('d-none', !!(window.R2K_CUST_HIDE_CREATE));
        _resetBody();
        $('#customerSearchModal').modal('show');
    });

    $('#customerSearchModal').on('shown.bs.modal', function () {
        _setupObserver();
        $('#custSearchInput').trigger('focus');
        _load(false);
    });

    $('#customerSearchModal').on('hidden.bs.modal', function () {
        _destroyObserver();
    });

    // ── Search (debounced 380 ms) ─────────────────────────────────────────────
    var _searchTimer;
    $(document).on('input', '#custSearchInput', function () {
        clearTimeout(_searchTimer);
        _term = $.trim($(this).val());
        $('#custSearchClear').toggleClass('d-none', _term === '');
        _searchTimer = setTimeout(function () {
            _page = 1; _hasMore = false; _loading = false;
            _resetBody();
            _load(false);
        }, 380);
    });

    $(document).on('click', '#custSearchClear', function () {
        $('#custSearchInput').val('').trigger('input');
    });

    // ── Row click → select customer ───────────────────────────────────────────
    $(document).on('click', '.cust-search-row', function () {
        var custUID      = $(this).data('uid');
        var custName     = $(this).data('name');
        var address      = $(this).data('address');
        var state        = $(this).data('state');
        var countryISO2  = $(this).data('country') || 'IN';
        var area         = $(this).data('area')    || '';
        var mobile       = $(this).data('mobile')  || '';
        var oaBalance    = parseFloat($(this).data('oa-balance') || 0);
        var oaRecordsRaw = $(this).data('oa-records');
        var oaRecords    = [];
        if (oaRecordsRaw) {
            try { oaRecords = typeof oaRecordsRaw === 'string' ? JSON.parse(oaRecordsRaw) : oaRecordsRaw; }
            catch (e) {}
        }
        if (!custUID || !custName) return;

        // Build formatted display text matching templateSelection: Name, Area (Mobile)
        var displayText = custName;
        if (area)   displayText += ', ' + area;
        if (mobile) displayText += ' (' + mobile + ')';

        var $select = $('#customerSearch');
        if ($select.find('option[value="' + custUID + '"]').length === 0) {
            $select.append(new Option(displayText, custUID, true, true));
        } else {
            $select.val(custUID);
        }
        $select.trigger('change');

        if (address) {
            var addrHtml = '<div><strong>Shipping Address:</strong></div>'
                + '<div>' + _esc(address.Line1 || '') + '</div>'
                + '<div>' + _esc(address.Line2 || '') + '</div>'
                + '<div>' + [address.City, address.State].filter(Boolean).map(_esc).join(', ')
                + (address.Pincode ? ' – ' + _esc(address.Pincode) : '') + '</div>';
            $('#customerAddressBox').html(addrHtml).removeClass('d-none');
            if (typeof window._onCustStateSelected === 'function') {
                window._onCustStateSelected((state || (address && address.State) || '').trim());
            }
        } else {
            $('#customerAddressBox').addClass('d-none').empty();
        }

        if (typeof _showCustTypeIndicator === 'function') {
            _showCustTypeIndicator({ countryISO2: countryISO2, address: address || null });
        }

        if (typeof _showOnAccountBanner === 'function') {
            _showOnAccountBanner(oaBalance, oaRecords, custUID);
        }

        $('#customerSearchModal').modal('hide');
    });

    // ── Infinite scroll sentinel ──────────────────────────────────────────────
    function _setupObserver() {
        _destroyObserver();
        var el   = document.getElementById('custSearchSentinel');
        var root = document.getElementById('custSearchScrollBody');
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

        // Cache already resolved this session — go directly
        if (_cacheAttempted) {
            if (_cacheData) {
                _renderFromCache(append); // synchronous; no _loading guard needed
            } else {
                _loadFromBackend(append); // async AJAX fallback
            }
            return;
        }

        // First ever load — try Upstash
        if (typeof UpstashService === 'undefined' || !UpstashService.isEnabled()) {
            _cacheAttempted = true;
            _cacheData = null;
            _loadFromBackend(append);
            return;
        }

        _loading = true; // block while HGETALL is in-flight
        UpstashService.hgetall(UpstashService.orgKey('customers'))
            .then(function (map) {
                _loading = false;
                _cacheAttempted = true;

                if (!map || typeof map !== 'object' || !Object.keys(map).length) {
                    _cacheData = null;
                    _loadFromBackend(append);
                    return;
                }

                // Build normalised array from Upstash hash
                var list = [];
                Object.keys(map).forEach(function (uid) {
                    var c = map[uid];
                    var entry = {
                        CustomerUID:      parseInt(c.CustomerUID || uid, 10),
                        Name:             c.Name          || '',
                        Area:             c.Area          || '',
                        MobileNumber:     c.MobileNumber  || '',
                        Balance:          parseFloat(c.ClosingBalance  || 0),
                        BalanceType:      c.ClosingBalType || 'Debit',
                        CountryISO2:      c.CountryISO2   || 'IN',
                        _lastTxAt:        c.LastTransactionAt || '',
                        OnAccountBalance: parseFloat(c.OnAccountBalance || 0),
                        OnAccountRecords: (function () {
                            try { return c.OnAccountRecords ? (typeof c.OnAccountRecords === 'string' ? JSON.parse(c.OnAccountRecords) : c.OnAccountRecords) : []; }
                            catch (e) { return []; }
                        }()),
                    };
                    // Pick billing address; fall back to first address
                    if (c.Address && c.Address.length) {
                        var addr = c.Address[0];
                        c.Address.forEach(function (a) {
                            if (a.AddressType === 'Billing') addr = a;
                        });
                        entry.address = {
                            Line1:   addr.Line1     || '',
                            Line2:   addr.Line2     || '',
                            Pincode: addr.Pincode   || '',
                            City:    addr.CityText  || '',
                            State:   addr.StateText || '',
                        };
                    }
                    list.push(entry);
                });

                // Sort: most recently transacted first, then A-Z
                list.sort(function (a, b) {
                    if (a._lastTxAt && b._lastTxAt) {
                        return a._lastTxAt > b._lastTxAt ? -1 : a._lastTxAt < b._lastTxAt ? 1 : 0;
                    }
                    if (a._lastTxAt) return -1;
                    if (b._lastTxAt) return  1;
                    return a.Name.localeCompare(b.Name);
                });

                _cacheData = list;
                _renderFromCache(append);
            })
            .catch(function () {
                _loading = false;
                _cacheAttempted = true;
                _cacheData = null;
                _loadFromBackend(append);
            });
    }

    // ── Render from in-memory cache ────────────────────────────────────────────
    function _renderFromCache(append) {
        var term = _term.toLowerCase();
        var filtered = term
            ? _cacheData.filter(function (c) {
                return (c.Name         && c.Name.toLowerCase().includes(term))        ||
                       (c.Area         && c.Area.toLowerCase().includes(term))        ||
                       (c.MobileNumber && c.MobileNumber.toLowerCase().includes(term));
              })
            : _cacheData;

        var total  = filtered.length;
        var start  = (_page - 1) * _limit;
        var end    = start + _limit;
        var slice  = filtered.slice(start, end);
        _hasMore   = end < total;

        if (!append && slice.length === 0) {
            _showEmpty();
            $('#custSearchPageInfo').text('');
            return;
        }

        var currency = (typeof CurrencySymbol !== 'undefined' && CurrencySymbol) ? CurrencySymbol : '₹';
        var rows = '';
        slice.forEach(function (cust, idx) {
            var serial  = start + idx + 1;
            var balCls  = cust.BalanceType === 'Debit' ? 'cust-bal-debit' : 'cust-bal-credit';
            var balLbl  = cust.BalanceType === 'Debit' ? 'Receivable'     : 'Payable';
            var addrAttr    = cust.address ? " data-address='" + JSON.stringify(cust.address).replace(/'/g, '&#39;') + "'" : '';
            var stateAttr   = cust.address ? ' data-state="'   + _esc((cust.address && cust.address.State)   || '') + '"' : '';
            var countryAttr = ' data-country="' + _esc(cust.CountryISO2 || 'IN') + '"';
            var areaAttr    = ' data-area="'    + _esc(cust.Area         || '') + '"';
            var mobileAttr  = ' data-mobile="'  + _esc(cust.MobileNumber || '') + '"';
            var oaBalAttr   = ' data-oa-balance="' + (cust.OnAccountBalance || 0) + '"';
            var oaRecAttr   = " data-oa-records='" + JSON.stringify(cust.OnAccountRecords || []).replace(/'/g, '&#39;') + "'";

            rows += '<tr class="cust-search-row" data-uid="' + cust.CustomerUID + '" data-name="' + _esc(cust.Name) + '"' + addrAttr + stateAttr + countryAttr + areaAttr + mobileAttr + oaBalAttr + oaRecAttr + '>';
            rows +=   '<td class="text-center"><span class="cust-serial">' + serial + '</span></td>';
            rows +=   '<td><div class="cust-name">' + _esc(cust.Name) + '</div></td>';
            rows +=   '<td><span class="cust-meta">' + _esc(cust.Area || '—') + '</span></td>';
            rows +=   '<td><span class="cust-meta">' + _esc(cust.MobileNumber || '—') + '</span></td>';
            rows +=   '<td class="text-end"><div class="' + balCls + '">' + currency + ' ' + _fmt(cust.Balance) + '</div>' +
                      '<div class="cust-meta">' + balLbl + '</div></td>';
            rows += '</tr>';
        });

        if (append) {
            $('#custSearchResults').append(rows);
            $('#custSearchLoadingMore').addClass('d-none');
        } else {
            $('#custSearchResults').html(rows);
        }

        var shown = Math.min(end, total);
        $('#custSearchPageInfo').text('Showing ' + shown + ' of ' + total + ' customers');
    }

    // ── Backend AJAX fallback (paginated, used when cache is miss) ────────────
    function _loadFromBackend(append) {
        if (_loading) return;
        _loading = true;
        AjaxLoading = 0;
        if (append) $('#custSearchLoadingMore').removeClass('d-none');

        var postData = { PageNo: _page, RowLimit: _limit, Search: _term };
        postData[CsrfName] = CsrfToken;

        $.ajax({
            url    : '/customers/getCustomerSearchList',
            method : 'POST',
            data   : postData,
            success: function (res) {
                _loading = false;
                AjaxLoading = 1;
                $('#custSearchLoadingMore').addClass('d-none');

                if (res.Error) {
                    if (!append) _showError(res.Message || 'Error loading customers');
                    _hasMore = false;
                    return;
                }

                var total = parseInt(res.TotalCount || 0);
                var custs = res.Customers || [];

                if (!append && custs.length === 0) {
                    _showEmpty();
                    $('#custSearchPageInfo').text('');
                    _hasMore = false;
                    return;
                }

                _hasMore = (_page * _limit) < total;
                var currency = (typeof CurrencySymbol !== 'undefined' && CurrencySymbol) ? CurrencySymbol : '₹';
                var rows = '';
                $.each(custs, function (idx, cust) {
                    var serial  = (_page - 1) * _limit + idx + 1;
                    var balType = cust.BalanceType || 'Debit';
                    var balCls  = balType === 'Debit' ? 'cust-bal-debit' : 'cust-bal-credit';
                    var balLbl  = balType === 'Debit' ? 'Receivable'     : 'Payable';
                    var addrAttr    = cust.address ? " data-address='" + JSON.stringify(cust.address).replace(/'/g, '&#39;') + "'" : '';
                    var stateAttr   = cust.address ? ' data-state="'   + _esc((cust.address && cust.address.State)   || '') + '"' : '';
                    var countryAttr = ' data-country="' + _esc(cust.CountryISO2 || 'IN') + '"';
                    var areaAttr    = ' data-area="'    + _esc(cust.Area         || '') + '"';
                    var mobileAttr  = ' data-mobile="'  + _esc(cust.MobileNumber || '') + '"';

                    rows += '<tr class="cust-search-row" data-uid="' + cust.CustomerUID + '" data-name="' + _esc(cust.Name) + '"' + addrAttr + stateAttr + countryAttr + areaAttr + mobileAttr + ' data-oa-balance="0" data-oa-records=\'[]\'' + '>';
                    rows +=   '<td class="text-center"><span class="cust-serial">' + serial + '</span></td>';
                    rows +=   '<td><div class="cust-name">' + _esc(cust.Name) + '</div></td>';
                    rows +=   '<td><span class="cust-meta">' + _esc(cust.Area || '—') + '</span></td>';
                    rows +=   '<td><span class="cust-meta">' + _esc(cust.MobileNumber || '—') + '</span></td>';
                    rows +=   '<td class="text-end"><div class="' + balCls + '">' + currency + ' ' + _fmt(parseFloat(cust.Balance || 0)) + '</div>' +
                              '<div class="cust-meta">' + balLbl + '</div></td>';
                    rows += '</tr>';
                });

                if (append) {
                    $('#custSearchResults').append(rows);
                } else {
                    $('#custSearchResults').html(rows);
                }

                var shown = Math.min(_page * _limit, total);
                $('#custSearchPageInfo').text('Showing ' + shown + ' of ' + total + ' customers');
            },
            error: function () {
                _loading = false;
                AjaxLoading = 1;
                $('#custSearchLoadingMore').addClass('d-none');
                if (!append) _showError('Failed to load customers. Please try again.');
                _hasMore = false;
            }
        });
    }

    // ── Shared helpers ────────────────────────────────────────────────────────
    function _resetBody() {
        $('#custSearchResults').html(
            '<tr><td colspan="5" class="text-center py-5">' +
            '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>' +
            '</td></tr>'
        );
        $('#custSearchLoadingMore').addClass('d-none');
        $('#custSearchPageInfo').text('Loading…');
    }

    function _showEmpty() {
        $('#custSearchResults').html(
            '<tr><td colspan="5" class="text-center py-5 text-muted">' +
            '<i class="bx bx-user-x fs-3 d-block mb-2"></i>' +
            '<div class="mb-2" style="font-size:.88rem;">No customers found</div>' +
            (!(window.R2K_CUST_HIDE_CREATE)
                ? '<button type="button" class="btn btn-primary btn-sm px-3" id="custSearchCreateBtn">' +
                  '<i class="bx bx-plus me-1"></i>Create Customer</button>'
                : '') +
            '</td></tr>'
        );
    }

    function _showError(msg) {
        $('#custSearchResults').html(
            '<tr><td colspan="5" class="text-center py-5 text-danger">' +
            '<i class="bx bx-error-circle fs-3 d-block mb-2"></i>' + _esc(msg) +
            '</td></tr>'
        );
        $('#custSearchPageInfo').text('');
    }

    // ── Create Customer ───────────────────────────────────────────────────────
    function _openCreate() {
        var prefill = $('#custSearchInput').val().trim();
        $('#customerSearchModal').modal('hide');
        setTimeout(function () {
            CustomerForm.open('add', null, {
                prefillName  : prefill,
                onSaveSuccess: function (response) {
                    var c = response.Customer;
                    if (!c || !c.id) return;
                    var $sel = $('#customerSearch');
                    if ($sel.find('option[value="' + c.id + '"]').length === 0) {
                        $sel.append(new Option(c.text, c.id, true, true));
                    } else {
                        $sel.val(c.id);
                    }
                    $sel.trigger('change');
                    $sel.trigger({ type: 'select2:select', params: { data: { id: c.id, text: c.text } } });
                    if (c.address) {
                        var a = c.address;
                        var aHtml = '<div><strong>Shipping Address:</strong></div>'
                            + '<div>' + (a.Line1 || '') + '</div>'
                            + '<div>' + (a.Line2 || '') + '</div>'
                            + '<div>' + [a.City, a.State].filter(Boolean).join(', ')
                            + (a.Pincode ? ' – ' + a.Pincode : '') + '</div>';
                        $('#customerAddressBox').html(aHtml).removeClass('d-none');
                    } else {
                        $('#customerAddressBox').addClass('d-none').empty();
                    }
                    // Invalidate cache so next modal open re-fetches the new customer
                    _cacheAttempted = false;
                    _cacheData = null;
                }
            });
        }, 300);
    }

    $(document).on('click', '#btnCreateCustomerFromSearch, #custSearchCreateBtn', _openCreate);

    function _esc(s) {
        return String(s || '').replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[c];
        });
    }

    function _fmt(n) {
        return parseFloat(n || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

}(jQuery));
