<?php defined('BASEPATH') or exit('No direct script access allowed');
// Shared product section (search bar, bill table, notes/terms, summary) for all CREATE forms.
// Required (from controller pageData): $JwtData
// Optional variables (pass via load->view second arg):
//   $transProductSectionTitle  — default: 'Product &amp; Services Details'
//   $transNotesPlaceholder     — default: 'Enter your notes, say thanks, or anything else'
//   $transTermsPlaceholder     — default: 'Enter your business terms &amp; Condition'
//   $transNotesContent         — default: '' (pre-filled text, e.g. from clone)
//   $transTermsContent         — default: '' (pre-filled text, e.g. from clone)
//   $transShowDropzone         — default: false (set true for quotations)
//   $transShowChargesBreakdown — default: false (set true for quotations)
$_secTitle  = isset($transProductSectionTitle) ? $transProductSectionTitle : 'Product &amp; Services Details';
$_notesPh   = isset($transNotesPlaceholder)    ? $transNotesPlaceholder    : 'Enter your notes, say thanks, or anything else';
$_termsPh   = isset($transTermsPlaceholder)    ? $transTermsPlaceholder    : 'Enter your business terms &amp; Condition';
$_notesVal  = isset($transNotesContent)        ? htmlspecialchars($transNotesContent) : '';
$_termsVal  = isset($transTermsContent)        ? htmlspecialchars($transTermsContent) : '';
$_dropzone    = !empty($transShowDropzone);
$_chargesBd   = !empty($transShowChargesBreakdown);
$_hideAddProd    = !empty($transHideAddProduct);
$_hideSearchCard = !empty($transHideProductSearch);
$_paymentVars    = isset($transPaymentVars) ? $transPaymentVars : null;
?>
<div class="card-header modal-header-center-sticky p-1 mb-3 d-flex align-items-center justify-content-between">
    <!-- Left: title + add button -->
    <div class="d-flex align-items-center gap-2">
        <h5 class="modal-title mb-0"><i class="bx bx-cart-add me-1"></i> <?php echo $_secTitle; ?></h5>
        <?php if (!$_hideAddProd): ?>
        <button type="button" class="trans-add-btn btn btn-outline-primary" id="addTransProduct"><i class="bx bx-plus-circle me-1"></i> Product</button>
        <?php endif; ?>
    </div>
    <!-- Right: controls -->
    <div class="d-flex align-items-center gap-3">
        <?php if (!empty($JwtData->TransSettings->ShowProductDescription)): ?>
        <div class="form-check form-check-inline mb-0">
            <input class="form-check-input" type="checkbox" id="chkShowDesc" checked>
            <label class="form-check-label small" for="chkShowDesc" style="cursor:pointer;">Show Description</label>
        </div>
        <?php endif; ?>
<script>window._showProductDescription = <?= !empty($JwtData->TransSettings->ShowProductDescription) ? 'true' : 'false'; ?>;</script>
        <div class="form-check form-check-inline mb-0">
            <input class="form-check-input" type="checkbox" id="chkReverseOrder">
            <label class="form-check-label small" for="chkReverseOrder" style="cursor:pointer;">Reverse Order</label>
        </div>
        <button type="button" class="btn btn-sm btn-outline-danger d-none" id="btnClearCart" style="font-size:.75rem;">
            <i class="bx bx-trash me-1"></i>Clear All
        </button>
    </div>
