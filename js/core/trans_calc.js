'use strict';

/* ── TransCalc — pure billing calculation helpers (no DOM, no jQuery) ───────
 * Used by bill_manager.js (tax split + summary aggregation)
 * Used by pos-billing.js  (summary from cart items)
 * ─────────────────────────────────────────────────────────────────────────── */

var TransCalc = (function () {

    /**
     * Split a tax amount into CGST / SGST / IGST based on the interstate flag.
     * Uses 3 decimal places for the CGST split so symmetric rates (9%+9%) yield
     * identical amounts and not an off-by-0.001 pair.
     *
     * @param {number}  taxAmount
     * @param {number}  cgstPct
     * @param {number}  sgstPct
     * @param {boolean} isInterState
     * @returns {{ cgstAmount: number, sgstAmount: number, igstAmount: number }}
     */
    function splitTax(taxAmount, cgstPct, sgstPct, isInterState) {
        taxAmount = parseFloat(taxAmount) || 0;
        if (isInterState) {
            return { cgstAmount: 0, sgstAmount: 0, igstAmount: taxAmount };
        }
        var base = (parseFloat(cgstPct) || 0) + (parseFloat(sgstPct) || 0);
        if (base > 0 && taxAmount > 0) {
            var cgst = parseFloat((taxAmount * ((parseFloat(cgstPct) || 0) / base)).toFixed(3));
            var sgst = parseFloat((taxAmount - cgst).toFixed(3));
            return { cgstAmount: cgst, sgstAmount: sgst, igstAmount: 0 };
        }
        return { cgstAmount: 0, sgstAmount: 0, igstAmount: 0 };
    }

    /**
     * Aggregate pre-calculated items into a full bill summary.
     *
     * Each item must have:
     *   line_total, net_total, taxAmount, cgstAmount, sgstAmount, igstAmount,
     *   discount_amount, taxPercent, cgstPercent, sgstPercent, igstPercent, quantity
     *
     * @param {Array}  items
     * @param {Object} opts
     *   opts.extraDiscValue         {number}  — extra-discount magnitude
     *   opts.extraDiscType          {string}  — 'percentage' | 'amount'
     *   opts.roundOffEnabled        {boolean}
     *   opts.additionalChargesTotal {number}  — gross charges added to subtotal
     *   opts.additionalCharges      {Object}  — full charges object (passed through)
     *   opts.decimals               {number}  — decimal places (defaults to genSettings)
     * @returns {Object}  summary (same shape as BillManager.summary)
     */
    function calcSummary(items, opts) {
        opts = opts || {};
        var dp              = opts.decimals !== undefined ? parseInt(opts.decimals, 10)
                            : (parseInt((window.genSettings && window.genSettings.DecimalPoints) || 2, 10));
        var roundOffEnabled = opts.roundOffEnabled !== undefined ? !!opts.roundOffEnabled : true;
        var extraDiscValue  = parseFloat(opts.extraDiscValue)  || 0;
        var extraDiscType   = (opts.extraDiscType  || 'percentage').toLowerCase();
        var addChargesGross = parseFloat(opts.additionalChargesTotal) || 0;

        /* ── 1. Item aggregations ───────────────────────────────────────────── */
        var totalQty = 0, taxableAmount = 0, discountTotal = 0, netAmount = 0;
        var taxSummary = {}, taxRateSet = [];

        items.forEach(function (item) {
            totalQty      += parseFloat(item.quantity)        || 0;
            taxableAmount += parseFloat(item.line_total)      || 0;
            discountTotal += parseFloat(item.discount_amount) || 0;
            netAmount     += parseFloat(item.net_total)       || 0;

            var rk = parseFloat(item.taxPercent || 0).toFixed(2);
            if (!taxSummary[rk]) {
                taxSummary[rk] = {
                    taxPercent  : parseFloat(item.taxPercent)  || 0,
                    cgstPercent : parseFloat(item.cgstPercent) || 0,
                    sgstPercent : parseFloat(item.sgstPercent) || 0,
                    igstPercent : parseFloat(item.igstPercent) || 0,
                    taxableAmount: 0, cgstAmount: 0, sgstAmount: 0,
                    igstAmount: 0, totalTax: 0, itemCount: 0,
                };
                taxRateSet.push(rk);
            }
            var ts = taxSummary[rk];
            ts.taxableAmount += parseFloat(item.line_total)   || 0;
            ts.cgstAmount    += parseFloat(item.cgstAmount)   || 0;
            ts.sgstAmount    += parseFloat(item.sgstAmount)   || 0;
            ts.igstAmount    += parseFloat(item.igstAmount)   || 0;
            ts.totalTax      += parseFloat(item.taxAmount)    || 0;
            ts.itemCount     += 1;
        });

        /* ── 2. Tax totals ──────────────────────────────────────────────────── */
        var cgstTotal = 0, sgstTotal = 0, igstTotal = 0, totalTax = 0;
        taxRateSet.forEach(function (rk) {
            var ts = taxSummary[rk];
            cgstTotal += ts.cgstAmount;
            sgstTotal += ts.sgstAmount;
            igstTotal += ts.igstAmount;
            totalTax  += ts.totalTax;
        });

        /* ── 3. Subtotal (items net + additional charges) ───────────────────── */
        var subtotal = netAmount + addChargesGross;

        /* ── 4. Extra discount ──────────────────────────────────────────────── */
        var extraDiscAmount;
        if (extraDiscType === 'percentage') {
            extraDiscAmount = subtotal * (Math.min(Math.max(extraDiscValue, 0), 50) / 100);
        } else {
            extraDiscAmount = Math.min(extraDiscValue, subtotal);
        }
        extraDiscAmount     = parseFloat(extraDiscAmount.toFixed(dp));
        var totalAfterExtra = subtotal - extraDiscAmount;

        /* ── 5. Round-off + grand total ─────────────────────────────────────── */
        var roundOff = 0;
        if (roundOffEnabled) {
            roundOff = parseFloat((Math.round(totalAfterExtra) - totalAfterExtra).toFixed(dp));
        }
        var grandTotal = parseFloat((totalAfterExtra + roundOff).toFixed(dp));

        return {
            items: {
                count        : items.length,
                totalQuantity: totalQty,
                taxableAmount: taxableAmount,
                discountTotal: discountTotal,
                netAmount    : netAmount,
            },
            taxSummary: taxSummary,
            taxRates  : taxRateSet.slice().sort(function (a, b) { return parseFloat(b) - parseFloat(a); }),
            taxTotals : { cgstTotal: cgstTotal, sgstTotal: sgstTotal, igstTotal: igstTotal, totalTax: totalTax },
            additionalCharges: opts.additionalCharges || {
                total: { netAmount: 0, cgstAmount: 0, sgstAmount: 0, igstAmount: 0, taxAmount: 0, grossAmount: addChargesGross }
            },
            extra: {
                discountType        : extraDiscType,
                discountValue       : extraDiscValue,
                discountAmount      : extraDiscAmount,
                amountBeforeRoundOff: totalAfterExtra,
                roundOff            : roundOff,
                finalAmount         : grandTotal,
            },
            totals: {
                subtotal       : subtotal,
                totalAfterExtra: totalAfterExtra,
                grandTotal     : grandTotal,
            },
        };
    }

    return { splitTax: splitTax, calcSummary: calcSummary };

}());
