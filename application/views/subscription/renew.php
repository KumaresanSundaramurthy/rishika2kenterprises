<?php defined('BASEPATH') OR exit('No direct script access allowed');

$orgName        = htmlspecialchars($org->Name ?? 'Your Organisation', ENT_QUOTES, 'UTF-8');
$currentPlanUID = $subscription->SectorPlanUID ?? null;
$currentStatus  = $subscription->Status        ?? null;
$currentMaxUsers     = (int)($subscription->MaxUsers     ?? 0);
$currentMaxBranches  = (int)($subscription->MaxBranches  ?? 0);
$currentDurationDays = (int)($subscription->DurationDays ?? 0);

/* Split plans by billing cycle */
$monthly = [];
$yearly  = [];
foreach ($plans as $p) {
    if ($p->BillingCycle === 'Yearly') $yearly[]  = $p;
    else                               $monthly[] = $p;
}

/* Calculate max yearly saving vs paying monthly for a year.
   Sort copies by price so mismatched test plans don't skew the pairing. */
$yearlySavingPct = 0;
if (!empty($monthly) && !empty($yearly)) {
    $sortedM = $monthly; $sortedY = $yearly;
    usort($sortedM, fn($a, $b) => (float)$a->Price <=> (float)$b->Price);
    usort($sortedY, fn($a, $b) => (float)$a->Price <=> (float)$b->Price);
    $maxSaving = 0;
    $count = min(count($sortedM), count($sortedY));
    for ($i = 0; $i < $count; $i++) {
        $mPrice = (float)$sortedM[$i]->Price;
        $yPrice = (float)$sortedY[$i]->Price;
        if ($mPrice > 0 && $yPrice > 0) {
            $annualIfMonthly = $mPrice * 12;
            $saving = ($annualIfMonthly - $yPrice) / $annualIfMonthly * 100;
            if ($saving > $maxSaving) $maxSaving = $saving;
        }
    }
    $yearlySavingPct = $maxSaving > 0 ? (int)floor($maxSaving) : 0;
}

