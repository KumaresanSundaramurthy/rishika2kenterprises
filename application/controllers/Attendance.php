<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Attendance extends MY_Controller {

    public  $pageData = [];
    private $EndReturnData;

    public function __construct() { parent::__construct(); }

    private function _fetchTableData($pageNo, $limit, $filter = []) {
        $offset = max(0, ($pageNo - 1) * $limit);
        $this->load->model('attendance_model');
        $result  = $this->attendance_model->getAttendanceListPaginated($this->_orgUID(), $limit, $offset, $filter);
        $rowHtml = $this->load->view('hrms/attendance/list', ['DataLists' => $result->rows, 'SerialNumber' => $offset, 'JwtData' => $this->pageData['JwtData']], TRUE);
        $r = new stdClass();
        $r->RecordHtmlData = $rowHtml;
        $r->Pagination     = $this->globalservice->buildPagePaginationHtml('/attendance/getPageDetails', $result->totalCount, $pageNo, $limit);
        $r->TotalCount     = $result->totalCount;
        return $r;
    }

    // ── Daily attendance list page ────────────────────────────────────────────

    public function index() {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $today  = date('Y-m-d');
            $filter = ['DateFrom' => $today, 'DateTo' => $today];
            $pd     = $this->_fetchTableData(1, $this->_rowLimit(), $filter);
            $this->pageData['ModRowData']    = $pd->RecordHtmlData;
            $this->pageData['ModPagination'] = $pd->Pagination;

            $this->load->model('attendance_model');
            $this->load->model('users_model');
            $this->pageData['DailyStats']   = $this->attendance_model->getDailyStats($this->_orgUID(), $today);
            $this->pageData['EmployeeList'] = $this->users_model->getEmployeeDropdownList($this->_orgUID());
            $this->pageData['TodayDate']    = $today;
            $this->load->view('hrms/attendance/view', $this->pageData);
        } catch (Exception $e) { redirect('dashboard', 'refresh'); }
    }

    // ── Monthly grid view ─────────────────────────────────────────────────────

    public function monthly() {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $month = (int)($this->input->get('month') ?: date('m'));
            $year  = (int)($this->input->get('year')  ?: date('Y'));
            $this->load->model('attendance_model');
            $this->load->model('users_model');
            $this->load->model('payroll_model');

            $this->pageData['AttendanceData'] = $this->attendance_model->getMonthlyAttendance($this->_orgUID(), $year, $month);
            $this->pageData['EmployeeList']   = $this->users_model->getEmployeeDropdownList($this->_orgUID());
            $this->pageData['HolidayDates']   = $this->attendance_model->getHolidayDatesForMonth($this->_orgUID(), $year, $month);
            $this->pageData['Month']          = $month;
            $this->pageData['Year']           = $year;
            $this->pageData['DaysInMonth']    = (int)date('t', mktime(0, 0, 0, $month, 1, $year));
            $this->load->view('hrms/attendance/monthly', $this->pageData);
        } catch (Exception $e) { redirect('attendance', 'refresh'); }
    }

    public function getPageDetails($pageNo = 0) {
        $this->EndReturnData = new stdClass();
        try {
            $filter = $this->input->post('Filter') ?: [];
            $pd = $this->_fetchTableData(max(1, (int)$pageNo), $this->_rowLimit(), $filter);
            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->RecordHtmlData = $pd->RecordHtmlData;
            $this->EndReturnData->Pagination     = $pd->Pagination;
            $this->EndReturnData->TotalCount     = $pd->TotalCount;
        } catch (Exception $e) { $this->EndReturnData->Error = TRUE; $this->EndReturnData->Message = $e->getMessage(); }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Save single attendance record ─────────────────────────────────────────

    public function save() {
        $this->EndReturnData = new stdClass();
        try {
            $p   = $this->input->post();
            $uid = (int)($p['AttendanceUID'] ?? 0);
            if (empty($p['EmployeeUID']))    throw new Exception('Employee is required.');
            if (empty($p['AttendanceDate'])) throw new Exception('Date is required.');
            if (empty($p['Status']))         throw new Exception('Status is required.');

            $validStatus = ['Present','Absent','HalfDay','Leave','Holiday','WeekOff'];
            if (!in_array($p['Status'], $validStatus)) throw new Exception('Invalid status.');

            $cin  = !empty($p['CheckInTime'])  ? $p['CheckInTime']  : NULL;
            $cout = !empty($p['CheckOutTime']) ? $p['CheckOutTime'] : NULL;
            $hrs  = NULL;
            if ($cin && $cout) {
                $diff = strtotime($cout) - strtotime($cin);
                $hrs  = $diff > 0 ? round($diff / 3600, 2) : NULL;
            }

            $this->load->model('dbwrite_model');
            $data = [
                'OrgUID'         => $this->_orgUID(),
                'BranchUID'      => $this->_branchUID(),
                'UserUID'        => (int)$p['EmployeeUID'],
                'AttendanceDate' => $p['AttendanceDate'],
                'Status'         => $p['Status'],
                'CheckInTime'    => $cin,
                'CheckOutTime'   => $cout,
                'WorkingHours'   => $hrs,
                'Remarks'        => trim($p['Remarks'] ?? ''),
                'UpdatedBy'      => $this->_userUID(),
            ];

            if ($uid === 0) {
                $data['CreatedBy'] = $this->_userUID();
                $res = $this->dbwrite_model->insertData('Transaction', 'AttendanceTbl', $data);
            } else {
                $res = $this->dbwrite_model->updateData('Transaction', 'AttendanceTbl', $data, ['AttendanceUID' => $uid, 'OrgUID' => $this->_orgUID()]);
            }
            if ($res->Error) throw new Exception($res->Message);
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Attendance saved.';
        } catch (Exception $e) { $this->EndReturnData->Error = TRUE; $this->EndReturnData->Message = $e->getMessage(); }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Bulk save (mark multiple employees for one date) ─────────────────────

    public function saveBulk() {
        $this->EndReturnData = new stdClass();
        try {
            $records = $this->input->post('Records');
            if (empty($records) || !is_array($records)) throw new Exception('No records provided.');
            $orgUID    = $this->_orgUID();
            $branchUID = $this->_branchUID();
            $userUID   = $this->_userUID();
            $saved = 0;
            $this->load->model('dbwrite_model');
            foreach ($records as $r) {
                if (empty($r['EmployeeUID']) || empty($r['AttendanceDate']) || empty($r['Status'])) continue;
                $data = ['OrgUID' => $orgUID, 'BranchUID' => $branchUID, 'UserUID' => (int)$r['EmployeeUID'], 'AttendanceDate' => $r['AttendanceDate'], 'Status' => $r['Status'], 'Remarks' => trim($r['Remarks'] ?? ''), 'UpdatedBy' => $userUID];
                $uid  = (int)($r['AttendanceUID'] ?? 0);
                if ($uid) {
                    $this->dbwrite_model->updateData('Transaction', 'AttendanceTbl', $data, ['AttendanceUID' => $uid, 'OrgUID' => $orgUID]);
                } else {
                    $data['CreatedBy'] = $userUID;
                    $this->dbwrite_model->insertData('Transaction', 'AttendanceTbl', $data);
                }
                $saved++;
            }
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = $saved . ' record(s) saved.';
        } catch (Exception $e) { $this->EndReturnData->Error = TRUE; $this->EndReturnData->Message = $e->getMessage(); }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function delete() {
        $this->EndReturnData = new stdClass();
        try {
            $uid = (int)$this->input->post('AttendanceUID');
            if (!$uid) throw new Exception('Invalid.');
            $this->load->model('dbwrite_model');
            $res = $this->dbwrite_model->updateData('Transaction', 'AttendanceTbl', ['IsDeleted' => 1, 'UpdatedBy' => $this->_userUID()], ['AttendanceUID' => $uid, 'OrgUID' => $this->_orgUID()]);
            if ($res->Error) throw new Exception($res->Message);
            $this->EndReturnData->Error = FALSE; $this->EndReturnData->Message = 'Deleted.';
        } catch (Exception $e) { $this->EndReturnData->Error = TRUE; $this->EndReturnData->Message = $e->getMessage(); }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function getMonthlyData() {
        $this->EndReturnData = new stdClass();
        try {
            $month = (int)($this->input->post('Month') ?: date('m'));
            $year  = (int)($this->input->post('Year')  ?: date('Y'));
            $this->load->model('attendance_model');
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $this->attendance_model->getMonthlyAttendance($this->_orgUID(), $year, $month);
        } catch (Exception $e) { $this->EndReturnData->Error = TRUE; $this->EndReturnData->Message = $e->getMessage(); }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function getDashboardStats() {
        $this->EndReturnData = new stdClass();
        try {
            $this->load->model('attendance_model');
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $this->attendance_model->getDailyStats($this->_orgUID(), date('Y-m-d'));
        } catch (Exception $e) { $this->EndReturnData->Error = TRUE; $this->EndReturnData->Message = $e->getMessage(); }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }
}
