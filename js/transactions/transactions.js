class BillManager {
    
    constructor() {
        this.items = [];
        this.map = {};
        this.globalDiscountPercent = 0;
        this.roundOffEnabled = true;
        this.chargeTypes = ['shipping', 'handling', 'packing', 'other'];
        
        // THREE-TIER SUMMARY STRUCTURE
        this.summary = {
            // TIER 1: ITEM AGGREGATIONS
            items: {
                count: 0,
                totalQuantity: 0,
                taxableAmount: 0,
                discountTotal: 0,
                netAmount: 0
            },
            
            // TIER 2: TAX SUMMARY BY RATE
            taxSummary: {},      // Dynamic: { "18.00": {...}, "5.00": {...}, "0.00": {...} }
            taxRates: [],        // Unique tax rates present
            
            // TIER 3: TAX TOTALS
            taxTotals: {
                cgstTotal: 0,
                sgstTotal: 0,
                igstTotal: 0,
                totalTax: 0
            },
            
            // TIER 4: ADDITIONAL CHARGES
            additionalCharges: {
                shipping: { 
                    netAmount: 0, 
                    taxPercent: 0, 
                    cgstPercent: 0,
                    sgstPercent: 0,
                    igstPercent: 0,
                    cgstAmount: 0,
                    sgstAmount: 0,
                    igstAmount: 0,
                    taxAmount: 0, 
                    grossAmount: 0 
                },
                handling: { 
                    netAmount: 0, 
                    taxPercent: 0, 
                    cgstPercent: 0,
                    sgstPercent: 0,
                    igstPercent: 0,
                    cgstAmount: 0,
                    sgstAmount: 0,
                    igstAmount: 0,
                    taxAmount: 0, 
                    grossAmount: 0 
                },
                packing: { 
                    netAmount: 0, 
                    taxPercent: 0, 
                    cgstPercent: 0,
                    sgstPercent: 0,
                    igstPercent: 0,
                    cgstAmount: 0,
                    sgstAmount: 0,
                    igstAmount: 0,
                    taxAmount: 0, 
                    grossAmount: 0 
                },
                other: { 
                    netAmount: 0, 
                    taxPercent: 0, 
                    cgstPercent: 0,
                    sgstPercent: 0,
                    igstPercent: 0,
                    cgstAmount: 0,
                    sgstAmount: 0,
                    igstAmount: 0,
                    taxAmount: 0, 
                    grossAmount: 0 
                },
                total: { 
                    netAmount: 0, 
                    cgstAmount: 0,
                    sgstAmount: 0,
                    igstAmount: 0,
                    taxAmount: 0, 
                    grossAmount: 0 
                }
            },
            
            // TIER 5: EXTRA DISCOUNT & FINAL
            extra: {
                discountType: 'percentage',
                discountValue: 0,
                discountAmount: 0,
                amountBeforeRoundOff: 0,
                roundOff: 0,
                finalAmount: 0
            },
            
            // COMPLETE TOTALS
            totals: {
                subtotal: 0,
                totalAfterExtra: 0,
                grandTotal: 0
            }
        };
    }

    get roundedDecimal() {
        return genSettings.DecimalPoints || 2;
    }

    // Set global discount percentage
    setGlobalDiscountPercent(percent) {
        const oldPercent = this.globalDiscountPercent;
        this.globalDiscountPercent = Math.min(Math.max(parseFloat(percent) || 0, 0), 50);
        
        // KEY RULE: When global discount changes, RESET ALL IMMUNITY
        if (this.globalDiscountPercent !== oldPercent) {
            this.resetAllImmunityAndApplyGlobalDiscount();
        }
        return this;
    }

    // Reset all immunity and apply global discount
    resetAllImmunityAndApplyGlobalDiscount() {
        this.items.forEach(item => {
            const itemId = parseInt(item.id, 10);
            const updatedItem = {...item};
            
            // RESET IMMUNITY
            updatedItem.immune_to_global = false;
            updatedItem.discount_manually_changed = false;
            updatedItem.overrides_global_discount = false;
            
            if (this.globalDiscountPercent > 0) {
                updatedItem.discount_is_global = true;
                updatedItem._lastChanged = 'globalDiscount';
                
                // Apply global discount based on type
                if (updatedItem.discountType === 'Percentage') {
                    updatedItem.discount = this.globalDiscountPercent;
                } else if (updatedItem.discountType === 'Amount') {
                    const baseTotals = this.calculateBaseTotals(updatedItem);
                    const discountBase = this.getDiscountBaseForItem(updatedItem, baseTotals);
                    updatedItem.discount = this.roundValue(discountBase * (this.globalDiscountPercent / 100));
                }
            } else {
                updatedItem.discount_is_global = false;
                updatedItem.discount = 0;
                updatedItem._lastChanged = 'globalDiscount';
            }
            
            const recalculatedItem = this.calculateRowItem(updatedItem);
            this.updateItemInStorage(itemId, recalculatedItem);
            updateTableRow(recalculatedItem);
        });
        
        this.updateSummary();
        return this;
    }
    
    updateSummaryFromItems() {
        // Calculate total from all items
        let itemsTotal = 0;
        let totalQuantity = 0;
        let discountTotal = 0;
        let netAmount = 0;
        
        for (let item of this.items) {
            const itemValue = parseFloat(item.line_total) || 0;
            const itemQty = parseFloat(item.quantity) || 0;
            const itemDiscount = parseFloat(item.discount_amount) || 0;
            const itemNet = parseFloat(item.net_total) || 0;
            
            itemsTotal += itemValue;
            totalQuantity += itemQty;
            discountTotal += itemDiscount;
            netAmount += itemNet;
        }
        
        // Update summary with item data - FIXED: Using proper summary structure
        this.summary.items.count = this.items.length;
        this.summary.items.totalQuantity = totalQuantity;
        this.summary.items.taxableAmount = itemsTotal;
        this.summary.items.discountTotal = discountTotal;
        this.summary.items.netAmount = netAmount;
        
        return this.summary;
    }

    updateBaseDiscount() {
        // Calculate base discount from global discount percentage
        const taxableAmount = this.summary.taxableAmount || 0;
        this.summary.baseDiscount = taxableAmount * (this.globalDiscountPercent / 100);        
        return this.summary;
    }

    // Helper: Update extra discount
    updateExtraDiscount() {
        const extra = this.summary.extra;
        const subtotal = this.summary.totals.subtotal; // Total after items + charges
        
        if (extra.discountType === 'percentage') {
            // Validate percentage (max 50% for extra discount)
            const percentage = Math.min(Math.max(extra.discountValue || 0, 0), 50);
            extra.discountAmount = subtotal * (percentage / 100);
        } else {
            // For amount, ensure it doesn't exceed subtotal
            const maxAmount = subtotal;
            extra.discountAmount = Math.min(extra.discountValue || 0, maxAmount);
        }
        
        // Round the discount amount
        extra.discountAmount = this.roundValue(extra.discountAmount);
        
        // Update the amount after extra discount
        this.summary.totals.totalAfterExtra = subtotal - extra.discountAmount;
        
        return this.summary;
    }

    // Validate extra discount
    validateExtraDiscount() {
        const extraDiscount = this.summary.extraDiscount;
        const taxableAmount = this.summary.taxableAmount || 0;
        const additionalCharge = this.summary.additionalCharge || 0;
        const baseAmount = taxableAmount + additionalCharge;
        
        if (extraDiscount.type === 'percentage') {
            if (extraDiscount.value > 100) {
                console.warn("Warning: Extra discount percentage exceeds 100%");
                return false;
            }
        } else {
            if (extraDiscount.value > baseAmount) {
                console.warn("Warning: Extra discount amount exceeds base amount");
                return false;
            }
        }
        
        return true;
    }

    updateSubtotals() {
        const taxableAmount = this.summary.taxableAmount || 0;
        const additionalCharge = this.summary.additionalCharge || 0;
        const baseDiscount = this.summary.baseDiscount || 0;
        const extraDiscountAmount = this.summary.extraDiscount.calculatedAmount || 0;
        
        // Calculate subtotal before any discounts
        this.summary.subtotalBeforeDiscount = taxableAmount + additionalCharge;
        
        // Calculate subtotal after discounts
        this.summary.subtotalAfterDiscount = this.summary.subtotalBeforeDiscount - baseDiscount - extraDiscountAmount;

        return this.summary;
    }

    getItemById(id) {
        id = parseInt(id, 10);
        return this.map[id] || null;
    }

    getAllItems() {
        return this.items;
    }

    // =============================================
    // ITEM OPERATIONS
    // =============================================
    addItem(productData, qty = 1) {

        const id = parseInt(productData.id, 10);
        if (this.map[id]) {
            Swal.fire({icon: "error", title: "Oops...", text: "Item already moved to cart."});
            return false;
        }
        
        const unitPrice = smartDecimal(parseFloat(productData.unitPrice) || 0, 8);
        const taxPercent = parseFloat(productData.taxPercent) || 0;
        const sellingPrice = this.roundValue(unitPrice * (1 + taxPercent / 100));
        
        const item = {
            ...productData, 
            quantity: parseFloat(qty) || 1,
            // TAX FIELDS
            cgstPercent: parseFloat(productData.cgstPercent) || 0,
            sgstPercent: parseFloat(productData.sgstPercent) || 0,
            igstPercent: parseFloat(productData.igstPercent) || 0,
            taxPercent: taxPercent,
            // Current prices (showing in inputs)
            unitPrice: unitPrice,
            sellingPrice: sellingPrice,
            // Original values (fixed unless user changes)
            orgunitprice: unitPrice,
            orgselngprice: sellingPrice,
            orgquantity: parseFloat(qty) || 1,
            // Effective prices (after discount)
            effectiveUnitPrice: unitPrice,
            effectiveSellingPrice: sellingPrice,
            // Initialize calculated fields
            cgstAmount: 0,
            sgstAmount: 0,
            igstAmount: 0,
            taxAmount: 0,
            // Discount fields
            discount_scope: $('#discApplyFor').find('option:selected').val() || 'TotalAmount',
            immune_to_global: false,
            discount_manually_changed: false,
            overrides_global_discount: false,
            discount_is_global: false,
            discount: 0,
            discountType: productData.discountType,
            discount_amount: 0,
            // Totals
            line_total: 0,
            net_total: 0
        };
        
        // Apply global discount if exists
        if (this.globalDiscountPercent > 0) {
            item.discount = this.globalDiscountPercent;
            item.discount_is_global = true;
        }
        
        const newItem = this.calculateRowItem(item);
        this.items.push(newItem);
        this.map[id] = newItem;
        
        this.updateSummary();
        updateItemTaxBreakdown();
        return true;

    }

    updateItem(id, field, value) {
        id = parseInt(id, 10);
        const oldItem = this.map[id];
        if (!oldItem) return;

        // Clean value for non-string fields
        if (field !== 'discountType' && (value === '' || value === null || value === '.' || isNaN(value))) {
            value = 0;
        }

        let newItem = { ...oldItem };
        newItem[field] = value;
        newItem._lastChanged = field;
        
        // Special handling for discount type change
        if (field === 'discountType' && value !== oldItem.discountType) {
            this.handleDiscountTypeConversion(newItem, oldItem);
        } 
        // Handle manual discount value change
        else if (field === 'discount' && parseFloat(value) !== parseFloat(oldItem.discount || 0)) {
            this.handleManualDiscountChange(newItem, oldItem, value);
        }
        
        // Recalculate and update
        const aftCalcItem = this.calculateRowItem(newItem);
        this.updateItemInStorage(id, aftCalcItem);
        updateTableRow(aftCalcItem);
        this.updateSummary();
        updateItemTaxBreakdown();

        return this.summary;
    }

    // New helper: Handle manual discount change
    handleManualDiscountChange(newItem, oldItem, newValue) {
        newItem.original_discount_before_type_change = parseFloat(newValue) || 0;
        newItem.immune_to_global = true;
        newItem.discount_manually_changed = true;
        newItem.discount_is_global = false;
        newItem.overrides_global_discount = true;
        
        // If this was previously a global discount, mark as overridden
        if (oldItem.discount_is_global) {
            newItem.discount_is_global = false;
        }
    }

    // Helper: Handle discount type conversion
    handleDiscountTypeConversion(newItem, oldItem) {

        const previousType = oldItem.discountType || 'Percentage';
        const newType = newItem.discountType;
        
        // Get the ACTUAL discount amount that was applied
        const actualDiscountAmount = parseFloat(oldItem.discount_amount) || 0;
        
        // Get base totals for calculation
        const baseTotals = this.calculateBaseTotals(newItem);
        const discountBase = parseFloat(this.getDiscountBaseForItem(newItem, baseTotals)) || 0;
        
        let convertedValue = 0;
        
        // Convert DISPLAY value only, keep same effective discount
        if (previousType === 'Percentage' && newType === 'Amount') {
            if (oldItem.discount_is_global && this.globalDiscountPercent > 0) {
                // For global discount, use global percentage
                convertedValue = discountBase * (this.globalDiscountPercent / 100);
                newItem.discount = this.roundValue(convertedValue);
            } else {
                // For manual percentage discount
                const percentageValue = parseFloat(oldItem.discount) || 0;
                convertedValue = discountBase * (percentageValue / 100);
                newItem.discount = this.roundValue(convertedValue);
            }
            
        } else if (previousType === 'Amount' && newType === 'Percentage') {
            if (oldItem.discount_is_global && this.globalDiscountPercent > 0) {
                // For global discount, show global percentage
                newItem.discount = this.globalDiscountPercent;
                convertedValue = this.globalDiscountPercent;
            } else {
                // For manual amount discount
                if (discountBase > 0 && actualDiscountAmount > 0) {
                    const percentageEquivalent = (actualDiscountAmount / discountBase) * 100;
                    newItem.discount = this.roundValue(percentageEquivalent);
                    convertedValue = this.roundValue(percentageEquivalent);
                } else {
                    newItem.discount = 0;
                    convertedValue = 0;
                }
            }
        }
        
        // IMPORTANT: Keep the SAME discount_amount (actual discount)
        newItem.discount_amount = actualDiscountAmount;
        
        // Update flags - if it was global, keep it global unless manually changed
        if (oldItem.discount_is_global && this.globalDiscountPercent > 0) {
            newItem.discount_is_global = true;
            newItem.immune_to_global = false;
            newItem.overrides_global_discount = false;
        } else {
            newItem.discount_manually_changed = true;
            newItem.immune_to_global = true;
            newItem.overrides_global_discount = true;
            newItem.discount_is_global = false;
        }
        
        newItem.previous_discountType = previousType;
        newItem.original_discount_before_type_change = parseFloat(oldItem.discount) || 0;
        
        return newItem;

    }

    removeItem(id) {
        id = parseInt(id, 10);
        
        // Remove from items array
        const initialLength = this.items.length;
        this.items = this.items.filter(item => parseInt(item.id, 10) !== id);
        
        // Remove from map
        delete this.map[id];
        
        if (this.items.length !== initialLength) {
            if(this.items.length === 0) {
                $('#billTableBody').html(emptyTableTrInfo);
                $('#showHideTaxBreakUp').addClass('d-none');
                $('#taxBreakupPanel').slideUp(300);
                this.resetAdditionalChargesOnEmptyItems();
            }
            this.updateSummary();
            updateItemTaxBreakdown();
            return true;
        }
        return false;
    }

    // =============================================
    // CALCULATION METHODS - OPTIMIZED
    // =============================================
    calculateRowItem(item) {
        const qty = parseFloat(item.quantity) || 0;
        const lastChanged = item._lastChanged;
        
        // PHASE 1: Update original values ONLY when explicitly changed
        this.updateOriginalValues(item, lastChanged, qty);
        
        // PHASE 2: Calculate base totals from ORIGINAL values
        const baseTotals = this.calculateBaseTotals(item);
        
        // PHASE 3: Handle discount logic
        const discountData = this.handleDiscountLogic(item, lastChanged, baseTotals);
        
        // PHASE 4: Apply discount and calculate effective values
        const finalValues = this.applyDiscountAndCalculate(item, discountData, baseTotals);
        
        // PHASE 5: Update item with all calculated values
        return this.updateItemWithCalculations(item, discountData, baseTotals);
    }

    // Helper: Update original values
    updateOriginalValues(item, lastChanged, qty) {

        // Initialize original values if they don't exist
        if (item.orgunitprice === undefined || isNaN(parseFloat(item.orgunitprice))) {
            item.orgunitprice = parseFloat(smartDecimal(parseFloat(item.unitPrice) || 0, 8));
            const taxPercent = parseFloat(item.taxPercent) || 0;
            item.orgselngprice = this.roundValue(item.orgunitprice * (1 + taxPercent / 100));
            item.orgquantity = parseFloat(qty);
        }
        
        // Only update original values when user explicitly changes unitPrice or sellingPrice
        if (lastChanged === 'unitPrice') {
            const newUnitPrice = parseFloat(item.unitPrice) || 0;
            item.orgunitprice = parseFloat(smartDecimal(newUnitPrice, 8));
            const taxPercent = parseFloat(item.taxPercent) || 0;
            item.orgselngprice = this.roundValue(item.orgunitprice * (1 + taxPercent / 100));
            item.orgquantity = parseFloat(qty) || 0;
        } else if (lastChanged === 'sellingPrice') {
            const newSellPrice = parseFloat(item.sellingPrice) || 0;
            item.orgselngprice = newSellPrice;
            const taxPercent = parseFloat(item.taxPercent) || 0;
            item.orgunitprice = smartDecimal(newSellPrice / (1 + taxPercent / 100), 8);
            item.orgquantity = parseFloat(qty) || 0;
        } else if (lastChanged === 'quantity') {
            item.orgquantity = parseFloat(qty) || 0;
        }

    }

    // Helper: Calculate base totals
    calculateBaseTotals(item) {

        const baseUnitPrice = parseFloat(item.orgunitprice) || 0;
        const baseQty = parseFloat(item.orgquantity) || 0;
        const taxPercent = parseFloat(item.taxPercent) || 0;

        const preciseUnitPrice = parseFloat(smartDecimal(baseUnitPrice, 8));
        const lineBaseTotal = parseFloat(smartDecimal(baseQty * preciseUnitPrice, genSettings.DecimalPoints, true));
        
        const baseSellPrice = parseFloat(smartDecimal(preciseUnitPrice * (1 + taxPercent / 100), genSettings.DecimalPoints, true));
        
        // Calculate taxes with rounding
        const cgstAmount = this.roundValue(lineBaseTotal * (parseFloat(item.cgstPercent) || 0) / 100);
        const sgstAmount = this.roundValue(lineBaseTotal * (parseFloat(item.sgstPercent) || 0) / 100);
        const igstAmount = this.roundValue(lineBaseTotal * (parseFloat(item.igstPercent) || 0) / 100);
        
        const baseTaxAmount = this.roundValue(lineBaseTotal * taxPercent / 100);
        const baseTotalAmount = this.roundValue(lineBaseTotal + baseTaxAmount);
        
        return {
            baseUnitPrice: preciseUnitPrice,
            baseSellPrice,
            baseQty,
            lineBaseTotal,
            cgstAmount,
            sgstAmount,
            igstAmount,
            baseTaxAmount,
            baseTotalAmount
        };

    }

    // Helper: Get discount base for item
    getDiscountBaseForItem(item, totals) {
        const discApplyFor = $('#billTable').find('#discApplyFor option:selected').val() || 'TotalAmount';
        switch (discApplyFor) {
            case 'TotalAmount':
                return parseFloat(totals.baseTotalAmount) || 0;
            case 'PriceWithTax':
                return parseFloat(totals.baseQty) * parseFloat(totals.baseSellPrice);
            case 'UnitPrice':
            case 'NetAmount':
                return parseFloat(totals.lineBaseTotal) || 0;
            default:
                return parseFloat(totals.lineBaseTotal) || 0;
        }
    }

    // Helper: Handle discount logic
    handleDiscountLogic(item, lastChanged, totals) {

        const discApplyFor = $('#discApplyFor').find('option:selected').val() || 'TotalAmount';
        let discountType = item.discountType || 'Percentage';
        let discountValue = parseFloat(item.discount) || 0;
        
        // Initialize flags if not present
        if (item.immune_to_global === undefined) item.immune_to_global = false;
        if (item.discount_manually_changed === undefined) item.discount_manually_changed = false;
        if (item.discount_is_global === undefined) item.discount_is_global = false;
        if (item.overrides_global_discount === undefined) item.overrides_global_discount = false;
        
        // Get discount base
        const discountBase = parseFloat(this.getDiscountBaseForItem(item, totals)) || 0;
        
        // Apply global discount if applicable
        if (!item.immune_to_global && !item.overrides_global_discount && this.globalDiscountPercent > 0) {
            item.discount_is_global = true;
            item.discount_manually_changed = false;
            
            if (discountType === 'Percentage') {
                discountValue = this.globalDiscountPercent;
            } else {
                // Convert global percentage to amount
                discountValue = this.roundValue(discountBase * (this.globalDiscountPercent / 100));
            }
        }
        
        // Calculate discount amount
        let discountAmount = 0;
        if (discountValue > 0) {
            if (discountType === 'Percentage') {
                discountAmount = discountBase * (discountValue / 100);
            } else {
                discountAmount = Math.min(discountValue, discountBase);
            }
        }
        
        discountAmount = this.roundValue(discountAmount);
        discountValue = this.roundValue(discountValue);
        
        return {
            discountType,
            discountValue,
            discountAmount,
            discountBase,
            discApplyFor
        };
        
    }

    // Helper: Initialize discount flags
    initializeDiscountFlags(item) {
        if (item.immune_to_global === undefined) item.immune_to_global = false;
        if (item.discount_manually_changed === undefined) item.discount_manually_changed = false;
        if (item.discount_is_global === undefined) item.discount_is_global = false;
        if (item.overrides_global_discount === undefined) item.overrides_global_discount = false;
        if (item.previous_discountType === undefined) item.previous_discountType = item.discountType || 'Percentage';
        if (item.original_discount_before_type_change === undefined) item.original_discount_before_type_change = item.discount || 0;
    }

    // Helper: Handle discount type change
    handleDiscountTypeChange(item, discountType, totals) {
        const previousType = item.previous_discountType || item.discountType || 'Percentage';
        const currentDiscountValue = parseFloat(item.discount) || 0;
        
        // Get the base amount for conversion
        const discountBase = this.getDiscountBaseForItem(item, totals);
        
        if (previousType === 'Percentage' && discountType === 'Amount') {
            // Convert percentage to amount
            const convertedAmount = discountBase * (currentDiscountValue / 100);
            item.discount = this.roundValue(convertedAmount);
            
        } else if (previousType === 'Amount' && discountType === 'Percentage') {
            // Convert amount to percentage
            if (discountBase > 0 && currentDiscountValue > 0) {
                const calculatedPercent = (currentDiscountValue / discountBase) * 100;
                item.discount = this.roundValue(calculatedPercent);
            } else {
                item.discount = 0;
            }
        }
        
        // Update flags
        item.previous_discountType = discountType;
        item.discount_manually_changed = true;
        item.immune_to_global = true;
        item.overrides_global_discount = true;
        item.discount_is_global = false;
        
        // Store original value for future conversions
        item.original_discount_before_type_change = item.discount;
        
        return item;
    }

    // Helper: Handle global discount change
    handleGlobalDiscountChange(item, discountType, totals) {
        item.immune_to_global = false;
        item.discount_manually_changed = false;
        item.overrides_global_discount = false;
        item.original_discount_before_type_change = 0;
        
        if (this.globalDiscountPercent > 0) {
            item.discount_is_global = true;
            
            if (discountType === 'Percentage') {
                item.discount = this.globalDiscountPercent;
            } else if (discountType === 'Amount') {
                const discountBase = this.getDiscountBaseForItem(item, totals);
                item.discount = discountBase * (this.globalDiscountPercent / 100);
                item.discount = parseFloat(item.discount.toFixed(genSettings.DecimalPoints));
            }
        } else {
            item.discount_is_global = false;
            item.discount = 0;
        }
    }

    // Helper: Apply global discount to item
    applyGlobalDiscountToItem(item, discountType, totals) {
        item.discount_is_global = true;
        item.discount_manually_changed = false;
        
        if (discountType === 'Percentage') {
            item.discount = this.globalDiscountPercent;
        } else if (discountType === 'Amount') {
            const discountBase = this.getDiscountBaseForItem(item, totals);
            item.discount = discountBase * (this.globalDiscountPercent / 100);
            item.discount = parseFloat(item.discount.toFixed(genSettings.DecimalPoints));
        }
    }

    // Helper: Apply discount and calculate final values
    applyDiscountAndCalculate(item, discountData, totals) {

        const baseUnitPrice = parseFloat(totals.baseUnitPrice) || 0;
        const baseSellPrice = parseFloat(totals.baseSellPrice) || 0;
        const baseTotalAmount = parseFloat(totals.baseTotalAmount) || 0;
        const discountAmount = parseFloat(discountData.discountAmount) || 0;

        if (discountData.discountAmount === 0) {
            return {
                effUnitPrice: baseUnitPrice,
                effSellPrice: baseSellPrice,
                effCgstAmount: parseFloat(totals.cgstAmount) || 0,
                effSgstAmount: parseFloat(totals.sgstAmount) || 0,
                effIgstAmount: parseFloat(totals.igstAmount) || 0,
                effTaxAmount: parseFloat(totals.baseTaxAmount) || 0,
                effTotalAmount: baseTotalAmount
            };
        }
        
        // Just return the base totals for now
        return {
            effUnitPrice: baseUnitPrice,
            effSellPrice: baseSellPrice,
            effCgstAmount: parseFloat(totals.cgstAmount) || 0,
            effSgstAmount: parseFloat(totals.sgstAmount) || 0,
            effIgstAmount: parseFloat(totals.igstAmount) || 0,
            effTaxAmount: parseFloat(totals.baseTaxAmount) || 0,
            effTotalAmount: baseTotalAmount - discountAmount
        };

    }

    // Helper: Apply discount to total amount
    applyDiscountToTotalAmount(item, discountData, totals) {
        const totalAfterDiscount = totals.baseTotalAmount - discountData.discountAmount;
        const pricePerUnit = totals.baseQty > 0 ? totalAfterDiscount / totals.baseQty : 0;
        const taxPercent = parseFloat(item.taxPercent) || 0;
        
        const effSellPrice = pricePerUnit;
        const effUnitPrice = effSellPrice / (1 + taxPercent / 100);
        const effTaxAmount = (effSellPrice - effUnitPrice) * totals.baseQty;
        
        // Calculate tax components proportionally
        const taxRatio = totals.baseTaxAmount > 0 ? effTaxAmount / totals.baseTaxAmount : 0;
        const effCgstAmount = totals.cgstAmount * taxRatio;
        const effSgstAmount = totals.sgstAmount * taxRatio;
        const effIgstAmount = totals.igstAmount * taxRatio;
        
        return {
            effUnitPrice,
            effSellPrice,
            effCgstAmount,
            effSgstAmount,
            effIgstAmount,
            effTaxAmount,
            effTotalAmount: totalAfterDiscount
        };
    }

    // Helper: Apply discount to price with tax
    applyDiscountToPriceWithTax(item, discountData, totals) {
        const totalSellAfter = (totals.baseQty * totals.baseSellPrice) - discountData.discountAmount;
        const sellPricePerUnit = totals.baseQty > 0 ? totalSellAfter / totals.baseQty : 0;
        const taxPercent = parseFloat(item.taxPercent) || 0;
        
        const effSellPrice = sellPricePerUnit;
        const effUnitPrice = effSellPrice / (1 + taxPercent / 100);
        const effTaxAmount = (effSellPrice - effUnitPrice) * totals.baseQty;
        
        // Calculate tax components proportionally
        const taxRatio = totals.baseTaxAmount > 0 ? effTaxAmount / totals.baseTaxAmount : 0;
        const effCgstAmount = totals.cgstAmount * taxRatio;
        const effSgstAmount = totals.sgstAmount * taxRatio;
        const effIgstAmount = totals.igstAmount * taxRatio;
        
        return {
            effUnitPrice,
            effSellPrice,
            effCgstAmount,
            effSgstAmount,
            effIgstAmount,
            effTaxAmount,
            effTotalAmount: (effUnitPrice * totals.baseQty) + effTaxAmount
        };
    }

    // Helper: Apply discount to unit price
    applyDiscountToUnitPrice(item, discountData, totals) {
        const unitTotalAfter = totals.lineBaseTotal - discountData.discountAmount;
        const effUnitPrice = totals.baseQty > 0 ? unitTotalAfter / totals.baseQty : totals.baseUnitPrice;
        const taxPercent = parseFloat(item.taxPercent) || 0;
        
        const effSellPrice = effUnitPrice * (1 + taxPercent / 100);
        const effTaxAmount = (effSellPrice - effUnitPrice) * totals.baseQty;
        
        // Calculate tax components proportionally
        const taxRatio = totals.baseTaxAmount > 0 ? effTaxAmount / totals.baseTaxAmount : 0;
        const effCgstAmount = totals.cgstAmount * taxRatio;
        const effSgstAmount = totals.sgstAmount * taxRatio;
        const effIgstAmount = totals.igstAmount * taxRatio;
        
        return {
            effUnitPrice,
            effSellPrice,
            effCgstAmount,
            effSgstAmount,
            effIgstAmount,
            effTaxAmount,
            effTotalAmount: (effUnitPrice * totals.baseQty) + effTaxAmount
        };
    }

    // Helper: Apply discount to net amount
    applyDiscountToNetAmount(item, discountData, totals) {
        const netAfter = totals.lineBaseTotal - discountData.discountAmount;
        const effUnitPrice = totals.baseQty > 0 ? netAfter / totals.baseQty : totals.baseUnitPrice;
        const taxPercent = parseFloat(item.taxPercent) || 0;
        
        const effSellPrice = effUnitPrice * (1 + taxPercent / 100);
        const effTaxAmount = (effSellPrice - effUnitPrice) * totals.baseQty;
        
        // Calculate tax components proportionally
        const taxRatio = totals.baseTaxAmount > 0 ? effTaxAmount / totals.baseTaxAmount : 0;
        const effCgstAmount = totals.cgstAmount * taxRatio;
        const effSgstAmount = totals.sgstAmount * taxRatio;
        const effIgstAmount = totals.igstAmount * taxRatio;
        
        return {
            effUnitPrice,
            effSellPrice,
            effCgstAmount,
            effSgstAmount,
            effIgstAmount,
            effTaxAmount,
            effTotalAmount: (effUnitPrice * totals.baseQty) + effTaxAmount
        };
    }

    // Helper: Update item with calculations
    updateItemWithCalculations(item, discountData, baseTotals) {

        // Use ORIGINAL values for calculation
        const originalUnitPrice = parseFloat(item.orgunitprice) || 0;
        const originalSellingPrice = parseFloat(item.orgselngprice) || 0;
        const quantity = parseFloat(item.orgquantity) || 0;
        const taxPercent = parseFloat(item.taxPercent) || 0;
        
        // Calculate total original amounts
        const originalUnitTotal = parseFloat(smartDecimal(originalUnitPrice * quantity, genSettings.DecimalPoints, true));
        const originalSellingTotal = parseFloat(smartDecimal(originalSellingPrice * quantity, genSettings.DecimalPoints, true));
        
        // Calculate discount amount
        let discountAmount = 0;
        if (discountData.discountType === 'Percentage') {
            discountAmount = originalSellingTotal * (parseFloat(discountData.discountValue) / 100);
        } else {
            discountAmount = parseFloat(discountData.discountAmount) || 0;
        }
        
        discountAmount = this.roundValue(discountAmount);
        
        // Calculate effective totals after discount
        const effectiveSellingTotal = this.roundValue(originalSellingTotal - discountAmount);
        
        // Calculate per-unit effective prices
        let effectiveSellingPricePerUnit = 0;
        let effectiveUnitPricePerUnit = 0;
        
        if (quantity > 0) {
            effectiveSellingPricePerUnit = this.roundValue(effectiveSellingTotal / quantity);
            
            if (taxPercent > 0) {
                effectiveUnitPricePerUnit = parseFloat(smartDecimal(effectiveSellingPricePerUnit / (1 + taxPercent / 100), 8));
            } else {
                effectiveUnitPricePerUnit = effectiveSellingPricePerUnit;
            }
        }
        
        // Update item fields
        // Keep displaying original unit price and selling price in inputs
        item.unitPrice = originalUnitPrice;  // Show original in input
        item.sellingPrice = originalSellingPrice;  // Show original in input
        item.effectiveUnitPrice = effectiveUnitPricePerUnit;
        item.effectiveSellingPrice = effectiveSellingPricePerUnit;
        item.quantity = quantity;
        item.line_total = this.roundValue(effectiveUnitPricePerUnit * quantity);
        item.taxAmount = this.roundValue(item.line_total * (taxPercent / 100));
        
        // Calculate tax components
        item.cgstAmount = this.roundValue(item.line_total * (parseFloat(item.cgstPercent) || 0) / 100);
        item.sgstAmount = this.roundValue(item.line_total * (parseFloat(item.sgstPercent) || 0) / 100);
        item.igstAmount = this.roundValue(item.line_total * (parseFloat(item.igstPercent) || 0) / 100);
        
        // Store discount information
        item.discount = parseFloat(discountData.discountValue) || 0;
        item.discountType = discountData.discountType;
        item.discount_amount = discountAmount;
        item.discount_scope = discountData.discApplyFor;
        
        // Net total is effective selling total
        item.net_total = effectiveSellingTotal;
        
        return item;

    }

    // Add this method inside BillManager class:
    resetAdditionalChargesOnEmptyItems() {
        
        this.chargeTypes.forEach(type => {
            // Reset the charge in summary
            this.summary.additionalCharges[type] = {
                netAmount: 0,
                taxPercent: 0,
                cgstPercent: 0,
                sgstPercent: 0,
                igstPercent: 0,
                cgstAmount: 0,
                sgstAmount: 0,
                igstAmount: 0,
                taxAmount: 0,
                grossAmount: 0
            };
            
            // Reset the input fields in UI
            $(`#${type}Percent`).val('0');
            $(`#${type}ChargeWOutTax`).val('0');
            $(`#${type}ChargeWithTax`).val('0');
            $(`#${type}Charges`).prop('selectedIndex', 0); // Reset tax dropdown
        });
        
        // Also update the totals
        this.updateAdditionalChargesTotal();
        
        // Update UI visibility
        this.updateAdditionalChargesUI();
    }

    /** show item summary info */
    getItemsTotals() {

        let totalItems = 0, totalQty = 0, totalAmount = 0, totalTax = 0, totalCgst = 0, totalSgst = 0, totalIgst = 0, netTotal = 0, totalDisc = 0;
        
        this.items.forEach((item, index) => {
            totalItems += parseFloat(item.id) || 0;
            totalQty += parseFloat(item.quantity) || 0;
            totalAmount += parseFloat(item.line_total) || 0;
            totalTax += parseFloat(item.taxAmount) || 0;
            totalCgst += parseFloat(item.cgstAmount) || 0;
            totalSgst += parseFloat(item.sgstAmount) || 0;
            totalIgst += parseFloat(item.igstAmount) || 0;
            netTotal += parseFloat(item.net_total) || 0;
            totalDisc += parseFloat(item.discount_amount) || 0;
        });
        
        return {
            totalItems: this.items.length,
            totalQty,
            totalAmount,
            totalTax,
            totalCgst,
            totalSgst,
            totalIgst,
            netTotal,
            totalDisc,
        };

    }

    // Add this method to BillManager class:
    calculateRoundOff(amount) {
        if (!this.roundOffEnabled) return 0;
        
        // Round to nearest whole number (1)
        const roundedTotal = Math.round(amount);
        return parseFloat((roundedTotal - amount).toFixed(genSettings.DecimalPoints));
    }

    // =============================================
    // SUMMARY CALCULATIONS
    // =============================================
    updateSummary() {

        if (this.items.length === 0) {
            this.summary = {
                items: { count: 0, totalQuantity: 0, taxableAmount: 0, discountTotal: 0, netAmount: 0 },
                taxSummary: {},
                taxRates: [],
                taxTotals: { cgstTotal: 0, sgstTotal: 0, igstTotal: 0, totalTax: 0 },
                additionalCharges: { shipping: 0, handling: 0, packing: 0, other: 0, total: 0 },
                extra: { discountType: 'percentage', discountValue: 0, discountAmount: 0, amountBeforeRoundOff: 0, roundOff: 0, finalAmount: 0 },
                totals: { subtotal: 0, totalAfterExtra: 0, grandTotal: 0 }
            };
            this.updateSummaryUI();
            return this.summary;
        }

        // Update item aggregations
        this.updateItemAggregations();
        
        // Update tax summary by rate
        this.updateTaxSummary();
        
        // Update tax totals
        this.updateTaxTotals();
        
        // Calculate subtotal
        const itemsNetAmount = this.summary.items.netAmount || 0;
        const additionalChargesTotal = this.summary.additionalCharges.total?.grossAmount || 0;
        this.summary.totals.subtotal = itemsNetAmount + additionalChargesTotal;
        
        // Calculate extra discount
        this.updateExtraDiscount();
        
        // Calculate final totals
        this.calculateFinalTotals();
        
        // Update UI
        this.updateSummaryUI();

        setTimeout(updateTaxTooltip, 0);
        $('#showHideTaxBreakUp').removeClass('d-none');
        
        return this.summary;
    }

    // Helper: Update item aggregations
    updateItemAggregations() {
        let totalQty = 0;
        let taxableAmount = 0;
        let discountTotal = 0;
        let netAmount = 0;
        
        this.items.forEach(item => {
            totalQty += parseFloat(item.quantity) || 0;
            taxableAmount += parseFloat(item.line_total) || 0;
            discountTotal += parseFloat(item.discount_amount) || 0;
            netAmount += parseFloat(item.net_total) || 0;
        });
        
        this.summary.items = {
            count: this.items.length,
            totalQuantity: totalQty,
            taxableAmount: taxableAmount,
            discountTotal: discountTotal,
            netAmount: netAmount
        };
    }

    // Helper: Update tax summary by rate
    updateTaxSummary() {
        const taxSummary = {};
        const taxRates = new Set();
        
        this.items.forEach(item => {
            const taxRateKey = parseFloat(item.taxPercent || 0).toFixed(2);
            taxRates.add(taxRateKey);
            
            if (!taxSummary[taxRateKey]) {
                taxSummary[taxRateKey] = {
                    taxPercent: parseFloat(item.taxPercent) || 0,
                    cgstPercent: parseFloat(item.cgstPercent) || 0,
                    sgstPercent: parseFloat(item.sgstPercent) || 0,
                    igstPercent: parseFloat(item.igstPercent) || 0,
                    taxableAmount: 0,
                    cgstAmount: 0,
                    sgstAmount: 0,
                    igstAmount: 0,
                    totalTax: 0,
                    itemCount: 0
                };
            }
            
            const summary = taxSummary[taxRateKey];
            summary.taxableAmount += parseFloat(item.line_total) || 0;
            summary.cgstAmount += parseFloat(item.cgstAmount) || 0;
            summary.sgstAmount += parseFloat(item.sgstAmount) || 0;
            summary.igstAmount += parseFloat(item.igstAmount) || 0;
            summary.totalTax += parseFloat(item.taxAmount) || 0;
            summary.itemCount += 1;
        });
        
        this.summary.taxSummary = taxSummary;
        this.summary.taxRates = Array.from(taxRates).sort((a, b) => parseFloat(b) - parseFloat(a));
    }

    // Helper: Update tax totals
    updateTaxTotals() {
        let cgstTotal = 0;
        let sgstTotal = 0;
        let igstTotal = 0;
        let totalTax = 0;
        
        Object.values(this.summary.taxSummary).forEach(summary => {
            cgstTotal += summary.cgstAmount;
            sgstTotal += summary.sgstAmount;
            igstTotal += summary.igstAmount;
            totalTax += summary.totalTax;
        });
        
        this.summary.taxTotals = {
            cgstTotal,
            sgstTotal,
            igstTotal,
            totalTax
        };
    }

    // Helper: Update extra discount
    updateExtraDiscount() {
        const extra = this.summary.extra;
        const subtotal = this.summary.totals.subtotal;
        
        if (extra.discountType === 'percentage') {
            // Validate percentage (max 50%)
            const percentage = Math.min(Math.max(extra.discountValue || 0, 0), 50);
            extra.discountAmount = subtotal * (percentage / 100);
        } else {
            // Validate amount (max subtotal)
            extra.discountAmount = Math.min(extra.discountValue || 0, subtotal);
        }
        
        this.summary.totals.totalAfterExtra = subtotal - extra.discountAmount;
    }

    // Helper: Calculate final totals
    calculateFinalTotals() {

        const totalAfterExtra = this.summary.totals.totalAfterExtra;

        let roundOffAmount = 0;
        if (this.roundOffEnabled) {
            const roundedTotal = Math.round(totalAfterExtra);
            roundOffAmount = parseFloat((roundedTotal - totalAfterExtra).toFixed(genSettings.DecimalPoints));
        }
        this.summary.extra.roundOff = roundOffAmount;
        this.summary.extra.amountBeforeRoundOff = totalAfterExtra;
        
        this.summary.totals.grandTotal = parseFloat((totalAfterExtra + roundOffAmount).toFixed(genSettings.DecimalPoints));
        this.summary.extra.finalAmount = this.summary.totals.grandTotal;

    }

    // =============================================
    // UI UPDATE METHODS
    // =============================================
    updateSummaryUI() {

        // Update basic counts
        $('.sumItemCount').text(smartDecimal(this.summary.items.count || 0));
        $('.sumTotalQty').text(smartDecimal(this.summary.items.totalQuantity || 0));
        
        // Update amounts
        $('.bill_taxable_amt').text(smartDecimal(this.summary.items.taxableAmount || 0, genSettings.DecimalPoints, true));
        $('.bill_tot_tax_amt').text(smartDecimal(this.summary.taxTotals.totalTax || 0, genSettings.DecimalPoints, true));
        $('.sumNetTotal').text(smartDecimal(this.summary.items.netAmount || 0, genSettings.DecimalPoints, true));
        
        // Update total amount
        $('.bill_tot_amt').text(smartDecimal(this.summary.totals.grandTotal || 0, genSettings.DecimalPoints, true));
        $('#grandChargesTaxTotal').text(smartDecimal((this.summary.taxTotals.totalTax + this.summary.additionalCharges.total.taxAmount) || 0, genSettings.DecimalPoints, true));
        
        // Update total discount
        const totalDiscount = (this.summary.items.discountTotal || 0) + (this.summary.extra.discountAmount || 0);
        $('.bill_tot_disc_amt').text(smartDecimal(totalDiscount, genSettings.DecimalPoints, true));
        
        // Update round off
        const roundOffAmount = this.roundOffEnabled ? (this.summary.extra.roundOff || 0) : 0;
        let displayRoundOff = smartDecimal(Math.abs(roundOffAmount), genSettings.DecimalPoints, true);
        if (roundOffAmount < 0) {
            displayRoundOff = '-' + displayRoundOff;
        }
        $('.bill_rndoff_amt').text(displayRoundOff);
        
    }

    updateTaxBreakupUI() {
        // Update CGST/SGST/IGST totals if elements exist
        if ($('#cgstTotal').length) {
            $('#cgstTotal').text(smartDecimal(this.summary.taxTotals.cgstTotal, genSettings.DecimalPoints, true));
        }
        if ($('#sgstTotal').length) {
            $('#sgstTotal').text(smartDecimal(this.summary.taxTotals.sgstTotal, genSettings.DecimalPoints, true));
        }
        if ($('#igstTotal').length) {
            $('#igstTotal').text(smartDecimal(this.summary.taxTotals.igstTotal, genSettings.DecimalPoints, true));
        }
        
        // Update tax summary table if exists
        this.updateTaxSummaryTable();
    }

    // New helper method to format charge display text
    getChargeDisplayText(baseLabel, charge) {
        if (!charge || typeof charge !== 'object') return baseLabel;
        
        const taxPercent = parseFloat(charge.taxPercent) || 0;
        
        if (taxPercent > 0) {
            // Show with tax percentage
            return `${baseLabel} (${taxPercent}%)`;
        } else {
            // Show without tax percentage
            return baseLabel;
        }
    }

    updateAdditionalChargesUI() {

        const charges = this.summary.additionalCharges;
        
        // Update shipping charges display
        if (charges.shipping && charges.shipping.grossAmount > 0) {
            const shippingCharge = charges.shipping;
            const displayText = this.getChargeDisplayText('Shipping Charges', shippingCharge);
            $('#shippingChargeAmt').text(smartDecimal(shippingCharge.grossAmount, genSettings.DecimalPoints, true));
            $('#shippingChargeLabel').html(displayText);
            $('#shippingRow').removeClass('d-none');
        } else {
            $('#shippingRow').addClass('d-none');
        }
        
        // Update packing charges display
        if (charges.packing && charges.packing.grossAmount > 0) {
            const packingCharge = charges.packing;
            const displayText = this.getChargeDisplayText('Packing Charges', packingCharge);
            $('#packingChargeAmt').text(smartDecimal(packingCharge.grossAmount, genSettings.DecimalPoints, true));
            $('#packingChargeLabel').html(displayText);
            $('#packingRow').removeClass('d-none');
        } else {
            $('#packingRow').addClass('d-none');
        }

        // Update handling charges if you have it
        if (charges.handling && charges.handling.grossAmount > 0) {
            const handlingCharge = charges.handling;
            const displayText = this.getChargeDisplayText('Handling Charges', handlingCharge);
            $('#handlingChargeAmt').text(smartDecimal(handlingCharge.grossAmount, genSettings.DecimalPoints, true));
            $('#handlingChargeLabel').html(displayText);
            $('#handlingRow').removeClass('d-none');
        } else {
            $('#handlingRow').addClass('d-none');
        }
        
        // Update other charges if you have it
        if (charges.other && charges.other.grossAmount > 0) {
            const otherCharge = charges.other;
            const displayText = this.getChargeDisplayText('Other Charges', otherCharge);
            $('#otherChargeAmt').text(smartDecimal(otherCharge.grossAmount, genSettings.DecimalPoints, true));
            $('#otherChargeLabel').html(displayText);
            $('#otherRow').removeClass('d-none');
        } else {
            $('#otherRow').addClass('d-none');
        }
        
        // Update totals in bill manager
        billManager.updateSummary();

    }

    updateTaxSummaryTable() {
        const $taxTable = $('#taxSummaryTable tbody');
        if ($taxTable.length) {
            $taxTable.empty();
            
            Object.values(this.summary.taxSummary).forEach(summary => {
                const row = `
                    <tr>
                        <td>${summary.taxPercent}%</td>
                        <td>${smartDecimal(summary.taxableAmount, genSettings.DecimalPoints, true)}</td>
                        <td>${smartDecimal(summary.cgstAmount, genSettings.DecimalPoints, true)}</td>
                        <td>${smartDecimal(summary.sgstAmount, genSettings.DecimalPoints, true)}</td>
                        <td>${smartDecimal(summary.igstAmount, genSettings.DecimalPoints, true)}</td>
                        <td>${smartDecimal(summary.totalTax, genSettings.DecimalPoints, true)}</td>
                        <td>${summary.itemCount}</td>
                    </tr>
                `;
                $taxTable.append(row);
            });
        }
    }

    // =============================================
    // EXTRA DISCOUNT METHODS
    // =============================================
    setExtraDiscountValue(value) {
        let parsedValue = parseFloat(value) || 0;
        
        // Validate based on type
        if (this.summary.extra.discountType === 'percentage') {
            parsedValue = Math.max(0, Math.min(parsedValue, 50)); // Max 50%
        } else {
            parsedValue = Math.max(parsedValue, 0);
        }
        
        this.summary.extra.discountValue = parsedValue;
        return this;
    }

    setExtraDiscountType(type) {
        const oldType = this.summary.extra.discountType;
        
        if (oldType !== type) {
            // Convert value if needed (like global discount)
            if (this.summary.extra.discountValue > 0) {
                const subtotal = this.summary.totals.subtotal || 0;
                
                if (oldType === 'percentage' && type === 'amount') {
                    // Convert percentage to amount
                    this.summary.extra.discountValue = subtotal * (this.summary.extra.discountValue / 100);
                } else if (oldType === 'amount' && type === 'percentage') {
                    // Convert amount to percentage
                    this.summary.extra.discountValue = subtotal > 0 ? (this.summary.extra.discountValue / subtotal) * 100 : 0;
                }
            }
            
            this.summary.extra.discountType = type;
            
            // Recalculate with new type
            this.updateExtraDiscount();
        }
        
        return this;
    }

    toggleRoundOff(enabled = null) {
        if (enabled !== null) {
            this.roundOffEnabled = enabled;
        } else {
            this.roundOffEnabled = !this.roundOffEnabled;
        }
        
        // Update checkbox state in UI
        $('#roundOffToggle').prop('checked', this.roundOffEnabled);
        
        // Recalculate with new roundoff setting
        this.calculateFinalTotals();
        this.updateSummaryUI();
        
        return this.roundOffEnabled;
    }

    // =============================================
    // ADDITIONAL CHARGES METHODS
    // =============================================
    setAdditionalCharge(type, amount) {
        if (this.summary.additionalCharges.hasOwnProperty(type)) {
            // Convert to object structure
            this.summary.additionalCharges[type] = {
                netAmount: parseFloat(amount) || 0,
                taxPercent: 0,
                taxAmount: 0,
                grossAmount: parseFloat(amount) || 0
            };
            this.updateAdditionalChargesTotal();
            this.updateSummary();
        }
        return this;
    }

    updateAdditionalChargesTotal() {
        
        let netTotal = 0;
        let taxTotal = 0;
        let grossTotal = 0;
        
        this.chargeTypes.forEach(type => {
            const charge = this.summary.additionalCharges[type];
            
            // Make sure charge is an object
            if (!charge || typeof charge !== 'object') {
                this.summary.additionalCharges[type] = {
                    netAmount: 0,
                    taxPercent: 0,
                    taxAmount: 0,
                    grossAmount: 0
                };
                return; // Skip to next
            }
            
            // Add to totals
            const net = parseFloat(charge.netAmount) || 0;
            const tax = parseFloat(charge.taxAmount) || 0;
            const gross = parseFloat(charge.grossAmount) || 0;
            
            netTotal += net;
            taxTotal += tax;
            grossTotal += gross;
            
        });
        
        this.summary.additionalCharges.total = {
            netAmount: netTotal,
            taxAmount: taxTotal,
            grossAmount: grossTotal
        };
    }

    // =============================================
    // HELPER METHODS
    // =============================================

    getItemById(id) {
        id = parseInt(id, 10);
        return this.map[id] || null;
    }

    getAllItems() {
        return this.items;
    }

    updateItemInStorage(id, item) {
        this.map[id] = item;
        const idx = this.items.findIndex(i => parseInt(i.id, 10) === id);
        if (idx >= 0) this.items[idx] = item;
    }

    clearAllItems() {
        this.items = [];
        this.map = {};
        this.summary = {
            items: { count: 0, totalQuantity: 0, taxableAmount: 0, discountTotal: 0, netAmount: 0 },
            taxSummary: {},
            taxRates: [],
            taxTotals: { cgstTotal: 0, sgstTotal: 0, igstTotal: 0, totalTax: 0 },
            additionalCharges: { shipping: 0, handling: 0, packing: 0, other: 0, total: 0 },
            extra: { discountType: 'percentage', discountValue: 0, discountAmount: 0, amountBeforeRoundOff: 0, roundOff: 0, finalAmount: 0 },
            totals: { subtotal: 0, totalAfterExtra: 0, grandTotal: 0 }
        };
        this.updateSummaryUI();
    }

    getFormattedSummary() {
        return {
            items: this.summary.items,
            taxSummary: this.summary.taxSummary,
            taxRates: this.summary.taxRates,
            taxTotals: this.summary.taxTotals,
            additionalCharges: this.summary.additionalCharges,
            extra: this.summary.extra,
            totals: this.summary.totals
        };
    }

    // =============================================
    // VALIDATION METHODS
    // =============================================
    validateDiscount(item, discountValue, discountType) {
        if (discountType === 'Percentage') {
            return discountValue >= 0 && discountValue <= 50;
        } else {
            const discountBase = this.getDiscountBaseForItem(item, this.calculateBaseTotals(item));
            return discountValue >= 0 && discountValue <= discountBase;
        }
    }

    validateExtraDiscount() {
        const extra = this.summary.extra;
        const subtotal = this.summary.totals.subtotal;
        
        if (extra.discountType === 'percentage') {
            return extra.discountValue >= 0 && extra.discountValue <= 50;
        } else {
            return extra.discountValue >= 0 && extra.discountValue <= subtotal;
        }
    }
    
    recalculateAllItemsForTax() {
        this.items.forEach(item => {
            const itemId = parseInt(item.id, 10);
            const updatedItem = {...item};
            
            if (customerInterState) {
                updatedItem.igstPercent = updatedItem.taxPercent;
                updatedItem.cgstPercent = 0;
                updatedItem.sgstPercent = 0;
            } else {
                updatedItem.cgstPercent = updatedItem.taxPercent / 2;
                updatedItem.sgstPercent = updatedItem.taxPercent / 2;
                updatedItem.igstPercent = 0;
            }
            
            const recalculatedItem = this.calculateRowItem(updatedItem);
            this.updateItemInStorage(itemId, recalculatedItem);
            updateTableRow(recalculatedItem);
        });
        
        this.updateSummary();
    }

    // Add tax to additional charges
    setAdditionalChargeWithTax(type, netAmount, taxPercent = 0) {
        
        const chargeAmount = parseFloat(netAmount) || 0;
        const taxRate = parseFloat(taxPercent) || 0;

        // Calculate tax amounts based on customerInterState
        let cgstPercent = 0, sgstPercent = 0, igstPercent = 0;
        let cgstAmount = 0, sgstAmount = 0, igstAmount = 0;

        cgstPercent = taxRate / 2;
        sgstPercent = taxRate / 2;
        igstPercent = taxRate;
        cgstAmount = this.roundValue((chargeAmount * cgstPercent / 100));
        sgstAmount = this.roundValue((chargeAmount * sgstPercent / 100));
        igstAmount = this.roundValue((chargeAmount * igstPercent / 100));
        
        // Calculate tax and gross
        const taxAmount = this.roundValue((chargeAmount * taxRate / 100));
        const grossAmount = this.roundValue((chargeAmount * (1 + taxRate / 100)));
        
        // Always create object structure
        this.summary.additionalCharges[type] = {
            netAmount: chargeAmount,
            taxPercent: taxRate,
            cgstPercent: cgstPercent,
            sgstPercent: sgstPercent,
            igstPercent: igstPercent,
            cgstAmount: cgstAmount,
            sgstAmount: sgstAmount,
            igstAmount: igstAmount,
            taxAmount: taxAmount,
            grossAmount: grossAmount
        };
        
        this.updateAdditionalChargesTotal();
        this.updateSummary();
        return this;
    }

    // Update totals calculation
    updateAdditionalChargesTotal() {

        let netTotal = 0;
        let cgstTotal = 0;
        let sgstTotal = 0;
        let igstTotal = 0;
        let taxTotal = 0;
        let grossTotal = 0;

        const charges = this.summary.additionalCharges;

        Object.entries(charges).forEach(([key, charge]) => {
            if (key === 'total') return;
            if (charge && typeof charge === 'object') {
                netTotal += parseFloat(charge.netAmount) || 0;
                cgstTotal += parseFloat(charge.cgstAmount || 0);
                sgstTotal += parseFloat(charge.sgstAmount || 0);
                igstTotal += parseFloat(charge.igstAmount || 0);
                taxTotal += parseFloat(charge.taxAmount || 0);
                grossTotal += parseFloat(charge.grossAmount || 0);
            }
        });
        
        charges.total = {
            netAmount: netTotal,
            cgstAmount: cgstTotal,
            sgstAmount: sgstTotal,
            igstAmount: igstTotal,
            taxAmount: taxTotal,
            grossAmount: grossTotal
        };

    }

    roundValue(value) {
        if (isNaN(value) || value === null || value === undefined) return 0;
        return parseFloat(parseFloat(value).toFixed(genSettings.DecimalPoints || 2));
    }

}

