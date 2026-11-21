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
        var $tbody  = $('#ssTableBody');
        var $tfoot  = $('#ssTableFoot');
        var $footer = $('#ssTblFooter');

        if (!rows || rows.length === 0) {
            $tbody.html('<tr><td colspan="9"><div class="rpt-empty"><i class="bx bx-package"></i><div class="rpt-empty-title">No products found</div><div style="font-size:var(--r2k-text-82)">No active products in the system</div></div></td></tr>');
            $tfoot.addClass('d-none');
            $footer.addClass('d-none');
            _updateStats(0, 0, 0, 0);
            return;
        }

        var html = '';
        var totQty = 0, totValue = 0, alerts = 0;

        rows.forEach(function (r, i) {
            var avail     = parseFloat(r.AvailableQty    || 0);
            var threshold = parseFloat(r.LowStockAlertAt || 0);
            var val       = parseFloat(r.StockValue      || 0);
            var purPrice  = parseFloat(r.PurchasePrice   || 0);
            var selPrice  = parseFloat(r.SellingPrice    || 0);

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

        $tbody.html(html);
        $('#ssFtQty').text(parseFloat(totQty).toFixed(2));
        $('#ssFtValue').text(_fmt(totValue));
        $tfoot.removeClass('d-none');
        $('#ssRowCount').text(rows.length + ' product' + (rows.length !== 1 ? 's' : ''));
        $('#ssFooterValue').text(_fmt(totValue));
        $('#ssFooterAlerts').text(alerts);
        $footer.removeClass('d-none');
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
        $('#ssStatTotal').text(total);
        $('#ssStatQty').text(parseFloat(qty).toFixed(2));
        $('#ssStatValue').text(_fmt(value));
        $('#ssStatAlert').text(alerts > 0 ? alerts + ' item' + (alerts !== 1 ? 's' : '') : '—');
    }

    /**
     * @returns {void}
     */
    function _fetch() {
        var $tbody = $('#ssTableBody');
        $tbody.html('<tr><td colspan="9" class="rpt-loading-cell"><div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div></td></tr>');
        $('#ssTableFoot').addClass('d-none');
        $('#ssTblFooter').addClass('d-none');

        ajaxLoading(1);
        $.ajax({
            url     : global_services_url + 'api/reports/getStockSummaryData',
            data    : { orgId: parseInt(JwtData.Org.OrgUID, 10) },
            type    : 'GET',
            dataType: 'json',
            headers : { Authorization: 'Bearer ' + JwtToken },
            success : function (res) {
                if (!res || res.Error) {
                    $tbody.html('<tr><td colspan="9" class="text-center p-4 text-danger">' + _esc((res && res.Message) ? res.Message : 'Failed to load data') + '</td></tr>');
                    return;
                }
                _render(res.rows || []);
            },
            error   : function () {
                $tbody.html('<tr><td colspan="9" class="text-center p-4 text-danger">Network error. Please try again.</td></tr>');
            }
        }).always(function () { ajaxLoading(0); });
    }

    /**
     * @param {string} format
     * @returns {void}
     */
    function _handleExport(format) {
        var type    = format === 'pdf' ? 'Pdf' : (format === 'print' ? 'Print' : (format === 'excel' ? 'Excel' : 'CSV'));
        var payload = { orgId: parseInt(JwtData.Org.OrgUID, 10), Type: type };

        if (type === 'Print') {
            $.ajax({
                url         : global_services_url + 'api/reports/exportStockSummary',
                method      : 'POST',
                contentType : 'application/json',
                headers     : { Authorization: 'Bearer ' + JwtToken },
                data        : JSON.stringify(payload),
                dataType    : 'json',
                success : function (resp) {
                    if (!resp || resp.Error || !resp.HtmlData) { showToastNotification('Export failed. Please try again.', 'error'); return; }
                    var win = window.open('', '_blank');
                    win.document.open();
                    win.document.write(resp.HtmlData);
                    win.document.close();
                },
                error : function () { showToastNotification('Export failed. Please try again.', 'error'); }
            });
        } else {
            var ext = type === 'Excel' ? 'xlsx' : (type === 'Pdf' ? 'pdf' : 'csv');
            $.ajax({
                url         : global_services_url + 'api/reports/exportStockSummary',
                method      : 'POST',
                contentType : 'application/json',
                headers     : { Authorization: 'Bearer ' + JwtToken },
                data        : JSON.stringify(payload),
                xhrFields   : { responseType: 'blob' },
                success : function (blob) {
                    var url = URL.createObjectURL(blob);
                    var $a  = $('<a>').attr({ href: url, download: 'stock_summary.' + ext }).appendTo('body');
                    $a[0].click();
                    setTimeout(function () { URL.revokeObjectURL(url); $a.remove(); }, 3000);
                },
                error : function () { showToastNotification('Export failed. Please try again.', 'error'); }
            });
        }
    }

    /**
     * @returns {void}
     */
    function _init() {
        $('#ssRefreshBtn').on('click', _fetch);
        $(document).on('click', '.r2k-export-btn[data-format]', function () {
            _handleExport($(this).data('format'));
        });
        _fetch();
    }

    $(document).ready(_init);
}());
