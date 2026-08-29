<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Customerbalance Ã¢â‚¬â€ single source of truth for customer balance management.
 *
 * Used across all modules that affect a customer's outstanding balance:
 * Invoices, Payments, Sales Returns, Customer form, etc.
 *
 * Public method:
 *   recalcAndSync($orgUID, $customerUID, $userUID)
 *     Recalculates closing balance from all active transactions and syncs:
 *       1. Customers.CustOpeningBalanceTbl  Ã¢â‚¬â€ PendingBalance / PendingBalType
 *       2. Accounting.ChartOfAccounts       Ã¢â‚¬â€ CurrentBalance / CurrentBalanceType
 *       3. Upstash cache                    Ã¢â‚¬â€ ClosingBalance / ClosingBalType
 *     Returns ['balance' => float, 'type' => string] or null on failure.
 *
 * Formula:
 *   ClosingBalance = OpeningBalance + TotalInvoiced Ã¢Ë†â€™ TotalReceived Ã¢Ë†â€™ TotalReturned
 */
class Customerbalance {

    private $CI;

    public function __construct() {
        $this->CI =& get_instance();
    }

    // Ã¢â€â‚¬Ã¢â€â‚¬ Credit Note: create when a paid/partial invoice is cancelled Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬

    public function createCreditNote(int $orgUID, int $customerUID, int $transUID, int $userUID, string $invoiceNumber = ''): ?array {
        try {
            $this->CI->load->model('transactions_model');
            $this->CI->load->model('dbwrite_model');

            // Get total payments made against this invoice
            $readDb = $this->CI->load->database('ReadDB', TRUE);
            $readDb->db_debug = FALSE;
            $readDb->select('COALESCE(SUM(Amount), 0) AS paid');
            $readDb->from('Transaction.PaymentsTbl');
            $readDb->where([
                'TransUID'                  => $transUID,
                'PartyType'                 => 'C',
                'PaymentDirection'          => 'In',
                'IsDeleted'                 => 0,
                'IsCancelled'               => 0,
                'IsTransferredToCreditNote' => 0,
            ]);
            $row       = $readDb->get()->row();
            $paidTotal = $row ? (float)$row->paid : 0.0;

            if ($paidTotal <= 0) return null;

            // Prefix lookup for Credit Notes (ModuleUID = 107)
            $prefixData = $this->CI->transactions_model->getTransactionsPrefixDetails([
                'Prefix.OrgUID'    => $orgUID,
                'Prefix.ModuleUID' => 107,
            ]);
            $prefix    = !empty($prefixData->Data) ? $prefixData->Data[0] : null;
            $prefixUID = $prefix ? (int)$prefix->PrefixUID : null;

            // Next sequential number (org-wide, never re-issues)
            $seq = $this->CI->transactions_model->getNextCreditNoteNumber($orgUID);

            // Build formatted CN number (same logic as all other transaction types)
            $cnNumber = null;
            if ($prefix) {
                $date    = date('Y-m-d');
                $sep     = $prefix->Separator ?? '-';
                $parts   = [strtoupper($prefix->Name)];
                if (!empty($prefix->IncludeShortName) && !empty($prefix->ShortName)) {
                    $parts[] = strtoupper($prefix->ShortName);
                }
                if (!empty($prefix->IncludeFiscalYear)) {
                    $m       = (int)date('m', strtotime($date));
                    $yr      = (int)date('Y', strtotime($date));
                    $fyStart = $m >= 4 ? $yr : $yr - 1;
                    $parts[] = ($prefix->FiscalYearFormat ?? 'SHORT') === 'LONG'
                        ? $fyStart . '-' . ($fyStart + 1)
                        : str_pad($fyStart % 100, 2, '0', STR_PAD_LEFT) . '-' . str_pad(($fyStart + 1) % 100, 2, '0', STR_PAD_LEFT);
                }
                $pad     = (int)($prefix->NumberPadding ?? 1);
                $parts[] = $pad > 1 ? str_pad($seq, $pad, '0', STR_PAD_LEFT) : (string)$seq;
                $cnNumber = implode($sep, $parts);
            }

            $writeDb = $this->CI->load->database('WriteDB', TRUE);
            $writeDb->db_debug = FALSE;

            // Mark existing payments as transferred to credit note
            $writeDb->where([
                'TransUID'                  => $transUID,
                'PartyType'                 => 'C',
                'PaymentDirection'          => 'In',
                'IsDeleted'                 => 0,
                'IsCancelled'               => 0,
                'IsTransferredToCreditNote' => 0,
            ]);
            $writeDb->update('Transaction.PaymentsTbl', [
                'IsTransferredToCreditNote' => 1,
                'UpdatedBy'                 => $userUID,
            ]);

            // Create the credit note record
            $writeDb->insert('Transaction.TransCreditNoteTbl', [
                'OrgUID'             => $orgUID,
                'PartyUID'           => $customerUID,
                'PartyType'          => 'C',
                'SourceTransUID'     => $transUID,
                'SourceTransNumber'  => $invoiceNumber,
                'SourceModuleUID'    => 103,
                'CreditNoteNumber'   => $cnNumber,
                'CreditNoteToken'    => generate_uuid4(),
                'CreditNoteSeq'      => $seq,
                'CreditNoteType'     => 'Invoice',
                'PrefixUID'          => $prefixUID,
                'Amount'             => $paidTotal,
                'Status'             => 'Pending',
                'Notes'              => 'Auto-created on invoice cancellation',
                'CreatedBy'          => $userUID,
                'UpdatedBy'          => $userUID,
                'IsActive'           => 1,
                'IsDeleted'          => 0,
            ]);

            $creditNoteUID = (int)$writeDb->insert_id();

            try {
                $cnLabel = $cnNumber ?: '#' . $creditNoteUID;
                $this->CI->load->library('auditlog');
                $this->CI->auditlog->log(
                    $orgUID, $userUID, 'CREATE_CREDIT_NOTE', 'CreditNote', $creditNoteUID,
                    $cnNumber ?: '',
                    ['amount' => $paidTotal, 'sourceTransUID' => $transUID, 'sourceTransNumber' => $invoiceNumber, 'type' => 'Invoice'],
                    'Credit Note ' . $cnLabel . ' auto-created from cancelled Invoice ' . ($invoiceNumber ?: '#' . $transUID),
                    'Invoices', 'TRANSACTION'
                );
            } catch (Exception $auditEx) {
            }

            return ['creditNoteUID' => $creditNoteUID, 'amount' => $paidTotal];

        } catch (Exception $e) {
            notifyError($e, 'Customerbalance::createCreditNote');
            return null;
        }
    }

