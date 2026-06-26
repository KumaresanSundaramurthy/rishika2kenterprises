<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php if (!(isset($JwtData->GenSettings->StatsDefaultOpen) ? (bool)$JwtData->GenSettings->StatsDefaultOpen : true)): ?>
<style>.apex-stats-strip{display:none}</style>
<?php endif; ?>
<?php
$_icon    = !empty($pageIcon)      ? $pageIcon      : ($PageIcon      ?? 'bx-file-blank');
$_iconBg  = !empty($pageIconBg)    ? $pageIconBg    : ($PageIconBg    ?? '#eef2ff');
$_iconClr = !empty($pageIconColor) ? $pageIconColor : ($PageIconColor ?? '#696cff');
$_title   = $pageTitle       ?? ($PageTitle       ?? 'Page');
$_desc    = $pageDescription ?? ($PageDescription ?? '');

// ── Quick Access palette data — built once at render, zero AJAX on open ──────
$_qaMenus    = $this->redisservice->getUserCache('menus')    ?? [];
$_qaSubMenus = $this->redisservice->getUserCache('submenus') ?? [];
$_qaData = [];
foreach ($_qaMenus as $_qaMM) {
    if (empty($_qaMM->MainMenuUID)) continue;
    $_leaves = [];
    foreach ($_qaSubMenus as $_qaSM) {
        if ($_qaSM->MainMenuUID != $_qaMM->MainMenuUID) continue;
        if (!empty($_qaSM->IsParent))                   continue;
        $_smName = $_qaSM->SubMenuName ?? ($_qaSM->Name  ?? '');
        if (empty($_smName))                            continue;
        $_smIcon = $_qaSM->SubMenuIcon ?? ($_qaSM->Icon  ?? ($_qaSM->icon ?? ''));
        $_smUrl  = $_qaSM->UrlPath     ?? ($_qaSM->ControllerName ?? '');
        $_leaves[] = ['name' => $_smName, 'icon' => trim($_smIcon), 'url' => '/' . ltrim($_smUrl, '/')];
    }
    if (empty($_leaves)) continue;
    $_qaData[] = ['name' => $_qaMM->MainMenuName ?? '', 'icon' => trim($_qaMM->MainMenuIcons ?? 'bx bx-grid-alt'), 'modules' => $_leaves];
}
?>
<script>window._APEX_QA_DATA=<?php echo json_encode($_qaData, JSON_UNESCAPED_UNICODE); ?>;</script>
<div class="apex-page-header">
    <div class="apex-page-header-left">
        <div class="apex-page-icon" style="background:<?php echo $_iconBg; ?>;">
            <i class="bx <?php echo $_icon; ?>" style="color:<?php echo $_iconClr; ?>;"></i>
        </div>
        <div>
            <h5 class="apex-page-title mb-0"><?php echo htmlspecialchars($_title); ?></h5>
            <?php if (!empty($_desc)): ?>
            <div class="apex-page-desc"><?php echo htmlspecialchars($_desc); ?></div>
            <?php endif; ?>
        </div>
    </div>
    <div class="apex-page-header-right">
        <?php if (!empty($pageHeaderActions)): ?>
            <?php echo $pageHeaderActions; ?>
            <div class="apex-nav-divider"></div>
        <?php endif; ?>
        <!-- Quick Access trigger -->
        <button class="apex-header-search-btn" id="apexHeaderSearch" type="button">
            <i class="bx bx-search"></i>
            <span>Search or go to a module...</span>
            <kbd class="apex-header-search-kbd">Ctrl K</kbd>
        </button>
        <div class="apex-nav-divider"></div>
        <!-- Help -->
        <button class="apex-nav-btn" title="Help" type="button">
            <i class="bx bx-help-circle"></i>
        </button>
        <!-- Notifications -->
        <button class="apex-nav-btn" id="apexNotifBtn" title="Notifications" type="button">
            <i class="bx bx-bell"></i>
            <span class="apex-notif-badge" id="apexNotifCount" style="display:none;">0</span>
        </button>
        <!-- User Dropdown -->
        <div class="apex-user-wrap" id="apexUserWrap">
            <button class="apex-user-btn" id="apexUserBtn" type="button">
                <?php if (!empty($JwtData->User->UserImage)): ?>
                    <img src="<?php echo getenv('CFLARE_R2_CDN') . $JwtData->User->UserImage; ?>"
                         alt="<?php echo htmlspecialchars($JwtData->User->FirstName); ?>"
                         class="apex-user-avatar">
                <?php else: ?>
                    <div class="apex-user-initials">
                        <?php echo strtoupper(substr($JwtData->User->FirstName, 0, 1) . substr($JwtData->User->LastName ?? '', 0, 1)); ?>
                    </div>
                <?php endif; ?>
                <div class="apex-user-text">
                    <div class="apex-user-name"><?php echo htmlspecialchars($JwtData->User->FirstName . ' ' . ($JwtData->User->LastName ?? '')); ?></div>
                    <div class="apex-user-role"><?php echo htmlspecialchars($JwtData->User->RoleName ?? ''); ?></div>
                </div>
                <i class="bx bx-chevron-down apex-user-caret"></i>
            </button>
            <div class="apex-user-dropdown" id="apexUserDropdown">
                <div class="apex-user-dd-header">
                    <div class="apex-user-dd-name"><?php echo htmlspecialchars($JwtData->User->FirstName . ' ' . ($JwtData->User->LastName ?? '')); ?></div>
                    <div class="apex-user-dd-org"><?php echo htmlspecialchars($JwtData->Org->OrgName ?? ''); ?></div>
                </div>
                <div class="apex-user-dd-divider"></div>
                <a href="/settings/profile" class="apex-user-dd-item">
                    <i class="bx bx-user"></i> My Profile
                </a>
                <a href="/settings/generalsettings" class="apex-user-dd-item">
                    <i class="bx bx-cog"></i> Settings
                </a>
                <button class="apex-user-dd-item ChangePasswordBtn" type="button">
                    <i class="bx bx-lock"></i> Change Password
                </button>
                <div class="apex-user-dd-divider"></div>
                <a href="/logout" class="apex-user-dd-item apex-user-dd-logout">
                    <i class="bx bx-power-off"></i> Log Out
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ── Quick Access Modal ────────────────────────────────────────────────── -->
<div class="apex-qa-overlay" id="apexQuickAccessModal">
    <div class="r2k-qs-box">
        <!-- Header: title + subtitle + close -->
        <div class="r2k-qs-header">
            <div>
                <div class="r2k-qs-title">Quick Access</div>
                <div class="r2k-qs-subtitle">Search or go to any module</div>
            </div>
            <button class="r2k-qs-close" id="apexQuickAccessClose" type="button" title="Close">
                <i class="bx bx-x"></i>
            </button>
        </div>
        <!-- Search bar with Ctrl K badge -->
        <div class="r2k-qs-search-row">
            <i class="bx bx-search r2k-qs-search-icon"></i>
            <input type="text" id="apexQuickSearchInput" class="r2k-qs-search-input"
                   placeholder="Search or go to a module..." autocomplete="off" spellcheck="false">
            <span class="r2k-qs-kbd-badge"><kbd>Ctrl</kbd><kbd>K</kbd></span>
        </div>
        <div class="r2k-qs-body" id="apexQABody">
            <!-- built by ApexHeader on DOMReady -->
        </div>
        <div class="r2k-qs-footer">
            <i class="bx bx-bulb r2k-qs-footer-tip-icon"></i>
            <span>Tip: Press <kbd>Ctrl K</kbd> anywhere to open Quick Access</span>
            <span class="r2k-qs-footer-sep"></span>
            <span><kbd>ESC</kbd> to close</span>
        </div>
    </div>
</div>
