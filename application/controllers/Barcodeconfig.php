<?php defined('BASEPATH') or exit('No direct script access allowed');

class Barcodeconfig extends MY_Controller {

    public function __construct() {
        parent::__construct();
    }

    /* ── Page load ──────────────────────────────────────────────────────── */
    public function index() {

        if (!$this->_loadPageTitle()) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }

        $OrgUID   = (int) $this->pageData['JwtData']->Org->OrgUID;
        $BarcodeConfig = services_get('api/globally/getSingleRow?' . http_build_query([
            'orgId'  => $OrgUID,
            'dbName' => 'Products',
            'table'  => 'BarcodeConfigTbl',
            'where'  => json_encode(['ConfigType' => 'barcode', 'IsDeleted' => 0]),
        ]), withJwt: true)->Data ?? null;

        $QRConfig = services_get('api/globally/getSingleRow?' . http_build_query([
            'orgId'  => $OrgUID,
            'dbName' => 'Products',
            'table'  => 'BarcodeConfigTbl',
            'where'  => json_encode(['ConfigType' => 'qrcode', 'IsDeleted' => 0]),
        ]), withJwt: true)->Data ?? null;

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

        $this->load->view('settings/barcodeconfig/view', [
            'JwtData'       => $this->pageData['JwtData'],
            'BarcodeConfig' => $BarcodeConfig,
            'QRConfig'      => $QRConfig,
        ]);
    }

}