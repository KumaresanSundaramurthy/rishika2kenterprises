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
            'Dispatched':  'reg-status-dispatched',
            'Converted':   'reg-status-converted',
        };
        var cls = map[status] || 'reg-status-other';
        return '<span class="reg-status ' + cls + '">' + _esc(status) + '</span>';
    }

    /** @type {Array<Object>} */
    var _rows = [];

    /**
     * @param {Array<Object>} rows
     * @returns {void}
     */
    function _render(rows) {
        var tbody = document.getElementById('dcrTableBody');
        var tfoot = document.getElementById('dcrTableFoot');
        var footer = document.getElementById('dcrTblFooter');

        if (!rows || rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8">' +
                '<div class="rpt-empty"><i class="bx bx-package"></i>' +
                '<div class="rpt-empty-title">No challans found</div>' +
                '<div>Try adjusting the date range</div></div></td></tr>';
            tfoot.classList.add('d-none');
            footer.classList.add('d-none');
            return;
        }

        var totTaxable = 0, totTax = 0, totNet = 0;
        var uniqueParties = {};
        var pendingCount = 0;
        var html = '';

        rows.forEach(function (r, i) {
            var taxable = parseFloat(r.Taxable)   || 0;
            var tax     = parseFloat(r.TaxAmount) || 0;
            var net     = parseFloat(r.NetAmount) || 0;
            totTaxable += taxable; totTax += tax; totNet += net;
            if (r.PartyName && r.PartyName !== '—') uniqueParties[r.PartyName] = true;
            if (r.DocStatus !== 'Converted') pendingCount++;

            html += '<tr>' +
                '<td>' + (i + 1) + '</td>' +
                '<td>' + _esc(r.DocNo) + '</td>' +
                '<td>' + _fmtDate(r.TransDate) + '</td>' +
                '<td>' + _esc(r.PartyName) + '</td>' +
                '<td class="rpt-col-num">' + taxable.toFixed(_dec) + '</td>' +
                '<td class="rpt-col-num">' + tax.toFixed(_dec) + '</td>' +
                '<td class="rpt-col-num rpt-num-blue">' + net.toFixed(_dec) + '</td>' +
                '<td>' + _statusBadge(r.DocStatus) + '</td>' +
                '</tr>';
        });
        tbody.innerHTML = html;

        document.getElementById('dcrFtTaxable').textContent = totTaxable.toFixed(_dec);
        document.getElementById('dcrFtTax').textContent     = totTax.toFixed(_dec);
        document.getElementById('dcrFtNet').textContent     = totNet.toFixed(_dec);
        tfoot.classList.remove('d-none');

        _updateStats(rows, uniqueParties, pendingCount, totNet);

        document.getElementById('dcrRowCount').textContent  = rows.length + ' challan' + (rows.length !== 1 ? 's' : '');
        document.getElementById('dcrFooterNet').textContent = _fmt(totNet);
        footer.classList.remove('d-none');
    }

    /**
     * @param {Array<Object>} rows
     * @param {Object} uniqueParties
     * @param {number} pendingCount
     * @param {number} totNet
     * @returns {void}
     */
    function _updateStats(rows, uniqueParties, pendingCount, totNet) {
        document.getElementById('dcrStatCount').textContent     = rows.length;
        document.getElementById('dcrStatCustomers').textContent = Object.keys(uniqueParties).length;
        document.getElementById('dcrStatPending').textContent   = pendingCount;
        document.getElementById('dcrStatNet').textContent       = _fmt(totNet);
    }

    /** @returns {void} */
    function _fetch() {
        var from = document.getElementById('dcrFrom').value;
        var to   = document.getElementById('dcrTo').value;

        ajaxLoading(1);
        document.getElementById('dcrTableBody').innerHTML =
            '<tr><td colspan="8" class="rpt-loading-cell">' +
            '<div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div>' +
            '</td></tr>';
        document.getElementById('dcrTableFoot').classList.add('d-none');
        document.getElementById('dcrTblFooter').classList.add('d-none');

        $.get('/reports/getDeliveryChallanRegisterData', { from: from, to: to })
            .done(function (res) {
                if (res.Status !== 'Success') {
                    document.getElementById('dcrTableBody').innerHTML =
                        '<tr><td colspan="8" class="text-center text-danger py-4">' + _esc(res.Message || 'Error loading data') + '</td></tr>';
                    return;
                }
                _rows = res.rows || [];
                _render(_rows);
            })
            .fail(function () {
                document.getElementById('dcrTableBody').innerHTML =
                    '<tr><td colspan="8" class="text-center text-danger py-4">Request failed. Please try again.</td></tr>';
            })
            .always(function () { ajaxLoading(0); });
    }

    /** @returns {void} */
    function _initDatePickers() {
        var initFrom = (typeof _dcrInitFrom !== 'undefined') ? _dcrInitFrom : '';
        var initTo   = (typeof _dcrInitTo   !== 'undefined') ? _dcrInitTo   : '';

        flatpickr('#dcrFromDisplay', {
            static: true,
            position: 'below left',
            dateFormat: 'Y-m-d',
            defaultDate: initFrom || undefined,
            onChange: function (sel, dateStr) {
                document.getElementById('dcrFrom').value = dateStr;
            }
        });
        if (initFrom) {
            document.getElementById('dcrFromDisplay').value = _fmtDate(initFrom);
        }

        flatpickr('#dcrToDisplay', {
            static: true,
            position: 'below left',
            dateFormat: 'Y-m-d',
            defaultDate: initTo || undefined,
            onChange: function (sel, dateStr) {
                document.getElementById('dcrTo').value = dateStr;
            }
        });
        if (initTo) {
            document.getElementById('dcrToDisplay').value = _fmtDate(initTo);
        }
    }

    /** @returns {void} */
    function _init() {
        _initDatePickers();
        document.getElementById('dcrApplyBtn').addEventListener('click', _fetch);
        _fetch();
    }

    $(document).ready(_init);
})();
