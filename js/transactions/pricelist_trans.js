/**
 * pricelist_trans.js — Price List Transaction Integration
 *
 * Handles price list resolution, application, chip UI, and auto-discount
 * logic for all sale-side transaction pages.
 *
 * Depends on: UpstashService, billManager, updateTableRow,
 *             recalculateAllItemsWithNewScope, showToastNotification
 *
 * Entry points called from transactions.js:
 *   _plTransResolve(custData)     — on customer select2:select
 *   _plTransClear()               — on customer select2:clear
 *   _plTransApplyToNewRow(rowId)  — after pushBillItems adds a row
 *   _plTransInjectFormData(fd)    — before AJAX submit to append PL fields
 */

// ── State ────────────────────────────────────────────────────────────────────
var _plAllLists          = null;   // array of all active price lists from Upstash
var _plActivePriceList   = null;   // currently applied price list object
var _plCurrentCustData   = null;   // customer data object at resolve time
var _plPriceSource       = {};     // rowId (int) → 'pricelist' | 'manual'
var _plAutoDiscount      = false;  // true = new rows get price list % auto-applied
var _custDiscountApplied = false;  // true = globalDiscount was set from customer's DiscountPercent

// ── Mapping helpers ──────────────────────────────────────────────────────────

/**
 * @param {string} globalBasedOn  'SellingPrice' | 'UnitPrice' | 'PurchasePrice'
 * @returns {string|null}  corresponding discApplyFor value, or null if unsupported
 */
function _plToDiscApplyFor(globalBasedOn) {
    if (globalBasedOn === 'SellingPrice') return 'PriceWithTax';
    if (globalBasedOn === 'UnitPrice')    return 'UnitPrice';
    return null; // PurchasePrice deferred
}

/**
 * @param {string} discApplyForVal  current value of #discApplyFor select
 * @returns {string|null}  corresponding GlobalBasedOn, or null if no match
 */
function _plFromDiscApplyFor(discApplyForVal) {
    if (discApplyForVal === 'PriceWithTax') return 'SellingPrice';
    if (discApplyForVal === 'UnitPrice')    return 'UnitPrice';
    return null;
}

// ── Customer discount (DiscountPercent field) ─────────────────────────────────

/**
 * Apply the customer's DiscountPercent to globalDiscount.
 * Only called when no price list is active for this customer.
 * @param {object} custData
 */
function _plApplyCustDiscount(custData) {
    var pct = parseFloat(custData ? custData.discountPercent || 0 : 0);
    if (pct <= 0) return;
    _custDiscountApplied = true;
    $('#globalDiscount').val(pct).trigger('input');
}

// ── Load price lists from Upstash ────────────────────────────────────────────

/**
 * Fetch all active price lists from Upstash into _plAllLists.
 * Called once on DOMContentLoaded.
 */
function _plLoad() {
    if (typeof UpstashService === 'undefined' || !UpstashService.isEnabled()) return;
    UpstashService.get(UpstashService.orgKey('price-lists')).then(function (data) {
        if (!Array.isArray(data) || !data.length) {
            // Flag says lists exist but cache is empty — self-heal from DB
            $.ajax({
                url:    '/products/syncPriceListCache',
                method: 'POST',
                data:   { [CsrfName]: CsrfToken },
                success: function (resp) {
                    if (!resp.Error && Array.isArray(resp.Lists) && resp.Lists.length) {
                        _plAllLists = resp.Lists.filter(function (pl) { return pl.Status === 1; });
                    }
                }
            });
            return;
        }
        // Already sorted Priority DESC by PHP; keep that order
        _plAllLists = data.filter(function (pl) { return pl.Status === 1; });
    }).catch(function () {});
}

// ── Resolution ───────────────────────────────────────────────────────────────

/**
 * Return all price lists that apply to the given customer.
 * Filters by: Status=1, date range, AssignedToType match.
 * @param {object} custData  customer data object from custCache
 * @returns {Array}
 */
function _plResolveForCustomer(custData) {
    if (!_plAllLists || !custData) return [];
    var now         = new Date();
    var custTypeUID = parseInt(custData.customerTypeUID || 0, 10);
    var groupUID    = parseInt(custData.groupUID        || 0, 10);
    var custUID     = parseInt(custData.id              || 0, 10);

    return _plAllLists.filter(function (pl) {
        if (pl.ValidFrom && new Date(pl.ValidFrom) > now) return false;
        if (pl.ValidTo   && new Date(pl.ValidTo + 'T23:59:59') < now) return false;

        if (pl.AssignedToType === 'All') return true;

        if (pl.AssignedToType === 'Groups') {
            return (pl.Assignments || []).some(function (a) {
                return a.RefType === 'Group' && a.RefUID === groupUID;
            });
        }

        if (pl.AssignedToType === 'Customers') {
            return (pl.Assignments || []).some(function (a) {
                return a.RefType === 'Customer' && a.RefUID === custUID;
            });
        }

        return false;
    });
}

// ── Discount lookup ──────────────────────────────────────────────────────────

/**
 * Get the discount entry for a given CustomerTypeUID from a price list.
 * @param {object} pl
 * @param {number} custTypeUID
 * @returns {object|null}  {CustomerTypeUID, DiscountType, DiscountValue} or null
 */
