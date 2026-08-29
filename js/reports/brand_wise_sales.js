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
     * @param {number} n
     * @returns {string}
     */
    function _fmtQty(n) {
        return parseFloat(n || 0).toFixed(_dec);
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
        var tbody  = document.getElementById('bwsTableBody');
        var tfoot  = document.getElementById('bwsTableFoot');
        var footer = document.getElementById('bwsTblFooter');

        if (!rows || rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7"><div class="rpt-empty"><i class="bx bx-purchase-tag-alt"></i><div class="rpt-empty-title">No brand sales found</div><div style="font-size:.82rem">Try a different date range</div></div></td></tr>';
            tfoot.classList.add('d-none');
            footer.classList.add('d-none');
            _updateStats(0, 0, 0, 0);
            return;
        }

        var html = '';
        var totInvoices = 0, totQty = 0, totValue = 0;

        rows.forEach(function (r, i) {
            var products = parseInt(r.ProductCount  || 0, 10);
            var inv      = parseInt(r.InvoiceCount  || 0, 10);
            var qty      = parseFloat(r.TotalQty    || 0);
            var val      = parseFloat(r.TotalValue   || 0);
            var avg      = parseFloat(r.AvgPrice     || 0);
            totInvoices += inv;
            totQty      += qty;
            totValue    += val;

            html += '<tr>';
            html += '<td>' + (i + 1) + '</td>';
            html += '<td><strong>' + _esc(r.BrandName) + '</strong></td>';
            html += '<td class="rpt-col-num">' + products + '</td>';
            html += '<td class="rpt-col-num">' + inv + '</td>';
            html += '<td class="rpt-col-num rpt-num-teal">' + _fmtQty(qty) + '</td>';
            html += '<td class="rpt-col-num">' + _fmt(avg) + '</td>';
            html += '<td class="rpt-col-num rpt-num-green">' + _fmt(val) + '</td>';
            html += '</tr>';
        });

        tbody.innerHTML = html;

        document.getElementById('bwsFtInvoices').textContent = totInvoices;
        document.getElementById('bwsFtQty').textContent      = _fmtQty(totQty);
        document.getElementById('bwsFtValue').textContent    = _fmt(totValue);
        tfoot.classList.remove('d-none');

        document.getElementById('bwsRowCount').textContent    = rows.length + ' brand' + (rows.length !== 1 ? 's' : '');
        document.getElementById('bwsFooterValue').textContent = _fmt(totValue);
        footer.classList.remove('d-none');

        _updateStats(rows.length, totInvoices, totQty, totValue);
    }

    /**
     * @param {number} brands
     * @param {number} invoices
     * @param {number} qty
     * @param {number} value
     * @returns {void}
     */
    function _updateStats(brands, invoices, qty, value) {
        document.getElementById('bwsStatBrands').textContent   = brands;
        document.getElementById('bwsStatInvoices').textContent = invoices;
        document.getElementById('bwsStatQty').textContent      = _fmtQty(qty);
        document.getElementById('bwsStatValue').textContent    = _fmt(value);
    }

    /**
     * @returns {void}
     */
    function _fetch() {
        var from  = document.getElementById('bwsFrom').value;
        var to    = document.getElementById('bwsTo').value;
        var tbody = document.getElementById('bwsTableBody');

        tbody.innerHTML = '<tr><td colspan="7" class="rpt-loading-cell"><div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div></td></tr>';
        document.getElementById('bwsTableFoot').classList.add('d-none');
        document.getElementById('bwsTblFooter').classList.add('d-none');

        $.ajax({
            url: '/reports/getBrandWiseSalesData',
            type: 'GET',
            dataType: 'json',
            data: { from: from, to: to },
            success: function (res) {
                if (res && res.Status === 'Success') {
                    _render(res.rows || []);
                } else {
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center p-4 text-danger">' + _esc((res && res.Message) ? res.Message : 'Failed to load data') + '</td></tr>';
                }
            },
            error: function () {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center p-4 text-danger">Network error. Please try again.</td></tr>';
            }
        });
    }

    /**
     * @returns {void}
     */
    function _initDatePickers() {
        var fromInput   = document.getElementById('bwsFrom');
        var toInput     = document.getElementById('bwsTo');
        var fromDisplay = document.getElementById('bwsFromDisplay');
        var toDisplay   = document.getElementById('bwsToDisplay');

        flatpickr(fromDisplay, {
            dateFormat: 'Y-m-d',
            defaultDate: fromInput.value || _bwsInitFrom,
            onChange: function (sel, str) {
                fromInput.value = str;
                fromDisplay.value = str ? new Date(str + 'T00:00:00').toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '';
            }
        });

        flatpickr(toDisplay, {
            dateFormat: 'Y-m-d',
            defaultDate: toInput.value || _bwsInitTo,
            onChange: function (sel, str) {
                toInput.value = str;
                toDisplay.value = str ? new Date(str + 'T00:00:00').toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '';
            }
        });

        if (fromInput.value) {
            fromDisplay.value = new Date(fromInput.value + 'T00:00:00').toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        }
        if (toInput.value) {
            toDisplay.value = new Date(toInput.value + 'T00:00:00').toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        }
    }

    /**
     * @returns {void}
     */
    function _init() {
        _initDatePickers();
        document.getElementById('bwsApplyBtn').addEventListener('click', function () {
            _fetch();
        });
        _fetch();
    }

    $(document).ready(_init);
}());
