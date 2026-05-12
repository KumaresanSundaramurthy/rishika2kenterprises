<?php defined('BASEPATH') OR exit('No direct script access allowed');

Class Accountledger {

    protected $CI;
    private   $_sysLedgerCache = [];

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
            $newType    = $postData['DebitCreditCheck'] ?? 'Debit';
            $oldBalance = (float)$entityLedger->OpeningBalance;
            $oldType    = $entityLedger->OpeningBalanceType ?? 'Debit';

            if ($oldBalance !== $newBalance || $oldType !== $newType) {

                $ledgerUpdateData['OpeningBalance']     = $newBalance;
                $ledgerUpdateData['OpeningBalanceType'] = $newType;

                if (!$hasTransactions) {
                    // No transactions yet — current balance mirrors opening balance
                    $ledgerUpdateData['CurrentBalance']     = $newBalance;
                    $ledgerUpdateData['CurrentBalanceType'] = $newType;
                } else {
                    // Transactions exist — shift current balance by the opening balance delta
                    // Convert to signed numbers (Debit = +, Credit = -)
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
            ['LedgerUID' => $ledgerUID]
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
            'sales_revenue' => 'SYS-SALES',
            'purchase_cost' => 'SYS-PURCH',
            'cgst_output'   => 'SYS-CGST-OUT',
            'sgst_output'   => 'SYS-SGST-OUT',
            'igst_output'   => 'SYS-IGST-OUT',
            'cgst_input'    => 'SYS-CGST-IN',
            'sgst_input'    => 'SYS-SGST-IN',
            'igst_input'    => 'SYS-IGST-IN',
        ];

        $code = $codeMap[$purpose] ?? null;
        if (!$code) {
            return $this->_sysLedgerCache[$purpose] = null;
        }

        $this->CI->load->model('accountledger_model');
        $row = $this->CI->accountledger_model->getSystemLedgerByCode($code);
        return $this->_sysLedgerCache[$purpose] = ($row ? (int) $row->LedgerUID : null);
    }

    // -------------------------------------------------------------------------
    // Low-level journal helpers
    // -------------------------------------------------------------------------

    private function _createJournalHeader($journalDate, $fy, $refType, $refID, $refNo, $narration, $createdBy) {
        $tmpNo = 'TMP-' . microtime(true) . '-' . $refID;
        $resp  = $this->CI->dbwrite_model->insertData('Accounting', 'GeneralJournal', [
            'JournalNo'     => $tmpNo,
            'JournalDate'   => $journalDate,
            'FinancialYear' => (int) $fy,
            'ReferenceType' => $refType,
            'ReferenceID'   => (int) $refID,
            'ReferenceNo'   => $refNo,
            'Narration'     => $narration,
            'IsDeleted'     => 0,
            'CreatedBy'     => (int) $createdBy,
        ]);
        if ($resp->Error) throw new Exception('GeneralJournal insert failed: ' . $resp->Message);

        $jUID      = (int) $resp->ID;
        $journalNo = 'JRN-' . $fy . '-' . str_pad($jUID, 7, '0', STR_PAD_LEFT);
        $this->CI->dbwrite_model->updateData(
            'Accounting', 'GeneralJournal',
            ['JournalNo' => $journalNo],
            ['JournalUID' => $jUID]
        );

        return $jUID;
    }

    private function _addJournalLine($journalUID, $ledgerUID, $type, $amount, $particulars, $journalDate, $fy, $createdBy) {
        $amount = round((float) $amount, 2);
        if ($amount <= 0 || !$ledgerUID) return;

        $this->CI->dbwrite_model->insertData('Accounting', 'JournalEntries', [
            'JournalUID'      => (int) $journalUID,
            'LedgerUID'       => (int) $ledgerUID,
            'TransactionType' => $type,
            'Amount'          => $amount,
            'Particulars'     => $particulars,
            'IsDeleted'       => 0,
            'CreatedBy'       => (int) $createdBy,
            'UpdatedBy'       => (int) $createdBy,
        ]);

        // Update running balance in LedgerBalances
        $this->CI->load->model('accountledger_model');
        $last     = $this->CI->accountledger_model->getLastLedgerBalance((int) $ledgerUID, (int) $fy);
        $prevBal  = $last ? (float) $last->RunningBalance : 0.0;
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

        $this->CI->dbwrite_model->insertData('Accounting', 'LedgerBalances', [
            'LedgerUID'       => (int) $ledgerUID,
            'FinancialYear'   => (int) $fy,
            'TransactionDate' => $journalDate,
            'JournalUID'      => (int) $journalUID,
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

    // Credit Note: Dr Sales Revenue + Dr Output Tax, Cr Customer (reversal of a sale)
    public function postCreditNoteJournal($transUID, $transDate, $uniqueNumber, $fy, $netAmount, $taxableAmount, $cgst, $sgst, $igst, $customerUID, $createdBy) {
        $netAmount     = round((float) $netAmount, 2);
        $taxableAmount = round((float) $taxableAmount, 2);
        $cgst          = round((float) $cgst, 2);
        $sgst          = round((float) $sgst, 2);
        $igst          = round((float) $igst, 2);
        if ($netAmount <= 0) return;

        $mapping = $this->getEntityLedgerMapping($customerUID, 'Customer');
        if (!$mapping || empty($mapping->LedgerUID)) return;
        $custLedgerUID = (int) $mapping->LedgerUID;

        $refNo = $uniqueNumber ?: null;
        $jUID  = $this->_createJournalHeader(
            $transDate, $fy, 'CreditNote', $transUID, $refNo,
            'Credit Note ' . ($uniqueNumber ?: 'Draft') . ' — Customer #' . $customerUID,
            $createdBy
        );

        $salesUID = $this->_getSystemLedgerUID('sales_revenue');
        if ($salesUID) {
            $this->_addJournalLine($jUID, $salesUID, 'Debit', $taxableAmount,
                'Sales return — ' . ($uniqueNumber ?: ''), $transDate, $fy, $createdBy);
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
            'Credit Note ' . ($uniqueNumber ?: '') . ' — Customer Credit', $transDate, $fy, $createdBy);
    }

    // Debit Note: Dr Vendor, Cr Purchase Cost + Cr Input Tax (reversal of a purchase)
    public function postDebitNoteJournal($transUID, $transDate, $uniqueNumber, $fy, $netAmount, $taxableAmount, $cgst, $sgst, $igst, $vendorUID, $createdBy) {
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
            $transDate, $fy, 'DebitNote', $transUID, $refNo,
            'Debit Note ' . ($uniqueNumber ?: 'Draft') . ' — Vendor #' . $vendorUID,
            $createdBy
        );

        $this->_addJournalLine($jUID, $vendLedgerUID, 'Debit', $netAmount,
            'Debit Note ' . ($uniqueNumber ?: '') . ' — Vendor Debit', $transDate, $fy, $createdBy);

        $purchUID = $this->_getSystemLedgerUID('purchase_cost');
        if ($purchUID) {
            $this->_addJournalLine($jUID, $purchUID, 'Credit', $taxableAmount,
                'Purchase return — ' . ($uniqueNumber ?: ''), $transDate, $fy, $createdBy);
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

    // Reverse all non-deleted journals for a given reference — creates counter-entry journals
    public function reverseJournal($refType, $transUID, $createdBy) {
        $this->CI->load->model('accountledger_model');
        $journals = $this->CI->accountledger_model->getJournalByReference($refType, (int) $transUID);

        foreach ($journals as $journal) {
            $jUID    = (int) $journal->JournalUID;
            $fy      = (int) $journal->FinancialYear;
            $revDate = date('Y-m-d');

            $entries = $this->CI->accountledger_model->getJournalEntries($jUID);

            // Soft-delete original journal header + lines
            $this->CI->dbwrite_model->updateData('Accounting', 'GeneralJournal',
                ['IsDeleted' => 1], ['JournalUID' => $jUID]);
            $this->CI->dbwrite_model->updateData('Accounting', 'JournalEntries',
                ['IsDeleted' => 1], ['JournalUID' => $jUID, 'IsDeleted' => 0]);

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