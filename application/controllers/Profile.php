<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Profile extends MY_Controller {

    public $pageData = array();

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

            $resp = services_get(
                'api/profile/getProfileDetails?orgId=' . $orgUID . '&userId=' . $userUID,
                ['Authorization: Bearer ' . $jwtToken]
            );

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