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
            'Issued':   'reg-status-issued',
            'Paid':     'reg-status-paid',
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
        var tbody = document.getElementById('prrTableBody');
        var tfoot = document.getElementById('prrTableFoot');
        var footer = document.getElementById('prrTblFooter');

        if (!rows || rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="11">' +
                '<div class="rpt-empty"><i class="bx bx-revision"></i>' +
                '<div class="rpt-empty-title">No debit notes found</div>' +
                '<div>Try adjusting the date range</div></div></td></tr>';
            tfoot.classList.add('d-none');
            footer.classList.add('d-none');
            return;
        }

        var totTaxable = 0, totCgst = 0, totSgst = 0, totIgst = 0, totTax = 0, totNet = 0;
        var html = '';
        rows.forEach(function (r, i) {
            var taxable = parseFloat(r.Taxable)    || 0;
            var cgst    = parseFloat(r.CgstAmount) || 0;
            var sgst    = parseFloat(r.SgstAmount) || 0;
            var igst    = parseFloat(r.IgstAmount) || 0;
            var tax     = parseFloat(r.TaxAmount)  || 0;
            var net     = parseFloat(r.NetAmount)  || 0;
            totTaxable += taxable; totCgst += cgst; totSgst += sgst;
            totIgst += igst; totTax += tax; totNet += net;

            html += '<tr>' +
                '<td>' + (i + 1) + '</td>' +
                '<td>' + _esc(r.DocNo) + '</td>' +
                '<td>' + _fmtDate(r.TransDate) + '</td>' +
                '<td>' + _esc(r.PartyName) + '</td>' +
                '<td class="rpt-col-num">' + taxable.toFixed(_dec) + '</td>' +
                '<td class="rpt-col-num">' + cgst.toFixed(_dec) + '</td>' +
                '<td class="rpt-col-num">' + sgst.toFixed(_dec) + '</td>' +
                '<td class="rpt-col-num">' + igst.toFixed(_dec) + '</td>' +
                '<td class="rpt-col-num rpt-num-yellow">' + tax.toFixed(_dec) + '</td>' +
                '<td class="rpt-col-num rpt-num-orange">' + net.toFixed(_dec) + '</td>' +
                '<td>' + _statusBadge(r.DocStatus) + '</td>' +
                '</tr>';
        });
        tbody.innerHTML = html;

        document.getElementById('prrFtTaxable').textContent = totTaxable.toFixed(_dec);
        document.getElementById('prrFtCgst').textContent    = totCgst.toFixed(_dec);
        document.getElementById('prrFtSgst').textContent    = totSgst.toFixed(_dec);
        document.getElementById('prrFtIgst').textContent    = totIgst.toFixed(_dec);
        document.getElementById('prrFtTax').textContent     = totTax.toFixed(_dec);
        document.getElementById('prrFtNet').textContent     = totNet.toFixed(_dec);
        tfoot.classList.remove('d-none');

        _updateStats(rows, totTaxable, totTax, totNet);

        document.getElementById('prrRowCount').textContent  = rows.length + ' debit note' + (rows.length !== 1 ? 's' : '');
        document.getElementById('prrFooterTax').textContent = _fmt(totTax);
        document.getElementById('prrFooterNet').textContent = _fmt(totNet);
        footer.classList.remove('d-none');
    }

    /**
     * @param {Array<Object>} rows
     * @param {number} totTaxable
     * @param {number} totTax
     * @param {number} totNet
     * @returns {void}
     */
    function _updateStats(rows, totTaxable, totTax, totNet) {
        document.getElementById('prrStatCount').textContent   = rows.length;
        document.getElementById('prrStatTaxable').textContent = _fmt(totTaxable);
        document.getElementById('prrStatTax').textContent     = _fmt(totTax);
        document.getElementById('prrStatNet').textContent     = _fmt(totNet);
    }

    /** @returns {void} */
    function _fetch() {
        var from = document.getElementById('prrFrom').value;
        var to   = document.getElementById('prrTo').value;

        ajaxLoading(1);
        document.getElementById('prrTableBody').innerHTML =
            '<tr><td colspan="11" class="rpt-loading-cell">' +
            '<div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div>' +
            '</td></tr>';
        document.getElementById('prrTableFoot').classList.add('d-none');
        document.getElementById('prrTblFooter').classList.add('d-none');

        $.get('/reports/getPurchaseReturnRegisterData', { from: from, to: to })
            .done(function (res) {
                if (res.Status !== 'Success') {
                    document.getElementById('prrTableBody').innerHTML =
                        '<tr><td colspan="11" class="text-center text-danger py-4">' + _esc(res.Message || 'Error loading data') + '</td></tr>';
                    return;
                }
                _rows = res.rows || [];
                _render(_rows);
            })
            .fail(function () {
                document.getElementById('prrTableBody').innerHTML =
                    '<tr><td colspan="11" class="text-center text-danger py-4">Request failed. Please try again.</td></tr>';
            })
            .always(function () { ajaxLoading(0); });
    }

    /** @returns {void} */
    function _initDatePickers() {
        var initFrom = (typeof _prrInitFrom !== 'undefined') ? _prrInitFrom : '';
        var initTo   = (typeof _prrInitTo   !== 'undefined') ? _prrInitTo   : '';

        flatpickr('#prrFromDisplay', {
            static: true,
            position: 'below left',
            dateFormat: 'Y-m-d',
            defaultDate: initFrom || undefined,
            onChange: function (sel, dateStr) {
                document.getElementById('prrFrom').value = dateStr;
            }
        });
        if (initFrom) {
            document.getElementById('prrFromDisplay').value = _fmtDate(initFrom);
        }

        flatpickr('#prrToDisplay', {
            static: true,
            position: 'below left',
            dateFormat: 'Y-m-d',
            defaultDate: initTo || undefined,
            onChange: function (sel, dateStr) {
                document.getElementById('prrTo').value = dateStr;
            }
        });
        if (initTo) {
            document.getElementById('prrToDisplay').value = _fmtDate(initTo);
        }
    }

    /** @returns {void} */
    function _init() {
        _initDatePickers();
        document.getElementById('prrApplyBtn').addEventListener('click', _fetch);
        _fetch();
    }

    $(document).ready(_init);
})();
