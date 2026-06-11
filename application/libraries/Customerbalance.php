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
                'CreatedBy'      => (int)$userUID,
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

    // ── Credit Note: create when a SR is saved without payment ───────────────

    public function createSalesReturnCreditNote($orgUID, $customerUID, $srTransUID, $srUniqueNumber, $amount, $userUID, $transDate = null) {
        try {
            log_message('debug', '[CN-TRACE] Start: orgUID=' . $orgUID . ' customerUID=' . $customerUID . ' srTransUID=' . $srTransUID . ' srUniqueNumber=' . $srUniqueNumber . ' amount=' . $amount);

            if ($amount <= 0) {
                log_message('debug', '[CN-TRACE] Abort: amount <= 0');
                return null;
            }

            $this->CI->load->model('transactions_model');

            // Prefix lookup for Credit Notes (ModuleUID = 107)
            $prefixData = $this->CI->transactions_model->getTransactionsPrefixDetails([
                'Prefix.OrgUID'    => (int)$orgUID,
                'Prefix.ModuleUID' => 107,
            ]);
            $prefix    = !empty($prefixData->Data) ? $prefixData->Data[0] : null;
            $prefixUID = $prefix ? (int)$prefix->PrefixUID : null;
            log_message('debug', '[CN-TRACE] Prefix lookup: prefixUID=' . ($prefixUID ?? 'NULL') . ' prefixName=' . ($prefix->Name ?? 'NOT FOUND') . ' totalFound=' . count($prefixData->Data ?? []));

            if (!$prefix) {
                log_message('error', '[CN-TRACE] WARNING: No prefix configured for Credit Notes (ModuleUID=107, OrgUID=' . $orgUID . '). CreditNoteNumber will be NULL.');
            }

            // Next sequential number (org-wide, never re-issues)
            $seq = $this->CI->transactions_model->getNextCreditNoteNumber($orgUID);
            log_message('debug', '[CN-TRACE] Next seq=' . $seq);

            // Build formatted number (same logic used across all transaction types)
            $cnNumber = null;
            if ($prefix) {
                $date   = $transDate ?: date('Y-m-d');
                $sep    = $prefix->Separator ?? '-';
                $parts  = [strtoupper($prefix->Name)];
                if (!empty($prefix->IncludeShortName) && !empty($prefix->ShortName)) {
                    $parts[] = strtoupper($prefix->ShortName);
                }
                if (!empty($prefix->IncludeFiscalYear)) {
                    $m      = (int)date('m', strtotime($date));
                    $yr     = (int)date('Y', strtotime($date));
                    $fyStart = $m >= 4 ? $yr : $yr - 1;
                    $parts[] = ($prefix->FiscalYearFormat ?? 'SHORT') === 'LONG'
                        ? $fyStart . '-' . ($fyStart + 1)
                        : str_pad($fyStart % 100, 2, '0', STR_PAD_LEFT) . '-' . str_pad(($fyStart + 1) % 100, 2, '0', STR_PAD_LEFT);
                }
                $pad     = (int)($prefix->NumberPadding ?? 1);
                $parts[] = $pad > 1 ? str_pad($seq, $pad, '0', STR_PAD_LEFT) : (string)$seq;
                $cnNumber = implode($sep, $parts);
            }
            log_message('debug', '[CN-TRACE] cnNumber=' . ($cnNumber ?? 'NULL'));

            $writeDb = $this->CI->load->database('WriteDB', TRUE);
            $writeDb->db_debug = FALSE;

            $insertData = [
                'OrgUID'             => (int)$orgUID,
                'CustomerUID'        => (int)$customerUID,
                'SourceTransUID'     => (int)$srTransUID,
                'SourceTransNumber'  => (string)$srUniqueNumber,
                'SourceModuleUID'    => 106,
                'CreditNoteNumber'   => $cnNumber,
                'CreditNoteToken'    => generate_uuid4(),
                'CreditNoteSeq'      => $seq,
                'CreditNoteType'     => 'SalesReturn',
                'PrefixUID'          => $prefixUID,
                'Amount'             => (float)$amount,
                'Status'             => 'Pending',
                'Notes'              => 'Auto-created from Sales Return ' . $srUniqueNumber,
                'CreatedBy'          => (int)$userUID,
                'UpdatedBy'          => (int)$userUID,
                'IsActive'           => 1,
                'IsDeleted'          => 0,
            ];
            log_message('debug', '[CN-TRACE] About to INSERT into Transaction.CustomerCreditNoteTbl with columns: ' . implode(', ', array_keys($insertData)));

            $insertOk = $writeDb->insert('Transaction.CustomerCreditNoteTbl', $insertData);
            $dbErr    = $writeDb->error();

            if (!$insertOk || !empty($dbErr['code'])) {
                log_message('error', '[CN-TRACE] INSERT FAILED — code=' . ($dbErr['code'] ?? '?') . ' message=' . ($dbErr['message'] ?? '?'));
                return null;
            }

            $creditNoteUID = (int)$writeDb->insert_id();
            log_message('debug', '[CN-TRACE] INSERT OK — CreditNoteUID=' . $creditNoteUID . ' Number=' . $cnNumber . ' SR=' . $srUniqueNumber . ' Amount=' . $amount);

            return ['creditNoteUID' => $creditNoteUID, 'creditNoteNumber' => $cnNumber, 'amount' => $amount];

        } catch (Exception $e) {
            log_message('error', '[CN-TRACE] EXCEPTION in createSalesReturnCreditNote: ' . $e->getMessage() . ' | File=' . $e->getFile() . ':' . $e->getLine());
            return null;
        }
    }

    // ── Debit Note: create when a paid SR is cancelled with Recover action ──────

    public function createDebitNote($orgUID, $customerUID, $sourceTransUID, $sourceTransNumber, $amount, $userUID, $writeDb = null) {
        try {
            if ($amount <= 0) return null;

            $this->CI->load->model('transactions_model');

            // Prefix lookup for Debit Notes (ModuleUID = 109)
            $prefixData = $this->CI->transactions_model->getTransactionsPrefixDetails([
                'Prefix.OrgUID'    => (int)$orgUID,
                'Prefix.ModuleUID' => 109,
            ]);
            $prefix    = !empty($prefixData->Data) ? $prefixData->Data[0] : null;
            $prefixUID = $prefix ? (int)$prefix->PrefixUID : null;

            // Next sequential number (org-wide, never re-issues)
            $seq = $this->CI->transactions_model->getNextDebitNoteNumber($orgUID);

            $dnNumber = null;
            if ($prefix) {
                $sep    = $prefix->Separator ?? '-';
                $parts  = [strtoupper($prefix->Name)];
                if (!empty($prefix->IncludeShortName) && !empty($prefix->ShortName)) {
                    $parts[] = strtoupper($prefix->ShortName);
                }
                if (!empty($prefix->IncludeFiscalYear)) {
                    $m       = (int)date('m');
                    $yr      = (int)date('Y');
                    $fyStart = $m >= 4 ? $yr : $yr - 1;
                    $parts[] = ($prefix->FiscalYearFormat ?? 'SHORT') === 'LONG'
                        ? $fyStart . '-' . ($fyStart + 1)
                        : str_pad($fyStart % 100, 2, '0', STR_PAD_LEFT) . '-' . str_pad(($fyStart + 1) % 100, 2, '0', STR_PAD_LEFT);
                }
                $pad     = (int)($prefix->NumberPadding ?? 1);
                $parts[] = $pad > 1 ? str_pad($seq, $pad, '0', STR_PAD_LEFT) : (string)$seq;
                $dnNumber = implode($sep, $parts);
            }

            if ($writeDb === null) {
                $writeDb = $this->CI->load->database('WriteDB', TRUE);
            }
            $writeDb->db_debug = FALSE;

            $writeDb->insert('Transaction.CustomerDebitNoteTbl', [
                'OrgUID'            => (int)$orgUID,
                'CustomerUID'       => (int)$customerUID,
                'SourceTransUID'    => (int)$sourceTransUID,
                'SourceTransNumber' => (string)$sourceTransNumber,
                'DebitNoteNumber'   => $dnNumber,
                'DebitNoteToken'    => generate_uuid4(),
                'DebitNoteSeq'      => $seq,
                'PrefixUID'         => $prefixUID,
                'Amount'            => (float)$amount,
                'Status'            => 'Pending',
                'Notes'             => 'Auto-created on SR cancellation (Recover from Customer)',
                'CreatedBy'         => (int)$userUID,
                'UpdatedBy'         => (int)$userUID,
                'IsActive'          => 1,
                'IsDeleted'         => 0,
            ]);

            $debitNoteUID = (int)$writeDb->insert_id();
            log_message('debug', '[DN-CREATE] DebitNoteUID=' . $debitNoteUID . ' Number=' . $dnNumber . ' SR=' . $sourceTransNumber . ' Amount=' . $amount);

            return ['debitNoteUID' => $debitNoteUID, 'debitNoteNumber' => $dnNumber, 'amount' => $amount];
        } catch (Exception $e) {
            log_message('error', 'Customerbalance::createDebitNote failed: ' . $e->getMessage());
            return null;
        }
    }

    // ── Debit Note: get pending notes for a customer ──────────────────────────

    public function getPendingDebitNotes($orgUID, $customerUID) {
        try {
            $readDb = $this->CI->load->database('ReadDB', TRUE);
            $readDb->db_debug = FALSE;
            $readDb->select('DN.*, T.UniqueNumber AS SourceSRNumber');
            $readDb->from('Transaction.CustomerDebitNoteTbl DN');
            $readDb->join('Transaction.TransactionsTbl T', 'T.TransUID = DN.SourceTransUID', 'left');
            $readDb->where([
                'DN.OrgUID'      => (int)$orgUID,
                'DN.CustomerUID' => (int)$customerUID,
                'DN.Status'      => 'Pending',
                'DN.IsDeleted'   => 0,
            ]);
            return $readDb->get()->result();
        } catch (Exception $e) {
            return [];
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

            $writeDb->where('CreditNoteUID', (int)$creditNoteUID);
            $writeDb->update('Transaction.CustomerCreditNoteTbl', [
                'Status'    => 'Refunded',
                'Notes'     => 'Refunded to customer',
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
                'CN.OrgUID'          => (int)$orgUID,
                'CN.CustomerUID'     => (int)$customerUID,
                'CN.Status'          => 'Pending',
                'CN.IsDeleted'       => 0,
                'CN.PaymentCleared'  => 0,
            ]);
            return $readDb->get()->result();
        } catch (Exception $e) {
            return [];
        }
    }

    // ── Pending credit/debit notes ────────────────────────────────────────────
    // Delegates to customers_model which holds the shared WriteDb connection —
    // avoids opening new TCP connections on every recalcAndSync call.

    private function _getPendingNoteTotals($orgUID, $customerUID) {
        $this->CI->load->model('customers_model');
        return $this->CI->customers_model->getCustomerPendingNoteTotals($orgUID, $customerUID);
    }

    // ── Balance recalculation ─────────────────────────────────────────────────

    public function recalcAndSync($orgUID, $customerUID, $userUID) {
        try {
            $this->CI->load->model('customers_model');

            // Log existing balance before recalc
            $preOB = $this->CI->customers_model->getCustomerOpeningBalance($orgUID, $customerUID);
            log_message('debug', '[BalanceRecalc-BEFORE] CustomerUID=' . $customerUID
                . ' PendingBalance=' . ($preOB ? $preOB->PendingBalance : 'NULL')
                . ' PendingBalType=' . ($preOB ? $preOB->PendingBalType : 'NULL'));

            $custRows = $this->CI->customers_model->getCustomersWithLedgerForBalance(
                (int)$orgUID, (int)$customerUID
            );
            if (empty($custRows)) {
                log_message('debug', '[BalanceRecalc] CustomerUID=' . $customerUID . ' — customer not found or inactive, recalc skipped');
                return null;
            }

            $cust = $custRows[0];

            $totalInvoiced  = $this->CI->customers_model->getCustomerTotalInvoiced($orgUID, $customerUID);
            $totalReceived  = $this->CI->customers_model->getCustomerTotalReceived($orgUID, $customerUID);
            $totalReturned  = $this->CI->customers_model->getCustomerTotalReturned($orgUID, $customerUID);
            // SRs that already have a pending/applied credit note must not be
            // subtracted a second time via totalReturned — pendingCreditNotes covers them.
            $srCoveredByCN     = $this->CI->customers_model->getCustomerSRCoveredByCreditNote($orgUID, $customerUID);
            $effectiveReturned = max(0.0, $totalReturned - $srCoveredByCN);
            [$pendingCreditNotes, $pendingDebitNotes] = $this->_getPendingNoteTotals($orgUID, $customerUID);

            $signedOpening = ($cust->OpeningBalType === 'Debit')
                ?  (float)$cust->OpeningBalance
                : -(float)$cust->OpeningBalance;

            $signedBalance = round(
                $signedOpening + $totalInvoiced - $totalReceived - $effectiveReturned - $pendingCreditNotes + $pendingDebitNotes,
                2
            );
            $newBalance    = abs($signedBalance);
            $newBalType    = ($signedBalance >= 0) ? 'Debit' : 'Credit';

            log_message('debug', '[BalanceRecalc-FORMULA] CustomerUID=' . $customerUID
                . ' Opening=' . $cust->OpeningBalance . '(' . $cust->OpeningBalType . ')'
                . ' Invoiced=' . $totalInvoiced
                . ' Received=' . $totalReceived
                . ' ReturnedRaw=' . $totalReturned
                . ' SRCoveredByCN=' . $srCoveredByCN
                . ' EffectiveReturned=' . $effectiveReturned
                . ' CreditNotes=' . $pendingCreditNotes
                . ' DebitNotes=' . $pendingDebitNotes
                . ' SignedBalance=' . $signedBalance
                . ' => NEW=' . $newBalance . '(' . $newBalType . ')');

            // 1. Update CustOpeningBalanceTbl → PendingBalance (closing balance)
            $this->CI->customers_model->updateCustomerPendingBalance(
                $orgUID, $customerUID, $newBalance, $newBalType, $userUID
            );

            log_message('debug', '[BalanceRecalc-AFTER] CustomerUID=' . $customerUID
                . ' Written=' . $newBalance . '(' . $newBalType . ')');

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
