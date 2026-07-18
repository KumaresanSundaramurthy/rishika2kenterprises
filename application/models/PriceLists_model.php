<?php defined('BASEPATH') or exit('No direct script access allowed');

class PriceLists_model extends CI_Model {

    /** @var CI_DB_query_builder */
    private $ReadDb;

    public function __construct() {
        parent::__construct();
        $this->ReadDb = $this->load->database('ReadDB', TRUE);
    }

    /**
     * Insert PriceListTbl header row and return the new PriceListUID.
     * @param int   $orgUID
     * @param int   $userUID
     * @param array $d  validated data keys: Name, Status, Priority, ValidFrom, ValidTo,
     *                  Description, AssignedToType, Scope, GlobalBasedOn
     * @return int
     */
    public function createHeader(int $orgUID, int $userUID, array $d): int {
        $this->load->model('dbwrite_model');
        $resp = $this->dbwrite_model->insertData('Products', 'PriceListTbl', [
            'OrgUID'         => $orgUID,
            'Name'           => $d['Name'],
            'Status'         => (int) ($d['Status'] ?? 1),
            'Priority'       => (int) ($d['Priority'] ?? 1),
            'ValidFrom'      => $d['ValidFrom'] ?: null,
            'ValidTo'        => $d['ValidTo']   ?: null,
            'Description'    => $d['Description'] ?: null,
            'AssignedToType' => $d['AssignedToType'],
            'Scope'          => $d['Scope'],
            'GlobalBasedOn'  => $d['GlobalBasedOn'] ?? 'SellingPrice',
            'CreatedBy'      => $userUID,
            'UpdatedBy'      => $userUID,
        ]);
        if ($resp->Error) throw new Exception($resp->Message);
        return (int) $resp->ID;
    }

    /**
     * Upsert assignment rows — restores soft-deleted row if same key exists, else inserts.
     * @param int    $priceListUID
     * @param int    $orgUID
     * @param string $refType  'Group' | 'Customer'
     * @param int[]  $refUIDs
     * @return void
     */
    public function saveAssignments(int $priceListUID, int $orgUID, string $refType, array $refUIDs): void {
        $this->load->model('dbwrite_model');
        foreach ($refUIDs as $uid) {
            $uid = (int) $uid;
            if ($uid <= 0) continue;
            $this->ReadDb->select('AssignmentUID');
            $this->ReadDb->from('Products.PriceListAssignmentTbl');
            $this->ReadDb->where(['PriceListUID' => $priceListUID, 'OrgUID' => $orgUID, 'RefType' => $refType, 'RefUID' => $uid]);
            $this->ReadDb->limit(1);
            $existing = ($q = $this->ReadDb->get()) ? $q->row() : null;
            if ($existing) {
                $resp = $this->dbwrite_model->updateData('Products', 'PriceListAssignmentTbl',
                    ['IsDeleted' => 0],
                    ['AssignmentUID' => $existing->AssignmentUID]
                );
            } else {
                $resp = $this->dbwrite_model->insertData('Products', 'PriceListAssignmentTbl', [
                    'PriceListUID' => $priceListUID,
                    'OrgUID'       => $orgUID,
                    'RefType'      => $refType,
                    'RefUID'       => $uid,
                    'IsDeleted'    => 0,
                ]);
            }
            if ($resp->Error) throw new Exception($resp->Message);
        }
    }

