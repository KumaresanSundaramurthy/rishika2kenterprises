<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Roles_model extends CI_Model {

    private $EndReturnData;
    private $ReadDb;

    function __construct() {
        parent::__construct();
        $this->ReadDb = $this->load->database('ReadDB', TRUE);
    }

    // ── List roles for org ───────────────────────────────────────────

    public function getRolesList($OrgUID) {

        $this->EndReturnData = new stdClass();
        try {

            $this->ReadDb->select('R.RoleUID, R.Name, R.IsDefault, R.IsActive, R.CreatedOn,
                (SELECT COUNT(*) FROM Users.UserTbl U WHERE U.RoleUID = R.RoleUID AND U.IsDeleted = 0) AS UserCount');
            $this->ReadDb->from('UserRole.RolesTbl AS R');
            $this->ReadDb->where('R.OrgUID', $OrgUID);
            $this->ReadDb->where('R.IsDeleted', 0);
            $this->ReadDb->order_by('R.RoleUID', 'ASC');
            $query = $this->ReadDb->get();

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Success';
            $this->EndReturnData->Data    = $query->result();

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        return $this->EndReturnData;
    }

    // ── All menus for permission matrix ─────────────────────────────

    public function getAllMenusForMatrix($OrgUID) {

        $this->EndReturnData = new stdClass();
        try {

            // Main menus
            $this->ReadDb->select('MM.MainMenuUID, MM.Name, MM.Icons, MM.Sorting');
            $this->ReadDb->from('Modules.MainMenusTbl AS MM');
            $this->ReadDb->where('MM.OrgUID', $OrgUID);
            $this->ReadDb->where('MM.IsDeleted', 0);
            $this->ReadDb->where('MM.IsActive', 1);
            $this->ReadDb->order_by('MM.Sorting', 'ASC');
            $mainQuery = $this->ReadDb->get();
            $mainMenus = $mainQuery->result();

            // Sub menus
            $this->ReadDb->select('SM.SubMenuUID, SM.MainMenuUID, SM.ParentSubMenuUID, SM.Name, SM.ControllerName, SM.Icons, SM.Sorting');
            $this->ReadDb->from('Modules.SubMenusTbl AS SM');
            $this->ReadDb->where('SM.OrgUID', $OrgUID);
            $this->ReadDb->where('SM.IsDeleted', 0);
            $this->ReadDb->where('SM.IsActive', 1);
            $this->ReadDb->order_by('SM.MainMenuUID', 'ASC');
            $this->ReadDb->order_by('SM.Sorting', 'ASC');
            $subQuery = $this->ReadDb->get();
            $subMenus = $subQuery->result();

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Success';
            $this->EndReturnData->Data    = ['main' => $mainMenus, 'sub' => $subMenus];

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        return $this->EndReturnData;
    }

    // ── Get permissions for one role ─────────────────────────────────

    public function getRolePermissions($RoleUID) {

        $this->EndReturnData = new stdClass();
        try {

            // Main menu permissions
            $this->ReadDb->select('RMM.RoleMainMenuUID, RMM.MainMenuUID, RMM.CanView, RMM.CanCreate, RMM.CanEdit, RMM.CanDelete');
            $this->ReadDb->from('UserRole.RoleMainMenusTbl AS RMM');
            $this->ReadDb->where('RMM.RoleUID', $RoleUID);
            $this->ReadDb->where('RMM.IsDeleted', 0);
            $mmQuery  = $this->ReadDb->get();
            $mainPerms = $mmQuery->result();

            // Sub menu permissions
            $this->ReadDb->select('RSM.RoleSubMenuUID, RSM.SubMenuUID, RSM.CanView, RSM.CanCreate, RSM.CanEdit, RSM.CanDelete');
            $this->ReadDb->from('UserRole.RoleSubMenusTbl AS RSM');
            $this->ReadDb->where('RSM.RoleUID', $RoleUID);
            $this->ReadDb->where('RSM.IsDeleted', 0);
            $smQuery  = $this->ReadDb->get();
            $subPerms = $smQuery->result();

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Success';
            $this->EndReturnData->Data    = ['main' => $mainPerms, 'sub' => $subPerms];

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        return $this->EndReturnData;
    }

    // ── Upsert role permissions ──────────────────────────────────────

    public function saveRolePermissions($RoleUID, $PostData, $UserUID) {

        $this->load->model('dbwrite_model');
        $WriteDb = $this->load->database('WriteDB', TRUE);
        $now = date('Y-m-d H:i:s');

        // ── Main menus ──────────────────────────────────────────
        // PostData['MainMenus'] is an array of:
        // { MainMenuUID, CanView, CanCreate, CanEdit, CanDelete, Sorting }
        $mainMenus = $PostData['MainMenus'] ?? [];
        if (!empty($mainMenus) && is_string($mainMenus)) {
            $mainMenus = json_decode($mainMenus, true) ?? [];
        }

        $mainMenuUIDs = [];
        foreach ($mainMenus as $mm) {
            $MainMenuUID = (int)$mm['MainMenuUID'];
            if (!$MainMenuUID) continue;
            $mainMenuUIDs[] = $MainMenuUID;

            // Check if row exists
            $this->ReadDb->select('RoleMainMenuUID');
            $this->ReadDb->from('UserRole.RoleMainMenusTbl');
            $this->ReadDb->where('RoleUID', $RoleUID);
            $this->ReadDb->where('MainMenuUID', $MainMenuUID);
            $this->ReadDb->limit(1);
            $existing = $this->ReadDb->get()->row();

            $row = [
                'RoleUID'    => $RoleUID,
                'MainMenuUID'=> $MainMenuUID,
                'CanView'    => (int)($mm['CanView']   ?? 0),
                'CanCreate'  => (int)($mm['CanCreate'] ?? 0),
                'CanEdit'    => (int)($mm['CanEdit']   ?? 0),
                'CanDelete'  => (int)($mm['CanDelete'] ?? 0),
                'Sorting'    => (int)($mm['Sorting']   ?? 0),
                'IsActive'   => 1,
                'IsDeleted'  => 0,
                'UpdatedBy'  => $UserUID,
                'UpdatedOn'  => $now,
            ];

            if ($existing) {
                $WriteDb->where('RoleUID', $RoleUID)->where('MainMenuUID', $MainMenuUID)
                    ->update('UserRole.RoleMainMenusTbl', $row);
            } else {
                $row['CreatedBy'] = $UserUID;
                $row['CreatedOn'] = $now;
                $WriteDb->insert('UserRole.RoleMainMenusTbl', $row);
            }
        }

        // ── Sub menus ───────────────────────────────────────────
        $subMenus = $PostData['SubMenus'] ?? [];
        if (!empty($subMenus) && is_string($subMenus)) {
            $subMenus = json_decode($subMenus, true) ?? [];
        }

        foreach ($subMenus as $sm) {
            $SubMenuUID = (int)$sm['SubMenuUID'];
            if (!$SubMenuUID) continue;

            // Resolve RoleMainMenuUID for this sub menu's parent main menu
            $this->ReadDb->select('RoleMainMenuUID');
            $this->ReadDb->from('UserRole.RoleMainMenusTbl AS RMM');
            $this->ReadDb->join('Modules.SubMenusTbl AS SM', 'SM.MainMenuUID = RMM.MainMenuUID', 'inner');
            $this->ReadDb->where('RMM.RoleUID', $RoleUID);
            $this->ReadDb->where('SM.SubMenuUID', $SubMenuUID);
            $this->ReadDb->limit(1);
            $rmmRow = $this->ReadDb->get()->row();
            $RoleMainMenuUID = $rmmRow ? $rmmRow->RoleMainMenuUID : 0;

            // Check if row exists
            $this->ReadDb->select('RoleSubMenuUID');
            $this->ReadDb->from('UserRole.RoleSubMenusTbl');
            $this->ReadDb->where('RoleUID', $RoleUID);
            $this->ReadDb->where('SubMenuUID', $SubMenuUID);
            $this->ReadDb->limit(1);
            $existing = $this->ReadDb->get()->row();

            $row = [
                'RoleUID'        => $RoleUID,
                'RoleMainMenuUID'=> $RoleMainMenuUID,
                'SubMenuUID'     => $SubMenuUID,
                'CanView'        => (int)($sm['CanView']   ?? 0),
                'CanCreate'      => (int)($sm['CanCreate'] ?? 0),
                'CanEdit'        => (int)($sm['CanEdit']   ?? 0),
                'CanDelete'      => (int)($sm['CanDelete'] ?? 0),
                'Sorting'        => (int)($sm['Sorting']   ?? 0),
                'IsActive'       => 1,
                'IsDeleted'      => 0,
                'UpdatedBy'      => $UserUID,
                'UpdatedOn'      => $now,
            ];

            if ($existing) {
                $WriteDb->where('RoleUID', $RoleUID)->where('SubMenuUID', $SubMenuUID)
                    ->update('UserRole.RoleSubMenusTbl', $row);
            } else {
                $row['CreatedBy'] = $UserUID;
                $row['CreatedOn'] = $now;
                $WriteDb->insert('UserRole.RoleSubMenusTbl', $row);
            }
        }

    }

    // ── Check if role is a default (system) role ─────────────────────

    public function isDefaultRole($RoleUID) {

        $this->ReadDb->select('IsDefault');
        $this->ReadDb->from('UserRole.RolesTbl');
        $this->ReadDb->where('RoleUID', $RoleUID);
        $this->ReadDb->limit(1);
        $row = $this->ReadDb->get()->row();
        return $row && (int)$row->IsDefault === 1;

    }

    // ── Check if role is assigned to any user ────────────────────────

    public function isRoleInUse($RoleUID) {

        $this->ReadDb->from('Users.UserTbl');
        $this->ReadDb->where('RoleUID', $RoleUID);
        $this->ReadDb->where('IsDeleted', 0);
        $this->ReadDb->limit(1);
        $query = $this->ReadDb->get();
        return $query->num_rows() > 0;

    }

}
