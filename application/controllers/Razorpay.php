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

            // Amount in paise (INR Ã— 100, no decimals)
            $amountPaise = (int)round($pendingAmt * 100);
            $receiptId   = 'r2k_' . preg_replace('/[^A-Za-z0-9]/', '', $stub->UniqueNumber ?? '') . '_' . time();

            /* Include trans_token in notes so the webhook can recover this payment
               if the browser callback never reaches verifyAndRecord. */
            $orderNotes = [
                'type'        => 'invoice',
                'trans_token' => $token,
                'invoice'     => $stub->UniqueNumber ?? '',
            ];
            $order = $this->razorpayapi->createOrder($amountPaise, $receiptId, 'INR', $orderNotes);

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
            $out->notes = $orderNotes;

        } catch (Exception $e) {
            notifyError('Razorpay::createOrder', $e);
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
            }

            $out->Error      = false;
            $out->Message    = 'Payment of â‚¹' . smartDecimal($pendingAmt) . ' recorded successfully.';
            $out->ReceiptUrl = base_url('receipt/' . $receiptToken);

        } catch (Exception $e) {
            notifyError('Razorpay::verifyAndRecord', $e);
            if (isset($this->dbwrite_model)) {
                try { $this->dbwrite_model->rollbackTransaction(); } catch (Throwable $_) {}
            }
            $out->Error   = true;
            $out->Message = $e->getMessage();
        }

        $this->_json($out);
    }

    /**
     * POST razorpay/webhook
     * Razorpay server-to-server event delivery. No JWT required.
     * Secured by HMAC-SHA256 signature verified against RAZORPAY_WEBHOOK_SECRET.
     *
     * Handles:
     *   payment.captured  — safety net if browser verifyAndRecord never fired
     *   payment.failed    — logged only, no action
     *   all others        — acknowledged silently
     *
     * IMPORTANT: always returns HTTP 200. Razorpay retries on any non-2xx response.
     *
     * @returns void
     */
    public function webhook(): void {

        $rawBody   = (string)file_get_contents('php://input');
        $signature = (string)($_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '');

        $this->load->library('Razorpayapi');

        /* ── Signature verification — primary security gate ───────── */
        if (!$this->razorpayapi->verifyWebhookSignature($rawBody, $signature)) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json', 'utf-8')
                ->set_output('{"status":"invalid_signature"}')
                ->_display();
            exit;
        }

        $event = json_decode($rawBody, true);

        if (!is_array($event)) {
            $this->_webhookOk();
            return;
        }

        $eventType = (string)($event['event'] ?? '');

        try {
            if ($eventType === 'payment.captured') {
                $this->_handlePaymentCaptured($event);
            }
            /* payment.failed: no action needed — Razorpay already notified the user */
        } catch (Throwable $ex) {
            notifyError('Razorpay::webhook[' . $eventType . ']', $ex);
        }

        $this->_webhookOk();
    }

    /**
     * Handles a payment.captured webhook event.
     * Routes to the invoice or subscription handler based on the order notes.
     *
     * @param array $event
     * @returns void
     */
    private function _handlePaymentCaptured(array $event): void {

        $entity    = $event['payload']['payment']['entity'] ?? [];
        $paymentId = (string)($entity['id']       ?? '');
        $orderId   = (string)($entity['order_id'] ?? '');
        $notes     = is_array($entity['notes'] ?? null) ? $entity['notes'] : [];

        if ($paymentId === '') return;

        $noteType = (string)($notes['type'] ?? '');

        /* ── Invoice payment ───────────────────────────────────────── */
        if ($noteType === 'invoice' && !empty($notes['trans_token'])) {
            $this->_webhookRecordInvoicePayment(
                (string)$notes['trans_token'],
                $orderId,
                $paymentId
            );
            return;
        }

        /* ── Subscription payment ───────────────────────────────────
           Notes embedded in createOrder() carry org_uid + sector_plan_uid
           so the webhook can fully recover without browser context. */
        if ($noteType === 'subscription' && !empty($notes['org_uid'])) {
            $this->_webhookRecordSubscriptionPayment(
                (int)$notes['org_uid'],
                (int)($notes['sector_plan_uid'] ?? 0),
                $orderId,
                $paymentId
            );
            return;
        }

        /* ── Unknown payment — last-resort idempotency check ────────
           Covers orders created before notes were added to createOrder(). */
        $this->load->model('razorpay_model');
        if ($this->razorpay_model->getSubscriptionOrderByPaymentId($paymentId)) return;

        notifyError('Razorpay::webhook[unknown_payment]', new Exception(
            'Unrecognized payment captured. Manual verification needed.' . PHP_EOL .
            'PaymentID: ' . $paymentId . PHP_EOL .
            'OrderID: '   . $orderId
        ));
    }

    /**
     * Recovers a subscription payment captured by Razorpay when the browser's
     * confirmPayment() call never completed (tab closed, network drop, etc.).
     *
     * Mirrors the per-flow logic in Signuppayment::confirmPayment() exactly.
     * Redis JWT cache is NOT updated here — the user's next login refreshes it.
     *
     * @param int    $orgUID
     * @param int    $sectorPlanUID
     * @param string $orderId    Razorpay order ID
     * @param string $paymentId  Razorpay payment ID
     * @returns void
     */
    private function _webhookRecordSubscriptionPayment(int $orgUID, int $sectorPlanUID, string $orderId, string $paymentId): void {

        $this->load->model('razorpay_model');

        /* Idempotency — already recorded by browser confirmPayment() */
        if ($this->razorpay_model->getSubscriptionOrderByPaymentId($paymentId)) return;

        if ($orgUID <= 0 || $sectorPlanUID <= 0) {
            throw new Exception('Invalid orgUID or sectorPlanUID in webhook notes.');
        }

        $subRow = $this->razorpay_model->getSubscriptionRow($orgUID);
        if (!$subRow) throw new Exception('Subscription row not found. OrgUID=' . $orgUID);

        $flow = match($subRow->Status) {
            'PendingPayment' => 'signup',
            'Expired'        => 'renewal',
            'Active'         => 'upgrade',
            default          => throw new Exception('Unexpected subscription status: ' . $subRow->Status . ' OrgUID=' . $orgUID),
        };

        $plan = $this->razorpay_model->getSubscriptionPlanDetails($sectorPlanUID);
        if (!$plan) throw new Exception('Plan not found. SectorPlanUID=' . $sectorPlanUID);

        /* Apply plan menu filter — same step confirmPayment() runs after signature verify */
        $this->load->model('signup_model');
        $filterResult = $this->signup_model->applyPlanMenuFilter($orgUID, $sectorPlanUID);
        if ($filterResult->Error) throw new Exception('Menu filter failed: ' . ($filterResult->Message ?? ''));

        $this->load->model('dbwrite_model');
        $writeDb = $this->dbwrite_model->getWriteDb();
        $writeDb->db_debug = FALSE;

        $now       = gmdate('Y-m-d H:i:s');
        $endDate   = gmdate('Y-m-d H:i:s', time() + max(1, (int)$plan->DurationDays) * 86400);
        $orgSubUID = (int)$subRow->OrgSubUID;

        if ($flow === 'signup') {

            $writeDb->where('OrgSubUID', $orgSubUID)
                    ->update('Billing.OrgSubscriptionTbl', ['Status' => 'Active']);

            $writeDb->where('OrgUID',  $orgUID)
                    ->where('Status', 'Pending')
                    ->update('Billing.SubscriptionOrdersTbl', [
                        'Status'            => 'Paid',
                        'PaidOn'            => $now,
                        'PaymentMode'       => 'Razorpay',
                        'RazorpayOrderId'   => $orderId,
                        'RazorpayPaymentId' => $paymentId,
                    ]);

        } elseif ($flow === 'renewal') {

            $writeDb->where('OrgSubUID', $orgSubUID)
                    ->update('Billing.OrgSubscriptionTbl', [
                        'SectorPlanUID' => $sectorPlanUID,
                        'Status'        => 'Active',
                        'StartDate'     => $now,
                        'EndDate'       => $endDate,
                    ]);

            $writeDb->insert('Billing.SubscriptionOrdersTbl', [
                'OrgUID'            => $orgUID,
                'SectorPlanUID'     => $sectorPlanUID,
                'OrgSubUID'         => $orgSubUID,
                'RenewalType'       => 'Renewal',
                'DueDate'           => $endDate,
                'Amount'            => (float)$plan->Price,
                'DiscountAmount'    => 0.00,
                'TaxAmount'         => 0.00,
                'NetAmount'         => (float)$plan->Price,
                'FinancialYear'     => billing_fy('long'),
                'Status'            => 'Paid',
                'PaidOn'            => $now,
                'PaymentMode'       => 'Razorpay',
                'RazorpayOrderId'   => $orderId,
                'RazorpayPaymentId' => $paymentId,
                'CreatedBy'         => null,
            ]);

        } elseif ($flow === 'upgrade') {

            $writeDb->insert('Billing.SubscriptionOrdersTbl', [
                'OrgUID'            => $orgUID,
                'SectorPlanUID'     => $sectorPlanUID,
                'OrgSubUID'         => $orgSubUID,
                'RenewalType'       => 'Upgrade',
                'DueDate'           => $now,
                'Amount'            => (float)$plan->Price,
                'DiscountAmount'    => 0.00,
                'TaxAmount'         => 0.00,
                'NetAmount'         => (float)$plan->Price,
                'FinancialYear'     => billing_fy('long'),
                'Status'            => 'Paid',
                'PaidOn'            => $now,
                'PaymentMode'       => 'Razorpay',
                'RazorpayOrderId'   => $orderId,
                'RazorpayPaymentId' => $paymentId,
                'CreatedBy'         => null,
            ]);
        }
    }

    /**
     * Records an invoice payment that was captured by Razorpay but whose
     * browser-side verifyAndRecord call never completed (tab closed, network drop).
     *
     * @param string $token      10-char TransToken
     * @param string $orderId    Razorpay order ID
     * @param string $paymentId  Razorpay payment ID
     * @returns void
     */
    private function _webhookRecordInvoicePayment(string $token, string $orderId, string $paymentId): void {

        $this->load->model('transactions_model');
        $this->load->model('razorpay_model');

        $stub = $this->transactions_model->getTransactionStubByToken($token);
        if (!$stub || (int)$stub->ModuleUID !== 103) return;

        /* Idempotency — payment already recorded by the browser callback */
        $existing = $this->razorpay_model->getPaymentByRazorpayRef($paymentId, (int)$stub->OrgUID);
        if ($existing) return;

        $pendingAmt = max(0.0, round((float)($stub->BalanceAmount ?? 0), 2));
        if ($pendingAmt < 0.01) return;

        $custInfo = $this->razorpay_model->getInvoicePartyInfo((int)$stub->TransUID);
        if (!$custInfo) throw new Exception('Customer not found. TransUID=' . $stub->TransUID);

        $payTypeUID = $this->razorpay_model->getOnlinePaymentTypeUID((int)$stub->OrgUID);
        if (!$payTypeUID) throw new Exception('No online payment type configured. OrgUID=' . $stub->OrgUID);

        $this->load->model('dbwrite_model');
        $this->dbwrite_model->startTransaction();

        $receiptToken = $this->transactions_model->_generateReceiptToken();

        $resp = $this->dbwrite_model->insertData('Transaction', 'PaymentsTbl', [
            'OrgUID'            => (int)$stub->OrgUID,
            'BranchUID'         => 1,
            'TransUID'          => (int)$stub->TransUID,
            'ModuleUID'         => 110,
            'PartyType'         => 'C',
            'PartyUID'          => (int)$custInfo->PartyUID,
            'PaymentTypeUID'    => (int)$payTypeUID,
            'Amount'            => round($pendingAmt, 2),
            'BankAccountUID'    => null,
            'ReferenceNo'       => $paymentId,
            'Notes'             => 'Online payment via Razorpay (webhook recovery)',
            'IsFullyPaid'       => 1,
            'ExcessAmount'      => 0,
            'AppliedToTransUID' => null,
            'ReceiptToken'      => $receiptToken,
            'IsActive'          => 1,
            'IsDeleted'         => 0,
            'CreatedBy'         => null,
            'UpdatedBy'         => null,
        ]);

        if ($resp->Error) throw new Exception($resp->Message);

        $this->dbwrite_model->commitTransaction();

        /* Recalculate customer outstanding balance */
        try {
            $this->load->library('customerbalance');
            $this->customerbalance->recalcAndSync((int)$stub->OrgUID, (int)$custInfo->PartyUID, 0);
        } catch (Throwable $_) {}
    }

    /**
     * @returns void
     */
    private function _webhookOk(): void {
        $this->output
            ->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output('{"status":"ok"}')
            ->_display();
        exit;
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
