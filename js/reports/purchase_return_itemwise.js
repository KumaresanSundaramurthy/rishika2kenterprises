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
     * @param {string} iso  YYYY-MM-DD
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
        var tbody  = document.getElementById('prTableBody');
        var tfoot  = document.getElementById('prTableFoot');
        var footer = document.getElementById('prTblFooter');
        if (!tbody) return;

        if (!rows || rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="12"><div class="rpt-empty">' +
                '<i class="bx bx-revision"></i>' +
                '<div class="rpt-empty-title">No returns for this period</div>' +
                '<div>Try changing the date range and applying again</div>' +
                '</div></td></tr>';
            if (tfoot) tfoot.classList.add('d-none');
            if (footer) footer.classList.add('d-none');
            return;
        }

        var html = '';
        var totQty = 0, totTaxable = 0, totTax = 0, totNet = 0;

        for (var i = 0; i < rows.length; i++) {
            var r = rows[i];
            var qty     = parseFloat(r.Quantity)      || 0;
            var taxable = parseFloat(r.TaxableAmount)  || 0;
            var taxAmt  = parseFloat(r.TaxAmount)      || 0;
            var net     = parseFloat(r.NetAmount)      || 0;
            var taxPct  = parseFloat(r.TaxPercentage)  || 0;
            totQty     += qty;
            totTaxable += taxable;
            totTax     += taxAmt;
            totNet     += net;

            html += '<tr>' +
                '<td>' + (i + 1) + '</td>' +
                '<td><span class="fw-semibold text-warning">' + _esc(r.DocNo) + '</span></td>' +
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

        tbody.innerHTML = html;

        if (tfoot) {
            document.getElementById('prFtQty').textContent     = _fmtQty(totQty, 2);
            document.getElementById('prFtTaxable').textContent = _fmt(totTaxable);
            document.getElementById('prFtTax').textContent     = _fmt(totTax);
            document.getElementById('prFtNet').textContent     = _fmt(totNet);
            tfoot.classList.remove('d-none');
        }
        if (footer) {
            footer.classList.remove('d-none');
            document.getElementById('prRowCount').textContent  = rows.length + ' line item' + (rows.length !== 1 ? 's' : '');
            document.getElementById('prFooterTax').textContent = _fmt(totTax);
            document.getElementById('prFooterNet').textContent = _fmt(totNet);
        }

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
        var elItems = document.getElementById('prStatItems');
        var elQty   = document.getElementById('prStatQty');
        var elTax   = document.getElementById('prStatTax');
        var elNet   = document.getElementById('prStatNet');
        if (elItems) elItems.textContent = rows.length.toLocaleString('en-IN');
        if (elQty)   elQty.textContent   = _fmtQty(totQty, 2);
        if (elTax)   elTax.textContent   = _fmt(totTax);
        if (elNet)   elNet.textContent   = _fmt(totNet);
    }

    /**
     * @returns {void}
     */
    function _fetch() {
        var from = (document.getElementById('prFrom') || {}).value || '';
        var to   = (document.getElementById('prTo')   || {}).value || '';
        var tbody = document.getElementById('prTableBody');
        if (!tbody) return;

        tbody.innerHTML = '<tr><td colspan="12" class="rpt-loading-cell">' +
            '<div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div>' +
            '</td></tr>';
        var tfoot = document.getElementById('prTableFoot');
        var footer = document.getElementById('prTblFooter');
        if (tfoot) tfoot.classList.add('d-none');
        if (footer) footer.classList.add('d-none');

        ajaxLoading(1);
        $.get('/reports/getPurchaseReturnItemwiseData', { from: from, to: to })
            .done(function (res) {
                if (res.Status !== 'Success') {
                    tbody.innerHTML = '<tr><td colspan="12" class="text-center text-danger py-4">' +
                        _esc(res.Message || 'Failed to load data') + '</td></tr>';
                    return;
                }
                _rows = res.rows || [];
                _render(_rows);
            })
            .fail(function () {
                tbody.innerHTML = '<tr><td colspan="12" class="text-center text-danger py-4">Request failed. Please try again.</td></tr>';
            })
            .always(function () { ajaxLoading(0); });
    }

    /**
     * @returns {void}
     */
    function _initDatePickers() {
        var fromHidden  = document.getElementById('prFrom');
        var toHidden    = document.getElementById('prTo');
        var fromDisplay = document.getElementById('prFromDisplay');
        var toDisplay   = document.getElementById('prToDisplay');
        if (!fromDisplay || !toDisplay || typeof flatpickr === 'undefined') return;

        var initFrom = (typeof _prInitFrom !== 'undefined') ? _prInitFrom : '';
        var initTo   = (typeof _prInitTo   !== 'undefined') ? _prInitTo   : '';

        flatpickr(fromDisplay, {
            dateFormat:  'Y-m-d',
            defaultDate: initFrom || null,
            static:      true,
            position:    'below left',
            onChange: function (sel, dateStr) {
                if (fromHidden) fromHidden.value = dateStr;
                var d = new Date(dateStr);
                fromDisplay.value = isNaN(d.getTime()) ? dateStr :
                    d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
            }
        });
        flatpickr(toDisplay, {
            dateFormat:  'Y-m-d',
            defaultDate: initTo || null,
            static:      true,
            position:    'below left',
            onChange: function (sel, dateStr) {
                if (toHidden) toHidden.value = dateStr;
                var d = new Date(dateStr);
                toDisplay.value = isNaN(d.getTime()) ? dateStr :
                    d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
            }
        });

        if (initFrom) {
            var df = new Date(initFrom);
            fromDisplay.value = isNaN(df.getTime()) ? initFrom :
                df.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        }
        if (initTo) {
            var dt = new Date(initTo);
            toDisplay.value = isNaN(dt.getTime()) ? initTo :
                dt.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        }
    }

    /**
     * @returns {void}
     */
    function _init() {
        _initDatePickers();

        var applyBtn = document.getElementById('prApplyBtn');
        if (applyBtn) {
            applyBtn.addEventListener('click', function () { _fetch(); });
        }

        _fetch();
    }

    $(document).ready(_init);
})();
