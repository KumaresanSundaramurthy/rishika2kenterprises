/**
 * Purchase Summary Report
 * Fetches Purchase (105) vs Purchase Return (108) data grouped by period.
 */

(function () {
    'use strict';

    var MONTHS = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

    var _from    = _purInitFrom;
    var _to      = _purInitTo;
    var _groupBy = _purGroupBy;
    var _rows    = [];

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
     * @param {string} period
     * @param {string} groupBy
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
     * Pivot flat rows into ordered map { period → { pur, ret } }.
     *
     * @param {Array} rows
     * @returns {{ order: string[], map: Object }}
     */
    function _pivot(rows) {
        var map   = {};
        var order = [];
        rows.forEach(function (r) {
            var p = r.Period;
            if (!map[p]) { map[p] = { period: p, pur: null, ret: null }; order.push(p); }
            var slot = {
                count : parseInt(r.TxCount,    10) || 0,
                sub   : parseFloat(r.SubTotalAmt)  || 0,
                tax   : parseFloat(r.TaxAmt)       || 0,
                net   : parseFloat(r.NetAmt)       || 0,
            };
            if (r.ModuleUID == 105) map[p].pur = slot;
            if (r.ModuleUID == 108) map[p].ret = slot;
        });
        return { order: order, map: map };
    }

    /**
     * @param {Object} pivoted
     */
    function _render(pivoted) {
        var tbody  = document.getElementById('purTableBody');
        var tfoot  = document.getElementById('purTableFoot');
        var footer = document.getElementById('purTblFooter');

        if (!pivoted.order.length) {
            tbody.innerHTML = '<tr><td colspan="8">'
                + '<div class="rpt-empty"><i class="bx bx-purchase-tag"></i>'
                + '<div class="rpt-empty-title">No data for this period</div>'
                + '<div>Try adjusting the date range or group-by</div></div></td></tr>';
            tfoot.classList.add('d-none');
            footer.classList.add('d-none');
            _updateStats(0, 0, 0, 0);
            return;
        }

        var totPurCount = 0, totSub = 0, totTax = 0, totGross = 0;
        var totRetCount = 0, totRet = 0;

        var html = '';
        pivoted.order.forEach(function (p) {
            var row    = pivoted.map[p];
            var pur    = row.pur || { count:0, sub:0, tax:0, net:0 };
            var ret    = row.ret || { count:0, sub:0, tax:0, net:0 };
            var netPur = pur.net - ret.net;

            totPurCount += pur.count;
            totSub      += pur.sub;
            totTax      += pur.tax;
            totGross    += pur.net;
            totRetCount += ret.count;
            totRet      += ret.net;

            html += '<tr>'
                + '<td class="rpt-col-period">' + _formatPeriod(p, _groupBy) + '</td>'
                + '<td class="rpt-col-num">' + (pur.count ? '<span class="rpt-count-chip">' + pur.count + '</span>' : '—') + '</td>'
                + '<td class="rpt-col-num">' + (pur.sub  ? _fmt(pur.sub)  : '<span class="text-muted">—</span>') + '</td>'
                + '<td class="rpt-col-num">' + (pur.tax  ? _fmt(pur.tax)  : '<span class="text-muted">—</span>') + '</td>'
                + '<td class="rpt-col-num rpt-num-orange">' + _fmt(pur.net) + '</td>'
                + '<td class="rpt-col-num">' + (ret.count ? '<span class="rpt-count-chip">' + ret.count + '</span>' : '—') + '</td>'
                + '<td class="rpt-col-num rpt-num-red">'    + (ret.net ? _fmt(ret.net) : '<span class="text-muted">—</span>') + '</td>'
                + '<td class="rpt-col-num rpt-num-blue">'   + _fmt(netPur) + '</td>'
                + '</tr>';
        });

        tbody.innerHTML = html;

        var netTotal = totGross - totRet;
        document.getElementById('purFtPurCount').textContent = totPurCount;
        document.getElementById('purFtSubTotal').textContent = _fmt(totSub);
        document.getElementById('purFtTax').textContent      = _fmt(totTax);
        document.getElementById('purFtGross').textContent    = _fmt(totGross);
        document.getElementById('purFtRetCount').textContent = totRetCount || '—';
        document.getElementById('purFtReturns').textContent  = _fmt(totRet);
        document.getElementById('purFtNet').textContent      = _fmt(netTotal);
        tfoot.classList.remove('d-none');

        document.getElementById('purPeriodCount').textContent   = pivoted.order.length + ' period(s)';
        document.getElementById('purFooterGross').textContent   = _fmt(totGross);
        document.getElementById('purFooterReturns').textContent = _fmt(totRet);
        document.getElementById('purFooterNet').textContent     = _fmt(netTotal);
        footer.classList.remove('d-none');

        _updateStats(totPurCount, totGross, totRet, netTotal);

        history.replaceState(null, '', '?from=' + _from + '&to=' + _to + '&groupby=' + _groupBy);
    }

    /**
     * @param {number} purCount
     * @param {number} gross
     * @param {number} ret
     * @param {number} net
     */
    function _updateStats(purCount, gross, ret, net) {
        document.getElementById('purStatPurCount').textContent = purCount || '0';
        document.getElementById('purStatGross').textContent    = _fmt(gross);
        document.getElementById('purStatReturns').textContent  = _fmt(ret);
        document.getElementById('purStatNet').textContent      = _fmt(net);
    }

    /** Fetch data from server and render. */
    function _fetch() {
        var tbody = document.getElementById('purTableBody');
        tbody.innerHTML = '<tr><td colspan="8" class="rpt-loading-cell">'
            + '<div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div></td></tr>';
        document.getElementById('purTableFoot').classList.add('d-none');
        document.getElementById('purTblFooter').classList.add('d-none');

        ajaxLoading(1);
        $.ajax({
            url      : '/reports/getPurchaseSummaryData',
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

    function _initDatePickers() {
        flatpickr('#purFromDisplay', {
            dateFormat  : 'Y-m-d',
            altInput    : true,
            altFormat   : _purListFmt,
            defaultDate : _purInitFrom,
            onChange    : function (dates, str) { _from = str; },
        });
        flatpickr('#purToDisplay', {
            dateFormat  : 'Y-m-d',
            altInput    : true,
            altFormat   : _purListFmt,
            defaultDate : _purInitTo,
            onChange    : function (dates, str) { _to = str; },
        });
    }

    function _initGroupBy() {
        document.querySelectorAll('.rpt-gb-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.rpt-gb-btn').forEach(function (b) { b.classList.remove('active'); });
                btn.classList.add('active');
                _groupBy = btn.getAttribute('data-groupby');
            });
        });
    }

    function _init() {
        _initDatePickers();
        _initGroupBy();

        document.getElementById('purApplyBtn').addEventListener('click', function () {
            _fetch();
        });

        _fetch();
    }

    $(document).ready(_init);

}());
