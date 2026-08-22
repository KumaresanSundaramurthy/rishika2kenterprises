<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Communicationservice
 * Central library for sending SMS and Email communications.
 * Used by Customers, Vendors, and any other module.
 *
 * Usage:
 *   $this->load->library('communicationservice');
 *   $this->communicationservice->sendSMS($orgUID, $sentBy, $recipientType, $uids, $message);
 *   $this->communicationservice->sendEmail($orgUID, $sentBy, $recipientType, $uids, $subject, $htmlMessage);
 */
class Communicationservice {

    private $CI;
    private $EndReturnData;

    public function __construct() {
        $this->CI =& get_instance();
    }

    // ── Send SMS ──────────────────────────────────────────────────────────────
    /**
     * @param int    $orgUID        Organisation UID
     * @param int    $sentBy        User UID who triggered the send
     * @param string $recipientType 'Customer' | 'Vendor'
     * @param array  $uids          Array of recipient UIDs
     * @param string $message       Plain text message
     * @return stdClass { Error, Message, Sent, Failed }
     */
    public function sendSMS($orgUID, $sentBy, $recipientType, array $uids, $message) {

        $this->EndReturnData = new stdClass();

        try {

            if (empty($message))  throw new Exception('Message cannot be empty.');
            if (empty($uids))     throw new Exception('No recipients selected.');

            $contacts = $this->_getContacts($recipientType, $uids, $orgUID);
            if (empty($contacts)) throw new Exception('No valid recipients found.');

            $this->CI->load->library('smsservice');
            $provider = $this->CI->smsservice->getProvider();

            $sent = 0; $failed = 0; $logs = [];

            foreach ($contacts as $c) {
                if (empty($c->MobileNumber)) { $failed++; continue; }
                $mobile = preg_replace('/[^0-9]/', '', ($c->CountryCode ?? '') . $c->MobileNumber);
                $result = $this->CI->smsservice->send($mobile, $message);
                $status = $result->Error ? 'Failed' : 'Sent';
                $result->Error ? $failed++ : $sent++;
                $logs[] = $this->_buildLog($orgUID, 'SMS', $provider, $recipientType, $c, $message, '', $status, $result, $sentBy);
            }

            $this->_saveLogs($logs);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Sent    = $sent;
            $this->EndReturnData->Failed  = $failed;
            $this->EndReturnData->Message = "SMS sent to {$sent} recipient(s)" . ($failed ? ", {$failed} failed." : '.');

        } catch (Throwable $e) {
            notifyError('Communicationservice::sendSMS', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        return $this->EndReturnData;
    }

    // ── Send Email ────────────────────────────────────────────────────────────
    /**
     * @param int    $orgUID        Organisation UID
     * @param int    $sentBy        User UID who triggered the send
     * @param string $recipientType 'Customer' | 'Vendor'
     * @param array  $uids          Array of recipient UIDs
     * @param string $subject       Email subject
     * @param string $htmlMessage   HTML message body (from Quill editor)
     * @param array  $attachments   Optional: array of file paths to attach
     * @return stdClass { Error, Message, Sent, Failed }
     */
    public function sendEmail($orgUID, $sentBy, $recipientType, array $uids, $subject, $htmlMessage, array $attachments = []) {

        $this->EndReturnData = new stdClass();

        try {

            if (empty($subject))     throw new Exception('Email subject is required.');
            if (empty($htmlMessage)) throw new Exception('Message cannot be empty.');
            if (empty($uids))        throw new Exception('No recipients selected.');

            $contacts = $this->_getContacts($recipientType, $uids, $orgUID);
            if (empty($contacts)) throw new Exception('No valid recipients found.');

            $apiKey = getenv('BREVO_API_KEY');
            if (!$apiKey) throw new Exception('BREVO_API_KEY is not configured.');

            $fromEmail = getenv('MAIL_FROM_EMAIL') ?: getenv('MAIL_USERNAME');
            $fromName  = getenv('MAIL_FROM_NAME')  ?: 'R2K Enterprises';

            $htmlBody = $this->_wrapHtmlEmail($htmlMessage);

            // Build Brevo attachment array (base64-encoded file content)
            $apiAttachments = [];
            foreach ($attachments as $filePath) {
                if (!is_file($filePath)) continue;
                $apiAttachments[] = [
                    'content' => base64_encode((string) file_get_contents($filePath)),
                    'name'    => basename($filePath),
                ];
            }

            $sent = 0; $failed = 0; $logs = [];

            foreach ($contacts as $c) {
                if (empty($c->EmailAddress)) { $failed++; continue; }

                $payload = [
                    'sender'      => ['name' => $fromName, 'email' => $fromEmail],
                    'to'          => [['email' => $c->EmailAddress, 'name' => $c->Name ?? '']],
                    'subject'     => $subject,
                    'htmlContent' => $htmlBody,
                ];
                if (!empty($apiAttachments)) {
                    $payload['attachment'] = $apiAttachments;
                }

                $ch = curl_init('https://api.brevo.com/v3/smtp/email');
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => json_encode($payload),
                    CURLOPT_HTTPHEADER     => [
                        'accept: application/json',
                        'api-key: ' . $apiKey,
                        'content-type: application/json',
                    ],
                    CURLOPT_TIMEOUT        => 30,
                ]);
                $response = curl_exec($ch);
                $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlErr  = curl_error($ch);
                curl_close($ch);

                $ok     = ($httpCode >= 200 && $httpCode < 300) && !$curlErr;
                $status = $ok ? 'Sent' : 'Failed';
                $ok ? $sent++ : $failed++;

                $result           = new stdClass();
                $result->Error    = !$ok;
                $result->Response = $ok ? null : ($curlErr ?: $response);

                $logs[] = $this->_buildLog($orgUID, 'Email', 'brevo_api', $recipientType, $c, $htmlMessage, $subject, $status, $result, $sentBy);
            }

            $this->_saveLogs($logs);

            if ($sent === 0 && $failed > 0) {
                $this->EndReturnData->Error   = TRUE;
                $this->EndReturnData->Sent    = 0;
                $this->EndReturnData->Failed  = $failed;
                $this->EndReturnData->Message = "Email delivery failed for all {$failed} recipient(s). Please check your Brevo API key and sender address configuration.";
                return $this->EndReturnData;
            }

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Sent    = $sent;
            $this->EndReturnData->Failed  = $failed;
            $this->EndReturnData->Message = "Email sent to {$sent} recipient(s)" . ($failed ? ", {$failed} failed." : '.');

        } catch (Throwable $e) {
            notifyError('Communicationservice::sendEmail', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        return $this->EndReturnData;
    }

    private function _wrapHtmlEmail($html) {
        // Already a full HTML document — use as-is
        if (stripos($html, '<html') !== false) {
            return $html;
        }
        // Quill fragment — wrap in a styled HTML document
        return '<!DOCTYPE html>
                <html>
                    <head>
                        <meta charset="UTF-8">
                        <meta name="viewport" content="width=device-width,initial-scale=1">
                        <style>
                            body { font-family: Arial, Helvetica, sans-serif; font-size: 14px; color: #333; line-height: 1.6; margin: 0; padding: 20px; }
                            p { margin: 0 0 10px 0; }
                            ul, ol { margin: 0 0 10px 20px; }
                            a { color: #1565c0; }
                        </style>
                        </head>
                    <body>' . $html . '</body>
                </html>';
    }

    // ── Private helpers ───────────────────────────────────────────────────────
    private function _getContacts($recipientType, array $uids, $orgUID) {
        if ($recipientType === 'Customer') {
            $this->CI->load->model('customers_model');
            $rows = $this->CI->customers_model->getCustomers(['Customers.OrgUID' => $orgUID]);
            return array_values(array_filter($rows, fn($r) => in_array($r->CustomerUID, $uids)));
        }
        $this->CI->load->model('vendors_model');
        $rows = $this->CI->vendors_model->getVendors(['Vendors.OrgUID' => $orgUID]);
        return array_values(array_filter($rows, fn($r) => in_array($r->VendorUID, $uids)));
    }

    private function _buildLog($orgUID, $commType, $provider, $recipientType, $c, $message, $subject, $status, $result, $sentBy) {
        return [
            'OrgUID'           => $orgUID,
            'CommType'         => $commType,
            'Provider'         => $provider,
            'RecipientType'    => $recipientType,
            'RecipientUID'     => $recipientType === 'Customer' ? $c->CustomerUID : $c->VendorUID,
            'RecipientName'    => $c->Name    ?? '',
            'RecipientContact' => $commType === 'SMS' ? ($c->MobileNumber ?? '') : ($c->EmailAddress ?? ''),
            'Subject'          => $subject,
            'Message'          => $message,
            'Status'           => $status,
            'ProviderResponse' => json_encode($result->Response ?? null),
            'SentBy'           => $sentBy,
            'SentOn'           => date('Y-m-d H:i:s'),
        ];
    }

    private function _saveLogs(array $logs) {
        if (empty($logs)) return;
        $this->CI->load->model('dbwrite_model');
        foreach ($logs as $log) {
            $this->CI->dbwrite_model->insertData('Security', 'CommunicationLogTbl', $log);
        }
    }

}