const billManager = new BillManager();

$(document).ready(function () {
    'use strict'

    $('#billTableBody').html(emptyTableTrInfo);
    
    loadSelect2Field('#prodCategory', 'Select Category');
    // searchProductInfo();

    $('#toggleChargesBtn').on('click', function (e) {
        e.preventDefault();
        const box = $('#additionalChargesBox');
        const icon = $(this).find('i');

        box.toggleClass('d-none');

        if (box.hasClass('d-none')) {
            icon.removeClass('bx-minus-circle').addClass('bx-plus-circle');
            $(this).text(' Additional Charges').prepend(icon);
        } else {
            icon.removeClass('bx-plus-circle').addClass('bx-minus-circle');
            $(this).text(' Hide Charges').prepend(icon);
        }
    });

    $('#prodQuantity').on('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $('#transAddToCartForm').trigger('click');
        }
    });

    $('#prodCategory').on('change', function (e) {
        e.preventDefault();
        productPreloaded = false;
        lastTerm = '';
        $('#searchProductInfo').empty().val(null).trigger('change');
    });

    $('#transAddToCartForm').click(function(e) {
        e.preventDefault();
        
        var getProd = $('#searchProductInfo').find('option:selected').val();
        if (!hasValue(getProd)) {
            $('#errSearchProd').fadeIn(150);
            setTimeout(() => $('#errSearchProd').fadeOut(150), 2000);
            return false;
        }

        var getProdQty = $('#prodQuantity').val().trim();
        if (!hasValue(getProdQty)) {
            $('#errorProdQty').html('<span class="icon">!</span>Please enter quantity.').fadeIn(150);
            setTimeout(() => $('#errorProdQty').fadeOut(150), 2000);
            return false;
        }

        const uom = $('#searchProductInfo option:selected').data('primaryunit');
        if (INTEGER_ONLY_UOMS.includes(uom)) {
            if (!isIntegerValue(getProdQty)) {
                $('#errorProdQty').html('<span class="icon">!</span>Decimal quantity not allowed for ' + uom).fadeIn(150);
                setTimeout(() => $('#errorProdQty').fadeOut(150), 2000);
                return false;
            }
        }

        $('#errSearchProd,#errorProdQty').hide();

        pushBillItems($('#searchProductInfo option:selected').data('allfields'), getProdQty);
        
        $('#searchProductInfo').val(null).trigger('change');
        $('#prodQuantity').val('').trigger('change');
        
        // $('#searchProductInfo').focus().select2('open');
        
    });

    $(document).on('focus', '.updateAllBillAmounts', function () {
        const input = this;
        setTimeout(() => input.select(), 0);
    });

    $(document).on('blur', '.updateAllBillAmounts', function () {
        let val = $(this).val();

        if (val === '' || val === '.') {
            $(this).val('0');
            return;
        }

        if (val.startsWith('.')) val = '0' + val;
        if (val.endsWith('.')) val = val.slice(0, -1);

        $(this).val(val);
    });

    // In $(document).ready, update the discount input handler:
    $(document).on('input', '.updateAllBillAmounts', function (e) {
        e.preventDefault();

        const $input = $(this);
        const $row = $input.closest('tr');
        const getId = $row.data('id');
        const fieldName = $input.attr('name');
        let newValue = $input.val();

        // Detect discount field
        const isDiscountField = fieldName.includes('_discount') && !fieldName.includes('Type');
        if (isDiscountField) {
            handleDiscountFieldInput($input, $row, getId);
            return;
        } else {

            // Allow typing states
            if (newValue === '' || newValue === '.' || newValue.endsWith('.')) {
                return;
            }

            // Clean value
            newValue = newValue.replace(/[^0-9.]/g, '');

            // Allow only one dot
            const parts = newValue.split('.');
            if (parts.length > 2) {
                newValue = parts[0] + '.' + parts.slice(1).join('');
            }

            $input.val(newValue);

        }

        const fieldMap = {
            [`bm_${getId}_qty`]: 'quantity',
            [`bm_${getId}_unitPrice`]: 'unitPrice',
            [`bm_${getId}_sellingPrice`]: 'sellingPrice',
            [`bm_${getId}_discountType`]: 'discountType'
        };

        const bmField = fieldMap[fieldName];
        if (!bmField) return;

        let parsedValue = newValue;
        if (bmField !== 'discountType') {
            parsedValue = parseFloat(newValue) || 0;
        }

        billManager.updateItem(getId, bmField, parsedValue);
        
    });

    // Handle discount type change
    $(document).on('change', '.discTypeActionBillAmounts', function(e) {
        e.preventDefault();
        
        let getId = $(this).closest('tr').data('id');
        const newType = $('#bm_'+getId+'_discountType').val();
        
        // Get the discount input field
        let discountInput = $('#bm_'+getId+'_discount');
        if (newType === 'Percentage') {
            discountInput.attr('maxlength', '5');
            discountInput.attr('pattern', '^\d{1,2}(\.\d{0,2})?$');
        } else {
            discountInput.attr('maxlength', '10');
            discountInput.attr('pattern', `^\\d{1,6}(\\.\\d{0,${genSettings.DecimalPoints}})?$`);
        }
        
        // Get current item
        const currentItem = billManager.getItemById(getId);
        
        if (!currentItem) return;
        
        const currentDiscountType = currentItem.discountType || 'Percentage';
        
        // Get and validate current value
        let currentInputValue = discountInput.val().trim();
        currentInputValue = validateDiscountInputOnTypeChange(currentInputValue, newType);
        discountInput.val(currentInputValue);
        
        const currentDiscountValue = parseFloat(currentInputValue) || 0;
        
        // Store current value before conversion
        currentItem.original_discount_before_type_change = currentDiscountValue;
        currentItem.previous_discountType = currentDiscountType;
        
        // Update discount type
        billManager.updateItem(getId, 'discountType', newType);
        
        // After update, sync the input field with converted value
        setTimeout(() => {
            const updatedItem = billManager.getItemById(getId);
            if (updatedItem) {
                let displayValue = updatedItem.discount;
                if (newType === 'Percentage') {
                    displayValue = smartDecimal(displayValue, 2);
                } else {
                    displayValue = smartDecimal(displayValue, genSettings.DecimalPoints);
                }
                discountInput.val(displayValue);
            }
        }, 10);

    });

    // Handle discount application scope change
    $('#discApplyFor').on('change', function(e) {
        e.preventDefault();
        
        const newScope = $(this).val(); // "TotalAmount", "PriceWithTax", "UnitPrice", "NetAmount"
        
        // Store the new scope in a global variable or data attribute if needed
        $(this).data('current-scope', newScope);
        
        // Recalculate ALL items with the new discount scope
        recalculateAllItemsWithNewScope(newScope);
    });

    $(document).on('click', '.deleteBillItem', function(e) {
        e.preventDefault();
        
        const itemId = $(this).data('id');
        const $row = $(this).closest('tr');
        
        const removed = billManager.removeItem(itemId);
        if (removed) {
            // Remove row from table
            $row.remove();
            
            // Re-number the remaining rows if needed
            renumberTableRows();
        }
    });

    $('#globalDiscount').on('input change', function() {
        const inputValue = $(this).val();
        
        // If the value ends with a dot (user is typing decimal), don't process yet
        if (inputValue.endsWith('.')) {
            return; // Exit early, let user finish typing
        }
        
        // Also check if the value is empty or just a dot
        if (inputValue === '' || inputValue === '.') {
            billManager.setGlobalDiscountPercent(0);
            return;
        }
        
        const percent = parseFloat(inputValue) || 0;
        
        // Enforce max limit of 50
        if (percent > 50) {
            $(this).val('50');
            billManager.setGlobalDiscountPercent(50);
        } else {
            billManager.setGlobalDiscountPercent(percent);
        }
    });

    $('#clearGlobalDiscount').on('click', function() {
        $('#globalDiscount').val('0').trigger('input');
    });

    // Handle extra discount with proper validation
    $('#extraDiscount').on('input', function() {
        let value = $(this).val().trim();
        const type = $('#extDiscountType').find('option:selected').val();
        
        // Clean input
        if (value === '' || value === '.' || value === null) {
            value = '0';
            $(this).val('0');
        }
        
        // Validate based on type
        if (type === 'Percentage') {
            // Remove non-numeric except decimal
            value = value.replace(/[^0-9.]/g, '');
            
            // Ensure only one decimal point
            const parts = value.split('.');
            if (parts.length > 2) {
                value = parts[0] + '.' + parts.slice(1).join('');
            }
            
            // Limit to 2 decimal places for percentage
            if (parts.length === 2) {
                parts[1] = parts[1].slice(0, 2);
                value = parts[0] + '.' + parts[1];
            }
            
            // Cap at 50%
            const parsedValue = parseFloat(value) || 0;
            if (parsedValue > 50) {
                value = '50';
                $(this).val('50');
            }
        }
        
        if (billManager) {
            if (type === 'Percentage') {
                billManager.setExtraDiscountValue(value);
                billManager.setExtraDiscountType('percentage');
            } else {
                billManager.setExtraDiscountValue(value);
                billManager.setExtraDiscountType('amount');
            }
            
            billManager.updateSummary();
        }
    });

    // Handle extra discount type change with conversion
    $('#extDiscountType').on('change', function() {
        const type = $(this).val();
        const extraDiscountInput = $('#extraDiscount');
        
        if (billManager) {
            // Get current value
            const currentValue = parseFloat(extraDiscountInput.val()) || 0;
            
            // Store current value before conversion
            billManager.summary.extra.discountValue = currentValue;
            
            // Change type (this handles conversion internally)
            billManager.setExtraDiscountType(type.toLowerCase());
            
            // Update input with converted value
            const convertedValue = billManager.summary.extra.discountValue || 0;
            
            // Format based on type
            if (type === 'Percentage') {
                extraDiscountInput.val(smartDecimal(convertedValue, 2));
            } else {
                extraDiscountInput.val(smartDecimal(convertedValue, genSettings.DecimalPoints));
            }
            
            // Update summary
            billManager.updateSummary();
        }
    });
    
    // Handle extra discount with proper validation
    $('#extraDiscount').on('input', function() {
        let value = $(this).val().trim();
        const type = $('#extDiscountType').find('option:selected').val();
        
        // Clean input
        if (value === '' || value === '.' || value === null) {
            value = '0';
            $(this).val('0');
        }
        
        // Validate based on type (same as global discount logic)
        if (type === 'Percentage') {
            // Use same validation as global discount
            value = validatePercentageInput(this, 50);
        } else {
            // For amount, just validate as number
            value = value.replace(/[^0-9.]/g, '');
            const parts = value.split('.');
            if (parts.length > 2) {
                value = parts[0] + '.' + parts.slice(1).join('');
            }
            
            // Limit to 2 decimal places
            if (parts.length === 2) {
                parts[1] = parts[1].slice(0, genSettings.DecimalPoints);
                value = parts[0] + '.' + parts[1];
            }
            
            $(this).val(value);
        }
        
        if (billManager) {
            const parsedValue = parseFloat(value) || 0;
            
            // Set value and type
            billManager.setExtraDiscountValue(parsedValue);
            
            // Make sure type is set (in case it wasn't already)
            if (type === 'Percentage') {
                billManager.setExtraDiscountType('percentage');
            } else {
                billManager.setExtraDiscountType('amount');
            }
            
            // Update summary with new discount
            billManager.updateSummary();
        }
    });

    // Handle round off toggle
    $('#roundOffToggle').on('change', function() {
        const isEnabled = $(this).is(':checked');
        if (billManager) {
            billManager.toggleRoundOff(isEnabled);
            
            // Update UI immediately
            const roundOffAmount = isEnabled ? (billManager.summary.extra.roundOff || 0) : 0;
            $('.bill_rndoff_amt').text(smartDecimal(roundOffAmount, genSettings.DecimalPoints, true));
            
        }
    });

    $('.additional-charge-percent,.additional-charge-withouttax,.additional-charge-withtax').on('focus', function() {
        const input = this;
        setTimeout(() => input.select(), 0);
    });

    // Handle percentage field changes
    $('.additional-charge-percent').on('input', function() {
        const chargeType = $(this).data('type');
        const value = $(this).val();
        updateAdditionalChargeFields(chargeType, 'percent', value);
    });

    $('.additional-charge-withtax').on('blur', function () {
        const val = parseFloat(this.value) || 0;
        this.value = smartDecimal(val, 8);
    });
    
    // Handle without-tax field changes
    $('.additional-charge-withouttax').on('input', function() {
        const chargeType = $(this).data('type');
        const value = $(this).val();
        updateAdditionalChargeFields(chargeType, 'withoutTax', value);
    });

    $('.additional-charge-withouttax').on('blur', function () {
        const val = parseFloat(this.value) || 0;
        this.value = smartDecimal(val, genSettings.DecimalPoints);
    });
    
    // Handle with-tax field changes
    $('.additional-charge-withtax').on('input', function() {
        const chargeType = $(this).data('type');
        const value = $(this).val();
        updateAdditionalChargeFields(chargeType, 'withTax', value);
    });

    $('.additional-charge-withtax').on('blur', function () {
        const val = parseFloat(this.value) || 0;
        this.value = smartDecimal(val, genSettings.DecimalPoints);
    });
    
    // Handle tax dropdown changes
    $('.additional-charge-tax').on('change', function() {
        const chargeType = $(this).data('type');
        const $selected = $(this).find('option:selected');
        const taxValue = $selected.data('percent') || '0';
        if (!billManager || !billManager.items || billManager.items.length === 0) {
            if (taxValue !== '0') {
                Swal.fire({icon: 'error', title: 'No Items', text: 'Please add items to bill before setting tax on additional charges'});
                $(this).prop('selectedIndex', 0);
                return;
            }
        }        
        // Call update with tax change
        updateAdditionalChargeFields(chargeType, 'tax', taxValue);
    });

    $('#taxBreakupToggle').on('click', function() {
        var $panel = $('#taxBreakupPanel');
        var $icon = $('.tax-toggle-icon');
        if ($panel.is(':hidden')) {
            $panel.slideDown(300);
            $icon.removeClass('bx bxs-show').addClass('bx bxs-hide');
        } else {
            $panel.slideUp(300);
            $icon.removeClass('bx bxs-hide').addClass('bx bxs-show');
        }
    });

});