    /**
     * Upsert discount rows — restores soft-deleted row if same CustomerTypeUID exists, else inserts.
     * @param int   $priceListUID
     * @param int   $orgUID
     * @param array $rows  each: [CustomerTypeUID, DiscountType, DiscountValue]
     * @return void
     */
    public function saveDiscounts(int $priceListUID, int $orgUID, array $rows): void {
        $this->load->model('dbwrite_model');
        foreach ($rows as $row) {
            $customerTypeUID = (int) ($row['CustomerTypeUID'] ?? 0);
            $this->ReadDb->select('DiscountUID');
            $this->ReadDb->from('Products.PriceListDiscountTbl');
            $this->ReadDb->where(['PriceListUID' => $priceListUID, 'OrgUID' => $orgUID, 'CustomerTypeUID' => $customerTypeUID]);
            $this->ReadDb->limit(1);
            $existing = ($q = $this->ReadDb->get()) ? $q->row() : null;
            $data = [
                'DiscountType'  => $row['DiscountType']  ?? 'Percentage',
                'DiscountValue' => (float) ($row['DiscountValue'] ?? 0),
                'IsDeleted'     => 0,
            ];
            if ($existing) {
                $resp = $this->dbwrite_model->updateData('Products', 'PriceListDiscountTbl',
                    $data,
                    ['DiscountUID' => $existing->DiscountUID]
                );
            } else {
                $resp = $this->dbwrite_model->insertData('Products', 'PriceListDiscountTbl', array_merge([
                    'PriceListUID'    => $priceListUID,
                    'OrgUID'          => $orgUID,
                    'CustomerTypeUID' => $customerTypeUID,
                ], $data));
            }
            if ($resp->Error) throw new Exception($resp->Message);
        }
    }

    /**
     * Upsert price rule rows — restores soft-deleted row if same natural key exists, else inserts.
     * Natural key: PriceListUID + OrgUID + ProductUID + MinQty + CustomerTypeUID.
     * @param int   $priceListUID
     * @param int   $orgUID
     * @param array $rows  each: [ProductUID, MinQty, MaxQty, CustomerTypeUID, Price]
     * @return void
     */
    public function saveRules(int $priceListUID, int $orgUID, array $rows): void {
        $this->load->model('dbwrite_model');
        foreach ($rows as $row) {
            $productUID      = (int) ($row['ProductUID']      ?? 0);
            $minQty          = (int) ($row['MinQty']          ?? 1);
            $customerTypeUID = (int) ($row['CustomerTypeUID'] ?? 0);
            $this->ReadDb->select('RuleUID');
            $this->ReadDb->from('Products.PriceListRuleTbl');
            $this->ReadDb->where([
                'PriceListUID'    => $priceListUID,
                'OrgUID'          => $orgUID,
                'ProductUID'      => $productUID,
                'MinQty'          => $minQty,
                'CustomerTypeUID' => $customerTypeUID,
            ]);
            $this->ReadDb->limit(1);
            $existing = ($q = $this->ReadDb->get()) ? $q->row() : null;
            $data = [
                'MaxQty'    => isset($row['MaxQty']) && $row['MaxQty'] !== '' ? (int) $row['MaxQty'] : null,
                'Price'     => (float) ($row['Price'] ?? 0),
                'IsDeleted' => 0,
            ];
            if ($existing) {
                $resp = $this->dbwrite_model->updateData('Products', 'PriceListRuleTbl',
                    $data,
                    ['RuleUID' => $existing->RuleUID]
                );
            } else {
                $resp = $this->dbwrite_model->insertData('Products', 'PriceListRuleTbl', array_merge([
                    'PriceListUID'    => $priceListUID,
                    'OrgUID'          => $orgUID,
                    'ProductUID'      => $productUID,
                    'MinQty'          => $minQty,
                    'CustomerTypeUID' => $customerTypeUID,
                ], $data));
            }
            if ($resp->Error) throw new Exception($resp->Message);
        }
    }

