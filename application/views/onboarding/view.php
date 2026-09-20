<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Your Setup</title>
<?php
$_obTk      = strtolower($jwtData->Org->OrgToken ?? '');
$_obEnv     = defined('ENVIRONMENT') ? ENVIRONMENT : 'production';
$_obPrefix  = $_obTk ? $_obTk . '-' . ($_obEnv === 'production' ? 'P' : 'S') : '';
?>
    <meta name="upstash-url"    content="<?= htmlspecialchars(getenv('UPSTASH_REDIS_REST_URL')   ?: '') ?>">
    <meta name="upstash-token"  content="<?= htmlspecialchars(getenv('UPSTASH_REDIS_REST_TOKEN') ?: '') ?>">
    <meta name="app-org-prefix" content="<?= htmlspecialchars($_obPrefix) ?>">
<?php unset($_obTk, $_obEnv, $_obPrefix); ?>
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
$avatarSrc  = avatarUrl($picture);
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
            <div class="ob-step active" id="obStepOrg">
                <div class="ob-step-icon">3</div>
                <div class="ob-step-label">Organisation Info</div>
            </div>
            <div class="ob-step pending" id="obStepAddr" style="display:none;">
                <div class="ob-step-icon">4</div>
                <div class="ob-step-label">Address</div>
            </div>
            <div class="ob-step pending" id="obStepPlan">
                <div class="ob-step-icon" id="obStepPlanNum">4</div>
                <div class="ob-step-label">Select Plan</div>
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
                    <div class="ob-phone-wrap">
                        <span class="ob-phone-prefix">+91</span>
                        <input type="tel" id="obMobile" class="ob-phone-input" placeholder="10-digit number"
                               maxlength="10" pattern="[0-9]{10}" autocomplete="off">
                    </div>
                    <div class="ob-field-err" id="obMobileErr">
                        <i class="bx bx-error-circle"></i><span></span>
                    </div>
                    <div class="ob-field-ok" id="obMobileOk" style="display:none;">
                        <i class="bx bx-check-circle"></i> Mobile is available
                    </div>
                </div>

                <div class="ob-field full">
                    <label class="ob-label" for="obState">State</label>
                    <select id="obState" class="ob-input">
                        <option value="">Select state...</option>
                    </select>
                    <div class="ob-field-err" id="obStateErr">
                        <i class="bx bx-error-circle"></i><span></span>
                    </div>
                </div>

                <div class="ob-field full">
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
                    <div class="ob-field-ok" id="obGSTINOk" style="display:none;">
                        <i class="bx bx-check-circle"></i> GSTIN is valid
                    </div>
                </div>

            </div>

            <div class="ob-submit-wrap">
                <button type="button" class="ob-btn" id="obNextBtn" onclick="obNextStep()" disabled>
                    <i class="bx bx-right-arrow-alt" id="obBtnIcon"></i>
                    <span id="obBtnLabel">Next</span>
                    <i class="bx bx-loader-alt bx-spin" id="obBtnLoader" style="display:none;"></i>
                </button>
            </div>
        </form>

        <!-- Address form (step 2, shown only when no GSTIN) -->
        <form id="obAddrForm" autocomplete="off" onsubmit="return false;" style="display:none;">
            <div class="ob-form-grid">

                <div class="ob-field full">
                    <label class="ob-label" for="obAddrLine1">Address Line 1</label>
                    <input type="text" id="obAddrLine1" class="ob-input" placeholder="Street / Building / Door No."
                           maxlength="200" autocomplete="off">
                    <div class="ob-field-err" id="obAddrLine1Err">
                        <i class="bx bx-error-circle"></i><span></span>
                    </div>
                </div>

                <div class="ob-field full">
                    <label class="ob-label" for="obAddrLine2">
                        Address Line 2 <span class="ob-label-opt">(optional)</span>
                    </label>
                    <input type="text" id="obAddrLine2" class="ob-input" placeholder="Area / Landmark"
                           maxlength="200" autocomplete="off">
                </div>

                <div class="ob-field">
                    <label class="ob-label" for="obAddrCity">City</label>
                    <select id="obAddrCity" class="ob-input">
                        <option value="">Select city…</option>
                    </select>
                    <div class="ob-field-err" id="obAddrCityErr">
                        <i class="bx bx-error-circle"></i><span></span>
                    </div>
                </div>

                <div class="ob-field">
                    <label class="ob-label" for="obAddrPincode">Pincode</label>
                    <input type="text" id="obAddrPincode" class="ob-input" placeholder="6-digit pincode"
                           maxlength="6" pattern="[0-9]{6}" autocomplete="off">
                    <div class="ob-field-err" id="obAddrPincodeErr">
                        <i class="bx bx-error-circle"></i><span></span>
                    </div>
                </div>

            </div>

            <div class="ob-submit-wrap" style="display:flex;gap:0.75rem;">
                <button type="button" class="ob-btn" id="obAddrBackBtn" onclick="obPrevStep()"
                        style="flex:0 0 auto;width:auto;padding-left:1.25rem;padding-right:1.25rem;background:rgba(105,108,255,0.15);box-shadow:none;">
                    <i class="bx bx-left-arrow-alt"></i>
                </button>
                <button type="button" class="ob-btn" id="obAddrSubmitBtn" onclick="obSubmitFinal()" style="flex:1;">
                    <i class="bx bx-rocket" id="obAddrBtnIcon"></i>
                    <span id="obAddrBtnLabel">Complete Setup</span>
                    <i class="bx bx-loader-alt bx-spin" id="obAddrBtnLoader" style="display:none;"></i>
                </button>
            </div>
        </form>

        <!-- Success screen (hidden until submit) -->
        <div class="ob-success" id="obSuccess">
            <div class="ob-success-icon"><i class="bx bx-check-circle"></i></div>
            <div class="ob-success-title">You're all set!</div>
            <p class="ob-success-msg">Your organisation is configured.<br>Taking you to plan selection now…</p>
            <div class="ob-success-redirect">
                <i class="bx bx-loader-alt bx-spin"></i> Redirecting…
            </div>
        </div>

        <p class="ob-footer-note">
            <i class="bx bx-lock-alt"></i>
            Your information is encrypted and never shared.
        </p>

    </div>

