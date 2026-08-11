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
extract(initTransFormCommon($isEdit, $DCData ?? null, '/deliverychallan', $JwtData));

$_prefix          = resolveTransPrefix($isEdit, $isDraftEdit, $PrefixData ?? [], $isEdit ? (int)($DCData->PrefixUID ?? 0) : 0, $isEdit ? (int)($DCData->TransNumber ?? 0) : 0, $NextNumberMap ?? []);
$editPrefixConfig = $_prefix['config'];
$editTransNumber  = $_prefix['transNumber'];
$editPrefixSeg    = $_prefix['seg'];

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

$_nt       = resolveTransNotesTerms($isEdit, $DCData ?? null, $JwtData);
$_notesVal = $_nt['notesVal'];
$_termsVal = $_nt['termsVal'];

$_addrLines = buildDispatchAddressLines($DispatchAddress ?? null);
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
                            <?php $this->load->view('transactions/partials/trans_form_header_btns', ['_hBtnLayout' => 'invoice', '_hDcMenu' => false, '_hEditSavePx3' => false, '_hIsEdit' => $isEdit, '_hIsDraftEdit' => $isDraftEdit, '_hCloseUrl' => $_closeUrl]); ?>
                        </div>

                        <div class="card-body card-body-form-static p-4">

                            <!-- ── Toolbar: Type · Mode · Dispatch From ───────────────── -->
                            <?php
                            $_tsSetting = strtolower($JwtData->TransSettings->DefaultTransactionType ?? 'regular');
                            $_tsDefault = ($_tsSetting === 'without_tax') ? 'Without_GST' : 'Regular';
                            $_dcInvType = $isEdit ? ($DCData->InvoiceType ?? 'Regular') : $_tsDefault;
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
                                        <button type="button" id="addTransCustomer" class="trans-add-btn btn btn-outline-primary btn-sm" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('tooltip_create_customer', 'Create Customer'); ?>"><i class="bx bx-plus-circle me-1"></i><?php echo t('btn_add_customer', 'Add Customer'); ?></button>
                                    </div>
                                    <div class="input-group input-group-sm input-group-merge customer-search-group" id="customerGroup_customerSearch">
                                        <span class="input-group-text p-2 cursor-pointer dc-cust-search-icon party-search-icon" id="openCustomerSearchModal"><i class="icon-base bx bx-search"></i></span>
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
                                        <span class="party-edit-icon" id="editCustomerBtn" title="Edit Customer"><i class="bx bx-edit-alt"></i></span>
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
                                'transEditItems'        => $isEdit ? ($DCItems ?? []) : [],
                            ]); ?>

                            <?php $this->load->view('transactions/partials/trans_summary_bar', ['_barIsSticky' => false, '_barSections' => '1', '_barButtonLayout' => 'split', '_barShowPrint' => 'draft_or_create', '_barUseDcClasses' => true, '_barIsEdit' => $isEdit, '_barIsDraftEdit' => $isDraftEdit]); ?>

                        </div>
                    </div>

                    <?php $this->load->view('transactions/partials/trans_summary_bar', ['_barIsSticky' => true, '_barSections' => '1', '_barButtonLayout' => 'split', '_barShowPrint' => 'draft_or_create', '_barUseDcClasses' => true, '_barIsEdit' => $isEdit, '_barIsDraftEdit' => $isDraftEdit]); ?>

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
<?php $this->load->view('common/imagepreview_modal'); ?>
<?php $this->load->view('common/transactions/footer'); ?>

<script src="/js/common/address.js"></script>
<script src="/js/common/bankdetails.js"></script>
<script src="/js/common/gstin_fetch.js"></script>
<script src="/js/common/phone_cc_dropdown.js"></script>
<script src="/js/common/customer_form.js"></script>
<script src="/js/transactions/deliverychallans.js"></script>
<script src="/js/transactions/transactions.js"></script>
<?php $this->load->view('common/transactions/pricelist_select_modal'); ?>
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
    'updateAction' => 'deliverychallan/updateDeliveryChallan',
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
                'mrp'             => (float)($item->MRP ?? 0),
                'purchasePriceIsIncl' => (bool)($item->IsPurchasePriceIncl ?? 1),
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
