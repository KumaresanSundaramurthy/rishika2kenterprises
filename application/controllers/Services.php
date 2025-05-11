<?php defined('BASEPATH') or exit('No direct script access allowed');

class Services extends CI_Controller {

    private $EndReturnData;

    public function __construct() {
        parent::__construct();

    }

    public function SubscribeSendEmail() {

        $this->EndReturnData = new stdClass();
		try {

            $SubsName = $this->input->post('SubsName');
            $SubsEmail = $this->input->post('SubsEmail');
            $SubsCcode = $this->input->post('SubsCountryCode');
            $SubsMobile = $this->input->post('SubsMobile');
            $SubsTimezone = $this->input->post('CustTimezone');
            $SubsComment = $this->input->post('SubsComment') ? $this->input->post('SubsComment') : '-';
            
            $this->load->library('emailservice');

            $Subject = "Subscribed by $SubsName on ".date('d-m-Y H:i:s');

            $BodyHtml = '<html>
                            <head>
                                <title>New Subscriber</title>
                                <style>
                                    body { font-family: Arial, sans-serif; }
                                    .content { background-color: #f4f4f4; padding: 20px; }
                                    .header { background-color: #4CAF50; color: white; padding: 0px; text-align: center; }
                                    .footer { font-size: 12px; color: #888; text-align: center; margin-top: 20px; }
                                    .button {
                                        background-color: #4CAF50;
                                        color: white;
                                        padding: 10px 20px;
                                        text-decoration: none;
                                        border-radius: 5px;
                                    }
                                </style>
                            </head>
                            <body>
                                <div class="content">
                                    <div class="header">
                                        <h1>New Subscriber!</h1>
                                    </div>
                                    <p>Dear Rishika,</p>
                                    <p><strong>Name:</strong> '.$SubsName.'</p>
                                    <p><strong>Email:</strong> '.$SubsEmail.'</p>
                                    <p><strong>Mobile Number:</strong> '.$SubsCcode.' '.$SubsMobile.'</p>
                                    <p><strong>Comment:</strong> '.$SubsComment.'</p>
                                    <p><strong>Date & Time:</strong> '.changeTimeZomeDateFormat(time(), $SubsTimezone).'</p>
                                </div>
                            </body>
                        </html>';

            $PlainText = "New Subscriber - ".$SubsName." - ".$SubsEmail.' - '.$SubsCcode.' '.$SubsMobile." - ".$SubsComment;
            
            $ServiceResponse = $this->emailservice->sendEmailUsingSES(getSiteConfiguration()->FromEmail, getSiteConfiguration()->ToEmail, $Subject, $BodyHtml, $PlainText);

            if($ServiceResponse->Error) {

                // $WhatsappMessage = "Hi Rishika, New subscriber has been initiated. Please find the following details.\n";
                // $WhatsappMessage .= "Name: ".$SubsName."\n";
                // $WhatsappMessage .= "Email: ".$SubsEmail."\n";
                // $WhatsappMessage .= "Mobile: ".$SubsCcode.' '.$SubsMobile."\n";
                // $WhatsappMessage .= "Comment: ".$SubsComment."\n";

                // $this->load->library('whatsappservice');
                // $ReturnResp = $this->whatsappservice->SendWhatsAppMessage(getSiteConfiguration()->WhatsAppPhoneNumber, $WhatsappMessage);

                $this->EndReturnData->Error = TRUE;
                $this->EndReturnData->Message = $ServiceResponse->Message;

            } else {

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = 'Successfully delivered';
                
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;
        
    }

    public function SendWhatsAppMessage($MessageInfo) {

        $message = urlencode($MessageInfo);

        $whatsapp_url = "https://api.whatsapp.com/send?phone=".getSiteConfiguration()->PhoneNumber."&text=$message";

        redirect($whatsapp_url);

    }

}