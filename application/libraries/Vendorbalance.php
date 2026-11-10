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
                return null;
            }

            $debitNoteUID = (int)$writeDb->insert_id();

            return ['debitNoteUID' => $debitNoteUID, 'amount' => $amount];

        } catch (Exception $e) {
            notifyError('Vendorbalance::createPurchaseReturnDebitNote', $e);
            return null;
        }
    }

    public function createPurchaseCancelDebitNote(int $orgUID, int $vendorUID, int $purchTransUID, string $purchUniqueNumber, float $amount, int $userUID): ?array {
        try {
            if ($amount <= 0) return null;

            $writeDb = $this->CI->load->database('WriteDB', TRUE);
            $writeDb->db_debug = FALSE;

            $insertData = [
                'OrgUID'            => $orgUID,
                'PartyUID'          => $vendorUID,
                'PartyType'         => 'S',
                'SourceTransUID'    => $purchTransUID,
                'SourceTransNumber' => $purchUniqueNumber,
                'SourceModuleUID'   => 105,
                'DebitNoteToken'    => generate_uuid4(),
                'Amount'            => $amount,
                'Status'            => 'Pending',
                'Notes'             => 'Auto-created from cancelled Purchase ' . $purchUniqueNumber,
                'CreatedBy'         => $userUID,
                'UpdatedBy'         => $userUID,
                'IsActive'          => 1,
                'IsDeleted'         => 0,
                'IsCancelled'       => 0,
            ];

            $insertOk = $writeDb->insert('Transaction.TransDebitNoteTbl', $insertData);
            $dbErr    = $writeDb->error();

            if (!$insertOk || !empty($dbErr['code'])) {
                return null;
            }

            $debitNoteUID = (int)$writeDb->insert_id();

            return ['debitNoteUID' => $debitNoteUID, 'amount' => $amount];

        } catch (Exception $e) {
            notifyError('Vendorbalance::createPurchaseCancelDebitNote', $e);
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

            return ['creditNoteUID' => $creditNoteUID, 'amount' => $amount];

        } catch (Exception $e) {
            notifyError('Vendorbalance::createVendorCreditNote', $e);
            return null;
        }
    }

    // ── Balance recalculation — mirrors Customerbalance::recalcAndSync ────────
    //
    // Formula (Credit = we owe vendor, Debit = vendor owes us):
    //   SignedBalance = signedOpening
    //                + TotalPurchased        (increases what we owe)
    //                âˆ’ TotalPaid             (decreases what we owe)
    //                âˆ’ EffectivePRReturned   (PRs not already covered by a debit note)
    //                âˆ’ PendingDebitNotes     (vendor owes us — reduces payable)
    //                + PendingCreditNotes    (we owe vendor back — increases payable)
    //
    // Syncs to:
    //   1. Vendors.VendOpeningBalanceTbl  â†’ PendingBalance / PendingBalType
    //   2. Accounting.ChartOfAccounts     â†’ CurrentBalance / CurrentBalanceType
    public function recalcAndSync(int $orgUID, int $vendorUID, int $userUID): ?array {
        try {
            $jwtToken = $_COOKIE[getenv(‘JWT_COOKIE_NAME’)] ?? ‘’;
            $headers  = [‘Authorization: Bearer ‘ . $jwtToken];

            $resp     = services_get(‘api/vendors/getVendorsWithLedgerForBalance?’ . http_build_query([
                ‘orgId’     => $orgUID,
                ‘VendorUID’ => $vendorUID,
            ]), $headers);
            $vendRows = (!($resp->Error ?? true)) ? ($resp->Data ?? []) : [];
            if (empty($vendRows)) return null;

            $vend = $vendRows[0];

            $totalPurchased = (float)(services_get(‘api/vendors/getVendorTotalPurchased?’ . http_build_query([
                ‘orgId’ => $orgUID, ‘VendorUID’ => $vendorUID,
            ]), $headers)->Data ?? 0);

            $totalPaid = (float)(services_get(‘api/vendors/getVendorTotalPaid?’ . http_build_query([
                ‘orgId’ => $orgUID, ‘VendorUID’ => $vendorUID,
            ]), $headers)->Data ?? 0);

            $totalReturned = (float)(services_get(‘api/vendors/getVendorTotalReturned?’ . http_build_query([
                ‘orgId’ => $orgUID, ‘VendorUID’ => $vendorUID,
            ]), $headers)->Data ?? 0);

            $prCoveredByDN = (float)(services_get(‘api/vendors/getVendorPRCoveredByDebitNote?’ . http_build_query([
                ‘orgId’ => $orgUID, ‘VendorUID’ => $vendorUID,
            ]), $headers)->Data ?? 0);

            $effectiveReturned = max(0.0, $totalReturned - $prCoveredByDN);

            $notesResp          = services_get(‘api/vendors/getVendorPendingNoteTotals?’ . http_build_query([
                ‘orgId’ => $orgUID, ‘VendorUID’ => $vendorUID,
            ]), $headers);
            $pendingDebitNotes  = (float)((!($notesResp->Error ?? true)) ? ($notesResp->Data->PendingDebit  ?? 0) : 0);
            $pendingCreditNotes = (float)((!($notesResp->Error ?? true)) ? ($notesResp->Data->PendingCredit ?? 0) : 0);

            // Vendor opening: Credit = positive (we owe them), Debit = negative
            $signedOpening = ($vend->OpeningBalType === ‘Credit’)
                ?  (float)$vend->OpeningBalance
                : -(float)$vend->OpeningBalance;

            $signedBalance = round(
                $signedOpening + $totalPurchased - $totalPaid - $effectiveReturned - $pendingDebitNotes + $pendingCreditNotes,
                2
            );
            $newBalance = abs($signedBalance);
            $newBalType = ($signedBalance >= 0) ? ‘Credit’ : ‘Debit’;

            // 1. Update VendOpeningBalanceTbl -> PendingBalance
            services_post(‘api/vendors/updateVendorPendingBalance’, [
                ‘orgId’          => $orgUID,
                ‘userId’         => $userUID,
                ‘VendorUID’      => $vendorUID,
                ‘PendingBalance’ => $newBalance,
                ‘PendingBalType’ => $newBalType,
            ], $headers);

            // 2. Update Accounting.ChartOfAccounts -> CurrentBalance
            if (!empty($vend->LedgerUID)) {
                services_post(‘api/vendors/updateVendorBalanceInLedger’, [
                    ‘orgId’       => $orgUID,
                    ‘userId’      => $userUID,
                    ‘LedgerUID’   => $vend->LedgerUID,
                    ‘Balance’     => $newBalance,
                    ‘BalanceType’ => $newBalType,
                ], $headers);
            }

            return [‘balance’ => $newBalance, ‘type’ => $newBalType];

        } catch (Exception $e) {
            notifyError(‘Vendorbalance::recalcAndSync’, $e);
            return null;
        }
    }
}
