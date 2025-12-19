<?php defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model {
    
    private $EndReturnData;
    private $ReadDb;

	function __construct() {
        parent::__construct();

		$this->ReadDb = $this->load->database('ReadDB', TRUE);

    }

    public function getUserByUserInfo($FilterArray = []) {

        $this->EndReturnData = new stdClass();
        try {

            $this->ReadDb->select('User.UserUID as UserUID, User.FirstName as UserFirstName, User.LastName as UserLastName, User.UserName as UserName, User.EmailAddress as UserEmailAddress, User.Password as UserPassword, Roles.RoleUID as UserRoleUID, Roles.Name as UserRoleName, Org.OrgUID as UserOrgUID, Org.Logo as UserOrgLogo, Org.CountryCode as UserOrgCCode, Org.CountryISO2 as UserOrgCISO2, Timezone.Timezone, User.CountryCode as UserCountryCode, User.CountryISO2 as UserCountryISO2, User.MobileNumber as UserMobileNumber, User.Image as UserImage');
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