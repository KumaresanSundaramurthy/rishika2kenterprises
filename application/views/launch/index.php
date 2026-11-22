<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>R2K ERP — Business Management Software</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700;800&family=Inter:wght@300;400;500;600;700&display=swap">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
<link rel="stylesheet" href="/assets/css/launch-index.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>

<?php
$_su     = $signupUrl ?? '#';
$_lu     = $loginUrl  ?? '#';
$_cur    = '₹';
$_eq     = $enquiryEndpoint ?? '';
$_svcUrl  = rtrim(getenv('SERVICES_BASE_URL') ?: '', '/') . '/';
$_pubTok  = getenv('PUBLIC_SERVICE_TOKEN') ?: '';
$_upUrl   = rtrim(getenv('UPSTASH_REDIS_REST_URL') ?: '', '/');
$_upTok   = getenv('UPSTASH_REDIS_REST_TOKEN') ?: '';
$_p      = $plansByCycle ?? [];
$_pm     = $planModules  ?? [];
$_hM     = !empty($_p['monthly']);
$_hY     = !empty($_p['yearly']);
$_hF     = !empty($_p['free']);
$_any    = !empty($_p);
$_popIdx = 1;
$_cycDef = $_hY ? 'yearly' : 'monthly';

/* Calculate actual savings % by matching monthly vs yearly plans positionally */
$_savePct = 0;
if ($_hM && $_hY) {
    $mPlans = array_values($_p['monthly']);
    $yPlans = array_values($_p['yearly']);
    $pairs  = min(count($mPlans), count($yPlans));
    $total  = 0;
    $count  = 0;
    for ($i = 0; $i < $pairs; $i++) {
        $mAnnual = (float)($mPlans[$i]->TotalAmount ?: $mPlans[$i]->Price ?: 0) * 12;
        $yCost   = (float)($yPlans[$i]->TotalAmount ?: $yPlans[$i]->Price ?: 0);
        if ($mAnnual > 0 && $yCost < $mAnnual) {
            $total += (($mAnnual - $yCost) / $mAnnual) * 100;
            $count++;
        }
    }
    if ($count > 0) {
        $_savePct = (int)round($total / $count);
    }
}
?>

<!-- â”€â”€ NAVBAR â”€â”€ -->
<nav class="nav" id="mainNav">
    <div class="nav-logo">
        <div class="nav-mark"><img src="/images/logo/favicon_io/android-chrome-512x512-1.png" alt="R2K ERP"></div>
        <span>R2K ERP</span>
    </div>
    <ul class="nav-links">
        <li><a href="#features">Features</a></li>
        <li><a href="#modules">Modules</a></li>
        <li><a href="#pricing">Pricing</a></li>
        <li><a href="#how">How it works</a></li>
    </ul>
    <div class="nav-ctas">
        <button class="btn btn-enquiry" id="navEnquiryBtn"><i class="bx bx-envelope"></i> Enquiry</button>
        <a href="<?= htmlspecialchars($_lu) ?>" class="btn btn-ghost">Sign In</a>
        <a href="<?= htmlspecialchars($_su) ?>" class="btn btn-pri">Start Free Trial</a>
    </div>
</nav>

<!-- ── ENQUIRY MODAL ── -->
<div class="enq-overlay" id="enqOverlay" aria-hidden="true">
    <div class="enq-modal" role="dialog" aria-modal="true" aria-labelledby="enqModalTitle">
        <div class="enq-header">
            <div>
                <div class="enq-title" id="enqModalTitle">Send an Enquiry</div>
                <div class="enq-sub">We'll get back to you within 24 hours.</div>
            </div>
            <button class="enq-close" id="enqCloseBtn" aria-label="Close"><i class="bx bx-x"></i></button>
        </div>
        <form id="enqForm" novalidate>
            <div class="enq-field">
                <label for="enqName">Full Name <span class="enq-req">*</span></label>
                <input type="text" id="enqName" name="name" class="enq-input" placeholder="Your name" autocomplete="name">
                <div class="enq-err" id="enqNameErr"></div>
            </div>
            <div class="enq-field">
                <label>Phone <span class="enq-req">*</span></label>
                <div class="enq-phone-wrap">
                    <div class="enq-cc-wrap">
                        <button type="button" class="enq-cc-btn" id="enqCCBtn">India +91</button>
                        <div class="enq-cc-dropdown" id="enqCCDropdown">
                            <input type="text" class="enq-cc-search" id="enqCCSearch" placeholder="Search country...">
                            <div class="enq-cc-list" id="enqCCList"></div>
                        </div>
                    </div>
                    <input type="tel" id="enqPhone" name="phone" class="enq-input enq-phone-num" placeholder="98765 43210" autocomplete="tel">
                    <input type="hidden" id="enqPhoneCode" value="+91">
                    <input type="hidden" id="enqPhoneISO2" value="IN">
                </div>
                <div class="enq-err" id="enqPhoneErr"></div>
            </div>
            <div class="enq-field">
                <label for="enqEmail">Email Address <span class="enq-req">*</span></label>
                <input type="email" id="enqEmail" name="email" class="enq-input" placeholder="you@company.com" autocomplete="email">
                <div class="enq-err" id="enqEmailErr"></div>
            </div>
            <div class="enq-field">
                <label for="enqMessage">Message <span class="enq-req">*</span></label>
                <textarea id="enqMessage" name="message" class="enq-input enq-textarea" placeholder="Tell us about your business and what you're looking for…" rows="4"></textarea>
                <div class="enq-err" id="enqMessageErr"></div>
            </div>
            <div class="enq-footer">
                <div class="enq-status" id="enqStatus"></div>
                <button type="submit" class="enq-submit" id="enqSubmitBtn">
                    <i class="bx bx-send"></i> Send Enquiry
                </button>
            </div>
        </form>
    </div>
</div>

