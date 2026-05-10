<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Customers extends CI_Controller {

    public $pageData = array();
    private $EndReturnData;

    public function __construct() {
        parent::__construct();
    }

    // ── Internal helpers ──────────────────────────────────────────────────────
    private function _initModule() {
        if (isset($this->pageData['ModuleId'])) return;

        $controllerName = strtolower($this->router->fetch_class());
        $getModuleInfo  = $this->redis_cache->get('Redis_UserModuleInfo')->Value ?? [];
        $ModuleInfo     = array_values(array_filter($getModuleInfo, fn($m) => $m->ControllerName === $controllerName));
        if (empty($ModuleInfo)) {
            throw new Exception("Module information not found for controller: {$controllerName}");
        }

        $this->pageData['ModuleId']              = $ModuleInfo[0]->ModuleUID;
        $this->pageData['ModColumnData']         = $ModuleInfo[0]->DispViewColumns ?? [];
        $this->pageData['DispSettColumnDetails'] = $ModuleInfo[0]->DispSettingsViewColumns ?? [];

        $GeneralSettings = ($this->redis_cache->get('Redis_UserGenSettings')->Value) ?? new stdClass();
        $this->pageData['JwtData']->GenSettings = $GeneralSettings;
        $this->pageData['Limit'] = $GeneralSettings->RowLimit ?? 10;
    }

    private function _fetchTableData($pageNo, $limit, $filter = []) {

        $orgUID = $this->pageData['JwtData']->User->OrgUID;
        $offset = max(0, ($pageNo - 1) * $limit);

        $this->load->model('customers_model');
        $result = $this->customers_model->getCustomerListPaginated($orgUID, $limit, $offset, $filter);

        $rowHtml = $this->load->view('customers/list', [
            'DataLists'       => $result->rows,
            'SerialNumber'    => $offset,
            'DispViewColumns' => $this->pageData['ModColumnData'],
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
        try {

            $this->_initModule();
            $limit = $this->pageData['Limit'];

            $pageData = $this->_fetchTableData(1, $limit);
            $this->pageData['ModRowData']    = $pageData->RecordHtmlData;
            $this->pageData['ModPagination'] = $pageData->Pagination;

            $this->load->model('customers_model');
            $this->pageData['CustStats']        = $this->customers_model->getCustomerStats($this->pageData['JwtData']->User->OrgUID);
            $this->pageData['CustomerTypeList'] = $this->customers_model->getCustomerTypeList($this->pageData['JwtData']->User->OrgUID);
            $this->pageData['Tags']             = $this->customers_model->getCustomerTags($this->pageData['JwtData']->User->OrgUID);

            $this->load->model('global_model');
            $GetCountryInfo = $this->global_model->getCountryInfo();
            $this->pageData['CountryInfo'] = $GetCountryInfo->Error === FALSE ? $GetCountryInfo->Data : [];

            // Resolve org phone country code from JwtData (sourced from OrganisationTbl at login)
            // Fall back to OrganisationTbl only if missing from JwtData
            $orgCCode  = $this->pageData['JwtData']->User->OrgCCode  ?? '';
            $orgCISO2  = $this->pageData['JwtData']->User->OrgCISO2  ?? '';
            if (empty($orgCCode) || empty($orgCISO2)) {
                $this->load->model('organisation_model');
                $orgResp = $this->organisation_model->getOrganisationDetails(['Org.OrgUID' => $this->pageData['JwtData']->User->OrgUID]);
                if ($orgResp->Error === FALSE && !empty($orgResp->Data)) {
                    $orgCCode = $orgCCode  ?: ($orgResp->Data[0]->CountryCode ?? '');
                    $orgCISO2 = $orgCISO2  ?: ($orgResp->Data[0]->CountryISO2 ?? '');
                }
            }
            $this->pageData['OrgCCode'] = $orgCCode;
            $this->pageData['OrgCISO2'] = $orgCISO2;

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
                        'text' => $value->Area
                            ? $value->Name . ' (' . $value->Area . ')'
                            : $value->Name,
                    ];
                }
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = 'Unable to fetch customers at the moment.';
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    private function loadCountryStateCityData() {
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
    }

    public function create() {
        try {
            $this->loadCountryStateCityData();
            $this->load->model('customers_model');
            $this->pageData['CustomerTypeList'] = $this->customers_model->getCustomerTypeList($this->pageData['JwtData']->User->OrgUID);
            $this->load->view('customers/forms/add', $this->pageData);
        } catch (Exception $e) {
            redirect('customers', 'refresh');
        }
    }

    private function buildCustomerFormData($postData, $isCreate = false) {
        $data = [
            'Name'              => getPostValue($postData, 'Name'),
            'Area'              => getPostValue($postData, 'Area'),
            'OrgUID'            => $this->pageData['JwtData']->User->OrgUID,
            'BranchUID'         => $this->pageData['JwtData']->User->BranchUID,
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
            'UpdatedBy'         => $this->pageData['JwtData']->User->UserUID,
        ];
        if ($isCreate) {
            $this->load->model('transactions_model');
            $data['CustToken'] = $this->transactions_model->_generateUniqueToken('Customers.CustomerTbl', 'CustToken');
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

            if (getPostValue($PostData, 'transCustomer') == 1) {
                $this->load->model('transactions_model');
                $customersData = $this->transactions_model->getCustomersDetails('', ['Customers.CustomerUID' => $CustomerUID]);
                $cust_Data = [];
                foreach ($customersData as $value) {
                    $cust_Data = [
                        'id'   => $value->CustomerUID,
                        'text' => $value->Area ? $value->Name . ' (' . $value->Area . ')' : $value->Name,
                    ];
                    if ($value->AddrUID) {
                        $cust_Data['address'] = [
                            'Line1'   => $value->Line1,
                            'Line2'   => $value->Line2,
                            'Pincode' => $value->Pincode,
                            'City'    => $value->CityText,
                            'State'   => $value->StateText,
                        ];
                    }
                }
                $this->EndReturnData->Customer = $cust_Data;
            }

            $this->dbwrite_model->commitTransaction();

            $this->_initModule();
            $pageData = $this->_fetchTableData(1, $this->pageData['Limit']);
            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Message    = 'Created Successfully';
            $this->EndReturnData->List       = $pageData->RecordHtmlData;
            $this->EndReturnData->Pagination = $pageData->Pagination;
            $this->EndReturnData->Stats      = $this->customers_model->getCustomerStats($this->pageData['JwtData']->User->OrgUID);

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

    public function edit($CustomerUID) {
        try {

            $CustomerUID = (int) $CustomerUID;
            if ($CustomerUID <= 0) { redirect('customers', 'refresh'); return; }

            $this->load->model('customers_model');
            $getCustData = $this->customers_model->getCustomers(['Customers.CustomerUID' => $CustomerUID]);
            if (empty($getCustData) || count($getCustData) !== 1) { redirect('customers', 'refresh'); return; }

            $this->pageData['EditData'] = $getCustData[0];

            $this->loadCountryStateCityData();
            $this->pageData['CustomerTypeList'] = $this->customers_model->getCustomerTypeList($this->pageData['JwtData']->User->OrgUID);
            $this->pageData['BankDetails']      = $this->customers_model->getCustomerBankInfo(['CustBankDetails.CustomerUID' => $getCustData[0]->CustomerUID]);

            $AddressInfo = $this->customers_model->getCustomerAddress(['CustAddress.CustomerUID' => $getCustData[0]->CustomerUID]);
            $this->pageData['BillingAddr']  = null;
            $this->pageData['ShippingAddr'] = null;
            foreach ($AddressInfo as $addr) {
                if ($addr->AddressType === 'Billing')  $this->pageData['BillingAddr']  = $addr;
                if ($addr->AddressType === 'Shipping') $this->pageData['ShippingAddr'] = $addr;
            }

            $this->load->view('customers/forms/edit', $this->pageData);

        } catch (Exception $e) {
            redirect('customers', 'refresh');
        }
    }

    public function cloneCustomer($CustomerUID) {
        try {

            $CustomerUID = (int) $CustomerUID;
            if ($CustomerUID <= 0) { redirect('customers', 'refresh'); return; }

            $this->load->model('customers_model');
            $customerData = $this->customers_model->getCustomers(['Customers.CustomerUID' => $CustomerUID]);
            if (empty($customerData) || count($customerData) !== 1) { redirect('customers', 'refresh'); return; }

            $this->pageData['EditData']   = $customerData[0];
            $this->pageData['BankDetails'] = $this->customers_model->getCustomerBankInfo(['CustBankDetails.CustomerUID' => $customerData[0]->CustomerUID]);

            $this->loadCountryStateCityData();

            $AddressInfo = $this->customers_model->getCustomerAddress(['CustAddress.CustomerUID' => $customerData[0]->CustomerUID]);
            $this->pageData['BillingAddr']  = null;
            $this->pageData['ShippingAddr'] = null;
            foreach ($AddressInfo as $addr) {
                if ($addr->AddressType === 'Billing')  $this->pageData['BillingAddr']  = $addr;
                if ($addr->AddressType === 'Shipping') $this->pageData['ShippingAddr'] = $addr;
            }

            $this->load->view('customers/forms/clone', $this->pageData);

        } catch (Exception $e) {
            redirect('customers', 'refresh');
        }
    }

    public function loadModalForm($type = 'add', $uid = 0) {
        $this->EndReturnData = new stdClass();
        try {
            $type = in_array($type, ['add', 'edit', 'clone']) ? $type : 'add';
            $uid  = (int) $uid;

            $this->load->model('customers_model');
            $this->loadCountryStateCityData();
            $this->pageData['CustomerTypeList'] = $this->customers_model->getCustomerTypeList($this->pageData['JwtData']->User->OrgUID);

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
                'FormMode'         => $type,
                'FormData'         => $formData,
                'BankDetails'      => $bankDetails,
                'BillingAddr'      => $billingAddr,
                'ShippingAddr'     => $shippingAddr,
                'CustomerTypeList' => $this->pageData['CustomerTypeList'],
                'CountryInfo'      => $this->pageData['CountryInfo'],
                'JwtData'          => $this->pageData['JwtData'],
            ], TRUE);

            $this->EndReturnData->Error        = FALSE;
            $this->EndReturnData->Html         = $html;
            $this->EndReturnData->StateData    = $this->pageData['StateData'];
            $this->EndReturnData->CityData     = $this->pageData['CityData'];
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

    public function getCustomerForModal($uid = 0) {
        $this->EndReturnData = new stdClass();
        try {
            $uid = (int) $uid;
            if ($uid <= 0) throw new Exception('Invalid customer ID.');

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

            $this->load->library('accountledger');
            $this->accountledger->updateEntityLedgerInfo(
                $CustomerUID,
                [
                    'DebitCreditAmount' => getPostValue($PostData, 'DebitCreditAmount', '', 0),
                    'DebitCreditCheck'  => getPostValue($PostData, 'DebitCreditCheck', '', 'Debit'),
                    'Name'              => getPostValue($PostData, 'Name'),
                ],
                'Customer',
            );

            $this->dbwrite_model->commitTransaction();

            $pageNo = (int) ($this->input->post('PageNo') ?: 1);
            $this->_initModule();
            $pageData = $this->_fetchTableData($pageNo, $this->pageData['Limit']);
            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Message    = 'Updated Successfully';
            $this->EndReturnData->List       = $pageData->RecordHtmlData;
            $this->EndReturnData->Pagination = $pageData->Pagination;
            $this->EndReturnData->Stats      = $this->customers_model->getCustomerStats($this->pageData['JwtData']->User->OrgUID);

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

            $this->dbwrite_model->commitTransaction();

            $pageNo   = (int) ($this->input->post('PageNo') ?: 1);
            $this->_initModule();
            $pageData = $this->_fetchTableData($pageNo, $this->pageData['Limit']);

            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Message    = 'Deleted Successfully';
            $this->EndReturnData->List       = $pageData->RecordHtmlData;
            $this->EndReturnData->Pagination = $pageData->Pagination;
            $this->EndReturnData->Stats      = $this->customers_model->getCustomerStats($this->pageData['JwtData']->User->OrgUID);

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function getCustomerTags() {
        $this->EndReturnData = new stdClass();
        try {
            $this->load->model('customers_model');
            $tags = $this->customers_model->getCustomerTags($this->pageData['JwtData']->User->OrgUID);
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
            $types = $this->customers_model->getCustomerTypeList($this->pageData['JwtData']->User->OrgUID);
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
            $stats = $this->customers_model->getCustomerStats($this->pageData['JwtData']->User->OrgUID);
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

            $pageNo = (int) ($this->input->post('PageNo') ?: 1);
            $this->_initModule();
            $pageData = $this->_fetchTableData($pageNo, $this->pageData['Limit']);
            $this->EndReturnData->Error      = false;
            $this->EndReturnData->Message    = 'Status updated successfully';
            $this->EndReturnData->Stats      = $this->customers_model->getCustomerStats($this->pageData['JwtData']->User->OrgUID);
            $this->EndReturnData->List       = $pageData->RecordHtmlData;
            $this->EndReturnData->Pagination = $pageData->Pagination;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
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

            $pageNo   = (int) ($this->input->post('PageNo') ?: 1);
            $this->_initModule();
            $pageData = $this->_fetchTableData($pageNo, $this->pageData['Limit']);

            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Message    = count($CustomerUIDs) . ' customer(s) deleted successfully';
            $this->EndReturnData->List       = $pageData->RecordHtmlData;
            $this->EndReturnData->Pagination = $pageData->Pagination;
            $this->EndReturnData->Stats      = $this->customers_model->getCustomerStats($this->pageData['JwtData']->User->OrgUID);

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

            $orgUID    = $this->pageData['JwtData']->User->OrgUID;
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

    public function getCustomerSearchList() {

        $this->EndReturnData = new stdClass();
        try {

            $pageNo = (int) $this->input->post('PageNo') ?: 1;
            $limit  = (int) $this->input->post('RowLimit') ?: 10;
            $search = trim($this->input->post('Search') ?? '');

            $orgUID = $this->pageData['JwtData']->User->OrgUID;
            $offset = max(0, ($pageNo - 1) * $limit);

            $filter = [];
            if (!empty($search)) {
                $filter['SearchAllData'] = $search;
            }

            $this->load->model('customers_model');
            $result = $this->customers_model->getCustomerListPaginated($orgUID, $limit, $offset, $filter);

            $this->EndReturnData->Error      = false;
            $this->EndReturnData->Customers  = $result->rows ?? [];
            $this->EndReturnData->Pagination = $this->globalservice->buildPagePaginationHtml(
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

}
