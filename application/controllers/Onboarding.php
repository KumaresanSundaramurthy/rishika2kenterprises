<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Onboarding extends MY_Controller {

    public function __construct() {
        parent::__construct();
    }

    /* ── Onboarding page ─────────────────────────────────────────────── */

    public function index(): void {
        $jwtData = $this->pageData['JwtData'] ?? null;

        /* If already complete, send to dashboard */
        if ($jwtData && (int)($jwtData->Org->IsOnboardingComplete ?? 1) === 1) {
            redirect('dashboard', 'refresh');
            return;
        }

        $shortCode  = '';
        $orgUID     = (int)($jwtData->Org->OrgUID ?? 0);

        if ($orgUID > 0) {
            try {
                $resp = services_get(
                    'api/organisation/getOrgShortCode?orgId=' . $orgUID,
                    withJwt: true
                );
                $shortCode = ($resp && empty($resp->Error)) ? ($resp->ShortCode ?? '') : '';
            } catch (Exception $e) {}
        }

        $this->load->view('onboarding/view', [
            'jwtData'   => $jwtData,
            'jwtToken'  => $this->pageData['JwtToken'] ?? '',
            'shortCode' => $shortCode,
        ]);
    }

}