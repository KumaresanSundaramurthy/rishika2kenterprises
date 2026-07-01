<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property object $transactions_model
 * @property object $dbwrite_model
 * @property object $formvalidation_model
 * @property object $globalservice
 * @property object $redisservice
 */
class Deliverychallans extends MY_Controller {

    public  $pageData     = [];
    /** @var object|null */
    private $EndReturnData;
    /** @var int */
    protected $pageModuleUID;

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
            $GeneralSettings = $this->pageData['JwtData']->GenSettings ?? new stdClass();
            $limit = $GeneralSettings->RowLimit ?? 10;

            $this->load->model('transactions_model');
            $datePref   = $this->getDateFilterPreference('deliverychallan'); // matches URL path /deliverychallan
            $initFilter = $datePref['from'] ? ['DateFrom' => $datePref['from'], 'DateTo' => $datePref['to']] : [];
            $allData      = $this->transactions_model->getTransactionPageList($limit, 0, $this->pageModuleUID, $initFilter, 0);
            $allDataCount = $this->transactions_model->getTransactionCount($this->pageModuleUID, $initFilter);
            $this->pageData['SavedDateRange'] = $datePref['range'];
            $this->pageData['SavedDateLabel'] = $datePref['label'];

            $this->pageData['ModRowData']    = $this->load->view('transactions/deliverychallans/list', ['DataLists' => $allData, 'SerialNumber' => 0, 'JwtData' => $this->pageData['JwtData']], TRUE);
            $this->pageData['ModPagination'] = $this->globalservice->buildPagePaginationHtml('/deliverychallan/getPageDetails', $allDataCount, 1, $limit);
            $this->pageData['ModAllCount']   = $allDataCount;
            $this->pageData['SummaryStats']  = $this->transactions_model->getTransactionSummaryStats($this->pageModuleUID, $this->pageData['JwtData']->Org->OrgUID);

            $this->_loadUpstashConfig();

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

            // Pre-fill from Clone (clone opens create form, not edit)
            $fromCloneUID = (int) $this->input->get('fromClone');
            $this->pageData['FromCloneUID'] = $fromCloneUID;
            $this->pageData['CloneData']    = null;
            $this->pageData['CloneItems']   = [];
            if ($fromCloneUID > 0) {
                $cloneData  = $this->transactions_model->getTransactionById($fromCloneUID, $orgUID, $this->pageModuleUID);
                $cloneItems = $cloneData ? $this->transactions_model->getTransactionItems($fromCloneUID, $orgUID) : [];
                $this->pageData['CloneData']  = $cloneData;
                $this->pageData['CloneItems'] = $cloneItems;
            }

            $this->_getDispatchAddresses($orgUID);

            $this->load->model('products_model');
            $this->pageData['SizeInfo']        = $this->products_model->getSizeDetails([]) ?? [];
            $this->pageData['BrandInfo']       = $this->products_model->getBrandDetails([]) ?? [];
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

            $orgUID = $this->pageData['JwtData']->Org->OrgUID;
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

            // Pre-fetch attachments — eliminates the AJAX call on the edit form
            $attachments = $this->transactions_model->getTransactionAttachments($transUID, $orgUID);
            $cdnUrl = rtrim(getenv('FILE_UPLOAD') == 'amazonaws' ? getenv('CDN_URL') : getenv('CFLARE_R2_CDN'), '/');
            foreach ($attachments as &$a) {
                $a->Url = $cdnUrl . '/' . ltrim($a->FilePath ?? '', '/');
            }
            unset($a);

            $this->pageData['DCData']        = $dcData;
            $this->pageData['DCItems']       = $dcItems;
            $this->pageData['DCAttachments'] = $attachments;