</div>
<div class="row">

    <?php if (!$_hideSearchCard): ?>
    <div class="card prod-header-static trans-theme p-1">
        <div class="d-flex align-items-center gap-2 mb-1">
            <div style="width: 20%;">
                <select id="prodCategory" name="prodCategory" class="form-select form-select-sm">
                    <option label="Select Category"></option>
                </select>
            </div>
            <div style="width: 35%;">
                <div class="input-group input-group-sm input-group-merge" id="searchProductGroup">
                    <span class="input-group-text p-2 cursor-pointer" id="openProdSearchModal" style="background:#f0efff !important;border-color:#d9d8ff;color:#696cff;"><i class="icon-base bx bx-search"></i></span>
                    <select id="searchProductInfo" name="searchProductInfo" class="form-select form-select-sm">
                        <option label="-- Select Product --"></option>
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
    <?php endif; ?>

    <div class="card mb-5 p-0">
        <div class="table-responsive">
            <table id="billTable" class="table trans-table table-bordered table-sm table-hover align-middle" data-update-delay="300">
                <thead class="table-light trans-table-light">
                    <tr>
                        <th style="width:30px;"></th>
                        <th style="width:30%;">Product Name</th>
                        <th style="width:10%;">Quantity</th>
                        <th style="width:15%;">Unit Price</th>
                        <th style="width:15%;">Price with Tax <i class="bx bx-info-circle ms-1 fs-6 text-primary" style="cursor:pointer;" data-bs-toggle="tooltip" title="Click to view tax breakdown" onclick="showTaxDetails()"></i></th>
                        <th style="width:15%;">
                            <div class="d-flex align-items-center gap-1 justify-content-center">
                                <span class="fw-semibold text-nowrap">Discount on</span>
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
                        <td colspan="7">
                            <div class="py-4">
                                <i class="bx bx-cart text-primary" style="font-size:2rem;"></i>
                                <p class="mt-2 mb-0">No items added yet</p>
                                <small class="text-muted">Click "Add Product" or search above to get started</small>
                            </div>
                        </td>
                    </tr>
                </tbody>
                <tfoot class="table-light trans-table-light">
                    <tr>
                        <td colspan="2" class="small fw-semibold">#Items: <span class="sumItemCount text-primary">0</span></td>
                        <td colspan="4" class="small fw-semibold">Qty: <span class="sumTotalQty text-primary">0</span></td>
                        <td colspan="1" class="small fw-semibold text-end">Net Total: <?php echo $JwtData->GenSettings->CurrenySymbol; ?><span class="sumNetTotal ms-1 text-primary"><?php echo smartDecimal(0, $JwtData->GenSettings->DecimalPoints, true); ?></span></td>
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
                            <button id="toggleChargesBtn" class="btn btn-sm btn-outline-secondary"><i class="bx bx-plus-circle me-1"></i> Additional Charges</button>
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
                                                    </select>
                                                </td>
                                                <td><input type="text" class="form-control form-control-sm additional-charge-percent" data-type="shipping" data-field="percent" name="shippingPercent" id="shippingPercent" min="0" placeholder="Enter Percentage" onkeydown="return handleDotOnly(event)" oninput="this.value=this.value.slice(0,this.maxLength); validatePriceInput(this, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" maxLength="6" value="0" /></td>
                                                <td><input type="text" class="form-control form-control-sm additional-charge-withouttax" data-type="shipping" data-field="withoutTax" name="shippingChargeWOutTax" id="shippingChargeWOutTax" min="0" placeholder="Without Tax" onkeydown="return handleDotOnly(event)" oninput="this.value=this.value.slice(0,this.maxLength); validatePriceInput(this, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" maxLength="<?php echo $JwtData->GenSettings->PriceMaxLength; ?>" value="0" /></td>
                                                <td><input type="text" class="form-control form-control-sm additional-charge-withtax" data-type="shipping" data-field="withTax" name="shippingChargeWithTax" id="shippingChargeWithTax" min="0" placeholder="With Tax" onkeydown="return handleDotOnly(event)" oninput="this.value=this.value.slice(0,this.maxLength); validatePriceInput(this, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" maxLength="<?php echo $JwtData->GenSettings->PriceMaxLength; ?>" value="0" /></td>
                                            </tr>
                                            <tr>
                                                <td>Packaging Charges</td>
                                                <td>
                                                    <select class="form-select form-select-sm additional-charge-tax" id="packingCharges" name="packingCharges" data-type="packing" data-field="tax">
                                                    </select>
                                                </td>
                                                <td><input type="text" class="form-control form-control-sm additional-charge-percent" name="packingPercent" data-type="packing" data-field="percent" id="packingPercent" min="0" placeholder="Enter Percentage" onkeydown="return handleDotOnly(event)" oninput="this.value=this.value.slice(0,this.maxLength); validatePriceInput(this, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" maxLength="6" value="0" /></td>
                                                <td><input type="text" class="form-control form-control-sm additional-charge-withouttax" data-type="packing" data-field="withoutTax" name="packingChargeWOutTax" id="packingChargeWOutTax" min="0" placeholder="Without Tax" onkeydown="return handleDotOnly(event)" oninput="this.value=this.value.slice(0,this.maxLength); validatePriceInput(this, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" maxLength="<?php echo $JwtData->GenSettings->PriceMaxLength; ?>" value="0" /></td>
                                                <td><input type="text" class="form-control form-control-sm additional-charge-withtax" data-type="packing" data-field="withTax" name="packingChargeWithTax" id="packingChargeWithTax" min="0" placeholder="With Tax" onkeydown="return handleDotOnly(event)" oninput="this.value=this.value.slice(0,this.maxLength); validatePriceInput(this, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" maxLength="<?php echo $JwtData->GenSettings->PriceMaxLength; ?>" value="0" /></td>
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

    <div class="col-md-6">
        <div class="mb-2">
            <label for="transNotes" class="form-label small fw-semibold">Notes</label>
            <textarea class="form-control" name="transNotes" id="transNotes" rows="2" placeholder="<?php echo $_notesPh; ?>"><?php echo $_notesVal; ?></textarea>
        </div>
        <?php if (empty($transHideTerms)): ?>
        <div class="mb-2">
            <label for="transTermsCond" class="form-label small fw-semibold">Terms &amp; Conditions</label>
            <textarea class="form-control" name="transTermsCond" id="transTermsCond" rows="2" placeholder="<?php echo $_termsPh; ?>"><?php echo $_termsVal; ?></textarea>
        </div>
        <?php endif; ?>
        <?php if ($_dropzone): ?>
        <div class="card border-0 shadow-sm mt-3" style="border-left:3px solid #6366f1 !important;">
            <div class="card-header bg-transparent border-0 py-2 px-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <span class="d-flex align-items-center justify-content-center rounded-2"
                          style="width:28px;height:28px;background:#ede9fe;">
                        <i class="bx bx-paperclip" style="color:#6366f1;font-size:1rem;"></i>
                    </span>
                    <span style="font-weight:600;font-size:.875rem;color:#374151;">Attach Files</span>
                    <span id="transAttachBadge" class="badge rounded-pill d-none"
                          style="background:#ede9fe;color:#6366f1;font-size:.7rem;font-weight:600;"></span>
                </div>
                <!-- id="transAttachHint" is updated by _attachResetState on DOM ready -->
                <span id="transAttachHint" style="font-size:.72rem;color:#9ca3af;"></span>
            </div>
            <div class="card-body px-3 pt-0 pb-3">
                <div id="transAttachZone" class="prod-attach-zone" onclick="_attachZoneTrigger('Transaction', event)">
                    <div id="transAttachEmpty" class="prod-attach-empty">
                        <i class="bx bx-upload" id="transAttachIcon" style="font-size:2rem;color:#9ca3af;display:block;margin-bottom:6px;"></i>
                        <div id="transAttachLabel" style="font-size:.78rem;font-weight:600;color:#6b7280;">Drag &amp; drop files here</div>
                    </div>
                </div>
                <div id="transAttachList" class="prod-attach-list mt-2" style="display:none;"></div>
                <input type="file" id="transAttachInput" multiple style="display:none;">
                <input type="hidden" id="RemovedAttachIDs" name="RemovedAttachIDs" value="">
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-md-6 p-2 trans-theme" style="align-self:flex-start;">
        <!-- Summary Information Section -->
        <div class="row">
            <div class="col-md-6 tax-summary-section">
                <div id="taxBreakupPanel" style="display:none;" class="tax-details-view rounded-3 overflow-hidden" style="border:1px solid #bae6fd;">

                    <!-- Header strip -->
                    <div class="d-flex align-items-center gap-2 px-3 py-2" style="background:#0284c7;">
                        <i class="bx bx-receipt" style="color:#fff;font-size:1rem;"></i>
                        <span style="font-size:.78rem;font-weight:700;color:#fff;letter-spacing:.4px;text-transform:uppercase;">Tax Breakdown</span>
                    </div>

                    <!-- Items Tax metrics -->
                    <div style="background:#f0f9ff;padding:10px 12px 8px;">
                        <div style="font-size:.65rem;font-weight:700;color:#0369a1;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">
                            Items Tax · <span class="taxBreakUpItemsCnt">0</span> items
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:6px;">
                            <div class="taxBreakUpItemsCgst" style="background:#fff;border-radius:6px;padding:6px 8px;border:1px solid #e0f2fe;">
                                <div style="font-size:.6rem;color:#64748b;text-transform:uppercase;letter-spacing:.3px;margin-bottom:2px;">CGST</div>
                                <div style="font-weight:600;font-size:.82rem;color:#0284c7;"><?php echo $JwtData->GenSettings->CurrenySymbol; ?> <span class="taxBreakUpItemsCgstVal">0</span></div>
                            </div>
                            <div class="taxBreakUpItemsSgst" style="background:#fff;border-radius:6px;padding:6px 8px;border:1px solid #e0f2fe;">
                                <div style="font-size:.6rem;color:#64748b;text-transform:uppercase;letter-spacing:.3px;margin-bottom:2px;">SGST</div>
                                <div style="font-weight:600;font-size:.82rem;color:#0284c7;"><?php echo $JwtData->GenSettings->CurrenySymbol; ?> <span class="taxBreakUpItemsSgstVal">0</span></div>
                            </div>
                            <div class="taxBreakUpItemsIgst d-none" style="background:#fff;border-radius:6px;padding:6px 8px;border:1px solid #e0f2fe;">
                                <div style="font-size:.6rem;color:#64748b;text-transform:uppercase;letter-spacing:.3px;margin-bottom:2px;">IGST</div>
                                <div style="font-weight:600;font-size:.82rem;color:#0284c7;"><?php echo $JwtData->GenSettings->CurrenySymbol; ?> <span class="taxBreakUpItemsIgstVal">0</span></div>
                            </div>
                            <div style="background:#fff;border-radius:6px;padding:6px 8px;border:1px solid #e0f2fe;">
                                <div style="font-size:.6rem;color:#64748b;text-transform:uppercase;letter-spacing:.3px;margin-bottom:2px;">Total Tax</div>
                                <div style="font-weight:700;font-size:.82rem;color:#0369a1;"><?php echo $JwtData->GenSettings->CurrenySymbol; ?> <span class="taxBreakUpItemsTotAmt">0</span></div>
                            </div>
                        </div>
                    </div>

                    <?php if ($_chargesBd): ?>
                    <!-- Charges Tax -->
                    <div class="d-none" id="chargeBreakUpTaxDetails" style="background:#f0f9ff;padding:8px 12px;border-top:1px solid #bae6fd;">
                        <div style="font-size:.65rem;font-weight:700;color:#0369a1;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Charges Tax</div>
                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:6px;">
                            <div class="taxBreakUpChargesCgst" style="background:#fff;border-radius:6px;padding:6px 8px;border:1px solid #e0f2fe;">
                                <div style="font-size:.6rem;color:#64748b;text-transform:uppercase;letter-spacing:.3px;margin-bottom:2px;">CGST</div>
                                <div style="font-weight:600;font-size:.82rem;color:#0284c7;"><?php echo $JwtData->GenSettings->CurrenySymbol; ?> <span class="taxBreakUpChargesCgstVal">0</span></div>
                            </div>
                            <div class="taxBreakUpChargesSgst" style="background:#fff;border-radius:6px;padding:6px 8px;border:1px solid #e0f2fe;">
                                <div style="font-size:.6rem;color:#64748b;text-transform:uppercase;letter-spacing:.3px;margin-bottom:2px;">SGST</div>
                                <div style="font-weight:600;font-size:.82rem;color:#0284c7;"><?php echo $JwtData->GenSettings->CurrenySymbol; ?> <span class="taxBreakUpChargesSgstVal">0</span></div>
                            </div>
                            <div class="taxBreakUpChargesIgst d-none" style="background:#fff;border-radius:6px;padding:6px 8px;border:1px solid #e0f2fe;">
                                <div style="font-size:.6rem;color:#64748b;text-transform:uppercase;letter-spacing:.3px;margin-bottom:2px;">IGST</div>
                                <div style="font-weight:600;font-size:.82rem;color:#0284c7;"><?php echo $JwtData->GenSettings->CurrenySymbol; ?> <span class="taxBreakUpChargesIgstVal">0</span></div>
                            </div>
                            <div style="background:#fff;border-radius:6px;padding:6px 8px;border:1px solid #e0f2fe;">
                                <div style="font-size:.6rem;color:#64748b;text-transform:uppercase;letter-spacing:.3px;margin-bottom:2px;">Total</div>
                                <div style="font-weight:700;font-size:.82rem;color:#0369a1;"><?php echo $JwtData->GenSettings->CurrenySymbol; ?> <span id="chargeTaxTotal">0</span></div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Grand Tax Total footer -->
                    <div class="d-flex align-items-center justify-content-between px-3 py-2" style="background:#0284c7;">
                        <span style="font-size:.75rem;font-weight:600;color:#e0f2fe;text-transform:uppercase;letter-spacing:.4px;">Grand Tax Total</span>
                        <span style="font-size:.92rem;font-weight:800;color:#fff;"><?php echo $JwtData->GenSettings->CurrenySymbol; ?> <span id="grandChargesTaxTotal">0</span></span>
                    </div>

                </div>
            </div>

            <div class="col-md-6 p-2 pe-5">
                <div class="row g-2">
                    <div class="d-flex align-items-center justify-content-end">
                        <div class="d-flex justify-content-end w-60 me-2">
                            <label class="form-label small fw-semibold">Extra Discount</label>
                        </div>
                        <div class="input-group input-group-merge w-70">
                            <select class="form-select form-select-sm" id="extDiscountType" name="extDiscountType">
                            </select>
                            <input class="form-control form-control-sm ps-1 w-30" type="text" id="extraDiscount" name="extraDiscount" min="0" step="0.01" placeholder="Extra Discount" onkeydown="return handleDotOnly(event)" oninput="this.value=this.value.slice(0,this.maxLength); validatePriceInput(this, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" maxlength="<?php echo $JwtData->GenSettings->PriceMaxLength; ?>" value="0">
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-end mt-2 d-none" id="shippingRow">
                        <div class="d-flex justify-content-end w-70"><label class="form-label small fw-semibold">Shipping Charges</label></div>
                        <div class="d-flex justify-content-end w-70 me-1"><span class="me-1"><?php echo $JwtData->GenSettings->CurrenySymbol; ?></span><span id="shippingChargeAmt"><?php echo smartDecimal(0, $JwtData->GenSettings->DecimalPoints, true); ?></span></div>
                    </div>
                    <div class="d-flex align-items-center justify-content-end mt-2 d-none" id="packingRow">
                        <div class="d-flex justify-content-end w-70"><label class="form-label small fw-semibold">Packaging Charges</label></div>
                        <div class="d-flex justify-content-end w-70 me-1"><span class="me-1"><?php echo $JwtData->GenSettings->CurrenySymbol; ?></span><span id="packingChargeAmt"><?php echo smartDecimal(0, $JwtData->GenSettings->DecimalPoints, true); ?></span></div>
                    </div>
                    <div class="d-flex align-items-center justify-content-end mt-2 d-none" id="handlingRow">
                        <div class="d-flex justify-content-end w-70"><label class="form-label small fw-semibold">Handling Charges</label></div>
                        <div class="d-flex justify-content-end w-70 me-1"><span class="me-1"><?php echo $JwtData->GenSettings->CurrenySymbol; ?></span><span id="handlingChargeAmt"><?php echo smartDecimal(0, $JwtData->GenSettings->DecimalPoints, true); ?></span></div>
                    </div>
                    <div class="d-flex align-items-center justify-content-end mt-2 d-none" id="othersRow">
                        <div class="d-flex justify-content-end w-70"><label class="form-label small fw-semibold">Other Charges</label></div>
                        <div class="d-flex justify-content-end w-70 me-1"><span class="me-1"><?php echo $JwtData->GenSettings->CurrenySymbol; ?></span><span id="othersChargeAmt"><?php echo smartDecimal(0, $JwtData->GenSettings->DecimalPoints, true); ?></span></div>
                    </div>
                    <div class="d-flex align-items-center justify-content-end mt-2 d-none">
                        <div class="d-flex justify-content-end w-70"><label class="form-label small fw-semibold">Taxable Amount</label></div>
                        <div class="d-flex justify-content-end w-70 me-1"><span class="me-1"><?php echo $JwtData->GenSettings->CurrenySymbol; ?></span><span class="bill_taxable_amt"><?php echo smartDecimal(0, $JwtData->GenSettings->DecimalPoints, true); ?></span></div>
                    </div>
                    <div class="d-flex align-items-center justify-content-end mt-2">
                        <div class="d-flex justify-content-end w-70">
                            <label class="form-label small fw-semibold">Total Tax <button type="button" class="btn btn-sm btn-link p-0 border-0" id="taxBreakupToggle"><i id="showHideTaxBreakUp" class="bx bxs-show tax-toggle-icon ms-1 fs-6 d-none"></i></button></label>
                        </div>
                        <div class="d-flex justify-content-end w-70 me-1"><span class="me-1"><?php echo $JwtData->GenSettings->CurrenySymbol; ?></span><span class="bill_tot_tax_amt"><?php echo smartDecimal(0, $JwtData->GenSettings->DecimalPoints, true); ?></span></div>
                    </div>
                    <div class="d-flex align-items-center justify-content-end mt-2">
                        <div class="d-flex justify-content-end w-70">
                            <div class="form-check form-switch mb-0">
                                <label class="form-check-label small fw-semibold" for="roundOffToggle">Round Off</label>
                                <input class="form-check-input" type="checkbox" name="roundOffToggle" id="roundOffToggle" checked />
                            </div>
                        </div>
                        <div class="d-flex justify-content-end w-70 me-1"><span class="me-1"><?php echo $JwtData->GenSettings->CurrenySymbol; ?></span><span class="bill_rndoff_amt"><?php echo smartDecimal(0, $JwtData->GenSettings->DecimalPoints, true); ?></span></div>
                    </div>
                    <div class="d-flex align-items-center justify-content-end mt-2">
                        <div class="d-flex justify-content-end w-70"><label class="form-label fw-semibold fs-4 text-primary">Total Amount</label></div>
                        <div class="d-flex justify-content-end w-70 me-1"><span class="me-1 fs-4 text-primary"><?php echo $JwtData->GenSettings->CurrenySymbol; ?></span><span class="bill_tot_amt fw-semibold fs-4 text-primary"><?php echo smartDecimal(0, $JwtData->GenSettings->DecimalPoints, true); ?></span></div>
                    </div>
                    <div class="d-flex align-items-center justify-content-end mt-2">
                        <div class="d-flex justify-content-end w-70"><label class="form-label small fw-semibold">Total Discount</label></div>
                        <div class="d-flex justify-content-end w-70 me-1"><span class="me-1"><?php echo $JwtData->GenSettings->CurrenySymbol; ?></span><span class="bill_tot_disc_amt"><?php echo smartDecimal(0, $JwtData->GenSettings->DecimalPoints, true); ?></span></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Section (Below Summary on Right Side) -->
        <?php if (!empty($_paymentVars)): ?>
        <div class="mt-4">
            <div class="card shadow-sm border-0" style="background: #f8f9fa;">
                <div class="card-body p-3">
                    <?php $this->load->view('transactions/partials/payment_section', $_paymentVars); ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!isset($transShowSignature) || $transShowSignature): ?>
        <?php $this->load->view('transactions/partials/form_signature', [
            'transSignatureUID'  => isset($transSignatureUID) ? (int)$transSignatureUID : 0,
            'transSignatures'    => isset($transSignatures) ? $transSignatures : null,
        ]); ?>
        <?php endif; ?>
    </div>

