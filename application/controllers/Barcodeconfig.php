<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Barcodeconfig extends CI_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function index() {
        $pageData = [
            'JwtData'       => $this->pageData['JwtData'],
            'PageTitle'     => 'Barcode & QR Code Config',
            'BarcodeConfig' => $this->_defaultConfig('barcode'),
            'QRConfig'      => $this->_defaultConfig('qrcode'),
        ];

        $this->load->view('barcodeconfig/view', $pageData);
    }

    private function _defaultConfig($type) {
        if ($type === 'barcode') {
            return (object) [
                'IsEnabled'    => 1,
                'Format'       => 'CODE128',
                'Width'        => 2,
                'Height'       => 60,
                'ShowValue'    => 1,
                'FontSize'     => 11,
                'LineColor'    => '#000000',
            ];
        }
        // qrcode
        return (object) [
            'IsEnabled'    => 1,
            'Size'         => 100,
            'ErrorLevel'   => 'M',
            'DarkColor'    => '#000000',
            'LightColor'   => '#ffffff',
        ];
    }

}