function _plGetDiscount(pl, custTypeUID) {
    if (!pl.Discounts || !pl.Discounts.length) return null;
    // Exact match first (type-specific discount)
    var exact = pl.Discounts.find(function (d) {
        return parseInt(d.CustomerTypeUID, 10) === custTypeUID;
    });
    if (exact) return exact;
    // Fall back to "All Selected Customers" entry (CustomerTypeUID = 0 or null)
    return pl.Discounts.find(function (d) {
        return !d.CustomerTypeUID || parseInt(d.CustomerTypeUID, 10) === 0;
    }) || null;
}

/**
 * Get the price for a product from a Specific Products price list.
 * Rules are sorted by MinQty DESC on the server, so first match wins.
 * @param {object} pl
 * @param {number} productUID
 * @param {number} custTypeUID
 * @param {number} qty
 * @returns {number|null}  price (tax-inclusive) or null if not in price list
 */
function _plGetRulePrice(pl, productUID, custTypeUID, qty) {
    if (!pl.Rules || !pl.Rules.length) return null;
    qty = parseInt(qty) || 1;
    var matches = (pl.Rules || []).filter(function (r) {
        return r.ProductUID     === productUID  &&
               r.CustomerTypeUID === custTypeUID &&
               r.MinQty <= qty &&
               (r.MaxQty === null || r.MaxQty >= qty);
    });
    if (!matches.length) return null;
    matches.sort(function (a, b) { return b.MinQty - a.MinQty; });
    return matches[0].Price;
}

// ── Apply price list to a single row ─────────────────────────────────────────

/**
 * Apply price list pricing to one cart row.
 * Specific: sets exact price (tax-inclusive) from Rules.
 * All Products: sets discount from Discounts (only when _plAutoDiscount = true).
 * @param {number} rowId
 * @param {object} pl
 * @param {number} custTypeUID
 */
function _plApplyToRow(rowId, pl, custTypeUID) {
    rowId = parseInt(rowId, 10);
    var item = billManager.getItemById(rowId);
    if (!item || item.isComposite) return;

    if (pl.Scope === 'Specific') {
        var price = _plGetRulePrice(pl, rowId, custTypeUID, parseInt(item.quantity) || 1);
        if (price === null) return; // product not in this price list — leave untouched

        var sp  = parseFloat(price) || 0;
        var tax = parseFloat(item.taxPercent) || 0;
        var up  = (tax > 0) ? sp / (1 + tax / 100) : sp;

        var updatedItem           = Object.assign({}, item);
        updatedItem.orgunitprice  = up;
        updatedItem.orgselngprice = sp;
        updatedItem.unitPrice     = up;
        updatedItem.sellingPrice  = sp;
        updatedItem._lastChanged  = 'unitPrice';

        var recalc = billManager.calculateRowItem(updatedItem);
        billManager.updateItemInStorage(rowId, recalc);
        updateTableRow(recalc);
        _plPriceSource[rowId] = 'pricelist';
        _plMarkRowTinted(rowId, true);

    } else if (pl.Scope === 'All' && _plAutoDiscount) {
        var disc = _plGetDiscount(pl, custTypeUID);
        if (!disc || !disc.DiscountValue) return;

        var updatedItem2           = Object.assign({}, item);
        updatedItem2.discount      = disc.DiscountValue;
        updatedItem2.discountType  = disc.DiscountType === 'Amount' ? 'Amount' : 'Percentage';
        updatedItem2._lastChanged  = 'discount';

        var recalc2 = billManager.calculateRowItem(updatedItem2);
        billManager.updateItemInStorage(rowId, recalc2);
        updateTableRow(recalc2);
        _plPriceSource[rowId] = 'pricelist';
        _plMarkRowTinted(rowId, true);

    }
}

// ── Apply price list to all cart rows ────────────────────────────────────────

/**
 * Apply price list to every item in the cart and update the Discount On selector.
 * @param {object} pl
 * @param {object} custData
 */
function _plApplyToAllRows(pl, custData) {
    if (!pl || !custData) return;
    var custTypeUID = parseInt(custData.customerTypeUID || 0, 10);

    if (pl.Scope === 'All') {
        var discApplyForVal = _plToDiscApplyFor(pl.GlobalBasedOn);
        if (discApplyForVal) {
            // Set without triggering change (we handle recalc ourselves below)
            $('#discApplyFor').val(discApplyForVal);
            _plAutoDiscount = true;
        } else {
            _plAutoDiscount = false;
        }
    } else {
        _plAutoDiscount = false; // Specific scope: no global discount
    }

    // Catalog map needed for Scope=Specific to detect manually adjusted prices.
    var rawMap = (typeof ProductAppend !== 'undefined') ? ProductAppend.getRawMap() : null;

    var items = billManager.getAllItems();
    items.forEach(function (item) {
        var rowId = parseInt(item.id, 10);

        if (pl.Scope === 'All') {
            // Rule 1: item already has a manual discount → don't overwrite it.
            if (parseFloat(item.discount || 0) !== 0) return;
        } else if (pl.Scope === 'Specific') {
            // Rule 2: current selling price differs from catalog → user adjusted it → skip.
            if (rawMap) {
                var prod      = rawMap[String(rowId)] || rawMap[rowId];
                var catalogSP = prod ? parseFloat(prod.SellingPrice || 0) : null;
                var currentSP = parseFloat(item.orgselngprice || 0);
                if (catalogSP !== null && Math.abs(currentSP - catalogSP) > 0.01) return;
            }
        }

        _plApplyToRow(rowId, pl, custTypeUID);
    });

    billManager.updateSummary();
}

