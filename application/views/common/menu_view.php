<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php 
    $CI = &get_instance();
    $ControllerName = get_class($CI);

    $firstSeg  = strtolower($CI->uri->segment(1) ?? '');
    $secondSeg = strtolower($CI->uri->segment(2) ?? '');
    $isSettingsPage = ($firstSeg === 'settings');

    $currentSettingsController = $isSettingsPage ? ucfirst($secondSeg) : null;

    $UserMainModule = $this->redis_cache->get('Redis_UserMainModule')->Value ?? [];
    $UserSubModule = $this->redis_cache->get('Redis_UserSubModule')->Value ?? [];
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

    <ul id="ModulesMenuBar" class="menu-inner py-1 <?php echo $isSettingsPage ? 'd-none' : ''; ?>">

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

        <!-- Profile Dropdown – properly styled for the sidebar menu -->
        <li class="menu-item user-profile-item">
            <a class="menu-link dropdown-toggle" href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false" style="display: flex; align-items: center; gap: 0.75rem;">
                <div class="avatar avatar-online flex-shrink-0">
                    <img src="<?php echo $JwtData->User->UserImage ? getenv('CFLARE_R2_CDN').$JwtData->User->UserImage : '/images/logo/avathar_user.png'; ?>" 
                        alt="<?php echo strtoupper($JwtData->User->FirstName); ?>" 
                        class="w-px-36 h-px-36 rounded-circle" />
                </div>
                <div class="flex-grow-1" style="line-height: 1.2;">
                    <div class="fw-semibold" style="font-size: 0.9rem;"><?php echo strtoupper($JwtData->User->FirstName); ?></div>
                    <small class="text-muted" style="font-size: 0.7rem;"><?php echo strtoupper($JwtData->User->RoleName); ?></small>
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow" style="position: absolute; inset: auto auto auto auto; margin-top: 0.25rem;">
                <li>
                    <a class="dropdown-item" href="/settings/profile">
                        <i class="bx bx-user me-2"></i> My Profile
                    </a>
                </li>
                <?php if ($JwtData->User->RoleUID == 1) { ?>
                <li>
                    <a class="dropdown-item" href="javascript:void(0);">
                        <i class="bx bx-cog me-2"></i> Settings
                    </a>
                </li>
                <?php } ?>
                <li>
                    <a class="dropdown-item" id="ChangePasswordBtn" href="javascript:void(0);">
                        <i class="bx bx-lock me-2"></i> Change Password
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item" href="/logout">
                        <i class="bx bx-power-off me-2"></i> Log Out
                    </a>
                </li>
            </ul>
        </li>

        <li class="menu-item menu-item-static mt-auto">
            <a href="javascript: void(0);" id="SettingsMenuBarBtn" class="menu-link">
                <i class="menu-icon tf-icons bx bx-cog"></i>
                <div data-i18n="Settings">Settings</div>
            </a>
        </li>

    </ul>

    <ul id="SettingsMenuBar" class="menu-inner py-1 <?php echo $isSettingsPage ? '' : 'd-none'; ?>">

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
            $isActive = ($isSettingsPage && strtolower($SubVal->ControllerName) === $secondSeg);
        ?>

        <li class="menu-item <?php echo $isActive ? 'active' : ''; ?>">
            <a href="/settings/<?php echo $SubVal->ControllerName; ?>" class="menu-link">
                <i class="menu-icon tf-icons <?php echo $SubVal->Icons; ?>"></i>
                <div><?php echo $SubVal->SubMenuName; ?></div>
            </a>
        </li>

    <?php } ?>

    </ul>

</aside>
<!-- / Menu -->