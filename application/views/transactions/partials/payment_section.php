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

<hr class="mt-3 mb-0"/>

<!-- ── Payment Section ─────────────────────────────────────────── -->
<div class="payment-section-wrap px-1 pt-3 pb-2">

    <!-- ── On Account info card (shown when customer has On Account balance) ── -->
    <div id="onAccountInfoCard" class="d-none mb-3 rounded" style="background:#fffbf0;border:1px solid #ffc107;">
        <div class="px-3 py-2 d-flex align-items-start justify-content-between gap-3">
            <div>
                <div class="fw-semibold mb-1" style="font-size:.84rem;color:#856404;">
                    <i class="bx bx-wallet me-1"></i>On Account Credits Available
                </div>
                <div style="font-size:.76rem;color:#78716c;line-height:1.5;">
                    Previously paid amounts from cancelled invoices are held as credits.
                    Apply them to reduce this invoice amount instead of collecting fresh payment.
                </div>
            </div>
            <button type="button" id="btnShowOnAccountPanel" class="btn btn-sm btn-warning flex-shrink-0 fw-semibold" style="font-size:.76rem;">
                <i class="bx bx-wallet me-1"></i>On Account
            </button>
        </div>
    </div>

    <!-- ── On Account Applied Info (shown after Confirm & Apply) ──────────── -->
    <div id="onAccountAppliedInfo" class="d-none mb-3 rounded" style="background:#f0fdf4;border:1px solid #86efac;">
        <div class="px-3 py-2 d-flex align-items-center justify-content-between"
             style="background:#dcfce7;border-bottom:1px solid #86efac;border-radius:6px 6px 0 0;">
            <span style="font-size:.82rem;font-weight:600;color:#166534;">
                <i class="bx bx-check-circle me-1"></i>On Account Applied
            </span>
            <div class="d-flex gap-3">
                <button type="button" id="btnEditOnAccount" title="Edit selection"
                        style="background:none;border:none;padding:0;cursor:pointer;color:#15803d;">
                    <i class="bx bx-edit" style="font-size:1rem;"></i>
                </button>
                <button type="button" id="btnRemoveOnAccount" title="Remove On Account"
                        style="background:none;border:none;padding:0;cursor:pointer;color:#dc2626;">
                    <i class="bx bx-trash" style="font-size:1rem;"></i>
                </button>
            </div>
        </div>
        <div id="onAccountAppliedRecords" class="px-3 py-2" style="font-size:.8rem;color:#166534;">
            <!-- records injected by JS -->
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
                    <th class="fw-semibold small text-secondary ps-3" style="width:47%;">Notes</th>
                    <th class="fw-semibold small text-secondary" style="width:18%;">Amount</th>
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

    <!-- Hidden: serialised On Account apply items -->
    <input type="hidden" id="OnAccountApplyJson" name="OnAccountApplyJson" value="">

    <!-- Balance summary (shown below) -->
    <div class="d-flex align-items-center justify-content-end gap-4 mt-2 px-1 small fw-semibold">
        <span class="text-muted">Bill Total: <span id="payBillTotal" class="text-dark"><?php echo $currSymbol; ?> 0.00</span></span>
        <span class="text-muted">Total Paid: <span id="payTotalPaid" class="text-success"><?php echo $currSymbol; ?> 0.00</span></span>
        <span id="payBalanceWrap" class="text-muted">Balance: <span id="payBalance" class="text-danger"><?php echo $currSymbol; ?> 0.00</span></span>
        <span id="payExcessWrap" class="text-warning d-none">Excess: <span id="payExcess"><?php echo $currSymbol; ?> 0.00</span></span>
    </div>

    <!-- Payment Attachments Section -->
    <div class="payment-attachments-section mt-3 px-1">
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

</div>