<!-- â”€â”€ HERO â”€â”€ -->
<section class="hero">
    <div class="hero-bg">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>
    <div class="hero-grid"></div>
    <div class="hero-inner">
        <!-- Text -->
        <div class="hero-text">
            <div class="hero-badge"><span class="badge-dot"></span>Complete Business ERP for India</div>
            <h1>Manage Your<br>Business with<br><em>Confidence</em></h1>
            <p class="hero-sub">
                From invoicing to payroll, from inventory to accounting —
                every tool your growing business needs, unified in one
                GST-compliant cloud platform.
            </p>
            <div class="hero-ctas">
                <a href="<?= htmlspecialchars($_su) ?>" class="btn btn-pri btn-lg">
                    <i class="bx bx-rocket"></i> Start Free Trial
                </a>
                <a href="#pricing" class="btn btn-out btn-lg">
                    <i class="bx bx-tag"></i> See Pricing
                </a>
            </div>
            <div class="hero-proof">
                <div class="proof-item"><i class="bx bx-check-circle"></i> No credit card required</div>
                <div class="proof-item"><i class="bx bx-check-circle"></i> GST compliant</div>
                <div class="proof-item"><i class="bx bx-check-circle"></i> Setup in minutes</div>
            </div>
        </div>
        <!-- App mockup -->
        <div class="hero-visual">
            <div class="mock-wrap">
                <div class="mockup">
                    <div class="m-bar">
                        <div class="m-dot m-dot-r"></div>
                        <div class="m-dot m-dot-y"></div>
                        <div class="m-dot m-dot-g"></div>
                        <div class="m-bar-ttl">R2K ERP &nbsp;Â·&nbsp; Invoices</div>
                    </div>
                    <div class="m-body">
                        <div class="m-side">
                            <div class="m-logo"><img src="/images/logo/favicon_io/android-chrome-512x512-1.png" alt="R2K"></div>
                            <div class="m-ico m-ico-a"><i class="bx bx-receipt"></i></div>
                            <div class="m-ico"><i class="bx bx-box"></i></div>
                            <div class="m-ico"><i class="bx bx-group"></i></div>
                            <div class="m-ico"><i class="bx bx-bar-chart-alt-2"></i></div>
                            <div class="m-ico"><i class="bx bx-cog"></i></div>
                        </div>
                        <div class="m-main">
                            <div class="m-hdr">
                                <span class="m-pg-ttl">Invoices</span>
                                <span class="m-btn-sm"><i class="bx bx-plus"></i> New Invoice</span>
                            </div>
                            <div class="m-stats">
                                <div class="m-st">
                                    <div class="m-st-lbl">This Month</div>
                                    <div class="m-st-val">₹4.2L</div>
                                    <div class="m-st-up">+18%</div>
                                </div>
                                <div class="m-st">
                                    <div class="m-st-lbl">Pending</div>
                                    <div class="m-st-val">₹86K</div>
                                    <div class="m-st-dn">3 due</div>
                                </div>
                                <div class="m-st">
                                    <div class="m-st-lbl">Collected</div>
                                    <div class="m-st-val">₹3.3L</div>
                                    <div class="m-st-up">94%</div>
                                </div>
                            </div>
                            <div class="m-thead">
                                <div>Invoice</div><div>Customer</div>
                                <div>Amount</div><div>Status</div>
                            </div>
                            <div class="m-row"><div class="m-id">#INV-0091</div><div>Lakshmi Traders</div><div>₹18,400</div><div><span class="m-bdg m-bdg-p">Paid</span></div></div>
                            <div class="m-row"><div class="m-id">#INV-0090</div><div>Sun Industries</div><div>₹42,200</div><div><span class="m-bdg m-bdg-w">Pending</span></div></div>
                            <div class="m-row"><div class="m-id">#INV-0089</div><div>Nova Tech</div><div>₹9,850</div><div><span class="m-bdg m-bdg-p">Paid</span></div></div>
                            <div class="m-row"><div class="m-id">#INV-0088</div><div>Ravi &amp; Sons</div><div>₹26,000</div><div><span class="m-bdg m-bdg-o">Overdue</span></div></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mock-glow"></div>
        </div>
    </div>
</section>

<!-- â”€â”€ TRUST â”€â”€ -->
<div class="trust">
    <div class="trust-inner">
        <span class="trust-lbl">Trusted by businesses across India</span>
        <div class="trust-line"></div>
        <div class="trust-chips">
            <span class="trust-chip">Retail</span>
            <span class="trust-chip">Manufacturing</span>
            <span class="trust-chip">Trading</span>
            <span class="trust-chip">Services</span>
            <span class="trust-chip">Distribution</span>
        </div>
    </div>
</div>

<!-- â”€â”€ STATS â”€â”€ -->
<div class="stats-wrap">
    <div class="stats-band rv">
        <div class="stat-cell">
            <div class="stat-n"><span class="counter" data-target="15">0</span><span class="stat-n-suf">+</span></div>
            <div class="stat-l">ERP Modules</div>
        </div>
        <div class="stat-cell">
            <div class="stat-n"><span class="counter" data-target="100">0</span><span class="stat-n-suf">%</span></div>
            <div class="stat-l">Cloud Based</div>
        </div>
        <div class="stat-cell">
            <div class="stat-n"><span class="stat-n-suf">₹0</span></div>
            <div class="stat-l">Setup Cost</div>
        </div>
        <div class="stat-cell">
            <div class="stat-n"><span class="counter" data-target="24">0</span><span class="stat-n-suf">/7</span></div>
            <div class="stat-l">Support</div>
        </div>
    </div>
</div>

