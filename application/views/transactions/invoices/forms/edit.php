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

            <?php
            $isDraftEdit = ($InvData->DocStatus === 'Draft');

            $editPrefixConfig = null;
            if (!empty($PrefixData)) {
                foreach ($PrefixData as $_pd) {
                    if ((int) $_pd->PrefixUID === (int) $InvData->PrefixUID) {
                        $editPrefixConfig = $_pd;
                        break;
                    }
                }
                if (!$editPrefixConfig) $editPrefixConfig = $PrefixData[0];
            }

            function buildInvEditPrefixSegment($cfg) {
                if (!$cfg) return '';
                $sep    = $cfg->Separator ?? '-';
                $parts  = [$cfg->Name];
                if (!empty($cfg->IncludeShortName) && !empty($cfg->ShortName)) {
                    $parts[] = strtoupper($cfg->ShortName);
                }
                if (!empty($cfg->IncludeFiscalYear)) {
                    $m  = (int)date('m');
                    $yr = (int)date('Y');
                    $fy = $m >= 4 ? $yr : $yr - 1;
                    $parts[] = ($cfg->FiscalYearFormat ?? 'SHORT') === 'LONG'
                        ? $fy . '-' . ($fy + 1)
                        : str_pad($fy % 100, 2, '0', STR_PAD_LEFT) . '-' . str_pad(($fy + 1) % 100, 2, '0', STR_PAD_LEFT);
                }
                return implode($sep, $parts) . $sep;
            }

            $editTransNumber = $isDraftEdit ? (int)($NextNumberMap[(int)($editPrefixConfig->PrefixUID ?? 0)] ?? 1) : (int)$InvData->TransNumber;
            $editPrefixSeg   = $isDraftEdit ? buildInvEditPrefixSegment($editPrefixConfig) : '';

            $FormAttribute = ['id' => 'editInvForm', 'name' => 'editInvForm', 'autocomplete' => 'off', 'data-csrf' => $this->security->get_csrf_token_name(), 'data-csrf-value' => $this->security->get_csrf_hash()];
                echo form_open('invoices/updateInvoice', $FormAttribute);
            ?>

                <input type="hidden" name="TransUID" value="<?php echo (int)$InvData->TransUID; ?>" />

                    <div class="card mb-3">

                        <div class="card-header bg-body-tertiary trans-header-static trans-theme modal-header-center-sticky d-flex justify-content-between align-items-center pb-3">
                            <div class="d-flex flex-wrap align-items-center gap-3" id="transHeaderInfo">
                                <h5 class="modal-title mb-0 ms-2"><?php echo $isDraftEdit ? '' : 'Edit'; ?> Invoice</h5>
                                <?php if (!$isDraftEdit && !empty($InvData->UniqueNumber)): ?>
                                    <span class="badge bg-label-primary fs-6"><?php echo htmlspecialchars($InvData->UniqueNumber); ?></span>
                                <?php endif; ?>
                                <div class="d-flex align-items-center gap-1">
                                    <div class="input-group w-auto <?php echo (!$isDraftEdit ? 'd-none' : ''); ?>">
                                        <select id="transPrefixSelect" name="transPrefixSelect" class="select2 form-select form-select-sm" <?php echo (!$isDraftEdit ? 'disabled' : 'required'); ?>>
                                    <?php try {
                                            if (empty($PrefixData)) throw new Exception('Prefix data not loaded');
                                            foreach ($PrefixData as $preData) {
                                                $isSelected = (int)$preData->PrefixUID === (int)$InvData->PrefixUID ? 'selected' : '';
                                            ?>
                                            <option value="<?php echo (int)$preData->PrefixUID; ?>"
                                                data-sep="<?php echo htmlspecialchars($preData->Separator ?? '-'); ?>"
                                                data-fiscal="<?php echo !empty($preData->IncludeFiscalYear) ? '1' : '0'; ?>"
                                                data-fiscal-format="<?php echo htmlspecialchars($preData->FiscalYearFormat ?? 'SHORT'); ?>"
                                                data-inc-short="<?php echo !empty($preData->IncludeShortName) ? '1' : '0'; ?>"
                                                data-short-name="<?php echo htmlspecialchars($preData->ShortName ?? ''); ?>"
                                                data-padding="<?php echo (int)($preData->NumberPadding ?? 3); ?>"
                                                data-next-number="<?php echo (int)($NextNumberMap[(int)$preData->PrefixUID] ?? 1); ?>"
                                                <?php echo $isSelected; ?>
                                            ><?php echo htmlspecialchars($preData->Name); ?></option>
                                        <?php }
                                        } catch (Exception $e) { ?>
                                            <option value="">Error loading prefixes</option>
                                        <?php } ?>
                                        </select>
                                        <?php if ($isDraftEdit): ?>
                                        <button type="button" class="btn btn-outline-secondary" id="addTransPrefixBtn" title="Configure Prefix"><i class="bx bx-cog"></i></button>
                                        <?php endif; ?>
                                    </div>
                                    <div class="input-group input-group-sm w-auto <?php echo (!$isDraftEdit ? 'd-none' : ''); ?>">
                                        <span class="input-group-text cursor-pointer fw-semibold text-primary" id="appendPrefixVal"><?php echo htmlspecialchars($editPrefixSeg); ?></span>
                                        <input type="number" id="transNumber" name="transNumber" class="form-control transAutoGenNumber stop-incre-indicator" maxLength="20" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength)" pattern="[0-9]*" value="<?php echo $editTransNumber; ?>" <?php echo (!$isDraftEdit ? 'disabled' : 'required'); ?> />
                                    </div>
                                    <?php if (!$isDraftEdit): ?>
                                    <input type="hidden" name="transPrefixSelect" value="<?php echo (int)$InvData->PrefixUID; ?>" />
                                    <input type="hidden" name="transNumber" value="<?php echo (int)$InvData->TransNumber; ?>" />
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="submit" name="action" value="save" class="btn btn-primary"><?php echo $isDraftEdit ? 'Save' : 'Update'; ?></button>
                                <?php if ($isDraftEdit): ?>
                                <button type="submit" name="action" value="draft" class="btn btn-outline-secondary">Save as Draft</button>
                                <?php endif; ?>
                                <a href="/invoices" class="btn btn-label-danger">Close</a>
                            </div>
                        </div>

                        <div class="card-body card-body-form-static p-4">

                            <div class="card-header modal-header-center-sticky p-1 mb-3">
                                <h5 class="modal-title mb-0"><i class="bx bx-user me-1"></i> Customer Details</h5>
                            </div>
                            <div class="row">
                                <div class="col-md-3 trans-right-border">
                                    <div class="mb-2">
                                        <label for="invoiceType" class="form-label small fw-semibold">Type <span style="color:red">*</span></label>
                                        <select id="invoiceType" name="invoiceType" class="form-select form-select-sm">
                                            <option value="Regular" <?php echo ($InvData->QuotationType === 'Regular' || empty($InvData->QuotationType)) ? 'selected' : ''; ?>>Regular</option>
                                            <option value="Without_GST" <?php echo $InvData->QuotationType === 'Without_GST' ? 'selected' : ''; ?>>Without GST</option>
                                        </select>
                                    </div>
                                    <?php if (!empty($DispatchAddress)): ?>
                                        <?php
                                            $addrLines = array_filter([
                                                htmlspecialchars($DispatchAddress->Line1 ?? ''),
                                                htmlspecialchars($DispatchAddress->Line2 ?? ''),
                                            ]);
                                            $cityPin = trim(implode(' - ', array_filter([
                                                htmlspecialchars($DispatchAddress->CityText ?? ''),
                                                htmlspecialchars($DispatchAddress->Pincode  ?? ''),
                                            ])));
                                            if ($cityPin) $addrLines[] = $cityPin;
                                            if (!empty($DispatchAddress->StateText)) $addrLines[] = htmlspecialchars($DispatchAddress->StateText);
                                        ?>
                                    <div class="mb-2">
                                        <label class="form-label small fw-semibold">Dispatch From <span style="color:red">*</span></label>
                                        <select id="dispatchFrom" name="dispatchFrom" class="form-select form-select-sm" required>
                                            <option value="<?php echo (int)$DispatchAddress->OrgAddressUID; ?>" selected><?php echo implode(', ', $addrLines); ?></option>
                                        </select>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6 border-end pe-3">
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <label for="customerSearch" class="form-label small fw-semibold">Select Customer <span class="text-danger">*</span></label>
                                        </div>
                                        <div class="flex-grow-1">
                                            <select id="customerSearch" name="customerSearch" class="form-select form-select-sm"></select>
                                        </div>
                                    </div>
                                    <div id="customerAddressBox" class="mt-2 p-2 border border-secondary trans-border-dotted rounded small d-none"></div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-2">
                                        <label for="transDate" class="form-label small fw-semibold">Invoice Date <span class="text-danger">*</span></label>
                                        <div class="input-group input-group-merge">
                                            <span class="input-group-text"><i class="icon-base bx bx-calendar"></i></span>
                                            <input type="text" class="form-control form-control-sm" id="transDate" name="transDate" readonly="readonly" value="<?php echo htmlspecialchars(format_datedisplay($InvData->TransDate, 'Y-m-d')); ?>" required />
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <label for="dueDate" class="form-label small fw-semibold">Due Date</label>
                                        <div class="input-group input-group-merge">
                                            <span class="input-group-text"><i class="icon-base bx bx-calendar"></i></span>
                                            <input type="text" class="form-control form-control-sm" id="dueDate" name="dueDate" readonly="readonly"
                                                value="<?php echo !empty($InvData->ValidityDate) ? htmlspecialchars(format_datedisplay($InvData->ValidityDate, 'Y-m-d')) : ''; ?>" />
                                        </div>
                                    </div>
                                    <div>
                                        <label for="referenceDetails" class="form-label small fw-semibold">Reference</label>
                                        <input type="text" id="referenceDetails" name="referenceDetails" class="form-control form-control-sm" placeholder="PO Number, Ref No..." maxlength="100"
                                            value="<?php echo htmlspecialchars($InvData->Reference ?? ''); ?>" />
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
                                        <div style="width:20%;">
                                            <select id="prodCategory" name="prodCategory" class="form-select form-select-sm">
                                                <option label="Select Category"></option>
                                            <?php foreach ($fltCategoryData as $Catg) { ?>
                                                <option value="<?php echo $Catg->CategoryUID; ?>"><?php echo $Catg->Name; ?></option>
                                            <?php } ?>
                                            </select>
                                        </div>
                                        <div style="width:35%;">
                                            <div class="input-group input-group-sm input-group-merge" id="searchProductGroup">
                                                <span class="input-group-text p-2"><i class="icon-base bx bx-search"></i></span>
                                                <select id="searchProductInfo" name="searchProductInfo" class="form-select form-select-sm">
                                                    <option label="-- Select Product --"></option>
                                                </select>
                                                <div class="transerror-tooltip" id="errSearchProd"><span class="icon">!</span>Please select an item in the list.</div>
                                            </div>
                                        </div>
                                        <div style="width:10%;">
                                            <input type="text" class="form-control" name="prodQuantity" id="prodQuantity" min="0" step="1" placeholder="Quantity" onkeydown="return handleDotOnly(event)" oninput="this.value=this.value.slice(0,this.maxLength); validatePriceInput(this, <?php echo $JwtData->GenSettings->QtyMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" maxLength="<?php echo $JwtData->GenSettings->QtyMaxLength; ?>" />
                                        </div>
                                        <div style="width:10%;">
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
                                                    <th style="width:15%;">Price with Tax</th>
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
                                                        <div class="text-end"><div class="fw-semibold">Total</div><div class="transtext-small text-muted">Net Amount + Tax</div></div>
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody id="billTableBody">
                                                <tr class="text-center text-muted">
                                                    <td colspan="6"><div class="py-4"><i class="bx bx-cart text-primary" style="font-size:2rem;"></i><p class="mt-2 mb-0">No items added yet</p></div></td>
                                                </tr>
                                            </tbody>
                                            <tfoot class="table-light trans-table-light">
                                                <tr>
                                                    <td colspan="1" class="small fw-semibold">#Items: <span class="sumItemCount text-primary">0</span></td>
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
                                                        <input type="text" class="form-control form-control-sm" name="globalDiscount" id="globalDiscount" min="0" step="0.01" max="50" placeholder="Discount (%)" onkeydown="return handleDotOnly(event)" oninput="this.value=this.value.slice(0,this.maxLength); validatePriceInput(this, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" maxLength="<?php echo $JwtData->GenSettings->PriceMaxLength; ?>" value="<?php echo smartDecimal($InvData->GlobalDiscPercent ?? 0); ?>" />
                                                        <button class="btn btn-sm btn-outline-danger" type="button" id="clearGlobalDiscount"><i class="bx bx-x"></i></button>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="d-flex align-items-center justify-content-end">
                                                        <button id="toggleChargesBtn" class="btn btn-sm btn-outline-secondary"><i class="bx bx-plus-circle me-1"></i> Additional Charges</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Notes & Terms -->
                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <label for="transNotes" class="form-label small fw-semibold">Notes</label>
                                        <textarea class="form-control" name="transNotes" id="transNotes" rows="2"><?php echo htmlspecialchars($InvData->Notes ?? ''); ?></textarea>
                                    </div>
                                    <div class="mb-2">
                                        <label for="transTermsCond" class="form-label small fw-semibold">Terms & Conditions</label>
                                        <textarea class="form-control" name="transTermsCond" id="transTermsCond" rows="2"><?php echo htmlspecialchars($InvData->TermsConditions ?? ''); ?></textarea>
                                    </div>
                                </div>

                                <!-- Summary -->
                                <div class="col-md-6 p-2 trans-theme" style="align-self:flex-start;">
                                    <div class="row">
                                        <div class="col-md-6 p-2 pe-5">
                                            <div class="row g-2">
                                                <div class="d-flex align-items-center justify-content-end">
                                                    <div class="d-flex justify-content-end w-60 me-2"><label class="form-label small fw-semibold">Extra Discount (%)</label></div>
                                                    <div class="input-group input-group-merge w-70">
                                                        <select class="form-select form-select-sm" id="extDiscountType" name="extDiscountType">
                                                        <?php foreach ($DiscTypeInfo as $DiscType) { ?>
                                                            <option value="<?php echo $DiscType->Name; ?>" <?php echo ($InvData->ExtraDiscType === $DiscType->Name) ? 'selected' : ''; ?>><?php echo $DiscType->Symbol; ?></option>
                                                        <?php } ?>
                                                        </select>
                                                        <input class="form-control form-control-sm ps-1 w-30" type="text" id="extraDiscount" name="extraDiscount" min="0" step="0.01" placeholder="Extra Discount" onkeydown="return handleDotOnly(event)" oninput="this.value=this.value.slice(0,this.maxLength); validatePriceInput(this, <?php echo $JwtData->GenSettings->PriceMaxLength; ?>, <?php echo $JwtData->GenSettings->DecimalPoints; ?>)" maxlength="<?php echo $JwtData->GenSettings->PriceMaxLength; ?>" value="<?php echo smartDecimal($InvData->ExtraDiscAmount ?? 0); ?>">
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

                                                <div class="d-flex align-items-center justify-content-end mt-2">
                                                    <div class="d-flex justify-content-end w-70"><label class="form-label small fw-semibold">Taxable Amount</label></div>
                                                    <div class="d-flex justify-content-end w-70 me-1"><span class="me-1"><?php echo $JwtData->GenSettings->CurrenySymbol; ?></span><span class="bill_taxable_amt"><?php echo smartDecimal(0, $JwtData->GenSettings->DecimalPoints, true); ?></span></div>
                                                </div>
                                                <div class="d-flex align-items-center justify-content-end mt-2">
                                                    <div class="d-flex justify-content-end w-70"><label class="form-label small fw-semibold">Total Tax</label></div>
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
                                </div>

                            </div>

                        <?php
                            $paymentPartyType = 'C';
                            $this->load->view('transactions/partials/payment_section', [
                                'PaymentTypes'     => $PaymentTypes ?? [],
                                'BankAccounts'     => $BankAccounts ?? [],
                                'JwtData'          => $JwtData,
                                'paymentPartyType' => $paymentPartyType,
                            ]);
                        ?>

                        </div> <!-- /card-body -->
                    </div> <!-- /card -->

                    <?php echo form_close(); ?>

                </div>
            </div>

            <?php $this->load->view('common/transactions/transprefix'); ?>
            <?php $this->load->view('transactions/modals/customer'); ?>
            <?php $this->load->view('transactions/modals/taxdetails'); ?>
            <?php $this->load->view('products/modals/items'); ?>
            <?php $this->load->view('common/footer_desc'); ?>

        </div>

    </div>
