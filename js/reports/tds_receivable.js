(function () {
    'use strict';

    var _dec  = (typeof genSettings !== 'undefined' && genSettings.DecimalPlaces != null) ? parseInt(genSettings.DecimalPlaces, 10) : 2;
    var _curr = (typeof genSettings !== 'undefined' && genSettings.CurrencySymbol) ? genSettings.CurrencySymbol : '₹';
    var _TDS_RATE = 0.02;

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

    /**
     * @param {string} status
     * @returns {string}
     */
    function _statusBadge(status) {
        var map = { 'Issued': 'reg-status-issued', 'Paid': 'reg-status-paid' };
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
        var $tbody = $('#tdrTableBody');
        var $tfoot = $('#tdrTableFoot');

        if (!rows || rows.length === 0) {
            $tbody.html('<tr><td colspan="10">' +
                '<div class="rpt-empty"><i class="bx bx-file-blank"></i>' +
                '<div class="rpt-empty-title">No qualifying invoices found</div>' +
                '<div>No sales above ₹30,000 in selected date range</div></div></td></tr>');
            $tfoot.addClass('d-none');
            _updateStats([], 0, 0, 0, 0);
            return;
        }

        var totTaxable = 0, totTax = 0, totNet = 0, totEst = 0;
        var uniqueCusts = {};
        var html = '';

        rows.forEach(function (r, i) {
            var taxable = parseFloat(r.Taxable)   || 0;
            var tax     = parseFloat(r.TaxAmount) || 0;
            var net     = parseFloat(r.NetAmount) || 0;
            var est     = net * _TDS_RATE;
            totTaxable += taxable; totTax += tax; totNet += net; totEst += est;
            if (r.CustomerName && r.CustomerName !== '—') uniqueCusts[r.CustomerName] = true;

            html += '<tr>' +
                '<td>' + (i + 1) + '</td>' +
                '<td>' + _esc(r.InvNo) + '</td>' +
                '<td>' + _fmtDate(r.TransDate) + '</td>' +
                '<td>' + _esc(r.CustomerName) + '</td>' +
                '<td>' + _gstinBadge(r.CustomerGSTIN) + '</td>' +
                '<td class="rpt-col-num">' + _num(taxable) + '</td>' +
                '<td class="rpt-col-num">' + _num(tax) + '</td>' +
                '<td class="rpt-col-num rpt-num-blue">' + _num(net) + '</td>' +
                '<td class="rpt-col-num rpt-num-orange">' + _num(est) + '</td>' +
                '<td>' + _statusBadge(r.DocStatus) + '</td>' +
                '</tr>';
        });
        $tbody.html(html);

        $('#tdrFtTaxable').text(_num(totTaxable));
        $('#tdrFtTax').text(_num(totTax));
        $('#tdrFtNet').text(_num(totNet));
        $('#tdrFtEst').text(_num(totEst));
        $tfoot.removeClass('d-none');
        _updateStats(rows, Object.keys(uniqueCusts).length, totNet, totEst);
    }

    /**
     * @param {Array<Object>} rows
     * @param {number}        custCount
     * @param {number}        totNet
     * @param {number}        totEst
     * @returns {void}
     */
    function _updateStats(rows, custCount, totNet, totEst) {
        $('#tdrStatCount').text(rows.length);
        $('#tdrStatCustomers').text(custCount || 0);
        $('#tdrStatNet').text(_fmt(totNet || 0));
        $('#tdrStatEst').text(_fmt(totEst || 0));
    }

    /** @returns {void} */
    function _fetch() {
        var from = $('#tdrFrom').val();
        var to   = $('#tdrTo').val();

        ajaxLoading(1);
        $('#tdrTableBody').html(
            '<tr><td colspan="10" class="rpt-loading-cell">' +
            '<div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div>' +
            '</td></tr>');
        $('#tdrTableFoot').addClass('d-none');

        $.ajax({
            type    : 'GET',
            url     : global_services_url + 'api/reports/getTdsReceivableData',
            headers : { Authorization: 'Bearer ' + JwtToken },
            data    : { orgId: parseInt(JwtData.Org.OrgUID, 10), from: from, to: to },
            success : function (res) {
                if (res.Error) {
                    $('#tdrTableBody').html(
                        '<tr><td colspan="10" class="text-center text-danger py-4">' + _esc(res.Message || 'Error loading data') + '</td></tr>');
                    return;
                }
                _rows = res.rows || [];
                _render(_rows);
            },
            error   : function () {
                $('#tdrTableBody').html(
                    '<tr><td colspan="10" class="text-center text-danger py-4">Request failed. Please try again.</td></tr>');
            }
        }).always(function () { ajaxLoading(0); });
    }

    /** @returns {void} */
    function _initDatePickers() {
        var initFrom = (typeof _tdrInitFrom !== 'undefined') ? _tdrInitFrom : '';
        var initTo   = (typeof _tdrInitTo   !== 'undefined') ? _tdrInitTo   : '';

        flatpickr('#tdrFromDisplay', {
            static: true, position: 'below left', dateFormat: 'Y-m-d',
            defaultDate: initFrom || undefined,
            onChange: function (sel, dateStr) { $('#tdrFrom').val(dateStr); }
        });
        if (initFrom) { $('#tdrFromDisplay').val(new Date(initFrom).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })); }

        flatpickr('#tdrToDisplay', {
            static: true, position: 'below left', dateFormat: 'Y-m-d',
            defaultDate: initTo || undefined,
            onChange: function (sel, dateStr) { $('#tdrTo').val(dateStr); }
        });
        if (initTo) { $('#tdrToDisplay').val(new Date(initTo).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })); }
    }

    /**
     * Export report data via the services endpoint.
     * @param {string} format
     * @returns {void}
     */
    function _handleExport(format) {
        var from    = $('#tdrFrom').val();
        var to      = $('#tdrTo').val();
        var type    = format === 'pdf' ? 'Pdf' : (format === 'print' ? 'Print' : (format === 'excel' ? 'Excel' : 'CSV'));
        var payload = { orgId: parseInt(JwtData.Org.OrgUID, 10), from: from, to: to, Type: type };
        if (type === 'Print') {
            $.ajax({
                url: global_services_url + 'api/reports/exportTdsReceivable',
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
                url: global_services_url + 'api/reports/exportTdsReceivable',
                method: 'POST', contentType: 'application/json',
                headers: { Authorization: 'Bearer ' + JwtToken },
                data: JSON.stringify(payload), xhrFields: { responseType: 'blob' },
                success: function (blob) {
                    var url = URL.createObjectURL(blob);
                    var $a  = $('<a>').attr({ href: url, download: 'TDS_Receivable_' + from + '_' + to + '.' + ext }).appendTo('body');
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
        $('#tdrApplyBtn').on('click', _fetch);
        $('#tdrRefreshBtn').on('click', _fetch);
        $(document).on('click', '.r2k-export-btn[data-format]', function () {
            _handleExport($(this).data('format'));
        });
        _fetch();
    }

    $(document).ready(_init);
})();