<!-- â”€â”€ FEATURES â”€â”€ -->
<div class="feat-section" id="features">

    <!-- Block 1: Invoicing -->
    <div class="feat-blk rv-l">
        <div class="feat-txt">
            <div class="feat-eye">Sales &amp; Invoicing</div>
            <h2 class="feat-h">Create invoices in seconds, get paid faster</h2>
            <p class="feat-p">Generate GST-compliant invoices, track payment status in real time, and follow up on overdue receivables — all from one screen.</p>
            <ul class="feat-chk">
                <li><i class="bx bx-check"></i>Auto-calculate CGST, SGST &amp; IGST based on party state</li>
                <li><i class="bx bx-check"></i>Convert quotations and orders to invoices in one click</li>
                <li><i class="bx bx-check"></i>Attach files, record payments, and send PDF via email</li>
                <li><i class="bx bx-check"></i>Customer-wise outstanding and aging reports</li>
            </ul>
            <a href="<?= htmlspecialchars($_su) ?>" class="btn btn-pri">Get Started <i class="bx bx-right-arrow-alt"></i></a>
        </div>
        <div class="feat-vis rv-r">
            <div class="feat-ico-lg"><i class="bx bx-receipt"></i></div>
            <div class="feat-vis-ttl">Sales Workflow</div>
            <ul class="feat-mini">
                <li><i class="bx bx-file"></i>Quotation <span class="feat-mini-tag tag-new">Draft</span></li>
                <li><i class="bx bx-store"></i>Sales Order <span class="feat-mini-tag tag-auto">Auto</span></li>
                <li><i class="bx bx-receipt"></i>Invoice <span class="feat-mini-tag tag-gst">GST</span></li>
                <li><i class="bx bx-revision"></i>Credit Note <span class="feat-mini-tag tag-auto">Returns</span></li>
                <li><i class="bx bx-money"></i>Payment Recording</li>
            </ul>
        </div>
    </div>

    <!-- Block 2: Inventory -->
    <div class="feat-blk feat-blk-flip rv-r">
        <div class="feat-txt">
            <div class="feat-eye">Inventory Management</div>
            <h2 class="feat-h">Never run out of stock again</h2>
            <p class="feat-p">Track every item across multiple branches in real time. Set reorder alerts, manage batches, and get stock valuation reports instantly.</p>
            <ul class="feat-chk">
                <li><i class="bx bx-check"></i>Real-time stock levels across all branches</li>
                <li><i class="bx bx-check"></i>Reorder point alerts and low-stock notifications</li>
                <li><i class="bx bx-check"></i>Automatic stock updates on every transaction</li>
                <li><i class="bx bx-check"></i>Product-wise profit margin tracking</li>
            </ul>
            <a href="<?= htmlspecialchars($_su) ?>" class="btn btn-pri">Get Started <i class="bx bx-right-arrow-alt"></i></a>
        </div>
        <div class="feat-vis rv-l">
            <div class="feat-ico-lg"><i class="bx bx-package"></i></div>
            <div class="feat-vis-ttl">Stock at a Glance</div>
            <ul class="feat-mini">
                <li><i class="bx bx-box"></i>Product Catalog &amp; Categories</li>
                <li><i class="bx bx-transfer"></i>Purchase &amp; GRN Tracking <span class="feat-mini-tag tag-auto">Auto</span></li>
                <li><i class="bx bx-trending-down"></i>Low Stock Alerts <span class="feat-mini-tag tag-new">Smart</span></li>
                <li><i class="bx bx-building-house"></i>Multi-Branch Stock View</li>
                <li><i class="bx bx-bar-chart-alt-2"></i>Stock Valuation Report</li>
            </ul>
        </div>
    </div>

    <!-- Block 3: Accounting -->
    <div class="feat-blk rv-l">
        <div class="feat-txt">
            <div class="feat-eye">Accounting &amp; Finance</div>
            <h2 class="feat-h">Full accounting built right in</h2>
            <p class="feat-p">Double-entry accounting, bank reconciliation, P&amp;L, and balance sheet — no external accountant software needed.</p>
            <ul class="feat-chk">
                <li><i class="bx bx-check"></i>Manual journal entries with multi-ledger support</li>
                <li><i class="bx bx-check"></i>Bank reconciliation with statement upload</li>
                <li><i class="bx bx-check"></i>Profit &amp; Loss, Balance Sheet, Cash Flow</li>
                <li><i class="bx bx-check"></i>Recurring journal automation</li>
            </ul>
            <a href="<?= htmlspecialchars($_su) ?>" class="btn btn-pri">Get Started <i class="bx bx-right-arrow-alt"></i></a>
        </div>
        <div class="feat-vis rv-r">
            <div class="feat-ico-lg"><i class="bx bx-line-chart"></i></div>
            <div class="feat-vis-ttl">Finance Reports</div>
            <ul class="feat-mini">
                <li><i class="bx bx-book-open"></i>Day Book &amp; Ledgers <span class="feat-mini-tag tag-gst">GST</span></li>
                <li><i class="bx bx-building-house"></i>Bank Reconciliation <span class="feat-mini-tag tag-auto">Auto</span></li>
                <li><i class="bx bx-trending-up"></i>Profit &amp; Loss Statement</li>
                <li><i class="bx bx-stats"></i>Balance Sheet</li>
                <li><i class="bx bx-transfer-alt"></i>Cash Flow Report</li>
            </ul>
        </div>
    </div>
</div>