    /**
     * Paginated list of price lists for the list table.
     * @param int    $orgUID
     * @param int    $limit
     * @param int    $offset
     * @param array  $filter  keys: SearchAllData, StatusFilter (array), AssignedToFilter (array), ScopeFilter (array)
     * @return object  ->rows (array), ->totalCount (int)
     */
    public function getPriceListPaginated(int $orgUID, int $limit, int $offset, array $filter = []): object {
        $result             = new stdClass();
        $result->rows       = [];
        $result->totalCount = 0;
        try {
            $this->ReadDb->db_debug = false;
            $baseWhere = ['PL.OrgUID' => $orgUID, 'PL.IsDeleted' => 0];
            $search    = trim($filter['SearchAllData'] ?? '');

            $this->ReadDb->select('COUNT(PL.PriceListUID) AS TotalCount');
            $this->ReadDb->from('Products.PriceListTbl PL');
            $this->ReadDb->where($baseWhere);
            if ($search !== '') $this->ReadDb->like('PL.Name', $search);
            if (!empty($filter['StatusFilter']))     $this->ReadDb->where_in('PL.Status',         array_map('intval', (array) $filter['StatusFilter']));
            if (!empty($filter['AssignedToFilter'])) $this->ReadDb->where_in('PL.AssignedToType', (array) $filter['AssignedToFilter']);
            if (!empty($filter['ScopeFilter']))      $this->ReadDb->where_in('PL.Scope',          (array) $filter['ScopeFilter']);
            $cq = $this->ReadDb->get();
            $result->totalCount = (int) ($cq ? $cq->row()->TotalCount : 0);

            $this->ReadDb->select([
                'PL.PriceListUID', 'PL.Name', 'PL.AssignedToType', 'PL.Scope',
                'PL.GlobalBasedOn', 'PL.Status', 'PL.Priority',
                'PL.ValidFrom', 'PL.ValidTo', 'PL.UpdatedAt',
                "CONCAT(U.FirstName, ' ', U.LastName) AS UpdatedByName",
            ]);
            $this->ReadDb->from('Products.PriceListTbl PL');
            $this->ReadDb->join('Users.UserTbl U', 'U.UserUID = PL.UpdatedBy', 'left');
            $this->ReadDb->where($baseWhere);
            if ($search !== '') $this->ReadDb->like('PL.Name', $search);
            if (!empty($filter['StatusFilter']))     $this->ReadDb->where_in('PL.Status',         array_map('intval', (array) $filter['StatusFilter']));
            if (!empty($filter['AssignedToFilter'])) $this->ReadDb->where_in('PL.AssignedToType', (array) $filter['AssignedToFilter']);
            if (!empty($filter['ScopeFilter']))      $this->ReadDb->where_in('PL.Scope',          (array) $filter['ScopeFilter']);
            $this->ReadDb->order_by('PL.PriceListUID', 'DESC');
            $this->ReadDb->limit($limit, $offset);
            $dq = $this->ReadDb->get();
            $result->rows = $dq ? $dq->result() : [];
        } catch (Exception $e) {
            log_message('error', 'getPriceListPaginated: ' . $e->getMessage());
        }
        return $result;
    }

    /**
     * Soft-delete a price list (sets IsDeleted=1).
     * @param int $orgUID
     * @param int $priceListUID
     * @param int $userUID
     * @return void
     */
    public function softDelete(int $orgUID, int $priceListUID, int $userUID): void {
        $this->load->model('dbwrite_model');
        $resp = $this->dbwrite_model->updateData('Products', 'PriceListTbl', [
            'IsDeleted' => 1,
            'UpdatedBy' => $userUID,
        ], ['PriceListUID' => $priceListUID, 'OrgUID' => $orgUID]);
        if ($resp->Error) throw new Exception($resp->Message);
    }

    /**
     * Fetch a single price list header by UID.
     * @param int $orgUID
     * @param int $priceListUID
     * @return object|null
     */
    public function getByUID(int $orgUID, int $priceListUID): ?object {
        $this->ReadDb->db_debug = false;
        $this->ReadDb->select('*');
        $this->ReadDb->from('Products.PriceListTbl');
        $this->ReadDb->where(['OrgUID' => $orgUID, 'PriceListUID' => $priceListUID, 'IsDeleted' => 0]);
        $q = $this->ReadDb->get();
        return $q ? ($q->row() ?: null) : null;
    }

