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
     * @param {number} n
     * @returns {string}
     */
    function _fmtQty(n) {
        return parseFloat(n || 0).toFixed(2);
    }

    /**
     * @param {string} str
     * @returns {string}
     */
    function _esc(str) {
        return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    /**
     * @param {Array} rows
     * @returns {void}
     */
    function _render(rows) {
        var tbody  = document.getElementById('iwpTableBody');
        var tfoot  = document.getElementById('iwpTableFoot');
        var footer = document.getElementById('iwpTblFooter');

        if (!rows || rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8"><div class="rpt-empty"><i class="bx bx-info-circle"></i><div class="rpt-empty-title">No purchases found</div><div style="font-size:.82rem">Try a different date range</div></div></td></tr>';
            tfoot.classList.add('d-none');
            footer.classList.add('d-none');
            _updateStats(0, 0, 0, 0);
            return;
        }

        var html = '';
        var totBills = 0, totQty = 0, totCost = 0;

        rows.forEach(function (r, i) {
            var bills = parseInt(r.BillCount || 0, 10);
            var qty   = parseFloat(r.TotalQty  || 0);
            var cost  = parseFloat(r.TotalCost || 0);
            var avg   = parseFloat(r.AvgCost   || 0);
            totBills += bills;
            totQty   += qty;
            totCost  += cost;

            html += '<tr>';
            html += '<td>' + (i + 1) + '</td>';
            html += '<td>' + _esc(r.ItemName) + (r.UnitName ? ' <span style="font-size:.72rem;color:#94a3b8">/' + _esc(r.UnitName) + '</span>' : '') + '</td>';
            html += '<td>' + (r.SKU ? _esc(r.SKU) : '<span style="color:#94a3b8">—</span>') + '</td>';
            html += '<td>' + (r.CategoryName ? '<span class="iwp-cat-chip">' + _esc(r.CategoryName) + '</span>' : '<span style="color:#94a3b8">—</span>') + '</td>';
            html += '<td class="rpt-col-num">' + bills + '</td>';
            html += '<td class="rpt-col-num rpt-num-blue">' + _fmtQty(qty) + '</td>';
            html += '<td class="rpt-col-num">' + _fmt(avg) + '</td>';
            html += '<td class="rpt-col-num rpt-num-orange">' + _fmt(cost) + '</td>';
            html += '</tr>';
        });

        tbody.innerHTML = html;

        document.getElementById('iwpFtBills').textContent = totBills;
        document.getElementById('iwpFtQty').textContent   = _fmtQty(totQty);
        document.getElementById('iwpFtCost').textContent  = _fmt(totCost);
        tfoot.classList.remove('d-none');

        document.getElementById('iwpRowCount').textContent    = rows.length + ' product' + (rows.length !== 1 ? 's' : '');
        document.getElementById('iwpFooterCost').textContent  = _fmt(totCost);
        footer.classList.remove('d-none');

        _updateStats(rows.length, totBills, totQty, totCost);
    }

    /**
     * @param {number} products
     * @param {number} bills
     * @param {number} qty
     * @param {number} cost
     * @returns {void}
     */
    function _updateStats(products, bills, qty, cost) {
        document.getElementById('iwpStatProducts').textContent = products;
        document.getElementById('iwpStatBills').textContent    = bills;
        document.getElementById('iwpStatQty').textContent      = _fmtQty(qty);
        document.getElementById('iwpStatCost').textContent     = _fmt(cost);
    }

    /**
     * @returns {void}
     */
    function _fetch() {
        var from  = document.getElementById('iwpFrom').value;
        var to    = document.getElementById('iwpTo').value;
        var tbody = document.getElementById('iwpTableBody');

        tbody.innerHTML = '<tr><td colspan="8" class="rpt-loading-cell"><div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div></td></tr>';
        document.getElementById('iwpTableFoot').classList.add('d-none');
        document.getElementById('iwpTblFooter').classList.add('d-none');

        $.ajax({
            url: '/reports/getItemWisePurchaseData',
            type: 'GET',
            dataType: 'json',
            data: { from: from, to: to },
            success: function (res) {
                if (res && res.Status === 'Success') {
                    _render(res.rows || []);
                } else {
                    tbody.innerHTML = '<tr><td colspan="8" class="text-center p-4 text-danger">' + _esc((res && res.Message) ? res.Message : 'Failed to load data') + '</td></tr>';
                }
            },
            error: function () {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center p-4 text-danger">Network error. Please try again.</td></tr>';
            }
        });
    }

    /**
     * @returns {void}
     */
    function _initDatePickers() {
        var fromInput   = document.getElementById('iwpFrom');
        var toInput     = document.getElementById('iwpTo');
        var fromDisplay = document.getElementById('iwpFromDisplay');
        var toDisplay   = document.getElementById('iwpToDisplay');

        flatpickr(fromDisplay, {
            dateFormat: 'Y-m-d',
            defaultDate: fromInput.value || _iwpInitFrom,
            onChange: function (sel, str) {
                fromInput.value = str;
                fromDisplay.value = str ? new Date(str + 'T00:00:00').toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '';
            }
        });

        flatpickr(toDisplay, {
            dateFormat: 'Y-m-d',
            defaultDate: toInput.value || _iwpInitTo,
            onChange: function (sel, str) {
                toInput.value = str;
                toDisplay.value = str ? new Date(str + 'T00:00:00').toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '';
            }
        });

        if (fromInput.value) {
            fromDisplay.value = new Date(fromInput.value + 'T00:00:00').toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        }
        if (toInput.value) {
            toDisplay.value = new Date(toInput.value + 'T00:00:00').toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        }
    }

    /**
     * @returns {void}
     */
    function _init() {
        _initDatePickers();
        document.getElementById('iwpApplyBtn').addEventListener('click', function () {
            _fetch();
        });
        _fetch();
    }

    $(document).ready(_init);
}());
