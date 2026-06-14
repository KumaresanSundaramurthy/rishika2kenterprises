<?php defined('BASEPATH') or exit('No direct script access allowed');
$this->load->view('common/transactions/header'); ?>

<?php
$isEdit     = isset($ExpenseData) && $ExpenseData !== null;
$expense    = $ExpenseData ?? null;
$expUID     = $isEdit ? (int)$expense->ExpenseUID : 0;
$formAction = $isEdit ? '/expenses/updateExpense' : '/expenses/addExpense';
$pageTitle  = $isEdit ? ('Edit Expense — ' . htmlspecialchars($expense->ExpenseNumber ?? '')) : 'Add Expense';

// Pre-fill values
$expDate      = $isEdit ? htmlspecialchars($expense->ExpenseDate  ?? date('Y-m-d')) : date('Y-m-d');
$amount       = $isEdit ? (float)$expense->Amount       : '';
$taxApplicable= $isEdit ? (int)$expense->TaxApplicable  : 0;
$taxPct       = $isEdit ? (float)$expense->TaxPercentage: 0;
$tdsApplicable= $isEdit ? (int)$expense->TDSApplicable  : 0;
$tdsPct       = $isEdit ? (float)$expense->TDSPercentage: 0;
$catUID       = $isEdit ? (int)$expense->CategoryUID    : 0;
$notes        = $isEdit ? htmlspecialchars($expense->Notes ?? '')       : '';
$isPaid       = $isEdit ? (int)$expense->IsPaid         : 0;
$pmtTypeUID   = $isEdit ? (int)$expense->PaymentTypeUID : 0;
$bankUID      = $isEdit ? (int)$expense->BankAccountUID : 0;
$pmtDate      = $isEdit ? htmlspecialchars($expense->PaymentDate ?? date('Y-m-d')) : date('Y-m-d');
$pmtNotes     = $isEdit ? htmlspecialchars($expense->PaymentNotes ?? '') : '';

$categories   = $Categories   ?? [];
$paymentTypes = $PaymentTypes ?? [];
$bankAccounts = $BankAccounts ?? [];

// Cash-like types: no bank needed
$noBankTypes  = ['Cash'];
?>

