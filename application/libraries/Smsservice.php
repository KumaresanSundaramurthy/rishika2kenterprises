<?php defined('BASEPATH') or exit('No direct script access allowed');

class Smsservice {

    private $CI;
    private $driver;
    private $provider;

    public function __construct() {
        $this->CI       =& get_instance();
        $this->provider = strtolower(trim(getenv('SMS_PROVIDER') ?: 'fast2sms'));
        $this->_loadDriver();
    }

    private function _loadDriver() {
        if ($this->provider === 'brevo') {
            require_once APPPATH . 'libraries/drivers/BrevoSmsDriver.php';
            $this->driver = new BrevoSmsDriver();
        } else {
            require_once APPPATH . 'libraries/drivers/Fast2SmsDriver.php';
            $this->driver = new Fast2SmsDriver();
        }
    }

    /**
     * Send SMS to a single mobile number.
     */
    public function send($mobile, $message) {
        return $this->driver->send($mobile, $message);
    }

    /**
     * Send SMS to multiple mobile numbers.
     * @param array $mobiles
     */
    public function sendBulk(array $mobiles, $message) {
        return $this->driver->sendBulk($mobiles, $message);
    }

    public function getProvider() {
        return $this->provider;
    }
}
