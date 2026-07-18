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
$_closeUrl   = '/deliverychallan';
$_cParams    = [];
if ($_returnTab) $_cParams[] = 'tab=' . urlencode($_returnTab);
if ($_returnPage > 1) $_cParams[] = 'page=' . $_returnPage;
if ($_cParams) $_closeUrl .= '?' . implode('&', $_cParams);

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
                                <button type="submit" name="action" value="draft" class="btn btn-sm btn-outline-secondary"><i class="bx bx-save me-1"></i>Save as Draft</button>
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
                                        <li><button type="submit" class="dropdown-item py-1" name="action" value="save_a4"><i class="bx bx-file text-primary me-2"></i>Save &amp; Print A4</button></li>
                                        <li><button type="submit" class="dropdown-item py-1" name="action" value="save_a5"><i class="bx bx-file-blank text-info me-2"></i>Save &amp; Print A5</button></li>
                                        <li><button type="submit" class="dropdown-item py-1" name="action" value="save_thermal"><i class="bx bx-receipt text-success me-2"></i>Save &amp; Print Thermal</button></li>
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
                                    <div class="d-flex align-items-center gap-2 border rounded px-2 py-1 dc-cust-readonly">
                                        <i class="bx bx-user-circle text-muted dc-cust-icon"></i>
                                        <div class="dc-cust-body">
                                            <div class="fw-semibold text-truncate dc-cust-name"><?php echo htmlspecialchars($DCData->PartyName ?? ''); ?></div>
                                            <?php
                                            $_custMeta = array_filter([
                                                !empty($DCData->PartyArea)   ? htmlspecialchars($DCData->PartyArea)   : '',
                                                !empty($DCData->PartyMobile) ? htmlspecialchars($DCData->PartyMobile) : '',
                                            ]);
                                            if ($_custMeta): ?>
                                            <div class="text-muted dc-cust-meta"><?php echo implode(' &middot; ', $_custMeta); ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <i class="bx bx-lock-alt text-muted dc-cust-lock" title="Cannot change customer on edit"></i>
                                    </div>
                                    <input type="hidden" id="customerSearch" name="customerSearch" value="<?php echo (int)($DCData->PartyUID ?? 0); ?>" />
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
                                        <button type="button" id="addTransCustomer" class="trans-add-btn btn btn-outline-primary btn-sm"><i class="bx bx-plus-circle me-1"></i>Add Customer</button>
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
                                    <label class="form-label small fw-semibold">Dispatch Date <span class="text-danger">*</span></label>
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
                                    <label class="form-label small fw-semibold">Expected Return Date</label>
                                    <div class="input-group input-group-sm input-group-merge">
                                        <span class="input-group-text bg-white"><i class="icon-base bx bx-calendar"></i></span>
                                        <input type="text" class="form-control form-control-sm bg-white" id="returnDate_disp" readonly="readonly"
                                            value="<?php echo $_returnDisp; ?>" />
                                    </div>
                                    <input type="hidden" id="returnDate" name="returnDate" value="<?php echo $_returnDate; ?>" />
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-semibold">Reference</label>
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
                                        <ul class="dropdown-menu dropdown-menu-end shadow dropup dc-save-menu">
                                            <li><span class="dropdown-header py-1">SAVE &amp; PRINT</span></li>
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
                    <div id="stickyBottomBar" class="sticky-bottom-bar dc-summary-bar dc-sticky-bar">
                        <div class="d-flex align-items-stretch gap-0">
                            <div class="dc-bar-left">
                                <div class="fw-bold dc-bar-total">TOTAL &nbsp;<span class="dc-bar-grand" id="stickyGrandTotal"><?php echo $cur; ?> 0.00</span></div>
                                <div class="text-muted dc-bar-tax">Includes Total Tax &nbsp;<span id="stickyTotalTax">0.00</span></div>
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
                                <ul class="dropdown-menu dropdown-menu-end shadow dropup dc-save-menu">
                                    <li><span class="dropdown-header py-1">SAVE &amp; PRINT</span></li>
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
<script>
var _transAdditionalCharges  = <?php echo json_encode(array_values($AdditionalCharges   ?? [])); ?>;
var _transAdditionalTaxOpts  = <?php echo json_encode(array_values($TaxList             ?? [])); ?>;
var _transTransactionCharges = <?php echo json_encode(array_values($TransactionCharges  ?? [])); ?>;
</script>
<script src="/js/transactions/additional_charges.js"></script>

