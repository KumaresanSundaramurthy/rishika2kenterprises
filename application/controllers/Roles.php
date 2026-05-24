<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Roles extends MY_Controller {

    public $pageData = array();
    private $EndReturnData;

    public function __construct() {
        parent::__construct();
    }

    // ── Page ─────────────────────────────────────────────────────────

    public function index() {

        if (!$this->_loadPageTitle()) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }

        try {
            $this->load->model('roles_model');

            // All roles for this org
            $JwtData = $this->pageData['JwtData'];
            $rolesResult = $this->roles_model->getRolesList($JwtData->User->OrgUID);
            $this->pageData['RolesList'] = $rolesResult->Error === FALSE ? $rolesResult->Data : [];

            // All main menus + sub menus (for the permission matrix)
            $menusResult = $this->roles_model->getAllMenusForMatrix($JwtData->User->OrgUID);
            $this->pageData['AllMainMenus'] = $menusResult->Error === FALSE ? ($menusResult->Data['main'] ?? []) : [];
            $this->pageData['AllSubMenus']  = $menusResult->Error === FALSE ? ($menusResult->Data['sub']  ?? []) : [];

            $this->load->view('roles/view', $this->pageData);

        } catch (Exception $e) {
            redirect('dashboard', 'refresh');
        }

    }

    // ── AJAX: refresh Redis cache (menus, permissions, settings) ────

    public function refreshTokens() {

        $this->EndReturnData = new stdClass();
        try {
            $JwtData    = $this->pageData['JwtData'];
            $userUID    = $JwtData->User->UserUID;
            $orgUID     = $JwtData->User->OrgUID;
            $roleUID    = $JwtData->User->RoleUID;
            $loginExpiry = (int) getenv('LOGIN_EXPIRE_SECS');

            $this->load->model('login_model');
            $this->load->model('user_model');

            $menus       = $this->login_model->getRoleMainMenus($roleUID)->Data;
            $submenus    = $this->login_model->getRoleSubMenus($roleUID)->Data;
            $modules     = $this->login_model->getModuleDetails($orgUID)->Data;
            $settings    = $this->login_model->getOrgGeneralSettings($orgUID)->Data[0] ?? null;
            $userInfoRes = $this->user_model->getUserByUserInfo(['User.UserUID' => $userUID]);
            $userInfo    = ($userInfoRes->Error === FALSE && !empty($userInfoRes->Data)) ? $userInfoRes->Data[0] : null;

            // Build permissions map same as login
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

            $this->redisservice->setUserCache('menus',       $userUID, $menus,       $loginExpiry);
            $this->redisservice->setUserCache('submenus',    $userUID, $submenus,    $loginExpiry);
            $this->redisservice->setUserCache('modules',     $userUID, $modules,     $loginExpiry);
            $this->redisservice->setUserCache('permissions', $userUID, $permissions, $loginExpiry);
            $this->redisservice->setUserCache('settings',    $userUID, $settings,    $loginExpiry);
            if ($userInfo) {
                $this->redisservice->setUserCache('userinfo', $userUID, $userInfo, $loginExpiry);
            }

            // Rebuild JWT payload (User + Permissions) in Redis
            $this->globalservice->refreshUserCache();

            // Rebuild org info cache with fresh DB data + resolved CDN URL
            $this->redisservice->deleteCache($this->redisservice->orgKey('org_info'));
            $this->load->model('organisation_model');
            $this->organisation_model->getOrgInfoCached($orgUID);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Cache refreshed successfully. Changes will reflect on next page load.';

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── AJAX: list roles ─────────────────────────────────────────────

    public function getRolesList() {

        $this->EndReturnData = new stdClass();
        try {
            $this->load->model('roles_model');
            $JwtData = $this->pageData['JwtData'];
            $result  = $this->roles_model->getRolesList($JwtData->User->OrgUID);

            $this->EndReturnData->Error   = $result->Error;
            $this->EndReturnData->Message = $result->Message;
            $this->EndReturnData->Data    = $result->Error === FALSE ? $result->Data : [];

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── AJAX: get permissions for one role ───────────────────────────

    public function getRolePermissions() {

        $this->EndReturnData = new stdClass();
        try {
            $PostData = $this->input->post();
            $RoleUID  = (int)($PostData['RoleUID'] ?? 0);
            if (!$RoleUID) throw new Exception('RoleUID is required.');

            $this->load->model('roles_model');
            $result = $this->roles_model->getRolePermissions($RoleUID);

            $this->EndReturnData->Error   = $result->Error;
            $this->EndReturnData->Message = $result->Message;
            $this->EndReturnData->Data    = $result->Error === FALSE ? $result->Data : [];

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── AJAX: save role (create / update) ───────────────────────────

    public function saveRole() {

        $this->EndReturnData = new stdClass();
        try {
            $PostData = $this->input->post();
            $RoleUID  = (int)($PostData['RoleUID'] ?? 0);
            $Name     = trim($PostData['RoleName'] ?? '');

            if (empty($Name)) throw new Exception('Role name is required.');

            $this->load->model('roles_model');
            $this->load->model('dbwrite_model');
            $JwtData = $this->pageData['JwtData'];

            // Block editing default roles
            if ($RoleUID > 0 && $this->roles_model->isDefaultRole($RoleUID)) {
                throw new Exception('Default roles cannot be renamed.');
            }

            $RoleData = [
                'Name'      => $Name,
                'OrgUID'    => $JwtData->User->OrgUID,
                'BranchUID' => $JwtData->User->BranchUID,
                'IsActive'  => 1,
                'IsDeleted' => 0,
            ];

            if ($RoleUID > 0) {
                $RoleData['UpdatedBy'] = $JwtData->User->UserUID;
                $RoleData['UpdatedOn'] = date('Y-m-d H:i:s');
                $result = $this->dbwrite_model->updateData('UserRole', 'RolesTbl', $RoleData, ['RoleUID' => $RoleUID]);
                $this->EndReturnData->UID = $RoleUID;
            } else {
                $RoleData['CreatedBy'] = $JwtData->User->UserUID;
                $RoleData['CreatedOn'] = date('Y-m-d H:i:s');
                $result = $this->dbwrite_model->insertData('UserRole', 'RolesTbl', $RoleData);
                $this->EndReturnData->UID = $result->ID ?? 0;
                $RoleUID = $this->EndReturnData->UID;
            }

            if ($result->Error) throw new Exception($result->Message);

            // Save permissions if provided
            if (!empty($PostData['MainMenus']) || !empty($PostData['SubMenus'])) {
                $this->load->model('roles_model');
                $this->roles_model->saveRolePermissions($RoleUID, $PostData, $JwtData->User->UserUID);
            }

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = $RoleUID > 0 ? 'Role updated successfully.' : 'Role created successfully.';

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── AJAX: save permissions only (matrix submit) ─────────────────

    public function saveRolePermissions() {

        $this->EndReturnData = new stdClass();
        try {
            $PostData = $this->input->post();
            $RoleUID  = (int)($PostData['RoleUID'] ?? 0);
            if (!$RoleUID) throw new Exception('RoleUID is required.');

            $this->load->model('roles_model');
            $JwtData = $this->pageData['JwtData'];
            $this->roles_model->saveRolePermissions($RoleUID, $PostData, $JwtData->User->UserUID);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Permissions saved successfully.';

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── AJAX: delete role ────────────────────────────────────────────

    public function deleteRole() {

        $this->EndReturnData = new stdClass();
        try {
            $PostData = $this->input->post();
            $RoleUID  = (int)($PostData['RoleUID'] ?? 0);
            if (!$RoleUID) throw new Exception('RoleUID is required.');

            $this->load->model('roles_model');

            // Block deleting any default role
            if ($this->roles_model->isDefaultRole($RoleUID)) {
                throw new Exception('Default roles cannot be deleted.');
            }

            $inUse = $this->roles_model->isRoleInUse($RoleUID);
            if ($inUse) throw new Exception('This role is assigned to one or more users. Reassign them first.');

            $this->load->model('dbwrite_model');
            $JwtData = $this->pageData['JwtData'];
            $result  = $this->dbwrite_model->updateData('UserRole', 'RolesTbl',
                ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $JwtData->User->UserUID, 'UpdatedOn' => date('Y-m-d H:i:s')],
                ['RoleUID' => $RoleUID]
            );

            if ($result->Error) throw new Exception($result->Message);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Role deleted successfully.';

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

}
