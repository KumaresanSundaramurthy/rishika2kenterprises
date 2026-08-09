(function () {
    'use strict';

    var _dec  = (typeof genSettings !== 'undefined' && genSettings.DecimalPlaces != null) ? parseInt(genSettings.DecimalPlaces, 10) : 2;
    var _curr = (typeof genSettings !== 'undefined' && genSettings.CurrencySymbol) ? genSettings.CurrencySymbol : '₹';
    var _activeTab = 'b2b';

    /**
     * @param {string} s
     * @returns {string}
     */
    function _esc(s) {
        if (s == null) return '';
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    /**
     * @param {number|string} n
     * @returns {string}
     */
    function _fmt(n) {
        var v = parseFloat(n) || 0;
        return _curr + ' ' + v.toFixed(_dec).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    /**
     * @param {number|string} n
     * @returns {string}
     */
    function _num(n) { return (parseFloat(n) || 0).toFixed(_dec); }

    /**
     * @param {string} iso
     * @returns {string}
     */
    function _fmtDate(iso) {
        if (!iso) return '—';
        var d = new Date(iso);
        if (isNaN(d.getTime())) return iso;
        return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    /**
     * @param {string} gstin
     * @returns {string}
     */
    function _gstinBadge(gstin) {
        if (!gstin) return '<span class="g1-unregd-badge">Unregistered</span>';
        return '<span class="g1-gstin-badge">' + _esc(gstin) + '</span>';
    }

    /** @type {Object} */
    var _data = { b2b: [], b2cs: [], cdnr: [] };

    /**
     * @param {Array<Object>} rows
     * @param {string}        bodyId
     * @param {string}        footId
     * @param {string}        colCount
     * @param {boolean}       hasGstin
     * @returns {{taxable:number, cgst:number, sgst:number, igst:number, tax:number, net:number}}
     */
    function _renderTable(rows, bodyId, footId, colCount, hasGstin) {
        var tbody = document.getElementById(bodyId);
        var tfoot = document.getElementById(footId);
        var totTaxable = 0, totCgst = 0, totSgst = 0, totIgst = 0, totTax = 0, totNet = 0;

        if (!rows || rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="' + colCount + '">' +
                '<div class="rpt-empty"><i class="bx bx-file-blank"></i>' +
                '<div class="rpt-empty-title">No records found</div>' +
                '<div>No transactions for selected month/year</div></div></td></tr>';
            tfoot.classList.add('d-none');
            return { taxable: 0, cgst: 0, sgst: 0, igst: 0, tax: 0, net: 0 };
        }

        var html = '';
        rows.forEach(function (r, i) {
            var taxable = parseFloat(r.Taxable) || 0;
            var cgst    = parseFloat(r.CgstAmount) || 0;
            var sgst    = parseFloat(r.SgstAmount) || 0;
            var igst    = parseFloat(r.IgstAmount) || 0;
            var tax     = parseFloat(r.TaxAmount)  || 0;
            var net     = parseFloat(r.NetAmount)  || 0;
            totTaxable += taxable; totCgst += cgst; totSgst += sgst;
            totIgst += igst; totTax += tax; totNet += net;

            var gstinCol = hasGstin
                ? '<td>' + _gstinBadge(r.RecipientGSTIN || r.SupplierGSTIN || '') + '</td><td>' + _esc(r.RecipientName || r.SupplierName || r.CustomerName || '—') + '</td>'
                : '<td>' + _esc(r.CustomerName || '—') + '</td>';
            var posCol = (r.PlaceOfSupply !== undefined) ? '<td>' + _esc(r.PlaceOfSupply) + '</td>' : '';

            html += '<tr>' +
                '<td>' + (i + 1) + '</td>' +
                '<td>' + _esc(r.InvNo || r.DocNo) + '</td>' +
                '<td>' + _fmtDate(r.TransDate) + '</td>' +
                gstinCol +
                posCol +
                '<td class="rpt-col-num">' + _num(taxable) + '</td>' +
                '<td class="rpt-col-num">' + _num(cgst) + '</td>' +
                '<td class="rpt-col-num">' + _num(sgst) + '</td>' +
                '<td class="rpt-col-num">' + _num(igst) + '</td>' +
                '<td class="rpt-col-num rpt-num-indigo">' + _num(tax) + '</td>' +
                '<td class="rpt-col-num rpt-num-teal">' + _num(net) + '</td>' +
                '</tr>';
        });
        tbody.innerHTML = html;

        var prefix = bodyId === 'g1B2bBody' ? 'g1B2b' : (bodyId === 'g1B2csBody' ? 'g1B2cs' : 'g1Cdnr');
        document.getElementById(prefix + 'FtTaxable').textContent = _num(totTaxable);
        document.getElementById(prefix + 'FtCgst').textContent    = _num(totCgst);
        document.getElementById(prefix + 'FtSgst').textContent    = _num(totSgst);
        document.getElementById(prefix + 'FtIgst').textContent    = _num(totIgst);
        document.getElementById(prefix + 'FtTax').textContent     = _num(totTax);
        document.getElementById(prefix + 'FtNet').textContent     = _num(totNet);
        tfoot.classList.remove('d-none');
        return { taxable: totTaxable, cgst: totCgst, sgst: totSgst, igst: totIgst, tax: totTax, net: totNet };
    }

    /** @returns {void} */
    function _render() {
        _renderTable(_data.b2b,  'g1B2bBody',  'g1B2bFoot',  '12', true);
        _renderTable(_data.b2cs, 'g1B2csBody', 'g1B2csFoot', '11', false);
        _renderTable(_data.cdnr, 'g1CdnrBody', 'g1CdnrFoot', '11', true);

        var totalTax = 0;
        (_data.b2b.concat(_data.b2cs)).forEach(function (r) { totalTax += parseFloat(r.TaxAmount) || 0; });
        (_data.cdnr).forEach(function (r) { totalTax -= parseFloat(r.TaxAmount) || 0; });

        document.getElementById('g1StatB2b').textContent      = _data.b2b.length;
        document.getElementById('g1StatB2cs').textContent     = _data.b2cs.length;
        document.getElementById('g1StatCdnr').textContent     = _data.cdnr.length;
        document.getElementById('g1StatTax').textContent      = _fmt(totalTax);
        document.getElementById('g1TabB2bCount').textContent  = _data.b2b.length;
        document.getElementById('g1TabB2csCount').textContent = _data.b2cs.length;
        document.getElementById('g1TabCdnrCount').textContent = _data.cdnr.length;
    }

    /** @returns {void} */
    function _fetch() {
        var month = document.getElementById('g1Month').value;
        var year  = document.getElementById('g1Year').value;

        ajaxLoading(1);
        ['g1B2bBody', 'g1B2csBody', 'g1CdnrBody'].forEach(function (id) {
            document.getElementById(id).innerHTML =
                '<tr><td colspan="12" class="rpt-loading-cell">' +
                '<div class="rpt-loading"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div>' +
                '</td></tr>';
        });
        ['g1B2bFoot', 'g1B2csFoot', 'g1CdnrFoot'].forEach(function (id) {
            document.getElementById(id).classList.add('d-none');
        });

        $.get('/reports/getGstr1Data', { month: month, year: year })
            .done(function (res) {
                if (res.Status !== 'Success') {
                    document.getElementById('g1B2bBody').innerHTML =
                        '<tr><td colspan="12" class="text-center text-danger py-4">' + _esc(res.Message || 'Error loading data') + '</td></tr>';
                    return;
                }
                _data.b2b  = res.b2b  || [];
                _data.b2cs = res.b2cs || [];
                _data.cdnr = res.cdnr || [];
                _render();
            })
            .fail(function () {
                document.getElementById('g1B2bBody').innerHTML =
                    '<tr><td colspan="12" class="text-center text-danger py-4">Request failed. Please try again.</td></tr>';
            })
            .always(function () { ajaxLoading(0); });
    }

    /** @returns {void} */
    function _initTabs() {
        document.getElementById('g1Tabs').addEventListener('click', function (e) {
            var btn = e.target.closest('[data-tab]');
            if (!btn) return;
            _activeTab = btn.getAttribute('data-tab');
            document.querySelectorAll('#g1Tabs .rpt-tab-btn').forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            document.querySelectorAll('.rpt-tab-panel').forEach(function (p) { p.classList.add('d-none'); });
            document.getElementById('g1Panel' + _activeTab.charAt(0).toUpperCase() + _activeTab.slice(1)).classList.remove('d-none');
        });
    }

    /** @returns {void} */
    function _init() {
        _initTabs();
        document.getElementById('g1ApplyBtn').addEventListener('click', _fetch);
        _fetch();
    }

    $(document).ready(_init);
})();
