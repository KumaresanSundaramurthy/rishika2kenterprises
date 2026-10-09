<?php defined('BASEPATH') or exit('No direct script access allowed');

class Storage_model extends CI_Model {

    private $EndReturnData;
    private $ReadDb;

    function __construct() {
        parent::__construct();
        $this->ReadDb = $this->load->database('ReadDB', TRUE);
    }

    public function getStorageDetails(array $FilterArray, int $Limit = 0, int $Offset = 0, string $DirectQuery = '', string $langCode = 'en'): array {

        $this->EndReturnData = new StdClass();
        try {

            $this->ReadDb->db_debug = FALSE;
            $useLang     = $langCode !== 'en';
            $nameField   = $useLang ? 'COALESCE(SL.Name, Storage.Name) AS Name'               : 'Storage.Name AS Name';
            $shortField  = $useLang ? 'COALESCE(SL.ShortName, Storage.ShortName) AS ShortName' : 'Storage.ShortName AS ShortName';
            $descField   = $useLang ? 'COALESCE(SL.Description, Storage.Description) AS Description' : 'Storage.Description AS Description';
            $this->ReadDb->select([
                'Storage.StorageUID AS StorageUID',
                'Storage.OrgUID AS OrgUID',
                $nameField,
                $shortField,
                $descField,
                'Storage.StorageTypeUID AS StorageTypeUID',
                'StorageType.Name AS StorageTypeName',
                'Storage.Image AS Image',
                'Storage.CreatedOn as CreatedOn',
                'Storage.UpdatedOn as UpdatedOn',
            ]);
            $this->ReadDb->from('Products.StorageTbl as Storage');
            $this->ReadDb->join('Global.StorageTypeTbl as StorageType', 'StorageType.StorageTypeUID = Storage.StorageTypeUID', 'left');
            if ($useLang) {
                $lc = $this->ReadDb->escape_str($langCode);
                $this->ReadDb->join("Products.StorageTbl_Lang AS SL", "SL.StorageUID = Storage.StorageUID AND SL.LangCode = '{$lc}'", 'left');
            }
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

    /**
     * Upsert a translated row into StorageTbl_Lang.
     * @param int         $storageUID
     * @param string      $langCode
     * @param string|null $name
     * @param string|null $shortName
     * @param string|null $description
     * @param int         $userUID
     * @returns void
     */
    public function saveStorageLangRow(int $storageUID, string $langCode, ?string $name, ?string $shortName, ?string $description, int $userUID): void {
        try {
            $this->load->model('dbwrite_ext_model');
            $this->dbwrite_ext_model->execWrite(
                "INSERT INTO Products.StorageTbl_Lang
                    (StorageUID, LangCode, Name, ShortName, Description, CreatedBy, UpdatedBy)
                 VALUES (?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    Name        = VALUES(Name),
                    ShortName   = VALUES(ShortName),
                    Description = VALUES(Description),
                    UpdatedBy   = VALUES(UpdatedBy)",
                [$storageUID, $langCode, $name, $shortName, $description, $userUID, $userUID]
            );
        } catch (Exception $e) {
            notifyError('Storage_model::saveStorageLangRow', $e);
        }
    }

}
