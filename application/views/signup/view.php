<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $pageTitle = 'Sign Up'; $this->load->view('login/header', ['pageTitle' => $pageTitle]); ?>

<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

/* Freeze the body so the admin theme's main.js cannot shift the layout */
html, body {
    margin: 0 !important;
    padding: 0 !important;
    overflow: hidden !important;
    height: 100% !important;
}

.su-root {
    display: flex;
    position: fixed;
    inset: 0;
    background: #040b18;
    font-family: 'Public Sans', sans-serif;
    overflow: hidden;
}

/* ── LEFT BRAND PANEL ──────────────────────────────────────── */
.su-brand {
    position: relative;
    width: 45%;
    overflow: hidden;
    background: #0e1318;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: flex-start;
    padding: 3rem 3.5rem;
}

.su-brand::after {
    content: '';
    position: absolute;
    top: 0; right: 0;
    width: 1px; height: 100%;
    background: linear-gradient(to bottom, transparent 0%, rgba(100,150,190,0.45) 35%, rgba(100,150,190,0.45) 65%, transparent 100%);
    z-index: 10;
}

/* CSS rain */
.su-rain-canvas {
    position: absolute;
    inset: 0;
    overflow: hidden;
    pointer-events: none;
}

.su-rain-drop {
    position: absolute;
    top: -20px;
    width: 1.5px;
    background: linear-gradient(to bottom, transparent, rgba(147,190,220,0.55));
    border-radius: 1px;
    animation: su-rain-fall linear infinite;
}

@keyframes su-rain-fall {
    to { transform: translateY(110vh) translateX(18px); }
}

.su-brand-content {
    position: relative;
    z-index: 5;
}

.su-brand-logo {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 3rem;
}

.su-brand-logo img {
    height: 40px;
    width: auto;
    filter: brightness(1.1);
}

.su-brand-logo span {
    font-size: 1.15rem;
    font-weight: 600;
    color: #e2e8f0;
    letter-spacing: 0.02em;
}

.su-brand-tagline {
    font-size: 2.1rem;
    font-weight: 700;
    color: #f0f4f8;
    line-height: 1.25;
    margin-bottom: 1.25rem;
    text-wrap: balance;
}

.su-brand-tagline em {
    font-style: normal;
    color: #60a5c8;
}

.su-brand-sub {
    font-size: 0.925rem;
    color: rgba(180,200,220,0.7);
    line-height: 1.7;
    max-width: 340px;
}

.su-brand-features {
    margin-top: 2.5rem;
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
}

.su-feat-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: rgba(180,210,230,0.75);
    font-size: 0.875rem;
}

.su-feat-dot {
    width: 6px; height: 6px;
    border-radius: 50%;
    background: #60a5c8;
    flex-shrink: 0;
}

/* ── RIGHT FORM PANEL ──────────────────────────────────────── */
.su-form-panel {
    width: 55%;
    height: 100vh;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    align-items: center;
    padding: 3rem 3rem 2.5rem;
    background: #0a1628;
    overflow-y: auto;
}

.su-form-wrap {
    width: 100%;
    max-width: 520px;
}

/* Step indicator */
.su-steps {
    display: flex;
    align-items: center;
    margin-bottom: 1.5rem;
}

