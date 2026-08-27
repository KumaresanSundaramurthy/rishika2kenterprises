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
            'Purchase Bill':    ['sl-type-bill',    'bx-receipt',      'Purchase Bill'],
            'Purchase Return':  ['sl-type-return',  'bx-arrow-back',   'Purchase Return'],
            'Payment Made':     ['sl-type-payment', 'bx-money',        'Payment Made'],
            'Opening Balance':  ['',                'bx-wallet',       'Opening Balance']
        };
        var info = map[txType] || ['', 'bx-circle', _esc(txType)];
        var chipCls = info[0] ? 'sl-type-chip ' + info[0] : '';
        return chipCls
            ? '<span class="' + chipCls + '"><i class="bx ' + info[1] + '"></i>' + _esc(info[2]) + '</span>'
            : '<span style="font-size:.78rem;color:#64748b">' + _esc(info[2]) + '</span>';
    }

    /**
     * For supplier ledger:
     *   Debit  = Purchase Return + Payment Made (reduces what we owe)
     *   Credit = Purchase Bill (we owe vendor)
     *   Balance (positive) = we still owe vendor
     *
     * @param {Array}  rows
     * @param {{debit:number,credit:number}} opening
     * @returns {void}
     */
    function _render(rows, opening) {
        var tbody    = document.getElementById('slTableBody');
        var tfoot    = document.getElementById('slTableFoot');
        var footer   = document.getElementById('slTblFooter');
        var statsRow = document.getElementById('slStatsRow');

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
                tbody.innerHTML = '<tr><td colspan="7"><div class="rpt-empty"><i class="bx bx-info-circle"></i><div class="rpt-empty-title">No transactions found</div><div style="font-size:.82rem">Try a different date range</div></div></td></tr>';
                tfoot.classList.add('d-none');
                footer.classList.add('d-none');
                statsRow.style.setProperty('display', 'none', 'important');
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

        tbody.innerHTML = html;

        document.getElementById('slFtDebit').textContent   = _fmt(totDebit);
        document.getElementById('slFtCredit').textContent  = _fmt(totCredit);
        document.getElementById('slFtBalance').textContent = _fmt(Math.abs(runBal)) + (runBal < 0 ? ' Dr' : ' Cr');
        tfoot.classList.remove('d-none');

        document.getElementById('slRowCount').textContent       = (rows ? rows.length : 0) + ' transaction' + ((rows && rows.length !== 1) ? 's' : '');
        document.getElementById('slFooterDebit').textContent    = _fmt(totDebit);
        document.getElementById('slFooterBalance').textContent  = _fmt(Math.abs(runBal)) + (runBal < 0 ? ' Dr' : ' Cr');
        footer.classList.remove('d-none');

        statsRow.style.removeProperty('display');
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
        document.getElementById('slStatOpening').textContent = _fmt(obAbs) + obSide;
        document.getElementById('slStatCredit').textContent  = _fmt(totCredit);
        document.getElementById('slStatDebit').textContent   = _fmt(totDebit);
        var closingAbs  = Math.abs(closing);
        var closingSide = closing >= 0 ? ' Cr' : ' Dr';
        document.getElementById('slStatClosing').textContent = _fmt(closingAbs) + closingSide;
    }

    /**
     * @returns {void}
     */
    function _fetch() {
        if (_vendorUID <= 0) {
            showToastError('Please select a supplier.');
            return;
        }

        var from = document.getElementById('slFrom').value;
        var to   = document.getElementById('slTo').value;

        if (!from || !to) {
            showToastError('Please select a date range.');
            return;
        }

        var tbody = document.getElementById('slTableBody');
        tbody.innerHTML = '<tr><td colspan="7" class="rpt-loading-cell"><div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div></td></tr>';
        document.getElementById('slTableFoot').classList.add('d-none');
        document.getElementById('slTblFooter').classList.add('d-none');

        $.ajax({
            url: '/reports/getSupplierLedgerData',
            type: 'GET',
            dataType: 'json',
            data: { vendorUID: _vendorUID, from: from, to: to },
            success: function (res) {
                if (res && res.Status === 'Success') {
                    var opening = res.opening || { debit: 0, credit: 0 };
                    _render(res.rows || [], opening);
                } else {
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center p-4 text-danger">' + _esc((res && res.Message) ? res.Message : 'Failed to load data') + '</td></tr>';
                }
            },
            error: function () {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center p-4 text-danger">Network error. Please try again.</td></tr>';
            }
        });
    }

    /**
     * @returns {void}
     */
    function _initDatePickers() {
        var fromInput   = document.getElementById('slFrom');
        var toInput     = document.getElementById('slTo');
        var fromDisplay = document.getElementById('slFromDisplay');
        var toDisplay   = document.getElementById('slToDisplay');

        flatpickr(fromDisplay, {
            dateFormat: 'Y-m-d',
            altInput: false,
            defaultDate: fromInput.value || _slInitFrom,
            onChange: function (sel, str) {
                fromInput.value = str;
                fromDisplay.value = str ? new Date(str + 'T00:00:00').toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '';
            }
        });

        flatpickr(toDisplay, {
            dateFormat: 'Y-m-d',
            altInput: false,
            defaultDate: toInput.value || _slInitTo,
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
    function _initSelect2() {
        $('#slSupplier').select2({
            placeholder: 'Search supplier…',
            allowClear: true,
            minimumInputLength: 1,
            ajax: {
                url: '/transactions/searchVendors',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { term: params.term };
                },
                processResults: function (data) {
                    return { results: data };
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
        document.getElementById('slApplyBtn').addEventListener('click', function () {
            _fetch();
        });
    }

    $(document).ready(_init);
}());
