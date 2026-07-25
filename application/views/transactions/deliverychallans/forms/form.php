<?php defined('BASEPATH') or exit('No direct script access allowed');
/** @var object|null $DCData */      $DCData      = $DCData      ?? null;
/** @var object      $JwtData */
/** @var array       $DCItems */     $DCItems      = $DCItems      ?? [];
/** @var int         $FromSOUID */   $FromSOUID    = $FromSOUID    ?? 0;
/** @var object|null $SOSourceData */$SOSourceData = $SOSourceData ?? null;
/** @var array       $SOSourceItems*/$SOSourceItems= $SOSourceItems?? [];
?>
<?php
$isEdit      = isset($DCData);
$isDraftEdit = $isEdit && ($DCData->DocStatus === 'Draft');
$transUID    = $isEdit ? (int)$DCData->TransUID : 0;
$formId      = 'dcForm';
$formAction  = $isEdit ? 'deliverychallan/updateDeliveryChallan' : 'deliverychallan/addDeliveryChallan';
$_posCode    = $isEdit ? ($DCData->PlaceOfSupplyCode  ?? '') : ($JwtData->Org->StateCode  ?? '');
$_posName    = $isEdit ? ($DCData->PlaceOfSupplyName  ?? '') : ($JwtData->Org->StateName  ?? '');

$_returnTab  = $this->input->get('returnTab')  ?: 'All';
$_returnPage = (int)($this->input->get('returnPage') ?: 1);
$_closeUrl   = trans_build_close_url('/deliverychallan', $_returnTab, $_returnPage);

if ($isEdit && !function_exists('buildDCPrefixSegment')) {
    function buildDCPrefixSegment(?object $cfg): string {
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
        if ((int)$_pd->PrefixUID === (int)$DCData->PrefixUID) {
            $editPrefixConfig = $_pd;
            break;
        }
    }
    if (!$editPrefixConfig) $editPrefixConfig = $PrefixData[0];
}
$editTransNumber = $isEdit ? ($isDraftEdit ? (int)($NextNumberMap[(int)($editPrefixConfig->PrefixUID ?? 0)] ?? 1) : (int)$DCData->TransNumber) : 0;
$editPrefixSeg   = ($isEdit && $isDraftEdit) ? buildDCPrefixSegment($editPrefixConfig) : '';

// Challan type
$_challanType = 'Non-Returnable';
if ($isEdit) {
    $_challanType = $DCData->DocType ?? 'Non-Returnable';
} elseif (!empty($SOSourceData)) {
    $_challanType = 'Non-Returnable';
}

// Vehicle number (stored in Reference)
$_vehicleNo = '';
if ($isEdit) {
    $_vehicleNo = $DCData->Reference ?? '';
}

$_fmt = $JwtData->GenSettings->FormDateFormat ?? 'd-m-Y';
// Expected return date → now stored in ExpectedDeliveryDate
$_returnDate = '';
$_returnDisp = '';
if ($isEdit && !empty($DCData->ExpectedDeliveryDate)) {
    $_returnDate = htmlspecialchars(format_datedisplay($DCData->ExpectedDeliveryDate, 'Y-m-d'));
    $_returnDisp = format_datedisplay($DCData->ExpectedDeliveryDate, $_fmt);
}

// Delivery By date → now stored in DeliveryByDate
$_deliveryByDate = '';
$_deliveryByDisp = '';
if ($isEdit && !empty($DCData->DeliveryByDate)) {
    $_deliveryByDate = htmlspecialchars(format_datedisplay($DCData->DeliveryByDate, 'Y-m-d'));
    $_deliveryByDisp = format_datedisplay($DCData->DeliveryByDate, $_fmt);
} elseif (!$isEdit && !empty($SOSourceData->DeliveryByDate)) {
    $_deliveryByDate = htmlspecialchars(format_datedisplay($SOSourceData->DeliveryByDate, 'Y-m-d'));
    $_deliveryByDisp = format_datedisplay($SOSourceData->DeliveryByDate, $_fmt);
} else {
    $_deliveryByDate = date('Y-m-d');
    $_deliveryByDisp = format_datedisplay(date('Y-m-d'), $_fmt);
}