<!-- ── On Account Credits Modal ────────────────────────────────────────────── -->
<div class="modal fade" id="onAccountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" style="margin-top:60px;">
        <div class="modal-content" style="overflow:hidden;">

            <!-- vtm-banner header -->
            <div class="vtm-banner flex-shrink-0" style="--vtm-color:#d97706;--vtm-bg:#fff8e1;--vtm-icon-bg:rgba(217,119,6,.12);">
                <div class="vtm-banner-inner">
                    <div class="vtm-banner-left">
                        <div class="vtm-banner-icon">
                            <i class="bx bx-wallet"></i>
                        </div>
                        <div>
                            <div class="vtm-doc-number">On Account Credits</div>
                            <div class="vtm-doc-meta">Select records to apply against this invoice</div>
                        </div>
                    </div>
                    <div class="vtm-banner-right">
                        <button type="button" class="vtm-close-btn" data-bs-dismiss="modal">
                            <i class="bx bx-x"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Table of On Account records -->
            <div class="modal-body p-0">
                <!-- Loading state while AJAX fetches records -->
                <div id="onAccountLoading" class="d-none text-center py-4">
                    <div class="spinner-border spinner-border-sm text-warning me-2"></div>
                    <span class="text-muted" style="font-size:.85rem;">Loading On Account records...</span>
                </div>
                <div class="table-responsive" id="onAccountTableWrap">
                    <table class="table table-sm table-hover mb-0" id="onAccountRecordsTable">
                        <thead style="background:#fff3cd;">
                            <tr>
                                <th class="ps-3" style="width:36px;"></th>
                                <th style="font-size:.76rem;font-weight:700;color:#856404;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap;">Invoice No</th>
                                <th style="font-size:.76rem;font-weight:700;color:#856404;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap;">Invoice Date</th>
                                <th style="font-size:.76rem;font-weight:700;color:#856404;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap;">Invoice Amt</th>
                                <th style="font-size:.76rem;font-weight:700;color:#856404;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap;">On Account</th>
                                <th style="font-size:.76rem;font-weight:700;color:#856404;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap;">Received On</th>
                                <th style="font-size:.76rem;font-weight:700;color:#856404;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap;">Apply Amt</th>
                            </tr>
                        </thead>
                        <tbody id="onAccountRecordsList">
                            <!-- rows injected by JS -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Footer -->
            <div class="modal-footer py-2 d-flex align-items-center justify-content-between border-top" style="border-color:#ffc107 !important;background:#fffdf0;">
                <div>
                    <span style="font-size:.82rem;color:#856404;">
                        Selected: <strong id="oaSelectedTotal"><?php echo $currSymbol; ?> 0.00</strong>
                    </span>
                    <div id="oaRemainingMsg" style="font-size:.75rem;color:#6c757d;margin-top:2px;"></div>
                </div>
                <button type="button" id="btnConfirmOnAccount" class="btn btn-warning btn-sm fw-semibold">
                    <i class="bx bx-check me-1"></i>Confirm &amp; Apply
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
#paymentRowsTable { table-layout: fixed; }
#paymentRowsTable td, #paymentRowsTable th { border: none; vertical-align: middle; padding: 8px 8px; }
#paymentRowsTable tr + tr td { border-top: 1px solid #cfe5d0; }
#paymentRowsTable td:nth-child(1) { width: 47%; }
#paymentRowsTable td:nth-child(2) { width: 18%; }
#paymentRowsTable td:nth-child(3) { width: 28%; }
#paymentRowsTable td:nth-child(4) { width: 7%; text-align: center; }

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
/* Payment section config — plain JS, no jQuery needed */
window._paymentCurrSymbol = '<?php echo addslashes($currSymbol); ?>';

// ── On Account panel logic ────────────────────────────────────────────────────
window._oaRecords      = [];
window._oaCustomerUID  = 0;
var _oaCur = window._paymentCurrSymbol;

