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

/* ── LEFT BRAND PANEL ──────────────────────────────────────── */
.lr-brand {
    position: relative;
    width: 55%;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    background: #040b18;
}

.lr-brand::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse 70% 60% at 30% 40%, rgba(245,158,11,0.14) 0%, transparent 70%),
        radial-gradient(ellipse 50% 50% at 75% 70%, rgba(16,185,129,0.07) 0%, transparent 60%);
    z-index: 0;
}

/* Animated geometric shapes */
.lr-shapes { position: absolute; inset: 0; z-index: 0; pointer-events: none; }

.lr-shape {
    position: absolute;
    border-radius: 50%;
    border: 1px solid rgba(245,158,11,0.18);
    animation: drift linear infinite;
}

.lr-shape:nth-child(1) { width:320px;height:320px; top:-60px; left:-80px; animation-duration:22s; border-color:rgba(245,158,11,0.12); }
.lr-shape:nth-child(2) { width:180px;height:180px; top:40%; right:-40px; animation-duration:18s; animation-delay:-7s; border-color:rgba(16,185,129,0.14); }
.lr-shape:nth-child(3) { width:240px;height:240px; bottom:-60px; left:20%; animation-duration:26s; animation-delay:-12s; border-color:rgba(245,158,11,0.08); }
.lr-shape:nth-child(4) { width:100px;height:100px; top:20%; right:20%; animation-duration:15s; animation-delay:-3s; border-radius:18px; rotate:45deg; border-color:rgba(245,158,11,0.22); }
.lr-shape:nth-child(5) { width:60px;height:60px; bottom:25%; left:12%; animation-duration:20s; animation-delay:-9s; border-radius:12px; rotate:30deg; border-color:rgba(16,185,129,0.18); }

@keyframes drift {
    0%   { transform: translateY(0px) rotate(0deg); }
    33%  { transform: translateY(-18px) rotate(4deg); }
    66%  { transform: translateY(10px) rotate(-3deg); }
    100% { transform: translateY(0px) rotate(0deg); }
}

/* Gold diagonal accent bar */
.lr-brand::after {
    content: '';
    position: absolute;
    top: 0; right: 0;
    width: 3px;
    height: 100%;
    background: linear-gradient(to bottom, transparent 0%, rgba(245,158,11,0.6) 35%, rgba(245,158,11,0.6) 65%, transparent 100%);
    z-index: 1;
}

/* Floating gold dots */
.lr-dots { position: absolute; inset: 0; z-index: 0; pointer-events: none; }
.lr-dot {
    position: absolute;
    width: 4px; height: 4px;
    border-radius: 50%;
    background: rgba(245,158,11,0.5);
    animation: floatDot ease-in-out infinite;
}
.lr-dot:nth-child(1)  { top:15%; left:25%; animation-duration:6s; animation-delay:0s; }
.lr-dot:nth-child(2)  { top:30%; left:60%; animation-duration:8s; animation-delay:-2s; }
.lr-dot:nth-child(3)  { top:55%; left:18%; animation-duration:7s; animation-delay:-4s; }
.lr-dot:nth-child(4)  { top:70%; left:75%; animation-duration:9s; animation-delay:-1s; }
.lr-dot:nth-child(5)  { top:82%; left:40%; animation-duration:6.5s; animation-delay:-3s; }
.lr-dot:nth-child(6)  { top:22%; left:82%; animation-duration:7.5s; animation-delay:-5s; }
.lr-dot:nth-child(7)  { top:45%; left:45%; animation-duration:8.5s; animation-delay:-1.5s; background:rgba(16,185,129,0.4); }
.lr-dot:nth-child(8)  { top:10%; left:48%; animation-duration:5.5s; animation-delay:-6s; }

@keyframes floatDot {
    0%,100% { transform: translateY(0); opacity:0.5; }
    50% { transform: translateY(-14px); opacity:1; }
}

.lr-brand-content {
    position: relative;
    z-index: 2;
    padding: 60px 56px;
    max-width: 520px;
    animation: fadeInLeft 0.9s ease-out both;
}

@keyframes fadeInLeft {
    from { opacity:0; transform:translateX(-30px); }
    to   { opacity:1; transform:translateX(0); }
}

.lr-brand-logo {
    margin-bottom: 36px;
}

.lr-brand-logo img {
    width: 68px; height: 68px;
    border-radius: 18px;
    box-shadow: 0 0 0 1px rgba(245,158,11,0.3), 0 8px 32px rgba(245,158,11,0.2);
}

.lr-brand-name {
    font-size: 42px;
    font-weight: 800;
    line-height: 1.15;
    letter-spacing: -0.5px;
    margin-bottom: 14px;
}