// Notes / Terms
$_notesVal = '';
$_jwtTerms = $JwtData->TransSettings->TermsAndConditions ?? '';
$_termsVal = '';
if (!$isEdit) {
    $_termsVal = $_jwtTerms;
} else {
    $_notesVal = $DCData->Notes ?? '';
    $_termsVal = $DCData->TermsConditions ?? '';
}

// Dispatch address
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
                    <input type="hidden" name="fromSOUID" id="fromSOUID" value="<?php echo (int)($FromSOUID ?? 0); ?>" />
                    <?php endif; ?>
                    <?php $this->load->view('transactions/partials/place_of_supply_inputs', ['_posCode' => $_posCode, '_posName' => $_posName]); ?>

                    <div class="card mb-3">

                        <?php
                        $_hideNav    = (int)($JwtData->TransSettings->HideNavOnTransForm ?? 0);
                        $_dcStatusMap = ['Draft' => 'warning', 'Dispatched' => 'success', 'Cancelled' => 'danger'];
                        $_dcStatus    = $isEdit ? ($DCData->DocStatus ?? '') : '';
                        $_dcStatusClr = $_dcStatusMap[$_dcStatus] ?? 'secondary';
                        ?>
                        <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between px-3 py-2 trans-header-static trans-theme modal-header-center-sticky">
                            <div class="d-flex align-items-center gap-3" id="transHeaderInfo">
                                <?php $this->load->view('transactions/partials/form_back_button'); ?>
                                <div class="trans-doc-icon dc-doc-icon">
                                    <i class="bx bx-package"></i>
                                </div>
                                <div>
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <?php if (!$isEdit): ?>
                                            <span class="fw-bold dc-form-title">Create Delivery Challan</span>
                                            <?php if (!empty($SOSourceData)): ?>
                                                <span class="badge text-bg-info dc-from-so-badge"><i class="bx bx-transfer-alt me-1"></i>From SO: <?php echo htmlspecialchars($SOSourceData->UniqueNumber ?? ''); ?></span>
                                            <?php endif; ?>
                                            <?php $this->load->view('transactions/partials/form_prefix_add'); ?>
                                        <?php else: ?>
                                            <span class="fw-bold dc-form-title"><?php echo $isDraftEdit ? '' : 'Edit'; ?> Delivery Challan</span>
                                            <?php if (!$isDraftEdit && !empty($DCData->UniqueNumber)): ?>
                                                <span class="trans-form-doc-number"><?php echo htmlspecialchars($DCData->UniqueNumber); ?></span>
                                                <span class="badge bg-label-<?php echo $_dcStatusClr; ?> dc-status-badge"><?php echo htmlspecialchars($_dcStatus); ?></span>
                                            <?php endif; ?>
                                            <div class="d-flex align-items-center gap-1 <?php echo (!$isDraftEdit ? 'd-none' : ''); ?>">
                                                <div class="input-group w-auto">
                                                    <select id="transPrefixSelect" name="transPrefixSelect" class="select2 form-select form-select-sm" <?php echo (!$isDraftEdit ? 'disabled' : 'required'); ?>>
                                                        <?php try {
                                                            if (empty($PrefixData)) throw new Exception('Prefix data not loaded');
                                                            foreach ($PrefixData as $preData) {
                                                                $isSelected = (int)$preData->PrefixUID === (int)$DCData->PrefixUID ? 'selected' : '';
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
                                                    <input type="text" inputmode="numeric" id="transNumber" name="transNumber" class="form-control transAutoGenNumber stop-incre-indicator" maxLength="20"
                                                        onkeydown="return handleDotOnly(event)"
                                                        oninput="this.value=this.value.slice(0,this.maxLength); handleOnlyNumbers(this)"
                                                        onpaste="pasteOnlyNumbers(event)" ondrop="dropOnlyNumbers(event)"
                                                        pattern="[0-9]*" value="<?php echo $editTransNumber; ?>"
                                                        <?php echo (!$isDraftEdit ? 'disabled' : 'required'); ?> />
                                                </div>
                                            </div>
                                            <?php if (!$isDraftEdit): ?>
                                            <input type="hidden" name="transPrefixSelect" value="<?php echo (int)$DCData->PrefixUID; ?>" />
                                            <input type="hidden" name="transNumber" value="<?php echo (int)$DCData->TransNumber; ?>" />
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <?php if (!$isEdit || $isDraftEdit): ?>
                                <button type="submit" name="action" value="draft" class="btn btn-sm btn-outline-secondary"><i class="bx bx-save me-1"></i><?php echo t('btn_save_draft', 'Save as Draft'); ?></button>
                                <?php endif; ?>
                                <div class="btn-group">
                                    <button type="submit" name="action" value="save" class="btn btn-sm btn-primary px-3">
                                        <i class="bx bx-check me-1"></i>Save
                                    </button>
                                    <button type="button" class="btn btn-sm btn-primary dropdown-toggle dropdown-toggle-split ps-2 pe-2" data-bs-toggle="dropdown" aria-expanded="false">
                                        <span class="visually-hidden">Save options</span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow dc-save-menu">
                                        <li><span class="dropdown-header py-1">SAVE &amp; PRINT</span></li>
                                        <li><button type="submit" class="dropdown-item py-1" name="action" value="save_a4"><i class="bx bx-file text-primary me-2"></i><?php echo t('btn_save_a4', 'Save & Print A4'); ?></button></li>
                                        <li><button type="submit" class="dropdown-item py-1" name="action" value="save_a5"><i class="bx bx-file-blank text-info me-2"></i><?php echo t('btn_save_a5', 'Save & Print A5'); ?></button></li>
                                        <li><button type="submit" class="dropdown-item py-1" name="action" value="save_thermal"><i class="bx bx-receipt text-success me-2"></i><?php echo t('btn_save_thermal', 'Save & Print Thermal'); ?></button></li>
                                    </ul>
                                </div>
                                <a href="<?php echo $_closeUrl; ?>" class="btn btn-sm btn-outline-danger px-3<?php echo $_hideNav ? ' d-none' : ''; ?>"><i class="bx bx-x me-1"></i>Close</a>
                            </div>
                        </div>

                        <div class="card-body card-body-form-static p-4">

                            <!-- ── Toolbar: Type · Mode · Dispatch From ───────────────── -->
                            <?php
                            $_dcInvType = $isEdit ? ($DCData->InvoiceType ?? 'Regular') : 'Regular';
                            $_dcInvTypeLabel = $_dcInvType === 'Without_GST' ? 'Without GST' : 'Regular';
                            $_modeLabel = $_challanType;
                            // Find saved dispatch address for display
                            $_editDispAddr = null;
                            if ($isEdit && !empty($DCData->DispatchFrom) && !empty($DispatchAddresses)) {
                                foreach ($DispatchAddresses as $_da) {
                                    if ((int)$_da->OrgAddressUID === (int)$DCData->DispatchFrom) {
                                        $_editDispAddr = $_da; break;
                                    }
                                }
                            }
                            if (!$_editDispAddr && !empty($DispatchAddresses)) $_editDispAddr = $DispatchAddresses[0];
                            $_dispAddrText = '';
                            if ($_editDispAddr) {
                                $_dispAddrText = implode(', ', array_filter([
                                    $_editDispAddr->Line1 ?? '',
                                    $_editDispAddr->CityText ?? '',
                                    $_editDispAddr->StateText ?? '',
                                ]));
                            }
                            ?>
                            <div class="d-flex align-items-center gap-4 mb-3 pb-2 border-bottom">
                                <?php if ($isEdit && !$isDraftEdit): ?>
                                <!-- Edit mode: all three as read-only text chips, same style -->
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-muted dc-toolbar-label">Type</span>
                                    <span class="badge fw-semibold dc-chip-badge">
                                        <?php echo htmlspecialchars($_dcInvTypeLabel); ?>
                                    </span>
                                    <input type="hidden" id="dcInvoiceType" name="invoiceType" value="<?php echo htmlspecialchars($_dcInvType); ?>" />
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-muted dc-toolbar-label">Mode</span>
                                    <span class="badge fw-semibold dc-chip-badge">
                                        <?php echo htmlspecialchars($_modeLabel); ?>
                                    </span>
                                    <input type="hidden" id="challanType" name="challanType" value="<?php echo htmlspecialchars($_challanType); ?>" />
                                </div>
                                <?php if ($_editDispAddr): ?>
                                <?php
                                $_fullAddr = implode(', ', array_filter([
                                    $_editDispAddr->Line1     ?? '',
                                    $_editDispAddr->Line2     ?? '',
                                    $_editDispAddr->CityText  ?? '',
                                    $_editDispAddr->StateText ?? '',
                                    $_editDispAddr->Pincode   ?? '',
                                ]));
                                ?>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-muted dc-toolbar-label">Dispatch From</span>
                                    <span class="badge fw-semibold dc-chip-badge dc-chip-badge-addr">
                                        <?php echo htmlspecialchars($_fullAddr); ?>
                                    </span>
                                    <input type="hidden" id="dispatchFrom" name="dispatchFrom" value="<?php echo (int)$_editDispAddr->OrgAddressUID; ?>" />
                                </div>
                                <?php endif; ?>
                                <?php else: ?>
                                <!-- Create mode: interactive selects -->
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-muted dc-toolbar-label">Type</span>
                                    <select class="form-select form-select-sm border-0 bg-transparent fw-semibold dc-type-sel trans-gst-type-select"
                                            id="dcInvoiceType" name="invoiceType">
                                        <option value="Regular"     <?php echo $_dcInvType === 'Regular'     ? 'selected' : ''; ?>>Regular</option>
                                        <option value="Without_GST" <?php echo $_dcInvType === 'Without_GST' ? 'selected' : ''; ?>>Without GST</option>
                                    </select>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-muted dc-toolbar-label">Mode</span>
                                    <select class="form-select form-select-sm border-0 bg-transparent fw-semibold dc-mode-sel"
                                            id="challanType" name="challanType" required>
                                        <option value="Non-Returnable" <?php echo $_challanType === 'Non-Returnable' ? 'selected' : ''; ?>>Non-Returnable</option>
                                        <option value="Returnable"     <?php echo $_challanType === 'Returnable'     ? 'selected' : ''; ?>>Returnable</option>
                                        <option value="Job Work"       <?php echo $_challanType === 'Job Work'       ? 'selected' : ''; ?>>Job Work</option>
                                    </select>
                                </div>
                                <?php if (!empty($DispatchAddresses)): ?>
                                <div class="d-flex align-items-center gap-2 dispatch-from-grp dc-dispatch-grp">
                                    <span class="text-muted dc-toolbar-label">Dispatch From</span>
                                    <?php $this->load->view('common/transactions/_dispatch_from'); ?>
                                </div>
                                <?php endif; ?>
                                <?php endif; ?>
                                <div class="ms-auto d-flex align-items-center gap-2">
                                    <div id="custTypeIndicator" class="d-none"></div>
                                    <div id="plChipWrap" class="d-none"></div>
                                    <div id="onAccountIndicator" class="d-none d-flex align-items-center gap-1"
                                         style="font-size:.78rem;color:#856404;background:#fff8e1;border:1px solid #ffc107;padding:3px 12px;border-radius:20px;white-space:nowrap;">
                                        <i class="bx bx-wallet" style="font-size:.88rem;"></i>
                                        On Account: <strong id="onAccountTotal" style="margin-left:3px;"></strong>
                                    </div>
                                </div>
                            </div>

                            <!-- ── Customer + fields row (matches quotation layout) ── -->
                            <div class="row g-2 align-items-end mb-2">
                                <div class="col-md-4">
                                    <?php if ($isEdit && !$isDraftEdit): ?>
                                    <label class="trans-field-label mb-1">Customer</label>
                                    <select id="customerSearch" name="customerSearch" class="form-select form-select-sm"></select>
                                    <?php elseif (!empty($SOSourceData)): ?>
                                    <!-- SO conversion: skip Select2 entirely, render static readonly display -->
                                    <label class="trans-field-label mb-1">Customer <span class="text-danger">*</span></label>
                                    <div class="d-flex align-items-center gap-2 border rounded px-2 py-1 dc-cust-readonly">
                                        <i class="bx bx-user-circle text-muted dc-cust-icon"></i>
                                        <div class="dc-cust-body">
                                            <div class="fw-semibold text-truncate dc-cust-name"><?php echo htmlspecialchars($SOSourceData->PartyName ?? ''); ?></div>
                                            <?php
                                            $_soMeta = array_filter([
                                                !empty($SOSourceData->PartyArea)   ? htmlspecialchars($SOSourceData->PartyArea)   : '',
                                                !empty($SOSourceData->PartyMobile) ? htmlspecialchars($SOSourceData->PartyMobile) : '',
                                            ]);
                                            if ($_soMeta): ?>
                                            <div class="text-muted dc-cust-meta"><?php echo implode(' &middot; ', $_soMeta); ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <span class="badge bg-label-warning ms-1 dc-cust-so-badge"><i class="bx bx-lock-alt me-1"></i>Locked to <?php echo htmlspecialchars($SOSourceData->UniqueNumber ?? 'SO'); ?></span>
                                    </div>
                                    <input type="hidden" id="customerSearch" name="customerSearch" value="<?php echo (int)($SOSourceData->PartyUID ?? 0); ?>" />
                                    <?php else: ?>
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <label for="customerSearch" class="trans-field-label mb-0">Select Customer <span class="text-danger">*</span></label>
                                        <button type="button" id="addTransCustomer" class="trans-add-btn btn btn-outline-primary btn-sm"><i class="bx bx-plus-circle me-1"></i><?php echo t('btn_add_customer', 'Add Customer'); ?></button>
                                    </div>
                                    <div class="input-group input-group-sm input-group-merge customer-search-group" id="customerGroup_customerSearch">
                                        <span class="input-group-text p-2 cursor-pointer dc-cust-search-icon" id="openCustomerSearchModal"><i class="icon-base bx bx-search"></i></span>
                                        <select id="customerSearch" name="customerSearch" class="form-select form-select-sm">
                                            <?php if ($isDraftEdit && !empty($DCData->PartyUID)): ?>
                                            <?php
                                            $_draftCustLabel = $DCData->PartyName ?? '';
                                            if (!empty($DCData->PartyArea))   $_draftCustLabel .= ', ' . $DCData->PartyArea;
                                            if (!empty($DCData->PartyMobile)) $_draftCustLabel .= ' (' . $DCData->PartyMobile . ')';
                                            ?>
                                            <option value="<?php echo (int)$DCData->PartyUID; ?>" selected="selected"><?php echo htmlspecialchars($_draftCustLabel); ?></option>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-semibold"><?php echo t('lbl_dispatch_date', 'Dispatch Date'); ?> <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-sm input-group-merge">
                                        <span class="input-group-text bg-white"><i class="icon-base bx bx-calendar"></i></span>
                                        <input type="text" class="form-control form-control-sm bg-white" id="transDate_disp" readonly="readonly"
                                            value="<?php echo $isEdit ? format_datedisplay($DCData->TransDate, $_fmt) : format_datedisplay(time(), $_fmt); ?>"
                                            required />
                                    </div>
                                    <input type="hidden" id="transDate" name="transDate" value="<?php echo $isEdit ? htmlspecialchars(format_datedisplay($DCData->TransDate, 'Y-m-d')) : format_datedisplay(time(), 'Y-m-d'); ?>" />
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-semibold">Delivery By</label>
                                    <div class="input-group input-group-sm input-group-merge">
                                        <span class="input-group-text bg-white"><i class="icon-base bx bx-calendar-check"></i></span>
                                        <input type="text" class="form-control form-control-sm bg-white" id="deliveryByDate_disp" readonly="readonly"
                                            value="<?php echo $_deliveryByDisp; ?>" />
                                    </div>
                                    <input type="hidden" id="deliveryByDate" name="deliveryBy" value="<?php echo $_deliveryByDate; ?>" />
                                </div>
                                <div class="col-md-2" id="returnDateWrap" style="<?php echo !in_array($_challanType, ['Returnable', 'Job Work']) ? 'display:none;' : ''; ?>">
                                    <label class="form-label small fw-semibold"><?php echo t('lbl_expected_return_date', 'Expected Return Date'); ?></label>
                                    <div class="input-group input-group-sm input-group-merge">
                                        <span class="input-group-text bg-white"><i class="icon-base bx bx-calendar"></i></span>
                                        <input type="text" class="form-control form-control-sm bg-white" id="returnDate_disp" readonly="readonly"
                                            value="<?php echo $_returnDisp; ?>" />
                                    </div>
                                    <input type="hidden" id="returnDate" name="returnDate" value="<?php echo $_returnDate; ?>" />
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-semibold"><?php echo t('lbl_reference', 'Reference'); ?></label>
                                    <input type="text" id="vehicleNumber" name="vehicleNumber" class="form-control form-control-sm"
                                           placeholder="Vehicle / PO / Ref No." maxlength="50"
                                           value="<?php echo htmlspecialchars($_vehicleNo); ?>" />
                                </div>
                            </div>
                            <div id="customerAddressBox" class="trans-addr-strip d-none"><i class="bx bx-map-pin"></i><span></span></div>

                            <hr class="mt-3"/>

                            <?php $this->load->view('transactions/partials/form_products_add', [
                                'transNotesPlaceholder' => 'Enter notes or anything else',
                                'transNotesContent'     => $_notesVal,
                                'transTermsContent'     => $_termsVal,
                                'transShowDropzone'     => true,
                                'transSignatureUID'     => $isEdit ? (int)($DCData->SignatureUID ?? 0) : 0,
                                'transSignatures'       => $JwtData->User->Signatures ?? [],
                            ]); ?>

                            <!-- ── Inline full-width summary ── -->
                            <?php $cur = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹'); ?>
                            <div id="inlineSummaryBar" class="sticky-bottom-bar mt-3 dc-summary-bar">
                                <div class="d-flex align-items-stretch gap-0">
                                    <div class="dc-bar-left">
                                        <div class="fw-bold dc-bar-total">TOTAL &nbsp;<span class="dc-bar-grand" id="inlineGrandTotal"><?php echo $cur; ?> 0.00</span></div>
                                        <div class="text-muted dc-bar-tax">Includes Total Tax &nbsp;<span id="inlineTotalTax">0.00</span></div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <?php if (!$isEdit || $isDraftEdit): ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="inlineDraftBtn"><i class="bx bx-save me-1"></i><?php echo t('btn_save_draft', 'Save as Draft'); ?></button>
                                    <?php endif; ?>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-primary px-3" id="inlineSaveBtn">
                                            <i class="bx bx-check me-1"></i>Save
                                        </button>
                                        <?php if (!$isEdit || $isDraftEdit): ?>
                                        <button type="button" class="btn btn-sm btn-primary dropdown-toggle dropdown-toggle-split ps-2 pe-2" data-bs-toggle="dropdown" aria-expanded="false">
                                            <span class="visually-hidden">Save options</span>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow dropup dc-save-menu">
                                            <li><span class="dropdown-header py-1">SAVE &amp; PRINT</span></li>
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
                    <div id="stickyBottomBar" class="sticky-bottom-bar dc-summary-bar dc-sticky-bar">
                        <div class="d-flex align-items-stretch gap-0">
                            <div class="dc-bar-left">
                                <div class="fw-bold dc-bar-total">TOTAL &nbsp;<span class="dc-bar-grand" id="stickyGrandTotal"><?php echo $cur; ?> 0.00</span></div>
                                <div class="text-muted dc-bar-tax">Includes Total Tax &nbsp;<span id="stickyTotalTax">0.00</span></div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <?php if (!$isEdit || $isDraftEdit): ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="stickyDraftBtn"><i class="bx bx-save me-1"></i><?php echo t('btn_save_draft', 'Save as Draft'); ?></button>
                            <?php endif; ?>
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-primary px-3" id="stickySaveBtn">
                                    <i class="bx bx-check me-1"></i>Save
                                </button>
                                <?php if (!$isEdit || $isDraftEdit): ?>
                                <button type="button" class="btn btn-sm btn-primary dropdown-toggle dropdown-toggle-split ps-2 pe-2" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="visually-hidden">Save options</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow dropup dc-save-menu">
                                    <li><span class="dropdown-header py-1">SAVE &amp; PRINT</span></li>
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
            <?php $this->load->view('transactions/partials/form_common_modals'); ?>

        </div>

    </div>
</div>

<?php $this->load->view('transactions/partials/additional_charges_modal'); ?>
<?php $this->load->view('common/transactions/footer'); ?>

<script src="/js/common/address.js"></script>
<script src="/js/common/bankdetails.js"></script>
<script src="/js/common/gstin_fetch.js"></script>
<script src="/js/common/customer_form.js"></script>
<script src="/js/transactions/deliverychallans.js"></script>
<script src="/js/transactions/transactions.js"></script>
<script src="/js/transactions/pricelist_trans.js"></script>
<script src="/js/transactions/transprefix.js"></script>
<script src="/js/transactions/modaladdress.js"></script>
<script src="/js/common/category_form.js"></script>
<script src="/js/common/product_form.js"></script>
<script src="/js/transactions/attachments.js"></script>
<?php $this->load->view('transactions/partials/additional_charges_data'); ?>

<script>
var _transFormData = <?php echo json_encode([
    'isEdit'       => $isEdit,
    'isDraftEdit'  => $isDraftEdit,
    'moduleUID'    => 112,
    'enableStorage'=> (bool)$JwtData->GenSettings->EnableStorage,
    'formId'       => $formId,
    'formAction'   => $formAction,
    'upstashUrl'   => $UpstashReadUrl   ?? '',
    'upstashToken' => $UpstashReadToken ?? '',
    'custCacheKey' => $CustomerCacheKey ?? '',
    'returnTab'    => $_returnTab,
    'returnPage'   => (int)$_returnPage,
    'currency'     => $JwtData->GenSettings->CurrenySymbol ?? '₹',
    'decimals'     => (int)($JwtData->GenSettings->DecimalPoints ?? 2),
    'orgState'     => $DispatchAddress->StateText ?? '',
    'editData'     => $isEdit ? [
        'transUID'          => $transUID,
        'custUID'           => (int)($DCData->PartyUID ?? 0),
        'custName'          => $DCData->PartyName  ?? '',
        'custArea'          => $DCData->PartyArea   ?? '',
        'custMobile'        => $DCData->PartyMobile ?? '',
        'custState'         => isset($CustAddr) ? ($CustAddr->StateText ?? '') : '',
        'custAddr'          => [
            'Line1'   => isset($CustAddr) ? ($CustAddr->Line1    ?? '') : '',
            'Line2'   => isset($CustAddr) ? ($CustAddr->Line2    ?? '') : '',
            'City'    => isset($CustAddr) ? ($CustAddr->CityText ?? '') : '',
            'State'   => isset($CustAddr) ? ($CustAddr->StateText ?? '') : '',
            'Pincode' => isset($CustAddr) ? ($CustAddr->Pincode  ?? '') : '',
        ],
        'extraDiscAmount'   => (float)($DCData->ExtraDiscAmount ?? 0),
        'extraDiscType'     => $DCData->ExtraDiscType ?? '',
        'globalDiscPercent' => (float)($DCData->GlobalDiscPercent ?? 0),
        'attachments'       => array_map(function($a) {
            return [
                'AttachUID' => (int)$a->AttachUID,
                'FileName'  => $a->FileName  ?? '',
                'FilePath'  => $a->FilePath  ?? '',
                'FileSize'  => (int)($a->FileSize ?? 0),
                'FileType'  => $a->FileType  ?? '',
                'Url'       => $a->Url       ?? '',
            ];
        }, $DCAttachments ?? []),
        'items'             => array_map(function($item) {
            return [
                'id'               => (int)   $item->ProductUID,
                'text'             => $item->ProductName,
                'itemName'         => $item->ProductName,
                'description'      => $item->Description   ?? '',
                'unitPrice'        => (float)  $item->UnitPrice,
                'taxAmount'        => (float)  $item->TaxAmount,
                'sellingPrice'     => (float)  $item->SellingPrice,
                'purchasePrice'    => (float)($item->PurchasePrice ?? 0),
                'availableQuantity'=> 0,
                'hsnCode'          => '',
                'categoryUID'      => $item->CategoryUID ? (int)$item->CategoryUID : null,
                'categoryName'     => $item->CategoryName  ?? '',
                'storageUID'       => $item->StorageUID  ? (int)$item->StorageUID  : null,
                'taxPercent'       => (float)  $item->TaxPercentage,
                'cgstPercent'      => (float)  $item->CGST,
                'sgstPercent'      => (float)  $item->SGST,
                'igstPercent'      => (float)  $item->IGST,
                'taxDetailsUID'    => (int)    $item->TaxDetailsUID,
                'quantity'         => (float)  $item->Quantity,
                'partNumber'       => $item->PartNumber      ?? '',
                'primaryUnit'      => $item->PrimaryUnitName ?? '',
                'discount'         => (float)  $item->Discount,
                'discountType'     => 'Percentage',
                'discountTypeUID'  => $item->DiscountTypeUID ? (int)$item->DiscountTypeUID : null,
                'discount_amount'  => (float)  $item->DiscountAmount,
                'line_total'       => (float)  $item->TaxableAmount,
                'net_total'        => (float)  $item->NetAmount,
            ];
        }, $DCItems ?? []),
    ] : null,
    'fromSO'        => !$isEdit && !empty($SOSourceData) ? [
        'uid'            => (int)$FromSOUID,
        'customer'       => (int)$SOSourceData->PartyUID,
        'customerName'   => $SOSourceData->PartyName   ?? '',
        'customerArea'   => $SOSourceData->PartyArea   ?? '',
        'customerMobile' => $SOSourceData->PartyMobile ?? '',
        'soNumber'       => $SOSourceData->UniqueNumber ?? '',
    ] : null,
    'fromSOItems'   => !$isEdit && !empty($SOSourceData) ? array_map(function($item) {
        return [
            'id'               => (int)   $item->ProductUID,
            'text'             => $item->ProductName,
            'itemName'         => $item->ProductName,
            'unitPrice'        => (float) $item->UnitPrice,
            'sellingPrice'     => (float) $item->SellingPrice,
            'taxAmount'        => (float) $item->TaxAmount,
            'purchasePrice'    => 0,
            'availableQuantity'=> 0,
            'hsnCode'          => '',
            'categoryUID'      => $item->CategoryUID ? (int)$item->CategoryUID : null,
            'storageUID'       => $item->StorageUID  ? (int)$item->StorageUID  : null,
            'taxPercent'       => (float) $item->TaxPercentage,
            'cgstPercent'      => (float) $item->CGST,
            'sgstPercent'      => (float) $item->SGST,
            'igstPercent'      => (float) $item->IGST,
            'taxDetailsUID'    => (int)   $item->TaxDetailsUID,
            'quantity'         => (float) $item->Quantity,
            'partNumber'       => $item->PartNumber      ?? '',
            'primaryUnit'      => $item->PrimaryUnitName ?? '',
            'discount'         => 0.0,
            'discountType'     => 'Percentage',
            'discountTypeUID'  => null,
            'discount_amount'  => 0.0,
            'line_total'       => (float) $item->TaxableAmount,
            'net_total'        => (float) $item->NetAmount,
        ];
    }, $SOSourceItems ?? []) : [],
    'fromClone'     => !$isEdit && !empty($CloneData) ? [
        'uid'         => (int)($FromCloneUID ?? 0),
        'challanType' => $CloneData->DocType ?? 'Non-Returnable',
        'invoiceType' => $CloneData->InvoiceType   ?? 'Regular',
        'dispatchFrom'=> (int)($CloneData->DispatchFrom ?? 0),
        'notes'       => $CloneData->Notes           ?? '',
        'terms'       => $CloneData->TermsConditions ?? '',
        'reference'   => $CloneData->Reference       ?? '',
    ] : null,
    'fromCloneItems'=> !$isEdit && !empty($CloneData) ? array_map(function($item) {
        return [
            'id'               => (int)   $item->ProductUID,
            'text'             => $item->ProductName,
            'itemName'         => $item->ProductName,
            'unitPrice'        => (float) $item->UnitPrice,
            'sellingPrice'     => (float) $item->SellingPrice,
            'taxAmount'        => (float) $item->TaxAmount,
            'purchasePrice'    => 0,
            'availableQuantity'=> 0,
            'hsnCode'          => '',
            'categoryUID'      => $item->CategoryUID ? (int)$item->CategoryUID : null,
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
    }, $CloneItems ?? []) : [],
]); ?>;
</script>
<script src="/js/transactions/forms/deliverychallan.js"></script>