// Called by form.php when customer is selected.
// total      = On Account balance (from cache) — shows button immediately
// records    = full FIFO list (may be [] if cache is stale — fetched lazily on panel open)
// customerUID = needed for lazy AJAX fetch
window._loadOnAccountPanel = function(records, customerUID, total) {
    window._oaRecords     = records || [];
    window._oaCustomerUID = parseInt(customerUID, 10) || 0;
    var card = document.getElementById('onAccountInfoCard');
    var applied = document.getElementById('onAccountAppliedInfo');
    var hasApplied = (document.getElementById('OnAccountApplyJson') || {}).value;

    // Show info card only when On Account available AND not already applied
    if (card) {
        var show = (parseFloat(total) || 0) > 0 && !hasApplied;
        card.classList.toggle('d-none', !show);
    }
    // Keep applied info block visible if already applied
    if (applied && hasApplied) applied.classList.remove('d-none');
};

// Called when customer is cleared
window._clearOnAccountPanel = function() {
    window._oaRecords     = [];
    window._oaCustomerUID = 0;
    var card    = document.getElementById('onAccountInfoCard');
    var applied = document.getElementById('onAccountAppliedInfo');
    var json    = document.getElementById('OnAccountApplyJson');
    if (card)    card.classList.add('d-none');
    if (applied) applied.classList.add('d-none');
    if (json)    json.value = '';
    if (typeof updatePaymentSummary === 'function') updatePaymentSummary();
};

function _renderOnAccountRecords() {
    var billTotal = parseFloat((document.getElementById('payBillTotal') || {}).textContent.replace(/[^0-9.]/g, '')) || 0;

    // Load previously applied selections (edit mode)
    var appliedMap = {};
    try {
        var saved = JSON.parse(document.getElementById('OnAccountApplyJson').value || '[]') || [];
        saved.forEach(function(x) { appliedMap[parseInt(x.PaymentUID, 10)] = parseFloat(x.ApplyAmount); });
    } catch(ex) {}
    var isEditMode = Object.keys(appliedMap).length > 0;

    var cumulative = 0;
    var html = '';

    for (var i = 0; i < window._oaRecords.length; i++) {
        var r        = window._oaRecords[i];
        var uid      = parseInt(r.PaymentUID, 10);
        var oaAmt    = parseFloat(r.Amount);
        var invAmt   = parseFloat(r.SourceInvoiceAmount || 0);
        var srcInv   = r.SourceInvoiceNumber || '—';
        var invDate  = r.SourceInvoiceDate  ? r.SourceInvoiceDate.substr(0, 10) : '—';
        var recvDate = r.CreatedOn          ? r.CreatedOn.substr(0, 10)         : '—';

        var isChecked, applyAmt, isFreeze;

        if (isEditMode) {
            // Restore previous selection exactly as the user left it
            if (appliedMap[uid] !== undefined) {
                applyAmt  = appliedMap[uid];
                isChecked = true;
                isFreeze  = false;
            } else {
                // Record not in previous selection — freeze if bill already covered
                var coveredByApplied = 0;
                Object.keys(appliedMap).forEach(function(k) { coveredByApplied += appliedMap[k]; });
                isFreeze  = (billTotal > 0 && round2(coveredByApplied) >= billTotal);
                applyAmt  = 0;
                isChecked = false;
            }
        } else {
            // Fresh open — FIFO auto-selection
            isFreeze  = (billTotal > 0 && cumulative >= billTotal);
            var remaining = billTotal > 0 ? Math.max(0, round2(billTotal - cumulative)) : oaAmt;
            applyAmt  = isFreeze ? 0 : (billTotal > 0 ? Math.min(oaAmt, remaining) : oaAmt);
            isChecked = !isFreeze;
            cumulative = round2(cumulative + (isChecked ? applyAmt : 0));
        }

        var isPartial = (isChecked && applyAmt < oaAmt && applyAmt > 0);
        var rowStyle  = isFreeze ? 'opacity:.45;' : '';

        var applyCell = isPartial
            ? '<span style="color:#0d6efd;font-weight:600;">' + _oaCur + ' ' + applyAmt.toFixed(2) + '</span>' +
              '<div style="font-size:.7rem;color:#6c757d;">' + _oaCur + round2(oaAmt - applyAmt).toFixed(2) + ' stays On A/c</div>'
            : '<span style="font-weight:600;">' + _oaCur + ' ' + applyAmt.toFixed(2) + '</span>';

        html += '<tr style="' + rowStyle + '">' +
            '<td class="ps-3">' +
                '<input type="checkbox" class="form-check-input oa-check mt-0"' +
                ' data-uid="' + uid + '" data-amount="' + oaAmt + '" data-apply="' + applyAmt + '"' +
                (isChecked ? ' checked' : '') +
                (isFreeze  ? ' disabled' : '') +
                '>' +
            '</td>' +
            '<td style="font-size:.82rem;font-weight:600;color:#0d6efd;">' + srcInv + '</td>' +
            '<td style="font-size:.82rem;">' + invDate + '</td>' +
            '<td style="font-size:.82rem;">' + (invAmt > 0 ? _oaCur + ' ' + invAmt.toFixed(2) : '—') + '</td>' +
            '<td style="font-size:.82rem;font-weight:600;color:#856404;">' + _oaCur + ' ' + oaAmt.toFixed(2) + '</td>' +
            '<td style="font-size:.82rem;">' + recvDate + '</td>' +
            '<td>' + applyCell + '</td>' +
        '</tr>';
    }

    document.getElementById('onAccountRecordsList').innerHTML =
        html || '<tr><td colspan="7" class="text-center text-muted py-3">No On Account records found.</td></tr>';
    _recalcOASelected();
}

