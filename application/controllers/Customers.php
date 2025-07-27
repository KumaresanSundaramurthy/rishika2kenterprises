<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Customers extends CI_Controller {

    public $pageData = array();
    private $EndReturnData;

    public function __construct() {
        parent::__construct();

    }

    public function index() {

        $ControllerName = strtolower($this->router->fetch_class());

        $this->pageData['ModuleInfo'] = array_values(array_filter($this->pageData['JwtData']->ModuleInfo, function($module) use ($ControllerName) {
            return $module->ControllerName === $ControllerName;
        }));

        $this->pageData['ModuleId'] = $this->pageData['ModuleInfo'][0]->ModuleUID;

        $limit = isset($this->pageData['JwtData']->GenSettings->RowLimit) ? $this->pageData['JwtData']->GenSettings->RowLimit : 10;

        $ReturnResponse = $this->globalservice->getBaseMainPageTablePagination($this->pageData['ModuleId'], '/customers/getCustomersDetails/', 'customers/list', 0, $limit, 0, [], []);
        if($ReturnResponse->Error) {
            throw new Exception($ReturnResponse->Message);
        }

        $this->pageData['ModDataList'] = $ReturnResponse->List;
        $this->pageData['ModDataUIDs'] = $ReturnResponse->UIDs;
        $this->pageData['ModDataPagination'] = $ReturnResponse->Pagination;
        $this->pageData['ColumnDetails'] = $ReturnResponse->AllViewColumns;
        
        $ItemColumns = array_filter($this->pageData['ColumnDetails'], function ($item) {
            return isset($item->IsMainPageApplicable) && $item->IsMainPageApplicable == 1;
        });
        usort($ItemColumns, function ($a, $b) {
            return $a->MainPageOrder <=> $b->MainPageOrder;
        });
        $this->pageData['ModuleColumns'] = $ItemColumns;

        $this->load->view('customers/view', $this->pageData);

    }

    public function getCustomersDetails($pageNo = 0) {

		$this->EndReturnData = new stdClass();
		try {

			$tablePagDataResp = $this->commonCustomerTablePagination($pageNo);

			$this->EndReturnData->Error = false;
            $this->EndReturnData->List = $tablePagDataResp->List;
			$this->EndReturnData->UIDs = $tablePagDataResp->UIDs;
            $this->EndReturnData->Pagination = $tablePagDataResp->Pagination;

		} catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		$this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;

	}

    public function commonCustomerTablePagination($pageNo = 0) {

        $limit = (int) $this->input->post('RowLimit');
        $offset = $pageNo ? ($pageNo - 1) * $limit : 0;
        $Filter = $this->input->post('Filter');
        $ModuleId = $this->input->post('ModuleId');

        $ReturnResponse = $this->globalservice->getBaseMainPageTablePagination($ModuleId, '/customers/getCustomersDetails/', 'customers/list', $pageNo, $limit, $offset, $Filter, []);
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
                $CustomersData = $this->customers_model->getCustomersDetails($term, []);

                $CustomersDetails = array();
                if (sizeof($CustomersData) > 0) {
                    foreach ($CustomersData as $key => $value) {
                        $CustomersDetails[] = array(
                            'id' => $value->CustomerUID,
                            'text' => $value->VillageName ? $value->Name.' ('.$value->VillageName.')' : $value->Name,
                        );
                    }
                }
                $this->EndReturnData->Lists = $CustomersDetails;

            } else {
                $this->EndReturnData->Lists = [];
            }
            

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;
        
    }

    public function add() {

        $this->load->model('global_model');
        $GetCountryInfo = $this->global_model->getCountryInfo();
        if($GetCountryInfo->Error === FALSE) {
            $this->pageData['CountryInfo'] = $GetCountryInfo->Data;
        }

        $this->load->view('customers/forms/add', $this->pageData);

    }

    public function addAddressInfo() {

        $this->EndReturnData = new stdClass();
		try {

            $PostData = $this->input->post();
            if($PostData['AddressType'] && $PostData['CountryCode']) {

                $this->pageData['StateData'] = [];
                $this->pageData['CityData'] = [];

                $this->load->model('global_model');

                $StateInfo = $this->global_model->getStateofCountry($PostData['CountryCode']);
                if($StateInfo->Error === FALSE) {
                    $this->pageData['StateData'] = $StateInfo->Data;
                }

                $CityInfo = $this->global_model->getCityofCountry($PostData['CountryCode']);
                if($CityInfo->Error === FALSE) {
                    $this->pageData['CityData'] = $CityInfo->Data;
                }

                $this->pageData['AddressType'] = $PostData['AddressType'];

                $this->EndReturnData->Error = FALSE;
                $this->EndReturnData->Message = 'Retrieved Successfully';
                $this->EndReturnData->HtmlData = $this->load->view('customers/forms/addressform', $this->pageData, TRUE);

            } else {
                throw new Exception('Address Type is not defined.');
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		$this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;

    }

    public function addCustomerData() {

        $this->EndReturnData = new stdClass();
		try {

            $this->load->model('formvalidation_model');

            $PostData = $this->input->post();
            $ErrorInForm = $this->formvalidation_model->custValidateForm($PostData);
            if(empty($ErrorInForm)) {

                $this->load->model('dbwrite_model');

                $CustomerUID = 0;
                $customerFormData = [
                    'Name' => $PostData['Name'],
                    'VillageName' => $PostData['VillageName'] ? $PostData['VillageName'] : '',
                    'OrgUID' => $this->pageData['JwtData']->User->OrgUID,
                    'CountryCode' => $PostData['CountryCode'],
                    'CountryISO2' => isset($PostData['CountryISO2']) ? $PostData['CountryISO2'] : 'IN',
                    'MobileNumber' => (isset($PostData['MobileNumber']) && !empty($PostData['MobileNumber'])) ? $PostData['MobileNumber'] : NULL,
                    'EmailAddress' => (isset($PostData['EmailAddress']) && !empty($PostData['EmailAddress'])) ? $PostData['EmailAddress'] : NULL,
                    'GSTIN' => (isset($PostData['GSTIN']) && !empty($PostData['GSTIN'])) ? $PostData['GSTIN'] : NULL,
                    'CompanyName' => (isset($PostData['CompanyName']) && !empty($PostData['CompanyName'])) ? $PostData['CompanyName'] : NULL,
                    'DebitCreditType' => isset($PostData['DebitCreditCheck']) ? $PostData['DebitCreditCheck'] : 'Debit',
                    'DebitCreditAmount' => isset($PostData['DebitCreditAmount']) ? $PostData['DebitCreditAmount'] : 0,
                    'PANNumber' => (isset($PostData['PANNumber']) && !empty($PostData['PANNumber'])) ? $PostData['PANNumber'] : NULL,
                    'DiscountPercent' => isset($PostData['DiscountPercent']) ? $PostData['DiscountPercent'] : 0,
                    'CreditLimit' => isset($PostData['CreditLimit']) ? $PostData['CreditLimit'] : 0,
                    'Notes' => (isset($PostData['Notes']) && !empty($PostData['Notes'])) ? $PostData['Notes'] : NULL,
                    'Tags' => (isset($PostData['Tags']) && !empty($PostData['Tags'])) ? implode(',', $PostData['Tags']) : NULL,
                    'CCEmails' => (isset($PostData['CCEmails']) && !empty($PostData['CCEmails'])) ? implode(',', $PostData['CCEmails']) : NULL,
                    'CreatedBy' => $this->pageData['JwtData']->User->UserUID,
                    'UpdatedBy' => $this->pageData['JwtData']->User->UserUID,
                    'CreatedOn' => time(),
                    'UpdatedOn' => time(),
                ];

                $InsertDataResp = $this->dbwrite_model->insertData('Customers', 'CustomerTbl', $customerFormData);
                if($InsertDataResp->Error) {
                    throw new Exception($InsertDataResp->Message);
                } else {
                    $CustomerUID = $InsertDataResp->ID;
                }

                // Image Upload
                if(isset($_FILES['UploadImage'])) {
                    $UploadResp = $this->globalservice->fileUploadService($_FILES['UploadImage'], 'customers/images/', 'Image', ['Customers', 'CustomerTbl', array('CustomerUID' => $CustomerUID)]);
                    if($UploadResp->Error === TRUE) {
                        throw new Exception($UploadResp->Message);
                    }
                }

                if(isset($PostData['BillAddrLine1']) && $PostData['BillAddrLine1'] != '') {
                    $BillingAddressData = [
                        'CustomerUID' => $CustomerUID,
                        'OrgUID' => $this->pageData['JwtData']->User->OrgUID,
                        'AddressType' => 'Billing',
                        'Line1' => $PostData['BillAddrLine1'],
                        'Line2' => $PostData['BillAddrLine2'] ? $PostData['BillAddrLine2'] : null,
                        'Pincode' => $PostData['BillAddrPincode'],
                        'City' => $PostData['BillAddrCity'] ? $PostData['BillAddrCity'] : null,
                        'CityText' => $PostData['BillAddrCityText'] ? $PostData['BillAddrCityText'] : null,
                        'State' => $PostData['BillAddrState'] ? $PostData['BillAddrState'] : null,
                        'StateText' => $PostData['BillAddrStateText'] ? $PostData['BillAddrStateText'] : null,
                        'CreatedBy' => $this->pageData['JwtData']->User->UserUID,
                        'UpdatedBy' => $this->pageData['JwtData']->User->UserUID,
                        'CreatedOn' => time(),
                        'UpdatedOn' => time(),
                    ];

                    $InsertBillAddrResp = $this->dbwrite_model->insertData('Customers', 'CustAddressTbl', $BillingAddressData);
                    if($InsertBillAddrResp->Error) {
                        throw new Exception($InsertBillAddrResp->Message);
                    }
                }

                if(isset($PostData['ShipAddrLine1']) && $PostData['ShipAddrLine1'] != '') {
                    $ShippingAddressData = [
                        'CustomerUID' => $CustomerUID,
                        'OrgUID' => $this->pageData['JwtData']->User->OrgUID,
                        'AddressType' => 'Shipping',
                        'Line1' => $PostData['ShipAddrLine1'],
                        'Line2' => $PostData['ShipAddrLine2'] ? $PostData['ShipAddrLine2'] : null,
                        'Pincode' => $PostData['ShipAddrPincode'],
                        'City' => $PostData['ShipAddrCity'] ? $PostData['ShipAddrCity'] : null,
                        'CityText' => $PostData['ShipAddrCityText'] ? $PostData['ShipAddrCityText'] : null,
                        'State' => $PostData['ShipAddrState'] ? $PostData['ShipAddrState'] : null,
                        'StateText' => $PostData['ShipAddrStateText'] ? $PostData['ShipAddrStateText'] : null,
                        'CreatedBy' => $this->pageData['JwtData']->User->UserUID,
                        'UpdatedBy' => $this->pageData['JwtData']->User->UserUID,
                        'CreatedOn' => time(),
                        'UpdatedOn' => time(),
                    ];

                    $InsertShipAddrResp = $this->dbwrite_model->insertData('Customers', 'CustAddressTbl', $ShippingAddressData);
                    if($InsertShipAddrResp->Error) {
                        throw new Exception($InsertShipAddrResp->Message);
                    }
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

		$this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;

    }

    public function checkImageType($str = '') {
        return $this->globalservice->checkImageType($str);
    }

    public function edit($CustomerUID) {

        $CustomerUID = (int) $CustomerUID;
		if($CustomerUID > 0) {

            $this->load->model('customers_model');
            $GetCustomerData = $this->customers_model->getCustomers(['Customers.CustomerUID' => $CustomerUID]);
            if((sizeof($GetCustomerData) > 0) && sizeof($GetCustomerData) == 1) {

                $this->load->model('global_model');
                $GetCountryInfo = $this->global_model->getCountryInfo();
                if($GetCountryInfo->Error === FALSE) {
                    $this->pageData['CountryInfo'] = $GetCountryInfo->Data;
                }
                
                $this->pageData['EditData'] = $GetCustomerData[0];
                $this->pageData['BillingAddr'] = $this->customers_model->getCustomerAddress(['CustAddress.CustomerUID' => $GetCustomerData[0]->CustomerUID, 'CustAddress.AddressType' => 'Billing']);
                $this->pageData['ShippingAddr'] = $this->customers_model->getCustomerAddress(['CustAddress.CustomerUID' => $GetCustomerData[0]->CustomerUID, 'CustAddress.AddressType' => 'Shipping']);

                $this->pageData['StateData'] = [];
                $this->pageData['CityData'] = [];

                $StateInfo = $this->global_model->getStateofCountry($GetCustomerData[0]->CountryISO2);
                if($StateInfo->Error === FALSE) {
                    $this->pageData['StateData'] = $StateInfo->Data;
                }

                $CityInfo = $this->global_model->getCityofCountry($GetCustomerData[0]->CountryISO2);
                if($CityInfo->Error === FALSE) {
                    $this->pageData['CityData'] = $CityInfo->Data;
                }

                $this->load->view('customers/forms/edit', $this->pageData);

            } else {
                redirect('customers', 'refresh');
            }

        } else {
			redirect('customers', 'refresh');
		}

    }

    public function clone($CustomerUID) {

        $CustomerUID = (int) $CustomerUID;
		if($CustomerUID > 0) {

            $this->load->model('customers_model');
            $GetCustomerData = $this->customers_model->getCustomers(['Customers.CustomerUID' => $CustomerUID]);
            if((sizeof($GetCustomerData) > 0) && sizeof($GetCustomerData) == 1) {

                $this->load->model('global_model');
                $GetCountryInfo = $this->global_model->getCountryInfo();
                if($GetCountryInfo->Error === FALSE) {
                    $this->pageData['CountryInfo'] = $GetCountryInfo->Data;
                }
                
                $this->pageData['EditData'] = $GetCustomerData[0];
                $this->pageData['BillingAddr'] = $this->customers_model->getCustomerAddress(['CustAddress.CustomerUID' => $GetCustomerData[0]->CustomerUID, 'CustAddress.AddressType' => 'Billing']);
                $this->pageData['ShippingAddr'] = $this->customers_model->getCustomerAddress(['CustAddress.CustomerUID' => $GetCustomerData[0]->CustomerUID, 'CustAddress.AddressType' => 'Shipping']);

                $this->pageData['StateData'] = [];
                $this->pageData['CityData'] = [];

                $StateInfo = $this->global_model->getStateofCountry($GetCustomerData[0]->CountryISO2);
                if($StateInfo->Error === FALSE) {
                    $this->pageData['StateData'] = $StateInfo->Data;
                }

                $CityInfo = $this->global_model->getCityofCountry($GetCustomerData[0]->CountryISO2);
                if($CityInfo->Error === FALSE) {
                    $this->pageData['CityData'] = $CityInfo->Data;
                }

                $this->load->view('customers/forms/clone', $this->pageData);

            } else {
                redirect('customers', 'refresh');
            }

        } else {
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

                $this->load->model('dbwrite_model');

                $CustomerUID = $PostData['CustomerUID'];
                $customerFormData = [
                    'Name' => $PostData['Name'],
                    'VillageName' => $PostData['VillageName'] ? $PostData['VillageName'] : '',
                    'CountryCode' => $PostData['CountryCode'],
                    'CountryISO2' => isset($PostData['CountryISO2']) ? $PostData['CountryISO2'] : 'IN',
                    'MobileNumber' => (isset($PostData['MobileNumber']) && !empty($PostData['MobileNumber'])) ? $PostData['MobileNumber'] : NULL,
                    'EmailAddress' => (isset($PostData['EmailAddress']) && !empty($PostData['EmailAddress'])) ? $PostData['EmailAddress'] : NULL,
                    'GSTIN' => (isset($PostData['GSTIN']) && !empty($PostData['GSTIN'])) ? $PostData['GSTIN'] : NULL,
                    'CompanyName' => (isset($PostData['CompanyName']) && !empty($PostData['CompanyName'])) ? $PostData['CompanyName'] : NULL,
                    'DebitCreditType' => isset($PostData['DebitCreditCheck']) ? $PostData['DebitCreditCheck'] : 'Debit',
                    'DebitCreditAmount' => isset($PostData['DebitCreditAmount']) ? $PostData['DebitCreditAmount'] : 0,
                    'PANNumber' => (isset($PostData['PANNumber']) && !empty($PostData['PANNumber'])) ? $PostData['PANNumber'] : NULL,
                    'DiscountPercent' => isset($PostData['DiscountPercent']) ? $PostData['DiscountPercent'] : 0,
                    'CreditLimit' => isset($PostData['CreditLimit']) ? $PostData['CreditLimit'] : 0,
                    'Notes' => (isset($PostData['Notes']) && !empty($PostData['Notes'])) ? $PostData['Notes'] : NULL,
                    'Tags' => (isset($PostData['Tags']) && !empty($PostData['Tags'])) ? implode(',', $PostData['Tags']) : NULL,
                    'CCEmails' => (isset($PostData['CCEmails']) && !empty($PostData['CCEmails'])) ? implode(',', $PostData['CCEmails']) : NULL,
                    'UpdatedBy' => $this->pageData['JwtData']->User->UserUID,
                    'UpdatedOn' => time(),
                ];
                if(isset($PostData['ImageRemoved']) && $PostData['ImageRemoved'] == 1) {
                    $customerFormData['Image'] = NULL;
                }

                $UpdateDataResp = $this->dbwrite_model->updateData('Customers', 'CustomerTbl', $customerFormData, array('CustomerUID' => $CustomerUID));
                if($UpdateDataResp->Error) {
                    throw new Exception($UpdateDataResp->Message);
                }

                // Image Upload
                if(isset($_FILES['UploadImage'])) {
                    $UploadResp = $this->globalservice->fileUploadService($_FILES['UploadImage'], 'customers/images/', 'Image', ['Customers', 'CustomerTbl', array('CustomerUID' => $CustomerUID)]);
                    if($UploadResp->Error === TRUE) {
                        throw new Exception($UploadResp->Message);
                    }
                }

                if(isset($PostData['BillAddrLine1']) && $PostData['BillAddrLine1'] != '') {

                    $BillingAddressData = [
                        'CustomerUID' => $CustomerUID,
                        'OrgUID' => $this->pageData['JwtData']->User->OrgUID,
                        'AddressType' => 'Billing',
                        'Line1' => $PostData['BillAddrLine1'],
                        'Line2' => $PostData['BillAddrLine2'] ? $PostData['BillAddrLine2'] : NULL,
                        'Pincode' => $PostData['BillAddrPincode'],
                        'City' => $PostData['BillAddrCity'] ? $PostData['BillAddrCity'] : NULL,
                        'CityText' => $PostData['BillAddrCityText'] ? $PostData['BillAddrCityText'] : NULL,
                        'State' => $PostData['BillAddrState'] ? $PostData['BillAddrState'] : NULL,
                        'StateText' => $PostData['BillAddrStateText'] ? $PostData['BillAddrStateText'] : NULL,
                        'UpdatedBy' => $this->pageData['JwtData']->User->UserUID,
                        'UpdatedOn' => time(),
                    ];

                    if(isset($PostData['BillAddressUID']) && $PostData['BillAddressUID'] == 0) {

                        $BillingAddressData['CreatedBy'] = $this->pageData['JwtData']->User->UserUID;
                        $BillingAddressData['CreatedOn'] = time();

                        $InsertBillAddrResp = $this->dbwrite_model->insertData('Customers', 'CustAddressTbl', $BillingAddressData);
                        if($InsertBillAddrResp->Error) {
                            throw new Exception($InsertBillAddrResp->Message);
                        }

                    } else if(isset($PostData['BillAddressUID']) && $PostData['BillAddressUID'] > 0) {

                        $UpdateBillAddrResp = $this->dbwrite_model->updateData('Customers', 'CustAddressTbl', $BillingAddressData, array('CustAddressUID' => $PostData['BillAddressUID']));
                        if($UpdateBillAddrResp->Error) {
                            throw new Exception($UpdateBillAddrResp->Message);
                        }

                    }
                    
                }

                if(isset($PostData['ShipAddrLine1']) && $PostData['ShipAddrLine1'] != '') {

                    $ShippingAddressData = [
                        'CustomerUID' => $CustomerUID,
                        'OrgUID' => $this->pageData['JwtData']->User->OrgUID,
                        'AddressType' => 'Shipping',
                        'Line1' => $PostData['ShipAddrLine1'],
                        'Line2' => $PostData['ShipAddrLine2'] ? $PostData['ShipAddrLine2'] : NULL,
                        'Pincode' => $PostData['ShipAddrPincode'],
                        'City' => $PostData['ShipAddrCity'] ? $PostData['ShipAddrCity'] : NULL,
                        'CityText' => $PostData['ShipAddrCityText'] ? $PostData['ShipAddrCityText'] : NULL,
                        'State' => $PostData['ShipAddrState'] ? $PostData['ShipAddrState'] : NULL,
                        'StateText' => $PostData['ShipAddrStateText'] ? $PostData['ShipAddrStateText'] : NULL,
                        'UpdatedBy' => $this->pageData['JwtData']->User->UserUID,
                        'UpdatedOn' => time(),
                    ];

                    if(isset($PostData['ShipAddressUID']) && $PostData['ShipAddressUID'] == 0) {

                        $ShippingAddressData['CreatedBy'] = $this->pageData['JwtData']->User->UserUID;
                        $ShippingAddressData['CreatedOn'] = time();

                        $InsertShipAddrResp = $this->dbwrite_model->insertData('Customers', 'CustAddressTbl', $ShippingAddressData);
                        if($InsertShipAddrResp->Error) {
                            throw new Exception($InsertShipAddrResp->Message);
                        }

                    } else if(isset($PostData['ShipAddressUID']) && $PostData['ShipAddressUID'] > 0) {

                        $UpdateShipAddrResp = $this->dbwrite_model->updateData('Customers', 'CustAddressTbl', $ShippingAddressData, array('CustAddressUID' => $PostData['ShipAddressUID']));
                        if($UpdateShipAddrResp->Error) {
                            throw new Exception($UpdateShipAddrResp->Message);
                        }

                    }

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

		$this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;

    }

    public function deleteCustomerData() {

        $this->EndReturnData = new stdClass();
		try {

            $CustomerUID = $this->input->post('CustomerUID');
            if($CustomerUID) {

                $updateCustData = [
                    'IsDeleted' => 1,
                    'UpdatedBy' => $this->pageData['JwtData']->User->UserUID,
                    'UpdatedOn' => time(),
                ];

                $this->load->model('dbwrite_model');
                $UpdateResp = $this->dbwrite_model->updateData('Customers', 'CustomerTbl', $updateCustData, array('CustomerUID' => $CustomerUID));
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

            } else {
                throw new Exception('Customer Information is Missing to Delete');
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		$this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;

    }

    public function deleteBulkCustomers() {

        $this->EndReturnData = new stdClass();
		try {

            $CustomerUIDs = $this->input->post('CustomerUIDs');
            if($CustomerUIDs) {

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

            } else {
                throw new Exception('Customer Information is Missing to Delete');
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		$this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;

    }

}