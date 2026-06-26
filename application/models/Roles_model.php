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
            $this->ReadDb->select('MM.MainMenuUID, MM.Name, MM.Icon, MM.Sorting, COALESCE(MM.IsDirectLink, 0) AS IsDirectLink');
            $this->ReadDb->from('Modules.MainMenusTbl AS MM');
            $this->ReadDb->where('MM.OrgUID', $OrgUID);
            $this->ReadDb->where('MM.IsDeleted', 0);
            $this->ReadDb->where('MM.IsActive', 1);
            $this->ReadDb->order_by('MM.Sorting', 'ASC');
            $mainQuery = $this->ReadDb->get();
            $mainMenus = $mainQuery->result();

            // Sub menus
            $this->ReadDb->select('SM.SubMenuUID, SM.MainMenuUID, SM.ParentSubMenuUID, SM.IsParent, SM.Name, SM.UrlPath as ControllerName, SM.Icon, SM.Sorting');
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

        $WriteDb = $this->load->database('WriteDB', TRUE);
        $now     = date('Y-m-d H:i:s');

        // ── Main menus ──────────────────────────────────────────────────────────
        $mainMenus = $PostData['MainMenus'] ?? [];
        if (is_string($mainMenus)) $mainMenus = json_decode($mainMenus, true) ?? [];

        if (!empty($mainMenus)) {
            // One bulk upsert — replaces N SELECT + N INSERT/UPDATE queries
            $mmVals  = [];
            $mmBinds = [];
            foreach ($mainMenus as $mm) {
                $MainMenuUID = (int)($mm['MainMenuUID'] ?? 0);
                if (!$MainMenuUID) continue;
                $mmVals[]  = '(?,?,?,?,?,?,?,1,0,?,?,?,?)';
                $mmBinds[] = $RoleUID;
                $mmBinds[] = $MainMenuUID;
                $mmBinds[] = (int)($mm['CanView']   ?? 0);
                $mmBinds[] = (int)($mm['CanCreate'] ?? 0);
                $mmBinds[] = (int)($mm['CanEdit']   ?? 0);
                $mmBinds[] = (int)($mm['CanDelete'] ?? 0);
                $mmBinds[] = (int)($mm['Sorting']   ?? 0);
                $mmBinds[] = $UserUID; // UpdatedBy
                $mmBinds[] = $now;     // UpdatedOn
                $mmBinds[] = $UserUID; // CreatedBy
                $mmBinds[] = $now;     // CreatedOn
            }
            if (!empty($mmVals)) {
                $WriteDb->query(
                    "INSERT INTO UserRole.RoleMainMenusTbl
                        (RoleUID, MainMenuUID, CanView, CanCreate, CanEdit, CanDelete, Sorting, IsActive, IsDeleted, UpdatedBy, UpdatedOn, CreatedBy, CreatedOn)
                     VALUES " . implode(',', $mmVals) . "
                     ON DUPLICATE KEY UPDATE
                        CanView   = VALUES(CanView),   CanCreate = VALUES(CanCreate),
                        CanEdit   = VALUES(CanEdit),   CanDelete = VALUES(CanDelete),
                        Sorting   = VALUES(Sorting),   IsActive  = 1, IsDeleted = 0,
                        UpdatedBy = VALUES(UpdatedBy), UpdatedOn = VALUES(UpdatedOn)",
                    $mmBinds
                );
            }
        }

        // ── Sub menus ───────────────────────────────────────────────────────────
        $subMenus = $PostData['SubMenus'] ?? [];
        if (is_string($subMenus)) $subMenus = json_decode($subMenus, true) ?? [];

        if (!empty($subMenus)) {
            // Batch-resolve SubMenuUID → RoleMainMenuUID in ONE query
            $subUIDs = array_values(array_filter(array_map(
                fn($sm) => (int)($sm['SubMenuUID'] ?? 0), $subMenus
            )));

            $in = implode(',', array_fill(0, count($subUIDs), '?'));
            $rmmRows = $this->ReadDb->query(
                "SELECT SM.SubMenuUID, RMM.RoleMainMenuUID
                   FROM UserRole.RoleMainMenusTbl AS RMM
                   JOIN Modules.SubMenusTbl AS SM ON SM.MainMenuUID = RMM.MainMenuUID
                  WHERE RMM.RoleUID = ? AND SM.SubMenuUID IN ($in)",
                array_merge([$RoleUID], $subUIDs)
            )->result();

            $rmmMap = []; // SubMenuUID => RoleMainMenuUID
            foreach ($rmmRows as $r) $rmmMap[(int)$r->SubMenuUID] = (int)$r->RoleMainMenuUID;

            // One bulk upsert for all sub menus
            $smVals  = [];
            $smBinds = [];
            foreach ($subMenus as $sm) {
                $SubMenuUID = (int)($sm['SubMenuUID'] ?? 0);
                if (!$SubMenuUID) continue;
                $smVals[]  = '(?,?,?,?,?,?,?,?,1,0,?,?,?,?)';
                $smBinds[] = $RoleUID;
                $smBinds[] = $rmmMap[$SubMenuUID] ?? 0; // RoleMainMenuUID
                $smBinds[] = $SubMenuUID;
                $smBinds[] = (int)($sm['CanView']   ?? 0);
                $smBinds[] = (int)($sm['CanCreate'] ?? 0);
                $smBinds[] = (int)($sm['CanEdit']   ?? 0);
                $smBinds[] = (int)($sm['CanDelete'] ?? 0);
                $smBinds[] = (int)($sm['Sorting']   ?? 0);
                $smBinds[] = $UserUID; // UpdatedBy
                $smBinds[] = $now;     // UpdatedOn
                $smBinds[] = $UserUID; // CreatedBy
                $smBinds[] = $now;     // CreatedOn
            }
            if (!empty($smVals)) {
                $WriteDb->query(
                    "INSERT INTO UserRole.RoleSubMenusTbl
                        (RoleUID, RoleMainMenuUID, SubMenuUID, CanView, CanCreate, CanEdit, CanDelete, Sorting, IsActive, IsDeleted, UpdatedBy, UpdatedOn, CreatedBy, CreatedOn)
                     VALUES " . implode(',', $smVals) . "
                     ON DUPLICATE KEY UPDATE
                        RoleMainMenuUID = VALUES(RoleMainMenuUID),
                        CanView   = VALUES(CanView),   CanCreate = VALUES(CanCreate),
                        CanEdit   = VALUES(CanEdit),   CanDelete = VALUES(CanDelete),
                        Sorting   = VALUES(Sorting),   IsActive  = 1, IsDeleted = 0,
                        UpdatedBy = VALUES(UpdatedBy), UpdatedOn = VALUES(UpdatedOn)",
                    $smBinds
                );
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
