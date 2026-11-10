<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Users extends MY_Controller {

    public  $pageData = [];

    public function __construct() {
        parent::__construct();
    }

    // ── List page ─────────────────────────────────────────────────────────────
    public function index(): void {
        if (!$this->_loadPageTitle()) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }
        try {
            $orgUID = $this->pageData['JwtData']->Org->OrgUID;

            $statusSlugMap = ['all' => 'All', 'active' => 'Active', 'resigned' => 'Resigned', 'terminated' => 'Terminated', 'onleave' => 'OnLeave'];
            $statusSlug    = strtolower(trim($this->input->get('status') ?: 'all'));
            $initStatus    = $statusSlugMap[$statusSlug] ?? 'All';

            $userUID  = (int) $this->pageData['JwtData']->User->UserUID;
            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';
            $pageResp = services_get(
                'api/users/getPageData?orgId=' . $orgUID . '&userId=' . $userUID
                    . '&status=' . urlencode($statusSlug) . '&langCode=' . urlencode($this->_uiLang()),
                ['Authorization: Bearer ' . $jwtToken]
            );
            $this->pageData['RolesList']       = $pageResp->RolesList       ?? [];
            $this->pageData['DepartmentList']  = $pageResp->DepartmentList  ?? [];
            $this->pageData['DesignationList'] = $pageResp->DesignationList ?? [];
            $this->pageData['NextEmpCode']     = $pageResp->NextEmpCode     ?? '';
            $this->pageData['CanSeeSalary']    = $pageResp->CanSeeSalary    ?? FALSE;
            $this->pageData['BranchesList']    = $pageResp->BranchesList    ?? [];
            $this->pageData['InitStatus']      = $pageResp->InitStatus      ?? $initStatus;

            $this->load->view('users/view', $this->pageData);

        } catch (Throwable $e) {
            notifyError('Users::index', $e);
            redirect('dashboard', 'refresh');
        }
    }

}