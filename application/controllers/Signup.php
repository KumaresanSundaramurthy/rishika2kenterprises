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
            $this->load->model('signup_model');
            $plans = $this->signup_model->getActivePlans();
            $this->globalservice->sendJsonResponse(['Error' => false, 'Plans' => $plans]);
        } catch (Exception $e) {
            $this->globalservice->sendJsonResponse(['Error' => true, 'Plans' => []]);
        }
    }

    public function checkEmail(): void {
        try {
            $email = strtolower(trim($this->input->post('email') ?? ''));
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->globalservice->sendJsonResponse(['available' => false, 'message' => 'Invalid email']);
                return;
            }
            $this->load->model('signup_model');
            $taken = $this->signup_model->isEmailTaken($email);
            $this->globalservice->sendJsonResponse(['available' => !$taken]);
        } catch (Exception $e) {
            $this->globalservice->sendJsonResponse(['available' => false, 'message' => 'Error checking email']);
        }
    }

    public function checkGSTIN(): void {
        try {
            $gstin = strtoupper(trim($this->input->post('gstin') ?? ''));
            if (empty($gstin) || !preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/', $gstin)) {
                $this->globalservice->sendJsonResponse(['available' => false, 'message' => 'Invalid GSTIN format']);
                return;
            }
            $this->load->model('signup_model');
            $taken = $this->signup_model->isGSTINTaken($gstin);
            $this->globalservice->sendJsonResponse(['available' => !$taken]);
        } catch (Exception $e) {
            $this->globalservice->sendJsonResponse(['available' => false, 'message' => 'Error checking GSTIN']);
        }
    }

    public function checkMobile(): void {
        try {
            $mobile = preg_replace('/\D/', '', trim($this->input->post('mobile') ?? ''));
            if (empty($mobile) || strlen($mobile) !== 10) {
                $this->globalservice->sendJsonResponse(['available' => false, 'message' => 'Invalid mobile number']);
                return;
            }
            $this->load->model('signup_model');
            $taken = $this->signup_model->isMobileTaken($mobile);
            $this->globalservice->sendJsonResponse(['available' => !$taken]);
        } catch (Exception $e) {
            $this->globalservice->sendJsonResponse(['available' => false, 'message' => 'Error checking mobile']);
        }
    }

    public function getCitiesOfState(): void {
        try {
            $countryISO2 = strtoupper(trim($this->input->post('CountryISO2') ?? ''));
            $stateISO2   = strtoupper(trim($this->input->post('StateISO2')   ?? ''));
            if (!$countryISO2 || !$stateISO2) throw new Exception('Country and State codes are required.');
            $this->load->model('location_model');
            $result = $this->location_model->getCitiesOfStateFromDB($countryISO2, $stateISO2);
            $this->globalservice->sendJsonResponse([
                'Error' => $result->Error,
                'Data'  => $result->Error ? [] : $result->Data,
            ]);
        } catch (Exception $e) {
            $this->globalservice->sendJsonResponse(['Error' => true, 'Data' => []]);
        }
    }

    public function checkUsername(): void {
        try {
            $username = strtolower(trim($this->input->post('username') ?? ''));
            if (empty($username)) {
                $this->globalservice->sendJsonResponse(['available' => false]);
                return;
            }
            $this->load->model('signup_model');
            $taken = $this->signup_model->isUsernameTaken($username);
            $this->globalservice->sendJsonResponse(['available' => !$taken]);
        } catch (Exception $e) {
            $this->globalservice->sendJsonResponse(['available' => false]);
        }
    }

    public function googleAuth(): void {
        $this->EndReturnData->Error   = false;
        $this->EndReturnData->Message = '';
        try {

            $idToken = trim($this->input->post('credential') ?? '');
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

            $emailCheck = $this->signup_model->checkEmailExists($email);

            if ($emailCheck->orgExists || $emailCheck->userRow) {
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

                $this->_createLoginSession($user, 'google', (bool)($result->IsPaidPlan ?? false));

                $this->EndReturnData->Redirect = ($result->IsPaidPlan ?? false)
                    ? base_url('subscribe')
                    : base_url('onboarding');
            }

        } catch (ValidationException $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Signup::googleAuth', $e);
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = 'Something went wrong. Please try again.';
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    /**
     * Build JWT + Redis session for the given user object (same logic as Login::doLoginForm).
     * @param object $user         Row from getUserByEmailOrUsername()
     * @param string $provider     'google' | 'local'
     * @param bool   $isPaidPlan   True when signup chose a paid plan — forces Status=PendingPayment
     *                             in the Redis payload so the middleware gate fires immediately,
     *                             bypassing any ReadDb replication lag on the fresh subscription row.
     */
    private function _createLoginSession(object $user, string $provider, bool $isPaidPlan = false): void {
        $this->load->model('login_model');
        $this->load->model('dbwrite_model');

        $jwtPayload = $this->login_model->formatJWTPayload($user);
        if ($jwtPayload->Error) throw new Exception('JWT build failed: ' . $jwtPayload->Message);

        if ($isPaidPlan && isset($jwtPayload->JWTData['Subscription'])) {
            $jwtPayload->JWTData['Subscription']->Status = 'PendingPayment';
        }

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
                $this->globalservice->sendJsonResponse($this->EndReturnData);
                return;
            }

            // Auto-login after registration
            $this->load->model('user_model');
            $userData = $this->user_model->getUserByEmailOrUsername(strtolower(trim($post['OrgEmail'])));
            if ($userData->Error || empty($userData->Data)) {
                $this->EndReturnData->Error   = true;
                $this->EndReturnData->Message = 'Account created but auto-login failed. Please sign in manually.';
                $this->globalservice->sendJsonResponse($this->EndReturnData);
                return;
            }

            $this->_createLoginSession($userData->Data[0], 'local', (bool)$result->IsPaidPlan);

            $this->EndReturnData->Error    = false;
            $this->EndReturnData->Message  = 'Organisation registered successfully!';
            $this->EndReturnData->Redirect = $result->IsPaidPlan ? base_url('subscribe') : base_url('dashboard');
            $this->globalservice->sendJsonResponse($this->EndReturnData);

        } catch (ValidationException $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
            $this->globalservice->sendJsonResponse($this->EndReturnData);
        } catch (Exception $e) {
            notifyError('Signup::doSignup', $e);
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = 'Registration failed. Please try again or contact support.';
            $this->globalservice->sendJsonResponse($this->EndReturnData);
        }
    }

}
