<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Organisation_model extends CI_Model {
    
    private $EndReturnData;
    private $ReadDb;

	function __construct() {
        parent::__construct();
        $this->ReadDb = $this->load->database('ReadDB', TRUE);
    }

    public function getOrganisationDetails($FilterArray) {

        $this->EndReturnData = new stdClass();
        try {

            $this->ReadDb->select('Org.OrgUID as OrgUID, Org.Name as Name, Org.ShortDescription as ShortDescription, Org.BrandName as BrandName, Org.Logo as Logo, Org.CountryCode as CountryCode, Org.CountryISO2 as CountryISO2, Org.MobileNumber as MobileNumber, Org.EmailAddress as EmailAddress, Org.GSTIN as GSTIN, Org.GSTINValidation as GSTINValidation, Org.OrgBussTypeUID as OrgBussTypeUID, Org.AlternateNumber as AlternateNumber, Org.Website as Website, Org.PANNumber as PANNumber, Org.TimezoneUID as TimezoneUID, BusinessType.Name as OrgBusinessTypeName');
            $this->ReadDb->from('Organisation.OrganisationTbl as Org');
            $this->ReadDb->join('Organisation.OrgBusinessTypeTbl as BusinessType', 'BusinessType.OrgBussTypeUID = Org.OrgBussTypeUID', 'left');
            $this->ReadDb->where($FilterArray);
            $this->ReadDb->where('Org.IsActive', 1);
            $this->ReadDb->where('Org.IsDeleted', 0);
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

    public function getAllOrganisationAddressDetails($FilterArray) {

        $this->EndReturnData = new stdClass();
        try {

            $this->ReadDb->select('Org.OrgUID as OrgUID, Org.Name as Name, Org.ShortDescription as ShortDescription, Org.BrandName as BrandName, Org.ShortCode as ShortCode, Org.Logo as Logo, Org.CountryCode as CountryCode, Org.CountryISO2 as CountryISO2, Org.MobileNumber as MobileNumber, Org.EmailAddress as EmailAddress, Org.GSTIN as GSTIN, Org.GSTINValidation as GSTINValidation, Org.OrgBussTypeUID as OrgBussTypeUID, Org.OrgIndTypeUID as OrgIndTypeUID, Org.OrgBusRegTypeUID as OrgBusRegTypeUID, Org.AlternateNumber as AlternateNumber, Org.Website as Website, Org.PANNumber as PANNumber, Org.TimezoneUID as TimezoneUID, Org.StateCode as StateCode, Org.StateName as StateName, Tz.Timezone as TimezoneText, Tz.GmtOffset as TimezoneGmtOffset, BusinessType.Name as OrgBusinessTypeName, Billing.OrgAddressUID as BAddressUID, Billing.Line1 as BLine1, Billing.Line2 as BLine2, Billing.Pincode as BPincode, Billing.City as BCity, Billing.CityText as BCityText, Billing.State as BState, Billing.StateText as BStateText');
            $this->ReadDb->from('Organisation.OrganisationTbl as Org');
            $this->ReadDb->join('Organisation.OrgBusinessTypeTbl as BusinessType', 'BusinessType.OrgBussTypeUID = Org.OrgBussTypeUID', 'left');
            $this->ReadDb->join('Global.TimezoneTbl as Tz', 'Tz.TimezoneUID = Org.TimezoneUID', 'left');
            $this->ReadDb->join('Organisation.OrgAddressTbl as Billing', "Billing.OrgUID = Org.OrgUID AND Billing.AddressType = 'Billing' AND Billing.IsDeleted = 0 AND Billing.IsActive = 1", 'left');
            $this->ReadDb->where($FilterArray);
            $this->ReadDb->where('Org.IsActive', 1);
            $this->ReadDb->where('Org.IsDeleted', 0);
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

    public function getOrgShippingAddresses($OrgUID) {

        $this->EndReturnData = new stdClass();
        try {

            $this->ReadDb->select('OrgAddressUID, Line1, Line2, Pincode, City, CityText, State, StateText');
            $this->ReadDb->from('Organisation.OrgAddressTbl');
            $this->ReadDb->where('OrgUID', (int) $OrgUID);
            $this->ReadDb->where('AddressType', 'Shipping');
            $this->ReadDb->where('IsActive', 1);
            $this->ReadDb->where('IsDeleted', 0);
            $this->ReadDb->order_by('OrgAddressUID', 'ASC');
            $query = $this->ReadDb->get();

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Success';
            $this->EndReturnData->Data    = $query->result();

            return $this->EndReturnData;

        } catch (Exception $e) {

            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);

        }

    }

    public function getOrgBusinessTypeDetails() {

        $this->EndReturnData = new stdClass();
        try {

            $OBTKey    = $this->redisservice->orgKey('org-bus-type');
            $OBTCached = $this->upstashservice->get($OBTKey);
            if ($OBTCached !== null) {
                $this->EndReturnData->Data = array_map(fn($r) => is_array($r) ? (object) $r : $r, (array)$OBTCached);
            } else {
                $this->ReadDb->select('BusinessType.OrgBussTypeUID as OrgBussTypeUID, BusinessType.Name as Name');
                $this->ReadDb->from('Organisation.OrgBusinessTypeTbl as BusinessType');
                $this->ReadDb->where('BusinessType.IsActive', 1);
                $this->ReadDb->where('BusinessType.IsDeleted', 0);
                $query = $this->ReadDb->get();
                $error = $this->ReadDb->error();
                if ($error['code']) {
                    throw new Exception($error['message']);
                }
                $this->EndReturnData->Data = $query->result();
                $this->upstashservice->set($OBTKey, $this->EndReturnData->Data, 0);
            }

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Success';

            return $this->EndReturnData;

        } catch(Exception $e) {

            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);

        }

    }

    public function getOrgIndustryTypeDetails() {

        $this->EndReturnData = new stdClass();
        try {

            $OITKey    = $this->redisservice->orgKey('org-ind-type');
            $OITCached = $this->upstashservice->get($OITKey);
            if ($OITCached !== null) {
                $this->EndReturnData->Data = array_map(fn($r) => is_array($r) ? (object) $r : $r, (array)$OITCached);
            } else {
                $this->ReadDb->select('IndustryType.OrgIndTypeUID as OrgIndTypeUID, IndustryType.Name as Name');
                $this->ReadDb->from('Organisation.OrgIndustryTypeTbl as IndustryType');
                $this->ReadDb->where('IndustryType.IsActive', 1);
                $this->ReadDb->where('IndustryType.IsDeleted', 0);
                $query = $this->ReadDb->get();
                $error = $this->ReadDb->error();
                if ($error['code']) {
                    throw new Exception($error['message']);
                }
                $this->EndReturnData->Data = $query->result();
                $this->upstashservice->set($OITKey, $this->EndReturnData->Data, 0);
            }

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Success';

            return $this->EndReturnData;

        } catch(Exception $e) {

            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);

        }

    }

    public function getOrgBusRegTypeDetails() {

        $this->EndReturnData = new stdClass();
        try {
            
            $OBRTKey    = $this->redisservice->orgKey('org-bus-reg-type');
            $OBRTCached = $this->upstashservice->get($OBRTKey);
            if ($OBRTCached !== null) {
                $this->EndReturnData->Data = array_map(fn($r) => is_array($r) ? (object) $r : $r, (array)$OBRTCached);
            } else {
                $this->ReadDb->select('BusRegType.OrgBusRegTypeUID as OrgBusRegTypeUID, BusRegType.Name as Name');
                $this->ReadDb->from('Organisation.OrgBusinessRegTypeTbl as BusRegType');
                $this->ReadDb->where('BusRegType.IsActive', 1);
                $this->ReadDb->where('BusRegType.IsDeleted', 0);
                $query = $this->ReadDb->get();
                $error = $this->ReadDb->error();
                if ($error['code']) {
                    throw new Exception($error['message']);
                }
                $this->EndReturnData->Data = $query->result();
                $this->upstashservice->set($OBRTKey, $this->EndReturnData->Data, 0);
            }

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Success';

            return $this->EndReturnData;

        } catch(Exception $e) {

            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);

        }

    }

    public function getOrgAddressDetails($FilterArray) {

        $this->EndReturnData = new stdClass();
        try {

            $this->ReadDb->select('Addr.OrgAddressUID as OrgAddressUID, Addr.OrgUID as OrgUID, Addr.AddressType as AddressType, Addr.Line1 as Line1, Addr.Line2 as Line2, Addr.Pincode as Pincode, Addr.City as City, Addr.CityText as CityText, Addr.State as State, Addr.StateText as StateText');
            $this->ReadDb->from('Organisation.OrgAddressTbl as Addr');
            if(sizeof($FilterArray) > 0) {
                $this->ReadDb->where($FilterArray);
            }
            $this->ReadDb->where('Addr.IsActive', 1);
            $this->ReadDb->where('Addr.IsDeleted', 0);
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

    /**
     * Returns the best dispatch address for an org.
     * Priority: Shipping → Billing → NULL (if neither exists).
     * Reusable from any controller that needs the org's dispatch address.
     *
     * @param  int        $orgUID
     * @return object|null  Single row with OrgAddressUID, AddressType, Line1, Line2,
     *                      Pincode, CityText, StateText — or NULL if no address found.
     */
    public function getAllOrgDispatchAddresses($orgUID) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('a.OrgAddressUID, a.OrgUID, a.AddressType, a.Line1, a.Line2, a.Pincode, a.CityText, a.StateText, o.Name AS OrgName');
            $this->ReadDb->from('Organisation.OrgAddressTbl a');
            $this->ReadDb->join('Organisation.OrganisationTbl o', 'o.OrgUID = a.OrgUID', 'left');
            $this->ReadDb->where('a.OrgUID',    (int) $orgUID);
            $this->ReadDb->where('a.IsActive',  1);
            $this->ReadDb->where('a.IsDeleted', 0);
            $this->ReadDb->order_by('a.AddressType', 'ASC');
            return $this->ReadDb->get()->result() ?? [];
        } catch (Exception $e) {
            return [];
        }
    }

    public function getOrgDispatchAddress($orgUID) {

        $this->EndReturnData = new stdClass();
        try {

            foreach (['Shipping', 'Billing'] as $type) {
                $this->ReadDb->select('Addr.OrgAddressUID, Addr.OrgUID, Addr.AddressType, Addr.Line1, Addr.Line2, Addr.Pincode, Addr.CityText, Addr.StateText');
                $this->ReadDb->from('Organisation.OrgAddressTbl as Addr');
                $this->ReadDb->where('Addr.OrgUID',      $orgUID);
                $this->ReadDb->where('Addr.AddressType', $type);
                $this->ReadDb->where('Addr.IsActive',    1);
                $this->ReadDb->where('Addr.IsDeleted',   0);
                $this->ReadDb->limit(1);
                $row = $this->ReadDb->get()->row();
                if ($row) {
                    $this->EndReturnData->Error   = FALSE;
                    $this->EndReturnData->Message = 'Success';
                    $this->EndReturnData->Data    = $row;
                    return $this->EndReturnData;
                }
            }

            // Neither Shipping nor Billing address found
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'No address found';
            $this->EndReturnData->Data    = NULL;
            return $this->EndReturnData;

        } catch (Exception $e) {

            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);

        }

    }

    /** Get org details (name, GSTIN, mobile, billing address) for thermal receipt. */
    public function getOrgForReceipt($orgUID) {

        $this->EndReturnData = new stdClass();
        try {

            $this->ReadDb->select(
                'Org.Name, Org.BrandName, Org.Logo, Org.GSTIN, Org.MobileNumber, Org.EmailAddress, Org.DevPassword, ' .
                'Org.ShortCode, Org.OrgToken, ' .
                'Addr.Line1, Addr.Line2, Addr.CityText, Addr.StateText, Addr.Pincode'
            );
            $this->ReadDb->from('Organisation.OrganisationTbl AS Org');
            $this->ReadDb->join(
                'Organisation.OrgAddressTbl AS Addr',
                "Addr.OrgUID = Org.OrgUID AND Addr.AddressType = 'Billing' AND Addr.IsDeleted = 0 AND Addr.IsActive = 1",
                'left'
            );
            $this->ReadDb->where('Org.OrgUID', $orgUID);
            $this->ReadDb->where('Org.IsDeleted', 0);
            $this->ReadDb->limit(1);
            $row = $this->ReadDb->get()->row();

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $row;
            return $this->EndReturnData;

        } catch (Exception $e) {

            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);

        }

    }

    public function getDevPassword($orgUID) {
        try {
            $this->ReadDb->select('DevPassword');
            $this->ReadDb->from('Organisation.OrganisationTbl');
            $this->ReadDb->where('OrgUID', (int)$orgUID);
            $this->ReadDb->where('IsDeleted', 0);
            $this->ReadDb->where('IsActive', 1);
            $row = $this->ReadDb->get()->row();
            return ($row && !empty($row->DevPassword)) ? $row->DevPassword : '';
        } catch (Exception $e) {
            return '';
        }
    }

    public function getDefaultOrgLogo() {
        try {
            $this->ReadDb->select('Logo');
            $this->ReadDb->from('Organisation.OrganisationTbl');
            $this->ReadDb->where('IsDeleted', 0);
            $this->ReadDb->where('IsActive', 1);
            $this->ReadDb->limit(1);
            $row = $this->ReadDb->get()->row();
            return ($row && !empty($row->Logo)) ? resolveCdnUrl($row->Logo) : '';
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * Returns org receipt info from Redis cache.
     * Key format: {ShortCode}:{OrgToken}:{env}:org_info  (falls back to org_info:{OrgUID})
     * On cache miss, fetches from DB, resolves the Logo to a full CDN URL,
     * stores in Redis, and returns. Return type matches getOrgForReceipt().
     */
    public function getOrgInfoCached($orgUID, $shortCode = '', $token = '') {
        $this->EndReturnData = new stdClass();
        try {
            $CI       =& get_instance();
            $cacheKey = $CI->redisservice->orgKey('org_info', $shortCode, $token);
            if ($cacheKey === 'org_info') {
                $cacheKey = 'org_info:' . (int)$orgUID;
            }
            $cached   = $CI->redisservice->getCache($cacheKey);

            if ($cached->Error === FALSE && !empty($cached->Value)) {
                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Data  = $cached->Value;
                return $this->EndReturnData;
            }

            // Cache miss — fetch from DB
            $result = $this->getOrgForReceipt($orgUID);
            if ($result->Error === FALSE && !empty($result->Data)) {
                $result->Data->Logo = resolveCdnUrl($result->Data->Logo ?? '');
                $expiry = (int)(getenv('LOGIN_EXPIRE_SECS') ?: 86400);
                $CI->redisservice->setCache($cacheKey, $result->Data, $expiry);
            }
            return $result;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            return $this->EndReturnData;
        }
    }

    /** Get all print theme configs for an org. */
    public function getPrintThemeConfigs($orgUID) {

        $this->EndReturnData = new stdClass();
        try {

            $this->ReadDb->from('Settings.PrintThemeConfigTbl');
            $this->ReadDb->where(['OrgUID' => $orgUID, 'IsDeleted' => 0]);
            $this->ReadDb->order_by('ThemeConfigUID', 'ASC');
            $rows = $this->ReadDb->get()->result();

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $rows;
            return $this->EndReturnData;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);
        }

    }

    /** Get print theme config for a specific transaction type, joined with template HTML. */
    public function getPrintThemeByType($orgUID, $transactionType) {

        $this->EndReturnData = new stdClass();
        try {

            $this->ReadDb->select([
                'TC.*',
                'PT.HtmlContent   AS TemplateHtmlContent',
                'PT.PreviewImage  AS TemplatePreviewImage',
                'PT.TemplateName  AS TemplateName',
            ]);
            $this->ReadDb->from('Settings.PrintThemeConfigTbl TC');
            $this->ReadDb->join('Settings.PrintTemplatesTbl PT', 'PT.TemplateUID = TC.TemplateUID AND PT.IsDeleted = 0', 'left');
            $this->ReadDb->where(['TC.OrgUID' => $orgUID, 'TC.TransactionType' => $transactionType, 'TC.IsDeleted' => 0]);
            $this->ReadDb->limit(1);
            $row = $this->ReadDb->get()->row();

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $row;
            return $this->EndReturnData;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);
        }

    }

    /** Paginated list of print theme configs for an org. */
    public function getPrintThemeConfigsPaginated($orgUID, $limit, $offset, $search = '') {

        $this->EndReturnData = new stdClass();
        try {

            $base = ['TC.OrgUID' => $orgUID, 'TC.IsDeleted' => 0];

            // Count
            $this->ReadDb->from('Settings.PrintThemeConfigTbl TC');
            $this->ReadDb->join('Settings.PrintTemplatesTbl PT', 'PT.TemplateUID = TC.TemplateUID AND PT.IsDeleted = 0', 'left');
            $this->ReadDb->where($base);
            $total = $this->ReadDb->count_all_results();

            // Rows
            $this->ReadDb->select([
                'TC.*',
                'PT.TemplateName  AS TemplateName',
                'PT.PreviewImage  AS TemplatePreviewImage',
            ]);
            $this->ReadDb->from('Settings.PrintThemeConfigTbl TC');
            $this->ReadDb->join('Settings.PrintTemplatesTbl PT', 'PT.TemplateUID = TC.TemplateUID AND PT.IsDeleted = 0', 'left');
            $this->ReadDb->where($base);
            $this->ReadDb->order_by('TC.ThemeConfigUID', 'ASC');
            $this->ReadDb->limit($limit, $offset);
            $rows = $this->ReadDb->get()->result();

            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->rows       = $rows;
            $this->EndReturnData->totalCount = $total;
            return $this->EndReturnData;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);
        }

    }

    /** Paginated list of global print templates. */
    public function getPrintTemplatesPaginated($limit, $offset, $search = '') {

        $this->EndReturnData = new stdClass();
        try {

            $this->ReadDb->from('Settings.PrintTemplatesTbl');
            $this->ReadDb->where('IsDeleted', 0);
            if ($search) {
                $this->ReadDb->like('TemplateName', $search);
            }
            $total = $this->ReadDb->count_all_results();

            $this->ReadDb->select(['TemplateUID', 'TemplateKey', 'TemplateName', 'Description', 'Category', 'PreviewImage', 'SortOrder', 'IsActive', 'CreatedOn', 'UpdatedOn']);
            $this->ReadDb->from('Settings.PrintTemplatesTbl');
            $this->ReadDb->where('IsDeleted', 0);
            if ($search) {
                $this->ReadDb->like('TemplateName', $search);
            }
            $this->ReadDb->order_by('SortOrder', 'ASC');
            $this->ReadDb->limit($limit, $offset);
            $rows = $this->ReadDb->get()->result();

            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->rows       = $rows;
            $this->EndReturnData->totalCount = $total;
            return $this->EndReturnData;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);
        }

    }

    /** Get all active print templates (for carousel / dropdown). */
    public function getPrintTemplatesAll() {

        $this->EndReturnData = new stdClass();
        try {

            $this->ReadDb->select(['TemplateUID', 'TemplateKey', 'TemplateName', 'Description', 'Category', 'PreviewImage']);
            $this->ReadDb->from('Settings.PrintTemplatesTbl');
            $this->ReadDb->where(['IsDeleted' => 0, 'IsActive' => 1]);
            $this->ReadDb->order_by('SortOrder', 'ASC');
            $rows = $this->ReadDb->get()->result();

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $rows;
            return $this->EndReturnData;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);
        }

    }

    /** Get a single print template by UID (full row including HtmlContent). */
    public function getPrintTemplateByUID($templateUID) {

        $this->EndReturnData = new stdClass();
        try {

            $this->ReadDb->from('Settings.PrintTemplatesTbl');
            $this->ReadDb->where(['TemplateUID' => (int)$templateUID, 'IsDeleted' => 0]);
            $this->ReadDb->limit(1);
            $row = $this->ReadDb->get()->row();

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $row;
            return $this->EndReturnData;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);
        }

    }

    /** Get thermal print config for an org. Returns NULL if not configured yet. */
    public function getThermalPrintConfig($orgUID) {

        $this->EndReturnData = new stdClass();
        try {

            $this->ReadDb->from('Settings.ThermalPrintConfigTbl');
            $this->ReadDb->where(['OrgUID' => $orgUID, 'IsDeleted' => 0]);
            $this->ReadDb->limit(1);
            $row = $this->ReadDb->get()->row();

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $row;
            return $this->EndReturnData;

        } catch (Exception $e) {

            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);

        }

    }

    /** Get thermal print config for a specific module (by ModuleUID). */
    public function getThermalPrintConfigByModule($orgUID, $moduleUID) {

        $this->EndReturnData = new stdClass();
        try {

            $this->ReadDb->from('Settings.ThermalPrintConfigTbl');
            $this->ReadDb->where(['OrgUID' => $orgUID, 'ModuleUID' => (int)$moduleUID, 'IsDeleted' => 0]);
            $this->ReadDb->limit(1);
            $row = $this->ReadDb->get()->row();

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $row;
            return $this->EndReturnData;

        } catch (Exception $e) {

            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);

        }

    }

    // ── Bank Accounts ────────────────────────────────────────────────────────

    /** Get all active bank accounts (including Cash) for an org. */
    public function getBankAccountList($orgUID) {

        $this->EndReturnData = new stdClass();
        try {

            $this->ReadDb->select([
                'BankAccountUID', 'AccountName', 'BankName', 'AccountNumber',
                'IFSC', 'BranchName', 'UPIId', 'UPINumber',
                'OpeningBalance', 'Notes', 'IsDefault', 'IsCash',
                'CreatedOn', 'UpdatedOn',
            ]);
            $this->ReadDb->from('Organisation.OrgBankAccountsTbl');
            $this->ReadDb->where(['OrgUID' => $orgUID, 'IsDeleted' => 0, 'IsActive' => 1]);
            $this->ReadDb->order_by('IsCash DESC, IsDefault DESC, BankAccountUID ASC');
            $rows = $this->ReadDb->get()->result();

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $rows;
            return $this->EndReturnData;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);
        }

    }

    /** Get a single bank account by UID + OrgUID. */
    public function getBankAccountByUID($bankUID, $orgUID) {

        $this->EndReturnData = new stdClass();
        try {

            $this->ReadDb->from('Organisation.OrgBankAccountsTbl');
            $this->ReadDb->where(['BankAccountUID' => (int)$bankUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]);
            $this->ReadDb->limit(1);
            $row = $this->ReadDb->get()->row();

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $row;
            return $this->EndReturnData;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);
        }

    }

    /** Calculate current balance for a single bank/cash account. */
    public function getBankBalance($bankUID, $orgUID) {

        $this->EndReturnData = new stdClass();
        try {

            $sql = "
                SELECT
                    BA.OpeningBalance,
                    COALESCE(SUM(CASE WHEN P.PartyType = 'C' AND P.IsDeleted = 0 AND P.IsActive = 1 THEN P.Amount ELSE 0 END), 0) AS TotalIn,
                    COALESCE(SUM(CASE WHEN P.PartyType = 'S' AND P.IsDeleted = 0 AND P.IsActive = 1 THEN P.Amount ELSE 0 END), 0) AS TotalOut,
                    COALESCE((SELECT SUM(FT.Amount) FROM `Transaction`.`FundTransfersTbl` FT WHERE FT.ToBankUID   = BA.BankAccountUID AND FT.IsDeleted = 0), 0) AS TransferIn,
                    COALESCE((SELECT SUM(FT.Amount) FROM `Transaction`.`FundTransfersTbl` FT WHERE FT.FromBankUID = BA.BankAccountUID AND FT.IsDeleted = 0), 0) AS TransferOut
                FROM `Organisation`.`OrgBankAccountsTbl` BA
                LEFT JOIN `Transaction`.`PaymentsTbl` P ON P.BankAccountUID = BA.BankAccountUID
                WHERE BA.BankAccountUID = ? AND BA.OrgUID = ? AND BA.IsDeleted = 0
                GROUP BY BA.BankAccountUID, BA.OpeningBalance
            ";

            $row = $this->ReadDb->query($sql, [(int)$bankUID, (int)$orgUID])->row();

            $balance = 0;
            if ($row) {
                $balance = (float)$row->OpeningBalance
                         + (float)$row->TotalIn
                         - (float)$row->TotalOut
                         + (float)$row->TransferIn
                         - (float)$row->TransferOut;
            }

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Balance = round($balance, 2);
            return $this->EndReturnData;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);
        }

    }

    /** Get all message templates for an org, joined with module name. */
    public function getMessageTemplates($orgUID) {
        $this->EndReturnData = new stdClass();
        try {
            $this->ReadDb->select(['T.*', 'M.Name AS ModuleName', "CONCAT(U.FirstName,' ',U.LastName) AS UpdatedByName"]);
            $this->ReadDb->from('Settings.MessageTemplatesTbl T');
            $this->ReadDb->join('Modules.ModuleTbl M', 'M.ModuleUID = T.ModuleUID', 'left');
            $this->ReadDb->join('Users.UserTbl U', 'U.UserUID = T.UpdatedBy', 'left');
            $this->ReadDb->where(['T.OrgUID' => $orgUID, 'T.IsDeleted' => 0]);
            $this->ReadDb->order_by('T.ModuleUID ASC, T.Channel ASC');
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $this->ReadDb->get()->result();
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        return $this->EndReturnData;
    }

    /** Fetch all message templates for a module in ONE query, keyed by Channel.
     *  Returns: ['Email' => obj, 'WhatsApp' => obj, 'SMS' => obj]  (only configured channels)
     */
    public function getModuleMessageTemplates($orgUID, $moduleUID) {
        try {
            $this->ReadDb->from('Settings.MessageTemplatesTbl');
            $this->ReadDb->where(['OrgUID' => $orgUID, 'ModuleUID' => (int)$moduleUID, 'IsDeleted' => 0]);
            $rows   = $this->ReadDb->get()->result();
            $result = [];
            foreach ($rows as $row) {
                $result[$row->Channel] = $row;
            }
            return $result;
        } catch (Exception $e) {
            return [];
        }
    }

    /** Get a single message template by org + module + channel. */
    public function getMessageTemplate($orgUID, $moduleUID, $channel) {
        $this->EndReturnData = new stdClass();
        try {
            $this->ReadDb->from('Settings.MessageTemplatesTbl');
            $this->ReadDb->where(['OrgUID' => $orgUID, 'ModuleUID' => (int)$moduleUID, 'Channel' => $channel, 'IsDeleted' => 0]);
            $this->ReadDb->limit(1);
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $this->ReadDb->get()->row();
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        return $this->EndReturnData;
    }

    public function getMessageTemplateByUID($templateUID, $orgUID) {
        $this->EndReturnData = new stdClass();
        try {
            $this->ReadDb->from('Settings.MessageTemplatesTbl');
            $this->ReadDb->where(['TemplateUID' => (int)$templateUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]);
            $this->ReadDb->limit(1);
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $this->ReadDb->get()->row();
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        return $this->EndReturnData;
    }

    /** Get all thermal print configs for an org (one per module), joined with module name. */
    public function getThermalPrintConfigList($orgUID) {

        $this->EndReturnData = new stdClass();
        try {

            $this->ReadDb->select(['TC.*', 'M.Name AS ModuleName', "CONCAT(U.FirstName, ' ', U.LastName) AS UpdatedByName"]);
            $this->ReadDb->from('Settings.ThermalPrintConfigTbl TC');
            $this->ReadDb->join('Modules.ModuleTbl M', 'M.ModuleUID = TC.ModuleUID', 'left');
            $this->ReadDb->join('Users.UserTbl U', 'U.UserUID = TC.UpdatedBy', 'left');
            $this->ReadDb->where(['TC.OrgUID' => $orgUID, 'TC.IsDeleted' => 0]);
            $this->ReadDb->order_by('TC.ThermalConfigUID', 'ASC');
            $rows = $this->ReadDb->get()->result();

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $rows;
            return $this->EndReturnData;

        } catch (Exception $e) {

            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);

        }

    }

    /** Get all modules that support thermal print (IsThermalPrint = 1). */
    public function getThermalPrintModules() {

        $this->EndReturnData = new stdClass();
        try {

            $this->ReadDb->select('ModuleUID, Name');
            $this->ReadDb->from('Modules.ModuleTbl');
            $this->ReadDb->where(['IsThermalPrint' => 1, 'IsActive' => 1, 'IsDeleted' => 0]);
            $this->ReadDb->order_by('Sorting', 'ASC');
            $rows = $this->ReadDb->get()->result();

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $rows;
            return $this->EndReturnData;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);
        }

    }

    // ── Prefix Configuration ─────────────────────────────────────────────────

    /** Get all modules that support prefix numbering (IsPrefix = 1). */
    public function getPrefixModules() {
        $this->EndReturnData = new stdClass();
        try {
            $this->ReadDb->select('ModuleUID, Name');
            $this->ReadDb->from('Modules.ModuleTbl');
            $this->ReadDb->where(['IsPrefix' => 1, 'IsActive' => 1, 'IsDeleted' => 0]);
            $this->ReadDb->order_by('Sorting', 'ASC');
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $this->ReadDb->get()->result();
        } catch (Exception $e) {
            log_message('error', 'Organisation_model::getPrefixModules — ' . $e->getMessage());
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        return $this->EndReturnData;
    }

    /** Get all prefix configurations for an org, joined with module name and updated-by user. */
    public function getPrefixConfigList($orgUID) {
        $this->EndReturnData = new stdClass();
        try {
            $this->ReadDb->select([
                'P.*',
                'M.Name AS ModuleName',
                "CONCAT(U.FirstName, ' ', IFNULL(U.LastName,'')) AS UpdatedByName",
            ]);
            $this->ReadDb->from('Settings.TransactionPrefixTbl P');
            $this->ReadDb->join('Modules.ModuleTbl M',  'M.ModuleUID = P.ModuleUID AND M.IsDeleted = 0', 'left');
            $this->ReadDb->join('Users.UserTbl U',       'U.UserUID = P.UpdatedBy',                      'left');
            $this->ReadDb->where(['P.OrgUID' => $orgUID, 'P.IsDeleted' => 0]);
            $this->ReadDb->order_by('P.IsDefault DESC, M.Sorting ASC, P.PrefixUID ASC');
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $this->ReadDb->get()->result();
        } catch (Exception $e) {
            log_message('error', 'Organisation_model::getPrefixConfigList — ' . $e->getMessage());
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        return $this->EndReturnData;
    }

    /** Get a single prefix row by PrefixUID (for validation before delete). */
    public function getPrefixByUID($prefixUID, $orgUID) {
        $this->EndReturnData = new stdClass();
        try {
            $this->ReadDb->select('P.*, M.Name AS ModuleName');
            $this->ReadDb->from('Settings.TransactionPrefixTbl P');
            $this->ReadDb->join('Modules.ModuleTbl M', 'M.ModuleUID = P.ModuleUID AND M.IsDeleted = 0', 'left');
            $this->ReadDb->where(['P.PrefixUID' => $prefixUID, 'P.OrgUID' => $orgUID, 'P.IsDeleted' => 0]);
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $this->ReadDb->get()->row();
        } catch (Exception $e) {
            log_message('error', 'Organisation_model::getPrefixByUID — ' . $e->getMessage());
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        return $this->EndReturnData;
    }

}