    /**
     * Fetch all data needed to populate the edit form: header + assignments + discounts + rules.
     * Rules include ProductName via join; rules array is flat (one row per ProductUID/MinQty/MaxQty/CustomerTypeUID).
     * @param int $orgUID
     * @param int $priceListUID
     * @return object|null  has ->Assignments (array), ->Discounts (array), ->Rules (array)
     */
    public function getForEdit(int $orgUID, int $priceListUID): ?object {
        $header = $this->getByUID($orgUID, $priceListUID);
        if (!$header) return null;

        $result = clone $header;
        $this->ReadDb->db_debug = false;

        $this->ReadDb->select('RefType, RefUID');
        $this->ReadDb->from('Products.PriceListAssignmentTbl');
        $this->ReadDb->where(['PriceListUID' => $priceListUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]);
        $q = $this->ReadDb->get();
        $result->Assignments = $q ? $q->result() : [];

        $this->ReadDb->select('CustomerTypeUID, DiscountType, DiscountValue');
        $this->ReadDb->from('Products.PriceListDiscountTbl');
        $this->ReadDb->where(['PriceListUID' => $priceListUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]);
        $q = $this->ReadDb->get();
        $result->Discounts = $q ? $q->result() : [];

        $this->ReadDb->select('R.ProductUID, P.ItemName AS ProductName, R.MinQty, R.MaxQty, R.CustomerTypeUID, R.Price');
        $this->ReadDb->from('Products.PriceListRuleTbl R');
        $this->ReadDb->join('Products.ProductTbl P', 'P.ProductUID = R.ProductUID', 'left');
        $this->ReadDb->where(['R.PriceListUID' => $priceListUID, 'R.OrgUID' => $orgUID, 'R.IsDeleted' => 0]);
        $this->ReadDb->order_by('R.ProductUID', 'ASC');
        $this->ReadDb->order_by('R.MinQty', 'ASC');
        $q = $this->ReadDb->get();
        $result->Rules = $q ? $q->result() : [];

        return $result;
    }

    /**
     * Update the PriceListTbl header row.
     * @param int   $priceListUID
     * @param int   $orgUID
     * @param int   $userUID
     * @param array $d
     * @return void
     */
    public function updateHeader(int $priceListUID, int $orgUID, int $userUID, array $d): void {
        $this->load->model('dbwrite_model');
        $resp = $this->dbwrite_model->updateData('Products', 'PriceListTbl', [
            'Name'           => $d['Name'],
            'Status'         => (int) ($d['Status'] ?? 1),
            'Priority'       => max(1, (int) ($d['Priority'] ?? 1)),
            'ValidFrom'      => $d['ValidFrom'] ?: null,
            'ValidTo'        => $d['ValidTo']   ?: null,
            'Description'    => $d['Description'] ?: null,
            'AssignedToType' => $d['AssignedToType'],
            'Scope'          => $d['Scope'],
            'GlobalBasedOn'  => $d['GlobalBasedOn'] ?? 'SellingPrice',
            'UpdatedBy'      => $userUID,
        ], ['PriceListUID' => $priceListUID, 'OrgUID' => $orgUID]);
        if ($resp->Error) throw new Exception($resp->Message);
    }

