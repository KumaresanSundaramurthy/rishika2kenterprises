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

            $this->OrgDb->select('Org.OrgUID as OrgUID, Org.Name as Name, Org.ShortDescription as ShortDescription, Org.BrandName as BrandName, Org.Logo as Logo, Org.CountryCode as CountryCode, Org.MobileNumber as MobileNumber, Org.EmailAddress as EmailAddress, Org.GSTIN as GSTIN, Org.GSTINValidation as GSTINValidation, Org.OrgBussTypeUID as OrgBussTypeUID, Org.AlternateNumber as AlternateNumber, Org.Website as Website, Org.PANNumber as PANNumber, Org.TimezoneUID as TimezoneUID, BusinessType.Name as OrgBusinessTypeName');
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

    public function getOrgBusinessTypeDetails() {

        $this->EndReturnData = new stdClass();
        try {

            $this->OrgDb->select('BusinessType.OrgBussTypeUID as OrgBussTypeUID, BusinessType.Name as Name');
            $this->OrgDb->from('Organisation.OrgBusinessTypeTbl as BusinessType');
            $this->OrgDb->where('BusinessType.IsActive', 1);
            $this->OrgDb->where('BusinessType.IsDeleted', 0);
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