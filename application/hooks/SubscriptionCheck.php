<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Subscription Check Hook
 * Validates user subscription on every request
 */
class SubscriptionCheck {

    protected $CI;
    
    // Controllers that don't require subscription check
    private $excludedControllers = [
        'login',
        'logout',
        'register',
        'forgotpassword',
        'resetpassword',
        'subscription',
        'payment',
        'webhook'
    ];

    // Methods that don't require subscription check
    private $excludedMethods = [
        'login',
        'logout',
        'register',
        'verify',
        'activate'
    ];

    public function __construct() {
        $this->CI =& get_instance();
    }

    /**
     * Check subscription before controller execution
     */
    public function checkSubscription() {
        // Get current controller and method
        $controller = strtolower($this->CI->router->fetch_class());
        $method = strtolower($this->CI->router->fetch_method());

        // Skip check for excluded controllers/methods
        if (in_array($controller, $this->excludedControllers) || in_array($method, $this->excludedMethods)) {
            return;
        }

        // Skip for AJAX requests that are just fetching data
        if ($this->CI->input->is_ajax_request() && $this->CI->input->method() === 'get') {
            // Allow GET AJAX requests but check on POST
            return;
        }

        // Check if user is logged in
        if (!isset($this->CI->pageData['JwtData']->User->UserUID)) {
            return; // Not logged in, let the auth system handle it
        }

        $userUID = $this->CI->pageData['JwtData']->User->UserUID;

        // Load subscription library
        $this->CI->load->library('subscription');
        
        // Check subscription
        $subscriptionCheck = $this->CI->subscription->checkSubscription($userUID);

        // Store subscription info in pageData for views
        $this->CI->pageData['SubscriptionInfo'] = $subscriptionCheck;

        // If subscription is invalid, redirect to subscription page
        if (!$subscriptionCheck->isValid) {
            // For AJAX requests, return JSON error
            if ($this->CI->input->is_ajax_request()) {
                $response = new stdClass();
                $response->Error = true;
                $response->SubscriptionExpired = true;
                $response->Message = $subscriptionCheck->message;
                $response->Status = $subscriptionCheck->status;
                
                $this->CI->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode($response))
                    ->_display();
                exit;
            }

            // For normal requests, redirect to subscription page
            if ($controller !== 'subscription') {
                // Store the intended URL to redirect back after renewal
                $this->CI->session->set_userdata('intended_url', current_url());
                redirect('subscription/expired');
            }
        }

        // Show warning banner if subscription is expiring soon (within 7 days)
        if ($subscriptionCheck->isValid && $subscriptionCheck->daysRemaining > 0 && $subscriptionCheck->daysRemaining <= 7) {
            $this->CI->pageData['ShowExpiryWarning'] = true;
            $this->CI->pageData['ExpiryWarningDays'] = $subscriptionCheck->daysRemaining;
        }

        // Show grace period warning
        if ($subscriptionCheck->inGracePeriod) {
            $this->CI->pageData['ShowGracePeriodWarning'] = true;
            $this->CI->pageData['GracePeriodMessage'] = $subscriptionCheck->message;
        }
    }
}
