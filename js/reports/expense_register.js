(function () {
    'use strict';

    var _dec  = (typeof genSettings !== 'undefined' && genSettings.DecimalPlaces != null) ? parseInt(genSettings.DecimalPlaces, 10) : 2;
    var _curr = (typeof genSettings !== 'undefined' && genSettings.CurrencySymbol) ? genSettings.CurrencySymbol : '₹';

    /**
     * @param {string} s
     * @returns {string}
     */
    function _esc(s) {
        if (s == null) return '';
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    /**
     * @param {number|string} n
     * @returns {string}
     */
    function _fmt(n) {
        var v = parseFloat(n) || 0;
        return _curr + ' ' + v.toFixed(_dec).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    /**
     * @param {string} iso
     * @returns {string}
     */
    function _fmtDate(iso) {
        if (!iso) return '—';
        var d = new Date(iso);
        if (isNaN(d.getTime())) return iso;
        return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    /**
     * @param {string} status
     * @returns {string}
     */
    function _statusBadge(status) {
        var map = {
            'Approved': 'reg-status-issued',
            'Paid':     'reg-status-paid',
        };
        var cls = map[status] || 'reg-status-other';
        return '<span class="reg-status ' + cls + '">' + _esc(status) + '</span>';
    }

    /**
     * @param {number|string} isPaid
     * @returns {string}
     */
    function _paidBadge(isPaid) {
        if (parseInt(isPaid, 10) === 1) {
            return '<span class="exr-paid-chip">Paid</span>';
        }
        return '<span class="exr-unpaid-chip">Unpaid</span>';
    }

    /** @type {Array<Object>} */
    var _rows = [];

    /**
     * @param {Array<Object>} rows
     * @returns {void}
     */
    function _render(rows) {
        var tbody = document.getElementById('exrTableBody');
        var tfoot = document.getElementById('exrTableFoot');
        var footer = document.getElementById('exrTblFooter');

        if (!rows || rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="10">' +
                '<div class="rpt-empty"><i class="bx bx-wallet-alt"></i>' +
                '<div class="rpt-empty-title">No expenses found</div>' +
                '<div>Try adjusting the date range</div></div></td></tr>';
            tfoot.classList.add('d-none');
            footer.classList.add('d-none');
            return;
        }

        var totAmount = 0, totTax = 0, totNet = 0;
        var uniqueCats = {};
        var html = '';

        rows.forEach(function (r, i) {
            var amount = parseFloat(r.Amount)    || 0;
            var tax    = parseFloat(r.TaxAmount) || 0;
            var net    = parseFloat(r.NetAmount) || 0;
            totAmount += amount; totTax += tax; totNet += net;
            if (r.CategoryName && r.CategoryName !== '—') uniqueCats[r.CategoryName] = true;

            html += '<tr>' +
                '<td>' + (i + 1) + '</td>' +
                '<td>' + _esc(r.DocNo) + '</td>' +
                '<td>' + _fmtDate(r.TransDate) + '</td>' +
                '<td>' + _esc(r.VendorName) + '</td>' +
                '<td><span class="exr-cat-chip">' + _esc(r.CategoryName) + '</span></td>' +
                '<td class="rpt-col-num">' + amount.toFixed(_dec) + '</td>' +
                '<td class="rpt-col-num rpt-num-yellow">' + tax.toFixed(_dec) + '</td>' +
                '<td class="rpt-col-num rpt-num-purple">' + net.toFixed(_dec) + '</td>' +
                '<td>' + _paidBadge(r.IsPaid) + '</td>' +
                '<td>' + _statusBadge(r.DocStatus) + '</td>' +
                '</tr>';
        });
        tbody.innerHTML = html;

        document.getElementById('exrFtAmount').textContent = totAmount.toFixed(_dec);
        document.getElementById('exrFtTax').textContent    = totTax.toFixed(_dec);
        document.getElementById('exrFtNet').textContent    = totNet.toFixed(_dec);
        tfoot.classList.remove('d-none');

        _updateStats(rows, uniqueCats, totTax, totNet);

        document.getElementById('exrRowCount').textContent  = rows.length + ' expense' + (rows.length !== 1 ? 's' : '');
        document.getElementById('exrFooterTax').textContent = _fmt(totTax);
        document.getElementById('exrFooterNet').textContent = _fmt(totNet);
        footer.classList.remove('d-none');
    }

    /**
     * @param {Array<Object>} rows
     * @param {Object} uniqueCats
     * @param {number} totTax
     * @param {number} totNet
     * @returns {void}
     */
    function _updateStats(rows, uniqueCats, totTax, totNet) {
        document.getElementById('exrStatCount').textContent = rows.length;
        document.getElementById('exrStatCats').textContent  = Object.keys(uniqueCats).length;
        document.getElementById('exrStatTax').textContent   = _fmt(totTax);
        document.getElementById('exrStatNet').textContent   = _fmt(totNet);
    }

    /** @returns {void} */
    function _fetch() {
        var from = document.getElementById('exrFrom').value;
        var to   = document.getElementById('exrTo').value;

        ajaxLoading(1);
        document.getElementById('exrTableBody').innerHTML =
            '<tr><td colspan="10" class="rpt-loading-cell">' +
            '<div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div>' +
            '</td></tr>';
        document.getElementById('exrTableFoot').classList.add('d-none');
        document.getElementById('exrTblFooter').classList.add('d-none');

        $.get('/reports/getExpenseRegisterData', { from: from, to: to })
            .done(function (res) {
                if (res.Status !== 'Success') {
                    document.getElementById('exrTableBody').innerHTML =
                        '<tr><td colspan="10" class="text-center text-danger py-4">' + _esc(res.Message || 'Error loading data') + '</td></tr>';
                    return;
                }
                _rows = res.rows || [];
                _render(_rows);
            })
            .fail(function () {
                document.getElementById('exrTableBody').innerHTML =
                    '<tr><td colspan="10" class="text-center text-danger py-4">Request failed. Please try again.</td></tr>';
            })
            .always(function () { ajaxLoading(0); });
    }

    /** @returns {void} */
    function _initDatePickers() {
        var initFrom = (typeof _exrInitFrom !== 'undefined') ? _exrInitFrom : '';
        var initTo   = (typeof _exrInitTo   !== 'undefined') ? _exrInitTo   : '';

        flatpickr('#exrFromDisplay', {
            static: true,
            position: 'below left',
            dateFormat: 'Y-m-d',
            defaultDate: initFrom || undefined,
            onChange: function (sel, dateStr) {
                document.getElementById('exrFrom').value = dateStr;
            }
        });
        if (initFrom) {
            document.getElementById('exrFromDisplay').value = _fmtDate(initFrom);
        }

        flatpickr('#exrToDisplay', {
            static: true,
            position: 'below left',
            dateFormat: 'Y-m-d',
            defaultDate: initTo || undefined,
            onChange: function (sel, dateStr) {
                document.getElementById('exrTo').value = dateStr;
            }
        });
        if (initTo) {
            document.getElementById('exrToDisplay').value = _fmtDate(initTo);
        }
    }

    /** @returns {void} */
    function _init() {
        _initDatePickers();
        document.getElementById('exrApplyBtn').addEventListener('click', _fetch);
        _fetch();
    }

    $(document).ready(_init);
})();
