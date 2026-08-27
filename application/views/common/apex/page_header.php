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
$_backUrl = $pageBackUrl     ?? ($PageBackUrl     ?? '');

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
        <?php if (!empty($_backUrl)): ?>
        <a href="<?php echo htmlspecialchars($_backUrl, ENT_QUOTES); ?>" class="apex-back-btn" title="Back">
            <i class="bx bx-arrow-back"></i>
        </a>
        <?php endif; ?>
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
            <span><?php echo t('search_go_module', 'Search or go to a module'); ?>...</span>
            <kbd class="apex-header-search-kbd">Ctrl K</kbd>
        </button>
        <div class="apex-nav-divider"></div>
        <!-- Quick Create -->
        <div class="apex-qc-wrap">
            <button class="apex-qc-btn" id="apexQuickCreateBtn" type="button" title="Quick Create">
                <i class="bx bx-plus"></i>
            </button>
            <div class="apex-qc-dropdown" id="apexQcDropdown">
                <div class="apex-qc-grid">
                    <!-- SALES -->
                    <div class="apex-qc-col apex-qc-col--sales">
                        <div class="apex-qc-col-header">
                            <div class="apex-qc-col-icon"><i class="bx bx-receipt"></i></div>
                            <span class="apex-qc-col-label">Sales</span>
                        </div>
                        <a href="/quotations/create" class="apex-qc-item"><i class="bx bx-notepad"></i>Quotation</a>
                        <a href="/proformainvoices/create" class="apex-qc-item"><i class="bx bx-file-find"></i>Proforma Invoice</a>
                        <a href="/salesorders/create" class="apex-qc-item"><i class="bx bx-cart-add"></i>Sales Order</a>
                        <a href="/invoices/create" class="apex-qc-item"><i class="bx bx-receipt"></i>Invoice</a>
                        <a href="/deliverychallans/create" class="apex-qc-item"><i class="bx bx-package"></i>Delivery Challan</a>
                        <a href="/salesreturns/create" class="apex-qc-item"><i class="bx bx-revision"></i>Sales Return</a>
                    </div>
                    <!-- PURCHASE -->
                    <div class="apex-qc-col apex-qc-col--purchase">
                        <div class="apex-qc-col-header">
                            <div class="apex-qc-col-icon"><i class="bx bx-shopping-bag"></i></div>
                            <span class="apex-qc-col-label">Purchase</span>
                        </div>
                        <a href="/purchaseorders/create" class="apex-qc-item"><i class="bx bx-list-ul"></i>Purchase Order</a>
                        <a href="/purchases/create" class="apex-qc-item"><i class="bx bx-shopping-bag"></i>Purchase</a>
                        <a href="/purchasereturns/create" class="apex-qc-item"><i class="bx bx-transfer-alt"></i>Purchase Return</a>
                    </div>
                    <!-- PARTY + INVENTORY -->
                    <div class="apex-qc-col apex-qc-col--party">
                        <div class="apex-qc-col-header">
                            <div class="apex-qc-col-icon"><i class="bx bx-group"></i></div>
                            <span class="apex-qc-col-label">Party</span>
                        </div>
                        <a href="/customers?action=create" class="apex-qc-item" data-qc-page="customers"><i class="bx bx-user-plus"></i>Customer</a>
                        <a href="/vendors?action=create" class="apex-qc-item" data-qc-page="vendors"><i class="bx bx-store"></i>Vendor</a>
                        <div class="apex-qc-subsection">
                            <div class="apex-qc-sub-header">
                                <div class="apex-qc-sub-icon"><i class="bx bx-box"></i></div>
                                <span class="apex-qc-sub-label">Inventory</span>
                            </div>
                            <a href="/products?action=create" class="apex-qc-item" data-qc-page="products"><i class="bx bx-box"></i>Product</a>
                        </div>
                    </div>
                    <!-- ACCOUNTING -->
                    <div class="apex-qc-col apex-qc-col--accounting">
                        <div class="apex-qc-col-header">
                            <div class="apex-qc-col-icon"><i class="bx bx-wallet"></i></div>
                            <span class="apex-qc-col-label">Accounting</span>
                        </div>
                        <a href="/expenses/create" class="apex-qc-item"><i class="bx bx-money-withdraw"></i>Expense</a>
                        <a href="/indirectincome?action=create" class="apex-qc-item"><i class="bx bx-trending-up"></i>Indirect Income</a>
                    </div>
                </div>
            </div>
        </div>
        <!-- Language Switcher -->
        <?php $_apexLang = $JwtData->User->UILanguage ?? 'en'; ?>
        <div class="apex-lang-wrap" id="apexLangWrap">
            <button class="apex-lang-trigger" id="apexLangBtn" type="button"
                    title="<?php echo t('lbl_language', 'Language'); ?>">
                <span class="apex-lang-label"><?php echo $_apexLang === 'ta' ? 'த' : 'En'; ?></span>
                <i class="bx bx-chevron-down apex-lang-caret"></i>
            </button>
            <div class="apex-lang-dropdown" id="apexLangDropdown">
                <button class="apex-lang-option<?php echo $_apexLang === 'en' ? ' active' : ''; ?>"
                        data-lang="en" type="button">
                    <span class="apex-lang-opt-name">English</span>
                    <i class="bx bx-check apex-lang-check"></i>
                </button>
                <button class="apex-lang-option<?php echo $_apexLang === 'ta' ? ' active' : ''; ?>"
                        data-lang="ta" type="button">
                    <span class="apex-lang-opt-name">தமிழ்</span>
                    <i class="bx bx-check apex-lang-check"></i>
                </button>
            </div>
        </div>
        <!-- Help -->
        <button class="apex-nav-btn" id="apexHelpBtn" title="<?php echo t('lbl_help', 'Help'); ?>" type="button">
            <i class="bx bx-help-circle"></i>
        </button>
        <!-- Notifications -->
        <button class="apex-nav-btn" id="apexNotifBtn" title="<?php echo t('lbl_notifications', 'Notifications'); ?>" type="button">
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
                    <?php
                    $_llOn = $JwtData->User->LastLoginOn ?? null;
                    if (!empty($_llOn)):
                        $_llTs = viewPageDateTimeFormat($_llOn, $JwtData->User->Timezone ?? 'UTC', 2);
                    ?>
                    <div class="apex-user-dd-lastlogin">
                        <span class="apex-user-dd-ll-label"><?php echo t('last_login', 'Last login'); ?></span>
                        <span class="apex-user-dd-ll-time"><i class="bx bx-time-five"></i><?php echo $_llTs->formatted; ?></span>
                        <?php if (!empty($JwtData->User->LastLoginDevice ?? null)): ?>
                        <span class="apex-user-dd-ll-device"><?php echo htmlspecialchars(mb_strimwidth($JwtData->User->LastLoginDevice, 0, 42, '…')); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="apex-user-dd-divider"></div>
                <a href="/settings/profile" class="apex-user-dd-item">
                    <i class="bx bx-user"></i> <?php echo t('my_profile', 'My Profile'); ?>
                </a>
                <a href="/settings/generalsettings" class="apex-user-dd-item">
                    <i class="bx bx-cog"></i> <?php echo t('settings', 'Settings'); ?>
                </a>
                <button class="apex-user-dd-item ChangePasswordBtn" type="button">
                    <i class="bx bx-lock"></i> <?php echo t('change_password', 'Change Password'); ?>
                </button>
                <div class="apex-user-dd-divider"></div>
                <a href="/logout" class="apex-user-dd-item apex-user-dd-logout">
                    <i class="bx bx-power-off"></i> <?php echo t('log_out', 'Log Out'); ?>
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
                <div class="r2k-qs-title"><?php echo t('quick_access', 'Quick Access'); ?></div>
                <div class="r2k-qs-subtitle"><?php echo t('search_go_module', 'Search or go to any module'); ?></div>
            </div>
            <button class="r2k-qs-close" id="apexQuickAccessClose" type="button" title="Close">
                <i class="bx bx-x"></i>
            </button>
        </div>
        <!-- Search bar with Ctrl K badge -->
        <div class="r2k-qs-search-row">
            <i class="bx bx-search r2k-qs-search-icon"></i>
            <input type="text" id="apexQuickSearchInput" class="r2k-qs-search-input"
                   placeholder="<?php echo t('search_go_module', 'Search or go to a module'); ?>..." autocomplete="off" spellcheck="false">
            <span class="r2k-qs-kbd-badge"><kbd>Ctrl</kbd><kbd>K</kbd></span>
        </div>
        <div class="r2k-qs-body" id="apexQABody">
            <!-- built by ApexHeader on DOMReady -->
        </div>
        <div class="r2k-qs-footer">
            <i class="bx bx-bulb r2k-qs-footer-tip-icon"></i>
            <span><?php echo t('tip_ctrl_k', 'Tip: Press Ctrl K anywhere to open Quick Access'); ?></span>
            <span class="r2k-qs-footer-sep"></span>
            <span><kbd>ESC</kbd> to close</span>
        </div>
    </div>
</div>
