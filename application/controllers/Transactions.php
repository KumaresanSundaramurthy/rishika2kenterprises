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

            $this->load->model('transactions_model');
            $header = $this->transactions_model->getTransactionById($transUID, $orgUID, $moduleUID);
            if (!$header) throw new Exception('Transaction not found.');

            $pdfBytes = $this->transactions_model->getOrGeneratePdfBytes($transUID, $orgUID, $moduleUID, $paperSize);
            if (!$pdfBytes) throw new Exception('Failed to generate PDF.');

            $filename = preg_replace('/[^A-Za-z0-9_\-]/', '_', $header->UniqueNumber ?? ('Trans_' . $transUID)) . '.pdf';

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
