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
        var tbody = document.getElementById('coTableBody');
        var tfoot = document.getElementById('coTableFoot');
        var footer = document.getElementById('coTblFooter');

        if (!rows || rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8"><div class="rpt-empty"><i class="bx bx-info-circle"></i><div class="rpt-empty-title">No outstanding invoices</div><div style="font-size:.82rem">All customers are fully paid</div></div></td></tr>';
            tfoot.classList.add('d-none');
            footer.classList.add('d-none');
            _updateStats(0, 0, 0, 0);
            return;
        }

        var html = '';
        var totInvoices = 0, totBilled = 0, totPaid = 0, totOutstanding = 0;

        rows.forEach(function (r, i) {
            var inv   = parseInt(r.InvoiceCount || 0, 10);
            var bill  = parseFloat(r.TotalBilled || 0);
            var paid  = parseFloat(r.TotalPaid || 0);
            var owed  = parseFloat(r.TotalOutstanding || 0);
            totInvoices   += inv;
            totBilled     += bill;
            totPaid       += paid;
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

        tbody.innerHTML = html;

        document.getElementById('coFtInvoices').textContent    = totInvoices;
        document.getElementById('coFtBilled').textContent      = _fmt(totBilled);
        document.getElementById('coFtPaid').textContent        = _fmt(totPaid);
        document.getElementById('coFtOutstanding').textContent = _fmt(totOutstanding);
        tfoot.classList.remove('d-none');

        document.getElementById('coRowCount').textContent       = rows.length + ' customer' + (rows.length !== 1 ? 's' : '');
        document.getElementById('coFooterOutstanding').textContent = _fmt(totOutstanding);
        footer.classList.remove('d-none');

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
        document.getElementById('coStatCustomers').textContent  = customers;
        document.getElementById('coStatInvoices').textContent   = invoices;
        document.getElementById('coStatBilled').textContent     = _fmt(billed);
        document.getElementById('coStatOutstanding').textContent = _fmt(outstanding);
    }

    /**
     * @returns {void}
     */
    function _fetch() {
        var tbody = document.getElementById('coTableBody');
        tbody.innerHTML = '<tr><td colspan="8" class="rpt-loading-cell"><div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div></td></tr>';
        document.getElementById('coTableFoot').classList.add('d-none');
        document.getElementById('coTblFooter').classList.add('d-none');

        $.ajax({
            url: '/reports/getCustomerOutstandingData',
            type: 'GET',
            dataType: 'json',
            success: function (res) {
                if (res && res.Status === 'Success') {
                    _render(res.rows || []);
                } else {
                    tbody.innerHTML = '<tr><td colspan="8" class="text-center p-4 text-danger">' + _esc((res && res.Message) ? res.Message : 'Failed to load data') + '</td></tr>';
                }
            },
            error: function () {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center p-4 text-danger">Network error. Please try again.</td></tr>';
            }
        });
    }

    /**
     * @returns {void}
     */
    function _init() {
        _fetch();
    }

    $(document).ready(_init);
}());
