class BillManager {
    
    constructor() {
        this.items = [];
        this.map = {};
        this.globalDiscountPercent = 0;
        this.roundOffEnabled = true;
        this.summary = {
            taxableAmount: 0,
            additionalCharge: 0,
            baseDiscount: 0,
            extraDiscount: {
                type: 'percentage',
                value: 0,
                calculatedAmount: 0,
                mode: 'percentage'
            },
            subtotalBeforeDiscount: 0,
            subtotalAfterDiscount: 0,
            taxAmount: 0,
            roundOff: 0,
            totalBeforeRoundOff: 0,
            totalAmount: 0,
            itemCount: 0,
            totalQuantity: 0,
        };
    }

    // Set global discount percentage
    setGlobalDiscountPercent(percent) {
        const oldPercent = this.globalDiscountPercent;
        this.globalDiscountPercent = parseFloat(percent) || 0;
        
        // Update UI field
        $('#globalDiscount').val(this.globalDiscountPercent);
        
        // KEY RULE: When global discount changes, RESET ALL IMMUNITY
        if (this.globalDiscountPercent !== oldPercent) {
            this.resetAllImmunityAndApplyGlobalDiscount();
        }
    }

    // When global discount changes, reset ALL immunity and apply to ALL rows
    resetAllImmunityAndApplyGlobalDiscount() {
        this.items.forEach(item => {
            const updatedItem = {...item};
            
            // RESET IMMUNITY for this item
            updatedItem.immune_to_global = false;
            updatedItem.discount_manually_changed = false;
            updatedItem.overrides_global_discount = false;
            
            if (this.globalDiscountPercent > 0) {
                updatedItem.discount_is_global = true;
                updatedItem._lastChanged = 'globalDiscount';
                
                // Apply global discount based on discount type
                if (updatedItem.discount_type === 'Percentage') {
                    updatedItem.discount = this.globalDiscountPercent;
                } else if (updatedItem.discount_type === 'Amount') {
                    // Convert global percentage to amount
                    const baseTotal = (updatedItem.orgquantity * updatedItem.orgunitprice) + 
                                    ((updatedItem.orgselngprice - updatedItem.orgunitprice) * updatedItem.orgquantity);
                    const discApplyFor = $('#billTable').find('#discApplyFor option:selected').val();
                    let discountBase = 0;
                    
                    switch (discApplyFor) {
                        case 'TotalAmount':
                            discountBase = baseTotal;
                            break;
                        case 'PriceWithTax':
                            discountBase = updatedItem.orgquantity * updatedItem.orgselngprice;
                            break;
                        default:
                            discountBase = updatedItem.orgquantity * updatedItem.orgunitprice;
                    }
                    
                    updatedItem.discount = (discountBase * this.globalDiscountPercent) / 100;
                    updatedItem.discount = parseFloat(updatedItem.discount.toFixed(genSettings.DecimalPoints));
                }
            } else {
                // Global discount removed
                updatedItem.discount_is_global = false;
                updatedItem.discount = 0;
                updatedItem._lastChanged = 'globalDiscount';
            }
            
            const recalculatedItem = this.calculateRowItem(updatedItem);
            
            // Update storage
            const itemId = parseInt(recalculatedItem.id, 10);
            this.map[itemId] = recalculatedItem;
            
            const idx = this.items.findIndex(i => parseInt(i.id, 10) === itemId);
            if (idx >= 0) this.items[idx] = recalculatedItem;
            
            updateTableRow(recalculatedItem);
        });
        this.updateSummary();
    }
    
    updateSummaryFromItems() {
        // Calculate total from all items
        let itemsTotal = 0;
        let totalQuantity = 0;
        
        for (let item of this.items) {
            // Use line_total (which is total before tax) OR net_total (which includes tax)
            // Based on your needs, let's use line_total for taxable amount
            const itemValue = parseFloat(item.line_total) || 0;
            const itemQty = parseFloat(item.quantity) || 0;
            
            itemsTotal += itemValue;
            totalQuantity += itemQty;
        }
        
        // Update summary with item data
        this.summary.taxableAmount = itemsTotal;
        this.summary.itemCount = this.items.length;
        this.summary.totalQuantity = totalQuantity;
        
        return this.summary;
    }

    updateBaseDiscount() {
        // Calculate base discount from global discount percentage
        const taxableAmount = this.summary.taxableAmount || 0;
        this.summary.baseDiscount = taxableAmount * (this.globalDiscountPercent / 100);        
        return this.summary;
    }

