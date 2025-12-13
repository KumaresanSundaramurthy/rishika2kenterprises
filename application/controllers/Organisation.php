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
            $this->pageData['BillOrgAddrData'] = $this->mapOrganisationAddress($orgRow, 'B', 'Billing') ?? null;
            $this->pageData['ShipOrgAddrData'] = $this->mapOrganisationAddress($orgRow, 'S', 'Shipping') ?? null;

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
        
        $GeneralSettings = $this->redis_cache->get('Redis_UserGenSettings')->Value ?? new stdClass();
        $this->pageData['JwtData']->GenSettings = $GeneralSettings;
        $this->load->view('organisation/view', $this->pageData);

    }

    private function mapOrganisationAddress($orgData, $prefix, $type) {
        
        if (empty($orgData->{$prefix.'AddressUID'})) {
            return null;
        }

        return (object) [
            'OrgAddressUID' => $orgData->{$prefix.'AddressUID'},
            'OrgUID'        => $orgData->OrgUID,
            'AddressType'   => $type,
            'Line1'         => $orgData->{$prefix.'Line1'},
            'Line2'         => $orgData->{$prefix.'Line2'},
            'Pincode'       => $orgData->{$prefix.'Pincode'},
            'City'          => $orgData->{$prefix.'City'},
            'CityText'      => $orgData->{$prefix.'CityText'},
            'State'         => $orgData->{$prefix.'State'},
            'StateText'     => $orgData->{$prefix.'StateText'},
        ];
    }

    public function updateOrgForm() {

        $this->EndReturnData = new stdClass();
		try {

            $this->load->model('formvalidation_model');

            $PostData = $this->input->post();
            $ErrorInForm = $this->formvalidation_model->orgValidateForm($PostData);
            if (!empty($ErrorInForm)) {
                throw new Exception($ErrorInForm);
            }

            $userUID = $this->pageData['JwtData']->User->UserUID;
            $now     = time();

            $updateOrgData = [
                'Name'              => getPostValue($PostData, 'Name'),
                'ShortDescription'  => getPostValue($PostData, 'Description', 'Array', NULL, false),
                'BrandName'         => getPostValue($PostData, 'BrandName'),
                'CountryCode'       => getPostValue($PostData, 'CountryCode'),
                'CountryISO2'       => getPostValue($PostData, 'CountryISO2'),
                'MobileNumber'      => getPostValue($PostData, 'MobileNumber', 'Array', NULL, false),
                'GSTIN'             => getPostValue($PostData, 'GSTIN', 'Array', NULL, false),
                'OrgBussTypeUID'    => getPostValue($PostData, 'OrgBussTypeUID'),
                'OrgIndTypeUID'     => getPostValue($PostData, 'OrgIndusTypeUID', 'Array', NULL, false),
                'OrgBusRegTypeUID'  => getPostValue($PostData, 'OrgBusRegTypeUID', 'Array', NULL, false),
                'AlternateNumber'   => getPostValue($PostData, 'AlternateNumber', 'Array', NULL, false),
                'Website'           => getPostValue($PostData, 'Website', 'Array', NULL, false),
                'PANNumber'         => getPostValue($PostData, 'PANNumber', 'Array', NULL, false),
                'TimezoneUID'       => getPostValue($PostData, 'TimezoneUID', 'Array', NULL, false),
                'UpdatedBy'         => $userUID,
                'UpdatedOn'         => $now,
            ];

            $this->load->model('dbwrite_model');
            $UpdateDataResp = $this->dbwrite_model->updateData('Organisation', 'OrganisationTbl', $updateOrgData, array('OrgUID' => $PostData['OrgUID']));
            if ($UpdateDataResp->Error) {
                throw new Exception($UpdateDataResp->Message);
            }

            if(isset($_FILES['UploadImage'])) {
                $UploadResp = $this->globalservice->fileUploadService($_FILES['UploadImage'], 'org/images/', 'Logo', ['Organisation', 'OrganisationTbl', array('OrgUID' => $PostData['OrgUID'])]);
                if($UploadResp->Error === TRUE) {
                    throw new Exception($UploadResp->Message);
                }
            }

            $PostData['BillOrgAddressUID'] = $this->handleAddress($PostData, 'Billing', $userUID, $now);
            $PostData['ShipOrgAddressUID'] = $this->handleAddress($PostData, 'Shipping', $userUID, $now);

            if (!empty($PostData['imageChange']) || !empty($PostData['countryChange'])) {
                $this->refreshUserCache($userUID);
            }
            
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Message = 'Updated Successfully';
            $this->EndReturnData->BillOrgAddressUID = $PostData['BillOrgAddressUID'];
            $this->EndReturnData->ShipOrgAddressUID = $PostData['ShipOrgAddressUID'];

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

    private function handleAddress($PostData, $type, $userUID, $now) {

        $addrUIDField = $type === 'Billing' ? 'BillOrgAddressUID' : 'ShipOrgAddressUID';
        $prefix       = $type === 'Billing' ? 'BillAddr' : 'ShipAddr';

        $AddressData = [
            'OrgUID'    => $PostData['OrgUID'],
            'Line1'     => getPostValue($PostData, $prefix.'Line1'),
            'Line2'     => getPostValue($PostData, $prefix.'Line2'),
            'Pincode'   => getPostValue($PostData, $prefix.'Pincode'),
            'City'      => getPostValue($PostData, $prefix.'City'),
            'CityText'  => getPostValue($PostData, $prefix.'CityText'),
            'State'     => getPostValue($PostData, $prefix.'State'),
            'StateText' => getPostValue($PostData, $prefix.'StateText'),
            'UpdatedBy' => $userUID,
            'UpdatedOn' => $now,
        ];

        if (empty($PostData[$addrUIDField])) {
            $AddressData['AddressType'] = $type;
            $AddressData['CreatedOn']   = $now;
            $AddressData['CreatedBy']   = $userUID;

            $InsertResp = $this->dbwrite_model->insertData('Organisation', 'OrgAddressTbl', $AddressData);
            if ($InsertResp->Error) throw new Exception($InsertResp->Message);
            return $InsertResp->ID;
        } else {
            $UpdateResp = $this->dbwrite_model->updateData('Organisation', 'OrgAddressTbl', $AddressData, ['OrgAddressUID' => $PostData[$addrUIDField]]);
            if ($UpdateResp->Error) throw new Exception($UpdateResp->Message);
            return $PostData[$addrUIDField];
        }

    }

    private function refreshUserCache($userUID) {
        $GetRedisDetails = $this->cacheservice->get($this->pageData['JwtUserKey']);
        if ($GetRedisDetails->Error === FALSE) {
            $this->load->model('user_model');
            $UserData = $this->user_model->getUserByUserInfo(['User.UserUID' => $userUID]);

            if ($UserData->Error === FALSE && count($UserData->Data) === 1) {
                $this->load->model('login_model');
                $jwtPayload = $this->login_model->formatJWTPayload($UserData->Data[0]);

                if ($jwtPayload->Error === FALSE) {
                    $this->cacheservice->set($GetRedisDetails->Key, json_encode($jwtPayload->JWTData), $GetRedisDetails->TTL);
                }
            }
        }
    }

    public function checkImageType($str = '') {
        return $this->globalservice->checkImageType($str);
    }

}