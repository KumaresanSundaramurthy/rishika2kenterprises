<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Subscription extends CI_Controller {

    public $pageData = array();
    private $EndReturnData;

    public function __construct() {
        parent::__construct();
        $this->load->library('subscription');
    }

    /**
     * Subscription expired page
     */
    public function expired() {
        try {
            $userUID = $this->pageData['JwtData']->User->UserUID ?? null;
            
            if (!$userUID) {
                redirect('login');
                return;
            }

            // Get subscription info
            $subscriptionInfo = $this->subscription->checkSubscription($userUID);
            $this->pageData['SubscriptionInfo'] = $subscriptionInfo;

            // Get available plans
            $this->pageData['Plans'] = $this->subscription->getSubscriptionPlans();

            // Get user info
            $this->load->model('users_model');
            $this->pageData['UserInfo'] = $this->users_model->getUserById($userUID);

            $this->load->view('subscription/expired', $this->pageData);

        } catch (Exception $e) {
            show_error('Error loading subscription page: ' . $e->getMessage());
        }
    }

    /**
     * Subscription plans page
     */
    public function plans() {
        try {
            $this->pageData['Plans'] = $this->subscription->getSubscriptionPlans();
            
            $userUID = $this->pageData['JwtData']->User->UserUID ?? null;
            if ($userUID) {
                $this->pageData['SubscriptionInfo'] = $this->subscription->checkSubscription($userUID);
            }

            $this->load->view('subscription/plans', $this->pageData);

        } catch (Exception $e) {
            show_error('Error loading plans: ' . $e->getMessage());
        }
    }

    /**
     * Extend subscription (Admin only)
     */
    public function extend() {
        $this->EndReturnData = new stdClass();
        try {
            // Check if user is admin
            if (!isset($this->pageData['JwtData']->User->IsAdmin) || $this->pageData['JwtData']->User->IsAdmin != 1) {
                throw new Exception('Unauthorized access');
            }

            $userUID = (int)$this->input->post('UserUID');
            $days = (int)$this->input->post('Days');
            $planCode = $this->input->post('PlanCode');

            if (!$userUID || !$days) {
                throw new Exception('Invalid parameters');
            }

            $result = $this->subscription->extendSubscription($userUID, $days, $planCode);

            $this->EndReturnData->Error = !$result['success'];
            $this->EndReturnData->Message = $result['message'];
            if ($result['success']) {
                $this->EndReturnData->NewEndDate = $result['newEndDate'];
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error = true;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    /**
     * Activate subscription with payment
     */
    public function activate() {
        $this->EndReturnData = new stdClass();
        try {
            $userUID = $this->pageData['JwtData']->User->UserUID ?? null;
            
            if (!$userUID) {
                throw new Exception('User not logged in');
            }

            $planCode = $this->input->post('PlanCode');
            $paymentMethod = $this->input->post('PaymentMethod');
            $transactionId = $this->input->post('TransactionID');

            if (!$planCode) {
                throw new Exception('Please select a plan');
            }

            // TODO: Integrate with payment gateway here
            // For now, we'll assume payment is successful

            $paymentData = [
                'status' => 'Paid',
                'method' => $paymentMethod,
                'transactionId' => $transactionId
            ];

            $result = $this->subscription->activateSubscription($userUID, $planCode, $paymentData);

            $this->EndReturnData->Error = !$result['success'];
            $this->EndReturnData->Message = $result['message'];
            
            if ($result['success']) {
                $this->EndReturnData->EndDate = $result['endDate'];
                
                // Redirect to intended URL or dashboard
                $intendedUrl = $this->session->userdata('intended_url');
                $this->session->unset_userdata('intended_url');
                $this->EndReturnData->RedirectUrl = $intendedUrl ?: '/dashboard';
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error = true;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    /**
     * Get subscription status (AJAX)
     */
    public function getStatus() {
        $this->EndReturnData = new stdClass();
        try {
            $userUID = $this->pageData['JwtData']->User->UserUID ?? null;
            
            if (!$userUID) {
                throw new Exception('User not logged in');
            }

            $subscriptionInfo = $this->subscription->checkSubscription($userUID);

            $this->EndReturnData->Error = false;
            $this->EndReturnData->Status = $subscriptionInfo->status;
            $this->EndReturnData->IsValid = $subscriptionInfo->isValid;
            $this->EndReturnData->DaysRemaining = $subscriptionInfo->daysRemaining;
            $this->EndReturnData->Message = $subscriptionInfo->message;
            $this->EndReturnData->InGracePeriod = $subscriptionInfo->inGracePeriod;
            $this->EndReturnData->Plan = $subscriptionInfo->plan ?? 'Unknown';

        } catch (Exception $e) {
            $this->EndReturnData->Error = true;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    /**
     * Subscription history
     */
    public function history() {
        try {
            $userUID = $this->pageData['JwtData']->User->UserUID ?? null;
            
            if (!$userUID) {
                redirect('login');
                return;
            }

            // Get subscription history
            $this->db->select('*');
            $this->db->from('Users.SubscriptionHistoryTbl');
            $this->db->where('UserUID', $userUID);
            $this->db->order_by('CreatedOn', 'DESC');
            $this->db->limit(50);
            $query = $this->db->get();

            $this->pageData['History'] = $query->result();
            $this->pageData['SubscriptionInfo'] = $this->subscription->checkSubscription($userUID);

            $this->load->view('subscription/history', $this->pageData);

        } catch (Exception $e) {
            show_error('Error loading subscription history: ' . $e->getMessage());
        }
    }
}