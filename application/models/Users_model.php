<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Users_model extends CI_Model {

    private $ReadDb;
    private $WriteDb;

    public function __construct() {
        parent::__construct();
        $this->ReadDb  = $this->load->database('ReadDB',  TRUE);
        $this->WriteDb = $this->load->database('WriteDB', TRUE);
    }

    // ── Login-users cache (HasLoginAccess=1 only) ─────────────────────────────
    public function getOrgUsersForCache($orgUID) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select("UserUID, FirstName, LastName, CONCAT(FirstName, ' ', LastName) AS FullName");
            $this->ReadDb->from('Users.UserTbl');
            $this->ReadDb->where(['OrgUID' => (int)$orgUID, 'IsDeleted' => 0, 'IsActive' => 1, 'HasLoginAccess' => 1]);
            $this->ReadDb->order_by('FirstName', 'ASC');
            $query = $this->ReadDb->get();
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            log_message('error', 'Users_model::getOrgUsersForCache — ' . $e->getMessage());
            return [];
        }
    }

    // ── All staff dropdown (includes non-login employees) ────────────────────
    public function getEmployeeDropdownList($orgUID) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select("UserUID AS EmployeeUID, EmployeeCode, CONCAT(FirstName, ' ', LastName) AS EmployeeName, HasLoginAccess, SalaryType, BasicSalary, Allowances, Incentives, FixedDeductions");
            $this->ReadDb->from('Users.UserTbl');
            $this->ReadDb->where(['OrgUID' => (int)$orgUID, 'IsDeleted' => 0, 'IsActive' => 1]);
            $this->ReadDb->where("EmployeeStatus !=", 'Terminated');
            $this->ReadDb->order_by('FirstName', 'ASC');
            $query = $this->ReadDb->get();
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            log_message('error', 'Users_model::getEmployeeDropdownList — ' . $e->getMessage());
            return [];
        }
    }

    // ── Staff stats (for header cards) ───────────────────────────────────────
    public function getUserStats($orgUID) {
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
            log_message('error', 'Users_model::getUserStats — ' . $e->getMessage());
            return null;
        }
    }

    // ── Paginated list ────────────────────────────────────────────────────────
    public function getUsersList($orgUID, $filter, $limit, $offset) {
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
            log_message('error', 'Users_model::getUsersList — ' . $e->getMessage());
            return [];
        }
    }

    // ── Count ─────────────────────────────────────────────────────────────────
    public function getUsersCount($orgUID, $filter) {
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
            log_message('error', 'Users_model::getUsersCount — ' . $e->getMessage());
            return 0;
        }
    }

    // ── Single record + addresses + HR fields ─────────────────────────────────
    public function getUserById($userUID, $orgUID) {
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
            log_message('error', 'Users_model::getUserById — ' . $e->getMessage());
            return null;
        }
    }

    // ── Org users for reporting-manager dropdown ──────────────────────────────
    public function getOrgUsersForDropdown($orgUID) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select("UserUID, CONCAT(FirstName, ' ', IFNULL(LastName,'')) AS FullName");
            $this->ReadDb->from('Users.UserTbl');
            $this->ReadDb->where(['OrgUID' => (int)$orgUID, 'IsDeleted' => 0, 'IsActive' => 1]);
            $this->ReadDb->order_by('FirstName', 'ASC');
            $q = $this->ReadDb->get();
            return $q ? $q->result() : [];
        } catch (Exception $e) {
            log_message('error', 'Users_model::getOrgUsersForDropdown — ' . $e->getMessage());
            return [];
        }
    }

    // ── User addresses ────────────────────────────────────────────────────────
    public function getUserAddresses($userUID) {
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
            log_message('error', 'Users_model::getUserAddresses — ' . $e->getMessage());
            return ['Current' => null, 'Permanent' => null];
        }
    }

    public function getUserAddressForType($userUID, $addressType) {
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
            log_message('error', 'Users_model::getUserAddressForType — ' . $e->getMessage());
            return null;
        }
    }

    // ── Next employee code ────────────────────────────────────────────────────
    public function getNextEmployeeCode($orgUID) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('MAX(CAST(SUBSTRING_INDEX(EmployeeCode, \'-\', -1) AS UNSIGNED)) AS MaxNum');
            $this->ReadDb->from('Users.UserTbl');
            $this->ReadDb->where('OrgUID',    (int)$orgUID);
            $this->ReadDb->where('IsDeleted', 0);
            $this->ReadDb->like('EmployeeCode', 'EMP-', 'after');
            $row = $this->ReadDb->get()->row();
            $next = (int)($row->MaxNum ?? 0) + 1;
            return 'EMP-' . str_pad($next, 4, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            return 'EMP-0001';
        }
    }

    // ── Department paginated list ─────────────────────────────────────────────
    public function getDepartmentListPaginated($orgUID, $limit, $offset, $filter = []) {
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
            log_message('error', 'Users_model::getDepartmentListPaginated — ' . $e->getMessage());
            $r = new stdClass(); $r->rows = []; $r->totalCount = 0; return $r;
        }
    }

    // ── Designation paginated list ────────────────────────────────────────────
    public function getDesignationListPaginated($orgUID, $limit, $offset, $filter = []) {
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
            log_message('error', 'Users_model::getDesignationListPaginated — ' . $e->getMessage());
            $r = new stdClass(); $r->rows = []; $r->totalCount = 0; return $r;
        }
    }

    // ── Holiday paginated list ────────────────────────────────────────────────
    public function getHolidayListPaginated($orgUID, $limit, $offset, $filter = []) {
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
            log_message('error', 'Users_model::getHolidayListPaginated — ' . $e->getMessage());
            $r = new stdClass(); $r->rows = []; $r->totalCount = 0; return $r;
        }
    }

    // ── Departments ───────────────────────────────────────────────────────────
    public function getDepartmentList($orgUID) {
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
            log_message('error', 'Users_model::getDepartmentList — ' . $e->getMessage());
            return [];
        }
    }

    // ── Designations ──────────────────────────────────────────────────────────
    public function getDesignationList($orgUID) {
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
            log_message('error', 'Users_model::getDesignationList — ' . $e->getMessage());
            return [];
        }
    }

    // ── Password helpers ──────────────────────────────────────────────────────
    public function getUserByPasswordToken($token) {
        try {
            $this->WriteDb->db_debug = FALSE;
            $this->WriteDb->select('UserUID, FirstName, EmailAddress, IsPasswordSet');
            $this->WriteDb->from('Users.UserTbl');
            $this->WriteDb->where('PasswordSetToken', $token);
            $this->WriteDb->where('IsDeleted', 0);
            $this->WriteDb->limit(1);
            return $this->WriteDb->get()->row();
        } catch (Throwable $e) {
            log_message('error', 'Users_model::getUserByPasswordToken — ' . $e->getMessage());
            return null;
        }
    }

    public function updateUserPassword($userUID, $password) {
        $this->WriteDb->db_debug = FALSE;
        $this->WriteDb->where('UserUID', (int)$userUID);
        $this->WriteDb->update('Users.UserTbl', [
            'Password'      => base64_encode($password),
            'IsPasswordSet' => 1,
            'UpdatedOn'     => date('Y-m-d H:i:s'),
        ]);
    }

    // ── User attachments ──────────────────────────────────────────────────────
    public function getUserAttachments($userUID, $orgUID) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('AttachUID, FileName, FilePath, FileType, FileSize, DocType, CreatedOn');
            $this->ReadDb->from('Users.UserAttachmentTbl');
            $this->ReadDb->where(['UserUID' => (int)$userUID, 'OrgUID' => (int)$orgUID, 'IsDeleted' => 0]);
            $this->ReadDb->order_by('AttachUID', 'ASC');
            $q = $this->ReadDb->get();
            return $q ? $q->result() : [];
        } catch (Exception $e) {
            log_message('error', 'Users_model::getUserAttachments — ' . $e->getMessage());
            return [];
        }
    }

    // ── Emergency contacts ────────────────────────────────────────────────────
    public function getEmergencyContacts($userUID) {
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
            log_message('error', 'Users_model::getEmergencyContacts — ' . $e->getMessage());
            return [];
        }
    }

    // ── Education list ────────────────────────────────────────────────────────
    public function getEducationList($userUID) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('EduUID, Institution, Degree, FieldOfStudy, CGPA, DateOfCompletion');
            $this->ReadDb->from('Users.UserEducationTbl');
            $this->ReadDb->where(['UserUID' => (int)$userUID, 'IsDeleted' => 0]);
            $this->ReadDb->order_by('EduUID', 'ASC');
            $q = $this->ReadDb->get();
            return $q ? $q->result() : [];
        } catch (Exception $e) {
            log_message('error', 'Users_model::getEducationList — ' . $e->getMessage());
            return [];
        }
    }

    // ── Experience list ───────────────────────────────────────────────────────
    public function getExperienceList($userUID) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('ExpUID, EmployerName, JobTitle, StartDate, EndDate, JobDescription');
            $this->ReadDb->from('Users.UserExperienceTbl');
            $this->ReadDb->where(['UserUID' => (int)$userUID, 'IsDeleted' => 0]);
            $this->ReadDb->order_by('StartDate', 'DESC');
            $q = $this->ReadDb->get();
            return $q ? $q->result() : [];
        } catch (Exception $e) {
            log_message('error', 'Users_model::getExperienceList — ' . $e->getMessage());
            return [];
        }
    }

    // ── Bank details ──────────────────────────────────────────────────────────
    public function getBankDetails($userUID) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('BankDetailUID, BankName, BranchName, IFSCCode, AccountNumber, AccountType, AccountHolder, UpiId, UpiNumber');
            $this->ReadDb->from('Users.UserBankDetailsTbl');
            $this->ReadDb->where(['UserUID' => (int)$userUID, 'IsDeleted' => 0]);
            $this->ReadDb->limit(1);
            $q = $this->ReadDb->get();
            return ($q && $q->num_rows() > 0) ? $q->row() : null;
        } catch (Exception $e) {
            log_message('error', 'Users_model::getBankDetails — ' . $e->getMessage());
            return null;
        }
    }

    // ── Private filter helper ─────────────────────────────────────────────────
    private function _applyFilters($filter) {
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
}