// ── Apply price list to a newly added row (called from pushBillItems) ─────────

/**
 * Called immediately after a new product row is added and rendered.
 * Applies price list price (Specific scope) or discount (All scope, if auto).
 * @param {number} rowId
 */
function _plTransApplyToNewRow(rowId) {
    if (!_plActivePriceList || !_plCurrentCustData) return;
    var pl          = _plActivePriceList;
    var custTypeUID = parseInt(_plCurrentCustData.customerTypeUID || 0, 10);

    if (pl.Scope === 'Specific') {
        _plApplyToRow(parseInt(rowId, 10), pl, custTypeUID);
        billManager.updateSummary();
    } else if (pl.Scope === 'All' && _plAutoDiscount) {
        _plApplyToRow(parseInt(rowId, 10), pl, custTypeUID);
        billManager.updateSummary();
    }
    // If Scope = All but _plAutoDiscount = false → leave new row at catalog price
}

// ── Revert all rows to catalog prices ────────────────────────────────────────

/**
 * Remove all price list pricing from every cart row and restore catalog prices.
 */
function _plRevert() {
    if (!_plActivePriceList) {
        _plCurrentCustData = null;
        _plPriceSource     = {};
        _plAutoDiscount    = false;
        _plHideChip();
        return;
    }

    var wasPL    = _plActivePriceList;
    var wasCust  = _plCurrentCustData;  // save before clearing
    _plActivePriceList = null;
    _plCurrentCustData = null;
    _plAutoDiscount    = false;

    // custTypeUID needed by both scope checks — hoist outside the if blocks.
    var custTypeUID = wasCust ? parseInt(wasCust.customerTypeUID || 0, 10) : 0;

    // Pre-compute Scope=All comparison values once (avoid per-row recalculation)
    var plExpectedVal   = null;
    var plExpectedScope = null;
    if (wasPL.Scope === 'All') {
        var plDisc = _plGetDiscount(wasPL, custTypeUID);
        if (plDisc) {
            plExpectedVal   = parseFloat(plDisc.DiscountValue || 0);
            plExpectedScope = _plToDiscApplyFor(wasPL.GlobalBasedOn) || '';
        }
    }

    billManager.getAllItems().forEach(function (item) {
        var rowId = parseInt(item.id, 10);
        if (_plPriceSource[rowId] !== 'pricelist') return;

        // Always remove tint — price list is no longer active regardless of whether the
        // price value is reverted.
        _plMarkRowTinted(rowId, false);

        if (wasPL.Scope === 'Specific') {
            var saved = _plOrigPrices[rowId];
            if (!saved) return;

            // Use _plGetRulePrice with the item's CURRENT qty to get the tier that was
            // actually applied. specificRuleMap would only hold one rule per product, so
            // it would give the wrong tier for qty-break products — this is correct.
            var appliedPrice = _plGetRulePrice(wasPL, rowId, custTypeUID, parseInt(item.quantity) || 1);
            if (appliedPrice !== null) {
                var currentSP = parseFloat(item.orgselngprice || 0);
                if (Math.abs(currentSP - parseFloat(appliedPrice)) > 0.01) return;
            }

            var orig          = Object.assign({}, item);
            orig.orgunitprice  = saved.orgunitprice;
            orig.orgselngprice = saved.orgselngprice;
            orig.unitPrice     = saved.unitPrice;
            orig.sellingPrice  = saved.sellingPrice;
            orig._lastChanged  = 'unitPrice';
            var recalc = billManager.calculateRowItem(orig);
            billManager.updateItemInStorage(rowId, recalc);
            updateTableRow(recalc);

        } else if (wasPL.Scope === 'All') {
            if (plExpectedVal === null) return;

            // Only revert if BOTH the discount value AND the discount scope still match
            // what the price list originally applied. If either differs, the user manually
            // changed it — leave it untouched.
            var currentDisc  = parseFloat(item.discount || 0);
            var currentScope = item.discount_scope || '';
            if (Math.abs(currentDisc - plExpectedVal) > 0.001) return;
            if (plExpectedScope && currentScope !== plExpectedScope)  return;

            var orig2          = Object.assign({}, item);
            orig2.discount     = 0;
            orig2.discountType = 'Percentage';
            orig2._lastChanged = 'discount';
            var recalc2 = billManager.calculateRowItem(orig2);
            billManager.updateItemInStorage(rowId, recalc2);
            updateTableRow(recalc2);
        }
    });

    _plPriceSource  = {};
    _plOrigPrices   = {};
    _plHideChip();
    billManager.updateSummary();
}

// ── Auto-discount flag sync ───────────────────────────────────────────────────

/**
 * Re-evaluate _plAutoDiscount based on current discApplyFor vs GlobalBasedOn.
 * Called whenever #discApplyFor changes (user or programmatic).
 */
function _plCheckAutoDiscount() {
    if (!_plActivePriceList || _plActivePriceList.Scope !== 'All') {
        _plAutoDiscount = false;
        return;
    }
    var currentVal    = $('#discApplyFor').val() || '';
    var currentMapped = _plFromDiscApplyFor(currentVal);
    _plAutoDiscount   = (currentMapped === _plActivePriceList.GlobalBasedOn);
}

