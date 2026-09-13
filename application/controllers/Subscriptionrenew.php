<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Public subscription renewal controller — no JWT required.
 * Identified by a short-lived Redis token passed as ?sid= in the URL.
 */
class Subscriptionrenew extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('billingplan_model');
    }

    /* ── Renew page ──────────────────────────────────────────────────────── */

    public function index(): void {
        $sid   = trim($this->input->get('sid') ?: '');
        $orgUID = $this->_resolveOrgUID($sid);

        if (!$orgUID) {
            $this->load->view('login/header', ['pageTitle' => 'Invalid Link']);
            $this->load->view('subscription/renew_error');
            $this->load->view('login/footer');
            return;
        }

        $org     = $this->billingplan_model->getOrgByUID($orgUID);
        $sub     = $this->billingplan_model->getOrgSubscription($orgUID);
        $plans   = $this->billingplan_model->getAvailablePlans($orgUID);

        /* Compute remaining seconds from createdAt stored in the Redis token */
        $sessionData   = $this->_getSessionData($sid);
        $createdAt     = $sessionData ? (int)($sessionData->createdAt ?? 0) : 0;
        $remainingSecs = $createdAt > 0 ? max(0, 1800 - (time() - $createdAt)) : 1800;

        $this->load->view('login/header', ['pageTitle' => 'Renew Subscription']);
        $this->load->view('subscription/renew', [
            'sid'          => $sid,
            'org'          => $org,
            'subscription' => (!$sub->Error && $sub->Data) ? $sub->Data : null,
            'plans'        => (!$plans->Error) ? $plans->Data : [],
            'remainingSecs'=> $remainingSecs,
        ]);
        $this->load->view('login/footer');
    }

    /* ── AJAX: create Razorpay order ─────────────────────────────────────── */

    public function createOrder(): void {
        $out = new stdClass();
        try {
            $sid           = trim($this->input->post('sid') ?: '');
            $sectorPlanUID = (int)$this->input->post('sector_plan_uid');

            $orgUID = $this->_resolveOrgUID($sid);
            if (!$orgUID) throw new Exception('Session expired. Please go back to the login page and try again.');

            if ($sectorPlanUID <= 0) throw new Exception('Please select a plan.');

            $this->load->library('Razorpayapi');
            if (!$this->razorpayapi->isConfigured()) {
                throw new Exception('Online payment is not currently enabled. Please contact support.');
            }

            $readDb = $this->load->database('ReadDB', TRUE);
            $readDb->db_debug = FALSE;
            $plan = $readDb->select('SPT.SectorPlanUID, SPT.Price, SP.PlanName, SP.BillingCycle')
                ->from('Billing.SectorPlanTbl AS SPT')
                ->join('Billing.SubscriptionPlansTbl AS SP', 'SP.PlanUID = SPT.PlanUID')
                ->where('SPT.SectorPlanUID', $sectorPlanUID)
                ->where('SPT.IsActive', 1)
                ->limit(1)
                ->get()->row();

            if (!$plan) throw new Exception('Selected plan not found.');

            $org         = $this->billingplan_model->getOrgByUID($orgUID);
            $amountPaise = (int)round((float)$plan->Price * 100);
            if ($amountPaise < 100) throw new Exception('Plan amount is too low for online payment.');

            $receiptId = 'sub_' . $orgUID . '_' . $sectorPlanUID . '_' . time();
            $order     = $this->razorpayapi->createOrder($amountPaise, $receiptId);

            $out->Error       = false;
            $out->order_id    = $order['id'];
            $out->key_id      = $this->razorpayapi->getKeyId();
            $out->amount      = $amountPaise;
            $out->currency    = 'INR';
            $out->name        = $org ? htmlspecialchars($org->Name, ENT_QUOTES, 'UTF-8') : 'Subscription Renewal';
            $out->description = $plan->PlanName . ' — ' . $plan->BillingCycle;
            $out->prefill     = ['name' => $out->name, 'email' => $org->EmailAddress ?? ''];

        } catch (Exception $e) {
            notifyError('Subscriptionrenew::createOrder', $e);
            $out->Error   = true;
            $out->Message = $e->getMessage();
        }
        $this->_json($out);
    }

    /* ── AJAX: verify payment and activate subscription ──────────────────── */

    public function confirmPayment(): void {
        $out = new stdClass();
        try {
            $sid           = trim($this->input->post('sid') ?: '');
            $sectorPlanUID = (int)$this->input->post('sector_plan_uid');
            $rpOrderId     = trim($this->input->post('razorpay_order_id')  ?: '');
            $rpPaymentId   = trim($this->input->post('razorpay_payment_id') ?: '');
            $rpSignature   = trim($this->input->post('razorpay_signature')  ?: '');

            if (!$rpOrderId || !$rpPaymentId || !$rpSignature) {
                throw new Exception('Incomplete payment data. Please try again.');
            }

            $orgUID = $this->_resolveOrgUID($sid);
            if (!$orgUID) throw new Exception('Session expired. Please go back to the login page.');

            $this->load->library('Razorpayapi');
            if (!$this->razorpayapi->verifySignature($rpOrderId, $rpPaymentId, $rpSignature)) {
                throw new Exception('Payment verification failed. If money was deducted, please contact support.');
            }

            /* Get admin role for menu swap */
            $readDb = $this->load->database('ReadDB', TRUE);
            $readDb->db_debug = FALSE;
            $roleRow = $readDb->select('RoleUID')
                ->from('UserRole.RolesTbl')
                ->where('OrgUID',    $orgUID)
                ->where('IsDeleted', 0)
                ->order_by('RoleUID', 'ASC')
                ->limit(1)
                ->get()->row();
            $adminRoleUID = $roleRow ? (int)$roleRow->RoleUID : 0;

            $changeResult = $this->billingplan_model->changePlan(
                $orgUID,
                $sectorPlanUID,
                'Renewal',
                $adminRoleUID,
                0,  /* no user context on public page */
                [
                    'paid'             => true,
                    'mode'             => 'Razorpay',
                    'payment_id'       => $rpPaymentId,
                    'gateway_order_id' => $rpOrderId,
                    'gateway_signature'=> $rpSignature,
                ]
            );

            if ($changeResult->Error) throw new Exception($changeResult->Message);

            /* Invalidate the renewal token so it can't be reused */
            $this->redisservice->deleteCache($this->redisservice->envKey('rnt_' . $this->input->post('sid')));

            $out->Error   = false;
            $out->Message = 'Subscription activated successfully.';

        } catch (Exception $e) {
            notifyError('Subscriptionrenew::confirmPayment', $e);
            $out->Error   = true;
            $out->Message = $e->getMessage();
        }
        $this->_json($out);
    }

    /* ── AJAX: delete renewal token on modal close ───────────────────────── */

    public function cancelToken(): void {
        $out = new stdClass();
        try {
            $sid = trim($this->input->post('sid') ?: '');
            if (empty($sid)) throw new Exception('No session reference provided.');
            $this->redisservice->deleteCache($this->redisservice->envKey('rnt_' . $sid));
            $out->Error   = false;
            $out->Message = 'Token cancelled.';
        } catch (Exception $e) {
            $out->Error   = true;
            $out->Message = $e->getMessage();
        }
        $this->_json($out);
    }

    /* ── Private helpers ──────────────────────────────────────────────────── */

    private function _resolveOrgUID(string $sid): int {
        $data = $this->_getSessionData($sid);
        return $data ? (int)($data->orgUID ?? 0) : 0;
    }

    private function _getSessionData(string $sid): ?object {
        if (empty($sid)) return null;
        $cached = $this->redisservice->getCache($this->redisservice->envKey('rnt_' . $sid));
        if ($cached->Error || $cached->Value === null) return null;
        return is_object($cached->Value) ? $cached->Value : (object)$cached->Value;
    }

    private function _json(object $data): void {
        $this->output
            ->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($data))
            ->_display();
        exit;
    }
}
