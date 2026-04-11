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
    <div class="payment-rows-container rounded border" style="background:#f6faf7;">
        <table class="table table-sm mb-0" id="paymentRowsTable" style="background:transparent;">
            <thead>
                <tr style="background:#eaf4ec; border-bottom:1px solid #d4e9d7;">
                    <th class="fw-semibold small text-secondary ps-3" style="width:42%;">Notes</th>
                    <th class="fw-semibold small text-secondary" style="width:20%;">Amount</th>
                    <th class="fw-semibold small text-secondary" style="width:28%;">Payment Mode</th>
                    <th style="width:10%;"></th>
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
#paymentRowsTable td, #paymentRowsTable th { border: none; vertical-align: middle; padding: 6px 8px; }
#paymentRowsTable tr + tr td { border-top: 1px solid #d4e9d7; }
.pay-mode-sublabel { font-size: 0.75rem; color: #6c757d; margin-top: 2px; cursor: pointer; }
.pay-mode-sublabel:hover { color: #198754; }
.pay-amount-inp { border: none; background: transparent; box-shadow: none; font-size: 0.9rem; }
.pay-amount-inp:focus { background: #fff; border: 1px solid #86b7fe; box-shadow: 0 0 0 0.2rem rgba(13,110,253,.15); border-radius: 4px; }
.pay-notes-inp { border: none; background: transparent; box-shadow: none; font-size: 0.85rem; resize: none; }
.pay-notes-inp:focus { background: #fff; border: 1px solid #86b7fe; box-shadow: 0 0 0 0.2rem rgba(13,110,253,.15); border-radius: 4px; }
.pay-type-sel { border: none; background: transparent; font-size: 0.85rem; font-weight: 600; box-shadow: none; padding: 0 4px; }
.pay-type-sel:focus { box-shadow: none; border-bottom: 1px solid #198754; }
.pay-bank-sel { border: none; background: transparent; font-size: 0.75rem; color: #6c757d; box-shadow: none; padding: 0 4px; }
.pay-bank-sel:focus { box-shadow: none; }
</style>

<script>
/* Payment section config — plain JS, no jQuery needed */
window._paymentCurrSymbol = '<?php echo addslashes($currSymbol); ?>';
</script>

