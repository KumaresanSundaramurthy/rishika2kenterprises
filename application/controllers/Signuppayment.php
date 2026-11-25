<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Unified subscribe page controller — handles plan selection + Razorpay payment
 * for three flows: signup (PendingPayment), renewal (Expired), upgrade (Active).
 * Route: /subscribe  →  signuppayment/index
 */
class Signuppayment extends CI_Controller {

    public function __construct() {
        parent::__construct();
    }

    /* ── Subscribe page ───────────────────────────────────────────────── */

    public function index(): void {
        $jwtData = $this->pageData['JwtData'] ?? null;
        if (!$jwtData) {
            redirect('login', 'refresh');
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
        $subResp = services_get('api/billing/orgPlanForPayment?orgUID=' . $orgUID . '&status=' . rawurlencode($statusFilter), services_server_headers());
        $sub = ($subResp && empty($subResp->Error) && !empty($subResp->Data)) ? $subResp->Data : null;

        if (!$sub && $flow !== 'upgrade') {
            redirect('dashboard', 'refresh');
            return;
        }

        $pageTitle = match($flow) {
            'renewal' => 'Renew Your Subscription',
            'upgrade' => 'Upgrade Your Plan',
            default   => 'Choose Your Plan',
        };

        $this->load->view('login/header', ['pageTitle' => $pageTitle]);
        $this->load->view('signup/subscribe', [
            'jwtData'  => $jwtData,
            'jwtToken' => $this->pageData['JwtToken'] ?? '',
            'sub'      => $sub,
            'flow'     => $flow,
            'orgName'  => $jwtData->Org->OrgName ?? '',
            'orgEmail' => $jwtData->User->EmailAddress ?? '',
        ]);
        $this->load->view('login/footer');
    }

}