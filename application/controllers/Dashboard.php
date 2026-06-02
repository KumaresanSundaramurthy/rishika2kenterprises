<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends MY_Controller {

    public $pageData = array();

    public function __construct() {
        parent::__construct();
    }

    public function index() {
        try {
            $orgUID  = (int)$this->pageData['JwtData']->Org->OrgUID;
            $gs      = $this->pageData['JwtData']->GenSettings ?? new stdClass();

            $this->load->model('dashboard_model');

            // All queries run in parallel conceptually — each is a single aggregate query
            $this->pageData['TotalReceivable']  = $this->dashboard_model->getTotalReceivable($orgUID);
            $this->pageData['TotalPayable']     = $this->dashboard_model->getTotalPayable($orgUID);
            $this->pageData['TodaySales']       = $this->dashboard_model->getTodaySales($orgUID);
            $this->pageData['MonthlyComparison']= $this->dashboard_model->getMonthlySalesComparison($orgUID);
            $this->pageData['SalesChartData']   = $this->dashboard_model->getSalesChartData($orgUID);
            $this->pageData['OverdueInvoices']  = $this->dashboard_model->getOverdueInvoices($orgUID);
            $this->pageData['TopCustomers']     = $this->dashboard_model->getTopCustomers($orgUID);
            $this->pageData['RecentTransactions']= $this->dashboard_model->getRecentTransactions($orgUID);

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
