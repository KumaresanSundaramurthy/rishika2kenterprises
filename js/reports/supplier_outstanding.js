(function () {
    'use strict';

    var _sym = (genSettings && genSettings.CurrenySymbol) ? genSettings.CurrenySymbol : '₹';
    var _dec = (genSettings && genSettings.DecimalPoints)  ? parseInt(genSettings.DecimalPoints, 10) : 2;

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
        var tbody  = document.getElementById('soTableBody');
        var tfoot  = document.getElementById('soTableFoot');
        var footer = document.getElementById('soTblFooter');

        if (!rows || rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8"><div class="rpt-empty"><i class="bx bx-info-circle"></i><div class="rpt-empty-title">No outstanding bills</div><div style="font-size:.82rem">All supplier bills are fully paid</div></div></td></tr>';
            tfoot.classList.add('d-none');
            footer.classList.add('d-none');
            _updateStats(0, 0, 0, 0);
            return;
        }

        var html = '';
        var totBills = 0, totBilled = 0, totPaid = 0, totOutstanding = 0;

        rows.forEach(function (r, i) {
            var bills = parseInt(r.BillCount || 0, 10);
            var bill  = parseFloat(r.TotalBilled || 0);
            var paid  = parseFloat(r.TotalPaid || 0);
            var owed  = parseFloat(r.TotalOutstanding || 0);
            totBills        += bills;
            totBilled       += bill;
            totPaid         += paid;
            totOutstanding  += owed;

            html += '<tr>';
            html += '<td>' + (i + 1) + '</td>';
            html += '<td>' + _esc(r.VendorName) + '</td>';
            html += '<td>' + _esc(r.MobileNumber || '—') + '</td>';
            html += '<td>' + _esc(r.Area || '—') + '</td>';
            html += '<td class="rpt-col-num">' + bills + '</td>';
            html += '<td class="rpt-col-num rpt-num-blue">' + _fmt(bill) + '</td>';
            html += '<td class="rpt-col-num">' + _fmt(paid) + '</td>';
            html += '<td class="rpt-col-num rpt-num-orange">' + _fmt(owed) + '</td>';
            html += '</tr>';
        });

        tbody.innerHTML = html;

        document.getElementById('soFtBills').textContent       = totBills;
        document.getElementById('soFtBilled').textContent      = _fmt(totBilled);
        document.getElementById('soFtPaid').textContent        = _fmt(totPaid);
        document.getElementById('soFtOutstanding').textContent = _fmt(totOutstanding);
        tfoot.classList.remove('d-none');

        document.getElementById('soRowCount').textContent          = rows.length + ' supplier' + (rows.length !== 1 ? 's' : '');
        document.getElementById('soFooterOutstanding').textContent = _fmt(totOutstanding);
        footer.classList.remove('d-none');

        _updateStats(rows.length, totBills, totBilled, totOutstanding);
    }

    /**
     * @param {number} suppliers
     * @param {number} bills
     * @param {number} billed
     * @param {number} outstanding
     * @returns {void}
     */
    function _updateStats(suppliers, bills, billed, outstanding) {
        document.getElementById('soStatSuppliers').textContent  = suppliers;
        document.getElementById('soStatBills').textContent      = bills;
        document.getElementById('soStatBilled').textContent     = _fmt(billed);
        document.getElementById('soStatOutstanding').textContent = _fmt(outstanding);
    }

    /**
     * @returns {void}
     */
    function _fetch() {
        var tbody = document.getElementById('soTableBody');
        tbody.innerHTML = '<tr><td colspan="8" class="rpt-loading-cell"><div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div></td></tr>';
        document.getElementById('soTableFoot').classList.add('d-none');
        document.getElementById('soTblFooter').classList.add('d-none');

        $.ajax({
            url: '/reports/getSupplierOutstandingData',
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
