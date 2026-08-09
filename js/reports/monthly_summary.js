/**
 * Monthly Summary Report
 * Shows 12 months for the selected year: Sales, Sales Returns, Net Sales,
 * Purchases, Purchase Returns, Net Purchase, Balance.
 */

(function () {
    'use strict';

    var MONTHS = ['January','February','March','April','May','June',
                  'July','August','September','October','November','December'];
    var MONTHS_SHORT = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

    var _year  = _msInitYear;
    var _rows  = [];

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
     * Build a 12-entry ledger (one per month) from flat SQL rows.
     * Each entry has: sales, salesRet, netSales, pur, purRet, netPur, balance.
     *
     * @param {Array}  rows   raw server rows
     * @param {number} year
     * @returns {Array}  length 12
     */
    function _buildLedger(rows, year) {
        var ledger = [];
        var curMonth = new Date().getMonth() + 1;
        var curYear  = new Date().getFullYear();

        for (var m = 1; m <= 12; m++) {
            ledger.push({ mo: m, sales: 0, salesRet: 0, pur: 0, purRet: 0 });
        }

        rows.forEach(function (r) {
            var mo  = parseInt(r.Mo, 10);
            var mid = parseInt(r.ModuleUID, 10);
            var amt = parseFloat(r.NetAmt) || 0;
            var idx = mo - 1;
            if (idx < 0 || idx > 11) return;
            if (mid === 103) ledger[idx].sales    += amt;
            if (mid === 106) ledger[idx].salesRet += amt;
            if (mid === 105) ledger[idx].pur      += amt;
            if (mid === 108) ledger[idx].purRet   += amt;
        });

        return ledger.map(function (e) {
            e.netSales = e.sales - e.salesRet;
            e.netPur   = e.pur   - e.purRet;
            e.balance  = e.netSales - e.netPur;
            e.isFuture = (year > curYear) || (year === curYear && e.mo > curMonth);
            e.isCur    = (year === curYear && e.mo === curMonth);
            return e;
        });
    }

    /**
     * Render the 12-row table and stat cards.
     *
     * @param {Array} ledger  from _buildLedger()
     */
    function _render(ledger) {
        var tbody  = document.getElementById('msTableBody');
        var tfoot  = document.getElementById('msTableFoot');
        var footer = document.getElementById('msTblFooter');

        var totSales = 0, totSalesRet = 0, totNetSales = 0;
        var totPur   = 0, totPurRet   = 0, totNetPur   = 0;
        var totBal   = 0;
        var bestMo   = null, bestBal = null;

        var html = '';
        ledger.forEach(function (e) {
            var rowCls = e.isFuture ? 'ms-row-future' : (e.isCur ? 'ms-row-cur' : '');
            var balCls = e.balance > 0 ? 'ms-num-pos' : (e.balance < 0 ? 'ms-num-neg' : 'ms-num-zero');

            // Bar width proportional — we set it after full loop
            var curBadge = e.isCur ? '<span class="ms-badge-cur">Now</span>' : '';

            html += '<tr class="' + rowCls + '" data-mo="' + e.mo + '">'
                + '<td class="ms-col-month">' + MONTHS[e.mo - 1] + curBadge + '</td>'
                + '<td class="ms-col-num ms-num-green">'  + (e.sales    ? _fmt(e.sales)    : '<span class="ms-num-zero">—</span>') + '</td>'
                + '<td class="ms-col-num ms-num-orange">' + (e.salesRet ? _fmt(e.salesRet) : '<span class="ms-num-zero">—</span>') + '</td>'
                + '<td class="ms-col-num ms-num-green">'  + _fmt(e.netSales) + '</td>'
                + '<td class="ms-col-num ms-num-orange">' + (e.pur    ? _fmt(e.pur)    : '<span class="ms-num-zero">—</span>') + '</td>'
                + '<td class="ms-col-num ms-num-orange">' + (e.purRet ? _fmt(e.purRet) : '<span class="ms-num-zero">—</span>') + '</td>'
                + '<td class="ms-col-num ms-num-orange">' + _fmt(e.netPur) + '</td>'
                + '<td class="ms-col-num ' + balCls + '">' + _fmt(e.balance) + '</td>'
                + '</tr>';

            if (!e.isFuture) {
                totSales    += e.sales;
                totSalesRet += e.salesRet;
                totNetSales += e.netSales;
                totPur      += e.pur;
                totPurRet   += e.purRet;
                totNetPur   += e.netPur;
                totBal      += e.balance;
                if (bestBal === null || e.balance > bestBal) {
                    bestBal = e.balance;
                    bestMo  = e.mo;
                }
            }
        });

        tbody.innerHTML = html;

        // Tfoot
        var totBalCls = totBal >= 0 ? 'ms-num-pos' : 'ms-num-neg';
        document.getElementById('msFtSales').textContent    = _fmt(totSales);
        document.getElementById('msFtSalesRet').textContent = _fmt(totSalesRet);
        document.getElementById('msFtNetSales').textContent = _fmt(totNetSales);
        document.getElementById('msFtPur').textContent      = _fmt(totPur);
        document.getElementById('msFtPurRet').textContent   = _fmt(totPurRet);
        document.getElementById('msFtNetPur').textContent   = _fmt(totNetPur);
        var balCell = document.getElementById('msFtBalance');
        balCell.textContent = _fmt(totBal);
        balCell.className   = 'ms-col-num ' + totBalCls;
        tfoot.classList.remove('d-none');

        // Bottom note
        document.getElementById('msFooterNote').textContent = _year + ' — 12 months';
        footer.classList.remove('d-none');

        // Stat cards
        var balCardVal = document.getElementById('msStatBalance');
        balCardVal.textContent = _fmt(totBal);
        balCardVal.className   = 'rpt-stat-val ' + totBalCls;
        document.getElementById('msStatSales').textContent = _fmt(totNetSales);
        document.getElementById('msStatPur').textContent   = _fmt(totNetPur);
        document.getElementById('msStatBest').textContent  = bestMo !== null
            ? MONTHS_SHORT[bestMo - 1] + ' (' + _fmt(bestBal) + ')'
            : '—';
    }

    /** Fetch data from server and render. */
    function _fetch() {
        var tbody = document.getElementById('msTableBody');
        tbody.innerHTML = '<tr><td colspan="8" class="rpt-loading-cell">'
            + '<div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div></td></tr>';
        document.getElementById('msTableFoot').classList.add('d-none');
        document.getElementById('msTblFooter').classList.add('d-none');

        ajaxLoading(1);
        $.ajax({
            url      : '/reports/getMonthlySummaryData',
            type     : 'GET',
            dataType : 'json',
            data     : { year: _year },
            success  : function (res) {
                ajaxLoading(0);
                if (!res || res.Status !== 'Success') {
                    tbody.innerHTML = '<tr><td colspan="8" class="rpt-loading-cell text-danger">'
                        + (res && res.Message ? res.Message : 'Server error') + '</td></tr>';
                    return;
                }
                _rows = res.rows || [];
                _render(_buildLedger(_rows, _year));
            },
            error: function () {
                ajaxLoading(0);
                tbody.innerHTML = '<tr><td colspan="8" class="rpt-loading-cell text-danger">Request failed. Please try again.</td></tr>';
            },
        });
    }

    function _setYear(y) {
        _year = y;
        document.getElementById('msYear').value       = y;
        document.getElementById('msYearLabel').textContent = y;
        history.replaceState(null, '', '?year=' + y);
        _fetch();
    }

    function _init() {
        document.getElementById('msYearPrev').addEventListener('click', function () {
            _setYear(_year - 1);
        });
        document.getElementById('msYearNext').addEventListener('click', function () {
            _setYear(_year + 1);
        });

        _fetch();
    }

    $(document).ready(_init);

}());
