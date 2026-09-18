<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Invoice Payment — <?php echo htmlspecialchars($stub->UniqueNumber ?? '', ENT_QUOTES, 'UTF-8'); ?></title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Segoe UI', 'Public Sans', system-ui, sans-serif;
    background: #040b18;
    color: #e2e8f0;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

/* ── Top bar ─────────────────────────────────────────────────── */
.pay-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 2rem;
    border-bottom: 1px solid rgba(105, 108, 255, 0.1);
    background: rgba(4, 11, 24, 0.9);
    position: sticky;
    top: 0;
    z-index: 10;
}

.pay-org {
    display: flex;
    align-items: center;
    gap: 0.65rem;
}

.pay-org-logo {
    height: 34px;
    width: auto;
    border-radius: 6px;
    object-fit: contain;
}

.pay-org-initial {
    width: 34px; height: 34px;
    border-radius: 8px;
    background: linear-gradient(135deg, #696cff, #38bdf8);
    display: flex; align-items: center; justify-content: center;
    font-size: 0.85rem;
    font-weight: 800;
    color: #fff;
    letter-spacing: -0.02em;
    flex-shrink: 0;
}

.pay-org-name {
    font-size: 0.92rem;
    font-weight: 700;
    color: #e2e8f0;
    letter-spacing: 0.01em;
}

.pay-secure-badge {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.72rem;
    color: rgba(160, 190, 215, 0.45);
    border: 1px solid rgba(105, 108, 255, 0.12);
    border-radius: 100px;
    padding: 0.3rem 0.75rem;
}

.pay-secure-dot {
    width: 7px; height: 7px;
    border-radius: 50%;
    background: #4ade80;
    box-shadow: 0 0 6px rgba(74, 222, 128, 0.6);
    flex-shrink: 0;
}

/* ── Body ────────────────────────────────────────────────────── */
.pay-body {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2.5rem 1.25rem 4rem;
}

/* ── Card ────────────────────────────────────────────────────── */
.pay-card {
    width: 100%;
    max-width: 460px;
    background: rgba(8, 18, 38, 0.92);
    border: 1px solid rgba(105, 108, 255, 0.18);
    border-radius: 18px;
    padding: 2rem 2rem 1.75rem;
    box-shadow:
        0 0 0 1px rgba(105, 108, 255, 0.06),
        0 24px 64px rgba(0, 0, 0, 0.55);
    animation: payFadeUp 0.4s cubic-bezier(0.34, 1.4, 0.64, 1) both;
}

@keyframes payFadeUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0);    }
}

/* ── Card header ─────────────────────────────────────────────── */
.pay-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
}

.pay-card-title {
    font-size: 1.1rem;
    font-weight: 800;
    color: #f0f4f8;
    letter-spacing: -0.01em;
}

.pay-status-badge {
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    padding: 0.25rem 0.65rem;
    border-radius: 100px;
    background: rgba(105, 108, 255, 0.12);
    border: 1px solid rgba(105, 108, 255, 0.28);
    color: #a5b4fc;
}

.pay-status-badge.paid {
    background: rgba(34, 197, 94, 0.12);
    border-color: rgba(34, 197, 94, 0.3);
    color: #4ade80;
}

/* ── Invoice meta ────────────────────────────────────────────── */
.pay-meta {
    background: rgba(105, 108, 255, 0.05);
    border: 1px solid rgba(105, 108, 255, 0.1);
    border-radius: 10px;
    padding: 0.85rem 1rem;
    margin-bottom: 1.25rem;
}

.pay-meta-row {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: 1rem;
    font-size: 0.83rem;
}

.pay-meta-row + .pay-meta-row {
    margin-top: 0.45rem;
    padding-top: 0.45rem;
    border-top: 1px solid rgba(105, 108, 255, 0.08);
}

.pay-meta-key {
    color: rgba(160, 190, 215, 0.5);
    font-weight: 500;
    flex-shrink: 0;
}

