<?php defined('BASEPATH') OR exit('No direct script access allowed');

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

use Ramsey\Uuid\Uuid;

class Login_model extends CI_Model {
    
    private $EndReturnData;
    private $ReadDb;

	function __construct() {
        parent::__construct();

		$this->ReadDb = $this->load->database('ReadDB', TRUE);

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
            $JwtUserData['UserImage'] = $UserData->UserImage;
            $JwtUserData['OrgUID'] = $UserData->UserOrgUID;
            $JwtUserData['BranchUID'] = $UserData->UserOrgUID;
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
        $Expiry = getenv('LOGIN_EXPIRE_SECS');
        try {

            $RedisKey = Uuid::uuid4() . '-' . $UserData->UserUID;

            // Unset all the unwanted information
            unset($jwtPayload->JWTData['UserMainModule']);
            unset($jwtPayload->JWTData['UserSubModule']);

            unset($jwtPayload->JWTData['ModuleInfo']);
            
            unset($jwtPayload->JWTData['GenSettings']);

            $this->cacheservice->set($RedisKey, $jwtPayload->JWTData, $Expiry);

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

            $this->ReadDb->select('UserMM.UserMainMenuUID as UserMainMenuUID, UserMM.MainMenuUID as MainMenuUID, MainMenu.Name as MainMenuName, MainMenu.Icons as MainMenuIcons, UserMM.Sorting as Sorting');
            $this->ReadDb->from('UserRole.UserMainMenusTbl as UserMM');
            $this->ReadDb->join('Modules.MainMenusTbl as MainMenu', 'MainMenu.MainMenuUID = UserMM.MainMenuUID', 'left');
            $this->ReadDb->where('UserMM.UserUID', $UserUID);
            $this->ReadDb->where('UserMM.IsActive', 1);
            $this->ReadDb->where('UserMM.IsDeleted', 0);
            $this->ReadDb->group_by('UserMM.UserMainMenuUID');
            $this->ReadDb->order_by('UserMM.Sorting', 'ASC');
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

    public function getUserRightsSubModule($UserUID) {

        $this->EndReturnData = new stdClass();
        try {

            $this->ReadDb->select('UserSM.UserSubMenuUID as UserSubMenuUID, SubMenu.MainMenuUID as MainMenuUID, UserSM.SubMenuUID as SubMenuUID, SubMenu.Name as SubMenuName, SubMenu.ControllerName as ControllerName, SubMenu.ParentSubMenuUID as ParentSubMenuUID, SubMenu.Icons as Icons, UserSM.Sorting as Sorting');
            $this->ReadDb->from('UserRole.UserSubMenusTbl as UserSM');
            $this->ReadDb->join('Modules.SubMenusTbl as SubMenu', 'SubMenu.SubMenuUID = UserSM.SubMenuUID', 'left');
            $this->ReadDb->where('UserSM.UserUID', $UserUID);
            $this->ReadDb->where('UserSM.IsActive', 1);
            $this->ReadDb->where('UserSM.IsDeleted', 0);
            $this->ReadDb->group_by('UserSM.UserSubMenuUID');
            $this->ReadDb->order_by('UserSM.Sorting', 'ASC');
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

    public function getOrgGeneralSettings($OrgUID) {

        $this->EndReturnData = new stdClass();
        try {

            $this->ReadDb->select('GeneralSettg.DecimalPoints as DecimalPoints, GeneralSettg.CurrenySymbol as CurrenySymbol, GeneralSettg.DiscountType as DiscountType, GeneralSettg.ProductType as ProductType, GeneralSettg.ProductTax as ProductTax, GeneralSettg.TaxDetail as TaxDetail, GeneralSettg.PriceMaxLength as PriceMaxLength, GeneralSettg.RowLimit as RowLimit, GeneralSettg.EnableStorage as EnableStorage, GeneralSettg.MandatoryStorage as MandatoryStorage, GeneralSettg.SerialNoDisplay as SerialNoDisplay, GeneralSettg.QtyMaxLength as QtyMaxLength');
            $this->ReadDb->from('Organisation.OrgSettingsTbl as GeneralSettg');
            $this->ReadDb->where('GeneralSettg.OrgUID', $OrgUID);
            $this->ReadDb->limit(1);
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

    public function getModuleDetails($OrgUID) {

        $this->EndReturnData = new stdClass();
        try {

            $this->ReadDb->select('Module.ModuleUID as ModuleUID, Module.Name as Name, Module.OrgUID as OrgUID, Module.MainMenuUID as MainMenuUID, Module.SubMenuUID as SubMenuUID, Module.ControllerName as ControllerName, Module.DatabaseName as DatabaseName, Module.MasterTableName as MasterTableName, Module.ParentModuleUID as ParentModuleUID, Module.IsMainModule as IsMainModule, Module.IsModuleEnabled as IsModuleEnabled, Module.EditOnPage as EditOnPage');
            $this->ReadDb->from('Modules.ModuleTbl as Module');
            $this->ReadDb->where('Module.OrgUID', $OrgUID);
            $this->ReadDb->where('Module.IsDeleted', 0);
            $this->ReadDb->where('Module.IsActive', 1);
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

    public function getFailedAttempts($userName, $hours = 1) {

        try {

            $this->ReadDb->db_debug = FALSE;
            
            $this->ReadDb->from('Security.UserLoginAudit as ula');
            $this->ReadDb->where('ula.AttemptedUsername', $userName);
            $this->ReadDb->where('ula.LoginStatus', 'FAILED');
            $this->ReadDb->where('ula.LoginTime >=', date('Y-m-d H:i:s', strtotime("-{$hours} hours")));
            $query = $this->ReadDb->get();
            if (!$query) {
                $error = $this->ReadDb->error();
                throw new Exception($error['message'] ?? 'Database error occurred');
            }

            return $query->num_rows();

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }

    public function getUserAuditInfo($auditId) {

        try {

            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'ula.AuditID',
                'ula.LoginTime',
            ]);
            $this->ReadDb->from('Security.UserLoginAudit as ula');
            $this->ReadDb->where('ula.AuditID', $auditId);
            $this->ReadDb->limit(1);
            $query = $this->ReadDb->get();
            if (!$query) {
                $error = $this->ReadDb->error();
                throw new Exception($error['message'] ?? 'Database error occurred');
            }

            return $query->result();

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }

}