// Helper function to get display name for scope
function getScopeDisplayName(scope) {
    switch(scope) {
        case 'TotalAmount':
            return 'Total Amount';
        case 'PriceWithTax':
            return 'Price With Tax';
        case 'UnitPrice':
            return 'Unit Price';
        case 'NetAmount':
            return 'Net Amount';
        default:
            return scope;
    }
}

// Function to recalculate all items with new discount scope
function recalculateAllItemsWithNewScope(newScope) {
    // Get all items from BillManager
    const allItems = billManager.getAllItems();
    
    // If no items, nothing to do
    if (allItems.length === 0) {
        return;
    }
    
    // Flag to track if any changes were made
    let changesMade = false;
    
    allItems.forEach(item => {
        const itemId = parseInt(item.id, 10);
        const currentItem = billManager.getItemById(itemId);
        
        if (currentItem) {
            // Check if the discount scope change affects this item
            // Only items with active discounts need recalculation
            if (currentItem.discount > 0 || currentItem.discount_amount > 0) {
                // Create updated item with scope change flag
                const updatedItem = {...currentItem};
                updatedItem._lastChanged = 'discount_scope_change';
                updatedItem.previous_discount_scope = currentItem.discount_scope || 'TotalAmount';
                updatedItem.discount_scope = newScope;
                
                // Recalculate the item with new scope
                const recalculatedItem = billManager.calculateRowItem(updatedItem);
                
                // Only update if something changed
                if (JSON.stringify(currentItem) !== JSON.stringify(recalculatedItem)) {
                    // Update in BillManager
                    billManager.map[itemId] = recalculatedItem;
                    
                    const idx = billManager.items.findIndex(i => parseInt(i.id, 10) === itemId);
                    if (idx >= 0) billManager.items[idx] = recalculatedItem;
                    
                    // Update UI
                    updateTableRow(recalculatedItem);
                    changesMade = true;
                }
            }
        }
    });
    
    // Update summary if any changes were made
    if (changesMade) {
        billManager.updateSummary();
    }
}

