<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$isEdit      = isset($SRData);
$isDraftEdit = $isEdit && ($SRData->DocStatus === 'Draft');
$transUID    = $isEdit ? (int)$SRData->TransUID : 0;
$formId      = 'srForm';
$formAction  = $isEdit ? 'salesreturns/updateSalesReturn' : 'salesreturns/addSalesReturn';
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
                    <input type="hidden" id="placeOfSupplyCode" name="placeOfSupplyCode" value="<?php echo !$isEdit ? htmlspecialchars($JwtData->Org->StateCode ?? '', ENT_QUOTES) : ''; ?>" />
                    <input type="hidden" id="placeOfSupplyName" name="placeOfSupplyName" value="<?php echo !$isEdit ? htmlspecialchars($JwtData->Org->StateName ?? '', ENT_QUOTES) : ''; ?>" />

                    <div class="card mb-3">

                        <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2 trans-header-static trans-theme modal-header-center-sticky">
                            <div class="d-flex align-items-center gap-3" id="transHeaderInfo">
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
                                            <div class="d-flex align-items-center gap-1 <?php echo (!$isDraftEdit ? 'd-none' : ''); ?>">
                                                <div class="input-group w-auto">
                                                    <select id="transPrefixSelect" name="transPrefixSelect" class="select2 form-select form-select-sm" <?php echo (!$isDraftEdit ? 'disabled' : 'required'); ?>>
                                                        <?php try {
                                                            if (empty($PrefixData)) throw new Exception('Prefix data not loaded');
                                                            foreach ($PrefixData as $preData) {
                                                                $isSelected = (int)$preData->PrefixUID === (int)$SRData->PrefixUID ? 'selected' : '';
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
                                                    <input type="number" id="transNumber" name="transNumber" class="form-control transAutoGenNumber stop-incre-indicator" maxLength="20"
                                                        onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))"
                                                        oninput="this.value=this.value.slice(0,this.maxLength)"
                                                        pattern="[0-9]*" value="<?php echo $editTransNumber; ?>"
                                                        <?php echo (!$isDraftEdit ? 'disabled' : 'required'); ?> />
                                                </div>
                                            </div>
                                            <?php if (!$isDraftEdit): ?>
                                            <input type="hidden" name="transPrefixSelect" value="<?php echo (int)$SRData->PrefixUID; ?>" />
                                            <input type="hidden" name="transNumber" value="<?php echo (int)$SRData->TransNumber; ?>" />
                                            <?php endif; ?>
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
                                <?php else: ?>
                                    <?php if ($isDraftEdit): ?>
                                    <button type="submit" name="action" value="draft" class="btn btn-sm btn-outline-secondary"><i class="bx bx-save me-1"></i>Draft</button>
                                    <?php endif; ?>
                                    <button type="submit" name="action" value="save" class="btn btn-sm btn-primary px-3"><i class="bx bx-check me-1"></i><?php echo $isDraftEdit ? 'Save' : 'Update'; ?></button>
                                <?php endif; ?>
                                <?php $_hideNav = (int)($JwtData->TransSettings->HideNavOnTransForm ?? 0); ?>
                                <a href="/salesreturns" class="btn btn-sm btn-outline-danger px-3<?php echo $_hideNav ? ' d-none' : ''; ?>"><i class="bx bx-x me-1"></i>Close</a>
                            </div>
                        </div>

                        <div class="card-body card-body-form-static p-3">

                            <!-- ── Toolbar: Type & Accepted At ─────────────────────────────── -->
                            <div class="d-flex align-items-center gap-4 mb-3 pb-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-muted" style="font-size:.78rem;white-space:nowrap;">Type</span>
                                    <select class="form-select form-select-sm border-0 bg-transparent fw-semibold"
                                            id="invoiceType" name="returnType" style="min-width:110px;cursor:pointer;"
                                            <?php echo ($isEdit && !$isDraftEdit) ? 'disabled' : ''; ?>>
                                        <option value="Regular"     <?php echo ($SRData->QuotationType ?? '') !== 'Without_GST' ? 'selected' : ''; ?>>Regular</option>
                                        <option value="Without_GST" <?php echo ($SRData->QuotationType ?? '') === 'Without_GST' ? 'selected' : ''; ?>>Without GST</option>
                                    </select>
                                    <?php if ($isEdit && !$isDraftEdit): ?>
                                    <input type="hidden" name="returnType" value="<?php echo htmlspecialchars($SRData->QuotationType ?? 'Regular'); ?>" />
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
                                        <div class="trans-vendor-card">
                                            <div class="trans-vendor-card-name">
                                                <i class="bx bx-user me-1"></i><?php echo htmlspecialchars($SRData->PartyName ?? '—'); ?>
                                            </div>
                                        </div>
                                        <input type="hidden" id="customerSearch" name="customerSearch" value="<?php echo (int)$SRData->PartyUID; ?>" />
                                    <?php else: ?>
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <label for="customerSearch" class="trans-field-label mb-0">Customer <span class="text-danger">*</span></label>
                                        </div>
                                        <select id="customerSearch" name="customerSearch" class="form-select form-select-sm"></select>
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
                                    <label for="transDate" class="trans-field-label">Return Date <span class="text-danger">*</span></label>
                                    <?php if ($isEdit && !$isDraftEdit): ?>
                                        <input type="hidden" name="transDate" value="<?php echo htmlspecialchars(format_datedisplay($SRData->TransDate, 'Y-m-d')); ?>" />
                                        <div class="input-group input-group-sm input-group-merge">
                                            <span class="input-group-text bg-white"><i class="icon-base bx bx-calendar"></i></span>
                                            <input type="text" class="form-control form-control-sm bg-white text-muted" style="cursor:default;"
                                                value="<?php echo htmlspecialchars(format_datedisplay($SRData->TransDate)); ?>" readonly tabindex="-1" />
                                        </div>
                                    <?php else: ?>
                                        <div class="input-group input-group-sm input-group-merge">
                                            <span class="input-group-text bg-white"><i class="icon-base bx bx-calendar"></i></span>
                                            <input type="text" class="form-control form-control-sm bg-white" id="transDate" name="transDate" readonly="readonly"
                                                value="<?php echo $isEdit ? htmlspecialchars(format_datedisplay($SRData->TransDate, 'Y-m-d')) : format_datedisplay(time(), 'Y-m-d'); ?>"
                                                required />
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col">
                                    <label for="referenceDetails" class="trans-field-label">Reference</label>
                                    <input type="text" id="referenceDetails" name="referenceDetails" class="form-control form-control-sm"
                                        placeholder="Invoice No, Ref No..." maxlength="100"
                                        value="<?php echo $isEdit ? htmlspecialchars($SRData->Reference ?? '') : ''; ?>" />
                                </div>
                            </div>

                            <!-- Address box (below customer column) -->
                            <div class="row g-2 mb-3">
                                <div class="col-md-4">
                                    <div id="customerAddressBox" class="p-2 border border-secondary trans-border-dotted rounded small d-none"></div>
                                </div>
                            </div>
                            <hr class="mt-2 mb-3"/>

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
            <?php $this->load->view('common/modals/customer_form'); ?>
            <?php $this->load->view('transactions/modals/invoice_items_select'); ?>
            <?php $this->load->view('transactions/modals/taxdetails'); ?>
            <?php $this->load->view('common/modals/category_form'); ?>
            <?php $this->load->view('common/modals/product_form'); ?>
            <?php $this->load->view('common/footer_desc'); ?>

        </div>

    </div>
