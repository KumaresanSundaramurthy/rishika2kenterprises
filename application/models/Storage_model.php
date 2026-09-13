<?php defined('BASEPATH') or exit('No direct script access allowed');

class Storage_model extends CI_Model {

    private $EndReturnData;
    private $ReadDb;

    function __construct() {
        parent::__construct();
        $this->ReadDb = $this->load->database('ReadDB', TRUE);
    }

    public function getStorageDetails(array $FilterArray, int $Limit = 0, int $Offset = 0, string $DirectQuery = ''): array {

        $this->EndReturnData = new StdClass();
        try {

            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
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
            ]);
            $this->ReadDb->from('Products.StorageTbl as Storage');
            $this->ReadDb->join('Global.StorageTypeTbl as StorageType', 'StorageType.StorageTypeUID = Storage.StorageTypeUID', 'left');
            $this->ReadDb->where(['Storage.IsDeleted' => 0, 'Storage.IsActive' => 1]);
            if (!empty($FilterArray)) {
                $this->ReadDb->where($FilterArray);
            }
            if (!empty($DirectQuery)) {
                $this->ReadDb->where($DirectQuery);
            }
            $this->ReadDb->group_by('Storage.StorageUID');
            $this->ReadDb->order_by('Storage.StorageUID', 'ASC');
            if ($Limit > 0) {
                $this->ReadDb->limit($Limit, $Offset);
            }

            $query = $this->ReadDb->get();
            if ($this->ReadDb->error()['code']) {
                throw new Exception($this->ReadDb->error()['message']);
            }

            return $query->result();

        } catch (Exception $e) {
            notifyError('Storage_model::getStorageDetails', $e);
            throw new Exception($e->getMessage());
        }

    }

    public function getTotalStorageCount(array $FilterArray, string $DirectQuery = ''): int {

        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->from('Products.StorageTbl as Storage');
        $this->ReadDb->where(['Storage.IsDeleted' => 0, 'Storage.IsActive' => 1]);
        if (!empty($FilterArray)) {
            $this->ReadDb->where($FilterArray);
        }
        if (!empty($DirectQuery)) {
            $this->ReadDb->where($DirectQuery);
        }
        return (int) $this->ReadDb->count_all_results();

    }

}