function updateTableRow(productRow) {
    const pid = Number(productRow.id);
    const $row = $(`#billTableBody tr[data-id="${pid}"]`);
    if($row) {
        // Always show original prices in inputs
        $row.find('#bm_'+pid+'_qty').val(productRow.quantity);
        $row.find('#bm_'+pid+'_unitPrice').val(smartDecimal(productRow.orgunitprice, 8));
        $row.find('#bm_'+pid+'_sellingPrice').val(smartDecimal(productRow.orgselngprice, genSettings.DecimalPoints));
        
        // Update discount field
        $row.find('#bm_'+pid+'_discount').val(productRow.discount);

        // Update discount type dropdown
        $row.find('#bm_'+pid+'_discountType').val(productRow.discountType || 'Percentage');

        // Show/hide effective price display based on discount
        if (productRow.discount > 0) {
            $row.find('.bm_efft_'+pid+'_price').removeClass('d-none');
            $row.find('#bm_'+pid+'_aftdisc_unitPrice').text(smartDecimal(productRow.effectiveUnitPrice || productRow.unitPrice, 8));
            $row.find('#bm_'+pid+'_aftdisc_sellingPrice').text(smartDecimal(productRow.effectiveSellingPrice || productRow.sellingPrice, genSettings.DecimalPoints));
        } else {
            $row.find('.bm_efft_'+pid+'_price').addClass('d-none');
        }

        // Update totals
        $row.find('#bm_'+pid+'_netamount').text(smartDecimal(productRow.net_total, genSettings.DecimalPoints, true));
        $row.find('#bm_'+pid+'_tot_unit_amount').text(`${smartDecimal(productRow.line_total, genSettings.DecimalPoints, true)}`);
        $row.find('#bm_'+pid+'_taxAmount').text(`${smartDecimal(productRow.taxAmount, genSettings.DecimalPoints, true)}`);
        
        // Visual indicators for override status
        const discountInput = $row.find('#bm_'+pid+'_discount');
        if (productRow.discount_is_global && !productRow.overrides_global_discount) {
            discountInput.attr('title', 'Global discount applied');
        } else if (productRow.overrides_global_discount) {
            discountInput.attr('title', 'Overrides global discount');
        } else {
            discountInput.removeAttr('title');
        }
    }
}

