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
$_termsVal = "1. Goods once sold will not be taken back or exchanged\n2. All disputes are subject to Gingee jurisdiction only";
if (!$isEdit) {
    if (!empty($SalesOrderData->Notes)) $_notesVal = $SalesOrderData->Notes;
    elseif (!empty($QuotationData->Notes)) $_notesVal = $QuotationData->Notes;
    if (!empty($SalesOrderData->TermsConditions)) $_termsVal = $SalesOrderData->TermsConditions;
    elseif (!empty($QuotationData->TermsConditions)) $_termsVal = $QuotationData->TermsConditions;
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
                    <?php endif; ?>

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
                                            <span style="font-size:.78rem;color:#566a7f;"><?php echo htmlspecialchars(format_datedisplay($InvData->TransDate, 'd M Y')); ?></span>
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
                                    <?php if (!$isEdit): ?>
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <label for="customerSearch" class="trans-field-label mb-0">Select Customer <span class="text-danger">*</span></label>
                                        <button type="button" id="addTransCustomer" class="trans-add-btn btn btn-outline-primary" aria-label="Add new customer"><i class="bx bx-plus-circle me-1"></i> Customer</button>
                                    </div>
                                    <select id="customerSearch" name="customerSearch" class="form-select form-select-sm"></select>
                                    <?php else: ?>
                                    <label for="customerSearch" class="trans-field-label">Select Customer <span class="text-danger">*</span></label>
                                    <select id="customerSearch" name="customerSearch" class="form-select form-select-sm">
                                        <?php if (!empty($InvData->PartyUID)): ?>
                                        <option value="<?php echo (int)$InvData->PartyUID; ?>" selected>
                                            <?php echo htmlspecialchars($InvData->PartyName ?? ''); ?>
                                        </option>
                                        <?php endif; ?>
                                    </select>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-2">
                                    <label for="invoiceType" class="trans-field-label">Type <span class="text-danger">*</span></label>
                                    <select id="invoiceType" name="invoiceType" class="form-select form-select-sm" <?php echo !$isEdit ? 'required' : ''; ?>>
                                        <option value="Regular" <?php echo ($isEdit && ($InvData->QuotationType === 'Regular' || empty($InvData->QuotationType))) || !$isEdit ? 'selected' : ''; ?>>Regular</option>
                                        <option value="Without_GST" <?php echo $isEdit && $InvData->QuotationType === 'Without_GST' ? 'selected' : ''; ?>>Without GST</option>
                                    </select>
                                </div>
                                <?php if (!empty($DispatchAddress)): ?>
                                <div class="col-md-2">
                                    <label class="trans-field-label">Dispatch From <span class="text-danger">*</span></label>
                                    <select id="dispatchFrom" name="dispatchFrom" class="form-select form-select-sm" required>
                                        <option value="<?php echo (int)$DispatchAddress->OrgAddressUID; ?>" selected><?php echo implode(', ', $_addrLines); ?></option>
                                    </select>
                                </div>
                                <?php endif; ?>
                                <div class="col-md-2">
                                    <label for="transDate" class="trans-field-label">Invoice Date <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-sm input-group-merge">
                                        <span class="input-group-text"><i class="icon-base bx bx-calendar"></i></span>
                                        <input type="text" class="form-control form-control-sm" id="transDate" name="transDate" readonly="readonly"
                                            value="<?php echo $isEdit ? htmlspecialchars(format_datedisplay($InvData->TransDate, 'Y-m-d')) : format_datedisplay(time(), 'Y-m-d'); ?>"
                                            required />
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label for="dueDate" class="trans-field-label">Due Date</label>
                                    <div class="input-group input-group-sm input-group-merge">
                                        <span class="input-group-text"><i class="icon-base bx bx-calendar"></i></span>
                                        <input type="text" class="form-control form-control-sm" id="dueDate" name="dueDate" readonly="readonly"
                                            value="<?php echo ($isEdit && !empty($InvData->ValidityDate)) ? htmlspecialchars(format_datedisplay($InvData->ValidityDate, 'Y-m-d')) : ''; ?>" />
                                    </div>
                                </div>
                            </div>

                            <!-- Row 2: Customer address + Reference -->
                            <div class="row g-2 mt-2">
                                <div class="col-md-4">
                                    <div id="customerAddressBox" class="p-2 border border-secondary trans-border-dotted rounded small <?php echo ($isEdit && isset($CustAddr) && !empty($CustAddr)) ? '' : 'd-none'; ?>">
                                        <?php if ($isEdit && isset($CustAddr) && !empty($CustAddr)): ?>
                                        <div><strong>Shipping Address:</strong></div>
                                        <div><?php echo htmlspecialchars($CustAddr->Line1 ?? ''); ?></div>
                                        <div><?php echo htmlspecialchars($CustAddr->Line2 ?? ''); ?></div>
                                        <div><?php echo htmlspecialchars(trim(implode(' - ', array_filter([$CustAddr->CityText ?? '', $CustAddr->Pincode ?? ''])))); ?></div>
                                        <div><?php echo htmlspecialchars($CustAddr->StateText ?? ''); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="referenceDetails" class="trans-field-label">Reference</label>
                                    <input type="text" id="referenceDetails" name="referenceDetails" class="form-control form-control-sm"
                                        placeholder="PO Number, <?php echo $isEdit ? 'Ref No...' : 'Sales Person, Ref No...'; ?>" maxlength="100"
                                        value="<?php echo $isEdit ? htmlspecialchars($InvData->Reference ?? '') : (!empty($SalesOrderData->Reference) ? htmlspecialchars($SalesOrderData->Reference) : (!empty($QuotationData->Reference) ? htmlspecialchars($QuotationData->Reference) : '')); ?>" />
                                </div>
                            </div>
                            <hr class="mt-3"/>

                            <?php $this->load->view('transactions/partials/form_products_add', [
                                'transNotesPlaceholder' => 'Enter notes or anything else',
                                'transNotesContent'     => $_notesVal,
                                'transTermsContent'     => $_termsVal,
                                'transShowDropzone'     => true,
                            ]); ?>

                            <?php if (!$isEdit): ?>
                            <?php
                                $paymentPartyType = 'C';
                                $this->load->view('transactions/partials/payment_section', [
                                    'PaymentTypes'     => $PaymentTypes ?? [],
                                    'BankAccounts'     => $BankAccounts ?? [],
                                    'JwtData'          => $JwtData,
                                    'paymentPartyType' => $paymentPartyType,
                                ]);
                            ?>
                            <?php endif; ?>

                        </div>
                    </div>

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

<?php if ($isEdit): ?>
<!-- Edit-mode attachment preview modal (custom, separate from shared attachPreviewModal) -->
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
<?php endif; ?>

<script src="/js/transactions/invoices.js"></script>
<script src="/js/transactions/transactions.js"></script>
<script src="/js/transactions/transprefix.js"></script>
<script src="/js/transactions/modaladdress.js"></script>
<script src="/js/transactions/products.js"></script>
<script src="/js/combinemodules/products.js"></script>
<?php if (!$isEdit): ?>
<script src="/js/transactions/payment_section.js"></script>
<?php endif; ?>

<script>
const StateInfo     = <?php echo json_encode($StateData); ?>;
const CityInfo      = <?php echo json_encode($CityData); ?>;
const EnableStorage = <?php echo $JwtData->GenSettings->EnableStorage; ?>;
var _isEdit    = <?php echo $isEdit ? 'true' : 'false'; ?>;
var _orgState  = '<?php echo addslashes($DispatchAddress->StateText ?? ''); ?>';
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
}, $QuotationItems ?? [])); ?>;
<?php else: ?>
var _fromQuotation = null;
var _fromQuotItems = [];
<?php endif; ?>
<?php endif; ?>

