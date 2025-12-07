<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Customers extends CI_Controller {

    public $pageData = array();
    private $EndReturnData;
    private $custPageCallUrl = '/customers/getCustomersDetails/';
    private $custDataList = 'customers/list';

    public function __construct() {
        parent::__construct();

    }

    private function sendJsonResponse($data) {
        $this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;
    }

    public function index() {

        try {

            $controllerName = strtolower($this->router->fetch_class());

            $getModuleInfo = $this->redis_cache->get('Redis_UserModuleInfo') ?? [];
            $ModuleInfo = array_values(array_filter($getModuleInfo, fn($m) => $m->ControllerName === $controllerName));
            if (empty($ModuleInfo)) {
                throw new Exception("Module information not found for controller: {$controllerName}");
            }

            $this->pageData['ModuleId'] = $ModuleInfo[0]->ModuleUID;

            $GeneralSettings = $this->redis_cache->get('Redis_UserGenSettings') ?? new stdClass();
            $limit = $GeneralSettings->RowLimit ?? 10;

            $ReturnResponse = $this->globalservice->getBaseMainPageTablePagination($this->pageData['ModuleId'], $this->custPageCallUrl, $this->custDataList, 0, $limit, 0, [], [], 'Index');
            if ($ReturnResponse->Error) throw new Exception($ReturnResponse->Message);

            $this->pageData['ModColumnData'] = $ReturnResponse->DispViewColumns;
            $this->pageData['ModRowData'] = $ReturnResponse->RecordHtmlData;
            $this->pageData['ModPagination'] = $ReturnResponse->Pagination;
            $this->pageData['DispSettColumnDetails'] = $ReturnResponse->DispSettingsViewColumns;

            $this->pageData['JwtData']->GenSettings = $GeneralSettings;
            $this->load->view('customers/view', $this->pageData);

        } catch (Exception $e) {
            redirect('dashboard', 'refresh');
        }

    }

    public function getCustomersDetails($pageNo = 0) {

		$this->EndReturnData = new stdClass();
		try {
            
            $getResp = $this->commonCustomerTablePagination($pageNo);

            $this->EndReturnData->List = $getResp->RecordHtmlData;
            $this->EndReturnData->Pagination = $getResp->Pagination;
            $this->EndReturnData->Error = false;

		} catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		$this->sendJsonResponse($this->EndReturnData);

	}

    public function commonCustomerTablePagination($pageNo = 0) {

        $limit = (int) $this->input->post('RowLimit');
        $offset = $pageNo ? ($pageNo - 1) * $limit : 0;
        $Filter = $this->input->post('Filter');
        $ModuleId = $this->input->post('ModuleId');

        $ReturnResponse = $this->globalservice->getBaseMainPageTablePagination($ModuleId, $this->custPageCallUrl, $this->custDataList, $pageNo, $limit, $offset, $Filter, [], 'Pagination');
        if($ReturnResponse->Error) {
            throw new Exception($ReturnResponse->Message);
        }

        return $ReturnResponse;

    }

    public function searchCustomers() {

        $this->EndReturnData = new stdClass();
		try {

            $term = trim($this->input->get('term'));
            if($term) {

                $this->load->model('customers_model');
                $customersData = $this->customers_model->getCustomersDetails($term, []);

                $customersDetails = [];
                foreach ($customersData as $value) {
                    $customersDetails[] = [
                        'id'   => $value->CustomerUID,
                        'text' => $value->Area 
                            ? $value->Name . ' (' . $value->Area . ')' 
                            : $value->Name,
                    ];
                }
                $this->EndReturnData->Lists = $customersDetails;

            } else {
                $this->EndReturnData->Lists = [];
            }
            $this->EndReturnData->Error = false;

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->sendJsonResponse($this->EndReturnData);
        
    }

    public function create() {

        try {

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
            'UpdatedOn'        => time(),
        ];
        if ($isCreate) {
            $data['CreatedBy'] = $this->pageData['JwtData']->User->UserUID;
            $data['CreatedOn'] = time();
        }
        return $data;
    }

    public function addCustomerData() {

        $this->EndReturnData = new stdClass();
		try {

            $PostData = $this->input->post();

            $this->load->model('formvalidation_model');
            $ErrorInForm = $this->formvalidation_model->custValidateForm($PostData);
            if(empty($ErrorInForm)) {

                $customerFormData = $this->buildCustomerFormData($PostData, true);

                $this->load->model('dbwrite_model');
                $InsertDataResp = $this->dbwrite_model->insertData('Customers', 'CustomerTbl', $customerFormData);
                if ($InsertDataResp->Error) throw new Exception($InsertDataResp->Message);
                
                $CustomerUID = $InsertDataResp->ID;

                if(isset($_FILES['UploadImage'])) {
                    $UploadResp = $this->globalservice->fileUploadService($_FILES['UploadImage'], 'customers/images/', 'Image', ['Customers', 'CustomerTbl', array('CustomerUID' => $CustomerUID)]);
                    if ($UploadResp->Error) throw new Exception($UploadResp->Message);
                }

                $this->saveBankDetails($CustomerUID, $this->input->post('BankDetailsJSON'));

                foreach ([['Bill','Billing'], ['Ship','Shipping']] as [$prefix,$type]) {
                    $this->saveAddressInfo($PostData, $CustomerUID, $prefix, $type);
                }

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = 'Created Successfully';

            } else {
                throw new Exception($ErrorInForm);
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		$this->sendJsonResponse($this->EndReturnData);

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
            
            $this->pageData['BankDetails'] = $this->customers_model->getCustomerBankInfo([
                'CustBankDetails.CustomerUID' => $getCustData[0]->CustomerUID
            ]);

            $AddressInfo = $this->customers_model->getCustomerAddress([
                'CustAddress.CustomerUID' => $getCustData[0]->CustomerUID
            ]);

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

    public function clone($CustomerUID) {

        try {

            $CustomerUID = (int) $CustomerUID;
            if ($CustomerUID <= 0) {
                redirect('customers', 'refresh');
                return;
            }

            $this->load->model('customers_model');
            $GetCustomerData = $this->customers_model->getCustomers(['Customers.CustomerUID' => $CustomerUID]);
            if (empty($customerData) || sizeof($customerData) !== 1) {
                redirect('customers', 'refresh');
                return;
            }

            $this->pageData['EditData'] = $GetCustomerData[0];

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
            
            $this->pageData['BankDetails'] = $this->customers_model->getCustomerBankInfo([
                'CustBankDetails.CustomerUID' => $GetCustomerData[0]->CustomerUID
            ]);

            $AddressInfo = $this->customers_model->getCustomerAddress([
                'CustAddress.CustomerUID' => $GetCustomerData[0]->CustomerUID
            ]);

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
		try {

            $this->load->model('formvalidation_model');

            $PostData = $this->input->post();
            $ErrorInForm = $this->formvalidation_model->custValidateForm($PostData);
            if(empty($ErrorInForm)) {

                $CustomerUID = getPostValue($PostData, 'CustomerUID', 0);

                $customerFormData = $this->buildCustomerFormData($PostData, false);
                if (!empty($PostData['ImageRemoved'])) $customerFormData['Image'] = NULL;

                $this->load->model('dbwrite_model');
                $UpdateDataResp = $this->dbwrite_model->updateData('Customers', 'CustomerTbl', $customerFormData, ['CustomerUID' => $CustomerUID]);
                if ($UpdateDataResp->Error) throw new Exception($UpdateDataResp->Message);

                if(isset($_FILES['UploadImage'])) {
                    $UploadResp = $this->globalservice->fileUploadService($_FILES['UploadImage'], 'customers/images/', 'Image', ['Customers', 'CustomerTbl', array('CustomerUID' => $CustomerUID)]);
                    if ($UploadResp->Error) throw new Exception($UploadResp->Message);
                }

                $actionBankData = getPostValue($PostData, 'actionBankData');
                if($actionBankData) {
                    $delBnkFlag = getPostValue($PostData, 'delBankDataFlag');
                    if($delBnkFlag == 1) {
                        $delBankRecIds = getPostValue($PostData, 'delBankData');
                        if(count($delBankRecIds) == 1) {
                            $updDataResp = $this->dbwrite_model->updateData('Customers', 'CustBankDetailsTbl', ['IsDeleted' => 1, 'UpdatedBy' => $this->pageData['JwtData']->User->UserUID, 'UpdatedOn' => time()], ['CustBankDetUID' => $delBankRecIds[0]]);
                        } else {
                            $updBankArray = [];
                            foreach($delBankRecIds as $rid) {
                                $updBankWhere = [
                                    'CustBankDetUID' => $rid,
                                    'IsDeleted' => 1,
                                    'UpdatedBy' => $this->pageData['JwtData']->User->UserUID,
                                    'UpdatedOn' => time(),
                                ];
                                $updBankArray[] = $updBankWhere;
                            }
                            $updDataResp = $this->dbwrite_model->updateBatchData('Customers', 'CustBankDetailsTbl', $updBankArray, 'CustBankDetUID');
                        }
                        if ($updDataResp->Error) throw new Exception($updDataResp->Message);
                    }

                    $this->saveBankDetails($PostData['CustomerUID'], $this->input->post('BankDetailsJSON'));
                }

                foreach ([['Bill','Billing'], ['Ship','Shipping']] as [$prefix,$type]) {
                    $this->saveAddressInfo($PostData, $CustomerUID, $prefix, $type);
                }

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = 'Updated Successfully';

            } else {
                throw new Exception($ErrorInForm);
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		$this->sendJsonResponse($this->EndReturnData);

    }

    public function checkImageType($str = '') {
        return $this->globalservice->checkImageType($str);
    }

    public function validateDateFormat($date) {
        // Allow empty (optional field)
        if ($date === null || $date === '') {
            return TRUE;
        }

        // Validate format only if not empty
        $d = DateTime::createFromFormat('Y-m-d', $date);
        if ($d && $d->format('Y-m-d') === $date) {
            return TRUE;
        } else {
            $this->form_validation->set_message(
                'validateDateFormat',
                'The {field} must be a valid date in YYYY-MM-DD format.'
            );
            return FALSE;
        }
    }

    private function saveBankDetails($customerUID, $bankDetailsJson) {

        if (!$bankDetailsJson) return;

        $bankDetails = json_decode($bankDetailsJson, true);
        if (!is_array($bankDetails)) return;

        $batchInsert = [];
        foreach ($bankDetails as $record) {
            $bankDataArray = [
                'CustomerUID'            => $customerUID,
                'Type'                   => $record['type'],
                'BankAccountNumber'      => getPostValue($record, 'accNumber') ?? NULL,
                'BankIFSC_Code'          => getPostValue($record, 'ifsc') ?? NULL,
                'BankBranchName'         => getPostValue($record, 'branch') ?? NULL,
                'BankAccountHolderName'  => getPostValue($record, 'holder') ?? NULL,
                'UPI_Id'                 => getPostValue($record, 'upiId') ?? NULL,
                'UpdatedBy'              => $this->pageData['JwtData']->User->UserUID,
                'UpdatedOn'              => time(),
            ];

            if (empty($record['id']) || $record['id'] == 0) {
                $bankDataArray['CreatedBy'] = $this->pageData['JwtData']->User->UserUID;
                $bankDataArray['CreatedOn'] = time();
                $batchInsert[] = $bankDataArray;
            } else {
                $resp = $this->dbwrite_model->updateData(
                    'Customers',
                    'CustBankDetailsTbl',
                    $bankDataArray,
                    ['CustBankDetUID' => $record['id']]
                );
                if ($resp->Error) throw new Exception($resp->Message);
            }
        }
        
        if (count($batchInsert) > 0) {
            $resp = $this->dbwrite_model->insertBatchData('Customers', 'CustBankDetailsTbl', $batchInsert);
            if ($resp->Error) throw new Exception($resp->Message);
        }

    }

    private function saveAddressInfo($postData, $customerUID, $typePrefix, $addressType) {

        if (!isset($postData[$typePrefix.'AddrLine1']) || $postData[$typePrefix.'AddrLine1'] === '') {
            return; // nothing to save
        }

        $addressData = [
            'CustomerUID' => $customerUID,
            'OrgUID'      => $this->pageData['JwtData']->User->OrgUID,
            'AddressType' => $addressType,
            'Line1'       => $postData[$typePrefix.'AddrLine1'],
            'Line2'       => getPostValue($postData, $typePrefix.'AddrLine2') ?? NULL,
            'Pincode'     => $postData[$typePrefix.'AddrPincode'],
            'City'        => getPostValue($postData, $typePrefix.'AddrCity') ?? NULL,
            'CityText'    => getPostValue($postData, $typePrefix.'AddrCityText') ?? NULL,
            'State'       => getPostValue($postData, $typePrefix.'AddrState') ?? NULL,
            'StateText'   => getPostValue($postData, $typePrefix.'AddrStateText') ?? NULL,
            'UpdatedBy'   => $this->pageData['JwtData']->User->UserUID,
            'UpdatedOn'   => time(),
        ];

        $uidField = $typePrefix.'AddressUID';
        if (isset($postData[$uidField]) && $postData[$uidField] == 0) {
            $addressData['CreatedBy'] = $this->pageData['JwtData']->User->UserUID;
            $addressData['CreatedOn'] = time();

            $resp = $this->dbwrite_model->insertData('Customers', 'CustAddressTbl', $addressData);
            if ($resp->Error) throw new Exception($resp->Message);
        } elseif (isset($postData[$uidField]) && $postData[$uidField] > 0) {
            $resp = $this->dbwrite_model->updateData('Customers', 'CustAddressTbl', $addressData, ['CustAddressUID' => $postData[$uidField]]);
            if ($resp->Error) throw new Exception($resp->Message);
        }

    }

    public function deleteCustomerData() {

        $this->EndReturnData = new stdClass();
		try {

            $CustomerUID = $this->input->post('CustomerUID');
            if (empty($customerUID)) {
                throw new Exception('Customer Information is Missing to Delete');
            }

            $updateCustData = [
                'IsDeleted' => 1,
                'UpdatedBy' => $this->pageData['JwtData']->User->UserUID,
                'UpdatedOn' => time(),
            ];

            $this->load->model('dbwrite_model');
            $UpdateResp = $this->dbwrite_model->updateData('Customers', 'CustomerTbl', $updateCustData, ['CustomerUID' => $CustomerUID]);
            if($UpdateResp->Error) {
                throw new Exception($UpdateResp->Message);
            }

            $pageNo = $this->input->post('PageNo');
            $tablePagDataResp = $this->commonCustomerTablePagination($pageNo);

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Deleted Successfully';
            $this->EndReturnData->List = $tablePagDataResp->List;
            $this->EndReturnData->Pagination = $tablePagDataResp->Pagination;
            $this->EndReturnData->UIDs = $tablePagDataResp->UIDs;

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		$this->sendJsonResponse($this->EndReturnData);

    }

    public function deleteBulkCustomers() {

        $this->EndReturnData = new stdClass();
		try {

            $CustomerUIDs = $this->input->post('CustomerUIDs');
            if (empty($customerUIDs)) {
                throw new Exception('Customer Information is Missing to Delete');
            }

            $updateDelData = [
                'IsDeleted' => 1,
                'UpdatedBy' => $this->pageData['JwtData']->User->UserUID,
                'UpdatedOn' => time(),
            ];

            $this->load->model('dbwrite_model');
            $UpdateResp = $this->dbwrite_model->updateData('Customers', 'CustomerTbl', $updateDelData, [], array('CustomerUID' => $CustomerUIDs));
            if($UpdateResp->Error) {
                throw new Exception($UpdateResp->Message);
            }

            $pageNo = $this->input->post('PageNo');
            $tablePagDataResp = $this->commonCustomerTablePagination($pageNo);

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Deleted Successfully';
            $this->EndReturnData->List = $tablePagDataResp->List;
            $this->EndReturnData->Pagination = $tablePagDataResp->Pagination;
            $this->EndReturnData->UIDs = $tablePagDataResp->UIDs;

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		$this->sendJsonResponse($this->EndReturnData);

    }

}