<script>
const EnableStorage = <?php echo $JwtData->GenSettings->EnableStorage; ?>;
var _isEdit    = <?php echo $isEdit ? 'true' : 'false'; ?>;
var _orgState  = '<?php echo addslashes($DispatchAddress->StateText ?? ''); ?>';
var _upstashUrl       = '<?php echo addslashes($UpstashReadUrl   ?? ''); ?>';
var _upstashReadToken = '<?php echo addslashes($UpstashReadToken ?? ''); ?>';
var _custCacheKey     = '<?php echo addslashes($CustomerCacheKey ?? ''); ?>';
var _returnTab  = <?php echo json_encode($_returnTab); ?>;
var _returnPage = <?php echo (int)$_returnPage; ?>;
let imgData;

<?php if ($isEdit): ?>
var _custState = '<?php echo addslashes($CustAddr->StateText ?? ''); ?>';
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
}, $DCItems)); ?>;
<?php else: ?>
<?php if (!empty($SOSourceData)): ?>
var _fromSO = <?php echo json_encode([
    'uid'            => (int)$FromSOUID,
    'customer'       => (int)$SOSourceData->PartyUID,
    'customerName'   => $SOSourceData->PartyName   ?? '',
    'customerArea'   => $SOSourceData->PartyArea   ?? '',
    'customerMobile' => $SOSourceData->PartyMobile ?? '',
    'soNumber'       => $SOSourceData->UniqueNumber ?? '',
]); ?>;
var _fromSOItems = <?php echo json_encode(array_map(function($item) {
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
}, $SOSourceItems ?? [])); ?>;
<?php else: ?>
var _fromSO = null;
var _fromSOItems = [];
<?php endif; ?>

<?php if (!empty($CloneData)): ?>
var _fromClone = <?php echo json_encode([
    'uid'         => (int)($FromCloneUID ?? 0),
    'challanType' => $CloneData->DocType ?? 'Non-Returnable',
    'invoiceType' => $CloneData->InvoiceType   ?? 'Regular',
    'dispatchFrom'=> (int)($CloneData->DispatchFrom ?? 0),
    'notes'       => $CloneData->Notes           ?? '',
    'terms'       => $CloneData->TermsConditions ?? '',
    'reference'   => $CloneData->Reference       ?? '',
]); ?>;
var _fromCloneItems = <?php echo json_encode(array_map(function($item) {
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
}, $CloneItems ?? [])); ?>;
<?php else: ?>
var _fromClone = null;
var _fromCloneItems = [];
<?php endif; ?>
<?php endif; ?>

