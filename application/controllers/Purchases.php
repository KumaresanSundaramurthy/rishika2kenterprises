<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Purchases extends MY_Controller {

    public $pageData = array();
    protected $EndReturnData;
    protected $pageModuleUID;

    public function __construct() {
        parent::__construct();

        $this->pageModuleUID = 105;
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
                'datePrefKey'  => 'purchases',
                'tabSlugMap'   => ['all' => 'All', 'pending' => 'Pending', 'paid' => 'Paid', 'cancelled' => 'Cancelled', 'draft' => 'Draft'],
                'listViewPath' => 'transactions/purchases/list',
                'paginationUrl'=> '/transactions/getPageDetails/105',
            ]);
            $this->load->view('transactions/purchases/view', $this->pageData);
        } catch (ValidationException $e) {
            redirect('dashboard', 'refresh');
        } catch (Exception $e) {
            notifyError('Purchases::index', $e);
            redirect('dashboard', 'refresh');
        }
    }

    public function purchasePayments() {

        try {

            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;

            $GeneralSettings = $this->pageData['JwtData']->GenSettings ?? new stdClass();
            $limit  = $GeneralSettings->RowLimit ?? 10;
            $orgUID = $this->pageData['JwtData']->Org->OrgUID;

            $filter = ['PartyType' => 'V', 'ModuleUID' => $this->pageModuleUID];

            $this->load->model('transactions_model');
            $allData      = $this->transactions_model->getPaymentsList($limit, 0, $orgUID, $filter);
            $allDataCount = $this->transactions_model->getPaymentsCount($orgUID, $filter);

            $this->pageData['ModRowData']    = $this->load->view('transactions/payments/list', [
                'DataLists'    => $allData,
                'SerialNumber' => 0,
                'JwtData'      => $this->pageData['JwtData'],
            ], TRUE);
            $this->pageData['ModPagination'] = $this->globalservice->buildPagePaginationHtml('/purchases/getPurchasePaymentsPageDetails', $allDataCount, 1, $limit);
            $this->pageData['ModAllCount']   = $allDataCount;
            $this->pageData['Totals']        = $this->transactions_model->getPaymentsTotals($orgUID, $filter);

            $this->load->view('transactions/purchases/payments', $this->pageData);

        } catch (ValidationException $e) {
            redirect('purchases', 'refresh');
        } catch (Exception $e) {
            notifyError('Purchases::purchasePayments', $e);
            redirect('purchases', 'refresh');
        }

    }

    public function getPurchasePaymentsPageDetails($pageNo = 0) {

        $this->EndReturnData = new stdClass();
        try {

            $pageNo = (int) $pageNo;
            if ($pageNo < 1) $pageNo = 1;

            $limit  = (int) $this->input->post('RowLimit') ?: 10;
            $offset = ($pageNo - 1) * $limit;
            $filter = $this->input->post('Filter') ?: [];

            // Always scope to vendor payments for this module
            $filter['PartyType'] = 'V';
            $filter['ModuleUID'] = $this->pageModuleUID;

            $orgUID = $this->pageData['JwtData']->Org->OrgUID;

            $this->load->model('transactions_model');
            $allData      = $this->transactions_model->getPaymentsList($limit, $offset, $orgUID, $filter);
            $allDataCount = $this->transactions_model->getPaymentsCount($orgUID, $filter);

            $rowHtml = $this->load->view('transactions/payments/list', [
                'DataLists'    => $allData,
                'SerialNumber' => ($pageNo - 1) * $limit,
                'JwtData'      => $this->pageData['JwtData'],
            ], true);

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->RecordHtmlData = $rowHtml;
            $this->EndReturnData->Pagination     = $this->globalservice->buildPagePaginationHtml('/purchases/getPurchasePaymentsPageDetails', $allDataCount, $pageNo, $limit);
            $this->EndReturnData->TotalCount     = $allDataCount;
            $this->EndReturnData->Totals         = $this->transactions_model->getPaymentsTotals($orgUID, $filter);

        } catch (ValidationException $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Purchases::getPurchasePaymentsPageDetails', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function addPurchase() {

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
            $vendorUID     = (int) getPostValue($PostData, 'vendorSearch');

            $resolved = $this->_resolveTransPrefix(
                $isDraft, $amounts['prefixUID'], $amounts['transNumber'], $transDate, $orgUID
            );

            $amounts['moduleUID']    = $this->pageModuleUID;
            $amounts['prefixUID']    = $resolved['prefixUID'];
            $amounts['transNumber']  = $resolved['transNumber'];
            $amounts['uniqueNumber'] = $resolved['uniqueNumber'];

            $this->load->model('dbwrite_model');
            $headerData = $this->_buildTransHeader(
                [
                    'TransType'       => 'Purchase',
                    'PartyType'       => 'S',
                    'PartyUID'        => $vendorUID,
                    'DocTypePostKey'  => 'purchaseType',
                    'DispatchPostKey' => 'dispatchTo',
                    'InitialStatus'   => 'Received',
                    'hasPaidAmount'   => true,
                    'hasIsFullyPaid'  => true,
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
                    'PartyType'               => 'S',
                    'ValidityDatePostKey'      => 'billDueDate',
                    'SupplierInvoiceNoPostKey' => 'supplierInvoiceNo',
                ],
                $amounts, $PostData, $transUID
            );
            $this->dbwrite_model->insertData('Transaction', 'TransDetailTbl', $detailData);

            $this->_insertTransItems($transUID, $financialYear, $orgUID, $userUID, $items);

            if (!$isDraft) {
                $this->_saveTransSerials($transUID, $orgUID, $userUID, 'Purchase', $items);
                $this->dbwrite_model->saveStockMovements($transUID, $this->pageModuleUID, $orgUID, $userUID, $items, $this->_branchUID());
            }

            // Save payment records and update balance
            $paidAmountForLedger = 0;
            if (!$isDraft && (int) getPostValue($PostData, 'RecordPayment') === 1) {
                $paidAmountForLedger = $this->_savePaymentRecord($transUID, $orgUID, $userUID, 'S', $vendorUID, $netAmount, $PostData, 'Out', $transDate)['totalPaid'];
                if ($paidAmountForLedger > 0) {
                    $this->_updateTransactionBalance($transUID, $netAmount, $paidAmountForLedger, $userUID);
                    $isFullyPaid = $netAmount > 0 && round($netAmount - $paidAmountForLedger, 4) <= 0;
                    $newStatus   = $isFullyPaid ? 'Paid' : 'Partial';
                    $this->dbwrite_model->updateTransDocStatus($transUID, $orgUID, $newStatus, $userUID);
                }
            }

            $this->dbwrite_model->commitTransaction();
            $transCommitted = true;

            // â”€â”€ Post-commit steps (each isolated so one failure cannot crash the response) â”€â”€

            if (!$isDraft) {
                try {
                    $this->load->library('accountledger');
                    $this->accountledger->applyLedgerEntry($vendorUID, 'Vendor', $netAmount, 'Credit', $transUID);
                    if ($paidAmountForLedger > 0) {
                        $this->accountledger->applyLedgerEntry($vendorUID, 'Vendor', $paidAmountForLedger, 'Debit', $transUID);
                    }
                    $this->accountledger->postPurchaseJournal(
                        $transUID, $transDate, $uniqueNumber, $financialYear,
                        $netAmount, $subTotal, $cgstAmount, $sgstAmount, $igstAmount,
                        $vendorUID, $userUID
                    );
                } catch (Exception $ledgerEx) {
                }
            }

            try {
                $this->_saveAttachments($transUID);
            } catch (Exception $attEx) {
            }

            try {
                $this->_touchVendorCache($vendorUID);
            } catch (Exception $vcEx) {
            }

            if (!$isDraft) {
                try {
                    $this->_syncProductCacheFromItems($items);
                } catch (Exception $syncEx) {
                }

                if ((int) getPostValue($PostData, 'UpdatePurchasePrices') === 1) {
                    try {
                        $this->_applyPurchasePriceUpdates($orgUID, $userUID, $PostData);
                    } catch (Exception $priceUpEx) {
                    }
                }

                try {
                    $this->_recalcVendorBalance($orgUID, $vendorUID, $userUID);
                } catch (Exception $balEx) {
                }

                try {
                    $this->load->model('purchasepricelist_model');
                    $this->purchasepricelist_model->upsertFromPurchase(
                        $orgUID, $this->_branchUID(), $vendorUID,
                        $transUID, $transDate, $uniqueNumber, $financialYear,
                        $userUID, $items
                    );
                } catch (Exception $pplEx) {
                }
            }

            $this->EndReturnData->Error    = FALSE;
            $this->EndReturnData->Message  = 'Purchase bill recorded successfully.';
            try {
                $this->auditlog->log(
                    (int) $orgUID, (int) $userUID,
                    'ADD_PURCHASE', 'Purchase', (int) $transUID, (string) $uniqueNumber,
                    [], 'Created purchase ' . $uniqueNumber, 'Purchases', 'TRANSACTION', 'SUCCESS', '', 'WEB', [], [], $PostData
                );
            } catch (Exception $auditEx) {
            }
            $this->EndReturnData->TransUID = $transUID;
            $this->EndReturnData->Token    = $headerData['TransToken'];

        } catch (ValidationException $e) {
            if (empty($transCommitted)) {
                $this->dbwrite_model->rollbackTransaction();
            }
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Purchases::addPurchase', $e);
            if (empty($transCommitted)) {
                $this->dbwrite_model->rollbackTransaction();
            }
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function updatePurchase() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData = $this->input->post();
            $userUID  = $this->pageData['JwtData']->User->UserUID;
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;

            $transUID = (int) getPostValue($PostData, 'TransUID');
            if ($transUID <= 0) throw new ValidationException('Purchase bill ID is required.');

            $itemsJson = $this->_validateTransForm($PostData);
            $amounts   = $this->_extractTransAmounts($PostData, $itemsJson);

            $amounts['moduleUID'] = $this->pageModuleUID;
            $vendorUID   = (int) getPostValue($PostData, 'vendorSearch');
            $prefixUID   = $amounts['prefixUID'];
            $transNumber = $amounts['transNumber'];
            $isDraft     = $amounts['isDraft'];
            $items       = $amounts['items'];
            $billDueDate = getPostValue($PostData, 'billDueDate');
            $netAmount   = $amounts['netAmount'];

            $cfg = [
                'TransType'       => 'Purchase',
                'PartyType'       => 'S',
                'PartyUID'        => $vendorUID,
                'DocTypePostKey'  => 'purchaseType',
                'DispatchPostKey' => 'dispatchTo',
                'InitialStatus'   => 'Received',
            ];

            $this->load->model('transactions_model');
            $existing = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$existing) throw new ValidationException('Purchase bill not found.');

            $uniqueNumber = NULL;
            if ($existing->DocStatus === 'Draft' && !$isDraft) {
                if ($prefixUID <= 0) throw new ValidationException('Please select a prefix to finalise this purchase bill.');
                if ($transNumber <= 0) throw new ValidationException('Transaction number must be greater than 0.');

                $prefixData = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.PrefixUID' => $prefixUID, 'Prefix.OrgUID' => $orgUID]);
                if (empty($prefixData->Data)) throw new ValidationException('Invalid prefix selected.');
                $prefix = $prefixData->Data[0];

                $dupCheck = $this->transactions_model->getTransactionByPrefixAndNumber($prefixUID, $transNumber, $orgUID, $this->pageModuleUID);
                if ($dupCheck) {
                    $nextSuggested = $this->transactions_model->getNextTransactionNumber($prefixUID, $orgUID, $this->pageModuleUID);
                    throw new ValidationException('Transaction number ' . $transNumber . ' already exists. Next available: ' . $nextSuggested . '.');
                }

                [$uniqueNumber] = $this->buildUniqueNumber($prefix, $transNumber, $amounts['transDate']);
            }

            $newBalance   = max(0, round($netAmount - (float)($existing->PaidAmount ?? 0), $this->_decimals()));
            $updateHeader = $this->_buildTransUpdateHeader($cfg, $amounts, $PostData, $orgUID, $userUID);
            $updateHeader['BalanceAmount'] = $newBalance;

            $rawIS        = getPostValue($PostData, 'isInterState');
            $isInterState = ($rawIS !== null && $rawIS !== '') ? (int)$rawIS : null;

            $commonDetail = [
                'ValidityDays'      => NULL,
                'ValidityDate'      => $billDueDate ?: NULL,
                'SupplierInvoiceNo' => getPostValue($PostData, 'supplierInvoiceNo') ?: NULL,
                'Reference'         => getPostValue($PostData, 'referenceDetails') ?: NULL,
                'Notes'             => getPostValue($PostData, 'transNotes') ?: NULL,
                'TermsConditions'   => getPostValue($PostData, 'transTermsCond') ?: NULL,
                'SignatureUID'      => (int)getPostValue($PostData, 'SignatureUID') ?: NULL,
                'PlaceOfSupplyCode' => getPostValue($PostData, 'placeOfSupplyCode') ?: NULL,
                'PlaceOfSupplyName' => getPostValue($PostData, 'placeOfSupplyName') ?: NULL,
                'IsInterState'      => $isInterState,
                'IsForeignCustomer' => NULL,
            ];

            $wasNonDraft    = ($existing->DocStatus !== 'Draft');
            $activeTransUID = $transUID;
            $_cacheUIDs     = [];
            if ($wasNonDraft) {
                $oldItems = $this->transactions_model->getTransactionItems($transUID, $orgUID);
                foreach ($oldItems as $_oi) { $u = (int)$_oi->ProductUID; if ($u > 0) $_cacheUIDs[$u] = true; }
                $this->dbwrite_model->reverseStockMovements($transUID, $orgUID, $userUID);
            }

            if ($existing->DocStatus === 'Draft' && !$isDraft
                && $this->transactions_model->hasNewerTransactions($transUID, $orgUID, $this->pageModuleUID)) {

                $insertHeader = $this->_buildTransHeader($cfg, $amounts, $PostData, $orgUID, $userUID);
                $insertHeader['BalanceAmount'] = $newBalance;
                $insertResp     = $this->dbwrite_model->insertData('Transaction', 'TransactionsTbl', $insertHeader);
                if ($insertResp->Error) throw new Exception($insertResp->Message);
                $newTransUID    = $insertResp->ID;
                $activeTransUID = $newTransUID;

                $detailResp = $this->dbwrite_model->updateData(
                    'Transaction', 'TransDetailTbl',
                    array_merge($commonDetail, ['TransUID' => $newTransUID]),
                    ['TransUID' => $transUID, 'FinancialYear' => $amounts['financialYear']]
                );
                if ($detailResp->Error) throw new Exception($detailResp->Message);

                $this->dbwrite_model->updateData(
                    'Transaction', 'TransProductsTbl',
                    ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID],
                    ['TransUID' => $transUID, 'IsDeleted' => 0]
                );
                $this->_insertTransItems($newTransUID, $amounts['financialYear'], $orgUID, $userUID, $items);

                if (!$isDraft) {
                    $this->dbwrite_model->saveStockMovements($newTransUID, $this->pageModuleUID, $orgUID, $userUID, $items, $this->_branchUID());
                }

                $this->dbwrite_model->deleteInTransaction('Transaction', 'TransactionsTbl', ['TransUID' => $transUID]);

            } else {
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

                $this->dbwrite_model->updateData(
                    'Transaction', 'TransDetailTbl', $commonDetail,
                    ['FinancialYear' => $amounts['financialYear'], 'TransUID' => $transUID]
                );

                $this->_updateTransItems($transUID, $items, $orgUID, $amounts['financialYear'], $userUID);

                if (!$isDraft) {
                    $this->_updateTransSerials($transUID, $orgUID, $userUID, 'Purchase', $items);
                    $this->dbwrite_model->saveStockMovements($transUID, $this->pageModuleUID, $orgUID, $userUID, $items, $this->_branchUID());
                }
            }

            // Save optional payment records
            if (!$isDraft && (int) getPostValue($PostData, 'RecordPayment') === 1) {
                $paidAmountForUpdate = $this->_savePaymentRecord($activeTransUID, $orgUID, $userUID, 'S', $vendorUID, $netAmount, $PostData, 'Out', $amounts['transDate'])['totalPaid'];
                if ($paidAmountForUpdate > 0) {
                    $this->_updateTransactionBalance($activeTransUID, $netAmount, $paidAmountForUpdate, $userUID);
                    $isFullyPaid = $netAmount > 0 && round($netAmount - $paidAmountForUpdate, 4) <= 0;
                    $newStatus   = $isFullyPaid ? 'Paid' : 'Partial';
                    $this->dbwrite_model->updateTransDocStatus($activeTransUID, $orgUID, $newStatus, $userUID);
                }
            }

            // Apply vendor ledger + post journal after commit
            if (!$isDraft) {
                try {
                    $this->load->library('accountledger');
                    if ($wasNonDraft) {
                        $this->accountledger->applyLedgerEntry($existing->PartyUID, 'Vendor', (float) $existing->NetAmount, 'Debit', $transUID);
                        $this->accountledger->reverseJournal('Purchase', $transUID, $userUID);
                    }
                    $this->accountledger->applyLedgerEntry($vendorUID, 'Vendor', $netAmount, 'Credit', $activeTransUID);
                    $activeUniqueNumber = $uniqueNumber ?? ($existing->UniqueNumber ?? null);
                    $this->accountledger->postPurchaseJournal(
                        $activeTransUID, $amounts['transDate'], $activeUniqueNumber, $amounts['financialYear'],
                        $netAmount, $amounts['subTotal'], $amounts['cgstAmount'], $amounts['sgstAmount'], $amounts['igstAmount'],
                        $vendorUID, $userUID
                    );
                } catch (Exception $ledgerEx) {
                }
            }

            $this->_saveAttachments($activeTransUID);
            $this->_softDeleteAttachments($this->input->post('RemovedAttachIDs') ?? '');

            $this->_saveTransCharges($activeTransUID, $orgUID, $userUID, $PostData);
            $this->dbwrite_model->commitTransaction();
            $transCommitted = true;

            // â”€â”€ Post-commit steps (each isolated so one failure cannot crash the response) â”€â”€

            try {
                $this->_touchVendorCache($vendorUID);
            } catch (Exception $vcEx) {
            }

            if (!$isDraft) {
                try {
                    foreach ($items as $_item) { $u = (int)($_item['id'] ?? 0); if ($u > 0) $_cacheUIDs[$u] = true; }
                    foreach (array_keys($_cacheUIDs) as $_uid) { $this->cachehelper->upsertProduct($_uid); }
                } catch (Exception $cacheEx) {
                }

                if ((int) getPostValue($PostData, 'UpdatePurchasePrices') === 1) {
                    try {
                        $this->_applyPurchasePriceUpdates($orgUID, $userUID, $PostData);
                    } catch (Exception $priceUpEx) {
                    }
                }

                try {
                    $this->_recalcVendorBalance($orgUID, $vendorUID, $userUID);
                } catch (Exception $balEx) {
                }

                try {
                    $this->load->model('purchasepricelist_model');
                    $activeUniqueNum = $uniqueNumber ?? ($existing->UniqueNumber ?? '');
                    $this->purchasepricelist_model->upsertFromPurchase(
                        $orgUID, $this->_branchUID(), $vendorUID,
                        $activeTransUID, $amounts['transDate'], (string)$activeUniqueNum, $amounts['financialYear'],
                        $userUID, $items
                    );
                } catch (Exception $pplEx) {
                }
            }

            try {
                $this->transactions_model->generateAndStorePdf($activeTransUID, $orgUID, $this->pageModuleUID);
            } catch (Exception $pdfEx) {
            }

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Purchase bill updated successfully.';
            $this->EndReturnData->Token   = $this->_getOrCreateTransToken($activeTransUID);
            try {
                $this->auditlog->log(
                    (int) $orgUID, (int) $userUID,
                    'UPDATE_PURCHASE', 'Purchase', (int) $activeTransUID, (string) ($uniqueNumber ?? $existing->UniqueNumber ?? ''),
                    [], 'Updated purchase ' . ($uniqueNumber ?? $existing->UniqueNumber ?? ''), 'Purchases', 'TRANSACTION', 'SUCCESS', '', 'WEB', [], [], $PostData
                );
            } catch (Exception $auditEx) {
            }

        } catch (ValidationException $e) {
            if (empty($transCommitted)) {
                $this->dbwrite_model->rollbackTransaction();
            }
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Purchases::updatePurchase', $e);
            if (empty($transCommitted)) {
                $this->dbwrite_model->rollbackTransaction();
            }
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function deletePurchase() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData = $this->input->post();
            $userUID  = $this->pageData['JwtData']->User->UserUID;
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;

            $transUID = (int) getPostValue($PostData, 'TransUID');
            if ($transUID <= 0) throw new ValidationException('Purchase bill ID is required.');

            // Point 5: JWT setting as default; POST overrides for 'ask' mode
            $transSettings       = $this->pageData['JwtData']->TransSettings ?? null;
            $cancelPaymentAction = $transSettings->PurchaseCancelAction ?? 'ask';
            $postAction          = trim($this->input->post('CancelPaymentAction') ?? '');
            if (in_array($postAction, ['debit_note', 'refund'])) {
                $cancelPaymentAction = $postAction;
            }

            $this->load->model('transactions_model');
            $existing = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$existing) throw new ValidationException('Purchase bill not found.');

            // Point 4: guard â€” block delete if a debit note credit has already been applied to this purchase
            $readDb = $this->load->database('ReadDB', TRUE);
            $readDb->db_debug = FALSE;
            $debitAppliedCheck = $readDb->query(
                'SELECT PaymentUID FROM Transaction.PaymentsTbl
                  WHERE TransUID = ? AND OrgUID = ? AND PaymentTypeUID = 0
                    AND PartyType = ? AND IsDeleted = 0 AND IsCancelled = 0
                  LIMIT 1',
                [$transUID, $orgUID, 'S']
            )->row();
            if ($debitAppliedCheck) {
                throw new ValidationException(
                    'A debit note credit has already been applied to this purchase bill. ' .
                    'Please remove the debit note payment entry first, then delete this bill.'
                );
            }

            // Fetch payment total before the DB transaction so we can fix ledger reversal
            $payments    = $this->transactions_model->getTransactionPayments($transUID, $orgUID);
            $alreadyPaid = array_sum(array_column((array) $payments, 'Amount'));

            $this->dbwrite_model->reverseStockMovements($transUID, $orgUID, $userUID);

            // Mark payments before deleting the transaction
            if ($alreadyPaid > 0 && in_array($cancelPaymentAction, ['debit_note', 'refund'])) {
                if ($cancelPaymentAction === 'refund') {
                    $this->dbwrite_model->markVendorPaymentsDeletedForTrans($transUID, $orgUID, $userUID);
                } else {
                    // Debit Note: mark payments cancelled and create a vendor debit note credit
                    $this->dbwrite_model->markVendorPaymentsCancelledForTrans($transUID, $orgUID, $userUID);
                    $this->load->library('vendorbalance');
                    $this->vendorbalance->createPurchaseCancelDebitNote(
                        $orgUID, (int)$existing->PartyUID, $transUID,
                        (string)($existing->UniqueNumber ?? ''), $alreadyPaid, $userUID
                    );
                }
            }

            $this->dbwrite_model->updateData(
                'Transaction', 'TransProductsTbl',
                ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID],
                ['TransUID' => $transUID, 'IsDeleted' => 0]
            );

            $deleteData = $this->globalservice->baseDeleteArrayDetails();
            $deleteData['IsActive'] = 0;

            $deleteResp = $this->dbwrite_model->updateData(
                'Transaction', 'TransactionsTbl', $deleteData,
                ['TransUID' => $transUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );
            if ($deleteResp->Error) throw new Exception($deleteResp->Message);

            $this->dbwrite_model->commitTransaction();

            // Reverse vendor ledger + journal after commit
            if ($existing->DocStatus !== 'Draft' && $existing->PartyType === 'S' && $existing->PartyUID > 0) {
                try {
                    $this->load->library('accountledger');
                    // Only reverse the unpaid portion â€” payments already reduced vendor balance
                    $remaining = max(0, round((float)$existing->NetAmount - $alreadyPaid, $this->_decimals()));
                    if ($remaining > 0) {
                        $this->accountledger->applyLedgerEntry($existing->PartyUID, 'Vendor', $remaining, 'Debit', $transUID);
                    }
                    $this->accountledger->reverseJournal('Purchase', $transUID, $userUID);
                } catch (Exception $ledgerEx) {
                }
                $this->_recalcVendorBalance($orgUID, $existing->PartyUID, $userUID);
            }

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Purchase bill deleted successfully.';
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'DELETE_PURCHASE', 'Purchase', (int) $transUID, (string) ($existing->UniqueNumber ?? ''),
                ['CancelPaymentAction' => $cancelPaymentAction], 'Deleted purchase #' . $transUID, 'Purchases', 'TRANSACTION'
            );

            $this->_buildListResponse('transactions/purchases/list', '/transactions/getPageDetails/105');

        } catch (ValidationException $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Purchases::deletePurchase', $e);
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function duplicatePurchase() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData = $this->input->post();
            $srcUID   = (int) getPostValue($PostData, 'TransUID');
            $userUID  = $this->pageData['JwtData']->User->UserUID;
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;

            if ($srcUID <= 0) throw new ValidationException('Invalid purchase bill.');

            $this->load->model('transactions_model');
            $src = $this->transactions_model->getTransactionById($srcUID, $orgUID, $this->pageModuleUID);
            if (!$src) throw new ValidationException('Purchase bill not found.');

            $nextNumber   = $this->transactions_model->getNextTransactionNumber($src->PrefixUID, $orgUID, $this->pageModuleUID);
            $prefixResult = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.PrefixUID' => $src->PrefixUID, 'Prefix.OrgUID' => $orgUID]);
            $prefix       = $prefixResult->Data[0] ?? null;
            if (!$prefix) throw new ValidationException('Prefix not found.');

            $sep   = $prefix->Separator ?? '-';
            $parts = [strtoupper($prefix->Name)];
            if (!empty($prefix->IncludeShortName) && !empty($prefix->ShortName)) {
                $parts[] = strtoupper($prefix->ShortName);
            }
            if (!empty($prefix->IncludeFiscalYear)) {
                $m  = (int) date('m');
                $yr = (int) date('Y');
                $fy = $m >= 4 ? $yr : $yr - 1;
                $parts[] = ($prefix->FiscalYearFormat ?? 'SHORT') === 'LONG'
                    ? $fy . '-' . ($fy + 1)
                    : str_pad($fy % 100, 2, '0', STR_PAD_LEFT) . '-' . str_pad(($fy + 1) % 100, 2, '0', STR_PAD_LEFT);
            }
            $pad     = (int)($prefix->NumberPadding ?? 1);
            $parts[] = $pad > 1 ? str_pad($nextNumber, $pad, '0', STR_PAD_LEFT) : (string) $nextNumber;
            $uniqueNumber = implode($sep, $parts);

            $today = date('Y-m-d');

            $headerData = [
                'OrgUID'            => $orgUID,
                'ModuleUID'         => $this->pageModuleUID,
                'PrefixUID'         => $src->PrefixUID,
                'UniqueNumber'      => $uniqueNumber,
                'TransType'         => 'Purchase',
                'TransNumber'       => $nextNumber,
                'PartyType'         => 'S',
                'PartyUID'          => $src->PartyUID,
                'TransDate'         => $today,
                'TransYear'         => (int) date('Y'),
                'DocType'     => $src->DocType,
                'TotalQuantity'     => $src->TotalQuantity ?? 0,
                'TotalItems'        => $src->TotalItems    ?? 0,
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
                'IsForeignCustomer' => $src->IsForeignVendor ?? NULL,
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
                    'IsActive'          => 1,
                    'IsDeleted'         => 0,
                    'CreatedBy'         => $userUID,
                    'UpdatedBy'         => $userUID,
                ];
                $this->dbwrite_model->insertData('Transaction', 'TransProductsTbl', $itemRow);
            }

            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error    = FALSE;
            $this->EndReturnData->Message  = 'Purchase bill duplicated as ' . $uniqueNumber . '.';
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'DUPLICATE_PURCHASE', 'Purchase', (int) $newTransUID, (string) $uniqueNumber,
                [], 'Duplicated purchase ' . $uniqueNumber, 'Purchases', 'TRANSACTION'
            );
            $this->EndReturnData->TransUID = $newTransUID;
            $this->EndReturnData->EditURL  = '/purchases/edit/' . $newTransUID;

        } catch (ValidationException $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Purchases::duplicatePurchase', $e);
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function updatePurchaseStatus() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');
            $PostData  = $this->input->post();
            $transUID  = (int) getPostValue($PostData, 'TransUID');
            $newStatus = trim(getPostValue($PostData, 'Status'));
            $userUID   = $this->pageData['JwtData']->User->UserUID;
            $orgUID    = $this->pageData['JwtData']->Org->OrgUID;

            if ($transUID <= 0) throw new ValidationException('Invalid purchase bill.');

            $validTransitions = [
                'Draft'     => ['Received'],
                'Received'  => ['Paid', 'Cancelled'],
                'Paid'      => ['Cancelled'],
                'Cancelled' => [],
            ];

            // Point 5: read JWT setting as authoritative default; POST overrides only for 'ask' mode
            $transSettings       = $this->pageData['JwtData']->TransSettings ?? null;
            $cancelPaymentAction = $transSettings->PurchaseCancelAction ?? 'ask';
            $postAction          = trim($this->input->post('CancelPaymentAction') ?? '');
            if (in_array($postAction, ['debit_note', 'refund'])) {
                $cancelPaymentAction = $postAction;
            }
            $cancelReason = trim($this->input->post('CancelReason') ?? '');

            $this->load->model('transactions_model');
            $existing = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$existing) throw new ValidationException('Purchase bill not found.');

            $current = $existing->DocStatus;
            if (!in_array($newStatus, $validTransitions[$current] ?? [])) {
                throw new ValidationException("Cannot change status from {$current} to {$newStatus}.");
            }

            // Block cancel if a debit note credit has already been applied to this purchase
            if ($newStatus === 'Cancelled') {
                $readDb = $this->load->database('ReadDB', TRUE);
                $readDb->db_debug = FALSE;
                $dnApplied = $readDb->query(
                    'SELECT PaymentUID FROM Transaction.PaymentsTbl
                      WHERE TransUID = ? AND OrgUID = ? AND PaymentTypeUID = 0
                        AND PartyType = ? AND IsDeleted = 0 AND IsCancelled = 0
                      LIMIT 1',
                    [$transUID, $orgUID, 'S']
                )->row();
                if ($dnApplied) {
                    throw new ValidationException(
                        'A debit note credit has already been applied to this purchase bill. ' .
                        'Please remove the debit note payment entry first, then cancel this bill.'
                    );
                }
            }

            $updateFields = [
                'DocStatus' => $newStatus,
                'UpdatedBy' => $userUID,
                'UpdatedOn' => date('Y-m-d H:i:s'),
            ];
            // Point 1 + 2: set IsCancelled flag and persist CancelReason
            if ($newStatus === 'Cancelled') {
                $updateFields['IsCancelled']  = 1;
                $updateFields['CancelReason'] = $cancelReason ?: NULL;
            }

            $this->dbwrite_model->startTransaction();
            $resp = $this->dbwrite_model->updateData(
                'Transaction', 'TransactionsTbl', $updateFields,
                ['TransUID' => $transUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );
            if ($resp->Error) throw new Exception($resp->Message);

            if ($newStatus === 'Cancelled') {
                // Point 3: cascade IsCancelled to child records
                $this->dbwrite_model->cancelTransactionChildRecords($transUID, $userUID);
                // Point 11: stock reversal inside the transaction (same failure scope as status update)
                $this->dbwrite_model->reverseStockMovements($transUID, $orgUID, $userUID);
            }

            $this->dbwrite_model->commitTransaction();

            if ($newStatus === 'Cancelled') {
                if ($existing->DocStatus !== 'Draft' && !empty($existing->PartyUID)) {
                    // Payment handling â€” only when a paid amount exists
                    $payments    = $this->transactions_model->getTransactionPayments($transUID, $orgUID);
                    $alreadyPaid = array_sum(array_column((array) $payments, 'Amount'));

                    if ($alreadyPaid > 0 && in_array($cancelPaymentAction, ['debit_note', 'refund'])) {
                        if ($cancelPaymentAction === 'refund') {
                            $this->dbwrite_model->markVendorPaymentsDeletedForTrans($transUID, $orgUID, $userUID);
                        } else {
                            // Debit Note: mark payments cancelled and create a vendor debit note credit
                            $this->dbwrite_model->markVendorPaymentsCancelledForTrans($transUID, $orgUID, $userUID);
                            $this->load->library('vendorbalance');
                            $this->vendorbalance->createPurchaseCancelDebitNote(
                                $orgUID, (int)$existing->PartyUID, $transUID,
                                (string)($existing->UniqueNumber ?? ''), $alreadyPaid, $userUID
                            );
                        }
                    }

                    try {
                        $this->load->library('accountledger');
                        // Reverse only the unpaid portion â€” payments already reduced vendor balance
                        $remaining = max(0, round((float)$existing->NetAmount - $alreadyPaid, $this->_decimals()));
                        if ($remaining > 0) {
                            $this->accountledger->applyLedgerEntry($existing->PartyUID, 'Vendor', $remaining, 'Debit', $transUID);
                        }
                        $this->accountledger->reverseJournal('Purchase', $transUID, $userUID);
                    } catch (Exception $ledgerEx) {
                    }
                    $this->_recalcVendorBalance($orgUID, $existing->PartyUID, $userUID);
                }
            }

            $docNum = $existing->UniqueNumber ?? '';
            $prefix = $docNum ? "{$docNum} " : '';
            if ($newStatus === 'Cancelled') {
                $msg = "Purchase bill {$prefix}cancelled successfully.";
            } elseif ($newStatus === 'Received') {
                $msg = "Purchase bill {$prefix}marked as received.";
            } elseif ($newStatus === 'Paid') {
                $msg = "Purchase bill {$prefix}marked as paid.";
            } else {
                $msg = 'Status updated.';
            }

            $this->EndReturnData->Error     = FALSE;
            $this->EndReturnData->Message   = $msg;
            if ($cancelPaymentAction === 'debit_note') {
                $payments    = $payments ?? $this->transactions_model->getTransactionPayments($transUID, $orgUID);
                $this->EndReturnData->DebitNoteAmount = array_sum(array_column((array) $payments, 'Amount'));
            }
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'UPDATE_PURCHASE_STATUS', 'Purchase', (int) $transUID, (string) ($existing->UniqueNumber ?? ''),
                ['NewStatus' => $newStatus, 'CancelPaymentAction' => $cancelPaymentAction, 'CancelReason' => $cancelReason],
                'Updated purchase status #' . $transUID . ($cancelReason ? ' â€” ' . $cancelReason : ''), 'Purchases', 'TRANSACTION'
            );
            $this->EndReturnData->NewStatus = $newStatus;

        } catch (ValidationException $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Purchases::updatePurchaseStatus', $e);
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
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
                $nextNumberMap[(int)$pd->PrefixUID] = $this->transactions_model->getNextTransactionNumber(
                    $pd->PrefixUID, $orgUID, $this->pageModuleUID
                );
            }
            $this->pageData['NextNumberMap'] = $nextNumberMap;

            // Pre-fill from Purchase Order if converting
            $fromPOUID = (int) $this->input->get('fromPurchaseOrder');
            $this->pageData['FromPOUID'] = $fromPOUID;
            $this->pageData['POData']    = null;
            $this->pageData['POItems']   = [];
            $this->pageData['AdditionalCharges']  = $this->_getAdditionalChargesForOrg((int)$orgUID, true);
            $this->pageData['TaxList']            = $this->_getTaxList();
            $this->pageData['TransactionCharges'] = [];
            $this->pageData['IsEditMode']         = false;
            if ($fromPOUID > 0) {
                $poData  = $this->transactions_model->getTransactionById($fromPOUID, $orgUID, 104);
                $poItems = $poData ? $this->transactions_model->getTransactionItems($fromPOUID, $orgUID) : [];
                $this->pageData['POData']  = $poData;
                $this->pageData['POItems'] = $poItems;
            }

            $this->pageData['PaymentTypes'] = $this->transactions_model->getPaymentTypesList();
            $this->pageData['BankAccounts'] = $this->transactions_model->getOrgBankAccounts($orgUID);

            $this->_getDispatchAddresses($orgUID);
            $this->_loadUpstashConfig();

            $this->load->view('transactions/purchases/forms/form', $this->pageData);

        } catch (ValidationException $e) {
            redirect('purchases', 'refresh');
        } catch (Exception $e) {
            notifyError('Purchases::create', $e);
            redirect('purchases', 'refresh');
        }

    }

    public function edit($transUID = 0) {

        try {

            $transUID = (int) $transUID;
            if ($transUID <= 0) redirect('purchases');

            $orgUID = $this->pageData['JwtData']->Org->OrgUID;
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;

            $this->load->model('transactions_model');

            $purchData  = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$purchData) redirect('purchases');

            $purchItems = $this->transactions_model->getTransactionItems($transUID, $orgUID);

            $this->pageData['PurchData']          = $purchData;
            $this->pageData['PurchItems']         = $purchItems;
            $this->pageData['PurchAttachments']   = $this->transactions_model->getTransactionAttachments($transUID, $orgUID);
            $this->pageData['PurchSerialsByProd'] = $this->_getTransSerialsGrouped($transUID, $orgUID, 'Purchase');

            $prefixResult                    = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID, 'Prefix.ModuleUID' => $this->pageModuleUID]);
            $this->pageData['PrefixData']    = $prefixResult->Data ?? [];

            $nextNumberMap = [];
            foreach ($this->pageData['PrefixData'] as $pd) {
                $nextNumberMap[(int)$pd->PrefixUID] = $this->transactions_model->getNextTransactionNumber(
                    $pd->PrefixUID, $orgUID, $this->pageModuleUID
                );
            }
            $this->pageData['NextNumberMap'] = $nextNumberMap;

            // Load vendor address for inter-state detection
            $this->load->model('vendors_model');
            $vendorAddrArr                = $this->vendors_model->getVendorAddress(['VendAddress.VendorUID' => (int)$purchData->PartyUID, 'VendAddress.OrgUID' => $orgUID]);
            $this->pageData['VendorAddr'] = !empty($vendorAddrArr) ? $vendorAddrArr[0] : null;

            $this->pageData['AdditionalCharges']  = $this->_getAdditionalChargesForOrg((int)$orgUID, true);
            $this->pageData['TransactionCharges'] = $this->transactions_model->getTransactionCharges($transUID, (int)$orgUID);
            $this->pageData['TaxList']            = $this->_getTaxList();
            $this->pageData['IsEditMode']         = true;

            $this->pageData['PaymentTypes'] = $this->transactions_model->getPaymentTypesList();
            $this->pageData['BankAccounts'] = $this->transactions_model->getOrgBankAccounts($orgUID);

            $this->_getDispatchAddresses($orgUID);
            $this->_loadUpstashConfig();

            $this->load->view('transactions/purchases/forms/form', $this->pageData);

        } catch (ValidationException $e) {
            redirect('purchases', 'refresh');
        } catch (Exception $e) {
            notifyError('Purchases::edit', $e);
            redirect('purchases', 'refresh');
        }

    }

    private function _touchVendorCache($vendorUID) {
        $this->cachehelper->touchVendor($vendorUID);
    }

    private function _refreshProductCache(array $items) {
        $seen = [];
        foreach ($items as $item) {
            $uid = (int)($item['id'] ?? 0);
            if ($uid > 0 && !isset($seen[$uid])) {
                $seen[$uid] = true;
                $this->cachehelper->upsertProduct($uid);
            }
        }
    }

    /**
     * Update the product master purchase price for items the user chose to sync.
     * Called after transaction commit when UpdatePurchasePrices=1 is in the POST.
     * @param int   $orgUID
     * @param int   $userUID
     * @param array $postData
     * @return void
     */
    private function _applyPurchasePriceUpdates(int $orgUID, int $userUID, array $postData): void {
        $json = getPostValue($postData, 'PriceChangedItems');
        if (!$json) return;
        $changedItems = json_decode($json, true);
        if (!is_array($changedItems) || empty($changedItems)) return;

        $this->load->model('dbwrite_model');
        foreach ($changedItems as $entry) {
            $productUID = (int)($entry['productUID'] ?? 0);
            $newPrice   = (float)($entry['newPrice'] ?? 0);
            if ($productUID <= 0 || $newPrice <= 0) continue;

            $this->dbwrite_model->updateData(
                'Products', 'ProductTbl',
                ['PurchasePrice' => $newPrice, 'UpdatedBy' => $userUID],
                ['ProductUID' => $productUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );
            $this->cachehelper->upsertProduct($productUID);
        }
    }








    public function recordPurchasePayment(): void {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');
            $this->load->model('transactions_model');

            $PostData = $this->input->post();
            $userUID  = $this->pageData['JwtData']->User->UserUID;
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;

            $transUID       = (int)   getPostValue($PostData, 'TransUID');
            $paymentTypeUID = (int)   getPostValue($PostData, 'PaymentTypeUID');
            $amount         = (float) getPostValue($PostData, 'Amount', 'Array', 0);
            $debitNoteUID   = (int)   getPostValue($PostData, 'DebitNoteUID');
            $debitNoteAmt   = (float) getPostValue($PostData, 'DebitNoteAmount', 'Array', 0);
            $paymentDate    =         getPostValue($PostData, 'PaymentDate') ?: date('Y-m-d');
            $bankAccountUID = (int)   getPostValue($PostData, 'BankAccountUID') ?: NULL;
            $referenceNo    =         getPostValue($PostData, 'ReferenceNo') ?: NULL;
            $notes          =         getPostValue($PostData, 'Notes') ?: NULL;

            if ($transUID <= 0) throw new ValidationException('Invalid transaction.');
            if ($amount <= 0 && $debitNoteAmt <= 0) throw new ValidationException('Enter a payment amount or select a debit note.');
            if ($amount > 0 && $paymentTypeUID <= 0) throw new ValidationException('Please select a payment type.');

            $existing = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$existing)                                                throw new ValidationException('Purchase not found.');
            if ($existing->DocStatus === 'Draft')                          throw new ValidationException('Cannot record payment for a Draft purchase.');
            if (in_array($existing->DocStatus, ['Cancelled', 'Rejected'])) throw new ValidationException('Purchase is cancelled.');

            // Validate debit note before locking
            $debitNote = null;
            if ($debitNoteUID > 0 && $debitNoteAmt > 0) {
                $debitNote = $this->transactions_model->getVendorDebitNote($debitNoteUID, $orgUID);
                if (!$debitNote) throw new ValidationException('Debit note not found.');
                if ($debitNote->Status !== 'Pending') throw new ValidationException('This debit note has already been applied.');
                if ((int)$debitNote->PartyUID !== (int)$existing->PartyUID) {
                    throw new ValidationException('Debit note does not belong to this vendor.');
                }
                if ($debitNoteAmt > (float)$debitNote->Amount + 0.01) {
                    throw new ValidationException('Debit note amount exceeds available balance.');
                }
            }

            $this->dbwrite_model->startTransaction();

            if (!$this->dbwrite_model->lockTransactionRow($transUID, $orgUID)) {
                throw new ValidationException('Purchase not found.');
            }
            $alreadyPaid   = $this->dbwrite_model->sumTransactionPayments($transUID, $orgUID);
            $pending       = max(0, round((float)$existing->NetAmount - $alreadyPaid, $this->_decimals()));
            $effectivePaid = round($amount + $debitNoteAmt, $this->_decimals());

            if ($effectivePaid > $pending + 0.01) {
                throw new ValidationException('Total payment (' . $effectivePaid . ') exceeds remaining balance (' . $pending . ').');
            }

            $newTotalPaid  = round($alreadyPaid + $effectivePaid, $this->_decimals());
            $balanceAmount = max(0, round((float)$existing->NetAmount - $newTotalPaid, $this->_decimals()));
            $isFullyPaid   = ($existing->NetAmount > 0 && $balanceAmount <= 0) ? 1 : 0;
            $excessAmount  = max(0, round($newTotalPaid - (float)$existing->NetAmount, 4));
            $newStatus     = $isFullyPaid ? 'Paid' : 'Partial';

            $payTransYear  = (int) date('Y', strtotime($paymentDate));
            $payPrefixData = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID, 'Prefix.ModuleUID' => 111]);
            $payPrefix     = !empty($payPrefixData->Data) ? $payPrefixData->Data[0] : null;
            $payPrefixUID  = $payPrefix ? (int) $payPrefix->PrefixUID : null;
            $receiptToken  = $this->transactions_model->_generateReceiptToken();
            $cashPaymentID = null;

            // Insert cash payment row when amount > 0
            if ($amount > 0) {
                $paymentNumber = $payPrefixUID ? $this->transactions_model->getNextPaymentNumber($payPrefixUID, $orgUID, $payTransYear) : 0;
                $payUniqueNum  = ($payPrefix && $paymentNumber > 0) ? $this->_buildPaymentUniqueNumber($payPrefix, $paymentDate, $paymentNumber) : null;
                $resp = $this->dbwrite_model->insertData('Transaction', 'PaymentsTbl', [
                    'OrgUID'           => $orgUID,
                    'PaymentDate'      => $paymentDate,
                    'PrefixUID'        => $payPrefixUID,
                    'PaymentNumber'    => $paymentNumber,
                    'UniqueNumber'     => $payUniqueNum,
                    'ReceiptToken'     => $receiptToken,
                    'TransYear'        => $payTransYear,
                    'TransUID'         => $transUID,
                    'ModuleUID'        => 111,
                    'PartyType'        => 'S',
                    'PartyUID'         => $existing->PartyUID,
                    'PaymentTypeUID'   => $paymentTypeUID,
                    'Amount'           => $amount,
                    'BankAccountUID'   => $bankAccountUID,
                    'ReferenceNo'      => $referenceNo,
                    'Notes'            => $notes,
                    'PaymentSource'    => 'Record',
                    'PaymentDirection' => 'Out',
                    'IsFullyPaid'      => $isFullyPaid,
                    'ExcessAmount'     => $excessAmount,
                    'IsActive'         => 1,
                    'IsDeleted'        => 0,
                    'CreatedBy'        => $userUID,
                    'UpdatedBy'        => $userUID,
                ]);
                if ($resp->Error) throw new Exception($resp->Message);
                $cashPaymentID = $resp->ID;
            }

            // Insert debit note adjustment row when debitNoteAmt > 0
            if ($debitNote !== null && $debitNoteAmt > 0) {
                $dnPayNumber  = $payPrefixUID ? $this->transactions_model->getNextPaymentNumber($payPrefixUID, $orgUID, $payTransYear) : 0;
                $dnUniqueNum  = ($payPrefix && $dnPayNumber > 0) ? $this->_buildPaymentUniqueNumber($payPrefix, $paymentDate, $dnPayNumber) : null;
                $dnReceipt    = $this->transactions_model->_generateReceiptToken();
                $dnResp = $this->dbwrite_model->insertData('Transaction', 'PaymentsTbl', [
                    'OrgUID'           => $orgUID,
                    'PaymentDate'      => $paymentDate,
                    'PrefixUID'        => $payPrefixUID,
                    'PaymentNumber'    => $dnPayNumber,
                    'UniqueNumber'     => $dnUniqueNum,
                    'ReceiptToken'     => $dnReceipt,
                    'TransYear'        => $payTransYear,
                    'TransUID'         => $transUID,
                    'ModuleUID'        => 111,
                    'PartyType'        => 'S',
                    'PartyUID'         => $existing->PartyUID,
                    'PaymentTypeUID'   => 0,
                    'Amount'           => $debitNoteAmt,
                    'BankAccountUID'   => NULL,
                    'ReferenceNo'      => $debitNote->SourceTransNumber,
                    'Notes'            => 'Debit note credit applied',
                    'PaymentSource'    => 'Record',
                    'PaymentDirection' => 'Out',
                    'IsFullyPaid'      => $isFullyPaid,
                    'ExcessAmount'     => 0,
                    'IsActive'         => 1,
                    'IsDeleted'        => 0,
                    'CreatedBy'        => $userUID,
                    'UpdatedBy'        => $userUID,
                ]);
                if ($dnResp->Error) throw new Exception($dnResp->Message);

                $newDnBalance = round((float)$debitNote->Amount - $debitNoteAmt, $this->_decimals());
                $newDnStatus  = $newDnBalance <= 0 ? 'Applied' : 'Pending';
                $dnUpd = $this->dbwrite_model->updateData(
                    'Transaction', 'TransDebitNoteTbl',
                    ['Amount' => max(0, $newDnBalance), 'Status' => $newDnStatus, 'UpdatedBy' => $userUID],
                    ['DebitNoteUID' => $debitNoteUID, 'OrgUID' => $orgUID]
                );
                if ($dnUpd->Error) throw new Exception('Failed to update debit note: ' . $dnUpd->Message);
            }

            $ok = $this->dbwrite_model->updateTransIsFullyPaid($transUID, $isFullyPaid, $newTotalPaid, $balanceAmount, $userUID);
            if ($ok === false) throw new Exception('Failed to update purchase balance.');
            $this->dbwrite_model->updateTransDocStatus($transUID, $orgUID, $newStatus, $userUID);

            $this->dbwrite_model->commitTransaction();

            // Save payment file attachments for the cash payment row only
            if ($cashPaymentID) {
                $this->_savePaymentAttachments($cashPaymentID);
            }

            // Debit vendor ledger for cash amount only
            if ($amount > 0) {
                try {
                    $this->load->library('accountledger');
                    $this->accountledger->applyLedgerEntry($existing->PartyUID, 'Vendor', $amount, 'Debit', $transUID);
                    $this->accountledger->postPaymentJournal(
                        'made', $transUID, $paymentDate, $payTransYear,
                        $amount, $existing->PartyUID, 'Vendor', $userUID
                    );
                } catch (Exception $ledgerEx) {
                }
                $this->_writeBankLedgerEntry(
                    $orgUID, $bankAccountUID, 'DR', $amount,
                    'Purchase', $transUID, 111,
                    $referenceNo, 'Payment made to vendor â€” ' . ($existing->UniqueNumber ?? '#' . $transUID),
                    $paymentDate, $userUID
                );
            }

            $this->_recalcVendorBalance($orgUID, $existing->PartyUID, $userUID);

            $msgParts = [];
            if ($amount > 0)       { $msgParts[] = 'Payment of ' . $amount; }
            if ($debitNoteAmt > 0) { $msgParts[] = 'Debit note credit of ' . $debitNoteAmt; }
            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Message    = implode(' + ', $msgParts) . ' applied to ' . ($existing->UniqueNumber ?? '#' . $transUID) . '.';
            $this->EndReturnData->IsFullyPaid = $isFullyPaid;
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'RECORD_PURCHASE_PAYMENT', 'Purchase', (int) $transUID, (string) ($existing->UniqueNumber ?? ''),
                ['Amount' => $amount, 'DebitNoteUID' => $debitNoteUID, 'DebitNoteAmount' => $debitNoteAmt],
                'Recorded payment for purchase ' . ($existing->UniqueNumber ?? ''), 'Purchases', 'PAYMENT'
            );

            $this->_buildPaymentListResponse('transactions/purchases/list', '/transactions/getPageDetails/105');

        } catch (ValidationException $e) {
            if (isset($this->dbwrite_model)) $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Purchases::recordPurchasePayment', $e);
            if (isset($this->dbwrite_model)) $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function getVendorDebitNotes(): void {
        $this->EndReturnData = new stdClass();
        try {
            $vendorUID = (int)($this->input->get('VendorUID') ?: $this->input->post('VendorUID'));
            $orgUID    = $this->pageData['JwtData']->Org->OrgUID;
            if ($vendorUID <= 0) throw new Exception('Invalid vendor.');

            $this->load->model('transactions_model');
            $rows = $this->transactions_model->getVendorAvailableDebitNotes($orgUID, $vendorUID);

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $rows;
        } catch (ValidationException $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Purchases::getVendorDebitNotes', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function applyDebitNote(): void {
        $this->EndReturnData = new stdClass();
        try {
            $this->load->model('dbwrite_model');
            $this->load->model('transactions_model');

            $PostData      = $this->input->post();
            $userUID       = $this->pageData['JwtData']->User->UserUID;
            $orgUID        = $this->pageData['JwtData']->Org->OrgUID;
            $transUID      = (int)   getPostValue($PostData, 'TransUID');
            $debitNoteUID  = (int)   getPostValue($PostData, 'DebitNoteUID');
            $amount        = (float) getPostValue($PostData, 'Amount', 'Array', 0);
            $notes         = getPostValue($PostData, 'Notes') ?: NULL;

            if ($transUID     <= 0) throw new ValidationException('Invalid purchase.');
            if ($debitNoteUID <= 0) throw new ValidationException('Invalid debit note.');
            if ($amount       <= 0) throw new ValidationException('Amount must be greater than 0.');

            $purchase = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$purchase) throw new ValidationException('Purchase not found.');
            if (in_array($purchase->DocStatus, ['Draft', 'Cancelled', 'Rejected'])) {
                throw new ValidationException('Cannot apply debit to this purchase.');
            }

            // Load the debit note row
            $debitNote = $this->transactions_model->getVendorDebitNote($debitNoteUID, $orgUID);
            if (!$debitNote) throw new ValidationException('Debit note not found.');
            if ($debitNote->Status !== 'Pending') throw new ValidationException('This debit note has already been fully applied.');
            if ((int)$debitNote->PartyUID !== (int)$purchase->PartyUID) {
                throw new ValidationException('Debit note does not belong to the same vendor as this purchase.');
            }

            $dnBalance    = (float)$debitNote->Amount;
            $purchPaid    = (float)($purchase->PaidAmount ?? 0);
            $purchBalance = max(0, round((float)$purchase->NetAmount - $purchPaid, $this->_decimals()));

            if ($purchBalance <= 0) throw new ValidationException('Purchase has no pending balance.');
            if ($dnBalance    <= 0) throw new ValidationException('Debit note has no remaining balance.');

            $maxAmount = min($dnBalance, $purchBalance);
            if ($amount > $maxAmount + 0.01) {
                throw new ValidationException('Amount exceeds available debit (' . smartDecimal($maxAmount) . ').');
            }
            $amount = min($amount, $maxAmount);

            $this->dbwrite_model->startTransaction();

            // Lock purchase row to prevent concurrent payment race
            $this->dbwrite_model->lockTransactionRow($transUID, $orgUID);

            // Generate payment number
            $today         = date('Y-m-d');
            $payTransYear  = (int)date('Y');
            $payPrefixData = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID, 'Prefix.ModuleUID' => 111]);
            $payPrefix     = !empty($payPrefixData->Data) ? $payPrefixData->Data[0] : null;
            $payPrefixUID  = $payPrefix ? (int)$payPrefix->PrefixUID : null;
            $paymentNumber = $payPrefixUID ? $this->transactions_model->getNextPaymentNumber($payPrefixUID, $orgUID, $payTransYear) : 0;
            $payUniqueNum  = ($payPrefix && $paymentNumber > 0) ? $this->_buildPaymentUniqueNumber($payPrefix, $today, $paymentNumber) : null;
            $receiptToken  = $this->transactions_model->_generateReceiptToken();

            // Insert PaymentsTbl row â€” PaymentTypeUID=0 = debit adjustment
            $paymentData = [
                'OrgUID'           => $orgUID,
                'PaymentDate'      => $today,
                'PaymentModuleUID' => 111,
                'PrefixUID'        => $payPrefixUID,
                'PaymentNumber'    => $paymentNumber,
                'UniqueNumber'     => $payUniqueNum,
                'ReceiptToken'     => $receiptToken,
                'TransYear'        => $payTransYear,
                'TransUID'         => $transUID,
                'ModuleUID'        => 105,
                'PartyType'        => 'S',
                'PartyUID'         => $purchase->PartyUID,
                'PaymentTypeUID'   => 0,
                'Amount'           => $amount,
                'BankAccountUID'   => NULL,
                'ReferenceNo'      => $debitNote->SourceTransNumber,
                'Notes'            => $notes,
                'PaymentSource'    => 'Record',
                'PaymentDirection' => 'Out',
                'IsFullyPaid'      => 0,
                'ExcessAmount'     => 0,
                'IsActive'         => 1,
                'IsDeleted'        => 0,
                'CreatedBy'        => $userUID,
                'UpdatedBy'        => $userUID,
            ];
            $resp = $this->dbwrite_model->insertData('Transaction', 'PaymentsTbl', $paymentData);
            if ($resp->Error) throw new Exception($resp->Message);

            // Update purchase paid/balance/status
            $newPurchPaid    = round($purchPaid + $amount, $this->_decimals());
            $newPurchBalance = max(0, round((float)$purchase->NetAmount - $newPurchPaid, $this->_decimals()));
            $purchFullyPaid  = ($purchase->NetAmount > 0 && $newPurchBalance <= 0) ? 1 : 0;
            $purchNewStatus  = $purchFullyPaid ? 'Paid' : 'Partial';
            $this->dbwrite_model->updateTransIsFullyPaid($transUID, $purchFullyPaid, $newPurchPaid, $newPurchBalance, $userUID);
            $this->dbwrite_model->updateTransDocStatus($transUID, $orgUID, $purchNewStatus, $userUID);

            // Reduce debit note balance; mark Applied when fully consumed
            $newDnBalance = round($dnBalance - $amount, $this->_decimals());
            $newDnStatus  = $newDnBalance <= 0 ? 'Applied' : 'Pending';
            $dnUpd = $this->dbwrite_model->updateData(
                'Transaction', 'TransDebitNoteTbl',
                ['Amount' => max(0, $newDnBalance), 'Status' => $newDnStatus, 'UpdatedBy' => $userUID],
                ['DebitNoteUID' => $debitNoteUID, 'OrgUID' => $orgUID]
            );
            if ($dnUpd->Error) throw new Exception('Failed to update debit note: ' . $dnUpd->Message);

            $this->dbwrite_model->commitTransaction();

            $this->_recalcVendorBalance($orgUID, $purchase->PartyUID, $userUID);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Debit note credit of ' . smartDecimal($amount) . ' applied to purchase ' . ($purchase->UniqueNumber ?? '#' . $transUID) . '.';
            $this->EndReturnData->IsFullyPaid = $purchFullyPaid;
            $this->auditlog->log(
                (int)$orgUID, (int)$userUID,
                'APPLY_DEBIT_NOTE', 'Purchase', (int)$transUID, (string)($purchase->UniqueNumber ?? ''),
                ['DebitNoteUID' => $debitNoteUID, 'Amount' => $amount],
                'Applied debit note #' . $debitNoteUID . ' to purchase ' . ($purchase->UniqueNumber ?? ''), 'Purchases', 'PAYMENT'
            );

            $this->_buildPaymentListResponse('transactions/purchases/list', '/transactions/getPageDetails/105');

        } catch (ValidationException $e) {
            if (isset($this->dbwrite_model)) $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Purchases::applyDebitNote', $e);
            if (isset($this->dbwrite_model)) $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // â”€â”€ Debit Note: refund (vendor pays back in cash) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function refundDebitNote(): void {
        $this->EndReturnData = new stdClass();
        try {
            $orgUID       = $this->pageData['JwtData']->Org->OrgUID;
            $userUID      = $this->pageData['JwtData']->User->UserUID;
            $debitNoteUID = (int)$this->input->post('DebitNoteUID');
            if ($debitNoteUID <= 0) throw new ValidationException('Debit note ID is required.');

            $readDb = $this->load->database('ReadDB', TRUE);
            $readDb->db_debug = FALSE;
            $readDb->from('Transaction.TransDebitNoteTbl');
            $readDb->where(['DebitNoteUID' => $debitNoteUID, 'OrgUID' => (int)$orgUID, 'IsDeleted' => 0, 'IsCancelled' => 0]);
            $dn = $readDb->get()->row();
            if (!$dn) throw new ValidationException('Debit note not found.');
            if ($dn->Status !== 'Pending') throw new ValidationException('Only Pending debit notes can be refunded. This one is ' . $dn->Status . '.');

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();
            $wdb = $this->dbwrite_model->getWriteDb();
            $wdb->db_debug = FALSE;
            $wdb->where(['DebitNoteUID' => $debitNoteUID, 'OrgUID' => (int)$orgUID]);
            $wdb->update('Transaction.TransDebitNoteTbl', ['Status' => 'Refunded', 'UpdatedBy' => $userUID]);
            $this->dbwrite_model->commitTransaction();

            $this->load->library('vendorbalance');
            $this->vendorbalance->recalcAndSync($orgUID, (int)$dn->PartyUID, $userUID);

            $this->auditlog->log($orgUID, $userUID, 'REFUND_DEBIT_NOTE', 'DebitNote', $debitNoteUID,
                $dn->SourceTransNumber ?? '', [], 'Refunded debit note #' . $debitNoteUID, 'Purchases', 'TRANSACTION');

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Debit note marked as refunded.';
        } catch (ValidationException $e) {
            if (isset($this->dbwrite_model)) $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Purchases::refundDebitNote', $e);
            if (isset($this->dbwrite_model)) $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // â”€â”€ Debit Note: delete a Pending note â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function deleteDebitNote(): void {
        $this->EndReturnData = new stdClass();
        try {
            $orgUID       = $this->pageData['JwtData']->Org->OrgUID;
            $userUID      = $this->pageData['JwtData']->User->UserUID;
            $debitNoteUID = (int)$this->input->post('DebitNoteUID');
            if ($debitNoteUID <= 0) throw new ValidationException('Debit note ID is required.');

            $readDb = $this->load->database('ReadDB', TRUE);
            $readDb->db_debug = FALSE;
            $readDb->from('Transaction.TransDebitNoteTbl');
            $readDb->where(['DebitNoteUID' => $debitNoteUID, 'OrgUID' => (int)$orgUID, 'IsDeleted' => 0]);
            $dn = $readDb->get()->row();
            if (!$dn) throw new ValidationException('Debit note not found.');
            if ($dn->Status !== 'Pending') throw new ValidationException('Only Pending debit notes can be deleted. This one is ' . $dn->Status . '.');

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();
            $wdb = $this->dbwrite_model->getWriteDb();
            $wdb->db_debug = FALSE;
            $wdb->where(['DebitNoteUID' => $debitNoteUID, 'OrgUID' => (int)$orgUID]);
            $wdb->update('Transaction.TransDebitNoteTbl', ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID]);
            $this->dbwrite_model->commitTransaction();

            $this->load->library('vendorbalance');
            $this->vendorbalance->recalcAndSync($orgUID, (int)$dn->PartyUID, $userUID);

            $this->auditlog->log($orgUID, $userUID, 'DELETE_DEBIT_NOTE', 'DebitNote', $debitNoteUID,
                $dn->SourceTransNumber ?? '', [], 'Deleted debit note #' . $debitNoteUID, 'Purchases', 'TRANSACTION');

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Debit note deleted successfully.';
        } catch (ValidationException $e) {
            if (isset($this->dbwrite_model)) $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Purchases::deleteDebitNote', $e);
            if (isset($this->dbwrite_model)) $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // â”€â”€ Debit Notes: paginated list for the Debit Notes tab â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function getDebitNotesList(): void {
        $this->EndReturnData = new stdClass();
        try {
            $orgUID  = $this->pageData['JwtData']->Org->OrgUID;
            $pageNo  = max(1, (int)($this->input->post('PageNo')   ?: 1));
            $limit   = max(1, (int)($this->input->post('RowLimit') ?: 10));
            $offset  = ($pageNo - 1) * $limit;
            $status  = trim($this->input->post('Status') ?: '');
            $search  = trim($this->input->post('Search') ?: '');

            $readDb = $this->load->database('ReadDB', TRUE);
            $readDb->db_debug = FALSE;

            $baseWhere = ['DN.OrgUID' => (int)$orgUID, 'DN.PartyType' => 'S', 'DN.IsDeleted' => 0, 'DN.IsCancelled' => 0];
            if ($status !== '' && $status !== 'All') $baseWhere['DN.Status'] = $status;

            // Count
            $readDb->select('COUNT(*) AS total');
            $readDb->from('Transaction.TransDebitNoteTbl DN');
            $readDb->join('Vendors.VendorTbl V', 'V.VendorUID = DN.PartyUID', 'left');
            $readDb->where($baseWhere);
            if ($search !== '') {
                $readDb->group_start();
                $readDb->like('DN.SourceTransNumber', $search);
                $readDb->or_like('V.Name', $search);
                $readDb->group_end();
            }
            $totalCount = (int)($readDb->get()->row()->total ?? 0);

            // Data
            $readDb->select([
                'DN.DebitNoteUID', 'DN.SourceTransUID', 'DN.SourceTransNumber',
                'DN.SourceModuleUID', 'DN.Amount', 'DN.Status', 'DN.Notes', 'DN.CreatedOn',
                'V.VendorUID', 'V.Name AS VendorName', 'V.Image AS VendorImage',
                "CONCAT(U.FirstName, ' ', U.LastName) AS CreatorName",
            ]);
            $readDb->from('Transaction.TransDebitNoteTbl DN');
            $readDb->join('Vendors.VendorTbl V', 'V.VendorUID = DN.PartyUID', 'left');
            $readDb->join('Users.UserTbl U',     'U.UserUID = DN.CreatedBy',  'left');
            $readDb->where($baseWhere);
            if ($search !== '') {
                $readDb->group_start();
                $readDb->like('DN.SourceTransNumber', $search);
                $readDb->or_like('V.Name', $search);
                $readDb->group_end();
            }
            $readDb->order_by('DN.DebitNoteUID', 'DESC');
            $readDb->limit($limit, $offset);
            $rows = $readDb->get()->result();

            $cur      = $this->pageData['JwtData']->GenSettings->CurrenySymbol  ?? 'â‚¹';
            $dec      = 2;
            $timezone = $this->pageData['JwtData']->User->Timezone ?? 'UTC';

            $html = '';
            if (empty($rows)) {
                $html = '<tr><td colspan="6" class="text-center text-muted py-4">No debit notes found.</td></tr>';
            } else {
                foreach ($rows as $i => $dn) {
                    $statusBadge = match($dn->Status) {
                        'Pending'  => '<span class="badge bg-label-warning">Pending</span>',
                        'Applied'  => '<span class="badge bg-label-success">Applied</span>',
                        'Refunded' => '<span class="badge bg-label-info">Refunded</span>',
                        default    => '<span class="badge bg-label-secondary">' . htmlspecialchars($dn->Status) . '</span>',
                    };
                    $canDelete  = $dn->Status === 'Pending';
                    $canRefund  = $dn->Status === 'Pending';
                    $dnModType  = ((int)$dn->SourceModuleUID === 105) ? 'purchase' : 'purchasereturn';
                    $sourceLink = $dn->SourceTransUID
                        ? '<a href="javascript:void(0)" class="viewTransaction fw-semibold" data-uid="' . (int)$dn->SourceTransUID . '" data-module="' . (int)$dn->SourceModuleUID . '" data-type="' . $dnModType . '" data-number="' . htmlspecialchars($dn->SourceTransNumber ?? '') . '">' . htmlspecialchars($dn->SourceTransNumber ?? 'â€”') . '</a>'
                        : htmlspecialchars($dn->SourceTransNumber ?? 'â€”');

                    $dnActions = '<div class="d-flex align-items-center justify-content-end gap-1">'
                        . ($canRefund
                            ? '<button class="btn btn-icon btn-sm text-info inv-row-action dnRefundBtn" data-uid="' . (int)$dn->DebitNoteUID . '" data-num="' . htmlspecialchars($dn->SourceTransNumber ?? '') . '" data-bs-toggle="tooltip" data-bs-placement="top" title="Mark as Refunded"><i class="bx bx-money"></i></button>'
                            : '')
                        . ($canDelete
                            ? '<div class="dropdown">'
                                . '<button class="trans-actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bx bx-dots-vertical-rounded fs-5"></i></button>'
                                . '<ul class="dropdown-menu dropdown-menu-end r2k-action-menu">'
                                    . '<li><a class="dropdown-item text-danger dnDeleteBtn" href="javascript:void(0)" data-uid="' . (int)$dn->DebitNoteUID . '" data-num="' . htmlspecialchars($dn->SourceTransNumber ?? '') . '"><i class="bx bx-trash me-1"></i>Delete</a></li>'
                                . '</ul>'
                                . '</div>'
                            : '')
                        . '</div>';

                    $html .= '<tr>'
                        . '<td class="text-muted small">' . ($offset + $i + 1) . '</td>'
                        . '<td>' . $sourceLink . '<div class="text-muted small">' . htmlspecialchars($dn->VendorName ?? 'â€”') . '</div></td>'
                        . '<td>' . $statusBadge . '</td>'
                        . '<td class="fw-semibold text-success">' . htmlspecialchars($cur) . ' ' . smartDecimal((float)$dn->Amount) . '</td>'
                        . '<td class="text-muted small">' . changeTimeZonefromDateTime($dn->CreatedOn, $timezone, 2) . '<br><span>' . htmlspecialchars($dn->CreatorName ?? 'â€”') . '</span></td>'
                        . '<td>' . $dnActions . '</td>'
                        . '</tr>';
                }
            }

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->TotalCount     = $totalCount;
            $this->EndReturnData->RecordHtmlData = $html;
            $this->EndReturnData->Pagination     = $this->globalservice->buildPagePaginationHtml(
                '/purchases/getDebitNotesList', $totalCount, $pageNo, $limit
            );

        } catch (ValidationException $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Purchases::getDebitNotesList', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function getPaymentAttachments() {

        $this->EndReturnData = new stdClass();
        try {

            $transUID = (int) $this->input->post('TransUID');
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;
            if ($transUID <= 0) throw new ValidationException('Invalid transaction.');

            $this->load->model('transactions_model');
            $payments = $this->transactions_model->getTransactionPayments($transUID, $orgUID);

            $attachments = [];
            foreach ($payments as $payment) {
                $paymentAttachments = $this->transactions_model->getPaymentAttachments($payment->PaymentUID, $orgUID);
                foreach ($paymentAttachments as $attach) {
                    $attach->PaymentTypeName      = $payment->PaymentTypeName;
                    $attach->PaymentAmount        = $payment->Amount;
                    $attach->PaymentUniqueNumber  = $payment->UniqueNumber ?? null;
                    $attachments[] = $attach;
                }
            }

            $this->EndReturnData->Error       = FALSE;
            $this->EndReturnData->Attachments = $attachments;

        } catch (ValidationException $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        } catch (Exception $e) {
            notifyError('Purchases::getPaymentAttachments', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }



}
