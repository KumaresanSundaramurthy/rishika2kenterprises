<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Payment Section Partial — Swipe-style layout
 * Variables expected:
 *   $PaymentTypes     – array of payment type objects
 *   $BankAccounts     – array of bank account objects
 *   $JwtData          – JWT / session data (GenSettings)
 *   $paymentPartyType – 'C' (customer/invoice) or 'V' (vendor/purchase)
 */
$paymentPartyType = $paymentPartyType ?? 'C';
$currSymbol       = $JwtData->GenSettings->CurrenySymbol ?? '₹';
?>

<!-- ── Credit Note Banner (shown only when customer has pending CNs) ──── -->
<div id="cnBannerWrap" class="d-none">
    <hr class="mt-3 mb-0"/>
    <div id="cnBannerBtn" role="button" class="cn-banner-btn d-flex align-items-center gap-2 px-3 py-2" title="Click to view and apply a credit note">
        <i class="bx bx-credit-card-alt cn-banner-icon"></i>
        <span class="cn-banner-label">Credit Notes Available</span>
        <span class="cn-banner-hint ms-auto">Click to view &amp; apply</span>
        <i class="bx bx-chevron-right cn-banner-chevron"></i>
    </div>
    <hr class="my-0"/>
</div>

<!-- ── Payment Section ─────────────────────────────────────────── -->
<div class="payment-section-wrap px-1 pt-3 pb-2">

    <!-- Applied Credit Strip (shown after a credit note is selected) -->
    <div id="cnAppliedStrip" class="d-none cn-applied-strip mb-3 px-1">
        <div class="d-flex align-items-center gap-2">
            <i class="bx bx-check-circle text-success" style="font-size:1.05rem;"></i>
            <span class="fw-semibold text-success small">Credit Applied:</span>
            <span id="cnAppliedLabel" class="text-dark small fw-semibold"></span>
            <span class="text-success small fw-bold ms-1">— <span id="cnAppliedAmt"></span></span>
            <button type="button" id="cnRemoveBtn" class="btn btn-sm btn-outline-danger cn-remove-btn ms-auto">
                <i class="bx bx-x me-1"></i>Remove
            </button>
        </div>
    </div>

    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-2 px-1">
        <span class="fw-semibold text-dark" style="font-size:0.95rem;">
            Add payment <span class="text-muted fw-normal small">(Payment Notes, Amount and Mode)</span>
        </span>
        <div class="form-check mb-0 ms-3">
            <input class="form-check-input" type="checkbox" id="isFullyPaid">
            <label class="form-check-label small fw-semibold" for="isFullyPaid">Mark as fully paid</label>
        </div>
    </div>

    <!-- Payment Rows -->
    <div class="payment-rows-container rounded border" style="background:#f0f7f1;">
        <table class="table table-sm mb-0" id="paymentRowsTable" style="background:transparent;">
            <thead>
                <tr style="background:#dff0e2; border-bottom:1px solid #b8d4ba;">
                    <th class="fw-semibold small text-secondary ps-3" style="width:34%;">Notes</th>
                    <th class="fw-semibold small text-secondary" style="width:16%;">Date</th>
                    <th class="fw-semibold small text-secondary" style="width:15%;">Amount</th>
                    <th class="fw-semibold small text-secondary" style="width:28%;">Payment Mode</th>
                    <th style="width:7%;"></th>
                </tr>
            </thead>
            <tbody id="paymentRowsBody">
                <!-- rows injected by JS -->
            </tbody>
        </table>

        <!-- Split Payment button only -->
        <div class="px-3 py-2 border-top" style="border-color:#d4e9d7 !important;">
            <button type="button" class="btn btn-sm btn-link p-0 text-success fw-semibold text-decoration-none" id="splitPaymentBtn">
                <i class="bx bx-plus-circle me-1"></i>Split Payment
            </button>
        </div>
    </div>

    <!-- Balance summary (shown below) -->
    <div class="d-flex align-items-center justify-content-end flex-wrap gap-4 mt-2 px-1 small fw-semibold">
        <span class="text-muted">Bill Total: <span id="payBillTotal" class="text-dark"><?php echo $currSymbol; ?> 0.00</span></span>
        <span id="cnCreditAmtWrap" class="text-muted d-none">Credit Amount: <span id="cnCreditAmt" class="text-success"><?php echo $currSymbol; ?> 0.00</span></span>
        <span class="text-muted">Total Paid: <span id="payTotalPaid" class="text-success"><?php echo $currSymbol; ?> 0.00</span></span>
        <span id="payBalanceWrap" class="text-muted">Balance: <span id="payBalance" class="text-danger"><?php echo $currSymbol; ?> 0.00</span></span>
        <span id="payExcessWrap" class="text-warning d-none">Excess: <span id="payExcess"><?php echo $currSymbol; ?> 0.00</span></span>
    </div>

    <!-- Payment Attachments Section (hidden for vendor/purchase pages) -->
    <div class="payment-attachments-section mt-3 px-1<?php echo $paymentPartyType === 'V' ? ' d-none' : ''; ?>">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="fw-semibold text-dark" style="font-size:0.9rem;">
                <i class="bx bx-paperclip me-1"></i>Payment Attachments
                <span class="text-muted fw-normal small">(Max 3 files, 3MB each)</span>
            </span>
        </div>
        
        <!-- Upload Button -->
        <div class="mb-2">
            <input type="file" id="paymentAttachmentInput" class="d-none" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx" multiple>
            <button type="button" class="btn btn-sm btn-outline-primary" id="uploadPaymentAttachmentBtn">
                <i class="bx bx-upload me-1"></i>Upload Files
            </button>
            <span class="text-muted small ms-2">Supported: Images, PDF, DOC, XLS</span>
        </div>
        
        <!-- Uploaded Files List -->
        <div id="paymentAttachmentsList" class="uploaded-files-list">
            <!-- Files will be listed here -->
        </div>
    </div>

    <!-- Hidden: serialised payment rows sent with form -->
    <input type="hidden" id="PaymentRowsJson" name="PaymentRows" value="">
    <input type="hidden" id="PaymentIsFullyPaid" name="IsFullyPaid" value="0">
    <input type="hidden" name="RecordPayment" value="1">
    <input type="hidden" id="PaymentAttachmentsJson" name="PaymentAttachments" value="">
    <input type="hidden" id="CreditNoteUIDInput" name="CreditNoteUID" value="">

