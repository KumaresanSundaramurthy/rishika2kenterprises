<?php defined('BASEPATH') or exit('No direct script access allowed');

class Global_model extends CI_Model {

    private $EndReturnData;
    private $ReadDb;

    function __construct() {
        parent::__construct();
        $this->ReadDb = $this->load->database('ReadDB', TRUE);
    }

    public function getTimezoneDetails(array $FilterArray = []): object {

        $this->EndReturnData = new stdClass();
        try {

            $key    = $this->redisservice->orgKey('loc-timezones');
            $cached = $this->upstashservice->get($key);
            if ($cached !== null) {
                $this->EndReturnData->Error   = FALSE;
                $this->EndReturnData->Message = 'Success';
                $this->EndReturnData->Data    = $cached;
                return $this->EndReturnData;
            }

            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'Tzone.TimezoneUID as TimezoneUID',
                'Tzone.CountryCode as CountryCode',
                'Tzone.CountryName as CountryName',
                'Tzone.Timezone as Timezone',
                'Tzone.GmtOffset as GmtOffset',
                'Tzone.UTCOffset as UTCOffset',
                'Tzone.RawOffset as RawOffset',
            ]);
            $this->ReadDb->from('Global.TimezoneTbl as Tzone');
            if (!empty($FilterArray)) {
                $this->ReadDb->where($FilterArray);
            }
            $query = $this->ReadDb->get();
            if (!$query) {
                $error = $this->ReadDb->error();
                throw new Exception($error['message']);
            }

