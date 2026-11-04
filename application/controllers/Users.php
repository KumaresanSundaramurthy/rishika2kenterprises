<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Users extends MY_Controller {

    public  $pageData = [];
    protected $EndReturnData;

    public function __construct() {
        parent::__construct();
        $this->load->model('users_model');
        $this->load->model('roles_model');
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

            $rolesResult = $this->roles_model->getRolesList($orgUID);
            $this->pageData['RolesList']       = $rolesResult->Error === FALSE ? $rolesResult->Data : [];
            $this->pageData['DepartmentList']  = $this->users_model->getDepartmentList($orgUID);
            $this->pageData['DesignationList'] = $this->users_model->getDesignationList($orgUID);
            $this->pageData['NextEmpCode']     = $this->users_model->getNextEmployeeCode($orgUID);
            $this->pageData['CanSeeSalary']    = in_array((int)($this->pageData['JwtData']->User->RoleUID ?? 0), [1, 2]);
            $this->load->model('branches_model');
            $this->pageData['BranchesList'] = $this->branches_model->getBranchList($orgUID, $this->_uiLang());
            $this->pageData['InitStatus']   = $initStatus;

            $this->load->view('users/view', $this->pageData);

        } catch (Throwable $e) {
            notifyError('Users::index', $e);
            redirect('dashboard', 'refresh');
        }
    }

}