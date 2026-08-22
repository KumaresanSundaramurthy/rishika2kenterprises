<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends MY_Controller {

    protected $EndReturnData;

    public function __construct() {
        parent::__construct();
    }

    // ── AJAX: rebuild Redis cache (menus, permissions, settings) ─────────────
    // Called silently after any operation that changes settings or permissions
    // so the new values take effect on the very next request without a page reload.

    public function refreshTokens(): void {

        $this->EndReturnData = new stdClass();
        try {
            $JwtData     = $this->pageData['JwtData'];
            $userUID     = $JwtData->User->UserUID;
            $orgUID      = $JwtData->Org->OrgUID;
            $roleUID     = $JwtData->User->RoleUID;
            $orgToken    = $JwtData->Org->OrgToken ?? '';
            $loginExpiry = (int) getenv('LOGIN_EXPIRE_SECS');

            $this->load->model('login_model');
            $this->load->model('user_model');

            $menus       = $this->login_model->getRoleMainMenus($roleUID)->Data;
            $submenus    = $this->login_model->getRoleSubMenus($roleUID)->Data;
            $modules     = $this->login_model->getModuleDetails($orgUID)->Data;
            $userInfoRes = $this->user_model->getUserByUserInfo(['User.UserUID' => $userUID]);
            $userInfo    = ($userInfoRes->Error === FALSE && !empty($userInfoRes->Data)) ? $userInfoRes->Data[0] : null;

            // Build permissions map — same structure as login
            $permissions = [];
            foreach ($submenus as $sm) {
                if (!empty($sm->ControllerName)) {
                    $permissions[$sm->ControllerName] = [
                        'CanView'   => (int)$sm->CanView,
                        'CanCreate' => (int)$sm->CanCreate,
                        'CanEdit'   => (int)$sm->CanEdit,
                        'CanDelete' => (int)$sm->CanDelete,
                    ];
                }
            }

            $this->redisservice->setUserCache('menus',       $userUID, $menus,       $loginExpiry, $orgToken);
            $this->redisservice->setUserCache('submenus',    $userUID, $submenus,    $loginExpiry, $orgToken);
            $this->redisservice->setUserCache('modules',     $userUID, $modules,     $loginExpiry, $orgToken);
            $this->redisservice->setUserCache('permissions', $userUID, $permissions, $loginExpiry, $orgToken);
            if ($userInfo) {
                $this->redisservice->setUserCache('userinfo', $userUID, $userInfo, $loginExpiry, $orgToken);
            }

            // Rebuild JWT payload in Redis
            $this->globalservice->refreshUserCache();

            // Rebuild org info cache with fresh DB data
            $this->redisservice->deleteCache($this->redisservice->orgKey('org-info'));
            $this->load->model('organisation_model');
            $this->organisation_model->getOrgInfoCached($orgUID);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Cache refreshed successfully. Changes will reflect on next page load.';

        } catch (Exception $e) {
            $this->notifyError('Auth::refreshTokens', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }
}
