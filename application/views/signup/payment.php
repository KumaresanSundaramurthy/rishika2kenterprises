<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

.sp-root {
    min-height: 100vh;
    background: #040b18;
    font-family: 'Public Sans', sans-serif;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem 1rem;
}

.sp-card {
    width: 100%;
    max-width: 480px;
    background: rgba(14, 25, 48, 0.92);
    border: 1px solid rgba(96, 165, 200, 0.18);
    border-radius: 16px;
    padding: 2.5rem 2.25rem;
    box-shadow: 0 24px 64px rgba(0,0,0,0.5);
}

.sp-logo {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    margin-bottom: 2rem;
}

.sp-logo img { height: 34px; width: auto; }

.sp-logo span {
    font-size: 1rem;
    font-weight: 600;
    color: #e2e8f0;
    letter-spacing: 0.02em;
}

.sp-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    background: rgba(34, 197, 94, 0.12);
    border: 1px solid rgba(34, 197, 94, 0.25);
    color: #4ade80;
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    padding: 0.3rem 0.7rem;
    border-radius: 100px;
    margin-bottom: 1.25rem;
}

.sp-title {
    font-size: 1.55rem;
    font-weight: 700;
    color: #f0f4f8;
    line-height: 1.25;
    margin-bottom: 0.45rem;
}

.sp-sub {
    font-size: 0.875rem;
    color: rgba(180, 200, 220, 0.6);
    margin-bottom: 2rem;
    line-height: 1.6;
}

/* Plan summary box */
.sp-plan-box {
    background: rgba(96, 165, 200, 0.07);
    border: 1px solid rgba(96, 165, 200, 0.2);
    border-radius: 12px;
    padding: 1.25rem 1.4rem;
    margin-bottom: 1.75rem;
}

.sp-plan-name {
    font-size: 1rem;
    font-weight: 700;
    color: #c8e4f4;
    margin-bottom: 0.35rem;
}

.sp-plan-cycle {
    font-size: 0.78rem;
    color: rgba(160, 200, 220, 0.55);
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin-bottom: 0.85rem;
}

.sp-plan-price {
    display: flex;
    align-items: baseline;
    gap: 0.3rem;
}

