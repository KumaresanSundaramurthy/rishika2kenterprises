<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Quotations extends CI_Controller {

    public $pageData = array();
    private $EndReturnData;
    private $pageModuleUID;

    public function __construct() {
        parent::__construct();

        $this->pageModuleUID = 101;
        $this->load->helper('transaction');

    }

    public function index() {

        try {

            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;

            $GeneralSettings = ($this->redis_cache->get('Redis_UserGenSettings')->Value) ?? new stdClass();
            $limit = $GeneralSettings->RowLimit ?? 10;
            $this->pageData['JwtData']->GenSettings = $GeneralSettings;
            $this->pageData['DiscTypeInfo'] = [];

            $this->load->model('transactions_model');
            $allData = $this->transactions_model->getTransactionPageList($limit, 0, $this->pageModuleUID, [], 0);
            $allDataCount = $this->transactions_model->getTransactionCount($this->pageModuleUID, []);
            
            $this->pageData['ModRowData'] = $this->load->view('transactions/quotations/list', ['DataLists' => $allData, 'SerialNumber' => 0, 'JwtData'   => $this->pageData['JwtData']], TRUE);
            $this->pageData['ModPagination'] = $this->globalservice->buildPagePaginationHtml('/quotations/getQuotationsPageDetails', $allDataCount, 1, $limit);
            $this->pageData['ModAllCount'] = $allDataCount;
            
            $this->load->view('transactions/quotations/view', $this->pageData);

        } catch (Exception $e) {
            redirect('dashboard', 'refresh');
        }
        
    }

    public function getQuotationsPageDetails($pageNo = 0) {

        $this->EndReturnData = new stdClass();
		try {

            $pageNo = (int) $pageNo;
            if ($pageNo < 1) {
                $pageNo = 1;
            }

			$limit = (int) $this->input->post('RowLimit') ?: 10;
            $offset = ($pageNo - 1) * $limit;
            $filter = $this->input->post('Filter') ?: [];

			$this->load->model('transactions_model');
            $allData = $this->transactions_model->getTransactionPageList($limit, $offset, $this->pageModuleUID, $filter, 0);
            $allDataCount = $this->transactions_model->getTransactionCount($this->pageModuleUID, $filter);

            $this->pageData['JwtData']->GenSettings = ($this->redis_cache->get('Redis_UserGenSettings')->Value) ?? new stdClass();
            
            $rowHtml = $this->load->view('transactions/quotations/list', [
                'DataLists'    => $allData,
                'SerialNumber' => ($pageNo - 1) * $limit,
                'JwtData'      => $this->pageData['JwtData'],
            ], true);
            
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->RecordHtmlData = $rowHtml;
            $this->EndReturnData->Pagination = $this->globalservice->buildPagePaginationHtml('/quotations/getQuotationsPageDetails', $allDataCount, $pageNo, $limit);
            $this->EndReturnData->TotalCount = $allDataCount;

		} catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function addQuotation() {

        $this->EndReturnData = new stdClass();
        $ErrorInForm = '';
        try {

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData = $this->input->post();
            $userUID  = $this->pageData['JwtData']->User->UserUID;
            $orgUID   = $this->pageData['JwtData']->User->OrgUID;

            // --- Validation ---
            $this->load->model('formvalidation_model');
            $ErrorInForm = $this->formvalidation_model->quotationValidateForm($PostData);
            if (!empty($ErrorInForm)) throw new Exception($ErrorInForm);

            $itemsJson = getPostValue($PostData, 'Items');
            $ErrorInForm = $this->formvalidation_model->validateQuotationItems($itemsJson);
            if (!empty($ErrorInForm)) throw new Exception($ErrorInForm);

            $customerUID            = (int)   getPostValue($PostData, 'customerSearch');
            $prefixUID              = (int)   getPostValue($PostData, 'transPrefixSelect');
            $transNumber            = (int)   getPostValue($PostData, 'transNumber');   // raw integer
            $transDate              =         getPostValue($PostData, 'transDate');
            $validityDate           =         getPostValue($PostData, 'validityDate');
            $validityDays           = (int)   getPostValue($PostData, 'validityDays', 'Array', 0);
            $items                  = json_decode($itemsJson, true);
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
            $isDraft   = getPostValue($PostData, 'action') === 'draft';
            $status    = $isDraft ? 'Draft' : 'Pending';

            // Auto-compute validityDate from settings if not provided
            if (empty($validityDate) && $validityDays > 0) {
                $validityDate = date('Y-m-d', strtotime($transDate . " +{$validityDays} days"));
            }

            $financialYear = (int) date('Y', strtotime($transDate));
            $this->load->model('transactions_model');

            // --- Draft: no number assigned yet ---
            if ($isDraft) {
                $uniqueNumber = NULL;
                $transNumber  = NULL;
                $prefixUID    = NULL;
            } else {
                if ($transNumber <= 0) throw new Exception('Transaction number must be greater than 0.');

                // Resolve prefix & build UniqueNumber
                $prefixData = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.PrefixUID' => $prefixUID, 'Prefix.OrgUID' => $orgUID]);
                if (empty($prefixData->Data)) throw new Exception('Invalid prefix selected.');
                $prefix = $prefixData->Data[0];

                // Duplicate check (padding-agnostic)
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

            // --- Insert header ---
            $this->load->model('dbwrite_model');
            $headerData = [
                'OrgUID'                => $orgUID,
                'ModuleUID'             => $this->pageModuleUID,
                'PrefixUID'             => $prefixUID,
                'UniqueNumber'          => $uniqueNumber,
                'TransType'             => 'Quotation',
                'TransNumber'           => $transNumber,
                'PartyType'             => 'C',
                'PartyUID'              => $customerUID,
                'TransDate'             => $transDate,
                'QuotationType'         => getPostValue($PostData, 'quotationType') ?: NULL,
                'DispatchFromUID'       => ($dfUID = (int) getPostValue($PostData, 'dispatchFrom')) > 0 ? $dfUID : NULL,
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
                'DocStatus'             => $status,
                'IsActive'              => 1,
                'IsDeleted'             => 0,
                'CreatedBy'             => $userUID,
                'UpdatedBy'             => $userUID,
            ];

            $insertResp = $this->dbwrite_model->insertData('Transaction', 'TransactionsTbl', $headerData);
            if ($insertResp->Error) throw new Exception($insertResp->Message);

            $transUID = $insertResp->ID;

            // --- Insert detail row (TransDetailTbl — notes, terms, validity, charges) ---
            $additionalChargesJson = $this->buildAdditionalChargesJson($PostData);
            $detailData = [
                'FinancialYear'     => $financialYear,
                'TransUID'          => $transUID,
                'ValidityDays'      => $validityDays ?: NULL,
                'ValidityDate'      => $validityDate ?: NULL,
                'Reference'         => getPostValue($PostData, 'referenceDetails') ?: NULL,
                'Notes'             => getPostValue($PostData, 'transNotes') ?: NULL,
                'TermsConditions'   => getPostValue($PostData, 'transTermsCond') ?: NULL,
                'AdditionalCharges' => $additionalChargesJson,
            ];
            $this->dbwrite_model->insertData('Transaction', 'TransDetailTbl', $detailData);

            // --- Insert line items + tax records ---
            $this->saveQuotationItems($transUID, $financialYear, $orgUID, $userUID, $items);

            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Quotation created successfully.';
            $this->EndReturnData->TransUID = $transUID;

        } catch (InvalidArgumentException $e) {
            $this->dbwrite_model->rollbackTransaction();
            if ($e->getMessage() === 'VALIDATION_ERROR') {
                $this->EndReturnData->Error = true;
                $this->EndReturnData->Message = strip_tags($ErrorInForm);
                $this->EndReturnData->Errors = 'Please correct the highlighted errors.';
            } else {
                throw $e;
            }
        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function updateQuotation() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData = $this->input->post();
            $userUID  = $this->pageData['JwtData']->User->UserUID;
            $orgUID   = $this->pageData['JwtData']->User->OrgUID;

            // --- Validation ---
            $transUID = (int) getPostValue($PostData, 'TransUID');
            if ($transUID <= 0) throw new Exception('Quotation ID is required.');

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
            $validityDate           =         getPostValue($PostData, 'validityDate');
            $validityDays           = (int)   getPostValue($PostData, 'validityDays', 'Array', 0);
            $items                  = json_decode($itemsJson, true);
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
            $status                 = $isDraft ? 'Draft' : 'Pending';

            if (empty($validityDate) && $validityDays > 0) {
                $validityDate = date('Y-m-d', strtotime($transDate . " +{$validityDays} days"));
            }

            $financialYear = (int) date('Y', strtotime($transDate));

            // Load existing row to check current DocStatus (needed for draft→pending promotion)
            $this->load->model('transactions_model');
            $existing = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$existing) throw new Exception('Quotation not found.');

            // --- Build UniqueNumber when promoting Draft → Pending ---
            $uniqueNumber = NULL;
            if ($existing->DocStatus === 'Draft' && !$isDraft) {
                if ($prefixUID <= 0) throw new Exception('Please select a prefix to finalize this quotation.');
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

            // --- Shared header fields (used in both update and clone paths) ---
            $commonHeader = [
                'OrgUID'            => $orgUID,
                'ModuleUID'         => $this->pageModuleUID,
                'PartyType'         => 'C',
                'PartyUID'          => $customerUID,
                'TransDate'         => $transDate,
                'TransType'         => 'Quotation',
                'QuotationType'     => getPostValue($PostData, 'quotationType') ?: NULL,
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
                'DocStatus'         => $status,
                'UpdatedBy'         => $userUID,
            ];

            $commonDetail = [
                'ValidityDays'      => $validityDays ?: NULL,
                'ValidityDate'      => $validityDate ?: NULL,
                'Reference'         => getPostValue($PostData, 'referenceDetails') ?: NULL,
                'Notes'             => getPostValue($PostData, 'transNotes') ?: NULL,
                'TermsConditions'   => getPostValue($PostData, 'transTermsCond') ?: NULL,
                'AdditionalCharges' => $additionalChargesJson,
            ];

            // --- CLONE PATH: Draft being saved as Pending and newer records exist ---
            // We insert a new TransactionsTbl row (gets a higher auto-increment TransUID)
            // so it naturally sorts to the top of the DESC list, then hard-delete the old draft.
            if ($existing->DocStatus === 'Draft' && !$isDraft
                && $this->transactions_model->hasNewerTransactions($transUID, $orgUID, $this->pageModuleUID)) {

                // Insert new header row
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
                $newTransUID = $insertResp->ID;

                // Insert new detail row
                $this->dbwrite_model->insertData('Transaction', 'TransDetailTbl', array_merge($commonDetail, [
                    'FinancialYear' => $financialYear,
                    'TransUID'      => $newTransUID,
                ]));

                // Soft-delete old items (audit trail), insert new items under new TransUID
                $this->dbwrite_model->updateData(
                    'Transaction', 'TransProductsTbl',
                    ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID],
                    ['TransUID' => $transUID, 'IsDeleted' => 0]
                );
                $this->saveQuotationItems($newTransUID, $financialYear, $orgUID, $userUID, $items);

                // Hard-delete old draft header and its detail — only TransactionsTbl drives list order
                $this->dbwrite_model->deleteInTransaction('Transaction', 'TransactionsTbl', ['TransUID' => $transUID]);
                $this->dbwrite_model->deleteInTransaction('Transaction', 'TransDetailTbl',  ['TransUID' => $transUID]);

            } else {
                // --- NORMAL UPDATE PATH (draft stays draft, or no newer records exist) ---
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

                // Soft-delete old items and insert new ones at offset sequences
                $this->dbwrite_model->updateData(
                    'Transaction', 'TransProductsTbl',
                    ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID],
                    ['TransUID' => $transUID, 'IsDeleted' => 0]
                );
                $seqOffset = $this->transactions_model->getMaxItemSequence($transUID);
                $this->saveQuotationItems($transUID, $financialYear, $orgUID, $userUID, $items, $seqOffset);
            }

            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Quotation updated successfully.';

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function deleteQuotation() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData = $this->input->post();
            $userUID  = $this->pageData['JwtData']->User->UserUID;
            $orgUID   = $this->pageData['JwtData']->User->OrgUID;

            $transUID = (int) getPostValue($PostData, 'TransUID');
            if ($transUID <= 0) throw new Exception('Quotation ID is required.');

            $this->load->model('transactions_model');
            $existing = $this->transactions_model->getTransactionPageList(1, 0, $this->pageModuleUID, [
                'TransUID' => $transUID,
                'OrgUID'   => $orgUID,
            ]);
            if (empty($existing)) throw new Exception('Quotation not found.');

            $now = time();

            // Soft-delete line items
            $this->dbwrite_model->updateData(
                'Transaction', 'TransProductsTbl',
                ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID, 'UpdatedOn' => $now],
                ['TransUID' => $transUID, 'IsDeleted' => 0]
            );

            // Soft-delete header
            $deleteData = $this->globalservice->baseDeleteArrayDetails();
            $deleteData['IsActive'] = 0;

            $deleteResp = $this->dbwrite_model->updateData(
                'Transaction', 'TransactionsTbl', $deleteData,
                ['TransUID' => $transUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );
            if ($deleteResp->Error) throw new Exception($deleteResp->Message);

            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Quotation deleted successfully.';

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function duplicateQuotation() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData = $this->input->post();
            $srcUID   = (int) getPostValue($PostData, 'TransUID');
            $userUID  = $this->pageData['JwtData']->User->UserUID;
            $orgUID   = $this->pageData['JwtData']->User->OrgUID;

            if ($srcUID <= 0) throw new Exception('Invalid quotation.');

            $this->load->model('transactions_model');
            $src = $this->transactions_model->getTransactionById($srcUID, $orgUID, $this->pageModuleUID);
            if (!$src) throw new Exception('Quotation not found.');

            // Next number for the same prefix
            $nextNumber   = $this->transactions_model->getNextTransactionNumber($src->PrefixUID, $orgUID, $this->pageModuleUID);
            $prefixResult = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.PrefixUID' => $src->PrefixUID, 'Prefix.OrgUID' => $orgUID]);
            $prefix       = $prefixResult->Data[0] ?? null;
            if (!$prefix) throw new Exception('Prefix not found.');

            // Build UniqueNumber
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

            // Insert duplicate header
            $headerData = [
                'OrgUID'            => $orgUID,
                'ModuleUID'         => $this->pageModuleUID,
                'PrefixUID'         => $src->PrefixUID,
                'UniqueNumber'      => $uniqueNumber,
                'TransType'         => $src->TransType,
                'TransNumber'       => $nextNumber,
                'PartyType'         => $src->PartyType,
                'PartyUID'          => $src->PartyUID,
                'TransDate'         => $today,
                'QuotationType'     => $src->QuotationType,
                'DispatchFromUID'   => $src->DispatchFromUID ?? NULL,
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

            // Insert detail
            $detailData = [
                'FinancialYear'     => (int) date('Y'),
                'TransUID'          => $newTransUID,
                'ValidityDays'      => $src->ValidityDays  ?? NULL,
                'ValidityDate'      => NULL,
                'Reference'         => $src->Reference     ?? NULL,
                'Notes'             => $src->Notes         ?? NULL,
                'TermsConditions'   => $src->TermsConditions ?? NULL,
                'AdditionalCharges' => $src->AdditionalChargesJson ?? NULL,
            ];
            $this->dbwrite_model->insertData('Transaction', 'TransDetailTbl', $detailData);

            // Copy line items
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
                    'CreatedOn'       => $now,
                    'UpdatedOn'       => $now,
                ];
                $this->dbwrite_model->insertData('Transaction', 'TransProductsTbl', $itemRow);
            }

            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Message    = 'Quotation duplicated as ' . $uniqueNumber . '.';
            $this->EndReturnData->TransUID   = $newTransUID;
            $this->EndReturnData->EditURL    = '/quotations/edit/' . $newTransUID;

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function convertQuotationToInvoice() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');
            $PostData = $this->input->post();
            $transUID = (int) getPostValue($PostData, 'TransUID');
            $userUID  = $this->pageData['JwtData']->User->UserUID;
            $orgUID   = $this->pageData['JwtData']->User->OrgUID;

            if ($transUID <= 0) throw new Exception('Invalid quotation.');

            // Mark quotation as Converted
            $this->dbwrite_model->startTransaction();
            $resp = $this->dbwrite_model->updateData(
                'Transaction', 'TransactionsTbl',
                ['Status' => 'Converted', 'DocStatus' => 'Converted', 'UpdatedBy' => $userUID, 'UpdatedOn' => date('Y-m-d H:i:s')],
                ['TransUID' => $transUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );
            if ($resp->Error) throw new Exception($resp->Message);
            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error     = FALSE;
            $this->EndReturnData->Message   = 'Quotation marked as converted.';
            $this->EndReturnData->RedirectURL = '/invoices/create?fromQuotation=' . $transUID;

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function updateQuotationStatus() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');
            $PostData  = $this->input->post();
            $transUID  = (int) getPostValue($PostData, 'TransUID');
            $newStatus = trim(getPostValue($PostData, 'Status'));
            $userUID   = $this->pageData['JwtData']->User->UserUID;
            $orgUID    = $this->pageData['JwtData']->User->OrgUID;

            if ($transUID <= 0) throw new Exception('Invalid quotation.');

            $validTransitions = [
                'Pending'   => ['Accepted', 'Partial', 'Rejected'],
                'Accepted'  => ['Pending',  'Partial', 'Rejected'],
                'Partial'   => ['Accepted', 'Pending', 'Rejected'],
                'Draft'     => ['Pending'],
                'Rejected'  => [],
                'Converted' => [],
            ];

            $this->load->model('transactions_model');
            $existing = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$existing) throw new Exception('Quotation not found.');

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

    public function getQuotationDetail() {

        $this->EndReturnData = new stdClass();
        try {

            $transUID = (int) $this->input->get_post('TransUID');
            $orgUID   = $this->pageData['JwtData']->User->OrgUID;

            if ($transUID <= 0) throw new Exception('Invalid quotation.');

            $this->load->model('transactions_model');
            $header = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$header) throw new Exception('Quotation not found.');

            $items = $this->transactions_model->getTransactionItems($transUID, $orgUID);

            $this->load->model('organisation_model');
            $orgInfo          = $this->organisation_model->getOrgForReceipt($orgUID);
            $thermalCfgResult = $this->organisation_model->getThermalPrintConfig($orgUID);
            $printThemeResult = $this->organisation_model->getPrintThemeByType($orgUID, 'Quotation');

            $this->EndReturnData->Error        = FALSE;
            $this->EndReturnData->Header       = $header;
            $this->EndReturnData->Items        = $items;
            $this->EndReturnData->OrgInfo      = $orgInfo->Data ?? null;
            $this->EndReturnData->ThermalConfig = $thermalCfgResult->Data ?? null;
            $this->EndReturnData->PrintTheme   = $printThemeResult->Data ?? null;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    /**
     * Insert line items into TransProductsTbl.
     *
     * Key JS→PHP field name mappings:
     *   JS item.quantity        → PHP $qty          (BillManager uses 'quantity', not 'qty')
     *   JS item.discount_amount → PHP $discountAmount (underscore, not camel)
     *   JS item.line_total      → PHP $lineTaxable   (unit_price×qty, BEFORE tax)
     *   JS item.net_total       → PHP $netAmount      (selling_price×qty, WITH tax)
     */
    private function saveQuotationItems($transUID, $financialYear, $orgUID, $userUID, array $items, $seqOffset = 0) {

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

        // Single batch INSERT instead of one query per item — much faster for large carts
        $batchResp = $this->dbwrite_model->insertBatchInTransaction('Transaction', 'TransProductsTbl', $rows);
        if ($batchResp->Error) throw new Exception($batchResp->Message);

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

            // Prefixes are org-level (shared across all transaction types)
            $prefixResult                         = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID]);
            $this->pageData['PrefixData']         = $prefixResult->Data ?? [];
            $this->pageData['TransPageSettings']  = $this->transactions_model->getTransPageSettings(['pageSettings.ModuleUID' => $this->pageModuleUID]);

            // Preload next transaction number for each prefix — embedded in the view as data-attrs,
            // so the JS never needs an AJAX call to get the next number.
            $nextNumberMap = [];
            foreach ($this->pageData['PrefixData'] as $pd) {
                $nextNumberMap[(int)$pd->PrefixUID] = $this->transactions_model->getNextTransactionNumber(
                    $pd->PrefixUID, $orgUID, $this->pageModuleUID
                );
            }
            $this->pageData['NextNumberMap'] = $nextNumberMap;

            // Dispatch address: Shipping first, Billing as fallback, NULL if neither
            $this->load->model('organisation_model');
            $dispatchAddrResult                  = $this->organisation_model->getOrgDispatchAddress($orgUID);
            $this->pageData['DispatchAddress']   = $dispatchAddrResult->Data ?? NULL;

            $this->load->model('global_model');
            $GetCountryInfo = $this->global_model->getCountryInfo();
            $this->pageData['CountryInfo'] = $GetCountryInfo->Error === FALSE ? $GetCountryInfo->Data : [];

            $this->pageData['StateData'] = [];
            $this->pageData['CityData'] = [];

            $OrgCountryISO2 = $this->pageData['JwtData']->User->OrgCISO2;
            if(!empty($OrgCountryISO2)) {
                $StateInfo = $this->global_model->getStateofCountry($OrgCountryISO2);
                if($StateInfo->Error === FALSE) {
                    $this->pageData['StateData'] = $StateInfo->Data;
                }
                $CityInfo = $this->global_model->getCityofCountry($OrgCountryISO2);
                if($CityInfo->Error === FALSE) {
                    $this->pageData['CityData'] = $CityInfo->Data;
                }
            }

            /** Product Details */
            $this->pageData['PrimaryUnitInfo'] = $this->global_model->getPrimaryUnitInfo()->Data ?? [];
            $this->pageData['DiscTypeInfo']    = $this->global_model->getDiscountTypeInfo()->Data ?? [];
            $this->pageData['ProdTypeInfo']    = $this->global_model->getProductTypeInfo()->Data ?? [];
            $this->pageData['ProdTaxInfo']     = $this->global_model->getProductTaxInfo()->Data ?? [];
            $this->pageData['TaxDetInfo']      = $this->global_model->getTaxDetailsInfo()->Data ?? [];
            
            $this->load->model('products_model');
            $this->pageData['SizeInfo']   = $this->products_model->getSizeDetails([]) ?? [];
            $this->pageData['BrandInfo']  = $this->products_model->getBrandDetails([]) ?? [];
            $this->pageData['fltCategoryData'] = $this->products_model->getCategoriesDetails([]) ?? [];

            $this->pageData['fltStorageData'] = [];
            if (!empty($this->pageData['JwtData']->GenSettings->EnableStorage)) {
                $this->load->model('storage_model');
                $this->pageData['fltStorageData'] = $this->storage_model->getStorageDetails([]) ?? [];
            }
            
            $this->load->view('transactions/quotations/forms/add', $this->pageData);

        } catch (Exception $e) {
            redirect('dashboard', 'refresh');
        }

    }

    public function edit($transUID = 0) {

        try {

            $transUID = (int) $transUID;
            if ($transUID <= 0) redirect('quotations', 'refresh');

            $GeneralSettings = $this->redis_cache->get('Redis_UserGenSettings')->Value ?? NULL;
            $this->pageData['JwtData']->GenSettings = $GeneralSettings;

            $orgUID = $this->pageData['JwtData']->User->OrgUID;
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;

            $this->load->model('transactions_model');

            // Load the quotation header + detail fields
            $quotData = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$quotData) redirect('quotations', 'refresh');

            // Load the line items
            $quotItems = $this->transactions_model->getTransactionItems($transUID, $orgUID);

            // Load the party address information
            $this->load->model('customers_model');
            $custAddr = $this->customers_model->getCustomerAddress(['CustAddress.CustomerUID' => $quotData->PartyUID, 'CustAddress.OrgUID' => $orgUID]);
            $shipping = current(array_filter($custAddr, fn($a) => $a->AddressType === 'Shipping'));
            $billing  = current(array_filter($custAddr, fn($a) => $a->AddressType === 'Billing'));
            $this->pageData['CustAddr'] = $shipping ?: ($billing ?: ($custAddr[0] ?? null));

            $this->pageData['QuotData']  = $quotData;
            $this->pageData['QuotItems'] = $quotItems;

            // Prefix data
            $prefixResult                        = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID]);
            $this->pageData['PrefixData']        = $prefixResult->Data ?? [];
            $this->pageData['TransPageSettings'] = $this->transactions_model->getTransPageSettings(['pageSettings.ModuleUID' => $this->pageModuleUID]);

            $nextNumberMap = [];
            foreach ($this->pageData['PrefixData'] as $pd) {
                $nextNumberMap[(int)$pd->PrefixUID] = $this->transactions_model->getNextTransactionNumber(
                    $pd->PrefixUID, $orgUID, $this->pageModuleUID
                );
            }
            $this->pageData['NextNumberMap'] = $nextNumberMap;

            // Dispatch address
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
            $this->pageData['SizeInfo']        = $this->products_model->getSizeDetails([]) ?? [];
            $this->pageData['BrandInfo']       = $this->products_model->getBrandDetails([]) ?? [];
            $this->pageData['fltCategoryData'] = $this->products_model->getCategoriesDetails([]) ?? [];

            $this->pageData['fltStorageData'] = [];
            if (!empty($this->pageData['JwtData']->GenSettings->EnableStorage)) {
                $this->load->model('storage_model');
                $this->pageData['fltStorageData'] = $this->storage_model->getStorageDetails([]) ?? [];
            }

            $this->load->view('transactions/quotations/forms/edit', $this->pageData);

        } catch (Exception $e) {
            redirect('quotations', 'refresh');
        }

    }

    // ── Thermal Print Config ──────────────────────────────────────────────────
    public function thermalPrintConfig() {

        try {

            $orgUID = $this->pageData['JwtData']->User->OrgUID;

            $this->load->model('organisation_model');
            $cfgResult = $this->organisation_model->getThermalPrintConfig($orgUID);
            $this->pageData['ThermalConfig'] = $cfgResult->Data ?? null;

            $this->load->view('transactions/quotations/thermal_config', $this->pageData);

        } catch (Exception $e) {
            redirect('quotations', 'refresh');
        }

    }

    public function saveThermalPrintConfig() {

        $this->EndReturnData = new stdClass();
        try {

            $PostData = $this->input->post();
            $orgUID   = $this->pageData['JwtData']->User->OrgUID;
            $userUID  = $this->pageData['JwtData']->User->UserUID;

            $configData = [
                'PaperWidth'       => in_array(getPostValue($PostData, 'PaperWidth'), ['58mm', '80mm'])
                                        ? getPostValue($PostData, 'PaperWidth') : '80mm',
                'HeaderLine1'      => substr(getPostValue($PostData, 'HeaderLine1') ?: '', 0, 100) ?: NULL,
                'HeaderLine2'      => substr(getPostValue($PostData, 'HeaderLine2') ?: '', 0, 100) ?: NULL,
                'HeaderLine3'      => substr(getPostValue($PostData, 'HeaderLine3') ?: '', 0, 100) ?: NULL,
                'ShowGSTIN'        => (int)(bool)getPostValue($PostData, 'ShowGSTIN'),
                'ShowMobile'       => (int)(bool)getPostValue($PostData, 'ShowMobile'),
                'ShowLogo'         => (int)(bool)getPostValue($PostData, 'ShowLogo'),
                'ShowHSN'          => (int)(bool)getPostValue($PostData, 'ShowHSN'),
                'ShowTaxBreakdown' => (int)(bool)getPostValue($PostData, 'ShowTaxBreakdown'),
                'FooterMessage'    => substr(getPostValue($PostData, 'FooterMessage') ?: '', 0, 200) ?: NULL,
                'UpdatedBy'        => $userUID,
            ];

            $this->load->model('organisation_model');
            $this->load->model('dbwrite_model');

            $existing = $this->organisation_model->getThermalPrintConfig($orgUID);

            if (!empty($existing->Data)) {
                $this->dbwrite_model->updateData(
                    'Organisation', 'ThermalPrintConfigTbl', $configData,
                    ['OrgUID' => $orgUID, 'IsDeleted' => 0]
                );
            } else {
                $configData['OrgUID']    = $orgUID;
                $configData['CreatedBy'] = $userUID;
                $configData['IsActive']  = 1;
                $configData['IsDeleted'] = 0;
                $this->dbwrite_model->insertData('Organisation', 'ThermalPrintConfigTbl', $configData);
            }

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Thermal print settings saved.';

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

}