// ── Price list select modal ───────────────────────────────────────────────────

/**
 * Show a modal listing multiple matching price lists for the user to pick one.
 * @param {Array}  matches
 * @param {object} custData
 */
function _plShowSelectModal(matches, custData) {
    var cur         = (typeof currency !== 'undefined' ? currency : '₹');
    var custTypeUID = parseInt(custData.customerTypeUID || 0, 10);

    // find which match index is currently applied (if any)
    var activeIdx = -1;
    if (_plActivePriceList) {
        for (var ai = 0; ai < matches.length; ai++) {
            if (matches[ai].Name === _plActivePriceList.Name &&
                matches[ai].Priority === _plActivePriceList.Priority) {
                activeIdx = ai;
                break;
            }
        }
    }

    function _plEsc(v) { return $('<span>').text(String(v || '')).html(); }

    function _plFmtDate(s) {
        if (!s) return '—';
        var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        var p = String(s).split(/[-T ]/);
        if (p.length < 3) return s;
        return parseInt(p[2], 10) + ' ' + months[parseInt(p[1], 10) - 1] + ' ' + p[0];
    }

    function _buildCard(pl, i) {
        var isTop      = (i === 0);
        var isSelected = (i === activeIdx);

        // border/accent
        var accentColor = isSelected ? '#16a34a' : (isTop ? '#696cff' : '#adb5bd');
        var headerBg    = isSelected ? '#f0fdf4'  : (isTop ? '#f0efff' : '#f8f9fa');
        var headerColor = isSelected ? '#14532d'  : (isTop ? '#3730a3' : '#495057');

        // badges
        var badges = '';
        if (isTop)      badges += '<span class="badge bg-primary ms-2" style="font-size:.65rem;font-weight:600;vertical-align:middle;">Top Priority</span>';
        if (isSelected) badges += '<span class="badge ms-2" style="font-size:.65rem;font-weight:600;vertical-align:middle;background:#16a34a;">Currently Applied</span>';

        var priLabel = '<span style="font-size:.72rem;font-weight:700;color:' + accentColor + ';background:#fff;border:1.5px solid ' + accentColor + ';border-radius:20px;padding:2px 10px;white-space:nowrap;">Priority ' + pl.Priority + '</span>';

        // scope
        var scopeColor = pl.Scope === 'All' ? '#696cff' : '#0097a7';
        var scopeText  = pl.Scope === 'All' ? 'All Products' : 'Specific Products';
        var scopeIcon  = pl.Scope === 'All' ? 'bx-grid-alt' : 'bx-list-check';

        // based on
        var basedOnMap  = { SellingPrice: 'Selling Price', UnitPrice: 'Unit Price', PurchasePrice: 'Purchase Price' };
        var basedOnText = basedOnMap[pl.GlobalBasedOn] || _plEsc(pl.GlobalBasedOn);

        // assignment
        var assignIcon, assignText;
        if (pl.AssignedToType === 'All') {
            assignIcon = 'bx-globe'; assignText = 'All Customers';
        } else if (pl.AssignedToType === 'Groups') {
            var gCnt = (pl.Assignments || []).filter(function (a) { return a.RefType === 'Group'; }).length;
            assignIcon = 'bx-group'; assignText = gCnt + ' Customer Group' + (gCnt !== 1 ? 's' : '');
        } else {
            var cCnt = (pl.Assignments || []).filter(function (a) { return a.RefType === 'Customer'; }).length;
            assignIcon = 'bx-user-check'; assignText = cCnt + ' Specific Customer' + (cCnt !== 1 ? 's' : '');
        }

        // validity
        var validityValue;
        if (!pl.ValidFrom && !pl.ValidTo) {
            validityValue = '<span style="color:#6c757d;font-size:.8rem;">Always active</span>';
        } else {
            var vFrom = _plFmtDate(pl.ValidFrom);
            var vTo   = pl.ValidTo ? _plFmtDate(pl.ValidTo) : '<span style="color:#6c757d;">No expiry</span>';
            validityValue = '<span style="font-size:.8rem;color:#212529;">' + vFrom + '</span>' +
                            '<i class="bx bx-right-arrow-alt mx-1 text-muted" style="vertical-align:middle;"></i>' +
                            '<span style="font-size:.8rem;color:#212529;">' + vTo + '</span>';
        }

        // highlight block — customer-specific discount or rule info
        var hlBg, hlBorder, hlIcon, hlIconColor, hlTitle, hlBody;
        if (pl.Scope === 'All') {
            var disc = _plGetDiscount(pl, custTypeUID);
            if (disc && parseFloat(disc.DiscountValue || 0) > 0) {
                // Customer's type has a configured discount — show it in green.
                var dv = disc.DiscountType === 'Amount' ? (cur + ' ' + disc.DiscountValue + ' off') : (disc.DiscountValue + '% off');
                hlBg = '#f0fdf4'; hlBorder = '#bbf7d0'; hlIconColor = '#16a34a';
                hlIcon  = 'bx-badge-check';
                hlTitle = 'Your Discount';
                hlBody  = _plEsc(dv) + ' on ' + _plEsc(basedOnText);
            } else if (disc) {
                // Matched the customer's type but discount is explicitly 0 (No Discount).
                hlBg = '#f8f9fa'; hlBorder = '#dee2e6'; hlIconColor = '#6c757d';
                hlIcon  = 'bx-info-circle';
                hlTitle = 'Discount';
                hlBody  = 'No discount configured for your customer type';
            } else {
                // Customer's type is not listed — show what IS configured so the user isn't confused.
                var activeDvList = (pl.Discounts || [])
                    .filter(function (d) { return parseFloat(d.DiscountValue || 0) > 0; })
                    .map(function (d) {
                        return d.DiscountType === 'Amount'
                            ? (cur + ' ' + d.DiscountValue + ' off')
                            : (d.DiscountValue + '%');
                    });
                if (activeDvList.length) {
                    hlBg = '#fff7ed'; hlBorder = '#fed7aa'; hlIconColor = '#c2410c';
                    hlIcon  = 'bx-info-circle';
                    hlTitle = 'Discount';
                    hlBody  = _plEsc(activeDvList.join(' · ')) + ' (varies by customer type — none set for your type)';
                } else {
                    hlBg = '#f8f9fa'; hlBorder = '#dee2e6'; hlIconColor = '#6c757d';
                    hlIcon  = 'bx-info-circle';
                    hlTitle = 'Discount';
                    hlBody  = 'No discount configured';
                }
            }
        } else {
            var applicable = (pl.Rules || []).filter(function (r) { return r.CustomerTypeUID === custTypeUID; });
            var prodUIDs   = {};
            applicable.forEach(function (r) { prodUIDs[r.ProductUID] = 1; });
            var prodCnt    = Object.keys(prodUIDs).length;
            if (applicable.length) {
                var hasTiers = applicable.some(function (r) { return r.MinQty > 1; });
                hlBg = '#f0f9ff'; hlBorder = '#bae6fd'; hlIconColor = '#0369a1';
                hlIcon  = 'bx-list-ul';
                hlTitle = 'Product Rules';
                hlBody  = applicable.length + ' rule' + (applicable.length !== 1 ? 's' : '') +
                          ' across ' + prodCnt + ' product' + (prodCnt !== 1 ? 's' : '') +
                          (hasTiers ? ' &mdash; includes qty-based tiers' : '');
            } else {
                hlBg = '#f8f9fa'; hlBorder = '#dee2e6'; hlIconColor = '#6c757d';
                hlIcon  = 'bx-info-circle';
                hlTitle = 'Product Rules';
                hlBody  = 'No product rules configured for your customer type';
            }
        }

        // apply button
        var applyBtnStyle, applyBtnText;
        if (isSelected) {
            applyBtnStyle = 'background:#16a34a;border-color:#16a34a;color:#fff;font-size:.8rem;';
            applyBtnText  = '<i class="bx bx-check me-1"></i>Currently Applied';
        } else {
            applyBtnStyle = isTop
                ? 'font-size:.8rem;'
                : 'font-size:.8rem;';
            applyBtnText  = '<i class="bx bx-check me-1"></i>Apply this Price List';
        }
        var applyBtnCls = (isSelected || isTop) ? 'btn-primary' : 'btn-outline-primary';

        // ── assemble ──────────────────────────────────────────────────────────
        var h = '';
        h += '<div class="pl-select-card" data-pl-idx="' + i + '" style="border:1.5px solid ' + accentColor + ';border-left:4px solid ' + accentColor + ';border-radius:8px;cursor:pointer;transition:box-shadow .15s;margin-bottom:0;">';

            // header row
            h += '<div style="padding:10px 16px;background:' + headerBg + ';display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #e9ecef;border-radius:6px 6px 0 0;">';
                h += '<div style="display:flex;align-items:center;flex-wrap:wrap;gap:4px;">';
                    h += '<span style="font-size:.9rem;font-weight:700;color:' + headerColor + ';">' + _plEsc(pl.Name) + '</span>';
                    h += badges;
                h += '</div>';
                h += priLabel;
            h += '</div>';

            // body — info grid
            h += '<div style="padding:12px 16px 0 16px;background:#fff;">';
                h += '<div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:12px;">';

                    // scope
                    h += '<div>';
                        h += '<div class="vtm-card-label"><i class="bx ' + scopeIcon + ' me-1"></i>Scope</div>';
                        h += '<div style="font-size:.82rem;font-weight:600;color:' + scopeColor + ';">' + _plEsc(scopeText) + '</div>';
                    h += '</div>';

                    // based on
                    h += '<div>';
                        h += '<div class="vtm-card-label"><i class="bx bx-calculator me-1"></i>Based On</div>';
                        h += '<div style="font-size:.82rem;color:#212529;">' + _plEsc(basedOnText) + '</div>';
                    h += '</div>';

                    // assigned to
                    h += '<div>';
                        h += '<div class="vtm-card-label"><i class="bx ' + assignIcon + ' me-1"></i>Assigned To</div>';
                        h += '<div style="font-size:.82rem;color:#212529;">' + _plEsc(assignText) + '</div>';
                    h += '</div>';

                    // validity
                    h += '<div>';
                        h += '<div class="vtm-card-label"><i class="bx bx-calendar me-1"></i>Valid Period</div>';
                        h += '<div>' + validityValue + '</div>';
                    h += '</div>';

                h += '</div>'; // end grid

                // highlight block
                h += '<div style="margin-top:10px;padding:8px 12px;background:' + hlBg + ';border:1px solid ' + hlBorder + ';border-radius:6px;display:flex;align-items:center;gap:8px;">';
                    h += '<i class="bx ' + hlIcon + '" style="color:' + hlIconColor + ';font-size:1.1rem;flex-shrink:0;"></i>';
                    h += '<div>';
                        h += '<div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:' + hlIconColor + ';margin-bottom:1px;">' + hlTitle + '</div>';
                        h += '<div style="font-size:.82rem;font-weight:600;color:#212529;">' + hlBody + '</div>';
                    h += '</div>';
                h += '</div>';

            h += '</div>'; // end body

            // footer — apply button
            h += '<div style="padding:8px 16px 10px 16px;background:#fff;display:flex;justify-content:flex-end;">';
                h += '<button type="button" class="btn btn-sm ' + applyBtnCls + ' pl-card-apply-btn" data-pl-idx="' + i + '" style="' + applyBtnStyle + '">' + applyBtnText + '</button>';
            h += '</div>';

        h += '</div>'; // end card
        return h;
    }

    var cards = matches.map(_buildCard).join('');

    $('#plSelectMeta').text(
        matches.length + ' price list' + (matches.length > 1 ? 's' : '') +
        ' match this customer — top priority applies first'
    );
    $('#plSelectCards').html(cards);

    var $modal = $('#plSelectModal');
    $modal.modal('show');

    $modal.off('click.plcard').on('click.plcard', '.pl-card-apply-btn', function (e) {
        e.stopPropagation();
        var idx = parseInt($(this).data('pl-idx'), 10);
        $modal.modal('hide');
        _plActivePriceList = matches[idx];
        _plCurrentCustData = custData;
        _plApplyToAllRows(_plActivePriceList, _plCurrentCustData);
        _plShowChip(_plActivePriceList);
    });

    $modal.off('click.plselect').on('click.plselect', '.pl-select-card', function () {
        var idx = parseInt($(this).data('pl-idx'), 10);
        $modal.modal('hide');
        _plActivePriceList = matches[idx];
        _plCurrentCustData = custData;
        _plApplyToAllRows(_plActivePriceList, _plCurrentCustData);
        _plShowChip(_plActivePriceList);
    });

    $modal.off('click.plnone').on('click.plnone', '#plSelectNone', function () {
        $modal.modal('hide');
        var savedCustData = _plCurrentCustData || custData;
        _plRevert();
        _plCurrentCustData = savedCustData;
        _plApplyCustDiscount(custData);
        _plShowNoneChip();
    });
}

