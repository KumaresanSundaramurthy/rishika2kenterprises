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
     * @param {string}  elId
     * @param {number}  val
     * @returns {void}
     */
    function _set(elId, val) {
        var el = document.getElementById(elId);
        if (el) el.textContent = _fmt(val);
    }

    /**
     * @param {string}  elId
     * @param {number}  val
     * @returns {void}
     */
    function _setCount(elId, val) {
        var el = document.getElementById(elId);
        if (el) el.textContent = val || 0;
    }

    /**
     * @param {Object} outward
     * @param {Object} itc
     * @returns {void}
     */
    function _render(outward, itc) {
        var salesTaxable  = parseFloat(outward.SalesTaxable)  || 0;
        var salesCgst     = parseFloat(outward.SalesCgst)     || 0;
        var salesSgst     = parseFloat(outward.SalesSgst)     || 0;
        var salesIgst     = parseFloat(outward.SalesIgst)     || 0;
        var salesTax      = parseFloat(outward.SalesTax)      || 0;
        var salesNet      = parseFloat(outward.SalesNet)       || 0;
        var salesCount    = parseInt(outward.SalesCount, 10)   || 0;
        var returnTaxable = parseFloat(outward.ReturnTaxable)  || 0;
        var returnTax     = parseFloat(outward.ReturnTax)      || 0;
        var returnCount   = parseInt(outward.ReturnCount, 10)  || 0;

        _setCount('g3SalesCount',    salesCount);
        _set('g3SalesTaxable', salesTaxable);
        _set('g3SalesTax',     salesTax);
        _set('g3SalesCgst',    salesCgst);
        _set('g3SalesSgst',    salesSgst);
        _set('g3SalesIgst',    salesIgst);
        _setCount('g3ReturnCount', returnCount);
        _set('g3ReturnTaxable', returnTaxable);
        _set('g3ReturnTax',     returnTax);
        _set('g3NetTaxable',    salesTaxable - returnTaxable);
        _set('g3NetTax',        salesTax - returnTax);

        var purchaseTaxable = parseFloat(itc.PurchaseTaxable)  || 0;
        var purchaseCgst    = parseFloat(itc.PurchaseCgst)     || 0;
        var purchaseSgst    = parseFloat(itc.PurchaseSgst)     || 0;
        var purchaseIgst    = parseFloat(itc.PurchaseIgst)     || 0;
        var purchaseTax     = parseFloat(itc.PurchaseTax)      || 0;
        var purchaseNet     = parseFloat(itc.PurchaseNet)       || 0;
        var purchaseCount   = parseInt(itc.PurchaseCount, 10)  || 0;
        var prCgst          = parseFloat(itc.PRCgst)           || 0;
        var prSgst          = parseFloat(itc.PRSgst)           || 0;
        var prIgst          = parseFloat(itc.PRIgst)           || 0;
        var prTax           = prCgst + prSgst + prIgst;
        var netItcCgst      = purchaseCgst - prCgst;
        var netItcSgst      = purchaseSgst - prSgst;
        var netItcIgst      = purchaseIgst - prIgst;
        var netItcTotal     = netItcCgst + netItcSgst + netItcIgst;

        _setCount('g3PurchaseCount', purchaseCount);
        _set('g3PurchaseTaxable', purchaseTaxable);
        _set('g3PurchaseTax',     purchaseTax);
        _set('g3PurchaseCgst',    purchaseCgst);
        _set('g3PurchaseSgst',    purchaseSgst);
        _set('g3PurchaseIgst',    purchaseIgst);
        _set('g3PurchaseReturnTax', prTax);
        _set('g3ItcTaxable',      purchaseTaxable);
        _set('g3ItcNet',          netItcTotal);

        var liabCgst  = Math.max(0, salesCgst - netItcCgst);
        var liabSgst  = Math.max(0, salesSgst - netItcSgst);
        var liabIgst  = Math.max(0, salesIgst - netItcIgst);
        var liabTotal = liabCgst + liabSgst + liabIgst;

        _set('g3LiabCgst',  liabCgst);
        _set('g3LiabSgst',  liabSgst);
        _set('g3LiabIgst',  liabIgst);
        _set('g3LiabTotal', liabTotal);
    }

    /** @returns {void} */
    function _fetch() {
        var month = document.getElementById('g3Month').value;
        var year  = document.getElementById('g3Year').value;

        ajaxLoading(1);
        ['g3SalesCount','g3SalesTaxable','g3SalesTax','g3SalesCgst','g3SalesSgst','g3SalesIgst',
         'g3ReturnCount','g3ReturnTaxable','g3ReturnTax','g3NetTaxable','g3NetTax',
         'g3PurchaseCount','g3PurchaseTaxable','g3PurchaseTax','g3PurchaseCgst','g3PurchaseSgst','g3PurchaseIgst',
         'g3PurchaseReturnTax','g3ItcTaxable','g3ItcNet',
         'g3LiabCgst','g3LiabSgst','g3LiabIgst','g3LiabTotal'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.textContent = '—';
        });

        $.get('/reports/getGstr3bData', { month: month, year: year })
            .done(function (res) {
                if (res.Status !== 'Success') {
                    document.getElementById('g3LiabTotal').textContent = 'Error';
                    return;
                }
                _render(res.outward || {}, res.itc || {});
            })
            .fail(function () {
                document.getElementById('g3LiabTotal').textContent = 'Failed';
            })
            .always(function () { ajaxLoading(0); });
    }

    /** @returns {void} */
    function _init() {
        document.getElementById('g3ApplyBtn').addEventListener('click', _fetch);
        _fetch();
    }

    $(document).ready(_init);
})();
