<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends MY_Controller {

    public $pageData = array();

    public function __construct() {
        parent::__construct();
    }

    public function index() {
        try {
            $orgUID    = (int)$this->pageData['JwtData']->Org->OrgUID;
            $branchUID = $this->_branchUID();
            $gs        = $this->pageData['JwtData']->GenSettings ?? new stdClass();

            $this->load->model('dashboard_model');

            // All queries run in parallel conceptually — each is a single aggregate query
            $this->pageData['TotalReceivable']   = $this->dashboard_model->getTotalReceivable($orgUID);
            $this->pageData['TotalPayable']      = $this->dashboard_model->getTotalPayable($orgUID);
            $this->pageData['TodaySales']        = $this->dashboard_model->getTodaySales($orgUID, $branchUID);
            $this->pageData['MonthlyComparison'] = $this->dashboard_model->getMonthlySalesComparison($orgUID, $branchUID);
            $this->pageData['SalesChartData']    = $this->dashboard_model->getSalesChartData($orgUID, $branchUID);
            $this->pageData['OverdueInvoices']             = $this->dashboard_model->getOverdueInvoices($orgUID, $branchUID);
            $this->pageData['TopCustomers']                = $this->dashboard_model->getTopCustomers($orgUID);
            $this->pageData['RecentTransactions']          = $this->dashboard_model->getRecentTransactions($orgUID, $branchUID);
            $this->pageData['ReceivableTrend']             = $this->dashboard_model->getReceivableTrend($orgUID, $branchUID);
            $this->pageData['PayableTrend']                = $this->dashboard_model->getPayableTrend($orgUID, $branchUID);
            $this->pageData['MonthlyTrend']                = $this->dashboard_model->getMonthlySalesTrend($orgUID, $branchUID);
            $this->pageData['TodayPurchases']              = $this->dashboard_model->getTodayPurchases($orgUID, $branchUID);
            $this->pageData['MonthlyPurchasesComparison']  = $this->dashboard_model->getMonthlyPurchasesComparison($orgUID, $branchUID);
            $this->pageData['TopVendors']                  = $this->dashboard_model->getTopVendors($orgUID);
            $this->pageData['PendingCounts']               = $this->dashboard_model->getPendingCounts($orgUID, $branchUID);
            $this->pageData['ExpenseSummary']              = $this->dashboard_model->getExpenseSummary($orgUID);
            $this->pageData['TopProducts']                 = $this->dashboard_model->getTopProducts($orgUID, $branchUID);

            $this->pageData['PageTitle']   = 'Dashboard';
            // Use org timezone for Last Updated timestamp
            $userTimezone = $this->pageData['JwtData']->User->Timezone ?? 'UTC';
            $dtFmt        = $gs->ListDateTimeFormat ?? 'd M Y h:i A';
            try {
                $dt = new DateTime('now', new DateTimeZone($userTimezone));
                $this->pageData['LastUpdated'] = $dt->format($dtFmt);
            } catch (Exception $e) {
                $this->pageData['LastUpdated'] = date($dtFmt);
            }

            $this->load->view('dashboard/view', $this->pageData);

        } catch (Exception $e) {
            $this->load->view('dashboard/view', $this->pageData);
        }
    }
}
