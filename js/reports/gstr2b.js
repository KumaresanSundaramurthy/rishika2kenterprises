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
    function _fmtDate(iso) {
        if (!iso) return '—';
        var d = new Date(iso);
        if (isNaN(d.getTime())) return iso;
        return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    /**
     * @param {string} gstin
     * @returns {string}
     */
    function _gstinBadge(gstin) {
        if (!gstin) return '<span class="g1-unregd-badge">No GSTIN</span>';
        return '<span class="g1-gstin-badge">' + _esc(gstin) + '</span>';
    }

    /** @type {Array<Object>} */
    var _rows = [];

    /**
     * @param {Array<Object>} rows
     * @returns {void}
     */
    function _render(rows) {
        if (!rows || rows.length === 0) {
            $('#g2TableBody').html('<tr><td colspan="12">' +
                '<div class="rpt-empty"><i class="bx bx-store"></i>' +
                '<div class="rpt-empty-title">No inward supplies found</div>' +
                '<div>No purchase bills from GSTIN-registered suppliers for this month</div></div></td></tr>');
            $('#g2TableFoot').addClass('d-none');
            _updateStats([], 0, 0);
            return;
        }

        var totTaxable = 0, totCgst = 0, totSgst = 0, totIgst = 0, totTax = 0, totNet = 0;
        var uniqueSuppliers = {};
        var html = '';

        rows.forEach(function (r, i) {
            var taxable = parseFloat(r.Taxable) || 0;
            var cgst    = parseFloat(r.CgstAmount) || 0;
            var sgst    = parseFloat(r.SgstAmount) || 0;
            var igst    = parseFloat(r.IgstAmount) || 0;
            var tax     = parseFloat(r.TaxAmount)  || 0;
            var net     = parseFloat(r.NetAmount)  || 0;
            totTaxable += taxable; totCgst += cgst; totSgst += sgst;
            totIgst += igst; totTax += tax; totNet += net;
            if (r.SupplierGSTIN) uniqueSuppliers[r.SupplierGSTIN] = true;

            html += '<tr>' +
                '<td>' + (i + 1) + '</td>' +
                '<td>' + _esc(r.BillNo) + '</td>' +
                '<td>' + _fmtDate(r.TransDate) + '</td>' +
                '<td>' + _gstinBadge(r.SupplierGSTIN) + '</td>' +
                '<td>' + _esc(r.SupplierName) + '</td>' +
                '<td>' + _esc(r.PlaceOfSupply || '—') + '</td>' +
                '<td class="rpt-col-num">' + _num(taxable) + '</td>' +
                '<td class="rpt-col-num">' + _num(cgst) + '</td>' +
                '<td class="rpt-col-num">' + _num(sgst) + '</td>' +
                '<td class="rpt-col-num">' + _num(igst) + '</td>' +
                '<td class="rpt-col-num rpt-num-green">' + _num(tax) + '</td>' +
                '<td class="rpt-col-num rpt-num-blue">' + _num(net) + '</td>' +
                '</tr>';
        });
        $('#g2TableBody').html(html);

        $('#g2FtTaxable').text(_num(totTaxable));
        $('#g2FtCgst').text(_num(totCgst));
        $('#g2FtSgst').text(_num(totSgst));
        $('#g2FtIgst').text(_num(totIgst));
        $('#g2FtTax').text(_num(totTax));
        $('#g2FtNet').text(_num(totNet));
        $('#g2TableFoot').removeClass('d-none');
        _updateStats(rows, Object.keys(uniqueSuppliers).length, totTaxable, totTax);
    }

    /**
     * @param {Array<Object>} rows
     * @param {number}        supplierCount
     * @param {number}        totTaxable
     * @param {number}        totTax
     * @returns {void}
     */
    function _updateStats(rows, supplierCount, totTaxable, totTax) {
        $('#g2StatCount').text(rows.length);
        $('#g2StatSuppliers').text(supplierCount || 0);
        $('#g2StatTaxable').text(_fmt(totTaxable || 0));
        $('#g2StatItc').text(_fmt(totTax || 0));
    }

    /**
     * Export the GSTR-2B data via the services endpoint.
     * @param {string} format
     * @returns {void}
     */
    function _handleExport(format) {
        var month = $('#g2Month').val();
        var year  = $('#g2Year').val();
        var type  = format === 'pdf' ? 'Pdf' : (format === 'print' ? 'Print' : (format === 'excel' ? 'Excel' : 'CSV'));

        var payload = {
            orgId : parseInt(JwtData.Org.OrgUID, 10),
            month : parseInt(month, 10),
            year  : parseInt(year, 10),
            Type  : type
        };

        if (type === 'Print') {
            $.ajax({
                url        : global_services_url + 'api/reports/exportGstr2b',
                method     : 'POST',
                contentType: 'application/json',
                headers    : { Authorization: 'Bearer ' + JwtToken },
                data       : JSON.stringify(payload),
                dataType   : 'json',
                success    : function (resp) {
                    if (!resp || resp.Error || !resp.HtmlData) {
                        showToastNotification((resp && resp.Message) ? resp.Message : 'Export failed.', 'error');
                        return;
                    }
                    var win = window.open('', '_blank');
                    win.document.open(); win.document.write(resp.HtmlData); win.document.close();
                },
                error : function () { showToastNotification('Export failed. Please try again.', 'error'); }
            });
        } else {
            var ext = type === 'Excel' ? 'xlsx' : (type === 'Pdf' ? 'pdf' : 'csv');
            $.ajax({
                url        : global_services_url + 'api/reports/exportGstr2b',
                method     : 'POST',
                contentType: 'application/json',
                headers    : { Authorization: 'Bearer ' + JwtToken },
                data       : JSON.stringify(payload),
                xhrFields  : { responseType: 'blob' },
                success    : function (blob) {
                    var url = URL.createObjectURL(blob);
                    var $a  = $('<a>').attr({ href: url, download: 'GSTR2B_' + year + '_' + month + '.' + ext }).appendTo('body');
                    $a[0].click();
                    setTimeout(function () { URL.revokeObjectURL(url); $a.remove(); }, 3000);
                },
                error : function () { showToastNotification('Export failed. Please try again.', 'error'); }
            });
        }
    }

    /** @returns {void} */
    function _fetch() {
        var month = $('#g2Month').val();
        var year  = $('#g2Year').val();

        ajaxLoading(1);
        $('#g2TableBody').html(
            '<tr><td colspan="12" class="rpt-loading-cell">' +
            '<div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div>' +
            '</td></tr>');
        $('#g2TableFoot').addClass('d-none');

        $.ajax({
            type    : 'GET',
            url     : global_services_url + 'api/reports/getGstr2bData',
            headers : { Authorization: 'Bearer ' + JwtToken },
            data    : { orgId: parseInt(JwtData.Org.OrgUID, 10), month: month, year: year },
            success : function (res) {
                if (res.Error) {
                    $('#g2TableBody').html(
                        '<tr><td colspan="12" class="text-center text-danger py-4">' + _esc(res.Message || 'Error loading data') + '</td></tr>');
                    return;
                }
                _rows = res.rows || [];
                _render(_rows);
            },
            error   : function () {
                $('#g2TableBody').html(
                    '<tr><td colspan="12" class="text-center text-danger py-4">Request failed. Please try again.</td></tr>');
            }
        }).always(function () { ajaxLoading(0); });
    }

    /** @returns {void} */
    function _init() {
        $('#g2ApplyBtn').on('click', _fetch);
        $('#g2RefreshBtn').on('click', _fetch);
        $(document).on('click', '.r2k-export-btn[data-format]', function () {
            _handleExport($(this).data('format'));
        });
        _fetch();
    }

    $(document).ready(_init);
})();
