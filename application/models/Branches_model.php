<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Branches_model extends CI_Model {

    private $EndReturnData;
    private $ReadDb;

    public function __construct() {
        parent::__construct();
        $this->ReadDb = $this->load->database('ReadDB', TRUE);
    }

    public function getBranchListPaginated(int $orgUID, int $limit, int $offset, array $filter = [], string $langCode = 'en'): object {

        $this->EndReturnData = new stdClass();
        try {
            $useLang   = $langCode !== 'en';
            $lc        = $this->ReadDb->escape($langCode);
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

            $nameField        = $useLang ? "COALESCE(bl.Name, b.Name) AS Name"                               : "b.Name AS Name";
            $shortDescField   = $useLang ? "COALESCE(bl.ShortDescription, b.ShortDescription) AS ShortDescription" : "b.ShortDescription AS ShortDescription";
            $contactField     = $useLang ? "COALESCE(bl.ContactPerson, b.ContactPerson) AS ContactPerson"     : "b.ContactPerson AS ContactPerson";
            $addr1Field       = $useLang ? "COALESCE(bl.AddressLine1, b.AddressLine1) AS AddressLine1"        : "b.AddressLine1 AS AddressLine1";
            $addr2Field       = $useLang ? "COALESCE(bl.AddressLine2, b.AddressLine2) AS AddressLine2"        : "b.AddressLine2 AS AddressLine2";
            $stateTextField   = $useLang ? "COALESCE(bl.StateText, b.StateText) AS StateText"                 : "b.StateText AS StateText";
            $cityTextField    = $useLang ? "COALESCE(bl.CityText, b.CityText) AS CityText"                   : "b.CityText AS CityText";
            $landmarkField    = $useLang ? "COALESCE(bl.Landmark, b.Landmark) AS Landmark"                   : "b.Landmark AS Landmark";

            // ── List query ─────────────────────────────────────────────────
            $this->ReadDb->select([
                'b.BranchUID', $nameField, 'b.BranchCode', $shortDescField,
                $contactField, 'b.MobileNumber', 'b.AlternateNumber', 'b.CountryCode', 'b.CountryISO2', 'b.EmailAddress',
                'b.GSTIN', 'b.PANNumber', 'b.BranchTypeUID', 'b.IsHeadOffice', 'b.IsActive',
                $addr1Field, $addr2Field, 'b.Pincode', $landmarkField,
                'b.StateId', $stateTextField, 'b.CityId', $cityTextField,
                'b.IsWarehouse', 'b.IsDispatchPoint', 'b.IsSalesPoint', 'b.IsServiceCenter',
                'b.CreatedOn', 'b.UpdatedOn',
                'bt.Name AS BranchTypeName',
            ]);
            $this->ReadDb->from('Organisation.BranchesTbl b');
            $this->ReadDb->join('Organisation.BranchTypesTbl bt', 'bt.BranchTypeUID = b.BranchTypeUID', 'left');
            if ($useLang) {
                $this->ReadDb->join("Organisation.BranchesTbl_Lang AS bl", "bl.BranchUID = b.BranchUID AND bl.LangCode = {$lc}", 'left');
            }
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
            notifyError('Branches_model::getBranchListPaginated', $e);
            $this->EndReturnData->rows       = [];
            $this->EndReturnData->totalCount = 0;
            return $this->EndReturnData;
        }
    }

    public function getBranchList(int $orgUID, string $langCode = 'en'): array {
        try {
            $useLang = $langCode !== 'en';
            $lc      = $this->ReadDb->escape_str($langCode);
            if ($useLang) {
                $query = $this->ReadDb->query(
                    "SELECT b.BranchUID, COALESCE(bl.Name, b.Name) AS Name, b.BranchCode, b.IsHeadOffice
                     FROM Organisation.BranchesTbl b
                     LEFT JOIN Organisation.BranchesTbl_Lang bl ON bl.BranchUID = b.BranchUID AND bl.LangCode = '{$lc}'
                     WHERE b.OrgUID = ? AND b.IsDeleted = 0 AND b.IsActive = 1
                     ORDER BY b.IsHeadOffice DESC, COALESCE(bl.Name, b.Name) ASC",
                    [$orgUID]
                );
            } else {
                $query = $this->ReadDb->query(
                    'SELECT BranchUID, Name, BranchCode, IsHeadOffice
                     FROM Organisation.BranchesTbl
                     WHERE OrgUID = ? AND IsDeleted = 0 AND IsActive = 1
                     ORDER BY IsHeadOffice DESC, Name ASC',
                    [$orgUID]
                );
            }
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            notifyError('Branches_model::getBranchList', $e);
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
            notifyError('Branches_model::hasLinkedRecords', $e);
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
            notifyError('Branches_model::getBranchTypesList', $e);
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
            notifyError('Branches_model::getBranchCodeExists', $e);
            return FALSE;
        }
    }

    /**
     * Upsert a translated row into BranchesTbl_Lang.
     * @param int         $branchUID
     * @param string      $langCode
     * @param string|null $name
     * @param string|null $shortDescription
     * @param string|null $contactPerson
     * @param string|null $addressLine1
     * @param string|null $addressLine2
     * @param string|null $stateText
     * @param string|null $cityText
     * @param string|null $landmark
     * @param int         $userUID
     * @returns void
     */
    public function saveBranchLangRow(int $branchUID, string $langCode, ?string $name, ?string $shortDescription, ?string $contactPerson, ?string $addressLine1, ?string $addressLine2, ?string $stateText, ?string $cityText, ?string $landmark, int $userUID): void {
        try {
            $this->load->model('dbwrite_ext_model');
            $this->dbwrite_ext_model->execWrite(
                "INSERT INTO Organisation.BranchesTbl_Lang
                    (BranchUID, LangCode, Name, ShortDescription, ContactPerson, AddressLine1, AddressLine2, StateText, CityText, Landmark, CreatedBy, UpdatedBy)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    Name             = VALUES(Name),
                    ShortDescription = VALUES(ShortDescription),
                    ContactPerson    = VALUES(ContactPerson),
                    AddressLine1     = VALUES(AddressLine1),
                    AddressLine2     = VALUES(AddressLine2),
                    StateText        = VALUES(StateText),
                    CityText         = VALUES(CityText),
                    Landmark         = VALUES(Landmark),
                    UpdatedBy        = VALUES(UpdatedBy)",
                [$branchUID, $langCode, $name, $shortDescription, $contactPerson, $addressLine1, $addressLine2, $stateText, $cityText, $landmark, $userUID, $userUID]
            );
        } catch (Exception $e) {
            notifyError('Branches_model::saveBranchLangRow', $e);
        }
    }

}
