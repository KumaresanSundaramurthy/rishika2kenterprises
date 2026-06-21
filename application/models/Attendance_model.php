<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Attendance_model extends CI_Model {

    private $ReadDb;
    private $WriteDb;

    public function __construct() {
        parent::__construct();
        $this->ReadDb  = $this->load->database('ReadDB',  TRUE);
        $this->WriteDb = $this->load->database('WriteDB', TRUE);
    }

    public function getAttendanceListPaginated($orgUID, $limit, $offset, $filter = []) {
        $this->ReadDb->db_debug = FALSE;
        $where = ['A.IsDeleted' => 0, 'A.OrgUID' => $orgUID];

        $this->ReadDb->select('COUNT(*) AS cnt');
        $this->ReadDb->from('Transaction.AttendanceTbl A');
        $this->ReadDb->where($where);
        $this->_applyAttFilter($filter);
        $total = (int)($this->ReadDb->get()->row()->cnt ?? 0);

        $this->ReadDb->select([
            'A.AttendanceUID AS TablePrimaryUID',
            'A.UserUID AS EmployeeUID',
            'A.AttendanceDate', 'A.Status', 'A.CheckInTime', 'A.CheckOutTime',
            'A.WorkingHours', 'A.Remarks',
            "CONCAT(U.FirstName, ' ', U.LastName) AS EmployeeName",
            'U.EmployeeCode',
            'D.DepartmentName', 'DS.DesignationName',
        ]);
        $this->ReadDb->from('Transaction.AttendanceTbl A');
        $this->ReadDb->join('Users.UserTbl U',            'U.UserUID        = A.UserUID        AND U.IsDeleted  = 0');
        $this->ReadDb->join('Organisation.DepartmentTbl D',  'D.DepartmentUID  = U.DepartmentUID  AND D.IsDeleted = 0', 'left');
        $this->ReadDb->join('Organisation.DesignationTbl DS','DS.DesignationUID = U.DesignationUID AND DS.IsDeleted = 0', 'left');
        $this->ReadDb->where($where);
        $this->_applyAttFilter($filter);
        $this->ReadDb->order_by('A.AttendanceDate', 'DESC');
        $this->ReadDb->order_by('EmployeeName',     'ASC');
        $this->ReadDb->limit($limit, $offset);
        $rows = $this->ReadDb->get()->result();

        $r = new stdClass();
        $r->rows       = $rows;
        $r->totalCount = $total;
        return $r;
    }

    private function _applyAttFilter($filter) {
        if (!empty($filter['EmployeeUID']))   $this->ReadDb->where('A.UserUID',          (int)$filter['EmployeeUID']);
        if (!empty($filter['Status']))        $this->ReadDb->where('A.Status',            $filter['Status']);
        if (!empty($filter['DateFrom']))      $this->ReadDb->where('A.AttendanceDate >=', $filter['DateFrom']);
        if (!empty($filter['DateTo']))        $this->ReadDb->where('A.AttendanceDate <=', $filter['DateTo']);
        if (!empty($filter['Month']))         $this->ReadDb->where('MONTH(A.AttendanceDate)', (int)$filter['Month']);
        if (!empty($filter['Year']))          $this->ReadDb->where('YEAR(A.AttendanceDate)',  (int)$filter['Year']);
    }

    public function getMonthlyAttendance($orgUID, $year, $month) {
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->select([
            'A.AttendanceUID', 'A.UserUID AS EmployeeUID', 'A.AttendanceDate', 'A.Status',
            'A.CheckInTime', 'A.CheckOutTime', 'A.WorkingHours', 'A.Remarks',
        ]);
        $this->ReadDb->from('Transaction.AttendanceTbl A');
        $this->ReadDb->where(['A.OrgUID' => $orgUID, 'A.IsDeleted' => 0]);
        $this->ReadDb->where('YEAR(A.AttendanceDate)',  $year);
        $this->ReadDb->where('MONTH(A.AttendanceDate)', $month);
        $this->ReadDb->order_by('A.UserUID', 'ASC');
        $this->ReadDb->order_by('A.AttendanceDate', 'ASC');
        return $this->ReadDb->get()->result();
    }

    public function getDailyStats($orgUID, $date) {
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->select([
            'SUM(CASE WHEN A.Status = \'Present\'  THEN 1 ELSE 0 END) AS PresentCount',
            'SUM(CASE WHEN A.Status = \'Absent\'   THEN 1 ELSE 0 END) AS AbsentCount',
            'SUM(CASE WHEN A.Status = \'Leave\'    THEN 1 ELSE 0 END) AS LeaveCount',
            'SUM(CASE WHEN A.Status = \'HalfDay\'  THEN 1 ELSE 0 END) AS HalfDayCount',
            'COUNT(DISTINCT A.UserUID) AS MarkedCount',
        ]);
        $this->ReadDb->from('Transaction.AttendanceTbl A');
        $this->ReadDb->where(['A.OrgUID' => $orgUID, 'A.AttendanceDate' => $date, 'A.IsDeleted' => 0]);
        return $this->ReadDb->get()->row() ?? new stdClass();
    }

    public function getAttendanceSummaryForPayroll($orgUID, $year, $month) {
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->select([
            'A.UserUID AS EmployeeUID',
            'SUM(CASE WHEN A.Status = \'Present\'  THEN 1.0 ELSE 0 END) +
             SUM(CASE WHEN A.Status = \'HalfDay\'  THEN 0.5 ELSE 0 END) AS PresentDays',
            'SUM(CASE WHEN A.Status = \'Absent\'   THEN 1.0 ELSE 0 END) AS AbsentDays',
            'SUM(CASE WHEN A.Status IN (\'Present\',\'HalfDay\',\'Leave\',\'Holiday\') THEN IFNULL(A.WorkingHours, 0) ELSE 0 END) AS TotalHours',
        ]);
        $this->ReadDb->from('Transaction.AttendanceTbl A');
        $this->ReadDb->where(['A.OrgUID' => $orgUID, 'A.IsDeleted' => 0]);
        $this->ReadDb->where('YEAR(A.AttendanceDate)',  $year);
        $this->ReadDb->where('MONTH(A.AttendanceDate)', $month);
        $this->ReadDb->group_by('A.UserUID');
        return $this->ReadDb->get()->result();
    }

    public function getAttendanceByUID($uid, $orgUID) {
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->from('Transaction.AttendanceTbl');
        $this->ReadDb->where(['AttendanceUID' => $uid, 'OrgUID' => $orgUID, 'IsDeleted' => 0]);
        return $this->ReadDb->get()->row();
    }

    public function getHolidayDatesForMonth($orgUID, $year, $month) {
        $firstDay = sprintf('%04d-%02d-01', $year, $month);
        $lastDay  = date('Y-m-t', strtotime($firstDay));
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->select('HolidayDate');
        $this->ReadDb->from('Organisation.HolidayTbl');
        $this->ReadDb->where('OrgUID', $orgUID);
        $this->ReadDb->where('IsDeleted', 0);
        $this->ReadDb->where('HolidayDate >=', $firstDay);
        $this->ReadDb->where('HolidayDate <=', $lastDay);
        return array_column($this->ReadDb->get()->result_array(), 'HolidayDate');
    }

    // Salary Advances
    public function getAdvanceListPaginated($orgUID, $limit, $offset, $filter = []) {
        $this->ReadDb->db_debug = FALSE;
        $where = ['SA.OrgUID' => $orgUID, 'SA.IsDeleted' => 0];

        $this->ReadDb->select('COUNT(*) AS cnt');
        $this->ReadDb->from('Transaction.SalaryAdvanceTbl SA');
        $this->ReadDb->where($where);
        if (!empty($filter['EmployeeUID'])) $this->ReadDb->where('SA.UserUID',   (int)$filter['EmployeeUID']);
        if (isset($filter['IsSettled']))    $this->ReadDb->where('SA.IsSettled', (int)$filter['IsSettled']);
        $total = (int)($this->ReadDb->get()->row()->cnt ?? 0);

        $this->ReadDb->select([
            'SA.AdvanceUID AS TablePrimaryUID', 'SA.UserUID AS EmployeeUID',
            'SA.AdvanceDate', 'SA.AdvanceAmount',
            'SA.Reason', 'SA.BalancePending', 'SA.IsSettled',
            "CONCAT(U.FirstName, ' ', U.LastName) AS EmployeeName",
            'U.EmployeeCode',
        ]);
        $this->ReadDb->from('Transaction.SalaryAdvanceTbl SA');
        $this->ReadDb->join('Users.UserTbl U', 'U.UserUID = SA.UserUID AND U.IsDeleted = 0');
        $this->ReadDb->where($where);
        if (!empty($filter['EmployeeUID'])) $this->ReadDb->where('SA.UserUID', (int)$filter['EmployeeUID']);
        if (isset($filter['IsSettled']))    $this->ReadDb->where('SA.IsSettled', (int)$filter['IsSettled']);
        $this->ReadDb->order_by('SA.AdvanceUID', 'DESC');
        $this->ReadDb->limit($limit, $offset);
        $rows = $this->ReadDb->get()->result();

        $r = new stdClass();
        $r->rows       = $rows;
        $r->totalCount = $total;
        return $r;
    }

    public function getPendingAdvanceBalance($employeeUID, $orgUID) {
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->select('IFNULL(SUM(BalancePending), 0) AS TotalPending');
        $this->ReadDb->from('Transaction.SalaryAdvanceTbl');
        $this->ReadDb->where(['UserUID' => $employeeUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0, 'IsSettled' => 0]);
        return (float)($this->ReadDb->get()->row()->TotalPending ?? 0);
    }
}
