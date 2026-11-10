<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Attendance extends MY_Controller {

    public  $pageData = [];

    public function __construct() { parent::__construct(); }

    // ── Daily attendance list page ────────────────────────────────────────────

    public function index(): void {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        $this->load->view('hrms/attendance/view', $this->pageData);
    }

    // ── Monthly grid view ─────────────────────────────────────────────────────

    public function monthly() {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $month = (int)($this->input->get('month') ?: date('m'));
            $year  = (int)($this->input->get('year')  ?: date('Y'));
            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';
            $resp     = services_get(
                'api/attendance/getMonthlyData?orgId=' . $this->_orgUID() . '&month=' . $month . '&year=' . $year,
                ['Authorization: Bearer ' . $jwtToken]
            );
            $this->pageData['AttendanceData'] = $resp->Data->AttendanceData ?? $resp->Data->Data ?? [];
            $this->pageData['EmployeeList']   = (array)($resp->Data->EmployeeList ?? []);
            $this->pageData['HolidayDates']   = (array)($resp->Data->HolidayDates ?? []);
            $this->pageData['Month']          = (int)($resp->Data->Month     ?? $month);
            $this->pageData['Year']           = (int)($resp->Data->Year      ?? $year);
            $this->pageData['DaysInMonth']    = (int)($resp->Data->DaysInMonth ?? date('t', mktime(0, 0, 0, $month, 1, $year)));
            $this->load->view('hrms/attendance/monthly', $this->pageData);
        } catch (Exception $e) { $this->notifyError('Attendance::monthly', $e); redirect('attendance', 'refresh'); }
    }

}