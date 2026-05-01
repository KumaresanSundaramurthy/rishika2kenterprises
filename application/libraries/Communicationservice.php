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

        } catch (Exception $e) {
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
     * @return stdClass { Error, Message, Sent, Failed }
     */
    public function sendEmail($orgUID, $sentBy, $recipientType, array $uids, $subject, $htmlMessage) {

        $this->EndReturnData = new stdClass();

        try {

            if (empty($subject))     throw new Exception('Email subject is required.');
            if (empty($htmlMessage)) throw new Exception('Message cannot be empty.');
            if (empty($uids))        throw new Exception('No recipients selected.');

            $contacts = $this->_getContacts($recipientType, $uids, $orgUID);
            if (empty($contacts)) throw new Exception('No valid recipients found.');

            $fromEmail = getenv('MAIL_FROM_EMAIL') ?: getenv('MAIL_USERNAME');
            $fromName  = getenv('MAIL_FROM_NAME')  ?: 'R2K Enterprises';

            $this->CI->load->library('email');
            $this->CI->email->initialize([
                'protocol'    => 'smtp',
                'smtp_host'   => getenv('MAIL_HOST')     ?: 'smtp-relay.brevo.com',
                'smtp_port'   => (int)(getenv('MAIL_PORT') ?: 587),
                'smtp_user'   => getenv('MAIL_USERNAME')  ?: '',
                'smtp_pass'   => getenv('MAIL_PASSWORD')  ?: '',
                'smtp_crypto' => 'tls',
                'mailtype'    => 'html',
                'charset'     => 'utf-8',
                'newline'     => "\r\n",
            ]);

            $sent = 0; $failed = 0; $logs = [];

            foreach ($contacts as $c) {
                if (empty($c->EmailAddress)) { $failed++; continue; }

                $this->CI->email->clear();
                $this->CI->email->from($fromEmail, $fromName);
                $this->CI->email->to($c->EmailAddress, $c->Name ?? '');
                $this->CI->email->subject($subject);
                $this->CI->email->message($htmlMessage);

                $ok     = $this->CI->email->send(false);
                $status = $ok ? 'Sent' : 'Failed';
                $ok ? $sent++ : $failed++;

                $result           = new stdClass();
                $result->Error    = !$ok;
                $result->Response = $ok ? null : $this->CI->email->print_debugger(['headers']);

                $logs[] = $this->_buildLog($orgUID, 'Email', 'brevo_smtp', $recipientType, $c, $htmlMessage, $subject, $status, $result, $sentBy);
            }

            $this->_saveLogs($logs);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Sent    = $sent;
            $this->EndReturnData->Failed  = $failed;
            $this->EndReturnData->Message = "Email sent to {$sent} recipient(s)" . ($failed ? ", {$failed} failed." : '.');

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        return $this->EndReturnData;
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
