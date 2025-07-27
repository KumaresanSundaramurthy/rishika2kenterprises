<?php defined('BASEPATH') or exit('No direct script access allowed');

class Storage_model extends CI_Model {

    private $EndReturnData;
    private $ProductDb;
    private $GlobalDb;

    function __construct() {
        parent::__construct();

        $this->ProductDb = $this->load->database('Products', TRUE);
        $this->GlobalDb = $this->load->database('Global', TRUE);

    }

    public function getStorageDetails($FilterArray) {

        $this->EndReturnData = new StdClass();
        try {

            $this->ProductDb->db_debug = FALSE;
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
            $this->ProductDb->select($select_ary);
            $this->ProductDb->from('Products.StorageTbl as Storage');
            $this->ProductDb->join($this->GlobalDb->database.'.StorageTypeTbl as StorageType', 'StorageType.StorageTypeUID = Storage.StorageTypeUID', 'left');
            $this->ProductDb->where($WhereCondition);
            if (!empty($FilterArray)) {
                $this->ProductDb->where($FilterArray);
            }
            $this->ProductDb->group_by('Storage.StorageUID');
            $this->ProductDb->order_by('Storage.StorageUID', 'ASC');

            $query = $this->ProductDb->get();
            $error = $this->ProductDb->error();
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

    public function storageFilterFormation($ModuleInfoData, $Filter) {

        $this->EndReturnData = new StdClass();
        try {

            $SearchDirectQuery = '';
            $SearchFilter = [];
            if(!empty($Filter)) {
                if (array_key_exists('SearchAllData', $Filter)) {
                    $SearchDirectQuery .= '(('. $ModuleInfoData->TableAliasName.'.Name LIKE "%'.$Filter['SearchAllData'].'%" ) OR ('.$ModuleInfoData->TableAliasName.'.ShortName LIKE "%'.$Filter['SearchAllData'].'%") OR ('.$ModuleInfoData->TableAliasName.'.Description LIKE "%'.$Filter['SearchAllData'].'%"))';
                }
            }

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->SearchDirectQuery = $SearchDirectQuery;
            $this->EndReturnData->SearchFilter = $SearchFilter;

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            $this->EndReturnData->SearchDirectQuery = '';
            $this->EndReturnData->SearchFilter = [];
        }

        return $this->EndReturnData;

    }

}