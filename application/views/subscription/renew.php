<?php defined('BASEPATH') OR exit('No direct script access allowed');

$orgName        = htmlspecialchars($org->Name ?? 'Your Organisation', ENT_QUOTES, 'UTF-8');
$currentPlanUID = $subscription->SectorPlanUID ?? null;
$currentStatus  = $subscription->Status        ?? null;

/* Split plans by billing cycle */
$monthly = [];
$yearly  = [];
foreach ($plans as $p) {
    if ($p->BillingCycle === 'Yearly') $yearly[]  = $p;
    else                               $monthly[] = $p;
}

/* Dynamically calculate minimum yearly saving vs paying monthly for a year */
$yearlySavingPct = 0;
if (!empty($monthly) && !empty($yearly)) {
    $minSaving = PHP_INT_MAX;
    $count = min(count($monthly), count($yearly));
    for ($i = 0; $i < $count; $i++) {
        $mPrice = (float)$monthly[$i]->Price;
        $yPrice = (float)$yearly[$i]->Price;
        if ($mPrice > 0 && $yPrice > 0) {
            $annualIfMonthly = $mPrice * 12;
            $saving = ($annualIfMonthly - $yPrice) / $annualIfMonthly * 100;
            if ($saving < $minSaving) $minSaving = $saving;
        }
    }
    if ($minSaving !== PHP_INT_MAX && $minSaving > 0) {
        $yearlySavingPct = (int)floor($minSaving);
    }
}
?>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

.sr-root {
    min-height: 100vh;
    background: #040b18;
    font-family: 'Public Sans', sans-serif;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1rem 1.5rem 4rem;
}

/* ── Back button — fixed, no effect on layout ────────── */
.sr-back {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 100;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    background: rgba(255,255,255,0.05);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 100px;
    color: #94a3b8;
    font-size: 13px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s;
}
.sr-back:hover { background: rgba(255,255,255,0.09); color: #e2e8f0; text-decoration: none; }

/* ── Header ──────────────────────────────────────────── */
.sr-header { text-align: center; margin-bottom: 2.5rem; }
.sr-expired-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 14px;
    background: rgba(239,68,68,0.12);
    border: 1px solid rgba(239,68,68,0.3);
    border-radius: 100px;
    color: #f87171;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    margin-bottom: 1rem;
}
.sr-org-name {
    font-size: 1.7rem;
    font-weight: 700;
    color: #f0f4f8;
    margin-bottom: 0.4rem;
}
.sr-sub-hint {
    font-size: 0.9rem;
    color: rgba(148,163,184,0.75);
}

/* ── Cycle toggle ────────────────────────────────────── */
.sr-toggle-wrap {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 100px;
    padding: 4px;
    margin-bottom: 2.5rem;
}
.sr-toggle-btn {
    padding: 7px 22px;
    border-radius: 100px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    background: transparent;
    color: rgba(148,163,184,0.8);
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 7px;
}
.sr-toggle-btn.active {
    background: rgba(245,158,11,0.18);
    color: #fbbf24;
    border: 1px solid rgba(245,158,11,0.35);
}
.sr-save-badge {
    font-size: 10px;
    font-weight: 700;
    padding: 2px 7px;
    background: rgba(52,211,153,0.15);
    border: 1px solid rgba(52,211,153,0.3);
    border-radius: 100px;
    color: #6ee7b7;
    letter-spacing: 0.02em;
}

/* ── Plan grid ───────────────────────────────────────── */
.sr-plans-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
    width: 100%;
    max-width: 980px;
    align-items: start;
}