    // Ã¢â€â‚¬Ã¢â€â‚¬ Credit Note: create when a SR is saved without payment Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬

    public function createSalesReturnCreditNote($orgUID, $customerUID, $srTransUID, $srUniqueNumber, $amount, $userUID, $transDate = null) {
        try {

            if ($amount <= 0) {
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

            if (!$prefix) {
            }

            // Next sequential number (org-wide, never re-issues)
            $seq = $this->CI->transactions_model->getNextCreditNoteNumber($orgUID);

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

            $writeDb = $this->CI->load->database('WriteDB', TRUE);
            $writeDb->db_debug = FALSE;

            $insertData = [
                'OrgUID'             => (int)$orgUID,
                'PartyUID'           => (int)$customerUID,
                'PartyType'          => 'C',
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

            $insertOk = $writeDb->insert('Transaction.TransCreditNoteTbl', $insertData);
            $dbErr    = $writeDb->error();

            if (!$insertOk || !empty($dbErr['code'])) {
                return null;
            }

            $creditNoteUID = (int)$writeDb->insert_id();

            try {
                $cnLabel = $cnNumber ?: '#' . $creditNoteUID;
                $this->CI->load->library('auditlog');
                $this->CI->auditlog->log(
                    $orgUID, $userUID, 'CREATE_CREDIT_NOTE', 'CreditNote', $creditNoteUID,
                    $cnNumber ?: '',
                    ['amount' => $amount, 'sourceTransUID' => $srTransUID, 'sourceTransNumber' => $srUniqueNumber, 'type' => 'SalesReturn'],
                    'Credit Note ' . $cnLabel . ' auto-created from Sales Return ' . $srUniqueNumber,
                    'SalesReturns', 'TRANSACTION'
                );
            } catch (Exception $auditEx) {
            }

            return ['creditNoteUID' => $creditNoteUID, 'creditNoteNumber' => $cnNumber, 'amount' => $amount];

        } catch (Exception $e) {
            notifyError($e, 'Customerbalance::createSalesReturnCreditNote');
            return null;
        }
    }

    // Ã¢â€â‚¬Ã¢â€â‚¬ Debit Note: create when a paid SR is cancelled with Recover action Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬

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

            $writeDb->insert('Transaction.TransDebitNoteTbl', [
                'OrgUID'            => (int)$orgUID,
                'PartyUID'          => (int)$customerUID,
                'PartyType'         => 'C',
                'SourceTransUID'    => (int)$sourceTransUID,
                'SourceTransNumber' => (string)$sourceTransNumber,
                'SourceModuleUID'   => 106,
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

            return ['debitNoteUID' => $debitNoteUID, 'debitNoteNumber' => $dnNumber, 'amount' => $amount];
        } catch (Exception $e) {
            notifyError($e, 'Customerbalance::createDebitNote');
            return null;
        }
    }

    // Ã¢â€â‚¬Ã¢â€â‚¬ Debit Note: get pending notes for a customer Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬

    public function getPendingDebitNotes($orgUID, $customerUID) {
        try {
            $readDb = $this->CI->load->database('ReadDB', TRUE);
            $readDb->db_debug = FALSE;
            $readDb->select('DN.*, T.UniqueNumber AS SourceSRNumber');
            $readDb->from('Transaction.TransDebitNoteTbl DN');
            $readDb->join('Transaction.TransactionsTbl T', 'T.TransUID = DN.SourceTransUID', 'left');
            $readDb->where([
                'DN.OrgUID'     => (int)$orgUID,
                'DN.PartyUID'   => (int)$customerUID,
                'DN.PartyType'  => 'C',
                'DN.Status'     => 'Pending',
                'DN.IsDeleted'  => 0,
            ]);
            return $readDb->get()->result();
        } catch (Exception $e) {
            notifyError($e, 'Customerbalance::getPendingDebitNotes');
            return [];
        }
    }

    // Ã¢â€â‚¬Ã¢â€â‚¬ Credit Note: apply to a future invoice Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬

    public function applyCreditNote($orgUID, $creditNoteUID, $targetTransUID, $userUID, $moduleUID = 103) {
        try {
            $this->CI->load->model('dbwrite_model');
            $writeDb = $this->CI->load->database('WriteDB', TRUE);
            $writeDb->db_debug = FALSE;
            $writeDb->query("SET SESSION sql_mode = ''");

            // Use WriteDB to fetch Ã¢â‚¬â€ ensures we always see the latest committed data
            $writeDb->from('Transaction.TransCreditNoteTbl');
            $writeDb->where(['CreditNoteUID' => (int)$creditNoteUID, 'Status' => 'Pending', 'IsDeleted' => 0]);
            $cn = $writeDb->get()->row();

            if (!$cn) throw new Exception('Credit note not found or already used.');

            // Generate payment unique number (same logic as _savePaymentRecord)
            $this->CI->load->model('transactions_model');
            $payDate     = date('Y-m-d');
            $payTransYear = (int) date('Y');
            $payPrefixData = $this->CI->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => (int)$orgUID, 'Prefix.ModuleUID' => 110]);
            $payPrefix   = !empty($payPrefixData->Data) ? $payPrefixData->Data[0] : null;
            $payPrefixUID = $payPrefix ? (int)$payPrefix->PrefixUID : null;
            $payNumber   = $payPrefixUID ? $this->CI->transactions_model->getNextPaymentNumber($payPrefixUID, (int)$orgUID, $payTransYear) : 0;
            $payUnique   = null;
            if ($payPrefix && $payNumber > 0) {
                $sep   = $payPrefix->Separator ?? '-';
                $parts = [strtoupper($payPrefix->Name)];
                if (!empty($payPrefix->IncludeShortName) && !empty($payPrefix->ShortName)) {
                    $parts[] = strtoupper($payPrefix->ShortName);
                }
                if (!empty($payPrefix->IncludeFiscalYear)) {
                    $m   = (int) date('m', strtotime($payDate));
                    $yr  = (int) date('Y', strtotime($payDate));
                    $fy  = $m >= 4 ? $yr : $yr - 1;
                    $parts[] = ($payPrefix->FiscalYearFormat ?? 'SHORT') === 'LONG'
                        ? $fy . '-' . ($fy + 1)
                        : str_pad($fy % 100, 2, '0', STR_PAD_LEFT) . '-' . str_pad(($fy + 1) % 100, 2, '0', STR_PAD_LEFT);
                }
                $pad     = (int)($payPrefix->NumberPadding ?? 1);
                $parts[] = $pad > 1 ? str_pad($payNumber, $pad, '0', STR_PAD_LEFT) : (string)$payNumber;
                $payUnique = implode($sep, $parts);
            }

            $insertData = [
                'OrgUID'                    => (int)$orgUID,
                'PaymentDate'               => $payDate,
                'PaymentModuleUID'          => 110,
                'PrefixUID'                 => $payPrefixUID,
                'PaymentNumber'             => $payNumber,
                'UniqueNumber'              => $payUnique,
                'TransYear'                 => $payTransYear,
                'ReceiptToken'              => generate_uuid4(),
                'TransUID'                  => (int)$targetTransUID,
                'ModuleUID'                 => (int)$moduleUID,
                'PartyUID'                  => (int)$cn->PartyUID,
                'PartyType'                 => 'C',
                'PaymentTypeUID'            => 0,
                'Amount'                    => (float)$cn->Amount,
                'PaymentDirection'          => 'In',
                'SourceType'                => 'CreditNote',
                'IsTransferredToCreditNote' => 0,
                'IsActive'                  => 1,
                'IsDeleted'                 => 0,
                'CreatedBy'                 => (int)$userUID,
                'UpdatedBy'                 => (int)$userUID,
            ];

            $insertResult = $writeDb->insert('Transaction.PaymentsTbl', $insertData);
            $dbError      = $writeDb->error();
            $paymentUID   = (int)$writeDb->insert_id();

            if (!$insertResult) {
                throw new Exception('PaymentsTbl INSERT failed: ' . ($dbError['message'] ?? 'unknown error') . ' (code: ' . ($dbError['code'] ?? '?') . ')');
            }

            // Mark credit note as Applied
            $writeDb->where('CreditNoteUID', (int)$creditNoteUID);
            $writeDb->update('Transaction.TransCreditNoteTbl', [
                'Status'            => 'Applied',
                'AppliedTransUID'   => (int)$targetTransUID,
                'AppliedPaymentUID' => $paymentUID,
                'UpdatedBy'         => (int)$userUID,
            ]);
            if ($writeDb->affected_rows() < 1) {
                $updateErr = $writeDb->error();
            }

            $this->recalcAndSync($orgUID, $cn->PartyUID, $userUID);

            try {
                $cnNumber = $cn->CreditNoteNumber ?: '';
                $cnLabel  = $cnNumber ?: '#' . $creditNoteUID;
                $this->CI->load->library('auditlog');
                $this->CI->auditlog->log(
                    $orgUID, $userUID, 'APPLY_CREDIT_NOTE', 'CreditNote', (int)$creditNoteUID,
                    $cnNumber,
                    ['amount' => (float)$cn->Amount, 'targetTransUID' => $targetTransUID, 'paymentUID' => $paymentUID],
                    'Credit Note ' . $cnLabel . ' applied to Invoice #' . $targetTransUID,
                    'Invoices', 'TRANSACTION'
                );
            } catch (Exception $auditEx) {
            }

            return ['paymentUID' => $paymentUID];

        } catch (Exception $e) {
            notifyError($e, 'Customerbalance::applyCreditNote');
            throw $e;
        }
    }

