<?php defined('BASEPATH') OR exit('No direct script access allowed');

Class Accountledger {

    protected $CI;

    public function __construct() {
        $this->CI =& get_instance();
    }

    public function createLedgerAccountingInfo($entityId, $postData, $entityType = 'Customer') {

        try {
            
            $ledgerConfig = $this->getLedgerConfig($entityType);
            
            $this->CI->load->model('accountledger_model');
            $parentExists = $this->CI->accountledger_model->getLedgerById($ledgerConfig['parent_id'], $ledgerConfig['parent_type']);
            if(!$parentExists) {
                throw new Exception("Parent ledger ({$ledgerConfig['parent_name']}) not found. Setup chart of accounts first.");
            }

            // Create ledger account
            $ledgerCode = $this->generateLedgerCode($entityId, $entityType);
            $ledgerData = [
                'LedgerCode' => $ledgerCode,
                'LedgerName' => $postData['Name'],
                'LedgerType' => $entityType,
                'ParentLedgerUID' => $ledgerConfig['parent_id'],
                'OpeningBalance' => $postData['OpeningBalance'],
                'OpeningBalanceType' => $postData['OpeningBalanceType'],
                'CurrentBalance' => (float)$postData['OpeningBalance'],
                'CurrentBalanceType' => $postData['OpeningBalanceType'],
                'CreatedBy' => $this->CI->pageData['JwtData']->User->UserUID,
                'UpdatedBy' => $this->CI->pageData['JwtData']->User->UserUID,
            ];
            $ledgerResp = $this->CI->dbwrite_model->insertData('Accounting', 'ChartOfAccounts', $ledgerData);
            if ($ledgerResp->Error) throw new Exception($ledgerResp->Message);
            
            $ledgerId = $ledgerResp->ID;

            /*
            |--------------------------------------------------------------------------
            | INSERT YEARLY OPENING BALANCE (CRITICAL ADDITION)
            |--------------------------------------------------------------------------
            */
            $financialYear = $this->getFinancialYear();
            $yearOpeningData = [
                'LedgerUID' => $ledgerId,
                'FinancialYear' => $financialYear,
                'OpeningBalance' => (float) $postData['OpeningBalance'],
                'OpeningBalanceType' => $postData['OpeningBalanceType'],
            ];
            $yearResp = $this->CI->dbwrite_model->insertData('Accounting', 'LedgerYearOpening', $yearOpeningData);
            if ($yearResp->Error) {
                throw new Exception('Failed to create yearly opening balance: ' . $yearResp->Message);
            }

            /*
            |--------------------------------------------------------------------------
            | MAP ENTITY TO LEDGER (UNCHANGED)
            |--------------------------------------------------------------------------
            */
            $this->mapEntityToLedger($entityId, $ledgerId, $entityType);

            $this->logLedgerAudit(
                $entityId,
                $ledgerId,
                'CREATE',
                [
                    'reason' => "{$entityType} ledger created",
                    'entity_type' => $entityType,
                    'amount_change' => $postData['OpeningBalance'],
                    'balance_type_change' => $postData['OpeningBalanceType'],
                    'ledger_code' => $ledgerCode,
                    'parent_ledger' => $ledgerConfig['parent_name']
                ],
                $entityType,
            );

            return $ledgerId;
        
        } catch (Exception $e) {
            throw $e;
        }

    }

    private function getLedgerConfig($entityType) {
        $config = [
            'Customer' => [
                'parent_id' => 2, // Sundry Debtors
                'parent_name' => 'Sundry Debtors',
                'parent_type' => 'Asset',
                'prefix' => 'CST',
                'description' => 'Customer ledger account'
            ],
            'Vendor' => [
                'parent_id' => 3, // Sundry Creditors
                'parent_name' => 'Sundry Creditors',
                'parent_type' => 'Liability',
                'prefix' => 'VND',
                'description' => 'Vendor ledger account'
            ],
            'Employee' => [ // Future use
                'parent_id' => 4,
                'parent_name' => 'Salary Payable',
                'parent_type' => 'Liability',
                'prefix' => 'EMP',
                'description' => 'Employee ledger account'
            ]
        ];
        
        if (!isset($config[$entityType])) {
            throw new Exception("Unsupported entity type: {$entityType}");
        }
        
        return $config[$entityType];
    }

    private function generateLedgerCode($entityId, $entityType) {
        $config = $this->getLedgerConfig($entityType);
        return $config['prefix'] . '-' . str_pad($entityId, 5, '0', STR_PAD_LEFT);
    }

    private function mapEntityToLedger($entityId, $ledgerId, $entityType) {

        try {

            // Determine which column to set based on entity type
            $mapData = [
                'LedgerUID' => $ledgerId,
                'EntityType' => $entityType,
                'CreatedBy' => $this->CI->pageData['JwtData']->User->UserUID,
                'UpdatedBy' => $this->CI->pageData['JwtData']->User->UserUID,
            ];
            
            // Set the appropriate entity column
            switch ($entityType) {
                case 'Customer':
                    $mapData['CustomerUID'] = $entityId;
                    $mapData['VendorUID'] = null;
                    $mapData['UserUID'] = null;
                    break;
                    
                case 'Vendor':
                    $mapData['CustomerUID'] = null;
                    $mapData['VendorUID'] = $entityId;
                    $mapData['UserUID'] = null;
                    break;
                    
                case 'Employee':
                    $mapData['CustomerUID'] = null;
                    $mapData['VendorUID'] = null;
                    $mapData['UserUID'] = $entityId;
                    break;
                    
                default:
                    throw new Exception("Unsupported entity type: {$entityType}");
            }
            
            $mapResp = $this->CI->dbwrite_model->insertData('Accounting', 'EntityLedgerMap', $mapData);
            
            if ($mapResp->Error) {
                throw new Exception($mapResp->Message);
            }
            
            return $mapResp->ID;
            
        } catch (Exception $e) {
            throw new Exception("Failed to map {$entityType} to ledger: " . $e->getMessage());
        }

    }

    public function getEntityLedgerMapping($entityId, $entityType) {

        try {
            
            $columnMap = [
                'Customer' => 'CustomerUID',
                'Vendor' => 'VendorUID',
                'Employee' => 'UserUID'
            ];
            
            if (!isset($columnMap[$entityType])) {
                throw new Exception("Invalid entity type: {$entityType}");
            }
            
            $column = $columnMap[$entityType];
            
            $this->CI->load->model('accountledger_model');
            return $this->CI->accountledger_model->getEntityLedgerByColumn($column, $entityId, $entityType);
            
        } catch (Exception $e) {
            throw new Exception("Failed to get ledger mapping: " . $e->getMessage());
        }
    }

    public function logLedgerAudit($entityId, $ledgerId, $changeType, $details = [], $entityType = 'Customer') {

        try {
            
            $entityColumns = $this->getEntityColumns($entityType, $entityId);
            
            $auditData = [
                'LedgerUID' => $ledgerId,
                'EntityType' => $entityType,
                'ChangeType' => $changeType,
                'FieldChanged' => $details['field'] ?? NULL,
                'OldValue' => $details['old_value'] ?? NULL,
                'NewValue' => $details['new_value'] ?? NULL,
                'ChangeDetails' => json_encode([
                    'AmountChange' => $details['amount_change'] ?? 0,
                    'BalanceTypeChange' => $details['balance_type_change'] ?? NULL,
                    'Reason' => $details['reason'] ?? 'Ledger update',
                    'ReferenceId' => $details['reference_id'] ?? NULL,
                    'EntityType' => $entityType
                ]),
                'CreatedBy' => $this->CI->pageData['JwtData']->User->UserUID,
                'UpdatedBy' => $this->CI->pageData['JwtData']->User->UserUID,
                'IPAddress' => $this->CI->input->ip_address(),
                'UserAgent' => $this->CI->input->user_agent()
            ];
            
            // Merge entity columns
            $auditData = array_merge($auditData, $entityColumns);
            
            $this->CI->dbwrite_model->insertData('Accounting', 'LedgerAuditTrail', $auditData);
            
        } catch (Exception $e) {
            throw new Exception("Ledger audit log failed: " . $e->getMessage());
        }

    }

    private function getEntityColumns($entityType, $entityId) {

        $columns = [
            'CustomerUID' => NULL,
            'VendorUID' => NULL,
            'UserUID' => NULL,
            'BankUID' => NULL
        ];
        
        switch ($entityType) {
            case 'Customer':
                $columns['CustomerUID'] = $entityId;
                break;
            case 'Vendor':
                $columns['VendorUID'] = $entityId;
                break;
            case 'Employee':
                $columns['UserUID'] = $entityId;
                break;
            case 'Bank':
                $columns['BankUID'] = $entityId;
                break;
            default:
                // System/Generic audit
                break;
        }
        
        return $columns;
        
    }

    public function updateEntityLedgerInfo($entityId, $postData, $entityType = 'Customer') {

        try {

            $this->CI->load->model('accountledger_model');
            $entityLedger = $this->getEntityLedgerMapping($entityId, $entityType);

            // Customer | Vendor | Employee has no ledger yet - create one
            if (!$entityLedger || !$entityLedger->LedgerUID) {
                return $this->createLedgerAccountingInfo(
                    $entityId,
                    [
                        'Name' => $postData['Name'],
                        'OpeningBalance' => $postData['OpeningBalance'] ?? $postData['DebitCreditAmount'] ?? 0,
                        'OpeningBalanceType' => $postData['OpeningBalanceType'] ?? $postData['DebitCreditCheck'] ?? 'Debit',
                    ],
                    $entityType
                );
            }

            $ledgerUID = $entityLedger->LedgerUID;

            // Check if any transactions exist
            $hasTransactions = $this->CI->accountledger_model->ledgerHasTransactions($ledgerUID);

            // Track changes for audit
            $changes = [];
            $ledgerUpdateData = [];

            /** ---------------- Ledger Name ---------------- */
            if ($entityLedger->LedgerName != $postData['Name']) {
                $ledgerUpdateData['LedgerName'] = $postData['Name'];
                $changes[] = [
                    'field' => 'LedgerName',
                    'old_value' => $entityLedger->LedgerName,
                    'new_value' => $postData['Name']
                ];
            }

            /** ---------------- Opening Balance ---------------- */
            $newBalance = (float)($postData['DebitCreditAmount'] ?? 0);
            $newType = $postData['DebitCreditCheck'] ?? 'Debit';

            if (!$hasTransactions && ((float)$entityLedger->OpeningBalance !== $newBalance || $entityLedger->OpeningBalanceType !== $newType)) {

                // Update opening
                $ledgerUpdateData['OpeningBalance'] = $newBalance;
                $ledgerUpdateData['OpeningBalanceType'] = $newType;

                // Sync current balance
                $ledgerUpdateData['CurrentBalance'] = $newBalance;
                $ledgerUpdateData['CurrentBalanceType'] = $newType;

                $changes[] = [
                    'field' => 'OpeningBalance',
                    'old_value' => $entityLedger->OpeningBalance . ' ' . $entityLedger->OpeningBalanceType,
                    'new_value' => $newBalance . ' ' . $newType
                ];

                // Update yearly opening balance
                $financialYear = $this->getFinancialYear();

                $this->CI->dbwrite_model->updateData('Accounting', 'LedgerYearOpening', ['OpeningBalance' => $newBalance, 'OpeningBalanceType' => $newType], ['LedgerUID' => $ledgerUID, 'FinancialYear' => $financialYear]);

            }
            
            /** ---------------- Persist Changes ---------------- */
            if (!empty($ledgerUpdateData)) {
                $updateResp = $this->CI->dbwrite_model->updateData('Accounting', 'ChartOfAccounts', $ledgerUpdateData, ['LedgerUID' => $entityLedger->LedgerUID]);
                if ($updateResp->Error) {
                    throw new Exception('Failed to update ledger: ' . $updateResp->Message);
                }

                // Log audit
                foreach ($changes as $change) {
                    $this->logLedgerAudit($entityId, $entityLedger->LedgerUID, 'UPDATE', $change, $entityType);
                }
            }

            return $entityLedger->LedgerUID;

        } catch (Exception $e) {
            throw new Exception("{$entityType} ledger update failed: " . $e->getMessage());
        }

    }

    public function deactivateEntityLedger($entityId, $ledgerId, $entityType = 'Customer') {

        try {

            // Defensive check – ledger should not have transactions
            $this->CI->load->model('accountledger_model');
            if ($this->CI->accountledger_model->ledgerHasTransactions($ledgerId)) {
                throw new Exception('Ledger has accounting transactions and cannot be deactivated');
            }

            // 1. Update ledger status (soft delete)
            $ledgerData = ['IsDeleted' => 1, 'IsActive' => 0];
            
            $updateResp = $this->CI->dbwrite_model->updateData('Accounting', 'ChartOfAccounts', $ledgerData, ['LedgerUID' => $ledgerId]);
            if ($updateResp->Error) throw new Exception($updateResp->Message);

            // Soft deactivate entity-ledger mapping
            $this->CI->dbwrite_model->updateData('Accounting', 'EntityLedgerMap', ['IsDeleted' => 1], ['LedgerUID' => $ledgerId, 'EntityType' => $entityType]);
            
            // 4Audit log
            $this->logLedgerAudit(
                $entityId,
                $ledgerId,
                'DELETE',
                [
                    'field' => 'LedgerStatus',
                    'reason' => "{$entityType} deleted",
                    'old_value' => 'Active',
                    'new_value' => 'Deactivated',
                    'action' => 'Ledger deactivated'
                ],
                $entityType,
            );
            
            return true;
            
        } catch (Exception $e) {
            throw new Exception("{$entityType} ledger deactivation failed: " . $e->getMessage());
        }

    }

    private function getFinancialYear() {
        $year = (int)date('Y');
        return (date('m') >= 4) ? $year : ($year - 1);
    }

}