(function () {
    'use strict';

    var _sym = (genSettings && genSettings.CurrenySymbol) ? genSettings.CurrenySymbol : '₹';
    var _dec = 2;

    /**
     * @param {number} n
     * @returns {string}
     */
    function _fmt(n) {
        return _sym + ' ' + parseFloat(n || 0).toFixed(_dec);
    }

    /**
     * @param {string} str
     * @returns {string}
     */
    function _esc(str) {
        return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    /**
     * @param {Array} rows
     * @returns {void}
     */
    function _render(rows) {
        var $tbody  = $('#coTableBody');
        var $tfoot  = $('#coTableFoot');
        var $footer = $('#coTblFooter');

        if (!rows || rows.length === 0) {
            $tbody.html('<tr><td colspan="8"><div class="rpt-empty"><i class="bx bx-info-circle"></i><div class="rpt-empty-title">No outstanding invoices</div><div style="font-size:var(--r2k-text-82)">All customers are fully paid</div></div></td></tr>');
            $tfoot.addClass('d-none');
            $footer.addClass('d-none');
            _updateStats(0, 0, 0, 0);
            return;
        }

        var html = '';
        var totInvoices = 0, totBilled = 0, totPaid = 0, totOutstanding = 0;

        rows.forEach(function (r, i) {
            var inv  = parseInt(r.InvoiceCount     || 0, 10);
            var bill = parseFloat(r.TotalBilled    || 0);
            var paid = parseFloat(r.TotalPaid      || 0);
            var owed = parseFloat(r.TotalOutstanding || 0);
            totInvoices    += inv;
            totBilled      += bill;
            totPaid        += paid;
            totOutstanding += owed;

            html += '<tr>';
            html += '<td>' + (i + 1) + '</td>';
            html += '<td>' + _esc(r.CustomerName) + '</td>';
            html += '<td>' + _esc(r.MobileNumber || '—') + '</td>';
            html += '<td>' + _esc(r.Area || '—') + '</td>';
            html += '<td class="rpt-col-num">' + inv + '</td>';
            html += '<td class="rpt-col-num">' + _fmt(bill) + '</td>';
            html += '<td class="rpt-col-num">' + _fmt(paid) + '</td>';
            html += '<td class="rpt-col-num rpt-num-red">' + _fmt(owed) + '</td>';
            html += '</tr>';
        });

        $tbody.html(html);
        $('#coFtInvoices').text(totInvoices);
        $('#coFtBilled').text(_fmt(totBilled));
        $('#coFtPaid').text(_fmt(totPaid));
        $('#coFtOutstanding').text(_fmt(totOutstanding));
        $tfoot.removeClass('d-none');
        $('#coRowCount').text(rows.length + ' customer' + (rows.length !== 1 ? 's' : ''));
        $('#coFooterOutstanding').text(_fmt(totOutstanding));
        $footer.removeClass('d-none');
        _updateStats(rows.length, totInvoices, totBilled, totOutstanding);
    }

    /**
     * @param {number} customers
     * @param {number} invoices
     * @param {number} billed
     * @param {number} outstanding
     * @returns {void}
     */
    function _updateStats(customers, invoices, billed, outstanding) {
        $('#coStatCustomers').text(customers);
        $('#coStatInvoices').text(invoices);
        $('#coStatBilled').text(_fmt(billed));
        $('#coStatOutstanding').text(_fmt(outstanding));
    }

    /**
     * @returns {void}
     */
    function _fetch() {
        var $tbody = $('#coTableBody');
        $tbody.html('<tr><td colspan="8" class="rpt-loading-cell"><div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div></td></tr>');
        $('#coTableFoot').addClass('d-none');
        $('#coTblFooter').addClass('d-none');

        ajaxLoading(1);
        $.ajax({
            url     : global_services_url + 'api/reports/getCustomerOutstandingData',
            data    : { orgId: parseInt(JwtData.Org.OrgUID, 10), langCode: JwtData.User.UILanguage || 'en' },
            type    : 'GET',
            dataType: 'json',
            headers : { Authorization: 'Bearer ' + JwtToken },
            success : function (res) {
                if (!res || res.Error) {
                    $tbody.html('<tr><td colspan="8" class="text-center p-4 text-danger">' + _esc((res && res.Message) ? res.Message : 'Failed to load data') + '</td></tr>');
                    return;
                }
                _render(res.rows || []);
            },
            error   : function () {
                $tbody.html('<tr><td colspan="8" class="text-center p-4 text-danger">Network error. Please try again.</td></tr>');
            }
        }).always(function () { ajaxLoading(0); });
    }

    /**
     * @param {string} format
     * @returns {void}
     */
    function _handleExport(format) {
        var type    = format === 'pdf' ? 'Pdf' : (format === 'print' ? 'Print' : (format === 'excel' ? 'Excel' : 'CSV'));
        var payload = { orgId: parseInt(JwtData.Org.OrgUID, 10), Type: type };

        if (type === 'Print') {
            $.ajax({
                url         : global_services_url + 'api/reports/exportCustomerOutstanding',
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
                url         : global_services_url + 'api/reports/exportCustomerOutstanding',
                method      : 'POST',
                contentType : 'application/json',
                headers     : { Authorization: 'Bearer ' + JwtToken },
                data        : JSON.stringify(payload),
                xhrFields   : { responseType: 'blob' },
                success : function (blob) {
                    var url = URL.createObjectURL(blob);
                    var $a  = $('<a>').attr({ href: url, download: 'customer_outstanding.' + ext }).appendTo('body');
                    $a[0].click();
                    setTimeout(function () { URL.revokeObjectURL(url); $a.remove(); }, 3000);
                },
                error : function () { showToastNotification('Export failed. Please try again.', 'error'); }
            });
        }
    }

    /**
     * @returns {void}
     */
    function _init() {
        $('#coRefreshBtn').on('click', _fetch);
        $(document).on('click', '.r2k-export-btn[data-format]', function () {
            _handleExport($(this).data('format'));
        });
        _fetch();
    }

    $(document).ready(_init);
}());
