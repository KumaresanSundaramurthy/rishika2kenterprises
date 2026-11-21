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
     * @param {string} status
     * @returns {string}
     */
    function _statusBadge(status) {
        var cls = status === 'Approved' ? 'reg-status-issued' : 'reg-status-other';
        return '<span class="reg-status ' + cls + '">' + _esc(status) + '</span>';
    }

    /**
     * @param {number|string} isPaid
     * @returns {string}
     */
    function _paidBadge(isPaid) {
        return parseInt(isPaid, 10) === 1
            ? '<span class="exr-paid-chip">Paid</span>'
            : '<span class="exr-unpaid-chip">Unpaid</span>';
    }

    /** @type {Array<Object>} */
    var _rows = [];

    /**
     * @param {Array<Object>} rows
     * @returns {void}
     */
    function _render(rows) {
        var $tbody = $('#tdpTableBody');
        var $tfoot = $('#tdpTableFoot');

        if (!rows || rows.length === 0) {
            $tbody.html('<tr><td colspan="13">' +
                '<div class="rpt-empty"><i class="bx bx-minus-circle"></i>' +
                '<div class="rpt-empty-title">No TDS payable entries found</div>' +
                '<div>No expenses with TDS deducted for selected date range</div></div></td></tr>');
            $tfoot.addClass('d-none');
            _updateStats([], 0, 0, 0);
            return;
        }

        var totBase = 0, totTds = 0, totNet = 0;
        var uniqueVendors = {};
        var html = '';

        rows.forEach(function (r, i) {
            var base = parseFloat(r.BaseAmount) || 0;
            var tds  = parseFloat(r.TdsAmount)  || 0;
            var net  = parseFloat(r.NetAmount)  || 0;
            totBase += base; totTds += tds; totNet += net;
            if (r.VendorName && r.VendorName !== '—') uniqueVendors[r.VendorName] = true;

            html += '<tr>' +
                '<td>' + (i + 1) + '</td>' +
                '<td>' + _esc(r.DocNo) + '</td>' +
                '<td>' + _fmtDate(r.TransDate) + '</td>' +
                '<td>' + _esc(r.VendorName) + '</td>' +
                '<td>' + _esc(r.VendorGSTIN || '—') + '</td>' +
                '<td><span class="g7-section-badge">' + _esc(r.TdsSection) + '</span></td>' +
                '<td class="text-muted small">' + _esc(r.TdsSectionDesc) + '</td>' +
                '<td class="rpt-col-num">' + _num(base) + '</td>' +
                '<td class="rpt-col-num">' + _num(r.TdsPct) + '%</td>' +
                '<td class="rpt-col-num rpt-num-red">' + _num(tds) + '</td>' +
                '<td class="rpt-col-num rpt-num-orange">' + _num(net) + '</td>' +
                '<td>' + _paidBadge(r.IsPaid) + '</td>' +
                '<td>' + _statusBadge(r.DocStatus) + '</td>' +
                '</tr>';
        });
        $tbody.html(html);

        $('#tdpFtBase').text(_num(totBase));
        $('#tdpFtTds').text(_num(totTds));
        $('#tdpFtNet').text(_num(totNet));
        $tfoot.removeClass('d-none');
        _updateStats(rows, Object.keys(uniqueVendors).length, totBase, totTds);
    }

    /**
     * @param {Array<Object>} rows
     * @param {number}        vendorCount
     * @param {number}        totBase
     * @param {number}        totTds
     * @returns {void}
     */
    function _updateStats(rows, vendorCount, totBase, totTds) {
        $('#tdpStatCount').text(rows.length);
        $('#tdpStatVendors').text(vendorCount || 0);
        $('#tdpStatBase').text(_fmt(totBase || 0));
        $('#tdpStatTds').text(_fmt(totTds  || 0));
    }

    /** @returns {void} */
    function _fetch() {
        var from = $('#tdpFrom').val();
        var to   = $('#tdpTo').val();

        ajaxLoading(1);
        $('#tdpTableBody').html(
            '<tr><td colspan="13" class="rpt-loading-cell">' +
            '<div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div>' +
            '</td></tr>');
        $('#tdpTableFoot').addClass('d-none');

        $.ajax({
            type    : 'GET',
            url     : global_services_url + 'api/reports/getTdsPayableData',
            headers : { Authorization: 'Bearer ' + JwtToken },
            data    : { orgId: parseInt(JwtData.Org.OrgUID, 10), from: from, to: to },
            success : function (res) {
                if (res.Error) {
                    $('#tdpTableBody').html(
                        '<tr><td colspan="13" class="text-center text-danger py-4">' + _esc(res.Message || 'Error loading data') + '</td></tr>');
                    return;
                }
                _rows = res.rows || [];
                _render(_rows);
            },
            error   : function () {
                $('#tdpTableBody').html(
                    '<tr><td colspan="13" class="text-center text-danger py-4">Request failed. Please try again.</td></tr>');
            }
        }).always(function () { ajaxLoading(0); });
    }

    /** @returns {void} */
    function _initDatePickers() {
        var initFrom = (typeof _tdpInitFrom !== 'undefined') ? _tdpInitFrom : '';
        var initTo   = (typeof _tdpInitTo   !== 'undefined') ? _tdpInitTo   : '';

        flatpickr('#tdpFromDisplay', {
            static: true, position: 'below left', dateFormat: 'Y-m-d',
            defaultDate: initFrom || undefined,
            onChange: function (sel, dateStr) { $('#tdpFrom').val(dateStr); }
        });
        if (initFrom) { $('#tdpFromDisplay').val(new Date(initFrom).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })); }

        flatpickr('#tdpToDisplay', {
            static: true, position: 'below left', dateFormat: 'Y-m-d',
            defaultDate: initTo || undefined,
            onChange: function (sel, dateStr) { $('#tdpTo').val(dateStr); }
        });
        if (initTo) { $('#tdpToDisplay').val(new Date(initTo).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })); }
    }

    /**
     * Export report data via the services endpoint.
     * @param {string} format
     * @returns {void}
     */
    function _handleExport(format) {
        var from    = $('#tdpFrom').val();
        var to      = $('#tdpTo').val();
        var type    = format === 'pdf' ? 'Pdf' : (format === 'print' ? 'Print' : (format === 'excel' ? 'Excel' : 'CSV'));
        var payload = { orgId: parseInt(JwtData.Org.OrgUID, 10), from: from, to: to, Type: type };
        if (type === 'Print') {
            $.ajax({
                url: global_services_url + 'api/reports/exportTdsPayable',
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
                url: global_services_url + 'api/reports/exportTdsPayable',
                method: 'POST', contentType: 'application/json',
                headers: { Authorization: 'Bearer ' + JwtToken },
                data: JSON.stringify(payload), xhrFields: { responseType: 'blob' },
                success: function (blob) {
                    var url = URL.createObjectURL(blob);
                    var $a  = $('<a>').attr({ href: url, download: 'TDS_Payable_' + from + '_' + to + '.' + ext }).appendTo('body');
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
        $('#tdpApplyBtn').on('click', _fetch);
        $('#tdpRefreshBtn').on('click', _fetch);
        $(document).on('click', '.r2k-export-btn[data-format]', function () {
            _handleExport($(this).data('format'));
        });
        _fetch();
    }

    $(document).ready(_init);
})();