$(function() {
    'use strict';

    <?php if ((!$isEdit || $isDraftEdit) && empty($SOSourceData)): ?>
    searchCustomers('customerSearch');
    var _dcOACur = '<?php echo addslashes($JwtData->GenSettings->CurrenySymbol ?? "₹"); ?>';
    window._showOnAccountBanner = function(total) {
        if ((parseFloat(total) || 0) > 0) {
            $('#onAccountTotal').text(_dcOACur + ' ' + parseFloat(total).toFixed(2));
            $('#onAccountIndicator').removeClass('d-none');
        } else {
            $('#onAccountIndicator').addClass('d-none');
        }
    };
    $('#customerSearch').on('select2:clear change', function() {
        if (!parseInt($(this).val(), 10)) $('#onAccountIndicator').addClass('d-none');
    });
    <?php if ($isDraftEdit && !empty($DCData->PartyUID) && !empty($CustAddr)): ?>
    (function () {
        var a = <?php echo json_encode([
            'Line1'  => $CustAddr->Line1    ?? '',
            'Line2'  => $CustAddr->Line2    ?? '',
            'City'   => $CustAddr->CityText ?? '',
            'State'  => $CustAddr->StateText ?? '',
            'Pincode'=> $CustAddr->Pincode  ?? '',
        ]); ?>;
        if (a.Line1 || a.City || a.State) {
            var _dcLines = [a.Line1, a.Line2].filter(Boolean).join(', ');
            var _dcLoc   = [a.City, a.State].filter(Boolean).join(', ');
            if (a.Pincode) _dcLoc += ' – ' + a.Pincode;
            $('#customerAddressBox').find('span').text([_dcLines, _dcLoc].filter(Boolean).join(' · '));
            $('#customerAddressBox').removeClass('d-none');
        }
        if (typeof window._onCustStateSelected === 'function' && a.State) {
            window._onCustStateSelected(a.State.trim());
        }
    })();
    <?php endif; ?>
    <?php endif; ?>
    transDatePickr('#transDate_disp',      '#transDate',      false, false, true,  true,  '');
    transDatePickr('#returnDate_disp',     '#returnDate',     false, false, false, false, '#transDate');
    transDatePickr('#deliveryByDate_disp', '#deliveryByDate', false, false, false, false, '');

    // ── DC Expected Return Date auto-fill ─────────────────────────────────────
    // Reads DCDefaultReturnDays from user's settings.
    // Only auto-sets when the field is empty (new DC, not edit mode).
    // Tracks whether the date was auto-set so dispatch-date changes can recalculate it.
    // User manually picking a date marks it as manual — no further auto-recalculation.

    var _dcReturnAutoSet  = false; // true = currently holding an auto-calculated date
    var _dcReturnLocking  = false; // guard: prevents our own setDate triggering the "manual" flag

    var _dcGetReturnDays  = function () {
        var days = (typeof JwtData !== 'undefined' && JwtData.TransSettings && JwtData.TransSettings.DCDefaultReturnDays !== undefined)
            ? parseInt(JwtData.TransSettings.DCDefaultReturnDays, 10)
            : 7;
        return isNaN(days) ? 7 : days;
    };

    var _dcCalcReturnDate = function () {
        var days = _dcGetReturnDays();
        if (days <= 0) return; // 0 = no default, leave blank

        var rawDispatch = $('#transDate').val(); // Y-m-d from hidden field
        if (!rawDispatch) return;

        var dispatchDate = new Date(rawDispatch + 'T00:00:00');
        dispatchDate.setDate(dispatchDate.getDate() + days);

        var fp = document.getElementById('returnDate_disp')?._flatpickr;
        if (!fp) return;

        _dcReturnLocking = true;
        _dcReturnAutoSet = true;
        fp.setDate(dispatchDate, true); // triggers onChange → updates #returnDate hidden field
        _dcReturnLocking = false;
    };

    // Hook onto returnDate flatpickr: mark as manual when user picks a date
    (function () {
        var fpEl = document.getElementById('returnDate_disp');
        if (fpEl && fpEl._flatpickr && Array.isArray(fpEl._flatpickr.config.onChange)) {
            fpEl._flatpickr.config.onChange.push(function () {
                if (!_dcReturnLocking) {
                    _dcReturnAutoSet = false; // user manually selected — stop auto-recalculating
                }
            });
        }
    })();

    // Hook onto transDate flatpickr: update return date minDate + recalculate auto-set date
    (function () {
        var fpEl = document.getElementById('transDate_disp');
        if (fpEl && fpEl._flatpickr && Array.isArray(fpEl._flatpickr.config.onChange)) {
            fpEl._flatpickr.config.onChange.push(function (selectedDates) {
                // Always update the minDate of returnDate picker to match dispatch date
                var returnFp = document.getElementById('returnDate_disp')?._flatpickr;
                if (returnFp && selectedDates.length) {
                    returnFp.set('minDate', selectedDates[0]);

                    // If current return date is now before the new dispatch date, clear it
                    var currentReturn = returnFp.selectedDates[0];
                    if (currentReturn && currentReturn < selectedDates[0]) {
                        _dcReturnLocking = true;
                        returnFp.clear();
                        $('#returnDate').val('');
                        _dcReturnAutoSet = false;
                        _dcReturnLocking = false;
                    }
                }

                // Recalculate auto-set return date if it was auto-set (not manually picked)
                var type = $('#challanType').val();
                if ((type === 'Returnable' || type === 'Job Work') && _dcReturnAutoSet) {
                    _dcCalcReturnDate();
                }
            });
        }
    })();

    // Show/hide Expected Return Date + auto-fill on type change
    $('#challanType').on('change', function () {
        var type = $(this).val();
        if (type === 'Returnable' || type === 'Job Work') {
            $('#returnDateWrap').show();
            // Auto-set only if field is currently empty (create mode, not edit)
            if (!$('#returnDate').val()) {
                _dcCalcReturnDate();
            }
        } else {
            $('#returnDateWrap').hide();
            var fp = document.getElementById('returnDate_disp')?._flatpickr;
            if (fp) fp.clear();
            $('#returnDate').val('');
            _dcReturnAutoSet = false;
        }
    });

    <?php if ($isEdit): ?>
    // Attachments pre-loaded by controller — no AJAX needed
    renderTransAttachmentsFromData(<?php echo json_encode(array_map(function($a) {
        return [
            'AttachUID' => (int)$a->AttachUID,
            'FileName'  => $a->FileName  ?? '',
            'FilePath'  => $a->FilePath  ?? '',
            'FileSize'  => (int)($a->FileSize ?? 0),
            'FileType'  => $a->FileType  ?? '',
            'Url'       => $a->Url       ?? '',
        ];
    }, $DCAttachments ?? [])); ?>);

    $('#extraDiscount').val('<?php echo smartDecimal($DCData->ExtraDiscAmount ?? 0); ?>');
    $('#extDiscountType').val('<?php echo addslashes($DCData->ExtraDiscType ?? ''); ?>').trigger('change');
    $('#globalDiscount').val('<?php echo smartDecimal($DCData->GlobalDiscPercent ?? 0); ?>').trigger('input');

    if (typeof billManager !== 'undefined' && _orgState && _custState) {
        billManager.setInterState(_custState.trim().toLowerCase() !== _orgState.trim().toLowerCase());
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
    if (_fromSO && _fromSO.uid > 0) {
        var _soNum = _fromSO.soNumber || 'SO';

        // ── Apply CSS class to form — handles static + dynamically created elements ─
        // CSS rules in the <style> block above cover:
        //   #openCustomerSearchModal, #addTransCustomer, #addTransProduct,
        //   .prod-header-static (entire search row), .deleteBillItem (per-row delete)
        document.getElementById('<?php echo $formId; ?>').classList.add('so-linked-dc');

        // ── Pre-fill SO items ─────────────────────────────────────────────────
        if (typeof billManager !== 'undefined' && typeof formationTableBillItems === 'function'
                && Array.isArray(_fromSOItems) && _fromSOItems.length > 0) {
            $('#billTableBody').empty();
            _fromSOItems.forEach(function(item) {
                var added = billManager.addItem(item, item.quantity);
                if (added !== false) formationTableBillItems(billManager.getItemById(item.id));
            });
            if (typeof updateItemTaxBreakdown === 'function') updateItemTaxBreakdown();
            billManager.updateSummary();
        }

        // ── Restriction 2: Block adding non-SO products via billManager ───────
        var _soProductIds = {};
        _fromSOItems.forEach(function(item) { _soProductIds[item.id] = true; });

        if (typeof billManager !== 'undefined') {
            var _origAddItem = billManager.addItem.bind(billManager);
            billManager.addItem = function(item, qty) {
                if (item && item.id && !_soProductIds[item.id]) {
                    showToastNotification('Only items from ' + _soNum + ' can be dispatched on this challan.', 'warning');
                    return false;
                }
                return _origAddItem(item, qty);
            };
        }

        // Show info notice below the product section header
        $('#addTransProduct').closest('.card-header').after(
            '<div class="alert dc-so-notice d-flex align-items-center gap-2 py-2 px-3 mx-3 mt-2">' +
            '<i class="bx bx-link-alt flex-shrink-0 dc-so-notice-icon"></i>' +
            '<span>Linked to <strong>' + _soNum + '</strong>. You may adjust quantities or remove items for a partial dispatch. Adding new products is not allowed.</span>' +
            '</div>'
        );

        // ── Restriction 3: Cap quantity to SO ordered quantity ────────────────
        var _soQtyMap = {};
        _fromSOItems.forEach(function(item) { _soQtyMap[item.id] = item.quantity; });

        $('#<?php echo $formId; ?>').on('change blur', '#billTableBody input[type="number"]', function () {
            var $row   = $(this).closest('tr[data-item-id]');
            var itemId = parseInt($row.data('item-id')) || 0;
            if (!itemId || !_soQtyMap.hasOwnProperty(itemId)) return;
            var maxQty = _soQtyMap[itemId];
            var entered = parseFloat($(this).val()) || 0;
            if (entered > maxQty) {
                $(this).val(maxQty);
                showToastNotification('Quantity cannot exceed SO ordered qty (' + maxQty + ').', 'warning');
                $(this).trigger('input');
            }
        });
    } else if (_fromClone && _fromClone.uid > 0) {

        // ── Pre-fill Type (invoiceType select) ───────────────────────────────
        if (_fromClone.invoiceType) {
            $('#dcInvoiceType').val(_fromClone.invoiceType).trigger('change');
        }

        // ── Pre-fill Mode (challanType select) — triggers return-date visibility
        if (_fromClone.challanType) {
            $('#challanType').val(_fromClone.challanType).trigger('change');
        }

        // ── Pre-fill Dispatch From ────────────────────────────────────────────
        if (_fromClone.dispatchFrom > 0 && typeof window._setDispatchFrom === 'function') {
            window._setDispatchFrom(_fromClone.dispatchFrom);
        }

        // ── Pre-fill Notes / Reference ────────────────────────────────────────
        if (_fromClone.notes)     $('#transNotes').val(_fromClone.notes);
        if (_fromClone.reference) $('#vehicleNumber').val(_fromClone.reference);

        // ── Pre-fill items ────────────────────────────────────────────────────
        if (typeof billManager !== 'undefined' && typeof formationTableBillItems === 'function'
                && Array.isArray(_fromCloneItems) && _fromCloneItems.length > 0) {
            $('#billTableBody').empty();
            _fromCloneItems.forEach(function(item) {
                var added = billManager.addItem(item, item.quantity);
                if (added !== false) formationTableBillItems(billManager.getItemById(item.id));
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
                if (!prefixUID || prefixUID <= 0) return showFormError('Please select a delivery challan prefix.');

                var transNumber = $.trim($('#transNumber').val());
                if (!transNumber || parseInt(transNumber, 10) <= 0) return showFormError('Transaction number must be greater than 0.');
            }

            var transDate = $.trim($('#transDate').val());
            if (!transDate || !/^\d{4}-\d{2}-\d{2}$/.test(transDate)) return showFormError('Please enter a valid dispatch date.');

            var items = typeof billManager !== 'undefined' ? billManager.getAllItems() : [];
            if (!items || items.length === 0) return showFormError('Please add at least one product.');

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

            var charges = { AdditionalCharges: JSON.stringify(typeof collectAdditionalCharges === 'function' ? collectAdditionalCharges() : []) };

            var postData = $.extend({
                transPrefixSelect      : parseInt($('#transPrefixSelect').val(), 10) || 0,
                transNumber            : $.trim($('#transNumber').val()),
                transDate              : transDate,
                returnDate             : $.trim($('#returnDate').val()),
                customerSearch         : customerUID,
                invoiceType            : $('#dcInvoiceType').val() || 'Regular',
                challanType            : $('#challanType').val() || 'Non-Returnable',
                vehicleNumber          : $.trim($('#vehicleNumber').val()),
                deliveryBy             : $.trim($('#deliveryByDate').val()),
                dispatchFrom           : $('#dispatchFrom').val() || '',
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
            } else {
                postData.fromSOUID = parseInt($('#fromSOUID').val(), 10) || 0;
            }

            var formData = new FormData();
            $.each(postData, function(k, v) { formData.append(k, v); });
            collectTransAttachData(formData);
            if (typeof _plTransInjectFormData === 'function') _plTransInjectFormData(formData);

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
                        _setPendingToast('_dcPendingToast', response.Message, 'success');
                        window.location.href = _buildReturnUrl('/deliverychallan');
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
    var dec = <?php echo (int)($JwtData->GenSettings->DecimalPoints ?? 2); ?>;
    function _r2(n) { return parseFloat((+n || 0).toFixed(dec)); }
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

    var _totEl = document.querySelector('.bill_tot_amt');
    if (_totEl) new MutationObserver(_sync).observe(_totEl, { childList: true, subtree: true, characterData: true });
    _sync();
})();
</script>
