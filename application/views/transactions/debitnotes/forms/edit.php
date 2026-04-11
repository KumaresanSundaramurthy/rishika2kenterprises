<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('common/transactions/header'); ?>
<div class="layout-wrapper transactionPage layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">
                <?php
                $isDraftEdit = ($DNData->DocStatus === 'Draft');
                $editPrefixConfig = null;
                if (!empty($PrefixData)) {
                    foreach ($PrefixData as $_pd) { if ((int)$_pd->PrefixUID === (int)$DNData->PrefixUID) { $editPrefixConfig = $_pd; break; } }
                    if (!$editPrefixConfig) $editPrefixConfig = $PrefixData[0];
                }
                function buildDNEditPrefixSegment($cfg) {
                    if (!$cfg) return ''; $sep = $cfg->Separator ?? '-'; $parts = [$cfg->Name];
                    if (!empty($cfg->IncludeShortName) && !empty($cfg->ShortName)) $parts[] = strtoupper($cfg->ShortName);
                    if (!empty($cfg->IncludeFiscalYear)) { $m = (int)date('m'); $yr = (int)date('Y'); $fy = $m >= 4 ? $yr : $yr - 1; $parts[] = ($cfg->FiscalYearFormat ?? 'SHORT') === 'LONG' ? $fy . '-' . ($fy + 1) : str_pad($fy % 100, 2, '0', STR_PAD_LEFT) . '-' . str_pad(($fy + 1) % 100, 2, '0', STR_PAD_LEFT); }
                    return implode($sep, $parts) . $sep;
                }
                $editTransNumber = $isDraftEdit ? (int)($NextNumberMap[(int)($editPrefixConfig->PrefixUID ?? 0)] ?? 1) : (int)$DNData->TransNumber;
                $editPrefixSeg   = $isDraftEdit ? buildDNEditPrefixSegment($editPrefixConfig) : '';
                $FormAttribute = ['id' => 'editDNForm', 'name' => 'editDNForm', 'autocomplete' => 'off', 'data-csrf' => $this->security->get_csrf_token_name(), 'data-csrf-value' => $this->security->get_csrf_hash()];
                echo form_open('debitnotes/updateDebitNote', $FormAttribute);
                ?>
                <input type="hidden" name="TransUID" value="<?php echo (int)$DNData->TransUID; ?>" />
                <div class="card mb-3">
                    <div class="card-header bg-body-tertiary trans-header-static trans-theme modal-header-center-sticky d-flex justify-content-between align-items-center pb-3">
                        <div class="d-flex flex-wrap align-items-center gap-3" id="transHeaderInfo">
                            <h5 class="modal-title mb-0 ms-2"><?php echo $isDraftEdit ? '' : 'Edit'; ?> Debit Note</h5>
                            <?php if (!$isDraftEdit && !empty($DNData->UniqueNumber)): ?><span class="badge bg-label-primary fs-6"><?php echo htmlspecialchars($DNData->UniqueNumber); ?></span><?php endif; ?>
                            <div class="d-flex align-items-center gap-1">
                                <div class="input-group w-auto <?php echo (!$isDraftEdit ? 'd-none' : ''); ?>">
                                    <select id="transPrefixSelect" name="transPrefixSelect" class="select2 form-select form-select-sm" <?php echo (!$isDraftEdit ? 'disabled' : 'required'); ?>>
                                    <?php foreach ($PrefixData as $preData): $isSelected = (int)$preData->PrefixUID === (int)$DNData->PrefixUID ? 'selected' : ''; ?>
                                        <option value="<?php echo (int)$preData->PrefixUID; ?>" data-sep="<?php echo htmlspecialchars($preData->Separator ?? '-'); ?>" data-fiscal="<?php echo !empty($preData->IncludeFiscalYear) ? '1' : '0'; ?>" data-fiscal-format="<?php echo htmlspecialchars($preData->FiscalYearFormat ?? 'SHORT'); ?>" data-inc-short="<?php echo !empty($preData->IncludeShortName) ? '1' : '0'; ?>" data-short-name="<?php echo htmlspecialchars($preData->ShortName ?? ''); ?>" data-padding="<?php echo (int)($preData->NumberPadding ?? 3); ?>" data-next-number="<?php echo (int)($NextNumberMap[(int)$preData->PrefixUID] ?? 1); ?>" <?php echo $isSelected; ?>><?php echo htmlspecialchars($preData->Name); ?></option>
                                    <?php endforeach; ?>
                                    </select>
                                    <?php if ($isDraftEdit): ?><button type="button" class="btn btn-outline-secondary" id="addTransPrefixBtn" title="Configure Prefix"><i class="bx bx-cog"></i></button><?php endif; ?>
                                </div>
                                <div class="input-group input-group-sm w-auto <?php echo (!$isDraftEdit ? 'd-none' : ''); ?>">
                                    <span class="input-group-text cursor-pointer fw-semibold text-primary" id="appendPrefixVal"><?php echo htmlspecialchars($editPrefixSeg); ?></span>
                                    <input type="number" id="transNumber" name="transNumber" class="form-control transAutoGenNumber stop-incre-indicator" maxLength="20" value="<?php echo $editTransNumber; ?>" <?php echo (!$isDraftEdit ? 'disabled' : 'required'); ?> />
                                </div>
                                <?php if (!$isDraftEdit): ?>
                                <input type="hidden" name="transPrefixSelect" value="<?php echo (int)$DNData->PrefixUID; ?>" />
                                <input type="hidden" name="transNumber" value="<?php echo (int)$DNData->TransNumber; ?>" />
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="submit" name="action" value="save" class="btn btn-primary"><?php echo $isDraftEdit ? 'Save' : 'Update'; ?></button>
                            <?php if ($isDraftEdit): ?><button type="submit" name="action" value="draft" class="btn btn-outline-secondary">Save as Draft</button><?php endif; ?>
                            <a href="/debitnotes" class="btn btn-label-danger">Close</a>
                        </div>
                    </div>
                    <div class="card-body card-body-form-static p-4">
                        <div class="card-header modal-header-center-sticky p-1 mb-3"><h5 class="modal-title mb-0"><i class="bx bx-building me-1"></i> Vendor Details</h5></div>
                        <div class="row">
                            <div class="col-md-6 border-end pe-3">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <label for="vendorSearch" class="form-label small fw-semibold">Select Vendor <span class="text-danger">*</span></label>
                                    <div class="flex-grow-1"><select id="vendorSearch" name="vendorSearch" class="form-select form-select-sm"></select></div>
                                </div>
                                <div id="vendorAddressBox" class="mt-2 p-2 border border-secondary trans-border-dotted rounded small d-none"></div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-2"><label for="transDate" class="form-label small fw-semibold">Debit Note Date <span class="text-danger">*</span></label><div class="input-group input-group-merge"><span class="input-group-text"><i class="icon-base bx bx-calendar"></i></span><input type="text" class="form-control form-control-sm" id="transDate" name="transDate" readonly="readonly" value="<?php echo htmlspecialchars(format_datedisplay($DNData->TransDate, 'Y-m-d')); ?>" required /></div></div>
                                <div><label for="referenceDetails" class="form-label small fw-semibold">Reference</label><input type="text" id="referenceDetails" name="referenceDetails" class="form-control form-control-sm" maxlength="100" value="<?php echo htmlspecialchars($DNData->Reference ?? ''); ?>" /></div>
                            </div>
                        </div>
                        <hr/>
                        <div class="card-header modal-header-center-sticky p-1 mb-3"><div class="d-flex align-items-center gap-2"><h5 class="modal-title mb-0"><i class="bx bx-cart-add me-1"></i> Products / Services</h5><button type="button" class="btn btn-sm btn-outline-primary" id="addTransProduct"><i class="bx bx-plus-circle me-1"></i> Product</button></div></div>
                        <div class="row">
                            <div class="card prod-header-static trans-theme p-2">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <div style="width:20%;"><select id="prodCategory" name="prodCategory" class="form-select form-select-sm"><option label="Select Category"></option><?php foreach ($fltCategoryData as $Catg): ?><option value="<?php echo $Catg->CategoryUID; ?>"><?php echo $Catg->Name; ?></option><?php endforeach; ?></select></div>
                                    <div style="width:35%;"><div class="input-group input-group-sm input-group-merge" id="searchProductGroup"><span class="input-group-text p-2"><i class="icon-base bx bx-search"></i></span><select id="searchProductInfo" name="searchProductInfo" class="form-select form-select-sm"><option label="-- Select Product --"></option></select><div class="transerror-tooltip" id="errSearchProd"><span class="icon">!</span>Please select an item.</div></div></div>
                                    <div style="width:10%;"><input type="text" class="form-control" name="prodQuantity" id="prodQuantity" placeholder="Quantity" onkeydown="return handleDotOnly(event)" oninput="this.value=this.value.slice(0,this.maxLength); validatePriceInput(this, <?php echo $JwtData->GenSettings->QtyMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" maxLength="<?php echo $JwtData->GenSettings->QtyMaxLength; ?>" /></div>
                                    <div style="width:10%;"><button type="button" class="btn btn-success w-100" id="transAddToCartForm"><i class="bx bx-cart-add"></i> Add to Bill</button></div>
                                </div>
                            </div>
                            <div class="card mb-5 p-0">
                                <div class="table-responsive">
                                    <table id="billTable" class="table trans-table table-bordered table-sm table-hover align-middle" data-update-delay="300">
                                        <thead class="table-light trans-table-light"><tr><th style="width:30%;">Product Name</th><th style="width:10%;">Quantity</th><th style="width:15%;">Unit Price</th><th style="width:15%;">Price with Tax</th><th style="width:15%;"><div class="d-flex align-items-center gap-1 justify-content-center"><span class="fw-semibold text-nowrap">Discount on</span><select class="form-select form-select-sm w-auto" id="discApplyFor" name="discApplyFor"><option value="TotalAmount" selected>Total Amount</option></select></div></th><th style="width:15%;"><div class="text-end fw-semibold">Total</div></th></tr></thead>
                                        <tbody id="billTableBody"></tbody>
                                        <tfoot class="table-light trans-table-light"><tr><td colspan="1" class="small fw-semibold">#Items: <span class="sumItemCount text-primary">0</span></td><td colspan="4" class="small fw-semibold">Qty: <span class="sumTotalQty text-primary">0</span></td><td colspan="1" class="small fw-semibold text-end">Net Total: <?php echo $JwtData->GenSettings->CurrenySymbol; ?><span class="sumNetTotal ms-1 text-primary">0</span></td></tr></tfoot>
                                    </table>
                                    <div class="row mt-1 m-1 p-2"><div class="d-flex align-items-center justify-content-between px-1">
                                        <div class="col-md-4"><label class="form-label fw-semibold mb-0">Apply Discount (%) to all items</label><div class="input-group input-group-merge input-group-sm mt-1 w-50"><input type="text" class="form-control form-control-sm" name="globalDiscount" id="globalDiscount" placeholder="Discount (%)" onkeydown="return handleDotOnly(event)" oninput="this.value=this.value.slice(0,this.maxLength); validatePriceInput(this, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" maxLength="<?php echo $JwtData->GenSettings->PriceMaxLength; ?>" value="0" /><button class="btn btn-sm btn-outline-danger" type="button" id="clearGlobalDiscount"><i class="bx bx-x"></i></button></div></div>
                                        <div class="row"><div class="d-flex align-items-center justify-content-end"><button id="toggleChargesBtn" class="btn btn-sm btn-outline-secondary"><i class="bx bx-plus-circle me-1"></i> Additional Charges</button></div>
                                        <div id="additionalChargesBox" class="mt-2 p-2 d-none"><div class="row g-2"><div class="col-md-12"><table class="table trans-table table-bordered border-primary rounded table-sm mb-0"><thead class="table-light trans-table-light"><tr><th>Charges</th><th>Tax</th><th>%</th><th>w/o Tax</th><th>w/ Tax</th></tr></thead><tbody>
                                        <tr><td>Shipping</td><td><select class="form-select form-select-sm additional-charge-tax" id="shippingCharges" name="shippingCharges" data-type="shipping" data-field="tax"><?php foreach ($TaxDetInfo as $TaxInfo): ?><option value="<?php echo $TaxInfo->TaxDetailsUID; ?>" data-percent="<?php echo smartDecimal($TaxInfo->Percentage); ?>" <?php echo smartDecimal($TaxInfo->Percentage) == 0 ? 'selected' : ''; ?>><?php echo smartDecimal($TaxInfo->Percentage); ?></option><?php endforeach; ?></select></td><td><input type="text" class="form-control form-control-sm additional-charge-percent" data-type="shipping" data-field="percent" name="shippingPercent" id="shippingPercent" maxLength="6" value="0" /></td><td><input type="text" class="form-control form-control-sm additional-charge-withouttax" data-type="shipping" data-field="withoutTax" name="shippingChargeWOutTax" id="shippingChargeWOutTax" maxLength="<?php echo $JwtData->GenSettings->PriceMaxLength; ?>" value="0" /></td><td><input type="text" class="form-control form-control-sm additional-charge-withtax" data-type="shipping" data-field="withTax" name="shippingChargeWithTax" id="shippingChargeWithTax" maxLength="<?php echo $JwtData->GenSettings->PriceMaxLength; ?>" value="0" /></td></tr>
                                        <tr><td>Packing</td><td><select class="form-select form-select-sm additional-charge-tax" id="packingCharges" name="packingCharges" data-type="packing" data-field="tax"><?php foreach ($TaxDetInfo as $TaxInfo): ?><option value="<?php echo $TaxInfo->TaxDetailsUID; ?>" data-percent="<?php echo smartDecimal($TaxInfo->Percentage); ?>" <?php echo smartDecimal($TaxInfo->Percentage) == 0 ? 'selected' : ''; ?>><?php echo smartDecimal($TaxInfo->Percentage); ?></option><?php endforeach; ?></select></td><td><input type="text" class="form-control form-control-sm additional-charge-percent" name="packingPercent" data-type="packing" data-field="percent" id="packingPercent" maxLength="6" value="0" /></td><td><input type="text" class="form-control form-control-sm additional-charge-withouttax" data-type="packing" data-field="withoutTax" name="packingChargeWOutTax" id="packingChargeWOutTax" maxLength="<?php echo $JwtData->GenSettings->PriceMaxLength; ?>" value="0" /></td><td><input type="text" class="form-control form-control-sm additional-charge-withtax" data-type="packing" data-field="withTax" name="packingChargeWithTax" id="packingChargeWithTax" maxLength="<?php echo $JwtData->GenSettings->PriceMaxLength; ?>" value="0" /></td></tr>
                                        </tbody></table></div></div></div></div>
                                    </div></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-2"><label for="transNotes" class="form-label small fw-semibold">Notes</label><textarea class="form-control" name="transNotes" id="transNotes" rows="2"><?php echo htmlspecialchars($DNData->Notes ?? ''); ?></textarea></div>
                                <div class="mb-2"><label for="transTermsCond" class="form-label small fw-semibold">Terms & Conditions</label><textarea class="form-control" name="transTermsCond" id="transTermsCond" rows="2"><?php echo htmlspecialchars($DNData->TermsConditions ?? ''); ?></textarea></div>
                            </div>
                            <div class="col-md-6 p-2 trans-theme" style="align-self:flex-start;">
                                <div class="row"><div class="col-md-6 tax-summary-section"><div id="taxBreakupPanel" style="display:none;" class="tax-details-view p-2 bg-light rounded border"><h6 class="tax-details-title mb-2">Tax Breakdown</h6><div class="mb-3"><p class="small fw-semibold mb-2 text-secondary">Items Tax</p><div class="table-responsive"><table class="table table-sm mb-0"><thead class="small bg-light"><tr><th class="fw-semibold border-bottom"># Items</th><th class="fw-semibold border-bottom taxBreakUpItemsCgst">CGST</th><th class="fw-semibold border-bottom taxBreakUpItemsSgst">SGST</th><th class="fw-semibold border-bottom taxBreakUpItemsIgst d-none">IGST</th><th class="fw-semibold border-bottom text-end">Total</th></tr></thead><tbody><tr><td class="py-1"><span class="taxBreakUpItemsCnt">0</span></td><td class="py-1 taxBreakUpItemsCgst"><?php echo $JwtData->GenSettings->CurrenySymbol; ?> <span class="taxBreakUpItemsCgstVal">0</span></td><td class="py-1 taxBreakUpItemsSgst"><?php echo $JwtData->GenSettings->CurrenySymbol; ?> <span class="taxBreakUpItemsSgstVal">0</span></td><td class="py-1 taxBreakUpItemsIgst d-none"><?php echo $JwtData->GenSettings->CurrenySymbol; ?> <span class="taxBreakUpItemsIgstVal">0</span></td><td class="py-1 text-end fw-semibold"><?php echo $JwtData->GenSettings->CurrenySymbol; ?> <span class="taxBreakUpItemsTotAmt">0</span></td></tr></tbody></table></div></div><div class="border-top pt-2"><table class="table table-sm mb-0"><tbody><tr><td class="fw-bold">Grand Tax Total</td><td class="text-end fw-bold"><?php echo $JwtData->GenSettings->CurrenySymbol; ?> <span id="grandChargesTaxTotal">0</span></td></tr></tbody></table></div></div></div>
                                <div class="col-md-6 p-2 pe-5"><div class="row g-2">
                                    <div class="d-flex align-items-center justify-content-end"><div class="d-flex justify-content-end w-60 me-2"><label class="form-label small fw-semibold">Extra Discount</label></div><div class="input-group input-group-merge w-70"><select class="form-select form-select-sm" id="extDiscountType" name="extDiscountType"><?php foreach ($DiscTypeInfo as $DiscType): ?><option value="<?php echo $DiscType->Name; ?>" <?php echo ($DNData->ExtraDiscountType ?? '') === $DiscType->Name ? 'selected' : ''; ?>><?php echo $DiscType->Symbol; ?></option><?php endforeach; ?></select><input class="form-control form-control-sm ps-1 w-30" type="text" id="extraDiscount" name="extraDiscount" onkeydown="return handleDotOnly(event)" oninput="this.value=this.value.slice(0,this.maxLength); validatePriceInput(this, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" maxlength="<?php echo $JwtData->GenSettings->PriceMaxLength; ?>" value="<?php echo smartDecimal($DNData->ExtraDiscount ?? 0); ?>"></div></div>
                                    <div class="d-flex align-items-center justify-content-end mt-2 d-none" id="shippingRow"><div class="d-flex justify-content-end w-70"><label class="form-label small fw-semibold">Shipping</label></div><div class="d-flex justify-content-end w-70 me-1"><span class="me-1"><?php echo $JwtData->GenSettings->CurrenySymbol; ?></span><span id="shippingChargeAmt">0</span></div></div>
                                    <div class="d-flex align-items-center justify-content-end mt-2 d-none" id="packingRow"><div class="d-flex justify-content-end w-70"><label class="form-label small fw-semibold">Packing</label></div><div class="d-flex justify-content-end w-70 me-1"><span class="me-1"><?php echo $JwtData->GenSettings->CurrenySymbol; ?></span><span id="packingChargeAmt">0</span></div></div>
                                    <div class="d-flex align-items-center justify-content-end mt-2 d-none" id="handlingRow"><div class="d-flex justify-content-end w-70"><label class="form-label small fw-semibold">Handling</label></div><div class="d-flex justify-content-end w-70 me-1"><span class="me-1"><?php echo $JwtData->GenSettings->CurrenySymbol; ?></span><span id="handlingChargeAmt">0</span></div></div>
                                    <div class="d-flex align-items-center justify-content-end mt-2 d-none" id="othersRow"><div class="d-flex justify-content-end w-70"><label class="form-label small fw-semibold">Others</label></div><div class="d-flex justify-content-end w-70 me-1"><span class="me-1"><?php echo $JwtData->GenSettings->CurrenySymbol; ?></span><span id="othersChargeAmt">0</span></div></div>
                                    <div class="d-flex align-items-center justify-content-end mt-2"><div class="d-flex justify-content-end w-70"><label class="form-label small fw-semibold">Taxable Amount</label></div><div class="d-flex justify-content-end w-70 me-1"><span class="me-1"><?php echo $JwtData->GenSettings->CurrenySymbol; ?></span><span class="bill_taxable_amt">0</span></div></div>
                                    <div class="d-flex align-items-center justify-content-end mt-2"><div class="d-flex justify-content-end w-70"><label class="form-label small fw-semibold">Total Tax <button type="button" class="btn btn-sm btn-link p-0 border-0" id="taxBreakupToggle"><i id="showHideTaxBreakUp" class="bx bxs-show tax-toggle-icon ms-1 fs-6 d-none"></i></button></label></div><div class="d-flex justify-content-end w-70 me-1"><span class="me-1"><?php echo $JwtData->GenSettings->CurrenySymbol; ?></span><span class="bill_tot_tax_amt">0</span></div></div>
                                    <div class="d-flex align-items-center justify-content-end mt-2"><div class="d-flex justify-content-end w-70"><div class="form-check form-switch mb-0"><label class="form-check-label small fw-semibold" for="roundOffToggle">Round Off</label><input class="form-check-input" type="checkbox" name="roundOffToggle" id="roundOffToggle" checked /></div></div><div class="d-flex justify-content-end w-70 me-1"><span class="me-1"><?php echo $JwtData->GenSettings->CurrenySymbol; ?></span><span class="bill_rndoff_amt">0</span></div></div>
                                    <div class="d-flex align-items-center justify-content-end mt-2"><div class="d-flex justify-content-end w-70"><label class="form-label fw-semibold fs-4 text-primary">Total Amount</label></div><div class="d-flex justify-content-end w-70 me-1"><span class="me-1 fs-4 text-primary"><?php echo $JwtData->GenSettings->CurrenySymbol; ?></span><span class="bill_tot_amt fw-semibold fs-4 text-primary">0</span></div></div>
                                    <div class="d-flex align-items-center justify-content-end mt-2"><div class="d-flex justify-content-end w-70"><label class="form-label small fw-semibold">Total Discount</label></div><div class="d-flex justify-content-end w-70 me-1"><span class="me-1"><?php echo $JwtData->GenSettings->CurrenySymbol; ?></span><span class="bill_tot_disc_amt">0</span></div></div>
                                </div></div></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php echo form_close(); ?>
                </div>
            </div>
            <?php $this->load->view('common/transactions/transprefix'); ?>
            <?php $this->load->view('transactions/modals/taxdetails'); ?>
            <?php $this->load->view('products/modals/items'); ?>
            <?php $this->load->view('common/footer_desc'); ?>
        </div>
    </div>
