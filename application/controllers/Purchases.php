<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Purchases extends MY_Controller {

    public $pageData = array();
    private $EndReturnData;
    private $pageModuleUID;

    public function __construct() {
        parent::__construct();

        $this->pageModuleUID = 105;
        $this->load->helper('transaction');

    }

    public function index() {

        if (!$this->_loadPageTitle($this->pageModuleUID)) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }

        try {

            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;

            $GeneralSettings = $this->pageData['JwtData']->GenSettings ?? new stdClass();
            $limit = $GeneralSettings->RowLimit ?? 10;
            $this->pageData['DiscTypeInfo'] = [];

            $this->load->model('transactions_model');
            $allData      = $this->transactions_model->getTransactionPageList($limit, 0, $this->pageModuleUID, [], 0);
            $allDataCount = $this->transactions_model->getTransactionCount($this->pageModuleUID, []);

            $orgUID = $this->pageData['JwtData']->Org->OrgUID;

            $this->pageData['ModRowData']    = $this->load->view('transactions/purchases/list', ['DataLists' => $allData, 'SerialNumber' => 0, 'JwtData' => $this->pageData['JwtData']], TRUE);
            $this->pageData['ModPagination'] = $this->globalservice->buildPagePaginationHtml('/purchases/getPurchasesPageDetails', $allDataCount, 1, $limit);
            $this->pageData['ModAllCount']   = $allDataCount;
            $this->pageData['SummaryStats']  = $this->transactions_model->getTransactionSummaryStats($this->pageModuleUID, $orgUID);
            $this->pageData['PaymentTypes']  = $this->transactions_model->getPaymentTypesList();
            $this->pageData['BankAccounts']  = $this->transactions_model->getOrgBankAccounts($orgUID);

            $this->pageData['UpstashReadUrl']   = getenv('UPSTASH_REDIS_REST_URL') ?: '';
            $this->pageData['UpstashReadToken'] = getenv('UPSTASH_REDIS_REST_READONLY_TOKEN') ?: '';
            $this->pageData['VendorCacheKey']   = $this->redisservice->orgKey('vendors');

            $this->load->view('transactions/purchases/view', $this->pageData);

        } catch (Exception $e) {
            redirect('dashboard', 'refresh');
        }

    }

    public function getPurchasesPageDetails($pageNo = 0) {

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


            $rowHtml = $this->load->view('transactions/purchases/list', [
                'DataLists'    => $allData,
                'SerialNumber' => ($pageNo - 1) * $limit,
                'JwtData'      => $this->pageData['JwtData'],
            ], true);

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->RecordHtmlData = $rowHtml;
            $this->EndReturnData->Pagination     = $this->globalservice->buildPagePaginationHtml('/purchases/getPurchasesPageDetails', $allDataCount, $pageNo, $limit);
            $this->EndReturnData->TotalCount     = $allDataCount;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

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

            $this->load->model('formvalidation_model');
            $ErrorInForm = $this->formvalidation_model->quotationValidateForm($PostData);
            if (!empty($ErrorInForm)) throw new Exception($ErrorInForm);

            $itemsJson   = getPostValue($PostData, 'Items');
            $ErrorInForm = $this->formvalidation_model->validateQuotationItems($itemsJson);
            if (!empty($ErrorInForm)) throw new Exception($ErrorInForm);

            $vendorUID              = (int)   getPostValue($PostData, 'vendorSearch');
            $prefixUID              = (int)   getPostValue($PostData, 'transPrefixSelect');
            $transNumber            = (int)   getPostValue($PostData, 'transNumber');
            $transDate              =         getPostValue($PostData, 'transDate');
            $billDueDate            =         getPostValue($PostData, 'billDueDate');
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
            $status                 = $isDraft ? 'Draft' : 'Received';

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
                'TransType'             => 'Purchase',
                'TransNumber'           => $transNumber,
                'PartyType'             => 'S',
                'PartyUID'              => $vendorUID,
                'TransDate'             => $transDate,
                'TransYear'             => $financialYear,
                'QuotationType'         => getPostValue($PostData, 'purchaseType') ?: NULL,
                'DispatchFrom'          => getPostValue($PostData, 'dispatchTo') ?: NULL,
                'TotalQuantity'         => $totalQty,
                'TotalItems'            => count($items),
                'GrossAmount'           => $subTotal + $discountAmount,
                'SubTotal'              => $subTotal,
                'TaxableAmount'         => $subTotal,
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
                'PaidAmount'            => 0,
                'BalanceAmount'         => $netAmount,
                'IsFullyPaid'           => 0,
                'DocStatus'             => $status,
                'TransToken'            => $this->transactions_model->_uniqueTransToken(),
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

            $this->load->model('vendors_model');
            $vendorAddrArr = $this->vendors_model->getVendorAddress(['VendAddress.VendorUID' => $vendorUID, 'VendAddress.OrgUID' => $orgUID]);
            $placeOfSupply = !empty($vendorAddrArr) ? ($vendorAddrArr[0]->StateText ?? NULL) : NULL;

            $detailData = [
                'FinancialYear'     => $financialYear,
                'TransUID'          => $transUID,
                'ValidityDays'      => NULL,
                'ValidityDate'      => $billDueDate ?: NULL,
                'SupplierInvoiceNo' => getPostValue($PostData, 'supplierInvoiceNo') ?: NULL,
                'Reference'         => getPostValue($PostData, 'referenceDetails') ?: NULL,
                'Notes'             => getPostValue($PostData, 'transNotes') ?: NULL,
                'TermsConditions'   => getPostValue($PostData, 'transTermsCond') ?: NULL,
                'SignatureUID'      => (int)getPostValue($PostData, 'SignatureUID') ?: NULL,
                'AdditionalCharges' => $additionalChargesJson,
                'PlaceOfSupply'     => $placeOfSupply,
                'IsInterState'      => $isInterState,
                'IsForeignCustomer' => NULL,
            ];
            $this->dbwrite_model->insertData('Transaction', 'TransDetailTbl', $detailData);

            $this->savePurchaseItems($transUID, $financialYear, $orgUID, $userUID, $items);

            if (!$isDraft) {
                $this->dbwrite_model->saveStockMovements($transUID, $this->pageModuleUID, $orgUID, $userUID, $items);
            }

            // Save payment records and update balance
            $paidAmountForLedger = 0;
            if (!$isDraft && (int) getPostValue($PostData, 'RecordPayment') === 1) {
                $paidAmountForLedger = $this->savePaymentRecord($transUID, $orgUID, $userUID, 'S', $vendorUID, $netAmount, $PostData, $transDate);
                if ($paidAmountForLedger > 0) {
                    $this->updateTransactionBalance($transUID, $netAmount, $paidAmountForLedger, $userUID);
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

            $this->EndReturnData->Error    = FALSE;
            $this->EndReturnData->Message  = 'Purchase bill recorded successfully.';
            $this->EndReturnData->TransUID = $transUID;

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

            $this->load->model('formvalidation_model');
            $headerError = $this->formvalidation_model->quotationValidateForm($PostData);
            if (!empty($headerError)) throw new Exception($headerError);

            $itemsJson  = getPostValue($PostData, 'Items');
            $itemsError = $this->formvalidation_model->validateQuotationItems($itemsJson);
            if (!empty($itemsError)) throw new Exception($itemsError);

            $vendorUID              = (int)   getPostValue($PostData, 'vendorSearch');
            $prefixUID              = (int)   getPostValue($PostData, 'transPrefixSelect');
            $transNumber            = (int)   getPostValue($PostData, 'transNumber');
            $transDate              =         getPostValue($PostData, 'transDate');
            $billDueDate            =         getPostValue($PostData, 'billDueDate');
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
            $status                 = $isDraft ? 'Draft' : 'Received';

            $financialYear = (int) date('Y', strtotime($transDate));

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

            $commonHeader = [
                'OrgUID'            => $orgUID,
                'ModuleUID'         => $this->pageModuleUID,
                'PartyType'         => 'S',
                'PartyUID'          => $vendorUID,
                'TransDate'         => $transDate,
                'TransYear'         => $financialYear,
                'TransType'         => 'Purchase',
                'QuotationType'     => getPostValue($PostData, 'purchaseType') ?: NULL,
                'DispatchFrom'      => getPostValue($PostData, 'dispatchTo') ?: NULL,
                'GrossAmount'       => $subTotal + $discountAmount,
                'SubTotal'          => $subTotal,
                'TaxableAmount'     => $subTotal,
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
                'BalanceAmount'     => max(0, round($netAmount - (float)($existing->PaidAmount ?? 0), 2)),
                'DocStatus'         => $status,
                'UpdatedBy'         => $userUID,
                'PdfPath'           => NULL,
            ];

            $isInterState = $igstAmount > 0 ? 1 : ($cgstAmount > 0 || $sgstAmount > 0 ? 0 : NULL);

            $this->load->model('vendors_model');
            $vendorAddrArr = $this->vendors_model->getVendorAddress(['VendAddress.VendorUID' => $vendorUID, 'VendAddress.OrgUID' => $orgUID]);
            $placeOfSupply = !empty($vendorAddrArr) ? ($vendorAddrArr[0]->StateText ?? NULL) : NULL;

            $commonDetail = [
                'ValidityDays'      => NULL,
                'ValidityDate'      => $billDueDate ?: NULL,
                'SupplierInvoiceNo' => getPostValue($PostData, 'supplierInvoiceNo') ?: NULL,
                'Reference'         => getPostValue($PostData, 'referenceDetails') ?: NULL,
                'Notes'             => getPostValue($PostData, 'transNotes') ?: NULL,
                'TermsConditions'   => getPostValue($PostData, 'transTermsCond') ?: NULL,
                'SignatureUID'      => (int)getPostValue($PostData, 'SignatureUID') ?: NULL,
                'AdditionalCharges' => $additionalChargesJson,
                'PlaceOfSupply'     => $placeOfSupply,
                'IsInterState'      => $isInterState,
                'IsForeignCustomer' => NULL,
            ];

            $wasNonDraft   = ($existing->DocStatus !== 'Draft');
            $activeTransUID = $transUID;
            if ($wasNonDraft) {
                $this->dbwrite_model->reverseStockMovements($transUID, $orgUID, $userUID);
            }

            if ($existing->DocStatus === 'Draft' && !$isDraft
                && $this->transactions_model->hasNewerTransactions($transUID, $orgUID, $this->pageModuleUID)) {

                $newHeader = array_merge($commonHeader, [
                    'PrefixUID'    => $prefixUID,
                    'TransNumber'  => $transNumber,
                    'UniqueNumber' => $uniqueNumber,
                    'TransToken'   => $this->transactions_model->_uniqueTransToken(),
                    'IsActive'     => 1,
                    'IsDeleted'    => 0,
                    'CreatedBy'    => $userUID,
                ]);
                $insertResp     = $this->dbwrite_model->insertData('Transaction', 'TransactionsTbl', $newHeader);
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
                $this->savePurchaseItems($newTransUID, $financialYear, $orgUID, $userUID, $items);

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

                                // Smart item diff: only soft-delete removed items, update existing, insert new
                $existingItems = $this->transactions_model->getTransactionItems($transUID, $orgUID);
                $existingByProduct = [];
                foreach ($existingItems as $ei) { $existingByProduct[(int)$ei->ProductUID] = $ei; }
                $submittedProductUIDs = [];
                foreach ($items as $item) { $pid = isset($item['id']) ? (int)$item['id'] : 0; if ($pid > 0) $submittedProductUIDs[] = $pid; }
                $removedProductUIDs = array_diff(array_keys($existingByProduct), $submittedProductUIDs);
                if (!empty($removedProductUIDs)) {
                    $this->dbwrite_model->softDeleteTransactionItemsByProductUIDs($transUID, array_values($removedProductUIDs), $userUID);
                }
                $newRows = [];
                foreach ($items as $seq => $item) {
                    $productUID = isset($item['id']) ? (int)$item['id'] : 0;
                    $qty        = isset($item['quantity']) ? (float)$item['quantity'] : 0;
                    $unitPrice  = isset($item['unitPrice']) ? (float)$item['unitPrice'] : 0;
                    if ($productUID <= 0 || $qty <= 0) continue;
                    $rowData = [
                        'ItemSequence'    => $seq + 1,
                        'ProductName'     => substr(strip_tags($item['itemName'] ?? ''), 0, 100),
                        'Description'     => !empty($item['description'])  ? substr($item['description'], 0, 500) : NULL,
                        'PartNumber'      => !empty($item['partNumber'])   ? substr($item['partNumber'], 0, 50)   : NULL,
                        'CategoryUID'     => !empty($item['categoryUID'])  ? (int)$item['categoryUID']            : NULL,
                        'CategoryName'    => !empty($item['categoryName']) ? substr($item['categoryName'], 0, 100) : NULL,
                        'StorageUID'      => isset($item['storageUID'])    ? (int)$item['storageUID']              : NULL,
                        'Quantity'        => $qty,
                        'PrimaryUnitName' => isset($item['primaryUnit'])    ? substr($item['primaryUnit'], 0, 20)  : NULL,
                        'TaxDetailsUID'   => isset($item['taxDetailsUID'])  ? (int)$item['taxDetailsUID']          : 1,
                        'TaxPercentage'   => (float)($item['taxPercent']    ?? 0),
                        'CGST'            => (float)($item['cgstPercent']   ?? 0),
                        'SGST'            => (float)($item['sgstPercent']   ?? 0),
                        'IGST'            => (float)($item['igstPercent']   ?? 0),
                        'DiscountTypeUID' => isset($item['discountTypeUID']) ? (int)$item['discountTypeUID'] : NULL,
                        'Discount'        => (float)($item['discount']        ?? 0),
                        'UnitPrice'       => $unitPrice,
                        'SellingPrice'    => (float)($item['sellingPrice']    ?? $unitPrice),
                        'PurchasePrice'   => (float)($item['purchasePrice']   ?? 0),
                        'TaxableAmount'   => (float)($item['line_total']      ?? 0),
                        'CgstAmount'      => (float)($item['cgstAmount']      ?? 0),
                        'SgstAmount'      => (float)($item['sgstAmount']      ?? 0),
                        'IgstAmount'      => (float)($item['igstAmount']      ?? 0),
                        'TaxAmount'       => (float)($item['taxAmount']       ?? 0),
                        'DiscountAmount'  => (float)($item['discount_amount']  ?? 0),
                        'NetAmount'       => (float)($item['net_total']        ?? 0),
                        'UpdatedBy'       => $userUID,
                    ];
                    if (isset($existingByProduct[$productUID])) {
                        $this->dbwrite_model->updateTransProductItem($transUID, $productUID, $rowData);
                    } else {
                        $newRows[] = array_merge($rowData, [
                            'OrgUID' => $orgUID, 'FinancialYear' => $financialYear,
                            'TransUID' => $transUID, 'ProductUID' => $productUID,
                            'QuantityConverted' => 0, 'IsActive' => 1, 'IsDeleted' => 0, 'CreatedBy' => $userUID,
                        ]);
                    }
                }
                if (!empty($newRows)) {
                    $batchResp = $this->dbwrite_model->insertBatchInTransaction('Transaction', 'TransProductsTbl', $newRows);
                    if ($batchResp->Error) throw new Exception($batchResp->Message);
                }

                if (!$isDraft) {
                    $this->dbwrite_model->saveStockMovements($transUID, $this->pageModuleUID, $orgUID, $userUID, $items);
                }
            }

            // Save optional payment records
            if (!$isDraft && (int) getPostValue($PostData, 'RecordPayment') === 1) {
                $paidAmountForUpdate = $this->savePaymentRecord($transUID, $orgUID, $userUID, 'S', $vendorUID, $netAmount, $PostData, $transDate);
                if ($paidAmountForUpdate > 0) {
                    $this->updateTransactionBalance($activeTransUID, $netAmount, $paidAmountForUpdate, $userUID);
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
                        $activeTransUID, $transDate, $activeUniqueNumber, $financialYear,
                        $netAmount, $subTotal, $cgstAmount, $sgstAmount, $igstAmount,
                        $vendorUID, $userUID
                    );
                } catch (Exception $ledgerEx) {
                    log_message('error', 'Ledger update failed after purchase update: ' . $ledgerEx->getMessage());
                }
            }

            $this->_saveAttachments($activeTransUID);
            $this->_softDeleteAttachments($this->input->post('RemovedAttachIDs') ?? '');

            $this->dbwrite_model->commitTransaction();
            $this->_touchVendorCache($vendorUID);
            $this->transactions_model->generateAndStorePdf(isset($newTransUID) ? $newTransUID : $transUID, $orgUID, $this->pageModuleUID);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Purchase bill updated successfully.';

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
                ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID, 'UpdatedOn' => $now],
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
            }

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Purchase bill deleted successfully.';

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
                'QuotationType'     => $src->QuotationType,
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
                    'CreatedOn'         => $now,
                    'UpdatedOn'         => $now,
                ];
                $this->dbwrite_model->insertData('Transaction', 'TransProductsTbl', $itemRow);
            }

            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error    = FALSE;
            $this->EndReturnData->Message  = 'Purchase bill duplicated as ' . $uniqueNumber . '.';
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

    public function getPurchaseDetail() {

        $this->EndReturnData = new stdClass();
        try {

            $transUID = (int) $this->input->get_post('TransUID');
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;

            if ($transUID <= 0) throw new Exception('Invalid purchase bill.');

            $this->load->model('transactions_model');
            $header = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$header) throw new Exception('Purchase bill not found.');

            $items = $this->transactions_model->getTransactionItems($transUID, $orgUID);

            $this->load->model('organisation_model');
            $orgInfo          = $this->organisation_model->getOrgInfoCached($orgUID);
            $thermalCfgResult = $this->organisation_model->getThermalPrintConfig($orgUID);
            $printThemeResult = $this->organisation_model->getPrintThemeByType($orgUID, 'Purchase');

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

    private function savePurchaseItems($transUID, $financialYear, $orgUID, $userUID, array $items, $seqOffset = 0) {

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
                'Description'       => !empty($item['description'])  ? substr($item['description'], 0, 500) : NULL,
                'PartNumber'        => !empty($item['partNumber'])   ? substr($item['partNumber'], 0, 50)  : NULL,
                'CategoryUID'       => !empty($item['categoryUID'])  ? (int) $item['categoryUID']           : NULL,
                'CategoryName'      => !empty($item['categoryName']) ? substr($item['categoryName'], 0, 100) : NULL,
                'StorageUID'        => isset($item['storageUID'])    ? (int) $item['storageUID']             : NULL,
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
                'PurchasePrice'     => (float) ($item['purchasePrice']  ?? 0),
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

    private function updateTransactionBalance($transUID, $netAmount, $paidAmount, $userUID) {
        $isFullyPaid   = ($netAmount > 0 && round($netAmount - $paidAmount, 4) <= 0) ? 1 : 0;
        $balanceAmount = max(0, round($netAmount - $paidAmount, 2));
        $this->dbwrite_model->updateTransIsFullyPaid($transUID, $isFullyPaid, $paidAmount, $balanceAmount, $userUID);
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
        $this->load->model('transactions_model');
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

            $rowExcess = 0;
            if ($idx === count($rows) - 1 && $billTotal > 0 && $totalPaid > $billTotal) {
                $rowExcess = round($totalPaid - $billTotal, 4);
            }

            $paymentNumber = $payPrefixUID ? $this->transactions_model->getNextPaymentNumber($payPrefixUID, $orgUID, $payTransYear) : 0;
            $payUniqueNum  = ($payPrefix && $paymentNumber > 0) ? $this->buildPaymentUniqueNumber($payPrefix, $paymentDate, $paymentNumber) : null;
            $receiptToken  = $this->transactions_model->_generateReceiptToken();

            $paymentData = [
                'OrgUID'            => $orgUID,
                'PaymentDate'       => $paymentDate,
                'PrefixUID'         => $payPrefixUID,
                'PaymentNumber'     => $paymentNumber,
                'UniqueNumber'      => $payUniqueNum,
                'ReceiptToken'      => $receiptToken,
                'TransYear'         => $payTransYear,
                'TransUID'          => $transUID,
                'ModuleUID'         => 110,
                'PartyType'         => $partyType,
                'PartyUID'          => $partyUID,
                'PaymentTypeUID'    => $paymentTypeUID,
                'Amount'            => $amount,
                'BankAccountUID'    => $bankAccountUID,
                'ReferenceNo'       => $referenceNo,
                'Notes'             => $notes,
                'PaymentSource'     => 'Create',
                'PaymentDirection'  => 'Out',
                'IsFullyPaid'       => ($idx === count($rows) - 1) ? $isFullyPaid : 0,
                'ExcessAmount'      => $rowExcess,
                'AppliedToTransUID' => NULL,
                'IsActive'          => 1,
                'IsDeleted'         => 0,
                'CreatedBy'         => $userUID,
                'UpdatedBy'         => $userUID,
            ];

            $this->dbwrite_model->insertData('Transaction', 'PaymentsTbl', $paymentData);
        }

        return $totalPaid;

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
            if ($fromPOUID > 0) {
                $poData  = $this->transactions_model->getTransactionById($fromPOUID, $orgUID, 104);
                $poItems = $poData ? $this->transactions_model->getTransactionItems($fromPOUID, $orgUID) : [];
                $this->pageData['POData']  = $poData;
                $this->pageData['POItems'] = $poItems;
            }

            $this->load->model('global_model');
            $GetCountryInfo = $this->global_model->getCountryInfo();
            $this->pageData['CountryInfo'] = $GetCountryInfo->Error === FALSE ? $GetCountryInfo->Data : [];

            $this->pageData['StateData'] = [];
            $this->pageData['CityData']  = [];

            $OrgCountryISO2 = $this->pageData['JwtData']->Org->OrgCISO2;
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

            $this->_getDispatchAddresses($orgUID);

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

            $this->load->model('global_model');
            $GetCountryInfo = $this->global_model->getCountryInfo();
            $this->pageData['CountryInfo'] = $GetCountryInfo->Error === FALSE ? $GetCountryInfo->Data : [];

            $this->pageData['StateData'] = [];
            $this->pageData['CityData']  = [];

            $OrgCountryISO2 = $this->pageData['JwtData']->Org->OrgCISO2;
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

            $this->_getDispatchAddresses($orgUID);

            $this->load->view('transactions/purchases/forms/form', $this->pageData);

        } catch (Exception $e) {
            redirect('purchases', 'refresh');
        }

    }

    private function _touchVendorCache($vendorUID) {
        $this->cachehelper->touchVendor($vendorUID);
    }

    private function _saveAttachments($transUID) {
        $files = $_FILES['AttachFiles'] ?? null;
        if (empty($files) || empty($files['name'][0])) return;

        $userUID   = $this->pageData['JwtData']->User->UserUID;
        $orgUID    = $this->pageData['JwtData']->Org->OrgUID;
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
            $storagePath = 'purchases/' . $transUID . '/' . $safeName;

            $uploadResult = $this->fileupload->fileUpload('file', $storagePath, $tmpPath);
            if ($uploadResult->Error) continue;

            $this->dbwrite_model->insertData('Transaction', 'TransAttachmentsTbl', [
                'OrgUID'    => $orgUID,
                'TransUID'  => $transUID,
                'ModuleUID' => $moduleUID,
                'FileName'  => $origName,
                'FilePath'  => '/' . ltrim($uploadResult->Path, '/'),
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

        $orgUID  = $this->pageData['JwtData']->Org->OrgUID;
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

    public function uploadAttachments() {

        $this->EndReturnData = new stdClass();
        try {

            $PostData  = $this->input->post();
            $userUID   = $this->pageData['JwtData']->User->UserUID;
            $orgUID    = $this->pageData['JwtData']->Org->OrgUID;
            $transUID  = (int) getPostValue($PostData, 'TransUID');
            $moduleUID = (int) getPostValue($PostData, 'ModuleUID') ?: $this->pageModuleUID;

            if ($transUID <= 0) throw new Exception('Invalid purchase reference.');

            $files = $_FILES['AttachFiles'] ?? null;
            if (empty($files) || empty($files['name'][0])) {
                $this->EndReturnData->Error   = FALSE;
                $this->EndReturnData->Message = 'No files to upload.';
                $this->globalservice->sendJsonResponse($this->EndReturnData);
                return;
            }

            $this->load->library('fileupload');
            $this->load->model('dbwrite_model');

            $uploaded  = 0;
            $fileCount = count($files['name']);
            for ($i = 0; $i < $fileCount; $i++) {
                if ($files['error'][$i] !== UPLOAD_ERR_OK || empty($files['name'][$i])) continue;

                $origName    = basename($files['name'][$i]);
                $tmpPath     = $files['tmp_name'][$i];
                $fileType    = $files['type'][$i];
                $fileSize    = $files['size'][$i];
                $safeName    = time() . '_' . $i . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $origName);
                $storagePath = 'purchases/' . $transUID . '/' . $safeName;

                $uploadResult = $this->fileupload->fileUpload('file', $storagePath, $tmpPath);
                if ($uploadResult->Error) continue;

                $this->dbwrite_model->insertData('Transaction', 'TransAttachmentsTbl', [
                    'OrgUID'    => $orgUID,
                    'TransUID'  => $transUID,
                    'ModuleUID' => $moduleUID,
                    'FileName'  => $origName,
                    'FilePath'  => $uploadResult->Path,
                    'FileType'  => $fileType,
                    'FileSize'  => $fileSize,
                    'SortOrder' => $i,
                    'IsActive'  => 1,
                    'IsDeleted' => 0,
                    'CreatedBy' => $userUID,
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

            $payTransYear  = (int) date('Y', strtotime($paymentDate));
            $payPrefixData = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID, 'Prefix.ModuleUID' => 111]);
            $payPrefix     = !empty($payPrefixData->Data) ? $payPrefixData->Data[0] : null;
            $payPrefixUID  = $payPrefix ? (int) $payPrefix->PrefixUID : null;
            $paymentNumber = $payPrefixUID ? $this->transactions_model->getNextPaymentNumber($payPrefixUID, $orgUID, $payTransYear) : 0;
            $payUniqueNum  = ($payPrefix && $paymentNumber > 0) ? $this->buildPaymentUniqueNumber($payPrefix, $paymentDate, $paymentNumber) : null;
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

            $balanceAmount = max(0, round((float) $existing->NetAmount - $newTotalPaid, 2));
            $ok = $this->dbwrite_model->updateTransIsFullyPaid($transUID, $isFullyPaid, $newTotalPaid, $balanceAmount, $userUID);
            if ($ok === false) throw new Exception('Failed to update purchase balance.');

            $this->dbwrite_model->updateTransDocStatus($transUID, $orgUID, $newStatus, $userUID);

            $this->dbwrite_model->commitTransaction();

            // Save payment file attachments immediately after commit
            $this->_savePaymentAttachments($resp->ID);

            // Debit vendor ledger — payment made reduces our payable to them
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

            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Message    = 'Payment of ' . $amount . ' recorded successfully.';
            $this->EndReturnData->IsFullyPaid = $isFullyPaid;

            // Refresh the purchase list
            $GeneralSettings = $this->pageData['JwtData']->GenSettings ?? new stdClass();
            $limit  = $GeneralSettings->RowLimit ?? 10;
            $pageNo = (int) $this->input->post('CurrentPage') ?: 1;
            $offset = ($pageNo - 1) * $limit;
            $filter = $this->input->post('Filter') ?: [];

            $allData      = $this->transactions_model->getTransactionPageList($limit, $offset, $this->pageModuleUID, $filter, 0);
            $allDataCount = $this->transactions_model->getTransactionCount($this->pageModuleUID, $filter);
            $summaryStats = $this->transactions_model->getTransactionSummaryStats($this->pageModuleUID, $orgUID);

            $this->pageData['JwtData']->GenSettings = $GeneralSettings;
            $rowHtml = $this->load->view('transactions/purchases/list', [
                'DataLists'    => $allData,
                'SerialNumber' => $offset,
                'JwtData'      => $this->pageData['JwtData'],
            ], true);

            $this->EndReturnData->RecordHtmlData = $rowHtml;
            $this->EndReturnData->Pagination     = $this->globalservice->buildPagePaginationHtml('/purchases/getPurchasesPageDetails', $allDataCount, $pageNo, $limit);
            $this->EndReturnData->TotalCount     = $allDataCount;
            $this->EndReturnData->SummaryStats   = $summaryStats;

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

    private function _savePaymentAttachments($paymentUID) {
        $files = $_FILES['PaymentFiles'] ?? null;
        if (empty($files) || empty($files['name'][0])) return;

        $userUID = $this->pageData['JwtData']->User->UserUID;
        $orgUID  = $this->pageData['JwtData']->Org->OrgUID;

        $this->load->library('fileupload');
        $this->load->model('dbwrite_model');

        $allowed = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
        $count   = min(count($files['name']), 3);

        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK || empty($files['name'][$i])) continue;
            if ($files['size'][$i] > 3 * 1024 * 1024) continue;
            if (!in_array($files['type'][$i], $allowed)) continue;

            $origName    = basename($files['name'][$i]);
            $safeName    = time() . '_' . $i . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $origName);
            $storagePath = 'payments/' . $paymentUID . '/' . $safeName;

            $uploadResult = $this->fileupload->fileUpload('file', $storagePath, $files['tmp_name'][$i]);
            if ($uploadResult->Error) {
                log_message('error', 'Purchase payment attachment upload failed: ' . $uploadResult->Message);
                continue;
            }

            $this->dbwrite_model->insertData('Transaction', 'PaymentAttachmentsTbl', [
                'OrgUID'     => $orgUID,
                'PaymentUID' => $paymentUID,
                'FileName'   => $origName,
                'FilePath'   => '/' . ltrim($uploadResult->Path, '/'),
                'FileType'   => $files['type'][$i],
                'FileSize'   => $files['size'][$i],
                'SortOrder'  => $i,
                'IsActive'   => 1,
                'IsDeleted'  => 0,
                'CreatedBy'  => $userUID,
            ]);
        }
    }

}
