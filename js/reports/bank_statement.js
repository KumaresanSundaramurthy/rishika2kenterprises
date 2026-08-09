/**
 * Bank Statement Report
 * Fetches payments chronologically and computes a running balance.
 * PartyType='C' (Customer receipt) = Credit (money IN).
 * PartyType='S' (Supplier payment) = Debit  (money OUT).
 */

(function () {
    'use strict';

    var MONTHS = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

    var _from       = _bsInitFrom;
    var _to         = _bsInitTo;
    var _accountUID = _bsInitAccountUID;
    var _rows       = [];
    var _fpFrom     = null;
    var _fpTo       = null;

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
     * Format a MySQL date string using the org's list date format.
     *
     * @param {string} str  'YYYY-MM-DD'
     * @returns {string}
     */
    function _fmtDate(str) {
        if (!str) return '—';
        var d = str.split('-');
        if (d.length < 3) return str;
        return _bsListFmt
            .replace('d', d[2])
            .replace('M', MONTHS[parseInt(d[1], 10) - 1])
            .replace('m', d[1])
            .replace('Y', d[0]);
    }

    /**
     * Render the balance value with the appropriate CSS class.
     *
     * @param {number} balance
     * @returns {string}
     */
    function _balanceHtml(balance) {
        var cls = balance > 0 ? 'bs-balance-pos' : (balance < 0 ? 'bs-balance-neg' : 'bs-balance-zero');
        return '<span class="' + cls + '">' + _fmt(balance) + '</span>';
    }

    /**
     * Render the statement rows with running balance and update stats.
     *
     * @param {Array} rows  Ordered ASC by PaymentDate, PaymentUID
     */
    function _render(rows) {
        var tbody  = document.getElementById('bsTableBody');
        var tfoot  = document.getElementById('bsTableFoot');
        var footer = document.getElementById('bsTblFooter');

        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="9">'
                + '<div class="rpt-empty"><i class="bx bx-bank"></i>'
                + '<div class="rpt-empty-title">No transactions found</div>'
                + '<div>Try adjusting the date range or account filter</div></div></td></tr>';
            tfoot.classList.add('d-none');
            footer.classList.add('d-none');
            _updateStats(0, 0, 0, 0);
            return;
        }

        var totalCredit  = 0;
        var totalDebit   = 0;
        var runBalance   = 0;

        var html = '';
        rows.forEach(function (r) {
            var amt    = parseFloat(r.Amount) || 0;
            var isIn   = r.PartyType === 'C';   // Customer receipt = money IN
            var isCash = parseInt(r.IsCash, 10) === 1;

            if (isIn) {
                totalCredit += amt;
                runBalance  += amt;
            } else {
                totalDebit  += amt;
                runBalance  -= amt;
            }

            var dirHtml = isIn
                ? '<span class="bs-dir-in"><i class="bx bx-arrow-to-bottom"></i>In</span>'
                : '<span class="bs-dir-out"><i class="bx bx-arrow-to-top"></i>Out</span>';

            var modeHtml = isCash
                ? '<span class="pay-mode-chip pay-mode-cash"><i class="bx bx-wallet"></i>' + (r.PaymentMode || 'Cash') + '</span>'
                : '<span class="pay-mode-chip"><i class="bx bx-credit-card"></i>' + (r.PaymentMode || '—') + '</span>';

            var acctHtml = isCash ? '—' : (r.AccountName || '—');
            var refHtml  = r.ReferenceNo ? ('<span class="text-muted">' + r.ReferenceNo + '</span>') : '—';

            var creditHtml = isIn
                ? '<span class="bs-credit">' + _fmt(amt) + '</span>'
                : '<span class="text-muted">—</span>';
            var debitHtml  = !isIn
                ? '<span class="bs-debit">' + _fmt(amt) + '</span>'
                : '<span class="text-muted">—</span>';

            html += '<tr>'
                + '<td>' + _fmtDate(r.PaymentDate) + '</td>'
                + '<td><strong>' + (r.UniqueNumber || '—') + '</strong></td>'
                + '<td>' + (r.PartyName || '—') + '</td>'
                + '<td>' + modeHtml + '</td>'
                + '<td>' + acctHtml + '</td>'
                + '<td>' + dirHtml + '</td>'
                + '<td class="rpt-col-num">' + creditHtml + '</td>'
                + '<td class="rpt-col-num">' + debitHtml + '</td>'
                + '<td class="rpt-col-num">' + _balanceHtml(runBalance) + '</td>'
                + '</tr>';
        });

        tbody.innerHTML = html;

        // Tfoot
        var netBal = totalCredit - totalDebit;
        var balCls = netBal >= 0 ? 'bs-balance-pos' : 'bs-balance-neg';
        document.getElementById('bsFtCredit').textContent  = _fmt(totalCredit);
        document.getElementById('bsFtDebit').textContent   = _fmt(totalDebit);
        document.getElementById('bsFtBalance').innerHTML   = '<span class="' + balCls + '">' + _fmt(netBal) + '</span>';
        tfoot.classList.remove('d-none');

        // Bottom footer chips
        document.getElementById('bsRowCount').textContent     = rows.length + ' transaction(s)';
        document.getElementById('bsFooterIn').textContent     = _fmt(totalCredit);
        document.getElementById('bsFooterOut').textContent    = _fmt(totalDebit);
        document.getElementById('bsFooterBalance').textContent = _fmt(netBal);
        footer.classList.remove('d-none');

        _updateStats(totalCredit, totalDebit, netBal, rows.length);

        history.replaceState(null, '', '?from=' + _from + '&to=' + _to + '&account=' + _accountUID);
    }

    /**
     * @param {number} totalIn
     * @param {number} totalOut
     * @param {number} balance
     * @param {number} count
     */
    function _updateStats(totalIn, totalOut, balance, count) {
        document.getElementById('bsStatIn').textContent      = _fmt(totalIn);
        document.getElementById('bsStatOut').textContent     = _fmt(totalOut);
        document.getElementById('bsStatBalance').textContent = _fmt(balance);
        document.getElementById('bsStatCount').textContent   = count || '0';
    }

    /** Fetch data from server and render. */
    function _fetch() {
        var tbody = document.getElementById('bsTableBody');
        tbody.innerHTML = '<tr><td colspan="9" class="rpt-loading-cell">'
            + '<div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div></td></tr>';
        document.getElementById('bsTableFoot').classList.add('d-none');
        document.getElementById('bsTblFooter').classList.add('d-none');

        ajaxLoading(1);
        $.ajax({
            url      : '/reports/getBankStatementData',
            type     : 'GET',
            dataType : 'json',
            data     : { from: _from, to: _to, account: _accountUID },
            success  : function (res) {
                ajaxLoading(0);
                if (!res || res.Status !== 'Success') {
                    tbody.innerHTML = '<tr><td colspan="9" class="rpt-loading-cell text-danger">'
                        + (res && res.Message ? res.Message : 'Server error') + '</td></tr>';
                    return;
                }
                _rows = res.rows || [];
                _render(_rows);
            },
            error: function () {
                ajaxLoading(0);
                tbody.innerHTML = '<tr><td colspan="9" class="rpt-loading-cell text-danger">Request failed. Please try again.</td></tr>';
            },
        });
    }

    /** Wire up Flatpickr date pickers. */
    function _initDatePickers() {
        _fpFrom = flatpickr('#bsFromDisplay', {
            dateFormat  : 'Y-m-d',
            altInput    : true,
            altFormat   : _bsListFmt,
            defaultDate : _bsInitFrom,
            onChange    : function (dates, str) { _from = str; },
        });
        _fpTo = flatpickr('#bsToDisplay', {
            dateFormat  : 'Y-m-d',
            altInput    : true,
            altFormat   : _bsListFmt,
            defaultDate : _bsInitTo,
            onChange    : function (dates, str) { _to = str; },
        });
    }

    /** Initialise the page. */
    function _init() {
        _initDatePickers();

        document.getElementById('bsAccount').addEventListener('change', function () {
            _accountUID = parseInt(this.value, 10) || 0;
            _fetch();
        });

        document.getElementById('bsApplyBtn').addEventListener('click', _fetch);

        _fetch();
    }

    $(document).ready(_init);

}());
