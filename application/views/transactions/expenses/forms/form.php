<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$isEdit     = isset($ExpenseData) && $ExpenseData !== null;
$expense    = $ExpenseData ?? null;
$expUID     = $isEdit ? (int)$expense->ExpenseUID : 0;
$formAction = $isEdit ? '/expenses/updateExpense' : '/expenses/addExpense';

$_fmt              = $JwtData->GenSettings->FormDateFormat ?? 'd-m-Y';
$expDate           = $isEdit ? htmlspecialchars($expense->ExpenseDate  ?? date('Y-m-d')) : '';
$expDateDisp       = $expDate ? format_datedisplay($expDate, $_fmt) : '';
$taxApplicable     = $isEdit ? (int)$expense->TaxApplicable   : 0;
$taxPct            = $isEdit ? (float)$expense->TaxPercentage : 0;
$tdsApplicable     = $isEdit ? (int)$expense->TDSApplicable   : 0;
$tdsPct            = $isEdit ? (float)$expense->TDSPercentage : 0;
$rcmApplicable     = $isEdit ? (int)($expense->RCMApplicable ?? 0) : 0;
$rcmAmount         = $isEdit ? (float)($expense->RCMAmount ?? 0)   : 0;
$roundOff          = $isEdit ? (float)($expense->RoundOff ?? 0)    : 0;
$catUID            = $isEdit ? (int)$expense->CategoryUID    : 0;
$simpleExpAmt      = ($isEdit && !$taxApplicable) ? number_format((float)($expense->Amount ?? 0), 2, '.', '') : '';
$tdsSectionUID     = $isEdit ? (int)($expense->TdsSectionUID ?? 0) : 0;
$vendorUID         = $isEdit ? (int)($expense->VendorUID ?? 0) : 0;
$vendorName        = $isEdit ? htmlspecialchars($expense->VendorName ?? '') : '';
$supplierDate      = $isEdit ? htmlspecialchars($expense->SupplierInvoiceDate ?? '') : '';
$supplierDateDisp  = $supplierDate ? format_datedisplay($supplierDate, $_fmt) : '';
$supplierRef       = $isEdit ? htmlspecialchars($expense->SupplierInvoiceSerialNo ?? '') : '';
$amountType        = $isEdit ? htmlspecialchars($expense->AmountType ?? 'NetAmount') : 'NetAmount';
if ($amountType === 'TaxableAmount') $amountType = 'NetAmount'; // normalise legacy DB value
$notes             = $isEdit ? htmlspecialchars($expense->Notes ?? '') : '';
$isPaid            = $isEdit ? (int)$expense->IsPaid  : 0;
$pmtTypeUID        = $isEdit ? (int)($expense->PaymentTypeUID ?? 0) : 0;
$bankUID           = $isEdit ? (int)($expense->BankAccountUID ?? 0) : 0;
$pmtDate           = $isEdit ? htmlspecialchars($expense->PaymentDate ?? date('Y-m-d')) : '';
$pmtDateDisp       = $pmtDate ? format_datedisplay($pmtDate, $_fmt) : '';
$pmtNotes          = $isEdit ? htmlspecialchars($expense->PaymentNotes ?? '') : '';

$categories   = $Categories   ?? [];
$paymentTypes = $PaymentTypes ?? [];
$bankAccounts = $BankAccounts ?? [];
$existItems   = $ExpenseItems ?? [];

$cur = htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹');

$vendorStateCode = $VendorStateCode ?? '';
$orgStateCode    = $JwtData->Org->StateCode ?? '';
$isInterState    = ($vendorStateCode !== '' && $orgStateCode !== '' && $vendorStateCode !== $orgStateCode);


// Pre-computed values for edit-mode footer/summary pre-population
if ($isEdit) {
    $_expAmt    = round((float)($expense->Amount    ?? 0));
    $_taxAmt    = round((float)($expense->TaxAmount ?? 0));
    $_cgstTotal = round((float)($expense->CGSTTotal ?? 0));
    $_sgstTotal = round((float)($expense->SGSTTotal ?? 0));
    $_igstTotal = round((float)($expense->IGSTTotal ?? 0));
    $_tdsAmt    = round((float)($expense->TDSAmount ?? 0));
    $_netAmt    = round((float)($expense->NetAmount ?? 0));
    $_grandTotal = round($_expAmt + $_taxAmt); // Grand Total = taxable + tax, no round-off
    $_cgstDisp  = $isInterState ? $_igstTotal : $_cgstTotal;
} else {
    $_expAmt = $_taxAmt = $_cgstTotal = $_sgstTotal = $_igstTotal = $_tdsAmt = $_netAmt = $_grandTotal = $_cgstDisp = 0.0;
}
?>

