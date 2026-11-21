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
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    /**
     * @param {number|string} n
     * @returns {string}
     */
    function _fmt(n) {
        var v = parseFloat(n);
        if (isNaN(v)) return _curr + ' 0.' + '0'.repeat(_dec);
        return _curr + ' ' + v.toLocaleString('en-IN', { minimumFractionDigits: _dec, maximumFractionDigits: _dec });
    }

    /**
     * @param {number|string} n
     * @param {number} dec
     * @returns {string}
     */
    function _fmtQty(n, dec) {
        var v = parseFloat(n);
        if (isNaN(v)) return '0';
        var d = dec != null ? dec : 2;
        return v.toLocaleString('en-IN', { minimumFractionDigits: 0, maximumFractionDigits: d });
    }

    /**
     * @param {string} iso
     * @returns {string}
     */
    function _fmtDate(iso) {
        if (!iso) return '';
        var parts = iso.split('-');
        if (parts.length !== 3) return iso;
        var d = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
        if (isNaN(d.getTime())) return iso;
        return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    /** @type {Array<Object>} */
    var _rows = [];

    /**
     * @param {Array<Object>} rows
     * @returns {void}
     */
    function _render(rows) {
        var $tbody  = $('#piTableBody');
        var $tfoot  = $('#piTableFoot');
        var $footer = $('#piTblFooter');
        if (!$tbody.length) return;

        if (!rows || rows.length === 0) {
            $tbody.html('<tr><td colspan="12"><div class="rpt-empty">' +
                '<i class="bx bx-purchase-tag-alt"></i>' +
                '<div class="rpt-empty-title">No data for this period</div>' +
                '<div>Try changing the date range and applying again</div>' +
                '</div></td></tr>');
            $tfoot.addClass('d-none');
            $footer.addClass('d-none');
            return;
        }

        var html = '';
        var totQty = 0, totTaxable = 0, totTax = 0, totNet = 0;

        for (var i = 0; i < rows.length; i++) {
            var r       = rows[i];
            var qty     = parseFloat(r.Quantity)     || 0;
            var taxable = parseFloat(r.TaxableAmount) || 0;
            var taxAmt  = parseFloat(r.TaxAmount)     || 0;
            var net     = parseFloat(r.NetAmount)     || 0;
            var taxPct  = parseFloat(r.TaxPercentage) || 0;
            totQty     += qty;
            totTaxable += taxable;
            totTax     += taxAmt;
            totNet     += net;

            html += '<tr>' +
                '<td>' + (i + 1) + '</td>' +
                '<td><span class="fw-semibold text-orange">' + _esc(r.DocNo) + '</span></td>' +
                '<td>' + _esc(_fmtDate(r.TransDate)) + '</td>' +
                '<td>' + _esc(r.PartyName || '—') + '</td>' +
                '<td>' + _esc(r.ItemName) + '</td>' +
                '<td class="rpt-col-num">' + _fmtQty(qty, 2) + '</td>' +
                '<td>' + _esc(r.UnitName || '') + '</td>' +
                '<td class="rpt-col-num">' + _fmt(r.UnitPrice) + '</td>' +
                '<td class="rpt-col-num">' + _fmt(taxable) + '</td>' +
                '<td class="rpt-col-num rpt-num-gray">' + (taxPct > 0 ? taxPct + '%' : '—') + '</td>' +
                '<td class="rpt-col-num rpt-num-yellow">' + _fmt(taxAmt) + '</td>' +
                '<td class="rpt-col-num rpt-num-orange">' + _fmt(net) + '</td>' +
                '</tr>';
        }

        $tbody.html(html);
        $('#piFtQty').text(_fmtQty(totQty, 2));
        $('#piFtTaxable').text(_fmt(totTaxable));
        $('#piFtTax').text(_fmt(totTax));
        $('#piFtNet').text(_fmt(totNet));
        $tfoot.removeClass('d-none');
        $('#piRowCount').text(rows.length + ' line item' + (rows.length !== 1 ? 's' : ''));
        $('#piFooterTax').text(_fmt(totTax));
        $('#piFooterNet').text(_fmt(totNet));
        $footer.removeClass('d-none');
        _updateStats(rows, totQty, totTax, totNet);
    }

    /**
     * @param {Array<Object>} rows
     * @param {number} totQty
     * @param {number} totTax
     * @param {number} totNet
     * @returns {void}
     */
    function _updateStats(rows, totQty, totTax, totNet) {
        $('#piStatItems').text(rows.length.toLocaleString('en-IN'));
        $('#piStatQty').text(_fmtQty(totQty, 2));
        $('#piStatTax').text(_fmt(totTax));
        $('#piStatNet').text(_fmt(totNet));
    }

    /**
     * @returns {void}
     */
    function _fetch() {
        var from   = $('#piFrom').val() || '';
        var to     = $('#piTo').val()   || '';
        var $tbody = $('#piTableBody');
        if (!$tbody.length) return;

        $tbody.html('<tr><td colspan="12" class="rpt-loading-cell">' +
            '<div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div>' +
            '</td></tr>');
        $('#piTableFoot').addClass('d-none');
        $('#piTblFooter').addClass('d-none');

        ajaxLoading(1);
        $.ajax({
            type    : 'GET',
            url     : global_services_url + 'api/reports/getPurchaseItemwiseData',
            headers : { Authorization: 'Bearer ' + JwtToken },
            data    : { orgId: parseInt(JwtData.Org.OrgUID, 10), from: from, to: to },
            success : function (res) {
                if (res.Error) {
                    $tbody.html('<tr><td colspan="12" class="text-center text-danger py-4">' +
                        _esc(res.Message || 'Failed to load data') + '</td></tr>');
                    return;
                }
                _rows = res.rows || [];
                _render(_rows);
            },
            error   : function () {
                $tbody.html('<tr><td colspan="12" class="text-center text-danger py-4">Request failed. Please try again.</td></tr>');
            }
        }).always(function () { ajaxLoading(0); });
    }

    /**
     * @param {string} format
     * @returns {void}
     */
    function _handleExport(format) {
        var from    = $('#piFrom').val() || '';
        var to      = $('#piTo').val()   || '';
        var type    = format === 'pdf' ? 'Pdf' : (format === 'print' ? 'Print' : (format === 'excel' ? 'Excel' : 'CSV'));
        var payload = { orgId: parseInt(JwtData.Org.OrgUID, 10), from: from, to: to, report: 'purchase', Type: type };

        if (type === 'Print') {
            $.ajax({
                url         : global_services_url + 'api/reports/exportItemwise',
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
                url         : global_services_url + 'api/reports/exportItemwise',
                method      : 'POST',
                contentType : 'application/json',
                headers     : { Authorization: 'Bearer ' + JwtToken },
                data        : JSON.stringify(payload),
                xhrFields   : { responseType: 'blob' },
                success : function (blob) {
                    var url = URL.createObjectURL(blob);
                    var $a  = $('<a>').attr({ href: url, download: 'purchase_itemwise.' + ext }).appendTo('body');
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
        var $fromHidden  = $('#piFrom');
        var $toHidden    = $('#piTo');
        var $fromDisplay = $('#piFromDisplay');
        var $toDisplay   = $('#piToDisplay');
        if (!$fromDisplay.length || !$toDisplay.length || typeof flatpickr === 'undefined') return;

        var initFrom = (typeof _piInitFrom !== 'undefined') ? _piInitFrom : '';
        var initTo   = (typeof _piInitTo   !== 'undefined') ? _piInitTo   : '';

        flatpickr($fromDisplay[0], {
            dateFormat:  'Y-m-d',
            defaultDate: initFrom || null,
            static:      true,
            position:    'below left',
            onChange: function (sel, dateStr) {
                $fromHidden.val(dateStr);
                var d = new Date(dateStr);
                $fromDisplay.val(isNaN(d.getTime()) ? dateStr : d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }));
            }
        });
        flatpickr($toDisplay[0], {
            dateFormat:  'Y-m-d',
            defaultDate: initTo || null,
            static:      true,
            position:    'below left',
            onChange: function (sel, dateStr) {
                $toHidden.val(dateStr);
                var d = new Date(dateStr);
                $toDisplay.val(isNaN(d.getTime()) ? dateStr : d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }));
            }
        });

        if (initFrom) {
            var df = new Date(initFrom);
            $fromDisplay.val(isNaN(df.getTime()) ? initFrom : df.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }));
        }
        if (initTo) {
            var dt = new Date(initTo);
            $toDisplay.val(isNaN(dt.getTime()) ? initTo : dt.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }));
        }
    }

    /**
     * @returns {void}
     */
    function _init() {
        _initDatePickers();
        $('#piApplyBtn').on('click', _fetch);
        $('#piRefreshBtn').on('click', _fetch);
        $(document).on('click', '.r2k-export-btn[data-format]', function () {
            _handleExport($(this).data('format'));
        });
        _fetch();
    }

    $(document).ready(_init);
})();
