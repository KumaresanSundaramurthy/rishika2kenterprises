/**
 * Payment Received Report
 * Lists all customer receipts for the selected date range.
 */

(function () {
    'use strict';

    var MONTHS = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

    var _from = _prInitFrom;
    var _to   = _prInitTo;
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
        return _prListFmt
            .replace('d', d[2])
            .replace('M', MONTHS[parseInt(d[1], 10) - 1])
            .replace('m', d[1])
            .replace('Y', d[0]);
    }

    /**
     * @param {Array} rows
     */
    function _render(rows) {
        var $tbody  = $('#prTableBody');
        var $tfoot  = $('#prTableFoot');
        var $footer = $('#prTblFooter');

        if (!rows.length) {
            $tbody.html('<tr><td colspan="7">'
                + '<div class="rpt-empty"><i class="bx bx-receipt"></i>'
                + '<div class="rpt-empty-title">No receipts found</div>'
                + '<div>Try adjusting the date range</div></div></td></tr>');
            $tfoot.addClass('d-none');
            $footer.addClass('d-none');
            _updateStats(0, 0, 0, 0);
            return;
        }

        var totalAmt  = 0;
        var cashAmt   = 0;
        var bankAmt   = 0;

        var html = '';
        rows.forEach(function (r) {
            var amt   = parseFloat(r.Amount) || 0;
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
                + '<td class="rpt-col-num rpt-num-green">' + _fmt(amt) + '</td>'
                + '</tr>';
        });

        $tbody.html(html);

        // Tfoot
        $('#prFtTotal').text(_fmt(totalAmt));
        $tfoot.removeClass('d-none');

        // Bottom footer
        $('#prRowCount').text(rows.length + ' receipt(s)');
        $('#prFooterCash').text(_fmt(cashAmt));
        $('#prFooterBank').text(_fmt(bankAmt));
        $('#prFooterTotal').text(_fmt(totalAmt));
        $footer.removeClass('d-none');

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
        $('#prStatCount').text(count || '0');
        $('#prStatTotal').text(_fmt(total));
        $('#prStatCash').text(_fmt(cash));
        $('#prStatBank').text(_fmt(bank));
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
                url         : global_services_url + 'api/reports/exportPaymentReceived',
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
                url         : global_services_url + 'api/reports/exportPaymentReceived',
                method      : 'POST',
                contentType : 'application/json',
                headers     : { Authorization: 'Bearer ' + JwtToken },
                data        : JSON.stringify(payload),
                xhrFields   : { responseType: 'blob' },
                success : function (blob) {
                    var url = URL.createObjectURL(blob);
                    var $a  = $('<a>').attr({ href: url, download: 'payment_received.' + ext }).appendTo('body');
                    $a[0].click();
                    setTimeout(function () { URL.revokeObjectURL(url); $a.remove(); }, 3000);
                },
                error : function () { showToastNotification('Export failed. Please try again.', 'error'); }
            });
        }
    }

    /** Fetch data from server and render. */
    function _fetch() {
        var $tbody = $('#prTableBody');
        $tbody.html('<tr><td colspan="7" class="rpt-loading-cell">'
            + '<div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div></td></tr>');
        $('#prTableFoot').addClass('d-none');
        $('#prTblFooter').addClass('d-none');

        ajaxLoading(1);
        $.ajax({
            url      : global_services_url + 'api/reports/getPaymentReceivedData',
            type     : 'GET',
            dataType : 'json',
            headers  : { Authorization: 'Bearer ' + JwtToken },
            data     : { orgId: parseInt(JwtData.Org.OrgUID, 10), from: _from, to: _to },
            success  : function (res) {
                ajaxLoading(0);
                if (!res || res.Error) {
                    $tbody.html('<tr><td colspan="7" class="rpt-loading-cell text-danger">'
                        + (res && res.Message ? res.Message : 'Server error') + '</td></tr>');
                    return;
                }
                _rows = res.rows || [];
                _render(_rows);
            },
            error: function () {
                ajaxLoading(0);
                $tbody.html('<tr><td colspan="7" class="rpt-loading-cell text-danger">Request failed. Please try again.</td></tr>');
            },
        });
    }

    /** Wire up Flatpickr date pickers. */
    function _initDatePickers() {
        _fpFrom = flatpickr('#prFromDisplay', {
            dateFormat  : 'Y-m-d',
            altInput    : true,
            altFormat   : _prListFmt,
            defaultDate : _prInitFrom,
            onChange    : function (dates, str) { _from = str; },
        });
        _fpTo = flatpickr('#prToDisplay', {
            dateFormat  : 'Y-m-d',
            altInput    : true,
            altFormat   : _prListFmt,
            defaultDate : _prInitTo,
            onChange    : function (dates, str) { _to = str; },
        });
    }

    /** Initialise the page. */
    function _init() {
        _initDatePickers();
        $('#prApplyBtn').on('click', _fetch);
        $(document).on('click', '.r2k-export-btn[data-format]', function () {
            _handleExport($(this).data('format'));
        });
        _fetch();
    }

    $(document).ready(_init);

}());
