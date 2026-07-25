<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Purchasereturns extends MY_Controller {

    public $pageData = array();
    private $EndReturnData;
    protected $pageModuleUID;

    public function __construct() {
        parent::__construct();
        $this->pageModuleUID = 108;
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
                'datePrefKey'  => 'purchasereturns',
                'tabSlugMap'   => ['all' => 'All', 'pending' => 'PRPending', 'settled' => 'Paid', 'cancelled' => 'Cancelled', 'drafts' => 'Draft'],
                'listViewPath' => 'transactions/purchasereturns/list',
                'paginationUrl'=> '/transactions/getPageDetails/108',
            ]);
            $this->load->view('transactions/purchasereturns/view', $this->pageData);
        } catch (Exception $e) {
            redirect('dashboard', 'refresh');
        }
    }

    public function addPurchaseReturn() {
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

            $headerData = $this->_buildTransHeader(
                [
                    'TransType'       => 'Purchase Return',
                    'PartyType'       => 'S',
                    'PartyUID'        => $vendorUID,
                    'DocTypePostKey'  => 'purchaseType',
                    'DispatchPostKey' => 'dispatchTo',
                    'InitialStatus'   => 'Approved',
                    'hasPaidAmount'   => false,
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
                    'PartyType'          => 'S',
                    'PartyUID'           => $vendorUID,
                    'ValidityDatePostKey' => 'returnDate',
                ],
                $amounts, $PostData, $transUID
            );
            $this->dbwrite_model->insertData('Transaction', 'TransDetailTbl', $detailData);

            $this->_insertTransItems($transUID, $financialYear, $orgUID, $userUID, $items);

            if (!$isDraft) {
                $this->dbwrite_model->saveStockMovements($transUID, $this->pageModuleUID, $orgUID, $userUID, $items);
                $this->_syncProductCacheFromItems($items);
            }

            $this->dbwrite_model->commitTransaction();

            if (!$isDraft) {
                try {
                    $this->load->library('accountledger');
                    $this->accountledger->postPurchaseReturnJournal(
                        $transUID, $transDate, $uniqueNumber, $financialYear,
                        $netAmount, $subTotal, $cgstAmount, $sgstAmount, $igstAmount,
                        $vendorUID, $userUID
                    );
                } catch (Exception $ledgerEx) {
                    log_message('error', 'Ledger update failed after purchase return creation: ' . $ledgerEx->getMessage());
                }
            }

            $this->_saveAttachments($transUID);
            $this->_touchVendorCache($vendorUID);
            if (!$isDraft) {
                $this->_recalcVendorBalance($orgUID, $vendorUID, $userUID);
            }

            $hasPayment    = false;
            $balanceAmount = $netAmount;

            if (!$isDraft && (int) getPostValue($PostData, 'RecordPayment') === 1) {
                $payResult = $this->_savePaymentRecord($transUID, $orgUID, $userUID, 'S', $vendorUID, $netAmount, $PostData, 'In', $transDate);
                if ($payResult['totalPaid'] > 0) {
                    $hasPayment    = true;
                    $isFullyPaid   = ($netAmount > 0 && round($netAmount - $payResult['totalPaid'], 4) <= 0) ? 1 : 0;
                    $balanceAmount = max(0, round($netAmount - $payResult['totalPaid'], $this->_decimals()));
                    $this->_updateTransactionBalance($transUID, $netAmount, $payResult['totalPaid'], $userUID);
                    $newStatus = $isFullyPaid ? 'Paid' : 'Partial';
                    $this->dbwrite_model->updateTransDocStatus($transUID, $orgUID, $newStatus, $userUID);
                }
                if (!empty($payResult['firstPaymentUID'])) {
                    $this->_savePaymentAttachments($payResult['firstPaymentUID']);
                }
            }

            if (!$isDraft) {
                $dnAmount = $hasPayment ? $balanceAmount : $netAmount;
                if ($dnAmount > 0) {
                    $this->load->library('vendorbalance');
                    $this->vendorbalance->createPurchaseReturnDebitNote(
                        $orgUID, $vendorUID, $transUID, $uniqueNumber, $dnAmount, $userUID, $transDate
                    );
                }
            }

            $this->EndReturnData->Error    = FALSE;
            $this->EndReturnData->Message  = 'Purchase Return created successfully.';
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'ADD_PURCHASE_RETURN', 'PurchaseReturn', (int) $transUID, (string) ($uniqueNumber ?? ''),
                [], 'Created purchase return ' . ($uniqueNumber ?? ''), 'PurchaseReturns', 'TRANSACTION', 'SUCCESS', '', 'WEB', [], [], $PostData
            );
            $this->EndReturnData->TransUID = $transUID;
        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function updatePurchaseReturn() {
        $this->EndReturnData = new stdClass();
        try {
            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData = $this->input->post();
            $userUID  = $this->pageData['JwtData']->User->UserUID;
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;
            $transUID = (int) getPostValue($PostData, 'TransUID');
            if ($transUID <= 0) throw new Exception('Purchase Return ID is required.');

            $itemsJson = $this->_validateTransForm($PostData);
            $amounts   = $this->_extractTransAmounts($PostData, $itemsJson);

            $amounts['moduleUID'] = $this->pageModuleUID;
            $vendorUID   = (int) getPostValue($PostData, 'vendorSearch');
            $prefixUID   = $amounts['prefixUID'];
            $transNumber = $amounts['transNumber'];
            $isDraft     = $amounts['isDraft'];
            $items       = $amounts['items'];
            $returnDate  = getPostValue($PostData, 'returnDate');

            $cfg = [
                'TransType'       => 'Purchase Return',
                'PartyType'       => 'S',
                'PartyUID'        => $vendorUID,
                'DocTypePostKey'  => 'purchaseType',
                'DispatchPostKey' => 'dispatchTo',
                'InitialStatus'   => 'Approved',
            ];

            $this->load->model('transactions_model');
            $existing = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$existing) throw new Exception('Purchase Return not found.');

            $uniqueNumber = NULL;
            if ($existing->DocStatus === 'Draft' && !$isDraft) {
                if ($prefixUID <= 0) throw new Exception('Please select a prefix to finalise this return.');
                if ($transNumber <= 0) throw new Exception('Transaction number must be greater than 0.');
                $prefixData = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.PrefixUID' => $prefixUID, 'Prefix.OrgUID' => $orgUID]);
                if (empty($prefixData->Data)) throw new Exception('Invalid prefix selected.');
                $prefix   = $prefixData->Data[0];
                $dupCheck = $this->transactions_model->getTransactionByPrefixAndNumber($prefixUID, $transNumber, $orgUID, $this->pageModuleUID);
                if ($dupCheck) {
                    $nextSuggested = $this->transactions_model->getNextTransactionNumber($prefixUID, $orgUID, $this->pageModuleUID);
                    throw new Exception("Transaction number {$transNumber} already exists. Next available: {$nextSuggested}.");
                }
                [$uniqueNumber] = $this->buildUniqueNumber($prefix, $transNumber, $amounts['transDate']);
            }

            $isInterState = $amounts['igstAmount'] > 0 ? 1 : ($amounts['cgstAmount'] > 0 || $amounts['sgstAmount'] > 0 ? 0 : NULL);
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
                'IsForeignCustomer' => NULL,
            ];

            $wasNonDraft = ($existing->DocStatus !== 'Draft');
            if ($wasNonDraft) {
                $this->dbwrite_model->reverseStockMovements($transUID, $orgUID, $userUID);
                $this->_syncProductCacheByTransUID($transUID);
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
                $this->dbwrite_model->insertData('Transaction', 'TransDetailTbl', array_merge($commonDetail, ['FinancialYear' => $amounts['financialYear'], 'TransUID' => $newTransUID]));
                $this->dbwrite_model->updateData('Transaction', 'TransProductsTbl', ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID], ['TransUID' => $transUID, 'IsDeleted' => 0]);
                $this->_insertTransItems($newTransUID, $amounts['financialYear'], $orgUID, $userUID, $items);
                if (!$isDraft) {
                    $this->dbwrite_model->saveStockMovements($newTransUID, $this->pageModuleUID, $orgUID, $userUID, $items);
                    $this->_syncProductCacheFromItems($items);
                }
                $this->dbwrite_model->deleteInTransaction('Transaction', 'TransactionsTbl', ['TransUID' => $transUID]);
                $this->dbwrite_model->deleteInTransaction('Transaction', 'TransDetailTbl',  ['TransUID' => $transUID]);
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
                    $this->dbwrite_model->saveStockMovements($transUID, $this->pageModuleUID, $orgUID, $userUID, $items);
                    $this->_syncProductCacheFromItems($items);
                }
            }

            $activeTransUID = $newTransUID ?? $transUID;
            $this->_saveTransCharges($activeTransUID, $orgUID, $userUID, $PostData);
            $this->dbwrite_model->commitTransaction();
            $this->_saveAttachments($activeTransUID);
            $this->_softDeleteAttachments($this->input->post('RemovedAttachIDs') ?? '');
            $this->_touchVendorCache($vendorUID);
            $this->transactions_model->generateAndStorePdf($activeTransUID, $orgUID, $this->pageModuleUID);
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Purchase Return updated successfully.';
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'UPDATE_PURCHASE_RETURN', 'PurchaseReturn', (int) $activeTransUID, (string) ($uniqueNumber ?? $existing->UniqueNumber ?? ''),
                [], 'Updated purchase return ' . ($uniqueNumber ?? $existing->UniqueNumber ?? ''), 'PurchaseReturns', 'TRANSACTION', 'SUCCESS', '', 'WEB', [], [], $PostData
            );
        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function deletePurchaseReturn() {
        $this->EndReturnData = new stdClass();
        try {
            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();
            $PostData = $this->input->post();
            $userUID  = $this->pageData['JwtData']->User->UserUID;
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;
            $transUID = (int) getPostValue($PostData, 'TransUID');
            if ($transUID <= 0) throw new Exception('Purchase Return ID is required.');
            $this->load->model('transactions_model');
            $existing = $this->transactions_model->getTransactionPageList(1, 0, $this->pageModuleUID, ['TransUID' => $transUID, 'OrgUID' => $orgUID]);
            if (empty($existing)) throw new Exception('Purchase Return not found.');

            $this->dbwrite_model->reverseStockMovements($transUID, $orgUID, $userUID);
            $this->_syncProductCacheByTransUID($transUID);

            $now = time();
            $this->dbwrite_model->updateData('Transaction', 'TransProductsTbl', ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID], ['TransUID' => $transUID, 'IsDeleted' => 0]);
            $deleteData = $this->globalservice->baseDeleteArrayDetails();
            $deleteData['IsActive'] = 0;
            $deleteResp = $this->dbwrite_model->updateData('Transaction', 'TransactionsTbl', $deleteData, ['TransUID' => $transUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]);
            if ($deleteResp->Error) throw new Exception($deleteResp->Message);
            $this->dbwrite_model->commitTransaction();

            // Reverse journal entry for the purchase return (non-fatal)
            try {
                $this->load->library('accountledger');
                $this->accountledger->reverseJournal('PurchaseReturn', $transUID, $userUID);
            } catch (Exception $ledgerEx) {
                log_message('error', 'Ledger reverse failed after purchase return delete #' . $transUID . ': ' . $ledgerEx->getMessage());
            }

            if (!empty($existing->PartyUID)) {
                $this->_recalcVendorBalance($orgUID, (int)$existing->PartyUID, $userUID);
            }

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Purchase Return deleted successfully.';
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'DELETE_PURCHASE_RETURN', 'PurchaseReturn', (int) $transUID, '',
                [], 'Deleted purchase return #' . $transUID, 'PurchaseReturns', 'TRANSACTION'
            );

            $this->_buildListResponse('transactions/purchasereturns/list', '/transactions/getPageDetails/108');
        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function duplicatePurchaseReturn() {
        $this->EndReturnData = new stdClass();
        try {
            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();
            $PostData = $this->input->post();
            $srcUID   = (int) getPostValue($PostData, 'TransUID');
            $userUID  = $this->pageData['JwtData']->User->UserUID;
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;
            if ($srcUID <= 0) throw new Exception('Invalid purchase return.');
            $this->load->model('transactions_model');
            $src = $this->transactions_model->getTransactionById($srcUID, $orgUID, $this->pageModuleUID);
            if (!$src) throw new Exception('Purchase Return not found.');

            $nextNumber   = $this->transactions_model->getNextTransactionNumber($src->PrefixUID, $orgUID, $this->pageModuleUID);
            $prefixResult = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.PrefixUID' => $src->PrefixUID, 'Prefix.OrgUID' => $orgUID]);
            $prefix       = $prefixResult->Data[0] ?? null;
            if (!$prefix) throw new Exception('Prefix not found.');

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
                'TransType'         => 'Purchase Return',
                'TransNumber'       => $nextNumber,
                'PartyType'         => $src->PartyType,
                'PartyUID'          => $src->PartyUID,
                'TransDate'         => $today,
                'TransYear'         => (int) date('Y'),
                'DocType'     => NULL,
                'DispatchFrom'      => NULL,
                'TotalQuantity'     => $totalQty,
                'TotalItems'        => count($items),
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
            $this->EndReturnData->Message  = 'Purchase Return duplicated as ' . $uniqueNumber . '.';
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'DUPLICATE_PURCHASE_RETURN', 'PurchaseReturn', (int) $newTransUID, (string) $uniqueNumber,
                [], 'Duplicated purchase return as ' . $uniqueNumber, 'PurchaseReturns', 'TRANSACTION'
            );
            $this->EndReturnData->TransUID = $newTransUID;
            $this->EndReturnData->EditURL  = '/purchasereturns/edit/' . $newTransUID;
        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function updatePurchaseReturnStatus() {
        $this->EndReturnData = new stdClass();
        try {
            $this->load->model('dbwrite_model');
            $PostData  = $this->input->post();
            $transUID  = (int) getPostValue($PostData, 'TransUID');
            $newStatus = trim(getPostValue($PostData, 'Status'));
            $userUID   = $this->pageData['JwtData']->User->UserUID;
            $orgUID    = $this->pageData['JwtData']->Org->OrgUID;
            if ($transUID <= 0) throw new Exception('Invalid purchase return.');

            $validTransitions = [
                'Draft'     => ['Approved', 'Cancelled'],
                'Approved'  => ['Cancelled'],
                'Partial'   => ['Cancelled'],
                'Paid'      => ['Cancelled'],
                'Cancelled' => [],
                'Rejected'  => [],
            ];

            $this->load->model('transactions_model');
            $existing = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$existing) throw new Exception('Purchase Return not found.');
            $current = $existing->DocStatus;
            if (!in_array($newStatus, $validTransitions[$current] ?? [])) throw new Exception("Cannot change status from {$current} to {$newStatus}.");

            $this->dbwrite_model->startTransaction();

            // â”€â”€ Pre-cancel dependency checks (before any DB write) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            $hasCashRefunds = false;
            $cancelAction   = '';
            $totalRefunded  = 0;

            if ($newStatus === 'Cancelled') {
                // Check for cash/bank refunds received from vendor
                $totalRefunded  = $this->transactions_model->getPRTotalRefunded($transUID);
                $hasCashRefunds = $totalRefunded > 0;
                if ($hasCashRefunds) {
                    $cancelAction = trim($this->input->post('CancelPaymentAction') ?? '');
                    if (!in_array($cancelAction, ['recover', 'writeoff'])) {
                        // No valid action â€” tell frontend to show action dialog
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

                // Handle refund payments per chosen action
                if ($hasCashRefunds) {
                    $wdb = $this->dbwrite_model->getWriteDb();
                    $wdb->db_debug = FALSE;
                    if ($cancelAction === 'writeoff') {
                        // Keep the vendor's refund as a business gain â€” mark payments written off
                        $wdb->where(['TransUID' => $transUID, 'IsDeleted' => 0])
                            ->where('PaymentTypeUID !=', 0)
                            ->update('Transaction.PaymentsTbl', ['IsCancelled' => 1, 'UpdatedBy' => $userUID]);
                    } else {
                        // Recover â€” void the refund payments; we owe vendor back, tracked via VendorCreditNote
                        $wdb->where(['TransUID' => $transUID, 'IsDeleted' => 0])
                            ->where('PaymentTypeUID !=', 0)
                            ->update('Transaction.PaymentsTbl', ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID]);
                    }
                }

                // Reverse stock that went out when PR was approved
                $this->dbwrite_model->reverseStockMovements($transUID, $orgUID, $userUID);
                $this->_syncProductCacheByTransUID($transUID);

                // Reset PR payment counters
                $this->dbwrite_model->updateTransIsFullyPaid($transUID, 0, 0, 0, $userUID);

                // Recover: create a vendor credit note so we track that we owe vendor back
                if ($cancelAction === 'recover' && $hasCashRefunds) {
                    $this->load->library('vendorbalance');
                    $this->vendorbalance->createVendorCreditNote(
                        $orgUID, (int)$existing->PartyUID, $transUID,
                        $existing->UniqueNumber ?? '', $totalRefunded, $userUID,
                        $this->dbwrite_model->getWriteDb()
                    );
                }

                // Cancel any pending VendorDebitNote that was auto-created when this PR had no cash refund
                $wdb = $this->dbwrite_model->getWriteDb();
                $wdb->db_debug = FALSE;
                $wdb->where([
                    'SourceTransUID'  => $transUID,
                    'SourceModuleUID' => 108,
                    'Status'          => 'Pending',
                    'IsCancelled'     => 0,
                    'IsDeleted'       => 0,
                ])->update('Transaction.TransDebitNoteTbl', [
                    'IsCancelled' => 1,
                    'UpdatedBy'   => $userUID,
                ]);
            }

            $this->dbwrite_model->commitTransaction();

            $docNum = $existing->UniqueNumber ?? '';
            $prefix = $docNum ? "{$docNum} " : '';
            if ($newStatus === 'Cancelled') {
                $msg = "Purchase return {$prefix}cancelled successfully.";
            } elseif ($newStatus === 'Approved') {
                $msg = "Purchase return {$prefix}approved.";
            } else {
                $msg = 'Status updated.';
            }

            $this->EndReturnData->Error     = FALSE;
            $this->EndReturnData->Message   = $msg;
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'UPDATE_PR_STATUS', 'PurchaseReturn', (int) $transUID, (string) ($existing->UniqueNumber ?? ''),
                ['NewStatus' => $newStatus], 'Updated purchase return status to ' . $newStatus, 'PurchaseReturns', 'TRANSACTION'
            );
            $this->EndReturnData->NewStatus = $newStatus;
        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function getPRCancelDependencies() {
        $this->EndReturnData = new stdClass();
        try {
            $transUID = (int) $this->input->post('TransUID');
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;
            if ($transUID <= 0) throw new Exception('Invalid purchase return.');

            $this->load->model('transactions_model');
            $existing = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$existing) throw new Exception('Purchase Return not found.');

            $totalRefunded = $this->transactions_model->getPRTotalRefunded($transUID);

            $this->EndReturnData->Error        = FALSE;
            $this->EndReturnData->HasRefunds   = $totalRefunded > 0;
            $this->EndReturnData->RefundAmount = $totalRefunded;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function getVendorPurchases() {
        $this->EndReturnData = new stdClass();
        try {
            $vendorUID = (int) $this->input->post('VendorUID');
            $orgUID    = $this->pageData['JwtData']->Org->OrgUID;
            if ($vendorUID <= 0) throw new Exception('Invalid vendor.');

            $this->load->model('transactions_model');
            $this->EndReturnData->Error     = false;
            $this->EndReturnData->Purchases = $this->transactions_model->getVendorPurchasesWithReturnableItems($vendorUID, $orgUID);
        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function getPurchaseItems() {
        $this->EndReturnData = new stdClass();
        try {
            $transUID = (int) $this->input->post('TransUID');
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;
            if ($transUID <= 0) throw new Exception('Invalid purchase.');

            $this->load->model('transactions_model');
            $header = $this->transactions_model->getTransactionById($transUID, $orgUID, 105);
            if (!$header) throw new Exception('Purchase not found.');
            $items  = $this->transactions_model->getTransactionItems($transUID, $orgUID);

            // Annotate each item with how much quantity has already been returned
            if (!empty($items)) {
                $transProdUIDs = array_map(fn($i) => (int)$i->TransProdUID, $items);
                $returnedMap   = $this->transactions_model->getReturnedQtyMapForItems($transProdUIDs, $orgUID);
                foreach ($items as $item) {
                    $item->ReturnedQty  = $returnedMap[(int)$item->TransProdUID] ?? 0;
                    $item->RemainingQty = max(0, (float)$item->Quantity - $item->ReturnedQty);
                }
                // Filter out fully-returned items
                $items = array_values(array_filter($items, fn($i) => $i->RemainingQty > 0));
            }

            $this->EndReturnData->Error  = false;
            $this->EndReturnData->Header = $header;
            $this->EndReturnData->Items  = $items;
        } catch (Exception $e) {
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

            $this->pageData['PaymentTypes'] = $this->transactions_model->getPaymentTypesList();
            $this->pageData['BankAccounts'] = $this->transactions_model->getOrgBankAccounts($orgUID);

            $this->_getDispatchAddresses($orgUID);
            $this->_loadUpstashConfig();

            $this->pageData['AdditionalCharges']  = $this->_getAdditionalChargesForOrg((int)$orgUID, true);
            $this->pageData['TaxList']            = $this->_getTaxList();
            $this->pageData['TransactionCharges'] = [];
            $this->pageData['IsEditMode']         = false;

            $this->load->view('transactions/purchasereturns/forms/form', $this->pageData);
        } catch (Exception $e) {
            redirect('purchasereturns', 'refresh');
        }
    }

    public function edit($transUID = 0) {
        try {
            $transUID = (int) $transUID;
            if ($transUID <= 0) redirect('purchasereturns');

            $orgUID = $this->pageData['JwtData']->Org->OrgUID;
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;

            $this->load->model('transactions_model');
            $transData  = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$transData) redirect('purchasereturns');
            $transItems = $this->transactions_model->getTransactionItems($transUID, $orgUID);
            $this->pageData['PRData']    = $transData;
            $this->pageData['PRItems']   = $transItems;

            $prefixResult                    = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID, 'Prefix.ModuleUID' => $this->pageModuleUID]);
            $this->pageData['PrefixData']    = $prefixResult->Data ?? [];
            $nextNumberMap = [];
            foreach ($this->pageData['PrefixData'] as $pd) {
                $nextNumberMap[(int)$pd->PrefixUID] = $this->transactions_model->getNextTransactionNumber($pd->PrefixUID, $orgUID, $this->pageModuleUID);
            }
            $this->pageData['NextNumberMap'] = $nextNumberMap;

            $this->pageData['AdditionalCharges']  = $this->_getAdditionalChargesForOrg((int)$orgUID, true);
            $this->pageData['TransactionCharges'] = $this->transactions_model->getTransactionCharges($transUID, (int)$orgUID);
            $this->pageData['TaxList']            = $this->_getTaxList();
            $this->pageData['IsEditMode']         = true;

            $this->_getDispatchAddresses($orgUID);
            $this->_loadUpstashConfig();

            // Attachments — load server-side to avoid AJAX call on page load
            $this->pageData['PRAttachments'] = $this->transactions_model->getTransactionAttachments($transUID, $orgUID);

            $this->load->view('transactions/purchasereturns/forms/form', $this->pageData);
        } catch (Exception $e) {
            redirect('purchasereturns', 'refresh');
        }
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

            if ($transUID <= 0)       throw new Exception('Invalid transaction.');
            if ($paymentTypeUID <= 0) throw new Exception('Please select a payment type.');
            if ($amount <= 0)         throw new Exception('Amount must be greater than 0.');

            $this->load->model('transactions_model');
            $existing = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$existing) throw new Exception('Purchase Return not found.');
            if ($existing->DocStatus === 'Draft')                          throw new Exception('Cannot record payment for a Draft.');
            if (in_array($existing->DocStatus, ['Cancelled', 'Rejected'])) throw new Exception('Purchase Return is cancelled.');

            $payments    = $this->transactions_model->getTransactionPayments($transUID, $orgUID);
            $alreadyPaid = array_sum(array_column((array) $payments, 'Amount'));
            $pending     = max(0, round((float)$existing->NetAmount - $alreadyPaid, $this->_decimals()));

            if ($amount > $pending + 0.01) {
                throw new Exception('Amount exceeds pending balance (' . number_format($pending, 2) . ').');
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
                    $m  = (int) date('m', strtotime($paymentDate));
                    $yr = (int) date('Y', strtotime($paymentDate));
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
                'PartyType'        => 'S',
                'PartyUID'         => $existing->PartyUID,
                'PaymentTypeUID'   => $paymentTypeUID,
                'Amount'           => $amount,
                'BankAccountUID'   => $bankAccountUID,
                'ReferenceNo'      => $referenceNo,
                'Notes'            => $notes,
                'PaymentSource'    => 'Record',
                'PaymentDirection' => 'In',
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

            $this->dbwrite_model->commitTransaction();

            if (!empty($paymentUID)) {
                $this->_savePaymentAttachments($paymentUID);
            }

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Refund of ' . number_format($amount, 2) . ' recorded successfully.';
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'RECORD_PR_PAYMENT', 'PurchaseReturn', (int) $transUID, (string) ($existing->UniqueNumber ?? ''),
                ['Amount' => $amount], 'Recorded refund of ' . $amount . ' for purchase return ' . ($existing->UniqueNumber ?? ''), 'PurchaseReturns', 'PAYMENT'
            );

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function getPendingPurchases() {
        $this->EndReturnData = new stdClass();
        try {
            $prUID  = (int) $this->input->post('PurchaseReturnUID');
            $orgUID = $this->pageData['JwtData']->Org->OrgUID;
            if ($prUID <= 0) throw new Exception('Invalid purchase return.');

            $this->load->model('transactions_model');
            $pr = $this->transactions_model->getTransactionById($prUID, $orgUID, $this->pageModuleUID);
            if (!$pr) throw new Exception('Purchase Return not found.');

            $vendorUID = (int) $pr->PartyUID;

            $this->EndReturnData->Error     = false;
            $this->EndReturnData->Purchases = $this->transactions_model->getVendorPendingPurchases($vendorUID, $orgUID);
        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function applyDebit() {
        $this->EndReturnData = new stdClass();
        try {
            $this->load->model('dbwrite_model');
            $this->load->model('transactions_model');

            $PostData   = $this->input->post();
            $userUID    = $this->pageData['JwtData']->User->UserUID;
            $orgUID     = $this->pageData['JwtData']->Org->OrgUID;
            $prUID      = (int)   getPostValue($PostData, 'PurchaseReturnUID');
            $purchUID   = (int)   getPostValue($PostData, 'PurchaseUID');
            $amount     = (float) getPostValue($PostData, 'Amount', 'Array', 0);
            $notes      = getPostValue($PostData, 'Notes') ?: NULL;

            if ($prUID <= 0)    throw new Exception('Invalid purchase return.');
            if ($purchUID <= 0) throw new Exception('Please select a purchase.');
            if ($amount <= 0)   throw new Exception('Amount must be greater than 0.');

            $pr = $this->transactions_model->getTransactionById($prUID, $orgUID, $this->pageModuleUID);
            if (!$pr) throw new Exception('Purchase Return not found.');
            if (in_array($pr->DocStatus, ['Draft', 'Cancelled', 'Rejected'])) {
                throw new Exception('Cannot apply debit for this Purchase Return.');
            }

            $prPaid    = (float)($pr->PaidAmount    ?? 0);
            $prBalance = max(0, round((float)$pr->NetAmount - $prPaid, $this->_decimals()));
            if ($prBalance <= 0) throw new Exception('No debit balance available on this Purchase Return.');

            $purchase = $this->transactions_model->getTransactionById($purchUID, $orgUID, 105);
            if (!$purchase) throw new Exception('Purchase not found.');
            if ($purchase->PartyUID != $pr->PartyUID) throw new Exception('Purchase does not belong to the same vendor.');
            if (in_array($purchase->DocStatus, ['Draft', 'Cancelled', 'Paid'])) {
                throw new Exception('This purchase cannot receive a debit adjustment.');
            }

            $purchPaid    = (float)($purchase->PaidAmount    ?? 0);
            $purchBalance = max(0, round((float)$purchase->NetAmount - $purchPaid, $this->_decimals()));
            if ($purchBalance <= 0) throw new Exception('Purchase has no pending balance.');

            $maxAmount = min($prBalance, $purchBalance);
            if ($amount > $maxAmount + 0.01) {
                throw new Exception('Amount exceeds available debit (' . number_format($maxAmount, 2) . ').');
            }
            $amount = min($amount, $maxAmount);

            $this->dbwrite_model->startTransaction();

            $payPrefixData = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID, 'Prefix.ModuleUID' => 111]);
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

            // Record the debit adjustment against the purchase (PaymentTypeUID=0 = debit adjustment)
            $paymentData = [
                'OrgUID'           => $orgUID,
                'PaymentDate'      => $today,
                'PaymentModuleUID' => 111,
                'PrefixUID'        => $payPrefixUID,
                'PaymentNumber'    => $paymentNumber,
                'UniqueNumber'     => $payUniqueNum,
                'ReceiptToken'     => $receiptToken,
                'TransYear'        => $payTransYear,
                'TransUID'         => $purchUID,
                'ModuleUID'        => 105,
                'PartyType'        => 'S',
                'PartyUID'         => $purchase->PartyUID,
                'PaymentTypeUID'   => 0,
                'Amount'           => $amount,
                'BankAccountUID'   => NULL,
                'ReferenceNo'      => $pr->UniqueNumber,
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

            // Update Purchase
            $newPurchPaid    = round($purchPaid + $amount, $this->_decimals());
            $newPurchBalance = max(0, round((float)$purchase->NetAmount - $newPurchPaid, $this->_decimals()));
            $purchFullyPaid  = ($purchase->NetAmount > 0 && $newPurchBalance <= 0) ? 1 : 0;
            $purchStatus     = $purchFullyPaid ? 'Paid' : 'Partial';
            $this->dbwrite_model->updateTransIsFullyPaid($purchUID, $purchFullyPaid, $newPurchPaid, $newPurchBalance, $userUID);
            $this->dbwrite_model->updateTransDocStatus($purchUID, $orgUID, $purchStatus, $userUID);

            // Update Purchase Return
            $newPrPaid    = round($prPaid + $amount, $this->_decimals());
            $newPrBalance = max(0, round((float)$pr->NetAmount - $newPrPaid, $this->_decimals()));
            $prFullyPaid  = ($pr->NetAmount > 0 && $newPrBalance <= 0) ? 1 : 0;
            $prNewStatus  = $prFullyPaid ? 'Paid' : ($newPrPaid > 0 ? 'Partial' : $pr->DocStatus);
            $this->dbwrite_model->updateTransIsFullyPaid($prUID, $prFullyPaid, $newPrPaid, $newPrBalance, $userUID);
            if ($prNewStatus !== $pr->DocStatus) {
                $this->dbwrite_model->updateTransDocStatus($prUID, $orgUID, $prNewStatus, $userUID);
            }

            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Debit of ' . number_format($amount, 2) . ' applied to purchase ' . ($purchase->UniqueNumber ?? '#' . $purchUID) . '.';

        } catch (Exception $e) {
            if (isset($this->dbwrite_model)) $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    private function _touchVendorCache($vendorUID) {
        $this->cachehelper->touchVendor($vendorUID);
    }

}
