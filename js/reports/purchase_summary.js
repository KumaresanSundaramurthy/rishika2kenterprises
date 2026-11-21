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
        var $tbody  = $('#purTableBody');
        var $tfoot  = $('#purTableFoot');
        var $footer = $('#purTblFooter');

        if (!pivoted.order.length) {
            $tbody.html('<tr><td colspan="8">'
                + '<div class="rpt-empty"><i class="bx bx-purchase-tag"></i>'
                + '<div class="rpt-empty-title">No data for this period</div>'
                + '<div>Try adjusting the date range or group-by</div></div></td></tr>');
            $tfoot.addClass('d-none');
            $footer.addClass('d-none');
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
                + '<td class="rpt-col-num">' + (pur.count ? '<span class="rpt-count-chip rpt-count-chip-orange">' + pur.count + '</span>' : '—') + '</td>'
                + '<td class="rpt-col-num">' + (pur.sub  ? _fmt(pur.sub)  : '<span class="text-muted">—</span>') + '</td>'
                + '<td class="rpt-col-num">' + (pur.tax  ? _fmt(pur.tax)  : '<span class="text-muted">—</span>') + '</td>'
                + '<td class="rpt-col-num rpt-num-orange">' + _fmt(pur.net) + '</td>'
                + '<td class="rpt-col-num">' + (ret.count ? '<span class="rpt-count-chip rpt-count-chip-red">' + ret.count + '</span>' : '—') + '</td>'
                + '<td class="rpt-col-num rpt-num-red">'    + (ret.net ? _fmt(ret.net) : '<span class="text-muted">—</span>') + '</td>'
                + '<td class="rpt-col-num rpt-num-blue">'   + _fmt(netPur) + '</td>'
                + '</tr>';
        });

        $tbody.html(html);

        var netTotal = totGross - totRet;
        $('#purFtPurCount').text(totPurCount);
        $('#purFtSubTotal').text(_fmt(totSub));
        $('#purFtTax').text(_fmt(totTax));
        $('#purFtGross').text(_fmt(totGross));
        $('#purFtRetCount').text(totRetCount || '—');
        $('#purFtReturns').text(_fmt(totRet));
        $('#purFtNet').text(_fmt(netTotal));
        $tfoot.removeClass('d-none');

        $('#purPeriodCount').text(pivoted.order.length + ' period(s)');
        $('#purFooterGross').text(_fmt(totGross));
        $('#purFooterReturns').text(_fmt(totRet));
        $('#purFooterNet').text(_fmt(netTotal));
        $footer.removeClass('d-none');

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
        $('#purStatPurCount').text(purCount || '0');
        $('#purStatGross').text(_fmt(gross));
        $('#purStatReturns').text(_fmt(ret));
        $('#purStatNet').text(_fmt(net));
    }

    /**
     * @param {string} format
     * @returns {void}
     */
    function _handleExport(format) {
        var type    = format === 'pdf' ? 'Pdf' : (format === 'print' ? 'Print' : (format === 'excel' ? 'Excel' : 'CSV'));
        var payload = { orgId: parseInt(JwtData.Org.OrgUID, 10), from: _from, to: _to, groupby: _groupBy, Type: type };

        if (type === 'Print') {
            $.ajax({
                url         : global_services_url + 'api/reports/exportPurchaseSummary',
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
                url         : global_services_url + 'api/reports/exportPurchaseSummary',
                method      : 'POST',
                contentType : 'application/json',
                headers     : { Authorization: 'Bearer ' + JwtToken },
                data        : JSON.stringify(payload),
                xhrFields   : { responseType: 'blob' },
                success : function (blob) {
                    var url = URL.createObjectURL(blob);
                    var $a  = $('<a>').attr({ href: url, download: 'purchase_summary.' + ext }).appendTo('body');
                    $a[0].click();
                    setTimeout(function () { URL.revokeObjectURL(url); $a.remove(); }, 3000);
                },
                error : function () { showToastNotification('Export failed. Please try again.', 'error'); }
            });
        }
    }

    /** Fetch data from server and render. */
    function _fetch() {
        var $tbody = $('#purTableBody');
        $tbody.html('<tr><td colspan="8" class="rpt-loading-cell">'
            + '<div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div></td></tr>');
        $('#purTableFoot').addClass('d-none');
        $('#purTblFooter').addClass('d-none');

        ajaxLoading(1);
        $.ajax({
            url      : global_services_url + 'api/reports/getPurchaseSummaryData',
            type     : 'GET',
            dataType : 'json',
            headers  : { Authorization: 'Bearer ' + JwtToken },
            data     : { orgId: parseInt(JwtData.Org.OrgUID, 10), from: _from, to: _to, groupby: _groupBy },
            success  : function (res) {
                ajaxLoading(0);
                if (!res || res.Error) {
                    $tbody.html('<tr><td colspan="8" class="rpt-loading-cell text-danger">'
                        + (res && res.Message ? res.Message : 'Server error') + '</td></tr>');
                    return;
                }
                _rows = res.rows || [];
                _render(_pivot(_rows));
            },
            error: function () {
                ajaxLoading(0);
                $tbody.html('<tr><td colspan="8" class="rpt-loading-cell text-danger">Request failed. Please try again.</td></tr>');
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
        $(document).on('click', '.rpt-gb-btn', function () {
            $('.rpt-gb-btn').removeClass('active');
            $(this).addClass('active');
            _groupBy = $(this).data('groupby');
        });
    }

    function _init() {
        _initDatePickers();
        _initGroupBy();

        $('#purApplyBtn').on('click', function () { _fetch(); });

        $(document).on('click', '.r2k-export-btn[data-format]', function () {
            _handleExport($(this).data('format'));
        });

        _fetch();
    }

    $(document).ready(_init);

}());
