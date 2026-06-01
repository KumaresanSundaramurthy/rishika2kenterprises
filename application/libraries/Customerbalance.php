<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Customerbalance — single source of truth for customer balance management.
 *
 * Used across all modules that affect a customer's outstanding balance:
 * Invoices, Payments, Sales Returns, Customer form, etc.
 *
 * Public method:
 *   recalcAndSync($orgUID, $customerUID, $userUID)
 *     Recalculates closing balance from all active transactions and syncs:
 *       1. Customers.CustOpeningBalanceTbl  — PendingBalance / PendingBalType
 *       2. Accounting.ChartOfAccounts       — CurrentBalance / CurrentBalanceType
 *       3. Upstash cache                    — ClosingBalance / ClosingBalType
 *     Returns ['balance' => float, 'type' => string] or null on failure.
 *
 * Formula:
 *   ClosingBalance = OpeningBalance + TotalInvoiced − TotalReceived − TotalReturned
 */
class Customerbalance {

    private $CI;

    public function __construct() {
        $this->CI =& get_instance();
    }

    // ── Credit Note: create when a paid/partial invoice is cancelled ──────────

    public function createCreditNote($orgUID, $customerUID, $transUID, $userUID) {
        try {
            $this->CI->load->model('dbwrite_model');

            // Get total payments made against this invoice
            $readDb = $this->CI->load->database('ReadDB', TRUE);
            $readDb->db_debug = FALSE;
            $readDb->select('COALESCE(SUM(Amount), 0) AS paid');
            $readDb->from('Transaction.PaymentsTbl');
            $readDb->where([
                'TransUID'         => (int)$transUID,
                'PartyType'        => 'C',
                'PaymentDirection' => 'In',
                'IsDeleted'        => 0,
                'IsTransferredToCreditNote' => 0,
            ]);
            $row       = $readDb->get()->row();
            $paidTotal = $row ? (float)$row->paid : 0.0;

            if ($paidTotal <= 0) return null; // nothing to credit

            $now = time();

            // Mark existing payments as transferred to credit note
            $writeDb = $this->CI->load->database('WriteDB', TRUE);
            $writeDb->db_debug = FALSE;
            $writeDb->where([
                'TransUID'                  => (int)$transUID,
                'PartyType'                 => 'C',
                'PaymentDirection'          => 'In',
                'IsDeleted'                 => 0,
                'IsTransferredToCreditNote' => 0,
            ]);
            $writeDb->update('Transaction.PaymentsTbl', [
                'IsTransferredToCreditNote' => 1,
                'UpdatedBy'                 => (int)$userUID,
            ]);

            // Create the credit note record
            $writeDb->insert('Transaction.CustomerCreditNoteTbl', [
                'OrgUID'         => (int)$orgUID,
                'CustomerUID'    => (int)$customerUID,
                'SourceTransUID' => (int)$transUID,
                'Amount'         => $paidTotal,
                'Status'         => 'Pending',
                'Notes'          => 'Auto-created on invoice cancellation',
                'CreatedOn'      => $now,
                'CreatedBy'      => (int)$userUID,
                'UpdatedOn'      => $now,
                'UpdatedBy'      => (int)$userUID,
                'IsActive'       => 1,
                'IsDeleted'      => 0,
            ]);

            return ['creditNoteUID' => (int)$writeDb->insert_id(), 'amount' => $paidTotal];

        } catch (Exception $e) {
            log_message('error', 'Customerbalance::createCreditNote failed: ' . $e->getMessage());
            return null;
        }
    }

    // ── Credit Note: apply to a future invoice ────────────────────────────────