    // Enhanced updateExtraDiscount method
    updateExtraDiscount() {
        const extraDiscount = this.summary.extraDiscount;
        const taxableAmount = this.summary.taxableAmount || 0;
        const additionalCharge = this.summary.additionalCharge || 0;
        
        const baseAmount = taxableAmount + additionalCharge;
        
        // Validate and calculate
        if (extraDiscount.type === 'percentage') {
            // Ensure percentage is valid
            const percentage = Math.min(Math.max(extraDiscount.value || 0, 0), 100);
            extraDiscount.calculatedAmount = baseAmount * (percentage / 100);
        } else {
            // For amount, ensure it doesn't exceed base amount
            const maxAmount = baseAmount;
            extraDiscount.calculatedAmount = Math.min(extraDiscount.value || 0, maxAmount);
        }
        
        // Update validation message if needed
        this.validateExtraDiscount();
        
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

    addItem(productData, qty = 1) {
        const id = parseInt(productData.id, 10);
        if (this.map[id]) {
            Swal.fire({icon: "error", title: "Oops...", text: "Item already moved to cart."});
            return false;
        } else {
            const item = {...productData, quantity: qty};

            // Initialize with current discount scope
            item.discount_scope = $('#discApplyFor').find('option:selected').val() || 'TotalAmount';
            
            // New items are NOT immune by default
            item.immune_to_global = false;
            item.discount_manually_changed = false;
            item.overrides_global_discount = false;
            item.discount_is_global = false;
            
            // Apply global discount if exists
            if (this.globalDiscountPercent > 0) {
                item.discount = this.globalDiscountPercent;
                item.discount_type = 'Percentage';
                item.discount_is_global = true;
            }
            
            const newItem = this.calculateRowItem(item);
            this.items.push(newItem);
            this.map[id] = newItem;
            this.updateSummary();
        }
    }

    updateItem(id, field, value) {
        id = parseInt(id, 10);
        const oldItem = this.map[id];
        if (!oldItem) return;

        if (value === '' || value === null || isNaN(value)) {
            value = 0;
        }

        const newItem = { ...oldItem, [field]: value };
        newItem._lastChanged = field;
        
        // Track original discount before type change
        if (field === 'discount_type') {
            newItem.previous_discount_type = oldItem.discount_type || 'Percentage';
            newItem.original_discount_before_type_change = oldItem.discount || 0;
        }
        
        // If user manually changes discount value, store as original
        if (field === 'discount' && value !== oldItem.discount) {
            newItem.original_discount_before_type_change = value;
            newItem.immune_to_global = true;
            newItem.discount_manually_changed = true;
            newItem.discount_is_global = false;
            newItem.overrides_global_discount = true;
        }
        
        const aftCalcItem = this.calculateRowItem(newItem);
        
        // replace object
        this.map[id] = aftCalcItem;

        const idx = this.items.findIndex(i => parseInt(i.id, 10) === id);
        if (idx >= 0) this.items[idx] = aftCalcItem;
        updateTableRow(aftCalcItem);
        this.updateSummary();
    }

    removeItem(id) {
        id = parseInt(id, 10);
        
        // Remove from items array
        const initialLength = this.items.length;
        this.items = this.items.filter(item => parseInt(item.id, 10) !== id);
        
        // Remove from map
        delete this.map[id];
        
        // Update summary if item was actually removed
        if (this.items.length !== initialLength) {
            if(this.items.length === 0) {
                $('#billTableBody').html(`<tr class="text-center text-muted">
                                <tr class="text-center text-muted">
                                    <td colspan="6">
                                        <div class="py-4">
                                            <i class="bx bx-cart text-muted" style="font-size: 2rem;"></i>
                                            <p class="mt-2 mb-0">No items added yet</p>
                                            <small class="text-muted">Click "Add Product" or search above to get started</small>
                                        </div>
                                    </td>
                                </tr>
                            </tr>`);
            }
            this.updateSummary();
            return true;
        }
        return false;
    }

    calculateRowItem(item) {
        const qty = parseFloat(item.quantity) || 0;
        const taxPercent = parseFloat(item.taxPercent || 0);
        
        // Store what field was changed to know if we should update originals
        const lastChanged = item._lastChanged;
        
        // --- PHASE 1: Handle ORIGINAL value updates ---
        if (lastChanged === 'quantity' || lastChanged === 'unitPrice' || lastChanged === 'sellingPrice' || 
            lastChanged === 'discount' || lastChanged === 'discount_type' || lastChanged === 'globalDiscount') {
            
            // When any field changes, update ORIGINAL values if needed
            
            if (lastChanged === 'unitPrice') {
                // New unit price entered
                const newUnitPrice = parseFloat(item.unitPrice) || 0;
                item.orgunitprice = newUnitPrice;
                item.orgselngprice = newUnitPrice * (1 + taxPercent / 100);
                item.orgquantity = qty;
            }
            else if (lastChanged === 'sellingPrice') {
                // New selling price entered  
                const newSellPrice = parseFloat(item.sellingPrice) || 0;
                item.orgselngprice = newSellPrice;
                item.orgunitprice = newSellPrice / (1 + taxPercent / 100);
                item.orgquantity = qty;
            }
            else if (lastChanged === 'quantity') {
                // Quantity changed - preserve unit/sell prices
                item.orgquantity = qty;
                if (item.orgunitprice == null) {
                    item.orgunitprice = parseFloat(item.unitPrice) || 0;
                    item.orgselngprice = item.orgunitprice * (1 + taxPercent / 100);
                }
            }
            else if (lastChanged === 'discount' || lastChanged === 'discount_type' || lastChanged === 'globalDiscount') {
                // Discount related change - preserve original prices
                if (item.orgunitprice == null) {
                    item.orgunitprice = parseFloat(item.unitPrice) || 0;
                    item.orgselngprice = item.orgunitprice * (1 + taxPercent / 100);
                }
                if (item.orgquantity == null) {
                    item.orgquantity = qty;
                }
            }
        }
        
        // Initialize original values if not set
        if (item.orgunitprice == null || isNaN(item.orgunitprice)) {
            item.orgunitprice = parseFloat(item.unitPrice) || 0;
            item.orgselngprice = item.orgunitprice * (1 + taxPercent / 100);
            item.orgquantity = qty;
        }
        if (item.orgselngprice == null || isNaN(item.orgselngprice)) {
            item.orgselngprice = item.orgunitprice * (1 + taxPercent / 100);
        }
        if (item.orgquantity == null || isNaN(item.orgquantity)) {
            item.orgquantity = qty;
        }
        
        // --- PHASE 2: Calculate BASE totals (before discount) ---
        const baseUnitPrice = item.orgunitprice;
        const baseSellPrice = item.orgselngprice;
        const baseQty = item.orgquantity;
        
        const lineBaseTotal = baseQty * baseUnitPrice;
        const baseTaxAmount = (baseSellPrice - baseUnitPrice) * baseQty;
        const baseTotalAmount = lineBaseTotal + baseTaxAmount;
        
        // --- PHASE 3: Smart Discount Logic ---
        const previousScope = item.previous_discount_scope || 'TotalAmount';
        const discApplyFor = $('#billTable').find('#discApplyFor option:selected').val();
        let discountType = item.discount_type || 'Percentage';
        let discountValue = parseFloat(item.discount) || 0;
        
        // Track what's happening
        const isDiscountTypeChange = (lastChanged === 'discount_type');
        const isDiscountValueManualChange = (lastChanged === 'discount');
        const isGlobalDiscountApplied = (this.globalDiscountPercent > 0);
        
        // Initialize flags if not exists
        if (item.immune_to_global === undefined) item.immune_to_global = false;
        if (item.discount_manually_changed === undefined) item.discount_manually_changed = false;
        if (item.discount_is_global === undefined) item.discount_is_global = false;
        if (item.overrides_global_discount === undefined) item.overrides_global_discount = false;
        if (item.previous_discount_type === undefined) item.previous_discount_type = discountType;
        if (item.original_discount_before_type_change === undefined) item.original_discount_before_type_change = discountValue;
        
        // Helper functions
        const getDiscountBase = () => {
            // Get current scope from dropdown
            switch (discApplyFor) {
                case 'TotalAmount':
                    return baseTotalAmount;
                case 'PriceWithTax':
                    return baseQty * baseSellPrice;
                case 'UnitPrice':
                case 'NetAmount':
                    return lineBaseTotal;
                default:
                    return lineBaseTotal;
            }
        };
        
        const calculateAmountFromPercent = (percentValue) => {
            const discountBase = getDiscountBase();
            return (discountBase * percentValue) / 100;
        };
        
        const calculatePercentFromAmount = (amountValue) => {
            const discountBase = getDiscountBase();
            if (discountBase === 0) return 0;
            return (amountValue / discountBase) * 100;
        };
        
        // Store the discount value before any type change logic
        const discountValueBeforeProcessing = discountValue;

        // Handle discount scope change
        if (lastChanged === 'discount_scope_change' && previousScope !== discApplyFor) {
            // When scope changes, we need to recalculate discount value if it's a percentage
            if (discountType === 'Percentage' && discountValue > 0) {
                // For percentage discount, the value stays the same
                // The calculation will automatically use the new base
            } else if (discountType === 'Amount' && discountValue > 0) {
                // For amount discount, we might need to adjust if the base changes significantly
                // For now, keep the same amount
            }
        }
        
        // CASE 1: Discount Type Change (Percentage ↔ Amount)
        if (isDiscountTypeChange) {
            const previousType = item.previous_discount_type;
            const originalDiscount = item.original_discount_before_type_change;
            
            if (previousType === 'Percentage' && discountType === 'Amount') {
                // % → Amount: Convert percentage to amount
                if (item.discount_is_global && isGlobalDiscountApplied) {
                    // Global discount: use global percentage
                    discountValue = calculateAmountFromPercent(this.globalDiscountPercent);
                } else if (originalDiscount > 0) {
                    // Individual discount: convert stored percentage to amount
                    discountValue = calculateAmountFromPercent(originalDiscount);
                }
                discountValue = parseFloat(discountValue.toFixed(genSettings.DecimalPoints));
                
                // Mark as immune (user changed type)
                item.immune_to_global = true;
                item.discount_manually_changed = true;
                item.discount_is_global = false;
                item.overrides_global_discount = true;
                
            }
            else if (previousType === 'Amount' && discountType === 'Percentage') {
                // Amount → %: Convert amount to percentage if possible
                if (originalDiscount > 0) {
                    const calculatedPercent = calculatePercentFromAmount(originalDiscount);
                    discountValue = parseFloat(calculatedPercent.toFixed(2));
                    
                    // Check if this matches global discount
                    if (isGlobalDiscountApplied && Math.abs(discountValue - this.globalDiscountPercent) < 0.01) {
                        item.discount_is_global = true;
                        discountValue = this.globalDiscountPercent;
                        item.immune_to_global = false;
                        item.overrides_global_discount = false;
                    } else {
                        item.immune_to_global = true;
                        item.discount_is_global = false;
                        item.overrides_global_discount = true;
                    }
                }
                item.discount_manually_changed = true;
            }
            
            // Update tracking variables
            item.previous_discount_type = discountType;
            item.original_discount_before_type_change = discountValue;
        }
        // CASE 2: User manually typed in discount field
        else if (isDiscountValueManualChange) {
            // User manually changed discount value - store as original
            item.original_discount_before_type_change = discountValue;
            item.immune_to_global = true;
            item.discount_manually_changed = true;
            item.discount_is_global = false;
            item.overrides_global_discount = true;
        }
        // CASE 3: Global discount change (resets everything)
        else if (lastChanged === 'globalDiscount') {
            // Reset immunity for all items
            item.immune_to_global = false;
            item.discount_manually_changed = false;
            item.overrides_global_discount = false;
            item.original_discount_before_type_change = 0;
            
            if (isGlobalDiscountApplied) {
                item.discount_is_global = true;
                
                if (discountType === 'Percentage') {
                    discountValue = this.globalDiscountPercent;
                } else if (discountType === 'Amount') {
                    discountValue = calculateAmountFromPercent(this.globalDiscountPercent);
                    discountValue = parseFloat(discountValue.toFixed(genSettings.DecimalPoints));
                }
            } else {
                item.discount_is_global = false;
                discountValue = 0;
            }
        }
        // CASE 4: Apply global discount if NOT immune
        else if (!item.immune_to_global && !item.overrides_global_discount && isGlobalDiscountApplied) {
            item.discount_is_global = true;
            item.discount_manually_changed = false;
            
            if (discountType === 'Percentage') {
                discountValue = this.globalDiscountPercent;
            } else if (discountType === 'Amount') {
                discountValue = calculateAmountFromPercent(this.globalDiscountPercent);
                discountValue = parseFloat(discountValue.toFixed(genSettings.DecimalPoints));
            }
        }
        // CASE 5: Item is immune - keep current value
        else if (item.immune_to_global || item.overrides_global_discount) {
            item.discount_is_global = false;
            // Keep current discountValue
        }
        // CASE 6: Default case
        else {
            item.discount_is_global = false;
            item.discount_manually_changed = false;
            item.overrides_global_discount = false;
            // Keep current discountValue
        }
        
        // Ensure discount value is valid
        if (isNaN(discountValue) || discountValue < 0) {
            discountValue = 0;
        }
        
        // --- PHASE 4: Calculate discount amount for totals ---
        const discountBase = getDiscountBase();
        let discountAmount = 0;
        
        if (discountValue > 0) {
            if (discountType === 'Percentage') {
                discountAmount = discountBase * (discountValue / 100);
            } else if (discountType === 'Amount') {
                discountAmount = Math.min(discountValue, discountBase);
            }
        }
        
        // --- PHASE 5: Calculate EFFECTIVE prices after discount ---
        let effUnitPrice, effSellPrice, effTaxAmount, effTotalAmount;
        
        if (discountAmount === 0) {
            // No discount
            effUnitPrice = baseUnitPrice;
            effSellPrice = baseSellPrice;
            effTaxAmount = baseTaxAmount;
            effTotalAmount = baseTotalAmount;
        } else {
            // Apply discount based on the discount base type
            switch (discApplyFor) {
                case 'TotalAmount':
                    const totalAfterDiscount = baseTotalAmount - discountAmount;
                    const pricePerUnit = baseQty > 0 ? totalAfterDiscount / baseQty : 0;
                    
                    effSellPrice = pricePerUnit;
                    effUnitPrice = effSellPrice / (1 + taxPercent / 100);
                    effTaxAmount = (effSellPrice - effUnitPrice) * baseQty;
                    effTotalAmount = totalAfterDiscount;
                    break;
                    
                case 'PriceWithTax':
                    const totalSellAfter = (baseQty * baseSellPrice) - discountAmount;
                    const sellPricePerUnit = baseQty > 0 ? totalSellAfter / baseQty : 0;
                    
                    effSellPrice = sellPricePerUnit;
                    effUnitPrice = effSellPrice / (1 + taxPercent / 100);
                    effTaxAmount = (effSellPrice - effUnitPrice) * baseQty;
                    effTotalAmount = (effUnitPrice * baseQty) + effTaxAmount;
                    break;
                    
                case 'UnitPrice':
                    const unitTotalAfter = lineBaseTotal - discountAmount;
                    effUnitPrice = baseQty > 0 ? unitTotalAfter / baseQty : baseUnitPrice;
                    effSellPrice = effUnitPrice * (1 + taxPercent / 100);
                    effTaxAmount = (effSellPrice - effUnitPrice) * baseQty;
                    effTotalAmount = (effUnitPrice * baseQty) + effTaxAmount;
                    break;
                    
                case 'NetAmount':
                    const netAfter = lineBaseTotal - discountAmount;
                    effUnitPrice = baseQty > 0 ? netAfter / baseQty : baseUnitPrice;
                    effSellPrice = effUnitPrice * (1 + taxPercent / 100);
                    effTaxAmount = (effSellPrice - effUnitPrice) * baseQty;
                    effTotalAmount = (effUnitPrice * baseQty) + effTaxAmount;
                    break;
                    
                default:
                    effUnitPrice = baseUnitPrice;
                    effSellPrice = baseSellPrice;
                    effTaxAmount = baseTaxAmount;
                    effTotalAmount = baseTotalAmount - discountAmount;
            }
        }
        
        // --- PHASE 6: Update working values for display ---
        item.unitPrice = parseFloat(effUnitPrice);
        item.sellingPrice = parseFloat(effSellPrice);
        item.quantity = parseFloat(baseQty);
        
        // Store discount information
        item.discount = discountValue;
        item.discount_type = discountType;
        item.discount_amount = parseFloat(discountAmount);
        item.discount_scope = discApplyFor;
        
        // These are calculated totals
        item.line_total = parseFloat(effUnitPrice * baseQty);
        item.taxAmount = parseFloat(effTaxAmount);
        item.net_total = parseFloat(effTotalAmount);
        
        return item;
    }

    getTotals() {
        let totalQty = 0, totalAmount = 0, totalTax = 0, netTotal = 0, totalDisc = 0;
        
        this.items.forEach((item, index) => {            
            totalQty += parseFloat(item.quantity) || 0;
            totalAmount += parseFloat(item.line_total) || 0;
            totalTax += parseFloat(item.taxAmount) || 0;
            netTotal += parseFloat(item.net_total) || 0;
            totalDisc += parseFloat(item.discount_amount) || 0;
        });
        
        return {
            totalItems: this.items.length,
            totalQty,
            totalAmount,  // This is line_total sum (before tax)
            totalTax,     // This is taxAmount sum from items
            netTotal,     // This is net_total sum from items
            totalDisc     // This is discount_amount sum from items
        };
    }

    updateSummary() {
        const getTotalVal = this.getTotals();
        
        // First, update the summary object from items
        this.updateSummaryFromItems();
        
        // Recalculate the full summary
        this.recalculateSummary();
        
        // Calculate TOTAL discount = Base discount + Extra discount
        const baseDiscount = this.summary.baseDiscount || 0;
        const extraDiscount = this.summary.extraDiscount.calculatedAmount || 0;
        const totalDiscount = baseDiscount + extraDiscount;
        
        // Update UI fields
        $('.sumItemCount').text(smartDecimal(getTotalVal.totalItems));
        $('.sumTotalQty').text(smartDecimal(getTotalVal.totalQty));
        $('.bill_taxable_amt').text(smartDecimal(getTotalVal.totalAmount, genSettings.DecimalPoints, true));
        $('.bill_tot_tax_amt').text(smartDecimal(getTotalVal.totalTax, genSettings.DecimalPoints, true));

        $('.sumNetTotal').text(smartDecimal(this.summary.totalBeforeRoundOff, genSettings.DecimalPoints, true));
        $('.bill_rndoff_amt').text(smartDecimal((this.roundOffEnabled ? this.summary.roundOff : 0), genSettings.DecimalPoints, true));
        
        // Update TOTAL amount
        const finalTotal = (this.roundOffEnabled ? this.summary.totalAmount : this.summary.totalBeforeRoundOff) || getTotalVal.netTotal;
        $('.bill_tot_amt').text(smartDecimal(finalTotal, genSettings.DecimalPoints, true));
        
        // Show TOTAL discount (base + extra)
        $('.bill_tot_disc_amt').text(smartDecimal(totalDiscount, genSettings.DecimalPoints, true));
        
        return this.summary;
    }

    clearAllItems() {
        this.items = [];
        this.map = {};
        this.updateSummary();
    }

    setAdditionalCharge(amount) {
        this.summary.additionalCharge = parseFloat(amount) || 0;
        return this.recalculateSummary();
    }

    setExtraDiscountValue(value) {
        // Convert to number, default to 0 if invalid
        let parsedValue;
        
        if (value === '' || value === null || value === undefined || value === '.') {
            parsedValue = 0;
        } else {
            parsedValue = parseFloat(value);
            if (isNaN(parsedValue) || !isFinite(parsedValue)) {
                parsedValue = 0;
            }
        }
        
        // Validate based on current type
        if (this.summary.extraDiscount.type === 'percentage') {
            // For percentage, ensure it's between 0-50
            parsedValue = Math.max(0, Math.min(parsedValue, 50));
        } else {
            // For amount, ensure it's non-negative
            parsedValue = Math.max(parsedValue, 0);
        }
        
        this.summary.extraDiscount.value = parsedValue;
        
        return this;
    }

    setExtraDiscountType(type) {
        const oldType = this.summary.extraDiscount.type;
        
        if (oldType !== type) {
            this.summary.extraDiscount.type = type;
            this.summary.extraDiscount.mode = type;
        }
        
        return this;
    }

    calculateTax(taxRate = 18) {
        const subtotalAfterDiscount = this.summary.subtotalAfterDiscount || 0;
        
        // Calculate tax on subtotal after discount
        this.summary.taxAmount = subtotalAfterDiscount * (taxRate / 100);
        
        return this.summary;
    }

    // ALWAYS calculate round off value
    calculateRoundOff() {
        // Calculate total before rounding
        const subtotalAfterDiscount = this.summary.subtotalAfterDiscount || 0;
        const taxAmount = this.summary.taxAmount || 0;
        const totalBeforeRounding = subtotalAfterDiscount + taxAmount;
        
        // Store the total before round off
        this.summary.totalBeforeRoundOff = totalBeforeRounding;
        
        // ALWAYS calculate round off (nearest 0.5)
        const roundedTotal = Math.round(totalBeforeRounding * 2) / 2;
        this.summary.roundOff = parseFloat((roundedTotal - totalBeforeRounding).toFixed(genSettings.DecimalPoints));
        
        return this.summary;
    }

    recalculateSummary(taxRate = 18) {
        
        // Update in sequence
        this.updateSummaryFromItems();
        this.updateBaseDiscount();
        this.updateExtraDiscount();
        this.updateSubtotals();
        this.calculateTax(taxRate);
        this.calculateRoundOff();  // This now stores totalBeforeRoundOff
        this.calculateTotalAmount();
        
        return this.summary;
    }

    // Apply round off based on toggle state
    calculateTotalAmount() {
        const totalBeforeRoundOff = this.summary.totalBeforeRoundOff || 0;
        const roundOff = this.summary.roundOff || 0;
        
        // Apply round off only if enabled
        if (this.roundOffEnabled) {
            this.summary.totalAmount = totalBeforeRoundOff + roundOff;
        } else {
            this.summary.totalAmount = totalBeforeRoundOff;
        }
        
        return this.summary;
    }

    toggleRoundOff(enabled = null) {
        if (enabled !== null) {
            this.roundOffEnabled = enabled;
        } else {
            this.roundOffEnabled = !this.roundOffEnabled;
        }
        
        // Recalculate total with new setting
        this.calculateTotalAmount();
        
        // Update UI toggle
        const toggleElement = $('#roundOffToggle');
        if (toggleElement.length) {
            toggleElement.prop('checked', this.roundOffEnabled);
        }
        
        return this.roundOffEnabled;
    }

    getFormattedSummary() {
        return {
            taxableAmount: smartDecimal((this.summary.taxableAmount || 0), genSettings.DecimalPoints, true),
            additionalCharge: smartDecimal((this.summary.additionalCharge || 0), genSettings.DecimalPoints, true),
            baseDiscount: (this.summary.baseDiscount || 0).toFixed(genSettings.DecimalPoints),
            extraDiscount: {
                value: this.summary.extraDiscount.value || 0,
                type: this.summary.extraDiscount.type,
                amount: (this.summary.extraDiscount.calculatedAmount || 0).toFixed(genSettings.DecimalPoints)
            },
            subtotalBeforeDiscount: smartDecimal((this.summary.subtotalBeforeDiscount || 0), genSettings.DecimalPoints, true),
            subtotalAfterDiscount: smartDecimal((this.summary.subtotalAfterDiscount || 0), genSettings.DecimalPoints, true),
            taxAmount: smartDecimal((this.summary.taxAmount || 0), genSettings.DecimalPoints, true),
            roundOff: smartDecimal((this.summary.roundOff || 0), genSettings.DecimalPoints, true),
            totalBeforeRoundOff: smartDecimal((this.summary.totalBeforeRoundOff || 0), genSettings.DecimalPoints, true),
            totalAmount: smartDecimal((this.summary.totalAmount || 0), genSettings.DecimalPoints, true),
            itemCount: this.summary.itemCount,
            totalQuantity: this.summary.totalQuantity
        };
    }

    convertExtraDiscount(newType) {
        const currentType = this.summary.extraDiscount.type;
        const currentValue = this.summary.extraDiscount.value || 0;
        
        if (currentType === newType || currentValue === 0) {
            console.log("No conversion needed or value is 0");
            return this;
        }
        
        const taxableAmount = this.summary.taxableAmount || 0;
        const additionalCharge = this.summary.additionalCharge || 0;
        const baseAmount = taxableAmount + additionalCharge;
        
        if (currentType === 'percentage' && newType === 'amount') {
            // Convert percentage to amount
            const calculatedAmount = baseAmount * (currentValue / 100);
            this.summary.extraDiscount.value = calculatedAmount;
        } 
        else if (currentType === 'amount' && newType === 'percentage') {
            // Convert amount to percentage
            if (baseAmount > 0) {
                const calculatedPercentage = (currentValue / baseAmount) * 100;
                this.summary.extraDiscount.value = calculatedPercentage;
            } else {
                this.summary.extraDiscount.value = 0;
            }
        }
        
        // Update the type
        this.summary.extraDiscount.type = newType;
        this.summary.extraDiscount.mode = newType;
        
        // Recalculate the calculated amount
        this.updateExtraDiscount();
        
        return this;
    }

    getTotalBeforeRoundOff() {
        return this.summary.totalBeforeRoundOff || 0;
    }

    getFormattedTotalBeforeRoundOff() {
        const amount = this.summary.totalBeforeRoundOff || 0;
        return `${genSettings.CurrenySymbol} ${amount.toFixed(genSettings.DecimalPoints)}`;
    }

    resetSummary() {
        this.summary = {
            taxableAmount: 0,
            additionalCharge: 0,
            baseDiscount: 0,
            extraDiscount: {
                type: 'percentage',
                value: 0,
                calculatedAmount: 0,
                mode: 'percentage'
            },
            subtotalBeforeDiscount: 0,
            subtotalAfterDiscount: 0,
            taxAmount: 0,
            roundOff: 0,
            totalBeforeRoundOff: 0,
            totalAmount: 0,
            itemCount: 0,
            totalQuantity: 0,
        };
        
        return this.summary;
    }

}

const billManager = new BillManager();

$(document).ready(function () {
    'use strict'

    loadSelect2Field('#prodCategory', 'Select Category');
    searchProductInfo();

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
        
        $('#searchProductInfo').focus().select2('open');
        
    });

