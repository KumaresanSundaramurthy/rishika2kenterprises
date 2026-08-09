<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Razorpay — public payment endpoints for customer invoice self-pay.
 * No JWT authentication required — security is provided by Razorpay HMAC-SHA256 signature.
 *
 * Routes:
 *   POST razorpay/createOrder/{token}      — create a Razorpay order for the invoice
 *   POST razorpay/verifyAndRecord/{token}  — verify signature and record payment in ERP
 */
class Razorpay extends CI_Controller {

    public function __construct() {
        parent::__construct();
    }

    /**
     * POST razorpay/createOrder/{token}
     * Creates a Razorpay order for the pending amount of the invoice identified by token.
     *
     * @param string $token  10-char TransToken
     * @returns void
     */
    public function createOrder(string $token = ''): void {
        $out = new stdClass();
        try {
            $token = trim($token);
            if (strlen($token) !== 10) throw new Exception('Invalid invoice link.');

            $this->load->library('Razorpayapi');
            if (!$this->razorpayapi->isConfigured()) {
                throw new Exception('Online payment is not currently enabled for this business.');
            }

            $this->load->model('transactions_model');
            $stub = $this->transactions_model->getTransactionStubByToken($token);

            if (!$stub)                              throw new Exception('Invoice not found.');
            if ((int)$stub->ModuleUID !== 103)       throw new Exception('Online payment is only available for invoices.');
            if (in_array($stub->DocStatus, ['Cancelled', 'Rejected', 'Draft'], true)) {
                throw new Exception('This invoice cannot be paid online.');
            }

            $pendingAmt = max(0.0, round((float)($stub->BalanceAmount ?? 0), 2));
            if ($pendingAmt < 1.0) throw new Exception('This invoice is already fully paid.');

            // Prefill: customer name, mobile, email
            $this->load->model('razorpay_model');
            $custInfo = $this->razorpay_model->getInvoicePartyInfo((int)$stub->TransUID);

            // Org branding for the checkout modal
            $this->load->model('organisation_model');
            $orgInfo = $this->organisation_model->getOrgInfoCached((int)$stub->OrgUID);
            $orgName = htmlspecialchars(
                $orgInfo->Data->BrandName ?? $orgInfo->Data->Name ?? 'Invoice Payment',
                ENT_QUOTES, 'UTF-8'
            );

            // Amount in paise (INR × 100, no decimals)
            $amountPaise = (int)round($pendingAmt * 100);
            $receiptId   = 'r2k_' . preg_replace('/[^A-Za-z0-9]/', '', $stub->UniqueNumber ?? '') . '_' . time();

            $order = $this->razorpayapi->createOrder($amountPaise, $receiptId);

            $out->Error       = false;
            $out->order_id    = $order['id'];
            $out->key_id      = $this->razorpayapi->getKeyId();
            $out->amount      = $amountPaise;
            $out->currency    = 'INR';
            $out->name        = $orgName;
            $out->description = 'Invoice ' . htmlspecialchars($stub->UniqueNumber ?? '', ENT_QUOTES, 'UTF-8');
            $out->prefill     = [
                'name'    => $custInfo->PartyName    ?? '',
                'contact' => $custInfo->MobileNumber ?? '',
                'email'   => $custInfo->EmailAddress ?? '',
            ];
            $out->notes = ['invoice' => $stub->UniqueNumber ?? ''];

        } catch (Exception $e) {
            $out->Error   = true;
            $out->Message = $e->getMessage();
        }

        $this->_json($out);
    }

