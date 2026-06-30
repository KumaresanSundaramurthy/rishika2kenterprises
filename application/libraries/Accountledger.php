<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property object $accountledger_model
 * @property object $dbwrite_model
 * @property array  $pageData
 * @property object $input
 */
Class Accountledger {

    /** @var object */
    protected $CI;
    /** @var array */
    private $_sysLedgerCache = [];

    public function __construct() {
        $this->CI =& get_instance();
    }

    /** Returns the current org UID from JWT — used in every Accounting insert/query */
    private function _orgUID(): int {
        return (int)($this->CI->pageData['JwtData']->Org->OrgUID ?? 0);
    }

    public function createLedgerAccountingInfo($entityId, $postData, $entityType = 'Customer') {

        try {
            
            $ledgerConfig = $this->getLedgerConfig($entityType);

            // Cache parent ledger existence — it never changes once chart of accounts is set up
            $cacheKey = 'parent_' . $ledgerConfig['parent_id'];
            if (!isset($this->_sysLedgerCache[$cacheKey])) {
                $this->CI->load->model('accountledger_model');
                $parentExists = $this->CI->accountledger_model->getLedgerById($ledgerConfig['parent_id'], $ledgerConfig['parent_type']);
                if (!$parentExists) {
                    throw new Exception("Parent ledger ({$ledgerConfig['parent_name']}) not found. Setup chart of accounts first.");
                }
                $this->_sysLedgerCache[$cacheKey] = true;
            }

            // Create ledger account
            $ledgerCode = $this->generateLedgerCode($entityId, $entityType);
            $ledgerData = [
                'OrgUID'             => $this->_orgUID(),
                'LedgerCode'         => $ledgerCode,
                'LedgerName'         => $postData['Name'],
                'LedgerType'         => $entityType,
                'ParentLedgerUID'    => $ledgerConfig['parent_id'],
                'OpeningBalance'     => $postData['OpeningBalance'],
                'OpeningBalanceType' => $postData['OpeningBalanceType'],
                'CurrentBalance'     => (float)$postData['OpeningBalance'],
                'CurrentBalanceType' => $postData['OpeningBalanceType'],
                'CreatedBy'          => $this->CI->pageData['JwtData']->User->UserUID,
                'UpdatedBy'          => $this->CI->pageData['JwtData']->User->UserUID,
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
                'OrgUID'            => $this->_orgUID(),
                'LedgerUID'         => $ledgerId,
                'FinancialYear'     => $financialYear,
                'OpeningBalance'    => (float) $postData['OpeningBalance'],
                'OpeningBalanceType'=> $postData['OpeningBalanceType'],
                'CreatedBy'         => $this->CI->pageData['JwtData']->User->UserUID,
                'UpdatedBy'         => $this->CI->pageData['JwtData']->User->UserUID,
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
                'OrgUID'     => $this->_orgUID(),
                'LedgerUID'  => $ledgerId,
                'EntityType' => $entityType,
                'CreatedBy'  => $this->CI->pageData['JwtData']->User->UserUID,
                'UpdatedBy'  => $this->CI->pageData['JwtData']->User->UserUID,
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
            
            // Parent row (Customer/Vendor) was inserted in the same open transaction.
            // InnoDB FK check tries to acquire a shared lock on a row that already has
            // an exclusive lock in our transaction → lock wait timeout (50s).
            // The row genuinely exists — disable FK checks just for this insert.
            $this->CI->dbwrite_model->setForeignKeyChecks(false);
            $mapResp = $this->CI->dbwrite_model->insertData('Accounting', 'EntityLedgerMap', $mapData);
            $this->CI->dbwrite_model->setForeignKeyChecks(true);

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
                'OrgUID'       => $this->_orgUID(),
                'LedgerUID'    => $ledgerId,
                'EntityType'   => $entityType,
                'ChangeType'   => $changeType,
                'FieldChanged' => $details['field'] ?? NULL,
                'OldValue'     => $details['old_value'] ?? NULL,
                'NewValue'     => $details['new_value'] ?? NULL,
                'ChangeDetails'=> json_encode([
                    'AmountChange'      => $details['amount_change'] ?? 0,
                    'BalanceTypeChange' => $details['balance_type_change'] ?? NULL,
                    'Reason'            => $details['reason'] ?? 'Ledger update',
                    'ReferenceId'       => $details['reference_id'] ?? NULL,
                    'EntityType'        => $entityType,
                ]),
                'CreatedBy' => $this->CI->pageData['JwtData']->User->UserUID,
                'UpdatedBy' => $this->CI->pageData['JwtData']->User->UserUID,
                'IPAddress' => $this->CI->input->ip_address(),
                'UserAgent' => $this->CI->input->user_agent(),
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
            $newType    = $postData['DebitCreditCheck'] ?? 'Debit';
            $oldBalance = (float)$entityLedger->OpeningBalance;
            $oldType    = $entityLedger->OpeningBalanceType ?? 'Debit';

            if ($oldBalance !== $newBalance || $oldType !== $newType) {

                $ledgerUpdateData['OpeningBalance']     = $newBalance;
                $ledgerUpdateData['OpeningBalanceType'] = $newType;

                // Only query JournalEntries when the balance has actually changed
                $hasTransactions = $this->CI->accountledger_model->ledgerHasTransactions($ledgerUID);

                if (!$hasTransactions) {
                    $ledgerUpdateData['CurrentBalance']     = $newBalance;
                    $ledgerUpdateData['CurrentBalanceType'] = $newType;
                } else {
                    $signOld = ($oldType === 'Debit') ?  $oldBalance : -$oldBalance;
                    $signNew = ($newType === 'Debit') ?  $newBalance : -$newBalance;
                    $curBal  = (float)$entityLedger->CurrentBalance;
                    $curType = $entityLedger->CurrentBalanceType ?? 'Debit';
                    $signCur = ($curType === 'Debit') ? $curBal : -$curBal;

                    $signResult = $signCur + ($signNew - $signOld);
                    $ledgerUpdateData['CurrentBalance']     = round(abs($signResult), 2);
                    $ledgerUpdateData['CurrentBalanceType'] = ($signResult >= 0) ? 'Debit' : 'Credit';
                }

                $changes[] = [
                    'field'     => 'OpeningBalance',
                    'old_value' => $oldBalance . ' ' . $oldType,
                    'new_value' => $newBalance . ' ' . $newType,
                ];

                // Update yearly opening balance
                $financialYear = $this->getFinancialYear();
                $this->CI->dbwrite_model->updateData('Accounting', 'LedgerYearOpening', ['OpeningBalance' => $newBalance, 'OpeningBalanceType' => $newType], ['OrgUID' => $this->_orgUID(), 'LedgerUID' => $ledgerUID, 'FinancialYear' => $financialYear]);

            }
            
            /** ---------------- Persist Changes ---------------- */
            if (!empty($ledgerUpdateData)) {
                $updateResp = $this->CI->dbwrite_model->updateData('Accounting', 'ChartOfAccounts', $ledgerUpdateData, ['OrgUID' => $this->_orgUID(), 'LedgerUID' => $entityLedger->LedgerUID]);
                if ($updateResp->Error) {
                    throw new Exception('Failed to update ledger: ' . $updateResp->Message);
                }

                // Audit log only when there are actual changes
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
            
            $updateResp = $this->CI->dbwrite_model->updateData('Accounting', 'ChartOfAccounts', $ledgerData, ['OrgUID' => $this->_orgUID(), 'LedgerUID' => $ledgerId]);
            if ($updateResp->Error) throw new Exception($updateResp->Message);

            // Soft deactivate entity-ledger mapping
            $this->CI->dbwrite_model->updateData('Accounting', 'EntityLedgerMap', ['IsDeleted' => 1], ['OrgUID' => $this->_orgUID(), 'LedgerUID' => $ledgerId, 'EntityType' => $entityType]);
            
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

    public function applyLedgerEntry($entityId, $entityType, $amount, $entryType, $referenceId = NULL) {

        $amount = round((float) $amount, 2);
        if ($amount <= 0) return;

        $mapping = $this->getEntityLedgerMapping($entityId, $entityType);
        if (!$mapping || empty($mapping->LedgerUID)) {
            throw new Exception("No ledger found for {$entityType} ID {$entityId}. Create the customer/vendor first.");
        }

        $ledgerUID      = $mapping->LedgerUID;
        $currentBalance = round((float) $mapping->CurrentBalance, 2);
        $currentType    = $mapping->CurrentBalanceType ?? 'Debit';

        // Standard double-entry: same side adds, opposite side nets
        if ($entryType === $currentType) {
            $newBalance = $currentBalance + $amount;
            $newType    = $currentType;
        } else {
            if ($amount >= $currentBalance) {
                $newBalance = round($amount - $currentBalance, 2);
                $newType    = $entryType;
            } else {
                $newBalance = round($currentBalance - $amount, 2);
                $newType    = $currentType;
            }
        }

        $updateResp = $this->CI->dbwrite_model->updateData(
            'Accounting', 'ChartOfAccounts',
            [
                'CurrentBalance'     => $newBalance,
                'CurrentBalanceType' => $newType,
                'UpdatedBy'          => $this->CI->pageData['JwtData']->User->UserUID,
            ],
            ['OrgUID' => $this->_orgUID(), 'LedgerUID' => $ledgerUID]
        );
        if ($updateResp->Error) {
            throw new Exception('Failed to update ledger balance: ' . $updateResp->Message);
        }

        // Audit log is non-critical — runs outside any caller transaction
        // so it cannot poison a parent commit. Failures are logged silently.
        try {
            $this->logLedgerAudit($entityId, $ledgerUID, 'BALANCE_ADJUST', [
                'reason'              => "{$entryType} entry applied",
                'amount_change'       => $amount,
                'balance_type_change' => $newType,
                'reference_id'        => $referenceId,
            ], $entityType);
        } catch (Exception $ignored) {
            log_message('error', "Ledger audit log failed [{$entityType} ID {$entityId}]: " . $ignored->getMessage());
        }

    }

    private function getFinancialYear() {
        $year = (int)date('Y');
        return (date('m') >= 4) ? $year : ($year - 1);
    }

    // -------------------------------------------------------------------------
    // System ledger lookup (cached per request)
    // -------------------------------------------------------------------------

    private function _getSystemLedgerUID($purpose) {
        if (array_key_exists($purpose, $this->_sysLedgerCache)) {
            return $this->_sysLedgerCache[$purpose];
        }

        $codeMap = [
            'sales_revenue'   => 'SYS-SALES',
            'purchase_cost'   => 'SYS-PURCH',
            'cgst_output'     => 'SYS-CGST-OUT',
            'sgst_output'     => 'SYS-SGST-OUT',
            'igst_output'     => 'SYS-IGST-OUT',
            'cgst_input'      => 'SYS-CGST-IN',
            'sgst_input'      => 'SYS-SGST-IN',
            'igst_input'      => 'SYS-IGST-IN',
            'expense_account' => 'SYS-EXPENSE',
            'income_account'  => 'SYS-IND-INC',
            'accounts_payable'=> 'SYS-AP',
            'accounts_receiv' => 'SYS-AR',
            'salary_expense'  => 'SYS-SALARY-EXP',
            'salary_payable'  => 'SYS-SALARY-PAY',
            'advance_clearing'=> 'SYS-EMP-ADV',   // Employee Advances (Asset) — Dr on advance, Cr on payroll recovery
            'employee_advance'=> 'SYS-EMP-ADV',
            'stock_in_hand'   => 'SYS-STOCK',
            'stock_adj_loss'  => 'SYS-STOCK-ADJ',
        ];

        $code = $codeMap[$purpose] ?? null;
        if (!$code) {
            return $this->_sysLedgerCache[$purpose] = null;
        }

        $this->CI->load->model('accountledger_model');
        $row = $this->CI->accountledger_model->getSystemLedgerByCode($code, $this->_orgUID());
        return $this->_sysLedgerCache[$purpose] = ($row ? (int) $row->LedgerUID : null);
    }

    // -------------------------------------------------------------------------
    // Low-level journal helpers
    // -------------------------------------------------------------------------

    private function _createJournalHeader($journalDate, $fy, $refType, $refID, $refNo, $narration, $createdBy) {
        $orgUID = $this->_orgUID();
        $tmpNo  = 'TMP-' . $orgUID . '-' . microtime(true) . '-' . $refID;
        $resp   = $this->CI->dbwrite_model->insertData('Accounting', 'GeneralJournal', [
            'OrgUID'        => $orgUID,
            'JournalNo'     => $tmpNo,
            'JournalDate'   => $journalDate,
            'FinancialYear' => (int) $fy,
            'ReferenceType' => $refType,
            'ReferenceID'   => (int) $refID,
            'ReferenceNo'   => $refNo,
            'Narration'     => $narration,
            'IsDeleted'     => 0,
            'CreatedBy'     => (int) $createdBy,
            'UpdatedBy'     => (int) $createdBy,
        ]);
        if ($resp->Error) throw new Exception('GeneralJournal insert failed: ' . $resp->Message);

        $jUID      = (int) $resp->ID;
        // JournalNo is per-org unique: ORG1-JRN-2026-0000001
        $journalNo = 'JRN-' . $fy . '-' . str_pad($jUID, 7, '0', STR_PAD_LEFT);
        $this->CI->dbwrite_model->updateData(
            'Accounting', 'GeneralJournal',
            ['JournalNo' => $journalNo],
            ['JournalUID' => $jUID, 'OrgUID' => $orgUID]
        );

        return $jUID;
    }

    private function _addJournalLine($journalUID, $ledgerUID, $type, $amount, $particulars, $journalDate, $fy, $createdBy) {
        $amount = round((float) $amount, 2);
        if ($amount <= 0 || !$ledgerUID) return;

        $orgUID = $this->_orgUID();

        // Insert journal entry line — capture EntryUID for LedgerBalances FK
        $entryResp = $this->CI->dbwrite_model->insertData('Accounting', 'JournalEntries', [
            'OrgUID'          => $orgUID,
            'JournalUID'      => (int) $journalUID,
            'LedgerUID'       => (int) $ledgerUID,
            'TransactionType' => $type,
            'Amount'          => $amount,
            'Particulars'     => $particulars,
            'IsDeleted'       => 0,
            'CreatedBy'       => (int) $createdBy,
            'UpdatedBy'       => (int) $createdBy,
        ]);
        $entryUID = $entryResp->Error ? 0 : (int)$entryResp->ID;

        // Compute running balance for this ledger
        $this->CI->load->model('accountledger_model');
        $last     = $this->CI->accountledger_model->getLastLedgerBalance((int)$ledgerUID, (int)$fy, $orgUID);
        $prevBal  = $last ? (float)$last->RunningBalance : 0.0;
        $prevType = $last ? $last->BalanceType : 'Debit';

        if ($type === $prevType) {
            $newBal  = $prevBal + $amount;
            $newType = $prevType;
        } else {
            if ($amount >= $prevBal) {
                $newBal  = round($amount - $prevBal, 2);
                $newType = $type;
            } else {
                $newBal  = round($prevBal - $amount, 2);
                $newType = $prevType;
            }
        }

        // One LedgerBalances row per journal ENTRY (EntryUID), not per journal
        // This allows the same ledger to appear in multiple lines of one journal
        $this->CI->dbwrite_model->insertData('Accounting', 'LedgerBalances', [
            'OrgUID'          => $orgUID,
            'LedgerUID'       => (int)$ledgerUID,
            'EntryUID'        => $entryUID,
            'FinancialYear'   => (int)$fy,
            'TransactionDate' => $journalDate,
            'JournalUID'      => (int)$journalUID,
            'DebitAmount'     => $type === 'Debit'  ? $amount : 0.00,
            'CreditAmount'    => $type === 'Credit' ? $amount : 0.00,
            'RunningBalance'  => $newBal,
            'BalanceType'     => $newType,
        ]);
    }

    // -------------------------------------------------------------------------
    // Public journal posting methods
    // -------------------------------------------------------------------------

    // Invoice / Sale: Dr Customer, Cr Sales + Cr Output Tax
    public function postSaleJournal($transUID, $transDate, $uniqueNumber, $fy, $netAmount, $taxableAmount, $cgst, $sgst, $igst, $customerUID, $createdBy) {
        $netAmount     = round((float) $netAmount, 2);
        $taxableAmount = round((float) $taxableAmount, 2);
        $cgst          = round((float) $cgst, 2);
        $sgst          = round((float) $sgst, 2);
        $igst          = round((float) $igst, 2);
        if ($netAmount <= 0) return;

        $mapping = $this->getEntityLedgerMapping($customerUID, 'Customer');
        if (!$mapping || empty($mapping->LedgerUID)) return;
        $custLedgerUID = (int) $mapping->LedgerUID;

        $refNo  = $uniqueNumber ?: null;
        $jUID   = $this->_createJournalHeader(
            $transDate, $fy, 'Invoice', $transUID, $refNo,
            'Sale Invoice ' . ($uniqueNumber ?: 'Draft') . ' — Customer #' . $customerUID,
            $createdBy
        );

        $this->_addJournalLine($jUID, $custLedgerUID, 'Debit', $netAmount,
            'Invoice ' . ($uniqueNumber ?: '') . ' — Amount Receivable', $transDate, $fy, $createdBy);

        $salesUID = $this->_getSystemLedgerUID('sales_revenue');
        if ($salesUID) {
            $this->_addJournalLine($jUID, $salesUID, 'Credit', $taxableAmount,
                'Sale of goods/services — ' . ($uniqueNumber ?: ''), $transDate, $fy, $createdBy);
        }

        if ($cgst > 0) {
            $uid = $this->_getSystemLedgerUID('cgst_output');
            if ($uid) $this->_addJournalLine($jUID, $uid, 'Credit', $cgst,
                'Output CGST — ' . ($uniqueNumber ?: ''), $transDate, $fy, $createdBy);
        }
        if ($sgst > 0) {
            $uid = $this->_getSystemLedgerUID('sgst_output');
            if ($uid) $this->_addJournalLine($jUID, $uid, 'Credit', $sgst,
                'Output SGST — ' . ($uniqueNumber ?: ''), $transDate, $fy, $createdBy);
        }
        if ($igst > 0) {
            $uid = $this->_getSystemLedgerUID('igst_output');
            if ($uid) $this->_addJournalLine($jUID, $uid, 'Credit', $igst,
                'Output IGST — ' . ($uniqueNumber ?: ''), $transDate, $fy, $createdBy);
        }
    }

    // Purchase Bill: Dr Purchase + Dr Input Tax, Cr Vendor
    public function postPurchaseJournal($transUID, $transDate, $uniqueNumber, $fy, $netAmount, $taxableAmount, $cgst, $sgst, $igst, $vendorUID, $createdBy) {
        $netAmount     = round((float) $netAmount, 2);
        $taxableAmount = round((float) $taxableAmount, 2);
        $cgst          = round((float) $cgst, 2);
        $sgst          = round((float) $sgst, 2);
        $igst          = round((float) $igst, 2);
        if ($netAmount <= 0) return;

        $mapping = $this->getEntityLedgerMapping($vendorUID, 'Vendor');
        if (!$mapping || empty($mapping->LedgerUID)) return;
        $vendLedgerUID = (int) $mapping->LedgerUID;

        $refNo = $uniqueNumber ?: null;
        $jUID  = $this->_createJournalHeader(
            $transDate, $fy, 'Purchase', $transUID, $refNo,
            'Purchase Bill ' . ($uniqueNumber ?: 'Draft') . ' — Vendor #' . $vendorUID,
            $createdBy
        );

        $purchUID = $this->_getSystemLedgerUID('purchase_cost');
        if ($purchUID) {
            $this->_addJournalLine($jUID, $purchUID, 'Debit', $taxableAmount,
                'Purchase of goods/services — ' . ($uniqueNumber ?: ''), $transDate, $fy, $createdBy);
        }

        if ($cgst > 0) {
            $uid = $this->_getSystemLedgerUID('cgst_input');
            if ($uid) $this->_addJournalLine($jUID, $uid, 'Debit', $cgst,
                'Input CGST — ' . ($uniqueNumber ?: ''), $transDate, $fy, $createdBy);
        }
        if ($sgst > 0) {
            $uid = $this->_getSystemLedgerUID('sgst_input');
            if ($uid) $this->_addJournalLine($jUID, $uid, 'Debit', $sgst,
                'Input SGST — ' . ($uniqueNumber ?: ''), $transDate, $fy, $createdBy);
        }
        if ($igst > 0) {
            $uid = $this->_getSystemLedgerUID('igst_input');
            if ($uid) $this->_addJournalLine($jUID, $uid, 'Debit', $igst,
                'Input IGST — ' . ($uniqueNumber ?: ''), $transDate, $fy, $createdBy);
        }

        $this->_addJournalLine($jUID, $vendLedgerUID, 'Credit', $netAmount,
            'Bill ' . ($uniqueNumber ?: '') . ' — Amount Payable', $transDate, $fy, $createdBy);
    }

    // Payment journal: received = Cr Customer; made = Dr Vendor
    public function postPaymentJournal($direction, $transUID, $paymentDate, $fy, $amount, $partyUID, $entityType, $createdBy) {
        $amount = round((float) $amount, 2);
        if ($amount <= 0) return;

        $mapping = $this->getEntityLedgerMapping($partyUID, $entityType);
        if (!$mapping || empty($mapping->LedgerUID)) return;
        $partyLedgerUID = (int) $mapping->LedgerUID;

        if ($direction === 'received') {
            $jUID = $this->_createJournalHeader(
                $paymentDate, $fy, 'Payment-In', $transUID, null,
                'Payment received — ' . $entityType . ' #' . $partyUID, $createdBy
            );
            $this->_addJournalLine($jUID, $partyLedgerUID, 'Credit', $amount,
                'Payment received against transaction #' . $transUID, $paymentDate, $fy, $createdBy);
        } elseif ($direction === 'made') {
            $jUID = $this->_createJournalHeader(
                $paymentDate, $fy, 'Payment-Out', $transUID, null,
                'Payment made — ' . $entityType . ' #' . $partyUID, $createdBy
            );
            $this->_addJournalLine($jUID, $partyLedgerUID, 'Debit', $amount,
                'Payment made against transaction #' . $transUID, $paymentDate, $fy, $createdBy);
        }
    }

    // Sales Return: Dr Sales Revenue + Dr Output Tax, Cr Customer (reversal of a sale)
    public function postSaleReturnJournal($transUID, $transDate, $uniqueNumber, $fy, $netAmount, $taxableAmount, $cgst, $sgst, $igst, $customerUID, $createdBy) {
        $netAmount     = round((float) $netAmount, 2);
        $taxableAmount = round((float) $taxableAmount, 2);
        $cgst          = round((float) $cgst, 2);
        $sgst          = round((float) $sgst, 2);
        $igst          = round((float) $igst, 2);
        if ($netAmount <= 0) return;

        $mapping = $this->getEntityLedgerMapping($customerUID, 'Customer');
        if (!$mapping || empty($mapping->LedgerUID)) return;
        $custLedgerUID = (int) $mapping->LedgerUID;

        $jUID = $this->_createJournalHeader(
            $transDate, $fy, 'SalesReturn', $transUID, $uniqueNumber ?: null,
            'Sales Return ' . ($uniqueNumber ?: 'Draft') . ' — Customer #' . $customerUID,
            $createdBy
        );

        $salesUID = $this->_getSystemLedgerUID('sales_revenue');
        if ($salesUID) {
            $this->_addJournalLine($jUID, $salesUID, 'Debit', $taxableAmount,
                'Sales return — goods/services reversed ' . ($uniqueNumber ?: ''), $transDate, $fy, $createdBy);
        }
        if ($cgst > 0) {
            $uid = $this->_getSystemLedgerUID('cgst_output');
            if ($uid) $this->_addJournalLine($jUID, $uid, 'Debit', $cgst,
                'Output CGST reversed — ' . ($uniqueNumber ?: ''), $transDate, $fy, $createdBy);
        }
        if ($sgst > 0) {
            $uid = $this->_getSystemLedgerUID('sgst_output');
            if ($uid) $this->_addJournalLine($jUID, $uid, 'Debit', $sgst,
                'Output SGST reversed — ' . ($uniqueNumber ?: ''), $transDate, $fy, $createdBy);
        }
        if ($igst > 0) {
            $uid = $this->_getSystemLedgerUID('igst_output');
            if ($uid) $this->_addJournalLine($jUID, $uid, 'Debit', $igst,
                'Output IGST reversed — ' . ($uniqueNumber ?: ''), $transDate, $fy, $createdBy);
        }
        $this->_addJournalLine($jUID, $custLedgerUID, 'Credit', $netAmount,
            'Sales return credit — ' . ($uniqueNumber ?: ''), $transDate, $fy, $createdBy);
    }

    // Purchase Return: Dr Vendor, Cr Purchase Cost + Cr Input Tax (reversal of a purchase)
    public function postPurchaseReturnJournal($transUID, $transDate, $uniqueNumber, $fy, $netAmount, $taxableAmount, $cgst, $sgst, $igst, $vendorUID, $createdBy) {
        $netAmount     = round((float) $netAmount, 2);
        $taxableAmount = round((float) $taxableAmount, 2);
        $cgst          = round((float) $cgst, 2);
        $sgst          = round((float) $sgst, 2);
        $igst          = round((float) $igst, 2);
        if ($netAmount <= 0) return;

        $mapping = $this->getEntityLedgerMapping($vendorUID, 'Vendor');
        if (!$mapping || empty($mapping->LedgerUID)) return;
        $vendLedgerUID = (int) $mapping->LedgerUID;

        $jUID = $this->_createJournalHeader(
            $transDate, $fy, 'PurchaseReturn', $transUID, $uniqueNumber ?: null,
            'Purchase Return ' . ($uniqueNumber ?: 'Draft') . ' — Vendor #' . $vendorUID,
            $createdBy
        );

        $this->_addJournalLine($jUID, $vendLedgerUID, 'Debit', $netAmount,
            'Purchase return debit — ' . ($uniqueNumber ?: ''), $transDate, $fy, $createdBy);

        $purchUID = $this->_getSystemLedgerUID('purchase_cost');
        if ($purchUID) {
            $this->_addJournalLine($jUID, $purchUID, 'Credit', $taxableAmount,
                'Purchase return — goods/services reversed ' . ($uniqueNumber ?: ''), $transDate, $fy, $createdBy);
        }
        if ($cgst > 0) {
            $uid = $this->_getSystemLedgerUID('cgst_input');
            if ($uid) $this->_addJournalLine($jUID, $uid, 'Credit', $cgst,
                'Input CGST reversed — ' . ($uniqueNumber ?: ''), $transDate, $fy, $createdBy);
        }
        if ($sgst > 0) {
            $uid = $this->_getSystemLedgerUID('sgst_input');
            if ($uid) $this->_addJournalLine($jUID, $uid, 'Credit', $sgst,
                'Input SGST reversed — ' . ($uniqueNumber ?: ''), $transDate, $fy, $createdBy);
        }
        if ($igst > 0) {
            $uid = $this->_getSystemLedgerUID('igst_input');
            if ($uid) $this->_addJournalLine($jUID, $uid, 'Credit', $igst,
                'Input IGST reversed — ' . ($uniqueNumber ?: ''), $transDate, $fy, $createdBy);
        }
    }

    // Expense: Dr Expense Account, Cr Accounts Payable
    public function postExpenseJournal($expenseUID, $expenseDate, $expenseNumber, $fy, $netAmount, $createdBy) {
        $netAmount = round((float) $netAmount, 2);
        if ($netAmount <= 0) return;

        $jUID = $this->_createJournalHeader(
            $expenseDate, $fy, 'Expense', $expenseUID, $expenseNumber ?: null,
            'Expense ' . ($expenseNumber ?: '#' . $expenseUID),
            $createdBy
        );

        $expUID = $this->_getSystemLedgerUID('expense_account');
        if ($expUID) {
            $this->_addJournalLine($jUID, $expUID, 'Debit', $netAmount,
                'Expense recorded — ' . ($expenseNumber ?: '#' . $expenseUID), $expenseDate, $fy, $createdBy);
        }
        $apUID = $this->_getSystemLedgerUID('accounts_payable');
        if ($apUID) {
            $this->_addJournalLine($jUID, $apUID, 'Credit', $netAmount,
                'Payable for expense — ' . ($expenseNumber ?: '#' . $expenseUID), $expenseDate, $fy, $createdBy);
        }
    }

    // Indirect Income: Dr Accounts Receivable, Cr Income Account
    public function postIndirectIncomeJournal($incomeUID, $incomeDate, $incomeNumber, $fy, $netAmount, $createdBy) {
        $netAmount = round((float) $netAmount, 2);
        if ($netAmount <= 0) return;

        $jUID = $this->_createJournalHeader(
            $incomeDate, $fy, 'IndirectIncome', $incomeUID, $incomeNumber ?: null,
            'Indirect Income ' . ($incomeNumber ?: '#' . $incomeUID),
            $createdBy
        );

        $arUID = $this->_getSystemLedgerUID('accounts_receiv');
        if ($arUID) {
            $this->_addJournalLine($jUID, $arUID, 'Debit', $netAmount,
                'Income receivable — ' . ($incomeNumber ?: '#' . $incomeUID), $incomeDate, $fy, $createdBy);
        }
        $incUID = $this->_getSystemLedgerUID('income_account');
        if ($incUID) {
            $this->_addJournalLine($jUID, $incUID, 'Credit', $netAmount,
                'Indirect income recorded — ' . ($incomeNumber ?: '#' . $incomeUID), $incomeDate, $fy, $createdBy);
        }
    }

    // Salary Advance approved: Dr Employee Advances (Asset), Cr Accounts Payable (cash out)
    public function postAdvanceJournal(int $advanceUID, string $advanceDate, int $fy, float $amount, int $createdBy) {
        $amount = round($amount, 2);
        if ($amount <= 0) return;

        $jUID = $this->_createJournalHeader(
            $advanceDate, $fy, 'SalaryAdvance', $advanceUID, 'ADV-' . $advanceUID,
            'Salary advance approved — Advance #' . $advanceUID,
            $createdBy
        );

        // Dr: Employee Advances (our asset — employee owes us this amount)
        $advUID = $this->_getSystemLedgerUID('employee_advance');
        if ($advUID) {
            $this->_addJournalLine($jUID, $advUID, 'Debit', $amount,
                'Advance given to employee — #' . $advanceUID, $advanceDate, $fy, $createdBy);
        }

        // Cr: Accounts Payable (represents cash/bank outflow)
        $apUID = $this->_getSystemLedgerUID('accounts_payable');
        if ($apUID) {
            $this->_addJournalLine($jUID, $apUID, 'Credit', $amount,
                'Cash/bank paid for advance #' . $advanceUID, $advanceDate, $fy, $createdBy);
        }
    }

    // Payroll: Dr Salary & Wages Expense, Cr Salary Payable
    public function postPayrollJournal(int $payrollUID, string $payrollDate, int $fy, float $grossAmount, float $netAmount, float $deductions, float $advanceRecovery, int $createdBy) {
        $grossAmount    = round($grossAmount, 2);
        $netAmount      = round($netAmount, 2);
        $deductions     = round($deductions, 2);
        $advanceRecovery= round($advanceRecovery, 2);
        if ($grossAmount <= 0) return;

        $jUID = $this->_createJournalHeader(
            $payrollDate, $fy, 'Payroll', $payrollUID, 'PAY-' . $payrollUID,
            'Payroll processed for ' . date('F Y', mktime(0,0,0,1,1,$fy)),
            $createdBy
        );

        // Dr: Salary & Wages Expense (full gross)
        $salExpUID = $this->_getSystemLedgerUID('salary_expense');
        if ($salExpUID) {
            $this->_addJournalLine($jUID, $salExpUID, 'Debit', $grossAmount,
                'Salary expense for payroll #' . $payrollUID, $payrollDate, $fy, $createdBy);
        }

        // Cr: Salary Payable (net take-home)
        $salPayUID = $this->_getSystemLedgerUID('salary_payable');
        if ($salPayUID && $netAmount > 0) {
            $this->_addJournalLine($jUID, $salPayUID, 'Credit', $netAmount,
                'Net salary payable — payroll #' . $payrollUID, $payrollDate, $fy, $createdBy);
        }

        // Cr: Advance Recovery Clearing (reduces employee advance balance)
        $advUID = $this->_getSystemLedgerUID('advance_clearing');
        if ($advUID && $advanceRecovery > 0) {
            $this->_addJournalLine($jUID, $advUID, 'Credit', $advanceRecovery,
                'Advance recovery — payroll #' . $payrollUID, $payrollDate, $fy, $createdBy);
        }

        // Cr: Other deductions (PF, ESI, TDS etc. — goes to a clearing/deductions account)
        $otherDed = round($deductions - $advanceRecovery, 2);
        $apUID    = $this->_getSystemLedgerUID('accounts_payable');
        if ($apUID && $otherDed > 0) {
            $this->_addJournalLine($jUID, $apUID, 'Credit', $otherDed,
                'Salary deductions (PF/ESI/TDS) — payroll #' . $payrollUID, $payrollDate, $fy, $createdBy);
        }
    }

    // Fund Transfer (Contra): Dr Destination Bank, Cr Source Bank
    public function postFundTransferJournal(int $transferUID, string $transferDate, int $fy, float $amount, int $fromBankUID, int $toBankUID, int $createdBy) {
        $amount = round($amount, 2);
        if ($amount <= 0) return;

        $fromLedgerUID = $this->_getOrCreateBankLedgerUID($fromBankUID, $createdBy);
        $toLedgerUID   = $this->_getOrCreateBankLedgerUID($toBankUID,   $createdBy);

        if (!$fromLedgerUID || !$toLedgerUID) {
            log_message('error', 'Fund transfer journal skipped — bank ledger(s) could not be resolved. FromBank=' . $fromBankUID . ' ToBank=' . $toBankUID);
            return;
        }

        $jUID = $this->_createJournalHeader(
            $transferDate, $fy, 'FundTransfer', $transferUID, 'FT-' . $transferUID,
            'Fund transfer between bank accounts — Ref #' . $transferUID,
            $createdBy
        );

        // Dr: Destination bank (receives funds)
        $this->_addJournalLine($jUID, $toLedgerUID, 'Debit', $amount,
            'Transfer in — bank account #' . $toBankUID, $transferDate, $fy, $createdBy);

        // Cr: Source bank (pays out funds)
        $this->_addJournalLine($jUID, $fromLedgerUID, 'Credit', $amount,
            'Transfer out — bank account #' . $fromBankUID, $transferDate, $fy, $createdBy);
    }

    // Public entry point — called from Settings when a new bank account is saved.
    public function createBankLedger(int $bankUID, int $createdBy): void {
        $this->_getOrCreateBankLedgerUID($bankUID, $createdBy);
    }

    // Returns the LedgerUID for a bank account — creates one on-the-fly if it doesn't exist yet.
    private function _getOrCreateBankLedgerUID(int $bankUID, int $createdBy): ?int {
        $this->CI->load->model('accountledger_model');
        $orgUID = $this->_orgUID();

        // 1. Try existing mapping
        $existing = $this->CI->accountledger_model->getEntityLedgerByColumn('BankUID', $bankUID, 'Bank');
        if ($existing) return (int)$existing->LedgerUID;

        // 2. Fetch bank account details from OrgBankAccountsTbl
        $readDb = $this->CI->load->database('ReadDB', TRUE);
        $readDb->db_debug = FALSE;
        $readDb->select('BankAccountUID, AccountName, BankName, IsCash');
        $readDb->from('Organisation.OrgBankAccountsTbl');
        $readDb->where(['BankAccountUID' => $bankUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]);
        $bankRow = $readDb->get()->row();
        if (!$bankRow) return null;

        // 3. Find the BANK_ACCOUNTS account group for this org
        $readDb->select('GroupUID');
        $readDb->from('Accounting.AccountGroupTbl');
        $readDb->where(['OrgUID' => $orgUID, 'GroupCode' => ($bankRow->IsCash ? 'CASH_IN_HAND' : 'BANK_ACCOUNTS'), 'IsDeleted' => 0]);
        $groupRow = $readDb->get()->row();
        if (!$groupRow) return null;

        // 4. Create a ChartOfAccounts ledger for this bank account
        $ledgerCode = ($bankRow->IsCash ? 'CASH' : 'BNK') . '-' . str_pad($bankUID, 5, '0', STR_PAD_LEFT);
        $ledgerName = $bankRow->AccountName . ($bankRow->BankName ? ' (' . $bankRow->BankName . ')' : '');
        $ledgerResp = $this->CI->dbwrite_model->insertData('Accounting', 'ChartOfAccounts', [
            'OrgUID'          => $orgUID,
            'GroupUID'        => (int)$groupRow->GroupUID,
            'LedgerCode'      => $ledgerCode,
            'LedgerName'      => $ledgerName,
            'Nature'          => 'Asset',
            'NatureSign'      => 'Dr',
            'IsContra'        => 0,
            'IsPostable'      => 1,
            'IsSystem'        => 0,
            'IsBankAccount'   => $bankRow->IsCash ? 0 : 1,
            'CreatedBy'       => $createdBy,
            'UpdatedBy'       => $createdBy,
        ]);
        if ($ledgerResp->Error) {
            log_message('error', 'Failed to auto-create bank ledger for BankUID=' . $bankUID . ': ' . $ledgerResp->Message);
            return null;
        }
        $ledgerUID = (int)$ledgerResp->ID;

        // 5. Map the bank account to its new ledger in EntityLedgerMap
        $mapResp = $this->CI->dbwrite_model->insertData('Accounting', 'EntityLedgerMap', [
            'OrgUID'     => $orgUID,
            'LedgerUID'  => $ledgerUID,
            'BankUID'    => $bankUID,
            'EntityType' => 'Bank',
            'CreatedBy'  => $createdBy,
            'UpdatedBy'  => $createdBy,
        ]);
        if ($mapResp->Error) {
            log_message('error', 'Failed to map bank ledger for BankUID=' . $bankUID . ': ' . $mapResp->Message);
            return null;
        }

        return $ledgerUID;
    }

    // Manual Stock Adjustment: Stock IN → Dr Stock-in-Hand / Cr Purchase Cost
    //                          Stock OUT → Dr Stock Adjustment Loss / Cr Stock-in-Hand
    public function postStockAdjustmentJournal(int $adjUID, string $adjDate, int $fy, string $adjType, float $stockValue, int $createdBy) {
        $stockValue = round($stockValue, 2);
        if ($stockValue <= 0) return;

        $jUID = $this->_createJournalHeader(
            $adjDate, $fy, 'StockAdjustment', $adjUID, 'ADJ-' . $adjUID,
            'Manual stock ' . strtolower($adjType) . ' adjustment — Adj #' . $adjUID,
            $createdBy
        );

        $stockUID = $this->_getSystemLedgerUID('stock_in_hand');

        if ($adjType === 'IN') {
            // Dr: Stock-in-Hand (inventory asset increases)
            if ($stockUID) {
                $this->_addJournalLine($jUID, $stockUID, 'Debit', $stockValue,
                    'Stock received — Adj #' . $adjUID, $adjDate, $fy, $createdBy);
            }
            // Cr: Purchase Cost (cost of goods received without invoice)
            $purchUID = $this->_getSystemLedgerUID('purchase_cost');
            if ($purchUID) {
                $this->_addJournalLine($jUID, $purchUID, 'Credit', $stockValue,
                    'Stock addition cost — Adj #' . $adjUID, $adjDate, $fy, $createdBy);
            }
        } else {
            // Dr: Stock Adjustment Loss (expense for stock written off / consumed)
            $adjLossUID = $this->_getSystemLedgerUID('stock_adj_loss');
            if ($adjLossUID) {
                $this->_addJournalLine($jUID, $adjLossUID, 'Debit', $stockValue,
                    'Stock removed/written off — Adj #' . $adjUID, $adjDate, $fy, $createdBy);
            }
            // Cr: Stock-in-Hand (inventory asset decreases)
            if ($stockUID) {
                $this->_addJournalLine($jUID, $stockUID, 'Credit', $stockValue,
                    'Stock reduction — Adj #' . $adjUID, $adjDate, $fy, $createdBy);
            }
        }
    }

    // Reverse all non-deleted journals for a given reference — creates counter-entry journals
    public function reverseJournal($refType, $transUID, $createdBy) {
        $this->CI->load->model('accountledger_model');
        $journals = $this->CI->accountledger_model->getJournalByReference($refType, (int) $transUID);

        foreach ($journals as $journal) {
            $jUID    = (int) $journal->JournalUID;
            $fy      = (int) $journal->FinancialYear;
            $revDate = date('Y-m-d');

            $entries = $this->CI->accountledger_model->getJournalEntries($jUID);

            // Soft-delete original journal header + lines (scoped to this org)
            $this->CI->dbwrite_model->updateData('Accounting', 'GeneralJournal',
                ['IsDeleted' => 1], ['OrgUID' => $this->_orgUID(), 'JournalUID' => $jUID]);
            $this->CI->dbwrite_model->updateData('Accounting', 'JournalEntries',
                ['IsDeleted' => 1], ['OrgUID' => $this->_orgUID(), 'JournalUID' => $jUID, 'IsDeleted' => 0]);

            if (empty($entries)) continue;

            // Create reversal journal with swapped Dr/Cr
            $revUID = $this->_createJournalHeader(
                $revDate, $fy,
                'Reversal-' . $refType, (int) $transUID,
                'REV-' . $journal->JournalNo,
                'Reversal of journal ' . $journal->JournalNo,
                $createdBy
            );

            foreach ($entries as $entry) {
                $counterType = ($entry->TransactionType === 'Debit') ? 'Credit' : 'Debit';
                $this->_addJournalLine(
                    $revUID, (int) $entry->LedgerUID, $counterType,
                    (float) $entry->Amount,
                    'Reversal: ' . ($entry->Particulars ?? ''),
                    $revDate, $fy, $createdBy
                );
            }
        }
    }

}