/**
 * Balance Sheet Report
 * Computes closing balance per ledger (opening + all movements up to asOf).
 * Groups ledgers into Assets, Liabilities, and Equity (net of Income−Expense).
 *
 * Closing balance formula (mirrors Accounting controller _calcBalance):
 *   net = OpeningBalance ± (TotalDebit − TotalCredit) based on OpeningBalanceType
 *   if net < 0 → flip the balance type
 *
 * Assets  (Debit-side closing) : Asset, Bank, Cash, Customer (receivable)
 * Liabilities (Credit-side closing) : Liability, Vendor (payable)
 * Equity : Assets − Liabilities (simple double-entry identity)
 */

(function () {
    'use strict';

    var _asOf   = _bsInitAsOf;
    var _fpAsOf = null;

    var _cur = (typeof genSettings !== 'undefined' && genSettings.CurrenySymbol) ? genSettings.CurrenySymbol : '₹';
    var _dec = (typeof genSettings !== 'undefined' && genSettings.DecimalPoints) ? parseInt(genSettings.DecimalPoints, 10) : 2;

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
     * Compute the closing balance from opening balance + period movements.
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
     * Build one section column (Assets or Liabilities).
     *
     * @param {string} title
     * @param {Array}  groups   [{label, rows, subtotal}]
     * @param {string} valCls
     * @param {number} grandTotal
     * @param {string} grandLabel
     * @returns {string}
     */
    function _buildCol(title, groups, valCls, grandTotal, grandLabel) {
        var html = '<div>';
        html += '<div class="fin-section-title">' + title + '</div>';

        groups.forEach(function (g) {
            if (g.rows.length === 0) return;
            if (g.label) {
                html += '<div style="font-size:.72rem;font-weight:700;color:#94a3b8;margin:10px 0 4px;text-transform:uppercase;letter-spacing:.05em;">'
                    + g.label + '</div>';
            }
            g.rows.forEach(function (r) {
                var val = r._closingBal || 0;
                html += '<div class="fin-row">'
                    + '<span class="fin-row-label">' + (r.LedgerName || '—') + '</span>'
                    + '<span class="fin-row-val">'
                    + (val === 0 ? '<span class="fin-zero">—</span>' : _fmt(val))
                    + '</span></div>';
            });
            if (g.showSubtotal) {
                html += '<div class="fin-total-row" style="margin-top:2px;">'
                    + '<span class="fin-total-label" style="font-weight:600;font-size:.78rem;">'
                    + (g.subtotalLabel || 'Subtotal') + '</span>'
                    + '<span class="fin-total-val ' + valCls + '">' + _fmt(g.subtotal) + '</span></div>';
            }
        });

        html += '<div class="fin-total-row" style="border-top-width:2px;">'
            + '<span class="fin-total-label">' + grandLabel + '</span>'
            + '<span class="fin-total-val ' + valCls + '">' + _fmt(grandTotal) + '</span>'
            + '</div></div>';
        return html;
    }

    /**
     * @param {Array} rows
     */
    function _render(rows) {
        var content = document.getElementById('bsContent');

        if (!rows.length) {
            content.innerHTML = '<div class="fin-empty"><i class="bx bx-equalizer"></i>'
                + '<div class="fin-empty-title">No ledger accounts found</div>'
                + '<div>Set up accounts in Chart of Accounts first</div></div>';
            _updateStats(0, 0, 0, true);
            return;
        }

        // Compute closing balance for every ledger
        rows.forEach(function (r) {
            var cb = _calcBalance(r.OpeningBalance, r.OpeningBalanceType, r.TotalDebit, r.TotalCredit);
            r._closingBal  = cb.bal;
            r._closingType = cb.type;
        });

        // Separate into groups
        var fixedAssets   = rows.filter(function (r) { return r.LedgerType === 'Asset'; });
        var bankLedgers   = rows.filter(function (r) { return r.LedgerType === 'Bank'; });
        var cashLedgers   = rows.filter(function (r) { return r.LedgerType === 'Cash'; });
        var receivables   = rows.filter(function (r) { return r.LedgerType === 'Customer' && r._closingType === 'Debit'; });
        var liabilities   = rows.filter(function (r) { return r.LedgerType === 'Liability'; });
        var payables      = rows.filter(function (r) { return r.LedgerType === 'Vendor'  && r._closingType === 'Credit'; });
        var incomeRows    = rows.filter(function (r) { return r.LedgerType === 'Income'; });
        var expenseRows   = rows.filter(function (r) { return r.LedgerType === 'Expense'; });

        // Sum each group (only include rows where closing type matches expected side)
        function _sumDebit(arr) {
            return arr.reduce(function (s, r) { return s + (r._closingType === 'Debit' ? r._closingBal : 0); }, 0);
        }
        function _sumCredit(arr) {
            return arr.reduce(function (s, r) { return s + (r._closingType === 'Credit' ? r._closingBal : 0); }, 0);
        }

        var assetTotal    = _sumDebit(fixedAssets) + _sumDebit(bankLedgers) + _sumDebit(cashLedgers) + _sumDebit(receivables);
        var liabTotal     = _sumCredit(liabilities) + _sumCredit(payables);

        // Net P&L feeds into equity
        var totalIncome   = incomeRows.reduce(function (s, r) {
            var net = parseFloat(r.TotalCredit) - parseFloat(r.TotalDebit);
            return s + (net > 0 ? net : 0);
        }, 0);
        var totalExpense  = expenseRows.reduce(function (s, r) {
            var net = parseFloat(r.TotalDebit) - parseFloat(r.TotalCredit);
            return s + (net > 0 ? net : 0);
        }, 0);
        var retainedEarnings = totalIncome - totalExpense;

        var equity        = assetTotal - liabTotal;
        var isBalanced    = Math.abs(assetTotal - (liabTotal + retainedEarnings)) < 0.01;

        // Build HTML
        var assetGroups = [
            { label: 'Fixed Assets',   rows: fixedAssets,  subtotal: _sumDebit(fixedAssets),  showSubtotal: fixedAssets.length > 1,  subtotalLabel: 'Subtotal Assets'  },
            { label: 'Bank Accounts',  rows: bankLedgers,  subtotal: _sumDebit(bankLedgers),   showSubtotal: bankLedgers.length > 1,  subtotalLabel: 'Subtotal Bank'    },
            { label: 'Cash',           rows: cashLedgers,  subtotal: _sumDebit(cashLedgers),   showSubtotal: cashLedgers.length > 1,  subtotalLabel: 'Subtotal Cash'    },
            { label: 'Receivables',    rows: receivables,  subtotal: _sumDebit(receivables),   showSubtotal: receivables.length > 1,  subtotalLabel: 'Subtotal Rec.'    },
        ];

        var liabGroups = [
            { label: 'Liabilities',    rows: liabilities,  subtotal: _sumCredit(liabilities),  showSubtotal: liabilities.length > 1,  subtotalLabel: 'Subtotal Liab.'   },
            { label: 'Payables',       rows: payables,     subtotal: _sumCredit(payables),     showSubtotal: payables.length > 1,     subtotalLabel: 'Subtotal Pay.'    },
        ];

        var leftCol  = _buildCol('ASSETS', assetGroups, 'rpt-num-blue', assetTotal, 'Total Assets');
        var isEqPos  = equity >= 0;
        var rightCol = '<div>'
            + '<div class="fin-section-title">LIABILITIES</div>';

        liabGroups.forEach(function (g) {
            if (g.rows.length === 0) return;
            if (g.label) rightCol += '<div style="font-size:.72rem;font-weight:700;color:#94a3b8;margin:10px 0 4px;text-transform:uppercase;letter-spacing:.05em;">' + g.label + '</div>';
            g.rows.forEach(function (r) {
                rightCol += '<div class="fin-row">'
                    + '<span class="fin-row-label">' + (r.LedgerName || '—') + '</span>'
                    + '<span class="fin-row-val">' + (r._closingBal ? _fmt(r._closingBal) : '<span class="fin-zero">—</span>') + '</span></div>';
            });
        });

        rightCol += '<div class="fin-total-row"><span class="fin-total-label">Total Liabilities</span>'
            + '<span class="fin-total-val rpt-num-orange">' + _fmt(liabTotal) + '</span></div>';

        // Equity section
        rightCol += '<div class="fin-section-gap"></div>'
            + '<div class="fin-section-title">EQUITY</div>'
            + '<div class="fin-row"><span class="fin-row-label">Retained Earnings (Net P&L)</span>'
            + '<span class="fin-row-val ' + (retainedEarnings >= 0 ? 'rpt-num-green' : 'rpt-num-red') + '">'
            + _fmt(retainedEarnings) + '</span></div>'
            + '<div class="fin-equity-row ' + (isEqPos ? '' : 'fin-equity-neg') + '">'
            + '<span class="fin-equity-label">Net Equity (Assets − Liabilities)</span>'
            + '<span class="fin-equity-val">' + _fmt(Math.abs(equity)) + (isEqPos ? '' : ' (Deficit)') + '</span></div>'
            + '</div>';

        var html = '<div class="fin-wrap"><div class="fin-cols">' + leftCol + rightCol + '</div>';

        // Grand check bar
        html += '<div class="fin-grand-total-bar">'
            + '<span class="fin-grand-label">Total Assets</span>'
            + '<div class="d-flex align-items-center gap-3">'
            + '<span id="bsBalancedChip" class="' + (isBalanced ? 'fin-check-balanced' : '') + '"></span>'
            + '<span class="fin-grand-val">' + _fmt(assetTotal) + '</span>'
            + '</div></div></div>';

        content.innerHTML = html;

        var chipEl = document.getElementById('bsBalancedChip');
        if (chipEl) {
            chipEl.className = isBalanced ? 'fin-check-ok' : 'fin-check-off';
            chipEl.innerHTML = isBalanced
                ? '<i class="bx bx-check"></i>Balanced'
                : '<i class="bx bx-x"></i>Off by ' + _fmt(Math.abs(assetTotal - liabTotal - retainedEarnings));
        }

        _updateStats(assetTotal, liabTotal, equity, isBalanced);
        history.replaceState(null, '', '?asof=' + _asOf);
    }

    /**
     * @param {number} assets
     * @param {number} liab
     * @param {number} equity
     * @param {boolean} balanced
     */
    function _updateStats(assets, liab, equity, balanced) {
        document.getElementById('bsStatAssets').textContent = _fmt(assets);
        document.getElementById('bsStatLiab').textContent   = _fmt(liab);
        document.getElementById('bsStatEquity').textContent = _fmt(equity);
        document.getElementById('bsStatCheck').innerHTML    = balanced
            ? '<span class="fin-check-ok"><i class="bx bx-check"></i>Balanced</span>'
            : '<span class="fin-check-off"><i class="bx bx-x"></i>Not Balanced</span>';
    }

    /** Fetch data from server and render. */
    function _fetch() {
        var content = document.getElementById('bsContent');
        content.innerHTML = '<div class="fin-loading"><span class="spinner-border spinner-border-sm"></span>Loading…</div>';

        ajaxLoading(1);
        $.ajax({
            url      : '/reports/getBalanceSheetData',
            type     : 'GET',
            dataType : 'json',
            data     : { asof: _asOf },
            success  : function (res) {
                ajaxLoading(0);
                if (!res || res.Status !== 'Success') {
                    content.innerHTML = '<div class="fin-loading" style="color:#dc2626;">'
                        + (res && res.Message ? res.Message : 'Server error') + '</div>';
                    return;
                }
                _render(res.rows || []);
            },
            error: function () {
                ajaxLoading(0);
                content.innerHTML = '<div class="fin-loading" style="color:#dc2626;">Request failed. Please try again.</div>';
            },
        });
    }

    /** Wire up Flatpickr date picker. */
    function _initDatePicker() {
        _fpAsOf = flatpickr('#bsAsOfDisplay', {
            dateFormat  : 'Y-m-d',
            altInput    : true,
            altFormat   : _bsListFmt,
            defaultDate : _bsInitAsOf,
            onChange    : function (dates, str) { _asOf = str; },
        });
    }

    /** Initialise the page. */
    function _init() {
        _initDatePicker();
        document.getElementById('bsApplyBtn').addEventListener('click', _fetch);
        _fetch();
    }

    $(document).ready(_init);

}());
