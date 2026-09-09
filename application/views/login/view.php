<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('login/header'); ?>

<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

.lr-root {
    display: flex;
    min-height: 100vh;
    background: #040b18;
    font-family: 'Public Sans', sans-serif;
    overflow: hidden;
}

/* ── LEFT BRAND PANEL — Time-of-Day Adaptive ──────────────── */
.lr-brand {
    position: relative;
    width: 50%;
    min-height: 100vh;
    overflow: hidden;
    background: #01020a;
    transition: background 1.2s ease;
}

/* Gradient fallbacks when image is unavailable */
.lr-brand.time-morning { background: linear-gradient(160deg, #1a0800 0%, #6b3510 45%, #d4842a 100%); }
.lr-brand.time-day     { background: linear-gradient(160deg, #071a36 0%, #0e4a7a 55%, #2596d0 100%); }
.lr-brand.time-evening { background: linear-gradient(160deg, #1a0510 0%, #8b2250 55%, #f07040 100%); }
.lr-brand.time-night   { background: linear-gradient(160deg, #01020a 0%, #080c22 55%, #10153a 100%); }

.lr-brand::after {
    content: '';
    position: absolute;
    top: 0; right: 0;
    width: 1px; height: 100%;
    background: linear-gradient(to bottom, transparent 0%, rgba(100,150,190,0.45) 35%, rgba(100,150,190,0.45) 65%, transparent 100%);
    z-index: 20;
}

/* Image background */
.lr-img-bg {
    position: absolute;
    inset: 0;
    width: 100%; height: 100%;
    object-fit: cover;
    transform: translate3d(0, 0, 0) scale(1.06);
    transition: transform 0.1s linear;
    will-change: transform;
    z-index: 0;
}

/* Multi-layer depth overlay */
.lr-video-overlay {
    position: absolute;
    inset: 0;
    background:
        linear-gradient(to right,  rgba(0,0,0,0.18) 0%, transparent 30%),
        linear-gradient(to bottom, rgba(0,0,0,0.5)  0%, transparent 38%),
        linear-gradient(to top,    rgba(0,0,0,0.65) 0%, transparent 42%);
    z-index: 1;
}

/* Glassmorphism card */
.lr-glass-card {
    position: absolute;
    bottom: 40px;
    left: 40px;
    right: 40px;
    backdrop-filter: blur(24px) saturate(180%);
    -webkit-backdrop-filter: blur(24px) saturate(180%);
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 20px;
    padding: 28px 32px;
    box-shadow:
        0 20px 40px rgba(0, 0, 0, 0.35),
        inset 0 1px 0 rgba(255, 255, 255, 0.1);
    z-index: 10;
    transition: transform 0.12s ease-out;
    will-change: transform;
    animation: lrCardFloat 0.9s cubic-bezier(0.22, 1, 0.36, 1) 0.35s both;
}

@keyframes lrCardFloat {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}

.lr-glass-greeting {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: #94a3b8;
    margin-bottom: 10px;
}

.lr-glass-clock {
    font-size: 40px;
    font-weight: 700;
    color: #f8fafc;
    letter-spacing: -0.5px;
    font-variant-numeric: tabular-nums;
    line-height: 1;
    margin-bottom: 18px;
}

.lr-glass-status {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: #64748b;
    font-weight: 500;
}

.lr-status-dot {
    width: 7px; height: 7px;
    border-radius: 50%;
    background: #22c55e;
    box-shadow: 0 0 6px rgba(34, 197, 94, 0.7);
    flex-shrink: 0;
    animation: lr-pulse-dot 2s ease-in-out infinite;
}

@keyframes lr-pulse-dot {
    0%, 100% { opacity: 1;   box-shadow: 0 0 6px  rgba(34,197,94,0.7); }
    50%       { opacity: 0.6; box-shadow: 0 0 12px rgba(34,197,94,0.35); }
}

/* Logo + heading inline, vertically centered */
.lr-head-row {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 8px;
}
.lr-form-head-logo {
    width: 44px; height: 44px;
    border-radius: 12px;
    flex-shrink: 0;
    box-shadow: 0 0 0 1px rgba(245,158,11,0.28), 0 4px 20px rgba(245,158,11,0.16);
}

/* ── RIGHT FORM PANEL ──────────────────────────────────────── */
.lr-form-panel {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 32px;
    background: #060e20;
    position: relative;
    overflow: hidden;
}

.lr-form-panel::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse 80% 60% at 50% 0%, rgba(245,158,11,0.06) 0%, transparent 60%);
    pointer-events: none;
}

.lr-form-card {
    width: 100%;
    max-width: 400px;
    position: relative;
    z-index: 1;
    animation: lrFadeUp 0.4s ease-out 0.1s both;
}

@keyframes lrFadeUp {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* Mobile logo (hidden on desktop) */
.lr-mobile-logo {
    display: none;
    text-align: center;
    margin-bottom: 32px;
}

.lr-mobile-logo img {
    width: 56px; height: 56px;
    border-radius: 14px;
    box-shadow: 0 0 0 1px rgba(245,158,11,0.3);
    margin-bottom: 12px;
}

.lr-mobile-logo h2 {
    font-size: 22px;
    font-weight: 800;
    color: #f1f5f9;
}

.lr-mobile-logo h2 span { color: #f59e0b; }

.lr-form-head {
    margin-bottom: 36px;
}

.lr-form-head h3 {
    font-size: 28px;
    font-weight: 700;
    color: #f1f5f9;
    margin-bottom: -6px;
    letter-spacing: -0.3px;
}

.lr-form-head p {
    color: #94a3b8;
    font-size: 14px;
}

/* Input groups */
.lr-field {
    margin-bottom: 22px;
}

.lr-field label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #94a3b8;
    margin-bottom: 8px;
    letter-spacing: 0.3px;
    text-transform: uppercase;
}

.lr-input-wrap {
    position: relative;
}

.lr-input-wrap .lr-input-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #475569;
    font-size: 18px;
    pointer-events: none;
    transition: color 0.25s;
}

.lr-input-wrap input {
    width: 100%;
    background: rgba(255,255,255,0.04);
    border: 1.5px solid rgba(255,255,255,0.08);
    border-radius: 12px;
    padding: 14px 16px 14px 42px;
    color: #f1f5f9;
    font-size: 14px;
    font-family: inherit;
    outline: none;
    transition: all 0.2s ease-in-out;
    -webkit-appearance: none;
    appearance: none;
}

.lr-input-wrap input:focus {
    background: rgba(245,158,11,0.05);
    border-color: #f59e0b;
    box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.15);
}

/* ── Browser autofill override ── */
.lr-input-wrap input:-webkit-autofill,
.lr-input-wrap input:-webkit-autofill:hover,
.lr-input-wrap input:-webkit-autofill:focus {
    -webkit-text-fill-color: #f1f5f9;
    -webkit-box-shadow: 0 0 0px 1000px #1a1a24 inset;
    caret-color: #f1f5f9;
    transition: background-color 5000s ease-in-out 0s;
}

.lr-input-wrap input:focus + .lr-input-icon,
.lr-input-wrap:focus-within .lr-input-icon {
    color: #f59e0b;
}

.lr-input-wrap input::placeholder { color: #94a3b8; opacity: 1; }
.lr-input-wrap input.form-control::placeholder { color: #94a3b8 !important; opacity: 1; }

/* Fix browser autofill — forces dark background + light text so autofilled values are readable */
.lr-input-wrap input:-webkit-autofill,
.lr-input-wrap input:-webkit-autofill:hover,
.lr-input-wrap input:-webkit-autofill:focus,
.lr-input-wrap input:-webkit-autofill:active {
    -webkit-text-fill-color: #f1f5f9;
    -webkit-box-shadow: 0 0 0px 1000px #0d1829 inset;
    transition: background-color 5000s ease-in-out 0s;
    caret-color: #f1f5f9;
    border-color: rgba(245,158,11,0.3) !important;
}

/* Password toggle */
.lr-pw-toggle {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #475569;
    cursor: pointer;
    font-size: 18px;
    padding: 2px;
    transition: color 0.25s;
    display: flex; align-items: center;
}

.lr-pw-toggle:hover { color: #f59e0b; }

.lr-input-wrap input[type="password"],
.lr-input-wrap input[type="text"] {
    padding-right: 44px;
}

/* Remember / Forgot row */
.lr-bottom-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 28px;
    margin-top: -8px;
}

.lr-forgot {
    font-size: 13px;
    color: #f59e0b;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s;
}

.lr-forgot:hover { color: #fbbf24; }

/* Submit button */
.lr-btn {
    width: 100%;
    padding: 14px;
    border: none;
    border-radius: 12px;
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: #040b18;
    font-size: 15px;
    font-weight: 700;
    letter-spacing: 0.3px;
    cursor: pointer;
    position: relative;
    overflow: hidden;
    transition: all 0.3s;
    box-shadow: 0 4px 12px rgba(245,158,11,0.3);
    font-family: inherit;
}

.lr-btn::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    opacity: 0;
    transition: opacity 0.3s;
}

.lr-btn:hover::before { opacity: 1; }
.lr-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 20px rgba(245,158,11,0.35); }
.lr-btn:active { transform: translateY(0); box-shadow: 0 4px 12px rgba(245,158,11,0.25); }

.lr-btn span { position: relative; z-index: 1; display: flex; align-items: center; justify-content: center; gap: 8px; }

/* Ripple effect */
.lr-btn .lr-ripple {
    position: absolute;
    border-radius: 50%;
    background: rgba(255,255,255,0.3);
    transform: scale(0);
    animation: ripple 0.6s linear;
    pointer-events: none;
}

@keyframes ripple {
    to { transform: scale(4); opacity: 0; }
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to   { transform: rotate(360deg); }
}

/* Alert */
.lr-alerts { margin-bottom: 20px; }

.lr-alerts .alert {
    background: rgba(239,68,68,0.1);
    border: 1px solid rgba(239,68,68,0.25);
    border-radius: 10px;
    color: #fca5a5;
    padding: 11px 14px;
    font-size: 13px;
}

.lr-alerts .alert-success {
    background: rgba(16,185,129,0.1);
    border-color: rgba(16,185,129,0.25);
    color: #6ee7b7;
}

/* Email sub-line shown only for OAuth redirect errors */
.lr-error-sub {
    margin-top: 6px;
    font-size: 12px;
    color: rgba(252,165,165,0.75);
    word-break: break-all;
}

/* Resend verification link inside alert */
.lr-resend-wrap {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid rgba(252,165,165,0.2);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}
.lr-resend-hint {
    font-size: 12px;
    color: rgba(252,165,165,0.7);
}
.lr-resend-btn {
    flex-shrink: 0;
    padding: 5px 12px;
    border: 1px solid rgba(252,165,165,0.4);
    border-radius: 6px;
    background: transparent;
    color: #fca5a5;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.15s, border-color 0.15s;
}
.lr-resend-btn:hover:not(:disabled) {
    background: rgba(252,165,165,0.12);
    border-color: rgba(252,165,165,0.7);
}
.lr-resend-btn:disabled { opacity: 0.5; cursor: not-allowed; }

/* Toast notification */
.lr-toast {
    position: fixed;
    bottom: 28px;
    left: 50%;
    transform: translateX(-50%) translateY(16px);
    background: #0f1f3a;
    border: 1px solid rgba(52,211,153,0.4);
    color: #6ee7b7;
    padding: 11px 20px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 500;
    white-space: nowrap;
    opacity: 0;
    transition: opacity 0.25s ease, transform 0.25s ease;
    z-index: 9999;
    pointer-events: none;
}
.lr-toast.lr-toast--error {
    border-color: rgba(248,113,113,0.4);
    color: #fca5a5;
}
.lr-toast.lr-toast--show {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
}

/* Divider */
.lr-divider {
    height: 1px;
    background: rgba(255,255,255,0.05);
    margin: 28px 0;
    position: relative;
}

/* Social divider */
.lr-social-divider {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 24px 0 16px;
    color: #94a3b8;
    font-size: 12px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}

.lr-social-divider::before,
.lr-social-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: rgba(255,255,255,0.06);
}

/* Social buttons */
.lr-social-btns {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 24px;
}

.lr-social-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    padding: 11px 16px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s;
    border: 1.5px solid rgba(255,255,255,0.08);
    background: rgba(255,255,255,0.04);
    color: #cbd5e1;
    font-family: inherit;
}

.lr-social-btn:hover {
    background: rgba(255,255,255,0.08);
    border-color: rgba(255,255,255,0.14);
    color: #f1f5f9;
    transform: translateY(-1px);
    text-decoration: none;
}

.lr-social-icon {
    width: 18px;
    height: 18px;
    flex-shrink: 0;
}

/* Footer note */
.lr-footer-note {
    text-align: center;
    font-size: 12px;
    color: #334155;
    margin-top: 28px;
}

.lr-footer-note span { color: #f59e0b; }

.lr-signup-note { margin-bottom: -8px; }
.lr-signup-note a { color: #60a5fa; text-decoration: none; font-weight: 500; }
.lr-signup-note a:hover { text-decoration: underline; }


/* ── Language switcher ─────────────────────────────────────── */
.lr-lang-switch {
    position: absolute;
    top: 24px;
    right: 24px;
    z-index: 10;
}

.lr-lang-trigger {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 7px 13px;
    background: rgba(255, 255, 255, 0.06);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 100px;
    color: #94a3b8;
    font-size: 13px;
    font-weight: 500;
    font-family: inherit;
    cursor: pointer;
    transition: all 0.2s ease-in-out;
    line-height: 1;
}

.lr-lang-trigger:hover {
    background: rgba(255, 255, 255, 0.1);
    color: #f1f5f9;
    border-color: rgba(255, 255, 255, 0.16);
}

.lr-lang-chevron {
    font-size: 14px;
    transition: transform 0.2s ease;
    display: flex;
}

.lr-lang-switch.open .lr-lang-chevron { transform: rotate(180deg); }

.lr-lang-dropdown {
    position: absolute;
    top: calc(100% + 6px);
    right: 0;
    min-width: 140px;
    background: rgba(8, 16, 38, 0.92);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.45);
    display: none;
}

.lr-lang-switch.open .lr-lang-dropdown {
    display: block;
    animation: lrDropIn 0.18s ease-out both;
}

@keyframes lrDropIn {
    from { opacity: 0; transform: translateY(-6px); }
    to   { opacity: 1; transform: translateY(0); }
}

.lr-lang-opt {
    display: block;
    width: 100%;
    padding: 10px 16px;
    background: none;
    border: none;
    color: #94a3b8;
    font-size: 13px;
    font-weight: 500;
    font-family: inherit;
    text-align: left;
    cursor: pointer;
    transition: all 0.15s;
}

.lr-lang-opt:hover { background: rgba(255,255,255,0.06); color: #f1f5f9; }
.lr-lang-opt.lr-lang-active { color: #f59e0b; }

/* ── RESPONSIVE ────────────────────────────────────────────── */
@media (max-width: 900px) {
    .lr-brand { display: none; }
    .lr-form-panel { width: 100%; background: #040b18; padding: 40px 24px; }
    .lr-mobile-logo { display: block; }
    .lr-form-card { max-width: 440px; }
}

@media (max-width: 480px) {
    .lr-form-panel { padding: 32px 20px; }
    .lr-form-head h3 { font-size: 24px; }
    .lr-btn { padding: 13px; font-size: 14px; }
}
</style>

<div class="lr-root">

    <!-- ── LEFT: Time-of-Day Adaptive Panel ── -->
    <div class="lr-brand time-night" id="lrBrand">
        <img id="lrBgImg" class="lr-img-bg" src="" alt="">
        <div class="lr-video-overlay"></div>
        <div class="lr-glass-card" id="lrGlassCard">
            <div class="lr-glass-greeting" id="lrGreeting">Good Evening</div>
            <div class="lr-glass-clock" id="lrLiveClock">--:--:-- --</div>
            <div class="lr-glass-status">
                <span class="lr-status-dot"></span>
                System Operational &bull; 99.9% Uptime
            </div>
        </div>
        <div style="display:none" class="lr-agri-scene">
            <svg viewBox="0 0 900 195" preserveAspectRatio="xMidYMax meet" xmlns="http://www.w3.org/2000/svg">
                <!-- Far hills -->
                <path d="M0 195 L0 128 Q150 65 300 108 Q450 150 600 78 Q750 8 900 58 L900 195 Z" fill="#040f06"/>
                <!-- Mid hills -->
                <path d="M0 195 L0 152 Q100 116 220 142 Q340 168 460 132 Q580 96 700 126 Q800 150 900 138 L900 195 Z" fill="#061508"/>
                <!-- Foreground ground -->
                <path d="M0 195 L0 172 Q220 163 450 168 Q660 173 900 165 L900 195 Z" fill="#08200b"/>

                <!-- Wheat stalks — LEFT -->
                <g stroke="#13381a" stroke-linecap="round" fill="none">
                    <line x1="42" y1="190" x2="44" y2="163" stroke-width="1.5"/><ellipse cx="44" cy="161" rx="3" ry="5" fill="#13381a" stroke="none"/>
                    <line x1="44" y1="163" x2="39" y2="168" stroke-width="1"/><line x1="44" y1="163" x2="49" y2="168" stroke-width="1"/>
                    <line x1="44" y1="169" x2="39" y2="174" stroke-width="1"/><line x1="44" y1="169" x2="49" y2="174" stroke-width="1"/>

                    <line x1="72" y1="190" x2="74" y2="157" stroke-width="1.5"/><ellipse cx="74" cy="155" rx="3" ry="5" fill="#13381a" stroke="none"/>
                    <line x1="74" y1="157" x2="69" y2="162" stroke-width="1"/><line x1="74" y1="157" x2="79" y2="162" stroke-width="1"/>
                    <line x1="74" y1="163" x2="69" y2="168" stroke-width="1"/><line x1="74" y1="163" x2="79" y2="168" stroke-width="1"/>
                    <line x1="74" y1="169" x2="69" y2="174" stroke-width="1"/><line x1="74" y1="169" x2="79" y2="174" stroke-width="1"/>

                    <line x1="100" y1="190" x2="102" y2="161" stroke-width="1.5"/><ellipse cx="102" cy="159" rx="3" ry="5" fill="#13381a" stroke="none"/>
                    <line x1="102" y1="161" x2="97" y2="166" stroke-width="1"/><line x1="102" y1="161" x2="107" y2="166" stroke-width="1"/>
                    <line x1="102" y1="167" x2="97" y2="172" stroke-width="1"/><line x1="102" y1="167" x2="107" y2="172" stroke-width="1"/>

                    <line x1="128" y1="190" x2="130" y2="166" stroke-width="1.5"/><ellipse cx="130" cy="164" rx="2.5" ry="4" fill="#13381a" stroke="none"/>
                    <line x1="130" y1="166" x2="125" y2="171" stroke-width="1"/><line x1="130" y1="166" x2="135" y2="171" stroke-width="1"/>
                    <line x1="130" y1="172" x2="125" y2="177" stroke-width="1"/><line x1="130" y1="172" x2="135" y2="177" stroke-width="1"/>

                    <line x1="155" y1="190" x2="157" y2="159" stroke-width="1.5"/><ellipse cx="157" cy="157" rx="3" ry="5" fill="#13381a" stroke="none"/>
                    <line x1="157" y1="159" x2="152" y2="164" stroke-width="1"/><line x1="157" y1="159" x2="162" y2="164" stroke-width="1"/>
                    <line x1="157" y1="165" x2="152" y2="170" stroke-width="1"/><line x1="157" y1="165" x2="162" y2="170" stroke-width="1"/>
                    <line x1="157" y1="171" x2="152" y2="176" stroke-width="1"/><line x1="157" y1="171" x2="162" y2="176" stroke-width="1"/>

                    <line x1="183" y1="190" x2="185" y2="162" stroke-width="1.5"/><ellipse cx="185" cy="160" rx="3" ry="5" fill="#13381a" stroke="none"/>
                    <line x1="185" y1="162" x2="180" y2="167" stroke-width="1"/><line x1="185" y1="162" x2="190" y2="167" stroke-width="1"/>
                    <line x1="185" y1="168" x2="180" y2="173" stroke-width="1"/><line x1="185" y1="168" x2="190" y2="173" stroke-width="1"/>
                    <line x1="185" y1="174" x2="180" y2="179" stroke-width="1"/><line x1="185" y1="174" x2="190" y2="179" stroke-width="1"/>

                    <line x1="213" y1="190" x2="215" y2="164" stroke-width="1.5"/><ellipse cx="215" cy="162" rx="2.5" ry="4.5" fill="#13381a" stroke="none"/>
                    <line x1="215" y1="164" x2="210" y2="169" stroke-width="1"/><line x1="215" y1="164" x2="220" y2="169" stroke-width="1"/>
                    <line x1="215" y1="170" x2="210" y2="175" stroke-width="1"/><line x1="215" y1="170" x2="220" y2="175" stroke-width="1"/>

                    <line x1="248" y1="190" x2="250" y2="161" stroke-width="1.5"/><ellipse cx="250" cy="159" rx="3" ry="5" fill="#13381a" stroke="none"/>
                    <line x1="250" y1="161" x2="245" y2="166" stroke-width="1"/><line x1="250" y1="161" x2="255" y2="166" stroke-width="1"/>
                    <line x1="250" y1="167" x2="245" y2="172" stroke-width="1"/><line x1="250" y1="167" x2="255" y2="172" stroke-width="1"/>
                    <line x1="250" y1="173" x2="245" y2="178" stroke-width="1"/><line x1="250" y1="173" x2="255" y2="178" stroke-width="1"/>

                    <line x1="278" y1="190" x2="280" y2="165" stroke-width="1.5"/><ellipse cx="280" cy="163" rx="2.5" ry="4" fill="#13381a" stroke="none"/>
                    <line x1="280" y1="165" x2="275" y2="170" stroke-width="1"/><line x1="280" y1="165" x2="285" y2="170" stroke-width="1"/>
                    <line x1="280" y1="171" x2="275" y2="176" stroke-width="1"/><line x1="280" y1="171" x2="285" y2="176" stroke-width="1"/>

                    <line x1="308" y1="190" x2="310" y2="158" stroke-width="1.5"/><ellipse cx="310" cy="156" rx="3" ry="5" fill="#13381a" stroke="none"/>
                    <line x1="310" y1="158" x2="305" y2="163" stroke-width="1"/><line x1="310" y1="158" x2="315" y2="163" stroke-width="1"/>
                    <line x1="310" y1="164" x2="305" y2="169" stroke-width="1"/><line x1="310" y1="164" x2="315" y2="169" stroke-width="1"/>
                    <line x1="310" y1="170" x2="305" y2="175" stroke-width="1"/><line x1="310" y1="170" x2="315" y2="175" stroke-width="1"/>
                </g>

                <!-- Windmill (center x=450) -->
                <polygon points="438,193 462,193 455,108 445,108" fill="#071208" stroke="#13381a" stroke-width="0.5"/>
                <rect x="436" y="108" width="28" height="5" rx="2" fill="#0d2010"/>
                <circle cx="450" cy="110" r="5" fill="#1a4520"/>
                <g fill="#1a4520" opacity="0.88">
                    <rect x="447" y="74" width="6" height="34" rx="3"/>
                    <rect x="450" y="107" width="34" height="6" rx="3"/>
                    <rect x="447" y="110" width="6" height="34" rx="3"/>
                    <rect x="416" y="107" width="34" height="6" rx="3"/>
                    <animateTransform attributeName="transform" type="rotate"
                        from="0 450 110" to="360 450 110" dur="7s" repeatCount="indefinite"/>
                </g>

                <!-- Wheat stalks — RIGHT -->
                <g stroke="#13381a" stroke-linecap="round" fill="none">
                    <line x1="590" y1="190" x2="592" y2="164" stroke-width="1.5"/><ellipse cx="592" cy="162" rx="2.5" ry="4" fill="#13381a" stroke="none"/>
                    <line x1="592" y1="164" x2="587" y2="169" stroke-width="1"/><line x1="592" y1="164" x2="597" y2="169" stroke-width="1"/>
                    <line x1="592" y1="170" x2="587" y2="175" stroke-width="1"/><line x1="592" y1="170" x2="597" y2="175" stroke-width="1"/>

                    <line x1="620" y1="190" x2="622" y2="159" stroke-width="1.5"/><ellipse cx="622" cy="157" rx="3" ry="5" fill="#13381a" stroke="none"/>
                    <line x1="622" y1="159" x2="617" y2="164" stroke-width="1"/><line x1="622" y1="159" x2="627" y2="164" stroke-width="1"/>
                    <line x1="622" y1="165" x2="617" y2="170" stroke-width="1"/><line x1="622" y1="165" x2="627" y2="170" stroke-width="1"/>
                    <line x1="622" y1="171" x2="617" y2="176" stroke-width="1"/><line x1="622" y1="171" x2="627" y2="176" stroke-width="1"/>

                    <line x1="648" y1="190" x2="650" y2="162" stroke-width="1.5"/><ellipse cx="650" cy="160" rx="3" ry="5" fill="#13381a" stroke="none"/>
                    <line x1="650" y1="162" x2="645" y2="167" stroke-width="1"/><line x1="650" y1="162" x2="655" y2="167" stroke-width="1"/>
                    <line x1="650" y1="168" x2="645" y2="173" stroke-width="1"/><line x1="650" y1="168" x2="655" y2="173" stroke-width="1"/>
                    <line x1="650" y1="174" x2="645" y2="179" stroke-width="1"/><line x1="650" y1="174" x2="655" y2="179" stroke-width="1"/>

                    <line x1="676" y1="190" x2="678" y2="165" stroke-width="1.5"/><ellipse cx="678" cy="163" rx="2.5" ry="4.5" fill="#13381a" stroke="none"/>
                    <line x1="678" y1="165" x2="673" y2="170" stroke-width="1"/><line x1="678" y1="165" x2="683" y2="170" stroke-width="1"/>
                    <line x1="678" y1="171" x2="673" y2="176" stroke-width="1"/><line x1="678" y1="171" x2="683" y2="176" stroke-width="1"/>

                    <line x1="706" y1="190" x2="708" y2="161" stroke-width="1.5"/><ellipse cx="708" cy="159" rx="3" ry="5" fill="#13381a" stroke="none"/>
                    <line x1="708" y1="161" x2="703" y2="166" stroke-width="1"/><line x1="708" y1="161" x2="713" y2="166" stroke-width="1"/>
                    <line x1="708" y1="167" x2="703" y2="172" stroke-width="1"/><line x1="708" y1="167" x2="713" y2="172" stroke-width="1"/>
                    <line x1="708" y1="173" x2="703" y2="178" stroke-width="1"/><line x1="708" y1="173" x2="713" y2="178" stroke-width="1"/>

                    <line x1="734" y1="190" x2="736" y2="163" stroke-width="1.5"/><ellipse cx="736" cy="161" rx="3" ry="5" fill="#13381a" stroke="none"/>
                    <line x1="736" y1="163" x2="731" y2="168" stroke-width="1"/><line x1="736" y1="163" x2="741" y2="168" stroke-width="1"/>
                    <line x1="736" y1="169" x2="731" y2="174" stroke-width="1"/><line x1="736" y1="169" x2="741" y2="174" stroke-width="1"/>
                    <line x1="736" y1="175" x2="731" y2="180" stroke-width="1"/><line x1="736" y1="175" x2="741" y2="180" stroke-width="1"/>

                    <line x1="762" y1="190" x2="764" y2="158" stroke-width="1.5"/><ellipse cx="764" cy="156" rx="3" ry="5" fill="#13381a" stroke="none"/>
                    <line x1="764" y1="158" x2="759" y2="163" stroke-width="1"/><line x1="764" y1="158" x2="769" y2="163" stroke-width="1"/>
                    <line x1="764" y1="164" x2="759" y2="169" stroke-width="1"/><line x1="764" y1="164" x2="769" y2="169" stroke-width="1"/>
                    <line x1="764" y1="170" x2="759" y2="175" stroke-width="1"/><line x1="764" y1="170" x2="769" y2="175" stroke-width="1"/>

                    <line x1="790" y1="190" x2="792" y2="166" stroke-width="1.5"/><ellipse cx="792" cy="164" rx="2.5" ry="4" fill="#13381a" stroke="none"/>
                    <line x1="792" y1="166" x2="787" y2="171" stroke-width="1"/><line x1="792" y1="166" x2="797" y2="171" stroke-width="1"/>
                    <line x1="792" y1="172" x2="787" y2="177" stroke-width="1"/><line x1="792" y1="172" x2="797" y2="177" stroke-width="1"/>

                    <line x1="820" y1="190" x2="822" y2="161" stroke-width="1.5"/><ellipse cx="822" cy="159" rx="3" ry="5" fill="#13381a" stroke="none"/>
                    <line x1="822" y1="161" x2="817" y2="166" stroke-width="1"/><line x1="822" y1="161" x2="827" y2="166" stroke-width="1"/>
                    <line x1="822" y1="167" x2="817" y2="172" stroke-width="1"/><line x1="822" y1="167" x2="827" y2="172" stroke-width="1"/>
                    <line x1="822" y1="173" x2="817" y2="178" stroke-width="1"/><line x1="822" y1="173" x2="827" y2="178" stroke-width="1"/>

                    <line x1="850" y1="190" x2="852" y2="164" stroke-width="1.5"/><ellipse cx="852" cy="162" rx="2.5" ry="4.5" fill="#13381a" stroke="none"/>
                    <line x1="852" y1="164" x2="847" y2="169" stroke-width="1"/><line x1="852" y1="164" x2="857" y2="169" stroke-width="1"/>
                    <line x1="852" y1="170" x2="847" y2="175" stroke-width="1"/><line x1="852" y1="170" x2="857" y2="175" stroke-width="1"/>
                </g>
            </svg>
        </div>

    </div>

    <!-- ── RIGHT: Form Panel ── -->
    <div class="lr-form-panel">

        <!-- Language switcher pill -->
        <div class="lr-lang-switch" id="lrLangSwitch">
            <button class="lr-lang-trigger" id="lrLangTrigger" type="button" aria-label="Switch language">
                <span id="lrLangLabel">En</span>
                <i class="bx bx-chevron-down lr-lang-chevron"></i>
            </button>
            <div class="lr-lang-dropdown" id="lrLangDropdown">
                <button class="lr-lang-opt lr-lang-active" data-lang="en" type="button">&#127760; English</button>
                <button class="lr-lang-opt" data-lang="ta" type="button">&#127760; Tamil (த)</button>
            </div>
        </div>

        <div class="lr-form-card">

            <!-- Mobile-only logo -->
            <div class="lr-mobile-logo">
                <?php if (!empty($OrgLogo)): ?>
                <img src="<?php echo htmlspecialchars($OrgLogo); ?>" alt="logo">
                <?php endif; ?>
                <h2><span>RISHIKA 2K</span> ENTERPRISES</h2>
            </div>

            <?php if (!empty($TwoStepEnabled)): ?>
            <!-- ── TWO-STEP FLOW ────────────────────────────────────── -->

            <!-- Step 1: Username -->
            <div id="lrStep1Panel">
                <div class="lr-form-head">
                    <div class="lr-head-row">
                        <?php if (!empty($OrgLogo)): ?>
                        <img class="lr-form-head-logo" src="<?php echo htmlspecialchars($OrgLogo); ?>" alt="<?php echo getSiteConfiguration()->ShortName; ?>">
                        <?php endif; ?>
                        <h3>Sign in</h3>
                    </div>
                    <p>Enter your credentials to continue</p>
                </div>

                <div id="lrStep1Error" class="lr-alerts" style="display:none;">
                    <div class="alert">
                        <span id="lrStep1ErrorMsg"></span>
                        <div id="lrStep1ErrorSub" class="lr-error-sub" style="display:none;"></div>
                        <div id="lrResendWrap" class="lr-resend-wrap" style="display:none;">
                            <span class="lr-resend-hint">Didn't receive it?</span>
                            <button type="button" id="lrResendBtn" class="lr-resend-btn">Resend Email</button>
                        </div>
                    </div>
                </div>

                <div class="lr-field">
                    <label for="UserName">Username or Email</label>
                    <div class="lr-input-wrap">
                        <input type="text" id="UserName" name="UserName" placeholder="Enter your username"
                               autocomplete="username" />
                        <i class="bx bx-user lr-input-icon"></i>
                    </div>
                </div>

                <button type="button" class="lr-btn" id="lrContinueBtn">
                    <span>
                        <i class="bx bx-right-arrow-circle" style="font-size:18px"></i>
                        Continue
                    </span>
                </button>

                <!-- Social sign-in -->
                <div class="lr-social-divider"><span>or continue with</span></div>
                <div class="lr-social-btns">
                    <a href="/auth/google" class="lr-social-btn lr-social-google">
                        <svg class="lr-social-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        Continue with Google
                    </a>
                </div>
            </div>

            <!-- Step 2: Password (hidden until step 1 succeeds) -->
            <div id="lrStep2Panel" style="display:none;">

                <!-- Confirmed-user card -->
                <div class="lr-user-card" id="lrUserCard">
                    <div class="lr-user-avatar">
                        <i class="bx bx-user-circle"></i>
                    </div>
                    <div class="lr-user-info">
                        <div class="lr-user-name" id="lrWelcomeName"></div>
                        <a href="javascript:void(0);" class="lr-not-you" id="lrNotYou">Not you?</a>
                    </div>
                </div>

                <?php $FormAttribute2 = array('id' => 'doLoginForm', 'name' => 'doLoginForm', 'autocomplete' => 'on');
                echo form_open('login/doLoginForm', $FormAttribute2); ?>

                <input type="hidden" id="step2UserName" name="UserName" value="">

                <div id="lrStep2AlertWrap" class="lr-alerts">
                    <?php $this->load->view('login/alerts'); ?>
                </div>

                <div class="lr-field">
                    <label for="UserPassword">Password</label>
                    <div class="lr-input-wrap">
                        <input type="password" id="UserPassword" name="UserPassword"
                               placeholder="Enter your password" autocomplete="current-password" required />
                        <i class="bx bx-lock-alt lr-input-icon"></i>
                        <button type="button" class="lr-pw-toggle" id="pwToggle" aria-label="Toggle password visibility">
                            <i class="bx bx-hide" id="pwIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="lr-bottom-row" style="justify-content:flex-end;">
                    <a href="/forgot-password" class="lr-forgot">Forgot password?</a>
                </div>

                <button type="submit" class="lr-btn" id="lrSubmit">
                    <span>
                        <i class="bx bx-log-in-circle" style="font-size:18px"></i>
                        Sign In
                    </span>
                </button>

                <?php echo form_close(); ?>
            </div>

            <?php else: ?>
            <!-- ── SINGLE-STEP FLOW (default) ───────────────────────── -->

            <div class="lr-form-head">
                <div class="lr-head-row">
                    <?php if (!empty($OrgLogo)): ?>
                    <img class="lr-form-head-logo" src="<?php echo htmlspecialchars($OrgLogo); ?>" alt="<?php echo getSiteConfiguration()->ShortName; ?>">
                    <?php endif; ?>
                    <h3>Sign in</h3>
                </div>
                <p>Enter your credentials to continue</p>
            </div>

            <?php $FormAttribute = array('id' => 'doLoginForm', 'name' => 'doLoginForm', 'autocomplete' => 'on');
            echo form_open('login/doLoginForm', $FormAttribute); ?>

            <div class="lr-alerts">
                <?php $this->load->view('login/alerts'); ?>
            </div>

            <div class="lr-field">
                <label for="UserName">Username or Email</label>
                <div class="lr-input-wrap">
                    <input type="text" id="UserName" name="UserName" placeholder="Enter your username" autocomplete="username" required />
                    <i class="bx bx-user lr-input-icon"></i>
                </div>
            </div>

            <div class="lr-field">
                <label for="UserPassword">Password</label>
                <div class="lr-input-wrap">
                    <input type="password" id="UserPassword" name="UserPassword" placeholder="Enter your password" autocomplete="current-password" required />
                    <i class="bx bx-lock-alt lr-input-icon"></i>
                    <button type="button" class="lr-pw-toggle" id="pwToggle" aria-label="Toggle password visibility">
                        <i class="bx bx-hide" id="pwIcon"></i>
                    </button>
                </div>
            </div>

            <div class="lr-bottom-row" style="justify-content:flex-end;">
                <a href="/forgot-password" class="lr-forgot">Forgot password?</a>
            </div>

            <button type="submit" class="lr-btn" id="lrSubmit">
                <span>
                    <i class="bx bx-log-in-circle" style="font-size:18px"></i>
                    Sign In
                </span>
            </button>

            <?php echo form_close(); ?>

            <!-- Social sign-in -->
            <div class="lr-social-divider">
                <span>or continue with</span>
            </div>

            <div class="lr-social-btns">
                <a href="/auth/google" class="lr-social-btn lr-social-google">
                    <svg class="lr-social-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    Continue with Google
                </a>
            </div>

            <?php endif; ?>

            <p class="lr-footer-note lr-signup-note">
                New here? <a href="/signup">Create your account</a>
            </p>

            <p class="lr-footer-note">&copy; <?php echo date('Y'); ?> <span><?php echo getSiteConfiguration()->ShortName; ?></span>. All rights reserved.</p>
        </div>
    </div>

</div>

<?php $this->load->view('login/footer'); ?>

<style>
/* ── Two-step user card ──────────────────────────────────────── */
.lr-user-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    border-radius: 14px;
    border: 1.5px solid rgba(245,158,11,0.2);
    background: rgba(245,158,11,0.05);
    margin-bottom: 28px;
}

.lr-user-avatar {
    width: 46px;
    height: 46px;
    border-radius: 50%;
    background: rgba(245,158,11,0.12);
    border: 1.5px solid rgba(245,158,11,0.25);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #f59e0b;
    font-size: 26px;
    flex-shrink: 0;
}

.lr-user-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.lr-user-name {
    font-weight: 700;
    font-size: 15px;
    color: #e2e8f0;
    letter-spacing: 0.1px;
}

.lr-not-you {
    font-size: 12px;
    color: #f59e0b;
    text-decoration: none;
    font-weight: 500;
    opacity: 0.8;
    transition: opacity 0.2s;
}

.lr-not-you:hover { opacity: 1; }

/* Slide transition between steps */
#lrStep1Panel, #lrStep2Panel {
    animation: lrFadeSlide 0.3s ease-out both;
}

@keyframes lrFadeSlide {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
}
</style>

<script>
(function () {
    var twoStep = <?php echo !empty($TwoStepEnabled) ? 'true' : 'false'; ?>;

    // Hide template customizer
    document.addEventListener('DOMContentLoaded', function () {
        var el = document.getElementById('template-customizer');
        if (el) el.classList.add('d-none');
    });

    // ── Shared: password toggle ──────────────────────────────────────────────
    function initPwToggle() {
        var pwToggle = document.getElementById('pwToggle');
        var pwInput  = document.getElementById('UserPassword');
        var pwIcon   = document.getElementById('pwIcon');
        if (!pwToggle) return;
        pwToggle.addEventListener('click', function () {
            if (pwInput.type === 'password') {
                pwInput.type = 'text';
                pwIcon.className = 'bx bx-show';
            } else {
                pwInput.type = 'password';
                pwIcon.className = 'bx bx-hide';
            }
        });
    }

    // ── Shared: ripple effect ────────────────────────────────────────────────
    function addRipple(btn) {
        if (!btn) return;
        btn.addEventListener('click', function (e) {
            var r = document.createElement('span');
            var d = Math.max(btn.clientWidth, btn.clientHeight);
            var rect = btn.getBoundingClientRect();
            r.className = 'lr-ripple';
            r.style.cssText = 'width:' + d + 'px;height:' + d + 'px;left:' + (e.clientX - rect.left - d/2) + 'px;top:' + (e.clientY - rect.top - d/2) + 'px';
            btn.appendChild(r);
            setTimeout(function () { r.remove(); }, 700);
        });
    }

    // ── Shared: spinner HTML ─────────────────────────────────────────────────
    var _spinnerHtml =
        '<span style="display:flex;align-items:center;justify-content:center;gap:10px;">' +
            '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="animation:spin 0.8s linear infinite;flex-shrink:0;">' +
                '<path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>' +
            '</svg>' +
            '{label}' +
        '</span>';

    function spinBtn(btn, label) {
        btn.disabled = true;
        btn.innerHTML = _spinnerHtml.replace('{label}', label);
        btn.style.opacity = '0.75';
        btn.style.cursor  = 'not-allowed';
    }

    function resetBtn(btn, iconClass, label) {
        btn.disabled = false;
        btn.innerHTML = '<span><i class="bx ' + iconClass + '" style="font-size:18px"></i> ' + label + '</span>';
        btn.style.opacity = '';
        btn.style.cursor  = '';
    }

    if (twoStep) {
        // ── TWO-STEP FLOW ────────────────────────────────────────────────────

        var csrfInput = document.querySelector('#doLoginForm input[name^="csrf"]') ||
                        document.querySelector('#lrStep1Panel input[name^="csrf"]');
        var csrfName  = csrfInput ? csrfInput.name  : '';
        var csrfVal   = csrfInput ? csrfInput.value : '';

        var step1Panel   = document.getElementById('lrStep1Panel');
        var step2Panel   = document.getElementById('lrStep2Panel');
        var continueBtn  = document.getElementById('lrContinueBtn');
        var step1Error   = document.getElementById('lrStep1Error');
        var welcomeName  = document.getElementById('lrWelcomeName');
        var step2Name    = document.getElementById('step2UserName');
        var notYouLink   = document.getElementById('lrNotYou');
        var submitBtn    = document.getElementById('lrSubmit');
        var loginForm    = document.getElementById('doLoginForm');
        var unameInput   = document.getElementById('UserName');
        var resendWrap    = document.getElementById('lrResendWrap');
        var resendBtn     = document.getElementById('lrResendBtn');
        var step1ErrorMsg = document.getElementById('lrStep1ErrorMsg');
        var step1ErrorSub = document.getElementById('lrStep1ErrorSub');

        /* ── Toast ──────────────────────────────────────────────────── */
        var _lrToastTimer = null;
        function lrToast(msg, isError) {
            var el = document.getElementById('lrToast');
            if (!el) {
                el = document.createElement('div');
                el.id = 'lrToast';
                el.className = 'lr-toast';
                document.body.appendChild(el);
            }
            clearTimeout(_lrToastTimer);
            el.textContent = msg;
            el.className   = 'lr-toast' + (isError ? ' lr-toast--error' : '');
            requestAnimationFrame(function () {
                requestAnimationFrame(function () { el.classList.add('lr-toast--show'); });
            });
            _lrToastTimer = setTimeout(function () {
                el.classList.remove('lr-toast--show');
            }, 4000);
        }

        /* ── Resend verification ─────────────────────────────────────── */
        var _pendingOrgEmail = '';

        function setStep1FormDisabled(disabled) {
            if (unameInput) {
                unameInput.disabled = disabled;
                unameInput.style.opacity = disabled ? '0.5' : '';
            }
            if (continueBtn) {
                continueBtn.disabled      = disabled;
                continueBtn.style.opacity = disabled ? '0.6'          : '';
                continueBtn.style.cursor  = disabled ? 'not-allowed'  : '';
            }
            setSocialDisabled(disabled);
            if (resendBtn) resendBtn.disabled = disabled;
        }

        function doResendVerification() {
            if (!_pendingOrgEmail) return;

            setStep1FormDisabled(true);
            resendBtn.textContent = 'Sending…';

            var emailToSend = _pendingOrgEmail;

            fetch('/resend-verification', {
                method : 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
                body   : 'identifier=' + encodeURIComponent(emailToSend),
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                _pendingOrgEmail = '';
                setStep1FormDisabled(false);
                clearStep1Error();
                if (data.Error) {
                    lrToast(data.Message || 'Failed to resend. Please try again.', true);
                } else {
                    lrToast('Verification email sent! Please check your inbox.', false);
                }
            })
            .catch(function () {
                setStep1FormDisabled(false);
                resendBtn.textContent = 'Resend Email';
                lrToast('Network error. Please try again.', true);
            });
        }

        if (resendBtn) resendBtn.addEventListener('click', doResendVerification);

        /* ── Initialise from OAuth redirect (Google / Facebook) ─────────── */
        <?php
            $oauthFlashMsg   = $this->session->flashdata('danger');
            $oauthOrgEmail   = $this->session->flashdata('unverified_org_email');
        ?>
        <?php if (!empty($oauthFlashMsg) && !empty($oauthOrgEmail)): ?>
        _pendingOrgEmail = <?php echo json_encode($oauthOrgEmail); ?>;
        showStep1Error(
            <?php echo json_encode($oauthFlashMsg); ?>,
            true,
            'Verification link sent to: ' + <?php echo json_encode($oauthOrgEmail); ?>
        );
        <?php elseif (!empty($oauthFlashMsg)): ?>
        showStep1Error(<?php echo json_encode($oauthFlashMsg); ?>, false);
        <?php endif; ?>

        /* Clear the error and pending email whenever the user edits the identifier field */
        if (unameInput) {
            unameInput.addEventListener('input', function () {
                if (_pendingOrgEmail) {
                    _pendingOrgEmail = '';
                    clearStep1Error();
                }
            });
        }

        /* subMsg — optional email line shown only for OAuth flow; omit for manual login */
        function showStep1Error(msg, showResend, subMsg) {
            if (step1ErrorMsg) step1ErrorMsg.textContent = msg;
            if (step1ErrorSub) {
                if (subMsg) {
                    step1ErrorSub.textContent = subMsg;
                    step1ErrorSub.style.display = 'block';
                } else {
                    step1ErrorSub.textContent   = '';
                    step1ErrorSub.style.display = 'none';
                }
            }
            step1Error.style.display = 'block';
            if (resendWrap) resendWrap.style.display = showResend ? 'flex' : 'none';
            if (resendBtn && showResend) { resendBtn.disabled = false; resendBtn.textContent = 'Resend Email'; }
        }

        function clearStep1Error() {
            step1Error.style.display = 'none';
            if (resendWrap) resendWrap.style.display = 'none';
            if (step1ErrorSub) { step1ErrorSub.textContent = ''; step1ErrorSub.style.display = 'none'; }
            _pendingOrgEmail = '';
        }

        function goToStep2(displayName, username, imageUrl) {
            welcomeName.textContent = displayName;
            step2Name.value = username;

            // Show profile photo if available, otherwise keep the generic icon
            var avatarEl = document.querySelector('#lrUserCard .lr-user-avatar');
            if (avatarEl) {
                if (imageUrl) {
                    avatarEl.innerHTML = '<img src="' + imageUrl + '" alt="' + displayName + '" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">';
                } else {
                    avatarEl.innerHTML = '<i class="bx bx-user-circle"></i>';
                }
            }

            step1Panel.style.animation = 'none';
            step1Panel.style.display   = 'none';
            step2Panel.style.animation = '';
            step2Panel.style.display   = 'block';
            var pw = document.getElementById('UserPassword');
            if (pw) setTimeout(function () { pw.focus(); }, 100);
        }

        function goToStep1() {
            // Fire-and-forget: clear the pending session on the server
            fetch('/login/clear-pending', { method: 'POST' });
            step2Panel.style.display = 'none';
            step1Panel.style.display = 'block';
            clearStep1Error();
            if (unameInput) unameInput.focus();
        }

        function setSocialDisabled(disabled) {
            document.querySelectorAll('#lrStep1Panel .lr-social-btn').forEach(function (b) {
                b.style.pointerEvents = disabled ? 'none' : '';
                b.style.opacity       = disabled ? '0.4'  : '';
            });
            var signupLink = document.querySelector('.lr-signup-note a');
            if (signupLink) {
                signupLink.style.pointerEvents = disabled ? 'none' : '';
                signupLink.style.opacity       = disabled ? '0.4'  : '';
            }
        }

        function doValidate() {
            var username = unameInput ? unameInput.value.trim() : '';
            if (!username) { showStep1Error('Please enter your username or email.', false); return; }
            clearStep1Error();
            spinBtn(continueBtn, 'Checking...');
            setSocialDisabled(true);

            var body = 'UserName=' + encodeURIComponent(username);
            if (csrfName && csrfVal) body += '&' + encodeURIComponent(csrfName) + '=' + encodeURIComponent(csrfVal);

            fetch('/login/validate-username', {
                method : 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body   : body,
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                resetBtn(continueBtn, 'bx-right-arrow-circle', 'Continue');
                setSocialDisabled(false);
                if (data.Error) {
                    if (data.NeedsEmailVerification) _pendingOrgEmail = data.OrgEmail || '';
                    showStep1Error(data.Message || 'Something went wrong. Please try again.', !!data.NeedsEmailVerification);
                    return;
                }
                goToStep2(data.DisplayName || data.Username, data.Username || username, data.ImageUrl || '');
            })
            .catch(function () {
                resetBtn(continueBtn, 'bx-right-arrow-circle', 'Continue');
                setSocialDisabled(false);
                showStep1Error('Connection failed. Please try again.', false);
            });
        }

        // Continue button
        if (continueBtn) {
            addRipple(continueBtn);
            continueBtn.addEventListener('click', doValidate);
        }

        // Enter key on username
        if (unameInput) {
            unameInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); doValidate(); }
            });
        }

        // Not you link
        if (notYouLink) {
            notYouLink.addEventListener('click', goToStep1);
        }

        // Step 2 form submit
        if (loginForm) {
            addRipple(submitBtn);
            loginForm.addEventListener('submit', function () {
                if (submitBtn) {
                    spinBtn(submitBtn, 'Signing in...');
                }
                setSocialDisabled(true);
            });
        }

        // Auto-focus username on load
        if (unameInput && step1Panel.style.display !== 'none') {
            setTimeout(function () { unameInput.focus(); }, 400);
        }

        initPwToggle();

    } else {
        // ── SINGLE-STEP FLOW ─────────────────────────────────────────────────

        var lrBtn     = document.getElementById('lrSubmit');
        var loginForm = document.getElementById('doLoginForm');

        addRipple(lrBtn);

        if (loginForm) {
            loginForm.addEventListener('submit', function () {
                if (lrBtn) {
                    spinBtn(lrBtn, 'Signing in...');
                }
                setSocialDisabled(true);
            });
        }

        // Re-enable on back/bfcache
        window.addEventListener('pageshow', function (e) {
            if (e.persisted) {
                if (lrBtn) resetBtn(lrBtn, 'bx-log-in-circle', 'Sign In');
                document.querySelectorAll('.lr-social-btn').forEach(function (btn) {
                    btn.style.pointerEvents = '';
                    btn.style.opacity       = '';
                });
            }
        });

        // Auto-focus username
        var un = document.getElementById('UserName');
        if (un) setTimeout(function () { un.focus(); }, 400);

        initPwToggle();
    }

    // ── i18n translation strings ─────────────────────────────────────────────
    var _lrStrings = {
        en: {
            heading    : 'Sign in',
            sub        : 'Enter your credentials to continue',
            labelUser  : 'Username or Email',
            phUser     : 'Enter your username',
            labelPass  : 'Password',
            phPass     : 'Enter your password',
            forgot     : 'Forgot password?',
            notYou     : 'Not you?',
            divider    : 'or continue with',
            btnContinue: 'Continue',
            btnSignin  : 'Sign In',
            btnGoogle  : 'Continue with Google',
            signupPre  : 'New here?',
            signupLink : 'Create your account'
        },
        ta: {
            heading    : 'உள்நுழைக',
            sub        : 'தொடர உங்கள் விவரங்களை உள்ளிடவும்',
            labelUser  : 'பயனர்பெயர் அல்லது மின்னஞ்சல்',
            phUser     : 'உங்கள் பயனர்பெயரை உள்ளிடவும்',
            labelPass  : 'கடவுச்சொல்',
            phPass     : 'உங்கள் கடவுச்சொல்லை உள்ளிடவும்',
            forgot     : 'கடவுச்சொல் மறந்துவிட்டதா?',
            notYou     : 'நீங்கள் இல்லையா?',
            divider    : 'அல்லது இதன் மூலம் தொடரவும்',
            btnContinue: 'தொடரவும்',
            btnSignin  : 'உள்நுழைக',
            btnGoogle  : 'Google மூலம் தொடரவும்',
            signupPre  : 'புதியவரா?',
            signupLink : 'கணக்கு உருவாக்கவும்'
        }
    };

    /**
     * Returns the last non-empty text node child of el.
     * @param {Element} el
     * @returns {Text|null}
     */
    function _lastTextNode(el) {
        if (!el) return null;
        var node = el.lastChild;
        while (node) {
            if (node.nodeType === 3 && node.textContent.trim() !== '') return node;
            node = node.previousSibling;
        }
        return null;
    }

    /**
     * Applies translated strings to all visible page elements without reloading.
     * Text nodes inside icon-buttons are updated individually to preserve the icon.
     * @param {string} lang - 'en' | 'ta'
     * @returns {void}
     */
    function applyI18n(lang) {
        var s = _lrStrings[lang] || _lrStrings.en;

        // Pure text elements — safe to set textContent directly
        document.querySelectorAll('.lr-form-head h3').forEach(function (el) { el.textContent = s.heading; });
        document.querySelectorAll('.lr-form-head p').forEach(function (el) { el.textContent = s.sub; });
        document.querySelectorAll('label[for="UserName"]').forEach(function (el) { el.textContent = s.labelUser; });
        document.querySelectorAll('label[for="UserPassword"]').forEach(function (el) { el.textContent = s.labelPass; });
        document.querySelectorAll('.lr-forgot').forEach(function (el) { el.textContent = s.forgot; });
        document.querySelectorAll('.lr-not-you').forEach(function (el) { el.textContent = s.notYou; });
        document.querySelectorAll('.lr-social-divider span').forEach(function (el) { el.textContent = s.divider; });

        // Input placeholders
        document.querySelectorAll('#UserName').forEach(function (el) { el.placeholder = s.phUser; });
        document.querySelectorAll('#UserPassword').forEach(function (el) { el.placeholder = s.phPass; });

        // Continue button — update trailing text node, leave icon intact
        var contBtn = document.getElementById('lrContinueBtn');
        if (contBtn) {
            var tn = _lastTextNode(contBtn.querySelector('span'));
            if (tn) tn.textContent = ' ' + s.btnContinue;
        }

        // Sign In button(s) — update trailing text node, leave icon intact
        document.querySelectorAll('button.lr-btn[type="submit"] > span').forEach(function (span) {
            var tn = _lastTextNode(span);
            if (tn) tn.textContent = ' ' + s.btnSignin;
        });

        // Google button(s) — update trailing text node, leave SVG intact
        document.querySelectorAll('.lr-social-google').forEach(function (el) {
            var tn = _lastTextNode(el);
            if (tn) tn.textContent = ' ' + s.btnGoogle + ' ';
        });

        // Footer signup note — "New here?" text + link
        var signupPara = document.querySelector('.lr-signup-note');
        if (signupPara) {
            var firstTn = signupPara.firstChild;
            while (firstTn && firstTn.nodeType !== 3) firstTn = firstTn.nextSibling;
            if (firstTn) firstTn.textContent = ' ' + s.signupPre + ' ';
            var signupAnchor = signupPara.querySelector('a');
            if (signupAnchor) signupAnchor.textContent = s.signupLink;
        }
    }

    // ── Language switcher ────────────────────────────────────────────────────
    (function () {
        var switchEl   = document.getElementById('lrLangSwitch');
        var triggerEl  = document.getElementById('lrLangTrigger');
        var labelEl    = document.getElementById('lrLangLabel');
        var opts       = document.querySelectorAll('.lr-lang-opt');
        if (!switchEl || !triggerEl) return;

        triggerEl.addEventListener('click', function (e) {
            e.stopPropagation();
            switchEl.classList.toggle('open');
        });

        opts.forEach(function (opt) {
            opt.addEventListener('click', function () {
                var lang = opt.getAttribute('data-lang');
                labelEl.textContent = lang === 'ta' ? 'த' : 'En';
                opts.forEach(function (o) { o.classList.remove('lr-lang-active'); });
                opt.classList.add('lr-lang-active');
                switchEl.classList.remove('open');
                applyI18n(lang);
            });
        });

        document.addEventListener('click', function () {
            switchEl.classList.remove('open');
        });
    }());
})();
</script>

<script>
/* ── Time-of-Day Adaptive Panel ────────────────────────────── */
(function ($) {

    // ── Image sources — served from Cloudflare R2 CDN ─────────
    var _cdnBase = '<?= $CdnBase ?>';
    var IMAGE_SOURCES = {
        morning: _cdnBase + '/Global/landing%20page/r2k_morning.jpg',
        day    : _cdnBase + '/Global/landing%20page/r2k_day.jpg',
        evening: _cdnBase + '/Global/landing%20page/r2k_evening.jpg',
        night  : _cdnBase + '/Global/landing%20page/r2k_night.jpg'
    };

    /**
     * @returns {string} 'morning' | 'day' | 'evening' | 'night'
     */
    function getTimeSlot() {
        var h = new Date().getHours();
        if (h >= 5  && h < 12) return 'morning';
        if (h >= 12 && h < 17) return 'day';
        if (h >= 17 && h < 20) return 'evening';
        return 'night';
    }

    /**
     * @returns {string} greeting text based on current hour
     */
    function getGreeting() {
        var h = new Date().getHours();
        if (h >= 5  && h < 12) return 'Good Morning';
        if (h >= 12 && h < 17) return 'Good Afternoon';
        if (h >= 17 && h < 20) return 'Good Evening';
        return 'Working Late';
    }

    /**
     * @returns {string} formatted HH:MM:SS AM/PM
     */
    function formatClock() {
        var now  = new Date();
        var h    = now.getHours();
        var m    = now.getMinutes();
        var s    = now.getSeconds();
        var ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        return (
            String(h).padStart(2, '0') + ':' +
            String(m).padStart(2, '0') + ':' +
            String(s).padStart(2, '0') + ' ' + ampm
        );
    }

    /**
     * Starts the live clock, updating every second.
     * @returns {void}
     */
    function startClock() {
        var el = document.getElementById('lrLiveClock');
        if (!el) return;
        function tick() { el.textContent = formatClock(); }
        tick();
        setInterval(tick, 1000);
    }

    /**
     * Applies the time-slot gradient class and loads the matching image.
     * @returns {void}
     */
    function initImage() {
        var slot  = getTimeSlot();
        var brand = document.getElementById('lrBrand');
        var img   = document.getElementById('lrBgImg');
        if (!brand) return;

        ['time-morning', 'time-day', 'time-evening', 'time-night'].forEach(function (c) {
            brand.classList.remove(c);
        });
        brand.classList.add('time-' + slot);

        if (img && IMAGE_SOURCES[slot]) {
            img.src = IMAGE_SOURCES[slot];
        }
    }

    /**
     * Wires mouse-parallax on the brand panel.
     * Video shifts opposite to cursor; glass card shifts with cursor for 5D depth.
     * @param {MouseEvent} e - mouse event (internal)
     * @returns {void}
     */
    function initParallax() {
        var panel = document.getElementById('lrBrand');
        var img   = document.getElementById('lrBgImg');
        var card  = document.getElementById('lrGlassCard');
        if (!panel) return;

        panel.addEventListener('mousemove', function (e) {
            var rect = panel.getBoundingClientRect();
            var dx   = (e.clientX - rect.left - rect.width  / 2) / rect.width;
            var dy   = (e.clientY - rect.top  - rect.height / 2) / rect.height;

            if (img) {
                img.style.transform =
                    'translate3d(' + (dx * -20) + 'px, ' + (dy * -15) + 'px, 0) scale(1.06)';
            }
            if (card) {
                card.style.transform =
                    'translate3d(' + (dx * 11) + 'px, ' + (dy * 9) + 'px, 0)';
            }
        });

        panel.addEventListener('mouseleave', function () {
            if (img)  img.style.transform  = 'translate3d(0, 0, 0) scale(1.06)';
            if (card) card.style.transform  = 'translate3d(0, 0, 0)';
        });
    }

    // ── Boot ───────────────────────────────────────────────────
    var greetEl = document.getElementById('lrGreeting');
    if (greetEl) greetEl.textContent = getGreeting();

    startClock();
    initImage();
    initParallax();

}(jQuery));
</script>
