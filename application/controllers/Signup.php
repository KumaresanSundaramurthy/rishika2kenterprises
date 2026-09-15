<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Signup extends CI_Controller {

    protected object $EndReturnData;

    public function __construct() {
        parent::__construct();
        $this->EndReturnData = new stdClass();
    }

    public function index(): void {
        $this->load->helper('auth');
        if (is_authenticated()) {
            redirect('dashboard', 'refresh');
            return;
        }
        $this->load->model('signup_model');
        $pageData['timezones'] = $this->signup_model->getTimezones();
        $this->load->view('signup/view', $pageData);
    }

    public function getPlans(): void {
        try {
            $readDb = $this->load->database('ReadDB', TRUE);
            $readDb->db_debug = FALSE;
            $plans = $readDb
                ->select('SPT.SectorPlanUID, SPT.Price, SPT.DurationDays, SPT.MaxUsers, SPT.MaxBranches, SP.PlanName, SP.PlanCode, SP.BillingCycle')
                ->from('Billing.SectorPlanTbl AS SPT')
                ->join('Billing.SubscriptionPlansTbl AS SP', 'SP.PlanUID = SPT.PlanUID')
                ->where('SPT.IsActive', 1)
                ->order_by('SPT.Price', 'ASC')
                ->get()->result();
            echo json_encode(['Error' => false, 'Plans' => $plans ?: []]);
        } catch (Exception $e) {
            echo json_encode(['Error' => true, 'Plans' => []]);
        }
    }

    public function checkEmail(): void {
        try {
            $email = strtolower(trim($this->input->post('email') ?? ''));
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['available' => false, 'message' => 'Invalid email']);
                return;
            }
            $this->load->model('signup_model');
            $taken = $this->signup_model->isEmailTaken($email);
            echo json_encode(['available' => !$taken]);
        } catch (Exception $e) {
            echo json_encode(['available' => false, 'message' => 'Error checking email']);
        }
    }

    public function checkGSTIN(): void {
        try {
            $gstin = strtoupper(trim($this->input->post('gstin') ?? ''));
            if (empty($gstin) || !preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/', $gstin)) {
                echo json_encode(['available' => false, 'message' => 'Invalid GSTIN format']);
                return;
            }
            $this->load->model('signup_model');
            $taken = $this->signup_model->isGSTINTaken($gstin);
            echo json_encode(['available' => !$taken]);
        } catch (Exception $e) {
            echo json_encode(['available' => false, 'message' => 'Error checking GSTIN']);
        }
    }

    public function checkMobile(): void {
        try {
            $mobile = preg_replace('/\D/', '', trim($this->input->post('mobile') ?? ''));
            if (empty($mobile) || strlen($mobile) !== 10) {
                echo json_encode(['available' => false, 'message' => 'Invalid mobile number']);
                return;
            }
            $this->load->model('signup_model');
            $taken = $this->signup_model->isMobileTaken($mobile);
            echo json_encode(['available' => !$taken]);
        } catch (Exception $e) {
            echo json_encode(['available' => false, 'message' => 'Error checking mobile']);
        }
    }

    public function checkUsername(): void {
        try {
            $username = strtolower(trim($this->input->post('username') ?? ''));
            if (empty($username)) {
                echo json_encode(['available' => false]);
                return;
            }
            $this->load->model('signup_model');
            $taken = $this->signup_model->isUsernameTaken($username);
            echo json_encode(['available' => !$taken]);
        } catch (Exception $e) {
            echo json_encode(['available' => false]);
        }
    }

    public function googleAuth(): void {
        header('Content-Type: application/json');
        $this->EndReturnData->Error   = false;
        $this->EndReturnData->Message = '';
        try {

            $idToken  = trim($this->input->post('credential') ?? '');
            if (empty($idToken)) throw new ValidationException('No credential received.');

            /* ── Verify token with Google ────────────────────────────────── */
            $ch = curl_init('https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($idToken));
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10, CURLOPT_SSL_VERIFYPEER => true]);
            $raw      = curl_exec($ch);
            $curlErr  = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($curlErr || $httpCode !== 200) throw new Exception('Google token verification failed.');

            $g = json_decode($raw, true);
            if (empty($g['email']) || empty($g['sub'])) throw new ValidationException('Incomplete Google profile.');

            $clientId = getenv('GOOGLE_CLIENT_ID');
            if (!empty($clientId) && ($g['aud'] ?? '') !== $clientId) throw new ValidationException('Token audience mismatch.');

            $email = strtolower(trim($g['email']));

            /* ── Check if email already registered ───────────────────────── */
            $this->load->model('signup_model');
            $this->load->model('user_model');

            $ReadDb = $this->load->database('ReadDB', TRUE);
            $ReadDb->db_debug = FALSE;

            $orgExists = $ReadDb->where('EmailAddress', $email)->where('IsDeleted', 0)->count_all_results('Organisation.OrganisationTbl') > 0;
            $userRow   = $ReadDb->where('EmailAddress', $email)->where('IsDeleted', 0)->where('IsActive', 1)->where('HasLoginAccess', 1)->limit(1)->get('Users.UserTbl')->row();

            if ($orgExists || $userRow) {
                /* ── Existing account — log them in ──────────────────────── */
                $userData = $this->user_model->getUserByEmailOrUsername($email);
                if ($userData->Error || empty($userData->Data)) throw new ValidationException('Unable to load account.');
                $user = $userData->Data[0];

                if ($user->IsLocked == 1) throw new ValidationException('Account is locked. Contact your administrator.');
                if (!empty($user->LoginExpiryDateTime) && strtotime($user->LoginExpiryDateTime) < time()) {
                    $expiryDate = date('d M Y', strtotime($user->LoginExpiryDateTime));
                    throw new ValidationException('Your portal access expired on ' . $expiryDate . '. Please contact your administrator.');
                }

                $this->load->library('subscription');
                $sub = $this->subscription->checkSubscription($user->UserUID);
                if (!$sub->isValid) throw new ValidationException($sub->message);

                $this->_createLoginSession($user, 'google');
                $this->EndReturnData->Redirect = base_url('dashboard');
            } else {
                /* ── New account — create org + user ─────────────────────── */
                $sectorPlanUID = (int)($this->input->post('SectorPlanUID') ?? 0);
                $result = $this->signup_model->registerOrganisationViaGoogle($g, $sectorPlanUID);
                if ($result->Error) throw new Exception('Registration failed. Please try again.');

                $userData = $this->user_model->getUserByEmailOrUsername($email);
                if ($userData->Error || empty($userData->Data)) throw new Exception('Account created but login failed.');
                $user = $userData->Data[0];

                $this->_createLoginSession($user, 'google');

                if ($result->IsPaidPlan ?? false) {
                    $this->EndReturnData->Redirect = base_url('signup/payment');
                } else {
                    $this->EndReturnData->Redirect = base_url('onboarding');
                }
            }

        } catch (ValidationException $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Signup::googleAuth', $e);
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = 'Something went wrong. Please try again.';
        }

        echo json_encode($this->EndReturnData);
    }

    /**
     * Build JWT + Redis session for the given user object (same logic as Login::doLoginForm).
     * @param object $user   Row from getUserByEmailOrUsername()
     * @param string $provider 'google' | 'local'
     */
    private function _createLoginSession(object $user, string $provider): void {
        $this->load->model('login_model');
        $this->load->model('dbwrite_model');

        $jwtPayload = $this->login_model->formatJWTPayload($user);
        if ($jwtPayload->Error) throw new Exception('JWT build failed: ' . $jwtPayload->Message);

        $newPayload   = clone $jwtPayload;
        $orgToken     = $newPayload->JWTData['Org']['OrgToken'] ?? '';
        $sessionToken = bin2hex(random_bytes(32));
        $jwtPayload->JWTData['User']['SessionToken'] = $sessionToken;
        $jwtPayload->JWTData['User']['auditId']      = 0;

        $jwtResult = $this->login_model->setJwtToken($user, $jwtPayload);
        if ($jwtResult->Error) throw new Exception('JWT sign failed: ' . $jwtResult->Message);

        $this->dbwrite_model->updateData('Users', 'UserTbl', [
            'LastLogin'           => date('Y-m-d H:i:s'),
            'CurrentSessionToken' => $sessionToken,
            'LastLoginOn'         => date('Y-m-d H:i:s'),
            'LastLoginIP'         => $this->input->ip_address(),
            'LastLoginDevice'     => $provider . ' oauth',
        ], ['UserUID' => $user->UserUID]);

        $loginExpiry = (int) getenv('LOGIN_EXPIRE_SECS');
        $userUID     = $user->UserUID;

        $this->redisservice->setCache($this->redisservice->envKey('UserActiveSession_' . $userUID), $sessionToken, $loginExpiry);
        $this->redisservice->setUserCache('menus',       $userUID, $newPayload->JWTData['UserMainModule'] ?? [], $loginExpiry, $orgToken);
        $this->redisservice->setUserCache('submenus',    $userUID, $newPayload->JWTData['UserSubModule']  ?? [], $loginExpiry, $orgToken);
        $this->redisservice->setUserCache('modules',     $userUID, $newPayload->JWTData['ModuleInfo']     ?? [], $loginExpiry, $orgToken);
        $this->redisservice->setUserCache('permissions', $userUID, $newPayload->JWTData['Permissions']    ?? [], $loginExpiry, $orgToken);
        $this->redisservice->setUserCache('userinfo',    $userUID, $user,                                        $loginExpiry, $orgToken);
    }

    public function doSignup(): void {
        try {
            $post = $this->input->post();
            $this->load->model('signup_model');

            $required = [
                'OrgName'         => 'Organisation name',
                'ShortCode'       => 'Short code',
                'OrgMobile'       => 'Mobile number',
                'OrgEmail'        => 'Email address',
                'StateCode'       => 'State',
                'StateName'       => 'State',
                'TimezoneUID'     => 'Timezone',
                'AdminFirstName'  => 'First name',
                'AdminUsername'   => 'Username',
                'AdminPassword'   => 'Password',
                'ConfirmPassword' => 'Confirm password',
            ];

            foreach ($required as $field => $label) {
                if (empty(trim($post[$field] ?? ''))) {
                    throw new ValidationException("{$label} is required.");
                }
            }

            $shortCode = strtoupper(trim($post['ShortCode']));
            if (!preg_match('/^[A-Z]{3}$/', $shortCode)) {
                throw new ValidationException('Short code must be exactly 3 uppercase letters (A–Z).');
            }

            if (!filter_var(trim($post['OrgEmail']), FILTER_VALIDATE_EMAIL)) {
                throw new ValidationException('Please enter a valid email address.');
            }

            if (!preg_match('/^\d{10}$/', preg_replace('/\D/', '', $post['OrgMobile']))) {
                throw new ValidationException('Mobile number must be 10 digits.');
            }

            if (strlen($post['AdminPassword']) < 8) {
                throw new ValidationException('Password must be at least 8 characters.');
            }

            if ($post['AdminPassword'] !== $post['ConfirmPassword']) {
                throw new ValidationException('Passwords do not match.');
            }

            if (!empty($post['GSTIN'])) {
                $gstin = strtoupper(trim($post['GSTIN']));
                if (!preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/', $gstin)) {
                    throw new ValidationException('GSTIN format is invalid.');
                }
                if ($this->signup_model->isGSTINTaken($gstin)) {
                    throw new ValidationException('This GSTIN is already registered.');
                }
            }

            if ($this->signup_model->isEmailTaken(trim($post['OrgEmail']))) {
                throw new ValidationException('This email address is already registered.');
            }

            $mobile = preg_replace('/\D/', '', trim($post['OrgMobile']));
            if ($this->signup_model->isMobileTaken($mobile)) {
                throw new ValidationException('This mobile number is already registered.');
            }

            if ($this->signup_model->isUsernameTaken(trim($post['AdminUsername']))) {
                throw new ValidationException('This username is already taken. Please choose another.');
            }

            $result = $this->signup_model->registerOrganisation($post);

            if ($result->Error) {
                // Model already called notifyError(); don't re-throw or we'd notify twice.
                $this->EndReturnData->Error   = true;
                $this->EndReturnData->Message = 'Registration failed. Please try again or contact support.';
                echo json_encode($this->EndReturnData);
                return;
            }

            // Auto-login after registration
            $this->load->model('user_model');
            $userData = $this->user_model->getUserByEmailOrUsername(strtolower(trim($post['OrgEmail'])));
            if ($userData->Error || empty($userData->Data)) {
                $this->EndReturnData->Error   = true;
                $this->EndReturnData->Message = 'Account created but auto-login failed. Please sign in manually.';
                echo json_encode($this->EndReturnData);
                return;
            }

            $this->_createLoginSession($userData->Data[0], 'local');

            $redirect = $result->IsPaidPlan
                ? base_url('signup/payment')
                : base_url('dashboard');

            $this->EndReturnData->Error    = false;
            $this->EndReturnData->Message  = 'Organisation registered successfully!';
            $this->EndReturnData->Redirect = $redirect;
            echo json_encode($this->EndReturnData);

        } catch (ValidationException $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
            echo json_encode($this->EndReturnData);
        } catch (Exception $e) {
            notifyError('Signup::doSignup', $e);
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = 'Registration failed. Please try again or contact support.';
            echo json_encode($this->EndReturnData);
        }
    }

}
