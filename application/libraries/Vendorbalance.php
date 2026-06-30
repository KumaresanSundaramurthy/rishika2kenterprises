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

    /** @var object */
    private $CI;

    public function __construct() {
        $this->CI =& get_instance();
    }

    // ── Debit Note: create when PR is saved without full cash refund received ──

    public function createPurchaseReturnDebitNote(int $orgUID, int $vendorUID, int $prTransUID, string $prUniqueNumber, float $amount, int $userUID, ?string $_transDate = null) {
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

    public function createVendorCreditNote(int $orgUID, int $vendorUID, int $sourceTransUID, string $sourceTransNumber, float $amount, int $userUID, $writeDb = null) {
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

    // ── Balance recalculation — mirrors Customerbalance::recalcAndSync ────────
    //
    // Formula (Credit = we owe vendor, Debit = vendor owes us):
    //   SignedBalance = signedOpening
    //                + TotalPurchased        (increases what we owe)
    //                − TotalPaid             (decreases what we owe)
    //                − EffectivePRReturned   (PRs not already covered by a debit note)
    //                − PendingDebitNotes     (vendor owes us — reduces payable)
    //                + PendingCreditNotes    (we owe vendor back — increases payable)
    //
    // Syncs to:
    //   1. Vendors.VendOpeningBalanceTbl  → PendingBalance / PendingBalType
    //   2. Accounting.ChartOfAccounts     → CurrentBalance / CurrentBalanceType
    //   3. Upstash cache                  → via cachehelper->upsertVendor()

    public function recalcAndSync(int $orgUID, int $vendorUID, int $userUID): ?array {
        try {
            $this->CI->load->model('vendors_model');

            $vendRows = $this->CI->vendors_model->getVendorsWithLedgerForBalance(
                (int)$orgUID, (int)$vendorUID
            );
            if (empty($vendRows)) {
                log_message('debug', '[VendBalanceRecalc] VendorUID=' . $vendorUID . ' — vendor not found or inactive, recalc skipped');
                return null;
            }

            $vend = $vendRows[0];

            $totalPurchased   = $this->CI->vendors_model->getVendorTotalPurchased($orgUID, $vendorUID);
            $totalPaid        = $this->CI->vendors_model->getVendorTotalPaid($orgUID, $vendorUID);
            $totalReturned    = $this->CI->vendors_model->getVendorTotalReturned($orgUID, $vendorUID);
            $prCoveredByDN    = $this->CI->vendors_model->getVendorPRCoveredByDebitNote($orgUID, $vendorUID);
            $effectiveReturned = max(0.0, $totalReturned - $prCoveredByDN);
            [$pendingDebitNotes, $pendingCreditNotes] = $this->CI->vendors_model->getVendorPendingNoteTotals($orgUID, $vendorUID);

            // Vendor opening: Credit = positive (we owe them), Debit = negative
            $signedOpening = ($vend->OpeningBalType === 'Credit')
                ?  (float)$vend->OpeningBalance
                : -(float)$vend->OpeningBalance;

            $signedBalance = round(
                $signedOpening + $totalPurchased - $totalPaid - $effectiveReturned - $pendingDebitNotes + $pendingCreditNotes,
                2
            );
            $newBalance  = abs($signedBalance);
            $newBalType  = ($signedBalance >= 0) ? 'Credit' : 'Debit';

            log_message('debug', '[VendBalanceRecalc] VendorUID=' . $vendorUID
                . ' Opening=' . $vend->OpeningBalance . '(' . $vend->OpeningBalType . ')'
                . ' Purchased=' . $totalPurchased
                . ' Paid=' . $totalPaid
                . ' ReturnedRaw=' . $totalReturned
                . ' PRCoveredByDN=' . $prCoveredByDN
                . ' EffectiveReturned=' . $effectiveReturned
                . ' PendingDN=' . $pendingDebitNotes
                . ' PendingCN=' . $pendingCreditNotes
                . ' Signed=' . $signedBalance
                . ' => NEW=' . $newBalance . '(' . $newBalType . ')');

            // 1. Update VendOpeningBalanceTbl → PendingBalance
            $this->CI->vendors_model->updateVendorPendingBalance(
                $orgUID, $vendorUID, $newBalance, $newBalType, $userUID
            );

            // 2. Update Accounting.ChartOfAccounts → CurrentBalance
            if (!empty($vend->LedgerUID)) {
                $this->CI->vendors_model->updateVendorBalanceInLedger(
                    $vend->LedgerUID, $newBalance, $newBalType, $userUID
                );
            }

            // 3. Sync Upstash cache
            $this->CI->cachehelper->upsertVendor((int)$vendorUID);

            return ['balance' => $newBalance, 'type' => $newBalType];

        } catch (Exception $e) {
            log_message('error', 'Vendorbalance::recalcAndSync failed for VendorUID=' . $vendorUID . ': ' . $e->getMessage());
            return null;
        }
    }
}
