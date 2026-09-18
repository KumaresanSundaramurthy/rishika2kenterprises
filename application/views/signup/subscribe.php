<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

/* ── Root ─────────────────────────────────────────────────────────── */
.sub-root {
    min-height: 100vh;
    background: #040b18;
    font-family: 'Public Sans', sans-serif;
    color: #e2e8f0;
    display: flex;
    flex-direction: column;
}

/* ── Top bar ──────────────────────────────────────────────────────── */
.sub-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.1rem 2rem;
    border-bottom: 1px solid rgba(105, 108, 255, 0.12);
    background: rgba(4, 11, 24, 0.85);
    backdrop-filter: blur(8px);
    position: sticky;
    top: 0;
    z-index: 10;
}

.sub-logo {
    display: flex;
    align-items: center;
    gap: 0.6rem;
}
.sub-logo img { height: 32px; width: auto; }
.sub-logo span {
    font-size: 0.95rem;
    font-weight: 700;
    color: #e2e8f0;
    letter-spacing: 0.02em;
}

.sub-org-pill {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(105, 108, 255, 0.1);
    border: 1px solid rgba(105, 108, 255, 0.2);
    border-radius: 100px;
    padding: 0.35rem 0.85rem;
    font-size: 0.78rem;
    color: rgba(160, 165, 255, 0.9);
}
.sub-org-pill i { font-size: 0.85rem; }

.sub-logout-link {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.78rem;
    color: rgba(220, 100, 100, 0.75);
    text-decoration: none;
    border: 1px solid rgba(220, 100, 100, 0.25);
    border-radius: 100px;
    padding: 0.35rem 0.85rem;
    transition: color 0.2s, border-color 0.2s, background 0.2s;
}
.sub-logout-link:hover {
    color: rgba(240, 120, 120, 1);
    border-color: rgba(220, 100, 100, 0.55);
    background: rgba(220, 100, 100, 0.08);
}

/* ── Page body ────────────────────────────────────────────────────── */
.sub-body {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 2.5rem 1.5rem 4rem;
}

.sub-heading-wrap {
    text-align: center;
    max-width: 540px;
    margin-bottom: 2.75rem;
}

.sub-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    background: rgba(105, 108, 255, 0.12);
    border: 1px solid rgba(105, 108, 255, 0.25);
    color: rgba(140, 143, 255, 1);
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    padding: 0.3rem 0.8rem;
    border-radius: 100px;
    margin-bottom: 1rem;
}

.sub-heading-wrap h1 {
    font-size: 1.95rem;
    font-weight: 800;
    color: #f0f4f8;
    line-height: 1.2;
    margin-bottom: 0.6rem;
    letter-spacing: -0.01em;
}

.sub-heading-wrap p {
    font-size: 0.9rem;
    color: rgba(160, 190, 215, 0.6);
    line-height: 1.7;
}

/* ── Main layout ──────────────────────────────────────────────────── */
.sub-layout {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 1.75rem;
    width: 100%;
    max-width: 980px;
    align-items: start;
}

/* ── Plan cards ───────────────────────────────────────────────────── */
.sub-plans-wrap h2 {
    font-size: 0.75rem;
    font-weight: 700;
    color: rgba(160, 190, 215, 0.45);
    letter-spacing: 0.1em;
    text-transform: uppercase;
    margin-bottom: 1rem;
}

.sub-plan-card {
    background: rgba(14, 25, 48, 0.9);
    border: 1px solid rgba(105, 108, 255, 0.14);
    border-radius: 14px;
    padding: 1.35rem 1.5rem;
    margin-bottom: 0.85rem;
    cursor: pointer;
    transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
    display: grid;
    grid-template-columns: auto 1fr auto;
    align-items: center;
    gap: 1.1rem;
    position: relative;
    overflow: hidden;
}

.sub-plan-card::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(105,108,255,0.04) 0%, transparent 60%);
    opacity: 0;
    transition: opacity 0.25s;
    pointer-events: none;
}

