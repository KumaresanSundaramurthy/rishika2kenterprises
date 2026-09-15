<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Signup payment gate — handles Razorpay payment for new paid-plan signups.
 * Requires a valid JWT session (middleware enforced).
 * After payment: OrgSubscriptionTbl.Status moves from PendingPayment → Active.
 * Redirect: manual signup → dashboard, Google signup → onboarding.
 */
class Signuppayment extends CI_Controller {

    public function __construct() {
        parent::__construct();
    }

    /* ── Payment page ─────────────────────────────────────────────────── */

    public function index(): void {
        $jwtData = $this->pageData['JwtData'] ?? null;
        if (!$jwtData) {
            redirect('portal', 'refresh');
            return;
        }

        $orgUID = (int)($jwtData->Org->OrgUID ?? 0);
        $subStatus = $jwtData->Subscription->Status ?? '';

        if ($subStatus !== 'PendingPayment') {
            redirect('dashboard', 'refresh');
            return;
        }

        /* Load plan details from OrgSubscriptionTbl */
        $readDb = $this->load->database('ReadDB', TRUE);
        $readDb->db_debug = FALSE;

        $sub = $readDb
            ->select('OS.OrgSubUID, OS.SectorPlanUID, OS.EndDate, SPT.Price, SPT.DurationDays, SP.PlanName, SP.BillingCycle')
            ->from('Billing.OrgSubscriptionTbl AS OS')
            ->join('Billing.SectorPlanTbl AS SPT', 'SPT.SectorPlanUID = OS.SectorPlanUID', 'left')
            ->join('Billing.SubscriptionPlansTbl AS SP', 'SP.PlanUID = SPT.PlanUID', 'left')
            ->where('OS.OrgUID', $orgUID)
            ->where('OS.Status', 'PendingPayment')
            ->order_by('OS.OrgSubUID', 'DESC')
            ->limit(1)
            ->get()->row();

        if (!$sub) {
            redirect('dashboard', 'refresh');
            return;
        }

        $this->load->view('login/header', ['pageTitle' => 'Complete Your Subscription']);
        $this->load->view('signup/payment', [
            'jwtData'    => $jwtData,
            'sub'        => $sub,
            'orgName'    => $jwtData->Org->OrgName ?? '',
            'orgEmail'   => $jwtData->User->EmailAddress ?? '',
        ]);
        $this->load->view('login/footer');
    }

    /* ── AJAX: create Razorpay order ─────────────────────────────────── */

    public function createOrder(): void {
        $out = new stdClass();
        try {
            $jwtData = $this->pageData['JwtData'] ?? null;
            if (!$jwtData) throw new Exception('Session error.');

            $orgUID        = (int)($jwtData->Org->OrgUID ?? 0);
            $sectorPlanUID = (int)$this->input->post('sector_plan_uid');

            if ($sectorPlanUID <= 0) throw new ValidationException('Invalid plan selected.');

            $readDb = $this->load->database('ReadDB', TRUE);
            $readDb->db_debug = FALSE;
            $plan = $readDb
                ->select('SPT.SectorPlanUID, SPT.Price, SP.PlanName, SP.BillingCycle')
                ->from('Billing.SectorPlanTbl AS SPT')
                ->join('Billing.SubscriptionPlansTbl AS SP', 'SP.PlanUID = SPT.PlanUID')
                ->where('SPT.SectorPlanUID', $sectorPlanUID)
                ->where('SPT.IsActive', 1)
                ->limit(1)
                ->get()->row();

            if (!$plan) throw new ValidationException('Plan not found.');

            $amountPaise = (int)round((float)$plan->Price * 100);
            if ($amountPaise < 100) throw new ValidationException('Plan amount too low for online payment.');

            $this->load->library('Razorpayapi');
            if (!$this->razorpayapi->isConfigured()) {
                throw new Exception('Online payment is not currently enabled. Please contact support.');
            }

            $receiptId = 'sp_' . $orgUID . '_' . $sectorPlanUID . '_' . time();
            $order     = $this->razorpayapi->createOrder($amountPaise, $receiptId);

            $orgName = $jwtData->Org->OrgName ?? 'Your Organisation';

            $out->Error       = false;
            $out->order_id    = $order['id'];
            $out->key_id      = $this->razorpayapi->getKeyId();
            $out->amount      = $amountPaise;
            $out->currency    = 'INR';
            $out->name        = htmlspecialchars($orgName, ENT_QUOTES, 'UTF-8');
            $out->description = $plan->PlanName . ' — ' . $plan->BillingCycle;
            $out->prefill     = [
                'name'  => $out->name,
                'email' => $jwtData->User->EmailAddress ?? '',
            ];

        } catch (ValidationException $e) {
            $out->Error   = true;
            $out->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Signuppayment::createOrder', $e);
            $out->Error   = true;
            $out->Message = 'Could not initiate payment. Please try again.';
        }
        $this->_json($out);
    }

