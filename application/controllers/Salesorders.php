<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Salesorders extends MY_Controller {

    public $pageData = array();
    protected $EndReturnData;
    protected $pageModuleUID;

    public function __construct() {
        parent::__construct();

        $this->pageModuleUID = 102;
        $this->load->helper('transaction');

    }

    public function index(): void {
        if (!$this->_loadPageTitle($this->pageModuleUID)) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }
        try {
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;
            $orgUID = (int)$this->pageData['JwtData']->Org->OrgUID;
            $this->load->model('organisation_model');
            $orgResult = $this->organisation_model->getOrgInfoCached($orgUID);
            $this->pageData['CommOrgContext'] = $orgResult->Data ?? null;
            $templates = $this->organisation_model->getModuleMessageTemplates($orgUID, $this->pageModuleUID);
            $this->pageData['CommEmailTemplate'] = isset($templates['Email'])
                ? ['Subject' => $templates['Email']->Subject ?? '', 'Body' => $templates['Email']->Body ?? '']
                : null;
            $this->_loadTransactionIndexPage([
                'datePrefKey'       => 'salesorders',
                'tabSlugMap'        => ['all' => 'All', 'pending' => 'Pending', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'draft' => 'Draft'],
                'listViewPath'      => 'transactions/salesorders/list',
                'paginationUrl'     => '/transactions/getPageDetails/102',
                'listViewExtraData' => ['WhatsAppTemplate' => $templates['WhatsApp'] ?? null],
            ]);
            $this->load->view('transactions/salesorders/view', $this->pageData);
        } catch (Exception $e) {
            redirect('dashboard', 'refresh');
        }
    }

    public function addSalesOrder() {

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
            $financialYear = $amounts['financialYear'];
            $transDate     = $amounts['transDate'];

            $customerUID = (int) getPostValue($PostData, 'customerSearch');

            $this->load->model('transactions_model');

            $resolved = $this->_resolveTransPrefix($isDraft, $amounts['prefixUID'], $amounts['transNumber'], $transDate, $orgUID);
            $amounts['moduleUID']    = $this->pageModuleUID;
            $amounts['prefixUID']    = $resolved['prefixUID'];
            $amounts['transNumber']  = $resolved['transNumber'];
            $amounts['uniqueNumber'] = $resolved['uniqueNumber'];

            $headerData = $this->_buildTransHeader(
                [
                    'TransType'       => 'SalesOrder',
                    'PartyType'       => 'C',
                    'PartyUID'        => $customerUID,
                    'DocTypePostKey'  => 'orderType',
                    'DispatchPostKey' => 'dispatchFrom',
                    'InitialStatus'   => 'Pending',
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
                    'ValidityDatePostKey' => 'deliveryDate',
                ],
                $amounts, $PostData, $transUID
            );
            $detailResp = $this->dbwrite_model->insertData('Transaction', 'TransDetailTbl', $detailData);
            if ($detailResp->Error) throw new Exception($detailResp->Message);

            $this->_insertTransItems($transUID, $financialYear, $orgUID, $userUID, $items);

            // Conversion tracking: Quotation -> SalesOrder
            $fromQuotationUID = (int) getPostValue($PostData, 'fromQuotationUID');
            if ($fromQuotationUID > 0 && !$isDraft) {
                $this->dbwrite_model->updateTransDocStatus($fromQuotationUID, $orgUID, 'Converted', $userUID);
                $this->dbwrite_model->insertConversionRecord(
                    $orgUID, $fromQuotationUID, 101, $transUID, $this->pageModuleUID, 'QuotToOrder', $userUID
                );
            }

            $this->_saveAttachments($transUID);

            $this->dbwrite_model->commitTransaction();

            $this->cachehelper->touchCustomer($customerUID);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Sales order created successfully.';
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'ADD_SALES_ORDER', 'SalesOrder', (int) $transUID, (string) ($uniqueNumber ?? ''),
                [], 'Created sales order ' . ($uniqueNumber ?? ''), 'SalesOrders', 'TRANSACTION', 'SUCCESS', '', 'WEB', [], [], $PostData
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

    public function updateSalesOrder() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData = $this->input->post();
            $userUID  = $this->pageData['JwtData']->User->UserUID;
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;

            $transUID = (int) getPostValue($PostData, 'TransUID');
            if ($transUID <= 0) throw new Exception('Sales Order ID is required.');

            $itemsJson = $this->_validateTransForm($PostData);
            $amounts   = $this->_extractTransAmounts($PostData, $itemsJson);

            $amounts['moduleUID'] = $this->pageModuleUID;
            $customerUID  = (int) getPostValue($PostData, 'customerSearch');
            $prefixUID    = $amounts['prefixUID'];
            $transNumber  = $amounts['transNumber'];
            $isDraft      = $amounts['isDraft'];
            $items        = $amounts['items'];
            $deliveryDate = getPostValue($PostData, 'deliveryDate');

            $cfg = [
                'TransType'       => 'SalesOrder',
                'PartyType'       => 'C',
                'PartyUID'        => $customerUID,
                'DocTypePostKey'  => 'orderType',
                'DispatchPostKey' => 'dispatchFrom',
                'InitialStatus'   => 'Pending',
            ];

            $this->load->model('transactions_model');
            $existing = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$existing) throw new Exception('Sales Order not found.');

            $uniqueNumber = NULL;
            if ($existing->DocStatus === 'Draft' && !$isDraft) {
                if ($prefixUID <= 0) throw new Exception('Please select a prefix to finalize this sales order.');
                if ($transNumber <= 0) throw new Exception('Transaction number must be greater than 0.');

                $prefixData = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.PrefixUID' => $prefixUID, 'Prefix.OrgUID' => $orgUID]);
                if (empty($prefixData->Data)) throw new Exception('Invalid prefix selected.');
                $prefix = $prefixData->Data[0];

                $dupCheck = $this->transactions_model->getTransactionByPrefixAndNumber($prefixUID, $transNumber, $orgUID, $this->pageModuleUID);
                if ($dupCheck) {
                    $nextSuggested = $this->transactions_model->getNextTransactionNumber($prefixUID, $orgUID, $this->pageModuleUID);
                    throw new Exception("Transaction number {$transNumber} already exists. Next available: {$nextSuggested}.");
                }

                [$uniqueNumber] = $this->buildUniqueNumber($prefix, $transNumber, $amounts['transDate']);
            }

            $rawIS             = getPostValue($PostData, 'isInterState');
            $isInterState      = ($rawIS !== null && $rawIS !== '') ? (int)$rawIS : null;
            $_cc               = $this->transactions_model->getCustomerCountryCode($customerUID);
            $isForeignCustomer = $_cc !== NULL ? ($_cc === 'IN' ? 0 : 1) : NULL;
            $commonDetail = [
                'ValidityDays'      => NULL,
                'ValidityDate'      => $deliveryDate ?: NULL,
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

                $this->dbwrite_model->updateData(
                    'Transaction', 'TransDetailTbl', $commonDetail,
                    ['FinancialYear' => $amounts['financialYear'], 'TransUID' => $transUID]
                );

                $this->_updateTransItems($transUID, $items, $orgUID, $amounts['financialYear'], $userUID);
            }

            $activeTransUID = $newTransUID ?? $transUID;
            $this->_saveTransCharges($activeTransUID, $orgUID, $userUID, $PostData);
            $this->dbwrite_model->commitTransaction();

            $this->_saveAttachments($activeTransUID);
            $this->_softDeleteAttachments($this->input->post('RemovedAttachIDs') ?? '');
            $this->cachehelper->touchCustomer($customerUID);
            $this->transactions_model->generateAndStorePdf($activeTransUID, $orgUID, $this->pageModuleUID);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Sales order updated successfully.';
            $this->EndReturnData->Token   = $this->_getOrCreateTransToken($activeTransUID);
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'UPDATE_SALES_ORDER', 'SalesOrder', (int) $activeTransUID, (string) ($uniqueNumber ?? $existing->UniqueNumber ?? ''),
                [], 'Updated sales order ' . ($uniqueNumber ?? $existing->UniqueNumber ?? ''), 'SalesOrders', 'TRANSACTION', 'SUCCESS', '', 'WEB', [], [], $PostData
            );

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function deleteSalesOrder() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData = $this->input->post();
            $userUID  = $this->pageData['JwtData']->User->UserUID;
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;

            $transUID = (int) getPostValue($PostData, 'TransUID');
            if ($transUID <= 0) throw new Exception('Sales Order ID is required.');

            $this->load->model('transactions_model');
            $existing = $this->transactions_model->getTransactionPageList(1, 0, $this->pageModuleUID, [
                'TransUID' => $transUID,
                'OrgUID'   => $orgUID,
            ]);
            if (empty($existing)) throw new Exception('Sales Order not found.');

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

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->Message        = 'Sales order deleted successfully.';
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'DELETE_SALES_ORDER', 'SalesOrder', (int) $transUID, '',
                [], 'Deleted sales order #' . $transUID, 'SalesOrders', 'TRANSACTION'
            );
            $this->_buildListResponse('transactions/salesorders/list', '/transactions/getPageDetails/102');

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function duplicateSalesOrder() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData = $this->input->post();
            $srcUID   = (int) getPostValue($PostData, 'TransUID');
            $userUID  = $this->pageData['JwtData']->User->UserUID;
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;

            if ($srcUID <= 0) throw new Exception('Invalid sales order.');

            $this->load->model('transactions_model');
            $src = $this->transactions_model->getTransactionById($srcUID, $orgUID, $this->pageModuleUID);
            if (!$src) throw new Exception('Sales Order not found.');

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
                'TransType'         => 'SalesOrder',
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
                'Reference'         => $src->Reference    ?? NULL,
                'Notes'             => $src->Notes        ?? NULL,
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
                    'OrgUID'          => $orgUID,
                    'FinancialYear'   => (int) date('Y'),
                    'TransUID'        => $newTransUID,
                    'ItemSequence'    => $seq + 1,
                    'ProductUID'      => $item->ProductUID,
                    'ProductName'     => $item->ProductName,
                    'PartNumber'      => $item->PartNumber,
                    'CategoryUID'     => $item->CategoryUID,
                    'StorageUID'      => $item->StorageUID,
                    'Quantity'        => $item->Quantity,
                    'PrimaryUnitName' => $item->PrimaryUnitName,
                    'TaxDetailsUID'   => $item->TaxDetailsUID,
                    'TaxPercentage'   => $item->TaxPercentage,
                    'CGST'            => $item->CGST,
                    'SGST'            => $item->SGST,
                    'IGST'            => $item->IGST,
                    'DiscountTypeUID' => $item->DiscountTypeUID,
                    'Discount'        => $item->Discount,
                    'UnitPrice'       => $item->UnitPrice,
                    'SellingPrice'    => $item->SellingPrice,
                    'TaxableAmount'   => $item->TaxableAmount,
                    'CgstAmount'      => $item->CgstAmount,
                    'SgstAmount'      => $item->SgstAmount,
                    'IgstAmount'      => $item->IgstAmount,
                    'TaxAmount'       => $item->TaxAmount,
                    'DiscountAmount'  => $item->DiscountAmount,
                    'NetAmount'       => $item->NetAmount,
                    'QuantityConverted' => 0,
                    'IsActive'        => 1,
                    'IsDeleted'       => 0,
                    'CreatedBy'       => $userUID,
                    'UpdatedBy'       => $userUID,
                ];
                $this->dbwrite_model->insertData('Transaction', 'TransProductsTbl', $itemRow);
            }

            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error    = FALSE;
            $this->EndReturnData->Message  = 'Sales order duplicated as ' . $uniqueNumber . '.';
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'DUPLICATE_SALES_ORDER', 'SalesOrder', (int) $newTransUID, (string) $uniqueNumber,
                [], 'Duplicated sales order ' . $uniqueNumber, 'SalesOrders', 'TRANSACTION'
            );
            $this->EndReturnData->TransUID = $newTransUID;
            $this->EndReturnData->EditURL  = '/salesorders/edit/' . $newTransUID;

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function convertSalesOrderToInvoice() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');
            $PostData = $this->input->post();
            $transUID = (int) getPostValue($PostData, 'TransUID');
            $userUID  = $this->pageData['JwtData']->User->UserUID;
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;

            if ($transUID <= 0) throw new Exception('Invalid sales order.');

            $this->load->model('transactions_model');
            $existing = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$existing) throw new Exception('Sales Order not found.');
            if ($existing->DocStatus !== 'Pending') throw new Exception('Only Pending orders can be converted to an Invoice.');

            $this->dbwrite_model->startTransaction();
            $resp = $this->dbwrite_model->updateData(
                'Transaction', 'TransactionsTbl',
                ['DocStatus' => 'Converted', 'UpdatedBy' => $userUID, 'UpdatedOn' => date('Y-m-d H:i:s')],
                ['TransUID' => $transUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );
            if ($resp->Error) throw new Exception($resp->Message);
            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error       = FALSE;
            $this->EndReturnData->Message     = 'Sales order marked as converted.';
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'CONVERT_SO_TO_INVOICE', 'SalesOrder', (int) $transUID, (string) ($existing->UniqueNumber ?? ''),
                [], 'Converted sales order ' . ($existing->UniqueNumber ?? '') . ' to invoice', 'SalesOrders', 'TRANSACTION'
            );
            $this->EndReturnData->RedirectURL = '/invoices/create?fromSalesOrder=' . $transUID;

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function convertSalesOrderToDeliveryChallan() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');
            $PostData = $this->input->post();
            $transUID = (int) getPostValue($PostData, 'TransUID');
            $userUID  = $this->pageData['JwtData']->User->UserUID;
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;

            if ($transUID <= 0) throw new Exception('Invalid sales order.');

            $this->load->model('transactions_model');
            $existing = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$existing) throw new Exception('Sales Order not found.');
            if ($existing->DocStatus !== 'Pending') throw new Exception('Only Pending orders can be converted to a Delivery Challan.');

            $this->dbwrite_model->startTransaction();
            $resp = $this->dbwrite_model->updateData(
                'Transaction', 'TransactionsTbl',
                ['DocStatus' => 'Converted', 'UpdatedBy' => $userUID, 'UpdatedOn' => date('Y-m-d H:i:s')],
                ['TransUID' => $transUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );
            if ($resp->Error) throw new Exception($resp->Message);
            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error       = FALSE;
            $this->EndReturnData->Message     = 'Sales order marked as converted.';
            $this->EndReturnData->RedirectURL = '/deliverychallan/create?fromSalesOrder=' . $transUID;

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function updateSalesOrderStatus() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');
            $PostData  = $this->input->post();
            $transUID  = (int) getPostValue($PostData, 'TransUID');
            $newStatus = trim(getPostValue($PostData, 'Status'));
            $userUID   = $this->pageData['JwtData']->User->UserUID;
            $orgUID    = $this->pageData['JwtData']->Org->OrgUID;

            if ($transUID <= 0) throw new Exception('Invalid sales order.');

            $validTransitions = [
                'Draft'     => ['Pending'],
                'Pending'   => ['Cancelled'],
                'Cancelled' => [],
                'Converted' => [],
            ];

            $this->load->model('transactions_model');
            $existing = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$existing) throw new Exception('Sales Order not found.');

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

            $this->EndReturnData->Error           = FALSE;
            $this->EndReturnData->Message         = 'Status updated.';
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'UPDATE_SO_STATUS', 'SalesOrder', (int) $transUID, (string) ($existing->UniqueNumber ?? ''),
                ['NewStatus' => $newStatus], 'Updated sales order status #' . $transUID, 'SalesOrders', 'TRANSACTION'
            );
            $this->EndReturnData->NewStatus       = $newStatus;
            $this->_buildListResponse('transactions/salesorders/list', '/transactions/getPageDetails/102');

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

            $prefixResult                         = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID, 'Prefix.ModuleUID' => $this->pageModuleUID]);
            $this->pageData['PrefixData']         = $prefixResult->Data ?? [];

            $nextNumberMap = [];
            foreach ($this->pageData['PrefixData'] as $pd) {
                $nextNumberMap[(int)$pd->PrefixUID] = $this->transactions_model->getNextTransactionNumber(
                    $pd->PrefixUID, $orgUID, $this->pageModuleUID
                );
            }
            $this->pageData['NextNumberMap'] = $nextNumberMap;

            // Pre-fill from quotation if converting
            $fromQuotationUID = (int) $this->input->get('fromQuotation');
            $this->pageData['FromQuotationUID'] = $fromQuotationUID;
            $this->pageData['QuotationData']    = null;
            $this->pageData['QuotationItems']   = [];
            if ($fromQuotationUID > 0) {
                $quotData        = $this->transactions_model->getTransactionById($fromQuotationUID, $orgUID, 101);
                $quotItems       = $quotData ? $this->transactions_model->getTransactionItems($fromQuotationUID, $orgUID) : [];
                $quotAttachments = $this->transactions_model->getTransactionAttachments($fromQuotationUID, $orgUID);
                $cdnUrl = rtrim(getenv('FILE_UPLOAD') == 'amazonaws' ? getenv('CDN_URL') : getenv('CFLARE_R2_CDN'), '/');
                foreach ($quotAttachments as &$a) {
                    $a->Url = $cdnUrl . '/' . ltrim($a->FilePath ?? '', '/');
                }
                unset($a);
                $this->pageData['QuotationData']        = $quotData;
                $this->pageData['QuotationItems']       = $quotItems;
                $this->pageData['QuotationAttachments'] = $quotAttachments;
            }

            $this->_getDispatchAddresses($orgUID);

            $this->pageData['AdditionalCharges']  = $this->_getAdditionalChargesForOrg((int)$orgUID, true);
            $this->pageData['TaxList']            = $this->_getTaxList();
            $this->pageData['TransactionCharges'] = [];
            $this->pageData['IsEditMode']         = false;

            $this->pageData['fltStorageData'] = [];
            if (!empty($this->pageData['JwtData']->GenSettings->EnableStorage)) {
                $this->load->model('storage_model');
                $this->pageData['fltStorageData'] = $this->storage_model->getStorageDetails([]) ?? [];
            }

            $this->load->view('transactions/salesorders/forms/form', $this->pageData);

        } catch (Exception $e) {
            redirect('salesorders', 'refresh');
        }

    }

    public function edit($transUID = 0) {

        try {

            $transUID = (int) $transUID;
            if ($transUID <= 0) redirect('salesorders', 'refresh');

            $orgUID = $this->pageData['JwtData']->Org->OrgUID;
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;

            $this->load->model('transactions_model');

            $soData = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$soData) redirect('salesorders', 'refresh');

            $soItems = $this->transactions_model->getTransactionItems($transUID, $orgUID);

            $this->load->model('customers_model');
            $custAddr = $this->customers_model->getCustomerAddress(['CustAddress.CustomerUID' => $soData->PartyUID, 'CustAddress.OrgUID' => $orgUID]);
            $shipping = current(array_filter($custAddr, fn($a) => $a->AddressType === 'Shipping'));
            $billing  = current(array_filter($custAddr, fn($a) => $a->AddressType === 'Billing'));
            $this->pageData['CustAddr'] = $shipping ?: ($billing ?: ($custAddr[0] ?? null));

            $this->pageData['SOData']  = $soData;
            $this->pageData['SOItems'] = $soItems;

            $prefixResult                        = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID, 'Prefix.ModuleUID' => $this->pageModuleUID]);
            $this->pageData['PrefixData']        = $prefixResult->Data ?? [];

            $nextNumberMap = [];
            foreach ($this->pageData['PrefixData'] as $pd) {
                $nextNumberMap[(int)$pd->PrefixUID] = $this->transactions_model->getNextTransactionNumber(
                    $pd->PrefixUID, $orgUID, $this->pageModuleUID
                );
            }
            $this->pageData['NextNumberMap'] = $nextNumberMap;

            $this->load->model('organisation_model');
            $dispatchAddrResult                = $this->organisation_model->getOrgDispatchAddress($orgUID);
            $this->pageData['DispatchAddress'] = $dispatchAddrResult->Data ?? NULL;

            $this->pageData['AdditionalCharges']  = $this->_getAdditionalChargesForOrg((int)$orgUID, true);
            $this->pageData['TransactionCharges'] = $this->transactions_model->getTransactionCharges($transUID, (int)$orgUID);
            $this->pageData['TaxList']            = $this->_getTaxList();
            $this->pageData['IsEditMode']         = true;

            $this->pageData['fltStorageData'] = [];
            if (!empty($this->pageData['JwtData']->GenSettings->EnableStorage)) {
                $this->load->model('storage_model');
                $this->pageData['fltStorageData'] = $this->storage_model->getStorageDetails([]) ?? [];
            }

            // Attachments — load server-side to avoid AJAX call on page load
            $this->pageData['SOAttachments'] = $this->transactions_model->getTransactionAttachments($transUID, $orgUID);

            $this->load->view('transactions/salesorders/forms/form', $this->pageData);

        } catch (Exception $e) {
            redirect('salesorders', 'refresh');
        }

    }

}