            $prefixResult                    = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID, 'Prefix.ModuleUID' => $this->pageModuleUID]);
            $this->pageData['PrefixData']    = $prefixResult->Data ?? [];
            $nextNumberMap = [];
            foreach ($this->pageData['PrefixData'] as $pd) {
                $nextNumberMap[(int)$pd->PrefixUID] = $this->transactions_model->getNextTransactionNumber($pd->PrefixUID, $orgUID, $this->pageModuleUID);
            }
            $this->pageData['NextNumberMap'] = $nextNumberMap;

            $this->_getDispatchAddresses($orgUID);

            $this->load->model('products_model');
            $this->pageData['SizeInfo']        = $this->products_model->getSizeDetails([]) ?? [];
            $this->pageData['BrandInfo']       = $this->products_model->getBrandDetails([]) ?? [];
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
            $returnDate             =         getPostValue($PostData, 'returnDate');    // → ExpectedDeliveryDate
            $deliveryByDate         =         getPostValue($PostData, 'deliveryBy');    // → DeliveryByDate
            $items                  = json_decode($itemsJson, true);
            $totalQty               = (float) array_sum(array_column($items, 'quantity'));

            // ── SO-linked DC: enforce customer lock + item/qty restrictions ──
            $fromSOUID = (int) getPostValue($PostData, 'fromSOUID');
            if ($fromSOUID > 0) {
                $this->load->model('transactions_model');
                $soData  = $this->transactions_model->getTransactionById($fromSOUID, $orgUID, 102);
                $soItems = $soData ? $this->transactions_model->getTransactionItems($fromSOUID, $orgUID) : [];

                if ($soData && (int)$soData->PartyUID !== $customerUID) {
                    throw new Exception('Customer cannot be changed on a challan linked to a Sales Order.');
                }

                $soQtyMap = [];
                foreach ($soItems as $si) {
                    $soQtyMap[(int)$si->ProductUID] = (float)$si->Quantity;
                }

                foreach ($items as $item) {
                    $pid = (int)($item['productUID'] ?? $item['id'] ?? 0);
                    if (!isset($soQtyMap[$pid])) {
                        throw new Exception('Item "' . ($item['itemName'] ?? 'Unknown') . '" is not part of the Sales Order and cannot be dispatched.');
                    }
                    $dispatchedQty = (float)($item['quantity'] ?? 0);
                    if ($dispatchedQty > $soQtyMap[$pid]) {
                        throw new Exception('Quantity for "' . ($item['itemName'] ?? 'Unknown') . '" (' . $dispatchedQty . ') exceeds the Sales Order quantity (' . $soQtyMap[$pid] . ').');
                    }
                }
            }

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
            $prefix = null;
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

                list($uniqueNumber) = $this->buildUniqueNumber($prefix, $transNumber, $transDate);
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

            $detailData = [
                'FinancialYear'     => $financialYear,
                'TransUID'          => $transUID,
                'ValidityDays'        => NULL,
                'ValidityDate'        => NULL,
                'ExpectedDeliveryDate'=> $returnDate    ?: NULL,
                'DeliveryByDate'      => $deliveryByDate ?: NULL,
                'Reference'         => getPostValue($PostData, 'vehicleNumber') ?: NULL,
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

            $this->saveChallanItems($transUID, $financialYear, $orgUID, $userUID, $items);

            // Conversion tracking: SalesOrder → DeliveryChallan
            $fromSOUID = (int) getPostValue($PostData, 'fromSOUID');
            if ($fromSOUID > 0 && !$isDraft) {
                $this->dbwrite_model->updateTransDocStatus($fromSOUID, $orgUID, 'Converted', $userUID);
                $this->dbwrite_model->insertConversionRecord(
                    $orgUID, $fromSOUID, 102, $transUID, $this->pageModuleUID, 'OrderToChallan', $userUID
                );
            }

            $this->dbwrite_model->commitTransaction();
            $this->cachehelper->touchCustomer($customerUID);
            $this->_saveAttachments($transUID);

            // Reduce AvailableQty for all modes (Non-Returnable / Returnable / Job Work)
            if (!$isDraft) {
                $this->dbwrite_model->saveStockMovements($transUID, $this->pageModuleUID, $orgUID, $userUID, $items);
                $this->_syncProductCacheFromItems($items);
            }

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
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;

            $transUID = (int) getPostValue($PostData, 'TransUID');
            if ($transUID <= 0) throw new Exception('Delivery Challan ID is required.');

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
            $returnDate             =         getPostValue($PostData, 'returnDate');    // → ExpectedDeliveryDate
            $deliveryByDate         =         getPostValue($PostData, 'deliveryBy');    // → DeliveryByDate
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
                'PdfPath'           => NULL,
            ];

            $commonDetail = [
                'ValidityDays'        => NULL,
                'ValidityDate'        => NULL,
                'ExpectedDeliveryDate'=> $returnDate    ?: NULL,
                'DeliveryByDate'      => $deliveryByDate ?: NULL,
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
            $this->cachehelper->touchCustomer($customerUID);
            $this->_saveAttachments($transUID);
            $this->transactions_model->generateAndStorePdf(isset($newTransUID) ? $newTransUID : $transUID, $orgUID, $this->pageModuleUID);

            // Stock movement after commit — handle 3 transitions:
            // Draft → Draft     : no stock change
            // Draft → Dispatched: save new stock movements (OUT)
            // Dispatched → Dispatched: reverse old items then save new items (item qty may have changed)
            if (!$isDraft) {
                $wasDispatched = ($existing->DocStatus === 'Dispatched');
                if ($wasDispatched) {
                    $this->dbwrite_model->reverseStockMovements($transUID, $orgUID, $userUID);
                    $this->_syncProductCacheByTransUID($transUID);
                }
                $this->dbwrite_model->saveStockMovements($transUID, $this->pageModuleUID, $orgUID, $userUID, $items);
                $this->_syncProductCacheFromItems($items);
            }

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
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;
            $transUID = (int) getPostValue($PostData, 'TransUID');
            if ($transUID <= 0) throw new Exception('Delivery Challan ID is required.');

            $this->load->model('transactions_model');
            $existing = $this->transactions_model->getTransactionPageList(1, 0, $this->pageModuleUID, ['TransUID' => $transUID, 'OrgUID' => $orgUID]);
            if (empty($existing)) throw new Exception('Delivery Challan not found.');
            // getTransactionPageList aliases DocStatus as 'Status'; also reverse stock for Delivered/Partially Returned
            $currentStatus      = $existing[0]->Status ?? '';
            $needsStockReversal = in_array($currentStatus, ['Dispatched', 'Delivered', 'Partially Returned', 'Converted']);

            $now = time();
            $this->dbwrite_model->updateData('Transaction', 'TransProductsTbl',
                ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID],
                ['TransUID' => $transUID, 'IsDeleted' => 0]
            );

            $deleteData = $this->globalservice->baseDeleteArrayDetails();
            $deleteData['IsActive'] = 0;
            $deleteResp = $this->dbwrite_model->updateData('Transaction', 'TransactionsTbl', $deleteData,
                ['TransUID' => $transUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );
            if ($deleteResp->Error) throw new Exception($deleteResp->Message);

            $this->dbwrite_model->commitTransaction();

            // Restore AvailableQty for any status that had stock deducted
            if ($needsStockReversal) {
                $this->dbwrite_model->reverseStockMovements($transUID, $orgUID, $userUID);
                $this->_syncProductCacheByTransUID($transUID);
            }

            $pageNo  = max(1, (int) $this->input->post('PageNo'));
            $limit   = (int) $this->input->post('RowLimit') ?: 10;
            $filter  = $this->input->post('Filter') ?: [];
            $offset  = ($pageNo - 1) * $limit;

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
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;
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
                'ExtraDiscApplied'  => ($src->ExtraDiscAmount ?? 0) > 0 ? 1 : 0,
                'ExtraDiscAmount'   => $src->ExtraDiscAmount,
                'ExtraDiscType'     => $src->ExtraDiscType,
                'NetAmount'         => $src->NetAmount,
                'DocStatus'         => 'Draft',
                'TransToken'        => generate_uuid4(),
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
                ]);
            }

            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error    = FALSE;
            $this->EndReturnData->Message  = 'Delivery challan cloned as ' . $uniqueNumber . '.';
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
            $orgUID    = $this->pageData['JwtData']->Org->OrgUID;
            if ($transUID <= 0) throw new Exception('Invalid delivery challan.');

            $this->load->model('transactions_model');
            $existing = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$existing) throw new Exception('Delivery Challan not found.');

            $current    = $existing->DocStatus;
            $challanMode = $existing->QuotationType ?? 'Non-Returnable';
            $isReturnable = in_array($challanMode, ['Returnable', 'Job Work']);

            // Mode-aware transitions:
            // Non-Returnable: Dispatched → Delivered (then → Converted to invoice)
            // Returnable / Job Work: Dispatched → Returned (stock comes back)
            // All modes: Dispatched → Cancelled
            $validTransitions = [
                'Draft'      => ['Dispatched'],
                'Dispatched' => $isReturnable ? ['Returned', 'Cancelled'] : ['Delivered', 'Cancelled'],
                'Delivered'  => [],
                'Returned'   => [],
                'Converted'  => [],
                'Cancelled'  => [],
            ];

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

            // Restore AvailableQty when goods come back (Returned) or are cancelled before delivery
            if (in_array($newStatus, ['Returned', 'Cancelled']) && $current === 'Dispatched') {
                $this->dbwrite_model->reverseStockMovements($transUID, $orgUID, $userUID);
                $this->_syncProductCacheByTransUID($transUID);
            }

            $pageNo  = max(1, (int) $this->input->post('PageNo'));
            $limit   = (int) $this->input->post('RowLimit') ?: 10;
            $filter  = $this->input->post('Filter') ?: [];
            $offset  = ($pageNo - 1) * $limit;

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

    // ── Partial Return: fetch data for the modal ─────────────────
    public function getPartialReturnData(): void {
        $this->EndReturnData = new stdClass();
        try {
            $transUID = (int) $this->input->post('TransUID');
            $orgUID   = (int) $this->pageData['JwtData']->Org->OrgUID;
            if ($transUID <= 0) throw new Exception('Invalid DC.');

            $this->load->model('transactions_model');
            $dc    = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$dc) throw new Exception('Delivery Challan not found.');

            $allowedStatuses = ['Dispatched', 'Partially Returned'];
            if (!in_array($dc->DocStatus, $allowedStatuses)) {
                throw new Exception('Partial return is only allowed for Dispatched or Partially Returned challans.');
            }

            $items      = $this->transactions_model->getTransactionItems($transUID, $orgUID);
            $returnedMap = $this->transactions_model->getDCReturnedQty($transUID, $orgUID);

            $itemData = [];
            foreach ($items as $item) {
                $dispatched      = (float)$item->Quantity;
                $alreadyReturned = (float)($returnedMap[(int)$item->TransProdUID] ?? 0);
                $stillOut        = max(0, $dispatched - $alreadyReturned);
                $itemData[] = [
                    'TransProdUID'   => (int)$item->TransProdUID,
                    'ProductUID'     => (int)$item->ProductUID,
                    'ProductName'    => $item->ProductName,
                    'UnitName'       => $item->PrimaryUnitName ?? '',
                    'DispatchedQty'  => $dispatched,
                    'ReturnedQty'    => $alreadyReturned,
                    'StillOut'       => $stillOut,
                ];
            }

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->DC      = ['UniqueNumber' => $dc->UniqueNumber, 'DocStatus' => $dc->DocStatus];
            $this->EndReturnData->Items   = $itemData;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Partial Return: save return event ────────────────────────
    public function partialReturn(): void {
        $this->EndReturnData = new stdClass();
        try {
            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $transUID   = (int) $this->input->post('TransUID');
            $orgUID     = (int) $this->pageData['JwtData']->Org->OrgUID;
            $userUID    = (int) $this->pageData['JwtData']->User->UserUID;
            $returnJson = $this->input->post('ReturnItems');
            $notes      = trim($this->input->post('Notes') ?? '');
            if ($transUID <= 0) throw new Exception('Invalid DC.');

            $returnItems = json_decode($returnJson, true);
            if (empty($returnItems) || !is_array($returnItems)) {
                throw new Exception('No items to return.');
            }

            $this->load->model('transactions_model');
            $dc = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$dc) throw new Exception('Delivery Challan not found.');

            $allowedStatuses = ['Dispatched', 'Partially Returned'];
            if (!in_array($dc->DocStatus, $allowedStatuses)) {
                throw new Exception('Cannot process return for status: ' . $dc->DocStatus);
            }

            // Load dispatched items and already-returned map
            $dcItems     = $this->transactions_model->getTransactionItems($transUID, $orgUID);
            $returnedMap = $this->transactions_model->getDCReturnedQty($transUID, $orgUID);
            $dcItemMap   = [];
            foreach ($dcItems as $item) {
                $dcItemMap[(int)$item->TransProdUID] = $item;
            }

            $wdb = $this->dbwrite_model->getWriteDb();
            $now = date('Y-m-d H:i:s');
            $totalStillOut = 0;
            $anyReturn     = false;

            foreach ($returnItems as $r) {
                $transProdUID = (int)($r['TransProdUID'] ?? 0);
                $returnQty    = (float)($r['ReturnQty']    ?? 0);
                if ($returnQty <= 0) continue;

                $item = $dcItemMap[$transProdUID] ?? null;
                if (!$item) throw new Exception('Invalid item reference: TransProdUID=' . $transProdUID);

                $dispatched      = (float)$item->Quantity;
                $alreadyReturned = (float)($returnedMap[$transProdUID] ?? 0);
                $stillOut        = $dispatched - $alreadyReturned;

                if ($returnQty > $stillOut + 0.001) {
                    throw new Exception('"' . $item->ProductName . '": return qty (' . $returnQty . ') exceeds quantity still out (' . $stillOut . ').');
                }

                // Insert DCReturnItemsTbl row
                $wdb->db_debug = FALSE;
                $insOk = $wdb->insert('Transaction.DCReturnItemsTbl', [
                    'TransUID'    => $transUID,
                    'TransProdUID'=> $transProdUID,
                    'ProductUID'  => (int)$item->ProductUID,
                    'OrgUID'      => $orgUID,
                    'ReturnedQty' => $returnQty,
                    'ReturnedOn'  => $now,
                    'Notes'       => $notes ?: null,
                    'IsDeleted'   => 0,
                    'CreatedBy'   => $userUID,
                ]);
                if (!$insOk) throw new Exception('Failed to record return for ' . $item->ProductName);

                // Add stock back (IN movement) for the returned qty
                $wdb->db_debug = FALSE;
                $wdb->query(
                    "UPDATE Products.ProductStockTbl
                        SET AvailableQty = CAST(AvailableQty AS SIGNED) + ?
                      WHERE ProductUID = ? AND OrgUID = ?",
                    [$returnQty, (int)$item->ProductUID, $orgUID]
                );

                $anyReturn = true;
                $totalStillOut += max(0, $stillOut - $returnQty);
            }

            if (!$anyReturn) throw new Exception('No valid return quantities provided.');

            // Recalculate remaining still-out across ALL items (including those not in this batch)
            foreach ($dcItems as $item) {
                $transProdUID    = (int)$item->TransProdUID;
                $dispatched      = (float)$item->Quantity;
                $alreadyReturned = (float)($returnedMap[$transProdUID] ?? 0);

                // Add this batch's return qty
                $batchReturn = 0;
                foreach ($returnItems as $r) {
                    if ((int)$r['TransProdUID'] === $transProdUID) {
                        $batchReturn = (float)($r['ReturnQty'] ?? 0);
                        break;
                    }
                }
                $newTotalReturned = $alreadyReturned + $batchReturn;
                if ($newTotalReturned < $dispatched - 0.001) {
                    $totalStillOut = 1; // at least one item still out — force Partially Returned
                    break;
                }
            }

            $newStatus = ($totalStillOut <= 0) ? 'Returned' : 'Partially Returned';
            $updOk = $wdb->query(
                "UPDATE Transaction.TransactionsTbl
                    SET DocStatus = ?, UpdatedBy = ?, UpdatedOn = ?
                  WHERE TransUID = ? AND OrgUID = ? AND IsDeleted = 0",
                [$newStatus, $userUID, $now, $transUID, $orgUID]
            );
            if (!$updOk) throw new Exception('Failed to update DC status.');

            $this->dbwrite_model->commitTransaction();

            // Sync product cache for returned items
            $this->_syncProductCacheByTransUID($transUID);

            // Return updated list
            $pageNo  = max(1, (int) $this->input->post('PageNo'));
            $limit   = (int) $this->input->post('RowLimit') ?: 10;
            $filter  = $this->input->post('Filter') ?: [];
            $offset  = ($pageNo - 1) * $limit;

            $allData      = $this->transactions_model->getTransactionPageList($limit, $offset, $this->pageModuleUID, $filter, 0);
            $allDataCount = $this->transactions_model->getTransactionCount($this->pageModuleUID, $filter);

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->Message        = 'Return recorded. Status updated to ' . $newStatus . '.';
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
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;
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
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;
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
}
