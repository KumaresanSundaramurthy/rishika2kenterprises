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
        var content = document.getElementById('plContent');

        if (!rows.length) {
            content.innerHTML = '<div class="fin-empty"><i class="bx bx-bar-chart-alt-2"></i>'
                + '<div class="fin-empty-title">No ledger accounts found</div>'
                + '<div>Set up Income and Expense accounts in Chart of Accounts first</div></div>';
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

        content.innerHTML = html;

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
        document.getElementById('plStatRevenue').textContent  = _fmt(revenue);
        document.getElementById('plStatExpenses').textContent = _fmt(expenses);
        document.getElementById('plStatNet').textContent      = _fmt(net);
        document.getElementById('plStatMargin').textContent   = margin.toFixed(1) + '%';
    }

    /** Fetch data from server and render. */
    function _fetch() {
        var content = document.getElementById('plContent');
        content.innerHTML = '<div class="fin-loading"><span class="spinner-border spinner-border-sm"></span>Loading…</div>';

        ajaxLoading(1);
        $.ajax({
            url      : '/reports/getPLStatementData',
            type     : 'GET',
            dataType : 'json',
            data     : { from: _from, to: _to },
            success  : function (res) {
                ajaxLoading(0);
                if (!res || res.Status !== 'Success') {
                    content.innerHTML = '<div class="fin-loading" style="color:#dc2626;">'
                        + (res && res.Message ? res.Message : 'Server error') + '</div>';
                    return;
                }
                _render(res.rows || []);
            },
            error: function () {
                ajaxLoading(0);
                content.innerHTML = '<div class="fin-loading" style="color:#dc2626;">Request failed. Please try again.</div>';
            },
        });
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
        document.getElementById('plApplyBtn').addEventListener('click', _fetch);
        _fetch();
    }

    $(document).ready(_init);

}());
