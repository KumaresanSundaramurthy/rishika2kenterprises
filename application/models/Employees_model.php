<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Employees_model extends CI_Model {

    private $ReadDb;
    private $WriteDb;

    public function __construct() {
        parent::__construct();
        $this->ReadDb  = $this->load->database('ReadDB',  TRUE);
        $this->WriteDb = $this->load->database('WriteDB', TRUE);
    }

    // ── Employees ─────────────────────────────────────────────────────────────

    public function getEmployeeListPaginated($orgUID, $limit, $offset, $filter = []) {
        $this->ReadDb->db_debug = FALSE;

        $baseWhere = ['E.IsDeleted' => 0, 'E.OrgUID' => $orgUID];

        // COUNT
        $this->ReadDb->select('COUNT(*) AS cnt');
        $this->ReadDb->from('Organisation.EmployeeTbl E');
        $this->ReadDb->where($baseWhere);
        if (!empty($filter['Status']))        $this->ReadDb->where('E.EmployeeStatus', $filter['Status']);
        if (!empty($filter['DeptUID']))       $this->ReadDb->where('E.DepartmentUID',  $filter['DeptUID']);
        if (!empty($filter['DesigUID']))      $this->ReadDb->where('E.DesignationUID', $filter['DesigUID']);
        if (isset($filter['IsActive']))       $this->ReadDb->where('E.IsActive',       (int)$filter['IsActive']);
        if (!empty($filter['SearchAllData'])) {
            $s = $filter['SearchAllData'];
            $this->ReadDb->group_start();
            $this->ReadDb->or_like('E.EmployeeName', $s, 'both');
            $this->ReadDb->or_like('E.EmployeeCode', $s, 'both');
            $this->ReadDb->or_like('E.Mobile',       $s, 'both');
            $this->ReadDb->group_end();
        }
        $cntQ   = $this->ReadDb->get();
        $cntRow = ($cntQ !== false) ? $cntQ->row() : null;
        $total  = (int)($cntRow->cnt ?? 0);

        // DATA
        $this->ReadDb->select([
            'E.EmployeeUID AS TablePrimaryUID',
            'E.EmployeeCode', 'E.EmployeeName', 'E.Mobile', 'E.Email',
            'E.SalaryType', 'E.BasicSalary', 'E.Allowances', 'E.Incentives',
            'E.EmployeeStatus', 'E.IsActive', 'E.DateOfJoining',
            'D.DepartmentName', 'DS.DesignationName',
        ]);
        $this->ReadDb->from('Organisation.EmployeeTbl E');
        $this->ReadDb->join('Organisation.DepartmentTbl  D',  'D.DepartmentUID  = E.DepartmentUID  AND D.IsDeleted  = 0', 'left');
        $this->ReadDb->join('Organisation.DesignationTbl DS', 'DS.DesignationUID = E.DesignationUID AND DS.IsDeleted = 0', 'left');
        $this->ReadDb->where($baseWhere);
        if (!empty($filter['Status']))        $this->ReadDb->where('E.EmployeeStatus', $filter['Status']);
        if (!empty($filter['DeptUID']))       $this->ReadDb->where('E.DepartmentUID',  $filter['DeptUID']);
        if (!empty($filter['DesigUID']))      $this->ReadDb->where('E.DesignationUID', $filter['DesigUID']);
        if (isset($filter['IsActive']))       $this->ReadDb->where('E.IsActive',       (int)$filter['IsActive']);
        if (!empty($filter['SearchAllData'])) {
            $s = $filter['SearchAllData'];
            $this->ReadDb->group_start();
            $this->ReadDb->or_like('E.EmployeeName', $s, 'both');
            $this->ReadDb->or_like('E.EmployeeCode', $s, 'both');
            $this->ReadDb->or_like('E.Mobile',       $s, 'both');
            $this->ReadDb->group_end();
        }
        $this->ReadDb->order_by('E.EmployeeUID', 'DESC');
        $this->ReadDb->limit($limit, $offset);
        $dataQ = $this->ReadDb->get();
        $rows  = ($dataQ !== false) ? $dataQ->result() : [];

        $r = new stdClass();
        $r->rows       = $rows;
        $r->totalCount = $total;
        return $r;
    }

    public function getEmployeeByUID($employeeUID, $orgUID) {
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->select(['E.*', 'D.DepartmentName', 'DS.DesignationName']);
        $this->ReadDb->from('Organisation.EmployeeTbl E');
        $this->ReadDb->join('Organisation.DepartmentTbl  D',  'D.DepartmentUID  = E.DepartmentUID  AND D.IsDeleted  = 0', 'left');
        $this->ReadDb->join('Organisation.DesignationTbl DS', 'DS.DesignationUID = E.DesignationUID AND DS.IsDeleted = 0', 'left');
        $this->ReadDb->where(['E.EmployeeUID' => $employeeUID, 'E.OrgUID' => $orgUID, 'E.IsDeleted' => 0]);
        $q = $this->ReadDb->get();
        return ($q !== false) ? $q->row() : null;
    }

    public function getEmployeeStats($orgUID) {
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->select([
            'COUNT(*) AS Total',
            'SUM(CASE WHEN E.EmployeeStatus = \'Active\'     THEN 1 ELSE 0 END) AS Active',
            'SUM(CASE WHEN E.EmployeeStatus = \'Resigned\'   THEN 1 ELSE 0 END) AS Resigned',
            'SUM(CASE WHEN E.EmployeeStatus = \'Terminated\' THEN 1 ELSE 0 END) AS Terminated',
            'SUM(CASE WHEN E.IsActive = 1 THEN 1 ELSE 0 END) AS IsActiveCount',
        ]);
        $this->ReadDb->from('Organisation.EmployeeTbl E');
        $this->ReadDb->where(['E.OrgUID' => $orgUID, 'E.IsDeleted' => 0]);
        $q = $this->ReadDb->get();
        return ($q !== false) ? ($q->row() ?? new stdClass()) : new stdClass();
    }

    public function getEmployeeDropdownList($orgUID) {
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->select('EmployeeUID, EmployeeName, EmployeeCode, SalaryType, BasicSalary, Allowances, Incentives, FixedDeductions');
        $this->ReadDb->from('Organisation.EmployeeTbl');
        $this->ReadDb->where(['OrgUID' => $orgUID, 'IsDeleted' => 0, 'IsActive' => 1, 'EmployeeStatus' => 'Active']);
        $this->ReadDb->order_by('EmployeeName', 'ASC');
        $q = $this->ReadDb->get();
        return ($q !== false) ? $q->result() : [];
    }

    public function getNextEmployeeCode($orgUID) {
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->select('COUNT(*) AS cnt');
        $this->ReadDb->from('Organisation.EmployeeTbl');
        $this->ReadDb->where(['OrgUID' => $orgUID, 'IsDeleted' => 0]);
        $q   = $this->ReadDb->get();
        $cnt = ($q !== false) ? (int)($q->row()->cnt ?? 0) : 0;
        return 'EMP' . str_pad($cnt + 1, 4, '0', STR_PAD_LEFT);
    }

    // ── Departments ───────────────────────────────────────────────────────────

    public function getDepartmentListPaginated($orgUID, $limit, $offset, $filter = []) {
        $this->ReadDb->db_debug = FALSE;
        $where = ['IsDeleted' => 0];
        $this->ReadDb->group_start();
        $this->ReadDb->where('OrgUID', $orgUID);
        $this->ReadDb->or_where('OrgUID', 0);
        $this->ReadDb->group_end();

        $this->ReadDb->select('COUNT(*) AS cnt');
        $this->ReadDb->from('Organisation.DepartmentTbl');
        $this->ReadDb->where($where);
        if (!empty($filter['SearchAllData'])) $this->ReadDb->like('DepartmentName', $filter['SearchAllData'], 'both');
        $cntQ  = $this->ReadDb->get();
        $total = ($cntQ !== false) ? (int)($cntQ->row()->cnt ?? 0) : 0;

        $this->ReadDb->select('DepartmentUID AS TablePrimaryUID, DepartmentName, Description, IsActive, OrgUID');
        $this->ReadDb->from('Organisation.DepartmentTbl');
        $this->ReadDb->where($where);
        $this->ReadDb->group_start();
        $this->ReadDb->where('OrgUID', $orgUID);
        $this->ReadDb->or_where('OrgUID', 0);
        $this->ReadDb->group_end();
        if (!empty($filter['SearchAllData'])) $this->ReadDb->like('DepartmentName', $filter['SearchAllData'], 'both');
        $this->ReadDb->order_by('OrgUID', 'ASC');
        $this->ReadDb->order_by('DepartmentName', 'ASC');
        $this->ReadDb->limit($limit, $offset);
        $dataQ = $this->ReadDb->get();
        $rows  = ($dataQ !== false) ? $dataQ->result() : [];

        $r = new stdClass();
        $r->rows       = $rows;
        $r->totalCount = $total;
        return $r;
    }

    public function getDepartmentList($orgUID) {
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->select('DepartmentUID, DepartmentName');
        $this->ReadDb->from('Organisation.DepartmentTbl');
        $this->ReadDb->where('IsDeleted', 0);
        $this->ReadDb->group_start();
        $this->ReadDb->where('OrgUID', $orgUID);
        $this->ReadDb->or_where('OrgUID', 0);
        $this->ReadDb->group_end();
        $this->ReadDb->order_by('DepartmentName', 'ASC');
        $q = $this->ReadDb->get();
        return ($q !== false) ? $q->result() : [];
    }

    // ── Designations ──────────────────────────────────────────────────────────

    public function getDesignationListPaginated($orgUID, $limit, $offset, $filter = []) {
        $this->ReadDb->db_debug = FALSE;
        $where = ['IsDeleted' => 0];

        $this->ReadDb->select('COUNT(*) AS cnt');
        $this->ReadDb->from('Organisation.DesignationTbl');
        $this->ReadDb->where($where);
        $this->ReadDb->group_start();
        $this->ReadDb->where('OrgUID', $orgUID);
        $this->ReadDb->or_where('OrgUID', 0);
        $this->ReadDb->group_end();
        if (!empty($filter['SearchAllData'])) $this->ReadDb->like('DesignationName', $filter['SearchAllData'], 'both');
        $cntQ  = $this->ReadDb->get();
        $total = ($cntQ !== false) ? (int)($cntQ->row()->cnt ?? 0) : 0;

        $this->ReadDb->select('DesignationUID AS TablePrimaryUID, DesignationName, Description, IsActive, OrgUID');
        $this->ReadDb->from('Organisation.DesignationTbl');
        $this->ReadDb->where($where);
        $this->ReadDb->group_start();
        $this->ReadDb->where('OrgUID', $orgUID);
        $this->ReadDb->or_where('OrgUID', 0);
        $this->ReadDb->group_end();
        if (!empty($filter['SearchAllData'])) $this->ReadDb->like('DesignationName', $filter['SearchAllData'], 'both');
        $this->ReadDb->order_by('DesignationName', 'ASC');
        $this->ReadDb->limit($limit, $offset);
        $dataQ = $this->ReadDb->get();
        $rows  = ($dataQ !== false) ? $dataQ->result() : [];

        $r = new stdClass();
        $r->rows       = $rows;
        $r->totalCount = $total;
        return $r;
    }

    public function getDesignationList($orgUID) {
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->select('DesignationUID, DesignationName');
        $this->ReadDb->from('Organisation.DesignationTbl');
        $this->ReadDb->where('IsDeleted', 0);
        $this->ReadDb->group_start();
        $this->ReadDb->where('OrgUID', $orgUID);
        $this->ReadDb->or_where('OrgUID', 0);
        $this->ReadDb->group_end();
        $this->ReadDb->order_by('DesignationName', 'ASC');
        $q = $this->ReadDb->get();
        return ($q !== false) ? $q->result() : [];
    }

    // ── Holidays ──────────────────────────────────────────────────────────────

    public function getHolidayListPaginated($orgUID, $limit, $offset, $filter = []) {
        $this->ReadDb->db_debug = FALSE;
        $where = ['OrgUID' => $orgUID, 'IsDeleted' => 0];

        $this->ReadDb->select('COUNT(*) AS cnt');
        $this->ReadDb->from('Organisation.HolidayTbl');
        $this->ReadDb->where($where);
        if (!empty($filter['Year'])) $this->ReadDb->where('YEAR(HolidayDate)', (int)$filter['Year']);
        $cntQ  = $this->ReadDb->get();
        $total = ($cntQ !== false) ? (int)($cntQ->row()->cnt ?? 0) : 0;

        $this->ReadDb->select('HolidayUID AS TablePrimaryUID, HolidayName, HolidayDate, Description, IsActive');
        $this->ReadDb->from('Organisation.HolidayTbl');
        $this->ReadDb->where($where);
        if (!empty($filter['Year'])) $this->ReadDb->where('YEAR(HolidayDate)', (int)$filter['Year']);
        $this->ReadDb->order_by('HolidayDate', 'ASC');
        $this->ReadDb->limit($limit, $offset);
        $dataQ = $this->ReadDb->get();
        $rows  = ($dataQ !== false) ? $dataQ->result() : [];

        $r = new stdClass();
        $r->rows       = $rows;
        $r->totalCount = $total;
        return $r;
    }

    public function getHolidayDatesForMonth($orgUID, $year, $month) {
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->select('HolidayDate, HolidayName');
        $this->ReadDb->from('Organisation.HolidayTbl');
        $this->ReadDb->where(['OrgUID' => $orgUID, 'IsDeleted' => 0]);
        $this->ReadDb->where('YEAR(HolidayDate)',  $year);
        $this->ReadDb->where('MONTH(HolidayDate)', $month);
        $q = $this->ReadDb->get();
        return ($q !== false) ? $q->result() : [];
    }
}