</div>

<!-- ── Credit Note Selection Modal ─────────────────────────────── -->
<div class="modal fade" id="cnSelectModal" tabindex="-1" aria-labelledby="cnSelectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border:none;border-radius:12px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,.13);">

            <!-- Header -->
            <div class="modal-header px-4 py-3" style="background:linear-gradient(135deg,#198754 0%,#20c075 100%);border-bottom:none;">
                <div class="d-flex align-items-center gap-2">
                    <div style="width:32px;height:32px;background:rgba(255,255,255,.2);border-radius:8px;display:flex;align-items:center;justify-content:center;">
                        <i class="bx bx-credit-card-alt text-white" style="font-size:1.1rem;"></i>
                    </div>
                    <div>
                        <h6 class="modal-title fw-bold text-white mb-0" id="cnSelectModalLabel" style="font-size:.95rem;">Select Credit Note to Apply</h6>
                        <div class="text-white opacity-75" style="font-size:.72rem;">Choose one credit note from the list below</div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Body -->
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0" id="cnModalTable">
                        <thead>
                            <tr style="background:#f0f7f1;border-bottom:2px solid #b8d4ba;">
                                <th style="width:44px;padding:10px 16px;"></th>
                                <th class="fw-semibold text-secondary" style="font-size:.78rem;letter-spacing:.04em;padding:10px 12px;">CREDIT NOTE #</th>
                                <th class="fw-semibold text-secondary" style="font-size:.78rem;letter-spacing:.04em;padding:10px 12px;">TYPE</th>
                                <th class="fw-semibold text-secondary" style="font-size:.78rem;letter-spacing:.04em;padding:10px 12px;">SOURCE INVOICE</th>
                                <th class="fw-semibold text-secondary text-end" style="font-size:.78rem;letter-spacing:.04em;padding:10px 12px;">AMOUNT</th>
                                <th class="fw-semibold text-secondary" style="font-size:.78rem;letter-spacing:.04em;padding:10px 12px;">DATE</th>
                            </tr>
                        </thead>
                        <tbody id="cnModalTableBody">
                            <tr><td colspan="6" class="text-center text-muted py-5" style="font-size:.88rem;">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Footer -->
            <div class="modal-footer px-4 py-3 d-flex align-items-center gap-2" style="background:#f8fdf9;border-top:1px solid #d4e9d7;">
                <div class="d-flex align-items-center gap-1 me-auto">
                    <i class="bx bx-info-circle text-muted" style="font-size:.95rem;"></i>
                    <span class="text-muted" style="font-size:.78rem;">Credit notes exceeding the invoice total are disabled.</span>
                </div>
                <button type="button" class="btn btn-sm px-3" style="border:1px solid #dee2e6;color:#6c757d;background:#fff;border-radius:6px;" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success btn-sm px-4" id="cnApplyBtn" disabled style="border-radius:6px;">
                    <i class="bx bx-check me-1"></i>Apply Credit Note
                </button>
            </div>

        </div>
    </div>
