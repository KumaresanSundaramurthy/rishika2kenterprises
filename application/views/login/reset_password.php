<?php defined('BASEPATH') or exit('No direct script access allowed');
/** @var string $token         — reset token, injected by Login::showResetForm() */
/** @var int    $remainingSecs — seconds until token expires, injected by Login::showResetForm() */
$token         = isset($token)         ? $token         : '';
$remainingSecs = isset($remainingSecs) ? (int)$remainingSecs : 0;
?>

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
    position: relative; width: 55%;
    display: flex; align-items: center; justify-content: center;
    overflow: hidden; background: #040b18;
}
.lr-brand::before {
    content: ''; position: absolute; inset: 0;
    background:
        radial-gradient(ellipse 70% 60% at 30% 40%, rgba(245,158,11,0.14) 0%, transparent 70%),
        radial-gradient(ellipse 50% 50% at 75% 70%, rgba(16,185,129,0.07) 0%, transparent 60%);
    z-index: 0;
}
.lr-shapes { position: absolute; inset: 0; z-index: 0; pointer-events: none; }
.lr-shape {
    position: absolute; border-radius: 50%;
    border: 1px solid rgba(245,158,11,0.18);
    animation: drift linear infinite;
}
.lr-shape:nth-child(1) { width:320px;height:320px; top:-60px;left:-80px; animation-duration:22s; border-color:rgba(245,158,11,0.12); }
.lr-shape:nth-child(2) { width:180px;height:180px; top:40%;right:-40px; animation-duration:18s; animation-delay:-7s; border-color:rgba(16,185,129,0.14); }
.lr-shape:nth-child(3) { width:240px;height:240px; bottom:-60px;left:20%; animation-duration:26s; animation-delay:-12s; border-color:rgba(245,158,11,0.08); }
.lr-shape:nth-child(4) { width:100px;height:100px; top:20%;right:20%; animation-duration:15s; animation-delay:-3s; border-radius:18px; rotate:45deg; border-color:rgba(245,158,11,0.22); }
.lr-shape:nth-child(5) { width:60px;height:60px; bottom:25%;left:12%; animation-duration:20s; animation-delay:-9s; border-radius:12px; rotate:30deg; border-color:rgba(16,185,129,0.18); }
@keyframes drift {
    0%   { transform: translateY(0px) rotate(0deg); }
    33%  { transform: translateY(-18px) rotate(4deg); }
    66%  { transform: translateY(10px) rotate(-3deg); }
    100% { transform: translateY(0px) rotate(0deg); }
}
.lr-brand::after {
    content: ''; position: absolute; top:0; right:0;
    width:3px; height:100%;
    background: linear-gradient(to bottom, transparent 0%, rgba(245,158,11,0.6) 35%, rgba(245,158,11,0.6) 65%, transparent 100%);
    z-index: 1;
}
.lr-dots { position: absolute; inset: 0; z-index: 0; pointer-events: none; }
.lr-dot {
    position: absolute; width:4px; height:4px; border-radius:50%;
    background: rgba(245,158,11,0.5); animation: floatDot ease-in-out infinite;
}
.lr-dot:nth-child(1) { top:15%;left:25%; animation-duration:6s; }
.lr-dot:nth-child(2) { top:30%;left:60%; animation-duration:8s; animation-delay:-2s; }
.lr-dot:nth-child(3) { top:55%;left:18%; animation-duration:7s; animation-delay:-4s; }
.lr-dot:nth-child(4) { top:70%;left:75%; animation-duration:9s; animation-delay:-1s; }
.lr-dot:nth-child(5) { top:82%;left:40%; animation-duration:6.5s; animation-delay:-3s; }
.lr-dot:nth-child(6) { top:22%;left:82%; animation-duration:7.5s; animation-delay:-5s; }
.lr-dot:nth-child(7) { top:45%;left:45%; animation-duration:8.5s; animation-delay:-1.5s; background:rgba(16,185,129,0.4); }
.lr-dot:nth-child(8) { top:10%;left:48%; animation-duration:5.5s; animation-delay:-6s; }
@keyframes floatDot {
    0%,100% { transform:translateY(0); opacity:0.5; }
    50%     { transform:translateY(-14px); opacity:1; }
}

