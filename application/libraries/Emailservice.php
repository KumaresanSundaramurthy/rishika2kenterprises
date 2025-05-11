<?php defined('BASEPATH') or exit('No direct script access allowed');

require 'vendor/autoload.php';

use Aws\Ses\SesClient;
use PHPMailer\PHPMailer\PHPMailer;
use Aws\Exception\AwsException;

class Emailservice {

    private $config;
    private $SesClient;
    private $CI;
    private $EndReturnData;

    function __construct() {

        $this->CI =& get_instance();
        
        // $credentials = new Aws\Credentials\Credentials(getenv('AWS_KEY'), getenv('AWS_SECRET'));
        $credentials = new Aws\Credentials\Credentials(getAWSConfigurationDetails()->AWS_KEY, getAWSConfigurationDetails()->AWS_SECRET);

        $this->config = array(
            'version'     => 'latest',
            'region'      => 'ap-south-1',
            'credentials' => $credentials
        );

        $this->SesClient = new SesClient($this->config);

    }

    function sendEmailUsingSES($FromAddr, $ToAddr, $Subject, $HtmlBody, $PlainText) {

        $this->EndReturnData = new stdClass();

        try {

            $result = $this->SesClient->sendEmail([
                'Source' => $FromAddr,
                'Destination' => [
                    'ToAddresses' => [$ToAddr]
                ],
                'Message' => [
                    'Subject' => [
                        'Data'    => $Subject,
                        'Charset' => 'UTF-8'
                    ],
                    'Body' => [
                        'Html' => [
                            'Data' => $HtmlBody,
                            'Charset' => 'UTF-8',
                        ],
                        'Text' => [
                            'Data'    => $PlainText,
                            'Charset' => 'UTF-8'
                        ]
                    ]
                ],
            ]);

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->MsgResponse = $result->get('MessageResponse');
            $this->EndReturnData->MsgId = $result['MessageId'];
            $this->EndReturnData->Message = 'Success';
            
        } catch(AwsException $e) {

            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();

        }

        return $this->EndReturnData;

    }

}