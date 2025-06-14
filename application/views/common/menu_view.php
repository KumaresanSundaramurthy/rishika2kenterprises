<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $CI =& get_instance();
$ControllerName = get_class($CI); ?>

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

    <ul class="menu-inner py-1">

        <!-- Dashboard -->
        <li class="menu-item <?php echo $ControllerName == "Dashboard" ? 'active' : ''; ?>">
            <a href="/dashboard" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-smile"></i>
                <div data-i18n="Main Menu">Dashboard</div>
            </a>
        </li>

        <?php if (sizeof($JwtData->UserMainModule) > 0) {
            foreach ($JwtData->UserMainModule as $MMKey => $MMVal) {
                $SubMenuData = [];
                if (sizeof($JwtData->UserSubModule) > 0) {
                    $SubMenuData = filterByMainMenuUID($JwtData->UserSubModule, $MMVal->MainMenuUID);
                } ?>

                <!-- All Pages -->
                <li class="menu-item <?php echo in_array(strtolower($ControllerName), array_column($SubMenuData, 'ControllerName')) ? 'active' : ''; ?>">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="menu-icon tf-icons <?php echo $MMVal->MainMenuIcons; ?>"></i>
                        <div data-i18n="<?php echo $MMVal->MainMenuName; ?>"><?php echo $MMVal->MainMenuName; ?></div>
                    </a>


                    <?php if (sizeof($SubMenuData) > 0) { ?>

                        <ul class="menu-sub">

                            <?php foreach ($SubMenuData as $SMKey => $SMVal) { ?>

                                <li class="menu-item <?php echo strtolower($ControllerName) == strtolower($SMVal->ControllerName) ? 'active' : ''; ?>">
                                    <a href="/<?php echo $SMVal->ControllerName; ?>" class="menu-link">
                                        <div data-i18n="<?php echo $SMVal->SubMenuName; ?>"><?php echo $SMVal->SubMenuName; ?></div>
                                    </a>
                                </li>

                            <?php } ?>

                        </ul>

                    <?php } ?>

                </li>

        <?php }
        } ?>

    </ul>
</aside>
<!-- / Menu -->