    public function applyCreditNote($orgUID, $creditNoteUID, $targetTransUID, $userUID) {
        try {
            $this->CI->load->model('dbwrite_model');
            $writeDb = $this->CI->load->database('WriteDB', TRUE);
            $writeDb->db_debug = FALSE;

            // Use WriteDB to fetch — ensures we always see the latest committed data
            $writeDb->from('Transaction.CustomerCreditNoteTbl');
            $writeDb->where(['CreditNoteUID' => (int)$creditNoteUID, 'Status' => 'Pending', 'IsDeleted' => 0]);
            $cn = $writeDb->get()->row();
            if (!$cn) throw new Exception('Credit note not found or already used.');

            $now = time();

            // Create a new payment record (In) against the target invoice
            $writeDb->insert('Transaction.PaymentsTbl', [
                'OrgUID'                    => (int)$orgUID,
                'TransUID'                  => (int)$targetTransUID,
                'PartyUID'                  => (int)$cn->CustomerUID,
                'PartyType'                 => 'C',
                'Amount'                    => (float)$cn->Amount,
                'PaymentDirection'          => 'In',
                'Source'                    => 'CreditNote',
                'IsTransferredToCreditNote' => 0,
                'IsActive'                  => 1,
                'IsDeleted'                 => 0,
                'CreatedOn'                 => $now,
                'CreatedBy'                 => (int)$userUID,
                'UpdatedBy'                 => (int)$userUID,
            ]);
            $paymentUID = (int)$writeDb->insert_id();

            // Mark credit note as Applied
            $writeDb->where('CreditNoteUID', (int)$creditNoteUID);
            $writeDb->update('Transaction.CustomerCreditNoteTbl', [
                'Status'           => 'Applied',
                'AppliedTransUID'  => (int)$targetTransUID,
                'AppliedPaymentUID'=> $paymentUID,
                'UpdatedOn'        => $now,
                'UpdatedBy'        => (int)$userUID,
            ]);

            // Recalc balance
            $this->recalcAndSync($orgUID, $cn->CustomerUID, $userUID);

            return ['paymentUID' => $paymentUID];

        } catch (Exception $e) {
            log_message('error', 'Customerbalance::applyCreditNote failed: ' . $e->getMessage());
            throw $e;
        }
    }

    // ── Credit Note: mark as refunded (org physically returns money) ──────────

    public function refundCreditNote($orgUID, $creditNoteUID, $userUID) {
        try {
            $writeDb = $this->CI->load->database('WriteDB', TRUE);
            $writeDb->db_debug = FALSE;

            // Use WriteDB (not ReadDB) to look up the credit note — guarantees
            // we see our own writes when called immediately after createCreditNote()
            $writeDb->from('Transaction.CustomerCreditNoteTbl');
            $writeDb->where(['CreditNoteUID' => (int)$creditNoteUID, 'Status' => 'Pending', 'IsDeleted' => 0]);
            $cn = $writeDb->get()->row();
            if (!$cn) throw new Exception('Credit note not found or already used.');

            $now = time();

            $writeDb->where('CreditNoteUID', (int)$creditNoteUID);
            $writeDb->update('Transaction.CustomerCreditNoteTbl', [
                'Status'    => 'Refunded',
                'Notes'     => 'Refunded to customer',
                'UpdatedOn' => $now,
                'UpdatedBy' => (int)$userUID,
            ]);

            // Mark the original payment(s) as IsCancelled = 1 — payment is reversed/voided
            $writeDb->where([
                'TransUID'                  => (int)$cn->SourceTransUID,
                'PartyType'                 => 'C',
                'PaymentDirection'          => 'In',
                'IsDeleted'                 => 0,
                'IsTransferredToCreditNote' => 1,
            ])->update('Transaction.PaymentsTbl', [
                'IsCancelled' => 1,
                'UpdatedBy'   => (int)$userUID,
            ]);

            // Recalc balance
            $this->recalcAndSync($orgUID, $cn->CustomerUID, $userUID);

            return true;

        } catch (Exception $e) {
            log_message('error', 'Customerbalance::refundCreditNote failed: ' . $e->getMessage());
            throw $e;
        }
    }

    // ── Get pending credit notes for a customer ───────────────────────────────