function _recalcOASelected() {
    var total     = 0;
    var items     = [];
    var billTotal = parseFloat((document.getElementById('payBillTotal') || {}).textContent.replace(/[^0-9.]/g,'')) || 0;

    document.querySelectorAll('#onAccountRecordsList .oa-check:checked').forEach(function(cb) {
        var applyAmt = parseFloat(cb.getAttribute('data-apply')) || 0;
        total += applyAmt;
        items.push({ PaymentUID: parseInt(cb.getAttribute('data-uid'), 10), ApplyAmount: applyAmt });
    });

    total = round2(total);
    var selEl = document.getElementById('oaSelectedTotal');
    var msgEl = document.getElementById('oaRemainingMsg');
    if (selEl) selEl.textContent = _oaCur + ' ' + total.toFixed(2);

    var remainder = round2(billTotal - total);
    if (msgEl) {
        if      (total > 0 && remainder > 0)  msgEl.textContent = 'Invoice balance remaining: ' + _oaCur + ' ' + remainder.toFixed(2);
        else if (total > 0 && remainder <= 0) msgEl.textContent = 'Invoice fully covered by On Account.';
        else                                   msgEl.textContent = '';
    }

    var jsonEl = document.getElementById('OnAccountApplyJson');
    if (jsonEl) jsonEl.value = items.length ? JSON.stringify(items) : '';
}

function round2(v) { return Math.round(v * 100) / 100; }

function _hasItems() {
    return typeof billManager !== 'undefined' && billManager.getAllItems().length > 0;
}

function _openOnAccountModal() {
    var modal     = bootstrap.Modal.getOrCreateInstance(document.getElementById('onAccountModal'));
    var loadingEl = document.getElementById('onAccountLoading');
    var tableEl   = document.getElementById('onAccountTableWrap');

    if (window._oaRecords.length > 0) {
        if (loadingEl) loadingEl.classList.add('d-none');
        if (tableEl)   tableEl.classList.remove('d-none');
        _renderOnAccountRecords();
        modal.show();
    } else if (window._oaCustomerUID > 0) {
        // Show modal immediately with spinner — no global UI block
        if (loadingEl) loadingEl.classList.remove('d-none');
        if (tableEl)   tableEl.classList.add('d-none');
        modal.show();

        var _prev = (typeof AjaxLoading !== 'undefined') ? AjaxLoading : 1;
        if (typeof AjaxLoading !== 'undefined') AjaxLoading = 0;
        var _oaPostData = { CustomerUID: window._oaCustomerUID };
        if (typeof CsrfName !== 'undefined' && typeof CsrfToken !== 'undefined') {
            _oaPostData[CsrfName] = CsrfToken;
        }
        $.post('/customers/getCustomerOnAccountBalance', _oaPostData, function(resp) {
            if (typeof AjaxLoading !== 'undefined') AjaxLoading = _prev;
            if (loadingEl) loadingEl.classList.add('d-none');
            if (tableEl)   tableEl.classList.remove('d-none');
            if (resp && !resp.Error) {
                window._oaRecords = resp.Payments || [];
                _renderOnAccountRecords();
            }
        }, 'json');
    }
}

