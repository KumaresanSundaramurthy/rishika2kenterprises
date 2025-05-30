<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Customers extends CI_Controller {

    public $pageData = array();
    private $EndReturnData;

    public function __construct() {
        parent::__construct();

    }

    public function index() {
        $this->load->view('customers/view', $this->pageData);
    }

    public function getCustomersDetails($pageNo = 0) {

		$this->EndReturnData = new stdClass();
		try {

			$limit = $this->input->post('RowLimit');
            $offset = ($pageNo != 0) ? (($pageNo - 1) * $limit) : $pageNo;
            $Filter = $this->input->post('Filter');

			$this->load->model('customers_model');
            $this->pageData['CustomersList'] = $this->customers_model->getCustomersList($limit, $offset, $Filter, 0);
            $CustomersCount = $this->customers_model->getCustomersList($limit, $offset, $Filter, 1);

			$config['base_url'] = '/customers/getCustomersDetails/';
            $config['use_page_numbers'] = TRUE;
            $config['total_rows'] = $CustomersCount;
            $config['per_page'] = $limit;

            $config['result_count'] = pageResultCount($pageNo, $limit, $CustomersCount);
            $this->pagination->initialize($config);

            $this->EndReturnData->Error = false;
            $this->EndReturnData->List = $this->load->view('customers/list', $this->pageData, TRUE);
			$this->EndReturnData->CustomersCount = $CustomersCount;
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
                    'OrgUID' => $this->pageData['JwtData']->User->OrgUID,
                    'CountryCode' => $PostData['CountryCode'],
                    'CountryISO2' => isset($PostData['CountryISO2']) ? $PostData['CountryISO2'] : 'IN',
                    'MobileNumber' => isset($PostData['MobileNumber']) ? $PostData['MobileNumber'] : null,
                    'EmailAddress' => isset($PostData['EmailAddress']) ? $PostData['EmailAddress'] : null,
                    'GSTIN' => isset($PostData['GSTIN']) ? $PostData['GSTIN'] : null,
                    'CompanyName' => isset($PostData['CompanyName']) ? $PostData['CompanyName'] : null,
                    'DebitCreditType' => isset($PostData['DebitCreditCheck']) ? $PostData['DebitCreditCheck'] : 'Debit',
                    'DebitCreditAmount' => isset($PostData['DebitCreditAmount']) ? $PostData['DebitCreditAmount'] : 0,
                    'PANNumber' => isset($PostData['PANNumber']) ? $PostData['PANNumber'] : null,
                    'DiscountPercent' => isset($PostData['DiscountPercent']) ? $PostData['DiscountPercent'] : 0,
                    'CreditLimit' => isset($PostData['CreditLimit']) ? $PostData['CreditLimit'] : 0,
                    'Notes' => isset($PostData['Notes']) ? $PostData['Notes'] : null,
                    'Tags' => isset($PostData['Tags']) ? $PostData['Tags'] : null,
                    'CCEmails' => isset($PostData['CCEmails']) ? $PostData['CCEmails'] : null,
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
                if($PostData['imageChange'] == 1) {

                    $imagePath = NULL;

                    if (isset($_FILES['UploadImage']['tmp_name']) && !empty($_FILES['UploadImage']['tmp_name'])) {

                        $ext = pathinfo($_FILES['UploadImage']['name'], PATHINFO_EXTENSION);
                        $fileName = substr(str_replace('.'.$ext, '', str_replace(' ', '_', $_FILES['UploadImage']['name'])), 0, 50).'_'.uniqid().'.'.$ext;
                        $imagePath = $this->imageUpload($_FILES['UploadImage']['tmp_name'], $fileName);

                    }

                    if($imagePath) {
                        $updateCustImgData = [
                            'Image' => $imagePath,
                        ];
                        $UpdateImgResp = $this->dbwrite_model->updateData('Customers', 'CustomerTbl', $updateCustImgData, array('CustomerUID' => $CustomerUID));
                        if($UpdateImgResp->Error) {
                            throw new Exception($UpdateImgResp->Message);
                        }
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

    private function imageUpload($tempName, $fullPath) {

        $uploadPath = 'org/images/' . $fullPath;

        $this->load->library('fileupload');
        $uploadDetail = $this->fileupload->fileUpload('file', $uploadPath, $tempName);

        if ($uploadDetail->Error === false) {
			return '/'.$uploadDetail->Path;
        } else {
            throw new Exception('File upload failed');
        }

    }

    public function checkImageType() {

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

                $this->load->view('customers/forms/edit', $this->pageData);

            } else {
                redirect('customers');
            }

        } else {
			redirect('customers');
		}

    }

}