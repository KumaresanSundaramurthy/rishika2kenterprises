<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Subscription extends MY_Controller {

    public function __construct() {
        parent::__construct();
    }

    /* ── Pages ──────────────────────────────────────────────────────────────── */

    public function index(): void {
        redirect('subscription/dashboard', 'refresh');
    }

    public function dashboard(): void {
        /* Data is loaded via AJAX after page render — no blocking services_get calls here. */
        $this->load->view('common/header');
        $this->load->view('subscription/dashboard', $this->pageData);
        $this->load->view('common/footer');
        $this->load->view('subscription/dashboard_js', $this->pageData);
    }

    public function expired(): void {
        $this->load->view('subscription/expired');
    }

    public function plans(): void {
        $orgUID = (int)($this->pageData['JwtData']->Org->OrgUID ?? 0);

        $subResp   = services_get('api/billing/orgSubscription?orgUID='       . $orgUID, services_server_headers());
        $plansResp = services_get('api/billing/billingAvailablePlans?orgUID=' . $orgUID, services_server_headers());

        $subscription = ($subResp && empty($subResp->Error) && !empty($subResp->Data)) ? $subResp->Data : null;
        $plans        = ($plansResp && empty($plansResp->Error) && !empty($plansResp->Data)) ? $plansResp->Data : [];

        /* Module count per PlanUID */
        $moduleCountMap = [];
        if (!empty($plans)) {
            $sectorUID = (int)($plans[0]->SectorUID ?? 0);
            if ($sectorUID > 0) {
                $cntResp = services_get('api/billing/moduleCountByPlan?sectorUID=' . $sectorUID, services_server_headers());
                if ($cntResp && empty($cntResp->Error) && !empty($cntResp->Data)) {
                    foreach ($cntResp->Data as $r) {
                        $moduleCountMap[(int)$r->PlanUID] = (int)$r->Cnt;
                    }
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

    /* ── Pay a specific pending order ──────────────────────────────────────── */

    /**
     * @param int $orderUID
     * @return void
     */
    public function payOrder(int $orderUID): void {
        $orgUID = (int)($this->pageData['JwtData']->Org->OrgUID ?? 0);

        $resp = services_get('api/billing/pendingOrder?orgUID=' . $orgUID . '&orderUID=' . $orderUID, services_server_headers());
        $order = ($resp && empty($resp->Error) && !empty($resp->Data)) ? $resp->Data : null;

        if (!$order || (float)($order->NetAmount ?? 0) <= 0) {
            redirect('subscription/dashboard', 'refresh');
            return;
        }

        $this->pageData['order']   = $order;
        $this->pageData['orgName'] = $this->pageData['JwtData']->Org->OrgName ?? '';

        $this->load->view('common/header');
        $this->load->view('subscription/pay_order', $this->pageData);
        $this->load->view('common/footer');
        $this->load->view('subscription/pay_order_js', $this->pageData);
    }

}
