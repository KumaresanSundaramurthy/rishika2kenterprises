<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Organisation_model extends CI_Model {
    
    private $EndReturnData;
    private $OrgDb;

	function __construct() {
        parent::__construct();
        
        $this->OrgDb = $this->load->database('Organisation', TRUE);

    }

    public function getOrganisationDetails($FilterArray) {

        $this->EndReturnData = new stdClass();
        try {

            $this->OrgDb->select('Org.OrgUID as OrgUID, Org.Name as Name, Org.ShortDescription as ShortDescription, Org.BrandName as BrandName, Org.Logo as Logo, Org.CountryCode as CountryCode, Org.CountryISO2 as CountryISO2, Org.MobileNumber as MobileNumber, Org.EmailAddress as EmailAddress, Org.GSTIN as GSTIN, Org.GSTINValidation as GSTINValidation, Org.OrgBussTypeUID as OrgBussTypeUID, Org.AlternateNumber as AlternateNumber, Org.Website as Website, Org.PANNumber as PANNumber, Org.TimezoneUID as TimezoneUID, BusinessType.Name as OrgBusinessTypeName');
            $this->OrgDb->from('Organisation.OrganisationTbl as Org');
            $this->OrgDb->join('Organisation.OrgBusinessTypeTbl as BusinessType', 'BusinessType.OrgBussTypeUID = Org.OrgBussTypeUID', 'left');
            $this->OrgDb->where($FilterArray);
            $this->OrgDb->where('Org.IsActive', 1);
            $this->OrgDb->where('Org.IsDeleted', 0);
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

    public function getAllOrganisationAddressDetails($FilterArray) {

        $this->EndReturnData = new stdClass();
        try {

            $this->OrgDb->select('Org.OrgUID as OrgUID, Org.Name as Name, Org.ShortDescription as ShortDescription, Org.BrandName as BrandName, Org.Logo as Logo, Org.CountryCode as CountryCode, Org.CountryISO2 as CountryISO2, Org.MobileNumber as MobileNumber, Org.EmailAddress as EmailAddress, Org.GSTIN as GSTIN, Org.GSTINValidation as GSTINValidation, Org.OrgBussTypeUID as OrgBussTypeUID, Org.OrgIndTypeUID as OrgIndTypeUID, Org.OrgBusRegTypeUID as OrgBusRegTypeUID, Org.AlternateNumber as AlternateNumber, Org.Website as Website, Org.PANNumber as PANNumber, Org.TimezoneUID as TimezoneUID, BusinessType.Name as OrgBusinessTypeName, Billing.OrgAddressUID as BAddressUID, Billing.Line1 as BLine1, Billing.Line2 as BLine2, Billing.Pincode as BPincode, Billing.City as BCity, Billing.CityText as BCityText, Billing.State as BState, Billing.StateText as BStateText, Shipping.OrgAddressUID as SAddressUID, Shipping.Line1 as SLine1, Shipping.Line2 as SLine2, Shipping.Pincode as SPincode, Shipping.City as SCity, Shipping.CityText as SCityText, Shipping.State as SState, Shipping.StateText as SStateText');
            $this->OrgDb->from('Organisation.OrganisationTbl as Org');
            $this->OrgDb->join('Organisation.OrgBusinessTypeTbl as BusinessType', 'BusinessType.OrgBussTypeUID = Org.OrgBussTypeUID', 'left');
            $this->OrgDb->join('Organisation.OrgAddressTbl as Billing', "Billing.OrgUID = Org.OrgUID AND Billing.AddressType = 'Billing' AND Billing.IsDeleted = 0 AND Billing.IsActive = 1", 'left');
            $this->OrgDb->join('Organisation.OrgAddressTbl as Shipping', "Shipping.OrgUID = Org.OrgUID AND Shipping.AddressType = 'Shipping' AND Shipping.IsDeleted = 0 AND Shipping.IsActive = 1", 'left');
            $this->OrgDb->where($FilterArray);
            $this->OrgDb->where('Org.IsActive', 1);
            $this->OrgDb->where('Org.IsDeleted', 0);
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

    public function getOrgBusinessTypeDetails() {

        $this->EndReturnData = new stdClass();
        try {

            $OrgBusTypeKey = getSiteConfiguration()->RedisName . '-orgbustypeinfo';
            $OBTGet_Data = $this->session->userdata($OrgBusTypeKey);
            if (empty($OBTGet_Data)) {

                $this->OrgDb->select('BusinessType.OrgBussTypeUID as OrgBussTypeUID, BusinessType.Name as Name');
                $this->OrgDb->from('Organisation.OrgBusinessTypeTbl as BusinessType');
                $this->OrgDb->where('BusinessType.IsActive', 1);
                $this->OrgDb->where('BusinessType.IsDeleted', 0);
                $query = $this->OrgDb->get();
                $error = $this->OrgDb->error();
                if ($error['code']) {
                    throw new Exception($error['message']);
                } else {
                    $this->EndReturnData->Data = $query->result();
                }

                $this->session->set_userdata($OrgBusTypeKey, $this->EndReturnData->Data);
                
            } else {
                $this->EndReturnData->Data = $OBTGet_Data;
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

            $OrgIndusTypeKey = getSiteConfiguration()->RedisName . '-orgindustypeinfo';
            $OITGet_Data = $this->session->userdata($OrgIndusTypeKey);
            if (empty($OITGet_Data)) {

                $this->OrgDb->select('IndustryType.OrgIndTypeUID as OrgIndTypeUID, IndustryType.Name as Name');
                $this->OrgDb->from('Organisation.OrgIndustryTypeTbl as IndustryType');
                $this->OrgDb->where('IndustryType.IsActive', 1);
                $this->OrgDb->where('IndustryType.IsDeleted', 0);
                $query = $this->OrgDb->get();
                $error = $this->OrgDb->error();
                if ($error['code']) {
                    throw new Exception($error['message']);
                } else {
                    $this->EndReturnData->Data = $query->result();
                }

                $this->session->set_userdata($OrgIndusTypeKey, $this->EndReturnData->Data);

            } else {
                $this->EndReturnData->Data = $OITGet_Data;
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

            $OrgBusRegTypeKey = getSiteConfiguration()->RedisName . '-orgbusregtypeinfo';
            $OBRTGet_Data = $this->session->userdata($OrgBusRegTypeKey);
            if (empty($OBRTGet_Data)) {

                $this->OrgDb->select('BusRegType.OrgBusRegTypeUID as OrgBusRegTypeUID, BusRegType.Name as Name');
                $this->OrgDb->from('Organisation.OrgBusinessRegTypeTbl as BusRegType');
                $this->OrgDb->where('BusRegType.IsActive', 1);
                $this->OrgDb->where('BusRegType.IsDeleted', 0);
                $query = $this->OrgDb->get();
                $error = $this->OrgDb->error();
                if ($error['code']) {
                    throw new Exception($error['message']);
                } else {
                    $this->EndReturnData->Data = $query->result();
                }

                $this->session->set_userdata($OrgBusRegTypeKey, $this->EndReturnData->Data);

            } else {
                $this->EndReturnData->Data = $OBRTGet_Data;
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

            $this->OrgDb->select('Addr.OrgAddressUID as OrgAddressUID, Addr.OrgUID as OrgUID, Addr.AddressType as AddressType, Addr.Line1 as Line1, Addr.Line2 as Line2, Addr.Pincode as Pincode, Addr.City as City, Addr.CityText as CityText, Addr.State as State, Addr.StateText as StateText');
            $this->OrgDb->from('Organisation.OrgAddressTbl as Addr');
            if(sizeof($FilterArray) > 0) {
                $this->OrgDb->where($FilterArray);
            }
            $this->OrgDb->where('Addr.IsActive', 1);
            $this->OrgDb->where('Addr.IsDeleted', 0);
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

}