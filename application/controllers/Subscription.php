<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Subscription extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('subscription_model');
        $this->load->model('billingplan_model');
        $this->load->model('signup_model');
        $this->load->model('dbwrite_model'); $this->load->model('dbwrite_ext_model');
    }

    /* ── Pages ──────────────────────────────────────────────────────────────── */

    public function index(): void {
        redirect('subscription/dashboard', 'refresh');
    }

    public function dashboard(): void {
        $orgUID = $this->_orgUID();

        $subResult       = $this->billingplan_model->getOrgSubscription($orgUID);
        $plansResult     = $this->billingplan_model->getAvailablePlans($orgUID);
        $ordersResult    = $this->billingplan_model->getOrderHistory($orgUID, 10);

        $this->pageData['subscription'] = (!$subResult->Error && $subResult->Data)
            ? $subResult->Data
            : null;
        $this->pageData['plans']        = (!$plansResult->Error) ? $plansResult->Data : [];
        $this->pageData['orders']       = (!$ordersResult->Error) ? $ordersResult->Data : [];

        $this->load->view('common/header');
        $this->load->view('subscription/dashboard', $this->pageData);
        $this->load->view('common/footer');
        $this->load->view('subscription/dashboard_js', $this->pageData);
    }

    public function expired(): void {
        $this->load->view('subscription/expired');
    }

    public function plans(): void {
        $orgUID = $this->_orgUID();

        $subResult   = $this->billingplan_model->getOrgSubscription($orgUID);
        $plansResult = $this->billingplan_model->getAvailablePlans($orgUID);

        $subscription = (!$subResult->Error && $subResult->Data) ? $subResult->Data : null;
        $plans        = (!$plansResult->Error) ? $plansResult->Data : [];

        /* Module count per PlanUID */
        $moduleCountMap = [];
        if (!empty($plans)) {
            $sectorUID = (int)($plans[0]->SectorUID ?? 0);
            if ($sectorUID > 0) {
                $rows = $this->billingplan_model->getModuleCountByPlan($sectorUID);
                foreach ($rows as $r) {
                    $moduleCountMap[(int)$r->PlanUID] = (int)$r->Cnt;
                }
            }
        }

        $this->pageData['subscription']   = $subscription;
        $this->pageData['plans']          = $plans;
        $this->pageData['moduleCountMap'] = $moduleCountMap;

        $this->load->vars($this->pageData);
        $this->load->view('common/header');
        $this->load->view('subscription/plans', $this->pageData);
        $this->load->view('common/footer');
        $this->load->view('subscription/plans_js');
    }

    /* ── AJAX: get plan-change options (for modal) ──────────────────────────── */

    public function getPlanChangeOptions(): void {
        $this->_ajaxOnly();
        $result = new stdClass();
        try {
            $orgUID          = $this->_orgUID();
            $newSectorPlanUID = (int)$this->input->post('sector_plan_uid');

            if ($newSectorPlanUID <= 0) {
                throw new ValidationException('Invalid plan selected.');
            }

            /* 5-day lock check */
            $lock = $this->billingplan_model->getPlanChangeLockStatus($orgUID);
            if ($lock->IsLocked) {
                $result->Status        = 'LOCKED';
                $result->Message       = 'Plan changes are locked for ' . $lock->DaysRemaining . ' more day(s) due to a recent change. A GST invoice was already issued.';
                $result->UnlocksAt     = $lock->UnlocksAt;
                $result->DaysRemaining = $lock->DaysRemaining;
                $this->output->set_content_type('application/json')->set_output(json_encode($result));
                return;
            }

            /* Any pending scheduled change? */
            $scheduled = $this->billingplan_model->getScheduledPlanChange($orgUID);

            /* Option calculations */
            $details = $this->billingplan_model->calculatePlanChangeDetails($orgUID, $newSectorPlanUID);
            if ($details->Error) throw new ValidationException($details->Message);

            /* Module comparison */
            $modules = $this->billingplan_model->getModuleComparison($orgUID, $newSectorPlanUID);

            $result->Status    = 'OK';
            $result->Details   = $details;
            $result->Modules   = $modules;
            $result->Scheduled = $scheduled;

        } catch (ValidationException $e) {
            $result->Status  = 'FAIL';
            $result->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Subscription::getPlanChangeOptions', $e);
            $result->Status  = 'ERROR';
            $result->Message = 'Something went wrong. Please try again.';
        }
        $this->output->set_content_type('application/json')->set_output(json_encode($result));
    }

    /* ── AJAX: initiate Razorpay order for plan change ─────────────────────── */

    public function initiatePayment(): void {
        $this->_ajaxOnly();
        $result = new stdClass();
        try {
            $orgUID           = $this->_orgUID();
            $newSectorPlanUID = (int)$this->input->post('sector_plan_uid');
            $optionType       = $this->input->post('option_type');

            if ($newSectorPlanUID <= 0) {
                throw new ValidationException('Invalid plan selected.');
            }
            if (!in_array($optionType, ['UA', 'UB', 'DA', 'DB', 'DC'], true)) {
                throw new ValidationException('Invalid option type.');
            }

            $details = $this->billingplan_model->calculatePlanChangeDetails($orgUID, $newSectorPlanUID);
            if ($details->Error) throw new ValidationException($details->Message);

            $optKey = 'Option' . $optionType;
            $opt    = $details->$optKey ?? null;
            if (!$opt) throw new ValidationException('Option details not found.');

            $amountPaise = (int)round((float)$opt->TotalAmount * 100);
            if ($amountPaise < 100) throw new ValidationException('Amount is too low for online payment.');

            $this->load->library('Razorpayapi');
            if (!$this->razorpayapi->isConfigured()) {
                throw new Exception('Online payment is not currently enabled. Please contact support.');
            }

            $jwtData   = $this->pageData['JwtData'];
            $orgName   = $jwtData->Org->OrgName ?? 'Your Organisation';
            $receiptId = 'plan_' . $orgUID . '_' . $newSectorPlanUID . '_' . time();
            $order     = $this->razorpayapi->createOrder($amountPaise, $receiptId);

            $result->Error       = false;
            $result->order_id    = $order['id'];
            $result->key_id      = $this->razorpayapi->getKeyId();
            $result->amount      = $amountPaise;
            $result->currency    = 'INR';
            $result->name        = htmlspecialchars($orgName, ENT_QUOTES, 'UTF-8');
            $result->description = ($details->NewPlan->PlanName ?? 'Plan') . ' — ' . $optionType;
            $result->prefill     = [
                'name'  => $orgName,
                'email' => $jwtData->User->EmailAddress ?? '',
            ];

        } catch (ValidationException $e) {
            $result->Error   = true;
            $result->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Subscription::initiatePayment', $e);
            $result->Error   = true;
            $result->Message = 'Could not initiate payment. Please try again.';
        }
        $this->output->set_content_type('application/json')->set_output(json_encode($result));
    }

    /* ── AJAX: confirm plan change after Razorpay payment ───────────────────── */

    public function confirmPlanChange(): void {
        $this->_ajaxOnly();
        $result = new stdClass();
        try {
            $orgUID           = $this->_orgUID();
            $userUID          = $this->_userUID();
            $newSectorPlanUID = (int)$this->input->post('sector_plan_uid');
            $optionType       = $this->input->post('option_type');

            if ($newSectorPlanUID <= 0) {
                throw new ValidationException('Invalid plan selected.');
            }
            if (!in_array($optionType, ['UA', 'UB', 'DA', 'DB', 'DC'], true)) {
                throw new ValidationException('Invalid option type.');
            }

            /* Verify Razorpay signature */
            $rpOrderId   = trim($this->input->post('razorpay_order_id')   ?: '');
            $rpPaymentId = trim($this->input->post('razorpay_payment_id') ?: '');
            $rpSignature = trim($this->input->post('razorpay_signature')  ?: '');

            if (!$rpOrderId || !$rpPaymentId || !$rpSignature) {
                throw new ValidationException('Incomplete payment data. Please try again.');
            }

            $this->load->library('Razorpayapi');
            if (!$this->razorpayapi->verifySignature($rpOrderId, $rpPaymentId, $rpSignature)) {
                throw new ValidationException('Payment verification failed. Contact support if amount was deducted.');
            }

            /* Re-check lock */
            $lock = $this->billingplan_model->getPlanChangeLockStatus($orgUID);
            if ($lock->IsLocked) {
                throw new ValidationException('Plan changes are locked for ' . $lock->DaysRemaining . ' more day(s). A GST invoice was already issued for the recent change.');
            }

            $adminRoleUID = $this->_getAdminRoleUID($orgUID);
            if ($adminRoleUID <= 0) {
                throw new ValidationException('No admin role found for this organisation.');
            }

            $paymentData = [
                'paid'     => true,
                'mode'     => 'Razorpay',
                'amount'   => (float)$this->input->post('amount'),
                'discount' => 0,
                'tax'      => (float)$this->input->post('tax'),
                'notes'    => 'Razorpay Order: ' . $rpOrderId . ' | Payment: ' . $rpPaymentId,
            ];

            if (in_array($optionType, ['UB', 'DC'], true)) {
                /* Scheduled options — validate dates */
                $scheduledStart = $this->input->post('scheduled_start');
                $scheduledEnd   = $this->input->post('scheduled_end');
                if (empty($scheduledStart) || empty($scheduledEnd)) {
                    throw new ValidationException('Scheduled dates are required.');
                }
                $changeType = ($optionType === 'UB') ? 'Upgrade' : 'Downgrade';
                $changeResult = $this->billingplan_model->schedulePlanChange(
                    $orgUID, $newSectorPlanUID, $changeType, $optionType,
                    $scheduledStart, $scheduledEnd, $adminRoleUID, $userUID, $paymentData
                );
            } else {
                /* Immediate options UA, DA, DB */
                $renewalTypeMap = ['UA' => 'Upgrade', 'DA' => 'Downgrade', 'DB' => 'Downgrade'];
                $renewalType    = $renewalTypeMap[$optionType];
                $changeResult   = $this->billingplan_model->changePlan(
                    $orgUID, $newSectorPlanUID, $renewalType, $adminRoleUID, $userUID, $paymentData
                );
            }

            if ($changeResult->Error) throw new Exception($changeResult->Message);

            $result->Status  = 'OK';
            $result->Message = $changeResult->Message;

        } catch (ValidationException $e) {
            $result->Status  = 'FAIL';
            $result->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Subscription::confirmPlanChange', $e);
            $result->Status  = 'ERROR';
            $result->Message = 'Something went wrong. Please try again.';
        }
        $this->output->set_content_type('application/json')->set_output(json_encode($result));
    }

    /* ── AJAX: change / renew plan ──────────────────────────────────────────── */

    public function changePlan(): void {
        $this->_ajaxOnly();
        $result = new stdClass();
        try {
            $orgUID        = $this->_orgUID();
            $userUID       = $this->_userUID();
            $sectorPlanUID = (int)$this->input->post('sector_plan_uid');
            $renewalType   = $this->input->post('renewal_type') ?: 'New';

            if (!in_array($renewalType, ['New', 'Renewal', 'Upgrade', 'Downgrade'], true)) {
                throw new ValidationException('Invalid renewal type.');
            }
            if ($sectorPlanUID <= 0) {
                throw new ValidationException('Please select a plan.');
            }

            /* Admin role = first role for this org in UserRole.RolesTbl */
            $adminRoleUID = $this->_getAdminRoleUID($orgUID);
            if ($adminRoleUID <= 0) {
                throw new ValidationException('No admin role found for this organisation.');
            }

            $paymentData = [
                'paid'     => (bool)$this->input->post('is_paid'),
                'mode'     => $this->input->post('payment_mode') ?: null,
                'amount'   => (float)$this->input->post('amount'),
                'discount' => (float)$this->input->post('discount'),
                'tax'      => (float)$this->input->post('tax'),
                'notes'    => $this->input->post('notes') ?: null,
            ];

            $changeResult = $this->billingplan_model->changePlan(
                $orgUID,
                $sectorPlanUID,
                $renewalType,
                $adminRoleUID,
                $userUID,
                $paymentData
            );

            if ($changeResult->Error) {
                throw new Exception($changeResult->Message);
            }

            $result->Status  = 'OK';
            $result->Message = $changeResult->Message;
            $result->EndDate = $changeResult->EndDate;

        } catch (ValidationException $e) {
            $result->Status  = 'FAIL';
            $result->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Subscription::changePlan', $e);
            $result->Status  = 'ERROR';
            $result->Message = 'Something went wrong. Please try again.';
        }
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($result));
    }

    /* ── AJAX: renew current plan ───────────────────────────────────────────── */

    public function renewPlan(): void {
        $this->_ajaxOnly();
        $result = new stdClass();
        try {
            $orgUID  = $this->_orgUID();
            $userUID = $this->_userUID();

            /* Get current plan */
            $subResult = $this->billingplan_model->getOrgSubscription($orgUID);
            if ($subResult->Error || !$subResult->Data) {
                throw new ValidationException('No active subscription found.');
            }
            $sub = $subResult->Data;
            if (!$sub->SectorPlanUID) {
                throw new ValidationException('Trial subscriptions cannot be renewed here. Please select a plan first.');
            }

            $adminRoleUID = $this->_getAdminRoleUID($orgUID);
            if ($adminRoleUID <= 0) {
                throw new ValidationException('No admin role found for this organisation.');
            }

            $paymentData = [
                'paid'     => (bool)$this->input->post('is_paid'),
                'mode'     => $this->input->post('payment_mode') ?: null,
                'amount'   => (float)$this->input->post('amount'),
                'discount' => (float)$this->input->post('discount'),
                'tax'      => (float)$this->input->post('tax'),
                'notes'    => $this->input->post('notes') ?: null,
            ];

            $changeResult = $this->billingplan_model->changePlan(
                $orgUID,
                (int)$sub->SectorPlanUID,
                'Renewal',
                $adminRoleUID,
                $userUID,
                $paymentData
            );

            if ($changeResult->Error) {
                throw new Exception($changeResult->Message);
            }

            $result->Status  = 'OK';
            $result->Message = $changeResult->Message;
            $result->EndDate = $changeResult->EndDate;

        } catch (ValidationException $e) {
            $result->Status  = 'FAIL';
            $result->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Subscription::renewPlan', $e);
            $result->Status  = 'ERROR';
            $result->Message = 'Something went wrong. Please try again.';
        }
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($result));
    }

    /* ── AJAX: record payment on a pending order ────────────────────────────── */

    public function recordPayment(): void {
        $this->_ajaxOnly();
        $result = new stdClass();
        try {
            $orgUID   = $this->_orgUID();
            $userUID  = $this->_userUID();
            $orderUID = (int)$this->input->post('order_uid');
            $mode     = $this->input->post('payment_mode');
            $amount   = (float)$this->input->post('amount');

            if ($orderUID <= 0) {
                throw new ValidationException('Invalid order.');
            }
            if ($amount <= 0) {
                throw new ValidationException('Payment amount must be greater than zero.');
            }
            if (empty($mode)) {
                throw new ValidationException('Payment mode is required.');
            }

            /* Verify order belongs to this org */
            $orderRow = $this->billingplan_model->getSubscriptionOrderByUID($orgUID, $orderUID);

            if (!$orderRow) {
                throw new ValidationException('Order not found.');
            }
            if ($orderRow->Status === 'Paid') {
                throw new ValidationException('This order is already paid.');
            }

            $this->dbwrite_model->startTransaction();

            /* Insert payment row */
            $this->dbwrite_model->insertData('Billing', 'SubscriptionPaymentsTbl', [
                'OrderUID'         => $orderUID,
                'FinancialYear'    => billing_fy('long'),
                'PaymentDate'      => date('Y-m-d H:i:s'),
                'Amount'           => $amount,
                'Mode'             => $mode,
                'PaymentID'        => null,
                'GatewayOrderID'   => null,
                'GatewaySignature' => null,
                'Status'           => 'Success',
            ]);

            /* Mark order paid */
            $this->dbwrite_model->updateData('Billing', 'SubscriptionOrdersTbl', [
                'Status'      => 'Paid',
                'IsPaid'      => 1,
                'PaidOn'      => date('Y-m-d H:i:s'),
                'PaymentMode' => $mode,
            ], ['OrderUID' => $orderUID]);

            /* Activate subscription */
            $this->dbwrite_ext_model->activateLatestOrgSubscription($orgUID);

            $this->dbwrite_model->commitTransaction();

            $result->Status  = 'OK';
            $result->Message = 'Payment recorded successfully.';

        } catch (ValidationException $e) {
            $result->Status  = 'FAIL';
            $result->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Subscription::recordPayment', $e);
            $result->Status  = 'ERROR';
            $result->Message = 'Something went wrong. Please try again.';
        }
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($result));
    }

    /* ── Invoice download ───────────────────────────────────────────────────── */

    /**
     * @param int $invoiceUID
     * @returns void
     */
    public function invoice(int $invoiceUID): void {
        $orgUID = $this->_orgUID();
        try {
            if ($invoiceUID <= 0) {
                show_error('Invalid invoice.', 400);
                return;
            }

            $row = $this->billingplan_model->getSubscriptionInvoiceByUID($invoiceUID, $orgUID);

            if (!$row || empty($row->PDFPath)) {
                show_error('Invoice PDF is not available yet.', 404);
                return;
            }

            $ch = curl_init($row->PDFPath);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $pdfBytes = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr  = curl_error($ch);
            curl_close($ch);

            if ($curlErr || $httpCode !== 200 || !$pdfBytes) {
                show_error('Could not fetch invoice PDF. Please try again.', 502);
                return;
            }

            $filename = 'Invoice-' . preg_replace('/[^A-Za-z0-9\-]/', '', $row->InvoiceNumber ?? $invoiceUID) . '.pdf';

            $this->output
                ->set_status_header(200)
                ->set_content_type('application/pdf')
                ->set_header('Content-Disposition: attachment; filename="' . $filename . '"')
                ->set_header('Content-Length: ' . strlen($pdfBytes))
                ->set_header('Cache-Control: private, no-store')
                ->set_output($pdfBytes)
                ->_display();
            exit;

        } catch (Exception $e) {
            notifyError('Subscription::invoice', $e);
            show_error('Something went wrong. Please try again.', 500);
        }
    }

    /* ── Pay a specific pending order ──────────────────────────────────────── */

    /**
     * @param int $orderUID
     * @returns void
     */
    public function payOrder(int $orderUID): void {
        $orgUID = $this->_orgUID();

        $orderResult = $this->billingplan_model->getPendingOrder($orgUID, $orderUID);
        if ($orderResult->Error || !$orderResult->Data || (float)$orderResult->Data->NetAmount <= 0) {
            redirect('subscription/dashboard', 'refresh');
            return;
        }

        $this->pageData['order']   = $orderResult->Data;
        $this->pageData['orgName'] = $this->pageData['JwtData']->Org->OrgName ?? '';

        $this->load->view('common/header');
        $this->load->view('subscription/pay_order', $this->pageData);
        $this->load->view('common/footer');
        $this->load->view('subscription/pay_order_js', $this->pageData);
    }

    /* ── AJAX: create Razorpay order for a pending subscription order ───────── */

    public function createOrderPayment(): void {
        $this->_ajaxOnly();
        $out = new stdClass();
        try {
            $jwtData  = $this->pageData['JwtData'] ?? null;
            if (!$jwtData) throw new Exception('Session error.');

            $orgUID   = $this->_orgUID();
            $orderUID = (int)$this->input->post('order_uid');

            if ($orderUID <= 0) throw new ValidationException('Invalid order.');

            $orderResult = $this->billingplan_model->getPendingOrder($orgUID, $orderUID);
            if ($orderResult->Error || !$orderResult->Data) {
                throw new ValidationException('Order not found or already paid.');
            }
            $order = $orderResult->Data;

            $amountPaise = (int)round((float)$order->NetAmount * 100);
            if ($amountPaise < 100) throw new ValidationException('Amount too low for online payment.');

            $this->load->library('Razorpayapi');
            if (!$this->razorpayapi->isConfigured()) {
                throw new Exception('Online payment is not currently enabled. Please contact support.');
            }

            $receiptId  = 'spay_' . $orgUID . '_' . $orderUID . '_' . time();
            $rpOrder = $this->razorpayapi->createOrder($amountPaise, $receiptId, 'INR', [
                'type'      => 'subscription_pay',
                'org_uid'   => (string)$orgUID,
                'order_uid' => (string)$orderUID,
            ]);

            $out->Error       = false;
            $out->order_id    = $rpOrder['id'];
            $out->key_id      = $this->razorpayapi->getKeyId();
            $out->amount      = $amountPaise;
            $out->currency    = 'INR';
            $out->name        = htmlspecialchars($jwtData->Org->OrgName ?? 'Your Organisation', ENT_QUOTES, 'UTF-8');
            $out->description = $order->PlanName . ' — ' . ($order->BillingCycle ?? '');
            $out->prefill     = ['name' => $out->name, 'email' => $jwtData->User->EmailAddress ?? ''];

        } catch (ValidationException $e) {
            $out->Error   = true;
            $out->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Subscription::createOrderPayment', $e);
            $out->Error   = true;
            $out->Message = 'Could not initiate payment. Please try again.';
        }
        $this->output->set_content_type('application/json')->set_output(json_encode($out));
    }

    /* ── AJAX: verify Razorpay payment and mark pending order as paid ──────── */

    public function confirmOrderPayment(): void {
        $this->_ajaxOnly();
        $out = new stdClass();
        try {
            $jwtData = $this->pageData['JwtData'] ?? null;
            if (!$jwtData) throw new Exception('Session error.');

            $orgUID      = $this->_orgUID();
            $orderUID    = (int)$this->input->post('order_uid');
            $rpOrderId   = trim($this->input->post('razorpay_order_id')   ?: '');
            $rpPaymentId = trim($this->input->post('razorpay_payment_id') ?: '');
            $rpSignature = trim($this->input->post('razorpay_signature')  ?: '');

            if ($orderUID <= 0) throw new ValidationException('Invalid order.');
            if (!$rpOrderId || !$rpPaymentId || !$rpSignature) {
                throw new ValidationException('Incomplete payment data. Please try again.');
            }

            $orderResult = $this->billingplan_model->getPendingOrder($orgUID, $orderUID);
            if ($orderResult->Error || !$orderResult->Data) {
                throw new ValidationException('Order not found or already paid.');
            }
            $order = $orderResult->Data;

            $this->load->library('Razorpayapi');
            if (!$this->razorpayapi->verifySignature($rpOrderId, $rpPaymentId, $rpSignature)) {
                throw new ValidationException('Payment verification failed. Contact support if amount was deducted.');
            }

            $paymentMode = 'Razorpay';
            $bankRrn     = '';
            try {
                $rpDetails   = $this->razorpayapi->fetchPayment($rpPaymentId);
                $paymentMode = $this->_parsePaymentMode($rpDetails);
                $bankRrn     = (string)(
                    $rpDetails['acquirer_data']['rrn']
                    ?? $rpDetails['acquirer_data']['bank_transaction_id']
                    ?? ''
                );
            } catch (Exception $e) {
                notifyError('Subscription::confirmOrderPayment fetchPayment', $e);
            }

            $now = gmdate('Y-m-d H:i:s');

            $this->dbwrite_model->updateData('Billing', 'SubscriptionOrdersTbl', [
                'Status'      => 'Paid',
                'IsPaid'      => 1,
                'PaidOn'      => $now,
                'PaymentMode' => $paymentMode,
            ], ['OrderUID' => $orderUID, 'OrgUID' => $orgUID]);

            $plan = $this->signup_model->getSectorPlan((int)$order->SectorPlanUID);
            if (!$plan) throw new Exception('Plan not found.');

            $this->signup_model->createPaymentAndInvoice(
                $orgUID, $orderUID, $plan,
                $order->RenewalType,
                $rpOrderId, $rpPaymentId, $rpSignature, $paymentMode, $now, $bankRrn
            );

            $out->Error    = false;
            $out->Message  = 'Payment successful. Thank you!';
            $out->Redirect = base_url('subscription/dashboard');

        } catch (ValidationException $e) {
            $out->Error   = true;
            $out->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Subscription::confirmOrderPayment', $e);
            $out->Error   = true;
            $out->Message = 'Something went wrong confirming your payment. Please contact support.';
        }
        $this->output->set_content_type('application/json')->set_output(json_encode($out));
    }

    /* ── Private helpers ────────────────────────────────────────────────────── */

    private function _ajaxOnly(): void {
        if (!$this->input->is_ajax_request()) {
            show_error('Direct access not allowed.', 403);
        }
    }

    /**
     * @param array $p  Razorpay fetchPayment response
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

    private function _getAdminRoleUID(int $orgUID): int {
        $this->load->model('roles_model');
        return $this->roles_model->getFirstRoleUID($orgUID);
    }

}
