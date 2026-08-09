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
     * @param {number|string} n
     * @returns {string}
     */
    function _num(n) { return (parseFloat(n) || 0).toFixed(_dec); }

    /**
     * @param {string} iso
     * @returns {string}
     */
    function _fmtQty(n) {
        var v = parseFloat(n) || 0;
        return v.toLocaleString('en-IN', { maximumFractionDigits: _dec });
    }

    /** @type {Array<Object>} */
    var _rows = [];

    /**
     * @param {Array<Object>} rows
     * @returns {void}
     */
    function _render(rows) {
        var tbody = document.getElementById('hsnTableBody');
        var tfoot = document.getElementById('hsnTableFoot');

        if (!rows || rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="11">' +
                '<div class="rpt-empty"><i class="bx bx-barcode"></i>' +
                '<div class="rpt-empty-title">No HSN data found</div>' +
                '<div>No sales with product HSN codes for selected date range</div></div></td></tr>';
            tfoot.classList.add('d-none');
            _updateStats([], 0, 0, 0);
            return;
        }

        var totQty = 0, totTaxable = 0, totCgst = 0, totSgst = 0, totIgst = 0, totTax = 0;
        var html = '';

        rows.forEach(function (r, i) {
            var qty     = parseFloat(r.TotalQty)   || 0;
            var taxable = parseFloat(r.TaxableValue) || 0;
            var cgst    = parseFloat(r.CgstAmount)  || 0;
            var sgst    = parseFloat(r.SgstAmount)  || 0;
            var igst    = parseFloat(r.IgstAmount)  || 0;
            var tax     = parseFloat(r.TaxAmount)   || 0;
            totQty += qty; totTaxable += taxable;
            totCgst += cgst; totSgst += sgst; totIgst += igst; totTax += tax;

            html += '<tr>' +
                '<td>' + (i + 1) + '</td>' +
                '<td><span class="hsn-code-badge">' + _esc(r.HsnCode) + '</span></td>' +
                '<td>' + _esc(r.UOM) + '</td>' +
                '<td class="rpt-col-num">' + _num(r.TaxRate) + '%</td>' +
                '<td class="rpt-col-num">' + _fmtQty(qty) + '</td>' +
                '<td class="rpt-col-num">' + _num(taxable) + '</td>' +
                '<td class="rpt-col-num">' + _num(cgst) + '</td>' +
                '<td class="rpt-col-num">' + _num(sgst) + '</td>' +
                '<td class="rpt-col-num">' + _num(igst) + '</td>' +
                '<td class="rpt-col-num rpt-num-teal">' + _num(tax) + '</td>' +
                '<td class="rpt-col-num">' + (parseInt(r.InvCount, 10) || 0) + '</td>' +
                '</tr>';
        });
        tbody.innerHTML = html;

        document.getElementById('hsnFtTaxable').textContent = _num(totTaxable);
        document.getElementById('hsnFtCgst').textContent    = _num(totCgst);
        document.getElementById('hsnFtSgst').textContent    = _num(totSgst);
        document.getElementById('hsnFtIgst').textContent    = _num(totIgst);
        document.getElementById('hsnFtTax').textContent     = _num(totTax);
        tfoot.classList.remove('d-none');
        _updateStats(rows, totQty, totTaxable, totTax);
    }

    /**
     * @param {Array<Object>} rows
     * @param {number}        totQty
     * @param {number}        totTaxable
     * @param {number}        totTax
     * @returns {void}
     */
    function _updateStats(rows, totQty, totTaxable, totTax) {
        document.getElementById('hsnStatCodes').textContent   = rows.length;
        document.getElementById('hsnStatQty').textContent     = _fmtQty(totQty || 0);
        document.getElementById('hsnStatTaxable').textContent = _fmt(totTaxable || 0);
        document.getElementById('hsnStatTax').textContent     = _fmt(totTax || 0);
    }

    /** @returns {void} */
    function _fetch() {
        var from = document.getElementById('hsnFrom').value;
        var to   = document.getElementById('hsnTo').value;

        ajaxLoading(1);
        document.getElementById('hsnTableBody').innerHTML =
            '<tr><td colspan="11" class="rpt-loading-cell">' +
            '<div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div>' +
            '</td></tr>';
        document.getElementById('hsnTableFoot').classList.add('d-none');

        $.get('/reports/getHsnData', { from: from, to: to })
            .done(function (res) {
                if (res.Status !== 'Success') {
                    document.getElementById('hsnTableBody').innerHTML =
                        '<tr><td colspan="11" class="text-center text-danger py-4">' + _esc(res.Message || 'Error loading data') + '</td></tr>';
                    return;
                }
                _rows = res.rows || [];
                _render(_rows);
            })
            .fail(function () {
                document.getElementById('hsnTableBody').innerHTML =
                    '<tr><td colspan="11" class="text-center text-danger py-4">Request failed. Please try again.</td></tr>';
            })
            .always(function () { ajaxLoading(0); });
    }

    /** @returns {void} */
    function _initDatePickers() {
        var initFrom = (typeof _hsnInitFrom !== 'undefined') ? _hsnInitFrom : '';
        var initTo   = (typeof _hsnInitTo   !== 'undefined') ? _hsnInitTo   : '';

        flatpickr('#hsnFromDisplay', {
            static: true,
            position: 'below left',
            dateFormat: 'Y-m-d',
            defaultDate: initFrom || undefined,
            onChange: function (sel, dateStr) { document.getElementById('hsnFrom').value = dateStr; }
        });
        if (initFrom) document.getElementById('hsnFromDisplay').value =
            new Date(initFrom).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });

        flatpickr('#hsnToDisplay', {
            static: true,
            position: 'below left',
            dateFormat: 'Y-m-d',
            defaultDate: initTo || undefined,
            onChange: function (sel, dateStr) { document.getElementById('hsnTo').value = dateStr; }
        });
        if (initTo) document.getElementById('hsnToDisplay').value =
            new Date(initTo).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    /** @returns {void} */
    function _init() {
        _initDatePickers();
        document.getElementById('hsnApplyBtn').addEventListener('click', _fetch);
        _fetch();
    }

    $(document).ready(_init);
})();
