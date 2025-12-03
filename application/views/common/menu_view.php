<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php 

$CI = &get_instance();
$ControllerName = get_class($CI); 

$UserMainModule = $this->redis_cache->get('Redis_UserMainModule') ?? [];
$UserSubModule = $this->redis_cache->get('Redis_UserSubModule') ?? [];

?>

<!-- Menu -->
<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme" data-bs-theme="dark">
    <div class="app-brand demo">
        <a href="/dashboard" class="app-brand-link">
            <span class="app-brand-logo demo">
                <img src="/images/logo/favicon_io/android-chrome-512x512-1.png" width="40px;" height="40px;" alt="<?php echo strtoupper(getSiteConfiguration()->MenuName); ?>" />
            </span>
            <span class="app-brand-text demo menu-text fw-bolder ms-2"><?php echo strtoupper(getSiteConfiguration()->MenuName); ?></span>
        </a>

        <a href="javascript: void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
            <i class="bx bx-chevron-left bx-sm align-middle"></i>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul id="ModulesMenuBar" class="menu-inner py-1">

        <!-- Dashboard -->
        <li class="menu-item <?php echo $ControllerName == "Dashboard" ? 'active' : ''; ?>">
            <a href="/dashboard" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-smile"></i>
                <div data-i18n="Main Menu">Dashboard</div>
            </a>
        </li>

        <?php if (sizeof($UserMainModule) > 0) {
            foreach ($UserMainModule as $MMKey => $MMVal) {
                $SubMenuData = [];
                if (sizeof($UserSubModule) > 0) {
                    $SubMenuData = filterByMainMenuUID($UserSubModule, $MMVal->MainMenuUID);
                } ?>

                <!-- All Pages -->
                <li class="menu-item <?php echo in_array(strtolower($ControllerName), array_column($SubMenuData, 'ControllerName')) ? 'active' : ''; ?>">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="menu-icon tf-icons <?php echo $MMVal->MainMenuIcons; ?>"></i>
                        <div data-i18n="<?php echo $MMVal->MainMenuName; ?>"><?php echo $MMVal->MainMenuName; ?></div>
                    </a>


                    <?php if (sizeof($SubMenuData) > 0) { ?>

                        <ul class="menu-sub">

                            <?php $this->globalservice->renderSubMenu($ControllerName, $SubMenuData, null); ?>

                        </ul>

                    <?php } ?>

                </li>

        <?php }
        } ?>

        <li class="menu-item menu-item-static mt-auto">
            <a href="javascript: void(0);" id="SettingsMenuBarBtn" class="menu-link">
                <i class="menu-icon tf-icons bx bx-cog"></i>
                <div data-i18n="Settings">Settings</div>
            </a>
        </li>

    </ul>

    <ul id="SettingsMenuBar" class="menu-inner py-1 d-none">

        <li class="menu-item">
            <a href="javascript: void(0);" id="SettingsBackMenuBarBtn" class="menu-link backtodashboard-btn d-flex align-items-center justify-content-center">
                <i class="menu-icon tf-icons bx bx-arrow-back"></i>
                <div data-i18n="Main Menu">Back to Menu</div>
            </a>
        </li>

        <li class="menu-item">
            <a href="/settings/account" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-smile"></i>
                <div data-i18n="Account">Account</div>
            </a>
        </li>

        <li class="menu-item">
            <a href="/settings/general-settings" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-smile"></i>
                <div data-i18n="General Settings">General Settings</div>
            </a>
        </li>

        <li class="menu-item">
            <a href="/settings/manage-users" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-smile"></i>
                <div data-i18n="Manage Users">Manage Users</div>
            </a>
        </li>

        <li class="menu-item">
            <a href="/settings/reminders" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-smile"></i>
                <div data-i18n="Reminders">Reminders</div>
            </a>
        </li>

    </ul>

</aside>
<!-- / Menu -->