    /**
     * POST razorpay/verifyAndRecord/{token}
     * Verifies the Razorpay HMAC-SHA256 signature, then records the payment in the ERP.
     *
     * @param string $token  10-char TransToken
     * @returns void
     */
    public function verifyAndRecord(string $token = ''): void {
        $out = new stdClass();
        try {
            $token = trim($token);
            if (strlen($token) !== 10) throw new Exception('Invalid invoice link.');

            $rpOrderId   = trim((string)($this->input->post('razorpay_order_id')  ?? ''));
            $rpPaymentId = trim((string)($this->input->post('razorpay_payment_id') ?? ''));
            $rpSignature = trim((string)($this->input->post('razorpay_signature')  ?? ''));

            if (!$rpOrderId || !$rpPaymentId || !$rpSignature) {
                throw new Exception('Incomplete payment data. Please try again.');
            }

            $this->load->library('Razorpayapi');

            // ── Signature verification — PRIMARY security gate ────────────────
            if (!$this->razorpayapi->verifySignature($rpOrderId, $rpPaymentId, $rpSignature)) {
                log_message('error', '[Razorpay] Signature mismatch for token=' . $token . ' payment=' . $rpPaymentId);
                throw new Exception('Payment verification failed. Please contact support if money was deducted.');
            }

            $this->load->model('transactions_model');
            $stub = $this->transactions_model->getTransactionStubByToken($token);

            if (!$stub)                        throw new Exception('Invoice not found.');
            if ((int)$stub->ModuleUID !== 103) throw new Exception('Invalid document type for payment.');

            $pendingAmt = max(0.0, round((float)($stub->BalanceAmount ?? 0), 2));

            // ── Idempotency: prevent double-recording if browser retries ──────
            $this->load->model('razorpay_model');
            $existing = $this->razorpay_model->getPaymentByRazorpayRef($rpPaymentId, (int)$stub->OrgUID);
            if ($existing) {
                $out->Error      = false;
                $out->Message    = 'Payment already recorded.';
                $out->ReceiptUrl = base_url('receipt/' . $existing->ReceiptToken);
                $this->_json($out);
                return;
            }

            // Invoice fully paid already
            if ($pendingAmt < 0.01) {
                $out->Error   = false;
                $out->Message = 'Invoice is already fully paid.';
                $out->ReceiptUrl = null;
                $this->_json($out);
                return;
            }

            // ── Get customer & payment type ───────────────────────────────────
            $custInfo   = $this->razorpay_model->getInvoicePartyInfo((int)$stub->TransUID);
            if (!$custInfo) throw new Exception('Customer information could not be resolved.');

            $payTypeUID = $this->razorpay_model->getOnlinePaymentTypeUID((int)$stub->OrgUID);
            if (!$payTypeUID) throw new Exception('No online payment type is configured in Settings.');

            // ── Record payment ────────────────────────────────────────────────
            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $receiptToken = $this->transactions_model->_generateReceiptToken();
            $paymentData  = [
                'OrgUID'            => (int)$stub->OrgUID,
                'BranchUID'         => 1,
                'TransUID'          => (int)$stub->TransUID,
                'ModuleUID'         => 110,
                'PartyType'         => 'C',
                'PartyUID'          => (int)$custInfo->PartyUID,
                'PaymentTypeUID'    => (int)$payTypeUID,
                'Amount'            => round($pendingAmt, 2),
                'BankAccountUID'    => null,
                'ReferenceNo'       => $rpPaymentId,
                'Notes'             => 'Online payment via Razorpay',
                'IsFullyPaid'       => 1,
                'ExcessAmount'      => 0,
                'AppliedToTransUID' => null,
                'ReceiptToken'      => $receiptToken,
                'IsActive'          => 1,
                'IsDeleted'         => 0,
                'CreatedBy'         => null,
                'UpdatedBy'         => null,
            ];

            $resp = $this->dbwrite_model->insertData('Transaction', 'PaymentsTbl', $paymentData);
            if ($resp->Error) throw new Exception($resp->Message);

            $this->dbwrite_model->commitTransaction();

            // ── Recalculate customer outstanding balance ───────────────────────
            try {
                $this->load->library('customerbalance');
                $this->customerbalance->recalcAndSync((int)$stub->OrgUID, (int)$custInfo->PartyUID, 0);
            } catch (Throwable $e) {
                log_message('error', '[Razorpay] Balance recalc failed after payment: ' . $e->getMessage());
            }

            $out->Error      = false;
            $out->Message    = 'Payment of ₹' . number_format($pendingAmt, 2) . ' recorded successfully.';
            $out->ReceiptUrl = base_url('receipt/' . $receiptToken);

        } catch (Exception $e) {
            if (isset($this->dbwrite_model)) {
                try { $this->dbwrite_model->rollbackTransaction(); } catch (Throwable $_) {}
            }
            $out->Error   = true;
            $out->Message = $e->getMessage();
        }

        $this->_json($out);
    }

    /**
     * @param object $data
     * @returns void
     */
    private function _json(object $data): void {
        $this->output
            ->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($data))
            ->_display();
        exit;
    }
}
