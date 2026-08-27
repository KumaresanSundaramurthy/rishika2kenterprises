/**
 * Payment Made Report
 * Lists all payments made to suppliers for the selected date range.
 */

(function () {
    'use strict';

    var MONTHS = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

    var _from = _pmInitFrom;
    var _to   = _pmInitTo;
    var _rows = [];
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
     * Format a MySQL date string using the org's list date format.
     *
     * @param {string} str  'YYYY-MM-DD'
     * @returns {string}
     */
    function _fmtDate(str) {
        if (!str) return '—';
        var d = str.split('-');
        if (d.length < 3) return str;
        return _pmListFmt
            .replace('d', d[2])
            .replace('M', MONTHS[parseInt(d[1], 10) - 1])
            .replace('m', d[1])
            .replace('Y', d[0]);
    }

    /**
     * @param {Array} rows
     */
    function _render(rows) {
        var tbody  = document.getElementById('pmTableBody');
        var tfoot  = document.getElementById('pmTableFoot');
        var footer = document.getElementById('pmTblFooter');

        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="7">'
                + '<div class="rpt-empty"><i class="bx bx-up-arrow-circle"></i>'
                + '<div class="rpt-empty-title">No payments found</div>'
                + '<div>Try adjusting the date range</div></div></td></tr>';
            tfoot.classList.add('d-none');
            footer.classList.add('d-none');
            _updateStats(0, 0, 0, 0);
            return;
        }

        var totalAmt = 0;
        var cashAmt  = 0;
        var bankAmt  = 0;

        var html = '';
        rows.forEach(function (r) {
            var amt    = parseFloat(r.Amount) || 0;
            var isCash = parseInt(r.IsCash, 10) === 1;
            totalAmt += amt;
            if (isCash) { cashAmt += amt; } else { bankAmt += amt; }

            var modeHtml = isCash
                ? '<span class="pay-mode-chip pay-mode-cash"><i class="bx bx-wallet"></i>' + r.PaymentMode + '</span>'
                : '<span class="pay-mode-chip"><i class="bx bx-credit-card"></i>' + r.PaymentMode + '</span>';

            var onAccTag = parseInt(r.IsOnAccount, 10) === 1
                ? '<span class="pay-onaccount">On Acct</span>' : '';

            var refHtml = r.ReferenceNo ? ('<span class="text-muted">' + r.ReferenceNo + '</span>') : '—';

            html += '<tr>'
                + '<td>' + _fmtDate(r.PaymentDate) + '</td>'
                + '<td><strong>' + (r.UniqueNumber || '—') + '</strong>' + onAccTag + '</td>'
                + '<td>' + (r.PartyName || '—') + '</td>'
                + '<td>' + modeHtml + '</td>'
                + '<td>' + (isCash ? '—' : (r.AccountName || '—')) + '</td>'
                + '<td>' + refHtml + '</td>'
                + '<td class="rpt-col-num rpt-num-orange">' + _fmt(amt) + '</td>'
                + '</tr>';
        });

        tbody.innerHTML = html;

        // Tfoot
        document.getElementById('pmFtTotal').textContent = _fmt(totalAmt);
        tfoot.classList.remove('d-none');

        // Bottom footer
        document.getElementById('pmRowCount').textContent    = rows.length + ' payment(s)';
        document.getElementById('pmFooterCash').textContent  = _fmt(cashAmt);
        document.getElementById('pmFooterBank').textContent  = _fmt(bankAmt);
        document.getElementById('pmFooterTotal').textContent = _fmt(totalAmt);
        footer.classList.remove('d-none');

        _updateStats(rows.length, totalAmt, cashAmt, bankAmt);

        history.replaceState(null, '', '?from=' + _from + '&to=' + _to);
    }

    /**
     * @param {number} count
     * @param {number} total
     * @param {number} cash
     * @param {number} bank
     */
    function _updateStats(count, total, cash, bank) {
        document.getElementById('pmStatCount').textContent = count || '0';
        document.getElementById('pmStatTotal').textContent = _fmt(total);
        document.getElementById('pmStatCash').textContent  = _fmt(cash);
        document.getElementById('pmStatBank').textContent  = _fmt(bank);
    }

    /** Fetch data from server and render. */
    function _fetch() {
        var tbody = document.getElementById('pmTableBody');
        tbody.innerHTML = '<tr><td colspan="7" class="rpt-loading-cell">'
            + '<div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div></td></tr>';
        document.getElementById('pmTableFoot').classList.add('d-none');
        document.getElementById('pmTblFooter').classList.add('d-none');

        ajaxLoading(1);
        $.ajax({
            url      : '/reports/getPaymentMadeData',
            type     : 'GET',
            dataType : 'json',
            data     : { from: _from, to: _to },
            success  : function (res) {
                ajaxLoading(0);
                if (!res || res.Status !== 'Success') {
                    tbody.innerHTML = '<tr><td colspan="7" class="rpt-loading-cell text-danger">'
                        + (res && res.Message ? res.Message : 'Server error') + '</td></tr>';
                    return;
                }
                _rows = res.rows || [];
                _render(_rows);
            },
            error: function () {
                ajaxLoading(0);
                tbody.innerHTML = '<tr><td colspan="7" class="rpt-loading-cell text-danger">Request failed. Please try again.</td></tr>';
            },
        });
    }

    /** Wire up Flatpickr date pickers. */
    function _initDatePickers() {
        _fpFrom = flatpickr('#pmFromDisplay', {
            dateFormat  : 'Y-m-d',
            altInput    : true,
            altFormat   : _pmListFmt,
            defaultDate : _pmInitFrom,
            onChange    : function (dates, str) { _from = str; },
        });
        _fpTo = flatpickr('#pmToDisplay', {
            dateFormat  : 'Y-m-d',
            altInput    : true,
            altFormat   : _pmListFmt,
            defaultDate : _pmInitTo,
            onChange    : function (dates, str) { _to = str; },
        });
    }

    /** Initialise the page. */
    function _init() {
        _initDatePickers();
        document.getElementById('pmApplyBtn').addEventListener('click', _fetch);
        _fetch();
    }

    $(document).ready(_init);

}());