<!-- â”€â”€ MODULES â”€â”€ -->
<section class="section sec-alt" id="modules">
    <div class="sec-center rv">
        <div class="sec-eye">Everything included</div>
        <h2 class="sec-title">15+ modules. One subscription.</h2>
        <p class="sec-sub">From first quotation to final report — every business process covered.</p>
    </div>
    <div class="mods-grid">
        <div class="mod-card rv d1"><div class="mod-ico ico-pri"><i class="bx bx-receipt"></i></div><div class="mod-nm">Invoicing</div><div class="mod-ds">GST-compliant invoices with payment tracking.</div></div>
        <div class="mod-card rv d2"><div class="mod-ico ico-amb"><i class="bx bx-file"></i></div><div class="mod-nm">Quotations</div><div class="mod-ds">Professional quotes, convert to order in one click.</div></div>
        <div class="mod-card rv d3"><div class="mod-ico ico-grn"><i class="bx bx-store"></i></div><div class="mod-nm">Sales Orders</div><div class="mod-ds">Full order lifecycle from confirmation to delivery.</div></div>
        <div class="mod-card rv d4"><div class="mod-ico ico-blu"><i class="bx bx-cart"></i></div><div class="mod-nm">Purchase Orders</div><div class="mod-ds">Raise POs, track vendor deliveries easily.</div></div>
        <div class="mod-card rv d1"><div class="mod-ico ico-pur"><i class="bx bx-package"></i></div><div class="mod-nm">Purchase &amp; GRN</div><div class="mod-ds">Record purchases and goods receipt notes.</div></div>
        <div class="mod-card rv d2"><div class="mod-ico ico-red"><i class="bx bx-revision"></i></div><div class="mod-nm">Sales Returns</div><div class="mod-ds">Credit notes and returns with stock update.</div></div>
        <div class="mod-card rv d3"><div class="mod-ico ico-amb"><i class="bx bx-transfer"></i></div><div class="mod-nm">Purchase Returns</div><div class="mod-ds">Return goods to suppliers, track debit notes.</div></div>
        <div class="mod-card rv d4"><div class="mod-ico ico-grn"><i class="bx bx-user-circle"></i></div><div class="mod-nm">Customers</div><div class="mod-ds">Profiles, balances, history and groups.</div></div>
        <div class="mod-card rv d1"><div class="mod-ico ico-blu"><i class="bx bx-buildings"></i></div><div class="mod-nm">Vendors</div><div class="mod-ds">Directory, payables and outstanding amounts.</div></div>
        <div class="mod-card rv d2"><div class="mod-ico ico-pri"><i class="bx bx-box"></i></div><div class="mod-nm">Products &amp; Stock</div><div class="mod-ds">Catalog, stock levels and reorder management.</div></div>
        <div class="mod-card rv d3"><div class="mod-ico ico-pur"><i class="bx bx-line-chart"></i></div><div class="mod-nm">Accounting</div><div class="mod-ds">Journals, reconciliation, P&amp;L, balance sheet.</div></div>
        <div class="mod-card rv d4"><div class="mod-ico ico-red"><i class="bx bx-money-withdraw"></i></div><div class="mod-nm">Expenses</div><div class="mod-ds">Record and categorise business expenses.</div></div>
        <div class="mod-card rv d1"><div class="mod-ico ico-grn"><i class="bx bx-trending-up"></i></div><div class="mod-nm">Indirect Income</div><div class="mod-ds">Track rent, interest and other non-sale income.</div></div>
        <div class="mod-card rv d2"><div class="mod-ico ico-amb"><i class="bx bx-group"></i></div><div class="mod-nm">HRMS</div><div class="mod-ds">Employees, roles, departments and access.</div></div>
        <div class="mod-card rv d3"><div class="mod-ico ico-blu"><i class="bx bx-bar-chart-alt-2"></i></div><div class="mod-nm">Reports</div><div class="mod-ds">Comprehensive reports — PDF, Excel, CSV export.</div></div>
    </div>
</section>

<!-- â”€â”€ HOW IT WORKS â”€â”€ -->
<section class="section" id="how">
    <div class="sec-center rv">
        <div class="sec-eye">Simple Onboarding</div>
        <h2 class="sec-title">Up and running in 3 steps</h2>
        <p class="sec-sub">No complicated setup. No IT team needed. Start managing your business today.</p>
    </div>
    <div class="proc-grid rv">
        <div class="proc-step">
            <div class="proc-n">01</div>
            <div class="proc-ico"><i class="bx bx-user-plus"></i></div>
            <div class="proc-ttl">Create your account</div>
            <div class="proc-p">Sign up for free in under 2 minutes. Add your business name, GST number, and you're ready. No credit card required.</div>
        </div>
        <div class="proc-step">
            <div class="proc-n">02</div>
            <div class="proc-ico"><i class="bx bx-customize"></i></div>
            <div class="proc-ttl">Configure your workspace</div>
            <div class="proc-p">Set up your products, customers, and vendors. Import existing data from Excel. Our onboarding guide walks you through each step.</div>
        </div>
        <div class="proc-step">
            <div class="proc-n">03</div>
            <div class="proc-ico"><i class="bx bx-rocket"></i></div>
            <div class="proc-ttl">Start transacting</div>
            <div class="proc-p">Create your first invoice, purchase order, or expense. Every transaction updates your inventory, accounts, and reports automatically.</div>
        </div>
    </div>
</section>