.su-step {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.su-step-num {
    width: 32px; height: 32px;
    border-radius: 50%;
    border: 2px solid rgba(96,165,200,0.35);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    font-weight: 600;
    color: rgba(180,210,230,0.5);
    transition: all 0.3s;
}

.su-step.active .su-step-num {
    background: #1e5a82;
    border-color: #60a5c8;
    color: #e2f0ff;
}

.su-step.done .su-step-num {
    background: #1a6b4a;
    border-color: #34d399;
    color: #d1fae5;
}

.su-step-label {
    font-size: 0.8rem;
    color: rgba(180,210,230,0.5);
    font-weight: 500;
    transition: color 0.3s;
}

.su-step.active .su-step-label { color: #a3d0e8; }
.su-step.done .su-step-label   { color: #6ee7b7; }

.su-step-line {
    flex: 1;
    height: 1px;
    background: rgba(96,165,200,0.2);
    margin: 0 0.75rem;
}

/* Section heading */
.su-step-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #e2f0ff;
    margin-bottom: 0.4rem;
}

.su-step-hint {
    font-size: 0.875rem;
    color: rgba(160,190,215,0.65);
    margin-bottom: 2rem;
}

/* Form fields */
.su-field {
    margin-bottom: 1.25rem;
}

.su-field-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.su-label {
    display: block;
    font-size: 0.8rem;
    font-weight: 600;
    color: rgba(180,210,230,0.75);
    margin-bottom: 0.45rem;
    letter-spacing: 0.03em;
    text-transform: uppercase;
}

.su-label .su-optional {
    text-transform: none;
    font-weight: 400;
    color: rgba(160,190,215,0.45);
    margin-left: 0.3rem;
}

.su-input {
    width: 100%;
    padding: 0.7rem 1rem;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(96,165,200,0.2);
    border-radius: 8px;
    color: #e2f0ff;
    font-size: 0.9rem;
    font-family: inherit;
    transition: border-color 0.2s, background 0.2s;
    outline: none;
}

.su-input:focus {
    border-color: rgba(96,165,200,0.6);
    background: rgba(96,165,200,0.07);
}

.su-input.error  { border-color: rgba(239,68,68,0.6); }
.su-input.valid  { border-color: rgba(52,211,153,0.5); }

/* ── Browser autofill override ── */
.su-input:-webkit-autofill,
.su-input:-webkit-autofill:hover,
.su-input:-webkit-autofill:focus {
    -webkit-text-fill-color: #e2f0ff;
    -webkit-box-shadow: 0 0 0px 1000px #0f1929 inset;
    caret-color: #e2f0ff;
    transition: background-color 5000s ease-in-out 0s;
}
.su-input option { background: #0a1628; color: #e2f0ff; }

/* ── Phone prefix input ─────────────────────────────────────── */
.su-phone-wrap {
    display: flex;
    align-items: center;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(96,165,200,0.2);
    border-radius: 8px;
    transition: border-color 0.2s, background 0.2s;
    overflow: hidden;
}
.su-phone-wrap:focus-within {
    border-color: rgba(96,165,200,0.6);
    background: rgba(96,165,200,0.07);
}
.su-phone-wrap.error { border-color: rgba(239,68,68,0.6); }
.su-phone-wrap.valid { border-color: rgba(52,211,153,0.5); }

.su-phone-prefix {
    padding: 0.7rem 0.8rem 0.7rem 1rem;
    font-size: 0.9rem;
    font-weight: 600;
    color: rgba(96,165,200,0.9);
    user-select: none;
    white-space: nowrap;
    flex-shrink: 0;
    border-right: 1px solid rgba(96,165,200,0.2);
    line-height: 1.4;
}
.su-phone-input {
    flex: 1;
    min-width: 0;
    padding: 0.7rem 1rem;
    background: transparent;
    border: none;
    color: #e2f0ff;
    font-size: 0.9rem;
    font-family: inherit;
    outline: none;
}
.su-phone-input::placeholder { color: rgba(160,190,215,0.4); }

.su-pw-wrap {
    position: relative;
}

.su-pw-toggle {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: rgba(160,190,215,0.5);
    cursor: pointer;
    padding: 0.25rem;
    font-size: 1.1rem;
    line-height: 1;
    display: flex;
    align-items: center;
    transition: color 0.2s;
}

.su-pw-toggle:hover { color: #a3d0e8; }

.su-field-hint {
    font-size: 0.76rem;
    color: rgba(160,190,215,0.5);
    margin-top: 0.35rem;
}

.su-field-err {
    font-size: 0.76rem;
    color: #f87171;
    margin-top: 0.35rem;
    display: none;
}

.su-field-err.show { display: block; }

.su-field-ok {
    font-size: 0.76rem;
    color: #6ee7b7;
    margin-top: 0.35rem;
    display: none;
}

.su-field-ok.show { display: block; }

/* Strength bar */
.su-strength {
    margin-top: 0.5rem;
    display: flex;
    gap: 4px;
}

.su-strength-seg {
    flex: 1;
    height: 3px;
    border-radius: 2px;
    background: rgba(255,255,255,0.08);
    transition: background 0.3s;
}

.su-strength-seg.filled-weak   { background: #f87171; }
.su-strength-seg.filled-fair   { background: #fbbf24; }
.su-strength-seg.filled-good   { background: #34d399; }
.su-strength-seg.filled-strong { background: #60a5fa; }

.su-strength-label {
    font-size: 0.74rem;
    color: rgba(160,190,215,0.5);
    margin-top: 0.3rem;
}

/* Buttons */
.su-btn-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 2rem;
    gap: 1rem;
}

.su-btn {
    padding: 0.72rem 1.75rem;
    border-radius: 8px;
    border: none;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s;
}

.su-btn-primary {
    background: linear-gradient(135deg, #1e5a82 0%, #1a4870 100%);
    color: #e2f0ff;
    border: 1px solid rgba(96,165,200,0.35);
    flex: 1;
    justify-content: center;
}

.su-btn-primary:hover:not(:disabled) {
    background: linear-gradient(135deg, #2870a0 0%, #1e5a82 100%);
    border-color: rgba(96,165,200,0.6);
}

.su-btn-primary:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.su-btn-ghost {
    background: transparent;
    color: rgba(160,190,215,0.7);
    border: 1px solid rgba(96,165,200,0.2);
}

.su-btn-ghost:hover {
    background: rgba(96,165,200,0.07);
    border-color: rgba(96,165,200,0.4);
    color: #a3d0e8;
}

/* Alert box */
.su-alert {
    padding: 0.85rem 1rem;
    border-radius: 8px;
    font-size: 0.875rem;
    margin-bottom: 1.25rem;
    display: none;
    align-items: flex-start;
    gap: 0.6rem;
}

.su-alert.show { display: flex; }

.su-alert-err {
    background: rgba(239,68,68,0.1);
    border: 1px solid rgba(239,68,68,0.3);
    color: #fca5a5;
}

/* Login link */
.su-login-link {
    text-align: center;
    margin-top: 1.75rem;
    font-size: 0.875rem;
    color: rgba(160,190,215,0.55);
}

.su-login-link a {
    color: #60a5c8;
    text-decoration: none;
    font-weight: 500;
}

.su-login-link a:hover { text-decoration: underline; }

/* Success screen */
.su-success {
    display: none;
    text-align: center;
    padding: 2rem 0;
}

.su-success.show { display: block; }

.su-success-icon {
    width: 72px; height: 72px;
    border-radius: 50%;
    background: rgba(52,211,153,0.12);
    border: 2px solid rgba(52,211,153,0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    font-size: 2rem;
    color: #34d399;
}

.su-success-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #e2f0ff;
    margin-bottom: 0.6rem;
}

.su-success-msg {
    font-size: 0.9rem;
    color: rgba(160,190,215,0.7);
    line-height: 1.65;
    margin-bottom: 2rem;
}

.su-success-msg strong { color: #a3d0e8; }

/* ── Password criteria list ────────────────────────────────── */
.su-pw-criteria {
    list-style: none;
    margin-top: 12px;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.su-pw-criterion {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: #64748b;
    transition: color 0.2s ease;
}

.su-crit-icon {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    flex-shrink: 0;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: #475569;
    transition: all 0.2s ease;
}

.su-pw-criterion.su-crit-pass { color: #22c55e; }
.su-pw-criterion.su-crit-pass .su-crit-icon {
    background: rgba(34, 197, 94, 0.12);
    border-color: rgba(34, 197, 94, 0.35);
    color: #22c55e;
}

.su-pw-criterion.su-crit-fail { color: #f87171; }
.su-pw-criterion.su-crit-fail .su-crit-icon {
    background: rgba(248, 113, 113, 0.12);
    border-color: rgba(248, 113, 113, 0.35);
    color: #f87171;
}

/* ── Processing overlay ────────────────────────────────────────── */
.su-overlay {
    position: fixed;
    inset: 0;
    background: rgba(4, 11, 24, 0.88);
    backdrop-filter: blur(4px);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 1.25rem;
    z-index: 9999;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.2s ease;
}

.su-overlay.show {
    opacity: 1;
    pointer-events: all;
}

/* Gradient ring logo — same pattern as subscription renew page */
.su-overlay-logo-wrap {
    position: relative;
    width: 48px;
    height: 48px;
    flex-shrink: 0;
}

.su-overlay-ring {
    position: absolute;
    inset: -4px;
    border-radius: 50%;
    background: conic-gradient(from 0deg,
        #7c3aed 0%,
        #06b6d4 28%,
        #6366f1 52%,
        #a78bfa 76%,
        #7c3aed 100%
    );
    animation: su-ring-spin 1.6s linear infinite;
}

.su-overlay-ring::after {
    content: '';
    position: absolute;
    inset: 4px;
    border-radius: 50%;
    background: #040b18;
}

.su-overlay-logo-img {
    position: absolute;
    inset: 5px;        /* 5px gap between logo and ring inner edge */
    width: 38px;
    height: 38px;
    border-radius: 50%;
    object-fit: cover;
    z-index: 1;
}

@keyframes su-ring-spin {
    to { transform: rotate(360deg); }
}

/* keep the old spinner class stub so nothing breaks if referenced elsewhere */
.su-overlay-spinner {
    display: none;
    width: 48px;
    height: 48px;
    border: 3px solid rgba(96, 165, 200, 0.2);
    border-top-color: #60a5c8;
    border-radius: 50%;
    animation: su-spin 0.75s linear infinite;
}

@keyframes su-spin {
    to { transform: rotate(360deg); }
}

.su-overlay-text {
    font-size: 0.9rem;
    font-weight: 500;
    color: rgba(180, 210, 230, 0.85);
    letter-spacing: 0.02em;
}

/* ── Overlay two-state toggle ──────────────────────────── */
.su-overlay-creating { display: contents; }
.su-overlay-done     { display: none; flex-direction: column; align-items: center; gap: 0.6rem; }

.su-overlay.success .su-overlay-creating { display: none; }
.su-overlay.success .su-overlay-done     { display: flex; }

/* Checkmark circle */
.su-overlay-check {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: rgba(52, 211, 153, 0.12);
    border: 2px solid rgba(52, 211, 153, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: #34d399;
    animation: su-check-pop 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) both;
    margin-bottom: 0.4rem;
}

@keyframes su-check-pop {
    from { transform: scale(0.4); opacity: 0; }
    to   { transform: scale(1);   opacity: 1; }
}

.su-overlay-done-title {
    font-size: 1.4rem;
    font-weight: 700;
    color: #e2f0ff;
    letter-spacing: 0.01em;
}

.su-overlay-done-org {
    font-size: 0.95rem;
    font-weight: 600;
    color: #60a5c8;
    max-width: 260px;
    text-align: center;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.su-overlay-done-msg {
    font-size: 0.82rem;
    color: rgba(160, 190, 215, 0.6);
    margin-top: 0.2rem;
}

/* Three-dot bounce */
.su-overlay-done-dots {
    display: flex;
    gap: 6px;
    margin-top: 0.5rem;
}

.su-overlay-done-dots span {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: rgba(96, 165, 200, 0.5);
    animation: su-dot-bounce 1.2s ease-in-out infinite;
}

.su-overlay-done-dots span:nth-child(2) { animation-delay: 0.2s; }
.su-overlay-done-dots span:nth-child(3) { animation-delay: 0.4s; }

@keyframes su-dot-bounce {
    0%, 80%, 100% { transform: scale(0.7); opacity: 0.4; }
    40%            { transform: scale(1);   opacity: 1;   }
}

/* ── Google confirmation overlay ───────────────────────────────── */
.su-gconf-overlay {
    position: fixed;
    inset: 0;
    background: rgba(4, 11, 24, 0.82);
    backdrop-filter: blur(6px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9998;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.22s ease;
}

.su-gconf-overlay.show {
    opacity: 1;
    pointer-events: all;
}

.su-gconf-card {
    background: #0d1f38;
    border: 1px solid rgba(96, 165, 200, 0.22);
    border-radius: 16px;
    padding: 2rem 2rem 1.75rem;
    width: 100%;
    max-width: 340px;
    text-align: center;
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.55);
    transform: translateY(12px) scale(0.97);
    transition: transform 0.22s ease;
}

.su-gconf-overlay.show .su-gconf-card {
    transform: translateY(0) scale(1);
}

.su-gconf-site {
    font-size: 0.75rem;
    color: rgba(160, 190, 215, 0.45);
    letter-spacing: 0.04em;
    margin-bottom: 1.35rem;
}

.su-gconf-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid rgba(96, 165, 200, 0.3);
    margin: 0 auto 0.85rem;
    display: block;
}

.su-gconf-avatar-placeholder {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #1e5a82, #2870a0);
    border: 2px solid rgba(96, 165, 200, 0.3);
    margin: 0 auto 0.85rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: 700;
    color: #e2f0ff;
}

.su-gconf-name {
    font-size: 1.05rem;
    font-weight: 600;
    color: #e2f0ff;
    margin-bottom: 0.3rem;
}

.su-gconf-email {
    font-size: 0.82rem;
    color: rgba(160, 190, 215, 0.6);
    margin-bottom: 1rem;
    word-break: break-all;
}

.su-gconf-plan-section {
    width: 100%;
    margin-bottom: 1.25rem;
}

.su-gconf-plan-label {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: rgba(160, 190, 215, 0.55);
    margin-bottom: 0.65rem;
    text-align: left;
}

#suGconfPlanCards .su-plan-grid {
    grid-template-columns: 1fr;
    gap: 0.5rem;
}

#suGconfPlanCards .su-plan-card {
    padding: 0.65rem 0.9rem;
    flex-direction: row;
    align-items: center;
    gap: 0.75rem;
}
#suGconfPlanCards .su-plan-card::before { display: none; }
#suGconfPlanCards .su-plan-card-name  { font-size: 0.88rem; }
#suGconfPlanCards .su-plan-card-price { margin-top: 0; margin-left: auto; }
#suGconfPlanCards .su-plan-card-meta  { display: none; }
#suGconfPlanCards .su-plan-card-cycle { display: none; }

.su-gconf-btn-continue {
    width: 100%;
    padding: 0.72rem 1rem;
    border-radius: 8px;
    border: none;
    background: linear-gradient(135deg, #1e5a82 0%, #1a4870 100%);
    border: 1px solid rgba(96, 165, 200, 0.35);
    color: #e2f0ff;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
    transition: all 0.2s;
    margin-bottom: 0.85rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.su-gconf-btn-continue:hover {
    background: linear-gradient(135deg, #2870a0 0%, #1e5a82 100%);
    border-color: rgba(96, 165, 200, 0.6);
}

.su-gconf-btn-switch {
    background: none;
    border: none;
    color: rgba(96, 165, 200, 0.75);
    font-size: 0.82rem;
    cursor: pointer;
    font-family: inherit;
    padding: 0.3rem;
    transition: color 0.2s;
}

.su-gconf-btn-switch:hover { color: #a3d0e8; }

@media (max-width: 900px) {
    .su-brand { display: none; }
    .su-form-panel { width: 100%; padding: 2rem 1.5rem; }
}

/* ── Home pill ─────────────────────────────────────────────── */
.su-home-link {
    position: absolute;
    top: 24px;
    right: 24px;
    z-index: 10;
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 7px 13px;
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid rgba(96, 165, 200, 0.18);
    border-radius: 100px;
    color: rgba(160, 200, 225, 0.7);
    font-size: 13px;
    font-weight: 500;
    text-decoration: none;
    line-height: 1;
    transition: all 0.2s ease-in-out;
}
.su-home-link:hover {
    background: rgba(96, 165, 200, 0.1);
    color: #c8e4f4;
    border-color: rgba(96, 165, 200, 0.35);
    text-decoration: none;
    transform: translateX(2px);
}
.su-home-link i { font-size: 14px; }

/* ── Language switcher ─────────────────────────────────────── */
.su-lang-switch {
    position: absolute;
    top: 24px;
    right: 24px;
    z-index: 10;
}
.su-lang-trigger {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 7px 13px;
    background: rgba(255,255,255,0.06);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 100px;
    color: #94a3b8;
    font-size: 13px;
    font-weight: 500;
    font-family: inherit;
    cursor: pointer;
    transition: all 0.2s ease-in-out;
    line-height: 1;
}
.su-lang-trigger:hover {
    background: rgba(255,255,255,0.1);
    color: #f1f5f9;
    border-color: rgba(255,255,255,0.16);
}
.su-lang-chevron {
    font-size: 14px;
    transition: transform 0.2s ease;
    display: flex;
}
.su-lang-switch.open .su-lang-chevron { transform: rotate(180deg); }
.su-lang-dropdown {
    position: absolute;
    top: calc(100% + 6px);
    right: 0;
    min-width: 140px;
    background: rgba(8,16,38,0.92);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 12px 32px rgba(0,0,0,0.45);
    display: none;
}
.su-lang-switch.open .su-lang-dropdown {
    display: block;
    animation: suDropIn 0.18s ease-out both;
}
@keyframes suDropIn {
    from { opacity: 0; transform: translateY(-6px); }
    to   { opacity: 1; transform: translateY(0); }
}
.su-lang-opt {
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
.su-lang-opt:hover { background: rgba(255,255,255,0.06); color: #f1f5f9; }
.su-lang-opt.su-lang-active { color: #f59e0b; }

/* ── Plan loading spinner ───────────────────────────────────── */
.su-plans-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem 0;
    gap: 0.55rem;
    color: rgba(160, 200, 220, 0.5);
    font-size: 0.84rem;
}
.su-plans-loading .bx { font-size: 1.9rem; }

/* ── Plan cards ────────────────────────────────────────────── */
.su-plan-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.7rem;
    margin-bottom: 0.5rem;
}

.su-plan-card {
    position: relative;
    display: flex;
    flex-direction: column;
    background: rgba(9, 16, 32, 0.8);
    border: 1.5px solid rgba(96, 165, 200, 0.13);
    border-radius: 13px;
    padding: 1.05rem 0.95rem 0.9rem;
    cursor: pointer;
    transition: border-color 0.18s, background 0.18s, box-shadow 0.18s, transform 0.18s;
    outline: none;
    overflow: hidden;
}

/* Accent bar at top */
.su-plan-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg, #3b82f6, #60a5c8);
    border-radius: 13px 13px 0 0;
    opacity: 0.55;
    transition: opacity 0.18s;
}
.su-plan-card[data-cycle="trial"]::before   { background: linear-gradient(90deg, #4ade80, #22c55e); }
.su-plan-card[data-cycle="monthly"]::before { background: linear-gradient(90deg, #3b82f6, #60a5c8); }
.su-plan-card[data-cycle="yearly"]::before  { background: linear-gradient(90deg, #f59e0b, #a78bfa); }

.su-plan-card:hover {
    border-color: rgba(96, 165, 200, 0.38);
    background: rgba(16, 28, 52, 0.92);
    transform: translateY(-2px);
    box-shadow: 0 6px 22px rgba(0, 0, 0, 0.35);
}
.su-plan-card:hover::before { opacity: 1; }
.su-plan-card[data-cycle="trial"]:hover  { border-color: rgba(74, 222, 128, 0.38); }
.su-plan-card[data-cycle="yearly"]:hover { border-color: rgba(167, 139, 250, 0.38); }

.su-plan-card.selected {
    border-color: #60a5c8;
    background: rgba(14, 30, 60, 0.96);
    box-shadow: 0 0 0 2px rgba(96, 165, 200, 0.18), 0 6px 24px rgba(0, 0, 0, 0.4);
    transform: translateY(-1px);
}
.su-plan-card.selected::before { opacity: 1; }
.su-plan-card[data-cycle="trial"].selected  { border-color: #4ade80; box-shadow: 0 0 0 2px rgba(74,222,128,0.18), 0 6px 24px rgba(0,0,0,0.4); }
.su-plan-card[data-cycle="yearly"].selected { border-color: #a78bfa; box-shadow: 0 0 0 2px rgba(167,139,250,0.18), 0 6px 24px rgba(0,0,0,0.4); }

/* Check circle */
.su-plan-check {
    position: absolute;
    top: 0.75rem; right: 0.75rem;
    width: 18px; height: 18px;
    border: 1.5px solid rgba(96, 165, 200, 0.35);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.6rem;
    color: transparent;
    transition: all 0.18s;
}
.su-plan-card.selected .su-plan-check                 { background: #60a5c8; border-color: #60a5c8; color: #fff; }
.su-plan-card[data-cycle="trial"].selected .su-plan-check  { background: #4ade80; border-color: #4ade80; color: #052e16; }
.su-plan-card[data-cycle="yearly"].selected .su-plan-check { background: #a78bfa; border-color: #a78bfa; color: #fff; }

/* Cycle pill */
.su-plan-card-cycle {
    display: inline-flex;
    align-self: flex-start;
    font-size: 0.58rem;
    font-weight: 700;
    letter-spacing: 0.09em;
    text-transform: uppercase;
    padding: 0.18rem 0.5rem;
    border-radius: 100px;
    background: rgba(96, 165, 200, 0.09);
    color: rgba(96, 165, 200, 0.7);
    border: 1px solid rgba(96, 165, 200, 0.17);
    margin-bottom: 0.45rem;
}
.su-plan-card[data-cycle="trial"]  .su-plan-card-cycle { background: rgba(74,222,128,0.08); color: rgba(74,222,128,0.85); border-color: rgba(74,222,128,0.2); }
.su-plan-card[data-cycle="yearly"] .su-plan-card-cycle { background: rgba(167,139,250,0.08); color: rgba(167,139,250,0.85); border-color: rgba(167,139,250,0.2); }

/* Plan name */
.su-plan-card-name {
    font-size: 0.9rem;
    font-weight: 700;
    color: #daeeff;
    padding-right: 1.5rem;
    line-height: 1.3;
    margin-bottom: 0;
}

/* Price */
.su-plan-card-price {
    display: flex;
    align-items: baseline;
    gap: 0.12rem;
    margin-top: 0.35rem;
    flex: 1;
}
.su-plan-card-price .sp-cur { font-size: 0.8rem; color: #60a5c8; font-weight: 600; line-height: 2; }
.su-plan-card-price .sp-amt { font-size: 1.65rem; font-weight: 800; color: #f0f6ff; line-height: 1; font-variant-numeric: tabular-nums; }
.su-plan-card-price .sp-free { font-size: 1.15rem; font-weight: 800; color: #4ade80; }

/* Meta info */
.su-plan-card-meta {
    margin-top: 0.6rem;
    padding-top: 0.55rem;
    border-top: 1px solid rgba(96, 165, 200, 0.08);
    font-size: 0.69rem;
    color: rgba(160, 200, 220, 0.45);
    display: flex;
    flex-direction: column;
    gap: 0.22rem;
}
.su-plan-card-meta span { display: flex; align-items: center; gap: 0.3rem; }
.su-plan-card-meta .bx { font-size: 0.72rem; opacity: 0.7; }

/* Free badge (inline next to plan name) */
.su-plan-free-badge {
    display: inline-block;
    background: rgba(34, 197, 94, 0.12);
    border: 1px solid rgba(34, 197, 94, 0.25);
    color: #4ade80;
    font-size: 0.58rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    padding: 0.12rem 0.4rem;
    border-radius: 100px;
    margin-left: 0.35rem;
    vertical-align: middle;
}
</style>

<div class="su-root">

    <!-- ── LEFT PANEL ── -->
    <div class="su-brand">
        <div class="su-rain-canvas" id="suRainCanvas"></div>

        <a href="<?php echo base_url(); ?>" class="su-home-link" title="Back to homepage">
            <i class="bx bx-arrow-back"></i>
            Home
        </a>

        <div class="su-brand-content">
            <div class="su-brand-logo">
                <img src="/images/logo/logo.png" alt="Logo" onerror="this.style.display='none'">
            </div>

            <p class="su-brand-tagline">Start managing your <em>business</em> the smarter way</p>
            <p class="su-brand-sub">One platform for invoicing, inventory, purchases, accounting, and your entire team.</p>

            <div class="su-brand-features">
                <div class="su-feat-item"><span class="su-feat-dot"></span>Multi-branch inventory &amp; transactions</div>
                <div class="su-feat-item"><span class="su-feat-dot"></span>GST-ready invoicing &amp; reports</div>
                <div class="su-feat-item"><span class="su-feat-dot"></span>Role-based access for your team</div>
                <div class="su-feat-item"><span class="su-feat-dot"></span>Real-time accounting &amp; P&amp;L</div>
            </div>
        </div>
    </div>

    <!-- ── RIGHT FORM PANEL ── -->
    <div class="su-form-panel" style="position:relative;">

        <div class="su-overlay" id="suProcessingOverlay">
            <!-- Creating state -->
            <div class="su-overlay-creating">
                <div class="su-overlay-logo-wrap">
                    <div class="su-overlay-ring"></div>
                    <img src="https://pub-bb40942a33344637936ade1f3800ff8b.r2.dev/Global/favicon_io/android-chrome-512x512-1.png"
                         class="su-overlay-logo-img" alt="R2K">
                </div>
                <div class="su-overlay-text">Creating your account…</div>
            </div>
            <!-- Success state -->
            <div class="su-overlay-done">
                <div class="su-overlay-check"><i class="bx bx-check"></i></div>
                <div class="su-overlay-done-title">Account Created!</div>
                <div class="su-overlay-done-org" id="suOverlayOrgName"></div>
                <div class="su-overlay-done-msg">Taking you to your dashboard…</div>
                <div class="su-overlay-done-dots">
                    <span></span><span></span><span></span>
                </div>
            </div>
        </div>

        <!-- Google sign-in confirmation card -->
        <div class="su-gconf-overlay" id="suGconfOverlay">
            <div class="su-gconf-card">
                <div class="su-gconf-site">Sign in to this application with Google</div>
                <img class="su-gconf-avatar" id="suGconfAvatar" src="" alt="" style="display:none;">
                <div class="su-gconf-avatar-placeholder" id="suGconfAvatarPlaceholder"></div>
                <div class="su-gconf-name" id="suGconfName"></div>
                <div class="su-gconf-email" id="suGconfEmail"></div>

                <!-- Plan selection inside Google overlay -->
                <div class="su-gconf-plan-section">
                    <div class="su-gconf-plan-label">Select your plan</div>
                    <div id="suGconfPlansLoading" style="text-align:center;padding:0.75rem 0;color:rgba(160,200,220,0.5);font-size:0.82rem;">
                        <i class="bx bx-loader-alt bx-spin"></i> Loading plans…
                    </div>
                    <div id="suGconfPlanCards" style="display:none;"></div>
                    <div id="suGconfPlansError" style="display:none;color:rgba(248,113,113,0.8);font-size:0.82rem;padding:0.4rem 0;text-align:center;">
                        <span id="suGconfPlansErrorText">Could not load plans.</span>
                    </div>
                </div>

                <button type="button" class="su-gconf-btn-continue" id="suGconfContinueBtn" onclick="suConfirmGoogle()">
                    <svg width="17" height="17" viewBox="0 0 18 18" style="flex-shrink:0;"><path fill="#4285F4" d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.615z"/><path fill="#34A853" d="M9 18c2.43 0 4.467-.806 5.956-2.18l-2.908-2.259c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332A8.997 8.997 0 0 0 9 18z"/><path fill="#FBBC05" d="M3.964 10.71A5.41 5.41 0 0 1 3.682 9c0-.593.102-1.17.282-1.71V4.958H.957A8.996 8.996 0 0 0 0 9c0 1.452.348 2.827.957 4.042l3.007-2.332z"/><path fill="#EA4335" d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0A8.997 8.997 0 0 0 .957 4.958L3.964 6.29C4.672 4.163 6.656 3.58 9 3.58z"/></svg>
                    Continue as <span id="suGconfFirstName"></span>
                </button>
                <button type="button" class="su-gconf-btn-switch" onclick="suCancelGoogle()">Use a different account</button>
            </div>
        </div>

        <!-- Language switcher pill -->
        <div class="su-lang-switch" id="suLangSwitch">
            <button class="su-lang-trigger" id="suLangTrigger" type="button" aria-label="Switch language">
                <span id="suLangLabel">En</span>
                <i class="bx bx-chevron-down su-lang-chevron"></i>
            </button>
            <div class="su-lang-dropdown" id="suLangDropdown">
                <button class="su-lang-opt su-lang-active" data-lang="en" type="button">&#127760; English</button>
                <button class="su-lang-opt" data-lang="ta" type="button">&#127760; Tamil (த)</button>
            </div>
        </div>

        <div class="su-form-wrap">

            <!-- Step indicator -->
            <div class="su-steps" id="suStepIndicator">
                <div class="su-step active" id="suStep1Ind">
                    <div class="su-step-num">1</div>
                    <div class="su-step-label">Organisation</div>
                </div>
                <div class="su-step-line"></div>
                <div class="su-step" id="suStep2Ind">
                    <div class="su-step-num">2</div>
                    <div class="su-step-label">Admin Account</div>
                </div>
                <div class="su-step-line"></div>
                <div class="su-step" id="suStep3Ind">
                    <div class="su-step-num">3</div>
                    <div class="su-step-label">Select Plan</div>
                </div>
            </div>

            <!-- Alert -->
            <div class="su-alert su-alert-err" id="suAlert">
                <i class="bx bx-error-circle" style="font-size:1.1rem;flex-shrink:0;margin-top:1px;"></i>
                <span id="suAlertText"></span>
            </div>

            <!-- ── STEP 1: ORGANISATION ── -->
            <div id="suFormStep1">
                <h2 class="su-step-title">Organisation Details</h2>
                <p class="su-step-hint">Basic information about your business.</p>

                <!-- Google One Tap fill -->
                <?php if (!empty(getenv('GOOGLE_CLIENT_ID'))): ?>
                <div id="suGoogleWrap" style="margin-bottom:1.25rem;">
                    <button type="button" id="suGoogleFillBtn" onclick="suTriggerGoogle()" style="width:100%;display:flex;align-items:center;justify-content:center;gap:0.7rem;padding:0.68rem 1rem;border-radius:8px;border:1px solid rgba(96,165,200,0.22);background:rgba(255,255,255,0.04);color:#c8dff0;font-size:0.88rem;font-weight:500;cursor:pointer;font-family:inherit;transition:all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.08)'" onmouseout="this.style.background='rgba(255,255,255,0.04)'">
                        <svg width="17" height="17" viewBox="0 0 18 18" style="flex-shrink:0;"><path fill="#4285F4" d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.615z"/><path fill="#34A853" d="M9 18c2.43 0 4.467-.806 5.956-2.18l-2.908-2.259c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332A8.997 8.997 0 0 0 9 18z"/><path fill="#FBBC05" d="M3.964 10.71A5.41 5.41 0 0 1 3.682 9c0-.593.102-1.17.282-1.71V4.958H.957A8.996 8.996 0 0 0 0 9c0 1.452.348 2.827.957 4.042l3.007-2.332z"/><path fill="#EA4335" d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0A8.997 8.997 0 0 0 .957 4.958L3.964 6.29C4.672 4.163 6.656 3.58 9 3.58z"/></svg>
                        Sign up with Google
                    </button>
                </div>
                <div style="display:flex;align-items:center;gap:0.7rem;margin-bottom:1.25rem;">
                    <div style="flex:1;height:1px;background:rgba(96,165,200,0.13);"></div>
                    <span class="su-or-divider" style="font-size:0.72rem;color:rgba(160,190,215,0.38);white-space:nowrap;">or fill manually</span>
                    <div style="flex:1;height:1px;background:rgba(96,165,200,0.13);"></div>
                </div>
                <?php endif; ?>

                <div class="su-field-row" style="grid-template-columns: 1fr 140px;">
                    <div class="su-field">
                        <label class="su-label" for="suOrgName">Organisation Name</label>
                        <input type="text" id="suOrgName" name="OrgName" class="su-input" placeholder="e.g. Rishika 2K Enterprises" autocomplete="off" maxlength="150">
                        <div class="su-field-err" id="suOrgNameErr"></div>
                    </div>
                    <div class="su-field">
                        <label class="su-label" for="suShortCode">Short Code</label>
                        <input type="text" id="suShortCode" name="ShortCode" class="su-input" placeholder="ABC" maxlength="3" autocomplete="off" style="text-transform:uppercase;letter-spacing:0.15em;font-weight:600;">
                        <div class="su-field-hint">3 letters · org prefix</div>
                        <div class="su-field-err" id="suShortCodeErr"></div>
                    </div>
                </div>

                <div class="su-field-row">
                    <div class="su-field">
                        <label class="su-label" for="suOrgMobile">Mobile Number</label>
                        <div class="su-phone-wrap" id="suOrgMobileWrap">
                            <span class="su-phone-prefix" id="suPhonePrefixLabel">+91</span>
                            <input type="hidden" id="suOrgCountryCode" value="+91">
                            <input type="tel" id="suOrgMobile" name="OrgMobile" class="su-phone-input" placeholder="10-digit number" autocomplete="off" maxlength="10" pattern="[0-9]{10}">
                        </div>
                        <div class="su-field-err" id="suOrgMobileErr"></div>
                        <div class="su-field-ok" id="suOrgMobileOk">Mobile is available</div>
                    </div>
                    <div class="su-field">
                        <label class="su-label" for="suOrgEmail">Email Address</label>
                        <input type="email" id="suOrgEmail" name="OrgEmail" class="su-input" placeholder="admin@company.com" autocomplete="off">
                        <div class="su-field-err" id="suOrgEmailErr"></div>
                        <div class="su-field-ok" id="suOrgEmailOk">Email is available</div>
                    </div>
                </div>

                <div class="su-field">
                    <label class="su-label" for="suState">State</label>
                    <select id="suState" name="State" class="su-input">
                        <option value="">Select state...</option>
                    </select>
                    <div class="su-field-err" id="suStateErr"></div>
                </div>

                <div class="su-field">
                    <label class="su-label" for="suTimezone">Timezone</label>
                    <select id="suTimezone" name="suTimezone" class="su-input">
                        <option value="">Select timezone...</option>
                    </select>
                    <div class="su-field-err" id="suTimezoneErr"></div>
                </div>

                <div class="su-field">
                    <label class="su-label" for="suGSTIN">GSTIN <span class="su-optional">(optional)</span></label>
                    <input type="text" id="suGSTIN" name="GSTIN" class="su-input" placeholder="22AAAAA0000A1Z5" autocomplete="off" maxlength="15" style="text-transform:uppercase;">
                    <div class="su-field-err" id="suGSTINErr"></div>
                    <div class="su-field-ok" id="suGSTINOk">GSTIN is available</div>
                </div>

                <div class="su-btn-row">
                    <button type="button" class="su-btn su-btn-primary" id="suNextBtn" onclick="suNextStep()" disabled>
                        Next <i class="bx bx-right-arrow-alt"></i>
                    </button>
                </div>
            </div>

            <!-- ── STEP 2: ADMIN ACCOUNT ── -->
            <div id="suFormStep2" style="display:none;">
                <h2 class="su-step-title">Admin Account</h2>
                <p class="su-step-hint">This account will have full access to your organisation.</p>

                <div class="su-field-row">
                    <div class="su-field">
                        <label class="su-label" for="suFirstName">First Name</label>
                        <input type="text" id="suFirstName" name="AdminFirstName" class="su-input" placeholder="First name" autocomplete="off" maxlength="80">
                        <div class="su-field-err" id="suFirstNameErr"></div>
                    </div>
                    <div class="su-field">
                        <label class="su-label" for="suLastName">Last Name <span class="su-optional">(optional)</span></label>
                        <input type="text" id="suLastName" name="AdminLastName" class="su-input" placeholder="Last name" autocomplete="off" maxlength="80">
                    </div>
                </div>

                <div class="su-field">
                    <label class="su-label" for="suUsername">Username</label>
                    <input type="text" id="suUsername" name="AdminUsername" class="su-input" placeholder="Used to log in" autocomplete="off" maxlength="80">
                    <div class="su-field-err" id="suUsernameErr"></div>
                    <div class="su-field-ok" id="suUsernameOk">Username is available</div>
                </div>

                <div class="su-field">
                    <label class="su-label" for="suPassword">Password</label>
                    <div class="su-pw-wrap">
                        <input type="password" id="suPassword" name="AdminPassword" class="su-input" placeholder="At least 8 characters" autocomplete="off" maxlength="64">
                    </div>
                    <div class="su-strength" id="suPwStrength">
                        <div class="su-strength-seg" id="suSeg1"></div>
                        <div class="su-strength-seg" id="suSeg2"></div>
                        <div class="su-strength-seg" id="suSeg3"></div>
                        <div class="su-strength-seg" id="suSeg4"></div>
                    </div>
                    <div class="su-strength-label" id="suPwStrengthLabel"></div>

                    <!-- Password criteria -->
                    <ul class="su-pw-criteria" id="suPwCriteria" hidden>
                        <li class="su-pw-criterion" id="suCritLen">
                            <span class="su-crit-icon"><i class="bx bx-minus"></i></span>
                            Minimum 8 characters, maximum 64
                        </li>
                        <li class="su-pw-criterion" id="suCritUpper">
                            <span class="su-crit-icon"><i class="bx bx-minus"></i></span>
                            At least 1 uppercase letter (A–Z)
                        </li>
                        <li class="su-pw-criterion" id="suCritLower">
                            <span class="su-crit-icon"><i class="bx bx-minus"></i></span>
                            At least 1 lowercase letter (a–z)
                        </li>
                        <li class="su-pw-criterion" id="suCritNum">
                            <span class="su-crit-icon"><i class="bx bx-minus"></i></span>
                            At least 1 number (0–9)
                        </li>
                        <li class="su-pw-criterion" id="suCritSpecial">
                            <span class="su-crit-icon"><i class="bx bx-minus"></i></span>
                            At least 1 special character (!&nbsp;@&nbsp;#&nbsp;$&nbsp;%&nbsp;^&nbsp;&amp;&nbsp;*)
                        </li>
                        <li class="su-pw-criterion" id="suCritNoUser">
                            <span class="su-crit-icon"><i class="bx bx-minus"></i></span>
                            Must not contain your username or email
                        </li>
                        <li class="su-pw-criterion" id="suCritNotCommon">
                            <span class="su-crit-icon"><i class="bx bx-minus"></i></span>
                            Must not be a commonly used password
                        </li>
                    </ul>

                    <div class="su-field-err" id="suPasswordErr"></div>
                </div>

                <div class="su-field">
                    <label class="su-label" for="suConfirmPassword">Confirm Password</label>
                    <div class="su-pw-wrap">
                        <input type="password" id="suConfirmPassword" name="ConfirmPassword" class="su-input" placeholder="Re-enter password" autocomplete="off" maxlength="100">
                        <button type="button" class="su-pw-toggle" onclick="suTogglePw('suConfirmPassword', this)" tabindex="-1">
                            <i class="bx bx-hide"></i>
                        </button>
                    </div>
                    <div class="su-field-err" id="suConfirmPasswordErr"></div>
                    <div class="su-field-ok" id="suConfirmPasswordOk">Passwords match</div>
                </div>

                <div class="su-btn-row">
                    <button type="button" class="su-btn su-btn-ghost" onclick="suPrevStep()">
                        <i class="bx bx-left-arrow-alt"></i> Back
                    </button>
                    <button type="button" class="su-btn su-btn-primary" id="suNextStep2Btn" onclick="suNextStep2()" disabled>
                        <span id="suNextStep2Label">Next</span>
                        <i class="bx bx-right-arrow-alt"></i>
                    </button>
                </div>
            </div>

            <!-- ── STEP 3: SELECT PLAN ── -->
            <div id="suFormStep3" style="display:none;">
                <h2 class="su-step-title" id="suStep3Title">Select Your Plan</h2>
                <p class="su-step-hint" id="suStep3Hint">Choose the plan that fits your business.</p>

                <!-- Plan loading state -->
                <div id="suPlansLoading" class="su-plans-loading">
                    <i class="bx bx-loader-alt bx-spin"></i>
                    <span>Loading plans…</span>
                </div>

                <!-- Plan cards container -->
                <div id="suPlanCards" style="display:none;"></div>

                <!-- Plan error -->
                <div id="suPlansError" style="display:none;text-align:center;padding:1.5rem 0;color:rgba(248,113,113,0.8);font-size:0.84rem;">
                    <i class="bx bx-error-circle" style="font-size:1.3rem;display:block;margin-bottom:0.4rem;"></i>
                    <span id="suPlansErrorText">Could not load plans. Please go back and try again.</span>
                </div>

                <input type="hidden" id="suSectorPlanUID" name="SectorPlanUID" value="0">

                <div class="su-btn-row" style="margin-top:1.25rem;">
                    <button type="button" class="su-btn su-btn-ghost" onclick="suPrevStep2()">
                        <i class="bx bx-left-arrow-alt"></i> Back
                    </button>
                    <button type="button" class="su-btn su-btn-primary" id="suSubmitBtn" onclick="suSubmit()" disabled>
                        <span id="suSubmitLabel">Create Account</span>
                        <i class="bx bx-check-circle" id="suSubmitIcon"></i>
                        <i class="bx bx-loader-alt bx-spin" id="suSubmitLoader" style="display:none;"></i>
                    </button>
                </div>
            </div>

            <!-- ── SUCCESS SCREEN ── -->
            <div class="su-success" id="suSuccess">
                <div class="su-success-icon"><i class="bx bx-envelope"></i></div>
                <h2 class="su-success-title">Verify your email</h2>
                <p class="su-success-msg">
                    Your organisation account has been created successfully!<br><br>
                    We've sent a verification link to<br>
                    <strong id="suSuccessEmail"></strong><br><br>
                    Please check your inbox and click the link to verify your email address.<br>
                    <span style="color:rgba(248,113,113,0.9);font-size:0.82rem;">
                        You will not be able to log in until your email is verified.
                    </span>
                </p>
                <a href="/portal" class="su-btn su-btn-primary" style="display:inline-flex;text-decoration:none;justify-content:center;">
                    <i class="bx bx-log-in-circle"></i> Go to Sign In
                </a>
            </div>

            <p class="su-login-link" id="suLoginLink">
                Already have an account? <a href="/portal">Sign in</a>
            </p>

        </div>
    </div>
</div>

<script src="/assets/js/services/upstash-service.js"></script>
<script>
(function () {

    /* ── Language switcher ─────────────────────────────────────────── */
    var _suStrings = {
        en: {
            stepOrg:          'Organisation',
            stepAdmin:         'Admin',
            stepPlan:          'Select Plan',
            titleOrg:          'Organisation Details',
            hintOrg:           'Basic information about your business.',
            titleAdmin:        'Admin Account',
            hintAdmin:         'This account will have full access to your organisation.',
            titlePlan:         'Select Your Plan',
            hintPlan:          'Choose the plan that fits your business.',
            btnNext:           'Next',
            btnCreate:         'Create Account',
            btnBack:           'Back',
            btnSignin:         'Already have an account?',
            btnSigninLink:     'Sign in',
            labelOrgName:      'Organisation Name',
            labelShortCode:    'Short Code',
            hintShortCode:     '3 letters · org prefix',
            labelMobile:       'Mobile Number',
            labelEmail:        'Email Address',
            labelState:        'State',
            labelTimezone:     'Timezone',
            labelGSTIN:        'GSTIN',
            labelFirstName:    'First Name',
            labelLastName:     'Last Name',
            labelUsername:     'Username',
            labelPassword:     'Password',
            labelConfirm:      'Confirm Password',
            optional:          '(optional)',
            orFill:            'or fill manually',
            phOrgName:         'e.g. Rishika 2K Enterprises',
            phShortCode:       'ABC',
            phMobile:          '10-digit number',
            phEmail:           'admin@company.com',
            phStateSelect:     'Select state...',
            phTimezoneSelect:  'Select timezone...',
            phGSTIN:           '22AAAAA0000A1Z5',
            phFirstName:       'First name',
            phLastName:        'Last name',
            phUsername:        'Used to log in',
            phPassword:        'At least 8 characters',
            phConfirm:         'Re-enter password',
        },
        ta: {
            stepOrg:          'நிறுவனம்',
            stepAdmin:         'நிர்வாகி',
            stepPlan:          'திட்டம் தேர்வு',
            titleOrg:          'நிறுவன விவரங்கள்',
            hintOrg:           'உங்கள் வணிகம் பற்றிய அடிப்படை தகவல்.',
            titleAdmin:        'நிர்வாகி கணக்கு',
            hintAdmin:         'இந்த கணக்கிற்கு உங்கள் நிறுவனத்தில் முழு அணுகல் இருக்கும்.',
            titlePlan:         'உங்கள் திட்டத்தை தேர்வு செய்யுங்கள்',
            hintPlan:          'உங்கள் வணிகத்திற்கு ஏற்ற திட்டத்தை தேர்ந்தெடுக்கவும்.',
            btnNext:           'அடுத்தது',
            btnCreate:         'கணக்கை உருவாக்கு',
            btnBack:           'திரும்பு',
            btnSignin:         'ஏற்கனவே கணக்கு உள்ளதா?',
            btnSigninLink:     'உள்நுழைக',
            labelOrgName:      'நிறுவனத்தின் பெயர்',
            labelShortCode:    'குறுகிய குறியீடு',
            hintShortCode:     '3 எழுத்துகள் · org முன்னொட்டு',
            labelMobile:       'கைபேசி எண்',
            labelEmail:        'மின்னஞ்சல் முகவரி',
            labelState:        'மாநிலம்',
            labelTimezone:     'நேர மண்டலம்',
            labelGSTIN:        'ஜிஎஸ்டிஐஎன்',
            labelFirstName:    'முதல் பெயர்',
            labelLastName:     'கடைசி பெயர்',
            labelUsername:     'பயனர்பெயர்',
            labelPassword:     'கடவுச்சொல்',
            labelConfirm:      'கடவுச்சொல்லை உறுதிப்படுத்தவும்',
            optional:          '(விருப்பமானது)',
            orFill:            'அல்லது கைமுறையாக நிரப்பவும்',
            phOrgName:         'எ.கா. ரிஷிகா 2கே எண்டர்பிரைசஸ்',
            phShortCode:       'ABC',
            phMobile:          '10-இலக்க எண்',
            phEmail:           'admin@company.com',
            phStateSelect:     'மாநிலத்தைத் தேர்ந்தெடுக்கவும்...',
            phTimezoneSelect:  'நேர மண்டலத்தைத் தேர்ந்தெடுக்கவும்...',
            phGSTIN:           '22AAAAA0000A1Z5',
            phFirstName:       'முதல் பெயர்',
            phLastName:        'கடைசி பெயர்',
            phUsername:        'உள்நுழைய பயன்படுகிறது',
            phPassword:        'குறைந்தது 8 எழுத்துகள்',
            phConfirm:         'கடவுச்சொல்லை மீண்டும் உள்ளிடவும்',
        }
    };

    function _suApplyI18n(lang) {
        var s   = _suStrings[lang] || _suStrings.en;
        var txt = function (sel, val) { var el = document.querySelector(sel); if (el) el.textContent = val; };
        /* Set a label that may contain an (optional) span — replaces text node only */
        var lbl = function (forId, val, hasOptional) {
            var el = document.querySelector('label[for="' + forId + '"]');
            if (!el) return;
            if (hasOptional) {
                el.innerHTML = val + ' <span class="su-optional">' + s.optional + '</span>';
            } else {
                el.textContent = val;
            }
        };

        /* Step indicators */
        txt('#suStep1Ind .su-step-label',  s.stepOrg);
        txt('#suStep2Ind .su-step-label',  s.stepAdmin);
        txt('#suStep3Ind .su-step-label',  s.stepPlan);

        /* Section titles */
        txt('#suFormStep1 .su-step-title', s.titleOrg);
        txt('#suFormStep1 .su-step-hint',  s.hintOrg);
        txt('#suFormStep2 .su-step-title', s.titleAdmin);
        txt('#suFormStep2 .su-step-hint',  s.hintAdmin);
        txt('#suStep3Title',               s.titlePlan);
        txt('#suStep3Hint',                s.hintPlan);

        /* Step 1 labels */
        lbl('suOrgName',  s.labelOrgName,  false);
        lbl('suShortCode',s.labelShortCode,false);
        lbl('suOrgMobile',s.labelMobile,   false);
        lbl('suOrgEmail', s.labelEmail,    false);
        lbl('suState',    s.labelState,    false);
        lbl('suTimezone', s.labelTimezone, false);
        lbl('suGSTIN',    s.labelGSTIN,    true);
        txt('.su-field-hint', s.hintShortCode);

        /* Step 2 labels */
        lbl('suFirstName',      s.labelFirstName, false);
        lbl('suLastName',       s.labelLastName,  true);
        lbl('suUsername',       s.labelUsername,  false);
        lbl('suPassword',       s.labelPassword,  false);
        lbl('suConfirmPassword',s.labelConfirm,   false);

        /* Buttons */
        var nextBtn = document.getElementById('suNextBtn');
        if (nextBtn) nextBtn.innerHTML = s.btnNext + ' <i class="bx bx-right-arrow-alt"></i>';
        txt('#suSubmitLabel', s.btnCreate);
        var backBtn = document.querySelector('#suFormStep2 .su-btn-ghost');
        if (backBtn) backBtn.innerHTML = '<i class="bx bx-left-arrow-alt"></i> ' + s.btnBack;

        /* Placeholders */
        var ph = function (id, val) { var el = document.getElementById(id); if (el) el.placeholder = val; };
        var phOpt = function (id, val) { var el = document.querySelector('#' + id + ' option[value=""]'); if (el) el.textContent = val; };
        ph('suOrgName',         s.phOrgName);
        ph('suShortCode',       s.phShortCode);
        ph('suOrgMobile',       s.phMobile);
        ph('suOrgEmail',        s.phEmail);
        ph('suGSTIN',           s.phGSTIN);
        ph('suFirstName',       s.phFirstName);
        ph('suLastName',        s.phLastName);
        ph('suUsername',        s.phUsername);
        ph('suPassword',        s.phPassword);
        ph('suConfirmPassword', s.phConfirm);
        phOpt('suState',    s.phStateSelect);
        phOpt('suTimezone', s.phTimezoneSelect);

        /* Divider & login link */
        document.querySelectorAll('.su-or-divider').forEach(function(el){ el.textContent = s.orFill; });
        var loginLink = document.getElementById('suLoginLink');
        if (loginLink) loginLink.innerHTML = s.btnSignin + ' <a href="/portal">' + s.btnSigninLink + '</a>';
    }

    (function () {
        var switchEl  = document.getElementById('suLangSwitch');
        var triggerEl = document.getElementById('suLangTrigger');
        var labelEl   = document.getElementById('suLangLabel');
        var opts      = document.querySelectorAll('.su-lang-opt');
        if (!switchEl || !triggerEl) return;

        triggerEl.addEventListener('click', function (e) {
            e.stopPropagation();
            switchEl.classList.toggle('open');
        });

        opts.forEach(function (opt) {
            opt.addEventListener('click', function () {
                var lang = opt.getAttribute('data-lang');
                labelEl.textContent = lang === 'ta' ? 'த' : 'En';
                opts.forEach(function (o) { o.classList.remove('su-lang-active'); });
                opt.classList.add('su-lang-active');
                switchEl.classList.remove('open');
                _suApplyI18n(lang);
            });
        });

        document.addEventListener('click', function () {
            switchEl.classList.remove('open');
        });
    }());

    /* ── State ─────────────────────────────────────────────────────── */
    var currentStep = 1;
    var emailCheckTimer    = null;
    var usernameCheckTimer = null;
    var _emailTaken        = false;
    var _emailAvailable    = false;
    var _mobileTaken       = false;
    var _mobileAvailable   = false;
    var _emailChecking     = false;
    var _mobileChecking    = false;
    var _gstinTaken        = false;
    var _gstinChecking     = false;
    var _usernameTaken     = false;
    var _usernameChecking  = false;
    var _usernameAvailable = false;

    /**
     * Enables Next only when every Step 1 field is clean.
     * @returns {void}
     */
    function _syncNextBtn() {
        var ok = true;
        if (!document.getElementById('suOrgName').value.trim()) ok = false;
        var sc = document.getElementById('suShortCode').value.toUpperCase().trim();
        if (!/^[A-Z]{3}$/.test(sc)) ok = false;
        var mobile = document.getElementById('suOrgMobile').value.replace(/\D/g, '');
        if (mobile.length !== 10 || !_mobileAvailable || _mobileTaken || _mobileChecking) ok = false;
        var email = document.getElementById('suOrgEmail').value.trim();
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email) || !_emailAvailable || _emailTaken || _emailChecking) ok = false;
        if (!document.getElementById('suState').value) ok = false;
        if (!document.getElementById('suTimezone').value) ok = false;
        var gstin = document.getElementById('suGSTIN').value.trim().toUpperCase();
        if (gstin && (!/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/.test(gstin) || _gstinTaken || _gstinChecking)) ok = false;
        document.getElementById('suNextBtn').disabled = !ok;
    }

    /**
     * Enables Step 2 "Next" button when all Step 2 fields are clean.
     * @returns {void}
     */
    function _syncNextStep2Btn() {
        var ok = true;
        if (!document.getElementById('suFirstName').value.trim()) ok = false;
        var uname = document.getElementById('suUsername').value.trim();
        if (!uname || !_usernameAvailable || _usernameChecking) ok = false;
        var pw  = document.getElementById('suPassword').value;
        var cpw = document.getElementById('suConfirmPassword').value;
        if (pw.length < 8 || !cpw || pw !== cpw) ok = false;
        document.getElementById('suNextStep2Btn').disabled = !ok;
    }

    /**
     * Enables Create Account only when a plan is selected.
     * @returns {void}
     */
    function _syncSubmitBtn() {
        var planUID = parseInt(document.getElementById('suSectorPlanUID').value || '0', 10);
        document.getElementById('suSubmitBtn').disabled = (planUID <= 0);
    }

    /* States loaded from Upstash global cache — key: r2k-loc-states */

    /* ── Auto-trim / strip spaces on paste, drop, blur ─────────────── */
    /* stripSpaceFields: ALL spaces removed (mobile, email)             */
    /* trimFields: only leading/trailing spaces removed (everything else) */
    (function () {
        var stripSpaceFields = ['suOrgMobile', 'suOrgEmail'];
        var trimOnlyFields   = ['suOrgName', 'suShortCode', 'suGSTIN',
                                'suFirstName', 'suLastName', 'suUsername'];

        function attachHandlers(id, cleanFn) {
            var el = document.getElementById(id);
            if (!el) return;

            function applyClean() {
                var cleaned = cleanFn(el.value);
                if (el.value !== cleaned) {
                    el.value = cleaned;
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                }
            }

            el.addEventListener('paste', function () { setTimeout(applyClean, 0); });
            el.addEventListener('drop',  function () { setTimeout(applyClean, 0); });
            el.addEventListener('blur',  applyClean);
        }

        /* Clean functions per field.
           Mobile: strip spaces + remove +91/91 country code prefix.
           Email:  strip all spaces only.
           Paste is intercepted directly so maxlength truncation cannot
           eat characters before we clean them. */
        var cleanFnMap = {
            suOrgMobile: function (v) {
                v = v.replace(/\s+/g, '');          /* remove all spaces    */
                if (v.slice(0, 3) === '+91') v = v.slice(3);
                else if (v.slice(0, 2) === '91' && v.length >= 12) v = v.slice(2);
                return v.replace(/\D/g, '');         /* digits only          */
            },
            suOrgEmail: function (v) { return v.replace(/\s+/g, ''); },
        };

        stripSpaceFields.forEach(function (id) {
            var el = document.getElementById(id);
            if (!el) return;

            var cleanFn = cleanFnMap[id] || function (v) { return v.replace(/\s+/g, ''); };

            el.addEventListener('paste', function (e) {
                e.preventDefault();
                var raw     = (e.clipboardData || window.clipboardData).getData('text');
                var cleaned = cleanFn(raw);
                /* Respect maxlength if present */
                var max = parseInt(el.getAttribute('maxlength'), 10);
                if (!isNaN(max)) cleaned = cleaned.slice(0, max);
                el.value = cleaned;
                el.dispatchEvent(new Event('input', { bubbles: true }));
            });

            el.addEventListener('drop', function () { setTimeout(function () {
                var cleaned = cleanFn(el.value);
                if (el.value !== cleaned) {
                    el.value = cleaned;
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                }
            }, 0); });

            el.addEventListener('blur', function () {
                var cleaned = cleanFn(el.value);
                if (el.value !== cleaned) {
                    el.value = cleaned;
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                }
            });
        });

        /* Remove only leading / trailing spaces */
        trimOnlyFields.forEach(function (id) {
            attachHandlers(id, function (v) { return v.trim(); });
        });
    }());

    /* ── ShortCode: auto-suggest from org name ─────────────────────── */
    var _shortCodeManuallyEdited = false;

    document.getElementById('suOrgName').addEventListener('input', function () {
        if (!_shortCodeManuallyEdited) {
            var clean = this.value.replace(/[^A-Za-z]/g, '').toUpperCase();
            document.getElementById('suShortCode').value = clean.substring(0, 3);
            clearErr('suShortCode');
        }
        _syncNextBtn();
    });

    document.getElementById('suShortCode').addEventListener('input', function () {
        _shortCodeManuallyEdited = true;
        var pos = this.selectionStart;
        this.value = this.value.toUpperCase().replace(/[^A-Z]/g, '');
        this.setSelectionRange(pos, pos);
        clearErr('suShortCode');
        _syncNextBtn();
    });

    document.getElementById('suState').addEventListener('change', function () { _syncNextBtn(); });
    document.getElementById('suTimezone').addEventListener('change', function () { _syncNextBtn(); });

    /* ── Populate state dropdown from Upstash global cache ─────────── */
    /* Key: r2k-loc-states (HASH), field: 'in', each row: {id, name, iso2} */
    (function seedStates() {
        if (!UpstashService.isEnabled()) return;
        UpstashService.hget(UpstashService.globalKey('loc-states'), 'in').then(function (data) {
            if (!Array.isArray(data) || data.length === 0) return;
            var sel = document.getElementById('suState');
            data.forEach(function (s) {
                var code = s.iso2 || '';
                var name = s.name || '';
                if (!code || !name) return;
                var opt = document.createElement('option');
                opt.value = code + '|' + name;
                opt.textContent = name;
                sel.appendChild(opt);
            });
        });
    }());

    /* ── Timezone data from DB ─────────────────────────────────────── */
    var _suTimezones = <?php echo json_encode(array_map(function($tz) {
        return ['uid' => (int)$tz->TimezoneUID, 'label' => $tz->Timezone . ' (' . $tz->GmtOffset . ') — ' . $tz->CountryName];
    }, $timezones ?? [])); ?>;

    /* ── Populate timezone dropdown ─────────────────────────────────── */
    (function seedTimezones() {
        var sel = document.getElementById('suTimezone');
        _suTimezones.forEach(function (tz) {
            var opt = document.createElement('option');
            opt.value = tz.uid;
            opt.textContent = tz.label;
            if (tz.uid === 181) opt.selected = true; // Default: Asia/Kolkata
            sel.appendChild(opt);
        });
    }());

    /* ── CSS rain ──────────────────────────────────────────────────── */
    (function buildRain() {
        var canvas = document.getElementById('suRainCanvas');
        if (!canvas) return;
        var frag = document.createDocumentFragment();
        for (var i = 0; i < 55; i++) {
            var d = document.createElement('div');
            d.className = 'su-rain-drop';
            d.style.left       = (Math.random() * 110 - 5) + '%';
            d.style.height     = (12 + Math.random() * 22) + 'px';
            d.style.opacity    = (0.2 + Math.random() * 0.45).toFixed(2);
            d.style.animationDuration  = (0.55 + Math.random() * 0.7) + 's';
            d.style.animationDelay     = (-Math.random() * 1.5) + 's';
            frag.appendChild(d);
        }
        canvas.appendChild(frag);
    }());

    /* ── Helpers ───────────────────────────────────────────────────── */
    /**
     * @param {string} id
     * @param {string} msg
     */
    function showErr(id, msg) {
        var el = document.getElementById(id + 'Err');
        if (!el) return;
        el.textContent = msg;
        el.classList.add('show');
    }

    /**
     * @param {string} id
     */
    function clearErr(id) {
        var el = document.getElementById(id + 'Err');
        if (el) el.classList.remove('show');
    }

    /**
     * @param {string} id
     * @param {boolean} valid
     */
    function setInputState(id, valid) {
        /* For wrapped inputs (e.g. phone prefix), target the wrapper */
        var el = document.getElementById(id + 'Wrap') || document.getElementById(id);
        if (!el) return;
        el.classList.toggle('valid', valid);
        el.classList.toggle('error', !valid);
    }

    /**
     * @param {string} msg
     */
    function showAlert(msg) {
        var a = document.getElementById('suAlert');
        document.getElementById('suAlertText').textContent = msg;
        a.classList.add('show');
    }

    function hideAlert() {
        document.getElementById('suAlert').classList.remove('show');
    }

    /* ── Step navigation ───────────────────────────────────────────── */
    window.suNextStep = function () {
        if (!validateStep1()) return;
        currentStep = 2;
        document.getElementById('suFormStep1').style.display = 'none';
        document.getElementById('suFormStep2').style.display = '';
        document.getElementById('suStep1Ind').className = 'su-step done';
        document.getElementById('suStep2Ind').className = 'su-step active';
        hideAlert();
        suggestUsername();
        var uname = document.getElementById('suUsername').value.trim().toLowerCase();
        _runUsernameCheck(uname);
    };

    window.suPrevStep = function () {
        currentStep = 1;
        document.getElementById('suFormStep2').style.display = 'none';
        document.getElementById('suFormStep1').style.display = '';
        document.getElementById('suStep1Ind').className = 'su-step active';
        document.getElementById('suStep2Ind').className = 'su-step';
        hideAlert();
    };

    /* Step 2 → 3 */
    window.suNextStep2 = function () {
        if (!validateStep2()) return;
        currentStep = 3;
        document.getElementById('suFormStep2').style.display = 'none';
        document.getElementById('suFormStep3').style.display = '';
        document.getElementById('suStep2Ind').className = 'su-step done';
        document.getElementById('suStep3Ind').className = 'su-step active';
        hideAlert();
        _suLoadPlans();
    };

    /* Step 3 → 2 */
    window.suPrevStep2 = function () {
        currentStep = 2;
        document.getElementById('suFormStep3').style.display = 'none';
        document.getElementById('suFormStep2').style.display = '';
        document.getElementById('suStep2Ind').className = 'su-step active';
        document.getElementById('suStep3Ind').className = 'su-step';
        hideAlert();
    };

    /* ── Step 1 validation ─────────────────────────────────────────── */
    /**
     * @returns {boolean}
     */
    function validateStep1() {
        var ok = true;

        var orgName = document.getElementById('suOrgName').value.trim();
        clearErr('suOrgName');
        if (!orgName) {
            showErr('suOrgName', 'Organisation name is required.');
            setInputState('suOrgName', false);
            ok = false;
        }

        var shortCode = document.getElementById('suShortCode').value.trim().toUpperCase();
        clearErr('suShortCode');
        if (!shortCode || shortCode.length !== 3 || !/^[A-Z]{3}$/.test(shortCode)) {
            showErr('suShortCode', 'Short code must be exactly 3 letters (A–Z).');
            setInputState('suShortCode', false);
            ok = false;
        }

        var mobile = document.getElementById('suOrgMobile').value.replace(/\D/g, '');
        clearErr('suOrgMobile');
        if (!mobile || mobile.length !== 10) {
            showErr('suOrgMobile', 'Enter a valid 10-digit mobile number.');
            setInputState('suOrgMobile', false);
            ok = false;
        } else if (_mobileTaken) {
            showErr('suOrgMobile', 'This mobile number is already registered.');
            setInputState('suOrgMobile', false);
            ok = false;
        }

        var email = document.getElementById('suOrgEmail').value.trim();
        clearErr('suOrgEmail');
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showErr('suOrgEmail', 'Enter a valid email address.');
            setInputState('suOrgEmail', false);
            ok = false;
        } else if (_emailTaken) {
            showErr('suOrgEmail', 'This email is already registered.');
            setInputState('suOrgEmail', false);
            ok = false;
        }

        var stateSel = document.getElementById('suState').value;
        clearErr('suState');
        if (!stateSel) {
            showErr('suState', 'Please select a state.');
            setInputState('suState', false);
            ok = false;
        }

        var tzSel = document.getElementById('suTimezone').value;
        clearErr('suTimezone');
        if (!tzSel) {
            showErr('suTimezone', 'Please select a timezone.');
            setInputState('suTimezone', false);
            ok = false;
        }

        var gstin = document.getElementById('suGSTIN').value.trim();
        clearErr('suGSTIN');
        if (gstin && !/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/.test(gstin.toUpperCase())) {
            showErr('suGSTIN', 'Invalid GSTIN format (e.g. 22AAAAA0000A1Z5).');
            setInputState('suGSTIN', false);
            ok = false;
        }

        return ok;
    }

    /* ── Step 2 validation ─────────────────────────────────────────── */
    /**
     * @returns {boolean}
     */
    function validateStep2() {
        var ok = true;

        clearErr('suFirstName');
        if (!document.getElementById('suFirstName').value.trim()) {
            showErr('suFirstName', 'First name is required.');
            setInputState('suFirstName', false);
            ok = false;
        }

        var uname = document.getElementById('suUsername').value.trim();
        clearErr('suUsername');
        if (!uname) {
            showErr('suUsername', 'Username is required.');
            setInputState('suUsername', false);
            ok = false;
        } else if (!/^[a-z0-9_.\-]+$/.test(uname.toLowerCase())) {
            showErr('suUsername', 'Use only letters, numbers, dots, or underscores.');
            setInputState('suUsername', false);
            ok = false;
        }

        var pw = document.getElementById('suPassword').value;
        clearErr('suPassword');
        if (pw.length < 8) {
            showErr('suPassword', 'Password must be at least 8 characters.');
            setInputState('suPassword', false);
            ok = false;
        }

        var cpw = document.getElementById('suConfirmPassword').value;
        clearErr('suConfirmPassword');
        if (pw !== cpw) {
            showErr('suConfirmPassword', 'Passwords do not match.');
            setInputState('suConfirmPassword', false);
            ok = false;
        }

        return ok;
    }

    /* ── Auto-suggest username ─────────────────────────────────────── */
    function suggestUsername() {
        var orgName = document.getElementById('suOrgName').value.trim();
        var uField  = document.getElementById('suUsername');
        if (uField.value) return;
        var slug = orgName.toLowerCase().replace(/[^a-z0-9]/g, '').substring(0, 12);
        if (slug) uField.value = 'admin_' + slug;
    }

    /**
     * Fires the username availability AJAX check for a given value.
     * Called on Next click (auto-suggest) and on manual input change.
     * @param {string} uname
     * @returns {void}
     */
    function _runUsernameCheck(uname) {
        clearTimeout(usernameCheckTimer);
        _usernameTaken     = false;
        _usernameAvailable = false;
        document.getElementById('suUsernameOk').classList.remove('show');
        clearErr('suUsername');
        _syncNextStep2Btn();
        if (!uname || uname.length < 3) return;
        _usernameChecking = true;
        _syncNextStep2Btn();
        fetch('/signup/checkUsername', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'username=' + encodeURIComponent(uname),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            var okEl = document.getElementById('suUsernameOk');
            if (data.available) {
                _usernameTaken     = false;
                _usernameAvailable = true;
                okEl.classList.add('show');
                clearErr('suUsername');
                setInputState('suUsername', true);
            } else {
                _usernameTaken     = true;
                _usernameAvailable = false;
                okEl.classList.remove('show');
                showErr('suUsername', 'This username is already taken.');
                setInputState('suUsername', false);
            }
        })
        .catch(function () {})
        .finally(function () {
            _usernameChecking = false;
            _syncNextStep2Btn();
        });
    }

    /* ── Async checks ──────────────────────────────────────────────── */
    document.getElementById('suOrgEmail').addEventListener('input', function () {
        _emailTaken      = false;
        _emailAvailable  = false;
        document.getElementById('suOrgEmailOk').classList.remove('show');
        clearErr('suOrgEmail');
        _syncNextBtn();
    });

    document.getElementById('suOrgEmail').addEventListener('blur', function () {
        var email = this.value.trim();
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return;
        clearTimeout(emailCheckTimer);
        emailCheckTimer = setTimeout(function () {
            _emailChecking = true;
            _syncNextBtn();
            fetch('/signup/checkEmail', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'email=' + encodeURIComponent(email),
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var okEl  = document.getElementById('suOrgEmailOk');
                var errEl = document.getElementById('suOrgEmailErr');
                if (data.available) {
                    _emailTaken     = false;
                    _emailAvailable = true;
                    okEl.classList.add('show');
                    errEl.classList.remove('show');
                    setInputState('suOrgEmail', true);
                } else {
                    _emailTaken     = true;
                    _emailAvailable = false;
                    okEl.classList.remove('show');
                    showErr('suOrgEmail', 'This email is already registered.');
                    setInputState('suOrgEmail', false);
                }
            })
            .catch(function () {})
            .finally(function () {
                _emailChecking = false;
                _syncNextBtn();
            });
        }, 400);
    });

    document.getElementById('suOrgMobile').addEventListener('input', function () {
        _mobileTaken     = false;
        _mobileAvailable = false;
        document.getElementById('suOrgMobileOk').classList.remove('show');
        clearErr('suOrgMobile');
        _syncNextBtn();
    });

    document.getElementById('suOrgMobile').addEventListener('blur', function () {
        var mobile = this.value.replace(/\D/g, '');
        if (mobile.length !== 10) return;
        _mobileChecking = true;
        _syncNextBtn();
        fetch('/signup/checkMobile', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'mobile=' + encodeURIComponent(mobile),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            var okEl = document.getElementById('suOrgMobileOk');
            if (data.available) {
                _mobileTaken     = false;
                _mobileAvailable = true;
                okEl.classList.add('show');
                clearErr('suOrgMobile');
                setInputState('suOrgMobile', true);
            } else {
                _mobileTaken     = true;
                _mobileAvailable = false;
                okEl.classList.remove('show');
                showErr('suOrgMobile', 'This mobile number is already registered.');
                setInputState('suOrgMobile', false);
            }
        })
        .catch(function () {})
        .finally(function () {
            _mobileChecking = false;
            _syncNextBtn();
        });
    });

    document.getElementById('suFirstName').addEventListener('input', function () { _syncNextStep2Btn(); });

    document.getElementById('suUsername').addEventListener('input', function () {
        var uname = this.value.trim().toLowerCase();
        clearTimeout(usernameCheckTimer);
        usernameCheckTimer = setTimeout(function () { _runUsernameCheck(uname); }, 400);
        // Reset immediately so button disables while debounce is pending
        _usernameAvailable = false;
        _syncNextStep2Btn();
    });

    /* ── Password strength ─────────────────────────────────────────── */
    /**
     * @param {string} pw
     * @returns {number}
     */
    function pwStrength(pw) {
        var score = 0;
        if (pw.length >= 8)  score++;
        if (pw.length >= 12) score++;
        if (/[A-Z]/.test(pw) && /[a-z]/.test(pw)) score++;
        if (/[0-9]/.test(pw)) score++;
        if (/[^A-Za-z0-9]/.test(pw)) score++;
        return Math.min(4, score);
    }

    /* ── Password criteria checker ─────────────────────────────────── */
    var _commonPasswords = [
        'password','password1','password123','password@123','password#123',
        'admin','admin123','admin@123','admin#123','administrator',
        'qwerty','qwerty123','qwerty@123','123456','1234567','12345678',
        '123456789','1234567890','iloveyou','letmein','welcome','welcome1',
        'welcome123','monkey','dragon','master','abc123','pass@123',
        'p@ssword','p@ss123','p@ssw0rd','passw0rd','test123','user123',
        'login123','changeme','superman','sunshine','princess','football'
    ];

    /**
     * Sets a criterion item to pass, fail, or neutral state.
     * @param {string} id - element ID of the <li>
     * @param {boolean|null} state - true=pass, false=fail, null=neutral
     * @returns {void}
     */
    function setCriterion(id, state) {
        var el = document.getElementById(id);
        if (!el) return;
        var icon = el.querySelector('i');
        el.classList.remove('su-crit-pass', 'su-crit-fail');
        if (state === true) {
            el.classList.add('su-crit-pass');
            icon.className = 'bx bx-check';
        } else if (state === false) {
            el.classList.add('su-crit-fail');
            icon.className = 'bx bx-x';
        } else {
            icon.className = 'bx bx-minus';
        }
    }

    /**
     * Evaluates all password criteria and updates the checklist UI.
     * @param {string} pw - current password value
     * @returns {void}
     */
    function checkPwCriteria(pw) {
        var criteriaEl = document.getElementById('suPwCriteria');
        if (!criteriaEl) return;

        if (!pw) {
            criteriaEl.hidden = true;
            ['suCritLen','suCritUpper','suCritLower','suCritNum','suCritSpecial','suCritNoUser','suCritNotCommon']
                .forEach(function (id) { setCriterion(id, null); });
            return;
        }
        criteriaEl.hidden = false;

        var username = (document.getElementById('suUsername') ? document.getElementById('suUsername').value : '').toLowerCase().trim();
        var email    = (document.getElementById('suOrgEmail')  ? document.getElementById('suOrgEmail').value  : '').toLowerCase().trim();
        var emailLocal = email.split('@')[0];
        var pwLower  = pw.toLowerCase();

        setCriterion('suCritLen',       pw.length >= 8 && pw.length <= 64);
        setCriterion('suCritUpper',     /[A-Z]/.test(pw));
        setCriterion('suCritLower',     /[a-z]/.test(pw));
        setCriterion('suCritNum',       /[0-9]/.test(pw));
        setCriterion('suCritSpecial',   /[!@#$%^&*]/.test(pw));
        setCriterion('suCritNoUser',
            !(username   && username.length   > 2 && pwLower.includes(username))   &&
            !(emailLocal && emailLocal.length > 2 && pwLower.includes(emailLocal))
        );
        setCriterion('suCritNotCommon', !_commonPasswords.includes(pwLower));
    }

    /* ── Block paste / drag-drop on password fields ───────────────── */
    ['suPassword', 'suConfirmPassword'].forEach(function (id) {
        var el = document.getElementById(id);
        ['paste', 'drop', 'dragover'].forEach(function (evt) {
            el.addEventListener(evt, function (e) { e.preventDefault(); });
        });
    });

    /* ── Live password match check ─────────────────────────────────── */
    var _confirmTouched = false;

    /**
     * @returns {void}
     */
    function _checkPwMatch() {
        var pw  = document.getElementById('suPassword').value;
        var cpw = document.getElementById('suConfirmPassword').value;
        var okEl = document.getElementById('suConfirmPasswordOk');

        if (!_confirmTouched || !cpw) {
            okEl.classList.remove('show');
            clearErr('suConfirmPassword');
        } else if (pw === cpw) {
            okEl.classList.add('show');
            clearErr('suConfirmPassword');
            setInputState('suConfirmPassword', true);
        } else {
            okEl.classList.remove('show');
            showErr('suConfirmPassword', 'Passwords do not match.');
            setInputState('suConfirmPassword', false);
        }
        _syncNextStep2Btn();
    }

    document.getElementById('suConfirmPassword').addEventListener('input', function () {
        _confirmTouched = true;
        _checkPwMatch();
    });

    document.getElementById('suPassword').addEventListener('input', function () {
        var pw    = this.value;
        var score = pwStrength(pw);
        var labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
        var cls    = ['', 'filled-weak', 'filled-fair', 'filled-good', 'filled-strong'];
        for (var i = 1; i <= 4; i++) {
            var seg = document.getElementById('suSeg' + i);
            seg.className = 'su-strength-seg' + (i <= score ? ' ' + cls[score] : '');
        }
        document.getElementById('suPwStrengthLabel').textContent = pw ? labels[score] : '';
        checkPwCriteria(pw);
        _checkPwMatch();
        _syncNextStep2Btn();
    });

    /* ── Password toggle ───────────────────────────────────────────── */
    /**
     * @param {string} inputId
     * @param {HTMLElement} btn
     */
    window.suTogglePw = function (inputId, btn) {
        var inp = document.getElementById(inputId);
        var icon = btn.querySelector('i');
        if (inp.type === 'password') {
            inp.type = 'text';
            icon.className = 'bx bx-show';
        } else {
            inp.type = 'password';
            icon.className = 'bx bx-hide';
        }
    };

    /* ── GSTIN auto-uppercase + uniqueness check ───────────────────── */
    document.getElementById('suGSTIN').addEventListener('input', function () {
        var pos = this.selectionStart;
        this.value = this.value.toUpperCase();
        this.setSelectionRange(pos, pos);
        _gstinTaken = false;
        document.getElementById('suGSTINOk').classList.remove('show');
        clearErr('suGSTIN');
        _syncNextBtn();
    });

    document.getElementById('suGSTIN').addEventListener('blur', function () {
        var gstin = this.value.trim().toUpperCase();
        if (!gstin || !/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/.test(gstin)) return;
        _gstinChecking = true;
        _syncNextBtn();
        fetch('/signup/checkGSTIN', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'gstin=' + encodeURIComponent(gstin),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.available) {
                _gstinTaken = false;
                document.getElementById('suGSTINOk').classList.add('show');
                clearErr('suGSTIN');
                setInputState('suGSTIN', true);
            } else {
                _gstinTaken = true;
                document.getElementById('suGSTINOk').classList.remove('show');
                showErr('suGSTIN', 'This GSTIN is already registered.');
                setInputState('suGSTIN', false);
            }
        })
        .catch(function () {})
        .finally(function () {
            _gstinChecking = false;
            _syncNextBtn();
        });
    });

    /* ── Plan loading ──────────────────────────────────────────────── */
    var _suPlansLoaded = false;
    var _suPlansData   = null; /* shared cache after first fetch */
    var _suGconfPlanUID = 0;   /* selected plan UID in Google overlay */

    /**
     * Loads plans from server, renders cards. Called once when entering Step 3.
     * @returns {void}
     */
    function _suLoadPlans() {
        if (_suPlansLoaded) return;

        document.getElementById('suPlansLoading').style.display = '';
        document.getElementById('suPlanCards').style.display    = 'none';
        document.getElementById('suPlansError').style.display   = 'none';

        if (_suPlansData) {
            /* already fetched by Google overlay loader — reuse cache */
            document.getElementById('suPlansLoading').style.display = 'none';
            _suRenderPlanCards(_suPlansData, document.getElementById('suPlanCards'), _suSelectPlan);
            document.getElementById('suPlanCards').style.display = '';
            _suPlansLoaded = true;
            return;
        }

        fetch('/signup/getPlans', { method: 'GET' })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            document.getElementById('suPlansLoading').style.display = 'none';
            if (data.Error || !data.Plans || data.Plans.length === 0) {
                document.getElementById('suPlansErrorText').textContent = data.Plans && data.Plans.length === 0
                    ? 'No plans are available at the moment. Please contact support.'
                    : 'Could not load plans. Please go back and try again.';
                document.getElementById('suPlansError').style.display = '';
                return;
            }
            _suPlansData = data.Plans;
            _suRenderPlanCards(data.Plans, document.getElementById('suPlanCards'), _suSelectPlan);
            document.getElementById('suPlanCards').style.display = '';
            _suPlansLoaded = true;
        })
        .catch(function () {
            document.getElementById('suPlansLoading').style.display = 'none';
            document.getElementById('suPlansErrorText').textContent = 'A network error occurred loading plans.';
            document.getElementById('suPlansError').style.display = '';
        });
    }

    /**
     * Loads plan cards into the Google confirmation overlay. Uses cache if available.
     * @returns {void}
     */
    function _suLoadGconfPlans() {
        _suGconfPlanUID = 0;
        document.getElementById('suGconfPlansLoading').style.display = '';
        document.getElementById('suGconfPlanCards').style.display    = 'none';
        document.getElementById('suGconfPlansError').style.display   = 'none';

        if (_suPlansData) {
            document.getElementById('suGconfPlansLoading').style.display = 'none';
            _suRenderPlanCards(_suPlansData, document.getElementById('suGconfPlanCards'), _suSelectGconfPlan);
            document.getElementById('suGconfPlanCards').style.display = '';
            return;
        }

        fetch('/signup/getPlans', { method: 'GET' })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            document.getElementById('suGconfPlansLoading').style.display = 'none';
            if (data.Error || !data.Plans || data.Plans.length === 0) {
                document.getElementById('suGconfPlansErrorText').textContent = 'No plans available. Please contact support.';
                document.getElementById('suGconfPlansError').style.display = '';
                return;
            }
            _suPlansData = data.Plans;
            _suRenderPlanCards(data.Plans, document.getElementById('suGconfPlanCards'), _suSelectGconfPlan);
            document.getElementById('suGconfPlanCards').style.display = '';
        })
        .catch(function () {
            document.getElementById('suGconfPlansLoading').style.display = 'none';
            document.getElementById('suGconfPlansErrorText').textContent = 'Could not load plans. Please try again.';
            document.getElementById('suGconfPlansError').style.display = '';
        });
    }

    /**
     * Selects a plan card inside the Google overlay.
     * @param {number|string} uid
     * @param {HTMLElement} cardEl
     * @returns {void}
     */
    function _suSelectGconfPlan(uid, cardEl) {
        document.querySelectorAll('#suGconfPlanCards .su-plan-card').forEach(function (c) { c.classList.remove('selected'); });
        cardEl.classList.add('selected');
        _suGconfPlanUID = parseInt(uid, 10);
    }

    /**
     * Renders plan cards into the given container element.
     * @param {Array<object>} plans
     * @param {HTMLElement} container
     * @param {function(number|string, HTMLElement): void} onSelectFn
     * @returns {void}
     */
    function _suRenderPlanCards(plans, container, onSelectFn) {
        container.innerHTML = '';
        var grid = document.createElement('div');
        grid.className = 'su-plan-grid';

        plans.forEach(function (plan) {
            var price  = parseFloat(plan.Price || 0);
            var isFree = (price <= 0);
            var cycle  = (plan.BillingCycle || '').toLowerCase();

            var card = document.createElement('div');
            card.className = 'su-plan-card';
            card.setAttribute('data-plan-uid', plan.SectorPlanUID);
            card.setAttribute('data-cycle', cycle);
            card.setAttribute('tabindex', '0');

            var priceHtml = isFree
                ? '<span class="sp-free">Free</span>'
                : '<span class="sp-cur">₹</span><span class="sp-amt">' + Math.round(price).toLocaleString('en-IN') + '</span>';

            var metaHtml = '';
            if (plan.MaxUsers && parseInt(plan.MaxUsers) > 0) {
                metaHtml += '<span><i class="bx bx-user"></i> Up to ' + plan.MaxUsers + ' users</span>';
            }
            if (plan.MaxBranches && parseInt(plan.MaxBranches) > 0) {
                metaHtml += '<span><i class="bx bx-building"></i> Up to ' + plan.MaxBranches + ' branches</span>';
            }
            if (plan.DurationDays && parseInt(plan.DurationDays) > 0) {
                var days = parseInt(plan.DurationDays);
                var durationLabel = days >= 365 ? Math.round(days / 365) + ' yr' : days + ' days';
                metaHtml += '<span><i class="bx bx-time"></i> ' + durationLabel + '</span>';
            }

            card.innerHTML =
                '<div class="su-plan-check"><i class="bx bx-check"></i></div>' +
                '<div class="su-plan-card-name">' + _suEscape(plan.PlanName || 'Plan') +
                    (isFree ? '<span class="su-plan-free-badge">Free</span>' : '') +
                '</div>' +
                '<div class="su-plan-card-cycle">' + _suEscape(plan.BillingCycle || '') + '</div>' +
                '<div class="su-plan-card-price">' + priceHtml + '</div>' +
                (metaHtml ? '<div class="su-plan-card-meta">' + metaHtml + '</div>' : '');

            card.addEventListener('click', function () { onSelectFn(plan.SectorPlanUID, card); });
            card.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); onSelectFn(plan.SectorPlanUID, card); }
            });

            grid.appendChild(card);
        });

        container.appendChild(grid);
    }

    /**
     * Marks a plan card as selected and stores the UID.
     * @param {number|string} uid
     * @param {HTMLElement} cardEl
     * @returns {void}
     */
    function _suSelectPlan(uid, cardEl) {
        document.querySelectorAll('.su-plan-card').forEach(function (c) { c.classList.remove('selected'); });
        cardEl.classList.add('selected');
        document.getElementById('suSectorPlanUID').value = uid;
        _syncSubmitBtn();
    }

    /**
     * @param {string} str
     * @returns {string}
     */
    function _suEscape(str) {
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    /* ── Submit ────────────────────────────────────────────────────── */
    /**
     * @param {string} orgName
     * @returns {void}
     */
    function suShowSuccess(orgName) {
        var overlay = document.getElementById('suProcessingOverlay');
        document.getElementById('suOverlayOrgName').textContent = orgName || '';
        overlay.classList.add('success');
        /* overlay stays visible — redirect fires from the caller */
    }

    /**
     * @returns {void}
     */
    function suShowProcessing() {
        document.getElementById('suProcessingOverlay').classList.add('show');
        document.getElementById('suSubmitBtn').disabled = true;
        var backBtn = document.querySelector('#suFormStep3 .su-btn-ghost');
        if (backBtn) backBtn.disabled = true;
        var loginLink = document.getElementById('suLoginLink');
        if (loginLink) loginLink.style.pointerEvents = 'none';
    }

    /**
     * @returns {void}
     */
    function suHideProcessing() {
        document.getElementById('suProcessingOverlay').classList.remove('show');
        document.getElementById('suSubmitBtn').disabled = false;
        var backBtn = document.querySelector('#suFormStep3 .su-btn-ghost');
        if (backBtn) backBtn.disabled = false;
        var loginLink = document.getElementById('suLoginLink');
        if (loginLink) loginLink.style.pointerEvents = '';
    }

    window.suSubmit = function () {
        hideAlert();

        var planUID = parseInt(document.getElementById('suSectorPlanUID').value || '0', 10);
        if (planUID <= 0) {
            showAlert('Please select a plan to continue.');
            return;
        }

        suShowProcessing();

        var stateParts = document.getElementById('suState').value.split('|');
        var body = new URLSearchParams({
            OrgName:         document.getElementById('suOrgName').value.trim(),
            ShortCode:       document.getElementById('suShortCode').value.trim().toUpperCase(),
            CountryCode:     document.getElementById('suOrgCountryCode').value,
            OrgMobile:       document.getElementById('suOrgMobile').value.replace(/\D/g, ''),
            OrgEmail:        document.getElementById('suOrgEmail').value.trim(),
            StateCode:       stateParts[0] || '',
            StateName:       stateParts[1] || '',
            TimezoneUID:     document.getElementById('suTimezone').value,
            GSTIN:           document.getElementById('suGSTIN').value.trim(),
            AdminFirstName:  document.getElementById('suFirstName').value.trim(),
            AdminLastName:   document.getElementById('suLastName').value.trim(),
            AdminUsername:   document.getElementById('suUsername').value.trim().toLowerCase(),
            AdminPassword:   document.getElementById('suPassword').value,
            ConfirmPassword: document.getElementById('suConfirmPassword').value,
            SectorPlanUID:   planUID,
        });

        fetch('/signup/doSignup', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString(),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.Error) {
                suHideProcessing();
                showAlert(data.Message);
            } else if (data.Redirect) {
                suShowSuccess(document.getElementById('suOrgName').value.trim());
                window.location.href = data.Redirect;
            } else {
                /* Fallback: show email verification screen */
                suHideProcessing();
                document.getElementById('suStepIndicator').style.display = 'none';
                document.getElementById('suFormStep3').style.display = 'none';
                document.getElementById('suLoginLink').style.display = 'none';
                document.getElementById('suSuccessEmail').textContent =
                    document.getElementById('suOrgEmail').value.trim().toLowerCase();
                document.getElementById('suSuccess').classList.add('show');
            }
        })
        .catch(function () {
            suHideProcessing();
            showAlert('A network error occurred. Please try again.');
        });
    };

    /* ── Google One Tap ───────────────────────────────────────────── */
    <?php if (!empty(getenv('GOOGLE_CLIENT_ID'))): ?>
    var _suGoogleClientId  = <?php echo json_encode(getenv('GOOGLE_CLIENT_ID')); ?>;
    var _suPendingCred     = null;

    /**
     * Decodes the JWT payload (no signature verification — server does that).
     * @param {string} token
     * @returns {object}
     */
    function _decodeJwt(token) {
        try {
            var payload = token.split('.')[1];
            var base64  = payload.replace(/-/g, '+').replace(/_/g, '/');
            var padded  = base64 + '='.repeat((4 - base64.length % 4) % 4);
            return JSON.parse(atob(padded));
        } catch (e) {
            return {};
        }
    }

    /**
     * Called by Google Identity Services. Shows confirmation card instead of posting directly.
     * @param {{credential: string}} response
     */
    function _onGoogleCredential(response) {
        if (!response || !response.credential) return;
        _suPendingCred = response.credential;

        var payload   = _decodeJwt(response.credential);
        var name      = payload.name  || payload.email || 'You';
        var email     = payload.email || '';
        var picture   = payload.picture || '';
        var firstName = (payload.given_name || name).split(' ')[0];

        document.getElementById('suGconfName').textContent      = name;
        document.getElementById('suGconfEmail').textContent     = email;
        document.getElementById('suGconfFirstName').textContent = firstName;

        var avatarImg = document.getElementById('suGconfAvatar');
        var avatarPh  = document.getElementById('suGconfAvatarPlaceholder');
        if (picture) {
            avatarImg.src             = picture;
            avatarImg.style.display   = 'block';
            avatarPh.style.display    = 'none';
        } else {
            avatarPh.textContent      = firstName.charAt(0).toUpperCase();
            avatarImg.style.display   = 'none';
            avatarPh.style.display    = 'flex';
        }

        document.getElementById('suGconfOverlay').classList.add('show');
        _suLoadGconfPlans();
    }

    /**
     * User confirmed — now POST the stored credential to the server.
     * @returns {void}
     */
    window.suConfirmGoogle = function () {
        if (!_suPendingCred) return;

        if (_suGconfPlanUID <= 0) {
            document.getElementById('suGconfPlansErrorText').textContent = 'Please select a plan to continue.';
            document.getElementById('suGconfPlansError').style.display = '';
            return;
        }

        document.getElementById('suGconfOverlay').classList.remove('show');

        var overlay = document.getElementById('suProcessingOverlay');
        if (overlay) overlay.classList.add('show');

        var fd = new FormData();
        fd.append('credential', _suPendingCred);
        fd.append('SectorPlanUID', _suGconfPlanUID);
        fd.append('CountryCode', document.getElementById('suOrgCountryCode').value);
        _suPendingCred = null;

        fetch('<?php echo base_url('signup/google-auth'); ?>', {
            method: 'POST',
            body:   fd,
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.Error) {
                if (overlay) overlay.classList.remove('show');
                showAlert(data.Message || 'Google sign-in failed. Please try again.');
            } else {
                suShowSuccess(document.getElementById('suGconfName').textContent || '');
                window.location.href = data.Redirect || '<?php echo base_url('dashboard'); ?>';
            }
        })
        .catch(function () {
            if (overlay) overlay.classList.remove('show');
            showAlert('An error occurred. Please try again.');
        });
    };

    /**
     * User wants a different account — dismiss card, re-prompt Google.
     * @returns {void}
     */
    window.suCancelGoogle = function () {
        _suPendingCred = null;
        document.getElementById('suGconfOverlay').classList.remove('show');
        if (window.google && window.google.accounts) {
            google.accounts.id.cancel();
            google.accounts.id.prompt();
        }
    };

    function _initGoogleOneTap() {
        if (!window.google || !window.google.accounts) return;
        google.accounts.id.initialize({
            client_id:             _suGoogleClientId,
            callback:              _onGoogleCredential,
            auto_select:           true,
            cancel_on_tap_outside: true,
        });
        google.accounts.id.prompt();
    }

    window.suTriggerGoogle = function () {
        window.location.href = '<?php echo base_url('auth/google'); ?>';
    };

    /* Wait for the async Google script, then initialise */
    window._suGoogleReady = _initGoogleOneTap;
    <?php endif; ?>

}());

/* Preload the overlay logo so it's in cache before the overlay opens */
(function() {
    var _img = new Image();
    _img.src = 'https://pub-bb40942a33344637936ade1f3800ff8b.r2.dev/Global/favicon_io/android-chrome-512x512-1.png';
}());
</script>

<!-- Google Identity Services (One Tap) -->
<?php if (!empty(getenv('GOOGLE_CLIENT_ID'))): ?>
<script src="https://accounts.google.com/gsi/client" async defer onload="window._suGoogleReady && window._suGoogleReady()"></script>
<?php endif; ?>

</body>
</html>
