<?php defined('BASEPATH') OR exit('No direct script access allowed');

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

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

            // User — personal identity fields only
            $JwtUserData = [];
            $JwtUserData['UserUID']      = $UserData->UserUID;
            $JwtUserData['FirstName']    = $UserData->UserFirstName;
            $JwtUserData['LastName']     = $UserData->UserLastName;
            $JwtUserData['UserName']     = $UserData->UserName;
            $JwtUserData['EmailAddress'] = $UserData->UserEmailAddress;
            $JwtUserData['UserImage']    = $UserData->UserImage;
            $JwtUserData['RoleUID']      = $UserData->UserRoleUID;
            $JwtUserData['RoleName']     = $UserData->UserRoleName;
            $JwtUserData['Timezone']     = $UserData->Timezone;
            $JwtUserData['Signatures']   = $this->_loadUserSignatures($UserData->UserUID, $UserData->UserOrgUID);
            // Keep OrgShortCode/OrgToken in User for backward compat (header.php prefix building)
            $JwtUserData['OrgShortCode'] = $UserData->OrgShortCode ?? '';
            $JwtUserData['OrgToken']     = $UserData->OrgToken     ?? '';

            // Org — organisation-level fields
            $JwtOrgData = [];
            $JwtOrgData['OrgUID']       = $UserData->UserOrgUID;
            $JwtOrgData['BranchUID']    = $UserData->UserOrgUID;
            $JwtOrgData['OrgCCode']     = $UserData->UserOrgCCode;
            $JwtOrgData['OrgCISO2']     = $UserData->UserOrgCISO2;
            $JwtOrgData['OrgLogo']      = $UserData->UserOrgLogo;
            $JwtOrgData['OrgName']      = !empty($UserData->UserOrgBrandName) ? $UserData->UserOrgBrandName : ($UserData->UserOrgName ?? '');
            $JwtOrgData['OrgMobile']    = $UserData->UserOrgMobile   ?? '';
            $JwtOrgData['OrgShortCode'] = $UserData->OrgShortCode    ?? '';
            $JwtOrgData['OrgToken']     = $UserData->OrgToken        ?? '';
            $JwtOrgData['StateCode']    = $UserData->OrgStateCode    ?? '';
            $JwtOrgData['StateName']    = $UserData->OrgStateName    ?? '';

            $MainModule = $this->getRoleMainMenus($UserData->UserRoleUID)->Data;
            $SubModule  = $this->getRoleSubMenus($UserData->UserRoleUID)->Data;

            // Organisation Settings
            $GeneralSettings = $this->getOrgGeneralSettings($UserData->UserOrgUID)->Data[0];
            $ModuleInfo      = $this->getModuleDetails($UserData->UserOrgUID)->Data;

            // Product Settings (OrgProductSettingsTbl) — stored in main JWT payload
            $productSettingsResult = $this->getProductSettings($UserData->UserOrgUID);
            $ProductSettings = (!$productSettingsResult->Error && !empty($productSettingsResult->Data))
                ? $productSettingsResult->Data[0]
                : new stdClass();

            // Transaction Settings (OrgTransactionSettingsTbl) — stored in main JWT payload
            $transSettingsResult = $this->getOrgTransactionSettings($UserData->UserOrgUID);
            $TransSettings = (!$transSettingsResult->Error && !empty($transSettingsResult->Data))
                ? $transSettingsResult->Data[0]
                : new stdClass();

            // Transaction General Settings (OrgTransGeneralSettingsTbl) — T&C and date overrides
            $transGenResult = $this->getOrgTransGeneralSettings($UserData->UserOrgUID);
            $TransGenSettings = (!$transGenResult->Error && !empty($transGenResult->Data))
                ? $transGenResult->Data[0]
                : new stdClass();

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

            $jwtPayload = array('User' => $JwtUserData, 'Org' => $JwtOrgData, 'UserMainModule' => $MainModule, 'UserSubModule' => $SubModule, 'Permissions' => $Permissions, 'GenSettings' => $GeneralSettings, 'ProdSettings' => $ProductSettings, 'TransSettings' => $TransSettings, 'TransGenSettings' => $TransGenSettings, 'ModuleInfo' => $ModuleInfo);

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

            $RedisKey = generate_uuid4() . '-' . $UserData->UserUID;

            // GenSettings and ProdSettings are kept in the main JWT payload.
            // Both are accessible as $JwtData->GenSettings and $JwtData->ProdSettings on every request.
            // No separate Redis cache needed for either.
            unset($jwtPayload->JWTData['UserMainModule']);
            unset($jwtPayload->JWTData['UserSubModule']);
            unset($jwtPayload->JWTData['ModuleInfo']);

            $this->redisservice->setCache($RedisKey, $jwtPayload->JWTData, $Expiry);

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

            $this->ReadDb->select('RMM.RoleMainMenuUID, RMM.MainMenuUID, MainMenu.Name as MainMenuName, MainMenu.Icon as MainMenuIcons, MainMenu.IsDirectLink, MainMenu.DirectUrl, RMM.Sorting, RMM.CanView, RMM.CanCreate, RMM.CanEdit, RMM.CanDelete');
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

            $this->ReadDb->select("RSM.RoleSubMenuUID, Sub.MainMenuUID, RSM.SubMenuUID, Sub.Name as SubMenuName, Sub.UrlPath, Sub.ParentSubMenuUID, Sub.IsParent, Sub.Icon as SubMenuIcon, COALESCE(Mod.ControllerName, '') as ControllerName, RSM.Sorting, RSM.CanView, RSM.CanCreate, RSM.CanEdit, RSM.CanDelete");
            $this->ReadDb->from('UserRole.RoleSubMenusTbl as RSM');
            $this->ReadDb->join('Modules.SubMenusTbl Sub', 'Sub.SubMenuUID = RSM.SubMenuUID AND Sub.IsDeleted = 0');
            $this->ReadDb->join('Modules.ModuleTbl Mod', 'Mod.ModuleUID = Sub.ModuleUID AND Mod.IsDeleted = 0', 'left');
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

            $this->ReadDb->select("GeneralSettg.DecimalPoints, GeneralSettg.CurrenySymbol, GeneralSettg.PriceMaxLength, GeneralSettg.RowLimit, GeneralSettg.EnableStorage, GeneralSettg.MandatoryStorage, GeneralSettg.SerialNoDisplay, GeneralSettg.QtyMaxLength, GeneralSettg.FYStartMonth, GeneralSettg.MaxShippingAddr, COALESCE(GeneralSettg.FormDateFormat,'d-m-Y') AS FormDateFormat, COALESCE(GeneralSettg.ListDateFormat,'d-m-Y') AS ListDateFormat, COALESCE(GeneralSettg.PrintDateFormat,'d-m-Y') AS PrintDateFormat, COALESCE(GeneralSettg.FormDateTimeFormat,'d-m-Y H:i') AS FormDateTimeFormat, COALESCE(GeneralSettg.ListDateTimeFormat,'d-m-Y H:i') AS ListDateTimeFormat, COALESCE(GeneralSettg.PrintDateTimeFormat,'d-m-Y H:i') AS PrintDateTimeFormat");
            $this->ReadDb->from('Settings.OrgSettingsTbl as GeneralSettg');
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

    public function getProductSettings($OrgUID) {

        $this->EndReturnData = new stdClass();
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('DefaultProductTypeUID, DefaultDiscountTypeUID, DefaultProductTaxUID, DefaultTaxDetailUID');
            $this->ReadDb->from('Settings.OrgProductSettingsTbl');
            $this->ReadDb->where('OrgUID', (int) $OrgUID);
            $this->ReadDb->limit(1);
            $query = $this->ReadDb->get();
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $query->result();
            return $this->EndReturnData;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);
        }

    }

    public function getOrgTransactionSettings($OrgUID) {

        $this->EndReturnData = new stdClass();
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('InvoiceCancelAction');
            $this->ReadDb->from('Settings.OrgTransactionSettingsTbl');
            $this->ReadDb->where('OrgUID', (int) $OrgUID);
            $this->ReadDb->limit(1);
            $query = $this->ReadDb->get();
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $query ? $query->result() : [];
            return $this->EndReturnData;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            $this->EndReturnData->Data    = [];
            return $this->EndReturnData;
        }

    }

    public function getOrgTransGeneralSettings($OrgUID) {
        $this->EndReturnData = new stdClass();
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('TermsAndConditions, HideNavOnTransForm');
            $this->ReadDb->from('Settings.OrgTransGeneralSettingsTbl');
            $this->ReadDb->where('OrgUID', (int) $OrgUID);
            $this->ReadDb->limit(1);
            $query = $this->ReadDb->get();
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $query ? $query->result() : [];
            return $this->EndReturnData;
        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Data  = [];
            return $this->EndReturnData;
        }
    }

    public function getModuleDetails($OrgUID) {

        $this->EndReturnData = new stdClass();
        try {

            $this->ReadDb->select('Module.ModuleUID as ModuleUID, Module.Name as Name, Module.DisplayName as DisplayName, Module.Description as Description, Module.Icon as Icon, Module.OrgUID as OrgUID, Module.ControllerName as ControllerName, Module.DatabaseName as DatabaseName, Module.MasterTableName as MasterTableName, Module.ParentModuleUID as ParentModuleUID, Module.IsMainModule as IsMainModule, Module.IsModuleEnabled as IsModuleEnabled, Module.EditOnPage as EditOnPage');
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

    private function _loadUserSignatures($userUID, $orgUID) {
        try {
            $this->load->model('signature_model');
            $result  = $this->signature_model->getSignatureList((int)$userUID, (int)$orgUID);
            if ($result->Error || empty($result->Data)) return [];

            $cdnBase = getenv('FILE_UPLOAD') == 'amazonaws' ? getenv('CDN_URL') : getenv('CFLARE_R2_CDN');
            $list    = [];
            foreach ($result->Data as $sig) {
                $imgSrc = ($sig->SignatureType === 'Draw')
                    ? ($sig->DrawData ?? '')
                    : ($cdnBase . ($sig->ImagePath ?? ''));
                $list[] = [
                    'SignatureUID'  => (int)$sig->SignatureUID,
                    'Label'         => $sig->Label,
                    'SignatureType' => $sig->SignatureType,
                    'ImgSrc'        => $imgSrc,
                    'IsDefault'     => (int)$sig->IsDefault,
                ];
            }
            return $list;
        } catch (Exception $e) {
            return [];
        }
    }

}