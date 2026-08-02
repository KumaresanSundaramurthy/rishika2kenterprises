<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$isEdit      = isset($PRData);
$isDraftEdit = $isEdit && ($PRData->DocStatus === 'Draft');
$transUID    = $isEdit ? (int)$PRData->TransUID : 0;
$formId      = 'prForm';
$formAction  = $isEdit ? 'purchasereturns/updatePurchaseReturn' : 'purchasereturns/addPurchaseReturn';
$_posCode    = $isEdit ? ($PRData->PlaceOfSupplyCode  ?? '') : ($JwtData->Org->StateCode  ?? '');
$_posName    = $isEdit ? ($PRData->PlaceOfSupplyName  ?? '') : ($JwtData->Org->StateName  ?? '');

$_returnTab  = $this->input->get('returnTab')  ?: 'All';
$_returnPage = (int)($this->input->get('returnPage') ?: 1);
$_closeUrl   = trans_build_close_url('/purchasereturns', $_returnTab, $_returnPage);
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
    $hDecimals = (int)($JwtData->GenSettings->DecimalPoints ?? 2);
    $hBalAmt   = max(0, round($hNetAmt - $hPaidAmt, $hDecimals));
    $hCurrency = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '&#8377;');
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
                <?php $this->load->view('transactions/partials/place_of_supply_inputs', ['_posCode' => $_posCode, '_posName' => $_posName]); ?>

                    <div class="card mb-3">

                        <!-- Card Header -->
                        <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2 trans-header-static trans-theme modal-header-center-sticky">
                            <div class="d-flex align-items-center gap-3" id="transHeaderInfo">
                                <?php $this->load->view('transactions/partials/form_back_button'); ?>
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
                                            <?php $this->load->view('transactions/partials/form_prefix_edit', [
                                                '_editPrefixUID'  => (int)($PRData->PrefixUID   ?? 0),
                                                'editTransNumber' => $editTransNumber,
                                                'editPrefixSeg'   => $editPrefixSeg,
                                                'isDraftEdit'     => $isDraftEdit,
                                            ]); ?>
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
                                    <button type="submit" name="action" value="draft" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('tooltip_save_draft', 'Save and continue editing later'); ?>"><i class="bx bx-save me-1"></i><?php echo t('btn_save_draft', 'Save as Draft'); ?></button>
                                    <div class="btn-group">
                                        <button type="submit" name="action" value="save" class="btn btn-sm btn-primary px-3" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('tooltip_save', 'Save transaction'); ?>"><i class="bx bx-check me-1"></i>Save</button>
                                        <button type="button" class="btn btn-sm btn-primary dropdown-toggle dropdown-toggle-split ps-2 pe-2" data-bs-toggle="dropdown" aria-expanded="false">
                                            <span class="visually-hidden">Save options</span>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width:195px;font-size:.82rem;">
                                            <li><span class="dropdown-header py-1" style="font-size:.65rem;letter-spacing:.4px;">SAVE &amp; PRINT</span></li>
                                            <li><button type="submit" class="dropdown-item py-1" name="action" value="save_a4"><i class="bx bx-file text-primary me-2"></i><?php echo t('btn_save_a4', 'Save & Print A4'); ?></button></li>
                                            <li><button type="submit" class="dropdown-item py-1" name="action" value="save_a5"><i class="bx bx-file-blank text-info me-2"></i><?php echo t('btn_save_a5', 'Save & Print A5'); ?></button></li>
                                            <li><button type="submit" class="dropdown-item py-1" name="action" value="save_thermal"><i class="bx bx-receipt text-success me-2"></i><?php echo t('btn_save_thermal', 'Save & Print Thermal'); ?></button></li>
                                        </ul>
                                    </div>
                                <?php elseif ($isDraftEdit): ?>
                                    <button type="submit" name="action" value="draft" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('tooltip_save_draft', 'Save and continue editing later'); ?>"><i class="bx bx-save me-1"></i><?php echo t('btn_save_draft', 'Save as Draft'); ?></button>
                                    <button type="submit" name="action" value="save" class="btn btn-sm btn-primary"><i class="bx bx-check me-1"></i>Save</button>
                                <?php else: ?>
                                    <button type="submit" name="action" value="save" class="btn btn-sm btn-primary"><i class="bx bx-check me-1"></i>Save</button>
                                <?php endif; ?>
                                <?php $_hideNav = (int)($JwtData->TransSettings->HideNavOnTransForm ?? 0); ?>
                                <a href="<?php echo $_closeUrl; ?>" class="btn btn-sm btn-outline-danger px-3<?php echo $_hideNav ? ' d-none' : ''; ?>" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('tooltip_close', 'Return to list'); ?>"><i class="bx bx-x me-1"></i>Close</a>
                            </div>
                        </div>

                        <div class="card-body card-body-form-static p-3">

                            <!-- ── Toolbar: Type & Dispatch From ──────────────────────────────── -->
                            <div class="d-flex align-items-center gap-4 mb-3 pb-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-muted" style="font-size:.78rem;white-space:nowrap;">Type</span>
                                    <?php if ($isEdit && !$isDraftEdit): ?>
                                    <?php $_prType = $PRData->DocType ?? 'Regular'; ?>
                                    <span class="trans-type-readonly"><?php echo $_prType === 'Without_GST' ? 'Without GST' : 'Regular'; ?></span>
                                    <input type="hidden" name="purchaseType" value="<?php echo htmlspecialchars($_prType); ?>" />
                                    <?php else: ?>
                                    <select class="form-select form-select-sm border-0 bg-transparent fw-semibold trans-gst-type-select"
                                            id="purchaseType" name="purchaseType" style="min-width:110px;cursor:pointer;" required>
                                        <option value="Regular" <?php echo (!$isEdit || ($PRData->DocType ?? '') === 'Regular' || empty($PRData->DocType ?? '')) ? 'selected' : ''; ?>>Regular</option>
                                        <option value="Without_GST" <?php echo ($PRData->DocType ?? '') === 'Without_GST' ? 'selected' : ''; ?>>Without GST</option>
                                    </select>
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
                                        <label class="trans-field-label mb-1">Vendor</label>
                                        <select id="vendorSearch" name="vendorSearch" class="form-select form-select-sm"></select>
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
                                        <?php echo t('lbl_return_date', 'Return Date'); ?> <span class="text-danger">*</span>
                                    </label>
                                    <?php $_fmt = $JwtData->GenSettings->FormDateFormat ?? 'd-m-Y'; ?>
                                    <?php if ($isEdit && !$isDraftEdit): ?>
                                        <input type="hidden" name="transDate" value="<?php echo htmlspecialchars(format_datedisplay($PRData->TransDate, 'Y-m-d')); ?>" />
                                        <div class="input-group input-group-sm input-group-merge">
                                            <span class="input-group-text bg-white"><i class="icon-base bx bx-calendar"></i></span>
                                            <input type="text" class="form-control form-control-sm bg-white text-muted" style="cursor:default;" value="<?php echo htmlspecialchars(format_datedisplay($PRData->TransDate, $_fmt)); ?>" readonly tabindex="-1" />
                                        </div>
                                    <?php else: ?>
                                        <div class="input-group input-group-sm input-group-merge">
                                            <span class="input-group-text bg-white"><i class="icon-base bx bx-calendar"></i></span>
                                            <input type="text" class="form-control form-control-sm bg-white" id="transDate_disp" readonly="readonly"
                                                value="<?php echo $isEdit ? format_datedisplay($PRData->TransDate, $_fmt) : format_datedisplay(time(), $_fmt); ?>"
                                                required />
                                            <input type="hidden" id="transDate" name="transDate" value="<?php echo $isEdit ? htmlspecialchars(format_datedisplay($PRData->TransDate, 'Y-m-d')) : format_datedisplay(time(), 'Y-m-d'); ?>" />
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Reference -->
                                <div class="col">
                                    <label for="referenceDetails" class="trans-field-label"><?php echo t('lbl_reference_bill', 'Reference / Bill No.'); ?></label>
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
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="inlineDraftBtn" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo t('tooltip_save_draft', 'Save and continue editing later'); ?>"><i class="bx bx-save me-1"></i><?php echo t('btn_save_draft', 'Save as Draft'); ?></button>
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
                                            <li><button type="button" class="dropdown-item py-1" data-inline-action="save_a4"><i class="bx bx-file text-primary me-2"></i><?php echo t('btn_save_a4', 'Save & Print A4'); ?></button></li>
                                            <li><button type="button" class="dropdown-item py-1" data-inline-action="save_a5"><i class="bx bx-file-blank text-info me-2"></i><?php echo t('btn_save_a5', 'Save & Print A5'); ?></button></li>
                                            <li><button type="button" class="dropdown-item py-1" data-inline-action="save_thermal"><i class="bx bx-receipt text-success me-2"></i><?php echo t('btn_save_thermal', 'Save & Print Thermal'); ?></button></li>
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
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="stickyDraftBtn" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo t('tooltip_save_draft', 'Save and continue editing later'); ?>"><i class="bx bx-save me-1"></i><?php echo t('btn_save_draft', 'Save as Draft'); ?></button>
                            <?php endif; ?>
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-primary px-3" id="stickySaveBtn" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo t('tooltip_save', 'Save transaction'); ?>">
                                    <i class="bx bx-check me-1"></i>Save
                                </button>
                                <?php if (!$isEdit || $isDraftEdit): ?>
                                <button type="button" class="btn btn-sm btn-primary dropdown-toggle dropdown-toggle-split ps-2 pe-2" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="visually-hidden">Save options</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow dropup" style="min-width:195px;font-size:.82rem;">
                                    <li><span class="dropdown-header py-1" style="font-size:.65rem;letter-spacing:.4px;">SAVE &amp; PRINT</span></li>
                                    <li><button type="button" class="dropdown-item py-1" data-sticky-action="save_a4"><i class="bx bx-file text-primary me-2"></i><?php echo t('btn_save_a4', 'Save & Print A4'); ?></button></li>
                                    <li><button type="button" class="dropdown-item py-1" data-sticky-action="save_a5"><i class="bx bx-file-blank text-info me-2"></i><?php echo t('btn_save_a5', 'Save & Print A5'); ?></button></li>
                                    <li><button type="button" class="dropdown-item py-1" data-sticky-action="save_thermal"><i class="bx bx-receipt text-success me-2"></i><?php echo t('btn_save_thermal', 'Save & Print Thermal'); ?></button></li>
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
            <?php $this->load->view('transactions/partials/form_common_modals'); ?>

        </div>

    </div>
