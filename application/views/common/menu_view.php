<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php 
    $CI = &get_instance();
    $ControllerName = get_class($CI);

    $firstSeg  = strtolower($CI->uri->segment(1) ?? '');
    $secondSeg = strtolower($CI->uri->segment(2) ?? '');
    $isSettingsPage = ($firstSeg === 'settings');

    $currentSettingsController = $isSettingsPage ? ucfirst($secondSeg) : null;

    $UserMainModule = $this->redisservice->getUserCache('menus') ?? [];
    $UserSubModule = $this->redisservice->getUserCache('submenus') ?? [];
?>

<!-- Menu -->
<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme d-flex flex-column" data-bs-theme="dark" style="height: 100vh;">
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


    <ul id="ModulesMenuBar" class="menu-inner py-1 flex-grow-1 overflow-auto <?php echo $isSettingsPage ? 'd-none' : ''; ?>">

        <!-- Dashboard -->
        <li class="menu-item <?php echo $ControllerName == "Dashboard" ? 'active' : ''; ?>">
            <a href="/dashboard" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-smile"></i>
                <div data-i18n="Main Menu">Dashboard</div>
            </a>
        </li>

        <?php if (count($UserMainModule) > 0) {
            $lastSettings = count($UserMainModule) - 1;
            foreach ($UserMainModule as $MMKey => $MMVal) {

                if ($MMKey === $lastSettings) continue;

                $SubMenuData = (count($UserSubModule) > 0) ? filterByMainMenuUID($UserSubModule, $MMVal->MainMenuUID) : []; ?>

                <!-- All Pages -->
                <li class="menu-item <?php echo in_array(strtolower($ControllerName), array_column($SubMenuData, 'ControllerName')) ? 'active' : ''; ?>">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="menu-icon tf-icons <?php echo $MMVal->MainMenuIcons; ?>"></i>
                        <div data-i18n="<?php echo $MMVal->MainMenuName; ?>"><?php echo $MMVal->MainMenuName; ?></div>
                    </a>


                    <?php if (count($SubMenuData) > 0) { ?>

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

        <!-- Profile Section -->
        <li class="menu-item user-profile-item">
            <a class="menu-link d-flex align-items-center" style="margin-inline: 0.5rem;" id="ProfileDropdownToggle" href="javascript:void(0);">
                <span class="profile-avatar-wrap">
                    <img src="<?php echo $JwtData->User->UserImage ? getenv('CFLARE_R2_CDN').$JwtData->User->UserImage : '/images/logo/avathar_user.png'; ?>"
                        alt="<?php echo htmlspecialchars($JwtData->User->FirstName); ?>" />
                </span>
                <div class="profile-text-info ms-2 flex-grow-1">
                    <div class="profile-name"><?php echo strtoupper($JwtData->User->FirstName); ?></div>
                    <div class="profile-role"><?php echo strtoupper($JwtData->User->RoleName); ?></div>
                </div>
                <i class="bx bx-chevron-down profile-caret"></i>
            </a>
            <ul id="ProfileDropdownMenu">
                <li><a class="dropdown-item" href="/settings/profile"><i class="bx bx-user"></i> My Profile</a></li>
                <li><a class="dropdown-item ChangePasswordBtn" href="javascript:void(0);"><i class="bx bx-lock"></i> Change Password</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="/logout"><i class="bx bx-power-off"></i> Log Out</a></li>
            </ul>
        </li>

    </ul>

    <ul id="SettingsMenuBar" style="margin-top: 0 !important;" class="menu-inner py-1 flex-grow-1 overflow-auto <?php echo $isSettingsPage ? '' : 'd-none'; ?>">

        <li class="menu-item">
            <a href="javascript: void(0);" id="SettingsBackMenuBarBtn" class="menu-link backtodashboard-btn d-flex align-items-center justify-content-center">
                <i class="menu-icon tf-icons bx bx-arrow-back"></i>
                <div data-i18n="Main Menu">Back to Menu</div>
            </a>
        </li>

    <?php $SubMenuData = [];
        if (count($UserSubModule) > 0) {
            $SubMenuData = filterByMainMenuUID($UserSubModule, $UserMainModule[count($UserMainModule) -1]->MainMenuUID);
        }

        foreach($SubMenuData as $SubKey => $SubVal) {
            $subUrlPath = ltrim($SubVal->UrlPath ?? ('settings/' . $SubVal->ControllerName), '/');
            $currentPath = trim($firstSeg . ($secondSeg ? '/' . $secondSeg : ''), '/');
            $isActive = ($isSettingsPage && $subUrlPath === $currentPath);
        ?>

        <li class="menu-item <?php echo $isActive ? 'active' : ''; ?>">
            <a href="/<?php echo htmlspecialchars($subUrlPath); ?>" class="menu-link">
                <i class="menu-icon tf-icons <?php echo htmlspecialchars($SubVal->SubMenuIcon ?? ''); ?>"></i>
                <div><?php echo htmlspecialchars($SubVal->SubMenuName); ?></div>
            </a>
        </li>

    <?php } ?>

    </ul>

</aside>
<!-- / Menu -->