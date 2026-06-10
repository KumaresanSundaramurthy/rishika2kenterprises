<?php defined('BASEPATH') OR exit('No direct script access allowed');

class UserPreferences_model extends CI_Model {

    private $ReadDb;
    private $WriteDb;

    function __construct() {
        parent::__construct();
        $this->ReadDb  = $this->load->database('ReadDB',  TRUE);
        $this->WriteDb = $this->load->database('WriteDB', TRUE);
    }

    public function upsertPreference(int $orgUID, int $branchUID, int $userUID, string $key, string $value) {
        $orgUID    = (int)$orgUID;
        $branchUID = (int)$branchUID;
        $userUID   = (int)$userUID;
        $key       = $this->WriteDb->escape_str($key);
        $value     = $this->WriteDb->escape_str((string)$value);

        $sql = "INSERT INTO Users.UserPreferencesTbl (OrgUID, BranchUID, UserUID, PreferenceKey, PreferenceValue)
                VALUES ({$orgUID}, {$branchUID}, {$userUID}, '{$key}', '{$value}')
                ON DUPLICATE KEY UPDATE PreferenceValue = VALUES(PreferenceValue), UpdatedAt = NOW()";
        return $this->WriteDb->query($sql);
    }

    public function getUserPreference(int $orgUID, int $branchUID, int $userUID, string $key) {
        $this->ReadDb->where('OrgUID',        (int)$orgUID);
        $this->ReadDb->where('BranchUID',     (int)$branchUID);
        $this->ReadDb->where('UserUID',       (int)$userUID);
        $this->ReadDb->where('PreferenceKey', $key);
        $row = $this->ReadDb->get('Users.UserPreferencesTbl')->row();
        return $row ? $row->PreferenceValue : null;
    }

    public function getAllUserPreferences(int $orgUID, int $branchUID, int $userUID) {
        $this->ReadDb->where('OrgUID',    (int)$orgUID);
        $this->ReadDb->where('BranchUID', (int)$branchUID);
        $this->ReadDb->where('UserUID',   (int)$userUID);
        $rows = $this->ReadDb->get('Users.UserPreferencesTbl')->result();

        $map = [];
        foreach ($rows as $r) {
            $map[$r->PreferenceKey] = $r->PreferenceValue;
        }
        return $map;
    }
}