</div>

<!-- ── Manage Banks link (shown in bank selector sublabel) ──────── -->
<!-- trigger is rendered by JS; this is just a placeholder comment -->

<!-- ── Embedded data ──────────────────────────────────────────── -->
<script id="paymentTypeOptionsData" type="application/json"><?php echo json_encode(array_values($PaymentTypes ?? [])); ?></script>
<script id="bankAccountOptionsData"  type="application/json"><?php echo json_encode(array_values($BankAccounts  ?? [])); ?></script>

<style>
#paymentRowsTable { table-layout: fixed; }
#paymentRowsTable th { border: none; vertical-align: middle; padding: 8px 8px; }
#paymentRowsTable td { border: none; vertical-align: top; padding: 8px 8px; }
#paymentRowsTable tr + tr td { border-top: 1px solid #cfe5d0; }
#paymentRowsTable td:nth-child(1) { width: 34%; }
#paymentRowsTable td:nth-child(2) { width: 16%; }
#paymentRowsTable td:nth-child(3) { width: 15%; }
#paymentRowsTable td:nth-child(4) { width: 28%; }
#paymentRowsTable td:nth-child(5) { width: 7%; text-align: center; }

.pay-notes-inp {
    background-color: #fff;
    border: 1px solid #b8d4ba;
    border-radius: 6px;
    font-size: 0.85rem;
    resize: none;
    box-shadow: none;
    width: 100%;
    height: 100%;
    min-height: 34px;
    padding: 6px 10px;
    color: #333;
    overflow-y: hidden;
}
.pay-notes-inp:focus {
    background-color: #fff;
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.18rem rgba(13,110,253,.15);
    outline: none;
}

.pay-amount-inp {
    background: #fff;
    border: 1px solid #b8d4ba;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 600;
    box-shadow: none;
    width: 100%;
    padding: 6px 10px;
    text-align: right;
    color: #333;
}
.pay-amount-inp:focus {
    background: #fff;
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.18rem rgba(13,110,253,.15);
    outline: none;
}

.pay-date-inp {
    background: #fff;
    border: 1px solid #b8d4ba;
    border-radius: 6px;
    font-size: 0.85rem;
    box-shadow: none;
    width: 100%;
    padding: 6px 10px;
    color: #333;
    cursor: pointer;
}
.pay-date-inp:focus {
    background: #fff;
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.18rem rgba(13,110,253,.15);
    outline: none;
}

.pay-type-sel {
    background-color: #fff;
    border: 1px solid #b8d4ba;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 600;
    box-shadow: none;
    padding: 6px 8px;
    width: 100%;
    max-width: 100%;
    cursor: pointer;
    color: #333;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.pay-type-sel:focus {
    background-color: #fff;
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.18rem rgba(13,110,253,.15);
    outline: none;
}

