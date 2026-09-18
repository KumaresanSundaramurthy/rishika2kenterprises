<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Unified subscribe page controller — handles plan selection + Razorpay payment
 * for three flows: signup (PendingPayment), renewal (Expired), upgrade (Active).
 * Route: /subscribe  →  signuppayment/index
 */
class Signuppayment extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('signup_model');
        $this->load->model('dbwrite_model');
    }

    /* ── Subscribe page ───────────────────────────────────────────────── */

    public function index(): void {
        $jwtData = $this->pageData['JwtData'] ?? null;
        if (!$jwtData) {
            redirect('portal', 'refresh');
            return;
        }

        $orgUID    = (int)($jwtData->Org->OrgUID ?? 0);
        $subStatus = $jwtData->Subscription->Status ?? '';

        /* Derive flow from subscription status */
        if ($subStatus === 'PendingPayment') {
            $flow = 'signup';
        } elseif ($subStatus === 'Expired') {
            $flow = 'renewal';
        } elseif ($subStatus === 'Active') {
            $flow = 'upgrade';
        } else {
            redirect('dashboard', 'refresh');
            return;
        }

        $readDb = $this->load->database('ReadDB', TRUE);
        $readDb->db_debug = FALSE;

        /* Current subscription row */
        $statusFilter = match($flow) {
            'signup'  => 'PendingPayment',
            'renewal' => 'Expired',
            default   => 'Active',
        };
        $sub = $readDb
            ->select('OS.OrgSubUID, OS.SectorPlanUID, OS.EndDate, SPT.Price, SPT.DurationDays, SP.PlanName, SP.BillingCycle')
            ->from('Billing.OrgSubscriptionTbl AS OS')
            ->join('Billing.SectorPlanTbl AS SPT', 'SPT.SectorPlanUID = OS.SectorPlanUID', 'left')
            ->join('Billing.SubscriptionPlansTbl AS SP', 'SP.PlanUID = SPT.PlanUID', 'left')
            ->where('OS.OrgUID', $orgUID)
            ->where('OS.Status', $statusFilter)
            ->order_by('OS.OrgSubUID', 'DESC')
            ->limit(1)
            ->get()->row();

        if (!$sub && $flow !== 'upgrade') {
            redirect('dashboard', 'refresh');
            return;
        }

        /* All available plans for this org's sector */
        $plans = $readDb
            ->select('SPT.SectorPlanUID, SPT.Price, SPT.DurationDays, SP.PlanName, SP.BillingCycle')
            ->from('Billing.SectorPlanTbl AS SPT')
            ->join('Billing.SubscriptionPlansTbl AS SP', 'SP.PlanUID = SPT.PlanUID')
            ->join('Organisation.OrganisationTbl AS O',  'O.SectorUID = SPT.SectorUID')
            ->where('O.OrgUID',     $orgUID)
            ->where('SPT.IsActive', 1)
            ->order_by('SPT.Price', 'ASC')
            ->get()->result();

        $pageTitle = match($flow) {
            'renewal' => 'Renew Your Subscription',
            'upgrade' => 'Upgrade Your Plan',
            default   => 'Choose Your Plan',
        };

        $this->load->view('login/header', ['pageTitle' => $pageTitle]);
        $this->load->view('signup/subscribe', [
            'jwtData'  => $jwtData,
            'sub'      => $sub,
            'plans'    => $plans,
            'flow'     => $flow,
            'orgName'  => $jwtData->Org->OrgName ?? '',
            'orgEmail' => $jwtData->User->EmailAddress ?? '',
        ]);
        $this->load->view('login/footer');
    }

    /* ── AJAX: change plan selection before payment ───────────────────── */

    public function changePlan(): void {
        $out = new stdClass();
        try {
            $jwtData = $this->pageData['JwtData'] ?? null;
            $jwtKey  = $this->pageData['JwtUserKey'] ?? '';
            if (!$jwtData) throw new Exception('Session error.');

            $orgUID     = (int)($jwtData->Org->OrgUID ?? 0);
            $newPlanUID = (int)$this->input->post('sector_plan_uid');

            if ($newPlanUID <= 0) throw new ValidationException('Please select a plan.');

            $subStatus = $jwtData->Subscription->Status ?? '';
            $flow = match(true) {
                $subStatus === 'PendingPayment' => 'signup',
                $subStatus === 'Expired'        => 'renewal',
                $subStatus === 'Active'         => 'upgrade',
                default                          => throw new ValidationException('Invalid subscription state.'),
            };

            /* 1. Load full plan details */
            $readDb = $this->load->database('ReadDB', TRUE);
            $readDb->db_debug = FALSE;
            $plan = $readDb->select('SPT.SectorPlanUID, SPT.Price, SPT.DurationDays, SP.PlanName, SP.BillingCycle')
                ->from('Billing.SectorPlanTbl AS SPT')
                ->join('Billing.SubscriptionPlansTbl AS SP', 'SP.PlanUID = SPT.PlanUID')
                ->where('SPT.SectorPlanUID', $newPlanUID)
                ->where('SPT.IsActive', 1)
                ->limit(1)
                ->get()->row();
            if (!$plan) throw new ValidationException('Plan not found.');

            /* 3. Get the most recent non-cancelled subscription row */
            $subRow = $readDb->select('OrgSubUID')
                ->from('Billing.OrgSubscriptionTbl')
                ->where('OrgUID', $orgUID)
                ->where_not_in('Status', ['Cancelled'])
                ->order_by('OrgSubUID', 'DESC')
                ->limit(1)->get()->row();

            $isPaid  = ((float)$plan->Price > 0);
            $endDate = gmdate('Y-m-d H:i:s', time() + max(1, (int)$plan->DurationDays) * 86400);

            /* 4. Update subscription row SectorPlanUID + EndDate */
            $writeDb = $this->dbwrite_model->getWriteDb();
            $writeDb->db_debug = FALSE;

            /* For signup: always stamp the correct status so the gate stays consistent
               regardless of what the row contained before this plan switch. */
            $subUpdate = ['SectorPlanUID' => $newPlanUID, 'EndDate' => $endDate];
            if ($flow === 'signup') {
                $subUpdate['Status'] = $isPaid ? 'PendingPayment' : 'Active';
            } elseif (!$isPaid) {
                $subUpdate['Status'] = 'Active';
            }

            if ($subRow) {
                $writeDb->where('OrgSubUID', (int)$subRow->OrgSubUID)
                        ->update('Billing.OrgSubscriptionTbl', $subUpdate);
            }

            /* 5. For signup flow: also update the pending order row (if exists) */
            if ($flow === 'signup') {
                $writeDb->where('OrgUID', $orgUID)
                        ->where_in('Status', ['Pending', 'Waived'])
                        ->update('Billing.SubscriptionOrdersTbl', [
                            'SectorPlanUID' => $newPlanUID,
                            'Amount'        => $isPaid ? (float)$plan->Price : 0.00,
                            'NetAmount'     => $isPaid ? (float)$plan->Price : 0.00,
                            'Status'        => $isPaid ? 'Pending' : 'Waived',
                        ]);
            }

            /* 6. Update JWT cache to match the new subscription state */
            $redirect = null;
            if ($jwtKey) {
                $cached = $this->redisservice->getCache($jwtKey);
                if (!$cached->Error && $cached->Value !== null) {
                    $sessionData = $cached->Value;
                    if (isset($sessionData->Subscription)) {
                        $sessionData->Subscription->SectorPlanUID = $newPlanUID;
                        if ($flow === 'signup') {
                            $sessionData->Subscription->Status = $isPaid ? 'PendingPayment' : 'Active';
                        } elseif (!$isPaid) {
                            $sessionData->Subscription->Status = 'Active';
                        }
                    }
                    $loginExpiry = (int)getenv('LOGIN_EXPIRE_SECS') ?: 86400;
                    $this->redisservice->setCache($jwtKey, $sessionData, $loginExpiry);
                }
                if (!$isPaid) {
                    /* Free plan confirmed — apply module filter immediately.
                       Paid flow defers this to verifyPayment() after Razorpay callback. */
                    $filterResult = $this->signup_model->applyPlanMenuFilter($orgUID, $newPlanUID);
                    if ($filterResult->Error) throw new Exception($filterResult->Message ?? 'Menu filter failed.');

                    $redirect = $this->_redirectForFlow($flow, $jwtData);
                }
            }

            $out->Error         = false;
            $out->IsFree        = !$isPaid;
            $out->SectorPlanUID = $newPlanUID;
            $out->PlanName      = $plan->PlanName;
            $out->BillingCycle  = $plan->BillingCycle;
            $out->Price         = (float)$plan->Price;
            $out->Redirect      = $redirect;

        } catch (ValidationException $e) {
            $out->Error   = true;
            $out->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Signuppayment::changePlan', $e);
            $out->Error   = true;
            $out->Message = 'Something went wrong. Please try again.';
        }
        $this->_json($out);
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

            $receiptId  = 'sub_' . $orgUID . '_' . $sectorPlanUID . '_' . time();
            $orderNotes = [
                'type'            => 'subscription',
                'org_uid'         => (string)$orgUID,
                'sector_plan_uid' => (string)$sectorPlanUID,
            ];
            $order   = $this->razorpayapi->createOrder($amountPaise, $receiptId, 'INR', $orderNotes);
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

            /* Verify Razorpay signature */
            $this->load->library('Razorpayapi');
            if (!$this->razorpayapi->verifySignature($rpOrderId, $rpPaymentId, $rpSignature)) {
                throw new ValidationException('Payment verification failed. Contact support if amount was deducted.');
            }

            /* Derive flow from JWT subscription status (status before payment) */
            $subStatus = $jwtData->Subscription->Status ?? '';
            $flow = match(true) {
                $subStatus === 'PendingPayment' => 'signup',
                $subStatus === 'Expired'        => 'renewal',
                $subStatus === 'Active'         => 'upgrade',
                default                          => 'signup',
            };

            $readDb = $this->load->database('ReadDB', TRUE);
            $readDb->db_debug = FALSE;

            /* Load plan details */
            $plan = $readDb->select('SPT.Price, SPT.DurationDays, SP.PlanName')
                ->from('Billing.SectorPlanTbl AS SPT')
                ->join('Billing.SubscriptionPlansTbl AS SP', 'SP.PlanUID = SPT.PlanUID')
                ->where('SPT.SectorPlanUID', $sectorPlanUID)
                ->limit(1)->get()->row();
            if (!$plan) throw new ValidationException('Plan not found.');

            /* Get current subscription row */
            $subRow = $readDb->select('OrgSubUID')
                ->from('Billing.OrgSubscriptionTbl')
                ->where('OrgUID', $orgUID)
                ->where_not_in('Status', ['Cancelled'])
                ->order_by('OrgSubUID', 'DESC')
                ->limit(1)->get()->row();
            if (!$subRow) throw new Exception('Subscription record not found.');

            /* Apply menu filter now that payment is confirmed — deferred from changePlan()
               so plan switching on the payment page is instant (no heavy DB writes per click). */
            $filterResult = $this->signup_model->applyPlanMenuFilter($orgUID, $sectorPlanUID);
            if ($filterResult->Error) throw new Exception($filterResult->Message ?? 'Menu filter failed.');

            $writeDb = $this->dbwrite_model->getWriteDb();
            $writeDb->db_debug = FALSE;
            $now     = gmdate('Y-m-d H:i:s');
            $endDate = gmdate('Y-m-d H:i:s', time() + max(1, (int)$plan->DurationDays) * 86400);
            $orgSubUID = (int)$subRow->OrgSubUID;

            /* ── Per-flow activation ──────────────────────────────────── */
            if ($flow === 'signup') {
                /* Activate the pending subscription */
                $writeDb->where('OrgSubUID', $orgSubUID)
                        ->update('Billing.OrgSubscriptionTbl', ['Status' => 'Active']);

                /* Mark the pre-created Pending order as Paid */
                $writeDb->where('OrgUID',  $orgUID)
                        ->where('Status', 'Pending')
                        ->update('Billing.SubscriptionOrdersTbl', [
                            'Status'            => 'Paid',
                            'PaidOn'            => $now,
                            'PaymentMode'       => 'Razorpay',
                            'RazorpayOrderId'   => $rpOrderId,
                            'RazorpayPaymentId' => $rpPaymentId,
                        ]);

            } elseif ($flow === 'renewal') {
                /* Reactivate expired subscription with new dates */
                $writeDb->where('OrgSubUID', $orgSubUID)
                        ->update('Billing.OrgSubscriptionTbl', [
                            'SectorPlanUID' => $sectorPlanUID,
                            'Status'        => 'Active',
                            'StartDate'     => $now,
                            'EndDate'       => $endDate,
                        ]);

                /* Create renewal order + payment record */
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
                    'RazorpayOrderId'   => $rpOrderId,
                    'RazorpayPaymentId' => $rpPaymentId,
                    'CreatedBy'         => null,
                ]);

            } elseif ($flow === 'upgrade') {
                /* SectorPlanUID already updated by changePlan(); just record the payment */
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
                    'RazorpayOrderId'   => $rpOrderId,
                    'RazorpayPaymentId' => $rpPaymentId,
                    'CreatedBy'         => null,
                ]);
            }

            /* Update JWT cache — Status=Active + new SectorPlanUID */
            $cached = $this->redisservice->getCache($jwtKey);
            if (!$cached->Error && $cached->Value !== null) {
                $sessionData = $cached->Value;
                if (isset($sessionData->Subscription)) {
                    $sessionData->Subscription->Status        = 'Active';
                    $sessionData->Subscription->SectorPlanUID = $sectorPlanUID;
                }
                $loginExpiry = (int)getenv('LOGIN_EXPIRE_SECS') ?: 86400;
                $this->redisservice->setCache($jwtKey, $sessionData, $loginExpiry);
            }

            $out->Error    = false;
            $out->Message  = 'Payment verified. Your subscription is now active!';
            $out->Redirect = $this->_redirectForFlow($flow, $jwtData);

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

    private function _redirectForFlow(string $flow, object $jwtData): string {
        if ($flow === 'upgrade') {
            return base_url('subscription/dashboard');
        }
        if ($flow === 'renewal') {
            return base_url('dashboard');
        }
        /* signup */
        $isOnboardingDone = (int)($jwtData->Org->IsOnboardingComplete ?? 1);
        return ($isOnboardingDone === 0) ? base_url('onboarding') : base_url('dashboard');
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
