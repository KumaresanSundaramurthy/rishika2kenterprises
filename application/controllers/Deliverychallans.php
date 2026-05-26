<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Deliverychallans extends MY_Controller {

    public $pageData = array();
    private $EndReturnData;
    private $pageModuleUID;

    public function __construct() {
        parent::__construct();
        $this->pageModuleUID = 112;
        $this->load->helper('transaction');
    }

    // ── List page ────────────────────────────────────────────────
    public function index() {
        if (!$this->_loadPageTitle($this->pageModuleUID)) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }
        try {
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;
            $GeneralSettings = ($this->redisservice->getUserCache('settings')) ?? new stdClass();
            $limit = $GeneralSettings->RowLimit ?? 10;
            $this->pageData['JwtData']->GenSettings = $GeneralSettings;
            $this->pageData['DiscTypeInfo'] = [];

            $this->load->model('transactions_model');
            $allData      = $this->transactions_model->getTransactionPageList($limit, 0, $this->pageModuleUID, [], 0);
            $allDataCount = $this->transactions_model->getTransactionCount($this->pageModuleUID, []);

            $this->pageData['ModRowData']    = $this->load->view('transactions/deliverychallans/list', ['DataLists' => $allData, 'SerialNumber' => 0, 'JwtData' => $this->pageData['JwtData']], TRUE);
            $this->pageData['ModPagination'] = $this->globalservice->buildPagePaginationHtml('/deliverychallan/getPageDetails', $allDataCount, 1, $limit);
            $this->pageData['ModAllCount']   = $allDataCount;
            $this->pageData['SummaryStats']  = $this->transactions_model->getTransactionSummaryStats($this->pageModuleUID, $this->pageData['JwtData']->User->OrgUID);

            $this->pageData['UpstashReadUrl']   = getenv('UPSTASH_REDIS_REST_URL') ?: '';
            $this->pageData['UpstashReadToken'] = getenv('UPSTASH_REDIS_REST_READONLY_TOKEN') ?: '';
            $this->pageData['CustomerCacheKey'] = $this->redisservice->orgKey('customers');

            $this->load->view('transactions/deliverychallans/view', $this->pageData);
        } catch (Exception $e) {
            redirect('dashboard', 'refresh');
        }
    }

    // ── Paginated list (AJAX) ────────────────────────────────────
    public function getPageDetails($pageNo = 0) {
        $this->EndReturnData = new stdClass();
        try {
            $pageNo = max(1, (int) $pageNo);
            $limit  = (int) $this->input->post('RowLimit') ?: 10;
            $offset = ($pageNo - 1) * $limit;
            $filter = $this->input->post('Filter') ?: [];

            $this->load->model('transactions_model');
            $allData      = $this->transactions_model->getTransactionPageList($limit, $offset, $this->pageModuleUID, $filter, 0);
            $allDataCount = $this->transactions_model->getTransactionCount($this->pageModuleUID, $filter);

            $this->pageData['JwtData']->GenSettings = ($this->redisservice->getUserCache('settings')) ?? new stdClass();

            $rowHtml = $this->load->view('transactions/deliverychallans/list', [
                'DataLists'    => $allData,
                'SerialNumber' => ($pageNo - 1) * $limit,
                'JwtData'      => $this->pageData['JwtData'],
            ], true);

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->RecordHtmlData = $rowHtml;
            $this->EndReturnData->Pagination     = $this->globalservice->buildPagePaginationHtml('/deliverychallan/getPageDetails', $allDataCount, $pageNo, $limit);
            $this->EndReturnData->TotalCount     = $allDataCount;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Create form ──────────────────────────────────────────────
    public function create() {
        try {
            $GeneralSettings = $this->redisservice->getUserCache('settings') ?? NULL;
            $this->pageData['JwtData']->GenSettings = $GeneralSettings;

            $orgUID = $this->pageData['JwtData']->User->OrgUID;
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;

            $this->load->model('transactions_model');

            $prefixResult                    = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID, 'Prefix.ModuleUID' => $this->pageModuleUID]);
            $this->pageData['PrefixData']    = $prefixResult->Data ?? [];
            $nextNumberMap = [];
            foreach ($this->pageData['PrefixData'] as $pd) {
                $nextNumberMap[(int)$pd->PrefixUID] = $this->transactions_model->getNextTransactionNumber($pd->PrefixUID, $orgUID, $this->pageModuleUID);
            }
            $this->pageData['NextNumberMap'] = $nextNumberMap;

            // Pre-fill from Sales Order if converting
            $fromSOUID = (int) $this->input->get('fromSalesOrder');
            $this->pageData['FromSOUID']    = $fromSOUID;
            $this->pageData['SOSourceData'] = null;
            $this->pageData['SOSourceItems']= [];
            if ($fromSOUID > 0) {
                $soData  = $this->transactions_model->getTransactionById($fromSOUID, $orgUID, 102);
                $soItems = $soData ? $this->transactions_model->getTransactionItems($fromSOUID, $orgUID) : [];
                $this->pageData['SOSourceData']  = $soData;
                $this->pageData['SOSourceItems'] = $soItems;
            }

            $this->load->model('organisation_model');
            $dispatchAddrResult                  = $this->organisation_model->getOrgDispatchAddress($orgUID);
            $this->pageData['DispatchAddress']   = $dispatchAddrResult->Data ?? NULL;

            $this->load->model('global_model');
            $GetCountryInfo = $this->global_model->getCountryInfo();
            $this->pageData['CountryInfo'] = $GetCountryInfo->Error === FALSE ? $GetCountryInfo->Data : [];
            $this->pageData['StateData']   = [];
            $this->pageData['CityData']    = [];
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
            $this->pageData['fltStorageData']  = [];
            if (!empty($this->pageData['JwtData']->GenSettings->EnableStorage)) {
                $this->load->model('storage_model');
                $this->pageData['fltStorageData'] = $this->storage_model->getStorageDetails([]) ?? [];
            }

            $this->load->view('transactions/deliverychallans/forms/form', $this->pageData);
        } catch (Exception $e) {
            redirect('deliverychallan', 'refresh');
        }
    }

    // ── Edit form ────────────────────────────────────────────────
    public function edit($transUID = 0) {
        try {
            $transUID = (int) $transUID;
            if ($transUID <= 0) redirect('deliverychallan', 'refresh');

            $GeneralSettings = $this->redisservice->getUserCache('settings') ?? NULL;
            $this->pageData['JwtData']->GenSettings = $GeneralSettings;

            $orgUID = $this->pageData['JwtData']->User->OrgUID;
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;

            $this->load->model('transactions_model');
            $dcData = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$dcData) redirect('deliverychallan', 'refresh');

            $dcItems = $this->transactions_model->getTransactionItems($transUID, $orgUID);

            $this->load->model('customers_model');
            $custAddr = $this->customers_model->getCustomerAddress(['CustAddress.CustomerUID' => $dcData->PartyUID, 'CustAddress.OrgUID' => $orgUID]);
            $shipping = current(array_filter($custAddr, fn($a) => $a->AddressType === 'Shipping'));
            $billing  = current(array_filter($custAddr, fn($a) => $a->AddressType === 'Billing'));
            $this->pageData['CustAddr'] = $shipping ?: ($billing ?: ($custAddr[0] ?? null));

            $this->pageData['DCData']  = $dcData;
            $this->pageData['DCItems'] = $dcItems;

            $prefixResult                    = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID, 'Prefix.ModuleUID' => $this->pageModuleUID]);
            $this->pageData['PrefixData']    = $prefixResult->Data ?? [];
            $nextNumberMap = [];
            foreach ($this->pageData['PrefixData'] as $pd) {
                $nextNumberMap[(int)$pd->PrefixUID] = $this->transactions_model->getNextTransactionNumber($pd->PrefixUID, $orgUID, $this->pageModuleUID);
            }
            $this->pageData['NextNumberMap'] = $nextNumberMap;

            $this->load->model('organisation_model');
            $dispatchAddrResult                  = $this->organisation_model->getOrgDispatchAddress($orgUID);
            $this->pageData['DispatchAddress']   = $dispatchAddrResult->Data ?? NULL;

            $this->load->model('global_model');
            $GetCountryInfo = $this->global_model->getCountryInfo();
            $this->pageData['CountryInfo'] = $GetCountryInfo->Error === FALSE ? $GetCountryInfo->Data : [];
            $this->pageData['StateData']   = [];
            $this->pageData['CityData']    = [];
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
            $this->pageData['fltStorageData']  = [];
            if (!empty($this->pageData['JwtData']->GenSettings->EnableStorage)) {
                $this->load->model('storage_model');
                $this->pageData['fltStorageData'] = $this->storage_model->getStorageDetails([]) ?? [];
            }

            $this->load->view('transactions/deliverychallans/forms/form', $this->pageData);
        } catch (Exception $e) {
            redirect('deliverychallan', 'refresh');
        }
    }

    // ── Save new challan ─────────────────────────────────────────
    public function addDeliveryChallan() {
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
            $extraDiscount          = (float) getPostValue($PostData, 'extraDiscount',           'Array', 0);
            $isDraft   = getPostValue($PostData, 'action') === 'draft';
            $status    = $isDraft ? 'Draft' : 'Dispatched';

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
            $isInterState          = $igstAmount > 0 ? 1 : ($cgstAmount > 0 || $sgstAmount > 0 ? 0 : NULL);
            $_cc                   = $this->transactions_model->getCustomerCountryCode($customerUID);
            $isForeignCustomer     = $_cc !== NULL ? ($_cc === 'IN' ? 0 : 1) : NULL;

            $headerData = [
                'OrgUID'            => $orgUID,
                'ModuleUID'         => $this->pageModuleUID,
                'PrefixUID'         => $prefixUID,
                'UniqueNumber'      => $uniqueNumber,
                'TransType'         => 'DeliveryChallan',
                'TransNumber'       => $transNumber,
                'PartyType'         => 'C',
                'PartyUID'          => $customerUID,
                'TransDate'         => $transDate,
                'TransYear'         => $financialYear,
                'QuotationType'     => getPostValue($PostData, 'challanType') ?: 'Non-Returnable',
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
                'TransToken'        => $this->transactions_model->_uniqueTransToken(),
                'IsActive'          => 1,
                'IsDeleted'         => 0,
                'CreatedBy'         => $userUID,
                'UpdatedBy'         => $userUID,
            ];

            $insertResp = $this->dbwrite_model->insertData('Transaction', 'TransactionsTbl', $headerData);
            if ($insertResp->Error) throw new Exception($insertResp->Message);
            $transUID = $insertResp->ID;

            $detailData = [
                'FinancialYear'     => $financialYear,
                'TransUID'          => $transUID,
                'ValidityDays'      => NULL,
                'ValidityDate'      => $returnDate ?: NULL,
                'Reference'         => getPostValue($PostData, 'vehicleNumber') ?: NULL,
                'Notes'             => getPostValue($PostData, 'transNotes') ?: NULL,
                'TermsConditions'   => getPostValue($PostData, 'transTermsCond') ?: NULL,
                'SignatureUID'      => (int)getPostValue($PostData, 'SignatureUID') ?: NULL,
                'AdditionalCharges' => $additionalChargesJson,
                'IsInterState'      => $isInterState,
                'IsForeignCustomer' => $isForeignCustomer,
            ];
            $this->dbwrite_model->insertData('Transaction', 'TransDetailTbl', $detailData);

            $this->saveChallanItems($transUID, $financialYear, $orgUID, $userUID, $items);

            // Conversion tracking: SalesOrder → DeliveryChallan
            $fromSOUID = (int) getPostValue($PostData, 'fromSOUID');
            if ($fromSOUID > 0 && !$isDraft) {
                $this->dbwrite_model->updateTransDocStatus($fromSOUID, $orgUID, 'Converted', $userUID);
                $this->dbwrite_model->insertConversionRecord(
                    $orgUID, $fromSOUID, 102, $transUID, $this->pageModuleUID, 'SOToDeliveryChallan', $userUID
                );
            }

            $this->dbwrite_model->commitTransaction();
            $this->_touchCustomerCache($customerUID);

            $this->EndReturnData->Error    = FALSE;
            $this->EndReturnData->Message  = 'Delivery challan created successfully.';
            $this->EndReturnData->TransUID = $transUID;
        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Update existing challan ──────────────────────────────────
    public function updateDeliveryChallan() {
        $this->EndReturnData = new stdClass();
        try {
            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData = $this->input->post();
            $userUID  = $this->pageData['JwtData']->User->UserUID;
            $orgUID   = $this->pageData['JwtData']->User->OrgUID;

            $transUID = (int) getPostValue($PostData, 'TransUID');
            if ($transUID <= 0) throw new Exception('Delivery Challan ID is required.');

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
            $status                 = $isDraft ? 'Draft' : 'Dispatched';

            $financialYear = (int) date('Y', strtotime($transDate));

            $this->load->model('transactions_model');
            $existing = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$existing) throw new Exception('Delivery Challan not found.');

            $uniqueNumber = NULL;
            if ($existing->DocStatus === 'Draft' && !$isDraft) {
                if ($prefixUID <= 0) throw new Exception('Please select a prefix to finalize this challan.');
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
            $isInterState          = $igstAmount > 0 ? 1 : ($cgstAmount > 0 || $sgstAmount > 0 ? 0 : NULL);
            $_cc                   = $this->transactions_model->getCustomerCountryCode($customerUID);
            $isForeignCustomer     = $_cc !== NULL ? ($_cc === 'IN' ? 0 : 1) : NULL;

            $commonHeader = [
                'OrgUID'            => $orgUID,
                'ModuleUID'         => $this->pageModuleUID,
                'PartyType'         => 'C',
                'PartyUID'          => $customerUID,
                'TransDate'         => $transDate,
                'TransYear'         => $financialYear,
                'TransType'         => 'DeliveryChallan',
                'QuotationType'     => getPostValue($PostData, 'challanType') ?: 'Non-Returnable',
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
            ];

            $commonDetail = [
                'ValidityDays'      => NULL,
                'ValidityDate'      => $returnDate ?: NULL,
                'Reference'         => getPostValue($PostData, 'vehicleNumber') ?: NULL,
                'Notes'             => getPostValue($PostData, 'transNotes') ?: NULL,
                'TermsConditions'   => getPostValue($PostData, 'transTermsCond') ?: NULL,
                'SignatureUID'      => (int)getPostValue($PostData, 'SignatureUID') ?: NULL,
                'AdditionalCharges' => $additionalChargesJson,
                'IsInterState'      => $isInterState,
                'IsForeignCustomer' => $isForeignCustomer,
            ];

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

            $this->dbwrite_model->updateData(
                'Transaction', 'TransDetailTbl', $commonDetail,
                ['FinancialYear' => $financialYear, 'TransUID' => $transUID]
            );

            // Smart item diff
            $existingItems     = $this->transactions_model->getTransactionItems($transUID, $orgUID);
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

            $this->dbwrite_model->commitTransaction();
            $this->_touchCustomerCache($customerUID);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Delivery challan updated successfully.';
        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Delete challan ───────────────────────────────────────────
    public function deleteDeliveryChallan() {
        $this->EndReturnData = new stdClass();
        try {
            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData = $this->input->post();
            $userUID  = $this->pageData['JwtData']->User->UserUID;
            $orgUID   = $this->pageData['JwtData']->User->OrgUID;
            $transUID = (int) getPostValue($PostData, 'TransUID');
            if ($transUID <= 0) throw new Exception('Delivery Challan ID is required.');

            $this->load->model('transactions_model');
            $existing = $this->transactions_model->getTransactionPageList(1, 0, $this->pageModuleUID, ['TransUID' => $transUID, 'OrgUID' => $orgUID]);
            if (empty($existing)) throw new Exception('Delivery Challan not found.');

            $now = time();
            $this->dbwrite_model->updateData('Transaction', 'TransProductsTbl',
                ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID, 'UpdatedOn' => $now],
                ['TransUID' => $transUID, 'IsDeleted' => 0]
            );

            $deleteData = $this->globalservice->baseDeleteArrayDetails();
            $deleteData['IsActive'] = 0;
            $deleteResp = $this->dbwrite_model->updateData('Transaction', 'TransactionsTbl', $deleteData,
                ['TransUID' => $transUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );
            if ($deleteResp->Error) throw new Exception($deleteResp->Message);

            $this->dbwrite_model->commitTransaction();

            $pageNo  = max(1, (int) $this->input->post('PageNo'));
            $limit   = (int) $this->input->post('RowLimit') ?: 10;
            $filter  = $this->input->post('Filter') ?: [];
            $offset  = ($pageNo - 1) * $limit;

            $this->pageData['JwtData']->GenSettings = ($this->redisservice->getUserCache('settings')) ?? new stdClass();
            $allData      = $this->transactions_model->getTransactionPageList($limit, $offset, $this->pageModuleUID, $filter, 0);
            $allDataCount = $this->transactions_model->getTransactionCount($this->pageModuleUID, $filter);

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->Message        = 'Delivery challan deleted successfully.';
            $this->EndReturnData->RecordHtmlData = $this->load->view('transactions/deliverychallans/list', ['DataLists' => $allData, 'SerialNumber' => $offset, 'JwtData' => $this->pageData['JwtData']], true);
            $this->EndReturnData->Pagination     = $this->globalservice->buildPagePaginationHtml('/deliverychallan/getPageDetails', $allDataCount, $pageNo, $limit);
            $this->EndReturnData->TotalCount     = $allDataCount;
        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Duplicate challan ────────────────────────────────────────
    public function duplicateDeliveryChallan() {
        $this->EndReturnData = new stdClass();
        try {
            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData = $this->input->post();
            $srcUID   = (int) getPostValue($PostData, 'TransUID');
            $userUID  = $this->pageData['JwtData']->User->UserUID;
            $orgUID   = $this->pageData['JwtData']->User->OrgUID;
            if ($srcUID <= 0) throw new Exception('Invalid delivery challan.');

            $this->load->model('transactions_model');
            $src = $this->transactions_model->getTransactionById($srcUID, $orgUID, $this->pageModuleUID);
            if (!$src) throw new Exception('Delivery Challan not found.');

            $nextNumber   = $this->transactions_model->getNextTransactionNumber($src->PrefixUID, $orgUID, $this->pageModuleUID);
            $prefixResult = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.PrefixUID' => $src->PrefixUID, 'Prefix.OrgUID' => $orgUID]);
            $prefix       = $prefixResult->Data[0] ?? null;
            if (!$prefix) throw new Exception('Prefix not found.');

            $sep   = $prefix->Separator ?? '-';
            $parts = [strtoupper($prefix->Name)];
            if (!empty($prefix->IncludeShortName) && !empty($prefix->ShortName)) $parts[] = strtoupper($prefix->ShortName);
            if (!empty($prefix->IncludeFiscalYear)) {
                $m  = (int) date('m'); $yr = (int) date('Y'); $fy = $m >= 4 ? $yr : $yr - 1;
                $parts[] = ($prefix->FiscalYearFormat ?? 'SHORT') === 'LONG'
                    ? $fy . '-' . ($fy + 1)
                    : str_pad($fy % 100, 2, '0', STR_PAD_LEFT) . '-' . str_pad(($fy + 1) % 100, 2, '0', STR_PAD_LEFT);
            }
            $pad = (int)($prefix->NumberPadding ?? 1);
            $parts[] = $pad > 1 ? str_pad($nextNumber, $pad, '0', STR_PAD_LEFT) : (string) $nextNumber;
            $uniqueNumber = implode($sep, $parts);

            $headerData = [
                'OrgUID'            => $orgUID,
                'ModuleUID'         => $this->pageModuleUID,
                'PrefixUID'         => $src->PrefixUID,
                'UniqueNumber'      => $uniqueNumber,
                'TransType'         => 'DeliveryChallan',
                'TransNumber'       => $nextNumber,
                'PartyType'         => $src->PartyType,
                'PartyUID'          => $src->PartyUID,
                'TransDate'         => date('Y-m-d'),
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
                'IsActive'          => 1, 'IsDeleted' => 0, 'CreatedBy' => $userUID, 'UpdatedBy' => $userUID,
            ];
            $insertResp = $this->dbwrite_model->insertData('Transaction', 'TransactionsTbl', $headerData);
            if ($insertResp->Error) throw new Exception($insertResp->Message);
            $newTransUID = $insertResp->ID;

            $_srcCC = $src->PartyCountryCode ?? NULL;
            $this->dbwrite_model->insertData('Transaction', 'TransDetailTbl', [
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
            ]);

            $srcItems = $this->transactions_model->getTransactionItems($srcUID, $orgUID);
            $now = time();
            foreach ($srcItems as $seq => $item) {
                $this->dbwrite_model->insertData('Transaction', 'TransProductsTbl', [
                    'OrgUID' => $orgUID, 'FinancialYear' => (int) date('Y'), 'TransUID' => $newTransUID,
                    'ItemSequence' => $seq + 1, 'ProductUID' => $item->ProductUID,
                    'ProductName' => $item->ProductName, 'PartNumber' => $item->PartNumber,
                    'CategoryUID' => $item->CategoryUID, 'StorageUID' => $item->StorageUID,
                    'Quantity' => $item->Quantity, 'PrimaryUnitName' => $item->PrimaryUnitName,
                    'TaxDetailsUID' => $item->TaxDetailsUID, 'TaxPercentage' => $item->TaxPercentage,
                    'CGST' => $item->CGST, 'SGST' => $item->SGST, 'IGST' => $item->IGST,
                    'DiscountTypeUID' => $item->DiscountTypeUID, 'Discount' => $item->Discount,
                    'UnitPrice' => $item->UnitPrice, 'SellingPrice' => $item->SellingPrice,
                    'TaxableAmount' => $item->TaxableAmount, 'CgstAmount' => $item->CgstAmount,
                    'SgstAmount' => $item->SgstAmount, 'IgstAmount' => $item->IgstAmount,
                    'TaxAmount' => $item->TaxAmount, 'DiscountAmount' => $item->DiscountAmount,
                    'NetAmount' => $item->NetAmount, 'QuantityConverted' => 0,
                    'IsActive' => 1, 'IsDeleted' => 0, 'CreatedBy' => $userUID, 'UpdatedBy' => $userUID,
                    'CreatedOn' => $now, 'UpdatedOn' => $now,
                ]);
            }

            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error    = FALSE;
            $this->EndReturnData->Message  = 'Delivery challan duplicated as ' . $uniqueNumber . '.';
            $this->EndReturnData->TransUID = $newTransUID;
            $this->EndReturnData->EditURL  = '/deliverychallan/' . $newTransUID . '/edit';
        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Status update ────────────────────────────────────────────
    public function updateDeliveryChallanStatus() {
        $this->EndReturnData = new stdClass();
        try {
            $this->load->model('dbwrite_model');
            $PostData  = $this->input->post();
            $transUID  = (int) getPostValue($PostData, 'TransUID');
            $newStatus = trim(getPostValue($PostData, 'Status'));
            $userUID   = $this->pageData['JwtData']->User->UserUID;
            $orgUID    = $this->pageData['JwtData']->User->OrgUID;
            if ($transUID <= 0) throw new Exception('Invalid delivery challan.');

            $validTransitions = [
                'Draft'      => ['Dispatched'],
                'Dispatched' => ['Delivered', 'Cancelled'],
                'Delivered'  => [],
                'Converted'  => [],
                'Cancelled'  => [],
            ];

            $this->load->model('transactions_model');
            $existing = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$existing) throw new Exception('Delivery Challan not found.');

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

            $pageNo  = max(1, (int) $this->input->post('PageNo'));
            $limit   = (int) $this->input->post('RowLimit') ?: 10;
            $filter  = $this->input->post('Filter') ?: [];
            $offset  = ($pageNo - 1) * $limit;

            $this->pageData['JwtData']->GenSettings = ($this->redisservice->getUserCache('settings')) ?? new stdClass();
            $allData      = $this->transactions_model->getTransactionPageList($limit, $offset, $this->pageModuleUID, $filter, 0);
            $allDataCount = $this->transactions_model->getTransactionCount($this->pageModuleUID, $filter);

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->Message        = 'Status updated to ' . $newStatus . '.';
            $this->EndReturnData->NewStatus      = $newStatus;
            $this->EndReturnData->RecordHtmlData = $this->load->view('transactions/deliverychallans/list', ['DataLists' => $allData, 'SerialNumber' => $offset, 'JwtData' => $this->pageData['JwtData']], true);
            $this->EndReturnData->Pagination     = $this->globalservice->buildPagePaginationHtml('/deliverychallan/getPageDetails', $allDataCount, $pageNo, $limit);
            $this->EndReturnData->TotalCount     = $allDataCount;
        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Convert to Invoice ───────────────────────────────────────
    public function convertChallanToInvoice() {
        $this->EndReturnData = new stdClass();
        try {
            $this->load->model('dbwrite_model');
            $PostData = $this->input->post();
            $transUID = (int) getPostValue($PostData, 'TransUID');
            $userUID  = $this->pageData['JwtData']->User->UserUID;
            $orgUID   = $this->pageData['JwtData']->User->OrgUID;
            if ($transUID <= 0) throw new Exception('Invalid delivery challan.');

            $this->load->model('transactions_model');
            $existing = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$existing) throw new Exception('Delivery Challan not found.');
            if ($existing->DocStatus !== 'Delivered') throw new Exception('Only Delivered challans can be converted to an Invoice.');

            $this->dbwrite_model->startTransaction();
            $resp = $this->dbwrite_model->updateData(
                'Transaction', 'TransactionsTbl',
                ['DocStatus' => 'Converted', 'UpdatedBy' => $userUID, 'UpdatedOn' => date('Y-m-d H:i:s')],
                ['TransUID' => $transUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );
            if ($resp->Error) throw new Exception($resp->Message);
            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error       = FALSE;
            $this->EndReturnData->Message     = 'Challan marked as converted.';
            $this->EndReturnData->RedirectURL = '/invoices/create?fromChallan=' . $transUID;
        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Detail (for print/view modal) ────────────────────────────
    public function getChallanDetail() {
        $this->EndReturnData = new stdClass();
        try {
            $transUID = (int) $this->input->get_post('TransUID');
            $orgUID   = $this->pageData['JwtData']->User->OrgUID;
            if ($transUID <= 0) throw new Exception('Invalid delivery challan.');

            $this->load->model('transactions_model');
            $header = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$header) throw new Exception('Delivery Challan not found.');
            $items = $this->transactions_model->getTransactionItems($transUID, $orgUID);

            $this->load->model('organisation_model');
            $orgInfo          = $this->organisation_model->getOrgInfoCached($orgUID);
            $thermalCfgResult = $this->organisation_model->getThermalPrintConfig($orgUID);
            $printThemeResult = $this->organisation_model->getPrintThemeByType($orgUID, 'DeliveryChallan');

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

    // ── Packing List (standalone print page) ────────────────────
    public function packingList($transUID = 0) {
        try {
            $transUID = (int) $transUID;
            if ($transUID <= 0) redirect('deliverychallan', 'refresh');

            $orgUID = $this->pageData['JwtData']->User->OrgUID;

            $this->load->model('transactions_model');
            $header = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$header) redirect('deliverychallan', 'refresh');

            $items = $this->transactions_model->getTransactionItems($transUID, $orgUID);

            $this->load->model('organisation_model');
            $orgInfo = $this->organisation_model->getOrgInfoCached($orgUID);

            $this->pageData['PackingHeader'] = $header;
            $this->pageData['PackingItems']  = $items;
            $this->pageData['OrgInfo']       = $orgInfo->Data ?? null;

            $this->load->view('transactions/deliverychallans/packing_list', $this->pageData);
        } catch (Exception $e) {
            redirect('deliverychallan', 'refresh');
        }
    }

    private function _touchCustomerCache($customerUID) {
        $this->cachehelper->touchCustomer($customerUID);
    }

    // ── Private helpers ──────────────────────────────────────────
    private function saveChallanItems($transUID, $financialYear, $orgUID, $userUID, array $items) {
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
                'ItemSequence'      => $seq + 1,
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

    private function buildAdditionalChargesJson($PostData) {
        $charges = [];
        $types   = ['shipping', 'handling', 'packing', 'other'];
        foreach ($types as $type) {
            $amt = (float) getPostValue($PostData, $type . 'Amount', 'Array', 0);
            $tax = getPostValue($PostData, $type . 'Tax') ?: NULL;
            if ($amt > 0) $charges[] = ['type' => $type, 'amount' => $amt, 'tax' => $tax];
        }
        return !empty($charges) ? json_encode($charges) : NULL;
    }
}