</div>

<?php $this->load->view('transactions/partials/additional_charges_modal'); ?>
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
<?php $this->load->view('transactions/partials/additional_charges_data'); ?>

<script>
var _transFormData = <?php echo json_encode([
    'isEdit'        => $isEdit,
    'isDraftEdit'   => $isDraftEdit,
    'moduleUID'     => 108,
    'enableStorage' => (bool)$JwtData->GenSettings->EnableStorage,
    'formId'        => $formId,
    'formAction'    => $formAction,
    'upstashUrl'    => $UpstashReadUrl   ?? '',
    'upstashToken'  => $UpstashReadToken ?? '',
    'vendorCacheKey'=> $VendorCacheKey   ?? '',
    'returnTab'     => $_returnTab,
    'returnPage'    => (int)$_returnPage,
    'currency'      => $JwtData->GenSettings->CurrenySymbol ?? '₹',
    'decimals'      => (int)($JwtData->GenSettings->DecimalPoints ?? 2),
    'prItemMethod'  => $_prMethod,
    'editData'      => $isEdit ? [
        'transUID'          => $transUID,
        'vendorUID'         => (int)($PRData->PartyUID ?? 0),
        'vendorName'        => $PRData->PartyName  ?? '',
        'vendorArea'        => $PRData->PartyArea   ?? '',
        'vendorMobile'      => $PRData->PartyMobile ?? '',
        'extraDiscAmount'   => (float)($PRData->ExtraDiscount ?? 0),
        'extraDiscType'     => $PRData->ExtraDiscountType ?? '',
        'globalDiscPercent' => (float)($PRData->GlobalDiscPercent ?? 0),
        'attachments'       => $PRAttachments ?? [],
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
        }, $PRItems ?? []),
    ] : null,
]); ?>;
</script>
<script src="/js/transactions/forms/purchasereturn.js"></script>
