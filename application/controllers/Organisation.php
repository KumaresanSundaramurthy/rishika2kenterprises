<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Organisation extends CI_Controller {

    public $pageData = array();
    private $EndReturnData;

    public function __construct() {
        parent::__construct();

    }

    public function index() {
        
        $this->load->library('curlservice');

        $Countries = [];
        $this->load->model('global_model');
        $GetCountryInfo = $this->global_model->getCountryInfo();
        if($GetCountryInfo->Error === FALSE) {
            $Countries = $GetCountryInfo->Data;
        }

        $this->load->model('organisation_model');
        $this->pageData['OrgBussType'] = [];
        $OrgBussTypeData = $this->organisation_model->getOrgBusinessTypeDetails();
        if($OrgBussTypeData->Error === FALSE) {
            $this->pageData['OrgBussType'] = $OrgBussTypeData->Data;
        }

        $this->pageData['EditOrgData'] = [];
        $this->pageData['BillOrgAddrData'] = [];
        $this->pageData['ShipOrgAddrData'] = [];

        $this->pageData['StateData'] = [];
        $this->pageData['CityData'] = [];

        $OrganisationData = $this->organisation_model->getOrganisationDetails(['Org.OrgUID' => $this->pageData['JwtData']->User->OrgUID]);
        if($OrganisationData->Error === FALSE) {

            $this->pageData['EditOrgData'] = $OrganisationData->Data[0];

            $OrgBillAddrData = $this->organisation_model->getOrgAddressDetails(['Addr.OrgUID' => $this->pageData['JwtData']->User->OrgUID, 'Addr.AddressType' => 'Billing']);
            if($OrgBillAddrData->Error === FALSE && (sizeof($OrgBillAddrData->Data) > 0)) {
                $this->pageData['BillOrgAddrData'] = $OrgBillAddrData->Data[0];
            }

            $OrgShipAddrData = $this->organisation_model->getOrgAddressDetails(['Addr.OrgUID' => $this->pageData['JwtData']->User->OrgUID, 'Addr.AddressType' => 'Shipping']);
            if($OrgBillAddrData->Error === FALSE && (sizeof($OrgShipAddrData->Data) > 0)) {
                $this->pageData['ShipOrgAddrData'] = $OrgShipAddrData->Data[0];
            }

            if(!empty($OrganisationData->Data[0]->CountryISO2)) {

                $StateInfo = $this->global_model->getStateofCountry($OrganisationData->Data[0]->CountryISO2);
                if($StateInfo->Error === FALSE) {
                    $this->pageData['StateData'] = $StateInfo->Data;
                }

                $CityInfo = $this->global_model->getCityofCountry($OrganisationData->Data[0]->CountryISO2);
                if($CityInfo->Error === FALSE) {
                    $this->pageData['CityData'] = $CityInfo->Data;
                }

            }

        }

        $this->pageData['CountryInfo'] = $Countries;

        $this->load->model('global_model');
        $this->pageData['TimezoneInfo'] = [];
        $TimezoneInfo = $this->global_model->getTimezoneDetails([]);
        if($TimezoneInfo->Error === FALSE) {
            $this->pageData['TimezoneInfo'] = $TimezoneInfo->Data;
        }
        
        $this->load->view('organisation/view', $this->pageData);

    }

    public function updateOrgForm() {

        $this->EndReturnData = new stdClass();
		try {

            $this->load->model('formvalidation_model');

            $PostData = $this->input->post();
            $ErrorInForm = $this->formvalidation_model->orgValidateForm($PostData);
            if(empty($ErrorInForm)) {

                $this->load->model('dbwrite_model');

                $updateOrgData = [
                    'Name' => $PostData['Name'],
                    'ShortDescription' => $PostData['Description'] ? $PostData['Description'] : null,
                    'BrandName' => $PostData['BrandName'],
                    'CountryCode' => $PostData['CountryCode'],
                    'CountryISO2' => $PostData['CountryISO2'],
                    'MobileNumber' => $PostData['MobileNumber'] ? $PostData['MobileNumber'] : null,
                    'GSTIN' => $PostData['GSTIN'] ? $PostData['GSTIN'] : null,
                    'OrgBussTypeUID' => $PostData['OrgBussTypeUID'],
                    'AlternateNumber' => $PostData['AlternateNumber'] ? $PostData['AlternateNumber'] : null,
                    'Website' => $PostData['Website'] ? $PostData['Website'] : null,
                    'PANNumber' => $PostData['PANNumber'] ? $PostData['PANNumber'] : null,
                    'TimezoneUID' => $PostData['TimezoneUID'] ? $PostData['TimezoneUID'] : null,
                    'UpdatedBy' => $this->pageData['JwtData']->User->UserUID,
                    'UpdatedOn' => time(),
                ];

                $UpdateDataResp = $this->dbwrite_model->updateData('Organisation', 'OrganisationTbl', $updateOrgData, array('OrgUID' => $PostData['OrgUID']));

                if($UpdateDataResp->Error === FALSE) {

                    if($PostData['imageChange'] == 1) {

                        $imagePath = NULL;

                        if (isset($_FILES['UploadImage']['tmp_name']) && !empty($_FILES['UploadImage']['tmp_name'])) {

                            $ext = pathinfo($_FILES['UploadImage']['name'], PATHINFO_EXTENSION);
                            $fileName = substr(str_replace('.'.$ext, '', str_replace(' ', '_', $_FILES['UploadImage']['name'])), 0, 50).'_'.uniqid().'.'.$ext;
                            $imagePath = $this->imageUpload($_FILES['UploadImage']['tmp_name'], $fileName);

                        }

                        if($imagePath) {
                            $updateOrgImgData = [
                                'Logo' => $imagePath,
                            ];
                            $UpdateImgResp = $this->dbwrite_model->updateData('Organisation', 'OrganisationTbl', $updateOrgImgData, array('OrgUID' => $PostData['OrgUID']));
                            if($UpdateImgResp->Error) {
                                throw new Exception($UpdateImgResp->Message);
                            }
                        }                        

                    }
                    
                    $BillAddrInstUpdt = 0;
                    if($PostData['BillOrgAddressUID'] > 0) {
                        $BillAddrInstUpdt = 1;
                    }

                    $BillingAddressData = [
                        'OrgUID' => $PostData['OrgUID'],
                        'Line1' => $PostData['BillAddrLine1'],
                        'Line2' => $PostData['BillAddrLine2'] ? $PostData['BillAddrLine2'] : null,
                        'Pincode' => $PostData['BillAddrPincode'],
                        'City' => $PostData['BillAddrCity'] ? $PostData['BillAddrCity'] : null,
                        'CityText' => $PostData['BillAddrCityText'] ? $PostData['BillAddrCityText'] : null,
                        'State' => $PostData['BillAddrState'] ? $PostData['BillAddrState'] : null,
                        'StateText' => $PostData['BillAddrStateText'] ? $PostData['BillAddrStateText'] : null,
                        'UpdatedBy' => $this->pageData['JwtData']->User->UserUID,
                        'UpdatedOn' => time(),
                    ];
                    if($BillAddrInstUpdt == 0) {

                        $BillingAddressData['AddressType'] = 'Billing';
                        $BillingAddressData['CreatedOn'] = time();
                        $BillingAddressData['CreatedBy'] = $this->pageData['JwtData']->User->UserUID;

                        $InsertBillAddrResp = $this->dbwrite_model->insertData('Organisation', 'OrgAddressTbl', $BillingAddressData);
                        if($InsertBillAddrResp->Error) {
                            throw new Exception($InsertBillAddrResp->Message);
                        } else {
                            $PostData['BillOrgAddressUID'] = $InsertBillAddrResp->ID;
                        }

                    } else {

                        $UpdateBillAddrResp = $this->dbwrite_model->updateData('Organisation', 'OrgAddressTbl', $BillingAddressData, array('OrgAddressUID' => $PostData['BillOrgAddressUID']));
                        if($UpdateBillAddrResp->Error) {
                            throw new Exception($UpdateBillAddrResp->Message);
                        }

                    }

                    $ShipAddrInstUpdt = 0;
                    if($PostData['ShipOrgAddressUID'] > 0) {
                        $ShipAddrInstUpdt = 1;
                    }

                    $ShippingAddressData = [
                        'OrgUID' => $PostData['OrgUID'],
                        'Line1' => $PostData['ShipAddrLine1'],
                        'Line2' => $PostData['ShipAddrLine2'] ? $PostData['ShipAddrLine2'] : null,
                        'Pincode' => $PostData['ShipAddrPincode'],
                        'City' => $PostData['ShipAddrCity'] ? $PostData['ShipAddrCity'] : null,
                        'CityText' => $PostData['ShipAddrCityText'] ? $PostData['ShipAddrCityText'] : null,
                        'State' => $PostData['ShipAddrState'] ? $PostData['ShipAddrState'] : null,
                        'StateText' => $PostData['ShipAddrStateText'] ? $PostData['ShipAddrStateText'] : null,
                        'UpdatedBy' => $this->pageData['JwtData']->User->UserUID,
                        'UpdatedOn' => time(),
                    ];
                    if($ShipAddrInstUpdt == 0) {

                        $ShippingAddressData['AddressType'] = 'Shipping';
                        $ShippingAddressData['CreatedOn'] = time();
                        $ShippingAddressData['CreatedBy'] = $this->pageData['JwtData']->User->UserUID;
                        

                        $InsertShipAddrResp = $this->dbwrite_model->insertData('Organisation', 'OrgAddressTbl', $ShippingAddressData);
                        if($InsertShipAddrResp->Error) {
                            throw new Exception($InsertShipAddrResp->Message);
                        } else {
                            $PostData['ShipOrgAddressUID'] = $InsertShipAddrResp->ID;
                        }

                    } else {

                        $UpdateShipAddrResp = $this->dbwrite_model->updateData('Organisation', 'OrgAddressTbl', $ShippingAddressData, array('OrgAddressUID' => $PostData['ShipOrgAddressUID']));
                        if($UpdateShipAddrResp->Error) {
                            throw new Exception($UpdateShipAddrResp->Message);
                        }

                    }

                    if($PostData['imageChange'] == 1 || $PostData['countryChange'] == 1) {

                        $GetRedisDetails = $this->cacheservice->get($this->pageData['JwtUserKey']);
                        if($GetRedisDetails->Error === false) {

                            $this->load->model('user_model');
                            $UserData = $this->user_model->getUserByUserInfo(array('User.UserUID' => $this->pageData['JwtData']->User->UserUID));

                            if($UserData->Error === FALSE && count($UserData->Data) > 0 && sizeof($UserData->Data) == 1) {

                                $this->load->model('login_model');
                                $jwtPayload = $this->login_model->formatJWTPayload($UserData->Data[0]);

                                if($jwtPayload->Error === false) {
                                    $this->cacheservice->set($GetRedisDetails->Key, json_encode($jwtPayload->JWTData), $GetRedisDetails->TTL);
                                }

                            }

                        }

                    }
                    
                    $this->EndReturnData->Error = FALSE;
                    $this->EndReturnData->Message = 'Updated Successfully';
                    $this->EndReturnData->BillOrgAddressUID = $PostData['BillOrgAddressUID'];
                    $this->EndReturnData->ShipOrgAddressUID = $PostData['ShipOrgAddressUID'];
                    
                } else {
                    throw new Exception($UpdateDataResp->Message);
                }

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

}