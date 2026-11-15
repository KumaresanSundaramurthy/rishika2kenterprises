<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property object $users_model
 * @property object $dbwrite_model
 * @property object $globalservice
 * @property object $input
 */
class Salaryadvances extends MY_Controller {

    public  $pageData     = [];
    /** @var object|null */
    protected $EndReturnData;

    public function __construct() { parent::__construct(); }

    public function index(): void {
        if (!$this->_loadPageTitle()) { $this->load->view('common/module_error', $this->pageData); return; }
        try {
            $this->load->view('hrms/salaryadvances/view', $this->pageData);
        } catch (Exception $e) {
            notifyError('Salaryadvances::index', $e);
            redirect('dashboard', 'refresh');
        }
    }

    public function save(): void {
        $this->EndReturnData = new stdClass();
        try {
            $p   = $this->input->post();
            $uid = (int)($p['AdvanceUID'] ?? 0);
            if (empty($p['EmployeeUID'])) throw new Exception('Employee is required.');
            if (empty($p['AdvanceDate'])) throw new Exception('Date is required.');
            $amt = (float)($p['AdvanceAmount'] ?? 0);
            if ($amt <= 0) throw new Exception('Amount must be greater than 0.');

            $this->load->model('dbwrite_model'); $this->load->model('dbwrite_ext_model');
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
                $res = $this->dbwrite_model->updateData('Transaction', 'SalaryAdvanceTbl', $data,
                    ['AdvanceUID' => $uid, 'OrgUID' => $this->_orgUID(), 'AdvanceStatus' => 'Requested']);
            }
            if ($res->Error) throw new Exception($res->Message);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = $uid ? 'Advance request updated.' : 'Advance request submitted.';
        } catch (Exception $e) {
            $this->notifyError('Salaryadvances::save', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function approve(): void {
        $this->EndReturnData = new stdClass();
        try {
            $uid = (int)$this->input->post('AdvanceUID');
            if (!$uid) throw new Exception('Invalid advance.');
            $this->load->model('dbwrite_model'); $this->load->model('dbwrite_ext_model');

            /* Fetch advance details needed for journal entry */
            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';
            $advResp  = services_get(
                'api/salaryadvances/getPageDetails/1?orgId=' . $this->_orgUID() . '&RowLimit=1&Filter=' . urlencode(json_encode(['AdvanceUID' => $uid])),
                ['Authorization: Bearer ' . $jwtToken]
            );
            $advance = $advResp->Data->Data[0] ?? null;

            $res = $this->dbwrite_model->updateData('Transaction', 'SalaryAdvanceTbl',
                ['AdvanceStatus' => 'Approved', 'UpdatedBy' => $this->_userUID()],
                ['AdvanceUID' => $uid, 'OrgUID' => $this->_orgUID(), 'AdvanceStatus' => 'Requested']
            );
            if ($res->Error) throw new Exception($res->Message);

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
                }
            }

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Advance approved.';
        } catch (Exception $e) {
            $this->notifyError('Salaryadvances::approve', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function reject(): void {
        $this->EndReturnData = new stdClass();
        try {
            $uid = (int)$this->input->post('AdvanceUID');
            if (!$uid) throw new Exception('Invalid advance.');
            $this->load->model('dbwrite_model'); $this->load->model('dbwrite_ext_model');
            $res = $this->dbwrite_model->updateData('Transaction', 'SalaryAdvanceTbl',
                ['AdvanceStatus' => 'Rejected', 'UpdatedBy' => $this->_userUID()],
                ['AdvanceUID' => $uid, 'OrgUID' => $this->_orgUID(), 'AdvanceStatus' => 'Requested']
            );
            if ($res->Error) throw new Exception($res->Message);
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Advance rejected.';
        } catch (Exception $e) {
            $this->notifyError('Salaryadvances::reject', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function delete(): void {
        $this->EndReturnData = new stdClass();
        try {
            $uid = (int)$this->input->post('AdvanceUID');
            if (!$uid) throw new Exception('Invalid.');

            /* Fetch advance status before deletion for journal reversal */
            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';
            $advResp  = services_get(
                'api/salaryadvances/getPageDetails/1?orgId=' . $this->_orgUID() . '&RowLimit=1&Filter=' . urlencode(json_encode(['AdvanceUID' => $uid])),
                ['Authorization: Bearer ' . $jwtToken]
            );
            $advance     = $advResp->Data->Data[0] ?? null;
            $wasApproved = $advance && ($advance->AdvanceStatus ?? '') === 'Approved';

            $this->load->model('dbwrite_model'); $this->load->model('dbwrite_ext_model');
            $res = $this->dbwrite_model->updateData('Transaction', 'SalaryAdvanceTbl',
                ['IsDeleted' => 1, 'UpdatedBy' => $this->_userUID()],
                ['AdvanceUID' => $uid, 'OrgUID' => $this->_orgUID()]
            );
            if ($res->Error) throw new Exception($res->Message);

            if ($wasApproved) {
                try {
                    $this->load->library('accountledger');
                    $this->accountledger->reverseJournal('SalaryAdvance', $uid, $this->_userUID());
                } catch (Exception $ledgerEx) {
                }
            }

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Advance deleted.';
        } catch (Exception $e) {
            $this->notifyError('Salaryadvances::delete', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }
}
