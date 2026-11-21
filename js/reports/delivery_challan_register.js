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
            'Dispatched':  'reg-status-dispatched',
            'Converted':   'reg-status-converted',
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
        var $tbody  = $('#dcrTableBody');
        var $tfoot  = $('#dcrTableFoot');
        var $footer = $('#dcrTblFooter');

        if (!rows || rows.length === 0) {
            $tbody.html('<tr><td colspan="8">' +
                '<div class="rpt-empty"><i class="bx bx-package"></i>' +
                '<div class="rpt-empty-title">No challans found</div>' +
                '<div>Try adjusting the date range</div></div></td></tr>');
            $tfoot.addClass('d-none');
            $footer.addClass('d-none');
            return;
        }

        var totTaxable = 0, totTax = 0, totNet = 0;
        var uniqueParties = {};
        var pendingCount = 0;
        var html = '';

        rows.forEach(function (r, i) {
            var taxable = parseFloat(r.Taxable)   || 0;
            var tax     = parseFloat(r.TaxAmount) || 0;
            var net     = parseFloat(r.NetAmount) || 0;
            totTaxable += taxable; totTax += tax; totNet += net;
            if (r.PartyName && r.PartyName !== '—') uniqueParties[r.PartyName] = true;
            if (r.DocStatus !== 'Converted') pendingCount++;

            html += '<tr>' +
                '<td>' + (i + 1) + '</td>' +
                '<td>' + _esc(r.DocNo) + '</td>' +
                '<td>' + _fmtDate(r.TransDate) + '</td>' +
                '<td>' + _esc(r.PartyName) + '</td>' +
                '<td class="rpt-col-num">' + taxable.toFixed(_dec) + '</td>' +
                '<td class="rpt-col-num">' + tax.toFixed(_dec) + '</td>' +
                '<td class="rpt-col-num rpt-num-blue">' + net.toFixed(_dec) + '</td>' +
                '<td>' + _statusBadge(r.DocStatus) + '</td>' +
                '</tr>';
        });
        $tbody.html(html);

        $('#dcrFtTaxable').text(totTaxable.toFixed(_dec));
        $('#dcrFtTax').text(totTax.toFixed(_dec));
        $('#dcrFtNet').text(totNet.toFixed(_dec));
        $tfoot.removeClass('d-none');

        _updateStats(rows, uniqueParties, pendingCount, totNet);

        $('#dcrRowCount').text(rows.length + ' challan' + (rows.length !== 1 ? 's' : ''));
        $('#dcrFooterNet').text(_fmt(totNet));
        $footer.removeClass('d-none');
    }

    /**
     * @param {Array<Object>} rows
     * @param {Object} uniqueParties
     * @param {number} pendingCount
     * @param {number} totNet
     * @returns {void}
     */
    function _updateStats(rows, uniqueParties, pendingCount, totNet) {
        $('#dcrStatCount').text(rows.length);
        $('#dcrStatCustomers').text(Object.keys(uniqueParties).length);
        $('#dcrStatPending').text(pendingCount);
        $('#dcrStatNet').text(_fmt(totNet));
    }

    /** @returns {void} */
    function _fetch() {
        var from = $('#dcrFrom').val();
        var to   = $('#dcrTo').val();

        ajaxLoading(1);
        $('#dcrTableBody').html(
            '<tr><td colspan="8" class="rpt-loading-cell">' +
            '<div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div>' +
            '</td></tr>');
        $('#dcrTableFoot').addClass('d-none');
        $('#dcrTblFooter').addClass('d-none');

        $.ajax({
            type    : 'GET',
            url     : global_services_url + 'api/reports/getDeliveryChallanRegisterData',
            headers : { Authorization: 'Bearer ' + JwtToken },
            data    : { orgId: parseInt(JwtData.Org.OrgUID, 10), from: from, to: to },
            success : function (res) {
                if (res.Error) {
                    $('#dcrTableBody').html(
                        '<tr><td colspan="8" class="text-center text-danger py-4">' + _esc(res.Message || 'Error loading data') + '</td></tr>');
                    return;
                }
                _rows = res.rows || [];
                _render(_rows);
            },
            error   : function () {
                $('#dcrTableBody').html(
                    '<tr><td colspan="8" class="text-center text-danger py-4">Request failed. Please try again.</td></tr>');
            }
        }).always(function () { ajaxLoading(0); });
    }

    /** @returns {void} */
    function _initDatePickers() {
        var initFrom = (typeof _dcrInitFrom !== 'undefined') ? _dcrInitFrom : '';
        var initTo   = (typeof _dcrInitTo   !== 'undefined') ? _dcrInitTo   : '';

        flatpickr('#dcrFromDisplay', {
            static: true, position: 'below left', dateFormat: 'Y-m-d',
            defaultDate: initFrom || undefined,
            onChange: function (sel, dateStr) { $('#dcrFrom').val(dateStr); }
        });
        if (initFrom) { $('#dcrFromDisplay').val(_fmtDate(initFrom)); }

        flatpickr('#dcrToDisplay', {
            static: true, position: 'below left', dateFormat: 'Y-m-d',
            defaultDate: initTo || undefined,
            onChange: function (sel, dateStr) { $('#dcrTo').val(dateStr); }
        });
        if (initTo) { $('#dcrToDisplay').val(_fmtDate(initTo)); }
    }

    /**
     * Export report data via the services endpoint.
     * @param {string} format
     * @returns {void}
     */
    function _handleExport(format) {
        var from    = $('#dcrFrom').val();
        var to      = $('#dcrTo').val();
        var type    = format === 'pdf' ? 'Pdf' : (format === 'print' ? 'Print' : (format === 'excel' ? 'Excel' : 'CSV'));
        var payload = { orgId: parseInt(JwtData.Org.OrgUID, 10), from: from, to: to, report: 'deliveryChallan', Type: type };
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
                    var $a  = $('<a>').attr({ href: url, download: 'Delivery_Challan_' + from + '_' + to + '.' + ext }).appendTo('body');
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
        $('#dcrApplyBtn').on('click', _fetch);
        $('#dcrRefreshBtn').on('click', _fetch);
        $(document).on('click', '.r2k-export-btn[data-format]', function () {
            _handleExport($(this).data('format'));
        });
        _fetch();
    }

    $(document).ready(_init);
})();