    /* ── AJAX: verify payment and activate subscription ──────────────── */

    public function confirmPayment(): void {
        $out = new stdClass();
        try {
            $jwtData = $this->pageData['JwtData'] ?? null;
            $jwtKey  = $this->pageData['JwtUserKey'] ?? '';
            if (!$jwtData || !$jwtKey) throw new Exception('Session error.');

            $orgUID        = (int)($jwtData->Org->OrgUID ?? 0);
            $sectorPlanUID = (int)$this->input->post('sector_plan_uid');
            $rpOrderId     = trim($this->input->post('razorpay_order_id')   ?: '');
            $rpPaymentId   = trim($this->input->post('razorpay_payment_id') ?: '');
            $rpSignature   = trim($this->input->post('razorpay_signature')  ?: '');

            if (!$rpOrderId || !$rpPaymentId || !$rpSignature) {
                throw new ValidationException('Incomplete payment data. Please try again.');
            }

            $this->load->library('Razorpayapi');
            if (!$this->razorpayapi->verifySignature($rpOrderId, $rpPaymentId, $rpSignature)) {
                throw new ValidationException('Payment verification failed. Contact support if amount was deducted.');
            }

            $this->load->model('dbwrite_model');
            $writeDb = $this->dbwrite_model->getWriteDb();
            $writeDb->db_debug = FALSE;

            /* Activate the subscription */
            $writeDb->where('OrgUID', $orgUID)
                    ->where('Status', 'PendingPayment')
                    ->update('Billing.OrgSubscriptionTbl', ['Status' => 'Active']);

            /* Record payment on the pending order */
            $writeDb->where('OrgUID', $orgUID)
                    ->where('SectorPlanUID', $sectorPlanUID)
                    ->where('Status', 'Pending')
                    ->update('Billing.SubscriptionOrdersTbl', [
                        'Status'            => 'Paid',
                        'RazorpayOrderId'   => $rpOrderId,
                        'RazorpayPaymentId' => $rpPaymentId,
                    ]);

            /* Refresh Redis JWT so middleware sees Active status */
            $cached = $this->redisservice->getCache($jwtKey);
            if (!$cached->Error && $cached->Value !== null) {
                $sessionData = $cached->Value;
                if (isset($sessionData->Subscription)) {
                    $sessionData->Subscription->Status = 'Active';
                }
                $loginExpiry = (int)getenv('LOGIN_EXPIRE_SECS') ?: 86400;
                $this->redisservice->setCache($jwtKey, $sessionData, $loginExpiry);
            }

            /* Redirect: Google signup (IsOnboardingComplete=0) → onboarding; manual → dashboard */
            $isOnboardingDone = (int)($jwtData->Org->IsOnboardingComplete ?? 1);
            $redirect = ($isOnboardingDone === 0)
                ? base_url('onboarding')
                : base_url('dashboard');

            $out->Error    = false;
            $out->Message  = 'Payment verified. Welcome aboard!';
            $out->Redirect = $redirect;

        } catch (ValidationException $e) {
            $out->Error   = true;
            $out->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Signuppayment::confirmPayment', $e);
            $out->Error   = true;
            $out->Message = 'Something went wrong confirming your payment. Please contact support.';
        }
        $this->_json($out);
    }

    /* ── Private helpers ─────────────────────────────────────────────── */

    private function _json(object $data): void {
        $this->output
            ->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($data))
            ->_display();
        exit;
    }
}
