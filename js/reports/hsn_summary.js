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
    function _fmtQty(n) {
        var v = parseFloat(n) || 0;
        return v.toLocaleString('en-IN', { maximumFractionDigits: _dec });
    }

    /** @type {Array<Object>} */
    var _rows = [];

    /**
     * @param {Array<Object>} rows
     * @returns {void}
     */
    function _render(rows) {
        var $tbody = $('#hsnTableBody');
        var $tfoot = $('#hsnTableFoot');

        if (!rows || rows.length === 0) {
            $tbody.html('<tr><td colspan="11">' +
                '<div class="rpt-empty"><i class="bx bx-barcode"></i>' +
                '<div class="rpt-empty-title">No HSN data found</div>' +
                '<div>No sales with product HSN codes for selected date range</div></div></td></tr>');
            $tfoot.addClass('d-none');
            _updateStats([], 0, 0, 0);
            return;
        }

        var totQty = 0, totTaxable = 0, totCgst = 0, totSgst = 0, totIgst = 0, totTax = 0;
        var html = '';

        rows.forEach(function (r, i) {
            var qty     = parseFloat(r.TotalQty)   || 0;
            var taxable = parseFloat(r.TaxableValue) || 0;
            var cgst    = parseFloat(r.CgstAmount)  || 0;
            var sgst    = parseFloat(r.SgstAmount)  || 0;
            var igst    = parseFloat(r.IgstAmount)  || 0;
            var tax     = parseFloat(r.TaxAmount)   || 0;
            totQty += qty; totTaxable += taxable;
            totCgst += cgst; totSgst += sgst; totIgst += igst; totTax += tax;

            html += '<tr>' +
                '<td>' + (i + 1) + '</td>' +
                '<td><span class="hsn-code-badge">' + _esc(r.HsnCode) + '</span></td>' +
                '<td>' + _esc(r.UOM) + '</td>' +
                '<td class="rpt-col-num">' + _num(r.TaxRate) + '%</td>' +
                '<td class="rpt-col-num">' + _fmtQty(qty) + '</td>' +
                '<td class="rpt-col-num">' + _num(taxable) + '</td>' +
                '<td class="rpt-col-num">' + _num(cgst) + '</td>' +
                '<td class="rpt-col-num">' + _num(sgst) + '</td>' +
                '<td class="rpt-col-num">' + _num(igst) + '</td>' +
                '<td class="rpt-col-num rpt-num-teal">' + _num(tax) + '</td>' +
                '<td class="rpt-col-num">' + (parseInt(r.InvCount, 10) || 0) + '</td>' +
                '</tr>';
        });
        $tbody.html(html);

        $('#hsnFtTaxable').text(_num(totTaxable));
        $('#hsnFtCgst').text(_num(totCgst));
        $('#hsnFtSgst').text(_num(totSgst));
        $('#hsnFtIgst').text(_num(totIgst));
        $('#hsnFtTax').text(_num(totTax));
        $tfoot.removeClass('d-none');
        _updateStats(rows, totQty, totTaxable, totTax);
    }

    /**
     * @param {Array<Object>} rows
     * @param {number}        totQty
     * @param {number}        totTaxable
     * @param {number}        totTax
     * @returns {void}
     */
    function _updateStats(rows, totQty, totTaxable, totTax) {
        $('#hsnStatCodes').text(rows.length);
        $('#hsnStatQty').text(_fmtQty(totQty || 0));
        $('#hsnStatTaxable').text(_fmt(totTaxable || 0));
        $('#hsnStatTax').text(_fmt(totTax || 0));
    }

    /** @returns {void} */
    function _fetch() {
        var from = $('#hsnFrom').val();
        var to   = $('#hsnTo').val();

        ajaxLoading(1);
        $('#hsnTableBody').html(
            '<tr><td colspan="11" class="rpt-loading-cell">' +
            '<div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div>' +
            '</td></tr>');
        $('#hsnTableFoot').addClass('d-none');

        $.ajax({
            type    : 'GET',
            url     : global_services_url + 'api/reports/getHsnData',
            headers : { Authorization: 'Bearer ' + JwtToken },
            data    : { orgId: parseInt(JwtData.Org.OrgUID, 10), from: from, to: to },
            success : function (res) {
                if (res.Error) {
                    $('#hsnTableBody').html(
                        '<tr><td colspan="11" class="text-center text-danger py-4">' + _esc(res.Message || 'Error loading data') + '</td></tr>');
                    return;
                }
                _rows = res.rows || [];
                _render(_rows);
            },
            error   : function () {
                $('#hsnTableBody').html(
                    '<tr><td colspan="11" class="text-center text-danger py-4">Request failed. Please try again.</td></tr>');
            }
        }).always(function () { ajaxLoading(0); });
    }

    /** @returns {void} */
    function _initDatePickers() {
        var initFrom = (typeof _hsnInitFrom !== 'undefined') ? _hsnInitFrom : '';
        var initTo   = (typeof _hsnInitTo   !== 'undefined') ? _hsnInitTo   : '';

        flatpickr('#hsnFromDisplay', {
            static: true, position: 'below left', dateFormat: 'Y-m-d',
            defaultDate: initFrom || undefined,
            onChange: function (sel, dateStr) { $('#hsnFrom').val(dateStr); }
        });
        if (initFrom) { $('#hsnFromDisplay').val(new Date(initFrom).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })); }

        flatpickr('#hsnToDisplay', {
            static: true, position: 'below left', dateFormat: 'Y-m-d',
            defaultDate: initTo || undefined,
            onChange: function (sel, dateStr) { $('#hsnTo').val(dateStr); }
        });
        if (initTo) { $('#hsnToDisplay').val(new Date(initTo).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })); }
    }

    /**
     * Export report data via the services endpoint.
     * @param {string} format
     * @returns {void}
     */
    function _handleExport(format) {
        var from    = $('#hsnFrom').val();
        var to      = $('#hsnTo').val();
        var type    = format === 'pdf' ? 'Pdf' : (format === 'print' ? 'Print' : (format === 'excel' ? 'Excel' : 'CSV'));
        var payload = { orgId: parseInt(JwtData.Org.OrgUID, 10), from: from, to: to, Type: type };
        if (type === 'Print') {
            $.ajax({
                url: global_services_url + 'api/reports/exportHsnSummary',
                method: 'POST', contentType: 'application/json',
                headers: { Authorization: 'Bearer ' + JwtToken },
                data: JSON.stringify(payload), dataType: 'json',
                success: function (resp) {
                    if (!resp || resp.Error || !resp.HtmlData) { showToastNotification((resp && resp.Message) ? resp.Message : 'Export failed.', 'error'); return; }
                    var win = window.open('', '_blank');
                    win.document.open(); win.document.write(resp.HtmlData); win.document.close();
                },
                error: function () { showToastNotification('Export failed. Please try again.', 'error'); }
            });
        } else {
            var ext = type === 'Excel' ? 'xlsx' : (type === 'Pdf' ? 'pdf' : 'csv');
            $.ajax({
                url: global_services_url + 'api/reports/exportHsnSummary',
                method: 'POST', contentType: 'application/json',
                headers: { Authorization: 'Bearer ' + JwtToken },
                data: JSON.stringify(payload), xhrFields: { responseType: 'blob' },
                success: function (blob) {
                    var url = URL.createObjectURL(blob);
                    var $a  = $('<a>').attr({ href: url, download: 'HSN_Summary_' + from + '_' + to + '.' + ext }).appendTo('body');
                    $a[0].click();
                    setTimeout(function () { URL.revokeObjectURL(url); $a.remove(); }, 3000);
                },
                error: function () { showToastNotification('Export failed. Please try again.', 'error'); }
            });
        }
    }

    /** @returns {void} */
    function _init() {
        _initDatePickers();
        $('#hsnApplyBtn').on('click', _fetch);
        $('#hsnRefreshBtn').on('click', _fetch);
        $(document).on('click', '.r2k-export-btn[data-format]', function () {
            _handleExport($(this).data('format'));
        });
        _fetch();
    }

    $(document).ready(_init);
})();
