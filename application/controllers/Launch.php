<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Launch extends CI_Controller {
	
	public function __construct() {
        parent::__construct();
    }

    public function home(): void {
        $this->load->view('launch/home');
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
                /* Outer container must be a PHP array; each plan/mod item must be stdClass */
                $plansByCycle = [];
                foreach ((array)$cached['plansByCycle'] as $cycle => $plans) {
                    $plansByCycle[$cycle] = json_decode(json_encode($plans));
                }
                $planModules = [];
                foreach ((array)$cached['planModules'] as $mk => $mods) {
                    $planModules[$mk] = json_decode(json_encode($mods));
                }
                $this->load->view('launch/index', array_merge($commonData, [
                    'plansByCycle' => $plansByCycle,
                    'planModules'  => $planModules,
                ]));
                return;
            }

            /* ── Cache miss: call NestJS, populate cache ── */
            $plansResp  = services_get('api/subscription/public-plans',    services_server_headers(), 10);
            $modsResp   = services_get('api/subscription/all-plan-modules', services_server_headers(), 10);
            $rawPlans   = $plansResp->data ?? [];
            $rawModules = $modsResp->data  ?? [];

            $plansByCycle = [];
            foreach ($rawPlans as $plan) {
                $cycle = strtolower($plan->BillingCycle ?? 'monthly');
                $plansByCycle[$cycle][] = $plan;
            }

            $planModules = [];
            foreach ($rawModules as $mod) {
                $key = $mod->PlanUID . '_' . $mod->SectorUID;
                $planModules[$key][] = $mod;
            }

            /* Cache for 24 hours — invalidate on plan update */
            $this->upstashservice->set('r2k-landing-public-plans', [
                'plansByCycle' => $plansByCycle,
                'planModules'  => $planModules,
            ], 86400);

            $this->load->view('launch/index', array_merge($commonData, [
                'plansByCycle' => $plansByCycle,
                'planModules'  => $planModules,
            ]));

        } catch (Exception $e) {
            notifyError('Launch::index', $e);
            $this->load->view('launch/index', array_merge($commonData, [
                'plansByCycle' => [],
                'planModules'  => [],
            ]));
        }
    }

}