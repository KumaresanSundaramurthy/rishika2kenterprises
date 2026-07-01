<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$isEdit      = isset($InvData);
$isDraftEdit = $isEdit && ($InvData->DocStatus === 'Draft');
$transUID    = $isEdit ? (int)$InvData->TransUID : 0;
$formId      = 'invForm';
$formAction  = $isEdit ? 'invoices/updateInvoice' : 'invoices/addInvoice';

if ($isEdit && !function_exists('buildInvPrefixSegment')) {
    function buildInvPrefixSegment($cfg) {
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
        if ((int)$_pd->PrefixUID === (int)$InvData->PrefixUID) {
            $editPrefixConfig = $_pd;
            break;
        }
    }
    if (!$editPrefixConfig) $editPrefixConfig = $PrefixData[0];
}
$editTransNumber = $isEdit ? ($isDraftEdit ? (int)($NextNumberMap[(int)($editPrefixConfig->PrefixUID ?? 0)] ?? 1) : (int)$InvData->TransNumber) : 0;
$editPrefixSeg   = ($isEdit && $isDraftEdit) ? buildInvPrefixSegment($editPrefixConfig) : '';

$_addrLines = [];
if (!empty($DispatchAddress)) {
    $_addrLines = array_filter([
        htmlspecialchars($DispatchAddress->Line1 ?? ''),
        htmlspecialchars($DispatchAddress->Line2 ?? ''),
    ]);
    $_cityPin = trim(implode(' - ', array_filter([
        htmlspecialchars($DispatchAddress->CityText ?? ''),
        htmlspecialchars($DispatchAddress->Pincode  ?? ''),
    ])));
    if ($_cityPin) $_addrLines[] = $_cityPin;
    if (!empty($DispatchAddress->StateText)) $_addrLines[] = htmlspecialchars($DispatchAddress->StateText);
}

$_notesVal = '';
$_jwtTerms = $JwtData->TransSettings->TermsAndConditions ?? '';
$_termsVal = $_jwtTerms;
if (!$isEdit) {
    if (!empty($SalesOrderData->Notes)) $_notesVal = $SalesOrderData->Notes;
    elseif (!empty($QuotationData->Notes)) $_notesVal = $QuotationData->Notes;
    elseif (!empty($ChallanData->Notes)) $_notesVal = $ChallanData->Notes;
    if (!empty($SalesOrderData->TermsConditions)) $_termsVal = $SalesOrderData->TermsConditions;
    elseif (!empty($QuotationData->TermsConditions)) $_termsVal = $QuotationData->TermsConditions;
    elseif (!empty($ChallanData->TermsConditions)) $_termsVal = $ChallanData->TermsConditions;
} else {
    $_notesVal = $InvData->Notes ?? '';
    $_termsVal = $InvData->TermsConditions ?? '';
}