document.addEventListener('click', function(e) {
    // Split Payment — require items first
    var t = e.target.closest('#splitPaymentBtn');
    if (t) {
        if (!_hasItems()) {
            showToastNotification('Please add at least one product before adding a payment.', 'error');
            e.stopImmediatePropagation();
        }
        return;
    }

    // On Account button — require items first, then open modal
    t = e.target.closest('#btnShowOnAccountPanel');
    if (t) {
        if (!_hasItems()) {
            showToastNotification('Please add at least one product before applying On Account credits.', 'error');
            return;
        }
        _openOnAccountModal();
        return;
    }

    // Confirm inside modal — build applied info block
    t = e.target.closest('#btnConfirmOnAccount');
    if (t) {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('onAccountModal')).hide();
        var items = [];
        try { items = JSON.parse(document.getElementById('OnAccountApplyJson').value || '[]') || []; } catch(ex) {}
        if (items.length > 0) {
            var totalApplied = 0;
            var html = '';
            items.forEach(function(item) {
                var rec = (window._oaRecords || []).filter(function(r) { return parseInt(r.PaymentUID, 10) === parseInt(item.PaymentUID, 10); })[0];
                var invNum = rec ? (rec.SourceInvoiceNumber || '—') : '—';
                var amt = parseFloat(item.ApplyAmount) || 0;
                totalApplied += amt;
                html += '<div style="padding:2px 0;">' +
                    '<span style="font-weight:600;">' + invNum + '</span>' +
                    ' &rarr; <span style="font-weight:700;">' + _oaCur + ' ' + amt.toFixed(2) + '</span>' +
                '</div>';
            });
            html += '<div style="margin-top:5px;padding-top:5px;border-top:1px solid #86efac;font-weight:700;">' +
                'Total Applied: ' + _oaCur + ' ' + totalApplied.toFixed(2) + '</div>';
            document.getElementById('onAccountAppliedRecords').innerHTML = html;
            document.getElementById('onAccountAppliedInfo').classList.remove('d-none');
            document.getElementById('onAccountInfoCard').classList.add('d-none');
            // Update payment summary so Balance reflects On Account
            if (typeof updatePaymentSummary === 'function') updatePaymentSummary();
        }
        return;
    }

    // Edit — reopen modal with existing selection pre-populated
    t = e.target.closest('#btnEditOnAccount');
    if (t) {
        _openOnAccountModal();
        return;
    }

    // Remove — clear selection, restore info card
    t = e.target.closest('#btnRemoveOnAccount');
    if (t) {
        document.getElementById('OnAccountApplyJson').value = '';
        document.getElementById('onAccountAppliedInfo').classList.add('d-none');
        document.getElementById('onAccountAppliedRecords').innerHTML = '';
        // Restore info card if customer still has On Account balance
        if (window._oaRecords.length > 0 || window._oaCustomerUID > 0) {
            document.getElementById('onAccountInfoCard').classList.remove('d-none');
        }
        if (typeof updatePaymentSummary === 'function') updatePaymentSummary();
        return;
    }
});

document.addEventListener('change', function(e) {
    if (e.target.classList.contains('oa-check')) _recalcOASelected();
});

document.addEventListener('payBillTotalChanged', function() {
    if (window._oaRecords.length) _renderOnAccountRecords();
});
</script>