.sp-plan-price .sp-currency { font-size: 1.1rem; color: #60a5c8; font-weight: 600; }
.sp-plan-price .sp-amount { font-size: 2rem; font-weight: 700; color: #f0f4f8; line-height: 1; }
.sp-plan-price .sp-period { font-size: 0.8rem; color: rgba(160, 200, 220, 0.55); }

/* Pay button */
.sp-pay-btn {
    width: 100%;
    padding: 0.85rem 1.5rem;
    background: linear-gradient(135deg, #2870a0 0%, #1e5a82 100%);
    border: 1px solid rgba(96, 165, 200, 0.4);
    border-radius: 10px;
    color: #fff;
    font-size: 0.92rem;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.55rem;
    margin-bottom: 1rem;
}

.sp-pay-btn:hover:not(:disabled) {
    background: linear-gradient(135deg, #3080b2 0%, #2870a0 100%);
    border-color: rgba(96, 165, 200, 0.6);
}

.sp-pay-btn:disabled { opacity: 0.6; cursor: not-allowed; }

.sp-logout-link {
    display: block;
    text-align: center;
    font-size: 0.8rem;
    color: rgba(160, 190, 215, 0.45);
    text-decoration: none;
    transition: color 0.2s;
}

.sp-logout-link:hover { color: rgba(160, 190, 215, 0.75); }

/* Change plan link */
.sp-change-link {
    display: block;
    text-align: right;
    font-size: 0.78rem;
    color: rgba(96, 165, 200, 0.6);
    text-decoration: none;
    margin-top: -1.25rem;
    margin-bottom: 1.5rem;
    cursor: pointer;
    transition: color 0.2s;
    background: none;
    border: none;
    padding: 0;
    font-family: inherit;
}
.sp-change-link:hover { color: rgba(96, 165, 200, 1); }

/* Plan picker panel */
.sp-plan-picker {
    display: none;
    flex-direction: column;
    gap: 0.6rem;
    margin-bottom: 1.5rem;
}
.sp-plan-picker.show { display: flex; }

.sp-plan-option {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: rgba(96, 165, 200, 0.05);
    border: 1px solid rgba(96, 165, 200, 0.15);
    border-radius: 10px;
    padding: 0.75rem 1rem;
    cursor: pointer;
    transition: all 0.18s;
}
.sp-plan-option:hover { background: rgba(96, 165, 200, 0.12); border-color: rgba(96, 165, 200, 0.35); }
.sp-plan-option.selected {
    border-color: rgba(96, 165, 200, 0.55);
    background: rgba(96, 165, 200, 0.14);
}

.sp-po-name {
    font-size: 0.88rem;
    font-weight: 600;
    color: #c8e4f4;
    margin-bottom: 0.15rem;
}
.sp-po-cycle {
    font-size: 0.72rem;
    color: rgba(160, 200, 220, 0.5);
    text-transform: uppercase;
    letter-spacing: 0.06em;
}
.sp-po-price {
    font-size: 1.05rem;
    font-weight: 700;
    color: #f0f4f8;
    white-space: nowrap;
}
.sp-po-free { color: #4ade80; font-size: 0.85rem; }

.sp-picker-spinner { display: none; }
.sp-picker-spinner.show { display: block; text-align: center; padding: 0.5rem 0; }

/* Alert */
.sp-alert {
    background: rgba(239,68,68,0.1);
    border: 1px solid rgba(239,68,68,0.35);
    border-radius: 8px;
    color: rgba(252,165,165,0.9);
    font-size: 0.83rem;
    padding: 0.65rem 0.9rem;
    margin-bottom: 1rem;
    display: none;
    align-items: flex-start;
    gap: 0.5rem;
}
.sp-alert.show { display: flex; }

/* Success overlay */
.sp-success {
    display: none;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 2rem 0;
}
.sp-success.show { display: flex; }

.sp-success-icon {
    width: 60px; height: 60px;
    background: rgba(34, 197, 94, 0.12);
    border: 1px solid rgba(34, 197, 94, 0.3);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: #4ade80;
    margin-bottom: 1.25rem;
}

.sp-success-title {
    font-size: 1.35rem;
    font-weight: 700;
    color: #f0f4f8;
    margin-bottom: 0.5rem;
}

.sp-success-msg {
    font-size: 0.875rem;
    color: rgba(180, 200, 220, 0.65);
    line-height: 1.65;
    margin-bottom: 1.75rem;
}

.sp-continue-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.75rem;
    background: linear-gradient(135deg, #2870a0 0%, #1e5a82 100%);
    border: 1px solid rgba(96, 165, 200, 0.4);
    border-radius: 10px;
    color: #fff;
    font-size: 0.9rem;
    font-weight: 600;
    text-decoration: none;
    font-family: inherit;
    cursor: pointer;
    transition: all 0.2s;
}
.sp-continue-btn:hover {
    background: linear-gradient(135deg, #3080b2 0%, #2870a0 100%);
    color: #fff;
    text-decoration: none;
}

/* Loading spinner */
.sp-spinner { display: inline-block; width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.25); border-top-color: #fff; border-radius: 50%; animation: sp-spin 0.6s linear infinite; }
@keyframes sp-spin { to { transform: rotate(360deg); } }

@media (max-width: 520px) {
    .sp-card { padding: 1.75rem 1.25rem; }
}
</style>

<div class="sp-root">
    <div class="sp-card">
        <div class="sp-logo">
            <img src="/images/logo/favicon_io/android-chrome-512x512-1.png" alt="Logo">
            <span><?php echo htmlspecialchars(getSiteConfiguration()->ShortName ?? 'App'); ?></span>
        </div>

        <!-- Main content (hidden once paid) -->
        <div id="spMainContent">
            <div class="sp-badge"><i class="bx bx-lock-alt"></i> Secure Payment</div>
            <h1 class="sp-title">Complete your subscription</h1>
            <p class="sp-sub">
                Your account is ready! Activate your plan to get started.
            </p>

            <!-- Plan summary -->
            <div class="sp-plan-box">
                <div class="sp-plan-name"><?php echo htmlspecialchars($sub->PlanName ?? 'Subscription Plan'); ?></div>
                <div class="sp-plan-cycle"><?php echo htmlspecialchars($sub->BillingCycle ?? ''); ?></div>
                <div class="sp-plan-price">
                    <span class="sp-currency">₹</span>
                    <span class="sp-amount"><?php echo number_format((float)($sub->Price ?? 0), 0); ?></span>
                    <span class="sp-period">/ <?php echo strtolower($sub->BillingCycle ?? 'plan'); ?></span>
                </div>
            </div>

            <!-- Change plan link -->
            <?php if (!empty($plans) && count($plans) > 1): ?>
            <button type="button" class="sp-change-link" id="spChangePlanToggle" onclick="spTogglePicker()">
                Change plan &rsaquo;
            </button>

            <!-- Plan picker -->
            <div class="sp-plan-picker" id="spPlanPicker">
                <?php foreach ($plans as $p): ?>
                <div class="sp-plan-option<?php echo ((int)$p->SectorPlanUID === (int)$sub->SectorPlanUID) ? ' selected' : ''; ?>"
                     onclick="spSelectPlan(<?php echo (int)$p->SectorPlanUID; ?>, this)">
                    <div>
                        <div class="sp-po-name"><?php echo htmlspecialchars($p->PlanName); ?></div>
                        <div class="sp-po-cycle"><?php echo htmlspecialchars($p->BillingCycle ?? ''); ?></div>
                    </div>
                    <?php if ((float)$p->Price > 0): ?>
                    <div class="sp-po-price">&#8377;<?php echo number_format((float)$p->Price, 0); ?></div>
                    <?php else: ?>
                    <div class="sp-po-free">Free</div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <div class="sp-picker-spinner" id="spPickerSpinner">
                    <span class="sp-spinner"></span>
                </div>
            </div>
            <?php endif; ?>

            <!-- Alert -->
            <div class="sp-alert" id="spAlert">
                <i class="bx bx-error-circle" style="flex-shrink:0;margin-top:1px;"></i>
                <span id="spAlertText"></span>
            </div>

            <button type="button" class="sp-pay-btn" id="spPayBtn" onclick="spInitiatePayment()">
                <i class="bx bx-credit-card"></i>
                <span id="spPayLabel">Pay ₹<?php echo number_format((float)($sub->Price ?? 0), 0); ?></span>
                <span id="spPaySpinner" style="display:none;"><span class="sp-spinner"></span></span>
            </button>

            <a href="/logout" class="sp-logout-link">Sign out and return later</a>
        </div>

        <!-- Success screen -->
        <div class="sp-success" id="spSuccess">
            <div class="sp-success-icon"><i class="bx bx-check"></i></div>
            <h2 class="sp-success-title">Payment successful!</h2>
            <p class="sp-success-msg">Your subscription is now active. You're all set.</p>
            <button type="button" class="sp-continue-btn" id="spContinueBtn" onclick="spContinue()">
                Continue <i class="bx bx-right-arrow-alt"></i>
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    var _sectorPlanUID = <?php echo (int)($sub->SectorPlanUID ?? 0); ?>;
    var _planPrice     = <?php echo (float)($sub->Price ?? 0); ?>;
    var _orgName       = <?php echo json_encode($orgName); ?>;
    var _orgEmail      = <?php echo json_encode($orgEmail); ?>;
    var _redirectUrl   = null;

    /**
     * @param {string} msg
     */
    function spShowAlert(msg) {
        document.getElementById('spAlertText').textContent = msg;
        document.getElementById('spAlert').classList.add('show');
    }

    function spHideAlert() {
        document.getElementById('spAlert').classList.remove('show');
    }

    /**
     * @param {boolean} loading
     */
    function spSetLoading(loading) {
        var btn     = document.getElementById('spPayBtn');
        var label   = document.getElementById('spPayLabel');
        var spinner = document.getElementById('spPaySpinner');
        btn.disabled    = loading;
        label.style.display   = loading ? 'none' : '';
        spinner.style.display = loading ? '' : 'none';
    }

    /** @returns {void} */
    window.spTogglePicker = function () {
        var picker = document.getElementById('spPlanPicker');
        var toggle = document.getElementById('spChangePlanToggle');
        if (!picker) return;
        var isOpen = picker.classList.contains('show');
        if (isOpen) {
            picker.classList.remove('show');
            if (toggle) toggle.textContent = 'Change plan ›';
        } else {
            picker.classList.add('show');
            if (toggle) toggle.textContent = 'Hide plans ›';
        }
    };

    /**
     * Called when user clicks a plan option in the picker.
     * @param {number} planUID
     * @param {HTMLElement} el
     * @returns {void}
     */
    window.spSelectPlan = function (planUID, el) {
        if (planUID === _sectorPlanUID) {
            spTogglePicker();
            return;
        }

        /* Mark selected */
        var picker = document.getElementById('spPlanPicker');
        if (picker) {
            picker.querySelectorAll('.sp-plan-option').forEach(function (o) { o.classList.remove('selected'); });
        }
        if (el) el.classList.add('selected');

        /* Disable all options + show spinner */
        var spinner = document.getElementById('spPickerSpinner');
        if (picker) picker.querySelectorAll('.sp-plan-option').forEach(function (o) { o.style.pointerEvents = 'none'; });
        if (spinner) spinner.classList.add('show');
        spHideAlert();

        fetch('/signup/changePlan', {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    'sector_plan_uid=' + encodeURIComponent(planUID),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (spinner) spinner.classList.remove('show');
            if (picker) picker.querySelectorAll('.sp-plan-option').forEach(function (o) { o.style.pointerEvents = ''; });

            if (data.Error) {
                spShowAlert(data.Message || 'Could not change plan. Please try again.');
                return;
            }

            /* Update stored plan UID + price */
            _sectorPlanUID = data.SectorPlanUID;
            _planPrice     = data.Price;

            /* Update plan box display */
            var nameEl  = document.querySelector('.sp-plan-name');
            var cycleEl = document.querySelector('.sp-plan-cycle');
            var amtEl   = document.querySelector('.sp-amount');
            if (nameEl)  nameEl.textContent  = data.PlanName    || '';
            if (cycleEl) cycleEl.textContent = data.BillingCycle || '';
            if (amtEl)   amtEl.textContent   = data.Price > 0 ? Math.round(data.Price).toLocaleString('en-IN') : '0';

            /* Update pay button label */
            var payLabel = document.getElementById('spPayLabel');
            if (payLabel) payLabel.textContent = data.Price > 0
                ? 'Pay ₹' + Math.round(data.Price).toLocaleString('en-IN')
                : 'Activate Free Plan';

            /* Hide/show pay button for free plan */
            var payBtn = document.getElementById('spPayBtn');
            if (payBtn) payBtn.onclick = data.IsFree ? spActivateFree : spInitiatePayment;

            spTogglePicker();

            /* Free plan — redirect immediately */
            if (data.IsFree && data.Redirect) {
                window.location.href = data.Redirect;
            }
        })
        .catch(function () {
            if (spinner) spinner.classList.remove('show');
            if (picker) picker.querySelectorAll('.sp-plan-option').forEach(function (o) { o.style.pointerEvents = ''; });
            spShowAlert('A network error occurred. Please try again.');
        });
    };

    /** @returns {void} */
    window.spActivateFree = function () {
        /* Should not be reached — redirect happens in spSelectPlan; safety fallback */
        window.location.href = '/dashboard';
    };

    /**
     * Calls server to create Razorpay order, then opens the Razorpay checkout.
     * @returns {void}
     */
    window.spInitiatePayment = function () {
        spHideAlert();
        spSetLoading(true);

        fetch('/signup/createOrder', {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    'sector_plan_uid=' + encodeURIComponent(_sectorPlanUID),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.Error) {
                spSetLoading(false);
                spShowAlert(data.Message || 'Unable to initiate payment. Please try again.');
                return;
            }

            var options = {
                key:         data.key_id,
                amount:      data.amount,
                currency:    data.currency || 'INR',
                name:        data.name,
                description: data.description,
                order_id:    data.order_id,
                prefill:     data.prefill || {},
                theme:       { color: '#2870a0' },
                modal: {
                    ondismiss: function () {
                        spSetLoading(false);
                        spShowAlert('Payment was cancelled. Click the button above to try again.');
                    }
                },
                handler: function (response) {
                    spVerifyPayment(response, data.order_id);
                }
            };

            var rzp = new Razorpay(options);
            rzp.on('payment.failed', function (resp) {
                spSetLoading(false);
                spShowAlert(resp.error.description || 'Payment failed. Please try again.');
            });
            rzp.open();
        })
        .catch(function () {
            spSetLoading(false);
            spShowAlert('A network error occurred. Please check your connection and try again.');
        });
    };

    /**
     * @param {object} response  Razorpay success response
     * @param {string} orderId
     * @returns {void}
     */
    function spVerifyPayment(response, orderId) {
        var body = new URLSearchParams({
            sector_plan_uid:      _sectorPlanUID,
            razorpay_order_id:    orderId,
            razorpay_payment_id:  response.razorpay_payment_id,
            razorpay_signature:   response.razorpay_signature,
        });

        fetch('/signup/confirmPayment', {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    body.toString(),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            spSetLoading(false);
            if (data.Error) {
                spShowAlert(data.Message || 'Payment verification failed. Please contact support.');
                return;
            }
            _redirectUrl = data.Redirect || '/dashboard';
            document.getElementById('spMainContent').style.display = 'none';
            document.getElementById('spSuccess').classList.add('show');
        })
        .catch(function () {
            spSetLoading(false);
            spShowAlert('Verification failed. Please contact support with your payment ID: ' + (response.razorpay_payment_id || ''));
        });
    }

    /**
     * @returns {void}
     */
    window.spContinue = function () {
        window.location.href = _redirectUrl || '/dashboard';
    };
}());
</script>

<!-- Razorpay SDK -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