            $data = $query->result_array();
            $this->upstashservice->set($key, $data, 0);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Success';
            $this->EndReturnData->Data    = $data;

        } catch (Exception $e) {
            notifyError('Global_model::getTimezoneDetails', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        return $this->EndReturnData;

    }

    public function getCountryInfo(): object {
        $this->load->model('location_model');
        return $this->location_model->getCountriesFromDB();
    }

    public function getStateofCountry(string $CountryCode): object {
        $this->load->model('location_model');
        return $this->location_model->getStatesFromDB($CountryCode);
    }

    public function getCityofCountry(string $CountryCode): object {
        $this->load->model('location_model');
        return $this->location_model->getAllCitiesOfCountryFromDB($CountryCode);
    }

    public function getCitiesOfState(string $countryISO2, string $stateISO2): object {
        $this->load->model('location_model');
        return $this->location_model->getCitiesOfStateFromDB($countryISO2, $stateISO2);
    }

    public function getPrimaryUnitInfo(): object {

        $this->EndReturnData = new stdClass();
        try {
            
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'PrimaryUnit.PrimaryUnitUID AS PrimaryUnitUID',
                'PrimaryUnit.Name AS Name',
                'PrimaryUnit.ShortName AS ShortName',
                'PrimaryUnit.Description AS Description',
                'PrimaryUnit.UpdatedOn as UpdatedOn',
            ]);
            $this->ReadDb->from('Global.PrimaryUnitTbl as PrimaryUnit');
            $this->ReadDb->where(['PrimaryUnit.IsDeleted' => 0, 'PrimaryUnit.IsActive' => 1]);
            $this->ReadDb->order_by('PrimaryUnit.Sorting', 'ASC');
            $query = $this->ReadDb->get();
            if (!$query) {
                $error = $this->ReadDb->error();
                throw new Exception($error['message']);
            }
            $this->EndReturnData->Data = $query->result();

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Data Retrieved Successfully';

        } catch (Exception $e) {
            notifyError('Global_model::getPrimaryUnitInfo', $e);
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        return $this->EndReturnData;

    }

    public function getDiscountTypeInfo(): object {

        $this->EndReturnData = new stdClass();
        try {

            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'DiscType.DiscountTypeUID AS DiscountTypeUID',
                'DiscType.Name AS Name',
                'DiscType.DisplayName AS DisplayName',
                'DiscType.Symbol AS Symbol',
                'DiscType.UpdatedOn as UpdatedOn',
            ]);
            $this->ReadDb->from('Global.DiscountTypeTbl as DiscType');
            $this->ReadDb->where(['DiscType.IsDeleted' => 0, 'DiscType.IsActive' => 1]);
            $this->ReadDb->order_by('DiscType.Sorting', 'ASC');
            $query = $this->ReadDb->get();
            if (!$query) {
                $error = $this->ReadDb->error();
                throw new Exception($error['message']);
            }
            $this->EndReturnData->Data = $query->result();

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Data Retrieved Successfully';

        } catch (Exception $e) {
            notifyError('Global_model::getDiscountTypeInfo', $e);
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        return $this->EndReturnData;

    }

    public function getProductTypeInfo(): object {

        $this->EndReturnData = new stdClass();
        try {
            
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'ProdType.ProductTypeUID AS ProductTypeUID',
                'ProdType.Name AS Name',
                'ProdType.UpdatedOn as UpdatedOn'
            ]);
            $this->ReadDb->from('Global.ProductTypeTbl as ProdType');
            $this->ReadDb->where(['ProdType.IsDeleted' => 0, 'ProdType.IsActive' => 1]);
            $this->ReadDb->order_by('ProdType.Sorting', 'ASC');
            $query = $this->ReadDb->get();
            if (!$query) {
                $error = $this->ReadDb->error();
                throw new Exception($error['message']);
            }
            $this->EndReturnData->Data = $query->result();

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Data Retrieved Successfully';

        } catch (Exception $e) {
            notifyError('Global_model::getProductTypeInfo', $e);
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        return $this->EndReturnData;

    }

    public function getProductTaxInfo(): object {

        $this->EndReturnData = new stdClass();
        try {

            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'ProdTax.ProductTaxUID AS ProductTaxUID',
                'ProdTax.Name AS Name',
                'ProdTax.UpdatedOn as UpdatedOn',
            ]);
            $this->ReadDb->from('Global.ProductTaxTbl as ProdTax');
            $this->ReadDb->where(['ProdTax.IsDeleted' => 0, 'ProdTax.IsActive' => 1]);
            $this->ReadDb->order_by('ProdTax.Sorting', 'ASC');
            $query = $this->ReadDb->get();
            if (!$query) {
                $error = $this->ReadDb->error();
                throw new Exception($error['message']);
            }
            $this->EndReturnData->Data = $query->result();

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Data Retrieved Successfully';

        } catch (Exception $e) {
            notifyError('Global_model::getProductTaxInfo', $e);
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        return $this->EndReturnData;

    }

    public function getTaxDetailsInfo(): object {

        $this->EndReturnData = new stdClass();
        try {
            
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'TaxDetail.TaxDetailsUID AS TaxDetailsUID',
                'TaxDetail.TaxName AS TaxName',
                'TaxDetail.Percentage AS Percentage',
                'TaxDetail.CGST AS CGST',
                'TaxDetail.SGST AS SGST',
                'TaxDetail.IGST AS IGST',
                'TaxDetail.UpdatedOn as UpdatedOn',
            ]);
            $this->ReadDb->from('Global.TaxDetailsTbl as TaxDetail');
            $this->ReadDb->where(['TaxDetail.IsDeleted' => 0, 'TaxDetail.IsActive' => 1]);
            $this->ReadDb->order_by('TaxDetail.Sorting', 'ASC');
            $query = $this->ReadDb->get();
            if (!$query) {
                $error = $this->ReadDb->error();
                throw new Exception($error['message']);
            }
            $this->EndReturnData->Data = $query->result();

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Data Retrieved Successfully';

        } catch (Exception $e) {
            notifyError('Global_model::getTaxDetailsInfo', $e);
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        return $this->EndReturnData;

    }

    public function getTaxPercentageDetailsInfo(array $WhereArrayCondition): object {

        $this->EndReturnData = new stdClass();
        try {
            $cacheKey = $this->redisservice->globalKey('tax-details');
            $cached   = $this->upstashservice->get($cacheKey);
            if ($cached !== null) {
                $all = array_map(fn($r) => is_array($r) ? (object) $r : $r, (array)$cached);
            } else {
                $this->ReadDb->db_debug = FALSE;
                $this->ReadDb->select([
                    'TaxDetail.TaxDetailsUID AS TaxDetailsUID',
                    'TaxDetail.TaxName AS TaxName',
                    'TaxDetail.Percentage AS Percentage',
                    'TaxDetail.CGST AS CGST',
                    'TaxDetail.SGST AS SGST',
                    'TaxDetail.IGST AS IGST',
                    'TaxDetail.UpdatedOn as UpdatedOn',
                ]);
                $this->ReadDb->from('Global.TaxDetailsTbl as TaxDetail');
                $this->ReadDb->where(['TaxDetail.IsDeleted' => 0, 'TaxDetail.IsActive' => 1]);
                $this->ReadDb->order_by('TaxDetail.Sorting', 'ASC');
                $query = $this->ReadDb->get();
                if (!$query) {
                    $error = $this->ReadDb->error();
                    throw new Exception($error['message']);
                }
                $all = $query->result();
                $this->upstashservice->set($cacheKey, $all, (int)getenv('ONEYEAR_EXPIRE_SECS'));
            }

            if (!empty($WhereArrayCondition)) {
                $all = array_values(array_filter($all, function ($row) use ($WhereArrayCondition) {
                    foreach ($WhereArrayCondition as $col => $val) {
                        $field = strpos($col, '.') !== false ? explode('.', $col)[1] : $col;
                        if (!isset($row->$field) || $row->$field != $val) return false;
                    }
                    return true;
                }));
            }

            $this->EndReturnData->Data  = $all;
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Data Retrieved Successfully';

        } catch (Exception $e) {
            notifyError('Global_model::getTaxPercentageDetailsInfo', $e);
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        return $this->EndReturnData;

    }

    public function getSalutations(): object {
        $this->EndReturnData = new stdClass();
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('SalutationUID, SalutationName, Sorting');
            $this->ReadDb->from('Global.SalutationTbl');
            $this->ReadDb->where(['IsDeleted' => 0, 'IsActive' => 1]);
            $this->ReadDb->order_by('Sorting', 'ASC');
            $query = $this->ReadDb->get();
            if (!$query) throw new Exception($this->ReadDb->error()['message'] ?? 'DB error');
            $this->EndReturnData->Data    = $query->result();
            $this->EndReturnData->Error   = false;
            $this->EndReturnData->Message = 'Success';
        } catch (Exception $e) {
            notifyError('Global_model::getSalutations', $e);
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        return $this->EndReturnData;
    }

    public function getStorageTypeData(): object {

        $this->EndReturnData = new stdClass();
        try {
            
            $STRDEKey    = $this->redisservice->orgKey('storage-type');
            $STRDECached = $this->upstashservice->get($STRDEKey);
            if ($STRDECached !== null) {
                $this->EndReturnData->Data = array_map(fn($r) => is_array($r) ? (object) $r : $r, (array)$STRDECached);
            } else {
                $this->ReadDb->db_debug = FALSE;
                $this->ReadDb->select([
                    'StorageType.StorageTypeUID AS StorageTypeUID',
                    'StorageType.Name AS Name',
                    'StorageType.UpdatedOn as UpdatedOn'
                ]);
                $this->ReadDb->from('Global.StorageTypeTbl as StorageType');
                $this->ReadDb->where(['StorageType.IsDeleted' => 0, 'StorageType.IsActive' => 1]);
                $this->ReadDb->group_by('StorageType.StorageTypeUID');
                $this->ReadDb->order_by('StorageType.Sorting', 'ASC');
                $query = $this->ReadDb->get();
                if (!$query) {
                    $error = $this->ReadDb->error();
                    throw new Exception($error['message']);
                }
                $this->EndReturnData->Data = $query->result();
                $this->upstashservice->set($STRDEKey, $this->EndReturnData->Data, 0);
            }

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Data Retrieved Successfully';
        } catch (Exception $e) {
            notifyError('Global_model::getStorageTypeData', $e);
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        return $this->EndReturnData;

    }

    public function getModuleDetails(array $WhereCond = [], array $whereInCondition = []): array {

        $this->EndReturnData = new StdClass();
        try {

            $params_hash   = md5(json_encode(['WC' => $WhereCond, 'WIC' => $whereInCondition]));
            $ModKey        = $this->redisservice->orgKey('module-details-' . $params_hash);
            $ModCached     = $this->upstashservice->get($ModKey);
            if ($ModCached !== null) {
                return $ModCached;
            }

            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'Modules.ModuleUID AS ModuleUID',
                'Modules.Name AS Name',
                'Modules.ControllerName AS ControllerName',
            ]);
            $this->ReadDb->from('Modules.ModuleTbl as Modules');
            $this->ReadDb->where(['Modules.IsDeleted' => 0, 'Modules.IsActive' => 1]);
            if (!empty($WhereCond)) {
                $this->ReadDb->where($WhereCond);
            }
            if (!empty($whereInCondition)) {
                foreach ($whereInCondition as $wkey => $wval) {
                    $this->ReadDb->where_in($wkey, $wval);
                }
            }
            $query = $this->ReadDb->get();
            if (!$query) {
                $error = $this->ReadDb->error();
                throw new Exception($error['message']);
            }
            $this->EndReturnData->Data = $query->result();
            $this->upstashservice->set($ModKey, $this->EndReturnData->Data, (int)getenv('ONEMONTH_EXPIRE_SECS'));
            return $this->EndReturnData->Data;

        } catch (Exception $e) {
            notifyError('Global_model::getModuleDetails', $e);
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);
        }

    }


    public function getSingleRow(string $dbName = '', string $table = '', array $where = [], string $select = '*'): ?object {
        $query = $this->ReadDb->select($select)
                          ->from($dbName.'.'.$table)
                          ->where($where)
                          ->limit(1)
                          ->get();
        if ($query->num_rows() > 0) {
            return $query->row();
        }
        return null;
    }
    
}