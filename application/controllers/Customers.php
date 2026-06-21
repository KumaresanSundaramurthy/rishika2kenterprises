<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Customers extends MY_Controller {

    public $pageData = array();
    private $EndReturnData;

    public function __construct() {
        parent::__construct();
    }

    // ── Internal helpers ──────────────────────────────────────────────────────
    private function _initModule() {
        $GeneralSettings = $this->pageData['JwtData']->GenSettings ?? new stdClass();
        $this->pageData['Limit'] = $GeneralSettings->RowLimit ?? 10;
    }

    private function _fetchTableData($pageNo, $limit, $filter = []) {

        $orgUID = $this->pageData['JwtData']->Org->OrgUID;
        $offset = max(0, ($pageNo - 1) * $limit);

        $this->load->model('customers_model');
        $result = $this->customers_model->getCustomerListPaginated($orgUID, $limit, $offset, $filter);

        $rowHtml = $this->load->view('customers/list', [
            'DataLists'       => $result->rows,
            'SerialNumber'    => $offset,
            'JwtData'         => $this->pageData['JwtData'],
            'GenSettings'     => $this->pageData['JwtData']->GenSettings,
        ], TRUE);

        $resp               = new stdClass();
        $resp->RecordHtmlData = $rowHtml;
        $resp->Pagination     = $this->globalservice->buildPagePaginationHtml('/customers/getCustomersPageDetails', $result->totalCount, $pageNo, $limit);
        $resp->TotalCount     = $result->totalCount;
        return $resp;
        
    }

    // ── Page routes ───────────────────────────────────────────────────────────
    public function index() {
        if (!$this->_loadPageTitle()) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }
        try {

            $this->_initModule();
            $limit = $this->pageData['Limit'];

            $pageData = $this->_fetchTableData(1, $limit);
            $this->pageData['ModRowData']    = $pageData->RecordHtmlData;
            $this->pageData['ModPagination'] = $pageData->Pagination;

            $this->load->model('customers_model');
            $this->pageData['CustStats']        = $this->customers_model->getCustomerStats($this->pageData['JwtData']->Org->OrgUID);
            $this->pageData['CustomerTypeList'] = $this->customers_model->getCustomerTypeList($this->pageData['JwtData']->Org->OrgUID);
            $this->pageData['Tags']             = $this->customers_model->getCustomerTags($this->pageData['JwtData']->Org->OrgUID);
            $this->pageData['GroupTypes']       = $this->_groupTypesList();

            // Resolve org phone country code from JwtData (sourced from OrganisationTbl at login)
            $this->pageData['OrgCCode'] = $this->pageData['JwtData']->Org->OrgCCode  ?? '';
            $this->pageData['OrgCISO2'] = $this->pageData['JwtData']->Org->OrgCISO2  ?? '';

            $orgUsers = $this->_requireCache($this->redisservice->orgKey('org_users'));
            if (!$orgUsers) return;
            $this->pageData['OrgUsers']      = $orgUsers;
            $this->pageData['ShowUserFilter'] = count($orgUsers) > 1;

            $this->load->view('customers/view', $this->pageData);

        } catch (Exception $e) {
            redirect('dashboard', 'refresh');
        }
    }

    public function getCustomersPageDetails($pageNo = 0) {
        $this->EndReturnData = new stdClass();
        try {

            $pageNo = max(1, (int) $pageNo);
            $filter = $this->input->post('Filter') ?: [];

            $this->_initModule();

            $limit = (int) ($this->input->post('RowLimit') ?: $this->pageData['Limit']);

            $pageData = $this->_fetchTableData($pageNo, $limit, $filter);

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->RecordHtmlData = $pageData->RecordHtmlData;
            $this->EndReturnData->Pagination     = $pageData->Pagination;
            $this->EndReturnData->TotalCount     = $pageData->TotalCount;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function searchCustomers() {
        $this->EndReturnData = new stdClass();
        $this->EndReturnData->Error = false;
        $this->EndReturnData->Lists = [];
        try {

            $term = trim($this->input->get('term'));
            if ($term) {
                $this->load->model('customers_model');
                $customersData = $this->customers_model->getCustomersDetails($term);

                foreach ($customersData as $value) {
                    $this->EndReturnData->Lists[] = [
                        'id'   => $value->CustomerUID,
                        'text' => $value->Area ? $value->Name . ' (' . $value->Area . ')' : $value->Name,
                    ];
                }
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = 'Unable to fetch customers at the moment.';
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    private function buildCustomerFormData($postData, $isCreate = false) {
        $data = [
            'Name'              => getPostValue($postData, 'Name'),
            'Area'              => getPostValue($postData, 'Area'),
            'OrgUID'            => $this->pageData['JwtData']->Org->OrgUID,
            'BranchUID'         => $this->pageData['JwtData']->Org->BranchUID,
            'EmailAddress'      => getPostValue($postData, 'EmailAddress'),
            'CountryCode'       => getPostValue($postData, 'CountryCode'),
            'CountryISO2'       => getPostValue($postData, 'CountryISO2', '', 'IN'),
            'MobileNumber'      => getPostValue($postData, 'MobileNumber'),
            'DebitCreditType'   => getPostValue($postData, 'DebitCreditCheck', '', 'Debit'),
            'DebitCreditAmount' => getPostValue($postData, 'DebitCreditAmount', '', 0),
            'PANNumber'         => getPostValue($postData, 'PANNumber'),
            'ContactPerson'     => getPostValue($postData, 'ContactPerson'),
            'DateOfBirth'       => getPostValue($postData, 'CPDateOfBirth'),
            'GSTIN'             => getPostValue($postData, 'GSTIN'),
            'CompanyName'       => getPostValue($postData, 'CompanyName'),
            'DiscountPercent'   => getPostValue($postData, 'DiscountPercent', '', 0),
            'CreditPeriod'      => getPostValue($postData, 'CreditPeriod', '', 30),
            'CreditLimit'       => getPostValue($postData, 'CreditLimit', '', 0),
            'Notes'             => getPostValue($postData, 'Notes'),
            'Tags'              => getPostValue($postData, 'Tags', 'Comma'),
            'CCEmails'          => getPostValue($postData, 'CCEmails', 'Comma'),
            'CustomerTypeUID'   => (int) getPostValue($postData, 'CustomerTypeUID', '', 0),
            'GroupUID'          => (int) getPostValue($postData, 'GroupUID', '', 0) ?: null,
            'IsGroupPrimary'    => 0,
            'SalutationUID'     => (int) getPostValue($postData, 'SalutationUID') ?: null,
            'UpdatedBy'         => $this->pageData['JwtData']->User->UserUID,
        ];
        if ($isCreate) {
            $data['CustToken'] = generate_uuid4();
            $data['CreatedBy'] = $this->pageData['JwtData']->User->UserUID;
        }
        return $data;
    }

    public function addCustomerData() {
        
        $this->EndReturnData = new stdClass();
        $ErrorInForm = '';
        try {
            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData = $this->input->post();

            $this->load->model('formvalidation_model');
            $ErrorInForm = $this->formvalidation_model->custValidateForm($PostData);
            if (!empty($ErrorInForm)) throw new InvalidArgumentException('VALIDATION_ERROR');

            $customerFormData = $this->buildCustomerFormData($PostData, true);

            $InsertDataResp = $this->dbwrite_model->insertData('Customers', 'CustomerTbl', $customerFormData);
            if ($InsertDataResp->Error) throw new Exception($InsertDataResp->Message);
            $CustomerUID = $InsertDataResp->ID;

            if (isset($_FILES['UploadImage'])) {
                $UploadResp = $this->globalservice->fileUploadService($_FILES['UploadImage'], 'customers/images/', 'Image', ['Customers', 'CustomerTbl', ['CustomerUID' => $CustomerUID]]);
                if ($UploadResp->Error) throw new Exception($UploadResp->Message);
            }

            $this->globalservice->saveBankDetails($CustomerUID, $this->input->post('BankDetailsJSON'), 'Customers', 'CustBankDetailsTbl', [], 'CustBankDetUID');

            foreach ([['Bill', 'Billing'], ['Ship', 'Shipping']] as [$prefix, $type]) {
                $this->globalservice->saveAddressInfo($PostData, $CustomerUID, $prefix, $type, 'Customers', 'CustAddressTbl', 'CustAddressUID', 'CustomerUID');
            }

            $this->load->library('accountledger');
            $this->accountledger->createLedgerAccountingInfo(
                $CustomerUID,
                [
                    'Name'               => getPostValue($PostData, 'Name'),
                    'OpeningBalance'     => getPostValue($PostData, 'DebitCreditAmount', '', 0),
                    'OpeningBalanceType' => getPostValue($PostData, 'DebitCreditCheck', '', 'Debit'),
                ],
                'Customer'
            );

            $this->load->model('customers_model');
            $initAmt  = (float) getPostValue($PostData, 'DebitCreditAmount', '', 0);
            $initType = getPostValue($PostData, 'DebitCreditCheck', '', 'Debit');
            if ($initAmt > 0) {
                $this->customers_model->saveCustomerOpeningBalance(
                    $this->pageData['JwtData']->Org->OrgUID, $CustomerUID,
                    $initAmt, $initType, null,
                    $this->pageData['JwtData']->User->UserUID, true
                );
                $this->customers_model->saveCustomerYearOpening(
                    $this->pageData['JwtData']->Org->OrgUID, $CustomerUID,
                    $this->_currentFinancialYear(),
                    $initAmt, $initType,
                    $this->pageData['JwtData']->User->UserUID, false, true
                );
            }

            // Build Customer response from POST data — no extra DB query needed
            $custName = getPostValue($PostData, 'Name');
            $custArea = getPostValue($PostData, 'Area');
            $cust_Data = [
                'id'   => $CustomerUID,
                'text' => $custArea ? $custName . ' (' . $custArea . ')' : $custName,
            ];
            if (!empty(getPostValue($PostData, 'BillAddrLine1'))) {
                $cust_Data['address'] = [
                    'Line1'   => getPostValue($PostData, 'BillAddrLine1'),
                    'Line2'   => getPostValue($PostData, 'BillAddrLine2'),
                    'Pincode' => getPostValue($PostData, 'BillAddrPincode'),
                    'City'    => getPostValue($PostData, 'BillAddrCityText'),
                    'State'   => getPostValue($PostData, 'BillAddrStateText'),
                ];
            }
            $this->EndReturnData->Customer = $cust_Data;

            $this->dbwrite_model->commitTransaction();

            // Cache update is a Redis write — not a DB operation, safe after commit.
            // Must be after commit so ReadDB can see the now-committed customer row.
            $this->cachehelper->upsertCustomer($CustomerUID);

            // Success is confirmed — customer is saved
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Created Successfully';

            // List + stats are optional extras; failures here must NOT flip Error to true
            if ($this->input->post('returnList') == 1) {
                try {
                    $this->_initModule();
                    $pageData = $this->_fetchTableData(1, $this->pageData['Limit']);
                    $this->EndReturnData->List       = $pageData->RecordHtmlData;
                    $this->EndReturnData->Pagination = $pageData->Pagination;
                    $this->EndReturnData->Stats      = $this->customers_model->getCustomerStats($this->pageData['JwtData']->Org->OrgUID);
                } catch (Exception $e) {
                    // non-fatal — list refresh failed but customer was created successfully
                }
            }

        } catch (InvalidArgumentException $e) {
            $this->dbwrite_model->rollbackTransaction();
            if ($e->getMessage() === 'VALIDATION_ERROR') {
                $this->EndReturnData->Error  = true;
                $this->EndReturnData->Message = strip_tags($ErrorInForm);
                $this->EndReturnData->Errors  = 'Please correct the highlighted errors.';
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

    public function loadModalForm($type = 'add', $uid = 0) {
        $this->EndReturnData = new stdClass();
        try {
            $type = in_array($type, ['add', 'edit', 'clone']) ? $type : 'add';
            $uid  = (int) $uid;

            $orgUID = $this->pageData['JwtData']->Org->OrgUID;
            $this->load->model('customers_model');
            $this->pageData['CustomerTypeList'] = $this->customers_model->getCustomerTypeList($orgUID);

            $this->pageData['CustomerGroupList'] = $this->customers_model->getActiveGroupsForDropdown($orgUID);

            $formData     = null;
            $bankDetails  = [];
            $billingAddr  = null;
            $shippingAddr = null;

            if (in_array($type, ['edit', 'clone']) && $uid > 0) {
                $getCustData = $this->customers_model->getCustomers(['Customers.CustomerUID' => $uid]);
                if (!empty($getCustData)) {
                    $formData    = $getCustData[0];
                    $bankDetails = $this->customers_model->getCustomerBankInfo(['CustBankDetails.CustomerUID' => $uid]);
                    $addrInfo    = $this->customers_model->getCustomerAddress(['CustAddress.CustomerUID' => $uid]);
                    foreach ($addrInfo as $addr) {
                        if ($addr->AddressType === 'Billing')  $billingAddr  = $addr;
                        if ($addr->AddressType === 'Shipping') $shippingAddr = $addr;
                    }
                }
            }

            $html = $this->load->view('customers/forms/modal_body', [
                'FormMode'          => $type,
                'FormData'          => $formData,
                'BankDetails'       => $bankDetails,
                'BillingAddr'       => $billingAddr,
                'ShippingAddr'      => $shippingAddr,
                'CustomerTypeList'  => $this->pageData['CustomerTypeList'],
                'CustomerGroupList' => $this->pageData['CustomerGroupList'],
                'OrgCCode'          => $this->pageData['JwtData']->Org->OrgCCode  ?? '',
                'OrgCISO2'          => $this->pageData['JwtData']->Org->OrgCISO2  ?? '',
                'JwtData'           => $this->pageData['JwtData'],
            ], TRUE);

            $this->EndReturnData->Error        = FALSE;
            $this->EndReturnData->Html         = $html;
            $this->EndReturnData->FormMode     = $type;
            $this->EndReturnData->ImgData      = $formData ? ($formData->Image ?? '') : '';
            $this->EndReturnData->BillingAddr  = $billingAddr;
            $this->EndReturnData->ShippingAddr = $shippingAddr;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function syncCustomersCache() {

        $this->EndReturnData = new stdClass();
        try {

            $orgUID  = $this->pageData['JwtData']->Org->OrgUID;
            $userUID = $this->pageData['JwtData']->User->UserUID;

            $this->load->model('customers_model');

            // Fetch all active customers
            $customers = $this->customers_model->getCustomers(['Customers.OrgUID' => $orgUID]);
            if (empty($customers)) throw new Exception('No customers found.');

            // Build the HSET map — DEL old key first to clear stale entries (handles migration from old STRING format)
            $cacheKey = $this->redisservice->orgKey('customers');
            $this->upstashservice->del($cacheKey);
            $newMap   = [];

            foreach ($customers as $cust) {
                $uid = (int)$cust->CustomerUID;

                // Fetch address
                $addrInfo     = $this->customers_model->getCustomerAddress(['CustAddress.CustomerUID' => $uid]);
                $addressList  = [];
                foreach ($addrInfo as $addr) {
                    $addressList[] = [
                        'AddressType' => $addr->AddressType,
                        'Line1'       => $addr->Line1       ?? '',
                        'Line2'       => $addr->Line2       ?? '',
                        'Pincode'     => $addr->Pincode     ?? '',
                        'CityText'    => $addr->CityText    ?? '',
                        'StateText'   => $addr->StateText   ?? '',
                    ];
                }

                // Closing balance = outstanding after all invoices & payments
                // (same logic as Cachehelper::upsertCustomer — PendingBalance is computed by the ledger query)
                $obRow          = $this->customers_model->getCustomerOpeningBalance($orgUID, $uid);
                $closingBalance = $obRow ? (float)($obRow->PendingBalance ?? $obRow->OpeningBalance) : 0.0;
                $closingBalType = $obRow ? ($obRow->PendingBalType        ?? $obRow->OpeningBalType)  : 'Debit';

                // On-account = unapplied credits from cancelled invoices
                $onAccountRows    = $this->customers_model->getCustomerOnAccountPayments($orgUID, $uid);
                $onAccountBalance = round(array_sum(array_column($onAccountRows, 'Amount')), 2);
                $onAccountRecords = array_map(function ($r) {
                    return [
                        'PaymentUID'          => (int)$r['PaymentUID'],
                        'Amount'              => (float)$r['Amount'],
                        'CreatedOn'           => $r['CreatedOn']           ?? '',
                        'SourceInvoiceNumber' => $r['SourceInvoiceNumber'] ?? '—',
                    ];
                }, $onAccountRows);

                // Build customer entry — identical shape to Cachehelper::upsertCustomer
                $newMap[(string)$uid] = [
                    'CustomerUID'      => $uid,
                    'Name'             => $cust->Name            ?? '',
                    'CompanyName'      => $cust->CompanyName     ?? '',
                    'ContactPerson'    => $cust->ContactPerson   ?? '',
                    'MobileNumber'     => $cust->MobileNumber    ?? '',
                    'CountryCode'      => $cust->CountryCode     ?? '',
                    'CountryISO2'      => $cust->CountryISO2     ?? '',
                    'EmailAddress'     => $cust->EmailAddress    ?? '',
                    'CCEmails'         => $cust->CCEmails        ?? '',
                    'GSTIN'            => $cust->GSTIN           ?? '',
                    'PANNumber'        => $cust->PANNumber       ?? '',
                    'CustomerTypeUID'  => (int)($cust->CustomerTypeUID  ?? 0),
                    'DiscountPercent'  => (float)($cust->DiscountPercent ?? 0),
                    'CreditPeriod'     => (int)($cust->CreditPeriod     ?? 0),
                    'CreditLimit'      => (float)($cust->CreditLimit    ?? 0),
                    'ClosingBalance'   => $closingBalance,
                    'ClosingBalType'   => $closingBalType,
                    'OnAccountBalance' => $onAccountBalance,
                    'OnAccountRecords' => $onAccountRecords,
                    'Area'             => $cust->Area   ?? '',
                    'Tags'             => $cust->Tags   ?? '',
                    'Notes'            => $cust->Notes  ?? '',
                    'Image'            => $cust->Image  ?? '',
                    'Address'          => $addressList,
                    'LastTransactionAt' => date('c'),
                ];
            }

            // Store as HSET — one bulk command, one field per customer
            $this->upstashservice->hmset($cacheKey, $newMap);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = count($customers) . ' customer(s) synced to cache.';
            $this->EndReturnData->Count   = count($customers);

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function getCustomerForModal($uid = 0) {
        $this->EndReturnData = new stdClass();
        try {
            $uid = (int) $uid;
            if ($uid <= 0) throw new Exception('Invalid customer ID.');

            // Cache-Aside READ
            $cacheKey = Upstashservice::keyCustomer($uid);
            $cached   = $this->upstashservice->get($cacheKey);

            if ($cached !== null) {
                $this->EndReturnData->Error        = FALSE;
                $this->EndReturnData->Data         = (object)$cached['Data'];
                $this->EndReturnData->BankDetails  = $cached['BankDetails'] ?? [];
                $this->EndReturnData->BillingAddr  = isset($cached['BillingAddr'])  ? (object)$cached['BillingAddr']  : null;
                $this->EndReturnData->ShippingAddr = isset($cached['ShippingAddr']) ? (object)$cached['ShippingAddr'] : null;
            } else {
                $this->load->model('customers_model');
                $getCustData = $this->customers_model->getCustomers(['Customers.CustomerUID' => $uid]);
                if (empty($getCustData)) throw new Exception('Customer not found.');

                $bankDetails = $this->customers_model->getCustomerBankInfo(['CustBankDetails.CustomerUID' => $uid]);
                $addrInfo    = $this->customers_model->getCustomerAddress(['CustAddress.CustomerUID' => $uid]);

                $billingAddr = null; $shippingAddr = null;
                foreach ($addrInfo as $addr) {
                    if ($addr->AddressType === 'Billing')  $billingAddr  = $addr;
                    if ($addr->AddressType === 'Shipping') $shippingAddr = $addr;
                }

                $this->EndReturnData->Error        = FALSE;
                $this->EndReturnData->Data         = $getCustData[0];
                $this->EndReturnData->BankDetails  = $bankDetails;
                $this->EndReturnData->BillingAddr  = $billingAddr;
                $this->EndReturnData->ShippingAddr = $shippingAddr;

                $this->upstashservice->set($cacheKey, [
                    'Data'         => $getCustData[0],
                    'BankDetails'  => $bankDetails,
                    'BillingAddr'  => $billingAddr,
                    'ShippingAddr' => $shippingAddr,
                ], Upstashservice::TTL_CUSTOMER);
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function updateCustomerData() {
        $this->EndReturnData = new stdClass();
        $ErrorInForm = '';
        try {

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData = $this->input->post();
            $this->load->model('formvalidation_model');
            $ErrorInForm = $this->formvalidation_model->custValidateForm($PostData);
            if (!empty($ErrorInForm)) throw new InvalidArgumentException('VALIDATION_ERROR');

            $CustomerUID      = getPostValue($PostData, 'CustomerUID');
            $customerFormData = $this->buildCustomerFormData($PostData, false);
            if (!empty($PostData['ImageRemoved'])) $customerFormData['Image'] = NULL;

            // Capture old DebitCredit values BEFORE the row is overwritten
            $this->load->model('customers_model');
            $oldDCRow    = $this->customers_model->getCustomerDebitCreditRaw((int)$CustomerUID);
            $oldDCAmount = $oldDCRow ? (float)($oldDCRow->DebitCreditAmount ?? 0) : 0.0;
            $oldDCType   = $oldDCRow ? ($oldDCRow->DebitCreditType ?? 'Debit') : 'Debit';

            // Compute delta once — used to gate both the ledger update and the opening balance update
            $newAmt  = (float) getPostValue($PostData, 'DebitCreditAmount', '', 0);
            $newType = getPostValue($PostData, 'DebitCreditCheck', '', 'Debit');
            $newName = getPostValue($PostData, 'Name');
            $oldSgn  = ($oldDCType === 'Debit') ?  $oldDCAmount : -$oldDCAmount;
            $newSgn  = ($newType   === 'Debit') ?  $newAmt      : -$newAmt;
            $delta   = round($newSgn - $oldSgn, 2);

            $UpdateDataResp = $this->dbwrite_model->updateData('Customers', 'CustomerTbl', $customerFormData, ['CustomerUID' => $CustomerUID]);
            if ($UpdateDataResp->Error) throw new Exception($UpdateDataResp->Message);

            if (isset($_FILES['UploadImage'])) {
                $UploadResp = $this->globalservice->fileUploadService($_FILES['UploadImage'], 'customers/images/', 'Image', ['Customers', 'CustomerTbl', ['CustomerUID' => $CustomerUID]]);
                if ($UploadResp->Error) throw new Exception($UploadResp->Message);
            }

            $delBnkFlag = getPostValue($PostData, 'delBankDataFlag');
            if ($delBnkFlag == 1) {
                $this->globalservice->softDeleteBankRecords(getPostValue($PostData, 'delBankData'), 'Customers', 'CustBankDetailsTbl', 'CustBankDetUID');
            }
            $this->globalservice->saveBankDetails($CustomerUID, $this->input->post('BankDetailsJSON'), 'Customers', 'CustBankDetailsTbl', [], 'CustBankDetUID');

            $delAddrFlag = getPostValue($PostData, 'delAddrDetailFlag');
            if ($delAddrFlag == 1) {
                $this->globalservice->softDeleteAddressRecords(getPostValue($PostData, 'delAddrData'), 'Customers', 'CustAddressTbl', 'CustAddressUID');
            }
            foreach ([['Bill', 'Billing'], ['Ship', 'Shipping']] as [$prefix, $type]) {
                $this->globalservice->saveAddressInfo($PostData, $CustomerUID, $prefix, $type, 'Customers', 'CustAddressTbl', 'CustAddressUID', 'CustomerUID');
            }

            // Only call ledger update when name or balance actually changed
            $nameChanged    = ($newName !== ($oldDCRow->Name ?? $newName));
            $balanceChanged = ($delta != 0.0);
            if ($nameChanged || $balanceChanged) {
                $this->load->library('accountledger');
                $this->accountledger->updateEntityLedgerInfo(
                    $CustomerUID,
                    [
                        'DebitCreditAmount' => $newAmt,
                        'DebitCreditCheck'  => $newType,
                        'Name'              => $newName,
                    ],
                    'Customer'
                );
            }

            if ($balanceChanged) {
                $orgUID  = $this->pageData['JwtData']->Org->OrgUID;
                $userUID = $this->pageData['JwtData']->User->UserUID;

                // Read current balance via ReadDb (no lock conflict)
                $obRow         = $this->customers_model->getCustomerOpeningBalance($orgUID, (int)$CustomerUID);
                $currentSigned = 0.0;
                if ($obRow) {
                    $currentSigned = ($obRow->OpeningBalType === 'Debit')
                        ? (float)$obRow->OpeningBalance : -(float)$obRow->OpeningBalance;
                }
                $newSigned  = round($currentSigned + $delta, 2);
                $newBalance = abs($newSigned);
                $newType    = ($newSigned >= 0) ? 'Debit' : 'Credit';

                // Write via dbwrite_model (C1 — same connection as this transaction, no FK deadlock)
                if ($obRow) {
                    $this->dbwrite_model->updateData('Customers', 'CustOpeningBalanceTbl', [
                        'OpeningBalance' => $newBalance,
                        'OpeningBalType' => $newType,
                        'UpdatedBy'      => (int)$userUID,
                    ], ['OpeningBalUID' => (int)$obRow->OpeningBalUID]);
                } else {
                    $this->dbwrite_model->insertData('Customers', 'CustOpeningBalanceTbl', [
                        'OrgUID'         => (int)$orgUID,
                        'CustomerUID'    => (int)$CustomerUID,
                        'OpeningBalance' => $newBalance,
                        'OpeningBalType' => $newType,
                        'PendingBalance' => $newBalance,
                        'PendingBalType' => $newType,
                        'IsActive'       => 1,
                        'IsDeleted'      => 0,
                        'CreatedBy'      => (int)$userUID,
                        'UpdatedBy'      => (int)$userUID,
                    ]);
                }

                // Update year-opening snapshot for current financial year
                $yrRow = $this->customers_model->getCustomerYearOpening($orgUID, (int)$CustomerUID, $this->_currentFinancialYear());
                if ($yrRow) {
                    $this->dbwrite_model->updateData('Customers', 'CustYearOpeningBalanceTbl', [
                        'OpeningBalance' => $newBalance,
                        'OpeningBalType' => $newType,
                        'UpdatedBy'      => (int)$userUID,
                    ], ['OrgUID' => (int)$orgUID, 'CustomerUID' => (int)$CustomerUID, 'FinancialYear' => (int)$this->_currentFinancialYear()]);
                } else {
                    $this->dbwrite_model->insertData('Customers', 'CustYearOpeningBalanceTbl', [
                        'OrgUID'         => (int)$orgUID,
                        'CustomerUID'    => (int)$CustomerUID,
                        'FinancialYear'  => (int)$this->_currentFinancialYear(),
                        'OpeningBalance' => $newBalance,
                        'OpeningBalType' => $newType,
                        'IsActive'       => 1,
                        'IsDeleted'      => 0,
                        'CreatedBy'      => (int)$userUID,
                        'UpdatedBy'      => (int)$userUID,
                    ]);
                }

                // Recalculate closing balance and sync all tables + cache
                $this->load->library('customerbalance');
                $this->customerbalance->recalcAndSync($orgUID, (int)$CustomerUID, $userUID);
            }

            // Refresh customer in bulk search cache with live data
            $this->cachehelper->upsertCustomer((int)$CustomerUID);

            $this->dbwrite_model->commitTransaction();

            $pageNo = (int) ($this->input->post('PageNo') ?: 1);
            $this->_initModule();
            $pageData = $this->_fetchTableData($pageNo, $this->pageData['Limit']);
            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Message    = 'Updated Successfully';
            $this->EndReturnData->List       = $pageData->RecordHtmlData;
            $this->EndReturnData->Pagination = $pageData->Pagination;
            $this->EndReturnData->Stats      = $this->customers_model->getCustomerStats($this->pageData['JwtData']->Org->OrgUID);

        } catch (InvalidArgumentException $e) {
            $this->dbwrite_model->rollbackTransaction();
            if ($e->getMessage() === 'VALIDATION_ERROR') {
                $this->EndReturnData->Error   = true;
                $this->EndReturnData->Message = strip_tags($ErrorInForm);
                $this->EndReturnData->Errors  = 'Please correct the highlighted errors.';
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

    public function deleteCustomerData() {
        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $CustomerUID = (int) $this->input->post('CustomerUID');
            if (!$CustomerUID) throw new Exception('Customer Information is Missing to Delete');

            $this->load->model('accountledger_model');
            $customer = $this->accountledger_model->getEntityWithLedger($CustomerUID, 'Customer');
            if (!$customer)             throw new Exception('Customer not found');
            if ($customer->IsDeleted == 1) throw new Exception('Customer already deleted');
            if ($this->customerHasTransactions($CustomerUID)) throw new Exception('Customer has existing transactions (Invoices/Payments/Orders)');

            $UpdateResp = $this->dbwrite_model->updateData('Customers', 'CustomerTbl', $this->globalservice->baseDeleteArrayDetails(), ['CustomerUID' => $CustomerUID]);
            if ($UpdateResp->Error) throw new Exception($UpdateResp->Message);

            if ($customer->LedgerUID) {
                $this->load->library('accountledger');
                $this->accountledger->deactivateEntityLedger($CustomerUID, $customer->LedgerUID, 'Customer');
            }
            // Remove deleted customer from bulk search cache
            $this->cachehelper->removeCustomer($CustomerUID);

            $this->dbwrite_model->commitTransaction();

            $pageNo   = (int) ($this->input->post('PageNo') ?: 1);
            $this->_initModule();
            $pageData = $this->_fetchTableData($pageNo, $this->pageData['Limit']);

            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Message    = 'Deleted Successfully';
            $this->EndReturnData->List       = $pageData->RecordHtmlData;
            $this->EndReturnData->Pagination = $pageData->Pagination;
            $this->EndReturnData->Stats      = $this->customers_model->getCustomerStats($this->pageData['JwtData']->Org->OrgUID);

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function exportCustomers() {
        try {
            $type   = $this->input->get('Type')   ?: 'CSV';
            $filter = $this->input->get('Filter') ?: '{}';
            $filter = json_decode($filter, true)  ?: [];

            $orgUID = (int)$this->pageData['JwtData']->Org->OrgUID;
            $this->load->model('organisation_model');
            $orgResult = $this->organisation_model->getOrgInfoCached($orgUID);
            $orgInfo   = ($orgResult->Error === FALSE) ? $orgResult->Data : null;

            $this->load->model('customers_model');
            $result = $this->customers_model->getCustomerListPaginated($orgUID, 0, 0, $filter);
            $data   = $result->rows;

            $headers = ['#', 'Customer Name', 'Area', 'Mobile', 'Email', 'GSTIN', 'Company Name', 'Customer Type', 'Balance', 'Balance Type', 'Status', 'Last Updated', 'Updated By'];
            $rows    = [];
            foreach ($data as $i => $row) {
                $rows[] = [
                    $i + 1,
                    $row->Name             ?? '',
                    $row->Area             ?? '',
                    $row->MobileNumber     ?? '',
                    $row->EmailAddress     ?? '',
                    $row->GSTIN            ?? '',
                    $row->CompanyName      ?? '',
                    $row->CustomerTypeName ?? '',
                    number_format((float)($row->ClosingBalance ?? 0), 2),
                    $row->ClosingBalanceType ?? '',
                    ((int)($row->IsActive ?? 1)) === 1 ? 'Active' : 'Inactive',
                    !empty($row->UpdatedOn) ? date($this->pageData['JwtData']->GenSettings->ListDateTimeFormat ?? 'd M Y', strtotime($row->UpdatedOn)) : '',
                    $row->UpdatedBy ?? '',
                ];
            }

            $timezone  = $this->pageData['JwtData']->User->Timezone ?? 'UTC';
            $colWidths = ['3%','14%','9%','9%','12%','9%','9%','8%','7%','6%','5%','9%','10%'];
            $this->_sendExport($type, 'Customer_Data', 'Customers', 'Customer Report', $headers, $rows, $orgInfo, $timezone, $colWidths);

        } catch (Exception $e) {
            echo json_encode(['Error' => true, 'Message' => $e->getMessage()]);
        }
    }

    public function getCustomerTags() {
        $this->EndReturnData = new stdClass();
        try {
            $this->load->model('customers_model');
            $tags = $this->customers_model->getCustomerTags($this->pageData['JwtData']->Org->OrgUID);
            $this->EndReturnData->Error = false;
            $this->EndReturnData->Tags  = $tags;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function getCustomerTypesList() {
        $this->EndReturnData = new stdClass();
        try {
            $this->load->model('customers_model');
            $types = $this->customers_model->getCustomerTypeList($this->pageData['JwtData']->Org->OrgUID);
            $this->EndReturnData->Error = false;
            $this->EndReturnData->Types = $types;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }


    public function getStats() {
        $this->EndReturnData = new stdClass();
        try {
            $this->load->model('customers_model');
            $stats = $this->customers_model->getCustomerStats($this->pageData['JwtData']->Org->OrgUID);
            $this->EndReturnData->Error = false;
            $this->EndReturnData->Stats = $stats;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }
    public function toggleCustomerStatus() {
        $this->EndReturnData = new stdClass();
        try {
            $CustomerUID = (int) $this->input->post('CustomerUID');
            $newStatus   = (int) $this->input->post('IsActive');
            if (!$CustomerUID) throw new Exception('Customer ID is missing');
            if (!in_array($newStatus, [0, 1])) throw new Exception('Invalid status value');

            $this->load->model('dbwrite_model');
            $resp = $this->dbwrite_model->updateData(
                'Customers', 'CustomerTbl',
                ['IsActive' => $newStatus, 'UpdatedBy' => $this->pageData['JwtData']->User->UserUID],
                ['CustomerUID' => $CustomerUID]
            );
            if ($resp->Error) throw new Exception($resp->Message);
            $this->cachehelper->upsertCustomer($CustomerUID);

            $pageNo = (int) ($this->input->post('PageNo') ?: 1);
            $this->_initModule();
            $pageData = $this->_fetchTableData($pageNo, $this->pageData['Limit']);
            $this->EndReturnData->Error      = false;
            $this->EndReturnData->Message    = 'Status updated successfully';
            $this->EndReturnData->Stats      = $this->customers_model->getCustomerStats($this->pageData['JwtData']->Org->OrgUID);
            $this->EndReturnData->List       = $pageData->RecordHtmlData;
            $this->EndReturnData->Pagination = $pageData->Pagination;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    private function _currentFinancialYear() {
        return (date('n') >= 4) ? (int)date('Y') : (int)date('Y') - 1;
    }

    private function customerHasTransactions($customerId) {
        try {
            $this->load->model('transactions_model');
            return count($this->transactions_model->getEntityInvoices($customerId, 'Customer')) > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    public function deleteBulkCustomers() {
        $this->EndReturnData = new stdClass();
        try {

            $CustomerUIDs = $this->input->post('CustomerUIDs[]');
            if (empty($CustomerUIDs)) throw new Exception('Customer Information is Missing to Delete');

            if (!is_array($CustomerUIDs)) $CustomerUIDs = [$CustomerUIDs];
            $CustomerUIDs = array_filter(array_map('intval', $CustomerUIDs));
            if (empty($CustomerUIDs)) throw new Exception('Invalid customer IDs provided');

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            foreach ($CustomerUIDs as $customerId) {
                if ($this->customerHasTransactions($customerId)) {
                    throw new Exception("Customer ID {$customerId} has existing transactions (Invoices/Payments/Orders)");
                }

                $this->load->model('accountledger_model');
                $customer = $this->accountledger_model->getEntityWithLedger($customerId, 'Customer');
                if (!$customer)              throw new Exception("Customer ID {$customerId} not found");
                if ($customer->IsDeleted == 1) throw new Exception("Customer ID {$customerId} is already deleted");

                if ($customer->LedgerUID) {
                    $this->load->library('accountledger');
                    $this->accountledger->deactivateEntityLedger($customerId, $customer->LedgerUID, 'Customer');
                }
            }

            $UpdateResp = $this->dbwrite_model->updateData('Customers', 'CustomerTbl', $this->globalservice->baseDeleteArrayDetails(), [], ['CustomerUID' => $CustomerUIDs]);
            if ($UpdateResp->Error) throw new Exception($UpdateResp->Message);

            $this->dbwrite_model->commitTransaction();

            // Remove each deleted customer from bulk search cache
            foreach ($CustomerUIDs as $cid) {
                $this->cachehelper->removeCustomer($cid);
            }

            $pageNo   = (int) ($this->input->post('PageNo') ?: 1);
            $this->_initModule();
            $pageData = $this->_fetchTableData($pageNo, $this->pageData['Limit']);

            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Message    = count($CustomerUIDs) . ' customer(s) deleted successfully';
            $this->EndReturnData->List       = $pageData->RecordHtmlData;
            $this->EndReturnData->Pagination = $pageData->Pagination;
            $this->EndReturnData->Stats      = $this->customers_model->getCustomerStats($this->pageData['JwtData']->Org->OrgUID);

        } catch (Exception $e) {
            if (isset($this->dbwrite_model)) $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Send SMS / Email ─────────────────────────────────────────────────────
    public function sendCommunication() {

        $this->EndReturnData = new stdClass();
        $tempFiles = [];
        try {

            $orgUID    = $this->pageData['JwtData']->Org->OrgUID;
            $sentBy    = $this->pageData['JwtData']->User->UserUID;
            $commType  = $this->input->post('CommType');
            $message   = trim($this->input->post('Message', FALSE));
            $subject   = trim($this->input->post('Subject', FALSE) ?: '');
            $uids      = $this->input->post('UIDs');
            $moduleUID = (int) $this->input->post('ModuleUID');
            $recordUID = (int) $this->input->post('RecordUID');

            if (!in_array($commType, ['SMS', 'Email'])) throw new Exception('Invalid communication type.');
            if (empty($message))                         throw new Exception('Message cannot be empty.');
            if ($commType === 'Email' && empty($subject)) throw new Exception('Email subject is required.');
            if (empty($uids) || !is_array($uids))        throw new Exception('No recipients selected.');

            $uids = array_map('intval', $uids);

            $uploadDir = FCPATH . 'uploads/comm_tmp/';

            // Save uploaded attachments to a temp dir
            if ($commType === 'Email' && !empty($_FILES['Attachments']['name'][0])) {
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $files = $_FILES['Attachments'];
                $count = is_array($files['name']) ? count($files['name']) : 0;
                for ($i = 0; $i < min($count, 3); $i++) {
                    if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
                    $ext      = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                    if (!in_array($ext, ['pdf','jpg','jpeg','png'])) continue;
                    $tmpPath  = $uploadDir . uniqid('attach_', true) . '.' . $ext;
                    if (move_uploaded_file($files['tmp_name'][$i], $tmpPath)) {
                        $tempFiles[] = $tmpPath;
                    }
                }
            }

            $this->load->library('communicationservice');

            if ($commType === 'SMS') {
                $result = $this->communicationservice->sendSMS($orgUID, $sentBy, 'Customer', $uids, $message);
            } else {
                $result = $this->communicationservice->sendEmail($orgUID, $sentBy, 'Customer', $uids, $subject, $message, $tempFiles);
            }

            if ($result->Error) throw new Exception($result->Message);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = $result->Message;
            $this->EndReturnData->Sent    = $result->Sent   ?? 0;
            $this->EndReturnData->Failed  = $result->Failed ?? 0;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        foreach ($tempFiles as $f) { if (is_file($f)) unlink($f); }
        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function saveCustomerOpeningBalance() {

        $this->EndReturnData = new stdClass();
        try {

            $orgUID        = $this->pageData['JwtData']->Org->OrgUID;
            $userUID       = $this->pageData['JwtData']->User->UserUID;
            $customerUID   = (int)   $this->input->post('CustomerUID');
            $balance       = (float) $this->input->post('OpeningBalance');
            $balanceType   = trim($this->input->post('BalanceType'));
            $notes         = trim($this->input->post('Notes') ?? '');
            $financialYear = (int)   $this->input->post('FinancialYear');

            if ($customerUID <= 0)                              throw new Exception('Invalid customer.');
            if ($balance < 0)                                   throw new Exception('Opening balance cannot be negative.');
            if (!in_array($balanceType, ['Debit', 'Credit']))   throw new Exception('BalanceType must be Debit or Credit.');
            if ($financialYear <= 0) {
                $financialYear = (date('n') >= 4) ? (int)date('Y') : (int)date('Y') - 1;
            }

            $this->load->model('customers_model');
            $id = $this->customers_model->saveCustomerOpeningBalance(
                $orgUID, $customerUID, $balance, $balanceType, $notes, $userUID
            );
            $this->customers_model->saveCustomerYearOpening(
                $orgUID, $customerUID, $financialYear, $balance, $balanceType, $userUID
            );

            $this->cachehelper->upsertCustomer($customerUID);

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->Message        = 'Opening balance saved successfully.';
            $this->EndReturnData->OpeningBalUID  = $id;
            $this->EndReturnData->FinancialYear  = $financialYear;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function getCustomerOpeningBalance() {

        $this->EndReturnData = new stdClass();
        try {

            $orgUID        = $this->pageData['JwtData']->Org->OrgUID;
            $customerUID   = (int) $this->input->get_post('CustomerUID');
            $financialYear = (int) $this->input->get_post('FinancialYear');

            if ($customerUID <= 0) throw new Exception('Invalid customer.');
            if ($financialYear <= 0) {
                $financialYear = $this->_currentFinancialYear();
            }

            $this->load->model('customers_model');
            $current = $this->customers_model->getCustomerOpeningBalance($orgUID, $customerUID);
            $yearRow = $this->customers_model->getCustomerYearOpening($orgUID, $customerUID, $financialYear);

            $this->EndReturnData->Error         = FALSE;
            $this->EndReturnData->Data          = $current;
            $this->EndReturnData->YearData      = $yearRow;
            $this->EndReturnData->FinancialYear = $financialYear;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function updateCustomerBalance() {

        $this->EndReturnData = new stdClass();
        try {

            $orgUID     = $this->pageData['JwtData']->Org->OrgUID;
            $userUID    = $this->pageData['JwtData']->User->UserUID;
            $filterUID  = (int) $this->input->post('CustomerUID');

            $this->load->model('customers_model');

            // Step 1: Fetch all customers (or a single one) with their ledger info
            $customers = $this->customers_model->getCustomersWithLedgerForBalance($orgUID, $filterUID);

            if (empty($customers)) {
                throw new Exception('No customers found to update.');
            }

            $updated = 0;
            $skipped = 0;
            $errors  = [];

            foreach ($customers as $cust) {
                try {

                    $custUID = (int) $cust->CustomerUID;

                    // Step 2: Fetch transaction totals via model
                    $totalInvoiced = $this->customers_model->getCustomerTotalInvoiced($orgUID, $custUID);
                    $totalReceived = $this->customers_model->getCustomerTotalReceived($orgUID, $custUID);
                    $totalReturned = $this->customers_model->getCustomerTotalReturned($orgUID, $custUID);

                    // Step 3: Compute net balance
                    // Opening balance from CustOpeningBalanceTbl (one row per customer, no year)
                    $signedOpening = ($cust->OpeningBalType === 'Debit')
                        ? (float)$cust->OpeningBalance
                        : -(float)$cust->OpeningBalance;

                    // net = opening + invoiced - received - returned
                    $signedBalance  = round($signedOpening + $totalInvoiced - $totalReceived - $totalReturned, 2);
                    $newBalance     = abs($signedBalance);
                    $newBalanceType = ($signedBalance >= 0) ? 'Debit' : 'Credit';

                    // Step 4: Persist — update ledger current balance + CustOpeningBalanceTbl pending balance.
                    // DebitCreditAmount in CustomerTbl is the adjustment-delta field; do NOT overwrite it here.
                    if (!empty($cust->LedgerUID)) {
                        $this->customers_model->updateCustomerBalanceInLedger(
                            $cust->LedgerUID, $newBalance, $newBalanceType, $userUID
                        );
                    }

                    $this->customers_model->updateCustomerPendingBalance(
                        $orgUID, $custUID, $newBalance, $newBalanceType, $userUID
                    );

                    $updated++;

                } catch (Exception $innerEx) {
                    $errors[] = 'CustomerUID ' . $cust->CustomerUID . ': ' . $innerEx->getMessage();
                    $skipped++;
                }
            }

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = "Balance recalculated: {$updated} updated, {$skipped} skipped.";
            $this->EndReturnData->Updated = $updated;
            $this->EndReturnData->Skipped = $skipped;
            if (!empty($errors)) {
                $this->EndReturnData->Errors = $errors;
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function getCustomerBalance() {

        $this->EndReturnData = new stdClass();
        try {

            $orgUID      = $this->pageData['JwtData']->Org->OrgUID;
            $customerUID = (int) $this->input->get_post('CustomerUID');
            if ($customerUID <= 0) throw new Exception('Invalid customer.');

            $this->load->model('customers_model');

            $custRows = $this->customers_model->getCustomersWithLedgerForBalance($orgUID, $customerUID);
            if (empty($custRows)) throw new Exception('Customer not found.');

            $cust          = $custRows[0];
            $totalInvoiced = $this->customers_model->getCustomerTotalInvoiced($orgUID, $customerUID);
            $totalReceived = $this->customers_model->getCustomerTotalReceived($orgUID, $customerUID);
            $totalReturned = $this->customers_model->getCustomerTotalReturned($orgUID, $customerUID);

            $signedOpening  = ($cust->OpeningBalType === 'Debit')
                ? (float)$cust->OpeningBalance
                : -(float)$cust->OpeningBalance;
            $signedBalance  = round($signedOpening + $totalInvoiced - $totalReceived - $totalReturned, 2);
            $balance        = abs($signedBalance);
            $balanceType    = ($signedBalance >= 0) ? 'Debit' : 'Credit';

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->CustomerUID    = $customerUID;
            $this->EndReturnData->Balance        = $balance;
            $this->EndReturnData->BalanceType    = $balanceType;
            $this->EndReturnData->Breakdown      = [
                'OpeningBalance' => (float)$cust->OpeningBalance,
                'OpeningBalType' => $cust->OpeningBalType,
                'TotalInvoiced'  => $totalInvoiced,
                'TotalReceived'  => $totalReceived,
                'TotalReturned'  => $totalReturned,
            ];

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function getCustomerSearchList() {

        $this->EndReturnData = new stdClass();
        try {

            $pageNo = (int) $this->input->post('PageNo') ?: 1;
            $limit  = (int) $this->input->post('RowLimit') ?: 20;
            $search = trim($this->input->post('Search') ?? '');

            $orgUID = $this->pageData['JwtData']->Org->OrgUID;
            $offset = max(0, ($pageNo - 1) * $limit);

            $filter = [];
            if (!empty($search)) {
                $filter['SearchAllData'] = $search;
            }

            $this->load->model('customers_model');
            $result = $this->customers_model->getCustomerListPaginated($orgUID, $limit, $offset, $filter);

            $this->EndReturnData->Error      = false;
            $this->EndReturnData->Customers   = array_map(function($row) {
                $cust = [
                    'CustomerUID'  => $row->CustomerUID,
                    'Name'         => $row->Name,
                    'Area'         => $row->Area ?? '',
                    'MobileNumber' => $row->MobileNumber ?? '',
                    'Balance'      => $row->ClosingBalance ?? 0,
                    'BalanceType'  => $row->ClosingBalanceType ?? 'Debit',
                    'CountryISO2'  => $row->CountryISO2 ?? 'IN',
                ];
                if (!empty($row->ShipLine1) || !empty($row->ShipCity) || !empty($row->ShipState)) {
                    $cust['address'] = [
                        'Line1'   => $row->ShipLine1   ?? '',
                        'Line2'   => $row->ShipLine2   ?? '',
                        'City'    => $row->ShipCity    ?? '',
                        'State'   => $row->ShipState   ?? '',
                        'Pincode' => $row->ShipPincode ?? '',
                    ];
                }
                return $cust;
            }, $result->rows ?? []);
            $this->EndReturnData->TotalCount  = $result->totalCount ?? 0;
            $this->EndReturnData->Pagination  = $this->globalservice->buildPagePaginationHtml(
                '/customers/getCustomerSearchList',
                $result->totalCount ?? 0,
                $pageNo,
                $limit
            );

        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    // Returns total On Account balance + individual payment rows for a customer
    public function getCustomerOnAccountBalance() {

        $this->EndReturnData = new stdClass();
        try {
            $orgUID      = $this->pageData['JwtData']->Org->OrgUID;
            $customerUID = (int) $this->input->post('CustomerUID');
            if ($customerUID <= 0) throw new Exception('CustomerUID is required.');

            $this->load->model('customers_model');
            $result = $this->customers_model->getCustomerOnAccountPayments($orgUID, $customerUID);

            $total = array_sum(array_column($result, 'Amount'));

            $this->EndReturnData->Error    = false;
            $this->EndReturnData->Total    = round((float)$total, 2);
            $this->EndReturnData->Payments = $result;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    // Applies a specific On Account payment to the given invoice
    public function applyOnAccountPayment() {

        $this->EndReturnData = new stdClass();
        try {
            $orgUID      = $this->pageData['JwtData']->Org->OrgUID;
            $userUID     = $this->pageData['JwtData']->User->UserUID;
            $paymentUID  = (int) $this->input->post('PaymentUID');
            $transUID    = (int) $this->input->post('TransUID');
            if ($paymentUID <= 0 || $transUID <= 0) throw new Exception('PaymentUID and TransUID are required.');

            $this->load->model('dbwrite_model');
            $this->load->model('transactions_model');

            $payment = $this->dbwrite_model->getOnAccountPayment($paymentUID, $orgUID);
            if (!$payment) throw new Exception('On Account payment not found or already applied.');

            $this->dbwrite_model->startTransaction();

            $this->dbwrite_model->applyOnAccountPayment($paymentUID, $orgUID, $transUID, $userUID);

            // Update invoice paid/balance
            $existingPaid = $this->transactions_model->getSumPaidForTransaction($transUID, $orgUID);
            $trans        = $this->transactions_model->getTransactionBasicInfo($transUID, $orgUID);
            if ($trans) {
                $netAmount     = (float) $trans->NetAmount;
                $newPaid       = round($existingPaid, 2);
                $balanceAmount = max(0, round($netAmount - $newPaid, 2));
                $isFullyPaid   = ($netAmount > 0 && $balanceAmount <= 0) ? 1 : 0;
                $newStatus     = $isFullyPaid ? 'Paid' : ($newPaid > 0 ? 'Partial' : 'Issued');

                $this->dbwrite_model->updateTransIsFullyPaid($transUID, $isFullyPaid, $newPaid, $balanceAmount, $userUID);
                $this->dbwrite_model->updateTransDocStatus($transUID, $orgUID, $newStatus, $userUID);
            }

            $this->dbwrite_model->commitTransaction();

            // Recalculate customer closing balance
            $this->load->library('customerbalance');
            $balResult = $this->customerbalance->recalcAndSync($orgUID, (int)$payment->PartyUID, $userUID);

            $this->EndReturnData->Error   = false;
            $this->EndReturnData->Message = 'On Account payment applied successfully.';
            if ($balResult) {
                $this->EndReturnData->CustomerBalance     = $balResult['balance'];
                $this->EndReturnData->CustomerBalanceType = $balResult['type'];
            }

        } catch (Exception $e) {
            if (isset($this->dbwrite_model)) $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    // ══════════════════════════════════════════════════════════════════
    // Customer Group methods
    // ══════════════════════════════════════════════════════════════════

    private function _groupTypesList() {
        $this->load->model('customers_model');
        return $this->customers_model->getGroupTypes('customers');
    }

    public function getGroupTypes() {
        $this->EndReturnData = new stdClass();
        try {
            $this->EndReturnData->Error = false;
            $this->EndReturnData->Data  = $this->_groupTypesList();
        } catch (Exception $e) {
            $this->EndReturnData->Error = true;
            $this->EndReturnData->Data  = [];
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    private function _fetchGroupsTableData($pageNo, $limit, $filter = []) {
        $orgUID  = $this->pageData['JwtData']->Org->OrgUID;
        $offset  = max(0, ($pageNo - 1) * $limit);
        $this->load->model('customers_model');
        $result  = $this->customers_model->getGroupListPaginated($orgUID, $limit, $offset, $filter);
        $rowHtml = $this->load->view('customers/groups/list', [
            'DataLists'    => $result->rows,
            'SerialNumber' => $offset,
            'JwtData'      => $this->pageData['JwtData'],
        ], true);
        $resp                 = new stdClass();
        $resp->RecordHtmlData = $rowHtml;
        $resp->Pagination     = $this->globalservice->buildPagePaginationHtml('/customers/getGroupsData', $result->totalCount, $pageNo, $limit);
        $resp->TotalCount     = $result->totalCount;
        return $resp;
    }

    public function getGroupsData($pageNo = 0) {
        $this->EndReturnData = new stdClass();
        try {
            $pageNo   = max(1, (int) $pageNo);
            $filter   = $this->input->post('Filter') ?: [];
            $this->_initModule();
            $limit    = (int)($this->input->post('RowLimit') ?: $this->pageData['Limit']);
            $pageData = $this->_fetchGroupsTableData($pageNo, $limit, $filter);
            $this->load->model('customers_model');
            $this->EndReturnData->Error          = false;
            $this->EndReturnData->RecordHtmlData = $pageData->RecordHtmlData;
            $this->EndReturnData->Pagination     = $pageData->Pagination;
            $this->EndReturnData->TotalCount     = $pageData->TotalCount;
            $this->EndReturnData->Stats          = $this->customers_model->getGroupStats($this->pageData['JwtData']->Org->OrgUID);
        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function getGroupForModal($groupUID = 0) {
        $this->EndReturnData = new stdClass();
        try {
            $groupUID = (int)$groupUID;
            if (!$groupUID) throw new Exception('Group ID is missing.');
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;
            $this->load->model('customers_model');
            $group   = $this->customers_model->getGroupByUID($orgUID, $groupUID);
            if (!$group) throw new Exception('Group not found.');
            $members = $this->customers_model->getGroupMembers($orgUID, $groupUID);
            $this->EndReturnData->Error      = false;
            $this->EndReturnData->Data       = $group;
            $this->EndReturnData->Members    = $members;
            $this->EndReturnData->GroupTypes = $this->_groupTypesList();
        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function addGroup() {
        if (!$this->_loadPageTitle()) { redirect('customers'); return; }
        $this->pageData['FormMode']   = 'add';
        $this->pageData['FormData']   = null;
        $this->pageData['Members']    = [];
        $this->pageData['GroupTypes'] = $this->_groupTypesList();
        $this->load->view('customers/groups/form', $this->pageData);
    }

    public function addGroupData() {
        $this->EndReturnData = new stdClass();
        try {
            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();
            $post      = $this->input->post();
            $orgUID    = $this->pageData['JwtData']->Org->OrgUID;
            $userUID   = $this->pageData['JwtData']->User->UserUID;
            $groupName = trim($post['GroupName'] ?? '');
            if (!$groupName) throw new InvalidArgumentException('Group Name is required.');
            $validTypes = $this->_groupTypesList();
            $data = [
                'OrgUID'            => $orgUID,
                'GroupCode'         => trim($post['GroupCode']         ?? '') ?: null,
                'GroupName'         => $groupName,
                'GroupType'         => in_array($post['GroupType'] ?? '', $validTypes) ? $post['GroupType'] : 'Business Group',
                'ContactPerson'     => trim($post['ContactPerson']     ?? '') ?: null,
                'Mobile'            => trim($post['Mobile']            ?? '') ?: null,
                'MobileCountryCode' => trim($post['MobileCountryCode'] ?? '') ?: null,
                'Email'             => trim($post['Email']             ?? '') ?: null,
                'GSTNo'             => trim($post['GSTNo']             ?? '') ?: null,
                'AddrLine1'         => trim($post['AddrLine1']         ?? '') ?: null,
                'AddrLine2'         => trim($post['AddrLine2']         ?? '') ?: null,
                'AddrCity'          => trim($post['AddrCity']          ?? '') ?: null,
                'AddrState'         => trim($post['AddrState']         ?? '') ?: null,
                'AddrStateCode'     => trim($post['AddrStateCode']     ?? '') ?: null,
                'AddrPincode'       => trim($post['AddrPincode']       ?? '') ?: null,
                'Notes'             => trim($post['Notes']             ?? '') ?: null,
                'IsActive'          => 1,
                'CreatedBy'         => $userUID,
                'UpdatedBy'         => $userUID,
            ];
            $resp = $this->dbwrite_model->insertData('Customers', 'CustomerGroupTbl', $data);
            if ($resp->Error) throw new Exception($resp->Message);
            $groupUID   = $resp->ID;
            $memberUIDs = array_values(array_filter(array_map('intval', (array)($post['MemberUIDs'] ?? []))));
            $primaryUID = (int)($post['PrimaryUID'] ?? 0);
            if (!empty($memberUIDs)) {
                $this->load->model('customers_model');
                $this->customers_model->assignGroupMembers($orgUID, $groupUID, $memberUIDs, $primaryUID, $userUID);
            }
            $this->dbwrite_model->commitTransaction();
            $this->EndReturnData->Error    = false;
            $this->EndReturnData->Message  = 'Customer Group created successfully.';
            $this->EndReturnData->GroupUID = $groupUID;
            $limit    = isset($this->pageData['Limit']) ? (int)$this->pageData['Limit'] : 25;
            $pageData = $this->_fetchGroupsTableData(1, $limit);
            $this->load->model('customers_model');
            $this->EndReturnData->RecordHtmlData = $pageData->RecordHtmlData;
            $this->EndReturnData->Pagination     = $pageData->Pagination;
            $this->EndReturnData->TotalCount     = $pageData->TotalCount;
            $this->EndReturnData->Stats          = $this->customers_model->getGroupStats($orgUID);
        } catch (InvalidArgumentException $e) {
            if (isset($this->dbwrite_model)) $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        } catch (Exception $e) {
            if (isset($this->dbwrite_model)) $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function groupEdit($groupUID = 0) {
        $groupUID = (int) $groupUID;
        if (!$groupUID) { redirect('customers'); return; }
        if (!$this->_loadPageTitle()) { redirect('customers'); return; }
        try {
            $orgUID = $this->pageData['JwtData']->Org->OrgUID;
            $this->load->model('customers_model');
            $group = $this->customers_model->getGroupByUID($orgUID, $groupUID);
            if (!$group) { redirect('customers'); return; }
            $this->pageData['FormMode']   = 'edit';
            $this->pageData['FormData']   = $group;
            $this->pageData['Members']    = $this->customers_model->getGroupMembers($orgUID, $groupUID);
            $this->pageData['GroupTypes'] = $this->_groupTypesList();
            $this->load->view('customers/groups/form', $this->pageData);
        } catch (Exception $e) {
            redirect('customers');
        }
    }

    public function updateGroupData() {
        $this->EndReturnData = new stdClass();
        try {
            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();
            $post      = $this->input->post();
            $groupUID  = (int)($post['GroupUID'] ?? 0);
            $orgUID    = $this->pageData['JwtData']->Org->OrgUID;
            $userUID   = $this->pageData['JwtData']->User->UserUID;
            if (!$groupUID) throw new Exception('Group ID is missing.');
            $groupName = trim($post['GroupName'] ?? '');
            if (!$groupName) throw new InvalidArgumentException('Group Name is required.');
            $validTypes = $this->_groupTypesList();
            $data = [
                'GroupCode'         => trim($post['GroupCode']         ?? '') ?: null,
                'GroupName'         => $groupName,
                'GroupType'         => in_array($post['GroupType'] ?? '', $validTypes) ? $post['GroupType'] : 'Business Group',
                'ContactPerson'     => trim($post['ContactPerson']     ?? '') ?: null,
                'Mobile'            => trim($post['Mobile']            ?? '') ?: null,
                'MobileCountryCode' => trim($post['MobileCountryCode'] ?? '') ?: null,
                'Email'             => trim($post['Email']             ?? '') ?: null,
                'GSTNo'             => trim($post['GSTNo']             ?? '') ?: null,
                'AddrLine1'         => trim($post['AddrLine1']         ?? '') ?: null,
                'AddrLine2'         => trim($post['AddrLine2']         ?? '') ?: null,
                'AddrCity'          => trim($post['AddrCity']          ?? '') ?: null,
                'AddrState'         => trim($post['AddrState']         ?? '') ?: null,
                'AddrStateCode'     => trim($post['AddrStateCode']     ?? '') ?: null,
                'AddrPincode'       => trim($post['AddrPincode']       ?? '') ?: null,
                'Notes'             => trim($post['Notes']             ?? '') ?: null,
                'UpdatedBy'         => $userUID,
            ];
            $resp = $this->dbwrite_model->updateData('Customers', 'CustomerGroupTbl', $data, ['GroupUID' => $groupUID, 'OrgUID' => $orgUID]);
            if ($resp->Error) throw new Exception($resp->Message);
            $memberUIDs = array_values(array_filter(array_map('intval', (array)($post['MemberUIDs'] ?? []))));
            $primaryUID = (int)($post['PrimaryUID'] ?? 0);
            $this->load->model('customers_model');
            $this->customers_model->syncGroupMembers($orgUID, $groupUID, $memberUIDs, $primaryUID, $userUID);
            $this->dbwrite_model->commitTransaction();
            $this->EndReturnData->Error    = false;
            $this->EndReturnData->Message  = 'Customer Group updated successfully.';
            $this->EndReturnData->GroupUID = $groupUID;
            $limit    = isset($this->pageData['Limit']) ? (int)$this->pageData['Limit'] : 25;
            $pageData = $this->_fetchGroupsTableData(1, $limit);
            $this->EndReturnData->RecordHtmlData = $pageData->RecordHtmlData;
            $this->EndReturnData->Pagination     = $pageData->Pagination;
            $this->EndReturnData->TotalCount     = $pageData->TotalCount;
            $this->EndReturnData->Stats          = $this->customers_model->getGroupStats($orgUID);
        } catch (InvalidArgumentException $e) {
            if (isset($this->dbwrite_model)) $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        } catch (Exception $e) {
            if (isset($this->dbwrite_model)) $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function deleteGroup() {
        $this->EndReturnData = new stdClass();
        try {
            $groupUID = (int) $this->input->post('GroupUID');
            $pageNo   = (int)($this->input->post('PageNo') ?: 1);
            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;
            $userUID  = $this->pageData['JwtData']->User->UserUID;
            if (!$groupUID) throw new Exception('Group ID is missing.');
            $this->load->model('dbwrite_model');
            $this->load->model('customers_model');
            $this->dbwrite_model->startTransaction();
            $this->customers_model->unlinkAllGroupMembers($orgUID, $groupUID, $userUID);
            $resp = $this->dbwrite_model->updateData('Customers', 'CustomerGroupTbl',
                ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID],
                ['GroupUID' => $groupUID, 'OrgUID' => $orgUID]
            );
            if ($resp->Error) throw new Exception($resp->Message);
            $this->dbwrite_model->commitTransaction();
            $this->_initModule();
            $pageData = $this->_fetchGroupsTableData($pageNo, $this->pageData['Limit']);
            $this->EndReturnData->Error          = false;
            $this->EndReturnData->Message        = 'Group deleted successfully.';
            $this->EndReturnData->RecordHtmlData = $pageData->RecordHtmlData;
            $this->EndReturnData->Pagination     = $pageData->Pagination;
            $this->EndReturnData->Stats          = $this->customers_model->getGroupStats($orgUID);
        } catch (Exception $e) {
            if (isset($this->dbwrite_model)) $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function toggleGroupStatus() {
        $this->EndReturnData = new stdClass();
        try {
            $groupUID  = (int) $this->input->post('GroupUID');
            $newStatus = (int) $this->input->post('IsActive');
            $pageNo    = (int)($this->input->post('PageNo') ?: 1);
            $orgUID    = $this->pageData['JwtData']->Org->OrgUID;
            $userUID   = $this->pageData['JwtData']->User->UserUID;
            if (!$groupUID) throw new Exception('Group ID is missing.');
            if (!in_array($newStatus, [0, 1])) throw new Exception('Invalid status value.');
            $this->load->model('dbwrite_model');
            $resp = $this->dbwrite_model->updateData('Customers', 'CustomerGroupTbl',
                ['IsActive' => $newStatus, 'UpdatedBy' => $userUID],
                ['GroupUID' => $groupUID, 'OrgUID' => $orgUID]
            );
            if ($resp->Error) throw new Exception($resp->Message);
            $this->_initModule();
            $pageData = $this->_fetchGroupsTableData($pageNo, $this->pageData['Limit']);
            $this->load->model('customers_model');
            $this->EndReturnData->Error          = false;
            $this->EndReturnData->Message        = 'Status updated successfully.';
            $this->EndReturnData->RecordHtmlData = $pageData->RecordHtmlData;
            $this->EndReturnData->Pagination     = $pageData->Pagination;
            $this->EndReturnData->Stats          = $this->customers_model->getGroupStats($orgUID);
        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function groupDetail($groupUID = 0) {
        $groupUID = (int) $groupUID;
        if (!$groupUID) { redirect('customers'); return; }
        if (!$this->_loadPageTitle()) { redirect('customers'); return; }
        try {
            $orgUID = $this->pageData['JwtData']->Org->OrgUID;
            $this->load->model('customers_model');
            $group = $this->customers_model->getGroupByUID($orgUID, $groupUID);
            if (!$group) { redirect('customers'); return; }
            $this->pageData['GroupData']     = $group;
            $this->pageData['Members']       = $this->customers_model->getGroupMembers($orgUID, $groupUID);
            $this->pageData['GroupOverview'] = $this->customers_model->getGroupOverview($orgUID, $groupUID);
            $this->load->view('customers/groups/detail', $this->pageData);
        } catch (Exception $e) {
            redirect('customers');
        }
    }

    public function getGroupOutstanding($groupUID = 0) {
        $this->EndReturnData = new stdClass();
        try {
            $orgUID = $this->pageData['JwtData']->Org->OrgUID;
            $this->load->model('customers_model');
            $this->EndReturnData->Error = false;
            $this->EndReturnData->Data  = $this->customers_model->getGroupOutstanding($orgUID, (int)$groupUID);
        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function getGroupsForDropdown() {
        $this->EndReturnData = new stdClass();
        try {
            $orgUID = $this->pageData['JwtData']->Org->OrgUID;
            $this->load->model('customers_model');
            $this->EndReturnData->Error  = false;
            $this->EndReturnData->Groups = $this->customers_model->getActiveGroupsForDropdown($orgUID);
        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }


}