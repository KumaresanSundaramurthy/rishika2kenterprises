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
     * @param {number} val
     * @param {string} cls
     * @returns {string}
     */
    function _bucketCell(val, cls) {
        var n = parseFloat(val || 0);
        if (n < 0.01) {
            return '<td class="rpt-col-num" style="color:#94a3b8">—</td>';
        }
        return '<td class="rpt-col-num ' + cls + '">' + _fmt(n) + '</td>';
    }

    /**
     * @param {Array} rows
     * @returns {void}
     */
    function _render(rows) {
        var $tbody  = $('#caTableBody');
        var $tfoot  = $('#caTableFoot');
        var $footer = $('#caTblFooter');

        if (!rows || rows.length === 0) {
            $tbody.html('<tr><td colspan="9"><div class="rpt-empty"><i class="bx bx-info-circle"></i><div class="rpt-empty-title">No overdue invoices</div><div style="font-size:var(--r2k-text-82)">All customers are up to date</div></div></td></tr>');
            $tfoot.addClass('d-none');
            $footer.addClass('d-none');
            _updateStats(0, 0, 0, 0);
            return;
        }

        var html = '';
        var ft0_30 = 0, ft31_60 = 0, ft61_90 = 0, ft91_120 = 0, ft120p = 0, ftTotal = 0;

        rows.forEach(function (r, i) {
            var b0  = parseFloat(r.Bucket0_30    || 0);
            var b1  = parseFloat(r.Bucket31_60   || 0);
            var b2  = parseFloat(r.Bucket61_90   || 0);
            var b3  = parseFloat(r.Bucket91_120  || 0);
            var b4  = parseFloat(r.Bucket120Plus || 0);
            var tot = b0 + b1 + b2 + b3 + b4;

            ft0_30   += b0;
            ft31_60  += b1;
            ft61_90  += b2;
            ft91_120 += b3;
            ft120p   += b4;
            ftTotal  += tot;

            html += '<tr>';
            html += '<td>' + (i + 1) + '</td>';
            html += '<td>' + _esc(r.CustomerName) + '</td>';
            html += '<td>' + _esc(r.MobileNumber || '—') + '</td>';
            html += _bucketCell(b0, 'age-bucket-0');
            html += _bucketCell(b1, 'age-bucket-1');
            html += _bucketCell(b2, 'age-bucket-2');
            html += _bucketCell(b3, 'age-bucket-3');
            html += _bucketCell(b4, 'age-bucket-4');
            html += '<td class="rpt-col-num rpt-num-red">' + _fmt(tot) + '</td>';
            html += '</tr>';
        });

        $tbody.html(html);
        $('#caFt0_30').text(ft0_30 > 0.005   ? _fmt(ft0_30)   : '—');
        $('#caFt31_60').text(ft31_60 > 0.005  ? _fmt(ft31_60)  : '—');
        $('#caFt61_90').text(ft61_90 > 0.005  ? _fmt(ft61_90)  : '—');
        $('#caFt91_120').text(ft91_120 > 0.005 ? _fmt(ft91_120) : '—');
        $('#caFt120Plus').text(ft120p > 0.005  ? _fmt(ft120p)   : '—');
        $('#caFtTotal').text(_fmt(ftTotal));
        $tfoot.removeClass('d-none');
        $('#caRowCount').text(rows.length + ' customer' + (rows.length !== 1 ? 's' : ''));
        $('#caFooterTotal').text(_fmt(ftTotal));
        $footer.removeClass('d-none');
        _updateStats(ftTotal, ft0_30, ft31_60 + ft61_90, ft91_120 + ft120p);
    }

    /**
     * @param {number} total
     * @param {number} current
     * @param {number} mid
     * @param {number} old
     * @returns {void}
     */
    function _updateStats(total, current, mid, old) {
        $('#caStatTotal').text(_fmt(total));
        $('#caStatCurrent').text(_fmt(current));
        $('#caStatMid').text(_fmt(mid));
        $('#caStatOld').text(_fmt(old));
    }

    /**
     * @returns {void}
     */
    function _fetch() {
        var $tbody = $('#caTableBody');
        $tbody.html('<tr><td colspan="9" class="rpt-loading-cell"><div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div></td></tr>');
        $('#caTableFoot').addClass('d-none');
        $('#caTblFooter').addClass('d-none');

        ajaxLoading(1);
        $.ajax({
            url     : global_services_url + 'api/reports/getCustomerAgeingData',
            data    : { orgId: parseInt(JwtData.Org.OrgUID, 10), langCode: JwtData.User.UILanguage || 'en' },
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
                url         : global_services_url + 'api/reports/exportCustomerAgeing',
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
                url         : global_services_url + 'api/reports/exportCustomerAgeing',
                method      : 'POST',
                contentType : 'application/json',
                headers     : { Authorization: 'Bearer ' + JwtToken },
                data        : JSON.stringify(payload),
                xhrFields   : { responseType: 'blob' },
                success : function (blob) {
                    var url = URL.createObjectURL(blob);
                    var $a  = $('<a>').attr({ href: url, download: 'customer_ageing.' + ext }).appendTo('body');
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
        $('#caRefreshBtn').on('click', _fetch);
        $(document).on('click', '.r2k-export-btn[data-format]', function () {
            _handleExport($(this).data('format'));
        });
        _fetch();
    }

    $(document).ready(_init);
}());
