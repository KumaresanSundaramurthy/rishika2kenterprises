<?php defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model {
    
    private $EndReturnData;
    private $UserDb;
    private $UserRoleDb;
    private $OrgDb;
    private $GlobalDb;

	function __construct() {
        parent::__construct();

		$this->UserDb = $this->load->database('Users', TRUE);
        $this->UserRoleDb = $this->load->database('UserRole', TRUE);
        $this->OrgDb = $this->load->database('Organisation', TRUE);
        $this->GlobalDb = $this->load->database('Global', TRUE);

    }

    public function getUserByUserInfo($FilterArray) {

        $this->EndReturnData = new stdClass();
        try {

            $this->UserDb->select('User.UserUID as UserUID, User.FirstName as UserFirstName, User.LastName as UserLastName, User.UserName as UserName, User.EmailAddress as UserEmailAddress, User.Password as UserPassword, Roles.RoleUID as UserRoleUID, Roles.Name as UserRoleName, Org.OrgUID as UserOrgUID, Org.Logo as UserOrgLogo, Org.CountryCode as UserOrgCCode, Org.CountryISO2 as UserOrgCISO2, Timezone.Timezone');
            $this->UserDb->from('Users.UserTbl as User');
            $this->UserDb->join($this->UserRoleDb->database.'.RolesTbl as Roles', 'Roles.RoleUID = User.RoleUID', 'left');
            $this->UserDb->join($this->OrgDb->database.'.OrganisationTbl as Org', 'Org.OrgUID = User.OrgUID', 'left');
            $this->UserDb->join($this->GlobalDb->database.'.TimezoneTbl as Timezone', 'Timezone.TimezoneUID = Org.TimezoneUID', 'left');
            $this->UserDb->where($FilterArray);
            $this->UserDb->where('User.IsActive', 1);
            $this->UserDb->where('User.IsDeleted', 0);
            $query = $this->UserDb->get();

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