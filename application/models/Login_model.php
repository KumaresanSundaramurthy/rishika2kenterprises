<?php defined('BASEPATH') OR exit('No direct script access allowed');

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

use Ramsey\Uuid\Uuid;

class Login_model extends CI_Model {
    
    private $EndReturnData;
    private $UserRoleDb;

	function __construct() {
        parent::__construct();

		$this->UserRoleDb = $this->load->database('UserRole', TRUE);

    }

    public function formatJWTPayload($UserData) {

        $this->EndReturnData = new stdClass();
        try {

            $JwtUserData = [];
            $JwtUserData['UserUID'] = $UserData->UserUID;
            $JwtUserData['FirstName'] = $UserData->UserFirstName;
            $JwtUserData['LastName'] = $UserData->UserLastName;
            $JwtUserData['UserName'] = $UserData->UserName;
            $JwtUserData['EmailAddress'] = $UserData->UserEmailAddress;

            $MainModule = $this->getUserRightsMainModule($UserData->UserUID)->Data;
            $SubModule = $this->getUserRightsSubModule($UserData->UserUID)->Data;

            $jwtPayload = array('User' => $JwtUserData, 'UserMainModule' => $MainModule, 'UserSubModule' => $SubModule);

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Success';
            $this->EndReturnData->Data = $UserData;
            $this->EndReturnData->JWTData = $jwtPayload;

        } catch(Exception $e) {

            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->UserData = $UserData;
            $this->EndReturnData->Message = $e->getMessage();

        }

        return $this->EndReturnData;
    }

    public function setJwtToken($UserData, $jwtPayload) {

        $this->EndReturnData = new stdClass();
        $Expiry = 43200; //12 hours expiry
        try {

            $RedisKey = Uuid::uuid4() . '-' . $UserData->UserUID;

            $this->cacheservice->set($RedisKey, json_encode($jwtPayload->JWTData), $Expiry);

            $jwtData['key'] = $RedisKey;
            $jwtData['iss'] = getenv('HTTP_HOST');
            $jwtData['iat'] = time();
            $jwtData['nbf'] = time();
            $jwtData['exp'] = time() + $Expiry; 

            // $jwtEncoded = JWT::encode($jwtData, getenv('JWT_KEY'), 'HS256');
            $jwtEncoded = JWT::encode($jwtData, getAWSConfigurationDetails()->JWT_KEY, 'HS256');

            // set_cookie(getenv('JWT_COOKIE_NAME'), $jwtEncoded, $Expiry);
            set_cookie(getAWSConfigurationDetails()->JWT_COOKIE_NAME, $jwtEncoded, $Expiry);

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Success';

        } catch(Exception $e) {

            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();

        }

        return $this->EndReturnData;
        
    }

    public function getUserRightsMainModule($UserUID) {

        $this->EndReturnData = new stdClass();
        try {

            $this->UserRoleDb->select('UserMM.UserMainMenuUID as UserMainMenuUID, UserMM.MainMenuUID as MainMenuUID, MainMenu.Name as MainMenuName');
            $this->UserRoleDb->from('UserRole.UserMainMenusTbl as UserMM');
            $this->UserRoleDb->join('UserRole.MainMenusTbl as MainMenu', 'MainMenu.MainMenuUID = UserMM.MainMenuUID', 'left');
            $this->UserRoleDb->where('UserMM.UserUID', $UserUID);
            $this->UserRoleDb->where('UserMM.IsActive', 1);
            $this->UserRoleDb->where('UserMM.IsDeleted', 0);
            $query = $this->UserRoleDb->get();

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

    public function getUserRightsSubModule($UserUID) {

        $this->EndReturnData = new stdClass();
        try {

            $this->UserRoleDb->select('UserSM.UserSubMenuUID as UserSubMenuUID, SubMenu.MainMenuUID as MainMenuUID, UserSM.SubMenuUID as SubMenuUID, SubMenu.Name as SubMenuName, SubMenu.ControllerName as ControllerName');
            $this->UserRoleDb->from('UserRole.UserSubMenusTbl as UserSM');
            $this->UserRoleDb->join('UserRole.SubMenusTbl as SubMenu', 'SubMenu.SubMenuUID = UserSM.SubMenuUID', 'left');
            $this->UserRoleDb->where('UserSM.UserUID', $UserUID);
            $this->UserRoleDb->where('UserSM.IsActive', 1);
            $this->UserRoleDb->where('UserSM.IsDeleted', 0);
            $query = $this->UserRoleDb->get();

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