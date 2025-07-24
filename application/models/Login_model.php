<?php defined('BASEPATH') OR exit('No direct script access allowed');

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

use Ramsey\Uuid\Uuid;

class Login_model extends CI_Model {
    
    private $EndReturnData;
    private $UserRoleDb;
    private $OrgDb;
    private $ModuleDb;

	function __construct() {
        parent::__construct();

		$this->UserRoleDb = $this->load->database('UserRole', TRUE);
		$this->OrgDb = $this->load->database('Organisation', TRUE);
        $this->ModuleDb = $this->load->database('Modules', TRUE);

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
            $JwtUserData['OrgUID'] = $UserData->UserOrgUID;
            $JwtUserData['OrgCCode'] = $UserData->UserOrgCCode;
            $JwtUserData['OrgCISO2'] = $UserData->UserOrgCISO2;
            $JwtUserData['OrgLogo'] = $UserData->UserOrgLogo;
            $JwtUserData['RoleUID'] = $UserData->UserRoleUID;
            $JwtUserData['RoleName'] = $UserData->UserRoleName;
            $JwtUserData['Timezone'] = $UserData->Timezone;

            $MainModule = $this->getUserRightsMainModule($UserData->UserUID)->Data;
            $SubModule = $this->getUserRightsSubModule($UserData->UserUID)->Data;

            // Organisation Settings
            $GeneralSettings = $this->getOrgGeneralSettings($UserData->UserOrgUID)->Data[0];
            $ModuleInfo = $this->getModuleDetails($UserData->UserOrgUID)->Data;

            $jwtPayload = array('User' => $JwtUserData, 'UserMainModule' => $MainModule, 'UserSubModule' => $SubModule, 'GenSettings' => $GeneralSettings, 'ModuleInfo' => $ModuleInfo);

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

            $jwtEncoded = JWT::encode($jwtData, getenv('JWT_KEY'), 'HS256');

            set_cookie(getenv('JWT_COOKIE_NAME'), $jwtEncoded, $Expiry);

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

            $this->UserRoleDb->select('UserMM.UserMainMenuUID as UserMainMenuUID, UserMM.MainMenuUID as MainMenuUID, MainMenu.Name as MainMenuName, MainMenu.Icons as MainMenuIcons, UserMM.Sorting as Sorting');
            $this->UserRoleDb->from('UserRole.UserMainMenusTbl as UserMM');
            $this->UserRoleDb->join($this->ModuleDb->database.'.MainMenusTbl as MainMenu', 'MainMenu.MainMenuUID = UserMM.MainMenuUID', 'left');
            $this->UserRoleDb->where('UserMM.UserUID', $UserUID);
            $this->UserRoleDb->where('UserMM.IsActive', 1);
            $this->UserRoleDb->where('UserMM.IsDeleted', 0);
            $this->UserRoleDb->group_by('UserMM.UserMainMenuUID');
            $this->UserRoleDb->order_by('UserMM.Sorting', 'ASC');
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

            $this->UserRoleDb->select('UserSM.UserSubMenuUID as UserSubMenuUID, SubMenu.MainMenuUID as MainMenuUID, UserSM.SubMenuUID as SubMenuUID, SubMenu.Name as SubMenuName, SubMenu.ControllerName as ControllerName, SubMenu.ParentSubMenuUID as ParentSubMenuUID, UserSM.Sorting as Sorting');
            $this->UserRoleDb->from('UserRole.UserSubMenusTbl as UserSM');
            $this->UserRoleDb->join($this->ModuleDb->database.'.SubMenusTbl as SubMenu', 'SubMenu.SubMenuUID = UserSM.SubMenuUID', 'left');
            $this->UserRoleDb->where('UserSM.UserUID', $UserUID);
            $this->UserRoleDb->where('UserSM.IsActive', 1);
            $this->UserRoleDb->where('UserSM.IsDeleted', 0);
            $this->UserRoleDb->group_by('UserSM.UserSubMenuUID');
            $this->UserRoleDb->order_by('UserSM.Sorting', 'ASC');
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

    public function getOrgGeneralSettings($OrgUID) {

        $this->EndReturnData = new stdClass();
        try {

            $this->OrgDb->select('GeneralSettg.DecimalPoints as DecimalPoints, GeneralSettg.CurrenySymbol as CurrenySymbol, GeneralSettg.DiscountType as DiscountType, GeneralSettg.ProductType as ProductType, GeneralSettg.ProductTax as ProductTax, GeneralSettg.TaxDetail as TaxDetail, GeneralSettg.PriceMaxLength as PriceMaxLength, GeneralSettg.RowLimit as RowLimit, GeneralSettg.EnableStorage as EnableStorage, GeneralSettg.MandatoryStorage as MandatoryStorage');
            $this->OrgDb->from('Organisation.OrgSettingsTbl as GeneralSettg');
            $this->OrgDb->where('GeneralSettg.OrgUID', $OrgUID);
            $this->OrgDb->limit(1);
            $query = $this->OrgDb->get();

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

    public function getModuleDetails($OrgUID) {

        $this->EndReturnData = new stdClass();
        try {

            $this->ModuleDb->select('Module.ModuleUID as ModuleUID, Module.Name as Name, Module.OrgUID as OrgUID, Module.MainMenuUID as MainMenuUID, Module.SubMenuUID as SubMenuUID, Module.ControllerName as ControllerName, Module.DatabaseName as DatabaseName, Module.MasterTableName as MasterTableName, Module.ParentModuleUID as ParentModuleUID, Module.IsMainModule as IsMainModule, Module.IsModuleEnabled as IsModuleEnabled');
            $this->ModuleDb->from('Modules.ModuleTbl as Module');
            $this->ModuleDb->where('Module.OrgUID', $OrgUID);
            $this->ModuleDb->where('Module.IsDeleted', 0);
            $this->ModuleDb->where('Module.IsActive', 1);
            $query = $this->ModuleDb->get();

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