.pay-meta-val {
    font-weight: 700;
    color: #d4e4f7;
    text-align: right;
    word-break: break-word;
}

/* ── Amount breakdown ────────────────────────────────────────── */
.pay-amounts {
    margin-bottom: 1rem;
}

.pay-amt-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.45rem 0;
    font-size: 0.85rem;
    border-bottom: 1px solid rgba(105, 108, 255, 0.06);
}

.pay-amt-row:last-child { border-bottom: none; }

.pay-amt-key { color: rgba(160, 190, 215, 0.55); }

.pay-amt-val {
    font-weight: 600;
    color: #d4e4f7;
    font-variant-numeric: tabular-nums;
}

.pay-amt-row.balance {
    padding-top: 0.6rem;
    margin-top: 0.2rem;
    border-top: 1px dashed rgba(105, 108, 255, 0.2);
    border-bottom: none;
}

.pay-amt-row.balance .pay-amt-key {
    font-weight: 700;
    color: rgba(165, 180, 252, 0.8);
    font-size: 0.88rem;
}

.pay-amt-row.balance .pay-amt-val {
    color: #a5b4fc;
    font-size: 1rem;
    font-weight: 800;
}

/* ── Amount to pay box ───────────────────────────────────────── */
.pay-to-pay {
    background: rgba(105, 108, 255, 0.08);
    border: 1px solid rgba(105, 108, 255, 0.22);
    border-radius: 12px;
    padding: 1rem 1.25rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.25rem;
}

.pay-to-pay-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: rgba(165, 180, 252, 0.7);
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.pay-to-pay-amount {
    font-size: 1.7rem;
    font-weight: 800;
    color: #f0f4f8;
    letter-spacing: -0.02em;
    font-variant-numeric: tabular-nums;
}

/* ── Alert ───────────────────────────────────────────────────── */
.pay-alert {
    display: none;
    align-items: flex-start;
    gap: 0.5rem;
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.28);
    border-radius: 8px;
    padding: 0.7rem 0.9rem;
    font-size: 0.82rem;
    color: #fca5a5;
    line-height: 1.45;
    margin-bottom: 1rem;
}

.pay-alert.show { display: flex; }

/* ── Pay button ──────────────────────────────────────────────── */
.pay-btn {
    width: 100%;
    padding: 0.9rem 1.5rem;
    background: linear-gradient(135deg, #696cff 0%, #5254cc 100%);
    border: none;
    border-radius: 10px;
    color: #fff;
    font-size: 0.95rem;
    font-weight: 700;
    font-family: inherit;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    letter-spacing: 0.01em;
    box-shadow: 0 4px 24px rgba(105, 108, 255, 0.35);
    transition: all 0.2s;
    margin-bottom: 0.75rem;
}

.pay-btn:hover:not(:disabled) {
    background: linear-gradient(135deg, #7b7fff 0%, #696cff 100%);
    box-shadow: 0 6px 30px rgba(105, 108, 255, 0.5);
    transform: translateY(-1px);
}

.pay-btn:active:not(:disabled) { transform: translateY(0); }

.pay-btn:disabled {
    opacity: 0.55;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

/* ── Spinner ─────────────────────────────────────────────────── */
.pay-spinner {
    display: inline-block;
    width: 16px; height: 16px;
    border: 2px solid rgba(255,255,255,0.25);
    border-top-color: #fff;
    border-radius: 50%;
    animation: paySpin 0.65s linear infinite;
}
@keyframes paySpin { to { transform: rotate(360deg); } }

/* ── Footer note ─────────────────────────────────────────────── */
.pay-footer-note {
    text-align: center;
    font-size: 0.73rem;
    color: rgba(160, 190, 215, 0.28);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
}

/* ── Success state ───────────────────────────────────────────── */
.pay-success {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 0.5rem 0;
    gap: 0.85rem;
}

.pay-success-icon {
    width: 64px; height: 64px;
    background: rgba(34, 197, 94, 0.1);
    border: 1.5px solid rgba(34, 197, 94, 0.35);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.8rem;
    color: #4ade80;
    animation: payPop 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) both;
}

@keyframes payPop {
    from { transform: scale(0.5); opacity: 0; }
    to   { transform: scale(1);   opacity: 1; }
}

.pay-success-title {
    font-size: 1.2rem;
    font-weight: 800;
    color: #f0f4f8;
}

.pay-success-msg {
    font-size: 0.85rem;
    color: rgba(160, 190, 215, 0.6);
    line-height: 1.6;
    max-width: 300px;
}

.pay-receipt-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.7rem 1.5rem;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    border-radius: 10px;
    color: #fff;
    font-size: 0.88rem;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.2s;
    box-shadow: 0 4px 18px rgba(34, 197, 94, 0.3);
    border: none;
    cursor: pointer;
    font-family: inherit;
}

.pay-receipt-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 24px rgba(34, 197, 94, 0.45);
}