// ── Chip UI ──────────────────────────────────────────────────────────────────

/**
 * Render the active price list chip in #plChipWrap.
 * @param {object} pl
 */
function _plShowChip(pl) {
    var $wrap = $('#plChipWrap');
    if (!$wrap.length) return;
    var locked = typeof _isEdit !== 'undefined' && _isEdit;
    var h = '';
    if (locked) {
        h += '<span class="pl-active-chip pl-chip-locked" title="Price list is locked during edit" onclick="_plLockedChipClick()">';
        h += '<i class="bx bx-purchase-tag"></i>';
        h += '<span>' + pl.Name + '</span>';
        h += '<i class="bx bx-lock-alt" style="font-size:.75rem;opacity:.55;margin-left:2px;"></i>';
        h += '</span>';
    } else {
        h += '<span class="pl-active-chip" title="Click to change price list" onclick="_plChangeChipClick()">';
        h += '<i class="bx bx-purchase-tag"></i>';
        h += '<span>' + pl.Name + '</span>';
        h += '<i class="bx bx-x pl-chip-x" onclick="event.stopPropagation();_plRemoveChipClick()"></i>';
        h += '</span>';
    }
    $wrap.html(h).removeClass('d-none');
}

function _plHideChip() {
    var $wrap = $('#plChipWrap');
    if ($wrap.length) $wrap.addClass('d-none').empty();
}