.pay-bank-wrap {
    margin-top: 5px;
    display: flex;
    align-items: center;
    gap: 4px;
}
.pay-bank-sel {
    background: #fff;
    border: 1px solid #b8d4ba;
    border-radius: 6px;
    font-size: 0.78rem;
    color: #566a7f;
    box-shadow: none;
    padding: 4px 8px;
    flex: 1;
    min-width: 0;
    cursor: pointer;
}
.pay-bank-sel:focus {
    background: #fff;
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.18rem rgba(13,110,253,.15);
    outline: none;
}
.pay-bank-link {
    color: #adb5bd;
    font-size: 0.82rem;
    flex-shrink: 0;
    line-height: 1;
}
.pay-bank-link:hover { color: #198754; }

.pay-cash-label {
    display: inline-block;
    margin-top: 5px;
    font-size: 0.75rem;
    color: #6c757d;
    background: #e8f5ea;
    border-radius: 4px;
    padding: 2px 8px;
}
.pay-mode-sublabel { font-size: 0.75rem; color: #6c757d; margin-top: 4px; }

/* Credit Note Banner */
.cn-banner-btn {
    cursor: pointer;
    transition: background .15s;
}
.cn-banner-btn:hover { background: #f0faf2; }
.cn-banner-icon   { font-size: 1.05rem; color: #198754; }
.cn-banner-label  { font-size: .88rem; font-weight: 600; color: #198754; }
.cn-banner-hint   { font-size: .78rem; color: #6c757d; }
.cn-banner-chevron { font-size: .9rem; color: #adb5bd; }

/* Applied Credit Strip */
.cn-applied-strip {
    background: #f0fdf4;
    border: 1px solid #86efac;
    border-radius: 6px;
    padding: 8px 12px;
}
.cn-remove-btn { font-size: .75rem; padding: 2px 10px; }

/* Credit Note Modal rows */
#cnModalTable td { border: none; padding: 11px 12px; vertical-align: middle; font-size: .875rem; }
#cnModalTable tr + tr td { border-top: 1px solid #eef6ef; }
#cnModalTable td:first-child { padding-left: 16px; }
.cn-modal-row { cursor: pointer; transition: background .12s; }
.cn-modal-row:hover { background: #f4fbf5; }
.cn-row-disabled { opacity: .5; cursor: not-allowed; }
.cn-row-disabled td { color: #adb5bd; }

/* Payment Attachments Styles */
.uploaded-files-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.uploaded-file-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 12px;
    background: #fff;
    border: 1px solid #d4e9d7;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.uploaded-file-item:hover {
    background: #f8fdf9;
    border-color: #b8d4ba;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.file-info {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1;
    min-width: 0;
    cursor: pointer;
}

.file-icon {
    font-size: 24px;
    flex-shrink: 0;
}

.file-icon.file-image { color: #0d6efd; }
.file-icon.file-pdf { color: #dc3545; }
.file-icon.file-doc { color: #0d6efd; }
.file-icon.file-xls { color: #198754; }
.file-icon.file-default { color: #6c757d; }

.file-details {
    flex: 1;
    min-width: 0;
}

.file-name {
    font-size: 0.85rem;
    font-weight: 600;
    color: #333;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 2px;
}

.file-size {
    font-size: 0.75rem;
    color: #6c757d;
}

.file-remove-btn {
    background: #fff;
    border: 1px solid #dc3545;
    color: #dc3545;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 0.75rem;
    cursor: pointer;
    transition: all 0.2s ease;
    flex-shrink: 0;
}

.file-remove-btn:hover {
    background: #dc3545;
    color: #fff;
}
</style>

<script>
window._paymentCurrSymbol  = '<?php echo addslashes($currSymbol); ?>';
window._paymentPartyType   = '<?php echo addslashes($paymentPartyType); ?>';
var _psDecimal = <?php echo (int)($JwtData->GenSettings->DecimalPoints ?? 2); ?>;

function _hasItems() {
    return typeof billManager !== 'undefined' && billManager.getAllItems().length > 0;
}

document.addEventListener('click', function(e) {
    var t = e.target.closest('#splitPaymentBtn');
    if (t) {
        if (!_hasItems()) {
            showToastNotification('Please add at least one product before adding a payment.', 'error');
            e.stopImmediatePropagation();
        }
    }
});
</script>