    $(document).on('input', '.updateAllBillAmounts', function(e) {
        e.preventDefault();

        let getId = $(this).closest('tr').data('id');
        const fieldName = $(this).attr('name');
        let newValue = $(this).val().trim();
        
        // ALLOW DOT TYPING: If value ends with dot, don't process yet
        if (newValue.endsWith('.')) {
            return; // Let user finish typing
        }
        
        // Check if this is a discount field
        const isDiscountField = fieldName.includes('_discount') && !fieldName.includes('Type');
        const isDiscountTypeField = fieldName.includes('_discountType');
        
        if (isDiscountField) {
            // Get the discount type for this row
            const discountTypeSelect = $(this).closest('tr').find('select[name*="_discountType"]');
            const discountType = discountTypeSelect.length ? discountTypeSelect.val() : 'Percentage';
            
            // Validate based on discount type
            if (newValue !== '' && newValue !== '.') {
                const parsedValue = parseFloat(newValue) || 0;
                
                if (discountType === 'Percentage') {
                    // Validate percentage (max 50%)
                    if (parsedValue > 50) {
                        $(this).val('50');
                        newValue = '50';
                    } else if (parsedValue < 0) {
                        $(this).val('0');
                        newValue = '0';
                    }
                    
                    // Ensure max 2 decimal places for percentage
                    if (newValue.includes('.')) {
                        const parts = newValue.split('.');
                        if (parts[1].length > 2) {
                            newValue = parts[0] + '.' + parts[1].substring(0, 2);
                            $(this).val(newValue);
                        }
                    }
                } else if (discountType === 'Amount') {
                    // Validate amount (max length already handled by maxlength attribute)
                    // Additional validation if needed
                    if (parsedValue < 0) {
                        $(this).val('0');
                        newValue = '0';
                    }
                }
            }
        }
        
        if (newValue === '' || newValue === '.') {
            newValue = 0;
            $(this).val(0);
        }

        let fieldMap = {
            [`bm_${getId}_qty`]: 'quantity',
            [`bm_${getId}_unitPrice`]: 'unitPrice',
            [`bm_${getId}_sellingPrice`]: 'sellingPrice',
            [`bm_${getId}_discount`]: 'discount',
            [`bm_${getId}_discountType`]: 'discount_type'
        };
        
        const bmField = fieldMap[fieldName];
        if (bmField) {
            let parsedValue = newValue;
            if (bmField !== 'discount_type') {
                parsedValue = parseFloat(newValue) || 0;
            }
            
            // Special handling for discount field
            if (bmField === 'discount' && parsedValue > 0) {
                const currentItem = billManager.getItemById(getId);
                if (currentItem && currentItem.discount_is_global) {
                    // User is manually overriding global discount
                    currentItem.overrides_global_discount = true;
                    currentItem.discount_manually_changed = true;
                    currentItem.discount_is_global = false;
                }
            }
            
            billManager.updateItem(getId, bmField, parsedValue);
        }
    });

