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
    protected $EndReturnData;
    /** @var int */
    protected $pageModuleUID;

    public function __construct() {
        parent::__construct();
        $this->pageModuleUID = 112;
        $this->load->helper('transaction');
    }

    // â"€â"€ List page â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€
    public function index(): void {
        if (!$this->_loadPageTitle($this->pageModuleUID)) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }
        try {
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;
            $this->_loadTransactionIndexPage([
                'datePrefKey'  => 'deliverychallans',
                'tabSlugMap'   => ['all' => 'All', 'dispatched' => 'Dispatched', 'delivered' => 'Delivered', 'converted' => 'Converted', 'cancelled' => 'Cancelled', 'draft' => 'Draft'],
                'listViewPath' => 'transactions/deliverychallans/list',
                'paginationUrl'=> '/transactions/getPageDetails/112',
            ]);
            $this->load->view('transactions/deliverychallans/view', $this->pageData);
        } catch (Exception $e) {
            redirect('dashboard', 'refresh');
        }
    }

    // â"€â"€ Create form â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€
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

            $this->pageData['AdditionalCharges']  = $this->_getAdditionalChargesForOrg((int)$orgUID, true);
            $this->pageData['TaxList']            = $this->_getTaxList();
            $this->pageData['TransactionCharges'] = [];
            $this->pageData['IsEditMode']         = false;

            $this->_getDispatchAddresses($orgUID);

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

    //â"€â"€ Edit form â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€
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

            // Pre-fetch attachments â€" eliminates the AJAX call on the edit form
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

            $this->pageData['AdditionalCharges']  = $this->_getAdditionalChargesForOrg((int)$orgUID, true);
            $this->pageData['TransactionCharges'] = $this->transactions_model->getTransactionCharges($transUID, (int)$orgUID);
            $this->pageData['TaxList']            = $this->_getTaxList();
            $this->pageData['IsEditMode']         = true;

            $this->_getDispatchAddresses($orgUID);

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

    //â"€â"€ Save new challan â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€
    public function addDeliveryChallan() {
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

            // -- SO-linked DC: enforce customer lock + item/qty restrictions --
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

            $this->load->model('transactions_model');

            $resolved = $this->_resolveTransPrefix($isDraft, $amounts['prefixUID'], $amounts['transNumber'], $transDate, $orgUID);
            $amounts['moduleUID']    = $this->pageModuleUID;
            $amounts['prefixUID']    = $resolved['prefixUID'];
            $amounts['transNumber']  = $resolved['transNumber'];
            $amounts['uniqueNumber'] = $resolved['uniqueNumber'];

            $headerData = $this->_buildTransHeader(
                [
                    'TransType'       => 'DeliveryChallan',
                    'PartyType'       => 'C',
                    'PartyUID'        => $customerUID,
                    'DocTypePostKey'  => 'challanType',
                    'DocTypeDefault'  => 'Non-Returnable',
                    'DispatchPostKey' => 'dispatchFrom',
                    'InitialStatus'   => 'Dispatched',
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
                    'ValidityDatePostKey' => '',
                    'ReferencePostKey'   => 'vehicleNumber',
                    'extraDetailFields'  => [
                        'ExpectedDeliveryDate' => 'returnDate',
                        'DeliveryByDate'       => 'deliveryBy',
                    ],
                ],
                $amounts, $PostData, $transUID
            );
            $detailResp = $this->dbwrite_model->insertData('Transaction', 'TransDetailTbl', $detailData);
            if ($detailResp->Error) throw new Exception($detailResp->Message);

            $this->_insertTransItems($transUID, $financialYear, $orgUID, $userUID, $items);

            // Conversion tracking: SalesOrder â†' DeliveryChallan
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
                $this->dbwrite_model->saveStockMovements($transUID, $this->pageModuleUID, $orgUID, $userUID, $items, $this->_branchUID());
                $this->_syncProductCacheFromItems($items);
            }

            $this->EndReturnData->Error    = FALSE;
            $this->EndReturnData->Message  = 'Delivery challan created successfully.';
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'ADD_DELIVERY_CHALLAN', 'DeliveryChallan', (int) $transUID, (string) ($uniqueNumber ?? ''),
                [], 'Created delivery challan ' . ($uniqueNumber ?? ''), 'DeliveryChallans', 'TRANSACTION', 'SUCCESS', '', 'WEB', [], [], $PostData
            );
            $this->EndReturnData->TransUID = $transUID;
        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // â"€â"€ Update existing challan â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€
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

            $itemsJson = $this->_validateTransForm($PostData);
            $amounts   = $this->_extractTransAmounts($PostData, $itemsJson);

            $amounts['moduleUID'] = $this->pageModuleUID;
            $customerUID    = (int) getPostValue($PostData, 'customerSearch');
            $prefixUID      = $amounts['prefixUID'];
            $transNumber    = $amounts['transNumber'];
            $isDraft        = $amounts['isDraft'];
            $items          = $amounts['items'];
            $returnDate     = getPostValue($PostData, 'returnDate');
            $deliveryByDate = getPostValue($PostData, 'deliveryBy');

            $cfg = [
                'TransType'      => 'DeliveryChallan',
                'PartyType'      => 'C',
                'PartyUID'       => $customerUID,
                'DocTypePostKey' => 'challanType',
                'DocTypeDefault' => 'Non-Returnable',
                'InitialStatus'  => 'Dispatched',
            ];

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

                [$uniqueNumber] = $this->buildUniqueNumber($prefix, $transNumber, $amounts['transDate']);
            }

            $isInterState      = $amounts['igstAmount'] > 0 ? 1 : ($amounts['cgstAmount'] > 0 || $amounts['sgstAmount'] > 0 ? 0 : NULL);
            $_cc               = $this->transactions_model->getCustomerCountryCode($customerUID);
            $isForeignCustomer = $_cc !== NULL ? ($_cc === 'IN' ? 0 : 1) : NULL;

            $commonDetail = [
                'ValidityDays'        => NULL,
                'ValidityDate'        => NULL,
                'ExpectedDeliveryDate'=> $returnDate     ?: NULL,
                'DeliveryByDate'      => $deliveryByDate ?: NULL,
                'Reference'           => getPostValue($PostData, 'vehicleNumber') ?: NULL,
                'Notes'               => getPostValue($PostData, 'transNotes') ?: NULL,
                'TermsConditions'     => getPostValue($PostData, 'transTermsCond') ?: NULL,
                'SignatureUID'        => (int)getPostValue($PostData, 'SignatureUID') ?: NULL,
                'PlaceOfSupplyCode'   => getPostValue($PostData, 'placeOfSupplyCode') ?: NULL,
                'PlaceOfSupplyName'   => getPostValue($PostData, 'placeOfSupplyName') ?: NULL,
                'IsInterState'        => $isInterState,
                'IsForeignCustomer'   => $isForeignCustomer,
                'PriceListUID'        => (int)getPostValue($PostData, 'PriceListUID') ?: NULL,
                'PriceListData'       => getPostValue($PostData, 'PriceListData') ?: NULL,
            ];

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

            $this->_saveTransCharges($transUID, $orgUID, $userUID, $PostData);
            $this->dbwrite_model->commitTransaction();
            $this->cachehelper->touchCustomer($customerUID);
            $this->_saveAttachments($transUID);
            $this->_softDeleteAttachments($this->input->post('RemovedAttachIDs') ?? '');
            $this->transactions_model->generateAndStorePdf($transUID, $orgUID, $this->pageModuleUID);

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
                $this->dbwrite_model->saveStockMovements($transUID, $this->pageModuleUID, $orgUID, $userUID, $items, $this->_branchUID());
                $this->_syncProductCacheFromItems($items);
            }

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Delivery challan updated successfully.';
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'UPDATE_DELIVERY_CHALLAN', 'DeliveryChallan', (int) $transUID, (string) ($uniqueNumber ?? $existing->UniqueNumber ?? ''),
                [], 'Updated delivery challan ' . ($uniqueNumber ?? $existing->UniqueNumber ?? ''), 'DeliveryChallans', 'TRANSACTION', 'SUCCESS', '', 'WEB', [], [], $PostData
            );
        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // â"€â"€ Delete challan â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€
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

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->Message        = 'Delivery challan deleted successfully.';
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'DELETE_DELIVERY_CHALLAN', 'DeliveryChallan', (int) $transUID, '',
                [], 'Deleted delivery challan #' . $transUID, 'DeliveryChallans', 'TRANSACTION'
            );
            $this->_buildListResponse('transactions/deliverychallans/list', '/transactions/getPageDetails/112');
        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // â"€â"€ Duplicate challan â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€
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
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'DUPLICATE_DELIVERY_CHALLAN', 'DeliveryChallan', (int) $newTransUID, (string) $uniqueNumber,
                [], 'Duplicated delivery challan ' . $uniqueNumber, 'DeliveryChallans', 'TRANSACTION'
            );
            $this->EndReturnData->TransUID = $newTransUID;
            $this->EndReturnData->EditURL  = '/deliverychallan/' . $newTransUID . '/edit';
        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // â"€â"€ Status update â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€
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
            $challanMode = $existing->DocType ?? 'Non-Returnable';
            $isReturnable = in_array($challanMode, ['Returnable', 'Job Work']);

            // Mode-aware transitions:
            // Non-Returnable: Dispatched â†' Delivered (then â†' Converted to invoice)
            // Returnable / Job Work: Dispatched â†' Returned (stock comes back)
            // All modes: Dispatched â†' Cancelled
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

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->Message        = 'Status updated to ' . $newStatus . '.';
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'UPDATE_DC_STATUS', 'DeliveryChallan', (int) $transUID, (string) ($existing->UniqueNumber ?? ''),
                ['NewStatus' => $newStatus], 'Updated delivery challan status #' . $transUID, 'DeliveryChallans', 'TRANSACTION'
            );
            $this->EndReturnData->NewStatus      = $newStatus;
            $this->_buildListResponse('transactions/deliverychallans/list', '/transactions/getPageDetails/112');
        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // â"€â"€ Partial Return: fetch data for the modal â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€
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

    // â"€â"€ Partial Return: save return event â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€
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

                // Add stock back via ledger so reverseStockMovements nets it on DC delete
                $this->dbwrite_model->applyDCReturnStockMovement(
                    $transUID,
                    $this->pageModuleUID,
                    $orgUID,
                    $userUID,
                    (int)$item->ProductUID,
                    (float)$returnQty,
                    $transProdUID,
                    $this->_branchUID()
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
                    $totalStillOut = 1; // at least one item still out â€" force Partially Returned
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

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->Message        = 'Return recorded. Status updated to ' . $newStatus . '.';
            $this->EndReturnData->NewStatus      = $newStatus;
            $this->_buildListResponse('transactions/deliverychallans/list', '/transactions/getPageDetails/112');

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // â"€â"€ Convert to Invoice â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€
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
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'CONVERT_DC_TO_INVOICE', 'DeliveryChallan', (int) $transUID, (string) ($existing->UniqueNumber ?? ''),
                [], 'Converted delivery challan ' . ($existing->UniqueNumber ?? '') . ' to invoice', 'DeliveryChallans', 'TRANSACTION'
            );
            $this->EndReturnData->RedirectURL = '/invoices/create?fromChallan=' . $transUID;
        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // â"€â"€ Detail (for print/view modal) â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€â"€

}
