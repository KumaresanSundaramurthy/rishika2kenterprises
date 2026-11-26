<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Launch extends CI_Controller {
	
	public function __construct() {
        parent::__construct();
    }

    public function index(): void {
        $this->load->library('upstashservice');

        $commonData = [
            'signupUrl'       => rtrim(getenv('HTTP_HOST_URL') ?: '', '/') . '/signup',
            'loginUrl'        => rtrim(getenv('HTTP_HOST_URL') ?: '', '/') . '/login',
            'enquiryEndpoint' => rtrim(getenv('SERVICES_BASE_URL') ?: '', '/') . '/api/launch/send-enquiry',
        ];

        try {
            /* ── Cache hit: serve in <10ms ── */
            $cached = $this->upstashservice->get('r2k-landing-public-plans');

            if ($cached !== null && isset($cached['plansByCycle'])) {
                $plansByCycle = [];
                foreach ((array)$cached['plansByCycle'] as $cycle => $plans) {
                    $plansByCycle[$cycle] = json_decode(json_encode($plans));
                }
                $this->load->view('launch/index', array_merge($commonData, [
                    'plansByCycle' => $plansByCycle,
                ]));
                return;
            }

            /* ── Cache miss: call NestJS, populate cache ── */
            $plansResp = services_get('api/subscription/public-plans', services_server_headers(), 10);
            $rawPlans  = $plansResp->data ?? [];

            $plansByCycle = [];
            foreach ($rawPlans as $plan) {
                $cycle = strtolower($plan->BillingCycle ?? 'monthly');
                $plansByCycle[$cycle][] = $plan;
            }

            /* Cache for 24 hours — invalidate on plan update */
            $this->upstashservice->set('r2k-landing-public-plans', [
                'plansByCycle' => $plansByCycle,
            ], 86400);

            $this->load->view('launch/index', array_merge($commonData, [
                'plansByCycle' => $plansByCycle,
            ]));

        } catch (Exception $e) {
            notifyError('Launch::index', $e);
            $this->load->view('launch/index', array_merge($commonData, [
                'plansByCycle' => [],
            ]));
        }
    }

}