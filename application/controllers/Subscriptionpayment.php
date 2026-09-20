<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Subscription payment confirmation page.
 * Reached from /subscribe via a Redis-backed token.
 * Route: /subscription/payment?t={token}
 */
class Subscriptionpayment extends CI_Controller {

    public function index(): void {
        $jwtData = $this->pageData['JwtData'] ?? null;
        if (!$jwtData) {
            redirect('login', 'refresh');
            return;
        }

        $orgUID = (int)($jwtData->Org->OrgUID ?? 0);
        $token  = trim($this->input->get('t') ?: '');

        /* Validate token format — must be 32 lowercase hex chars */
        if (!$token || !preg_match('/^[0-9a-f]{32}$/', $token)) {
            redirect('subscribe', 'refresh');
            return;
        }

        /* Look up the Redis payment intent */
        $redisKey = 'sub_pay_' . $orgUID . '_' . $token;
        $cached   = $this->redisservice->getCache($redisKey);

        if ($cached->Error || $cached->Value === null) {
            /* Token expired or not found */
            redirect('subscribe', 'refresh');
            return;
        }

        $payload       = $cached->Value;
        $sectorPlanUID = (int)($payload->sector_plan_uid ?? 0);
        $flow          = (string)($payload->flow ?? 'signup');

        /* Org UID in payload must match the authenticated user */
        if ((int)($payload->org_uid ?? 0) !== $orgUID || $sectorPlanUID <= 0) {
            redirect('subscribe', 'refresh');
            return;
        }

        /* Fetch fresh plan details from DB */
        $readDb = $this->load->database('ReadDB', TRUE);
        $readDb->db_debug = FALSE;

        $plan = $readDb
            ->select('SPT.SectorPlanUID, SPT.Price, SPT.DurationDays, SP.PlanName, SP.BillingCycle')
            ->from('Billing.SectorPlanTbl AS SPT')
            ->join('Billing.SubscriptionPlansTbl AS SP', 'SP.PlanUID = SPT.PlanUID')
            ->where('SPT.SectorPlanUID', $sectorPlanUID)
            ->where('SPT.IsActive', 1)
            ->limit(1)
            ->get()->row();

        if (!$plan || (float)$plan->Price <= 0) {
            redirect('subscribe', 'refresh');
            return;
        }

        /* GST-inclusive breakdown: Total = Taxable × 1.18 */
        $total   = (float)$plan->Price;
        $taxable = round($total / 1.18, 2);
        $tax     = round($total - $taxable, 2);

        $pageTitle = match($flow) {
            'renewal' => 'Renew Your Subscription',
            'upgrade' => 'Upgrade Your Plan',
            default   => 'Complete Your Payment',
        };

        $this->load->view('login/header', ['pageTitle' => $pageTitle]);
        $this->load->view('subscriptionpayment/index', [
            'jwtData'       => $jwtData,
            'plan'          => $plan,
            'flow'          => $flow,
            'orgName'       => $jwtData->Org->OrgName ?? '',
            'orgEmail'      => $jwtData->User->EmailAddress ?? '',
            'totalPrice'    => $total,
            'taxableAmount' => $taxable,
            'taxAmount'     => $tax,
            'token'         => $token,
        ]);
        $this->load->view('login/footer');
    }
}
