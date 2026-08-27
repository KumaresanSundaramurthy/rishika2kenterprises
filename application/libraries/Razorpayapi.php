<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Razorpayapi â€” cURL wrapper for Razorpay REST API v1.
 * Reads credentials from environment variables:
 *   RAZORPAY_KEY_ID      â€” rzp_live_xxx or rzp_test_xxx
 *   RAZORPAY_KEY_SECRET  â€” secret key
 *
 * No external SDK required; works in the existing Docker/PHP environment.
 */
class Razorpayapi {

    private string $_keyId;
    private string $_keySecret;
    private string $_baseUrl = 'https://api.razorpay.com/v1';

    public function __construct() {
        $this->_keyId     = (string)(getenv('RAZORPAY_KEY_ID')     ?: '');
        $this->_keySecret = (string)(getenv('RAZORPAY_KEY_SECRET') ?: '');
    }

    /**
     * @returns bool
     */
    public function isConfigured(): bool {
        return $this->_keyId !== '' && $this->_keySecret !== '';
    }

    /**
     * @returns string
     */
    public function getKeyId(): string { return $this->_keyId; }

    /**
     * Create a Razorpay order. Returns the full order object from Razorpay.
     * Amount must be in the smallest currency unit (paise for INR).
     *
     * @param int    $amountPaise
     * @param string $receiptId    Short reference for your records (max 40 chars)
     * @param string $currency
     * @returns array
     */
    public function createOrder(int $amountPaise, string $receiptId, string $currency = 'INR'): array {
        return $this->_post('/orders', [
            'amount'   => $amountPaise,
            'currency' => $currency,
            'receipt'  => substr($receiptId, 0, 40),
        ]);
    }

    /**
     * Verify Razorpay payment signature (HMAC-SHA256).
     * Per Razorpay docs: signature = HMAC(order_id + "|" + payment_id, key_secret)
     *
     * @param string $orderId
     * @param string $paymentId
     * @param string $signature
     * @returns bool
     */
    public function verifySignature(string $orderId, string $paymentId, string $signature): bool {
        if ($this->_keySecret === '') return false;
        $expected = hash_hmac('sha256', $orderId . '|' . $paymentId, $this->_keySecret);
        return hash_equals($expected, $signature);
    }

    /**
     * @param string $path
     * @param array  $payload
     * @returns array
     */
    private function _post(string $path, array $payload): array {
        $url = $this->_baseUrl . $path;
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERPWD        => $this->_keyId . ':' . $this->_keySecret,
        ]);
        $raw  = curl_exec($ch);
        $err  = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) throw new Exception('Razorpay network error: ' . $err);

        $data = @json_decode($raw, true);
        if (!is_array($data)) throw new Exception('Razorpay returned an invalid response.');

        if (!empty($data['error'])) {
            $msg = $data['error']['description'] ?? ($data['error']['code'] ?? 'Razorpay error');
            throw new Exception($msg);
        }

        return $data;
    }
}
