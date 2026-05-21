<?php defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Controller extends CI_Controller {

    public $pageData = [];

    /**
     * Looks up the module record from the Redis module cache and sets:
     *   $this->pageData['PageTitle']  — DisplayName (falls back to Name)
     *   $this->pageData['PageIcon']   — Icon class string
     *
     * @param int|null $moduleUID  Pass a UID to search by ID; omit to search by controller name.
     * @return bool  true = found, false = module not configured in ModuleTbl
     */
    protected function _loadPageTitle($moduleUID = null) {
        $modules = (array)($this->redisservice->getUserCache('modules') ?? []);

        // Cache miss — don't block; title stays empty
        if (empty($modules)) {
            $this->pageData['PageTitle'] = '';
            $this->pageData['PageIcon']  = '';
            return true;
        }

        $found = null;
        if ($moduleUID !== null) {
            foreach ($modules as $m) {
                if ((int)$m->ModuleUID === (int)$moduleUID) { $found = $m; break; }
            }
        } else {
            $controllerName = strtolower($this->router->fetch_class());
            foreach ($modules as $m) {
                if ($m->ControllerName === $controllerName) { $found = $m; break; }
            }
        }

        if (!$found) return false;

        $this->pageData['PageTitle'] = !empty($found->DisplayName) ? $found->DisplayName : ($found->Name ?? '');
        $this->pageData['PageIcon']  = $found->Icon ?? '';
        return true;
    }

}
