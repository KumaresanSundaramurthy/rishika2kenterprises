/**
 * Trial Balance Report
 * Displays all ledger accounts with opening balance, period movements, and closing balance.
 * Closing balance = Opening ± (Debit − Credit) based on OpeningBalanceType.
 * If total closing Debit = total closing Credit the trial balance is Balanced.
 */

(function () {
    'use strict';

    var _from   = _tbInitFrom;
    var _to     = _tbInitTo;
    var _fpFrom = null;
    var _fpTo   = null;

    var TYPE_CHIPS = {
        Asset     : 'tb-type-asset',
        Liability : 'tb-type-liability',
        Income    : 'tb-type-income',
        Expense   : 'tb-type-expense',
        Bank      : 'tb-type-bank',
        Cash      : 'tb-type-cash',
        Customer  : 'tb-type-customer',
        Vendor    : 'tb-type-vendor',
        Employee  : 'tb-type-customer',
    };

    var _cur = (typeof genSettings !== 'undefined' && genSettings.CurrenySymbol) ? genSettings.CurrenySymbol : '₹';
    var _dec = 2;

    /**
     * @param {number} n
     * @returns {string}
     */
    function _fmt(n) {
        return _cur + ' ' + parseFloat(n || 0).toLocaleString('en-IN', {
            minimumFractionDigits : _dec,
            maximumFractionDigits : _dec,
        });
    }

    /**
     * Compute closing balance from opening + period movements.
     *
     * @param {number} obBal
     * @param {string} obType  'Debit' | 'Credit'
     * @param {number} debit
     * @param {number} credit
     * @returns {{ bal: number, type: string }}
     */
    function _calcBalance(obBal, obType, debit, credit) {
        var ob  = parseFloat(obBal)  || 0;
        var dr  = parseFloat(debit)  || 0;
        var cr  = parseFloat(credit) || 0;
        var net = ob + (obType === 'Debit' ? dr - cr : cr - dr);
        if (net >= 0) return { bal: net, type: obType };
        return { bal: Math.abs(net), type: obType === 'Debit' ? 'Credit' : 'Debit' };
    }

    /**
     * @param {number} val
     * @param {string} side   'Debit' | 'Credit'
     * @param {string} wantSide  which column this cell is for
     * @returns {string}
     */
    function _amtCell(val, side, wantSide) {
        if (side !== wantSide || val === 0) return '<td class="rpt-col-num tb-zero">—</td>';
        var cls = wantSide === 'Debit' ? 'tb-dr' : 'tb-cr';
        return '<td class="rpt-col-num ' + cls + '">' + _fmt(val) + '</td>';
    }

    /**
     * @param {Array} rows
     */
    function _render(rows) {
        var $tbody  = $('#tbTableBody');
        var $tfoot  = $('#tbTableFoot');
        var $footer = $('#tbTblFooter');

        if (!rows.length) {
            $tbody.html('<tr><td colspan="9"><div class="rpt-empty">'
                + '<i class="bx bx-transfer-alt"></i>'
                + '<div class="rpt-empty-title">No data for this period</div>'
                + '<div>No ledger accounts with activity in this date range</div>'
                + '</div></td></tr>');
            $tfoot.addClass('d-none');
            $footer.addClass('d-none');
            _updateStats(0, 0, 0, true);
            return;
        }

        var grandObDr    = 0, grandObCr    = 0;
        var grandPeriodDr = 0, grandPeriodCr = 0;
        var grandCloseDr = 0, grandCloseCr  = 0;

        var currentType = null;
        var html = '';

        rows.forEach(function (r) {
            // Group header when LedgerType changes
            if (r.LedgerType !== currentType) {
                currentType = r.LedgerType;
                html += '<tr class="tb-group-hd"><td colspan="9">' + currentType + '</td></tr>';
            }

            var ob   = parseFloat(r.OpeningBalance) || 0;
            var obT  = r.OpeningBalanceType || 'Debit';
            var pDr  = parseFloat(r.PeriodDebit)  || 0;
            var pCr  = parseFloat(r.PeriodCredit) || 0;

            var cb   = _calcBalance(ob, obT, pDr, pCr);

            // Grand totals
            if (obT === 'Debit')       { grandObDr    += ob;      } else { grandObCr    += ob;      }
            grandPeriodDr += pDr;
            grandPeriodCr += pCr;
            if (cb.type === 'Debit')   { grandCloseDr += cb.bal;  } else { grandCloseCr += cb.bal;  }

            var chipCls = TYPE_CHIPS[r.LedgerType] || 'tb-type-asset';

            html += '<tr>'
                + '<td style="font-size:var(--r2k-text-75);color:#64748b;">' + (r.LedgerCode || '—') + '</td>'
                + '<td>' + (r.LedgerName || '—') + '</td>'
                + '<td><span class="tb-type-chip ' + chipCls + '">' + r.LedgerType + '</span></td>'
                + _amtCell(ob, obT, 'Debit')
                + _amtCell(ob, obT, 'Credit')
                + (pDr > 0 ? '<td class="rpt-col-num tb-dr">' + _fmt(pDr) + '</td>' : '<td class="rpt-col-num tb-zero">—</td>')
                + (pCr > 0 ? '<td class="rpt-col-num tb-cr">' + _fmt(pCr) + '</td>' : '<td class="rpt-col-num tb-zero">—</td>')
                + _amtCell(cb.bal, cb.type, 'Debit')
                + _amtCell(cb.bal, cb.type, 'Credit')
                + '</tr>';
        });

        $tbody.html(html);

        // Tfoot
        $('#tbFtObDr').text(_fmt(grandObDr));
        $('#tbFtObCr').text(_fmt(grandObCr));
        $('#tbFtPeriodDr').text(_fmt(grandPeriodDr));
        $('#tbFtPeriodCr').text(_fmt(grandPeriodCr));
        $('#tbFtCloseDr').text(_fmt(grandCloseDr));
        $('#tbFtCloseCr').text(_fmt(grandCloseCr));
        $tfoot.removeClass('d-none');

        // Footer chips
        var isBalanced = Math.abs(grandCloseDr - grandCloseCr) < 0.01;
        $('#tbRowCount').text(rows.length + ' account(s)');
        $('#tbFooterDr').text(_fmt(grandCloseDr));
        $('#tbFooterCr').text(_fmt(grandCloseCr));
        $('#tbFooterBalanced').html(isBalanced
            ? '<span class="tb-balanced-ok"><i class="bx bx-check"></i>Balanced</span>'
            : '<span class="tb-balanced-off"><i class="bx bx-x"></i>Difference: ' + _fmt(Math.abs(grandCloseDr - grandCloseCr)) + '</span>');
        $footer.removeClass('d-none');

        _updateStats(rows.length, grandCloseDr, grandCloseCr, isBalanced);
        history.replaceState(null, '', '?from=' + _from + '&to=' + _to);
    }

    /**
     * @param {number} count
     * @param {number} totalDr
     * @param {number} totalCr
     * @param {boolean} balanced
     */
    function _updateStats(count, totalDr, totalCr, balanced) {
        $('#tbStatCount').text(count || '0');
        $('#tbStatDebit').text(_fmt(totalDr));
        $('#tbStatCredit').text(_fmt(totalCr));
        $('#tbStatBalanced').html(balanced
            ? '<span class="tb-balanced-ok"><i class="bx bx-check"></i>Yes</span>'
            : '<span class="tb-balanced-off"><i class="bx bx-x"></i>No</span>');
    }

    /** Fetch data from server and render. */
    function _fetch() {
        var $tbody = $('#tbTableBody');
        $tbody.html('<tr><td colspan="9" class="rpt-loading-cell">'
            + '<div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div></td></tr>');
        $('#tbTableFoot').addClass('d-none');
        $('#tbTblFooter').addClass('d-none');

        ajaxLoading(1);
        $.ajax({
            url      : global_services_url + 'api/reports/getTrialBalanceData',
            type     : 'GET',
            dataType : 'json',
            headers  : { Authorization: 'Bearer ' + JwtToken },
            data     : { orgId: parseInt(JwtData.Org.OrgUID, 10), from: _from, to: _to },
            success  : function (res) {
                ajaxLoading(0);
                if (!res || res.Error) {
                    $tbody.html('<tr><td colspan="9" class="rpt-loading-cell text-danger">'
                        + (res && res.Message ? res.Message : 'Server error') + '</td></tr>');
                    return;
                }
                _render(res.rows || []);
            },
            error: function () {
                ajaxLoading(0);
                $tbody.html('<tr><td colspan="9" class="rpt-loading-cell text-danger">Request failed. Please try again.</td></tr>');
            },
        });
    }

    /**
     * @param {string} format
     * @returns {void}
     */
    function _handleExport(format) {
        var type    = format === 'pdf' ? 'Pdf' : (format === 'print' ? 'Print' : (format === 'excel' ? 'Excel' : 'CSV'));
        var payload = { orgId: parseInt(JwtData.Org.OrgUID, 10), from: _from, to: _to, Type: type };

        if (type === 'Print') {
            $.ajax({
                url         : global_services_url + 'api/reports/exportTrialBalance',
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
                url         : global_services_url + 'api/reports/exportTrialBalance',
                method      : 'POST',
                contentType : 'application/json',
                headers     : { Authorization: 'Bearer ' + JwtToken },
                data        : JSON.stringify(payload),
                xhrFields   : { responseType: 'blob' },
                success : function (blob) {
                    var url = URL.createObjectURL(blob);
                    var $a  = $('<a>').attr({ href: url, download: 'trial_balance.' + ext }).appendTo('body');
                    $a[0].click();
                    setTimeout(function () { URL.revokeObjectURL(url); $a.remove(); }, 3000);
                },
                error : function () { showToastNotification('Export failed. Please try again.', 'error'); }
            });
        }
    }

    /** Wire up Flatpickr date pickers. */
    function _initDatePickers() {
        _fpFrom = flatpickr('#tbFromDisplay', {
            dateFormat  : 'Y-m-d',
            altInput    : true,
            altFormat   : _tbListFmt,
            defaultDate : _tbInitFrom,
            onChange    : function (dates, str) { _from = str; },
        });
        _fpTo = flatpickr('#tbToDisplay', {
            dateFormat  : 'Y-m-d',
            altInput    : true,
            altFormat   : _tbListFmt,
            defaultDate : _tbInitTo,
            onChange    : function (dates, str) { _to = str; },
        });
    }

    /** Initialise the page. */
    function _init() {
        _initDatePickers();
        $('#tbApplyBtn').on('click', _fetch);
        $(document).on('click', '.r2k-export-btn[data-format]', function () {
            _handleExport($(this).data('format'));
        });
        _fetch();
    }

    $(document).ready(_init);

}());
