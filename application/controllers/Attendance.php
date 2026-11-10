<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Attendance extends MY_Controller {

    public  $pageData = [];
    protected $EndReturnData;

    public function __construct() { parent::__construct(); }

    private function _fetchTableData($pageNo, $limit, $filter = []) {
        $offset   = max(0, ($pageNo - 1) * $limit);
        $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';
        $resp     = services_get(
            'api/attendance/getPageDetails/' . $pageNo
                . '?orgId=' . $this->_orgUID()
                . '&RowLimit=' . $limit
                . '&Filter=' . urlencode(json_encode($filter)),
            ['Authorization: Bearer ' . $jwtToken]
        );
        $rows    = (array)($resp->Data->Data ?? []);
        $total   = (int)($resp->Data->TotalCount ?? 0);
        $rowHtml = $this->load->view('hrms/attendance/list', ['DataLists' => $rows, 'SerialNumber' => $offset, 'JwtData' => $this->pageData['JwtData']], TRUE);
        $r = new stdClass();
        $r->RecordHtmlData = $rowHtml;
        $r->Pagination     = $this->globalservice->buildPagePaginationHtml('/attendance/getPageDetails', $total, $pageNo, $limit);
        $r->TotalCount     = $total;
        return $r;
    }

    // ── Daily attendance list page ────────────────────────────────────────────

    public function index() {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $today    = date('Y-m-d');
            $limit    = $this->_rowLimit();
            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';
            $resp     = services_get(
                'api/attendance/getPageData?orgId=' . $this->_orgUID() . '&branchId=' . $this->_branchUID(),
                ['Authorization: Bearer ' . $jwtToken]
            );
            $rows  = (array)($resp->Data->Data ?? []);
            $total = (int)($resp->Data->TotalCount ?? 0);
            $this->pageData['ModRowData']    = $this->load->view('hrms/attendance/list', ['DataLists' => $rows, 'SerialNumber' => 0, 'JwtData' => $this->pageData['JwtData']], TRUE);
            $this->pageData['ModPagination'] = $this->globalservice->buildPagePaginationHtml('/attendance/getPageDetails', $total, 1, $limit);
            $this->pageData['DailyStats']   = $resp->Data->DailyStats   ?? new stdClass();
            $this->pageData['EmployeeList'] = (array)($resp->Data->EmployeeList ?? []);
            $this->pageData['TodayDate']    = $resp->Data->TodayDate     ?? $today;
            $this->load->view('hrms/attendance/view', $this->pageData);
        } catch (Exception $e) { $this->notifyError('Attendance::index', $e); redirect('dashboard', 'refresh'); }
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

    public function getPageDetails($pageNo = 0) {
        $this->EndReturnData = new stdClass();
        try {
            $filter = $this->input->post('Filter') ?: [];
            $pd = $this->_fetchTableData(max(1, (int)$pageNo), $this->_rowLimit(), $filter);
            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->RecordHtmlData = $pd->RecordHtmlData;
            $this->EndReturnData->Pagination     = $pd->Pagination;
            $this->EndReturnData->TotalCount     = $pd->TotalCount;
        } catch (Exception $e) { $this->notifyError('Attendance::getPageDetails', $e); $this->EndReturnData->Error = TRUE; $this->EndReturnData->Message = $e->getMessage(); }
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

            $this->load->model('dbwrite_model'); $this->load->model('dbwrite_ext_model');
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
        } catch (Exception $e) { $this->notifyError('Attendance::save', $e); $this->EndReturnData->Error = TRUE; $this->EndReturnData->Message = $e->getMessage(); }
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
            $this->load->model('dbwrite_model'); $this->load->model('dbwrite_ext_model');
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
        } catch (Exception $e) { $this->notifyError('Attendance::saveBulk', $e); $this->EndReturnData->Error = TRUE; $this->EndReturnData->Message = $e->getMessage(); }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function delete() {
        $this->EndReturnData = new stdClass();
        try {
            $uid = (int)$this->input->post('AttendanceUID');
            if (!$uid) throw new Exception('Invalid.');
            $this->load->model('dbwrite_model'); $this->load->model('dbwrite_ext_model');
            $res = $this->dbwrite_model->updateData('Transaction', 'AttendanceTbl', ['IsDeleted' => 1, 'UpdatedBy' => $this->_userUID()], ['AttendanceUID' => $uid, 'OrgUID' => $this->_orgUID()]);
            if ($res->Error) throw new Exception($res->Message);
            $this->EndReturnData->Error = FALSE; $this->EndReturnData->Message = 'Deleted.';
        } catch (Exception $e) { $this->notifyError('Attendance::delete', $e); $this->EndReturnData->Error = TRUE; $this->EndReturnData->Message = $e->getMessage(); }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function getMonthlyData() {
        $this->EndReturnData = new stdClass();
        try {
            $month    = (int)($this->input->post('Month') ?: date('m'));
            $year     = (int)($this->input->post('Year')  ?: date('Y'));
            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';
            $resp     = services_get(
                'api/attendance/getMonthlyData?orgId=' . $this->_orgUID() . '&month=' . $month . '&year=' . $year,
                ['Authorization: Bearer ' . $jwtToken]
            );
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $resp->Data->Data ?? [];
        } catch (Exception $e) { $this->notifyError('Attendance::getMonthlyData', $e); $this->EndReturnData->Error = TRUE; $this->EndReturnData->Message = $e->getMessage(); }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function getDashboardStats() {
        $this->EndReturnData = new stdClass();
        try {
            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';
            $resp     = services_get(
                'api/attendance/getDashboardStats?orgId=' . $this->_orgUID(),
                ['Authorization: Bearer ' . $jwtToken]
            );
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $resp->Data->Data ?? new stdClass();
        } catch (Exception $e) { $this->notifyError('Attendance::getDashboardStats', $e); $this->EndReturnData->Error = TRUE; $this->EndReturnData->Message = $e->getMessage(); }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }
}
