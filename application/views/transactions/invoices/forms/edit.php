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

                        <?php
                            $hNetAmt    = (float)($InvData->NetAmount   ?? 0);
                            $hPaidAmt   = (float)($InvData->PaidAmount  ?? 0);
                            $hBalAmt    = max(0, round($hNetAmt - $hPaidAmt, 2));
                            $hCurrency  = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
                            $hDecimals  = $JwtData->GenSettings->DecimalPoints ?? 2;
                            $hStatus    = $InvData->DocStatus ?? '';
                            $hStatusMap = ['Issued' => 'primary', 'Partial' => 'info', 'Paid' => 'success', 'Cancelled' => 'danger', 'Rejected' => 'secondary', 'Draft' => 'secondary'];
                            $hStatusClr = $hStatusMap[$hStatus] ?? 'secondary';
                        ?>
                        <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2 trans-header-static trans-theme modal-header-center-sticky">
                            <div class="d-flex align-items-center gap-3" id="transHeaderInfo">
                                <div class="trans-doc-icon bg-primary bg-opacity-10">
                                    <i class="bx bx-receipt text-primary" style="font-size:1.1rem;"></i>
                                </div>
                                <div>
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <span class="fw-bold" style="font-size:.92rem;"><?php echo $isDraftEdit ? '' : 'Edit'; ?> Invoice</span>
                                        <?php if (!$isDraftEdit && !empty($InvData->UniqueNumber)): ?>
                                            <span class="badge bg-label-primary"><?php echo htmlspecialchars($InvData->UniqueNumber); ?></span>
                                            <span class="badge bg-label-<?php echo $hStatusClr; ?>" style="font-size:.7rem;"><?php echo $hStatus; ?></span>
                                        <?php endif; ?>
                                        <div class="d-flex align-items-center gap-1 <?php echo (!$isDraftEdit ? 'd-none' : ''); ?>">
                                            <div class="input-group w-auto">
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
                                            <div class="input-group input-group-sm w-auto">
                                                <span class="input-group-text cursor-pointer fw-semibold text-primary" id="appendPrefixVal"><?php echo htmlspecialchars($editPrefixSeg); ?></span>
                                                <input type="number" id="transNumber" name="transNumber" class="form-control transAutoGenNumber stop-incre-indicator" maxLength="20" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))" oninput="this.value=this.value.slice(0,this.maxLength)" pattern="[0-9]*" value="<?php echo $editTransNumber; ?>" <?php echo (!$isDraftEdit ? 'disabled' : 'required'); ?> />
                                            </div>
                                        </div>
                                        <?php if (!$isDraftEdit): ?>
                                        <input type="hidden" name="transPrefixSelect" value="<?php echo (int)$InvData->PrefixUID; ?>" />
                                        <input type="hidden" name="transNumber" value="<?php echo (int)$InvData->TransNumber; ?>" />
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!$isDraftEdit): ?>
                                    <div class="d-flex align-items-center gap-3 mt-1">
                                        <div class="d-flex align-items-center gap-1">
                                            <span style="font-size:.7rem;color:#8592a3;">Invoice Amount</span>
                                            <span style="font-size:.82rem;font-weight:600;"><?php echo $hCurrency . ' ' . smartDecimal($hNetAmt, $hDecimals, true); ?></span>
                                        </div>
                                        <?php if ($hPaidAmt > 0): ?>
                                        <div class="d-flex align-items-center gap-1">
                                            <span style="font-size:.7rem;color:#8592a3;">Paid</span>
                                            <span style="font-size:.82rem;font-weight:600;color:#28a745;"><?php echo $hCurrency . ' ' . smartDecimal($hPaidAmt, $hDecimals, true); ?></span>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($hBalAmt > 0.009): ?>
                                        <div class="d-flex align-items-center gap-1">
                                            <span style="font-size:.7rem;color:#8592a3;">Balance</span>
                                            <span style="font-size:.82rem;font-weight:600;color:#dc3545;"><?php echo $hCurrency . ' ' . smartDecimal($hBalAmt, $hDecimals, true); ?></span>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($InvData->TransDate)): ?>
                                        <div class="d-flex align-items-center gap-1">
                                            <span style="font-size:.7rem;color:#8592a3;">Date</span>
                                            <span style="font-size:.78rem;color:#566a7f;"><?php echo htmlspecialchars(format_datedisplay($InvData->TransDate, 'd M Y')); ?></span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <?php if ($isDraftEdit): ?>
                                <button type="submit" name="action" value="draft" class="btn btn-sm btn-outline-secondary"><i class="bx bx-save me-1"></i>Draft</button>
                                <?php endif; ?>
                                <button type="submit" name="action" value="save" class="btn btn-sm btn-primary px-3"><i class="bx bx-check me-1"></i><?php echo $isDraftEdit ? 'Save' : 'Update'; ?></button>
                                <a href="/invoices" class="btn btn-sm btn-outline-danger px-3"><i class="bx bx-x me-1"></i>Close</a>
                            </div>
                        </div>

                        <div class="card-body card-body-form-static p-4">

                            <div class="card-header modal-header-center-sticky p-1 mb-3">
                                <h5 class="modal-title mb-0"><i class="bx bx-user me-1"></i> Customer Details</h5>
                            </div>
                            <!-- Row 1: Customer | Type | Dispatch From | Invoice Date | Due Date -->
                            <div class="row g-2 align-items-end">
                                <div class="col-md-4">
                                    <label for="customerSearch" class="trans-field-label">Select Customer <span class="text-danger">*</span></label>
                                    <select id="customerSearch" name="customerSearch" class="form-select form-select-sm">
                                        <?php if (!empty($InvData->PartyUID)): ?>
                                        <option value="<?php echo (int)$InvData->PartyUID; ?>" selected>
                                            <?php echo htmlspecialchars($InvData->PartyName ?? ''); ?>
                                        </option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="invoiceType" class="trans-field-label">Type <span class="text-danger">*</span></label>
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
                                <div class="col-md-2">
                                    <label class="trans-field-label">Dispatch From <span class="text-danger">*</span></label>
                                    <select id="dispatchFrom" name="dispatchFrom" class="form-select form-select-sm" required>
                                        <option value="<?php echo (int)$DispatchAddress->OrgAddressUID; ?>" selected><?php echo implode(', ', $addrLines); ?></option>
                                    </select>
                                </div>
                                <?php endif; ?>
                                <div class="col-md-2">
                                    <label for="transDate" class="trans-field-label">Invoice Date <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-sm input-group-merge">
                                        <span class="input-group-text"><i class="icon-base bx bx-calendar"></i></span>
                                        <input type="text" class="form-control form-control-sm" id="transDate" name="transDate" readonly="readonly" value="<?php echo htmlspecialchars(format_datedisplay($InvData->TransDate, 'Y-m-d')); ?>" required />
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label for="dueDate" class="trans-field-label">Due Date</label>
                                    <div class="input-group input-group-sm input-group-merge">
                                        <span class="input-group-text"><i class="icon-base bx bx-calendar"></i></span>
                                        <input type="text" class="form-control form-control-sm" id="dueDate" name="dueDate" readonly="readonly"
                                            value="<?php echo !empty($InvData->ValidityDate) ? htmlspecialchars(format_datedisplay($InvData->ValidityDate, 'Y-m-d')) : ''; ?>" />
                                    </div>
                                </div>
                            </div>
                            <!-- Row 2: Customer address (when selected) + Reference -->
                            <div class="row g-2 mt-2">
                                <div class="col-md-4">
                                    <div id="customerAddressBox" class="p-2 border border-secondary trans-border-dotted rounded small <?php echo isset($CustAddr) && !empty($CustAddr) ? '' : 'd-none'; ?>">
                                        <?php if (isset($CustAddr) && !empty($CustAddr)): ?>
                                        <div><strong>Shipping Address:</strong></div>
                                        <div><?php echo htmlspecialchars($CustAddr->Line1 ?? ''); ?></div>
                                        <div><?php echo htmlspecialchars($CustAddr->Line2 ?? ''); ?></div>
                                        <div><?php echo htmlspecialchars(trim(implode(' - ', array_filter([
                                            $CustAddr->CityText ?? '',
                                            $CustAddr->Pincode  ?? '',
                                        ])))); ?></div>
                                        <div><?php echo htmlspecialchars($CustAddr->StateText ?? ''); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="referenceDetails" class="trans-field-label">Reference</label>
                                    <input type="text" id="referenceDetails" name="referenceDetails" class="form-control form-control-sm" placeholder="PO Number, Ref No..." maxlength="100"
                                        value="<?php echo htmlspecialchars($InvData->Reference ?? ''); ?>" />
                                </div>
                            </div>
                            <hr class="mt-3"/>

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
                                    <!-- Attach Files (Dropzone) -->
                                    <div class="accordion transAccordion" id="dropZoneAccordion">
                                        <div class="accordion-item">
                                            <h2 class="accordion-header text-body d-flex justify-content-between">
                                                <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#accordionUploadFiles" aria-controls="accordionUploadFiles" aria-expanded="false">
                                                    <i class="icon-base bx bx-paperclip me-2"></i> Attach Files <span class="ms-2 text-muted">(Max: 5)</span>
                                                    <span id="existingAttachCount" class="badge bg-label-primary ms-2 d-none" style="font-size:.7rem;"></span>
                                                </button>
                                            </h2>
                                            <div id="accordionUploadFiles" class="accordion-collapse collapse" data-bs-parent="#dropZoneAccordion">
                                                <div class="accordion-body">
                                                    <!-- Saved attachments -->
                                                    <div id="existingAttachList" class="mb-3 d-none">
                                                        <div class="d-flex align-items-center gap-1 mb-2">
                                                            <i class="bx bx-link-alt text-primary" style="font-size:.85rem;"></i>
                                                            <span style="font-size:.75rem;font-weight:700;color:#566a7f;text-transform:uppercase;letter-spacing:.5px;">Saved Files</span>
                                                        </div>
                                                        <div id="existingAttachItems" class="d-flex flex-wrap gap-2"></div>
                                                    </div>
                                                    <div class="dropzone needsclick p-3 dz-clickable w-100" id="multipleDropzone">
                                                        <div class="dz-message needsclick text-center">
                                                            <i class="upload-icon mb-3"></i>
                                                            <p class="h5 needsclick mb-2">Drag and drop files / images here</p>
                                                            <p class="h4 text-body-secondary fw-normal mb-0">or click to browse</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Summary -->
                                <div class="col-md-6 p-2 trans-theme" style="align-self:flex-start;">
                                    <div class="row">

                                        <!-- Tax Breakdown Panel (left) -->
                                        <div class="col-md-6 tax-summary-section">
                                            <div id="taxBreakupPanel" style="display:none;" class="tax-details-view p-2 bg-light rounded border">
                                                <h6 class="tax-details-title mb-2">Tax Breakdown</h6>
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
                                                <div class="border-top pt-2">
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
                                </div>

                            </div>

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