    // Ã¢â€â‚¬Ã¢â€â‚¬ Credit Note: mark as refunded (org physically returns money) Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬

    public function refundCreditNote($orgUID, $creditNoteUID, $userUID) {
        try {
            $writeDb = $this->CI->load->database('WriteDB', TRUE);
            $writeDb->db_debug = FALSE;

            // Use WriteDB (not ReadDB) to look up the credit note Ã¢â‚¬â€ guarantees
            // we see our own writes when called immediately after createCreditNote()
            $writeDb->from('Transaction.TransCreditNoteTbl');
            $writeDb->where(['CreditNoteUID' => (int)$creditNoteUID, 'Status' => 'Pending', 'IsDeleted' => 0]);
            $cn = $writeDb->get()->row();
            if (!$cn) throw new Exception('Credit note not found or already used.');

            $writeDb->where('CreditNoteUID', (int)$creditNoteUID);
            $writeDb->update('Transaction.TransCreditNoteTbl', [
                'Status'    => 'Refunded',
                'Notes'     => 'Refunded to customer',
                'UpdatedBy' => (int)$userUID,
            ]);

            // Mark the original payment(s) as IsCancelled = 1 Ã¢â‚¬â€ payment is reversed/voided
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
            $this->recalcAndSync($orgUID, $cn->PartyUID, $userUID);

            try {
                $cnNumber = $cn->CreditNoteNumber ?: '';
                $cnLabel  = $cnNumber ?: '#' . $creditNoteUID;
                $this->CI->load->library('auditlog');
                $this->CI->auditlog->log(
                    $orgUID, $userUID, 'REFUND_CREDIT_NOTE', 'CreditNote', (int)$creditNoteUID,
                    $cnNumber,
                    ['amount' => (float)$cn->Amount, 'sourceTransUID' => (int)$cn->SourceTransUID],
                    'Credit Note ' . $cnLabel . ' refunded to customer',
                    'Invoices', 'TRANSACTION'
                );
            } catch (Exception $auditEx) {
            }

            return true;

        } catch (Exception $e) {
            notifyError($e, 'Customerbalance::refundCreditNote');
            throw $e;
        }
    }