</div>

<?php $this->load->view('common/transactions/footer'); ?>

<script src="/js/transactions/invoices.js"></script>
<script src="/js/transactions/transactions.js"></script>
<script src="/js/transactions/transprefix.js"></script>
<script src="/js/transactions/modaladdress.js"></script>
<script src="/js/transactions/products.js"></script>
<script src="/js/combinemodules/products.js"></script>
<script src="/js/transactions/payment_section.js"></script>

<script>
const StateInfo     = <?php echo json_encode($StateData); ?>;
const CityInfo      = <?php echo json_encode($CityData); ?>;
const EnableStorage = <?php echo $JwtData->GenSettings->EnableStorage; ?>;
var _orgState       = '<?php echo addslashes($DispatchAddress->StateText ?? ''); ?>';
var _custState      = '<?php echo addslashes($CustAddr->StateText ?? ''); ?>';

var _editItems = <?php echo json_encode(array_map(function($item) {
    return [
        'id'               => (int)  $item->ProductUID,
        'text'             => $item->ProductName,
        'itemName'         => $item->ProductName,
        'unitPrice'        => (float)$item->UnitPrice,
        'taxAmount'        => (float)$item->TaxAmount,
        'sellingPrice'     => (float)$item->SellingPrice,
        'purchasePrice'    => 0,
        'availableQuantity'=> 0,
        'hsnCode'          => '',
        'categoryUID'      => $item->CategoryUID ? (int)$item->CategoryUID : null,
        'storageUID'       => $item->StorageUID  ? (int)$item->StorageUID  : null,
        'taxPercent'       => (float)$item->TaxPercentage,
        'cgstPercent'      => (float)$item->CGST,
        'sgstPercent'      => (float)$item->SGST,
        'igstPercent'      => (float)$item->IGST,
        'taxDetailsUID'    => (int)  $item->TaxDetailsUID,
        'quantity'         => (float)$item->Quantity,
        'partNumber'       => $item->PartNumber      ?? '',
        'primaryUnit'      => $item->PrimaryUnitName ?? '',
        'discount'         => (float)$item->Discount,
        'discountType'     => 'Percentage',
        'discountTypeUID'  => $item->DiscountTypeUID ? (int)$item->DiscountTypeUID : null,
        'discount_amount'  => (float)$item->DiscountAmount,
        'line_total'       => (float)$item->TaxableAmount,
        'net_total'        => (float)$item->NetAmount,
    ];
}, $InvItems)); ?>;

