<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Accountledger_model extends CI_Model {
    
    private $ReadDb;

	function __construct() {
        parent::__construct();
        
        $this->ReadDb = $this->load->database('ReadDB', TRUE);

    }

    public function getEntityLedgerByColumn($column, $entityId, $entityType = null) {

        try {
            $this->ReadDb->select('el.*, ca.*');
            $this->ReadDb->from('Accounting.EntityLedgerMap as el');
            $this->ReadDb->join('Accounting.ChartOfAccounts as ca', 'ca.LedgerUID = el.LedgerUID');
            $this->ReadDb->where("el.{$column}", $entityId);
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
    
    public function getEntityWithLedger($entityId, $entityType = 'Customer') {

        try {

            $this->CI->load->model('accountledger_model');
            
            $tableMap = [
                'Customer' => ['table' => 'Customers.CustomerTbl', 'alias' => 'c', 'id' => 'CustomerUID'],
                'Vendor' => ['table' => 'Vendors.VendorTbl', 'alias' => 'v', 'id' => 'VendorUID'],
                'Employee' => ['table' => 'Users.UserTbl', 'alias' => 'e', 'id' => 'UserUID']
            ];
            
            if (!isset($tableMap[$entityType])) {
                throw new Exception("Unsupported entity type: {$entityType}");
            }
            
            $config = $tableMap[$entityType];
            
            $this->CI->ReadDb->select([
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
            
            $this->CI->ReadDb->from("{$config['table']} as {$config['alias']}");
            $this->CI->ReadDb->join('Accounting.EntityLedgerMap as el', 
                "el.{$config['id']} = {$config['alias']}.{$config['id']} AND el.EntityType = '{$entityType}'", 
                'left');
            $this->CI->ReadDb->join('Accounting.ChartOfAccounts as ca', 
                'ca.LedgerUID = el.LedgerUID AND ca.IsDeleted = 0', 
                'left');
            $this->CI->ReadDb->where(["{$config['alias']}.{$config['id']}" => $entityId]);
            $this->CI->ReadDb->limit(1);
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
    public function getLedgerById($ledgerId, $ledgerType = NULL) {

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

    public function getLedgerByParentAndType($parentId, $ledgerType = null) {

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

    public function ledgerHasTransactions($ledgerUID) {

        try {

            $this->ReadDb->select('EntryUID');
            $this->ReadDb->from('Accounting.JournalEntries');
            $this->ReadDb->where('LedgerUID', $ledgerUID);
            $this->ReadDb->where('IsDeleted', 0);
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


}