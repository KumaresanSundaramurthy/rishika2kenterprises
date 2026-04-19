<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Organisation_model extends CI_Model {
    
    private $EndReturnData;
    private $ReadDb;
    private $OrgBusTypeKey;
    private $OrgIndTypeKey;
    private $OrgBusRegTypeKey;

	function __construct() {
        parent::__construct();
        
        $this->OrgBusTypeKey = getSiteConfiguration()->RedisName . '-orgbustypeinfo';
        $this->OrgIndTypeKey = getSiteConfiguration()->RedisName . '-orgindustypeinfo';
        $this->OrgBusRegTypeKey = getSiteConfiguration()->RedisName . '-orgbusregtypeinfo';
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

            $this->ReadDb->select('Org.OrgUID as OrgUID, Org.Name as Name, Org.ShortDescription as ShortDescription, Org.BrandName as BrandName, Org.Logo as Logo, Org.CountryCode as CountryCode, Org.CountryISO2 as CountryISO2, Org.MobileNumber as MobileNumber, Org.EmailAddress as EmailAddress, Org.GSTIN as GSTIN, Org.GSTINValidation as GSTINValidation, Org.OrgBussTypeUID as OrgBussTypeUID, Org.OrgIndTypeUID as OrgIndTypeUID, Org.OrgBusRegTypeUID as OrgBusRegTypeUID, Org.AlternateNumber as AlternateNumber, Org.Website as Website, Org.PANNumber as PANNumber, Org.TimezoneUID as TimezoneUID, BusinessType.Name as OrgBusinessTypeName, Billing.OrgAddressUID as BAddressUID, Billing.Line1 as BLine1, Billing.Line2 as BLine2, Billing.Pincode as BPincode, Billing.City as BCity, Billing.CityText as BCityText, Billing.State as BState, Billing.StateText as BStateText, Shipping.OrgAddressUID as SAddressUID, Shipping.Line1 as SLine1, Shipping.Line2 as SLine2, Shipping.Pincode as SPincode, Shipping.City as SCity, Shipping.CityText as SCityText, Shipping.State as SState, Shipping.StateText as SStateText');
            $this->ReadDb->from('Organisation.OrganisationTbl as Org');
            $this->ReadDb->join('Organisation.OrgBusinessTypeTbl as BusinessType', 'BusinessType.OrgBussTypeUID = Org.OrgBussTypeUID', 'left');
            $this->ReadDb->join('Organisation.OrgAddressTbl as Billing', "Billing.OrgUID = Org.OrgUID AND Billing.AddressType = 'Billing' AND Billing.IsDeleted = 0 AND Billing.IsActive = 1", 'left');
            $this->ReadDb->join('Organisation.OrgAddressTbl as Shipping', "Shipping.OrgUID = Org.OrgUID AND Shipping.AddressType = 'Shipping' AND Shipping.IsDeleted = 0 AND Shipping.IsActive = 1", 'left');
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

    public function getOrgBusinessTypeDetails() {

        $this->EndReturnData = new stdClass();
        try {

            $OBTKey = $this->OrgBusTypeKey;
            $OBTGet_Data = $this->redis_cache->get($OBTKey);
            if ($OBTGet_Data->Error) {

                $this->ReadDb->select('BusinessType.OrgBussTypeUID as OrgBussTypeUID, BusinessType.Name as Name');
                $this->ReadDb->from('Organisation.OrgBusinessTypeTbl as BusinessType');
                $this->ReadDb->where('BusinessType.IsActive', 1);
                $this->ReadDb->where('BusinessType.IsDeleted', 0);
                $query = $this->ReadDb->get();
                $error = $this->ReadDb->error();
                if ($error['code']) {
                    throw new Exception($error['message']);
                } else {
                    $this->EndReturnData->Data = $query->result();
                }
                
                $this->redis_cache->set($OBTKey, $this->EndReturnData->Data, getenv('ONEYEAR_EXPIRE_SECS'));
                
            } else {
                $this->EndReturnData->Data = $OBTGet_Data->Value;
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

            $OITKey = $this->OrgIndTypeKey;
            $OITGet_Data = $this->redis_cache->get($OITKey);
            if ($OITGet_Data->Error) {

                $this->ReadDb->select('IndustryType.OrgIndTypeUID as OrgIndTypeUID, IndustryType.Name as Name');
                $this->ReadDb->from('Organisation.OrgIndustryTypeTbl as IndustryType');
                $this->ReadDb->where('IndustryType.IsActive', 1);
                $this->ReadDb->where('IndustryType.IsDeleted', 0);
                $query = $this->ReadDb->get();
                $error = $this->ReadDb->error();
                if ($error['code']) {
                    throw new Exception($error['message']);
                } else {
                    $this->EndReturnData->Data = $query->result();
                }

                $this->redis_cache->set($OITKey, $this->EndReturnData->Data, getenv('ONEYEAR_EXPIRE_SECS'));

            } else {
                $this->EndReturnData->Data = $OITGet_Data->Value;
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
            
            $OBRTKey = $this->OrgBusRegTypeKey;
            $OBRTGet_Data = $this->redis_cache->get($OBRTKey);
            if ($OBRTGet_Data->Error) {

                $this->ReadDb->select('BusRegType.OrgBusRegTypeUID as OrgBusRegTypeUID, BusRegType.Name as Name');
                $this->ReadDb->from('Organisation.OrgBusinessRegTypeTbl as BusRegType');
                $this->ReadDb->where('BusRegType.IsActive', 1);
                $this->ReadDb->where('BusRegType.IsDeleted', 0);
                $query = $this->ReadDb->get();
                $error = $this->ReadDb->error();
                if ($error['code']) {
                    throw new Exception($error['message']);
                } else {
                    $this->EndReturnData->Data = $query->result();
                }

                $this->redis_cache->set($OBRTKey, $this->EndReturnData->Data, getenv('ONEYEAR_EXPIRE_SECS'));

            } else {
                $this->EndReturnData->Data = $OBRTGet_Data->Value;
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
                'Org.Name, Org.BrandName, Org.GSTIN, Org.MobileNumber, Org.EmailAddress, ' .
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

    /** Get all print theme configs for an org. */
    public function getPrintThemeConfigs($orgUID) {

        $this->EndReturnData = new stdClass();
        try {

            $this->ReadDb->from('Organisation.PrintThemeConfigTbl');
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
            $this->ReadDb->from('Organisation.PrintThemeConfigTbl TC');
            $this->ReadDb->join('Organisation.PrintTemplatesTbl PT', 'PT.TemplateUID = TC.TemplateUID AND PT.IsDeleted = 0', 'left');
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
            $this->ReadDb->from('Organisation.PrintThemeConfigTbl TC');
            $this->ReadDb->join('Organisation.PrintTemplatesTbl PT', 'PT.TemplateUID = TC.TemplateUID AND PT.IsDeleted = 0', 'left');
            $this->ReadDb->where($base);
            $total = $this->ReadDb->count_all_results();

            // Rows
            $this->ReadDb->select([
                'TC.*',
                'PT.TemplateName  AS TemplateName',
                'PT.PreviewImage  AS TemplatePreviewImage',
            ]);
            $this->ReadDb->from('Organisation.PrintThemeConfigTbl TC');
            $this->ReadDb->join('Organisation.PrintTemplatesTbl PT', 'PT.TemplateUID = TC.TemplateUID AND PT.IsDeleted = 0', 'left');
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

            $this->ReadDb->from('Organisation.PrintTemplatesTbl');
            $this->ReadDb->where('IsDeleted', 0);
            if ($search) {
                $this->ReadDb->like('TemplateName', $search);
            }
            $total = $this->ReadDb->count_all_results();

            $this->ReadDb->select(['TemplateUID', 'TemplateKey', 'TemplateName', 'Description', 'Category', 'PreviewImage', 'SortOrder', 'IsActive', 'CreatedOn', 'UpdatedOn']);
            $this->ReadDb->from('Organisation.PrintTemplatesTbl');
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
            $this->ReadDb->from('Organisation.PrintTemplatesTbl');
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

            $this->ReadDb->from('Organisation.PrintTemplatesTbl');
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

            $this->ReadDb->from('Organisation.ThermalPrintConfigTbl');
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

    /** Get thermal print config for a specific transaction type. */
    public function getThermalPrintConfigByType($orgUID, $transactionType) {

        $this->EndReturnData = new stdClass();
        try {

            $this->ReadDb->from('Organisation.ThermalPrintConfigTbl');
            $this->ReadDb->where(['OrgUID' => $orgUID, 'TransactionType' => $transactionType, 'IsDeleted' => 0]);
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
            $this->ReadDb->from('Transaction.OrgBankAccountsTbl');
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

            $this->ReadDb->from('Transaction.OrgBankAccountsTbl');
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

    /** Get all thermal print configs for an org (one per transaction type). */
    public function getThermalPrintConfigList($orgUID) {

        $this->EndReturnData = new stdClass();
        try {

            $this->ReadDb->from('Organisation.ThermalPrintConfigTbl');
            $this->ReadDb->where(['OrgUID' => $orgUID, 'IsDeleted' => 0]);
            $this->ReadDb->order_by('ThermalConfigUID', 'ASC');
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

}