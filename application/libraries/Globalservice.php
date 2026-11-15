<?php defined('BASEPATH') OR exit('No direct script access allowed');

Class Globalservice {

    protected $CI;

    public function __construct() {

        $this->CI =& get_instance();

    }

    public function renderSubMenu($ControllerName, $allSubMenus, $parentUID = null, $currentUrlPath = null) {

        // Filter only menus under current parent
        $filteredMenus = array_filter($allSubMenus, function($sm) use ($parentUID) {
            return $sm->ParentSubMenuUID == $parentUID;
        });

        // Sort by 'Sorting' property
        usort($filteredMenus, function($a, $b) {
            return $a->Sorting <=> $b->Sorting;
        });

        // Loop through and render
        foreach ($filteredMenus as $subMenu) {
            // Find children
            $childMenus = array_filter($allSubMenus, function($sm) use ($subMenu) {
                return $sm->ParentSubMenuUID == $subMenu->SubMenuUID;
            });

            $iconHtml = !empty($subMenu->SubMenuIcon)
                ? '<i class="menu-icon tf-icons ' . htmlspecialchars($subMenu->SubMenuIcon) . '"></i>'
                : '';

            if (!empty($subMenu->IsParent)) {
                // Group item — check if any descendant is active to open the group
                $hasActiveChild = $this->_hasActiveDescendant($allSubMenus, $subMenu->SubMenuUID, $ControllerName, $currentUrlPath);
                $openClass = $hasActiveChild ? ' open active' : '';
                echo '<li class="menu-item' . $openClass . '">';
                echo '<a href="javascript:void(0);" class="menu-link menu-toggle">';
                echo $iconHtml;
                echo '<div data-i18n="' . htmlspecialchars($subMenu->SubMenuName) . '">' . htmlspecialchars($subMenu->SubMenuName) . '</div>';
                echo '</a>';
                echo '<ul class="menu-sub">';
                $this->renderSubMenu($ControllerName, $allSubMenus, $subMenu->SubMenuUID, $currentUrlPath);
                echo '</ul>';
                echo '</li>';
            } else {
                $urlPath = site_url(ltrim($subMenu->UrlPath ?? $subMenu->ControllerName, '/'));
                if ($currentUrlPath !== null) {
                    $itemPath = strtolower(ltrim($subMenu->UrlPath ?? $subMenu->ControllerName, '/'));
                    $isActive = ($itemPath === strtolower(ltrim($currentUrlPath, '/')));
                } else {
                    $isActive = (strtolower($ControllerName) == strtolower($subMenu->ControllerName));
                }
                $activeClass = $isActive ? 'active' : '';
                $href = $isActive ? 'javascript: void(0);' : $urlPath;
                echo '<li class="menu-item ' . $activeClass . '">';
                echo '<a href="' . $href . '" class="menu-link">';
                echo $iconHtml;
                echo '<div data-i18n="' . htmlspecialchars($subMenu->SubMenuName) . '">' . htmlspecialchars($subMenu->SubMenuName) . '</div>';
                echo '</a>';
                echo '</li>';
            }
        }

    }

    public function hasActiveDescendant($allSubMenus, $parentUID, $ControllerName, $currentUrlPath = null) {
        return $this->_hasActiveDescendant($allSubMenus, $parentUID, $ControllerName, $currentUrlPath);
    }

    private function _hasActiveDescendant($allSubMenus, $parentUID, $ControllerName, $currentUrlPath = null) {
        foreach ($allSubMenus as $sm) {
            if ($sm->ParentSubMenuUID != $parentUID) continue;
            if (!empty($sm->IsParent)) {
                if ($this->_hasActiveDescendant($allSubMenus, $sm->SubMenuUID, $ControllerName, $currentUrlPath)) return true;
            } else {
                if ($currentUrlPath !== null) {
                    $itemPath = strtolower(ltrim($sm->UrlPath ?? $sm->ControllerName, '/'));
                    if ($itemPath === strtolower(ltrim($currentUrlPath, '/'))) return true;
                } else {
                    if (strtolower($ControllerName) == strtolower($sm->ControllerName)) return true;
                }
            }
        }
        return false;
    }

    public function sendJsonResponse($data): void {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json === false) {
            $json = json_encode(['Error' => true, 'Message' => 'Response encoding failed: ' . json_last_error_msg()]);
        }
        // Discard any stray output (BOM, whitespace) buffered from included PHP files
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        echo $json;
        exit;
    }

}