<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Salesreturns extends MY_Controller {

    public $pageData = array();
    protected $EndReturnData;
    protected $pageModuleUID;

    public function __construct() {
        parent::__construct();
        $this->pageModuleUID = 106;
        $this->load->helper('transaction');
    }

    public function index(): void {
        if (!$this->_loadPageTitle($this->pageModuleUID)) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }
        try {
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;
            $this->_loadTransactionIndexPage([
                'datePrefKey'  => 'salesreturns',
                'tabSlugMap'   => ['all' => 'All', 'pending' => 'SRPending', 'paid' => 'Paid', 'cancelled' => 'Cancelled', 'drafts' => 'Draft'],
                'listViewPath' => 'transactions/salesreturns/list',
                'paginationUrl'=> '/transactions/getPageDetails/106',
            ]);
            $this->load->view('transactions/salesreturns/view', $this->pageData);
        } catch (ValidationException $e) {
            redirect('dashboard', 'refresh');
        } catch (Exception $e) {
            notifyError('Salesreturns::index', $e);
            redirect('dashboard', 'refresh');
        }
    }

    public function addSalesReturn() {
        $this->EndReturnData = new stdClass();
        $ErrorInForm = '';
        try {
            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData = $this->input->post();
            $userUID  = $this->pageData['JwtData']->User->UserUID;
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;

            $itemsJson     = $this->_validateTransForm($PostData);
            $amounts       = $this->_extractTransAmounts($PostData, $itemsJson);
            $isDraft       = $amounts['isDraft'];
            $items         = $amounts['items'];
            $netAmount     = $amounts['netAmount'];
            $financialYear = $amounts['financialYear'];
            $subTotal      = $amounts['subTotal'];
            $cgstAmount    = $amounts['cgstAmount'];
            $sgstAmount    = $amounts['sgstAmount'];
            $igstAmount    = $amounts['igstAmount'];
            $transDate     = $amounts['transDate'];
            $customerUID      = (int) getPostValue($PostData, 'customerSearch');
            $rawInvoiceUIDs   = $this->input->post('fromInvoiceUIDs');
            $decodedUIDs      = (is_string($rawInvoiceUIDs) && $rawInvoiceUIDs !== '')
                                ? (json_decode($rawInvoiceUIDs, true) ?: [])
                                : [];
            $fromInvoiceUIDs  = array_values(array_filter(array_map('intval', $decodedUIDs)));

            // Log customer balance before SR creation
            $this->load->model('customers_model');
            $_preSR = $this->customers_model->getCustomerOpeningBalance($orgUID, $customerUID);
                . ' SRAmount=' . $netAmount
                . ' PendingBalance=' . ($_preSR ? $_preSR->PendingBalance : 'NULL')
                . ' PendingBalType=' . ($_preSR ? $_preSR->PendingBalType : 'NULL'));

            $resolved = $this->_resolveTransPrefix(
                $isDraft, $amounts['prefixUID'], $amounts['transNumber'], $transDate, $orgUID
            );

            $amounts['moduleUID']    = $this->pageModuleUID;
            $amounts['prefixUID']    = $resolved['prefixUID'];
            $amounts['transNumber']  = $resolved['transNumber'];
            $amounts['uniqueNumber'] = $resolved['uniqueNumber'];

            $headerData = $this->_buildTransHeader(
                [
                    'TransType'        => 'Sales Return',
                    'PartyType'        => 'C',
                    'PartyUID'         => $customerUID,
                    'DocTypePostKey'   => 'returnType',
                    'DispatchPostKey'  => 'dispatchFrom',
                    'InitialStatus'    => 'Approved',
                    'hasPaidAmount'    => false,
                    'hasBalanceAmount' => true,
                ],
                $amounts, $PostData, $orgUID, $userUID
            );

            $insertResp = $this->_insertTransactionWithRetry($headerData, $resolved['prefixUID'], $orgUID, $resolved['prefix'], $transDate);
            if ($insertResp->Error) throw new Exception($insertResp->Message);
            $transUID     = $insertResp->ID;
            $transNumber  = $headerData['TransNumber'];
            $uniqueNumber = $headerData['UniqueNumber'];

            $this->_saveTransCharges($transUID, $orgUID, $userUID, $PostData);

            $detailData = $this->_buildTransDetail(
                [
                    'PartyType'          => 'C',
                    'PartyUID'           => $customerUID,
                    'ValidityDatePostKey' => 'returnDate',
                ],
                $amounts, $PostData, $transUID
            );
            $this->dbwrite_model->insertData('Transaction', 'TransDetailTbl', $detailData);

            $this->_insertTransItems($transUID, $financialYear, $orgUID, $userUID, $items);

            if (!$isDraft) {
                $this->_saveTransSerials($transUID, $orgUID, $userUID, 'SalesReturn', $items, $customerUID);
                $this->dbwrite_model->saveStockMovements($transUID, $this->pageModuleUID, $orgUID, $userUID, $items, $this->_branchUID());
                foreach ($fromInvoiceUIDs as $invUID) {
                    $this->dbwrite_model->insertConversionRecord(
                        $orgUID, $invUID, 103, $transUID, $this->pageModuleUID, 'InvoiceToSalesReturn', $userUID
                    );
                }
            }

            $this->dbwrite_model->commitTransaction();

            if (!$isDraft) {
                $this->_syncProductCacheFromItems($items); // after commit â€” ReadDB now sees updated stock
                try {
                    $this->load->library('accountledger');
                    $this->accountledger->postSaleReturnJournal(
                        $transUID, $transDate, $uniqueNumber, $financialYear,
                        $netAmount, $subTotal, $cgstAmount, $sgstAmount, $igstAmount,
                        $customerUID, $userUID
                    );
                } catch (Exception $ledgerEx) {
                }
            }

            $this->_saveAttachments($transUID);
            if ($isDraft) $this->cachehelper->touchCustomer($customerUID);

            // Ã¢â€â‚¬Ã¢â€â‚¬ Save payment if recorded on create Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
            $hasPayment = false;
            if (!$isDraft && (int) getPostValue($PostData, 'RecordPayment') === 1) {
                $payResult = $this->_savePaymentRecord($transUID, $orgUID, $userUID, 'C', $customerUID, $netAmount, $PostData, 'Out', $transDate);
                if ($payResult['totalPaid'] > 0) {
                    $hasPayment    = true;
                    $isFullyPaid   = ($netAmount > 0 && round($netAmount - $payResult['totalPaid'], 4) <= 0) ? 1 : 0;
                    $balanceAmount = max(0, round($netAmount - $payResult['totalPaid'], $this->_decimals()));
                    $this->dbwrite_model->updateTransIsFullyPaid($transUID, $isFullyPaid, $payResult['totalPaid'], $balanceAmount, $userUID);
                    $newStatus = $isFullyPaid ? 'Paid' : 'Partial';
                    $this->dbwrite_model->updateTransDocStatus($transUID, $orgUID, $newStatus, $userUID);
                }
                if (!empty($payResult['firstPaymentUID'])) {
                    $this->_savePaymentAttachments($payResult['firstPaymentUID']);
                }
            }

            if (!$isDraft) {
                    . ' hasPayment=' . ($hasPayment ? 'true' : 'false')
                    . ' netAmount=' . $netAmount
                    . ' uniqueNumber=' . $uniqueNumber
                    . ' customerUID=' . $customerUID
                    . ' orgUID=' . $orgUID
                    . ' RecordPayment_POST=' . (int)getPostValue($PostData, 'RecordPayment'));

                // Ã¢â€â‚¬Ã¢â€â‚¬ Create credit note for the outstanding balance Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
                // No payment  Ã¢â€ â€™ CN for full NetAmount
                // Partial pay Ã¢â€ â€™ CN for remaining BalanceAmount
                // Full pay    Ã¢â€ â€™ no CN (balanceAmount = 0)
                $cnAmount = $hasPayment ? ($balanceAmount ?? 0) : $netAmount;
                    . ' hasPayment=' . ($hasPayment ? 'true' : 'false')
                    . ' netAmount=' . $netAmount
                    . ' balanceAmount=' . ($balanceAmount ?? 'N/A'));

                if ($cnAmount > 0) {
                    $this->load->library('customerbalance');
                    $cnResult = $this->customerbalance->createSalesReturnCreditNote(
                        $orgUID, $customerUID, $transUID, $uniqueNumber, $cnAmount, $userUID, $transDate
                    );
                    if ($cnResult) {
                        $this->EndReturnData->CreditNoteUID    = $cnResult['creditNoteUID'];
                        $this->EndReturnData->CreditNoteNumber = $cnResult['creditNoteNumber'];
                    } else {
                    }
                } else {
                }

                $balResult = $this->_recalcCustomerBalance($orgUID, $customerUID, $userUID);
                if ($balResult) {
                    $this->EndReturnData->CustomerBalance     = $balResult['balance'];
                    $this->EndReturnData->CustomerBalanceType = $balResult['type'];
                        . ' NewBalance=' . $balResult['balance'] . '(' . $balResult['type'] . ')');
                } else {
                }
            } else {
            }

            $this->EndReturnData->Error    = FALSE;
            $this->EndReturnData->Message  = 'Sales Return created successfully.';
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'ADD_SALES_RETURN', 'SalesReturn', (int) $transUID, (string) ($uniqueNumber ?? ''),
                [], 'Created sales return ' . ($uniqueNumber ?? ''), 'SalesReturns', 'TRANSACTION', 'SUCCESS', '', 'WEB', [], [], $PostData
            );
            $this->EndReturnData->TransUID = $transUID;
            $this->EndReturnData->Token    = $headerData['TransToken'];
        } catch (ValidationException $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Salesreturns::addSalesReturn', $e);
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function updateSalesReturn() {
        $this->EndReturnData = new stdClass();
        try {
            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData = $this->input->post();
            $userUID  = $this->pageData['JwtData']->User->UserUID;
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;
            $transUID = (int) getPostValue($PostData, 'TransUID');
            if ($transUID <= 0) throw new ValidationException('Sales Return ID is required.');

            $itemsJson = $this->_validateTransForm($PostData);
            $amounts   = $this->_extractTransAmounts($PostData, $itemsJson);

            $amounts['moduleUID'] = $this->pageModuleUID;
            $customerUID = (int) getPostValue($PostData, 'customerSearch');
            $prefixUID   = $amounts['prefixUID'];
            $transNumber = $amounts['transNumber'];
            $isDraft     = $amounts['isDraft'];
            $items       = $amounts['items'];
            $returnDate  = getPostValue($PostData, 'returnDate');

            $cfg = [
                'TransType'       => 'Sales Return',
                'PartyType'       => 'C',
                'PartyUID'        => $customerUID,
                'DocTypePostKey'  => 'returnType',
                'DispatchPostKey' => 'dispatchFrom',
                'InitialStatus'   => 'Approved',
            ];

            $this->load->model('transactions_model');
            $existing = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$existing) throw new ValidationException('Sales Return not found.');

            $uniqueNumber = NULL;
            if ($existing->DocStatus === 'Draft' && !$isDraft) {
                if ($prefixUID <= 0) throw new ValidationException('Please select a prefix to finalise this return.');
                if ($transNumber <= 0) throw new ValidationException('Transaction number must be greater than 0.');
                $prefixData = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.PrefixUID' => $prefixUID, 'Prefix.OrgUID' => $orgUID]);
                if (empty($prefixData->Data)) throw new ValidationException('Invalid prefix selected.');
                $prefix   = $prefixData->Data[0];
                $dupCheck = $this->transactions_model->getTransactionByPrefixAndNumber($prefixUID, $transNumber, $orgUID, $this->pageModuleUID);
                if ($dupCheck) {
                    $nextSuggested = $this->transactions_model->getNextTransactionNumber($prefixUID, $orgUID, $this->pageModuleUID);
                    throw new ValidationException("Transaction number {$transNumber} already exists. Next available: {$nextSuggested}.");
                }
                [$uniqueNumber] = $this->buildUniqueNumber($prefix, $transNumber, $amounts['transDate']);
            }

            $rawIS             = getPostValue($PostData, 'isInterState');
            $isInterState      = ($rawIS !== null && $rawIS !== '') ? (int)$rawIS : null;
            $_cc               = $this->transactions_model->getCustomerCountryCode($customerUID);
            $isForeignCustomer = $_cc !== NULL ? ($_cc === 'IN' ? 0 : 1) : NULL;
            $commonDetail = [
                'ValidityDays'      => NULL,
                'ValidityDate'      => $returnDate ?: NULL,
                'Reference'         => getPostValue($PostData, 'referenceDetails') ?: NULL,
                'Notes'             => getPostValue($PostData, 'transNotes') ?: NULL,
                'TermsConditions'   => getPostValue($PostData, 'transTermsCond') ?: NULL,
                'SignatureUID'      => (int)getPostValue($PostData, 'SignatureUID') ?: NULL,
                'PlaceOfSupplyCode' => getPostValue($PostData, 'placeOfSupplyCode') ?: NULL,
                'PlaceOfSupplyName' => getPostValue($PostData, 'placeOfSupplyName') ?: NULL,
                'IsInterState'      => $isInterState,
                'IsForeignCustomer' => $isForeignCustomer,
                'PriceListUID'      => (int)getPostValue($PostData, 'PriceListUID') ?: NULL,
                'PriceListData'     => getPostValue($PostData, 'PriceListData') ?: NULL,
            ];

            $wasNonDraft = ($existing->DocStatus !== 'Draft');
            if ($wasNonDraft) {
                $this->dbwrite_model->reverseStockMovements($transUID, $orgUID, $userUID);
            }

            if ($existing->DocStatus === 'Draft' && !$isDraft
                && $this->transactions_model->hasNewerTransactions($transUID, $orgUID, $this->pageModuleUID)) {

                $amounts['prefixUID']    = $prefixUID;
                $amounts['transNumber']  = $transNumber;
                $amounts['uniqueNumber'] = $uniqueNumber;

                $insertResp = $this->dbwrite_model->insertData(
                    'Transaction', 'TransactionsTbl',
                    $this->_buildTransHeader($cfg, $amounts, $PostData, $orgUID, $userUID)
                );
                if ($insertResp->Error) throw new Exception($insertResp->Message);
                $newTransUID = $insertResp->ID;
                $detailResp = $this->dbwrite_model->updateData(
                    'Transaction', 'TransDetailTbl',
                    array_merge($commonDetail, ['TransUID' => $newTransUID]),
                    ['TransUID' => $transUID, 'FinancialYear' => $amounts['financialYear']]
                );
                if ($detailResp->Error) throw new Exception($detailResp->Message);
                $this->dbwrite_model->updateData('Transaction', 'TransProductsTbl', ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID], ['TransUID' => $transUID, 'IsDeleted' => 0]);
                $this->_insertTransItems($newTransUID, $amounts['financialYear'], $orgUID, $userUID, $items);
                if (!$isDraft) {
                    $this->_saveTransSerials($newTransUID, $orgUID, $userUID, 'SalesReturn', $items, $customerUID);
                    $this->dbwrite_model->saveStockMovements($newTransUID, $this->pageModuleUID, $orgUID, $userUID, $items, $this->_branchUID());
                }
                $this->dbwrite_model->deleteInTransaction('Transaction', 'TransactionsTbl', ['TransUID' => $transUID]);
            } else {
                $updateHeader = $this->_buildTransUpdateHeader($cfg, $amounts, $PostData, $orgUID, $userUID);
                if ($uniqueNumber !== NULL) {
                    $updateHeader['PrefixUID']    = $prefixUID;
                    $updateHeader['TransNumber']  = $transNumber;
                    $updateHeader['UniqueNumber'] = $uniqueNumber;
                }
                $updateResp = $this->dbwrite_model->updateData(
                    'Transaction', 'TransactionsTbl',
                    $updateHeader,
                    ['TransUID' => $transUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
                );
                if ($updateResp->Error) throw new Exception($updateResp->Message);
                $this->dbwrite_model->updateData('Transaction', 'TransDetailTbl', $commonDetail, ['FinancialYear' => $amounts['financialYear'], 'TransUID' => $transUID]);
                $this->_updateTransItems($transUID, $items, $orgUID, $amounts['financialYear'], $userUID);
                if (!$isDraft) {
                    $this->_updateTransSerials($transUID, $orgUID, $userUID, 'SalesReturn', $items, $customerUID);
                    $this->dbwrite_model->saveStockMovements($transUID, $this->pageModuleUID, $orgUID, $userUID, $items, $this->_branchUID());
                }
            }

            $activeTransUID = $newTransUID ?? $transUID;
            $this->_saveTransCharges($activeTransUID, $orgUID, $userUID, $PostData);

            // Sync TransConversionTbl: soft-delete records for invoices that no longer
            // have any active items linked to this SR (items removed during edit).
            $wdb = $this->dbwrite_model->getWriteDb();
            $wdb->db_debug = FALSE;
            $activeInvoiceRows = $wdb->query(
                'SELECT DISTINCT src.TransUID
                 FROM Transaction.TransProductsTbl sr
                 INNER JOIN Transaction.TransProductsTbl src ON src.TransProdUID = sr.SourceTransProdUID
                 WHERE sr.TransUID = ? AND sr.IsDeleted = 0 AND sr.IsActive = 1
                   AND src.IsDeleted = 0',
                [$activeTransUID]
            )->result_array();
            $activeInvoiceUIDs = array_column($activeInvoiceRows, 'TransUID');
            $wdb->where(['TargetTransUID' => $activeTransUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]);
            if (!empty($activeInvoiceUIDs)) {
                $wdb->where_not_in('SourceTransUID', $activeInvoiceUIDs);
            }
            $wdb->update('Transaction.TransConversionTbl', [
                'IsDeleted' => 1,
                'UpdatedBy' => $userUID,
                'UpdatedOn' => date('Y-m-d H:i:s'),
            ]);

            // Insert or restore conversion records for invoices newly linked in this edit.
            foreach ($activeInvoiceUIDs as $invUID) {
                $invUID   = (int) $invUID;
                $existing = $wdb->query(
                    'SELECT ConversionUID, IsDeleted FROM Transaction.TransConversionTbl
                     WHERE SourceTransUID = ? AND TargetTransUID = ? LIMIT 1',
                    [$invUID, $activeTransUID]
                )->row();

                if ($existing) {
                    if ((int) $existing->IsDeleted === 1) {
                        // Previously removed â€” restore it
                        $wdb->where('ConversionUID', $existing->ConversionUID)
                            ->update('Transaction.TransConversionTbl', [
                                'IsDeleted'   => 0,
                                'IsCancelled' => 0,
                                'UpdatedBy'   => $userUID,
                                'UpdatedOn'   => date('Y-m-d H:i:s'),
                            ]);
                    }
                    // else: record is already active â€” nothing to do
                } else {
                    // Brand new invoice added in this edit â€” insert fresh record
                    $this->dbwrite_model->insertConversionRecord(
                        $orgUID, $invUID, 103, $activeTransUID, $this->pageModuleUID, 'InvoiceToSalesReturn', $userUID
                    );
                }
            }

            $this->dbwrite_model->commitTransaction();
            if (!$isDraft) { $this->_syncProductCacheByTransUID($activeTransUID); } // after commit â€” ReadDB now sees updated stock
            $this->_saveAttachments($activeTransUID);
            $this->_softDeleteAttachments($this->input->post('RemovedAttachIDs') ?? '');
            $this->cachehelper->touchCustomer($customerUID);
            $this->transactions_model->generateAndStorePdf($activeTransUID, $orgUID, $this->pageModuleUID);
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Sales Return updated successfully.';
            $this->EndReturnData->Token   = $this->_getOrCreateTransToken($activeTransUID);
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'UPDATE_SALES_RETURN', 'SalesReturn', (int) $activeTransUID, (string) ($uniqueNumber ?? $existing->UniqueNumber ?? ''),
                [], 'Updated sales return ' . ($uniqueNumber ?? $existing->UniqueNumber ?? ''), 'SalesReturns', 'TRANSACTION', 'SUCCESS', '', 'WEB', [], [], $PostData
            );
        } catch (ValidationException $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Salesreturns::updateSalesReturn', $e);
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function deleteSalesReturn() {
        $this->EndReturnData = new stdClass();
        try {
            $this->load->model('dbwrite_model');
            $PostData = $this->input->post();
            $userUID  = $this->pageData['JwtData']->User->UserUID;
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;
            $transUID = (int) getPostValue($PostData, 'TransUID');
            if ($transUID <= 0) throw new ValidationException('Sales Return ID is required.');
            $this->load->model('transactions_model');
            $existing = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$existing) throw new ValidationException('Sales Return not found.');

            if (!in_array($existing->DocStatus, ['Draft', 'Approved', 'Partial', 'Paid'])) {
                throw new ValidationException('Only Draft, Approved, Partial, or fully paid sales returns can be deleted.');
            }

            // Check 1: block if credit was applied via applyCredit() Ã¢â‚¬â€ PaymentsTbl path
            $creditApplied = $this->_getSRCreditApplied($existing->UniqueNumber, $orgUID);
            if ($creditApplied > 0) {
                throw new ValidationException(
                    'This Sales Return has already been applied to one or more invoices. ' .
                    'Please reverse the credit allocations before deleting.'
                );
            }

            // Check 2: block if CN was applied via applyCreditNote() Ã¢â‚¬â€ CN Status path
            $readDb = $this->load->database('ReadDB', TRUE);
            $readDb->db_debug = FALSE;
            $readDb->from('Transaction.TransCreditNoteTbl');
            $readDb->where([
                'SourceTransUID'  => $transUID,
                'SourceModuleUID' => 106,
                'IsDeleted'       => 0,
                'IsCancelled'     => 0,
                'Status'          => 'Applied',
            ]);
            if ($readDb->get()->num_rows() > 0) {
                throw new ValidationException(
                    'This Sales Return\'s credit note has been applied to an invoice. ' .
                    'Please reverse the credit allocation before deleting.'
                );
            }

            $this->dbwrite_model->startTransaction();

            $this->dbwrite_model->reverseStockMovements($transUID, $orgUID, $userUID);

            $this->dbwrite_model->updateData(
                'Transaction', 'PaymentsTbl',
                ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID],
                ['TransUID' => $transUID, 'IsDeleted' => 0]
            );

            $this->_reverseCreditPayments($existing, $orgUID, $userUID);

            // Soft-delete any pending credit note that was auto-created for this SR
            $wdb = $this->dbwrite_model->getWriteDb();
            $wdb->db_debug = FALSE;
            $wdb->where([
                'SourceTransUID'  => $transUID,
                'SourceModuleUID' => 106,
                'Status'          => 'Pending',
                'IsCancelled'     => 0,
                'IsDeleted'       => 0,
            ])->update('Transaction.TransCreditNoteTbl', [
                'IsDeleted' => 1,
                'UpdatedBy' => $userUID,
            ]);

            $this->dbwrite_model->updateData('Transaction', 'TransProductsTbl', ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID], ['TransUID' => $transUID, 'IsDeleted' => 0]);
            $deleteData = $this->globalservice->baseDeleteArrayDetails();
            $deleteData['IsActive'] = 0;
            $deleteResp = $this->dbwrite_model->updateData('Transaction', 'TransactionsTbl', $deleteData, ['TransUID' => $transUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]);
            if ($deleteResp->Error) throw new Exception($deleteResp->Message);
            $this->dbwrite_model->markConversionDeleted($transUID, $orgUID, $userUID);
            $this->dbwrite_model->commitTransaction();
            $this->_syncProductCacheByTransUID($transUID); // after commit â€” ReadDB now sees reverted stock

            $this->_recalcCustomerBalance($orgUID, (int)$existing->PartyUID, $userUID);

            // Reverse journal entry for the sales return (non-fatal)
            try {
                $this->load->library('accountledger');
                $this->accountledger->reverseJournal('SalesReturn', $transUID, $userUID);
            } catch (Exception $ledgerEx) {
            }

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Sales Return deleted successfully.';
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'DELETE_SALES_RETURN', 'SalesReturn', (int) $transUID, (string) ($existing->UniqueNumber ?? ''),
                [], 'Deleted sales return ' . ($existing->UniqueNumber ?? ''), 'SalesReturns', 'TRANSACTION'
            );

            $this->_buildListResponse('transactions/salesreturns/list', '/transactions/getPageDetails/106');
        } catch (ValidationException $e) {
            if (isset($this->dbwrite_model)) $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Salesreturns::deleteSalesReturn', $e);
            if (isset($this->dbwrite_model)) $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function duplicateSalesReturn() {
        $this->EndReturnData = new stdClass();
        try {
            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();
            $PostData = $this->input->post();
            $srcUID   = (int) getPostValue($PostData, 'TransUID');
            $userUID  = $this->pageData['JwtData']->User->UserUID;
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;
            if ($srcUID <= 0) throw new ValidationException('Invalid sales return.');
            $this->load->model('transactions_model');
            $src = $this->transactions_model->getTransactionById($srcUID, $orgUID, $this->pageModuleUID);
            if (!$src) throw new ValidationException('Sales Return not found.');

            $nextNumber   = $this->transactions_model->getNextTransactionNumber($src->PrefixUID, $orgUID, $this->pageModuleUID);
            $prefixResult = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.PrefixUID' => $src->PrefixUID, 'Prefix.OrgUID' => $orgUID]);
            $prefix       = $prefixResult->Data[0] ?? null;
            if (!$prefix) throw new ValidationException('Prefix not found.');

            $sep   = $prefix->Separator ?? '-';
            $parts = [strtoupper($prefix->Name)];
            if (!empty($prefix->IncludeShortName) && !empty($prefix->ShortName)) $parts[] = strtoupper($prefix->ShortName);
            if (!empty($prefix->IncludeFiscalYear)) {
                $m = (int) date('m'); $yr = (int) date('Y'); $fy = $m >= 4 ? $yr : $yr - 1;
                $parts[] = ($prefix->FiscalYearFormat ?? 'SHORT') === 'LONG'
                    ? $fy . '-' . ($fy + 1)
                    : str_pad($fy % 100, 2, '0', STR_PAD_LEFT) . '-' . str_pad(($fy + 1) % 100, 2, '0', STR_PAD_LEFT);
            }
            $pad          = (int)($prefix->NumberPadding ?? 1);
            $parts[]      = $pad > 1 ? str_pad($nextNumber, $pad, '0', STR_PAD_LEFT) : (string) $nextNumber;
            $uniqueNumber = implode($sep, $parts);
            $today        = date('Y-m-d');

            $headerData = [
                'OrgUID'            => $orgUID,
                'ModuleUID'         => $this->pageModuleUID,
                'PrefixUID'         => $src->PrefixUID,
                'UniqueNumber'      => $uniqueNumber,
                'TransType'         => 'Sales Return',
                'TransNumber'       => $nextNumber,
                'PartyType'         => $src->PartyType,
                'PartyUID'          => $src->PartyUID,
                'TransDate'         => $today,
                'TransYear'         => (int) date('Y'),
                'DocType'     => $src->DocType,
                'DispatchFrom'      => $src->DispatchFrom ?? NULL,
                'TotalQuantity'     => (float)($src->TotalQuantity ?? 0),
                'TotalItems'        => (int)($src->TotalItems ?? 0),
                'GrossAmount'       => $src->GrossAmount,
                'SubTotal'          => $src->SubTotal,
                'DiscountAmount'    => $src->DiscountAmount,
                'AdditionalCharges' => $src->AdditionalCharges,
                'TaxAmount'         => $src->TaxAmount,
                'CgstAmount'        => $src->CgstAmount,
                'SgstAmount'        => $src->SgstAmount,
                'IgstAmount'        => $src->IgstAmount,
                'RoundOff'          => $src->RoundOff,
                'GlobalDiscPercent' => (float) $src->GlobalDiscPercent,
                'ExtraDiscApplied'  => $src->ExtraDiscApplied,
                'ExtraDiscAmount'   => $src->ExtraDiscAmount,
                'ExtraDiscType'     => $src->ExtraDiscType,
                'NetAmount'         => $src->NetAmount,
                'DocStatus'         => 'Draft',
                'IsActive'          => 1,
                'IsDeleted'         => 0,
                'CreatedBy'         => $userUID,
                'UpdatedBy'         => $userUID,
            ];
            $insertResp = $this->dbwrite_model->insertData('Transaction', 'TransactionsTbl', $headerData);
            if ($insertResp->Error) throw new Exception($insertResp->Message);
            $newTransUID = $insertResp->ID;

            $_srcCC     = $src->PartyCountryCode ?? NULL;
            $detailData = [
                'FinancialYear'     => (int) date('Y'),
                'TransUID'          => $newTransUID,
                'ValidityDays'      => NULL,
                'ValidityDate'      => NULL,
                'Reference'         => $src->Reference       ?? NULL,
                'Notes'             => $src->Notes           ?? NULL,
                'TermsConditions'   => $src->TermsConditions ?? NULL,
                'SignatureUID'      => $src->SignatureUID     ?? NULL,
                'AdditionalCharges' => $src->AdditionalChargesJson ?? NULL,
                'IsInterState'      => ($src->IgstAmount ?? 0) > 0 ? 1 : (($src->CgstAmount ?? 0) > 0 || ($src->SgstAmount ?? 0) > 0 ? 0 : NULL),
                'IsForeignCustomer' => $_srcCC !== NULL ? ($_srcCC === 'IN' ? 0 : 1) : NULL,
            ];
            $this->dbwrite_model->insertData('Transaction', 'TransDetailTbl', $detailData);

            $srcItems = $this->transactions_model->getTransactionItems($srcUID, $orgUID);
            $now      = time();
            foreach ($srcItems as $seq => $item) {
                $itemRow = [
                    'OrgUID'            => $orgUID,
                    'FinancialYear'     => (int) date('Y'),
                    'TransUID'          => $newTransUID,
                    'ItemSequence'      => $seq + 1,
                    'ProductUID'        => $item->ProductUID,
                    'ProductName'       => $item->ProductName,
                    'PartNumber'        => $item->PartNumber,
                    'CategoryUID'       => $item->CategoryUID,
                    'StorageUID'        => $item->StorageUID,
                    'Quantity'          => $item->Quantity,
                    'PrimaryUnitName'   => $item->PrimaryUnitName,
                    'TaxDetailsUID'     => $item->TaxDetailsUID,
                    'TaxPercentage'     => $item->TaxPercentage,
                    'CGST'              => $item->CGST,
                    'SGST'              => $item->SGST,
                    'IGST'              => $item->IGST,
                    'DiscountTypeUID'   => $item->DiscountTypeUID,
                    'Discount'          => $item->Discount,
                    'UnitPrice'         => $item->UnitPrice,
                    'SellingPrice'      => $item->SellingPrice,
                    'TaxableAmount'     => $item->TaxableAmount,
                    'CgstAmount'        => $item->CgstAmount,
                    'SgstAmount'        => $item->SgstAmount,
                    'IgstAmount'        => $item->IgstAmount,
                    'TaxAmount'         => $item->TaxAmount,
                    'DiscountAmount'    => $item->DiscountAmount,
                    'NetAmount'         => $item->NetAmount,
                    'QuantityConverted' => 0,
                    'IsCompliment'      => (int)($item->IsCompliment ?? 0),
                    'IsActive'          => 1,
                    'IsDeleted'         => 0,
                    'CreatedBy'         => $userUID,
                    'UpdatedBy'         => $userUID,
                ];
                $this->dbwrite_model->insertData('Transaction', 'TransProductsTbl', $itemRow);
            }

            $this->dbwrite_model->commitTransaction();
            $this->EndReturnData->Error    = FALSE;
            $this->EndReturnData->Message  = 'Sales Return duplicated as ' . $uniqueNumber . '.';
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'DUPLICATE_SALES_RETURN', 'SalesReturn', (int) $newTransUID, (string) $uniqueNumber,
                [], 'Duplicated sales return as ' . $uniqueNumber, 'SalesReturns', 'TRANSACTION'
            );
            $this->EndReturnData->TransUID = $newTransUID;
            $this->EndReturnData->EditURL  = '/salesreturns/edit/' . $newTransUID;
        } catch (ValidationException $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Salesreturns::duplicateSalesReturn', $e);
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function updateSalesReturnStatus() {
        $this->EndReturnData = new stdClass();
        try {
            $this->load->model('dbwrite_model');
            $PostData  = $this->input->post();
            $transUID  = (int) getPostValue($PostData, 'TransUID');
            $newStatus = trim(getPostValue($PostData, 'Status'));
            $userUID   = $this->pageData['JwtData']->User->UserUID;
            $orgUID    = $this->pageData['JwtData']->Org->OrgUID;
            if ($transUID <= 0) throw new ValidationException('Invalid sales return.');

            $validTransitions = [
                'Draft'     => ['Approved', 'Cancelled'],
                'Pending'   => ['Cancelled'],
                'Approved'  => ['Cancelled'],
                'Partial'   => ['Cancelled'],
                'Issued'    => ['Cancelled'],
                'Paid'      => ['Cancelled'],
                'Cancelled' => [],
                'Rejected'  => [],
            ];

            $this->load->model('transactions_model');
            $this->load->model('customers_model');

            $existing = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$existing) throw new ValidationException('Sales Return not found.');
            $current = $existing->DocStatus;
            if (!in_array($newStatus, $validTransitions[$current] ?? [])) throw new ValidationException("Cannot change status from {$current} to {$newStatus}.");

            $this->dbwrite_model->startTransaction();

            // Ã¢â€â‚¬Ã¢â€â‚¬ Pre-cancel dependency checks (before any DB write) Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
            $hasCashRefunds = false;
            $cancelAction   = '';
            $totalRefunded  = 0;

            if ($newStatus === 'Cancelled') {
                // Priority 1: Block if this SR's credit is still applied to any invoice.
                // User must manually reverse those allocations before cancelling.
                $creditApplied = $this->_getSRCreditApplied($existing->UniqueNumber, $orgUID);
                if ($creditApplied > 0) {
                    throw new ValidationException(
                        'This Sales Return has already been applied to one or more invoices. ' .
                        'Please reverse the credit allocations before cancelling.'
                    );
                }

                // Priority 2: Cash/bank refunds require an explicit action (recover or write off).
                $totalRefunded  = $this->_getSRTotalRefunded($transUID, $orgUID);
                $hasCashRefunds = $totalRefunded > 0;
                if ($hasCashRefunds) {
                    $cancelAction = trim($this->input->post('CancelPaymentAction') ?? '');
                    if (!in_array($cancelAction, ['recover', 'writeoff'])) {
                        // No valid action supplied Ã¢â‚¬â€ tell the frontend to show the action dialog.
                        $this->dbwrite_model->rollbackTransaction();
                        $this->EndReturnData->Error          = FALSE;
                        $this->EndReturnData->RequiresAction = TRUE;
                        $this->EndReturnData->RefundAmount   = $totalRefunded;
                        $this->globalservice->sendJsonResponse($this->EndReturnData);
                        return;
                    }
                }
            }

            // Update DocStatus
            $resp = $this->dbwrite_model->updateData('Transaction', 'TransactionsTbl',
                ['DocStatus' => $newStatus, 'UpdatedBy' => $userUID, 'UpdatedOn' => date('Y-m-d H:i:s')],
                ['TransUID' => $transUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );
            if ($resp->Error) throw new Exception($resp->Message);

            if ($newStatus === 'Cancelled') {
                // Soft-delete all line items
                $this->dbwrite_model->updateData(
                    'Transaction', 'TransProductsTbl',
                    ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID, 'UpdatedOn' => date('Y-m-d H:i:s')],
                    ['TransUID' => $transUID, 'IsDeleted' => 0]
                );

                // Handle cash/bank refund payments per the chosen action
                if ($hasCashRefunds) {
                    $wdb = $this->dbwrite_model->getWriteDb();
                    $wdb->db_debug = FALSE;
                    if ($cancelAction === 'writeoff') {
                        // Accept the refund as a business loss Ã¢â‚¬â€ mark payments written off
                        $wdb->where(['TransUID' => $transUID, 'IsDeleted' => 0])
                            ->where('PaymentTypeUID !=', 0)
                            ->update('Transaction.PaymentsTbl', ['IsCancelled' => 1, 'UpdatedBy' => $userUID]);
                    } else {
                        // Recover: void the refund payments; recovery amount added to customer balance below
                        $wdb->where(['TransUID' => $transUID, 'IsDeleted' => 0])
                            ->where('PaymentTypeUID !=', 0)
                            ->update('Transaction.PaymentsTbl', ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID]);
                    }
                }

                // Reverse stock that came in when the SR was approved
                $this->dbwrite_model->reverseStockMovements($transUID, $orgUID, $userUID);

                // Reset SR payment counters
                $this->dbwrite_model->updateTransIsFullyPaid($transUID, 0, 0, 0, $userUID);

                // Recover: create a formal debit note so the customer owes back the refunded amount.
                // Must happen inside the transaction so it is atomic with the cancellation.
                if ($cancelAction === 'recover' && $hasCashRefunds) {
                    $this->load->library('customerbalance');
                    $this->customerbalance->createDebitNote(
                        $orgUID, (int)$existing->PartyUID, $transUID,
                        $existing->UniqueNumber ?? '', $totalRefunded, $userUID,
                        $this->dbwrite_model->getWriteDb()
                    );
                }

                // Cancel any pending credit note that was auto-created when this SR had no payment.
                // Without this, the cancelled SR stays out of totalReturned but its CN stays in
                // pendingCreditNotes, wrongly reducing the customer balance.
                $wdb = $this->dbwrite_model->getWriteDb();
                $wdb->db_debug = FALSE;
                $wdb->where([
                    'SourceTransUID'  => $transUID,
                    'SourceModuleUID' => 106,
                    'Status'          => 'Pending',
                    'IsCancelled'     => 0,
                    'IsDeleted'       => 0,
                ])->update('Transaction.TransCreditNoteTbl', [
                    'IsCancelled' => 1,
                    'UpdatedBy'   => $userUID,
                ]);
            }

            if ($newStatus === 'Cancelled') {
                $this->dbwrite_model->markConversionCancelled($transUID, $orgUID, $userUID);
            }

            // Commit BEFORE recalculating balance so ReadDB sees DocStatus='Cancelled'
            // and getCustomerTotalReturned correctly excludes the cancelled SR.
            $this->dbwrite_model->commitTransaction();
            if ($newStatus === 'Cancelled') { $this->_syncProductCacheByTransUID($transUID); } // after commit â€” ReadDB now sees reverted stock

            if ($newStatus === 'Cancelled') {
                $balResult = $this->_recalcCustomerBalance($orgUID, (int)$existing->PartyUID, $userUID);
                if ($balResult) {
                    $this->EndReturnData->CustomerBalance     = $balResult['balance'];
                    $this->EndReturnData->CustomerBalanceType = $balResult['type'];
                }
            }

            $docNum = $existing->UniqueNumber ?? '';
            $prefix = $docNum ? "{$docNum} " : '';
            if ($newStatus === 'Cancelled') {
                $msg = "Sales return {$prefix}cancelled successfully.";
            } elseif ($newStatus === 'Approved') {
                $msg = "Sales return {$prefix}approved.";
            } else {
                $msg = 'Status updated.';
            }

            $this->EndReturnData->Error     = FALSE;
            $this->EndReturnData->Message   = $msg;
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'UPDATE_SR_STATUS', 'SalesReturn', (int) $transUID, (string) ($existing->UniqueNumber ?? ''),
                ['NewStatus' => $newStatus], 'Updated sales return status to ' . $newStatus, 'SalesReturns', 'TRANSACTION'
            );
            $this->EndReturnData->NewStatus = $newStatus;
        } catch (ValidationException $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Salesreturns::updateSalesReturnStatus', $e);
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function getSRCancelDependencies() {
        $this->EndReturnData = new stdClass();
        try {
            $transUID = (int) $this->input->post('TransUID');
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;
            if ($transUID <= 0) throw new ValidationException('Invalid sales return.');

            $this->load->model('transactions_model');
            $existing = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$existing) throw new ValidationException('Sales Return not found.');

            $creditApplied = $this->_getSRCreditApplied($existing->UniqueNumber, $orgUID);
            $totalRefunded = $this->_getSRTotalRefunded($transUID, $orgUID);

            $this->EndReturnData->Error            = FALSE;
            $this->EndReturnData->HasCreditApplied = $creditApplied > 0;
            $this->EndReturnData->CreditAmount     = $creditApplied;
            $this->EndReturnData->HasRefunds       = $totalRefunded > 0;
            $this->EndReturnData->RefundAmount     = $totalRefunded;
        } catch (ValidationException $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Salesreturns::getSRCancelDependencies', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }


    public function getInvoiceItems() {
        $this->EndReturnData = new stdClass();
        try {
            $transUID     = (int) $this->input->post('TransUID');
            $excludeSrUID = (int) $this->input->post('ExcludeSrUID');
            $orgUID       = $this->pageData['JwtData']->Org->OrgUID;
            if ($transUID <= 0) throw new ValidationException('Invalid invoice.');

            $this->load->model('transactions_model');
            $header = $this->transactions_model->getTransactionById($transUID, $orgUID, 103);
            if (!$header) throw new ValidationException('Invoice not found.');
            $items  = $this->transactions_model->getTransactionItems($transUID, $orgUID);

            // Annotate each item with how much quantity has already been returned.
            // When editing an SR, exclude the current SR's own items from the count
            // so the user can re-add items they just removed from the bill.
            if (!empty($items)) {
                $transProdUIDs = array_map(fn($i) => (int)$i->TransProdUID, $items);
                $returnedMap   = $this->transactions_model->getReturnedQtyMapForItems($transProdUIDs, $orgUID, $excludeSrUID);
                foreach ($items as $item) {
                    $item->ReturnedQty  = $returnedMap[(int)$item->TransProdUID] ?? 0;
                    $item->RemainingQty = max(0, (float)$item->Quantity - $item->ReturnedQty);
                }
                // Filter out fully-returned items
                $items = array_values(array_filter($items, fn($i) => $i->RemainingQty > 0));
            }

            $this->EndReturnData->Error   = false;
            $this->EndReturnData->Header  = $header;
            $this->EndReturnData->Items   = $items;
        } catch (ValidationException $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Salesreturns::getInvoiceItems', $e);
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function getCustomerInvoices() {
        $this->EndReturnData = new stdClass();
        try {
            $customerUID  = (int) $this->input->post('CustomerUID');
            $excludeSrUID = (int) $this->input->post('ExcludeSrUID');
            $orgUID       = $this->pageData['JwtData']->Org->OrgUID;
            if ($customerUID <= 0) throw new ValidationException('Invalid customer.');

            $this->load->model('transactions_model');

            $this->EndReturnData->Error    = false;
            $this->EndReturnData->Invoices = $this->transactions_model->getCustomerInvoicesWithReturnableItems($customerUID, $orgUID, $excludeSrUID);
        } catch (ValidationException $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Salesreturns::getCustomerInvoices', $e);
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function create() {
        try {
            $orgUID = $this->pageData['JwtData']->Org->OrgUID;
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;

            $this->load->model('transactions_model');
            $prefixResult                    = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID, 'Prefix.ModuleUID' => $this->pageModuleUID]);
            $this->pageData['PrefixData']    = $prefixResult->Data ?? [];
            $nextNumberMap = [];
            foreach ($this->pageData['PrefixData'] as $pd) {
                $nextNumberMap[(int)$pd->PrefixUID] = $this->transactions_model->getNextTransactionNumber($pd->PrefixUID, $orgUID, $this->pageModuleUID);
            }
            $this->pageData['NextNumberMap'] = $nextNumberMap;

            $this->_getDispatchAddresses($orgUID);

            $this->pageData['PaymentTypes']    = $this->transactions_model->getPaymentTypesList();
            $this->pageData['BankAccounts']    = $this->transactions_model->getOrgBankAccounts($orgUID);

            $this->pageData['AdditionalCharges']  = $this->_getAdditionalChargesForOrg((int)$orgUID, true);
            $this->pageData['TaxList']            = $this->_getTaxList();
            $this->pageData['TransactionCharges'] = [];
            $this->pageData['IsEditMode']         = false;

            $this->load->view('transactions/salesreturns/forms/form', $this->pageData);
        } catch (ValidationException $e) {
            redirect('salesreturns', 'refresh');
        } catch (Exception $e) {
            notifyError('Salesreturns::create', $e);
            redirect('salesreturns', 'refresh');
        }
    }

    public function edit($transUID = 0) {
        try {
            $transUID = (int) $transUID;
            if ($transUID <= 0) redirect('salesreturns');

            $orgUID = $this->pageData['JwtData']->Org->OrgUID;
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;

            $this->load->model('transactions_model');
            $transData  = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$transData) redirect('salesreturns');
            $transItems = $this->transactions_model->getTransactionItems($transUID, $orgUID);
            $this->pageData['SRData']    = $transData;
            $this->pageData['SRItems']   = $transItems;

            $prefixResult                    = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID, 'Prefix.ModuleUID' => $this->pageModuleUID]);
            $this->pageData['PrefixData']    = $prefixResult->Data ?? [];
            $nextNumberMap = [];
            foreach ($this->pageData['PrefixData'] as $pd) {
                $nextNumberMap[(int)$pd->PrefixUID] = $this->transactions_model->getNextTransactionNumber($pd->PrefixUID, $orgUID, $this->pageModuleUID);
            }
            $this->pageData['NextNumberMap'] = $nextNumberMap;

            $this->_getDispatchAddresses($orgUID);

            $this->pageData['AdditionalCharges']  = $this->_getAdditionalChargesForOrg((int)$orgUID, true);
            $this->pageData['TransactionCharges'] = $this->transactions_model->getTransactionCharges($transUID, (int)$orgUID);
            $this->pageData['TaxList']            = $this->_getTaxList();
            $this->pageData['IsEditMode']         = true;
            $this->pageData['SRSerialsByProd']    = $this->_getTransSerialsGrouped($transUID, $orgUID, 'SalesReturn');

            // Attachments â€” load server-side to avoid AJAX call on page load
            $this->pageData['SRAttachments'] = $this->transactions_model->getTransactionAttachments($transUID, $orgUID);

            $this->load->view('transactions/salesreturns/forms/form', $this->pageData);
        } catch (ValidationException $e) {
            redirect('salesreturns', 'refresh');
        } catch (Exception $e) {
            notifyError('Salesreturns::edit', $e);
            redirect('salesreturns', 'refresh');
        }
    }








    public function getPaymentAttachments() {
        $this->EndReturnData = new stdClass();
        try {
            $transUID = (int) $this->input->post('TransUID');
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;
            if ($transUID <= 0) throw new ValidationException('Invalid transaction.');
            $this->load->model('transactions_model');
            $payments    = $this->transactions_model->getTransactionPayments($transUID, $orgUID);
            $attachments = [];
            foreach ($payments as $payment) {
                $paymentAttachments = $this->transactions_model->getPaymentAttachments($payment->PaymentUID, $orgUID);
                foreach ($paymentAttachments as $attach) {
                    $attach->PaymentTypeName       = $payment->PaymentTypeName;
                    $attach->PaymentAmount         = $payment->Amount;
                    $attach->PaymentUniqueNumber   = $payment->UniqueNumber ?? null;
                    $attachments[] = $attach;
                }
            }
            $this->EndReturnData->Error       = FALSE;
            $this->EndReturnData->Attachments = $attachments;
        } catch (ValidationException $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Salesreturns::getPaymentAttachments', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function recordPayment() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData       = $this->input->post();
            $userUID        = $this->pageData['JwtData']->User->UserUID;
            $orgUID         = $this->pageData['JwtData']->Org->OrgUID;
            $transUID       = (int)   getPostValue($PostData, 'TransUID');
            $paymentTypeUID = (int)   getPostValue($PostData, 'PaymentTypeUID');
            $amount         = (float) getPostValue($PostData, 'Amount', 'Array', 0);
            $paymentDate    =         getPostValue($PostData, 'PaymentDate') ?: date('Y-m-d');
            $bankAccountUID = (int)   getPostValue($PostData, 'BankAccountUID') ?: NULL;
            $referenceNo    =         getPostValue($PostData, 'ReferenceNo') ?: NULL;
            $notes          =         getPostValue($PostData, 'Notes') ?: NULL;

            if ($transUID <= 0)       throw new ValidationException('Invalid transaction.');
            if ($paymentTypeUID <= 0) throw new ValidationException('Please select a payment type.');
            if ($amount <= 0)         throw new ValidationException('Amount must be greater than 0.');

            $this->load->model('transactions_model');
            $existing = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$existing) throw new ValidationException('Sales Return not found.');
            if ($existing->DocStatus === 'Draft')                          throw new ValidationException('Cannot record payment for a Draft.');
            if (in_array($existing->DocStatus, ['Cancelled', 'Rejected'])) throw new ValidationException('Sales Return is cancelled.');

            if (!$this->dbwrite_model->lockTransactionRow($transUID, $orgUID)) {
                throw new ValidationException('Sales Return not found.');
            }
            $alreadyPaid = $this->dbwrite_model->sumTransactionPayments($transUID, $orgUID);
            $pending     = max(0, round((float)$existing->NetAmount - $alreadyPaid, $this->_decimals()));

            if ($amount > $pending + 0.01) {
                throw new ValidationException('Amount exceeds remaining balance (' . $pending . '). A concurrent payment may have just been recorded.');
            }

            $newTotalPaid = $alreadyPaid + $amount;
            $isFullyPaid  = ($existing->NetAmount > 0 && round((float)$existing->NetAmount - $newTotalPaid, 4) <= 0) ? 1 : 0;
            $newStatus    = $isFullyPaid ? 'Paid' : 'Partial';

            $payTransYear  = (int) date('Y', strtotime($paymentDate));
            $payPrefixData = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID, 'Prefix.ModuleUID' => 111]);
            $payPrefix     = !empty($payPrefixData->Data) ? $payPrefixData->Data[0] : null;
            $payPrefixUID  = $payPrefix ? (int) $payPrefix->PrefixUID : null;
            $paymentNumber = $payPrefixUID ? $this->transactions_model->getNextPaymentNumber($payPrefixUID, $orgUID, $payTransYear) : 0;
            $payUniqueNum  = null;
            if ($payPrefix && $paymentNumber > 0) {
                $sep   = $payPrefix->Separator ?? '-';
                $parts = [strtoupper($payPrefix->Name)];
                if (!empty($payPrefix->IncludeShortName) && !empty($payPrefix->ShortName)) $parts[] = strtoupper($payPrefix->ShortName);
                if (!empty($payPrefix->IncludeFiscalYear)) {
                    $m = (int) date('m', strtotime($paymentDate)); $yr = (int) date('Y', strtotime($paymentDate));
                    $fy = $m >= 4 ? $yr : $yr - 1;
                    $parts[] = ($payPrefix->FiscalYearFormat ?? 'SHORT') === 'LONG'
                        ? $fy . '-' . ($fy + 1)
                        : str_pad($fy % 100, 2, '0', STR_PAD_LEFT) . '-' . str_pad(($fy + 1) % 100, 2, '0', STR_PAD_LEFT);
                }
                $pad = (int)($payPrefix->NumberPadding ?? 1);
                $parts[] = $pad > 1 ? str_pad($paymentNumber, $pad, '0', STR_PAD_LEFT) : (string) $paymentNumber;
                $payUniqueNum = implode($sep, $parts);
            }
            $receiptToken = $this->transactions_model->_generateReceiptToken();

            $paymentData = [
                'OrgUID'           => $orgUID,
                'PaymentDate'      => $paymentDate,
                'PaymentModuleUID' => 111,
                'PrefixUID'        => $payPrefixUID,
                'PaymentNumber'    => $paymentNumber,
                'UniqueNumber'     => $payUniqueNum,
                'ReceiptToken'     => $receiptToken,
                'TransYear'        => $payTransYear,
                'TransUID'         => $transUID,
                'ModuleUID'        => $this->pageModuleUID,
                'PartyType'        => 'C',
                'PartyUID'         => $existing->PartyUID,
                'PaymentTypeUID'   => $paymentTypeUID,
                'Amount'           => $amount,
                'BankAccountUID'   => $bankAccountUID,
                'ReferenceNo'      => $referenceNo,
                'Notes'            => $notes,
                'PaymentSource'    => 'Record',
                'PaymentDirection' => 'Out',
                'IsFullyPaid'      => $isFullyPaid,
                'ExcessAmount'     => 0,
                'IsActive'         => 1,
                'IsDeleted'        => 0,
                'CreatedBy'        => $userUID,
                'UpdatedBy'        => $userUID,
            ];

            $resp = $this->dbwrite_model->insertData('Transaction', 'PaymentsTbl', $paymentData);
            if ($resp->Error) throw new Exception($resp->Message);
            $paymentUID = $resp->ID ?? null;

            $balanceAmount = max(0, round((float)$existing->NetAmount - $newTotalPaid, $this->_decimals()));
            $this->dbwrite_model->updateTransIsFullyPaid($transUID, $isFullyPaid, $newTotalPaid, $balanceAmount, $userUID);
            $this->dbwrite_model->updateTransDocStatus($transUID, $orgUID, $newStatus, $userUID);

            // Ã¢â€â‚¬Ã¢â€â‚¬ Reduce linked Credit Note by the payment amount Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
            // Only acts when a Pending CN exists (partial SR scenario).
            // Full-payment SR never has a CN, so this block is a no-op there.
            $wdb = $this->dbwrite_model->getWriteDb();
            $wdb->db_debug = FALSE;
            $wdb->from('Transaction.TransCreditNoteTbl');
            $wdb->where([
                'SourceTransUID'  => $transUID,
                'SourceModuleUID' => 106,
                'Status'          => 'Pending',
                'IsCancelled'     => 0,
                'IsDeleted'       => 0,
            ]);
            $cn = $wdb->get()->row();
            if ($cn) {
                $newCNAmount = round(max(0, (float)$cn->Amount - $amount), $this->_decimals());
                $wdb->where('CreditNoteUID', (int)$cn->CreditNoteUID);
                $wdb->update('Transaction.TransCreditNoteTbl', [
                    'Amount'         => $newCNAmount,
                    'PaymentCleared' => ($newCNAmount <= 0) ? 1 : 0,
                    'UpdatedBy'      => $userUID,
                ]);
            }

            $this->dbwrite_model->commitTransaction();

            if (!empty($paymentUID)) {
                $this->_savePaymentAttachments($paymentUID);
            }

            $balResult = $this->_recalcCustomerBalance($orgUID, (int)$existing->PartyUID, $userUID);
            if ($balResult) {
                $this->EndReturnData->CustomerBalance     = $balResult['balance'];
                $this->EndReturnData->CustomerBalanceType = $balResult['type'];
            }

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Payment of ' . $amount . ' recorded successfully.';
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'RECORD_SR_PAYMENT', 'SalesReturn', (int) $transUID, (string) ($existing->UniqueNumber ?? ''),
                ['Amount' => $amount], 'Recorded payment of ' . $amount . ' for sales return ' . ($existing->UniqueNumber ?? ''), 'SalesReturns', 'PAYMENT'
            );

        } catch (ValidationException $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Salesreturns::recordPayment', $e);
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    protected function _savePaymentRecord(
        int $transUID, int $orgUID, int $userUID,
        string $partyType, int $partyUID, float $billTotal,
        array $PostData, string $paymentDirection,
        ?string $transDate = null
    ): array {
        $rowsJson    = getPostValue($PostData, 'PaymentRows') ?: '';
        $isFullyPaid = (int) getPostValue($PostData, 'IsFullyPaid') === 1 ? 1 : 0;
        if (empty($rowsJson)) return ['totalPaid' => 0, 'firstPaymentUID' => null];
        $rows = json_decode($rowsJson, true);
        if (!is_array($rows) || empty($rows)) return ['totalPaid' => 0, 'firstPaymentUID' => null];

        $defaultPaymentDate = $transDate ?: date('Y-m-d');
        $totalPaid          = array_sum(array_column($rows, 'amount'));
        $payPrefixData = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID, 'Prefix.ModuleUID' => 111]);
        $payPrefix     = !empty($payPrefixData->Data) ? $payPrefixData->Data[0] : null;
        $payPrefixUID  = $payPrefix ? (int) $payPrefix->PrefixUID : null;
        $firstPaymentUID = null;

        foreach ($rows as $idx => $row) {
            $paymentTypeUID = (int)   ($row['paymentTypeUID'] ?? 0);
            $amount         = (float) ($row['amount']         ?? 0);
            $bankAccountUID = !empty($row['bankAccountUID']) ? (int) $row['bankAccountUID'] : NULL;
            $referenceNo    = !empty($row['referenceNo'])    ? $row['referenceNo'] : NULL;
            $notes          = !empty($row['notes'])          ? $row['notes']       : NULL;
            $rowDate        = !empty($row['paymentDate']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $row['paymentDate'])
                                ? $row['paymentDate'] : $defaultPaymentDate;
            $rowTransYear   = (int) date('Y', strtotime($rowDate));
            if ($paymentTypeUID <= 0 || $amount <= 0) continue;

            $rowExcess = 0;
            if ($idx === count($rows) - 1 && $billTotal > 0 && $totalPaid > $billTotal) {
                $rowExcess = round($totalPaid - $billTotal, 4);
            }

            $paymentNumber = $payPrefixUID ? $this->transactions_model->getNextPaymentNumber($payPrefixUID, $orgUID, $rowTransYear) : 0;
            $payUniqueNum  = null;
            if ($payPrefix && $paymentNumber > 0) {
                $sep   = $payPrefix->Separator ?? '-';
                $parts = [strtoupper($payPrefix->Name)];
                if (!empty($payPrefix->IncludeShortName) && !empty($payPrefix->ShortName)) $parts[] = strtoupper($payPrefix->ShortName);
                if (!empty($payPrefix->IncludeFiscalYear)) {
                    $m  = (int) date('m', strtotime($rowDate));
                    $yr = (int) date('Y', strtotime($rowDate));
                    $fy = $m >= 4 ? $yr : $yr - 1;
                    $parts[] = ($payPrefix->FiscalYearFormat ?? 'SHORT') === 'LONG'
                        ? $fy . '-' . ($fy + 1)
                        : str_pad($fy % 100, 2, '0', STR_PAD_LEFT) . '-' . str_pad(($fy + 1) % 100, 2, '0', STR_PAD_LEFT);
                }
                $pad = (int)($payPrefix->NumberPadding ?? 1);
                $parts[] = $pad > 1 ? str_pad($paymentNumber, $pad, '0', STR_PAD_LEFT) : (string) $paymentNumber;
                $payUniqueNum = implode($sep, $parts);
            }
            $receiptToken = $this->transactions_model->_generateReceiptToken();

            $paymentData = [
                'OrgUID'           => $orgUID,
                'PaymentDate'      => $rowDate,
                'PaymentModuleUID' => 111,
                'PrefixUID'        => $payPrefixUID,
                'PaymentNumber'    => $paymentNumber,
                'UniqueNumber'     => $payUniqueNum,
                'ReceiptToken'     => $receiptToken,
                'TransYear'        => $rowTransYear,
                'TransUID'         => (int) $transUID,
                'ModuleUID'        => $this->pageModuleUID,
                'PartyType'        => $partyType,
                'PartyUID'         => $partyUID,
                'PaymentTypeUID'   => $paymentTypeUID,
                'Amount'           => $amount,
                'BankAccountUID'   => $bankAccountUID,
                'ReferenceNo'      => $referenceNo,
                'Notes'            => $notes,
                'PaymentSource'    => 'Create',
                'PaymentDirection' => $paymentDirection,
                'IsFullyPaid'      => ($idx === count($rows) - 1) ? $isFullyPaid : 0,
                'ExcessAmount'     => $rowExcess,
                'IsActive'         => 1,
                'IsDeleted'        => 0,
                'CreatedBy'        => $userUID,
                'UpdatedBy'        => $userUID,
            ];

            $resp = $this->dbwrite_model->insertData('Transaction', 'PaymentsTbl', $paymentData);
            if ($resp->Error) throw new Exception('Payment save failed: ' . $resp->Message);
            if ($idx === 0) $firstPaymentUID = $resp->ID ?? null;
        }

        return ['totalPaid' => $totalPaid, 'firstPaymentUID' => $firstPaymentUID];
    }

    public function getPendingInvoices() {
        $this->EndReturnData = new stdClass();
        try {
            $srUID  = (int) $this->input->post('SalesReturnUID');
            $orgUID = $this->pageData['JwtData']->Org->OrgUID;
            if ($srUID <= 0) throw new ValidationException('Invalid sales return.');

            $this->load->model('transactions_model');
            $sr = $this->transactions_model->getTransactionById($srUID, $orgUID, $this->pageModuleUID);
            if (!$sr) throw new ValidationException('Sales Return not found.');

            $customerUID = (int) $sr->PartyUID;

            $this->EndReturnData->Error    = false;
            $this->EndReturnData->Invoices = $this->transactions_model->getPendingInvoicesForCustomer($customerUID, $orgUID);
        } catch (ValidationException $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Salesreturns::getPendingInvoices', $e);
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function applyCredit() {
        $this->EndReturnData = new stdClass();
        try {
            $this->load->model('dbwrite_model');
            $this->load->model('transactions_model');

            $PostData   = $this->input->post();
            $userUID    = $this->pageData['JwtData']->User->UserUID;
            $orgUID     = $this->pageData['JwtData']->Org->OrgUID;
            $srUID      = (int)   getPostValue($PostData, 'SalesReturnUID');
            $invoiceUID = (int)   getPostValue($PostData, 'InvoiceUID');
            $amount     = (float) getPostValue($PostData, 'Amount', 'Array', 0);
            $notes      = getPostValue($PostData, 'Notes') ?: NULL;

            if ($srUID <= 0)      throw new ValidationException('Invalid sales return.');
            if ($invoiceUID <= 0) throw new ValidationException('Please select an invoice.');
            if ($amount <= 0)     throw new ValidationException('Amount must be greater than 0.');

            $sr = $this->transactions_model->getTransactionById($srUID, $orgUID, $this->pageModuleUID);
            if (!$sr) throw new ValidationException('Sales Return not found.');
            if (in_array($sr->DocStatus, ['Draft', 'Cancelled', 'Rejected'])) {
                throw new ValidationException('Cannot apply credit for this Sales Return.');
            }

            $srPaid    = (float)($sr->PaidAmount    ?? 0);
            $srBalance = max(0, round((float)$sr->NetAmount - $srPaid, $this->_decimals()));
            if ($srBalance <= 0) throw new ValidationException('No credit balance available on this Sales Return.');

            $invoice = $this->transactions_model->getTransactionById($invoiceUID, $orgUID, 103);
            if (!$invoice) throw new ValidationException('Invoice not found.');
            if ($invoice->PartyUID != $sr->PartyUID) throw new ValidationException('Invoice does not belong to the same customer.');
            if (in_array($invoice->DocStatus, ['Draft', 'Cancelled', 'Paid'])) {
                throw new ValidationException('This invoice cannot receive a credit adjustment.');
            }

            $invPaid    = (float)($invoice->PaidAmount    ?? 0);
            $invBalance = max(0, round((float)$invoice->NetAmount - $invPaid, $this->_decimals()));
            if ($invBalance <= 0) throw new ValidationException('Invoice has no pending balance.');

            $maxAmount = min($srBalance, $invBalance);
            if ($amount > $maxAmount + 0.01) {
                throw new ValidationException('Amount exceeds available credit (' . smartDecimal($maxAmount) . ').');
            }
            $amount = min($amount, $maxAmount);

            $this->dbwrite_model->startTransaction();

            $payPrefixData = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID, 'Prefix.ModuleUID' => 110]);
            $payPrefix     = !empty($payPrefixData->Data) ? $payPrefixData->Data[0] : null;
            $payPrefixUID  = $payPrefix ? (int) $payPrefix->PrefixUID : null;
            $today         = date('Y-m-d');
            $payTransYear  = (int) date('Y');
            $paymentNumber = $payPrefixUID ? $this->transactions_model->getNextPaymentNumber($payPrefixUID, $orgUID, $payTransYear) : 0;
            $payUniqueNum  = null;
            if ($payPrefix && $paymentNumber > 0) {
                $sep   = $payPrefix->Separator ?? '-';
                $parts = [strtoupper($payPrefix->Name)];
                if (!empty($payPrefix->IncludeShortName) && !empty($payPrefix->ShortName)) $parts[] = strtoupper($payPrefix->ShortName);
                if (!empty($payPrefix->IncludeFiscalYear)) {
                    $m  = (int) date('m'); $yr = (int) date('Y');
                    $fy = $m >= 4 ? $yr : $yr - 1;
                    $parts[] = ($payPrefix->FiscalYearFormat ?? 'SHORT') === 'LONG'
                        ? $fy . '-' . ($fy + 1)
                        : str_pad($fy % 100, 2, '0', STR_PAD_LEFT) . '-' . str_pad(($fy + 1) % 100, 2, '0', STR_PAD_LEFT);
                }
                $pad = (int)($payPrefix->NumberPadding ?? 1);
                $parts[] = $pad > 1 ? str_pad($paymentNumber, $pad, '0', STR_PAD_LEFT) : (string) $paymentNumber;
                $payUniqueNum = implode($sep, $parts);
            }
            $receiptToken = $this->transactions_model->_generateReceiptToken();

            // Record the credit against the invoice (PaymentTypeUID=0 = credit adjustment, no real payment)
            $paymentData = [
                'OrgUID'           => $orgUID,
                'PaymentDate'      => $today,
                'PaymentModuleUID' => 110,
                'PrefixUID'        => $payPrefixUID,
                'PaymentNumber'    => $paymentNumber,
                'UniqueNumber'     => $payUniqueNum,
                'ReceiptToken'     => $receiptToken,
                'TransYear'        => $payTransYear,
                'TransUID'         => $invoiceUID,
                'ModuleUID'        => 103,
                'PartyType'        => 'C',
                'PartyUID'         => $invoice->PartyUID,
                'PaymentTypeUID'   => 0,
                'Amount'           => $amount,
                'BankAccountUID'   => NULL,
                'ReferenceNo'      => $sr->UniqueNumber,
                'Notes'            => $notes,
                'PaymentSource'    => 'Record',
                'PaymentDirection' => 'In',
                'IsFullyPaid'      => 0,
                'ExcessAmount'     => 0,
                'IsActive'         => 1,
                'IsDeleted'        => 0,
                'CreatedBy'        => $userUID,
                'UpdatedBy'        => $userUID,
            ];

            $resp = $this->dbwrite_model->insertData('Transaction', 'PaymentsTbl', $paymentData);
            if ($resp->Error) throw new Exception($resp->Message);

            // Update Invoice
            $newInvPaid    = round($invPaid + $amount, $this->_decimals());
            $newInvBalance = max(0, round((float)$invoice->NetAmount - $newInvPaid, $this->_decimals()));
            $invFullyPaid  = ($invoice->NetAmount > 0 && $newInvBalance <= 0) ? 1 : 0;
            $invStatus     = $invFullyPaid ? 'Paid' : 'Partial';
            $this->dbwrite_model->updateTransIsFullyPaid($invoiceUID, $invFullyPaid, $newInvPaid, $newInvBalance, $userUID);
            $this->dbwrite_model->updateTransDocStatus($invoiceUID, $orgUID, $invStatus, $userUID);

            // Update Sales Return
            $newSrPaid    = round($srPaid + $amount, $this->_decimals());
            $newSrBalance = max(0, round((float)$sr->NetAmount - $newSrPaid, $this->_decimals()));
            $srFullyPaid  = ($sr->NetAmount > 0 && $newSrBalance <= 0) ? 1 : 0;
            $srNewStatus  = $srFullyPaid ? 'Paid' : ($newSrPaid > 0 ? 'Partial' : $sr->DocStatus);
            $this->dbwrite_model->updateTransIsFullyPaid($srUID, $srFullyPaid, $newSrPaid, $newSrBalance, $userUID);
            if ($srNewStatus !== $sr->DocStatus) {
                $this->dbwrite_model->updateTransDocStatus($srUID, $orgUID, $srNewStatus, $userUID);
            }

            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Credit of ' . smartDecimal($amount) . ' applied to invoice ' . ($invoice->UniqueNumber ?? '#' . $invoiceUID) . '.';

        } catch (ValidationException $e) {
            if (isset($this->dbwrite_model)) $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Salesreturns::applyCredit', $e);
            if (isset($this->dbwrite_model)) $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    private function _getSRCreditApplied($srUniqueNumber, $orgUID) {
        return $this->transactions_model->getSRCreditApplied($srUniqueNumber);
    }

    private function _getSRTotalRefunded($transUID, $orgUID) {
        return $this->transactions_model->getSRTotalRefunded($transUID);
    }

    private function _reverseCreditPayments($sr, $orgUID, $userUID) {
        $this->load->model('transactions_model');
        $creditPayments = $this->transactions_model->getSRCreditPayments($sr->UniqueNumber);

        if (empty($creditPayments)) return;

        $this->load->model('transactions_model');

        foreach ($creditPayments as $cp) {
            $invoiceUID = (int)$cp->TransUID;
            $creditAmt  = (float)$cp->Amount;

            $this->dbwrite_model->updateData(
                'Transaction', 'PaymentsTbl',
                ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID],
                ['PaymentUID' => (int)$cp->PaymentUID, 'IsDeleted' => 0]
            );

            $invoice = $this->transactions_model->getTransactionById($invoiceUID, $orgUID, 103);
            if (!$invoice) continue;

            $newPaid     = max(0, round((float)($invoice->PaidAmount ?? 0) - $creditAmt, $this->_decimals()));
            $newBalance  = max(0, round((float)$invoice->NetAmount - $newPaid, $this->_decimals()));
            $isFullyPaid = ($invoice->NetAmount > 0 && $newBalance <= 0) ? 1 : 0;
            if ($newBalance <= 0) {
                $newStatus = 'Paid';
            } elseif ($newPaid > 0) {
                $newStatus = 'Partial';
            } else {
                $newStatus = 'Approved';
            }

            $this->dbwrite_model->updateTransIsFullyPaid($invoiceUID, $isFullyPaid, $newPaid, $newBalance, $userUID);
            $this->dbwrite_model->updateTransDocStatus($invoiceUID, $orgUID, $newStatus, $userUID);
        }
    }


}
