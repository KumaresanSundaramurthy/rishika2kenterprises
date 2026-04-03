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
            
            $this->load->view('transactions/quotations/view', $this->pageData);

        } catch (Exception $e) {
            redirect('dashboard', 'refresh');
        }
        
    }

    public function getQuotationsPageDetails($pageNo = 0) {

        $this->EndReturnData = new stdClass();
		try {

			$limit = (int) $this->input->post('RowLimit') ?: 10;
            $offset = max(0, ($pageNo - 1)) * $limit;
            $filter = $this->input->post('Filter') ?: [];

            if ($limit <= 0 || $limit > 100) $limit = 10;

			$this->load->model('transactions_model');
            $allData = $this->transactions_model->getTransactionPageList($limit, $offset, $this->pageModuleUID, $filter, 0);
            $allDataCount = $this->transactions_model->getTransactionCount($this->pageModuleUID, $filter);

			// $config['base_url'] = '/quotations/getQuotationsPageDetails/';
            // $config['use_page_numbers'] = TRUE;
            // $config['total_rows'] = $allDataCount;
            // $config['per_page'] = $limit;
            $config = [
                'base_url' => base_url('quotations/getQuotationsPageDetails/'),
                'use_page_numbers' => TRUE,
                'total_rows' => $allDataCount,
                'per_page' => $limit,
                'reuse_query_string' => TRUE, // Preserve filter parameters
                'first_link' => 'First',
                'last_link' => 'Last',
                'next_link' => 'Next',
                'prev_link' => 'Previous',
                'full_tag_open' => '<ul class="pagination justify-content-center">',
                'full_tag_close' => '</ul>',
                'attributes' => ['class' => 'page-link'],
                'cur_tag_open' => '<li class="page-item active"><span class="page-link">',
                'cur_tag_close' => '</span></li>',
            ];

            $this->pagination->initialize($config);
            $this->EndReturnData->pagination = $this->pagination->create_links();
            $this->EndReturnData->ResultCount = $allDataCount;
            $this->EndReturnData->ShowingCount = count($allData);
            $this->EndReturnData->PageNo = $pageNo;
            $this->EndReturnData->dataList = $this->load->view('transactions/quotations/mainpagelist', ['dataLists' => $allData], true);

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
            $status                 =         getPostValue($PostData, 'action') === 'draft' ? 'Draft' : 'Pending';

            if ($transNumber <= 0) throw new Exception('Transaction number must be greater than 0.');

            // Auto-compute validityDate from settings if not provided
            if (empty($validityDate) && $validityDays > 0) {
                $validityDate = date('Y-m-d', strtotime($transDate . " +{$validityDays} days"));
            }

            $financialYear = (int) date('Y', strtotime($transDate));

            // --- Resolve prefix config & build UniqueNumber ---
            $this->load->model('transactions_model');
            $prefixData = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.PrefixUID' => $prefixUID, 'Prefix.OrgUID' => $orgUID]);
            if (empty($prefixData->Data)) throw new Exception('Invalid prefix selected.');
            $prefix = $prefixData->Data[0];

            // Duplicate check: same raw TransNumber for same prefix (regardless of padding)
            $dupCheck = $this->transactions_model->getTransactionByPrefixAndNumber($prefixUID, $transNumber, $orgUID, $this->pageModuleUID);
            if ($dupCheck) {
                $nextSuggested = $this->transactions_model->getNextTransactionNumber($prefixUID, $orgUID, $this->pageModuleUID);
                throw new Exception("Transaction number {$transNumber} already exists for this prefix. " . "Next available number is {$nextSuggested}.");
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

            // --- Insert header ---
            $this->load->model('dbwrite_model');
            $headerData = [
                'OrgUID'                => $orgUID,
                'ModuleUID'             => $this->pageModuleUID,
                'PrefixUID'             => $prefixUID,
                'UniqueNumber'          => $uniqueNumber,
                'TransType'             => 'Quotation',
                'TransNumber'           => $transNumber,           // raw integer, no padding
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
            $status                 =         getPostValue($PostData, 'action') === 'draft' ? 'Draft' : 'Pending';

            if (empty($validityDate) && $validityDays > 0) {
                $validityDate = date('Y-m-d', strtotime($transDate . " +{$validityDays} days"));
            }

            $financialYear = (int) date('Y', strtotime($transDate));

            // --- Update header ---
            $headerData = [
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
                'UpdatedBy'             => $userUID,
            ];

            $updateResp = $this->dbwrite_model->updateData(
                'Transaction', 'TransactionsTbl', $headerData,
                ['TransUID' => $transUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );
            if ($updateResp->Error) throw new Exception($updateResp->Message);

            // --- Update detail row ---
            $additionalChargesJson = $this->buildAdditionalChargesJson($PostData);
            $detailData = [
                'ValidityDays'      => $validityDays ?: NULL,
                'ValidityDate'      => $validityDate ?: NULL,
                'Reference'         => getPostValue($PostData, 'referenceDetails') ?: NULL,
                'Notes'             => getPostValue($PostData, 'transNotes') ?: NULL,
                'TermsConditions'   => getPostValue($PostData, 'transTermsCond') ?: NULL,
                'AdditionalCharges' => $additionalChargesJson,
            ];
            $this->dbwrite_model->updateData(
                'Transaction', 'TransDetailTbl', $detailData,
                ['FinancialYear' => $financialYear, 'TransUID' => $transUID]
            );

            // --- Replace line items: soft-delete old items + taxes, insert new ---
            $this->dbwrite_model->updateData(
                'Transaction', 'TransProductsTbl',
                ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID, 'UpdatedOn' => time()],
                ['TransUID' => $transUID, 'IsDeleted' => 0]
            );

            $this->saveQuotationItems($transUID, $financialYear, $orgUID, $userUID, $items);

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

    /**
     * Insert line items into TransProductsTbl.
     *
     * Key JS→PHP field name mappings:
     *   JS item.quantity        → PHP $qty          (BillManager uses 'quantity', not 'qty')
     *   JS item.discount_amount → PHP $discountAmount (underscore, not camel)
     *   JS item.line_total      → PHP $lineTaxable   (unit_price×qty, BEFORE tax)
     *   JS item.net_total       → PHP $netAmount      (selling_price×qty, WITH tax)
     */
    private function saveQuotationItems($transUID, $financialYear, $orgUID, $userUID, array $items) {

        $this->load->model('dbwrite_model');
        $now = time();

        foreach ($items as $seq => $item) {

            $productUID      = isset($item['id'])       ? (int)   $item['id']       : 0;
            $qty             = isset($item['quantity'])  ? (float) $item['quantity']  : 0;   // JS: item.quantity
            $unitPrice       = isset($item['unitPrice']) ? (float) $item['unitPrice'] : 0;

            if ($productUID <= 0 || $qty <= 0) continue;

            $taxPercent      = (float) ($item['taxPercent']   ?? 0);
            $cgst            = (float) ($item['cgstPercent']  ?? 0);
            $sgst            = (float) ($item['sgstPercent']  ?? 0);
            $igst            = (float) ($item['igstPercent']  ?? 0);
            $taxDetailsUID   = isset($item['taxDetailsUID'])   ? (int)   $item['taxDetailsUID']   : 1;
            $discountTypeUID = isset($item['discountTypeUID']) ? (int)   $item['discountTypeUID'] : NULL;
            $discount        = (float) ($item['discount']      ?? 0);
            $discountAmount  = (float) ($item['discount_amount'] ?? 0);  // JS: item.discount_amount
            $taxAmount       = (float) ($item['taxAmount']     ?? 0);
            $cgstAmount      = (float) ($item['cgstAmount']    ?? 0);
            $sgstAmount      = (float) ($item['sgstAmount']    ?? 0);
            $igstAmount      = (float) ($item['igstAmount']    ?? 0);
            $sellingPrice    = (float) ($item['sellingPrice']  ?? $unitPrice);
            $lineTaxable     = (float) ($item['line_total']    ?? 0);    // JS: item.line_total  (before tax)
            $netAmount       = (float) ($item['net_total']     ?? 0);    // JS: item.net_total   (with tax)

            $itemData = [
                'OrgUID'          => $orgUID,
                'FinancialYear'   => $financialYear,
                'TransUID'        => $transUID,
                'ItemSequence'    => $seq + 1,
                'ProductUID'      => $productUID,
                'ProductName'     => substr(strip_tags($item['itemName'] ?? ''), 0, 100),
                'PartNumber'      => isset($item['partNumber'])   ? substr($item['partNumber'], 0, 50) : NULL,
                'CategoryUID'     => isset($item['categoryUID'])  ? (int) $item['categoryUID']         : NULL,
                'StorageUID'      => isset($item['storageUID'])   ? (int) $item['storageUID']           : NULL,
                'Quantity'        => $qty,
                'PrimaryUnitName' => isset($item['primaryUnit'])  ? substr($item['primaryUnit'], 0, 20) : NULL,
                'TaxDetailsUID'   => $taxDetailsUID,
                'TaxPercentage'   => $taxPercent,
                'CGST'            => $cgst,
                'SGST'            => $sgst,
                'IGST'            => $igst,
                'DiscountTypeUID' => $discountTypeUID,
                'Discount'        => $discount,
                'UnitPrice'       => $unitPrice,
                'SellingPrice'    => $sellingPrice,
                'TaxableAmount'   => $lineTaxable,
                'CgstAmount'      => $cgstAmount,
                'SgstAmount'      => $sgstAmount,
                'IgstAmount'      => $igstAmount,
                'TaxAmount'       => $taxAmount,
                'DiscountAmount'  => $discountAmount,
                'NetAmount'       => $netAmount,
                'QuantityConverted' => 0,
                'IsActive'        => 1,
                'IsDeleted'       => 0,
                'CreatedBy'       => $userUID,
                'UpdatedBy'       => $userUID,
            ];

            $itemResp = $this->dbwrite_model->insertData('Transaction', 'TransProductsTbl', $itemData);
            if ($itemResp->Error) throw new Exception($itemResp->Message);

        }

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

            // Dispatch locations (org-level, default first)
            $locationResult                    = $this->transactions_model->getOrgLocations($orgUID);
            $this->pageData['LocationData']    = $locationResult->Data ?? [];

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

}