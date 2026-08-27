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
    
    public function getEntityWithLedger(int $entityId, string $entityType = 'Customer'): ?object {

        try {

            
            
            $tableMap = [
                'Customer' => ['table' => 'Customers.CustomerTbl', 'alias' => 'c', 'id' => 'CustomerUID'],
                'Vendor' => ['table' => 'Vendors.VendorTbl', 'alias' => 'v', 'id' => 'VendorUID'],
                'Employee' => ['table' => 'Users.UserTbl', 'alias' => 'e', 'id' => 'UserUID']
            ];
            
            if (!isset($tableMap[$entityType])) {
                throw new Exception("Unsupported entity type: {$entityType}");
            }
            
            $config = $tableMap[$entityType];
            
            $this->ReadDb->select([
                "{$config['alias']}.{$config['id']}",
                "{$config['alias']}.Name as EntityName",
                "{$config['alias']}.IsDeleted",
                "{$config['alias']}.IsActive",
                'el.LedgerUID',
                'ca.LedgerCode',
                'ca.LedgerName',
                'ca.OpeningBalance',
                'ca.OpeningBalanceType',
                'ca.ParentLedgerUID'
            ]);
            
            $this->ReadDb->from("{$config['table']} as {$config['alias']}");
            $this->ReadDb->join('Accounting.EntityLedgerMap as el', 
                "el.{$config['id']} = {$config['alias']}.{$config['id']} AND el.EntityType = '{$entityType}'", 
                'left');
            $this->ReadDb->join('Accounting.ChartOfAccounts as ca', 
                'ca.LedgerUID = el.LedgerUID AND ca.IsDeleted = 0', 
                'left');
            $this->ReadDb->where(["{$config['alias']}.{$config['id']}" => $entityId]);
            $this->ReadDb->limit(1);
            $query = $this->ReadDb->get();
            if (!$query) {
                $error = $this->ReadDb->error();
                throw new Exception($error['message'] ?? 'Database error occurred');
            }

            return $query->row();
            
        } catch (Exception $e) {
            notifyError('Accountledger_model::getEntityWithLedger', $e);
            throw new Exception( "getEntityWithLedger failed for {$entityType}: " .$e->getMessage());
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

    public function getLedgerByParentAndType(int $parentId, ?string $ledgerType = null): array {

        try {

            $this->ReadDb->select('*');
            $this->ReadDb->from('Accounting.ChartOfAccounts');
            $this->ReadDb->where('ParentLedgerUID', $parentId);
            if ($ledgerType) {
                $this->ReadDb->where('LedgerType', $ledgerType);
            }
            $query = $this->ReadDb->get();
            if (!$query) {
                $error = $this->ReadDb->error();
                throw new Exception($error['message'] ?? 'Database error occurred');
            }
            return $query->result();

        } catch (Exception $e) {
            notifyError('Accountledger_model::getLedgerByParentAndType', $e);
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

    // â”€â”€ Trial Balance â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /** Fetch all active ledgers with their debit/credit totals for a financial year */
    public function getTrialBalance(int $financialYear): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $orgUID = $this->_orgUID();
            $fy = (int)$financialYear;
            $this->ReadDb->select([
                'ca.LedgerUID', 'ca.LedgerCode', 'ca.LedgerName', 'ca.LedgerType',
                'ca.OpeningBalance', 'ca.OpeningBalanceType',
                "IFNULL(SUM(CASE WHEN je.TransactionType='Debit'  THEN je.Amount ELSE 0 END),0) AS PeriodDebit",
                "IFNULL(SUM(CASE WHEN je.TransactionType='Credit' THEN je.Amount ELSE 0 END),0) AS PeriodCredit",
            ]);
            $this->ReadDb->from('Accounting.ChartOfAccounts ca');
            $this->ReadDb->join(
                'Accounting.JournalEntries je',
                'je.LedgerUID = ca.LedgerUID AND je.IsDeleted = 0',
                'left'
            );
            $this->ReadDb->join(
                'Accounting.GeneralJournal gj',
                "gj.JournalUID = je.JournalUID AND gj.IsDeleted = 0 AND gj.FinancialYear = {$fy}" .
                ($orgUID > 0 ? " AND gj.OrgUID = {$orgUID}" : ''),
                'left'
            );
            $this->ReadDb->where('ca.IsDeleted', 0);
            if ($orgUID > 0) $this->ReadDb->where('ca.OrgUID', $orgUID);
            $this->ReadDb->group_by('ca.LedgerUID');
            $this->ReadDb->order_by('ca.LedgerType', 'ASC');
            $this->ReadDb->order_by('ca.LedgerName', 'ASC');
            $query = $this->ReadDb->get();
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            notifyError('Accountledger_model::getTrialBalance', $e);
            return [];
        }
    }

    /** Distinct financial years that have journal entries */
    public function getJournalFinancialYears(): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $orgUID = $this->_orgUID();
            $this->ReadDb->select('DISTINCT FinancialYear');
            $this->ReadDb->from('Accounting.GeneralJournal');
            $this->ReadDb->where('IsDeleted', 0);
            if ($orgUID > 0) $this->ReadDb->where('OrgUID', $orgUID);
            $this->ReadDb->order_by('FinancialYear', 'DESC');
            $query = $this->ReadDb->get();
            if (!$query || $query->num_rows() === 0) {
                return [(int)date('Y')];
            }
            return array_column($query->result_array(), 'FinancialYear');
        } catch (Exception $e) {
            notifyError('Accountledger_model::getJournalFinancialYears', $e);
            return [(int)date('Y')];
        }
    }

    // â”€â”€ Journal list (paginated) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function getJournalList(int $limit, int $offset, array $filter = []): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $orgUID = $this->_orgUID();
            $this->ReadDb->select([
                'gj.JournalUID', 'gj.JournalNo', 'gj.JournalDate', 'gj.FinancialYear',
                'gj.ReferenceType', 'gj.ReferenceID', 'gj.ReferenceNo', 'gj.Narration',
                'gj.CreatedBy', 'gj.CreatedOn',
                'IFNULL(SUM(CASE WHEN je.TransactionType=\'Debit\' THEN je.Amount ELSE 0 END),0) AS TotalDebit',
                'IFNULL(SUM(CASE WHEN je.TransactionType=\'Credit\' THEN je.Amount ELSE 0 END),0) AS TotalCredit',
                'COUNT(je.EntryUID) AS LineCount',
            ]);
            $this->ReadDb->from('Accounting.GeneralJournal gj');
            $this->ReadDb->join('Accounting.JournalEntries je', 'je.JournalUID = gj.JournalUID AND je.IsDeleted = 0', 'left');
            $this->ReadDb->where('gj.IsDeleted', 0);
            if ($orgUID > 0) $this->ReadDb->where('gj.OrgUID', $orgUID);
            if (!empty($filter['ReferenceType']))  $this->ReadDb->where('gj.ReferenceType', $filter['ReferenceType']);
            if (!empty($filter['DateFrom']))       $this->ReadDb->where('gj.JournalDate >=', $filter['DateFrom']);
            if (!empty($filter['DateTo']))         $this->ReadDb->where('gj.JournalDate <=', $filter['DateTo']);
            if (!empty($filter['SearchAllData'])) {
                $s = $this->ReadDb->escape_like_str($filter['SearchAllData']);
                $this->ReadDb->where("(gj.JournalNo LIKE '%{$s}%' OR gj.ReferenceNo LIKE '%{$s}%' OR gj.Narration LIKE '%{$s}%')", null, false);
            }
            $this->ReadDb->group_by('gj.JournalUID');
            $this->ReadDb->order_by('gj.JournalDate', 'DESC');
            $this->ReadDb->order_by('gj.JournalUID',  'DESC');
            $this->ReadDb->limit($limit, $offset);
            $query = $this->ReadDb->get();
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            notifyError('Accountledger_model::getJournalList', $e);
            return [];
        }
    }

    public function getJournalCount(array $filter = []): int {
        try {
            $this->ReadDb->db_debug = FALSE;
            $orgUID = $this->_orgUID();
            $this->ReadDb->select('COUNT(DISTINCT gj.JournalUID) AS cnt');
            $this->ReadDb->from('Accounting.GeneralJournal gj');
            $this->ReadDb->where('gj.IsDeleted', 0);
            if ($orgUID > 0) $this->ReadDb->where('gj.OrgUID', $orgUID);
            if (!empty($filter['ReferenceType']))  $this->ReadDb->where('gj.ReferenceType', $filter['ReferenceType']);
            if (!empty($filter['DateFrom']))       $this->ReadDb->where('gj.JournalDate >=', $filter['DateFrom']);
            if (!empty($filter['DateTo']))         $this->ReadDb->where('gj.JournalDate <=', $filter['DateTo']);
            if (!empty($filter['SearchAllData'])) {
                $s = $this->ReadDb->escape_like_str($filter['SearchAllData']);
                $this->ReadDb->where("(gj.JournalNo LIKE '%{$s}%' OR gj.ReferenceNo LIKE '%{$s}%' OR gj.Narration LIKE '%{$s}%')", null, false);
            }
            $row = $this->ReadDb->get()->row();
            return (int)($row->cnt ?? 0);
        } catch (Exception $e) {
            notifyError('Accountledger_model::getJournalCount', $e);
            return 0;
        }
    }

    public function getJournalStats(): object {
        try {
            $this->ReadDb->db_debug = FALSE;
            $orgUID = $this->_orgUID();
            $this->ReadDb->select("
                COUNT(*) AS TotalCount,
                SUM(ReferenceType = 'Invoice')        AS InvoiceCount,
                SUM(ReferenceType = 'Purchase')       AS PurchaseCount,
                SUM(ReferenceType LIKE 'Payment%')    AS PaymentCount,
                SUM(ReferenceType LIKE 'Reversal%')   AS ReversalCount,
                SUM(ReferenceType = 'Manual')         AS ManualCount,
                SUM(ReferenceType NOT IN ('Invoice','Purchase','Manual') AND ReferenceType NOT LIKE 'Payment%' AND ReferenceType NOT LIKE 'Reversal%') AS OtherCount
            ");
            $this->ReadDb->from('Accounting.GeneralJournal');
            $this->ReadDb->where('IsDeleted', 0);
            if ($orgUID > 0) $this->ReadDb->where('OrgUID', $orgUID);
            $row = $this->ReadDb->get()->row();
            return $row ?? new stdClass();
        } catch (Exception $e) {
            notifyError('Accountledger_model::getJournalStats', $e);
            return new stdClass();
        }
    }

    public function getJournalWithEntries(int $journalUID): ?object {
        try {
            $this->ReadDb->db_debug = FALSE;
            $orgUID = $this->_orgUID();
            // Header â€” scoped to this org
            $this->ReadDb->select('*');
            $this->ReadDb->from('Accounting.GeneralJournal');
            $this->ReadDb->where('JournalUID', (int)$journalUID);
            if ($orgUID > 0) $this->ReadDb->where('OrgUID', $orgUID);
            $this->ReadDb->limit(1);
            $header = $this->ReadDb->get()->row();
            if (!$header) return null;

            // Lines with ledger name â€” scoped to this org
            $this->ReadDb->select([
                'je.EntryUID', 'je.LedgerUID', 'je.TransactionType', 'je.Amount', 'je.Particulars',
                'ca.LedgerCode', 'ca.LedgerName', 'ca.LedgerType',
            ]);
            $this->ReadDb->from('Accounting.JournalEntries je');
            $this->ReadDb->join('Accounting.ChartOfAccounts ca', 'ca.LedgerUID = je.LedgerUID', 'left');
            $this->ReadDb->where('je.JournalUID', (int)$journalUID);
            $this->ReadDb->where('je.IsDeleted', 0);
            if ($orgUID > 0) $this->ReadDb->where('je.OrgUID', $orgUID);
            $this->ReadDb->order_by('je.EntryUID', 'ASC');
            $lines = $this->ReadDb->get()->result();

            $header->Lines = $lines;
            return $header;
        } catch (Exception $e) {
            notifyError('Accountledger_model::getJournalWithEntries', $e);
            return null;
        }
    }

    // â”€â”€ Chart of Accounts list (paginated) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function getChartOfAccountsList(int $limit, int $offset, array $filter = []): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $orgUID = $this->_orgUID();
            $this->ReadDb->select([
                'ca.LedgerUID', 'ca.LedgerCode', 'ca.LedgerName', 'ca.LedgerType',
                'ca.OpeningBalance', 'ca.OpeningBalanceType',
                'ca.CurrentBalance', 'ca.CurrentBalanceType',
                'ca.IsActive', 'ca.IsDeleted', 'ca.ParentLedgerUID',
                'p.LedgerName AS ParentLedgerName',
            ]);
            $this->ReadDb->from('Accounting.ChartOfAccounts ca');
            $this->ReadDb->join('Accounting.ChartOfAccounts p', 'p.LedgerUID = ca.ParentLedgerUID', 'left');
            $this->ReadDb->where('ca.IsDeleted', 0);
            if ($orgUID > 0) $this->ReadDb->where('ca.OrgUID', $orgUID);
            if (!empty($filter['LedgerType'])) $this->ReadDb->where('ca.LedgerType', $filter['LedgerType']);
            if (!empty($filter['SearchAllData'])) {
                $s = $this->ReadDb->escape_like_str($filter['SearchAllData']);
                $this->ReadDb->where("(ca.LedgerCode LIKE '%{$s}%' OR ca.LedgerName LIKE '%{$s}%')", null, false);
            }
            $this->ReadDb->order_by('ca.LedgerType', 'ASC');
            $this->ReadDb->order_by('ca.LedgerName', 'ASC');
            $this->ReadDb->limit($limit, $offset);
            $query = $this->ReadDb->get();
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            notifyError('Accountledger_model::getChartOfAccountsList', $e);
            return [];
        }
    }

    public function getChartOfAccountsCount(array $filter = []): int {
        try {
            $this->ReadDb->db_debug = FALSE;
            $orgUID = $this->_orgUID();
            $this->ReadDb->select('COUNT(*) AS cnt');
            $this->ReadDb->from('Accounting.ChartOfAccounts ca');
            $this->ReadDb->where('ca.IsDeleted', 0);
            if ($orgUID > 0) $this->ReadDb->where('ca.OrgUID', $orgUID);
            if (!empty($filter['LedgerType'])) $this->ReadDb->where('ca.LedgerType', $filter['LedgerType']);
            if (!empty($filter['SearchAllData'])) {
                $s = $this->ReadDb->escape_like_str($filter['SearchAllData']);
                $this->ReadDb->where("(ca.LedgerCode LIKE '%{$s}%' OR ca.LedgerName LIKE '%{$s}%')", null, false);
            }
            $row = $this->ReadDb->get()->row();
            return (int)($row->cnt ?? 0);
        } catch (Exception $e) {
            notifyError('Accountledger_model::getChartOfAccountsCount', $e);
            return 0;
        }
    }

    public function getChartOfAccountsStats(): object {
        try {
            $this->ReadDb->db_debug = FALSE;
            $orgUID = $this->_orgUID();
            $this->ReadDb->select("
                COUNT(*) AS TotalCount,
                SUM(LedgerType = 'Asset')     AS AssetCount,
                SUM(LedgerType = 'Liability') AS LiabilityCount,
                SUM(LedgerType = 'Income')    AS IncomeCount,
                SUM(LedgerType = 'Expense')   AS ExpenseCount,
                SUM(LedgerType IN ('Customer','Vendor','Employee','Bank','Cash')) AS OtherCount
            ");
            $this->ReadDb->from('Accounting.ChartOfAccounts');
            $this->ReadDb->where('IsDeleted', 0);
            if ($orgUID > 0) $this->ReadDb->where('OrgUID', $orgUID);
            $row = $this->ReadDb->get()->row();
            return $row ?? new stdClass();
        } catch (Exception $e) {
            notifyError('Accountledger_model::getChartOfAccountsStats', $e);
            return new stdClass();
        }
    }

    // â”€â”€ General Ledger statement â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /** All journal lines for one ledger within a date range */
    public function getLedgerStatement(int $ledgerUID, ?string $dateFrom, ?string $dateTo): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select([
                'gj.JournalUID',  'gj.JournalNo', 'gj.JournalDate',
                'gj.ReferenceType', 'gj.ReferenceNo', 'gj.Narration',
                'je.TransactionType', 'je.Amount', 'je.Particulars',
            ]);
            $this->ReadDb->from('Accounting.JournalEntries je');
            $this->ReadDb->join('Accounting.GeneralJournal gj', 'gj.JournalUID = je.JournalUID AND gj.IsDeleted = 0');
            $this->ReadDb->where('je.LedgerUID', (int) $ledgerUID);
            $this->ReadDb->where('je.IsDeleted', 0);
            if ($dateFrom) $this->ReadDb->where('gj.JournalDate >=', $dateFrom);
            if ($dateTo)   $this->ReadDb->where('gj.JournalDate <=', $dateTo);
            $this->ReadDb->order_by('gj.JournalDate', 'ASC');
            $this->ReadDb->order_by('gj.JournalUID',  'ASC');
            $this->ReadDb->order_by('je.EntryUID',    'ASC');
            $query = $this->ReadDb->get();
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            notifyError('Accountledger_model::getLedgerStatement', $e);
            return [];
        }
    }

    /** Sum of Debit/Credit before the dateFrom (for opening balance calculation) */
    public function getLedgerActivityBefore(int $ledgerUID, ?string $dateBefore): object {
        try {
            $this->ReadDb->db_debug = FALSE;
            $this->ReadDb->select("
                IFNULL(SUM(CASE WHEN je.TransactionType='Debit'  THEN je.Amount ELSE 0 END), 0) AS TotalDebit,
                IFNULL(SUM(CASE WHEN je.TransactionType='Credit' THEN je.Amount ELSE 0 END), 0) AS TotalCredit
            ");
            $this->ReadDb->from('Accounting.JournalEntries je');
            $this->ReadDb->join('Accounting.GeneralJournal gj', 'gj.JournalUID = je.JournalUID AND gj.IsDeleted = 0');
            $this->ReadDb->where('je.LedgerUID', (int) $ledgerUID);
            $this->ReadDb->where('je.IsDeleted', 0);
            if ($dateBefore) $this->ReadDb->where('gj.JournalDate <', $dateBefore);
            $row = $this->ReadDb->get()->row();
            return $row ?? (object)['TotalDebit' => 0, 'TotalCredit' => 0];
        } catch (Exception $e) {
            notifyError('Accountledger_model::getLedgerActivityBefore', $e);
            return (object)['TotalDebit' => 0, 'TotalCredit' => 0];
        }
    }

    /** All active ledgers for the dropdown selector */
    public function getAllActiveLedgers(): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $orgUID = $this->_orgUID();
            $this->ReadDb->select('LedgerUID, LedgerCode, LedgerName, LedgerType, OpeningBalance, OpeningBalanceType');
            $this->ReadDb->from('Accounting.ChartOfAccounts');
            $this->ReadDb->where(['IsDeleted' => 0, 'IsActive' => 1]);
            if ($orgUID > 0) $this->ReadDb->where('OrgUID', $orgUID);
            $this->ReadDb->order_by('LedgerType', 'ASC');
            $this->ReadDb->order_by('LedgerName', 'ASC');
            $query = $this->ReadDb->get();
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            notifyError('Accountledger_model::getAllActiveLedgers', $e);
            return [];
        }
    }

    // â”€â”€ Bank Reconciliation â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function getBankAndCashLedgers(): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $orgUID = $this->_orgUID();
            $this->ReadDb->select('LedgerUID, LedgerCode, LedgerName, LedgerType, OpeningBalance, OpeningBalanceType');
            $this->ReadDb->from('Accounting.ChartOfAccounts');
            $this->ReadDb->where('IsDeleted', 0);
            $this->ReadDb->where('IsActive',  1);
            $this->ReadDb->where_in('LedgerType', ['Bank', 'Cash']);
            if ($orgUID > 0) $this->ReadDb->where('OrgUID', $orgUID);
            $this->ReadDb->order_by('LedgerType', 'ASC');
            $this->ReadDb->order_by('LedgerName', 'ASC');
            $query = $this->ReadDb->get();
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            notifyError('Accountledger_model::getBankAndCashLedgers', $e);
            return [];
        }
    }

    public function getBankReconEntries(int $ledgerUID, ?string $dateFrom, ?string $dateTo): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $orgUID = $this->_orgUID();
            $this->ReadDb->select([
                'je.EntryUID', 'je.IsReconciled', 'je.TransactionType', 'je.Amount', 'je.Particulars',
                'gj.JournalUID', 'gj.JournalNo', 'gj.JournalDate',
                'gj.ReferenceType', 'gj.ReferenceNo', 'gj.Narration',
            ]);
            $this->ReadDb->from('Accounting.JournalEntries je');
            $this->ReadDb->join('Accounting.GeneralJournal gj',
                'gj.JournalUID = je.JournalUID AND gj.IsDeleted = 0');
            $this->ReadDb->where('je.LedgerUID', $ledgerUID);
            $this->ReadDb->where('je.IsDeleted', 0);
            if ($orgUID > 0) $this->ReadDb->where('je.OrgUID', $orgUID);
            if ($dateFrom) $this->ReadDb->where('gj.JournalDate >=', $dateFrom);
            if ($dateTo)   $this->ReadDb->where('gj.JournalDate <=', $dateTo);
            $this->ReadDb->order_by('gj.JournalDate', 'ASC');
            $this->ReadDb->order_by('gj.JournalUID',  'ASC');
            $this->ReadDb->order_by('je.EntryUID',    'ASC');
            $query = $this->ReadDb->get();
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            notifyError('Accountledger_model::getBankReconEntries', $e);
            return [];
        }
    }

    public function bulkUpdateReconStatus(array $entryUIDs, int $isReconciled, int $orgUID): void {
        if (empty($entryUIDs)) return;
        try {
            $WriteDb = $this->load->database('WriteDB', TRUE);
            $WriteDb->db_debug = FALSE;
            $WriteDb->where('OrgUID', $orgUID);
            $WriteDb->where_in('EntryUID', $entryUIDs);
            $WriteDb->update('Accounting.JournalEntries', ['IsReconciled' => (int)$isReconciled]);
        } catch (Exception $e) {
            notifyError('Accountledger_model::bulkUpdateReconStatus', $e);
            throw $e;
        }
    }

    // â”€â”€ Recurring Journals â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function getRecurringJournalList(int $limit, int $offset, array $filter = []): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $orgUID = $this->_orgUID();
            $this->ReadDb->select('*');
            $this->ReadDb->from('Accounting.RecurringJournals');
            $this->ReadDb->where('IsDeleted', 0);
            if ($orgUID > 0) $this->ReadDb->where('OrgUID', $orgUID);
            if (isset($filter['IsActive']) && $filter['IsActive'] !== '') {
                $this->ReadDb->where('IsActive', (int)$filter['IsActive']);
            }
            if (!empty($filter['Due'])) {
                $this->ReadDb->where('NextRunDate <=', date('Y-m-d'));
                $this->ReadDb->where('IsActive', 1);
            }
            if (!empty($filter['SearchAllData'])) {
                $s = $this->ReadDb->escape_like_str($filter['SearchAllData']);
                $this->ReadDb->where("(Title LIKE '%{$s}%' OR Narration LIKE '%{$s}%')", null, false);
            }
            $this->ReadDb->order_by('IsActive', 'DESC');
            $this->ReadDb->order_by('NextRunDate', 'ASC');
            $this->ReadDb->limit($limit, $offset);
            $query = $this->ReadDb->get();
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            notifyError('Accountledger_model::getRecurringJournalList', $e);
            return [];
        }
    }

    public function getRecurringJournalCount(array $filter = []): int {
        try {
            $this->ReadDb->db_debug = FALSE;
            $orgUID = $this->_orgUID();
            $this->ReadDb->select('COUNT(*) AS cnt');
            $this->ReadDb->from('Accounting.RecurringJournals');
            $this->ReadDb->where('IsDeleted', 0);
            if ($orgUID > 0) $this->ReadDb->where('OrgUID', $orgUID);
            if (isset($filter['IsActive']) && $filter['IsActive'] !== '') {
                $this->ReadDb->where('IsActive', (int)$filter['IsActive']);
            }
            if (!empty($filter['Due'])) {
                $this->ReadDb->where('NextRunDate <=', date('Y-m-d'));
                $this->ReadDb->where('IsActive', 1);
            }
            if (!empty($filter['SearchAllData'])) {
                $s = $this->ReadDb->escape_like_str($filter['SearchAllData']);
                $this->ReadDb->where("(Title LIKE '%{$s}%' OR Narration LIKE '%{$s}%')", null, false);
            }
            $row = $this->ReadDb->get()->row();
            return (int)($row->cnt ?? 0);
        } catch (Exception $e) {
            notifyError('Accountledger_model::getRecurringJournalCount', $e);
            return 0;
        }
    }

    public function getRecurringJournalStats(): object {
        try {
            $this->ReadDb->db_debug = FALSE;
            $orgUID  = $this->_orgUID();
            $today   = date('Y-m-d');
            $this->ReadDb->select("
                COUNT(*) AS TotalCount,
                SUM(IsActive = 1) AS ActiveCount,
                SUM(IsActive = 0) AS PausedCount,
                SUM(IsActive = 1 AND NextRunDate <= '{$today}') AS DueCount
            ");
            $this->ReadDb->from('Accounting.RecurringJournals');
            $this->ReadDb->where('IsDeleted', 0);
            if ($orgUID > 0) $this->ReadDb->where('OrgUID', $orgUID);
            $row = $this->ReadDb->get()->row();
            return $row ?? new stdClass();
        } catch (Exception $e) {
            notifyError('Accountledger_model::getRecurringJournalStats', $e);
            return new stdClass();
        }
    }

    public function getRecurringJournalById(int $recurUID): ?object {
        try {
            $this->ReadDb->db_debug = FALSE;
            $orgUID = $this->_orgUID();
            $this->ReadDb->select('*');
            $this->ReadDb->from('Accounting.RecurringJournals');
            $this->ReadDb->where('RecurUID', $recurUID);
            $this->ReadDb->where('IsDeleted', 0);
            if ($orgUID > 0) $this->ReadDb->where('OrgUID', $orgUID);
            $this->ReadDb->limit(1);
            $header = $this->ReadDb->get()->row();
            if (!$header) return null;

            $this->ReadDb->select('rjl.*, ca.LedgerName, ca.LedgerCode, ca.LedgerType');
            $this->ReadDb->from('Accounting.RecurringJournalLines rjl');
            $this->ReadDb->join('Accounting.ChartOfAccounts ca', 'ca.LedgerUID = rjl.LedgerUID', 'left');
            $this->ReadDb->where('rjl.RecurUID', $recurUID);
            if ($orgUID > 0) $this->ReadDb->where('rjl.OrgUID', $orgUID);
            $this->ReadDb->order_by('rjl.SortOrder', 'ASC');
            $this->ReadDb->order_by('rjl.LineUID',   'ASC');
            $header->Lines = $this->ReadDb->get()->result();

            return $header;
        } catch (Exception $e) {
            notifyError('Accountledger_model::getRecurringJournalById', $e);
            return null;
        }
    }

    public function getDueRecurringJournals(): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $orgUID = $this->_orgUID();
            $today  = date('Y-m-d');
            $this->ReadDb->select('*');
            $this->ReadDb->from('Accounting.RecurringJournals');
            $this->ReadDb->where('IsDeleted', 0);
            $this->ReadDb->where('IsActive',  1);
            $this->ReadDb->where('NextRunDate <=', $today);
            if ($orgUID > 0) $this->ReadDb->where('OrgUID', $orgUID);
            $this->ReadDb->order_by('NextRunDate', 'ASC');
            $query = $this->ReadDb->get();
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            notifyError('Accountledger_model::getDueRecurringJournals', $e);
            return [];
        }
    }

    // â”€â”€ Profit & Loss â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function getPandLRows(string $dateFrom, string $dateTo): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $orgUID = $this->_orgUID();
            $this->ReadDb->select([
                'ca.LedgerUID', 'ca.LedgerCode', 'ca.LedgerName', 'ca.LedgerType',
                "IFNULL(SUM(CASE WHEN je.TransactionType='Debit'  THEN je.Amount ELSE 0 END),0) AS PeriodDebit",
                "IFNULL(SUM(CASE WHEN je.TransactionType='Credit' THEN je.Amount ELSE 0 END),0) AS PeriodCredit",
            ]);
            $this->ReadDb->from('Accounting.ChartOfAccounts ca');
            $this->ReadDb->join(
                'Accounting.JournalEntries je',
                'je.LedgerUID = ca.LedgerUID AND je.IsDeleted = 0',
                'left'
            );
            $this->ReadDb->join(
                'Accounting.GeneralJournal gj',
                "gj.JournalUID = je.JournalUID AND gj.IsDeleted = 0" .
                " AND gj.JournalDate >= '{$dateFrom}' AND gj.JournalDate <= '{$dateTo}'" .
                ($orgUID > 0 ? " AND gj.OrgUID = {$orgUID}" : ''),
                'left'
            );
            $this->ReadDb->where_in('ca.LedgerType', ['Income', 'Expense']);
            $this->ReadDb->where('ca.IsDeleted', 0);
            $this->ReadDb->where('ca.IsActive',  1);
            if ($orgUID > 0) $this->ReadDb->where('ca.OrgUID', $orgUID);
            $this->ReadDb->group_by('ca.LedgerUID');
            $this->ReadDb->order_by('ca.LedgerType', 'ASC');
            $this->ReadDb->order_by('ca.LedgerName',  'ASC');
            $query = $this->ReadDb->get();
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            notifyError('Accountledger_model::getPandLRows', $e);
            return [];
        }
    }

    // â”€â”€ Balance Sheet â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function getBalanceSheetRows(string $asOfDate): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $orgUID = $this->_orgUID();
            $bsTypes = ['Asset', 'Liability', 'Bank', 'Cash', 'Customer', 'Vendor', 'Employee'];
            $this->ReadDb->select([
                'ca.LedgerUID', 'ca.LedgerCode', 'ca.LedgerName', 'ca.LedgerType',
                'ca.OpeningBalance', 'ca.OpeningBalanceType',
                "IFNULL(SUM(CASE WHEN je.TransactionType='Debit'  THEN je.Amount ELSE 0 END),0) AS PeriodDebit",
                "IFNULL(SUM(CASE WHEN je.TransactionType='Credit' THEN je.Amount ELSE 0 END),0) AS PeriodCredit",
            ]);
            $this->ReadDb->from('Accounting.ChartOfAccounts ca');
            $this->ReadDb->join(
                'Accounting.JournalEntries je',
                'je.LedgerUID = ca.LedgerUID AND je.IsDeleted = 0',
                'left'
            );
            $this->ReadDb->join(
                'Accounting.GeneralJournal gj',
                "gj.JournalUID = je.JournalUID AND gj.IsDeleted = 0" .
                " AND gj.JournalDate <= '{$asOfDate}'" .
                ($orgUID > 0 ? " AND gj.OrgUID = {$orgUID}" : ''),
                'left'
            );
            $this->ReadDb->where_in('ca.LedgerType', $bsTypes);
            $this->ReadDb->where('ca.IsDeleted', 0);
            if ($orgUID > 0) $this->ReadDb->where('ca.OrgUID', $orgUID);
            $this->ReadDb->group_by('ca.LedgerUID');
            $this->ReadDb->order_by('ca.LedgerType', 'ASC');
            $this->ReadDb->order_by('ca.LedgerName',  'ASC');
            $query = $this->ReadDb->get();
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            notifyError('Accountledger_model::getBalanceSheetRows', $e);
            return [];
        }
    }

    // â”€â”€ Cash Flow Statement â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function getCashFlowBalances(string $dateFrom, string $dateTo): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $orgUID = $this->_orgUID();
            $this->ReadDb->select([
                'ca.LedgerUID', 'ca.LedgerName', 'ca.LedgerType',
                'ca.OpeningBalance', 'ca.OpeningBalanceType',
                "IFNULL(SUM(CASE WHEN gj.JournalDate < '{$dateFrom}' AND je.TransactionType='Debit'  THEN je.Amount ELSE 0 END),0) AS PreDr",
                "IFNULL(SUM(CASE WHEN gj.JournalDate < '{$dateFrom}' AND je.TransactionType='Credit' THEN je.Amount ELSE 0 END),0) AS PreCr",
                "IFNULL(SUM(CASE WHEN gj.JournalDate >= '{$dateFrom}' AND gj.JournalDate <= '{$dateTo}' AND je.TransactionType='Debit'  THEN je.Amount ELSE 0 END),0) AS PeriodDr",
                "IFNULL(SUM(CASE WHEN gj.JournalDate >= '{$dateFrom}' AND gj.JournalDate <= '{$dateTo}' AND je.TransactionType='Credit' THEN je.Amount ELSE 0 END),0) AS PeriodCr",
            ]);
            $this->ReadDb->from('Accounting.ChartOfAccounts ca');
            $this->ReadDb->join('Accounting.JournalEntries je', 'je.LedgerUID = ca.LedgerUID AND je.IsDeleted = 0', 'left');
            $this->ReadDb->join('Accounting.GeneralJournal gj',
                'gj.JournalUID = je.JournalUID AND gj.IsDeleted = 0' .
                ($orgUID > 0 ? " AND gj.OrgUID = {$orgUID}" : ''), 'left');
            $this->ReadDb->where_in('ca.LedgerType', ['Bank', 'Cash']);
            $this->ReadDb->where('ca.IsDeleted', 0);
            if ($orgUID > 0) $this->ReadDb->where('ca.OrgUID', $orgUID);
            $this->ReadDb->group_by('ca.LedgerUID');
            $this->ReadDb->order_by('ca.LedgerType', 'DESC'); // Cash before Bank
            $this->ReadDb->order_by('ca.LedgerName', 'ASC');
            $query = $this->ReadDb->get();
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            notifyError('Accountledger_model::getCashFlowBalances', $e);
            return [];
        }
    }

    public function getCashFlowCategoryRows(string $dateFrom, string $dateTo): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $orgUID = $this->_orgUID();
            $this->ReadDb->select([
                'je.LedgerUID AS CashLedgerUID',
                'gj.ReferenceType',
                'je.TransactionType',
                "SUM(je.Amount) AS Total",
            ]);
            $this->ReadDb->from('Accounting.JournalEntries je');
            $this->ReadDb->join('Accounting.ChartOfAccounts ca', 'ca.LedgerUID = je.LedgerUID AND ca.IsDeleted = 0');
            $this->ReadDb->join('Accounting.GeneralJournal gj',
                "gj.JournalUID = je.JournalUID AND gj.IsDeleted = 0" .
                " AND gj.JournalDate >= '{$dateFrom}' AND gj.JournalDate <= '{$dateTo}'" .
                ($orgUID > 0 ? " AND gj.OrgUID = {$orgUID}" : ''));
            $this->ReadDb->where_in('ca.LedgerType', ['Bank', 'Cash']);
            $this->ReadDb->where('je.IsDeleted', 0);
            if ($orgUID > 0) $this->ReadDb->where('ca.OrgUID', $orgUID);
            $this->ReadDb->group_by(['je.LedgerUID', 'gj.ReferenceType', 'je.TransactionType']);
            $query = $this->ReadDb->get();
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            notifyError('Accountledger_model::getCashFlowCategoryRows', $e);
            return [];
        }
    }

    // â”€â”€ Budget vs Actual â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    // NOTE: Requires Accounting.Budgets table:
    //   BudgetUID INT AUTO_INCREMENT PK,
    //   OrgUID INT, LedgerUID INT, FinancialYear SMALLINT,
    //   Month TINYINT DEFAULT 0 (0=annual),
    //   BudgetedAmount DECIMAL(18,4), CreatedBy INT,
    //   CreatedAt DATETIME, UpdatedBy INT, UpdatedAt DATETIME,
    //   IsDeleted TINYINT DEFAULT 0,
    //   UNIQUE KEY (OrgUID, LedgerUID, FinancialYear, Month)

    public function getBudgetVsActualRows(int $fy, string $dateFrom, string $dateTo): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $orgUID = $this->_orgUID();
            $this->ReadDb->select([
                'ca.LedgerUID', 'ca.LedgerCode', 'ca.LedgerName', 'ca.LedgerType',
                "IFNULL(b.BudgetedAmount, 0) AS BudgetedAmount",
                "IFNULL(SUM(CASE WHEN je.TransactionType='Debit'  THEN je.Amount ELSE 0 END),0) AS PeriodDr",
                "IFNULL(SUM(CASE WHEN je.TransactionType='Credit' THEN je.Amount ELSE 0 END),0) AS PeriodCr",
            ]);
            $this->ReadDb->from('Accounting.ChartOfAccounts ca');
            $this->ReadDb->join('Accounting.Budgets b',
                "b.LedgerUID = ca.LedgerUID AND b.OrgUID = ca.OrgUID" .
                " AND b.FinancialYear = {$fy} AND b.Month = 0 AND b.IsDeleted = 0", 'left');
            $this->ReadDb->join('Accounting.JournalEntries je', 'je.LedgerUID = ca.LedgerUID AND je.IsDeleted = 0', 'left');
            $this->ReadDb->join('Accounting.GeneralJournal gj',
                "gj.JournalUID = je.JournalUID AND gj.IsDeleted = 0" .
                " AND gj.JournalDate >= '{$dateFrom}' AND gj.JournalDate <= '{$dateTo}'" .
                ($orgUID > 0 ? " AND gj.OrgUID = {$orgUID}" : ''), 'left');
            $this->ReadDb->where_in('ca.LedgerType', ['Income', 'Expense']);
            $this->ReadDb->where('ca.IsDeleted', 0);
            $this->ReadDb->where('ca.IsActive', 1);
            if ($orgUID > 0) $this->ReadDb->where('ca.OrgUID', $orgUID);
            $this->ReadDb->group_by('ca.LedgerUID');
            $this->ReadDb->order_by('ca.LedgerType', 'ASC');
            $this->ReadDb->order_by('ca.LedgerName', 'ASC');
            $query = $this->ReadDb->get();
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            notifyError('Accountledger_model::getBudgetVsActualRows', $e);
            return [];
        }
    }

    public function saveBudgetAmount(int $ledgerUID, int $fy, float $amount, int $userUID): bool {
        try {
            $this->ReadDb->db_debug = FALSE;
            $orgUID  = $this->_orgUID();
            $existing = $this->ReadDb
                ->select('BudgetUID')
                ->where('OrgUID', $orgUID)
                ->where('LedgerUID', $ledgerUID)
                ->where('FinancialYear', $fy)
                ->where('Month', 0)
                ->where('IsDeleted', 0)
                ->get('Accounting.Budgets')
                ->row();
            $now = date('Y-m-d H:i:s');
            if ($existing) {
                $res = $this->dbwrite_model->updateData('Accounting', 'Budgets',
                    ['BudgetedAmount' => $amount, 'UpdatedBy' => $userUID, 'UpdatedAt' => $now],
                    ['BudgetUID' => (int)$existing->BudgetUID, 'OrgUID' => $orgUID]);
            } else {
                $res = $this->dbwrite_model->insertData('Accounting', 'Budgets', [
                    'OrgUID' => $orgUID, 'LedgerUID' => $ledgerUID,
                    'FinancialYear' => $fy, 'Month' => 0,
                    'BudgetedAmount' => $amount, 'CreatedBy' => $userUID,
                    'CreatedAt' => $now, 'IsDeleted' => 0,
                ]);
            }
            return !$res->Error;
        } catch (Exception $e) {
            notifyError('Accountledger_model::saveBudgetAmount', $e);
            return false;
        }
    }

    // â”€â”€ Aged Receivables â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function getAgedReceivablesRows(string $asOfDate): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $orgUID = $this->_orgUID();
            $d30 = date('Y-m-d', strtotime($asOfDate . ' -30 days'));
            $d60 = date('Y-m-d', strtotime($asOfDate . ' -60 days'));
            $d90 = date('Y-m-d', strtotime($asOfDate . ' -90 days'));
            $this->ReadDb->select([
                'ca.LedgerUID', 'ca.LedgerCode', 'ca.LedgerName',
                'ca.OpeningBalance', 'ca.OpeningBalanceType',
                "IFNULL(SUM(CASE WHEN je.TransactionType='Debit'  THEN je.Amount ELSE 0 END),0) AS TotalDr",
                "IFNULL(SUM(CASE WHEN je.TransactionType='Credit' THEN je.Amount ELSE 0 END),0) AS TotalCr",
                "IFNULL(SUM(CASE WHEN je.TransactionType='Debit' AND gj.JournalDate >= '{$d30}'                                    THEN je.Amount ELSE 0 END),0) AS DrBand0_30",
                "IFNULL(SUM(CASE WHEN je.TransactionType='Debit' AND gj.JournalDate >= '{$d60}' AND gj.JournalDate < '{$d30}'      THEN je.Amount ELSE 0 END),0) AS DrBand31_60",
                "IFNULL(SUM(CASE WHEN je.TransactionType='Debit' AND gj.JournalDate >= '{$d90}' AND gj.JournalDate < '{$d60}'      THEN je.Amount ELSE 0 END),0) AS DrBand61_90",
                "IFNULL(SUM(CASE WHEN je.TransactionType='Debit' AND gj.JournalDate < '{$d90}'                                     THEN je.Amount ELSE 0 END),0) AS DrBand90plus",
            ]);
            $this->ReadDb->from('Accounting.ChartOfAccounts ca');
            $this->ReadDb->join('Accounting.JournalEntries je', 'je.LedgerUID = ca.LedgerUID AND je.IsDeleted = 0', 'left');
            $this->ReadDb->join('Accounting.GeneralJournal gj',
                "gj.JournalUID = je.JournalUID AND gj.IsDeleted = 0 AND gj.JournalDate <= '{$asOfDate}'" .
                ($orgUID > 0 ? " AND gj.OrgUID = {$orgUID}" : ''), 'left');
            $this->ReadDb->where('ca.LedgerType', 'Customer');
            $this->ReadDb->where('ca.IsDeleted', 0);
            if ($orgUID > 0) $this->ReadDb->where('ca.OrgUID', $orgUID);
            $this->ReadDb->group_by('ca.LedgerUID');
            $this->ReadDb->order_by('ca.LedgerName', 'ASC');
            $query = $this->ReadDb->get();
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            notifyError('Accountledger_model::getAgedReceivablesRows', $e);
            return [];
        }
    }

    // â”€â”€ Aged Payables â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function getAgedPayablesRows(string $asOfDate): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $orgUID = $this->_orgUID();
            $d30 = date('Y-m-d', strtotime($asOfDate . ' -30 days'));
            $d60 = date('Y-m-d', strtotime($asOfDate . ' -60 days'));
            $d90 = date('Y-m-d', strtotime($asOfDate . ' -90 days'));
            $this->ReadDb->select([
                'ca.LedgerUID', 'ca.LedgerCode', 'ca.LedgerName',
                'ca.OpeningBalance', 'ca.OpeningBalanceType',
                "IFNULL(SUM(CASE WHEN je.TransactionType='Debit'  THEN je.Amount ELSE 0 END),0) AS TotalDr",
                "IFNULL(SUM(CASE WHEN je.TransactionType='Credit' THEN je.Amount ELSE 0 END),0) AS TotalCr",
                "IFNULL(SUM(CASE WHEN je.TransactionType='Credit' AND gj.JournalDate >= '{$d30}'                                   THEN je.Amount ELSE 0 END),0) AS CrBand0_30",
                "IFNULL(SUM(CASE WHEN je.TransactionType='Credit' AND gj.JournalDate >= '{$d60}' AND gj.JournalDate < '{$d30}'     THEN je.Amount ELSE 0 END),0) AS CrBand31_60",
                "IFNULL(SUM(CASE WHEN je.TransactionType='Credit' AND gj.JournalDate >= '{$d90}' AND gj.JournalDate < '{$d60}'     THEN je.Amount ELSE 0 END),0) AS CrBand61_90",
                "IFNULL(SUM(CASE WHEN je.TransactionType='Credit' AND gj.JournalDate < '{$d90}'                                    THEN je.Amount ELSE 0 END),0) AS CrBand90plus",
            ]);
            $this->ReadDb->from('Accounting.ChartOfAccounts ca');
            $this->ReadDb->join('Accounting.JournalEntries je', 'je.LedgerUID = ca.LedgerUID AND je.IsDeleted = 0', 'left');
            $this->ReadDb->join('Accounting.GeneralJournal gj',
                "gj.JournalUID = je.JournalUID AND gj.IsDeleted = 0 AND gj.JournalDate <= '{$asOfDate}'" .
                ($orgUID > 0 ? " AND gj.OrgUID = {$orgUID}" : ''), 'left');
            $this->ReadDb->where('ca.LedgerType', 'Vendor');
            $this->ReadDb->where('ca.IsDeleted', 0);
            if ($orgUID > 0) $this->ReadDb->where('ca.OrgUID', $orgUID);
            $this->ReadDb->group_by('ca.LedgerUID');
            $this->ReadDb->order_by('ca.LedgerName', 'ASC');
            $query = $this->ReadDb->get();
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            notifyError('Accountledger_model::getAgedPayablesRows', $e);
            return [];
        }
    }

    // â”€â”€ Day Book â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function getDayBookRows(string $dateFrom, string $dateTo, bool $cashBankOnly = false, int $ledgerUID = 0): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $orgUID = $this->_orgUID();
            $this->ReadDb->select([
                'gj.JournalDate', 'gj.JournalUID', 'gj.JournalNo', 'gj.ReferenceType', 'gj.ReferenceNo', 'gj.Narration',
                'ca.LedgerUID', 'ca.LedgerName', 'ca.LedgerType', 'ca.LedgerCode',
                'je.EntryUID', 'je.TransactionType', 'je.Amount', 'je.Particulars',
            ]);
            $this->ReadDb->from('Accounting.GeneralJournal gj');
            $this->ReadDb->join('Accounting.JournalEntries je', 'je.JournalUID = gj.JournalUID AND je.IsDeleted = 0');
            $this->ReadDb->join('Accounting.ChartOfAccounts ca', 'ca.LedgerUID = je.LedgerUID AND ca.IsDeleted = 0');
            $this->ReadDb->where('gj.IsDeleted', 0);
            $this->ReadDb->where("gj.JournalDate >=", $dateFrom);
            $this->ReadDb->where("gj.JournalDate <=", $dateTo);
            if ($orgUID > 0) $this->ReadDb->where('gj.OrgUID', $orgUID);
            if ($ledgerUID > 0) {
                $this->ReadDb->where('ca.LedgerUID', $ledgerUID);
            } elseif ($cashBankOnly) {
                $this->ReadDb->where_in('ca.LedgerType', ['Bank', 'Cash']);
            }
            $this->ReadDb->order_by('gj.JournalDate', 'ASC');
            $this->ReadDb->order_by('gj.JournalUID',  'ASC');
            $this->ReadDb->order_by('FIELD(je.TransactionType,\'Debit\',\'Credit\')', NULL, FALSE);
            $query = $this->ReadDb->get();
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            notifyError('Accountledger_model::getDayBookRows', $e);
            return [];
        }
    }

    public function getCashBankLedgers(): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $orgUID = $this->_orgUID();
            $this->ReadDb->select('LedgerUID, LedgerCode, LedgerName, LedgerType');
            $this->ReadDb->from('Accounting.ChartOfAccounts');
            $this->ReadDb->where_in('LedgerType', ['Bank', 'Cash']);
            $this->ReadDb->where('IsDeleted', 0);
            $this->ReadDb->where('IsActive', 1);
            if ($orgUID > 0) $this->ReadDb->where('OrgUID', $orgUID);
            $this->ReadDb->order_by('LedgerType', 'ASC');
            $this->ReadDb->order_by('LedgerName',  'ASC');
            $query = $this->ReadDb->get();
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            notifyError('Accountledger_model::getCashBankLedgers', $e);
            return [];
        }
    }

    // â”€â”€ Period Lock â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

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

    public function getLedgersForJournalDropdown(string $search = '', int $limit = 50): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $orgUID = $this->_orgUID();
            $this->ReadDb->select('LedgerUID, LedgerCode, LedgerName, LedgerType');
            $this->ReadDb->from('Accounting.ChartOfAccounts');
            $this->ReadDb->where('IsDeleted', 0);
            $this->ReadDb->where('IsActive',  1);
            if ($orgUID > 0) $this->ReadDb->where('OrgUID', $orgUID);
            if ($search !== '') {
                $s = $this->ReadDb->escape_like_str($search);
                $this->ReadDb->where("(LedgerCode LIKE '%{$s}%' OR LedgerName LIKE '%{$s}%')", null, false);
            }
            $this->ReadDb->order_by('LedgerType', 'ASC');
            $this->ReadDb->order_by('LedgerName', 'ASC');
            $this->ReadDb->limit($limit);
            $query = $this->ReadDb->get();
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            notifyError('Accountledger_model::getLedgersForJournalDropdown', $e);
            return [];
        }
    }

    public function getParentLedgers(): array {
        try {
            $this->ReadDb->db_debug = FALSE;
            $orgUID = $this->_orgUID();
            $this->ReadDb->select('LedgerUID, LedgerCode, LedgerName, LedgerType');
            $this->ReadDb->from('Accounting.ChartOfAccounts');
            $this->ReadDb->where('IsDeleted', 0);
            $this->ReadDb->where('IsActive', 1);
            if ($orgUID > 0) $this->ReadDb->where('OrgUID', $orgUID);
            $this->ReadDb->order_by('LedgerType', 'ASC');
            $this->ReadDb->order_by('LedgerName', 'ASC');
            $query = $this->ReadDb->get();
            return $query ? $query->result() : [];
        } catch (Exception $e) {
            notifyError('Accountledger_model::getParentLedgers', $e);
            return [];
        }
    }

}
