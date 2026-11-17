<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $this->load->view('login/header'); ?>

<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body { overflow: hidden; }

.lr-root {
    display: flex;
    height: 100vh;
    background: #040b18;
    font-family: 'Public Sans', sans-serif;
    overflow: hidden;
}

/* ── LEFT BRAND PANEL (same as login) ─────────────────────── */
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

.lr-brand::after {
    content: '';
    position: absolute;
    top: 0; right: 0;
    width: 3px; height: 100%;
    background: linear-gradient(to bottom, transparent 0%, rgba(245,158,11,0.6) 35%, rgba(245,158,11,0.6) 65%, transparent 100%);
    z-index: 1;
}

.lr-dots { position: absolute; inset: 0; z-index: 0; pointer-events: none; }
.lr-dot {
    position: absolute;
    width: 4px; height: 4px;
    border-radius: 50%;
    background: rgba(245,158,11,0.5);
    animation: floatDot ease-in-out infinite;
}
.lr-dot:nth-child(1)  { top:15%; left:25%; animation-duration:6s; }
.lr-dot:nth-child(2)  { top:30%; left:60%; animation-duration:8s; animation-delay:-2s; }
.lr-dot:nth-child(3)  { top:55%; left:18%; animation-duration:7s; animation-delay:-4s; }
.lr-dot:nth-child(4)  { top:70%; left:75%; animation-duration:9s; animation-delay:-1s; }
.lr-dot:nth-child(5)  { top:82%; left:40%; animation-duration:6.5s; animation-delay:-3s; }
.lr-dot:nth-child(6)  { top:22%; left:82%; animation-duration:7.5s; animation-delay:-5s; }
.lr-dot:nth-child(7)  { top:45%; left:45%; animation-duration:8.5s; animation-delay:-1.5s; background:rgba(16,185,129,0.4); }
.lr-dot:nth-child(8)  { top:10%; left:48%; animation-duration:5.5s; animation-delay:-6s; }

