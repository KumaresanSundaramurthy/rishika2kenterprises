<?php defined('BASEPATH') or exit('No direct script access allowed');

class Location_model extends CI_Model {

    private $EndReturnData;
    private $ReadDb;

    function __construct() {
        parent::__construct();
        $this->ReadDb = $this->load->database('ReadDB', TRUE);
    }

    private function _statesCacheKey($countryISO2) {
        return getSiteConfiguration()->RedisName . getenv('REDIS_STATICKEY') . '-Loc_States-' . strtoupper($countryISO2);
    }

    private function _citiesOfStateCacheKey($countryISO2, $stateISO2) {
        return getSiteConfiguration()->RedisName . getenv('REDIS_STATICKEY') . '-Loc_CityOfState-' . strtoupper($countryISO2) . '-' . strtoupper($stateISO2);
    }

    public function getStatesFromDB($countryISO2) {

        $this->EndReturnData = new stdClass();
        try {

            $iso2     = strtoupper(trim($countryISO2));
            $cacheKey = $this->_statesCacheKey($iso2);
            $cached   = $this->redisservice->getCache($cacheKey);

            if ($cached->Error) {

                $this->ReadDb->db_debug = FALSE;
                $this->ReadDb->select(['St.id', 'St.name', 'St.iso2']);
                $this->ReadDb->from('Global.StatesTbl as St');
                $this->ReadDb->where(['St.country_code' => $iso2, 'St.flag' => 1]);
                $this->ReadDb->order_by('St.name', 'ASC');
                $query = $this->ReadDb->get();

                if (!$query) {
                    $error = $this->ReadDb->error();
                    throw new Exception($error['message']);
                }

                $this->EndReturnData->Data = $query->result();
                $this->redisservice->setCache($cacheKey, $this->EndReturnData->Data, getenv('ONEYEAR_EXPIRE_SECS'));

            } else {
                $this->EndReturnData->Data = $cached->Value;
            }

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Data Retrieved Successfully';

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        return $this->EndReturnData;
    }

    public function getCitiesOfStateFromDB($countryISO2, $stateISO2) {

        $this->EndReturnData = new stdClass();
        try {

            $cISO2    = strtoupper(trim($countryISO2));
            $sISO2    = strtoupper(trim($stateISO2));
            $cacheKey = $this->_citiesOfStateCacheKey($cISO2, $sISO2);
            $cached   = $this->redisservice->getCache($cacheKey);

            if ($cached->Error) {

                $this->ReadDb->db_debug = FALSE;
                $this->ReadDb->select(['Ct.id', 'Ct.name']);
                $this->ReadDb->from('Global.CitiesTbl as Ct');
                $this->ReadDb->where(['Ct.country_code' => $cISO2, 'Ct.state_code' => $sISO2, 'Ct.flag' => 1]);
                $this->ReadDb->order_by('Ct.name', 'ASC');
                $query = $this->ReadDb->get();

                if (!$query) {
                    $error = $this->ReadDb->error();
                    throw new Exception($error['message']);
                }

                $this->EndReturnData->Data = $query->result();
                $this->redisservice->setCache($cacheKey, $this->EndReturnData->Data, getenv('ONEYEAR_EXPIRE_SECS'));

            } else {
                $this->EndReturnData->Data = $cached->Value;
            }

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Data Retrieved Successfully';

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        return $this->EndReturnData;
    }

}
