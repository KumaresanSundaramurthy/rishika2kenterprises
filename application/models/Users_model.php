<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Users_model extends CI_Model {

    private $ReadDb;

    public function __construct() {
        parent::__construct();
        $this->ReadDb = $this->load->database('ReadDB', TRUE);
    }

    // ── Login-users cache (HasLoginAccess=1 only) ─────────────────────────────
    public function getOrgUsersForCache(int $orgUID): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select("UserUID, FirstName, LastName, CONCAT(FirstName, ' ', LastName) AS FullName");
            $this->ReadDb->from('Users.UserTbl');
            $this->ReadDb->where(['OrgUID' => (int) $orgUID, 'IsDeleted' => 0, 'IsActive' => 1, 'HasLoginAccess' => 1]);
            $this->ReadDb->order_by('FirstName', 'ASC');
            $query = $this->ReadDb->get();
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            notifyError('Users_model::getOrgUsersForCache', $e);
            return [];
        }
    }

    // ── All staff dropdown (includes non-login employees) ────────────────────
    public function getEmployeeDropdownList(int $orgUID): array {
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
            notifyError('Users_model::getEmployeeDropdownList', $e);
            return [];
        }
    }

    // ── Department paginated list ─────────────────────────────────────────────
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

    // ── Designation paginated list ────────────────────────────────────────────
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

    // ── Holiday paginated list ────────────────────────────────────────────────
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

    // ── Departments ───────────────────────────────────────────────────────────
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

    // ── Designations ──────────────────────────────────────────────────────────
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

    // ── Password helpers ──────────────────────────────────────────────────────
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
        ], ['UserUID' => (int)$userUID]);
    }

    // ── User attachments ──────────────────────────────────────────────────────
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

    // ── Emergency contacts ────────────────────────────────────────────────────
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

    // ── Education list ────────────────────────────────────────────────────────
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

    // ── Experience list ───────────────────────────────────────────────────────
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

    // ── Bank details ──────────────────────────────────────────────────────────
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

    // ── Expenses & Reimbursements ─────────────────────────────────────────────
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

    // ── Branch access assignments for a user ──────────────────────────────────
    /**
     * Returns the last N bcrypt hashes from PasswordHistoryTbl for reuse checking.
     * @param int $userUID
     * @param int $limit
     * @returns array
     */
    public function getRecentPasswordHashes(int $userUID, int $limit = 3): array {
        try {
            $this->ReadDb->select('Password');
            $this->ReadDb->from('Users.PasswordHistoryTbl');
            $this->ReadDb->where('UserUID', $userUID);
            $this->ReadDb->order_by('CreatedOn', 'DESC');
            $this->ReadDb->limit($limit);
            $query = $this->ReadDb->get();
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            notifyError('Users_model::getRecentPasswordHashes', $e);
            return [];
        }
    }

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