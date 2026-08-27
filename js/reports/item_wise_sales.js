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
     * @param {number} dec
     * @returns {string}
     */
    function _fmtQty(n, dec) {
        return parseFloat(n || 0).toFixed(dec || 2);
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
        var tbody  = document.getElementById('iwsTableBody');
        var tfoot  = document.getElementById('iwsTableFoot');
        var footer = document.getElementById('iwsTblFooter');

        if (!rows || rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8"><div class="rpt-empty"><i class="bx bx-info-circle"></i><div class="rpt-empty-title">No sales found</div><div style="font-size:.82rem">Try a different date range</div></div></td></tr>';
            tfoot.classList.add('d-none');
            footer.classList.add('d-none');
            _updateStats(0, 0, 0, 0);
            return;
        }

        var html = '';
        var totInvoices = 0, totQty = 0, totValue = 0;

        rows.forEach(function (r, i) {
            var inv   = parseInt(r.InvoiceCount || 0, 10);
            var qty   = parseFloat(r.TotalQty   || 0);
            var val   = parseFloat(r.TotalValue  || 0);
            var avg   = parseFloat(r.AvgPrice    || 0);
            totInvoices += inv;
            totQty      += qty;
            totValue    += val;

            html += '<tr>';
            html += '<td>' + (i + 1) + '</td>';
            html += '<td>' + _esc(r.ItemName) + (r.UnitName ? ' <span style="font-size:.72rem;color:#94a3b8">/' + _esc(r.UnitName) + '</span>' : '') + '</td>';
            html += '<td>' + (r.SKU ? _esc(r.SKU) : '<span style="color:#94a3b8">—</span>') + '</td>';
            html += '<td>' + (r.CategoryName ? '<span class="iws-cat-chip">' + _esc(r.CategoryName) + '</span>' : '<span style="color:#94a3b8">—</span>') + '</td>';
            html += '<td class="rpt-col-num">' + inv + '</td>';
            html += '<td class="rpt-col-num rpt-num-teal">' + _fmtQty(qty) + '</td>';
            html += '<td class="rpt-col-num">' + _fmt(avg) + '</td>';
            html += '<td class="rpt-col-num rpt-num-green">' + _fmt(val) + '</td>';
            html += '</tr>';
        });

        tbody.innerHTML = html;

        document.getElementById('iwsFtInvoices').textContent = totInvoices;
        document.getElementById('iwsFtQty').textContent      = _fmtQty(totQty);
        document.getElementById('iwsFtValue').textContent    = _fmt(totValue);
        tfoot.classList.remove('d-none');

        document.getElementById('iwsRowCount').textContent     = rows.length + ' product' + (rows.length !== 1 ? 's' : '');
        document.getElementById('iwsFooterValue').textContent  = _fmt(totValue);
        footer.classList.remove('d-none');

        _updateStats(rows.length, totInvoices, totQty, totValue);
    }

    /**
     * @param {number} products
     * @param {number} invoices
     * @param {number} qty
     * @param {number} value
     * @returns {void}
     */
    function _updateStats(products, invoices, qty, value) {
        document.getElementById('iwsStatProducts').textContent = products;
        document.getElementById('iwsStatInvoices').textContent = invoices;
        document.getElementById('iwsStatQty').textContent      = _fmtQty(qty);
        document.getElementById('iwsStatValue').textContent    = _fmt(value);
    }

    /**
     * @returns {void}
     */
    function _fetch() {
        var from  = document.getElementById('iwsFrom').value;
        var to    = document.getElementById('iwsTo').value;
        var tbody = document.getElementById('iwsTableBody');

        tbody.innerHTML = '<tr><td colspan="8" class="rpt-loading-cell"><div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div></td></tr>';
        document.getElementById('iwsTableFoot').classList.add('d-none');
        document.getElementById('iwsTblFooter').classList.add('d-none');

        $.ajax({
            url: '/reports/getItemWiseSalesData',
            type: 'GET',
            dataType: 'json',
            data: { from: from, to: to },
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
    function _initDatePickers() {
        var fromInput   = document.getElementById('iwsFrom');
        var toInput     = document.getElementById('iwsTo');
        var fromDisplay = document.getElementById('iwsFromDisplay');
        var toDisplay   = document.getElementById('iwsToDisplay');

        flatpickr(fromDisplay, {
            dateFormat: 'Y-m-d',
            defaultDate: fromInput.value || _iwsInitFrom,
            onChange: function (sel, str) {
                fromInput.value = str;
                fromDisplay.value = str ? new Date(str + 'T00:00:00').toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '';
            }
        });

        flatpickr(toDisplay, {
            dateFormat: 'Y-m-d',
            defaultDate: toInput.value || _iwsInitTo,
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
        document.getElementById('iwsApplyBtn').addEventListener('click', function () {
            _fetch();
        });
        _fetch();
    }

    $(document).ready(_init);
}());