/* ── Tier accent tokens ──────────────────────────────── */
.sr-plan-card[data-tier="basic"] { --tc: #38bdf8; --tr: 56,189,248; }
.sr-plan-card[data-tier="pro"]   { --tc: #a78bfa; --tr: 167,139,250; }
.sr-plan-card[data-tier="ent"]   { --tc: #f59e0b; --tr: 245,158,11; }

/* ── Card base ───────────────────────────────────────── */
.sr-plan-card {
    position: relative;
    background: linear-gradient(160deg, rgba(255,255,255,0.055) 0%, rgba(255,255,255,0.02) 100%);
    border: 1px solid rgba(255,255,255,0.09);
    border-radius: 20px;
    padding: 1.875rem 1.625rem 1.625rem;
    display: flex;
    flex-direction: column;
    gap: 1.1rem;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
    cursor: default;
}

/* Glow border via pseudo */
.sr-plan-card::before {
    content: '';
    position: absolute;
    inset: -1px;
    border-radius: 21px;
    background: linear-gradient(135deg, var(--tc, #fff), transparent 55%);
    z-index: -1;
    opacity: 0;
    transition: opacity 0.35s ease;
}

/* Shimmer sweep on hover */
.sr-plan-card::after {
    content: '';
    position: absolute;
    top: -40%;
    left: -60%;
    width: 35%;
    height: 180%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.04), transparent);
    transform: skewX(-18deg);
    transition: left 0.7s ease;
    pointer-events: none;
}

.sr-plan-card:hover { transform: translateY(-7px); box-shadow: 0 24px 64px rgba(var(--tr,255,255,255), 0.13); border-color: rgba(var(--tr,255,255,255), 0.22); }
.sr-plan-card:hover::before { opacity: 1; }
.sr-plan-card:hover::after  { left: 120%; }

/* Pro — always elevated */
.sr-plan-card[data-tier="pro"] {
    border-color: rgba(167,139,250,0.28);
    background: linear-gradient(160deg, rgba(167,139,250,0.09) 0%, rgba(255,255,255,0.025) 100%);
    box-shadow: 0 0 0 1px rgba(167,139,250,0.12), 0 12px 48px rgba(167,139,250,0.14);
    transform: translateY(-4px);
}
.sr-plan-card[data-tier="pro"]::before { opacity: 0.5; }

/* Current plan */
.sr-plan-card.is-current {
    border-color: rgba(var(--tr,245,158,11), 0.45) !important;
    box-shadow: 0 0 0 1px rgba(var(--tr,245,158,11), 0.18), 0 8px 40px rgba(var(--tr,245,158,11), 0.1) !important;
}

/* ── Card header ─────────────────────────────────────── */
.sr-card-top { display: flex; align-items: flex-start; justify-content: space-between; }
.sr-card-badges { display: flex; flex-direction: column; gap: 5px; align-items: flex-end; }

.sr-badge {
    font-size: 9.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    padding: 3px 9px;
    border-radius: 100px;
}
.sr-badge-current  { background: rgba(245,158,11,0.14); border: 1px solid rgba(245,158,11,0.38); color: #fbbf24; }
.sr-badge-popular  { background: rgba(167,139,250,0.14); border: 1px solid rgba(167,139,250,0.38); color: #c4b5fd; }
.sr-badge-upgrade  { background: rgba(52,211,153,0.1);  border: 1px solid rgba(52,211,153,0.28);  color: #6ee7b7; }
.sr-badge-downgrade{ background: rgba(148,163,184,0.08); border: 1px solid rgba(148,163,184,0.18); color: #94a3b8; }
.sr-badge-recommend{ background: rgba(34,197,94,0.14);  border: 1px solid rgba(34,197,94,0.4);    color: #4ade80; }
.sr-badge-trial    { background: rgba(148,163,184,0.08); border: 1px solid rgba(148,163,184,0.18); color: #94a3b8; }
.sr-badge-previous { background: rgba(99,179,237,0.1);  border: 1px solid rgba(99,179,237,0.28);  color: #7dd3fc; }

.sr-plan-name {
    font-size: 1rem;
    font-weight: 700;
    color: #e2f0ff;
    letter-spacing: -0.01em;
}

/* Accent line under name */
.sr-plan-accent-line {
    width: 28px;
    height: 3px;
    border-radius: 2px;
    background: var(--tc, #f59e0b);
    margin-top: 6px;
    opacity: 0.7;
}

/* ── Price block ─────────────────────────────────────── */
.sr-price-block { display: flex; flex-direction: column; gap: 4px; }
.sr-price-row {
    display: flex;
    align-items: baseline;
    gap: 3px;
}
.sr-price-sym  { font-size: 1.15rem; font-weight: 700; color: var(--tc, #94a3b8); align-self: flex-start; margin-top: 7px; }
.sr-price-amt  { font-size: 2.4rem; font-weight: 800; color: #f1f5f9; line-height: 1; letter-spacing: -0.03em; font-variant-numeric: tabular-nums; }
.sr-price-cycle{ font-size: 0.78rem; color: rgba(148,163,184,0.6); margin-left: 2px; }
.sr-price-gst  { font-size: 0.72rem; color: rgba(148,163,184,0.5); letter-spacing: 0.01em; }

/* ── Stat tiles ──────────────────────────────────────── */
.sr-stats-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
    flex: 1;
}
.sr-stat-tile {
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 12px;
    padding: 10px 8px 9px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 5px;
    text-align: center;
    transition: background 0.2s, border-color 0.2s;
}
.sr-plan-card:hover .sr-stat-tile {
    background: rgba(var(--tr,255,255,255), 0.05);
    border-color: rgba(var(--tr,255,255,255), 0.12);
}
.sr-stat-icon {
    font-size: 17px;
    color: var(--tc, #94a3b8);
    line-height: 1;
}
.sr-stat-val {
    font-size: 1.15rem;
    font-weight: 800;
    color: #e2f0ff;
    line-height: 1;
    letter-spacing: -0.02em;
    font-variant-numeric: tabular-nums;
}
.sr-stat-lbl {
    font-size: 0.67rem;
    color: rgba(148,163,184,0.6);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    line-height: 1;
}

/* ── Pay button ──────────────────────────────────────── */
.sr-pay-btn {
    width: 100%;
    padding: 12px;
    border-radius: 11px;
    border: none;
    font-size: 0.875rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.25s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    letter-spacing: 0.01em;
}
.sr-pay-btn.tier-basic { background: linear-gradient(135deg, #0284c7, #38bdf8); color: #fff; box-shadow: 0 4px 16px rgba(56,189,248,0.25); }
.sr-pay-btn.tier-pro   { background: linear-gradient(135deg, #7c3aed, #a78bfa); color: #fff; box-shadow: 0 4px 16px rgba(167,139,250,0.3); }
.sr-pay-btn.tier-ent   { background: linear-gradient(135deg, #d97706, #fbbf24); color: #040b18; box-shadow: 0 4px 16px rgba(245,158,11,0.3); }
.sr-pay-btn:hover      { filter: brightness(1.1); transform: translateY(-2px); }
.sr-pay-btn:disabled   { opacity: 0.45; cursor: not-allowed; transform: none !important; filter: none !important; }

/* ── Success overlay ──────────────────────────────────── */
.sr-success-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(4,11,24,0.92);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 1.25rem;
    text-align: center;
    padding: 2rem;
}
.sr-success-overlay.show { display: flex; }
.sr-success-icon { font-size: 4rem; color: #34d399; }
.sr-success-title { font-size: 1.5rem; font-weight: 700; color: #f0f4f8; }
.sr-success-sub   { font-size: 0.9rem; color: rgba(148,163,184,0.8); }
.sr-success-btn {
    margin-top: 0.5rem;
    padding: 12px 30px;
    background: linear-gradient(135deg, #f59e0b, #d97706);
    border: none;
    border-radius: 10px;
    color: #040b18;
    font-size: 0.95rem;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    transition: filter 0.2s;
}
.sr-success-btn:hover { filter: brightness(1.1); text-decoration: none; color: #040b18; }

/* ── Spinner ──────────────────────────────────────────── */
.sr-spinner {
    display: none;
    width: 18px; height: 18px;
    border: 2px solid rgba(4,11,24,0.3);
    border-top-color: #040b18;
    border-radius: 50%;
    animation: sr-spin 0.7s linear infinite;
}
@keyframes sr-spin { to { transform: rotate(360deg); } }
@keyframes sr-fade-dots {
    0%,20%  { content: 'Processing'; }
    40%     { content: 'Processing.'; }
    60%     { content: 'Processing..'; }
    80%,100%{ content: 'Processing...'; }
}

/* ── Full-page payment processing overlay ────────────── */
.sr-pay-overlay {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 9000;
    background: rgba(4,11,24,0.92);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 2rem;
}
.sr-pay-overlay.show { display: flex; }

/* Spinning gradient ring logo */
.sr-pay-logo-wrap {
    position: relative;
    width: 60px;       /* sized to hug the logo — 52px logo + 4px gap each side */
    height: 60px;
    flex-shrink: 0;
}
.sr-ring-spin {
    position: absolute;
    inset: -4px;       /* 4px ring outside the wrap */
    border-radius: 50%;
    background: conic-gradient(from 0deg,
        #7c3aed 0%,
        #06b6d4 28%,
        #ef4444 52%,
        #f59e0b 76%,
        #7c3aed 100%
    );
    animation: sr-ring-rot 2s linear infinite;
    z-index: 0;
    filter: drop-shadow(0 0 5px rgba(124,58,237,0.6));
}
.sr-ring-spin::after {
    content: '';
    position: absolute;
    inset: 4px;        /* dark mask cuts back to wrap boundary */
    border-radius: 50%;
    background: #040b18;
}
.sr-pay-logo-img {
    position: absolute;
    inset: 4px;        /* 4px gap between logo and ring inner edge */
    width: 52px;
    height: 52px;
    border-radius: 50%;
    object-fit: cover;
    z-index: 1;
}
@keyframes sr-ring-rot { to { transform: rotate(360deg); } }

/* Text below */
.sr-pay-overlay-msg {
    font-size: 1.1rem;
    font-weight: 700;
    color: #e2f0ff;
    letter-spacing: -0.01em;
}
.sr-pay-overlay-msg::after {
    display: inline-block;
    animation: sr-fade-dots 2s steps(1) infinite;
}
.sr-pay-overlay-sub {
    font-size: 0.8rem;
    color: rgba(148,163,184,0.55);
    margin-top: -1.4rem;
    letter-spacing: 0.01em;
}
.sr-pay-overlay-stage {
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: rgba(148,163,184,0.4);
    margin-top: -1rem;
}

/* ── Session countdown banner ────────────────────────────── */
.sr-countdown-bar {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 9px 20px;
    background: rgba(245,158,11,0.08);
    border: 1px solid rgba(245,158,11,0.22);
    border-radius: 100px;
    color: #fbbf24;
    font-size: 13px;
    font-weight: 600;
    margin-top: -1.2rem;
    margin-bottom: 1rem;
    transition: background 0.3s, border-color 0.3s, color 0.3s;
}
.sr-countdown-bar.sr-cd-warn {
    background: rgba(239,68,68,0.12);
    border-color: rgba(239,68,68,0.35);
    color: #f87171;
    animation: sr-cd-pulse 1s ease-in-out infinite;
}
@keyframes sr-cd-pulse {
    0%,100% { opacity: 1; }
    50%      { opacity: 0.65; }
}
.sr-countdown-icon { font-size: 16px; flex-shrink: 0; }
.sr-countdown-val  { font-variant-numeric: tabular-nums; letter-spacing: 0.04em; }

@media (max-width: 960px) {
    .sr-plans-grid { grid-template-columns: repeat(2, 1fr); max-width: 660px; }
    .sr-plan-card[data-tier="pro"] { transform: none; }
    .sr-plan-card[data-tier="pro"]:hover { transform: translateY(-7px); }
}
@media (max-width: 640px) {
    .sr-plans-grid { grid-template-columns: 1fr; max-width: 400px; }
}
@media (max-width: 540px) {
    .sr-org-name { font-size: 1.3rem; }
    .sr-topbar { flex-direction: column; gap: 1rem; align-items: flex-start; }
}
</style>

<div class="sr-root">

    <!-- Back to Login — fixed top-right -->
    <a href="<?php echo base_url('login'); ?>" class="sr-back">
        <i class="bx bx-arrow-back" style="font-size:14px;"></i> Back to Login
    </a>

    <!-- Header -->
    <div class="sr-header">
        <h1 class="sr-org-name"><?php echo $orgName; ?></h1>
        <p class="sr-sub-hint">Choose a plan below to reactivate your account instantly.</p>
    </div>

    <!-- Session countdown -->
    <div class="sr-countdown-bar" id="srCountdownBar">
        <i class="bx bx-time-five sr-countdown-icon"></i>
        <span>Renewal link expires in&nbsp;</span>
        <span class="sr-countdown-val" id="srCountdownVal">30:00</span>
    </div>

    <!-- Cycle toggle -->
    <?php if (!empty($monthly) && !empty($yearly)): ?>
    <div class="sr-toggle-wrap">
        <button class="sr-toggle-btn active" id="srToggleMonthly" onclick="switchCycle('Monthly')">Monthly</button>
        <button class="sr-toggle-btn" id="srToggleYearly" onclick="switchCycle('Yearly')">
            Yearly
            <span class="sr-save-badge">SAVE ~<?php echo $yearlySavingPct; ?>%</span>
        </button>
    </div>
    <?php endif; ?>

    <!-- Plan cards -->
    <div class="sr-plans-grid">

        <?php
        /* Determine which SectorPlanUID is the current one for badge logic */
        $allPlans = array_merge($monthly ?: [], $yearly ?: []);
        $currentSectorPlanUID = (int)($subscription->SectorPlanUID ?? 0);

        /* Find price rank + plan code of current plan */
        $currentPrice    = 0;
        $currentPlanCode = '';
        foreach ($allPlans as $p) {
            if ((int)$p->SectorPlanUID === $currentSectorPlanUID) {
                $currentPrice    = (float)$p->Price;
                $currentPlanCode = strtolower(trim($p->PlanCode ?? ''));
                break;
            }
        }
        /* Trial = PlanCode is 'trial' OR price is 0 */
        $wasTrial = ($currentPlanCode === 'trial' || $currentPrice == 0);

        /* Assign tiers by position within each billing cycle */
        $tierNames    = ['basic', 'pro', 'ent'];
        $tierBtnClass = ['tier-basic', 'tier-pro', 'tier-ent'];
        $cycleCounters = ['Monthly' => 0, 'Yearly' => 0];

        /* Render both cycles; JS toggles visibility */
        foreach ($allPlans as $p):
            $billingCycle = $p->BillingCycle;
            $tierIdx     = $cycleCounters[$billingCycle] ?? 0;
            $tier        = $tierNames[min($tierIdx, 2)];
            $tierBtn     = $tierBtnClass[min($tierIdx, 2)];
            $cycleCounters[$billingCycle]++;

            /* Strip " — Monthly" / " — Yearly" suffix — cycle is shown on the toggle */
            $displayName = trim(preg_replace('/\s*[—\-]+\s*(Monthly|Yearly)\s*$/i', '', $p->PlanName));

            $isCurrent   = ((int)$p->SectorPlanUID === $currentSectorPlanUID);
            $isUpgrade   = (!$isCurrent && (float)$p->Price > $currentPrice && $currentPrice > 0);
            $isDowngrade = (!$isCurrent && (float)$p->Price < $currentPrice && $currentPrice > 0);
            $cycle       = $billingCycle === 'Yearly' ? '/yr' : '/mo';
            $cardClass   = 'sr-plan-card' . ($isCurrent ? ' is-current' : '');

            $taxableAmt  = !empty($p->TaxableAmount) ? (float)$p->TaxableAmount : round((float)$p->Price * 100 / 118, 2);
            $taxAmt      = !empty($p->TaxAmount)     ? (float)$p->TaxAmount     : round((float)$p->Price * 18  / 118, 2);
            $btnLabel    = $isCurrent ? 'Renew — ₹' . number_format((float)$p->Price, 0, '.', ',') : 'Select — ₹' . number_format((float)$p->Price, 0, '.', ',');
        ?>
        <div class="<?php echo $cardClass; ?>"
             data-tier="<?php echo $tier; ?>"
             data-cycle="<?php echo htmlspecialchars($billingCycle, ENT_QUOTES); ?>"
             data-plan-uid="<?php echo (int)$p->SectorPlanUID; ?>"
             data-price="<?php echo number_format((float)$p->Price, 2, '.', ''); ?>"
             data-plan-name="<?php echo htmlspecialchars($displayName, ENT_QUOTES); ?>">

            <div class="sr-card-top">
                <div>
                    <p class="sr-plan-name"><?php echo htmlspecialchars($displayName, ENT_QUOTES); ?></p>
                    <div class="sr-plan-accent-line"></div>
                </div>
                <div class="sr-card-badges">
                    <?php if ($wasTrial):
                        /* Coming from Trial — recommend Enterprise; mark trial card */
                        if ($tier === 'ent'): ?>
                            <span class="sr-badge sr-badge-recommend">&#10003; Recommended</span>
                        <?php elseif ($isCurrent): ?>
                            <span class="sr-badge sr-badge-trial">Trial</span>
                        <?php elseif ($tier === 'pro'): ?>
                            <span class="sr-badge sr-badge-popular">&#9733; Popular</span>
                        <?php endif;
                    else:
                        /* Coming from a paid plan */
                        if ($isCurrent): ?>
                            <span class="sr-badge sr-badge-previous">Previous Plan</span>
                        <?php elseif ($tier === 'pro' && !$isCurrent): ?>
                            <span class="sr-badge sr-badge-popular">&#9733; Popular</span>
                        <?php elseif ($isUpgrade): ?>
                            <span class="sr-badge sr-badge-upgrade">Upgrade</span>
                        <?php elseif ($isDowngrade): ?>
                            <span class="sr-badge sr-badge-downgrade">Downgrade</span>
                        <?php endif;
                    endif; ?>
                </div>
            </div>

            <div class="sr-price-block">
                <div class="sr-price-row">
                    <span class="sr-price-sym">₹</span>
                    <span class="sr-price-amt"><?php echo number_format((float)$p->Price, 0, '.', ','); ?></span>
                    <span class="sr-price-cycle"><?php echo $cycle; ?></span>
                </div>
                <?php if ((float)$p->Price > 0): ?>
                <p class="sr-price-gst">₹<?php echo number_format($taxableAmt, 2); ?> + ₹<?php echo number_format($taxAmt, 2); ?> GST (18%)</p>
                <?php else: ?>
                <p class="sr-price-gst">Free — no billing required</p>
                <?php endif; ?>
            </div>

            <?php
            $userVal    = (int)$p->MaxUsers    === 0 ? '∞'  : (string)(int)$p->MaxUsers;
            $branchVal  = (int)$p->MaxBranches === 0 ? '∞'  : (string)(int)$p->MaxBranches;
            $daysVal    = (string)(int)$p->DurationDays;
            ?>
            <div class="sr-stats-row">
                <div class="sr-stat-tile">
                    <i class="bx bx-user sr-stat-icon"></i>
                    <span class="sr-stat-val"><?php echo $userVal; ?></span>
                    <span class="sr-stat-lbl">Users</span>
                </div>
                <div class="sr-stat-tile">
                    <i class="bx bx-store sr-stat-icon"></i>
                    <span class="sr-stat-val"><?php echo $branchVal; ?></span>
                    <span class="sr-stat-lbl">Branches</span>
                </div>
                <div class="sr-stat-tile">
                    <i class="bx bx-calendar-check sr-stat-icon"></i>
                    <span class="sr-stat-val"><?php echo $daysVal; ?></span>
                    <span class="sr-stat-lbl">Days</span>
                </div>
            </div>

            <button class="sr-pay-btn <?php echo $tierBtn; ?>"
                    onclick="startPayment(this, <?php echo (int)$p->SectorPlanUID; ?>, '<?php echo htmlspecialchars($displayName, ENT_QUOTES); ?>', '<?php echo number_format((float)$p->Price, 2, '.', ''); ?>')">
                <span class="sr-btn-text"><?php echo $btnLabel; ?></span>
                <span class="sr-spinner"></span>
            </button>

        </div>
        <?php endforeach; ?>

    </div>

</div>

<!-- Full-page payment processing overlay -->
<div class="sr-pay-overlay" id="srPayOverlay">
    <div class="sr-pay-logo-wrap">
        <div class="sr-ring-spin"></div>
        <img src="https://pub-bb40942a33344637936ade1f3800ff8b.r2.dev/Global/favicon_io/android-chrome-512x512-1.png"
             class="sr-pay-logo-img" alt="Logo">
    </div>
    <p class="sr-pay-overlay-msg" id="srOverlayMsg">Preparing your payment</p>
    <p class="sr-pay-overlay-stage" id="srOverlayStage">Step 1 of 2 — Creating order</p>
    <p class="sr-pay-overlay-sub">Do not close or refresh this page</p>
</div>

<!-- Success overlay -->
<div class="sr-success-overlay" id="srSuccessOverlay">
    <div class="sr-success-icon"><i class="bx bx-check-circle"></i></div>
    <p class="sr-success-title">Subscription Activated!</p>
    <p class="sr-success-sub">Your plan is now active. You can log in to continue.</p>
    <a href="<?php echo base_url('login'); ?>" class="sr-success-btn">
        <i class="bx bx-log-in"></i> Sign In Now
    </a>
</div>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
var _sid         = <?php echo json_encode($sid); ?>;
var _orgName     = <?php echo json_encode($orgName); ?>;
var _activeCycle = 'Monthly';
var _defaultCycle = <?php echo json_encode(!empty($monthly) ? 'Monthly' : 'Yearly'); ?>;

function switchCycle(cycle) {
    _activeCycle = cycle;
    document.querySelectorAll('.sr-plan-card').forEach(function(card) {
        card.style.display = card.dataset.cycle === cycle ? 'flex' : 'none';
    });
    var btnM = document.getElementById('srToggleMonthly');
    var btnY = document.getElementById('srToggleYearly');
    if (btnM) btnM.classList.toggle('active', cycle === 'Monthly');
    if (btnY) btnY.classList.toggle('active',  cycle === 'Yearly');
}

/* Show correct cycle by default */
document.addEventListener('DOMContentLoaded', function() { switchCycle(_defaultCycle); });

/* ── Overlay helpers ─────────────────────────────────── */
function _showOverlay(msg, stage) {
    document.getElementById('srOverlayMsg').textContent   = msg;
    document.getElementById('srOverlayStage').textContent = stage;
    document.getElementById('srPayOverlay').classList.add('show');
}
function _hideOverlay() {
    document.getElementById('srPayOverlay').classList.remove('show');
}

function startPayment(btn, sectorPlanUID, planName, price) {
    /* Disable all plan buttons to prevent double-selection */
    document.querySelectorAll('.sr-pay-btn').forEach(function(b) { b.disabled = true; });

    _showOverlay('Preparing your payment', 'Step 1 of 2 — Creating order');

    var body = 'sid=' + encodeURIComponent(_sid)
             + '&sector_plan_uid=' + sectorPlanUID;

    var csrfInput = document.querySelector('input[name^="csrf"]');
    if (csrfInput) body += '&' + encodeURIComponent(csrfInput.name) + '=' + encodeURIComponent(csrfInput.value);

    fetch('/subscription/renew/createOrder', {
        method : 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body   : body,
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.Error) {
            _hideOverlay();
            document.querySelectorAll('.sr-pay-btn').forEach(function(b) { b.disabled = false; });
            alert(data.Message || 'Could not initiate payment. Please try again.');
            return;
        }

        _showOverlay('Opening payment gateway', 'Step 1 of 2 — Redirecting to Razorpay');

        var options = {
            key         : data.key_id,
            amount      : data.amount,
            currency    : data.currency || 'INR',
            name        : data.name,
            description : data.description,
            order_id    : data.order_id,
            prefill     : data.prefill || {},
            theme       : { color: '#a78bfa' },
            handler     : function(response) {
                /* Payment captured — show confirmation stage */
                _showOverlay('Confirming your payment', 'Step 2 of 2 — Activating subscription');
                handlePaymentSuccess(sectorPlanUID, response);
            },
            modal: {
                onopen: function() {
                    /* Razorpay modal is now visible — hide our overlay */
                    _hideOverlay();
                },
                ondismiss: function() {
                    _hideOverlay();
                    document.querySelectorAll('.sr-pay-btn').forEach(function(b) { b.disabled = false; });
                }
            }
        };
        var rzp = new Razorpay(options);
        rzp.open();
    })
    .catch(function() {
        _hideOverlay();
        document.querySelectorAll('.sr-pay-btn').forEach(function(b) { b.disabled = false; });
        alert('Connection failed. Please try again.');
    });
}

/* ── Session countdown ───────────────────────────────────── */
(function () {
    var _totalSecs = <?php echo (int)($remainingSecs ?? 1800); ?>;
    var _remaining = _totalSecs;
    var _bar       = document.getElementById('srCountdownBar');
    var _valEl     = document.getElementById('srCountdownVal');
    var _expired   = '<?php echo base_url('subscription/renew'); ?>';

    function _fmt(s) {
        var m = Math.floor(s / 60);
        var sec = s % 60;
        return String(m).padStart(2, '0') + ':' + String(sec).padStart(2, '0');
    }

    function _tick() {
        if (_remaining <= 0) {
            window.location.href = _expired;
            return;
        }
        _remaining--;
        if (_valEl) _valEl.textContent = _fmt(_remaining);
        if (_remaining <= 300 && _bar) _bar.classList.add('sr-cd-warn');
        if (_remaining > 300 && _bar)  _bar.classList.remove('sr-cd-warn');
    }

    if (_valEl) _valEl.textContent = _fmt(_remaining);
    setInterval(_tick, 1000);
}());

function handlePaymentSuccess(sectorPlanUID, rpResponse) {
    var body = 'sid=' + encodeURIComponent(_sid)
             + '&sector_plan_uid=' + sectorPlanUID
             + '&razorpay_order_id='   + encodeURIComponent(rpResponse.razorpay_order_id)
             + '&razorpay_payment_id=' + encodeURIComponent(rpResponse.razorpay_payment_id)
             + '&razorpay_signature='  + encodeURIComponent(rpResponse.razorpay_signature);

    var csrfInput = document.querySelector('input[name^="csrf"]');
    if (csrfInput) body += '&' + encodeURIComponent(csrfInput.name) + '=' + encodeURIComponent(csrfInput.value);

    fetch('/subscription/renew/confirmPayment', {
        method : 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body   : body,
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        _hideOverlay();
        if (data.Error) {
            alert('Payment received but activation failed: ' + data.Message + '\nPlease contact support with your payment ID: ' + rpResponse.razorpay_payment_id);
            return;
        }
        document.getElementById('srSuccessOverlay').classList.add('show');
    })
    .catch(function() {
        _hideOverlay();
        alert('Payment received but confirmation failed. Please contact support with your payment ID: ' + rpResponse.razorpay_payment_id);
    });
}
</script>
