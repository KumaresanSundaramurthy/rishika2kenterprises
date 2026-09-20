<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="layout-wrapper layout-horizontal layout-content-navbar">
    <div class="layout-container">
        <?php $this->load->view('common/menu_view'); ?>
        <div class="layout-page">
            <div class="content-wrapper apex-content">
                <div class="container-xxl flex-grow-1 container-p-y pt-3">
                <?php
                $sub            = $subscription ?? null;
                $currentPlanUID = (int)($sub->SectorPlanUID ?? 0);
                $plans          = $plans ?? [];
                $modCount       = $moduleCountMap ?? [];

                /* Group plans by billing cycle */
                $byBilling = ['Trial' => [], 'Monthly' => [], 'Yearly' => []];
                foreach ($plans as $_p) {
                    $bc = $_p->BillingCycle ?? 'Monthly';
                    $byBilling[$bc][] = $_p;
                }

                /* Build yearly savings map: match BASIC_M → BASIC_Y via PlanCode prefix */
                $yearSavings         = []; /* SectorPlanUID(yearly) → savings% */
                $monthlyPerMonth     = []; /* SectorPlanUID(yearly) → monthly equivalent price */
                $monthlyOriginalPrice = []; /* SectorPlanUID(yearly) → original monthly plan price */
                foreach ($byBilling['Monthly'] as $_mp) {
                    $code = $_mp->PlanCode ?? '';
                    if (substr($code, -2) !== '_M') continue;
                    $base = substr($code, 0, -2);
                    foreach ($byBilling['Yearly'] as $_yp) {
                        $yCode = $_yp->PlanCode ?? '';
                        if (substr($yCode, -2) !== '_Y') continue;
                        if (substr($yCode, 0, -2) !== $base) continue;
                        $annualised = (float)$_mp->Price * 12;
                        $yearPrice  = (float)$_yp->Price;
                        if ($annualised > 0) {
                            $yearSavings[(int)$_yp->SectorPlanUID]        = (int)round(($annualised - $yearPrice) / $annualised * 100);
                            $monthlyPerMonth[(int)$_yp->SectorPlanUID]    = round($yearPrice / 12, 0);
                            $monthlyOriginalPrice[(int)$_yp->SectorPlanUID] = (float)$_mp->Price;
                        }
                    }
                }

                /* Plan tier labels / descriptions keyed by PlanCode prefix */
                $tierMeta = [
                    'TRIAL' => ['label' => 'Free',       'desc' => 'Try all features risk-free'],
                    'BASIC' => ['label' => 'Basic',       'desc' => 'For small teams getting started'],
                    'PRO'   => ['label' => 'Pro',         'desc' => 'For growing businesses'],
                    'ENT'   => ['label' => 'Enterprise',  'desc' => 'Scale without limits'],
                ];
                $getTierMeta = function(string $code) use ($tierMeta): array {
                    foreach ($tierMeta as $prefix => $meta) {
                        if (str_starts_with($code, $prefix)) return $meta;
                    }
                    return ['label' => '', 'desc' => ''];
                };

                /* Per-tier color palette: band, CTA gradient, chip */
                $tierColors = [
                    'TRIAL' => ['band'=>'#22c55e', 'g1'=>'#22c55e', 'g2'=>'#16a34a', 'shadow'=>'rgba(34,197,94,.3)',   'chip'=>'#dcfce7', 'chipTxt'=>'#15803d'],
                    'BASIC' => ['band'=>'#f97316', 'g1'=>'#f97316', 'g2'=>'#ea580c', 'shadow'=>'rgba(249,115,22,.3)', 'chip'=>'#ffedd5', 'chipTxt'=>'#c2410c'],
                    'PRO'   => ['band'=>'#8b5cf6', 'g1'=>'#8b5cf6', 'g2'=>'#7c3aed', 'shadow'=>'rgba(139,92,246,.3)', 'chip'=>'#ede9fe', 'chipTxt'=>'#6d28d9'],
                    'ENT'   => ['band'=>'#1e293b', 'g1'=>'#334155', 'g2'=>'#1e293b', 'shadow'=>'rgba(30,41,59,.3)',   'chip'=>'#f1f5f9', 'chipTxt'=>'#0f172a'],
                ];
                $defaultTierColor = ['band'=>'#696cff','g1'=>'#696cff','g2'=>'#5254cc','shadow'=>'rgba(105,108,255,.3)','chip'=>'#ede9fe','chipTxt'=>'#4338ca'];
                $getTierColor = function(string $code) use ($tierColors, $defaultTierColor): array {
                    foreach ($tierColors as $prefix => $color) {
                        if (str_starts_with($code, $prefix)) return $color;
                    }
                    return $defaultTierColor;
                };

                /* Find max savings for the badge in the toggle */
                $maxSavings = !empty($yearSavings) ? max($yearSavings) : 0;

                /* Determine "popular" plan index in each billing group */
                $getPopularUID = function(array $group): int {
                    $cnt = count($group);
                    if ($cnt < 2) return 0;
                    return (int)$group[(int)floor(($cnt - 1) / 2)]->SectorPlanUID;
                };
                $popularM = $getPopularUID($byBilling['Monthly']);
                $popularY = $getPopularUID($byBilling['Yearly']);

                /* Current subscription billing cycle for default tab */
                $currentBC = $sub->BillingCycle ?? 'Monthly';
                $defaultTab = in_array($currentBC, ['Yearly']) ? 'yearly' : 'monthly';

                /* All plans ordered by price for upgrade/downgrade logic */
                $priceMap = [];
                foreach ($plans as $_p) { $priceMap[(int)$_p->SectorPlanUID] = (float)$_p->Price; }
                $currentPrice = $priceMap[$currentPlanUID] ?? 0;
                ?>

                <style>
                /* ─── Page wrapper ─────────────────────────────────────── */
                .spl-page { padding: 0 0 3rem; }

                /* Bootstrap d-flex overrides [hidden] display:none — fix it */
                [hidden] { display: none !important; }

                /* ─── Current plan card (redesigned) ───────────────────── */
                .spl-hero {
                    background: var(--bs-card-bg,#fff);
                    border-radius: 16px;
                    border: 1.5px solid var(--bs-border-color,#e9e9e9);
                    box-shadow: 0 2px 18px rgba(0,0,0,.07);
                    margin-bottom: 2.5rem;
                    overflow: hidden;
                    position: relative;
                }
                .spl-hero-accent {
                    height: 4px; width: 100%;
                }
                .spl-hero-body {
                    padding: .85rem 1.6rem 1.25rem;
                }
                .spl-hero-nav {
                    display: flex; align-items: center; gap: .4rem;
                    margin-bottom: .9rem;
                    font-size: .78rem;
                }
                .spl-hero-back {
                    display: inline-flex; align-items: center; gap: .2rem;
                    color: var(--bs-secondary-color,#6c757d);
                    text-decoration: none; font-weight: 500;
                    transition: color .15s;
                }
                .spl-hero-back:hover { color: #696cff; }
                .spl-hero-sep { color: var(--bs-border-color,#ccc); }
                .spl-hero-crumb { color: var(--bs-secondary-color,#6c757d); }
                .spl-hero-main {
                    display: flex; flex-wrap: wrap; align-items: center;
                    justify-content: space-between; gap: 1.2rem;
                }
                .spl-hero-left { display: flex; align-items: center; gap: 1rem; }
                .spl-hero-icon {
                    width: 50px; height: 50px; border-radius: 14px;
                    display: flex; align-items: center; justify-content: center;
                    font-size: 1.5rem; color: #fff; flex-shrink: 0;
                    box-shadow: 0 4px 14px rgba(105,108,255,.3);
                }
                .spl-hero-plan {
                    font-size: 1.12rem; font-weight: 800;
                    color: var(--bs-heading-color,#1e1e2e);
                    display: flex; align-items: center; gap: .6rem; flex-wrap: wrap;
                    margin-bottom: .2rem;
                }
                .spl-hero-status {
                    display: inline-flex; align-items: center; gap: .3rem;
                    padding: .2rem .65rem; border-radius: 99px;
                    font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em;
                }
                .spl-hero-meta { font-size: .78rem; color: var(--bs-secondary-color,#6c757d); }
                .spl-hero-right {
                    display: flex; align-items: center; gap: .85rem; flex-wrap: wrap;
                }
                .spl-hero-expiry {
                    text-align: right;
                }
                .spl-hero-expiry-val {
                    font-size: .88rem; font-weight: 700;
                    color: var(--bs-heading-color,#1e1e2e);
                }
                .spl-hero-expiry-lbl {
                    font-size: .67rem; text-transform: uppercase;
                    letter-spacing: .08em; color: var(--bs-secondary-color,#6c757d);
                }
                .spl-hero-days {
                    display: inline-flex; align-items: center; gap: .35rem;
                    padding: .35rem .85rem; border-radius: 99px;
                    font-size: .82rem; font-weight: 700;
                    white-space: nowrap;
                }
                .spl-hero-progress {
                    height: 3px; background: var(--bs-border-color,#eee);
                }

                /* ─── Billing toggle ───────────────────────────────────── */
                .spl-toggle-wrap {
                    display: flex; align-items: center; justify-content: center;
                    gap: .75rem; margin-bottom: 2.2rem; flex-wrap: wrap;
                }
                .spl-toggle {
                    display: inline-flex;
                    background: var(--bs-card-bg, #fff);
                    border: 1.5px solid var(--bs-border-color, #e0e0e0);
                    border-radius: 100px;
                    padding: 4px;
                    box-shadow: 0 2px 8px rgba(0,0,0,.06);
                }
                .spl-toggle-btn {
                    padding: .45rem 1.4rem;
                    border-radius: 100px;
                    font-size: .85rem; font-weight: 600;
                    cursor: pointer; border: none;
                    background: transparent;
                    color: var(--bs-secondary-color, #6c757d);
                    transition: all .18s;
                    white-space: nowrap;
                }
                .spl-toggle-btn.active {
                    background: #696cff;
                    color: #fff;
                    box-shadow: 0 2px 10px rgba(105,108,255,.4);
                }
                .spl-save-badge {
                    display: inline-flex; align-items: center; gap: .3rem;
                    background: #dcfce7; color: #16a34a;
                    border-radius: 100px;
                    padding: .28rem .85rem;
                    font-size: .75rem; font-weight: 700;
                    border: 1.5px solid #bbf7d0;
                    white-space: nowrap;
                }

                /* ─── Plan grid ─────────────────────────────────────────── */
                .spl-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                    gap: 1.25rem;
                    align-items: stretch;
                    margin-bottom: 3rem;
                }

                /* ─── Plan card ─────────────────────────────────────────── */
                .spl-card {
                    border-radius: 16px;
                    border: 1.5px solid var(--bs-border-color, #e8e8e8);
                    background: var(--bs-card-bg, #fff);
                    display: flex; flex-direction: column;
                    overflow: hidden;
                    transition: box-shadow .2s, transform .18s;
                    position: relative;
                }
                .spl-card:hover:not(.is-current) {
                    box-shadow: 0 8px 32px rgba(0,0,0,.09);
                    transform: translateY(-3px);
                }
                .spl-card.is-current {
                    border-color: #696cff;
                    box-shadow: 0 0 0 1px rgba(105,108,255,.3), 0 10px 40px rgba(105,108,255,.15);
                }
                .spl-card.is-popular:not(.is-current) {
                    border-color: rgba(105,108,255,.4);
                    box-shadow: 0 4px 20px rgba(105,108,255,.12);
                }

                /* Card top band */
                .spl-card-band {
                    height: 4px;
                    background: var(--bs-border-color, #e8e8e8);
                }
                .is-current .spl-card-band  { background: linear-gradient(90deg,#696cff,#5254cc); }
                .is-popular:not(.is-current) .spl-card-band { background: linear-gradient(90deg,#696cff 0%,#a855f7 100%); }

                /* Card header */
                .spl-card-header {
                    padding: 1.3rem 1.4rem .8rem;
                    border-bottom: 1px solid var(--bs-border-color, #f0f0f0);
                }
                .spl-card-eyebrow {
                    font-size: .68rem; font-weight: 700; letter-spacing: .1em;
                    text-transform: uppercase; color: #696cff; margin-bottom: .35rem;
                    display: flex; align-items: center; gap: .5rem;
                }
                .spl-card-badge {
                    display: inline-flex; align-items: center; gap: .25rem;
                    background: rgba(105,108,255,.1);
                    border: 1px solid rgba(105,108,255,.25);
                    border-radius: 100px;
                    padding: .15rem .55rem;
                    font-size: .62rem; font-weight: 700;
                    letter-spacing: .06em; text-transform: uppercase; color: #696cff;
                }
                .spl-card-badge.popular-badge {
                    background: rgba(168,85,247,.1);
                    border-color: rgba(168,85,247,.25);
                    color: #a855f7;
                }
                .spl-card-name { font-size: 1rem; font-weight: 800; margin-bottom: .2rem; }
                .spl-card-desc { font-size: .75rem; color: var(--bs-secondary-color, #6c757d); }

                /* Price block */
                .spl-price-block { padding: 1.1rem 1.4rem .9rem; }
                .spl-price-row {
                    display: flex; align-items: baseline; gap: .15rem;
                    margin-bottom: .2rem;
                }
                .spl-price-cur  { font-size: 1rem; font-weight: 700; color: #696cff; line-height: 1; }
                .spl-price-amt  { font-size: 2.6rem; font-weight: 900; line-height: 1; letter-spacing: -.04em; }
                .spl-price-per  { font-size: .75rem; color: var(--bs-secondary-color, #6c757d); margin-left: .1rem; align-self: flex-end; padding-bottom: .2rem; }
                .spl-price-free { font-size: 2.2rem; font-weight: 900; color: #22c55e; line-height: 1; }
                .spl-price-sub  { font-size: .73rem; color: var(--bs-secondary-color, #6c757d); margin-top: .2rem; min-height: 1.2em; }
                .spl-savings-tag {
                    display: inline-flex; align-items: center;
                    background: #dcfce7; color: #16a34a;
                    border-radius: 100px; padding: .18rem .6rem;
                    font-size: .68rem; font-weight: 700;
                    border: 1px solid #bbf7d0; margin-left: .4rem;
                }
                .spl-price-strike {
                    font-size: .95rem; font-weight: 500;
                    color: var(--bs-secondary-color,#9ca3af);
                    text-decoration: line-through;
                    margin-right: .25rem;
                }
                .spl-save-yearly {
                    font-size: .72rem; color: #16a34a; font-weight: 600;
                    margin-top: .15rem;
                    display: flex; align-items: center; gap: .25rem;
                }
                /* ─── Comparison table header badge chip ────────────────── */
                .spl-th-badge {
                    display: inline-flex; align-items: center;
                    border-radius: 100px;
                    padding: .3rem .85rem;
                    font-size: .75rem; font-weight: 800;
                    letter-spacing: .04em;
                    white-space: nowrap;
                }

                /* Features */
                .spl-features-block { padding: 0 1.4rem 1rem; flex: 1; }
                .spl-feat-row {
                    display: flex; align-items: center; gap: .65rem;
                    padding: .55rem 0;
                    border-bottom: 1px solid var(--bs-border-color, #f3f3f3);
                    font-size: .82rem;
                }
                .spl-feat-row:last-child { border-bottom: none; }
                .spl-feat-icon {
                    width: 22px; height: 22px; border-radius: 6px;
                    display: flex; align-items: center; justify-content: center;
                    font-size: .8rem; flex-shrink: 0;
                    background: rgba(105,108,255,.08);
                    color: #696cff;
                }
                .spl-feat-text { flex: 1; }
                .spl-feat-label { color: var(--bs-secondary-color,#6c757d); }
                .spl-feat-val   { font-weight: 700; color: var(--bs-body-color,#333); }

                /* CTA */
                .spl-cta-block { padding: 1rem 1.4rem 1.4rem; }
                .spl-btn-primary {
                    display: flex; align-items: center; justify-content: center; gap: .45rem;
                    width: 100%; padding: .78rem 1rem; border-radius: 10px;
                    font-size: .88rem; font-weight: 700; cursor: pointer;
                    border: none; text-decoration: none; transition: filter .18s, box-shadow .18s, transform .18s;
                    background: linear-gradient(135deg, var(--btn-g1,#696cff), var(--btn-g2,#5254cc));
                    color: #fff;
                    box-shadow: 0 4px 16px var(--btn-shadow,rgba(105,108,255,.3));
                }
                .spl-btn-primary:hover {
                    filter: brightness(1.13);
                    box-shadow: 0 8px 24px var(--btn-shadow,rgba(105,108,255,.45));
                    color: #fff; transform: translateY(-2px); text-decoration: none;
                }
                .spl-btn-outline {
                    display: flex; align-items: center; justify-content: center; gap: .45rem;
                    width: 100%; padding: .76rem 1rem; border-radius: 10px;
                    font-size: .88rem; font-weight: 600; cursor: pointer;
                    background: transparent;
                    border: 1.5px solid var(--btn-band,var(--bs-border-color,#e0e0e0));
                    color: var(--btn-band,var(--bs-secondary-color,#6c757d));
                    text-decoration: none; transition: all .2s;
                }
                .spl-btn-outline:hover {
                    background: var(--btn-chip,rgba(105,108,255,.07));
                    border-color: var(--btn-band,#696cff);
                    color: var(--btn-band,#696cff);
                    transform: translateY(-2px);
                    box-shadow: 0 4px 14px var(--btn-shadow,rgba(105,108,255,.15));
                    text-decoration: none;
                }
                .spl-btn-current {
                    display: flex; align-items: center; justify-content: center; gap: .45rem;
                    width: 100%; padding: .76rem 1rem; border-radius: 10px;
                    font-size: .88rem; font-weight: 700;
                    background: rgba(105,108,255,.08);
                    border: 1.5px dashed rgba(105,108,255,.3);
                    color: #696cff; cursor: default;
                }

                /* ─── Comparison table ──────────────────────────────────── */
                .spl-compare { margin-top: 1.5rem; }
                .spl-compare-title-wrap {
                    text-align: center; margin-bottom: 2rem;
                }
                .spl-compare-title {
                    display: inline-flex; align-items: center; gap: .6rem;
                    background: linear-gradient(135deg,#696cff 0%,#a855f7 100%);
                    color: #fff; padding: .55rem 1.75rem;
                    border-radius: 99px; font-size: .95rem;
                    font-weight: 700; letter-spacing: .03em;
                    box-shadow: 0 4px 14px rgba(105,108,255,.35);
                }
                .spl-table-wrap {
                    overflow-x: auto; border-radius: 18px;
                    box-shadow: 0 6px 32px rgba(105,108,255,.13), 0 1px 4px rgba(0,0,0,.06);
                    border: none;
                }
                .spl-table {
                    width: 100%; border-collapse: collapse; min-width: 520px;
                    background: var(--bs-card-bg,#fff);
                }
                .spl-table thead tr th {
                    padding: 1.1rem 1.2rem;
                    border-bottom: 2px solid var(--bs-border-color,#e8e8e8);
                    font-size: .72rem; font-weight: 700; text-align: center;
                    text-transform: uppercase; letter-spacing: .09em;
                    color: var(--bs-secondary-color,#6c757d);
                    background: var(--bs-tertiary-bg,#f7f7fb);
                }
                .spl-table th:first-child { text-align: left; width: 36%; }
                .spl-table th.col-current {
                    color: #696cff; background: rgba(105,108,255,.07);
                    border-top: 3px solid #696cff;
                }
                .spl-table th.col-tier {
                    color: #7c3aed; background: rgba(139,92,246,.06);
                    border-top: 3px solid #8b5cf6;
                }
                .spl-table tbody tr { transition: background .13s; }
                .spl-table tbody tr:hover td { background: rgba(105,108,255,.03); }
                .spl-table td {
                    padding: .8rem 1.2rem;
                    border-bottom: 1px solid var(--bs-border-color,#f0f0f0);
                    font-size: .84rem; text-align: center; vertical-align: middle;
                }
                .spl-table td:first-child { text-align: left; font-weight: 500; }
                .spl-table tr:last-child td { border-bottom: none; }
                .spl-table td.col-current { background: rgba(105,108,255,.045); font-weight: 700; color: #696cff; }
                .spl-table td.col-tier    { background: rgba(139,92,246,.03); }
                /* Group section label rows */
                .spl-table tr.spl-group td {
                    padding: .45rem 1.2rem;
                    font-size: .67rem; font-weight: 800; text-transform: uppercase;
                    letter-spacing: .1em; color: #696cff;
                    background: linear-gradient(90deg,rgba(105,108,255,.08) 0%,rgba(105,108,255,.02) 100%);
                    border-bottom: 1px solid rgba(105,108,255,.12);
                    pointer-events: none;
                }
                .spl-table tr.spl-group td.col-current { background: rgba(105,108,255,.1); }
                .spl-table tr.spl-group td.col-tier    { background: rgba(139,92,246,.07); }
                .spl-table .check  { color: #22c55e; font-size: 1.4rem; vertical-align: middle; }
                .spl-table .dash   { color: var(--bs-secondary-color,#ccc); font-size: 1.1rem; }
                .spl-table .val    { font-weight: 700; }
                .spl-table .feat-label { display: flex; align-items: center; gap: .55rem; }
                .spl-table .feat-icon  { color: #696cff; font-size: .95rem; flex-shrink: 0; }

                /* ─── Empty state ───────────────────────────────────────── */
                .spl-empty { text-align: center; padding: 4rem 1rem; color: var(--bs-secondary-color,#6c757d); }

                @media (max-width: 640px) {
                    .spl-hero-main { flex-direction: column; align-items: flex-start; }
                    .spl-hero-right { justify-content: flex-start; }
                    .spl-hero-expiry { text-align: left; }
                    .spl-grid { grid-template-columns: 1fr; }
                    .spl-toggle-wrap { flex-direction: column; }
                }
                </style>

                <div class="spl-page">

                <!-- ── Current plan card ─────────────────────────────── -->
                <?php if ($sub): ?>
                <?php
                $_tz          = $JwtData->Org->OrgTimezone ?? 'UTC';
                $_endTs       = viewPageDateTimeFormat($sub->EndDate ?? null, $_tz, 2);
                $_heroTc      = $getTierColor($sub->PlanCode ?? '');
                $_days        = max(0, (int)($sub->DaysRemaining ?? 0));
                $_dur         = max(1, (int)($sub->DurationDays ?? 30));
                $_pct         = min(100, round($_days / $_dur * 100));
                $_status      = $sub->Status ?? '';
                /* Status pill colors */
                $_statusMap   = [
                    'Active'         => ['bg'=>'#dcfce7','color'=>'#15803d','dot'=>'#22c55e'],
                    'Trial'          => ['bg'=>'#dbeafe','color'=>'#1d4ed8','dot'=>'#3b82f6'],
                    'Expired'        => ['bg'=>'#fee2e2','color'=>'#b91c1c','dot'=>'#ef4444'],
                    'PendingPayment' => ['bg'=>'#fef3c7','color'=>'#92400e','dot'=>'#f59e0b'],
                    'Suspended'      => ['bg'=>'#f3f4f6','color'=>'#374151','dot'=>'#9ca3af'],
                ];
                $_sp          = $_statusMap[$_status] ?? ['bg'=>'#f3f4f6','color'=>'#374151','dot'=>'#9ca3af'];
                /* Days pill urgency */
                if ($_days > 30)     { $_dayBg = '#dcfce7'; $_dayColor = '#15803d'; $_dayIcon = 'bx-time-five'; }
                elseif ($_days > 7)  { $_dayBg = '#fef3c7'; $_dayColor = '#92400e'; $_dayIcon = 'bx-time-five'; }
                else                 { $_dayBg = '#fee2e2'; $_dayColor = '#b91c1c'; $_dayIcon = 'bx-alarm-exclamation'; }
                /* Progress bar color matches urgency */
                $_barColor    = ($_days > 30) ? '#22c55e' : (($_days > 7) ? '#f59e0b' : '#ef4444');
                ?>
                <div class="spl-hero">
                    <!-- Tier-colored top accent stripe -->
                    <div class="spl-hero-accent" style="background:linear-gradient(90deg,<?php echo $_heroTc['g1']; ?>,<?php echo $_heroTc['g2']; ?>);"></div>
                    <div class="spl-hero-body">
                        <!-- Breadcrumb nav row -->
                        <div class="spl-hero-nav">
                            <a href="<?= site_url('subscription/dashboard') ?>" class="spl-hero-back">
                                <i class="bx bx-chevron-left"></i> My Subscription
                            </a>
                            <span class="spl-hero-sep"><i class="bx bx-chevron-right" style="font-size:.7rem;"></i></span>
                            <span class="spl-hero-crumb">Plans</span>
                        </div>
                        <!-- Main content row -->
                        <div class="spl-hero-main">
                            <div class="spl-hero-left">
                                <!-- Tier-colored plan icon -->
                                <div class="spl-hero-icon" style="background:linear-gradient(135deg,<?php echo $_heroTc['g1']; ?>,<?php echo $_heroTc['g2']; ?>);box-shadow:0 4px 14px <?php echo $_heroTc['shadow']; ?>;">
                                    <i class="bx bx-crown" style="font-size:1.4rem;"></i>
                                </div>
                                <div>
                                    <div class="spl-hero-plan">
                                        <?php echo htmlspecialchars($sub->PlanName ?? 'Trial'); ?>
                                        <span class="spl-hero-status" style="background:<?php echo $_sp['bg']; ?>;color:<?php echo $_sp['color']; ?>;">
                                            <span style="width:6px;height:6px;border-radius:50%;background:<?php echo $_sp['dot']; ?>;display:inline-block;"></span>
                                            <?php echo htmlspecialchars($_status); ?>
                                        </span>
                                    </div>
                                    <div class="spl-hero-meta">
                                        <?php echo htmlspecialchars($sub->SectorName ?? 'ERP'); ?>
                                        <?php if (!empty($sub->BillingCycle)): ?>&middot; <?php echo htmlspecialchars($sub->BillingCycle); ?><?php endif; ?>
                                        <?php if ((int)($sub->MaxUsers ?? 0) > 0): ?>&middot; <?php echo (int)$sub->MaxUsers; ?> users max<?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <!-- Right: expiry + days pill -->
                            <div class="spl-hero-right">
                                <?php if (!empty($sub->EndDate)): ?>
                                <div class="spl-hero-expiry">
                                    <div class="spl-hero-expiry-val"><?php echo $_endTs->formatted; ?></div>
                                    <div class="spl-hero-expiry-lbl">Expires</div>
                                </div>
                                <span class="spl-hero-days" style="background:<?php echo $_dayBg; ?>;color:<?php echo $_dayColor; ?>;">
                                    <i class="bx <?php echo $_dayIcon; ?>"></i>
                                    <?php echo $_days; ?> day<?php echo $_days !== 1 ? 's' : ''; ?> left
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <!-- Urgency progress bar bleeds to card edges -->
                    <div class="spl-hero-progress">
                        <div style="height:100%;width:<?php echo $_pct; ?>%;background:<?php echo $_barColor; ?>;transition:width .4s;"></div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (empty($plans)): ?>
                <div class="spl-empty">
                    <i class="bx bx-package" style="font-size:3rem;display:block;margin-bottom:.75rem;"></i>
                    No plans available. Please contact support.
                </div>
                <?php else: ?>

                <!-- ── Billing cycle toggle ──────────────────────────── -->
                <div class="spl-toggle-wrap">
                    <div class="spl-toggle">
                        <?php if (!empty($byBilling['Monthly'])): ?>
                        <button class="spl-toggle-btn <?php echo $defaultTab==='monthly'?'active':''; ?>" onclick="splSetTab('monthly',this)">Monthly</button>
                        <?php endif; ?>
                        <?php if (!empty($byBilling['Yearly'])): ?>
                        <button class="spl-toggle-btn <?php echo $defaultTab==='yearly'?'active':''; ?>" onclick="splSetTab('yearly',this)">Yearly</button>
                        <?php endif; ?>
                    </div>
                    <?php if ($maxSavings > 0): ?>
                    <div class="spl-save-badge">
                        <i class="bx bx-trending-down"></i>
                        Save up to <?php echo $maxSavings; ?>% with Yearly
                    </div>
                    <?php endif; ?>
                </div>

                <!-- ── Plan cards (Monthly) ──────────────────────────── -->
                <?php if (!empty($byBilling['Monthly'])): ?>
                <div class="spl-grid spl-tab-monthly" <?php echo $defaultTab!=='monthly'?'style="display:none"':''; ?>>
                <?php foreach ($byBilling['Monthly'] as $_plan):
                    $_isCurrent = ((int)$_plan->SectorPlanUID === $currentPlanUID);
                    $_isPopular = ((int)$_plan->SectorPlanUID === $popularM && !$_isCurrent);
                    $_isFree    = ((float)$_plan->Price <= 0);
                    $_isUpgrade = (!$_isCurrent && (float)$_plan->Price > $currentPrice);
                    $_modCnt    = $modCount[(int)$_plan->PlanUID] ?? 0;
                    $_meta      = $getTierMeta($_plan->PlanCode ?? '');
                    $_tc        = $getTierColor($_plan->PlanCode ?? '');
                    /* Find savings% for this monthly plan's yearly equivalent */
                    $_yearlySav = 0;
                    foreach ($byBilling['Yearly'] as $_yp2) {
                        $yBase = preg_replace('/_Y$/', '', $_yp2->PlanCode ?? '');
                        $mBase = preg_replace('/_M$/', '', $_plan->PlanCode ?? '');
                        if ($yBase === $mBase) { $_yearlySav = $yearSavings[(int)$_yp2->SectorPlanUID] ?? 0; break; }
                    }
                ?>
                <div class="spl-card <?php echo $_isCurrent?'is-current':''; ?> <?php echo $_isPopular?'is-popular':''; ?>">
                    <div class="spl-card-band" style="background:linear-gradient(90deg,<?php echo $_tc['g1']; ?>,<?php echo $_tc['g2']; ?>);"></div>
                    <div class="spl-card-header">
                        <div class="spl-card-eyebrow">
                            <?php if ($_isCurrent): ?>
                            <span class="spl-card-badge"><i class="bx bx-check"></i> Current Plan</span>
                            <?php elseif ($_isPopular): ?>
                            <span class="spl-card-badge popular-badge"><i class="bx bx-star"></i> Most Popular</span>
                            <?php else: echo htmlspecialchars($_meta['label']); ?>
                            <?php endif; ?>
                        </div>
                        <div class="spl-card-name"><?php echo htmlspecialchars($_plan->PlanName); ?></div>
                        <div class="spl-card-desc"><?php echo htmlspecialchars($_meta['desc']); ?></div>
                    </div>
                    <div class="spl-price-block">
                        <?php if ($_isFree): ?>
                        <div class="spl-price-row"><span class="spl-price-free">Free</span></div>
                        <div class="spl-price-sub">No credit card required</div>
                        <?php else: ?>
                        <div class="spl-price-row">
                            <span class="spl-price-cur">&#8377;</span>
                            <span class="spl-price-amt"><?php echo number_format((float)$_plan->Price, 0); ?></span>
                            <span class="spl-price-per">/ month</span>
                        </div>
                        <div class="spl-price-sub">
                            Billed monthly &middot; <?php echo (int)$_plan->DurationDays; ?> days
                        </div>
                        <?php if ($_yearlySav > 0): ?>
                        <div class="spl-save-yearly"><i class="bx bx-trending-down"></i> Switch to Yearly &amp; save <?php echo $_yearlySav; ?>%</div>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <div class="spl-features-block">
                        <?php if ((int)($_plan->MaxUsers??0) > 0): ?><div class="spl-feat-row"><div class="spl-feat-icon"><i class="bx bx-user"></i></div><div class="spl-feat-text"><span class="spl-feat-label">Users — </span><span class="spl-feat-val"><?php echo (int)$_plan->MaxUsers===0?'Unlimited':(int)$_plan->MaxUsers; ?></span></div></div><?php endif; ?>
                        <?php if ((int)($_plan->MaxBranches??0) > 0): ?><div class="spl-feat-row"><div class="spl-feat-icon"><i class="bx bx-buildings"></i></div><div class="spl-feat-text"><span class="spl-feat-label">Branches — </span><span class="spl-feat-val"><?php echo (int)$_plan->MaxBranches===0?'Unlimited':(int)$_plan->MaxBranches; ?></span></div></div><?php endif; ?>
                        <?php if ($_modCnt > 0): ?><div class="spl-feat-row"><div class="spl-feat-icon"><i class="bx bx-grid-alt"></i></div><div class="spl-feat-text"><span class="spl-feat-label">Modules — </span><span class="spl-feat-val"><?php echo $_modCnt; ?> included</span></div></div><?php endif; ?>
                        <?php if ((float)($_plan->TaxRate??0) > 0): ?><div class="spl-feat-row"><div class="spl-feat-icon"><i class="bx bx-receipt"></i></div><div class="spl-feat-text"><span class="spl-feat-label">GST — </span><span class="spl-feat-val"><?php echo number_format((float)$_plan->TaxRate,0); ?>% included</span></div></div><?php endif; ?>
                        <div class="spl-feat-row"><div class="spl-feat-icon"><i class="bx bxs-check-shield"></i></div><div class="spl-feat-text"><span class="spl-feat-label">Access — </span><span class="spl-feat-val">Full ERP suite</span></div></div>
                    </div>
                    <div class="spl-cta-block">
                        <?php if ($_isCurrent): ?>
                        <div class="spl-btn-current"><i class="bx bx-check-shield"></i> Your Current Plan</div>
                        <?php elseif ($_isUpgrade): ?>
                        <button type="button" class="spl-btn-primary" style="--btn-g1:<?php echo $_tc['g1']; ?>;--btn-g2:<?php echo $_tc['g2']; ?>;--btn-shadow:<?php echo $_tc['shadow']; ?>;" onclick="splOpenChangePlan(<?php echo (int)$_plan->SectorPlanUID; ?>,<?php echo htmlspecialchars(json_encode($_isUpgrade ? 'Upgrade' : 'Downgrade'), ENT_QUOTES); ?>)"><i class="bx bx-up-arrow-circle"></i> Upgrade Now</button>
                        <?php else: ?>
                        <button type="button" class="spl-btn-outline" style="--btn-band:<?php echo $_tc['band']; ?>;--btn-chip:<?php echo $_tc['chip']; ?>;--btn-shadow:<?php echo $_tc['shadow']; ?>;" onclick="splOpenChangePlan(<?php echo (int)$_plan->SectorPlanUID; ?>,<?php echo htmlspecialchars(json_encode('Downgrade'), ENT_QUOTES); ?>)"><i class="bx bx-transfer"></i> Switch Plan</button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- ── Plan cards (Yearly) ───────────────────────────── -->
                <?php if (!empty($byBilling['Yearly'])): ?>
                <div class="spl-grid spl-tab-yearly" <?php echo $defaultTab!=='yearly'?'style="display:none"':''; ?>>
                <?php foreach ($byBilling['Yearly'] as $_plan):
                    $_isCurrent = ((int)$_plan->SectorPlanUID === $currentPlanUID);
                    $_isPopular = ((int)$_plan->SectorPlanUID === $popularY && !$_isCurrent);
                    $_isFree    = ((float)$_plan->Price <= 0);
                    $_isUpgrade = (!$_isCurrent && (float)$_plan->Price > $currentPrice);
                    $_modCnt    = $modCount[(int)$_plan->PlanUID] ?? 0;
                    $_meta      = $getTierMeta($_plan->PlanCode ?? '');
                    $_tc        = $getTierColor($_plan->PlanCode ?? '');
                    $_savings   = $yearSavings[(int)$_plan->SectorPlanUID] ?? 0;
                    $_perMonth  = $monthlyPerMonth[(int)$_plan->SectorPlanUID] ?? 0;
                    $_origMonth = $monthlyOriginalPrice[(int)$_plan->SectorPlanUID] ?? 0;
                ?>
                <div class="spl-card <?php echo $_isCurrent?'is-current':''; ?> <?php echo $_isPopular?'is-popular':''; ?>">
                    <div class="spl-card-band" style="background:linear-gradient(90deg,<?php echo $_tc['g1'].','.$_tc['g2']; ?>);"></div>
                    <div class="spl-card-header">
                        <div class="spl-card-eyebrow">
                            <?php if ($_isCurrent): ?>
                            <span class="spl-card-badge"><i class="bx bx-check"></i> Current Plan</span>
                            <?php elseif ($_isPopular): ?>
                            <span class="spl-card-badge popular-badge"><i class="bx bx-star"></i> Best Value</span>
                            <?php else: echo htmlspecialchars($_meta['label']); ?>
                            <?php endif; ?>
                        </div>
                        <div class="spl-card-name"><?php echo htmlspecialchars($_plan->PlanName); ?></div>
                        <div class="spl-card-desc"><?php echo htmlspecialchars($_meta['desc']); ?></div>
                    </div>
                    <div class="spl-price-block">
                        <?php if ($_isFree): ?>
                        <div class="spl-price-row"><span class="spl-price-free">Free</span></div>
                        <div class="spl-price-sub">No credit card required</div>
                        <?php else: ?>
                        <div class="spl-price-row" style="align-items:baseline;flex-wrap:wrap;gap:.2rem;">
                            <?php if ($_origMonth > 0): ?>
                            <span class="spl-price-strike">&#8377;<?php echo number_format($_origMonth, 0); ?></span>
                            <?php endif; ?>
                            <span class="spl-price-cur">&#8377;</span>
                            <span class="spl-price-amt"><?php echo $_perMonth > 0 ? number_format($_perMonth, 0) : number_format((float)$_plan->Price, 0); ?></span>
                            <span class="spl-price-per">/ month</span>
                            <?php if ($_savings > 0): ?>
                            <span class="spl-savings-tag">Save <?php echo $_savings; ?>%</span>
                            <?php endif; ?>
                        </div>
                        <div class="spl-price-sub">
                            Pay &#8377;<?php echo number_format((float)$_plan->Price, 0); ?> annually &middot; <?php echo (int)$_plan->DurationDays; ?> days
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="spl-features-block">
                        <?php if ((int)($_plan->MaxUsers??0) > 0): ?><div class="spl-feat-row"><div class="spl-feat-icon"><i class="bx bx-user"></i></div><div class="spl-feat-text"><span class="spl-feat-label">Users — </span><span class="spl-feat-val"><?php echo (int)$_plan->MaxUsers===0?'Unlimited':(int)$_plan->MaxUsers; ?></span></div></div><?php endif; ?>
                        <?php if ((int)($_plan->MaxBranches??0) > 0): ?><div class="spl-feat-row"><div class="spl-feat-icon"><i class="bx bx-buildings"></i></div><div class="spl-feat-text"><span class="spl-feat-label">Branches — </span><span class="spl-feat-val"><?php echo (int)$_plan->MaxBranches===0?'Unlimited':(int)$_plan->MaxBranches; ?></span></div></div><?php endif; ?>
                        <?php if ($_modCnt > 0): ?><div class="spl-feat-row"><div class="spl-feat-icon"><i class="bx bx-grid-alt"></i></div><div class="spl-feat-text"><span class="spl-feat-label">Modules — </span><span class="spl-feat-val"><?php echo $_modCnt; ?> included</span></div></div><?php endif; ?>
                        <?php if ((float)($_plan->TaxRate??0) > 0): ?><div class="spl-feat-row"><div class="spl-feat-icon"><i class="bx bx-receipt"></i></div><div class="spl-feat-text"><span class="spl-feat-label">GST — </span><span class="spl-feat-val"><?php echo number_format((float)$_plan->TaxRate,0); ?>% included</span></div></div><?php endif; ?>
                        <div class="spl-feat-row"><div class="spl-feat-icon"><i class="bx bxs-check-shield"></i></div><div class="spl-feat-text"><span class="spl-feat-label">Access — </span><span class="spl-feat-val">Full ERP suite</span></div></div>
                    </div>
                    <div class="spl-cta-block">
                        <?php if ($_isCurrent): ?>
                        <div class="spl-btn-current"><i class="bx bx-check-shield"></i> Your Current Plan</div>
                        <?php elseif ($_isUpgrade): ?>
                        <button type="button" class="spl-btn-primary" style="--btn-g1:<?php echo $_tc['g1']; ?>;--btn-g2:<?php echo $_tc['g2']; ?>;--btn-shadow:<?php echo $_tc['shadow']; ?>;" onclick="splOpenChangePlan(<?php echo (int)$_plan->SectorPlanUID; ?>,<?php echo htmlspecialchars(json_encode($_isUpgrade ? 'Upgrade' : 'Downgrade'), ENT_QUOTES); ?>)"><i class="bx bx-up-arrow-circle"></i> Upgrade Now</button>
                        <?php else: ?>
                        <button type="button" class="spl-btn-outline" style="--btn-band:<?php echo $_tc['band']; ?>;--btn-chip:<?php echo $_tc['chip']; ?>;--btn-shadow:<?php echo $_tc['shadow']; ?>;" onclick="splOpenChangePlan(<?php echo (int)$_plan->SectorPlanUID; ?>,<?php echo htmlspecialchars(json_encode('Downgrade'), ENT_QUOTES); ?>)"><i class="bx bx-transfer"></i> Switch Plan</button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- ── Plan comparison tables (one per billing cycle) ── -->
                <?php
                $supportLabels = ['BASIC'=>'Email','PRO'=>'Priority','ENT'=>'Dedicated'];

                /* Determine the tier base of the current plan (e.g. "ENT" from "ENT_M") */
                $currentPlanBase = '';
                foreach ($plans as $_p) {
                    if ((int)$_p->SectorPlanUID === $currentPlanUID) {
                        $code = $_p->PlanCode ?? '';
                        $currentPlanBase = rtrim(rtrim($code, 'Y'), rtrim($code, 'M'));
                        /* strip trailing _M or _Y */
                        if (preg_match('/^(.+)_[MY]$/', $code, $_m)) {
                            $currentPlanBase = $_m[1];
                        }
                        break;
                    }
                }

                /* Helper: build ordered column map for a billing group */
                $buildTableCols = function(array $group, string $suffix) use ($modCount): array {
                    $cols = [];
                    foreach ($group as $_p) {
                        $code = $_p->PlanCode ?? '';
                        if (strtoupper(substr($code, -2)) !== $suffix) continue;
                        $base = substr($code, 0, -2);
                        if ($base === 'TRIAL') continue;
                        $cols[$base] = $_p;
                    }
                    return $cols;
                };

                $tableColsM = $buildTableCols($byBilling['Monthly'], '_M');
                $tableColsY = $buildTableCols($byBilling['Yearly'],  '_Y');

                /* Render a comparison table given a column map and a price label */
                $renderTable = function(array $cols, string $priceLabel) use ($currentPlanUID, $currentPlanBase, $modCount, $supportLabels, $yearSavings, $monthlyPerMonth, $getTierColor): void {
                    if (count($cols) < 2) return;
                    ?>
                    <div class="spl-table-wrap">
                        <table class="spl-table">
                            <thead>
                                <tr>
                                    <th style="width:35%">Feature</th>
                                    <?php foreach ($cols as $base => $_col):
                                        $_isCurrentCol = (int)$_col->SectorPlanUID === $currentPlanUID;
                                        $_isTierCol    = !$_isCurrentCol && ($currentPlanBase !== '' && $base === $currentPlanBase);
                                        $_colClass     = $_isCurrentCol ? 'col-current' : ($_isTierCol ? 'col-tier' : '');
                                        $_thTc         = $getTierColor(($_col->PlanCode ?? '') . '_');
                                    ?>
                                                    <th class="<?php echo $_colClass; ?>" style="<?php echo !$_isCurrentCol && !$_isTierCol ? 'border-top:3px solid '.$_thTc['band'].';' : ''; ?>">
                                        <span class="spl-th-badge" style="background:<?php echo $_thTc['chip']; ?>;color:<?php echo $_thTc['chipTxt']; ?>;font-size:.78rem;padding:.3rem .9rem;">
                                            <?php echo htmlspecialchars($_col->PlanName ?? $base); ?>
                                        </span>
                                        <?php if ($_isCurrentCol): ?>
                                        <br><small style="font-weight:700;font-size:.65rem;color:#696cff;margin-top:.25rem;display:block;">&#10003; Current</small>
                                        <?php elseif ($_isTierCol): ?>
                                        <br><small style="font-weight:700;font-size:.65rem;color:#7c3aed;margin-top:.25rem;display:block;">Your Tier</small>
                                        <?php endif; ?>
                                    </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Group: Pricing -->
                                <tr class="spl-group">
                                    <td colspan="<?php echo count($cols) + 1; ?>"><i class="bx bx-dollar-circle me-1"></i> Pricing</td>
                                </tr>
                                <tr>
                                    <td><span class="feat-label"><i class="feat-icon bx bx-rupee"></i> <?php echo $priceLabel; ?></span></td>
                                    <?php foreach ($cols as $base => $_col):
                                        $_isCurrentCol = (int)$_col->SectorPlanUID === $currentPlanUID;
                                        $_isTierCol    = !$_isCurrentCol && ($currentPlanBase !== '' && $base === $currentPlanBase);
                                        $_colClass     = $_isCurrentCol ? 'col-current' : ($_isTierCol ? 'col-tier' : '');
                                        $_sav = $yearSavings[(int)$_col->SectorPlanUID] ?? 0;
                                    ?>
                                    <td class="<?php echo $_colClass; ?>">
                                        <?php if ((float)$_col->Price <= 0): ?>
                                        <span class="val" style="color:#22c55e">Free</span>
                                        <?php else: ?>
                                        <span class="val">&#8377;<?php echo number_format((float)$_col->Price,0); ?></span>
                                        <?php if ($_sav > 0): ?><br><small style="color:#16a34a;font-weight:600;">Save <?php echo $_sav; ?>%</small><?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php if ($priceLabel === 'Price / Year'): ?>
                                <tr>
                                    <td><span class="feat-label"><i class="feat-icon bx bx-calculator"></i> Per Month Equiv.</span></td>
                                    <?php foreach ($cols as $base => $_col):
                                        $_isCurrentCol = (int)$_col->SectorPlanUID === $currentPlanUID;
                                        $_isTierCol    = !$_isCurrentCol && ($currentPlanBase !== '' && $base === $currentPlanBase);
                                        $_colClass     = $_isCurrentCol ? 'col-current' : ($_isTierCol ? 'col-tier' : '');
                                        $_pm = $monthlyPerMonth[(int)$_col->SectorPlanUID] ?? 0;
                                    ?>
                                    <td class="<?php echo $_colClass; ?>">
                                        <span class="val"><?php echo $_pm > 0 ? '₹'.number_format($_pm,0).'/mo' : '—'; ?></span>
                                    </td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php endif; ?>
                                <!-- Group: Capacity -->
                                <tr class="spl-group">
                                    <td colspan="<?php echo count($cols) + 1; ?>"><i class="bx bx-layer me-1"></i> Capacity</td>
                                </tr>
                                <tr>
                                    <td><span class="feat-label"><i class="feat-icon bx bx-user"></i> Max Users</span></td>
                                    <?php foreach ($cols as $base => $_col):
                                        $_isCurrentCol = (int)$_col->SectorPlanUID === $currentPlanUID;
                                        $_isTierCol    = !$_isCurrentCol && ($currentPlanBase !== '' && $base === $currentPlanBase);
                                        $_colClass     = $_isCurrentCol ? 'col-current' : ($_isTierCol ? 'col-tier' : '');
                                        $_maxU = (int)($_col->MaxUsers ?? 0);
                                    ?>
                                    <td class="<?php echo $_colClass; ?>">
                                        <span class="val"><?php echo $_maxU === 0 ? 'Unlimited' : $_maxU; ?></span>
                                    </td>
                                    <?php endforeach; ?>
                                </tr>
                                <tr>
                                    <td><span class="feat-label"><i class="feat-icon bx bx-buildings"></i> Branches</span></td>
                                    <?php foreach ($cols as $base => $_col):
                                        $_isCurrentCol = (int)$_col->SectorPlanUID === $currentPlanUID;
                                        $_isTierCol    = !$_isCurrentCol && ($currentPlanBase !== '' && $base === $currentPlanBase);
                                        $_colClass     = $_isCurrentCol ? 'col-current' : ($_isTierCol ? 'col-tier' : '');
                                        $_maxB = (int)($_col->MaxBranches ?? 0);
                                    ?>
                                    <td class="<?php echo $_colClass; ?>">
                                        <span class="val"><?php echo $_maxB === 0 ? 'Unlimited' : $_maxB; ?></span>
                                    </td>
                                    <?php endforeach; ?>
                                </tr>
                                <tr>
                                    <td><span class="feat-label"><i class="feat-icon bx bx-grid-alt"></i> Modules Included</span></td>
                                    <?php foreach ($cols as $base => $_col):
                                        $_isCurrentCol = (int)$_col->SectorPlanUID === $currentPlanUID;
                                        $_isTierCol    = !$_isCurrentCol && ($currentPlanBase !== '' && $base === $currentPlanBase);
                                        $_colClass     = $_isCurrentCol ? 'col-current' : ($_isTierCol ? 'col-tier' : '');
                                        $_mc = $modCount[(int)$_col->PlanUID] ?? 0;
                                    ?>
                                    <td class="<?php echo $_colClass; ?>">
                                        <span class="val"><?php echo $_mc > 0 ? $_mc : '—'; ?></span>
                                    </td>
                                    <?php endforeach; ?>
                                </tr>
                                <tr>
                                    <td><span class="feat-label"><i class="feat-icon bx bx-calendar"></i> Validity</span></td>
                                    <?php foreach ($cols as $base => $_col):
                                        $_isCurrentCol = (int)$_col->SectorPlanUID === $currentPlanUID;
                                        $_isTierCol    = !$_isCurrentCol && ($currentPlanBase !== '' && $base === $currentPlanBase);
                                        $_colClass     = $_isCurrentCol ? 'col-current' : ($_isTierCol ? 'col-tier' : '');
                                    ?>
                                    <td class="<?php echo $_colClass; ?>">
                                        <span class="val"><?php echo (int)$_col->DurationDays; ?> days</span>
                                    </td>
                                    <?php endforeach; ?>
                                </tr>
                                <tr>
                                    <td><span class="feat-label"><i class="feat-icon bx bx-receipt"></i> GST Rate</span></td>
                                    <?php foreach ($cols as $base => $_col):
                                        $_isCurrentCol = (int)$_col->SectorPlanUID === $currentPlanUID;
                                        $_isTierCol    = !$_isCurrentCol && ($currentPlanBase !== '' && $base === $currentPlanBase);
                                        $_colClass     = $_isCurrentCol ? 'col-current' : ($_isTierCol ? 'col-tier' : '');
                                    ?>
                                    <td class="<?php echo $_colClass; ?>">
                                        <span class="val"><?php echo number_format((float)$_col->TaxRate,0); ?>%</span>
                                    </td>
                                    <?php endforeach; ?>
                                </tr>
                                <!-- Group: Features -->
                                <tr class="spl-group">
                                    <td colspan="<?php echo count($cols) + 1; ?>"><i class="bx bx-star me-1"></i> Features</td>
                                </tr>
                                <tr>
                                    <td><span class="feat-label"><i class="feat-icon bx bxs-check-shield"></i> Full ERP Access</span></td>
                                    <?php foreach ($cols as $base => $_col):
                                        $_isCurrentCol = (int)$_col->SectorPlanUID === $currentPlanUID;
                                        $_isTierCol    = !$_isCurrentCol && ($currentPlanBase !== '' && $base === $currentPlanBase);
                                        $_colClass     = $_isCurrentCol ? 'col-current' : ($_isTierCol ? 'col-tier' : '');
                                    ?>
                                    <td class="<?php echo $_colClass; ?>"><i class="bx bxs-check-circle check"></i></td>
                                    <?php endforeach; ?>
                                </tr>
                                <tr>
                                    <td><span class="feat-label"><i class="feat-icon bx bx-support"></i> Support</span></td>
                                    <?php foreach ($cols as $base => $_col):
                                        $_isCurrentCol = (int)$_col->SectorPlanUID === $currentPlanUID;
                                        $_isTierCol    = !$_isCurrentCol && ($currentPlanBase !== '' && $base === $currentPlanBase);
                                        $_colClass     = $_isCurrentCol ? 'col-current' : ($_isTierCol ? 'col-tier' : '');
                                        $supportLabel  = $supportLabels[$base] ?? 'Standard';
                                    ?>
                                    <td class="<?php echo $_colClass; ?>">
                                        <span class="val"><?php echo $supportLabel; ?></span>
                                    </td>
                                    <?php endforeach; ?>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <?php
                };
                ?>

                <?php if (count($tableColsM) >= 2 || count($tableColsY) >= 2): ?>
                <div class="spl-compare">
                    <div class="spl-compare-title-wrap">
                        <span class="spl-compare-title">
                            <i class="bx bx-table"></i> Plan Comparison
                        </span>
                    </div>

                    <?php if (count($tableColsM) >= 2): ?>
                    <div class="spl-tab-monthly" <?php echo $defaultTab !== 'monthly' ? 'style="display:none"' : ''; ?>>
                        <?php $renderTable($tableColsM, 'Price / Month'); ?>
                    </div>
                    <?php endif; ?>

                    <?php if (count($tableColsY) >= 2): ?>
                    <div class="spl-tab-yearly" <?php echo $defaultTab !== 'yearly' ? 'style="display:none"' : ''; ?>>
                        <?php $renderTable($tableColsY, 'Price / Year'); ?>
                    </div>
                    <?php endif; ?>

                </div>
                <?php endif; ?>

                <?php endif; /* !empty($plans) */ ?>

                </div><!-- end spl-page -->

                </div><!-- end container-xxl -->
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════
     Plan Change Modal
═══════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="planChangeModal" tabindex="-1" aria-labelledby="pcmTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:16px;overflow:hidden;">

            <!-- Header -->
            <div class="modal-header border-0 pb-0" id="pcm-header" style="background:var(--bs-card-bg);padding:1.25rem 1.5rem .75rem;transition:box-shadow .2s;">
                <div class="d-flex align-items-center gap-2 flex-grow-1">
                    <div id="pcm-change-badge" class="badge rounded-pill" style="font-size:.7rem;padding:.3rem .7rem;">Upgrade</div>
                    <h5 class="modal-title mb-0 fw-bold" id="pcmTitle">Change Plan</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <!-- Plan flow strip -->
            <div class="px-4 pb-0 pt-2" style="background:var(--bs-card-bg);">
                <div class="d-flex align-items-center gap-2 mb-3" style="font-size:.85rem;">
                    <span class="fw-semibold" id="pcm-from-name" style="color:var(--bs-secondary-color);">—</span>
                    <i class="bx bx-right-arrow-alt" style="color:#696cff;font-size:1.1rem;"></i>
                    <span class="fw-bold" id="pcm-to-name" style="color:var(--bs-heading-color);">—</span>
                </div>
                <hr class="mt-0 mb-0">
            </div>

            <div class="modal-body px-4 py-3">

                <!-- Loading -->
                <div id="pcm-loading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="mt-2 text-muted small">Loading options…</div>
                </div>

                <!-- Locked state -->
                <div id="pcm-locked" hidden>
                    <div class="alert alert-warning d-flex gap-2 align-items-start mb-0" style="border-radius:10px;">
                        <i class="bx bx-lock-alt fs-5 mt-1 flex-shrink-0"></i>
                        <div>
                            <div class="fw-bold mb-1">Plan changes are temporarily locked</div>
                            <div id="pcm-locked-msg" class="small"></div>
                        </div>
                    </div>
                </div>

                <!-- Generic error state -->
                <div id="pcm-error" hidden>
                    <div class="alert alert-danger d-flex gap-2 align-items-start mb-0" style="border-radius:10px;">
                        <i class="bx bx-error-circle fs-5 mt-1 flex-shrink-0"></i>
                        <div>
                            <div class="fw-bold mb-1">Unable to load options</div>
                            <div id="pcm-error-msg" class="small"></div>
                        </div>
                    </div>
                </div>

                <!-- Scheduled plan notice -->
                <div id="pcm-scheduled-notice" hidden class="alert d-flex gap-2 align-items-start mb-3" style="border-radius:10px;background:#ede9fe;border:1.5px solid #c4b5fd;color:#4c1d95;">
                    <i class="bx bx-calendar-check fs-5 mt-1 flex-shrink-0"></i>
                    <div id="pcm-scheduled-msg" class="small"></div>
                </div>

                <!-- Module comparison -->
                <div id="pcm-modules" hidden class="mb-3 p-3 rounded-3" style="background:var(--bs-body-bg);border:1.5px solid var(--bs-border-color);">
                    <div class="fw-semibold small mb-2" style="color:var(--bs-heading-color);">
                        <i class="bx bx-grid-alt me-1" style="color:#696cff;"></i> Module Changes
                    </div>
                    <div class="d-flex gap-3 flex-wrap">
                        <div id="pcm-modules-added" hidden>
                            <div class="small fw-bold mb-1" style="color:#15803d;"><i class="bx bx-plus-circle me-1"></i>Added</div>
                            <div id="pcm-modules-added-list"></div>
                        </div>
                        <div id="pcm-modules-removed" hidden>
                            <div class="small fw-bold mb-1" style="color:#b91c1c;"><i class="bx bx-minus-circle me-1"></i>Removed</div>
                            <div id="pcm-modules-removed-list"></div>
                        </div>
                        <div id="pcm-modules-empty" hidden class="small text-muted">Module details not yet configured for this plan.</div>
                    </div>
                </div>

                <!-- Options -->
                <div id="pcm-options" hidden>
                    <div class="fw-semibold small mb-2" style="color:var(--bs-heading-color);">Choose how to switch:</div>
                    <div id="pcm-options-list" class="d-flex flex-column gap-2"></div>
                </div>

                <!-- Detail panel (updates on option select) -->
                <div id="pcm-detail" hidden class="mt-3 p-3 rounded-3" style="background:var(--bs-body-bg);border:1.5px solid var(--bs-border-color);">
                    <div class="row g-2 mb-3">
                        <div class="col-6 col-sm-3 text-center">
                            <div class="small text-muted mb-1">Starts</div>
                            <div class="fw-bold small" id="pcm-d-start">—</div>
                        </div>
                        <div class="col-6 col-sm-3 text-center">
                            <div class="small text-muted mb-1">Ends</div>
                            <div class="fw-bold small" id="pcm-d-end">—</div>
                        </div>
                        <div class="col-6 col-sm-3 text-center">
                            <div class="small text-muted mb-1">Duration</div>
                            <div class="fw-bold small" id="pcm-d-days">—</div>
                        </div>
                        <div class="col-6 col-sm-3 text-center">
                            <div class="small text-muted mb-1">Credit Used</div>
                            <div class="fw-bold small" id="pcm-d-credit">—</div>
                        </div>
                    </div>
                    <hr class="my-2">
                    <!-- GST breakdown -->
                    <div class="d-flex flex-column gap-1" style="font-size:.83rem;">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Base Amount</span>
                            <span id="pcm-d-base">—</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">GST (<span id="pcm-d-taxrate">18</span>%)</span>
                            <span id="pcm-d-tax">—</span>
                        </div>
                        <div class="d-flex justify-content-between fw-bold border-top pt-1 mt-1">
                            <span>Total Payable</span>
                            <span id="pcm-d-total" style="color:#696cff;font-size:1rem;">—</span>
                        </div>
                    </div>
                </div>

            </div><!-- modal-body -->

            <div class="modal-footer pt-2 px-4 pb-3" id="pcm-footer" style="border-top:1.5px solid var(--bs-border-color);">
                <button type="button" class="btn btn-label-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm px-4" id="pcm-confirm-btn" hidden onclick="pcmConfirm()">
                    <span id="pcm-confirm-label">Confirm</span>
                </button>
            </div>

        </div>
    </div>
</div>

<!-- Payment processing overlay -->
<div id="pcm-pay-overlay" style="display:none;position:fixed;inset:0;z-index:10050;align-items:center;justify-content:center;">
    <div style="position:absolute;inset:0;background:rgba(8,8,20,.72);backdrop-filter:blur(7px);-webkit-backdrop-filter:blur(7px);"></div>
    <div class="pcm-pay-card">
        <div class="pcm-pay-ring-wrap">
            <div class="pcm-pay-ring-spinner"></div>
            <div class="pcm-pay-logo-inner">
                <img src="/images/logo/favicon_io/android-chrome-512x512-1.png" alt="R2kE" />
            </div>
        </div>
        <p class="pcm-pay-title">Secure Payment</p>
        <p id="pcm-pay-msg" class="pcm-pay-msg">Initiating payment…</p>
        <div id="pcm-pay-dots" class="pcm-pay-dots">
            <span></span><span></span><span></span>
        </div>
        <div id="pcm-pay-success-icon" class="pcm-pay-success-icon" style="display:none;">
            <svg viewBox="0 0 52 52" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="26" cy="26" r="25" stroke="url(#pcm-ok-grad)" stroke-width="2" fill="none"/>
                <path d="M14 27l8 8 16-16" stroke="url(#pcm-ok-grad)" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"/>
                <defs>
                    <linearGradient id="pcm-ok-grad" x1="0" y1="0" x2="52" y2="52" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#696cff"/><stop offset="1" stop-color="#10b981"/>
                    </linearGradient>
                </defs>
            </svg>
        </div>
    </div>
</div>

<style>
/* ── Payment processing overlay ─────────────────────────────────── */
.pcm-pay-card {
    position: relative; z-index: 1;
    background: transparent;
    padding: 0;
    text-align: center;
}
:root { --pcm-ov-ring-bg: transparent; }

.pcm-pay-ring-wrap {
    position: relative;
    width: 90px; height: 90px;
    margin: 0 auto 1.5rem;
}
.pcm-pay-ring-spinner {
    position: absolute; inset: 0;
    border-radius: 50%;
    background: conic-gradient(from 0deg, #696cff 0%, #a855f7 30%, #06b6d4 60%, #10b981 80%, #696cff 100%);
    animation: pcm-spin 2s linear infinite;
}
.pcm-pay-logo-inner {
    position: absolute;
    inset: 4px;
    border-radius: 50%;
    background: #fff;
    display: flex; align-items: center; justify-content: center;
    overflow: hidden;
}
.pcm-pay-logo-inner img {
    width: 62px; height: 62px;
    object-fit: contain; padding: 6px;
}
@keyframes pcm-spin { to { transform: rotate(360deg); } }

.pcm-pay-title {
    font-weight: 700; font-size: .95rem; margin-bottom: .35rem; margin-top: 1.5rem;
    background: linear-gradient(135deg, #696cff 0%, #a855f7 100%);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text;
}
.pcm-pay-msg {
    color: rgba(255,255,255,.80);
    font-size: .85rem; margin-bottom: 1.25rem; min-height: 1.2em;
    transition: opacity .3s;
}
.pcm-pay-dots { display: flex; justify-content: center; gap: 7px; margin-top: .25rem; }
.pcm-pay-dots span {
    width: 8px; height: 8px; border-radius: 50%;
    background: linear-gradient(135deg, #696cff, #a855f7);
    animation: pcm-dot 1.2s ease-in-out infinite;
}
.pcm-pay-dots span:nth-child(2) { animation-delay: .2s; }
.pcm-pay-dots span:nth-child(3) { animation-delay: .4s; }
@keyframes pcm-dot {
    0%,80%,100% { transform: scale(.55); opacity: .35; }
    40%          { transform: scale(1);   opacity: 1; }
}
.pcm-pay-success-icon { margin-top: .25rem; }
.pcm-pay-success-icon svg { width: 44px; height: 44px; }

.pcm-option-card {
    border: 1.5px solid var(--bs-border-color,#e0e0e0);
    border-radius: 10px;
    padding: .75rem 1rem;
    cursor: pointer;
    transition: border-color .15s, box-shadow .15s;
    background: var(--bs-card-bg,#fff);
    position: relative;
}
.pcm-option-card:hover { border-color: #696cff; box-shadow: 0 2px 10px rgba(105,108,255,.12); }
.pcm-option-card.selected { border-color: #696cff; box-shadow: 0 0 0 2px rgba(105,108,255,.2); }
.pcm-recommended-badge {
    position: absolute; top: -9px; right: 10px;
    background: #696cff; color: #fff;
    font-size: .62rem; font-weight: 700; text-transform: uppercase;
    padding: .15rem .55rem; border-radius: 99px; letter-spacing: .07em;
}
.pcm-sub-option {
    border: 1.5px solid var(--bs-border-color,#e0e0e0);
    border-radius: 8px; padding: .6rem .9rem; cursor: pointer;
    transition: border-color .15s; margin-top: .5rem;
    background: var(--bs-body-bg,#f9f9f9);
}
.pcm-sub-option:hover { border-color: #696cff; }
.pcm-sub-option.selected { border-color: #696cff; background: rgba(105,108,255,.04); }
.pcm-module-pill {
    display: inline-block;
    font-size: .7rem; font-weight: 600; padding: .15rem .55rem;
    border-radius: 99px; margin: .15rem .1rem;
}
.pcm-module-added   { background: #dcfce7; color: #15803d; }
.pcm-module-removed { background: #fee2e2; color: #b91c1c; }
</style>

<script>
/* ── Tab toggler ────────────────────────────────────────────────────── */
function splSetTab(tab, btn) {
    document.querySelectorAll('.spl-toggle-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.spl-tab-monthly, .spl-tab-yearly').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.spl-tab-' + tab).forEach(el => {
        el.style.display = el.classList.contains('spl-grid') ? 'grid' : 'block';
    });
}

/* ── Plan change modal ──────────────────────────────────────────────── */
const _pcmListDateFormat = '<?php echo addslashes($JwtData->GenSettings->ListDateFormat ?? 'd M Y'); ?>';
let _pcmData   = null;   /* server response */
let _pcmOption = null;   /* selected top-level option key e.g. 'OptionUA' */
let _pcmSubOpt = null;   /* 'DA' or 'DB' for immediate downgrade */
let _pcmSectorPlanUID = 0;

function splOpenChangePlan(sectorPlanUID, changeType) {
    _pcmData           = null;
    _pcmOption         = null;
    _pcmSubOpt         = null;
    _pcmSectorPlanUID  = sectorPlanUID;

    /* Reset all modal sections so modal opens clean when data arrives */
    document.getElementById('pcm-loading').hidden          = true;
    document.getElementById('pcm-locked').hidden           = true;
    document.getElementById('pcm-error').hidden            = true;
    document.getElementById('pcm-scheduled-notice').hidden = true;
    document.getElementById('pcm-modules').hidden          = true;
    document.getElementById('pcm-options').hidden          = true;
    document.getElementById('pcm-detail').hidden           = true;
    document.getElementById('pcm-confirm-btn').hidden      = true;

    const badge = document.getElementById('pcm-change-badge');
    badge.textContent          = changeType;
    badge.style.background     = changeType === 'Upgrade' ? '#696cff' : '#f97316';
    badge.style.color          = '#fff';

    /* Show gradient overlay while fetching — modal stays closed */
    pcmShowOverlay('Loading plan details…');

    fetch('/subscription/getPlanChangeOptions', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
        body: 'sector_plan_uid=' + sectorPlanUID
    })
    .then(r => r.json())
    .then(data => {
        pcmHandleResponse(data);          /* populate modal content */
        pcmHideOverlay();                 /* hide overlay */
        /* Open modal only now — fully populated, no spinner needed */
        new bootstrap.Modal(document.getElementById('planChangeModal')).show();
    })
    .catch(() => {
        pcmHideOverlay();
        alert('Failed to load plan options. Please check your connection and try again.');
    });
}

function pcmHandleResponse(data) {
    if (data.Status === 'LOCKED') {
        /* Show modal so the lock message is visible */
        document.getElementById('pcm-locked').hidden  = false;
        document.getElementById('pcm-locked-msg').textContent =
            'A plan change was made recently. You can make the next change after ' +
            pcmFmtDate(data.UnlocksAt) + ' (' + data.DaysRemaining + ' day(s) remaining). ' +
            'This restriction exists because a GST invoice was already issued.';
        return;
    }
    if (data.Status !== 'OK') {
        /* Show modal with error message */
        document.getElementById('pcm-error').hidden  = false;
        document.getElementById('pcm-error-msg').textContent = data.Message || 'Something went wrong. Please try again.';
        return;
    }

    _pcmData = data;
    const d  = data.Details;

    /* Plan names */
    document.getElementById('pcm-from-name').textContent = d.CurrentPlan?.PlanName || '—';
    document.getElementById('pcm-to-name').textContent   = d.NewPlan?.PlanName     || '—';

    /* Scheduled notice — only show when it has actual plan/date data */
    if (data.Scheduled && data.Scheduled.NewPlanName && data.Scheduled.ScheduledStartDate) {
        const sn  = document.getElementById('pcm-scheduled-notice');
        const sm  = document.getElementById('pcm-scheduled-msg');
        sm.innerHTML = '<strong>Note:</strong> You already have a scheduled plan change to <strong>' +
            pcmEsc(data.Scheduled.NewPlanName) + '</strong> activating on ' +
            pcmFmtDate(data.Scheduled.ScheduledStartDate) + '. Proceeding will create a new change.';
        sn.hidden = false;
    }

    /* Module comparison */
    const mods = data.Modules;
    if (mods) {
        const modWrap = document.getElementById('pcm-modules');
        modWrap.hidden = false;
        if (!mods.HasData) {
            document.getElementById('pcm-modules-empty').hidden = false;
        } else {
            if (mods.Added && mods.Added.length > 0) {
                const wrap = document.getElementById('pcm-modules-added');
                wrap.hidden = false;
                const list = document.getElementById('pcm-modules-added-list');
                list.innerHTML = mods.Added.map(m =>
                    '<span class="pcm-module-pill pcm-module-added">' + pcmEsc(m.DisplayName || m.ModuleUID) + '</span>'
                ).join('');
            }
            if (mods.Removed && mods.Removed.length > 0) {
                const wrap = document.getElementById('pcm-modules-removed');
                wrap.hidden = false;
                const list = document.getElementById('pcm-modules-removed-list');
                list.innerHTML = mods.Removed.map(m =>
                    '<span class="pcm-module-pill pcm-module-removed">' + pcmEsc(m.DisplayName || m.ModuleUID) + '</span>'
                ).join('');
                /* Warn about immediate removal */
                list.innerHTML += '<div class="small mt-1" style="color:#b91c1c;"><i class="bx bx-error-circle me-1"></i>These modules lose access immediately on an instant switch.</div>';
            }
            if ((!mods.Added || mods.Added.length === 0) && (!mods.Removed || mods.Removed.length === 0)) {
                document.getElementById('pcm-modules-empty').hidden = false;
            }
        }
    }

    /* Build option cards */
    pcmBuildOptions(d);

    document.getElementById('pcm-options').hidden  = false;

    /* Auto-select the recommended option so the detail panel shows immediately */
    const defaultOpt = (d.ChangeType === 'Upgrade') ? 'OptionUA' : 'OptionDB';
    const defaultRadio = document.getElementById('pcm-radio-' + defaultOpt);
    if (defaultRadio) {
        defaultRadio.checked = true;
        pcmSelectTop(defaultOpt);
    }
}

function pcmBuildOptions(d) {
    const list = document.getElementById('pcm-options-list');
    list.innerHTML = '';

    if (d.ChangeType === 'Upgrade') {
        list.appendChild(pcmMakeCard('OptionUA', '⚡ Upgrade Today',
            'Switch immediately. Remaining ' + d.RemainingDays + ' days credit (₹' + d.RemainingCredit.toFixed(2) + ') deducted from new plan.',
            true, false));
        list.appendChild(pcmMakeCard('OptionUB', '📅 Upgrade from ' + pcmFmtDate(d.OptionUB.StartDate),
            'Current plan continues until ' + pcmFmtDate(d.CurrentEndDate) + '. New plan queues up automatically. Pay today.',
            false, false));
    } else {
        /* Immediate downgrade card with two sub-options */
        const immCard = document.createElement('div');
        immCard.className = 'pcm-option-card';
        immCard.id = 'pcm-card-immediate';
        immCard.innerHTML = `
            <div class="d-flex align-items-start gap-2">
                <input type="radio" name="pcm-top-opt" id="pcm-radio-immediate" value="immediate" class="mt-1 flex-shrink-0" onchange="pcmSelectTop('immediate')">
                <label for="pcm-radio-immediate" class="w-100" style="cursor:pointer;">
                    <div class="fw-semibold small mb-1">⚡ Switch Today</div>
                    <div class="small text-muted">Switch immediately. Choose how to apply your ₹${d.RemainingCredit.toFixed(2)} remaining credit:</div>
                    <div id="pcm-sub-options" class="mt-2">
                        <div class="pcm-sub-option" id="pcm-sub-DA" onclick="pcmSelectSub('DA',event)">
                            <div class="d-flex align-items-start gap-2">
                                <input type="radio" name="pcm-sub-opt" id="pcm-sub-radio-DA" value="DA" class="mt-1 flex-shrink-0">
                                <label for="pcm-sub-radio-DA" style="cursor:pointer;">
                                    <div class="fw-semibold small">Pay ₹${(d.OptionDA.TotalAmount).toFixed(2)} · Get ${d.OptionDA.TotalDays} days <span class="badge" style="background:#dcfce7;color:#15803d;font-size:.65rem;">+${d.OptionDA.BonusDays} bonus days</span></div>
                                    <div class="small text-muted">Pay full plan price. Credit converted to ${d.OptionDA.BonusDays} extra days. Ends ${pcmFmtDate(d.OptionDA.EndDate)}.</div>
                                </label>
                            </div>
                        </div>
                        <div class="pcm-sub-option" id="pcm-sub-DB" onclick="pcmSelectSub('DB',event)">
                            <div class="d-flex align-items-start gap-2">
                                <input type="radio" name="pcm-sub-opt" id="pcm-sub-radio-DB" value="DB" class="mt-1 flex-shrink-0">
                                <label for="pcm-sub-radio-DB" style="cursor:pointer;">
                                    <div class="fw-semibold small">Pay ₹${(d.OptionDB.TotalAmount).toFixed(2)} · Standard 30 days <span class="badge" style="background:#dbeafe;color:#1d4ed8;font-size:.65rem;">Recommended</span></div>
                                    <div class="small text-muted">Credit deducted from plan price. Pay less, get standard period. Ends ${pcmFmtDate(d.OptionDB.EndDate)}.</div>
                                </label>
                            </div>
                        </div>
                    </div>
                </label>
            </div>`;
        list.appendChild(immCard);

        list.appendChild(pcmMakeCard('OptionDC', '📅 Switch from ' + pcmFmtDate(d.OptionDC.StartDate),
            'Current plan continues until ' + pcmFmtDate(d.CurrentEndDate) + '. Downgrade activates automatically. Pay today.',
            true, false));
    }
}

function pcmMakeCard(optionKey, title, desc, recommended, selected) {
    const card = document.createElement('div');
    card.className = 'pcm-option-card' + (selected ? ' selected' : '');
    card.id = 'pcm-card-' + optionKey;
    if (recommended) {
        const badge = document.createElement('span');
        badge.className = 'pcm-recommended-badge';
        badge.textContent = 'Recommended';
        card.appendChild(badge);
    }
    card.innerHTML += `
        <div class="d-flex align-items-start gap-2">
            <input type="radio" name="pcm-top-opt" id="pcm-radio-${optionKey}" value="${optionKey}" class="mt-1 flex-shrink-0"
                onchange="pcmSelectTop('${optionKey}')">
            <label for="pcm-radio-${optionKey}" style="cursor:pointer;">
                <div class="fw-semibold small mb-1">${pcmEsc(title)}</div>
                <div class="small text-muted">${pcmEsc(desc)}</div>
            </label>
        </div>`;
    card.onclick = (e) => {
        if (!e.target.closest('input')) {
            document.getElementById('pcm-radio-' + optionKey).click();
        }
    };
    return card;
}

function pcmSelectTop(value) {
    document.querySelectorAll('.pcm-option-card').forEach(c => c.classList.remove('selected'));
    const card = (value === 'immediate')
        ? document.getElementById('pcm-card-immediate')
        : document.getElementById('pcm-card-' + value);
    if (card) card.classList.add('selected');

    _pcmOption = value;
    _pcmSubOpt = null;

    if (value === 'immediate') {
        /* Require sub-option selection */
        pcmUpdateDetail(null);
        document.getElementById('pcm-confirm-btn').hidden = true;
    } else {
        /* value is the full key e.g. 'OptionUA', 'OptionUB', 'OptionDC' */
        pcmUpdateDetail(_pcmData.Details[value]);
        document.getElementById('pcm-confirm-btn').hidden = false;
        pcmSetConfirmLabel(value);
    }
}

function pcmSelectSub(subOpt, e) {
    e && e.stopPropagation();
    /* Make sure parent radio is checked */
    const parentRadio = document.getElementById('pcm-radio-immediate');
    if (parentRadio) { parentRadio.checked = true; pcmSelectTop('immediate'); }

    document.querySelectorAll('.pcm-sub-option').forEach(s => s.classList.remove('selected'));
    const subCard = document.getElementById('pcm-sub-' + subOpt);
    if (subCard) subCard.classList.add('selected');
    const subRadio = document.getElementById('pcm-sub-radio-' + subOpt);
    if (subRadio) subRadio.checked = true;

    _pcmSubOpt = subOpt;
    pcmUpdateDetail(_pcmData.Details['Option' + subOpt]);
    document.getElementById('pcm-confirm-btn').hidden = false;
    pcmSetConfirmLabel(subOpt);
}

function pcmUpdateDetail(opt) {
    const panel = document.getElementById('pcm-detail');
    if (!opt) { panel.hidden = true; return; }
    panel.hidden = false;

    document.getElementById('pcm-d-start').textContent   = pcmFmtDate(opt.StartDate);
    document.getElementById('pcm-d-end').textContent     = pcmFmtDate(opt.EndDate);
    document.getElementById('pcm-d-days').textContent    = (opt.TotalDays || _pcmData.Details.NewPlan?.DurationDays || 30) + ' days';
    document.getElementById('pcm-d-credit').textContent  = opt.CreditUsed > 0 ? '₹' + opt.CreditUsed.toFixed(2) : 'None';
    document.getElementById('pcm-d-base').textContent    = '₹' + opt.BaseAmount.toFixed(2);
    document.getElementById('pcm-d-taxrate').textContent = opt.TaxRate || 18;
    document.getElementById('pcm-d-tax').textContent     = '₹' + opt.TaxAmount.toFixed(2);
    document.getElementById('pcm-d-total').textContent   = '₹' + opt.TotalAmount.toFixed(2);
}

function pcmSetConfirmLabel(optKey) {
    const labels = {
        OptionUA: 'Upgrade Now', OptionUB: 'Schedule Upgrade',
        DA: 'Downgrade Now', DB: 'Downgrade Now', OptionDC: 'Schedule Downgrade'
    };
    document.getElementById('pcm-confirm-label').textContent = labels[optKey] || 'Confirm';
}

function pcmConfirm() {
    const effectiveOpt = (_pcmOption === 'immediate') ? _pcmSubOpt : _pcmOption;
    if (!effectiveOpt) { alert('Please select an option.'); return; }

    const optMap = { OptionUA: 'UA', OptionUB: 'UB', DA: 'DA', DB: 'DB', OptionDC: 'DC' };
    const optionType = optMap[effectiveOpt];
    if (!optionType) { alert('Please select an option.'); return; }

    const resolvedOpt = ['DA','DB'].includes(optionType)
        ? _pcmData.Details['Option' + optionType]
        : _pcmData.Details[effectiveOpt];

    const btn        = document.getElementById('pcm-confirm-btn');
    const labelText  = document.getElementById('pcm-confirm-label')?.textContent || 'Confirm';
    btn.disabled     = true;

    /* Show full-screen processing overlay */
    pcmShowOverlay('Initiating secure payment…');

    /* Step 1: Create Razorpay order */
    fetch('/subscription/initiatePayment', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
        body: 'sector_plan_uid=' + _pcmSectorPlanUID + '&option_type=' + encodeURIComponent(optionType)
    })
    .then(r => r.json())
    .then(ord => {
        btn.disabled = false;

        if (ord.Error) {
            pcmHideOverlay();
            alert(ord.Message || 'Could not initiate payment. Please try again.');
            return;
        }

        /* Overlay hides while Razorpay checkout is open */
        pcmHideOverlay();

        /* Step 2: Open Razorpay checkout */
        const rzp = new Razorpay({
            key:         ord.key_id,
            amount:      ord.amount,
            currency:    ord.currency,
            name:        ord.name,
            description: ord.description,
            order_id:    ord.order_id,
            prefill:     ord.prefill || {},
            theme:       { color: '#696cff' },
            handler: function(response) {
                /* Payment done — show overlay again while we verify + process */
                pcmShowOverlay('Payment received. Processing your plan…');

                /* Step 3: Confirm plan change after payment verified */
                let body = 'sector_plan_uid='       + _pcmSectorPlanUID +
                    '&option_type='                 + encodeURIComponent(optionType) +
                    '&amount='                      + encodeURIComponent(resolvedOpt?.TotalAmount || 0) +
                    '&tax='                         + encodeURIComponent(resolvedOpt?.TaxAmount || 0) +
                    '&razorpay_order_id='           + encodeURIComponent(response.razorpay_order_id) +
                    '&razorpay_payment_id='         + encodeURIComponent(response.razorpay_payment_id) +
                    '&razorpay_signature='          + encodeURIComponent(response.razorpay_signature);

                if (['UB','DC'].includes(optionType) && resolvedOpt) {
                    body += '&scheduled_start=' + encodeURIComponent(resolvedOpt.StartDate) +
                            '&scheduled_end='   + encodeURIComponent(resolvedOpt.EndDate);
                }

                fetch('/subscription/confirmPlanChange', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
                    body: body
                })
                .then(r => r.json())
                .then(res => {
                    if (res.Status === 'OK') {
                        bootstrap.Modal.getInstance(document.getElementById('planChangeModal'))?.hide();
                        pcmSuccessOverlay(res.Message || 'Plan changed successfully!');
                        setTimeout(() => { pcmHideOverlay(); location.reload(); }, 2000);
                    } else {
                        pcmHideOverlay();
                        alert(res.Message || 'Something went wrong. Please try again.');
                    }
                })
                .catch(() => {
                    pcmHideOverlay();
                    alert('Plan change confirmation failed. Please contact support if amount was deducted.');
                });
            }
        });
        rzp.open();
    })
    .catch(() => {
        btn.disabled = false;
        pcmHideOverlay();
        alert('Request failed. Please check your connection and try again.');
    });
}

/* ── Payment overlay helpers ─────────────────────────────────────── */
function pcmShowOverlay(msg) {
    document.getElementById('pcm-pay-msg').textContent = msg;
    document.getElementById('pcm-pay-dots').style.display = 'flex';
    document.getElementById('pcm-pay-success-icon').style.display = 'none';
    document.querySelector('.pcm-pay-ring-spinner').style.animationPlayState = 'running';
    document.getElementById('pcm-pay-overlay').style.display = 'flex';
}
function pcmUpdateOverlay(msg) {
    const el = document.getElementById('pcm-pay-msg');
    el.style.opacity = '0';
    setTimeout(() => { el.textContent = msg; el.style.opacity = '1'; }, 200);
}
function pcmSuccessOverlay(msg) {
    pcmUpdateOverlay(msg);
    document.getElementById('pcm-pay-dots').style.display = 'none';
    document.querySelector('.pcm-pay-ring-spinner').style.animationPlayState = 'paused';
    document.getElementById('pcm-pay-success-icon').style.display = 'block';
}
function pcmHideOverlay() {
    document.getElementById('pcm-pay-overlay').style.display = 'none';
}

function pcmShowError(msg) {
    document.getElementById('pcm-loading').hidden = true;
    document.getElementById('pcm-error').hidden   = false;
    document.getElementById('pcm-error-msg').textContent = msg;
}

function pcmFmtDate(d) {
    if (!d) return '—';
    const parts = String(d).split('-');
    if (parts.length < 3) return d;
    const day = parseInt(parts[2], 10);
    const mon = parseInt(parts[1], 10) - 1;
    const yr  = parseInt(parts[0], 10);
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const fmt = _pcmListDateFormat || 'd M Y';
    return fmt
        .replace('d', String(day).padStart(2, '0'))
        .replace('j', String(day))
        .replace('M', months[mon])
        .replace('m', String(mon + 1).padStart(2, '0'))
        .replace('Y', String(yr))
        .replace('y', String(yr).slice(-2));
}

function pcmEsc(s) {
    return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* Shadow on header when body is scrolled */
document.getElementById('planChangeModal').addEventListener('shown.bs.modal', function () {
    const body   = this.querySelector('.modal-body');
    const header = document.getElementById('pcm-header');
    if (!body || !header) return;
    body.addEventListener('scroll', function () {
        header.style.boxShadow = this.scrollTop > 4
            ? '0 4px 12px rgba(0,0,0,.10)'
            : 'none';
    });
});
</script>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