if ($isEdit) {
    $hNetAmt   = (float)($InvData->NetAmount  ?? 0);
    $hPaidAmt  = (float)($InvData->PaidAmount ?? 0);
    $hBalAmt   = max(0, round($hNetAmt - $hPaidAmt, 2));
    $hCurrency = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');
    $hDecimals = $JwtData->GenSettings->DecimalPoints ?? 2;
    $hStatus   = $InvData->DocStatus ?? '';
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
                    <?php else: ?>
                    <input type="hidden" name="fromSalesOrderUID" id="fromSalesOrderUID" value="<?php echo (int)($FromSalesOrderUID ?? 0); ?>" />
                    <input type="hidden" name="fromQuotationUID" id="fromQuotationUID" value="<?php echo (int)($FromQuotationUID ?? 0); ?>" />
                    <input type="hidden" name="fromChallanUID" id="fromChallanUID" value="<?php echo (int)($FromChallanUID ?? 0); ?>" />
                    <?php endif; ?>
                    <input type="hidden" id="placeOfSupplyCode" name="placeOfSupplyCode" value="<?php echo !$isEdit ? htmlspecialchars($JwtData->Org->StateCode ?? '', ENT_QUOTES) : ''; ?>" />
                    <input type="hidden" id="placeOfSupplyName" name="placeOfSupplyName" value="<?php echo !$isEdit ? htmlspecialchars($JwtData->Org->StateName ?? '', ENT_QUOTES) : ''; ?>" />

                    <div class="card mb-3">

                        <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2 trans-header-static trans-theme modal-header-center-sticky">
                            <div class="d-flex align-items-center gap-3" id="transHeaderInfo">
                                <div class="trans-doc-icon bg-primary bg-opacity-10">
                                    <i class="bx bx-receipt text-primary" style="font-size:1.1rem;"></i>
                                </div>
                                <div>
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <?php if (!$isEdit): ?>
                                            <span class="fw-bold" style="font-size:.92rem;">Create Invoice</span>
                                            <?php $this->load->view('transactions/partials/form_prefix_add'); ?>
                                        <?php else: ?>
                                            <span class="fw-bold" style="font-size:.92rem;"><?php echo $isDraftEdit ? '' : 'Edit'; ?> Invoice</span>
                                            <?php if (!$isDraftEdit && !empty($InvData->UniqueNumber)): ?>
                                                <span class="trans-form-doc-number"><?php echo htmlspecialchars($InvData->UniqueNumber); ?></span>
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
                                                    <input type="number" id="transNumber" name="transNumber" class="form-control transAutoGenNumber stop-incre-indicator" maxLength="20"
                                                        onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57))"
                                                        oninput="this.value=this.value.slice(0,this.maxLength)"
                                                        pattern="[0-9]*" value="<?php echo $editTransNumber; ?>"
                                                        <?php echo (!$isDraftEdit ? 'disabled' : 'required'); ?> />
                                                </div>
                                            </div>
                                            <?php if (!$isDraftEdit): ?>
                                            <input type="hidden" name="transPrefixSelect" value="<?php echo (int)$InvData->PrefixUID; ?>" />
                                            <input type="hidden" name="transNumber" value="<?php echo (int)$InvData->TransNumber; ?>" />
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($isEdit && !$isDraftEdit): ?>
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
                                            <span style="font-size:.78rem;color:#566a7f;"><?php echo htmlspecialchars(format_datedisplay($InvData->TransDate)); ?></span>
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
                                <a href="/invoices" class="btn btn-sm btn-outline-danger px-3<?php echo $_hideNav ? ' d-none' : ''; ?>"><i class="bx bx-x me-1"></i>Close</a>
                            </div>
                        </div>

                        <div class="card-body card-body-form-static p-3">

                            <?php
                            $_invType  = $InvData->QuotationType ?? 'Regular';
                            ?>

                            <!-- ── Toolbar: Type & Dispatch From ─────────────────────────────── -->
                            <div class="d-flex align-items-center gap-4 mb-3 pb-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-muted" style="font-size:.78rem;white-space:nowrap;">Type</span>
                                    <select class="form-select form-select-sm border-0 bg-transparent fw-semibold"
                                            id="invoiceType" name="invoiceType" style="min-width:110px;cursor:pointer;"
                                            <?php echo ($isEdit && !$isDraftEdit) ? 'disabled' : 'required'; ?>>
                                        <option value="Regular"     <?php echo $_invType !== 'Without_GST' ? 'selected' : ''; ?>>Regular</option>
                                        <option value="Without_GST" <?php echo $_invType === 'Without_GST' ? 'selected' : ''; ?>>Without GST</option>
                                    </select>
                                    <?php if ($isEdit && !$isDraftEdit): ?>
                                    <input type="hidden" name="invoiceType" value="<?php echo htmlspecialchars($_invType); ?>" />
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($DispatchAddresses)): ?>
                                <div class="d-flex align-items-center gap-2 dispatch-from-grp" style="max-width:360px;">
                                    <span class="text-muted" style="font-size:.78rem;white-space:nowrap;">Dispatch From</span>
                                    <?php $this->load->view('common/transactions/_dispatch_from'); ?>
                                </div>
                                <?php endif; ?>
                                <?php if (!$isEdit): ?>
                                <div class="ms-auto d-flex align-items-center gap-2">
                                    <div id="custTypeIndicator" class="d-none"></div>
                                    <!-- On Account indicator — shown when customer has unapplied credits -->
                                    <div id="onAccountIndicator" class="d-none d-flex align-items-center gap-1"
                                         style="font-size:.78rem;color:#856404;background:#fff8e1;border:1px solid #ffc107;padding:3px 12px;border-radius:20px;white-space:nowrap;">
                                        <i class="bx bx-wallet" style="font-size:.88rem;"></i>
                                        On Account: <strong id="onAccountTotal" style="margin-left:3px;"></strong>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- ── Row 1: Customer | Invoice Date | Due Date | Reference ─────── -->
                            <div class="row g-2 align-items-end mb-2">

                                <!-- Customer -->
                                <div class="col-md-4">
                                    <?php if ($isEdit && !$isDraftEdit): ?>
                                        <label class="trans-field-label">Customer</label>
                                        <div class="trans-vendor-card">
                                            <div class="trans-vendor-card-name"><i class="bx bx-user me-1"></i><?php echo htmlspecialchars($InvData->PartyName ?? '—'); ?></div>
                                            <?php if (!empty($InvData->PartyMobile)): ?>
                                            <div class="trans-vendor-card-meta"><i class="bx bx-phone me-1"></i><?php echo htmlspecialchars($InvData->PartyMobile); ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($InvData->PartyGSTIN)): ?>
                                            <div class="trans-vendor-card-meta"><i class="bx bx-id-card me-1"></i><?php echo htmlspecialchars($InvData->PartyGSTIN); ?></div>
                                            <?php endif; ?>
                                            <?php $_cParts = array_filter([$CustAddr->Line1 ?? '', $CustAddr->CityText ?? '', $CustAddr->StateText ?? '']);
                                                if (!empty($_cParts)): ?>
                                            <div class="trans-vendor-card-meta"><i class="bx bx-map me-1"></i><?php echo htmlspecialchars(implode(', ', $_cParts)); ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <input type="hidden" id="customerSearch" name="customerSearch" value="<?php echo (int)$InvData->PartyUID; ?>" />
                                    <?php else: ?>
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <label for="customerSearch" class="trans-field-label mb-0">Customer <span class="text-danger">*</span></label>
                                            <button type="button" id="addTransCustomer" class="trans-add-btn btn btn-outline-primary btn-sm" style="font-size:.72rem;white-space:nowrap;">
                                                <i class="bx bx-plus-circle me-1"></i>Add Customer
                                            </button>
                                        </div>
                                        <select id="customerSearch" name="customerSearch" class="form-select form-select-sm"></select>
                                    <?php endif; ?>
                                </div>

                                <!-- Invoice Date — narrow (date width only) -->
                                <div class="col-auto" style="min-width:155px;">
                                    <label for="transDate" class="trans-field-label">Invoice Date <span class="text-danger">*</span></label>
                                    <?php $_fmt = $JwtData->GenSettings->FormDateFormat ?? 'd-m-Y'; ?>
                                    <?php if ($isEdit && !$isDraftEdit): ?>
                                        <input type="hidden" name="transDate" value="<?php echo htmlspecialchars(format_datedisplay($InvData->TransDate, 'Y-m-d')); ?>" />
                                        <div class="input-group input-group-sm input-group-merge">
                                            <span class="input-group-text bg-white"><i class="icon-base bx bx-calendar"></i></span>
                                            <input type="text" class="form-control form-control-sm bg-white text-muted" style="cursor:default;" value="<?php echo htmlspecialchars(format_datedisplay($InvData->TransDate, $_fmt)); ?>" readonly tabindex="-1" />
                                        </div>
                                    <?php else: ?>
                                        <div class="input-group input-group-sm input-group-merge">
                                            <span class="input-group-text bg-white"><i class="icon-base bx bx-calendar"></i></span>
                                            <input type="text" class="form-control form-control-sm bg-white" id="transDate_disp" readonly="readonly" value="<?php echo $isEdit ? format_datedisplay($InvData->TransDate, $_fmt) : format_datedisplay(time(), $_fmt); ?>" required />
                                            <input type="hidden" id="transDate" name="transDate" value="<?php echo $isEdit ? htmlspecialchars(format_datedisplay($InvData->TransDate, 'Y-m-d')) : format_datedisplay(time(), 'Y-m-d'); ?>" />
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Due Date — narrow (date width only) -->
                                <div class="col-auto" style="min-width:145px;">
                                    <label for="dueDate" class="trans-field-label">Due Date</label>
                                    <div class="input-group input-group-sm input-group-merge">
                                        <span class="input-group-text bg-white"><i class="icon-base bx bx-calendar"></i></span>
                                        <input type="text" class="form-control form-control-sm bg-white" id="dueDate_disp" readonly="readonly" value="<?php echo ($isEdit && !empty($InvData->ValidityDate)) ? format_datedisplay($InvData->ValidityDate, $_fmt) : ''; ?>" />
                                        <input type="hidden" id="dueDate" name="dueDate" value="<?php echo ($isEdit && !empty($InvData->ValidityDate)) ? htmlspecialchars(format_datedisplay($InvData->ValidityDate, 'Y-m-d')) : ''; ?>" />
                                    </div>
                                </div>

                                <!-- Reference — takes remaining width -->
                                <div class="col">
                                    <label for="referenceDetails" class="trans-field-label">Reference</label>
                                    <input type="text" id="referenceDetails" name="referenceDetails" class="form-control form-control-sm"
                                        placeholder="PO Number, Sales Person, Ref No..." maxlength="100"
                                        value="<?php echo $isEdit ? htmlspecialchars($InvData->Reference ?? '') : (!empty($SalesOrderData->Reference) ? htmlspecialchars($SalesOrderData->Reference) : (!empty($QuotationData->Reference) ? htmlspecialchars($QuotationData->Reference) : '')); ?>" />
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
                                'transNotesPlaceholder' => 'Enter notes or anything else',
                                'transNotesContent'     => $_notesVal,
                                'transTermsContent'     => $_termsVal,
                                'transShowDropzone'     => true,
                                'transSignatureUID'     => $isEdit ? (int)($InvData->SignatureUID ?? 0) : 0,
                                'transSignatures'       => $JwtData->User->Signatures ?? [],
                                'transPaymentVars'      => !$isEdit ? [
                                    'PaymentTypes'     => $PaymentTypes ?? [],
                                    'BankAccounts'     => $BankAccounts ?? [],
                                    'JwtData'          => $JwtData,
                                    'paymentPartyType' => 'C',
                                ] : null,
                            ]); ?>

                            <!-- ── Inline full-width summary (below both columns) ──────────── -->
                            <?php $cur = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹'); ?>
                            <div id="inlineSummaryBar" class="sticky-bottom-bar mt-3" style="
                                padding:10px 24px;
                                display:flex;align-items:center;justify-content:space-between;gap:16px;
                                border-radius:8px;">

                                <!-- Left info sections -->
                                <div class="d-flex align-items-stretch gap-0">

                                    <!-- Section 1: Total + Tax (always visible) -->
                                    <div style="padding-right:20px;">
                                        <div class="fw-bold" style="font-size:.95rem;">TOTAL &nbsp;<span style="color:#0d6efd;" id="inlineGrandTotal"><?php echo $cur; ?> 0.00</span></div>
                                        <div class="text-muted" style="font-size:.74rem;">Includes Total Tax &nbsp;<span id="inlineTotalTax">0.00</span></div>
                                    </div>

                                    <!-- Section 2: Total Paid (shown when paid > 0) -->
                                    <div id="inlinePaidGroup" class="d-none d-flex align-items-stretch">
                                        <div style="width:1px;background:#c5dcff;margin:0 20px;flex-shrink:0;"></div>
                                        <div>
                                            <div style="font-size:.74rem;color:#198754;font-weight:600;">
                                                <i class="bx bx-check-circle me-1"></i>Total Paid
                                            </div>
                                            <div class="fw-bold" style="font-size:.92rem;color:#198754;">
                                                <span id="inlineTotalPaid"><?php echo $cur; ?> 0.00</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Section 3: Balance (shown when balance > 0 and no excess) -->
                                    <div id="inlineBalanceGroup" class="d-none d-flex align-items-stretch">
                                        <div style="width:1px;background:#c5dcff;margin:0 20px;flex-shrink:0;"></div>
                                        <div>
                                            <div style="font-size:.74rem;color:#dc3545;font-weight:600;">
                                                <i class="bx bx-wallet me-1"></i>Balance
                                            </div>
                                            <div class="fw-bold" style="font-size:.92rem;color:#dc3545;">
                                                <span id="inlineBalanceAmt"><?php echo $cur; ?> 0.00</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Section 4: Excess (shown when excess > 0) -->
                                    <div id="inlineExcessGroup" class="d-none d-flex align-items-stretch">
                                        <div style="width:1px;background:#c5dcff;margin:0 20px;flex-shrink:0;"></div>
                                        <div>
                                            <div style="font-size:.74rem;color:#f59e0b;font-weight:600;">
                                                <i class="bx bx-error-circle me-1"></i>Excess
                                            </div>
                                            <div class="fw-bold" style="font-size:.92rem;color:#f59e0b;">
                                                <span id="inlineExcessAmt"><?php echo $cur; ?> 0.00</span>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                                <div class="d-flex align-items-center gap-2">
                                    <?php if (!$isEdit || $isDraftEdit): ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="inlineDraftBtn">
                                        <i class="bx bx-save me-1"></i>Draft
                                    </button>
                                    <?php endif; ?>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-primary px-3" id="inlineSaveBtn">
                                            <i class="bx bx-check me-1"></i><?php echo ($isEdit && !$isDraftEdit) ? 'Update' : 'Save'; ?>
                                        </button>
                                        <?php if (!$isEdit || $isDraftEdit): ?>
                                        <button type="button" class="btn btn-sm btn-primary dropdown-toggle dropdown-toggle-split ps-2 pe-2"
                                                data-bs-toggle="dropdown" aria-expanded="false">
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

                    <!-- ── Sticky bottom summary bar ──────────────────────────────── -->
                    <div id="stickyBottomBar" class="sticky-bottom-bar" style="
                        position:fixed;bottom:0;right:0;z-index:1040;
                        padding:10px 24px;
                        display:flex;align-items:center;justify-content:space-between;gap:16px;">

                        <!-- Left info sections -->
                        <div class="d-flex align-items-stretch gap-0">

                            <!-- Section 1: Total + Tax -->
                            <div style="padding-right:20px;">
                                <div class="fw-bold" style="font-size:.95rem;">TOTAL &nbsp;<span style="color:#0d6efd;" id="stickyGrandTotal"><?php echo $cur; ?> 0.00</span></div>
                                <div class="text-muted" style="font-size:.74rem;">Includes Total Tax &nbsp;<span id="stickyTotalTax">0.00</span></div>
                            </div>

                            <!-- Section 2: Total Paid -->
                            <div id="stickyPaidGroup" class="d-none d-flex align-items-stretch">
                                <div style="width:1px;background:#c5dcff;margin:0 20px;flex-shrink:0;"></div>
                                <div>
                                    <div style="font-size:.74rem;color:#198754;font-weight:600;">
                                        <i class="bx bx-check-circle me-1"></i>Total Paid
                                    </div>
                                    <div class="fw-bold" style="font-size:.92rem;color:#198754;">
                                        <span id="stickyTotalPaid"><?php echo $cur; ?> 0.00</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Section 3: Balance -->
                            <div id="stickyBalanceGroup" class="d-none d-flex align-items-stretch">
                                <div style="width:1px;background:#c5dcff;margin:0 20px;flex-shrink:0;"></div>
                                <div>
                                    <div style="font-size:.74rem;color:#dc3545;font-weight:600;">
                                        <i class="bx bx-wallet me-1"></i>Balance
                                    </div>
                                    <div class="fw-bold" style="font-size:.92rem;color:#dc3545;">
                                        <span id="stickyBalanceAmt"><?php echo $cur; ?> 0.00</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Section 4: Excess -->
                            <div id="stickyExcessGroup" class="d-none d-flex align-items-stretch">
                                <div style="width:1px;background:#c5dcff;margin:0 20px;flex-shrink:0;"></div>
                                <div>
                                    <div style="font-size:.74rem;color:#f59e0b;font-weight:600;">
                                        <i class="bx bx-error-circle me-1"></i>Excess
                                    </div>
                                    <div class="fw-bold" style="font-size:.92rem;color:#f59e0b;">
                                        <span id="stickyExcessAmt"><?php echo $cur; ?> 0.00</span>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- Action buttons — delegate to existing header buttons -->
                        <div class="d-flex align-items-center gap-2">
                            <?php if (!$isEdit || $isDraftEdit): ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="stickyDraftBtn">
                                <i class="bx bx-save me-1"></i>Draft
                            </button>
                            <?php endif; ?>

                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-primary px-3" id="stickySaveBtn">
                                    <i class="bx bx-check me-1"></i><?php echo ($isEdit && !$isDraftEdit) ? 'Update' : 'Save'; ?>
                                </button>
                                <?php if (!$isEdit || $isDraftEdit): ?>
                                <button type="button" class="btn btn-sm btn-primary dropdown-toggle dropdown-toggle-split ps-2 pe-2"
                                        data-bs-toggle="dropdown" aria-expanded="false">
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
<script src="/js/transactions/invoices.js"></script>
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
var _isEdit    = <?php echo $isEdit ? 'true' : 'false'; ?>;
var _orgState  = '<?php echo addslashes($DispatchAddress->StateText ?? ''); ?>';
var _upstashUrl       = '<?php echo addslashes($UpstashReadUrl   ?? ''); ?>';
var _upstashReadToken = '<?php echo addslashes($UpstashReadToken ?? ''); ?>';
var _custCacheKey     = '<?php echo addslashes($CustomerCacheKey ?? ''); ?>';
let imgData;

