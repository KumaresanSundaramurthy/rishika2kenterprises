<?php defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model {
    
    private $EndReturnData;
    private $UserDb;

	function __construct() {
        parent::__construct();

		$this->UserDb = $this->load->database('Users', TRUE);

    }

    public function getUserByUserInfo($FilterArray) {

        $this->EndReturnData = new stdClass();
        try {

            $this->UserDb->select('User.UserUID as UserUID, User.FirstName as UserFirstName, User.LastName as UserLastName, User.UserName as UserName, User.EmailAddress as UserEmailAddress, User.Password as UserPassword');
            $this->UserDb->from('Users.UserTbl as User');
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