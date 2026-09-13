<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Your Setup</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="/assets/vendor/fonts/boxicons.css">
    <link rel="stylesheet" href="/assets/css/onboarding.css">
</head>
<body>
<?php
$user       = $jwtData->User ?? null;
$org        = $jwtData->Org  ?? null;
$firstName  = $user ? ($user->FirstName ?? 'there') : 'there';
$picture    = $user ? ($user->UserImage ?? '') : '';
$initial    = strtoupper(substr($firstName, 0, 1));
$cdnBase    = getenv('CDN_BASE_URL') ?: '';
$avatarSrc  = ($picture && $cdnBase) ? rtrim($cdnBase, '/') . '/' . ltrim($picture, '/') : $picture;
$shortCode  = htmlspecialchars($shortCode ?? '', ENT_QUOTES);
?>

<div class="ob-root">

    <!-- Background glows -->
    <div class="ob-glow ob-glow-1"></div>
    <div class="ob-glow ob-glow-2"></div>
    <div class="ob-glow ob-glow-3"></div>

    <!-- Particle canvas -->
    <canvas class="ob-particles" id="obParticles"></canvas>

    <!-- Main card -->
    <div class="ob-card" id="obCard">

        <!-- Header -->
        <div class="ob-header">
            <div class="ob-avatar-ring">
                <?php if (!empty($avatarSrc)): ?>
                    <img class="ob-avatar" src="<?php echo htmlspecialchars($avatarSrc, ENT_QUOTES); ?>"
                         alt="<?php echo htmlspecialchars($firstName, ENT_QUOTES); ?>"
                         onerror="this.style.display='none';document.getElementById('obAvatarPh').style.display='flex';">
                    <div class="ob-avatar-placeholder" id="obAvatarPh" style="display:none;"><?php echo $initial; ?></div>
                <?php else: ?>
                    <div class="ob-avatar-placeholder"><?php echo $initial; ?></div>
                <?php endif; ?>
            </div>

            <div class="ob-badge"><span class="ob-badge-dot"></span>One last step</div>

            <h1 class="ob-title">
                Welcome, <span><?php echo htmlspecialchars($firstName, ENT_QUOTES); ?>!</span><br>
                Set up your organisation
            </h1>
            <p class="ob-subtitle">Tell us about your business so your account is ready to use.</p>
        </div>

        <!-- Progress indicator -->
        <div class="ob-progress">
            <div class="ob-step done">
                <div class="ob-step-icon"><i class="bx bx-check"></i></div>
                <div class="ob-step-label">Google Sign‑in</div>
            </div>
            <div class="ob-step done">
                <div class="ob-step-icon"><i class="bx bx-check"></i></div>
                <div class="ob-step-label">Account Created</div>
            </div>
            <div class="ob-step active">
                <div class="ob-step-icon">3</div>
                <div class="ob-step-label">Organisation Info</div>
            </div>
            <div class="ob-step pending">
                <div class="ob-step-icon">4</div>
                <div class="ob-step-label">Dashboard</div>
            </div>
        </div>

        <!-- Alert -->
        <div class="ob-alert" id="obAlert">
            <i class="bx bx-error-circle" style="font-size:1.1rem;flex-shrink:0;margin-top:1px;"></i>
            <span id="obAlertText"></span>
        </div>

        <!-- Form -->
        <form id="obForm" autocomplete="off" onsubmit="return false;">
            <div class="ob-form-grid">

                <div class="ob-field full">
                    <label class="ob-label" for="obOrgName">Organisation Name</label>
                    <input type="text" id="obOrgName" class="ob-input" placeholder="e.g. Rishika 2K Enterprises"
                           maxlength="150" autocomplete="off">
                    <div class="ob-field-err" id="obOrgNameErr">
                        <i class="bx bx-error-circle"></i><span></span>
                    </div>
                </div>

                <div class="ob-field full">
                    <label class="ob-label" for="obBrandName">Brand Name</label>
                    <input type="text" id="obBrandName" class="ob-input" placeholder="e.g. R2K Enterprises"
                           maxlength="150" autocomplete="off">
                    <div class="ob-field-err" id="obBrandNameErr">
                        <i class="bx bx-error-circle"></i><span></span>
                    </div>
                </div>

                <div class="ob-field">
                    <label class="ob-label" for="obShortCode">Short Code</label>
                    <input type="text" id="obShortCode" class="ob-input"
                           placeholder="e.g. R2K"
                           maxlength="3" autocomplete="off"
                           value="<?php echo $shortCode; ?>"
                           style="text-transform:uppercase;letter-spacing:0.12em;">
                    <div class="ob-field-err" id="obShortCodeErr">
                        <i class="bx bx-error-circle"></i><span></span>
                    </div>
                </div>

                <div class="ob-field">
                    <label class="ob-label" for="obMobile">Mobile Number</label>
                    <input type="tel" id="obMobile" class="ob-input" placeholder="10-digit number"
                           maxlength="10" pattern="[0-9]{10}" autocomplete="off">
                    <div class="ob-field-err" id="obMobileErr">
                        <i class="bx bx-error-circle"></i><span></span>
                    </div>
                </div>

                <div class="ob-field">
                    <label class="ob-label" for="obState">State</label>
                    <select id="obState" class="ob-input">
                        <option value="">Select state...</option>
                    </select>
                    <div class="ob-field-err" id="obStateErr">
                        <i class="bx bx-error-circle"></i><span></span>
                    </div>
                </div>

                <div class="ob-field">
                    <label class="ob-label" for="obTimezone">Timezone</label>
                    <select id="obTimezone" class="ob-input">
                        <option value="">Select timezone...</option>
                    </select>
                    <div class="ob-field-err" id="obTimezoneErr">
                        <i class="bx bx-error-circle"></i><span></span>
                    </div>
                </div>

                <div class="ob-field full">
                    <label class="ob-label" for="obGSTIN">
                        GSTIN <span class="ob-label-opt">(optional)</span>
                    </label>
                    <input type="text" id="obGSTIN" class="ob-input" placeholder="22AAAAA0000A1Z5"
                           maxlength="15" autocomplete="off" style="text-transform:uppercase;letter-spacing:0.06em;">
                    <div class="ob-field-err" id="obGSTINErr">
                        <i class="bx bx-error-circle"></i><span></span>
                    </div>
                </div>

            </div>

            <div class="ob-submit-wrap">
                <button type="button" class="ob-btn" id="obSubmitBtn" onclick="obSubmit()">
                    <i class="bx bx-rocket" id="obBtnIcon"></i>
                    <span id="obBtnLabel">Complete Setup &amp; Enter Dashboard</span>
                    <i class="bx bx-loader-alt bx-spin" id="obBtnLoader" style="display:none;"></i>
                </button>
            </div>
        </form>

        <!-- Success screen (hidden until submit) -->
        <div class="ob-success" id="obSuccess">
            <div class="ob-success-icon"><i class="bx bx-check-circle"></i></div>
            <div class="ob-success-title">You're all set!</div>
            <p class="ob-success-msg">Your organisation is configured.<br>Taking you to your dashboard now…</p>
            <div class="ob-success-redirect">
                <i class="bx bx-loader-alt bx-spin"></i> Redirecting…
            </div>
        </div>

        <p class="ob-footer-note">
            <i class="bx bx-lock-alt"></i>
            Your information is encrypted and never shared.
        </p>

    </div>
    <!-- Processing overlay -->
    <div class="ob-overlay" id="obProcessingOverlay">
        <div class="ob-overlay-logo-wrap">
            <div class="ob-overlay-ring"></div>
            <img src="https://pub-bb40942a33344637936ade1f3800ff8b.r2.dev/Global/favicon_io/android-chrome-512x512-1.png"
                 class="ob-overlay-logo-img" alt="R2K">
        </div>
        <div class="ob-overlay-text">Setting up your organisation…</div>
    </div>

