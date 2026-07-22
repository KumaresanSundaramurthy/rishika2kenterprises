<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Drive_model extends CI_Model {

    /** @var CI_DB_driver */
    private $ReadDb;

    public function __construct() {
        parent::__construct();
        $this->ReadDb = $this->load->database('ReadDB', TRUE);
    }

    /** @return CI_DB_driver */
    private function _wdb() {
        return $this->load->database('WriteDB', TRUE);
    }

    /**
     * @param int $orgUID
     * @param int $userUID
     * @param int $parentUID
     * @return array
     */
    public function getItems(int $orgUID, int $userUID, int $parentUID): array {
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->select(['DriveItemUID', 'UserUID', 'ParentUID', 'ItemType', 'ItemName', 'FilePath', 'FileSize', 'MimeType', 'CreatedOn']);
        $this->ReadDb->from('Drive.DriveItemsTbl');
        $this->ReadDb->where(['OrgUID' => $orgUID, 'UserUID' => $userUID, 'ParentUID' => $parentUID, 'IsDeleted' => 0]);
        $this->ReadDb->order_by('ItemType', 'ASC'); // Folder sorts before File alphabetically
        $this->ReadDb->order_by('ItemName',  'ASC');
        $result = $this->ReadDb->get();
        return $result ? $result->result() : [];
    }

    /**
     * @param int $orgUID
     * @param int $userUID
     * @return int  total bytes used by non-deleted files
     */
    public function getUserUsage(int $orgUID, int $userUID): int {
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->select_sum('FileSize');
        $this->ReadDb->from('Drive.DriveItemsTbl');
        $this->ReadDb->where(['OrgUID' => $orgUID, 'UserUID' => $userUID, 'ItemType' => 'File', 'IsDeleted' => 0]);
        $row = $this->ReadDb->get()->row();
        return (int)($row->FileSize ?? 0);
    }

    /**
     * Returns the ancestor chain for breadcrumb (root → … → current folder).
     *
     * @param int $orgUID
     * @param int $userUID
     * @param int $folderUID  0 = root, returns []
     * @return array  [{DriveItemUID, ItemName}, ...]
     */
    public function getFolderPath(int $orgUID, int $userUID, int $folderUID): array {
        if ($folderUID === 0) return [];
        $path  = [];
        $uid   = $folderUID;
        $limit = 20;
        while ($uid > 0 && $limit-- > 0) {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select(['DriveItemUID', 'ItemName', 'ParentUID']);
            $this->ReadDb->from('Drive.DriveItemsTbl');
            $this->ReadDb->where(['DriveItemUID' => $uid, 'OrgUID' => $orgUID, 'IsDeleted' => 0]);
            $row = $this->ReadDb->get()->row();
            if (!$row) break;
            array_unshift($path, ['DriveItemUID' => (int)$row->DriveItemUID, 'ItemName' => $row->ItemName]);
            $uid = (int)$row->ParentUID;
        }
        return $path;
    }

    /**
     * @param int $driveItemUID
     * @param int $orgUID
     * @return object|null
     */
    public function getItemByUID(int $driveItemUID, int $orgUID): ?object {
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->from('Drive.DriveItemsTbl');
        $this->ReadDb->where(['DriveItemUID' => $driveItemUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]);
        $row = $this->ReadDb->get()->row();
        return $row ?: null;
    }

    /**
     * @param int    $orgUID
     * @param int    $userUID
     * @param int    $parentUID
     * @param string $name
     * @param int    $excludeUID  exclude this UID from the check (for rename)
     * @return bool
     */
    public function isDuplicateName(int $orgUID, int $userUID, int $parentUID, string $name, int $excludeUID = 0): bool {
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->from('Drive.DriveItemsTbl');
        $this->ReadDb->where(['OrgUID' => $orgUID, 'UserUID' => $userUID, 'ParentUID' => $parentUID, 'IsDeleted' => 0]);
        $this->ReadDb->where('LOWER(ItemName) =', strtolower($name));
        if ($excludeUID > 0) $this->ReadDb->where('DriveItemUID !=', $excludeUID);
        return $this->ReadDb->count_all_results() > 0;
    }

    /**
     * @param int    $orgUID
     * @param int    $userUID
     * @param int    $parentUID
     * @param string $name
     * @param int    $actorUID
     * @return int  new DriveItemUID
     */
    public function createFolder(int $orgUID, int $userUID, int $parentUID, string $name, int $actorUID): int {
        $wdb = $this->_wdb();
        $wdb->insert('Drive.DriveItemsTbl', [
            'OrgUID' => $orgUID, 'UserUID' => $userUID, 'ParentUID' => $parentUID,
            'ItemType' => 'Folder', 'ItemName' => $name,
            'FilePath' => null, 'FileSize' => null, 'MimeType' => null,
            'IsDeleted' => 0, 'CreatedBy' => $actorUID, 'UpdatedBy' => $actorUID,
        ]);
        return (int)$wdb->insert_id();
    }

    /**
     * @param int    $orgUID
     * @param int    $userUID
     * @param int    $parentUID
     * @param string $name
     * @param string $filePath
     * @param int    $fileSize
     * @param string $mimeType
     * @param int    $actorUID
     * @return int  new DriveItemUID
     */
    public function createFile(int $orgUID, int $userUID, int $parentUID, string $name, string $filePath, int $fileSize, string $mimeType, int $actorUID): int {
        $wdb = $this->_wdb();
        $wdb->insert('Drive.DriveItemsTbl', [
            'OrgUID' => $orgUID, 'UserUID' => $userUID, 'ParentUID' => $parentUID,
            'ItemType' => 'File', 'ItemName' => $name,
            'FilePath' => $filePath, 'FileSize' => $fileSize, 'MimeType' => $mimeType,
            'IsDeleted' => 0, 'CreatedBy' => $actorUID, 'UpdatedBy' => $actorUID,
        ]);
        return (int)$wdb->insert_id();
    }

    /**
     * @param int $driveItemUID
     * @param int $newParentUID
     * @param int $orgUID
     * @param int $userUID
     * @param int $actorUID
     * @return bool
     */
    public function moveItem(int $driveItemUID, int $newParentUID, int $orgUID, int $userUID, int $actorUID): bool {
        $wdb = $this->_wdb();
        $wdb->where(['DriveItemUID' => $driveItemUID, 'OrgUID' => $orgUID, 'UserUID' => $userUID]);
        $wdb->update('Drive.DriveItemsTbl', ['ParentUID' => $newParentUID, 'UpdatedBy' => $actorUID, 'UpdatedOn' => date('Y-m-d H:i:s')]);
        return $wdb->affected_rows() > 0;
    }

    /**
     * @param int    $driveItemUID
     * @param string $newName
     * @param int    $orgUID
     * @param int    $userUID
     * @param int    $actorUID
     * @return bool
     */
    public function renameItem(int $driveItemUID, string $newName, int $orgUID, int $userUID, int $actorUID): bool {
        $wdb = $this->_wdb();
        $wdb->where(['DriveItemUID' => $driveItemUID, 'OrgUID' => $orgUID, 'UserUID' => $userUID]);
        $wdb->update('Drive.DriveItemsTbl', ['ItemName' => $newName, 'UpdatedBy' => $actorUID, 'UpdatedOn' => date('Y-m-d H:i:s')]);
        return $wdb->affected_rows() > 0;
    }

    /**
     * @param int $driveItemUID
     * @param int $orgUID
     * @param int $userUID
     * @param int $actorUID
     * @return void
     */
    public function softDeleteItem(int $driveItemUID, int $orgUID, int $userUID, int $actorUID): void {
        $wdb = $this->_wdb();
        $wdb->where(['DriveItemUID' => $driveItemUID, 'OrgUID' => $orgUID, 'UserUID' => $userUID]);
        $wdb->update('Drive.DriveItemsTbl', ['IsDeleted' => 1, 'UpdatedBy' => $actorUID, 'UpdatedOn' => date('Y-m-d H:i:s')]);
        $this->_softDeleteChildren($driveItemUID, $orgUID, $userUID, $actorUID);
    }

    /**
     * @param int $parentUID
     * @param int $orgUID
     * @param int $userUID
     * @param int $actorUID
     * @return void
     */
    private function _softDeleteChildren(int $parentUID, int $orgUID, int $userUID, int $actorUID): void {
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->select(['DriveItemUID', 'ItemType']);
        $this->ReadDb->from('Drive.DriveItemsTbl');
        $this->ReadDb->where(['ParentUID' => $parentUID, 'OrgUID' => $orgUID, 'UserUID' => $userUID, 'IsDeleted' => 0]);
        $children = $this->ReadDb->get()->result();
        if (!$children) return;

        $wdb = $this->_wdb();
        foreach ($children as $child) {
            $wdb->where(['DriveItemUID' => (int)$child->DriveItemUID, 'OrgUID' => $orgUID, 'UserUID' => $userUID]);
            $wdb->update('Drive.DriveItemsTbl', ['IsDeleted' => 1, 'UpdatedBy' => $actorUID, 'UpdatedOn' => date('Y-m-d H:i:s')]);
            if ($child->ItemType === 'Folder') {
                $this->_softDeleteChildren((int)$child->DriveItemUID, $orgUID, $userUID, $actorUID);
            }
        }
    }

    /**
     * @param int $orgUID
     * @return array
     */
    public function getOrgUsers(int $orgUID): array {
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->select("UserUID, CONCAT(FirstName, ' ', LastName) AS FullName");
        $this->ReadDb->from('Users.UserTbl');
        $this->ReadDb->where(['OrgUID' => $orgUID, 'IsDeleted' => 0, 'IsActive' => 1, 'HasLoginAccess' => 1]);
        $this->ReadDb->order_by('FirstName', 'ASC');
        $result = $this->ReadDb->get();
        return $result ? $result->result() : [];
    }
}
