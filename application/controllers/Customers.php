<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Customers extends CI_Controller {

    public $pageData = array();
    private $EndReturnData;

    public function __construct() {
        parent::__construct();

    }

    public function index() {

        try {

            $controllerName = strtolower($this->router->fetch_class());

            $getModuleInfo = $this->redis_cache->get('Redis_UserModuleInfo')->Value ?? [];
            $ModuleInfo = array_values(array_filter($getModuleInfo, fn($m) => $m->ControllerName === $controllerName));
            if (empty($ModuleInfo)) {
                throw new Exception("Module information not found for controller: {$controllerName}");
            }

            $this->pageData['ModuleId'] = $ModuleInfo[0]->ModuleUID;

            $GeneralSettings = ($this->redis_cache->get('Redis_UserGenSettings')->Value) ?? new stdClass();
            $limit = $GeneralSettings->RowLimit ?? 10;
            $this->pageData['JwtData']->GenSettings = $GeneralSettings;

            $ReturnResponse = $this->globalservice->getBaseMainPageTablePagination($this->pageData['ModuleId'], 0, $limit, 0, [], [], 'Index');
            if ($ReturnResponse->Error) throw new Exception($ReturnResponse->Message);

            $this->pageData['ModColumnData'] = $ReturnResponse->DispViewColumns;
            $this->pageData['ModRowData'] = $ReturnResponse->RecordHtmlData;
            $this->pageData['ModPagination'] = $ReturnResponse->Pagination;
            $this->pageData['DispSettColumnDetails'] = $ReturnResponse->DispSettingsViewColumns;
            
            $this->load->view('customers/view', $this->pageData);

        } catch (Exception $e) {
            redirect('dashboard', 'refresh');
        }

    }

    public function searchCustomers() {

        $this->EndReturnData = new stdClass();
        $this->EndReturnData->Error = false;
        $this->EndReturnData->Lists = [];
		try {

            $term = trim($this->input->get('term'));
            if($term) {

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
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = 'Unable to fetch customers at the moment.';
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);
        
    }

    private function loadCountryStateCityData() {

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

    }

    public function create() {

        try {

            $this->loadCountryStateCityData();
            $this->load->view('customers/forms/add', $this->pageData);

        } catch (Exception $e) {
            redirect('customers', 'refresh');
        }

    }

    private function buildCustomerFormData($postData, $isCreate = false) {
        $data = [
            'Name'             => getPostValue($postData, 'Name'),
            'Area'             => getPostValue($postData, 'Area'),
            'OrgUID'           => $this->pageData['JwtData']->User->OrgUID,
            'BranchUID'        => $this->pageData['JwtData']->User->BranchUID,
            'EmailAddress'     => getPostValue($postData, 'EmailAddress'),
            'CountryCode'      => getPostValue($postData, 'CountryCode'),
            'CountryISO2'      => getPostValue($postData, 'CountryISO2', '', 'IN'),
            'MobileNumber'     => getPostValue($postData, 'MobileNumber'),
            'DebitCreditType'  => getPostValue($postData, 'DebitCreditCheck', '', 'Debit'),
            'DebitCreditAmount'=> getPostValue($postData, 'DebitCreditAmount', '', 0),
            'PANNumber'        => getPostValue($postData, 'PANNumber'),
            'ContactPerson'    => getPostValue($postData, 'ContactPerson'),
            'DateOfBirth'      => getPostValue($postData, 'CPDateOfBirth'),
            'GSTIN'            => getPostValue($postData, 'GSTIN'),
            'CompanyName'      => getPostValue($postData, 'CompanyName'),
            'DiscountPercent'  => getPostValue($postData, 'DiscountPercent', '', 0),
            'CreditPeriod'     => getPostValue($postData, 'CreditPeriod', '', 30),
            'CreditLimit'      => getPostValue($postData, 'CreditLimit', '', 0),
            'Notes'            => getPostValue($postData, 'Notes'),
            'Tags'             => getPostValue($postData, 'Tags', 'Comma'),
            'CCEmails'         => getPostValue($postData, 'CCEmails', 'Comma'),
            'UpdatedBy'        => $this->pageData['JwtData']->User->UserUID,
        ];
        if ($isCreate) {
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
            if(!empty($ErrorInForm)) {
                throw new InvalidArgumentException('VALIDATION_ERROR');
            }

            $customerFormData = $this->buildCustomerFormData($PostData, true);
            
            $InsertDataResp = $this->dbwrite_model->insertData('Customers', 'CustomerTbl', $customerFormData);
            if ($InsertDataResp->Error) throw new Exception($InsertDataResp->Message);
            
            $CustomerUID = $InsertDataResp->ID;

            if(isset($_FILES['UploadImage'])) {
                $UploadResp = $this->globalservice->fileUploadService($_FILES['UploadImage'], 'customers/images/', 'Image', ['Customers', 'CustomerTbl', array('CustomerUID' => $CustomerUID)]);
                if ($UploadResp->Error) throw new Exception($UploadResp->Message);
            }

            $this->globalservice->saveBankDetails($CustomerUID, $this->input->post('BankDetailsJSON'), 'Customers', 'CustBankDetailsTbl');

            foreach ([['Bill','Billing'], ['Ship','Shipping']] as [$prefix,$type]) {
                $this->globalservice->saveAddressInfo($PostData, $CustomerUID, $prefix, $type, 'Customers', 'CustAddressTbl', 'CustAddressUID', 'CustomerUID');
            }

            $this->load->library('accountledger');
            $this->accountledger->createLedgerAccountingInfo(
                $CustomerUID,
                [
                    'Name' => getPostValue($PostData, 'Name'),
                    'OpeningBalance' => getPostValue($PostData, 'DebitCreditAmount', '', 0),
                    'OpeningBalanceType' => getPostValue($PostData, 'DebitCreditCheck', '', 'Debit'),
                ],
                'Customer'
            );

            if(getPostValue($PostData, 'transCustomer') == 1) {
                $this->load->model('transactions_model');
                $customersData = $this->transactions_model->getCustomersDetails('', ['Customers.CustomerUID' => $CustomerUID]);
                $cust_Data = [];
                foreach ($customersData as $value) {
                    $cust_Data = [
                        'id'   => $value->CustomerUID,
                        'text' => $value->Area  ? $value->Name . ' (' . $value->Area . ')' : $value->Name,
                    ];
                    if($value->AddrUID) {
                        $cust_Data['address'] = [
                            'Line1' => $value->Line1,
                            'Line2' => $value->Line2,
                            'Pincode' => $value->Pincode,
                            'City' => $value->CityText,
                            'State' => $value->StateText,
                        ];
                    }
                }
                $this->EndReturnData->Customer = $cust_Data;
            }
            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Created Successfully';

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
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		$this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function edit($CustomerUID) {

        try {

            $CustomerUID = (int) $CustomerUID;
            if ($CustomerUID <= 0) {
                redirect('customers', 'refresh');
                return;
            }

            $this->load->model('customers_model');
            $getCustData = $this->customers_model->getCustomers(['Customers.CustomerUID' => $CustomerUID]);
            if (empty($getCustData) || count($getCustData) !== 1) {
                redirect('customers', 'refresh');
                return;
            }

            $this->pageData['EditData'] = $getCustData[0];

            $this->loadCountryStateCityData();
            
            $this->pageData['BankDetails'] = $this->customers_model->getCustomerBankInfo(['CustBankDetails.CustomerUID' => $getCustData[0]->CustomerUID]);

            $AddressInfo = $this->customers_model->getCustomerAddress(['CustAddress.CustomerUID' => $getCustData[0]->CustomerUID]);

            $this->pageData['BillingAddr']  = null;
            $this->pageData['ShippingAddr'] = null;
            
            foreach ($AddressInfo as $addr) {
                if ($addr->AddressType === 'Billing') {
                    $this->pageData['BillingAddr'] = $addr;
                } elseif ($addr->AddressType === 'Shipping') {
                    $this->pageData['ShippingAddr'] = $addr;
                }
            }

            $this->load->view('customers/forms/edit', $this->pageData);

        } catch (Exception $e) {
            redirect('customers', 'refresh');
        }

    }

    public function cloneCustomer($CustomerUID) {

        try {

            $CustomerUID = (int) $CustomerUID;
            if ($CustomerUID <= 0) {
                redirect('customers', 'refresh');
                return;
            }

            $this->load->model('customers_model');
            $customerData = $this->customers_model->getCustomers(['Customers.CustomerUID' => $CustomerUID]);
            if (empty($customerData) || sizeof($customerData) !== 1) {
                redirect('customers', 'refresh');
                return;
            }

            $this->pageData['EditData'] = $customerData[0];

            $this->loadCountryStateCityData();
            
            $this->pageData['BankDetails'] = $this->customers_model->getCustomerBankInfo(['CustBankDetails.CustomerUID' => $customerData[0]->CustomerUID]);

            $AddressInfo = $this->customers_model->getCustomerAddress(['CustAddress.CustomerUID' => $customerData[0]->CustomerUID]);

            $this->pageData['BillingAddr']  = null;
            $this->pageData['ShippingAddr'] = null;
            
            foreach ($AddressInfo as $addr) {
                if ($addr->AddressType === 'Billing') {
                    $this->pageData['BillingAddr'] = $addr;
                } elseif ($addr->AddressType === 'Shipping') {
                    $this->pageData['ShippingAddr'] = $addr;
                }
            }

            $this->load->view('customers/forms/clone', $this->pageData);

        } catch (Exception $e) {
            redirect('customers', 'refresh');
        }

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
            if(!empty($ErrorInForm)) {
                throw new InvalidArgumentException('VALIDATION_ERROR');
            }

            $CustomerUID = getPostValue($PostData, 'CustomerUID');

            $customerFormData = $this->buildCustomerFormData($PostData, false);
            if (!empty($PostData['ImageRemoved'])) $customerFormData['Image'] = NULL;

            $UpdateDataResp = $this->dbwrite_model->updateData('Customers', 'CustomerTbl', $customerFormData, ['CustomerUID' => $CustomerUID]);
            if ($UpdateDataResp->Error) throw new Exception($UpdateDataResp->Message);

            if(isset($_FILES['UploadImage'])) {
                $UploadResp = $this->globalservice->fileUploadService($_FILES['UploadImage'], 'customers/images/', 'Image', ['Customers', 'CustomerTbl', array('CustomerUID' => $CustomerUID)]);
                if ($UploadResp->Error) throw new Exception($UploadResp->Message);
            }

            $delBnkFlag = getPostValue($PostData, 'delBankDataFlag');
            if($delBnkFlag == 1) {
                $delBankRecIds = getPostValue($PostData, 'delBankData');
                $this->globalservice->softDeleteBankRecords($delBankRecIds, 'Customers', 'CustBankDetailsTbl', 'CustBankDetUID');
            }
            $this->globalservice->saveBankDetails($CustomerUID, $this->input->post('BankDetailsJSON'), 'Customers', 'CustBankDetailsTbl');

            $delAddrFlag = getPostValue($PostData, 'delAddrDetailFlag');
            if ($delAddrFlag == 1) {
                $delAddrRecIds = getPostValue($PostData, 'delAddrData');
                $this->globalservice->softDeleteAddressRecords($delAddrRecIds, 'Customers', 'CustAddressTbl', 'CustAddressUID');
            }
            foreach ([['Bill','Billing'], ['Ship','Shipping']] as [$prefix,$type]) {
                $this->globalservice->saveAddressInfo($PostData, $CustomerUID, $prefix, $type, 'Customers', 'CustAddressTbl', 'CustAddressUID', 'CustomerUID');
            }

            /** Customer Ledger Update */
            $this->load->library('accountledger');
            $this->accountledger->updateEntityLedgerInfo(
                $CustomerUID,
                [
                    'DebitCreditAmount' => getPostValue($PostData, 'DebitCreditAmount', '', 0),
                    'DebitCreditCheck' => getPostValue($PostData, 'DebitCreditCheck', '', 'Debit'),
                    'Name' => getPostValue($PostData, 'Name')
                ],
                'Customer',
            );

            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Updated Successfully';

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
            $this->EndReturnData->Error = TRUE;
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
            if (!$CustomerUID) {
                throw new Exception('Customer Information is Missing to Delete');
            }

            $this->load->model('accountledger_model');
            $customer = $this->accountledger_model->getEntityWithLedger($CustomerUID, 'Customer');
            if (!$customer) {
                throw new Exception('Customer not found');
            }

            if ($customer->IsDeleted == 1) {
                throw new Exception('Customer already deleted');
            }

            if ($this->customerHasTransactions($CustomerUID)) {
                throw new Exception('Customer has existing transactions (Invoices/Payments/Orders)');
            }
            
            $UpdateResp = $this->dbwrite_model->updateData('Customers', 'CustomerTbl', $this->globalservice->baseDeleteArrayDetails(), ['CustomerUID' => $CustomerUID]);
            if($UpdateResp->Error) throw new Exception($UpdateResp->Message);

            if ($customer->LedgerUID) {
                $this->load->library('accountledger');
                $this->accountledger->deactivateEntityLedger(
                    $CustomerUID, 
                    $customer->LedgerUID,
                    'Customer',
                );
            }

            $this->dbwrite_model->commitTransaction();

            $pageNo = getPostValue($this->input->post(), 'PageNo', 1);
            $getResp = $this->globalservice->baseTableDataPaginationDetails($pageNo);

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Deleted Successfully';
            $this->EndReturnData->List = $getResp->RecordHtmlData;
            $this->EndReturnData->Pagination = $getResp->Pagination;

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		$this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    private function customerHasTransactions($customerId) {

        try {

            $this->load->model('transactions_model');
            $invoices = $this->transactions_model->getEntityInvoices($customerId, 'Customer');
            return count($invoices) > 0;

            // $payments = $this->transactions_model->getCustomerPayments($customerId);
            // if(count($payments) > 0) return true;

            // $orders = $this->transactions_model->getCustomerOrders($customerId);
            // if(count($orders) > 0) return true;

        } catch (Exception $e) {
            return false;
        }

    }

    public function deleteBulkCustomers() {

        $this->EndReturnData = new stdClass();
		try {

            $CustomerUIDs = $this->input->post('CustomerUIDs[]');
            if (empty($CustomerUIDs)) {
                throw new Exception('Customer Information is Missing to Delete');
            }

            if (!is_array($CustomerUIDs)) {
                $CustomerUIDs = [$CustomerUIDs];
            }
            $CustomerUIDs = array_map('intval', $CustomerUIDs);
            $CustomerUIDs = array_filter($CustomerUIDs);
            if (empty($CustomerUIDs)) {
                throw new Exception('Invalid customer IDs provided');
            }

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            foreach ($CustomerUIDs as $customerId) {

                if ($this->customerHasTransactions($customerId)) {
                    throw new Exception("Customer ID {$customerId} has existing transactions (Invoices/Payments/Orders)");
                }
                
                // Check if customer exists and not already deleted
                $this->load->model('accountledger_model');
                $customer = $this->accountledger_model->getEntityWithLedger($customerId, 'Customer');
                
                if (!$customer) {
                    throw new Exception("Customer ID {$customerId} not found");
                }
                
                if ($customer->IsDeleted == 1) {
                    throw new Exception("Customer ID {$customerId} is already deleted");
                }
                
                // Deactivate ledger if exists
                if ($customer->LedgerUID) {
                    $this->load->library('accountledger');
                    $this->accountledger->deactivateEntityLedger(
                        $customerId, 
                        $customer->LedgerUID,
                        'Customer',
                    );
                }
            }
            
            $UpdateResp = $this->dbwrite_model->updateData('Customers', 'CustomerTbl', $this->globalservice->baseDeleteArrayDetails(), [], array('CustomerUID' => $CustomerUIDs));
            if($UpdateResp->Error) {
                throw new Exception($UpdateResp->Message);
            }

            $this->dbwrite_model->commitTransaction();

            $pageNo = getPostValue($this->input->post(), 'PageNo', 1);
            $getResp = $this->globalservice->baseTableDataPaginationDetails($pageNo);

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = count($CustomerUIDs) . ' customer(s) deleted successfully';
            $this->EndReturnData->List = $getResp->RecordHtmlData;
            $this->EndReturnData->Pagination = $getResp->Pagination;

        } catch (Exception $e) {
            if (isset($this->dbwrite_model)) {
                $this->dbwrite_model->rollbackTransaction();
            }
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		$this->globalservice->sendJsonResponse($this->EndReturnData);

    }

}