.lr-brand-name .gold { color: #f59e0b; display: block; }
.lr-brand-name .white { color: #f1f5f9; display: block; }

.lr-brand-sub {
    font-size: 14px;
    font-weight: 500;
    color: #64748b;
    letter-spacing: 2px;
    text-transform: uppercase;
    margin-bottom: 48px;
}

.lr-features {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.lr-feat {
    display: flex;
    align-items: center;
    gap: 14px;
    opacity: 0;
    animation: featIn 0.5s ease-out forwards;
}

.lr-feat:nth-child(1) { animation-delay: 0.5s; }
.lr-feat:nth-child(2) { animation-delay: 0.7s; }
.lr-feat:nth-child(3) { animation-delay: 0.9s; }

@keyframes featIn {
    from { opacity:0; transform:translateX(-16px); }
    to   { opacity:1; transform:translateX(0); }
}

.lr-feat-icon {
    width: 42px; height: 42px;
    border-radius: 12px;
    background: rgba(245,158,11,0.1);
    border: 1px solid rgba(245,158,11,0.2);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    color: #f59e0b;
    font-size: 20px;
    transition: all 0.3s;
}

.lr-feat:hover .lr-feat-icon {
    background: rgba(245,158,11,0.18);
    box-shadow: 0 0 20px rgba(245,158,11,0.15);
}

.lr-feat-text strong {
    display: block;
    color: #e2e8f0;
    font-size: 14px;
    font-weight: 600;
}

.lr-feat-text span {
    color: #64748b;
    font-size: 12px;
}

.lr-brand-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-top: 52px;
    padding: 10px 16px;
    border-radius: 50px;
    background: rgba(245,158,11,0.08);
    border: 1px solid rgba(245,158,11,0.2);
    color: #f59e0b;
    font-size: 12px;
    font-weight: 500;
    letter-spacing: 0.5px;
    opacity: 0;
    animation: featIn 0.5s ease-out 1.1s forwards;
}

.lr-brand-badge i { font-size: 15px; }

/* ── RIGHT FORM PANEL ──────────────────────────────────────── */
.lr-form-panel {
    width: 45%;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 32px;
    background: #060e20;
    position: relative;
    overflow-y: auto;
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
    animation: fadeInRight 0.8s ease-out 0.2s both;
}

@keyframes fadeInRight {
    from { opacity:0; transform:translateY(24px); }
    to   { opacity:1; transform:translateY(0); }
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
    margin-bottom: 8px;
    letter-spacing: -0.3px;
}

.lr-form-head p {
    color: #64748b;
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
    padding: 13px 14px 13px 44px;
    color: #f1f5f9;
    font-size: 14px;
    font-family: inherit;
    outline: none;
    transition: all 0.25s;
    -webkit-appearance: none;
    appearance: none;
}

.lr-input-wrap input:focus {
    background: rgba(245,158,11,0.05);
    border-color: rgba(245,158,11,0.5);
    box-shadow: 0 0 0 3px rgba(245,158,11,0.08);
}

.lr-input-wrap input:focus + .lr-input-icon,
.lr-input-wrap:focus-within .lr-input-icon {
    color: #f59e0b;
}

.lr-input-wrap input::placeholder { color: #334155; }

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
    box-shadow: 0 4px 20px rgba(245,158,11,0.3);
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
.lr-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(245,158,11,0.4); }
.lr-btn:active { transform: translateY(0); box-shadow: 0 4px 16px rgba(245,158,11,0.3); }

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
    color: #334155;
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

    <!-- ── LEFT: Brand Panel ── -->
    <div class="lr-brand">
        <div class="lr-shapes">
            <div class="lr-shape"></div>
            <div class="lr-shape"></div>
            <div class="lr-shape"></div>
            <div class="lr-shape"></div>
            <div class="lr-shape"></div>
        </div>
        <div class="lr-dots">
            <div class="lr-dot"></div><div class="lr-dot"></div><div class="lr-dot"></div>
            <div class="lr-dot"></div><div class="lr-dot"></div><div class="lr-dot"></div>
            <div class="lr-dot"></div><div class="lr-dot"></div>
        </div>

        <div class="lr-brand-content">
            <div class="lr-brand-logo">
                <?php if (!empty($OrgLogo)): ?>
                <img src="<?php echo htmlspecialchars($OrgLogo); ?>" alt="<?php echo getSiteConfiguration()->ShortName; ?>">
                <?php endif; ?>
            </div>

            <div class="lr-brand-name">
                <span class="gold">RISHIKA 2K</span>
                <span class="white">ENTERPRISES</span>
            </div>

            <p class="lr-brand-sub">Agricultural Machinery · Tamil Nadu</p>

            <div class="lr-features">
                <div class="lr-feat">
                    <div class="lr-feat-icon"><i class="bx bx-shield-quarter"></i></div>
                    <div class="lr-feat-text">
                        <strong>Secure Billing</strong>
                        <span>End-to-end encrypted transactions</span>
                    </div>
                </div>
                <div class="lr-feat">
                    <div class="lr-feat-icon"><i class="bx bx-bolt-circle"></i></div>
                    <div class="lr-feat-text">
                        <strong>Instant Invoicing</strong>
                        <span>Generate &amp; share invoices in seconds</span>
                    </div>
                </div>
                <div class="lr-feat">
                    <div class="lr-feat-icon"><i class="bx bx-bar-chart-alt-2"></i></div>
                    <div class="lr-feat-text">
                        <strong>Smart Reports</strong>
                        <span>Real-time sales &amp; inventory insights</span>
                    </div>
                </div>
            </div>

            <div class="lr-brand-badge">
                <i class="bx bx-certification"></i>
                Authorized Dealer — Rotoking &amp; Bharat Baler
            </div>
        </div>
    </div>

    <!-- ── RIGHT: Form Panel ── -->
    <div class="lr-form-panel">
        <div class="lr-form-card">

            <!-- Mobile-only logo -->
            <div class="lr-mobile-logo">
                <?php if (!empty($OrgLogo)): ?>
                <img src="<?php echo htmlspecialchars($OrgLogo); ?>" alt="logo">
                <?php endif; ?>
                <h2><span>RISHIKA 2K</span> ENTERPRISES</h2>
            </div>

            <div class="lr-form-head">
                <h3>Welcome back</h3>
                <p>Sign in to manage your billing operations</p>
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
                <a href="/auth/facebook" class="lr-social-btn lr-social-facebook">
                    <svg class="lr-social-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path fill="#1877F2" d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047V9.41c0-3.025 1.792-4.697 4.533-4.697 1.312 0 2.686.236 2.686.236v2.97h-1.513c-1.491 0-1.956.93-1.956 1.886v2.267h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z"/>
                    </svg>
                    Continue with Facebook
                </a>
            </div>

            <p class="lr-footer-note">&copy; <?php echo date('Y'); ?> <span><?php echo getSiteConfiguration()->ShortName; ?></span>. All rights reserved.</p>
        </div>
    </div>

</div>

<?php $this->load->view('login/footer'); ?>

<script>
(function () {
    // Hide template customizer
    document.addEventListener('DOMContentLoaded', function () {
        var el = document.getElementById('template-customizer');
        if (el) el.classList.add('d-none');
    });

    // Password toggle
    var pwToggle = document.getElementById('pwToggle');
    var pwInput  = document.getElementById('UserPassword');
    var pwIcon   = document.getElementById('pwIcon');

    if (pwToggle) {
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

    // Ripple on submit button
    var lrBtn = document.getElementById('lrSubmit');
    if (lrBtn) {
        lrBtn.addEventListener('click', function (e) {
            var r = document.createElement('span');
            var d = Math.max(lrBtn.clientWidth, lrBtn.clientHeight);
            var rect = lrBtn.getBoundingClientRect();
            r.className = 'lr-ripple';
            r.style.cssText = 'width:' + d + 'px;height:' + d + 'px;left:' + (e.clientX - rect.left - d/2) + 'px;top:' + (e.clientY - rect.top - d/2) + 'px';
            lrBtn.appendChild(r);
            setTimeout(function () { r.remove(); }, 700);
        });
    }

    // ── Prevent double-submit: lock the entire form on first submit ───────────
    var loginForm = document.getElementById('doLoginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function () {
            // 1. Disable & show spinner on Sign In button
            if (lrBtn) {
                lrBtn.disabled = true;
                lrBtn.innerHTML =
                    '<span style="display:flex;align-items:center;justify-content:center;gap:10px;">' +
                        '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="animation:spin 0.8s linear infinite;flex-shrink:0;">' +
                            '<path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>' +
                        '</svg>' +
                        'Signing in...' +
                    '</span>';
                lrBtn.style.opacity = '0.75';
                lrBtn.style.cursor  = 'not-allowed';
            }

            // 2. Disable social login buttons so they can't be clicked mid-request
            document.querySelectorAll('.lr-social-btn').forEach(function (btn) {
                btn.style.pointerEvents = 'none';
                btn.style.opacity       = '0.4';
            });
        });
    }

    // Re-enable everything if user presses browser Back button (bfcache restore)
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) {
            if (lrBtn) {
                lrBtn.disabled = false;
                lrBtn.style.opacity = '';
                lrBtn.style.cursor  = '';
                lrBtn.innerHTML =
                    '<span><i class="bx bx-log-in-circle" style="font-size:18px"></i> Sign In</span>';
            }
            document.querySelectorAll('.lr-social-btn').forEach(function (btn) {
                btn.style.pointerEvents = '';
                btn.style.opacity       = '';
            });
        }
    });

    // Auto-focus username
    var un = document.getElementById('UserName');
    if (un) setTimeout(function () { un.focus(); }, 400);
})();
</script>