/* ── Already-paid state ──────────────────────────────────────── */
.pay-already-paid {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 0.85rem;
    padding: 0.5rem 0;
}

/* ── No Razorpay warning ─────────────────────────────────────── */
.pay-no-rzp {
    background: rgba(245, 158, 11, 0.08);
    border: 1px solid rgba(245, 158, 11, 0.25);
    border-radius: 8px;
    padding: 0.75rem 1rem;
    font-size: 0.82rem;
    color: rgba(253, 230, 138, 0.85);
    text-align: center;
    line-height: 1.5;
    margin-bottom: 0;
}

/* ── Responsive ──────────────────────────────────────────────── */
@media (max-width: 480px) {
    .pay-topbar { padding: 0.85rem 1.25rem; }
    .pay-card { padding: 1.5rem 1.25rem 1.35rem; border-radius: 14px; }
    .pay-to-pay-amount { font-size: 1.45rem; }
    .pay-secure-badge { display: none; }
}
</style>
</head>
<body>

<?php
$e       = fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$fmtAmt  = fn(float $v): string  => '&#8377;' . number_format($v, 2);
$orgName = $e($org->BrandName ?? $org->Name ?? 'Invoice Payment');
$initial = strtoupper(mb_substr(strip_tags($org->BrandName ?? $org->Name ?? 'P'), 0, 1));
?>

<!-- Top bar -->
<div class="pay-topbar">
    <div class="pay-org">
        <?php if (!empty($org->Logo)): ?>
        <img src="<?php echo $e($org->Logo); ?>" class="pay-org-logo" alt="Logo">
        <?php else: ?>
        <div class="pay-org-initial"><?php echo $initial; ?></div>
        <?php endif; ?>
        <span class="pay-org-name"><?php echo $orgName; ?></span>
    </div>
    <div class="pay-secure-badge">
        <span class="pay-secure-dot"></span>
        SSL Secured
    </div>
</div>

