<?php defined('BASEPATH') or exit('No direct script access allowed');

class PackingList_model extends MY_Model {

    /** @var object */
    private $ReadDb;

    public function __construct() {
        parent::__construct();
        $this->ReadDb = $this->load->database('ReadDB', TRUE);
    }

    /**
     * Find an existing PL linked to a DC.
     * Returns the header row or null if none exists yet.
     *
     * @param int $transUID
     * @param int $orgUID
     * @return object|null
     */
    public function getByTransUID(int $transUID, int $orgUID): ?object {
        $this->ReadDb->from('Transaction.PackingListTbl');
        $this->ReadDb->where(['TransUID' => $transUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]);
        $this->ReadDb->limit(1);
        $row = $this->ReadDb->get()->row();
        return $row ?: null;
    }

    /**
     * Fetch a PL header by its own UID.
     *
     * @param int $plUID
     * @param int $orgUID
     * @return object|null
     */
    public function getByUID(int $plUID, int $orgUID): ?object {
        $this->ReadDb->from('Transaction.PackingListTbl');
        $this->ReadDb->where(['PackingListUID' => $plUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]);
        $this->ReadDb->limit(1);
        $row = $this->ReadDb->get()->row();
        return $row ?: null;
    }

    /**
     * Fetch saved PL items joined with product names from the DC line items.
     *
     * @param int $plUID
     * @param int $orgUID
     * @return array
     */
    public function getItems(int $plUID, int $orgUID): array {
        $this->ReadDb->select([
            'pli.PLItemUID',
            'pli.TransProductUID',
            'pli.ProductUID',
            'pli.Quantity',
            'pli.PackageKind',
            'pli.NumberOfPackages',
            'pli.NetWeight',
            'pli.GrossWeight',
            'pli.CBM',
            'tp.ProductName',
            'tp.PartNumber',
            'tp.PrimaryUnitName',
            'tp.Description',
        ]);
        $this->ReadDb->from('Transaction.PackingListItemsTbl pli');
        $this->ReadDb->join('Transaction.TransProductsTbl tp', 'tp.TransProdUID = pli.TransProductUID AND tp.IsDeleted = 0', 'LEFT');
        $this->ReadDb->where(['pli.PackingListUID' => $plUID, 'pli.OrgUID' => $orgUID, 'pli.IsDeleted' => 0]);
        $this->ReadDb->order_by('pli.PLItemUID', 'ASC');
        return $this->ReadDb->get()->result();
    }

    /**
     * List all packing lists for the org, newest first.
     * JOINs TransactionsTbl to surface the source document number and party name.
     *
     * @param int $orgUID
     * @return array
     */
    public function getAll(int $orgUID): array {
        $this->ReadDb->select([
            'PL.PackingListUID',
            'PL.UniqueNumber',
            'PL.PLDate',
            'PL.VehicleNumber',
            'PL.TransporterName',
            'PL.TransUID',
            'Ts.TransNumber',
            'Ts.ModuleUID',
            'COALESCE(Cust.Name, Vend.Name) AS PartyName',
        ]);
        $this->ReadDb->from('Transaction.PackingListTbl PL');
        $this->ReadDb->join('Transaction.TransactionsTbl Ts',   'Ts.TransUID = PL.TransUID AND Ts.IsDeleted = 0', 'LEFT');
        $this->ReadDb->join('Customers.CustomerTbl Cust',       "Cust.CustomerUID = Ts.PartyUID AND Ts.PartyType = 'C'", 'LEFT');
        $this->ReadDb->join('Vendors.VendorTbl Vend',           "Vend.VendorUID   = Ts.PartyUID AND Ts.PartyType = 'S'", 'LEFT');
        $this->ReadDb->where(['PL.OrgUID' => $orgUID, 'PL.IsDeleted' => 0]);
        $this->ReadDb->order_by('PL.PackingListUID', 'DESC');
        return $this->ReadDb->get()->result();
    }