</div>

<script>
(function () {
    /* Preload overlay logo so it appears instantly */
    (function () {
        var _img = new Image();
        _img.src = 'https://pub-bb40942a33344637936ade1f3800ff8b.r2.dev/Global/favicon_io/android-chrome-512x512-1.png';
    }());

    /* ── Particle system ─────────────────────────────────────────── */
    (function () {
        var canvas = document.getElementById('obParticles');
        if (!canvas) return;
        var ctx    = canvas.getContext('2d');
        var pts    = [];
        var W, H;

        function resize() {
            W = canvas.width  = window.innerWidth;
            H = canvas.height = window.innerHeight;
        }
        resize();
        window.addEventListener('resize', resize);

        for (var i = 0; i < 70; i++) {
            pts.push({
                x:  Math.random() * window.innerWidth,
                y:  Math.random() * window.innerHeight,
                r:  0.5 + Math.random() * 1.2,
                dx: (Math.random() - 0.5) * 0.3,
                dy: (Math.random() - 0.5) * 0.3,
                o:  0.15 + Math.random() * 0.45
            });
        }

        function draw() {
            ctx.clearRect(0, 0, W, H);
            pts.forEach(function (p) {
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                ctx.fillStyle = 'rgba(148, 163, 252, ' + p.o + ')';
                ctx.fill();
                p.x += p.dx;
                p.y += p.dy;
                if (p.x < 0) p.x = W;
                if (p.x > W) p.x = 0;
                if (p.y < 0) p.y = H;
                if (p.y > H) p.y = 0;
            });
            requestAnimationFrame(draw);
        }
        draw();
    }());

    /* ── State dropdown ──────────────────────────────────────────── */
    var indianStates = [
        { code: '01', name: 'Jammu & Kashmir' },{ code: '02', name: 'Himachal Pradesh' },
        { code: '03', name: 'Punjab' },          { code: '04', name: 'Chandigarh' },
        { code: '05', name: 'Uttarakhand' },     { code: '06', name: 'Haryana' },
        { code: '07', name: 'Delhi' },           { code: '08', name: 'Rajasthan' },
        { code: '09', name: 'Uttar Pradesh' },   { code: '10', name: 'Bihar' },
        { code: '11', name: 'Sikkim' },          { code: '12', name: 'Arunachal Pradesh' },
        { code: '13', name: 'Nagaland' },        { code: '14', name: 'Manipur' },
        { code: '15', name: 'Mizoram' },         { code: '16', name: 'Tripura' },
        { code: '17', name: 'Meghalaya' },       { code: '18', name: 'Assam' },
        { code: '19', name: 'West Bengal' },     { code: '20', name: 'Jharkhand' },
        { code: '21', name: 'Odisha' },          { code: '22', name: 'Chhattisgarh' },
        { code: '23', name: 'Madhya Pradesh' },  { code: '24', name: 'Gujarat' },
        { code: '25', name: 'Daman & Diu' },     { code: '26', name: 'Dadra & Nagar Haveli' },
        { code: '27', name: 'Maharashtra' },     { code: '28', name: 'Andhra Pradesh' },
        { code: '29', name: 'Karnataka' },       { code: '30', name: 'Goa' },
        { code: '31', name: 'Lakshadweep' },     { code: '32', name: 'Kerala' },
        { code: '33', name: 'Tamil Nadu' },      { code: '34', name: 'Puducherry' },
        { code: '35', name: 'Andaman & Nicobar Islands' },
        { code: '36', name: 'Telangana' },       { code: '37', name: 'Andhra Pradesh (New)' },
        { code: '38', name: 'Ladakh' },
    ];

    (function () {
        var sel = document.getElementById('obState');
        indianStates.forEach(function (s) {
            var opt = document.createElement('option');
            opt.value       = s.code + '|' + s.name;
            opt.textContent = s.name;
            sel.appendChild(opt);
        });
    }());

    /* ── Timezone dropdown ───────────────────────────────────────── */
    var _timezones = <?php echo json_encode(array_map(function($tz) {
        return ['uid' => (int)$tz->TimezoneUID, 'label' => $tz->Timezone . ' (' . $tz->GmtOffset . ')'];
    }, $timezones ?? [])); ?>;

    (function () {
        var sel = document.getElementById('obTimezone');
        _timezones.forEach(function (tz) {
            var opt = document.createElement('option');
            opt.value       = tz.uid;
            opt.textContent = tz.label;
            if (tz.uid === 181) opt.selected = true;
            sel.appendChild(opt);
        });
        /* Ensure default is actually selected if not set by above */
        if (!sel.value) sel.value = '181';
    }());

    /* ── Org Name → Brand Name mirror on blur (only while Brand Name is empty) ── */
    document.getElementById('obOrgName').addEventListener('change', function () {
        var brandInput = document.getElementById('obBrandName');
        if (!brandInput.value.trim()) {
            brandInput.value = this.value.trim();
        }
        _clearErr('obOrgName');
    });

    /* ── Short code auto-uppercase ──────────────────────────────── */
    document.getElementById('obShortCode').addEventListener('input', function () {
        var pos = this.selectionStart;
        this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        this.setSelectionRange(pos, pos);
        _clearErr('obShortCode');
    });

    /* ── GSTIN auto-uppercase ───────────────────────────────────────── */
    document.getElementById('obGSTIN').addEventListener('input', function () {
        var pos = this.selectionStart;
        this.value = this.value.toUpperCase();
        this.setSelectionRange(pos, pos);
        _clearErr('obGSTIN');
    });

    /* ── Helpers ─────────────────────────────────────────────────── */
    /**
     * @param {string} fieldId
     * @param {string} msg
     */
    function _showErr(fieldId, msg) {
        var el = document.getElementById(fieldId + 'Err');
        if (!el) return;
        var span = el.querySelector('span');
        if (span) span.textContent = msg;
        el.classList.add('show');
        var input = document.getElementById(fieldId);
        if (input) input.classList.add('ob-error');
    }

    /**
     * @param {string} fieldId
     */
    function _clearErr(fieldId) {
        var el = document.getElementById(fieldId + 'Err');
        if (el) el.classList.remove('show');
        var input = document.getElementById(fieldId);
        if (input) input.classList.remove('ob-error');
    }

    function _showAlert(msg) {
        var a = document.getElementById('obAlert');
        document.getElementById('obAlertText').textContent = msg;
        a.classList.add('show');
        a.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function _hideAlert() {
        document.getElementById('obAlert').classList.remove('show');
    }

    /* ── Validate ────────────────────────────────────────────────── */
    /**
     * @returns {boolean}
     */
    function _validate() {
        var ok = true;
        _hideAlert();

        var orgName = document.getElementById('obOrgName').value.trim();
        _clearErr('obOrgName');
        if (!orgName || orgName.length < 2) {
            _showErr('obOrgName', 'Organisation name is required (min 2 characters).');
            ok = false;
        }

        var brandName = document.getElementById('obBrandName').value.trim();
        _clearErr('obBrandName');
        if (!brandName || brandName.length < 2) {
            _showErr('obBrandName', 'Brand name is required (min 2 characters).');
            ok = false;
        }

        var shortCode = document.getElementById('obShortCode').value.trim().toUpperCase();
        _clearErr('obShortCode');
        if (!shortCode || shortCode.length !== 3 || !/^[A-Z0-9]{3}$/.test(shortCode)) {
            _showErr('obShortCode', 'Short code must be exactly 3 alphanumeric characters (e.g. R2K).');
            ok = false;
        }

        var mobile = document.getElementById('obMobile').value.replace(/\D/g, '');
        _clearErr('obMobile');
        if (!mobile || mobile.length !== 10) {
            _showErr('obMobile', 'Enter a valid 10-digit mobile number.');
            ok = false;
        }

        _clearErr('obState');
        if (!document.getElementById('obState').value) {
            _showErr('obState', 'Please select your state.');
            ok = false;
        }

        _clearErr('obTimezone');
        if (!document.getElementById('obTimezone').value) {
            _showErr('obTimezone', 'Please select a timezone.');
            ok = false;
        }

        var gstin = document.getElementById('obGSTIN').value.trim().toUpperCase();
        _clearErr('obGSTIN');
        if (gstin && !/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/.test(gstin)) {
            _showErr('obGSTIN', 'Invalid GSTIN format (e.g. 22AAAAA0000A1Z5).');
            ok = false;
        }

        return ok;
    }

    /* ── Submit ──────────────────────────────────────────────────── */
    window.obSubmit = function () {
        if (!_validate()) return;

        var btn     = document.getElementById('obSubmitBtn');
        var overlay = document.getElementById('obProcessingOverlay');

        btn.disabled = true;
        overlay.classList.add('is-active');

        var body = new URLSearchParams({
            org_name:     document.getElementById('obOrgName').value.trim(),
            brand_name:   document.getElementById('obBrandName').value.trim(),
            short_code:   document.getElementById('obShortCode').value.trim().toUpperCase(),
            mobile:       document.getElementById('obMobile').value.replace(/\D/g, ''),
            state:        document.getElementById('obState').value,
            timezone_uid: document.getElementById('obTimezone').value,
            gstin:        document.getElementById('obGSTIN').value.trim().toUpperCase(),
        });

        fetch('/onboarding/complete', {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    body.toString(),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.Error) {
                overlay.classList.remove('is-active');
                btn.disabled   = false;
                _showAlert(data.Message || 'Something went wrong. Please try again.');
            } else {
                /* Overlay stays visible through the success screen + redirect */
                document.getElementById('obForm').style.display      = 'none';
                document.querySelector('.ob-progress').style.display  = 'none';
                document.getElementById('obSuccess').classList.add('show');
                setTimeout(function () { window.location.href = '/dashboard'; }, 1800);
            }
        })
        .catch(function () {
            overlay.hidden = true;
            btn.disabled   = false;
            _showAlert('A network error occurred. Please try again.');
        });
    };

    /* ── Block ESC & back-navigation attempts ────────────────────── */
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); }
    }, true);

    /* Push a dummy history state so Back button doesn't leave the page */
    history.pushState(null, '', window.location.href);
    window.addEventListener('popstate', function () {
        history.pushState(null, '', window.location.href);
    });

}());
</script>
</body>
</html>
