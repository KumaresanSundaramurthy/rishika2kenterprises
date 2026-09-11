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
        $this->load->view('common/menu_view');
        $this->load->view('subscription/dashboard', $this->pageData);
        $this->load->view('common/footer');
    }

    public function expired(): void {
        $this->load->view('subscription/expired');
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

            /* Bust org-specific caches */
            $this->load->helper('cachehelper');
            $this->cachehelper->bustOrgMenuCache($orgUID);

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

    private function _orgUID(): int {
        $jwt = $this->session->userdata('JwtData');
        return (int)($jwt->OrgUID ?? 0);
    }

    private function _userUID(): int {
        $jwt = $this->session->userdata('JwtData');
        return (int)($jwt->UserUID ?? 0);
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
