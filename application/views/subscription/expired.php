<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Subscription Expired — <?php echo getSiteConfiguration()->ShortName; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="/images/logo/favicon_io/android-chrome-512x512-1.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/vendor/fonts/boxicons.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:        #0f0f12;
            --surface:   #18181f;
            --border:    rgba(255,255,255,0.08);
            --text:      #e2e8f0;
            --muted:     #64748b;
            --amber:     #f59e0b;
            --amber-dim: rgba(245,158,11,0.12);
            --green:     #22c55e;
            --green-dim: rgba(34,197,94,0.10);
            --blue:      #3b82f6;
            --blue-dim:  rgba(59,130,246,0.10);
            --purple:    #a855f7;
            --purple-dim:rgba(168,85,247,0.10);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .se-wrap {
            width: 100%;
            max-width: 860px;
            display: flex;
            flex-direction: column;
            gap: 40px;
        }

        /* ── Header ─────────────────────────────────────────────────── */
        .se-header {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .se-logo {
            width: 44px;
            height: 44px;
            object-fit: contain;
        }
        .se-brand-name {
            font-size: 18px;
            font-weight: 600;
            color: var(--text);
            letter-spacing: -0.02em;
        }

        /* ── Hero ───────────────────────────────────────────────────── */
        .se-hero {
            text-align: center;
            padding: 0 16px;
        }
        .se-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(239,68,68,0.12);
            color: #f87171;
            border: 1px solid rgba(239,68,68,0.2);
            border-radius: 100px;
            font-size: 12px;
            font-weight: 600;
            padding: 5px 12px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            margin-bottom: 20px;
        }
        .se-badge-dot {
            width: 6px;
            height: 6px;
            background: #ef4444;
            border-radius: 50%;
            animation: pulse 1.8s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50%       { opacity: 0.4; transform: scale(0.7); }
        }
        .se-h1 {
            font-size: clamp(26px, 4vw, 38px);
            font-weight: 700;
            letter-spacing: -0.03em;
            line-height: 1.2;
            margin-bottom: 14px;
        }
        .se-h1 span { color: var(--amber); }
        .se-sub {
            font-size: 15px;
            color: var(--muted);
            max-width: 500px;
            margin: 0 auto 28px;
            line-height: 1.6;
        }

        /* ── Plan cards ─────────────────────────────────────────────── */
        .se-plans {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
        }
        .se-plan {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            position: relative;
            transition: border-color 0.2s, transform 0.2s;
        }
        .se-plan:hover {
            transform: translateY(-3px);
        }
        .se-plan.se-popular {
            border-color: var(--amber);
            box-shadow: 0 0 0 1px var(--amber), 0 8px 32px rgba(245,158,11,0.12);
        }
        .se-popular-tag {
            position: absolute;
            top: -11px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--amber);
            color: #000;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            padding: 3px 10px;
            border-radius: 100px;
            white-space: nowrap;
        }
        .se-plan-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        .se-plan-icon.green  { background: var(--green-dim);  color: var(--green); }
        .se-plan-icon.amber  { background: var(--amber-dim);  color: var(--amber); }
        .se-plan-icon.blue   { background: var(--blue-dim);   color: var(--blue); }
        .se-plan-icon.purple { background: var(--purple-dim); color: var(--purple); }

        .se-plan-name  { font-size: 14px; font-weight: 600; color: var(--text); }
        .se-plan-cycle { font-size: 11px; color: var(--muted); font-weight: 500; }

        .se-plan-price {
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.04em;
            color: var(--text);
        }
        .se-plan-price sup {
            font-size: 14px;
            font-weight: 600;
            vertical-align: super;
            letter-spacing: 0;
        }
        .se-plan-price span {
            font-size: 12px;
            font-weight: 400;
            color: var(--muted);
            letter-spacing: 0;
        }

        .se-plan-features {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 6px;
            flex: 1;
        }
        .se-plan-features li {
            font-size: 12px;
            color: var(--muted);
            display: flex;
            align-items: center;
            gap: 7px;
        }
        .se-plan-features li .bx {
            color: var(--green);
            font-size: 14px;
            flex-shrink: 0;
        }

        .se-plan-btn {
            display: block;
            width: 100%;
            padding: 10px;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: rgba(255,255,255,0.04);
            color: var(--text);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, border-color 0.2s;
            text-align: center;
            font-family: inherit;
            letter-spacing: 0.01em;
        }
        .se-plan-btn:hover { background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.18); }
        .se-plan-btn.se-btn-amber {
            background: var(--amber);
            border-color: var(--amber);
            color: #000;
        }
        .se-plan-btn.se-btn-amber:hover { background: #f6a823; border-color: #f6a823; }

        /* ── Footer actions ─────────────────────────────────────────── */
        .se-footer {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 14px;
            padding-bottom: 8px;
        }
        .se-contact {
            font-size: 13px;
            color: var(--muted);
        }
        .se-contact a {
            color: var(--amber);
            text-decoration: none;
        }
        .se-contact a:hover { text-decoration: underline; }

        .se-logout-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: transparent;
            border: none;
            color: var(--muted);
            font-size: 13px;
            font-family: inherit;
            cursor: pointer;
            padding: 6px 10px;
            border-radius: 8px;
            transition: color 0.2s, background 0.2s;
        }
        .se-logout-btn:hover { color: var(--text); background: rgba(255,255,255,0.05); }

        @media (max-width: 500px) {
            .se-plans { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="se-wrap">

    <!-- Header -->
    <header class="se-header">
        <img src="/images/logo/r2k_logo.png" alt="Logo" class="se-logo" onerror="this.style.display='none'">
        <span class="se-brand-name"><?php echo getSiteConfiguration()->ShortName; ?></span>
    </header>

    <!-- Hero -->
    <div class="se-hero">
        <div class="se-badge">
            <span class="se-badge-dot"></span>
            Subscription Expired
        </div>
        <h1 class="se-h1">Your <span>free trial</span> has ended.</h1>
        <p class="se-sub">
            To continue using all features, choose a plan that works best for your business.
            Your data is safe and ready — just pick a plan to continue.
        </p>
    </div>

    <!-- Plans -->
    <div class="se-plans">

        <div class="se-plan">
            <div class="se-plan-icon green"><i class="bx bx-store"></i></div>
            <div>
                <div class="se-plan-name">Basic</div>
                <div class="se-plan-cycle">Monthly</div>
            </div>
            <div class="se-plan-price"><sup>₹</sup>999 <span>/ mo</span></div>
            <ul class="se-plan-features">
                <li><i class="bx bx-check-circle"></i> Up to 5 users</li>
                <li><i class="bx bx-check-circle"></i> 1 branch</li>
                <li><i class="bx bx-check-circle"></i> Invoicing &amp; purchases</li>
                <li><i class="bx bx-check-circle"></i> Basic reports</li>
            </ul>
            <button class="se-plan-btn" onclick="seContactSales('Basic Monthly')">Get Basic</button>
        </div>

        <div class="se-plan se-popular">
            <span class="se-popular-tag">Most Popular</span>
            <div class="se-plan-icon amber"><i class="bx bx-trending-up"></i></div>
            <div>
                <div class="se-plan-name">Professional</div>
                <div class="se-plan-cycle">Monthly</div>
            </div>
            <div class="se-plan-price"><sup>₹</sup>2,499 <span>/ mo</span></div>
            <ul class="se-plan-features">
                <li><i class="bx bx-check-circle"></i> Up to 20 users</li>
                <li><i class="bx bx-check-circle"></i> Up to 5 branches</li>
                <li><i class="bx bx-check-circle"></i> All modules</li>
                <li><i class="bx bx-check-circle"></i> Advanced reports</li>
                <li><i class="bx bx-check-circle"></i> HRMS &amp; payroll</li>
            </ul>
            <button class="se-plan-btn se-btn-amber" onclick="seContactSales('Professional Monthly')">Get Professional</button>
        </div>

        <div class="se-plan">
            <div class="se-plan-icon blue"><i class="bx bx-buildings"></i></div>
            <div>
                <div class="se-plan-name">Enterprise</div>
                <div class="se-plan-cycle">Monthly</div>
            </div>
            <div class="se-plan-price"><sup>₹</sup>4,999 <span>/ mo</span></div>
            <ul class="se-plan-features">
                <li><i class="bx bx-check-circle"></i> Unlimited users</li>
                <li><i class="bx bx-check-circle"></i> Up to 10 branches</li>
                <li><i class="bx bx-check-circle"></i> All modules</li>
                <li><i class="bx bx-check-circle"></i> Priority support</li>
                <li><i class="bx bx-check-circle"></i> Custom integrations</li>
            </ul>
            <button class="se-plan-btn" onclick="seContactSales('Enterprise Monthly')">Get Enterprise</button>
        </div>

        <div class="se-plan">
            <div class="se-plan-icon purple"><i class="bx bx-calendar-star"></i></div>
            <div>
                <div class="se-plan-name">Professional</div>
                <div class="se-plan-cycle">Yearly — save 17%</div>
            </div>
            <div class="se-plan-price"><sup>₹</sup>24,999 <span>/ yr</span></div>
            <ul class="se-plan-features">
                <li><i class="bx bx-check-circle"></i> Everything in Pro Monthly</li>
                <li><i class="bx bx-check-circle"></i> 2 months free</li>
                <li><i class="bx bx-check-circle"></i> Dedicated account manager</li>
            </ul>
            <button class="se-plan-btn" onclick="seContactSales('Professional Yearly')">Get Yearly Plan</button>
        </div>

    </div>

    <!-- Footer -->
    <div class="se-footer">
        <p class="se-contact">
            Questions? Contact us at
            <a href="mailto:<?php echo getSiteConfiguration()->Email ?? 'support@rishika2k.com'; ?>">
                <?php echo getSiteConfiguration()->Email ?? 'support@rishika2k.com'; ?>
            </a>
            or call <a href="tel:<?php echo getSiteConfiguration()->Mobile ?? ''; ?>"><?php echo getSiteConfiguration()->Mobile ?? ''; ?></a>
        </p>
        <button class="se-logout-btn" onclick="window.location.href='/logout'">
            <i class="bx bx-log-out"></i> Sign out
        </button>
    </div>

</div>

<script>
/**
 * Opens a mailto link with the selected plan pre-filled for easy contact.
 * @param {string} planName - The plan the user clicked
 * @returns {void}
 */
function seContactSales(planName) {
    var supportEmail = '<?php echo addslashes(getSiteConfiguration()->Email ?? 'support@rishika2k.com'); ?>';
    var subject = encodeURIComponent('Subscription Upgrade — ' + planName);
    var body    = encodeURIComponent(
        'Hi,\n\nI would like to upgrade to the ' + planName + ' plan.\n\n' +
        'Organisation: <?php echo addslashes(getSiteConfiguration()->ShortName ?? ''); ?>\n\nPlease get in touch.\n\nThank you.'
    );
    window.location.href = 'mailto:' + supportEmail + '?subject=' + subject + '&body=' + body;
}
</script>

</body>
</html>
