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
            $gstin        = !empty(trim($formData['GSTIN'] ?? '')) ? strtoupper(trim($formData['GSTIN'])) : null;
            $countryCode  = !empty(trim($formData['CountryCode'] ?? '')) ? trim($formData['CountryCode']) : '+91';

            /* Resolve SectorUID + PlanUID from the selected plan BEFORE the transaction
               so _seedMenusFromSector receives them directly — ReadDb cannot see rows
               inserted on WriteDb that haven't been committed yet. */
            $sectorPlanUIDMeta = (int)($formData['SectorPlanUID'] ?? 0);
            $sectorUID         = 0;
            $seedPlanUID       = 0;
            if ($sectorPlanUIDMeta > 0) {
                $this->ReadDb->db_debug = FALSE;
                $planMeta = $this->ReadDb->select('SectorUID, PlanUID')
                    ->from('Billing.SectorPlanTbl')
                    ->where('SectorPlanUID', $sectorPlanUIDMeta)
                    ->where('IsActive', 1)
                    ->limit(1)
                    ->get()->row();
                if ($planMeta) {
                    $sectorUID   = (int)($planMeta->SectorUID ?? 0);
                    $seedPlanUID = (int)($planMeta->PlanUID   ?? 0);
                }
            }

            // 2. OrganisationTbl
            $orgResult = $this->dbwrite_model->insertData('Organisation', 'OrganisationTbl', [
                'Name'         => $orgName,
                'BrandName'    => $orgName,
                'ShortCode'    => $shortCode,
                'OrgToken'     => $orgToken,
                'SectorUID'    => $sectorUID,
                'CountryCode'  => $countryCode,
                'CountryISO2'  => 'IN',
                'MobileNumber' => trim($formData['OrgMobile']),
                'EmailAddress' => strtolower(trim($formData['OrgEmail'])),
                'GSTIN'            => $gstin,
                'GSTINValidation'  => $gstin ? 1 : 0,
                'PANNumber'        => $gstin ? substr($gstin, 2, 10) : null,
                'StateCode'        => trim($formData['StateCode']),
                'StateName'        => trim($formData['StateName']),
                'TimezoneUID'      => (int) ($formData['TimezoneUID'] ?? 181),
                'IsActive'         => 1,
                'IsDeleted'        => 0,
                'CreatedBy'        => 0,
                'UpdatedBy'        => 0,
            ]);
            if ($orgResult->Error) throw new Exception('Organisation insert failed: ' . $orgResult->Message);
            $orgUID = (int) $orgResult->ID;

            // 3a. OrgAddressTbl — billing address when provided (no GSTIN flow)
            $addrLine1 = trim($formData['AddrLine1'] ?? '');
            if (!empty($addrLine1)) {
                $this->dbwrite_model->insertData('Organisation', 'OrgAddressTbl', [
                    'OrgUID'      => $orgUID,
                    'AddressType' => 'Billing',
                    'Line1'       => $addrLine1,
                    'Line2'       => trim($formData['AddrLine2'] ?? '') ?: null,
                    'CityText'    => trim($formData['AddrCity'] ?? '') ?: null,
                    'StateText'   => trim($formData['StateName'] ?? '') ?: null,
                    'Pincode'     => trim($formData['AddrPincode'] ?? '') ?: null,
                    'IsActive'    => 1,
                    'IsDeleted'   => 0,
                    'CreatedBy'   => 0,
                    'UpdatedBy'   => 0,
                ]);
            }

            /* Branch name = org name; branch code = short code (not shortCode+HO) */
            $adminName    = trim($formData['AdminFirstName']) . ' ' . trim($formData['AdminLastName'] ?? '');
            $branchResult = $this->dbwrite_model->insertData('Organisation', 'BranchesTbl', [
                'OrgUID'          => $orgUID,
                'Name'            => $orgName,
                'BranchCode'      => $shortCode,
                'ContactPerson'   => trim($adminName),
                'MobileNumber'    => trim($formData['OrgMobile']),
                'CountryCode'     => $countryCode,
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
                'CountryCode'    => $countryCode,
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

            // 8. OrgSubscriptionTbl — assign based on selected plan
            $sectorPlanUID = (int)($formData['SectorPlanUID'] ?? 0);
            $assignResult  = $this->_assignPlanSubscription($orgUID, $sectorPlanUID, $now);

            // 9. Copy menus & permissions from template org
            $this->_seedMenusFromSector($orgUID, $sectorUID, $seedPlanUID, $roleUID, $userUID);

            $this->dbwrite_model->commitTransaction();

            // Free plan — record order/payment/invoice + stamp FirstPaidOn immediately after commit
            if (!$assignResult->isPaid && $assignResult->orderUID > 0) {
                $this->createPaymentAndInvoice(
                    $orgUID, $assignResult->orderUID, $assignResult->plan ?? new stdClass(),
                    'New', '', '', '', 'Free', $now
                );
                $this->dbwrite_model->updateData('Billing', 'OrgSubscriptionTbl',
                    ['OrderUID' => null], ['OrgUID' => $orgUID]);
                $this->dbwrite_model->updateData('Billing', 'OrgSubscriptionTbl',
                    ['FirstPaidOn' => $now], ['OrgUID' => $orgUID, 'FirstPaidOn' => null]);
            }

            // Send verification email after commit (non-critical — failure does not roll back)
            $this->sendVerificationEmail(
                $orgUID,
                trim($formData['AdminFirstName']),
                strtolower(trim($formData['OrgEmail']))
            );

            $this->EndReturnData->Error      = false;
            $this->EndReturnData->Message    = 'Success';
            $this->EndReturnData->OrgUID     = $orgUID;
            $this->EndReturnData->IsPaidPlan = $assignResult->isPaid;
            return $this->EndReturnData;

        } catch (Exception $e) {
            try { $this->dbwrite_model->rollbackTransaction(); } catch (Exception $_) {}
            notifyError('Signup_model::registerOrganisation', $e);
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
            return $this->EndReturnData;
        }

    }

    /* Assign subscription based on the plan selected during signup.
       Returns object {isPaid, orderUID, plan} — isPaid=true means payment required. */
    private function _assignPlanSubscription(int $orgUID, int $sectorPlanUID, string $now): object {
        $isPaid           = false;
        $planPrice        = 0.0;
        $planDurationDays = 30;
        $planRow          = null;

        if ($sectorPlanUID > 0) {
            $planRow = $this->getSectorPlan($sectorPlanUID);

            if ($planRow) {
                $planPrice        = (float)($planRow->Price ?? 0);
                $planDurationDays = max(1, (int)($planRow->DurationDays ?? 30));
                $isPaid           = ($planPrice > 0);
                $taxableAmount    = (float)($planRow->TaxableAmount ?? $planPrice);
                $taxAmount        = (float)($planRow->TaxAmount ?? 0.00);
                $netAmount        = (float)($planRow->TotalAmount ?? $planPrice);
            }
        }

        $startDate = gmdate('Y-m-d H:i:s');
        $_ts = time() + $planDurationDays * 86400;
        [$_y, $_m, $_d] = explode('-', gmdate('Y-m-d', $_ts + 19800));
        $endDate = gmdate('Y-m-d H:i:s', gmmktime(23, 59, 59, (int)$_m, (int)$_d, (int)$_y) - 19800);
        /* sectorPlanUID=0 means no plan selected yet (e.g. Google OAuth signup) — force
           PendingPayment so the user is sent to the subscribe page to pick a plan */
        $status    = ($isPaid || $sectorPlanUID === 0) ? 'PendingPayment' : 'Active';

        $rSub = $this->dbwrite_model->insertData('Billing', 'OrgSubscriptionTbl', [
            'OrgUID'          => $orgUID,
            'SectorPlanUID'   => $sectorPlanUID > 0 ? $sectorPlanUID : 1,
            'FinancialYear'   => billing_fy('long'),
            'StartDate'       => $startDate,
            'EndDate'         => $endDate,
            'Status'          => $status,
            'AutoRenew'       => 0,
            'GracePeriodDays' => 7,
        ]);
        if ($rSub->Error) throw new Exception('OrgSubscriptionTbl insert failed: ' . $rSub->Message);
        $orgSubUID = (int) $rSub->ID;

        $orderUID = 0;
        if ($orgSubUID > 0 && $sectorPlanUID > 0) {
            $rOrder = $this->dbwrite_model->insertData('Billing', 'SubscriptionOrdersTbl', [
                'OrgUID'         => $orgUID,
                'SectorPlanUID'  => $sectorPlanUID,
                'OrgSubUID'      => $orgSubUID,
                'FinancialYear'  => billing_fy('long'),
                'RenewalType'    => $isPaid ? 'New' : 'Trial',
                'DueDate'        => $endDate,
                'Amount'         => $isPaid ? ($taxableAmount ?? $planPrice) : 0.00,
                'DiscountAmount' => 0.00,
                'TaxAmount'      => $isPaid ? ($taxAmount ?? 0.00) : 0.00,
                'NetAmount'      => $isPaid ? ($netAmount ?? $planPrice) : 0.00,
                'Status'         => $isPaid ? 'Pending' : 'Waived',
                'IsPaid'         => $isPaid ? 0 : 1,
                'CreatedBy'      => null,
            ]);
            if ($rOrder->Error) throw new Exception('SubscriptionOrdersTbl insert failed: ' . $rOrder->Message);
            $orderUID = (int)$rOrder->ID;
            $this->dbwrite_model->updateData('Billing', 'OrgSubscriptionTbl', ['OrderUID' => $orderUID], ['OrgSubUID' => $orgSubUID]);
        }

        return (object)['isPaid' => $isPaid, 'orderUID' => $orderUID, 'plan' => $planRow];
    }

    private function _assignTrialSubscription(int $orgUID, string $now): void {

        $trialDays = 30;
        $startDate = gmdate('Y-m-d H:i:s');
        $_ts = time() + $trialDays * 86400;
        [$_y, $_m, $_d] = explode('-', gmdate('Y-m-d', $_ts + 19800));
        $endDate = gmdate('Y-m-d H:i:s', gmmktime(23, 59, 59, (int)$_m, (int)$_d, (int)$_y) - 19800);

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
                $_ts = time() + $trialDays * 86400;
                [$_y, $_m, $_d] = explode('-', gmdate('Y-m-d', $_ts + 19800));
                $endDate = gmdate('Y-m-d H:i:s', gmmktime(23, 59, 59, (int)$_m, (int)$_d, (int)$_y) - 19800);
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
                'FinancialYear'  => billing_fy('long'),
                'RenewalType'    => 'Trial',
                'DueDate'        => $endDate,
                'Amount'         => 0.00,
                'DiscountAmount' => 0.00,
                'TaxAmount'      => 0.00,
                'NetAmount'      => 0.00,
                'Status'         => 'Waived',
                'IsPaid'         => 1,
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
            for ($i = 0; $i < 12; $i++) {
                $token .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $exists = $this->ReadDb->where('OrgToken', $token)->count_all_results('Organisation.OrganisationTbl');
        } while ($exists > 0);
        return $token;
    }

    /**
     * @param array<string,string> $g  Verified Google profile fields
     * @param int $sectorPlanUID  Plan chosen during signup (0 = free/trial)
     */
    public function registerOrganisationViaGoogle(array $g, int $sectorPlanUID = 0): object {

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

            $orgName     = $firstName ?: 'Organisation';
            $shortCode   = $this->_generateShortCode($orgName);
            $orgToken    = $this->_generateOrgToken();
            $username    = $this->_generateUniqueUsername($firstName);
            $countryCode = '+91';

            /* Resolve SectorUID + PlanUID BEFORE the transaction — ReadDb cannot see
               rows inserted on WriteDb that haven't been committed yet. */
            $sectorUID   = 0;
            $seedPlanUID = 0;
            if ($sectorPlanUID > 0) {
                $this->ReadDb->db_debug = FALSE;
                $planMeta = $this->ReadDb->select('SectorUID, PlanUID')
                    ->from('Billing.SectorPlanTbl')
                    ->where('SectorPlanUID', $sectorPlanUID)
                    ->where('IsActive', 1)
                    ->limit(1)
                    ->get()->row();
                if ($planMeta) {
                    $sectorUID   = (int)($planMeta->SectorUID ?? 0);
                    $seedPlanUID = (int)($planMeta->PlanUID   ?? 0);
                }
            }
            if ($sectorUID === 0) {
                $this->ReadDb->db_debug = FALSE;
                $defaultSectorResult = $this->ReadDb->select('SectorUID')
                    ->from('Billing.SectorsTbl')
                    ->where('IsActive', 1)
                    ->limit(1)
                    ->get();
                if ($defaultSectorResult && $defaultSectorResult !== false) {
                    $defaultSector = $defaultSectorResult->row();
                    if ($defaultSector) $sectorUID = (int)$defaultSector->SectorUID;
                }
            }

            /* ── OrganisationTbl ──────────────────────────────────────── */
            $orgResult = $this->dbwrite_model->insertData('Organisation', 'OrganisationTbl', [
                'Name'            => $orgName,
                'BrandName'       => $orgName,
                'ShortCode'       => $shortCode,
                'OrgToken'        => $orgToken,
                'SectorUID'       => $sectorUID,
                'CountryCode'     => $countryCode,
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
                'CountryCode'     => $countryCode,
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
                'CountryCode'    => $countryCode,
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

            $assignResult = $this->_assignPlanSubscription($orgUID, $sectorPlanUID, $now);
            $this->_seedMenusFromSector($orgUID, $sectorUID, $seedPlanUID, $roleUID, $userUID);

            $this->dbwrite_model->commitTransaction();

            // Free plan — record order/payment/invoice + stamp FirstPaidOn immediately after commit
            if (!$assignResult->isPaid && $assignResult->orderUID > 0) {
                $this->createPaymentAndInvoice(
                    $orgUID, $assignResult->orderUID, $assignResult->plan ?? new stdClass(),
                    'New', '', '', '', 'Free', $now
                );
                $this->dbwrite_model->updateData('Billing', 'OrgSubscriptionTbl',
                    ['OrderUID' => null], ['OrgUID' => $orgUID]);
                $this->dbwrite_model->updateData('Billing', 'OrgSubscriptionTbl',
                    ['FirstPaidOn' => $now], ['OrgUID' => $orgUID, 'FirstPaidOn' => null]);
            }

            $this->EndReturnData->Error      = false;
            $this->EndReturnData->Message    = 'Success';
            $this->EndReturnData->OrgUID     = $orgUID;
            $this->EndReturnData->IsPaidPlan = $assignResult->isPaid;
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

    /**
     * Updates IsActive on all 4 menu tables for an existing org when their plan changes.
     * Reads SectorPlanModulesTbl for the new plan, then batch-updates MainMenusTbl,
     * SubMenusTbl, RoleMainMenusTbl, RoleSubMenusTbl accordingly.
     *
     * @return object {Error, Message?, SectorUID, PlanUID, Price, DurationDays}
     */
    public function applyPlanMenuFilter(int $orgUID, int $sectorPlanUID): object {
        $out = new stdClass();
        $out->Error = false;
        $this->ReadDb->db_debug = FALSE;

        /* 1. Resolve sectorUID + planUID from the chosen plan */
        $planMeta = $this->ReadDb->select('SectorUID, PlanUID, Price, DurationDays')
            ->from('Billing.SectorPlanTbl')
            ->where('SectorPlanUID', $sectorPlanUID)
            ->where('IsActive', 1)
            ->limit(1)
            ->get()->row();

        if (!$planMeta) {
            $out->Error   = true;
            $out->Message = 'Plan not found.';
            return $out;
        }

        $sectorUID = (int)$planMeta->SectorUID;
        $planUID   = (int)$planMeta->PlanUID;

        /* 2. Allowed ModuleUIDs for this plan (empty = no filter = all active) */
        $allowedModuleUIDs = [];
        if ($planUID > 0) {
            $modRows = $this->ReadDb->select('ModuleUID')
                ->from('Billing.SectorPlanModulesTbl')
                ->where('SectorUID', $sectorUID)
                ->where('PlanUID',   $planUID)
                ->get()->result();
            $allowedModuleUIDs = array_map('intval', array_column($modRows, 'ModuleUID'));
        }

        /* 3. Load this org's menus */
        $mainMenus = $this->ReadDb->select('MainMenuUID, ModuleUID, IsDirectLink')
            ->from('Modules.MainMenusTbl')
            ->where('OrgUID',    $orgUID)
            ->where('IsDeleted', 0)
            ->get()->result();

        $subMenus = $this->ReadDb->select('SubMenuUID, MainMenuUID, ModuleUID, ParentSubMenuUID')
            ->from('Modules.SubMenusTbl')
            ->where('OrgUID',    $orgUID)
            ->where('IsDeleted', 0)
            ->get()->result();

        /* 4. Compute which main menus are active */
        $activeMMUIDs = [];
        foreach ($subMenus as $sm) {
            $modUID = ($sm->ModuleUID !== null && $sm->ModuleUID !== '') ? (int)$sm->ModuleUID : null;
            if (empty($allowedModuleUIDs) || ($modUID !== null && in_array($modUID, $allowedModuleUIDs, true))) {
                $activeMMUIDs[(int)$sm->MainMenuUID] = true;
            }
        }
        foreach ($mainMenus as $mm) {
            if ((int)($mm->IsDirectLink ?? 0) !== 1) continue;
            $modUID = ($mm->ModuleUID !== null && $mm->ModuleUID !== '') ? (int)$mm->ModuleUID : null;
            if ($modUID === null || empty($allowedModuleUIDs) || in_array($modUID, $allowedModuleUIDs, true)) {
                $activeMMUIDs[(int)$mm->MainMenuUID] = true;
            }
        }

        $allMMUIDs      = array_map(fn(object $m): int => (int)$m->MainMenuUID, $mainMenus);
        $activeMMList   = array_keys($activeMMUIDs);
        $inactiveMMList = array_values(array_diff($allMMUIDs, $activeMMList));

        /* 5. Compute which sub menus are active (parent headers inherit from children) */
        $activeParentSubUIDs = [];
        foreach ($subMenus as $sm) {
            if (!$sm->ParentSubMenuUID) continue;
            $modUID = ($sm->ModuleUID !== null && $sm->ModuleUID !== '') ? (int)$sm->ModuleUID : null;
            if (empty($allowedModuleUIDs) || ($modUID !== null && in_array($modUID, $allowedModuleUIDs, true))) {
                $activeParentSubUIDs[(int)$sm->ParentSubMenuUID] = true;
            }
        }

        $activeSMUIDs   = [];
        $inactiveSMUIDs = [];
        foreach ($subMenus as $sm) {
            $smUID  = (int)$sm->SubMenuUID;
            $modUID = ($sm->ModuleUID !== null && $sm->ModuleUID !== '') ? (int)$sm->ModuleUID : null;
            $isActive = isset($activeParentSubUIDs[$smUID])
                || empty($allowedModuleUIDs)
                || ($modUID !== null && in_array($modUID, $allowedModuleUIDs, true));
            if ($isActive) {
                $activeSMUIDs[] = $smUID;
            } else {
                $inactiveSMUIDs[] = $smUID;
            }
        }

        /* 6. Batch-update all 4 tables */
        $this->load->model('dbwrite_model');
        $writeDb = $this->dbwrite_model->getWriteDb();
        $writeDb->db_debug = FALSE;

        if (!empty($activeMMList)) {
            $writeDb->where_in('MainMenuUID', $activeMMList)->update('Modules.MainMenusTbl',      ['IsActive' => 1]);
            $writeDb->where_in('MainMenuUID', $activeMMList)->update('UserRole.RoleMainMenusTbl', ['IsActive' => 1]);
        }
        if (!empty($inactiveMMList)) {
            $writeDb->where_in('MainMenuUID', $inactiveMMList)->update('Modules.MainMenusTbl',      ['IsActive' => 0]);
            $writeDb->where_in('MainMenuUID', $inactiveMMList)->update('UserRole.RoleMainMenusTbl', ['IsActive' => 0]);
        }
        if (!empty($activeSMUIDs)) {
            $writeDb->where_in('SubMenuUID', $activeSMUIDs)->update('Modules.SubMenusTbl',      ['IsActive' => 1]);
            $writeDb->where_in('SubMenuUID', $activeSMUIDs)->update('UserRole.RoleSubMenusTbl', ['IsActive' => 1]);
        }
        if (!empty($inactiveSMUIDs)) {
            $writeDb->where_in('SubMenuUID', $inactiveSMUIDs)->update('Modules.SubMenusTbl',      ['IsActive' => 0]);
            $writeDb->where_in('SubMenuUID', $inactiveSMUIDs)->update('UserRole.RoleSubMenusTbl', ['IsActive' => 0]);
        }

        $out->SectorUID    = $sectorUID;
        $out->PlanUID      = $planUID;
        $out->Price        = (float)$planMeta->Price;
        $out->DurationDays = (int)$planMeta->DurationDays;
        return $out;
    }

    /* $sectorUID and $planUID are passed directly — they were resolved from SectorPlanTbl
       BEFORE the transaction started so ReadDb could read them; reading them again inside
       the transaction would fail because ReadDb cannot see WriteDb's uncommitted rows. */
    private function _seedMenusFromSector(int $orgUID, int $sectorUID, int $planUID, int $roleUID, int $userUID): void {

        if ($sectorUID <= 0) return;

        $this->ReadDb->db_debug = FALSE;

        /* ── 1. Allowed ModuleUIDs for this sector + plan ───────────────────── */
        $allowedModuleUIDs = [];
        if ($planUID > 0) {
            $modRows = $this->ReadDb->select('ModuleUID')
                ->from('Billing.SectorPlanModulesTbl')
                ->where('SectorUID', $sectorUID)
                ->where('PlanUID',   $planUID)
                ->get()->result();
            $allowedModuleUIDs = array_map('intval', array_column($modRows, 'ModuleUID'));
        }
        /* Empty allowedModuleUIDs = no filter → all menus active (Trial / unconfigured) */

        /* ── 2. Read ALL sector main menus ──────────────────────────────────── */
        $mainMenuRows = $this->ReadDb->select('*')
            ->from('Modules.SectorMainMenusTbl')
            ->where('SectorUID',  $sectorUID)
            ->where('IsDeleted',  0)
            ->order_by('Sorting', 'ASC')
            ->get()->result();

        if (empty($mainMenuRows)) return;

        /* ── 3. Read ALL sector sub menus ───────────────────────────────────── */
        $subMenuRows = $this->ReadDb->select('*')
            ->from('Modules.SectorSubMenusTbl')
            ->where('SectorUID',  $sectorUID)
            ->where('IsDeleted',  0)
            ->order_by('ParentSectorSubMenuUID', 'ASC')
            ->order_by('Sorting', 'ASC')
            ->get()->result();

        /* ── 6. Determine which main menus are active ──────────────────────── */
        $activeMainSectorUIDs = [];

        /* Regular menus: active if ≥ 1 submenu's ModuleUID is in allowed list */
        foreach ($subMenuRows as $sm) {
            $modUID   = ($sm->ModuleUID !== null && $sm->ModuleUID !== '') ? (int)$sm->ModuleUID : null;
            $isActive = empty($allowedModuleUIDs) || ($modUID !== null && in_array($modUID, $allowedModuleUIDs, true));
            if ($isActive) {
                $activeMainSectorUIDs[(int)$sm->SectorMainMenuUID] = true;
            }
        }

        /* Direct-link menus: active based on their own ModuleUID */
        foreach ($mainMenuRows as $mm) {
            if ((int)($mm->IsDirectLink ?? 0) !== 1) continue;
            $modUID = ($mm->ModuleUID !== null && $mm->ModuleUID !== '') ? (int)$mm->ModuleUID : null;
            if ($modUID === null) {
                /* No module constraint — always active */
                $activeMainSectorUIDs[(int)$mm->SectorMainMenuUID] = true;
            } elseif (empty($allowedModuleUIDs) || in_array($modUID, $allowedModuleUIDs, true)) {
                $activeMainSectorUIDs[(int)$mm->SectorMainMenuUID] = true;
            }
        }

        /* ── 7. Insert main menus ───────────────────────────────────────────── */
        $mainMenuMap     = []; /* SectorMainMenuUID → new MainMenuUID */
        $mainMenuIsActive = []; /* SectorMainMenuUID → isActive */
        foreach ($mainMenuRows as $mm) {
            $sectorMMUID = (int)$mm->SectorMainMenuUID;
            $isActive    = isset($activeMainSectorUIDs[$sectorMMUID]) ? 1 : 0;
            $mmModUID    = ($mm->ModuleUID !== null && $mm->ModuleUID !== '') ? (int)$mm->ModuleUID : null;
            $r = $this->dbwrite_model->insertData('Modules', 'MainMenusTbl', [
                'OrgUID'       => $orgUID,
                'Source'       => 'Plan',
                'Name'         => $mm->Name,
                'Icon'         => $mm->Icon ?? '',
                'IsDirectLink' => $mm->IsDirectLink ?? 0,
                'DirectUrl'    => $mm->DirectUrl ?? '',
                'ModuleUID'    => $mmModUID,
                'Sorting'      => $mm->Sorting ?? 0,
                'IsActive'     => $isActive,
                'IsDeleted'    => 0,
                'CreatedBy'    => $userUID,
                'UpdatedBy'    => $userUID,
            ]);
            if ($r->Error) throw new Exception('MainMenu insert failed: ' . $r->Message . ' (Name=' . $mm->Name . ')');
            $mainMenuMap[$sectorMMUID]      = (int)$r->ID;
            $mainMenuIsActive[$sectorMMUID] = $isActive;
        }

        /* ── 8. Insert RoleMainMenusTbl (before submenus so RoleMainMenuUID exists) ── */
        $roleMainMenuMap = []; /* new MainMenuUID → RoleMainMenuUID */
        $sort = 1;
        foreach ($mainMenuMap as $sectorMMUID => $newMMUID) {
            $isActive = $mainMenuIsActive[$sectorMMUID] ?? 0;
            $r = $this->dbwrite_model->insertData('UserRole', 'RoleMainMenusTbl', [
                'RoleUID'     => $roleUID,
                'MainMenuUID' => $newMMUID,
                'Sorting'     => $sort++,
                'CanView'     => 1,
                'CanCreate'   => 1,
                'CanEdit'     => 1,
                'CanDelete'   => 1,
                'IsActive'    => $isActive,
                'IsDeleted'   => 0,
                'CreatedBy'   => $userUID,
                'UpdatedBy'   => $userUID,
            ]);
            if ($r->Error) throw new Exception('RoleMainMenu insert failed: ' . $r->Message . ' (MainMenuUID=' . $newMMUID . ')');
            $roleMainMenuMap[$newMMUID] = (int)$r->ID;
        }

        /* ── 9. Insert sub menus ────────────────────────────────────────────── */
        /* Pre-compute: which SectorSubMenuUIDs are parent headers of ≥1 active child */
        $activeParentSectorSubUIDs = [];
        foreach ($subMenuRows as $sm) {
            if ($sm->ParentSectorSubMenuUID === null || $sm->ParentSectorSubMenuUID === '') continue;
            $modUID = ($sm->ModuleUID !== null && $sm->ModuleUID !== '') ? (int)$sm->ModuleUID : null;
            if (empty($allowedModuleUIDs) || ($modUID !== null && in_array($modUID, $allowedModuleUIDs, true))) {
                $activeParentSectorSubUIDs[(int)$sm->ParentSectorSubMenuUID] = true;
            }
        }

        $subMenuMap       = []; /* SectorSubMenuUID → new SubMenuUID */
        $subMenuIsActive  = []; /* new SubMenuUID → isActive */
        $subMenuRoleMMMap = []; /* new SubMenuUID → RoleMainMenuUID */

        foreach ($subMenuRows as $sm) {
            $sectorMMUID = (int)$sm->SectorMainMenuUID;
            $newMMUID    = $mainMenuMap[$sectorMMUID] ?? 0;
            if (!$newMMUID) continue;

            $newRoleMMUID  = $roleMainMenuMap[$newMMUID] ?? 0;
            $modUID        = ($sm->ModuleUID !== null && $sm->ModuleUID !== '') ? (int)$sm->ModuleUID : null;
            $ssmUID        = (int)$sm->SectorSubMenuUID;
            /* Parent header rows (ModuleUID=NULL) are active if any child is active */
            $isActive      = (
                isset($activeParentSectorSubUIDs[$ssmUID])
                || empty($allowedModuleUIDs)
                || ($modUID !== null && in_array($modUID, $allowedModuleUIDs, true))
            ) ? 1 : 0;
            $oldParentUID  = ($sm->ParentSectorSubMenuUID !== null && $sm->ParentSectorSubMenuUID !== '') ? (int)$sm->ParentSectorSubMenuUID : 0;
            $newParentUID  = ($oldParentUID && isset($subMenuMap[$oldParentUID])) ? $subMenuMap[$oldParentUID] : null;

            $r = $this->dbwrite_model->insertData('Modules', 'SubMenusTbl', [
                'OrgUID'           => $orgUID,
                'Source'           => 'Plan',
                'MainMenuUID'      => $newMMUID,
                'Name'             => $sm->Name,
                'UrlPath'          => $sm->UrlPath ?? '',
                'ParentSubMenuUID' => $newParentUID,
                'IsParent'         => $sm->IsParent ?? 0,
                'Icon'             => $sm->Icon ?? '',
                'ModuleUID'        => $modUID,
                'Sorting'          => $sm->Sorting ?? 0,
                'IsActive'         => $isActive,
                'IsDeleted'        => 0,
                'CreatedBy'        => $userUID,
                'UpdatedBy'        => $userUID,
            ]);
            if ($r->Error) throw new Exception('SubMenu insert failed: ' . $r->Message . ' (Name=' . $sm->Name . ')');
            $newSubUID                              = (int)$r->ID;
            $subMenuMap[(int)$sm->SectorSubMenuUID] = $newSubUID;
            $subMenuIsActive[$newSubUID]             = $isActive;
            $subMenuRoleMMMap[$newSubUID]            = $newRoleMMUID;
        }

        /* ── 10. Insert RoleSubMenusTbl ─────────────────────────────────────── */
        $sort = 1;
        foreach ($subMenuMap as $newSubUID) {
            $roleMMUID = $subMenuRoleMMMap[$newSubUID] ?? 0;
            $isActive  = $subMenuIsActive[$newSubUID]  ?? 0;
            $r = $this->dbwrite_model->insertData('UserRole', 'RoleSubMenusTbl', [
                'RoleUID'         => $roleUID,
                'RoleMainMenuUID' => $roleMMUID,
                'SubMenuUID'      => $newSubUID,
                'Sorting'         => $sort++,
                'CanView'         => 1,
                'CanCreate'       => 1,
                'CanEdit'         => 1,
                'CanDelete'       => 1,
                'IsActive'        => $isActive,
                'IsDeleted'       => 0,
            ]);
            if ($r->Error) throw new Exception('RoleSubMenu insert failed: ' . $r->Message . ' (SubMenuUID=' . $newSubUID . ')');
        }
    }

    /**
     * Fetch a sector plan row by its UID.
     * Returns SectorPlanUID, Price, DurationDays, PlanName, BillingCycle or null if not found.
     * @param int $sectorPlanUID
     * @returns object|null
     */
    public function getSectorPlan(int $sectorPlanUID): ?object {
        $this->ReadDb->db_debug = FALSE;
        return $this->ReadDb
            ->select('SPT.SectorPlanUID, SPT.Price, SPT.TaxableAmount, SPT.TaxAmount, SPT.TotalAmount, SPT.TaxRate, SPT.DurationDays, SP.PlanName, SP.BillingCycle')
            ->from('Billing.SectorPlanTbl AS SPT')
            ->join('Billing.SubscriptionPlansTbl AS SP', 'SP.PlanUID = SPT.PlanUID')
            ->where('SPT.SectorPlanUID', $sectorPlanUID)
            ->where('SPT.IsActive', 1)
            ->limit(1)
            ->get()->row() ?: null;
    }

    /* Fetch org subscription row with plan details for a given status.
     * Returns SectorPlanUID, Price, DurationDays, PlanName, BillingCycle or null.
     * @param int    $orgUID
     * @param string $status  e.g. 'PendingPayment', 'Expired', 'Active'
     * @returns object|null
     */
    public function getOrgPlanForPayment(int $orgUID, string $status): ?object {
        $this->ReadDb->db_debug = FALSE;
        return $this->ReadDb
            ->select('OS.OrgSubUID, OS.SectorPlanUID, OS.EndDate, SPT.Price, SPT.DurationDays, SP.PlanName, SP.BillingCycle')
            ->from('Billing.OrgSubscriptionTbl AS OS')
            ->join('Billing.SectorPlanTbl AS SPT', 'SPT.SectorPlanUID = OS.SectorPlanUID', 'left')
            ->join('Billing.SubscriptionPlansTbl AS SP', 'SP.PlanUID = SPT.PlanUID', 'left')
            ->where('OS.OrgUID', $orgUID)
            ->where('OS.Status', $status)
            ->order_by('OS.OrgSubUID', 'DESC')
            ->limit(1)
            ->get()->row() ?: null;
    }

    /* Fetch all active plans (used on the public signup page plan selector).
     * @returns array
     */
    public function getActivePlans(): array {
        $this->ReadDb->db_debug = FALSE;
        return $this->ReadDb
            ->select('SPT.SectorPlanUID, SPT.Price, SPT.DurationDays, SPT.MaxUsers, SPT.MaxBranches, SP.PlanName, SP.PlanCode, SP.BillingCycle')
            ->from('Billing.SectorPlanTbl AS SPT')
            ->join('Billing.SubscriptionPlansTbl AS SP', 'SP.PlanUID = SPT.PlanUID')
            ->where('SPT.IsActive', 1)
            ->order_by('SPT.Price', 'ASC')
            ->get()->result() ?: [];
    }

    /* Check whether an email address belongs to an existing org or active user.
     * Returns object with orgExists (bool) and userRow (?object).
     * @param string $email
     * @returns object
     */
    public function checkEmailExists(string $email): object {
        $this->ReadDb->db_debug = FALSE;
        $out           = new stdClass();
        $out->orgExists = $this->ReadDb
            ->where('EmailAddress', $email)
            ->where('IsDeleted', 0)
            ->count_all_results('Organisation.OrganisationTbl') > 0;
        $out->userRow  = $this->ReadDb
            ->where('EmailAddress', $email)
            ->where('IsDeleted', 0)
            ->where('IsActive', 1)
            ->where('HasLoginAccess', 1)
            ->limit(1)
            ->get('Users.UserTbl')->row() ?: null;
        return $out;
    }

    /* Fetch all active plans available for an org's sector (used on subscribe page).
     * @param int $orgUID
     * @returns array
     */
    public function getAvailablePlansForOrg(int $orgUID): array {
        $this->ReadDb->db_debug = FALSE;
        return $this->ReadDb
            ->select('SPT.SectorPlanUID, SPT.Price, SPT.DurationDays, SPT.MaxUsers, SPT.MaxBranches, SPT.TaxableAmount, SPT.TaxAmount, SPT.TotalAmount, SP.PlanName, SP.BillingCycle')
            ->from('Billing.SectorPlanTbl AS SPT')
            ->join('Billing.SubscriptionPlansTbl AS SP', 'SP.PlanUID = SPT.PlanUID')
            ->join('Organisation.OrganisationTbl AS O', 'O.SectorUID = SPT.SectorUID')
            ->where('O.OrgUID', $orgUID)
            ->where('SPT.IsActive', 1)
            ->order_by('SPT.Price', 'ASC')
            ->get()->result() ?: [];
    }

    /* Fetch the most recent non-cancelled OrgSubUID for an org.
     * Returns object with OrgSubUID or null.
     * @param int $orgUID
     * @returns object|null
     */
    public function getOrgSubUID(int $orgUID): ?object {
        $this->ReadDb->db_debug = FALSE;
        return $this->ReadDb
            ->select('OrgSubUID, OrderUID')
            ->from('Billing.OrgSubscriptionTbl')
            ->where('OrgUID', $orgUID)
            ->where_not_in('Status', ['Cancelled'])
            ->order_by('OrgSubUID', 'DESC')
            ->limit(1)
            ->get()->row() ?: null;
    }

    /**
     * Fetch the most recent paid OrderUID for a subscription row.
     * Used as fallback when OrgSubscriptionTbl.OrderUID is null.
     * @param int $orgSubUID
     * @returns int  0 if none found
     */
    public function getLatestPaidOrderUID(int $orgSubUID): int {
        $this->ReadDb->db_debug = FALSE;
        $row = $this->ReadDb
            ->select('OrderUID')
            ->from('Billing.SubscriptionOrdersTbl')
            ->where('OrgSubUID', $orgSubUID)
            ->where('Status', 'Paid')
            ->order_by('OrderUID', 'DESC')
            ->limit(1)
            ->get()->row();
        return $row ? (int)$row->OrderUID : 0;
    }

    /**
     * Returns the most recent Pending or Waived OrderUID for an org.
     * Used in changePlan() when OrgSubscriptionTbl.OrderUID is NULL (migration not yet run).
     * @param int $orgUID
     * @returns int  0 if none found
     */
    public function getSignupOrderUID(int $orgUID): int {
        $this->ReadDb->db_debug = FALSE;
        $row = $this->ReadDb->select('OrderUID')
            ->from('Billing.SubscriptionOrdersTbl')
            ->where('OrgUID', $orgUID)
            ->where_in('Status', ['Pending', 'Waived'])
            ->order_by('OrderUID', 'DESC')
            ->limit(1)
            ->get()->row();
        return $row ? (int)$row->OrderUID : 0;
    }

    /**
     * Returns true if a payment record already exists for the given order.
     * Used to prevent duplicate payment/invoice rows on repeated free-plan switches.
     * @param int $orderUID
     * @returns bool
     */
    public function orderHasPayment(int $orderUID): bool {
        $this->ReadDb->db_debug = FALSE;
        $row = $this->ReadDb->select('PaymentUID')
            ->from('Billing.SubscriptionPaymentsTbl')
            ->where('OrderUID', $orderUID)
            ->limit(1)
            ->get()->row();
        return (bool)$row;
    }

    /**
     * Insert SubscriptionPaymentsTbl + SubscriptionInvoicesTbl rows after a
     * successful Razorpay payment and optionally generate + upload the PDF invoice.
     * Non-fatal — errors are logged via notifyError() but do not throw.
     * @param int    $orgUID
     * @param int    $orderUID
     * @param object $plan          Row from getSectorPlan()
     * @param string $renewalType   'New' | 'Renewal' | 'Upgrade'
     * @param string $rpOrderId
     * @param string $rpPaymentId
     * @param string $rpSignature
     * @param string $paymentMode   Human-readable mode e.g. 'UPI - abc@upi', 'Card - Visa Credit'
     * @param string $now           gmdate('Y-m-d H:i:s')
     * @returns void
     */
    public function createPaymentAndInvoice(int $orgUID, int $orderUID, object $plan, string $renewalType, string $rpOrderId, string $rpPaymentId, string $rpSignature, string $paymentMode, string $now): void {
        log_message('error', '[BILLING] createPaymentAndInvoice called — orgUID=' . $orgUID . ' orderUID=' . $orderUID . ' renewalType=' . $renewalType . ' paymentMode=' . $paymentMode);
        if ($orderUID <= 0) {
            log_message('error', '[BILLING] createPaymentAndInvoice ABORTED — orderUID is 0');
            return;
        }

        try {
            /* 1. SubscriptionPaymentsTbl */
            $rPay = $this->dbwrite_model->insertData('Billing', 'SubscriptionPaymentsTbl', [
                'OrderUID'         => $orderUID,
                'FinancialYear'    => billing_fy('long'),
                'PaymentDate'      => $now,
                'Amount'           => (float)$plan->TotalAmount,
                'Mode'             => $paymentMode,
                'PaymentType'      => ($paymentMode === 'Free' ? 'Free' : 'Razorpay'),
                'PaymentID'        => $rpPaymentId,
                'GatewayOrderID'   => $rpOrderId,
                'GatewaySignature' => $rpSignature,
                'Status'           => 'Success',
            ]);
            log_message('error', '[BILLING] SubscriptionPaymentsTbl insert: Error=' . ($rPay->Error ? 'YES' : 'NO') . ' ID=' . ($rPay->ID ?? 'NULL') . ' Msg=' . ($rPay->Message ?? ''));

            /* 2. Org billing address for invoice */
            $this->ReadDb->db_debug = FALSE;
            $orgRow = $this->ReadDb
                ->select('O.Name, O.GSTIN, O.MobileNumber, O.EmailAddress,
                    A.Line1, A.Line2, A.CityText, A.StateText, A.Pincode')
                ->from('Organisation.OrganisationTbl AS O')
                ->join(
                    'Organisation.OrgAddressTbl AS A',
                    "A.OrgUID = O.OrgUID AND A.AddressType = 'Billing' AND A.IsDeleted = 0 AND A.IsActive = 1",
                    'left'
                )
                ->where('O.OrgUID', $orgUID)
                ->limit(1)
                ->get()->row();

            /* 3. Auto-sequence invoice number e.g. R2K-2627-001 */
            $prefix  = 'R2K-' . billing_fy('code') . '-';
            $lastRow = $this->ReadDb->select('MAX(InvoiceNumber) AS LastInv')
                ->from('Billing.SubscriptionInvoicesTbl')
                ->like('InvoiceNumber', $prefix, 'after')
                ->get()->row();
            $lastSeq = 0;
            if (!empty($lastRow->LastInv)) {
                $parts   = explode('-', $lastRow->LastInv);
                $lastSeq = (int)end($parts);
            }
            $invoiceNumber = $prefix . str_pad($lastSeq + 1, 3, '0', STR_PAD_LEFT);

            /* 4. Tax figures with 18% GST-inclusive fallback */
            $taxRate    = (float)((float)$plan->TaxRate    > 0 ? $plan->TaxRate    : 18.00);
            $totalAmt   = (float)$plan->TotalAmount;
            $taxableAmt = (float)((float)$plan->TaxableAmount > 0 ? $plan->TaxableAmount : round($totalAmt * 100 / (100 + $taxRate), 2));
            $taxAmt     = (float)((float)$plan->TaxAmount   > 0 ? $plan->TaxAmount   : round($totalAmt * $taxRate / (100 + $taxRate), 2));

            /* 5. SubscriptionInvoicesTbl */
            $rInv = $this->dbwrite_model->insertData('Billing', 'SubscriptionInvoicesTbl', [
                'OrderUID'      => $orderUID,
                'InvoiceNumber' => $invoiceNumber,
                'FinancialYear' => billing_fy('long'),
                'InvoiceDate'   => gmdate('Y-m-d'),
                'OrgName'       => $orgRow ? $orgRow->Name                    : '',
                'GSTIN'         => $orgRow ? ($orgRow->GSTIN        ?: null)  : null,
                'OrgAddress1'   => $orgRow ? ($orgRow->Line1        ?: null)  : null,
                'OrgAddress2'   => $orgRow ? ($orgRow->Line2        ?: null)  : null,
                'OrgCity'       => $orgRow ? ($orgRow->CityText     ?: null)  : null,
                'OrgState'      => $orgRow ? ($orgRow->StateText    ?: null)  : null,
                'OrgPincode'    => $orgRow ? ($orgRow->Pincode      ?: null)  : null,
                'OrgPhone'      => $orgRow ? ($orgRow->MobileNumber ?: null)  : null,
                'OrgEmail'      => $orgRow ? ($orgRow->EmailAddress ?: null)  : null,
                'TaxableAmount' => $taxableAmt,
                'TaxRate'       => $taxRate,
                'TaxAmount'     => $taxAmt,
                'TotalAmount'   => $totalAmt,
                'PDFPath'       => null,
            ]);
            log_message('error', '[BILLING] SubscriptionInvoicesTbl insert: Error=' . ($rInv->Error ? 'YES' : 'NO') . ' ID=' . ($rInv->ID ?? 'NULL') . ' InvoiceNumber=' . ($invoiceNumber ?? '') . ' Msg=' . ($rInv->Message ?? ''));
            if ($rInv->Error) return;
            $invoiceUID = (int)$rInv->ID;

            /* 6. Generate PDF and upload to R2 — non-fatal */
            $this->load->library('billinginvoice');
            $pdfUrl = $this->billinginvoice->generateAndUpload([
                'invoice_number' => $invoiceNumber,
                'invoice_date'   => gmdate('Y-m-d'),
                'financial_year' => billing_fy('long'),
                'org_name'       => $orgRow ? $orgRow->Name                   : '',
                'gstin'          => $orgRow ? ($orgRow->GSTIN        ?: '')   : '',
                'org_address1'   => $orgRow ? ($orgRow->Line1        ?: '')   : '',
                'org_address2'   => $orgRow ? ($orgRow->Line2        ?: '')   : '',
                'org_city'       => $orgRow ? ($orgRow->CityText     ?: '')   : '',
                'org_state'      => $orgRow ? ($orgRow->StateText    ?: '')   : '',
                'org_pincode'    => $orgRow ? ($orgRow->Pincode      ?: '')   : '',
                'org_phone'      => $orgRow ? ($orgRow->MobileNumber ?: '')   : '',
                'org_email'      => $orgRow ? ($orgRow->EmailAddress ?: '')   : '',
                'plan_name'      => $plan->PlanName,
                'billing_cycle'  => $plan->BillingCycle ?? '',
                'renewal_type'   => $renewalType,
                'taxable_amount' => $taxableAmt,
                'tax_rate'       => $taxRate,
                'tax_amount'     => $taxAmt,
                'total_amount'   => $totalAmt,
                'order_uid'      => $orderUID,
                'org_uid'        => $orgUID,
            ]);
            if ($pdfUrl) {
                $this->dbwrite_model->updateData('Billing', 'SubscriptionInvoicesTbl', ['PDFPath' => $pdfUrl], ['InvoiceUID' => $invoiceUID]);
            }

        } catch (Exception $e) {
            log_message('error', '[BILLING] createPaymentAndInvoice EXCEPTION: ' . $e->getMessage());
            notifyError('Signup_model::createPaymentAndInvoice', $e);
        }
    }

}
