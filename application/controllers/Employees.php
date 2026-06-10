<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Employees extends MY_Controller {

    public  $pageData = [];
    private $EndReturnData;
    private $pageModuleUID;

    public function __construct() {
        parent::__construct();

        $this->pageModuleUID = 71;
        
    }

    private function _initModule() {
        $this->pageData['Limit'] = $this->pageData['JwtData']->GenSettings->RowLimit ?? 10;
    }

    private function _orgUID()    { return (int)$this->pageData['JwtData']->Org->OrgUID; }
    private function _branchUID() { return (int)($this->pageData['JwtData']->Org->BranchUID ?? 1); }
    private function _userUID()   { return (int)$this->pageData['JwtData']->User->UserUID; }

    private function _fetchTableData($pageNo, $limit, $filter = []) {
        $offset = max(0, ($pageNo - 1) * $limit);
        $this->load->model('employees_model');
        $result  = $this->employees_model->getEmployeeListPaginated($this->_orgUID(), $limit, $offset, $filter);
        $rowHtml = $this->load->view('hrms/employees/list', [
            'DataLists'    => $result->rows,
            'SerialNumber' => $offset,
            'JwtData'      => $this->pageData['JwtData'],
        ], TRUE);
        $resp                  = new stdClass();
        $resp->RecordHtmlData  = $rowHtml;
        $resp->Pagination      = $this->globalservice->buildPagePaginationHtml('/employees/getPageDetails', $result->totalCount, $pageNo, $limit);
        $resp->TotalCount      = $result->totalCount;
        return $resp;
    }

    // ── Page routes ───────────────────────────────────────────────────────────

    public function index() {
        if (!$this->_loadPageTitle($this->pageModuleUID)) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $this->_initModule();
            $pageData = $this->_fetchTableData(1, $this->pageData['Limit']);
            $this->pageData['ModRowData']    = $pageData->RecordHtmlData;
            $this->pageData['ModPagination'] = $pageData->Pagination;

            $this->load->model('employees_model');
            $this->pageData['EmpStats']      = $this->employees_model->getEmployeeStats($this->_orgUID());
            $this->pageData['DepartmentList'] = $this->employees_model->getDepartmentList($this->_orgUID());
            $this->pageData['DesignationList']= $this->employees_model->getDesignationList($this->_orgUID());
            $this->pageData['NextEmpCode']   = $this->employees_model->getNextEmployeeCode($this->_orgUID());
            $this->load->view('hrms/employees/view', $this->pageData);
        } catch (Exception $e) { redirect('dashboard', 'refresh'); }
    }

    // ── AJAX — pagination ─────────────────────────────────────────────────────

    public function getPageDetails($pageNo = 0) {
        $this->EndReturnData = new stdClass();
        try {
            $pageNo  = max(1, (int)$pageNo);
            $filter  = $this->input->post('Filter') ?: [];
            $this->_initModule();
            $limit   = (int)($this->input->post('RowLimit') ?: $this->pageData['Limit']);
            $pd      = $this->_fetchTableData($pageNo, $limit, $filter);
            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->RecordHtmlData = $pd->RecordHtmlData;
            $this->EndReturnData->Pagination     = $pd->Pagination;
            $this->EndReturnData->TotalCount     = $pd->TotalCount;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── AJAX — get single employee ────────────────────────────────────────────

    public function getEmployee($uid = 0) {
        $this->EndReturnData = new stdClass();
        try {
            $this->load->model('employees_model');
            $data = $this->employees_model->getEmployeeByUID((int)$uid, $this->_orgUID());
            if (!$data) throw new Exception('Employee not found.');
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $data;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── AJAX — save (create / update) ─────────────────────────────────────────

    public function save() {
        $this->EndReturnData = new stdClass();
        try {
            $p      = $this->input->post();
            $uid    = (int)($p['EmployeeUID'] ?? 0);
            $isNew  = $uid === 0;
            $orgUID = $this->_orgUID();

            if (empty(trim($p['EmployeeName'] ?? ''))) throw new Exception('Employee name is required.');
            if (empty(trim($p['EmployeeCode'] ?? ''))) throw new Exception('Employee code is required.');

            $this->load->model('dbwrite_model');
            $data = [
                'OrgUID'          => $orgUID,
                'BranchUID'       => $this->_branchUID(),
                'DepartmentUID'   => !empty($p['DepartmentUID'])  ? (int)$p['DepartmentUID']  : NULL,
                'DesignationUID'  => !empty($p['DesignationUID']) ? (int)$p['DesignationUID'] : NULL,
                'EmployeeCode'    => trim($p['EmployeeCode']),
                'EmployeeName'    => trim($p['EmployeeName']),
                'Mobile'          => trim($p['Mobile']     ?? ''),
                'Email'           => trim($p['Email']      ?? ''),
                'Address'         => trim($p['Address']    ?? ''),
                'DateOfJoining'   => !empty($p['DateOfJoining']) ? $p['DateOfJoining'] : NULL,
                'SalaryType'      => in_array($p['SalaryType'] ?? '', ['Monthly','Daily','Hourly']) ? $p['SalaryType'] : 'Monthly',
                'BasicSalary'     => (float)($p['BasicSalary']    ?? 0),
                'Allowances'      => (float)($p['Allowances']     ?? 0),
                'Incentives'      => (float)($p['Incentives']     ?? 0),
                'FixedDeductions' => (float)($p['FixedDeductions'] ?? 0),
                'EmployeeStatus'  => in_array($p['EmployeeStatus'] ?? '', ['Active','Resigned','Terminated','OnLeave']) ? $p['EmployeeStatus'] : 'Active',
                'IsActive'        => ($p['EmployeeStatus'] ?? 'Active') === 'Active' ? 1 : 1,
                'UpdatedBy'       => $this->_userUID(),
            ];

            if ($isNew) {
                $data['CreatedBy'] = $this->_userUID();
                $res = $this->dbwrite_model->insertData('Organisation', 'EmployeeTbl', $data);
                if ($res->Error) throw new Exception($res->Message);
                $this->EndReturnData->Message = 'Employee created successfully.';
            } else {
                $res = $this->dbwrite_model->updateData('Organisation', 'EmployeeTbl', $data, ['EmployeeUID' => $uid, 'OrgUID' => $orgUID]);
                if ($res->Error) throw new Exception($res->Message);
                $this->EndReturnData->Message = 'Employee updated successfully.';
            }
            $this->EndReturnData->Error = FALSE;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── AJAX — toggle active status ───────────────────────────────────────────

    public function toggleStatus() {
        $this->EndReturnData = new stdClass();
        try {
            $uid       = (int)$this->input->post('EmployeeUID');
            $newStatus = (int)$this->input->post('IsActive');
            if (!$uid) throw new Exception('Invalid employee.');
            $this->load->model('dbwrite_model');
            $res = $this->dbwrite_model->updateData('Organisation', 'EmployeeTbl', ['IsActive' => $newStatus, 'UpdatedBy' => $this->_userUID()], ['EmployeeUID' => $uid, 'OrgUID' => $this->_orgUID()]);
            if ($res->Error) throw new Exception($res->Message);
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Status updated.';
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── AJAX — soft-delete ────────────────────────────────────────────────────

    public function delete() {
        $this->EndReturnData = new stdClass();
        try {
            $uid = (int)$this->input->post('EmployeeUID');
            if (!$uid) throw new Exception('Invalid employee.');
            $this->load->model('dbwrite_model');
            $res = $this->dbwrite_model->updateData('Organisation', 'EmployeeTbl', ['IsDeleted' => 1, 'UpdatedBy' => $this->_userUID()], ['EmployeeUID' => $uid, 'OrgUID' => $this->_orgUID()]);
            if ($res->Error) throw new Exception($res->Message);
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Employee deleted.';
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── AJAX — dropdown list for other modules ────────────────────────────────

    public function getEmployeeList() {
        $this->EndReturnData = new stdClass();
        try {
            $this->load->model('employees_model');
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $this->employees_model->getEmployeeDropdownList($this->_orgUID());
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }
}
