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
        return parseFloat(n || 0).toFixed(2);
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
        var $tbody  = $('#iwpTableBody');
        var $tfoot  = $('#iwpTableFoot');
        var $footer = $('#iwpTblFooter');

        if (!rows || rows.length === 0) {
            $tbody.html('<tr><td colspan="8"><div class="rpt-empty"><i class="bx bx-info-circle"></i><div class="rpt-empty-title">No purchases found</div><div style="font-size:var(--r2k-text-82)">Try a different date range</div></div></td></tr>');
            $tfoot.addClass('d-none');
            $footer.addClass('d-none');
            _updateStats(0, 0, 0, 0);
            return;
        }

        var html = '';
        var totBills = 0, totQty = 0, totCost = 0;

        rows.forEach(function (r, i) {
            var bills = parseInt(r.BillCount || 0, 10);
            var qty   = parseFloat(r.TotalQty  || 0);
            var cost  = parseFloat(r.TotalCost || 0);
            var avg   = parseFloat(r.AvgCost   || 0);
            totBills += bills;
            totQty   += qty;
            totCost  += cost;

            html += '<tr>';
            html += '<td>' + (i + 1) + '</td>';
            html += '<td>' + _esc(r.ItemName) + (r.UnitName ? ' <span style="font-size:var(--r2k-text-sm);color:#94a3b8">/' + _esc(r.UnitName) + '</span>' : '') + '</td>';
            html += '<td>' + (r.SKU ? _esc(r.SKU) : '<span style="color:#94a3b8">—</span>') + '</td>';
            html += '<td>' + (r.CategoryName ? '<span class="iwp-cat-chip">' + _esc(r.CategoryName) + '</span>' : '<span style="color:#94a3b8">—</span>') + '</td>';
            html += '<td class="rpt-col-num">' + bills + '</td>';
            html += '<td class="rpt-col-num rpt-num-blue">' + _fmtQty(qty) + '</td>';
            html += '<td class="rpt-col-num">' + _fmt(avg) + '</td>';
            html += '<td class="rpt-col-num rpt-num-orange">' + _fmt(cost) + '</td>';
            html += '</tr>';
        });

        $tbody.html(html);
        $('#iwpFtBills').text(totBills);
        $('#iwpFtQty').text(_fmtQty(totQty));
        $('#iwpFtCost').text(_fmt(totCost));
        $tfoot.removeClass('d-none');
        $('#iwpRowCount').text(rows.length + ' product' + (rows.length !== 1 ? 's' : ''));
        $('#iwpFooterCost').text(_fmt(totCost));
        $footer.removeClass('d-none');
        _updateStats(rows.length, totBills, totQty, totCost);
    }

    /**
     * @param {number} products
     * @param {number} bills
     * @param {number} qty
     * @param {number} cost
     * @returns {void}
     */
    function _updateStats(products, bills, qty, cost) {
        $('#iwpStatProducts').text(products);
        $('#iwpStatBills').text(bills);
        $('#iwpStatQty').text(_fmtQty(qty));
        $('#iwpStatCost').text(_fmt(cost));
    }

    /**
     * @returns {void}
     */
    function _fetch() {
        var from   = $('#iwpFrom').val();
        var to     = $('#iwpTo').val();
        var $tbody = $('#iwpTableBody');

        $tbody.html('<tr><td colspan="8" class="rpt-loading-cell"><div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div></td></tr>');
        $('#iwpTableFoot').addClass('d-none');
        $('#iwpTblFooter').addClass('d-none');

        ajaxLoading(1);
        $.ajax({
            url     : global_services_url + 'api/reports/getItemWisePurchaseData',
            type    : 'GET',
            dataType: 'json',
            headers : { Authorization: 'Bearer ' + JwtToken },
            data    : { orgId: parseInt(JwtData.Org.OrgUID, 10), from: from, to: to },
            success : function (res) {
                if (!res || res.Error) {
                    $tbody.html('<tr><td colspan="8" class="text-center p-4 text-danger">' + _esc((res && res.Message) ? res.Message : 'Failed to load data') + '</td></tr>');
                    return;
                }
                _render(res.rows || []);
            },
            error   : function () {
                $tbody.html('<tr><td colspan="8" class="text-center p-4 text-danger">Network error. Please try again.</td></tr>');
            }
        }).always(function () { ajaxLoading(0); });
    }

    /**
     * @param {string} format
     * @returns {void}
     */
    function _handleExport(format) {
        var from    = $('#iwpFrom').val() || '';
        var to      = $('#iwpTo').val()   || '';
        var type    = format === 'pdf' ? 'Pdf' : (format === 'print' ? 'Print' : (format === 'excel' ? 'Excel' : 'CSV'));
        var payload = { orgId: parseInt(JwtData.Org.OrgUID, 10), from: from, to: to, Type: type };

        if (type === 'Print') {
            $.ajax({
                url         : global_services_url + 'api/reports/exportItemWisePurchase',
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
                url         : global_services_url + 'api/reports/exportItemWisePurchase',
                method      : 'POST',
                contentType : 'application/json',
                headers     : { Authorization: 'Bearer ' + JwtToken },
                data        : JSON.stringify(payload),
                xhrFields   : { responseType: 'blob' },
                success : function (blob) {
                    var url = URL.createObjectURL(blob);
                    var $a  = $('<a>').attr({ href: url, download: 'item_wise_purchase.' + ext }).appendTo('body');
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
        var $fromHidden  = $('#iwpFrom');
        var $toHidden    = $('#iwpTo');
        var $fromDisplay = $('#iwpFromDisplay');
        var $toDisplay   = $('#iwpToDisplay');

        flatpickr($fromDisplay[0], {
            dateFormat:  'Y-m-d',
            defaultDate: $fromHidden.val() || _iwpInitFrom,
            onChange: function (sel, str) {
                $fromHidden.val(str);
                $fromDisplay.val(str ? new Date(str + 'T00:00:00').toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '');
            }
        });

        flatpickr($toDisplay[0], {
            dateFormat:  'Y-m-d',
            defaultDate: $toHidden.val() || _iwpInitTo,
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
        $('#iwpApplyBtn').on('click', _fetch);
        $('#iwpRefreshBtn').on('click', _fetch);
        $(document).on('click', '.r2k-export-btn[data-format]', function () {
            _handleExport($(this).data('format'));
        });
        _fetch();
    }

    $(document).ready(_init);
}());
