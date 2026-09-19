<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Onboarding — mandatory profile completion for Google signup users.
 * Triggered by Middleware when Org.IsOnboardingComplete === 0.
 * Extends MY_Controller so JWT/Redis session data is fully available.
 */
class Onboarding extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('dbwrite_model');
    }

    /* ── Onboarding page ─────────────────────────────────────────────── */

    public function index(): void {
        $jwtData = $this->pageData['JwtData'] ?? null;

        /* If already complete, send to dashboard */
        if ($jwtData && (int)($jwtData->Org->IsOnboardingComplete ?? 1) === 1) {
            redirect('dashboard', 'refresh');
            return;
        }

        $shortCode  = '';
        $orgUID     = (int)($jwtData->Org->OrgUID ?? 0);

        if ($orgUID > 0) {
            try {
                $readDb = $this->load->database('ReadDB', TRUE);
                $readDb->db_debug = FALSE;
                $row = $readDb->select('ShortCode')
                    ->from('Organisation.OrganisationTbl')
                    ->where('OrgUID', $orgUID)
                    ->limit(1)
                    ->get();
                if ($row && $row->num_rows() > 0) {
                    $shortCode = $row->row()->ShortCode ?? '';
                }
            } catch (Exception $e) {}
        }

        $this->load->view('onboarding/view', [
            'jwtData'   => $jwtData,
            'shortCode' => $shortCode,
        ]);
    }

    /* ── AJAX: states (called before onboarding is complete, so lives here) ───── */

    public function getStates(): void {
        $out = new stdClass();
        try {
            $readDb = $this->load->database('ReadDB', TRUE);
            $readDb->db_debug = FALSE;
            $rows = $readDb->select('name, iso2')
                ->from('Global.StatesTbl')
                ->where('country_code', 'IN')
                ->where('flag', 1)
                ->order_by('name', 'ASC')
                ->get();
            $out->Error = false;
            $out->Data  = ($rows && $rows->num_rows() > 0) ? $rows->result() : [];
        } catch (Exception $e) {
            notifyError('Onboarding::getStates', $e);
            $out->Error   = true;
            $out->Message = 'Could not load states.';
        }
        $this->output
            ->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($out))
            ->_display();
        exit;
    }

    /* ── AJAX: timezones (called before onboarding is complete, so lives here) ── */

    public function getTimezones(): void {
        $out = new stdClass();
        try {
            $this->load->model('global_model');
            $result       = $this->global_model->getTimezoneDetails([]);
            $out->Error   = $result->Error;
            $out->Data    = ($result->Error === FALSE) ? $result->Data : [];
            if ($result->Error) $out->Message = $result->Message ?? '';
        } catch (Exception $e) {
            notifyError('Onboarding::getTimezones', $e);
            $out->Error   = true;
            $out->Message = 'Could not load timezones.';
        }
        $this->output
            ->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($out))
            ->_display();
        exit;
    }

    /* ── AJAX: save profile ───────────────────────────────────────────── */

    public function completeProfile(): void {
        $out = new stdClass();
        try {
            $jwtData = $this->pageData['JwtData']  ?? null;
            $jwtKey  = $this->pageData['JwtUserKey'] ?? '';

            if (!$jwtData || !$jwtKey) throw new Exception('Session error. Please log in again.');

            $orgUID = (int)($jwtData->Org->OrgUID ?? 0);
            if ($orgUID <= 0) throw new Exception('Organisation not found in session.');

            /* ── Collect & validate ──────────────────────────────────── */
            $orgName     = trim($this->input->post('org_name')    ?: '');
            $brandName   = trim($this->input->post('brand_name')  ?: '');
            $shortCode   = strtoupper(trim($this->input->post('short_code') ?: ''));
            $mobile      = trim($this->input->post('mobile')      ?: '');
            $stateVal    = trim($this->input->post('state')       ?: '');
            $timezoneUID = (int)($this->input->post('timezone_uid') ?: 181);
            $gstin       = strtoupper(trim($this->input->post('gstin') ?: ''));

            if (empty($orgName))  throw new ValidationException('Organisation name is required.');
            if (strlen($orgName) < 2) throw new ValidationException('Organisation name must be at least 2 characters.');

            if (empty($brandName)) throw new ValidationException('Brand name is required.');
            if (strlen($brandName) < 2) throw new ValidationException('Brand name must be at least 2 characters.');

            if (empty($shortCode) || strlen($shortCode) !== 3 || !ctype_alnum($shortCode)) {
                throw new ValidationException('Short code must be exactly 3 alphanumeric characters (e.g. R2K).');
            }

            $mobile = preg_replace('/\D/', '', $mobile);
            if (empty($mobile) || strlen($mobile) !== 10) {
                throw new ValidationException('Enter a valid 10-digit mobile number.');
            }

            if (empty($stateVal)) throw new ValidationException('Please select a state.');
            $stateParts = explode('|', $stateVal, 2);
            $stateCode  = $stateParts[0] ?? '';
            $stateName  = $stateParts[1] ?? '';
            if (empty($stateCode) || empty($stateName)) throw new ValidationException('Invalid state selected.');

            if ($timezoneUID <= 0) $timezoneUID = 181;

            if (!empty($gstin) && !preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/', $gstin)) {
                throw new ValidationException('Invalid GSTIN format (e.g. 22AAAAA0000A1Z5).');
            }

            /* ── Uniqueness checks ───────────────────────────────────── */
            $readDb = $this->load->database('ReadDB', TRUE);
            $readDb->db_debug = FALSE;

            $mobileExists = $readDb->where('MobileNumber', $mobile)
                ->where('OrgUID !=', $orgUID)
                ->where('IsDeleted', 0)
                ->count_all_results('Organisation.OrganisationTbl');
            if ($mobileExists > 0) {
                throw new ValidationException('This mobile number is already registered with another account.');
            }

            if (!empty($gstin)) {
                $gstinExists = $readDb->where('GSTIN', $gstin)
                    ->where('OrgUID !=', $orgUID)
                    ->where('IsDeleted', 0)
                    ->count_all_results('Organisation.OrganisationTbl');
                if ($gstinExists > 0) {
                    throw new ValidationException('This GSTIN is already registered with another account.');
                }
            }

            /* ── Server-side GSTIN validation ───────────────────────── */
            $gstinValidated = 0;
            $panNumber      = null;
            $addrLine1      = null;
            $addrLine2      = null;
            $addrCity       = null;
            $addrPincode    = null;
            $addrStateText  = null;

            if (!empty($gstin)) {
                $this->load->library('curlservice');
                $apiResp = $this->curlservice->retrieve(
                    'https://gstverify.co.in/api/v1/verify/' . urlencode($gstin),
                    'GET',
                    null,
                    ['Accept: application/json', 'X-API-Key: ' . getenv('GSTIN_API_KEY')]
                );

                if (!$apiResp || $apiResp->Error) {
                    throw new ValidationException('GSTIN verification failed. Please try again or leave the field empty to skip.');
                }

                $apiData = is_array($apiResp->Data)
                    ? $apiResp->Data
                    : json_decode(json_encode($apiResp->Data ?? []), true);

                if (empty($apiData) || ($apiData['success'] ?? false) !== true) {
                    $apiMsg = $apiData['message'] ?? $apiData['error'] ?? $apiData['msg'] ?? '';
                    throw new ValidationException($apiMsg ?: 'GSTIN not found or invalid. Please verify the number.');
                }

                $d       = $apiData['data'] ?? [];
                $rawAddr = trim($d['address'] ?? '');
                $addrLine1 = $rawAddr;

                if ($rawAddr !== '') {
                    $dashPos = mb_strpos($rawAddr, '—');
                    if ($dashPos !== false) {
                        $leftPart    = trim(mb_substr($rawAddr, 0, $dashPos));
                        $addrPincode = trim(preg_replace('/\D/', '', mb_substr($rawAddr, $dashPos + 1))) ?: null;
                        $commaPos    = strrpos($leftPart, ',');
                        if ($commaPos !== false) {
                            $addrCity  = trim(substr($leftPart, $commaPos + 1)) ?: null;
                            $addrLine1 = trim(substr($leftPart, 0, $commaPos));
                        } else {
                            $addrLine1 = $leftPart;
                        }
                    }
                }

                $gstinValidated = 1;
                $panNumber      = $d['pan']   ?? null;
                $addrStateText  = $d['state'] ?? null;
                $addrLine1      = $addrLine1 ?: null;
            }

            /* ── Persist to DB ───────────────────────────────────────── */
            $userUID = (int)($jwtData->User->UserUID ?? 0);

            $writeDb = $this->dbwrite_model->getWriteDb();
            $writeDb->db_debug = FALSE;
            $updated = $writeDb->where('OrgUID', $orgUID)->update('Organisation.OrganisationTbl', [
                'Name'                 => $orgName,
                'BrandName'            => $brandName,
                'ShortCode'            => $shortCode,
                'MobileNumber'         => $mobile,
                'StateCode'            => $stateCode,
                'StateName'            => $stateName,
                'TimezoneUID'          => $timezoneUID,
                'GSTIN'                => $gstin ?: null,
                'GSTINValidation'      => $gstinValidated,
                'PANNumber'            => $panNumber,
                'IsOnboardingComplete' => 1,
                'UpdatedBy'            => $userUID,
            ]);

            if (!$updated) {
                $dbErr = $writeDb->error();
                throw new Exception('DB update failed: ' . ($dbErr['message'] ?? 'Unknown error'));
            }

            /* ── Billing address — from GSTIN or manual entry ───────────── */
            if (!$gstinValidated) {
                $addrLine1     = trim($this->input->post('addr_line1')   ?: '');
                $addrLine2     = trim($this->input->post('addr_line2')   ?: '') ?: null;
                $addrCity      = trim($this->input->post('addr_city')    ?: '') ?: null;
                $addrPincode   = trim($this->input->post('addr_pincode') ?: '') ?: null;
                $addrStateText = $stateName ?: null;
            }

            if (!empty($addrLine1)) {
                try {
                    $this->dbwrite_model->insertData('Organisation', 'OrgAddressTbl', [
                        'OrgUID'      => $orgUID,
                        'AddressType' => 'Billing',
                        'Line1'       => $addrLine1,
                        'Line2'       => $addrLine2,
                        'Pincode'     => $addrPincode,
                        'CityText'    => $addrCity,
                        'StateText'   => $addrStateText,
                        'IsActive'    => 1,
                        'IsDeleted'   => 0,
                        'CreatedBy'   => $userUID,
                        'UpdatedBy'   => $userUID,
                    ]);
                } catch (Exception $_ae) {}
            }

            /* ── Update default branch GSTIN + state ────────────────── */
            if (!empty($gstin) && $gstinValidated) {
                $writeDb->where('OrgUID', $orgUID)
                        ->where('IsHeadOffice', 1)
                        ->update('Organisation.BranchesTbl', [
                            'GSTIN'     => $gstin,
                            'StateText' => $stateName,
                            'UpdatedBy' => $userUID,
                        ]);
            }

            /* ── Seed org-users cache (may be missing for new Google signups) ── */
            $orgUsersKey = $this->redisservice->orgKey('org-users');
            $existing    = $this->redisservice->getCache($orgUsersKey);
            if ($existing->Error || empty($existing->Value)) {
                $this->load->model('users_model');
                $orgUsers    = $this->users_model->getOrgUsersForCache($orgUID);
                $loginExpiry = (int)getenv('LOGIN_EXPIRE_SECS') ?: 86400;
                $this->redisservice->setCache($orgUsersKey, $orgUsers, $loginExpiry);
            }

            /* ── Refresh Redis session so Middleware sees the new flag ── */
            $cached = $this->redisservice->getCache($jwtKey);
            if (!$cached->Error && $cached->Value !== null) {
                $sessionData = $cached->Value;
                $sessionData->Org->IsOnboardingComplete = 1;
                $sessionData->Org->OrgName              = $orgName;
                $sessionData->Org->OrgMobile            = $mobile;
                $sessionData->Org->StateCode            = $stateCode;
                $sessionData->Org->StateName            = $stateName;
                $loginExpiry = (int)getenv('LOGIN_EXPIRE_SECS') ?: 86400;
                $this->redisservice->setCache($jwtKey, $sessionData, $loginExpiry);
            }

            $out->Error   = false;
            $out->Message = 'Profile completed successfully.';

        } catch (ValidationException $e) {
            $out->Error   = true;
            $out->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Onboarding::completeProfile', $e);
            $out->Error   = true;
            $out->Message = 'Something went wrong. Please try again.';
        }

        $this->output
            ->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($out))
            ->_display();
        exit;
    }

}
