<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $_homeUrl = rtrim(preg_replace('#/app/?$#', '', rtrim(base_url(), '/')), '/') . '/'; ?>

<?php $this->load->view('login/header'); ?>

<link rel="stylesheet" href="/assets/css/login.css">

<?php if (!empty($TwoStepEnabled) && !empty($this->session->flashdata('danger'))): ?>
<script>/* Runs before body paints — prevents Step 1 flash on wrong-password reload */
(function(){try{if(sessionStorage.getItem('lr_step2_user'))document.documentElement.classList.add('lr-step2-active');}catch(_){}}());
</script>
<?php endif; ?>

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
        <div class="lr-agri-scene">
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

        <!-- Home pill — top left -->
        <a href="<?php echo $_homeUrl; ?>" class="lr-home-link" title="Back to homepage">
            <i class="bx bx-arrow-back"></i>
            Home
        </a>

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
                        <a href="<?php echo $_homeUrl; ?>" class="lr-head-logo-link" title="Back to homepage">
                            <img class="lr-form-head-logo" src="<?php echo htmlspecialchars($OrgLogo); ?>" alt="<?php echo getSiteConfiguration()->ShortName; ?>">
                        </a>
                        <?php endif; ?>
                        <h3>Sign in</h3>
                    </div>
                    <p>Enter your credentials to continue</p>
                </div>

                <div id="lrStep1Error" class="lr-alerts">
                    <div class="alert">
                        <span id="lrStep1ErrorMsg"></span>
                        <div id="lrStep1ErrorSub" class="lr-error-sub"></div>
                        <div id="lrResendWrap" class="lr-resend-wrap">
                            <span class="lr-resend-hint">Didn't receive it?</span>
                            <button type="button" id="lrResendBtn" class="lr-resend-btn">Resend Email</button>
                        </div>
                        <div id="lrRenewWrap">
                            <a id="lrRenewBtn" href="#" class="lr-renew-btn">
                                <i class="bx bx-refresh"></i> Renew Subscription
                            </a>
                        </div>
                    </div>
                </div>

                <div class="lr-field">
                    <label for="UserName">Username (or) Email</label>
                    <div class="lr-input-wrap">
                        <input type="text" id="UserName" name="UserName" placeholder="Enter your username"
                               autocomplete="username" />
                        <i class="bx bx-user lr-input-icon"></i>
                    </div>
                </div>

                <button type="button" class="lr-btn" id="lrContinueBtn">
                    <span>
                        <i class="bx bx-right-arrow-circle"></i>
                        Continue
                    </span>
                </button>

                <!-- Social sign-in -->
                <div class="lr-social-divider"><span>or continue with</span></div>
                <div class="lr-social-btns">
                    <a href="<?= rtrim(getenv('SERVICES_PUBLIC_URL') ?: getenv('SERVICES_BASE_URL') ?: '', '/') . '/api/auth/google' ?>" class="lr-social-btn lr-social-google">
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
            <div id="lrStep2Panel">

                <div id="lrStep2AlertWrap" class="lr-alerts">
                    <?php $this->load->view('login/alerts'); ?>
                </div>

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

                <div class="lr-bottom-row lr-bottom-row--end">
                    <a href="<?= site_url('forgot-password') ?>" class="lr-forgot">Forgot password?</a>
                </div>

                <button type="submit" class="lr-btn" id="lrSubmit">
                    <span>
                        <i class="bx bx-log-in-circle"></i>
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
                    <a href="<?php echo $_homeUrl; ?>" class="lr-head-logo-link" title="Back to homepage">
                        <img class="lr-form-head-logo" src="<?php echo htmlspecialchars($OrgLogo); ?>" alt="<?php echo getSiteConfiguration()->ShortName; ?>">
                    </a>
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

            <div class="lr-bottom-row lr-bottom-row--end">
                <a href="<?= site_url('forgot-password') ?>" class="lr-forgot">Forgot password?</a>
            </div>

            <button type="submit" class="lr-btn" id="lrSubmit">
                <span>
                    <i class="bx bx-log-in-circle"></i>
                    Sign In
                </span>
            </button>

            <?php echo form_close(); ?>

            <!-- Social sign-in -->
            <div class="lr-social-divider">
                <span>or continue with</span>
            </div>

            <div class="lr-social-btns">
                <a href="<?= rtrim(getenv('SERVICES_PUBLIC_URL') ?: getenv('SERVICES_BASE_URL') ?: '', '/') . '/api/auth/google' ?>" class="lr-social-btn lr-social-google">
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
                New here? <a href="<?= site_url('signup') ?>">Create your account</a>
            </p>

            <p class="lr-footer-note">&copy; <?php echo date('Y'); ?> <span><?php echo getSiteConfiguration()->ShortName; ?></span>. All rights reserved.</p>
        </div>
    </div>

