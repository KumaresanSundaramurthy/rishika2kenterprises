<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Public subscription renewal page — no JWT required.
 * Identified by a short-lived Redis token passed as ?sid= in the URL.
 * All AJAX (createOrder, confirmPayment, prepareCheckout, cancelToken) is handled by NestJS api/renew/*.
 */
class Subscriptionrenew extends CI_Controller {

    public function __construct() {
        parent::__construct();
    }

    /* ── Renew page ──────────────────────────────────────────────────────── */

    public function index(): void {
        $sid    = trim($this->input->get('sid') ?: '');
        $orgUID = $this->_resolveOrgUID($sid);

        if (!$orgUID) {
            $this->load->view('login/header', ['pageTitle' => 'Invalid Link']);
            $this->load->view('subscription/renew_error');
            $this->load->view('login/footer');
            return;
        }

        $orgResp   = services_get('api/billing/orgByUID?orgUID='              . $orgUID, services_server_headers());
        $subResp   = services_get('api/billing/orgSubscription?orgUID='       . $orgUID, services_server_headers());
        $plansResp = services_get('api/billing/billingAvailablePlans?orgUID=' . $orgUID, services_server_headers());

        $org = ($orgResp && empty($orgResp->Error) && !empty($orgResp->Data)) ? $orgResp->Data : null;

        $this->load->view('login/header', ['pageTitle' => 'Renew Subscription']);
        $this->load->view('subscription/renew', [
            'sid'          => $sid,
            'org'          => $org,
            'subscription' => ($subResp && empty($subResp->Error) && !empty($subResp->Data)) ? $subResp->Data : null,
            'plans'        => ($plansResp && empty($plansResp->Error) && !empty($plansResp->Data)) ? $plansResp->Data : [],
        ]);
        $this->load->view('login/footer');
    }

    /* ── Private helpers ──────────────────────────────────────────────────── */

    private function _resolveOrgUID(string $sid): int {
        $data = $this->_getSessionData($sid);
        return $data ? (int)($data->orgUID ?? 0) : 0;
    }

    private function _getSessionData(string $sid): ?object {
        if (empty($sid)) return null;
        $cached = $this->redisservice->getCache($this->redisservice->envKey('rnt_' . $sid));
        if ($cached->Error || $cached->Value === null) return null;
        return is_object($cached->Value) ? $cached->Value : (object)$cached->Value;
    }

}
