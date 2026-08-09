(function () {
    'use strict';

    var _sym = (genSettings && genSettings.CurrenySymbol) ? genSettings.CurrenySymbol : '₹';
    var _dec = (genSettings && genSettings.DecimalPoints)  ? parseInt(genSettings.DecimalPoints, 10) : 2;
    var _customerUID = 0;

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
        var fmt = _clListFmt || 'd M Y';
        var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
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
            'Invoice':          ['cl-type-invoice',  'bx-receipt',      'Invoice'],
            'Sales Return':     ['cl-type-return',   'bx-arrow-back',   'Sales Return'],
            'Payment Received': ['cl-type-payment',  'bx-money',        'Payment Received'],
            'Opening Balance':  ['',                 'bx-wallet',       'Opening Balance']
        };
        var info = map[txType] || ['', 'bx-circle', _esc(txType)];
        var chipCls = info[0] ? 'cl-type-chip ' + info[0] : '';
        return chipCls
            ? '<span class="' + chipCls + '"><i class="bx ' + info[1] + '"></i>' + _esc(info[2]) + '</span>'
            : '<span style="font-size:.78rem;color:#64748b">' + _esc(info[2]) + '</span>';
    }

    /**
     * @param {Array}  rows
     * @param {{debit:number,credit:number}} opening
     * @returns {void}
     */
    function _render(rows, opening) {
        var tbody  = document.getElementById('clTableBody');
        var tfoot  = document.getElementById('clTableFoot');
        var footer = document.getElementById('clTblFooter');
        var statsRow = document.getElementById('clStatsRow');

        var obDebit  = parseFloat(opening.debit  || 0);
        var obCredit = parseFloat(opening.credit || 0);
        var runBal   = obDebit - obCredit;

        var html = '';
        var rowNum = 0;

        if (obDebit > 0.005 || obCredit > 0.005) {
            rowNum++;
            html += '<tr class="cl-ob-row">';
            html += '<td>' + rowNum + '</td>';
            html += '<td>—</td>';
            html += '<td>' + _typeChip('Opening Balance') + '</td>';
            html += '<td><em>Opening Balance</em></td>';
            html += '<td class="rpt-col-num">' + (obDebit  > 0.005 ? _fmt(obDebit)  : '—') + '</td>';
            html += '<td class="rpt-col-num">' + (obCredit > 0.005 ? _fmt(obCredit) : '—') + '</td>';
            html += '<td class="rpt-col-num ' + (runBal >= 0 ? 'rpt-num-blue' : 'rpt-num-green') + '">' + _fmt(Math.abs(runBal)) + (runBal < 0 ? ' Cr' : ' Dr') + '</td>';
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
            runBal   += dr - cr;
            totDebit  += dr;
            totCredit += cr;

            html += '<tr>';
            html += '<td>' + rowNum + '</td>';
            html += '<td>' + _fmtDate(r.TxDate) + '</td>';
            html += '<td>' + _typeChip(r.TxType) + '</td>';
            html += '<td>' + _esc(r.RefNo || '—') + '</td>';
            html += '<td class="rpt-col-num">' + (dr > 0.005 ? _fmt(dr) : '—') + '</td>';
            html += '<td class="rpt-col-num">' + (cr > 0.005 ? _fmt(cr) : '—') + '</td>';
            html += '<td class="rpt-col-num ' + (runBal >= 0 ? 'rpt-num-blue' : 'rpt-num-green') + '">' + _fmt(Math.abs(runBal)) + (runBal < 0 ? ' Cr' : ' Dr') + '</td>';
            html += '</tr>';
        });

        tbody.innerHTML = html;

        document.getElementById('clFtDebit').textContent   = _fmt(totDebit);
        document.getElementById('clFtCredit').textContent  = _fmt(totCredit);
        document.getElementById('clFtBalance').textContent = _fmt(Math.abs(runBal)) + (runBal < 0 ? ' Cr' : ' Dr');
        tfoot.classList.remove('d-none');

        document.getElementById('clRowCount').textContent       = (rows ? rows.length : 0) + ' transaction' + ((rows && rows.length !== 1) ? 's' : '');
        document.getElementById('clFooterDebit').textContent    = _fmt(totDebit);
        document.getElementById('clFooterBalance').textContent  = _fmt(Math.abs(runBal)) + (runBal < 0 ? ' Cr' : ' Dr');
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
        var obAbs  = Math.abs(obDebit - obCredit);
        var obSide = (obDebit - obCredit) >= 0 ? ' Dr' : ' Cr';
        document.getElementById('clStatOpening').textContent = _fmt(obAbs) + obSide;
        document.getElementById('clStatDebit').textContent   = _fmt(totDebit);
        document.getElementById('clStatCredit').textContent  = _fmt(totCredit);
        var closingAbs  = Math.abs(closing);
        var closingSide = closing >= 0 ? ' Dr' : ' Cr';
        document.getElementById('clStatClosing').textContent = _fmt(closingAbs) + closingSide;
    }

    /**
     * @returns {void}
     */
    function _fetch() {
        if (_customerUID <= 0) {
            showToastError('Please select a customer.');
            return;
        }

        var from = document.getElementById('clFrom').value;
        var to   = document.getElementById('clTo').value;

        if (!from || !to) {
            showToastError('Please select a date range.');
            return;
        }

        var tbody = document.getElementById('clTableBody');
        tbody.innerHTML = '<tr><td colspan="7" class="rpt-loading-cell"><div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div></td></tr>';
        document.getElementById('clTableFoot').classList.add('d-none');
        document.getElementById('clTblFooter').classList.add('d-none');

        $.ajax({
            url: '/reports/getCustomerLedgerData',
            type: 'GET',
            dataType: 'json',
            data: { customerUID: _customerUID, from: from, to: to },
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
        var fromInput    = document.getElementById('clFrom');
        var toInput      = document.getElementById('clTo');
        var fromDisplay  = document.getElementById('clFromDisplay');
        var toDisplay    = document.getElementById('clToDisplay');

        var fmt = typeof _clListFmt !== 'undefined' ? _clListFmt : 'd M Y';
        var fpFmt = fmt
            .replace('d', 'd').replace('M', 'M').replace('m', 'm').replace('Y', 'Y');

        flatpickr(fromDisplay, {
            dateFormat: 'Y-m-d',
            altInput: false,
            defaultDate: fromInput.value || _clInitFrom,
            onChange: function (sel, str) {
                fromInput.value = str;
                var inst = fromDisplay._flatpickr;
                inst.setDate(str, false);
                fromDisplay.value = str ? new Date(str + 'T00:00:00').toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '';
            }
        });

        flatpickr(toDisplay, {
            dateFormat: 'Y-m-d',
            altInput: false,
            defaultDate: toInput.value || _clInitTo,
            onChange: function (sel, str) {
                toInput.value = str;
                var inst = toDisplay._flatpickr;
                inst.setDate(str, false);
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
        $('#clCustomer').select2({
            placeholder: 'Search customer…',
            allowClear: true,
            minimumInputLength: 1,
            ajax: {
                url: '/transactions/searchCustomers',
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
            _customerUID = parseInt($(this).val() || '0', 10);
        });
    }

    /**
     * @returns {void}
     */
    function _init() {
        _initDatePickers();
        _initSelect2();
        document.getElementById('clApplyBtn').addEventListener('click', function () {
            _fetch();
        });
    }

    $(document).ready(_init);
}());