/* Default to the billing cycle the org was on; fall back to Monthly */
$defaultCycle = (($subscription->BillingCycle ?? 'Monthly') === 'Yearly') ? 'Yearly' : 'Monthly';
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
    cursor: pointer;
    font-family: inherit;
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
.sr-org-identity {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    margin-bottom: 0.4rem;
}
.sr-org-logo {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    object-fit: contain;
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.1);
    padding: 4px;
    flex-shrink: 0;
}
.sr-org-name {
    font-size: 1.7rem;
    font-weight: 700;
    color: #f0f4f8;
    margin-bottom: 0;
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
    display: none; /* hidden until switchCycle() reveals the active tab's cards */
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

/* Previous / current plan — stronger visual treatment */
.sr-plan-card.is-current {
    border-color: rgba(var(--tr,245,158,11), 0.6) !important;
    box-shadow: 0 0 0 2px rgba(var(--tr,245,158,11), 0.25),
                0 12px 48px rgba(var(--tr,245,158,11), 0.18) !important;
    background: linear-gradient(160deg,
        rgba(var(--tr,245,158,11), 0.08) 0%,
        rgba(255,255,255,0.02) 100%) !important;
}
.sr-prev-plan-strip {
    margin: 0.6rem -1.625rem -1.625rem;
    padding: 7px 14px;
    background: rgba(var(--tr,245,158,11), 0.1);
    border-top: 1px solid rgba(var(--tr,245,158,11), 0.2);
    border-radius: 0 0 20px 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: rgba(var(--tr,245,158,11), 1);
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

/* ── Plan change modal ────────────────────────────────── */
.sr-modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 9000;
    background: rgba(4,11,24,0.82);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
}
.sr-modal-overlay[hidden] { display: none !important; }
.sr-modal-box {
    background: #0e1929;
    border: 1px solid rgba(255,255,255,0.11);
    border-radius: 20px;
    padding: 2rem 2rem 1.75rem;
    max-width: 480px;
    width: 100%;
    animation: sr-modal-in 0.22s ease;
}
@keyframes sr-modal-in {
    from { opacity: 0; transform: translateY(14px) scale(0.98); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}
.sr-modal-hero {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 56px;
    height: 56px;
    border-radius: 50%;
    font-size: 1.6rem;
    margin: 0 auto 1.25rem;
}
.sr-modal-hero--warn  { background: rgba(245,158,11,0.15); color: #f59e0b; border: 1px solid rgba(245,158,11,0.3); }
.sr-modal-hero--up    { background: rgba(167,139,250,0.15); color: #a78bfa; border: 1px solid rgba(167,139,250,0.3); }
.sr-modal-title {
    font-size: 1.2rem;
    font-weight: 700;
    color: #f0f4f8;
    text-align: center;
    margin-bottom: 0.5rem;
}
.sr-modal-lead {
    font-size: 0.87rem;
    color: rgba(148,163,184,0.75);
    text-align: center;
    margin-bottom: 1.25rem;
    line-height: 1.55;
}
.sr-modal-list {
    list-style: none;
    background: rgba(0,0,0,0.22);
    border-radius: 12px;
    padding: 0.2rem 1.1rem;
    margin-bottom: 1.1rem;
}
.sr-modal-list li {
    display: flex;
    align-items: flex-start;
    gap: 0.6rem;
    padding: 0.65rem 0;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    font-size: 0.875rem;
    color: rgba(148,163,184,0.85);
    line-height: 1.45;
}
.sr-modal-list li:last-child { border-bottom: none; }
.sr-modal-list li .sr-ml-icon { flex-shrink: 0; margin-top: 1px; font-size: 1rem; }
.sr-modal-list li .sr-ml-text b { color: #f0f4f8; }
.sr-modal-list li .sr-ml-text .sr-ml-arrow { color: #f59e0b; font-weight: 700; margin: 0 4px; }
.sr-modal-list li .sr-ml-text .sr-ml-arrow--up { color: #a78bfa; }
.sr-modal-note {
    font-size: 0.79rem;
    color: rgba(148,163,184,0.45);
    text-align: center;
    margin-bottom: 1.25rem;
    line-height: 1.5;
}
.sr-modal-note[hidden] { display: none !important; }
.sr-modal-actions {
    display: flex;
    gap: 0.65rem;
}
.sr-modal-actions--single { justify-content: center; flex-direction: column; align-items: center; }
.sr-modal-btn-close {
    flex: 1;
    padding: 12px;
    background: transparent;
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: 12px;
    color: rgba(148,163,184,0.65);
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}
.sr-modal-btn-close:hover { background: rgba(255,255,255,0.05); color: #e2e8f0; }
.sr-modal-btn-proceed {
    flex: 2;
    padding: 12px;
    background: linear-gradient(135deg, #7c3aed, #a78bfa);
    border: none;
    border-radius: 12px;
    color: #fff;
    font-size: 0.95rem;
    font-weight: 700;
    cursor: pointer;
    transition: opacity 0.2s, transform 0.15s;
    letter-spacing: 0.01em;
}
.sr-modal-btn-proceed:hover  { opacity: 0.9; transform: translateY(-1px); }
.sr-modal-btn-proceed:active { transform: translateY(0); }
.sr-modal-esc-hint {
    font-size: 0.76rem;
    color: rgba(148,163,184,0.35);
    margin-top: 0.6rem;
}
@media (max-width: 480px) {
    .sr-modal-actions { flex-direction: column; }
    .sr-modal-btn-close, .sr-modal-btn-proceed { flex: unset; width: 100%; }
}

</style>

<div class="sr-root">

    <!-- Back to Login — fixed top-right -->
    <button type="button" class="sr-back" id="srBackBtn">
        <i class="bx bx-arrow-back" style="font-size:14px;"></i> Back to Login
    </button>

    <!-- Header -->
    <div class="sr-header">
        <div class="sr-org-identity">
            <?php $orgLogo = $org->Logo ?? ''; if (!empty($orgLogo)): ?>
                <img src="<?php echo htmlspecialchars($orgLogo, ENT_QUOTES); ?>"
                     alt="<?php echo $orgName; ?> logo"
                     class="sr-org-logo">
            <?php endif; ?>
            <h1 class="sr-org-name"><?php echo $orgName; ?></h1>
        </div>
        <p class="sr-sub-hint">Choose a plan below to reactivate your account instantly.</p>
    </div>

    <!-- Cycle toggle -->
    <?php if (!empty($monthly) && !empty($yearly)): ?>
    <div class="sr-toggle-wrap">
        <button class="sr-toggle-btn <?php echo $defaultCycle === 'Monthly' ? 'active' : ''; ?>" id="srToggleMonthly" onclick="switchCycle('Monthly')">Monthly</button>
        <button class="sr-toggle-btn <?php echo $defaultCycle === 'Yearly'  ? 'active' : ''; ?>" id="srToggleYearly" onclick="switchCycle('Yearly')">
            Yearly
            <?php if ($yearlySavingPct > 0): ?>
                <span class="sr-save-badge">SAVE ~<?php echo $yearlySavingPct; ?>%</span>
            <?php endif; ?>
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
                    <?php
                    /* Enterprise always carries the Popular badge */
                    if ($tier === 'ent'): ?>
                        <span class="sr-badge sr-badge-popular">&#9733; Popular</span>
                    <?php endif;

                    /* Status badge — applies to every tier including Enterprise */
                    if ($wasTrial):
                        if ($isCurrent): ?>
                            <span class="sr-badge sr-badge-trial">Trial</span>
                        <?php endif;
                    else:
                        if ($isCurrent): ?>
                            <span class="sr-badge sr-badge-previous">Previous Plan</span>
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

            <?php $upgradeType = $isCurrent ? 'renew' : ($isDowngrade ? 'downgrade' : 'upgrade'); ?>
            <button class="sr-pay-btn <?php echo $tierBtn; ?>"
                    data-plan-uid="<?php echo (int)$p->SectorPlanUID; ?>"
                    data-plan-name="<?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>"
                    data-plan-price="<?php echo number_format((float)$p->Price, 2, '.', ''); ?>"
                    data-cycle="<?php echo htmlspecialchars($billingCycle, ENT_QUOTES); ?>"
                    data-taxable="<?php echo number_format($taxableAmt, 2, '.', ''); ?>"
                    data-tax="<?php echo number_format($taxAmt, 2, '.', ''); ?>"
                    data-max-users="<?php echo (int)$p->MaxUsers; ?>"
                    data-max-branches="<?php echo (int)$p->MaxBranches; ?>"
                    data-duration-days="<?php echo (int)$p->DurationDays; ?>"
                    data-upgrade-type="<?php echo $upgradeType; ?>">
                <span class="sr-btn-text"><?php echo $btnLabel; ?></span>
                <span class="sr-spinner"></span>
            </button>

            <?php if ($isCurrent): ?>
            <div class="sr-prev-plan-strip">
                <i class="bx bx-history"></i> Your Previous Plan
            </div>
            <?php endif; ?>

        </div>
        <?php endforeach; ?>

    </div>


</div>

<!-- Plan change confirmation modal -->
<div class="sr-modal-overlay" id="srPlanModal" hidden>
    <div class="sr-modal-box" id="srModalBox">
        <div class="sr-modal-hero" id="srModalHero"></div>
        <h3 class="sr-modal-title" id="srModalTitle"></h3>
        <p class="sr-modal-lead" id="srModalLead"></p>
        <ul class="sr-modal-list" id="srModalList"></ul>
        <p class="sr-modal-note" id="srModalNote" hidden></p>
        <div class="sr-modal-actions" id="srModalActions"></div>
    </div>
</div>

<!-- Full-page payment processing overlay -->
<div class="sr-pay-overlay" id="srPayOverlay">
    <div class="sr-pay-logo-wrap">
        <div class="sr-ring-spin"></div>
        <img src="https://pub-bb40942a33344637936ade1f3800ff8b.r2.dev/Global/favicon_io/android-chrome-512x512-1.png"
             class="sr-pay-logo-img" alt="Logo">
    </div>
    <p class="sr-pay-overlay-msg" id="srOverlayMsg">Preparing checkout</p>
    <p class="sr-pay-overlay-stage" id="srOverlayStage">Loading confirmation page…</p>
    <p class="sr-pay-overlay-sub">Do not close or refresh this page</p>
</div>


<script>
var _sid                 = <?php echo json_encode($sid); ?>;
var _activeCycle         = 'Monthly';
var _defaultCycle        = <?php echo json_encode($defaultCycle); ?>;
var _currentMaxUsers     = <?php echo json_encode($currentMaxUsers); ?>;
var _currentMaxBranches  = <?php echo json_encode($currentMaxBranches); ?>;
var _currentDurationDays = <?php echo json_encode($currentDurationDays); ?>;
var _pendingPlanUID      = 0;
var _pendingBtn          = null;

/**
 * @param {string} cycle
 * @returns {void}
 */
function switchCycle(cycle) {
    _activeCycle = cycle;
    $('.sr-plan-card').each(function() {
        $(this).toggle($(this).data('cycle') === cycle);
    });
    /* cards use display:flex internally — restore flex after toggle() sets display:block */
    $('.sr-plan-card[data-cycle="' + cycle + '"]').css('display', 'flex');
    $('#srToggleMonthly').toggleClass('active', cycle === 'Monthly');
    $('#srToggleYearly').toggleClass('active',  cycle === 'Yearly');
}

/**
 * Show the full-page processing overlay.
 * @param {string} msg
 * @param {string} stage
 * @returns {void}
 */
function _showOverlay(msg, stage) {
    $('#srOverlayMsg').text(msg);
    $('#srOverlayStage').text(stage);
    $('#srPayOverlay').addClass('show');
}

/**
 * @returns {void}
 */
function _hideOverlay() {
    $('#srPayOverlay').removeClass('show');
}

/**
 * Format a plan limit value (0 = Unlimited).
 * @param {number} val
 * @returns {string}
 */
function _fmtLimit(val) {
    return val === 0 ? 'Unlimited' : String(val);
}

/**
 * Close and reset the plan change modal.
 * @returns {void}
 */
function _closeModal() {
    $('#srPlanModal').prop('hidden', true);
    _pendingPlanUID = 0;
    _pendingBtn     = null;
}

/**
 * Show the upgrade or downgrade confirmation modal before redirecting.
 * @param {jQuery} $btn
 * @returns {void}
 */
function showPlanModal($btn) {
    var type         = $btn.data('upgrade-type');
    var planName     = $btn.data('plan-name');
    var newUsers     = parseInt($btn.data('max-users'),     10) || 0;
    var newBranches  = parseInt($btn.data('max-branches'),  10) || 0;
    var newDuration  = parseInt($btn.data('duration-days'), 10) || 0;

    _pendingPlanUID = parseInt($btn.data('plan-uid'), 10);
    _pendingBtn     = $btn;

    var $hero    = $('#srModalHero');
    var $title   = $('#srModalTitle');
    var $lead    = $('#srModalLead');
    var $list    = $('#srModalList');
    var $note    = $('#srModalNote');
    var $actions = $('#srModalActions');

    $list.empty();
    $actions.empty();
    $note.prop('hidden', true);

    if (type === 'downgrade') {
        /* ── Downgrade modal ───────────────────────────── */
        $hero.attr('class', 'sr-modal-hero sr-modal-hero--warn').html('<i class="bx bx-error-alt"></i>');
        $title.text('Downgrading Your Plan');
        $lead.text('You are switching to a plan with lower limits. Some features and data may become restricted.');

        var items = [
            {
                icon : 'bx bx-store',
                label: 'Branches',
                from : _fmtLimit(_currentMaxBranches),
                to   : _fmtLimit(newBranches),
                note : 'Extra branches may become inaccessible.'
            },
            {
                icon : 'bx bx-user',
                label: 'Users',
                from : _fmtLimit(_currentMaxUsers),
                to   : _fmtLimit(newUsers),
                note : 'Extra team members may lose access.'
            },
            {
                icon : 'bx bx-calendar-check',
                label: 'Duration',
                from : _currentDurationDays + ' days',
                to   : newDuration + ' days',
                note : ''
            },
            {
                icon : 'bx bx-lock-open',
                label: 'Modules',
                from : null,
                to   : null,
                note : 'Some features on your current plan may be disabled.'
            }
        ];

        $.each(items, function(_, item) {
            var arrowHtml = item.from !== null
                ? '<b>' + item.from + '</b><span class="sr-ml-arrow">→</span><b>' + item.to + '</b>'
                : '';
            var noteHtml = item.note ? ' <span style="color:rgba(148,163,184,0.55)">' + item.note + '</span>' : '';
            $list.append(
                '<li>' +
                    '<i class="sr-ml-icon ' + item.icon + '"></i>' +
                    '<span class="sr-ml-text">' +
                        '<b>' + item.label + ':</b> ' + arrowHtml + noteHtml +
                    '</span>' +
                '</li>'
            );
        });

        $note.text('No data is permanently deleted — access is restored if you upgrade again.').prop('hidden', false);

        $actions.attr('class', 'sr-modal-actions');
        $actions.append(
            $('<button class="sr-modal-btn-close" id="srModalBtnClose">Close</button>'),
            $('<button class="sr-modal-btn-proceed" id="srModalBtnProceed">Continue to Checkout →</button>')
        );

    } else {
        /* ── Upgrade modal (also covers free→paid first-time renewal) ── */
        $hero.attr('class', 'sr-modal-hero sr-modal-hero--up').html('<i class="bx bx-rocket"></i>');
        $title.text('Upgrading to ' + planName);
        $lead.text('Great choice! Here\'s what you\'ll get with your new plan:');

        var gainItems = [
            { icon: 'bx bx-store',          text: 'Up to <b>' + _fmtLimit(newBranches) + '</b> branches' },
            { icon: 'bx bx-user',            text: 'Up to <b>' + _fmtLimit(newUsers)   + '</b> users' },
            { icon: 'bx bx-calendar-check',  text: '<b>' + newDuration + ' days</b> of access' },
            { icon: 'bx bx-check-shield',    text: 'All modules unlocked for this plan' }
        ];

        $.each(gainItems, function(_, item) {
            $list.append(
                '<li>' +
                    '<i class="sr-ml-icon ' + item.icon + '" style="color:#a78bfa"></i>' +
                    '<span class="sr-ml-text">' + item.text + '</span>' +
                '</li>'
            );
        });

        $actions.attr('class', 'sr-modal-actions sr-modal-actions--single');
        $actions.append(
            $('<button class="sr-modal-btn-proceed" id="srModalBtnProceed">Continue to Checkout →</button>'),
            $('<p class="sr-modal-esc-hint">Press Esc to cancel</p>')
        );
    }

    $('#srPlanModal').prop('hidden', false);
}

/**
 * POST to prepareCheckout, then redirect to billing/checkout.
 * @param {number} sectorPlanUID
 * @param {jQuery} $btn  — the clicked button, disabled during request
 * @returns {void}
 */
function prepareCheckout(sectorPlanUID, $btn) {
    _closeModal();
    $btn.prop('disabled', true);
    _showOverlay('Preparing checkout', 'Loading confirmation page…');

    var data = { sid: _sid, sector_plan_uid: sectorPlanUID };
    var csrfInput = $('input[name^="csrf"]');
    if (csrfInput.length) data[csrfInput.attr('name')] = csrfInput.val();

    ajaxLoading(0);
    $.ajax({
        url     : '<?php echo base_url('subscription/renew/prepareCheckout'); ?>',
        method  : 'POST',
        data    : data,
        dataType: 'json',
        success : function(res) {
            if (res.Error) {
                _hideOverlay();
                $btn.prop('disabled', false);
                showToastNotification(res.Message || 'Could not prepare checkout. Please try again.', 'error');
                return;
            }
            window.location.href = res.Redirect;
        },
        error: function(xhr, status) {
            _hideOverlay();
            $btn.prop('disabled', false);
            showToastNotification('Connection failed (' + status + '). Please try again.', 'error');
        },
        complete: function() { ajaxLoading(1); }
    });
}

$(function() {
    switchCycle(_defaultCycle);

    /* Plan button → show confirmation modal (or go direct if same plan) */
    $(document).on('click', '.sr-pay-btn', function() {
        var type = $(this).data('upgrade-type');
        if (type === 'renew') {
            /* Same plan renew — skip modal */
            prepareCheckout(parseInt($(this).data('plan-uid'), 10), $(this));
        } else {
            showPlanModal($(this));
        }
    });

    /* Modal: proceed button */
    $(document).on('click', '#srModalBtnProceed', function() {
        if (_pendingPlanUID && _pendingBtn) {
            prepareCheckout(_pendingPlanUID, _pendingBtn);
        }
    });

    /* Modal: close button (downgrade modal only) */
    $(document).on('click', '#srModalBtnClose', function() {
        _closeModal();
    });

    /* Modal: close on overlay backdrop click */
    $(document).on('click', '#srPlanModal', function(e) {
        if ($(e.target).is('#srPlanModal')) _closeModal();
    });

    /* ESC closes the modal */
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && !$('#srPlanModal').prop('hidden')) {
            _closeModal();
        }
    });
});

/* ── Back to Login — delete renewal token then redirect to logout ───── */
$('#srBackBtn').on('click', function() {
    $(this).prop('disabled', true);

    $('#srOverlayMsg').text('Logging out');
    $('#srOverlayStage').text('Please wait…');
    $('#srPayOverlay .sr-pay-overlay-sub').text('You will be redirected to the login page');
    $('#srPayOverlay').addClass('show');

    var data = { sid: '<?php echo htmlspecialchars($sid ?? '', ENT_QUOTES); ?>' };
    var csrfInput = $('input[name^="csrf"]');
    if (csrfInput.length) data[csrfInput.attr('name')] = csrfInput.val();

    ajaxLoading(0);
    $.ajax({
        url   : '<?php echo base_url('subscription/renew/cancelToken'); ?>',
        method: 'POST',
        data  : data,
    }).always(function() {
        window.location.href = '<?php echo base_url('logout'); ?>';
    });
});
</script>
