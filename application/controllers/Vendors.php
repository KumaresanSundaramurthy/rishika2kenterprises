<?php defined('BASEPATH') or exit('No direct script access allowed');

class Vendors extends CI_Controller
{

    public $pageData = array();
    private $EndReturnData;

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $this->load->view('vendors/view', $this->pageData);
    }

    public function getVendorsDetails($pageNo = 0)
    {

        $this->EndReturnData = new stdClass();
        try {

            $limit = $this->input->post('RowLimit');
            $offset = ($pageNo != 0) ? (($pageNo - 1) * $limit) : $pageNo;
            $Filter = $this->input->post('Filter');

            $this->load->model('vendors_model');
            $this->pageData['VendorsList'] = $this->vendors_model->getVendorsList($limit, $offset, $Filter, 0);
            $VendorsCount = $this->vendors_model->getVendorsList($limit, $offset, $Filter, 1);

            $config['base_url'] = '/vendors/getVendorsDetails/';
            $config['use_page_numbers'] = TRUE;
            $config['total_rows'] = $VendorsCount;
            $config['per_page'] = $limit;

            $config['result_count'] = pageResultCount($pageNo, $limit, $VendorsCount);
            $this->pagination->initialize($config);

            $this->EndReturnData->Error = false;
            $this->EndReturnData->List = $this->load->view('vendors/list', $this->pageData, TRUE);
            $this->EndReturnData->VendorsCount = $VendorsCount;
            $this->EndReturnData->Pagination = $this->pagination->create_links();
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

    public function add()
    {

        $this->load->model('global_model');

        $this->pageData['CountryInfo'] = [];
        $GetCountryInfo = $this->global_model->getCountryInfo();
        if ($GetCountryInfo->Error === FALSE) {
            $this->pageData['CountryInfo'] = $GetCountryInfo->Data;
        }

        $this->pageData['StateData'] = [];
        $this->pageData['CityData'] = [];

        $StateInfo = $this->global_model->getStateofCountry('IN');
        if ($StateInfo->Error === FALSE) {
            $this->pageData['StateData'] = $StateInfo->Data;
        }

        $CityInfo = $this->global_model->getCityofCountry('IN');
        if ($CityInfo->Error === FALSE) {
            $this->pageData['CityData'] = $CityInfo->Data;
        }

        $this->load->view('vendors/forms/add', $this->pageData);
    }

    public function addVendorData()
    {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('formvalidation_model');

            $PostData = $this->input->post();
            $ErrorInForm = $this->formvalidation_model->vendorValidateForm($PostData);
            if (empty($ErrorInForm)) {

                $this->load->model('dbwrite_model');

                $VendorUID = 0;
                $vendorFormData = [
                    'Name' => $PostData['Name'],
                    'Area' => $PostData['Area'] ? $PostData['Area'] : '',
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
                    'DebitLimit' => isset($PostData['DebitLimit']) ? $PostData['DebitLimit'] : 0,
                    'Notes' => (isset($PostData['Notes']) && !empty($PostData['Notes'])) ? implode(',', $PostData['Notes']) : NULL,
                    'Tags' => (isset($PostData['Tags']) && !empty($PostData['Tags'])) ? implode(',', $PostData['Tags']) : NULL,
                    'CCEmails' => (isset($PostData['CCEmails']) && !empty($PostData['CCEmails'])) ? $PostData['CCEmails'] : NULL,
                    'CreatedBy' => $this->pageData['JwtData']->User->UserUID,
                    'UpdatedBy' => $this->pageData['JwtData']->User->UserUID,
                    'CreatedOn' => time(),
                    'UpdatedOn' => time(),
                ];

                $InsertDataResp = $this->dbwrite_model->insertData('Vendors', 'VendorTbl', $vendorFormData);
                if ($InsertDataResp->Error) {
                    throw new Exception($InsertDataResp->Message);
                } else {
                    $VendorUID = $InsertDataResp->ID;
                }

                // Image Upload
                $imagePath = NULL;
                if ($PostData['imageChange'] == 1) {

                    if (isset($_FILES['UploadImage']['tmp_name']) && !empty($_FILES['UploadImage']['tmp_name'])) {

                        $ext = pathinfo($_FILES['UploadImage']['name'], PATHINFO_EXTENSION);
                        $fileName = substr(str_replace('.' . $ext, '', str_replace(' ', '_', $_FILES['UploadImage']['name'])), 0, 50) . '_' . uniqid() . '.' . $ext;
                        $imagePath = $this->imageUpload($_FILES['UploadImage']['tmp_name'], $fileName);
                    }

                    if ($imagePath) {
                        $updateVendorImgData = [
                            'Image' => $imagePath,
                        ];
                        $UpdateImgResp = $this->dbwrite_model->updateData('Vendors', 'VendorTbl', $updateVendorImgData, array('VendorUID' => $VendorUID));
                        if ($UpdateImgResp->Error) {
                            throw new Exception($UpdateImgResp->Message);
                        }
                    }
                }

                if (isset($PostData['addressData']) && $PostData['addressData'] == 1) {
                    $BillingAddressData = [
                        'VendorUID' => $VendorUID,
                        'OrgUID' => $this->pageData['JwtData']->User->OrgUID,
                        'Line1' => $PostData['BillAddrLine1'] ? $PostData['BillAddrLine1'] : NULL,
                        'Line2' => $PostData['BillAddrLine2'] ? $PostData['BillAddrLine2'] : NULL,
                        'Pincode' => $PostData['BillAddrPincode'] ? $PostData['BillAddrPincode'] : NULL,
                        'City' => $PostData['BillAddrCity'] ? $PostData['BillAddrCity'] : NULL,
                        'CityText' => $PostData['BillAddrCityText'] ? $PostData['BillAddrCityText'] : NULL,
                        'State' => $PostData['BillAddrState'] ? $PostData['BillAddrState'] : NULL,
                        'StateText' => $PostData['BillAddrStateText'] ? $PostData['BillAddrStateText'] : NULL,
                        'CreatedBy' => $this->pageData['JwtData']->User->UserUID,
                        'UpdatedBy' => $this->pageData['JwtData']->User->UserUID,
                        'CreatedOn' => time(),
                        'UpdatedOn' => time(),
                    ];

                    $InsertBillAddrResp = $this->dbwrite_model->insertData('Vendors', 'VendAddressTbl', $BillingAddressData);
                    if ($InsertBillAddrResp->Error) {
                        throw new Exception($InsertBillAddrResp->Message);
                    }
                }

                if (isset($PostData['CustomerLinkingCheck']) && !empty($PostData['CustomerLinkingCheck']) && $PostData['CustomerLinkingCheck'] == 'NewCustomer') {

                    $customerFormData = [
                        'Name' => $PostData['Name'],
                        'Area' => $PostData['Area'] ? $PostData['Area'] : '',
                        'OrgUID' => $this->pageData['JwtData']->User->OrgUID,
                        'CountryCode' => $PostData['CountryCode'],
                        'CountryISO2' => isset($PostData['CountryISO2']) ? $PostData['CountryISO2'] : 'IN',
                        'MobileNumber' => (isset($PostData['MobileNumber']) && !empty($PostData['MobileNumber'])) ? $PostData['MobileNumber'] : NULL,
                        'EmailAddress' => (isset($PostData['EmailAddress']) && !empty($PostData['EmailAddress'])) ? $PostData['EmailAddress'] : NULL,
                        'GSTIN' => (isset($PostData['GSTIN']) && !empty($PostData['GSTIN'])) ? $PostData['GSTIN'] : NULL,
                        'CompanyName' => (isset($PostData['CompanyName']) && !empty($PostData['CompanyName'])) ? $PostData['CompanyName'] : NULL,
                        'DebitCreditType' => isset($PostData['DebitCreditCheck']) ? $PostData['DebitCreditCheck'] : 'Debit',
                        'DebitCreditAmount' => isset($PostData['DebitCreditAmount']) ? $PostData['DebitCreditAmount'] : 0,
                        'Image' => ($PostData['imageChange'] == 1) ? $imagePath : NULL,
                        'PANNumber' => (isset($PostData['PANNumber']) && !empty($PostData['PANNumber'])) ? $PostData['PANNumber'] : NULL,
                        'CreditLimit' => isset($PostData['DebitLimit']) ? $PostData['DebitLimit'] : 0,
                        'Notes' => (isset($PostData['Notes']) && !empty($PostData['Notes'])) ? implode(',', $PostData['Notes']) : NULL,
                        'Tags' => (isset($PostData['Tags']) && !empty($PostData['Tags'])) ? implode(',', $PostData['Tags']) : NULL,
                        'CCEmails' => (isset($PostData['CCEmails']) && !empty($PostData['CCEmails'])) ? $PostData['CCEmails'] : NULL,
                        'CreatedBy' => $this->pageData['JwtData']->User->UserUID,
                        'UpdatedBy' => $this->pageData['JwtData']->User->UserUID,
                        'CreatedOn' => time(),
                        'UpdatedOn' => time(),
                    ];

                    $InsertDataResp = $this->dbwrite_model->insertData('Customers', 'CustomerTbl', $customerFormData);
                    if ($InsertDataResp->Error) {
                        throw new Exception($InsertDataResp->Message);
                    }
                    $CustomerUID = $InsertDataResp->ID;

                    if (isset($PostData['addressData']) && $PostData['addressData'] == 1) {

                        $BillingAddressData = [
                            'CustomerUID' => $CustomerUID,
                            'OrgUID' => $this->pageData['JwtData']->User->OrgUID,
                            'AddressType' => 'Billing',
                            'Line1' => $PostData['BillAddrLine1'] ? $PostData['BillAddrLine1'] : NULL,
                            'Line2' => $PostData['BillAddrLine2'] ? $PostData['BillAddrLine2'] : NULL,
                            'Pincode' => $PostData['BillAddrPincode'] ? $PostData['BillAddrPincode'] : NULL,
                            'City' => $PostData['BillAddrCity'] ? $PostData['BillAddrCity'] : NULL,
                            'CityText' => $PostData['BillAddrCityText'] ? $PostData['BillAddrCityText'] : NULL,
                            'State' => $PostData['BillAddrState'] ? $PostData['BillAddrState'] : NULL,
                            'StateText' => $PostData['BillAddrStateText'] ? $PostData['BillAddrStateText'] : NULL,
                            'CreatedBy' => $this->pageData['JwtData']->User->UserUID,
                            'UpdatedBy' => $this->pageData['JwtData']->User->UserUID,
                            'CreatedOn' => time(),
                            'UpdatedOn' => time(),
                        ];

                        $InsertBillAddrResp = $this->dbwrite_model->insertData('Customers', 'CustAddressTbl', $BillingAddressData);
                        if ($InsertBillAddrResp->Error) {
                            throw new Exception($InsertBillAddrResp->Message);
                        }
                    }

                    // Update Customer UID in Vendor Table
                    if ($CustomerUID) {
                        $updateVendorData = [
                            'CustomerUID' => $CustomerUID,
                        ];
                        $UpdateResp = $this->dbwrite_model->updateData('Vendors', 'VendorTbl', $updateVendorData, array('VendorUID' => $VendorUID));
                        if ($UpdateResp->Error) {
                            throw new Exception($UpdateResp->Message);
                        }
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

    private function imageUpload($tempName, $fullPath)
    {

        $uploadPath = 'vendors/images/' . $fullPath;

        $this->load->library('fileupload');
        $uploadDetail = $this->fileupload->fileUpload('file', $uploadPath, $tempName);

        if ($uploadDetail->Error === false) {
            return '/' . $uploadDetail->Path;
        } else {
            throw new Exception('File upload failed');
        }
    }

    public function checkImageType()
    {

        $allowed = array('image/jpeg', 'image/jpg', 'image/png');
        $type_not_match = false;
        if (isset($_FILES['Thumbnail']['name']) && !empty($_FILES['Thumbnail']['name'])) {
            if (!in_array($_FILES['Thumbnail']['type'], $allowed) || $_FILES['Thumbnail']['size'] > 1048576) {
                $type_not_match = true;
            }
        }
        if ($type_not_match) {
            $this->form_validation->set_message('checkImageType', 'Invalid File. Please upload allowed format and size will be below 1MB');
            return false;
        } else {
            return true;
        }
    }

    public function customerCheck()
    {

        $Linking = $this->input->post('CustomerLinkingCheck');
        $Customers = $this->input->post('Customers') ? $this->input->post('Customers') : NULL;
        if ($Linking === 'OldCustomer' && empty($Customers)) {

            $this->form_validation->set_message('customerCheck', 'The Customers field is required for Old Customers.');
            return FALSE;
        }

        return TRUE;
    }

    public function edit($VendorUID)
    {

        $VendorUID = (int) $VendorUID;
        if ($VendorUID > 0) {

            $this->load->model('vendors_model');
            $GetVendorData = $this->vendors_model->getVendors(['Vendors.VendorUID' => $VendorUID]);
            if ((sizeof($GetVendorData) > 0) && sizeof($GetVendorData) == 1) {

                $this->load->model('global_model');
                $GetCountryInfo = $this->global_model->getCountryInfo();
                if ($GetCountryInfo->Error === FALSE) {
                    $this->pageData['CountryInfo'] = $GetCountryInfo->Data;
                }

                $this->pageData['EditData'] = $GetVendorData[0];
                $this->pageData['BillingAddr'] = $this->vendors_model->getVendorAddress(['VendAddress.VendorUID' => $GetVendorData[0]->VendorUID]);

                $this->pageData['StateData'] = [];
                $this->pageData['CityData'] = [];

                $StateInfo = $this->global_model->getStateofCountry($GetVendorData[0]->CountryISO2);
                if ($StateInfo->Error === FALSE) {
                    $this->pageData['StateData'] = $StateInfo->Data;
                }

                $CityInfo = $this->global_model->getCityofCountry($GetVendorData[0]->CountryISO2);
                if ($CityInfo->Error === FALSE) {
                    $this->pageData['CityData'] = $CityInfo->Data;
                }

                $this->load->view('vendors/forms/edit', $this->pageData);
            } else {
                redirect('vendors', 'refresh');
            }
        } else {
            redirect('vendors', 'refresh');
        }
    }

    public function updateVendorData()
    {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('formvalidation_model');

            $PostData = $this->input->post();
            $ErrorInForm = $this->formvalidation_model->vendorValidateForm($PostData);
            if (empty($ErrorInForm)) {

                $this->load->model('dbwrite_model');

                $VendorUID = $PostData['VendorUID'];

                $vendorFormData = [
                    'Name' => $PostData['Name'],
                    'Area' => $PostData['Area'] ? $PostData['Area'] : '',
                    'CountryCode' => $PostData['CountryCode'],
                    'CountryISO2' => isset($PostData['CountryISO2']) ? $PostData['CountryISO2'] : 'IN',
                    'MobileNumber' => (isset($PostData['MobileNumber']) && !empty($PostData['MobileNumber'])) ? $PostData['MobileNumber'] : NULL,
                    'EmailAddress' => (isset($PostData['EmailAddress']) && !empty($PostData['EmailAddress'])) ? $PostData['EmailAddress'] : NULL,
                    'GSTIN' => (isset($PostData['GSTIN']) && !empty($PostData['GSTIN'])) ? $PostData['GSTIN'] : NULL,
                    'CompanyName' => (isset($PostData['CompanyName']) && !empty($PostData['CompanyName'])) ? $PostData['CompanyName'] : NULL,
                    'DebitCreditType' => isset($PostData['DebitCreditCheck']) ? $PostData['DebitCreditCheck'] : 'Debit',
                    'DebitCreditAmount' => isset($PostData['DebitCreditAmount']) ? $PostData['DebitCreditAmount'] : 0,
                    'PANNumber' => (isset($PostData['PANNumber']) && !empty($PostData['PANNumber'])) ? $PostData['PANNumber'] : NULL,
                    'DebitLimit' => isset($PostData['DebitLimit']) ? $PostData['DebitLimit'] : 0,
                    'Notes' => (isset($PostData['Notes']) && !empty($PostData['Notes'])) ? implode(',', $PostData['Notes']) : NULL,
                    'Tags' => (isset($PostData['Tags']) && !empty($PostData['Tags'])) ? implode(',', $PostData['Tags']) : NULL,
                    'CCEmails' => (isset($PostData['CCEmails']) && !empty($PostData['CCEmails'])) ? $PostData['CCEmails'] : NULL,
                    'UpdatedBy' => $this->pageData['JwtData']->User->UserUID,
                    'UpdatedOn' => time(),
                ];

                $UpdateDataResp = $this->dbwrite_model->updateData('Vendors', 'VendorTbl', $vendorFormData, array('VendorUID' => $VendorUID));
                if ($UpdateDataResp->Error) {
                    throw new Exception($UpdateDataResp->Message);
                }

                // Image Upload
                $imagePath = NULL;
                if ($PostData['imageChange'] == 1) {

                    if (isset($_FILES['UploadImage']['tmp_name']) && !empty($_FILES['UploadImage']['tmp_name'])) {

                        $ext = pathinfo($_FILES['UploadImage']['name'], PATHINFO_EXTENSION);
                        $fileName = substr(str_replace('.' . $ext, '', str_replace(' ', '_', $_FILES['UploadImage']['name'])), 0, 50) . '_' . uniqid() . '.' . $ext;
                        $imagePath = $this->imageUpload($_FILES['UploadImage']['tmp_name'], $fileName);
                    }

                    if ($imagePath) {
                        $updateVendorImgData = [
                            'Image' => $imagePath,
                        ];
                        $UpdateImgResp = $this->dbwrite_model->updateData('Vendors', 'VendorTbl', $updateVendorImgData, array('VendorUID' => $VendorUID));
                        if ($UpdateImgResp->Error) {
                            throw new Exception($UpdateImgResp->Message);
                        }
                    }
                }

                if ((isset($PostData['addressData']) && $PostData['addressData'] == 1) || (isset($PostData['VendAddressUID']) && $PostData['VendAddressUID'] > 0)) {

                    $BillingAddressData = [
                        'VendorUID' => $VendorUID,
                        'OrgUID' => $this->pageData['JwtData']->User->OrgUID,
                        'Line1' => $PostData['BillAddrLine1'] ? $PostData['BillAddrLine1'] : NULL,
                        'Line2' => $PostData['BillAddrLine2'] ? $PostData['BillAddrLine2'] : NULL,
                        'Pincode' => $PostData['BillAddrPincode'] ? $PostData['BillAddrPincode'] : NULL,
                        'City' => $PostData['BillAddrCity'] ? $PostData['BillAddrCity'] : NULL,
                        'CityText' => $PostData['BillAddrCityText'] ? $PostData['BillAddrCityText'] : NULL,
                        'State' => $PostData['BillAddrState'] ? $PostData['BillAddrState'] : NULL,
                        'StateText' => $PostData['BillAddrStateText'] ? $PostData['BillAddrStateText'] : NULL,
                        'UpdatedBy' => $this->pageData['JwtData']->User->UserUID,
                        'UpdatedOn' => time(),
                    ];

                    if ($PostData['VendAddressUID'] == 0) {
                        $BillingAddressData['CreatedBy'] = $this->pageData['JwtData']->User->UserUID;
                        $BillingAddressData['CreatedOn'] = time();
                        $InsertBillAddrResp = $this->dbwrite_model->insertData('Vendors', 'VendAddressTbl', $BillingAddressData);
                        if ($InsertBillAddrResp->Error) {
                            throw new Exception($InsertBillAddrResp->Message);
                        }
                    } else {
                        $UpdateBillAddrResp = $this->dbwrite_model->updateData('Vendors', 'VendAddressTbl', $BillingAddressData, array('VendAddressUID' => $PostData['VendAddressUID']));
                        if ($UpdateBillAddrResp->Error) {
                            throw new Exception($UpdateBillAddrResp->Message);
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
}
