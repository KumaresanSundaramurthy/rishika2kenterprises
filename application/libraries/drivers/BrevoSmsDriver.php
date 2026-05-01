<?php defined('BASEPATH') or exit('No direct script access allowed');

class BrevoSmsDriver {

    private $apiKey;
    private $sender;

    public function __construct() {
        $this->apiKey = getenv('BREVO_SMS_API_KEY');
        $this->sender = getenv('BREVO_SMS_SENDER') ?: 'R2K';
    }

    public function send($mobile, $message) {
        return $this->_dispatch($this->_formatMobile($mobile), $message);
    }

    public function sendBulk(array $mobiles, $message) {
        $sent   = 0;
        $failed = 0;
        $errors = [];

        foreach ($mobiles as $mobile) {
            $mobile = trim($mobile);
            if (empty($mobile)) continue;

            $res = $this->_dispatch($this->_formatMobile($mobile), $message);
            if ($res->Error) {
                $failed++;
                $errors[] = $mobile . ': ' . $res->Message;
            } else {
                $sent++;
            }
        }

        $result          = new stdClass();
        $result->Error   = $failed > 0 && $sent === 0;
        $result->Message = $failed === 0
            ? 'All ' . $sent . ' SMS sent successfully'
            : $sent . ' sent, ' . $failed . ' failed';
        $result->Response = ['sent' => $sent, 'failed' => $failed, 'errors' => $errors];

        return $result;
    }

    private function _formatMobile($mobile) {
        $mobile = preg_replace('/[^0-9+]/', '', $mobile);
        if (substr($mobile, 0, 1) !== '+') {
            $mobile = '+91' . ltrim($mobile, '0');
        }
        return $mobile;
    }

    private function _dispatch($recipient, $message) {
        $result = new stdClass();
        try {
            $ch = curl_init('https://api.brevo.com/v3/transactionalSMS/sms');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'sender'    => $this->sender,
                'recipient' => $recipient,
                'content'   => $message,
                'type'      => 'transactional',
            ]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'api-key: ' . $this->apiKey,
                'Content-Type: application/json',
                'Accept: application/json',
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err      = curl_error($ch);
            curl_close($ch);

            if ($err) throw new Exception('cURL error: ' . $err);

            $resp = json_decode($response, true);

            if ($httpCode === 201 || $httpCode === 200) {
                $result->Error    = FALSE;
                $result->Message  = 'SMS sent successfully';
                $result->Response = $resp;
            } else {
                $result->Error    = TRUE;
                $result->Message  = $resp['message'] ?? ('Brevo error ' . $httpCode);
                $result->Response = $resp;
            }

        } catch (Exception $e) {
            $result->Error    = TRUE;
            $result->Message  = $e->getMessage();
            $result->Response = null;
        }

        return $result;
    }
}
