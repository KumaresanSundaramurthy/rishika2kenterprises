<?php defined('BASEPATH') or exit('No direct script access allowed');

class Storage_model extends CI_Model {

    private $EndReturnData;
    private $ReadDb;

    function __construct() {
        parent::__construct();

        $this->ReadDb = $this->load->database('ReadDB', TRUE);

    }

    public function storageFilterFormation($ModuleInfoData, $Filter) {

        $this->EndReturnData = new StdClass();
        try {

            $SearchDirectQuery = '';
            $SearchFilter = [];
            $sortOperation = [];
            if(!empty($Filter)) {
                if (array_key_exists('SearchAllData', $Filter)) {
                    $SearchDirectQuery .= "((". $ModuleInfoData->TableAliasName.".Name LIKE '%".$Filter['SearchAllData']."%' ) OR (".$ModuleInfoData->TableAliasName.".ShortName LIKE '%".$Filter['SearchAllData']."%') OR (".$ModuleInfoData->TableAliasName.".Description LIKE '%".$Filter['SearchAllData']."%'))";
                }
                if (array_key_exists('NameSorting', $Filter)) {
                    $sortOperation[$ModuleInfoData->TableAliasName . '.Name'] = $Filter['NameSorting'] == 1 ? 'ASC' : 'DESC';
                }
                if (array_key_exists('StorageType', $Filter)) {
                    if($SearchDirectQuery != '') {
                        $SearchDirectQuery .= ' AND ';    
                    }
                    $SearchDirectQuery .= $ModuleInfoData->TableAliasName.'.StorageTypeUID IN ('.implode(',', $Filter['StorageType']).')';
                }
            }
            
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->SearchDirectQuery = $SearchDirectQuery;
            $this->EndReturnData->SearchFilter = $SearchFilter;
            $this->EndReturnData->sortOperation = $sortOperation;

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            $this->EndReturnData->SearchDirectQuery = '';
            $this->EndReturnData->SearchFilter = [];
            $this->EndReturnData->sortOperation = [];
        }

        return $this->EndReturnData;

    }

    public function getStorageDetails($FilterArray) {

        $this->EndReturnData = new StdClass();
        try {

            $this->ReadDb->db_debug = FALSE;
            $select_ary = array(
                'Storage.StorageUID AS StorageUID',
                'Storage.OrgUID AS OrgUID',
                'Storage.Name AS Name',
                'Storage.ShortName AS ShortName',
                'Storage.Description AS Description',
                'Storage.StorageTypeUID AS StorageTypeUID',
                'StorageType.Name AS StorageTypeName',
                'Storage.Image AS Image',
                'Storage.CreatedOn as CreatedOn',
                'Storage.UpdatedOn as UpdatedOn',
            );
            $WhereCondition = array(
                'Storage.IsDeleted' => 0,
                'Storage.IsActive' => 1,
            );
            $this->ReadDb->select($select_ary);
            $this->ReadDb->from('Products.StorageTbl as Storage');
            $this->ReadDb->join('Global.StorageTypeTbl as StorageType', 'StorageType.StorageTypeUID = Storage.StorageTypeUID', 'left');
            $this->ReadDb->where($WhereCondition);
            if (!empty($FilterArray)) {
                $this->ReadDb->where($FilterArray);
            }
            $this->ReadDb->group_by('Storage.StorageUID');
            $this->ReadDb->order_by('Storage.StorageUID', 'ASC');

            $query = $this->ReadDb->get();
            $error = $this->ReadDb->error();
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

}