</div>
<?php $this->load->view('common/transactions/footer'); ?>
<script src="/js/transactions/debitnotes.js"></script>
<script src="/js/transactions/transactions.js"></script>
<script src="/js/transactions/transprefix.js"></script>
<script src="/js/transactions/modaladdress.js"></script>
<script src="/js/transactions/products.js"></script>
<script src="/js/combinemodules/products.js"></script>
<script>
const StateInfo     = <?php echo json_encode($StateData); ?>;
const CityInfo      = <?php echo json_encode($CityData); ?>;
const EnableStorage = <?php echo $JwtData->GenSettings->EnableStorage; ?>;
var _editItems = <?php echo json_encode(array_map(function($item) { return ['id' => (int)$item->ProductUID,'text' => $item->ProductName,'itemName' => $item->ProductName,'unitPrice' => (float)$item->UnitPrice,'taxAmount' => (float)$item->TaxAmount,'sellingPrice' => (float)$item->SellingPrice,'purchasePrice' => 0,'availableQuantity' => 0,'hsnCode' => '','categoryUID' => $item->CategoryUID ? (int)$item->CategoryUID : null,'storageUID' => $item->StorageUID ? (int)$item->StorageUID : null,'taxPercent' => (float)$item->TaxPercentage,'cgstPercent' => (float)$item->CGST,'sgstPercent' => (float)$item->SGST,'igstPercent' => (float)$item->IGST,'taxDetailsUID' => (int)$item->TaxDetailsUID,'quantity' => (float)$item->Quantity,'partNumber' => $item->PartNumber ?? '','primaryUnit' => $item->PrimaryUnitName ?? '','discount' => (float)$item->Discount,'discountType' => 'Percentage','discountTypeUID' => $item->DiscountTypeUID ? (int)$item->DiscountTypeUID : null,'discount_amount' => (float)$item->DiscountAmount,'line_total' => (float)$item->TaxableAmount,'net_total' => (float)$item->NetAmount]; }, $DNItems)); ?>;
$(function() {
    'use strict'
    searchVendors('vendorSearch');
    <?php if (!empty($DNData->PartyUID)): ?>
    $('#vendorSearch').append(new Option('<?php echo addslashes($DNData->PartyName ?? ''); ?>', <?php echo (int)$DNData->PartyUID; ?>, true, true)).trigger('change');
    <?php endif; ?>
    transDatePickr('#transDate', false, 'Y-m-d', false, true, true, true, 'd-m-Y');
    if (typeof billManager !== 'undefined' && typeof formationTableBillItems === 'function' && _editItems.length > 0) {
        $(document).one('billmanager:ready', function() { formationTableBillItems(_editItems); });
    }
    var $form = $('#editDNForm');
    if ($form.length) {
        $form.on('submit', function(e) {
            e.preventDefault();
            var $btn = $('button[type="submit"][name="action"]:focus, button[type="submit"][name="action"].active-submit', $form);
            var action = $btn.val() || 'save';
            var csrfName = $form.data('csrf'); var csrfVal = $form.data('csrf-value');
            var vendorUID = parseInt($('#vendorSearch').val(), 10);
            if (!vendorUID || vendorUID <= 0) return showFormError('Please select a vendor.');
            var transDate = $.trim($('#transDate').val());
            if (!transDate || !/^\d{4}-\d{2}-\d{2}$/.test(transDate)) return showFormError('Please enter a valid date.');
            var items = typeof billManager !== 'undefined' ? billManager.getAllItems() : [];
            if (!items || items.length === 0) return showFormError('Please add at least one product.');
            var bm = typeof billManager !== 'undefined' ? billManager : null;
            var summary = bm ? bm.summary : {};
            var charges = {};
            if (summary.additionalCharges) { ['shipping','handling','packing','other'].forEach(function(t) { var c = summary.additionalCharges[t]; if (c && c.grossAmount > 0) { charges[t+'Amount'] = c.grossAmount; charges[t+'Tax'] = c.taxPercent || 0; } }); }
            var postData = $.extend({ TransUID: parseInt($('input[name="TransUID"]').val(),10), transPrefixSelect: parseInt($('#transPrefixSelect').val(),10)||0, transNumber: $.trim($('#transNumber').val()), transDate: transDate, vendorSearch: vendorUID, referenceDetails: $.trim($('#referenceDetails').val()), transNotes: $.trim($('#transNotes').val()), transTermsCond: $.trim($('#transTermsCond').val()), extraDiscount: parseFloat($('#extraDiscount').val())||0, extDiscountType: $('#extDiscountType').val()||'', SubTotal: summary.items?(summary.items.taxableAmount||0):0, DiscountAmount: summary.items?(summary.items.discountTotal||0):0, TaxAmount: summary.taxTotals?(summary.taxTotals.totalTax||0):0, CgstAmount: summary.taxTotals?(summary.taxTotals.cgstTotal||0):0, SgstAmount: summary.taxTotals?(summary.taxTotals.sgstTotal||0):0, IgstAmount: summary.taxTotals?(summary.taxTotals.igstTotal||0):0, AdditionalChargesTotal: (summary.additionalCharges&&summary.additionalCharges.total)?(summary.additionalCharges.total.grossAmount||0):0, GlobalDiscPercent: bm?(bm.globalDiscountPercent||0):0, RoundOff: summary.extra?(summary.extra.roundOff||0):0, NetAmount: summary.totals?(summary.totals.grandTotal||0):0, Items: JSON.stringify(items), action: action, [csrfName]: csrfVal }, charges);
            setFormLoading('#editDNForm', true, action);
            $.ajax({ url: '/debitnotes/updateDebitNote', method: 'POST', data: postData, cache: false,
                success: function(response) { if (response.Error) { setFormLoading('#editDNForm', false); showFormError(response.Message); } else { Swal.fire({ icon: 'success', title: 'Debit Note Updated', text: response.Message||'Updated successfully.', confirmButtonText: 'OK', timer: 3000, timerProgressBar: true }).then(function() { window.location.href = '/debitnotes'; }); } },
                error: function() { setFormLoading('#editDNForm', false); showFormError('Server error. Please try again.'); }
            });
        });
        $form.on('click', 'button[type="submit"][name="action"]', function() { $form.find('button[type="submit"][name="action"]').removeClass('active-submit'); $(this).addClass('active-submit'); });
    }
});
</script>
