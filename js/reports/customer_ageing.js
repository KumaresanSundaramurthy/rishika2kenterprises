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
        var tbody  = document.getElementById('caTableBody');
        var tfoot  = document.getElementById('caTableFoot');
        var footer = document.getElementById('caTblFooter');

        if (!rows || rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9"><div class="rpt-empty"><i class="bx bx-info-circle"></i><div class="rpt-empty-title">No overdue invoices</div><div style="font-size:.82rem">All customers are up to date</div></div></td></tr>';
            tfoot.classList.add('d-none');
            footer.classList.add('d-none');
            _updateStats(0, 0, 0, 0);
            return;
        }

        var html = '';
        var ft0_30 = 0, ft31_60 = 0, ft61_90 = 0, ft91_120 = 0, ft120p = 0, ftTotal = 0;

        rows.forEach(function (r, i) {
            var b0   = parseFloat(r.Bucket0_30   || 0);
            var b1   = parseFloat(r.Bucket31_60  || 0);
            var b2   = parseFloat(r.Bucket61_90  || 0);
            var b3   = parseFloat(r.Bucket91_120 || 0);
            var b4   = parseFloat(r.Bucket120Plus || 0);
            var tot  = b0 + b1 + b2 + b3 + b4;

            ft0_30  += b0;
            ft31_60 += b1;
            ft61_90 += b2;
            ft91_120 += b3;
            ft120p  += b4;
            ftTotal += tot;

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

        tbody.innerHTML = html;

        document.getElementById('caFt0_30').textContent    = ft0_30 > 0.005   ? _fmt(ft0_30)   : '—';
        document.getElementById('caFt31_60').textContent   = ft31_60 > 0.005  ? _fmt(ft31_60)  : '—';
        document.getElementById('caFt61_90').textContent   = ft61_90 > 0.005  ? _fmt(ft61_90)  : '—';
        document.getElementById('caFt91_120').textContent  = ft91_120 > 0.005 ? _fmt(ft91_120) : '—';
        document.getElementById('caFt120Plus').textContent = ft120p > 0.005   ? _fmt(ft120p)   : '—';
        document.getElementById('caFtTotal').textContent   = _fmt(ftTotal);
        tfoot.classList.remove('d-none');

        document.getElementById('caRowCount').textContent    = rows.length + ' customer' + (rows.length !== 1 ? 's' : '');
        document.getElementById('caFooterTotal').textContent = _fmt(ftTotal);
        footer.classList.remove('d-none');

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
        document.getElementById('caStatTotal').textContent   = _fmt(total);
        document.getElementById('caStatCurrent').textContent = _fmt(current);
        document.getElementById('caStatMid').textContent     = _fmt(mid);
        document.getElementById('caStatOld').textContent     = _fmt(old);
    }

    /**
     * @returns {void}
     */
    function _fetch() {
        var tbody = document.getElementById('caTableBody');
        tbody.innerHTML = '<tr><td colspan="9" class="rpt-loading-cell"><div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div></td></tr>';
        document.getElementById('caTableFoot').classList.add('d-none');
        document.getElementById('caTblFooter').classList.add('d-none');

        $.ajax({
            url: '/reports/getCustomerAgeingData',
            type: 'GET',
            dataType: 'json',
            success: function (res) {
                if (res && res.Status === 'Success') {
                    _render(res.rows || []);
                } else {
                    tbody.innerHTML = '<tr><td colspan="9" class="text-center p-4 text-danger">' + _esc((res && res.Message) ? res.Message : 'Failed to load data') + '</td></tr>';
                }
            },
            error: function () {
                tbody.innerHTML = '<tr><td colspan="9" class="text-center p-4 text-danger">Network error. Please try again.</td></tr>';
            }
        });
    }

    /**
     * @returns {void}
     */
    function _init() {
        _fetch();
    }

    $(document).ready(_init);
}());