    /**
     * Fetch all active price lists with full child data for Upstash cache rebuild.
     * Returns array ordered by Priority DESC (highest priority first).
     * @param int $orgUID
     * @return array
     */
    public function getAllForCache(int $orgUID): array {
        $this->ReadDb->db_debug = false;

        $this->ReadDb->select([
            'PriceListUID', 'Name', 'Description', 'Status', 'Priority',
            'ValidFrom', 'ValidTo', 'AssignedToType', 'Scope', 'GlobalBasedOn',
        ]);
        $this->ReadDb->from('Products.PriceListTbl');
        $this->ReadDb->where(['OrgUID' => $orgUID, 'IsDeleted' => 0, 'Status' => 1]);
        $this->ReadDb->order_by('Priority', 'DESC');
        $hq      = $this->ReadDb->get();
        $headers = $hq ? $hq->result_array() : [];
        if (empty($headers)) return [];

        $plUIDs = array_column($headers, 'PriceListUID');

        $this->ReadDb->select('PriceListUID, RefType, RefUID');
        $this->ReadDb->from('Products.PriceListAssignmentTbl');
        $this->ReadDb->where(['OrgUID' => $orgUID, 'IsDeleted' => 0]);
        $this->ReadDb->where_in('PriceListUID', $plUIDs);
        $aq          = $this->ReadDb->get();
        $assignRows  = $aq ? $aq->result_array() : [];

        $this->ReadDb->select('PriceListUID, CustomerTypeUID, DiscountType, DiscountValue');
        $this->ReadDb->from('Products.PriceListDiscountTbl');
        $this->ReadDb->where(['OrgUID' => $orgUID, 'IsDeleted' => 0]);
        $this->ReadDb->where_in('PriceListUID', $plUIDs);
        $dq          = $this->ReadDb->get();
        $discRows    = $dq ? $dq->result_array() : [];

        $this->ReadDb->select('PriceListUID, ProductUID, MinQty, MaxQty, CustomerTypeUID, Price');
        $this->ReadDb->from('Products.PriceListRuleTbl');
        $this->ReadDb->where(['OrgUID' => $orgUID, 'IsDeleted' => 0]);
        $this->ReadDb->where_in('PriceListUID', $plUIDs);
        $rq          = $this->ReadDb->get();
        $ruleRows    = $rq ? $rq->result_array() : [];

        $assignMap = [];
        $discMap   = [];
        $rulesMap  = [];

        foreach ($assignRows as $r) {
            $assignMap[(int)$r['PriceListUID']][] = [
                'RefType' => $r['RefType'],
                'RefUID'  => (int)$r['RefUID'],
            ];
        }
        foreach ($discRows as $r) {
            $discMap[(int)$r['PriceListUID']][] = [
                'CustomerTypeUID' => (int)$r['CustomerTypeUID'],
                'DiscountType'    => $r['DiscountType'],
                'DiscountValue'   => (float)$r['DiscountValue'],
            ];
        }
        foreach ($ruleRows as $r) {
            $rulesMap[(int)$r['PriceListUID']][] = [
                'ProductUID'      => (int)$r['ProductUID'],
                'MinQty'          => (int)$r['MinQty'],
                'MaxQty'          => $r['MaxQty'] !== null ? (int)$r['MaxQty'] : null,
                'CustomerTypeUID' => (int)$r['CustomerTypeUID'],
                'Price'           => (float)$r['Price'],
            ];
        }

        $result = [];
        foreach ($headers as $h) {
            $uid      = (int)$h['PriceListUID'];
            $result[] = [
                'PriceListUID'   => $uid,
                'Name'           => $h['Name'],
                'Description'    => $h['Description'] ?: null,
                'Status'         => (int)$h['Status'],
                'Priority'       => (int)$h['Priority'],
                'ValidFrom'      => $h['ValidFrom'] ?: null,
                'ValidTo'        => $h['ValidTo']   ?: null,
                'AssignedToType' => $h['AssignedToType'],
                'Scope'          => $h['Scope'],
                'GlobalBasedOn'  => $h['GlobalBasedOn'],
                'Assignments'    => $assignMap[$uid] ?? [],
                'Discounts'      => $discMap[$uid]   ?? [],
                'Rules'          => $rulesMap[$uid]  ?? [],
            ];
        }
        return $result;
    }

