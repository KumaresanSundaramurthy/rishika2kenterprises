<?php defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model {
    
    private $EndReturnData;
    private $ReadDb;

	function __construct() {
        parent::__construct();

		$this->ReadDb = $this->load->database('ReadDB', TRUE);

    }

    public function getUserByEmailOrUsername($identifier) {

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

            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);

        }

    }

    public function getCurrentSessionToken($userUID) {

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
            return null;
        }

    }

    public function getUserByUserInfo($FilterArray = []) {

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

            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);

        }

    }

}