<!-- Body -->
<div class="pay-body">
    <div class="pay-card">

        <?php if ($isFullyPaid): ?>
        <!-- ── Already fully paid ───────────────────────────────── -->
        <div class="pay-already-paid">
            <div class="pay-success-icon">&#10003;</div>
            <div class="pay-success-title">Invoice Fully Paid</div>
            <p class="pay-success-msg">
                Invoice <strong><?php echo $e($stub->UniqueNumber ?? ''); ?></strong> has already been
                paid in full. No further payment is needed.
            </p>
        </div>

        <?php else: ?>
        <!-- ── Payment form ─────────────────────────────────────── -->
        <div id="payMain">

            <div class="pay-card-header">
                <div class="pay-card-title">Invoice Payment</div>
                <div class="pay-status-badge">
                    <?php echo $e($stub->DocStatus ?? 'Issued'); ?>
                </div>
            </div>

            <!-- Invoice meta -->
            <div class="pay-meta">
                <div class="pay-meta-row">
                    <span class="pay-meta-key">Invoice No</span>
                    <span class="pay-meta-val"><?php echo $e($stub->UniqueNumber ?? '—'); ?></span>
                </div>
                <div class="pay-meta-row">
                    <span class="pay-meta-key">Customer</span>
                    <span class="pay-meta-val"><?php echo $e($custInfo->PartyName ?? '—'); ?></span>
                </div>
            </div>

            <!-- Amount breakdown -->
            <div class="pay-amounts">
                <div class="pay-amt-row">
                    <span class="pay-amt-key">Invoice Amount</span>
                    <span class="pay-amt-val"><?php echo $fmtAmt($netAmount); ?></span>
                </div>
                <?php if ($alreadyPaid > 0): ?>
                <div class="pay-amt-row">
                    <span class="pay-amt-key">Already Paid</span>
                    <span class="pay-amt-val" style="color:#4ade80;"><?php echo $fmtAmt($alreadyPaid); ?></span>
                </div>
                <?php endif; ?>
                <div class="pay-amt-row balance">
                    <span class="pay-amt-key">Balance Due</span>
                    <span class="pay-amt-val"><?php echo $fmtAmt($balance); ?></span>
                </div>
            </div>

            <!-- Amount to pay -->
            <div class="pay-to-pay">
                <span class="pay-to-pay-label">Amount to Pay</span>
                <span class="pay-to-pay-amount"><?php echo $fmtAmt($balance); ?></span>
            </div>

            <!-- Alert -->
            <div class="pay-alert" id="payAlert">
                <span style="flex-shrink:0;font-size:0.95rem;">&#9888;</span>
                <span id="payAlertText"></span>
            </div>

            <?php if ($rzpEnabled): ?>
            <!-- Pay button -->
            <button type="button" class="pay-btn" id="payBtn" onclick="payInitiate()">
                <span id="payBtnLabel">Pay <?php echo $fmtAmt($balance); ?></span>
                <span id="payBtnSpinner" hidden><span class="pay-spinner"></span></span>
            </button>
            <?php else: ?>
            <div class="pay-no-rzp">
                &#9888;&nbsp; Online payment is not currently enabled. Please contact
                <strong><?php echo $orgName; ?></strong> directly to settle this invoice.
            </div>
            <?php endif; ?>

            <div class="pay-footer-note">
                <span>&#128274;</span> Payments are processed securely via Razorpay
            </div>

        </div><!-- /#payMain -->

        <!-- Success state (shown after payment) -->
        <div id="paySuccess" class="pay-success" hidden>
            <div class="pay-success-icon">&#10003;</div>
            <div class="pay-success-title">Payment Successful!</div>
            <p class="pay-success-msg">
                Your payment for invoice <strong><?php echo $e($stub->UniqueNumber ?? ''); ?></strong>
                has been verified and recorded.
            </p>
            <button type="button" class="pay-receipt-btn" id="payReceiptBtn" onclick="payOpenReceipt()">
                &#128196; View Receipt
            </button>
        </div>

        <?php endif; /* end !$isFullyPaid */ ?>

    </div><!-- /.pay-card -->
</div><!-- /.pay-body -->

<script src="<?php echo base_url('js/common/global-overlay.js'); ?>"></script>

