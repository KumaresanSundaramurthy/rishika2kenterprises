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
        var $tbody  = $('#vsTableBody');
        var $tfoot  = $('#vsTableFoot');
        var $footer = $('#vsTblFooter');

        if (!rows || rows.length === 0) {
            $tbody.html('<tr><td colspan="9"><div class="rpt-empty"><i class="bx bx-git-branch"></i><div class="rpt-empty-title">No variants found</div><div style="font-size:var(--r2k-text-82)">Add brand &amp; size variants to products first</div></div></td></tr>');
            $tfoot.addClass('d-none');
            $footer.addClass('d-none');
            _updateStats(0, 0, 0, 0);
            return;
        }

        var html = '';
        var totQty = 0, totValue = 0, inStock = 0, outOfStock = 0;

        rows.forEach(function (r, i) {
            var qty  = parseFloat(r.StockQty    || 0);
            var val  = parseFloat(r.StockValue   || 0);
            var pp   = parseFloat(r.PurchasePrice || 0);
            var sp   = parseFloat(r.SellingPrice  || 0);
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

        $tbody.html(html);
        $('#vsFtQty').text(_fmtQty(totQty));
        $('#vsFtValue').text(_fmt(totValue));
        $tfoot.removeClass('d-none');
        $('#vsRowCount').text(rows.length + ' variant' + (rows.length !== 1 ? 's' : ''));
        $('#vsFooterValue').text(_fmt(totValue));
        $footer.removeClass('d-none');
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
        $('#vsStatVariants').text(total);
        $('#vsStatInStock').text(inStock);
        $('#vsStatOutOfStock').text(outOfStock);
        $('#vsStatValue').text(_fmt(value));
    }

    /**
     * @returns {void}
     */
    function _fetch() {
        var $tbody = $('#vsTableBody');
        $tbody.html('<tr><td colspan="9" class="rpt-loading-cell"><div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div></td></tr>');
        $('#vsTableFoot').addClass('d-none');
        $('#vsTblFooter').addClass('d-none');

        ajaxLoading(1);
        $.ajax({
            url     : global_services_url + 'api/reports/getVariantStockData',
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
                url         : global_services_url + 'api/reports/exportVariantStock',
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
                url         : global_services_url + 'api/reports/exportVariantStock',
                method      : 'POST',
                contentType : 'application/json',
                headers     : { Authorization: 'Bearer ' + JwtToken },
                data        : JSON.stringify(payload),
                xhrFields   : { responseType: 'blob' },
                success : function (blob) {
                    var url = URL.createObjectURL(blob);
                    var $a  = $('<a>').attr({ href: url, download: 'variant_stock.' + ext }).appendTo('body');
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
        $('#vsRefreshBtn').on('click', _fetch);
        $(document).on('click', '.r2k-export-btn[data-format]', function () {
            _handleExport($(this).data('format'));
        });
        _fetch();
    }

    $(document).ready(_init);
}());
