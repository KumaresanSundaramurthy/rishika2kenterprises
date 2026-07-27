<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Userpreferences_model extends CI_Model {

    private $ReadDb;

    function __construct() {
        parent::__construct();
        $this->ReadDb = $this->load->database('ReadDB', TRUE);
    }

    public function getUserPreference(int $orgUID, int $branchUID, int $userUID, string $key): ?string {
        $this->ReadDb->where('OrgUID',        (int)$orgUID);
        $this->ReadDb->where('BranchUID',     (int)$branchUID);
        $this->ReadDb->where('UserUID',       (int)$userUID);
        $this->ReadDb->where('PreferenceKey', $key);
        $row = $this->ReadDb->get('Users.UserPreferencesTbl')->row();
        return $row ? $row->PreferenceValue : null;
    }

    public function getAllUserPreferences(int $orgUID, int $branchUID, int $userUID): array {
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
