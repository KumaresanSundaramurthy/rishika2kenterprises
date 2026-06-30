<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property object $attendance_model
 * @property object $users_model
 * @property object $dbwrite_model
 * @property object $globalservice
 * @property object $input
 */
class Salaryadvances extends MY_Controller {

    public  $pageData     = [];
    /** @var object|null */
    private $EndReturnData;

    public function __construct() { parent::__construct(); }

    private function _fetchTableData(int $pageNo, int $limit, array $filter = []): object {
        $offset = max(0, ($pageNo - 1) * $limit);
        $this->load->model('attendance_model');
        $result  = $this->attendance_model->getAdvanceListPaginated($this->_orgUID(), $limit, $offset, $filter);
        $rowHtml = $this->load->view('hrms/salaryadvances/list', ['DataLists' => $result->rows, 'SerialNumber' => $offset, 'JwtData' => $this->pageData['JwtData']], TRUE);
        $r = new stdClass();
        $r->RecordHtmlData = $rowHtml;
        $r->Pagination     = $this->globalservice->buildPagePaginationHtml('/salaryadvances/getPageDetails', $result->totalCount, $pageNo, $limit);
        $r->TotalCount     = $result->totalCount;
        $r->Stats          = $this->attendance_model->getAdvanceStats($this->_orgUID());
        return $r;
    }

