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
    var _dec = 2;

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
        var $tbody  = $('#msTableBody');
        var $tfoot  = $('#msTableFoot');
        var $footer = $('#msTblFooter');

        var totSales = 0, totSalesRet = 0, totNetSales = 0;
        var totPur   = 0, totPurRet   = 0, totNetPur   = 0;
        var totBal   = 0;
        var bestMo   = null, bestBal = null;

        /* Pre-compute max absolute balance for proportional bar scaling */
        var maxAbsBal = 0;
        ledger.forEach(function (e) {
            if (!e.isFuture && Math.abs(e.balance) > maxAbsBal) { maxAbsBal = Math.abs(e.balance); }
        });

        var html = '';
        ledger.forEach(function (e) {
            var rowCls = e.isFuture ? 'ms-row-future' : (e.isCur ? 'ms-row-cur' : '');
            var balCls = e.balance > 0 ? 'ms-num-pos' : (e.balance < 0 ? 'ms-num-neg' : 'ms-num-zero');

            var barW   = (maxAbsBal > 0 && !e.isFuture) ? Math.max(2, Math.round(Math.abs(e.balance) / maxAbsBal * 48)) : 0;
            var barCls = e.balance >= 0 ? 'ms-bal-bar-pos' : 'ms-bal-bar-neg';
            var balBar = barW > 0 ? '<span class="ms-bal-bar ' + barCls + '" style="--ms-bar-w:' + barW + 'px"></span>' : '';

            var curBadge = e.isCur ? '<span class="ms-badge-cur">Now</span>' : '';

            html += '<tr class="' + rowCls + '" data-mo="' + e.mo + '">'
                + '<td class="ms-col-month">' + MONTHS[e.mo - 1] + curBadge + '</td>'
                + '<td class="ms-col-num ms-num-green">'  + (e.sales    ? _fmt(e.sales)    : '<span class="ms-num-zero">—</span>') + '</td>'
                + '<td class="ms-col-num ms-num-orange">' + (e.salesRet ? _fmt(e.salesRet) : '<span class="ms-num-zero">—</span>') + '</td>'
                + '<td class="ms-col-num ms-num-green">'  + _fmt(e.netSales) + '</td>'
                + '<td class="ms-col-num ms-num-orange">' + (e.pur    ? _fmt(e.pur)    : '<span class="ms-num-zero">—</span>') + '</td>'
                + '<td class="ms-col-num ms-num-orange">' + (e.purRet ? _fmt(e.purRet) : '<span class="ms-num-zero">—</span>') + '</td>'
                + '<td class="ms-col-num ms-num-orange">' + _fmt(e.netPur) + '</td>'
                + '<td class="ms-col-num ' + balCls + '">' + _fmt(e.balance) + balBar + '</td>'
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

        $tbody.html(html);

        // Tfoot
        var totBalCls = totBal >= 0 ? 'ms-num-pos' : 'ms-num-neg';
        $('#msFtSales').text(_fmt(totSales));
        $('#msFtSalesRet').text(_fmt(totSalesRet));
        $('#msFtNetSales').text(_fmt(totNetSales));
        $('#msFtPur').text(_fmt(totPur));
        $('#msFtPurRet').text(_fmt(totPurRet));
        $('#msFtNetPur').text(_fmt(totNetPur));
        $('#msFtBalance').text(_fmt(totBal)).attr('class', 'ms-col-num ' + totBalCls);
        $tfoot.removeClass('d-none');

        // Bottom note
        $('#msFooterNote').text(_year + ' — 12 months');
        $footer.removeClass('d-none');

        // Stat cards
        $('#msStatBalance').text(_fmt(totBal)).attr('class', 'rpt-stat-val ' + totBalCls);
        $('#msStatSales').text(_fmt(totNetSales));
        $('#msStatPur').text(_fmt(totNetPur));
        $('#msStatBest').text(bestMo !== null ? MONTHS_SHORT[bestMo - 1] + ' (' + _fmt(bestBal) + ')' : '—');
    }

    /** Fetch data from server and render. */
    function _fetch() {
        var $tbody = $('#msTableBody');
        $tbody.html('<tr><td colspan="8" class="rpt-loading-cell">'
            + '<div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div></td></tr>');
        $('#msTableFoot').addClass('d-none');
        $('#msTblFooter').addClass('d-none');

        ajaxLoading(1);
        $.ajax({
            url      : global_services_url + 'api/reports/getMonthlySummaryData',
            type     : 'GET',
            dataType : 'json',
            headers  : { Authorization: 'Bearer ' + JwtToken },
            data     : { orgId: parseInt(JwtData.Org.OrgUID, 10), year: _year },
            success  : function (res) {
                ajaxLoading(0);
                if (!res || res.Error) {
                    $tbody.html('<tr><td colspan="8" class="rpt-loading-cell text-danger">'
                        + (res && res.Message ? res.Message : 'Server error') + '</td></tr>');
                    return;
                }
                _rows = res.rows || [];
                _render(_buildLedger(_rows, _year));
            },
            error: function () {
                ajaxLoading(0);
                $tbody.html('<tr><td colspan="8" class="rpt-loading-cell text-danger">Request failed. Please try again.</td></tr>');
            },
        });
    }

    /**
     * @param {number} y
     * @returns {void}
     */
    function _setYear(y) {
        _year = y;
        $('#msYear').val(y);
        $('#msYearLabel').text(y);
        history.replaceState(null, '', '?year=' + y);
        _fetch();
    }

    /**
     * @param {string} format
     * @returns {void}
     */
    function _handleExport(format) {
        var type    = format === 'pdf' ? 'Pdf' : (format === 'print' ? 'Print' : (format === 'excel' ? 'Excel' : 'CSV'));
        var payload = { orgId: parseInt(JwtData.Org.OrgUID, 10), year: _year, Type: type };

        if (type === 'Print') {
            $.ajax({
                url         : global_services_url + 'api/reports/exportMonthlySummary',
                method      : 'POST',
                contentType : 'application/json',
                headers     : { Authorization: 'Bearer ' + JwtToken },
                data        : JSON.stringify(payload),
                dataType    : 'json',
                success : function (resp) {
                    if (!resp || resp.Error || !resp.HtmlData) { showToastNotification('Export failed. Please try again.', 'error'); return; }
                    var win = window.open('', '_blank');
                    win.document.open();
                    win.document.write(resp.HtmlData);
                    win.document.close();
                },
                error : function () { showToastNotification('Export failed. Please try again.', 'error'); }
            });
        } else {
            var ext = type === 'Excel' ? 'xlsx' : (type === 'Pdf' ? 'pdf' : 'csv');
            $.ajax({
                url         : global_services_url + 'api/reports/exportMonthlySummary',
                method      : 'POST',
                contentType : 'application/json',
                headers     : { Authorization: 'Bearer ' + JwtToken },
                data        : JSON.stringify(payload),
                xhrFields   : { responseType: 'blob' },
                success : function (blob) {
                    var url = URL.createObjectURL(blob);
                    var $a  = $('<a>').attr({ href: url, download: 'monthly_summary.' + ext }).appendTo('body');
                    $a[0].click();
                    setTimeout(function () { URL.revokeObjectURL(url); $a.remove(); }, 3000);
                },
                error : function () { showToastNotification('Export failed. Please try again.', 'error'); }
            });
        }
    }

    function _init() {
        $('#msYearPrev').on('click', function () { _setYear(_year - 1); });
        $('#msYearNext').on('click', function () { _setYear(_year + 1); });
        $(document).on('click', '.r2k-export-btn[data-format]', function () {
            _handleExport($(this).data('format'));
        });
        _fetch();
    }

    $(document).ready(_init);

}());