    public function getPendingCreditNotes($orgUID, $customerUID) {
        try {
            $readDb = $this->CI->load->database('ReadDB', TRUE);
            $readDb->db_debug = FALSE;
            $readDb->select('CN.*, T.UniqueNumber AS SourceInvoiceNumber');
            $readDb->from('Transaction.CustomerCreditNoteTbl CN');
            $readDb->join('Transaction.TransactionsTbl T', 'T.TransUID = CN.SourceTransUID', 'left');
            $readDb->where([
                'CN.OrgUID'      => (int)$orgUID,
                'CN.CustomerUID' => (int)$customerUID,
                'CN.Status'      => 'Pending',
                'CN.IsDeleted'   => 0,
            ]);
            return $readDb->get()->result();
        } catch (Exception $e) {
            return [];
        }
    }

    // ── Pending credit notes (WriteDB) ───────────────────────────────────────
    // Must use WriteDB — refundCreditNote() writes Status='Refunded' to WriteDB,
    // and ReadDB may not yet reflect that update when recalcAndSync runs immediately after.

    private function _getPendingCreditNotesViaWriteDb($orgUID, $customerUID) {
        try {
            $writeDb = $this->CI->load->database('WriteDB', TRUE);
            $writeDb->db_debug = FALSE;
            $writeDb->select('COALESCE(SUM(Amount), 0) AS total');
            $writeDb->from('Transaction.CustomerCreditNoteTbl');
            $writeDb->where([
                'OrgUID'      => (int)$orgUID,
                'CustomerUID' => (int)$customerUID,
                'Status'      => 'Pending',
                'IsDeleted'   => 0,
            ]);
            $row = $writeDb->get()->row();
            return $row ? (float)$row->total : 0.0;
        } catch (Exception $e) {
            log_message('error', 'Customerbalance::_getPendingCreditNotesViaWriteDb failed: ' . $e->getMessage());
            return 0.0;
        }
    }

    // ── Balance recalculation ─────────────────────────────────────────────────

    public function recalcAndSync($orgUID, $customerUID, $userUID) {
        try {
            $this->CI->load->model('customers_model');

            $custRows = $this->CI->customers_model->getCustomersWithLedgerForBalance(
                (int)$orgUID, (int)$customerUID
            );
            if (empty($custRows)) return null;

            $cust = $custRows[0];

            $totalInvoiced      = $this->CI->customers_model->getCustomerTotalInvoiced($orgUID, $customerUID);
            $totalReceived      = $this->CI->customers_model->getCustomerTotalReceived($orgUID, $customerUID);
            $totalReturned      = $this->CI->customers_model->getCustomerTotalReturned($orgUID, $customerUID);
            // Query pending credit notes via WriteDB — guarantees we always see our own
            // writes (refundCreditNote updates Status on WriteDB; ReadDB may lag behind)
            $pendingCreditNotes = $this->_getPendingCreditNotesViaWriteDb($orgUID, $customerUID);

            $signedOpening = ($cust->OpeningBalType === 'Debit')
                ?  (float)$cust->OpeningBalance
                : -(float)$cust->OpeningBalance;

            $signedBalance = round(
                $signedOpening + $totalInvoiced - $totalReceived - $totalReturned - $pendingCreditNotes,
                2
            );
            $newBalance    = abs($signedBalance);
            $newBalType    = ($signedBalance >= 0) ? 'Debit' : 'Credit';

            // 1. Update CustOpeningBalanceTbl → PendingBalance (closing balance)
            $this->CI->customers_model->updateCustomerPendingBalance(
                $orgUID, $customerUID, $newBalance, $newBalType, $userUID
            );

            // 2. Update Accounting.ChartOfAccounts → CurrentBalance
            if (!empty($cust->LedgerUID)) {
                $this->CI->customers_model->updateCustomerBalanceInLedger(
                    $cust->LedgerUID, $newBalance, $newBalType, $userUID
                );
            }

            // 3. Sync Upstash cache → ClosingBalance
            $this->CI->cachehelper->upsertCustomer((int)$customerUID);

            return ['balance' => $newBalance, 'type' => $newBalType];

        } catch (Exception $e) {
            log_message('error', 'Customerbalance::recalcAndSync failed for CustomerUID=' . $customerUID . ': ' . $e->getMessage());
            return null;
        }
    }
}
