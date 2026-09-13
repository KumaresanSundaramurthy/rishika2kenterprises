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
                'Password'          => ($pwHash = password_hash($formData['AdminPassword'], PASSWORD_BCRYPT)),
                'PasswordChangedOn' => $now,
                'OrgUID'            => $orgUID,
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
            ]);
            if ($userResult->Error) throw new Exception('User insert failed: ' . $userResult->Message);
            $userUID = (int) $userResult->ID;

            /* Seed password history so the signup password counts toward the "last 3" rule */
            $WriteDb->insert('Users.PasswordHistoryTbl', [
                'UserUID'  => $userUID,
                'Password' => $pwHash,
            ]);

            /* Back-fill CreatedBy/UpdatedBy with the real user UID now that it exists */
            $WriteDb->db_debug = FALSE;
            $WriteDb->where('OrgUID', $orgUID)->update('Organisation.OrganisationTbl', ['CreatedBy' => $userUID, 'UpdatedBy' => $userUID]);
            $WriteDb->where('BranchUID', $branchUID)->update('Organisation.BranchesTbl', ['CreatedBy' => $userUID, 'UpdatedBy' => $userUID]);
            $WriteDb->where('RoleUID', $roleUID)->update('UserRole.RolesTbl',            ['CreatedBy' => $userUID, 'UpdatedBy' => $userUID]);
            $WriteDb->where('UserUID', $userUID)->update('Users.UserTbl',                ['CreatedBy' => $userUID, 'UpdatedBy' => $userUID]);

            // 6b. Additional default roles — no permissions seeded; admin configures via Roles settings
            $additionalRoles = ['Admin', 'Sales Manager', 'Sales Executive', 'Accountant'];
            foreach ($additionalRoles as $roleName) {
                $r = $this->dbwrite_model->insertData('UserRole', 'RolesTbl', [
                    'Name'      => $roleName,
                    'OrgUID'    => $orgUID,
                    'BranchUID' => $branchUID,
                    'IsDefault' => 1,
                    'IsGlobal'  => 0,
                    'IsActive'  => 1,
                    'IsDeleted' => 0,
                    'CreatedBy' => $userUID,
                    'UpdatedBy' => $userUID,
                ]);
                if ($r->Error) throw new Exception('Role insert failed (' . $roleName . '): ' . $r->Message);
            }

            // 7. UserBranchAccessTbl
            $r = $this->dbwrite_model->insertData('Users', 'UserBranchAccessTbl', [
                'UserUID'   => $userUID,
                'OrgUID'    => $orgUID,
                'BranchUID' => $branchUID,
                'IsDefault' => 1,
                'IsActive'  => 1,
                'CreatedBy' => $userUID,
            ]);
            if ($r->Error) throw new Exception('UserBranchAccess insert failed: ' . $r->Message);

            // 8. OrgSubscriptionTbl — auto-assign Free Trial (30 days)
            $this->_assignTrialSubscription($orgUID, $now);

            // 9. Copy menus & permissions from template org
            $this->_copyMenusFromTemplate($orgUID, $roleUID, $userUID);

            $this->dbwrite_model->commitTransaction();

            // Send verification email after commit (non-critical — failure does not roll back)
            $this->sendVerificationEmail(
                $orgUID,
                trim($formData['AdminFirstName']),
                strtolower(trim($formData['OrgEmail']))
            );

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

    private function _assignTrialSubscription(int $orgUID, string $now): void {

        $trialDays = 30;
        $startDate = date('Y-m-d H:i:s', strtotime($now));
        $endDate   = date('Y-m-d H:i:s', strtotime($now . ' +' . $trialDays . ' days'));

        /* Try to find a SectorPlan for this org's sector — may not exist yet if no plans are configured */
        $this->ReadDb->db_debug = FALSE;
        $sectorPlanRow = $this->ReadDb->select('SP.SectorPlanUID, SP.TrialDays')
            ->from('Organisation.OrganisationTbl AS O')
            ->join('Billing.SectorPlanTbl AS SP', 'SP.SectorUID = O.SectorUID', 'left')
            ->where('O.OrgUID', $orgUID)
            ->where('SP.IsActive', 1)
            ->order_by('SP.SectorPlanUID', 'ASC')
            ->limit(1)
            ->get();

        $sectorPlanUID = null;
        if ($sectorPlanRow && $sectorPlanRow->num_rows() > 0) {
            $sp            = $sectorPlanRow->row();
            $sectorPlanUID = ($sp->SectorPlanUID > 0) ? (int) $sp->SectorPlanUID : null;
            if ($sectorPlanUID && (int) $sp->TrialDays > 0) {
                $trialDays = (int) $sp->TrialDays;
                $endDate   = date('Y-m-d H:i:s', strtotime($now . ' +' . $trialDays . ' days'));
            }
        }

        $rSub = $this->dbwrite_model->insertData('Billing', 'OrgSubscriptionTbl', [
            'OrgUID'          => $orgUID,
            'SectorPlanUID'   => $sectorPlanUID,
            'FinancialYear'   => billing_fy('long'),
            'StartDate'       => $startDate,
            'EndDate'         => $endDate,
            'Status'          => 'Trial',
            'AutoRenew'       => 0,
            'GracePeriodDays' => 7,
        ]);
        if ($rSub->Error) throw new Exception('OrgSubscriptionTbl insert failed: ' . $rSub->Message);
        $orgSubUID = (int) $rSub->ID;

        /* Log a matching order row so billing history is complete from day one */
        if ($orgSubUID > 0 && $sectorPlanUID !== null) {
            $rOrder = $this->dbwrite_model->insertData('Billing', 'SubscriptionOrdersTbl', [
                'OrgUID'         => $orgUID,
                'SectorPlanUID'  => $sectorPlanUID,
                'OrgSubUID'      => $orgSubUID,
                'RenewalType'    => 'Trial',
                'DueDate'        => $endDate,
                'Amount'         => 0.00,
                'DiscountAmount' => 0.00,
                'TaxAmount'      => 0.00,
                'NetAmount'      => 0.00,
                'Status'         => 'Waived',
                'CreatedBy'      => null,
            ]);
            if ($rOrder->Error) throw new Exception('SubscriptionOrdersTbl insert failed: ' . $rOrder->Message);
        }
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

    /**
     * @param array<string,string> $g  Verified Google profile fields
     */
    public function registerOrganisationViaGoogle(array $g): object {

        $this->EndReturnData = new stdClass();
        $this->load->model('dbwrite_model');

        try {

            $this->dbwrite_model->startTransaction();
            $WriteDb = $this->dbwrite_model->getWriteDb();
            $now = date('Y-m-d H:i:s');

            $firstName = trim($g['given_name']  ?? '');
            $lastName  = trim($g['family_name'] ?? '');
            $email     = strtolower(trim($g['email']   ?? ''));
            $picture   = trim($g['picture']  ?? '');
            $googleSub = trim($g['sub']      ?? '');
            $locale    = trim($g['locale']   ?? '');
            $hd        = trim($g['hd']       ?? '');

            $orgName   = $firstName ?: 'Organisation';
            $shortCode = $this->_generateShortCode($orgName);
            $orgToken  = $this->_generateOrgToken();
            $username  = $this->_generateUniqueUsername($firstName);

            /* ── OrganisationTbl ──────────────────────────────────────── */
            $orgResult = $this->dbwrite_model->insertData('Organisation', 'OrganisationTbl', [
                'Name'            => $orgName,
                'BrandName'       => $orgName,
                'ShortCode'       => $shortCode,
                'OrgToken'        => $orgToken,
                'CountryCode'     => '+91',
                'CountryISO2'     => 'IN',
                'MobileNumber'    => null,
                'EmailAddress'    => $email,
                'GSTIN'           => null,
                'StateCode'       => null,
                'StateName'       => null,
                'TimezoneUID'     => 181,
                'IsEmailVerified'      => 1,
                'IsOnboardingComplete' => 0,
                'IsActive'             => 1,
                'IsDeleted'            => 0,
                'CreatedBy'            => 0,
                'UpdatedBy'            => 0,
            ]);
            if ($orgResult->Error) throw new Exception('Organisation insert failed: ' . $orgResult->Message);
            $orgUID = (int) $orgResult->ID;

            /* ── BranchesTbl ─────────────────────────────────────────── */
            $branchResult = $this->dbwrite_model->insertData('Organisation', 'BranchesTbl', [
                'OrgUID'          => $orgUID,
                'Name'            => $orgName,
                'BranchCode'      => $shortCode,
                'ContactPerson'   => trim($firstName . ' ' . $lastName),
                'MobileNumber'    => null,
                'CountryCode'     => '+91',
                'CountryISO2'     => 'IN',
                'EmailAddress'    => $email,
                'GSTIN'           => null,
                'StateText'       => null,
                'IsHeadOffice'    => 1,
                'IsWarehouse'     => 1,
                'IsSalesPoint'    => 1,
                'IsDispatchPoint' => 1,
                'IsServiceCenter' => 0,
                'IsActive'        => 1,
                'IsDeleted'       => 0,
                'CreatedBy'       => 0,
                'UpdatedBy'       => 0,
            ]);
            if ($branchResult->Error) throw new Exception('Branch insert failed: ' . $branchResult->Message);
            $branchUID = (int) $branchResult->ID;

            /* ── OrgSettingsTbl ──────────────────────────────────────── */
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

            /* ── RolesTbl — Super Admin ───────────────────────────────── */
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
            ]);
            if ($roleResult->Error) throw new Exception('Role insert failed: ' . $roleResult->Message);
            $roleUID = (int) $roleResult->ID;

            /* ── UserTbl ─────────────────────────────────────────────── */
            $userCode   = $shortCode . '-' . str_pad(1, 4, '0', STR_PAD_LEFT);
            $userResult = $this->dbwrite_model->insertData('Users', 'UserTbl', [
                'EmployeeCode'   => $userCode,
                'FirstName'      => $firstName,
                'LastName'       => $lastName ?: null,
                'UserName'       => $username,
                'EmailAddress'   => $email,
                'Password'       => null,
                'Image'          => $picture   ?: null,
                'GoogleSub'      => $googleSub ?: null,
                'GoogleLocale'   => $locale    ?: null,
                'GoogleHD'       => $hd        ?: null,
                'AuthProvider'   => 'google',
                'OrgUID'         => $orgUID,
                'BranchUID'      => $branchUID,
                'RoleUID'        => $roleUID,
                'CountryCode'    => '+91',
                'CountryISO2'    => 'IN',
                'MobileNumber'   => null,
                'HasLoginAccess' => 1,
                'IsPasswordSet'  => 0,
                'IsActive'       => 1,
                'IsDeleted'      => 0,
                'UILanguage'     => 'en',
                'CreatedBy'      => 0,
                'UpdatedBy'      => 0,
            ]);
            if ($userResult->Error) throw new Exception('User insert failed: ' . $userResult->Message);
            $userUID = (int) $userResult->ID;

            /* Back-fill CreatedBy/UpdatedBy */
            $WriteDb->db_debug = FALSE;
            $WriteDb->where('OrgUID',    $orgUID)->update('Organisation.OrganisationTbl', ['CreatedBy' => $userUID, 'UpdatedBy' => $userUID]);
            $WriteDb->where('BranchUID', $branchUID)->update('Organisation.BranchesTbl', ['CreatedBy' => $userUID, 'UpdatedBy' => $userUID]);
            $WriteDb->where('RoleUID',   $roleUID)->update('UserRole.RolesTbl',           ['CreatedBy' => $userUID, 'UpdatedBy' => $userUID]);
            $WriteDb->where('UserUID',   $userUID)->update('Users.UserTbl',               ['CreatedBy' => $userUID, 'UpdatedBy' => $userUID]);

            /* Additional default roles */
            foreach (['Admin', 'Sales Manager', 'Sales Executive', 'Accountant'] as $roleName) {
                $r = $this->dbwrite_model->insertData('UserRole', 'RolesTbl', [
                    'Name'      => $roleName,
                    'OrgUID'    => $orgUID,
                    'BranchUID' => $branchUID,
                    'IsDefault' => 1,
                    'IsGlobal'  => 0,
                    'IsActive'  => 1,
                    'IsDeleted' => 0,
                    'CreatedBy' => $userUID,
                    'UpdatedBy' => $userUID,
                ]);
                if ($r->Error) throw new Exception('Role insert failed (' . $roleName . '): ' . $r->Message);
            }

            /* UserBranchAccessTbl */
            $r = $this->dbwrite_model->insertData('Users', 'UserBranchAccessTbl', [
                'UserUID'   => $userUID,
                'OrgUID'    => $orgUID,
                'BranchUID' => $branchUID,
                'IsDefault' => 1,
                'IsActive'  => 1,
                'CreatedBy' => $userUID,
            ]);
            if ($r->Error) throw new Exception('UserBranchAccess insert failed: ' . $r->Message);

            $this->_assignTrialSubscription($orgUID, $now);
            $this->_copyMenusFromTemplate($orgUID, $roleUID, $userUID);

            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error   = false;
            $this->EndReturnData->Message = 'Success';
            $this->EndReturnData->OrgUID  = $orgUID;
            return $this->EndReturnData;

        } catch (Exception $e) {
            try { $this->dbwrite_model->rollbackTransaction(); } catch (Exception $_) {}
            notifyError('Signup_model::registerOrganisationViaGoogle', $e);
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
            return $this->EndReturnData;
        }

    }

    private function _generateUniqueUsername(string $name): string {
        $base    = strtolower(preg_replace('/[^a-z0-9]/i', '', $name));
        $base    = substr($base ?: 'user', 0, 12);
        $attempt = $base;
        $i       = 1;
        $this->ReadDb->db_debug = FALSE;
        while ($this->ReadDb->where('UserName', $attempt)->where('IsDeleted', 0)->count_all_results('Users.UserTbl') > 0) {
            $attempt = $base . $i++;
        }
        return $attempt;
    }

    public function sendVerificationEmail(int $orgUID, string $firstName, string $email): void {
        $this->load->library('telegramnotifier');
        try {
            /* ── Step 1: validate inputs ──────────────────────────────────── */
            if ($orgUID <= 0 || empty($email)) {
                throw new Exception('sendVerificationEmail called with invalid orgUID or empty email. orgUID=' . $orgUID . ' email=' . $email);
            }

            /* ── Step 2: generate token and update DB ─────────────────────── */
            $this->load->model('dbwrite_model');
            $token   = bin2hex(random_bytes(32));
            $expiry  = date('Y-m-d H:i:s', strtotime('+24 hours'));
            $WriteDb = $this->dbwrite_model->getWriteDb();
            $WriteDb->db_debug = FALSE;

            $updated = $WriteDb->where('OrgUID', $orgUID)->update('Organisation.OrganisationTbl', [
                'EmailVerifyToken'  => $token,
                'EmailVerifyExpiry' => $expiry,
            ]);

            if (!$updated) {
                $dbErr = $WriteDb->error();
                throw new Exception('DB update failed for OrgUID=' . $orgUID . '. ' . ($dbErr['message'] ?? 'Unknown DB error'));
            }

            /* ── Step 3: build and send email via Brevo ───────────────────── */
            $verifyUrl = base_url('verify-email/' . $token);
            $fromEmail = getenv('MAIL_FROM_EMAIL') ?: getenv('MAIL_USERNAME');
            $fromName  = getenv('MAIL_FROM_NAME')  ?: 'R2K Enterprises';
            $apiKey    = getenv('BREVO_API_KEY');

            if (empty($apiKey)) {
                throw new Exception('BREVO_API_KEY is not set in .env');
            }
            if (empty($fromEmail)) {
                throw new Exception('MAIL_FROM_EMAIL is not set in .env');
            }

            $htmlBody = '<!DOCTYPE html><html><head><meta charset="UTF-8">'
                . '<style>body{font-family:Arial,sans-serif;font-size:14px;color:#333;line-height:1.6;margin:0;padding:20px;}'
                . '.btn{display:inline-block;padding:12px 28px;background:#f59e0b;color:#0a1628;text-decoration:none;border-radius:8px;font-size:14px;font-weight:700;}'
                . '</style></head><body>'
                . '<p>Hi ' . htmlspecialchars($firstName) . ',</p>'
                . '<p>Thank you for signing up! Please verify your email address by clicking the button below:</p>'
                . '<p style="margin:24px 0;"><a class="btn" href="' . $verifyUrl . '">Verify Email Address</a></p>'
                . '<p>If the button does not work, copy and paste this link into your browser:<br>'
                . '<a href="' . $verifyUrl . '">' . $verifyUrl . '</a></p>'
                . '<p>This link will expire in <strong>24 hours</strong>.</p>'
                . '<p>Regards,<br>' . $fromName . '</p>'
                . '</body></html>';

            $payload = json_encode([
                'sender'      => ['name' => $fromName, 'email' => $fromEmail],
                'to'          => [['email' => $email, 'name' => $firstName]],
                'subject'     => 'Verify your email address — ' . $fromName,
                'htmlContent' => $htmlBody,
            ]);

            $ch = curl_init('https://api.brevo.com/v3/smtp/email');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_HTTPHEADER     => [
                    'accept: application/json',
                    'api-key: ' . $apiKey,
                    'content-type: application/json',
                ],
                CURLOPT_TIMEOUT => 30,
            ]);
            $response = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr  = curl_error($ch);
            curl_close($ch);

            if ($curlErr) {
                throw new Exception('Brevo cURL error: ' . $curlErr);
            }
            if ($httpCode < 200 || $httpCode >= 300) {
                throw new Exception('Brevo API HTTP ' . $httpCode . ' — ' . $response);
            }

        } catch (Throwable $e) {
            Telegramnotifier::error('Signup_model::sendVerificationEmail', $e, [
                'OrgUID'    => $orgUID,
                'ToEmail'   => $email,
                'FirstName' => $firstName,
            ]);
        }
    }

    private function _copyMenusFromTemplate(int $orgUID, int $roleUID, int $userUID): void {

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
            $r = $this->dbwrite_model->insertData('Modules', 'MainMenusTbl', [
                'OrgUID'       => $orgUID,
                'Source'       => 'Plan',
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
            if ($r->Error) throw new Exception('MainMenu insert failed: ' . $r->Message . ' (Name=' . $mm->Name . ')');
            $mainMenuMap[(int) $mm->MainMenuUID] = (int) $r->ID;
        }

        // ── Modules — global catalog with UNIQUE(Name); skip copy, use original UIDs ──
        // ModuleTbl.Name is globally unique across all orgs so inserts would fail.
        // SubMenusTbl.ModuleUID FK is satisfied as long as the original record exists.

        // ── RoleMainMenusTbl — inserted before SubMenus so RoleMainMenuUID is available ──
        $roleMainMenuMap = [];
        $sort = 1;
        foreach ($mainMenuMap as $newMMUID) {
            $r = $this->dbwrite_model->insertData('UserRole', 'RoleMainMenusTbl', [
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
            if ($r->Error) throw new Exception('RoleMainMenu insert failed: ' . $r->Message . ' (MainMenuUID=' . $newMMUID . ')');
            $roleMainMenuMap[$newMMUID] = (int) $r->ID;
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
        $subMenuRoleMMMap = [];

        foreach ($subMenuRows as $sm) {
            $oldMain   = (int) $sm->MainMenuUID;
            $oldParent = ($sm->ParentSubMenuUID !== null && $sm->ParentSubMenuUID !== '') ? (int) $sm->ParentSubMenuUID : 0;
            $origMod   = ($sm->ModuleUID !== null && $sm->ModuleUID !== '') ? (int) $sm->ModuleUID : null;

            $newMMUID     = $mainMenuMap[$oldMain] ?? 0;
            $newRoleMMUID = $roleMainMenuMap[$newMMUID] ?? 0;

            $r = $this->dbwrite_model->insertData('Modules', 'SubMenusTbl', [
                'OrgUID'           => $orgUID,
                'Source'           => 'Plan',
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
            if ($r->Error) throw new Exception('SubMenu insert failed: ' . $r->Message . ' (Name=' . $sm->Name . ', ModuleUID=' . ($origMod ?? 'null') . ')');
            $newSubUID                         = (int) $r->ID;
            $subMenuMap[(int) $sm->SubMenuUID] = $newSubUID;
            $subMenuRoleMMMap[$newSubUID]       = $newRoleMMUID;
        }

        // ── RoleSubMenusTbl — admin gets full access ───────────────────────────
        $sort = 1;
        foreach ($subMenuMap as $newSubUID) {
            $roleMMUID = $subMenuRoleMMMap[$newSubUID] ?? 0;
            $r = $this->dbwrite_model->insertData('UserRole', 'RoleSubMenusTbl', [
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
            if ($r->Error) throw new Exception('RoleSubMenu insert failed: ' . $r->Message . ' (SubMenuUID=' . $newSubUID . ', RoleUID=' . $roleUID . ')');
        }

    }

}
