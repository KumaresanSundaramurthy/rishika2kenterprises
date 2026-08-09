<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Quotations extends MY_Controller {

    public $pageData = array();
    protected $EndReturnData;
    protected $pageModuleUID;

    public function __construct() {
        parent::__construct();

        $this->pageModuleUID = 101;
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
                'datePrefKey'       => 'quotations',
                'tabSlugMap'        => ['all' => 'All', 'open' => 'Open', 'accepted' => 'Accepted', 'converted' => 'Converted', 'cancelled' => 'Cancelled', 'draft' => 'Draft'],
                'listViewPath'      => 'transactions/quotations/list',
                'paginationUrl'     => '/transactions/getPageDetails/101',
                'listViewExtraData' => ['WhatsAppTemplate' => $templates['WhatsApp'] ?? null],
            ]);
            $this->load->view('transactions/quotations/view', $this->pageData);
        } catch (Exception $e) {
            log_message('error', '[Quotations::index] ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            redirect('dashboard', 'refresh');
        }
    }

    public function addQuotation() {

        $this->EndReturnData = new stdClass();
        $ErrorInForm = '';
        try {

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData = $this->input->post();
            $userUID  = $this->pageData['JwtData']->User->UserUID;
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;

            $itemsJson = $this->_validateTransForm($PostData);
            $amounts   = $this->_extractTransAmounts($PostData, $itemsJson);

            $customerUID  = (int) getPostValue($PostData, 'customerSearch');
            $validityDate = getPostValue($PostData, 'validityDate');
            $validityDays = (int) getPostValue($PostData, 'validityDays', 'Array', 0);

            // Auto-compute validityDate from days if not submitted directly
            if (empty($validityDate) && $validityDays > 0) {
                $validityDate = date('Y-m-d', strtotime($amounts['transDate'] . " +{$validityDays} days"));
            }
            $PostData['validityDate'] = $validityDate;

            $resolved = $this->_resolveTransPrefix(
                $amounts['isDraft'], $amounts['prefixUID'], $amounts['transNumber'],
                $amounts['transDate'], $orgUID
            );

            $amounts['moduleUID']    = $this->pageModuleUID;
            $amounts['prefixUID']    = $resolved['prefixUID'];
            $amounts['transNumber']  = $resolved['transNumber'];
            $amounts['uniqueNumber'] = $resolved['uniqueNumber'];

            $headerData = $this->_buildTransHeader(
                [
                    'TransType'       => 'Quotation',
                    'PartyType'       => 'C',
                    'PartyUID'        => $customerUID,
                    'DocTypePostKey'  => 'quotationType',
                    'DispatchPostKey' => 'dispatchFrom',
                    'InitialStatus'   => 'Pending',
                ],
                $amounts, $PostData, $orgUID, $userUID
            );

            $insertResp = $this->_insertTransactionWithRetry($headerData, $resolved['prefixUID'], $orgUID, $resolved['prefix'], $amounts['transDate']);
            if ($insertResp->Error) throw new Exception($insertResp->Message);

            $transUID     = $insertResp->ID;
            $transNumber  = $headerData['TransNumber'];
            $uniqueNumber = $headerData['UniqueNumber'];

            $this->_saveTransCharges($transUID, $orgUID, $userUID, $PostData);

            $detailData = $this->_buildTransDetail(
                [
                    'PartyType'           => 'C',
                    'PartyUID'            => $customerUID,
                    'ValidityDatePostKey' => 'validityDate',
                    'ValidityDaysPostKey' => 'validityDays',
                ],
                $amounts, $PostData, $transUID
            );
            $this->dbwrite_model->insertData('Transaction', 'TransDetailTbl', $detailData);

            $this->_insertTransItems($transUID, $amounts['financialYear'], $orgUID, $userUID, $amounts['items']);
            $this->_saveAttachments($transUID);

            $this->dbwrite_model->commitTransaction();
            $this->cachehelper->touchCustomer($customerUID);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Quotation created successfully.';
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'ADD_QUOTATION', 'Quotation', (int) $transUID, (string) ($uniqueNumber ?? ''),
                [], 'Created quotation ' . ($uniqueNumber ?? ''), 'Quotations', 'TRANSACTION', 'SUCCESS', '', 'WEB', [], [], $PostData
            );
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
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;

            $transUID = (int) getPostValue($PostData, 'TransUID');
            if ($transUID <= 0) throw new Exception('Quotation ID is required.');

            $itemsJson = $this->_validateTransForm($PostData);
            $amounts   = $this->_extractTransAmounts($PostData, $itemsJson);

            $amounts['moduleUID'] = $this->pageModuleUID;
            $customerUID = (int) getPostValue($PostData, 'customerSearch');
            $prefixUID   = $amounts['prefixUID'];
            $transNumber = $amounts['transNumber'];
            $isDraft     = $amounts['isDraft'];
            $items       = $amounts['items'];

            $cfg = [
                'TransType'       => 'Quotation',
                'PartyType'       => 'C',
                'PartyUID'        => $customerUID,
                'DocTypePostKey'  => 'quotationType',
                'DispatchPostKey' => 'dispatchFrom',
                'InitialStatus'   => 'Pending',
            ];

            $validityDate = getPostValue($PostData, 'validityDate');
            $validityDays = (int) getPostValue($PostData, 'validityDays', 'Array', 0);
            if (empty($validityDate) && $validityDays > 0) {
                $validityDate = date('Y-m-d', strtotime($amounts['transDate'] . " +{$validityDays} days"));
            }

            // Load existing row to check current DocStatus (needed for draft → pending promotion)
            $this->load->model('transactions_model');
            $existing = $this->transactions_model->getTransactionById($transUID, $orgUID, $this->pageModuleUID);
            if (!$existing) throw new Exception('Quotation not found.');

            // --- Build UniqueNumber when promoting Draft → Pending ---
            $uniqueNumber = NULL;
            if ($existing->DocStatus === 'Draft' && !$isDraft) {
                if ($prefixUID <= 0) throw new Exception('Please select a prefix to finalize this quotation.');
                if ($transNumber <= 0) throw new Exception('Transaction number must be greater than 0.');
                if ($transNumber > 2147483647) throw new Exception('Transaction number exceeds the maximum allowed value of 2,147,483,647. Please use a smaller number or create a new prefix series.');

                $prefixData = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.PrefixUID' => $prefixUID, 'Prefix.OrgUID' => $orgUID]);
                if (empty($prefixData->Data)) throw new Exception('Invalid prefix selected.');
                $prefix = $prefixData->Data[0];

                // Race condition guard for updateQuotation
                if ($this->dbwrite_model->checkTransactionNumberExists($prefixUID, $transNumber, $orgUID)) {
                    $transNumber = $this->dbwrite_model->getNextAvailableTransNumber($prefixUID, $orgUID);
                    if ($transNumber === -1) throw new Exception('This prefix series has reached its maximum (2,147,483,647). Please create a new prefix to continue.');
                }

                list($uniqueNumber) = $this->buildUniqueNumber($prefix, $transNumber, $amounts['transDate']);
            }

            $isInterState      = $amounts['igstAmount'] > 0 ? 1 : ($amounts['cgstAmount'] > 0 || $amounts['sgstAmount'] > 0 ? 0 : NULL);
            $_cc               = $this->transactions_model->getCustomerCountryCode($customerUID);
            $isForeignCustomer = $_cc !== NULL ? ($_cc === 'IN' ? 0 : 1) : NULL;
            $commonDetail = [
                'ValidityDays'      => $validityDays ?: NULL,
                'ValidityDate'      => $validityDate ?: NULL,
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

            // --- CLONE PATH: Draft being saved as Pending and newer records exist ---
            // We insert a new TransactionsTbl row (gets a higher auto-increment TransUID)
            // so it naturally sorts to the top of the DESC list, then hard-delete the old draft.
            if ($existing->DocStatus === 'Draft' && !$isDraft
                && $this->transactions_model->hasNewerTransactions($transUID, $orgUID, $this->pageModuleUID)) {

                // Patch amounts with the resolved numbers before building the insert header
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

                // Soft-delete old items (audit trail), insert new items under new TransUID
                $this->dbwrite_model->updateData(
                    'Transaction', 'TransProductsTbl',
                    ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID],
                    ['TransUID' => $transUID, 'IsDeleted' => 0]
                );
                $this->_insertTransItems($newTransUID, $amounts['financialYear'], $orgUID, $userUID, $items);

                // Hard-delete old draft header and its detail — only TransactionsTbl drives list order
                $this->dbwrite_model->deleteInTransaction('Transaction', 'TransactionsTbl', ['TransUID' => $transUID]);
                $this->dbwrite_model->deleteInTransaction('Transaction', 'TransDetailTbl',  ['TransUID' => $transUID]);

            } else {
                // --- NORMAL UPDATE PATH (draft stays draft, or no newer records exist) ---
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
            $this->EndReturnData->Message = 'Quotation updated successfully.';
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'UPDATE_QUOTATION', 'Quotation', (int) $activeTransUID, (string) ($uniqueNumber ?? $existing->UniqueNumber ?? ''),
                [], 'Updated quotation ' . ($uniqueNumber ?? $existing->UniqueNumber ?? ''), 'Quotations', 'TRANSACTION', 'SUCCESS', '', 'WEB', [], [], $PostData
            );

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
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;

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
                ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID],
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
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'DELETE_QUOTATION', 'Quotation', (int) $transUID, '',
                [], 'Deleted quotation #' . $transUID, 'Quotations', 'TRANSACTION'
            );

            $this->_buildListResponse('transactions/quotations/list', '/transactions/getPageDetails/101');

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

            $PostData      = $this->input->post();
            $transUID      = (int) getPostValue($PostData, 'TransUID');
            $convertTarget = trim(getPostValue($PostData, 'ConvertTarget') ?: 'Invoice');

            if ($transUID <= 0) throw new Exception('Invalid quotation.');

            // Do NOT change quotation status here.
            // Status is set to Converted only after the target document is saved.
            if ($convertTarget === 'SalesOrder') {
                $redirectURL = '/salesorders/create?fromQuotation=' . $transUID;
            } else {
                $redirectURL = '/invoices/create?fromQuotation=' . $transUID;
            }

            $this->EndReturnData->Error       = FALSE;
            $this->EndReturnData->Message     = 'Redirecting...';
            $this->EndReturnData->RedirectURL = $redirectURL;

        } catch (Exception $e) {
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
            $orgUID    = $this->pageData['JwtData']->Org->OrgUID;

            if ($transUID <= 0) throw new Exception('Invalid quotation.');

            $validTransitions = [
                'Draft'     => ['Pending'],
                'Pending'   => ['Accepted', 'Cancelled'],
                'Accepted'  => ['Pending', 'Cancelled', 'Converted'],
                'Cancelled' => [],
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

            $docNum = $existing->UniqueNumber ?? '';
            $prefix = $docNum ? "{$docNum} " : '';
            if ($newStatus === 'Cancelled') {
                $msg = "Quotation {$prefix}cancelled successfully.";
            } elseif ($newStatus === 'Accepted') {
                $msg = "Quotation {$prefix}marked as accepted.";
            } elseif ($newStatus === 'Pending' && $current === 'Accepted') {
                $msg = "Quotation {$prefix}reverted to open.";
            } elseif ($newStatus === 'Pending') {
                $msg = "Quotation {$prefix}sent successfully.";
            } else {
                $msg = 'Status updated.';
            }

            $this->EndReturnData->Error     = FALSE;
            $this->EndReturnData->Message   = $msg;
            $this->auditlog->log(
                (int) $orgUID, (int) $userUID,
                'UPDATE_QUOTATION_STATUS', 'Quotation', (int) $transUID, (string) ($existing->UniqueNumber ?? ''),
                ['NewStatus' => $newStatus], 'Updated quotation status #' . $transUID, 'Quotations', 'TRANSACTION'
            );
            $this->EndReturnData->NewStatus = $newStatus;

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

            // Clone: pre-fill from source quotation if ?fromQuotation= is in URL
            $fromUID = (int) $this->input->get('fromQuotation');
            $this->pageData['CloneData']  = null;
            $this->pageData['CloneItems'] = [];
            if ($fromUID > 0) {
                $cloneData  = $this->transactions_model->getTransactionById($fromUID, $orgUID, $this->pageModuleUID);
                $cloneItems = $cloneData ? $this->transactions_model->getTransactionItems($fromUID, $orgUID) : [];
                $this->pageData['CloneData']  = $cloneData;
                $this->pageData['CloneItems'] = $cloneItems;
            }

            $this->_getDispatchAddresses($orgUID);

            $this->pageData['fltStorageData'] = [];
            if (!empty($this->pageData['JwtData']->GenSettings->EnableStorage)) {
                $this->load->model('storage_model');
                $this->pageData['fltStorageData'] = $this->storage_model->getStorageDetails([]) ?? [];
            }

            $defaultValidityDays = (int)($this->pageData['JwtData']->TransSettings->QuotValidityDays ?? 7);
            if ($defaultValidityDays < 1) $defaultValidityDays = 7;
            $this->pageData['DefaultValidityDays'] = $defaultValidityDays;
            $this->pageData['DefaultValidityDate'] = date('Y-m-d', strtotime("+{$defaultValidityDays} days"));

            $this->pageData['AdditionalCharges']  = $this->_getAdditionalChargesForOrg((int)$orgUID, true);
            $this->pageData['TaxList']            = $this->_getTaxList();
            $this->pageData['TransactionCharges'] = [];
            $this->pageData['IsEditMode']         = false;

            $this->_loadUpstashConfig();

            $this->load->view('transactions/quotations/forms/form', $this->pageData);

        } catch (Exception $e) {
            redirect('dashboard', 'refresh');
        }

    }

    public function edit($transUID = 0) {

        try {

            $transUID = (int) $transUID;
            if ($transUID <= 0) redirect('quotations', 'refresh');

            $orgUID = $this->pageData['JwtData']->Org->OrgUID;
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
            $prefixResult                        = $this->transactions_model->getTransactionsPrefixDetails(['Prefix.OrgUID' => $orgUID, 'Prefix.ModuleUID' => $this->pageModuleUID]);
            $this->pageData['PrefixData']        = $prefixResult->Data ?? [];

            $nextNumberMap = [];
            foreach ($this->pageData['PrefixData'] as $pd) {
                $nextNumberMap[(int)$pd->PrefixUID] = $this->transactions_model->getNextTransactionNumber(
                    $pd->PrefixUID, $orgUID, $this->pageModuleUID
                );
            }
            $this->pageData['NextNumberMap'] = $nextNumberMap;

            // Dispatch address — use same method as create so DispatchAddresses (plural) is set
            $this->_getDispatchAddresses($orgUID);

            // Attachments — load server-side to avoid AJAX call on page load
            $this->pageData['QuotAttachments'] = $this->transactions_model->getTransactionAttachments($transUID, $orgUID);

            $this->pageData['fltStorageData'] = [];
            if (!empty($this->pageData['JwtData']->GenSettings->EnableStorage)) {
                $this->load->model('storage_model');
                $this->pageData['fltStorageData'] = $this->storage_model->getStorageDetails([]) ?? [];
            }

            $defaultValidityDays = (int)($this->pageData['JwtData']->TransSettings->QuotValidityDays ?? 7);
            if ($defaultValidityDays < 1) $defaultValidityDays = 7;
            $this->pageData['DefaultValidityDays'] = $defaultValidityDays;
            $this->pageData['DefaultValidityDate'] = date('Y-m-d', strtotime("+{$defaultValidityDays} days"));

            $this->pageData['AdditionalCharges']  = $this->_getAdditionalChargesForOrg((int)$orgUID, true);
            $this->pageData['TransactionCharges'] = $this->transactions_model->getTransactionCharges($transUID, (int)$orgUID);
            $this->pageData['TaxList']            = $this->_getTaxList();
            $this->pageData['IsEditMode']         = true;

            $this->load->view('transactions/quotations/forms/form', $this->pageData);

        } catch (Exception $e) {
            redirect('quotations', 'refresh');
        }

    }

}