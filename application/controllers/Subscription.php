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
        $orgUID = (int)($this->pageData['JwtData']->Org->OrgUID ?? 0);

        $subResp    = services_get('api/billing/orgSubscription?orgUID='   . $orgUID, services_server_headers());
        $plansResp  = services_get('api/billing/billingAvailablePlans?orgUID=' . $orgUID, services_server_headers());
        $ordersResp = services_get('api/billing/orderHistory?orgUID='      . $orgUID . '&limit=10', services_server_headers());

        $this->pageData['subscription'] = ($subResp && empty($subResp->Error) && !empty($subResp->Data)) ? $subResp->Data : null;
        $this->pageData['plans']        = ($plansResp && empty($plansResp->Error) && !empty($plansResp->Data)) ? $plansResp->Data : [];
        $this->pageData['orders']       = ($ordersResp && empty($ordersResp->Error) && !empty($ordersResp->Data)) ? $ordersResp->Data : [];

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

    /* ── Invoice download ───────────────────────────────────────────────────── */

    /**
     * @param int $invoiceUID
     * @return void
     */
    public function invoice(int $invoiceUID): void {
        $orgUID = (int)($this->pageData['JwtData']->Org->OrgUID ?? 0);
        try {
            if ($invoiceUID <= 0) {
                show_error('Invalid invoice.', 400);
                return;
            }

            $resp = services_get('api/billing/subscriptionInvoice?invoiceUID=' . $invoiceUID . '&orgUID=' . $orgUID, services_server_headers());
            $row  = ($resp && empty($resp->Error) && !empty($resp->Data)) ? $resp->Data : null;

            if (!$row || empty($row->PDFPath)) {
                show_error('Invoice PDF is not available yet.', 404);
                return;
            }

            $ch = curl_init($row->PDFPath);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $pdfBytes = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr  = curl_error($ch);
            curl_close($ch);

            if ($curlErr || $httpCode !== 200 || !$pdfBytes) {
                show_error('Could not fetch invoice PDF. Please try again.', 502);
                return;
            }

            $filename = 'Invoice-' . preg_replace('/[^A-Za-z0-9\-]/', '', $row->InvoiceNumber ?? (string)$invoiceUID) . '.pdf';

            $this->output
                ->set_status_header(200)
                ->set_content_type('application/pdf')
                ->set_header('Content-Disposition: attachment; filename="' . $filename . '"')
                ->set_header('Content-Length: ' . strlen($pdfBytes))
                ->set_header('Cache-Control: private, no-store')
                ->set_output($pdfBytes)
                ->_display();
            exit;

        } catch (Exception $e) {
            notifyError('Subscription::invoice', $e);
            show_error('Something went wrong. Please try again.', 500);
        }
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
