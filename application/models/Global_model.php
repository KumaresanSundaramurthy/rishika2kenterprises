<?php defined('BASEPATH') or exit('No direct script access allowed');

class Global_model extends CI_Model {

    private $EndReturnData;
    private $ReadDb;
    private $GlbCountryKey;
    private $PrimUnitKey;
    private $DiscTypeKey;
    private $ProdTypeKey;
    private $ProdTaxKey;
    private $ProdDetKey;
    private $TaxPerDetKey;
    private $StrgTypeKey;

    function __construct() {
        parent::__construct();

        $this->ReadDb = $this->load->database('ReadDB', TRUE);

        $this->GlbCountryKey = getSiteConfiguration()->RedisName.getenv('REDIS_STATICKEY').'-Glb_CountryInfo';
        $this->PrimUnitKey = getSiteConfiguration()->RedisName.getenv('REDIS_STATICKEY').'-primaryunitinfo';
        $this->DiscTypeKey = getSiteConfiguration()->RedisName .getenv('REDIS_STATICKEY'). '-disctypeinfo';
        $this->ProdTypeKey = getSiteConfiguration()->RedisName .getenv('REDIS_STATICKEY'). '-prodtypeinfo';
        $this->ProdTaxKey = getSiteConfiguration()->RedisName .getenv('REDIS_STATICKEY'). '-prodtaxinfo';
        $this->ProdDetKey = getSiteConfiguration()->RedisName .getenv('REDIS_STATICKEY'). '-taxdetailsinfo';
        $this->TaxPerDetKey = getSiteConfiguration()->RedisName .getenv('REDIS_STATICKEY'). '-taxperdetinfo';
        $this->StrgTypeKey = getSiteConfiguration()->RedisName .getenv('REDIS_STATICKEY'). '-storagetypeinfo';
        
    }

