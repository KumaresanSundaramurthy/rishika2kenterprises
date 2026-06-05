<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Exports — single controller for all transaction export formats.
 * Route: POST /exports/exportData
 *
 * Accepts: moduleUID, format (csv|excel|pdf|print), Filter[]
 * Delegates entirely to TransactionExporter library — no per-type logic here.
 */
class Exports extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('TransactionExporter');
    }

    public function exportData() {

        $this->EndReturnData = new stdClass();

        try {

            $moduleUID = (int)   $this->input->post('moduleUID');
            $format    = (string)$this->input->post('format');
            $filters   =         $this->input->post('Filter') ?: [];
            $orgUID    = $this->pageData['JwtData']->Org->OrgUID;
            $dateFmt   = $this->pageData['JwtData']->GenSettings->ListDateFormat ?? 'd M Y';

            $validFormats = ['csv', 'excel', 'pdf', 'print'];
            if (!$moduleUID || !in_array($format, $validFormats)) {
                http_response_code(400);
                echo json_encode(['Error' => true, 'Message' => 'Invalid export request.']);
                return;
            }

            if (!$this->transactionexporter->getConfig($moduleUID)) {
                http_response_code(404);
                echo json_encode(['Error' => true, 'Message' => 'Export config not found for this module.']);
                return;
            }

            $result = $this->transactionexporter->export($moduleUID, $format, $filters, $orgUID, $dateFmt);

            if (!$result) {
                http_response_code(500);
                echo json_encode(['Error' => true, 'Message' => 'Export failed.']);
                return;
            }

            // Print: return HTML as JSON — JS opens it in a new tab
            if ($format === 'print') {
                echo json_encode(['Error' => false, 'Html' => $result['content']]);
                return;
            }

            // CSV / Excel / PDF: stream as download
            header('Content-Type: '        . $result['type']);
            header('Content-Disposition: attachment; filename="' . rawurlencode($result['filename']) . '"');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
            echo $result['content'];

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['Error' => true, 'Message' => $e->getMessage()]);
        }
    }
}
