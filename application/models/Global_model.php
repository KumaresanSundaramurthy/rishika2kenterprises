<?php defined('BASEPATH') or exit('No direct script access allowed');

class Global_model extends CI_Model
{

    private $EndReturnData;
    private $GlobalDb;
    private $ModuleDb;

    function __construct()
    {
        parent::__construct();

        $this->GlobalDb = $this->load->database('Global', TRUE);
        $this->ModuleDb = $this->load->database('Modules', TRUE);
    }

    public function getTimezoneDetails($FilterArray)
    {

        $this->EndReturnData = new stdClass();
        try {

            $this->GlobalDb->select('Tzone.TimezoneUID as TimezoneUID, Tzone.CountryCode as CountryCode, Tzone.CountryName as CountryName, Tzone.Timezone as Timezone, Tzone.GmtOffset as GmtOffset, Tzone.UTCOffset as UTCOffset, Tzone.RawOffset as RawOffset');
            $this->GlobalDb->from('Global.TimezoneTbl as Tzone');
            if (sizeof($FilterArray) > 0) {
                $this->GlobalDb->where($FilterArray);
            }
            $query = $this->GlobalDb->get();

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Success';
            $this->EndReturnData->Data = $query->result();
        } catch (Exception $e) {

            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        return $this->EndReturnData;
    }

    public function getCountryInfo()
    {

        $this->EndReturnData = new stdClass();
        try {

            $CountryRedisDataExists = $this->cacheservice->exists(getSiteConfiguration()->RedisName . '-countryinfo');
            if ($CountryRedisDataExists->Error) {

                $this->load->library('curlservice');

                $CountryResp = $this->curlservice->retrieve(getenv('CDN_URL') . '/global/countrydetails.json', 'GET', []);

                $Countries = $CountryResp->Data;
                usort($Countries, function ($a, $b) {
                    return strcmp($a['name'], $b['name']);
                });

                $this->EndReturnData->Data = $Countries;
                $this->cacheservice->set(getSiteConfiguration()->RedisName . '-countryinfo', json_encode($Countries), 43200 * 365);
            } else {

                $RedisCountryInfo = $this->cacheservice->get(getSiteConfiguration()->RedisName . '-countryinfo');
                if ($RedisCountryInfo->Error === FALSE) {
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

    public function getStateofCountry($CountryCode)
    {

        $this->EndReturnData = new stdClass();
        try {

            $StateRedisDataExists = $this->cacheservice->exists(getSiteConfiguration()->RedisName . '-stateinfo-' . $CountryCode);
            if ($StateRedisDataExists->Error) {

                $this->load->library('curlservice');

                $StateResp = $this->curlservice->retrieve(getenv('COUNTRY_API_URL') . '/countries/' . $CountryCode . '/states', 'GET', [], array('X-CSCAPI-KEY: ' . getenv('COUNTRY_API_KEY')));
                if ($StateResp->Error === false && sizeof($StateResp->Data) > 0) {
                    $this->EndReturnData->Data = $StateResp->Data;
                    $this->cacheservice->set(getSiteConfiguration()->RedisName . '-stateinfo-' . $CountryCode, json_encode($StateResp->Data), 43200 * 365);
                } else {
                    throw new Exception($StateResp->Message);
                }
            } else {

                $RedisStateInfo = $this->cacheservice->get(getSiteConfiguration()->RedisName . '-stateinfo-' . $CountryCode);
                if ($RedisStateInfo->Error === FALSE) {
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

    public function getCityofCountry($CountryCode)
    {

        $this->EndReturnData = new stdClass();
        try {

            // Redis City Details
            $CityRedisDataExists = $this->cacheservice->exists(getSiteConfiguration()->RedisName . '-cityinfo-' . $CountryCode);
            if ($CityRedisDataExists->Error) {

                $CityResp = $this->curlservice->retrieve(getenv('COUNTRY_API_URL') . '/countries/' . $CountryCode . '/cities', 'GET', [], array('X-CSCAPI-KEY: ' . getenv('COUNTRY_API_KEY')));
                if ($CityResp->Error === false && sizeof($CityResp->Data) > 0) {
                    $this->EndReturnData->Data = $CityResp->Data;
                    $this->cacheservice->set(getSiteConfiguration()->RedisName . '-cityinfo-' . $CountryCode, json_encode($CityResp->Data), 43200 * 365);
                } else {
                    throw new Exception($CityResp->Message);
                }
            } else {

                $RedisCityInfo = $this->cacheservice->get(getSiteConfiguration()->RedisName . '-cityinfo-' . $CountryCode);
                if ($RedisCityInfo->Error === FALSE) {
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

    public function getPrimaryUnitInfo()
    {

        $this->EndReturnData = new stdClass();
        try {

            $PriUnitRedisDataExists = $this->cacheservice->exists(getSiteConfiguration()->RedisName . '-primaryunitinfo');
            if ($PriUnitRedisDataExists->Error) {

                $this->GlobalDb->db_debug = FALSE;

                $WhereCondition = array(
                    'PrimaryUnit.IsDeleted' => 0,
                    'PrimaryUnit.IsActive' => 1,
                );

                $select_ary = array(
                    'PrimaryUnit.PrimaryUnitUID AS PrimaryUnitUID',
                    'PrimaryUnit.OrgUID AS OrgUID',
                    'PrimaryUnit.Name AS Name',
                    'PrimaryUnit.ShortName AS ShortName',
                    'PrimaryUnit.Description AS Description',
                    'PrimaryUnit.UpdatedOn as UpdatedOn',
                );
                $this->GlobalDb->select($select_ary);
                $this->GlobalDb->from('Global.PrimaryUnitTbl as PrimaryUnit');
                $this->GlobalDb->where($WhereCondition);
                $this->GlobalDb->group_by('PrimaryUnit.PrimaryUnitUID');
                $this->GlobalDb->order_by('PrimaryUnit.Sorting', 'ASC');
                $query = $this->GlobalDb->get();
                $error = $this->GlobalDb->error();
                if ($error['code']) {
                    throw new Exception($error['message']);
                } else {
                    $this->EndReturnData->Data = $query->result();
                }


                $this->cacheservice->set(getSiteConfiguration()->RedisName . '-primaryunitinfo', json_encode($this->EndReturnData->Data), 43200 * 365);
            } else {

                $RedisPrimaryUnitInfo = $this->cacheservice->get(getSiteConfiguration()->RedisName . '-primaryunitinfo');
                if ($RedisPrimaryUnitInfo->Error === FALSE) {
                    $this->EndReturnData->Data = json_decode($RedisPrimaryUnitInfo->Value, TRUE);
                } else {
                    throw new Exception($RedisPrimaryUnitInfo->Message);
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

    public function getDiscountTypeInfo()
    {

        $this->EndReturnData = new stdClass();
        try {

            $DisTypeRedisDataExists = $this->cacheservice->exists(getSiteConfiguration()->RedisName . '-disctypeinfo');
            if ($DisTypeRedisDataExists->Error) {

                $this->GlobalDb->db_debug = FALSE;

                $WhereCondition = array(
                    'DiscType.IsDeleted' => 0,
                    'DiscType.IsActive' => 1,
                );

                $select_ary = array(
                    'DiscType.DiscountTypeUID AS DiscountTypeUID',
                    'DiscType.Name AS Name',
                    'DiscType.DisplayName AS DisplayName',
                    'DiscType.UpdatedOn as UpdatedOn',
                );
                $this->GlobalDb->select($select_ary);
                $this->GlobalDb->from('Global.DiscountTypeTbl as DiscType');
                $this->GlobalDb->where($WhereCondition);
                $this->GlobalDb->group_by('DiscType.DiscountTypeUID');
                $this->GlobalDb->order_by('DiscType.Sorting', 'ASC');
                $query = $this->GlobalDb->get();
                $error = $this->GlobalDb->error();
                if ($error['code']) {
                    throw new Exception($error['message']);
                } else {
                    $this->EndReturnData->Data = $query->result();
                }

                $this->cacheservice->set(getSiteConfiguration()->RedisName . '-disctypeinfo', json_encode($this->EndReturnData->Data), 43200 * 365);
            } else {

                $RedisDiscTypeInfo = $this->cacheservice->get(getSiteConfiguration()->RedisName . '-disctypeinfo');
                if ($RedisDiscTypeInfo->Error === FALSE) {
                    $this->EndReturnData->Data = json_decode($RedisDiscTypeInfo->Value, TRUE);
                } else {
                    throw new Exception($RedisDiscTypeInfo->Message);
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

    public function getProductTypeInfo()
    {

        $this->EndReturnData = new stdClass();
        try {

            $ProdTypeRedisDataExists = $this->cacheservice->exists(getSiteConfiguration()->RedisName . '-prodtypeinfo');
            if ($ProdTypeRedisDataExists->Error) {

                $this->GlobalDb->db_debug = FALSE;

                $WhereCondition = array(
                    'ProdType.IsDeleted' => 0,
                    'ProdType.IsActive' => 1,
                );

                $select_ary = array(
                    'ProdType.ProductTypeUID AS ProductTypeUID',
                    'ProdType.Name AS Name',
                    'ProdType.UpdatedOn as UpdatedOn',
                );
                $this->GlobalDb->select($select_ary);
                $this->GlobalDb->from('Global.ProductTypeTbl as ProdType');
                $this->GlobalDb->where($WhereCondition);
                $this->GlobalDb->group_by('ProdType.ProductTypeUID');
                $this->GlobalDb->order_by('ProdType.Sorting', 'ASC');
                $query = $this->GlobalDb->get();
                $error = $this->GlobalDb->error();
                if ($error['code']) {
                    throw new Exception($error['message']);
                } else {
                    $this->EndReturnData->Data = $query->result();
                }

                $this->cacheservice->set(getSiteConfiguration()->RedisName . '-prodtypeinfo', json_encode($this->EndReturnData->Data), 43200 * 365);
            } else {

                $RedisProdTypeInfo = $this->cacheservice->get(getSiteConfiguration()->RedisName . '-prodtypeinfo');
                if ($RedisProdTypeInfo->Error === FALSE) {
                    $this->EndReturnData->Data = json_decode($RedisProdTypeInfo->Value, TRUE);
                } else {
                    throw new Exception($RedisProdTypeInfo->Message);
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

    public function getProductTaxInfo()
    {

        $this->EndReturnData = new stdClass();
        try {

            $ProdTaxRedisDataExists = $this->cacheservice->exists(getSiteConfiguration()->RedisName . '-prodtaxinfo');
            if ($ProdTaxRedisDataExists->Error) {

                $this->GlobalDb->db_debug = FALSE;

                $WhereCondition = array(
                    'ProdTax.IsDeleted' => 0,
                    'ProdTax.IsActive' => 1,
                );

                $select_ary = array(
                    'ProdTax.ProductTaxUID AS ProductTaxUID',
                    'ProdTax.Name AS Name',
                    'ProdTax.UpdatedOn as UpdatedOn',
                );
                $this->GlobalDb->select($select_ary);
                $this->GlobalDb->from('Global.ProductTaxTbl as ProdTax');
                $this->GlobalDb->where($WhereCondition);
                $this->GlobalDb->group_by('ProdTax.ProductTaxUID');
                $this->GlobalDb->order_by('ProdTax.Sorting', 'ASC');
                $query = $this->GlobalDb->get();
                $error = $this->GlobalDb->error();
                if ($error['code']) {
                    throw new Exception($error['message']);
                } else {
                    $this->EndReturnData->Data = $query->result();
                }

                $this->cacheservice->set(getSiteConfiguration()->RedisName . '-prodtaxinfo', json_encode($this->EndReturnData->Data), 43200 * 365);
            } else {

                $RedisProdTaxInfo = $this->cacheservice->get(getSiteConfiguration()->RedisName . '-prodtaxinfo');
                if ($RedisProdTaxInfo->Error === FALSE) {
                    $this->EndReturnData->Data = json_decode($RedisProdTaxInfo->Value, TRUE);
                } else {
                    throw new Exception($RedisProdTaxInfo->Message);
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

    public function getTaxDetailsInfo()
    {

        $this->EndReturnData = new stdClass();
        try {

            $TaxDetailRedisDataExists = $this->cacheservice->exists(getSiteConfiguration()->RedisName . '-taxdetailsinfo');
            if ($TaxDetailRedisDataExists->Error) {

                $this->GlobalDb->db_debug = FALSE;

                $WhereCondition = array(
                    'TaxDetail.IsDeleted' => 0,
                    'TaxDetail.IsActive' => 1,
                );

                $select_ary = array(
                    'TaxDetail.TaxDetailsUID AS TaxDetailsUID',
                    'TaxDetail.TaxName AS TaxName',
                    'TaxDetail.Percentage AS Percentage',
                    'TaxDetail.CGST AS CGST',
                    'TaxDetail.SGST AS SGST',
                    'TaxDetail.IGST AS IGST',
                    'TaxDetail.UpdatedOn as UpdatedOn',
                );
                $this->GlobalDb->select($select_ary);
                $this->GlobalDb->from('Global.TaxDetailsTbl as TaxDetail');
                $this->GlobalDb->where($WhereCondition);
                $this->GlobalDb->group_by('TaxDetail.TaxDetailsUID');
                $this->GlobalDb->order_by('TaxDetail.Sorting', 'ASC');
                $query = $this->GlobalDb->get();
                $error = $this->GlobalDb->error();
                if ($error['code']) {
                    throw new Exception($error['message']);
                } else {
                    $this->EndReturnData->Data = $query->result();
                }

                $this->cacheservice->set(getSiteConfiguration()->RedisName . '-taxdetailsinfo', json_encode($this->EndReturnData->Data), 43200 * 365);
            } else {

                $RedisTaxDetailInfo = $this->cacheservice->get(getSiteConfiguration()->RedisName . '-taxdetailsinfo');
                if ($RedisTaxDetailInfo->Error === FALSE) {
                    $this->EndReturnData->Data = json_decode($RedisTaxDetailInfo->Value, TRUE);
                } else {
                    throw new Exception($RedisTaxDetailInfo->Message);
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

    public function getTaxPercentageDetailsInfo($WhereArrayCondition)
    {

        $this->EndReturnData = new stdClass();
        try {

            $this->GlobalDb->db_debug = FALSE;

            $WhereCondition = array(
                'TaxDetail.IsDeleted' => 0,
                'TaxDetail.IsActive' => 1,
            );

            $select_ary = array(
                'TaxDetail.TaxDetailsUID AS TaxDetailsUID',
                'TaxDetail.TaxName AS TaxName',
                'TaxDetail.Percentage AS Percentage',
                'TaxDetail.CGST AS CGST',
                'TaxDetail.SGST AS SGST',
                'TaxDetail.IGST AS IGST',
                'TaxDetail.UpdatedOn as UpdatedOn',
            );
            $this->GlobalDb->select($select_ary);
            $this->GlobalDb->from('Global.TaxDetailsTbl as TaxDetail');
            $this->GlobalDb->where($WhereCondition);
            if (sizeof($WhereArrayCondition) > 0) {
                $this->GlobalDb->where($WhereArrayCondition);
            }
            $this->GlobalDb->group_by('TaxDetail.TaxDetailsUID');
            $this->GlobalDb->order_by('TaxDetail.Sorting', 'ASC');
            $query = $this->GlobalDb->get();
            $error = $this->GlobalDb->error();
            if ($error['code']) {
                throw new Exception($error['message']);
            } else {
                $this->EndReturnData->Data = $query->result();
            }

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Data Retrieved Successfully';
        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        return $this->EndReturnData;
    }

    public function getModuleViewColumnDetails($WhereArrayCondition, $Sorting = false, $SortingColumn = [])
    {

        $this->EndReturnData = new stdClass();
        try {

            $this->ModuleDb->db_debug = FALSE;

            $WhereCondition = array(
                'ViewColmn.IsDeleted' => 0,
                'ViewColmn.IsActive' => 1,
            );

            $select_ary = array(
                'ViewColmn.ViewDataUID AS ViewDataUID',
                'ViewColmn.OrgUID AS OrgUID',
                'ViewColmn.ModuleUID AS ModuleUID',
                'ViewColmn.SubModuleUID AS SubModuleUID',
                'ViewColmn.DisplayName AS DisplayName',
                'ViewColmn.FieldName AS FieldName',
                'ViewColmn.DbFieldName AS DbFieldName',
            );
            $this->ModuleDb->select($select_ary);
            $this->ModuleDb->from('Modules.ViewDataTbl as ViewColmn');
            $this->ModuleDb->where($WhereCondition);
            if (sizeof($WhereArrayCondition) > 0) {
                $this->ModuleDb->where($WhereArrayCondition);
            }
            $this->ModuleDb->group_by('ViewColmn.ViewDataUID');
            if ($Sorting) {
                $this->ModuleDb->order_by(key($SortingColumn), $SortingColumn[key($SortingColumn)]);
            }
            $query = $this->ModuleDb->get();
            $error = $this->ModuleDb->error();
            if ($error['code']) {
                throw new Exception($error['message']);
            } else {
                $this->EndReturnData->Data = $query->result();
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