/**
 * Show a muted "No Price List Applied" chip when the user explicitly chose
 * to skip all price lists. Clicking reopens the price list modal.
 * @returns {void}
 */
function _plShowNoneChip() {
    var $wrap = $('#plChipWrap');
    if (!$wrap.length) return;
    var h = '<span class="pl-active-chip pl-chip-none" title="Click to select a price list" onclick="_plChangeChipClick()">';
    h += '<i class="bx bx-purchase-tag"></i>';
    h += '<span>No Price List Applied</span>';
    h += '</span>';
    $wrap.html(h).removeClass('d-none');
}

// Edit mode: chip is locked — show error instead of allowing change
function _plLockedChipClick() {
    showToastNotification(t('toast_pricelist_locked', 'Price list cannot be changed while editing a transaction. Create a new transaction to apply a different price list.'), 'warning');
}

// Chip click → change price list (re-open selection modal or auto-apply if single match)
function _plChangeChipClick() {
    if (typeof _isEdit !== 'undefined' && _isEdit) { _plLockedChipClick(); return; }
    if (!_plCurrentCustData) return;
    var matches = _plResolveForCustomer(_plCurrentCustData);
    if (!matches.length) {
        showToastNotification(t('toast_no_pricelist', 'No price lists available for this customer.'), 'info');
        return;
    }
    if (matches.length === 1) {
        // Only one option — re-confirm selection
        _plActivePriceList = matches[0];
        _plApplyToAllRows(_plActivePriceList, _plCurrentCustData);
        _plShowChip(_plActivePriceList);
    } else {
        _plShowSelectModal(matches, _plCurrentCustData);
    }
}

