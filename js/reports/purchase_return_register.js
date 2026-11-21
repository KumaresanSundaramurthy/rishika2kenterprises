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
     * @param {string} status
     * @returns {string}
     */
    function _statusBadge(status) {
        var map = {
            'Issued':   'reg-status-issued',
            'Paid':     'reg-status-paid',
        };
        var cls = map[status] || 'reg-status-other';
        return '<span class="reg-status ' + cls + '">' + _esc(status) + '</span>';
    }

    /** @type {Array<Object>} */
    var _rows = [];

    /**
     * @param {Array<Object>} rows
     * @returns {void}
     */
    function _render(rows) {
        var $tbody  = $('#prrTableBody');
        var $tfoot  = $('#prrTableFoot');
        var $footer = $('#prrTblFooter');

        if (!rows || rows.length === 0) {
            $tbody.html('<tr><td colspan="11">' +
                '<div class="rpt-empty"><i class="bx bx-revision"></i>' +
                '<div class="rpt-empty-title">No debit notes found</div>' +
                '<div>Try adjusting the date range</div></div></td></tr>');
            $tfoot.addClass('d-none');
            $footer.addClass('d-none');
            return;
        }

        var totTaxable = 0, totCgst = 0, totSgst = 0, totIgst = 0, totTax = 0, totNet = 0;
        var html = '';
        rows.forEach(function (r, i) {
            var taxable = parseFloat(r.Taxable)    || 0;
            var cgst    = parseFloat(r.CgstAmount) || 0;
            var sgst    = parseFloat(r.SgstAmount) || 0;
            var igst    = parseFloat(r.IgstAmount) || 0;
            var tax     = parseFloat(r.TaxAmount)  || 0;
            var net     = parseFloat(r.NetAmount)  || 0;
            totTaxable += taxable; totCgst += cgst; totSgst += sgst;
            totIgst += igst; totTax += tax; totNet += net;

            html += '<tr>' +
                '<td>' + (i + 1) + '</td>' +
                '<td>' + _esc(r.DocNo) + '</td>' +
                '<td>' + _fmtDate(r.TransDate) + '</td>' +
                '<td>' + _esc(r.PartyName) + '</td>' +
                '<td class="rpt-col-num">' + taxable.toFixed(_dec) + '</td>' +
                '<td class="rpt-col-num">' + cgst.toFixed(_dec) + '</td>' +
                '<td class="rpt-col-num">' + sgst.toFixed(_dec) + '</td>' +
                '<td class="rpt-col-num">' + igst.toFixed(_dec) + '</td>' +
                '<td class="rpt-col-num rpt-num-yellow">' + tax.toFixed(_dec) + '</td>' +
                '<td class="rpt-col-num rpt-num-orange">' + net.toFixed(_dec) + '</td>' +
                '<td>' + _statusBadge(r.DocStatus) + '</td>' +
                '</tr>';
        });
        $tbody.html(html);

        $('#prrFtTaxable').text(totTaxable.toFixed(_dec));
        $('#prrFtCgst').text(totCgst.toFixed(_dec));
        $('#prrFtSgst').text(totSgst.toFixed(_dec));
        $('#prrFtIgst').text(totIgst.toFixed(_dec));
        $('#prrFtTax').text(totTax.toFixed(_dec));
        $('#prrFtNet').text(totNet.toFixed(_dec));
        $tfoot.removeClass('d-none');

        _updateStats(rows, totTaxable, totTax, totNet);

        $('#prrRowCount').text(rows.length + ' debit note' + (rows.length !== 1 ? 's' : ''));
        $('#prrFooterTax').text(_fmt(totTax));
        $('#prrFooterNet').text(_fmt(totNet));
        $footer.removeClass('d-none');
    }

    /**
     * @param {Array<Object>} rows
     * @param {number} totTaxable
     * @param {number} totTax
     * @param {number} totNet
     * @returns {void}
     */
    function _updateStats(rows, totTaxable, totTax, totNet) {
        $('#prrStatCount').text(rows.length);
        $('#prrStatTaxable').text(_fmt(totTaxable));
        $('#prrStatTax').text(_fmt(totTax));
        $('#prrStatNet').text(_fmt(totNet));
    }

    /** @returns {void} */
    function _fetch() {
        var from = $('#prrFrom').val();
        var to   = $('#prrTo').val();

        ajaxLoading(1);
        $('#prrTableBody').html(
            '<tr><td colspan="11" class="rpt-loading-cell">' +
            '<div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div>' +
            '</td></tr>');
        $('#prrTableFoot').addClass('d-none');
        $('#prrTblFooter').addClass('d-none');

        $.ajax({
            type    : 'GET',
            url     : global_services_url + 'api/reports/getPurchaseReturnRegisterData',
            headers : { Authorization: 'Bearer ' + JwtToken },
            data    : { orgId: parseInt(JwtData.Org.OrgUID, 10), from: from, to: to },
            success : function (res) {
                if (res.Error) {
                    $('#prrTableBody').html(
                        '<tr><td colspan="11" class="text-center text-danger py-4">' + _esc(res.Message || 'Error loading data') + '</td></tr>');
                    return;
                }
                _rows = res.rows || [];
                _render(_rows);
            },
            error   : function () {
                $('#prrTableBody').html(
                    '<tr><td colspan="11" class="text-center text-danger py-4">Request failed. Please try again.</td></tr>');
            }
        }).always(function () { ajaxLoading(0); });
    }

    /** @returns {void} */
    function _initDatePickers() {
        var initFrom = (typeof _prrInitFrom !== 'undefined') ? _prrInitFrom : '';
        var initTo   = (typeof _prrInitTo   !== 'undefined') ? _prrInitTo   : '';

        flatpickr('#prrFromDisplay', {
            static: true, position: 'below left', dateFormat: 'Y-m-d',
            defaultDate: initFrom || undefined,
            onChange: function (sel, dateStr) { $('#prrFrom').val(dateStr); }
        });
        if (initFrom) { $('#prrFromDisplay').val(_fmtDate(initFrom)); }

        flatpickr('#prrToDisplay', {
            static: true, position: 'below left', dateFormat: 'Y-m-d',
            defaultDate: initTo || undefined,
            onChange: function (sel, dateStr) { $('#prrTo').val(dateStr); }
        });
        if (initTo) { $('#prrToDisplay').val(_fmtDate(initTo)); }
    }

    /**
     * Export report data via the services endpoint.
     * @param {string} format
     * @returns {void}
     */
    function _handleExport(format) {
        var from    = $('#prrFrom').val();
        var to      = $('#prrTo').val();
        var type    = format === 'pdf' ? 'Pdf' : (format === 'print' ? 'Print' : (format === 'excel' ? 'Excel' : 'CSV'));
        var payload = { orgId: parseInt(JwtData.Org.OrgUID, 10), from: from, to: to, report: 'purchaseReturn', Type: type };
        if (type === 'Print') {
            $.ajax({
                url: global_services_url + 'api/reports/exportRegister',
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
                url: global_services_url + 'api/reports/exportRegister',
                method: 'POST', contentType: 'application/json',
                headers: { Authorization: 'Bearer ' + JwtToken },
                data: JSON.stringify(payload), xhrFields: { responseType: 'blob' },
                success: function (blob) {
                    var url = URL.createObjectURL(blob);
                    var $a  = $('<a>').attr({ href: url, download: 'Purchase_Return_Register_' + from + '_' + to + '.' + ext }).appendTo('body');
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
        $('#prrApplyBtn').on('click', _fetch);
        $('#prrRefreshBtn').on('click', _fetch);
        $(document).on('click', '.r2k-export-btn[data-format]', function () {
            _handleExport($(this).data('format'));
        });
        _fetch();
    }

    $(document).ready(_init);
})();
