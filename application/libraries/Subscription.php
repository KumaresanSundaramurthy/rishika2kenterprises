<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Subscription {

    protected $CI;

    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->model('subscription_model');
        $this->CI->load->model('dbwrite_model');
        $this->CI->load->library('telegramnotifier');
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
                Telegramnotifier::alert('[SUB-CHECK] getUserSubscription failed', [
                    'UserUID'      => $userUID,
                    'Error'        => $userResult->Error  ? 'TRUE' : 'FALSE',
                    'Message'      => $userResult->Message ?? '—',
                    'DataIsNull'   => is_null($userResult->Data) ? 'yes' : 'no',
                    'LastQuery'    => method_exists($this->CI->subscription_model, 'getLastQuery')
                                        ? $this->CI->subscription_model->getLastQuery() : '—',
                ]);
                $result->message = 'User not found';
                return $result;
            }

            $user            = $userResult->Data;
            $result->status  = $user->SubscriptionStatus;
            $result->plan    = $user->SubscriptionPlan;
            $daysRemaining   = 0;

            if (in_array($user->SubscriptionStatus, ['Active', 'Trial'])) {
                if ($user->SubscriptionEndDate) {
                    /* Use explicit UTC for both sides to avoid server-timezone drift */
                    $endDate = new DateTime($user->SubscriptionEndDate, new DateTimeZone('UTC'));
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

                            Telegramnotifier::alert('[SUB-CHECK] Login BLOCKED — past grace', [
                                'UserUID'         => $userUID,
                                'Status_DB'       => $user->SubscriptionStatus,
                                'EndDate_Raw'     => $user->SubscriptionEndDate,
                                'EndDate_UTC'     => $endDate->format('Y-m-d H:i:s'),
                                'Now_UTC'         => $now->format('Y-m-d H:i:s'),
                                'DaysRemaining'   => $daysRemaining,
                                'GracePeriodDays' => $gracePeriodDays,
                                'GraceEnd_UTC'    => $gracePeriodEnd->format('Y-m-d H:i:s'),
                                'isValid'         => 'FALSE',
                            ]);
                        }
                    }
                } else {
                    $result->isValid = false;
                    $result->message = 'No subscription end date set.';

                    Telegramnotifier::alert('[SUB-CHECK] Login BLOCKED — no EndDate', [
                        'UserUID'    => $userUID,
                        'Status_DB'  => $user->SubscriptionStatus,
                        'EndDate'    => 'NULL',
                        'isValid'    => 'FALSE',
                    ]);
                }
            } elseif ($user->SubscriptionStatus === 'Expired') {
                $result->isValid = false;
                $result->message = 'Your subscription has expired. Please renew to continue using the service.';

                Telegramnotifier::alert('[SUB-CHECK] Login BLOCKED — Status=Expired', [
                    'UserUID'   => $userUID,
                    'Status_DB' => $user->SubscriptionStatus,
                    'EndDate'   => $user->SubscriptionEndDate ?? 'NULL',
                    'isValid'   => 'FALSE',
                ]);
            } elseif ($user->SubscriptionStatus === 'Suspended') {
                $result->isValid = false;
                $result->message = 'Your account has been suspended. Please contact support.';
            } elseif ($user->SubscriptionStatus === 'Cancelled') {
                $result->isValid = false;
                $result->message = 'Your subscription has been cancelled.';
            } else {
                $result->isValid = false;
                $result->message = 'No active subscription found for your organisation. Please contact support.';

                Telegramnotifier::alert('[SUB-CHECK] Login BLOCKED — no subscription row', [
                    'UserUID'    => $userUID,
                    'Status_DB'  => $user->SubscriptionStatus ?? 'NULL',
                    'OrgSubUID'  => $user->OrgSubUID           ?? 'NULL',
                    'OrgUID'     => $user->OrgUID              ?? 'NULL',
                    'isValid'    => 'FALSE',
                ]);
            }

            if ($result->isValid && $daysRemaining > 0 && $daysRemaining <= 7) {
                $this->sendExpiryWarning($userUID, $daysRemaining);
            }

        } catch (Exception $e) {
            notifyError('Subscription::checkSubscription', $e);
            $result->message = 'Error checking subscription: ' . $e->getMessage();
        }

        return $result;
    }

    // ── Update subscription status ────────────────────────────────────────────
    public function updateSubscriptionStatus($userUID, $status) {
        try {
            $orgUID = $this->CI->subscription_model->getOrgUIDByUser((int)$userUID);
            if (!$orgUID) return false;
            $db  = $this->CI->dbwrite_model->getWriteDb();
            $sql = 'UPDATE Billing.OrgSubscriptionTbl'
                 . ' SET Status = ?'
                 . ' WHERE OrgUID = ? AND Status != ?'
                 . ' ORDER BY StartDate DESC LIMIT 1';
            $db->query($sql, [$status, $orgUID, 'Cancelled']);
            return $db->affected_rows() >= 0;
        } catch (Exception $e) {
            notifyError('Subscription::updateSubscriptionStatus', $e);
            return false;
        }
    }

    // ── Extend subscription by N days ─────────────────────────────────────────
    public function extendSubscription($userUID, $days) {
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

            $orgUID = $this->CI->subscription_model->getOrgUIDByUser((int)$userUID);
            if (!$orgUID) return ['success' => false, 'message' => 'Org not found for user'];

            $db    = $this->CI->dbwrite_model->getWriteDb();
            $sql   = 'UPDATE Billing.OrgSubscriptionTbl'
                   . ' SET Status = ?, EndDate = ?'
                   . ' WHERE OrgUID = ? AND Status != ?'
                   . ' ORDER BY StartDate DESC LIMIT 1';
            $binds = ['Active', $newEndDate->format('Y-m-d H:i:s'), $orgUID, 'Cancelled'];
            $db->query($sql, $binds);
            if ($db->affected_rows() === 0) {
                return ['success' => false, 'message' => 'No active subscription record found to extend'];
            }

            $this->_logSubscriptionHistory($userUID, 'Renewed', $days);

            return [
                'success'    => true,
                'message'    => "Subscription extended by {$days} days",
                'newEndDate' => $newEndDate->format('Y-m-d H:i:s'),
            ];

        } catch (Exception $e) {
            notifyError('Subscription::extendSubscription', $e);
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
            ]);
        } catch (Exception $e) {
            notifyError('Subscription::_logSubscriptionHistory', $e);
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
                    notifyError('Subscription::sendExpiryWarning', $e);
                }
                if ($sent) {
                    $this->CI->dbwrite_model->updateData(
                        'Users', 'SubscriptionNotificationTbl',
                        ['EmailSent' => 1],
                        ['NotificationUID' => $notifUID]
                    );
                }
            }


        } catch (Throwable $e) {
            notifyError('Subscription::sendExpiryWarning', $e);
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
            notifyError('Subscription::logLoginAttempt', $e);
        }
    }

    // ── Get subscription plans ────────────────────────────────────────────────
    public function getSubscriptionPlans($activeOnly = true) {
        $result = $this->CI->subscription_model->getSubscriptionPlans($activeOnly);
        return ($result->Error === FALSE) ? $result->Data : [];
    }

    // ── Activate subscription with a SectorPlan ──────────────────────────────────
    public function activateSubscription($userUID, $sectorPlanUID, $paymentData = []) {
        try {
            $planResult = $this->CI->subscription_model->getSectorPlanByUID((int)$sectorPlanUID);
            if ($planResult->Error || !$planResult->Data) {
                return ['success' => false, 'message' => 'Invalid plan'];
            }
            $plan = $planResult->Data;

            $now     = new DateTime('now', new DateTimeZone('UTC'));
            $endDate = clone $now;
            $endDate->modify('+' . (int)$plan->DurationDays . ' days');

            $orgUID = $this->CI->subscription_model->getOrgUIDByUser((int)$userUID);
            if (!$orgUID) return ['success' => false, 'message' => 'Org not found for user'];

            /* Insert new subscription row — previous rows kept as history */
            $subResult = $this->CI->dbwrite_model->insertData('Billing', 'OrgSubscriptionTbl', [
                'OrgUID'          => $orgUID,
                'SectorPlanUID'   => (int)$plan->SectorPlanUID,
                'StartDate'       => $now->format('Y-m-d H:i:s'),
                'EndDate'         => $endDate->format('Y-m-d H:i:s'),
                'Status'          => 'Active',
                'AutoRenew'       => 0,
                'GracePeriodDays' => 7,
            ]);
            if ($subResult->Error) {
                return ['success' => false, 'message' => $subResult->Message];
            }
            $orgSubUID = (int)$subResult->ID;

            /* Log the order */
            $paidAmount = (float)($paymentData['amount'] ?? $plan->Price ?? 0);
            $this->CI->dbwrite_model->insertData('Billing', 'SubscriptionOrdersTbl', [
                'OrgUID'        => $orgUID,
                'SectorPlanUID' => (int)$plan->SectorPlanUID,
                'OrgSubUID'     => $orgSubUID,
                'RenewalType'   => 'New',
                'DueDate'       => $endDate->format('Y-m-d'),
                'Amount'        => $paidAmount,
                'DiscountAmount'=> 0.00,
                'TaxAmount'     => 0.00,
                'NetAmount'     => $paidAmount,
                'Status'        => 'Paid',
                'PaymentMode'   => $paymentData['mode']          ?? null,
                'PaidOn'        => $now->format('Y-m-d H:i:s'),
                'Notes'         => $paymentData['notes']         ?? null,
                'CreatedBy'     => (int)$userUID ?: null,
            ]);

            return [
                'success' => true,
                'message' => 'Subscription activated successfully',
                'endDate' => $endDate->format('Y-m-d'),
            ];

        } catch (Exception $e) {
            notifyError('Subscription::activateSubscription', $e);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ── Send expiry warning email via Brevo REST API ──────────────────────────
    private function _sendExpiryEmail(string $toEmail, string $toName, int $daysRemaining): bool {
        $apiKey    = getenv('BREVO_API_KEY');
        $fromEmail = getenv('MAIL_FROM_EMAIL') ?: 'noreply@rishika2kenterprises.com';
        $fromName  = getenv('MAIL_FROM_NAME')  ?: 'Rishika 2K Enterprises';

        if (empty($apiKey)) {
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