function searchCustomers(key) {
    $("#"+key).select2({
        placeholder: "Search Customer by Name, Email, Mobile, GSTIN, Company, Contact Person.",
        minimumInputLength: 0,
        allowClear: true,
        escapeMarkup: function (markup) { return markup; },
        ajax: {
            url: '/transactions/searchCustomers',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                AjaxLoading = 0;
                return { term: params.term, type: 'public' };
            },
            processResults: function (data) {
                AjaxLoading = 1;
                return { results: data.Lists };
            },
            cache: true
        }
    }).on('select2:select', function (e) {
        var data = e.params.data;
        if (data.address) {
            var addrHtml = `
                <div><strong>Shipping Address:</strong></div>
                <div>${data.address.Line1 || ''}</div>
                <div>${data.address.Line2 || ''}</div>
                <div>${data.address.City || ''}, ${data.address.State || ''} - ${data.address.Pincode || ''}</div>
            `;
            $("#customerAddressBox").html(addrHtml).removeClass('d-none');
        } else {
            $("#customerAddressBox").addClass('d-none').empty();
        }
    }).on('select2:clear', function () {
        $("#customerAddressBox").addClass('d-none').empty();
    }).on('select2:close', function () {
        AjaxLoading = 1;
    });
}

function transDatePickr(FieldName, IsModal = '', dateFormat = 'Y-m-d', restrictPastDate = false, restrictFutureDate = false, setTodaysDate = false, useAltInput = true,
altFormat = 'd-m-Y', minDateField = '') {

    const el = document.querySelector(FieldName);
    if (!el) return;

    const existingVal = el.value?.trim() || '';

    const options = {
        dateFormat,
        altInput: useAltInput,
        altFormat,
        allowInput: false,
        clickOpens: true
    };
    if (IsModal) {
        const container = document.querySelector(IsModal + ' .modal-body');
        if (container) options.appendTo = container;
    }
    if (restrictPastDate) options.minDate = 'today';
    if (restrictFutureDate) options.maxDate = 'today';

    if (existingVal) {
        options.defaultDate = existingVal;
    } else if (setTodaysDate) {
        const today = new Date();
        const pad = n => String(n).padStart(2, '0');
        const yyyy = today.getFullYear();
        const mm = pad(today.getMonth() + 1);
        const dd = pad(today.getDate());
        options.defaultDate =
        dateFormat === 'Y-m-d' ? `${yyyy}-${mm}-${dd}` :
        dateFormat === 'd-m-Y' ? `${dd}-${mm}-${yyyy}` :
        dateFormat === 'm/d/Y' ? `${mm}/${dd}/${yyyy}` :
        today;
    }
    if (minDateField) {
        const refEl = document.querySelector(minDateField);
        const refVal = refEl?.value?.trim();
        if (refVal) options.minDate = refVal;
    }
    if (el._flatpickr) {
        el._flatpickr.destroy();
    }

    flatpickr(FieldName, options);

}