</div>


<?php $this->load->view('login/footer'); ?>

<script>
(function ($) {
    var twoStep      = <?php echo !empty($TwoStepEnabled) ? 'true' : 'false'; ?>;
    var _servicesUrl = '<?php echo rtrim(getenv('SERVICES_BASE_URL') ?: '', '/') . '/'; ?>';

    /* Hide template customizer */
    $(function () {
        $('#template-customizer').addClass('d-none');
    });

    /* ── Shared: password toggle ────────────────────────────────────────────
     * @returns {void}
     */
    function initPwToggle() {
        var $pwToggle = $('#pwToggle');
        var $pwInput  = $('#UserPassword');
        var $pwIcon   = $('#pwIcon');
        if (!$pwToggle.length) return;
        $pwToggle.on('click', function () {
            if ($pwInput.attr('type') === 'password') {
                $pwInput.attr('type', 'text');
                $pwIcon.attr('class', 'bx bx-show');
            } else {
                $pwInput.attr('type', 'password');
                $pwIcon.attr('class', 'bx bx-hide');
            }
        });
    }

    /* ── Shared: ripple effect
     * @param {jQuery} $btn
     * @returns {void}
     */
    function addRipple($btn) {
        if (!$btn || !$btn.length) return;
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
    }

    /* ── Shared: spinner HTML ───────────────────────────────────────────── */
    var _spinnerHtml =
        '<span>' +
            '<svg class="lr-spin-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">' +
                '<path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>' +
            '</svg>' +
            '{label}' +
        '</span>';

    /* @param {jQuery|Element} btn  @param {string} label  @returns {void} */
    function spinBtn(btn, label) {
        $(btn).prop('disabled', true)
              .html(_spinnerHtml.replace('{label}', label))
              .addClass('lr-btn--loading');
    }

    /* @param {jQuery|Element} btn  @param {string} iconClass  @param {string} label  @returns {void} */
    function resetBtn(btn, iconClass, label) {
        $(btn).prop('disabled', false)
              .html('<span><i class="bx ' + iconClass + '"></i> ' + label + '</span>')
              .removeClass('lr-btn--loading');
    }

    /* ── Shared: disable/enable social buttons and signup link
     * @param {boolean} disabled
     * @returns {void}
     */
    function setSocialDisabled(disabled) {
        $('.lr-social-btn').css({'pointer-events': disabled ? 'none' : '', 'opacity': disabled ? '0.4' : ''});
        $('.lr-signup-note a').css({'pointer-events': disabled ? 'none' : '', 'opacity': disabled ? '0.4' : ''});
        $('.lr-forgot').css({'pointer-events': disabled ? 'none' : '', 'opacity': disabled ? '0.4' : ''});
    }

    if (twoStep) {
        // ── TWO-STEP FLOW ────────────────────────────────────────────────────

        var $csrfInput = $('#doLoginForm input[name^="csrf"]');
        if (!$csrfInput.length) $csrfInput = $('#lrStep1Panel input[name^="csrf"]');
        var csrfName   = $csrfInput.length ? $csrfInput.attr('name') : '';
        var csrfVal    = $csrfInput.length ? $csrfInput.val()        : '';

        var $step1Panel    = $('#lrStep1Panel');
        var $step2Panel    = $('#lrStep2Panel');
        var $continueBtn   = $('#lrContinueBtn');
        var $step1Error    = $('#lrStep1Error');
        var $welcomeName   = $('#lrWelcomeName');
        var $step2Name     = $('#step2UserName');
        var $notYouLink    = $('#lrNotYou');
        var $submitBtn     = $('#lrSubmit');
        var $loginForm     = $('#doLoginForm');
        var $unameInput    = $('#UserName');
        var $resendWrap    = $('#lrResendWrap');
        var $resendBtn     = $('#lrResendBtn');
        var $step1ErrorMsg = $('#lrStep1ErrorMsg');
        var $step1ErrorSub = $('#lrStep1ErrorSub');

        /* ── Toast
         * @param {string} msg  @param {boolean} isError  @returns {void}
         */
        var _lrToastTimer = null;
        function lrToast(msg, isError) {
            var $toast = $('#lrToast');
            if (!$toast.length) {
                $toast = $('<div>').attr('id', 'lrToast').addClass('lr-toast');
                $('body').append($toast);
            }
            clearTimeout(_lrToastTimer);
            $toast.text(msg).attr('class', 'lr-toast' + (isError ? ' lr-toast--error' : ''));
            requestAnimationFrame(function () {
                requestAnimationFrame(function () { $toast.addClass('lr-toast--show'); });
            });
            _lrToastTimer = setTimeout(function () {
                $toast.removeClass('lr-toast--show');
            }, 4000);
        }

        /* ── Resend verification ─────────────────────────────────────────── */
        var _pendingOrgEmail = '';

        /* @param {boolean} disabled  @returns {void} */
        function setStep1FormDisabled(disabled) {
            $unameInput.prop('disabled', disabled).css('opacity', disabled ? '0.5' : '');
            $continueBtn.prop('disabled', disabled).css({'opacity': disabled ? '0.6' : '', 'cursor': disabled ? 'not-allowed' : ''});
            setSocialDisabled(disabled);
            $resendBtn.prop('disabled', disabled);
        }

        /* @returns {void} */
        function doResendVerification() {
            if (!_pendingOrgEmail) return;
            setStep1FormDisabled(true);
            $resendBtn.text('Sending…');
            var emailToSend = _pendingOrgEmail;
            $.ajax({
                url        : _servicesUrl + 'api/auth/email/resend-verification',
                method     : 'POST',
                contentType: 'application/json',
                data       : JSON.stringify({ identifier: emailToSend }),
                dataType   : 'json'
            }).done(function () {
                _pendingOrgEmail = '';
                setStep1FormDisabled(false);
                clearStep1Error();
                lrToast('Verification email sent! Please check your inbox.', false);
            }).fail(function (xhr) {
                setStep1FormDisabled(false);
                $resendBtn.text('Resend Email');
                var err = xhr.responseJSON;
                var msg = err && err.message ? (Array.isArray(err.message) ? err.message.join(', ') : err.message) : 'Network error. Please try again.';
                lrToast(msg, true);
            });
        }

        $resendBtn.on('click', doResendVerification);

        /* ── Initialise from OAuth redirect (Google / Facebook) ─────────── */
        <?php
            /* Flash data = CI3 OAuth; ?error= query param = NestJS OAuth failure */
            $oauthFlashMsg   = $this->session->flashdata('danger') ?: trim($this->input->get('error') ?? '');
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

        /* Clear error when user edits the identifier */
        $unameInput.on('input', function () {
            if (_pendingOrgEmail) {
                _pendingOrgEmail = '';
                clearStep1Error();
            }
        });

        /* @param {string} msg  @param {boolean} showResend  @param {string} [subMsg]  @returns {void} */
        function showStep1Error(msg, showResend, subMsg) {
            $step1ErrorMsg.text(msg);
            if (subMsg) {
                $step1ErrorSub.text(subMsg).show();
            } else {
                $step1ErrorSub.text('').hide();
            }
            $step1Error.show();
            $resendWrap.css('display', showResend ? 'flex' : 'none');
            if (showResend) $resendBtn.prop('disabled', false).text('Resend Email');
        }

        /* @returns {void} */
        function clearStep1Error() {
            $step1Error.hide();
            $resendWrap.hide();
            $step1ErrorSub.text('').hide();
            $('#lrRenewWrap').hide();
            _pendingOrgEmail = '';
        }

        /**
         * Render avatar into $el — img with letter-badge fallback on error.
         * @param {jQuery} $el
         * @param {string} letter    — single character from NestJS avatarLetter
         * @param {string} imageUrl  — may be empty/null
         * @returns {void}
         */
        function setAvatar($el, letter, imageUrl) {
            var $letter = $('<span>').addClass('lr-user-letter').text(letter || '?');
            if (imageUrl) {
                var $img = $('<img>').attr('alt', '').addClass('lr-avatar-img');
                $img.on('error', function () { $el.html($letter); });
                $img.attr('src', imageUrl);
                $el.html($img);
            } else {
                $el.html($letter);
            }
        }

        /* @param {string} displayName  @param {string} username  @param {string} imageUrl  @param {string} avatarLetter  @returns {void} */
        function goToStep2(displayName, username, imageUrl, avatarLetter) {
            $welcomeName.text(displayName);
            $step2Name.val(username);

            /* Persist so wrong-password page reload can restore step 2 */
            try {
                sessionStorage.setItem('lr_step2_user', username);
                sessionStorage.setItem('lr_step2_name', displayName);
                sessionStorage.setItem('lr_step2_image', imageUrl || '');
                sessionStorage.setItem('lr_step2_letter', avatarLetter || '');
            } catch (_) {}

            var $avatar = $('#lrUserCard .lr-user-avatar');
            if ($avatar.length) {
                setAvatar($avatar, avatarLetter || displayName.charAt(0).toUpperCase(), imageUrl);
            }

            $step1Panel.css('animation', 'none').hide();
            $step2Panel.css('animation', '').show();
            setTimeout(function () { $('#UserPassword').trigger('focus'); }, 100);
        }

        /* @returns {void} */
        function goToStep1() {
            $.post('/login/clear-pending');
            try { sessionStorage.removeItem('lr_step2_user'); sessionStorage.removeItem('lr_step2_name'); sessionStorage.removeItem('lr_step2_image'); sessionStorage.removeItem('lr_step2_letter'); } catch (_) {}
            $step2Panel.hide();
            $step1Panel.show();
            clearStep1Error();
            $unameInput.trigger('focus');
        }

        /* @returns {void} */
        function doValidate() {
            var username = $unameInput.val().trim();
            if (!username) { showStep1Error('Please enter your username or email.', false); return; }
            $unameInput.trigger('blur');
            clearStep1Error();
            spinBtn($continueBtn, 'Checking...');
            setSocialDisabled(true);

            // Services API: { identifier } in; { username, displayName, imageUrl } back on success,
            // HTTP 4xx { message, needsEmailVerification, orgEmail } on a refused account
            $.ajax({
                url        : _servicesUrl + 'api/auth/login/identify',
                method     : 'POST',
                contentType: 'application/json',
                data       : JSON.stringify({ identifier: username }),
                dataType   : 'json'
            }).done(function (resp) {
                resetBtn($continueBtn, 'bx-right-arrow-circle', 'Continue');
                setSocialDisabled(false);
                goToStep2(resp.displayName || resp.username, resp.username || username, resp.imageUrl || '', resp.avatarLetter || '');
            }).fail(function (xhr) {
                resetBtn($continueBtn, 'bx-right-arrow-circle', 'Continue');
                setSocialDisabled(false);
                var err = xhr.responseJSON;
                if (!err) { showStep1Error('Connection failed. Please try again.', false); return; }
                var msg = Array.isArray(err.message) ? err.message.join(', ') : err.message;
                if (err.needsEmailVerification) _pendingOrgEmail = err.orgEmail || '';
                showStep1Error(msg || 'Something went wrong. Please try again.', !!err.needsEmailVerification);
            });
        }

        addRipple($continueBtn);
        $continueBtn.on('click', doValidate);
        $unameInput.on('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); doValidate(); }
        });
        $notYouLink.on('click', goToStep1);
        if ($loginForm.length) {
            addRipple($submitBtn);
            $loginForm.on('submit', function () {
                spinBtn($submitBtn, 'Signing in...');
                setSocialDisabled(true);
            });
        }

        /* Blur password on Enter, then submit — prevent default to avoid double-submit */
        $('#UserPassword').on('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                $(this).trigger('blur');
                $(this).closest('form').trigger('submit');
            }
        });

        /* On wrong-password page reload: restore step 2 using sessionStorage */
        (function () {
            if (!$('#lrStep2AlertWrap .alert-danger').length) return;
            var pendUser = '', pendName = '';
            try { pendUser = sessionStorage.getItem('lr_step2_user') || ''; pendName = sessionStorage.getItem('lr_step2_name') || ''; } catch (_) {}
            if (!pendUser) return;
            var pendImage = '', pendLetter = '';
            try {
                pendImage  = sessionStorage.getItem('lr_step2_image')  || '';
                pendLetter = sessionStorage.getItem('lr_step2_letter') || '';
            } catch (_) {}
            $welcomeName.text(pendName || pendUser);
            $step2Name.val(pendUser);
            setAvatar($('#lrUserCard .lr-user-avatar'), pendLetter || (pendName || pendUser).charAt(0).toUpperCase(), pendImage);
            $step1Panel.css('animation', 'none').hide();
            $step2Panel.css('animation', '').show();
            setTimeout(function () { $('#UserPassword').trigger('focus'); }, 150);
        }());

        /* Auto-focus username on initial load (step 1 visible) */
        if ($unameInput.length && $step1Panel.css('display') !== 'none') {
            setTimeout(function () { $unameInput.trigger('focus'); }, 400);
        }

        initPwToggle();

    } else {
        // ── SINGLE-STEP FLOW ─────────────────────────────────────────────────

        var $lrBtn     = $('#lrSubmit');
        var $loginForm = $('#doLoginForm');

        addRipple($lrBtn);

        if ($loginForm.length) {
            $loginForm.on('submit', function () {
                spinBtn($lrBtn, 'Signing in...');
                setSocialDisabled(true);
            });
        }

        /* Re-enable on back/bfcache */
        $(window).on('pageshow', function (e) {
            if (e.originalEvent.persisted) {
                resetBtn($lrBtn, 'bx-log-in-circle', 'Sign In');
                $('.lr-social-btn').css({'pointer-events': '', 'opacity': ''});
            }
        });

        /* Blur password on Enter, then submit — prevent default to avoid double-submit */
        $('#UserPassword').on('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                $(this).trigger('blur');
                $(this).closest('form').trigger('submit');
            }
        });

        /* Wrong password: danger alert present on load — focus the password field */
        if ($('.lr-alerts .alert-danger').length) {
            setTimeout(function () { $('#UserPassword').trigger('focus'); }, 300);
        } else {
            /* Auto-focus username only when no error is shown */
            setTimeout(function () { $('#UserName').trigger('focus'); }, 400);
        }

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
     * @param {string} lang - 'en' | 'ta'
     * @returns {void}
     */
    function applyI18n(lang) {
        var s = _lrStrings[lang] || _lrStrings.en;

        $('.lr-form-head h3').text(s.heading);
        $('.lr-form-head p').text(s.sub);
        $('label[for="UserName"]').text(s.labelUser);
        $('label[for="UserPassword"]').text(s.labelPass);
        $('.lr-forgot').text(s.forgot);
        $('.lr-not-you').text(s.notYou);
        $('.lr-social-divider span').text(s.divider);
        $('#UserName').prop('placeholder', s.phUser);
        $('#UserPassword').prop('placeholder', s.phPass);

        /* Continue button — update trailing text node, leave icon intact */
        var $contBtn = $('#lrContinueBtn');
        if ($contBtn.length) {
            var tn = _lastTextNode($contBtn.find('span')[0]);
            if (tn) tn.textContent = ' ' + s.btnContinue;
        }

        /* Sign In button(s) — update trailing text node, leave icon intact */
        $('button.lr-btn[type="submit"] > span').each(function () {
            var tn = _lastTextNode(this);
            if (tn) tn.textContent = ' ' + s.btnSignin;
        });

        /* Google button(s) — update trailing text node, leave SVG intact */
        $('.lr-social-google').each(function () {
            var tn = _lastTextNode(this);
            if (tn) tn.textContent = ' ' + s.btnGoogle + ' ';
        });

        /* Footer signup note */
        var $signupPara = $('.lr-signup-note');
        if ($signupPara.length) {
            var firstTn = $signupPara[0].firstChild;
            while (firstTn && firstTn.nodeType !== 3) firstTn = firstTn.nextSibling;
            if (firstTn) firstTn.textContent = ' ' + s.signupPre + ' ';
            $signupPara.find('a').text(s.signupLink);
        }
    }

    // ── Language switcher ────────────────────────────────────────────────────
    (function () {
        var $switchEl  = $('#lrLangSwitch');
        var $triggerEl = $('#lrLangTrigger');
        var $labelEl   = $('#lrLangLabel');
        var $opts      = $('.lr-lang-opt');
        if (!$switchEl.length || !$triggerEl.length) return;

        $triggerEl.on('click', function (e) {
            e.stopPropagation();
            $switchEl.toggleClass('open');
        });

        $opts.on('click', function () {
            var lang = $(this).data('lang');
            $labelEl.text(lang === 'ta' ? 'த' : 'En');
            $opts.removeClass('lr-lang-active');
            $(this).addClass('lr-lang-active');
            $switchEl.removeClass('open');
            applyI18n(lang);
        });

        $(document).on('click', function () {
            $switchEl.removeClass('open');
        });
    }());

}(jQuery));
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