<?php if ($isEdit): ?>
var _custState = '<?php echo addslashes($CustAddr->StateText ?? ''); ?>';
var _editItems = <?php echo json_encode(array_map(function($item) {
    return [
        'id'               => (int)  $item->ProductUID,
        'text'             => $item->ProductName,
        'itemName'         => $item->ProductName,
        'description'      => $item->Description ?? '',
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
}, $InvItems)); ?>;
<?php else: ?>
<?php if (!empty($SalesOrderData)): ?>
var _fromSO      = <?php echo json_encode(['uid' => (int)$FromSalesOrderUID, 'customer' => (int)$SalesOrderData->PartyUID, 'customerName' => $SalesOrderData->PartyName ?? '']); ?>;
var _fromSOItems = <?php echo json_encode(array_map(function($item) {
    return [
        'id'               => (int)   $item->ProductUID,
        'text'             => $item->ProductName,
        'itemName'         => $item->ProductName,
        'unitPrice'        => (float) $item->UnitPrice,
        'sellingPrice'     => (float) $item->SellingPrice,
        'taxAmount'        => (float) $item->TaxAmount,
        'purchasePrice'    => (float) ($item->PurchasePrice ?? 0),
        'availableQuantity'=> 0,
        'hsnCode'          => '',
        'categoryUID'      => $item->CategoryUID ? (int)$item->CategoryUID : null,
        'categoryName'     => $item->CategoryName  ?? '',
        'storageUID'       => $item->StorageUID  ? (int)$item->StorageUID  : null,
        'taxPercent'       => (float) $item->TaxPercentage,
        'cgstPercent'      => (float) $item->CGST,
        'sgstPercent'      => (float) $item->SGST,
        'igstPercent'      => (float) $item->IGST,
        'taxDetailsUID'    => (int)   $item->TaxDetailsUID,
        'quantity'         => (float) $item->Quantity,
        'partNumber'       => $item->PartNumber      ?? '',
        'primaryUnit'      => $item->PrimaryUnitName ?? '',
        'discount'         => (float) $item->Discount,
        'discountType'     => 'Percentage',
        'discountTypeUID'  => $item->DiscountTypeUID ? (int)$item->DiscountTypeUID : null,
        'discount_amount'  => (float) $item->DiscountAmount,
        'line_total'       => (float) $item->TaxableAmount,
        'net_total'        => (float) $item->NetAmount,
    ];
}, $SalesOrderItems ?? [])); ?>;
<?php else: ?>
var _fromSO      = null;
var _fromSOItems = [];
<?php endif; ?>
<?php if (!empty($QuotationData)): ?>
var _fromQuotation = <?php echo json_encode(['uid' => (int)$FromQuotationUID, 'customer' => (int)$QuotationData->PartyUID, 'customerName' => $QuotationData->PartyName ?? '']); ?>;
var _fromQuotItems = <?php echo json_encode(array_map(function($item) {
    return [
        'id'               => (int)   $item->ProductUID,
        'text'             => $item->ProductName,
        'itemName'         => $item->ProductName,
        'unitPrice'        => (float) $item->UnitPrice,
        'sellingPrice'     => (float) $item->SellingPrice,
        'taxAmount'        => (float) $item->TaxAmount,
        'purchasePrice'    => (float) ($item->PurchasePrice ?? 0),
        'availableQuantity'=> 0,
        'hsnCode'          => '',
        'categoryUID'      => $item->CategoryUID ? (int)$item->CategoryUID : null,
        'categoryName'     => $item->CategoryName  ?? '',
        'storageUID'       => $item->StorageUID  ? (int)$item->StorageUID  : null,
        'taxPercent'       => (float) $item->TaxPercentage,
        'cgstPercent'      => (float) $item->CGST,
        'sgstPercent'      => (float) $item->SGST,
        'igstPercent'      => (float) $item->IGST,
        'taxDetailsUID'    => (int)   $item->TaxDetailsUID,
        'quantity'         => (float) $item->Quantity,
        'partNumber'       => $item->PartNumber      ?? '',
        'primaryUnit'      => $item->PrimaryUnitName ?? '',
        'discount'         => (float) $item->Discount,
        'discountType'     => 'Percentage',
        'discountTypeUID'  => $item->DiscountTypeUID ? (int)$item->DiscountTypeUID : null,
        'discount_amount'  => (float) $item->DiscountAmount,
        'line_total'       => (float) $item->TaxableAmount,
        'net_total'        => (float) $item->NetAmount,
    ];
}, $QuotationItems ?? [])); ?>;
<?php else: ?>
var _fromQuotation = null;
var _fromQuotItems = [];
<?php endif; ?>
<?php if (!empty($ChallanData)): ?>
var _fromChallan      = <?php echo json_encode(['uid' => (int)$FromChallanUID, 'customer' => (int)$ChallanData->PartyUID, 'customerName' => $ChallanData->PartyName ?? '', 'reference' => $ChallanData->UniqueNumber ?? '']); ?>;
var _fromChallanItems = <?php echo json_encode(array_map(function($item) {
    return [
        'id'               => (int)   $item->ProductUID,
        'text'             => $item->ProductName,
        'itemName'         => $item->ProductName,
        'unitPrice'        => (float) $item->UnitPrice,
        'sellingPrice'     => (float) $item->SellingPrice,
        'taxAmount'        => (float) $item->TaxAmount,
        'purchasePrice'    => (float) ($item->PurchasePrice ?? 0),
        'availableQuantity'=> 0,
        'hsnCode'          => $item->HSNCode ?? '',
        'categoryUID'      => $item->CategoryUID ? (int)$item->CategoryUID : null,
        'categoryName'     => $item->CategoryName  ?? '',
        'storageUID'       => $item->StorageUID  ? (int)$item->StorageUID  : null,
        'taxPercent'       => (float) $item->TaxPercentage,
        'cgstPercent'      => (float) $item->CGST,
        'sgstPercent'      => (float) $item->SGST,
        'igstPercent'      => (float) $item->IGST,
        'taxDetailsUID'    => (int)   $item->TaxDetailsUID,
        'quantity'         => (float) $item->Quantity,
        'partNumber'       => $item->PartNumber      ?? '',
        'primaryUnit'      => $item->PrimaryUnitName ?? '',
        'discount'         => (float) $item->Discount,
        'discountType'     => 'Percentage',
        'discountTypeUID'  => $item->DiscountTypeUID ? (int)$item->DiscountTypeUID : null,
        'discount_amount'  => (float) $item->DiscountAmount,
        'line_total'       => (float) $item->TaxableAmount,
        'net_total'        => (float) $item->NetAmount,
    ];
}, $ChallanItems ?? [])); ?>;
<?php else: ?>
var _fromChallan      = null;
var _fromChallanItems = [];
<?php endif; ?>
<?php endif; ?>

$(function() {
    'use strict'

    <?php if (!$isEdit || $isDraftEdit): ?>
    searchCustomers('customerSearch');
    <?php endif; ?>

    <?php if (!$isEdit): ?>
    var _cur = '<?php echo addslashes($JwtData->GenSettings->CurrenySymbol ?? "₹"); ?>';

    var _oaCustomerUID = 0;

    // Called by transactions.js select2:select — immediate, no AJAX
    window._showOnAccountBanner = function(total, records, customerUID) {
        _oaCustomerUID = parseInt(customerUID, 10) || 0;
        if (total > 0) {
            $('#onAccountTotal').text(_cur + ' ' + parseFloat(total).toFixed(2));
            $('#onAccountIndicator').removeClass('d-none');
        } else {
            $('#onAccountIndicator').addClass('d-none');
        }
        // Pass total + records to panel. Button shows from total immediately;
        // records may be [] if cache is stale — fetched lazily when button is clicked.
        if (typeof window._loadOnAccountPanel === 'function') {
            window._loadOnAccountPanel(records || [], _oaCustomerUID, total);
        }
    };

    // Hide indicator and clear panel when customer is cleared
    $('#customerSearch').on('select2:clear change', function() {
        if (!parseInt($(this).val(), 10)) {
            $('#onAccountIndicator').addClass('d-none');
            if (typeof window._clearOnAccountPanel === 'function') window._clearOnAccountPanel();
        }
    });
    <?php endif; ?>
    <?php if (!$isEdit || $isDraftEdit): ?>
    transDatePickr('#transDate_disp', '#transDate', false, false, true, true, '');
    <?php endif; ?>
    transDatePickr('#dueDate_disp', '#dueDate', false, false, false, <?php echo $isEdit ? 'false' : 'true'; ?>, '<?php echo ($isEdit && !$isDraftEdit) ? '' : '#transDate'; ?>');

    <?php if (!$isEdit): ?>
    var _dueDatePicker   = document.querySelector('#dueDate') ? document.querySelector('#dueDate')._flatpickr : null;
    var _transDatePicker = document.querySelector('#transDate') ? document.querySelector('#transDate')._flatpickr : null;
    if (_dueDatePicker && _transDatePicker) {
        _dueDatePicker.setDate(_transDatePicker.selectedDates[0], true);
        document.querySelector('#transDate').addEventListener('change', function() {
            if (_transDatePicker.selectedDates[0]) {
                _dueDatePicker.setDate(_transDatePicker.selectedDates[0], true);
            }
        });
    }
    <?php endif; ?>

    <?php if ($isEdit): ?>
    initTooltips();
    renderTransAttachmentsFromData(<?php echo json_encode($InvAttachments ?? []); ?>);
    $('#extraDiscount').val('<?php echo smartDecimal($InvData->ExtraDiscAmount ?? 0); ?>');
    $('#extDiscountType').val('<?php echo addslashes($InvData->ExtraDiscType ?? ''); ?>').trigger('change');
    $('#globalDiscount').val('<?php echo smartDecimal($InvData->GlobalDiscPercent ?? 0); ?>').trigger('input');

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

    <?php else: ?>
    var _sourceData  = _fromSO || _fromQuotation || _fromChallan;
    var _sourceItems = _fromSO ? _fromSOItems : (_fromQuotation ? _fromQuotItems : _fromChallanItems);

    if (_sourceData && _sourceData.uid > 0) {
        if (_sourceData.customer > 0) {
            $('#customerSearch').append(new Option(_sourceData.customerName, _sourceData.customer, true, true)).trigger('change');
        }
        if (_fromChallan && _fromChallan.reference) {
            var $refField = $('#referenceDetails');
            if ($refField.length && !$refField.val()) {
                $refField.val(_fromChallan.reference);
            }
        }
        if (typeof billManager !== 'undefined' && typeof formationTableBillItems === 'function'
                && Array.isArray(_sourceItems) && _sourceItems.length > 0) {
            $('#billTableBody').empty();
            _sourceItems.forEach(function(item) {
                var added = billManager.addItem(item, item.quantity);
                if (added !== false) {
                    formationTableBillItems(billManager.getItemById(item.id));
                }
            });
            if (typeof updateItemTaxBreakdown === 'function') updateItemTaxBreakdown();
            billManager.updateSummary();
        }
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
                if (!prefixUID || prefixUID <= 0) return showFormError('Please select an invoice prefix.');

                var transNumber = $.trim($('#transNumber').val());
                if (!transNumber || parseInt(transNumber, 10) <= 0) return showFormError('Transaction number must be greater than 0.');
            }

            var transDate = $.trim($('[name="transDate"]').val());
            if (!transDate || !/^\d{4}-\d{2}-\d{2}$/.test(transDate)) return showFormError('Please enter a valid invoice date.');

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

            if (!_isEdit && action !== 'draft') {
                if (typeof getPaymentAttachmentFiles === 'function') {
                    var _payFiles = getPaymentAttachmentFiles();
                    if (_payFiles && _payFiles.length > 0) {
                        var _totalPayAmt = 0;
                        $('#paymentRowsBody tr').each(function() {
                            _totalPayAmt += parseFloat($(this).find('.pay-amount-inp').val()) || 0;
                        });
                        if (_totalPayAmt <= 0) return showFormError('Payment attachments are added but no payment amount is entered. Please enter a payment amount or remove the attachments.');
                    }
                }
                if (!serializePaymentRows()) return showFormError('Please enter a valid amount for every payment row.');
            }

            var fd = new FormData();
            fd.append(csrfName, csrfVal);
            if (_isEdit) fd.append('TransUID', parseInt($('input[name="TransUID"]').val(), 10));
            fd.append('transPrefixSelect',      parseInt($('#transPrefixSelect').val(), 10) || 0);
            fd.append('transNumber',            $.trim($('#transNumber').val()));
            fd.append('transDate',              transDate);
            fd.append('dueDate',                $.trim($('#dueDate').val()));
            fd.append('customerSearch',         customerUID);
            if (!_isEdit) {
                fd.append('fromSalesOrderUID',  parseInt($('#fromSalesOrderUID').val(), 10) || 0);
                fd.append('fromQuotationUID',   parseInt($('#fromQuotationUID').val(), 10) || 0);
                fd.append('placeOfSupplyCode',  $('#placeOfSupplyCode').val() || '');
                fd.append('placeOfSupplyName',  $('#placeOfSupplyName').val() || '');
            }
            fd.append('invoiceType',            $('[name="invoiceType"]').val() || '');
            fd.append('dispatchFrom',           $('[name="dispatchFrom"]').val() || '');
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
            fd.append('SignatureUID',           parseInt($('#transSignatureUID').val(), 10) || 0);
            fd.append('action',                 action);
            $.each(charges, function(k, v) { fd.append(k, v); });
            collectTransAttachData(fd);
            if (!_isEdit) {
                fd.append('PaymentRows',       $('#PaymentRowsJson').val()    || '');
                fd.append('IsFullyPaid',       $('#isFullyPaid').is(':checked') ? 1 : 0);
                fd.append('RecordPayment',     action !== 'draft' ? 1 : 0);
                fd.append('OnAccountApplyJson', $('#OnAccountApplyJson').val() || '');
                // Append payment attachment files
                if (typeof getPaymentAttachmentFiles === 'function') {
                    var paymentFiles = getPaymentAttachmentFiles();
                    if (paymentFiles && paymentFiles.length > 0) {
                        paymentFiles.forEach(function(file) {
                            fd.append('PaymentFiles[]', file);
                        });
                    }
                }
            }

            setFormLoading('#<?php echo $formId; ?>', true, action);

            $.ajax({
                url         : '/<?php echo $formAction; ?>',
                method      : 'POST',
                data        : fd,
                processData : false,
                contentType : false,
                cache       : false,
                success: function(response) {
                    if (response.Error) {
                        setFormLoading('#<?php echo $formId; ?>', false);
                        showFormError(response.Message);
                    } else {
                        _showSavedAndGo(_isEdit ? 'Invoice Updated' : 'Invoice Saved', response.Message || (_isEdit ? 'Invoice updated successfully.' : 'Invoice created successfully.'));
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

// ── Sticky bottom bar — total sync + button delegation ─────────────────────
(function() {
    var cur = '<?php echo addslashes($JwtData->GenSettings->CurrenySymbol ?? "₹"); ?>';
    var dec = <?php echo (int)($JwtData->GenSettings->DecimalPoints ?? 2); ?>;

    // Align sticky bar to exactly match the form's left and right edges.
    // Use clientWidth (excludes scrollbar) — window.innerWidth includes scrollbar
    // which creates a ~17px gap between the bar's right edge and the form's right edge.
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

        // IntersectionObserver: hide sticky when inline summary is in view
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

    // Sync totals — calculate fresh from source, never rely on stale DOM values
    function _syncStickyTotals() {
        if (typeof billManager === 'undefined') return;

        var grand = billManager.summary && billManager.summary.totals
            ? (billManager.summary.totals.grandTotal || 0) : 0;
        var tax = billManager.summary && billManager.summary.taxTotals
            ? (billManager.summary.taxTotals.totalTax || 0) : 0;

        // Sum payment rows
        var rowsPaid = 0;
        document.querySelectorAll('#paymentRowsBody tr .pay-amount-inp').forEach(function(inp) {
            rowsPaid += parseFloat(inp.value) || 0;
        });

        // Add On Account applied amount
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

        // Grand Total + Tax (always shown)
        ['stickyGrandTotal','inlineGrandTotal'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.textContent = cur + ' ' + grand.toFixed(dec);
        });
        ['stickyTotalTax','inlineTotalTax'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.textContent = cur + ' ' + tax.toFixed(dec);
        });

        // Total Paid — show only when paid > 0
        _toggleGroup(['stickyPaidGroup','inlinePaidGroup'],
            paid > 0, ['stickyTotalPaid','inlineTotalPaid'], paid);

        // Balance — show whenever items exist and there is still something to pay
        _toggleGroup(['stickyBalanceGroup','inlineBalanceGroup'],
            grand > 0 && balance > 0 && excess === 0,
            ['stickyBalanceAmt','inlineBalanceAmt'], balance);

        // Excess — show when excess > 0
        _toggleGroup(['stickyExcessGroup','inlineExcessGroup'],
            excess > 0, ['stickyExcessAmt','inlineExcessAmt'], excess);
    }

    document.addEventListener('DOMContentLoaded', function() {
        // When bill total changes → update payment section + sync sticky bars
        var billTotEl = document.querySelector('.bill_tot_amt');
        if (billTotEl) {
            new MutationObserver(function() {
                if (typeof updatePaymentSummary === 'function') updatePaymentSummary();
                _syncStickyTotals();
            }).observe(billTotEl, { childList: true, characterData: true, subtree: true });
        }

        // When payment row amounts change → sync sticky bars
        document.addEventListener('input', function(e) {
            if (e.target && e.target.classList.contains('pay-amount-inp')) {
                _syncStickyTotals();
            }
        });

        // When On Account JSON changes (apply/remove) → sync
        var oaJsonEl = document.getElementById('OnAccountApplyJson');
        if (oaJsonEl) {
            new MutationObserver(_syncStickyTotals).observe(oaJsonEl, { attributes: true, attributeFilter: ['value'] });
        }

        _syncStickyTotals();
        _syncStickyTotals();

        // Sticky bar button delegation
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

        // Inline bar button delegation
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
