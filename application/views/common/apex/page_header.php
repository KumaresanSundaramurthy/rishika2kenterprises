<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$_icon    = $pageIcon        ?? 'bx-file-blank';
$_iconBg  = $pageIconBg      ?? '#eef2ff';
$_iconClr = $pageIconColor   ?? '#696cff';
$_title   = $pageTitle       ?? 'Page';
$_desc    = $pageDescription ?? '';
?>
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
        <!-- Settings -->
        <a href="/settings" class="apex-nav-btn" title="Settings">
            <i class="bx bx-cog"></i>
        </a>
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
                <a href="/settings" class="apex-user-dd-item">
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
    <div class="apex-qa-box">
        <div class="apex-qa-header">
            <div>
                <div class="apex-qa-title">Quick Access</div>
                <div class="apex-qa-subtitle">Search or go to any module</div>
            </div>
            <button class="apex-qa-close" id="apexQuickAccessClose" type="button">
                <i class="bx bx-x"></i>
            </button>
        </div>
        <div class="apex-qa-search-wrap">
            <i class="bx bx-search apex-qa-search-icon"></i>
            <input type="text" id="apexQuickSearchInput" class="apex-qa-search-input"
                   placeholder="Search or go to a module..." autocomplete="off">
        </div>
        <div class="apex-qa-body" id="apexQABody">
            <div class="apex-qa-section-label">All Modules</div>
            <div class="apex-qa-modules-grid" id="apexQAModulesGrid">
                <!-- Populated by ApexHeader.loadModules() -->
            </div>
        </div>
        <div class="apex-qa-footer">
            <i class="bx bx-bulb"></i>
            <span>Tip: You can also use <kbd>Ctrl</kbd> <kbd>K</kbd> anywhere to open Quick Access</span>
            <span class="ms-auto"><kbd>ESC</kbd> to close</span>
        </div>
    </div>
</div>
