<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Organisation extends CI_Controller {

    public $pageData = array();
    private $EndReturnData;

    public function __construct() {
        parent::__construct();

    }

    public function index() {
        
        $this->load->model('global_model');
        $GetCountryInfo = $this->global_model->getCountryInfo();
        $this->pageData['CountryInfo'] = $GetCountryInfo->Error === FALSE ? $GetCountryInfo->Data : [];
        
        $this->load->model('organisation_model');
        $OrgBussTypeData = $this->organisation_model->getOrgBusinessTypeDetails();
        $this->pageData['OrgBussType'] = $OrgBussTypeData->Error === FALSE ? $OrgBussTypeData->Data : [];
        
        $OrgIndusTypeData = $this->organisation_model->getOrgIndustryTypeDetails();
        $this->pageData['OrgIndusType'] = $OrgIndusTypeData->Error === FALSE ? $OrgIndusTypeData->Data : [];
        
        $OrgBusRegData = $this->organisation_model->getOrgBusRegTypeDetails();
        $this->pageData['OrgBusRegType'] = $OrgBusRegData->Error === FALSE ? $OrgBusRegData->Data : [];
        
        $this->pageData['EditOrgData'] = null;
        $this->pageData['BillOrgAddrData'] = null;
        $this->pageData['ShipOrgAddrData'] = null;

        $this->pageData['StateData'] = [];
        $this->pageData['CityData'] = [];

        $OrganisationData = $this->organisation_model->getAllOrganisationAddressDetails(['Org.OrgUID' => $this->pageData['JwtData']->User->OrgUID]);
        if ($OrganisationData->Error === FALSE && !empty($OrganisationData->Data)) {

            $orgRow = $OrganisationData->Data[0];

            $this->pageData['EditOrgData'] = $orgRow;
            $this->pageData['BillOrgAddrData'] = mapOrganisationAddress($orgRow, 'B', 'Billing') ?? null;
            $this->pageData['ShipOrgAddrData'] = mapOrganisationAddress($orgRow, 'S', 'Shipping') ?? null;

            if(!empty($orgRow->CountryISO2)) {
                $StateInfo = $this->global_model->getStateofCountry($orgRow->CountryISO2);
                if($StateInfo->Error === FALSE) {
                    $this->pageData['StateData'] = $StateInfo->Data;
                }
                $CityInfo = $this->global_model->getCityofCountry($orgRow->CountryISO2);
                if($CityInfo->Error === FALSE) {
                    $this->pageData['CityData'] = $CityInfo->Data;
                }
            }

        }

        $TimezoneInfo = $this->global_model->getTimezoneDetails([]);
        $this->pageData['TimezoneInfo'] = $TimezoneInfo->Error === FALSE ? $TimezoneInfo->Data : [];
        
        $this->pageData['JwtData']->GenSettings = $this->session->userdata('CachedUserGenSettings');
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
                    'OrgIndTypeUID' => $PostData['OrgIndusTypeUID'] ? $PostData['OrgIndusTypeUID'] : NULL,
                    'OrgBusRegTypeUID' => $PostData['OrgBusRegTypeUID'] ? $PostData['OrgBusRegTypeUID'] : NULL,
                    'AlternateNumber' => $PostData['AlternateNumber'] ? $PostData['AlternateNumber'] : null,
                    'Website' => $PostData['Website'] ? $PostData['Website'] : null,
                    'PANNumber' => $PostData['PANNumber'] ? $PostData['PANNumber'] : null,
                    'TimezoneUID' => $PostData['TimezoneUID'] ? $PostData['TimezoneUID'] : null,
                    'UpdatedBy' => $this->pageData['JwtData']->User->UserUID,
                    'UpdatedOn' => time(),
                ];

                $UpdateDataResp = $this->dbwrite_model->updateData('Organisation', 'OrganisationTbl', $updateOrgData, array('OrgUID' => $PostData['OrgUID']));

                if($UpdateDataResp->Error === FALSE) {

                    // Image Upload
                    if(isset($_FILES['UploadImage'])) {
                        $UploadResp = $this->globalservice->fileUploadService($_FILES['UploadImage'], 'org/images/', 'Logo', ['Organisation', 'OrganisationTbl', array('OrgUID' => $PostData['OrgUID'])]);
                        if($UploadResp->Error === TRUE) {
                            throw new Exception($UploadResp->Message);
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

    public function checkImageType($str = '') {
        return $this->globalservice->checkImageType($str);
    }

}