$(function () {
    'use strict'

    searchCustomers('customerSearch');

    // Pre-load existing customer
    <?php if (!empty($InvData->PartyUID)): ?>
    $('#customerSearch').append(new Option(
        '<?php echo addslashes($InvData->PartyName ?? ''); ?>',
        <?php echo (int)$InvData->PartyUID; ?>, true, true
    )).trigger('change');
    <?php endif; ?>

    transDatePickr('#transDate', false, 'Y-m-d', false, true, true, true, 'd-m-Y');
    transDatePickr('#dueDate', false, 'Y-m-d', false, false, false, true, 'd-m-Y', '#transDate');

    if (typeof billManager !== 'undefined' && _orgState && _custState) {
        billManager.isInterState = (_custState.trim().toLowerCase() !== _orgState.trim().toLowerCase());
    }

    if (typeof billManager !== 'undefined' && typeof formationTableBillItems === 'function'
            && Array.isArray(_editItems) && _editItems.length > 0) {
        $('#billTableBody').empty();
        _editItems.forEach(function(item) {
            var added = billManager.addItem(item, item.quantity);
            if (added !== false) {
                formationTableBillItems(billManager.getItemById(item.id));
            }
        });
        if (typeof updateItemTaxBreakdown === 'function') updateItemTaxBreakdown();
        billManager.updateSummary();
    }

    // ── Edit Invoice form submit ──────────────────────────
    var $form = $('#editInvForm');
    if ($form.length) {

        $form.on('submit', function(e) {
            e.preventDefault();

            var $btn     = $('button[type="submit"][name="action"]:focus, button[type="submit"][name="action"].active-submit', $form);
            var action   = $btn.val() || 'save';
            var csrfName = $form.data('csrf');
            var csrfVal  = $form.data('csrf-value');

            var customerUID = parseInt($('#customerSearch').val(), 10);
            if (!customerUID || customerUID <= 0) return showFormError('Please select a customer.');

            var transDate = $.trim($('#transDate').val());
            if (!transDate || !/^\d{4}-\d{2}-\d{2}$/.test(transDate)) return showFormError('Please enter a valid invoice date.');

            var items = typeof billManager !== 'undefined' ? billManager.getAllItems() : [];
            if (!items || items.length === 0) return showFormError('Please add at least one product.');

            var bm            = typeof billManager !== 'undefined' ? billManager : null;
            var summary       = bm ? bm.summary : {};
            var netAmount     = summary.totals    ? (summary.totals.grandTotal    || 0) : 0;
            var subTotal      = summary.items     ? (summary.items.taxableAmount  || 0) : 0;
            var discountAmt   = summary.items     ? (summary.items.discountTotal  || 0) : 0;
            var taxAmt        = summary.taxTotals ? (summary.taxTotals.totalTax   || 0) : 0;
            var cgstAmt       = summary.taxTotals ? (summary.taxTotals.cgstTotal  || 0) : 0;
            var sgstAmt       = summary.taxTotals ? (summary.taxTotals.sgstTotal  || 0) : 0;
            var igstAmt       = summary.taxTotals ? (summary.taxTotals.igstTotal  || 0) : 0;
            var addCharges    = (summary.additionalCharges && summary.additionalCharges.total) ? (summary.additionalCharges.total.grossAmount || 0) : 0;
            var globalDiscPct = bm ? (bm.globalDiscountPercent || 0) : 0;
            var roundOff      = summary.extra     ? (summary.extra.roundOff       || 0) : 0;
            var extraDisc     = parseFloat($('#extraDiscount').val()) || 0;

            var charges = {};
            if (summary.additionalCharges) {
                ['shipping', 'handling', 'packing', 'other'].forEach(function(t) {
                    var c = summary.additionalCharges[t];
                    if (c && c.grossAmount > 0) {
                        charges[t + 'Amount'] = c.grossAmount;
                        charges[t + 'Tax']    = c.taxPercent || 0;
                    }
                });
            }

            // Serialize payment rows (skip for draft)
            if (action !== 'draft') {
                if (!serializePaymentRows()) return showFormError('Please enter a valid amount for every payment row.');
            }

            var postData = $.extend({
                TransUID               : parseInt($('input[name="TransUID"]').val(), 10),
                transPrefixSelect      : parseInt($('#transPrefixSelect').val(), 10) || 0,
                transNumber            : $.trim($('#transNumber').val()),
                transDate              : transDate,
                dueDate                : $.trim($('#dueDate').val()),
                customerSearch         : customerUID,
                invoiceType            : $('#invoiceType').val() || '',
                dispatchFrom           : $('#dispatchFrom').val() || '',
                referenceDetails       : $.trim($('#referenceDetails').val()),
                transNotes             : $.trim($('#transNotes').val()),
                transTermsCond         : $.trim($('#transTermsCond').val()),
                extraDiscount          : extraDisc,
                extDiscountType        : $('#extDiscountType').val() || '',
                SubTotal               : subTotal,
                DiscountAmount         : discountAmt,
                TaxAmount              : taxAmt,
                CgstAmount             : cgstAmt,
                SgstAmount             : sgstAmt,
                IgstAmount             : igstAmt,
                AdditionalChargesTotal : addCharges,
                GlobalDiscPercent      : globalDiscPct,
                RoundOff               : roundOff,
                NetAmount              : netAmount,
                Items                  : JSON.stringify(items),
                action                 : action,
                PaymentRows            : $('#PaymentRowsJson').val(),
                IsFullyPaid            : $('#isFullyPaid').is(':checked') ? 1 : 0,
                RecordPayment          : action !== 'draft' ? 1 : 0,
                [csrfName]             : csrfVal,
            }, charges);

            setFormLoading('#editInvForm', true, action);

            $.ajax({
                url    : '/invoices/updateInvoice',
                method : 'POST',
                data   : postData,
                cache  : false,
                success: function(response) {
                    if (response.Error) {
                        setFormLoading('#editInvForm', false);
                        showFormError(response.Message);
                    } else {
                        Swal.fire({
                            icon             : 'success',
                            title            : 'Invoice Updated',
                            text             : response.Message,
                            confirmButtonText: 'OK',
                            timer            : 3000,
                            timerProgressBar : true,
                        }).then(function() {
                            window.location.href = '/invoices';
                        });
                    }
                },
                error: function() {
                    setFormLoading('#editInvForm', false);
                    showFormError('Server error. Please try again.');
                }
            });
        });

        $form.on('click', 'button[type="submit"][name="action"]', function() {
            $form.find('button[type="submit"][name="action"]').removeClass('active-submit');
            $(this).addClass('active-submit');
        });

    }

});
</script>