@keyframes floatDot {
    0%,100% { transform: translateY(0); opacity:0.5; }
    50%     { transform: translateY(-14px); opacity:1; }
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

.lr-brand-logo { margin-bottom: 36px; }
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
.lr-brand-name .gold  { color: #f59e0b; display: block; }
.lr-brand-name .white { color: #f1f5f9; display: block; }

.lr-brand-sub {
    font-size: 14px; font-weight: 500; color: #64748b;
    letter-spacing: 2px; text-transform: uppercase; margin-bottom: 48px;
}

/* Lock icon illustration */
.lr-illus {
    width: 96px; height: 96px;
    border-radius: 24px;
    background: rgba(245,158,11,0.08);
    border: 1px solid rgba(245,158,11,0.2);
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 28px;
    animation: pulseIllus 3s ease-in-out infinite;
}

.lr-illus i { font-size: 46px; color: #f59e0b; }

@keyframes pulseIllus {
    0%,100% { box-shadow: 0 0 0 0 rgba(245,158,11,0.15); }
    50%     { box-shadow: 0 0 0 16px rgba(245,158,11,0); }
}

.lr-illus-text h4 {
    font-size: 22px; font-weight: 700; color: #f1f5f9; margin-bottom: 8px;
}
.lr-illus-text p {
    font-size: 14px; color: #64748b; line-height: 1.65; max-width: 320px;
}

/* ── RIGHT FORM PANEL ──────────────────────────────────────── */
.lr-form-panel {
    width: 45%;
    display: flex; align-items: center; justify-content: center;
    padding: 40px 32px;
    background: #060e20;
    position: relative;
    overflow-y: auto;
}

.lr-form-panel::before {
    content: '';
    position: absolute; inset: 0;
    background: radial-gradient(ellipse 80% 60% at 50% 0%, rgba(245,158,11,0.06) 0%, transparent 60%);
    pointer-events: none;
}

.lr-form-card {
    width: 100%; max-width: 400px;
    position: relative; z-index: 1;
    animation: fadeInRight 0.8s ease-out 0.2s both;
}

@keyframes fadeInRight {
    from { opacity:0; transform:translateY(24px); }
    to   { opacity:1; transform:translateY(0); }
}

/* Mobile logo */
.lr-mobile-logo {
    display: none; text-align: center; margin-bottom: 32px;
}
.lr-mobile-logo img {
    width: 56px; height: 56px; border-radius: 14px;
    box-shadow: 0 0 0 1px rgba(245,158,11,0.3); margin-bottom: 12px;
}
.lr-mobile-logo h2 { font-size: 22px; font-weight: 800; color: #f1f5f9; }
.lr-mobile-logo h2 span { color: #f59e0b; }

/* Back link */
.lr-back {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 13px; color: #475569;
    text-decoration: none; margin-bottom: 28px;
    transition: color 0.2s;
}
.lr-back:hover { color: #f59e0b; }
.lr-back i { font-size: 16px; }

.lr-form-head { margin-bottom: 32px; }
.lr-form-head h3 {
    font-size: 26px; font-weight: 700; color: #f1f5f9;
    margin-bottom: 8px; letter-spacing: -0.3px;
}
.lr-form-head p { color: #64748b; font-size: 14px; line-height: 1.6; }

/* Alert */
.lr-alerts { margin-bottom: 20px; }
.lr-alerts .alert {
    background: rgba(239,68,68,0.1);
    border: 1px solid rgba(239,68,68,0.25);
    border-radius: 10px; color: #fca5a5;
    padding: 11px 14px; font-size: 13px;
}
.lr-alerts .alert-success {
    background: rgba(16,185,129,0.1);
    border-color: rgba(16,185,129,0.25);
    color: #6ee7b7;
}
.lr-alerts .alert-dismissible .btn-close {
    filter: invert(1); opacity: 0.5;
}

/* Token error banner (rendered inline, not via flash) */
.lr-token-err {
    background: rgba(245,158,11,0.1);
    border: 1px solid rgba(245,158,11,0.3);
    border-radius: 10px; color: #fcd34d;
    padding: 12px 16px; font-size: 13px;
    margin-bottom: 20px;
    display: flex; align-items: flex-start; gap: 10px;
}
.lr-token-err i { font-size: 18px; flex-shrink: 0; margin-top: 1px; }

/* Field */
.lr-field { margin-bottom: 22px; }
.lr-field label {
    display: block; font-size: 13px; font-weight: 600;
    color: #94a3b8; margin-bottom: 8px;
    letter-spacing: 0.3px; text-transform: uppercase;
}
.lr-input-wrap { position: relative; }
.lr-input-wrap .lr-input-icon {
    position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
    color: #475569; font-size: 18px; pointer-events: none; transition: color 0.25s;
}
.lr-input-wrap input {
    width: 100%;
    background: rgba(255,255,255,0.04);
    border: 1.5px solid rgba(255,255,255,0.08);
    border-radius: 12px;
    padding: 13px 14px 13px 44px;
    color: #f1f5f9; font-size: 14px; font-family: inherit;
    outline: none; transition: all 0.25s;
    -webkit-appearance: none;
    appearance: none;
}
.lr-input-wrap input:focus {
    background: rgba(245,158,11,0.05);
    border-color: rgba(245,158,11,0.5);
    box-shadow: 0 0 0 3px rgba(245,158,11,0.08);
}
.lr-input-wrap:focus-within .lr-input-icon { color: #f59e0b; }
.lr-input-wrap input::placeholder { color: #334155; }

/* Button */
.lr-btn {
    width: 100%; padding: 14px; border: none; border-radius: 12px;
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: #040b18; font-size: 15px; font-weight: 700;
    letter-spacing: 0.3px; cursor: pointer; position: relative;
    overflow: hidden; transition: all 0.3s;
    box-shadow: 0 4px 20px rgba(245,158,11,0.3);
    font-family: inherit;
}
.lr-btn::before {
    content: ''; position: absolute; inset: 0;
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    opacity: 0; transition: opacity 0.3s;
}
.lr-btn:hover::before { opacity: 1; }
.lr-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(245,158,11,0.4); }
.lr-btn:active { transform: translateY(0); }
.lr-btn span { position: relative; z-index: 1; display: flex; align-items: center; justify-content: center; gap: 8px; }
.lr-btn .lr-ripple {
    position: absolute; border-radius: 50%;
    background: rgba(255,255,255,0.3);
    transform: scale(0); animation: ripple 0.6s linear; pointer-events: none;
}
@keyframes ripple { to { transform: scale(4); opacity: 0; } }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

.lr-signin-link {
    text-align: center; margin-top: 24px;
    font-size: 13px; color: #475569;
}
.lr-signin-link a { color: #f59e0b; text-decoration: none; font-weight: 600; transition: color 0.2s; }
.lr-signin-link a:hover { color: #fbbf24; }

/* Success panel shown after email is sent */
.lr-sent-panel {
    display: none;
    text-align: center;
    padding: 12px 0;
    animation: fadeInRight 0.5s ease-out both;
}
.lr-sent-icon {
    width: 72px; height: 72px; border-radius: 50%;
    background: rgba(16,185,129,0.1);
    border: 1.5px solid rgba(16,185,129,0.3);
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 20px;
}
.lr-sent-icon i { font-size: 36px; color: #34d399; }
.lr-sent-panel h4 { font-size: 20px; font-weight: 700; color: #f1f5f9; margin-bottom: 10px; }
.lr-sent-panel p { font-size: 13px; color: #64748b; line-height: 1.65; margin-bottom: 24px; }
.lr-sent-email { color: #f59e0b; font-weight: 600; word-break: break-all; }

/* Responsive */
@media (max-width: 900px) {
    .lr-brand { display: none; }
    .lr-form-panel { width: 100%; background: #040b18; padding: 40px 24px; }
    .lr-mobile-logo { display: block; }
    .lr-form-card { max-width: 440px; }
}
@media (max-width: 480px) {
    .lr-form-panel { padding: 32px 20px; }
    .lr-form-head h3 { font-size: 22px; }
}
</style>

<div class="lr-root">

    <!-- LEFT BRAND PANEL -->
    <div class="lr-brand">
        <div class="lr-shapes">
            <div class="lr-shape"></div><div class="lr-shape"></div><div class="lr-shape"></div>
            <div class="lr-shape"></div><div class="lr-shape"></div>
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
            <p class="lr-brand-sub">Billing Management System</p>

            <div class="lr-illus">
                <i class="bx bx-lock-open-alt"></i>
            </div>
            <div class="lr-illus-text">
                <h4>Account Recovery</h4>
                <p>Enter the email address linked to your account and we'll send you a secure, one-time reset link that expires in 15 minutes.</p>
            </div>
        </div>
    </div>

    <!-- RIGHT FORM PANEL -->
    <div class="lr-form-panel">
        <div class="lr-form-card">

            <div class="lr-mobile-logo">
                <?php if (!empty($OrgLogo)): ?>
                <img src="<?php echo htmlspecialchars($OrgLogo); ?>" alt="logo">
                <?php endif; ?>
                <h2><span>RISHIKA 2K</span> ENTERPRISES</h2>
            </div>

            <a href="<?= site_url('login') ?>" class="lr-back">
                <i class="bx bx-arrow-back"></i> Back to Sign In
            </a>

            <div class="lr-form-head">
                <h3>Forgot Password?</h3>
                <p>No worries. Enter your registered email and we'll send a reset link straight to your inbox.</p>
            </div>

            <!-- Token error (expired / invalid link used to reach this page) -->
            <?php $tokenErr = $this->session->flashdata('token_error'); ?>
            <?php if ($tokenErr === 'expired'): ?>
            <div class="lr-token-err">
                <i class="bx bx-time-five"></i>
                <div>
                    <strong>Reset link expired.</strong><br>
                    Your password reset link has expired (15-minute window). Please request a new one below.
                </div>
            </div>
            <?php elseif ($tokenErr === 'invalid'): ?>
            <div class="lr-token-err">
                <i class="bx bx-error-circle"></i>
                <div>
                    <strong>Invalid reset link.</strong><br>
                    This link is not recognised. It may have already been used. Please request a new one below.
                </div>
            </div>
            <?php endif; ?>

            <div class="lr-alerts">
                <?php $this->load->view('login/alerts'); ?>
            </div>

            <?php echo form_open('forgot-password/send', ['id' => 'forgotForm', 'autocomplete' => 'off']); ?>

            <div class="lr-field">
                <label for="EmailAddress">Registered Email Address</label>
                <div class="lr-input-wrap">
                    <input type="email" id="EmailAddress" name="EmailAddress"
                           placeholder="you@example.com" autocomplete="off" required
                           value="<?php echo set_value('EmailAddress'); ?>" />
                    <i class="bx bx-envelope lr-input-icon"></i>
                </div>
            </div>

            <button type="submit" class="lr-btn" id="fpBtn">
                <span>
                    <i class="bx bx-send" style="font-size:18px"></i>
                    Send Reset Link
                </span>
            </button>

            <?php echo form_close(); ?>

            <p class="lr-signin-link" id="fpSigninLink">
                Remembered your password? <a href="<?= site_url('login') ?>">Sign in here</a>
            </p>

            <!-- Shown after a successful send -->
            <div class="lr-sent-panel" id="fpSentPanel">
                <div class="lr-sent-icon"><i class="bx bx-mail-send"></i></div>
                <h4>Check your inbox</h4>
                <p>We sent a reset link to <span class="lr-sent-email" id="fpSentEmail"></span>.<br>It expires in 15 minutes.</p>
                <p class="lr-signin-link"><a href="<?= site_url('login') ?>">Back to Sign In</a></p>
            </div>

        </div>
    </div>

</div>

<?php $this->load->view('login/footer'); ?>

<script>
(function ($) {
    var _servicesUrl = '<?php echo rtrim(getenv('SERVICES_BASE_URL') ?: '', '/') . '/'; ?>';
    var _appBaseUrl  = '<?php echo rtrim(base_url(), '/'); ?>';
    var _spinnerHtml =
        '<span style="display:flex;align-items:center;justify-content:center;gap:10px;">' +
            '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="animation:spin 0.8s linear infinite;flex-shrink:0;">' +
                '<path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>' +
            '</svg>' +
            'Sending…' +
        '</span>';

    $(function () {
        $('#template-customizer').addClass('d-none');

        var $form      = $('#forgotForm');
        var $btn       = $('#fpBtn');
        var $email     = $('#EmailAddress');
        var $link      = $('#fpSigninLink a');
        var $alertWrap = $('.lr-alerts');
        var $sentPanel = $('#fpSentPanel');
        var $sentEmail = $('#fpSentEmail');

        /* Ripple effect
         * @param {jQuery.Event} e
         * @returns {void}
         */
        $btn.on('click', function (e) {
            var d    = Math.max($btn[0].clientWidth, $btn[0].clientHeight);
            var rect = $btn[0].getBoundingClientRect();
            var $r   = $('<span>').addClass('lr-ripple').css({
                width : d + 'px',
                height: d + 'px',
                left  : (e.clientX - rect.left - d / 2) + 'px',
                top   : (e.clientY - rect.top  - d / 2) + 'px'
            });
            $btn.append($r);
            setTimeout(function () { $r.remove(); }, 700);
        });

        /**
         * Lock form UI while the AJAX call is in-flight.
         * @returns {void}
         */
        function lockForm() {
            $btn.prop('disabled', true).html(_spinnerHtml).css({ opacity: '0.75', cursor: 'not-allowed' });
            $email.prop('readonly', true).css('opacity', '0.6');
            $link.css({ 'pointer-events': 'none', opacity: '0.4' });
        }

        /**
         * Restore form UI after a failed AJAX call.
         * @returns {void}
         */
        function unlockForm() {
            $btn.prop('disabled', false)
                .html('<span><i class="bx bx-send" style="font-size:18px"></i> Send Reset Link</span>')
                .css({ opacity: '', cursor: '' });
            $email.prop('readonly', false).css('opacity', '');
            $link.css({ 'pointer-events': '', opacity: '' });
        }

        /**
         * Show an inline error message above the form fields.
         * @param {string} msg
         * @returns {void}
         */
        function showError(msg) {
            $alertWrap.html(
                '<div class="alert alert-danger alert-dismissible" role="alert">' +
                msg +
                '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                '</div>'
            );
        }

        /* Intercept form submit — call NestJS directly via AJAX
         * @param {jQuery.Event} e
         * @returns {void}
         */
        $form.on('submit', function (e) {
            e.preventDefault();
            var emailVal = $email.val().trim();
            if (!emailVal) { showError('Please enter your email address.'); return; }

            $alertWrap.html('');
            lockForm();

            $.ajax({
                url        : _servicesUrl + 'api/auth/password/forgot',
                method     : 'POST',
                contentType: 'application/json',
                data       : JSON.stringify({ email: emailVal, appBaseUrl: _appBaseUrl }),
                dataType   : 'json'
            }).done(function () {
                /* Success — hide form, show sent confirmation panel */
                $form.hide();
                $('#fpSigninLink').hide();
                $sentEmail.text(emailVal);
                $sentPanel.css('display', 'block');
            }).fail(function (xhr) {
                unlockForm();
                var err = xhr.responseJSON;
                var msg = err && err.message
                    ? (Array.isArray(err.message) ? err.message.join(', ') : err.message)
                    : 'Something went wrong. Please try again.';
                showError(msg);
            });
        });

        /* Auto-focus */
        setTimeout(function () { $email.trigger('focus'); }, 300);
    });
}(jQuery));
</script>
