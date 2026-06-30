<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$isEdit      = isset($PRData);
$isDraftEdit = $isEdit && ($PRData->DocStatus === 'Draft');
$transUID    = $isEdit ? (int)$PRData->TransUID : 0;
$formId      = 'prForm';
$formAction  = $isEdit ? 'purchasereturns/updatePurchaseReturn' : 'purchasereturns/addPurchaseReturn';
$_prMethod   = $JwtData->TransSettings->PurchaseReturnItemMethod ?? 'Manual';

$editPrefixConfig = null;
if ($isEdit && !empty($PrefixData)) {
    foreach ($PrefixData as $_pd) {
        if ((int)$_pd->PrefixUID === (int)$PRData->PrefixUID) { $editPrefixConfig = $_pd; break; }
    }
    if (!$editPrefixConfig) $editPrefixConfig = $PrefixData[0];
}

if ($isEdit && !function_exists('buildPRPrefixSegment')) {
    function buildPRPrefixSegment($cfg) {
        if (!$cfg) return '';
        $sep   = $cfg->Separator ?? '-';
        $parts = [$cfg->Name];
        if (!empty($cfg->IncludeShortName) && !empty($cfg->ShortName)) $parts[] = strtoupper($cfg->ShortName);
        if (!empty($cfg->IncludeFiscalYear)) {
            $m  = (int)date('m'); $yr = (int)date('Y'); $fy = $m >= 4 ? $yr : $yr - 1;
            $parts[] = ($cfg->FiscalYearFormat ?? 'SHORT') === 'LONG'
                ? $fy . '-' . ($fy + 1)
                : str_pad($fy % 100, 2, '0', STR_PAD_LEFT) . '-' . str_pad(($fy + 1) % 100, 2, '0', STR_PAD_LEFT);
        }
        return implode($sep, $parts) . $sep;
    }
}

$editTransNumber = ($isEdit && $isDraftEdit)
    ? (int)($NextNumberMap[(int)($editPrefixConfig->PrefixUID ?? 0)] ?? 1)
    : ($isEdit ? (int)$PRData->TransNumber : 0);
$editPrefixSeg = ($isEdit && $isDraftEdit) ? buildPRPrefixSegment($editPrefixConfig) : '';