function setupTransactionValidity(quotationSel, validityDaysSel, validityDateSel) {

    const quotationEl    = document.querySelector(quotationSel);
    const validityDaysEl = document.querySelector(validityDaysSel);
    const validityDateEl = document.querySelector(validityDateSel);

    if (!quotationEl || !validityDaysEl || !validityDateEl) return;
    if (!quotationEl._flatpickr || !validityDateEl._flatpickr) return;

    const qPicker = quotationEl._flatpickr;
    const vPicker = validityDateEl._flatpickr;

    // Ensure validityDate can't be before quotationDate
    function enforceMinDate() {
        const qDate = qPicker.selectedDates[0];
        if (qDate) vPicker.set('minDate', qDate);
    }

    // Compute validity date = quotation date + days
    function updateValidityDateFromDays() {
        const qDate = qPicker.selectedDates[0];
        const days = parseInt(validityDaysEl.value, 10) || 0;
        if (!qDate) return;

        const newDate = new Date(qDate);
        newDate.setDate(newDate.getDate() + days);

        enforceMinDate();
        vPicker.setDate(newDate, true);
    }

    // Compute validity days from validity date (positive only)
    function updateDaysFromValidityDate(selectedDates) {
        const vDate = selectedDates[0] || vPicker.selectedDates[0];
        const qDate = qPicker.selectedDates[0];
        if (!vDate || !qDate) return;

        const diff = Math.round((vDate - qDate) / (1000 * 60 * 60 * 24));

        // Positive-only rule: clamp at 0
        validityDaysEl.value = Math.max(diff, 0);

        // If user picked a date before quotation date, snap back to minDate
        if (diff < 0) {
        vPicker.setDate(qDate, true);
        }
    }

    // Events
    qPicker.set('onChange', updateValidityDateFromDays);
    vPicker.set('onChange', updateDaysFromValidityDate);
    validityDaysEl.addEventListener('input', updateValidityDateFromDays);

    // Initial sync
    enforceMinDate();
    updateValidityDateFromDays();

}

function searchProductInfo() {
    $('#searchProductInfo').select2({
        placeholder: 'Search product or scan barcode',
        minimumInputLength: 0,
        allowClear: true,
        width: 'resolve',
        escapeMarkup: function (markup) { return markup; },
        dropdownParent: $('#searchProductGroup'),
        language: {
            searching: function () {
                return productPreloaded ? '' : 'Searching…';
            }
        },
        ajax: {
            url: '/transactions/searchTransProducts',
            dataType: 'json',
            delay: 250,
            transport: function (params, success, failure) {
                const term = params.data.term || '';
                if (productPreloaded && term === '') {
                    return null;
                }
                if (productPreloaded && term === '') {
                    return null;
                }
                if (term.length > 0 && term.length < 2) {
                    return null;
                }
                lastTerm = term;
                const request = $.ajax(params);
                request.then(success);
                request.fail(failure);
                return request;
            },
            data: function (params) {
                AjaxLoading = 0;
                return {
                    term: params.term,
                    categuid: $('#prodCategory').find('option:selected').val(),
                    type: 'public' 
                };
            },
            processResults: function (data) {
                AjaxLoading = 1;
                if (!lastTerm && data.Lists && data.Lists.length) {
                    productPreloaded = true;
                }
                return { results: data.Lists };
            },
            cache: true
        },
        templateResult: function (data) {
            if (!data.id) return data.text;
            const hsnText = data.hsnCode ? ` | HSN: ${data.hsnCode}` : '';
            const taxBreakup = data.cgstPercent ? `CGST: ${data.cgstPercent}%, SGST: ${data.sgstPercent}%, IGST: ${data.igstPercent}%` : `Tax: ${data.taxPercent || '0'}%`;
            return $(`
                <div class="d-flex justify-content-between flex-column flex-md-row">
                    <div class="text-start">
                        <div class="text-primary fw-semibold">${data.text}</div>
                        <div class="text-muted transtext-small">
                            Qty: ${data.availableQuantity || '0'}${hsnText} | ${data.primaryUnit || '-'} | ${data.category || ''}
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="text-primary fw-semibold">${genSettings.CurrenySymbol} ${smartDecimal(data.sellingPrice, genSettings.DecimalPoints, true)}</div>
                        <div class="text-muted transtext-small">incl tax: ${data.taxPercent || '0'}%</div>
                    </div>
                </div>
            `);
        },
        templateSelection: function (data) {
            return data.text || '';
        },
    }).on('select2:open', function () {
        const results = $('.select2-results__options');
        if (results.length) {
            results.find('.select2-results__option--loading').remove();
            results.find('.select2-results__message').remove();
            results.children('li').each(function () {
                if ($(this).text().trim() === '') {
                    $(this).remove();
                }
            });
        }
    }).on('select2:select', function (e) {
        const data = e.params.data;
        let $option = $(this).find('option[value="'+data.id+'"]');
        if (!$option.length) {
            $option = $('<option>').val(data.id).text(data.text);
            $(this).append($option);
        }
        $option.attr('data-allfields', JSON.stringify(data));
        $option.attr('data-primaryunit', data.primaryUnit);
        $('#prodQuantity').focus();
    }).on('select2:close', function () {
        lastTerm = '';
        AjaxLoading = 1;
    });
}

function pushBillItems(productData, qty) {
    let existingItem = billManager.getItemById(productData.id);
    if (existingItem) {
        Swal.fire({icon: "error", title: "Oops...", text: "Item already moved to cart."});
        return false;
    } else {
        billManager.addItem(productData, qty);
        formationTableBillItems(billManager.getItemById(productData.id));
    }
}

