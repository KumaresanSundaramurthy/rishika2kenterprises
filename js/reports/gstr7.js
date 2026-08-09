(function () {
    'use strict';

    var _dec  = (typeof genSettings !== 'undefined' && genSettings.DecimalPlaces != null) ? parseInt(genSettings.DecimalPlaces, 10) : 2;
    var _curr = (typeof genSettings !== 'undefined' && genSettings.CurrencySymbol) ? genSettings.CurrencySymbol : '₹';

    /**
     * @param {string} s
     * @returns {string}
     */
    function _esc(s) {
        if (s == null) return '';
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    /**
     * @param {number|string} n
     * @returns {string}
     */
    function _fmt(n) {
        var v = parseFloat(n) || 0;
        return _curr + ' ' + v.toFixed(_dec).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    /**
     * @param {number|string} n
     * @returns {string}
     */
    function _num(n) { return (parseFloat(n) || 0).toFixed(_dec); }

    /**
     * @param {string} iso
     * @returns {string}
     */
    function _fmtDate(iso) {
        if (!iso) return '—';
        var d = new Date(iso);
        if (isNaN(d.getTime())) return iso;
        return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    /**
     * @param {string} status
     * @returns {string}
     */
    function _statusBadge(status) {
        var cls = status === 'Approved' ? 'reg-status-issued' : 'reg-status-other';
        return '<span class="reg-status ' + cls + '">' + _esc(status) + '</span>';
    }

    /**
     * @param {number|string} isPaid
     * @returns {string}
     */
    function _paidBadge(isPaid) {
        return parseInt(isPaid, 10) === 1
            ? '<span class="exr-paid-chip">Paid</span>'
            : '<span class="exr-unpaid-chip">Unpaid</span>';
    }

    /** @type {Array<Object>} */
    var _rows = [];

    /**
     * @param {Array<Object>} rows
     * @returns {void}
     */
    function _render(rows) {
        var tbody = document.getElementById('g7TableBody');
        var tfoot = document.getElementById('g7TableFoot');

        if (!rows || rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="12">' +
                '<div class="rpt-empty"><i class="bx bx-minus-circle"></i>' +
                '<div class="rpt-empty-title">No TDS entries found</div>' +
                '<div>No expenses with TDS for selected date range</div></div></td></tr>';
            tfoot.classList.add('d-none');
            _updateStats([], 0, 0);
            return;
        }

        var totBase = 0, totTds = 0, totNet = 0;
        var uniqueVendors = {};
        var html = '';

        rows.forEach(function (r, i) {
            var base = parseFloat(r.BaseAmount) || 0;
            var tds  = parseFloat(r.TdsAmount)  || 0;
            var net  = parseFloat(r.NetAmount)  || 0;
            totBase += base; totTds += tds; totNet += net;
            if (r.VendorName && r.VendorName !== '—') uniqueVendors[r.VendorName] = true;

            html += '<tr>' +
                '<td>' + (i + 1) + '</td>' +
                '<td>' + _esc(r.DocNo) + '</td>' +
                '<td>' + _fmtDate(r.TransDate) + '</td>' +
                '<td>' + _esc(r.VendorName) + '</td>' +
                '<td>' + _esc(r.VendorGSTIN || '—') + '</td>' +
                '<td><span class="g7-section-badge">' + _esc(r.TdsSection) + '</span></td>' +
                '<td class="text-muted small">' + _esc(r.TdsSectionDesc) + '</td>' +
                '<td class="rpt-col-num">' + _num(base) + '</td>' +
                '<td class="rpt-col-num">' + _num(r.TdsPct) + '%</td>' +
                '<td class="rpt-col-num rpt-num-red">' + _num(tds) + '</td>' +
                '<td class="rpt-col-num rpt-num-blue">' + _num(net) + '</td>' +
                '<td>' + _statusBadge(r.DocStatus) + '</td>' +
                '</tr>';
        });
        tbody.innerHTML = html;

        document.getElementById('g7FtBase').textContent = _num(totBase);
        document.getElementById('g7FtTds').textContent  = _num(totTds);
        document.getElementById('g7FtNet').textContent  = _num(totNet);
        tfoot.classList.remove('d-none');
        _updateStats(rows, Object.keys(uniqueVendors).length, totBase, totTds);
    }

    /**
     * @param {Array<Object>} rows
     * @param {number}        vendorCount
     * @param {number}        totBase
     * @param {number}        totTds
     * @returns {void}
     */
    function _updateStats(rows, vendorCount, totBase, totTds) {
        document.getElementById('g7StatCount').textContent   = rows.length;
        document.getElementById('g7StatVendors').textContent = vendorCount || 0;
        document.getElementById('g7StatBase').textContent    = _fmt(totBase || 0);
        document.getElementById('g7StatTds').textContent     = _fmt(totTds  || 0);
    }

    /** @returns {void} */
    function _fetch() {
        var from = document.getElementById('g7From').value;
        var to   = document.getElementById('g7To').value;

        ajaxLoading(1);
        document.getElementById('g7TableBody').innerHTML =
            '<tr><td colspan="12" class="rpt-loading-cell">' +
            '<div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div>' +
            '</td></tr>';
        document.getElementById('g7TableFoot').classList.add('d-none');

        $.get('/reports/getGstr7Data', { from: from, to: to })
            .done(function (res) {
                if (res.Status !== 'Success') {
                    document.getElementById('g7TableBody').innerHTML =
                        '<tr><td colspan="12" class="text-center text-danger py-4">' + _esc(res.Message || 'Error loading data') + '</td></tr>';
                    return;
                }
                _rows = res.rows || [];
                _render(_rows);
            })
            .fail(function () {
                document.getElementById('g7TableBody').innerHTML =
                    '<tr><td colspan="12" class="text-center text-danger py-4">Request failed. Please try again.</td></tr>';
            })
            .always(function () { ajaxLoading(0); });
    }

    /** @returns {void} */
    function _initDatePickers() {
        var initFrom = (typeof _g7InitFrom !== 'undefined') ? _g7InitFrom : '';
        var initTo   = (typeof _g7InitTo   !== 'undefined') ? _g7InitTo   : '';

        flatpickr('#g7FromDisplay', {
            static: true,
            position: 'below left',
            dateFormat: 'Y-m-d',
            defaultDate: initFrom || undefined,
            onChange: function (sel, dateStr) { document.getElementById('g7From').value = dateStr; }
        });
        if (initFrom) document.getElementById('g7FromDisplay').value =
            new Date(initFrom).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });

        flatpickr('#g7ToDisplay', {
            static: true,
            position: 'below left',
            dateFormat: 'Y-m-d',
            defaultDate: initTo || undefined,
            onChange: function (sel, dateStr) { document.getElementById('g7To').value = dateStr; }
        });
        if (initTo) document.getElementById('g7ToDisplay').value =
            new Date(initTo).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    /** @returns {void} */
    function _init() {
        _initDatePickers();
        document.getElementById('g7ApplyBtn').addEventListener('click', _fetch);
        _fetch();
    }

    $(document).ready(_init);
})();
