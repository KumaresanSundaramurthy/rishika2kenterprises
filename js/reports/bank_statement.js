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
        var $tbody  = $('#bsTableBody');
        var $tfoot  = $('#bsTableFoot');
        var $footer = $('#bsTblFooter');

        if (!rows.length) {
            $tbody.html('<tr><td colspan="9">'
                + '<div class="rpt-empty"><i class="bx bx-bank"></i>'
                + '<div class="rpt-empty-title">No transactions found</div>'
                + '<div>Try adjusting the date range or account filter</div></div></td></tr>');
            $tfoot.addClass('d-none');
            $footer.addClass('d-none');
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

        $tbody.html(html);

        // Tfoot
        var netBal = totalCredit - totalDebit;
        var balCls = netBal >= 0 ? 'bs-balance-pos' : 'bs-balance-neg';
        $('#bsFtCredit').text(_fmt(totalCredit));
        $('#bsFtDebit').text(_fmt(totalDebit));
        $('#bsFtBalance').html('<span class="' + balCls + '">' + _fmt(netBal) + '</span>');
        $tfoot.removeClass('d-none');

        // Bottom footer chips
        $('#bsRowCount').text(rows.length + ' transaction(s)');
        $('#bsFooterIn').text(_fmt(totalCredit));
        $('#bsFooterOut').text(_fmt(totalDebit));
        $('#bsFooterBalance').text(_fmt(netBal));
        $footer.removeClass('d-none');

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
        $('#bsStatIn').text(_fmt(totalIn));
        $('#bsStatOut').text(_fmt(totalOut));
        $('#bsStatBalance').text(_fmt(balance));
        $('#bsStatCount').text(count || '0');
    }

    /**
     * @param {string} format
     * @returns {void}
     */
    function _handleExport(format) {
        var type    = format === 'pdf' ? 'Pdf' : (format === 'print' ? 'Print' : (format === 'excel' ? 'Excel' : 'CSV'));
        var payload = { orgId: parseInt(JwtData.Org.OrgUID, 10), from: _from, to: _to, account: _accountUID, Type: type };

        if (type === 'Print') {
            $.ajax({
                url         : global_services_url + 'api/reports/exportBankStatement',
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
                url         : global_services_url + 'api/reports/exportBankStatement',
                method      : 'POST',
                contentType : 'application/json',
                headers     : { Authorization: 'Bearer ' + JwtToken },
                data        : JSON.stringify(payload),
                xhrFields   : { responseType: 'blob' },
                success : function (blob) {
                    var url = URL.createObjectURL(blob);
                    var $a  = $('<a>').attr({ href: url, download: 'bank_statement.' + ext }).appendTo('body');
                    $a[0].click();
                    setTimeout(function () { URL.revokeObjectURL(url); $a.remove(); }, 3000);
                },
                error : function () { showToastNotification('Export failed. Please try again.', 'error'); }
            });
        }
    }

    /** Fetch data from server and render. */
    function _fetch() {
        var $tbody = $('#bsTableBody');
        $tbody.html('<tr><td colspan="9" class="rpt-loading-cell">'
            + '<div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div></td></tr>');
        $('#bsTableFoot').addClass('d-none');
        $('#bsTblFooter').addClass('d-none');

        ajaxLoading(1);
        $.ajax({
            url      : global_services_url + 'api/reports/getBankStatementData',
            type     : 'GET',
            dataType : 'json',
            headers  : { Authorization: 'Bearer ' + JwtToken },
            data     : { orgId: parseInt(JwtData.Org.OrgUID, 10), from: _from, to: _to, account: _accountUID },
            success  : function (res) {
                ajaxLoading(0);
                if (!res || res.Error) {
                    $tbody.html('<tr><td colspan="9" class="rpt-loading-cell text-danger">'
                        + (res && res.Message ? res.Message : 'Server error') + '</td></tr>');
                    return;
                }
                _rows = res.rows || [];
                _render(_rows);
            },
            error: function () {
                ajaxLoading(0);
                $tbody.html('<tr><td colspan="9" class="rpt-loading-cell text-danger">Request failed. Please try again.</td></tr>');
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

        $('#bsAccount').on('change', function () {
            _accountUID = parseInt($(this).val(), 10) || 0;
            _fetch();
        });

        $('#bsApplyBtn').on('click', _fetch);

        $(document).on('click', '.r2k-export-btn[data-format]', function () {
            _handleExport($(this).data('format'));
        });

        _fetch();
    }

    $(document).ready(_init);

}());