    /**
     * Build the cache entry for a single active price list.
     * Returns null if the price list does not exist, is deleted, or is inactive (Status=0).
     * @param int $orgUID
     * @param int $priceListUID
     * @return array|null
     */
    public function getOneForCache(int $orgUID, int $priceListUID): ?array {
        $this->ReadDb->db_debug = false;

        $this->ReadDb->select([
            'PriceListUID', 'Name', 'Description', 'Status', 'Priority',
            'ValidFrom', 'ValidTo', 'AssignedToType', 'Scope', 'GlobalBasedOn',
        ]);
        $this->ReadDb->from('Products.PriceListTbl');
        $this->ReadDb->where(['OrgUID' => $orgUID, 'PriceListUID' => $priceListUID, 'IsDeleted' => 0, 'Status' => 1]);
        $hq = $this->ReadDb->get();
        $h  = $hq ? $hq->row_array() : null;
        if (!$h) return null;

        $uid = (int)$h['PriceListUID'];

        $this->ReadDb->select('RefType, RefUID');
        $this->ReadDb->from('Products.PriceListAssignmentTbl');
        $this->ReadDb->where(['OrgUID' => $orgUID, 'PriceListUID' => $uid, 'IsDeleted' => 0]);
        $aq = $this->ReadDb->get();
        $assignRows = $aq ? $aq->result_array() : [];

        $this->ReadDb->select('CustomerTypeUID, DiscountType, DiscountValue');
        $this->ReadDb->from('Products.PriceListDiscountTbl');
        $this->ReadDb->where(['OrgUID' => $orgUID, 'PriceListUID' => $uid, 'IsDeleted' => 0]);
        $dq = $this->ReadDb->get();
        $discRows = $dq ? $dq->result_array() : [];

        $this->ReadDb->select('ProductUID, MinQty, MaxQty, CustomerTypeUID, Price');
        $this->ReadDb->from('Products.PriceListRuleTbl');
        $this->ReadDb->where(['OrgUID' => $orgUID, 'PriceListUID' => $uid, 'IsDeleted' => 0]);
        $rq = $this->ReadDb->get();
        $ruleRows = $rq ? $rq->result_array() : [];

        return [
            'PriceListUID'   => $uid,
            'Name'           => $h['Name'],
            'Description'    => $h['Description'] ?: null,
            'Status'         => (int)$h['Status'],
            'Priority'       => (int)$h['Priority'],
            'ValidFrom'      => $h['ValidFrom'] ?: null,
            'ValidTo'        => $h['ValidTo']   ?: null,
            'AssignedToType' => $h['AssignedToType'],
            'Scope'          => $h['Scope'],
            'GlobalBasedOn'  => $h['GlobalBasedOn'],
            'Assignments'    => array_map(fn($r) => [
                'RefType' => $r['RefType'],
                'RefUID'  => (int)$r['RefUID'],
            ], $assignRows),
            'Discounts'      => array_map(fn($r) => [
                'CustomerTypeUID' => (int)$r['CustomerTypeUID'],
                'DiscountType'    => $r['DiscountType'],
                'DiscountValue'   => (float)$r['DiscountValue'],
            ], $discRows),
            'Rules'          => array_map(fn($r) => [
                'ProductUID'      => (int)$r['ProductUID'],
                'MinQty'          => (int)$r['MinQty'],
                'MaxQty'          => $r['MaxQty'] !== null ? (int)$r['MaxQty'] : null,
                'CustomerTypeUID' => (int)$r['CustomerTypeUID'],
                'Price'           => (float)$r['Price'],
            ], $ruleRows),
        ];
    }

    /**
     * Soft-delete all child records (assignments, discounts, rules) for a price list.
     * Called before upsert on update so removed rows become IsDeleted=1.
     * @param int $priceListUID
     * @param int $orgUID
     * @return void
     */
    public function softDeleteChildRecords(int $priceListUID, int $orgUID): void {
        $this->load->model('dbwrite_model');
        $where = ['PriceListUID' => $priceListUID, 'OrgUID' => $orgUID];
        $this->dbwrite_model->updateData('Products', 'PriceListAssignmentTbl', ['IsDeleted' => 1], $where);
        $this->dbwrite_model->updateData('Products', 'PriceListDiscountTbl',   ['IsDeleted' => 1], $where);
        $this->dbwrite_model->updateData('Products', 'PriceListRuleTbl',       ['IsDeleted' => 1], $where);
    }
}
