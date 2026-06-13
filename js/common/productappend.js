'use strict';

/**
 * ProductAppend — single source for product data on all transaction pages.
 *
 * Exposes one public method:
 *   ProductAppend.load(onSuccess, onFail)
 *     — fetches the complete product list.
 *       Chain: Upstash orgKey('products') → AJAX /transactions/searchTransProducts
 *
 * On purchase pages (Purchases, Purchase Returns, Purchase Orders), set
 *   window._productPurchaseMode = true
 * before the page initialises. This causes unitPrice and sellingPrice to be
 * derived from PurchasePrice instead of SellingPrice.
 *
 * Data shape returned to onSuccess (mirrors searchTransProducts server response):
 *   [{ id, text, itemName, productType, unitPrice, taxAmount, sellingPrice,
 *      purchasePrice, purchasePriceTaxUID, availableQuantity, hsnCode, category,
 *      categoryUID, categoryName, partNumber, taxPercent, taxDetailsUID,
 *      cgstPercent, sgstPercent, igstPercent, discount, discountType,
 *      discountTypeUID, primaryUnit, description, isComboItem, comboItemCount,
 *      image }]
 */
window.ProductAppend = (function () {

    // ── Build a normalized product list from the Upstash bulk map ─────────────

    function _buildList(map) {
        var forPurchase = !!window._productPurchaseMode;
        return Object.keys(map).map(function (uid) {
            var p          = map[uid];
            var taxPercent = parseFloat(p.TaxPercentage  || 0);
            var purTaxUID  = parseInt(p.PurchasePriceProductTaxUID || 0, 10);
            var sellingPrice, unitPrice, taxAmount;

            if (forPurchase) {
                var purchPrice = parseFloat(p.PurchasePrice || 0);
                // PurchasePriceProductTaxUID === 1 means tax-inclusive → reverse-calc
                unitPrice    = (purTaxUID === 1 && taxPercent > 0)
                               ? purchPrice / (1 + taxPercent / 100)
                               : purchPrice;
                sellingPrice = purchPrice;
                taxAmount    = purchPrice - unitPrice;
            } else {
                sellingPrice = parseFloat(p.SellingPrice || 0);
                unitPrice    = taxPercent > 0
                               ? sellingPrice / (1 + taxPercent / 100)
                               : sellingPrice;
                taxAmount    = sellingPrice - unitPrice;
            }

            return {
                id                  : parseInt(uid, 10),
                text                : p.ItemName             || '',
                itemName            : p.ItemName             || '',
                productType         : p.ProductType          || '',
                unitPrice           : unitPrice,
                taxAmount           : taxAmount,
                sellingPrice        : sellingPrice,
                purchasePrice       : parseFloat(p.PurchasePrice     || 0),
                purchasePriceTaxUID : purTaxUID,
                availableQuantity   : parseFloat(p.AvailableQuantity || 0),
                hsnCode             : p.HSNSACCode            || '',
                category            : p.CategoryName          || '',
                categoryUID         : parseInt(p.CategoryUID  || 0, 10),
                categoryName        : p.CategoryName          || '',
                partNumber          : p.PartNumber            || '',
                taxPercent          : taxPercent,
                taxDetailsUID       : parseInt(p.TaxDetailsUID || 0, 10),
                cgstPercent         : parseFloat(p.CGST        || 0),
                sgstPercent         : parseFloat(p.SGST        || 0),
                igstPercent         : parseFloat(p.IGST        || 0),
                discount            : parseFloat(p.Discount    || 0),
                discountTypeUID     : parseInt(p.DiscountTypeUID || 0, 10),
                discountType        : parseInt(p.DiscountTypeUID || 0, 10) === 2 ? 'Fixed' : 'Percentage',
                primaryUnit         : p.PrimaryUnitName       || '',
                description         : p.Description           || '',
                isComboItem         : parseInt(p.IsComboItem   || 0, 10),
                isComposite         : parseInt(p.IsComposite   || 0, 10),
                comboItemCount      : 0,
                image               : p.Image                 || '',
                notForSale          : parseInt(p.NotForSale   || 0, 10),
            };
        }).filter(function (p) {
            return !forPurchase || !p.isComposite;
        }).sort(function (a, b) { return a.text.localeCompare(b.text); });
    }

    // ── Remap AJAX response for purchase pages ────────────────────────────────
    // Server returns unitPrice/sellingPrice from SellingPrice; override with
    // purchase price values when _productPurchaseMode is active.

    function _remapForPurchase(list) {
        return list.map(function (p) {
            var taxPercent = p.taxPercent    || 0;
            var purchPrice = p.purchasePrice || 0;
            var purTaxUID  = p.purchasePriceTaxUID || 0;
            var unitPrice  = (purTaxUID === 1 && taxPercent > 0)
                             ? purchPrice / (1 + taxPercent / 100)
                             : purchPrice;
            return Object.assign({}, p, {
                unitPrice    : unitPrice,
                taxAmount    : purchPrice - unitPrice,
                sellingPrice : purchPrice,
            });
        });
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
                    var list = window._productPurchaseMode
                        ? _remapForPurchase(data.Lists).filter(function (p) { return !p.isComposite; })
                        : data.Lists;
                    onSuccess(list);
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
