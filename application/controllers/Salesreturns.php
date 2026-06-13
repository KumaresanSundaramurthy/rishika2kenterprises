<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Salesreturns extends MY_Controller {

    public $pageData = array();
    private $EndReturnData;
    private $pageModuleUID;

    public function __construct() {
        parent::__construct();
        $this->pageModuleUID = 106;
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

            $this->load->model('transactions_model');
            $datePref   = $this->getDateFilterPreference('salesreturns');
            $initFilter = $datePref['from'] ? ['DateFrom' => $datePref['from'], 'DateTo' => $datePref['to']] : [];
            $allData      = $this->transactions_model->getTransactionPageList($limit, 0, $this->pageModuleUID, $initFilter, 0);
            $allDataCount = $this->transactions_model->getTransactionCount($this->pageModuleUID, $initFilter);
            $this->pageData['SavedDateRange'] = $datePref['range'];
            $this->pageData['SavedDateLabel'] = $datePref['label'];

            $orgUID = $this->pageData['JwtData']->Org->OrgUID;
            $this->_annotateSRListWithCancelDeps($allData, $orgUID);

            $this->pageData['ModRowData']    = $this->load->view('transactions/salesreturns/list', ['DataLists' => $allData, 'SerialNumber' => 0, 'JwtData' => $this->pageData['JwtData']], TRUE);
            $this->pageData['ModPagination'] = $this->globalservice->buildPagePaginationHtml('/salesreturns/getSalesReturnsPageDetails', $allDataCount, 1, $limit);
            $this->pageData['ModAllCount']   = $allDataCount;
            $this->pageData['SummaryStats']  = $this->transactions_model->getTransactionSummaryStats($this->pageModuleUID, $this->pageData['JwtData']->Org->OrgUID);
            $this->pageData['PaymentTypes']  = $this->transactions_model->getPaymentTypesList();
            $this->pageData['BankAccounts']  = $this->transactions_model->getOrgBankAccounts($this->pageData['JwtData']->Org->OrgUID);

            $this->_loadUpstashConfig();

            $this->load->model('users_model');
            $this->pageData['OrgUsers']         = $this->users_model->getOrgUsersForCache($this->pageData['JwtData']->Org->OrgUID);

            $this->load->view('transactions/salesreturns/view', $this->pageData);
        } catch (Exception $e) {
            redirect('dashboard', 'refresh');
        }
    }

    public function getSalesReturnsPageDetails($pageNo = 0) {
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

            $this->_annotateSRListWithCancelDeps($allData, $this->pageData['JwtData']->Org->OrgUID);

            $rowHtml = $this->load->view('transactions/salesreturns/list', [
                'DataLists'    => $allData,
                'SerialNumber' => ($pageNo - 1) * $limit,
                'JwtData'      => $this->pageData['JwtData'],
            ], true);

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->RecordHtmlData = $rowHtml;
            $this->EndReturnData->Pagination     = $this->globalservice->buildPagePaginationHtml('/salesreturns/getSalesReturnsPageDetails', $allDataCount, $pageNo, $limit);
            $this->EndReturnData->TotalCount     = $allDataCount;
            $this->EndReturnData->SummaryStats   = $this->transactions_model->getTransactionSummaryStats($this->pageModuleUID, $this->pageData['JwtData']->Org->OrgUID);
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
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

            $this->load->model('formvalidation_model');
            $ErrorInForm = $this->formvalidation_model->transactionValidateForm($PostData);
            if (!empty($ErrorInForm)) throw new Exception($ErrorInForm);

            $itemsJson   = getPostValue($PostData, 'Items');
            $ErrorInForm = $this->formvalidation_model->validateQuotationItems($itemsJson);
            if (!empty($ErrorInForm)) throw new Exception($ErrorInForm);

            $customerUID            = (int)   getPostValue($PostData, 'customerSearch');
            $prefixUID              = (int)   getPostValue($PostData, 'transPrefixSelect');
            $transNumber            = (int)   getPostValue($PostData, 'transNumber');
            $transDate              =         getPostValue($PostData, 'transDate');
            $returnDate             =         getPostValue($PostData, 'returnDate');
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
            $prefix = null;
            $isDraft                = getPostValue($PostData, 'action') === 'draft';
            $status                 = $isDraft ? 'Draft' : 'Approved';

            // Log customer balance before SR creation
            $this->load->model('customers_model');
            $_preSR = $this->customers_model->getCustomerOpeningBalance($orgUID, $customerUID);
            log_message('debug', '[SR-CREATE-BEFORE] CustomerUID=' . $customerUID . ' OrgUID=' . $orgUID
                . ' SRAmount=' . $netAmount
                . ' PendingBalance=' . ($_preSR ? $_preSR->PendingBalance : 'NULL')
                . ' PendingBalType=' . ($_preSR ? $_preSR->PendingBalType : 'NULL'));

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
                $prefix   = $prefixData->Data[0];
                $dupCheck = $this->transactions_model->getTransactionByPrefixAndNumber($prefixUID, $transNumber, $orgUID, $this->pageModuleUID);
                if ($dupCheck) {
                    $nextSuggested = $this->transactions_model->getNextTransactionNumber($prefixUID, $orgUID, $this->pageModuleUID);
                    throw new Exception("Transaction number {$transNumber} already exists. Next available: {$nextSuggested}.");
                }
                list($uniqueNumber) = $this->buildUniqueNumber($prefix, $transNumber, $transDate);
            }

            $headerData = [
                'OrgUID'            => $orgUID,
                'ModuleUID'         => $this->pageModuleUID,
                'PrefixUID'         => $prefixUID,
                'UniqueNumber'      => $uniqueNumber,
                'TransType'         => 'Sales Return',
                'TransNumber'       => $transNumber,
                'PartyType'         => 'C',
                'PartyUID'          => $customerUID,
                'TransDate'         => $transDate,
                'TransYear'         => $financialYear,
                'QuotationType'     => getPostValue($PostData, 'returnType') ?: NULL,
                'DispatchFrom'      => getPostValue($PostData, 'dispatchFrom') ?: NULL,
                'TotalQuantity'     => $totalQty,
                'TotalItems'        => count($items),
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
                'BalanceAmount'     => NULL,
                'DocStatus'         => $status,
                'TransToken'        => generate_uuid4(),
                'IsActive'          => 1,
                'IsDeleted'         => 0,
                'CreatedBy'         => $userUID,
                'UpdatedBy'         => $userUID,
            ];
            $insertResp = $this->_insertTransactionWithRetry($headerData, $prefixUID, $orgUID, $prefix, $transDate);
            if ($insertResp->Error) throw new Exception($insertResp->Message);
            $transUID     = $insertResp->ID;
            $transNumber  = $headerData['TransNumber'];
            $uniqueNumber = $headerData['UniqueNumber'];

            $additionalChargesJson = $this->buildAdditionalChargesJson($PostData);
            $isInterState          = $igstAmount > 0 ? 1 : ($cgstAmount > 0 || $sgstAmount > 0 ? 0 : NULL);
            $_cc                   = $this->transactions_model->getCustomerCountryCode($customerUID);
            $isForeignCustomer     = $_cc !== NULL ? ($_cc === 'IN' ? 0 : 1) : NULL;
            $detailData = [
                'FinancialYear'     => $financialYear,
                'TransUID'          => $transUID,
                'ValidityDays'      => NULL,
                'ValidityDate'      => $returnDate ?: NULL,
                'Reference'         => getPostValue($PostData, 'referenceDetails') ?: NULL,
                'Notes'             => getPostValue($PostData, 'transNotes') ?: NULL,
                'TermsConditions'   => getPostValue($PostData, 'transTermsCond') ?: NULL,
                'SignatureUID'      => (int)getPostValue($PostData, 'SignatureUID') ?: NULL,
                'AdditionalCharges' => $additionalChargesJson,
                'PlaceOfSupplyCode' => getPostValue($PostData, 'placeOfSupplyCode') ?: NULL,
                'PlaceOfSupplyName' => getPostValue($PostData, 'placeOfSupplyName') ?: NULL,
                'IsInterState'      => $isInterState,
                'IsForeignCustomer' => $isForeignCustomer,
            ];
            $this->dbwrite_model->insertData('Transaction', 'TransDetailTbl', $detailData);
            $this->saveTransactionItems($transUID, $financialYear, $orgUID, $userUID, $items);

            if (!$isDraft) {
                $this->dbwrite_model->saveStockMovements($transUID, $this->pageModuleUID, $orgUID, $userUID, $items);
            }

            $this->dbwrite_model->commitTransaction();

            $this->_saveAttachments($transUID);
            if ($isDraft) $this->cachehelper->touchCustomer($customerUID);

            // ── Save payment if recorded on create ──────────────────
            $hasPayment = false;
            if (!$isDraft && (int) getPostValue($PostData, 'RecordPayment') === 1) {
                $payResult = $this->_savePaymentRecord($transUID, $orgUID, $userUID, 'C', $customerUID, $netAmount, $PostData, $transDate);
                if ($payResult['totalPaid'] > 0) {
                    $hasPayment    = true;
                    $isFullyPaid   = ($netAmount > 0 && round($netAmount - $payResult['totalPaid'], 4) <= 0) ? 1 : 0;
                    $balanceAmount = max(0, round($netAmount - $payResult['totalPaid'], 2));
                    $this->dbwrite_model->updateTransIsFullyPaid($transUID, $isFullyPaid, $payResult['totalPaid'], $balanceAmount, $userUID);
                    $newStatus = $isFullyPaid ? 'Paid' : 'Partial';
                    $this->dbwrite_model->updateTransDocStatus($transUID, $orgUID, $newStatus, $userUID);
                }
                if (!empty($payResult['firstPaymentUID'])) {
                    $this->_savePaymentAttachments($payResult['firstPaymentUID']);
                }
            }

            if (!$isDraft) {
                log_message('debug', '[SR-CN-TRACE] Step1: TransUID=' . $transUID
                    . ' hasPayment=' . ($hasPayment ? 'true' : 'false')
                    . ' netAmount=' . $netAmount
                    . ' uniqueNumber=' . $uniqueNumber
                    . ' customerUID=' . $customerUID
                    . ' orgUID=' . $orgUID
                    . ' RecordPayment_POST=' . (int)getPostValue($PostData, 'RecordPayment'));

                // ── Create credit note for the outstanding balance ──────────────
                // No payment  → CN for full NetAmount
                // Partial pay → CN for remaining BalanceAmount
                // Full pay    → no CN (balanceAmount = 0)
                $cnAmount = $hasPayment ? ($balanceAmount ?? 0) : $netAmount;
                log_message('debug', '[SR-CN-TRACE] Step2: cnAmount=' . $cnAmount
                    . ' hasPayment=' . ($hasPayment ? 'true' : 'false')
                    . ' netAmount=' . $netAmount
                    . ' balanceAmount=' . ($balanceAmount ?? 'N/A'));

                if ($cnAmount > 0) {
                    log_message('debug', '[SR-CN-TRACE] Step2: Calling createSalesReturnCreditNote');
                    $this->load->library('customerbalance');
                    $cnResult = $this->customerbalance->createSalesReturnCreditNote(
                        $orgUID, $customerUID, $transUID, $uniqueNumber, $cnAmount, $userUID, $transDate
                    );
                    log_message('debug', '[SR-CN-TRACE] Step3: createSalesReturnCreditNote returned=' . ($cnResult ? json_encode($cnResult) : 'NULL — check error log for DB failure'));
                    if ($cnResult) {
                        $this->EndReturnData->CreditNoteUID    = $cnResult['creditNoteUID'];
                        $this->EndReturnData->CreditNoteNumber = $cnResult['creditNoteNumber'];
                    } else {
                        log_message('error', '[SR-CN-TRACE] CN creation FAILED for SR=' . $uniqueNumber . ' Amount=' . $cnAmount);
                    }
                } else {
                    log_message('debug', '[SR-CN-TRACE] Step2: CN creation SKIPPED — fully paid (cnAmount=0)');
                }

                $balResult = $this->_recalcCustomerBalance($orgUID, $customerUID, $userUID);
                if ($balResult) {
                    $this->EndReturnData->CustomerBalance     = $balResult['balance'];
                    $this->EndReturnData->CustomerBalanceType = $balResult['type'];
                    log_message('debug', '[SR-CN-TRACE] Step4-Balance: CustomerUID=' . $customerUID
                        . ' NewBalance=' . $balResult['balance'] . '(' . $balResult['type'] . ')');
                } else {
                    log_message('error', '[SR-CN-TRACE] Step4-Balance: recalcAndSync returned null for CustomerUID=' . $customerUID);
                }
            } else {
                log_message('debug', '[SR-CN-TRACE] Step1: isDraft=true — whole CN+balance block skipped');
            }

            $this->EndReturnData->Error    = FALSE;
            $this->EndReturnData->Message  = 'Sales Return created successfully.';
            $this->EndReturnData->TransUID = $transUID;
        } catch (Exception $e) {
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
            if ($transUID <= 0) throw new Exception('Sales Return ID is required.');

            $this->load->model('formvalidation_model');
            $headerError = $this->formvalidation_model->transactionValidateForm($PostData);
            if (!empty($headerError)) throw new Exception($headerError);
            $itemsJson  = getPostValue($PostData, 'Items');
            $itemsError = $this->formvalidation_model->validateQuotationItems($itemsJson);
            if (!empty($itemsError)) throw new Exception($itemsError);

            $customerUID            = (int)   getPostValue($PostData, 'customerSearch');
            $prefixUID              = (int)   getPostValue($PostData, 'transPrefixSelect');
            $transNumber            = (int)   getPostValue($PostData, 'transNumber');
            $transDate              =         getPostValue($PostData, 'transDate');
            $returnDate             =         getPostValue($PostData, 'returnDate');
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
            $status                 = $isDraft ? 'Draft' : 'Approved';
            $financialYear          = (int) date('Y', strtotime($transDate));

            $this->load->model('transactions_model');
            $existing = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$existing) throw new Exception('Sales Return not found.');

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
                $sep   = $prefix->Separator ?? '-';
                $parts = [strtoupper($prefix->Name)];
                if (!empty($prefix->IncludeShortName) && !empty($prefix->ShortName)) $parts[] = strtoupper($prefix->ShortName);
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
                'PartyType'         => 'C',
                'PartyUID'          => $customerUID,
                'TransDate'         => $transDate,
                'TransYear'         => $financialYear,
                'TransType'         => 'Sales Return',
                'QuotationType'     => getPostValue($PostData, 'returnType') ?: NULL,
                'DispatchFrom'      => getPostValue($PostData, 'dispatchFrom') ?: NULL,
                'TotalQuantity'     => $totalQty,
                'TotalItems'        => count($items),
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
                'DocStatus'         => $status,
                'UpdatedBy'         => $userUID,
                'PdfPath'           => NULL,
            ];
            $isInterState          = $igstAmount > 0 ? 1 : ($cgstAmount > 0 || $sgstAmount > 0 ? 0 : NULL);
            $_cc                   = $this->transactions_model->getCustomerCountryCode($customerUID);
            $isForeignCustomer     = $_cc !== NULL ? ($_cc === 'IN' ? 0 : 1) : NULL;
            $commonDetail = [
                'ValidityDays'      => NULL,
                'ValidityDate'      => $returnDate ?: NULL,
                'Reference'         => getPostValue($PostData, 'referenceDetails') ?: NULL,
                'Notes'             => getPostValue($PostData, 'transNotes') ?: NULL,
                'TermsConditions'   => getPostValue($PostData, 'transTermsCond') ?: NULL,
                'SignatureUID'      => (int)getPostValue($PostData, 'SignatureUID') ?: NULL,
                'AdditionalCharges' => $additionalChargesJson,
                'IsInterState'      => $isInterState,
                'IsForeignCustomer' => $isForeignCustomer,
            ];

            $wasNonDraft = ($existing->DocStatus !== 'Draft');
            if ($wasNonDraft) {
                $this->dbwrite_model->reverseStockMovements($transUID, $orgUID, $userUID);
            }

            if ($existing->DocStatus === 'Draft' && !$isDraft
                && $this->transactions_model->hasNewerTransactions($transUID, $orgUID, $this->pageModuleUID)) {

                $newHeader = array_merge($commonHeader, [
                    'PrefixUID'    => $prefixUID,
                    'TransNumber'  => $transNumber,
                    'UniqueNumber' => $uniqueNumber,
                    'TransToken'   => generate_uuid4(),
                    'IsActive'     => 1,
                    'IsDeleted'    => 0,
                    'CreatedBy'    => $userUID,
                ]);
                $insertResp = $this->dbwrite_model->insertData('Transaction', 'TransactionsTbl', $newHeader);
                if ($insertResp->Error) throw new Exception($insertResp->Message);
                $newTransUID = $insertResp->ID;
                $this->dbwrite_model->insertData('Transaction', 'TransDetailTbl', array_merge($commonDetail, ['FinancialYear' => $financialYear, 'TransUID' => $newTransUID]));
                $this->dbwrite_model->updateData('Transaction', 'TransProductsTbl', ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID], ['TransUID' => $transUID, 'IsDeleted' => 0]);
                $this->saveTransactionItems($newTransUID, $financialYear, $orgUID, $userUID, $items);
                if (!$isDraft) {
                    $this->dbwrite_model->saveStockMovements($newTransUID, $this->pageModuleUID, $orgUID, $userUID, $items);
                }
                $this->dbwrite_model->deleteInTransaction('Transaction', 'TransactionsTbl', ['TransUID' => $transUID]);
                $this->dbwrite_model->deleteInTransaction('Transaction', 'TransDetailTbl',  ['TransUID' => $transUID]);
            } else {
                $numberFields = [];
                if ($uniqueNumber !== NULL) {
                    $numberFields = ['PrefixUID' => $prefixUID, 'TransNumber' => $transNumber, 'UniqueNumber' => $uniqueNumber];
                }
                $updateResp = $this->dbwrite_model->updateData(
                    'Transaction', 'TransactionsTbl',
                    array_merge($commonHeader, $numberFields),
                    ['TransUID' => $transUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
                );
                if ($updateResp->Error) throw new Exception($updateResp->Message);
                $this->dbwrite_model->updateData('Transaction', 'TransDetailTbl', $commonDetail, ['FinancialYear' => $financialYear, 'TransUID' => $transUID]);
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
                            'SourceTransProdUID'=> isset($item['sourceTransProdUID']) && $item['sourceTransProdUID'] > 0 ? (int)$item['sourceTransProdUID'] : NULL,
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

            $this->dbwrite_model->commitTransaction();
            $this->_saveAttachments($transUID);
            $this->_softDeleteAttachments($this->input->post('RemovedAttachIDs') ?? '');
            $this->cachehelper->touchCustomer($customerUID);
            $this->transactions_model->generateAndStorePdf(isset($newTransUID) ? $newTransUID : $transUID, $orgUID, $this->pageModuleUID);
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Sales Return updated successfully.';
        } catch (Exception $e) {
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
            if ($transUID <= 0) throw new Exception('Sales Return ID is required.');
            $this->load->model('transactions_model');
            $existing = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$existing) throw new Exception('Sales Return not found.');

            if (!in_array($existing->DocStatus, ['Draft', 'Approved', 'Partial', 'Paid'])) {
                throw new Exception('Only Draft, Approved, Partial, or fully paid sales returns can be deleted.');
            }

            // Check 1: block if credit was applied via applyCredit() — PaymentsTbl path
            $creditApplied = $this->_getSRCreditApplied($existing->UniqueNumber, $orgUID);
            if ($creditApplied > 0) {
                throw new Exception(
                    'This Sales Return has already been applied to one or more invoices. ' .
                    'Please reverse the credit allocations before deleting.'
                );
            }

            // Check 2: block if CN was applied via applyCreditNote() — CN Status path
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
                throw new Exception(
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
            $this->dbwrite_model->commitTransaction();

            $this->_recalcCustomerBalance($orgUID, (int)$existing->PartyUID, $userUID);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Sales Return deleted successfully.';
        } catch (Exception $e) {
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
            if ($srcUID <= 0) throw new Exception('Invalid sales return.');
            $this->load->model('transactions_model');
            $src = $this->transactions_model->getTransactionById($srcUID, $orgUID, $this->pageModuleUID);
            if (!$src) throw new Exception('Sales Return not found.');

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
                'TransType'         => 'Sales Return',
                'TransNumber'       => $nextNumber,
                'PartyType'         => $src->PartyType,
                'PartyUID'          => $src->PartyUID,
                'TransDate'         => $today,
                'TransYear'         => (int) date('Y'),
                'QuotationType'     => $src->QuotationType,
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
            $this->EndReturnData->TransUID = $newTransUID;
            $this->EndReturnData->EditURL  = '/salesreturns/edit/' . $newTransUID;
        } catch (Exception $e) {
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
            if ($transUID <= 0) throw new Exception('Invalid sales return.');

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
            if (!$existing) throw new Exception('Sales Return not found.');
            $current = $existing->DocStatus;
            if (!in_array($newStatus, $validTransitions[$current] ?? [])) throw new Exception("Cannot change status from {$current} to {$newStatus}.");

            $this->dbwrite_model->startTransaction();

            // ── Pre-cancel dependency checks (before any DB write) ───────────────
            $hasCashRefunds = false;
            $cancelAction   = '';
            $totalRefunded  = 0;

            if ($newStatus === 'Cancelled') {
                // Priority 1: Block if this SR's credit is still applied to any invoice.
                // User must manually reverse those allocations before cancelling.
                $creditApplied = $this->_getSRCreditApplied($existing->UniqueNumber, $orgUID);
                if ($creditApplied > 0) {
                    throw new Exception(
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
                        // No valid action supplied — tell the frontend to show the action dialog.
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
                        // Accept the refund as a business loss — mark payments written off
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

            // Commit BEFORE recalculating balance so ReadDB sees DocStatus='Cancelled'
            // and getCustomerTotalReturned correctly excludes the cancelled SR.
            $this->dbwrite_model->commitTransaction();

            if ($newStatus === 'Cancelled') {
                $balResult = $this->_recalcCustomerBalance($orgUID, (int)$existing->PartyUID, $userUID);
                if ($balResult) {
                    $this->EndReturnData->CustomerBalance     = $balResult['balance'];
                    $this->EndReturnData->CustomerBalanceType = $balResult['type'];
                }
            }

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

    public function getSRCancelDependencies() {
        $this->EndReturnData = new stdClass();
        try {
            $transUID = (int) $this->input->post('TransUID');
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;
            if ($transUID <= 0) throw new Exception('Invalid sales return.');

            $this->load->model('transactions_model');
            $existing = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$existing) throw new Exception('Sales Return not found.');

            $creditApplied = $this->_getSRCreditApplied($existing->UniqueNumber, $orgUID);
            $totalRefunded = $this->_getSRTotalRefunded($transUID, $orgUID);

            $this->EndReturnData->Error            = FALSE;
            $this->EndReturnData->HasCreditApplied = $creditApplied > 0;
            $this->EndReturnData->CreditAmount     = $creditApplied;
            $this->EndReturnData->HasRefunds       = $totalRefunded > 0;
            $this->EndReturnData->RefundAmount     = $totalRefunded;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function getSalesReturnDetail() {
        $this->EndReturnData = new stdClass();
        try {
            $transUID = (int) $this->input->get_post('TransUID');
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;
            if ($transUID <= 0) throw new Exception('Invalid sales return.');
            $this->load->model('transactions_model');
            $header = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$header) throw new Exception('Sales Return not found.');
            $items = $this->transactions_model->getTransactionItems($transUID, $orgUID);
            $this->load->model('organisation_model');
            $orgInfo          = $this->organisation_model->getOrgInfoCached($orgUID);
            $thermalCfgResult = $this->organisation_model->getThermalPrintConfig($orgUID);
            $printThemeResult = $this->organisation_model->getPrintThemeByType($orgUID, 'Sales Return');
            $this->EndReturnData->Error         = FALSE;
            $this->EndReturnData->Header        = $header;
            $this->EndReturnData->Items         = $items;
            $this->EndReturnData->OrgInfo       = $orgInfo->Data ?? null;
            $this->EndReturnData->ThermalConfig = $thermalCfgResult->Data ?? null;
            $this->EndReturnData->PrintTheme    = $printThemeResult->Data ?? null;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function getInvoiceItems() {
        $this->EndReturnData = new stdClass();
        try {
            $transUID = (int) $this->input->post('TransUID');
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;
            if ($transUID <= 0) throw new Exception('Invalid invoice.');

            $this->load->model('transactions_model');
            $header = $this->transactions_model->getTransactionById($transUID, $orgUID, 103);
            if (!$header) throw new Exception('Invoice not found.');
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

            $this->EndReturnData->Error   = false;
            $this->EndReturnData->Header  = $header;
            $this->EndReturnData->Items   = $items;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function getCustomerInvoices() {
        $this->EndReturnData = new stdClass();
        try {
            $customerUID = (int) $this->input->post('CustomerUID');
            $orgUID      = $this->pageData['JwtData']->Org->OrgUID;
            if ($customerUID <= 0) throw new Exception('Invalid customer.');

            $this->load->model('transactions_model');

            $this->EndReturnData->Error    = false;
            $this->EndReturnData->Invoices = $this->transactions_model->getCustomerInvoicesWithReturnableItems($customerUID, $orgUID);
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

            $this->_getDispatchAddresses($orgUID);

            $this->pageData['PaymentTypes']    = $this->transactions_model->getPaymentTypesList();
            $this->pageData['BankAccounts']    = $this->transactions_model->getOrgBankAccounts($orgUID);

            $this->load->view('transactions/salesreturns/forms/form', $this->pageData);
        } catch (Exception $e) {
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

            $this->load->view('transactions/salesreturns/forms/form', $this->pageData);
        } catch (Exception $e) {
            redirect('salesreturns', 'refresh');
        }
    }

    private function saveTransactionItems($transUID, $financialYear, $orgUID, $userUID, array $items, $seqOffset = 0) {
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
                'PrimaryUnitName'   => isset($item['primaryUnit'])    ? substr($item['primaryUnit'], 0, 20)  : NULL,
                'TaxDetailsUID'     => isset($item['taxDetailsUID'])  ? (int) $item['taxDetailsUID']          : 1,
                'TaxPercentage'     => (float) ($item['taxPercent']    ?? 0),
                'CGST'              => (float) ($item['cgstPercent']   ?? 0),
                'SGST'              => (float) ($item['sgstPercent']   ?? 0),
                'IGST'              => (float) ($item['igstPercent']   ?? 0),
                'DiscountTypeUID'   => isset($item['discountTypeUID']) ? (int) $item['discountTypeUID'] : NULL,
                'Discount'          => (float) ($item['discount']        ?? 0),
                'UnitPrice'         => $unitPrice,
                'SellingPrice'      => (float) ($item['sellingPrice']    ?? $unitPrice),
                'PurchasePrice'     => (float) ($item['purchasePrice']   ?? 0),
                'TaxableAmount'     => (float) ($item['line_total']      ?? 0),
                'CgstAmount'        => (float) ($item['cgstAmount']      ?? 0),
                'SgstAmount'        => (float) ($item['sgstAmount']      ?? 0),
                'IgstAmount'        => (float) ($item['igstAmount']      ?? 0),
                'TaxAmount'         => (float) ($item['taxAmount']       ?? 0),
                'DiscountAmount'    => (float) ($item['discount_amount']  ?? 0),
                'NetAmount'         => (float) ($item['net_total']        ?? 0),
                'QuantityConverted' => 0,
                'SourceTransProdUID'=> isset($item['sourceTransProdUID']) && $item['sourceTransProdUID'] > 0 ? (int)$item['sourceTransProdUID'] : NULL,
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








    public function getPaymentAttachments() {
        $this->EndReturnData = new stdClass();
        try {
            $transUID = (int) $this->input->post('TransUID');
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;
            if ($transUID <= 0) throw new Exception('Invalid transaction.');
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
        } catch (Exception $e) {
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

            if ($transUID <= 0)       throw new Exception('Invalid transaction.');
            if ($paymentTypeUID <= 0) throw new Exception('Please select a payment type.');
            if ($amount <= 0)         throw new Exception('Amount must be greater than 0.');

            $this->load->model('transactions_model');
            $existing = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$existing) throw new Exception('Sales Return not found.');
            if ($existing->DocStatus === 'Draft')                          throw new Exception('Cannot record payment for a Draft.');
            if (in_array($existing->DocStatus, ['Cancelled', 'Rejected'])) throw new Exception('Sales Return is cancelled.');

            $alreadyPaid = $this->transactions_model->getSumPaidForTransaction($transUID, $orgUID);
            $pending     = max(0, round((float)$existing->NetAmount - $alreadyPaid, 2));

            if ($amount > $pending + 0.01) {
                throw new Exception('Amount exceeds pending balance (' . $pending . ').');
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

            $balanceAmount = max(0, round((float)$existing->NetAmount - $newTotalPaid, 2));
            $this->dbwrite_model->updateTransIsFullyPaid($transUID, $isFullyPaid, $newTotalPaid, $balanceAmount, $userUID);
            $this->dbwrite_model->updateTransDocStatus($transUID, $orgUID, $newStatus, $userUID);

            // ── Reduce linked Credit Note by the payment amount ──────────────
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
                $newCNAmount = round(max(0, (float)$cn->Amount - $amount), 2);
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

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    private function _savePaymentRecord($transUID, $orgUID, $userUID, $partyType, $partyUID, $billTotal, $PostData, $transDate = null) {
        $rowsJson    = getPostValue($PostData, 'PaymentRows') ?: '';
        $isFullyPaid = (int) getPostValue($PostData, 'IsFullyPaid') === 1 ? 1 : 0;
        if (empty($rowsJson)) return ['totalPaid' => 0, 'firstPaymentUID' => null];
        $rows = json_decode($rowsJson, true);
        if (!is_array($rows) || empty($rows)) return ['totalPaid' => 0, 'firstPaymentUID' => null];

        $paymentDate   = $transDate ?: date('Y-m-d');
        $totalPaid     = array_sum(array_column($rows, 'amount'));
        $payTransYear  = (int) date('Y', strtotime($paymentDate));
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
            if ($paymentTypeUID <= 0 || $amount <= 0) continue;

            $rowExcess = 0;
            if ($idx === count($rows) - 1 && $billTotal > 0 && $totalPaid > $billTotal) {
                $rowExcess = round($totalPaid - $billTotal, 4);
            }

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
                'PaymentDirection' => 'Out',
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
            if ($srUID <= 0) throw new Exception('Invalid sales return.');

            $this->load->model('transactions_model');
            $sr = $this->transactions_model->getTransactionById($srUID, $orgUID, $this->pageModuleUID);
            if (!$sr) throw new Exception('Sales Return not found.');

            $customerUID = (int) $sr->PartyUID;

            $this->EndReturnData->Error    = false;
            $this->EndReturnData->Invoices = $this->transactions_model->getPendingInvoicesForCustomer($customerUID, $orgUID);
        } catch (Exception $e) {
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

            if ($srUID <= 0)      throw new Exception('Invalid sales return.');
            if ($invoiceUID <= 0) throw new Exception('Please select an invoice.');
            if ($amount <= 0)     throw new Exception('Amount must be greater than 0.');

            $sr = $this->transactions_model->getTransactionById($srUID, $orgUID, $this->pageModuleUID);
            if (!$sr) throw new Exception('Sales Return not found.');
            if (in_array($sr->DocStatus, ['Draft', 'Cancelled', 'Rejected'])) {
                throw new Exception('Cannot apply credit for this Sales Return.');
            }

            $srPaid    = (float)($sr->PaidAmount    ?? 0);
            $srBalance = max(0, round((float)$sr->NetAmount - $srPaid, 2));
            if ($srBalance <= 0) throw new Exception('No credit balance available on this Sales Return.');

            $invoice = $this->transactions_model->getTransactionById($invoiceUID, $orgUID, 103);
            if (!$invoice) throw new Exception('Invoice not found.');
            if ($invoice->PartyUID != $sr->PartyUID) throw new Exception('Invoice does not belong to the same customer.');
            if (in_array($invoice->DocStatus, ['Draft', 'Cancelled', 'Paid'])) {
                throw new Exception('This invoice cannot receive a credit adjustment.');
            }

            $invPaid    = (float)($invoice->PaidAmount    ?? 0);
            $invBalance = max(0, round((float)$invoice->NetAmount - $invPaid, 2));
            if ($invBalance <= 0) throw new Exception('Invoice has no pending balance.');

            $maxAmount = min($srBalance, $invBalance);
            if ($amount > $maxAmount + 0.01) {
                throw new Exception('Amount exceeds available credit (' . number_format($maxAmount, 2) . ').');
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
            $newInvPaid    = round($invPaid + $amount, 2);
            $newInvBalance = max(0, round((float)$invoice->NetAmount - $newInvPaid, 2));
            $invFullyPaid  = ($invoice->NetAmount > 0 && $newInvBalance <= 0) ? 1 : 0;
            $invStatus     = $invFullyPaid ? 'Paid' : 'Partial';
            $this->dbwrite_model->updateTransIsFullyPaid($invoiceUID, $invFullyPaid, $newInvPaid, $newInvBalance, $userUID);
            $this->dbwrite_model->updateTransDocStatus($invoiceUID, $orgUID, $invStatus, $userUID);

            // Update Sales Return
            $newSrPaid    = round($srPaid + $amount, 2);
            $newSrBalance = max(0, round((float)$sr->NetAmount - $newSrPaid, 2));
            $srFullyPaid  = ($sr->NetAmount > 0 && $newSrBalance <= 0) ? 1 : 0;
            $srNewStatus  = $srFullyPaid ? 'Paid' : ($newSrPaid > 0 ? 'Partial' : $sr->DocStatus);
            $this->dbwrite_model->updateTransIsFullyPaid($srUID, $srFullyPaid, $newSrPaid, $newSrBalance, $userUID);
            if ($srNewStatus !== $sr->DocStatus) {
                $this->dbwrite_model->updateTransDocStatus($srUID, $orgUID, $srNewStatus, $userUID);
            }

            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Credit of ' . number_format($amount, 2) . ' applied to invoice ' . ($invoice->UniqueNumber ?? '#' . $invoiceUID) . '.';

        } catch (Exception $e) {
            if (isset($this->dbwrite_model)) $this->dbwrite_model->rollbackTransaction();
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
            if ($uploadResult->Error) continue;
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

    private function _annotateSRListWithCancelDeps(array &$rows, $orgUID) {
        if (empty($rows)) return;

        $transUIDs     = array_map(fn($r) => (int)$r->TransUID, $rows);
        $uniqueNumbers = array_values(array_filter(array_map(fn($r) => $r->UniqueNumber ?? null, $rows)));

        foreach ($rows as $r) {
            $r->CancelCashRefunded  = 0;
            $r->CancelCreditApplied = 0;
        }

        $this->load->model('transactions_model');
        $deps = $this->transactions_model->getSRCancelDepsMap($transUIDs, $uniqueNumbers);

        foreach ($rows as $r) {
            $r->CancelCashRefunded  = $deps['cashMap'][(int)$r->TransUID]        ?? 0;
            $r->CancelCreditApplied = $deps['creditMap'][$r->UniqueNumber ?? ''] ?? 0;
        }
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

            $newPaid     = max(0, round((float)($invoice->PaidAmount ?? 0) - $creditAmt, 2));
            $newBalance  = max(0, round((float)$invoice->NetAmount - $newPaid, 2));
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

    private function _recalcCustomerBalance($orgUID, $custUID, $userUID) {
        $this->load->library('customerbalance');
        return $this->customerbalance->recalcAndSync($orgUID, $custUID, $userUID);
    }

}