<!-- â”€â”€ PRICING â”€â”€ -->
<section class="section sec-alt" id="pricing">
    <div class="sec-center rv">
        <div class="sec-eye">Transparent Pricing</div>
        <h2 class="sec-title">Plans for every stage of growth</h2>
        <p class="sec-sub">Start free, scale as you grow. No hidden charges. Cancel anytime.</p>
    </div>

    <?php if ($_any && ($_hM || $_hY)): ?>
    <div class="pricing-tog rv">
        <span class="tog-lbl <?= $_hY ? '' : 'act' ?>" id="lblM">Monthly</span>
        <button class="tog-sw <?= $_hY ? 'on' : '' ?>" id="togBtn"><div class="tog-knob"></div></button>
        <span class="tog-lbl <?= $_hY ? 'act' : '' ?>" id="lblY">Yearly &nbsp;<span class="save-pill">Save <?= $_savePct ?>%</span></span>
    </div>
    <?php endif; ?>

    <?php if ($_any): ?>
    <div class="plans-grid" id="plansGrid">

        <?php if ($_hF): foreach ($_p['free'] as $plan):
            $mk = $plan->PlanUID . '_' . $plan->SectorUID;
            $mods = $_pm[$mk] ?? [];
        ?>
        <div class="plan-card rv d1">
            <div class="plan-cyc">Free</div>
            <div class="plan-nm"><?= htmlspecialchars($plan->PlanName) ?></div>
            <div class="plan-tag">Perfect for exploring the platform</div>
            <div class="plan-pr">
                <span class="plan-pr-cur"><?= $_cur ?></span>
                <span class="plan-pr-amt">0</span>
                <span class="plan-pr-per">/ forever</span>
            </div>
            <div class="plan-note">No credit card required</div>
            <?php if (!empty($plan->MaxUsers) || !empty($plan->MaxBranches)): ?>
            <div class="plan-lims">
                <?php if (!empty($plan->MaxUsers)): ?><span class="plan-lim"><i class="bx bx-user"></i><?= (int)$plan->MaxUsers ?> Users</span><?php endif; ?>
                <?php if (!empty($plan->MaxBranches)): ?><span class="plan-lim"><i class="bx bx-building"></i><?= (int)$plan->MaxBranches ?> Branch</span><?php endif; ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($mods)): ?>
            <div class="plan-div"></div>
            <ul class="plan-mods">
                <?php foreach (array_slice($mods, 0, 6) as $m): ?><li><i class="bx bx-check-circle"></i><?= htmlspecialchars($m->DisplayName) ?></li><?php endforeach; ?>
                <?php if (count($mods) > 6): ?><li class="more">+<?= count($mods)-6 ?> more included</li><?php endif; ?>
            </ul>
            <?php endif; ?>
            <a href="<?= htmlspecialchars($_su) ?>" class="plan-btn p-btn-free">Get Started Free</a>
        </div>
        <?php endforeach; endif; ?>

        <?php foreach (['monthly','yearly'] as $cyc):
            if (empty($_p[$cyc])) continue;
            $cyc_plans = $_p[$cyc];
            $cnt = count($cyc_plans);
        ?>
        <div class="cyc-blk <?= ($_hY ? $cyc === 'yearly' : $cyc === 'monthly') ? 'vis' : '' ?>" id="blk_<?= $cyc ?>">
        <?php foreach ($cyc_plans as $idx => $plan):
            $isPop = ($idx === min($_popIdx, $cnt - 1));
            $mk    = $plan->PlanUID . '_' . $plan->SectorUID;
            $mods  = $_pm[$mk] ?? [];
            $price = (float)($plan->TotalAmount ?: $plan->Price ?: 0);
            $dur   = (int)($plan->DurationDays ?? 30);
            $per   = $cyc === 'yearly' ? '/ year' : '/ month';
        ?>
        <div class="plan-card <?= $isPop ? 'pop' : '' ?> rv d<?= min($idx+1,4) ?>">
            <div class="plan-cyc"><?= ucfirst($cyc) ?></div>
            <div class="plan-nm"><?= htmlspecialchars($plan->PlanName) ?></div>
            <div class="plan-tag"><?= $cyc === 'yearly' ? 'Best value — pay once, save more.' : 'Flexible month-to-month billing.' ?></div>
            <div class="plan-pr">
                <span class="plan-pr-cur"><?= $_cur ?></span>
                <span class="plan-pr-amt"><?= number_format($price, 0) ?></span>
                <span class="plan-pr-per"><?= $per ?></span>
            </div>
            <div class="plan-note">GST included &nbsp;Â·&nbsp; <?= $dur ?> days</div>
            <?php if (!empty($plan->MaxUsers) || !empty($plan->MaxBranches)): ?>
            <div class="plan-lims">
                <?php if (!empty($plan->MaxUsers)): ?><span class="plan-lim"><i class="bx bx-user"></i><?= (int)$plan->MaxUsers ?> Users</span><?php endif; ?>
                <?php if (!empty($plan->MaxBranches)): ?><span class="plan-lim"><i class="bx bx-building"></i><?= (int)$plan->MaxBranches ?> Branches</span><?php endif; ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($mods)): ?>
            <div class="plan-div"></div>
            <ul class="plan-mods">
                <?php foreach (array_slice($mods, 0, 7) as $m): ?><li><i class="bx bx-check-circle"></i><?= htmlspecialchars($m->DisplayName) ?></li><?php endforeach; ?>
                <?php if (count($mods) > 7): ?><li class="more">+<?= count($mods)-7 ?> more modules</li><?php endif; ?>
            </ul>
            <?php endif; ?>
            <a href="<?= htmlspecialchars($_su) ?>" class="plan-btn <?= $isPop ? 'p-btn-solid' : 'p-btn-out' ?>">Get Started</a>
        </div>
        <?php endforeach; ?>
        </div>
        <?php endforeach; ?>

    </div>
    <?php else: ?>
    <div class="no-plans rv"><i class="bx bx-tag"></i><p>Pricing plans coming soon. <a href="<?= htmlspecialchars($_su) ?>" class="link-pri">Contact us</a> for a quote.</p></div>
    <?php endif; ?>
</section>