</div>

<!-- Combo BOM Breakdown Modal -->
<div class="modal fade" id="comboBOMModal" tabindex="-1" aria-labelledby="comboBOMModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-top">
        <div class="modal-content" style="border:none;box-shadow:0 20px 60px rgba(108,99,255,.14);">

            <!-- Header — brand accent left bar + icon -->
            <div class="modal-header py-3 pe-3" style="border-bottom:1px solid #e8e5ff;border-left:4px solid #7c3aed;background:linear-gradient(135deg,#faf8ff 0%,#fff 65%);">
                <div class="d-flex align-items-center gap-3 flex-grow-1">
                    <div style="width:40px;height:40px;border-radius:10px;background:#f0edff;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bx bx-package" style="color:#7c3aed;font-size:1.2rem;"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span style="font-size:.6rem;font-weight:700;letter-spacing:.5px;padding:2px 7px;border-radius:4px;background:#f0edff;color:#7c3aed;border:1px solid #d9d0ff;">COMBO</span>
                            <span id="comboBOMModalProductName" class="fw-bold" style="font-size:.94rem;color:#2d3748;"></span>
                        </div>
                        <div style="font-size:.72rem;color:#94a3b8;margin-top:2px;"><i class="bx bx-edit-alt me-1"></i>Edit component prices · totals update live</div>
                    </div>
                </div>
                <button type="button" class="btn-close ms-2" data-bs-dismiss="modal"></button>
            </div>

            <!-- Body -->
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0" id="comboBOMTable">
                        <thead>
                            <tr style="background:#f8f7ff;border-bottom:2px solid #e8e5ff;">
                                <th class="ps-4 py-3" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.55px;color:#64748b;font-weight:600;">Component</th>
                                <th class="text-center py-3" style="width:72px;font-size:.68rem;text-transform:uppercase;letter-spacing:.55px;color:#64748b;font-weight:600;">Qty</th>
                                <th class="py-3" style="width:180px;font-size:.68rem;text-transform:uppercase;letter-spacing:.55px;color:#64748b;font-weight:600;">Selling Price</th>
                                <th class="text-end pe-4 py-3" style="width:120px;font-size:.68rem;text-transform:uppercase;letter-spacing:.55px;color:#64748b;font-weight:600;">Amount</th>
                            </tr>
                        </thead>
                        <tbody id="comboBOMRows"></tbody>
                        <tfoot>
                            <tr style="background:#f3f0ff;border-top:2px solid #d9d0ff;">
                                <td colspan="3" class="ps-4 py-3" style="font-size:.88rem;font-weight:700;color:#7c3aed;">
                                    <i class="bx bx-calculator me-1" style="vertical-align:middle;"></i>Combo Total
                                </td>
                                <td class="text-end pe-4 py-3" style="font-size:1rem;font-weight:700;color:#7c3aed;" id="comboBOMTotal">0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="px-4 py-2 d-flex align-items-center gap-2" style="background:#fffbeb;border-top:1px solid #fde68a;font-size:.73rem;color:#92400e;">
                    <i class="bx bx-bulb" style="font-size:15px;flex-shrink:0;color:#d97706;"></i>
                    Change any component's selling price — the <strong>Combo Total</strong> updates instantly. Click <strong>Apply to Bill</strong> to save changes back to the bill row.
                </div>
            </div>

            <!-- Footer -->
            <div class="modal-footer py-2 px-3" style="border-top:1px solid #e2e8f0;background:#fafafa;">
                <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm px-4" id="comboBOMSubmitBtn">
                    <i class="bx bx-check me-1"></i>Apply to Bill
                </button>
            </div>

        </div>
    </div>
</div>
