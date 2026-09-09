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

    public function doSignup(): void {
        try {
            $post = $this->input->post();

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

            $this->load->model('signup_model');

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

            $this->EndReturnData->Error   = false;
            $this->EndReturnData->Message = 'Organisation registered successfully!';
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