<!-- â”€â”€ TESTIMONIALS â”€â”€ -->
<section class="section">
    <div class="sec-center rv">
        <div class="sec-eye">What Our Customers Say</div>
        <h2 class="sec-title">Trusted by businesses like yours</h2>
    </div>
    <div class="testi-grid">
        <div class="testi-card rv d1">
            <div class="testi-stars"><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i></div>
            <div class="testi-q">R2K ERP transformed how we manage invoicing and inventory. Everything is in one place and the GST reports save us hours every month.</div>
            <div class="testi-author">
                <div class="testi-av av-a">RK</div>
                <div><div class="testi-nm">Rajan Krishnan</div><div class="testi-role">Managing Director, Sun Traders</div></div>
            </div>
        </div>
        <div class="testi-card rv d2">
            <div class="testi-stars"><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i></div>
            <div class="testi-q">The accounting module alone replaced our external software. Our team got comfortable with it in less than a week — very intuitive.</div>
            <div class="testi-author">
                <div class="testi-av av-b">PS</div>
                <div><div class="testi-nm">Priya Shankar</div><div class="testi-role">Accounts Manager, Mahalakshmi Stores</div></div>
            </div>
        </div>
        <div class="testi-card rv d3">
            <div class="testi-stars"><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i></div>
            <div class="testi-q">We run 3 branches and the multi-branch stock visibility is exceptional. We can see everything from one dashboard in real time.</div>
            <div class="testi-author">
                <div class="testi-av av-c">AM</div>
                <div><div class="testi-nm">Arjun Mehta</div><div class="testi-role">CEO, Nova Enterprises</div></div>
            </div>
        </div>
    </div>
</section>

<!-- â”€â”€ CTA BAND â”€â”€ -->
<div class="cta-band rv">
    <div class="cta-orb1"></div>
    <div class="cta-orb2"></div>
    <div class="cta-ttl">Your business deserves better tools.</div>
    <div class="cta-sub">Start your free trial today. No credit card. No commitment.</div>
    <div class="cta-btns">
        <a href="<?= htmlspecialchars($_su) ?>" class="btn btn-pri btn-lg"><i class="bx bx-rocket"></i> Start Free Trial</a>
        <a href="<?= htmlspecialchars($_lu) ?>" class="btn btn-out btn-lg">Sign In</a>
    </div>
</div>

<!-- â”€â”€ FOOTER â”€â”€ -->
<footer class="footer">
    <div class="foot-top">
        <div>
            <div class="nav-logo foot-logo">
                <div class="nav-mark"><img src="/images/logo/favicon_io/android-chrome-512x512-1.png" alt="R2K ERP"></div>
                <span>R2K ERP</span>
            </div>
            <div class="foot-brand-copy">Complete business management software built for growing Indian businesses. GST compliant. Cloud hosted. Always on.</div>
        </div>
        <div>
            <div class="foot-col-ttl">Product</div>
            <ul class="foot-links">
                <li><a href="#features">Features</a></li>
                <li><a href="#modules">Modules</a></li>
                <li><a href="#pricing">Pricing</a></li>
                <li><a href="#how">How it works</a></li>
            </ul>
        </div>
        <div>
            <div class="foot-col-ttl">Quick Access</div>
            <ul class="foot-links">
                <li><a href="<?= htmlspecialchars($_su) ?>">Sign Up</a></li>
                <li><a href="<?= htmlspecialchars($_lu) ?>">Sign In</a></li>
                <li><a href="<?= htmlspecialchars($_su) ?>">Free Trial</a></li>
            </ul>
        </div>
        <div>
            <div class="foot-col-ttl">Compliance</div>
            <ul class="foot-links">
                <li><a href="#">GST Compliant</a></li>
                <li><a href="#">Data Security</a></li>
                <li><a href="#">Privacy Policy</a></li>
                <li><a href="#">Terms of Service</a></li>
            </ul>
        </div>
    </div>
    <div class="foot-btm">
        <div class="foot-copy">&copy; <?= date('Y') ?> R2K Enterprises. All rights reserved.</div>
        <div class="foot-badges">
            <span class="foot-bdg"><i class="bx bx-shield-check"></i> GST Compliant</span>
            <span class="foot-bdg"><i class="bx bx-lock-alt"></i> SSL Secured</span>
            <span class="foot-bdg"><i class="bx bx-cloud"></i> 99.9% Uptime</span>
        </div>
    </div>
</footer>

<script>
var _enquiryEndpoint    = '<?= htmlspecialchars($_eq,     ENT_QUOTES) ?>';
var global_services_url = '<?= htmlspecialchars($_svcUrl, ENT_QUOTES) ?>';
var PublicServiceToken  = '<?= htmlspecialchars($_pubTok, ENT_QUOTES) ?>';
var _upstashUrl         = '<?= htmlspecialchars($_upUrl,  ENT_QUOTES) ?>';
var _upstashToken       = '<?= htmlspecialchars($_upTok,  ENT_QUOTES) ?>';

/* ── Validation helpers (mirrored from default.js) ── */

/**
 * @param {string} sEmail
 * @returns {boolean}
 */
