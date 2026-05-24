<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Organisation extends CI_Controller {

    public $pageData = array();
    private $EndReturnData;

    public function __construct() {
        parent::__construct();

    }

    public function index() {

        // Ensure view-required keys always exist so footer_script.php never crashes
        $this->pageData['OrgBussType']    = [];
        $this->pageData['OrgIndusType']   = [];
        $this->pageData['OrgBusRegType']  = [];
        $this->pageData['EditOrgData']    = null;
        $this->pageData['BillOrgAddrData'] = null;
        $this->pageData['ShipOrgAddrData'] = null;
        $this->pageData['StateData']      = [];
        $this->pageData['CityData']       = [];
        $this->pageData['TimezoneInfo']   = [];

        try {

            $this->load->model('global_model');
            
            $this->load->model('organisation_model');
            $OrgBussTypeData = $this->organisation_model->getOrgBusinessTypeDetails();
            $this->pageData['OrgBussType'] = $OrgBussTypeData->Error === FALSE ? $OrgBussTypeData->Data : [];
            
            $OrgIndusTypeData = $this->organisation_model->getOrgIndustryTypeDetails();
            $this->pageData['OrgIndusType'] = $OrgIndusTypeData->Error === FALSE ? $OrgIndusTypeData->Data : [];
            
            $OrgBusRegData = $this->organisation_model->getOrgBusRegTypeDetails();
            $this->pageData['OrgBusRegType'] = $OrgBusRegData->Error === FALSE ? $OrgBusRegData->Data : [];

            $OrganisationData = $this->organisation_model->getAllOrganisationAddressDetails(['Org.OrgUID' => $this->pageData['JwtData']->User->OrgUID]);
            if ($OrganisationData->Error === FALSE && !empty($OrganisationData->Data)) {

                $orgRow = $OrganisationData->Data[0];

                $this->pageData['EditOrgData']    = $orgRow;
                $this->pageData['BillOrgAddrData'] = $this->mapOrganisationAddress($orgRow, 'B', 'Billing') ?? null;
                $this->pageData['ShipOrgAddrData'] = $this->mapOrganisationAddress($orgRow, 'S', 'Shipping') ?? null;

                if (!empty($orgRow->CountryISO2)) {
                    $StateInfo = $this->global_model->getStateofCountry($orgRow->CountryISO2);
                    if ($StateInfo->Error === FALSE) $this->pageData['StateData'] = $StateInfo->Data;

                    $CityInfo = $this->global_model->getCityofCountry($orgRow->CountryISO2);
                    if ($CityInfo->Error === FALSE) $this->pageData['CityData'] = $CityInfo->Data;
                }
            }

            $TimezoneInfo = $this->global_model->getTimezoneDetails([]);
            $this->pageData['TimezoneInfo'] = $TimezoneInfo->Error === FALSE ? $TimezoneInfo->Data : [];

            $GeneralSettings = $this->redisservice->getUserCache('settings') ?? new stdClass();
            $this->pageData['JwtData']->GenSettings = $GeneralSettings;

            $this->load->view('organisation/view', $this->pageData);

        } catch (Exception $e) {
            log_message('error', 'Organisation::index() — ' . $e->getMessage());
            $this->load->view('organisation/view', $this->pageData);
        }

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
                'CountryCode'       => '+91',
                'CountryISO2'       => 'IN',
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

            // Rebuild org info Redis cache with fresh DB data + resolved CDN URL
            $orgUID = (int)$PostData['OrgUID'];
            $this->redisservice->deleteCache($this->redisservice->orgKey('org_info'));
            $this->load->model('organisation_model');
            $this->organisation_model->getOrgInfoCached($orgUID);

            // Refresh JWT payload so OrgLogo / OrgName / OrgMobile are up-to-date
            $this->globalservice->refreshUserCache();

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

}