<?php $this->load->view('common/transactions/header'); ?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => $PageTitle       ?? 'Expenses',
                    'pageDescription' => $PageDescription ?? '',
                ]); ?>

                <form id="expenseForm" action="<?php echo $formAction; ?>" method="post" autocomplete="off">
                    <?php if ($isEdit): ?>
                        <input type="hidden" name="ExpenseUID" value="<?php echo $expUID; ?>">
                        <input type="hidden" id="expPaidAmount" value="<?php echo number_format((float)($expense->PaidAmount ?? 0), 4, '.', ''); ?>">
                    <?php endif; ?>

                    <!-- ── Sticky action bar — full width, directly below page header ── -->
                    <div class="exp-form-action-bar trans-theme d-flex align-items-center justify-content-between px-4 py-2">
                        <div class="d-flex align-items-center gap-3">
                            <span class="text-muted" style="font-size:.82rem;"><?php echo t('lbl_with_tax', 'With Tax'); ?></span>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" id="taxToggle"
                                       <?php echo $taxApplicable ? 'checked' : ''; ?>
                                       <?php echo $isEdit ? 'disabled' : ''; ?>>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="submit" class="btn btn-sm btn-primary px-4" id="submitBtn">
                                <i class="bx bx-check me-1"></i><?php echo $isEdit ? t('btn_update', 'Update') : t('btn_save', 'Save'); ?>
                            </button>
                            <a href="/expenses" class="btn btn-sm btn-outline-danger px-3">
                                <i class="bx bx-x me-1"></i><?php echo t('cancel', 'Cancel'); ?>
                            </a>
                        </div>
                    </div>

                    <!-- ── Form body ───────────────────────────────────────── -->
                    <div class="container-xxl flex-grow-1 container-p-y pt-3">
                        <div class="row g-3 align-items-start">

                            <!-- ── Left (8/12) ────────────────────────────── -->
                            <div class="col-lg-8">

                                <!-- Basic Details -->
                                <div class="card mb-3">
                                    <div class="card-header modal-header-center-sticky p-2 mb-3">
                                        <h5 class="modal-title mb-0">Basic Details</h5>
                                    </div>
                                    <div class="card-body pb-3">
                                        <div class="row g-4">

                                            <!-- Simple Expense Amount (without-tax mode only) -->
                                            <div class="col-md-6" id="simpleExpAmtField" style="display:<?php echo $taxApplicable ? 'none' : 'block'; ?>;">
                                                <label for="simpleExpAmtInput" class="form-label">Expense Amount <span class="text-danger">*</span></label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text"><?php echo $cur; ?></span>
                                                    <input type="text" class="form-control text-end" id="simpleExpAmtInput"
                                                           name="SimpleAmount" placeholder="0.00" value="<?php echo smartDecimal($simpleExpAmt ?? 0); ?>">
                                                </div>
                                            </div>

                                            <!-- Vendor (with-tax only) -->
                                            <div class="col-12 with-tax-field" style="display:<?php echo $taxApplicable ? 'block' : 'none'; ?>;">
                                                <?php if ($isEdit): ?>
                                                <label class="form-label">Vendor</label>
                                                <div class="d-flex align-items-center gap-2 border rounded px-2 py-1 dc-cust-readonly">
                                                    <i class="bx bx-buildings text-muted dc-cust-icon"></i>
                                                    <div class="dc-cust-body">
                                                        <div class="fw-semibold text-truncate dc-cust-name"><?php echo htmlspecialchars($vendorName); ?></div>
                                                    </div>
                                                    <i class="bx bx-lock-alt text-muted dc-cust-lock" title="Vendor cannot be changed after saving"></i>
                                                </div>
                                                <input type="hidden" id="vendorSearch" name="VendorUID" value="<?php echo $vendorUID; ?>">
                                                <?php else: ?>
                                                <label for="vendorSearch" class="form-label">Vendor <span class="text-danger">*</span></label>
                                                <div class="input-group input-group-sm input-group-merge vendor-search-group" id="vendorGroup_vendorSearch">
                                                    <span class="input-group-text p-2 cursor-pointer party-search-icon" id="openVendorSearchModal" style="background:#f0ebff;border-color:#d9d0ff;color:#6f42c1;"><i class="icon-base bx bx-search"></i></span>
                                                    <select class="form-select form-select-sm" id="vendorSearch" name="VendorUID">
                                                        <option value=""></option>
                                                    </select>
                                                    <span class="party-edit-icon" id="editVendorBtn" title="Edit Vendor"><i class="bx bx-edit-alt"></i></span>
                                                </div>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Expense Date | Supplier Invoice Date -->
                                            <div class="col-md-6">
                                                <label for="expDate_disp" class="form-label"><?php echo t('lbl_expense_date', 'Expense Date'); ?> <span class="text-danger">*</span></label>
                                                <div class="input-group input-group-merge">
                                                    <span class="input-group-text"><i class="bx bx-calendar"></i></span>
                                                    <input type="text" class="form-control" id="expDate_disp"
                                                           value="<?php echo $expDateDisp; ?>" required readonly placeholder="Select date">
                                                    <input type="hidden" id="expDate" name="ExpenseDate" value="<?php echo $expDate; ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6 with-tax-field" style="display:<?php echo $taxApplicable ? 'block' : 'none'; ?>;">
                                                <label for="supplierDate_disp" class="form-label"><?php echo t('lbl_supplier_invoice_date', 'Supplier Invoice Date'); ?> <span class="fw-normal text-muted">(Optional)</span></label>
                                                <div class="input-group input-group-merge">
                                                    <span class="input-group-text"><i class="bx bx-calendar"></i></span>
                                                    <input type="text" class="form-control" id="supplierDate_disp"
                                                           value="<?php echo $supplierDateDisp; ?>" readonly placeholder="Select date">
                                                    <input type="hidden" id="supplierDate" name="SupplierInvoiceDate" value="<?php echo $supplierDate; ?>">
                                                </div>
                                            </div>

                                            <!-- Amount Type | Supplier Invoice Ref (with-tax only) -->
                                            <div class="col-md-6 with-tax-field" style="display:<?php echo $taxApplicable ? 'block' : 'none'; ?>;">
                                                <label for="expAmountType" class="form-label">Amount Type</label>
                                                <select class="form-select" id="expAmountType" name="AmountType">
                                                    <option value="NetAmount"   <?php echo $amountType === 'NetAmount'   ? 'selected' : ''; ?>>Net Amount (tax added on top)</option>
                                                    <option value="TotalAmount" <?php echo $amountType === 'TotalAmount' ? 'selected' : ''; ?>>Total Amount (tax-inclusive)</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 with-tax-field" style="display:<?php echo $taxApplicable ? 'block' : 'none'; ?>;">
                                                <label class="form-label">Supplier Invoice / Ref No. <span class="fw-normal text-muted">(Optional)</span></label>
                                                <input type="text" class="form-control" name="SupplierInvoiceSerialNo"
                                                       maxlength="100" placeholder="Invoice or reference number"
                                                       value="<?php echo $supplierRef; ?>">
                                            </div>

                                            <!-- Category | Notes -->
                                            <div class="<?php echo $taxApplicable ? 'col-md-6' : 'col-12'; ?>" id="catCol">
                                                <label class="form-label">
                                                    Category
                                                    <button type="button" class="btn btn-link p-0 ms-2 lh-1" id="addCategoryBtn" style="font-size:.75rem;vertical-align:baseline;">
                                                        <i class="bx bx-plus-circle"></i> New
                                                    </button>
                                                </label>
                                                <select class="form-select select2-expense-category" name="CategoryUID">
                                                    <option value="">— Select Category —</option>
                                                    <?php foreach ($categories as $cat): ?>
                                                        <option value="<?php echo (int)$cat->CategoryUID; ?>"
                                                            <?php echo $catUID === (int)$cat->CategoryUID ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($cat->CategoryName); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="<?php echo $taxApplicable ? 'col-md-6' : 'col-12'; ?>" id="notesCol">
                                                <label class="form-label">Notes</label>
                                                <textarea class="form-control" name="Notes" rows="2"
                                                          placeholder="Notes or description..."><?php echo $notes; ?></textarea>
                                            </div>

                                        </div>
                                    </div>
                                </div>

                                <!-- Expense Items (with-tax only) -->
                                <div class="card mb-3" id="expItemsCard" style="display:<?php echo $taxApplicable ? 'block' : 'none'; ?>;">
                                    <div class="card-header modal-header-center-sticky p-2 d-flex align-items-center justify-content-between">
                                        <h5 class="modal-title mb-0">Expense Items</h5>
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="addItemRowBtn">
                                            <i class="bx bx-plus me-1"></i>Add Item
                                        </button>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table align-middle mb-0" id="expItemsTable" style="font-size:.82rem;">
                                                <thead style="background:#f8f9fb;">
                                                    <tr>
                                                        <th class="ps-3 py-2 text-muted fw-semibold" style="width:30px;font-size:.7rem;">#</th>
                                                        <th class="py-2 text-muted fw-semibold" style="font-size:.7rem;">DESCRIPTION</th>
                                                        <th class="py-2 text-muted fw-semibold" style="width:140px;font-size:.7rem;">CATEGORY</th>
                                                        <th class="tax-col py-2 text-muted fw-semibold text-end pe-2" style="display:<?php echo $taxApplicable ? 'table-cell' : 'none'; ?>;width:110px;font-size:.7rem;">
                                                            <span class="item-amount-label">AMOUNT</span>
                                                        </th>
                                                        <th class="no-tax-col py-2 text-muted fw-semibold text-end pe-2" style="display:<?php echo $taxApplicable ? 'none' : 'table-cell'; ?>;width:140px;font-size:.7rem;">AMOUNT</th>
                                                        <th class="tax-col py-2 text-muted fw-semibold text-end pe-2" style="display:<?php echo $taxApplicable ? 'table-cell' : 'none'; ?>;width:100px;font-size:.7rem;">TAX %</th>
                                                        <th class="tax-col cgst-col py-2 text-muted fw-semibold text-end pe-2" style="display:<?php echo $taxApplicable ? 'table-cell' : 'none'; ?>;width:85px;font-size:.7rem;"><span class="cgst-col-label"><?php echo $isInterState ? 'IGST' : 'CGST'; ?></span></th>
                                                        <th class="tax-col sgst-col py-2 text-muted fw-semibold text-end pe-2" style="display:<?php echo ($taxApplicable && !$isInterState) ? 'table-cell' : 'none'; ?>;width:85px;font-size:.7rem;">SGST</th>
                                                        <th class="tax-col py-2 text-muted fw-semibold text-end" style="display:<?php echo $taxApplicable ? 'table-cell' : 'none'; ?>;width:140px;font-size:.7rem;"><span class="item-total-label">Total Amount</span></th>
                                                    </tr>
                                                </thead>
                                                <tbody id="expItemsBody">
                                                    <?php if (!empty($existItems)): ?>
                                                        <?php foreach ($existItems as $idx => $ei): ?>
                                                        <tr class="exp-item-row border-top" data-row="<?php echo $idx; ?>" data-item-uid="<?php echo (int)$ei->ExpenseItemUID; ?>">
                                                            <td class="ps-3 text-muted" style="font-size:.72rem;"><?php echo $idx + 1; ?></td>
                                                            <td>
                                                                <input type="text" class="form-control form-control-sm item-desc"
                                                                       placeholder="Description (optional)"
                                                                       value="<?php echo htmlspecialchars($ei->ItemDescription ?? ''); ?>">
                                                            </td>
                                                            <td>
                                                                <select class="form-select form-select-sm item-cat">
                                                                    <option value="0">-- Category --</option>
                                                                    <?php foreach ($categories as $cat): ?>
                                                                    <option value="<?php echo (int)$cat->CategoryUID; ?>"
                                                                        <?php echo ($ei->CategoryUID ?? 0) == $cat->CategoryUID ? 'selected' : ''; ?>>
                                                                        <?php echo htmlspecialchars($cat->CategoryName); ?>
                                                                    </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </td>
                                                            <td class="tax-col" style="display:<?php echo $taxApplicable ? 'table-cell' : 'none'; ?>;">
                                                                <?php $dispAmt = $amountType === 'TotalAmount' ? (float)$ei->TotalAmount : (float)$ei->Amount; ?>
                                                                <input type="text" class="form-control form-control-sm item-amount text-end"
                                                                       placeholder="0.00"
                                                                       value="<?php echo $dispAmt > 0 ? number_format($dispAmt, 2, '.', '') : ''; ?>">
                                                            </td>
                                                            <td class="no-tax-col" style="display:<?php echo $taxApplicable ? 'none' : 'table-cell'; ?>;">
                                                                <input type="text" class="form-control form-control-sm item-amount text-end pe-3"
                                                                       placeholder="0.00"
                                                                       value="<?php echo (float)$ei->Amount > 0 ? number_format((float)$ei->Amount, 2, '.', '') : ''; ?>">
                                                            </td>
                                                            <td class="tax-col" style="display:<?php echo $taxApplicable ? 'table-cell' : 'none'; ?>;">
                                                                <select class="form-select form-select-sm item-tax-pct">
                                                                    <option value="">GST%</option>
                                                                    <?php if ($ei->TaxPercentage > 0): ?>
                                                                    <option value="<?php echo (float)$ei->TaxPercentage; ?>" selected><?php echo (float)$ei->TaxPercentage; ?>%</option>
                                                                    <?php endif; ?>
                                                                </select>
                                                            </td>
                                                            <?php
                                                            $eiCgst = $isInterState ? 0.0 : (float)($ei->CGSTAmount ?? ((float)$ei->TaxAmount / 2));
                                                            $eiSgst = $isInterState ? 0.0 : (float)($ei->SGSTAmount ?? ((float)$ei->TaxAmount / 2));
                                                            $eiIgst = $isInterState ? (float)$ei->TaxAmount : 0.0;
                                                            ?>
                                                            <td class="tax-col cgst-col text-end pe-2" style="display:<?php echo $taxApplicable ? 'table-cell' : 'none'; ?>;">
                                                                <span class="item-cgst-amt text-muted"><?php echo number_format($isInterState ? $eiIgst : $eiCgst, 2); ?></span>
                                                            </td>
                                                            <td class="tax-col sgst-col text-end pe-2" style="display:<?php echo ($taxApplicable && !$isInterState) ? 'table-cell' : 'none'; ?>;">
                                                                <span class="item-sgst-amt text-muted"><?php echo number_format($eiSgst, 2); ?></span>
                                                            </td>
                                                            <td class="tax-col" style="display:<?php echo $taxApplicable ? 'table-cell' : 'none'; ?>;">
                                                                <div class="d-flex align-items-center gap-1">
                                                                    <button type="button" class="item-del-btn flex-shrink-0" title="Remove"><i class="bx bx-trash me-2"></i></button>
                                                                    <span class="item-total fw-semibold flex-fill text-end" style="font-size:.82rem;"><?php echo number_format((float)$ei->TotalAmount, 2); ?></span>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                    <tr id="expEmptyRow"<?php echo !empty($existItems) ? ' class="d-none"' : ''; ?>>
                                                        <td colspan="9" class="text-center py-4">
                                                            <i class="bx bx-package exp-empty-icon"></i>
                                                            No items yet — click <strong>+ Add Item</strong> to add expense lines.
                                                        </td>
                                                    </tr>
                                                </tbody>
                                                <tfoot style="background:#f8f9fb;">
                                                    <?php $ntd = 'display:' . ($taxApplicable ? 'none' : 'table-cell') . ';'; ?>
                                                    <tr class="tax-col" id="footerTaxableRow" style="display:<?php echo $taxApplicable ? 'table-row' : 'none'; ?>;">
                                                        <td colspan="4" class="border-0 py-1"></td>
                                                        <td class="no-tax-col border-0 py-1" style="<?php echo $ntd; ?>"></td>
                                                        <td colspan="3" class="text-end py-1 text-muted border-0" style="font-size:.78rem;">Taxable Amount</td>
                                                        <td class="text-end py-1 fw-semibold border-0 pe-3" id="footerTaxable" style="font-size:.82rem;"><?php echo smartDecimal($_expAmt); ?></td>
                                                    </tr>
                                                    <tr class="tax-col" id="footerCgstRow" style="display:<?php echo $taxApplicable ? 'table-row' : 'none'; ?>;">
                                                        <td colspan="4" class="border-0 py-1"></td>
                                                        <td class="no-tax-col border-0 py-1" style="<?php echo $ntd; ?>"></td>
                                                        <td colspan="3" class="text-end py-1 text-muted border-0" style="font-size:.78rem;" id="footerCgstLabel"><?php echo $isInterState ? 'IGST' : 'CGST'; ?></td>
                                                        <td class="text-end py-1 border-0 pe-3" id="footerCgstAmt" style="font-size:.82rem;"><?php echo smartDecimal($_cgstDisp); ?></td>
                                                    </tr>
                                                    <tr class="tax-col sgst-col" id="footerSgstRow" style="display:<?php echo ($taxApplicable && !$isInterState) ? 'table-row' : 'none'; ?>;">
                                                        <td colspan="4" class="border-0 py-1"></td>
                                                        <td class="no-tax-col border-0 py-1" style="<?php echo $ntd; ?>"></td>
                                                        <td colspan="3" class="text-end py-1 text-muted border-0" style="font-size:.78rem;">SGST</td>
                                                        <td class="text-end py-1 border-0 pe-3" id="footerSgstAmt" style="font-size:.82rem;"><?php echo smartDecimal($_sgstTotal); ?></td>
                                                    </tr>
                                                    <tr id="footerRoundOffRow" style="display:none;"></tr>
                                                    <tr>
                                                        <td colspan="4" class="border-top py-2"></td>
                                                        <td class="no-tax-col border-top py-2" style="<?php echo $ntd; ?>"></td>
                                                        <td colspan="3" class="text-end py-2 border-top fw-semibold" style="font-size:.82rem;">Grand Total</td>
                                                        <td class="text-end py-2 border-top fw-bold pe-3" id="footerGrandTotal"><?php echo smartDecimal($_grandTotal); ?></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- Attachments -->
                                <div class="card mb-3">
                                    <div class="card-header modal-header-center-sticky p-2 mb-3 d-flex align-items-center justify-content-between">
                                        <h5 class="modal-title mb-0">
                                            <span class="d-flex align-items-center gap-2">
                                                <i class="bx bx-paperclip" style="color:#6366f1;font-size:1.1rem;"></i>
                                                Attach Files
                                            </span>
                                        </h5>
                                        <span id="transAttachBadge" class="badge rounded-pill d-none"
                                              style="background:#ede9fe;color:#6366f1;font-size:.7rem;font-weight:600;"></span>
                                    </div>
                                    <div class="card-body px-3 pt-3 pb-3">
                                        <div id="transAttachZone" class="prod-attach-zone" onclick="_attachZoneTrigger('Transaction', event)">
                                            <div id="transAttachEmpty" class="prod-attach-empty">
                                                <i class="bx bx-upload" id="transAttachIcon" style="font-size:1.4rem;color:#9ca3af;display:block;margin-bottom:3px;"></i>
                                                <div id="transAttachLabel" style="font-size:.78rem;font-weight:600;color:#6b7280;">Drag &amp; drop files here</div>
                                            </div>
                                        </div>
                                        <span id="transAttachHint" style="font-size:.72rem;color:#9ca3af;"></span>
                                        <div id="transAttachList" class="prod-attach-list mt-2" style="display:none;"></div>
                                        <input type="file" id="transAttachInput" multiple style="display:none;">
                                        <input type="hidden" id="RemovedAttachIDs" name="RemovedAttachIDs" value="">
                                    </div>
                                </div>

                            </div><!-- /col-lg-8 -->

                            <!-- ── Right (4/12) ────────────────────────────── -->
                            <div class="col-lg-4">

                                <!-- Vendor Balance Card — visible only when a vendor is selected in with-tax mode -->
                                <div class="card mb-3 d-none" id="vendorBalCard">
                                    <div class="card-body py-3 px-3">
                                        <div class="text-muted mb-2" style="font-size:.72rem;font-weight:600;letter-spacing:.3px;text-transform:uppercase;">Vendor Balance</div>
                                        <div class="d-flex align-items-baseline gap-2">
                                            <div class="fw-bold" id="vendorBalAmt" style="font-size:1.08rem;">—</div>
                                            <span class="badge rounded-pill" id="vendorBalBadge" style="font-size:.7rem;"></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- RCM Card -->
                                <div class="card mb-3" id="rcmCard" style="display:<?php echo $taxApplicable ? 'block' : 'none'; ?>;">
                                    <div class="card-body py-3 px-3">
                                        <div class="d-flex align-items-start justify-content-between gap-2">
                                            <div>
                                                <div class="fw-semibold" style="font-size:.85rem;">Reverse Charge (RCM)</div>
                                                <div class="text-muted mt-1" style="font-size:.75rem;">GST paid directly to government</div>
                                            </div>
                                            <div class="form-check form-switch mb-0 flex-shrink-0">
                                                <input class="form-check-input" type="checkbox" id="rcmToggle"
                                                       <?php echo $rcmApplicable ? 'checked' : ''; ?>
                                                       <?php echo $isEdit ? 'disabled' : ''; ?>>
                                            </div>
                                        </div>
                                        <div id="rcmSection" style="display:<?php echo $rcmApplicable ? 'block' : 'none'; ?>; margin-top:12px;">
                                            <label class="form-label">RCM Tax Amount</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text"><?php echo $cur; ?></span>
                                                <input type="text" class="form-control text-end" id="rcmAmountInput"
                                                       placeholder="0.00"
                                                       value="<?php echo $rcmAmount > 0 ? number_format($rcmAmount, 2, '.', '') : ''; ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- TDS Card -->
                                <div class="card mb-3">
                                    <div class="card-body py-3 px-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <span class="fw-semibold" style="font-size:.85rem;">TDS Applicable</span>
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" id="tdsToggle"
                                                       <?php echo $tdsApplicable ? 'checked' : ''; ?>
                                                       <?php echo $isEdit ? 'disabled' : ''; ?>>
                                            </div>
                                        </div>
                                        <div id="tdsSection" style="display:<?php echo $tdsApplicable ? 'block' : 'none'; ?>; margin-top:14px;">
                                            <div class="row g-2">
                                                <div class="col-12">
                                                    <label class="form-label">TDS Section <span class="text-danger">*</span></label>
                                                    <select class="form-select form-select-sm" id="tdsSectionSelect"
                                                            <?php echo $isEdit ? 'disabled' : ''; ?>>
                                                        <option value="">— Select TDS Section —</option>
                                                    </select>
                                                </div>
                                                <div class="col-12 mt-2">
                                                    <label class="form-label">TDS Amount</label>
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text"><?php echo $cur; ?></span>
                                                        <input type="text" class="form-control text-end" id="tdsAmountDisp" readonly placeholder="0.00">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <input type="hidden" name="TDSApplicable"  id="tdsApplicableVal"   value="<?php echo $tdsApplicable; ?>">
                                        <input type="hidden" name="TdsSectionUID"  id="tdsSectionUIDInput" value="<?php echo $tdsSectionUID; ?>">
                                        <input type="hidden" name="TDSPercentage"  id="tdsPctHidden"       value="<?php echo $tdsPct; ?>">
                                    </div>
                                </div>

                                <!-- Net Amount Summary -->
                                <div class="card mb-3">
                                    <div class="card-body py-0">
                                        <table class="table table-sm mb-0" style="font-size:.82rem;">
                                            <tbody>
                                            <tr class="tax-col" id="summaryTaxableRow" style="display:<?php echo $taxApplicable ? 'table-row' : 'none'; ?>;">
                                                <td class="border-0 ps-0 text-muted py-2">Taxable Amount</td>
                                                <td class="border-0 pe-0 text-end py-2" id="summaryTaxable"><?php echo $isEdit && $taxApplicable ? $cur . ' ' . smartDecimal($_expAmt) : '—'; ?></td>
                                            </tr>
                                            <tr class="tax-col" id="summaryCgstRow" style="display:<?php echo $taxApplicable ? 'table-row' : 'none'; ?>;">
                                                <td class="border-0 ps-0 text-muted py-2"><span class="cgst-col-label"><?php echo $isInterState ? 'IGST' : 'CGST'; ?></span></td>
                                                <td class="border-0 pe-0 text-end py-2" id="summaryCgstAmt"><?php echo $isEdit && $taxApplicable ? $cur . ' ' . smartDecimal($_cgstDisp) : '—'; ?></td>
                                            </tr>
                                            <tr class="tax-col sgst-col" id="summarySgstRow" style="display:<?php echo ($taxApplicable && !$isInterState) ? 'table-row' : 'none'; ?>;">
                                                <td class="border-0 ps-0 text-muted py-2">SGST</td>
                                                <td class="border-0 pe-0 text-end py-2" id="summarySgstAmt"><?php echo $isEdit && $taxApplicable && !$isInterState ? $cur . ' ' . smartDecimal($_sgstTotal) : '—'; ?></td>
                                            </tr>
                                            <tr id="summaryRcmRow" style="display:<?php echo $rcmApplicable ? 'table-row' : 'none'; ?>;">
                                                <td class="border-0 ps-0 text-muted py-2">RCM (Self-Pay)</td>
                                                <td class="border-0 pe-0 text-end py-2" id="summaryRcmAmt"><?php echo $isEdit && $rcmApplicable ? $cur . ' ' . smartDecimal($rcmAmount) : '—'; ?></td>
                                            </tr>
                                            <tr id="summaryTotalRow" style="display:<?php echo $taxApplicable ? 'table-row' : 'none'; ?>;border-top:2px solid #e9eaec;">
                                                <td class="ps-0 fw-semibold py-2" style="border-top:2px solid #e9eaec;">Total Amount</td>
                                                <td class="pe-0 text-end fw-semibold py-2" id="summaryTotal" style="border-top:2px solid #e9eaec;"><?php echo $isEdit && $taxApplicable ? $cur . ' ' . smartDecimal($_grandTotal) : '—'; ?></td>
                                            </tr>
                                            <tr id="summaryExpAmtRow" style="display:<?php echo $taxApplicable ? 'none' : 'table-row'; ?>;">
                                                <td class="border-0 ps-0 text-muted py-2">Expense Amount</td>
                                                <td class="border-0 pe-0 text-end py-2 fw-semibold" id="summaryExpAmt"><?php echo $isEdit && !$taxApplicable ? $cur . ' ' . smartDecimal($_expAmt) : '—'; ?></td>
                                            </tr>
                                            <tr id="summaryTdsRow" style="display:<?php echo $tdsApplicable ? 'table-row' : 'none'; ?>;">
                                                <td class="border-0 ps-0 text-muted py-2">TDS Deducted</td>
                                                <td class="border-0 pe-0 text-end py-2 text-danger" id="summaryTdsAmt"><?php echo $isEdit && $tdsApplicable ? '− ' . $cur . ' ' . smartDecimal($_tdsAmt) : '—'; ?></td>
                                            </tr>
                                            <?php $_roSign = ($roundOff >= 0) ? '+' : '−'; ?>
                                            <tr id="summaryRoundRow" style="display:<?php echo $isEdit && $roundOff != 0 ? 'table-row' : 'none'; ?>;">
                                                <td class="border-0 ps-0 text-muted py-2">Round Off</td>
                                                <td class="border-0 pe-0 text-end py-2" id="summaryRoundOff"><?php echo $isEdit && $roundOff != 0 ? $_roSign . $cur . ' ' . smartDecimal(abs($roundOff)) : '—'; ?></td>
                                            </tr>
                                            <tr style="border-top:2px solid #e9eaec;">
                                                <td class="ps-0 fw-bold py-2 text-primary" style="border-top:2px solid #e9eaec;font-size:.88rem;">Net Payable</td>
                                                <td class="pe-0 text-end fw-bold py-2 text-primary" id="summaryNet" style="border-top:2px solid #e9eaec;font-size:.88rem;"><?php echo $isEdit ? $cur . ' ' . smartDecimal($_netAmt) : '—'; ?></td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Hidden fields -->
                                <input type="hidden" name="TaxApplicable"  id="taxApplicableVal"  value="<?php echo $taxApplicable; ?>">
                                <input type="hidden" name="TaxPercentage"  id="taxPctHidden"      value="<?php echo $taxPct; ?>">
                                <input type="hidden" name="RCMApplicable"  id="rcmApplicableVal"  value="<?php echo $rcmApplicable; ?>">
                                <input type="hidden" name="RCMAmount"      id="rcmAmountHidden"   value="<?php echo $rcmAmount; ?>">
                                <input type="hidden" name="RoundOff"       id="roundOffHidden"    value="<?php echo $roundOff; ?>">
                                <input type="hidden" name="Items"             id="itemsJsonInput"       value="">
                <input type="hidden" name="DeletedItemUIDs" id="deletedItemUIDsInput" value="">

                                <?php if (!$isEdit): ?>
                                <!-- Payment Card — create mode only -->
                                <div class="card mb-3">
                                    <div class="card-header modal-header-center-sticky p-2 mb-3 d-flex align-items-center justify-content-between">
                                        <h5 class="modal-title mb-0">Payment</h5>
                                        <button type="button" id="markPaidBtn"
                                                class="btn btn-sm btn-outline-secondary"
                                                style="min-width:130px;">
                                            <i class="bx bx-credit-card me-1"></i>Mark as Paid
                                        </button>
                                        <input type="hidden" name="IsPaid" id="isPaidHidden" value="0">
                                    </div>

                                    <div id="paymentSection" style="display:none;">
                                        <div class="card-body pt-1 pb-3">

                                            <div class="mb-3">
                                                <label class="form-label">Payment Date</label>
                                                <div class="input-group input-group-merge">
                                                    <span class="input-group-text"><i class="bx bx-calendar"></i></span>
                                                    <input type="text" class="form-control" id="pmtDate_disp"
                                                           value="<?php echo $pmtDateDisp; ?>" readonly>
                                                    <input type="hidden" id="pmtDate" name="PaymentDate" value="<?php echo $pmtDate; ?>">
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Payment Type</label>
                                                <div class="d-flex flex-wrap gap-2" id="pmtTypePills">
                                                    <?php foreach ($paymentTypes as $pt): ?>
                                                        <button type="button"
                                                                class="btn btn-sm pmt-type-pill btn-outline-secondary"
                                                                data-uid="<?php echo (int)$pt->PaymentTypeUID; ?>"
                                                                data-name="<?php echo htmlspecialchars($pt->PaymentTypeName); ?>">
                                                            <?php echo htmlspecialchars($pt->PaymentTypeName); ?>
                                                        </button>
                                                    <?php endforeach; ?>
                                                </div>
                                                <input type="hidden" name="PaymentTypeUID" id="pmtTypeUID" value="">
                                            </div>

                                            <div class="mb-3" id="bankSection">
                                                <label class="form-label">Bank / Account</label>
                                                <select class="form-select" name="BankAccountUID" id="bankAccountUID">
                                                    <option value="">None / Not Applicable</option>
                                                    <?php foreach ($bankAccounts as $ba): ?>
                                                        <option value="<?php echo (int)$ba->BankAccountUID; ?>">
                                                            <?php echo htmlspecialchars($ba->AccountName); ?>
                                                            <?php echo !empty($ba->BankName) ? ' — ' . htmlspecialchars($ba->BankName) : ''; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="mb-0">
                                                <label class="form-label">Payment Notes</label>
                                                <textarea class="form-control" name="PaymentNotes" rows="2"
                                                          placeholder="Cheque no., UTR, reference..."></textarea>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                            </div><!-- /col-lg-4 -->
                        </div><!-- /row -->
                    </div><!-- /container-xxl -->

                </form>

            </div>
            <?php $this->load->view('common/footer_desc'); ?>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-3">
                <h6 class="modal-title">New Category</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="text" class="form-control" id="newCategoryName" placeholder="Category name" maxlength="100">
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="saveCategoryBtn">Add</button>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('common/imagepreview_modal'); ?>
<?php $this->load->view('transactions/modals/vendor_search'); ?>
<?php $this->load->view('common/transactions/footer'); ?>

