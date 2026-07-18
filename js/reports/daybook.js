'use strict';

(function () {

    /* ── Type config ──────────────────────────────────────────────────────────
       label   : display text
       badge   : CSS class on .db-badge
       dot     : hex colour for type filter dot
       flow    : 'in' | 'out' | 'neutral'
    ──────────────────────────────────────────────────────────────────────── */
    var TYPE_CFG = {
        'Invoice':          { label: 'Invoice',           badge: 'db-badge-invoice',  dot: '#16a34a', flow: 'in'      },
        'Purchase':         { label: 'Purchase',          badge: 'db-badge-purchase', dot: '#dc2626', flow: 'out'     },
        'Payment In':       { label: 'Payment In',        badge: 'db-badge-payin',    dot: '#2563eb', flow: 'in'      },
        'Payment Out':      { label: 'Payment Out',       badge: 'db-badge-payout',   dot: '#d97706', flow: 'out'     },
        'SalesReturn':      { label: 'Sales Return',      badge: 'db-badge-sr',       dot: '#7c3aed', flow: 'out'     },
        'PurchaseReturn':   { label: 'Purchase Return',   badge: 'db-badge-pr',       dot: '#0d9488', flow: 'in'      },
        'SalesOrder':       { label: 'Sales Order',       badge: 'db-badge-so',       dot: '#0891b2', flow: 'neutral' },
        'PurchaseOrder':    { label: 'Purchase Order',    badge: 'db-badge-po',       dot: '#9333ea', flow: 'neutral' },
        'ProformaInvoice':  { label: 'Proforma',          badge: 'db-badge-pf',       dot: '#5b21b6', flow: 'neutral' },
        'Quotation':        { label: 'Quotation',         badge: 'db-badge-quot',     dot: '#475569', flow: 'neutral' },
        'DeliveryChallan':  { label: 'Delivery Challan',  badge: 'db-badge-dc',       dot: '#334155', flow: 'neutral' },
    };

    /* ── TransType → viewTransModal type key ────────────────────────────── */
    var TYPE_VIEW_MAP = {
        'Invoice':         'invoice',
        'Purchase':        'purchase',
        'SalesReturn':     'salesreturn',
        'PurchaseReturn':  'purchasereturn',
        'SalesOrder':      'salesorder',
        'PurchaseOrder':   'purchaseorder',
        'Quotation':       'quotation',
        'ProformaInvoice': 'proformainvoice',
        'DeliveryChallan': 'deliverychallan',
    };

    /* ── State ───────────────────────────────────────────────────────────── */
    var _currentDate = '';
    var _allEntries  = [];
    var _activeTypes = null; // null = all; otherwise Set of active type keys
    var _fp          = null; // flatpickr instance

    /* ── Init ─────────────────────────────────────────────────────────────── */
    /**
     * @returns {void}
     */
    function init() {
        _currentDate = (typeof _dbInitDate !== 'undefined' && _dbInitDate) ? _dbInitDate : _dbToday;

        var urlParams = new URLSearchParams(window.location.search);
        var urlDate   = urlParams.get('date');

        if (urlDate && /^\d{4}-\d{2}-\d{2}$/.test(urlDate)) {
            _currentDate = urlDate;
        }

        initDatePicker();
        buildTypeBox();
        bindEvents();
        updateDateWrap();

        var urlSearch = urlParams.get('search') || '';
        if (urlSearch) {
            var searchEl = document.getElementById('dbSearch');
            if (searchEl) searchEl.value = urlSearch;
            setSearchActive(true);
        }

        fetchData(_currentDate);
    }

    /* ── Flatpickr date picker ───────────────────────────────────────────── */
    /**
     * @returns {void}
     */
    function initDatePicker() {
        var altFmt = (typeof _dbFormFmt !== 'undefined' && _dbFormFmt) ? _dbFormFmt : 'd-m-Y';
        _fp = flatpickr('#dbDateDisplay', {
            dateFormat:    'Y-m-d',
            altInput:      true,
            altInputClass: 'db-date-input',
            altFormat:     altFmt,
            defaultDate:   _currentDate,
            maxDate:       'today',
            disableMobile: true,
            onChange: function (dates) {
                if (dates.length) {
                    var d = formatDateYMD(dates[0]);
                    _currentDate = d;
                    updateDateWrap();
                    pushUrlState();
                    fetchData(d);
                }
            },
        });
    }

    /* ── Type filter box ─────────────────────────────────────────────────── */
    /**
     * @returns {void}
     */
    function buildTypeBox() {
        var list  = document.getElementById('dbTypeBoxList');
        var badge = document.getElementById('dbTypeBoxBadge');
        if (!list) return;

        var types = Object.keys(TYPE_CFG);
        var html  = '';
        types.forEach(function (t) {
            var cfg = TYPE_CFG[t];
            var id  = 'dbTypeChk_' + t.replace(/[^a-zA-Z0-9]/g, '_');
            html += '<label class="catg-list-item" for="' + id + '">' +
                '<input class="form-check-input db-type-chk" type="checkbox" id="' + id + '" value="' + escHtml(t) + '" checked>' +
                '<span>' + escHtml(cfg.label) + '</span>' +
                '</label>';
        });
        list.innerHTML = html;

        if (badge) badge.textContent = types.length;
    }

    /**
     * @returns {void}
     */
    function showTypeBox() {
        var box = document.getElementById('dbTypeBox');
        var btn = document.getElementById('dbTypeFilterBtn');
        if (!box || !btn) return;

        // Reset search field on open
        var typeSearch = document.getElementById('dbTypeBoxSearch');
        var typeSearchClear = document.getElementById('dbTypeBoxSearchClear');
        if (typeSearch) typeSearch.value = '';
        if (typeSearchClear) typeSearchClear.style.display = 'none';
        document.querySelectorAll('#dbTypeBoxList .catg-list-item').forEach(function (item) {
            item.style.display = '';
        });

        syncBoxToState();

        var rect     = btn.getBoundingClientRect();
        var boxWidth = 240;
        var left     = rect.left;

        if (left + boxWidth > window.innerWidth - 8) {
            left = rect.right - boxWidth;
        }

        box.style.top     = (rect.bottom + 4) + 'px';
        box.style.left    = left + 'px';
        box.style.display = 'flex';
        box.style.flexDirection = 'column';
    }

    /**
     * @returns {void}
     */
    function hideTypeBox() {
        var box = document.getElementById('dbTypeBox');
        if (box) box.style.display = 'none';
    }

    /**
     * Sync checkboxes in the box to match current _activeTypes state.
     * @returns {void}
     */
    function syncBoxToState() {
        var allChk  = document.getElementById('dbTypeBoxSelectAll');
        var checks  = document.querySelectorAll('.db-type-chk');
        var isAll   = (_activeTypes === null);

        checks.forEach(function (c) {
            c.checked = isAll || _activeTypes.has(c.value);
        });

        if (allChk) allChk.checked = isAll;
        syncSelectAll();
    }

    /**
     * Update Select All checkbox based on individual checkboxes.
     * @returns {void}
     */
    function syncSelectAll() {
        var allChk  = document.getElementById('dbTypeBoxSelectAll');
        if (!allChk) return;
        var checks   = document.querySelectorAll('.db-type-chk');
        var total    = checks.length;
        var checked  = 0;
        checks.forEach(function (c) { if (c.checked) checked++; });

        allChk.checked       = checked === total;
        allChk.indeterminate = checked > 0 && checked < total;
    }

    /**
     * @returns {void}
     */
    function updateTypeLabel() {
        var label = document.getElementById('dbTypeFilterLabel');
        var btn   = document.getElementById('dbTypeFilterBtn');
        if (!label) return;

        if (!_activeTypes) {
            label.textContent = 'All Types';
            if (btn) btn.classList.remove('has-filter');
        } else {
            var count = _activeTypes.size;
            if (count === 0) {
                label.textContent = 'All Types';
                if (btn) btn.classList.remove('has-filter');
            } else if (count === 1) {
                var key = Array.from(_activeTypes)[0];
                label.textContent = TYPE_CFG[key] ? TYPE_CFG[key].label : '1 Type';
                if (btn) btn.classList.add('has-filter');
            } else {
                label.textContent = count + ' Types';
                if (btn) btn.classList.add('has-filter');
            }
        }
    }

    /* ── Events ──────────────────────────────────────────────────────────── */
    /**
     * @returns {void}
     */
    function bindEvents() {

        // Today button
        document.getElementById('dbTodayBtn').addEventListener('click', function () {
            setDate(_dbToday);
        });

        // Search expand / clear / URL sync
        var searchInput = document.getElementById('dbSearch');
        var searchClear = document.getElementById('dbSearchClear');

        searchInput.addEventListener('focus', function () {
            document.getElementById('dbSearchWrap').classList.add('is-expanded');
        });

        searchInput.addEventListener('blur', function () {
            if (!this.value) {
                document.getElementById('dbSearchWrap').classList.remove('is-expanded');
            }
        });

        searchInput.addEventListener('input', function () {
            setSearchActive(this.value.length > 0);
            pushUrlState();
            applyFilters();
        });

        searchClear.addEventListener('click', function () {
            searchInput.value = '';
            setSearchActive(false);
            document.getElementById('dbSearchWrap').classList.remove('is-expanded');
            pushUrlState();
            applyFilters();
            searchInput.focus();
        });

        // Type filter box — trigger
        document.getElementById('dbTypeFilterBtn').addEventListener('click', function (e) {
            e.stopPropagation();
            var box = document.getElementById('dbTypeBox');
            if (box && box.style.display !== 'none') {
                hideTypeBox();
            } else {
                showTypeBox();
            }
        });

        // Type box — close button
        document.getElementById('dbTypeBoxClose').addEventListener('click', hideTypeBox);

        // Type box — Select All checkbox
        document.getElementById('dbTypeBoxSelectAll').addEventListener('change', function () {
            document.querySelectorAll('.db-type-chk').forEach(function (c) {
                c.checked = document.getElementById('dbTypeBoxSelectAll').checked;
            });
        });

        // Type box — individual checkboxes
        document.getElementById('dbTypeBoxList').addEventListener('change', function (e) {
            if (e.target && e.target.classList.contains('db-type-chk')) {
                syncSelectAll();
            }
        });

        // Type box — Apply
        document.getElementById('dbTypeBoxApply').addEventListener('click', function () {
            var checks   = document.querySelectorAll('.db-type-chk');
            var total    = checks.length;
            var selected = [];
            checks.forEach(function (c) { if (c.checked) selected.push(c.value); });

            _activeTypes = (selected.length === total) ? null : new Set(selected);
            updateTypeLabel();
            hideTypeBox();
            applyFilters();
        });

        // Type box — Reset
        document.getElementById('dbTypeBoxReset').addEventListener('click', function () {
            document.querySelectorAll('.db-type-chk').forEach(function (c) { c.checked = true; });
            var allChk = document.getElementById('dbTypeBoxSelectAll');
            if (allChk) { allChk.checked = true; allChk.indeterminate = false; }
            _activeTypes = null;
            updateTypeLabel();
            hideTypeBox();
            applyFilters();
        });

        // Type box search
        var typeSearch      = document.getElementById('dbTypeBoxSearch');
        var typeSearchClear = document.getElementById('dbTypeBoxSearchClear');

        if (typeSearch) {
            typeSearch.addEventListener('input', function () {
                var term = this.value.toLowerCase();
                document.querySelectorAll('#dbTypeBoxList .catg-list-item').forEach(function (item) {
                    item.style.display = item.textContent.toLowerCase().indexOf(term) !== -1 ? '' : 'none';
                });
                if (typeSearchClear) typeSearchClear.style.display = this.value ? '' : 'none';
                syncSelectAll();
            });
        }

        if (typeSearchClear) {
            typeSearchClear.addEventListener('click', function () {
                if (typeSearch) typeSearch.value = '';
                document.querySelectorAll('#dbTypeBoxList .catg-list-item').forEach(function (item) {
                    item.style.display = '';
                });
                typeSearchClear.style.display = 'none';
                syncSelectAll();
            });
        }

        // Close box on outside click
        document.addEventListener('click', function (e) {
            var box = document.getElementById('dbTypeBox');
            var btn = document.getElementById('dbTypeFilterBtn');
            if (!box || box.style.display === 'none') return;
            if (!box.contains(e.target) && e.target !== btn && !btn.contains(e.target)) {
                hideTypeBox();
            }
        });

        // Close box on page scroll (but NOT on scroll inside the box itself)
        window.addEventListener('scroll', function (e) {
            var box = document.getElementById('dbTypeBox');
            if (box && box.contains(e.target)) return;
            hideTypeBox();
        }, true);
        window.addEventListener('resize', hideTypeBox);

        // Export handlers
        document.querySelectorAll('.r2k-export-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                handleExport(this.dataset.format);
            });
        });
    }

    /* ── Set date helper ────────────────────────────────────────────────── */
    /**
     * @param {string} dateStr
     * @returns {void}
     */
    function setDate(dateStr) {
        _currentDate = dateStr;
        if (_fp) _fp.setDate(dateStr, false);
        updateDateWrap();
        pushUrlState();
        fetchData(dateStr);
    }

    /**
     * @returns {void}
     */
    function updateDateWrap() {
        var wrap = document.getElementById('dbDateWrap');
        if (!wrap) return;
        wrap.classList.toggle('is-active', _currentDate !== _dbToday);
    }

    /* ── URL state ──────────────────────────────────────────────────────── */
    /**
     * @returns {void}
     */
    function pushUrlState() {
        var url    = new URL(window.location.href);
        var search = (document.getElementById('dbSearch') || {}).value || '';

        if (_currentDate && _currentDate !== _dbToday) {
            url.searchParams.set('date', _currentDate);
        } else {
            url.searchParams.delete('date');
        }

        if (search) {
            url.searchParams.set('search', search);
        } else {
            url.searchParams.delete('search');
        }

        history.replaceState(null, '', url.toString());
    }

    /* ── Search active state ─────────────────────────────────────────────── */
    /**
     * @param {boolean} active
     * @returns {void}
     */
    function setSearchActive(active) {
        var wrap  = document.getElementById('dbSearchWrap');
        var clear = document.getElementById('dbSearchClear');
        if (wrap)  wrap.classList.toggle('r2k-search-active', active);
        if (clear) clear.classList.toggle('d-none', !active);
    }

    /* ── Fetch data ──────────────────────────────────────────────────────── */
    /**
     * @param {string} date
     * @returns {void}
     */
    function fetchData(date) {
        ajaxLoading(0);
        showLoading();
        $.get('/reports/getDayBookData', { date: date }, function (res) {
            ajaxLoading(1);
            if (res.Status === 'Success') {
                _allEntries = res.entries || [];
                renderSummary(_allEntries);
                applyFilters();
            } else {
                showError(res.Message || 'Failed to load data.');
            }
        }).fail(function () {
            ajaxLoading(1);
            showError('Network error. Please try again.');
        });
    }

    /* ── Filter ──────────────────────────────────────────────────────────── */
    /**
     * @returns {void}
     */
    function applyFilters() {
        var search   = (document.getElementById('dbSearch').value || '').toLowerCase().trim();
        var filtered = _allEntries.filter(function (e) {
            var matchType   = !_activeTypes || _activeTypes.has(e.TransType);
            var matchSearch = !search ||
                (e.PartyName    || '').toLowerCase().indexOf(search) !== -1 ||
                (e.SerialNumber || '').toLowerCase().indexOf(search) !== -1 ||
                (e.PartyArea    || '').toLowerCase().indexOf(search) !== -1 ||
                (e.MobileNumber || '').toLowerCase().indexOf(search) !== -1;
            return matchType && matchSearch;
        });

        renderTable(filtered);
        renderFooter(filtered);
    }

    /* ── Render summary cards ────────────────────────────────────────────── */
    /**
     * @param {Array} entries
     * @returns {void}
     */
    function renderSummary(entries) {
        var salesTotal   = 0;
        var collectTotal = 0;
        var payoutTotal  = 0;

        entries.forEach(function (e) {
            var amt = parseFloat(e.Amount) || 0;
            if (e.TransType === 'Invoice')     salesTotal   += amt;
            if (e.TransType === 'Payment In')  collectTotal += amt;
            if (e.TransType === 'Payment Out') payoutTotal  += amt;
        });

        var net    = collectTotal - payoutTotal;
        var netCls = net >= 0 ? 'db-amt-in' : 'db-amt-out';

        setText('statTotal',       entries.length);
        setText('statSales',       fmtAmt(salesTotal));
        setText('statCollections', fmtAmt(collectTotal));

        var netEl = document.getElementById('statNet');
        if (netEl) {
            netEl.textContent = (net >= 0 ? '+' : '') + fmtAmt(Math.abs(net));
            netEl.className   = 'db-stat-val ' + netCls;
        }
    }

    /* ── Render table ────────────────────────────────────────────────────── */
    /**
     * @param {Array} entries
     * @returns {void}
     */
    function renderTable(entries) {
        var tbody = document.getElementById('dbTableBody');

        if (entries.length === 0) {
            tbody.innerHTML =
                '<tr><td colspan="7"><div class="db-empty">' +
                '<i class="bx bx-calendar-x"></i>' +
                '<div class="db-empty-title">No entries found</div>' +
                '<div class="db-empty-sub">No transactions recorded for this date matching your filters.</div>' +
                '</div></td></tr>';
            document.getElementById('dbTableFooter').classList.add('d-none');
            return;
        }

        tbody.innerHTML = entries.map(buildRow).join('');
        document.getElementById('dbTableFooter').classList.remove('d-none');
    }

    /**
     * @param {Object} e
     * @returns {string}
     */
    function buildRow(e) {
        var cfg    = TYPE_CFG[e.TransType] || { label: e.TransType, badge: 'db-badge-default', flow: 'neutral' };
        var amtCls = cfg.flow === 'in' ? 'db-amt-in' : (cfg.flow === 'out' ? 'db-amt-out' : 'db-amt-neutral');
        var serial = escHtml(e.SerialNumber || '—');
        var serialCell;

        if (e.Source === 'payment') {
            serialCell = e.ReceiptToken
                ? '<a href="/receipt/' + encodeURIComponent(e.ReceiptToken) + '" target="_blank" class="db-serial-link">' + serial + '</a>'
                : serial;
        } else {
            var viewType = TYPE_VIEW_MAP[e.TransType];
            serialCell = viewType
                ? '<a href="javascript:void(0)" class="db-serial-link viewTransaction"' +
                  ' data-uid="'    + escHtml(e.TransUID    || '') + '"' +
                  ' data-module="' + escHtml(e.ModuleUID   || '') + '"' +
                  ' data-type="'   + viewType                     + '"' +
                  ' data-number="' + serial                       + '"' +
                  ' data-date="'   + escHtml(_currentDate  || '') + '"' +
                  ' data-status="' + escHtml(e.DocStatus   || '') + '">' +
                  serial + '</a>'
                : serial;
        }

        var modeCell = e.PaymentMode
            ? '<span class="db-mode-chip">' + escHtml(e.PaymentMode) + '</span>'
            : '<span class="db-mode-na">—</span>';

        var isCustomer  = e.PartyType === 'C';
        var ptypeCell   = isCustomer
            ? '<span class="db-ptype-c"><i class="bx bx-user"></i>Customer</span>'
            : '<span class="db-ptype-s"><i class="bx bx-store"></i>Vendor</span>';

        var partyCell = '<div class="db-party-name">' + escHtml(e.PartyName || '—') + '</div>';
        if (e.PartyArea)    partyCell += '<div class="db-party-sub"><i class="bx bx-map"></i>' + escHtml(e.PartyArea) + '</div>';
        if (e.MobileNumber) partyCell += '<div class="db-party-sub"><i class="bx bx-phone"></i>' + escHtml(e.MobileNumber) + '</div>';

        return '<tr>' +
            '<td class="db-col-time">'                        + fmtTime(e.EntryTime) + '</td>' +
            '<td>'                                            + ptypeCell + '</td>' +
            '<td class="db-col-party">'                       + partyCell + '</td>' +
            '<td><span class="db-badge ' + cfg.badge + '">'  + escHtml(cfg.label) + '</span></td>' +
            '<td>'                                            + serialCell + '</td>' +
            '<td>'                                            + modeCell + '</td>' +
            '<td class="db-col-amount ' + amtCls + '">'      + fmtAmt(parseFloat(e.Amount) || 0) + '</td>' +
            '</tr>';
    }

    /* ── Render footer totals ────────────────────────────────────────────── */
    /**
     * @param {Array} entries
     * @returns {void}
     */
    function renderFooter(entries) {
        var inTotal  = 0;
        var outTotal = 0;

        entries.forEach(function (e) {
            var cfg = TYPE_CFG[e.TransType] || { flow: 'neutral' };
            var amt = parseFloat(e.Amount) || 0;
            if (cfg.flow === 'in')  inTotal  += amt;
            if (cfg.flow === 'out') outTotal += amt;
        });

        var net    = inTotal - outTotal;
        var netCls = net >= 0 ? 'text-success' : 'text-danger';

        setText('dbFooterCount', entries.length + ' entries');
        setText('dbFooterIn',    fmtAmt(inTotal));
        setText('dbFooterOut',   fmtAmt(outTotal));

        var netEl = document.getElementById('dbFooterNet');
        if (netEl) {
            netEl.textContent = (net >= 0 ? '+' : '') + fmtAmt(Math.abs(net));
            netEl.className   = netCls;
        }
    }

    /* ── Export handlers ─────────────────────────────────────────────────── */
    /**
     * @param {string} format
     * @returns {void}
     */
    function handleExport(format) {
        if (format === 'print' || format === 'pdf') {
            window.print();
            return;
        }

        var filtered = getFilteredEntries();

        if (format === 'csv') {
            downloadFile(buildCsv(filtered), 'daybook-' + _currentDate + '.csv', 'text/csv;charset=utf-8;');
        } else if (format === 'excel') {
            downloadFile(buildCsv(filtered), 'daybook-' + _currentDate + '.xlsx', 'application/vnd.ms-excel');
        }
    }

    /**
     * @returns {Array}
     */
    function getFilteredEntries() {
        var search = (document.getElementById('dbSearch').value || '').toLowerCase().trim();
        return _allEntries.filter(function (e) {
            var matchType   = !_activeTypes || _activeTypes.has(e.TransType);
            var matchSearch = !search ||
                (e.PartyName    || '').toLowerCase().indexOf(search) !== -1 ||
                (e.SerialNumber || '').toLowerCase().indexOf(search) !== -1 ||
                (e.PartyArea    || '').toLowerCase().indexOf(search) !== -1 ||
                (e.MobileNumber || '').toLowerCase().indexOf(search) !== -1;
            return matchType && matchSearch;
        });
    }

    /**
     * @param {Array} entries
     * @returns {string}
     */
    function buildCsv(entries) {
        var rows = [['Date', 'Time', 'Party Type', 'Party Name', 'Village / Area', 'Mobile', 'Transaction Type', 'Serial #', 'Payment Mode', 'Amount']];
        entries.forEach(function (e) {
            var cfg = TYPE_CFG[e.TransType] || { label: e.TransType };
            rows.push([
                _currentDate,
                fmtTime(e.EntryTime),
                e.PartyType === 'C' ? 'Customer' : 'Vendor',
                e.PartyName    || '',
                e.PartyArea    || '',
                e.MobileNumber || '',
                cfg.label,
                e.SerialNumber || '',
                e.PaymentMode  || '',
                (parseFloat(e.Amount) || 0).toFixed(2),
            ]);
        });
        return rows.map(function (row) {
            return row.map(function (cell) {
                return '"' + String(cell).replace(/"/g, '""') + '"';
            }).join(',');
        }).join('\n');
    }

    /**
     * @param {string} content
     * @param {string} filename
     * @param {string} mimeType
     * @returns {void}
     */
    function downloadFile(content, filename, mimeType) {
        var blob = new Blob([content], { type: mimeType });
        var url  = URL.createObjectURL(blob);
        var a    = document.createElement('a');
        a.href     = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    /* ── URL builder ─────────────────────────────────────────────────────── */
    /**
     * @param {Object} e
     * @returns {string|null}
     */
    /* ── Loading / error states ──────────────────────────────────────────── */
    /**
     * @returns {void}
     */
    function showLoading() {
        document.getElementById('dbTableBody').innerHTML =
            '<tr><td colspan="7" class="db-loading-cell">' +
            '<div class="db-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div>' +
            '</td></tr>';
        document.getElementById('dbTableFooter').classList.add('d-none');
        setText('statTotal', '—');
        setText('statSales', '—');
        setText('statCollections', '—');
        setText('statNet', '—');
    }

    /**
     * @param {string} msg
     * @returns {void}
     */
    function showError(msg) {
        document.getElementById('dbTableBody').innerHTML =
            '<tr><td colspan="7"><div class="db-empty">' +
            '<i class="bx bx-error-circle" style="color:#ef4444;"></i>' +
            '<div class="db-empty-title">Failed to load</div>' +
            '<div class="db-empty-sub">' + escHtml(msg) + '</div>' +
            '</div></td></tr>';
        document.getElementById('dbTableFooter').classList.add('d-none');
    }

    /* ── Helpers ─────────────────────────────────────────────────────────── */
    /**
     * @param {number} n
     * @returns {string}
     */
    function fmtAmt(n) {
        return n.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    /**
     * @param {string} timeStr  HH:MM:SS from DB
     * @returns {string}
     */
    function fmtTime(timeStr) {
        if (!timeStr) return '—';
        var parts = timeStr.split(':');
        if (parts.length < 2) return timeStr;
        var h    = parseInt(parts[0], 10);
        var m    = parts[1];
        var ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        return h + ':' + m + ' ' + ampm;
    }

    /**
     * @param {Date} d
     * @returns {string}
     */
    function formatDateYMD(d) {
        var yy = d.getFullYear();
        var mm = String(d.getMonth() + 1).padStart(2, '0');
        var dd = String(d.getDate()).padStart(2, '0');
        return yy + '-' + mm + '-' + dd;
    }

    /**
     * @param {string} str
     * @returns {string}
     */
    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    /**
     * @param {string} id
     * @param {string|number} val
     * @returns {void}
     */
    function setText(id, val) {
        var el = document.getElementById(id);
        if (el) el.textContent = val;
    }

    /* ── Boot ─────────────────────────────────────────────────────────────── */
    $(document).ready(init);

})();
