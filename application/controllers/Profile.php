<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Profile extends MY_Controller {

    public $pageData = array();
    protected $EndReturnData;

    public function __construct() {
        parent::__construct();
    }

    public function index(): void {

        $this->pageData['userInfo']       = null;
        $this->pageData['userFullData']   = null;
        $this->pageData['userAttachments'] = [];
        $this->pageData['DepartmentsList'] = [];
        $this->pageData['DesignationsList'] = [];
        $this->pageData['OrgUsersList']    = [];
        $this->pageData['bankDetails']     = null;

        try {

            $userUID  = (int) $this->pageData['JwtData']->User->UserUID;
            $orgUID   = (int) $this->pageData['JwtData']->Org->OrgUID;
            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';

            $serviceUrl = rtrim(getenv('SERVICES_BASE_URL'), '/') . '/api/profile/getProfileDetails';
            $ch = curl_init($serviceUrl . '?orgId=' . $orgUID . '&userId=' . $userUID);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $jwtToken],
            ]);
            $raw  = curl_exec($ch);
            curl_close($ch);
            $resp = $raw ? json_decode($raw) : null;

            if ($resp && !$resp->Error) {
                $this->pageData['userInfo']         = $resp->userInfo        ?? null;
                $this->pageData['userFullData']     = $resp->userFullData    ?? null;
                $this->pageData['userAttachments']  = $resp->userAttachments ?? [];
                $this->pageData['DepartmentsList']  = $resp->DepartmentsList ?? [];
                $this->pageData['DesignationsList'] = $resp->DesignationsList ?? [];
                $this->pageData['OrgUsersList']     = $resp->OrgUsersList    ?? [];
                $this->pageData['bankDetails']      = $resp->bankDetails     ?? null;
            }

            $this->load->view('profile/view', $this->pageData);

        } catch (Exception $e) {
            $this->notifyError('Profile::index', $e);
            $this->load->view('profile/view', $this->pageData);
        }

    }

}