<?php defined('BASEPATH') or exit('No direct script access allowed');

class Vendors extends CI_Controller {

    public $pageData = array();
    private $EndReturnData;

    public function __construct() {
        parent::__construct();

    }

    private function sendJsonResponse($data) {
        $this->output
            ->set_content_type('application/json')
            ->set_status_header(200)
            ->set_output(json_encode($data));
        $this->output->_display();
        exit;
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
            $this->load->view('vendors/view', $this->pageData);

        } catch (Exception $e) {
            redirect('dashboard', 'refresh');
        }
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

            $this->load->view('vendors/forms/add', $this->pageData);

        } catch (Exception $e) {
            redirect('vendors', 'refresh');
        }

    }

    private function buildVendorFormData($postData, $isCreate = false) {
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
            'Notes'            => getPostValue($postData, 'Notes'),
            'UpdatedBy'        => $this->pageData['JwtData']->User->UserUID,
            'UpdatedOn'        => time(),
        ];
        if ($isCreate) {
            $data['CreatedBy'] = $this->pageData['JwtData']->User->UserUID;
            $data['CreatedOn'] = time();
        }
        return $data;
    }

    public function addVendorData() {

        $this->EndReturnData = new stdClass();
		try {

            $PostData = $this->input->post();

            $this->load->model('formvalidation_model');
            $ErrorInForm = $this->formvalidation_model->vendorValidateForm($PostData);
            if(empty($ErrorInForm)) {

                $vendorFormData = $this->buildVendorFormData($PostData, true);

                $this->load->model('dbwrite_model');
                $InsertDataResp = $this->dbwrite_model->insertData('Vendors', 'VendorTbl', $vendorFormData);
                if ($InsertDataResp->Error) throw new Exception($InsertDataResp->Message);
                
                $VendorUID = $InsertDataResp->ID;

                if(isset($_FILES['UploadImage'])) {
                    $UploadResp = $this->globalservice->fileUploadService($_FILES['UploadImage'], 'vendors/images/', 'Image', ['Vendors', 'VendorTbl', array('VendorUID' => $VendorUID)]);
                    if ($UploadResp->Error) throw new Exception($UploadResp->Message);
                }

                $this->globalservice->saveBankDetails($VendorUID, $this->input->post('BankDetailsJSON'), 'Vendors', 'VendBankDetailsTbl');

                foreach ([['Bill','Billing'], ['Ship','Shipping']] as [$prefix,$type]) {
                    $this->globalservice->saveAddressInfo($PostData, $VendorUID, $prefix, $type, 'Vendors', 'VendAddressTbl', 'VendAddressUID', 'VendorUID');
                }

                $linkCustomer = isset($PostData['CustomerLinkingCheck']) ? $PostData['CustomerLinkingCheck'] : null;
                if (!empty($linkCustomer) && (string) $linkCustomer == 'NewCustomer') {
                    $insCustDataResp = $this->dbwrite_model->insertData('Customers', 'CustomerTbl', $vendorFormData);
                    if ($insCustDataResp->Error) throw new Exception($insCustDataResp->Message);
                    $this->globalservice->saveBankDetails($insCustDataResp->ID, $this->input->post('BankDetailsJSON'), 'Customers', 'CustBankDetailsTbl');
                    foreach ([['Bill','Billing'], ['Ship','Shipping']] as [$prefix,$type]) {
                        $this->globalservice->saveAddressInfo($PostData, $insCustDataResp->ID, $prefix, $type, 'Customers', 'CustAddressTbl', 'CustAddressUID', 'CustomerUID');
                    }
                    $updVendorLink = $this->dbwrite_model->updateData('Vendors', 'VendorTbl', ['CustomerUID' => $insCustDataResp->ID], ['VendorUID' => $VendorUID]);
                    if ($updVendorLink->Error) {
                        throw new Exception($updVendorLink->Message ?? 'Failed to link customer to vendor');
                    }
                } else if(!empty($linkCustomer) && (string) $linkCustomer == 'ExistingCustomer') {
                    $CustomerUID = getPostValue($PostData, 'Customers', 0);
                    if($CustomerUID <= 0) {
                        throw new Exception('Invalid Customer selected for linking');
                    }
                    $updVendorLink = $this->dbwrite_model->updateData('Vendors', 'VendorTbl', ['CustomerUID' => $CustomerUID], ['VendorUID' => $VendorUID]);
                    if ($updVendorLink->Error) {
                        throw new Exception($updVendorLink->Message ?? 'Failed to link customer to vendor');
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

		$this->sendJsonResponse($this->EndReturnData);

    }

    public function edit($VendorUID) {

        try {

            $VendorUID = (int) $VendorUID;
            if ($VendorUID <= 0) {
                redirect('vendors', 'refresh');
                return;
            }

            $this->load->model('vendors_model');
            $getVendorData = $this->vendors_model->getVendors(['Vendors.VendorUID' => $VendorUID]);
            if (empty($getVendorData) || count($getVendorData) !== 1) {
                redirect('vendors', 'refresh');
                return;
            }

            $this->pageData['EditData'] = $getVendorData[0];

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

            $this->pageData['BankDetails'] = $this->vendors_model->getVendorBankInfo(['VendBankDetails.VendorUID' => $getVendorData[0]->VendorUID]);

            $AddressInfo = $this->vendors_model->getVendorAddress(['VendAddress.VendorUID' => $getVendorData[0]->VendorUID]);

            $this->pageData['BillingAddr']  = null;
            $this->pageData['ShippingAddr'] = null;
            
            foreach ($AddressInfo as $addr) {
                if ($addr->AddressType === 'Billing') {
                    $this->pageData['BillingAddr'] = $addr;
                } elseif ($addr->AddressType === 'Shipping') {
                    $this->pageData['ShippingAddr'] = $addr;
                }
            }

            $this->load->view('vendors/forms/edit', $this->pageData);

        } catch (Exception $e) {
            redirect('vendors', 'refresh');
        }

    }

    public function cloneVendor($VendorUID) {

        try {

            $VendorUID = (int) $VendorUID;
            if ($VendorUID <= 0) {
                redirect('vendors', 'refresh');
                return;
            }

            $this->load->model('vendors_model');
            $vendorData = $this->vendors_model->getVendors(['Vendors.VendorUID' => $VendorUID]);
            if (empty($vendorData) || sizeof($vendorData) !== 1) {
                redirect('vendors', 'refresh');
                return;
            }

            $this->pageData['EditData'] = $vendorData[0];

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

            $this->pageData['BankDetails'] = $this->vendors_model->getVendorBankInfo(['VendBankDetails.VendorUID' => $vendorData[0]->VendorUID]);

            $AddressInfo = $this->vendors_model->getVendorAddress(['VendAddress.VendorUID' => $vendorData[0]->VendorUID]);

            $this->pageData['BillingAddr']  = null;
            $this->pageData['ShippingAddr'] = null;
            
            foreach ($AddressInfo as $addr) {
                if ($addr->AddressType === 'Billing') {
                    $this->pageData['BillingAddr'] = $addr;
                } elseif ($addr->AddressType === 'Shipping') {
                    $this->pageData['ShippingAddr'] = $addr;
                }
            }

            $this->load->view('vendors/forms/clone', $this->pageData);

        } catch (Exception $e) {
            redirect('vendors', 'refresh');
        }

    }

    public function updateVendorData() {

        $this->EndReturnData = new stdClass();
		try {

            $this->load->model('formvalidation_model');

            $PostData = $this->input->post();
            $ErrorInForm = $this->formvalidation_model->vendorValidateForm($PostData);
            if(empty($ErrorInForm)) {

                $VendorUID = getPostValue($PostData, 'VendorUID', 0);

                $vendorFormData = $this->buildVendorFormData($PostData, false);
                if (!empty($PostData['ImageRemoved'])) $vendorFormData['Image'] = NULL;

                $this->load->model('dbwrite_model');
                $UpdateDataResp = $this->dbwrite_model->updateData('Vendors', 'VendorTbl', $vendorFormData, ['VendorUID' => $VendorUID]);
                if ($UpdateDataResp->Error) throw new Exception($UpdateDataResp->Message);

                if(isset($_FILES['UploadImage'])) {
                    $UploadResp = $this->globalservice->fileUploadService($_FILES['UploadImage'], 'vendors/images/', 'Image', ['Vendors', 'VendorTbl', array('VendorUID' => $VendorUID)]);
                    if ($UploadResp->Error) throw new Exception($UploadResp->Message);
                }
                
                $delBnkFlag = getPostValue($PostData, 'delBankDataFlag');
                if($delBnkFlag == 1) {
                    $delBankRecIds = getPostValue($PostData, 'delBankData');
                    $this->globalservice->softDeleteBankRecords($delBankRecIds, 'Vendors', 'VendBankDetailsTbl', 'VendBankDetUID');
                }
                $this->globalservice->saveBankDetails($PostData['VendorUID'], $this->input->post('BankDetailsJSON'), 'Vendors', 'VendBankDetailsTbl');

                $delAddrFlag = getPostValue($PostData, 'delAddrDetailFlag');
                if ($delAddrFlag == 1) {
                    $delAddrRecIds = getPostValue($PostData, 'delAddrData');
                    $this->globalservice->softDeleteAddressRecords($delAddrRecIds, 'Vendors', 'VendAddressTbl', 'VendAddressUID');
                }
                foreach ([['Bill','Billing'], ['Ship','Shipping']] as [$prefix,$type]) {
                    $this->globalservice->saveAddressInfo($PostData, $VendorUID, $prefix, $type, 'Vendors', 'VendAddressTbl', 'VendAddressUID', 'VendorUID');
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
        if ($date === null || $date === '') {
            return TRUE;
        }
        $d = DateTime::createFromFormat('Y-m-d', $date);
        if ($d && $d->format('Y-m-d') === $date) {
            return TRUE;
        } else {
            $this->form_validation->set_message('validateDateFormat', 'The {field} must be a valid date in YYYY-MM-DD format.');
            return FALSE;
        }
    }

}
