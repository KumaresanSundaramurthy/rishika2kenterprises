<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Invoices extends CI_Controller {

    public $pageData = array();
    private $EndReturnData;
    private $pageModuleUID;

    public function __construct() {
        parent::__construct();

        $this->pageModuleUID = 103;
        $this->load->helper('transaction');

    }

    public function index() {

        try {

            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;

            $GeneralSettings = ($this->redis_cache->get('Redis_UserGenSettings')->Value) ?? new stdClass();
            $limit = $GeneralSettings->RowLimit ?? 10;
            $this->pageData['JwtData']->GenSettings = $GeneralSettings;
            $this->pageData['DiscTypeInfo'] = [];

            $orgUID = $this->pageData['JwtData']->User->OrgUID;

            $this->load->model('transactions_model');
            $allData      = $this->transactions_model->getTransactionPageList($limit, 0, $this->pageModuleUID, [], 0);
            $allDataCount = $this->transactions_model->getTransactionCount($this->pageModuleUID, []);
            $summaryStats = $this->transactions_model->getTransactionSummaryStats($this->pageModuleUID, $orgUID);

            $this->pageData['ModRowData']      = $this->load->view('transactions/invoices/list', ['DataLists' => $allData, 'SerialNumber' => 0, 'JwtData' => $this->pageData['JwtData']], TRUE);
            $this->pageData['ModPagination']   = $this->globalservice->buildPagePaginationHtml('/invoices/getInvoicesPageDetails', $allDataCount, 1, $limit);
            $this->pageData['ModAllCount']     = $allDataCount;
            $this->pageData['SummaryStats']    = $summaryStats;
            $this->pageData['PaymentTypes']    = $this->transactions_model->getPaymentTypesList();
            $this->pageData['BankAccounts']    = $this->transactions_model->getOrgBankAccounts($orgUID);

            $this->load->view('transactions/invoices/view', $this->pageData);

        } catch (Exception $e) {
            redirect('dashboard', 'refresh');
        }

    }

    public function getInvoicesPageDetails($pageNo = 0) {

        $this->EndReturnData = new stdClass();
        try {

            $pageNo = (int) $pageNo;
            if ($pageNo < 1) $pageNo = 1;

            $limit  = (int) $this->input->post('RowLimit') ?: 10;
            $offset = ($pageNo - 1) * $limit;
            $filter = $this->input->post('Filter') ?: [];

            $this->load->model('transactions_model');
            $allData      = $this->transactions_model->getTransactionPageList($limit, $offset, $this->pageModuleUID, $filter, 0);
            $allDataCount = $this->transactions_model->getTransactionCount($this->pageModuleUID, $filter);

            $this->pageData['JwtData']->GenSettings = ($this->redis_cache->get('Redis_UserGenSettings')->Value) ?? new stdClass();

            $rowHtml = $this->load->view('transactions/invoices/list', [
                'DataLists'    => $allData,
                'SerialNumber' => ($pageNo - 1) * $limit,
                'JwtData'      => $this->pageData['JwtData'],
            ], true);

            $this->EndReturnData->Error           = FALSE;
            $this->EndReturnData->RecordHtmlData  = $rowHtml;
            $this->EndReturnData->Pagination      = $this->globalservice->buildPagePaginationHtml('/invoices/getInvoicesPageDetails', $allDataCount, $pageNo, $limit);
            $this->EndReturnData->TotalCount      = $allDataCount;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function addInvoice() {

        $this->EndReturnData = new stdClass();
        $ErrorInForm = '';
        try {

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData = $this->input->post();
            $userUID  = $this->pageData['JwtData']->User->UserUID;
            $orgUID   = $this->pageData['JwtData']->User->OrgUID;

            $this->load->model('formvalidation_model');
            $ErrorInForm = $this->formvalidation_model->quotationValidateForm($PostData);
            if (!empty($ErrorInForm)) throw new Exception($ErrorInForm);

            $itemsJson   = getPostValue($PostData, 'Items');
            $ErrorInForm = $this->formvalidation_model->validateQuotationItems($itemsJson);
            if (!empty($ErrorInForm)) throw new Exception($ErrorInForm);

            $customerUID            = (int)   getPostValue($PostData, 'customerSearch');
            $prefixUID              = (int)   getPostValue($PostData, 'transPrefixSelect');
            $transNumber            = (int)   getPostValue($PostData, 'transNumber');
            $transDate              =         getPostValue($PostData, 'transDate');
            $dueDate                =         getPostValue($PostData, 'dueDate');
            $items                  = json_decode($itemsJson, true);
            $totalQty               = (float) array_sum(array_column($items, 'quantity'));
            $netAmount              = (float) getPostValue($PostData, 'NetAmount',              'Array', 0);
            $subTotal               = (float) getPostValue($PostData, 'SubTotal',               'Array', 0);
            $discountAmount         = (float) getPostValue($PostData, 'DiscountAmount',         'Array', 0);
            $taxAmount              = (float) getPostValue($PostData, 'TaxAmount',              'Array', 0);
            $cgstAmount             = (float) getPostValue($PostData, 'CgstAmount',             'Array', 0);
            $sgstAmount             = (float) getPostValue($PostData, 'SgstAmount',             'Array', 0);
            $igstAmount             = (float) getPostValue($PostData, 'IgstAmount',             'Array', 0);
            $additionalChargesTotal = (float) getPostValue($PostData, 'AdditionalChargesTotal', 'Array', 0);
            $roundOff               = (float) getPostValue($PostData, 'RoundOff',               'Array', 0);
            $globalDiscPercent      = (float) getPostValue($PostData, 'GlobalDiscPercent',      'Array', 0);
            $extraDiscount          = (float) getPostValue($PostData, 'extraDiscount',          'Array', 0);
            $isDraft                = getPostValue($PostData, 'action') === 'draft';
            $status                 = $isDraft ? 'Draft' : 'Issued';

            $financialYear = (int) date('Y', strtotime($transDate));
            $this->load->model('transactions_model');

            if ($isDraft) {
                $uniqueNumber = NULL;
                $transNumber  = NULL;
                $prefixUID    = NULL;
            } else {
                if ($transNumber <= 0) throw new Exception('Transaction number must be greater than 0.');

                $prefixData = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.PrefixUID' => $prefixUID, 'Prefix.OrgUID' => $orgUID]);
                if (empty($prefixData->Data)) throw new Exception('Invalid prefix selected.');
                $prefix = $prefixData->Data[0];

                $dupCheck = $this->transactions_model->getTransactionByPrefixAndNumber($prefixUID, $transNumber, $orgUID, $this->pageModuleUID);
                if ($dupCheck) {
                    $nextSuggested = $this->transactions_model->getNextTransactionNumber($prefixUID, $orgUID, $this->pageModuleUID);
                    throw new Exception("Transaction number {$transNumber} already exists for this prefix. Next available: {$nextSuggested}.");
                }

                $sep   = $prefix->Separator ?? '-';
                $parts = [strtoupper($prefix->Name)];
                if (!empty($prefix->IncludeShortName) && !empty($prefix->ShortName)) {
                    $parts[] = strtoupper($prefix->ShortName);
                }
                if (!empty($prefix->IncludeFiscalYear)) {
                    $txMonth = (int) date('m', strtotime($transDate));
                    $txYear  = (int) date('Y', strtotime($transDate));
                    $fyStart = $txMonth >= 4 ? $txYear : $txYear - 1;
                    $parts[] = ($prefix->FiscalYearFormat ?? 'SHORT') === 'LONG'
                        ? $fyStart . '-' . ($fyStart + 1)
                        : str_pad($fyStart % 100, 2, '0', STR_PAD_LEFT) . '-' . str_pad(($fyStart + 1) % 100, 2, '0', STR_PAD_LEFT);
                }
                $padding      = (int)($prefix->NumberPadding ?? 1);
                $parts[]      = $padding > 1 ? str_pad($transNumber, $padding, '0', STR_PAD_LEFT) : (string)$transNumber;
                $uniqueNumber = implode($sep, $parts);
            }

            $this->load->model('dbwrite_model');
            $headerData = [
                'OrgUID'                => $orgUID,
                'ModuleUID'             => $this->pageModuleUID,
                'PrefixUID'             => $prefixUID,
                'UniqueNumber'          => $uniqueNumber,
                'TransType'             => 'Invoice',
                'TransNumber'           => $transNumber,
                'PartyType'             => 'C',
                'PartyUID'              => $customerUID,
                'TransDate'             => $transDate,
                'TransYear'             => $financialYear,
                'QuotationType'         => getPostValue($PostData, 'invoiceType') ?: NULL,
                'DispatchFromUID'       => ($dfUID = (int) getPostValue($PostData, 'dispatchFrom')) > 0 ? $dfUID : NULL,
                'DispatchFrom'          => getPostValue($PostData, 'dispatchFrom') ?: NULL,
                'TotalQuantity'         => $totalQty,
                'TotalItems'            => count($items),
                'GrossAmount'           => $subTotal + $discountAmount,
                'SubTotal'              => $subTotal,
                'DiscountAmount'        => $discountAmount,
                'AdditionalCharges'     => $additionalChargesTotal,
                'TaxAmount'             => $taxAmount,
                'CgstAmount'            => $cgstAmount,
                'SgstAmount'            => $sgstAmount,
                'IgstAmount'            => $igstAmount,
                'RoundOff'              => $roundOff,
                'GlobalDiscPercent'     => $globalDiscPercent,
                'ExtraDiscApplied'      => $extraDiscount > 0 ? 1 : 0,
                'ExtraDiscAmount'       => $extraDiscount,
                'ExtraDiscType'         => getPostValue($PostData, 'extDiscountType') ?: NULL,
                'NetAmount'             => $netAmount,
                'TaxableAmount'         => $subTotal,
                'PaidAmount'            => 0,
                'BalanceAmount'         => $netAmount,
                'DocStatus'             => $status,
                'IsActive'              => 1,
                'IsDeleted'             => 0,
                'CreatedBy'             => $userUID,
                'UpdatedBy'             => $userUID,
            ];
            $insertResp = $this->dbwrite_model->insertData('Transaction', 'TransactionsTbl', $headerData);
            if ($insertResp->Error) throw new Exception($insertResp->Message);

            $transUID = $insertResp->ID;

            $additionalChargesJson = $this->buildAdditionalChargesJson($PostData);
            $isInterState          = $igstAmount > 0 ? 1 : ($cgstAmount > 0 || $sgstAmount > 0 ? 0 : NULL);
            $_cc                   = $this->transactions_model->getCustomerCountryCode($customerUID);
            $isForeignCustomer     = $_cc !== NULL ? ($_cc === 'IN' ? 0 : 1) : NULL;
            $placeOfSupply         = $this->transactions_model->getCustomerBillingState($customerUID);
            $detailData = [
                'FinancialYear'     => $financialYear,
                'TransUID'          => $transUID,
                'ValidityDays'      => NULL,
                'ValidityDate'      => $dueDate ?: NULL,
                'Reference'         => getPostValue($PostData, 'referenceDetails') ?: NULL,
                'Notes'             => getPostValue($PostData, 'transNotes') ?: NULL,
                'TermsConditions'   => getPostValue($PostData, 'transTermsCond') ?: NULL,
                'AdditionalCharges' => $additionalChargesJson,
                'PlaceOfSupply'     => $placeOfSupply,
                'IsInterState'      => $isInterState,
                'IsForeignCustomer' => $isForeignCustomer,
            ];
            $detailResp = $this->dbwrite_model->insertData('Transaction', 'TransDetailTbl', $detailData);
            if ($detailResp->Error) throw new Exception($detailResp->Message);

            $this->saveInvoiceItems($transUID, $financialYear, $orgUID, $userUID, $items);

            if (!$isDraft) {
                $this->dbwrite_model->saveStockMovements($transUID, $this->pageModuleUID, $orgUID, $userUID, $items);
            }

            // Record payment DB rows inside the transaction; ledger entries applied after commit
            $paidAmountForLedger = 0;
            if (!$isDraft && (int) getPostValue($PostData, 'RecordPayment') === 1) {
                $paidAmountForLedger = $this->savePaymentRecord($transUID, $orgUID, $userUID, 'C', $customerUID, $netAmount, $PostData, $transDate);
                if ($paidAmountForLedger > 0) {
                    $this->updateTransactionBalance($transUID, $netAmount, $paidAmountForLedger, $userUID);
                }
            }

            // Conversion tracking
            if (!$isDraft) {
                $fromSalesOrderUID = (int) getPostValue($PostData, 'fromSalesOrderUID');
                if ($fromSalesOrderUID > 0) {
                    $this->dbwrite_model->updateTransDocStatus($fromSalesOrderUID, $orgUID, 'Converted', $userUID);
                    $this->dbwrite_model->insertConversionRecord(
                        $orgUID, $fromSalesOrderUID, 102, $transUID, $this->pageModuleUID, 'OrderToInvoice', $userUID
                    );
                }
                $fromQuotationUID = (int) getPostValue($PostData, 'fromQuotationUID');
                if ($fromQuotationUID > 0) {
                    $this->dbwrite_model->updateTransDocStatus($fromQuotationUID, $orgUID, 'Converted', $userUID);
                    $this->dbwrite_model->insertConversionRecord(
                        $orgUID, $fromQuotationUID, 101, $transUID, $this->pageModuleUID, 'QuotToInvoice', $userUID
                    );
                }
            }

            $this->dbwrite_model->commitTransaction();

            // Apply ledger entries after commit so ReadDb sees the committed invoice write
            if (!$isDraft) {
                try {
                    $this->load->library('accountledger');
                    $this->accountledger->applyLedgerEntry($customerUID, 'Customer', $netAmount, 'Debit', $transUID);
                    if ($paidAmountForLedger > 0) {
                        $this->accountledger->applyLedgerEntry($customerUID, 'Customer', $paidAmountForLedger, 'Credit', $transUID);
                    }
                } catch (Exception $ledgerEx) {
                    log_message('error', 'Ledger update failed after invoice creation: ' . $ledgerEx->getMessage());
                }
            }

            $this->EndReturnData->Error    = FALSE;
            $this->EndReturnData->Message  = 'Invoice created successfully.';
            $this->EndReturnData->TransUID = $transUID;
            $this->_saveAttachments($transUID);

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function updateInvoice() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData = $this->input->post();
            $userUID  = $this->pageData['JwtData']->User->UserUID;
            $orgUID   = $this->pageData['JwtData']->User->OrgUID;

            $transUID = (int) getPostValue($PostData, 'TransUID');
            if ($transUID <= 0) throw new Exception('Invoice ID is required.');

            $this->load->model('formvalidation_model');
            $headerError = $this->formvalidation_model->quotationValidateForm($PostData);
            if (!empty($headerError)) throw new Exception($headerError);

            $itemsJson  = getPostValue($PostData, 'Items');
            $itemsError = $this->formvalidation_model->validateQuotationItems($itemsJson);
            if (!empty($itemsError)) throw new Exception($itemsError);

            $customerUID            = (int)   getPostValue($PostData, 'customerSearch');
            $prefixUID              = (int)   getPostValue($PostData, 'transPrefixSelect');
            $transNumber            = (int)   getPostValue($PostData, 'transNumber');
            $transDate              =         getPostValue($PostData, 'transDate');
            $dueDate                =         getPostValue($PostData, 'dueDate');
            $items                  = json_decode($itemsJson, true);
            $totalQty               = (float) array_sum(array_column($items, 'quantity'));
            $netAmount              = (float) getPostValue($PostData, 'NetAmount',              'Array', 0);
            $subTotal               = (float) getPostValue($PostData, 'SubTotal',               'Array', 0);
            $discountAmount         = (float) getPostValue($PostData, 'DiscountAmount',         'Array', 0);
            $taxAmount              = (float) getPostValue($PostData, 'TaxAmount',              'Array', 0);
            $cgstAmount             = (float) getPostValue($PostData, 'CgstAmount',             'Array', 0);
            $sgstAmount             = (float) getPostValue($PostData, 'SgstAmount',             'Array', 0);
            $igstAmount             = (float) getPostValue($PostData, 'IgstAmount',             'Array', 0);
            $additionalChargesTotal = (float) getPostValue($PostData, 'AdditionalChargesTotal', 'Array', 0);
            $roundOff               = (float) getPostValue($PostData, 'RoundOff',               'Array', 0);
            $globalDiscPercent      = (float) getPostValue($PostData, 'GlobalDiscPercent',      'Array', 0);
            $extraDiscount          = (float) getPostValue($PostData, 'extraDiscount',          'Array', 0);
            $isDraft                = getPostValue($PostData, 'action') === 'draft';
            $status                 = $isDraft ? 'Draft' : 'Issued';

            $financialYear = (int) date('Y', strtotime($transDate));

            $this->load->model('transactions_model');
            $existing = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$existing) throw new Exception('Invoice not found.');

            $wasNonDraft  = ($existing->DocStatus !== 'Draft');
            $existingPaid = (float)($existing->PaidAmount ?? 0);
            $newBalance   = max(0, round($netAmount - $existingPaid, 2));

            // Recalculate status based on payment state when editing a live (non-draft) invoice
            if (!$isDraft && $wasNonDraft && $existingPaid > 0) {
                if ($netAmount > 0 && $existingPaid >= $netAmount) {
                    $computedStatus = 'Paid';
                    $newIsFullyPaid = 1;
                } else {
                    $computedStatus = 'Partial';
                    $newIsFullyPaid = 0;
                }
            } else {
                $computedStatus = $status;
                $newIsFullyPaid = 0;
            }

            $uniqueNumber = NULL;
            if ($existing->DocStatus === 'Draft' && !$isDraft) {
                if ($prefixUID <= 0) throw new Exception('Please select a prefix to finalise this invoice.');
                if ($transNumber <= 0) throw new Exception('Transaction number must be greater than 0.');

                $prefixData = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.PrefixUID' => $prefixUID, 'Prefix.OrgUID' => $orgUID]);
                if (empty($prefixData->Data)) throw new Exception('Invalid prefix selected.');
                $prefix = $prefixData->Data[0];

                $dupCheck = $this->transactions_model->getTransactionByPrefixAndNumber($prefixUID, $transNumber, $orgUID, $this->pageModuleUID);
                if ($dupCheck) {
                    $nextSuggested = $this->transactions_model->getNextTransactionNumber($prefixUID, $orgUID, $this->pageModuleUID);
                    throw new Exception("Transaction number {$transNumber} already exists. Next available: {$nextSuggested}.");
                }

                $sep   = $prefix->Separator ?? '-';
                $parts = [strtoupper($prefix->Name)];
                if (!empty($prefix->IncludeShortName) && !empty($prefix->ShortName)) {
                    $parts[] = strtoupper($prefix->ShortName);
                }
                if (!empty($prefix->IncludeFiscalYear)) {
                    $txMonth = (int) date('m', strtotime($transDate));
                    $txYear  = (int) date('Y', strtotime($transDate));
                    $fyStart = $txMonth >= 4 ? $txYear : $txYear - 1;
                    $parts[] = ($prefix->FiscalYearFormat ?? 'SHORT') === 'LONG'
                        ? $fyStart . '-' . ($fyStart + 1)
                        : str_pad($fyStart % 100, 2, '0', STR_PAD_LEFT) . '-' . str_pad(($fyStart + 1) % 100, 2, '0', STR_PAD_LEFT);
                }
                $padding      = (int)($prefix->NumberPadding ?? 1);
                $parts[]      = $padding > 1 ? str_pad($transNumber, $padding, '0', STR_PAD_LEFT) : (string)$transNumber;
                $uniqueNumber = implode($sep, $parts);
            }

            $additionalChargesJson = $this->buildAdditionalChargesJson($PostData);

            $activeTransUID = $transUID; // tracks the final transUID (may change for draft→issued with newer transactions)

            $commonHeader = [
                'OrgUID'            => $orgUID,
                'ModuleUID'         => $this->pageModuleUID,
                'PartyType'         => 'C',
                'PartyUID'          => $customerUID,
                'TransDate'         => $transDate,
                'TransYear'         => $financialYear,
                'TransType'         => 'Invoice',
                'QuotationType'     => getPostValue($PostData, 'invoiceType') ?: NULL,
                'DispatchFromUID'   => ($dfUID = (int) getPostValue($PostData, 'dispatchFrom')) > 0 ? $dfUID : NULL,
                'GrossAmount'       => $subTotal + $discountAmount,
                'SubTotal'          => $subTotal,
                'DiscountAmount'    => $discountAmount,
                'AdditionalCharges' => $additionalChargesTotal,
                'TaxAmount'         => $taxAmount,
                'CgstAmount'        => $cgstAmount,
                'SgstAmount'        => $sgstAmount,
                'IgstAmount'        => $igstAmount,
                'RoundOff'          => $roundOff,
                'GlobalDiscPercent' => $globalDiscPercent,
                'ExtraDiscApplied'  => $extraDiscount > 0 ? 1 : 0,
                'ExtraDiscAmount'   => $extraDiscount,
                'ExtraDiscType'     => getPostValue($PostData, 'extDiscountType') ?: NULL,
                'NetAmount'         => $netAmount,
                'TaxableAmount'     => $subTotal,
                'BalanceAmount'     => $newBalance,
                'IsFullyPaid'       => $newIsFullyPaid,
                'DocStatus'         => $computedStatus,
                'UpdatedBy'         => $userUID,
            ];

            $isInterState          = $igstAmount > 0 ? 1 : ($cgstAmount > 0 || $sgstAmount > 0 ? 0 : NULL);
            $_cc                   = $this->transactions_model->getCustomerCountryCode($customerUID);
            $isForeignCustomer     = $_cc !== NULL ? ($_cc === 'IN' ? 0 : 1) : NULL;
            $placeOfSupply         = $this->transactions_model->getCustomerBillingState($customerUID);
            $commonDetail = [
                'ValidityDays'      => NULL,
                'ValidityDate'      => $dueDate ?: NULL,
                'Reference'         => getPostValue($PostData, 'referenceDetails') ?: NULL,
                'Notes'             => getPostValue($PostData, 'transNotes') ?: NULL,
                'TermsConditions'   => getPostValue($PostData, 'transTermsCond') ?: NULL,
                'AdditionalCharges' => $additionalChargesJson,
                'PlaceOfSupply'     => $placeOfSupply,
                'IsInterState'      => $isInterState,
                'IsForeignCustomer' => $isForeignCustomer,
            ];

            // Reverse stock if existing doc was already non-draft (edit of live invoice)
            if ($wasNonDraft) {
                $this->dbwrite_model->reverseStockMovements($transUID, $orgUID, $userUID);
            }

            if ($existing->DocStatus === 'Draft' && !$isDraft
                && $this->transactions_model->hasNewerTransactions($transUID, $orgUID, $this->pageModuleUID)) {

                $newHeader = array_merge($commonHeader, [
                    'PrefixUID'    => $prefixUID,
                    'TransNumber'  => $transNumber,
                    'UniqueNumber' => $uniqueNumber,
                    'IsActive'     => 1,
                    'IsDeleted'    => 0,
                    'CreatedBy'    => $userUID,
                ]);
                $insertResp = $this->dbwrite_model->insertData('Transaction', 'TransactionsTbl', $newHeader);
                if ($insertResp->Error) throw new Exception($insertResp->Message);
                $newTransUID    = $insertResp->ID;
                $activeTransUID = $newTransUID;

                $this->dbwrite_model->insertData('Transaction', 'TransDetailTbl', array_merge($commonDetail, [
                    'FinancialYear' => $financialYear,
                    'TransUID'      => $newTransUID,
                ]));

                $this->dbwrite_model->updateData(
                    'Transaction', 'TransProductsTbl',
                    ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID],
                    ['TransUID' => $transUID, 'IsDeleted' => 0]
                );
                $this->saveInvoiceItems($newTransUID, $financialYear, $orgUID, $userUID, $items);

                if (!$isDraft) {
                    $this->dbwrite_model->saveStockMovements($newTransUID, $this->pageModuleUID, $orgUID, $userUID, $items);
                }

                $this->dbwrite_model->deleteInTransaction('Transaction', 'TransactionsTbl', ['TransUID' => $transUID]);
                $this->dbwrite_model->deleteInTransaction('Transaction', 'TransDetailTbl',  ['TransUID' => $transUID]);

            } else {
                $numberFields = [];
                if ($uniqueNumber !== NULL) {
                    $numberFields = [
                        'PrefixUID'    => $prefixUID,
                        'TransNumber'  => $transNumber,
                        'UniqueNumber' => $uniqueNumber,
                    ];
                }

                $updateResp = $this->dbwrite_model->updateData(
                    'Transaction', 'TransactionsTbl',
                    array_merge($commonHeader, $numberFields),
                    ['TransUID' => $transUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
                );
                if ($updateResp->Error) throw new Exception($updateResp->Message);

                $this->dbwrite_model->updateData(
                    'Transaction', 'TransDetailTbl', $commonDetail,
                    ['FinancialYear' => $financialYear, 'TransUID' => $transUID]
                );

                $this->dbwrite_model->updateData(
                    'Transaction', 'TransProductsTbl',
                    ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID],
                    ['TransUID' => $transUID, 'IsDeleted' => 0]
                );
                $seqOffset = $this->transactions_model->getMaxItemSequence($transUID);
                $this->saveInvoiceItems($transUID, $financialYear, $orgUID, $userUID, $items, $seqOffset);

                if (!$isDraft) {
                    $this->dbwrite_model->saveStockMovements($transUID, $this->pageModuleUID, $orgUID, $userUID, $items);
                }
            }

            // Record payment DB rows inside the transaction; ledger entries applied after commit
            $paidAmountForLedger = 0;
            if (!$isDraft && (int) getPostValue($PostData, 'RecordPayment') === 1) {
                $paidAmountForLedger = $this->savePaymentRecord($activeTransUID, $orgUID, $userUID, 'C', $customerUID, $netAmount, $PostData, $transDate);
                if ($paidAmountForLedger > 0) {
                    $this->updateTransactionBalance($activeTransUID, $netAmount, $paidAmountForLedger, $userUID);
                }
            }

            $this->dbwrite_model->commitTransaction();

            // Apply ledger entries after commit so each ReadDb read sees the prior committed write
            if (!$isDraft) {
                try {
                    $this->load->library('accountledger');
                    if ($wasNonDraft) {
                        $this->accountledger->applyLedgerEntry($customerUID, 'Customer', (float) $existing->NetAmount, 'Credit', $activeTransUID);
                    }
                    $this->accountledger->applyLedgerEntry($customerUID, 'Customer', $netAmount, 'Debit', $activeTransUID);
                    if ($paidAmountForLedger > 0) {
                        $this->accountledger->applyLedgerEntry($customerUID, 'Customer', $paidAmountForLedger, 'Credit', $activeTransUID);
                    }
                } catch (Exception $ledgerEx) {
                    log_message('error', 'Ledger update failed after invoice update: ' . $ledgerEx->getMessage());
                }
            }

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Invoice updated successfully.';
            $this->_saveAttachments($activeTransUID);
            $this->_softDeleteAttachments($this->input->post('RemovedAttachIDs') ?? '');

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    private function _saveAttachments($transUID) {
        $files = $_FILES['AttachFiles'] ?? null;
        if (empty($files) || empty($files['name'][0])) return;

        $userUID   = $this->pageData['JwtData']->User->UserUID;
        $orgUID    = $this->pageData['JwtData']->User->OrgUID;
        $moduleUID = $this->pageModuleUID;

        $this->load->library('fileupload');
        $this->load->model('dbwrite_model');

        $fileCount = count($files['name']);
        for ($i = 0; $i < $fileCount; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK || empty($files['name'][$i])) continue;

            $origName    = basename($files['name'][$i]);
            $tmpPath     = $files['tmp_name'][$i];
            $fileType    = $files['type'][$i];
            $fileSize    = $files['size'][$i];
            $safeName    = time() . '_' . $i . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $origName);
            $storagePath = 'invoices/' . $transUID . '/' . $safeName;

            $uploadResult = $this->fileupload->fileUpload('file', $storagePath, $tmpPath);
            if ($uploadResult->Error) continue;

            $filePath = '/' . ltrim($uploadResult->Path, '/');

            $this->dbwrite_model->insertData('Transaction', 'TransAttachmentsTbl', [
                'OrgUID'    => $orgUID,
                'TransUID'  => $transUID,
                'ModuleUID' => $moduleUID,
                'FileName'  => $origName,
                'FilePath'  => $filePath,
                'FileType'  => $fileType,
                'FileSize'  => $fileSize,
                'SortOrder' => $i,
                'IsActive'  => 1,
                'IsDeleted' => 0,
                'CreatedBy' => $userUID,
            ]);
        }
    }

    private function _softDeleteAttachments($removedJson) {
        if (empty($removedJson)) return;
        $uids = json_decode($removedJson, true);
        if (empty($uids) || !is_array($uids)) return;

        $orgUID  = $this->pageData['JwtData']->User->OrgUID;
        $userUID = $this->pageData['JwtData']->User->UserUID;
        $this->load->model('dbwrite_model');

        foreach ($uids as $attachUID) {
            $attachUID = (int) $attachUID;
            if ($attachUID <= 0) continue;
            $this->dbwrite_model->updateData(
                'Transaction', 'TransAttachmentsTbl',
                ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID],
                ['AttachUID' => $attachUID, 'OrgUID' => $orgUID]
            );
        }
    }

    public function recordInvoicePayment() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData = $this->input->post();
            $userUID  = $this->pageData['JwtData']->User->UserUID;
            $orgUID   = $this->pageData['JwtData']->User->OrgUID;

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
            if (!$existing) throw new Exception('Invoice not found.');
            if ($existing->DocStatus === 'Draft')                               throw new Exception('Cannot record payment for a Draft invoice.');
            if (in_array($existing->DocStatus, ['Cancelled', 'Rejected']))      throw new Exception('Invoice is cancelled.');

            // Get total already paid
            $payments    = $this->transactions_model->getTransactionPayments($transUID, $orgUID);
            $alreadyPaid = array_sum(array_column((array) $payments, 'Amount'));
            $pending     = max(0, round((float)$existing->NetAmount - $alreadyPaid, 2));

            if ($amount > $pending + 0.01) {
                throw new Exception('Amount (' . $amount . ') exceeds pending balance (' . $pending . ').');
            }

            $newTotalPaid = $alreadyPaid + $amount;
            $isFullyPaid  = ($existing->NetAmount > 0 && round((float)$existing->NetAmount - $newTotalPaid, 4) <= 0) ? 1 : 0;
            $excessAmount = max(0, round($newTotalPaid - (float)$existing->NetAmount, 4));
            $newStatus    = $isFullyPaid ? 'Paid' : 'Partial';

            // Resolve payment prefix + unique number (module 110)
            $payTransYear   = (int) date('Y', strtotime($paymentDate));
            $payPrefixData  = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID, 'Prefix.ModuleUID' => 110]);
            $payPrefix      = !empty($payPrefixData->Data) ? $payPrefixData->Data[0] : null;
            $payPrefixUID   = $payPrefix ? (int) $payPrefix->PrefixUID : null;
            $paymentNumber  = $payPrefixUID ? $this->transactions_model->getNextPaymentNumber($payPrefixUID, $orgUID, $payTransYear) : 0;
            $payUniqueNum   = ($payPrefix && $paymentNumber > 0) ? $this->buildPaymentUniqueNumber($payPrefix, $paymentDate, $paymentNumber) : null;

            $paymentData = [
                'OrgUID'         => $orgUID,
                'PaymentDate'    => $paymentDate,
                'PrefixUID'      => $payPrefixUID,
                'PaymentNumber'  => $paymentNumber,
                'UniqueNumber'   => $payUniqueNum,
                'TransYear'      => $payTransYear,
                'TransUID'       => $transUID,
                'ModuleUID'      => $this->pageModuleUID,
                'PartyType'      => 'C',
                'PartyUID'       => $existing->PartyUID,
                'PaymentTypeUID' => $paymentTypeUID,
                'Amount'         => $amount,
                'BankAccountUID' => $bankAccountUID,
                'ReferenceNo'    => $referenceNo,
                'Notes'          => $notes,
                'PaymentSource'  => 'Record',
                'IsFullyPaid'    => $isFullyPaid,
                'ExcessAmount'   => $excessAmount,
                'IsActive'       => 1,
                'IsDeleted'      => 0,
                'CreatedBy'      => $userUID,
                'UpdatedBy'      => $userUID,
            ];

            $resp = $this->dbwrite_model->insertData('Transaction', 'PaymentsTbl', $paymentData);
            if ($resp->Error) throw new Exception($resp->Message);

            // Update IsFullyPaid + PaidAmount + BalanceAmount + DocStatus on the transaction
            $balanceAmount = max(0, round((float) $existing->NetAmount - $newTotalPaid, 2));
            $ok = $this->dbwrite_model->updateTransIsFullyPaid($transUID, $isFullyPaid, $newTotalPaid, $balanceAmount, $userUID);
            if ($ok === false) throw new Exception('Failed to update transaction balance.');

            $this->dbwrite_model->updateTransDocStatus($transUID, $orgUID, $newStatus, $userUID);

            $this->dbwrite_model->commitTransaction();

            // Credit customer ledger — runs after commit, cannot poison the transaction
            try {
                $this->load->library('accountledger');
                $this->accountledger->applyLedgerEntry($existing->PartyUID, 'Customer', $amount, 'Credit', $transUID);
            } catch (Exception $ledgerEx) {
                log_message('error', 'Ledger credit failed after invoice payment: ' . $ledgerEx->getMessage());
            }

            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Message    = 'Payment of ' . $amount . ' recorded successfully.';
            $this->EndReturnData->IsFullyPaid = $isFullyPaid;

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function deleteInvoice() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData = $this->input->post();
            $userUID  = $this->pageData['JwtData']->User->UserUID;
            $orgUID   = $this->pageData['JwtData']->User->OrgUID;

            $transUID = (int) getPostValue($PostData, 'TransUID');
            if ($transUID <= 0) throw new Exception('Invoice ID is required.');

            $this->load->model('transactions_model');
            $existing = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$existing) throw new Exception('Invoice not found.');

            // Reverse stock movements (no-op if it was a draft)
            $this->dbwrite_model->reverseStockMovements($transUID, $orgUID, $userUID);

            $this->dbwrite_model->softDeleteTransactionItems($transUID, $userUID);

            $this->dbwrite_model->softDeleteTransaction($transUID, $orgUID, $userUID);

            $this->dbwrite_model->commitTransaction();

            // Reverse customer ledger AFTER commit — runs in auto-commit mode so
            // any audit-log failure cannot roll back the already-committed delete.
            if ($existing->DocStatus !== 'Draft' && $existing->PartyType === 'C' && $existing->PartyUID > 0) {
                $netAmount = (float) $existing->NetAmount;
                if ($netAmount > 0) {
                    // Only reverse the UNPAID balance. Payments were already credited to
                    // the ledger when they were recorded; reversing them again would
                    // double-subtract and corrupt the customer balance.
                    $payments    = $this->transactions_model->getTransactionPayments($transUID, $orgUID);
                    $alreadyPaid = array_sum(array_column((array) $payments, 'Amount'));
                    $remaining   = max(0, round($netAmount - $alreadyPaid, 2));

                    if ($remaining > 0) {
                        $this->load->library('accountledger');
                        $this->accountledger->applyLedgerEntry($existing->PartyUID, 'Customer', $remaining, 'Credit', $transUID);
                    }
                }
            }

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Invoice deleted successfully.';

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function duplicateInvoice() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData = $this->input->post();
            $srcUID   = (int) getPostValue($PostData, 'TransUID');
            $userUID  = $this->pageData['JwtData']->User->UserUID;
            $orgUID   = $this->pageData['JwtData']->User->OrgUID;

            if ($srcUID <= 0) throw new Exception('Invalid invoice.');

            $this->load->model('transactions_model');
            $src = $this->transactions_model->getTransactionById($srcUID, $orgUID, $this->pageModuleUID);
            if (!$src) throw new Exception('Invoice not found.');

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
                'TransType'         => 'Invoice',
                'TransNumber'       => $nextNumber,
                'PartyType'         => $src->PartyType,
                'PartyUID'          => $src->PartyUID,
                'TransDate'         => $today,
                'QuotationType'     => $src->QuotationType,
                'DispatchFromUID'   => $src->DispatchFromUID ?? NULL,
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

            $_srcCC            = $src->PartyCountryCode ?? NULL;
            $detailData = [
                'FinancialYear'     => (int) date('Y'),
                'TransUID'          => $newTransUID,
                'ValidityDays'      => NULL,
                'ValidityDate'      => NULL,
                'Reference'         => $src->Reference       ?? NULL,
                'Notes'             => $src->Notes           ?? NULL,
                'TermsConditions'   => $src->TermsConditions ?? NULL,
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
                    'IsActive'          => 1,
                    'IsDeleted'         => 0,
                    'CreatedBy'         => $userUID,
                    'UpdatedBy'         => $userUID,
                ];
                $this->dbwrite_model->insertData('Transaction', 'TransProductsTbl', $itemRow);
            }

            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error    = FALSE;
            $this->EndReturnData->Message  = 'Invoice duplicated as ' . $uniqueNumber . '.';
            $this->EndReturnData->TransUID = $newTransUID;
            $this->EndReturnData->EditURL  = '/invoices/edit/' . $newTransUID;

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function updateInvoiceStatus() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');
            $PostData  = $this->input->post();
            $transUID  = (int) getPostValue($PostData, 'TransUID');
            $newStatus = trim(getPostValue($PostData, 'Status'));
            $userUID   = $this->pageData['JwtData']->User->UserUID;
            $orgUID    = $this->pageData['JwtData']->User->OrgUID;

            if ($transUID <= 0) throw new Exception('Invalid invoice.');

            $validTransitions = [
                'Draft'     => ['Issued', 'Cancelled'],
                'Issued'    => ['Paid', 'Partial', 'Cancelled'],
                'Partial'   => ['Paid', 'Cancelled'],
                'Paid'      => [],
                'Cancelled' => [],
            ];

            $this->load->model('transactions_model');
            $existing = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$existing) throw new Exception('Invoice not found.');

            $current = $existing->DocStatus;
            if (!in_array($newStatus, $validTransitions[$current] ?? [])) {
                throw new Exception("Cannot change status from {$current} to {$newStatus}.");
            }

            $this->dbwrite_model->startTransaction();
            $resp = $this->dbwrite_model->updateData(
                'Transaction', 'TransactionsTbl',
                ['DocStatus' => $newStatus, 'UpdatedBy' => $userUID],
                ['TransUID' => $transUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );
            if ($resp->Error) throw new Exception($resp->Message);
            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error     = FALSE;
            $this->EndReturnData->Message   = 'Status updated.';
            $this->EndReturnData->NewStatus = $newStatus;

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function getInvoiceDetail() {

        $this->EndReturnData = new stdClass();
        try {

            $transUID = (int) $this->input->get_post('TransUID');
            $orgUID   = $this->pageData['JwtData']->User->OrgUID;

            if ($transUID <= 0) throw new Exception('Invalid invoice.');

            $this->load->model('transactions_model');
            $header = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$header) throw new Exception('Invoice not found.');

            $items = $this->transactions_model->getTransactionItems($transUID, $orgUID);

            $this->load->model('organisation_model');
            $orgInfo          = $this->organisation_model->getOrgForReceipt($orgUID);
            $thermalCfgResult = $this->organisation_model->getThermalPrintConfig($orgUID);
            $printThemeResult = $this->organisation_model->getPrintThemeByType($orgUID, 'Invoice');

            $payments  = $this->transactions_model->getTransactionPayments($transUID, $orgUID);
            $paidTotal = array_sum(array_map(function($p) { return (float) $p->Amount; }, $payments));

            $this->EndReturnData->Error         = FALSE;
            $this->EndReturnData->Header        = $header;
            $this->EndReturnData->Items         = $items;
            $this->EndReturnData->Payments      = $payments;
            $this->EndReturnData->PaidTotal     = $paidTotal;
            $this->EndReturnData->OrgInfo       = $orgInfo->Data ?? null;
            $this->EndReturnData->ThermalConfig = $thermalCfgResult->Data ?? null;
            $this->EndReturnData->PrintTheme    = $printThemeResult->Data ?? null;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    private function saveInvoiceItems($transUID, $financialYear, $orgUID, $userUID, array $items, $seqOffset = 0) {

        $this->load->model('dbwrite_model');

        $rows = [];
        foreach ($items as $seq => $item) {

            $productUID = isset($item['id'])       ? (int)   $item['id']       : 0;
            $qty        = isset($item['quantity'])  ? (float) $item['quantity']  : 0;
            $unitPrice  = isset($item['unitPrice']) ? (float) $item['unitPrice'] : 0;

            if ($productUID <= 0 || $qty <= 0) continue;

            $rows[] = [
                'OrgUID'            => $orgUID,
                'FinancialYear'     => $financialYear,
                'TransUID'          => $transUID,
                'ItemSequence'      => $seqOffset + $seq + 1,
                'ProductUID'        => $productUID,
                'ProductName'       => substr(strip_tags($item['itemName'] ?? ''), 0, 100),
                'Description'       => isset($item['description'])   ? substr($item['description'], 0, 500) : NULL,
                'PartNumber'        => isset($item['partNumber'])    ? substr($item['partNumber'], 0, 50) : NULL,
                'CategoryUID'       => isset($item['categoryUID'])   ? (int) $item['categoryUID']          : NULL,
                'StorageUID'        => isset($item['storageUID'])    ? (int) $item['storageUID']            : NULL,
                'Quantity'          => $qty,
                'PrimaryUnitName'   => isset($item['primaryUnit'])   ? substr($item['primaryUnit'], 0, 20)  : NULL,
                'TaxDetailsUID'     => isset($item['taxDetailsUID']) ? (int) $item['taxDetailsUID']          : 1,
                'TaxPercentage'     => (float) ($item['taxPercent']   ?? 0),
                'CGST'              => (float) ($item['cgstPercent']  ?? 0),
                'SGST'              => (float) ($item['sgstPercent']  ?? 0),
                'IGST'              => (float) ($item['igstPercent']  ?? 0),
                'DiscountTypeUID'   => isset($item['discountTypeUID']) ? (int) $item['discountTypeUID'] : NULL,
                'Discount'          => (float) ($item['discount']       ?? 0),
                'UnitPrice'         => $unitPrice,
                'SellingPrice'      => (float) ($item['sellingPrice']   ?? $unitPrice),
                'TaxableAmount'     => (float) ($item['line_total']     ?? 0),
                'CgstAmount'        => (float) ($item['cgstAmount']     ?? 0),
                'SgstAmount'        => (float) ($item['sgstAmount']     ?? 0),
                'IgstAmount'        => (float) ($item['igstAmount']     ?? 0),
                'TaxAmount'         => (float) ($item['taxAmount']      ?? 0),
                'DiscountAmount'    => (float) ($item['discount_amount'] ?? 0),
                'NetAmount'         => (float) ($item['net_total']       ?? 0),
                'QuantityConverted' => 0,
                'IsActive'          => 1,
                'IsDeleted'         => 0,
                'CreatedBy'         => $userUID,
                'UpdatedBy'         => $userUID,
            ];
        }

        if (empty($rows)) return;

        $batchResp = $this->dbwrite_model->insertBatchInTransaction('Transaction', 'TransProductsTbl', $rows);
        if ($batchResp->Error) throw new Exception($batchResp->Message);

    }

    private function updateTransactionBalance($transUID, $netAmount, $paidAmount, $userUID) {
        $isFullyPaid   = ($netAmount > 0 && round($netAmount - $paidAmount, 4) <= 0) ? 1 : 0;
        $balanceAmount = max(0, round($netAmount - $paidAmount, 2));
        $ok = $this->dbwrite_model->updateTransIsFullyPaid($transUID, $isFullyPaid, $paidAmount, $balanceAmount, $userUID);
        if ($ok === false) {
            throw new Exception('Failed to update transaction balance for TransUID ' . $transUID);
        }
    }

    private function savePaymentRecord($transUID, $orgUID, $userUID, $partyType, $partyUID, $billTotal, $PostData, $transDate = null) {

        $rowsJson    = getPostValue($PostData, 'PaymentRows') ?: '';
        $isFullyPaid = (int) getPostValue($PostData, 'IsFullyPaid') === 1 ? 1 : 0;

        if (empty($rowsJson)) return 0;
        $rows = json_decode($rowsJson, true);
        if (!is_array($rows) || empty($rows)) return 0;

        $paymentDate = $transDate ?: date('Y-m-d');
        $totalPaid   = array_sum(array_column($rows, 'amount'));

        // Resolve payment prefix once for all rows in this batch
        $payTransYear  = (int) date('Y', strtotime($paymentDate));
        $payPrefixData = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID, 'Prefix.ModuleUID' => 110]);
        $payPrefix     = !empty($payPrefixData->Data) ? $payPrefixData->Data[0] : null;
        $payPrefixUID  = $payPrefix ? (int) $payPrefix->PrefixUID : null;

        foreach ($rows as $idx => $row) {
            $paymentTypeUID = (int)   ($row['paymentTypeUID'] ?? 0);
            $amount         = (float) ($row['amount']         ?? 0);
            $bankAccountUID = !empty($row['bankAccountUID']) ? (int) $row['bankAccountUID'] : NULL;
            $referenceNo    = !empty($row['referenceNo'])    ? $row['referenceNo'] : NULL;
            $notes          = !empty($row['notes'])          ? $row['notes']       : NULL;

            if ($paymentTypeUID <= 0 || $amount <= 0) continue;

            // Only the last row can carry excess; earlier rows never exceed bill
            $rowExcess = 0;
            if ($idx === count($rows) - 1 && $billTotal > 0 && $totalPaid > $billTotal) {
                $rowExcess = round($totalPaid - $billTotal, 4);
            }

            $paymentNumber = $payPrefixUID ? $this->transactions_model->getNextPaymentNumber($payPrefixUID, $orgUID, $payTransYear) : 0;
            $payUniqueNum  = ($payPrefix && $paymentNumber > 0) ? $this->buildPaymentUniqueNumber($payPrefix, $paymentDate, $paymentNumber) : null;

            $paymentData = [
                'OrgUID'            => $orgUID,
                'PaymentDate'       => $paymentDate,
                'PrefixUID'         => $payPrefixUID,
                'PaymentNumber'     => $paymentNumber,
                'UniqueNumber'      => $payUniqueNum,
                'TransYear'         => $payTransYear,
                'TransUID'          => (int) $transUID,
                'ModuleUID'         => $this->pageModuleUID,
                'PartyType'         => $partyType,
                'PartyUID'          => $partyUID,
                'PaymentTypeUID'    => $paymentTypeUID,
                'Amount'            => $amount,
                'BankAccountUID'    => $bankAccountUID,
                'ReferenceNo'       => $referenceNo,
                'Notes'             => $notes,
                'PaymentSource'     => 'Create',
                'IsFullyPaid'       => ($idx === count($rows) - 1) ? $isFullyPaid : 0,
                'ExcessAmount'      => $rowExcess,
                'AppliedToTransUID' => NULL,
                'IsActive'          => 1,
                'IsDeleted'         => 0,
                'CreatedBy'         => $userUID,
                'UpdatedBy'         => $userUID,
            ];

            $resp = $this->dbwrite_model->insertBatchInTransaction('Transaction', 'PaymentsTbl', [$paymentData]);
            if ($resp->Error) throw new Exception('Payment save failed: ' . $resp->Message);
        }

        return $totalPaid;

    }

    /**
     * Builds a formatted UniqueNumber for a payment entry using the same
     * prefix formatting rules as invoice numbers.
     */
    private function buildPaymentUniqueNumber($prefix, $paymentDate, $paymentNumber) {
        $sep   = $prefix->Separator ?? '-';
        $parts = [strtoupper($prefix->Name)];
        if (!empty($prefix->IncludeShortName) && !empty($prefix->ShortName)) {
            $parts[] = strtoupper($prefix->ShortName);
        }
        if (!empty($prefix->IncludeFiscalYear)) {
            $m  = (int) date('m', strtotime($paymentDate));
            $yr = (int) date('Y', strtotime($paymentDate));
            $fy = $m >= 4 ? $yr : $yr - 1;
            $parts[] = ($prefix->FiscalYearFormat ?? 'SHORT') === 'LONG'
                ? $fy . '-' . ($fy + 1)
                : str_pad($fy % 100, 2, '0', STR_PAD_LEFT) . '-' . str_pad(($fy + 1) % 100, 2, '0', STR_PAD_LEFT);
        }
        $pad     = (int)($prefix->NumberPadding ?? 1);
        $parts[] = $pad > 1 ? str_pad($paymentNumber, $pad, '0', STR_PAD_LEFT) : (string) $paymentNumber;
        return implode($sep, $parts);
    }

    public function uploadAttachments() {

        $this->EndReturnData = new stdClass();
        try {

            $PostData  = $this->input->post();
            $userUID   = $this->pageData['JwtData']->User->UserUID;
            $orgUID    = $this->pageData['JwtData']->User->OrgUID;
            $transUID  = (int) getPostValue($PostData, 'TransUID');
            $moduleUID = (int) getPostValue($PostData, 'ModuleUID') ?: $this->pageModuleUID;

            if ($transUID <= 0) throw new Exception('Invalid invoice reference.');

            $files = $_FILES['AttachFiles'] ?? null;
            if (empty($files) || empty($files['name'][0])) {
                $this->EndReturnData->Error   = FALSE;
                $this->EndReturnData->Message = 'No files to upload.';
                $this->globalservice->sendJsonResponse($this->EndReturnData);
                return;
            }

            $this->load->library('fileupload');
            $this->load->model('dbwrite_model');

            $uploaded = 0;
            $fileCount = count($files['name']);
            for ($i = 0; $i < $fileCount; $i++) {
                if ($files['error'][$i] !== UPLOAD_ERR_OK || empty($files['name'][$i])) continue;

                $origName  = basename($files['name'][$i]);
                $tmpPath   = $files['tmp_name'][$i];
                $fileType  = $files['type'][$i];
                $fileSize  = $files['size'][$i];
                $ext       = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                $safeName  = time() . '_' . $i . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $origName);
                $storagePath = 'invoices/' . $transUID . '/' . $safeName;

                $uploadResult = $this->fileupload->fileUpload('file', $storagePath, $tmpPath);
                if ($uploadResult->Error) continue;

                $this->dbwrite_model->insertData('Transaction', 'TransAttachmentsTbl', [
                    'OrgUID'     => $orgUID,
                    'TransUID'   => $transUID,
                    'ModuleUID'  => $moduleUID,
                    'FileName'   => $origName,
                    'FilePath'   => $uploadResult->Path,
                    'FileType'   => $fileType,
                    'FileSize'   => $fileSize,
                    'SortOrder'  => $i,
                    'IsActive'   => 1,
                    'IsDeleted'  => 0,
                    'CreatedBy'  => $userUID,
                ]);
                $uploaded++;
            }

            $this->EndReturnData->Error    = FALSE;
            $this->EndReturnData->Message  = $uploaded . ' file(s) uploaded.';
            $this->EndReturnData->Uploaded = $uploaded;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function getAttachments() {

        $this->EndReturnData = new stdClass();
        try {

            $transUID = (int) $this->input->post('TransUID');
            $orgUID   = $this->pageData['JwtData']->User->OrgUID;
            if ($transUID <= 0) throw new Exception('Invalid invoice.');

            $this->load->model('transactions_model');
            $attachments = $this->transactions_model->getTransactionAttachments($transUID, $orgUID);

            $this->EndReturnData->Error       = FALSE;
            $this->EndReturnData->Attachments = $attachments;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    private function buildAdditionalChargesJson($PostData) {
        $charges = [];
        $types   = ['shipping', 'handling', 'packing', 'other'];
        foreach ($types as $type) {
            $amt = (float) getPostValue($PostData, $type . 'Amount', 'Array', 0);
            $tax = getPostValue($PostData, $type . 'Tax') ?: NULL;
            if ($amt > 0) {
                $charges[] = ['type' => $type, 'amount' => $amt, 'tax' => $tax];
            }
        }
        return !empty($charges) ? json_encode($charges) : NULL;
    }

    public function create() {

        try {

            $GeneralSettings = $this->redis_cache->get('Redis_UserGenSettings')->Value ?? NULL;
            $this->pageData['JwtData']->GenSettings = $GeneralSettings;

            $orgUID = $this->pageData['JwtData']->User->OrgUID;
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

            // Pre-fill from Sales Order if converting
            $fromSOUID = (int) $this->input->get('fromSalesOrder');
            $this->pageData['FromSalesOrderUID'] = $fromSOUID;
            $this->pageData['SalesOrderData']    = null;
            $this->pageData['SalesOrderItems']   = [];
            if ($fromSOUID > 0) {
                $soData  = $this->transactions_model->getTransactionById($fromSOUID, $orgUID, 102);
                $soItems = $soData ? $this->transactions_model->getTransactionItems($fromSOUID, $orgUID) : [];
                $this->pageData['SalesOrderData']  = $soData;
                $this->pageData['SalesOrderItems'] = $soItems;
            }

            // Pre-fill from Quotation if converting directly
            $fromQuotationUID = (int) $this->input->get('fromQuotation');
            $this->pageData['FromQuotationUID'] = $fromQuotationUID;
            $this->pageData['QuotationData']    = null;
            $this->pageData['QuotationItems']   = [];
            if ($fromQuotationUID > 0) {
                $quotData  = $this->transactions_model->getTransactionById($fromQuotationUID, $orgUID, 101);
                $quotItems = $quotData ? $this->transactions_model->getTransactionItems($fromQuotationUID, $orgUID) : [];
                $this->pageData['QuotationData']  = $quotData;
                $this->pageData['QuotationItems'] = $quotItems;
            }

            $this->load->model('organisation_model');
            $dispatchAddrResult                = $this->organisation_model->getOrgDispatchAddress($orgUID);
            $this->pageData['DispatchAddress'] = $dispatchAddrResult->Data ?? NULL;

            $this->load->model('global_model');
            $GetCountryInfo = $this->global_model->getCountryInfo();
            $this->pageData['CountryInfo'] = $GetCountryInfo->Error === FALSE ? $GetCountryInfo->Data : [];

            $this->pageData['StateData'] = [];
            $this->pageData['CityData']  = [];

            $OrgCountryISO2 = $this->pageData['JwtData']->User->OrgCISO2;
            if (!empty($OrgCountryISO2)) {
                $StateInfo = $this->global_model->getStateofCountry($OrgCountryISO2);
                if ($StateInfo->Error === FALSE) $this->pageData['StateData'] = $StateInfo->Data;
                $CityInfo = $this->global_model->getCityofCountry($OrgCountryISO2);
                if ($CityInfo->Error === FALSE) $this->pageData['CityData'] = $CityInfo->Data;
            }

            $this->pageData['PrimaryUnitInfo'] = $this->global_model->getPrimaryUnitInfo()->Data ?? [];
            $this->pageData['DiscTypeInfo']    = $this->global_model->getDiscountTypeInfo()->Data ?? [];
            $this->pageData['ProdTypeInfo']    = $this->global_model->getProductTypeInfo()->Data ?? [];
            $this->pageData['ProdTaxInfo']     = $this->global_model->getProductTaxInfo()->Data ?? [];
            $this->pageData['TaxDetInfo']      = $this->global_model->getTaxDetailsInfo()->Data ?? [];

            $this->load->model('products_model');
            $this->pageData['fltCategoryData'] = $this->products_model->getCategoriesDetails([]) ?? [];

            $this->pageData['PaymentTypes']  = $this->transactions_model->getPaymentTypesList();
            $this->pageData['BankAccounts']  = $this->transactions_model->getOrgBankAccounts($orgUID);

            $this->load->view('transactions/invoices/forms/add', $this->pageData);

        } catch (Exception $e) {
            redirect('invoices', 'refresh');
        }

    }

    public function edit($transUID = 0) {

        try {

            $transUID = (int) $transUID;
            if ($transUID <= 0) redirect('invoices');

            $GeneralSettings = $this->redis_cache->get('Redis_UserGenSettings')->Value ?? NULL;
            $this->pageData['JwtData']->GenSettings = $GeneralSettings;

            $orgUID = $this->pageData['JwtData']->User->OrgUID;
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;

            $this->load->model('transactions_model');

            $invData  = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$invData) redirect('invoices');

            $invItems = $this->transactions_model->getTransactionItems($transUID, $orgUID);

            $this->load->model('customers_model');
            $custAddr = $this->customers_model->getCustomerAddress(['CustAddress.CustomerUID' => $invData->PartyUID, 'CustAddress.OrgUID' => $orgUID]);
            $shipping = current(array_filter($custAddr, fn($a) => $a->AddressType === 'Shipping'));
            $billing  = current(array_filter($custAddr, fn($a) => $a->AddressType === 'Billing'));
            $this->pageData['CustAddr'] = $shipping ?: ($billing ?: ($custAddr[0] ?? null));

            $this->pageData['InvData']  = $invData;
            $this->pageData['InvItems'] = $invItems;

            $prefixResult                    = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID, 'Prefix.ModuleUID' => $this->pageModuleUID]);
            $this->pageData['PrefixData']    = $prefixResult->Data ?? [];

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

            $this->load->model('global_model');
            $GetCountryInfo = $this->global_model->getCountryInfo();
            $this->pageData['CountryInfo'] = $GetCountryInfo->Error === FALSE ? $GetCountryInfo->Data : [];

            $this->pageData['StateData'] = [];
            $this->pageData['CityData']  = [];

            $OrgCountryISO2 = $this->pageData['JwtData']->User->OrgCISO2;
            if (!empty($OrgCountryISO2)) {
                $StateInfo = $this->global_model->getStateofCountry($OrgCountryISO2);
                if ($StateInfo->Error === FALSE) $this->pageData['StateData'] = $StateInfo->Data;
                $CityInfo = $this->global_model->getCityofCountry($OrgCountryISO2);
                if ($CityInfo->Error === FALSE) $this->pageData['CityData'] = $CityInfo->Data;
            }

            $this->pageData['PrimaryUnitInfo'] = $this->global_model->getPrimaryUnitInfo()->Data ?? [];
            $this->pageData['DiscTypeInfo']    = $this->global_model->getDiscountTypeInfo()->Data ?? [];
            $this->pageData['ProdTypeInfo']    = $this->global_model->getProductTypeInfo()->Data ?? [];
            $this->pageData['ProdTaxInfo']     = $this->global_model->getProductTaxInfo()->Data ?? [];
            $this->pageData['TaxDetInfo']      = $this->global_model->getTaxDetailsInfo()->Data ?? [];

            $this->load->model('products_model');
            $this->pageData['fltCategoryData'] = $this->products_model->getCategoriesDetails([]) ?? [];

            $this->pageData['PaymentTypes'] = $this->transactions_model->getPaymentTypesList();
            $this->pageData['BankAccounts'] = $this->transactions_model->getOrgBankAccounts($orgUID);

            $this->load->view('transactions/invoices/forms/edit', $this->pageData);

        } catch (Exception $e) {
            redirect('invoices', 'refresh');
        }

    }

}
