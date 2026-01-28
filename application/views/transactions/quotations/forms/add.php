<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('common/transactions/header'); ?>

<!-- Layout wrapper -->
<div class="layout-wrapper transactionPage layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <!-- Layout container -->
        <div class="layout-page">
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

            <?php $FormAttribute = ['id' => 'addQuotationForm', 'name' => 'addQuotationForm', 'autocomplete' => 'off', 'data-csrf' =>  $this->security->get_csrf_token_name(), 'data-csrf-value' => $this->security->get_csrf_hash()];
                    echo form_open('quotations/addQuotation', $FormAttribute); ?>

                    <div class="card mb-3">
                        
                        <div class="card-header bg-body-tertiary trans-header-static trans-theme modal-header-center-sticky d-flex justify-content-between align-items-center pb-3">
                            <div class="d-flex flex-wrap align-items-center gap-3" id="transHeaderInfo">
                                <h5 class="modal-title mb-0 ms-2">Create Quotation</h5>
                                <div class="d-flex align-items-center gap-1">
                                    <div class="input-group w-auto">
                                        <select id="transPrefixSelect" name="transPrefixSelect" class="select2 form-select form-select-sm" required>
                                    <?php try {
                                            if (empty($PrefixData)) {
                                                throw new Exception('Prefix data not loaded');
                                            }
                                            foreach($PrefixData as $preData) { ?>
                                            <option value="<?php echo $preData->Name; ?>" <?php echo $TransPageSettings->DefaultPrefix == $preData->Name ? 'selected' : ''; ?>><?php echo $preData->Name; ?></option>
                                        <?php }
                                        } catch (Exception $e) { ?>
                                            <option value="">Error loading prefixes</option>
                                        <?php } ?>
                                        </select>
                                        <button type="button" class="btn btn-outline-success" id="addTransPrefixBtn" data-toggle="tooltip" title="Add Prefix">➕</button>
                                    </div>
                                    <div class="input-group input-group-sm w-auto">
                                        <span class="input-group-text cursor-pointer"><span id="appendPrefixVal"><?php echo $TransPageSettings->DefaultPrefix; ?></span><?php echo $TransPageSettings->InvoiceSepText; ?><?php echo ($TransPageSettings->ShowFiscalYear == 1) ? $TransPageSettings->FiscalYearType.$TransPageSettings->InvoiceSepText : ''; ?></span>
                                        <input type="number" id="transNumber" name="transNumber" class="form-control transAutoGenNumber stop-incre-indicator" maxLength="20" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength)" pattern="[0-9]*" value="1" required />
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="submit" name="action" value="save" class="btn btn-primary">Save</button>
                                <button type="submit" name="action" value="draft" class="btn btn-outline-secondary">Save as Draft</button>
                                <a href="/quotations" class="btn btn-label-danger">Close</a>
                            </div>
                        </div>

                        <div class="card-body card-body-form-static p-4">

                            <div class="card-header modal-header-center-sticky p-1 mb-3">
                                <h5 class="modal-title mb-0"><i class="bx bx-user me-1"></i> Customer Details</h5>
                            </div>
                            <div class="row">
                                <div class="col-md-3 trans-right-border">
                                    <div class="mb-2">
                                        <label for="quotationType" class="form-label small fw-semibold">Type <span style="color:red">*</span></label>
                                        <select id="quotationType" name="quotationType" class="form-select form-select-sm">
                                            <option value="Regular" selected>Regular</option>
                                            <option value="Without_GST">Without GST</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="dispatchFrom" class="form-label small fw-semibold">Dispatch From</label>
                                        <select id="dispatchFrom" name="dispatchFrom" class="form-select form-select-sm">
                                            <option value="" disabled selected>Select address</option>
                                            <option value="Warehouse A">Warehouse A</option>
                                            <option value="Warehouse B">Warehouse B</option>
                                            <option value="Factory">Factory</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6 border-end pe-3">
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <label for="customerSearch" class="form-label small fw-semibold">Select Customer <span class="text-danger">*</span></label>
                                            <button type="button" id="addTransCustomer" class="btn btn-sm btn-outline-primary mt-1" aria-label="Add new customer"><i class="bx bx-plus-circle me-1"></i> Customer</button>
                                        </div>
                                        <div class="flex-grow-1">
                                            <select id="customerSearch" name="customerSearch" class="form-select form-select-sm"></select>
                                        </div>
                                    </div>
                                    <div id="customerAddressBox" class="mt-2 p-2 border border-secondary trans-border-dotted rounded small d-none"></div>
                                </div>
                                <div class="col-md-3">
                                    <div class="d-flex gap-2 mb-2">
                                        <div class="flex-fill">
                                            <label for="transDate" class="form-label small fw-semibold">Quotation Date <span class="text-danger">*</span></label>
                                            <div class="input-group input-group-merge">
                                                <span class="input-group-text"><i class="icon-base bx bx-calendar"></i></span>
                                                <input type="text" class="form-control form-control-sm" id="transDate" name="transDate" readonly="readonly" value="<?php echo format_datedisplay(time(), 'Y-m-d'); ?>" required />
                                            </div>
                                        </div>
                                        <div class="flex-fill">
                                            <label for="validityDays" class="form-label small fw-semibold">Validity (Days)</label>
                                            <input type="number" id="validityDays" name="validityDays" class="form-control form-control-sm" min="0" step="1" oninput="this.value = this.value.replace(/[^0-9]/g, '')" value="7" />
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <label for="validityDate" class="form-label small fw-semibold">Validity Date</label>
                                        <div class="input-group input-group-merge">
                                            <span class="input-group-text"><i class="icon-base bx bx-calendar"></i></span>
                                            <input type="text" class="form-control form-control-sm" id="validityDate" name="validityDate" readonly="readonly" value="<?php echo format_datedisplay(time(), 'Y-m-d', '', null, '+7'); ?>" required />
                                        </div>
                                    </div>
                                    <div>
                                        <label for="referenceDetails" class="form-label small fw-semibold">Reference</label>
                                        <input type="text" id="referenceDetails" name="referenceDetails" class="form-control form-control-sm" placeholder="Reference, e.g. PO Number, Sales Person, Shipment No..." maxlength="100" />
                                    </div>
                                </div>
                            </div>
                            <hr/>

                            <!-- Product Details -->
                            <div class="card-header modal-header-center-sticky p-1 mb-3">
                                <div class="d-flex align-items-center gap-2">
                                    <h5 class="modal-title mb-0"><i class="bx bx-cart-add me-1"></i> Product & Services Details</h5>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="addTransProduct"><i class="bx bx-plus-circle me-1"></i> Product</button>
                                </div>
                            </div>
                            <div class="row">

                                <div class="card prod-header-static trans-theme p-2">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <div style="width: 20%;">
                                            <select id="prodCategory" name="prodCategory" class="form-select form-select-sm">
                                                <option label="Select Category"></option>
                                            <?php if (sizeof($fltCategoryData) > 0) {
                                                foreach ($fltCategoryData as $Catg) { ?>
                                                    <option value="<?php echo $Catg->CategoryUID; ?>"><?php echo $Catg->Name; ?></option>
                                            <?php }
                                            } ?>
                                            </select>
                                        </div>
                                        <div style="width: 35%;">
                                            <div class="input-group input-group-sm input-group-merge" id="searchProductGroup">
                                                <span class="input-group-text p-2"><i class="icon-base bx bx-search"></i></span>
                                                <select id="searchProductInfo" name="searchProductInfo" class="form-select form-select-sm">
                                                    <option label="-- Select Product --"></option>
                                                    <option
                                                        value="1"
                                                        data-select2-id="44"
                                                        data-allfields='{
                                                            "id": 1,
                                                            "text": "Bearing 5206 2RS",
                                                            "itemName": "Bearing 5206 2RS",
                                                            "unitPrice": 228.81355932,
                                                            "taxAmount": 41.19,
                                                            "sellingPrice": 270,
                                                            "purchasePrice": 175,
                                                            "availableQuantity": 0,
                                                            "hsnCode": "1234567890",
                                                            "primaryUnit": "PCS",
                                                            "category": "Bearings",
                                                            "taxPercent": 18,
                                                            "cgstPercent": 9,
                                                            "sgstPercent": 9,
                                                            "igstPercent": 18,
                                                            "discount": 0,
                                                            "discountType": "Percentage"
                                                        }'
                                                        data-primaryunit="PCS"
                                                        >
                                                        Bearing 5206 2RS
                                                        </option>
                                                </select>
                                                <div class="transerror-tooltip" id="errSearchProd"><span class="icon">!</span>Please select an item in the list.</div>
                                            </div>
                                        </div>
                                        <div style="width: 10%;">
                                            <div class="align-items-center position-relative">
                                                <input type="text" class="form-control" name="prodQuantity" id="prodQuantity" min="0" step="1" placeholder="Quantity" onkeydown="return handleDotOnly(event)" oninput="this.value=this.value.slice(0,this.maxLength); validatePriceInput(this, <?php echo $JwtData->GenSettings->QtyMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" maxLength="<?php echo $JwtData->GenSettings->QtyMaxLength; ?>" pattern="^\d{1,<?php echo $JwtData->GenSettings->QtyMaxLength; ?>}(\.\d{0,<?php echo $JwtData->GenSettings->DecimalPoints; ?>})?$" onpaste="handlePricePaste(event, <?php echo $JwtData->GenSettings->QtyMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" ondrop="handlePriceDrop(event, <?php echo $JwtData->GenSettings->QtyMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" />
                                                <div id="errorProdQty" class="transerror-tooltip"><span class="icon">!</span>Please enter quantity.</div>
                                            </div>
                                        </div>
                                        <div style="width: 10%;">
                                            <button type="button" class="btn btn-success w-100" id="transAddToCartForm"><i class="bx bx-cart-add"></i> Add to Bill</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="card mb-5 p-0">
                                    <div class="table-responsive">
                                        <table id="billTable" class="table trans-table table-bordered table-sm table-hover align-middle" data-update-delay="300">
                                            <thead class="table-light trans-table-light">
                                                <tr>
                                                    <th style="width:30%;">Product Name</th>
                                                    <th style="width:10%;">Quantity</th>
                                                    <th style="width:15%;">Unit Price</th>
                                                    <th style="width:15%;">Price with Tax <i class="bx bx-info-circle ms-1 fs-6 text-primary"  style="cursor: pointer;" data-bs-toggle="tooltip" title="Click to view tax breakdown" onclick="showTaxDetails()"></i></th>
                                                    <th style="width:15%;">
                                                        <div class="d-flex align-items-center gap-1 justify-content-center">
                                                            <span class="fw-semibold text-nowrap">
                                                                Discount on
                                                            </span>
                                                            <select class="form-select form-select-sm w-auto" id="discApplyFor" name="discApplyFor">
                                                                <option value="PriceWithTax">Price With Tax</option>
                                                                <option value="UnitPrice">Unit Price</option>
                                                                <option value="NetAmount">Net Amount</option>
                                                                <option value="TotalAmount" selected>Total Amount</option>
                                                            </select>
                                                        </div>
                                                    </th>
                                                    <th style="width:15%;">
                                                        <div class="text-end">
                                                            <div class="fw-semibold">Total</div>
                                                            <div class="transtext-small text-muted">Net Amount + Tax</div>
                                                        </div>
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody id="billTableBody">
                                                <tr class="text-center text-muted">
                                                    <td colspan="6">
                                                        <div class="py-4">
                                                            <i class="bx bx-cart text-muted text-primary" style="font-size: 2rem;"></i>
                                                            <p class="mt-2 mb-0">No items added yet</p>
                                                            <small class="text-muted">Click "Add Product" or search above to get started</small>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                            <tfoot class="table-light trans-table-light">
                                                <tr>
                                                    <td colspan="1" class="small fw-semibold">#Items: <span class="sumItemCount text-primary">0</span></td>
                                                    <td colspan="4" class="small fw-semibold">Qty: <span class="sumTotalQty text-primary">0</span></td>
                                                    <td colspan="1" class="small fw-semibold text-end">Net Total: <?php echo $JwtData->GenSettings->CurrenySymbol; ?><span class="sumNetTotal ms-1 text-primary"><?php echo smartDecimal(0, $JwtData->GenSettings->DecimalPoints,true); ?></span></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                        <div class="row mt-1 m-1 p-2">
                                            <div class="d-flex align-items-center justify-content-between px-1">
                                                <div class="col-md-4">
                                                    <label for="globalDiscount" class="form-label fw-semibold mb-0">Apply Discount (%) to all items in the cart</label>
                                                    <div class="input-group input-group-merge input-group-sm mt-1 w-50">
                                                        <input type="text" class="form-control form-control-sm" name="globalDiscount" id="globalDiscount" min="0" step="0.01" max="50" placeholder="Discount (%)" onkeydown="return handleDotOnly(event)" oninput="this.value=this.value.slice(0,this.maxLength); validatePriceInput(this, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" maxLength="<?php echo $JwtData->GenSettings->PriceMaxLength; ?>" pattern="^\d{1,<?php echo $JwtData->GenSettings->PriceMaxLength; ?>}(\.\d{0,<?php echo $JwtData->GenSettings->DecimalPoints; ?>})?$" onpaste="handlePricePaste(event, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" ondrop="handlePriceDrop(event, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" value="0" />
                                                        <button class="btn btn-sm btn-outline-danger" type="button" id="clearGlobalDiscount"><i class="bx bx-x"></i></button>
                                                    </div>
                                                    <div class="form-text transtext-small text-danger small mt-1">This discount will be applied to all items. Individual discounts will be overridden.</div>
                                                </div>
                                                <div class="row">
                                                    <div class="d-flex align-items-center justify-content-end">
                                                        <button id="toggleChargesBtn" class="btn btn-sm btn-outline-secondary">
                                                            <i class="bx bx-plus-circle me-1"></i> Additional Charges
                                                        </button>
                                                    </div>
                                                    <div id="additionalChargesBox" class="mt-2 p-2 d-none">
                                                        <div class="row g-2">
                                                            <div class="col-md-12">
                                                                <table class="table trans-table table-bordered border-primary rounded table-sm mb-0">
                                                                    <thead class="table-light trans-table-light">
                                                                        <tr>
                                                                            <th>Charges</th>
                                                                            <th>Tax</th>
                                                                            <th>in (%)</th>
                                                                            <th>withoutTax in (<?php echo $JwtData->GenSettings->CurrenySymbol; ?>)</th>
                                                                            <th>withTax in (<?php echo $JwtData->GenSettings->CurrenySymbol; ?>)</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <tr>
                                                                            <td>Delivery / Shipping Charges</td>
                                                                            <td>
                                                                                <select class="form-select form-select-sm additional-charge-tax" id="shippingCharges" name="shippingCharges" data-type="shipping" data-field="tax">
                                                                                <?php foreach ($TaxDetInfo as $TaxInfo) { ?>
                                                                                    <option value="<?php echo $TaxInfo->TaxDetailsUID; ?>" data-percent="<?php echo smartDecimal($TaxInfo->Percentage); ?>" <?php echo smartDecimal($TaxInfo->Percentage) == 0 ? 'selected' : ''; ?>><?php echo smartDecimal($TaxInfo->Percentage); ?></option>
                                                                                <?php } ?>
                                                                                </select>
                                                                            </td>
                                                                            <td>
                                                                                <input type="text" class="form-control form-control-sm additional-charge-percent" data-type="shipping" data-field="percent" name="shippingPercent" id="shippingPercent" min="0" placeholder="Enter Percentage" onkeydown="return handleDotOnly(event)" oninput="this.value=this.value.slice(0,this.maxLength); validatePriceInput(this, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" maxLength="6" pattern="^\d{1,<?php echo $JwtData->GenSettings->PriceMaxLength; ?>}(\.\d{0,<?php echo $JwtData->GenSettings->DecimalPoints; ?>})?$" onpaste="handlePricePaste(event, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" ondrop="handlePriceDrop(event, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" value="0" />
                                                                            </td>
                                                                            <td>
                                                                                <input type="text" class="form-control form-control-sm additional-charge-withouttax" data-type="shipping" data-field="withoutTax" name="shippingChargeWOutTax" id="shippingChargeWOutTax" min="0" placeholder="Enter Without Tax Amount" onkeydown="return handleDotOnly(event)" oninput="this.value=this.value.slice(0,this.maxLength); validatePriceInput(this, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" maxLength="<?php echo $JwtData->GenSettings->PriceMaxLength; ?>" pattern="^\d{1,<?php echo $JwtData->GenSettings->PriceMaxLength; ?>}(\.\d{0,<?php echo $JwtData->GenSettings->DecimalPoints; ?>})?$" onpaste="handlePricePaste(event, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" ondrop="handlePriceDrop(event, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" value="0" />
                                                                            </td>
                                                                            <td>
                                                                                <input type="text" class="form-control form-control-sm additional-charge-withtax" data-type="shipping" data-field="withTax" name="shippingChargeWithTax" id="shippingChargeWithTax" min="0" placeholder="Enter Without Tax Amount" onkeydown="return handleDotOnly(event)" oninput="this.value=this.value.slice(0,this.maxLength); validatePriceInput(this, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" maxLength="<?php echo $JwtData->GenSettings->PriceMaxLength; ?>" pattern="^\d{1,<?php echo $JwtData->GenSettings->PriceMaxLength; ?>}(\.\d{0,<?php echo $JwtData->GenSettings->DecimalPoints; ?>})?$" onpaste="handlePricePaste(event, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" ondrop="handlePriceDrop(event, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" value="0" />
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>Packaging Charges</td>
                                                                            <td>
                                                                                <select class="form-select form-select-sm additional-charge-tax" id="packingCharges" name="packingCharges" data-type="packing" data-field="tax">
                                                                                <?php foreach ($TaxDetInfo as $TaxInfo) { ?>
                                                                                    <option value="<?php echo $TaxInfo->TaxDetailsUID; ?>" data-percent="<?php echo smartDecimal($TaxInfo->Percentage); ?>" <?php echo smartDecimal($TaxInfo->Percentage) == 0 ? 'selected' : ''; ?>><?php echo smartDecimal($TaxInfo->Percentage); ?></option>
                                                                                <?php } ?>
                                                                                </select>
                                                                            </td>
                                                                            <td>
                                                                                <input type="text" class="form-control form-control-sm additional-charge-percent" name="packingPercent" data-type="packing" data-field="percent" id="packingPercent" min="0" placeholder="Enter Percentage" onkeydown="return handleDotOnly(event)" oninput="this.value=this.value.slice(0,this.maxLength); validatePriceInput(this, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" maxLength="6" pattern="^\d{1,<?php echo $JwtData->GenSettings->PriceMaxLength; ?>}(\.\d{0,<?php echo $JwtData->GenSettings->DecimalPoints; ?>})?$" onpaste="handlePricePaste(event, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" ondrop="handlePriceDrop(event, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" value="0" />
                                                                            </td>
                                                                            <td>
                                                                                <input type="text" class="form-control form-control-sm additional-charge-withouttax" data-type="packing" data-field="withoutTax" name="packingChargeWOutTax" id="packingChargeWOutTax" min="0" placeholder="Enter Without Tax Amount" onkeydown="return handleDotOnly(event)" oninput="this.value=this.value.slice(0,this.maxLength); validatePriceInput(this, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" maxLength="<?php echo $JwtData->GenSettings->PriceMaxLength; ?>" pattern="^\d{1,<?php echo $JwtData->GenSettings->PriceMaxLength; ?>}(\.\d{0,<?php echo $JwtData->GenSettings->DecimalPoints; ?>})?$" onpaste="handlePricePaste(event, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" ondrop="handlePriceDrop(event, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" value="0" />
                                                                            </td>
                                                                            <td>
                                                                                <input type="text" class="form-control form-control-sm additional-charge-withtax" data-type="packing" data-field="withTax" name="packingChargeWithTax" id="packingChargeWithTax" min="0" placeholder="Enter Without Tax Amount" onkeydown="return handleDotOnly(event)" oninput="this.value=this.value.slice(0,this.maxLength); validatePriceInput(this, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" maxLength="<?php echo $JwtData->GenSettings->PriceMaxLength; ?>" pattern="^\d{1,<?php echo $JwtData->GenSettings->PriceMaxLength; ?>}(\.\d{0,<?php echo $JwtData->GenSettings->DecimalPoints; ?>})?$" onpaste="handlePricePaste(event, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" ondrop="handlePriceDrop(event, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" value="0" />
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                    
                                <!-- Notes & Terms Condition Details -->
                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <label for="transNotes" class="form-label small fw-semibold">Notes </label>
                                        <textarea class="form-control" name="transNotes" id="transNotes" rows="2" placeholder="Enter your notes, say thanks, or anything else"></textarea>
                                    </div>
                                    <div class="mb-2">
                                        <label for="transTermsCond" class="form-label small fw-semibold">Terms & Conditions </label>
                                        <textarea class="form-control" name="transTermsCond" id="transTermsCond" rows="2" placeholder="Enter your business terms & Condition"><?php echo "1. Goods once sold will not be taken back or exchanged\n2. All disputes are subject to Gingee jurisdiction only"; ?></textarea>
                                    </div>
                                    <div class="accordion transAccordion" id="dropZoneAccordion">
                                        <div class="accordion-item">
                                            <h2 class="accordion-header text-body d-flex justify-content-between">
                                                <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionUploadFiles" aria-controls="accordionUploadFiles" aria-expanded="false">
                                                <i class="icon-base bx bx-paperclip me-2"></i> Attach Files <span class="ms-2 text-muted">(Max: 5)</span></button>
                                            </h2>
                                            <div id="accordionUploadFiles" class="accordion-collapse collapse" data-bs-parent="#dropZoneAccordion">
                                                <div class="accordion-body">
                                                    <div class="dropzone needsclick p-3 dz-clickable w-100" id="multipleDropzone">
                                                        <div class="dz-message needsclick text-center">
                                                            <i class="upload-icon mb-3"></i>
                                                            <p class="h5 needsclick mb-2">Drag and drop files / images here</p>
                                                            <p class="h4 text-body-secondary fw-normal mb-0">JPG, GIF or PNG of 1 MB</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Summary and Bank/Signature -->
                                <div class="col-md-6 p-2 trans-theme" style="align-self: flex-start;">
                                    <div class="row">
                                        
                                        <!-- Tax Detailed View -->
                                        <div class="col-md-6 tax-summary-section">
                                            <div id="taxBreakupPanel" style="display: none;" class="tax-details-view p-2 bg-light rounded border">
                                                <h6 class="tax-details-title mb-2">Tax Breakdown</h6>
                                                
                                                <!-- Items Tax Table -->
                                                <div class="mb-3">
                                                    <p class="small fw-semibold mb-2 text-secondary">Items Tax</p>
                                                    <div class="table-responsive">
                                                        <table class="table table-sm mb-0">
                                                            <thead class="small bg-light">
                                                                <tr>
                                                                    <th class="fw-semibold border-bottom"># Items</th>
                                                                    <th class="fw-semibold border-bottom taxBreakUpItemsCgst">CGST</th>
                                                                    <th class="fw-semibold border-bottom taxBreakUpItemsSgst">SGST</th>
                                                                    <th class="fw-semibold border-bottom taxBreakUpItemsIgst d-none">IGST</th>
                                                                    <th class="fw-semibold border-bottom text-end">Total Amount</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <tr>
                                                                    <td class="py-1"><span class="taxBreakUpItemsCnt">0</span></td>
                                                                    <td class="py-1 taxBreakUpItemsCgst"><?php echo $JwtData->GenSettings->CurrenySymbol; ?> <span class="taxBreakUpItemsCgstVal">0</span></td>
                                                                    <td class="py-1 taxBreakUpItemsSgst"><?php echo $JwtData->GenSettings->CurrenySymbol; ?> <span class="taxBreakUpItemsSgstVal">0</span></td>
                                                                    <td class="py-1 taxBreakUpItemsIgst d-none"><?php echo $JwtData->GenSettings->CurrenySymbol; ?> <span class="taxBreakUpItemsIgstVal">0</span></td>
                                                                    <td class="py-1 text-end fw-semibold"><?php echo $JwtData->GenSettings->CurrenySymbol; ?> <span class="taxBreakUpItemsTotAmt">0</span></td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>

                                                <!-- Charges Tax Table -->
                                                <div class="mb-3 d-none" id="chargeBreakUpTaxDetails">
                                                    <p class="small fw-semibold mb-2 text-secondary">All Charges Tax</p>
                                                    <div class="table-responsive">
                                                        <table class="table table-sm mb-0">
                                                            <thead class="small bg-light">
                                                                <tr>
                                                                    <th class="fw-semibold border-bottom">Charges</th>
                                                                    <th class="fw-semibold border-bottom taxBreakUpChargesCgst">CGST</th>
                                                                    <th class="fw-semibold border-bottom taxBreakUpChargesSgst">SGST</th>
                                                                    <th class="fw-semibold border-bottom taxBreakUpChargesIgst d-none">IGST</th>
                                                                    <th class="fw-semibold border-bottom text-end">Total Amount</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <tr class="cgst-sgst-row">
                                                                    <td class="py-1">Shipping</td>
                                                                    <td class="py-1 taxBreakUpChargesCgst"><?php echo $JwtData->GenSettings->CurrenySymbol; ?> <span class="taxBreakUpChargesCgstVal">0</span></td>
                                                                    <td class="py-1 taxBreakUpChargesSgst"><?php echo $JwtData->GenSettings->CurrenySymbol; ?> <span class="taxBreakUpChargesSgstVal">0</span></td>
                                                                    <td class="py-1 taxBreakUpChargesIgst d-none"><?php echo $JwtData->GenSettings->CurrenySymbol; ?> <span class="taxBreakUpChargesIgstVal">0</span></td>
                                                                    <td class="py-1 text-end fw-semibold"><?php echo $JwtData->GenSettings->CurrenySymbol; ?> <span class="taxBreakUpChargesTotAmt">0</span></td>
                                                                </tr>
                                                            </tbody>
                                                            <tfoot>
                                                                <tr class="border-top">
                                                                    <td colspan="3" class="pt-2 fw-semibold">Total Charges Tax</td>
                                                                    <td class="pt-2 text-end fw-semibold"><?php echo $JwtData->GenSettings->CurrenySymbol; ?> <span id="chargeTaxTotal">0</span></td>
                                                                </tr>
                                                            </tfoot>
                                                        </table>
                                                    </div>
                                                </div>

                                                <!-- Final Tax Total -->
                                                <div class="border-top pt-2">
                                                    <div class="table-responsive">
                                                        <table class="table table-sm mb-0">
                                                            <tbody>
                                                                <tr>
                                                                    <td class="fw-bold">Grand Tax Total</td>
                                                                    <td class="text-end fw-bold"><?php echo $JwtData->GenSettings->CurrenySymbol; ?> <span id="grandChargesTaxTotal">0</span></td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6 p-2 pe-5">
                                            <div class="row g-2">

                                                <div class="d-flex align-items-center justify-content-end">
                                                    <div class="d-flex justify-content-end w-60 me-2">
                                                        <label class="form-label small fw-semibold">Extra Discount (%)</label>
                                                    </div>
                                                    <div class="input-group input-group-merge w-70">
                                                        <select class="form-select form-select-sm" id="extDiscountType" name="extDiscountType">
                                                        <?php foreach ($DiscTypeInfo as $DiscType) { ?>
                                                            <option value="<?php echo $DiscType->Name; ?>"><?php echo $DiscType->Symbol; ?></option>
                                                        <?php } ?>
                                                        </select>
                                                        <input class="form-control form-control-sm ps-1 w-30" type="text" id="extraDiscount" name="extraDiscount" min="0" step="0.01" placeholder="Extra Discount" onkeydown="return handleDotOnly(event)" oninput="this.value=this.value.slice(0,this.maxLength); validatePriceInput(this, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" maxlength="<?php echo $JwtData->GenSettings->PriceMaxLength; ?>" pattern="^d{1,<?php echo $JwtData->GenSettings->PriceMaxLength; ?>}(.d{0,<?php echo $JwtData->GenSettings->DecimalPoints; ?>})?$" onpaste="handleDiscountPaste(event, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" ondrop="handleDiscountDrop(event, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" value="0">
                                                    </div>
                                                </div>

                                                <div class="d-flex align-items-center justify-content-end mt-2 d-none" id="shippingRow">
                                                    <div class="d-flex justify-content-end w-70">
                                                        <label class="form-label small fw-semibold" id="shippingChargeLabel">Shipping Charges</label>
                                                    </div>
                                                    <div class="d-flex justify-content-end w-70 me-1">
                                                        <span class="me-1"><?php echo $JwtData->GenSettings->CurrenySymbol; ?></span>
                                                        <span id="shippingChargeAmt"><?php echo smartDecimal(0, $JwtData->GenSettings->DecimalPoints,true); ?></span>
                                                    </div>
                                                </div>

                                                <div class="d-flex align-items-center justify-content-end mt-2 d-none" id="packingRow">
                                                    <div class="d-flex justify-content-end w-70">
                                                        <label class="form-label small fw-semibold" id="packingChargeLabel">Packaging Charges </label>
                                                    </div>
                                                    <div class="d-flex justify-content-end w-70 me-1">
                                                        <span class="me-1"><?php echo $JwtData->GenSettings->CurrenySymbol; ?></span>
                                                        <span id="packingChargeAmt"><?php echo smartDecimal(0, $JwtData->GenSettings->DecimalPoints,true); ?></span>
                                                    </div>
                                                </div>

                                                <div class="d-flex align-items-center justify-content-end mt-2 d-none" id="handlingRow">
                                                    <div class="d-flex justify-content-end w-70">
                                                        <label class="form-label small fw-semibold" id="handlingChargeLabel">Handling Charges</label>
                                                    </div>
                                                    <div class="d-flex justify-content-end w-70 me-1">
                                                        <span class="me-1"><?php echo $JwtData->GenSettings->CurrenySymbol; ?></span>
                                                        <span id="handlingChargeAmt"><?php echo smartDecimal(0, $JwtData->GenSettings->DecimalPoints,true); ?></span>
                                                    </div>
                                                </div>

                                                <div class="d-flex align-items-center justify-content-end mt-2 d-none" id="othersRow">
                                                    <div class="d-flex justify-content-end w-70">
                                                        <label class="form-label small fw-semibold" id="othersChargeLabel">Other Charges </label>
                                                    </div>
                                                    <div class="d-flex justify-content-end w-70 me-1">
                                                        <span class="me-1"><?php echo $JwtData->GenSettings->CurrenySymbol; ?></span>
                                                        <span id="othersChargeAmt"><?php echo smartDecimal(0, $JwtData->GenSettings->DecimalPoints,true); ?></span>
                                                    </div>
                                                </div>

                                                <div class="d-flex align-items-center justify-content-end mt-2">
                                                    <div class="d-flex justify-content-end w-70">
                                                        <label class="form-label small fw-semibold">Taxable Amount</label>
                                                    </div>
                                                    <div class="d-flex justify-content-end w-70 me-1">
                                                        <span class="me-1"><?php echo $JwtData->GenSettings->CurrenySymbol; ?></span>
                                                        <span class="bill_taxable_amt"><?php echo smartDecimal(0, $JwtData->GenSettings->DecimalPoints,true); ?></span>
                                                    </div>
                                                </div>

                                                <div class="d-flex align-items-center justify-content-end mt-2">
                                                    <div class="d-flex justify-content-end w-70">
                                                        <label class="form-label small fw-semibold">Total Tax <button type="button" class="btn btn-sm btn-link p-0 border-0" id="taxBreakupToggle"><i id="showHideTaxBreakUp" class="bx bxs-show tax-toggle-icon ms-1 fs-6 d-none"></i></i></button></label>
                                                    </div>
                                                    <div class="d-flex justify-content-end w-70 me-1">
                                                        <span class="me-1"><?php echo $JwtData->GenSettings->CurrenySymbol; ?></span>
                                                        <span class="bill_tot_tax_amt"><?php echo smartDecimal(0, $JwtData->GenSettings->DecimalPoints,true); ?></span>
                                                    </div>
                                                </div>

                                                <div class="d-flex align-items-center justify-content-end mt-2">
                                                    <div class="d-flex justify-content-end w-70">
                                                        <div class="form-check form-switch mb-0">
                                                            <label class="form-check-label small fw-semibold" for="roundOffToggle">Round Off</label>
                                                            <input class="form-check-input" type="checkbox" name="roundOffToggle" id="roundOffToggle" checked />
                                                        </div>
                                                    </div>
                                                    <div class="d-flex justify-content-end w-70 me-1">
                                                        <span class="me-1"><?php echo $JwtData->GenSettings->CurrenySymbol; ?></span>
                                                        <span class="bill_rndoff_amt"><?php echo smartDecimal(0, $JwtData->GenSettings->DecimalPoints,true); ?></span>
                                                    </div>
                                                </div>

                                                <div class="d-flex align-items-center justify-content-end mt-2">
                                                    <div class="d-flex justify-content-end w-70">
                                                        <label class="form-label fw-semibold fs-4 text-primary">Total Amount</label>
                                                    </div>
                                                    <div class="d-flex justify-content-end w-70 me-1">
                                                        <span class="me-1 fs-4 text-primary"><?php echo $JwtData->GenSettings->CurrenySymbol; ?></span>
                                                        <span class="bill_tot_amt fw-semibold fs-4 text-primary"><?php echo smartDecimal(0, $JwtData->GenSettings->DecimalPoints,true); ?></span>
                                                    </div>
                                                </div>

                                                <div class="d-flex align-items-center justify-content-end mt-2">
                                                    <div class="d-flex justify-content-end w-70">
                                                        <label class="form-label small fw-semibold">Total Discount</label>
                                                    </div>
                                                    <div class="d-flex justify-content-end w-70 me-1">
                                                        <span class="me-1"><?php echo $JwtData->GenSettings->CurrenySymbol; ?></span>
                                                        <span class="bill_tot_disc_amt"><?php echo smartDecimal(0, $JwtData->GenSettings->DecimalPoints,true); ?></span>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                        
                                    </div>
                                </div>

                            </div>

                        </div> <!-- /card-body -->
                    </div> <!-- /card -->

                    <?php echo form_close(); ?>

                </div>
            </div>
            <!-- Content wrapper -->
             
            <?php $this->load->view('common/transactions/transprefix'); ?>
            <?php $this->load->view('transactions/modals/customer'); ?>
            <?php $this->load->view('transactions/modals/taxdetails'); ?>
            <?php $this->load->view('products/modals/items'); ?>
            <?php $this->load->view('common/footer_desc'); ?>

        </div>

    </div>
</div>

<?php $this->load->view('common/transactions/footer'); ?>

<script src="/js/transactions/quotations.js"></script>
<script src="/js/transactions/transactions.js"></script>
<script src="/js/transactions/transprefix.js"></script>
<script src="/js/transactions/modaladdress.js"></script>
<script src="/js/transactions/products.js"></script>

<script>
const StateInfo = <?php echo json_encode($StateData); ?>;
const CityInfo = <?php echo json_encode($CityData); ?>;
const EnableStorage = <?php echo $JwtData->GenSettings->EnableStorage; ?>;
let imgData;
$(function() {
    'use strict'

    searchCustomers('customerSearch');
    transDatePickr('#transDate', false, 'Y-m-d', false, true, true, true, 'd-m-Y');
    transDatePickr('#validityDate', false, 'Y-m-d', false, false, false, true, 'd-m-Y', '#transDate');
    
    setupTransactionValidity('#transDate', '#validityDays', '#validityDate');

});
</script>