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

            $MainModule = $this->getRoleMainMenus($UserData->UserRoleUID)->Data;
            $SubModule  = $this->getRoleSubMenus($UserData->UserRoleUID)->Data;

            // Organisation Settings
            $GeneralSettings = $this->getOrgGeneralSettings($UserData->UserOrgUID)->Data[0];
            $ModuleInfo = $this->getModuleDetails($UserData->UserOrgUID)->Data;

            // Build flat permissions map: ControllerName => {CanView,CanCreate,CanEdit,CanDelete}
            $Permissions = [];
            foreach ($SubModule as $sm) {
                if (!empty($sm->ControllerName)) {
                    $Permissions[$sm->ControllerName] = [
                        'CanView'   => (int)$sm->CanView,
                        'CanCreate' => (int)$sm->CanCreate,
                        'CanEdit'   => (int)$sm->CanEdit,
                        'CanDelete' => (int)$sm->CanDelete,
                    ];
                }
            }

            $jwtPayload = array('User' => $JwtUserData, 'UserMainModule' => $MainModule, 'UserSubModule' => $SubModule, 'Permissions' => $Permissions, 'GenSettings' => $GeneralSettings, 'ModuleInfo' => $ModuleInfo);

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

    // ── Role-based menu queries ──────────────────────────────────
    public function getRoleMainMenus($RoleUID) {

        $this->EndReturnData = new stdClass();
        try {

            $this->ReadDb->select('RMM.RoleMainMenuUID, RMM.MainMenuUID, MainMenu.Name as MainMenuName, MainMenu.Icons as MainMenuIcons, RMM.Sorting, RMM.CanView, RMM.CanCreate, RMM.CanEdit, RMM.CanDelete');
            $this->ReadDb->from('UserRole.RoleMainMenusTbl as RMM');
            $this->ReadDb->join('Modules.MainMenusTbl as MainMenu', 'MainMenu.MainMenuUID = RMM.MainMenuUID', 'left');
            $this->ReadDb->where('RMM.RoleUID', $RoleUID);
            $this->ReadDb->where('RMM.IsActive', 1);
            $this->ReadDb->where('RMM.IsDeleted', 0);
            $this->ReadDb->where('RMM.CanView', 1);
            $this->ReadDb->order_by('RMM.Sorting', 'ASC');
            $query = $this->ReadDb->get();

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Success';
            $this->EndReturnData->Data    = $query->result();

            return $this->EndReturnData;

        } catch(Exception $e) {

            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);

        }

    }

    public function getRoleSubMenus($RoleUID) {

        $this->EndReturnData = new stdClass();
        try {

            $this->ReadDb->select('RSM.RoleSubMenuUID, SubMenu.MainMenuUID, RSM.SubMenuUID, SubMenu.Name as SubMenuName, SubMenu.ControllerName, SubMenu.ParentSubMenuUID, SubMenu.Icons, RSM.Sorting, RSM.CanView, RSM.CanCreate, RSM.CanEdit, RSM.CanDelete');
            $this->ReadDb->from('UserRole.RoleSubMenusTbl as RSM');
            $this->ReadDb->join('Modules.SubMenusTbl as SubMenu', 'SubMenu.SubMenuUID = RSM.SubMenuUID', 'left');
            $this->ReadDb->where('RSM.RoleUID', $RoleUID);
            $this->ReadDb->where('RSM.IsActive', 1);
            $this->ReadDb->where('RSM.IsDeleted', 0);
            $this->ReadDb->where('RSM.CanView', 1);
            $this->ReadDb->order_by('RSM.Sorting', 'ASC');
            $query = $this->ReadDb->get();

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Success';
            $this->EndReturnData->Data    = $query->result();

            return $this->EndReturnData;

        } catch(Exception $e) {

            $this->EndReturnData->Error   = TRUE;
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

    public function getUserAuditInfo($whereCond) {

        try {

            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'ula.AuditID',
                'ula.LoginTime',
            ]);
            $this->ReadDb->from('Security.UserLoginAudit as ula');
            $this->ReadDb->where($whereCond);
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