function formationTableBillItems(productRow) {

    let rowCount = $('#billTableBody tr[data-id]').length;

    const hsnText = productRow.hsnCode ? `<div class="transtext-small text-muted">HSN: ${productRow.hsnCode}</div>` : '';

    // const discTypeHtml = discTypeInfo.map(d => `<option value="${d.Name}">${d.Symbol}</option>` ).join('');
    const discTypeHtml = `
        <option value="Percentage" ${productRow.discountType === 'Percentage' ? 'selected' : ''}>%</option>
        <option value="Amount" ${productRow.discountType === 'Amount' ? 'selected' : ''}>${genSettings.CurrenySymbol}</option>
    `;

    const getPrimUnit = productRow.primaryUnit;
    let qtyHtml;
    if (INTEGER_ONLY_UOMS.includes(getPrimUnit)) {
        qtyHtml = `<input type="text" class="form-control form-control-sm updateAllBillAmounts" name="bm_${productRow.id}_qty" id="bm_${productRow.id}_qty" min="0" placeholder="Quantity" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength); handleOnlyNumbers(this)" maxLength="${genSettings.QtyMaxLength}" pattern="[0-9]*" value="${productRow.quantity}" onpaste="pasteOnlyNumbers(event)" ondrop="dropOnlyNumbers(event)" />`;
    } else {
        qtyHtml = `<input type="text" class="form-control form-control-sm updateAllBillAmounts" name="bm_${productRow.id}_qty" id="bm_${productRow.id}_qty" min="0" placeholder="Quantity" onkeydown="return handleDotOnly(event)" oninput="this.value=this.value.slice(0,this.maxLength); validatePriceInput(this, ${genSettings.QtyMaxLength}, ${genSettings.DecimalPoints})" maxLength="${genSettings.QtyMaxLength}" pattern="^\d{1,${genSettings.QtyMaxLength}}(\.\d{0,${genSettings.DecimalPoints}})?$" onpaste="handlePricePaste(event, ${genSettings.QtyMaxLength}, ${genSettings.DecimalPoints})" ondrop="handlePriceDrop(event, ${genSettings.QtyMaxLength}, ${genSettings.DecimalPoints})" value="${productRow.quantity}" />`;
    }

    let discBfrPrice = parseInt(productRow.discount, 10) ? '' : 'd-none';

    let tableData = `
        <tr data-id="${productRow.id}">
            <td>
                <div class="text-primary fw-semibold">${productRow.text}</div>
                <div class="transtext-small text-muted">#<span id="sequenceId_${productRow.id}">${rowCount+1}</span> Stock: ${productRow.availableQuantity} ${productRow.primaryUnit}</div>
                ${hsnText}
            </td>
            <td>
                <div class="input-group input-group-merge input-group-sm">
                    ${qtyHtml}
                    <input type="text" readonly class="form-control form-control-sm" value="${getPrimUnit}" />
                </div>
            </td>
            <td>
                <div class="input-group input-group-merge">
                    <span class="input-group-text">${genSettings.CurrenySymbol}</span>
                    <input type="text" class="form-control form-control-sm updateAllBillAmounts" name="bm_${productRow.id}_unitPrice" id="bm_${productRow.id}_unitPrice" min="0" placeholder="Unit Price" onkeydown="return handleDotOnly(event)" oninput="this.value=this.value.slice(0,this.maxLength); validatePriceInput(this, ${genSettings.PriceMaxLength}, 8)" maxLength="${genSettings.PriceMaxLength + 9}" pattern="^\d{1,${genSettings.PriceMaxLength}}(\.\d{0,8})?$" onpaste="handlePricePaste(event, ${genSettings.PriceMaxLength}, 8)" ondrop="handlePriceDrop(event, ${genSettings.PriceMaxLength}, 8)" value="${smartDecimal(productRow.orgunitprice, 8)}" />
                </div>
                <div class="transtext-small text-muted text-warning bm_efft_${productRow.id}_price ${discBfrPrice}">aft disc: <span id="bm_${productRow.id}_aftdisc_unitPrice">${smartDecimal(productRow.unitPrice, 8)}</span></div>
            </td>
            <td>
                <div class="input-group input-group-merge">
                    <span class="input-group-text">${genSettings.CurrenySymbol}</span>
                    <input type="text" class="form-control form-control-sm updateAllBillAmounts" name="bm_${productRow.id}_sellingPrice" id="bm_${productRow.id}_sellingPrice" min="0" placeholder="Tax Price" onkeydown="return handleDotOnly(event)" oninput="this.value=this.value.slice(0,this.maxLength); validatePriceInput(this, ${genSettings.PriceMaxLength}, ${genSettings.DecimalPoints})" maxLength="${genSettings.PriceMaxLength}" pattern="^\d{1,${genSettings.PriceMaxLength}}(\.\d{0,${genSettings.DecimalPoints}})?$" onpaste="handlePricePaste(event, ${genSettings.PriceMaxLength}, ${genSettings.DecimalPoints})" ondrop="handlePriceDrop(event, ${genSettings.PriceMaxLength}, ${genSettings.DecimalPoints})" value="${smartDecimal(productRow.orgselngprice, genSettings.DecimalPoints)}" />
                </div>
                <div class="transtext-small text-muted text-warning bm_efft_${productRow.id}_price ${discBfrPrice}">aft disc: <span id="bm_${productRow.id}_aftdisc_sellingPrice">${smartDecimal(productRow.sellingPrice, genSettings.DecimalPoints)}</span></div>
            </td>
            <input type="text" class="form-control" name="SellingPrice" id="SellingPrice" min="0" placeholder="Enter Selling Price" onkeydown="return handleDotOnly(event)" oninput="this.value=this.value.slice(0,this.maxLength); validatePriceInput(this, 12, 2)" maxlength="12" pattern="^\d{1,12}(\.\d{0,2})?$" onpaste="handlePricePaste(event, 12, 2)" ondrop="handlePriceDrop(event, 12, 2)" required="">
            <td>
                <div class="input-group input-group-merge w-75">
                    <input class="form-control form-control-sm updateAllBillAmounts" type="text" id="bm_${productRow.id}_discount" name="bm_${productRow.id}_discount" min="0" step="0.01" placeholder="Discount" onkeydown="return handleDotOnly(event)" oninput="this.value=this.value.slice(0,this.maxLength); validatePriceInput(this, ${genSettings.PriceMaxLength}, ${genSettings.DecimalPoints})" maxLength="${genSettings.PriceMaxLength}" pattern="^\d{1,${genSettings.PriceMaxLength}}(\.\d{0,${genSettings.DecimalPoints}})?$" onpaste="handlePricePaste(event, ${genSettings.PriceMaxLength}, ${genSettings.DecimalPoints})" ondrop="handlePriceDrop(event, ${genSettings.PriceMaxLength}, ${genSettings.DecimalPoints})" value="${productRow.discount || 0}" title="${productRow.discount_is_global ? 'Global discount applied' : ''}" />
                    <select class="form-select form-select-sm px-2 w-auto discTypeActionBillAmounts" id="bm_${productRow.id}_discountType" name="bm_${productRow.id}_discountType">${discTypeHtml}</select>
                </div>
            </td>
            <td class="align-middle">
                <div class="d-flex align-items-center justify-content-between h-100">
                    <!-- LEFT SIDE: Delete Icon -->
                    <div class="flex-shrink-0 me-3">
                        <button type="button" class="btn btn-sm btn-outline-danger deleteBillItem" data-id="${productRow.id}" title="Remove item"><i class="bx bx-trash"></i></button>
                    </div>
                    <!-- RIGHT SIDE: Amount Details -->
                    <div class="text-end flex-grow-1">
                        <div class="text-primary fw-semibold">${genSettings.CurrenySymbol} <span id="bm_${productRow.id}_netamount"> ${smartDecimal(productRow.net_total, genSettings.DecimalPoints, true)}</span></div>
                        <div class="transtext-small text-muted"><span id="bm_${productRow.id}_tot_unit_amount">${smartDecimal(productRow.line_total, genSettings.DecimalPoints, true)}</span> + <span id="bm_${productRow.id}_taxAmount">${smartDecimal(productRow.taxAmount, genSettings.DecimalPoints, true)}</span> (${productRow.taxPercent}%)</div>
                    </div>
                </div>
            </td>
        </tr>
    `;
    if(rowCount == 0) {
        $('#billTableBody').html(tableData);
    } else {
        $('#billTableBody').append(tableData);
    }
}

function isIntegerValue(value) {
    return /^\d+$/.test(value);
}

function renumberTableRows() {
    // Update sequence numbers in the table
    $('#billTableBody tr').each(function(index) {
        const $row = $(this);
        const itemId = $row.data('id');
        $(`#sequenceId_${itemId}`).text(index + 1);
    });
}

function validatePercentageInput(input, maxPercentage = 50) {
    let value = input.value.trim();
    
    // Handle empty/blank
    if (value === '' || value === '.' || value === null) {
        input.value = '0';
        return 0;
    }
    
    // Remove non-numeric except decimal
    value = value.replace(/[^0-9.]/g, '');
    
    // Ensure only one decimal point
    const parts = value.split('.');
    if (parts.length > 2) {
        value = parts[0] + '.' + parts.slice(1).join('');
    }
    
    // Limit decimal places to 2
    if (parts.length === 2) {
        parts[1] = parts[1].slice(0, 2);
        value = parts[0] + '.' + parts[1];
    }
    
    const parsedValue = parseFloat(value) || 0;
    
    // Enforce limits
    if (parsedValue > maxPercentage) {
        input.value = maxPercentage.toString();
        return maxPercentage;
    }
    
    if (parsedValue < 0) {
        input.value = '0';
        return 0;
    }
    
    input.value = value;
    return parsedValue;
}

// Helper: Round to DecimalPoints consistently
function roundToDecimal(value) {
    return parseFloat(value.toFixed(genSettings.DecimalPoints));
}

function validateDiscountFieldInput(input, discountType) {
    let value = input.value.trim();
    const maxLength = parseInt(input.getAttribute('maxlength'), 10) || 10;

    // Handle empty or just dot
    if (!value || value === '.') {
        input.value = '';
        return;
    }

    // Allow only digits and one dot
    value = value.replace(/[^0-9.]/g, '');
    const parts = value.split('.');

    // Collapse multiple dots
    if (parts.length > 2) {
        value = parts[0] + '.' + parts.slice(1).join('');
    }

    // Normalize leading zeros (e.g. "0005" -> "5", "05.2" -> "5.2")
    if (parts[0].length > 1 && parts[0].startsWith('0')) {
        parts[0] = String(parseInt(parts[0], 10));
    }

    if (discountType === 'Percentage') {
        // Limit to 2 decimals
        if (parts.length === 2) {
            parts[1] = parts[1].slice(0, 2);
        }
        value = parts.join('.');

        // Enforce max 50
        let numValue = parseFloat(value) || 0;
        if (numValue > 50) {
            value = '50.00';
        }

        input.setAttribute('maxlength', '5'); // "50.00"
    } else if (discountType === 'Amount') {
        // Limit decimals to configured precision
        if (parts.length === 2) {
            parts[1] = parts[1].slice(0, genSettings.DecimalPoints || 2);
        }
        value = parts.join('.');
        input.setAttribute('maxlength', '10');
    }

    // Apply validated value
    if (value.length > maxLength) {
        value = value.slice(0, maxLength);
    }
    input.value = value;
}

// Helper function for discount input validation
function validateFunctDiscountInput(value, type) {
    if (value === '' || value === '.' || value === null) {
        return '0';
    }
    
    // Remove trailing dot
    if (value.endsWith('.')) {
        value = value.slice(0, -1);
    }
    
    // Remove non-numeric except decimal
    value = value.replace(/[^0-9.]/g, '');
    
    // Ensure only one decimal point
    const parts = value.split('.');
    if (parts.length > 2) {
        value = parts[0] + '.' + parts.slice(1).join('');
    }
    
    const parsedValue = parseFloat(value) || 0;
    
    if (type === 'Percentage') {
        // Max 50% for percentage discount
        if (parsedValue > 50) {
            value = '50';
        } else if (parsedValue < 0) {
            value = '0';
        }
        
        // Limit to 2 decimal places for percentage
        if (parts.length === 2) {
            parts[1] = parts[1].slice(0, 2);
            value = parts[0] + '.' + parts[1];
        }
    } else if (type === 'Amount') {
        // Ensure non-negative amount
        if (parsedValue < 0) {
            value = '0';
        }
        
        // Limit to configured decimal places for amount
        if (parts.length === 2) {
            parts[1] = parts[1].slice(0, genSettings.DecimalPoints || 2);
            value = parts[0] + '.' + parts[1];
        }
    }
    
    return value;
}

function getTotalUnitPrice() {
    let total = 0;
    if (billManager && billManager.items && billManager.items.length > 0) {
        billManager.items.forEach(item => {
            total += (parseFloat(item.line_total) || 0);
        });
    }
    return total > 0 ? total : 1; // Return 1 if no items to avoid division by zero
}

// Helper function to get field suffix
function getFieldSuffix(fieldType) {
    switch(fieldType) {
        case 'tax': return 'Charges';
        case 'percent': return 'Percent';
        case 'withoutTax': return 'ChargeWOutTax';
        case 'withTax': return 'ChargeWithTax';
        default: return '';
    }
}

