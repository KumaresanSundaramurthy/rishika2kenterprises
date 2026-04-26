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
                    <th class="fw-semibold small text-secondary ps-3" style="width:38%;">Notes</th>
                    <th class="fw-semibold small text-secondary" style="width:20%;">Amount</th>
                    <th class="fw-semibold small text-secondary" style="width:34%;">Payment Mode</th>
                    <th style="width:8%;"></th>
                </tr>
            </thead>
            <tbody id="paymentRowsBody">
                <!-- rows injected by JS -->
            </tbody>
        </table>

        <!-- Split Payment Button -->
        <div class="px-3 py-2 border-top" style="border-color:#d4e9d7 !important;">
            <button type="button" class="btn btn-sm btn-link p-0 text-success fw-semibold text-decoration-none" id="splitPaymentBtn">
                <i class="bx bx-plus-circle me-1"></i> Split Payment
            </button>
        </div>
    </div>

    <!-- Balance summary (shown below) -->
    <div class="d-flex align-items-center justify-content-end gap-4 mt-2 px-1 small fw-semibold">
        <span class="text-muted">Bill Total: <span id="payBillTotal" class="text-dark"><?php echo $currSymbol; ?> 0.00</span></span>
        <span class="text-muted">Total Paid: <span id="payTotalPaid" class="text-success"><?php echo $currSymbol; ?> 0.00</span></span>
        <span id="payBalanceWrap" class="text-muted">Balance: <span id="payBalance" class="text-danger"><?php echo $currSymbol; ?> 0.00</span></span>
        <span id="payExcessWrap" class="text-warning d-none">Excess: <span id="payExcess"><?php echo $currSymbol; ?> 0.00</span></span>
    </div>

    <!-- Hidden: serialised payment rows sent with form -->
    <input type="hidden" id="PaymentRowsJson" name="PaymentRows" value="">
    <input type="hidden" id="PaymentIsFullyPaid" name="IsFullyPaid" value="0">
    <input type="hidden" name="RecordPayment" value="1">

</div>

<!-- ── Manage Banks link (shown in bank selector sublabel) ──────── -->
<!-- trigger is rendered by JS; this is just a placeholder comment -->

<!-- ── Embedded data ──────────────────────────────────────────── -->
<script id="paymentTypeOptionsData" type="application/json"><?php echo json_encode(array_values($PaymentTypes ?? [])); ?></script>
<script id="bankAccountOptionsData"  type="application/json"><?php echo json_encode(array_values($BankAccounts  ?? [])); ?></script>

<style>
#paymentRowsTable td, #paymentRowsTable th { border: none; vertical-align: top; padding: 8px 8px; }
#paymentRowsTable td { vertical-align: middle; }
#paymentRowsTable tr + tr td { border-top: 1px solid #cfe5d0; }

.pay-notes-inp {
    background: #fff;
    border: 1px solid #b8d4ba;
    border-radius: 6px;
    font-size: 0.85rem;
    resize: none;
    box-shadow: none;
    width: 100%;
    padding: 6px 10px;
    color: #333;
}
.pay-notes-inp:focus {
    background: #fff;
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
    background: #fff;
    border: 1px solid #b8d4ba;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 600;
    box-shadow: none;
    padding: 6px 8px;
    width: 100%;
    cursor: pointer;
    color: #333;
}
.pay-type-sel:focus {
    background: #fff;
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
</style>

<script>
/* Payment section config — plain JS, no jQuery needed */
window._paymentCurrSymbol = '<?php echo addslashes($currSymbol); ?>';
</script>

