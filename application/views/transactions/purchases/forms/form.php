<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$isEdit      = isset($PurchData);
$isDraftEdit = $isEdit && ($PurchData->DocStatus === 'Draft');
$transUID    = $isEdit ? (int)$PurchData->TransUID : 0;
$formId      = 'purchForm';
$formAction  = $isEdit ? 'purchases/updatePurchase' : 'purchases/addPurchase';
$_posCode    = $isEdit ? ($PurchData->PlaceOfSupplyCode  ?? '') : ($JwtData->Org->StateCode  ?? '');
$_posName    = $isEdit ? ($PurchData->PlaceOfSupplyName  ?? '') : ($JwtData->Org->StateName  ?? '');

$_returnTab  = $this->input->get('returnTab')  ?: 'All';
$_returnPage = (int)($this->input->get('returnPage') ?: 1);
$_closeUrl   = '/purchases';
$_cParams    = [];
if ($_returnTab) $_cParams[] = 'tab=' . urlencode($_returnTab);
if ($_returnPage > 1) $_cParams[] = 'page=' . $_returnPage;
if ($_cParams) $_closeUrl .= '?' . implode('&', $_cParams);

// Edit: resolve prefix config for the existing transaction
$editPrefixConfig = null;
if ($isEdit && !empty($PrefixData)) {
    foreach ($PrefixData as $_pd) {
        if ((int)$_pd->PrefixUID === (int)$PurchData->PrefixUID) {
            $editPrefixConfig = $_pd;
            break;
        }
    }
    if (!$editPrefixConfig) $editPrefixConfig = $PrefixData[0];
}

if ($isEdit && !function_exists('buildPurchPrefixSegment')) {
    function buildPurchPrefixSegment($cfg) {
        if (!$cfg) return '';
        $sep   = $cfg->Separator ?? '-';
        $parts = [$cfg->Name];
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
}

$editTransNumber = ($isEdit && $isDraftEdit)
    ? (int)($NextNumberMap[(int)($editPrefixConfig->PrefixUID ?? 0)] ?? 1)
    : ($isEdit ? (int)$PurchData->TransNumber : 0);
$editPrefixSeg   = ($isEdit && $isDraftEdit) ? buildPurchPrefixSegment($editPrefixConfig) : '';

// Edit: parse stored Notes to split out PO Ref
$_poRef     = '';
$_userNotes = '';
if ($isEdit) {
    $_rawNotes  = $PurchData->Notes ?? '';
    $_userNotes = $_rawNotes;
    if (preg_match('/^\[PO Ref: (.*?)\]\s*(.*)$/s', $_rawNotes, $_m)) {
        $_poRef     = $_m[1];
        $_userNotes = trim($_m[2]);
    }
}

// Dispatch address display string — kept for edit mode vendor card only
$_addrParts = [];
if (!empty($DispatchAddress)) {
    $_addrParts = array_filter([
        htmlspecialchars($DispatchAddress->Line1 ?? ''),
        htmlspecialchars($DispatchAddress->Line2 ?? ''),
    ]);
    $_cityPin = trim(implode(' - ', array_filter([
        htmlspecialchars($DispatchAddress->CityText ?? ''),
        htmlspecialchars($DispatchAddress->Pincode  ?? ''),
    ])));
    if ($_cityPin) $_addrParts[] = $_cityPin;
    if (!empty($DispatchAddress->StateText)) $_addrParts[] = htmlspecialchars($DispatchAddress->StateText);
}

if ($isEdit) {
    $hNetAmt   = (float)($PurchData->NetAmount  ?? 0);
    $hPaidAmt  = (float)($PurchData->PaidAmount ?? 0);
    $hDecimals = (int)($JwtData->GenSettings->DecimalPoints ?? 2);
    $hBalAmt   = max(0, round($hNetAmt - $hPaidAmt, $hDecimals));
    $hCurrency = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '&#8377;');
    $hStatus   = $PurchData->DocStatus ?? '';
    $hStatusMap = ['Issued' => 'primary', 'Partial' => 'info', 'Paid' => 'success', 'Cancelled' => 'danger', 'Rejected' => 'secondary', 'Draft' => 'secondary'];
    $hStatusClr = $hStatusMap[$hStatus] ?? 'secondary';
}
?>

