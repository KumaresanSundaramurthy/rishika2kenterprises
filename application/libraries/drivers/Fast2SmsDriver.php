<?php defined('BASEPATH') or exit('No direct script access allowed');

class Fast2SmsDriver {

    private $apiKey;
    private $senderId;

    public function __construct() {
        $this->apiKey   = getenv('FAST2SMS_API_KEY');
        $this->senderId = getenv('FAST2SMS_SENDER_ID') ?: 'FSTSMS';
    }

    public function send($mobile, $message) {
        return $this->_dispatch($mobile, $message);
    }

    public function sendBulk(array $mobiles, $message) {
        $numbers = implode(',', array_filter(array_map('trim', $mobiles)));
        return $this->_dispatch($numbers, $message);
    }

    private function _dispatch($numbers, $message) {
        $result = new stdClass();
        try {
            $ch = curl_init('https://www.fast2sms.com/dev/bulk');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode([
                    'route'          => 'q',
                    'sender_id'      => $this->senderId,
                    'message'        => $message,
                    'language'       => 'english',
                    'flash'          => 0,
                    'numbers'        => $numbers,
                    'dlt_te_id'      => getenv('FAST2SMS_DLT_TE_ID') ?: '',
                ]),
                CURLOPT_HTTPHEADER     => [
                    'authorization: ' . $this->apiKey,
                    'Content-Type: application/json',
                    'cache-control: no-cache',
                ],
            ]);
            $response = curl_exec($ch);
            $err      = curl_error($ch);
            curl_close($ch);

            if ($err) throw new Exception('cURL error: ' . $err);

            $resp = json_decode($response, true);

            if ($resp && isset($resp['return']) && $resp['return'] === true) {
                $result->Error    = FALSE;
                $result->Message  = 'SMS sent successfully';
                $result->Response = $resp;
            } else {
                $msg = '';
                if (!empty($resp['message']) && is_array($resp['message'])) {
                    $msg = implode(', ', $resp['message']);
                } elseif (!empty($resp['message'])) {
                    $msg = $resp['message'];
                } else {
                    $msg = 'Fast2SMS error';
                }
                $result->Error    = TRUE;
                $result->Message  = $msg;
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
