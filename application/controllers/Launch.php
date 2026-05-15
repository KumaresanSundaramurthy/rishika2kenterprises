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

            $subject  = '🌾 New Enquiry from ' . $name . ' — Rishika2K Enterprises';
            $receivedAt = date('d M Y, h:i A');
            $htmlBody = '<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>New Enquiry</title></head>
<body style="margin:0;padding:0;background-color:#f0f4f8;font-family:\'Segoe UI\',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f4f8;padding:32px 16px;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.10);">

      <!-- Header -->
      <tr>
        <td style="background:linear-gradient(135deg,#0a1628 0%,#1a2d4f 60%,#0d3b2e 100%);padding:36px 40px 28px;text-align:center;">
          <div style="display:inline-block;padding:10px 24px;margin-bottom:16px;">
            <span style="font-size:22px;font-weight:800;color:#f59e0b;letter-spacing:2px;text-transform:uppercase;">RISHIKA 2K</span>
            <span style="font-size:22px;font-weight:300;color:#e2e8f0;letter-spacing:2px;"> ENTERPRISES</span>
          </div>
          <p style="margin:6px 0 0;font-size:12px;color:#94a3b8;letter-spacing:3px;text-transform:uppercase;">Rotavator &bull; Baler &bull; Harvesting Machines</p>
          <div style="margin-top:20px;background:rgba(245,158,11,0.1);border:1px solid rgba(245,158,11,0.3);border-radius:8px;padding:10px 20px;display:inline-block;">
            <span style="font-size:16px;font-weight:600;color:#fbbf24;">&#128235; New Website Enquiry</span>
          </div>
        </td>
      </tr>

      <!-- Alert Banner -->
      <tr>
        <td style="background:linear-gradient(90deg,#f59e0b,#d97706);padding:10px 40px;text-align:center;">
          <span style="font-size:13px;font-weight:600;color:#1a1a1a;letter-spacing:1px;">RECEIVED ON &nbsp;&#8212;&nbsp; ' . $receivedAt . '</span>
        </td>
      </tr>

      <!-- Body -->
      <tr>
        <td style="padding:36px 40px;">
          <p style="margin:0 0 24px;font-size:15px;color:#475569;line-height:1.6;">A new enquiry has been submitted through the <strong style="color:#1e293b;">Rishika2K Enterprises</strong> website. Here are the details:</p>

          <!-- Enquiry Details -->
          <table width="100%" cellpadding="0" cellspacing="0" style="border-radius:10px;overflow:hidden;border:1px solid #e2e8f0;">

            <tr style="background:#f8fafc;">
              <td style="padding:14px 20px;width:38%;border-bottom:1px solid #e2e8f0;">
                <span style="font-size:11px;font-weight:700;color:#94a3b8;letter-spacing:1.5px;text-transform:uppercase;">&#128100; Full Name</span>
              </td>
              <td style="padding:14px 20px;border-bottom:1px solid #e2e8f0;border-left:3px solid #f59e0b;">
                <span style="font-size:15px;font-weight:600;color:#1e293b;">' . htmlspecialchars($name) . '</span>
              </td>
            </tr>

            <tr style="background:#ffffff;">
              <td style="padding:14px 20px;border-bottom:1px solid #e2e8f0;">
                <span style="font-size:11px;font-weight:700;color:#94a3b8;letter-spacing:1.5px;text-transform:uppercase;">&#128222; Phone Number</span>
              </td>
              <td style="padding:14px 20px;border-bottom:1px solid #e2e8f0;border-left:3px solid #10b981;">
                <span style="font-size:15px;font-weight:600;color:#1e293b;">' . htmlspecialchars($phone) . '</span>
              </td>
            </tr>

            <tr style="background:#f8fafc;">
              <td style="padding:14px 20px;border-bottom:1px solid #e2e8f0;">
                <span style="font-size:11px;font-weight:700;color:#94a3b8;letter-spacing:1.5px;text-transform:uppercase;">&#9993; Email Address</span>
              </td>
              <td style="padding:14px 20px;border-bottom:1px solid #e2e8f0;border-left:3px solid #3b82f6;">
                <span style="font-size:15px;color:#1e293b;">' . ($email ? '<a href="mailto:' . htmlspecialchars($email) . '" style="color:#3b82f6;text-decoration:none;font-weight:600;">' . htmlspecialchars($email) . '</a>' : '<em style="color:#94a3b8;">Not provided</em>') . '</span>
              </td>
            </tr>

            <tr style="background:#ffffff;">
              <td style="padding:14px 20px;border-bottom:1px solid #e2e8f0;">
                <span style="font-size:11px;font-weight:700;color:#94a3b8;letter-spacing:1.5px;text-transform:uppercase;">&#127981; Product Interest</span>
              </td>
              <td style="padding:14px 20px;border-bottom:1px solid #e2e8f0;border-left:3px solid #f59e0b;">
                ' . ($product
                    ? '<span style="display:inline-block;background:#fef3c7;color:#b45309;font-size:13px;font-weight:700;padding:4px 14px;border-radius:20px;border:1px solid #fde68a;">' . htmlspecialchars($product) . '</span>'
                    : '<em style="font-size:14px;color:#94a3b8;">Not specified</em>') . '
              </td>
            </tr>

            <tr style="background:#f8fafc;">
              <td style="padding:14px 20px;vertical-align:top;">
                <span style="font-size:11px;font-weight:700;color:#94a3b8;letter-spacing:1.5px;text-transform:uppercase;">&#128172; Message</span>
              </td>
              <td style="padding:14px 20px;border-left:3px solid #8b5cf6;">
                <span style="font-size:14px;color:#334155;line-height:1.7;">' . ($message ? nl2br(htmlspecialchars($message)) : '<em style="color:#94a3b8;">No message provided</em>') . '</span>
              </td>
            </tr>

          </table>

          <!-- CTA -->
          <div style="margin-top:28px;padding:20px;background:linear-gradient(135deg,#fef9ec,#fff7e6);border:1px solid #fde68a;border-radius:10px;text-align:center;">
            <p style="margin:0 0 8px;font-size:13px;color:#92400e;font-weight:600;">&#9889; Respond quickly to convert this lead!</p>
            ' . ($email ? '<a href="mailto:' . htmlspecialchars($email) . '" style="display:inline-block;background:linear-gradient(135deg,#f59e0b,#d97706);color:#1a1a1a;font-size:14px;font-weight:700;padding:10px 28px;border-radius:8px;text-decoration:none;letter-spacing:0.5px;">Reply to Enquiry &rarr;</a>' : '') . '
          </div>

        </td>
      </tr>

      <!-- Footer -->
      <tr>
        <td style="background:#0a1628;padding:28px 40px;text-align:center;">
          <p style="margin:0 0 8px;font-size:13px;font-weight:700;color:#f59e0b;letter-spacing:2px;text-transform:uppercase;">Rishika2K Enterprises</p>
          <p style="margin:0 0 12px;font-size:12px;color:#64748b;">Authorized Dealer &bull; Rotoking Rotavator &bull; Bharat Baler</p>
          <div style="border-top:1px solid #1e3a5f;padding-top:14px;margin-top:4px;">
            <p style="margin:0;font-size:11px;color:#475569;">This is an automated notification from your website enquiry form.</p>
            <p style="margin:4px 0 0;font-size:11px;color:#334155;">&copy; ' . date('Y') . ' Rishika2K Enterprises. All rights reserved.</p>
          </div>
        </td>
      </tr>

    </table>
  </td></tr>
</table>
</body>
</html>';

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