<style>
/* Sticky action bar — full width, sticks to top of content area (below horizontal menu) */
.exp-form-action-bar {
    position: sticky;
    top: 0;
    z-index: 1020;
    border-bottom: 1px solid #b8d4f5;
    box-shadow: 0 2px 6px rgba(67,89,113,.08);
}
/* Item table inputs */
#expItemsTable .item-desc,
#expItemsTable .item-amount,
#expItemsTable .item-tax-pct,
#expItemsTable .item-cat {
    font-size: .82rem;
    min-width: 0;
}
#expItemsTable .item-del-btn {
    background: none;
    border: none;
    padding: 2px 3px;
    line-height: 1;
    color: #dc3545;
    cursor: pointer;
    font-size: 1rem;
}
#expItemsTable .item-del-btn:hover { color: #b02a37; }
#expItemsTable input:focus {
    outline: none;
    box-shadow: none;
    background: #f0f4ff !important;
    border-radius: 4px;
}
#expItemsTable tfoot td {
    font-size: .82rem;
    border-top: 1px solid #e9eaec;
}
#expEmptyRow td {
    font-size: .82rem;
    border: none;
    color: #9aa0ac;
}
#expEmptyRow .exp-empty-icon {
    font-size: 1.8rem;
    display: block;
    margin-bottom: .25rem;
}
</style>

