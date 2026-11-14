<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Accountledger_model extends CI_Model {

    /** @var object */
    private $ReadDb;

    public function __construct() {
        parent::__construct();
        $this->ReadDb = $this->load->database('ReadDB', TRUE);
    }

    /** Helper: returns the current org UID from JWT */
    private function _orgUID(): int {
        return (int)(get_instance()->pageData['JwtData']->Org->OrgUID ?? 0);
    }

    public function getEntityLedgerByColumn(string $column, mixed $entityId, ?string $entityType = null): ?object {
        try {
            $orgUID = $this->_orgUID();
            $this->ReadDb->select('el.*, ca.*');
            $this->ReadDb->from('Accounting.EntityLedgerMap as el');
            $this->ReadDb->join('Accounting.ChartOfAccounts as ca', 'ca.LedgerUID = el.LedgerUID');
            $this->ReadDb->where("el.{$column}", $entityId);
            if ($orgUID > 0) $this->ReadDb->where('el.OrgUID', $orgUID);
            if ($entityType) {
                $this->ReadDb->where('el.EntityType', $entityType);
            }
            $this->ReadDb->limit(1);
            $query = $this->ReadDb->get();
            if (!$query) {
                $error = $this->ReadDb->error();
                throw new Exception($error['message'] ?? 'Database error occurred');
            }

            return $query->row();
            
        } catch (Exception $e) {
            notifyError('Accountledger_model::getEntityLedgerByColumn', $e);
            throw new Exception('getEntityLedgerByColumn failed: ' . $e->getMessage());
        }

    }
    
    // get ledger inforamtion
    public function getLedgerById(int $ledgerId, ?string $ledgerType = null): ?object {

        try {

            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select('*');
            $this->ReadDb->from('Accounting.ChartOfAccounts');
            $this->ReadDb->where('LedgerUID', $ledgerId);
            if($ledgerType) {
                $this->ReadDb->where('LedgerType', $ledgerType);
            }
            $query = $this->ReadDb->get();
            if (!$query) {
                $error = $this->ReadDb->error();
                throw new Exception($error['message'] ?? 'Database error occurred');
            }

            return $query->row();

        } catch (Exception $e) {
            notifyError('Accountledger_model::getLedgerById', $e);
            throw $e;
        }

    }

    public function ledgerHasTransactions(int $ledgerUID): bool {
        try {
            $orgUID = $this->_orgUID();
            $this->ReadDb->select('EntryUID');
            $this->ReadDb->from('Accounting.JournalEntries');
            $this->ReadDb->where('LedgerUID', $ledgerUID);
            $this->ReadDb->where('IsDeleted', 0);
            if ($orgUID > 0) $this->ReadDb->where('OrgUID', $orgUID);
            $this->ReadDb->limit(1);
            $query = $this->ReadDb->get();
            if (!$query) {
                $error = $this->ReadDb->error();
                throw new Exception($error['message'] ?? 'Database error occurred');
            }

            return ($query->num_rows() > 0);

        } catch (Exception $e) {
            notifyError('Accountledger_model::ledgerHasTransactions', $e);
            throw new Exception('ledgerHasTransactions failed: ' . $e->getMessage());
        }

    }

    public function getSystemLedgerByCode(string $code, int $orgUID = 0): ?object {
        try {
            $this->ReadDb->select('LedgerUID, LedgerCode, LedgerName, LedgerType, CurrentBalance, CurrentBalanceType');
            $this->ReadDb->from('Accounting.ChartOfAccounts');
            $this->ReadDb->where('LedgerCode', $code);
            $this->ReadDb->where('IsDeleted', 0);
            if ($orgUID > 0) $this->ReadDb->where('OrgUID', (int)$orgUID);
            $this->ReadDb->limit(1);
            $query = $this->ReadDb->get();
            return ($query && $query->num_rows() > 0) ? $query->row() : null;
        } catch (Exception $e) {
            notifyError('Accountledger_model::getSystemLedgerByCode', $e);
            return null;
        }
    }

    public function getLastLedgerBalance(int $ledgerUID, int $financialYear, int $orgUID = 0): ?object {
        try {
            // End any open REPEATABLE READ snapshot so this read sees WriteDb auto-commits
            // made by earlier _addJournalLine calls in the same HTTP request.
            $this->ReadDb->simple_query('COMMIT');
            $this->ReadDb->select('RunningBalance, BalanceType');
            $this->ReadDb->from('Accounting.LedgerBalances');
            $this->ReadDb->where('LedgerUID', (int)$ledgerUID);
            $this->ReadDb->where('FinancialYear', (int)$financialYear);
            if ($orgUID > 0) $this->ReadDb->where('OrgUID', (int)$orgUID);
            $this->ReadDb->order_by('BalanceUID', 'DESC');
            $this->ReadDb->limit(1);
            $query = $this->ReadDb->get();
            return ($query && $query->num_rows() > 0) ? $query->row() : null;
        } catch (Exception $e) {
            notifyError('Accountledger_model::getLastLedgerBalance', $e);
            return null;
        }
    }

    public function getJournalByReference(string $refType, int $refID, int $orgUID = 0): array {
        try {
            $this->ReadDb->select('JournalUID, JournalNo, JournalDate, FinancialYear');
            $this->ReadDb->from('Accounting.GeneralJournal');
            $this->ReadDb->where('ReferenceType', $refType);
            $this->ReadDb->where('ReferenceID', (int)$refID);
            $this->ReadDb->where('IsDeleted', 0);
            if ($orgUID > 0) $this->ReadDb->where('OrgUID', (int)$orgUID);
            $query = $this->ReadDb->get();
            return ($query) ? $query->result() : [];
        } catch (Exception $e) {
            notifyError('Accountledger_model::getJournalByReference', $e);
            return [];
        }
    }

    public function getJournalEntries(int $journalUID, int $orgUID = 0): array {
        try {
            $this->ReadDb->select('EntryUID, LedgerUID, TransactionType, Amount, Particulars');
            $this->ReadDb->from('Accounting.JournalEntries');
            $this->ReadDb->where('JournalUID', (int)$journalUID);
            $this->ReadDb->where('IsDeleted', 0);
            if ($orgUID > 0) $this->ReadDb->where('OrgUID', (int)$orgUID);
            $query = $this->ReadDb->get();
            return ($query) ? $query->result() : [];
        } catch (Exception $e) {
            notifyError('Accountledger_model::getJournalEntries', $e);
            return [];
        }
    }

    // ── Period Lock (checked before every journal post) ──────────────────────

    public function getPeriodLock(): ?object {
        try {
            $this->ReadDb->db_debug = FALSE;
            $orgUID = $this->_orgUID();
            $this->ReadDb->select('*');
            $this->ReadDb->from('Accounting.PeriodLock');
            if ($orgUID > 0) $this->ReadDb->where('OrgUID', $orgUID);
            $this->ReadDb->limit(1);
            return $this->ReadDb->get()->row() ?? null;
        } catch (Exception $e) {
            notifyError('Accountledger_model::getPeriodLock', $e);
            return null;
        }
    }

}
