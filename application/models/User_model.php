<?php defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model {

    private $EndReturnData;
    private $ReadDb;

	function __construct() {
        parent::__construct();

		$this->ReadDb = $this->load->database('ReadDB', TRUE);

    }

    public function getUserByEmailOrUsername(string $identifier): object {

        $this->EndReturnData = new stdClass();
        try {

            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'User.UserUID as UserUID',
                'User.FirstName as UserFirstName',
                'User.LastName as UserLastName',
                'User.UserName as UserName',
                'User.EmailAddress as UserEmailAddress',
                'User.Password as UserPassword',
                'User.IsLocked as IsLocked',
                'Roles.RoleUID as UserRoleUID',
                'Roles.Name as UserRoleName',
                'Org.OrgUID as UserOrgUID',
                'User.BranchUID as BranchUID',
                'Branch.Name as BranchName',
                'Branch.BranchCode as BranchCode',
                'Org.Logo as UserOrgLogo',
                'Org.CountryCode as UserOrgCCode',
                'Org.CountryISO2 as UserOrgCISO2',
                'Org.Name as UserOrgName',
                'Org.BrandName as UserOrgBrandName',
                'Org.MobileNumber as UserOrgMobile',
                'Timezone.Timezone',
                'User.CountryCode as UserCountryCode',
                'User.CountryISO2 as UserCountryISO2',
                'User.MobileNumber as UserMobileNumber',
                'User.Image as UserImage',
                'Org.ShortCode as OrgShortCode',
                'Org.OrgToken as OrgToken',
                'Org.StateCode as OrgStateCode',
                'Org.StateName as OrgStateName',
                'User.UILanguage as UILanguage',
                'User.LastLoginOn as LastLoginOn',
                'User.LastLoginDevice as LastLoginDevice'
            ]);
            $this->ReadDb->from('Users.UserTbl as User');
            $this->ReadDb->join('UserRole.RolesTbl as Roles', 'Roles.RoleUID = User.RoleUID', 'left');
            $this->ReadDb->join('Organisation.OrganisationTbl as Org', 'Org.OrgUID = User.OrgUID', 'left');
            $this->ReadDb->join('Organisation.BranchesTbl as Branch', 'Branch.BranchUID = User.BranchUID', 'left');
            $this->ReadDb->join('Global.TimezoneTbl as Timezone', 'Timezone.TimezoneUID = Org.TimezoneUID', 'left');
            $this->ReadDb->group_start();
            $this->ReadDb->where('User.UserName', $identifier);
            $this->ReadDb->or_where('User.EmailAddress', $identifier);
            $this->ReadDb->group_end();
            $this->ReadDb->where('User.IsActive',       1);
            $this->ReadDb->where('User.IsDeleted',      0);
            $this->ReadDb->where('User.HasLoginAccess', 1);
            $query = $this->ReadDb->get();
            if (!$query) {
                $error = $this->ReadDb->error();
                throw new Exception($error['message']);
            }

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Success';
            $this->EndReturnData->Data = $query->result();

            return $this->EndReturnData;

        } catch(Exception $e) {
            notifyError($e, 'User_model::getUserByEmailOrUsername');
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);

        }

    }

    /**
     * Same JOIN as getUserByEmailOrUsername() but filters by primary key (UserUID).
     * Used in Step 2 of the two-step login when the UID is already known from Step 1.
     * @param int $uid
     * @return object {Error, Message, Data[]}
     */
    public function getUserByUID(int $uid): object {
        $this->EndReturnData = new stdClass();
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'User.UserUID as UserUID',
                'User.FirstName as UserFirstName',
                'User.LastName as UserLastName',
                'User.UserName as UserName',
                'User.EmailAddress as UserEmailAddress',
                'User.Password as UserPassword',
                'User.IsLocked as IsLocked',
                'Roles.RoleUID as UserRoleUID',
                'Roles.Name as UserRoleName',
                'Org.OrgUID as UserOrgUID',
                'User.BranchUID as BranchUID',
                'Branch.Name as BranchName',
                'Branch.BranchCode as BranchCode',
                'Org.Logo as UserOrgLogo',
                'Org.CountryCode as UserOrgCCode',
                'Org.CountryISO2 as UserOrgCISO2',
                'Org.Name as UserOrgName',
                'Org.BrandName as UserOrgBrandName',
                'Org.MobileNumber as UserOrgMobile',
                'Timezone.Timezone',
                'User.CountryCode as UserCountryCode',
                'User.CountryISO2 as UserCountryISO2',
                'User.MobileNumber as UserMobileNumber',
                'User.Image as UserImage',
                'Org.ShortCode as OrgShortCode',
                'Org.OrgToken as OrgToken',
                'Org.StateCode as OrgStateCode',
                'Org.StateName as OrgStateName',
                'User.UILanguage as UILanguage',
                'User.LastLoginOn as LastLoginOn',
                'User.LastLoginDevice as LastLoginDevice',
            ]);
            $this->ReadDb->from('Users.UserTbl as User');
            $this->ReadDb->join('UserRole.RolesTbl as Roles',          'Roles.RoleUID = User.RoleUID',          'left');
            $this->ReadDb->join('Organisation.OrganisationTbl as Org', 'Org.OrgUID = User.OrgUID',              'left');
            $this->ReadDb->join('Organisation.BranchesTbl as Branch',  'Branch.BranchUID = User.BranchUID',     'left');
            $this->ReadDb->join('Global.TimezoneTbl as Timezone',       'Timezone.TimezoneUID = Org.TimezoneUID', 'left');
            $this->ReadDb->where('User.UserUID',       $uid);
            $this->ReadDb->where('User.IsActive',       1);
            $this->ReadDb->where('User.IsDeleted',      0);
            $this->ReadDb->where('User.HasLoginAccess', 1);
            $this->ReadDb->limit(1);
            $query = $this->ReadDb->get();
            if (!$query) {
                $error = $this->ReadDb->error();
                throw new Exception($error['message']);
            }
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Success';
            $this->EndReturnData->Data    = $query->result();
            return $this->EndReturnData;
        } catch (Exception $e) {
            notifyError($e, 'User_model::getUserByUID');
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);
        }
    }

    /**
     * Lightweight Step-1 check: fetches only the fields needed to validate
     * a username before the password screen is shown. Much faster than
     * getUserByEmailOrUsername() which joins Roles, Org, and Branch tables.
     * @param string $identifier username or email
     * @return object|null null when not found; object with UserUID, UserName, FirstName, LastName, IsLocked
     */
    public function getUserForStepOne(string $identifier): ?object {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('UserUID, UserName, FirstName, LastName, IsLocked, Image');
            $this->ReadDb->from('Users.UserTbl');
            $this->ReadDb->group_start();
            $this->ReadDb->where('UserName', $identifier);
            $this->ReadDb->or_where('EmailAddress', $identifier);
            $this->ReadDb->group_end();
            $this->ReadDb->where('IsActive',       1);
            $this->ReadDb->where('IsDeleted',      0);
            $this->ReadDb->where('HasLoginAccess', 1);
            $this->ReadDb->limit(1);
            $row = $this->ReadDb->get()->row();
            return $row ?: null;
        } catch (Exception $e) {
            notifyError('User_model::getUserForStepOne', $e);
            return null;
        }
    }

    public function getCurrentSessionToken(int $userUID): ?string {

        try {

            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('User.CurrentSessionToken');
            $this->ReadDb->from('Users.UserTbl as User');
            $this->ReadDb->where('User.UserUID', (int) $userUID);
            $this->ReadDb->where('User.IsActive', 1);
            $this->ReadDb->where('User.IsDeleted', 0);
            $this->ReadDb->limit(1);
            $query = $this->ReadDb->get();
            if (!$query) return null;
            $row = $query->row();
            return $row ? $row->CurrentSessionToken : null;

        } catch (Exception $e) {
            notifyError($e, 'User_model::getCurrentSessionToken');
            return null;
        }

    }

    public function getUserByUserInfo(array $FilterArray = []): object {

        $this->EndReturnData = new stdClass();
        try {

            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'User.UserUID as UserUID',
                'User.FirstName as UserFirstName',
                'User.LastName as UserLastName',
                'User.UserName as UserName',
                'User.EmailAddress as UserEmailAddress',
                'User.Password as UserPassword',
                'User.IsLocked as IsLocked',
                'Roles.RoleUID as UserRoleUID',
                'Roles.Name as UserRoleName',
                'Org.OrgUID as UserOrgUID',
                'User.BranchUID as BranchUID',
                'Org.Logo as UserOrgLogo',
                'Org.CountryCode as UserOrgCCode',
                'Org.CountryISO2 as UserOrgCISO2',
                'Org.Name as UserOrgName',
                'Org.BrandName as UserOrgBrandName',
                'Org.MobileNumber as UserOrgMobile',
                'Timezone.Timezone',
                'User.CountryCode as UserCountryCode',
                'User.CountryISO2 as UserCountryISO2',
                'User.MobileNumber as UserMobileNumber',
                'User.Image as UserImage',
                'Org.ShortCode as OrgShortCode',
                'Org.OrgToken as OrgToken',
                'Org.StateCode as OrgStateCode',
                'Org.StateName as OrgStateName'
            ]);
            $this->ReadDb->from('Users.UserTbl as User');
            $this->ReadDb->join('UserRole.RolesTbl as Roles', 'Roles.RoleUID = User.RoleUID', 'left');
            $this->ReadDb->join('Organisation.OrganisationTbl as Org', 'Org.OrgUID = User.OrgUID', 'left');
            $this->ReadDb->join('Global.TimezoneTbl as Timezone', 'Timezone.TimezoneUID = Org.TimezoneUID', 'left');
            if(!empty($FilterArray)) {
                $this->ReadDb->where($FilterArray);
            }
            $this->ReadDb->where('User.IsActive', 1);
            $this->ReadDb->where('User.IsDeleted', 0);
            $query = $this->ReadDb->get();
            if (!$query) {
                $error = $this->ReadDb->error();
                throw new Exception($error['message']);
            }

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Success';
            $this->EndReturnData->Data = $query->result();

            return $this->EndReturnData;

        } catch(Exception $e) {
            notifyError($e, 'User_model::getUserByUserInfo');
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);

        }

    }

}