// Update all related fields when any field changes
function updateAdditionalChargeFields(chargeType, changedField, changedValue) {

    // Only process if billManager exists AND has items
    if (!billManager || !billManager.items || billManager.items.length === 0) {

        // Reset this charge to 0
        $(`#${chargeType}Percent`).val('0');
        $(`#${chargeType}ChargeWOutTax`).val('0');
        $(`#${chargeType}ChargeWithTax`).val('0');
        $(`#${chargeType}Charges`).prop('selectedIndex', 0);

        // Also reset in billManager
        if (billManager) {
            billManager.summary.additionalCharges[chargeType] = {
                netAmount: 0,
                taxPercent: 0,
                cgstPercent: 0,
                sgstPercent: 0,
                igstPercent: 0,
                cgstAmount: 0,
                sgstAmount: 0,
                igstAmount: 0,
                taxAmount: 0,
                grossAmount: 0
            };
            billManager.updateAdditionalChargesTotal();
            billManager.updateAdditionalChargesUI();
        }

        Swal.fire({icon: 'info', title: 'No Items', text: 'Please add items to bill before setting additional charges', timer: 1000});
        $(`#${chargeType}${getFieldSuffix(changedField)}`).val('0');
        return;
        
    }
    
    // Get the tax percent from dropdown
    const taxSelect = $(`#${chargeType}Charges`);
    const selectedOption = taxSelect.find('option:selected');
    const taxPercent = parseFloat(selectedOption.data('percent')) || 0;
    
    // Get current values from ALL fields
    let percent = parseFloat($(`#${chargeType}Percent`).val()) || 0;
    let withoutTax = parseFloat($(`#${chargeType}ChargeWOutTax`).val()) || 0;
    let withTax = parseFloat($(`#${chargeType}ChargeWithTax`).val()) || 0;
    
    // Get total taxable amount (sum of line_total from all items)
    const totalUnitPrice = billManager?.summary?.items?.taxableAmount || 0;
    
    // Based on which field changed, recalculate ALL THREE fields
    switch(changedField) {
        case 'tax':
            // Tax changed - recalculate withTax based on withoutTax and new tax %
            if (withoutTax > 0) {
                withTax = withoutTax * (1 + taxPercent / 100);
                // Percent stays same (based on withoutTax)
                percent = totalUnitPrice > 0 ? (withoutTax / totalUnitPrice) * 100 : 0;
            } else if (withTax > 0) {
                // If withTax has value, recalculate withoutTax
                withoutTax = withTax / (1 + taxPercent / 100);
                percent = totalUnitPrice > 0 ? (withoutTax / totalUnitPrice) * 100 : 0;
            }
            break;
            
        case 'percent':
            // Percentage changed - calculate withoutTax, then withTax
            percent = parseFloat(changedValue) || 0;
            withoutTax = (percent * totalUnitPrice) / 100;
            withTax = withoutTax * (1 + taxPercent / 100);
            break;
            
        case 'withoutTax':
            // Without tax changed - calculate percent and withTax
            withoutTax = parseFloat(changedValue) || 0;
            percent = totalUnitPrice > 0 ? (withoutTax / totalUnitPrice) * 100 : 0;
            withTax = withoutTax * (1 + taxPercent / 100);
            break;
            
        case 'withTax':
            // With tax changed - calculate withoutTax and percent
            withTax = parseFloat(changedValue) || 0;
            if (taxPercent > 0) {
                withoutTax = withTax / (1 + taxPercent / 100);
            } else {
                withoutTax = withTax;
            }
            percent = totalUnitPrice > 0 ? (withoutTax / totalUnitPrice) * 100 : 0;
            break;
    }
    
    // Update ALL THREE fields
    if (changedField !== 'percent') {
        updateFieldWithoutTrigger(`${chargeType}Percent`, smartDecimal(percent, 2));
    }
    if (changedField !== 'withoutTax') {
        updateFieldWithoutTrigger(`${chargeType}ChargeWOutTax`, smartDecimal(withoutTax, genSettings.DecimalPoints));
    }
    if (changedField !== 'withTax') {
        updateFieldWithoutTrigger(`${chargeType}ChargeWithTax`, smartDecimal(withTax, genSettings.DecimalPoints));
    }
    
    // Update bill manager
    updateBillManagerAdditionalCharge(chargeType, withoutTax, withTax, taxPercent);
    
    // Update UI visibility
    billManager.updateAdditionalChargesUI();

}

// Helper: Update field without triggering change event
function updateFieldWithoutTrigger(fieldId, value) {

    const $field = $(`#${fieldId}`);
    if (!$field.length) return;
    
    // Convert both to strings for comparison (to handle decimal precision)
    const currentValue = $field.val().trim();
    const newValue = value.toString().trim();
    
    // Always update, even if values appear equal (they might have different decimal places)
    $field.off('input change').val(newValue);
    
    // Reattach the appropriate event handler
    if ($field.hasClass('additional-charge-percent')) {
        $field.on('input', function() {
            const chargeType = $(this).data('type');
            updateAdditionalChargeFields(chargeType, 'percent', $(this).val());
        });
    } 
    else if ($field.hasClass('additional-charge-withouttax')) {
        $field.on('input', function() {
            const chargeType = $(this).data('type');
            updateAdditionalChargeFields(chargeType, 'withoutTax', $(this).val());
        });
    }
    else if ($field.hasClass('additional-charge-withtax')) {
        $field.on('input', function() {
            const chargeType = $(this).data('type');
            updateAdditionalChargeFields(chargeType, 'withTax', $(this).val());
        });
    }
    else if ($field.hasClass('additional-charge-tax')) {
        $field.on('change', function() {
            const chargeType = $(this).data('type');
            const $selected = $(this).find('option:selected');
            const taxValue = $selected.data('percent') || '0';
            updateAdditionalChargeFields(chargeType, 'tax', taxValue);
        });
    }

}

// Update BillManager with charge data
function updateBillManagerAdditionalCharge(chargeType, withoutTax, withTax, taxPercent) {

    if (!billManager) return;
    
    const netAmount = parseFloat(withoutTax) || 0;
    const grossAmount = parseFloat(withTax) || 0;
    const taxRate = parseFloat(taxPercent) || 0;
    
    // Update in bill manager
    if (billManager.setAdditionalChargeWithTax) {
        billManager.setAdditionalChargeWithTax(chargeType, netAmount, taxRate);
    }

    // Update the charges tax breakdown display
    updateChargeTaxBreakdown();
    
}

// Helper function for discount input validation on type change
function validateDiscountInputOnTypeChange(value, newType) {

    if (value === '' || value === '.' || value === null) {
        return '0';
    }
    
    // Remove non-numeric except decimal
    value = value.replace(/[^0-9.]/g, '');
    
    // Handle multiple decimal points
    const parts = value.split('.');
    if (parts.length > 2) {
        value = parts[0] + '.' + parts.slice(1).join('');
    }
    
    const parsedValue = parseFloat(value) || 0;
    
    if (newType === 'Percentage') {
        // Max 50% for percentage discount
        if (parsedValue > 50) {
            value = '50';
        } else if (parsedValue < 0) {
            value = '0';
        }
        
        // Limit to 2 decimal places for percentage
        if (parts.length === 2) {
            parts[1] = parts[1].slice(0, 2);
            value = parts[0] + '.' + parts[1];
        }
    } else if (newType === 'Amount') {
        // Ensure non-negative amount
        if (parsedValue < 0) {
            value = '0';
        }
        
        // Limit to configured decimal places for amount
        if (parts.length === 2) {
            parts[1] = parts[1].slice(0, genSettings.DecimalPoints || 2);
            value = parts[0] + '.' + parts[1];
        }
    }
    
    return value;
}

// Separate function for discount field handling
function handleDiscountFieldInput($input, $row, getId) {
    
    const discountTypeSelect = $row.find('select[name*="_discountType"]');
    const discountType = discountTypeSelect.length ? discountTypeSelect.find('option:selected').val() : 'Percentage';

    let newValue = $input.val();

    // Allow typing states
    if (newValue === '' || newValue === '.' || newValue.endsWith('.')) {
        return;
    }

    newValue = newValue.replace(/[^0-9.]/g, '');

    const parts = newValue.split('.');
    if (parts.length > 2) {
        newValue = parts[0] + '.' + parts.slice(1).join('');
    }

    if (discountType === 'Percentage') {
        const num = parseFloat(newValue);
        if (num > 50) newValue = '50';
    }

    $input.val(newValue);

    const parsedValue = parseFloat(newValue);
    if (isNaN(parsedValue)) return;

    // Handle global discount override
    const currentItem = billManager.getItemById(getId);
    if (currentItem && currentItem.discount_is_global && parsedValue > 0) {
        currentItem.overrides_global_discount = true;
        currentItem.discount_manually_changed = true;
        currentItem.discount_is_global = false;
    }

    billManager.updateItem(getId, 'discount', parsedValue);
    
}

// Function to show tax details modal
function showTaxDetails() {
    
    if (!billManager || billManager.items.length === 0) {
        Swal.fire({icon: 'info', title: 'No Items', text: 'Add items to view tax details'});
        return;
    }
    
    // Get tax summary from billManager
    const taxSummary = billManager.summary.taxSummary;
    const taxRates = billManager.summary.taxRates;
    
    // Populate tax details table
    const $taxTableBody = $('#taxDetailsTableBody');
    $taxTableBody.empty();
    
    let totalTaxable = 0;
    let totalCGST = 0;
    let totalSGST = 0;
    let totalIGST = 0;
    let totalTax = 0;
    let totalItems = 0;
    
    // Sort tax rates from highest to lowest
    const sortedRates = Object.keys(taxSummary).sort((a, b) => parseFloat(b) - parseFloat(a));
    
    sortedRates.forEach(taxRate => {
        const summary = taxSummary[taxRate];
        let row = `
            <tr>
                <td>${summary.taxPercent}%</td>
                <td>${smartDecimal(summary.taxableAmount, genSettings.DecimalPoints, true)}</td>`;
        if(customerInterState) {
            row += `<td>${summary.igstPercent > 0 ? smartDecimal(summary.igstAmount, genSettings.DecimalPoints, true) : '0.00'}</td>`;
        } else {
            row += `<td>${summary.cgstPercent > 0 ? smartDecimal(summary.cgstAmount, genSettings.DecimalPoints, true) : '0.00'}</td>
                <td>${summary.sgstPercent > 0 ? smartDecimal(summary.sgstAmount, genSettings.DecimalPoints, true) : '0.00'}</td>`;
        }
        row += `<td>${smartDecimal(summary.totalTax, genSettings.DecimalPoints, true)}</td>
                <td>${summary.itemCount}</td>
            </tr>
        `;
        $taxTableBody.append(row);
        
        // Update totals
        totalTaxable += summary.taxableAmount;
        totalCGST += summary.cgstAmount;
        totalSGST += summary.sgstAmount;
        totalIGST += summary.igstAmount;
        totalTax += summary.totalTax;
        totalItems += summary.itemCount;
    });
    
    // Update footer totals
    $('#totalTaxableAmount').text(smartDecimal(totalTaxable, genSettings.DecimalPoints, true));
    if(customerInterState) {
        $('.taxbrkupIgst').removeClass('d-none');
        $('.taxbrkupCgst,.taxbrkupSgst').addClass('d-none');
        $('#totalIGST').text(smartDecimal(totalIGST, genSettings.DecimalPoints, true));
    } else {
        $('.taxbrkupCgst,.taxbrkupSgst').removeClass('d-none');
        $('.taxbrkupIgst').addClass('d-none');
        $('#totalCGST').text(smartDecimal(totalCGST, genSettings.DecimalPoints, true));
        $('#totalSGST').text(smartDecimal(totalSGST, genSettings.DecimalPoints, true));    
    }
    $('#totalTaxAmount').text(smartDecimal(totalTax, genSettings.DecimalPoints, true));
    $('#totalItemsCount').text(totalItems);

    $('#taxDetailsModal').modal('show');
    
}

// Function to update tax tooltip dynamically
function updateTaxTooltip() {
    if (!billManager) return;
    
    const taxTotals = billManager.summary.taxTotals;
    const taxRates = billManager.summary.taxRates;
    
    let tooltipContent = '<div class="text-start small">';
    tooltipContent += `<div><strong>Tax Breakdown:</strong></div>`;
    
    if (taxRates.length > 0) {
        taxRates.forEach(rate => {
            const summary = billManager.summary.taxSummary[rate];
            if (summary) {
                tooltipContent += `
                    <div class="mt-1">
                        ${rate}%: ${smartDecimal(summary.totalTax, genSettings.DecimalPoints, true)}
                    </div>
                `;
            }
        });
    } else {
        tooltipContent += `<div class="text-muted">No tax applied</div>`;
    }
    
    tooltipContent += `<div class="mt-2"><strong>Total Tax:</strong> ${smartDecimal(taxTotals.totalTax || 0, genSettings.DecimalPoints, true)}</div>`;
    tooltipContent += '</div>';
    
}

function updateItemTaxBreakdown() {

    const getTot = billManager.getItemsTotals();

    if(customerInterState) {
        $('.taxBreakUpItemsCgst,.taxBreakUpItemsSgst').addClass('d-none');
        $('.taxBreakUpItemsIgst').removeClass('d-none');
    } else {
        $('.taxBreakUpItemsCgst,.taxBreakUpItemsSgst').removeClass('d-none');
        $('.taxBreakUpItemsIgst').addClass('d-none');
    }
    
    // Update UI
    $('.taxBreakUpItemsCnt').text(parseInt(getTot.totalItems));
    $('.taxBreakUpItemsCgstVal').text(parseFloat(getTot.totalCgst).toFixed(2));
    $('.taxBreakUpItemsSgstVal').text(parseFloat(getTot.totalSgst).toFixed(2));
    $('.taxBreakUpItemsIgstVal').text(parseFloat(getTot.totalIgst).toFixed(2));
    $('.taxBreakUpItemsTotAmt').text(parseFloat(getTot.totalTax).toFixed(2));    

}

function updateChargeTaxBreakdown() {

    const charges = billManager.summary.additionalCharges;
    $('#chargeBreakUpTaxDetails').removeClass('d-none');
    if(charges.total.taxAmount == 0) {
        $('#chargeBreakUpTaxDetails').addClass('d-none');
        return false;
    }

    const $tbody = $('#chargeBreakUpTaxDetails tbody');
    
    if (!$tbody.length) return;
    
    $tbody.empty();
    
    Object.entries(charges).forEach(([key, charge]) => {
        if (key === 'total') return;
        if (charge && charge.grossAmount > 0) {
            const chargeName = key.charAt(0).toUpperCase() + key.slice(1);
            const row = `
                <tr>
                    <td class="py-1">${chargeName}</td>
                    <td class="py-1 taxBreakUpChargesCgst ${customerInterState ? 'd-none' : ''}">${genSettings.CurrenySymbol} <span class="taxBreakUpChargesCgstVal">${smartDecimal(charge.cgstAmount, genSettings.DecimalPoints)}</span></td>
                    <td class="py-1 taxBreakUpChargesSgst ${customerInterState ? 'd-none' : ''}">${genSettings.CurrenySymbol} <span class="taxBreakUpChargesSgstVal">${smartDecimal(charge.sgstAmount, genSettings.DecimalPoints)}</span></td>
                    <td class="py-1 taxBreakUpChargesIgst ${!customerInterState ? 'd-none' : ''}">${genSettings.CurrenySymbol} <span class="taxBreakUpChargesIgstVal">${smartDecimal(charge.igstAmount, genSettings.DecimalPoints)}</span></td>
                    <td class="py-1 text-end fw-semibold">${genSettings.CurrenySymbol} <span class="taxBreakUpChargesTotAmt">${smartDecimal(charge.taxAmount, genSettings.DecimalPoints)}</span></td>
                </tr>
            `;
            $tbody.append(row);
        }
    });
    
    // Update total charges tax
    $('#chargeTaxTotal').text(smartDecimal(charges.total.taxAmount, genSettings.DecimalPoints, true));

}