    /**
     * Generate the next PL number for the org: PL-001, PL-002 ...
     * Reads the current max from the table, not a prefix system.
     *
     * @param int $orgUID
     * @return string
     */
    public function getNextNumber(int $orgUID): string {
        $this->ReadDb->select_max('PackingListUID', 'MaxUID');
        $this->ReadDb->from('Transaction.PackingListTbl');
        $this->ReadDb->where('OrgUID', $orgUID);
        $row = $this->ReadDb->get()->row();
        $next = $row ? ((int) $row->MaxUID + 1) : 1;
        return 'PL-' . str_pad($next, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Create or update a PL and its items atomically.
     * Pass $plUID = 0 for a new record.
     * Returns the PackingListUID on success; throws on failure.
     *
     * @param int   $plUID    0 = insert, >0 = update
     * @param array $header   Associative array of header fields
     * @param array $items    Array of item arrays
     * @param int   $orgUID
     * @param int   $userUID
     * @return int
     */
    public function save(int $plUID, array $header, array $items, int $orgUID, int $userUID): int {
        $this->load->model('dbwrite_model');
        $now = date('Y-m-d H:i:s');

        if ($plUID > 0) {
            // ── Update header ──────────────────────────────────────
            $headerRow = [
                'PLDate'          => $header['PLDate'],
                'VehicleNumber'   => $header['VehicleNumber']   ?: null,
                'LRNumber'        => $header['LRNumber']        ?: null,
                'TransporterName' => $header['TransporterName'] ?: null,
                'Notes'           => $header['Notes']           ?: null,
                'UpdatedBy'       => $userUID,
                'UpdatedOn'       => $now,
            ];
            $resp = $this->dbwrite_model->updateData(
                'Transaction', 'PackingListTbl', $headerRow,
                ['PackingListUID' => $plUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );
            if ($resp->Error) throw new Exception($resp->Message);

            // Soft-delete existing items then re-insert
            $this->dbwrite_model->updateData(
                'Transaction', 'PackingListItemsTbl',
                ['IsDeleted' => 1, 'UpdatedBy' => $userUID, 'UpdatedOn' => $now],
                ['PackingListUID' => $plUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );
        } else {
            // ── Insert header ──────────────────────────────────────
            $uniqueNumber = $this->getNextNumber($orgUID);
            $headerRow = [
                'OrgUID'          => $orgUID,
                'TransUID'        => $header['TransUID'],
                'UniqueNumber'    => $uniqueNumber,
                'PLDate'          => $header['PLDate'],
                'VehicleNumber'   => $header['VehicleNumber']   ?: null,
                'LRNumber'        => $header['LRNumber']        ?: null,
                'TransporterName' => $header['TransporterName'] ?: null,
                'Notes'           => $header['Notes']           ?: null,
                'IsDeleted'       => 0,
                'CreatedBy'       => $userUID,
                'CreatedOn'       => $now,
                'UpdatedBy'       => $userUID,
                'UpdatedOn'       => $now,
            ];
            $resp = $this->dbwrite_model->insertData('Transaction', 'PackingListTbl', $headerRow);
            if ($resp->Error) throw new Exception($resp->Message);
            $plUID = (int) $resp->InsertID;
            if ($plUID <= 0) throw new Exception('Failed to get PackingList ID after insert.');
        }

        // ── Insert items ───────────────────────────────────────────
        $itemRows = [];
        foreach ($items as $item) {
            $transProductUID = (int) ($item['TransProductUID'] ?? 0);
            $productUID      = (int) ($item['ProductUID']      ?? 0);
            if ($transProductUID <= 0 || $productUID <= 0) continue;
            $itemRows[] = [
                'PackingListUID'  => $plUID,
                'OrgUID'          => $orgUID,
                'TransProductUID' => $transProductUID,
                'ProductUID'      => $productUID,
                'Quantity'        => (float) ($item['Quantity']         ?? 0),
                'PackageKind'     => !empty($item['PackageKind'])       ? substr($item['PackageKind'], 0, 100) : null,
                'NumberOfPackages'=> (int)   ($item['NumberOfPackages'] ?? 0),
                'NetWeight'       => (float) ($item['NetWeight']        ?? 0),
                'GrossWeight'     => (float) ($item['GrossWeight']      ?? 0),
                'CBM'             => (float) ($item['CBM']              ?? 0),
                'IsDeleted'       => 0,
                'CreatedBy'       => $userUID,
                'CreatedOn'       => $now,
            ];
        }
        if (!empty($itemRows)) {
            $resp = $this->dbwrite_model->insertBatchData('Transaction', 'PackingListItemsTbl', $itemRows);
            if ($resp->Error) throw new Exception($resp->Message);
        }

        return $plUID;
    }
}