// Chip X → remove price list entirely
function _plRemoveChipClick() {
    if (typeof _isEdit !== 'undefined' && _isEdit) { _plLockedChipClick(); return; }
    _plRevert();
}

// ── Row visual tinting ────────────────────────────────────────────────────────

/**
 * Add/remove the blue tint class on the unit-price input of a row.
 * @param {number}  rowId
 * @param {boolean} on
 */
function _plMarkRowTinted(rowId, on) {
    var $inp = $('#bm_' + rowId + '_unitPrice');
    if (!$inp.length) return;
    var $wrap = $inp.closest('.input-group');
    var $td   = $inp.closest('td');
    if (on) {
        $wrap.addClass('pl-price-tinted');
        if (!$td.find('.pl-row-indicator').length) {
            $td.append('<div class="pl-row-indicator"><i class="bx bx-purchase-tag-alt"></i> Price List</div>');
        }
    } else {
        $wrap.removeClass('pl-price-tinted');
        $td.find('.pl-row-indicator').remove();
    }
}

// ── Entry points (called from transactions.js) ────────────────────────────────

/**
 * Called on customer select2:select.
 * Resolves matching price lists and auto-applies or shows modal.
 * @param {object} custData  entry from custCache (must have .customerTypeUID, .groupUID, .id)
 */
function _plTransResolve(custData) {
    _custDiscountApplied = false;
    _plCurrentCustData   = custData;
    _plPriceSource       = {};
    _plOrigPrices        = {};

    if (_plActivePriceList) {
        _plActivePriceList = null;
        _plHideChip();
    }

    // If price list data hasn't loaded (Upstash unavailable or no lists configured),
    // fall back to customer-level discount immediately.
    if (!_plAllLists) {
        _plApplyCustDiscount(custData);
        return;
    }

    var matches = _plResolveForCustomer(custData);

    // No price list matches this customer — apply their DiscountPercent instead.
    if (!matches.length) {
        _plApplyCustDiscount(custData);
        return;
    }

    if (matches.length === 1) {
        _plActivePriceList = matches[0];
        _plApplyToAllRows(_plActivePriceList, _plCurrentCustData);
        _plShowChip(_plActivePriceList);
    } else {
        _plShowSelectModal(matches, custData);
    }
}

/**
 * Called on customer select2:clear.
 * Reverts all price list pricing and the customer-level discount if it was auto-applied.
 */
function _plTransClear() {
    _plRevert();
    if (_custDiscountApplied) {
        _custDiscountApplied = false;
        $('#globalDiscount').val('0').trigger('input');
    }
}

/**
 * Called on edit page load to restore the price list chip without re-applying discounts.
 * Item discounts are already saved in DB — only the UI state needs to be rebuilt.
 * @param {object} custData  customer entry built from Upstash cache
 */
function _plTransEditRestore(custData) {
    _plCurrentCustData = custData;

    // Only restore if this quotation was saved with a price list.
    // If no saved price list → do nothing at all (no modal, no resolution, no chip).
    if (typeof _editPriceListData === 'undefined' || !_editPriceListData) return;

    var savedPL;
    if (typeof _editPriceListData === 'object') {
        savedPL = _editPriceListData;
    } else {
        try { savedPL = JSON.parse(_editPriceListData); } catch (e) { return; }
    }
    if (!savedPL) return;

    _plActivePriceList = savedPL;

    if (savedPL.Scope === 'All') {
        // Mark every cart item so _plRevert knows to zero their discounts on customer clear.
        billManager.getAllItems().forEach(function (item) {
            _plPriceSource[parseInt(item.id, 10)] = 'pricelist';
        });
        // Sync #discApplyFor silently — no .trigger('change') so existing rows stay untouched.
        var discVal = _plToDiscApplyFor(savedPL.GlobalBasedOn);
        if (discVal) {
            $('#discApplyFor').val(discVal);
            // Stamp discount_scope on each item so _plRevert can compare it during customer clear.
            // Items were added before this async restore ran, so their discount_scope reflects the
            // default #discApplyFor value (not the price list scope) — fix that silently here.
            billManager.getAllItems().forEach(function (item) {
                item.discount_scope = discVal;
            });
        }
        _plCheckAutoDiscount();
        _plShowChip(savedPL);

    } else if (savedPL.Scope === 'Specific') {
        // Build productUID → rule lookup from saved price list rules.
        var ruleMap = {};
        (savedPL.Rules || []).forEach(function (r) {
            ruleMap[parseInt(r.ProductUID, 10)] = r;
        });

        // Mark only items that matched a rule as 'pricelist'.
        billManager.getAllItems().forEach(function (item) {
            var rowId = parseInt(item.id, 10);
            if (ruleMap[rowId]) _plPriceSource[rowId] = 'pricelist';
        });

        // Populate _plOrigPrices with catalog prices so _plRevert can restore them.
        // Try ProductAppend raw map first (already loaded); fall back to direct Upstash call.
        function _storeOrigPrices(productMap) {
            billManager.getAllItems().forEach(function (item) {
                var rowId = parseInt(item.id, 10);
                if (_plPriceSource[rowId] !== 'pricelist') return;
                var prod = productMap[String(rowId)] || productMap[rowId];
                if (!prod) return;
                var taxPct    = parseFloat(item.taxPercent || 0);
                var catalogSP = parseFloat(prod.SellingPrice || 0);
                var catalogUP = taxPct > 0 ? catalogSP / (1 + taxPct / 100) : catalogSP;
                _plOrigPrices[rowId] = {
                    orgunitprice  : catalogUP,
                    orgselngprice : catalogSP,
                    unitPrice     : catalogUP,
                    sellingPrice  : catalogSP,
                };
            });
        }

        var rawMap = (typeof ProductAppend !== 'undefined') ? ProductAppend.getRawMap() : null;
        if (rawMap) {
            _storeOrigPrices(rawMap);
        } else if (typeof UpstashService !== 'undefined' && UpstashService.isEnabled()) {
            UpstashService.hgetall(UpstashService.orgKey('products')).then(function (map) {
                if (map && typeof map === 'object') _storeOrigPrices(map);
            }).catch(function () {});
        }

        _plShowChip(savedPL);
    }
}

