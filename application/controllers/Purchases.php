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
        } catch (Exception $e) {
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

        } catch (Exception $e) {
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

        } catch (Exception $e) {
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

            // Apply vendor ledger + post journal after commit
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
                    log_message('error', 'Ledger update failed after purchase creation: ' . $ledgerEx->getMessage());
                }
            }

            $this->_saveAttachments($transUID);
            $this->_touchVendorCache($vendorUID);
            if (!$isDraft) {
                $this->_syncProductCacheFromItems($items);
                $this->_recalcVendorBalance($orgUID, $vendorUID, $userUID);
                try {
                    $this->load->model('purchasepricehistory_model');
                    $this->purchasepricehistory_model->syncFromPurchase($orgUID, $userUID, $transUID, $transDate, $vendorUID, $items);
                } catch (Exception $priceEx) {
                    log_message('error', 'Purchase price log sync failed after create #' . $transUID . ': ' . $priceEx->getMessage());
                }
            }

            $this->EndReturnData->Error    = FALSE;
            $this->EndReturnData->Message  = 'Purchase bill recorded successfully.';
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'ADD_PURCHASE', 'Purchase', (int) $transUID, (string) $uniqueNumber,
                [], 'Created purchase ' . $uniqueNumber, 'Purchases', 'TRANSACTION', 'SUCCESS', '', 'WEB', [], [], $PostData
            );
            $this->EndReturnData->TransUID = $transUID;
            $this->EndReturnData->Token    = $headerData['TransToken'];

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
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
            if ($transUID <= 0) throw new Exception('Purchase bill ID is required.');

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
            if (!$existing) throw new Exception('Purchase bill not found.');

            $uniqueNumber = NULL;
            if ($existing->DocStatus === 'Draft' && !$isDraft) {
                if ($prefixUID <= 0) throw new Exception('Please select a prefix to finalise this purchase bill.');
                if ($transNumber <= 0) throw new Exception('Transaction number must be greater than 0.');

                $prefixData = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.PrefixUID' => $prefixUID, 'Prefix.OrgUID' => $orgUID]);
                if (empty($prefixData->Data)) throw new Exception('Invalid prefix selected.');
                $prefix = $prefixData->Data[0];

                $dupCheck = $this->transactions_model->getTransactionByPrefixAndNumber($prefixUID, $transNumber, $orgUID, $this->pageModuleUID);
                if ($dupCheck) {
                    $nextSuggested = $this->transactions_model->getNextTransactionNumber($prefixUID, $orgUID, $this->pageModuleUID);
                    throw new Exception('Transaction number ' . $transNumber . ' already exists. Next available: ' . $nextSuggested . '.');
                }

                [$uniqueNumber] = $this->buildUniqueNumber($prefix, $transNumber, $amounts['transDate']);
            }

            $newBalance   = max(0, round($netAmount - (float)($existing->PaidAmount ?? 0), $this->_decimals()));
            $updateHeader = $this->_buildTransUpdateHeader($cfg, $amounts, $PostData, $orgUID, $userUID);
            $updateHeader['BalanceAmount'] = $newBalance;

            $isInterState = $amounts['igstAmount'] > 0 ? 1 : ($amounts['cgstAmount'] > 0 || $amounts['sgstAmount'] > 0 ? 0 : NULL);

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

                $this->dbwrite_model->insertData('Transaction', 'TransDetailTbl', array_merge($commonDetail, [
                    'FinancialYear' => $amounts['financialYear'],
                    'TransUID'      => $newTransUID,
                ]));

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
                $this->dbwrite_model->deleteInTransaction('Transaction', 'TransDetailTbl',  ['TransUID' => $transUID]);

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
                    log_message('error', 'Ledger update failed after purchase update: ' . $ledgerEx->getMessage());
                }
            }

            $this->_saveAttachments($activeTransUID);
            $this->_softDeleteAttachments($this->input->post('RemovedAttachIDs') ?? '');

            $this->_saveTransCharges($activeTransUID, $orgUID, $userUID, $PostData);
            $this->dbwrite_model->commitTransaction();
            $this->_touchVendorCache($vendorUID);
            if (!$isDraft) {
                foreach ($items as $_item) { $u = (int)($_item['id'] ?? 0); if ($u > 0) $_cacheUIDs[$u] = true; }
                foreach (array_keys($_cacheUIDs) as $_uid) { $this->cachehelper->upsertProduct($_uid); }
                $this->_recalcVendorBalance($orgUID, $vendorUID, $userUID);
                try {
                    $this->load->model('purchasepricehistory_model');
                    $this->purchasepricehistory_model->updateFromPurchase($orgUID, $userUID, $activeTransUID, $transUID, $amounts['transDate'], $vendorUID, $items);
                } catch (Exception $priceEx) {
                    log_message('error', 'Purchase price log sync failed after update #' . $activeTransUID . ': ' . $priceEx->getMessage());
                }
            }
            $this->transactions_model->generateAndStorePdf($activeTransUID, $orgUID, $this->pageModuleUID);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Purchase bill updated successfully.';
            $this->EndReturnData->Token   = $this->_getOrCreateTransToken($activeTransUID);
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'UPDATE_PURCHASE', 'Purchase', (int) $activeTransUID, (string) ($uniqueNumber ?? $existing->UniqueNumber ?? ''),
                [], 'Updated purchase ' . ($uniqueNumber ?? $existing->UniqueNumber ?? ''), 'Purchases', 'TRANSACTION', 'SUCCESS', '', 'WEB', [], [], $PostData
            );

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
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
            if ($transUID <= 0) throw new Exception('Purchase bill ID is required.');

            $this->load->model('transactions_model');
            $existing = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$existing) throw new Exception('Purchase bill not found.');

            $this->dbwrite_model->reverseStockMovements($transUID, $orgUID, $userUID);

            $now = time();

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
                    $this->accountledger->applyLedgerEntry($existing->PartyUID, 'Vendor', (float) $existing->NetAmount, 'Debit', $transUID);
                    $this->accountledger->reverseJournal('Purchase', $transUID, $userUID);
                } catch (Exception $ledgerEx) {
                    log_message('error', 'Ledger reversal failed after purchase delete: ' . $ledgerEx->getMessage());
                }
                $this->_recalcVendorBalance($orgUID, $existing->PartyUID, $userUID);
            }

            try {
                $this->load->model('purchasepricehistory_model');
                $this->purchasepricehistory_model->softDeleteByTransaction($orgUID, $transUID, $userUID);
            } catch (Exception $priceEx) {
                log_message('error', 'Purchase price log soft-delete failed after delete #' . $transUID . ': ' . $priceEx->getMessage());
            }

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Purchase bill deleted successfully.';
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'DELETE_PURCHASE', 'Purchase', (int) $transUID, (string) ($existing->UniqueNumber ?? ''),
                [], 'Deleted purchase #' . $transUID, 'Purchases', 'TRANSACTION'
            );

            $this->_buildListResponse('transactions/purchases/list', '/transactions/getPageDetails/105');

        } catch (Exception $e) {
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

            if ($srcUID <= 0) throw new Exception('Invalid purchase bill.');

            $this->load->model('transactions_model');
            $src = $this->transactions_model->getTransactionById($srcUID, $orgUID, $this->pageModuleUID);
            if (!$src) throw new Exception('Purchase bill not found.');

            $nextNumber   = $this->transactions_model->getNextTransactionNumber($src->PrefixUID, $orgUID, $this->pageModuleUID);
            $prefixResult = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.PrefixUID' => $src->PrefixUID, 'Prefix.OrgUID' => $orgUID]);
            $prefix       = $prefixResult->Data[0] ?? null;
            if (!$prefix) throw new Exception('Prefix not found.');

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
                'IsForeignCustomer' => NULL,
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

        } catch (Exception $e) {
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

            if ($transUID <= 0) throw new Exception('Invalid purchase bill.');

            $validTransitions = [
                'Draft'     => ['Received'],
                'Received'  => ['Paid', 'Cancelled'],
                'Paid'      => [],
                'Cancelled' => [],
            ];

            $this->load->model('transactions_model');
            $existing = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$existing) throw new Exception('Purchase bill not found.');

            $current = $existing->DocStatus;
            if (!in_array($newStatus, $validTransitions[$current] ?? [])) {
                throw new Exception("Cannot change status from {$current} to {$newStatus}.");
            }

            $this->dbwrite_model->startTransaction();
            $resp = $this->dbwrite_model->updateData(
                'Transaction', 'TransactionsTbl',
                ['DocStatus' => $newStatus, 'UpdatedBy' => $userUID, 'UpdatedOn' => date('Y-m-d H:i:s')],
                ['TransUID' => $transUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );
            if ($resp->Error) throw new Exception($resp->Message);
            $this->dbwrite_model->commitTransaction();

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
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'UPDATE_PURCHASE_STATUS', 'Purchase', (int) $transUID, (string) ($existing->UniqueNumber ?? ''),
                ['NewStatus' => $newStatus], 'Updated purchase status #' . $transUID, 'Purchases', 'TRANSACTION'
            );
            $this->EndReturnData->NewStatus = $newStatus;

        } catch (Exception $e) {
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

        } catch (Exception $e) {
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

            $this->pageData['PurchData']        = $purchData;
            $this->pageData['PurchItems']       = $purchItems;
            $this->pageData['PurchAttachments'] = $this->transactions_model->getTransactionAttachments($transUID, $orgUID);

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

        } catch (Exception $e) {
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








    public function recordPurchasePayment() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData = $this->input->post();
            $userUID  = $this->pageData['JwtData']->User->UserUID;
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;

            $transUID       = (int)   getPostValue($PostData, 'TransUID');
            $paymentTypeUID = (int)   getPostValue($PostData, 'PaymentTypeUID');
            $amount         = (float) getPostValue($PostData, 'Amount', 'Array', 0);
            $paymentDate    =         getPostValue($PostData, 'PaymentDate') ?: date('Y-m-d');
            $bankAccountUID = (int)   getPostValue($PostData, 'BankAccountUID') ?: NULL;
            $referenceNo    =         getPostValue($PostData, 'ReferenceNo') ?: NULL;
            $notes          =         getPostValue($PostData, 'Notes') ?: NULL;

            if ($transUID <= 0)       throw new Exception('Invalid transaction.');
            if ($paymentTypeUID <= 0) throw new Exception('Please select a payment type.');
            if ($amount <= 0)         throw new Exception('Amount must be greater than 0.');

            $this->load->model('transactions_model');
            $existing = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$existing) throw new Exception('Purchase not found.');
            if ($existing->DocStatus === 'Draft')                          throw new Exception('Cannot record payment for a Draft purchase.');
            if (in_array($existing->DocStatus, ['Cancelled', 'Rejected'])) throw new Exception('Purchase is cancelled.');

            if (!$this->dbwrite_model->lockTransactionRow($transUID, $orgUID)) {
                throw new Exception('Purchase not found.');
            }
            $alreadyPaid = $this->dbwrite_model->sumTransactionPayments($transUID, $orgUID);
            $pending     = max(0, round((float)$existing->NetAmount - $alreadyPaid, $this->_decimals()));

            if ($amount > $pending + 0.01) {
                throw new Exception('Amount (' . $amount . ') exceeds remaining balance (' . $pending . '). A concurrent payment may have just been recorded.');
            }

            $newTotalPaid = $alreadyPaid + $amount;
            $isFullyPaid  = ($existing->NetAmount > 0 && round((float)$existing->NetAmount - $newTotalPaid, 4) <= 0) ? 1 : 0;
            $excessAmount = max(0, round($newTotalPaid - (float)$existing->NetAmount, 4));
            $newStatus    = $isFullyPaid ? 'Paid' : 'Partial';

            $payTransYear  = (int) date('Y', strtotime($paymentDate));
            $payPrefixData = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID, 'Prefix.ModuleUID' => 111]);
            $payPrefix     = !empty($payPrefixData->Data) ? $payPrefixData->Data[0] : null;
            $payPrefixUID  = $payPrefix ? (int) $payPrefix->PrefixUID : null;
            $paymentNumber = $payPrefixUID ? $this->transactions_model->getNextPaymentNumber($payPrefixUID, $orgUID, $payTransYear) : 0;
            $payUniqueNum  = ($payPrefix && $paymentNumber > 0) ? $this->_buildPaymentUniqueNumber($payPrefix, $paymentDate, $paymentNumber) : null;
            $receiptToken  = $this->transactions_model->_generateReceiptToken();

            $paymentData = [
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
            ];

            $resp = $this->dbwrite_model->insertData('Transaction', 'PaymentsTbl', $paymentData);
            if ($resp->Error) throw new Exception($resp->Message);

            $balanceAmount = max(0, round((float) $existing->NetAmount - $newTotalPaid, $this->_decimals()));
            $ok = $this->dbwrite_model->updateTransIsFullyPaid($transUID, $isFullyPaid, $newTotalPaid, $balanceAmount, $userUID);
            if ($ok === false) throw new Exception('Failed to update purchase balance.');

            $this->dbwrite_model->updateTransDocStatus($transUID, $orgUID, $newStatus, $userUID);

            $this->dbwrite_model->commitTransaction();

            // Save payment file attachments immediately after commit
            $this->_savePaymentAttachments($resp->ID);

            // Debit vendor ledger â€” payment made reduces our payable to them
            try {
                $this->load->library('accountledger');
                $this->accountledger->applyLedgerEntry($existing->PartyUID, 'Vendor', $amount, 'Debit', $transUID);
                $this->accountledger->postPaymentJournal(
                    'made', $transUID, $paymentDate, $payTransYear,
                    $amount, $existing->PartyUID, 'Vendor', $userUID
                );
            } catch (Exception $ledgerEx) {
                log_message('error', 'Ledger debit failed after purchase payment: ' . $ledgerEx->getMessage());
            }

            $this->_recalcVendorBalance($orgUID, $existing->PartyUID, $userUID);

            $this->_writeBankLedgerEntry(
                $orgUID, $bankAccountUID, 'DR', $amount,
                'Purchase', $transUID, 111,
                $referenceNo, 'Payment made to vendor â€” ' . ($payUniqueNum ?? $existing->UniqueNumber ?? '#' . $transUID),
                $paymentDate, $userUID
            );

            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Message    = 'Payment of ' . $amount . ' recorded successfully.';
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'RECORD_PURCHASE_PAYMENT', 'Purchase', (int) $transUID, (string) ($existing->UniqueNumber ?? ''),
                ['Amount' => $amount], 'Recorded payment for purchase ' . ($existing->UniqueNumber ?? ''), 'Purchases', 'PAYMENT'
            );
            $this->EndReturnData->IsFullyPaid = $isFullyPaid;

            $this->_buildPaymentListResponse('transactions/purchases/list', '/transactions/getPageDetails/105');

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
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
            if ($transUID <= 0) throw new Exception('Invalid transaction.');

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

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }



}
