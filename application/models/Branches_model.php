<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Branches_model extends CI_Model {

    private $EndReturnData;
    private $ReadDb;

    public function __construct() {
        parent::__construct();
        $this->ReadDb = $this->load->database('ReadDB', TRUE);
    }

    public function getBranchListPaginated(int $orgUID, int $limit, int $offset, array $filter = []): object {

        $this->EndReturnData = new stdClass();
        try {
            $baseWhere = ['b.OrgUID' => $orgUID, 'b.IsDeleted' => 0];

            // ── Count query (must run before the list query resets state) ──
            $this->ReadDb->from('Organisation.BranchesTbl b');
            $this->ReadDb->join('Organisation.BranchTypesTbl bt', 'bt.BranchTypeUID = b.BranchTypeUID', 'left');
            $this->ReadDb->where($baseWhere);
            if (!empty($filter['Search'])) {
                $search = trim($filter['Search']);
                $this->ReadDb->group_start();
                $this->ReadDb->like('b.Name',       $search);
                $this->ReadDb->or_like('b.BranchCode', $search);
                $this->ReadDb->or_like('b.GSTIN',      $search);
                $this->ReadDb->group_end();
            }
            $totalCount = (int) $this->ReadDb->count_all_results();

            // ── List query ─────────────────────────────────────────────────
            $this->ReadDb->select([
                'b.BranchUID', 'b.Name', 'b.BranchCode', 'b.ShortDescription',
                'b.ContactPerson', 'b.MobileNumber', 'b.AlternateNumber', 'b.CountryCode', 'b.CountryISO2', 'b.EmailAddress',
                'b.GSTIN', 'b.PANNumber', 'b.BranchTypeUID', 'b.IsHeadOffice', 'b.IsActive',
                'b.AddressLine1', 'b.AddressLine2', 'b.Pincode', 'b.Landmark',
                'b.StateId', 'b.StateText', 'b.CityId', 'b.CityText',
                'b.IsWarehouse', 'b.IsDispatchPoint', 'b.IsSalesPoint', 'b.IsServiceCenter',
                'b.CreatedOn', 'b.UpdatedOn',
                'bt.Name AS BranchTypeName',
            ]);
            $this->ReadDb->from('Organisation.BranchesTbl b');
            $this->ReadDb->join('Organisation.BranchTypesTbl bt', 'bt.BranchTypeUID = b.BranchTypeUID', 'left');
            $this->ReadDb->where($baseWhere);
            if (!empty($filter['Search'])) {
                $search = trim($filter['Search']);
                $this->ReadDb->group_start();
                $this->ReadDb->like('b.Name',       $search);
                $this->ReadDb->or_like('b.BranchCode', $search);
                $this->ReadDb->or_like('b.GSTIN',      $search);
                $this->ReadDb->group_end();
            }
            $this->ReadDb->order_by('b.IsHeadOffice', 'DESC');
            $this->ReadDb->order_by('b.Name', 'ASC');
            $this->ReadDb->limit($limit, $offset);
            $rows = $this->ReadDb->get()->result();

            $this->EndReturnData->rows       = $rows;
            $this->EndReturnData->totalCount = $totalCount;
            return $this->EndReturnData;

        } catch (Exception $e) {
            notifyError($e, 'Branches_model::getBranchListPaginated');
            $this->EndReturnData->rows       = [];
            $this->EndReturnData->totalCount = 0;
            return $this->EndReturnData;
        }
    }

    public function getBranchList(int $orgUID): array {
        try {
            $query = $this->ReadDb->query(
                'SELECT BranchUID, Name, BranchCode, IsHeadOffice
                 FROM Organisation.BranchesTbl
                 WHERE OrgUID = ? AND IsDeleted = 0 AND IsActive = 1
                 ORDER BY IsHeadOffice DESC, Name ASC',
                [$orgUID]
            );
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            notifyError($e, 'Branches_model::getBranchList');
            return [];
        }
    }

    /**
     * Returns true if the branch has any linked records in transaction tables.
     * @param int $uid
     * @param int $orgUID
     * @returns bool
     */
    public function hasLinkedRecords(int $uid, int $orgUID): bool {
        try {
            $sql = "SELECT 1 FROM Transaction.TransactionsTbl    WHERE BranchUID = ? AND OrgUID = ? AND IsDeleted = 0 LIMIT 1
                    UNION ALL
                    SELECT 1 FROM Transaction.ExpensesTbl         WHERE BranchUID = ? AND OrgUID = ? AND IsDeleted = 0 LIMIT 1
                    UNION ALL
                    SELECT 1 FROM Transaction.IndirectIncomeTbl   WHERE BranchUID = ? AND OrgUID = ? AND IsDeleted = 0 LIMIT 1
                    UNION ALL
                    SELECT 1 FROM Transaction.PayrollTbl          WHERE BranchUID = ? AND OrgUID = ? AND IsDeleted = 0 LIMIT 1";
            $query = $this->ReadDb->query($sql, [$uid, $orgUID, $uid, $orgUID, $uid, $orgUID, $uid, $orgUID]);
            return $query && $query->num_rows() > 0;
        } catch (Exception $e) {
            notifyError($e, 'Branches_model::hasLinkedRecords');
            return FALSE;
        }
    }

    public function getBranchTypesList(): array {
        try {
            $query = $this->ReadDb->query(
                'SELECT BranchTypeUID, Name FROM Organisation.BranchTypesTbl ORDER BY Name ASC'
            );
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            notifyError($e, 'Branches_model::getBranchTypesList');
            return [];
        }
    }

    public function getBranchCodeExists(int $orgUID, string $code, int $excludeUID = 0): bool {
        try {
            $this->ReadDb->select('BranchUID');
            $this->ReadDb->from('Organisation.BranchesTbl');
            $this->ReadDb->where(['OrgUID' => $orgUID, 'BranchCode' => strtoupper($code), 'IsDeleted' => 0]);
            if ($excludeUID > 0) {
                $this->ReadDb->where('BranchUID !=', $excludeUID);
            }
            $query = $this->ReadDb->get();
            return $query && $query->num_rows() > 0;
        } catch (Exception $e) {
            notifyError($e, 'Branches_model::getBranchCodeExists');
            return FALSE;
        }
    }

}
