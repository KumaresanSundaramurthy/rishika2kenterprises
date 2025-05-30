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

        } catch(Exception $e) {

            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();

        }

        return $this->EndReturnData;

    }

    public function getCountryInfo() {

        $this->EndReturnData = new stdClass();
		try {

            $CountryRedisDataExists = $this->cacheservice->exists(getSiteConfiguration()->RedisName.'-countryinfo');
            if($CountryRedisDataExists->Error) {

                $this->load->library('curlservice');

                $CountryResp = $this->curlservice->retrieve(getenv('CDN_URL').'/global/countrydetails.json', 'GET', []);

                $Countries = $CountryResp->Data;
                usort($Countries, function ($a, $b) {
                    return strcmp($a['name'], $b['name']);
                });

                $this->EndReturnData->Data = $Countries;
                $this->cacheservice->set(getSiteConfiguration()->RedisName.'-countryinfo', json_encode($Countries), 43200 * 365);

            } else {

                $RedisCountryInfo = $this->cacheservice->get(getSiteConfiguration()->RedisName.'-countryinfo');
                if($RedisCountryInfo->Error === FALSE) {
                    $this->EndReturnData->Data = json_decode($RedisCountryInfo->Value, TRUE);
                } else {
                    throw new Exception($RedisCountryInfo->Message);
                }

            }

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Data Retrieved Successfully';

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        
        return $this->EndReturnData;

    }

    public function getStateofCountry($CountryCode) {

        $this->EndReturnData = new stdClass();
		try {

            $StateRedisDataExists = $this->cacheservice->exists(getSiteConfiguration()->RedisName.'-stateinfo-'.$CountryCode);
            if($StateRedisDataExists->Error) {

                $this->load->library('curlservice');
                
                $StateResp = $this->curlservice->retrieve(getenv('COUNTRY_API_URL').'/countries/'.$CountryCode.'/states', 'GET', [], array('X-CSCAPI-KEY: '.getenv('COUNTRY_API_KEY')));
                if($StateResp->Error === false && sizeof($StateResp->Data) > 0) {
                    $this->EndReturnData->Data = $StateResp->Data;
                    $this->cacheservice->set(getSiteConfiguration()->RedisName.'-stateinfo-'.$CountryCode, json_encode($StateResp->Data), 43200 * 365);
                } else {
                    throw new Exception($StateResp->Message);
                }

            } else {

                $RedisStateInfo = $this->cacheservice->get(getSiteConfiguration()->RedisName.'-stateinfo-'.$CountryCode);
                if($RedisStateInfo->Error === FALSE) {
                    $this->EndReturnData->Data = json_decode($RedisStateInfo->Value, TRUE);
                } else {
                    throw new Exception($RedisStateInfo->Message);
                }

            }

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Data Retrieved Successfully';

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        return $this->EndReturnData;

    }

    public function getCityofCountry($CountryCode) {

        $this->EndReturnData = new stdClass();
		try {
            
            // Redis City Details
            $CityRedisDataExists = $this->cacheservice->exists(getSiteConfiguration()->RedisName.'-cityinfo-'.$CountryCode);
            if($CityRedisDataExists->Error) {

                $CityResp = $this->curlservice->retrieve(getenv('COUNTRY_API_URL').'/countries/'.$CountryCode.'/cities', 'GET', [], array('X-CSCAPI-KEY: '.getenv('COUNTRY_API_KEY')));
                if($CityResp->Error === false && sizeof($CityResp->Data) > 0) {
                    $this->EndReturnData->Data = $CityResp->Data;
                    $this->cacheservice->set(getSiteConfiguration()->RedisName.'-cityinfo-'.$CountryCode, json_encode($CityResp->Data), 43200 * 365);
                } else {
                    throw new Exception($CityResp->Message);
                }

            } else {

                $RedisCityInfo = $this->cacheservice->get(getSiteConfiguration()->RedisName.'-cityinfo-'.$CountryCode);
                if($RedisCityInfo->Error === FALSE) {
                    $this->EndReturnData->Data = json_decode($RedisCityInfo->Value, TRUE);
                } else {
                    throw new Exception($RedisCityInfo->Message);
                }

            }

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Data Retrieved Successfully';

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        return $this->EndReturnData;

    }

}