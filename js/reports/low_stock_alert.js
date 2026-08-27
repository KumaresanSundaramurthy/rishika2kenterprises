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
     * Returns severity badge + bar HTML for a product.
     *
     * @param {number} available
     * @param {number} threshold
     * @returns {string}
     */
    function _severityBadge(available, threshold) {
        if (available <= 0) {
            return '<span class="ls-severity-badge ls-sev-out"><i class="bx bx-x-circle"></i>Out of Stock</span>';
        }
        if (available <= threshold * 0.5) {
            return '<span class="ls-severity-badge ls-sev-critical"><i class="bx bx-alarm-exclamation"></i>Critical</span>';
        }
        return '<span class="ls-severity-badge ls-sev-low"><i class="bx bx-time"></i>Low</span>';
    }

    /**
     * @param {number} available
     * @param {number} threshold
     * @returns {string}
     */
    function _stockBar(available, threshold) {
        var pct = threshold > 0 ? Math.min(100, Math.round((available / threshold) * 100)) : 0;
        var fillCls = available <= 0 ? 'ls-bar-zero' : (available <= threshold * 0.5 ? 'ls-bar-low' : 'ls-bar-ok');
        return '<div class="ls-bar-wrap"><div class="ls-bar-bg"><div class="ls-bar-fill ' + fillCls + '" style="width:' + pct + '%"></div></div><span style="font-size:.72rem;color:#64748b;white-space:nowrap">' + pct + '%</span></div>';
    }

    /**
     * @param {Array} rows
     * @returns {void}
     */
    function _render(rows) {
        var tbody  = document.getElementById('lsTableBody');
        var footer = document.getElementById('lsTblFooter');

        if (!rows || rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9"><div class="rpt-empty"><i class="bx bx-check-circle" style="color:#16a34a"></i><div class="rpt-empty-title">All products are well stocked</div><div style="font-size:.82rem">No items below their minimum threshold</div></div></td></tr>';
            footer.classList.add('d-none');
            _updateStats(0, 0, 0, 0);
            return;
        }

        var html = '';
        var totOut = 0, totCritical = 0, totLow = 0;

        rows.forEach(function (r, i) {
            var avail     = parseFloat(r.AvailableQty   || 0);
            var threshold = parseFloat(r.LowStockAlertAt || 0);
            var shortfall = parseFloat(r.ShortfallQty   || 0);

            if (avail <= 0) { totOut++; }
            else if (avail <= threshold * 0.5) { totCritical++; }
            else { totLow++; }

            html += '<tr>';
            html += '<td>' + (i + 1) + '</td>';
            html += '<td>' + _esc(r.ItemName) + '</td>';
            html += '<td>' + (r.SKU ? _esc(r.SKU) : '<span style="color:#94a3b8">—</span>') + '</td>';
            html += '<td>' + (r.CategoryName ? _esc(r.CategoryName) : '<span style="color:#94a3b8">—</span>') + '</td>';
            html += '<td>' + _severityBadge(avail, threshold) + '</td>';
            html += '<td class="rpt-col-num ' + (avail <= 0 ? 'rpt-num-red' : 'rpt-num-orange') + '">' + parseFloat(avail).toFixed(2) + (r.UnitName ? ' ' + _esc(r.UnitName) : '') + '</td>';
            html += '<td class="rpt-col-num">' + parseFloat(threshold).toFixed(2) + (r.UnitName ? ' ' + _esc(r.UnitName) : '') + '</td>';
            html += '<td class="rpt-col-num rpt-num-red">' + parseFloat(shortfall > 0 ? shortfall : 0).toFixed(2) + '</td>';
            html += '<td>' + _stockBar(avail, threshold) + '</td>';
            html += '</tr>';
        });

        tbody.innerHTML = html;

        document.getElementById('lsRowCount').textContent    = rows.length + ' product' + (rows.length !== 1 ? 's' : '');
        document.getElementById('lsFooterTotal').textContent = rows.length;
        footer.classList.remove('d-none');

        _updateStats(rows.length, totOut, totCritical, totLow);
    }

    /**
     * @param {number} total
     * @param {number} out
     * @param {number} critical
     * @param {number} low
     * @returns {void}
     */
    function _updateStats(total, out, critical, low) {
        document.getElementById('lsStatTotal').textContent    = total;
        document.getElementById('lsStatOut').textContent      = out;
        document.getElementById('lsStatCritical').textContent = critical;
        document.getElementById('lsStatLow').textContent      = low;
    }

    /**
     * @returns {void}
     */
    function _fetch() {
        var tbody = document.getElementById('lsTableBody');
        tbody.innerHTML = '<tr><td colspan="9" class="rpt-loading-cell"><div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div></td></tr>';
        document.getElementById('lsTblFooter').classList.add('d-none');

        $.ajax({
            url: '/reports/getLowStockAlertData',
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
