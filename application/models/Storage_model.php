<?php defined('BASEPATH') or exit('No direct script access allowed');

class Storage_model extends CI_Model {

    private $EndReturnData;
    private $ProductDb;

    function __construct() {
        parent::__construct();

        $this->ProductDb = $this->load->database('Products', TRUE);
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