<!-- Upstash vars for vendor cache + helper required by vendor_search.js -->
<script>
var _upstashUrl       = '<?php echo addslashes($UpstashReadUrl   ?? ''); ?>';
var _upstashReadToken = '<?php echo addslashes($UpstashReadToken ?? ''); ?>';
var _vendorCacheKey   = '<?php echo addslashes($VendorCacheKey   ?? ''); ?>';

/**
 * @param {number} balance
 * @param {string} balanceType
 * @returns {string}
 */
function _custBalanceHtml(balance, balanceType) {
    if (!balance || balance === 0) {
        return '<span style="color:#6c757d;font-weight:600;">&#8377; 0</span>';
    }
    var isCredit = (balanceType === 'Credit');
    var color    = isCredit ? '#198754' : '#dc3545';
    var label    = isCredit ? 'Adv' : 'Bal';
    return '<span style="color:' + color + ';font-weight:600;">' + label + ' &#8377; '
        + parseFloat(balance).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
        + '</span>';
}
</script>

<script src="/js/transactions/attachments.js"></script>
<script src="/js/transactions/forms/vendor_search.js"></script>

<script>
$(function () {
    'use strict';

    var curSymbol       = '<?php echo addslashes($JwtData->GenSettings->CurrenySymbol ?? '₹'); ?>';
    var decPoints       = 9;
    var rowIndex        = <?php echo !empty($existItems) ? count($existItems) : 0; ?>;
    var isTaxOn         = <?php echo $taxApplicable ? 'true' : 'false'; ?>;
    var amountType      = '<?php echo $amountType; ?>';
    var noBankTypes     = ['Cash'];
    var activePmtName   = '';
    var orgStateCode      = '<?php echo addslashes($orgStateCode); ?>';
    var vendorStateCode   = '<?php echo addslashes($vendorStateCode); ?>';
    var isInterState      = <?php echo $isInterState ? 'true' : 'false'; ?>;
    var _deletedItemUIDs  = [];

    // ── Vendor — Upstash-cached search (create mode only; edit shows readonly display) ──
    <?php if (!$isEdit): ?>
    if (typeof searchVendors === 'function') {
        searchVendors('vendorSearch');
    }
    <?php endif; ?>

    // ── Supply type (Intra / Inter State) ────────────────────────────────────
    /**
     * Updates CGST/IGST label and shows/hides the SGST column based on vendor state.
     * @param {string} vendState
     * @returns {void}
     */
    function _applySupplyType(vendState) {
        vendorStateCode = vendState;
        isInterState = (vendState !== '' && orgStateCode !== '' && vendState !== orgStateCode);
        var label = isInterState ? 'IGST' : 'CGST';
        $('.cgst-col-label').text(label);
        $('#footerCgstLabel').text(label);
        if (isTaxOn) {
            if (isInterState) {
                $('.sgst-col').hide();
            } else {
                $('.sgst-col').show();
            }
        }
        computeAmounts();
    }

    // ── Vendor balance display ────────────────────────────────────────────────
    var _vendorBalData = null;

    /**
     * Show the vendor's closing balance in #vendorBalCard, or hide it.
     * @param {Object|null} vd  — parsed Upstash vendor entry, or null to clear
     * @returns {void}
     */
    function _showVendorBalance(vd) {
        _vendorBalData = vd || null;
        if (!vd) { $('#vendorBalCard').addClass('d-none'); return; }
        var bal      = parseFloat(vd.ClosingBalance || vd.OpeningBalance || 0);
        var balType  = vd.ClosingBalType || vd.OpeningBalType || 'Credit';
        var payable  = (balType === 'Credit');
        var color    = payable ? '#dc3545' : '#198754';
        var label    = payable ? 'Payable'  : 'Receivable';
        var balStr   = bal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        $('#vendorBalAmt').text(curSymbol + ' ' + balStr).css('color', color);
        $('#vendorBalBadge').text(label).css({ 'background-color': payable ? '#fdecea' : '#e8f5e9', 'color': color });
        $('#vendorBalCard').removeClass('d-none');
    }

    // ── Vendor change — read billing state + balance from cache (create mode) ──
    <?php if (!$isEdit): ?>
    $('#vendorSearch').on('change', function () {
        var uid = parseInt($(this).val()) || 0;
        if (!uid) { _applySupplyType(''); _showVendorBalance(null); return; }
        UpstashService.hget(_vendorCacheKey, String(uid)).then(function (vd) {
            var state = '';
            if (vd && Array.isArray(vd.Address)) {
                var billing = vd.Address.find(function (a) { return a.AddressType === 'Billing'; });
                if (billing) state = billing.State || '';
            }
            _applySupplyType(state);
            _showVendorBalance(vd || null);
        }).catch(function () {
            _showVendorBalance(null);
        });
    });
    <?php else: ?>
    // Edit mode: apply saved vendor state and load balance on page load
    _applySupplyType(vendorStateCode);
    if (isTaxOn && typeof _vendorCacheKey !== 'undefined' && _vendorCacheKey) {
        UpstashService.hget(_vendorCacheKey, '<?php echo $vendorUID; ?>').then(function (vd) {
            _showVendorBalance(vd || null);
        });
    }
    <?php endif; ?>

    // ── Category Select2 ──────────────────────────────────────────────────────
    if ($.fn.select2) {
        $('.select2-expense-category').select2({
            placeholder: '— Select Category —',
            allowClear : true,
            width      : '100%',
        });
    }

    // ── Flatpickr dates ───────────────────────────────────────────────────────
    /**
     * @param {Date} d
     * @returns {string}
     */
    function _isoDate(d) {
        return d.getFullYear() + '-'
            + String(d.getMonth() + 1).padStart(2, '0') + '-'
            + String(d.getDate()).padStart(2, '0');
    }

    var _fmt = typeof _transFormDateFormat !== 'undefined' ? _transFormDateFormat : 'd-m-Y';

    flatpickr('#expDate_disp', {
        dateFormat  : _fmt, allowInput: false,
        defaultDate : <?php echo ($isEdit && $expDate) ? "'" . $expDate . "'" : "'today'"; ?>,
        onChange    : function (d) { if (d.length) document.getElementById('expDate').value = _isoDate(d[0]); },
        onReady     : function (d) { if (d.length) document.getElementById('expDate').value = _isoDate(d[0]); }
    });
    flatpickr('#supplierDate_disp', {
        dateFormat  : _fmt, allowInput: false,
        <?php if ($supplierDate): ?>defaultDate: '<?php echo $supplierDate; ?>',<?php endif; ?>
        onChange    : function (d) { document.getElementById('supplierDate').value = d.length ? _isoDate(d[0]) : ''; }
    });
    flatpickr('#pmtDate_disp', {
        dateFormat  : _fmt, allowInput: false,
        defaultDate : 'today',
        onChange    : function (d) { if (d.length) document.getElementById('pmtDate').value = _isoDate(d[0]); },
        onReady     : function (d) { if (d.length) document.getElementById('pmtDate').value = _isoDate(d[0]); }
    });

    // ── Items table ───────────────────────────────────────────────────────────

    var _expCategories = <?php echo json_encode(array_map(function(object $c): array {
        return ['uid' => (int)$c->CategoryUID, 'name' => $c->CategoryName];
    }, $categories)); ?>;

    var _taxPctOptionsHtml = '<option value="">GST%</option>';

    /**
     * @param {Array} taxList
     * @returns {void}
     */
    function _fillTaxPctSelects(taxList) {
        var html = '<option value="">GST%</option>';
        taxList.forEach(function (t) {
            var pct = parseFloat(t.Percentage) || 0;
            if (pct > 0) html += '<option value="' + pct + '">' + pct + '%</option>';
        });
        _taxPctOptionsHtml = html;
        $('#expItemsBody .item-tax-pct').each(function () {
            var cur = $(this).val();
            $(this).html(html);
            if (cur) $(this).val(cur);
        });
    }

    /**
     * @returns {void}
     */
    function _loadTaxPctOptions() {
        UpstashService.get('r2k-tax-details').then(function (data) {
            if (Array.isArray(data) && data.length) {
                _fillTaxPctSelects(data);
                return;
            }
            $.ajax({
                url: '/products/getDropdownCache', method: 'POST',
                data: { 'fields[0]': 'taxDetails', [CsrfName]: CsrfToken },
                success: function (res) {
                    if (res && !res.Error && res.Data && Array.isArray(res.Data.taxDetails)) {
                        _fillTaxPctSelects(res.Data.taxDetails);
                    }
                }
            });
        });
    }

    /**
     * @param {number} n
     * @returns {string}
     */
    function fmt(n) { return parseFloat(n || 0).toFixed(decPoints); }

    /**
     * @param {number} idx
     * @returns {string}
     */
    function _buildItemRow(idx) {
        var tShow  = isTaxOn ? 'table-cell' : 'none';
        var ntShow = isTaxOn ? 'none'       : 'table-cell';
        var catOpts = '<option value="0">-- Category --</option>';
        _expCategories.forEach(function (c) {
            catOpts += '<option value="' + c.uid + '">' + c.name.replace(/&/g,'&amp;').replace(/</g,'&lt;') + '</option>';
        });
        return '<tr class="exp-item-row border-top" data-row="' + idx + '">'
            + '<td class="ps-3 text-muted" style="font-size:.72rem;">' + (idx + 1) + '</td>'
            + '<td><input type="text" class="form-control form-control-sm item-desc" placeholder="Description (optional)"></td>'
            + '<td><select class="form-select form-select-sm item-cat">' + catOpts + '</select></td>'
            + '<td class="tax-col" style="display:' + tShow + ';">'
            +   '<input type="text" class="form-control form-control-sm item-amount text-end" placeholder="0.00">'
            + '</td>'
            + '<td class="no-tax-col" style="display:' + ntShow + ';">'
            +   '<input type="text" class="form-control form-control-sm item-amount text-end pe-3" placeholder="0.00">'
            + '</td>'
            + '<td class="tax-col" style="display:' + tShow + ';">'
            +   '<select class="form-select form-select-sm item-tax-pct">' + _taxPctOptionsHtml + '</select>'
            + '</td>'
            + '<td class="tax-col cgst-col text-end pe-2" style="display:' + tShow + ';"><span class="item-cgst-amt text-muted" style="font-size:.82rem;">0.00</span></td>'
            + '<td class="tax-col sgst-col text-end pe-2" style="display:' + (isTaxOn && !isInterState ? 'table-cell' : 'none') + ';"><span class="item-sgst-amt text-muted" style="font-size:.82rem;">0.00</span></td>'
            + '<td class="tax-col" style="display:' + tShow + ';">'
            +   '<div class="d-flex align-items-center gap-1">'
            +     '<button type="button" class="item-del-btn flex-shrink-0" title="Remove"><i class="bx bx-trash me-2"></i></button>'
            +     '<span class="item-total fw-semibold flex-fill text-end" style="font-size:.82rem;">0.00</span>'
            +   '</div>'
            + '</td>'
            + '</tr>';
    }

    function _toggleEmptyRow() {
        $('#expEmptyRow').toggleClass('d-none', $('#expItemsBody .exp-item-row').length > 0);
    }

    function _ensureOneRow() {
        if ($('#expItemsBody .exp-item-row').length === 0) {
            $('#expItemsBody').append(_buildItemRow(rowIndex++));
            _toggleEmptyRow();
        }
    }

    <?php if ($isEdit): ?>_ensureOneRow();<?php endif; ?>
    _loadTaxPctOptions();
    _toggleEmptyRow();

    $('#addItemRowBtn').on('click', function () {
        $('#expItemsBody').append(_buildItemRow(rowIndex++));
        _renumberRows();
        _toggleEmptyRow();
        computeAmounts();
    });

    $(document).on('click', '.item-del-btn', function () {
        var $row = $(this).closest('tr');
        var uid  = parseInt($row.data('item-uid')) || 0;
        if (uid > 0) _deletedItemUIDs.push(uid);
        $row.remove();
        _renumberRows();
        _toggleEmptyRow();
        computeAmounts();
    });

    function _renumberRows() {
        $('#expItemsBody .exp-item-row').each(function (i) {
            $(this).find('td:first').text(i + 1);
        });
    }

    $(document).on('input change', '.item-amount, .item-tax-pct', function () {
        _recalcRow($(this).closest('tr'));
        computeAmounts();
    });

    $('#simpleExpAmtInput').on('input', function () {
        var amt = parseFloat($(this).val()) || 0;
        if (amt <= 0 && $('#tdsToggle').is(':checked')) {
            $('#tdsToggle').prop('checked', false).trigger('change');
        } else {
            computeAmounts();
        }
        _checkMinAmount(_lastComputedNet);
    });

    /**
     * @param {jQuery} $row
     */
    function _recalcRow($row) {
        var inputAmt = parseFloat($row.find('.item-amount').val()) || 0;
        var taxPct   = isTaxOn ? (parseFloat($row.find('.item-tax-pct').val()) || 0) : 0;
        var taxAmt, total;
        if (amountType === 'TotalAmount' && isTaxOn) {
            total  = inputAmt;
            taxAmt = taxPct > 0 ? total - total / (1 + taxPct / 100) : 0;
        } else {
            taxAmt = inputAmt * taxPct / 100;
            total  = inputAmt + taxAmt;
        }
        var cgstAmt = 0, sgstAmt = 0, igstAmt = 0;
        if (isTaxOn && taxPct > 0) {
            if (isInterState) { igstAmt = taxAmt; }
            else              { cgstAmt = taxAmt / 2; sgstAmt = taxAmt / 2; }
        }
        $row.find('.item-cgst-amt').text(fmt(isInterState ? igstAmt : cgstAmt));
        $row.find('.item-sgst-amt').text(fmt(sgstAmt));
        $row.find('.item-total').text(fmt(total));
    }

    // ── Minimum-amount guard (edit: amount must be ≥ PaidAmount) ─────────────
    var _lastComputedNet = 0;
    var _AMT_ERR_ID      = 'r2k-exp-min-amt-toast';

    /**
     * Shows a persistent (non-auto-hide) toast when net < paid amount, clears it when valid.
     * @param {number} net
     * @returns {boolean} true when amount is valid
     */
    function _checkMinAmount(net) {
        var minAmt = parseFloat($('#expPaidAmount').val()) || 0;
        if (minAmt <= 0) return true;
        if (net < minAmt - 0.001) {
            if (!$('#' + _AMT_ERR_ID).length) {
                var _c = '#dc3545';
                var _h = '<div id="' + _AMT_ERR_ID + '" class="r2k-toast-notify" style="border-left-color:' + _c + ';">' +
                    '<i class="bx bx-x-circle r2k-toast-icon" style="color:' + _c + ';"></i>' +
                    '<span class="r2k-toast-msg">Amount cannot be less than ' + curSymbol + ' ' + fmt(minAmt) + ' (already paid).</span>' +
                    '<button class="r2k-toast-close" onclick="$(\'#' + _AMT_ERR_ID + '\').remove()">&times;</button>' +
                '</div>';
                if (!$('#r2k-toast-wrap').length) $('body').append('<div id="r2k-toast-wrap"></div>');
                $('#r2k-toast-wrap').append(_h);
                setTimeout(function () { $('#' + _AMT_ERR_ID).addClass('r2k-toast-show'); }, 10);
            }
            return false;
        }
        $('#' + _AMT_ERR_ID).remove();
        return true;
    }

    // ── Master compute ────────────────────────────────────────────────────────
    function computeAmounts() {
        var tdsPct = $('#tdsToggle').is(':checked') ? (parseFloat($('#tdsPctHidden').val()) || 0) : 0;

        if (!isTaxOn) {
            var expAmt   = parseFloat($('#simpleExpAmtInput').val()) || 0;
            var tdsAmt   = expAmt * tdsPct / 100;
            var net_raw  = expAmt - tdsAmt;
            var roundOff = parseFloat((Math.round(net_raw) - net_raw).toFixed(decPoints));
            var net      = net_raw + roundOff;

            $('#summaryExpAmt').text(curSymbol + ' ' + fmt(expAmt));
            $('#summaryTdsAmt').text('− ' + curSymbol + ' ' + fmt(tdsAmt));
            var _rs = roundOff >= 0 ? '+' : '−';
            $('#summaryRoundOff').text(roundOff !== 0 ? _rs + curSymbol + ' ' + fmt(Math.abs(roundOff)) : '—');
            $('#summaryRoundRow').toggle(roundOff !== 0);
            $('#summaryNet').text(curSymbol + ' ' + fmt(net));
            $('#tdsAmountDisp').val(fmt(tdsAmt));
            $('#tdsPctHidden').val(tdsPct.toFixed(2));
            $('#roundOffHidden').val(roundOff.toFixed(decPoints));
            _lastComputedNet = net;
            return;
        }

        var taxableSum = 0, cgstSum = 0, sgstSum = 0, igstSum = 0, totalSum = 0;

        $('#expItemsBody .exp-item-row').each(function () {
            var inputAmt = parseFloat($(this).find('.item-amount').val()) || 0;
            var taxPct   = parseFloat($(this).find('.item-tax-pct').val()) || 0;
            var taxable, taxAmt, total;
            if (amountType === 'TotalAmount') {
                total   = inputAmt;
                taxable = taxPct > 0 ? total / (1 + taxPct / 100) : total;
                taxAmt  = total - taxable;
            } else {
                taxable = inputAmt;
                taxAmt  = taxable * taxPct / 100;
                total   = taxable + taxAmt;
            }
            var rowCgst = 0, rowSgst = 0, rowIgst = 0;
            if (taxPct > 0) {
                if (isInterState) { rowIgst = taxAmt; }
                else              { rowCgst = taxAmt / 2; rowSgst = taxAmt / 2; }
            }
            $(this).find('.item-cgst-amt').text(fmt(isInterState ? rowIgst : rowCgst));
            $(this).find('.item-sgst-amt').text(fmt(rowSgst));
            taxableSum += taxable;
            cgstSum    += rowCgst;
            sgstSum    += rowSgst;
            igstSum    += rowIgst;
            totalSum   += total;
        });

        // Round-off applied at Net Payable level (after TDS), not at Grand Total level
        var tdsAmt   = totalSum * tdsPct / 100;
        var rcmAmt   = ($('#rcmToggle').is(':checked')) ? (parseFloat($('#rcmAmountInput').val()) || 0) : 0;
        var net_raw  = totalSum - tdsAmt;
        var roundOff = parseFloat((Math.round(net_raw) - net_raw).toFixed(decPoints));
        var net      = net_raw + roundOff;

        $('#footerTaxable').text(fmt(taxableSum));
        $('#footerCgstAmt').text(fmt(isInterState ? igstSum : cgstSum));
        $('#footerCgstLabel').text(isInterState ? 'IGST' : 'CGST');
        $('#footerSgstAmt').text(fmt(sgstSum));
        $('#footerGrandTotal').text(fmt(totalSum));

        $('#summaryTaxable').text(curSymbol + ' ' + fmt(taxableSum));
        $('#summaryCgstAmt').text(curSymbol + ' ' + fmt(isInterState ? igstSum : cgstSum));
        $('#summarySgstAmt').text(curSymbol + ' ' + fmt(sgstSum));
        $('#summaryTotal').text(curSymbol + ' ' + fmt(totalSum));
        $('#summaryRcmAmt').text(curSymbol + ' ' + fmt(rcmAmt));
        $('#summaryTdsAmt').text('− ' + curSymbol + ' ' + fmt(tdsAmt));
        var _rs = roundOff >= 0 ? '+' : '−';
        $('#summaryRoundOff').text(roundOff !== 0 ? _rs + curSymbol + ' ' + fmt(Math.abs(roundOff)) : '—');
        $('#summaryRoundRow').toggle(roundOff !== 0);
        $('#summaryNet').text(curSymbol + ' ' + fmt(net));

        $('#tdsAmountDisp').val(fmt(tdsAmt));
        $('#roundOffHidden').val(roundOff.toFixed(decPoints));
        $('#rcmAmountHidden').val(rcmAmt.toFixed(decPoints));
        $('#tdsPctHidden').val(tdsPct.toFixed(2));
        _lastComputedNet = net;
    }

    computeAmounts();

    // ── Tax toggle ────────────────────────────────────────────────────────────
    $('#taxToggle').on('change', function () {
        isTaxOn = $(this).is(':checked');
        $('#taxApplicableVal').val(isTaxOn ? 1 : 0);

        $('.with-tax-field').toggle(isTaxOn);
        $('#expItemsCard').toggle(isTaxOn);
        $('#simpleExpAmtField').toggle(!isTaxOn);

        $('#summaryTotalRow').toggle(isTaxOn);
        $('#summaryExpAmtRow').toggle(!isTaxOn);
        $('#summaryTaxableRow, #summaryCgstRow').toggle(isTaxOn);
        if (!isTaxOn) $('#summaryRoundRow').hide();

        if (isTaxOn) {
            $('#catCol, #notesCol').removeClass('col-12').addClass('col-md-6');
        } else {
            $('#catCol, #notesCol').removeClass('col-md-6').addClass('col-12');
        }

        $('#rcmCard').toggle(isTaxOn);
        if (!isTaxOn) $('#rcmToggle').prop('checked', false).trigger('change');
        $('.tax-col').css('display', isTaxOn ? '' : 'none');
        $('.no-tax-col').css('display', isTaxOn ? 'none' : '');
        $('#footerTaxableRow, #footerCgstRow').toggle(isTaxOn);
        // sgst-col visibility is managed by _applySupplyType; restore it when turning tax on
        if (isTaxOn) {
            _applySupplyType(vendorStateCode);
            if (_vendorBalData) _showVendorBalance(_vendorBalData);
        } else {
            $('.sgst-col').hide();
            $('#vendorBalCard').addClass('d-none');
        }
        computeAmounts();
    });

    // ── Amount Type ───────────────────────────────────────────────────────────
    $('#expAmountType').on('change', function () {
        amountType = $(this).val();
        $('#expItemsBody .exp-item-row').each(function () { _recalcRow($(this)); });
        computeAmounts();
    });

    // ── RCM toggle ────────────────────────────────────────────────────────────
    $('#rcmToggle').on('change', function () {
        var on = $(this).is(':checked');
        $('#rcmSection').slideToggle(150);
        $('#rcmApplicableVal').val(on ? 1 : 0);
        $('#summaryRcmRow').toggle(on);
        if (!on) { $('#rcmAmountInput').val(''); }
        computeAmounts();
    });

    $('#rcmAmountInput').on('input', computeAmounts);

    // ── TDS Sections — cache-first loader ────────────────────────────────────
    var _tdsSectionsLoaded  = false;
    var _tdsSectionRateMap  = {}; // uid → TaxRate
    var _openDropdownAfterLoad = false;

    /**
     * @returns {void}
     */
    function loadTdsSections() {
        if (_tdsSectionsLoaded) return;

        var cacheKey = UpstashService.globalKey('tds-section');

        UpstashService.get(cacheKey).then(function (cached) {
            if (Array.isArray(cached) && cached.length) {
                _populateTdsSectionDropdown(cached);
            } else {
                // Cache miss — AJAX fallback
                $.ajax({
                    url: '/expenses/getTdsSections', method: 'GET',
                    success: function (resp) {
                        if (!resp.Error && Array.isArray(resp.Data)) {
                            _populateTdsSectionDropdown(resp.Data);
                        }
                    }
                });
            }
        });
    }

    /**
     * @param {Array} sections
     * @returns {void}
     */
    function _populateTdsSectionDropdown(sections) {
        if (_tdsSectionsLoaded) return;
        _tdsSectionsLoaded = true;

        var $sel = $('#tdsSectionSelect');
        sections.forEach(function (s) {
            _tdsSectionRateMap[s.TdsSectionUID] = parseFloat(s.TaxRate) || 0;
            var label = s.SectionCode + ' (' + s.TaxRate + '%) — ' + s.Description;
            $sel.append($('<option>').val(s.TdsSectionUID).text(label));
        });

        if ($.fn.select2) {
            $sel.select2({ placeholder: '— Select TDS Section —', allowClear: true, width: '100%' });
        }

        // Re-attach change handler after Select2 init (event delegation doesn't survive reinit)
        $sel.off('change.tds').on('change.tds', _onTdsSectionChange);

        // Auto-open if user clicked the dropdown to trigger the load
        if (_openDropdownAfterLoad) {
            _openDropdownAfterLoad = false;
            if ($.fn.select2) { $sel.select2('open'); }
        }

        // Pre-select saved section (edit mode)
        var savedUID = <?php echo $tdsSectionUID; ?>;
        if (savedUID > 0) {
            $sel.val(savedUID).trigger('change.select2');
            var savedRate = _tdsSectionRateMap[savedUID] || <?php echo $tdsPct ?: 0; ?>;
            $('#tdsPctHidden').val(savedRate > 0 ? savedRate.toFixed(2) : '0.00');
        }
    }

    /**
     * @returns {void}
     */
    function _onTdsSectionChange() {
        var uid  = parseInt($('#tdsSectionSelect').val()) || 0;
        var rate = uid > 0 ? (_tdsSectionRateMap[uid] || 0) : 0;
        $('#tdsSectionUIDInput').val(uid || '');
        $('#tdsPctHidden').val(rate > 0 ? rate.toFixed(2) : '0.00');
        computeAmounts();
    }

    $('#tdsSectionSelect').on('change.tds', _onTdsSectionChange);

    // Lazy-load on first click of the dropdown; open it after data is ready
    $('#tdsSectionSelect').on('click', function () {
        if (!_tdsSectionsLoaded) {
            _openDropdownAfterLoad = true;
        }
        loadTdsSections();
    });

    // Edit mode: pre-load so saved section is already selected when page opens
    <?php if ($isEdit && $tdsApplicable): ?>
    loadTdsSections();
    <?php endif; ?>

    // ── TDS toggle ────────────────────────────────────────────────────────────
    $('#tdsToggle').on('change', function () {
        var on = $(this).is(':checked');
        $('#tdsSection').slideToggle(150);
        $('#tdsApplicableVal').val(on ? 1 : 0);
        $('#summaryTdsRow').toggle(on);
        if (!on) {
            $('#tdsSectionSelect').val('').trigger('change.select2');
            $('#tdsSectionUIDInput').val('');
            $('#tdsPctHidden').val('0.00');
        }
        computeAmounts();
    });

    // ── Mark as Paid ──────────────────────────────────────────────────────────
    $('#markPaidBtn').on('click', function () {
        var isPaid = $('#isPaidHidden').val() === '1';
        isPaid = !isPaid;
        $('#isPaidHidden').val(isPaid ? 1 : 0);
        if (isPaid) {
            $(this).removeClass('btn-outline-secondary').addClass('btn-success')
                   .html('<i class="bx bx-check me-1"></i>Paid');
            $('#paymentSection').slideDown(200);
        } else {
            $(this).removeClass('btn-success').addClass('btn-outline-secondary')
                   .html('<i class="bx bx-credit-card me-1"></i>Mark as Paid');
            $('#paymentSection').slideUp(200);
        }
    });

    <?php if (!$isEdit): ?>$('#markPaidBtn').trigger('click');<?php endif; ?>

    // ── Payment type pills ────────────────────────────────────────────────────
    $(document).on('click', '.pmt-type-pill', function () {
        $('.pmt-type-pill').removeClass('btn-primary').addClass('btn-outline-secondary');
        $(this).removeClass('btn-outline-secondary').addClass('btn-primary');
        $('#pmtTypeUID').val($(this).data('uid'));
        activePmtName = $(this).data('name');
        _updateBankVisibility();
    });

    function _updateBankVisibility() {
        if (noBankTypes.indexOf(activePmtName) !== -1) {
            $('#bankSection').slideUp(150);
            $('#bankAccountUID').val('');
        } else {
            $('#bankSection').slideDown(150);
        }
    }

    activePmtName = $('.pmt-type-pill.btn-primary').data('name') || '';
    _updateBankVisibility();

    // ── Add Category modal ────────────────────────────────────────────────────
    $('#addCategoryBtn').on('click', function () {
        $('#newCategoryName').val('');
        new bootstrap.Modal(document.getElementById('addCategoryModal')).show();
        setTimeout(function () { $('#newCategoryName').focus(); }, 350);
    });

    $('#saveCategoryBtn').on('click', function () {
        var name = $.trim($('#newCategoryName').val());
        if (!name) { $('#newCategoryName').addClass('is-invalid'); return; }
        $('#newCategoryName').removeClass('is-invalid');
        var $btn = $(this).prop('disabled', true).text('Adding…');
        $.ajax({
            url: '/expenses/addCategory', method: 'POST',
            data: { CategoryName: name, [CsrfName]: CsrfToken },
            success: function (resp) {
                $btn.prop('disabled', false).text('Add');
                if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
                var opt = new Option(resp.CategoryName, resp.CategoryUID, true, true);
                $('.select2-expense-category').append(opt).trigger('change');
                bootstrap.Modal.getInstance(document.getElementById('addCategoryModal')).hide();
                showToastNotification('Category "' + resp.CategoryName + '" added.', 'success');
            },
            error: function () {
                $btn.prop('disabled', false).text('Add');
                showToastNotification('Failed to add category.', 'error');
            }
        });
    });

    // ── Collect items ─────────────────────────────────────────────────────────

    /**
     * @returns {Array<Object>}
     */
    function _collectItems() {
        if (!isTaxOn) {
            var expAmt = parseFloat($('#simpleExpAmtInput').val()) || 0;
            return [{ ItemDescription: '', Amount: expAmt, TaxPercentage: 0, TaxAmount: 0, TotalAmount: expAmt, SortOrder: 0 }];
        }
        var items = [];
        $('#expItemsBody .exp-item-row').each(function (i) {
            var inputAmt = parseFloat($(this).find('.item-amount').val()) || 0;
            var taxPct   = parseFloat($(this).find('.item-tax-pct').val()) || 0;
            var taxable, taxAmt, total;
            if (amountType === 'TotalAmount') {
                total   = inputAmt;
                taxable = taxPct > 0 ? total / (1 + taxPct / 100) : total;
                taxAmt  = total - taxable;
            } else {
                taxable = inputAmt;
                taxAmt  = taxable * taxPct / 100;
                total   = taxable + taxAmt;
            }
            var itemCgst = 0, itemSgst = 0, itemIgst = 0;
            if (taxPct > 0) {
                if (isInterState) { itemIgst = taxAmt; }
                else              { itemCgst = taxAmt / 2; itemSgst = taxAmt / 2; }
            }
            items.push({
                ExpItemUID     : parseInt($(this).data('item-uid')) || 0,
                CategoryUID    : parseInt($(this).find('.item-cat').val()) || 0,
                ItemDescription: $(this).find('.item-desc').val() || '',
                Amount         : parseFloat(taxable.toFixed(4)),
                TaxPercentage  : taxPct,
                TaxAmount      : parseFloat(taxAmt.toFixed(4)),
                CGSTAmount     : parseFloat(itemCgst.toFixed(4)),
                SGSTAmount     : parseFloat(itemSgst.toFixed(4)),
                IGSTAmount     : parseFloat(itemIgst.toFixed(4)),
                TotalAmount    : parseFloat(total.toFixed(4)),
                SortOrder      : i
            });
        });
        return items;
    }

    // ── Form submit ───────────────────────────────────────────────────────────
    $('#expenseForm').on('submit', function (e) {
        e.preventDefault();

        if (!isTaxOn) {
            var simpleAmt = parseFloat($('#simpleExpAmtInput').val()) || 0;
            if (simpleAmt <= 0) {
                showToastNotification('Please enter the expense amount.', 'error');
                return;
            }
        } else {
            var $itemRows = $('#expItemsBody .exp-item-row');
            if ($itemRows.length === 0) {
                showToastNotification('Please add at least one expense item.', 'error');
                return;
            }
            var zeroRow = -1;
            $itemRows.each(function (i) {
                if ((parseFloat($(this).find('.item-amount').val()) || 0) <= 0) {
                    zeroRow = i + 1;
                    return false;
                }
            });
            if (zeroRow !== -1) {
                showToastNotification('Item #' + zeroRow + ': Amount must be greater than 0.', 'error');
                return;
            }
        }

        if (isTaxOn && !parseInt($('#vendorSearch').val())) {
            $('#vendorSearch').addClass('is-invalid');
            showToastNotification('Please select a vendor.', 'error');
            return;
        }
        $('#vendorSearch').removeClass('is-invalid');

        if ($('#tdsApplicableVal').val() === '1' && !parseInt($('#tdsSectionUIDInput').val())) {
            $('#tdsSectionSelect').addClass('is-invalid');
            showToastNotification('Please select a TDS section.', 'error');
            return;
        }
        $('#tdsSectionSelect').removeClass('is-invalid');

        if ($('#isPaidHidden').val() === '1' && !$('#pmtTypeUID').val()) {
            showToastNotification('Please select a payment type.', 'error');
            return;
        }

        $('#itemsJsonInput').val(JSON.stringify(_collectItems()));
        $('#deletedItemUIDsInput').val(JSON.stringify(_deletedItemUIDs));
        computeAmounts();

        if (!_checkMinAmount(_lastComputedNet)) return;

        var label = '<?php echo $isEdit ? 'Update' : 'Save'; ?>';
        var $btn  = $('#submitBtn').prop('disabled', true)
                                   .html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving…');
        var fd    = new FormData(this);
        fd.append(CsrfName, CsrfToken);
        if (typeof collectTransAttachData === 'function') collectTransAttachData(fd);

        ajaxLoading(1);

        $.ajax({
            url: $(this).attr('action'), method: 'POST',
            data: fd, processData: false, contentType: false,
            success: function (resp) {
                if (resp.Error) {
                    ajaxLoading(0);
                    $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i>' + label);
                    showToastNotification(resp.Message, 'error');
                    return;
                }
                window._r2kRedirecting = true;
                sessionStorage.setItem('pendingToast', JSON.stringify({ message: resp.Message, type: 'success' }));
                _isDirty = false;
                window.location.href = resp.RedirectURL || '/expenses';
            },
            error: function () {
                ajaxLoading(0);
                $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i>' + label);
                showToastNotification('An error occurred. Please try again.', 'error');
            }
        });
    });

    // ── Load existing attachments (edit mode) ─────────────────────────────────
    <?php if ($isEdit && $expUID > 0): ?>
    if (typeof renderTransAttachmentsFromData === 'function') {
        renderTransAttachmentsFromData(<?php echo json_encode($ExpenseAttachments ?? []); ?>);
    }
    <?php else: ?>
    if (typeof _attachResetState === 'function') _attachResetState('Transaction');
    <?php endif; ?>

    // ── Unsaved-changes guard ─────────────────────────────────────────────────
    var _isDirty = false;
    $(document).on('input change', function (e) {
        var t = e.target;
        if (t && t.type !== 'hidden' && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.tagName === 'SELECT')) {
            _isDirty = true;
        }
    });
    $(window).on('beforeunload', function (e) {
        if (_isDirty) { e.preventDefault(); e.returnValue = ''; }
    });
    $(document).on('click', 'a.btn-outline-danger', function (e) {
        if (!_isDirty) return;
        e.preventDefault();
        var href = $(this).attr('href');
        Swal.fire({
            title             : t('swal_unsaved_title',   'Unsaved Changes'),
            text              : t('swal_unsaved_msg',     'Your changes will be lost if you close now.'),
            icon              : 'warning',
            showCancelButton  : true,
            confirmButtonText : t('swal_unsaved_confirm', 'Close Anyway'),
            cancelButtonText  : t('swal_unsaved_cancel',  'Stay'),
            confirmButtonColor: '#d33',
            cancelButtonColor : '#3085d6',
        }).then(function (result) {
            if (result.isConfirmed) {
                _isDirty = false;
                window.location.href = href;
            }
        });
    });

});
</script>
