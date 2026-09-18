<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Pay — public invoice payment page. No JWT auth required.
 * Security is enforced by Razorpay HMAC-SHA256 signature on the verify step.
 *
 * Route: GET /pay/{token}  →  pay/index/{token}
 */
class Pay extends CI_Controller {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Renders the payment summary page for an invoice identified by its TransToken.
     *
     * @param string $token  10-char TransToken
     * @returns void
     */
    public function index(string $token = ''): void {

        $token = trim($token);

        if (strlen($token) !== 10) {
            $this->_showError('Invalid payment link.');
            return;
        }

        $this->load->model('transactions_model');
        $stub = $this->transactions_model->getTransactionStubByToken($token);

        if (!$stub) {
            $this->_showError('Invoice not found or this payment link has expired.');
            return;
        }

        if ((int)$stub->ModuleUID !== 103) {
            $this->_showError('This link is not valid for an invoice payment.');
            return;
        }

        if (in_array($stub->DocStatus, ['Cancelled', 'Rejected', 'Draft'], true)) {
            $this->_showError(
                'This invoice cannot be paid online (status: ' .
                htmlspecialchars($stub->DocStatus, ENT_QUOTES, 'UTF-8') . ').'
            );
            return;
        }

        $this->load->model('razorpay_model');
        $this->load->model('organisation_model');
        $this->load->library('Razorpayapi');

        $custInfo     = $this->razorpay_model->getInvoicePartyInfo((int)$stub->TransUID);
        $orgResult    = $this->organisation_model->getOrgInfoCached((int)$stub->OrgUID);
        $org          = $orgResult->Data ?? null;
        $netAmount    = max(0.0, round((float)($stub->NetAmount    ?? 0), 2));
        $alreadyPaid  = max(0.0, round((float)($stub->PaidAmount   ?? 0), 2));
        $balance      = max(0.0, round((float)($stub->BalanceAmount ?? 0), 2));
        $isFullyPaid  = ($balance < 1.0);
        $rzpEnabled   = $this->razorpayapi->isConfigured();

        $this->load->view('pay/index', [
            'token'       => $token,
            'stub'        => $stub,
            'custInfo'    => $custInfo,
            'org'         => $org,
            'netAmount'   => $netAmount,
            'alreadyPaid' => $alreadyPaid,
            'balance'     => $balance,
            'isFullyPaid' => $isFullyPaid,
            'rzpEnabled'  => $rzpEnabled,
        ]);
    }

    /**
     * @param string $message
     * @returns void
     */
    private function _showError(string $message): void {
        $this->load->view('pay/error', ['message' => $message]);
    }
}
