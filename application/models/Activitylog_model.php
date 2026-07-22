<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Activitylog_model extends CI_Model {

    private $ReadDb;

    public function __construct() {
        parent::__construct();
        $this->ReadDb = $this->load->database('ReadDB', TRUE);
    }

    /**
     * @param int   $orgUID
     * @param array $filter  Keys: DateFrom, DateTo, Module, Action, UserUID, EntityType, Search
     * @param int   $limit
     * @param int   $offset
     * @return array
     */
    public function getAuditLogs(int $orgUID, array $filter, int $limit, int $offset): array {
        $this->ReadDb->db_debug = FALSE;
        $this->_applyBaseQuery($orgUID, $filter);
        $this->ReadDb->select([
            'al.AuditUID',
            'al.UserUID',
            'al.UserName',
            'al.Action',
            'al.EntityType',
            'al.Module',
            'al.AuditCategory',
            'al.Result',
            'al.Source',
            'al.EntityUID',
            'al.EntityRef',
            'al.Summary',
            'al.IPAddress',
            'al.DeviceType',
            'al.Details',
            'al.CreatedOn',
        ]);
        $this->ReadDb->order_by('al.AuditUID', 'DESC');
        $this->ReadDb->limit($limit, $offset);
        $result = $this->ReadDb->get();
        return $result ? $result->result() : [];
    }

    /**
     * @param int   $orgUID
     * @param array $filter
     * @return int
     */
    public function getAuditLogCount(int $orgUID, array $filter): int {
        $this->ReadDb->db_debug = FALSE;
        $this->_applyBaseQuery($orgUID, $filter);
        return (int) $this->ReadDb->count_all_results();
    }

    /**
     * @param int    $orgUID
     * @param string $entityType
     * @param int    $entityUID
     * @param int    $limit
     * @return array
     */
    public function getEntityHistory(int $orgUID, string $entityType, int $entityUID, int $limit = 50): array {
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->select([
            'al.AuditUID',
            'al.UserName',
            'al.Action',
            'al.EntityRef',
            'al.Summary',
            'al.Details',
            'al.IPAddress',
            'al.CreatedOn',
        ]);
        $this->ReadDb->from('Security.UserAuditLogTbl al');
        $this->ReadDb->where([
            'al.OrgUID'     => $orgUID,
            'al.EntityType' => $entityType,
            'al.EntityUID'  => $entityUID,
        ]);
        $this->ReadDb->order_by('al.AuditUID', 'DESC');
        $this->ReadDb->limit($limit);
        $result = $this->ReadDb->get();
        return $result ? $result->result() : [];
    }

    /**
     * @param int $orgUID
     * @return array
     */
    public function getDistinctModules(int $orgUID): array {
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->select('DISTINCT al.Module');
        $this->ReadDb->from('Security.UserAuditLogTbl al');
        $this->ReadDb->where('al.OrgUID', $orgUID);
        $this->ReadDb->where('al.Module IS NOT NULL');
        $this->ReadDb->where("al.Module != ''");
        $this->ReadDb->order_by('al.Module', 'ASC');
        $result = $this->ReadDb->get();
        if (!$result) return [];
        return array_column($result->result_array(), 'Module');
    }

    /**
     * @param int $orgUID
     * @return array
     */
    public function getDistinctUsers(int $orgUID): array {
        $this->ReadDb->db_debug = FALSE;
        $this->ReadDb->select('DISTINCT al.UserUID, al.UserName');
        $this->ReadDb->from('Security.UserAuditLogTbl al');
        $this->ReadDb->where('al.OrgUID', $orgUID);
        $this->ReadDb->where('al.UserUID IS NOT NULL');
        $this->ReadDb->where("al.UserUID != 0");
        $this->ReadDb->order_by('al.UserName', 'ASC');
        $result = $this->ReadDb->get();
        return $result ? $result->result() : [];
    }

    /**
     * @param int   $orgUID
     * @param array $filter
     * @return void
     */
    private function _applyBaseQuery(int $orgUID, array $filter): void {
        $this->ReadDb->from('Security.UserAuditLogTbl al');
        $this->ReadDb->where('al.OrgUID', $orgUID);

        if (!empty($filter['DateFrom'])) {
            $this->ReadDb->where('DATE(al.CreatedOn) >=', $filter['DateFrom']);
        }
        if (!empty($filter['DateTo'])) {
            $this->ReadDb->where('DATE(al.CreatedOn) <=', $filter['DateTo']);
        }
        if (!empty($filter['Module'])) {
            $this->ReadDb->where('al.Module', $filter['Module']);
        }
        if (!empty($filter['Action'])) {
            $this->ReadDb->where('al.Action', $filter['Action']);
        }
        if (!empty($filter['UserUID'])) {
            $this->ReadDb->where('al.UserUID', (int)$filter['UserUID']);
        }
        if (!empty($filter['EntityType'])) {
            $this->ReadDb->where('al.EntityType', $filter['EntityType']);
        }
        if (!empty($filter['Search'])) {
            $term = $filter['Search'];
            $this->ReadDb->group_start();
            $this->ReadDb->like('al.EntityRef', $term);
            $this->ReadDb->or_like('al.Summary', $term);
            $this->ReadDb->or_like('al.UserName', $term);
            $this->ReadDb->or_like('al.Action', $term);
            $this->ReadDb->group_end();
        }
    }

}
