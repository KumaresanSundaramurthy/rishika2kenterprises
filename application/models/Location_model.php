<?php defined('BASEPATH') or exit('No direct script access allowed');

class Location_model extends CI_Model {

    private $EndReturnData;
    private $ReadDb;

    function __construct() {
        parent::__construct();
        $this->ReadDb = $this->load->database('ReadDB', TRUE);
    }

    // ── Countries ─────────────────────────────────────────────────────────────
    // Single key holds the full countries list — no sub-map needed.

    public function getCountriesFromDB() {
        $this->EndReturnData = new stdClass();
        try {
            $key    = $this->redisservice->orgKey('loc-countries');
            $cached = $this->upstashservice->get($key);
            if ($cached !== null) {
                $this->EndReturnData->Error   = FALSE;
                $this->EndReturnData->Message = 'Data Retrieved Successfully';
                $this->EndReturnData->Data    = $cached;
                return $this->EndReturnData;
            }

            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select(['id', 'name', 'iso2', 'phonecode', 'emoji']);
            $this->ReadDb->from('Global.CountriesTbl');
            $this->ReadDb->where('flag', 1);
            $this->ReadDb->order_by('name', 'ASC');
            $query = $this->ReadDb->get();
            if (!$query) {
                $error = $this->ReadDb->error();
                throw new Exception($error['message']);
            }

            $data = $query->result_array();
            $this->upstashservice->set($key, $data, 0);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Data Retrieved Successfully';
            $this->EndReturnData->Data    = $data;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        return $this->EndReturnData;
    }

    // ── States ────────────────────────────────────────────────────────────────
    // Single key  loc-states  holds a map: { "in": [...], "lk": [...], ... }
    // Each country's states are merged in on first request, then served from cache.

    public function getStatesFromDB($countryISO2) {
        $this->EndReturnData = new stdClass();
        try {
            $iso2 = strtolower(trim($countryISO2));
            $key  = $this->redisservice->orgKey('loc-states');

            // HGET — fetch only this country's states (not the entire map)
            $cached = $this->upstashservice->hget($key, $iso2);
            if (is_array($cached)) {
                $this->EndReturnData->Error   = FALSE;
                $this->EndReturnData->Message = 'Data Retrieved Successfully';
                $this->EndReturnData->Data    = $cached;
                return $this->EndReturnData;
            }

            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select(['id', 'name', 'iso2']);
            $this->ReadDb->from('Global.StatesTbl');
            $this->ReadDb->where(['country_code' => strtoupper($countryISO2), 'flag' => 1]);
            $this->ReadDb->order_by('name', 'ASC');
            $query = $this->ReadDb->get();
            if (!$query) {
                $error = $this->ReadDb->error();
                throw new Exception($error['message']);
            }

            $data = $query->result_array();
            // HSET — store only this country's states as one field
            $this->upstashservice->hset($key, $iso2, $data);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Data Retrieved Successfully';
            $this->EndReturnData->Data    = $data;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        return $this->EndReturnData;
    }

    // ── Cities of a state ─────────────────────────────────────────────────────
    // Single key  loc-cities-by-state  holds a map: { "in-mh": [...], "lk-cp": [...], ... }
    // Sub-key is  {country}-{state}  (both lowercase).

    public function getCitiesOfStateFromDB($countryISO2, $stateISO2) {
        $this->EndReturnData = new stdClass();
        try {
            $cISO2  = strtolower(trim($countryISO2));
            $sISO2  = strtolower(trim($stateISO2));
            $subKey = $cISO2 . '-' . $sISO2;
            $key    = $this->redisservice->orgKey('loc-cities-by-state');

            // HGET — fetch only this state's cities (not the entire map)
            $cached = $this->upstashservice->hget($key, $subKey);
            if (is_array($cached)) {
                $this->EndReturnData->Error   = FALSE;
                $this->EndReturnData->Message = 'Data Retrieved Successfully';
                $this->EndReturnData->Data    = $cached;
                return $this->EndReturnData;
            }

            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select(['id', 'name']);
            $this->ReadDb->from('Global.CitiesTbl');
            $this->ReadDb->where(['country_code' => strtoupper($countryISO2), 'state_code' => strtoupper($stateISO2), 'flag' => 1]);
            $this->ReadDb->order_by('name', 'ASC');
            $query = $this->ReadDb->get();
            if (!$query) {
                $error = $this->ReadDb->error();
                throw new Exception($error['message']);
            }

            $data = $query->result_array();
            // HSET — store only this state's cities as one field
            $this->upstashservice->hset($key, $subKey, $data);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Data Retrieved Successfully';
            $this->EndReturnData->Data    = $data;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        return $this->EndReturnData;
    }

    // ── All cities of a country ───────────────────────────────────────────────
    // Single key  loc-all-cities  holds a map: { "in": [...], "lk": [...], ... }
    // Each country's full city list is merged in on first request.

    public function getAllCitiesOfCountryFromDB($countryISO2) {
        $this->EndReturnData = new stdClass();
        try {
            $iso2 = strtolower(trim($countryISO2));
            $key  = $this->redisservice->orgKey('loc-all-cities');

            // HGET — fetch only this country's cities (not the entire map)
            $cached = $this->upstashservice->hget($key, $iso2);
            if (is_array($cached)) {
                $this->EndReturnData->Error   = FALSE;
                $this->EndReturnData->Message = 'Data Retrieved Successfully';
                $this->EndReturnData->Data    = $cached;
                return $this->EndReturnData;
            }

            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select(['id', 'name', 'state_code']);
            $this->ReadDb->from('Global.CitiesTbl');
            $this->ReadDb->where(['country_code' => strtoupper($countryISO2), 'flag' => 1]);
            $this->ReadDb->order_by('name', 'ASC');
            $query = $this->ReadDb->get();
            if (!$query) {
                $error = $this->ReadDb->error();
                throw new Exception($error['message']);
            }

            $data = $query->result_array();
            // HSET — store only this country's cities as one field
            $this->upstashservice->hset($key, $iso2, $data);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Data Retrieved Successfully';
            $this->EndReturnData->Data    = $data;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        return $this->EndReturnData;
    }

}
