<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Barcodeconfig extends CI_Controller {

    private $EndReturnData;

    public function __construct() {
        parent::__construct();
        $this->load->model(['dbwrite_model', 'global_model']);
    }

    /* ── Page load ──────────────────────────────────────────────────────── */
    public function index() {

        $OrgUID = (int) $this->pageData['JwtData']->User->OrgUID;

        $BarcodeConfig = $this->global_model->getSingleRow(
            'Products', 'BarcodeConfigTbl',
            ['OrgUID' => $OrgUID, 'ConfigType' => 'barcode', 'IsDeleted' => 0]
        );

        $QRConfig = $this->global_model->getSingleRow(
            'Products', 'BarcodeConfigTbl',
            ['OrgUID' => $OrgUID, 'ConfigType' => 'qrcode', 'IsDeleted' => 0]
        );

        // Defaults if no DB row yet
        if (!$BarcodeConfig) {
            $BarcodeConfig = (object)[
                'ConfigUID'  => 0,
                'Format'     => 'CODE128',
                'BarWidth'   => 2,
                'Width'      => 2,
                'Height'     => 60,
                'ShowValue'  => 1,
                'FontSize'   => 11,
                'LineColor'  => '#000000',
                'IsEnabled'  => 1,
            ];
        }

        if (!$QRConfig) {
            $QRConfig = (object)[
                'ConfigUID'  => 0,
                'Size'       => 100,
                'ErrorLevel' => 'M',
                'DarkColor'  => '#000000',
                'LightColor' => '#ffffff',
                'IsEnabled'  => 1,
            ];
        }

        // Cast bit fields to int for JS
        $BarcodeConfig->ShowValue = (int)(bool)$BarcodeConfig->ShowValue;
        $BarcodeConfig->IsEnabled = (int)(bool)$BarcodeConfig->IsEnabled;
        $QRConfig->IsEnabled      = (int)(bool)$QRConfig->IsEnabled;

        $this->load->view('barcodeconfig/view', [
            'JwtData'       => $this->pageData['JwtData'],
            'BarcodeConfig' => $BarcodeConfig,
            'QRConfig'      => $QRConfig,
        ]);
    }

    /* ── Save barcode/qr config settings ───────────────────────────────── */
    public function saveConfig() {
        $this->EndReturnData = new stdClass();
        try {
            $OrgUID  = (int) $this->pageData['JwtData']->User->OrgUID;
            $UserUID = (int) $this->pageData['JwtData']->User->UserUID;
            $post    = $this->input->post();

            $ConfigType = ($post['ConfigType'] ?? 'barcode') === 'qrcode' ? 'qrcode' : 'barcode';

            if ($ConfigType === 'barcode') {
                $data = [
                    'Format'    => $post['Format']    ?? 'CODE128',
                    'BarWidth'  => (int)($post['BarWidth']  ?? 2),
                    'Height'    => (int)($post['Height']    ?? 60),
                    'ShowValue' => isset($post['ShowValue']) && $post['ShowValue'] == 1 ? 1 : 0,
                    'FontSize'  => (int)($post['FontSize']  ?? 11),
                    'LineColor' => $post['LineColor'] ?? '#000000',
                    'IsEnabled' => isset($post['IsEnabled']) && $post['IsEnabled'] == 1 ? 1 : 0,
                ];
            } else {
                $data = [
                    'Size'       => (int)($post['Size']       ?? 100),
                    'ErrorLevel' => $post['ErrorLevel'] ?? 'M',
                    'DarkColor'  => $post['DarkColor']  ?? '#000000',
                    'LightColor' => $post['LightColor'] ?? '#ffffff',
                    'IsEnabled'  => isset($post['IsEnabled']) && $post['IsEnabled'] == 1 ? 1 : 0,
                ];
            }

            $data['UpdatedBy'] = $UserUID;

            $existing = $this->global_model->getSingleRow(
                'Products', 'BarcodeConfigTbl',
                ['OrgUID' => $OrgUID, 'ConfigType' => $ConfigType, 'IsDeleted' => 0],
                'ConfigUID'
            );

            if ($existing) {
                $resp = $this->dbwrite_model->updateData(
                    'Products', 'BarcodeConfigTbl', $data,
                    ['ConfigUID' => (int)$existing->ConfigUID]
                );
            } else {
                $data['OrgUID']     = $OrgUID;
                $data['ConfigType'] = $ConfigType;
                $data['CreatedBy']  = $UserUID;
                $resp = $this->dbwrite_model->insertData('Products', 'BarcodeConfigTbl', $data);
            }

            if ($resp->Error) throw new Exception($resp->Message);

            $this->EndReturnData->Error   = false;
            $this->EndReturnData->Message = 'Configuration saved successfully.';

        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    /* ── Get saved label layout ─────────────────────────────────────────── */
    public function getLayout() {
        $this->EndReturnData = new stdClass();
        try {
            $OrgUID    = (int) $this->pageData['JwtData']->User->OrgUID;
            $LabelType = $this->input->get('type') === 'qrcode' ? 'qrcode' : 'barcode';

            $row = $this->global_model->getSingleRow(
                'Products', 'BarcodeLabelLayoutTbl',
                ['OrgUID' => $OrgUID, 'LabelType' => $LabelType, 'IsDeleted' => 0]
            );

            if ($row) {
                $row->FieldsLayout  = json_decode($row->FieldsLayout);
                $row->BarcodeConfig = $row->BarcodeConfig ? json_decode($row->BarcodeConfig) : null;
            }

            $this->EndReturnData->Error  = false;
            $this->EndReturnData->Layout = $row ?: null;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    /* ── Save label layout ──────────────────────────────────────────────── */
    public function saveLayout() {
        $this->EndReturnData = new stdClass();
        try {
            $OrgUID  = (int) $this->pageData['JwtData']->User->OrgUID;
            $UserUID = (int) $this->pageData['JwtData']->User->UserUID;
            $post    = $this->input->post();

            $LabelType     = ($post['LabelType'] ?? 'barcode') === 'qrcode' ? 'qrcode' : 'barcode';
            $LabelSizeKey  = $post['LabelSizeKey']   ?? '50x25';
            $LabelWidthPx  = (int)($post['LabelWidthPx']  ?? 189);
            $LabelHeightPx = (int)($post['LabelHeightPx'] ?? 94);
            $FieldsLayout  = $post['FieldsLayout']   ?? '[]';

            if (json_decode($FieldsLayout) === null) throw new Exception('Invalid layout data.');

            $existing = $this->global_model->getSingleRow(
                'Products', 'BarcodeLabelLayoutTbl',
                ['OrgUID' => $OrgUID, 'LabelType' => $LabelType, 'IsDeleted' => 0],
                'LayoutUID'
            );

            $data = [
                'LabelSizeKey'  => $LabelSizeKey,
                'LabelWidthPx'  => $LabelWidthPx,
                'LabelHeightPx' => $LabelHeightPx,
                'FieldsLayout'  => $FieldsLayout,
                'UpdatedBy'     => $UserUID,
            ];

            if ($existing) {
                $resp = $this->dbwrite_model->updateData(
                    'Products', 'BarcodeLabelLayoutTbl', $data,
                    ['LayoutUID' => (int)$existing->LayoutUID]
                );
            } else {
                $data['OrgUID']     = $OrgUID;
                $data['LabelType']  = $LabelType;
                $data['CreatedBy']  = $UserUID;
                $resp = $this->dbwrite_model->insertData('Products', 'BarcodeLabelLayoutTbl', $data);
            }

            if ($resp->Error) throw new Exception($resp->Message);

            $this->EndReturnData->Error   = false;
            $this->EndReturnData->Message = 'Layout saved successfully.';

        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

}
