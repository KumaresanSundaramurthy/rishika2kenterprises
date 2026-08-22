<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Todo_model extends CI_Model {

    private $ReadDb;

    public function __construct() {
        parent::__construct();
        $this->ReadDb = $this->load->database('ReadDB', TRUE);
    }

    // ── Applies FROM / JOIN / WHERE conditions shared by count + list queries ──
    private function _applyFilters(int $orgUID, int $userUID, array $filter): void {
        $myOnly   = !empty($filter['MyOnly']);
        $priority = trim($filter['Priority'] ?? '');
        $search   = trim($filter['Search']   ?? '');
        $tab      = trim($filter['Tab']      ?? 'all');

        $this->ReadDb->from('Task.TodoTbl t');
        $this->ReadDb->join('Users.UserTbl a', 'a.UserUID = t.AssignedToUID AND a.IsDeleted = 0', 'left');
        $this->ReadDb->join('Users.UserTbl c', 'c.UserUID = t.CreatedByUID  AND c.IsDeleted = 0', 'left');
        $this->ReadDb->where(['t.OrgUID' => $orgUID, 't.IsDeleted' => 0]);

        if ($myOnly) {
            $this->ReadDb->group_start();
            $this->ReadDb->where('t.AssignedToUID', $userUID);
            $this->ReadDb->or_where('t.CreatedByUID', $userUID);
            $this->ReadDb->group_end();
        }

        if ($priority !== '') {
            $this->ReadDb->where('t.Priority', $priority);
        }

        switch ($tab) {
            case 'overdue':
                $this->ReadDb->where('t.DueDate <', date('Y-m-d'));
                $this->ReadDb->where_not_in('t.Status', ['Completed', 'Cancelled']);
                break;
            case 'today':
                $this->ReadDb->where('t.DueDate', date('Y-m-d'));
                $this->ReadDb->where_not_in('t.Status', ['Completed', 'Cancelled']);
                break;
            case 'open':
                $this->ReadDb->where_in('t.Status', ['Open', 'InProgress', 'OnHold']);
                break;
            case 'completed':
                $this->ReadDb->where('t.Status', 'Completed');
                break;
        }

        if ($search !== '') {
            $this->ReadDb->group_start();
            $this->ReadDb->like('t.Title',    $search);
            $this->ReadDb->or_like('t.Description', $search);
            $this->ReadDb->or_like('t.RefLabel',    $search);
            $this->ReadDb->group_end();
        }
    }

    /**
     * @param int   $orgUID
     * @param int   $userUID
     * @param int   $limit
     * @param int   $offset
     * @param array $filter  Keys: MyOnly, Priority, Search, Tab
     * @return object{rows:array,totalCount:int}
     */
    public function getTodoListPaginated(int $orgUID, int $userUID, int $limit, int $offset, array $filter = []): object {
        $result             = new stdClass();
        $result->rows       = [];
        $result->totalCount = 0;
        try {
            $this->_applyFilters($orgUID, $userUID, $filter);
            $result->totalCount = (int)$this->ReadDb->count_all_results();

            $this->ReadDb->select([
                't.TodoUID', 't.Title', 't.Description', 't.Priority', 't.Status',
                't.DueDate', 't.RefType', 't.RefUID', 't.RefLabel',
                't.IsPrivate', 't.CreatedByUID', 't.AssignedToUID',
                't.CompletedAt', 't.CreatedDate',
                "CONCAT(a.FirstName, ' ', a.LastName) AS AssignedName",
                "CONCAT(c.FirstName, ' ', c.LastName) AS CreatedByName",
            ]);
            $this->_applyFilters($orgUID, $userUID, $filter);
            $this->ReadDb->order_by("FIELD(t.Priority, 'Urgent', 'High', 'Medium', 'Low')", '', FALSE);
            $this->ReadDb->order_by('t.DueDate IS NULL', 'ASC', FALSE);
            $this->ReadDb->order_by('t.DueDate', 'ASC');
            $this->ReadDb->order_by('t.TodoUID', 'DESC');
            $this->ReadDb->limit($limit, $offset);
            $result->rows = $this->ReadDb->get()->result();
        } catch (Exception $e) {
            notifyError($e, 'Todo_model::getTodoListPaginated');
            log_message('error', 'Todo_model::getTodoListPaginated — ' . $e->getMessage());
        }
        return $result;
    }

    /**
     * @param int $todoUID
     * @param int $orgUID
     * @return object|null
     */
    public function getTodoById(int $todoUID, int $orgUID): ?object {
        try {
            $query = $this->ReadDb->query(
                "SELECT t.*,
                        CONCAT(a.FirstName, ' ', a.LastName) AS AssignedName,
                        CONCAT(c.FirstName, ' ', c.LastName) AS CreatedByName
                 FROM Task.TodoTbl t
                 LEFT JOIN Users.UserTbl a ON a.UserUID = t.AssignedToUID AND a.IsDeleted = 0
                 LEFT JOIN Users.UserTbl c ON c.UserUID = t.CreatedByUID  AND c.IsDeleted = 0
                 WHERE t.TodoUID = ? AND t.OrgUID = ? AND t.IsDeleted = 0
                 LIMIT 1",
                [$todoUID, $orgUID]
            );
            if (!$query || $query->num_rows() === 0) return null;
            return $query->row();
        } catch (Exception $e) {
            notifyError($e, 'Todo_model::getTodoById');
            return null;
        }
    }

    /**
     * @param int $orgUID
     * @param int $userUID
     * @return array{total:int,active:int,overdue:int,today:int,completed:int}
     */
    public function getTodoStats(int $orgUID, int $userUID): array {
        try {
            $query = $this->ReadDb->query(
                "SELECT
                     COUNT(*) AS total,
                     SUM(Status IN ('Open','InProgress','OnHold')) AS active,
                     SUM(Status = 'Completed') AS completed,
                     SUM(Status NOT IN ('Completed','Cancelled') AND DueDate < CURDATE()) AS overdue,
                     SUM(Status NOT IN ('Completed','Cancelled') AND DueDate = CURDATE()) AS today
                 FROM Task.TodoTbl
                 WHERE OrgUID = ? AND IsDeleted = 0
                   AND (AssignedToUID = ? OR CreatedByUID = ?)",
                [$orgUID, $userUID, $userUID]
            );
            $row = $query ? $query->row() : null;
            return [
                'total'     => (int)($row->total     ?? 0),
                'active'    => (int)($row->active     ?? 0),
                'completed' => (int)($row->completed  ?? 0),
                'overdue'   => (int)($row->overdue    ?? 0),
                'today'     => (int)($row->today      ?? 0),
            ];
        } catch (Exception $e) {
            notifyError($e, 'Todo_model::getTodoStats');
            return ['total' => 0, 'active' => 0, 'completed' => 0, 'overdue' => 0, 'today' => 0];
        }
    }

    /**
     * @param int $orgUID
     * @param int $userUID
     * @return int
     */
    public function getPendingBadgeCount(int $orgUID, int $userUID): int {
        try {
            $query = $this->ReadDb->query(
                "SELECT COUNT(*) AS cnt FROM Task.TodoTbl
                 WHERE OrgUID = ? AND IsDeleted = 0
                   AND Status IN ('Open','InProgress','OnHold')
                   AND (AssignedToUID = ? OR CreatedByUID = ?)",
                [$orgUID, $userUID, $userUID]
            );
            $row = $query ? $query->row() : null;
            return (int)($row->cnt ?? 0);
        } catch (Exception $e) {
            notifyError($e, 'Todo_model::getPendingBadgeCount');
            return 0;
        }
    }

    /**
     * @param int $orgUID
     * @return array
     */
    public function getUserList(int $orgUID): array {
        try {
            $query = $this->ReadDb->query(
                "SELECT UserUID, CONCAT(FirstName, ' ', LastName) AS FullName, EmailAddress
                 FROM Users.UserTbl
                 WHERE OrgUID = ? AND IsDeleted = 0 AND IsActive = 1
                 ORDER BY FirstName ASC, LastName ASC",
                [$orgUID]
            );
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            notifyError($e, 'Todo_model::getUserList');
            return [];
        }
    }

    /**
     * @param int    $orgUID
     * @param string $refType
     * @param int    $refUID
     * @return array
     */
    public function getLinkedTodos(int $orgUID, string $refType, int $refUID): array {
        try {
            $query = $this->ReadDb->query(
                "SELECT t.TodoUID, t.Title, t.Priority, t.Status, t.DueDate,
                        CONCAT(a.FirstName, ' ', a.LastName) AS AssignedName
                 FROM Task.TodoTbl t
                 LEFT JOIN Users.UserTbl a ON a.UserUID = t.AssignedToUID AND a.IsDeleted = 0
                 WHERE t.OrgUID = ? AND t.IsDeleted = 0
                   AND t.RefType = ? AND t.RefUID = ?
                 ORDER BY t.TodoUID DESC",
                [$orgUID, $refType, $refUID]
            );
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            notifyError($e, 'Todo_model::getLinkedTodos');
            return [];
        }
    }
}
