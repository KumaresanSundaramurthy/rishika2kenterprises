<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Subscription extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('subscription_model');
        $this->load->model('billingplan_model');
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
            $readDb = $this->load->database('ReadDB', TRUE);
            $readDb->db_debug = FALSE;
            $sectorUID = (int)($plans[0]->SectorUID ?? 0);
            if ($sectorUID > 0) {
                $rows = $readDb->select('PlanUID, COUNT(*) AS Cnt')
                    ->from('Billing.SectorPlanModulesTbl')
                    ->where('SectorUID', $sectorUID)
                    ->group_by('PlanUID')
                    ->get()->result();
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

            $this->load->database('WriteDB', FALSE);
            $writeDb = $this->load->database('WriteDB', TRUE);

            /* Verify order belongs to this org */
            $readDb  = $this->load->database('ReadDB', TRUE);
            $orderRow = $readDb->select('OrderUID, Status, NetAmount')
                ->from('Billing.SubscriptionOrdersTbl')
                ->where('OrderUID', $orderUID)
                ->where('OrgUID',   $orgUID)
                ->limit(1)
                ->get()->row();

            if (!$orderRow) {
                throw new ValidationException('Order not found.');
            }
            if ($orderRow->Status === 'Paid') {
                throw new ValidationException('This order is already paid.');
            }

            $writeDb->trans_begin();

            /* Insert payment row */
            $writeDb->insert('Billing.SubscriptionPaymentsTbl', [
                'OrderUID'         => $orderUID,
                'FinancialYear'    => billing_fy('long'),
                'PaymentDate'      => date('Y-m-d H:i:s'),
                'Amount'           => $amount,
                'Mode'             => $mode,
                'PaymentID'        => null,   /* manual — no gateway transaction */
                'GatewayOrderID'   => null,
                'GatewaySignature' => null,
                'Status'           => 'Success',
            ]);

            /* Mark order paid */
            $writeDb->where('OrderUID', $orderUID)->update('Billing.SubscriptionOrdersTbl', [
                'Status'      => 'Paid',
                'IsPaid'      => 1,
                'PaidOn'      => date('Y-m-d H:i:s'),
                'PaymentMode' => $mode,
            ]);

            /* Activate subscription */
            $writeDb->where('OrgUID', $orgUID)
                ->where_not_in('Status', ['Cancelled'])
                ->order_by('StartDate', 'DESC')
                ->limit(1)
                ->update('Billing.OrgSubscriptionTbl', [
                    'Status'    => 'Active',
                    'UpdatedBy' => $userUID,
                ]);

            if ($writeDb->trans_status() === FALSE) {
                $writeDb->trans_rollback();
                throw new Exception('Payment recording failed.');
            }
            $writeDb->trans_commit();

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

    /* ── Private helpers ────────────────────────────────────────────────────── */

    private function _ajaxOnly(): void {
        if (!$this->input->is_ajax_request()) {
            show_error('Direct access not allowed.', 403);
        }
    }

    private function _getAdminRoleUID(int $orgUID): int {
        $readDb = $this->load->database('ReadDB', TRUE);
        $row = $readDb->select('RoleUID')
            ->from('UserRole.RolesTbl')
            ->where('OrgUID',    $orgUID)
            ->where('IsDeleted', 0)
            ->order_by('RoleUID', 'ASC')
            ->limit(1)
            ->get()->row();
        return $row ? (int)$row->RoleUID : 0;
    }

}