if ($isEdit) {
    $hNetAmt   = (float)($PRData->NetAmount  ?? 0);
    $hPaidAmt  = (float)($PRData->PaidAmount ?? 0);
    $hBalAmt   = max(0, round($hNetAmt - $hPaidAmt, 2));
    $hCurrency = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '&#8377;');
    $hDecimals = $JwtData->GenSettings->DecimalPoints ?? 2;
    $hStatus   = $PRData->DocStatus ?? '';
    $hStatusMap = ['Approved' => 'primary', 'Partial' => 'info', 'Paid' => 'success', 'Cancelled' => 'danger', 'Draft' => 'secondary'];
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
                    'id'              => $formId,
                    'name'            => $formId,
                    'autocomplete'    => 'off',
                    'data-csrf'       => $this->security->get_csrf_token_name(),
                    'data-csrf-value' => $this->security->get_csrf_hash(),
                ];
                echo form_open($formAction, $FormAttribute);
            ?>
                <?php if ($isEdit): ?>
                <input type="hidden" name="TransUID" value="<?php echo $transUID; ?>" />
                <?php endif; ?>
                <input type="hidden" id="placeOfSupplyCode" name="placeOfSupplyCode" value="<?php echo !$isEdit ? htmlspecialchars($JwtData->Org->StateCode ?? '', ENT_QUOTES) : ''; ?>" />
                <input type="hidden" id="placeOfSupplyName" name="placeOfSupplyName" value="<?php echo !$isEdit ? htmlspecialchars($JwtData->Org->StateName ?? '', ENT_QUOTES) : ''; ?>" />

                    <div class="card mb-3">

                        <!-- Card Header -->
                        <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2 trans-header-static trans-theme modal-header-center-sticky">
                            <div class="d-flex align-items-center gap-3" id="transHeaderInfo">
                                <div class="trans-doc-icon" style="background-color:#e8f5e9;">
                                    <i class="bx bx-undo" style="font-size:1.1rem;color:#28a745;"></i>
                                </div>
                                <div>
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <span class="fw-bold" style="font-size:.92rem;">
                                            <?php echo $isEdit ? (($isDraftEdit ? '' : 'Edit ') . 'Purchase Return') : 'Create Purchase Return'; ?>
                                        </span>
                                        <?php if ($isEdit && !$isDraftEdit && !empty($PRData->UniqueNumber)): ?>
                                            <span class="trans-form-doc-number"><?php echo htmlspecialchars($PRData->UniqueNumber); ?></span>
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
                                                                $isSelected = (int)$preData->PrefixUID === (int)$PRData->PrefixUID ? 'selected' : '';
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
                                                <input type="hidden" name="transPrefixSelect" value="<?php echo (int)$PRData->PrefixUID; ?>" />
                                                <input type="hidden" name="transNumber" value="<?php echo (int)$PRData->TransNumber; ?>" />
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($isEdit && !$isDraftEdit): ?>
                                    <div class="d-flex align-items-center gap-3 mt-1">
                                        <div class="d-flex align-items-center gap-1">
                                            <span style="font-size:.7rem;color:#8592a3;">Return Amount</span>
                                            <span style="font-size:.82rem;font-weight:600;"><?php echo $hCurrency . ' ' . smartDecimal($hNetAmt, $hDecimals, true); ?></span>
                                        </div>
                                        <?php if ($hPaidAmt > 0): ?>
                                        <div class="d-flex align-items-center gap-1">
                                            <span style="font-size:.7rem;color:#8592a3;">Received</span>
                                            <span style="font-size:.82rem;font-weight:600;color:#28a745;"><?php echo $hCurrency . ' ' . smartDecimal($hPaidAmt, $hDecimals, true); ?></span>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($hBalAmt > 0.009): ?>
                                        <div class="d-flex align-items-center gap-1">
                                            <span style="font-size:.7rem;color:#8592a3;">Balance</span>
                                            <span style="font-size:.82rem;font-weight:600;color:#dc3545;"><?php echo $hCurrency . ' ' . smartDecimal($hBalAmt, $hDecimals, true); ?></span>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($PRData->TransDate)): ?>
                                        <div class="d-flex align-items-center gap-1">
                                            <span style="font-size:.7rem;color:#8592a3;">Date</span>
                                            <span style="font-size:.78rem;color:#566a7f;"><?php echo htmlspecialchars(format_datedisplay($PRData->TransDate)); ?></span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <?php if (!$isEdit): ?>
                                    <button type="submit" name="action" value="draft" class="btn btn-sm btn-outline-secondary"><i class="bx bx-save me-1"></i>Draft</button>
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
                                    <button type="submit" name="action" value="save" class="btn btn-sm btn-primary"><i class="bx bx-check me-1"></i>Update</button>
                                <?php endif; ?>
                                <?php $_hideNav = (int)($JwtData->TransSettings->HideNavOnTransForm ?? 0); ?>
                                <a href="/purchasereturns" class="btn btn-sm btn-outline-danger px-3<?php echo $_hideNav ? ' d-none' : ''; ?>"><i class="bx bx-x me-1"></i>Close</a>
                            </div>
                        </div>

                        <div class="card-body card-body-form-static p-3">

                            <!-- ── Toolbar: Type & Dispatch From ──────────────────────────────── -->
                            <div class="d-flex align-items-center gap-4 mb-3 pb-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-muted" style="font-size:.78rem;white-space:nowrap;">Type</span>
                                    <select class="form-select form-select-sm border-0 bg-transparent fw-semibold"
                                            id="purchaseType" name="purchaseType" style="min-width:110px;cursor:pointer;"
                                            <?php echo ($isEdit && !$isDraftEdit) ? 'disabled' : 'required'; ?>>
                                        <option value="Regular" <?php echo (!$isEdit || ($PRData->QuotationType ?? '') === 'Regular' || empty($PRData->QuotationType ?? '')) ? 'selected' : ''; ?>>Regular</option>
                                        <option value="Without_GST" <?php echo ($isEdit && ($PRData->QuotationType ?? '') === 'Without_GST') ? 'selected' : ''; ?>>Without GST</option>
                                    </select>
                                    <?php if ($isEdit && !$isDraftEdit): ?>
                                    <input type="hidden" name="purchaseType" value="<?php echo htmlspecialchars($PRData->QuotationType ?? 'Regular'); ?>" />
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($DispatchAddresses)): ?>
                                <div class="d-flex align-items-center gap-2 dispatch-from-grp" style="max-width:360px;">
                                    <span class="text-muted" style="font-size:.78rem;white-space:nowrap;">Dispatch From</span>
                                    <?php $this->load->view('common/transactions/_dispatch_from'); ?>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- ── Row 1: Vendor | Return Date | Reference ── -->
                            <div class="row g-2 align-items-end mb-2">

                                <div class="col-md-4">
                                    <?php if ($isEdit && !$isDraftEdit): ?>
                                        <label class="trans-field-label">Vendor</label>
                                        <div class="trans-vendor-card">
                                            <div class="trans-vendor-card-name"><i class="bx bx-store me-1"></i><?php echo htmlspecialchars($PRData->PartyName ?? '—'); ?></div>
                                            <?php if (!empty($PRData->PartyMobile)): ?>
                                            <div class="trans-vendor-card-meta"><i class="bx bx-phone me-1"></i><?php echo htmlspecialchars($PRData->PartyMobile); ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($PRData->PartyGSTIN)): ?>
                                            <div class="trans-vendor-card-meta"><i class="bx bx-id-card me-1"></i><?php echo htmlspecialchars($PRData->PartyGSTIN); ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <input type="hidden" id="vendorSearch" name="vendorSearch" value="<?php echo (int)$PRData->PartyUID; ?>" />
                                    <?php else: ?>
                                        <label for="vendorSearch" class="trans-field-label">Vendor <span class="text-danger">*</span></label>
                                        <select id="vendorSearch" name="vendorSearch" class="form-select form-select-sm"></select>
                                    <?php endif; ?>
                                </div>

                                <?php if ($_prMethod !== 'Manual'): ?>
                                <div class="col-md-3">
                                    <label for="fromPurchaseUID" class="trans-field-label">Purchase From</label>
                                    <select id="fromPurchaseUID" name="fromPurchaseUID" class="form-select form-select-sm" disabled>
                                        <option value="">-- Select Vendor First --</option>
                                    </select>
                                </div>
                                <?php endif; ?>

                                <!-- Return Date -->
                                <div class="col-auto" style="min-width:160px;">
                                    <label for="transDate" class="trans-field-label">
                                        Return Date <span class="text-danger">*</span>
                                    </label>
                                    <?php if ($isEdit && !$isDraftEdit): ?>
                                        <input type="hidden" name="transDate" value="<?php echo htmlspecialchars(format_datedisplay($PRData->TransDate, 'Y-m-d')); ?>" />
                                        <div class="input-group input-group-sm input-group-merge">
                                            <span class="input-group-text bg-white"><i class="icon-base bx bx-calendar"></i></span>
                                            <input type="text" class="form-control form-control-sm bg-white text-muted" style="cursor:default;" value="<?php echo htmlspecialchars(format_datedisplay($PRData->TransDate, 'd-m-Y')); ?>" readonly tabindex="-1" />
                                        </div>
                                    <?php else: ?>
                                        <div class="input-group input-group-sm input-group-merge">
                                            <span class="input-group-text bg-white"><i class="icon-base bx bx-calendar"></i></span>
                                            <input type="text" class="form-control form-control-sm bg-white" id="transDate" name="transDate" readonly="readonly"
                                                value="<?php echo $isEdit ? htmlspecialchars(format_datedisplay($PRData->TransDate, 'Y-m-d')) : format_datedisplay(time(), 'Y-m-d'); ?>"
                                                required />
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Reference -->
                                <div class="col">
                                    <label for="referenceDetails" class="trans-field-label">Reference / Bill No.</label>
                                    <input type="text" id="referenceDetails" name="referenceDetails" class="form-control form-control-sm"
                                        placeholder="e.g. INV-2025-0042, PO No..."
                                        maxlength="100"
                                        value="<?php echo $isEdit ? htmlspecialchars($PRData->Reference ?? '') : ''; ?>" />
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
                                'transProductSectionTitle' => 'Returned Products',
                                'transNotesPlaceholder'    => 'Reason for return',
                                'transNotesContent'        => $isEdit ? ($PRData->Notes ?? '') : '',
                                'transHideTerms'           => empty($JwtData->TransSettings->PurchaseShowTerms),
                                'transTermsContent'        => $isEdit ? ($PRData->TermsConditions ?? '') : ($JwtData->TransSettings->TermsAndConditions ?? ''),
                                'transShowDropzone'        => true,
                                'transShowSignature'       => !empty($JwtData->TransSettings->PurchaseShowSignature),
                                'transSignatureUID'        => $isEdit ? (int)($PRData->SignatureUID ?? 0) : 0,
                                'transHideAddProduct'      => true,
                                'transHideProductSearch'   => $_prMethod === 'Automatic',
                                'transPaymentVars'         => !$isEdit ? [
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
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <?php if (!$isEdit || $isDraftEdit): ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="inlineDraftBtn"><i class="bx bx-save me-1"></i>Draft</button>
                                    <?php endif; ?>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-primary px-3" id="inlineSaveBtn">
                                            <i class="bx bx-check me-1"></i><?php echo ($isEdit && !$isDraftEdit) ? 'Update' : 'Save'; ?>
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

                        </div>
                    </div>

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
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <?php if (!$isEdit || $isDraftEdit): ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="stickyDraftBtn"><i class="bx bx-save me-1"></i>Draft</button>
                            <?php endif; ?>
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-primary px-3" id="stickySaveBtn">
                                    <i class="bx bx-check me-1"></i><?php echo ($isEdit && !$isDraftEdit) ? 'Update' : 'Save'; ?>
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
            <?php $this->load->view('transactions/modals/vendor_search'); ?>
            <?php if ($_prMethod !== 'Manual'): ?>
            <?php $this->load->view('transactions/modals/purchase_items_select'); ?>
            <?php endif; ?>
            <?php $this->load->view('transactions/modals/taxdetails'); ?>
            <?php $this->load->view('common/modals/category_form'); ?>
            <?php $this->load->view('common/modals/product_form'); ?>
            <?php $this->load->view('common/footer_desc'); ?>

        </div>

    </div>
</div>

<?php $this->load->view('common/transactions/footer'); ?>

<script src="/js/transactions/purchasereturns.js"></script>
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
const EnableStorage = <?php echo $JwtData->GenSettings->EnableStorage; ?>;
var _isEdit   = <?php echo $isEdit ? 'true' : 'false'; ?>;
var _transUID = <?php echo $transUID; ?>;
var _upstashUrl       = '<?php echo addslashes($UpstashReadUrl  ?? ''); ?>';
var _upstashReadToken = '<?php echo addslashes($UpstashReadToken ?? ''); ?>';
var _vendorCacheKey   = '<?php echo addslashes($VendorCacheKey  ?? ''); ?>';
window._productPurchaseMode = true;
var _prItemMethod = '<?php echo $_prMethod; ?>';

<?php if ($isEdit): ?>
var _editItems = <?php echo json_encode(array_map(function($item) {
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
}, $PRItems)); ?>;
<?php endif; ?>

$(function() {
    'use strict';

    <?php if ($isEdit): ?>
    initTransAttachments(<?php echo $transUID; ?>, '/transactions/getAttachments', 108);
    <?php endif; ?>

    <?php if (!$isEdit || $isDraftEdit): ?>
    searchVendors('vendorSearch');
    window._custSearchHideCreate = true;
    <?php if ($isEdit && $isDraftEdit && !empty($PRData->PartyUID)): ?>
    $('#vendorSearch').append(new Option('<?php echo addslashes($PRData->PartyName ?? ''); ?>', <?php echo (int)$PRData->PartyUID; ?>, true, true)).trigger('change');
    <?php endif; ?>
    <?php endif; ?>

    transDatePickr('#transDate', false, 'Y-m-d', false, true, true, true, 'd-m-Y');

    // ── Purchase From: load vendor purchases on vendor change ──────────────────
    <?php if ($_prMethod !== 'Manual'): ?>

    $('#vendorSearch').on('change', function() {
        var vendUID = parseInt($(this).val(), 10);
        if (_prItemMethod !== 'Manual') resetPurchaseDropdown();
        if (vendUID > 0 && _prItemMethod !== 'Manual') loadVendorPurchases(vendUID);
    });

    function loadVendorPurchases(vendUID) {
        var $pur = $('#fromPurchaseUID');
        $pur.prop('disabled', true).html('<option value="">Loading...</option>');
        AjaxLoading = 0;
        $.ajax({
            url    : '/purchasereturns/getVendorPurchases',
            method : 'POST',
            data   : { VendorUID: vendUID, [CsrfName]: CsrfToken },
            success: function(res) {
                AjaxLoading = 1;
                if (res.Error || !res.Purchases || res.Purchases.length === 0) {
                    $pur.html('<option value="">No purchase bills found</option>');
                    return;
                }
                var opts = '<option value="">-- Select Purchase Bill --</option>';
                res.Purchases.forEach(function(p) {
                    opts += '<option value="' + p.TransUID + '">' + p.UniqueNumber + ' — ' + p.TransDate + '</option>';
                });
                $pur.html(opts).prop('disabled', false);
            },
            error: function() {
                AjaxLoading = 1;
                $pur.html('<option value="">Failed to load</option>');
            }
        });
    }

    function resetPurchaseDropdown() {
        $('#fromPurchaseUID').html('<option value="">-- Select Vendor First --</option>').prop('disabled', true);
    }

    var _lastPurchaseUID  = 0;
    var _purchaseItems    = [];

    $('#fromPurchaseUID').on('change', function() {
        var transUID = parseInt($(this).val(), 10);
        if (!transUID || transUID <= 0) return;
        _lastPurchaseUID = transUID;
        openPurchaseItemsModal(transUID, $(this).find('option:selected').text());
    });

    $('#fromPurchaseUID').on('mousedown', function() {
        $(this).data('pre-click-val', $(this).val());
    }).on('click', function() {
        var preVal = parseInt($(this).data('pre-click-val'), 10);
        var curVal = parseInt($(this).val(), 10);
        if (preVal && preVal === curVal && curVal > 0) {
            openPurchaseItemsModal(curVal, $(this).find('option:selected').text());
        }
    });

    function openPurchaseItemsModal(transUID, purchLabel) {
        _purchaseItems = [];
        $('#purchItemsLoading').removeClass('d-none');
        $('#purchItemsTableWrap').addClass('d-none');
        $('#purchItemsTableBody').empty();
        $('#purchItemsSelectAll').prop('checked', true).prop('disabled', false);
        $('#purchItemsSelectedCount').text('0');
        $('#purchItemsAddToCart').prop('disabled', true);
        $('#purchItemsModalSubtitle').text(purchLabel);
        $('#purchaseItemsModal').modal('show');

        AjaxLoading = 0;
        $.ajax({
            url    : '/purchasereturns/getPurchaseItems',
            method : 'POST',
            data   : { TransUID: transUID, [CsrfName]: CsrfToken },
            success: function(res) {
                AjaxLoading = 1;
                $('#purchItemsLoading').addClass('d-none');
                if (res.Error || !res.Items || res.Items.length === 0) {
                    $('#purchItemsTableBody').html('<tr><td colspan="7" class="text-center text-muted py-4">No items found in this purchase bill.</td></tr>');
                    $('#purchItemsTableWrap').removeClass('d-none');
                    return;
                }
                _purchaseItems = res.Items;
                var cur = (typeof genSettings !== 'undefined' && genSettings.CurrenySymbol) ? genSettings.CurrenySymbol : '₹';
                var rows = '';
                var availCount = 0;
                res.Items.forEach(function(item, idx) {
                    var taxPct   = parseFloat(item.TaxPercentage) || 0;
                    var taxAmt   = parseFloat(item.TaxAmount)     || 0;
                    var disc     = parseFloat(item.Discount)      || 0;
                    var discAmt  = parseFloat(item.DiscountAmount)|| 0;
                    var rowTotal = parseFloat(item.NetAmount)     || 0;
                    var taxCell  = taxPct > 0
                        ? taxPct + '%<br><span class="text-muted" style="font-size:.75rem;">' + cur + ' ' + smartDecimal(taxAmt, 2, true) + '</span>'
                        : '<span class="text-muted">—</span>';
                    var discCell = disc > 0
                        ? disc + '%<br><span class="text-muted" style="font-size:.75rem;">' + cur + ' ' + smartDecimal(discAmt, 2, true) + '</span>'
                        : '<span class="text-muted">—</span>';
                    var inCart  = (typeof billManager !== 'undefined' && billManager.getItemById(item.ProductUID) !== null);
                    if (!inCart) availCount++;
                    var rowClass = inCart ? 'table-secondary' : '';
                    var chkAttr  = inCart ? 'disabled title="Already added to cart"' : 'checked';
                    rows += '<tr class="' + rowClass + '" data-idx="' + idx + '">' +
                        '<td><input type="checkbox" class="form-check-input purch-item-chk" data-idx="' + idx + '" data-transproduid="' + (parseInt(item.TransProdUID, 10) || 0) + '" ' + chkAttr + '></td>' +
                        '<td>' +
                            '<div class="fw-semibold' + (inCart ? ' text-muted' : '') + '" style="' + (inCart ? '' : 'color:#696cff;') + '">' + item.ProductName + '</div>' +
                            (item.PartNumber ? '<div class="small text-muted">Part#: ' + item.PartNumber + '</div>' : '') +
                            (inCart ? '<div class="small text-success"><i class="bx bx-check-circle me-1"></i>Added to cart</div>' : '') +
                        '</td>' +
                        '<td class="text-center">' + smartDecimal(item.RemainingQty) + ' ' + (item.PrimaryUnitName || '') + '</td>' +
                        '<td class="text-end">' + cur + ' ' + smartDecimal(item.UnitPrice, 2, true) + '</td>' +
                        '<td class="text-end">' + taxCell + '</td>' +
                        '<td class="text-end">' + discCell + '</td>' +
                        '<td class="text-end fw-semibold">' + cur + ' ' + smartDecimal(rowTotal, 2, true) + '</td>' +
                    '</tr>';
                });
                $('#purchItemsTableBody').html(rows);
                $('#purchItemsTableWrap').removeClass('d-none');
                $('#purchItemsSelectAll').prop('checked', availCount > 0).prop('disabled', availCount === 0);
                updatePurchItemsFooter();
            },
            error: function() {
                AjaxLoading = 1;
                $('#purchItemsLoading').addClass('d-none');
                $('#purchItemsTableBody').html('<tr><td colspan="7" class="text-center text-danger py-4">Failed to load purchase items.</td></tr>');
                $('#purchItemsTableWrap').removeClass('d-none');
            }
        });
    }

    $(document).on('change', '#purchItemsSelectAll', function() {
        $('#purchItemsTableBody .purch-item-chk:not(:disabled)').prop('checked', $(this).is(':checked'));
        updatePurchItemsFooter();
    });

    $(document).on('change', '.purch-item-chk', function() {
        var total    = $('#purchItemsTableBody .purch-item-chk:not(:disabled)').length;
        var selected = $('#purchItemsTableBody .purch-item-chk:not(:disabled):checked').length;
        $('#purchItemsSelectAll').prop('checked', total > 0 && selected === total);
        updatePurchItemsFooter();
    });

    function updatePurchItemsFooter() {
        var count = $('#purchItemsTableBody .purch-item-chk:not(:disabled):checked').length;
        $('#purchItemsSelectedCount').text(count);
        $('#purchItemsAddToCart').prop('disabled', count === 0);
    }

    $(document).on('click', '#purchItemsAddToCart', function() {
        var added = 0;
        $('#purchItemsTableBody .purch-item-chk:checked').each(function() {
            var idx  = parseInt($(this).data('idx'), 10);
            var item = _purchaseItems[idx];
            if (!item) return;

            var productData = {
                id               : parseInt(item.ProductUID, 10),
                text             : item.ProductName,
                itemName         : item.ProductName,
                description      : item.Description      || '',
                unitPrice        : parseFloat(item.UnitPrice)        || 0,
                sellingPrice     : parseFloat(item.SellingPrice)     || 0,
                purchasePrice    : parseFloat(item.PurchasePrice)    || 0,
                taxAmount        : parseFloat(item.TaxAmount)        || 0,
                availableQuantity: 0,
                hsnCode          : item.HSNCode           || '',
                categoryUID      : item.CategoryUID  ? parseInt(item.CategoryUID)  : null,
                categoryName     : item.CategoryName     || '',
                storageUID       : item.StorageUID   ? parseInt(item.StorageUID)   : null,
                taxPercent       : parseFloat(item.TaxPercentage)    || 0,
                cgstPercent      : parseFloat(item.CGST)             || 0,
                sgstPercent      : parseFloat(item.SGST)             || 0,
                igstPercent      : parseFloat(item.IGST)             || 0,
                taxDetailsUID    : parseInt(item.TaxDetailsUID)      || 1,
                partNumber       : item.PartNumber      || '',
                primaryUnit      : item.PrimaryUnitName || '',
                discount         : parseFloat(item.Discount)         || 0,
                discountType     : 'Percentage',
                discountTypeUID  : item.DiscountTypeUID ? parseInt(item.DiscountTypeUID) : null,
                discount_amount  : parseFloat(item.DiscountAmount)   || 0,
                line_total         : parseFloat(item.TaxableAmount)    || 0,
                net_total          : parseFloat(item.NetAmount)        || 0,
                sourceTransProdUID : parseInt(item.TransProdUID, 10)   || null,
            };

            if (typeof billManager !== 'undefined' && typeof formationTableBillItems === 'function') {
                var qty    = parseFloat(item.RemainingQty > 0 ? item.RemainingQty : item.Quantity) || 1;
                var result = billManager.addItem(productData, qty);
                if (result !== false) {
                    formationTableBillItems(billManager.getItemById(productData.id));
                    added++;
                }
            }
        });

        if (added > 0) {
            if (typeof updateItemTaxBreakdown === 'function') updateItemTaxBreakdown();
            billManager.updateSummary();
        }

        $('#purchaseItemsModal').modal('hide');

        var $pur = $('#fromPurchaseUID');
        $pur.val(null).trigger('change');
        _lastPurchaseUID = 0;
    });

    $('#purchaseItemsModal').on('hidden.bs.modal', function() {
        var $pur = $('#fromPurchaseUID');
        if ($pur.val()) {
            $pur.val(null).trigger('change');
        }
        _lastPurchaseUID = 0;
    });

    <?php if ($isEdit && !$isDraftEdit && !empty($PRData->PartyUID) && $_prMethod !== 'Manual'): ?>
    loadVendorPurchases(<?php echo (int)$PRData->PartyUID; ?>);
    <?php endif; ?>

    <?php endif; // _prMethod !== 'Manual' ?>

    <?php if ($isEdit): ?>
    if (typeof billManager !== 'undefined' && typeof formationTableBillItems === 'function'
            && Array.isArray(_editItems) && _editItems.length > 0) {
        $(document).one('billmanager:ready', function() { formationTableBillItems(_editItems); });
    }
    <?php if (!empty($PRData->GlobalDiscPercent) && $PRData->GlobalDiscPercent > 0): ?>
    $('#globalDiscount').val('<?php echo smartDecimal($PRData->GlobalDiscPercent); ?>').trigger('input');
    <?php endif; ?>
    <?php if (!empty($PRData->ExtraDiscount) && $PRData->ExtraDiscount > 0): ?>
    $('#extraDiscount').val('<?php echo smartDecimal($PRData->ExtraDiscount ?? 0); ?>');
    <?php endif; ?>
    <?php if (!empty($PRData->ExtraDiscountType)): ?>
    $('#extDiscountType').val('<?php echo addslashes($PRData->ExtraDiscountType); ?>');
    <?php endif; ?>
    <?php endif; ?>

    var $form = $('#<?php echo $formId; ?>');
    if ($form.length) {

        $form.on('submit', function(e) {
            e.preventDefault();

            var $btn     = $('button[type="submit"][name="action"]:focus, button[type="submit"][name="action"].active-submit', $form);
            var action   = $btn.val() || 'save';
            var csrfName = $form.data('csrf');
            var csrfVal  = $form.data('csrf-value');

            var vendorUID = parseInt($('#vendorSearch').val(), 10);
            if (!vendorUID || vendorUID <= 0) return showFormError('Please select a vendor.');

            if (!_isEdit && action !== 'draft') {
                var prefixUID = parseInt($('#transPrefixSelect').val(), 10);
                if (!prefixUID || prefixUID <= 0) return showFormError('Please select a prefix.');
                var transNumber = $.trim($('#transNumber').val());
                if (!transNumber || parseInt(transNumber, 10) <= 0) return showFormError('Transaction number must be greater than 0.');
            }

            var transDate = $.trim($('#transDate').val());
            if (!transDate || !/^\d{4}-\d{2}-\d{2}$/.test(transDate)) return showFormError('Please enter a valid date.');

            var items = typeof billManager !== 'undefined' ? billManager.getAllItems() : [];
            if (!items || items.length === 0) return showFormError('Please add at least one product.');

            var bm      = typeof billManager !== 'undefined' ? billManager : null;
            var summary = bm ? bm.summary : {};
            var charges = {};
            if (summary.additionalCharges) {
                ['shipping', 'handling', 'packing', 'other'].forEach(function(t) {
                    var c = summary.additionalCharges[t];
                    if (c && c.grossAmount > 0) { charges[t + 'Amount'] = c.grossAmount; charges[t + 'Tax'] = c.taxPercent || 0; }
                });
            }

            var _payRows = (typeof getPaymentSectionData === 'function') ? getPaymentSectionData() : {};
            var postData = $.extend({
                transPrefixSelect      : parseInt($('#transPrefixSelect').val(), 10) || 0,
                transNumber            : $.trim($('#transNumber').val()),
                transDate              : transDate,
                vendorSearch           : vendorUID,
                fromPurchaseUID        : parseInt($('#fromPurchaseUID').val(), 10) || 0,
                purchaseType           : $('#purchaseType').val() || 'Regular',
                dispatchTo             : $('#dispatchTo').val() || '',
                referenceDetails       : $.trim($('#referenceDetails').val()),
                transNotes             : $.trim($('#transNotes').val()),
                transTermsCond         : $.trim($('#transTermsCond').val()),
                placeOfSupplyCode      : $('#placeOfSupplyCode').val() || '',
                placeOfSupplyName      : $('#placeOfSupplyName').val() || '',
                extraDiscount          : parseFloat($('#extraDiscount').val()) || 0,
                extDiscountType        : $('#extDiscountType').val() || '',
                SubTotal               : summary.items     ? (summary.items.taxableAmount     || 0) : 0,
                DiscountAmount         : summary.items     ? (summary.items.discountTotal      || 0) : 0,
                TaxAmount              : summary.taxTotals ? (summary.taxTotals.totalTax       || 0) : 0,
                CgstAmount             : summary.taxTotals ? (summary.taxTotals.cgstTotal      || 0) : 0,
                SgstAmount             : summary.taxTotals ? (summary.taxTotals.sgstTotal      || 0) : 0,
                IgstAmount             : summary.taxTotals ? (summary.taxTotals.igstTotal      || 0) : 0,
                AdditionalChargesTotal : (summary.additionalCharges && summary.additionalCharges.total) ? (summary.additionalCharges.total.grossAmount || 0) : 0,
                GlobalDiscPercent      : bm ? (bm.globalDiscountPercent || 0) : 0,
                RoundOff               : summary.extra ? (summary.extra.roundOff || 0) : 0,
                NetAmount              : summary.totals ? (summary.totals.grandTotal || 0) : 0,
                Items                  : JSON.stringify(items),
                SignatureUID           : parseInt($('#transSignatureUID').val(), 10) || 0,
                action                 : action,
                [csrfName]             : csrfVal,
            }, charges, _payRows);

            if (_isEdit) postData.TransUID = _transUID;

            var formData = new FormData();
            $.each(postData, function(k, v) { formData.append(k, v); });
            collectTransAttachData(formData);

            var ajaxUrl = _isEdit ? '/purchasereturns/updatePurchaseReturn' : '/purchasereturns/addPurchaseReturn';
            setFormLoading('#<?php echo $formId; ?>', true, action);

            $.ajax({
                url         : ajaxUrl,
                method      : 'POST',
                data        : formData,
                processData : false,
                contentType : false,
                cache       : false,
                success: function(response) {
                    if (response.Error) {
                        setFormLoading('#<?php echo $formId; ?>', false);
                        showFormError(response.Message);
                    } else {
                        Swal.fire({
                            icon             : 'success',
                            title            : _isEdit ? 'Purchase Return Updated' : 'Purchase Return Saved',
                            text             : response.Message || (_isEdit ? 'Updated successfully.' : 'Purchase return created successfully.'),
                            confirmButtonText: 'OK',
                            timer            : 3000,
                            timerProgressBar : true,
                        }).then(function() {
                            window.location.href = '/purchasereturns';
                        });
                    }
                },
                error: function() {
                    setFormLoading('#<?php echo $formId; ?>', false);
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
<script>
(function () {
    var _formEl   = document.getElementById('<?php echo $formId; ?>');
    var _barEl    = document.getElementById('stickyBottomBar');
    var _inlineEl = document.getElementById('inlineSummaryBar');
    if (!_barEl || !_inlineEl) return;

    var cur = '<?php echo addslashes($JwtData->GenSettings->CurrenySymbol ?? "₹"); ?>';
    var dec = 2;
    function _r2(n) { return Math.round(n * 100) / 100; }
    function _fmt(n) { return cur + ' ' + _r2(n).toFixed(dec); }

    function _alignStickyBar() {
        if (!_formEl) return;
        var rect = _formEl.getBoundingClientRect();
        var vpW  = document.documentElement.clientWidth;
        _barEl.style.left  = rect.left + 'px';
        _barEl.style.right = (vpW - rect.right) + 'px';
        _barEl.style.width = 'auto';
    }

    function _sync() {
        if (typeof billManager === 'undefined') return;
        var grand = (billManager.summary && billManager.summary.totals)
            ? (billManager.summary.totals.grandTotal || 0) : 0;
        var tax   = (billManager.summary && billManager.summary.taxTotals)
            ? (billManager.summary.taxTotals.totalTax || 0) : 0;
        ['stickyGrandTotal','inlineGrandTotal'].forEach(function (id) {
            var el = document.getElementById(id); if (el) el.textContent = _fmt(grand);
        });
        ['stickyTotalTax','inlineTotalTax'].forEach(function (id) {
            var el = document.getElementById(id); if (el) el.textContent = _fmt(tax);
        });
    }

    var _obs = new IntersectionObserver(function (entries) {
        if (!entries[0].isIntersecting) { _alignStickyBar(); _barEl.style.display = 'flex'; }
        else { _barEl.style.display = 'none'; }
    }, { threshold: 0.1 });
    _obs.observe(_inlineEl);
    _barEl.style.display = 'none';
    window.addEventListener('resize', _alignStickyBar);

    function _delegate(val) {
        var sel = (val === 'save' || !val)
            ? 'button[name="action"][value="save"][type="submit"]'
            : 'button[name="action"][value="' + val + '"]';
        var btn = _formEl && _formEl.querySelector(sel);
        if (!btn && (val === 'save' || !val)) btn = _formEl && _formEl.querySelector('button[name="action"][value="save"]');
        if (btn) btn.click();
    }

    ['stickySaveBtn','inlineSaveBtn'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('click', function () { _delegate('save'); });
    });
    ['stickyDraftBtn','inlineDraftBtn'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('click', function () { _delegate('draft'); });
    });
    document.addEventListener('click', function (e) {
        var t = e.target.closest('[data-sticky-action],[data-inline-action]');
        if (!t) return;
        _delegate(t.dataset.stickyAction || t.dataset.inlineAction);
    });

    var _totEl = document.getElementById('bill_tot_amt');
    if (_totEl) new MutationObserver(_sync).observe(_totEl, { childList: true, subtree: true, characterData: true });
    _sync();
})();
</script>
