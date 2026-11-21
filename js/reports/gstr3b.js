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
    function _set(elId, val) { $('#' + elId).text(_fmt(val)); }

    /**
     * @param {string}  elId
     * @param {number}  val
     * @returns {void}
     */
    function _setCount(elId, val) { $('#' + elId).text(val || 0); }

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
        var month = $('#g3Month').val();
        var year  = $('#g3Year').val();

        ajaxLoading(1);
        ['g3SalesCount','g3SalesTaxable','g3SalesTax','g3SalesCgst','g3SalesSgst','g3SalesIgst',
         'g3ReturnCount','g3ReturnTaxable','g3ReturnTax','g3NetTaxable','g3NetTax',
         'g3PurchaseCount','g3PurchaseTaxable','g3PurchaseTax','g3PurchaseCgst','g3PurchaseSgst','g3PurchaseIgst',
         'g3PurchaseReturnTax','g3ItcTaxable','g3ItcNet',
         'g3LiabCgst','g3LiabSgst','g3LiabIgst','g3LiabTotal'].forEach(function (id) {
            $('#' + id).text('—');
        });

        $.ajax({
            type    : 'GET',
            url     : global_services_url + 'api/reports/getGstr3bData',
            headers : { Authorization: 'Bearer ' + JwtToken },
            data    : { orgId: parseInt(JwtData.Org.OrgUID, 10), month: month, year: year },
            success : function (res) {
                if (res.Error) {
                    $('#g3LiabTotal').text('Error');
                    return;
                }
                _render(res.outward || {}, res.itc || {});
            },
            error   : function () {
                $('#g3LiabTotal').text('Failed');
            }
        }).always(function () { ajaxLoading(0); });
    }

    /**
     * Export the GSTR-3B summary via the services endpoint.
     * @param {string} format
     * @returns {void}
     */
    function _handleExport(format) {
        var month = $('#g3Month').val();
        var year  = $('#g3Year').val();
        var type  = format === 'pdf' ? 'Pdf' : (format === 'print' ? 'Print' : (format === 'excel' ? 'Excel' : 'CSV'));

        var payload = {
            orgId : parseInt(JwtData.Org.OrgUID, 10),
            month : parseInt(month, 10),
            year  : parseInt(year, 10),
            Type  : type
        };

        if (type === 'Print') {
            $.ajax({
                url        : global_services_url + 'api/reports/exportGstr3b',
                method     : 'POST',
                contentType: 'application/json',
                headers    : { Authorization: 'Bearer ' + JwtToken },
                data       : JSON.stringify(payload),
                dataType   : 'json',
                success    : function (resp) {
                    if (!resp || resp.Error || !resp.HtmlData) {
                        showToastNotification((resp && resp.Message) ? resp.Message : 'Export failed.', 'error');
                        return;
                    }
                    var win = window.open('', '_blank');
                    win.document.open(); win.document.write(resp.HtmlData); win.document.close();
                },
                error : function () { showToastNotification('Export failed. Please try again.', 'error'); }
            });
        } else {
            var ext = type === 'Excel' ? 'xlsx' : (type === 'Pdf' ? 'pdf' : 'csv');
            $.ajax({
                url        : global_services_url + 'api/reports/exportGstr3b',
                method     : 'POST',
                contentType: 'application/json',
                headers    : { Authorization: 'Bearer ' + JwtToken },
                data       : JSON.stringify(payload),
                xhrFields  : { responseType: 'blob' },
                success    : function (blob) {
                    var url = URL.createObjectURL(blob);
                    var $a  = $('<a>').attr({ href: url, download: 'GSTR3B_' + year + '_' + month + '.' + ext }).appendTo('body');
                    $a[0].click();
                    setTimeout(function () { URL.revokeObjectURL(url); $a.remove(); }, 3000);
                },
                error : function () { showToastNotification('Export failed. Please try again.', 'error'); }
            });
        }
    }

    /** @returns {void} */
    function _init() {
        $('#g3ApplyBtn').on('click', _fetch);
        $('#g3RefreshBtn').on('click', _fetch);
        $(document).on('click', '.r2k-export-btn[data-format]', function () {
            _handleExport($(this).data('format'));
        });
        _fetch();
    }

    $(document).ready(_init);
})();