</div>

<script src="/assets/js/services/upstash-service.js"></script>
<script src="/js/common/global-overlay.js"></script>
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

    /* ── State dropdown — Upstash cache-first, DB fallback ──────── */
    /* Key: r2k-loc-states (HASH), field: 'in', each row: {name, iso2} */
    (function seedObStates() {
        /**
         * @param {Array} data
         * @returns {void}
         */
        function _renderStates(data) {
            var sel = document.getElementById('obState');
            data.forEach(function (s) {
                var iso2 = s.iso2 || '';
                var name = s.name || '';
                if (!iso2 || !name) return;
                var opt = document.createElement('option');
                opt.value        = iso2 + '|' + name;
                opt.dataset.iso2 = iso2;
                opt.textContent  = name;
                sel.appendChild(opt);
            });
        }

        function _fetchStatesFromServer() {
            fetch('/onboarding/getStates', { method: 'GET' })
                .then(function (r) { return r.json(); })
                .then(function (resp) {
                    if (!resp.Error && Array.isArray(resp.Data) && resp.Data.length > 0) {
                        if (UpstashService.isEnabled()) {
                            UpstashService.hset(UpstashService.globalKey('loc-states'), 'in', resp.Data);
                        }
                        _renderStates(resp.Data);
                    }
                })
                .catch(function () {});
        }

        if (!UpstashService.isEnabled()) {
            _fetchStatesFromServer();
            return;
        }
        UpstashService.hget(UpstashService.globalKey('loc-states'), 'in').then(function (data) {
            if (Array.isArray(data) && data.length > 0) {
                _renderStates(data);
            } else {
                _fetchStatesFromServer();
            }
        }).catch(function () { _fetchStatesFromServer(); });
    }());

    /* ── Timezone dropdown — Upstash cache-first ────────────────── */
    /* Key: r2k-loc-timezone-all (plain get), each row: {TimezoneUID, Timezone, GmtOffset} */
    (function seedObTimezones() {
        /**
         * @param {Array} data
         * @returns {void}
         */
        function _renderTimezones(data) {
            var sel = document.getElementById('obTimezone');
            data.forEach(function (tz) {
                var uid   = tz.TimezoneUID || tz.uid || 0;
                var label = (tz.Timezone || tz.label || '') + ' (' + (tz.GmtOffset || '') + ')';
                if (!uid) return;
                var opt = document.createElement('option');
                opt.value       = uid;
                opt.textContent = label;
                sel.appendChild(opt);
            });
            /* Default: Asia/Kolkata (UID 181) */
            sel.value = '181';
            _syncObNextBtn();
        }

        if (!UpstashService.isEnabled()) return;
        UpstashService.get(UpstashService.globalKey('loc-timezone-all')).then(function (cached) {
            if (Array.isArray(cached) && cached.length > 0) {
                _renderTimezones(cached);
            } else {
                fetch('/onboarding/getTimezones', { method: 'GET' })
                    .then(function (r) { return r.json(); })
                    .then(function (resp) {
                        if (!resp.Error && Array.isArray(resp.Data) && resp.Data.length > 0) {
                            UpstashService.set(UpstashService.globalKey('loc-timezone-all'), resp.Data);
                            _renderTimezones(resp.Data);
                        }
                    })
                    .catch(function () {});
            }
        });
    }());

    /* ── State / Timezone change — re-evaluate Next button ─────────── */
    document.getElementById('obState').addEventListener('change', function () {
        _clearErr('obState');
        _syncObNextBtn();
    });
    document.getElementById('obTimezone').addEventListener('change', function () {
        _clearErr('obTimezone');
        _syncObNextBtn();
    });

    /* ── Org Name → Brand Name mirror on blur (only while Brand Name is empty) ── */
    document.getElementById('obOrgName').addEventListener('input', function () {
        var brandInput = document.getElementById('obBrandName');
        if (!brandInput.value.trim()) {
            brandInput.value = this.value.trim();
        }
        _clearErr('obOrgName');
        _syncObNextBtn();
    });

    document.getElementById('obBrandName').addEventListener('input', function () {
        _clearErr('obBrandName');
        _syncObNextBtn();
    });

    /* ── Short code auto-uppercase ──────────────────────────────── */
    document.getElementById('obShortCode').addEventListener('input', function () {
        var pos = this.selectionStart;
        this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        this.setSelectionRange(pos, pos);
        _clearErr('obShortCode');
        _syncObNextBtn();
    });

    /* ── GSTIN auto-uppercase + availability check ──────────────────── */
    document.getElementById('obGSTIN').addEventListener('input', function () {
        var pos = this.selectionStart;
        this.value = this.value.toUpperCase();
        this.setSelectionRange(pos, pos);
        _gstinOk       = false;
        _gstinTaken    = false;
        _gstinChecking = false;
        document.getElementById('obGSTINOk').style.display = 'none';
        _clearErr('obGSTIN');
        _syncObNextBtn();
    });

    document.getElementById('obGSTIN').addEventListener('blur', function () {
        var gstin = this.value.trim().toUpperCase();
        if (!gstin) {
            _gstinOk = false; _gstinTaken = false; _gstinChecking = false;
            document.getElementById('obGSTINOk').style.display = 'none';
            _clearErr('obGSTIN');
            _syncObNextBtn();
            return;
        }
        if (!/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/.test(gstin)) {
            _gstinOk = false;
            _showErr('obGSTIN', 'Invalid GSTIN format (e.g. 22AAAAA0000A1Z5).');
            _syncObNextBtn();
            return;
        }
        _gstinChecking = true;
        _syncObNextBtn();
        fetch('/signup/checkGSTIN', {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    'gstin=' + encodeURIComponent(gstin),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            _gstinChecking = false;
            if (data.available) {
                _gstinOk     = true;
                _gstinTaken  = false;
                document.getElementById('obGSTINOk').style.display = '';
                _clearErr('obGSTIN');
            } else {
                _gstinOk    = false;
                _gstinTaken = true;
                document.getElementById('obGSTINOk').style.display = 'none';
                _showErr('obGSTIN', 'This GSTIN is already registered with another account.');
            }
            _syncObNextBtn();
        })
        .catch(function () { _gstinChecking = false; _syncObNextBtn(); });
    });

    /* ── Mobile: digits-only + availability check ───────────────────── */
    var _mobileTaken    = false;
    var _mobileOk       = false;
    var _mobileChecking = false;

    /* ── GSTIN: availability check ──────────────────────────────────── */
    var _gstinOk       = false;
    var _gstinTaken    = false;
    var _gstinChecking = false;

    document.getElementById('obMobile').addEventListener('input', function () {
        var clean = this.value.replace(/\D/g, '');
        if (this.value !== clean) this.value = clean;
        _mobileTaken    = false;
        _mobileOk       = false;
        _mobileChecking = false;
        document.getElementById('obMobileOk').style.display = 'none';
        _clearErr('obMobile');
        _syncObNextBtn();
    });

    document.getElementById('obMobile').addEventListener('blur', function () {
        var mobile = this.value.replace(/\D/g, '');
        if (mobile.length !== 10) return;
        _mobileChecking = true;
        _syncObNextBtn();
        fetch('/signup/checkMobile', {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    'mobile=' + encodeURIComponent(mobile),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            _mobileChecking = false;
            if (data.available) {
                _mobileOk    = true;
                _mobileTaken = false;
                document.getElementById('obMobileOk').style.display = '';
                _clearErr('obMobile');
            } else {
                _mobileOk    = false;
                _mobileTaken = true;
                document.getElementById('obMobileOk').style.display = 'none';
                _showErr('obMobile', 'This mobile number is already registered.');
            }
            _syncObNextBtn();
        })
        .catch(function () { _mobileChecking = false; _syncObNextBtn(); });
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

    /**
     * Enable "Next" only when all required fields pass.
     * GSTIN is optional — ignored when empty; blocks when filled but not yet validated.
     * @returns {void}
     */
    function _syncObNextBtn() {
        var btn = document.getElementById('obNextBtn');
        if (!btn) return;

        var orgName   = document.getElementById('obOrgName').value.trim();
        var brandName = document.getElementById('obBrandName').value.trim();
        var shortCode = document.getElementById('obShortCode').value.trim().toUpperCase();
        var mobile    = document.getElementById('obMobile').value.replace(/\D/g, '');
        var state     = document.getElementById('obState').value;
        var timezone  = document.getElementById('obTimezone').value;
        var gstin     = document.getElementById('obGSTIN').value.trim();

        var allOk =
            orgName.length >= 2 &&
            brandName.length >= 2 &&
            /^[A-Z0-9]{3}$/.test(shortCode) &&
            mobile.length === 10 && _mobileOk && !_mobileChecking &&
            state !== '' &&
            timezone !== '' &&
            (gstin === '' || (_gstinOk && !_gstinChecking));

        btn.disabled = !allOk;
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

    /* ── Two-step state ─────────────────────────────────────────── */
    var _hasAddressStep = false;

    /* ── City loader (Upstash cache-first) ──────────────────────── */
    /**
     * @param {Array} cities
     * @returns {void}
     */
    function _obPopulateCities(cities) {
        var sel = document.getElementById('obAddrCity');
        sel.innerHTML = '<option value="">Select city…</option>';
        if (Array.isArray(cities)) {
            cities.forEach(function (c) {
                var name = c.name || '';
                if (!name) return;
                var opt = document.createElement('option');
                opt.value = name; opt.textContent = name;
                sel.appendChild(opt);
            });
        }
        sel.disabled = false;
    }

    /**
     * @param {string} stateISO2
     * @param {string} cacheKey
     * @param {string} cacheField
     * @returns {void}
     */
    function _obFetchCitiesFromServer(stateISO2, cacheKey, cacheField) {
        fetch('/signup/getCitiesOfState', {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    'CountryISO2=IN&StateISO2=' + encodeURIComponent(stateISO2),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            var cities = (!data.Error && Array.isArray(data.Data)) ? data.Data : [];
            if (cities.length > 0 && typeof UpstashService !== 'undefined' && UpstashService.isEnabled()) {
                UpstashService.hset(cacheKey, cacheField, cities);
            }
            _obPopulateCities(cities);
        })
        .catch(function () {
            var sel = document.getElementById('obAddrCity');
            if (sel) { sel.innerHTML = '<option value="">Could not load cities</option>'; sel.disabled = false; }
        });
    }

    /**
     * @returns {void}
     */
    function _obLoadCities() {
        var stateSel  = document.getElementById('obState');
        var sel       = document.getElementById('obAddrCity');
        var selOpt    = stateSel.options[stateSel.selectedIndex];
        var stateISO2 = selOpt ? (selOpt.dataset.iso2 || '') : '';

        if (!stateISO2) {
            sel.innerHTML = '<option value="">— Select a state first —</option>';
            return;
        }

        sel.innerHTML = '<option value="">Loading cities…</option>';
        sel.disabled  = true;

        if (typeof UpstashService !== 'undefined' && UpstashService.isEnabled()) {
            var cacheKey   = UpstashService.globalKey('loc-cities-by-state');
            var cacheField = 'in-' + stateISO2.toLowerCase();
            UpstashService.hget(cacheKey, cacheField)
                .then(function (cached) {
                    if (Array.isArray(cached) && cached.length > 0) {
                        _obPopulateCities(cached);
                    } else {
                        _obFetchCitiesFromServer(stateISO2, cacheKey, cacheField);
                    }
                })
                .catch(function () { _obFetchCitiesFromServer(stateISO2, UpstashService.globalKey('loc-cities-by-state'), 'in-' + stateISO2.toLowerCase()); });
        } else {
            _obFetchCitiesFromServer(stateISO2, '', '');
        }
    }

    /* ── Step 1 → Next ──────────────────────────────────────────── */
    /**
     * @returns {void}
     */
    window.obNextStep = function () {
        if (!_validate()) return;
        var gstin = document.getElementById('obGSTIN').value.trim();
        _hasAddressStep = !gstin;

        if (_hasAddressStep) {
            /* Show address step in progress */
            document.getElementById('obStepAddr').style.display = '';
            document.getElementById('obStepPlanNum').textContent = '5';
            /* Switch forms */
            document.getElementById('obForm').style.display     = 'none';
            document.getElementById('obAddrForm').style.display = '';
            /* Mark progress */
            document.getElementById('obStepOrg').classList.remove('active');
            document.getElementById('obStepOrg').classList.add('done');
            document.getElementById('obStepAddr').classList.add('active');
            /* Load cities */
            _obLoadCities();
        } else {
            _obDoSubmit();
        }
    };

    /* ── Step 2 → Back ──────────────────────────────────────────── */
    /**
     * @returns {void}
     */
    window.obPrevStep = function () {
        document.getElementById('obAddrForm').style.display = 'none';
        document.getElementById('obForm').style.display     = '';
        document.getElementById('obStepAddr').classList.remove('active');
        document.getElementById('obStepOrg').classList.remove('done');
        document.getElementById('obStepOrg').classList.add('active');
    };

    /* ── Step 2 validate address ─────────────────────────────────── */
    /**
     * @returns {boolean}
     */
    function _validateAddr() {
        var ok = true;
        _hideAlert();

        var line1 = document.getElementById('obAddrLine1').value.trim();
        _clearErr('obAddrLine1');
        if (!line1) { _showErr('obAddrLine1', 'Address line 1 is required.'); ok = false; }

        var city = document.getElementById('obAddrCity').value;
        _clearErr('obAddrCity');
        if (!city) { _showErr('obAddrCity', 'Please select a city.'); ok = false; }

        var pin = document.getElementById('obAddrPincode').value.replace(/\D/g, '');
        _clearErr('obAddrPincode');
        if (!pin || pin.length !== 6) { _showErr('obAddrPincode', 'Enter a valid 6-digit pincode.'); ok = false; }

        return ok;
    }

    /* ── Step 2 → Submit ────────────────────────────────────────── */
    /**
     * @returns {void}
     */
    window.obSubmitFinal = function () {
        if (!_validateAddr()) return;
        _obDoSubmit(true);
    };

    /* ── Core submit ────────────────────────────────────────────── */
    /**
     * @param {boolean} [withAddr]
     * @returns {void}
     */
    function _obDoSubmit(withAddr) {
        var btn = withAddr
            ? document.getElementById('obAddrSubmitBtn')
            : document.getElementById('obNextBtn');
        btn.disabled = true;
        showUIBlock('Setting up your organisation…');

        var params = {
            org_name:     document.getElementById('obOrgName').value.trim(),
            brand_name:   document.getElementById('obBrandName').value.trim(),
            short_code:   document.getElementById('obShortCode').value.trim().toUpperCase(),
            mobile:       document.getElementById('obMobile').value.replace(/\D/g, ''),
            state:        document.getElementById('obState').value,
            timezone_uid: document.getElementById('obTimezone').value,
            gstin:        document.getElementById('obGSTIN').value.trim().toUpperCase(),
        };

        if (withAddr) {
            params.addr_line1   = document.getElementById('obAddrLine1').value.trim();
            params.addr_line2   = document.getElementById('obAddrLine2').value.trim();
            params.addr_city    = document.getElementById('obAddrCity').value;
            params.addr_pincode = document.getElementById('obAddrPincode').value.replace(/\D/g, '');
        }

        fetch('/onboarding/complete', {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    new URLSearchParams(params).toString(),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.Error) {
                hideUIBlock();
                btn.disabled = false;
                _showAlert(data.Message || 'Something went wrong. Please try again.');
            } else {
                document.getElementById('obForm').style.display     = 'none';
                document.getElementById('obAddrForm').style.display = 'none';
                document.querySelector('.ob-progress').style.display = 'none';
                document.getElementById('obSuccess').classList.add('show');
                setTimeout(function () { window.location.href = global_base_url + 'subscribe'; }, 1800);
            }
        })
        .catch(function () {
            hideUIBlock();
            btn.disabled = false;
            _showAlert('A network error occurred. Please try again.');
        });
    }

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