<?php $this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-horizontal transactionPage layout-content-navbar">
    <div class="layout-container">

        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">

            <?php
                $FormAttribute = [
                    'id'               => $formId,
                    'name'             => $formId,
                    'autocomplete'     => 'off',
                    'data-csrf'        => $this->security->get_csrf_token_name(),
                    'data-csrf-value'  => $this->security->get_csrf_hash(),
                ];
                echo form_open($formAction, $FormAttribute);
            ?>
                <?php if ($isEdit): ?>
                <input type="hidden" name="TransUID" value="<?php echo $transUID; ?>" />
                <?php endif; ?>
                <?php $this->load->view('transactions/partials/place_of_supply_inputs', ['_posCode' => $_posCode, '_posName' => $_posName]); ?>

                    <div class="card mb-3">

                        <!-- ── Card Header ── -->
                        <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2 trans-header-static trans-theme modal-header-center-sticky">
                            <div class="d-flex align-items-center gap-3" id="transHeaderInfo">
                                <div class="trans-doc-icon" style="background-color:#f0ebff;">
                                    <i class="bx bx-cart" style="font-size:1.1rem;color:#6f42c1;"></i>
                                </div>
                                <div>
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <span class="fw-bold" style="font-size:.92rem;">
                                            <?php echo $isEdit ? (($isDraftEdit ? '' : 'Edit ') . 'Purchase Bill') : 'Record Purchase Bill'; ?>
                                        </span>
                                        <?php if ($isEdit && !$isDraftEdit && !empty($PurchData->UniqueNumber)): ?>
                                            <span class="trans-form-doc-number"><?php echo htmlspecialchars($PurchData->UniqueNumber); ?></span>
                                        <?php endif; ?>

                                        <!-- Prefix / number block -->
                                        <?php if (!$isEdit): ?>
                                            <?php $this->load->view('transactions/partials/form_prefix_add'); ?>
                                        <?php else: ?>
                                            <div class="d-flex align-items-center gap-1">
                                                <div class="input-group w-auto <?php echo (!$isDraftEdit ? 'd-none' : ''); ?>">
                                                    <select id="transPrefixSelect" name="transPrefixSelect" class="select2 form-select form-select-sm" <?php echo (!$isDraftEdit ? 'disabled' : 'required'); ?>>
                                                    <?php try {
                                                            if (empty($PrefixData)) throw new Exception();
                                                            foreach ($PrefixData as $preData) {
                                                                $isSelected = (int)$preData->PrefixUID === (int)$PurchData->PrefixUID ? 'selected' : '';
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
                                                    <input type="number" id="transNumber" name="transNumber"
                                                        class="form-control transAutoGenNumber stop-incre-indicator"
                                                        maxLength="20"
                                                        onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))"
                                                        oninput="this.value=this.value.slice(0,this.maxLength)"
                                                        pattern="[0-9]*"
                                                        value="<?php echo $editTransNumber; ?>"
                                                        <?php echo (!$isDraftEdit ? 'disabled' : 'required'); ?> />
                                                </div>
                                                <?php if (!$isDraftEdit): ?>
                                                <input type="hidden" name="transPrefixSelect" value="<?php echo (int)$PurchData->PrefixUID; ?>" />
                                                <input type="hidden" name="transNumber" value="<?php echo (int)$PurchData->TransNumber; ?>" />
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($isEdit && !$isDraftEdit): ?>
                                    <div class="d-flex align-items-center gap-3 mt-1">
                                        <div class="d-flex align-items-center gap-1">
                                            <span style="font-size:.7rem;color:#8592a3;">Bill Amount</span>
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
                                        <?php if (!empty($PurchData->TransDate)): ?>
                                        <div class="d-flex align-items-center gap-1">
                                            <span style="font-size:.7rem;color:#8592a3;">Date</span>
                                            <span style="font-size:.78rem;color:#566a7f;"><?php echo htmlspecialchars(format_datedisplay($PurchData->TransDate)); ?></span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <?php if (!$isEdit): ?>
                                    <button type="submit" name="action" value="draft" class="btn btn-sm btn-outline-secondary"><i class="bx bx-save me-1"></i>Save as Draft</button>
                                    <div class="btn-group">
                                        <button type="submit" name="action" value="save" class="btn btn-sm btn-primary px-3"><i class="bx bx-check me-1"></i>Save</button>
                                        <button type="button" class="btn btn-sm btn-primary dropdown-toggle dropdown-toggle-split ps-2 pe-2" data-bs-toggle="dropdown" aria-expanded="false">
                                            <span class="visually-hidden">Save options</span>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width:195px;font-size:.82rem;">
                                            <li><span class="dropdown-header py-1" style="font-size:.65rem;letter-spacing:.4px;">SAVE &amp; PRINT</span></li>
                                            <li><button type="submit" class="dropdown-item py-1" name="action" value="save_a4"><i class="bx bx-file text-primary me-2"></i>Save &amp; Print A4</button></li>
                                            <li><button type="submit" class="dropdown-item py-1" name="action" value="save_a5"><i class="bx bx-file-blank text-info me-2"></i>Save &amp; Print A5</button></li>
                                            <li><button type="submit" class="dropdown-item py-1" name="action" value="save_thermal"><i class="bx bx-receipt text-success me-2"></i>Save &amp; Print Thermal</button></li>
                                        </ul>
                                    </div>
                                <?php elseif ($isDraftEdit): ?>
                                    <button type="submit" name="action" value="draft" class="btn btn-sm btn-outline-secondary"><i class="bx bx-save me-1"></i>Save as Draft</button>
                                    <button type="submit" name="action" value="save" class="btn btn-sm btn-primary"><i class="bx bx-check me-1"></i>Save</button>
                                <?php else: ?>
                                    <button type="submit" name="action" value="save" class="btn btn-sm btn-primary"><i class="bx bx-check me-1"></i>Save</button>
                                <?php endif; ?>
                                <?php $_hideNav = (int)($JwtData->TransSettings->HideNavOnTransForm ?? 0); ?>
                                <a href="<?php echo $_closeUrl; ?>" class="btn btn-sm btn-outline-danger px-3<?php echo $_hideNav ? ' d-none' : ''; ?>"><i class="bx bx-x me-1"></i>Close</a>
                            </div>
                        </div>

                        <div class="card-body card-body-form-static p-3">

                            <!-- ── Toolbar: Type & Deliver To ──────────────────────────────── -->
                            <div class="d-flex align-items-center gap-4 mb-3 pb-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-muted" style="font-size:.78rem;white-space:nowrap;">Type</span>
                                    <select class="form-select form-select-sm border-0 bg-transparent fw-semibold trans-gst-type-select"
                                            id="purchaseType" name="purchaseType" style="min-width:110px;cursor:pointer;"
                                            <?php echo ($isEdit && !$isDraftEdit) ? 'disabled' : 'required'; ?>>
                                        <option value="Regular" <?php echo (!$isEdit || ($PurchData->DocType ?? '') === 'Regular' || empty($PurchData->DocType ?? '')) ? 'selected' : ''; ?>>Regular</option>
                                        <option value="Without_GST" <?php echo ($isEdit && ($PurchData->DocType ?? '') === 'Without_GST') ? 'selected' : ''; ?>>Without GST</option>
                                    </select>
                                    <?php if ($isEdit && !$isDraftEdit): ?>
                                    <input type="hidden" name="purchaseType" value="<?php echo htmlspecialchars($PurchData->DocType ?? 'Regular'); ?>" />
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($DispatchAddresses)): ?>
                                <div class="d-flex align-items-center gap-2 dispatch-from-grp" style="max-width:360px;">
                                    <span class="text-muted" style="font-size:.78rem;white-space:nowrap;">Deliver To</span>
                                    <?php $this->load->view('common/transactions/_dispatch_from'); ?>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- ── Row 1: Vendor | Supplier Invoice Date | Payment By | Reference ── -->
                            <div class="row g-2 align-items-end mb-2">

                                <div class="col-md-4">
                                    <?php if ($isEdit && !$isDraftEdit): ?>
                                        <label class="trans-field-label">Vendor</label>
                                        <div class="trans-vendor-card">
                                            <div class="trans-vendor-card-name"><i class="bx bx-store me-1"></i><?php echo htmlspecialchars($PurchData->PartyName ?? '—'); ?></div>
                                            <?php if (!empty($PurchData->PartyMobile)): ?>
                                            <div class="trans-vendor-card-meta"><i class="bx bx-phone me-1"></i><?php echo htmlspecialchars($PurchData->PartyMobile); ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($PurchData->PartyGSTIN)): ?>
                                            <div class="trans-vendor-card-meta"><i class="bx bx-id-card me-1"></i><?php echo htmlspecialchars($PurchData->PartyGSTIN); ?></div>
                                            <?php endif; ?>
                                            <?php
                                                $_vParts = array_filter([
                                                    $VendorAddr->Line1     ?? '',
                                                    $VendorAddr->CityText  ?? '',
                                                    $VendorAddr->StateText ?? '',
                                                ]);
                                                if (!empty($_vParts)):
                                            ?>
                                            <div class="trans-vendor-card-meta"><i class="bx bx-map me-1"></i><?php echo htmlspecialchars(implode(', ', $_vParts)); ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <input type="hidden" id="vendorSearch" name="vendorSearch" value="<?php echo (int)$PurchData->PartyUID; ?>" />
                                    <?php else: ?>
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <label for="vendorSearch" class="trans-field-label mb-0">Vendor <span class="text-danger">*</span></label>
                                            <button type="button" id="addTransVendor" class="trans-add-btn btn btn-outline-primary btn-sm" style="font-size:.72rem;white-space:nowrap;"><i class="bx bx-plus-circle me-1"></i>Add Vendor</button>
                                        </div>
                                        <select id="vendorSearch" name="vendorSearch" class="form-select form-select-sm"></select>
                                    <?php endif; ?>
                                </div>

                                <!-- Supplier Invoice Date -->
                                <div class="col-auto" style="min-width:160px;">
                                    <label for="transDate" class="trans-field-label">
                                        Supplier Invoice Date <span class="text-danger">*</span>
                                        <i class="bx bx-help-circle ms-1 text-muted" style="font-size:.82rem;cursor:pointer;"
                                           data-bs-toggle="tooltip" data-bs-placement="top"
                                           title="The date printed on the supplier's invoice. Used for GST reporting and payment tracking."></i>
                                    </label>
                                    <?php $_fmt = $JwtData->GenSettings->FormDateFormat ?? 'd-m-Y'; ?>
                                    <?php if ($isEdit && !$isDraftEdit): ?>
                                        <input type="hidden" name="transDate" value="<?php echo htmlspecialchars(format_datedisplay($PurchData->TransDate, 'Y-m-d')); ?>" />
                                        <div class="input-group input-group-sm input-group-merge">
                                            <span class="input-group-text bg-white"><i class="icon-base bx bx-calendar"></i></span>
                                            <input type="text" class="form-control form-control-sm bg-white text-muted" style="cursor:default;" value="<?php echo htmlspecialchars(format_datedisplay($PurchData->TransDate, $_fmt)); ?>" readonly tabindex="-1" />
                                        </div>
                                    <?php else: ?>
                                        <div class="input-group input-group-sm input-group-merge">
                                            <span class="input-group-text bg-white"><i class="icon-base bx bx-calendar"></i></span>
                                            <input type="text" class="form-control form-control-sm bg-white" id="transDate_disp" readonly="readonly"
                                                value="<?php echo $isEdit ? format_datedisplay($PurchData->TransDate, $_fmt) : format_datedisplay(time(), $_fmt); ?>"
                                                required />
                                            <input type="hidden" id="transDate" name="transDate" value="<?php echo $isEdit ? htmlspecialchars(format_datedisplay($PurchData->TransDate, 'Y-m-d')) : format_datedisplay(time(), 'Y-m-d'); ?>" />
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Supplier Payment By -->
                                <div class="col-auto" style="min-width:150px;">
                                    <label for="billDueDate" class="trans-field-label">
                                        Payment By
                                        <i class="bx bx-help-circle ms-1 text-muted" style="font-size:.82rem;cursor:pointer;"
                                           data-bs-toggle="tooltip" data-bs-placement="top"
                                           title="The deadline by which you must pay your vendor."></i>
                                    </label>
                                    <div class="input-group input-group-sm input-group-merge">
                                        <span class="input-group-text bg-white"><i class="icon-base bx bx-calendar"></i></span>
                                        <input type="text" class="form-control form-control-sm bg-white" id="billDueDate_disp" readonly="readonly"
                                            value="<?php echo ($isEdit && !empty($PurchData->ValidityDate)) ? format_datedisplay($PurchData->ValidityDate, $_fmt) : format_datedisplay(date('Y-m-d'), $_fmt); ?>" />
                                        <input type="hidden" id="billDueDate" name="billDueDate" value="<?php echo ($isEdit && !empty($PurchData->ValidityDate)) ? htmlspecialchars(format_datedisplay($PurchData->ValidityDate, 'Y-m-d')) : date('Y-m-d'); ?>" />
                                    </div>
                                </div>

                                <!-- Supplier Invoice No -->
                                <div class="col-auto" style="min-width:150px;">
                                    <label for="supplierInvoiceNo" class="trans-field-label">Supplier Invoice No.</label>
                                    <input type="text" id="supplierInvoiceNo" name="supplierInvoiceNo" class="form-control form-control-sm"
                                        placeholder="e.g. INV-2025-0042" maxlength="100"
                                        value="<?php echo $isEdit ? htmlspecialchars($PurchData->SupplierInvoiceNo ?? '') : ''; ?>" />
                                </div>

                                <!-- Reference — takes remaining width -->
                                <div class="col">
                                    <label for="referenceDetails" class="trans-field-label">
                                        Reference / PO No.
                                        <i class="bx bx-help-circle ms-1 text-muted" style="font-size:.82rem;cursor:pointer;"
                                           data-bs-toggle="tooltip" data-bs-placement="top"
                                           title="Link the bill to a PO number, shipment reference, or sales person name."></i>
                                    </label>
                                    <input type="text" id="referenceDetails" name="referenceDetails" class="form-control form-control-sm"
                                        placeholder="e.g. PO-2025-001, Shipment #TRK456, Indent No: IND-88"
                                        maxlength="100"
                                        value="<?php echo $isEdit ? htmlspecialchars($PurchData->Reference ?? '') : (!empty($POData->UniqueNumber) ? htmlspecialchars($POData->UniqueNumber) : ''); ?>" />
                                </div>

                            </div>

                            <!-- Vendor address box -->
                            <div class="row g-2 mb-3">
                                <div class="col-md-4">
                                    <div id="vendorAddressBox" class="p-2 border border-secondary trans-border-dotted rounded small d-none"></div>
                                </div>
                            </div>
                            <hr class="mt-2 mb-3"/>

                            <?php $this->load->view('transactions/partials/form_products_add', [
                                'transNotesPlaceholder' => 'Enter notes or anything else',
                                'transNotesContent'     => $isEdit ? $_userNotes : '',
                                'transHideTerms'        => empty($JwtData->TransSettings->PurchaseShowTerms),
                                'transTermsContent'     => $isEdit ? ($PurchData->TermsConditions ?? '') : ($JwtData->TransSettings->TermsAndConditions ?? ''),
                                'transShowDropzone'     => true,
                                'transShowSignature'    => !empty($JwtData->TransSettings->PurchaseShowSignature),
                                'transSignatureUID'     => $isEdit ? (int)($PurchData->SignatureUID ?? 0) : 0,
                                'transPaymentVars'      => !$isEdit ? [
                                    'PaymentTypes'     => $PaymentTypes ?? [],
                                    'BankAccounts'     => $BankAccounts ?? [],
                                    'JwtData'          => $JwtData,
                                    'paymentPartyType' => 'V',
                                ] : null,
                            ]); ?>

                            <!-- ── Inline full-width summary ── -->
                            <?php $cur = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹'); ?>
                            <div id="inlineSummaryBar" class="sticky-bottom-bar mt-3" style="padding:10px 24px;display:flex;align-items:center;justify-content:space-between;gap:16px;border-radius:8px;">
                                <div class="d-flex align-items-stretch gap-0">
                                    <div style="padding-right:20px;">
                                        <div class="fw-bold" style="font-size:.95rem;">TOTAL &nbsp;<span style="color:#0d6efd;" id="inlineGrandTotal"><?php echo $cur; ?> 0.00</span></div>
                                        <div class="text-muted" style="font-size:.74rem;">Includes Total Tax &nbsp;<span id="inlineTotalTax">0.00</span></div>
                                    </div>
                                    <div id="inlinePaidGroup" class="d-none d-flex align-items-stretch">
                                        <div style="width:1px;background:#c5dcff;margin:0 20px;flex-shrink:0;"></div>
                                        <div>
                                            <div style="font-size:.74rem;color:#198754;font-weight:600;"><i class="bx bx-check-circle me-1"></i>Total Paid</div>
                                            <div class="fw-bold" style="font-size:.92rem;color:#198754;"><span id="inlineTotalPaid"><?php echo $cur; ?> 0.00</span></div>
                                        </div>
                                    </div>
                                    <div id="inlineBalanceGroup" class="d-none d-flex align-items-stretch">
                                        <div style="width:1px;background:#c5dcff;margin:0 20px;flex-shrink:0;"></div>
                                        <div>
                                            <div style="font-size:.74rem;color:#dc3545;font-weight:600;"><i class="bx bx-wallet me-1"></i>Balance</div>
                                            <div class="fw-bold" style="font-size:.92rem;color:#dc3545;"><span id="inlineBalanceAmt"><?php echo $cur; ?> 0.00</span></div>
                                        </div>
                                    </div>
                                    <div id="inlineExcessGroup" class="d-none d-flex align-items-stretch">
                                        <div style="width:1px;background:#c5dcff;margin:0 20px;flex-shrink:0;"></div>
                                        <div>
                                            <div style="font-size:.74rem;color:#f59e0b;font-weight:600;"><i class="bx bx-error-circle me-1"></i>Excess</div>
                                            <div class="fw-bold" style="font-size:.92rem;color:#f59e0b;"><span id="inlineExcessAmt"><?php echo $cur; ?> 0.00</span></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <?php if (!$isEdit || $isDraftEdit): ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="inlineDraftBtn"><i class="bx bx-save me-1"></i>Save as Draft</button>
                                    <?php endif; ?>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-primary px-3" id="inlineSaveBtn">
                                            <i class="bx bx-check me-1"></i>Save
                                        </button>
                                        <?php if (!$isEdit || $isDraftEdit): ?>
                                        <button type="button" class="btn btn-sm btn-primary dropdown-toggle dropdown-toggle-split ps-2 pe-2" data-bs-toggle="dropdown" aria-expanded="false">
                                            <span class="visually-hidden">Save options</span>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow dropup" style="min-width:195px;font-size:.82rem;">
                                            <li><span class="dropdown-header py-1" style="font-size:.65rem;letter-spacing:.4px;">SAVE &amp; PRINT</span></li>
                                            <li><button type="button" class="dropdown-item py-1" data-inline-action="save_a4"><i class="bx bx-file text-primary me-2"></i>Save &amp; Print A4</button></li>
                                            <li><button type="button" class="dropdown-item py-1" data-inline-action="save_a5"><i class="bx bx-file-blank text-info me-2"></i>Save &amp; Print A5</button></li>
                                            <li><button type="button" class="dropdown-item py-1" data-inline-action="save_thermal"><i class="bx bx-receipt text-success me-2"></i>Save &amp; Print Thermal</button></li>
                                        </ul>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                        </div> <!-- /card-body -->
                    </div> <!-- /card -->

                    <!-- ── Sticky bottom summary bar ── -->
                    <div id="stickyBottomBar" class="sticky-bottom-bar" style="position:fixed;bottom:0;right:0;z-index:1040;padding:10px 24px;display:flex;align-items:center;justify-content:space-between;gap:16px;">
                        <div class="d-flex align-items-stretch gap-0">
                            <div style="padding-right:20px;">
                                <div class="fw-bold" style="font-size:.95rem;">TOTAL &nbsp;<span style="color:#0d6efd;" id="stickyGrandTotal"><?php echo $cur; ?> 0.00</span></div>
                                <div class="text-muted" style="font-size:.74rem;">Includes Total Tax &nbsp;<span id="stickyTotalTax">0.00</span></div>
                            </div>
                            <div id="stickyPaidGroup" class="d-none d-flex align-items-stretch">
                                <div style="width:1px;background:#c5dcff;margin:0 20px;flex-shrink:0;"></div>
                                <div>
                                    <div style="font-size:.74rem;color:#198754;font-weight:600;"><i class="bx bx-check-circle me-1"></i>Total Paid</div>
                                    <div class="fw-bold" style="font-size:.92rem;color:#198754;"><span id="stickyTotalPaid"><?php echo $cur; ?> 0.00</span></div>
                                </div>
                            </div>
                            <div id="stickyBalanceGroup" class="d-none d-flex align-items-stretch">
                                <div style="width:1px;background:#c5dcff;margin:0 20px;flex-shrink:0;"></div>
                                <div>
                                    <div style="font-size:.74rem;color:#dc3545;font-weight:600;"><i class="bx bx-wallet me-1"></i>Balance</div>
                                    <div class="fw-bold" style="font-size:.92rem;color:#dc3545;"><span id="stickyBalanceAmt"><?php echo $cur; ?> 0.00</span></div>
                                </div>
                            </div>
                            <div id="stickyExcessGroup" class="d-none d-flex align-items-stretch">
                                <div style="width:1px;background:#c5dcff;margin:0 20px;flex-shrink:0;"></div>
                                <div>
                                    <div style="font-size:.74rem;color:#f59e0b;font-weight:600;"><i class="bx bx-error-circle me-1"></i>Excess</div>
                                    <div class="fw-bold" style="font-size:.92rem;color:#f59e0b;"><span id="stickyExcessAmt"><?php echo $cur; ?> 0.00</span></div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <?php if (!$isEdit || $isDraftEdit): ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="stickyDraftBtn"><i class="bx bx-save me-1"></i>Save as Draft</button>
                            <?php endif; ?>
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-primary px-3" id="stickySaveBtn">
                                    <i class="bx bx-check me-1"></i>Save
                                </button>
                                <?php if (!$isEdit || $isDraftEdit): ?>
                                <button type="button" class="btn btn-sm btn-primary dropdown-toggle dropdown-toggle-split ps-2 pe-2" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="visually-hidden">Save options</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow dropup" style="min-width:195px;font-size:.82rem;">
                                    <li><span class="dropdown-header py-1" style="font-size:.65rem;letter-spacing:.4px;">SAVE &amp; PRINT</span></li>
                                    <li><button type="button" class="dropdown-item py-1" data-sticky-action="save_a4"><i class="bx bx-file text-primary me-2"></i>Save &amp; Print A4</button></li>
                                    <li><button type="button" class="dropdown-item py-1" data-sticky-action="save_a5"><i class="bx bx-file-blank text-info me-2"></i>Save &amp; Print A5</button></li>
                                    <li><button type="button" class="dropdown-item py-1" data-sticky-action="save_thermal"><i class="bx bx-receipt text-success me-2"></i>Save &amp; Print Thermal</button></li>
                                </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <?php echo form_close(); ?>

                </div>
            </div>

            <?php $this->load->view('common/transactions/transprefix'); ?>
            <?php $this->load->view('transactions/modals/vendor'); ?>
            <?php $this->load->view('transactions/modals/vendor_search'); ?>
            <?php $this->load->view('transactions/modals/taxdetails'); ?>
            <?php $this->load->view('common/modals/category_form'); ?>
            <?php $this->load->view('common/modals/product_form'); ?>
            <?php $this->load->view('common/footer_desc'); ?>

        </div>

    </div>
