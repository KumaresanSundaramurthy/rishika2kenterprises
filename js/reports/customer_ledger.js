(function () {
    'use strict';

    var _sym = (genSettings && genSettings.CurrenySymbol) ? genSettings.CurrenySymbol : '₹';
    var _dec = 2;
    var _customerUID   = 0;
    var _clCustName    = '';
    var _clCustomers   = null;  // null = not loaded, Array = ready
    var _clCustQueue   = [];
    var _clCustAttempts= 0;
    var _clDropTimer;

    /**
     * @param {number} n
     * @returns {string}
     */
    function _fmt(n) {
        return _sym + ' ' + parseFloat(n || 0).toFixed(_dec);
    }

    /**
     * @param {string} str
     * @returns {string}
     */
    function _esc(str) {
        return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    /**
     * @param {string} isoDate
     * @returns {string}
     */
    function _fmtDate(isoDate) {
        if (!isoDate) { return '—'; }
        var d = new Date(isoDate + 'T00:00:00');
        if (isNaN(d.getTime())) { return isoDate; }
        var fmt = _clListFmt || 'd M Y';
        var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        return fmt
            .replace('d', ('0' + d.getDate()).slice(-2))
            .replace('M', months[d.getMonth()])
            .replace('m', ('0' + (d.getMonth() + 1)).slice(-2))
            .replace('Y', d.getFullYear());
    }

    /**
     * @param {string} txType
     * @returns {string}
     */
    function _typeChip(txType) {
        var map = {
            'Invoice':          ['cl-type-invoice',  'bx-receipt',      'Invoice'],
            'Sales Return':     ['cl-type-return',   'bx-arrow-back',   'Sales Return'],
            'Payment Received': ['cl-type-payment',  'bx-money',        'Payment Received'],
            'Opening Balance':  ['',                 'bx-wallet',       'Opening Balance']
        };
        var info = map[txType] || ['', 'bx-circle', _esc(txType)];
        var chipCls = info[0] ? 'cl-type-chip ' + info[0] : '';
        return chipCls
            ? '<span class="' + chipCls + '"><i class="bx ' + info[1] + '"></i>' + _esc(info[2]) + '</span>'
            : '<span style="font-size:var(--r2k-text-78);color:#64748b">' + _esc(info[2]) + '</span>';
    }

    /**
     * @param {Array}  rows
     * @param {{debit:number,credit:number}} opening
     * @returns {void}
     */
    function _render(rows, opening) {
        var tbody  = document.getElementById('clTableBody');
        var tfoot  = document.getElementById('clTableFoot');
        var footer = document.getElementById('clTblFooter');
        var statsRow = document.getElementById('clStatsRow');

        var obDebit  = parseFloat(opening.debit  || 0);
        var obCredit = parseFloat(opening.credit || 0);
        var runBal   = obDebit - obCredit;

        var html = '';
        var rowNum = 0;

        if (obDebit > 0.005 || obCredit > 0.005) {
            rowNum++;
            html += '<tr class="cl-ob-row">';
            html += '<td>' + rowNum + '</td>';
            html += '<td>—</td>';
            html += '<td>' + _typeChip('Opening Balance') + '</td>';
            html += '<td><em>Opening Balance</em></td>';
            html += '<td class="rpt-col-num">' + (obDebit  > 0.005 ? _fmt(obDebit)  : '—') + '</td>';
            html += '<td class="rpt-col-num">' + (obCredit > 0.005 ? _fmt(obCredit) : '—') + '</td>';
            html += '<td class="rpt-col-num ' + (runBal >= 0 ? 'rpt-num-blue' : 'rpt-num-green') + '">' + _fmt(Math.abs(runBal)) + (runBal < 0 ? ' Cr' : ' Dr') + '</td>';
            html += '</tr>';
        }

        if (!rows || rows.length === 0) {
            if (rowNum === 0) {
                tbody.innerHTML = '<tr><td colspan="7"><div class="rpt-empty"><i class="bx bx-info-circle"></i><div class="rpt-empty-title">No transactions found</div><div style="font-size:var(--r2k-text-82)">Try a different date range</div></div></td></tr>';
                tfoot.classList.add('d-none');
                footer.classList.add('d-none');
                statsRow.style.setProperty('display', 'none', 'important');
                _updateStats(obDebit, obCredit, 0, 0, runBal);
                return;
            }
        }

        var totDebit = 0, totCredit = 0;

        (rows || []).forEach(function (r) {
            rowNum++;
            var dr = parseFloat(r.Debit  || 0);
            var cr = parseFloat(r.Credit || 0);
            runBal   += dr - cr;
            totDebit  += dr;
            totCredit += cr;

            html += '<tr>';
            html += '<td>' + rowNum + '</td>';
            html += '<td>' + _fmtDate(r.TxDate) + '</td>';
            html += '<td>' + _typeChip(r.TxType) + '</td>';
            html += '<td>' + _esc(r.RefNo || '—') + '</td>';
            html += '<td class="rpt-col-num">' + (dr > 0.005 ? _fmt(dr) : '—') + '</td>';
            html += '<td class="rpt-col-num">' + (cr > 0.005 ? _fmt(cr) : '—') + '</td>';
            html += '<td class="rpt-col-num ' + (runBal >= 0 ? 'rpt-num-blue' : 'rpt-num-green') + '">' + _fmt(Math.abs(runBal)) + (runBal < 0 ? ' Cr' : ' Dr') + '</td>';
            html += '</tr>';
        });

        tbody.innerHTML = html;

        document.getElementById('clFtDebit').textContent   = _fmt(totDebit);
        document.getElementById('clFtCredit').textContent  = _fmt(totCredit);
        document.getElementById('clFtBalance').textContent = _fmt(Math.abs(runBal)) + (runBal < 0 ? ' Cr' : ' Dr');
        tfoot.classList.remove('d-none');

        document.getElementById('clRowCount').textContent       = (rows ? rows.length : 0) + ' transaction' + ((rows && rows.length !== 1) ? 's' : '');
        document.getElementById('clFooterDebit').textContent    = _fmt(totDebit);
        document.getElementById('clFooterBalance').textContent  = _fmt(Math.abs(runBal)) + (runBal < 0 ? ' Cr' : ' Dr');
        footer.classList.remove('d-none');

        statsRow.style.removeProperty('display');
        _updateStats(obDebit, obCredit, totDebit, totCredit, runBal);
    }

    /**
     * @param {number} obDebit
     * @param {number} obCredit
     * @param {number} totDebit
     * @param {number} totCredit
     * @param {number} closing
     * @returns {void}
     */
    function _updateStats(obDebit, obCredit, totDebit, totCredit, closing) {
        var obAbs  = Math.abs(obDebit - obCredit);
        var obSide = (obDebit - obCredit) >= 0 ? ' Dr' : ' Cr';
        document.getElementById('clStatOpening').textContent = _fmt(obAbs) + obSide;
        document.getElementById('clStatDebit').textContent   = _fmt(totDebit);
        document.getElementById('clStatCredit').textContent  = _fmt(totCredit);
        var closingAbs  = Math.abs(closing);
        var closingSide = closing >= 0 ? ' Dr' : ' Cr';
        document.getElementById('clStatClosing').textContent = _fmt(closingAbs) + closingSide;
    }

    /**
     * Load all customers from Upstash cache once; retries up to 3 times on empty.
     * @param {Function} cb
     * @returns {void}
     */
    function _clCustLoad(cb) {
        if (_clCustomers !== null) { cb(_clCustomers); return; }
        _clCustQueue.push(cb);
        if (_clCustQueue.length > 1) { return; }

        /** @param {Array} list @returns {void} */
        function _flush(list) {
            var cbs = _clCustQueue.slice(); _clCustQueue = [];
            cbs.forEach(function (fn) { fn(list); });
        }
        /** @param {Array} list @returns {void} */
        function _done(list) {
            _clCustAttempts++;
            if (list.length || _clCustAttempts >= 3) { _clCustomers = list; }
            _flush(list);
        }

        if (typeof UpstashService === 'undefined' || !UpstashService.isEnabled()) { _clCustomers = []; _flush([]); return; }

        UpstashService.hgetall(UpstashService.orgKey('customers')).then(function (map) {
            if (!map || typeof map !== 'object' || !Object.keys(map).length) { _done([]); return; }
            var list = Object.keys(map).map(function (uid) {
                var c = map[uid];
                return {
                    id    : parseInt(c.CustomerUID || uid, 10),
                    name  : c.Name         || '',
                    area  : c.Area         || '',
                    mobile: c.MobileNumber || '',
                };
            }).filter(function (c) { return c.id && c.name; })
              .sort(function (a, b) { return a.name.localeCompare(b.name); });
            _done(list);
        }).catch(function () { _done([]); });
    }

    /**
     * Render customer autocomplete dropdown filtered by term.
     * @param {string} term
     * @returns {void}
     */
    function _clShowCustDropdown(term) {
        var $drop  = $('#clCustDropdown');
        var lcTerm = (term || '').toLowerCase();
        $drop.html('<div class="cl-cust-loading"><i class="bx bx-loader-alt bx-spin"></i></div>').show();

        /** @param {Array} list @returns {void} */
        function _render(list) {
            var filtered = lcTerm
                ? list.filter(function (c) {
                    return c.name.toLowerCase().indexOf(lcTerm) > -1 ||
                           (c.area   && c.area.toLowerCase().indexOf(lcTerm)   > -1) ||
                           (c.mobile && c.mobile.toLowerCase().indexOf(lcTerm) > -1);
                  })
                : list;
            filtered = filtered.slice(0, 30);
            if (!filtered.length) {
                $drop.html('<div class="cl-cust-empty">No customers found</div>');
                return;
            }
            var html = '';
            filtered.forEach(function (c) {
                var w   = (c.name || '').trim().split(/\s+/);
                var ini = (((w[0]||'')[0]||'') + ((w[1]||'')[0]||'')).toUpperCase() || '?';
                html += '<div class="cl-cust-item" data-uid="' + c.id + '" data-name="' + _esc(c.name) + '">';
                html += '<span class="cl-cust-av">' + _esc(ini) + '</span>';
                html += '<div class="cl-cust-info"><span class="cl-cust-name">' + _esc(c.name) + '</span>';
                if (c.area || c.mobile) {
                    html += '<span class="cl-cust-meta">' + [c.area, c.mobile].filter(Boolean).map(function (s) { return _esc(s); }).join(' · ') + '</span>';
                }
                html += '</div></div>';
            });
            $drop.html(html);
        }

        _clCustLoad(function (list) { _render(list); });
    }

    /**
     * @returns {void}
     */
    function _fetch() {
        if (_customerUID <= 0) {
            showToastNotification('Please select a customer.', 'error');
            return;
        }

        var from = $('#clFrom').val();
        var to   = $('#clTo').val();

        if (!from || !to) {
            showToastNotification('Please select a date range.', 'error');
            return;
        }

        var $tbody = $('#clTableBody');
        $tbody.html('<tr><td colspan="7" class="rpt-loading-cell"><div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div></td></tr>');
        $('#clTableFoot').addClass('d-none');
        $('#clTblFooter').addClass('d-none');

        $.ajax({
            url     : global_services_url + 'api/reports/getCustomerLedgerData',
            type    : 'GET',
            dataType: 'json',
            headers : { Authorization: 'Bearer ' + JwtToken },
            data    : { orgId: parseInt(JwtData.Org.OrgUID, 10), customerUID: _customerUID, from: from, to: to },
            success : function (res) {
                if (!res || res.Error) {
                    $tbody.html('<tr><td colspan="7" class="text-center p-4 text-danger">' + _esc((res && res.Message) ? res.Message : 'Failed to load data') + '</td></tr>');
                    return;
                }
                var opening = res.opening || { debit: 0, credit: 0 };
                _render(res.rows || [], opening);
            },
            error   : function () {
                $tbody.html('<tr><td colspan="7" class="text-center p-4 text-danger">Network error. Please try again.</td></tr>');
            }
        });
    }

    /**
     * @returns {void}
     */
    function _initDatePickers() {
        var fromInput    = document.getElementById('clFrom');
        var toInput      = document.getElementById('clTo');
        var fromDisplay  = document.getElementById('clFromDisplay');
        var toDisplay    = document.getElementById('clToDisplay');

        var fmt = typeof _clListFmt !== 'undefined' ? _clListFmt : 'd M Y';
        var fpFmt = fmt
            .replace('d', 'd').replace('M', 'M').replace('m', 'm').replace('Y', 'Y');

        flatpickr(fromDisplay, {
            dateFormat: 'Y-m-d',
            altInput: false,
            defaultDate: fromInput.value || _clInitFrom,
            onChange: function (sel, str) {
                fromInput.value = str;
                var inst = fromDisplay._flatpickr;
                inst.setDate(str, false);
                fromDisplay.value = str ? new Date(str + 'T00:00:00').toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '';
            }
        });

        flatpickr(toDisplay, {
            dateFormat: 'Y-m-d',
            altInput: false,
            defaultDate: toInput.value || _clInitTo,
            onChange: function (sel, str) {
                toInput.value = str;
                var inst = toDisplay._flatpickr;
                inst.setDate(str, false);
                toDisplay.value = str ? new Date(str + 'T00:00:00').toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '';
            }
        });

        if (fromInput.value) {
            fromDisplay.value = new Date(fromInput.value + 'T00:00:00').toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        }
        if (toInput.value) {
            toDisplay.value = new Date(toInput.value + 'T00:00:00').toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        }
    }

    /**
     * Wire the cache-based customer autocomplete (focus/input → dropdown, click → select, outside → close).
     * @returns {void}
     */
    function _initCustPicker() {
        $('#clCustSearch').on('focus input', function () {
            clearTimeout(_clDropTimer);
            var term = $(this).val().trim();
            _clDropTimer = setTimeout(function () { _clShowCustDropdown(term); }, 160);
        });

        $(document).on('mousedown', function (e) {
            if (!$(e.target).closest('.cl-cust-wrap').length) {
                $('#clCustDropdown').hide().empty();
            }
        });

        $(document).on('click', '.cl-cust-item', function () {
            _customerUID = parseInt($(this).data('uid'), 10) || 0;
            _clCustName  = $(this).data('name') || '';
            $('#clCustSearch').val(_clCustName);
            $('#clCustomerUID').val(_customerUID);
            $('#clCustClear').removeClass('d-none');
            $('#clCustDropdown').hide().empty();
        });

        $('#clCustClear').on('click', function () {
            _customerUID = 0;
            _clCustName  = '';
            $('#clCustSearch').val('').trigger('focus');
            $('#clCustomerUID').val('0');
            $(this).addClass('d-none');
            $('#clCustDropdown').hide().empty();
        });

        /* Pre-load cache on page ready so first focus is instant */
        _clCustLoad(function () {});
    }

    /* ── Export helpers ──────────────────────────────────────────────────── */

    var _lastFrom = '';
    var _lastTo   = '';
    var _lastName = '';

    /**
     * Map lowercase format name to the ExportType the services endpoint expects.
     * @param {string} format
     * @returns {string}
     */
    function _exportType(format) {
        if (format === 'pdf')   { return 'Pdf'; }
        if (format === 'print') { return 'Print'; }
        if (format === 'excel') { return 'Excel'; }
        return 'CSV';
    }

    /**
     * Handles export button clicks (Print / PDF / CSV / Excel).
     * @param {string} format
     * @returns {void}
     */
    function _handleExport(format) {
        if (_customerUID <= 0) {
            showToastNotification('Please select a customer and apply filters first.', 'error');
            return;
        }

        var from = $('#clFrom').val() || '';
        var to   = $('#clTo').val()   || '';

        if (!from || !to) {
            showToastNotification('Please select a date range.', 'error');
            return;
        }

        var type    = _exportType(format);
        var payload = {
            orgId      : parseInt(JwtData.Org.OrgUID, 10),
            customerUID: _customerUID,
            from       : from,
            to         : to,
            Type       : type,
            langCode   : (JwtData.User && JwtData.User.LangCode) ? JwtData.User.LangCode : 'en'
        };

        if (type === 'Print') {
            /* Print: server returns { HtmlData } — open in new window */
            $.ajax({
                url        : global_services_url + 'api/reports/exportCustomerLedger',
                method     : 'POST',
                contentType: 'application/json',
                headers    : { Authorization: 'Bearer ' + JwtToken },
                data       : JSON.stringify(payload),
                dataType   : 'json',
                success    : function (resp) {
                    if (!resp || resp.Error || !resp.HtmlData) {
                        showToastNotification((resp && resp.Message) ? resp.Message : 'Export failed.', 'error');
                        return;
                    }
                    var win = window.open('', '_blank');
                    win.document.open(); win.document.write(resp.HtmlData); win.document.close();
                },
                error      : function () { showToastNotification('Export failed. Please try again.', 'error'); }
            });

        } else {
            /* PDF / CSV / Excel: server returns a file blob — download it directly */
            var ext = type === 'Excel' ? 'xlsx' : (type === 'Pdf' ? 'pdf' : 'csv');
            $.ajax({
                url        : global_services_url + 'api/reports/exportCustomerLedger',
                method     : 'POST',
                contentType: 'application/json',
                headers    : { Authorization: 'Bearer ' + JwtToken },
                data       : JSON.stringify(payload),
                xhrFields  : { responseType: 'blob' },
                success    : function (blob) {
                    var safeName = (_lastName || 'customer').replace(/[^a-z0-9]/gi, '_').toLowerCase();
                    var url  = URL.createObjectURL(blob);
                    var $a   = $('<a>').attr({ href: url, download: 'Customer_Ledger_' + safeName + '.' + ext }).appendTo('body');
                    $a[0].click();
                    setTimeout(function () { URL.revokeObjectURL(url); $a.remove(); }, 3000);
                },
                error      : function () { showToastNotification('Export failed. Please try again.', 'error'); }
            });
        }
    }

    /**
     * @returns {void}
     */
    function _init() {
        _initDatePickers();
        _initCustPicker();

        $('#clApplyBtn').on('click', function () {
            _lastFrom = $('#clFrom').val() || '';
            _lastTo   = $('#clTo').val()   || '';
            _lastName = _clCustName;
            _fetch();
        });

        $(document).on('click', '.r2k-export-btn[data-format]', function () {
            _handleExport($(this).data('format'));
        });
    }

    $(document).ready(_init);
}());
