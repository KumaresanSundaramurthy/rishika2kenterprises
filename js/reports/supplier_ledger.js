(function () {
    'use strict';

    var _sym = (genSettings && genSettings.CurrenySymbol) ? genSettings.CurrenySymbol : '₹';
    var _dec = 2;
    var _vendorUID = 0;

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
     * @param {string} isoDate
     * @returns {string}
     */
    function _fmtDate(isoDate) {
        if (!isoDate) { return '—'; }
        var d = new Date(isoDate + 'T00:00:00');
        if (isNaN(d.getTime())) { return isoDate; }
        var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        var fmt = _slListFmt || 'd M Y';
        return fmt
            .replace('d', ('0' + d.getDate()).slice(-2))
            .replace('M', months[d.getMonth()])
            .replace('m', ('0' + (d.getMonth() + 1)).slice(-2))
            .replace('Y', d.getFullYear());
    }

    /**
     * @param {string} txType
     * @returns {string}
     */
    function _typeChip(txType) {
        var map = {
            'Purchase Bill':    ['sl-type-bill',    'bx-receipt',    'Purchase Bill'],
            'Purchase Return':  ['sl-type-return',  'bx-arrow-back', 'Purchase Return'],
            'Payment Made':     ['sl-type-payment', 'bx-money',      'Payment Made'],
            'Opening Balance':  ['',                'bx-wallet',     'Opening Balance']
        };
        var info   = map[txType] || ['', 'bx-circle', _esc(txType)];
        var chipCls = info[0] ? 'sl-type-chip ' + info[0] : '';
        return chipCls
            ? '<span class="' + chipCls + '"><i class="bx ' + info[1] + '"></i>' + _esc(info[2]) + '</span>'
            : '<span style="font-size:var(--r2k-text-78);color:#64748b">' + _esc(info[2]) + '</span>';
    }

    /*
     * Debit  = Purchase Return + Payment Made (reduces what we owe)
     * Credit = Purchase Bill (we owe vendor)
     * Balance (positive) = we still owe vendor
     */

    /**
     * @param {Array}  rows
     * @param {{debit:number,credit:number}} opening
     * @returns {void}
     */
    function _render(rows, opening) {
        var $tbody    = $('#slTableBody');
        var $tfoot    = $('#slTableFoot');
        var $footer   = $('#slTblFooter');
        var $statsRow = $('#slStatsRow');

        var obDebit  = parseFloat(opening.debit  || 0);
        var obCredit = parseFloat(opening.credit || 0);
        var runBal   = obCredit - obDebit;

        var html   = '';
        var rowNum = 0;

        if (obDebit > 0.005 || obCredit > 0.005) {
            rowNum++;
            html += '<tr class="sl-ob-row">';
            html += '<td>' + rowNum + '</td>';
            html += '<td>—</td>';
            html += '<td>' + _typeChip('Opening Balance') + '</td>';
            html += '<td><em>Opening Balance</em></td>';
            html += '<td class="rpt-col-num">' + (obDebit  > 0.005 ? _fmt(obDebit)  : '—') + '</td>';
            html += '<td class="rpt-col-num">' + (obCredit > 0.005 ? _fmt(obCredit) : '—') + '</td>';
            html += '<td class="rpt-col-num ' + (runBal >= 0 ? 'rpt-num-orange' : 'rpt-num-green') + '">' + _fmt(Math.abs(runBal)) + (runBal < 0 ? ' Dr' : ' Cr') + '</td>';
            html += '</tr>';
        }

        if (!rows || rows.length === 0) {
            if (rowNum === 0) {
                $tbody.html('<tr><td colspan="7"><div class="rpt-empty"><i class="bx bx-info-circle"></i><div class="rpt-empty-title">No transactions found</div><div style="font-size:var(--r2k-text-82)">Try a different date range</div></div></td></tr>');
                $tfoot.addClass('d-none');
                $footer.addClass('d-none');
                $statsRow.addClass('d-none');
                _updateStats(obDebit, obCredit, 0, 0, runBal);
                return;
            }
        }

        var totDebit = 0, totCredit = 0;

        (rows || []).forEach(function (r) {
            rowNum++;
            var dr = parseFloat(r.Debit  || 0);
            var cr = parseFloat(r.Credit || 0);
            runBal   += cr - dr;
            totDebit  += dr;
            totCredit += cr;

            html += '<tr>';
            html += '<td>' + rowNum + '</td>';
            html += '<td>' + _fmtDate(r.TxDate) + '</td>';
            html += '<td>' + _typeChip(r.TxType) + '</td>';
            html += '<td>' + _esc(r.RefNo || '—') + '</td>';
            html += '<td class="rpt-col-num">' + (dr > 0.005 ? _fmt(dr) : '—') + '</td>';
            html += '<td class="rpt-col-num">' + (cr > 0.005 ? _fmt(cr) : '—') + '</td>';
            html += '<td class="rpt-col-num ' + (runBal >= 0 ? 'rpt-num-orange' : 'rpt-num-green') + '">' + _fmt(Math.abs(runBal)) + (runBal < 0 ? ' Dr' : ' Cr') + '</td>';
            html += '</tr>';
        });

        $tbody.html(html);
        $('#slFtDebit').text(_fmt(totDebit));
        $('#slFtCredit').text(_fmt(totCredit));
        $('#slFtBalance').text(_fmt(Math.abs(runBal)) + (runBal < 0 ? ' Dr' : ' Cr'));
        $tfoot.removeClass('d-none');
        $('#slRowCount').text((rows ? rows.length : 0) + ' transaction' + ((rows && rows.length !== 1) ? 's' : ''));
        $('#slFooterDebit').text(_fmt(totDebit));
        $('#slFooterBalance').text(_fmt(Math.abs(runBal)) + (runBal < 0 ? ' Dr' : ' Cr'));
        $footer.removeClass('d-none');
        $statsRow.removeClass('d-none');
        _updateStats(obDebit, obCredit, totDebit, totCredit, runBal);
    }

    /**
     * @param {number} obDebit
     * @param {number} obCredit
     * @param {number} totDebit
     * @param {number} totCredit
     * @param {number} closing
     * @returns {void}
     */
    function _updateStats(obDebit, obCredit, totDebit, totCredit, closing) {
        var obNet  = obCredit - obDebit;
        var obAbs  = Math.abs(obNet);
        var obSide = obNet >= 0 ? ' Cr' : ' Dr';
        $('#slStatOpening').text(_fmt(obAbs) + obSide);
        $('#slStatCredit').text(_fmt(totCredit));
        $('#slStatDebit').text(_fmt(totDebit));
        var closingAbs  = Math.abs(closing);
        var closingSide = closing >= 0 ? ' Cr' : ' Dr';
        $('#slStatClosing').text(_fmt(closingAbs) + closingSide);
    }

    /**
     * @returns {void}
     */
    function _fetch() {
        if (_vendorUID <= 0) {
            showToastNotification('Please select a supplier.', 'error');
            return;
        }

        var from = $('#slFrom').val();
        var to   = $('#slTo').val();

        if (!from || !to) {
            showToastNotification('Please select a date range.', 'error');
            return;
        }

        var $tbody = $('#slTableBody');
        $tbody.html('<tr><td colspan="7" class="rpt-loading-cell"><div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div></td></tr>');
        $('#slTableFoot').addClass('d-none');
        $('#slTblFooter').addClass('d-none');

        $.ajax({
            url     : global_services_url + 'api/reports/getSupplierLedgerData',
            type    : 'GET',
            dataType: 'json',
            headers : { Authorization: 'Bearer ' + JwtToken },
            data    : { orgId: parseInt(JwtData.Org.OrgUID, 10), vendorUID: _vendorUID, from: from, to: to },
            success : function (res) {
                if (!res || res.Error) {
                    $tbody.html('<tr><td colspan="7" class="text-center p-4 text-danger">' + _esc((res && res.Message) ? res.Message : 'Failed to load data') + '</td></tr>');
                    return;
                }
                var opening = res.opening || { debit: 0, credit: 0 };
                _render(res.rows || [], opening);
            },
            error   : function () {
                $tbody.html('<tr><td colspan="7" class="text-center p-4 text-danger">Network error. Please try again.</td></tr>');
            }
        });
    }

    /**
     * @param {string} format
     * @returns {void}
     */
    function _handleExport(format) {
        if (_vendorUID <= 0) { showToastNotification('Please select a supplier first.', 'error'); return; }
        var from    = $('#slFrom').val() || '';
        var to      = $('#slTo').val()   || '';
        var type    = format === 'pdf' ? 'Pdf' : (format === 'print' ? 'Print' : (format === 'excel' ? 'Excel' : 'CSV'));
        var payload = { orgId: parseInt(JwtData.Org.OrgUID, 10), vendorUID: _vendorUID, from: from, to: to, Type: type };

        if (type === 'Print') {
            $.ajax({
                url         : global_services_url + 'api/reports/exportSupplierLedger',
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
                url         : global_services_url + 'api/reports/exportSupplierLedger',
                method      : 'POST',
                contentType : 'application/json',
                headers     : { Authorization: 'Bearer ' + JwtToken },
                data        : JSON.stringify(payload),
                xhrFields   : { responseType: 'blob' },
                success : function (blob) {
                    var url = URL.createObjectURL(blob);
                    var $a  = $('<a>').attr({ href: url, download: 'supplier_ledger.' + ext }).appendTo('body');
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
        var $fromHidden  = $('#slFrom');
        var $toHidden    = $('#slTo');
        var $fromDisplay = $('#slFromDisplay');
        var $toDisplay   = $('#slToDisplay');

        flatpickr($fromDisplay[0], {
            dateFormat:  'Y-m-d',
            altInput:    false,
            defaultDate: $fromHidden.val() || _slInitFrom,
            onChange: function (sel, str) {
                $fromHidden.val(str);
                $fromDisplay.val(str ? new Date(str + 'T00:00:00').toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '');
            }
        });

        flatpickr($toDisplay[0], {
            dateFormat:  'Y-m-d',
            altInput:    false,
            defaultDate: $toHidden.val() || _slInitTo,
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
    function _initSelect2() {
        $('#slSupplier').select2({
            placeholder:        'Search supplier…',
            allowClear:         true,
            minimumInputLength: 1,
            ajax: {
                url     : global_services_url + 'api/transactions/searchVendors',
                method  : 'GET',
                headers : { Authorization: 'Bearer ' + JwtToken },
                dataType: 'json',
                delay:    250,
                data: function (params) {
                    return { term: params.term, orgId: parseInt(JwtData.Org.OrgUID, 10) };
                },
                processResults: function (data) {
                    return { results: (data.Data || data).Lists || [] };
                }
            }
        }).on('change', function () {
            _vendorUID = parseInt($(this).val() || '0', 10);
        });
    }

    /**
     * @returns {void}
     */
    function _init() {
        _initDatePickers();
        _initSelect2();
        $('#slApplyBtn').on('click', _fetch);
        $('#slRefreshBtn').on('click', _fetch);
        $(document).on('click', '.r2k-export-btn[data-format]', function () {
            _handleExport($(this).data('format'));
        });
    }

    $(document).ready(_init);
}());
