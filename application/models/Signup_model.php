<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Signup_model extends CI_Model {

    private object $EndReturnData;
    private object $ReadDb;

    public function __construct() {
        parent::__construct();
        $this->ReadDb = $this->load->database('ReadDB', TRUE);
    }

    /**
     * @return array<int,object>
     */
    public function getTimezones(): array {
        $this->ReadDb->db_debug = FALSE;
        $result = $this->ReadDb->select('TimezoneUID, CountryName, Timezone, GmtOffset')
            ->from('Global.TimezoneTbl')
            ->order_by('CountryName', 'ASC')
            ->order_by('Timezone', 'ASC')
            ->get();
        return ($result && $result->num_rows() > 0) ? $result->result() : [];
    }

    public function isEmailTaken(string $email): bool {
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->from('Users.UserTbl');
        $this->ReadDb->where('EmailAddress', strtolower($email));
        $this->ReadDb->where('IsDeleted', 0);
        return $this->ReadDb->count_all_results() > 0;
    }

    public function isMobileTaken(string $mobile): bool {
        $this->ReadDb->db_debug = FALSE;
        $clean = preg_replace('/\D/', '', $mobile);
        $inOrg = $this->ReadDb->where('MobileNumber', $clean)
            ->where('IsDeleted', 0)
            ->count_all_results('Organisation.OrganisationTbl') > 0;
        if ($inOrg) return true;
        return $this->ReadDb->where('MobileNumber', $clean)
            ->where('IsDeleted', 0)
            ->count_all_results('Users.UserTbl') > 0;
    }

    public function isGSTINTaken(string $gstin): bool {
        $this->ReadDb->db_debug = FALSE;
        return $this->ReadDb->where('GSTIN', strtoupper($gstin))
            ->where('IsDeleted', 0)
            ->where('GSTIN !=', '')
            ->count_all_results('Organisation.OrganisationTbl') > 0;
    }

    public function isUsernameTaken(string $username): bool {
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->from('Users.UserTbl');
        $this->ReadDb->where('UserName', strtolower($username));
        $this->ReadDb->where('IsDeleted', 0);
        return $this->ReadDb->count_all_results() > 0;
    }

    /**
     * @param array<string,string> $formData
     */
    public function registerOrganisation(array $formData): object {

        $this->EndReturnData = new stdClass();
        $this->load->model('dbwrite_model');

        try {

            $this->dbwrite_model->startTransaction();
            $WriteDb = $this->dbwrite_model->getWriteDb();
            $now     = date('Y-m-d H:i:s');

            // 1. Unique identifiers
            $shortCode = strtoupper(trim($formData['ShortCode'] ?? ''));
            if (empty($shortCode) || !preg_match('/^[A-Z]{3}$/', $shortCode)) {
                $shortCode = $this->_generateShortCode(trim($formData['OrgName']));
            }
            $orgToken = $this->_generateOrgToken();
            $orgName  = trim($formData['OrgName']);

            /* GSTIN: store NULL when not provided; never store empty string */
            $gstin = !empty(trim($formData['GSTIN'] ?? '')) ? strtoupper(trim($formData['GSTIN'])) : null;

            // 2. OrganisationTbl
            $orgResult = $this->dbwrite_model->insertData('Organisation', 'OrganisationTbl', [
                'Name'         => $orgName,
                'BrandName'    => $orgName,
                'ShortCode'    => $shortCode,
                'OrgToken'     => $orgToken,
                'CountryCode'  => '+91',
                'CountryISO2'  => 'IN',
                'MobileNumber' => trim($formData['OrgMobile']),
                'EmailAddress' => strtolower(trim($formData['OrgEmail'])),
                'GSTIN'        => $gstin,
                'StateCode'    => trim($formData['StateCode']),
                'StateName'    => trim($formData['StateName']),
                'TimezoneUID'  => (int) ($formData['TimezoneUID'] ?? 181),
                'IsActive'     => 1,
                'IsDeleted'    => 0,
                'CreatedBy'    => 0,
                'UpdatedBy'    => 0,
                'CreatedOn'    => $now,
            ]);
            if ($orgResult->Error) throw new Exception('Organisation insert failed: ' . $orgResult->Message);
            $orgUID = (int) $orgResult->ID;

            /* Branch name = org name; branch code = short code (not shortCode+HO) */
            $adminName    = trim($formData['AdminFirstName']) . ' ' . trim($formData['AdminLastName'] ?? '');
            $branchResult = $this->dbwrite_model->insertData('Organisation', 'BranchesTbl', [
                'OrgUID'          => $orgUID,
                'Name'            => $orgName,
                'BranchCode'      => $shortCode,
                'ContactPerson'   => trim($adminName),
                'MobileNumber'    => trim($formData['OrgMobile']),
                'CountryCode'     => '+91',
                'CountryISO2'     => 'IN',
                'EmailAddress'    => strtolower(trim($formData['OrgEmail'])),
                'GSTIN'           => $gstin,
                'StateText'       => trim($formData['StateName']),
                'IsHeadOffice'    => 1,
                'IsWarehouse'     => 1,
                'IsSalesPoint'    => 1,
                'IsDispatchPoint' => 1,
                'IsServiceCenter' => 0,
                'IsActive'        => 1,
                'IsDeleted'       => 0,
                'CreatedBy'       => 0,
                'UpdatedBy'       => 0,
                'CreatedOn'       => $now,
            ]);
            if ($branchResult->Error) throw new Exception('Branch insert failed: ' . $branchResult->Message);
            $branchUID = (int) $branchResult->ID;

            // 4. OrgSettingsTbl — defaults
            $settingsResult = $this->dbwrite_model->insertData('Settings', 'OrgSettingsTbl', [
                'OrgSettingsUID'      => $orgUID,
                'OrgUID'              => $orgUID,
                'CurrenySymbol'       => '₹',
                'FYStartMonth'        => 4,
                'RowLimit'            => 10,
                'QtyMaxLength'        => 2,

                'SerialNoDisplay'     => 0,
                'EnableStorage'       => 0,
                'MandatoryStorage'    => 0,
                'MaxShippingAddr'     => 3,
                'FormDateFormat'      => 'd-m-Y',
                'ListDateFormat'      => 'd-m-Y',
                'PrintDateFormat'     => 'd-m-Y',
                'FormDateTimeFormat'  => 'd-m-Y h:i A',
                'ListDateTimeFormat'  => 'd-m-Y h:i A',
                'PrintDateTimeFormat' => 'd-m-Y h:i A',
                'ShowStats'           => 0,
                'StatsDefaultOpen'    => 0,
                'EnableAIAssistant'   => 'No',
                'TwoStepLogin'        => 1,
                'EmpCodePrefix'       => $shortCode,
                'EmpCodeSeparator'    => '-',
                'EmpCodeDigits'       => 4,
            ]);
            if ($settingsResult->Error) throw new Exception('Settings insert failed: ' . $settingsResult->Message);

            // 5. RolesTbl — org-specific Admin role created per org
            $roleResult = $this->dbwrite_model->insertData('UserRole', 'RolesTbl', [
                'Name'      => 'Super Admin',
                'OrgUID'    => $orgUID,
                'BranchUID' => $branchUID,
                'IsDefault' => 1,
                'IsGlobal'  => 0,
                'IsActive'  => 1,
                'IsDeleted' => 0,
                'CreatedBy' => 0,
                'UpdatedBy' => 0,
                'CreatedOn' => $now,
            ]);
            if ($roleResult->Error) throw new Exception('Role insert failed: ' . $roleResult->Message);
            $roleUID = (int) $roleResult->ID;

            /* User is inserted with CreatedBy=0 temporarily; updated to self below */
            $userCode   = $shortCode . '-' . str_pad(1, 4, '0', STR_PAD_LEFT);
            $userResult = $this->dbwrite_model->insertData('Users', 'UserTbl', [
                'EmployeeCode'   => $userCode,
                'FirstName'      => trim($formData['AdminFirstName']),
                'LastName'       => !empty(trim($formData['AdminLastName'] ?? '')) ? trim($formData['AdminLastName']) : null,
                'UserName'       => strtolower(trim($formData['AdminUsername'])),
                'EmailAddress'   => strtolower(trim($formData['OrgEmail'])),
                'Password'       => password_hash($formData['AdminPassword'], PASSWORD_BCRYPT),
                'OrgUID'         => $orgUID,
                'BranchUID'      => $branchUID,
                'RoleUID'        => $roleUID,
                'CountryCode'    => '+91',
                'CountryISO2'    => 'IN',
                'MobileNumber'   => trim($formData['OrgMobile']),
                'HasLoginAccess' => 1,
                'IsPasswordSet'  => 1,
                'IsActive'       => 1,
                'IsDeleted'      => 0,
                'UILanguage'     => 'en',
                'CreatedBy'      => 0,
                'UpdatedBy'      => 0,
                'CreatedOn'      => $now,
            ]);
            if ($userResult->Error) throw new Exception('User insert failed: ' . $userResult->Message);
            $userUID = (int) $userResult->ID;

            /* Back-fill CreatedBy/UpdatedBy with the real user UID now that it exists */
            $WriteDb->db_debug = FALSE;
            $WriteDb->where('OrgUID', $orgUID)->update('Organisation.OrganisationTbl', ['CreatedBy' => $userUID, 'UpdatedBy' => $userUID]);
            $WriteDb->where('BranchUID', $branchUID)->update('Organisation.BranchesTbl', ['CreatedBy' => $userUID, 'UpdatedBy' => $userUID]);
            $WriteDb->where('RoleUID', $roleUID)->update('UserRole.RolesTbl',            ['CreatedBy' => $userUID, 'UpdatedBy' => $userUID]);
            $WriteDb->where('UserUID', $userUID)->update('Users.UserTbl',                ['CreatedBy' => $userUID, 'UpdatedBy' => $userUID]);

            // 6b. Additional default roles — no permissions seeded; admin configures via Roles settings
            $additionalRoles = ['Admin', 'Sales Manager', 'Sales Executive', 'Accountant'];
            foreach ($additionalRoles as $roleName) {
                $this->dbwrite_model->insertData('UserRole', 'RolesTbl', [
                    'Name'      => $roleName,
                    'OrgUID'    => $orgUID,
                    'BranchUID' => $branchUID,
                    'IsDefault' => 1,
                    'IsGlobal'  => 0,
                    'IsActive'  => 1,
                    'IsDeleted' => 0,
                    'CreatedBy' => $userUID,
                    'UpdatedBy' => $userUID,
                    'CreatedOn' => $now,
                ]);
            }

            // 7. UserBranchAccessTbl
            $this->dbwrite_model->insertData('Users', 'UserBranchAccessTbl', [
                'UserUID'   => $userUID,
                'OrgUID'    => $orgUID,
                'BranchUID' => $branchUID,
                'IsDefault' => 1,
                'IsActive'  => 1,
            ]);

            // 8. OrgSubscriptionTbl — auto-assign Free Trial (30 days)
            $this->_assignTrialSubscription($WriteDb, $orgUID, $now);

            // 9. Copy menus & permissions from template org
            $this->_copyMenusFromTemplate($WriteDb, $orgUID, $roleUID, $userUID);

            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error   = false;
            $this->EndReturnData->Message = 'Success';
            $this->EndReturnData->OrgUID  = $orgUID;
            return $this->EndReturnData;

        } catch (Exception $e) {
            try { $this->dbwrite_model->rollbackTransaction(); } catch (Exception $_) {}
            notifyError('Signup_model::registerOrganisation', $e);
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
            return $this->EndReturnData;
        }

    }

    private function _assignTrialSubscription(object $WriteDb, int $orgUID, string $now): void {
        $this->ReadDb->db_debug = FALSE;
        $planRow = $this->ReadDb->select('PlanUID, PlanCode, DurationDays')
            ->from('Organisation.SubscriptionPlansTbl')
            ->where('PlanCode', 'TRIAL')
            ->where('IsActive', 1)
            ->limit(1)
            ->get();

        if (!$planRow || $planRow->num_rows() === 0) return;

        $plan      = $planRow->row();
        $duration  = max(1, (int) $plan->DurationDays);
        $startDate = date('Y-m-d H:i:s', strtotime($now));
        $endDate   = date('Y-m-d', strtotime($now . ' +' . $duration . ' days')) . ' 23:59:59';

        $WriteDb->db_debug = FALSE;
        $WriteDb->insert('Organisation.OrgSubscriptionTbl', [
            'OrgUID'          => $orgUID,
            'PlanUID'         => (int) $plan->PlanUID,
            'PlanCode'        => $plan->PlanCode,
            'Status'          => 'Trial',
            'StartDate'       => $startDate,
            'EndDate'         => $endDate,
            'GracePeriodDays' => 7,
            'AutoRenew'       => 0,
            'PaidAmount'      => 0.00,
            'PaymentRef'      => '',
        ]);
    }

    private function _generateShortCode(string $orgName): string {
        $clean   = strtoupper(preg_replace('/[^A-Za-z]/u', '', $orgName));
        $base    = str_pad(substr($clean, 0, 3), 3, 'X');
        $attempt = $base;
        $suffix  = 1;
        while ($this->ReadDb->where('ShortCode', $attempt)->count_all_results('Organisation.OrganisationTbl') > 0) {
            $sfx     = (string) $suffix;
            $attempt = substr($base, 0, 3 - strlen($sfx)) . $sfx;
            $suffix++;
        }
        return $attempt;
    }

    private function _generateOrgToken(): string {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $token = '';
            for ($i = 0; $i < 8; $i++) {
                $token .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $exists = $this->ReadDb->where('OrgToken', $token)->count_all_results('Organisation.OrganisationTbl');
        } while ($exists > 0);
        return $token;
    }

    private function _copyMenusFromTemplate(object $WriteDb, int $orgUID, int $roleUID, int $userUID): void {

        // Use the lowest-OrgUID org as the menu template (the seed org)
        $this->ReadDb->db_debug = FALSE;
        $tplRow = $this->ReadDb->select('OrgUID')
            ->from('Organisation.OrganisationTbl')
            ->where('IsDeleted', 0)
            ->where('OrgUID !=', $orgUID)
            ->order_by('OrgUID', 'ASC')
            ->limit(1)
            ->get();

        if (!$tplRow || $tplRow->num_rows() === 0) return;
        $tplOrgUID = (int) $tplRow->row()->OrgUID;

        // ── Main Menus ─────────────────────────────────────────────────────────
        $mainMenuRows = $this->ReadDb->select('*')
            ->from('Modules.MainMenusTbl')
            ->where('OrgUID', $tplOrgUID)
            ->where('IsDeleted', 0)
            ->order_by('Sorting', 'ASC')
            ->get()->result();

        $mainMenuMap = [];
        foreach ($mainMenuRows as $mm) {
            $WriteDb->db_debug = FALSE;
            $WriteDb->insert('Modules.MainMenusTbl', [
                'OrgUID'       => $orgUID,
                'Name'         => $mm->Name,
                'Icon'         => $mm->Icon ?? '',
                'IsDirectLink' => $mm->IsDirectLink ?? 0,
                'DirectUrl'    => $mm->DirectUrl ?? '',
                'Sorting'      => $mm->Sorting ?? 0,
                'IsActive'     => (int)(bool) $mm->IsActive,
                'IsDeleted'    => 0,
                'CreatedBy'    => $userUID,
                'UpdatedBy'    => $userUID,
            ]);
            $dbErr = $WriteDb->error();
            if (!empty($dbErr['code'])) {
                throw new Exception('MainMenu insert failed [' . $dbErr['code'] . ']: ' . $dbErr['message'] . ' (Name=' . $mm->Name . ')');
            }
            $mainMenuMap[(int) $mm->MainMenuUID] = (int) $WriteDb->insert_id();
        }

        // ── Modules — global catalog with UNIQUE(Name); skip copy, use original UIDs ──
        // ModuleTbl.Name is globally unique across all orgs so inserts would fail.
        // SubMenusTbl.ModuleUID FK is satisfied as long as the original record exists.

        // ── RoleMainMenusTbl — inserted before SubMenus so RoleMainMenuUID is available ──
        // oldMainMenuUID → RoleMainMenuUID
        $roleMainMenuMap = [];
        $sort = 1;
        foreach ($mainMenuMap as $newMMUID) {
            $WriteDb->db_debug = FALSE;
            $WriteDb->insert('UserRole.RoleMainMenusTbl', [
                'RoleUID'     => $roleUID,
                'MainMenuUID' => $newMMUID,
                'Sorting'     => $sort++,
                'CanView'     => 1,
                'CanCreate'   => 1,
                'CanEdit'     => 1,
                'CanDelete'   => 1,
                'IsActive'    => 1,
                'IsDeleted'   => 0,
                'CreatedBy'   => $userUID,
                'UpdatedBy'   => $userUID,
            ]);
            $dbErr = $WriteDb->error();
            if (!empty($dbErr['code'])) {
                throw new Exception('RoleMainMenu insert failed [' . $dbErr['code'] . ']: ' . $dbErr['message'] . ' (MainMenuUID=' . $newMMUID . ')');
            }
            $roleMainMenuMap[$newMMUID] = (int) $WriteDb->insert_id();
        }

        // ── Sub Menus — parents first ─────────────────────────────────────────
        $subMenuRows = $this->ReadDb->select('*')
            ->from('Modules.SubMenusTbl')
            ->where('OrgUID', $tplOrgUID)
            ->where('IsDeleted', 0)
            ->order_by('ParentSubMenuUID', 'ASC')
            ->order_by('Sorting', 'ASC')
            ->get()->result();

        $subMenuMap       = [];
        $subMenuRoleMMMap = []; // newSubMenuUID => RoleMainMenuUID

        foreach ($subMenuRows as $sm) {
            $oldMain   = (int) $sm->MainMenuUID;
            $oldParent = ($sm->ParentSubMenuUID !== null && $sm->ParentSubMenuUID !== '') ? (int) $sm->ParentSubMenuUID : 0;
            $origMod   = ($sm->ModuleUID !== null && $sm->ModuleUID !== '') ? (int) $sm->ModuleUID : null;

            $newMMUID     = $mainMenuMap[$oldMain] ?? 0;
            $newRoleMMUID = $roleMainMenuMap[$newMMUID] ?? 0;

            $WriteDb->db_debug = FALSE;
            $WriteDb->insert('Modules.SubMenusTbl', [
                'OrgUID'           => $orgUID,
                'MainMenuUID'      => $newMMUID,
                'Name'             => $sm->Name,
                'UrlPath'          => $sm->UrlPath ?? '',
                'ParentSubMenuUID' => ($oldParent && isset($subMenuMap[$oldParent])) ? $subMenuMap[$oldParent] : null,
                'IsParent'         => $sm->IsParent ?? 0,
                'Icon'             => $sm->Icon ?? '',
                'ModuleUID'        => $origMod,
                'Sorting'          => $sm->Sorting ?? 0,
                'IsActive'         => (int)(bool) $sm->IsActive,
                'IsDeleted'        => 0,
                'CreatedBy'        => $userUID,
                'UpdatedBy'        => $userUID,
            ]);
            $dbErr = $WriteDb->error();
            if (!empty($dbErr['code'])) {
                throw new Exception('SubMenu insert failed [' . $dbErr['code'] . ']: ' . $dbErr['message'] . ' (Name=' . $sm->Name . ', ModuleUID=' . ($origMod ?? 'null') . ', MainMenuUID=' . $newMMUID . ')');
            }
            $newSubUID                         = (int) $WriteDb->insert_id();
            $subMenuMap[(int) $sm->SubMenuUID] = $newSubUID;
            $subMenuRoleMMMap[$newSubUID]       = $newRoleMMUID;
        }

        // ── RoleSubMenusTbl — admin gets full access ───────────────────────────
        $sort = 1;
        foreach ($subMenuMap as $newSubUID) {
            $roleMMUID = $subMenuRoleMMMap[$newSubUID] ?? 0;
            $WriteDb->db_debug = FALSE;
            $WriteDb->insert('UserRole.RoleSubMenusTbl', [
                'RoleUID'         => $roleUID,
                'RoleMainMenuUID' => $roleMMUID,
                'SubMenuUID'      => $newSubUID,
                'Sorting'         => $sort++,
                'CanView'         => 1,
                'CanCreate'       => 1,
                'CanEdit'         => 1,
                'CanDelete'       => 1,
                'IsActive'        => 1,
                'IsDeleted'       => 0,
            ]);
            $dbErr = $WriteDb->error();
            if (!empty($dbErr['code'])) {
                throw new Exception('RoleSubMenu insert failed [' . $dbErr['code'] . ']: ' . $dbErr['message'] . ' (SubMenuUID=' . $newSubUID . ', RoleMainMenuUID=' . $roleMMUID . ')');
            }
        }

    }

}