<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>

        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <?php $this->load->view('common/apex/page_header', [
                    'pageTitle'       => $pageTitle,
                    'pageDescription' => $PageDescription ?? '',
                ]); ?>
                <div class="container-xxl flex-grow-1 container-p-y">

                    <div class="mb-3">
                        <a href="/expenses" class="btn btn-sm btn-outline-secondary">
                            <i class="bx bx-arrow-back me-1"></i>Back
                        </a>
                    </div>

                    <form id="expenseForm" action="<?php echo $formAction; ?>" method="post" autocomplete="off">
                        <?php if ($isEdit): ?>
                            <input type="hidden" name="ExpenseUID" value="<?php echo $expUID; ?>">
                        <?php endif; ?>

                        <div class="row g-3">

                            <!-- ── Left Column ──────────────────────────── -->
                            <div class="col-lg-8">

                                <!-- Basic Details Card -->
                                <div class="card mb-3">
                                    <div class="card-header d-flex align-items-center justify-content-between py-3">
                                        <h6 class="mb-0">Basic Details</h6>
                                        <!-- Tax toggle -->
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="text-muted" style="font-size:.82rem;">Create Expense With Tax</span>
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" id="taxToggle"
                                                       <?php echo $taxApplicable ? 'checked' : ''; ?>>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">

                                        <div class="row g-3">

                                            <!-- Expense Amount -->
                                            <div class="col-12">
                                                <label class="form-label fw-semibold">
                                                    Expense Amount <span class="text-danger">*</span>
                                                </label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><?php echo htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹'); ?></span>
                                                    <input type="text" class="form-control form-control-lg" id="expAmount"
                                                           name="Amount" placeholder="Enter Expense Amount"
                                                           value="<?php echo $amount ?: ''; ?>"
                                                           maxlength="12" pattern="^\d{1,12}(\.\d{0,2})?$"
                                                           onkeydown="return handleDotOnly(event)"
                                                           oninput="this.value=this.value.slice(0,this.maxLength); validatePriceInput(this, 12, 2)"
                                                           onpaste="handlePricePaste(event, 12, 2)"
                                                           ondrop="handlePriceDrop(event, 12, 2)"
                                                           autocomplete="off" required>
                                                </div>
                                            </div>

                                            <!-- Expense Date + Category -->
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">Expense Date</label>
                                                <div class="input-group input-group-merge">
                                                    <span class="input-group-text"><i class="icon-base bx bx-calendar"></i></span>
                                                    <input type="text" class="form-control" id="expDate"
                                                           name="ExpenseDate" value="<?php echo $expDate; ?>" required readonly>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">
                                                    Category
                                                    <button type="button" class="btn btn-link p-0 ms-1 text-primary" id="addCategoryBtn" style="font-size:.75rem;vertical-align:baseline;" title="Add new category">
                                                        <i class="bx bx-plus-circle"></i> New
                                                    </button>
                                                </label>
                                                <select class="form-select select2-expense-category" id="expCategory" name="CategoryUID">
                                                    <option value="">Select Category</option>
                                                    <?php foreach ($categories as $cat): ?>
                                                        <option value="<?php echo (int)$cat->CategoryUID; ?>"
                                                            <?php echo $catUID === (int)$cat->CategoryUID ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($cat->CategoryName); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <!-- Tax section (shown when toggle is on) -->
                                            <div class="col-12" id="taxSection" style="display:<?php echo $taxApplicable ? 'block' : 'none'; ?>;">
                                                <div class="p-3 rounded border bg-light-subtle">
                                                    <div class="row g-2 align-items-end">
                                                        <div class="col-md-4">
                                                            <label class="form-label mb-1" style="font-size:.82rem;">Tax %</label>
                                                            <div class="input-group input-group-sm">
                                                                <input type="number" class="form-control" id="taxPct"
                                                                       name="TaxPercentage" min="0" max="100" step="0.01"
                                                                       value="<?php echo $taxPct ?: ''; ?>"
                                                                       placeholder="e.g. 18">
                                                                <span class="input-group-text">%</span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label mb-1" style="font-size:.82rem;">Tax Amount</label>
                                                            <div class="input-group input-group-sm">
                                                                <span class="input-group-text"><?php echo htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹'); ?></span>
                                                                <input type="text" class="form-control" id="taxAmount" readonly placeholder="0.00">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <input type="hidden" name="TaxApplicable" id="taxApplicableHidden" value="<?php echo $taxApplicable; ?>">
                                            </div>
                                            <input type="hidden" name="TaxApplicable" id="taxApplicableOff" value="0"
                                                   <?php echo $taxApplicable ? 'disabled' : ''; ?>>

                                            <!-- Notes -->
                                            <div class="col-12">
                                                <label class="form-label fw-semibold">Notes</label>
                                                <textarea class="form-control" id="expNotes" name="Notes"
                                                          rows="3" placeholder="Notes or description..."><?php echo $notes; ?></textarea>
                                            </div>

                                        </div>
                                    </div>
                                </div>

                                <!-- Attachments Card -->
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center gap-2 mb-3">
                                            <i class="icon-base bx bx-paperclip" style="font-size:1.25rem;"></i>
                                            <span class="fw-semibold"> Attach Files</span> <span class="text-muted">(Max 5, 3 MB each)</span>
                                            <span id="existingAttachCount" class="badge bg-label-primary ms-2 d-none" style="font-size:.7rem;"></span>
                                        </div>

                                        <!-- Existing saved files (edit mode) -->
                                        <div id="existingAttachList" class="mb-3 d-none">
                                            <div class="d-flex align-items-center gap-1 mb-2">
                                                <i class="bx bx-link-alt text-primary" style="font-size:.85rem;"></i>
                                                <span style="font-size:.75rem;font-weight:700;color:#566a7f;text-transform:uppercase;letter-spacing:.5px;">Saved Files</span>
                                            </div>
                                            <div id="existingAttachItems" class="d-flex flex-wrap gap-2"></div>
                                        </div>

                                        <!-- Dropzone -->
                                        <div class="dropzone needsclick dz-clickable w-100" id="multipleDropzone">
                                            <div class="dz-message needsclick text-center">
                                                <p class="h5 needsclick mb-2">Drag and drop files here</p>
                                                <p class="h4 text-body-secondary fw-normal mb-0">or click to browse (max 3 MB per file)</p>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                            </div>

                            <!-- ── Right Column ──────────────────────────── -->
                            <div class="col-lg-4">

                                <!-- TDS Card -->
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between mb-0">
                                            <span class="fw-semibold">TDS Applicable</span>
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" id="tdsToggle"
                                                       <?php echo $tdsApplicable ? 'checked' : ''; ?>>
                                            </div>
                                        </div>
                                        <div id="tdsSection" style="display:<?php echo $tdsApplicable ? 'block' : 'none'; ?>; margin-top:14px;">
                                            <div class="row g-2 align-items-end">
                                                <div class="col-6">
                                                    <label class="form-label mb-1" style="font-size:.82rem;">TDS %</label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" class="form-control" id="tdsPct"
                                                               name="TDSPercentage" min="0" max="100" step="0.01"
                                                               value="<?php echo $tdsPct ?: ''; ?>"
                                                               placeholder="e.g. 10">
                                                        <span class="input-group-text">%</span>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label mb-1" style="font-size:.82rem;">TDS Amount</label>
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text"><?php echo htmlspecialchars($JwtData->GenSettings->CurrenySymbol ?? '₹'); ?></span>
                                                        <input type="text" class="form-control" id="tdsAmount" readonly placeholder="0.00">
                                                    </div>
                                                </div>
                                            </div>
                                            <input type="hidden" name="TDSApplicable" id="tdsApplicableHidden" value="<?php echo $tdsApplicable; ?>">
                                        </div>
                                        <input type="hidden" name="TDSApplicable" id="tdsApplicableOff" value="0"
                                               <?php echo $tdsApplicable ? 'disabled' : ''; ?>>
                                    </div>
                                </div>

                                <!-- Net Amount Summary Card -->
                                <div class="card mb-3" id="amountSummaryCard" style="display:<?php echo ($taxApplicable || $tdsApplicable) ? 'block' : 'none'; ?>;">
                                    <div class="card-body py-2">
                                        <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:.85rem;">
                                            <span class="text-muted">Base Amount</span>
                                            <span id="summaryBase">—</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:.85rem;" id="summaryTaxRow">
                                            <span class="text-muted">Tax Amount</span>
                                            <span id="summaryTaxAmt">—</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:.85rem;" id="summaryTdsRow">
                                            <span class="text-muted">TDS Amount</span>
                                            <span id="summaryTdsAmt">—</span>
                                        </div>
                                        <hr class="my-1">
                                        <div class="d-flex justify-content-between align-items-center fw-bold">
                                            <span>Net Amount</span>
                                            <span id="summaryNet" class="text-primary">—</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment Card -->
                                <div class="card mb-3">
                                    <div class="card-header py-3">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <h6 class="mb-0">Payment</h6>
                                            <div>
                                                <button type="button" id="markPaidBtn"
                                                        class="btn btn-sm <?php echo $isPaid ? 'btn-success' : 'btn-outline-secondary'; ?>"
                                                        style="min-width:120px;">
                                                    <i class="bx <?php echo $isPaid ? 'bx-check' : 'bx-credit-card'; ?> me-1"></i>
                                                    <?php echo $isPaid ? 'Paid' : 'Mark as Paid'; ?>
                                                </button>
                                                <input type="hidden" name="IsPaid" id="isPaidHidden" value="<?php echo $isPaid; ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div id="paymentSection" style="display:<?php echo $isPaid ? 'block' : 'none'; ?>;">
                                        <div class="card-body">

                                            <!-- Payment Date -->
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold" style="font-size:.85rem;">Payment Date</label>
                                                <div class="input-group input-group-merge">
                                                    <span class="input-group-text"><i class="icon-base bx bx-calendar"></i></span>
                                                    <input type="text" class="form-control" id="pmtDate"
                                                           name="PaymentDate" value="<?php echo $pmtDate; ?>" readonly>
                                                </div>
                                            </div>

                                            <!-- Payment Type pills -->
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold" style="font-size:.85rem;">Payment Type</label>
                                                <div class="d-flex flex-wrap gap-2" id="pmtTypePills">
                                                    <?php foreach ($paymentTypes as $pt): ?>
                                                        <button type="button"
                                                                class="btn btn-sm pmt-type-pill <?php echo $pmtTypeUID === (int)$pt->PaymentTypeUID ? 'btn-primary' : 'btn-outline-secondary'; ?>"
                                                                data-uid="<?php echo (int)$pt->PaymentTypeUID; ?>"
                                                                data-name="<?php echo htmlspecialchars($pt->PaymentTypeName); ?>">
                                                            <?php echo htmlspecialchars($pt->PaymentTypeName); ?>
                                                        </button>
                                                    <?php endforeach; ?>
                                                    <?php if (empty($paymentTypes)): ?>
                                                        <?php
                                                        $defaultPmtTypes = ['UPI','Cash','Card','Net Banking','Cheque','EMI'];
                                                        foreach ($defaultPmtTypes as $idx => $pn): ?>
                                                        <button type="button"
                                                                class="btn btn-sm pmt-type-pill <?php echo $idx === 0 ? 'btn-primary' : 'btn-outline-secondary'; ?>"
                                                                data-uid="<?php echo $idx + 1; ?>"
                                                                data-name="<?php echo $pn; ?>">
                                                            <?php echo $pn; ?>
                                                        </button>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </div>
                                                <input type="hidden" name="PaymentTypeUID" id="pmtTypeUID" value="<?php echo $pmtTypeUID ?: (empty($paymentTypes) ? 1 : ''); ?>">
                                            </div>

                                            <!-- Bank Account (hidden for Cash) -->
                                            <div class="mb-3" id="bankSection">
                                                <label class="form-label fw-semibold" style="font-size:.85rem;">Select Bank / Account</label>
                                                <select class="form-select" name="BankAccountUID" id="bankAccountUID">
                                                    <option value="">None / Not Applicable</option>
                                                    <?php foreach ($bankAccounts as $ba): ?>
                                                        <option value="<?php echo (int)$ba->BankAccountUID; ?>"
                                                            <?php echo $bankUID === (int)$ba->BankAccountUID ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($ba->AccountName); ?>
                                                            <?php echo !empty($ba->BankName) ? ' — ' . htmlspecialchars($ba->BankName) : ''; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <!-- Payment Notes -->
                                            <div class="mb-0">
                                                <label class="form-label fw-semibold" style="font-size:.85rem;">Payment Notes</label>
                                                <textarea class="form-control" name="PaymentNotes" id="pmtNotes"
                                                          rows="2" placeholder="Reference, cheque no., UTR..."><?php echo $pmtNotes; ?></textarea>
                                            </div>

                                        </div>
                                    </div>
                                </div>

                                <!-- Submit buttons -->
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                        <i class="bx bx-save me-1"></i>
                                        <?php echo $isEdit ? 'Update Expense' : 'Add Expense'; ?>
                                    </button>
                                    <a href="/expenses" class="btn btn-outline-secondary">Cancel</a>
                                </div>

                            </div>
                        </div><!-- /row -->
                    </form>

                </div>
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
                <h6 class="modal-title">Add Category</h6>
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

