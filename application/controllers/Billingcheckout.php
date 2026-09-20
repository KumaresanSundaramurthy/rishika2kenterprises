<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Billing checkout — subscription payment confirmation page + payment AJAX.
 * Reached from /subscribe via a Redis-backed token (24h TTL).
 *
 * Routes:
 *   GET  /billing/checkout?t={token}          → index()
 *   POST /billing/checkout/createOrder         → createOrder()
 *   POST /billing/checkout/confirmPayment      → confirmPayment()
 */
class Billingcheckout extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('signup_model');
        $this->load->model('dbwrite_model');
    }

    /* ── Checkout page ────────────────────────────────────────────────── */

    public function index(): void {

        $jwtData = $this->pageData['JwtData'] ?? null;
        if (!$jwtData) {
            redirect('login', 'refresh');
            return;
        }

        $orgUID = (int)($jwtData->Org->OrgUID ?? 0);
        $token  = trim($this->input->get('t') ?: '');

        /* Token must be 32 lowercase hex chars */
        if (!$token || !preg_match('/^[0-9a-f]{32}$/', $token)) {
            notifyError('Billingcheckout::index', new Exception('Invalid token format — token=[' . $token . '] orgUID=' . $orgUID));
            redirect('subscribe', 'refresh');
            return;
        }

        /* Validate Redis payment intent */
        $redisKey = 'sub_pay_' . $orgUID . '_' . $token;
        $cached   = $this->redisservice->getCache($redisKey);

        if ($cached->Error || $cached->Value === null) {
            notifyError('Billingcheckout::index', new Exception('Redis miss — key=' . $redisKey . ' msg=' . ($cached->Message ?? 'null')));
            redirect('subscribe', 'refresh');
            return;
        }

        $payload       = $cached->Value;
        $sectorPlanUID = (int)($payload->sector_plan_uid ?? 0);
        $flow          = (string)($payload->flow ?? 'signup');
        $backUrl       = '/' . ltrim((string)($payload->back_url ?? 'subscribe'), '/');

        if ((int)($payload->org_uid ?? 0) !== $orgUID || $sectorPlanUID <= 0) {
            notifyError('Billingcheckout::index', new Exception('Payload mismatch — payload_org=' . ($payload->org_uid ?? 'null') . ' jwt_org=' . $orgUID . ' planUID=' . $sectorPlanUID));
            redirect('subscribe', 'refresh');
            return;
        }

        /* Fetch fresh plan details from DB */
        $plan = $this->signup_model->getSectorPlan($sectorPlanUID);
        if (!$plan || (float)$plan->Price <= 0) {
            notifyError('Billingcheckout::index', new Exception('Plan not found or zero price — sectorPlanUID=' . $sectorPlanUID . ' orgUID=' . $orgUID));
            redirect('subscribe', 'refresh');
            return;
        }

        /* For signup: create/update the pending order so confirmPayment can reference it.
           Renewal and upgrade always create a fresh order inside confirmPayment itself. */
        if ($flow === 'signup') {
            $subRow           = $this->signup_model->getOrgSubUID($orgUID);
            $existingOrderUID = $subRow ? (int)($subRow->OrderUID ?? 0) : 0;

            $_ts = time() + max(1, (int)$plan->DurationDays) * 86400;
            [$_y, $_m, $_d] = explode('-', gmdate('Y-m-d', $_ts + 19800));
            $dueDate = gmdate('Y-m-d H:i:s', gmmktime(23, 59, 59, (int)$_m, (int)$_d, (int)$_y) - 19800);

            $orderData = [
                'SectorPlanUID'  => $sectorPlanUID,
                'RenewalType'    => 'New',
                'Amount'         => (float)$plan->TaxableAmount,
                'DiscountAmount' => 0.00,
                'TaxAmount'      => (float)$plan->TaxAmount,
                'NetAmount'      => (float)$plan->TotalAmount,
                'Status'         => 'Pending',
                'IsPaid'         => 0,
                'DueDate'        => $dueDate,
            ];

            if ($existingOrderUID > 0) {
                $this->dbwrite_model->updateData('Billing', 'SubscriptionOrdersTbl',
                    $orderData, ['OrderUID' => $existingOrderUID]);
            } else {
                $orgSubUID = $subRow ? (int)$subRow->OrgSubUID : null;
                $rOrder    = $this->dbwrite_model->insertData('Billing', 'SubscriptionOrdersTbl',
                    $orderData + [
                        'OrgUID'        => $orgUID,
                        'OrgSubUID'     => $orgSubUID,
                        'FinancialYear' => billing_fy('long'),
                        'CreatedBy'     => null,
                    ]);
                if (!$rOrder->Error && (int)$rOrder->ID > 0 && $subRow) {
                    $this->dbwrite_model->updateData('Billing', 'OrgSubscriptionTbl',
                        ['OrderUID' => (int)$rOrder->ID], ['OrgSubUID' => $orgSubUID]);
                }
            }
        }

        $pageTitle = match($flow) {
            'renewal' => 'Renew Your Subscription',
            'upgrade' => 'Upgrade Your Plan',
            default   => 'Complete Your Payment',
        };

        $this->load->view('login/header', ['pageTitle' => $pageTitle]);
        $this->load->view('billingcheckout/index', [
            'jwtData'       => $jwtData,
            'plan'          => $plan,
            'flow'          => $flow,
            'orgName'       => $jwtData->Org->OrgName ?? '',
            'orgEmail'      => $jwtData->User->EmailAddress ?? '',
            'totalPrice'    => (float)$plan->TotalAmount,
            'taxableAmount' => (float)$plan->TaxableAmount,
            'taxAmount'     => (float)$plan->TaxAmount,
            'token'         => $token,
            'backUrl'       => $backUrl,
        ]);
        $this->load->view('login/footer');
        
    }

    /* ── AJAX: create Razorpay order ──────────────────────────────────── */

    public function createOrder(): void {
        $out = new stdClass();
        try {
            $jwtData = $this->pageData['JwtData'] ?? null;
            if (!$jwtData) throw new Exception('Session error.');

            $orgUID        = (int)($jwtData->Org->OrgUID ?? 0);
            $sectorPlanUID = (int)$this->input->post('sector_plan_uid');

            if ($sectorPlanUID <= 0) throw new ValidationException('Invalid plan selected.');

            $plan = $this->signup_model->getSectorPlan($sectorPlanUID);
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
            notifyError('Billingcheckout::createOrder', $e);
            $out->Error   = true;
            $out->Message = 'Could not initiate payment. Please try again.';
        }
        $this->_json($out);
    }

    /* ── AJAX: verify payment and activate subscription ───────────────── */

    public function confirmPayment(): void {
        $out = new stdClass();
        try {
            $jwtData = $this->pageData['JwtData'] ?? null;
            $jwtKey  = $this->pageData['JwtUserKey'] ?? '';
            if (!$jwtData || !$jwtKey) throw new Exception('Session error.');

            $orgUID        = (int)($jwtData->Org->OrgUID ?? 0);
            $sectorPlanUID = (int)$this->input->post('sector_plan_uid');
            $token         = trim($this->input->post('token') ?: '');
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

            /* Fetch real payment method (UPI / Card / Netbanking / Wallet / EMI) */
            $paymentMode = 'Razorpay';
            $bankRrn     = '';
            try {
                $rpDetails   = $this->razorpayapi->fetchPayment($rpPaymentId);
                $paymentMode = $this->_parsePaymentMode($rpDetails);
                $bankRrn     = (string)(
                    $rpDetails['acquirer_data']['rrn']                  /* UPI */
                    ?? $rpDetails['acquirer_data']['bank_transaction_id'] /* Netbanking */
                    ?? ''
                );
            } catch (Exception $e) {
                notifyError('Billingcheckout::confirmPayment fetchPayment', $e);
            }

            $subStatus = $jwtData->Subscription->Status ?? '';
            $flow = match(true) {
                $subStatus === 'PendingPayment' => 'signup',
                $subStatus === 'Expired'        => 'renewal',
                $subStatus === 'Active'         => 'upgrade',
                default                          => 'signup',
            };

            $plan = $this->signup_model->getSectorPlan($sectorPlanUID);
            if (!$plan) throw new ValidationException('Plan not found.');

            $subRow = $this->signup_model->getOrgSubUID($orgUID);
            if (!$subRow) throw new Exception('Subscription record not found.');

            $filterResult = $this->signup_model->applyPlanMenuFilter($orgUID, $sectorPlanUID);
            if ($filterResult->Error) throw new Exception($filterResult->Message ?? 'Menu filter failed.');

            $now       = gmdate('Y-m-d H:i:s');
            $_ts = time() + max(1, (int)$plan->DurationDays) * 86400;
            [$_y, $_m, $_d] = explode('-', gmdate('Y-m-d', $_ts + 19800));
            $endDate = gmdate('Y-m-d H:i:s', gmmktime(23, 59, 59, (int)$_m, (int)$_d, (int)$_y) - 19800);
            $orgSubUID = (int)$subRow->OrgSubUID;
            $orderUID  = (int)($subRow->OrderUID ?? 0);

            $resolvedOrderUID = 0;

            if ($flow === 'signup') {
                $rSubUpd = $this->dbwrite_model->updateData('Billing', 'OrgSubscriptionTbl', [
                    'Status'        => 'Active',
                    'SectorPlanUID' => $sectorPlanUID,
                    'EndDate'       => $endDate,
                ], ['OrgSubUID' => $orgSubUID]);

                if ($orderUID > 0) {
                    $rOrdUpd = $this->dbwrite_model->updateData('Billing', 'SubscriptionOrdersTbl', [
                        'RenewalType' => 'New',
                        'Status'      => 'Paid',
                        'IsPaid'      => 1,
                        'PaidOn'      => $now,
                        'PaymentMode' => $paymentMode,
                    ], ['OrderUID' => $orderUID]);
                    $resolvedOrderUID = $orderUID;
                } else {
                    $rOrder = $this->dbwrite_model->insertData('Billing', 'SubscriptionOrdersTbl', [
                        'OrgUID'         => $orgUID,
                        'SectorPlanUID'  => $sectorPlanUID,
                        'OrgSubUID'      => $orgSubUID,
                        'RenewalType'    => 'New',
                        'DueDate'        => $endDate,
                        'Amount'         => (float)$plan->TaxableAmount,
                        'DiscountAmount' => 0.00,
                        'TaxAmount'      => (float)$plan->TaxAmount,
                        'NetAmount'      => (float)$plan->TotalAmount,
                        'FinancialYear'  => billing_fy('long'),
                        'Status'         => 'Paid',
                        'IsPaid'         => 1,
                        'PaidOn'         => $now,
                        'PaymentMode'    => $paymentMode,
                        'CreatedBy'      => null,
                    ]);
                    if (!$rOrder->Error) {
                        $resolvedOrderUID = (int)$rOrder->ID;
                        $this->dbwrite_model->updateData('Billing', 'OrgSubscriptionTbl', ['OrderUID' => $resolvedOrderUID], ['OrgSubUID' => $orgSubUID]);
                    }
                }

            } elseif ($flow === 'renewal') {
                $this->dbwrite_model->updateData('Billing', 'OrgSubscriptionTbl', [
                    'SectorPlanUID' => $sectorPlanUID,
                    'Status'        => 'Active',
                    'StartDate'     => $now,
                    'EndDate'       => $endDate,
                ], ['OrgSubUID' => $orgSubUID]);

                $rOrder = $this->dbwrite_model->insertData('Billing', 'SubscriptionOrdersTbl', [
                    'OrgUID'         => $orgUID,
                    'SectorPlanUID'  => $sectorPlanUID,
                    'OrgSubUID'      => $orgSubUID,
                    'RenewalType'    => 'Renewal',
                    'DueDate'        => $endDate,
                    'Amount'         => (float)$plan->TaxableAmount,
                    'DiscountAmount' => 0.00,
                    'TaxAmount'      => (float)$plan->TaxAmount,
                    'NetAmount'      => (float)$plan->TotalAmount,
                    'FinancialYear'  => billing_fy('long'),
                    'Status'         => 'Paid',
                    'IsPaid'         => 1,
                    'PaidOn'         => $now,
                    'PaymentMode'    => $paymentMode,
                    'CreatedBy'      => null,
                ]);
                if ($rOrder->Error) throw new Exception('Order insert failed: ' . $rOrder->Message);
                $this->dbwrite_model->updateData('Billing', 'OrgSubscriptionTbl', ['OrderUID' => (int)$rOrder->ID], ['OrgSubUID' => $orgSubUID]);
                $resolvedOrderUID = (int)$rOrder->ID;

            } elseif ($flow === 'upgrade') {
                $rOrder = $this->dbwrite_model->insertData('Billing', 'SubscriptionOrdersTbl', [
                    'OrgUID'         => $orgUID,
                    'SectorPlanUID'  => $sectorPlanUID,
                    'OrgSubUID'      => $orgSubUID,
                    'RenewalType'    => 'Upgrade',
                    'DueDate'        => $now,
                    'Amount'         => (float)$plan->TaxableAmount,
                    'DiscountAmount' => 0.00,
                    'TaxAmount'      => (float)$plan->TaxAmount,
                    'NetAmount'      => (float)$plan->TotalAmount,
                    'FinancialYear'  => billing_fy('long'),
                    'Status'         => 'Paid',
                    'IsPaid'         => 1,
                    'PaidOn'         => $now,
                    'PaymentMode'    => $paymentMode,
                    'CreatedBy'      => null,
                ]);
                if ($rOrder->Error) throw new Exception('Order insert failed: ' . $rOrder->Message);
                $this->dbwrite_model->updateData('Billing', 'OrgSubscriptionTbl', ['OrderUID' => (int)$rOrder->ID], ['OrgSubUID' => $orgSubUID]);
                $resolvedOrderUID = (int)$rOrder->ID;
            }

            /* Stamp FirstPaidOn on the very first successful payment */
            $this->dbwrite_model->updateData('Billing', 'OrgSubscriptionTbl', ['FirstPaidOn' => $now], ['OrgSubUID' => $orgSubUID, 'FirstPaidOn' => null]);

            /* Clear the pending-order pointer — payment is done, no active pending order */
            $this->dbwrite_model->updateData('Billing', 'OrgSubscriptionTbl', ['OrderUID' => null], ['OrgSubUID' => $orgSubUID]);

            /* Create payment record + invoice + PDF — only if we have a valid resolved order */
            if ($resolvedOrderUID > 0) {
                $renewalTypeMap = ['signup' => 'New', 'renewal' => 'Renewal', 'upgrade' => 'Upgrade'];
                $this->signup_model->createPaymentAndInvoice(
                    $orgUID, $resolvedOrderUID, $plan,
                    $renewalTypeMap[$flow] ?? 'New',
                    $rpOrderId, $rpPaymentId, $rpSignature, $paymentMode, $now, $bankRrn
                );
            }

            /* Update JWT cache */
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

            /* Refresh Redis menu/submenu cache so sidebar reflects the purchased plan immediately.
               Must happen AFTER applyPlanMenuFilter() and the JWT cache update above. */
            $this->_refreshMenuCache($jwtData, (int)($jwtData->User->UserUID ?? 0), $orgUID);

            /* Delete the payment intent token — no longer needed after activation */
            if ($token && preg_match('/^[0-9a-f]{32}$/', $token)) {
                $this->redisservice->deleteCache('sub_pay_' . $orgUID . '_' . $token);
            }

            $out->Error    = false;
            $out->Message  = 'Payment verified. Your subscription is now active!';
            $out->Redirect = $this->_redirectForFlow($flow, $jwtData);

        } catch (ValidationException $e) {
            $out->Error   = true;
            $out->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Billingcheckout::confirmPayment', $e);
            $out->Error   = true;
            $out->Message = 'Something went wrong confirming your payment. Please contact support.';
        }
        $this->_json($out);
    }

    /* ── Abandon: delete Redis token and return to plan selection ────── */

    public function abandon(): void {
        $jwtData = $this->pageData['JwtData'] ?? null;
        if (!$jwtData) {
            redirect('login', 'refresh');
            return;
        }

        $orgUID   = (int)($jwtData->Org->OrgUID ?? 0);
        $token    = trim($this->input->get('t') ?: '');
        $backUrl  = 'subscribe';

        if ($token && preg_match('/^[0-9a-f]{32}$/', $token)) {
            $redisKey = 'sub_pay_' . $orgUID . '_' . $token;
            $cached   = $this->redisservice->getCache($redisKey);
            if (!$cached->Error && $cached->Value !== null) {
                $backUrl = ltrim((string)($cached->Value->back_url ?? 'subscribe'), '/');
                $this->redisservice->deleteCache($redisKey);
            }
        }

        redirect($backUrl, 'refresh');
    }

    /* ── Private helpers ──────────────────────────────────────────────── */

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

            /* Bust stale role-level cache set at login time so getRoleMainMenus()
               re-queries the DB and returns only the modules the purchased plan activates. */
            $this->redisservice->deleteCache($this->redisservice->orgKey('role-menus-'    . $roleUID, $orgToken));
            $this->redisservice->deleteCache($this->redisservice->orgKey('role-submenus-' . $roleUID, $orgToken));

            $this->load->model('login_model');
            $menus    = $this->login_model->getRoleMainMenus($roleUID, $orgUID)->Data ?? [];
            $submenus = $this->login_model->getRoleSubMenus($roleUID, $orgUID)->Data  ?? [];

            $this->redisservice->setUserCache('menus',    $userUID, $menus,    $loginExpiry, $orgToken);
            $this->redisservice->setUserCache('submenus', $userUID, $submenus, $loginExpiry, $orgToken);
        } catch (Exception $e) {
            notifyError('Billingcheckout::_refreshMenuCache', $e);
        }
    }

    /**
     * Build a human-readable payment mode label from a Razorpay payment object.
     * @param array $p  Response from fetchPayment()
     * @returns string
     */
    private function _parsePaymentMode(array $p): string {
        $method = $p['method'] ?? '';
        return match($method) {
            'upi'        => 'UPI' . (!empty($p['vpa']) ? ' - ' . $p['vpa'] : ''),
            'card'       => 'Card - ' . trim(($p['card']['network'] ?? '') . ' ' . ucfirst($p['card']['type'] ?? '')),
            'netbanking' => 'Netbanking' . (!empty($p['bank']) ? ' - ' . strtoupper($p['bank']) : ''),
            'wallet'     => 'Wallet - ' . ucfirst($p['wallet'] ?? ''),
            'emi'        => 'EMI',
            default      => 'Razorpay',
        };
    }

    private function _redirectForFlow(string $flow, object $jwtData): string {
        if ($flow === 'upgrade') {
            return base_url('subscription/dashboard');
        }
        if ($flow === 'renewal') {
            return base_url('dashboard');
        }
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
