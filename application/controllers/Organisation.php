<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Organisation extends MY_Controller {

    public    $pageData      = array();
    protected $pageModuleUID = 1;
    private   $EndReturnData;

    public function __construct() {
        parent::__construct();
    }

    public function index() {

        $this->_loadPageTitle($this->pageModuleUID);

        // Ensure view-required keys always exist so footer_script.php never crashes
        // OrgBussType / OrgIndusType / OrgBusRegType removed â€” lazy-loaded by JS via globalKey
        $this->pageData['EditOrgData']    = null;
        $this->pageData['BillOrgAddrData'] = null;
        $this->pageData['ShipOrgAddrData'] = null;

        try {

            $this->load->model('global_model');
            
            $this->load->model('organisation_model');

            $orgUID = $this->pageData['JwtData']->Org->OrgUID;

            $OrganisationData = $this->organisation_model->getAllOrganisationAddressDetails(['Org.OrgUID' => $orgUID]);
            if ($OrganisationData->Error === FALSE && !empty($OrganisationData->Data)) {
                $orgRow = $OrganisationData->Data[0];
                $this->pageData['EditOrgData']     = $orgRow;
                $this->pageData['BillOrgAddrData'] = $this->mapOrganisationAddress($orgRow, 'B', 'Billing') ?? null;
            }

            $shipResult = $this->organisation_model->getOrgShippingAddresses($orgUID);
            $this->pageData['ShipOrgAddrList']  = (!$shipResult->Error) ? $shipResult->Data : [];
            $this->pageData['MaxShippingAddr']   = (int)($this->pageData['JwtData']->GenSettings->MaxShippingAddr ?? 3);

            // Timezone list is loaded via AJAX after page render â€” not blocking here.

            $GeneralSettings = $this->pageData['JwtData']->GenSettings ?? new stdClass();

            $this->load->view('organisation/view', $this->pageData);

        } catch (Exception $e) {
            $this->notifyError('Organisation::index', $e);
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
                'ShortCode'         => strtoupper(getPostValue($PostData, 'ShortCode')),
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
                'StateCode'         => getPostValue($PostData, 'StateCode', 'Array', NULL, false),
                'StateName'         => getPostValue($PostData, 'StateName', 'Array', NULL, false),
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

            // Billing â€” single address
            if (!empty($PostData['BillAddrLine1'])) {
                $this->handleAddress($PostData, 'Billing', $userUID, $now);
            }

            // Shipping â€” multiple addresses via JSON array
            $shipAddresses = json_decode($PostData['ShipAddresses'] ?? '[]', true) ?: [];
            foreach ($shipAddresses as $sa) {
                if (empty($sa['Line1'])) continue;
                $shipData = [
                    'OrgUID'      => (int) $PostData['OrgUID'],
                    'AddressType' => 'Shipping',
                    'Line1'       => $sa['Line1']     ?? '',
                    'Line2'       => $sa['Line2']     ?? '',
                    'Pincode'     => $sa['Pincode']   ?? '',
                    'City'        => $sa['CityId']    ?? '',
                    'CityText'    => $sa['CityName']  ?? '',
                    'State'       => $sa['StateId']   ?? '',
                    'StateText'   => $sa['StateName'] ?? '',
                    'UpdatedBy'   => $userUID,
                ];
                $uid = (int)($sa['UID'] ?? 0);
                if ($uid > 0) {
                    $this->dbwrite_model->updateData('Organisation', 'OrgAddressTbl', $shipData, ['OrgAddressUID' => $uid]);
                } else {
                    $shipData['CreatedOn'] = $now;
                    $shipData['CreatedBy'] = $userUID;
                    $this->dbwrite_model->insertData('Organisation', 'OrgAddressTbl', $shipData);
                }
            }

            // Delete removed shipping addresses
            $delUIDs = json_decode($PostData['DelShipUIDs'] ?? '[]', true) ?: [];
            foreach ($delUIDs as $delUID) {
                $delUID = (int) $delUID;
                if ($delUID > 0) {
                    $this->dbwrite_model->updateData('Organisation', 'OrgAddressTbl', ['IsDeleted' => 1], ['OrgAddressUID' => $delUID]);
                }
            }

            // Rebuild org info Redis cache with fresh DB data + resolved CDN URL
            $orgUID = (int) $PostData['OrgUID'];
            $this->redisservice->deleteCache($this->redisservice->orgKey('org-info'));
            $this->redisservice->deleteCache($this->redisservice->orgKey('org-dispatch-addresses-shipping'));
            $this->load->model('organisation_model');
            $this->organisation_model->getOrgInfoCached($orgUID);

            // Refresh JWT payload so OrgLogo / OrgName / OrgMobile are up-to-date
            $this->globalservice->refreshUserCache();

            // Return fresh billing + shipping UIDs so JS can update in-memory state
            // and prevent duplicate inserts on subsequent saves without page refresh
            $freshOrg = $this->organisation_model->getAllOrganisationAddressDetails(['Org.OrgUID' => $orgUID]);
            $freshBill = (!$freshOrg->Error && !empty($freshOrg->Data))
                ? $this->mapOrganisationAddress($freshOrg->Data[0], 'B', 'Billing')
                : null;
            $freshShip = $this->organisation_model->getOrgShippingAddresses($orgUID);

            $this->EndReturnData->Error        = FALSE;
            $this->EndReturnData->Message      = 'Updated Successfully';
            $this->EndReturnData->BillAddrUID  = $freshBill ? (int)$freshBill->OrgAddressUID : 0;
            $this->EndReturnData->ShipAddresses = (!$freshShip->Error)
                ? array_map(function($sa) {
                    return [
                        'UID'       => (int)$sa->OrgAddressUID,
                        'Line1'     => $sa->Line1    ?? '',
                        'Line2'     => $sa->Line2    ?? '',
                        'Pincode'   => $sa->Pincode  ?? '',
                        'StateId'   => $sa->State    ?? '',
                        'StateName' => $sa->StateText ?? '',
                        'StateISO2' => '',
                        'CityId'    => $sa->City     ?? '',
                        'CityName'  => $sa->CityText ?? '',
                    ];
                }, $freshShip->Data)
                : [];

        } catch (Exception $e) {
            $this->notifyError('Organisation::updateOrgForm', $e);
            $this->EndReturnData->Error = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

		$this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->_display();
        exit;

    }

    public function getBusinessTypes(): void {
        $this->EndReturnData = new stdClass();
        try {
            $this->load->model('organisation_model');
            $result = $this->organisation_model->getOrgBusinessTypeDetails();
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $result->Data;
        } catch (Exception $e) {
            $this->notifyError('Organisation::getBusinessTypes', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData))
            ->_display();
        exit;
    }

    public function getIndustryTypes(): void {
        $this->EndReturnData = new stdClass();
        try {
            $this->load->model('organisation_model');
            $result = $this->organisation_model->getOrgIndustryTypeDetails();
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $result->Data;
        } catch (Exception $e) {
            $this->notifyError('Organisation::getIndustryTypes', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData))
            ->_display();
        exit;
    }

    public function getBusRegTypes(): void {
        $this->EndReturnData = new stdClass();
        try {
            $this->load->model('organisation_model');
            $result = $this->organisation_model->getOrgBusRegTypeDetails();
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $result->Data;
        } catch (Exception $e) {
            $this->notifyError('Organisation::getBusRegTypes', $e);
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData))
            ->_display();
        exit;
    }

    public function addShipAddress(): void {
        $this->EndReturnData          = new stdClass();
        $this->EndReturnData->Error   = false;
        $this->EndReturnData->Message = '';
        try {
            $post    = $this->input->post(null, true);
            $orgUID  = (int)$this->pageData['JwtData']->Org->OrgUID;
            $userUID = (int)$this->pageData['JwtData']->User->UserUID;
            $maxShip = (int)($this->pageData['JwtData']->GenSettings->MaxShippingAddr ?? 3);
            $now     = date('Y-m-d H:i:s');

            $this->load->model('organisation_model');
            $this->load->model('dbwrite_model');
            $shipResult   = $this->organisation_model->getOrgShippingAddresses($orgUID);
            $currentCount = (!$shipResult->Error) ? count($shipResult->Data) : 0;

            if ($currentCount >= $maxShip) {
                $this->EndReturnData->Error   = true;
                $this->EndReturnData->Message = 'Only ' . $maxShip . ' shipping ' . ($maxShip === 1 ? 'address' : 'addresses') . ' can be added. Limit reached.';
            } else {
                $line1     = trim($post['Line1']     ?? '');
                $line2     = trim($post['Line2']     ?? '');
                $cityText  = trim($post['CityText']  ?? '');
                $cityId    = (int)($post['CityId']   ?? 0);
                $stateText = trim($post['StateText'] ?? '');
                $stateId   = (int)($post['StateId']  ?? 0);
                $pincode   = trim($post['Pincode']   ?? '');

                if (empty($line1)) throw new Exception('Address Line 1 is required.');

                $resp = $this->dbwrite_model->insertData('Organisation', 'OrgAddressTbl', [
                    'OrgUID'      => $orgUID,
                    'AddressType' => 'Shipping',
                    'Line1'       => $line1,
                    'Line2'       => $line2,
                    'CityText'    => $cityText,
                    'City'        => $cityId ?: null,
                    'StateText'   => $stateText,
                    'State'       => $stateId ?: null,
                    'Pincode'     => $pincode,
                    'CreatedOn'   => $now,
                    'CreatedBy'   => $userUID,
                    'UpdatedBy'   => $userUID,
                ]);
                if ($resp->Error) throw new Exception($resp->Message);

                $this->redisservice->deleteCache($this->redisservice->orgKey('org-dispatch-addresses-shipping'));

                $newUID = (int)$resp->ID;
                $this->EndReturnData->OrgAddressUID = $newUID;
                $this->EndReturnData->Message       = 'Shipping address added successfully.';
                $this->EndReturnData->NewCount      = $currentCount + 1;
                $this->EndReturnData->MaxShip       = $maxShip;
                $this->EndReturnData->Address       = [
                    'OrgAddressUID' => $newUID,
                    'OrgName'       => $this->pageData['JwtData']->Org->Name ?? '',
                    'Line1'         => $line1,
                    'Line2'         => $line2,
                    'CityText'      => $cityText,
                    'CityId'        => $cityId,
                    'StateText'     => $stateText,
                    'StateId'       => $stateId,
                    'Pincode'       => $pincode,
                    'AddressType'   => 'Shipping',
                ];
            }
        } catch (Exception $e) {
            $this->notifyError('Organisation::addShipAddress', $e);
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData))
            ->_display();
        exit;
    }

    public function updateShipAddress(): void {
        $this->EndReturnData          = new stdClass();
        $this->EndReturnData->Error   = false;
        $this->EndReturnData->Message = '';
        try {
            $post    = $this->input->post(null, true);
            $orgUID  = (int)$this->pageData['JwtData']->Org->OrgUID;
            $userUID = (int)$this->pageData['JwtData']->User->UserUID;
            $addrUID = (int)($post['OrgAddressUID'] ?? 0);

            if ($addrUID <= 0) throw new Exception('Invalid address.');

            $line1     = trim($post['Line1']     ?? '');
            $line2     = trim($post['Line2']     ?? '');
            $cityText  = trim($post['CityText']  ?? '');
            $cityId    = (int)($post['CityId']   ?? 0);
            $stateText = trim($post['StateText'] ?? '');
            $stateId   = (int)($post['StateId']  ?? 0);
            $pincode   = trim($post['Pincode']   ?? '');

            if (empty($line1)) throw new Exception('Address Line 1 is required.');

            $this->load->model('dbwrite_model');
            $resp = $this->dbwrite_model->updateData('Organisation', 'OrgAddressTbl', [
                'Line1'     => $line1,
                'Line2'     => $line2,
                'CityText'  => $cityText,
                'City'      => $cityId ?: null,
                'StateText' => $stateText,
                'State'     => $stateId ?: null,
                'Pincode'   => $pincode,
                'UpdatedBy' => $userUID,
            ], ['OrgAddressUID' => $addrUID, 'OrgUID' => $orgUID]);

            if ($resp->Error) throw new Exception($resp->Message);

            $this->redisservice->deleteCache($this->redisservice->orgKey('org-dispatch-addresses-shipping'));

            $this->EndReturnData->OrgAddressUID = $addrUID;
            $this->EndReturnData->Message       = 'Shipping address updated successfully.';
            $this->EndReturnData->Address       = [
                'OrgAddressUID' => $addrUID,
                'Line1'         => $line1,
                'Line2'         => $line2,
                'CityText'      => $cityText,
                'CityId'        => $cityId,
                'StateText'     => $stateText,
                'StateId'       => $stateId,
                'Pincode'       => $pincode,
            ];
        } catch (Exception $e) {
            $this->notifyError('Organisation::updateShipAddress', $e);
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->output->set_status_header(200)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($this->EndReturnData))
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
