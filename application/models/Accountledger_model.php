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

    public function getEntityLedgerByColumn(string $column, $entityId, ?string $entityType = null) {
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
            throw new Exception('getEntityLedgerByColumn failed: ' . $e->getMessage());
        }

    }
    
    public function getEntityWithLedger(int $entityId, string $entityType = 'Customer') {

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
            throw new Exception( "getEntityWithLedger failed for {$entityType}: " .$e->getMessage());
        }

    }

    // get ledger inforamtion
    public function getLedgerById(int $ledgerId, ?string $ledgerType = null) {

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
            throw $e;
        }

    }

    public function getLedgerByParentAndType(int $parentId, ?string $ledgerType = null) {

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
            throw new Exception('ledgerHasTransactions failed: ' . $e->getMessage());
        }

    }

    public function getSystemLedgerByCode(string $code, int $orgUID = 0) {
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
            log_message('error', 'Accountledger_model: ' . $e->getMessage());
            return null;
        }
    }

    public function getLastLedgerBalance(int $ledgerUID, int $financialYear, int $orgUID = 0) {
        try {
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
            log_message('error', 'Accountledger_model: ' . $e->getMessage());
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
            log_message('error', 'Accountledger_model: ' . $e->getMessage());
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
            log_message('error', 'Accountledger_model: ' . $e->getMessage());
            return [];
        }
    }

    // ── Trial Balance ────────────────────────────────────────────────────────

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
            log_message('error', 'getTrialBalance: ' . $e->getMessage());
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
            return [(int)date('Y')];
        }
    }

    // ── Journal list (paginated) ─────────────────────────────────────────────

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
            log_message('error', 'getJournalList: ' . $e->getMessage());
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
            log_message('error', 'Accountledger_model: ' . $e->getMessage());
            return 0;
        }
    }

    public function getJournalStats() {
        try {
            $this->ReadDb->db_debug = FALSE;
            $orgUID = $this->_orgUID();
            $this->ReadDb->select("
                COUNT(*) AS TotalCount,
                SUM(ReferenceType = 'Invoice')        AS InvoiceCount,
                SUM(ReferenceType = 'Purchase')       AS PurchaseCount,
                SUM(ReferenceType LIKE 'Payment%')    AS PaymentCount,
                SUM(ReferenceType LIKE 'Reversal%')   AS ReversalCount,
                SUM(ReferenceType NOT IN ('Invoice','Purchase') AND ReferenceType NOT LIKE 'Payment%' AND ReferenceType NOT LIKE 'Reversal%') AS OtherCount
            ");
            $this->ReadDb->from('Accounting.GeneralJournal');
            $this->ReadDb->where('IsDeleted', 0);
            if ($orgUID > 0) $this->ReadDb->where('OrgUID', $orgUID);
            $row = $this->ReadDb->get()->row();
            return $row ?? new stdClass();
        } catch (Exception $e) {
            return new stdClass();
        }
    }

    public function getJournalWithEntries(int $journalUID) {
        try {
            $this->ReadDb->db_debug = FALSE;
            $orgUID = $this->_orgUID();
            // Header — scoped to this org
            $this->ReadDb->select('*');
            $this->ReadDb->from('Accounting.GeneralJournal');
            $this->ReadDb->where('JournalUID', (int)$journalUID);
            if ($orgUID > 0) $this->ReadDb->where('OrgUID', $orgUID);
            $this->ReadDb->limit(1);
            $header = $this->ReadDb->get()->row();
            if (!$header) return null;

            // Lines with ledger name — scoped to this org
            $this->ReadDb->select([
                'je.EntryUID', 'je.TransactionType', 'je.Amount', 'je.Particulars',
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
            log_message('error', 'getJournalWithEntries: ' . $e->getMessage());
            return null;
        }
    }

    // ── Chart of Accounts list (paginated) ───────────────────────────────────
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
            log_message('error', 'getChartOfAccountsList: ' . $e->getMessage());
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
            log_message('error', 'Accountledger_model: ' . $e->getMessage());
            return 0;
        }
    }

    public function getChartOfAccountsStats() {
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
            return new stdClass();
        }
    }

    // ── General Ledger statement ─────────────────────────────────────────────

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
            log_message('error', 'getLedgerStatement: ' . $e->getMessage());
            return [];
        }
    }

    /** Sum of Debit/Credit before the dateFrom (for opening balance calculation) */
    public function getLedgerActivityBefore(int $ledgerUID, ?string $dateBefore) {
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
            return (object)['TotalDebit' => 0, 'TotalCredit' => 0];
        }
    }

    /** All active ledgers for the dropdown selector */
    public function getAllActiveLedgers() {
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
            log_message('error', 'Accountledger_model: ' . $e->getMessage());
            return [];
        }
    }

    public function getParentLedgers() {
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
            log_message('error', 'Accountledger_model: ' . $e->getMessage());
            return [];
        }
    }

}
