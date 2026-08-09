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
    function _fmtDate(iso) {
        if (!iso) return '—';
        var d = new Date(iso);
        if (isNaN(d.getTime())) return iso;
        return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    /**
     * @param {string} gstin
     * @returns {string}
     */
    function _gstinBadge(gstin) {
        if (!gstin) return '<span class="g1-unregd-badge">No GSTIN</span>';
        return '<span class="g1-gstin-badge">' + _esc(gstin) + '</span>';
    }

    /** @type {Array<Object>} */
    var _rows = [];

    /**
     * @param {Array<Object>} rows
     * @returns {void}
     */
    function _render(rows) {
        var tbody  = document.getElementById('g2TableBody');
        var tfoot  = document.getElementById('g2TableFoot');

        if (!rows || rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="12">' +
                '<div class="rpt-empty"><i class="bx bx-store"></i>' +
                '<div class="rpt-empty-title">No inward supplies found</div>' +
                '<div>No purchase bills from GSTIN-registered suppliers for this month</div></div></td></tr>';
            tfoot.classList.add('d-none');
            _updateStats([], 0, 0);
            return;
        }

        var totTaxable = 0, totCgst = 0, totSgst = 0, totIgst = 0, totTax = 0, totNet = 0;
        var uniqueSuppliers = {};
        var html = '';

        rows.forEach(function (r, i) {
            var taxable = parseFloat(r.Taxable) || 0;
            var cgst    = parseFloat(r.CgstAmount) || 0;
            var sgst    = parseFloat(r.SgstAmount) || 0;
            var igst    = parseFloat(r.IgstAmount) || 0;
            var tax     = parseFloat(r.TaxAmount)  || 0;
            var net     = parseFloat(r.NetAmount)  || 0;
            totTaxable += taxable; totCgst += cgst; totSgst += sgst;
            totIgst += igst; totTax += tax; totNet += net;
            if (r.SupplierGSTIN) uniqueSuppliers[r.SupplierGSTIN] = true;

            html += '<tr>' +
                '<td>' + (i + 1) + '</td>' +
                '<td>' + _esc(r.BillNo) + '</td>' +
                '<td>' + _fmtDate(r.TransDate) + '</td>' +
                '<td>' + _gstinBadge(r.SupplierGSTIN) + '</td>' +
                '<td>' + _esc(r.SupplierName) + '</td>' +
                '<td>' + _esc(r.PlaceOfSupply || '—') + '</td>' +
                '<td class="rpt-col-num">' + _num(taxable) + '</td>' +
                '<td class="rpt-col-num">' + _num(cgst) + '</td>' +
                '<td class="rpt-col-num">' + _num(sgst) + '</td>' +
                '<td class="rpt-col-num">' + _num(igst) + '</td>' +
                '<td class="rpt-col-num rpt-num-green">' + _num(tax) + '</td>' +
                '<td class="rpt-col-num rpt-num-blue">' + _num(net) + '</td>' +
                '</tr>';
        });
        tbody.innerHTML = html;

        document.getElementById('g2FtTaxable').textContent = _num(totTaxable);
        document.getElementById('g2FtCgst').textContent    = _num(totCgst);
        document.getElementById('g2FtSgst').textContent    = _num(totSgst);
        document.getElementById('g2FtIgst').textContent    = _num(totIgst);
        document.getElementById('g2FtTax').textContent     = _num(totTax);
        document.getElementById('g2FtNet').textContent     = _num(totNet);
        tfoot.classList.remove('d-none');
        _updateStats(rows, Object.keys(uniqueSuppliers).length, totTaxable, totTax);
    }

    /**
     * @param {Array<Object>} rows
     * @param {number}        supplierCount
     * @param {number}        totTaxable
     * @param {number}        totTax
     * @returns {void}
     */
    function _updateStats(rows, supplierCount, totTaxable, totTax) {
        document.getElementById('g2StatCount').textContent     = rows.length;
        document.getElementById('g2StatSuppliers').textContent = supplierCount || 0;
        document.getElementById('g2StatTaxable').textContent   = _fmt(totTaxable || 0);
        document.getElementById('g2StatItc').textContent       = _fmt(totTax || 0);
    }

    /** @returns {void} */
    function _fetch() {
        var month = document.getElementById('g2Month').value;
        var year  = document.getElementById('g2Year').value;

        ajaxLoading(1);
        document.getElementById('g2TableBody').innerHTML =
            '<tr><td colspan="12" class="rpt-loading-cell">' +
            '<div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div>' +
            '</td></tr>';
        document.getElementById('g2TableFoot').classList.add('d-none');

        $.get('/reports/getGstr2bData', { month: month, year: year })
            .done(function (res) {
                if (res.Status !== 'Success') {
                    document.getElementById('g2TableBody').innerHTML =
                        '<tr><td colspan="12" class="text-center text-danger py-4">' + _esc(res.Message || 'Error loading data') + '</td></tr>';
                    return;
                }
                _rows = res.rows || [];
                _render(_rows);
            })
            .fail(function () {
                document.getElementById('g2TableBody').innerHTML =
                    '<tr><td colspan="12" class="text-center text-danger py-4">Request failed. Please try again.</td></tr>';
            })
            .always(function () { ajaxLoading(0); });
    }

    /** @returns {void} */
    function _init() {
        document.getElementById('g2ApplyBtn').addEventListener('click', _fetch);
        _fetch();
    }

    $(document).ready(_init);
})();