    public function getTimezoneDetails($FilterArray) {

        $this->EndReturnData = new stdClass();
        try {

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
            if (sizeof($FilterArray) > 0) {
                $this->ReadDb->where($FilterArray);
            }
            $query = $this->ReadDb->get();
            if (!$query) {
                $error = $this->ReadDb->error();
                throw new Exception($error['message']);
            }

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Success';
            $this->EndReturnData->Data = $query->result();

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        return $this->EndReturnData;

    }

    public function getCountryInfo() {

        $this->EndReturnData = new stdClass();
        try {
            
            $GCKey = $this->GlbCountryKey;
            $GCGet_Data = $this->redis_cache->get($GCKey);
            if ($GCGet_Data->Error) {

                $this->load->library('curlservice');

                $CountryResp = $this->curlservice->retrieve(getenv('CFLARE_R2_CDN') . '/Global/countrydetails.json', 'GET', []);
                
                $Countries = $CountryResp->Data;
                usort($Countries, function ($a, $b) {
                    return strcmp($a['name'], $b['name']);
                });

                $this->EndReturnData->Data = $Countries;

                $this->redis_cache->set($GCKey, $this->EndReturnData->Data, getenv('ONEYEAR_EXPIRE_SECS'));

            } else {
                $this->EndReturnData->Data = $GCGet_Data->Value;
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

            $GlbStateKey = getSiteConfiguration()->RedisName.getenv('REDIS_STATICKEY')."-Glb_StateInfo-".$CountryCode;
            $StateInfo = $this->redis_cache->get($GlbStateKey);
            if ($StateInfo->Error) {

                $this->load->library('curlservice');

                $StateResp = $this->curlservice->retrieve(getenv('COUNTRY_API_URL') . '/countries/' . $CountryCode . '/states', 'GET', [], array('X-CSCAPI-KEY: ' . getenv('COUNTRY_API_KEY')));
                if ($StateResp->Error === false && sizeof($StateResp->Data) > 0) {
                    $this->EndReturnData->Data = $StateResp->Data;
                } else {
                    throw new Exception($StateResp->Message);
                }

                $this->redis_cache->set($GlbStateKey, $this->EndReturnData->Data, getenv('ONEYEAR_EXPIRE_SECS'));

            } else {
                $this->EndReturnData->Data = $StateInfo->Value;
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

            $GlbCityKey = getSiteConfiguration()->RedisName.getenv('REDIS_STATICKEY')."Glb_CityInfo-".$CountryCode;
            $CityInfo = $this->redis_cache->get($GlbCityKey);
            if ($CityInfo->Error) {

                $CityResp = $this->curlservice->retrieve(getenv('COUNTRY_API_URL') . '/countries/' . $CountryCode . '/cities', 'GET', [], array('X-CSCAPI-KEY: ' . getenv('COUNTRY_API_KEY')));
                if ($CityResp->Error === false && sizeof($CityResp->Data) > 0) {
                    $this->EndReturnData->Data = $CityResp->Data;
                } else {
                    throw new Exception($CityResp->Message);
                }

                $this->redis_cache->set($GlbCityKey, $this->EndReturnData->Data, getenv('ONEYEAR_EXPIRE_SECS'));

            } else {
                $this->EndReturnData->Data = $CityInfo->Value;
            }

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Data Retrieved Successfully';

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        return $this->EndReturnData;

    }

    public function getPrimaryUnitInfo() {

        $this->EndReturnData = new stdClass();
        try {
            
            $PUIKey = $this->PrimUnitKey;
            $PUIGet_Data = $this->redis_cache->get($PUIKey);
            if ($PUIGet_Data->Error) {

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

                $this->redis_cache->set($PUIKey, $this->EndReturnData->Data, getenv('ONEYEAR_EXPIRE_SECS'));

            } else {
                $this->EndReturnData->Data = $PUIGet_Data->Value;
            }

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Data Retrieved Successfully';

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        return $this->EndReturnData;

    }

    public function getDiscountTypeInfo() {

        $this->EndReturnData = new stdClass();
        try {

            $DTIKey = $this->DiscTypeKey;
            $DTIGet_Data = $this->redis_cache->get($DTIKey);
            if ($DTIGet_Data->Error) {

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

                $this->redis_cache->set($DTIKey, $this->EndReturnData->Data, getenv('ONEYEAR_EXPIRE_SECS'));

            } else {
                $this->EndReturnData->Data = $DTIGet_Data->Value;
            }

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Data Retrieved Successfully';

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        return $this->EndReturnData;

    }

    public function getProductTypeInfo() {

        $this->EndReturnData = new stdClass();
        try {
            
            $PTIKey = $this->ProdTypeKey;
            $PTIGet_Data = $this->redis_cache->get($PTIKey);
            if ($PTIGet_Data->Error) {

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

                $this->redis_cache->set($PTIKey, $this->EndReturnData->Data, getenv('ONEYEAR_EXPIRE_SECS'));

            } else {
                $this->EndReturnData->Data = $PTIGet_Data->Value;
            }

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Data Retrieved Successfully';

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        return $this->EndReturnData;

    }

    public function getProductTaxInfo() {

        $this->EndReturnData = new stdClass();
        try {

            $PTIKey = $this->ProdTaxKey;
            $PTIGet_Data = $this->redis_cache->get($PTIKey);
            if ($PTIGet_Data->Error) {

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

                $this->redis_cache->set($PTIKey, $this->EndReturnData->Data, getenv('ONEYEAR_EXPIRE_SECS'));

            } else {
                $this->EndReturnData->Data = $PTIGet_Data->Value;
            }

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Data Retrieved Successfully';

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        return $this->EndReturnData;

    }

    public function getTaxDetailsInfo() {

        $this->EndReturnData = new stdClass();
        try {
            
            $TDIKey = $this->ProdDetKey;
            $TDIGet_Data = $this->redis_cache->get($TDIKey);
            if ($TDIGet_Data->Error) {

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

                $this->redis_cache->set($TDIKey, $this->EndReturnData->Data, getenv('ONEYEAR_EXPIRE_SECS'));

            } else {
                $this->EndReturnData->Data = $TDIGet_Data->Value;
            }

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Data Retrieved Successfully';

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        return $this->EndReturnData;

    }

    public function getTaxPercentageDetailsInfo($WhereArrayCondition) {

        $this->EndReturnData = new stdClass();
        try {
            
            $TPDIKey = $this->TaxPerDetKey;
            $TPDIGet_Data = $this->redis_cache->get($TPDIKey);
            if ($TPDIGet_Data->Error) {

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
                if (sizeof($WhereArrayCondition) > 0) {
                    $this->ReadDb->where($WhereArrayCondition);
                }
                $this->ReadDb->order_by('TaxDetail.Sorting', 'ASC');
                $query = $this->ReadDb->get();
                if (!$query) {
                    $error = $this->ReadDb->error();
                    throw new Exception($error['message']);
                }

                $this->EndReturnData->Data = $query->result();

                $this->redis_cache->set($TPDIKey, $this->EndReturnData->Data, getenv('ONEYEAR_EXPIRE_SECS'));

            } else {
                $this->EndReturnData->Data = $TPDIGet_Data->Value;
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
            
            $STRDEKey = $this->StrgTypeKey;
            $STRDEGet_Data = $this->redis_cache->get($STRDEKey);
            if ($STRDEGet_Data->Error) {

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

                $this->redis_cache->set($STRDEKey, $this->EndReturnData->Data, getenv('ONEYEAR_EXPIRE_SECS'));

            } else {
                $this->EndReturnData->Data = $STRDEGet_Data->Value;
            }

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Data Retrieved Successfully';
        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        return $this->EndReturnData;

    }

    public function getModuleDetails($WhereCond = [], $whereInCondition = []) {

        $this->EndReturnData = new StdClass();
        try {

            $params_hash = md5(json_encode(['WC' => $WhereCond, 'WIC' => $whereInCondition]));
            $RedisName = getSiteConfiguration()->RedisName.'-getModuleDetails'.$params_hash;
            $ModDataRedis = $this->cacheservice->get($RedisName);
            if ($ModDataRedis->Error) {

                $this->ReadDb->db_debug = FALSE;
                $this->ReadDb->select([
                    'Modules.ModuleUID AS ModuleUID',
                    'Modules.Name AS Name',
                    'Modules.OrgUID AS OrgUID',
                    'Modules.MainMenuUID AS MainMenuUID',
                    'Modules.SubMenuUID AS SubMenuUID',
                    'Modules.ControllerName AS ControllerName',
                    'Modules.ModelName AS ModelName',
                    'Modules.FilterFunctionName AS FilterFunctionName',
                    'Modules.ListUrl AS ListUrl',
                    'Modules.DatabaseName as DatabaseName',
                    'Modules.MasterTableName as MasterTableName',
                    'Modules.TableAliasName as TableAliasName',
                    'Modules.TablePrimaryUID as TablePrimaryUID',
                    'Modules.ParentModuleUID as ParentModuleUID',
                    'Modules.IsMainModule as IsMainModule',
                    'Modules.IsModuleEnabled as IsModuleEnabled',
                    'Modules.EditOnPage as EditOnPage',
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
                $this->cacheservice->set($RedisName, $this->EndReturnData->Data, getenv('ONEMONTH_EXPIRE_SECS'));

                return $this->EndReturnData->Data;

            } else {
                $this->EndReturnData->Data = $ModDataRedis->Value;
                return $this->EndReturnData->Data;
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);
        }

    }

    public function getModuleViewColumnDetails($WhereArrayCondition, $Sorting = false, $SortingColumn = []) {

        $this->EndReturnData = new stdClass();
        try {

            // $params_hash = md5(json_encode(['WAC' => $WhereArrayCondition, 'SRT' => $Sorting, 'SRTCLMN' => $SortingColumn]));
            // $RedisName = getSiteConfiguration()->RedisName.'-getModuleViewColumnDetails'.$params_hash;
            // $ModColumnDataRedis = $this->cacheservice->get($RedisName);
            // if ($ModColumnDataRedis->Error) {

                $this->ReadDb->db_debug = FALSE;
                $this->ReadDb->select([
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
                    'ViewColmn.IsMobileNumber AS IsMobileNumber',
                    'ViewColmn.CurrencySymbol AS CurrencySymbol',
                    'ViewColmn.AggregationMethod AS AggregationMethod',
                    'ViewColmn.MainPageImageDisplay AS MainPageImageDisplay',
                    'ViewColmn.IsMainPageApplicable AS IsMainPageApplicable',
                    'ViewColmn.IsMainPageSettingsApplicable AS IsMainPageSettingsApplicable',
                    'ViewColmn.IsMainPageRequired AS IsMainPageRequired',
                    'ViewColmn.MainPageOrder AS MainPageOrder',
                    'ViewColmn.MainPageColumnAddon AS MainPageColumnAddon',
                    'ViewColmn.MainPageDataAddon AS MainPageDataAddon',
                    'ViewColmn.MPFilterApplicable AS MPFilterApplicable',
                    'ViewColmn.MPSortApplicable AS MPSortApplicable',
                    'ViewColmn.MPDateFormatType AS MPDateFormatType',
                    'ViewColmn.IsPrintPreviewRequired AS IsPrintPreviewRequired',
                    'ViewColmn.IsPrintPreviewApplicable AS IsPrintPreviewApplicable',
                    'ViewColmn.PrintPreviewOrder AS PrintPreviewOrder',
                    'ViewColmn.IsExportRequired AS IsExportRequired',
                    'ViewColmn.IsExportCsvApplicable AS IsExportCsvApplicable',
                    'ViewColmn.ExportCsvOrder AS ExportCsvOrder',
                    'ViewColmn.IsExportPdfApplicable AS IsExportPdfApplicable',
                    'ViewColmn.ExportPdfOrder AS ExportPdfOrder',
                    'ViewColmn.IsExportExcelApplicable AS IsExportExcelApplicable',
                    'ViewColmn.ExportExcelOrder AS ExportExcelOrder',
                ]);
                $this->ReadDb->from('Modules.ViewDataTbl as ViewColmn');
                $this->ReadDb->where(['ViewColmn.IsDeleted' => 0, 'ViewColmn.IsActive' => 1]);
                if (sizeof($WhereArrayCondition) > 0) {
                    $this->ReadDb->where($WhereArrayCondition);
                }
                if ($Sorting) {
                    $this->ReadDb->order_by(key($SortingColumn), $SortingColumn[key($SortingColumn)]);
                }
                $query = $this->ReadDb->get();
                if (!$query) {
                    $error = $this->ReadDb->error();
                    throw new Exception($error['message']);
                }

                $this->EndReturnData->Data = $query->result();
                // $this->cacheservice->set($RedisName, $this->EndReturnData->Data, getenv('ONEMONTH_EXPIRE_SECS'));

                return $this->EndReturnData->Data;

            // } else {
            //     $this->EndReturnData->Data = $ModColumnDataRedis->Value;
            //     return $this->EndReturnData->Data;
            // }

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);
        }

    }

    public function getModuleViewJoinColumnDetails($WhereArrayCondition, $Sorting = false, $SortingColumn = []) {

        $this->EndReturnData = new stdClass();
        try {

            $RedisName = getSiteConfiguration()->RedisName.'-'.base64_encode(json_encode(['WC' => $WhereArrayCondition, 'Sort' => $Sorting, 'SortCol' => $SortingColumn])).'-getModuleViewJoinColumnDetails';
            $this->cacheservice->delete($RedisName);
            $ModViewJoinColRedis = $this->cacheservice->get($RedisName);
            if ($ModViewJoinColRedis->Error) {

                $this->ReadDb->db_debug = FALSE;
                $this->ReadDb->select([
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
                ]);
                $this->ReadDb->from('Modules.ViewDataJoinTbl as JoinColmn');
                $this->ReadDb->join('Modules.ModuleTbl as Module', 'Module.ModuleUID = JoinColmn.MainModuleUID AND Module.IsDeleted = 0 AND Module.IsActive = 1', 'LEFT');
                $this->ReadDb->join('Modules.ModuleTbl as JoinModule', 'JoinModule.ModuleUID = JoinColmn.JoinModuleUID AND JoinModule.IsDeleted = 0 AND JoinModule.IsActive = 1', 'LEFT');
                $this->ReadDb->join('Modules.LookupTbl as Lookup', 'Lookup.LookupUID = JoinColmn.JoinLookupUID AND Lookup.IsDeleted = 0 AND Lookup.IsActive = 1', 'LEFT');
                $this->ReadDb->where(['JoinColmn.IsDeleted' => 0, 'JoinColmn.IsActive' => 1]);
                if (sizeof($WhereArrayCondition) > 0) {
                    $this->ReadDb->where($WhereArrayCondition);
                }
                $this->ReadDb->group_by('JoinColmn.ViewDataJoinUID');
                if ($Sorting) {
                    $this->ReadDb->order_by(key($SortingColumn), $SortingColumn[key($SortingColumn)]);
                }
                $query = $this->ReadDb->get();
                if (!$query) {
                    $error = $this->ReadDb->error();
                    throw new Exception($error['message']);
                }

                $this->EndReturnData->Data = $query->result();

                $this->cacheservice->set($RedisName, $this->EndReturnData->Data, getenv('ONEYEAR_EXPIRE_SECS'));

                return $this->EndReturnData->Data;

            } else {
                
                $this->EndReturnData->Data = $ModViewJoinColRedis->Value;
                return $this->EndReturnData->Data;

            }

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($this->EndReturnData->Message);
        }
    }

    public function getModuleReportDetails($ModuleInfo, $SelectColumns, $JoinDataArr = [], $FilterArray = [], $DirectQuery = '', $OrderBy = 'ASC', $whereInCondition = [], $Limit = 0, $Offset = 0, $sortOperation = []) {

        $this->EndReturnData = new StdClass();
        try {

            $this->ReadDb->db_debug = FALSE;
            
            $rawFields = array_column($SelectColumns, 'DbFieldName');
            
            $aliases = [];
            foreach ($rawFields as $field) {
                $explode = explode('.', $field);
                if (isset($explode[0]) && $explode[0] !== $ModuleInfo->TableAliasName) {
                    $aliases[] = $explode[0];
                }
            }

            $getUnqJoinTable = array_unique($aliases);

            $selectFields = [];

            // Select primary UID
            $selectFields[] = "{$ModuleInfo->TableAliasName}.{$ModuleInfo->TablePrimaryUID} AS TablePrimaryUID";

            // Select module columns
            foreach ($SelectColumns as $col) {

                // Clean name — faster than regex
                $DisplayName = preg_replace('/[^A-Za-z0-9_ ]/', '', $col->DisplayName);

                // Use addon field if exists
                $fieldName = $col->DbFieldNameAddOn ?: $col->DbFieldName;

                $selectFields[] = "{$fieldName} AS `{$DisplayName}`";
            }

            $this->ReadDb->select($selectFields);
            
            $this->ReadDb->from("{$ModuleInfo->DatabaseName}.{$ModuleInfo->MasterTableName} AS {$ModuleInfo->TableAliasName}");
            
            if (!empty($JoinDataArr)) {
                foreach ($JoinDataArr as $join) {

                    // Determine alias
                    $alias = $join->JoinLookupTblAliasName ?? $join->JoinTblAliasName;

                    // Validate if join is needed
                    $isNeeded = ($join->IsMandatory == 1) || in_array($alias, $getUnqJoinTable);
                    if (!$isNeeded) continue;

                    // Resolve table name
                    $joinTable = $join->JoinLookupTblAliasName
                        ? "{$join->LkupDatabaseName}.{$join->LkupTableName}"
                        : "{$join->JoinDatabaseName}.{$join->JoinTableName}";

                    // Resolve field name
                    $joinField = $join->JoinLookupTblFieldName ?? $join->JoinTblFieldName;

                    // Build join condition
                    $joinCond = "{$alias}.{$joinField} = {$join->MainTblAliasName}.{$join->MainTblFieldName}";

                    // Add IsDeleted / IsActive if required
                    if ($join->JoinBasicCheck) {
                        $joinCond .= " AND {$alias}.IsDeleted = 0 AND {$alias}.IsActive = 1";
                    }

                    // Add additional join conditions
                    if (!empty($join->JoinColumnsAddon)) {
                        $joinCond .= " {$join->JoinColumnsAddon}";
                    }

                    // Perform join
                    $this->ReadDb->join("{$joinTable} AS {$alias}", $joinCond, $join->JoinType);

                }
            }
            
            $this->ReadDb->where(["{$ModuleInfo->TableAliasName}.IsDeleted" => 0, "{$ModuleInfo->TableAliasName}.IsActive"  => 1]);
            
            if (!empty($FilterArray)) {
                $this->ReadDb->where($FilterArray);
            }
            
            if (!empty($DirectQuery)) {
                $this->ReadDb->where($DirectQuery);
            }
            
            if (!empty($whereInCondition)) {
                foreach ($whereInCondition as $key => $value) {
                    if (!empty($value)) {
                        $this->ReadDb->where_in($key, $value);
                    }
                }
            }
            
            // $this->ReadDb->group_by("{$ModuleInfo->TableAliasName}.{$ModuleInfo->TablePrimaryUID}");
            if(!empty($sortOperation)) {
                foreach($sortOperation as $sortKey => $sortVal) {
                    $this->ReadDb->order_by($sortKey, $sortVal);
                }
            } else {
                $this->ReadDb->order_by("{$ModuleInfo->TableAliasName}.{$ModuleInfo->TablePrimaryUID}", $OrderBy);
            }
            if ($Limit > 0) {
                $this->ReadDb->limit($Limit, $Offset);
            }
            // print_r($this->ReadDb->get_compiled_select()); die();
            $query = $this->ReadDb->get();
            $error = $this->ReadDb->error();

            if ($error['code']) {
                throw new Exception($error['message']);
            }

            $this->EndReturnData->Data = $query->result();

            return $this->EndReturnData->Data;            

        } catch (Exception $e) {

            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($e->getMessage());

        }

    }

    public function getModuleTotalDataRowCount($ModuleInfo, $SelectColumns, $JoinDataArr = [], $FilterArray = [], $DirectQuery = '', $whereInCondition = []) {

        $this->EndReturnData = new StdClass();
        try {

            $this->ReadDb->db_debug = FALSE;

            if (empty($FilterArray) && empty($DirectQuery) && empty($whereInCondition)) {

                return $this->ReadDb
                    ->where(['IsDeleted' => 0, 'IsActive' => 1])
                    ->count_all_results("{$ModuleInfo->DatabaseName}.{$ModuleInfo->MasterTableName} AS {$ModuleInfo->TableAliasName}");

            } else {

                $rawFields = array_column($SelectColumns, 'DbFieldName');
                
                $aliases = [];
                foreach ($rawFields as $field) {
                    $explode = explode('.', $field);
                    if (isset($explode[0]) && $explode[0] !== $ModuleInfo->TableAliasName) {
                        $aliases[] = $explode[0];
                    }
                }

                $getUnqJoinTable = array_unique($aliases);

                $this->ReadDb->select("COUNT (DISTINCT {$ModuleInfo->TableAliasName}.{$ModuleInfo->TablePrimaryUID}) as TotalRowCount");
                $this->ReadDb->from("{$ModuleInfo->DatabaseName}.{$ModuleInfo->MasterTableName} AS {$ModuleInfo->TableAliasName}");
                if (!empty($JoinDataArr)) {
                    foreach ($JoinDataArr as $join) {

                        // Determine alias
                        $alias = $join->JoinLookupTblAliasName ?? $join->JoinTblAliasName;

                        // Validate if join is needed
                        $isNeeded = ($join->IsMandatory == 1) || in_array($alias, $getUnqJoinTable);
                        if (!$isNeeded) continue;

                        // Resolve table name
                        $joinTable = $join->JoinLookupTblAliasName
                            ? "{$join->LkupDatabaseName}.{$join->LkupTableName}"
                            : "{$join->JoinDatabaseName}.{$join->JoinTableName}";

                        // Resolve field name
                        $joinField = $join->JoinLookupTblFieldName ?? $join->JoinTblFieldName;

                        // Build join condition
                        $joinCond = "{$alias}.{$joinField} = {$join->MainTblAliasName}.{$join->MainTblFieldName}";

                        // Add IsDeleted / IsActive if required
                        if ($join->JoinBasicCheck) {
                            $joinCond .= " AND {$alias}.IsDeleted = 0 AND {$alias}.IsActive = 1";
                        }

                        // Add additional join conditions
                        if (!empty($join->JoinColumnsAddon)) {
                            $joinCond .= " {$join->JoinColumnsAddon}";
                        }

                        // Perform join
                        $this->ReadDb->join("{$joinTable} AS {$alias}", $joinCond, $join->JoinType);

                    }
                }
                $this->ReadDb->where(["{$ModuleInfo->TableAliasName}.IsDeleted" => 0, "{$ModuleInfo->TableAliasName}.IsActive"  => 1]);

                if (!empty($FilterArray)) {
                    $this->ReadDb->where($FilterArray);
                }
                
                if (!empty($DirectQuery)) {
                    $this->ReadDb->where($DirectQuery);
                }
                
                if (!empty($whereInCondition)) {
                    foreach ($whereInCondition as $key => $value) {
                        if (!empty($value)) {
                            $this->ReadDb->where_in($key, $value);
                        }
                    }
                }

                $query = $this->ReadDb->get();
                if (!$query) {
                    $error = $this->ReadDb->error();
                    throw new Exception($error['message']);
                }
                
                $this->EndReturnData->Data = $query->row()->TotalRowCount;
                return $this->EndReturnData->Data;

            }

        } catch (Exception $e) {

            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            throw new Exception($e->getMessage());

        }

    }

    public function getSingleRow($dbName = '', $table = '', $where = [], $select = '*') {
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