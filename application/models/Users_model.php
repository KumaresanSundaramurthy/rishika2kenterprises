<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Users_model extends CI_Model {

    private $ReadDb;
    private $WriteDb;

    public function __construct() {
        parent::__construct();
        $this->ReadDb  = $this->load->database('ReadDB',  TRUE);
        $this->WriteDb = $this->load->database('WriteDB', TRUE);
    }

    // ── Org users list for cache (user filter across all pages) ──────────────
    public function getOrgUsersForCache($orgUID) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select("UserUID, FirstName, LastName, CONCAT(FirstName, ' ', LastName) AS FullName");
            $this->ReadDb->from('Users.UserTbl');
            $this->ReadDb->where(['OrgUID' => (int)$orgUID, 'IsDeleted' => 0, 'IsActive' => 1]);
            $this->ReadDb->order_by('FirstName', 'ASC');
            $query = $this->ReadDb->get();
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            log_message('error', 'Users_model::getOrgUsersForCache — ' . $e->getMessage());
            return [];
        }
    }

    // ── Paginated list ────────────────────────────────────────────────────────
    public function getUsersList($orgUID, $filter, $limit, $offset) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select(
                'u.UserUID, u.UserCode, u.FirstName, u.LastName, u.UserName,
                 u.EmailAddress, u.MobileNumber,
                 u.RoleUID, r.Name AS RoleName,
                 u.IsActive, u.IsLocked, u.LastLoginOn,
                 u.UpdatedOn,
                 CONCAT(IFNULL(ub.FirstName,\'\'), \' \', IFNULL(ub.LastName,\'\')) AS UpdatedBy'
            );
            $this->ReadDb->from('Users.UserTbl u');
            $this->ReadDb->join('UserRole.RolesTbl r', 'r.RoleUID = u.RoleUID AND r.IsDeleted = 0', 'left');
            $this->ReadDb->join('Users.UserTbl ub',    'ub.UserUID = u.UpdatedBy',                  'left');
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

    // ── Single record + addresses ─────────────────────────────────────────────
    public function getUserById($userUID, $orgUID) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select(
                'u.UserUID, u.UserCode, u.FirstName, u.LastName, u.UserName,
                 u.EmailAddress, u.MobileNumber, u.CountryCode, u.CountryISO2,
                 u.RoleUID, u.IsActive, u.IsLocked, u.LastLoginOn'
            );
            $this->ReadDb->from('Users.UserTbl u');
            $this->ReadDb->where('u.UserUID',   $userUID);
            $this->ReadDb->where('u.OrgUID',    $orgUID);
            $this->ReadDb->where('u.IsDeleted', 0);
            $this->ReadDb->limit(1);
            $query = $this->ReadDb->get();
            $user  = $query ? $query->row() : null;
            if (!$user) return null;

            // Attach addresses
            $user->Addresses = $this->getUserAddresses($userUID);
            return $user;
        } catch (Exception $e) {
            log_message('error', 'Users_model::getUserById — ' . $e->getMessage());
            return null;
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
            // Key by type for easy JS lookup
            $out = ['Current' => null, 'Permanent' => null];
            foreach ($rows as $r) {
                $out[$r->AddressType] = $r;
            }
            return $out;
        } catch (Exception $e) {
            log_message('error', 'Users_model::getUserAddresses — ' . $e->getMessage());
            return ['Current' => null, 'Temporary' => null];
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

    // ── Private filter helper ─────────────────────────────────────────────────
    private function _applyFilters($filter) {
        if (!empty($filter['Status']) && $filter['Status'] !== 'All') {
            $this->ReadDb->where('u.IsActive', $filter['Status'] === 'Active' ? 1 : 0);
        }
        if (!empty($filter['Name'])) {
            $term = $this->ReadDb->escape_like_str($filter['Name']);
            $this->ReadDb->group_start();
            $this->ReadDb->like('u.FirstName',       $term, 'both');
            $this->ReadDb->or_like('u.LastName',     $term, 'both');
            $this->ReadDb->or_like('u.UserName',     $term, 'both');
            $this->ReadDb->or_like('u.EmailAddress', $term, 'both');
            $this->ReadDb->group_end();
        }
    }
}
