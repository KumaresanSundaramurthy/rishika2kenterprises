<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Userpreferences extends MY_Controller {

    public $pageData = [];

    public function __construct() {
        parent::__construct();
        $this->load->model('UserPreferences_model');
    }

    // POST /userpreferences/save
    // Body: PreferenceKey, PreferenceValue
    public function save() {
        $out = new stdClass();
        try {
            $jwtData   = $this->pageData['JwtData'] ?? null;
            $orgUID    = (int)($jwtData->Org->OrgUID    ?? 0);
            $branchUID = (int)($jwtData->Org->BranchUID ?? 0);
            $userUID   = (int)($jwtData->User->UserUID  ?? 0);

            if (!$orgUID || !$userUID) {
                $out->Error   = true;
                $out->Message = 'Unauthorised';
                $this->globalservice->sendJsonResponse($out);
                return;
            }

            $key   = trim($this->input->post('PreferenceKey')   ?? '');
            $value = trim($this->input->post('PreferenceValue') ?? '');

            if (!$key) {
                $out->Error   = true;
                $out->Message = 'PreferenceKey is required';
                $this->globalservice->sendJsonResponse($out);
                return;
            }

            $this->UserPreferences_model->upsertPreference($orgUID, $branchUID, $userUID, $key, $value);

            $out->Error = false;
        } catch (Exception $e) {
            $out->Error   = true;
            $out->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($out);
    }

    // GET /userpreferences/getAll
    public function getAll() {
        $out = new stdClass();
        try {
            $jwtData   = $this->pageData['JwtData'] ?? null;
            $orgUID    = (int)($jwtData->Org->OrgUID    ?? 0);
            $branchUID = (int)($jwtData->Org->BranchUID ?? 0);
            $userUID   = (int)($jwtData->User->UserUID  ?? 0);

            $prefs = ($orgUID && $userUID)
                ? $this->UserPreferences_model->getAllUserPreferences($orgUID, $branchUID, $userUID)
                : [];

            $out->Error       = false;
            $out->Preferences = $prefs;
        } catch (Exception $e) {
            $out->Error   = true;
            $out->Message = $e->getMessage();
            $out->Preferences = [];
        }
        $this->globalservice->sendJsonResponse($out);
    }
}
