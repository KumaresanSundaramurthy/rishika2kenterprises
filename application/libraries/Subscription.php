<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Subscription Library
 * Handles subscription validation, expiry checks, and notifications
 */
class Subscription {

    protected $CI;

    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->database();
    }

    /**
     * Check if user subscription is valid
     * @param int $userUID
     * @return object {isValid: bool, status: string, message: string, daysRemaining: int}
     */
    public function checkSubscription($userUID) {
        $result = new stdClass();
        $result->isValid = false;
        $result->status = 'Unknown';
        $result->message = '';
        $result->daysRemaining = 0;
        $result->inGracePeriod = false;

        try {
            $this->CI->db->select('UserUID, SubscriptionStatus, SubscriptionEndDate, GracePeriodDays, SubscriptionPlan');
            $this->CI->db->from('Users.UserTbl');
            $this->CI->db->where('UserUID', $userUID);
            $query = $this->CI->db->get();

            if ($query->num_rows() === 0) {
                $result->message = 'User not found';
                return $result;
            }

            $user = $query->row();
            $result->status = $user->SubscriptionStatus;
            $result->plan = $user->SubscriptionPlan;

            // Check if subscription is active or in trial
            if (in_array($user->SubscriptionStatus, ['Active', 'Trial'])) {
                if ($user->SubscriptionEndDate) {
                    $endDate = new DateTime($user->SubscriptionEndDate);
                    $now = new DateTime('now', new DateTimeZone('UTC'));
                    $interval = $now->diff($endDate);
                    $daysRemaining = (int)$interval->format('%r%a');

                    $result->daysRemaining = $daysRemaining;

                    if ($daysRemaining > 0) {
                        $result->isValid = true;
                        $result->message = "Subscription active. {$daysRemaining} days remaining.";
                    } else {
                        // Check grace period
                        $gracePeriodDays = (int)$user->GracePeriodDays;
                        $gracePeriodEnd = clone $endDate;
                        $gracePeriodEnd->modify("+{$gracePeriodDays} days");

                        if ($now <= $gracePeriodEnd) {
                            $result->isValid = true;
                            $result->inGracePeriod = true;
                            $graceRemaining = (int)$now->diff($gracePeriodEnd)->format('%a');
                            $result->message = "Subscription expired but in grace period. {$graceRemaining} days remaining.";
                            $result->daysRemaining = -abs($daysRemaining);
                        } else {
                            $result->isValid = false;
                            $result->message = 'Subscription expired. Please renew to continue.';
                            $this->updateSubscriptionStatus($userUID, 'Expired');
                        }
                    }
                } else {
                    $result->isValid = false;
                    $result->message = 'No subscription end date set.';
                }
            } else if ($user->SubscriptionStatus === 'Expired') {
                $result->isValid = false;
                $result->message = 'Your subscription has expired. Please renew to continue using the service.';
            } else if ($user->SubscriptionStatus === 'Suspended') {
                $result->isValid = false;
                $result->message = 'Your account has been suspended. Please contact support.';
            } else if ($user->SubscriptionStatus === 'Cancelled') {
                $result->isValid = false;
                $result->message = 'Your subscription has been cancelled.';
            }

            // Send notifications if needed
            if ($result->isValid && $daysRemaining > 0 && $daysRemaining <= 7) {
                $this->sendExpiryWarning($userUID, $daysRemaining);
            }

        } catch (Exception $e) {
            $result->message = 'Error checking subscription: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Update subscription status
     */
    public function updateSubscriptionStatus($userUID, $status) {
        try {
            $this->CI->db->where('UserUID', $userUID);
            $this->CI->db->update('Users.UserTbl', [
                'SubscriptionStatus' => $status,
                'UpdatedOn' => date('Y-m-d H:i:s')
            ]);
            return true;
        } catch (Exception $e) {
            log_message('error', 'Failed to update subscription status: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Extend subscription
     */
    public function extendSubscription($userUID, $days, $planCode = null) {
        try {
            $this->CI->db->select('SubscriptionEndDate, SubscriptionStatus');
            $this->CI->db->from('Users.UserTbl');
            $this->CI->db->where('UserUID', $userUID);
            $query = $this->CI->db->get();

            if ($query->num_rows() === 0) {
                return ['success' => false, 'message' => 'User not found'];
            }

            $user = $query->row();
            $currentEndDate = $user->SubscriptionEndDate ? new DateTime($user->SubscriptionEndDate) : new DateTime();
            $now = new DateTime('now', new DateTimeZone('UTC'));

            // If expired, start from now, otherwise extend from current end date
            if ($user->SubscriptionStatus === 'Expired' || $currentEndDate < $now) {
                $newEndDate = clone $now;
            } else {
                $newEndDate = clone $currentEndDate;
            }
            $newEndDate->modify("+{$days} days");

            $updateData = [
                'SubscriptionStatus' => 'Active',
                'SubscriptionEndDate' => $newEndDate->format('Y-m-d H:i:s'),
                'UpdatedOn' => date('Y-m-d H:i:s')
            ];

            if ($planCode) {
                $updateData['SubscriptionPlan'] = $planCode;
            }

            $this->CI->db->where('UserUID', $userUID);
            $this->CI->db->update('Users.UserTbl', $updateData);

            // Log to history
            $this->logSubscriptionHistory($userUID, 'Renewed', $days);

            return [
                'success' => true,
                'message' => "Subscription extended by {$days} days",
                'newEndDate' => $newEndDate->format('Y-m-d H:i:s')
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Log subscription history
     */
    private function logSubscriptionHistory($userUID, $status, $days = 0) {
        try {
            $this->CI->db->select('OrgUID, SubscriptionStartDate, SubscriptionEndDate');
            $this->CI->db->from('Users.UserTbl');
            $this->CI->db->where('UserUID', $userUID);
            $query = $this->CI->db->get();

            if ($query->num_rows() > 0) {
                $user = $query->row();
                $this->CI->db->insert('Users.SubscriptionHistoryTbl', [
                    'UserUID' => $userUID,
                    'OrgUID' => $user->OrgUID,
                    'SubscriptionStatus' => $status,
                    'StartDate' => $user->SubscriptionStartDate,
                    'EndDate' => $user->SubscriptionEndDate,
                    'ActualEndDate' => date('Y-m-d H:i:s'),
                    'Notes' => "Extended by {$days} days",
                    'CreatedOn' => date('Y-m-d H:i:s')
                ]);
            }
        } catch (Exception $e) {
            log_message('error', 'Failed to log subscription history: ' . $e->getMessage());
        }
    }

    /**
     * Send expiry warning notification
     */
    private function sendExpiryWarning($userUID, $daysRemaining) {
        try {
            // Check if notification already sent today
            $notificationType = 'Expiry_Warning_' . $daysRemaining . 'Days';
            if (!in_array($daysRemaining, [7, 3, 1])) {
                return; // Only send on 7, 3, 1 days
            }

            $this->CI->db->select('NotificationUID');
            $this->CI->db->from('Users.SubscriptionNotificationTbl');
            $this->CI->db->where('UserUID', $userUID);
            $this->CI->db->where('NotificationType', $notificationType);
            $this->CI->db->where('DATE(SentOn)', date('Y-m-d'));
            $query = $this->CI->db->get();

            if ($query->num_rows() > 0) {
                return; // Already sent today
            }

            // Get user email
            $this->CI->db->select('EmailAddress, FirstName, LastName');
            $this->CI->db->from('Users.UserTbl');
            $this->CI->db->where('UserUID', $userUID);
            $userQuery = $this->CI->db->get();

            if ($userQuery->num_rows() > 0) {
                $user = $userQuery->row();
                
                // Log notification
                $this->CI->db->insert('Users.SubscriptionNotificationTbl', [
                    'UserUID' => $userUID,
                    'NotificationType' => $notificationType,
                    'SentOn' => date('Y-m-d H:i:s'),
                    'EmailSent' => 0,
                    'NotificationData' => json_encode(['daysRemaining' => $daysRemaining])
                ]);

                // TODO: Send actual email using your email service
                // $this->CI->load->library('email');
                // $this->CI->email->to($user->EmailAddress);
                // $this->CI->email->subject('Subscription Expiry Warning');
                // $this->CI->email->message("Your subscription will expire in {$daysRemaining} days.");
                // $this->CI->email->send();

                log_message('info', "Expiry warning sent to user {$userUID}: {$daysRemaining} days remaining");
            }

        } catch (Exception $e) {
            log_message('error', 'Failed to send expiry warning: ' . $e->getMessage());
        }
    }

    /**
     * Log login attempt
     */
    public function logLoginAttempt($userUID, $username, $status, $subscriptionStatus, $errorMessage = null) {
        try {
            $this->CI->db->insert('Users.LoginAttemptLogTbl', [
                'UserUID' => $userUID,
                'Username' => $username,
                'AttemptStatus' => $status,
                'SubscriptionStatus' => $subscriptionStatus,
                'IPAddress' => $this->CI->input->ip_address(),
                'UserAgent' => $this->CI->input->user_agent(),
                'AttemptTime' => date('Y-m-d H:i:s'),
                'ErrorMessage' => $errorMessage
            ]);
        } catch (Exception $e) {
            log_message('error', 'Failed to log login attempt: ' . $e->getMessage());
        }
    }

    /**
     * Get subscription plans
     */
    public function getSubscriptionPlans($activeOnly = true) {
        try {
            $this->CI->db->select('*');
            $this->CI->db->from('Users.SubscriptionPlanTbl');
            if ($activeOnly) {
                $this->CI->db->where('IsActive', 1);
            }
            $this->CI->db->order_by('Price', 'ASC');
            $query = $this->CI->db->get();
            return $query->result();
        } catch (Exception $e) {
            log_message('error', 'Failed to get subscription plans: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Activate subscription with plan
     */
    public function activateSubscription($userUID, $planCode, $paymentData = []) {
        try {
            // Get plan details
            $this->CI->db->select('*');
            $this->CI->db->from('Users.SubscriptionPlanTbl');
            $this->CI->db->where('PlanCode', $planCode);
            $this->CI->db->where('IsActive', 1);
            $query = $this->CI->db->get();

            if ($query->num_rows() === 0) {
                return ['success' => false, 'message' => 'Invalid plan'];
            }

            $plan = $query->row();
            $now = new DateTime('now', new DateTimeZone('UTC'));
            $endDate = clone $now;
            $endDate->modify("+{$plan->DurationDays} days");

            // Update user subscription
            $this->CI->db->where('UserUID', $userUID);
            $this->CI->db->update('Users.UserTbl', [
                'SubscriptionStatus' => 'Active',
                'SubscriptionStartDate' => $now->format('Y-m-d H:i:s'),
                'SubscriptionEndDate' => $endDate->format('Y-m-d H:i:s'),
                'SubscriptionPlan' => $plan->PlanName,
                'UpdatedOn' => date('Y-m-d H:i:s')
            ]);

            // Get user org
            $this->CI->db->select('OrgUID');
            $this->CI->db->from('Users.UserTbl');
            $this->CI->db->where('UserUID', $userUID);
            $userQuery = $this->CI->db->get();
            $user = $userQuery->row();

            // Log to history
            $this->CI->db->insert('Users.SubscriptionHistoryTbl', [
                'UserUID' => $userUID,
                'OrgUID' => $user->OrgUID,
                'PlanUID' => $plan->PlanUID,
                'SubscriptionStatus' => 'Active',
                'StartDate' => $now->format('Y-m-d H:i:s'),
                'EndDate' => $endDate->format('Y-m-d H:i:s'),
                'Amount' => $plan->Price,
                'PaymentStatus' => isset($paymentData['status']) ? $paymentData['status'] : 'Paid',
                'PaymentMethod' => isset($paymentData['method']) ? $paymentData['method'] : null,
                'TransactionID' => isset($paymentData['transactionId']) ? $paymentData['transactionId'] : null,
                'CreatedOn' => date('Y-m-d H:i:s')
            ]);

            return [
                'success' => true,
                'message' => 'Subscription activated successfully',
                'endDate' => $endDate->format('Y-m-d H:i:s')
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
