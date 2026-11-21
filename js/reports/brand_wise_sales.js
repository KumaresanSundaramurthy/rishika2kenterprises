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
        var $tbody  = $('#bwsTableBody');
        var $tfoot  = $('#bwsTableFoot');
        var $footer = $('#bwsTblFooter');

        if (!rows || rows.length === 0) {
            $tbody.html('<tr><td colspan="7"><div class="rpt-empty"><i class="bx bx-purchase-tag-alt"></i><div class="rpt-empty-title">No brand sales found</div><div style="font-size:var(--r2k-text-82)">Try a different date range</div></div></td></tr>');
            $tfoot.addClass('d-none');
            $footer.addClass('d-none');
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

        $tbody.html(html);
        $('#bwsFtInvoices').text(totInvoices);
        $('#bwsFtQty').text(_fmtQty(totQty));
        $('#bwsFtValue').text(_fmt(totValue));
        $tfoot.removeClass('d-none');
        $('#bwsRowCount').text(rows.length + ' brand' + (rows.length !== 1 ? 's' : ''));
        $('#bwsFooterValue').text(_fmt(totValue));
        $footer.removeClass('d-none');
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
        $('#bwsStatBrands').text(brands);
        $('#bwsStatInvoices').text(invoices);
        $('#bwsStatQty').text(_fmtQty(qty));
        $('#bwsStatValue').text(_fmt(value));
    }

    /**
     * @returns {void}
     */
    function _fetch() {
        var from   = $('#bwsFrom').val();
        var to     = $('#bwsTo').val();
        var $tbody = $('#bwsTableBody');

        $tbody.html('<tr><td colspan="7" class="rpt-loading-cell"><div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div></td></tr>');
        $('#bwsTableFoot').addClass('d-none');
        $('#bwsTblFooter').addClass('d-none');

        ajaxLoading(1);
        $.ajax({
            url     : global_services_url + 'api/reports/getBrandWiseSalesData',
            type    : 'GET',
            dataType: 'json',
            headers : { Authorization: 'Bearer ' + JwtToken },
            data    : { orgId: parseInt(JwtData.Org.OrgUID, 10), from: from, to: to },
            success : function (res) {
                if (!res || res.Error) {
                    $tbody.html('<tr><td colspan="7" class="text-center p-4 text-danger">' + _esc((res && res.Message) ? res.Message : 'Failed to load data') + '</td></tr>');
                    return;
                }
                _render(res.rows || []);
            },
            error   : function () {
                $tbody.html('<tr><td colspan="7" class="text-center p-4 text-danger">Network error. Please try again.</td></tr>');
            }
        }).always(function () { ajaxLoading(0); });
    }

    /**
     * @param {string} format
     * @returns {void}
     */
    function _handleExport(format) {
        var from    = $('#bwsFrom').val() || '';
        var to      = $('#bwsTo').val()   || '';
        var type    = format === 'pdf' ? 'Pdf' : (format === 'print' ? 'Print' : (format === 'excel' ? 'Excel' : 'CSV'));
        var payload = { orgId: parseInt(JwtData.Org.OrgUID, 10), from: from, to: to, Type: type };

        if (type === 'Print') {
            $.ajax({
                url         : global_services_url + 'api/reports/exportBrandWiseSales',
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
                url         : global_services_url + 'api/reports/exportBrandWiseSales',
                method      : 'POST',
                contentType : 'application/json',
                headers     : { Authorization: 'Bearer ' + JwtToken },
                data        : JSON.stringify(payload),
                xhrFields   : { responseType: 'blob' },
                success : function (blob) {
                    var url = URL.createObjectURL(blob);
                    var $a  = $('<a>').attr({ href: url, download: 'brand_wise_sales.' + ext }).appendTo('body');
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
    function _initDatePickers() {
        var $fromHidden  = $('#bwsFrom');
        var $toHidden    = $('#bwsTo');
        var $fromDisplay = $('#bwsFromDisplay');
        var $toDisplay   = $('#bwsToDisplay');

        flatpickr($fromDisplay[0], {
            dateFormat:  'Y-m-d',
            defaultDate: $fromHidden.val() || _bwsInitFrom,
            onChange: function (sel, str) {
                $fromHidden.val(str);
                $fromDisplay.val(str ? new Date(str + 'T00:00:00').toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '');
            }
        });

        flatpickr($toDisplay[0], {
            dateFormat:  'Y-m-d',
            defaultDate: $toHidden.val() || _bwsInitTo,
            onChange: function (sel, str) {
                $toHidden.val(str);
                $toDisplay.val(str ? new Date(str + 'T00:00:00').toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '');
            }
        });

        if ($fromHidden.val()) {
            $fromDisplay.val(new Date($fromHidden.val() + 'T00:00:00').toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }));
        }
        if ($toHidden.val()) {
            $toDisplay.val(new Date($toHidden.val() + 'T00:00:00').toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }));
        }
    }

    /**
     * @returns {void}
     */
    function _init() {
        _initDatePickers();
        $('#bwsApplyBtn').on('click', _fetch);
        $('#bwsRefreshBtn').on('click', _fetch);
        $(document).on('click', '.r2k-export-btn[data-format]', function () {
            _handleExport($(this).data('format'));
        });
        _fetch();
    }

    $(document).ready(_init);
}());
