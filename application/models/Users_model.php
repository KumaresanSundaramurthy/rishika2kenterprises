<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Users_model extends CI_Model {

    private $ReadDb;

    public function __construct() {
        parent::__construct();
        $this->ReadDb = $this->load->database('ReadDB', TRUE);
    }

    // â”€â”€ Login-users cache (HasLoginAccess=1 only) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function getOrgUsersForCache(int $orgUID): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select("UserUID, FirstName, LastName, CONCAT(FirstName, ' ', LastName) AS FullName");
            $this->ReadDb->from('Users.UserTbl');
            $this->ReadDb->where(['OrgUID' => (int)$orgUID, 'IsDeleted' => 0, 'IsActive' => 1, 'HasLoginAccess' => 1]);
            $this->ReadDb->order_by('FirstName', 'ASC');
            $query = $this->ReadDb->get();
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            notifyError('Users_model::getOrgUsersForCache', $e);
            return [];
        }
    }

    // â”€â”€ All staff dropdown (includes non-login employees) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function getEmployeeDropdownList(int $orgUID): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select("UserUID AS EmployeeUID, UserCode AS EmployeeCode, CONCAT(FirstName, ' ', LastName) AS EmployeeName, HasLoginAccess, SalaryType, BasicSalary, Allowances, Incentives, FixedDeductions");
            $this->ReadDb->from('Users.UserTbl');
            $this->ReadDb->where(['OrgUID' => (int)$orgUID, 'IsDeleted' => 0, 'IsActive' => 1]);
            $this->ReadDb->where("EmployeeStatus !=", 'Terminated');
            $this->ReadDb->order_by('FirstName', 'ASC');
            $query = $this->ReadDb->get();
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            notifyError('Users_model::getEmployeeDropdownList', $e);
            return [];
        }
    }

    // â”€â”€ Staff stats (for header cards) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function getUserStats(int $orgUID): ?object {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select("
                COUNT(*) AS Total,
                SUM(EmployeeStatus = 'Active')     AS Active,
                SUM(EmployeeStatus = 'Resigned')   AS Resigned,
                SUM(EmployeeStatus = 'Terminated') AS Terminated,
                SUM(EmployeeStatus = 'OnLeave')    AS OnLeave,
                SUM(HasLoginAccess = 1)            AS LoginUsers
            ");
            $this->ReadDb->from('Users.UserTbl');
            $this->ReadDb->where(['OrgUID' => (int)$orgUID, 'IsDeleted' => 0]);
            $query = $this->ReadDb->get();
            return $query ? $query->row() : null;
        } catch (Exception $e) {
            notifyError('Users_model::getUserStats', $e);
            return null;
        }
    }

    // â”€â”€ Paginated list â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function getUsersList(int $orgUID, array $filter, int $limit, int $offset): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select(
                'u.UserUID, u.UserCode, u.FirstName, u.LastName, u.UserName,
                 u.EmailAddress, u.MobileNumber,
                 u.HasLoginAccess,
                 u.RoleUID, r.Name AS RoleName,
                 u.DepartmentUID, d.DepartmentName,
                 u.DesignationUID, ds.DesignationName,
                 u.DateOfJoining, u.EmployeeStatus,
                 u.SalaryType, u.BasicSalary,
                 u.IsActive, u.IsLocked, u.LastLoginOn,
                 u.UpdatedOn,
                 CONCAT(IFNULL(ub.FirstName,\'\'), \' \', IFNULL(ub.LastName,\'\')) AS UpdatedBy'
            );
            $this->ReadDb->from('Users.UserTbl u');
            $this->ReadDb->join('UserRole.RolesTbl r',            'r.RoleUID = u.RoleUID AND r.IsDeleted = 0',                     'left');
            $this->ReadDb->join('Organisation.DepartmentTbl d',   'd.DepartmentUID = u.DepartmentUID AND d.IsDeleted = 0',         'left');
            $this->ReadDb->join('Organisation.DesignationTbl ds', 'ds.DesignationUID = u.DesignationUID AND ds.IsDeleted = 0',     'left');
            $this->ReadDb->join('Users.UserTbl ub',               'ub.UserUID = u.UpdatedBy',                                     'left');
            $this->ReadDb->where('u.OrgUID',    $orgUID);
            $this->ReadDb->where('u.IsDeleted', 0);
            $this->_applyFilters($filter);
            $this->ReadDb->order_by('u.UserUID', 'DESC');
            $this->ReadDb->limit($limit, $offset);
            $query = $this->ReadDb->get();
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            notifyError('Users_model::getUsersList', $e);
            return [];
        }
    }

    // â”€â”€ Count â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function getUsersCount(int $orgUID, array $filter): int {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('COUNT(*) AS cnt');
            $this->ReadDb->from('Users.UserTbl u');
            $this->ReadDb->where('u.OrgUID',    $orgUID);
            $this->ReadDb->where('u.IsDeleted', 0);
            $this->_applyFilters($filter);
            $query = $this->ReadDb->get();
            $row   = $query ? $query->row() : null;
            return $row ? (int)$row->cnt : 0;
        } catch (Exception $e) {
            notifyError('Users_model::getUsersCount', $e);
            return 0;
        }
    }

    // â”€â”€ Single record + addresses + HR fields â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function getUserById(int $userUID, int $orgUID): ?object {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select(
                'u.UserUID, u.UserCode, u.FirstName, u.LastName, u.UserName,
                 u.EmailAddress, u.MobileNumber, u.CountryCode, u.CountryISO2,
                 u.HasLoginAccess,
                 u.RoleUID, u.IsActive, u.IsLocked, u.LastLoginOn,
                 u.DepartmentUID, d.DepartmentName,
                 u.DesignationUID, ds.DesignationName,
                 u.DateOfJoining, u.EmployeeStatus,
                 u.EmploymentType, u.WorkEmail, u.WorkPhone,
                 u.ProbationEndDate, u.NoticePeriodDays,
                 u.ReportingManagerUID,
                 CONCAT(IFNULL(rm.FirstName,\'\'), \' \', IFNULL(rm.LastName,\'\')) AS ReportingManagerName,
                 u.LastWorkingDate, u.ExitReason,
                 u.SalaryType, u.BasicSalary, u.Allowances, u.Incentives, u.FixedDeductions'
            );
            $this->ReadDb->from('Users.UserTbl u');
            $this->ReadDb->join('Organisation.DepartmentTbl d',   'd.DepartmentUID = u.DepartmentUID AND d.IsDeleted = 0',     'left');
            $this->ReadDb->join('Organisation.DesignationTbl ds', 'ds.DesignationUID = u.DesignationUID AND ds.IsDeleted = 0', 'left');
            $this->ReadDb->join('Users.UserTbl rm',               'rm.UserUID = u.ReportingManagerUID AND rm.IsDeleted = 0',   'left');
            $this->ReadDb->where('u.UserUID',   $userUID);
            $this->ReadDb->where('u.OrgUID',    $orgUID);
            $this->ReadDb->where('u.IsDeleted', 0);
            $this->ReadDb->limit(1);
            $query = $this->ReadDb->get();
            $user  = $query ? $query->row() : null;
            if (!$user) return null;

            $user->Addresses = $this->getUserAddresses($userUID);
            return $user;
        } catch (Exception $e) {
            notifyError('Users_model::getUserById', $e);
            return null;
        }
    }

    // â”€â”€ Org users for reporting-manager dropdown â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function getOrgUsersForDropdown(int $orgUID): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select("UserUID, CONCAT(FirstName, ' ', IFNULL(LastName,'')) AS FullName");
            $this->ReadDb->from('Users.UserTbl');
            $this->ReadDb->where(['OrgUID' => (int)$orgUID, 'IsDeleted' => 0, 'IsActive' => 1]);
            $this->ReadDb->order_by('FirstName', 'ASC');
            $q = $this->ReadDb->get();
            return $q ? $q->result() : [];
        } catch (Exception $e) {
            notifyError('Users_model::getOrgUsersForDropdown', $e);
            return [];
        }
    }

    // â”€â”€ User addresses â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function getUserAddresses(int $userUID): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('AddressUID, AddressType, AddressLine1, AddressLine2, City, State, PinCode, Country');
            $this->ReadDb->from('Users.UserAddressTbl');
            $this->ReadDb->where('UserUID',   $userUID);
            $this->ReadDb->where('IsDeleted', 0);
            $query = $this->ReadDb->get();
            $rows  = $query ? $query->result() : [];
            $out   = ['Current' => null, 'Permanent' => null];
            foreach ($rows as $r) {
                $out[$r->AddressType] = $r;
            }
            return $out;
        } catch (Exception $e) {
            notifyError('Users_model::getUserAddresses', $e);
            return ['Current' => null, 'Permanent' => null];
        }
    }

    public function getUserAddressForType(int $userUID, string $addressType): ?object {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('AddressUID');
            $this->ReadDb->from('Users.UserAddressTbl');
            $this->ReadDb->where('UserUID',     (int)$userUID);
            $this->ReadDb->where('AddressType', $addressType);
            $this->ReadDb->where('IsDeleted',   0);
            $this->ReadDb->limit(1);
            return $this->ReadDb->get()->row();
        } catch (Exception $e) {
            notifyError('Users_model::getUserAddressForType', $e);
            return null;
        }
    }

    // â”€â”€ Next employee code â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    private function _parseEmpCodeFormat(?object $fmt): array {
        $prefix    = strtoupper(trim($fmt->EmpCodePrefix ?? 'EMP'));
        $separator = $fmt->EmpCodeSeparator ?? '-';
        $digits    = (int)($fmt->EmpCodeDigits ?? 4);
        if (!$prefix || !preg_match('/^[A-Z0-9]{1,10}$/', $prefix)) $prefix = 'EMP';
        if ($separator === 'none') $separator = '';
        if (!in_array($separator, ['-', '/', ''])) $separator = '-';
        if ($digits < 3 || $digits > 6) $digits = 4;
        return [$prefix, $separator, $digits];
    }

    public function getNextEmployeeCode(int $orgUID): string {
        try {
            $this->ReadDb->db_debug = FALSE;
            $fmt = $this->ReadDb->select('EmpCodePrefix, EmpCodeSeparator, EmpCodeDigits')
                                ->get_where('Settings.OrgSettingsTbl', ['OrgUID' => $orgUID])
                                ->row();
            [$prefix, $separator, $digits] = $this->_parseEmpCodeFormat($fmt);

            $row     = $this->ReadDb->select('EmpCodeLastNum')
                                    ->get_where('Settings.OrgCreditSettingsTbl', ['OrgUID' => $orgUID])
                                    ->row();
            $nextNum = (int)($row->EmpCodeLastNum ?? 0) + 1;

            return $prefix . $separator . str_pad($nextNum, $digits, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            notifyError('Users_model::getNextEmployeeCode', $e);
            return 'EMP-0001';
        }
    }

    public function claimNextEmployeeCode(int $orgUID): string {
        try {
            $this->ReadDb->db_debug = FALSE;
            $fmt = $this->ReadDb->select('EmpCodePrefix, EmpCodeSeparator, EmpCodeDigits')
                                ->get_where('Settings.OrgSettingsTbl', ['OrgUID' => $orgUID])
                                ->row();
            [$prefix, $separator, $digits] = $this->_parseEmpCodeFormat($fmt);

            $this->load->model('dbwrite_model');
            $db = $this->dbwrite_model->getWriteDb();
            $db->db_debug = FALSE;
            $db->set('EmpCodeLastNum', 'EmpCodeLastNum + 1', FALSE)
               ->set('UpdatedAt', date('Y-m-d H:i:s'))
               ->where('OrgUID', $orgUID)
               ->update('Settings.OrgCreditSettingsTbl');

            if ($db->affected_rows() > 0) {
                $row     = $this->ReadDb->select('EmpCodeLastNum')
                                        ->get_where('Settings.OrgCreditSettingsTbl', ['OrgUID' => $orgUID])
                                        ->row();
                $nextNum = (int)($row->EmpCodeLastNum ?? 1);
            } else {
                // OrgCreditSettingsTbl row not yet seeded — fall back to MAX scan
                $this->ReadDb->select('COALESCE(MAX(CAST(REGEXP_REPLACE(EmployeeCode, "[^0-9]", "") AS UNSIGNED)), 0) + 1 AS NextNum');
                $this->ReadDb->from('Users.UserTbl');
                $this->ReadDb->where('OrgUID', $orgUID);
                $this->ReadDb->where('IsDeleted', 0);
                $maxRow  = $this->ReadDb->get()->row();
                $nextNum = max(1, (int)($maxRow->NextNum ?? 1));
            }

            return $prefix . $separator . str_pad($nextNum, $digits, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            notifyError('Users_model::claimNextEmployeeCode', $e);
            return 'EMP-0001';
        }
    }

    // â”€â”€ Department paginated list â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function getDepartmentListPaginated(int $orgUID, int $limit, int $offset, array $filter = []): object {
        try {
            $this->ReadDb->db_debug = FALSE;
            $search = trim($filter['SearchAllData'] ?? '');

            $this->ReadDb->select('COUNT(*) AS cnt');
            $this->ReadDb->from('Organisation.DepartmentTbl');
            $this->ReadDb->group_start();
            $this->ReadDb->where('OrgUID', (int)$orgUID);
            $this->ReadDb->or_where('OrgUID', 0);
            $this->ReadDb->group_end();
            $this->ReadDb->where('IsDeleted', 0);
            if ($search !== '') {
                $term = $this->ReadDb->escape_like_str($search);
                $this->ReadDb->group_start();
                $this->ReadDb->like('DepartmentName', $term);
                $this->ReadDb->or_like('Description',   $term);
                $this->ReadDb->group_end();
            }
            $total = (int)($this->ReadDb->get()->row()->cnt ?? 0);

            $this->ReadDb->select('DepartmentUID AS TablePrimaryUID, DepartmentUID, DepartmentName, Description, OrgUID, IsActive');
            $this->ReadDb->from('Organisation.DepartmentTbl');
            $this->ReadDb->group_start();
            $this->ReadDb->where('OrgUID', (int)$orgUID);
            $this->ReadDb->or_where('OrgUID', 0);
            $this->ReadDb->group_end();
            $this->ReadDb->where('IsDeleted', 0);
            if ($search !== '') {
                $term = $this->ReadDb->escape_like_str($search);
                $this->ReadDb->group_start();
                $this->ReadDb->like('DepartmentName', $term);
                $this->ReadDb->or_like('Description',   $term);
                $this->ReadDb->group_end();
            }
            $this->ReadDb->order_by('OrgUID', 'DESC');
            $this->ReadDb->order_by('DepartmentName', 'ASC');
            $this->ReadDb->limit($limit, $offset);
            $rows = $this->ReadDb->get()->result();

            $r = new stdClass();
            $r->rows       = $rows;
            $r->totalCount = $total;
            return $r;
        } catch (Exception $e) {
            notifyError('Users_model::getDepartmentListPaginated', $e);
            $r = new stdClass(); $r->rows = []; $r->totalCount = 0; return $r;
        }
    }

    // â”€â”€ Designation paginated list â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function getDesignationListPaginated(int $orgUID, int $limit, int $offset, array $filter = []): object {
        try {
            $this->ReadDb->db_debug = FALSE;
            $search = trim($filter['SearchAllData'] ?? '');

            $this->ReadDb->select('COUNT(*) AS cnt');
            $this->ReadDb->from('Organisation.DesignationTbl');
            $this->ReadDb->group_start();
            $this->ReadDb->where('OrgUID', (int)$orgUID);
            $this->ReadDb->or_where('OrgUID', 0);
            $this->ReadDb->group_end();
            $this->ReadDb->where('IsDeleted', 0);
            if ($search !== '') {
                $term = $this->ReadDb->escape_like_str($search);
                $this->ReadDb->group_start();
                $this->ReadDb->like('DesignationName', $term);
                $this->ReadDb->or_like('Description',  $term);
                $this->ReadDb->group_end();
            }
            $total = (int)($this->ReadDb->get()->row()->cnt ?? 0);

            $this->ReadDb->select('DesignationUID AS TablePrimaryUID, DesignationUID, DesignationName, Description, OrgUID, IsActive');
            $this->ReadDb->from('Organisation.DesignationTbl');
            $this->ReadDb->group_start();
            $this->ReadDb->where('OrgUID', (int)$orgUID);
            $this->ReadDb->or_where('OrgUID', 0);
            $this->ReadDb->group_end();
            $this->ReadDb->where('IsDeleted', 0);
            if ($search !== '') {
                $term = $this->ReadDb->escape_like_str($search);
                $this->ReadDb->group_start();
                $this->ReadDb->like('DesignationName', $term);
                $this->ReadDb->or_like('Description',  $term);
                $this->ReadDb->group_end();
            }
            $this->ReadDb->order_by('OrgUID', 'ASC');
            $this->ReadDb->order_by('DesignationName', 'ASC');
            $this->ReadDb->limit($limit, $offset);
            $rows = $this->ReadDb->get()->result();

            $r = new stdClass();
            $r->rows       = $rows;
            $r->totalCount = $total;
            return $r;
        } catch (Exception $e) {
            notifyError('Users_model::getDesignationListPaginated', $e);
            $r = new stdClass(); $r->rows = []; $r->totalCount = 0; return $r;
        }
    }

    // â”€â”€ Holiday paginated list â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function getHolidayListPaginated(int $orgUID, int $limit, int $offset, array $filter = []): object {
        try {
            $this->ReadDb->db_debug = FALSE;
            $search = trim($filter['SearchAllData'] ?? '');

            // Build index-friendly date range (avoids YEAR()/MONTH() function calls on the column)
            $dateStart = null;
            $dateEnd   = null;
            if (!empty($filter['Year'])) {
                $yr = (int)$filter['Year'];
                if (!empty($filter['Month'])) {
                    $mo        = str_pad((int)$filter['Month'], 2, '0', STR_PAD_LEFT);
                    $dateStart = "{$yr}-{$mo}-01";
                    $dateEnd   = date('Y-m-t', strtotime($dateStart));
                } else {
                    $dateStart = "{$yr}-01-01";
                    $dateEnd   = "{$yr}-12-31";
                }
            }

            // Single query: COUNT(*) OVER() window function avoids a separate COUNT round-trip
            $this->ReadDb->select('HolidayUID AS TablePrimaryUID, HolidayUID, HolidayName, HolidayDate, Description, IsOptional, IsActive, OrgUID, COUNT(*) OVER() AS _TotalRows', FALSE);
            $this->ReadDb->from('Organisation.HolidayTbl');
            $this->ReadDb->where(['OrgUID' => (int)$orgUID, 'IsDeleted' => 0]);
            if ($dateStart) {
                $this->ReadDb->where('HolidayDate >=', $dateStart);
                $this->ReadDb->where('HolidayDate <=', $dateEnd);
            }
            if ($search !== '') {
                $term = $this->ReadDb->escape_like_str($search);
                $this->ReadDb->group_start();
                $this->ReadDb->like('HolidayName', $term);
                $this->ReadDb->or_like('Description', $term);
                $this->ReadDb->group_end();
            }
            $this->ReadDb->order_by('HolidayDate', 'ASC');
            $this->ReadDb->limit($limit, $offset);
            $q    = $this->ReadDb->get();
            $rows = $q ? $q->result() : [];

            $total = !empty($rows) ? (int)($rows[0]->_TotalRows ?? 0) : 0;
            foreach ($rows as $row) { unset($row->_TotalRows); }

            $r = new stdClass();
            $r->rows       = $rows;
            $r->totalCount = $total;
            return $r;
        } catch (Exception $e) {
            notifyError('Users_model::getHolidayListPaginated', $e);
            $r = new stdClass(); $r->rows = []; $r->totalCount = 0; return $r;
        }
    }

    // â”€â”€ Departments â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function getDepartmentList(int $orgUID): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('DepartmentUID, DepartmentName');
            $this->ReadDb->from('Organisation.DepartmentTbl');
            $this->ReadDb->group_start();
            $this->ReadDb->where('OrgUID', (int)$orgUID);
            $this->ReadDb->or_where('OrgUID', 0);
            $this->ReadDb->group_end();
            $this->ReadDb->where(['IsDeleted' => 0, 'IsActive' => 1]);
            $this->ReadDb->order_by('DepartmentName', 'ASC');
            return $this->ReadDb->get()->result();
        } catch (Exception $e) {
            notifyError('Users_model::getDepartmentList', $e);
            return [];
        }
    }

    // â”€â”€ Designations â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function getDesignationList(int $orgUID): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('DesignationUID, DesignationName');
            $this->ReadDb->from('Organisation.DesignationTbl');
            $this->ReadDb->group_start();
            $this->ReadDb->where('OrgUID', (int)$orgUID);
            $this->ReadDb->or_where('OrgUID', 0);
            $this->ReadDb->group_end();
            $this->ReadDb->where(['IsDeleted' => 0, 'IsActive' => 1]);
            $this->ReadDb->order_by('DesignationName', 'ASC');
            return $this->ReadDb->get()->result();
        } catch (Exception $e) {
            notifyError('Users_model::getDesignationList', $e);
            return [];
        }
    }

    // â”€â”€ Password helpers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function getUserByPasswordToken(string $token): ?object {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('UserUID, FirstName, EmailAddress, IsPasswordSet');
            $this->ReadDb->from('Users.UserTbl');
            $this->ReadDb->where('PasswordSetToken', $token);
            $this->ReadDb->where('IsDeleted', 0);
            $this->ReadDb->limit(1);
            return $this->ReadDb->get()->row();
        } catch (Throwable $e) {
            notifyError('Users_model::getUserByPasswordToken', $e);
            return null;
        }
    }

    public function updateUserPassword(int $userUID, string $password): void {
        $this->dbwrite_model->updateData('Users', 'UserTbl', [
            'Password'      => base64_encode($password),
            'IsPasswordSet' => 1,
            'UpdatedOn'     => date('Y-m-d H:i:s'),
        ], ['UserUID' => (int)$userUID]);
    }

    // â”€â”€ User attachments â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function getUserAttachments(int $userUID, int $orgUID): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('AttachUID, FileName, FilePath, FileType, FileSize, DocType, CreatedOn');
            $this->ReadDb->from('Users.UserAttachmentTbl');
            $this->ReadDb->where(['UserUID' => (int)$userUID, 'OrgUID' => (int)$orgUID, 'IsDeleted' => 0]);
            $this->ReadDb->where("(RefType = 'Profile' OR RefType IS NULL)");
            $this->ReadDb->order_by('AttachUID', 'ASC');
            $q = $this->ReadDb->get();
            return $q ? $q->result() : [];
        } catch (Exception $e) {
            notifyError('Users_model::getUserAttachments', $e);
            return [];
        }
    }

    public function getExpenseAttachments(int $expenseUID, int $userUID, int $orgUID): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('AttachUID, FileName, FilePath, FileType, FileSize, CreatedOn');
            $this->ReadDb->from('Users.UserAttachmentTbl');
            $this->ReadDb->where(['RefType' => 'Expense', 'RefUID' => (int)$expenseUID, 'UserUID' => (int)$userUID, 'OrgUID' => (int)$orgUID, 'IsDeleted' => 0]);
            $this->ReadDb->order_by('AttachUID', 'ASC');
            $q = $this->ReadDb->get();
            return $q ? $q->result() : [];
        } catch (Exception $e) {
            notifyError('Users_model::getExpenseAttachments', $e);
            return [];
        }
    }

    // â”€â”€ Emergency contacts â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function getEmergencyContacts(int $userUID): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('EmgContactUID, Name, Relationship, PhoneNumber, EmailAddress, AddressLine1, AddressLine2, City, State, Country, IsPrimary');
            $this->ReadDb->from('Users.UserEmergencyContactTbl');
            $this->ReadDb->where(['UserUID' => (int)$userUID, 'IsDeleted' => 0]);
            $this->ReadDb->order_by('IsPrimary', 'DESC');
            $this->ReadDb->order_by('EmgContactUID', 'ASC');
            $q = $this->ReadDb->get();
            return $q ? $q->result() : [];
        } catch (Exception $e) {
            notifyError('Users_model::getEmergencyContacts', $e);
            return [];
        }
    }

    // â”€â”€ Education list â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function getEducationList(int $userUID): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('EduUID, Institution, Degree, FieldOfStudy, CGPA, DateOfCompletion');
            $this->ReadDb->from('Users.UserEducationTbl');
            $this->ReadDb->where(['UserUID' => (int)$userUID, 'IsDeleted' => 0]);
            $this->ReadDb->order_by('EduUID', 'ASC');
            $q = $this->ReadDb->get();
            return $q ? $q->result() : [];
        } catch (Exception $e) {
            notifyError('Users_model::getEducationList', $e);
            return [];
        }
    }

    // â”€â”€ Experience list â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function getExperienceList(int $userUID): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('ExpUID, EmployerName, JobTitle, StartDate, EndDate, JobDescription');
            $this->ReadDb->from('Users.UserExperienceTbl');
            $this->ReadDb->where(['UserUID' => (int)$userUID, 'IsDeleted' => 0]);
            $this->ReadDb->order_by('StartDate', 'DESC');
            $q = $this->ReadDb->get();
            return $q ? $q->result() : [];
        } catch (Exception $e) {
            notifyError('Users_model::getExperienceList', $e);
            return [];
        }
    }

    // â”€â”€ Bank details â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function getBankDetails(int $userUID): ?object {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('BankDetailUID, BankName, BranchName, IFSCCode, AccountNumber, AccountType, AccountHolder, UpiId, UpiNumber');
            $this->ReadDb->from('Users.UserBankDetailsTbl');
            $this->ReadDb->where(['UserUID' => (int)$userUID, 'IsDeleted' => 0]);
            $this->ReadDb->limit(1);
            $q = $this->ReadDb->get();
            return ($q && $q->num_rows() > 0) ? $q->row() : null;
        } catch (Exception $e) {
            notifyError('Users_model::getBankDetails', $e);
            return null;
        }
    }

    // â”€â”€ Expenses & Reimbursements â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function getExpenseList(int $userUID, int $orgUID): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('ExpenseUID, ReimbursementType, Category, Merchant, Amount, ExpenseDate, Reference, Description, ReceiptPath');
            $this->ReadDb->from('Users.UserExpenseTbl');
            $this->ReadDb->where(['UserUID' => (int)$userUID, 'OrgUID' => (int)$orgUID, 'IsDeleted' => 0]);
            $this->ReadDb->order_by('ExpenseDate', 'DESC');
            $q = $this->ReadDb->get();
            return ($q && $q->num_rows() > 0) ? $q->result() : [];
        } catch (Exception $e) {
            notifyError('Users_model::getExpenseList', $e);
            return [];
        }
    }

    // â”€â”€ Private filter helper â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    private function _applyFilters(array $filter): void {
        if (!empty($filter['EmpStatus']) && $filter['EmpStatus'] !== 'All') {
            $this->ReadDb->where('u.EmployeeStatus', $filter['EmpStatus']);
        }
        if (isset($filter['LoginAccess']) && $filter['LoginAccess'] !== '') {
            $this->ReadDb->where('u.HasLoginAccess', (int)$filter['LoginAccess']);
        }
        if (!empty($filter['DeptUID'])) {
            $this->ReadDb->where('u.DepartmentUID', (int)$filter['DeptUID']);
        }
        if (!empty($filter['Name'])) {
            $term = $this->ReadDb->escape_like_str($filter['Name']);
            $this->ReadDb->group_start();
            $this->ReadDb->like('u.FirstName',       $term, 'both');
            $this->ReadDb->or_like('u.LastName',     $term, 'both');
            $this->ReadDb->or_like('u.UserName',     $term, 'both');
            $this->ReadDb->or_like('u.EmailAddress', $term, 'both');
            $this->ReadDb->or_like('u.EmployeeCode', $term, 'both');
            $this->ReadDb->or_like('u.MobileNumber', $term, 'both');
            $this->ReadDb->group_end();
        }
    }

    // â”€â”€ Branch access assignments for a user â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function getUserBranchAccess(int $userUID, int $orgUID): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $q = $this->ReadDb->query(
                'SELECT BranchUID, BIT_COUNT(IsDefault) AS IsDefault
                 FROM Users.UserBranchAccessTbl
                 WHERE UserUID = ? AND OrgUID = ? AND IsActive = 1',
                [(int)$userUID, (int)$orgUID]
            );
            return $q ? $q->result() : [];
        } catch (Exception $e) {
            notifyError('Users_model::getUserBranchAccess', $e);
            return [];
        }
    }
}
