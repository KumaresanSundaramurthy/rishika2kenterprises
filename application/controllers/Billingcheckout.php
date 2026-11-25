<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Billing checkout — subscription payment page.
 * Reached from /subscribe via a Redis-backed token (24h TTL).
 * Payment AJAX (createOrder, confirmPayment) is handled directly by NestJS.
 *
 * Routes:
 *   GET  /billing/checkout?t={token}  → index()
 *   GET  /billing/checkout/abandon    → abandon()
 */
class Billingcheckout extends CI_Controller {

    public function __construct() {
        parent::__construct();
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
        $planResp = services_get('api/billing/plan?sectorPlanUID=' . $sectorPlanUID, services_server_headers());
        $plan = ($planResp && empty($planResp->Error) && !empty($planResp->Data)) ? $planResp->Data : null;
        if (!$plan || (float)$plan->Price <= 0) {
            notifyError('Billingcheckout::index', new Exception('Plan not found or zero price — sectorPlanUID=' . $sectorPlanUID . ' orgUID=' . $orgUID));
            redirect('subscribe', 'refresh');
            return;
        }

        /* For signup: create/update the pending order so confirmPayment can reference it.
           Renewal and upgrade create a fresh order inside confirmPayment itself. */
        if ($flow === 'signup') {
            services_post('api/billing/createPendingOrder',
                ['orgUID' => $orgUID, 'sectorPlanUID' => $sectorPlanUID], services_server_headers());
        }

        $pageTitle = match($flow) {
            'renewal' => 'Renew Your Subscription',
            'upgrade' => 'Upgrade Your Plan',
            default   => 'Complete Your Payment',
        };

        $this->load->view('login/header', ['pageTitle' => $pageTitle]);
        $this->load->view('billingcheckout/index', [
            'jwtData'       => $jwtData,
            'JwtToken'      => $this->pageData['JwtToken'] ?? '',
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

}