    // Handle discount type change
    $(document).on('change', '.discTypeActionBillAmounts', function(e) {
        e.preventDefault();
        
        let getId = $(this).closest('tr').data('id');
        const newType = $(this).val(); // "Percentage" or "Amount"
        
        // Get the discount input field
        const discountInput = $(this).closest('tr').find('input[name*="_discount"]');
        let currentInputValue = discountInput.val().trim();
        
        // Get current item
        const currentItem = billManager.getItemById(getId);
        
        if (currentItem) {
            const currentDiscountType = currentItem.discount_type || 'Percentage';
            
            // Validate current input value based on new type
            if (currentInputValue && currentInputValue !== '.' && currentInputValue !== '') {
                // Remove trailing dot if present
                if (currentInputValue.endsWith('.')) {
                    currentInputValue = currentInputValue.slice(0, -1);
                }
                
                const parsedValue = parseFloat(currentInputValue) || 0;
                
                if (newType === 'Percentage') {
                    // Validate percentage (max 50%)
                    if (parsedValue > 50) {
                        discountInput.val('50');
                        currentInputValue = '50';
                    } else if (parsedValue < 0) {
                        discountInput.val('0');
                        currentInputValue = '0';
                    }
                    
                    // Format to max 2 decimal places for percentage
                    if (currentInputValue.includes('.')) {
                        const parts = currentInputValue.split('.');
                        if (parts[1].length > 2) {
                            currentInputValue = parts[0] + '.' + parts[1].substring(0, 2);
                            discountInput.val(currentInputValue);
                        }
                    }
                } else if (newType === 'Amount') {
                    // Validate amount format
                    // Ensure proper decimal places (max 3)
                    if (currentInputValue.includes('.')) {
                        const parts = currentInputValue.split('.');
                        if (parts[1].length > 3) {
                            currentInputValue = parts[0] + '.' + parts[1].substring(0, 3);
                            discountInput.val(currentInputValue);
                        }
                    }
                    
                    // Ensure non-negative
                    if (parsedValue < 0) {
                        discountInput.val('0');
                        currentInputValue = '0';
                    }
                }
            }
            
            // Store current discount value before changing type
            const currentDiscountValue = parseFloat(currentInputValue) || 0;
            
            // Update discount type first (this will trigger calculateRowItem)
            billManager.updateItem(getId, 'discount_type', newType);
            
            // If the value needs to be converted (not 0), update it
            if (currentDiscountValue > 0) {
                // The conversion logic is handled in calculateRowItem
                // We just need to ensure the value is updated
                const updatedItem = {...currentItem};
                updatedItem.discount_type = newType;
                updatedItem.previous_discount_type = currentDiscountType;
                updatedItem._lastChanged = 'discount_type';
                
                const recalculatedItem = billManager.calculateRowItem(updatedItem);
                billManager.map[getId] = recalculatedItem;
                
                const idx = billManager.items.findIndex(i => parseInt(i.id, 10) === getId);
                if (idx >= 0) billManager.items[idx] = recalculatedItem;
                
                updateTableRow(recalculatedItem);
                billManager.updateSummary();
            }
        }
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

    // Handle extra discount input
    $('#extraDiscount').on('input', function() {
        const value = $(this).val();
        const type = $('#extDiscountType').find('option:selected').val();
        
        if (billManager) {
            if (type === 'Percentage') {
                if (value > 50) $(this).val('50');
                billManager.setExtraDiscountValue(value);
                billManager.setExtraDiscountType('percentage');
            } else if (type === 'Amount') {
                billManager.setExtraDiscountValue(value);
                billManager.setExtraDiscountType('amount');
            }
            
            // This will update the summary and UI
            billManager.updateSummary();
        }
    });

    $('#extDiscountType').on('change', function() {
        const type = $(this).val();
        const extraDiscountInput = $('#extraDiscount');
        let value = extraDiscountInput.val().trim();
        
        // Clean the input value
        if (value === '' || value === '.' || value === null) {
            value = '0';
            extraDiscountInput.val('0');
        }
        
        if (billManager) {
            if (type === 'Percentage') {
                // Convert to percentage and enforce 50% limit
                billManager.convertExtraDiscount('percentage');
                
                // Get converted value and ensure it doesn't exceed 50%
                let convertedValue = billManager.summary.extraDiscount.value || 0;
                if (convertedValue > 50) {
                    convertedValue = 50;
                    billManager.summary.extraDiscount.value = 50;
                }
                
                extraDiscountInput.val(convertedValue.toFixed(2));
            } else if (type === 'Amount') {
                // Convert to amount
                billManager.convertExtraDiscount('amount');
                
                // Update the input value with converted value
                const convertedValue = billManager.summary.extraDiscount.value || 0;
                extraDiscountInput.val(parseFloat(convertedValue).toFixed(2));
            }
            
            // This will update the summary and UI
            billManager.updateSummary();
        }
    });
    
    $('#extraDiscount').on('input', function() {
        const type = $('#extDiscountType').find('option:selected').val();
        
        if (billManager) {
            if (type === 'Percentage') {
                // Use helper function with 50% max
                const value = validatePercentageInput(this, 50);
                billManager.setExtraDiscountValue(value);
                billManager.setExtraDiscountType('percentage');
            } else if (type === 'Amount') {
                // For amount, just validate as number
                let value = $(this).val().trim();
                if (value === '' || value === '.' || value === null) {
                    value = '0';
                    $(this).val('0');
                }
                const parsedValue = parseFloat(value) || 0;
                billManager.setExtraDiscountValue(Math.max(parsedValue, 0));
                billManager.setExtraDiscountType('amount');
            }
            
            // This will update the summary and UI
            billManager.updateSummary();
        }
    });

    $('#roundOffToggle').on('change', function() {
        const isEnabled = $(this).is(':checked');
        
        if (billManager) {
            // Update bill manager
            billManager.toggleRoundOff(isEnabled);
            
            // Update all displays
            billManager.updateSummary();
            
            // Update round off display specifically
            updateRoundOffDisplay();
        }
    });

});

function initializeBillManager() {
    billManager = new BillManager();
    
    // Initialize extra discount from form values if they exist
    const extraDiscountValue = $('#extraDiscount').val() || '0';
    const extraDiscountType = $('#extDiscountType').val() || 'Percentage';
    
    if (extraDiscountValue && extraDiscountValue !== '0') {
        if (extraDiscountType === 'Percentage') {
            billManager.setExtraDiscountValue(extraDiscountValue);
            billManager.setExtraDiscountType('percentage');
        } else {
            billManager.setExtraDiscountValue(extraDiscountValue);
            billManager.setExtraDiscountType('amount');
        }
    }
    
    // Trigger initial calculation
    billManager.recalculateSummary();
    updateExtraDiscountUI();
}

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
        $row.find('.bm_efft_'+pid+'_price').addClass('d-none');
        $row.find('#bm_'+pid+'_qty').val(productRow.quantity);
        $row.find('#bm_'+pid+'_unitPrice').val(smartDecimal(productRow.orgunitprice, 8));
        $row.find('#bm_'+pid+'_sellingPrice').val(smartDecimal(productRow.orgselngprice, genSettings.DecimalPoints));
        