    // Ã¢â€â‚¬Ã¢â€â‚¬ Get pending credit notes for a customer Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬

    public function getPendingCreditNotes($orgUID, $customerUID) {
        try {
            $readDb = $this->CI->load->database('ReadDB', TRUE);
            $readDb->db_debug = FALSE;
            $readDb->select('CN.*, T.UniqueNumber AS SourceInvoiceNumber');
            $readDb->from('Transaction.TransCreditNoteTbl CN');
            $readDb->join('Transaction.TransactionsTbl T', 'T.TransUID = CN.SourceTransUID', 'left');
            $readDb->where([
                'CN.OrgUID'         => (int)$orgUID,
                'CN.PartyUID'       => (int)$customerUID,
                'CN.PartyType'      => 'C',
                'CN.Status'         => 'Pending',
                'CN.IsCancelled'    => 0,
                'CN.IsDeleted'      => 0,
                'CN.IsActive'       => 1,
                'CN.PaymentCleared' => 0,
            ]);
            return $readDb->get()->result();
        } catch (Exception $e) {
            notifyError($e, 'Customerbalance::getPendingCreditNotes');
            return [];
        }
    }

    // Ã¢â€â‚¬Ã¢â€â‚¬ Pending credit/debit notes Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
    // Delegates to customers_model which holds the shared WriteDb connection Ã¢â‚¬â€
    // avoids opening new TCP connections on every recalcAndSync call.

