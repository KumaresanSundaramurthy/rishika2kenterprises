<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Todos extends MY_Controller {

    protected $EndReturnData;
    protected $pageModuleUID;

    public function __construct() {
        parent::__construct();
        $this->pageModuleUID = 20;
    }

    // ── Builds paginated table data (HTML rows + pagination) ─────────────────
    private function _fetchTableData(int $pageNo, int $limit, array $filter = []): object {
        $offset = max(0, ($pageNo - 1) * $limit);
        $this->load->model('todo_model');
        $result  = $this->todo_model->getTodoListPaginated(
            $this->_orgUID(), $this->_userUID(), $limit, $offset, $filter
        );
        $rowHtml = $this->load->view('todos/list', [
            'DataLists'      => $result->rows,
            'SerialNumber'   => $offset,
            'JwtData'        => $this->pageData['JwtData'],
            'CurrentUserUID' => $this->_userUID(),
        ], TRUE);
        $r = new stdClass();
        $r->RecordHtmlData = $rowHtml;
        $r->Pagination     = $this->globalservice->buildPagePaginationHtml(
            '/todos/getPageDetails', $result->totalCount, $pageNo, $limit
        );
        $r->TotalCount = $result->totalCount;
        return $r;
    }

    // ── My Tasks list view ────────────────────────────────────────────────────
    public function index(): void {
        if (empty($this->pageData['PageTitle'])) $this->pageData['PageTitle'] = 'My Tasks';
        try {
            $limit  = $this->_rowLimit();
            $filter = ['MyOnly' => true, 'Tab' => 'open'];
            $this->load->model('todo_model');
            $stats  = $this->todo_model->getTodoStats($this->_orgUID(), $this->_userUID());
            $users  = $this->todo_model->getUserList($this->_orgUID());
            $pd     = $this->_fetchTableData(1, $limit, $filter);
            $this->pageData['TodoStats']     = $stats;
            $this->pageData['UserList']      = $users;
            $this->pageData['ModRowData']    = $pd->RecordHtmlData;
            $this->pageData['ModPagination'] = $pd->Pagination;
            $this->pageData['TotalCount']    = $pd->TotalCount;
            $this->load->view('todos/index', $this->pageData);
        } catch (Exception $e) {
            redirect('dashboard', 'refresh');
        }
    }

    // ── AJAX: paginated table data ────────────────────────────────────────────
    public function getPageDetails(int $pageNo = 1): void {
        $this->EndReturnData = new stdClass();
        try {
            $filter = $this->input->post('Filter') ?: [];
            $pd = $this->_fetchTableData(max(1, (int)$pageNo), $this->_rowLimit(), $filter);
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

    // ── AJAX: single task detail (for edit modal) ─────────────────────────────
    public function getDetail(): void {
        $this->EndReturnData = new stdClass();
        try {
            $uid = (int)$this->input->post('TodoUID');
            if (!$uid) throw new Exception('Invalid task.');
            $this->load->model('todo_model');
            $todo = $this->todo_model->getTodoById($uid, $this->_orgUID());
            if (!$todo) throw new Exception('Task not found.');
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $todo;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── POST: create or update task ───────────────────────────────────────────
    public function save(): void {
        $this->EndReturnData = new stdClass();
        try {
            $p      = $this->input->post();
            $uid    = (int)($p['TodoUID']     ?? 0);
            $pageNo = max(1, (int)($p['CurrentPage'] ?? 1));
            $filter = is_array($p['Filter'] ?? null) ? $p['Filter'] : ['MyOnly' => true, 'Tab' => 'open'];

            $title = trim($p['Title'] ?? '');
            if (empty($title)) throw new Exception('Task title is required.');

            $assignedToUID = (int)($p['AssignedToUID'] ?? 0);
            if (!$assignedToUID) $assignedToUID = $this->_userUID();

            $priority = in_array($p['Priority'] ?? '', ['Low','Medium','High','Urgent'])
                ? $p['Priority'] : 'Medium';
            $status   = in_array($p['Status']   ?? '', ['Open','InProgress','OnHold','Completed','Cancelled'])
                ? $p['Status']   : 'Open';
            $dueDate  = preg_match('/^\d{4}-\d{2}-\d{2}$/', $p['DueDate'] ?? '')
                ? $p['DueDate']  : null;
            $refType  = in_array($p['RefType']  ?? '', [
                'General','Customer','Vendor','Invoice','PurchaseBill',
                'SalesReturn','PurchaseReturn','Expense'
            ]) ? $p['RefType'] : 'General';
            $refUID   = (int)($p['RefUID']   ?? 0) ?: null;
            $refLabel = trim($p['RefLabel']  ?? '') ?: null;
            $isPrivate = (int)(bool)($p['IsPrivate'] ?? 0);

            $data = [
                'OrgUID'        => $this->_orgUID(),
                'Title'         => $title,
                'Description'   => trim($p['Description'] ?? '') ?: null,
                'Priority'      => $priority,
                'Status'        => $status,
                'DueDate'       => $dueDate,
                'AssignedToUID' => $assignedToUID,
                'RefType'       => $refType,
                'RefUID'        => $refUID,
                'RefLabel'      => $refLabel,
                'IsPrivate'     => $isPrivate,
                'UpdatedByUID'  => $this->_userUID(),
            ];

            $this->load->model('dbwrite_model');
            if ($uid === 0) {
                $data['CreatedByUID'] = $this->_userUID();
                $res = $this->dbwrite_model->insertData('Task', 'TodoTbl', $data);
            } else {
                $res = $this->dbwrite_model->updateData(
                    'Task', 'TodoTbl', $data,
                    ['TodoUID' => $uid, 'OrgUID' => $this->_orgUID()]
                );
            }
            if ($res->Error) throw new Exception($res->Message);

            $this->EndReturnData->Message = $uid ? 'Task updated successfully.' : 'Task created successfully.';
            $pd = $this->_fetchTableData($pageNo, $this->_rowLimit(), $filter);
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

    // ── POST: change task status ──────────────────────────────────────────────
    public function changeStatus(): void {
        $this->EndReturnData = new stdClass();
        try {
            $uid    = (int)$this->input->post('TodoUID');
            $status = trim($this->input->post('Status') ?? '');
            $pageNo = max(1, (int)($this->input->post('CurrentPage') ?? 1));
            $filter = $this->input->post('Filter') ?: ['MyOnly' => true, 'Tab' => 'open'];
            if (!$uid) throw new Exception('Invalid task.');
            if (!in_array($status, ['Open','InProgress','OnHold','Completed','Cancelled'])) {
                throw new Exception('Invalid status.');
            }

            $data = ['Status' => $status, 'UpdatedByUID' => $this->_userUID()];
            if ($status === 'Completed') {
                $data['CompletedAt']    = date('Y-m-d H:i:s');
                $data['CompletedByUID'] = $this->_userUID();
            } elseif ($status === 'Open') {
                $data['CompletedAt']    = null;
                $data['CompletedByUID'] = null;
            }

            $this->load->model('dbwrite_model');
            $res = $this->dbwrite_model->updateData(
                'Task', 'TodoTbl', $data,
                ['TodoUID' => $uid, 'OrgUID' => $this->_orgUID()]
            );
            if ($res->Error) throw new Exception($res->Message);

            $pd = $this->_fetchTableData($pageNo, $this->_rowLimit(), $filter);
            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->Message        = 'Status updated.';
            $this->EndReturnData->RecordHtmlData = $pd->RecordHtmlData;
            $this->EndReturnData->Pagination     = $pd->Pagination;
            $this->EndReturnData->TotalCount     = $pd->TotalCount;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── POST: soft-delete task ────────────────────────────────────────────────
    public function delete(): void {
        $this->EndReturnData = new stdClass();
        try {
            $uid    = (int)$this->input->post('TodoUID');
            $pageNo = max(1, (int)($this->input->post('CurrentPage') ?? 1));
            $filter = $this->input->post('Filter') ?: ['MyOnly' => true, 'Tab' => 'open'];
            if (!$uid) throw new Exception('Invalid task.');
            $this->load->model('dbwrite_model');
            $res = $this->dbwrite_model->updateData(
                'Task', 'TodoTbl',
                ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedByUID' => $this->_userUID()],
                ['TodoUID' => $uid, 'OrgUID' => $this->_orgUID()]
            );
            if ($res->Error) throw new Exception($res->Message);
            $pd = $this->_fetchTableData($pageNo, $this->_rowLimit(), $filter);
            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->Message        = 'Task deleted.';
            $this->EndReturnData->RecordHtmlData = $pd->RecordHtmlData;
            $this->EndReturnData->Pagination     = $pd->Pagination;
            $this->EndReturnData->TotalCount     = $pd->TotalCount;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── AJAX: badge count for nav ─────────────────────────────────────────────
    public function getBadgeCount(): void {
        $this->EndReturnData = new stdClass();
        try {
            $this->load->model('todo_model');
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Count = $this->todo_model->getPendingBadgeCount(
                $this->_orgUID(), $this->_userUID()
            );
        } catch (Exception $e) {
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Count = 0;
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── AJAX: stats refresh ───────────────────────────────────────────────────
    public function getStats(): void {
        $this->EndReturnData = new stdClass();
        try {
            $this->load->model('todo_model');
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $this->todo_model->getTodoStats(
                $this->_orgUID(), $this->_userUID()
            );
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── AJAX: todos linked to an ERP entity ───────────────────────────────────
    public function getLinkedTodos(): void {
        $this->EndReturnData = new stdClass();
        try {
            $refType = trim($this->input->post('RefType') ?? '');
            $refUID  = (int)$this->input->post('RefUID');
            if (!$refType || !$refUID) throw new Exception('Invalid reference.');
            $this->load->model('todo_model');
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->rows  = $this->todo_model->getLinkedTodos(
                $this->_orgUID(), $refType, $refUID
            );
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }
}