        // Update discount field
        $row.find('#bm_'+pid+'_discount').val(productRow.discount);

        // Update discount type dropdown
        $row.find('#bm_'+pid+'_discountType').val(productRow.discount_type || 'Percentage');

        // Visual indicators for override status
        const discountInput = $row.find('#bm_'+pid+'_discount');
        const discountTypeSelect = $row.find('#bm_'+pid+'_discountType');
        if (productRow.discount_is_global && !productRow.overrides_global_discount) {
            discountInput.attr('title', 'Global discount applied');
        } else if (productRow.overrides_global_discount) {
            discountInput.attr('title', 'Overrides global discount');
        } else {
            discountInput.removeAttr('title');
        }

        $row.find('#bm_'+pid+'_netamount').text(smartDecimal(productRow.net_total, genSettings.DecimalPoints, true));
        $row.find('#bm_'+pid+'_tot_unit_amount').text(`${smartDecimal(productRow.line_total, genSettings.DecimalPoints, true)}`);
        $row.find('#bm_'+pid+'_taxAmount').text(`${smartDecimal(productRow.taxAmount, genSettings.DecimalPoints, true)}`);
        if(Number(productRow.discount)) {
            $row.find('.bm_efft_'+pid+'_price').removeClass('d-none');
            $row.find('#bm_'+pid+'_aftdisc_unitPrice').text(smartDecimal(productRow.unitPrice, 8));
            $row.find('#bm_'+pid+'_aftdisc_sellingPrice').text(smartDecimal(productRow.sellingPrice, genSettings.DecimalPoints));
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
        <option value="Percentage" ${productRow.discount_type === 'Percentage' ? 'selected' : ''}>%</option>
        <option value="Amount" ${productRow.discount_type === 'Amount' ? 'selected' : ''}>${genSettings.CurrenySymbol}</option>
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
                    <input type="text" class="form-control form-control-sm updateAllBillAmounts" name="bm_${productRow.id}_sellingPrice" id="bm_${productRow.id}_sellingPrice" min="0" placeholder="Tax Price" onkeydown="return handleDotOnly(event)" oninput="this.value=this.value.slice(0,this.maxLength); validatePriceInput(this, ${genSettings.PriceMaxLength}, ${genSettings.DecimalPoints})" maxLength="${genSettings.PriceMaxLength + 1 + genSettings.DecimalPoints}" pattern="^\d{1,${genSettings.PriceMaxLength}}(\.\d{0,${genSettings.DecimalPoints}})?$" onpaste="handlePricePaste(event, ${genSettings.PriceMaxLength}, ${genSettings.DecimalPoints})" ondrop="handlePriceDrop(event, ${genSettings.PriceMaxLength}, ${genSettings.DecimalPoints})" value="${smartDecimal(productRow.orgselngprice, genSettings.DecimalPoints)}" />
                </div>
                <div class="transtext-small text-muted text-warning bm_efft_${productRow.id}_price ${discBfrPrice}">aft disc: <span id="bm_${productRow.id}_aftdisc_sellingPrice">${smartDecimal(productRow.sellingPrice, genSettings.DecimalPoints)}</span></div>
            </td>
            <td>
                <div class="input-group input-group-merge w-75">
                    <input class="form-control form-control-sm updateAllBillAmounts" type="text" id="bm_${productRow.id}_discount" name="bm_${productRow.id}_discount" min="0" step="0.01" placeholder="Discount" onkeydown="return handleDotOnly(event)" oninput="this.value=this.value.slice(0,this.maxLength); validatePriceInput(this, ${genSettings.PriceMaxLength}, ${genSettings.DecimalPoints})" maxLength="${genSettings.PriceMaxLength}" pattern="^\d{1,${genSettings.PriceMaxLength}}(\.\d{0,${genSettings.DecimalPoints}})?$" onpaste="handleDiscountPaste(event, ${genSettings.PriceMaxLength}, ${genSettings.DecimalPoints})" ondrop="handleDiscountDrop(event, ${genSettings.PriceMaxLength}, ${genSettings.DecimalPoints})" value="${productRow.discount || 0}" title="${productRow.discount_is_global ? 'Global discount applied' : ''}" />
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

function updateRoundOffDisplay() {

    if (!billManager) return;
    
    const roundOffAmount = billManager.summary.roundOff || 0;
    const formattedAmount = smartDecimal((billManager.roundOffEnabled ? roundOffAmount : 0), genSettings.DecimalPoints, true);
    
    // Always show the calculated round off value
    $('.bill_rndoff_amt').text(formattedAmount);

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