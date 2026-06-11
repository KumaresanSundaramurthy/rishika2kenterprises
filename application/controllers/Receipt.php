<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Receipt extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // No JWT / auth middleware — this is a public page
    }

    public function index($token = '') {

        $token = trim($token);

        if (strlen($token) !== 10) {
            $this->_showError('Invalid receipt link.');
            return;
        }

        $this->load->model('transactions_model');
        $this->load->model('organisation_model');

        $payment = $this->transactions_model->getPaymentByReceiptToken($token);

        if (!$payment) {
            $this->_showError('Receipt not found or link has expired.');
            return;
        }

        // Fetch org info
        $orgInfo = $this->organisation_model->getOrgInfoCached($payment->OrgUID);
        $org     = $orgInfo->Data ?? null;

        $this->load->view('receipt/payment', [
            'payment' => $payment,
            'org'     => $org,
        ]);
    }

    private function _showError($message) {
        $this->load->view('receipt/error', ['message' => $message]);
    }

}
