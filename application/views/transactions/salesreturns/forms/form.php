<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$isEdit      = isset($SRData);
$isDraftEdit = $isEdit && ($SRData->DocStatus === 'Draft');
$transUID    = $isEdit ? (int)$SRData->TransUID : 0;
$formId      = 'srForm';
$formAction  = $isEdit ? 'salesreturns/updateSalesReturn' : 'salesreturns/addSalesReturn';
$_posCode    = $isEdit ? ($SRData->PlaceOfSupplyCode  ?? '') : ($JwtData->Org->StateCode  ?? '');
$_posName    = $isEdit ? ($SRData->PlaceOfSupplyName  ?? '') : ($JwtData->Org->StateName  ?? '');

$_returnTab  = $this->input->get('returnTab')  ?: 'All';
$_returnPage = (int)($this->input->get('returnPage') ?: 1);
$_closeUrl   = trans_build_close_url('/salesreturns', $_returnTab, $_returnPage);
$_srMethod   = $JwtData->TransSettings->SalesReturnItemMethod ?? 'Manual';

if ($isEdit && !function_exists('buildSRPrefixSegment')) {
    function buildSRPrefixSegment($cfg) {
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

$editPrefixConfig = null;
if ($isEdit && !empty($PrefixData)) {
    foreach ($PrefixData as $_pd) {
        if ((int)$_pd->PrefixUID === (int)$SRData->PrefixUID) {
            $editPrefixConfig = $_pd;
            break;
        }
    }
    if (!$editPrefixConfig) $editPrefixConfig = $PrefixData[0];
}
$editTransNumber = $isEdit ? ($isDraftEdit ? (int)($NextNumberMap[(int)($editPrefixConfig->PrefixUID ?? 0)] ?? 1) : (int)$SRData->TransNumber) : 0;
$editPrefixSeg   = ($isEdit && $isDraftEdit) ? buildSRPrefixSegment($editPrefixConfig) : '';
?>

<?php
if ($isEdit) {
    $hNetAmt   = (float)($SRData->NetAmount  ?? 0);
    $hCurrency = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '&#8377;');
    $hDecimals = $JwtData->GenSettings->DecimalPoints ?? 2;
    $hStatus   = $SRData->DocStatus ?? '';
    $hStatusMap = ['Issued' => 'primary', 'Draft' => 'secondary', 'Cancelled' => 'danger', 'Rejected' => 'secondary'];
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
                        'id'           => $formId,
                        'name'         => $formId,
                        'autocomplete' => 'off',
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

                        <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2 trans-header-static trans-theme modal-header-center-sticky">
                            <div class="d-flex align-items-center gap-3" id="transHeaderInfo">
                                <?php $this->load->view('transactions/partials/form_back_button'); ?>
                                <div class="trans-doc-icon bg-danger bg-opacity-10">
                                    <i class="bx bx-undo text-danger" style="font-size:1.1rem;"></i>
                                </div>
                                <div>
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <?php if (!$isEdit): ?>
                                            <span class="fw-bold" style="font-size:.92rem;">Create Sales Return</span>
                                            <?php $this->load->view('transactions/partials/form_prefix_add'); ?>
                                        <?php else: ?>
                                            <span class="fw-bold" style="font-size:.92rem;"><?php echo $isDraftEdit ? '' : 'Edit'; ?> Sales Return</span>
                                            <?php if (!$isDraftEdit && !empty($SRData->UniqueNumber)): ?>
                                                <span class="trans-form-doc-number"><?php echo htmlspecialchars($SRData->UniqueNumber); ?></span>
                                                <span class="badge bg-label-<?php echo $hStatusClr; ?>" style="font-size:.7rem;"><?php echo $hStatus; ?></span>
                                            <?php endif; ?>
                                            <?php $this->load->view('transactions/partials/form_prefix_edit', [
                                                '_editPrefixUID'  => (int)($SRData->PrefixUID   ?? 0),
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
                                        <?php if (!empty($SRData->TransDate)): ?>
                                        <div class="d-flex align-items-center gap-1">
                                            <span style="font-size:.7rem;color:#8592a3;">Date</span>
                                            <span style="font-size:.78rem;color:#566a7f;"><?php echo htmlspecialchars(format_datedisplay($SRData->TransDate)); ?></span>
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
                                <?php else: ?>
                                    <?php if ($isDraftEdit): ?>
                                    <button type="submit" name="action" value="draft" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('tooltip_save_draft', 'Save and continue editing later'); ?>"><i class="bx bx-save me-1"></i><?php echo t('btn_save_draft', 'Save as Draft'); ?></button>
                                    <?php endif; ?>
                                    <button type="submit" name="action" value="save" class="btn btn-sm btn-primary px-3" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('tooltip_save', 'Save transaction'); ?>"><i class="bx bx-check me-1"></i>Save</button>
                                <?php endif; ?>
                                <?php $_hideNav = (int)($JwtData->TransSettings->HideNavOnTransForm ?? 0); ?>
                                <a href="<?php echo $_closeUrl; ?>" class="btn btn-sm btn-outline-danger px-3<?php echo $_hideNav ? ' d-none' : ''; ?>" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('tooltip_close', 'Return to list'); ?>"><i class="bx bx-x me-1"></i>Close</a>
                            </div>
                        </div>

                        <div class="card-body card-body-form-static p-3">

                            <!-- ── Toolbar: Type & Accepted At ─────────────────────────────── -->
                            <div class="d-flex align-items-center gap-4 mb-3 pb-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-muted" style="font-size:.78rem;white-space:nowrap;">Type</span>
                                    <?php if ($isEdit && !$isDraftEdit): ?>
                                    <?php $_srType = $SRData->DocType ?? 'Regular'; ?>
                                    <span class="trans-type-readonly"><?php echo $_srType === 'Without_GST' ? 'Without GST' : 'Regular'; ?></span>
                                    <input type="hidden" name="returnType" value="<?php echo htmlspecialchars($_srType); ?>" />
                                    <?php else: ?>
                                    <select class="form-select form-select-sm border-0 bg-transparent fw-semibold trans-gst-type-select"
                                            id="invoiceType" name="returnType" style="min-width:110px;cursor:pointer;">
                                        <option value="Regular"     <?php echo ($SRData->DocType ?? '') !== 'Without_GST' ? 'selected' : ''; ?>>Regular</option>
                                        <option value="Without_GST" <?php echo ($SRData->DocType ?? '') === 'Without_GST' ? 'selected' : ''; ?>>Without GST</option>
                                    </select>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($DispatchAddresses)): ?>
                                <div class="d-flex align-items-center gap-2 dispatch-from-grp" style="max-width:360px;">
                                    <span class="text-muted" style="font-size:.78rem;white-space:nowrap;">Accepted At</span>
                                    <?php $this->load->view('common/transactions/_dispatch_from'); ?>
                                </div>
                                <?php endif; ?>
                                <div class="ms-auto d-flex align-items-center gap-2">
                                    <div id="custTypeIndicator" class="d-none"></div>
                                    <div id="plChipWrap" class="d-none"></div>
                                    <div id="srOnAccountBadge" class="d-none"
                                         style="font-size:.78rem;color:#856404;background:#fff8e1;border:1px solid #ffc107;padding:3px 12px;border-radius:20px;white-space:nowrap;">
                                        <i class="bx bx-wallet" style="font-size:.88rem;"></i>
                                        On Account: <strong id="srOnAccountAmt" style="margin-left:3px;"></strong>
                                    </div>

                                </div>
                            </div>

                            <!-- ── Row 1: Customer | From Invoice | Return Date | Reference ── -->
                            <div class="row g-2 align-items-end mb-2">
                                <div class="col-md-4">
                                    <?php if ($isEdit && !$isDraftEdit): ?>
                                        <label class="trans-field-label mb-1">Customer</label>
                                        <select id="customerSearch" name="customerSearch" class="form-select form-select-sm"></select>
                                    <?php else: ?>
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <label for="customerSearch" class="trans-field-label mb-0">Customer <span class="text-danger">*</span></label>
                                        </div>
                                        <div class="input-group input-group-sm input-group-merge customer-search-group" id="customerGroup_customerSearch">
                                            <span class="input-group-text p-2 cursor-pointer" id="openCustomerSearchModal" style="background:#f0efff;border-color:#d9d8ff;color:#696cff;"><i class="icon-base bx bx-search"></i></span>
                                            <select id="customerSearch" name="customerSearch" class="form-select form-select-sm"></select>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php if ($_srMethod !== 'Manual'): ?>
                                <div class="col-md-3">
                                    <label for="fromInvoiceUID" class="trans-field-label">From Invoice</label>
                                    <select id="fromInvoiceUID" name="fromInvoiceUID" class="form-select form-select-sm" disabled>
                                        <option value="">-- Select Customer First --</option>
                                    </select>
                                </div>
                                <?php endif; ?>
                                <div class="col-auto" style="min-width:155px;">
                                    <label for="transDate" class="trans-field-label"><?php echo t('lbl_return_date', 'Return Date'); ?> <span class="text-danger">*</span></label>
                                    <?php $_fmt = $JwtData->GenSettings->FormDateFormat ?? 'd-m-Y'; ?>
                                    <?php if ($isEdit && !$isDraftEdit): ?>
                                        <input type="hidden" name="transDate" value="<?php echo htmlspecialchars(format_datedisplay($SRData->TransDate, 'Y-m-d')); ?>" />
                                        <div class="input-group input-group-sm input-group-merge">
                                            <span class="input-group-text bg-white"><i class="icon-base bx bx-calendar"></i></span>
                                            <input type="text" class="form-control form-control-sm bg-white text-muted" style="cursor:default;"
                                                value="<?php echo htmlspecialchars(format_datedisplay($SRData->TransDate, $_fmt)); ?>" readonly tabindex="-1" />
                                        </div>
                                    <?php else: ?>
                                        <div class="input-group input-group-sm input-group-merge">
                                            <span class="input-group-text bg-white"><i class="icon-base bx bx-calendar"></i></span>
                                            <input type="text" class="form-control form-control-sm bg-white" id="transDate_disp" readonly="readonly"
                                                value="<?php echo $isEdit ? format_datedisplay($SRData->TransDate, $_fmt) : format_datedisplay(time(), $_fmt); ?>"
                                                required />
                                            <input type="hidden" id="transDate" name="transDate" value="<?php echo $isEdit ? htmlspecialchars(format_datedisplay($SRData->TransDate, 'Y-m-d')) : format_datedisplay(time(), 'Y-m-d'); ?>" />
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col">
                                    <label for="referenceDetails" class="trans-field-label"><?php echo t('lbl_reference', 'Reference'); ?></label>
                                    <input type="text" id="referenceDetails" name="referenceDetails" class="form-control form-control-sm"
                                        placeholder="Invoice No, Ref No..." maxlength="100"
                                        value="<?php echo $isEdit ? htmlspecialchars($SRData->Reference ?? '') : ''; ?>" />
                                </div>
                            </div>

                            <div id="customerAddressBox" class="trans-addr-strip d-none"><i class="bx bx-map-pin"></i><span></span></div>
                            <hr class="mt-3"/>

                            <?php $this->load->view('transactions/partials/form_products_add', [
                                'transProductSectionTitle' => 'Returned Products',
                                'transNotesPlaceholder'    => 'Enter notes or reason for return',
                                'transShowDropzone'        => true,
                                'transHideAddProduct'      => true,
                                'transHideProductSearch'    => $_srMethod === 'Automatic',
                                'transNotesContent'        => $isEdit ? ($SRData->Notes ?? '') : '',
                                'transTermsContent'        => $isEdit ? ($SRData->TermsConditions ?? '') : ($JwtData->TransSettings->TermsAndConditions ?? ''),
                                'transSignatureUID'        => $isEdit ? (int)($SRData->SignatureUID ?? 0) : 0,
                                'transSignatures'          => $JwtData->User->Signatures ?? [],
                                'transPaymentVars'         => !$isEdit ? [
                                    'PaymentTypes'     => $PaymentTypes ?? [],
                                    'BankAccounts'     => $BankAccounts ?? [],
                                    'JwtData'          => $JwtData,
                                    'paymentPartyType' => 'C',
                                ] : null,
                                'transEditItems'           => $isEdit ? ($SRItems ?? []) : [],
                            ]); ?>

                            <!-- ── Inline full-width summary ────────────────────────────────── -->
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

                    <!-- ── Sticky bottom summary bar ────────────────────────────────── -->
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
            <?php $this->load->view('common/modals/customer_form'); ?>
            <?php $this->load->view('transactions/modals/invoice_items_select'); ?>
            <?php $this->load->view('transactions/partials/form_common_modals'); ?>

        </div>

    </div>
</div>

<?php $this->load->view('transactions/partials/additional_charges_modal'); ?>
<?php $this->load->view('common/imagepreview_modal'); ?>
<?php $this->load->view('common/transactions/footer'); ?>

<script src="/js/common/address.js"></script>
<script src="/js/common/bankdetails.js"></script>
<script src="/js/common/gstin_fetch.js"></script>
<script src="/js/common/customer_form.js"></script>
<script src="/js/transactions/salesreturns.js"></script>
<script src="/js/transactions/transactions.js"></script>

<script>window.R2K_CUST_HIDE_CREATE = true;</script>
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
    'isEdit'         => $isEdit,
    'isDraftEdit'    => $isDraftEdit,
    'moduleUID'      => 106,
    'enableStorage'  => (bool)$JwtData->GenSettings->EnableStorage,
    'formId'         => $formId,
    'formAction'     => $formAction,
    'upstashUrl'     => $UpstashReadUrl   ?? '',
    'upstashToken'   => $UpstashReadToken ?? '',
    'custCacheKey'   => $CustomerCacheKey ?? '',
    'returnTab'      => $_returnTab,
    'returnPage'     => (int)$_returnPage,
    'currency'       => $JwtData->GenSettings->CurrenySymbol ?? '₹',
    'decimals'       => (int)($JwtData->GenSettings->DecimalPoints ?? 2),
    'listDateFormat' => $JwtData->GenSettings->ListDateFormat ?? 'd M Y',
    'srItemMethod'   => $_srMethod,
    'editData'       => $isEdit ? [
        'transUID'          => $transUID,
        'custUID'           => (int)($SRData->PartyUID ?? 0),
        'custName'          => $SRData->PartyName  ?? '',
        'custArea'          => $SRData->PartyArea   ?? '',
        'custMobile'        => $SRData->PartyMobile ?? '',
        'extraDiscAmount'   => (float)($SRData->ExtraDiscount ?? 0),
        'extraDiscType'     => $SRData->ExtraDiscountType ?? '',
        'globalDiscPercent' => (float)($SRData->GlobalDiscPercent ?? 0),
        'attachments'       => $SRAttachments ?? [],
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
                'mrp'             => (float)($item->MRP ?? 0),
                'purchasePriceIsIncl' => (bool)($item->IsPurchasePriceIncl ?? 1),
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
        }, $SRItems ?? []),
    ] : null,
]); ?>;
</script>
<script src="/js/transactions/forms/salesreturn.js"></script>