.sub-plan-card:hover {
    border-color: rgba(105, 108, 255, 0.35);
    background: rgba(14, 25, 58, 0.95);
}
.sub-plan-card:hover::before { opacity: 1; }

.sub-plan-card.selected {
    border-color: rgba(105, 108, 255, 0.7);
    background: rgba(14, 25, 58, 0.98);
    box-shadow: 0 0 0 1px rgba(105,108,255,0.3), 0 8px 32px rgba(105,108,255,0.15);
}
.sub-plan-card.selected::before { opacity: 1; }

/* Radio dot */
.sub-plan-radio {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 2px solid rgba(105, 108, 255, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: border-color 0.2s;
}
.sub-plan-radio::after {
    content: '';
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #696cff;
    opacity: 0;
    transition: opacity 0.2s;
}
.sub-plan-card.selected .sub-plan-radio {
    border-color: #696cff;
}
.sub-plan-card.selected .sub-plan-radio::after { opacity: 1; }

.sub-plan-info {}
.sub-plan-name {
    font-size: 1rem;
    font-weight: 700;
    color: #d4e4f7;
    margin-bottom: 0.25rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.sub-plan-badge {
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    padding: 0.2rem 0.55rem;
    border-radius: 100px;
    background: rgba(105, 108, 255, 0.15);
    border: 1px solid rgba(105, 108, 255, 0.3);
    color: #9a9dff;
}
.sub-plan-badge.free {
    background: rgba(34, 197, 94, 0.12);
    border-color: rgba(34, 197, 94, 0.3);
    color: #4ade80;
}

.sub-plan-cycle {
    font-size: 0.76rem;
    color: rgba(160, 190, 215, 0.45);
    text-transform: uppercase;
    letter-spacing: 0.06em;
}

.sub-plan-price-col { text-align: right; flex-shrink: 0; }
.sub-plan-price {
    font-size: 1.5rem;
    font-weight: 800;
    color: #f0f4f8;
    line-height: 1;
    letter-spacing: -0.02em;
}
.sub-plan-price sup {
    font-size: 0.85rem;
    font-weight: 600;
    color: rgba(160, 190, 215, 0.65);
    vertical-align: super;
    letter-spacing: 0;
}
.sub-plan-price.free-price { color: #4ade80; font-size: 1.15rem; }
.sub-plan-duration {
    font-size: 0.72rem;
    color: rgba(160, 190, 215, 0.4);
    margin-top: 0.2rem;
}

/* ── Payment panel ────────────────────────────────────────────────── */
.sub-pay-panel {
    background: rgba(14, 25, 48, 0.92);
    border: 1px solid rgba(105, 108, 255, 0.18);
    border-radius: 16px;
    padding: 1.75rem 1.5rem;
    position: sticky;
    top: 80px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.45);
}

.sub-pay-panel h2 {
    font-size: 0.75rem;
    font-weight: 700;
    color: rgba(160, 190, 215, 0.45);
    letter-spacing: 0.1em;
    text-transform: uppercase;
    margin-bottom: 1.25rem;
}

/* Selected plan summary */
.sub-summary-plan {
    background: rgba(105, 108, 255, 0.08);
    border: 1px solid rgba(105, 108, 255, 0.18);
    border-radius: 10px;
    padding: 1rem 1.1rem;
    margin-bottom: 1.5rem;
}

.sub-summary-name {
    font-size: 1.05rem;
    font-weight: 700;
    color: #d4e4f7;
    margin-bottom: 0.2rem;
}

.sub-summary-cycle {
    font-size: 0.75rem;
    color: rgba(160, 190, 215, 0.45);
    text-transform: uppercase;
    letter-spacing: 0.07em;
    margin-bottom: 0.9rem;
}

.sub-summary-amount {
    display: flex;
    align-items: baseline;
    gap: 0.25rem;
}
.sub-summary-amount .currency { font-size: 1rem; color: rgba(105, 108, 255, 0.8); font-weight: 600; }
.sub-summary-amount .amount   { font-size: 2.2rem; font-weight: 800; color: #f0f4f8; line-height: 1; letter-spacing: -0.03em; }
.sub-summary-amount .period   { font-size: 0.8rem; color: rgba(160, 190, 215, 0.45); }

/* Divider */
.sub-divider {
    border: none;
    border-top: 1px solid rgba(105, 108, 255, 0.1);
    margin: 1.25rem 0;
}

/* Trust row */
.sub-trust-row {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    font-size: 0.75rem;
    color: rgba(160, 190, 215, 0.4);
    margin-bottom: 1.25rem;
}
.sub-trust-row i { font-size: 0.85rem; color: rgba(105, 108, 255, 0.5); }

/* Pay button */
.sub-pay-btn {
    width: 100%;
    padding: 0.9rem 1.5rem;
    background: linear-gradient(135deg, #696cff 0%, #5254cc 100%);
    border: none;
    border-radius: 10px;
    color: #fff;
    font-size: 0.95rem;
    font-weight: 700;
    cursor: pointer;
    font-family: inherit;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    letter-spacing: 0.01em;
    box-shadow: 0 4px 24px rgba(105, 108, 255, 0.35);
    margin-bottom: 0.85rem;
}
.sub-pay-btn:hover:not(:disabled) {
    background: linear-gradient(135deg, #7b7fff 0%, #696cff 100%);
    box-shadow: 0 6px 30px rgba(105, 108, 255, 0.5);
    transform: translateY(-1px);
}
.sub-pay-btn:disabled { opacity: 0.55; cursor: not-allowed; transform: none; box-shadow: none; }

.sub-pay-btn.free-btn {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    box-shadow: 0 4px 24px rgba(34, 197, 94, 0.3);
}
.sub-pay-btn.free-btn:hover:not(:disabled) {
    background: linear-gradient(135deg, #34d870 0%, #22c55e 100%);
    box-shadow: 0 6px 30px rgba(34, 197, 94, 0.45);
}

/* Alert */
.sub-alert {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    border-radius: 8px;
    color: rgba(252, 165, 165, 0.9);
    font-size: 0.82rem;
    padding: 0.65rem 0.85rem;
    margin-bottom: 0.85rem;
    display: none;
    align-items: flex-start;
    gap: 0.5rem;
    line-height: 1.45;
}
.sub-alert.show { display: flex; }

/* Success overlay */
.sub-success {
    display: none;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 1.5rem 0;
}
.sub-success.show { display: flex; }

.sub-success-icon {
    width: 56px; height: 56px;
    background: rgba(34, 197, 94, 0.12);
    border: 1px solid rgba(34, 197, 94, 0.3);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.6rem;
    color: #4ade80;
    margin-bottom: 1rem;
}
.sub-success-title { font-size: 1.15rem; font-weight: 700; color: #f0f4f8; margin-bottom: 0.4rem; }
.sub-success-msg { font-size: 0.83rem; color: rgba(180, 200, 220, 0.6); line-height: 1.6; margin-bottom: 1.25rem; }
.sub-continue-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.7rem 1.6rem;
    background: linear-gradient(135deg, #696cff 0%, #5254cc 100%);
    border-radius: 10px;
    color: #fff;
    font-size: 0.88rem;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
    border: none;
    font-family: inherit;
    transition: all 0.2s;
}

/* Spinner */
.sub-spinner { display: inline-block; width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.25); border-top-color: #fff; border-radius: 50%; animation: sub-spin 0.6s linear infinite; }
@keyframes sub-spin { to { transform: rotate(360deg); } }

/* ── Responsive ───────────────────────────────────────────────────── */
@media (max-width: 760px) {
    .sub-layout {
        grid-template-columns: 1fr;
    }
    .sub-pay-panel {
        position: static;
        order: -1;
    }
    .sub-body { padding: 1.75rem 1rem 3rem; }
    .sub-topbar { padding: 1rem 1.25rem; }
    .sub-heading-wrap h1 { font-size: 1.55rem; }
}
</style>

<?php
/* Resolve the "popular" plan index — middle plan by price gets the badge */
$_planCount  = count($plans ?? []);
$_popularIdx = $_planCount > 2 ? (int)floor(($_planCount - 1) / 2) : -1;
?>

<div class="sub-root">

    <!-- Top bar -->
    <div class="sub-topbar">
        <div class="sub-logo">
            <img src="/images/logo/favicon_io/android-chrome-512x512-1.png" alt="Logo">
            <span><?php echo htmlspecialchars(getSiteConfiguration()->ShortName ?? 'App'); ?></span>
        </div>
        <div class="sub-org-pill">
            <i class="bx bx-buildings"></i>
            <?php echo htmlspecialchars($orgName ?? ''); ?>
        </div>
        <a href="/logout" class="sub-logout-link"><i class="bx bx-log-out"></i> Sign out</a>
    </div>

    <!-- Body -->
    <div class="sub-body">

        <?php
        $_flowLabel = ['signup' => 'Activate Your Account', 'renewal' => 'Renew Subscription', 'upgrade' => 'Upgrade Plan'];
        $_flowHead  = ['signup' => 'Choose the right plan for you', 'renewal' => 'Renew your subscription', 'upgrade' => 'Upgrade your plan'];
        $_flowSub   = [
            'signup'  => 'Your account is ready. Select a plan below to unlock your workspace and get started.',
            'renewal' => 'Your subscription has expired. Select a plan below to reactivate your account.',
            'upgrade' => 'You\'re currently on an active plan. Upgrade anytime to unlock more features.',
        ];
        $_f = $flow ?? 'signup';
        ?>
        <div class="sub-heading-wrap">
            <div class="sub-eyebrow"><i class="bx bx-crown"></i> <?php echo $_flowLabel[$_f] ?? 'Activate Your Account'; ?></div>
            <h1><?php echo $_flowHead[$_f] ?? 'Choose the right plan for you'; ?></h1>
            <p><?php echo $_flowSub[$_f] ?? ''; ?></p>
        </div>

        <div class="sub-layout">

            <!-- Left: Plan cards -->
            <div class="sub-plans-wrap">
                <h2>Available Plans</h2>
                <?php foreach ($plans as $_i => $_plan): ?>
                <?php
                    $_isSelected = ((int)$_plan->SectorPlanUID === (int)($sub->SectorPlanUID ?? 0));
                    $_isFree     = ((float)$_plan->Price <= 0);
                    $_isPopular  = ($_i === $_popularIdx);
                ?>
                <div class="sub-plan-card<?php echo $_isSelected ? ' selected' : ''; ?>"
                     onclick="subSelectPlan(<?php echo (int)$_plan->SectorPlanUID; ?>, this)"
                     data-uid="<?php echo (int)$_plan->SectorPlanUID; ?>"
                     data-name="<?php echo htmlspecialchars($_plan->PlanName); ?>"
                     data-price="<?php echo (float)$_plan->Price; ?>"
                     data-cycle="<?php echo htmlspecialchars($_plan->BillingCycle ?? ''); ?>"
                     data-days="<?php echo (int)$_plan->DurationDays; ?>"
                     data-free="<?php echo $_isFree ? '1' : '0'; ?>">
                    <div class="sub-plan-radio"></div>
                    <div class="sub-plan-info">
                        <div class="sub-plan-name">
                            <?php echo htmlspecialchars($_plan->PlanName); ?>
                            <?php if ($_isPopular): ?>
                            <span class="sub-plan-badge">Popular</span>
                            <?php elseif ($_isFree): ?>
                            <span class="sub-plan-badge free">Free</span>
                            <?php endif; ?>
                        </div>
                        <div class="sub-plan-cycle">
                            <?php echo htmlspecialchars($_plan->BillingCycle ?? ''); ?>
                            &middot; <?php echo (int)$_plan->DurationDays; ?> days
                        </div>
                    </div>
                    <div class="sub-plan-price-col">
                        <?php if ($_isFree): ?>
                        <div class="sub-plan-price free-price">Free</div>
                        <?php else: ?>
                        <div class="sub-plan-price">
                            <sup>&#8377;</sup><?php echo number_format((float)$_plan->Price, 0); ?>
                        </div>
                        <div class="sub-plan-duration">/ <?php echo strtolower($_plan->BillingCycle ?? 'plan'); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Right: Payment panel -->
            <div class="sub-pay-panel">

                <!-- Main content -->
                <div id="subPayMain">
                    <h2>Your Selection</h2>

                    <!-- Selected plan summary -->
                    <div class="sub-summary-plan">
                        <div class="sub-summary-name" id="subSummaryName">
                            <?php echo htmlspecialchars($sub->PlanName ?? 'Select a plan'); ?>
                        </div>
                        <div class="sub-summary-cycle" id="subSummaryCycle">
                            <?php echo htmlspecialchars($sub->BillingCycle ?? ''); ?>
                        </div>
                        <div class="sub-summary-amount" id="subSummaryAmount">
                            <?php if ((float)($sub->Price ?? 0) > 0): ?>
                            <span class="currency">&#8377;</span>
                            <span class="amount"><?php echo number_format((float)$sub->Price, 0); ?></span>
                            <span class="period">/ <?php echo strtolower($sub->BillingCycle ?? 'plan'); ?></span>
                            <?php else: ?>
                            <span class="amount" style="color:#4ade80;font-size:1.5rem;">Free</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Alert -->
                    <div class="sub-alert" id="subAlert">
                        <i class="bx bx-error-circle" style="flex-shrink:0;margin-top:1px;font-size:1rem;"></i>
                        <span id="subAlertText"></span>
                    </div>

                    <hr class="sub-divider">

                    <div class="sub-trust-row">
                        <i class="bx bx-lock-alt"></i>
                        Secure &amp; encrypted payment via Razorpay
                    </div>

                    <?php
                    $_payLabels = ['signup' => 'Pay & Activate', 'renewal' => 'Pay & Renew', 'upgrade' => 'Pay & Upgrade'];
                    $_payLabel  = $_payLabels[$_f] ?? 'Pay Now';
                    $_isFreeDefault = ((float)($sub->Price ?? 0) <= 0);
                    ?>
                    <button type="button" class="sub-pay-btn<?php echo $_isFreeDefault ? ' free-btn' : ''; ?>"
                            id="subPayBtn"
                            onclick="subInitiatePayment()">
                        <i class="bx bx-credit-card" id="subPayIcon"></i>
                        <span id="subPayLabel">
                            <?php if (!$_isFreeDefault): ?>
                            <?php echo $_payLabel; ?> &#8377;<?php echo number_format((float)$sub->Price, 0); ?>
                            <?php else: ?>
                            Activate Free Plan
                            <?php endif; ?>
                        </span>
                        <span id="subPaySpinner" style="display:none;"><span class="sub-spinner"></span></span>
                    </button>

                    <a href="/logout" style="display:block;text-align:center;font-size:0.78rem;color:rgba(160,190,215,.35);text-decoration:none;transition:color .2s;"
                       onmouseover="this.style.color='rgba(160,190,215,.7)'" onmouseout="this.style.color='rgba(160,190,215,.35)'">
                        Return later &amp; pay when ready
                    </a>
                </div>

                <!-- Success screen -->
                <div class="sub-success" id="subSuccess">
                    <div class="sub-success-icon"><i class="bx bx-check"></i></div>
                    <div class="sub-success-title">You're all set!</div>
                    <p class="sub-success-msg">Your subscription is now active. Welcome aboard.</p>
                    <button type="button" class="sub-continue-btn" onclick="subContinue()">
                        Continue <i class="bx bx-right-arrow-alt"></i>
                    </button>
                </div>

            </div><!-- /pay panel -->
        </div><!-- /layout -->
    </div><!-- /body -->
</div>

<script>
(function () {
    var _sectorPlanUID = <?php echo (int)($sub->SectorPlanUID ?? 0); ?>;
    var _planPrice     = <?php echo (float)($sub->Price ?? 0); ?>;
    var _orgName       = <?php echo json_encode($orgName); ?>;
    var _orgEmail      = <?php echo json_encode($orgEmail); ?>;
    var _flow          = <?php echo json_encode($flow ?? 'signup'); ?>;
    var _payLabels     = { signup: 'Pay & Activate', renewal: 'Pay & Renew', upgrade: 'Pay & Upgrade' };
    var _redirectUrl   = null;
    var _ajaxPending   = false;

    /* ── Alert helpers ──────────────────────────────────────────── */
    /** @param {string} msg @returns {void} */
    function subShowAlert(msg) {
        document.getElementById('subAlertText').textContent = msg;
        document.getElementById('subAlert').classList.add('show');
    }
    function subHideAlert() {
        document.getElementById('subAlert').classList.remove('show');
    }

    /** @param {boolean} loading @returns {void} */
    function subSetLoading(loading) {
        var btn     = document.getElementById('subPayBtn');
        var label   = document.getElementById('subPayLabel');
        var spinner = document.getElementById('subPaySpinner');
        var icon    = document.getElementById('subPayIcon');
        btn.disabled          = loading;
        label.style.display   = loading ? 'none' : '';
        icon.style.display    = loading ? 'none' : '';
        spinner.style.display = loading ? '' : 'none';
    }

    /* ── Plan selection ─────────────────────────────────────────── */
    /**
     * @param {number} planUID
     * @param {HTMLElement} cardEl
     * @returns {void}
     */
    window.subSelectPlan = function (planUID, cardEl) {
        /* Always update UI immediately — no blocking lock */
        if (planUID === _sectorPlanUID) return;

        subHideAlert();

        /* ── Optimistic UI: update everything immediately from card data-* ── */
        document.querySelectorAll('.sub-plan-card').forEach(function (c) { c.classList.remove('selected'); });
        if (cardEl) cardEl.classList.add('selected');

        var cardName  = cardEl ? (cardEl.dataset.name  || '') : '';
        var cardPrice = cardEl ? parseFloat(cardEl.dataset.price || '0') : 0;
        var cardCycle = cardEl ? (cardEl.dataset.cycle || '') : '';
        var cardFree  = cardEl ? (cardEl.dataset.free === '1') : (cardPrice <= 0);

        _sectorPlanUID = planUID;
        _planPrice     = cardPrice;

        var nameEl  = document.getElementById('subSummaryName');
        var cycleEl = document.getElementById('subSummaryCycle');
        var amtEl   = document.getElementById('subSummaryAmount');
        if (nameEl)  nameEl.textContent  = cardName;
        if (cycleEl) cycleEl.textContent = cardCycle;
        if (amtEl) {
            if (!cardFree) {
                amtEl.innerHTML = '<span class="currency">&#8377;</span>'
                    + '<span class="amount">' + Math.round(cardPrice).toLocaleString('en-IN') + '</span>'
                    + '<span class="period">/ ' + cardCycle.toLowerCase() + '</span>';
            } else {
                amtEl.innerHTML = '<span class="amount" style="color:#4ade80;font-size:1.5rem;">Free</span>';
            }
        }

        var payBtn   = document.getElementById('subPayBtn');
        var payLabel = document.getElementById('subPayLabel');
        var payIcon  = document.getElementById('subPayIcon');
        var baseLabel = _payLabels[_flow] || 'Pay';
        if (payLabel) payLabel.textContent = !cardFree
            ? baseLabel + ' ₹' + Math.round(cardPrice).toLocaleString('en-IN')
            : 'Activate Free Plan';
        if (payBtn) {
            if (cardFree) {
                payBtn.classList.add('free-btn');
                payBtn.onclick = window.subActivateFree;
                if (payIcon) payIcon.className = 'bx bx-gift';
            } else {
                payBtn.classList.remove('free-btn');
                payBtn.onclick = window.subInitiatePayment;
                if (payIcon) payIcon.className = 'bx bx-credit-card';
            }
        }

        /* ── Background save: persist plan change to DB + Redis ── */
        if (_ajaxPending) return; /* avoid overlapping saves; UI already updated */
        _ajaxPending = true;
        fetch('/subscribe/changePlan', {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    'sector_plan_uid=' + encodeURIComponent(planUID),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            _ajaxPending = false;
            if (data.Error) {
                subShowAlert(data.Message || 'Could not save plan selection. Please try again.');
                return;
            }
            /* Free plan → redirect immediately */
            if (data.IsFree && data.Redirect) {
                showUIBlock('Activating your plan…');
                window.location.href = data.Redirect;
            }
        })
        .catch(function () {
            _ajaxPending = false;
            subShowAlert('A network error occurred. Please try again.');
        });
    };

    window.subActivateFree = function () {
        showUIBlock('Activating your plan…');
        window.location.href = '/dashboard';
    };

    /* ── Razorpay payment ───────────────────────────────────────── */
    /** @returns {void} */
    window.subInitiatePayment = function () {
        subHideAlert();
        subSetLoading(true);

        fetch('/subscribe/createOrder', {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    'sector_plan_uid=' + encodeURIComponent(_sectorPlanUID),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.Error) {
                subSetLoading(false);
                subShowAlert(data.Message || 'Unable to initiate payment. Please try again.');
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
                theme:       { color: '#696cff' },
                modal: {
                    ondismiss: function () {
                        subSetLoading(false);
                        subShowAlert('Payment was cancelled. Click the button above to try again.');
                    }
                },
                handler: function (response) {
                    _subVerifyPayment(response, data.order_id);
                }
            };

            var rzp = new Razorpay(options);
            rzp.on('payment.failed', function (resp) {
                subSetLoading(false);
                subShowAlert(resp.error.description || 'Payment failed. Please try again.');
            });
            rzp.open();
        })
        .catch(function () {
            subSetLoading(false);
            subShowAlert('A network error occurred. Please check your connection and try again.');
        });
    };

    /**
     * @param {object} response
     * @param {string} orderId
     * @returns {void}
     */
    function _subVerifyPayment(response, orderId) {
        var body = new URLSearchParams({
            sector_plan_uid:     _sectorPlanUID,
            razorpay_order_id:   orderId,
            razorpay_payment_id: response.razorpay_payment_id,
            razorpay_signature:  response.razorpay_signature,
        });

        fetch('/subscribe/confirmPayment', {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    body.toString(),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            subSetLoading(false);
            if (data.Error) {
                subShowAlert(data.Message || 'Payment verification failed. Please contact support.');
                return;
            }
            _redirectUrl = data.Redirect || '/dashboard';
            document.getElementById('subPayMain').style.display = 'none';
            document.getElementById('subSuccess').classList.add('show');
        })
        .catch(function () {
            subSetLoading(false);
            subShowAlert('Verification failed. Contact support with payment ID: ' + (response.razorpay_payment_id || ''));
        });
    }

    /** @returns {void} */
    window.subContinue = function () {
        showUIBlock('Taking you to your workspace…');
        window.location.href = _redirectUrl || '/dashboard';
    };
}());
</script>

<!-- Razorpay SDK -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