<!-- ── Edit Page Attachment Preview Modal ──────────────────────── -->
<div class="modal fade" id="editAttachPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-2 px-3">
                <h6 class="modal-title d-flex align-items-center gap-2 mb-0" style="font-size:.88rem;font-weight:700;max-width:90%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    <i class="bx bx-file text-primary"></i>
                    <span id="editAttachPreviewTitle">Preview</span>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" id="editAttachPreviewBody" style="min-height:200px;background:#1a1a2e;">
                <div class="text-center py-5"><span class="spinner-border text-light"></span></div>
            </div>
        </div>
    </div>
</div>

<script src="/js/transactions/invoices.js"></script>
<script src="/js/transactions/transactions.js"></script>
<script src="/js/transactions/transprefix.js"></script>
<script src="/js/transactions/modaladdress.js"></script>
<script src="/js/transactions/products.js"></script>
<script src="/js/combinemodules/products.js"></script>

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
        'description'      => $item->Description ?? '',
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

    transDatePickr('#transDate', false, 'Y-m-d', false, true, true, true, 'd-m-Y');
    transDatePickr('#dueDate', false, 'Y-m-d', false, false, false, true, 'd-m-Y', '#transDate');

    // If no due date saved, default to invoice date; sync when invoice date changes
    var _dueDatePicker   = document.querySelector('#dueDate')._flatpickr;
    var _transDatePicker = document.querySelector('#transDate')._flatpickr;
    if (_dueDatePicker && _transDatePicker) {
        if (!_dueDatePicker.selectedDates.length) {
            _dueDatePicker.setDate(_transDatePicker.selectedDates[0], true);
        }
        document.querySelector('#transDate').addEventListener('change', function () {
            if (_transDatePicker.selectedDates[0] && !$('#dueDate').data('manually-set')) {
                _dueDatePicker.setDate(_transDatePicker.selectedDates[0], true);
            }
        });
        $('#dueDate').on('change', function () {
            $(this).data('manually-set', true);
        });
    }

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

    // ── Load existing attachments ─────────────────────────
    var _removedAttachIDs = [];
    var _transUID = parseInt($('input[name="TransUID"]').val(), 10);
    if (_transUID > 0) {
        $.ajax({
            url   : '/invoices/getAttachments',
            method: 'POST',
            data  : { TransUID: _transUID, [CsrfName]: CsrfToken },
            success: function(resp) {
                if (resp.Error || !resp.Attachments || !resp.Attachments.length) return;
                var cdnUrl = (typeof CDN_URL !== 'undefined' && CDN_URL) ? CDN_URL : '';
                var $container = $('#existingAttachItems').empty();
                resp.Attachments.forEach(function(a) {
                    var uid      = a.AttachUID;
                    var name     = a.FileName || '';
                    var safeName = $('<span>').text(name).html();
                    var fullUrl  = cdnUrl + (a.FilePath || '');
                    var isImg    = /image\//i.test(a.FileType || '') || /\.(jpg|jpeg|png|gif|webp|bmp|svg)$/i.test(name);
                    var isPdf    = /pdf/i.test(a.FileType || '') || /\.pdf$/i.test(name);
                    var iconCls  = isImg ? 'bx-image-alt text-success' : (isPdf ? 'bxs-file-pdf text-danger' : 'bx-file text-secondary');
                    var encUrl   = encodeURIComponent(fullUrl);
                    var $item = $('<div class="d-flex align-items-center gap-1 border rounded px-2 py-1 bg-light existing-attach-item" style="font-size:.78rem;max-width:220px;" data-uid="' + uid + '">' +
                        '<i class="bx ' + iconCls + '" style="font-size:1rem;flex-shrink:0;cursor:pointer;" onclick="_openEditAttachPreview(\'' + encUrl + '\',\'' + (isImg?'img':(isPdf?'pdf':'file')) + '\',\'' + safeName.replace(/'/g,"\\'") + '\')"></i>' +
                        '<span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1;cursor:pointer;" title="' + safeName + '" onclick="_openEditAttachPreview(\'' + encUrl + '\',\'' + (isImg?'img':(isPdf?'pdf':'file')) + '\',\'' + safeName.replace(/'/g,"\\'") + '\')">' + safeName + '</span>' +
                        '<button type="button" class="btn-close btn-close-sm ms-1 remove-attach-btn" style="font-size:.6rem;" title="Remove" data-uid="' + uid + '"></button>' +
                    '</div>');
                    $container.append($item);
                });
                $('#existingAttachList').removeClass('d-none');
                var cnt = resp.Attachments.length;
                $('#existingAttachCount').text(cnt).removeClass('d-none');
                // Open accordion if there are saved files
                $('#accordionUploadFiles').addClass('show');

                // Remove handler
                $(document).on('click', '.remove-attach-btn', function() {
                    var attachUID = parseInt($(this).data('uid'), 10);
                    $(this).closest('.existing-attach-item').remove();
                    _removedAttachIDs.push(attachUID);
                    var remaining = $('#existingAttachItems .existing-attach-item').length;
                    if (remaining === 0) $('#existingAttachList').addClass('d-none');
                    var newCnt = remaining;
                    if (newCnt > 0) $('#existingAttachCount').text(newCnt);
                    else $('#existingAttachCount').addClass('d-none');
                });
            }
        });
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

            var fd = new FormData();
            fd.append(csrfName, csrfVal);
            fd.append('TransUID',               parseInt($('input[name="TransUID"]').val(), 10));
            fd.append('transPrefixSelect',      parseInt($('#transPrefixSelect').val(), 10) || 0);
            fd.append('transNumber',            $.trim($('#transNumber').val()));
            fd.append('transDate',              transDate);
            fd.append('dueDate',                $.trim($('#dueDate').val()));
            fd.append('customerSearch',         customerUID);
            fd.append('invoiceType',            $('#invoiceType').val() || '');
            fd.append('dispatchFrom',           $('#dispatchFrom').val() || '');
            fd.append('referenceDetails',       $.trim($('#referenceDetails').val()));
            fd.append('transNotes',             $.trim($('#transNotes').val()));
            fd.append('transTermsCond',         $.trim($('#transTermsCond').val()));
            fd.append('extraDiscount',          extraDisc);
            fd.append('extDiscountType',        $('#extDiscountType').val() || '');
            fd.append('SubTotal',               subTotal);
            fd.append('DiscountAmount',         discountAmt);
            fd.append('TaxAmount',              taxAmt);
            fd.append('CgstAmount',             cgstAmt);
            fd.append('SgstAmount',             sgstAmt);
            fd.append('IgstAmount',             igstAmt);
            fd.append('AdditionalChargesTotal', addCharges);
            fd.append('GlobalDiscPercent',      globalDiscPct);
            fd.append('RoundOff',               roundOff);
            fd.append('NetAmount',              netAmount);
            fd.append('Items',                  JSON.stringify(items));
            fd.append('action',                 action);
            $.each(charges, function(k, v) { fd.append(k, v); });
            if (typeof multiDropzone !== 'undefined' && multiDropzone) {
                multiDropzone.files.forEach(function(f) { fd.append('AttachFiles[]', f); });
            }
            fd.append('RemovedAttachIDs', JSON.stringify(_removedAttachIDs || []));

            setFormLoading('#editInvForm', true, action);

            $.ajax({
                url         : '/invoices/updateInvoice',
                method      : 'POST',
                data        : fd,
                processData : false,
                contentType : false,
                cache       : false,
                success: function(response) {
                    if (response.Error) {
                        setFormLoading('#editInvForm', false);
                        showFormError(response.Message);
                    } else {
                        _showSavedAndGo('Invoice Updated', response.Message);
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

function _openEditAttachPreview(encUrl, type, name) {
    var url = decodeURIComponent(encUrl);
    var safeName = $('<span>').text(name).html();
    var body = '';
    if (type === 'img') {
        body = '<div class="text-center p-3"><img src="' + $('<span>').text(url).html() + '" class="img-fluid rounded" style="max-height:70vh;" alt="' + safeName + '"></div>';
    } else if (type === 'pdf') {
        body = '<iframe src="' + $('<span>').text(url).html() + '" style="width:100%;height:70vh;border:none;"></iframe>';
    } else {
        body = '<div class="text-center py-5">' +
            '<i class="bx bx-file-blank text-secondary" style="font-size:4rem;display:block;margin-bottom:12px;"></i>' +
            '<div style="font-size:.9rem;font-weight:600;margin-bottom:16px;">' + safeName + '</div>' +
            '<button class="btn btn-primary px-4" onclick="(function(u,n){var a=document.createElement(\'a\');a.href=u;a.download=n;a.style.display=\'none\';document.body.appendChild(a);a.click();document.body.removeChild(a);})(decodeURIComponent(\'' + encUrl + '\'),\'' + safeName.replace(/'/g, "\\'") + '\')"><i class="bx bx-download me-2"></i>Download File</button>' +
            '</div>';
    }
    $('#editAttachPreviewBody').html(body);
    $('#editAttachPreviewTitle').text(name || 'Preview');
    new bootstrap.Modal(document.getElementById('editAttachPreviewModal')).show();
}

function _showSavedAndGo(title, msg) {
    Swal.fire({
        icon             : 'success',
        title            : title,
        text             : msg,
        confirmButtonText: 'OK',
        timer            : 3000,
        timerProgressBar : true,
    }).then(function() {
        window.location.href = '/invoices';
    });
}
</script>
