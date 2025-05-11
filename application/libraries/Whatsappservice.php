<?php defined('BASEPATH') or exit('No direct script access allowed');

require 'vendor/autoload.php';

use Twilio\Rest\Client;

class Whatsappservice {

    private $config;
    private $CI;
    private $Client;

    function __construct() {

        $this->CI =& get_instance();
        
        // $this->Client = new Client(getenv('TWILIO_ACC_SID'), getenv('TWILIO_AUTH_TOKEN'));
        $this->Client = new Client(getAWSConfigurationDetails()->TWILIO_ACC_SID, getAWSConfigurationDetails()->TWILIO_AUTH_TOKEN);

    }

    function SendWhatsAppMessage($ToNumber, $Message) {

        $this->EndReturnData = new stdClass();

        try {

            $MessageResp = $this->Client->messages->create(
                $ToNumber,
                [
                    'from' => getSiteConfiguration()->TwilioPhoneNum,
                    'body' => $Message
                ]
            );

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->MsgResponse = $MessageResp->sid;
            $this->EndReturnData->Message = 'Success';


        } catch(Exception $e) {

            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();

        }

        return $this->EndReturnData;

    }

}