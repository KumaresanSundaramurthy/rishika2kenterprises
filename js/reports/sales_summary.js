/**
 * Sales Summary Report
 * Fetches Invoice (103) vs Sales Return (106) data grouped by period.
 */

(function () {
    'use strict';

    var MONTHS = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

    var _from    = _ssInitFrom;
    var _to      = _ssInitTo;
    var _groupBy = _ssGroupBy;
    var _rows    = [];
    var _fpFrom  = null;
    var _fpTo    = null;

    var _cur = (typeof genSettings !== 'undefined' && genSettings.CurrenySymbol) ? genSettings.CurrenySymbol : '₹';
    var _dec = (typeof genSettings !== 'undefined' && genSettings.DecimalPoints) ? parseInt(genSettings.DecimalPoints, 10) : 2;

    /**
     * @param {number} n
     * @returns {string}
     */
    function _fmt(n) {
        return _cur + ' ' + parseFloat(n || 0).toLocaleString('en-IN', {
            minimumFractionDigits : _dec,
            maximumFractionDigits : _dec,
        });
    }

    /**
     * @param {string} period  e.g. '2026-01', '2026-Q2', '2026'
     * @param {string} groupBy 'month'|'quarter'|'year'
     * @returns {string}
     */
    function _formatPeriod(period, groupBy) {
        if (groupBy === 'year')    return period;
        if (groupBy === 'quarter') {
            var qp = period.split('-Q');
            return 'Q' + qp[1] + ' ' + qp[0];
        }
        var mp = period.split('-');
        return MONTHS[parseInt(mp[1], 10) - 1] + ' ' + mp[0];
    }

    /**
     * Pivot flat SQL rows [{Period, ModuleUID, TxCount, SubTotalAmt, TaxAmt, NetAmt}]
     * into an ordered map { period → { inv, ret } }.
     *
     * @param {Array} rows
     * @returns {{ order: string[], map: Object }}
     */
    function _pivot(rows) {
        var map   = {};
        var order = [];
        rows.forEach(function (r) {
            var p = r.Period;
            if (!map[p]) { map[p] = { period: p, inv: null, ret: null }; order.push(p); }
            var slot = {
                count : parseInt(r.TxCount,    10) || 0,
                sub   : parseFloat(r.SubTotalAmt)  || 0,
                tax   : parseFloat(r.TaxAmt)       || 0,
                net   : parseFloat(r.NetAmt)       || 0,
            };
            if (r.ModuleUID == 103) map[p].inv = slot;
            if (r.ModuleUID == 106) map[p].ret = slot;
        });
        return { order: order, map: map };
    }

    /**
     * @param {Object} pivoted  result of _pivot()
     */
    function _render(pivoted) {
        var tbody  = document.getElementById('ssTableBody');
        var tfoot  = document.getElementById('ssTableFoot');
        var footer = document.getElementById('ssTblFooter');

        if (!pivoted.order.length) {
            tbody.innerHTML = '<tr><td colspan="8">'
                + '<div class="rpt-empty"><i class="bx bx-bar-chart-alt-2"></i>'
                + '<div class="rpt-empty-title">No data for this period</div>'
                + '<div>Try adjusting the date range or group-by</div></div></td></tr>';
            tfoot.classList.add('d-none');
            footer.classList.add('d-none');
            _updateStats(0, 0, 0, 0, 0);
            return;
        }

        // Accumulate totals
        var totInvCount = 0, totSub = 0, totTax = 0, totGross = 0;
        var totRetCount = 0, totRet = 0;

        var html = '';
        pivoted.order.forEach(function (p) {
            var row    = pivoted.map[p];
            var inv    = row.inv || { count:0, sub:0, tax:0, net:0 };
            var ret    = row.ret || { count:0, sub:0, tax:0, net:0 };
            var netRev = inv.net - ret.net;

            totInvCount += inv.count;
            totSub      += inv.sub;
            totTax      += inv.tax;
            totGross    += inv.net;
            totRetCount += ret.count;
            totRet      += ret.net;

            var netCls  = netRev >= 0 ? 'rpt-num-green' : 'rpt-num-red';

            html += '<tr>'
                + '<td class="rpt-col-period">' + _formatPeriod(p, _groupBy) + '</td>'
                + '<td class="rpt-col-num">' + (inv.count ? '<span class="rpt-count-chip">' + inv.count + '</span>' : '—') + '</td>'
                + '<td class="rpt-col-num">' + (inv.sub  ? _fmt(inv.sub)  : '<span class="text-muted">—</span>') + '</td>'
                + '<td class="rpt-col-num">' + (inv.tax  ? _fmt(inv.tax)  : '<span class="text-muted">—</span>') + '</td>'
                + '<td class="rpt-col-num rpt-num-green">' + _fmt(inv.net) + '</td>'
                + '<td class="rpt-col-num">' + (ret.count ? '<span class="rpt-count-chip">' + ret.count + '</span>' : '—') + '</td>'
                + '<td class="rpt-col-num rpt-num-red">'   + (ret.net ? _fmt(ret.net) : '<span class="text-muted">—</span>') + '</td>'
                + '<td class="rpt-col-num ' + netCls + '">' + _fmt(netRev) + '</td>'
                + '</tr>';
        });

        tbody.innerHTML = html;

        // Tfoot
        var netTotal = totGross - totRet;
        document.getElementById('ssFtInvCount').textContent = totInvCount;
        document.getElementById('ssFtSubTotal').textContent = _fmt(totSub);
        document.getElementById('ssFtTax').textContent      = _fmt(totTax);
        document.getElementById('ssFtGross').textContent    = _fmt(totGross);
        document.getElementById('ssFtRetCount').textContent = totRetCount || '—';
        document.getElementById('ssFtReturns').textContent  = _fmt(totRet);
        document.getElementById('ssFtNet').textContent      = _fmt(netTotal);
        tfoot.classList.remove('d-none');

        // Bottom footer chips
        document.getElementById('ssPeriodCount').textContent   = pivoted.order.length + ' period(s)';
        document.getElementById('ssFooterGross').textContent   = _fmt(totGross);
        document.getElementById('ssFooterReturns').textContent = _fmt(totRet);
        document.getElementById('ssFooterNet').textContent     = _fmt(netTotal);
        footer.classList.remove('d-none');

        _updateStats(totInvCount, totGross, totRet, netTotal, pivoted.order.length);

        // Sync URL
        history.replaceState(null, '', '?from=' + _from + '&to=' + _to + '&groupby=' + _groupBy);
    }

    /**
     * @param {number} invCount
     * @param {number} gross
     * @param {number} ret
     * @param {number} net
     * @param {number} periods
     */
    function _updateStats(invCount, gross, ret, net, periods) {
        document.getElementById('ssStatInvCount').textContent = invCount || '0';
        document.getElementById('ssStatGross').textContent    = _fmt(gross);
        document.getElementById('ssStatReturns').textContent  = _fmt(ret);
        document.getElementById('ssStatNet').textContent      = _fmt(net);
    }

    /** Fetch data from server and render. */
    function _fetch() {
        var tbody = document.getElementById('ssTableBody');
        tbody.innerHTML = '<tr><td colspan="8" class="rpt-loading-cell">'
            + '<div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div></td></tr>';
        document.getElementById('ssTableFoot').classList.add('d-none');
        document.getElementById('ssTblFooter').classList.add('d-none');

        ajaxLoading(1);
        $.ajax({
            url      : '/reports/getSalesSummaryData',
            type     : 'GET',
            dataType : 'json',
            data     : { from: _from, to: _to, groupby: _groupBy },
            success  : function (res) {
                ajaxLoading(0);
                if (!res || res.Status !== 'Success') {
                    tbody.innerHTML = '<tr><td colspan="8" class="rpt-loading-cell text-danger">'
                        + (res && res.Message ? res.Message : 'Server error') + '</td></tr>';
                    return;
                }
                _rows = res.rows || [];
                _render(_pivot(_rows));
            },
            error: function () {
                ajaxLoading(0);
                tbody.innerHTML = '<tr><td colspan="8" class="rpt-loading-cell text-danger">Request failed. Please try again.</td></tr>';
            },
        });
    }

    /** Wire up date pickers with Flatpickr. */
    function _initDatePickers() {
        var fpCfgFrom = {
            dateFormat  : 'Y-m-d',
            altInput    : true,
            altFormat   : _ssListFmt,
            defaultDate : _ssInitFrom,
            onChange    : function (dates, str) { _from = str; },
        };
        var fpCfgTo = {
            dateFormat  : 'Y-m-d',
            altInput    : true,
            altFormat   : _ssListFmt,
            defaultDate : _ssInitTo,
            onChange    : function (dates, str) { _to = str; },
        };
        _fpFrom = flatpickr('#ssFromDisplay', fpCfgFrom);
        _fpTo   = flatpickr('#ssToDisplay',   fpCfgTo);
    }

    /** Wire up group-by buttons. */
    function _initGroupBy() {
        document.querySelectorAll('.rpt-gb-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.rpt-gb-btn').forEach(function (b) { b.classList.remove('active'); });
                btn.classList.add('active');
                _groupBy = btn.getAttribute('data-groupby');
            });
        });
    }

    /** Initialise the page. */
    function _init() {
        _initDatePickers();
        _initGroupBy();

        document.getElementById('ssApplyBtn').addEventListener('click', function () {
            _fetch();
        });

        _fetch();
    }

    $(document).ready(_init);

}());