</div>

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

<script>
const EnableStorage = <?php echo $JwtData->GenSettings->EnableStorage; ?>;

var _isEdit       = <?php echo $isEdit ? 'true' : 'false'; ?>;
var _orgDateFmt   = '<?php echo addslashes($JwtData->GenSettings->ListDateFormat ?? 'd M Y'); ?>';
var _upstashUrl       = '<?php echo addslashes($UpstashReadUrl   ?? ''); ?>';
var _upstashReadToken = '<?php echo addslashes($UpstashReadToken ?? ''); ?>';
var _custCacheKey     = '<?php echo addslashes($CustomerCacheKey ?? ''); ?>';
var _srItemMethod     = '<?php echo $_srMethod; ?>';

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
}, $SRItems)); ?>;
<?php endif; ?>

$(function() {
    'use strict'

    <?php if (!$isEdit || $isDraftEdit): ?>
    window._custSearchHideCreate = true;
    searchCustomers('customerSearch');
    <?php endif; ?>
    transDatePickr('#transDate', false, 'Y-m-d', false, true, true, true, 'd-m-Y');

    // Load invoices when customer is selected (only in Automatic / Both modes)
    // Fix 4: show On Account balance next to cust-type indicator
    $('#customerSearch').on('select2:select', function(e) {
        var custUID = parseInt($(this).val(), 10);
        if (!custUID || custUID <= 0) return;
        if (_srItemMethod !== 'Manual') loadCustomerInvoices(custUID);
        var data = e.params && e.params.data ? e.params.data : {};
        _showSROnAccountBadge(data.onAccountBalance || 0);
    }).on('select2:clear', function() {
        if (_srItemMethod !== 'Manual') resetInvoiceDropdown();
        $('#srOnAccountBadge').addClass('d-none');
    });

    function loadCustomerInvoices(custUID) {
        var $inv = $('#fromInvoiceUID');
        $inv.prop('disabled', true).html('<option value="">Loading...</option>');
        AjaxLoading = 0;
        $.ajax({
            url    : '/salesreturns/getCustomerInvoices',
            method : 'POST',
            data   : { CustomerUID: custUID, [CsrfName]: CsrfToken },
            success: function(res) {
                AjaxLoading = 1;
                if (res.Error || !res.Invoices || res.Invoices.length === 0) {
                    $inv.html('<option value="">-- No Invoices Found --</option>').prop('disabled', true);
                    return;
                }
                var _months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                var opts = '<option value="">-- Select Invoice --</option>';
                res.Invoices.forEach(function(inv) {
                    var d = inv.TransDate ? inv.TransDate.split('-') : [];
                    var dateLabel = (function() {
                        if (d.length !== 3) return inv.TransDate || '';
                        var fmt = _orgDateFmt || 'd M Y';
                        return fmt.replace('Y', d[0]).replace('m', d[1]).replace('d', d[2]).replace('M', _months[parseInt(d[1],10)-1]);
                    })();
                    var label = inv.UniqueNumber + ' | ' + dateLabel + ' | \u20b9' + parseFloat(inv.NetAmount).toFixed(2);
                    opts += '<option value="' + inv.TransUID + '">' + label + '</option>';
                });
                $inv.html(opts).prop('disabled', false);
            },
            error: function() {
                AjaxLoading = 1;
                $inv.html('<option value="">-- Error Loading --</option>').prop('disabled', true);
            }
        });
    }

    function resetInvoiceDropdown() {
        $('#fromInvoiceUID').html('<option value="">-- Select Customer First --</option>').prop('disabled', true);
    }

    function _showSROnAccountBadge(balance) {
        var $badge = $('#srOnAccountBadge');
        if (!$badge.length || !(parseFloat(balance) > 0)) { $badge.addClass('d-none'); return; }
        var cur = (typeof genSettings !== 'undefined' && genSettings.CurrenySymbol) ? genSettings.CurrenySymbol : '\u20b9';
        $('#srOnAccountAmt').text(cur + ' ' + parseFloat(balance).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        $badge.removeClass('d-none');
    }

    // ── Invoice items modal ──────────────────────────────────────────────────
    var _invoiceItems   = [];
    var _lastInvoiceUID = 0;
    var _invoiceTotalItems  = {}; // { transUID: totalItemCount }
    var _invoiceProductUIDs = {}; // { transUID: [productUID, ...] }

    // Open modal on invoice select; also re-open if same invoice clicked again
    $('#fromInvoiceUID').on('change', function() {
        var transUID = parseInt($(this).val(), 10);
        if (!transUID || transUID <= 0) return;
        _lastInvoiceUID = transUID;
        openInvoiceItemsModal(transUID, $(this).find('option:selected').text());
    });

    // Re-open modal when user clicks the already-selected invoice
    $('#fromInvoiceUID').on('mousedown', function() {
        $(this).data('pre-click-val', $(this).val());
    }).on('click', function() {
        var preVal = parseInt($(this).data('pre-click-val'), 10);
        var curVal = parseInt($(this).val(), 10);
        if (preVal && preVal === curVal && curVal > 0) {
            openInvoiceItemsModal(curVal, $(this).find('option:selected').text());
        }
    });

    function openInvoiceItemsModal(transUID, invoiceLabel) {
        _invoiceItems = [];
        $('#invItemsLoading').removeClass('d-none');
        $('#invItemsTableWrap').addClass('d-none');
        $('#invItemsTableBody').empty();
        $('#invItemsSelectAll').prop('checked', true).prop('disabled', false);
        $('#invItemsSelectedCount').text('0');
        $('#invItemsAddToCart').prop('disabled', true);
        $('#invItemsModalSubtitle').text(invoiceLabel);
        $('#invoiceItemsModal').modal('show');

        AjaxLoading = 0;
        $.ajax({
            url    : '/salesreturns/getInvoiceItems',
            method : 'POST',
            data   : { TransUID: transUID, [CsrfName]: CsrfToken },
            success: function(res) {
                AjaxLoading = 1;
                $('#invItemsLoading').addClass('d-none');
                if (res.Error || !res.Items || res.Items.length === 0) {
                    $('#invItemsTableBody').html('<tr><td colspan="7" class="text-center text-muted py-4">No items found in this invoice.</td></tr>');
                    $('#invItemsTableWrap').removeClass('d-none');
                    return;
                }
                _invoiceItems = res.Items;
                _invoiceTotalItems[_lastInvoiceUID]  = res.Items.length;
                _invoiceProductUIDs[_lastInvoiceUID] = res.Items.map(function(i) { return parseInt(i.ProductUID, 10); });
                var cur = (typeof genSettings !== 'undefined' && genSettings.CurrenySymbol) ? genSettings.CurrenySymbol : '\u20b9';
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
                    var inCart   = (typeof billManager !== 'undefined' && billManager.getItemById(item.ProductUID) !== null);
                    if (!inCart) availCount++;
                    var rowClass = inCart ? 'table-secondary' : '';
                    var chkAttr  = inCart ? 'disabled title="Already added to cart"' : 'checked';
                    rows += '<tr class="' + rowClass + '" data-idx="' + idx + '">' +
                        '<td><input type="checkbox" class="form-check-input inv-item-chk" data-idx="' + idx + '" data-transproduid="' + (parseInt(item.TransProdUID, 10) || 0) + '" ' + chkAttr + '></td>' +
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
                $('#invItemsTableBody').html(rows);
                $('#invItemsTableWrap').removeClass('d-none');
                // If all items already in cart, disable select-all header
                $('#invItemsSelectAll').prop('checked', availCount > 0).prop('disabled', availCount === 0);
                updateInvItemsFooter();
            },
            error: function() {
                AjaxLoading = 1;
                $('#invItemsLoading').addClass('d-none');
                $('#invItemsTableBody').html('<tr><td colspan="7" class="text-center text-danger py-4">Failed to load invoice items.</td></tr>');
                $('#invItemsTableWrap').removeClass('d-none');
            }
        });
    }

    // Select all checkbox
    $(document).on('change', '#invItemsSelectAll', function() {
        $('#invItemsTableBody .inv-item-chk:not(:disabled)').prop('checked', $(this).is(':checked'));
        updateInvItemsFooter();
    });

    // Individual checkbox
    $(document).on('change', '.inv-item-chk', function() {
        var total    = $('#invItemsTableBody .inv-item-chk:not(:disabled)').length;
        var selected = $('#invItemsTableBody .inv-item-chk:not(:disabled):checked').length;
        $('#invItemsSelectAll').prop('checked', total > 0 && selected === total);
        updateInvItemsFooter();
    });

    function updateInvItemsFooter() {
        var count = $('#invItemsTableBody .inv-item-chk:not(:disabled):checked').length;
        $('#invItemsSelectedCount').text(count);
        $('#invItemsAddToCart').prop('disabled', count === 0);
    }

    // Add selected items to cart
    $(document).on('click', '#invItemsAddToCart', function() {
        var added = 0;
        $('#invItemsTableBody .inv-item-chk:checked').each(function() {
            var idx  = parseInt($(this).data('idx'), 10);
            var item = _invoiceItems[idx];
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
                var qty = parseFloat(item.RemainingQty > 0 ? item.RemainingQty : item.Quantity) || 1;
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

        $('#invoiceItemsModal').modal('hide');

        // Reset invoice dropdown after converting to cart
        var $inv = $('#fromInvoiceUID');
        $inv.val(null).trigger('change');
        _lastInvoiceUID = 0;
    });

    // Reset invoice dropdown when modal is dismissed without adding to cart (Fix 2)
    $('#invoiceItemsModal').on('hidden.bs.modal', function() {
        var $inv = $('#fromInvoiceUID');
        if ($inv.val()) {
            $inv.val(null).trigger('change');
        }
        _lastInvoiceUID = 0;
    });

    <?php if ($isEdit): ?>
    initTransAttachments(<?php echo $transUID; ?>, '/transactions/getAttachments', 106);

    <?php if ($isDraftEdit && !empty($SRData->PartyUID)): ?>
    $('#customerSearch').append(new Option(
        '<?php echo addslashes($SRData->PartyName ?? ''); ?>',
        <?php echo (int)$SRData->PartyUID; ?>, true, true
    )).trigger('change');
    <?php elseif (!$isDraftEdit && !empty($SRData->PartyUID)): ?>
    if (_srItemMethod !== 'Manual') loadCustomerInvoices(<?php echo (int)$SRData->PartyUID; ?>);
    <?php endif; ?>

    $('#extraDiscount').val('<?php echo smartDecimal($SRData->ExtraDiscount ?? 0); ?>');
    $('#extDiscountType').val('<?php echo addslashes($SRData->ExtraDiscountType ?? ''); ?>').trigger('change');
    $('#globalDiscount').val('<?php echo smartDecimal($SRData->GlobalDiscPercent ?? 0); ?>').trigger('input');

    if (typeof billManager !== 'undefined' && typeof formationTableBillItems === 'function' && _editItems.length > 0) {
        $(document).one('billmanager:ready', function() {
            formationTableBillItems(_editItems);
        });
    }
    <?php endif; ?>

    var $form = $('#<?php echo $formId; ?>');
    if ($form.length) {

        $form.on('submit', function(e) {
            e.preventDefault();

            var $btn     = $('button[type="submit"][name="action"]:focus, button[type="submit"][name="action"].active-submit', $form);
            var action   = $btn.val() || 'save';
            var csrfName = $form.data('csrf');
            var csrfVal  = $form.data('csrf-value');

            var customerUID = parseInt($('#customerSearch').val(), 10);
            if (!customerUID || customerUID <= 0) return showFormError('Please select a customer.');

            if (!_isEdit && action !== 'draft') {
                var prefixUID = parseInt($('#transPrefixSelect').val(), 10);
                if (!prefixUID || prefixUID <= 0) return showFormError('Please select a sales return prefix.');

                var transNumber = $.trim($('#transNumber').val());
                if (!transNumber || parseInt(transNumber, 10) <= 0) return showFormError('Transaction number must be greater than 0.');
            }

            var transDate = $.trim($('#transDate').val());
            if (!transDate || !/^\d{4}-\d{2}-\d{2}$/.test(transDate)) return showFormError('Please enter a valid return date.');

            var items = typeof billManager !== 'undefined' ? billManager.getAllItems() : [];
            if (!items || items.length === 0) return showFormError('Please add at least one product.');

            if (!_isEdit) {
                for (var i = 0; i < items.length; i++) {
                    var item = items[i];
                    var qty  = parseFloat(item.quantity);
                    if (!qty || qty <= 0) return showFormError('Row ' + (i + 1) + ': Quantity must be greater than 0.');
                    if (parseFloat(item.unitPrice) < 0) return showFormError('Row ' + (i + 1) + ': Price cannot be negative.');
                }
            }

            var bm            = typeof billManager !== 'undefined' ? billManager : null;
            var summary       = bm ? bm.summary : {};
            var netAmount     = summary.totals    ? (summary.totals.grandTotal       || 0) : 0;
            var subTotal      = summary.items     ? (summary.items.taxableAmount     || 0) : 0;
            var discountAmt   = summary.items     ? (summary.items.discountTotal     || 0) : 0;
            var taxAmt        = summary.taxTotals ? (summary.taxTotals.totalTax      || 0) : 0;
            var cgstAmt       = summary.taxTotals ? (summary.taxTotals.cgstTotal     || 0) : 0;
            var sgstAmt       = summary.taxTotals ? (summary.taxTotals.sgstTotal     || 0) : 0;
            var igstAmt       = summary.taxTotals ? (summary.taxTotals.igstTotal     || 0) : 0;
            var addCharges    = (summary.additionalCharges && summary.additionalCharges.total) ? (summary.additionalCharges.total.grossAmount || 0) : 0;
            var globalDiscPct = bm ? (bm.globalDiscountPercent || 0) : 0;
            var roundOff      = summary.extra ? (summary.extra.roundOff || 0) : 0;
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

            var postData = $.extend({
                transPrefixSelect      : parseInt($('#transPrefixSelect').val(), 10) || 0,
                transNumber            : $.trim($('#transNumber').val()),
                transDate              : transDate,
                customerSearch         : customerUID,
                fromInvoiceUID         : parseInt($('#fromInvoiceUID').val(), 10) || 0,
                returnType             : $('[name="returnType"]').val() || 'Regular',
                dispatchFrom           : $('[name="dispatchFrom"]').val() || '',
                referenceDetails       : $.trim($('#referenceDetails').val()),
                transNotes             : $.trim($('#transNotes').val()),
                transTermsCond         : $.trim($('#transTermsCond').val()),
                placeOfSupplyCode      : $('#placeOfSupplyCode').val() || '',
                placeOfSupplyName      : $('#placeOfSupplyName').val() || '',
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
                SignatureUID           : parseInt($('#transSignatureUID').val(), 10) || 0,
                action                 : action,
                [csrfName]             : csrfVal,
            }, charges);

            if (_isEdit) {
                postData.TransUID = parseInt($('input[name="TransUID"]').val(), 10);
            }

            var formData = new FormData();
            $.each(postData, function(k, v) { formData.append(k, v); });
            if (typeof multiDropzone !== 'undefined' && multiDropzone.files.length > 0) {
                multiDropzone.files.forEach(function(f) { formData.append('AttachFiles[]', f); });
            }
            if (_isEdit) {
                formData.append('RemovedAttachIDs', JSON.stringify(typeof _removedAttachIDs !== 'undefined' ? _removedAttachIDs : []));
            }
            if (!_isEdit && action !== 'draft') {
                if (typeof getPaymentAttachmentFiles === 'function') {
                    var _payFiles = getPaymentAttachmentFiles();
                    if (_payFiles && _payFiles.length > 0) {
                        var _totalPayAmt = 0;
                        $('#paymentRowsBody tr').each(function() {
                            _totalPayAmt += parseFloat($(this).find('.pay-amount-inp').val()) || 0;
                        });
                        if (_totalPayAmt <= 0) return showFormError('Payment attachments are added but no payment amount is entered.');
                    }
                }
                if (!serializePaymentRows()) return showFormError('Please enter a valid amount for every payment row.');
                formData.append('PaymentRows',   $('#PaymentRowsJson').val() || '');
                formData.append('IsFullyPaid',   $('#isFullyPaid').is(':checked') ? 1 : 0);
                formData.append('RecordPayment', 1);
                if (typeof getPaymentAttachmentFiles === 'function') {
                    getPaymentAttachmentFiles().forEach(function(f) { formData.append('PaymentFiles[]', f); });
                }
            }

            setFormLoading('#<?php echo $formId; ?>', true, action);

            $.ajax({
                url         : '/<?php echo $formAction; ?>',
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
                            title            : _isEdit ? 'Sales Return Updated' : 'Sales Return Saved',
                            text             : response.Message || (_isEdit ? 'Sales return updated successfully.' : 'Sales return created successfully.'),
                            confirmButtonText: 'OK',
                            timer            : 3000,
                            timerProgressBar : true,
                        }).then(function() {
                            window.location.href = '/salesreturns';
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
// ── Sticky bottom bar — total sync + button delegation ─────────────────────
(function() {
    var cur = '<?php echo addslashes($JwtData->GenSettings->CurrenySymbol ?? "₹"); ?>';
    var dec = <?php echo (int)($JwtData->GenSettings->DecimalPoints ?? 2); ?>;

    function _alignStickyBar() {
        var bar  = document.getElementById('stickyBottomBar');
        var form = document.getElementById('<?php echo $formId; ?>');
        if (bar && form) {
            var rect        = form.getBoundingClientRect();
            var clientWidth = document.documentElement.clientWidth;
            bar.style.left  = Math.round(rect.left)               + 'px';
            bar.style.right = Math.round(clientWidth - rect.right) + 'px';
        }
    }
    document.addEventListener('DOMContentLoaded', _alignStickyBar);
    window.addEventListener('resize', _alignStickyBar);

    document.addEventListener('DOMContentLoaded', function() {
        _alignStickyBar();
        var inlineBar = document.getElementById('inlineSummaryBar');
        if (inlineBar) {
            new IntersectionObserver(function(entries) {
                var sticky = document.getElementById('stickyBottomBar');
                if (sticky) sticky.style.display = entries[0].isIntersecting ? 'none' : 'flex';
            }, { threshold: 0.1 }).observe(inlineBar);
        }
    });

    function _r2(v) { return Math.round(v * 100) / 100; }

    function _toggleGroup(ids, show, amtIds, amtValue) {
        ids.forEach(function(id) {
            var el = document.getElementById(id);
            if (!el) return;
            el.classList.toggle('d-none', !show);
            el.classList.toggle('d-flex', show);
        });
        if (amtIds) amtIds.forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.textContent = cur + ' ' + (amtValue || 0).toFixed(dec);
        });
    }

    function _syncStickyTotals() {
        if (typeof billManager === 'undefined') return;

        var grand = billManager.summary && billManager.summary.totals
            ? (billManager.summary.totals.grandTotal || 0) : 0;
        var tax = billManager.summary && billManager.summary.taxTotals
            ? (billManager.summary.taxTotals.totalTax || 0) : 0;

        var rowsPaid = 0;
        document.querySelectorAll('#paymentRowsBody tr .pay-amount-inp').forEach(function(inp) {
            rowsPaid += parseFloat(inp.value) || 0;
        });

        var oaPaid = 0;
        try {
            var oaEl = document.getElementById('OnAccountApplyJson');
            if (oaEl && oaEl.value) {
                (JSON.parse(oaEl.value) || []).forEach(function(x) { oaPaid += parseFloat(x.ApplyAmount) || 0; });
            }
        } catch(e) {}

        var paid    = _r2(rowsPaid + oaPaid);
        var balance = grand > 0 ? Math.max(0, _r2(grand - paid)) : 0;
        var excess  = grand > 0 ? Math.max(0, _r2(paid - grand)) : 0;

        ['stickyGrandTotal','inlineGrandTotal'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.textContent = cur + ' ' + grand.toFixed(dec);
        });
        ['stickyTotalTax','inlineTotalTax'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.textContent = cur + ' ' + tax.toFixed(dec);
        });

        _toggleGroup(['stickyPaidGroup','inlinePaidGroup'],
            paid > 0, ['stickyTotalPaid','inlineTotalPaid'], paid);
        _toggleGroup(['stickyBalanceGroup','inlineBalanceGroup'],
            grand > 0 && balance > 0 && excess === 0,
            ['stickyBalanceAmt','inlineBalanceAmt'], balance);
        _toggleGroup(['stickyExcessGroup','inlineExcessGroup'],
            excess > 0, ['stickyExcessAmt','inlineExcessAmt'], excess);
    }

    document.addEventListener('DOMContentLoaded', function() {
        var billTotEl = document.querySelector('.bill_tot_amt');
        if (billTotEl) {
            new MutationObserver(function() {
                if (typeof updatePaymentSummary === 'function') updatePaymentSummary();
                _syncStickyTotals();
            }).observe(billTotEl, { childList: true, characterData: true, subtree: true });
        }

        document.addEventListener('input', function(e) {
            if (e.target && e.target.classList.contains('pay-amount-inp')) _syncStickyTotals();
        });

        var oaJsonEl = document.getElementById('OnAccountApplyJson');
        if (oaJsonEl) {
            new MutationObserver(_syncStickyTotals).observe(oaJsonEl, { attributes: true, attributeFilter: ['value'] });
        }

        _syncStickyTotals();

        var d = document.getElementById('stickyDraftBtn');
        var s = document.getElementById('stickySaveBtn');
        if (d) d.addEventListener('click', function() { var o = document.querySelector('[name="action"][value="draft"]'); if (o) o.click(); });
        if (s) s.addEventListener('click', function() { var o = document.querySelector('[name="action"][value="save"]');  if (o) o.click(); });
        document.querySelectorAll('[data-sticky-action]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var o = document.querySelector('[name="action"][value="' + this.getAttribute('data-sticky-action') + '"]');
                if (o) o.click();
            });
        });

        var id = document.getElementById('inlineDraftBtn');
        var is = document.getElementById('inlineSaveBtn');
        if (id) id.addEventListener('click', function() { var o = document.querySelector('[name="action"][value="draft"]'); if (o) o.click(); });
        if (is) is.addEventListener('click', function() { var o = document.querySelector('[name="action"][value="save"]');  if (o) o.click(); });
        document.querySelectorAll('[data-inline-action]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var o = document.querySelector('[name="action"][value="' + this.getAttribute('data-inline-action') + '"]');
                if (o) o.click();
            });
        });
    });
}());
</script>