.lr-brand-content {
    position: relative; z-index: 2;
    padding: 60px 56px; max-width: 520px;
    animation: fadeInLeft 0.9s ease-out both;
}
@keyframes fadeInLeft {
    from { opacity:0; transform:translateX(-30px); }
    to   { opacity:1; transform:translateX(0); }
}
.lr-brand-logo { margin-bottom: 36px; }
.lr-brand-logo img {
    width:68px; height:68px; border-radius:18px;
    box-shadow: 0 0 0 1px rgba(245,158,11,0.3), 0 8px 32px rgba(245,158,11,0.2);
}
.lr-brand-name { font-size:42px; font-weight:800; line-height:1.15; letter-spacing:-0.5px; margin-bottom:14px; }
.lr-brand-name .gold  { color:#f59e0b; display:block; }
.lr-brand-name .white { color:#f1f5f9; display:block; }
.lr-brand-sub { font-size:14px; font-weight:500; color:#64748b; letter-spacing:2px; text-transform:uppercase; margin-bottom:48px; }

/* Illustration */
.lr-illus {
    width:96px; height:96px; border-radius:24px;
    background:rgba(245,158,11,0.08); border:1px solid rgba(245,158,11,0.2);
    display:flex; align-items:center; justify-content:center;
    margin-bottom:28px;
    animation: pulseIllus 3s ease-in-out infinite;
}
.lr-illus i { font-size:46px; color:#f59e0b; }
@keyframes pulseIllus {
    0%,100% { box-shadow:0 0 0 0 rgba(245,158,11,0.15); }
    50%     { box-shadow:0 0 0 16px rgba(245,158,11,0); }
}
.lr-illus-text h4 { font-size:22px; font-weight:700; color:#f1f5f9; margin-bottom:8px; }
.lr-illus-text p  { font-size:14px; color:#64748b; line-height:1.65; max-width:320px; }

/* Countdown timer */
.lr-timer {
    display:inline-flex; align-items:center; gap:8px;
    margin-top:28px; padding:10px 16px; border-radius:50px;
    background:rgba(245,158,11,0.08); border:1px solid rgba(245,158,11,0.2);
    font-size:13px; color:#f59e0b; font-weight:500;
}
.lr-timer i { font-size:16px; }
#timerDisplay { font-variant-numeric: tabular-nums; font-weight:700; }

/* ── RIGHT FORM PANEL ──────────────────────────────────────── */
.lr-form-panel {
    width:45%; display:flex; align-items:center; justify-content:center;
    padding:40px 32px; background:#060e20;
    position:relative; overflow-y:auto;
}
.lr-form-panel::before {
    content:''; position:absolute; inset:0;
    background:radial-gradient(ellipse 80% 60% at 50% 0%, rgba(245,158,11,0.06) 0%, transparent 60%);
    pointer-events:none;
}
.lr-form-card {
    width:100%; max-width:400px;
    position:relative; z-index:1;
    animation: fadeInRight 0.8s ease-out 0.2s both;
}
@keyframes fadeInRight {
    from { opacity:0; transform:translateY(24px); }
    to   { opacity:1; transform:translateY(0); }
}

/* Mobile logo */
.lr-mobile-logo { display:none; text-align:center; margin-bottom:32px; }
.lr-mobile-logo img { width:56px; height:56px; border-radius:14px; box-shadow:0 0 0 1px rgba(245,158,11,0.3); margin-bottom:12px; }
.lr-mobile-logo h2 { font-size:22px; font-weight:800; color:#f1f5f9; }
.lr-mobile-logo h2 span { color:#f59e0b; }

.lr-back {
    display:inline-flex; align-items:center; gap:6px;
    font-size:13px; color:#475569; text-decoration:none;
    margin-bottom:28px; transition:color 0.2s;
}
.lr-back:hover { color:#f59e0b; }
.lr-back i { font-size:16px; }

.lr-form-head { margin-bottom:32px; }
.lr-form-head h3 { font-size:26px; font-weight:700; color:#f1f5f9; margin-bottom:8px; letter-spacing:-0.3px; }
.lr-form-head p  { color:#64748b; font-size:14px; line-height:1.6; }

/* Alert */
.lr-alerts { margin-bottom:20px; }
.lr-alerts .alert {
    background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.25);
    border-radius:10px; color:#fca5a5; padding:11px 14px; font-size:13px;
}
.lr-alerts .alert-success {
    background:rgba(16,185,129,0.1); border-color:rgba(16,185,129,0.25); color:#6ee7b7;
}
.lr-alerts .alert-dismissible .btn-close { filter:invert(1); opacity:0.5; }

/* Field */
.lr-field { margin-bottom:22px; }
.lr-field label {
    display:block; font-size:13px; font-weight:600; color:#94a3b8;
    margin-bottom:8px; letter-spacing:0.3px; text-transform:uppercase;
}
.lr-input-wrap { position:relative; }
.lr-input-wrap .lr-input-icon {
    position:absolute; left:14px; top:50%; transform:translateY(-50%);
    color:#475569; font-size:18px; pointer-events:none; transition:color 0.25s;
}
.lr-input-wrap input {
    width:100%;
    background:rgba(255,255,255,0.04); border:1.5px solid rgba(255,255,255,0.08);
    border-radius:12px; padding:13px 44px 13px 44px;
    color:#f1f5f9; font-size:14px; font-family:inherit;
    outline:none; transition:all 0.25s;
    -webkit-appearance:none; appearance:none;
}
.lr-input-wrap input:focus {
    background:rgba(245,158,11,0.05); border-color:rgba(245,158,11,0.5);
    box-shadow:0 0 0 3px rgba(245,158,11,0.08);
}
.lr-input-wrap:focus-within .lr-input-icon { color:#f59e0b; }
.lr-input-wrap input::placeholder { color:#334155; }

.lr-pw-toggle {
    position:absolute; right:14px; top:50%; transform:translateY(-50%);
    background:none; border:none; color:#475569; cursor:pointer;
    font-size:18px; padding:2px; transition:color 0.25s; display:flex; align-items:center;
}
.lr-pw-toggle:hover { color:#f59e0b; }

/* Strength bar */
.lr-strength { margin-top:8px; }
.lr-strength-bar {
    height:3px; border-radius:4px;
    background:rgba(255,255,255,0.06);
    overflow:hidden; margin-bottom:5px;
}
.lr-strength-fill {
    height:100%; width:0; border-radius:4px;
    transition:width 0.3s, background 0.3s;
}
.lr-strength-label { font-size:11px; color:#475569; }

/* Match indicator */
.lr-match { font-size:11px; margin-top:6px; }
.lr-match.ok  { color:#10b981; }
.lr-match.err { color:#ef4444; }

/* Button */
.lr-btn {
    width:100%; padding:14px; border:none; border-radius:12px;
    background:linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color:#040b18; font-size:15px; font-weight:700;
    letter-spacing:0.3px; cursor:pointer; position:relative;
    overflow:hidden; transition:all 0.3s;
    box-shadow:0 4px 20px rgba(245,158,11,0.3);
    font-family:inherit;
}
.lr-btn::before {
    content:''; position:absolute; inset:0;
    background:linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    opacity:0; transition:opacity 0.3s;
}
.lr-btn:hover::before { opacity:1; }
.lr-btn:hover  { transform:translateY(-2px); box-shadow:0 8px 28px rgba(245,158,11,0.4); }
.lr-btn:active { transform:translateY(0); }
.lr-btn:disabled { opacity:0.5; cursor:not-allowed; transform:none; }
.lr-btn span { position:relative; z-index:1; display:flex; align-items:center; justify-content:center; gap:8px; }
.lr-btn .lr-ripple {
    position:absolute; border-radius:50%;
    background:rgba(255,255,255,0.3);
    transform:scale(0); animation:ripple 0.6s linear; pointer-events:none;
}
@keyframes ripple { to { transform:scale(4); opacity:0; } }

.lr-signin-link { text-align:center; margin-top:24px; font-size:13px; color:#475569; }
.lr-signin-link a { color:#f59e0b; text-decoration:none; font-weight:600; transition:color 0.2s; }
.lr-signin-link a:hover { color:#fbbf24; }

/* Responsive */
@media (max-width:900px) {
    .lr-brand { display:none; }
    .lr-form-panel { width:100%; background:#040b18; padding:40px 24px; }
    .lr-mobile-logo { display:block; }
    .lr-form-card { max-width:440px; }
}
@media (max-width:480px) {
    .lr-form-panel { padding:32px 20px; }
    .lr-form-head h3 { font-size:22px; }
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
                <img src="https://pub-bb40942a33344637936ade1f3800ff8b.r2.dev/Global/favicon_io/android-chrome-512x512-1.png"
                     alt="<?php echo getSiteConfiguration()->ShortName; ?>">
            </div>
            <div class="lr-brand-name">
                <span class="gold">RISHIKA 2K</span>
                <span class="white">ENTERPRISES</span>
            </div>
            <p class="lr-brand-sub">Billing Management System</p>

            <div class="lr-illus">
                <i class="bx bx-shield-check"></i>
            </div>
            <div class="lr-illus-text">
                <h4>Secure Password Reset</h4>
                <p>Choose a strong new password for your account. This link is valid for 15 minutes only and will expire after use.</p>
            </div>

            <div class="lr-timer">
                <i class="bx bx-time-five"></i>
                Link expires in &nbsp;<span id="timerDisplay"><?php
                    $m = floor($remainingSecs / 60);
                    $s = $remainingSecs % 60;
                    echo ($m < 10 ? '0' : '') . $m . ':' . ($s < 10 ? '0' : '') . $s;
                ?></span>
            </div>
        </div>
    </div>

    <!-- RIGHT FORM PANEL -->
    <div class="lr-form-panel">
        <div class="lr-form-card">

            <div class="lr-mobile-logo">
                <img src="https://pub-bb40942a33344637936ade1f3800ff8b.r2.dev/Global/favicon_io/android-chrome-512x512-1.png" alt="logo">
                <h2><span>RISHIKA 2K</span> ENTERPRISES</h2>
            </div>

            <a href="/forgot-password" class="lr-back">
                <i class="bx bx-arrow-back"></i> Request a new link
            </a>

            <div class="lr-form-head">
                <h3>Set New Password</h3>
                <p>Create a new password for your account. It must be at least 6 characters.</p>
            </div>

            <div class="lr-alerts">
                <?php $this->load->view('login/alerts'); ?>
            </div>

            <?php echo form_open('reset-password/update', ['id' => 'resetForm', 'autocomplete' => 'off']); ?>

            <!-- Hidden token -->
            <input type="hidden" name="ResetToken" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">

            <div class="lr-field">
                <label for="NewPassword">New Password</label>
                <div class="lr-input-wrap">
                    <input type="password" id="NewPassword" name="NewPassword"
                           placeholder="Enter new password" autocomplete="new-password" required />
                    <i class="bx bx-lock-alt lr-input-icon"></i>
                    <button type="button" class="lr-pw-toggle" id="pwT1" aria-label="Toggle">
                        <i class="bx bx-hide" id="pwI1"></i>
                    </button>
                </div>
                <div class="lr-strength" id="strengthWrap" style="display:none">
                    <div class="lr-strength-bar"><div class="lr-strength-fill" id="sFill"></div></div>
                    <span class="lr-strength-label" id="sLabel"></span>
                </div>
            </div>

            <div class="lr-field">
                <label for="ConfirmPassword">Confirm New Password</label>
                <div class="lr-input-wrap">
                    <input type="password" id="ConfirmPassword" name="ConfirmPassword"
                           placeholder="Re-enter new password" autocomplete="new-password" required />
                    <i class="bx bx-lock-alt lr-input-icon"></i>
                    <button type="button" class="lr-pw-toggle" id="pwT2" aria-label="Toggle">
                        <i class="bx bx-hide" id="pwI2"></i>
                    </button>
                </div>
                <div class="lr-match" id="matchMsg"></div>
            </div>

            <button type="submit" class="lr-btn" id="resetBtn" disabled>
                <span>
                    <i class="bx bx-check-shield" style="font-size:18px"></i>
                    Update Password
                </span>
            </button>

            <?php echo form_close(); ?>

            <p class="lr-signin-link">
                Remembered it? <a href="/portal">Sign in here</a>
            </p>

        </div>
    </div>

</div>

<?php $this->load->view('login/footer'); ?>

<script>
(function () {
    document.addEventListener('DOMContentLoaded', function () {
        var el = document.getElementById('template-customizer');
        if (el) el.classList.add('d-none');
    });

    // ── Countdown timer (15 min) ────────────────────────────
    var totalSecs = <?php echo (int)$remainingSecs; ?>;
    var timerEl   = document.getElementById('timerDisplay');
    var timerInterval = setInterval(function () {
        totalSecs--;
        if (totalSecs <= 0) {
            clearInterval(timerInterval);
            timerEl.textContent = 'Expired';
            timerEl.style.color = '#ef4444';
            document.getElementById('resetBtn').disabled = true;
            return;
        }
        var m = Math.floor(totalSecs / 60);
        var s = totalSecs % 60;
        timerEl.textContent = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
        if (totalSecs <= 60) timerEl.style.color = '#ef4444';
        else if (totalSecs <= 180) timerEl.style.color = '#f59e0b';
    }, 1000);

    // ── Password toggle ─────────────────────────────────────
    function addToggle(btnId, iconId, inputId) {
        var btn  = document.getElementById(btnId);
        var icon = document.getElementById(iconId);
        var inp  = document.getElementById(inputId);
        if (btn) {
            btn.addEventListener('click', function () {
                if (inp.type === 'password') {
                    inp.type = 'text'; icon.className = 'bx bx-show';
                } else {
                    inp.type = 'password'; icon.className = 'bx bx-hide';
                }
            });
        }
    }
    addToggle('pwT1', 'pwI1', 'NewPassword');
    addToggle('pwT2', 'pwI2', 'ConfirmPassword');

    // ── Password strength ───────────────────────────────────
    var pwInp   = document.getElementById('NewPassword');
    var cfInp   = document.getElementById('ConfirmPassword');
    var sFill   = document.getElementById('sFill');
    var sLabel  = document.getElementById('sLabel');
    var sWrap   = document.getElementById('strengthWrap');
    var matchEl = document.getElementById('matchMsg');
    var resetBtn = document.getElementById('resetBtn');

    function calcStrength(pw) {
        var score = 0;
        if (pw.length >= 6)  score++;
        if (pw.length >= 10) score++;
        if (/[A-Z]/.test(pw)) score++;
        if (/[0-9]/.test(pw)) score++;
        if (/[^A-Za-z0-9]/.test(pw)) score++;
        return score;
    }

    var colors = ['#ef4444','#f97316','#f59e0b','#84cc16','#10b981'];
    var labels = ['Very weak','Weak','Fair','Good','Strong'];

    function updateUI() {
        var pw = pwInp.value;
        var cf = cfInp.value;

        if (pw.length > 0) {
            sWrap.style.display = 'block';
            var s = Math.min(calcStrength(pw), 4);
            sFill.style.width  = ((s + 1) * 20) + '%';
            sFill.style.background = colors[s];
            sLabel.textContent = labels[s];
            sLabel.style.color = colors[s];
        } else {
            sWrap.style.display = 'none';
        }

        if (cf.length > 0) {
            if (pw === cf) {
                matchEl.className = 'lr-match ok';
                matchEl.innerHTML = '<i class="bx bx-check"></i> Passwords match';
            } else {
                matchEl.className = 'lr-match err';
                matchEl.innerHTML = '<i class="bx bx-x"></i> Passwords do not match';
            }
        } else {
            matchEl.textContent = '';
            matchEl.className   = 'lr-match';
        }

        var valid = pw.length >= 6 && pw === cf && totalSecs > 0;
        resetBtn.disabled = !valid;
    }

    pwInp.addEventListener('input', updateUI);
    cfInp.addEventListener('input', updateUI);

    // ── Ripple ──────────────────────────────────────────────
    resetBtn.addEventListener('click', function (e) {
        if (resetBtn.disabled) return;
        var r = document.createElement('span');
        var d = Math.max(resetBtn.clientWidth, resetBtn.clientHeight);
        var rect = resetBtn.getBoundingClientRect();
        r.className = 'lr-ripple';
        r.style.cssText = 'width:' + d + 'px;height:' + d + 'px;left:' + (e.clientX - rect.left - d/2) + 'px;top:' + (e.clientY - rect.top - d/2) + 'px';
        resetBtn.appendChild(r);
        setTimeout(function () { r.remove(); }, 700);
    });

    // Auto-focus
    setTimeout(function () { pwInp.focus(); }, 300);
})();
</script>
