<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Subscription {

    protected $CI;

    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->model('subscription_model');
        $this->CI->load->model('dbwrite_model');
    }

    // ── Check if user subscription is valid ───────────────────────────────────
    public function checkSubscription($userUID) {
        $result = new stdClass();
        $result->isValid       = false;
        $result->status        = 'Unknown';
        $result->message       = '';
        $result->daysRemaining = 0;
        $result->inGracePeriod = false;

        try {
            $userResult = $this->CI->subscription_model->getUserSubscription($userUID);
            if ($userResult->Error || !$userResult->Data) {
                $result->message = 'User not found';
                return $result;
            }

            $user            = $userResult->Data;
            $result->status  = $user->SubscriptionStatus;
            $result->plan    = $user->SubscriptionPlan;
            $daysRemaining   = 0;

            if (in_array($user->SubscriptionStatus, ['Active', 'Trial'])) {
                if ($user->SubscriptionEndDate) {
                    $endDate = new DateTime($user->SubscriptionEndDate);
                    $now     = new DateTime('now', new DateTimeZone('UTC'));
                    $daysRemaining = (int)$now->diff($endDate)->format('%r%a');

                    $result->daysRemaining = $daysRemaining;

                    if ($daysRemaining > 0) {
                        $result->isValid = true;
                        $result->message = "Subscription active. {$daysRemaining} days remaining.";
                    } else {
                        $gracePeriodDays = (int)$user->GracePeriodDays;
                        $gracePeriodEnd  = clone $endDate;
                        $gracePeriodEnd->modify("+{$gracePeriodDays} days");

                        if ($now <= $gracePeriodEnd) {
                            $result->isValid       = true;
                            $result->inGracePeriod = true;
                            $graceRemaining        = (int)$now->diff($gracePeriodEnd)->format('%a');
                            $result->message       = "Subscription expired but in grace period. {$graceRemaining} days remaining.";
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
            } elseif ($user->SubscriptionStatus === 'Expired') {
                $result->isValid = false;
                $result->message = 'Your subscription has expired. Please renew to continue using the service.';
            } elseif ($user->SubscriptionStatus === 'Suspended') {
                $result->isValid = false;
                $result->message = 'Your account has been suspended. Please contact support.';
            } elseif ($user->SubscriptionStatus === 'Cancelled') {
                $result->isValid = false;
                $result->message = 'Your subscription has been cancelled.';
            }

            if ($result->isValid && $daysRemaining > 0 && $daysRemaining <= 7) {
                $this->sendExpiryWarning($userUID, $daysRemaining);
            }

        } catch (Exception $e) {
            $result->message = 'Error checking subscription: ' . $e->getMessage();
        }

        return $result;
    }

    // ── Update subscription status ────────────────────────────────────────────
    public function updateSubscriptionStatus($userUID, $status) {
        try {
            $updateResult = $this->CI->dbwrite_model->updateData(
                'Users', 'UserTbl',
                ['SubscriptionStatus' => $status, 'UpdatedOn' => date('Y-m-d H:i:s')],
                ['UserUID' => (int)$userUID]
            );
            return $updateResult->Error === FALSE;
        } catch (Exception $e) {
            log_message('error', 'Subscription::updateSubscriptionStatus — ' . $e->getMessage());
            return false;
        }
    }

    // ── Extend subscription by N days ─────────────────────────────────────────
    public function extendSubscription($userUID, $days, $planCode = null) {
        try {
            $userResult = $this->CI->subscription_model->getUserSubscription($userUID);
            if ($userResult->Error || !$userResult->Data) {
                return ['success' => false, 'message' => 'User not found'];
            }

            $user           = $userResult->Data;
            $currentEndDate = $user->SubscriptionEndDate ? new DateTime($user->SubscriptionEndDate) : new DateTime();
            $now            = new DateTime('now', new DateTimeZone('UTC'));

            $newEndDate = ($user->SubscriptionStatus === 'Expired' || $currentEndDate < $now)
                ? clone $now
                : clone $currentEndDate;
            $newEndDate->modify("+{$days} days");

            $updateData = [
                'SubscriptionStatus'  => 'Active',
                'SubscriptionEndDate' => $newEndDate->format('Y-m-d H:i:s'),
                'UpdatedOn'           => date('Y-m-d H:i:s'),
            ];
            if ($planCode) {
                $updateData['SubscriptionPlan'] = $planCode;
            }

            $updateResult = $this->CI->dbwrite_model->updateData(
                'Users', 'UserTbl', $updateData, ['UserUID' => (int)$userUID]
            );
            if ($updateResult->Error) {
                return ['success' => false, 'message' => $updateResult->Message];
            }

            $this->_logSubscriptionHistory($userUID, 'Renewed', $days);

            return [
                'success'    => true,
                'message'    => "Subscription extended by {$days} days",
                'newEndDate' => $newEndDate->format('Y-m-d H:i:s'),
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ── Log subscription history ──────────────────────────────────────────────
    private function _logSubscriptionHistory($userUID, $status, $days = 0) {
        try {
            // Re-read after the update so StartDate/EndDate reflect the new values
            $userResult = $this->CI->subscription_model->getUserSubscription($userUID);
            if ($userResult->Error || !$userResult->Data) return;

            $user = $userResult->Data;
            $this->CI->dbwrite_model->insertData('Users', 'SubscriptionHistoryTbl', [
                'UserUID'            => (int)$userUID,
                'OrgUID'             => $user->OrgUID,
                'SubscriptionStatus' => $status,
                'StartDate'          => $user->SubscriptionStartDate,
                'EndDate'            => $user->SubscriptionEndDate,
                'ActualEndDate'      => date('Y-m-d H:i:s'),
                'Notes'              => "Extended by {$days} days",
                'CreatedOn'          => date('Y-m-d H:i:s'),
            ]);
        } catch (Exception $e) {
            log_message('error', 'Subscription::_logSubscriptionHistory — ' . $e->getMessage());
        }
    }

    // ── Send expiry warning notification ──────────────────────────────────────
    private function sendExpiryWarning($userUID, $daysRemaining) {
        try {
            if (!in_array($daysRemaining, [7, 3, 1])) return;

            $notificationType = 'Expiry_Warning_' . $daysRemaining . 'Days';
            $today            = date('Y-m-d');

            // Skip if already sent today
            $notifCheck = $this->CI->subscription_model->isNotificationSentToday($userUID, $notificationType, $today);
            if ($notifCheck->Error === FALSE && $notifCheck->AlreadySent) return;

            // Get user contact details
            $emailResult = $this->CI->subscription_model->getUserEmailInfo($userUID);
            if ($emailResult->Error || !$emailResult->Data) return;

            $user     = $emailResult->Data;
            $fullName = trim(($user->FirstName ?? '') . ' ' . ($user->LastName ?? ''));

            // Insert notification record
            $notifResult = $this->CI->dbwrite_model->insertData('Users', 'SubscriptionNotificationTbl', [
                'UserUID'          => (int)$userUID,
                'NotificationType' => $notificationType,
                'NotificationData' => json_encode(['daysRemaining' => (int)$daysRemaining]),
                'SentOn'           => date('Y-m-d H:i:s'),
                'EmailSent'        => 0,
            ]);
            $notifUID = ($notifResult->Error === FALSE) ? (int)$notifResult->ID : 0;

            // Send email and mark row on success
            if (!empty($user->EmailAddress) && $notifUID > 0) {
                $sent = false;
                try {
                    $sent = $this->_sendExpiryEmail($user->EmailAddress, $fullName ?: 'Valued Customer', (int)$daysRemaining);
                } catch (Throwable $e) {
                    log_message('error', '[Subscription] Email send failed: ' . $e->getMessage());
                }
                if ($sent) {
                    $this->CI->dbwrite_model->updateData(
                        'Users', 'SubscriptionNotificationTbl',
                        ['EmailSent' => 1],
                        ['NotificationUID' => $notifUID]
                    );
                }
            }

            log_message('info', "Expiry warning ({$daysRemaining} days) processed for user {$userUID}");

        } catch (Throwable $e) {
            log_message('error', 'Subscription::sendExpiryWarning — ' . $e->getMessage());
        }
    }

    // ── Log login attempt ─────────────────────────────────────────────────────
    public function logLoginAttempt($userUID, $username, $status, $subscriptionStatus, $errorMessage = null) {
        try {
            $this->CI->dbwrite_model->insertData('Users', 'LoginAttemptLogTbl', [
                'UserUID'            => $userUID,
                'Username'           => $username,
                'AttemptStatus'      => $status,
                'SubscriptionStatus' => $subscriptionStatus,
                'IPAddress'          => $this->CI->input->ip_address(),
                'UserAgent'          => $this->CI->input->user_agent(),
                'AttemptTime'        => date('Y-m-d H:i:s'),
                'ErrorMessage'       => $errorMessage,
            ]);
        } catch (Exception $e) {
            log_message('error', 'Subscription::logLoginAttempt — ' . $e->getMessage());
        }
    }

    // ── Get subscription plans ────────────────────────────────────────────────
    public function getSubscriptionPlans($activeOnly = true) {
        $result = $this->CI->subscription_model->getSubscriptionPlans($activeOnly);
        return ($result->Error === FALSE) ? $result->Data : [];
    }

    // ── Activate subscription with plan ──────────────────────────────────────
    public function activateSubscription($userUID, $planCode, $paymentData = []) {
        try {
            $planResult = $this->CI->subscription_model->getPlanByCode($planCode);
            if ($planResult->Error || !$planResult->Data) {
                return ['success' => false, 'message' => 'Invalid plan'];
            }
            $plan = $planResult->Data;

            $now     = new DateTime('now', new DateTimeZone('UTC'));
            $endDate = clone $now;
            $endDate->modify("+{$plan->DurationDays} days");

            // Update user subscription
            $updateResult = $this->CI->dbwrite_model->updateData(
                'Users', 'UserTbl',
                [
                    'SubscriptionStatus'    => 'Active',
                    'SubscriptionStartDate' => $now->format('Y-m-d H:i:s'),
                    'SubscriptionEndDate'   => $endDate->format('Y-m-d H:i:s'),
                    'SubscriptionPlan'      => $plan->PlanName,
                    'UpdatedOn'             => date('Y-m-d H:i:s'),
                ],
                ['UserUID' => (int)$userUID]
            );
            if ($updateResult->Error) {
                return ['success' => false, 'message' => $updateResult->Message];
            }

            // Get OrgUID for history record
            $userResult = $this->CI->subscription_model->getUserSubscription($userUID);
            $orgUID     = ($userResult->Error === FALSE && $userResult->Data) ? $userResult->Data->OrgUID : null;

            // Insert subscription history
            $this->CI->dbwrite_model->insertData('Users', 'SubscriptionHistoryTbl', [
                'UserUID'            => (int)$userUID,
                'OrgUID'             => $orgUID,
                'PlanUID'            => $plan->PlanUID,
                'SubscriptionStatus' => 'Active',
                'StartDate'          => $now->format('Y-m-d H:i:s'),
                'EndDate'            => $endDate->format('Y-m-d H:i:s'),
                'Amount'             => $plan->Price,
                'PaymentStatus'      => $paymentData['status']      ?? 'Paid',
                'PaymentMethod'      => $paymentData['method']      ?? null,
                'TransactionID'      => $paymentData['transactionId'] ?? null,
                'CreatedOn'          => date('Y-m-d H:i:s'),
            ]);

            return [
                'success' => true,
                'message' => 'Subscription activated successfully',
                'endDate' => $endDate->format('Y-m-d H:i:s'),
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ── Send expiry warning email via Brevo REST API ──────────────────────────
    private function _sendExpiryEmail(string $toEmail, string $toName, int $daysRemaining): bool {
        $apiKey    = getenv('BREVO_API_KEY');
        $fromEmail = getenv('MAIL_FROM_EMAIL') ?: 'noreply@rishika2kenterprises.com';
        $fromName  = getenv('MAIL_FROM_NAME')  ?: 'Rishika 2K Enterprises';

        if (empty($apiKey)) {
            log_message('error', '[Subscription] BREVO_API_KEY not configured — expiry email not sent');
            return false;
        }

        $urgency = $daysRemaining === 1 ? 'URGENT: ' : '';
        $subject = $urgency . 'Your subscription expires in ' . $daysRemaining . ' day' . ($daysRemaining > 1 ? 's' : '');

        $payload = json_encode([
            'sender'      => ['name' => $fromName, 'email' => $fromEmail],
            'to'          => [['email' => $toEmail, 'name' => $toName]],
            'subject'     => $subject,
            'htmlContent' => $this->_buildExpiryEmailHtml($toName, $daysRemaining),
        ]);

        $ch = curl_init('https://api.brevo.com/v3/smtp/email');
        if ($ch === false) {
            log_message('error', '[Subscription] curl_init failed');
            return false;
        }

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => [
                'api-key: ' . $apiKey,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr || $httpCode < 200 || $httpCode >= 300) {
            log_message('error', '[Subscription] Expiry email failed — HTTP ' . $httpCode . ' ' . ($curlErr ?: $response));
            return false;
        }

        return true;
    }

    // ── Build HTML body for expiry warning email ──────────────────────────────
    private function _buildExpiryEmailHtml(string $name, int $days): string {
        $dayText  = $days === 1 ? '1 day' : "{$days} days";
        $fromName = getenv('MAIL_FROM_NAME') ?: 'Rishika 2K Enterprises';
        $accent   = $days === 1 ? '#dc2626' : ($days <= 3 ? '#d97706' : '#2563eb');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="padding:32px 0;">
    <tr><td align="center">
      <table width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.08);">
        <tr><td style="background:{$accent};padding:20px 32px;">
          <p style="margin:0;font-size:20px;font-weight:700;color:#ffffff;">{$fromName}</p>
          <p style="margin:4px 0 0;font-size:12px;color:rgba(255,255,255,.85);">Subscription Notice</p>
        </td></tr>
        <tr><td style="padding:32px;">
          <p style="margin:0 0 16px;font-size:15px;color:#111;">Dear {$name},</p>
          <p style="margin:0 0 16px;font-size:15px;color:#374151;">
            This is a reminder that your subscription will expire in
            <strong style="color:{$accent};">{$dayText}</strong>.
          </p>
          <p style="margin:0 0 24px;font-size:15px;color:#374151;">
            To avoid any interruption in your service, please renew your subscription before it expires.
          </p>
          <p style="margin:0 0 32px;font-size:15px;color:#374151;">
            If you have already renewed, you may disregard this message.
          </p>
          <p style="margin:0;font-size:14px;color:#6b7280;">
            Thank you,<br><strong>{$fromName}</strong>
          </p>
        </td></tr>
        <tr><td style="background:#f9fafb;padding:14px 32px;border-top:1px solid #e5e7eb;">
          <p style="margin:0;font-size:11px;color:#9ca3af;text-align:center;">
            This is an automated message. Please do not reply to this email.
          </p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
    }
}
