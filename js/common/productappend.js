'use strict';

/**
 * ProductAppend — single source for product data on all transaction pages.
 *
 * Exposes one public method:
 *   ProductAppend.load(onSuccess, onFail)
 *     — fetches the complete product list.
 *       Chain: Upstash orgKey('products') → AJAX /transactions/searchTransProducts
 *
 * Data shape returned to onSuccess (mirrors searchTransProducts server response):
 *   [{ id, text, itemName, productType, unitPrice, taxAmount, sellingPrice,
 *      purchasePrice, availableQuantity, hsnCode, category, categoryUID,
 *      categoryName, partNumber, taxPercent, taxDetailsUID, cgstPercent,
 *      sgstPercent, igstPercent, discount, discountType, discountTypeUID,
 *      primaryUnit, description, isComboItem, comboItemCount, image }]
 */
window.ProductAppend = (function () {

    // ── Build a normalized product list from the Upstash bulk map ─────────────

    function _buildList(map) {
        return Object.keys(map).map(function (uid) {
            var p            = map[uid];
            var sellingPrice = parseFloat(p.SellingPrice   || 0);
            var taxPercent   = parseFloat(p.TaxPercentage  || 0);
            var unitPrice    = taxPercent > 0
                ? sellingPrice / (1 + taxPercent / 100)
                : sellingPrice;
            return {
                id                : parseInt(uid, 10),
                text              : p.ItemName             || '',
                itemName          : p.ItemName             || '',
                productType       : p.ProductType          || '',
                unitPrice         : unitPrice,
                taxAmount         : sellingPrice - unitPrice,
                sellingPrice      : sellingPrice,
                purchasePrice     : parseFloat(p.PurchasePrice     || 0),
                availableQuantity : parseFloat(p.AvailableQuantity || 0),
                hsnCode           : p.HSNSACCode            || '',
                category          : p.CategoryName          || '',
                categoryUID       : parseInt(p.CategoryUID  || 0, 10),
                categoryName      : p.CategoryName          || '',
                partNumber        : p.PartNumber            || '',
                taxPercent        : taxPercent,
                taxDetailsUID     : parseInt(p.TaxDetailsUID || 0, 10),
                cgstPercent       : parseFloat(p.CGST        || 0),
                sgstPercent       : parseFloat(p.SGST        || 0),
                igstPercent       : parseFloat(p.IGST        || 0),
                discount          : parseFloat(p.Discount    || 0),
                discountTypeUID   : parseInt(p.DiscountTypeUID || 0, 10),
                discountType      : parseInt(p.DiscountTypeUID || 0, 10) === 2 ? 'Fixed' : 'Percentage',
                primaryUnit       : p.PrimaryUnitName       || '',
                description       : p.Description           || '',
                isComboItem       : parseInt(p.IsComboItem   || 0, 10),
                comboItemCount    : 0,
                image             : p.Image                 || '',
                notForSale        : parseInt(p.NotForSale   || 0, 10),
            };
        }).sort(function (a, b) { return a.text.localeCompare(b.text); });
    }

    // ── Tier 1: Upstash cache ─────────────────────────────────────────────────

    function _fromUpstash(onSuccess, onFail) {
        if (!UpstashService.isEnabled()) { onFail(); return; }
        UpstashService.hgetall(UpstashService.orgKey('products')).then(function (map) {
            if (!map || typeof map !== 'object' || Array.isArray(map)) { onFail(); return; }
            var keys = Object.keys(map);
            if (!keys.length) { onFail(); return; }
            onSuccess(_buildList(map));
        }).catch(function () { onFail(); });
    }

    // ── Tier 2: AJAX fallback ─────────────────────────────────────────────────

    function _fromServer(onSuccess, onFail) {
        $.ajax({
            url     : '/transactions/searchTransProducts',
            dataType: 'json',
            data    : { term: '', type: 'public' },
            success : function (data) {
                if (data.Lists && data.Lists.length) {
                    onSuccess(data.Lists);
                } else {
                    onFail();
                }
            },
            error: function () { onFail(); }
        });
    }

    // ── Public ────────────────────────────────────────────────────────────────

    function load(onSuccess, onFail) {
        _fromUpstash(onSuccess, function () {
            _fromServer(onSuccess, onFail || function () {});
        });
    }

    return { load };

}());
