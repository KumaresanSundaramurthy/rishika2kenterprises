/**
 * P&L Statement Report
 * Income (LedgerType='Income'): Net = PeriodCredit − PeriodDebit
 * Expense (LedgerType='Expense'): Net = PeriodDebit − PeriodCredit
 */

(function () {
    'use strict';

    var _from   = _plInitFrom;
    var _to     = _plInitTo;
    var _fpFrom = null;
    var _fpTo   = null;

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
     * Build one financial-statement section (Revenue or Expenses).
     *
     * @param {string} title
     * @param {Array}  rows
     * @param {string} valClass  CSS class on the value span
     * @param {number} total
     * @param {string} totalLabel
     * @returns {string}
     */
    function _buildSection(title, rows, valClass, total, totalLabel) {
        var html = '<div class="fin-section">'
            + '<div class="fin-section-title">' + title + '</div>';

        if (rows.length === 0) {
            html += '<div class="fin-row"><span class="fin-row-label" style="color:#94a3b8;">No accounts</span>'
                + '<span class="fin-row-val fin-zero">—</span></div>';
        } else {
            rows.forEach(function (r) {
                var net = parseFloat(r._net) || 0;
                var valHtml = net === 0
                    ? '<span class="fin-zero">—</span>'
                    : '<span class="' + valClass + '">' + _fmt(net) + '</span>';
                html += '<div class="fin-row">'
                    + '<span class="fin-row-label">' + (r.LedgerName || '—') + '</span>'
                    + '<span class="fin-row-val">' + valHtml + '</span>'
                    + '</div>';
            });
        }

        html += '<div class="fin-total-row">'
            + '<span class="fin-total-label">' + totalLabel + '</span>'
            + '<span class="fin-total-val ' + valClass + '">' + _fmt(total) + '</span>'
            + '</div></div>';
        return html;
    }

    /**
     * @param {Array} rows
     */
    function _render(rows) {
        var $content = $('#plContent');

        if (!rows.length) {
            $content.html('<div class="fin-empty"><i class="bx bx-bar-chart-alt-2"></i>'
                + '<div class="fin-empty-title">No ledger accounts found</div>'
                + '<div>Set up Income and Expense accounts in Chart of Accounts first</div></div>');
            _updateStats(0, 0, 0);
            return;
        }

        var income  = [];
        var expense = [];

        rows.forEach(function (r) {
            var dr = parseFloat(r.PeriodDebit)  || 0;
            var cr = parseFloat(r.PeriodCredit) || 0;
            if (r.LedgerType === 'Income') {
                r._net = cr - dr;   // Income: Credit side increases
                income.push(r);
            } else {
                r._net = dr - cr;   // Expense: Debit side increases
                expense.push(r);
            }
        });

        var totalRevenue = income.reduce(function (s, r)  { return s + r._net; }, 0);
        var totalExpense = expense.reduce(function (s, r) { return s + r._net; }, 0);
        var netProfit    = totalRevenue - totalExpense;
        var isProfit     = netProfit >= 0;

        var html = '<div class="fin-wrap">'
            + '<div class="fin-cols">'
            + _buildSection('Revenue', income, 'rpt-num-green', totalRevenue, 'Total Revenue')
            + _buildSection('Expenses', expense, 'rpt-num-red', totalExpense, 'Total Expenses')
            + '</div>'
            + '<div class="fin-net-row ' + (isProfit ? 'fin-net-profit' : 'fin-net-loss') + '">'
            + '<span class="fin-net-label"><i class="bx ' + (isProfit ? 'bx-trending-up' : 'bx-trending-down') + ' me-1"></i>'
            + (isProfit ? 'Net Profit' : 'Net Loss') + '</span>'
            + '<span class="fin-net-val">' + _fmt(Math.abs(netProfit)) + '</span>'
            + '</div></div>';

        $content.html(html);

        _updateStats(totalRevenue, totalExpense, netProfit);
        history.replaceState(null, '', '?from=' + _from + '&to=' + _to);
    }

    /**
     * @param {number} revenue
     * @param {number} expenses
     * @param {number} net
     */
    function _updateStats(revenue, expenses, net) {
        var margin = revenue > 0 ? ((net / revenue) * 100) : 0;
        $('#plStatRevenue').text(_fmt(revenue));
        $('#plStatExpenses').text(_fmt(expenses));
        $('#plStatNet').text(_fmt(net));
        $('#plStatMargin').text(margin.toFixed(1) + '%');
    }

    /** Fetch data from server and render. */
    function _fetch() {
        var $content = $('#plContent');
        $content.html('<div class="fin-loading"><span class="spinner-border spinner-border-sm"></span>Loading…</div>');

        ajaxLoading(1);
        $.ajax({
            url      : global_services_url + 'api/reports/getPLStatementData',
            type     : 'GET',
            dataType : 'json',
            headers  : { Authorization: 'Bearer ' + JwtToken },
            data     : { orgId: parseInt(JwtData.Org.OrgUID, 10), from: _from, to: _to },
            success  : function (res) {
                ajaxLoading(0);
                if (!res || res.Error) {
                    $content.html('<div class="fin-loading" style="color:#dc2626;">'
                        + (res && res.Message ? res.Message : 'Server error') + '</div>');
                    return;
                }
                _render(res.rows || []);
            },
            error: function () {
                ajaxLoading(0);
                $content.html('<div class="fin-loading" style="color:#dc2626;">Request failed. Please try again.</div>');
            },
        });
    }

    /**
     * @param {string} format
     * @returns {void}
     */
    function _handleExport(format) {
        var type    = format === 'pdf' ? 'Pdf' : (format === 'print' ? 'Print' : (format === 'excel' ? 'Excel' : 'CSV'));
        var payload = { orgId: parseInt(JwtData.Org.OrgUID, 10), from: _from, to: _to, Type: type };

        if (type === 'Print') {
            $.ajax({
                url         : global_services_url + 'api/reports/exportPLStatement',
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
                url         : global_services_url + 'api/reports/exportPLStatement',
                method      : 'POST',
                contentType : 'application/json',
                headers     : { Authorization: 'Bearer ' + JwtToken },
                data        : JSON.stringify(payload),
                xhrFields   : { responseType: 'blob' },
                success : function (blob) {
                    var url = URL.createObjectURL(blob);
                    var $a  = $('<a>').attr({ href: url, download: 'pl_statement.' + ext }).appendTo('body');
                    $a[0].click();
                    setTimeout(function () { URL.revokeObjectURL(url); $a.remove(); }, 3000);
                },
                error : function () { showToastNotification('Export failed. Please try again.', 'error'); }
            });
        }
    }

    /** Wire up Flatpickr date pickers. */
    function _initDatePickers() {
        _fpFrom = flatpickr('#plFromDisplay', {
            dateFormat  : 'Y-m-d',
            altInput    : true,
            altFormat   : _plListFmt,
            defaultDate : _plInitFrom,
            onChange    : function (dates, str) { _from = str; },
        });
        _fpTo = flatpickr('#plToDisplay', {
            dateFormat  : 'Y-m-d',
            altInput    : true,
            altFormat   : _plListFmt,
            defaultDate : _plInitTo,
            onChange    : function (dates, str) { _to = str; },
        });
    }

    /** Initialise the page. */
    function _init() {
        _initDatePickers();
        $('#plApplyBtn').on('click', _fetch);
        $(document).on('click', '.r2k-export-btn[data-format]', function () {
            _handleExport($(this).data('format'));
        });
        _fetch();
    }

    $(document).ready(_init);

}());
