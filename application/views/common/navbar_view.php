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

            <!-- Style Switcher -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle hide-arrow" id="nav-theme" href="javascript:void(0);" data-bs-toggle="dropdown" aria-label="Toggle theme" aria-expanded="false">
                    <i class="bx-sun icon-base bx icon-md theme-icon-active"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="nav-theme-text">
                    <li>
                        <button type="button" class="dropdown-item align-items-center active" data-bs-theme-value="light" aria-pressed="true">
                            <span><i class="icon-base bx bx-sun icon-md me-3" data-icon="sun"></i>Light</span>
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item align-items-center" data-bs-theme-value="dark" aria-pressed="false">
                            <span><i class="icon-base bx bx-moon icon-md me-3" data-icon="moon"></i>Dark</span>
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item align-items-center" data-bs-theme-value="system" aria-pressed="false">
                            <span><i class="icon-base bx bx-desktop icon-md me-3" data-icon="desktop"></i>System</span>
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
                        <a class="dropdown-item" href="/settings/profile">
                            <i class="bx bx-user me-2"></i><span class="align-middle">My Profile</span>
                        </a>
                    </li>
                    <?php if ($JwtData->User->RoleUID == 1) { ?>
                    <li>
                        <a class="dropdown-item" href="javascript:void(0);">
                            <i class="bx bx-cog me-2"></i><span class="align-middle">Settings</span>
                        </a>
                    </li>
                    <?php } ?>
                    <li>
                        <a class="dropdown-item" id="ChangePasswordBtn" href="javascript:void(0);">
                            <i class="bx bx-lock me-2"></i><span class="align-middle">Change Password</span>
                        </a>
                    </li>
                    <li><div class="dropdown-divider"></div></li>
                    <li>
                        <a class="dropdown-item" href="/logout">
                            <i class="bx bx-power-off me-2"></i><span class="align-middle">Log Out</span>
                        </a>
                    </li>
                </ul>
            </li>

        </ul>
    </div>
</nav>
<!-- / Navbar -->
