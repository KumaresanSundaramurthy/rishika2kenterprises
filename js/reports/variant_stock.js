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
        var v = parseFloat(n || 0);
        return isNaN(v) ? '0' : parseFloat(v.toFixed(3)).toString();
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
        var tbody  = document.getElementById('vsTableBody');
        var tfoot  = document.getElementById('vsTableFoot');
        var footer = document.getElementById('vsTblFooter');

        if (!rows || rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9"><div class="rpt-empty"><i class="bx bx-git-branch"></i><div class="rpt-empty-title">No variants found</div><div style="font-size:.82rem">Add brand &amp; size variants to products first</div></div></td></tr>';
            tfoot.classList.add('d-none');
            footer.classList.add('d-none');
            _updateStats(0, 0, 0, 0);
            return;
        }

        var html = '';
        var totQty = 0, totValue = 0, inStock = 0, outOfStock = 0;

        rows.forEach(function (r, i) {
            var qty   = parseFloat(r.StockQty   || 0);
            var val   = parseFloat(r.StockValue  || 0);
            var pp    = parseFloat(r.PurchasePrice || 0);
            var sp    = parseFloat(r.SellingPrice  || 0);
            totQty   += qty;
            totValue += val;
            if (qty > 0) { inStock++; } else { outOfStock++; }

            var qtyClass = qty > 0 ? 'rpt-num-green' : 'rpt-num-red';

            html += '<tr>';
            html += '<td>' + (i + 1) + '</td>';
            html += '<td><span class="vs-brand-chip">' + _esc(r.BrandName) + '</span></td>';
            html += '<td><span class="vs-size-chip">'  + _esc(r.SizeName)  + '</span></td>';
            html += '<td>' + _esc(r.ItemName) + '</td>';
            html += '<td>' + (r.PartNumber ? _esc(r.PartNumber) : '<span style="color:#94a3b8">—</span>') + '</td>';
            html += '<td class="rpt-col-num">' + _fmt(pp) + '</td>';
            html += '<td class="rpt-col-num">' + _fmt(sp) + '</td>';
            html += '<td class="rpt-col-num ' + qtyClass + '">' + _fmtQty(qty) + '</td>';
            html += '<td class="rpt-col-num rpt-num-green">' + _fmt(val) + '</td>';
            html += '</tr>';
        });

        tbody.innerHTML = html;

        document.getElementById('vsFtQty').textContent   = _fmtQty(totQty);
        document.getElementById('vsFtValue').textContent = _fmt(totValue);
        tfoot.classList.remove('d-none');

        document.getElementById('vsRowCount').textContent    = rows.length + ' variant' + (rows.length !== 1 ? 's' : '');
        document.getElementById('vsFooterValue').textContent = _fmt(totValue);
        footer.classList.remove('d-none');

        _updateStats(rows.length, inStock, outOfStock, totValue);
    }

    /**
     * @param {number} total
     * @param {number} inStock
     * @param {number} outOfStock
     * @param {number} value
     * @returns {void}
     */
    function _updateStats(total, inStock, outOfStock, value) {
        document.getElementById('vsStatVariants').textContent   = total;
        document.getElementById('vsStatInStock').textContent    = inStock;
        document.getElementById('vsStatOutOfStock').textContent = outOfStock;
        document.getElementById('vsStatValue').textContent      = _fmt(value);
    }

    /**
     * @returns {void}
     */
    function _fetch() {
        var tbody = document.getElementById('vsTableBody');

        tbody.innerHTML = '<tr><td colspan="9" class="rpt-loading-cell"><div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div></td></tr>';
        document.getElementById('vsTableFoot').classList.add('d-none');
        document.getElementById('vsTblFooter').classList.add('d-none');

        $.ajax({
            url: '/reports/getVariantStockData',
            type: 'GET',
            dataType: 'json',
            success: function (res) {
                if (res && res.Status === 'Success') {
                    _render(res.rows || []);
                } else {
                    tbody.innerHTML = '<tr><td colspan="9" class="text-center p-4 text-danger">' + _esc((res && res.Message) ? res.Message : 'Failed to load data') + '</td></tr>';
                }
            },
            error: function () {
                tbody.innerHTML = '<tr><td colspan="9" class="text-center p-4 text-danger">Network error. Please try again.</td></tr>';
            }
        });
    }

    $(document).ready(function () {
        _fetch();
    });
}());
