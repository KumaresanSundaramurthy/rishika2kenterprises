<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Vendorbalance — vendor-side counterpart to Customerbalance.
 *
 * Manages debit/credit notes created when Purchase Returns are saved or cancelled.
 * Both note types are stored in the unified TransDebitNoteTbl / TransCreditNoteTbl
 * with PartyType='S' to distinguish vendor records from customer records ('C').
 *
 * TransDebitNoteTbl  — vendor owes us (created when PR has no full cash refund yet)
 * TransCreditNoteTbl — we owe vendor back (created when PR is cancelled with 'recover')
 *
 * Full vendor balance recalc (recalcAndSync) is stubbed — to be implemented
 * when the vendor balance tracking module is built.
 */
class Vendorbalance {

    private $CI;

    public function __construct() {
        $this->CI =& get_instance();
    }

    // ── Debit Note: create when PR is saved without full cash refund received ──

    public function createPurchaseReturnDebitNote($orgUID, $vendorUID, $prTransUID, $prUniqueNumber, $amount, $userUID, $transDate = null) {
        try {
            if ($amount <= 0) return null;

            $writeDb = $this->CI->load->database('WriteDB', TRUE);
            $writeDb->db_debug = FALSE;

            $insertData = [
                'OrgUID'            => (int)$orgUID,
                'PartyUID'          => (int)$vendorUID,
                'PartyType'         => 'S',
                'SourceTransUID'    => (int)$prTransUID,
                'SourceTransNumber' => (string)$prUniqueNumber,
                'SourceModuleUID'   => 108,
                'DebitNoteToken'    => generate_uuid4(),
                'Amount'            => (float)$amount,
                'Status'            => 'Pending',
                'Notes'             => 'Auto-created from Purchase Return ' . $prUniqueNumber,
                'CreatedBy'         => (int)$userUID,
                'UpdatedBy'         => (int)$userUID,
                'IsActive'          => 1,
                'IsDeleted'         => 0,
                'IsCancelled'       => 0,
            ];

            $insertOk = $writeDb->insert('Transaction.TransDebitNoteTbl', $insertData);
            $dbErr    = $writeDb->error();

            if (!$insertOk || !empty($dbErr['code'])) {
                log_message('error', '[VDN-TRACE] INSERT FAILED — ' . ($dbErr['message'] ?? '?'));
                return null;
            }

            $debitNoteUID = (int)$writeDb->insert_id();
            log_message('debug', '[VDN-TRACE] INSERT OK — TransDebitNoteUID=' . $debitNoteUID . ' PR=' . $prUniqueNumber . ' Amount=' . $amount);

            return ['debitNoteUID' => $debitNoteUID, 'amount' => $amount];

        } catch (Exception $e) {
            log_message('error', 'Vendorbalance::createPurchaseReturnDebitNote failed: ' . $e->getMessage());
            return null;
        }
    }

    // ── Credit Note: create when PR is cancelled with 'recover' action ─────────
    // Tracks: we owe vendor back the refund they had already given us.

    public function createVendorCreditNote($orgUID, $vendorUID, $sourceTransUID, $sourceTransNumber, $amount, $userUID, $writeDb = null) {
        try {
            if ($amount <= 0) return null;

            if ($writeDb === null) {
                $writeDb = $this->CI->load->database('WriteDB', TRUE);
            }
            $writeDb->db_debug = FALSE;

            $writeDb->insert('Transaction.TransCreditNoteTbl', [
                'OrgUID'            => (int)$orgUID,
                'PartyUID'          => (int)$vendorUID,
                'PartyType'         => 'S',
                'SourceTransUID'    => (int)$sourceTransUID,
                'SourceTransNumber' => (string)$sourceTransNumber,
                'SourceModuleUID'   => 108,
                'CreditNoteToken'   => generate_uuid4(),
                'Amount'            => (float)$amount,
                'Status'            => 'Pending',
                'Notes'             => 'Auto-created on PR cancellation (Recover — we owe vendor back)',
                'CreatedBy'         => (int)$userUID,
                'UpdatedBy'         => (int)$userUID,
                'IsActive'          => 1,
                'IsDeleted'         => 0,
            ]);

            $creditNoteUID = (int)$writeDb->insert_id();
            log_message('debug', '[VCN-CREATE] TransCreditNoteUID=' . $creditNoteUID . ' PR=' . $sourceTransNumber . ' Amount=' . $amount);

            return ['creditNoteUID' => $creditNoteUID, 'amount' => $amount];

        } catch (Exception $e) {
            log_message('error', 'Vendorbalance::createVendorCreditNote failed: ' . $e->getMessage());
            return null;
        }
    }

    // ── Stub: recalc vendor balance (future implementation) ───────────────────

    public function recalcAndSync($orgUID, $vendorUID, $userUID) {
        return null;
    }
}
