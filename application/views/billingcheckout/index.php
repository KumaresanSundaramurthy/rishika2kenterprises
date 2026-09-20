<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

.spay-root {
    min-height: 100vh;
    background: #040b18;
    font-family: 'Public Sans', sans-serif;
    color: #e2e8f0;
    display: flex;
    flex-direction: column;
}

/* ── Top bar ──────────────────────────────────────────────────────── */
.spay-topbar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 1.1rem 2rem;
    border-bottom: 1px solid rgba(105, 108, 255, 0.12);
    background: rgba(4, 11, 24, 0.85);
    backdrop-filter: blur(8px);
    position: sticky; top: 0; z-index: 10;
}
.spay-logo { display: flex; align-items: center; gap: 0.6rem; }
.spay-logo img { height: 32px; width: auto; }
.spay-logo span { font-size: 0.95rem; font-weight: 700; color: #e2e8f0; letter-spacing: 0.02em; }
.spay-org-pill {
    display: flex; align-items: center; gap: 0.5rem;
    background: rgba(105, 108, 255, 0.1); border: 1px solid rgba(105, 108, 255, 0.2);
    border-radius: 100px; padding: 0.35rem 0.85rem;
    font-size: 0.78rem; color: rgba(160, 165, 255, 0.9);
}
.spay-org-pill i { font-size: 0.85rem; }
.spay-logout-link {
    display: inline-flex; align-items: center; gap: 0.4rem;
    font-size: 0.78rem; color: rgba(220, 100, 100, 0.75); text-decoration: none;
    border: 1px solid rgba(220, 100, 100, 0.25); border-radius: 100px; padding: 0.35rem 0.85rem;
    transition: color 0.2s, border-color 0.2s, background 0.2s;
}
.spay-logout-link:hover { color: rgba(240,120,120,1); border-color: rgba(220,100,100,.55); background: rgba(220,100,100,.08); }

/* ── Body ──────────────────────────────────────────────────────────── */
.spay-body { flex: 1; display: flex; flex-direction: column; align-items: center; padding: 3rem 1.5rem 5rem; }

/* ── Card ──────────────────────────────────────────────────────────── */
.spay-card {
    width: 100%; max-width: 480px;
    background: rgba(14, 25, 48, 0.95); border: 1px solid rgba(105, 108, 255, 0.2);
    border-radius: 20px; padding: 2rem 2rem 1.75rem;
    box-shadow: 0 24px 72px rgba(0, 0, 0, 0.55);
}
.spay-plan-header {
    display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem;
}
.spay-change-link {
    display: inline-flex; align-items: center; gap: 0.35rem;
    flex-shrink: 0; margin-top: 0.1rem;
    font-size: 0.75rem; font-weight: 600;
    color: rgba(105, 108, 255, 0.85); text-decoration: none;
    border: 1px solid rgba(105, 108, 255, 0.28); border-radius: 6px;
    padding: 0.35rem 0.7rem; white-space: nowrap;
    transition: color 0.2s, background 0.2s, border-color 0.2s;
}
.spay-change-link:hover { color: #8a8dff; background: rgba(105, 108, 255, 0.1); border-color: rgba(105, 108, 255, 0.5); }
.spay-change-link i { font-size: 0.88rem; }

.spay-eyebrow {
    display: inline-flex; align-items: center; gap: 0.4rem;
    font-size: 0.7rem; font-weight: 700; letter-spacing: 0.12em;
    text-transform: uppercase; color: rgba(140, 143, 255, 0.9); margin-bottom: 0.65rem;
}
.spay-eyebrow i { font-size: 0.8rem; }
.spay-card-title { font-size: 1.5rem; font-weight: 800; color: #f0f4f8; letter-spacing: -0.01em; margin-bottom: 0.3rem; }
.spay-plan-meta { font-size: 0.8rem; color: rgba(160, 190, 215, 0.45); text-transform: uppercase; letter-spacing: 0.07em; margin-bottom: 2rem; }

/* ── Summary breakdown ─────────────────────────────────────────────── */
.spay-summary {
    background: rgba(105, 108, 255, 0.05); border: 1px solid rgba(105, 108, 255, 0.14);
    border-radius: 12px; padding: 1.15rem 1.2rem; margin-bottom: 1.5rem;
}
.spay-row { display: flex; justify-content: space-between; align-items: center; padding: 0.4rem 0; }
.spay-row-label { font-size: 0.85rem; color: rgba(160, 190, 215, 0.55); }
.spay-row-value { font-size: 0.85rem; color: rgba(200, 220, 240, 0.85); font-variant-numeric: tabular-nums; }
.spay-divider { border: none; border-top: 1px solid rgba(105, 108, 255, 0.12); margin: 0.6rem 0; }
.spay-total-row { display: flex; justify-content: space-between; align-items: baseline; padding-top: 0.2rem; }
.spay-total-label { font-size: 0.9rem; font-weight: 700; color: rgba(200, 220, 240, 0.85); }
.spay-total-amount { font-size: 2rem; font-weight: 800; color: #f0f4f8; letter-spacing: -0.03em; line-height: 1; }
.spay-total-amount sup { font-size: 1rem; font-weight: 600; color: rgba(160, 190, 215, 0.6); vertical-align: super; letter-spacing: 0; }

/* ── Trust row ─────────────────────────────────────────────────────── */
.spay-trust { display: flex; align-items: center; gap: 0.45rem; font-size: 0.75rem; color: rgba(160, 190, 215, 0.38); margin-bottom: 1.35rem; }
.spay-trust i { font-size: 0.88rem; color: rgba(105, 108, 255, 0.5); }

/* ── Pay button ────────────────────────────────────────────────────── */
.spay-btn {
    width: 100%; padding: 0.95rem 1.5rem;
    background: linear-gradient(135deg, #696cff 0%, #5254cc 100%);
    border: none; border-radius: 12px; color: #fff; font-size: 1rem; font-weight: 700;
    cursor: pointer; font-family: inherit; transition: all 0.2s;
    display: flex; align-items: center; justify-content: center; gap: 0.55rem;
    letter-spacing: 0.01em; box-shadow: 0 6px 28px rgba(105, 108, 255, 0.38); margin-bottom: 1rem;
}
.spay-btn:hover:not(:disabled) { background: linear-gradient(135deg, #7b7fff 0%, #696cff 100%); box-shadow: 0 8px 36px rgba(105,108,255,.55); transform: translateY(-1px); }
.spay-btn:disabled { opacity: 0.55; cursor: not-allowed; transform: none; box-shadow: none; }

/* ── Alert ─────────────────────────────────────────────────────────── */
.spay-alert {
    background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3);
    border-radius: 8px; color: rgba(252, 165, 165, 0.9); font-size: 0.82rem;
    padding: 0.7rem 0.9rem; margin-bottom: 1rem;
    display: none; align-items: flex-start; gap: 0.5rem; line-height: 1.45;
}
.spay-alert.show { display: flex; }

/* ── Success screen ────────────────────────────────────────────────── */
.spay-success { display: none; flex-direction: column; align-items: center; text-align: center; padding: 1.5rem 0; }
.spay-success.show { display: flex; }
.spay-success-icon {
    width: 60px; height: 60px; background: rgba(34, 197, 94, 0.12); border: 1px solid rgba(34, 197, 94, 0.3);
    border-radius: 50%; display: flex; align-items: center; justify-content: center;
    font-size: 1.75rem; color: #4ade80; margin-bottom: 1.1rem;
}
.spay-success-title { font-size: 1.2rem; font-weight: 800; color: #f0f4f8; margin-bottom: 0.4rem; }
.spay-success-msg { font-size: 0.85rem; color: rgba(180, 200, 220, 0.6); line-height: 1.6; margin-bottom: 1.5rem; }
.spay-continue-btn {
    display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.75rem;
    background: linear-gradient(135deg, #696cff 0%, #5254cc 100%);
    border-radius: 10px; color: #fff; font-size: 0.9rem; font-weight: 700;
    text-decoration: none; cursor: pointer; border: none; font-family: inherit; transition: all 0.2s;
    box-shadow: 0 4px 20px rgba(105, 108, 255, 0.35);
}
.spay-continue-btn:hover { background: linear-gradient(135deg, #7b7fff 0%, #696cff 100%); transform: translateY(-1px); }

/* ── Return later link ─────────────────────────────────────────────── */
.spay-return-link {
    display: block; text-align: center; font-size: 0.78rem;
    color: rgba(160, 190, 215, 0.35); text-decoration: none; transition: color 0.2s;
}
.spay-return-link:hover { color: rgba(160, 190, 215, 0.7); }

/* ── Nav links disabled during payment ────────────────────────────── */
.spay-link-disabled { pointer-events: none !important; opacity: 0.3; cursor: not-allowed; }

/* Spinner */
.spay-spinner { display: inline-block; width: 18px; height: 18px; border: 2px solid rgba(255,255,255,0.25); border-top-color: #fff; border-radius: 50%; animation: spay-spin 0.6s linear infinite; flex-shrink: 0; }
@keyframes spay-spin { to { transform: rotate(360deg); } }

/* ── Responsive ────────────────────────────────────────────────────── */
@media (max-width: 560px) {
    .spay-topbar { padding: 1rem 1.25rem; }
    .spay-body { padding: 2rem 1rem 4rem; }
    .spay-card { padding: 1.5rem 1.25rem 1.35rem; }
    .spay-card-title { font-size: 1.25rem; }
    .spay-total-amount { font-size: 1.65rem; }
}
</style>

<?php
$_flowLabel = ['signup' => 'Account Activation', 'renewal' => 'Subscription Renewal', 'upgrade' => 'Plan Upgrade'];
$_f         = $flow ?? 'signup';
$_total     = (float)($totalPrice ?? 0);
$_taxable   = (float)($taxableAmount ?? 0);
$_tax       = (float)($taxAmount ?? 0);
$_planName  = htmlspecialchars($plan->PlanName ?? '');
$_cycle     = htmlspecialchars($plan->BillingCycle ?? '');
$_days      = (int)($plan->DurationDays ?? 0);
$_planUID   = (int)($plan->SectorPlanUID ?? 0);
$_taxRate   = (int)($plan->TaxRate ?? 18);
$_backUrl   = htmlspecialchars($backUrl ?? '/subscribe', ENT_QUOTES, 'UTF-8');
?>

<div class="spay-root">

    <!-- Top bar -->
    <div class="spay-topbar">
        <div class="spay-logo">
            <img src="/images/logo/favicon_io/android-chrome-512x512-1.png" alt="Logo">
            <span><?php echo htmlspecialchars(getSiteConfiguration()->ShortName ?? 'App'); ?></span>
        </div>
        <div class="spay-org-pill">
            <i class="bx bx-buildings"></i>
            <?php echo htmlspecialchars($orgName ?? ''); ?>
        </div>
        <a href="<?= site_url('logout') ?>" class="spay-logout-link"><i class="bx bx-log-out"></i> Sign out</a>
    </div>

    <!-- Body -->
    <div class="spay-body">

        <div class="spay-card">

            <!-- Main content -->
            <div id="spayMain">
                <div class="spay-plan-header">
                    <div>
                        <div class="spay-eyebrow">
                            <i class="bx bx-receipt"></i>
                            <?php echo $_flowLabel[$_f] ?? 'Payment'; ?>
                        </div>
                        <div class="spay-card-title"><?php echo $_planName; ?></div>
                        <div class="spay-plan-meta"><?php echo $_cycle; ?> &middot; <?php echo $_days; ?> days</div>
                    </div>
                    <a href="<?= site_url('billing/checkout/abandon') ?>?t=<?php echo urlencode($token ?? ''); ?>" class="spay-change-link spay-abandon-link">
                        <i class="bx bx-left-arrow-alt"></i> Change Plan
                    </a>
                </div>

                <div class="spay-summary">
                    <div class="spay-row">
                        <span class="spay-row-label">Subscription fee</span>
                        <span class="spay-row-value">&#8377;<?php echo number_format($_taxable, 2); ?></span>
                    </div>
                    <div class="spay-row">
                        <span class="spay-row-label">GST <?php echo $_taxRate; ?>%</span>
                        <span class="spay-row-value">&#8377;<?php echo number_format($_tax, 2); ?></span>
                    </div>
                    <hr class="spay-divider">
                    <div class="spay-total-row">
                        <span class="spay-total-label">Total Payable</span>
                        <span class="spay-total-amount"><sup>&#8377;</sup><?php echo number_format($_total, 0); ?></span>
                    </div>
                </div>

                <div class="spay-trust">
                    <i class="bx bx-lock-alt"></i>
                    Payments are secured &amp; encrypted via Razorpay
                </div>

                <div class="spay-alert" id="spayAlert">
                    <i class="bx bx-error-circle" style="flex-shrink:0;margin-top:1px;font-size:1rem;"></i>
                    <span id="spayAlertText"></span>
                </div>

                <button type="button" class="spay-btn" id="spayPayBtn" onclick="spayConfirmAndPay()">
                    <i class="bx bx-credit-card" id="spayPayIcon"></i>
                    <span id="spayPayLabel">Confirm &amp; Pay &#8377;<?php echo number_format($_total, 0); ?></span>
                    <span id="spayPaySpinner" style="display:none;"><span class="spay-spinner"></span></span>
                </button>

                <a href="<?= site_url('billing/checkout/abandon') ?>?t=<?php echo urlencode($token ?? ''); ?>" class="spay-return-link spay-abandon-link">
                    Return later &amp; pay when ready
                </a>
            </div>

            <!-- Success screen -->
            <div class="spay-success" id="spaySuccess">
                <div class="spay-success-icon"><i class="bx bx-check"></i></div>
                <div class="spay-success-title">You're all set!</div>
                <p class="spay-success-msg">Your subscription is now active. Welcome aboard.</p>
                <button type="button" class="spay-continue-btn" onclick="spayContinue()">
                    Continue <i class="bx bx-right-arrow-alt"></i>
                </button>
            </div>

        </div>
    </div>
</div>

<script>
(function () {
    var _sectorPlanUID = <?php echo (int)$_planUID; ?>;
    var _payToken      = '<?php echo htmlspecialchars($token ?? '', ENT_QUOTES, 'UTF-8'); ?>';

    document.querySelectorAll('.spay-abandon-link').forEach(function (el) {
        el.addEventListener('click', function (e) {
            e.preventDefault();
            if (el.classList.contains('spay-link-disabled')) return;
            showUIBlock('Redirecting…');
            window.location.href = el.getAttribute('href');
        });
    });

    /** @param {string} msg @returns {void} */
    function spayShowAlert(msg) {
        document.getElementById('spayAlertText').textContent = msg;
        document.getElementById('spayAlert').classList.add('show');
    }
    function spayHideAlert() {
        document.getElementById('spayAlert').classList.remove('show');
    }

    /** @param {boolean} loading @returns {void} */
    function spaySetLoading(loading) {
        var btn     = document.getElementById('spayPayBtn');
        var label   = document.getElementById('spayPayLabel');
        var spinner = document.getElementById('spayPaySpinner');
        var icon    = document.getElementById('spayPayIcon');
        btn.disabled          = loading;
        label.style.display   = loading ? 'none' : '';
        icon.style.display    = loading ? 'none' : '';
        spinner.style.display = loading ? '' : 'none';

        document.querySelectorAll('.spay-change-link, .spay-logout-link').forEach(function (el) {
            el.classList.toggle('spay-link-disabled', loading);
        });
    }

    /** @returns {void} */
    window.spayConfirmAndPay = function () {
        spayHideAlert();
        spaySetLoading(true);
        showUIBlock('Preparing your payment…');

        fetch('/billing/checkout/createOrder', {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    'sector_plan_uid=' + encodeURIComponent(_sectorPlanUID),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.Error) {
                spaySetLoading(false);
                hideUIBlock();
                spayShowAlert(data.Message || 'Unable to initiate payment. Please try again.');
                return;
            }

            /* Hide overlay so Razorpay modal is visible */
            hideUIBlock();

            var options = {
                key:         data.key_id,
                amount:      data.amount,
                currency:    data.currency || 'INR',
                name:        data.name,
                description: data.description,
                order_id:    data.order_id,
                prefill:     data.prefill || {},
                theme:       { color: '#696cff' },
                modal: {
                    ondismiss: function () {
                        spaySetLoading(false);
                        spayShowAlert('Payment was cancelled. Click Confirm & Pay to try again.');
                    }
                },
                handler: function (response) {
                    showUIBlock('Verifying your payment…');
                    _spayVerifyPayment(response, data.order_id);
                }
            };

            var rzp = new Razorpay(options);
            rzp.on('payment.failed', function (resp) {
                spaySetLoading(false);
                spayShowAlert(resp.error.description || 'Payment failed. Please try again.');
            });
            rzp.open();
        })
        .catch(function () {
            spaySetLoading(false);
            hideUIBlock();
            spayShowAlert('A network error occurred. Please check your connection and try again.');
        });
    };

    /**
     * @param {object} response
     * @param {string} orderId
     * @returns {void}
     */
    function _spayVerifyPayment(response, orderId) {
        var body = new URLSearchParams({
            sector_plan_uid:     _sectorPlanUID,
            token:               _payToken,
            razorpay_order_id:   orderId,
            razorpay_payment_id: response.razorpay_payment_id,
            razorpay_signature:  response.razorpay_signature,
        });

        fetch('/billing/checkout/confirmPayment', {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    body.toString(),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            spaySetLoading(false);
            if (data.Error) {
                hideUIBlock();
                spayShowAlert(data.Message || 'Payment verification failed. Please contact support.');
                return;
            }
            showUIBlock('All done! Redirecting you to your workspace…');
            window.location.href = data.Redirect || '/dashboard';
        })
        .catch(function () {
            spaySetLoading(false);
            hideUIBlock();
            spayShowAlert('Verification failed. Contact support with payment ID: ' + (response.razorpay_payment_id || ''));
        });
    }
}());
</script>

<!-- Razorpay SDK -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
