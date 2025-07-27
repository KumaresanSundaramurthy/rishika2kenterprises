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

    public function getStorageTypeData() {

        $this->EndReturnData = new stdClass();
        try {

            $StorageTypeRedisDataExists = $this->cacheservice->exists(getSiteConfiguration()->RedisName . '-storagetypeinfo');
            if ($StorageTypeRedisDataExists->Error) {

                $this->GlobalDb->db_debug = FALSE;

                $WhereCondition = array(
                    'StorageType.IsDeleted' => 0,
                    'StorageType.IsActive' => 1,
                );

                $select_ary = array(
                    'StorageType.StorageTypeUID AS StorageTypeUID',
                    'StorageType.Name AS Name',
                    'StorageType.UpdatedOn as UpdatedOn',
                );
                $this->GlobalDb->select($select_ary);
                $this->GlobalDb->from('Global.StorageTypeTbl as StorageType');
                $this->GlobalDb->where($WhereCondition);
                $this->GlobalDb->group_by('StorageType.StorageTypeUID');
                $this->GlobalDb->order_by('StorageType.Sorting', 'ASC');
                $query = $this->GlobalDb->get();
                $error = $this->GlobalDb->error();
                if ($error['code']) {
                    throw new Exception($error['message']);
                } else {
                    $this->EndReturnData->Data = $query->result();
                }

                $this->cacheservice->set(getSiteConfiguration()->RedisName . '-storagetypeinfo', json_encode($this->EndReturnData->Data), 43200 * 365);
            } else {

                $RedisStrgTypeInfo = $this->cacheservice->get(getSiteConfiguration()->RedisName . '-storagetypeinfo');
                if ($RedisStrgTypeInfo->Error === FALSE) {
                    $this->EndReturnData->Data = json_decode($RedisStrgTypeInfo->Value, TRUE);
                } else {
                    throw new Exception($RedisStrgTypeInfo->Message);
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

    public function getModuleDetails($WhereCond = [], $whereInCondition = [])
    {

        $this->EndReturnData = new StdClass();
        try {

            $this->GlobalDb->db_debug = FALSE;

            $select_ary = array(
                'Modules.ModuleUID AS ModuleUID',
                'Modules.Name AS Name',
                'Modules.OrgUID AS OrgUID',
                'Modules.MainMenuUID AS MainMenuUID',
                'Modules.SubMenuUID AS SubMenuUID',
                'Modules.ControllerName AS ControllerName',
                'Modules.ModelName AS ModelName',
                'Modules.FilterFunctionName AS FilterFunctionName',
                'Modules.DatabaseName as DatabaseName',
                'Modules.MasterTableName as MasterTableName',
                'Modules.TableAliasName as TableAliasName',
                'Modules.TablePrimaryUID as TablePrimaryUID',
                'Modules.ParentModuleUID as ParentModuleUID',
                'Modules.IsMainModule as IsMainModule',
                'Modules.IsModuleEnabled as IsModuleEnabled',
            );
            $WhereCondition = array(
                'Modules.IsDeleted' => 0,
                'Modules.IsActive' => 1,
            );
            $this->GlobalDb->select($select_ary);
            $this->GlobalDb->from('Modules.ModuleTbl as Modules');
            $this->GlobalDb->where($WhereCondition);
            if (!empty($WhereCond)) {
                $this->GlobalDb->where($WhereCond);
            }
            if (!empty($whereInCondition)) {
                foreach ($whereInCondition as $wkey => $wval) {
                    $this->GlobalDb->where_in($wkey, $wval);
                }
            }
            $this->GlobalDb->group_by('Modules.ModuleUID');

            $query = $this->GlobalDb->get();
            $error = $this->GlobalDb->error();
            if ($error['code']) {
                throw new Exception($error['message']);
            } else {
                $this->EndReturnData->Data = $query->result();
            }

            return $this->EndReturnData->Data;
        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);
        }
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
                'ViewColmn.DbFieldNameAddOn AS DbFieldNameAddOn',
                'ViewColmn.IsDateField AS IsDateField',
                'ViewColmn.IsAmountField AS IsAmountField',
                'ViewColmn.CurrencySymbol AS CurrencySymbol',
                'ViewColmn.AggregationMethod AS AggregationMethod',
                'ViewColmn.MainPageImageDisplay AS MainPageImageDisplay',
                'ViewColmn.IsMainPageApplicable AS IsMainPageApplicable',
                'ViewColmn.IsMainPageRequired AS IsMainPageRequired',
                'ViewColmn.MainPageOrder AS MainPageOrder',
                'ViewColmn.MainPageColumnAddon AS MainPageColumnAddon',
                'ViewColmn.MainPageDataAddon AS MainPageDataAddon',
                'ViewColmn.MPDateFormatType AS MPDateFormatType',
                'ViewColmn.IsPrintPreviewApplicable AS IsPrintPreviewApplicable',
                'ViewColmn.PrintPreviewOrder AS PrintPreviewOrder',
                'ViewColmn.IsExportCsvApplicable AS IsExportCsvApplicable',
                'ViewColmn.ExportCsvOrder AS ExportCsvOrder',
                'ViewColmn.IsExportPdfApplicable AS IsExportPdfApplicable',
                'ViewColmn.ExportPdfOrder AS ExportPdfOrder',
                'ViewColmn.IsExportExcelApplicable AS IsExportExcelApplicable',
                'ViewColmn.ExportExcelOrder AS ExportExcelOrder',
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

            // print_r($this->ModuleDb->get_compiled_select()); die();

            $query = $this->ModuleDb->get();
            $error = $this->ModuleDb->error();
            if ($error['code']) {
                throw new Exception($error['message']);
            } else {
                $this->EndReturnData->Data = $query->result();
            }

            return $this->EndReturnData->Data;
        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);
        }
    }

    public function getModuleViewJoinColumnDetails($WhereArrayCondition, $Sorting = false, $SortingColumn = [])
    {

        $this->EndReturnData = new stdClass();
        try {

            $this->ModuleDb->db_debug = FALSE;

            $WhereCondition = array(
                'JoinColmn.IsDeleted' => 0,
                'JoinColmn.IsActive' => 1,
            );

            $select_ary = array(
                'JoinColmn.ViewDataJoinUID AS ViewDataJoinUID',
                'JoinColmn.OrgUID AS OrgUID',
                'JoinColmn.MainModuleUID AS MainModuleUID',
                'JoinColmn.MainTblAliasName AS MainTblAliasName',
                'JoinColmn.MainTblFieldName AS MainTblFieldName',
                'JoinColmn.JoinModuleUID AS JoinModuleUID',
                'JoinColmn.JoinTblAliasName AS JoinTblAliasName',
                'JoinColmn.JoinTblFieldName AS JoinTblFieldName',
                'JoinColmn.JoinLookupUID AS JoinLookupUID',
                'JoinColmn.JoinLookupTblAliasName AS JoinLookupTblAliasName',
                'JoinColmn.JoinLookupTblFieldName AS JoinLookupTblFieldName',
                'JoinColmn.JoinType AS JoinType',
                'JoinColmn.JoinBasicCheck AS JoinBasicCheck',
                'JoinColmn.JoinColumnsAddon AS JoinColumnsAddon',
                'JoinColmn.JoinQuery AS JoinQuery',
                'JoinColmn.IsMandatory AS IsMandatory',
                'Module.DatabaseName AS DatabaseName',
                'JoinModule.DatabaseName AS JoinDatabaseName',
                'JoinModule.MasterTableName AS JoinTableName',
                'Lookup.DatabaseName AS LkupDatabaseName',
                'Lookup.TableName AS LkupTableName',
            );
            $this->ModuleDb->select($select_ary);
            $this->ModuleDb->from('Modules.ViewDataJoinTbl as JoinColmn');
            $this->ModuleDb->join('Modules.ModuleTbl as Module', 'Module.ModuleUID = JoinColmn.MainModuleUID AND Module.IsDeleted = 0 AND Module.IsActive = 1', 'LEFT');
            $this->ModuleDb->join('Modules.ModuleTbl as JoinModule', 'JoinModule.ModuleUID = JoinColmn.JoinModuleUID AND JoinModule.IsDeleted = 0 AND JoinModule.IsActive = 1', 'LEFT');
            $this->ModuleDb->join('Modules.LookupTbl as Lookup', 'Lookup.LookupUID = JoinColmn.JoinLookupUID AND Lookup.IsDeleted = 0 AND Lookup.IsActive = 1', 'LEFT');
            $this->ModuleDb->where($WhereCondition);
            if (sizeof($WhereArrayCondition) > 0) {
                $this->ModuleDb->where($WhereArrayCondition);
            }
            $this->ModuleDb->group_by('JoinColmn.ViewDataJoinUID');
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

            return $this->EndReturnData->Data;
        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);
        }
    }

    public function getModuleReportDetails($ModuleInfo, $SelectColumns, $JoinDataArr = [], $FilterArray = [], $DirectQuery = '', $OrderBy = 'ASC', $whereInCondition = [], $Limit = 0, $Offset = 0, $Flag = 0)
    {

        $this->EndReturnData = new StdClass();
        try {

            $this->ModuleDb->db_debug = FALSE;

            $getUnqJoinTable = array_values(array_unique(array_filter(
                array_map(fn($f) => explode('.', $f)[0] ?? null, array_column($SelectColumns, 'DbFieldName')),
                fn($p) => $p && $p !== $ModuleInfo->TableAliasName
            )));

            if ($Flag == 0) {

                $selectFields = [];
                $selectFields[] = "{$ModuleInfo->TableAliasName}.{$ModuleInfo->TablePrimaryUID} AS TablePrimaryUID";
                foreach ($SelectColumns as $index => $column) {
                    $DisplayName = preg_replace('/[^a-zA-Z0-9_ ]/', '', $column->DisplayName);
                    $fieldName = !empty($column->DbFieldNameAddOn) ? $column->DbFieldNameAddOn : $column->DbFieldName;
                    $selectFields[] = "{$fieldName} AS '{$DisplayName}'";
                }
                $this->ModuleDb->select(implode(", ", $selectFields));
            } else if ($Flag == 1) {

                $this->ModuleDb->select($ModuleInfo->TableAliasName . '.' . $ModuleInfo->TablePrimaryUID);
            }

            $this->ModuleDb->from("{$ModuleInfo->DatabaseName}.{$ModuleInfo->MasterTableName} as {$ModuleInfo->TableAliasName}");
            if (!empty($JoinDataArr)) {
                foreach ($JoinDataArr as $join) {
                    $alias = $join->JoinLookupTblAliasName ?? $join->JoinTblAliasName;
                    $valid = in_array($alias, $getUnqJoinTable) || $join->IsMandatory == 1;

                    if (!$valid) continue;

                    $joinTable = $join->JoinLookupTblAliasName ? $join->LkupDatabaseName . '.' . $join->LkupTableName : $join->JoinDatabaseName . '.' . $join->JoinTableName;
                    $joinField = $join->JoinLookupTblFieldName ?? $join->JoinTblFieldName;
                    $joinAlias = $alias;
                    $joinCondition = $joinAlias . '.' . $joinField.' = '.$join->MainTblAliasName.'.'.$join->MainTblFieldName;
                    if($join->JoinBasicCheck) {
                        $joinCondition .= " AND ($joinAlias.IsDeleted = 0 AND $joinAlias.IsActive = 1) ";
                    }
                    $joinCondition .= $join->JoinColumnsAddon;
                    $joinType = $join->JoinType;

                    if ($joinAlias && ($join->JoinLookupUID > 0 || $join->JoinModuleUID > 0)) {
                        $this->ModuleDb->join("$joinTable as $joinAlias", $joinCondition, $joinType);
                    }
                }
            }
            $this->ModuleDb->where([
                "{$ModuleInfo->TableAliasName}.IsDeleted" => 0,
                "{$ModuleInfo->TableAliasName}.IsActive" => 1
            ]);
            if (!empty($FilterArray)) {
                $this->ModuleDb->where($FilterArray);
            }
            if (!empty($DirectQuery)) {
                $this->ModuleDb->where($DirectQuery);
            }
            if (!empty($whereInCondition)) {
                foreach ($whereInCondition as $wkey => $wval) {
                    if (!empty($wval)) {
                        $this->ModuleDb->where_in($wkey, $wval);
                    }
                }
            }
            $this->ModuleDb->group_by($ModuleInfo->TableAliasName . '.' . $ModuleInfo->TablePrimaryUID);
            $this->ModuleDb->order_by($ModuleInfo->TableAliasName . '.' . $ModuleInfo->TablePrimaryUID, $OrderBy);
            if ($Flag == 0 && $Limit > 0) {
                $this->ModuleDb->limit($Limit, $Offset);
            }

            // print_r($this->ModuleDb->get_compiled_select()); die();

            $query = $this->ModuleDb->get();
            $error = $this->ModuleDb->error();
            if ($error['code']) {
                throw new Exception($error['message']);
            }

            $this->EndReturnData->Data = $query->result();
            return $this->EndReturnData->Data;
        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);
        }
    }
    
}
