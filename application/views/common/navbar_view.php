<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<style>
/* ── Strip the template card/shadow from navbar ── */
#layout-navbar {
    padding-top: 0 !important;
    padding-bottom: 0 !important;
    min-height: 62px;
    background: transparent !important;
    box-shadow: none !important;
    border: none !important;
    border-radius: 0 !important;
    margin-bottom: 0 !important;
}
/* Sneat .navbar-detached adds card styles — remove them */
.layout-navbar.navbar-detached {
    box-shadow: none !important;
    border-radius: 0 !important;
    background: #ffffff !important;
    border-bottom: 1px solid #e2e8f0 !important;
    padding: 0 !important;
    margin: 0 0 1.5rem 0 !important;
}
#layout-navbar .navbar-nav-right {
    align-items: stretch !important;
    height: 62px;
}

/* ── Stat cards stretch full navbar height ── */
.nb-stats-wrap {
    display: flex;
    align-items: stretch;
    height: 62px;
    gap: 0;
    margin-right: 8px;
}
.nb-stat-card {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 0 20px;
    border-right: 1px solid #e2e8f0;
    min-width: 145px;
    position: relative;
    background: #fff;
    transition: background .15s;
}
.nb-stat-card:first-child { border-left: 1px solid #e2e8f0; }
.nb-stat-card:hover { background: #f8faff; }

/* bottom accent line on hover */
.nb-stat-card::after {
    content: '';
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 3px;
    opacity: 0;
    transition: opacity .2s;
}
.nb-stat-card:hover::after        { opacity: 1; }
.nb-stat-card.s-blue::after       { background: #2563eb; }
.nb-stat-card.s-green::after      { background: #16a34a; }
.nb-stat-card.s-amber::after      { background: #d97706; }

.nb-icon {
    width: 36px; height: 36px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.05rem; flex-shrink: 0;
}
.nb-icon.s-blue  { background: #dbeafe; color: #2563eb; }
.nb-icon.s-green { background: #dcfce7; color: #16a34a; }
.nb-icon.s-amber { background: #fef3c7; color: #d97706; }

.nb-label { font-size: .67rem; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: .4px; line-height: 1; margin-bottom: 3px; }
.nb-value { font-size: 1.1rem; font-weight: 800; color: #1e293b; line-height: 1; }

/* ── Quick Create Button ── */
.nb-qc-btn {
    width: 32px; height: 32px;
    border-radius: 8px;
    background: #2563eb;
    color: #fff;
    border: none;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem;
    cursor: pointer;
    transition: background .15s, box-shadow .15s, transform .1s;
    box-shadow: 0 2px 8px rgba(37,99,235,.28);
    padding: 0; line-height: 1;
}
.nb-qc-btn:hover { background: #1d4ed8; box-shadow: 0 4px 14px rgba(37,99,235,.38); }
.nb-qc-btn:active { transform: scale(.92); }

/* ── Quick Create Dropdown ── */
.nb-qc-dropdown {
    width: 620px;
    padding: 16px 18px 14px;
    border-radius: 14px !important;
    box-shadow: 0 12px 40px rgba(0,0,0,.13) !important;
    border: 1px solid #e2e8f0 !important;
    background: #fff;
    margin-top: 10px !important;
}
.nb-qc-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0 14px;
}
.nb-qc-col-header {
    font-size: .66rem;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: .55px;
    padding-bottom: 6px;
    margin-bottom: 4px;
    border-bottom: 1px solid #f1f5f9;
}
.nb-qc-col-header + .nb-qc-col-header,
.nb-qc-item + .nb-qc-col-header { margin-top: 12px; }
.nb-qc-item {
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 5px 7px;
    border-radius: 6px;
    color: #334155;
    font-size: .81rem;
    font-weight: 500;
    text-decoration: none !important;
    transition: background .12s, color .12s;
    white-space: nowrap;
    margin-bottom: 1px;
}
.nb-qc-item:hover { background: #eff6ff; color: #2563eb; }
.nb-qc-item i { font-size: .95rem; color: #64748b; flex-shrink: 0; transition: color .12s; }
.nb-qc-item:hover i { color: #2563eb; }

/* ── Language Switcher ── */
.apex-lang-wrap { position: relative; display: flex; align-items: center; }
.apex-lang-btn {
    display: flex; align-items: center; gap: 3px;
    padding: 5px 9px;
    background: none;
    border: 1px solid #e2e8f0;
    border-radius: 7px;
    cursor: pointer;
    font-size: .8rem; font-weight: 700; color: #334155; line-height: 1;
    transition: background .12s, border-color .12s;
}
.apex-lang-btn:hover { background: #f1f5f9; border-color: #cbd5e1; }
.apex-lang-wrap.open .apex-lang-btn { background: #eff6ff; border-color: #93c5fd; color: #2563eb; }
.apex-lang-chevron { font-size: .75rem; transition: transform .18s; color: #94a3b8; }
.apex-lang-wrap.open .apex-lang-chevron { transform: rotate(180deg); }
.apex-lang-dropdown {
    display: none; position: absolute;
    top: calc(100% + 6px); right: 0;
    background: #fff; border: 1px solid #e2e8f0;
    border-radius: 10px; box-shadow: 0 8px 28px rgba(0,0,0,.12);
    min-width: 115px; padding: 5px; z-index: 1050;
}
.apex-lang-wrap.open .apex-lang-dropdown { display: block; }
.apex-lang-option {
    display: flex; align-items: center; width: 100%;
    padding: 7px 12px; background: none; border: none;
    border-radius: 7px; font-size: .83rem; font-weight: 500;
    color: #334155; cursor: pointer; text-align: left;
    transition: background .1s, color .1s;
}
.apex-lang-option:hover { background: #f1f5f9; color: #2563eb; }
.apex-lang-option.active { color: #2563eb; font-weight: 700; background: #eff6ff; }

/* ── Branch Switcher ── */
.nb-branch-btn {
    display: flex; align-items: center; gap: 6px;
    padding: 5px 10px;
    border-radius: 8px;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    font-size: .78rem; font-weight: 600; color: #334155;
    cursor: pointer; transition: background .15s, border-color .15s;
    white-space: nowrap; max-width: 160px;
}
.nb-branch-btn:hover { background: #e8f0fe; border-color: #bfdbfe; color: #2563eb; }
.nb-branch-btn i.bx-store-alt { font-size: .9rem; color: #64748b; flex-shrink: 0; }
.nb-branch-btn .nb-branch-name { overflow: hidden; text-overflow: ellipsis; }
.nb-branch-btn i.bx-chevron-down { font-size: .8rem; color: #94a3b8; flex-shrink: 0; }
.nb-branch-dropdown {
    min-width: 200px;
    border-radius: 10px !important;
    box-shadow: 0 8px 28px rgba(0,0,0,.12) !important;
    border: 1px solid #e2e8f0 !important;
    padding: 6px 0;
    margin-top: 6px !important;
}
.nb-branch-item {
    display: flex; align-items: center; gap: 8px;
    padding: 7px 14px; font-size: .82rem; font-weight: 500; color: #334155;
    cursor: pointer; transition: background .12s; white-space: nowrap;
    text-decoration: none !important;
}
.nb-branch-item:hover { background: #eff6ff; color: #2563eb; }
.nb-branch-item.active { background: #dbeafe; color: #2563eb; font-weight: 700; }
.nb-branch-item .nb-bcode { font-size: .68rem; color: #94a3b8; margin-left: auto; }
.nb-branch-item.active .nb-bcode { color: #93c5fd; }
.nb-branch-divider { font-size: .65rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .5px; padding: 6px 14px 3px; pointer-events: none; }

/* User dropdown — org + last login info block */
.nav-user-info-block { padding: .45rem .9rem .5rem; pointer-events: none; }
.nav-user-info-org { display: flex; align-items: center; gap: .35rem; font-size: .76rem; color: #475569; font-weight: 500; margin-bottom: .4rem; }
.nav-user-info-org i { font-size: .85rem; color: #94a3b8; }
.nav-user-info-label { font-size: .66rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .4px; font-weight: 600; margin-bottom: 2px; }
.nav-user-info-time { display: flex; align-items: center; gap: .35rem; font-size: .75rem; color: #475569; }
.nav-user-info-time i { font-size: .8rem; color: #94a3b8; flex-shrink: 0; }
.nav-user-info-device { font-size: .68rem; color: #94a3b8; margin-top: 2px; padding-left: 1.15rem; }
</style>

<!-- Navbar -->
<nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme" id="layout-navbar">
    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
            <i class="bx bx-menu bx-sm"></i>
        </a>
    </div>

    <div class="navbar-nav-right d-flex align-items-center w-100" id="navbar-collapse">

        <!-- Stat cards — full navbar height, only on customer page -->
        <?php if (isset($CustStats)) { ?>
        <div class="nb-stats-wrap me-auto">
            <div class="nb-stat-card s-blue">
                <div class="nb-icon s-blue"><i class="bx bxs-group"></i></div>
                <div>
                    <div class="nb-label">Total Customers</div>
                    <div class="nb-value"><?php echo number_format((int)$CustStats->TotalCount); ?></div>
                </div>
            </div>
            <div class="nb-stat-card s-green">
                <div class="nb-icon s-green"><i class="bx bx-check-circle"></i></div>
                <div>
                    <div class="nb-label">Active</div>
                    <div class="nb-value"><?php echo number_format((int)$CustStats->ActiveCount); ?></div>
                </div>
            </div>
            <div class="nb-stat-card s-amber">
                <div class="nb-icon s-amber"><i class="bx bx-calendar-plus"></i></div>
                <div>
                    <div class="nb-label">This Month</div>
                    <div class="nb-value"><?php echo number_format((int)$CustStats->MonthCount); ?></div>
                </div>
            </div>
        </div>
        <?php } else { ?>
        <div class="me-auto"></div>
        <?php } ?>

        <ul class="navbar-nav flex-row align-items-center gap-1">

            <!-- Quick Create -->
            <li class="nav-item d-flex align-items-center">
                <div class="dropdown">
                    <button class="nb-qc-btn" id="nbQuickCreateBtn" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false" title="Quick Create">
                        <i class="bx bx-plus"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end nb-qc-dropdown" aria-labelledby="nbQuickCreateBtn">
                        <div class="nb-qc-grid">

                            <!-- SALES -->
                            <div class="nb-qc-col">
                                <div class="nb-qc-col-header">Sales</div>
                                <a href="/quotations/create" class="nb-qc-item"><i class="bx bx-notepad"></i>Quotation</a>
                                <a href="/proformainvoices/create" class="nb-qc-item"><i class="bx bx-file-find"></i>Proforma Invoice</a>
                                <a href="/salesorders/create" class="nb-qc-item"><i class="bx bx-cart-add"></i>Sales Order</a>
                                <a href="/invoices/create" class="nb-qc-item"><i class="bx bx-receipt"></i>Invoice</a>
                                <a href="/deliverychallans/create" class="nb-qc-item"><i class="bx bx-package"></i>Delivery Challan</a>
                                <a href="/salesreturns/create" class="nb-qc-item"><i class="bx bx-revision"></i>Sales Return</a>
                            </div>

                            <!-- PURCHASE -->
                            <div class="nb-qc-col">
                                <div class="nb-qc-col-header">Purchase</div>
                                <a href="/purchaseorders/create" class="nb-qc-item"><i class="bx bx-list-ul"></i>Purchase Order</a>
                                <a href="/purchases/create" class="nb-qc-item"><i class="bx bx-shopping-bag"></i>Purchase</a>
                                <a href="/purchasereturns/create" class="nb-qc-item"><i class="bx bx-transfer-alt"></i>Purchase Return</a>
                            </div>

                            <!-- PARTY -->
                            <div class="nb-qc-col">
                                <div class="nb-qc-col-header">Party</div>
                                <a href="/customers?action=create" class="nb-qc-item"><i class="bx bx-user-plus"></i>Customer</a>
                                <a href="/vendors?action=create" class="nb-qc-item"><i class="bx bx-store"></i>Vendor</a>
                                <div class="nb-qc-col-header" style="margin-top:12px;">Inventory</div>
                                <a href="/products?action=create" class="nb-qc-item"><i class="bx bx-box"></i>Product</a>
                            </div>

                            <!-- ACCOUNTING -->
                            <div class="nb-qc-col">
                                <div class="nb-qc-col-header">Accounting</div>
                                <a href="/expenses/create" class="nb-qc-item"><i class="bx bx-money-withdraw"></i>Expense</a>
                                <a href="/indirectincome?action=create" class="nb-qc-item"><i class="bx bx-trending-up"></i>Indirect Income</a>
                            </div>

                        </div>
                    </div>
                </div>
            </li>

            <!-- Branch Switcher — only shown when user has access to more than one branch -->
            <?php
            $_accessibleBranches = $JwtData->Org->AccessibleBranches ?? [];
            $_activeBranchUID    = (int)($JwtData->Org->BranchUID    ?? 0);
            $_activeBranchName   = $JwtData->Org->BranchName ?? '';
            $_activeBranchCode   = $JwtData->Org->BranchCode ?? '';
            if (count($_accessibleBranches) > 1):
            ?>
            <li class="nav-item d-flex align-items-center">
                <div class="dropdown">
                    <button class="nb-branch-btn" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false"
                        data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo t('tooltip_switch_branch', 'Switch Branch'); ?>">
                        <i class="bx bx-store-alt"></i>
                        <span class="nb-branch-name"><?php echo htmlspecialchars($_activeBranchName); ?></span>
                        <i class="bx bx-chevron-down"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end nb-branch-dropdown">
                        <div class="nb-branch-divider"><?php echo t('lbl_branches', 'Branches'); ?></div>
                        <?php foreach ($_accessibleBranches as $_br): ?>
                        <a href="javascript:void(0);"
                           class="nb-branch-item <?php echo ((int)$_br->BranchUID === $_activeBranchUID) ? 'active' : ''; ?>"
                           data-branch-uid="<?php echo (int)$_br->BranchUID; ?>"
                           data-branch-name="<?php echo htmlspecialchars($_br->BranchName ?? ''); ?>">
                            <i class="bx bx-map-pin" style="font-size:.85rem;color:#94a3b8;flex-shrink:0;"></i>
                            <?php echo htmlspecialchars($_br->BranchName ?? ''); ?>
                            <span class="nb-bcode"><?php echo htmlspecialchars($_br->BranchCode ?? ''); ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </li>
            <?php endif; ?>

            <!-- Language Switcher -->
            <?php $_navLang = $JwtData->User->UILanguage ?? 'en'; ?>
            <li class="nav-item d-flex align-items-center">
                <div id="apexLangWrap" class="apex-lang-wrap">
                    <button type="button" id="apexLangBtn" class="apex-lang-btn">
                        <span class="apex-lang-label"><?php echo $_navLang === 'ta' ? 'த' : 'En'; ?></span>
                        <i class="bx bx-chevron-down apex-lang-chevron"></i>
                    </button>
                    <div id="apexLangDropdown" class="apex-lang-dropdown">
                        <button type="button" class="apex-lang-option<?php echo $_navLang === 'en' ? ' active' : ''; ?>" data-lang="en">English</button>
                        <button type="button" class="apex-lang-option<?php echo $_navLang === 'ta' ? ' active' : ''; ?>" data-lang="ta">தமிழ்</button>
                    </div>
                </div>
            </li>

            <!-- Style Switcher -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle hide-arrow" id="nav-theme" href="javascript:void(0);" data-bs-toggle="dropdown" aria-label="Toggle theme" aria-expanded="false">
                    <i class="bx-sun icon-base bx icon-md theme-icon-active"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="nav-theme-text">
                    <li>
                        <button type="button" class="dropdown-item align-items-center active" data-bs-theme-value="light" aria-pressed="true">
                            <span><i class="icon-base bx bx-sun icon-md me-3" data-icon="sun"></i><?php echo t('theme_light', 'Light'); ?></span>
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item align-items-center" data-bs-theme-value="dark" aria-pressed="false">
                            <span><i class="icon-base bx bx-moon icon-md me-3" data-icon="moon"></i><?php echo t('theme_dark', 'Dark'); ?></span>
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item align-items-center" data-bs-theme-value="system" aria-pressed="false">
                            <span><i class="icon-base bx bx-desktop icon-md me-3" data-icon="desktop"></i><?php echo t('theme_system', 'System'); ?></span>
                        </button>
                    </li>
                </ul>
            </li>

            <!-- User -->
            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <div class="avatar avatar-online">
                        <img src="<?php echo $JwtData->User->UserImage ? getenv('CFLARE_R2_CDN').$JwtData->User->UserImage : '/images/logo/avathar_user.png' ?>" alt="<?php echo strtoupper($JwtData->User->FirstName); ?>" class="w-px-40 h-px-40 rounded-circle" />
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="javascript:void(0);">
                            <div class="d-flex">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar avatar-online">
                                        <img src="<?php echo $JwtData->User->UserImage ? getenv('CFLARE_R2_CDN').$JwtData->User->UserImage : '/images/logo/avathar_user.png' ?>" alt="<?php echo strtoupper($JwtData->User->FirstName); ?>" class="w-px-40 h-px-40 rounded-circle" />
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <span class="fw-semibold d-block"><?php echo strtoupper($JwtData->User->FirstName); ?></span>
                                    <small class="text-muted"><?php echo strtoupper($JwtData->User->RoleName); ?></small>
                                </div>
                            </div>
                        </a>
                    </li>
                    <li><div class="dropdown-divider"></div></li>
                    <li>
                        <div class="nav-user-info-block">
                            <div class="nav-user-info-org">
                                <i class="bx bx-buildings"></i>
                                <?php echo htmlspecialchars($JwtData->Org->OrgName ?? ''); ?>
                            </div>
                            <?php
                            $_lastLoginOn = $JwtData->User->LastLoginOn ?? null;
                            if (!empty($_lastLoginOn)):
                                $_lastLoginTs = viewPageDateTimeFormat($_lastLoginOn, $JwtData->User->Timezone ?? 'UTC', 2);
                            ?>
                            <div class="nav-user-info-label">Last login</div>
                            <div class="nav-user-info-time">
                                <i class="bx bx-time-five"></i>
                                <?php echo $_lastLoginTs->formatted; ?>
                            </div>
                            <?php if (!empty($JwtData->User->LastLoginDevice ?? null)): ?>
                            <div class="nav-user-info-device"><?php echo htmlspecialchars(mb_strimwidth($JwtData->User->LastLoginDevice, 0, 42, '…')); ?></div>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </li>
                    <li><div class="dropdown-divider"></div></li>
                    <li>
                        <a class="dropdown-item" href="/settings/profile">
                            <i class="bx bx-user me-2"></i><span class="align-middle"><?php echo t('my_profile', 'My Profile'); ?></span>
                        </a>
                    </li>
                    <?php if ($JwtData->User->RoleUID == 1) { ?>
                    <li>
                        <a class="dropdown-item" href="javascript:void(0);">
                            <i class="bx bx-cog me-2"></i><span class="align-middle"><?php echo t('settings', 'Settings'); ?></span>
                        </a>
                    </li>
                    <?php } ?>
                    <li>
                        <a class="dropdown-item ChangePasswordBtn" href="javascript:void(0);">
                            <i class="bx bx-lock me-2"></i><span class="align-middle"><?php echo t('change_password', 'Change Password'); ?></span>
                        </a>
                    </li>
                    <li><div class="dropdown-divider"></div></li>
                    <li>
                        <a class="dropdown-item" href="/logout">
                            <i class="bx bx-power-off me-2"></i><span class="align-middle"><?php echo t('log_out', 'Log Out'); ?></span>
                        </a>
                    </li>
                </ul>
            </li>

        </ul>
    </div>
</nav>
<!-- / Navbar -->