    private function _getPendingNoteTotals($orgUID, $customerUID) {
        $this->CI->load->model('customers_model');
        return $this->CI->customers_model->getCustomerPendingNoteTotals($orgUID, $customerUID);
    }

    // Ã¢â€â‚¬Ã¢â€â‚¬ Balance recalculation Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬

    public function recalcAndSync($orgUID, $customerUID, $userUID) {
        try {
            $this->CI->load->model('customers_model');

            $preOB = $this->CI->customers_model->getCustomerOpeningBalance($orgUID, $customerUID);

            $custRows = $this->CI->customers_model->getCustomersWithLedgerForBalance(
                (int)$orgUID, (int)$customerUID
            );
            if (empty($custRows)) {
                return null;
            }

            $cust = $custRows[0];

            $totalInvoiced  = $this->CI->customers_model->getCustomerTotalInvoiced($orgUID, $customerUID);
            $totalReceived  = $this->CI->customers_model->getCustomerTotalReceived($orgUID, $customerUID);
            $totalReturned  = $this->CI->customers_model->getCustomerTotalReturned($orgUID, $customerUID);
            // SRs that already have a pending/applied credit note must not be
            // subtracted a second time via totalReturned Ã¢â‚¬â€ pendingCreditNotes covers them.
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


            // 1. Update CustOpeningBalanceTbl Ã¢â€ â€™ PendingBalance (closing balance)
            $this->CI->customers_model->updateCustomerPendingBalance(
                $orgUID, $customerUID, $newBalance, $newBalType, $userUID
            );


            // 2. Update Accounting.ChartOfAccounts Ã¢â€ â€™ CurrentBalance
            if (!empty($cust->LedgerUID)) {
                $this->CI->customers_model->updateCustomerBalanceInLedger(
                    $cust->LedgerUID, $newBalance, $newBalType, $userUID
                );
            }

            // 3. Sync Upstash cache Ã¢â€ â€™ ClosingBalance
            $this->CI->cachehelper->upsertCustomer((int)$customerUID);

            return ['balance' => $newBalance, 'type' => $newBalType];

        } catch (Exception $e) {
            notifyError($e, 'Customerbalance::recalcAndSync');
            return null;
        }
    }
}
