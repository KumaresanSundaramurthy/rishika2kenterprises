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

        /* Current subscription row */
        $statusFilter = match($flow) {
            'signup'  => 'PendingPayment',
            'renewal' => 'Expired',
            default   => 'Active',
        };
        $sub = $this->signup_model->getOrgPlanForPayment($orgUID, $statusFilter);

        if (!$sub && $flow !== 'upgrade') {
            redirect('dashboard', 'refresh');
            return;
        }

        /* All available plans for this org's sector */
        $plans = $this->signup_model->getAvailablePlansForOrg($orgUID);

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

    /* ── AJAX: store payment intent in Redis and return redirect token ── */

    public function preparePayment(): void {
        $out = new stdClass();
        try {
            $jwtData = $this->pageData['JwtData'] ?? null;
            if (!$jwtData) throw new Exception('Session error.');

            $orgUID        = (int)($jwtData->Org->OrgUID ?? 0);
            $sectorPlanUID = (int)$this->input->post('sector_plan_uid');

            if ($sectorPlanUID <= 0) throw new ValidationException('Please select a plan.');

            $subStatus = $jwtData->Subscription->Status ?? '';

            $flow = match(true) {
                $subStatus === 'PendingPayment' => 'signup',
                $subStatus === 'Expired'        => 'renewal',
                $subStatus === 'Active'         => 'upgrade',
                default                          => throw new ValidationException('Invalid subscription state.'),
            };

            $plan = $this->signup_model->getSectorPlan($sectorPlanUID);
            if (!$plan) throw new ValidationException('Plan not found.');
            if ((float)$plan->Price <= 0) throw new ValidationException('Free plan does not require payment.');

            $token    = bin2hex(random_bytes(16));
            $redisKey = 'sub_pay_' . $orgUID . '_' . $token;

            $payload = new stdClass();
            $payload->org_uid         = $orgUID;
            $payload->sector_plan_uid = $sectorPlanUID;
            $payload->flow            = $flow;
            $payload->back_url        = 'subscribe';

            $cacheResult = $this->redisservice->setCache($redisKey, $payload, 86400);
            if (!empty($cacheResult->Error)) {
                throw new Exception('Could not prepare payment. Please try again.');
            }

            $out->Error    = false;
            $out->Redirect = '/billing/checkout?t=' . $token;

        } catch (ValidationException $e) {
            $out->Error   = true;
            $out->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Signuppayment::preparePayment', $e);
            $out->Error   = true;
            $out->Message = 'Could not prepare payment. Please try again.';
        }
        $this->_json($out);
    }

    /* ── AJAX: change plan selection before payment ───────────────────── */

    public function changePlan(): void {
        $out = new stdClass();
        try {
            $jwtData = $this->pageData['JwtData'] ?? null;
            $jwtKey  = $this->pageData['JwtUserKey'] ?? '';
            if (!$jwtData) throw new Exception('Session error.');

            $orgUID     = (int)($jwtData->Org->OrgUID     ?? 0);
            $userUID    = (int)($jwtData->User->UserUID   ?? 0);
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
            $plan = $this->signup_model->getSectorPlan($newPlanUID);
            if (!$plan) throw new ValidationException('Plan not found.');

            /* 2. Get the most recent non-cancelled subscription row */
            $subRow = $this->signup_model->getOrgSubUID($orgUID);

            $isPaid  = ((float)$plan->Price > 0);
            $_ts = time() + max(1, (int)$plan->DurationDays) * 86400;
            [$_y, $_m, $_d] = explode('-', gmdate('Y-m-d', $_ts + 19800));
            $endDate = gmdate('Y-m-d H:i:s', gmmktime(23, 59, 59, (int)$_m, (int)$_d, (int)$_y) - 19800);

            /* 3. Update subscription row SectorPlanUID + EndDate */
            /* For signup: always stamp the correct status so the gate stays consistent
               regardless of what the row contained before this plan switch. */
            $subUpdate = ['SectorPlanUID' => $newPlanUID, 'EndDate' => $endDate];
            if ($flow === 'signup') {
                $subUpdate['Status'] = $isPaid ? 'PendingPayment' : 'Active';
            } elseif (!$isPaid) {
                $subUpdate['Status'] = 'Active';
            }

            if ($subRow) {
                $this->dbwrite_model->updateData('Billing', 'OrgSubscriptionTbl', $subUpdate, ['OrgSubUID' => (int)$subRow->OrgSubUID]);
            }

            /* 4. For signup flow: update or create the order row with correct amounts */
            $orderUID = 0;
            if ($flow === 'signup') {
                $existingOrderUID = $subRow ? (int)($subRow->OrderUID ?? 0) : 0;

                $orderData = [
                    'SectorPlanUID'  => $newPlanUID,
                    'RenewalType'    => $isPaid ? 'New' : 'Trial',
                    'Amount'         => $isPaid ? (float)$plan->TaxableAmount : 0.00,
                    'DiscountAmount' => 0.00,
                    'TaxAmount'      => $isPaid ? (float)$plan->TaxAmount : 0.00,
                    'NetAmount'      => $isPaid ? (float)$plan->TotalAmount : 0.00,
                    'Status'         => $isPaid ? 'Pending' : 'Waived',
                    'IsPaid'         => $isPaid ? 0 : 1,
                ];

                if ($existingOrderUID > 0) {
                    /* OrderUID has a value — update that order */
                    $this->dbwrite_model->updateData('Billing', 'SubscriptionOrdersTbl',
                        $orderData, ['OrderUID' => $existingOrderUID]);
                    $orderUID = $existingOrderUID;
                } else {
                    /* OrderUID is NULL — always insert a fresh order */
                    $rOrder = $this->dbwrite_model->insertData('Billing', 'SubscriptionOrdersTbl',
                        $orderData + [
                            'OrgUID'        => $orgUID,
                            'OrgSubUID'     => $subRow ? (int)$subRow->OrgSubUID : null,
                            'FinancialYear' => billing_fy('long'),
                            'DueDate'       => $endDate,
                            'CreatedBy'     => null,
                        ]);
                    if (!$rOrder->Error) {
                        $orderUID = (int)$rOrder->ID;
                        /* Link the new order onto OrgSubscriptionTbl */
                        if ($orderUID > 0 && $subRow) {
                            $this->dbwrite_model->updateData('Billing', 'OrgSubscriptionTbl',
                                ['OrderUID' => $orderUID], ['OrgSubUID' => (int)$subRow->OrgSubUID]);
                        }
                    }
                }
            }

            /* 5. Update JWT cache to match the new subscription state */
            $redirect = null;
            if ($jwtKey) {
                $cached = $this->redisservice->getCache($jwtKey);
                if (!$cached->Error && $cached->Value !== null) {
                    $sessionData = $cached->Value;
                    if (isset($sessionData->Subscription)) {
                        $sessionData->Subscription->SectorPlanUID = $newPlanUID;
                        $sessionData->Subscription->EndDate       = $endDate;
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

                    /* Refresh Redis menu/submenu cache so sidebar reflects updated modules immediately */
                    $this->_refreshMenuCache($jwtData, $userUID, $orgUID);

                    /* Insert order/payment/invoice (once only — guard against duplicate on plan re-switch) */
                    if ($orderUID > 0 && !$this->signup_model->orderHasPayment($orderUID)) {
                        $this->signup_model->createPaymentAndInvoice(
                            $orgUID, $orderUID, $plan, 'New', '', '', '', 'Free', gmdate('Y-m-d H:i:s')
                        );
                    }

                    if ($subRow) {
                        $now = gmdate('Y-m-d H:i:s');
                        /* Clear OrderUID — order is now fulfilled (Waived) */
                        if ($orderUID > 0) {
                            $this->dbwrite_model->updateData('Billing', 'OrgSubscriptionTbl',
                                ['OrderUID' => null], ['OrgSubUID' => (int)$subRow->OrgSubUID]);
                        }
                        /* Stamp FirstPaidOn on first free plan activation */
                        $this->dbwrite_model->updateData('Billing', 'OrgSubscriptionTbl',
                            ['FirstPaidOn' => $now],
                            ['OrgSubUID' => (int)$subRow->OrgSubUID, 'FirstPaidOn' => null]
                        );
                    }

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

    /* ── Private helpers ─────────────────────────────────────────────── */

    /**
     * @param object $jwtData
     * @param int    $userUID
     * @param int    $orgUID
     * @returns void
     */
    private function _refreshMenuCache(object $jwtData, int $userUID, int $orgUID): void {
        try {
            $roleUID     = (int)($jwtData->User->RoleUID ?? 0);
            $orgToken    = $jwtData->Org->OrgToken ?? '';
            $loginExpiry = (int)getenv('LOGIN_EXPIRE_SECS') ?: 86400;
            if ($roleUID <= 0 || $userUID <= 0) return;

            $this->load->model('login_model');
            $menus    = $this->login_model->getRoleMainMenus($roleUID, $orgUID)->Data ?? [];
            $submenus = $this->login_model->getRoleSubMenus($roleUID, $orgUID)->Data  ?? [];

            $this->redisservice->setUserCache('menus',    $userUID, $menus,    $loginExpiry, $orgToken);
            $this->redisservice->setUserCache('submenus', $userUID, $submenus, $loginExpiry, $orgToken);
        } catch (Exception $e) {
            notifyError('Signuppayment::_refreshMenuCache', $e);
        }
    }

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