function validateEmail(sEmail) {
    var filter = /^([\w-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([\w-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/;
    return filter.test(sEmail);
}

/**
 * @param {string} mobileValue
 * @param {string} countryCode - Numeric calling code without '+' (e.g. '91')
 * @returns {{ isValid: boolean, message: string }}
 */
function validateMobileNumber(mobileValue, countryCode) {
    countryCode = countryCode || '91';
    if (!mobileValue || mobileValue.trim() === '') {
        return { isValid: true, message: '' };
    }
    var cleanMobile      = mobileValue.replace(/\D/g, '');
    var normalizedMobile = cleanMobile.replace(/^0+/, '');
    var countryRules = {
        '91':  { regex: /^[6-9]\d{9}$/,  message: 'Invalid Indian mobile number. Must be 10 digits starting with 6-9.' },
        '1':   { regex: /^\d{10}$/,       message: 'Invalid US/Canada mobile number. Must be 10 digits.' },
        '44':  { regex: /^\d{10,11}$/,    message: 'Invalid UK mobile number. Must be 10-11 digits.' },
        '61':  { regex: /^\d{9}$/,        message: 'Invalid Australian mobile number. Must be 9 digits.' },
        '971': { regex: /^\d{9}$/,        message: 'Invalid UAE mobile number. Must be 9 digits.' }
    };
    var rule    = countryRules[countryCode] || { regex: /^\d{5,15}$/, message: 'Invalid mobile number. Must be 5-15 digits.' };
    var isValid = rule.regex.test(normalizedMobile);
    return { isValid: isValid, message: isValid ? '' : rule.message };
}

$(function () {

    /* ── Enquiry modal state ── */
    var _enqCountryCache = null;
    var _enqDetectedISO2 = null;

    /* ── Load full country list from Upstash cache (r2k-loc-countries) ── */
    /**
     * @param {Function} cb
     * @returns {void}
     */
    function _loadCountries(cb) {
        if (_enqCountryCache !== null) { cb(_enqCountryCache); return; }
        $.ajax({
            url:      _upstashUrl + '/get/r2k-loc-countries',
            method:   'GET',
            headers:  { Authorization: 'Bearer ' + _upstashToken },
            dataType: 'json',
            success: function (res) {
                var data = null;
                try { data = (res && res.result) ? JSON.parse(res.result) : null; } catch (e) {}
                if (Array.isArray(data) && data.length) {
                    _enqCountryCache = data;
                    cb(_enqCountryCache);
                    return;
                }
                _fetchCountriesFallback(cb);
            },
            error: function () { _fetchCountriesFallback(cb); }
        });
    }

    /**
     * @param {Function} cb
     * @returns {void}
     */
    function _fetchCountriesFallback(cb) {
        $.ajax({
            url:      global_services_url + 'api/globally/getCountryInfo',
            method:   'GET',
            headers:  { Authorization: 'Bearer ' + PublicServiceToken },
            dataType: 'json',
            success: function (res) {
                _enqCountryCache = (res && res.Data) ? res.Data : [];
                cb(_enqCountryCache);
            },
            error: function () {
                _enqCountryCache = [];
                cb([]);
            }
        });
    }

    /* ── Detect user's country from IP ── */
    /**
     * @param {Function} cb
     * @returns {void}
     */
    function _detectCountry(cb) {
        if (_enqDetectedISO2 !== null) { cb(_enqDetectedISO2); return; }
        $.ajax({
            url:      'https://ipapi.co/json/',
            method:   'GET',
            dataType: 'json',
            success: function (res) {
                _enqDetectedISO2 = (res && res.country_code) ? res.country_code : 'IN';
                cb(_enqDetectedISO2);
            },
            error: function () {
                _enqDetectedISO2 = 'IN';
                cb('IN');
            }
        });
    }

    /* ── Render country list inside CC dropdown ── */
    /**
     * @param {string} query
     * @returns {void}
     */
    function _renderCCList(query) {
        if (!_enqCountryCache || !_enqCountryCache.length) {
            $('#enqCCList').html('<div class="enq-cc-no-result">No countries loaded.</div>');
            return;
        }
        var q        = $.trim(query || '').toLowerCase();
        var filtered = q
            ? $.grep(_enqCountryCache, function (c) {
                return (c.name || '').toLowerCase().indexOf(q) >= 0 ||
                       String(c.phonecode || '').indexOf(q) >= 0;
              })
            : _enqCountryCache;
        var html = '';
        $.each(filtered, function (_, c) {
            html += '<div class="enq-cc-item" data-iso2="' + (c.iso2 || '') + '" data-code="+' + (c.phonecode || '') + '">' +
                    (c.emoji ? '<span class="enq-cc-flag">' + c.emoji + '</span>' : '') +
                    '<span class="enq-cc-name">' + (c.name || '') + '</span>' +
                    '<span class="enq-cc-code">+' + (c.phonecode || '') + '</span>' +
                    '</div>';
        });
        $('#enqCCList').html(html || '<div class="enq-cc-no-result">No results</div>');
    }

    /* ── Pre-select a country in the CC button by ISO2 ── */
    /**
     * @param {string} iso2
     * @returns {void}
     */
    function _selectFromISO2(iso2) {
        if (!_enqCountryCache || !_enqCountryCache.length) { return; }
        var found = null;
        $.each(_enqCountryCache, function (_, c) {
            if ((c.iso2 || '').toUpperCase() === iso2.toUpperCase()) { found = c; return false; }
        });
        if (!found) { return; }
        var code  = '+' + found.phonecode;
        var label = (found.name || found.iso2) + ' ' + code;
        $('#enqCCBtn').text(label);
        $('#enqPhoneCode').val(code);
        $('#enqPhoneISO2').val(found.iso2);
    }

    /* ── Open: fire both API calls in parallel, then show modal ── */
    /**
     * @returns {void}
     */
    function openEnqModal() {
        var dCountries = $.Deferred();
        var dDetect    = $.Deferred();
        _loadCountries(function ()     { dCountries.resolve(); });
        _detectCountry(function (iso2) { dDetect.resolve(iso2); });
        $.when(dCountries, dDetect).done(function (unused, iso2) {
            _renderCCList('');
            _selectFromISO2(iso2);
            $('#enqOverlay').addClass('open').attr('aria-hidden', 'false');
            $('body').addClass('enq-open');
            setTimeout(function () { $('#enqName').trigger('focus'); }, 80);
        });
    }

    /* ── Close and reset modal ── */
    /**
     * @returns {void}
     */
    function closeEnqModal() {
        $('#enqOverlay').removeClass('open').attr('aria-hidden', 'true');
        $('body').removeClass('enq-open');
        $('#enqForm')[0].reset();
        $('#enqCCDropdown').removeClass('open');
        $('.enq-err').text('');
        $('.enq-input').removeClass('enq-input-err');
        $('#enqStatus').text('').removeClass('enq-status-ok enq-status-err');
    }

    /* ── CC dropdown toggle ── */
    $('#enqCCBtn').on('click', function (e) {
        e.stopPropagation();
        var $dd = $('#enqCCDropdown');
        if ($dd.hasClass('open')) {
            $dd.removeClass('open');
        } else {
            _renderCCList('');
            $dd.addClass('open');
            $('#enqCCSearch').val('').trigger('focus');
        }
    });

    /* ── CC search filter ── */
    $('#enqCCSearch').on('input', function () {
        _renderCCList($(this).val());
    });

    /* ── CC item select ── */
    $(document).on('click', '.enq-cc-item', function () {
        var iso2  = $(this).data('iso2');
        var code  = $(this).data('code');
        var cname = $(this).find('.enq-cc-name').text();
        var label = (cname || iso2) + ' ' + code;
        $('#enqCCBtn').text(label);
        $('#enqPhoneCode').val(code);
        $('#enqPhoneISO2').val(iso2);
        $('#enqCCDropdown').removeClass('open');
    });

    /* ── Close CC dropdown on outside click ── */
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.enq-cc-wrap').length) {
            $('#enqCCDropdown').removeClass('open');
        }
    });

    /* ── Bind open / close ── */
    $('#navEnquiryBtn').on('click', openEnqModal);
    $('#enqCloseBtn').on('click', closeEnqModal);
    $('#enqOverlay').on('click', function (e) {
        if ($(e.target).is('#enqOverlay')) { closeEnqModal(); }
    });
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') { closeEnqModal(); }
    });

    /* ── Submit with validation ── */
    /**
     * @param {Event} e
     * @returns {void}
     */
    $('#enqForm').on('submit', function (e) {
        e.preventDefault();

        var name  = $.trim($('#enqName').val());
        var phone = $.trim($('#enqPhone').val());
        var email = $.trim($('#enqEmail').val());
        var code  = ($('#enqPhoneCode').val() || '+91').replace('+', '');
        var valid = true;

        $('.enq-err').text('');
        $('.enq-input').removeClass('enq-input-err');

        if (!name) {
            $('#enqNameErr').text('Name is required.');
            $('#enqName').addClass('enq-input-err');
            valid = false;
        }
        if (!phone) {
            $('#enqPhoneErr').text('Phone is required.');
            $('#enqPhone').addClass('enq-input-err');
            valid = false;
        } else {
            var phoneVal = validateMobileNumber(phone, code);
            if (!phoneVal.isValid) {
                $('#enqPhoneErr').text(phoneVal.message);
                $('#enqPhone').addClass('enq-input-err');
                valid = false;
            }
        }
        if (!email) {
            $('#enqEmailErr').text('Email address is required.');
            $('#enqEmail').addClass('enq-input-err');
            valid = false;
        } else if (!validateEmail(email)) {
            $('#enqEmailErr').text('Please enter a valid email address.');
            $('#enqEmail').addClass('enq-input-err');
            valid = false;
        }
        var message = $.trim($('#enqMessage').val());
        if (!message) {
            $('#enqMessageErr').text('Message is required.');
            $('#enqMessage').addClass('enq-input-err');
            valid = false;
        }
        if (!valid) { return; }

        $('#enqSubmitBtn').prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Sending...');
        $('#enqStatus').text('').removeClass('enq-status-ok enq-status-err');

        $.ajax({
            url:         _enquiryEndpoint,
            method:      'POST',
            contentType: 'application/json',
            data:        JSON.stringify({
                name:    name,
                phone:   ($('#enqPhoneCode').val() || '+91') + phone,
                email:   email,
                message: message,
                product: 'R2K ERP'
            }),
            dataType: 'json',
            success: function () {
                $('#enqStatus').text("Enquiry sent! We'll contact you shortly.").addClass('enq-status-ok');
                $('#enqForm')[0].reset();
                setTimeout(closeEnqModal, 2800);
            },
            error: function () {
                $('#enqStatus').text('Something went wrong. Please try again.').addClass('enq-status-err');
            },
            complete: function () {
                $('#enqSubmitBtn').prop('disabled', false).html('<i class="bx bx-send"></i> Send Enquiry');
            }
        });
    });

    /* ── Navbar scroll state ── */
    /**
     * @returns {void}
     */
    function checkNavScroll() {
        if ($(window).scrollTop() > 40) {
            $('#mainNav').addClass('scrolled');
        } else {
            $('#mainNav').removeClass('scrolled');
        }
    }
    $(window).on('scroll', checkNavScroll);
    checkNavScroll();

    /* ── Scroll reveal ── */
    /**
     * @returns {void}
     */
    function checkReveal() {
        var vBot = $(window).scrollTop() + $(window).height();
        $('.rv, .rv-l, .rv-r, .rv-s').each(function () {
            if ($(this).offset().top < vBot - 60) {
                $(this).addClass('vis');
            }
        });
    }
    $(window).on('scroll', checkReveal);
    checkReveal();

    /* ── Counter animation ── */
    var _counted = false;
    /**
     * @returns {void}
     */
    function runCounters() {
        if (_counted) { return; }
        var statsTop = $('.stats-band').offset().top;
        if ($(window).scrollTop() + $(window).height() > statsTop) {
            _counted = true;
            $('.counter').each(function () {
                var $el    = $(this);
                var target = parseInt($el.data('target'), 10);
                $({ n: 0 }).animate({ n: target }, {
                    duration: 1600,
                    easing: 'swing',
                    step: function () { $el.text(Math.floor(this.n)); },
                    complete: function () { $el.text(target); }
                });
            });
        }
    }
    $(window).on('scroll', runCounters);
    runCounters();

    /* ── Pricing cycle toggle ── */
    var _cyc = '<?= $_cycDef ?>';
    /**
     * @returns {void}
     */
    function toggleCycle() {
        _cyc = (_cyc === 'monthly') ? 'yearly' : 'monthly';
        var isYearly = (_cyc === 'yearly');
        $('#togBtn').toggleClass('on', isYearly);
        $('#lblY').toggleClass('act', isYearly);
        $('#lblM').toggleClass('act', !isYearly);
        $('#blk_monthly').toggleClass('vis', !isYearly);
        $('#blk_yearly').toggleClass('vis', isYearly);
    }
    $('#togBtn').on('click', toggleCycle);

});
</script>
</body>
</html>

