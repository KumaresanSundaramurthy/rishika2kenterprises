<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Global_model extends CI_Model {
    
    private $EndReturnData;
    private $GlobalDb;

	function __construct() {
        parent::__construct();
        
        $this->GlobalDb = $this->load->database('Global', TRUE);

    }

    public function getTimezoneDetails($FilterArray) {

        $this->EndReturnData = new stdClass();
        try {

            $this->GlobalDb->select('Tzone.TimezoneUID as TimezoneUID, Tzone.CountryCode as CountryCode, Tzone.CountryName as CountryName, Tzone.Timezone as Timezone, Tzone.GmtOffset as GmtOffset, Tzone.UTCOffset as UTCOffset, Tzone.RawOffset as RawOffset');
            $this->GlobalDb->from('Global.TimezoneTbl as Tzone');
            if(sizeof($FilterArray) > 0) {
                $this->GlobalDb->where($FilterArray);
            }
            $query = $this->GlobalDb->get();

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