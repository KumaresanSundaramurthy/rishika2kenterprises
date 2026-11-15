<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Transactions extends MY_Controller {

    public function __construct() {
        parent::__construct();

    }

    // ----------------------------------------------------------------
    // POST /transactions/downloadA4Pdf
    // Renders the transaction as HTML, converts to PDF via DomPDF,
    // and streams it as a file download.
    // ----------------------------------------------------------------
    public function downloadA4Pdf() {

        try {

            $transUID  = (int) $this->input->get_post('TransUID');
            $moduleUID = (int) $this->input->get_post('ModuleUID');
            $paperSize = strtoupper(trim($this->input->get_post('PaperSize') ?: 'A4'));
            $orgUID    = $this->pageData['JwtData']->Org->OrgUID;

            if ($transUID  <= 0) throw new Exception('Invalid transaction.');
            if ($moduleUID <= 0) throw new Exception('ModuleUID is required.');

            $base = rtrim(getenv('SERVICES_BASE_URL') ?: '', '/');
            if (!$base) throw new Exception('PDF service unavailable.');

            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';
            $filename = 'Trans_' . $transUID . '.pdf';

            $ch = curl_init(
                $base . '/api/transactions/downloadPdf/' . $transUID
                    . '?orgId=' . $orgUID . '&moduleUID=' . $moduleUID . '&paperSize=' . urlencode($paperSize)
            );
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $jwtToken],
                CURLOPT_HEADERFUNCTION => function ($curl, $line) use (&$filename) {
                    if (preg_match('/^Content-Disposition:.*filename="([^"]+)"/i', $line, $m)) $filename = $m[1];
                    return strlen($line);
                },
            ]);
            $pdfBytes    = curl_exec($ch);
            $httpCode    = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            curl_close($ch);

            if (!$pdfBytes || $httpCode !== 200 || stripos($contentType, 'application/pdf') !== 0) {
                throw new Exception('Failed to generate PDF.');
            }

            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: private, max-age=0, must-revalidate');
            echo $pdfBytes;
            exit;

        } catch (Exception $e) {
            notifyError('Transactions::downloadA4Pdf', $e);
            header('Content-Type: application/json');
            echo json_encode(['Error' => true, 'Message' => $e->getMessage()]);
            exit;
        }

    }

}