$(function() {
    'use strict'

    searchCustomers('customerSearch');
    transDatePickr('#transDate', false, 'Y-m-d', false, true, true, true, 'd-m-Y');
    transDatePickr('#dueDate', false, 'Y-m-d', false, false, <?php echo $isEdit ? 'false' : 'true'; ?>, true, 'd-m-Y', '#transDate');

    var _dueDatePicker   = document.querySelector('#dueDate') ? document.querySelector('#dueDate')._flatpickr : null;
    var _transDatePicker = document.querySelector('#transDate') ? document.querySelector('#transDate')._flatpickr : null;
    if (_dueDatePicker && _transDatePicker) {
        <?php if (!$isEdit): ?>
        _dueDatePicker.setDate(_transDatePicker.selectedDates[0], true);
        document.querySelector('#transDate').addEventListener('change', function() {
            if (_transDatePicker.selectedDates[0]) {
                _dueDatePicker.setDate(_transDatePicker.selectedDates[0], true);
            }
        });
        <?php else: ?>
        if (!_dueDatePicker.selectedDates.length) {
            _dueDatePicker.setDate(_transDatePicker.selectedDates[0], true);
        }
        document.querySelector('#transDate').addEventListener('change', function() {
            if (_transDatePicker.selectedDates[0] && !$('#dueDate').data('manually-set')) {
                _dueDatePicker.setDate(_transDatePicker.selectedDates[0], true);
            }
        });
        $('#dueDate').on('change', function() { $(this).data('manually-set', true); });
        <?php endif; ?>
    }

    <?php if ($isEdit): ?>
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

    // Load existing attachments (custom implementation for invoices)
    var _removedAttachIDs = [];
    var _transUID = <?php echo $transUID; ?>;
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
                $('#existingAttachCount').text(resp.Attachments.length).removeClass('d-none');
                $('#accordionUploadFiles').addClass('show');

                $(document).on('click', '.remove-attach-btn', function() {
                    var attachUID = parseInt($(this).data('uid'), 10);
                    $(this).closest('.existing-attach-item').remove();
                    _removedAttachIDs.push(attachUID);
                    var remaining = $('#existingAttachItems .existing-attach-item').length;
                    if (remaining === 0) $('#existingAttachList').addClass('d-none');
                    if (remaining > 0) $('#existingAttachCount').text(remaining);
                    else $('#existingAttachCount').addClass('d-none');
                });
            }
        });
    }
    <?php else: ?>
    var _sourceData  = _fromSO || _fromQuotation;
    var _sourceItems = _fromSO ? _fromSOItems : _fromQuotItems;

    if (_sourceData && _sourceData.uid > 0) {
        if (_sourceData.customer > 0) {
            $('#customerSearch').append(new Option(_sourceData.customerName, _sourceData.customer, true, true)).trigger('change');
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

            var transDate = $.trim($('#transDate').val());
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
            }
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
            if (!_isEdit) {
                fd.append('PaymentRows',   $('#PaymentRowsJson').val() || '');
                fd.append('IsFullyPaid',   $('#isFullyPaid').is(':checked') ? 1 : 0);
                fd.append('RecordPayment', action !== 'draft' ? 1 : 0);
            } else {
                fd.append('RemovedAttachIDs', JSON.stringify(_removedAttachIDs || []));
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

<?php if ($isEdit): ?>
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
<?php endif; ?>

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