<?php $this->load->view('common/transactions/footer'); ?>

<style>
.no-spinner::-webkit-outer-spin-button,
.no-spinner::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
.no-spinner { -moz-appearance: textfield; }
</style>

<script src="/js/transactions/attachments.js"></script>

<script>
$(function () {
    'use strict';

    var curSymbol   = '<?php echo addslashes($JwtData->GenSettings->CurrenySymbol ?? '₹'); ?>';
    var decPoints   = <?php echo (int)($JwtData->GenSettings->DecimalPoints ?? 2); ?>;
    var selectedPmtType = '<?php echo $isEdit ? addslashes($expense->PaymentTypeName ?? '') : 'UPI'; ?>';
    var noBankTypes = ['Cash'];

    // ── Compute amounts ──────────────────────────────────────
    function computeAmounts() {
        var base   = parseFloat($('#expAmount').val()) || 0;
        var isTax  = $('#taxToggle').is(':checked');
        var isTDS  = $('#tdsToggle').is(':checked');
        var taxPct = isTax  ? (parseFloat($('#taxPct').val())  || 0) : 0;
        var tdsPct = isTDS  ? (parseFloat($('#tdsPct').val())  || 0) : 0;

        var taxAmt = isTax ? parseFloat((base * taxPct / 100).toFixed(decPoints)) : 0;
        var tdsAmt = isTDS ? parseFloat((base * tdsPct / 100).toFixed(decPoints)) : 0;
        var net    = parseFloat((base + taxAmt - tdsAmt).toFixed(decPoints));

        $('#taxAmount').val(taxAmt > 0 ? taxAmt.toFixed(decPoints) : '');
        $('#tdsAmount').val(tdsAmt > 0 ? tdsAmt.toFixed(decPoints) : '');

        if (isTax || isTDS) {
            $('#amountSummaryCard').show();
            $('#summaryBase').text(curSymbol + ' ' + base.toFixed(decPoints));
            $('#summaryTaxAmt').text(curSymbol + ' ' + taxAmt.toFixed(decPoints));
            $('#summaryTdsAmt').text(curSymbol + ' ' + tdsAmt.toFixed(decPoints));
            $('#summaryNet').text(curSymbol + ' ' + net.toFixed(decPoints));
            $('#summaryTaxRow').toggle(isTax);
            $('#summaryTdsRow').toggle(isTDS);
        } else {
            $('#amountSummaryCard').hide();
        }
    }

    $('#expAmount, #taxPct, #tdsPct').on('input', computeAmounts);
    computeAmounts();

    // ── Tax toggle ───────────────────────────────────────────
    $('#taxToggle').on('change', function () {
        var on = $(this).is(':checked');
        $('#taxSection').slideToggle(200);
        $('#taxApplicableHidden').prop('disabled', !on).val(on ? 1 : 0);
        $('#taxApplicableOff').prop('disabled', on);
        if (!on) $('#taxPct').val('');
        computeAmounts();
    });

    // ── TDS toggle ───────────────────────────────────────────
    $('#tdsToggle').on('change', function () {
        var on = $(this).is(':checked');
        $('#tdsSection').slideToggle(200);
        $('#tdsApplicableHidden').prop('disabled', !on).val(on ? 1 : 0);
        $('#tdsApplicableOff').prop('disabled', on);
        if (!on) $('#tdsPct').val('');
        computeAmounts();
    });

    // ── Mark as Paid toggle ──────────────────────────────────
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

    // ── Payment type pills ───────────────────────────────────
    function _updateBankVisibility() {
        if (noBankTypes.indexOf(selectedPmtType) !== -1) {
            $('#bankSection').slideUp(150);
            $('#bankAccountUID').val('');
        } else {
            $('#bankSection').slideDown(150);
        }
    }

    $(document).on('click', '.pmt-type-pill', function () {
        $('.pmt-type-pill').removeClass('btn-primary').addClass('btn-outline-secondary');
        $(this).removeClass('btn-outline-secondary').addClass('btn-primary');
        $('#pmtTypeUID').val($(this).data('uid'));
        selectedPmtType = $(this).data('name');
        _updateBankVisibility();
    });

    // Init bank visibility
    _updateBankVisibility();

    // ── Select2 for category ─────────────────────────────────
    if ($.fn.select2) {
        $('.select2-expense-category').select2({
            placeholder: 'Select Category',
            allowClear: true,
            width: '100%',
        });
    }

    // ── Add Category modal ───────────────────────────────────
    $('#addCategoryBtn').on('click', function () {
        $('#newCategoryName').val('');
        var modal = new bootstrap.Modal(document.getElementById('addCategoryModal'));
        modal.show();
        setTimeout(function () { $('#newCategoryName').focus(); }, 350);
    });

    $('#saveCategoryBtn').on('click', function () {
        var name = $.trim($('#newCategoryName').val());
        if (!name) { $('#newCategoryName').addClass('is-invalid'); return; }
        $('#newCategoryName').removeClass('is-invalid');
        $(this).prop('disabled', true).text('Adding…');

        $.ajax({
            url: '/expenses/addCategory', method: 'POST',
            data: { CategoryName: name, [CsrfName]: CsrfToken },
            success: function (resp) {
                $('#saveCategoryBtn').prop('disabled', false).text('Add');
                if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }

                var $sel = $('.select2-expense-category');
                var opt  = new Option(resp.CategoryName, resp.CategoryUID, true, true);
                $sel.append(opt).trigger('change');

                bootstrap.Modal.getInstance(document.getElementById('addCategoryModal')).hide();
                showToastNotification('Category "' + resp.CategoryName + '" added.', 'success');
            },
            error: function () {
                $('#saveCategoryBtn').prop('disabled', false).text('Add');
                showToastNotification('Failed to add category.', 'error');
            }
        });
    });

    // ── Form submission ──────────────────────────────────────
    $('#expenseForm').on('submit', function (e) {
        e.preventDefault();

        var amount = parseFloat($('#expAmount').val()) || 0;
        if (amount <= 0) {
            showToastNotification('Expense amount must be greater than 0.', 'error');
            $('#expAmount').focus();
            return;
        }

        var isPaid = $('#isPaidHidden').val() === '1';
        if (isPaid && !$('#pmtTypeUID').val()) {
            showToastNotification('Please select a payment type.', 'error');
            return;
        }

        var $btn     = $('#submitBtn').prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving…');
        var formData = new FormData(this);
        formData.append(CsrfName, CsrfToken);

        // Append new attachment files from multiDropzone
        if (typeof multiDropzone !== 'undefined' && multiDropzone && multiDropzone.files.length > 0) {
            multiDropzone.files.forEach(function (f) { formData.append('AttachFiles[]', f); });
        }
        // Append removed attachment IDs (edit mode)
        formData.append('RemovedAttachIDs', JSON.stringify(typeof _removedAttachIDs !== 'undefined' ? _removedAttachIDs : []));

        $.ajax({
            url         : $(this).attr('action'),
            method      : 'POST',
            data        : formData,
            processData : false,
            contentType : false,
            success: function (resp) {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i><?php echo $isEdit ? 'Update Expense' : 'Add Expense'; ?>');
                if (resp.Error) { showToastNotification(resp.Message, 'error'); return; }
                showToastNotification(resp.Message, 'success');
                setTimeout(function () { window.location.href = resp.RedirectURL || '/expenses'; }, 800);
            },
            error: function () {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i><?php echo $isEdit ? 'Update Expense' : 'Add Expense'; ?>');
                showToastNotification('An error occurred. Please try again.', 'error');
            }
        });
    });

    // ── Load existing attachments in edit mode ───────────────
    <?php if ($isEdit && $expUID > 0): ?>
    renderTransAttachmentsFromData(<?php echo json_encode($ExpenseAttachments ?? []); ?>);
    <?php else: ?>
    if (typeof _syncDropzoneLimit === 'function') _syncDropzoneLimit(0);
    <?php endif; ?>

    // ── Flatpickr date pickers ───────────────────────────────
    flatpickr('#expDate', {
        dateFormat   : 'Y-m-d',
        altInput     : true,
        altFormat    : 'd-m-Y',
        allowInput   : false,
        clickOpens   : true,
        appendTo     : document.body,
        defaultDate  : '<?php echo $expDate; ?>'
    });
    flatpickr('#pmtDate', {
        dateFormat   : 'Y-m-d',
        altInput     : true,
        altFormat    : 'd-m-Y',
        allowInput   : false,
        clickOpens   : true,
        appendTo     : document.body,
        defaultDate  : '<?php echo $pmtDate; ?>'
    });

});
</script>
