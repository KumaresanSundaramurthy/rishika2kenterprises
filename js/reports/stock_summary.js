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
     * @param {number} available
     * @param {number} threshold
     * @returns {string}
     */
    function _alertBadge(available, threshold) {
        if (threshold > 0 && available <= 0) {
            return ' <span class="ss-out-badge"><i class="bx bx-x-circle"></i>Out</span>';
        }
        if (threshold > 0 && available <= threshold) {
            return ' <span class="ss-low-badge"><i class="bx bx-error-circle"></i>Low</span>';
        }
        return '';
    }

    /**
     * @param {Array} rows
     * @returns {void}
     */
    function _render(rows) {
        var tbody  = document.getElementById('ssTableBody');
        var tfoot  = document.getElementById('ssTableFoot');
        var footer = document.getElementById('ssTblFooter');

        if (!rows || rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9"><div class="rpt-empty"><i class="bx bx-package"></i><div class="rpt-empty-title">No products found</div><div style="font-size:.82rem">No active products in the system</div></div></td></tr>';
            tfoot.classList.add('d-none');
            footer.classList.add('d-none');
            _updateStats(0, 0, 0, 0);
            return;
        }

        var html = '';
        var totQty = 0, totValue = 0, alerts = 0;

        rows.forEach(function (r, i) {
            var avail     = parseFloat(r.AvailableQty   || 0);
            var threshold = parseFloat(r.LowStockAlertAt || 0);
            var val       = parseFloat(r.StockValue     || 0);
            var purPrice  = parseFloat(r.PurchasePrice  || 0);
            var selPrice  = parseFloat(r.SellingPrice   || 0);

            totQty   += avail;
            totValue += val;
            if (threshold > 0 && avail <= threshold) { alerts++; }

            var rowCls = (threshold > 0 && avail <= 0) ? 'ss-out-row' : (threshold > 0 && avail <= threshold ? 'ss-low-row' : '');

            html += '<tr class="' + rowCls + '">';
            html += '<td>' + (i + 1) + '</td>';
            html += '<td>' + _esc(r.ItemName) + _alertBadge(avail, threshold) + '</td>';
            html += '<td>' + (r.SKU ? _esc(r.SKU) : '<span style="color:#94a3b8">—</span>') + '</td>';
            html += '<td>' + (r.CategoryName ? '<span class="ss-cat-chip">' + _esc(r.CategoryName) + '</span>' : '<span style="color:#94a3b8">—</span>') + '</td>';
            html += '<td>' + (r.UnitName ? _esc(r.UnitName) : '<span style="color:#94a3b8">—</span>') + '</td>';
            html += '<td class="rpt-col-num ' + (avail <= 0 ? 'rpt-num-red' : (threshold > 0 && avail <= threshold ? 'rpt-num-orange' : 'rpt-num-blue')) + '">' + parseFloat(avail).toFixed(2) + '</td>';
            html += '<td class="rpt-col-num">' + _fmt(purPrice) + '</td>';
            html += '<td class="rpt-col-num">' + _fmt(selPrice) + '</td>';
            html += '<td class="rpt-col-num rpt-num-green">' + _fmt(val) + '</td>';
            html += '</tr>';
        });

        tbody.innerHTML = html;

        document.getElementById('ssFtQty').textContent   = parseFloat(totQty).toFixed(2);
        document.getElementById('ssFtValue').textContent = _fmt(totValue);
        tfoot.classList.remove('d-none');

        document.getElementById('ssRowCount').textContent     = rows.length + ' product' + (rows.length !== 1 ? 's' : '');
        document.getElementById('ssFooterValue').textContent  = _fmt(totValue);
        document.getElementById('ssFooterAlerts').textContent = alerts;
        footer.classList.remove('d-none');

        _updateStats(rows.length, totQty, totValue, alerts);
    }

    /**
     * @param {number} total
     * @param {number} qty
     * @param {number} value
     * @param {number} alerts
     * @returns {void}
     */
    function _updateStats(total, qty, value, alerts) {
        document.getElementById('ssStatTotal').textContent = total;
        document.getElementById('ssStatQty').textContent   = parseFloat(qty).toFixed(2);
        document.getElementById('ssStatValue').textContent = _fmt(value);
        document.getElementById('ssStatAlert').textContent = alerts > 0 ? alerts + ' item' + (alerts !== 1 ? 's' : '') : '—';
    }

    /**
     * @returns {void}
     */
    function _fetch() {
        var tbody = document.getElementById('ssTableBody');
        tbody.innerHTML = '<tr><td colspan="9" class="rpt-loading-cell"><div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div></td></tr>';
        document.getElementById('ssTableFoot').classList.add('d-none');
        document.getElementById('ssTblFooter').classList.add('d-none');

        $.ajax({
            url: '/reports/getStockSummaryData',
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

    /**
     * @returns {void}
     */
    function _init() {
        _fetch();
    }

    $(document).ready(_init);
}());