/**
 * Append PriceListUID and PriceListData to the form's FormData before AJAX submit.
 * @param {FormData} fd
 */
function _plTransInjectFormData(fd) {
    fd.append('PriceListUID',  _plActivePriceList ? _plActivePriceList.PriceListUID  : 0);
    fd.append('PriceListData', _plActivePriceList ? JSON.stringify(_plActivePriceList) : '');
}

/**
 * Called from transactions.js whenever the user manually changes the qty of a cart row.
 * Re-looks up the correct price tier for Specific-scope price lists and updates the row
 * price if the new qty falls into a different tier.
 * @param {number} rowId
 * @param {number} newQty
 */
function _plTransQtyChange(rowId, newQty) {
    if (!_plActivePriceList || _plActivePriceList.Scope !== 'Specific') return;
    if (_plPriceSource[rowId] !== 'pricelist') return;
    if (!_plCurrentCustData) return;

    var custTypeUID = parseInt(_plCurrentCustData.customerTypeUID || 0, 10);
    var newPrice    = _plGetRulePrice(_plActivePriceList, rowId, custTypeUID, newQty);
    if (newPrice === null) return; // product not in this price list

    var item = billManager.getItemById(rowId);
    if (!item) return;

    var sp  = parseFloat(newPrice) || 0;
    var tax = parseFloat(item.taxPercent) || 0;
    var up  = tax > 0 ? sp / (1 + tax / 100) : sp;

    // Nothing to do if tier price hasn't changed
    if (Math.abs(sp - parseFloat(item.orgselngprice || 0)) < 0.001) return;

    // Apply the new tier price; qty is already updated in storage by billManager.updateItem
    var updated          = Object.assign({}, item);
    updated.orgunitprice  = up;
    updated.orgselngprice = sp;
    updated.unitPrice     = up;
    updated.sellingPrice  = sp;
    updated._lastChanged  = 'unitPrice';

    var recalc = billManager.calculateRowItem(updated);
    billManager.updateItemInStorage(rowId, recalc);
    updateTableRow(recalc);
    billManager.updateSummary();
    if (typeof updateItemTaxBreakdown === 'function') updateItemTaxBreakdown();
}

// ── Catalog price snapshot store ─────────────────────────────────────────────
// Stores original catalog prices per rowId so _plRevert can restore them.
var _plOrigPrices = {}; // rowId → {orgunitprice, orgselngprice, unitPrice, sellingPrice}

// Override _plApplyToRow for Specific scope to also snapshot catalog price before overwriting
(function () {
    var _origApplyToRow = _plApplyToRow;
    _plApplyToRow = function (rowId, pl, custTypeUID) {
        rowId = parseInt(rowId, 10);
        if (pl.Scope === 'Specific' && !_plOrigPrices[rowId]) {
            var item = billManager.getItemById(rowId);
            if (item) {
                _plOrigPrices[rowId] = {
                    orgunitprice  : item.orgunitprice,
                    orgselngprice : item.orgselngprice,
                    unitPrice     : item.unitPrice,
                    sellingPrice  : item.sellingPrice,
                };
            }
        }
        _origApplyToRow(rowId, pl, custTypeUID);
    };
}());

// ── Init ─────────────────────────────────────────────────────────────────────
$(function () {
    _plLoad();

    // Watch discApplyFor changes to update _plAutoDiscount flag
    $(document).on('change', '#discApplyFor', function () {
        _plCheckAutoDiscount();
    });

    // Mark a row as 'manual' when user edits its price directly
    $(document).on('input', '.updateAllBillAmounts', function () {
        var fieldName = $(this).attr('name') || '';
        if (fieldName.indexOf('_unitPrice') === -1 && fieldName.indexOf('_sellingPrice') === -1) return;
        var $row  = $(this).closest('tr');
        var rowId = parseInt($row.data('id'), 10);
        if (!isNaN(rowId) && _plPriceSource[rowId] === 'pricelist') {
            _plPriceSource[rowId] = 'manual';
            _plMarkRowTinted(rowId, false);
        }
    });
});
