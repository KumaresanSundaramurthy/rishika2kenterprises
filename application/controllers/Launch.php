<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Launch extends CI_Controller {
	
	public function __construct() {
        parent::__construct();
    }

    public function index() {
        $this->load->view('launch/index.html');
    }

    public function sendEnquiry() {

        $response = new stdClass();

        try {

            $name    = strip_tags($this->input->post('name'));
            $phone   = strip_tags($this->input->post('phone'));
            $email   = strip_tags($this->input->post('email'));
            $product = strip_tags($this->input->post('product'));
            $message = strip_tags($this->input->post('message'));

            if (empty($name) || empty($phone)) {
                throw new Exception('Name and phone number are required.');
            }

            $subject  = 'New Enquiry from ' . $name . ' — Rishika2K Enterprises';
            $htmlBody = '
                <h2 style="color:#f97316;font-family:Arial,sans-serif;">New Website Enquiry</h2>
                <table style="font-family:Arial,sans-serif;font-size:14px;color:#333;border-collapse:collapse;width:100%;">
                    <tr><td style="padding:8px;border:1px solid #ddd;"><strong>Name</strong></td><td style="padding:8px;border:1px solid #ddd;">' . $name . '</td></tr>
                    <tr><td style="padding:8px;border:1px solid #ddd;"><strong>Phone</strong></td><td style="padding:8px;border:1px solid #ddd;">' . $phone . '</td></tr>
                    <tr><td style="padding:8px;border:1px solid #ddd;"><strong>Email</strong></td><td style="padding:8px;border:1px solid #ddd;">' . ($email ?: '—') . '</td></tr>
                    <tr><td style="padding:8px;border:1px solid #ddd;"><strong>Product Interest</strong></td><td style="padding:8px;border:1px solid #ddd;">' . ($product ?: '—') . '</td></tr>
                    <tr><td style="padding:8px;border:1px solid #ddd;"><strong>Message</strong></td><td style="padding:8px;border:1px solid #ddd;">' . nl2br($message) . '</td></tr>
                </table>
            ';

            $this->load->library('email');
            $this->email->initialize([
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

            $this->email->clear();
            $this->email->from(getenv('MAIL_FROM_EMAIL'), getenv('MAIL_FROM_NAME') ?: 'Rishika2K Enterprises');
            $this->email->to('rishika2kenterprises@gmail.com');
            $this->email->subject($subject);
            $this->email->message($htmlBody);

            $sent = $this->email->send(false);
            if (!$sent) {
                throw new Exception('Failed to send email. Please try again.');
            }

            $response->Error   = false;
            $response->Message = 'Enquiry sent successfully!';

        } catch (Exception $e) {
            $response->Error   = true;
            $response->Message = $e->getMessage();
        }

        $this->output
            ->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($response))
            ->_display();
        exit;

    }

}