</div>

<?php $this->load->view('transactions/partials/additional_charges_modal'); ?>
<?php $this->load->view('common/transactions/footer'); ?>

<script src="/js/transactions/purchases.js"></script>
<script src="/js/transactions/vendor_search.js"></script>
<script src="/js/transactions/transactions.js"></script>
<script src="/js/transactions/transprefix.js"></script>
<script src="/js/transactions/modaladdress.js"></script>
<script src="/js/common/category_form.js"></script>
<script src="/js/common/product_form.js"></script>
<?php if (!$isEdit): ?>
<script src="/js/transactions/payment_section.js"></script>
<?php endif; ?>
<script src="/js/transactions/attachments.js"></script>
<script>
var _transAdditionalCharges  = <?php echo json_encode(array_values($AdditionalCharges   ?? [])); ?>;
var _transAdditionalTaxOpts  = <?php echo json_encode(array_values($TaxList             ?? [])); ?>;
var _transTransactionCharges = <?php echo json_encode(array_values($TransactionCharges  ?? [])); ?>;
</script>
<script src="/js/transactions/additional_charges.js"></script>

<script>
var _transFormData = <?php echo json_encode([
    'isEdit'        => $isEdit,
    'isDraftEdit'   => $isDraftEdit,
    'moduleUID'     => 105,
    'enableStorage' => (bool)$JwtData->GenSettings->EnableStorage,
    'formId'        => $formId,
    'formAction'    => $formAction,
    'orgState'      => $DispatchAddress->StateText ?? '',
    'upstashUrl'    => $UpstashReadUrl   ?? '',
    'upstashToken'  => $UpstashReadToken ?? '',
    'vendorCacheKey'=> $VendorCacheKey   ?? '',
    'returnTab'     => $_returnTab,
    'returnPage'    => (int)$_returnPage,
    'currency'      => $JwtData->GenSettings->CurrenySymbol ?? '₹',
    'decimals'      => (int)($JwtData->GenSettings->DecimalPoints ?? 2),
    'editData'      => $isEdit ? [
        'transUID'          => $transUID,
        'vendorState'       => isset($VendorAddr) ? ($VendorAddr->StateText ?? '') : '',
        'extraDiscAmount'   => (float)($PurchData->ExtraDiscAmount ?? 0),
        'extraDiscType'     => $PurchData->ExtraDiscType ?? '',
        'globalDiscPercent' => (float)($PurchData->GlobalDiscPercent ?? 0),
        'attachments'       => $PurchAttachments ?? [],
        'items'             => array_map(function($item) {
            return [
                'id'               => (int)  $item->ProductUID,
                'text'             => $item->ProductName,
                'itemName'         => $item->ProductName,
                'description'      => $item->Description   ?? '',
                'unitPrice'        => (float)$item->UnitPrice,
                'taxAmount'        => (float)$item->TaxAmount,
                'sellingPrice'     => (float)$item->SellingPrice,
                'purchasePrice'    => (float)($item->PurchasePrice ?? 0),
                'availableQuantity'=> 0,
                'hsnCode'          => '',
                'categoryUID'      => $item->CategoryUID ? (int)$item->CategoryUID : null,
                'categoryName'     => $item->CategoryName  ?? '',
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
        }, $PurchItems ?? []),
    ] : null,
    'fromPO'      => (!$isEdit && !empty($POData)) ? [
        'uid'        => (int)$POData->TransUID,
        'vendorUID'  => (int)$POData->PartyUID,
        'vendorName' => $POData->PartyName ?? '',
    ] : null,
    'fromPOItems' => (!$isEdit && !empty($POItems)) ? array_map(function($item) {
        return [
            'id'               => (int)  $item->ProductUID,
            'text'             => $item->ProductName,
            'itemName'         => $item->ProductName,
            'description'      => $item->Description   ?? '',
            'unitPrice'        => (float)$item->UnitPrice,
            'taxAmount'        => (float)$item->TaxAmount,
            'sellingPrice'     => (float)$item->SellingPrice,
            'purchasePrice'    => (float)($item->PurchasePrice ?? 0),
            'availableQuantity'=> 0,
            'hsnCode'          => '',
            'categoryUID'      => $item->CategoryUID ? (int)$item->CategoryUID : null,
            'categoryName'     => $item->CategoryName  ?? '',
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
    }, $POItems) : null,
]); ?>;
</script>
<script src="/js/transactions/forms/purchase.js"></script>