<?php if ($rzpEnabled && !$isFullyPaid): ?>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
(function () {

    var _token      = '<?php echo $e($token); ?>';
    var _orgName    = <?php echo json_encode($org->BrandName ?? $org->Name ?? 'Invoice Payment'); ?>;
    var _custName   = <?php echo json_encode($custInfo->PartyName    ?? ''); ?>;
    var _custMobile = <?php echo json_encode($custInfo->MobileNumber ?? ''); ?>;
    var _custEmail  = <?php echo json_encode($custInfo->EmailAddress ?? ''); ?>;
    var _receiptUrl = null;

    /* ── Alert helpers ──────────────────────────────────────── */
    /** @param {string} msg @returns {void} */
    function payShowAlert(msg) {
        document.getElementById('payAlertText').textContent = msg;
        document.getElementById('payAlert').classList.add('show');
    }
    /** @returns {void} */
    function payHideAlert() {
        document.getElementById('payAlert').classList.remove('show');
    }

    /** @param {boolean} loading @returns {void} */
    function paySetLoading(loading) {
        var btn     = document.getElementById('payBtn');
        var label   = document.getElementById('payBtnLabel');
        var spinner = document.getElementById('payBtnSpinner');
        btn.disabled    = loading;
        label.hidden    = loading;
        spinner.hidden  = !loading;
    }

    /* ── Initiate payment ───────────────────────────────────── */
    /** @returns {void} */
    window.payInitiate = function () {
        payHideAlert();
        paySetLoading(true);
        showUIBlock('Preparing payment…');

        fetch('<?php echo base_url('razorpay/createOrder/'); ?>' + _token, {
            method: 'POST',
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            hideUIBlock();
            if (data.Error) {
                paySetLoading(false);
                payShowAlert(data.Message || 'Unable to initiate payment. Please try again.');
                return;
            }

            var options = {
                key:         data.key_id,
                amount:      data.amount,
                currency:    data.currency || 'INR',
                name:        _orgName,
                description: data.description || ('Invoice ' + _token),
                order_id:    data.order_id,
                prefill: {
                    name:    _custName,
                    contact: _custMobile,
                    email:   _custEmail,
                },
                theme: { color: '#696cff' },
                modal: {
                    ondismiss: function () {
                        paySetLoading(false);
                        payShowAlert('Payment was cancelled. Click the button above to try again.');
                    }
                },
                handler: function (response) {
                    _payVerify(response, data.order_id);
                }
            };

            var rzp = new Razorpay(options);
            rzp.on('payment.failed', function (resp) {
                paySetLoading(false);
                payShowAlert(resp.error.description || 'Payment failed. Please try again.');
            });
            rzp.open();
        })
        .catch(function () {
            hideUIBlock();
            paySetLoading(false);
            payShowAlert('A network error occurred. Please check your connection and try again.');
        });
    };

    /* ── Verify and record ──────────────────────────────────── */
    /**
     * @param {object} response
     * @param {string} orderId
     * @returns {void}
     */
    function _payVerify(response, orderId) {
        showUIBlock('Verifying payment…');

        var body = new URLSearchParams({
            razorpay_order_id:   orderId,
            razorpay_payment_id: response.razorpay_payment_id,
            razorpay_signature:  response.razorpay_signature,
        });

        fetch('<?php echo base_url('razorpay/verifyAndRecord/'); ?>' + _token, {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    body.toString(),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.Error) {
                hideUIBlock();
                paySetLoading(false);
                payShowAlert(
                    (data.Message || 'Verification failed.') +
                    ' If money was deducted, contact support with ID: ' +
                    (response.razorpay_payment_id || '')
                );
                return;
            }
            _receiptUrl = data.ReceiptUrl || null;
            hideUIBlock();
            document.getElementById('payMain').hidden    = true;
            document.getElementById('paySuccess').hidden = false;
        })
        .catch(function () {
            hideUIBlock();
            paySetLoading(false);
            payShowAlert(
                'Verification request failed. If money was deducted, please contact support ' +
                'with payment ID: ' + (response.razorpay_payment_id || '')
            );
        });
    }

    /* ── Open receipt ───────────────────────────────────────── */
    /** @returns {void} */
    window.payOpenReceipt = function () {
        if (_receiptUrl) {
            showUIBlock('Opening receipt…');
            window.location.href = _receiptUrl;
        }
    };

}());
</script>
<?php endif; ?>

</body>
</html>
