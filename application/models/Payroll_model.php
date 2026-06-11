<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Payroll_model extends CI_Model {

    private $ReadDb;
    private $WriteDb;

    public function __construct() {
        parent::__construct();
        $this->ReadDb  = $this->load->database('ReadDB',  TRUE);
        $this->WriteDb = $this->load->database('WriteDB', TRUE);
    }

    public function getPayrollListPaginated($orgUID, $limit, $offset, $filter = []) {
        $this->ReadDb->db_debug = FALSE;
        $where = ['P.OrgUID' => $orgUID, 'P.IsDeleted' => 0];

        $this->ReadDb->select('COUNT(*) AS cnt');
        $this->ReadDb->from('Transaction.PayrollTbl P');
        $this->ReadDb->where($where);
        if (!empty($filter['Status'])) $this->ReadDb->where('P.PayrollStatus', $filter['Status']);
        if (!empty($filter['Year']))   $this->ReadDb->where('P.PayrollYear',   (int)$filter['Year']);
        $total = (int)($this->ReadDb->get()->row()->cnt ?? 0);

        $this->ReadDb->select([
            'P.PayrollUID AS TablePrimaryUID',
            'P.PayrollMonth', 'P.PayrollYear', 'P.PayrollStatus',
            'P.TotalEmployees', 'P.TotalGrossSalary', 'P.TotalDeductions', 'P.TotalNetSalary',
            'P.ProcessedOn', 'P.Notes', 'P.CreatedOn',
        ]);
        $this->ReadDb->from('Transaction.PayrollTbl P');
        $this->ReadDb->where($where);
        if (!empty($filter['Status'])) $this->ReadDb->where('P.PayrollStatus', $filter['Status']);
        if (!empty($filter['Year']))   $this->ReadDb->where('P.PayrollYear',   (int)$filter['Year']);
        $this->ReadDb->order_by('P.PayrollYear',  'DESC');
        $this->ReadDb->order_by('P.PayrollMonth', 'DESC');
        $this->ReadDb->limit($limit, $offset);
        $rows = $this->ReadDb->get()->result();

        $r = new stdClass();
        $r->rows       = $rows;
        $r->totalCount = $total;
        return $r;
    }

    public function getPayrollByUID($payrollUID, $orgUID) {
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->from('Transaction.PayrollTbl');
        $this->ReadDb->where(['PayrollUID' => $payrollUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]);
        return $this->ReadDb->get()->row();
    }

    public function getPayrollExists($orgUID, $branchUID, $month, $year) {
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->select('PayrollUID, PayrollStatus');
        $this->ReadDb->from('Transaction.PayrollTbl');
        $this->ReadDb->where(['OrgUID' => $orgUID, 'BranchUID' => $branchUID, 'PayrollMonth' => $month, 'PayrollYear' => $year, 'IsDeleted' => 0]);
        return $this->ReadDb->get()->row();
    }

    public function getPayrollLines($payrollUID) {
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->select([
            'PL.*', 'E.EmployeeName', 'E.EmployeeCode',
            'D.DepartmentName', 'DS.DesignationName',
        ]);
        $this->ReadDb->from('Transaction.PayrollLineTbl PL');
        $this->ReadDb->join('Organisation.EmployeeTbl  E',  'E.EmployeeUID     = PL.EmployeeUID AND E.IsDeleted  = 0');
        $this->ReadDb->join('Organisation.DepartmentTbl D',  'D.DepartmentUID  = E.DepartmentUID  AND D.IsDeleted  = 0', 'left');
        $this->ReadDb->join('Organisation.DesignationTbl DS','DS.DesignationUID = E.DesignationUID AND DS.IsDeleted = 0', 'left');
        $this->ReadDb->where(['PL.PayrollUID' => $payrollUID, 'PL.IsDeleted' => 0]);
        $this->ReadDb->order_by('E.EmployeeName', 'ASC');
        return $this->ReadDb->get()->result();
    }

    public function getPayrollStats($orgUID) {
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->select([
            'COUNT(*) AS Total',
            'SUM(CASE WHEN PayrollStatus = \'Draft\'     THEN 1 ELSE 0 END) AS Draft',
            'SUM(CASE WHEN PayrollStatus = \'Processed\' THEN 1 ELSE 0 END) AS Processed',
            'SUM(CASE WHEN PayrollStatus = \'Paid\'      THEN 1 ELSE 0 END) AS Paid',
            'SUM(CASE WHEN PayrollStatus = \'Paid\'      THEN TotalNetSalary ELSE 0 END) AS TotalPaid',
        ]);
        $this->ReadDb->from('Transaction.PayrollTbl');
        $this->ReadDb->where(['OrgUID' => $orgUID, 'IsDeleted' => 0]);
        return $this->ReadDb->get()->row() ?? new stdClass();
    }

    // Payslip = one PayrollLine record for one employee
    public function getPayslipListPaginated($orgUID, $limit, $offset, $filter = []) {
        $this->ReadDb->db_debug = FALSE;
        $where = ['P.OrgUID' => $orgUID, 'P.IsDeleted' => 0, 'PL.IsDeleted' => 0];

        $this->ReadDb->select('COUNT(*) AS cnt');
        $this->ReadDb->from('Transaction.PayrollLineTbl PL');
        $this->ReadDb->join('Transaction.PayrollTbl P', 'P.PayrollUID = PL.PayrollUID AND P.IsDeleted = 0');
        $this->ReadDb->where($where);
        if (!empty($filter['EmployeeUID'])) $this->ReadDb->where('PL.EmployeeUID',  (int)$filter['EmployeeUID']);
        if (!empty($filter['Year']))        $this->ReadDb->where('P.PayrollYear',   (int)$filter['Year']);
        if (!empty($filter['Month']))       $this->ReadDb->where('P.PayrollMonth',  (int)$filter['Month']);
        $total = (int)($this->ReadDb->get()->row()->cnt ?? 0);

        $this->ReadDb->select([
            'PL.PayrollLineUID AS TablePrimaryUID',
            'PL.PayrollUID', 'P.PayrollMonth', 'P.PayrollYear', 'P.PayrollStatus',
            'PL.EmployeeUID', 'E.EmployeeName', 'E.EmployeeCode',
            'D.DepartmentName', 'DS.DesignationName',
            'PL.SalaryType', 'PL.GrossSalary', 'PL.NetSalary',
            'PL.PresentDays', 'PL.AbsentDays', 'PL.WorkingDays',
            'PL.AdvanceRecovery', 'PL.SalaryDeduction',
        ]);
        $this->ReadDb->from('Transaction.PayrollLineTbl PL');
        $this->ReadDb->join('Transaction.PayrollTbl P', 'P.PayrollUID = PL.PayrollUID AND P.IsDeleted = 0');
        $this->ReadDb->join('Organisation.EmployeeTbl  E',  'E.EmployeeUID     = PL.EmployeeUID AND E.IsDeleted  = 0');
        $this->ReadDb->join('Organisation.DepartmentTbl D',  'D.DepartmentUID  = E.DepartmentUID  AND D.IsDeleted  = 0', 'left');
        $this->ReadDb->join('Organisation.DesignationTbl DS','DS.DesignationUID = E.DesignationUID AND DS.IsDeleted = 0', 'left');
        $this->ReadDb->where($where);
        if (!empty($filter['EmployeeUID'])) $this->ReadDb->where('PL.EmployeeUID', (int)$filter['EmployeeUID']);
        if (!empty($filter['Year']))        $this->ReadDb->where('P.PayrollYear',  (int)$filter['Year']);
        if (!empty($filter['Month']))       $this->ReadDb->where('P.PayrollMonth', (int)$filter['Month']);
        $this->ReadDb->order_by('P.PayrollYear',  'DESC');
        $this->ReadDb->order_by('P.PayrollMonth', 'DESC');
        $this->ReadDb->order_by('E.EmployeeName', 'ASC');
        $this->ReadDb->limit($limit, $offset);
        $rows = $this->ReadDb->get()->result();

        $r = new stdClass();
        $r->rows       = $rows;
        $r->totalCount = $total;
        return $r;
    }

    public function getPayslipDetail($payrollLineUID, $orgUID) {
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->select([
            'PL.*', 'P.PayrollMonth', 'P.PayrollYear', 'P.PayrollStatus',
            'E.EmployeeName', 'E.EmployeeCode', 'E.Mobile', 'E.Email', 'E.DateOfJoining',
            'D.DepartmentName', 'DS.DesignationName',
        ]);
        $this->ReadDb->from('Transaction.PayrollLineTbl PL');
        $this->ReadDb->join('Transaction.PayrollTbl P', 'P.PayrollUID = PL.PayrollUID AND P.IsDeleted = 0');
        $this->ReadDb->join('Organisation.EmployeeTbl  E',  'E.EmployeeUID     = PL.EmployeeUID AND E.IsDeleted  = 0');
        $this->ReadDb->join('Organisation.DepartmentTbl D',  'D.DepartmentUID  = E.DepartmentUID  AND D.IsDeleted  = 0', 'left');
        $this->ReadDb->join('Organisation.DesignationTbl DS','DS.DesignationUID = E.DesignationUID AND DS.IsDeleted = 0', 'left');
        $this->ReadDb->where(['PL.PayrollLineUID' => $payrollLineUID, 'P.OrgUID' => $orgUID, 'PL.IsDeleted' => 0]);
        return $this->ReadDb->get()->row();
    }

    public function getUnsettledAdvances($empUID, $orgUID) {
        $this->WriteDb->db_debug = FALSE;
        $this->WriteDb->select('AdvanceUID, BalancePending');
        $this->WriteDb->from('Transaction.SalaryAdvanceTbl');
        $this->WriteDb->where(['EmployeeUID' => (int)$empUID, 'OrgUID' => (int)$orgUID, 'IsDeleted' => 0, 'IsSettled' => 0]);
        $this->WriteDb->where('BalancePending >', 0);
        $this->WriteDb->order_by('AdvanceUID', 'ASC');
        return $this->WriteDb->get()->result();
    }

    public function getWorkingDaysInMonth($year, $month) {
        // Count calendar days in month excluding Sundays
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $working = 0;
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dow = date('N', mktime(0, 0, 0, $month, $d, $year));
            if ($dow != 7) $working++; // exclude Sunday
        }
        return $working;
    }
}