    public function index() {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $pd = $this->_fetchTableData(1, $this->_rowLimit());
            $this->pageData['ModRowData']    = $pd->RecordHtmlData;
            $this->pageData['ModPagination'] = $pd->Pagination;
            $this->pageData['AdvanceStats']  = $pd->Stats;
            $this->load->model('users_model');
            $this->pageData['EmployeeList']  = $this->users_model->getEmployeeDropdownList($this->_orgUID());
            $this->load->view('hrms/salaryadvances/view', $this->pageData);
        } catch (Exception $_) { redirect('dashboard', 'refresh'); }
    }

    public function getPageDetails($pageNo = 0) {
        $this->EndReturnData = new stdClass();
        try {
            $pd = $this->_fetchTableData(max(1, (int)$pageNo), $this->_rowLimit(), $this->input->post('Filter') ?: []);
            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->RecordHtmlData = $pd->RecordHtmlData;
            $this->EndReturnData->Pagination     = $pd->Pagination;
            $this->EndReturnData->TotalCount     = $pd->TotalCount;
            $this->EndReturnData->Stats          = $pd->Stats;
        } catch (Exception $e) { $this->EndReturnData->Error = TRUE; $this->EndReturnData->Message = $e->getMessage(); }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function save() {
        $this->EndReturnData = new stdClass();
        try {
            $p   = $this->input->post();
            $uid = (int)($p['AdvanceUID'] ?? 0);
            if (empty($p['EmployeeUID'])) throw new Exception('Employee is required.');
            if (empty($p['AdvanceDate'])) throw new Exception('Date is required.');
            $amt = (float)($p['AdvanceAmount'] ?? 0);
            if ($amt <= 0) throw new Exception('Amount must be greater than 0.');

            $this->load->model('dbwrite_model');
            $data = [
                'OrgUID'        => $this->_orgUID(),
                'BranchUID'     => $this->_branchUID(),
                'UserUID'       => (int)$p['EmployeeUID'],
                'AdvanceDate'   => $p['AdvanceDate'],
                'AdvanceAmount' => $amt,
                'Reason'        => trim($p['Remarks'] ?? $p['Reason'] ?? ''),
                'UpdatedBy'     => $this->_userUID(),
            ];

            if ($uid === 0) {
                $data['BalancePending'] = $amt;
                $data['IsSettled']      = 0;
                $data['AdvanceStatus']  = 'Requested';
                $data['CreatedBy']      = $this->_userUID();
                $res = $this->dbwrite_model->insertData('Transaction', 'SalaryAdvanceTbl', $data);
            } else {
                // Only allow editing Requested advances
                $res = $this->dbwrite_model->updateData('Transaction', 'SalaryAdvanceTbl', $data,
                    ['AdvanceUID' => $uid, 'OrgUID' => $this->_orgUID(), 'AdvanceStatus' => 'Requested']);
            }
            if ($res->Error) throw new Exception($res->Message);

            $pd = $this->_fetchTableData(1, $this->_rowLimit(), $this->input->post('Filter') ?: []);
            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->Message        = $uid ? 'Advance request updated.' : 'Advance request submitted.';
            $this->EndReturnData->RecordHtmlData = $pd->RecordHtmlData;
            $this->EndReturnData->Pagination     = $pd->Pagination;
            $this->EndReturnData->Stats          = $pd->Stats;
        } catch (Exception $e) { $this->EndReturnData->Error = TRUE; $this->EndReturnData->Message = $e->getMessage(); }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function approve() {
        $this->EndReturnData = new stdClass();
        try {
            $uid = (int)$this->input->post('AdvanceUID');
            if (!$uid) throw new Exception('Invalid advance.');
            $this->load->model('dbwrite_model');

            // Fetch advance details before status change (need date + amount for journal)
            $this->load->model('attendance_model');
            $advances = $this->attendance_model->getAdvanceListPaginated($this->_orgUID(), 1, 0, ['AdvanceUID' => $uid]);
            $advance  = !empty($advances->rows) ? $advances->rows[0] : null;

            $res = $this->dbwrite_model->updateData('Transaction', 'SalaryAdvanceTbl',
                ['AdvanceStatus' => 'Approved', 'UpdatedBy' => $this->_userUID()],
                ['AdvanceUID' => $uid, 'OrgUID' => $this->_orgUID(), 'AdvanceStatus' => 'Requested']
            );
            if ($res->Error) throw new Exception($res->Message);

            // ── Post advance journal entry ─────────────────────────────────
            if ($advance) {
                try {
                    $this->load->library('accountledger');
                    $advDate = $advance->AdvanceDate ?? date('Y-m-d');
                    $advFY   = (int)date('Y', strtotime($advDate));
                    $this->accountledger->postAdvanceJournal(
                        $uid, $advDate, $advFY,
                        (float)($advance->AdvanceAmount ?? 0),
                        $this->_userUID()
                    );
                } catch (Exception $ledgerEx) {
                    log_message('error', 'Ledger failed after advance approval: ' . $ledgerEx->getMessage());
                }
            }

            $pd = $this->_fetchTableData(1, $this->_rowLimit(), $this->input->post('Filter') ?: []);
            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->Message        = 'Advance approved.';
            $this->EndReturnData->RecordHtmlData = $pd->RecordHtmlData;
            $this->EndReturnData->Pagination     = $pd->Pagination;
            $this->EndReturnData->Stats          = $pd->Stats;
        } catch (Exception $e) { $this->EndReturnData->Error = TRUE; $this->EndReturnData->Message = $e->getMessage(); }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function reject() {
        $this->EndReturnData = new stdClass();
        try {
            $uid = (int)$this->input->post('AdvanceUID');
            if (!$uid) throw new Exception('Invalid advance.');
            $this->load->model('dbwrite_model');
            $res = $this->dbwrite_model->updateData('Transaction', 'SalaryAdvanceTbl',
                ['AdvanceStatus' => 'Rejected', 'UpdatedBy' => $this->_userUID()],
                ['AdvanceUID' => $uid, 'OrgUID' => $this->_orgUID(), 'AdvanceStatus' => 'Requested']
            );
            if ($res->Error) throw new Exception($res->Message);
            $pd = $this->_fetchTableData(1, $this->_rowLimit(), $this->input->post('Filter') ?: []);
            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->Message        = 'Advance rejected.';
            $this->EndReturnData->RecordHtmlData = $pd->RecordHtmlData;
            $this->EndReturnData->Pagination     = $pd->Pagination;
            $this->EndReturnData->Stats          = $pd->Stats;
        } catch (Exception $e) { $this->EndReturnData->Error = TRUE; $this->EndReturnData->Message = $e->getMessage(); }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function delete() {
        $this->EndReturnData = new stdClass();
        try {
            $uid = (int)$this->input->post('AdvanceUID');
            if (!$uid) throw new Exception('Invalid.');

            // Check current status before deleting (need it for journal reversal decision)
            $this->load->model('attendance_model');
            $advances   = $this->attendance_model->getAdvanceListPaginated($this->_orgUID(), 1, 0, ['AdvanceUID' => $uid]);
            $advance    = !empty($advances->rows) ? $advances->rows[0] : null;
            $wasApproved = $advance && ($advance->AdvanceStatus ?? '') === 'Approved';

            $this->load->model('dbwrite_model');
            $res = $this->dbwrite_model->updateData('Transaction', 'SalaryAdvanceTbl',
                ['IsDeleted' => 1, 'UpdatedBy' => $this->_userUID()],
                ['AdvanceUID' => $uid, 'OrgUID' => $this->_orgUID()]
            );
            if ($res->Error) throw new Exception($res->Message);

            // ── Reverse journal only if advance was already Approved ───────
            if ($wasApproved) {
                try {
                    $this->load->library('accountledger');
                    $this->accountledger->reverseJournal('SalaryAdvance', $uid, $this->_userUID());
                } catch (Exception $ledgerEx) {
                    log_message('error', 'Ledger reverse failed after advance delete: ' . $ledgerEx->getMessage());
                }
            }

            $pd = $this->_fetchTableData(1, $this->_rowLimit(), $this->input->post('Filter') ?: []);
            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->Message        = 'Advance deleted.';
            $this->EndReturnData->RecordHtmlData = $pd->RecordHtmlData;
            $this->EndReturnData->Pagination     = $pd->Pagination;
            $this->EndReturnData->Stats          = $pd->Stats;
        } catch (Exception $e) { $this->EndReturnData->Error = TRUE; $this->EndReturnData->Message = $e->getMessage(); }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }
}
