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

    <style>
        /* Profile avatar ring */
        .profile-avatar-wrap {
            width: 38px; height: 38px;
            border-radius: 50%;
            border: 2px solid rgba(255,255,255,0.25);
            padding: 2px;
            flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            background: rgba(255,255,255,0.07);
            transition: border-color 0.2s ease;
        }
        .user-profile-item:hover .profile-avatar-wrap,
        .user-profile-item.profile-open .profile-avatar-wrap {
            border-color: rgba(255,255,255,0.55);
        }
        .profile-avatar-wrap img {
            width: 30px; height: 30px;
            border-radius: 50%;
            object-fit: cover;
            display: block;
        }
        /* Profile text */
        .profile-text-info {
            line-height: 1.25;
            overflow: hidden;
        }
        .profile-text-info .profile-name {
            font-size: 0.82rem;
            font-weight: 600;
            color: rgba(255,255,255,0.92);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .profile-text-info .profile-role {
            font-size: 0.68rem;
            color: rgba(255,255,255,0.45);
            white-space: nowrap;
        }
        /* Caret */
        .profile-caret {
            margin-left: auto;
            font-size: 1rem;
            color: rgba(255,255,255,0.35);
            transition: transform 0.2s ease, color 0.2s ease;
            flex-shrink: 0;
        }
        .user-profile-item.profile-open .profile-caret {
            transform: rotate(180deg);
            color: rgba(255,255,255,0.7);
        }
        /* Dropdown panel */
        #ProfileDropdownMenu {
            display: none;
            list-style: none;
            margin: 4px 8px 8px 8px;
            padding: 6px 4px;
            border-radius: 8px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.08);
        }
        #ProfileDropdownMenu .dropdown-item {
            color: rgba(255,255,255,0.72);
            font-size: 0.82rem;
            padding: 7px 10px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: background 0.15s, color 0.15s;
        }
        #ProfileDropdownMenu .dropdown-item:hover {
            color: #fff;
            background: rgba(255,255,255,0.1);
        }
        #ProfileDropdownMenu .dropdown-item i { font-size: 0.95rem; }
        #ProfileDropdownMenu .dropdown-divider { border-color: rgba(255,255,255,0.08); margin: 4px 0; }
        /* Profile toggle row */
        .user-profile-item > a.menu-link {
            padding-top: 10px; padding-bottom: 10px;
            gap: 10px;
        }
    </style>

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
            <a class="menu-link d-flex align-items-center" id="ProfileDropdownToggle" href="javascript:void(0);">
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
                <li><a class="dropdown-item" id="ChangePasswordBtn" href="javascript:void(0);"><i class="bx bx-lock"></i> Change Password</a></li>
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