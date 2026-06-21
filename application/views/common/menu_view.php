<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php 
    $CI = &get_instance();
    $ControllerName = get_class($CI);

    $firstSeg  = strtolower($CI->uri->segment(1) ?? '');
    $secondSeg = strtolower($CI->uri->segment(2) ?? '');
    $isSettingsPage = ($firstSeg === 'settings');

    $currentSettingsController = $isSettingsPage ? ucfirst($secondSeg) : null;

    // Full current path including query string — used for exact URL-based active matching.
    // Prevents multiple items that share the same ControllerName (e.g. Products list vs
    // Products?tab=category) from all being marked active at once.
    $_reqUri         = $CI->input->server('REQUEST_URI') ?? '';
    $_parsedPath     = ltrim(parse_url($_reqUri, PHP_URL_PATH) ?? $firstSeg, '/');
    $_parsedQuery    = parse_url($_reqUri, PHP_URL_QUERY) ?? '';
    $currentPathFull = strtolower($_parsedPath . ($_parsedQuery ? '?' . $_parsedQuery : ''));

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
            $lastSettings    = count($UserMainModule) - 1;
            $menuActiveFound = false; // ensure only ONE main menu item is ever highlighted
            foreach ($UserMainModule as $MMKey => $MMVal) {

                if ($MMKey === $lastSettings) continue;

                $SubMenuData  = (count($UserSubModule) > 0) ? filterByMainMenuUID($UserSubModule, $MMVal->MainMenuUID) : [];
                $isDirectLink = !empty($MMVal->IsDirectLink);
                $directUrl    = $isDirectLink ? ('/' . ltrim($MMVal->DirectUrl ?? '', '/')) : null;

                // Only leaf (non-parent) submenu items represent actual pages — parent/group items
                // can carry a ControllerName that falsely matches another page's controller.
                $leafSubMenus = array_values(array_filter($SubMenuData, fn($sm) => empty($sm->IsParent)));

                // Use $firstSeg (URL path) for matching; lowercase both sides for reliability.
                // Stop after the first match so only one main menu item is ever active.
                $isMenuActive = !$menuActiveFound && ($isDirectLink
                    ? ($firstSeg === strtolower(ltrim($MMVal->DirectUrl ?? '', '/')))
                    : in_array($firstSeg, array_map('strtolower', array_column($leafSubMenus, 'ControllerName'))));
                if ($isMenuActive) $menuActiveFound = true;
                ?>

                <!-- All Pages -->
                <li class="menu-item <?php echo $isMenuActive ? 'active' : ''; ?>">
                    <?php if ($isDirectLink): ?>
                        <a href="<?php echo htmlspecialchars($directUrl); ?>" class="menu-link">
                            <i class="menu-icon tf-icons <?php echo $MMVal->MainMenuIcons; ?>"></i>
                            <div data-i18n="<?php echo $MMVal->MainMenuName; ?>"><?php echo $MMVal->MainMenuName; ?></div>
                        </a>
                    <?php else: ?>
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <i class="menu-icon tf-icons <?php echo $MMVal->MainMenuIcons; ?>"></i>
                            <div data-i18n="<?php echo $MMVal->MainMenuName; ?>"><?php echo $MMVal->MainMenuName; ?></div>
                        </a>
                        <?php if (count($SubMenuData) > 0): ?>
                        <ul class="menu-sub">
                            <?php $this->globalservice->renderSubMenu($ControllerName, $SubMenuData, null, $currentPathFull); ?>
                        </ul>
                        <?php endif; ?>
                    <?php endif; ?>
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

    <ul id="SettingsMenuBar" style="margin-top: 0 !important;" class="menu-inner py-1 flex-grow-1 overflow-auto <?php echo $isSettingsPage ? '' : 'd-none'; ?>">

        <li class="menu-item">
            <a href="javascript: void(0);" id="SettingsBackMenuBarBtn" class="menu-link backtodashboard-btn d-flex align-items-center justify-content-center">
                <i class="menu-icon tf-icons bx bx-arrow-back"></i>
                <div data-i18n="Main Menu">Back to Menu</div>
            </a>
        </li>

    <?php
        $SubMenuData = [];
        if (count($UserSubModule) > 0) {
            $SubMenuData = filterByMainMenuUID($UserSubModule, $UserMainModule[count($UserMainModule) - 1]->MainMenuUID);
        }
        $topItems = array_values(array_filter($SubMenuData, function($sm) { return empty($sm->ParentSubMenuUID); }));
        usort($topItems, fn($a, $b) => ($a->Sorting ?? 0) <=> ($b->Sorting ?? 0));
        foreach ($topItems as $item):
            $icon = !empty($item->SubMenuIcon) ? '<i class="menu-icon tf-icons ' . htmlspecialchars($item->SubMenuIcon) . '"></i>' : '';
            if (!empty($item->IsParent)):
                $isAnyChildActive = $this->globalservice->hasActiveDescendant($SubMenuData, $item->SubMenuUID, $ControllerName, $currentPathFull);
                $liClass = $isAnyChildActive ? 'menu-item open active' : 'menu-item';
    ?>
            <li class="<?php echo $liClass; ?>">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <?php echo $icon; ?>
                    <div data-i18n="<?php echo htmlspecialchars($item->SubMenuName); ?>"><?php echo htmlspecialchars($item->SubMenuName); ?></div>
                </a>
                <ul class="menu-sub">
                    <?php $this->globalservice->renderSubMenu($ControllerName, $SubMenuData, $item->SubMenuUID, $currentPathFull); ?>
                </ul>
            </li>
    <?php
            else:
                $itemPath = ltrim($item->UrlPath ?? $item->ControllerName, '/');
                $isActive = (strtolower($itemPath) === $currentPathFull);
                $liClass  = $isActive ? 'menu-item active' : 'menu-item';
                $href     = $isActive ? 'javascript:void(0);' : '/' . htmlspecialchars($itemPath);
    ?>
            <li class="<?php echo $liClass; ?>">
                <a href="<?php echo $href; ?>" class="menu-link">
                    <?php echo $icon; ?>
                    <div data-i18n="<?php echo htmlspecialchars($item->SubMenuName); ?>"><?php echo htmlspecialchars($item->SubMenuName); ?></div>
                </a>
            </li>
    <?php
            endif;
        endforeach;
    ?>

    </ul>

</aside>
<!-- / Menu -->