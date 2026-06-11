<?php defined('BASEPATH') or exit('No direct script access allowed');

class Vendors extends MY_Controller {

    public $pageData = array();
    private $EndReturnData;

    public function __construct() {
        parent::__construct();
    }

    // ── Internal helpers ──────────────────────────────────────────────────────

    private function _initModule() {
        if (isset($this->pageData['ModuleId'])) return;

        $controllerName = strtolower($this->router->fetch_class());
        $getModuleInfo  = $this->redisservice->getUserCache('modules') ?? [];
        $ModuleInfo     = array_values(array_filter($getModuleInfo, fn($m) => $m->ControllerName === $controllerName));
        if (empty($ModuleInfo)) {
            throw new Exception("Module information not found for controller: {$controllerName}");
        }

        $this->pageData['ModuleId']              = $ModuleInfo[0]->ModuleUID;
        $this->pageData['ModColumnData']         = $ModuleInfo[0]->DispViewColumns ?? [];
        $this->pageData['DispSettColumnDetails'] = $ModuleInfo[0]->DispSettingsViewColumns ?? [];

        $GeneralSettings = $this->pageData['JwtData']->GenSettings ?? new stdClass();
        $this->pageData['Limit'] = $GeneralSettings->RowLimit ?? 10;
    }

    private function _fetchTableData($pageNo, $limit, $filter = []) {
        $orgUID = $this->pageData['JwtData']->Org->OrgUID;
        $offset = max(0, ($pageNo - 1) * $limit);

        $this->load->model('vendors_model');
        $result = $this->vendors_model->getVendorListPaginated($orgUID, $limit, $offset, $filter);

        $rowHtml = $this->load->view('vendors/list', [
            'DataLists'       => $result->rows,
            'SerialNumber'    => $offset,
            'DispViewColumns' => $this->pageData['ModColumnData'],
            'JwtData'         => $this->pageData['JwtData'],
            'GenSettings'     => $this->pageData['JwtData']->GenSettings,
        ], TRUE);

        $resp                 = new stdClass();
        $resp->RecordHtmlData = $rowHtml;
        $resp->Pagination     = $this->globalservice->buildPagePaginationHtml('/vendors/getVendorsPageDetails', $result->totalCount, $pageNo, $limit);
        $resp->TotalCount     = $result->totalCount;
        return $resp;
    }

    // ── Page routes ───────────────────────────────────────────────────────────
    public function index() {
        if (!$this->_loadPageTitle()) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }
        try {

            $this->_initModule();
            $limit = $this->pageData['Limit'];

            $pageData = $this->_fetchTableData(1, $limit);
            $this->pageData['ModRowData']    = $pageData->RecordHtmlData;
            $this->pageData['ModPagination'] = $pageData->Pagination;

            $this->load->model('vendors_model');
            $orgUID = $this->pageData['JwtData']->Org->OrgUID;
            $this->pageData['VendStats'] = $this->vendors_model->getVendorStats($orgUID);
            $this->pageData['Tags']      = $this->vendors_model->getVendorTags($orgUID);

            $this->load->model('users_model');
            $cacheKey = $this->redisservice->orgKey('org_users');
            $orgUsers = $this->redisservice->getCache($cacheKey)->Value;
            if (!is_array($orgUsers)) {
                $orgUsers = $this->users_model->getOrgUsersForCache($orgUID);
                if (!empty($orgUsers)) { $this->redisservice->setCache($cacheKey, $orgUsers, 86400); }
            }
            $this->pageData['OrgUsers']      = $orgUsers;
            $this->pageData['ShowUserFilter'] = is_array($orgUsers) && count($orgUsers) > 1;

            $this->load->model('global_model');
            $GetCountryInfo = $this->global_model->getCountryInfo();
            $this->pageData['CountryInfo'] = $GetCountryInfo->Error === FALSE ? $GetCountryInfo->Data : [];

            $orgCCode = $this->pageData['JwtData']->Org->OrgCCode  ?? '';
            $orgCISO2 = $this->pageData['JwtData']->Org->OrgCISO2  ?? '';
            if (empty($orgCCode) || empty($orgCISO2)) {
                $this->load->model('organisation_model');
                $orgResp = $this->organisation_model->getOrganisationDetails(['Org.OrgUID' => $this->pageData['JwtData']->Org->OrgUID]);
                if ($orgResp->Error === FALSE && !empty($orgResp->Data)) {
                    $orgCCode = $orgCCode ?: ($orgResp->Data[0]->CountryCode ?? '');
                    $orgCISO2 = $orgCISO2 ?: ($orgResp->Data[0]->CountryISO2 ?? '');
                }
            }
            $this->pageData['OrgCCode'] = $orgCCode;
            $this->pageData['OrgCISO2'] = $orgCISO2;

            $this->load->view('vendors/view', $this->pageData);

        } catch (Exception $e) {
            redirect('dashboard', 'refresh');
        }
    }

    public function getVendorsPageDetails($pageNo = 0) {
        $this->EndReturnData = new stdClass();
        try {

            $pageNo = max(1, (int) $pageNo);
            $filter = $this->input->post('Filter') ?: [];

            $this->_initModule();

            $limit = (int) ($this->input->post('RowLimit') ?: $this->pageData['Limit']);

            $pageData = $this->_fetchTableData($pageNo, $limit, $filter);

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->RecordHtmlData = $pageData->RecordHtmlData;
            $this->EndReturnData->Pagination     = $pageData->Pagination;
            $this->EndReturnData->TotalCount     = $pageData->TotalCount;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    private function loadCountryStateCityData() {
        $this->load->model('global_model');
        $GetCountryInfo = $this->global_model->getCountryInfo();
        $this->pageData['CountryInfo'] = $GetCountryInfo->Error === FALSE ? $GetCountryInfo->Data : [];

        $this->pageData['StateData'] = [];
        $this->pageData['CityData']  = [];

        $OrgCountryISO2 = $this->pageData['JwtData']->Org->OrgCISO2;
        if (!empty($OrgCountryISO2)) {
            $StateInfo = $this->global_model->getStateofCountry($OrgCountryISO2);
            if ($StateInfo->Error === FALSE) $this->pageData['StateData'] = $StateInfo->Data;

            $CityInfo = $this->global_model->getCityofCountry($OrgCountryISO2);
            if ($CityInfo->Error === FALSE) $this->pageData['CityData'] = $CityInfo->Data;
        }
    }

    public function create() {
        try {
            $this->loadCountryStateCityData();
            $this->load->view('vendors/forms/add', $this->pageData);
        } catch (Exception $e) {
            redirect('vendors', 'refresh');
        }
    }

    private function buildVendorFormData($postData, $isCreate = false) {
        $data = [
            'Name'              => getPostValue($postData, 'Name'),
            'Area'              => getPostValue($postData, 'Area'),
            'OrgUID'            => $this->pageData['JwtData']->Org->OrgUID,
            'BranchUID'         => $this->pageData['JwtData']->Org->BranchUID,
            'EmailAddress'      => getPostValue($postData, 'EmailAddress'),
            'CountryCode'       => getPostValue($postData, 'CountryCode'),
            'CountryISO2'       => getPostValue($postData, 'CountryISO2', '', 'IN'),
            'MobileNumber'      => getPostValue($postData, 'MobileNumber'),
            'DebitCreditType'   => getPostValue($postData, 'DebitCreditCheck', '', 'Credit'),
            'DebitCreditAmount' => getPostValue($postData, 'DebitCreditAmount', '', 0),
            'PANNumber'         => getPostValue($postData, 'PANNumber'),
            'ContactPerson'     => getPostValue($postData, 'ContactPerson'),
            'DateOfBirth'       => getPostValue($postData, 'CPDateOfBirth'),
            'GSTIN'             => getPostValue($postData, 'GSTIN'),
            'CompanyName'       => getPostValue($postData, 'CompanyName'),
            'Notes'             => getPostValue($postData, 'Notes'),
            'UpdatedBy'         => $this->pageData['JwtData']->User->UserUID,
        ];
        if ($isCreate) {
            $this->load->model('transactions_model');
            $data['VendToken'] = $this->transactions_model->_generateUniqueToken('Vendors.VendorTbl', 'VendToken');
            $data['CreatedBy'] = $this->pageData['JwtData']->User->UserUID;
        }
        return $data;
    }

    public function addVendorData() {
        $this->EndReturnData = new stdClass();
        $ErrorInForm = '';
        try {

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData = $this->input->post();

            $this->load->model('formvalidation_model');
            $ErrorInForm = $this->formvalidation_model->vendorValidateForm($PostData);
            if (!empty($ErrorInForm)) throw new InvalidArgumentException('VALIDATION_ERROR');

            $vendorFormData = $this->buildVendorFormData($PostData, true);

            $InsertDataResp = $this->dbwrite_model->insertData('Vendors', 'VendorTbl', $vendorFormData);
            if ($InsertDataResp->Error) throw new Exception($InsertDataResp->Message);

            $VendorUID = $InsertDataResp->ID;

            if (isset($_FILES['UploadImage'])) {
                $UploadResp = $this->globalservice->fileUploadService($_FILES['UploadImage'], 'vendors/images/', 'Image', ['Vendors', 'VendorTbl', ['VendorUID' => $VendorUID]]);
                if ($UploadResp->Error) throw new Exception($UploadResp->Message);
            }

            $this->globalservice->saveBankDetails($VendorUID, $this->input->post('BankDetailsJSON'), 'Vendors', 'VendBankDetailsTbl', [], 'VendBankDetUID');

            foreach ([['Bill', 'Billing'], ['Ship', 'Shipping']] as [$prefix, $type]) {
                $this->globalservice->saveAddressInfo($PostData, $VendorUID, $prefix, $type, 'Vendors', 'VendAddressTbl', 'VendAddressUID', 'VendorUID');
            }

            $this->load->library('accountledger');
            $this->accountledger->createLedgerAccountingInfo(
                $VendorUID,
                [
                    'Name'               => getPostValue($PostData, 'Name'),
                    'OpeningBalance'     => getPostValue($PostData, 'DebitCreditAmount', '', 0),
                    'OpeningBalanceType' => getPostValue($PostData, 'DebitCreditCheck', '', 'Credit'),
                ],
                'Vendor'
            );

            $initAmt  = (float) getPostValue($PostData, 'DebitCreditAmount', '', 0);
            $initType = getPostValue($PostData, 'DebitCreditCheck', '', 'Credit');
            if ($initAmt > 0) {
                $this->vendors_model->saveVendorOpeningBalance(
                    $this->pageData['JwtData']->Org->OrgUID, $VendorUID,
                    $initAmt, $initType, null,
                    $this->pageData['JwtData']->User->UserUID
                );
                $this->vendors_model->saveVendorYearOpening(
                    $this->pageData['JwtData']->Org->OrgUID, $VendorUID,
                    $this->_currentFinancialYear(),
                    $initAmt, $initType,
                    $this->pageData['JwtData']->User->UserUID
                );
            }

            $linkCustomer = $PostData['CustomerLinkingCheck'] ?? null;
            if (!empty($linkCustomer) && (string) $linkCustomer === 'NewCustomer') {
                $insCustDataResp = $this->dbwrite_model->insertData('Customers', 'CustomerTbl', $vendorFormData);
                if ($insCustDataResp->Error) throw new Exception($insCustDataResp->Message);

                $this->globalservice->saveBankDetails($insCustDataResp->ID, $this->input->post('BankDetailsJSON'), 'Customers', 'CustBankDetailsTbl', [], 'CustBankDetUID');
                foreach ([['Bill', 'Billing'], ['Ship', 'Shipping']] as [$prefix, $type]) {
                    $this->globalservice->saveAddressInfo($PostData, $insCustDataResp->ID, $prefix, $type, 'Customers', 'CustAddressTbl', 'CustAddressUID', 'CustomerUID');
                }

                $updVendorLink = $this->dbwrite_model->updateData('Vendors', 'VendorTbl', ['CustomerUID' => $insCustDataResp->ID], ['VendorUID' => $VendorUID]);
                if ($updVendorLink->Error) throw new Exception($updVendorLink->Message ?? 'Failed to link customer to vendor');

            } elseif (!empty($linkCustomer) && (string) $linkCustomer === 'ExistingCustomer') {
                $CustomerUID = getPostValue($PostData, 'Customers', 0);
                if ($CustomerUID <= 0) throw new Exception('Invalid Customer selected for linking');

                $updVendorLink = $this->dbwrite_model->updateData('Vendors', 'VendorTbl', ['CustomerUID' => $CustomerUID], ['VendorUID' => $VendorUID]);
                if ($updVendorLink->Error) throw new Exception($updVendorLink->Message ?? 'Failed to link customer to vendor');
            }

            $this->dbwrite_model->commitTransaction();
            $this->cachehelper->upsertVendor($VendorUID);

            $this->_initModule();
            $pageData = $this->_fetchTableData(1, $this->pageData['Limit']);
            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Message    = 'Created Successfully';
            $this->EndReturnData->VendorUID  = $VendorUID;
            $this->EndReturnData->VendorName = getPostValue($PostData, 'Name');
            $this->EndReturnData->VendorArea = getPostValue($PostData, 'Area');
            $this->EndReturnData->List       = $pageData->RecordHtmlData;
            $this->EndReturnData->Pagination = $pageData->Pagination;
            $this->EndReturnData->Stats      = $this->vendors_model->getVendorStats($this->pageData['JwtData']->Org->OrgUID);

        } catch (InvalidArgumentException $e) {
            $this->dbwrite_model->rollbackTransaction();
            if ($e->getMessage() === 'VALIDATION_ERROR') {
                $this->EndReturnData->Error   = true;
                $this->EndReturnData->Message = strip_tags($ErrorInForm);
                $this->EndReturnData->Errors  = 'Please correct the highlighted errors.';
            } else {
                throw $e;
            }
        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function edit($VendorUID) {
        try {

            $VendorUID = (int) $VendorUID;
            if ($VendorUID <= 0) { redirect('vendors', 'refresh'); return; }

            $this->load->model('vendors_model');
            $getVendorData = $this->vendors_model->getVendors(['Vendors.VendorUID' => $VendorUID]);
            if (empty($getVendorData) || count($getVendorData) !== 1) { redirect('vendors', 'refresh'); return; }

            $this->pageData['EditData']   = $getVendorData[0];
            $this->pageData['BankDetails'] = $this->vendors_model->getVendorBankInfo(['VendBankDetails.VendorUID' => $getVendorData[0]->VendorUID]);

            $this->loadCountryStateCityData();

            $AddressInfo = $this->vendors_model->getVendorAddress(['VendAddress.VendorUID' => $getVendorData[0]->VendorUID]);
            $this->pageData['BillingAddr']  = null;
            $this->pageData['ShippingAddr'] = null;
            foreach ($AddressInfo as $addr) {
                if ($addr->AddressType === 'Billing')  $this->pageData['BillingAddr']  = $addr;
                if ($addr->AddressType === 'Shipping') $this->pageData['ShippingAddr'] = $addr;
            }

            $this->load->view('vendors/forms/edit', $this->pageData);

        } catch (Exception $e) {
            redirect('vendors', 'refresh');
        }
    }

    public function cloneVendor($VendorUID) {
        try {

            $VendorUID = (int) $VendorUID;
            if ($VendorUID <= 0) { redirect('vendors', 'refresh'); return; }

            $this->load->model('vendors_model');
            $vendorData = $this->vendors_model->getVendors(['Vendors.VendorUID' => $VendorUID]);
            if (empty($vendorData) || count($vendorData) !== 1) { redirect('vendors', 'refresh'); return; }

            $this->pageData['EditData']   = $vendorData[0];
            $this->pageData['BankDetails'] = $this->vendors_model->getVendorBankInfo(['VendBankDetails.VendorUID' => $vendorData[0]->VendorUID]);

            $this->loadCountryStateCityData();

            $AddressInfo = $this->vendors_model->getVendorAddress(['VendAddress.VendorUID' => $vendorData[0]->VendorUID]);
            $this->pageData['BillingAddr']  = null;
            $this->pageData['ShippingAddr'] = null;
            foreach ($AddressInfo as $addr) {
                if ($addr->AddressType === 'Billing')  $this->pageData['BillingAddr']  = $addr;
                if ($addr->AddressType === 'Shipping') $this->pageData['ShippingAddr'] = $addr;
            }

            $this->load->view('vendors/forms/clone', $this->pageData);

        } catch (Exception $e) {
            redirect('vendors', 'refresh');
        }
    }

    public function loadModalForm($type = 'add', $uid = 0) {
        $this->EndReturnData = new stdClass();
        try {
            $type = in_array($type, ['add', 'edit', 'clone']) ? $type : 'add';
            $uid  = (int) $uid;

            $this->load->model('vendors_model');
            $this->loadCountryStateCityData();

            $formData     = null;
            $bankDetails  = [];
            $billingAddr  = null;
            $shippingAddr = null;

            if (in_array($type, ['edit', 'clone']) && $uid > 0) {
                $getVendorData = $this->vendors_model->getVendors(['Vendors.VendorUID' => $uid]);
                if (!empty($getVendorData)) {
                    $formData    = $getVendorData[0];
                    $bankDetails = $this->vendors_model->getVendorBankInfo(['VendBankDetails.VendorUID' => $uid]);
                    $addrInfo    = $this->vendors_model->getVendorAddress(['VendAddress.VendorUID' => $uid]);
                    foreach ($addrInfo as $addr) {
                        if ($addr->AddressType === 'Billing')  $billingAddr  = $addr;
                        if ($addr->AddressType === 'Shipping') $shippingAddr = $addr;
                    }
                }
            }

            $html = $this->load->view('vendors/forms/modal_body', [
                'FormMode'     => $type,
                'FormData'     => $formData,
                'BankDetails'  => $bankDetails,
                'BillingAddr'  => $billingAddr,
                'ShippingAddr' => $shippingAddr,
                'CountryInfo'  => $this->pageData['CountryInfo'],
                'JwtData'      => $this->pageData['JwtData'],
            ], TRUE);

            $this->EndReturnData->Error        = FALSE;
            $this->EndReturnData->Html         = $html;
            $this->EndReturnData->StateData    = $this->pageData['StateData'];
            $this->EndReturnData->CityData     = $this->pageData['CityData'];
            $this->EndReturnData->FormMode     = $type;
            $this->EndReturnData->ImgData      = $formData ? ($formData->Image ?? '') : '';
            $this->EndReturnData->BillingAddr  = $billingAddr;
            $this->EndReturnData->ShippingAddr = $shippingAddr;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function syncVendorsCache() {

        $this->EndReturnData = new stdClass();
        try {

            $orgUID = $this->pageData['JwtData']->Org->OrgUID;

            $this->load->model('vendors_model');

            // Fetch all active vendors
            $vendors = $this->vendors_model->getVendors(['Vendors.OrgUID' => $orgUID]);
            if (empty($vendors)) throw new Exception('No vendors found.');

            // DEL old STRING key (handles migration) then rebuild fresh as HSET
            $cacheKey = $this->redisservice->orgKey('vendors');
            $this->upstashservice->del($cacheKey);
            $newMap   = [];

            foreach ($vendors as $vend) {
                $uid = (int)$vend->VendorUID;

                // Fetch address
                $addrInfo    = $this->vendors_model->getVendorAddress(['VendAddress.VendorUID' => $uid]);
                $addressList = [];
                foreach ($addrInfo as $addr) {
                    $addressList[] = [
                        'AddressType' => $addr->AddressType,
                        'Line1'       => $addr->Line1     ?? '',
                        'Line2'       => $addr->Line2     ?? '',
                        'Pincode'     => $addr->Pincode   ?? '',
                        'CityText'    => $addr->CityText  ?? '',
                        'StateText'   => $addr->StateText ?? '',
                    ];
                }

                // Fetch current opening balance
                $obRow          = $this->vendors_model->getVendorOpeningBalance($orgUID, $uid);
                $openingBalance = $obRow ? (float)$obRow->OpeningBalance : 0.0;
                $openingBalType = $obRow ? $obRow->OpeningBalType        : 'Credit';

                $newMap[(string)$uid] = [
                    'VendorUID'       => $uid,
                    'Name'            => $vend->Name          ?? '',
                    'CompanyName'     => $vend->CompanyName   ?? '',
                    'ContactPerson'   => $vend->ContactPerson ?? '',
                    'MobileNumber'    => $vend->MobileNumber  ?? '',
                    'CountryCode'     => $vend->CountryCode   ?? '',
                    'CountryISO2'     => $vend->CountryISO2   ?? '',
                    'EmailAddress'    => $vend->EmailAddress  ?? '',
                    'GSTIN'           => $vend->GSTIN         ?? '',
                    'PANNumber'       => $vend->PANNumber     ?? '',
                    'OpeningBalance'  => $openingBalance,
                    'OpeningBalType'  => $openingBalType,
                    'Area'            => $vend->Area          ?? '',
                    'Notes'           => $vend->Notes         ?? '',
                    'Image'           => $vend->Image         ?? '',
                    'Address'         => $addressList,
                ];
            }

            // Store as HSET — one bulk command, one field per vendor
            $this->upstashservice->hmset($cacheKey, $newMap);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = count($vendors) . ' vendor(s) synced to cache.';
            $this->EndReturnData->Count   = count($vendors);

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function getVendorForModal($uid = 0) {
        $this->EndReturnData = new stdClass();
        try {
            $uid = (int) $uid;
            if ($uid <= 0) throw new Exception('Invalid vendor ID.');

            // Cache-Aside READ
            $cacheKey = Upstashservice::keyVendor($uid);
            $cached   = $this->upstashservice->get($cacheKey);

            if ($cached !== null) {
                $this->EndReturnData->Error        = FALSE;
                $this->EndReturnData->Data         = (object)$cached['Data'];
                $this->EndReturnData->BankDetails  = $cached['BankDetails'] ?? [];
                $this->EndReturnData->BillingAddr  = isset($cached['BillingAddr'])  ? (object)$cached['BillingAddr']  : null;
                $this->EndReturnData->ShippingAddr = isset($cached['ShippingAddr']) ? (object)$cached['ShippingAddr'] : null;
            } else {
                $this->load->model('vendors_model');
                $getVendData = $this->vendors_model->getVendors(['Vendors.VendorUID' => $uid]);
                if (empty($getVendData)) throw new Exception('Vendor not found.');

                $bankDetails = $this->vendors_model->getVendorBankInfo(['VendBankDetails.VendorUID' => $uid]);
                $addrInfo    = $this->vendors_model->getVendorAddress(['VendAddress.VendorUID' => $uid]);

                $billingAddr = null; $shippingAddr = null;
                foreach ($addrInfo as $addr) {
                    if ($addr->AddressType === 'Billing')  $billingAddr  = $addr;
                    if ($addr->AddressType === 'Shipping') $shippingAddr = $addr;
                }

                $this->EndReturnData->Error        = FALSE;
                $this->EndReturnData->Data         = $getVendData[0];
                $this->EndReturnData->BankDetails  = $bankDetails;
                $this->EndReturnData->BillingAddr  = $billingAddr;
                $this->EndReturnData->ShippingAddr = $shippingAddr;

                $this->upstashservice->set($cacheKey, [
                    'Data'         => $getVendData[0],
                    'BankDetails'  => $bankDetails,
                    'BillingAddr'  => $billingAddr,
                    'ShippingAddr' => $shippingAddr,
                ], Upstashservice::TTL_VENDOR);
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function updateVendorData() {
        $this->EndReturnData = new stdClass();
        $ErrorInForm = '';
        try {

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData = $this->input->post();

            $this->load->model('formvalidation_model');
            $ErrorInForm = $this->formvalidation_model->vendorValidateForm($PostData);
            if (!empty($ErrorInForm)) throw new InvalidArgumentException('VALIDATION_ERROR');

            $VendorUID   = getPostValue($PostData, 'VendorUID', 0);
            $oldDCRow    = $this->vendors_model->getVendorDebitCreditRaw((int)$VendorUID);
            $oldDCAmount = $oldDCRow ? (float)$oldDCRow->DebitCreditAmount : 0.0;
            $oldDCType   = $oldDCRow ? $oldDCRow->DebitCreditType : 'Credit';

            $vendorFormData = $this->buildVendorFormData($PostData, false);
            if (!empty($PostData['ImageRemoved'])) $vendorFormData['Image'] = NULL;

            $UpdateDataResp = $this->dbwrite_model->updateData('Vendors', 'VendorTbl', $vendorFormData, ['VendorUID' => $VendorUID]);
            if ($UpdateDataResp->Error) throw new Exception($UpdateDataResp->Message);

            if (isset($_FILES['UploadImage'])) {
                $UploadResp = $this->globalservice->fileUploadService($_FILES['UploadImage'], 'vendors/images/', 'Image', ['Vendors', 'VendorTbl', ['VendorUID' => $VendorUID]]);
                if ($UploadResp->Error) throw new Exception($UploadResp->Message);
            }

            $delBnkFlag = getPostValue($PostData, 'delBankDataFlag');
            if ($delBnkFlag == 1) {
                $this->globalservice->softDeleteBankRecords(getPostValue($PostData, 'delBankData'), 'Vendors', 'VendBankDetailsTbl', 'VendBankDetUID');
            }
            $this->globalservice->saveBankDetails($PostData['VendorUID'], $this->input->post('BankDetailsJSON'), 'Vendors', 'VendBankDetailsTbl', [], 'VendBankDetUID');

            $delAddrFlag = getPostValue($PostData, 'delAddrDetailFlag');
            if ($delAddrFlag == 1) {
                $this->globalservice->softDeleteAddressRecords(getPostValue($PostData, 'delAddrData'), 'Vendors', 'VendAddressTbl', 'VendAddressUID');
            }
            foreach ([['Bill', 'Billing'], ['Ship', 'Shipping']] as [$prefix, $type]) {
                $this->globalservice->saveAddressInfo($PostData, $VendorUID, $prefix, $type, 'Vendors', 'VendAddressTbl', 'VendAddressUID', 'VendorUID');
            }

            $this->load->library('accountledger');
            $this->accountledger->updateEntityLedgerInfo(
                $VendorUID,
                [
                    'DebitCreditAmount' => getPostValue($PostData, 'DebitCreditAmount', '', 0),
                    'DebitCreditCheck'  => getPostValue($PostData, 'DebitCreditCheck', '', 'Credit'),
                    'Name'              => getPostValue($PostData, 'Name'),
                ],
                'Vendor'
            );

            $newDCAmount = (float) getPostValue($PostData, 'DebitCreditAmount', '', 0);
            $newDCType   = getPostValue($PostData, 'DebitCreditCheck', '', 'Credit');
            $oldSigned   = ($oldDCType === 'Credit') ? $oldDCAmount : -$oldDCAmount;
            $newSigned   = ($newDCType === 'Credit') ? $newDCAmount : -$newDCAmount;
            $delta       = round($newSigned - $oldSigned, 2);

            $orgUID  = $this->pageData['JwtData']->Org->OrgUID;
            $userUID = $this->pageData['JwtData']->User->UserUID;

            // Read current balance via ReadDb (no lock conflict)
            $obRow         = $this->vendors_model->getVendorOpeningBalance($orgUID, (int)$VendorUID);
            $currentSigned = 0.0;
            if ($obRow) {
                $currentSigned = ($obRow->OpeningBalType === 'Credit')
                    ? (float)$obRow->OpeningBalance : -(float)$obRow->OpeningBalance;
            }

            if ($delta != 0.0) {
                $balanceSigned = round($currentSigned + $delta, 2);
                $newBalance    = abs($balanceSigned);
                $newType       = ($balanceSigned >= 0) ? 'Credit' : 'Debit';

                // Write via dbwrite_model (C1 — same connection as this transaction, no FK deadlock)
                if ($obRow) {
                    $this->dbwrite_model->updateData('Vendors', 'VendOpeningBalanceTbl', [
                        'OpeningBalance' => $newBalance,
                        'OpeningBalType' => $newType,
                        'UpdatedBy'      => (int)$userUID,
                    ], ['VendBalUID' => (int)$obRow->VendBalUID]);
                } else {
                    $this->dbwrite_model->insertData('Vendors', 'VendOpeningBalanceTbl', [
                        'OrgUID'         => (int)$orgUID,
                        'VendorUID'      => (int)$VendorUID,
                        'OpeningBalance' => $newBalance,
                        'OpeningBalType' => $newType,
                        'PendingBalance' => $newBalance,
                        'PendingBalType' => $newType,
                        'IsActive'       => 1,
                        'IsDeleted'      => 0,
                        'CreatedBy'      => (int)$userUID,
                        'UpdatedBy'      => (int)$userUID,
                    ]);
                }
                $snapshotAmt  = $newBalance;
                $snapshotType = $newType;
            } else {
                $snapshotAmt  = $obRow ? (float)$obRow->OpeningBalance : abs($newSigned);
                $snapshotType = $obRow ? $obRow->OpeningBalType : (($newSigned >= 0) ? 'Credit' : 'Debit');
            }

            // Seed year-opening snapshot only if this financial year has no record yet
            $yrRow = $this->vendors_model->getVendorYearOpening($orgUID, (int)$VendorUID, $this->_currentFinancialYear());
            if (!$yrRow) {
                $this->dbwrite_model->insertData('Vendors', 'VendYearOpeningBalanceTbl', [
                    'OrgUID'         => (int)$orgUID,
                    'VendorUID'      => (int)$VendorUID,
                    'FinancialYear'  => (int)$this->_currentFinancialYear(),
                    'OpeningBalance' => $snapshotAmt,
                    'OpeningBalType' => $snapshotType,
                    'IsActive'       => 1,
                    'IsDeleted'      => 0,
                    'CreatedBy'      => (int)$userUID,
                    'UpdatedBy'      => (int)$userUID,
                ]);
            }

            $this->dbwrite_model->commitTransaction();

            // Refresh vendor in bulk search cache with live data
            $this->cachehelper->upsertVendor((int)$VendorUID);
            // Still invalidate vendor-products cache (separate key, separate data)
            $this->upstashservice->del(Upstashservice::keyVendorProducts((int)$VendorUID));

            $pageNo = (int) ($this->input->post('PageNo') ?: 1);
            $this->_initModule();
            $pageData = $this->_fetchTableData($pageNo, $this->pageData['Limit']);
            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Message    = 'Updated Successfully';
            $this->EndReturnData->List       = $pageData->RecordHtmlData;
            $this->EndReturnData->Pagination = $pageData->Pagination;
            $this->EndReturnData->Stats      = $this->vendors_model->getVendorStats($this->pageData['JwtData']->Org->OrgUID);

        } catch (InvalidArgumentException $e) {
            $this->dbwrite_model->rollbackTransaction();
            if ($e->getMessage() === 'VALIDATION_ERROR') {
                $this->EndReturnData->Error   = true;
                $this->EndReturnData->Message = strip_tags($ErrorInForm);
                $this->EndReturnData->Errors  = 'Please correct the highlighted errors.';
            } else {
                throw $e;
            }
        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }


    public function exportVendors() {
        $this->EndReturnData = new stdClass();
        try {
            $this->_initModule();
            $type      = $this->input->get('Type') ?: 'CSV';
            $filter    = [];
            $filterStr = $this->input->get('Filter');
            if (!empty($filterStr)) {
                $decoded = json_decode($filterStr, true);
                if (is_array($decoded)) $filter = $decoded;
            }

            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;
            $timezone = $this->pageData['JwtData']->GenSettings->Timezone ?? 'Asia/Kolkata';

            $this->load->model('vendors_model');
            $result = $this->vendors_model->getVendorListPaginated($orgUID, 0, 0, $filter);

            $this->load->model('organisation_model');
            $orgResult = $this->organisation_model->getOrgInfoCached($orgUID);
            $orgInfo   = ($orgResult->Error === FALSE) ? $orgResult->Data : null;

            $headers   = ['#', 'Vendor Name', 'Area', 'Mobile', 'Email', 'GSTIN', 'Company Name', 'Balance', 'Balance Type', 'Status', 'Last Updated', 'Updated By'];
            $colWidths = ['3%', '16%', '10%', '10%', '13%', '9%', '9%', '8%', '7%', '5%', '9%', '10%'];

            $rows = [];
            $i    = 1;
            foreach ($result->rows as $row) {
                $balance   = number_format((float)($row->ClosingBalance ?? 0), 2);
                $balType   = $row->ClosingBalanceType ?? 'Credit';
                $status    = (int)($row->IsActive ?? 1) === 1 ? 'Active' : 'Inactive';
                $updatedOn = !empty($row->UpdatedOn)
                    ? (new DateTime($row->UpdatedOn, new DateTimeZone('UTC')))->setTimezone(new DateTimeZone($timezone))->format($this->pageData['JwtData']->GenSettings->ListDateTimeFormat ?? 'd M Y')
                    : '';
                $rows[] = [
                    $i++,
                    $row->Name          ?? '',
                    $row->Area          ?? '',
                    $row->MobileNumber  ?? '',
                    $row->EmailAddress  ?? '',
                    $row->GSTIN         ?? '',
                    $row->CompanyName   ?? '',
                    $balance,
                    $balType,
                    $status,
                    $updatedOn,
                    $row->UpdatedBy     ?? '',
                ];
            }

            $this->_sendExport($type, 'Vendor_Data', 'Vendors', 'Vendor Report', $headers, $rows, $orgInfo, $timezone, $colWidths);

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
            $this->globalservice->sendJsonResponse($this->EndReturnData);
        }
    }

    public function getVendorTags() {
        $this->EndReturnData = new stdClass();
        try {
            $this->load->model('vendors_model');
            $tags = $this->vendors_model->getVendorTags($this->pageData['JwtData']->Org->OrgUID);
            $this->EndReturnData->Error = false;
            $this->EndReturnData->Tags  = $tags;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function getStats() {
        $this->EndReturnData = new stdClass();
        try {
            $this->load->model('vendors_model');
            $stats = $this->vendors_model->getVendorStats($this->pageData['JwtData']->Org->OrgUID);
            $this->EndReturnData->Error = false;
            $this->EndReturnData->Stats = $stats;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }
    public function toggleVendorStatus() {

        $this->EndReturnData = new stdClass();
        try {

            $VendorUID = (int) $this->input->post('VendorUID');
            $newStatus = (int) $this->input->post('IsActive');

            if (!$VendorUID) throw new Exception('Vendor ID is missing.');
            if (!in_array($newStatus, [0, 1])) throw new Exception('Invalid status value.');

            $this->load->model('dbwrite_model');
            $resp = $this->dbwrite_model->updateData(
                'Vendors', 'VendorTbl',
                ['IsActive' => $newStatus, 'UpdatedBy' => $this->pageData['JwtData']->User->UserUID],
                ['VendorUID' => $VendorUID]
            );
            if ($resp->Error) throw new Exception($resp->Message);
            $this->cachehelper->upsertVendor($VendorUID);

            $pageNo = (int) ($this->input->post('PageNo') ?: 1);
            $this->_initModule();
            $pageData = $this->_fetchTableData($pageNo, $this->pageData['Limit']);
            $this->EndReturnData->Error      = false;
            $this->EndReturnData->Message    = 'Status updated successfully.';
            $this->EndReturnData->Stats      = $this->vendors_model->getVendorStats($this->pageData['JwtData']->Org->OrgUID);
            $this->EndReturnData->List       = $pageData->RecordHtmlData;
            $this->EndReturnData->Pagination = $pageData->Pagination;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function deleteVendorData() {
        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $VendorUID = (int) $this->input->post('VendorUID');
            if (!$VendorUID) throw new Exception('Vendor Information is Missing to Delete');

            if ($this->vendorHasTransactions($VendorUID)) throw new Exception('Vendor has existing transactions (Purchase Orders/Payments)');

            $this->load->model('accountledger_model');
            $vendor = $this->accountledger_model->getEntityWithLedger($VendorUID, 'Vendor');
            if (!$vendor)             throw new Exception('Vendor not found');
            if ($vendor->IsDeleted == 1) throw new Exception('Vendor already deleted');

            $UpdateResp = $this->dbwrite_model->updateData('Vendors', 'VendorTbl', $this->globalservice->baseDeleteArrayDetails(), ['VendorUID' => $VendorUID]);
            if ($UpdateResp->Error) throw new Exception($UpdateResp->Message);

            if ($vendor->LedgerUID) {
                $this->load->library('accountledger');
                $this->accountledger->deactivateEntityLedger($VendorUID, $vendor->LedgerUID, 'Vendor');
            }

            $this->dbwrite_model->commitTransaction();

            // Remove deleted vendor from bulk search cache
            $this->cachehelper->removeVendor($VendorUID);

            $pageNo   = (int) ($this->input->post('PageNo') ?: 1);
            $this->_initModule();
            $pageData = $this->_fetchTableData($pageNo, $this->pageData['Limit']);

            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Message    = 'Deleted Successfully';
            $this->EndReturnData->List       = $pageData->RecordHtmlData;
            $this->EndReturnData->Pagination = $pageData->Pagination;
            $this->EndReturnData->Stats      = $this->vendors_model->getVendorStats($this->pageData['JwtData']->Org->OrgUID);

        } catch (Exception $e) {
            if (isset($this->dbwrite_model)) $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function deleteMultipleVendors() {
        $this->EndReturnData = new stdClass();
        try {

            $VendorUIDs = $this->input->post('VendorUIDs[]');
            if (empty($VendorUIDs)) throw new Exception('Vendor Information is Missing to Delete');

            if (!is_array($VendorUIDs)) $VendorUIDs = [$VendorUIDs];
            $VendorUIDs = array_filter(array_map('intval', $VendorUIDs));
            if (empty($VendorUIDs)) throw new Exception('Invalid vendor IDs provided');

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            foreach ($VendorUIDs as $vendorId) {
                if ($this->vendorHasTransactions($vendorId)) {
                    throw new Exception("Vendor ID {$vendorId} has existing transactions");
                }

                $this->load->model('accountledger_model');
                $vendor = $this->accountledger_model->getEntityWithLedger($vendorId, 'Vendor');
                if (!$vendor)              throw new Exception("Vendor ID {$vendorId} not found");
                if ($vendor->IsDeleted == 1) throw new Exception("Vendor ID {$vendorId} is already deleted");

                if ($vendor->LedgerUID) {
                    $this->load->library('accountledger');
                    $this->accountledger->deactivateEntityLedger($vendorId, $vendor->LedgerUID, 'Vendor');
                }
            }

            $UpdateResp = $this->dbwrite_model->updateData('Vendors', 'VendorTbl', $this->globalservice->baseDeleteArrayDetails(), [], ['VendorUID' => $VendorUIDs]);
            if ($UpdateResp->Error) throw new Exception($UpdateResp->Message);

            $this->dbwrite_model->commitTransaction();

            // Remove each deleted vendor from bulk search cache
            foreach ($VendorUIDs as $vid) {
                $this->cachehelper->removeVendor($vid);
            }

            $pageNo   = (int) ($this->input->post('PageNo') ?: 1);
            $this->_initModule();
            $pageData = $this->_fetchTableData($pageNo, $this->pageData['Limit']);

            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Message    = count($VendorUIDs) . ' vendor(s) deleted successfully';
            $this->EndReturnData->List       = $pageData->RecordHtmlData;
            $this->EndReturnData->Pagination = $pageData->Pagination;
            $this->EndReturnData->Stats      = $this->vendors_model->getVendorStats($this->pageData['JwtData']->Org->OrgUID);

        } catch (Exception $e) {
            if (isset($this->dbwrite_model)) $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    private function vendorHasTransactions($vendorId) {
        try {
            $this->load->model('transactions_model');
            return count($this->transactions_model->getEntityInvoices($vendorId, 'Vendor')) > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    private function _currentFinancialYear() {
        return (date('n') >= 4) ? (int)date('Y') : (int)date('Y') - 1;
    }

    // ── Send SMS / Email ─────────────────────────────────────────────────────
    public function sendCommunication() {

        $this->EndReturnData = new stdClass();
        $tempFiles = [];
        try {

            $orgUID    = $this->pageData['JwtData']->Org->OrgUID;
            $sentBy    = $this->pageData['JwtData']->User->UserUID;
            $commType  = $this->input->post('CommType');
            $message   = trim($this->input->post('Message', FALSE));
            $subject   = trim($this->input->post('Subject', FALSE) ?: '');
            $uids      = $this->input->post('UIDs');
            $moduleUID = (int) $this->input->post('ModuleUID');
            $recordUID = (int) $this->input->post('RecordUID');

            if (!in_array($commType, ['SMS', 'Email'])) throw new Exception('Invalid communication type.');
            if (empty($message))                         throw new Exception('Message cannot be empty.');
            if ($commType === 'Email' && empty($subject)) throw new Exception('Email subject is required.');
            if (empty($uids) || !is_array($uids))        throw new Exception('No recipients selected.');

            $uids = array_map('intval', $uids);

            $uploadDir = FCPATH . 'uploads/comm_tmp/';

            // Save uploaded attachments to a temp dir
            if ($commType === 'Email' && !empty($_FILES['Attachments']['name'][0])) {
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $files = $_FILES['Attachments'];
                $count = is_array($files['name']) ? count($files['name']) : 0;
                for ($i = 0; $i < min($count, 3); $i++) {
                    if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
                    $ext     = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                    if (!in_array($ext, ['pdf','jpg','jpeg','png'])) continue;
                    $tmpPath = $uploadDir . uniqid('attach_', true) . '.' . $ext;
                    if (move_uploaded_file($files['tmp_name'][$i], $tmpPath)) {
                        $tempFiles[] = $tmpPath;
                    }
                }
            }

            $this->load->library('communicationservice');

            if ($commType === 'SMS') {
                $result = $this->communicationservice->sendSMS($orgUID, $sentBy, 'Vendor', $uids, $message);
            } else {
                $result = $this->communicationservice->sendEmail($orgUID, $sentBy, 'Vendor', $uids, $subject, $message, $tempFiles);
            }

            if ($result->Error) throw new Exception($result->Message);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = $result->Message;
            $this->EndReturnData->Sent    = $result->Sent   ?? 0;
            $this->EndReturnData->Failed  = $result->Failed ?? 0;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        foreach ($tempFiles as $f) { if (is_file($f)) unlink($f); }
        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    // ── Vendor Opening Balance ────────────────────────────────────────────────

    public function saveVendorOpeningBalance() {

        $this->EndReturnData = new stdClass();
        try {

            $orgUID        = $this->pageData['JwtData']->Org->OrgUID;
            $userUID       = $this->pageData['JwtData']->User->UserUID;
            $vendorUID     = (int)   $this->input->post('VendorUID');
            $balance       = (float) $this->input->post('OpeningBalance');
            $balanceType   = trim($this->input->post('BalanceType'));
            $notes         = trim($this->input->post('Notes') ?? '');
            $financialYear = (int)   $this->input->post('FinancialYear');

            if ($vendorUID <= 0)                              throw new Exception('Invalid vendor.');
            if ($balance < 0)                                 throw new Exception('Opening balance cannot be negative.');
            if (!in_array($balanceType, ['Debit', 'Credit'])) throw new Exception('BalanceType must be Debit or Credit.');
            if ($financialYear <= 0) {
                $financialYear = $this->_currentFinancialYear();
            }

            $this->load->model('vendors_model');
            $id = $this->vendors_model->saveVendorOpeningBalance(
                $orgUID, $vendorUID, $balance, $balanceType, $notes, $userUID
            );
            $this->vendors_model->saveVendorYearOpening(
                $orgUID, $vendorUID, $financialYear, $balance, $balanceType, $userUID
            );

            $this->cachehelper->upsertVendor($vendorUID);

            $this->EndReturnData->Error         = FALSE;
            $this->EndReturnData->Message       = 'Opening balance saved successfully.';
            $this->EndReturnData->VendBalUID    = $id;
            $this->EndReturnData->FinancialYear = $financialYear;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function getVendorOpeningBalance() {

        $this->EndReturnData = new stdClass();
        try {

            $orgUID        = $this->pageData['JwtData']->Org->OrgUID;
            $vendorUID     = (int) $this->input->get_post('VendorUID');
            $financialYear = (int) $this->input->get_post('FinancialYear');

            if ($vendorUID <= 0) throw new Exception('Invalid vendor.');
            if ($financialYear <= 0) {
                $financialYear = $this->_currentFinancialYear();
            }

            $this->load->model('vendors_model');
            $current = $this->vendors_model->getVendorOpeningBalance($orgUID, $vendorUID);
            $yearRow = $this->vendors_model->getVendorYearOpening($orgUID, $vendorUID, $financialYear);

            $this->EndReturnData->Error         = FALSE;
            $this->EndReturnData->Data          = $current;
            $this->EndReturnData->YearData      = $yearRow;
            $this->EndReturnData->FinancialYear = $financialYear;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function getVendorSearchList() {

        $this->EndReturnData = new stdClass();
        try {

            $pageNo = (int) $this->input->post('PageNo') ?: 1;
            $limit  = (int) $this->input->post('RowLimit') ?: 10;
            $search = strtolower(trim($this->input->post('Search') ?? ''));

            $orgUID = $this->pageData['JwtData']->Org->OrgUID;
            $offset = max(0, ($pageNo - 1) * $limit);

            // Try Upstash cache first (org:{orgUID}:vendors HSET)
            $cacheKey = $this->redisservice->orgKey('vendors');
            $cached   = $this->upstashservice->hgetall($cacheKey);

            if (!empty($cached)) {
                $all = array_values($cached);

                // Filter by search term across name, mobile, area, company, contact
                if ($search !== '') {
                    $all = array_values(array_filter($all, function($v) use ($search) {
                        return strpos(strtolower($v['Name']          ?? ''), $search) !== false
                            || strpos(strtolower($v['MobileNumber']  ?? ''), $search) !== false
                            || strpos(strtolower($v['Area']          ?? ''), $search) !== false
                            || strpos(strtolower($v['CompanyName']   ?? ''), $search) !== false
                            || strpos(strtolower($v['ContactPerson'] ?? ''), $search) !== false;
                    }));
                }

                // Sort alphabetically by Name
                usort($all, function($a, $b) {
                    return strcasecmp($a['Name'] ?? '', $b['Name'] ?? '');
                });

                $total = count($all);
                $page  = array_slice($all, $offset, $limit);

                $this->EndReturnData->Vendors = array_map(function($v) {
                    $billingAddr = null;
                    foreach (($v['Address'] ?? []) as $addr) {
                        if (($addr['AddressType'] ?? '') === 'Billing') {
                            $billingAddr = [
                                'Line1'   => $addr['Line1']     ?? '',
                                'Line2'   => $addr['Line2']     ?? '',
                                'City'    => $addr['CityText']  ?? '',
                                'State'   => $addr['StateText'] ?? '',
                                'Pincode' => $addr['Pincode']   ?? '',
                            ];
                            break;
                        }
                    }
                    return [
                        'VendorUID'    => $v['VendorUID'],
                        'Name'         => $v['Name']           ?? '',
                        'Area'         => $v['Area']           ?? '',
                        'MobileNumber' => $v['MobileNumber']   ?? '',
                        'Balance'      => $v['OpeningBalance'] ?? 0,
                        'BalanceType'  => $v['OpeningBalType'] ?? 'Credit',
                        'address'      => $billingAddr,
                    ];
                }, $page);
                $this->EndReturnData->TotalCount = $total;

            } else {
                // DB fallback when cache is empty
                $filter = [];
                if ($search !== '') {
                    $filter['SearchAllData'] = $search;
                }

                $this->load->model('vendors_model');
                $result = $this->vendors_model->getVendorListPaginated($orgUID, $limit, $offset, $filter);

                $this->EndReturnData->Vendors = array_map(function($row) {
                    $vend = [
                        'VendorUID'    => $row->VendorUID,
                        'Name'         => $row->Name,
                        'Area'         => $row->Area          ?? '',
                        'MobileNumber' => $row->MobileNumber  ?? '',
                        'Balance'      => $row->ClosingBalance     ?? 0,
                        'BalanceType'  => $row->ClosingBalanceType ?? 'Credit',
                    ];
                    $addrInfo = $this->vendors_model->getVendorAddress(['VendAddress.VendorUID' => $row->VendorUID]);
                    foreach ($addrInfo as $addr) {
                        if ($addr->AddressType === 'Billing') {
                            $vend['address'] = [
                                'Line1'   => $addr->Line1     ?? '',
                                'Line2'   => $addr->Line2     ?? '',
                                'City'    => $addr->CityText  ?? '',
                                'State'   => $addr->StateText ?? '',
                                'Pincode' => $addr->Pincode   ?? '',
                            ];
                            break;
                        }
                    }
                    return $vend;
                }, $result->rows ?? []);
                $this->EndReturnData->TotalCount = $result->totalCount ?? 0;
            }

            $this->EndReturnData->Error = false;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = true;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function updateVendorBalance() {

        $this->EndReturnData = new stdClass();
        try {

            $orgUID    = $this->pageData['JwtData']->Org->OrgUID;
            $userUID   = $this->pageData['JwtData']->User->UserUID;
            $filterUID = (int) $this->input->post('VendorUID');

            $this->load->model('vendors_model');

            // Step 1: Fetch all vendors (or a single one) with their ledger info
            $vendors = $this->vendors_model->getVendorsWithLedgerForBalance($orgUID, $filterUID);

            if (empty($vendors)) {
                throw new Exception('No vendors found to update.');
            }

            $updated = 0;
            $skipped = 0;
            $errors  = [];

            foreach ($vendors as $vend) {
                try {

                    $vendUID = (int) $vend->VendorUID;

                    // Step 2: Fetch transaction totals
                    $totalPurchased = $this->vendors_model->getVendorTotalPurchased($orgUID, $vendUID);
                    $totalPaid      = $this->vendors_model->getVendorTotalPaid($orgUID, $vendUID);
                    $totalReturned  = $this->vendors_model->getVendorTotalReturned($orgUID, $vendUID);

                    // Step 3: Compute net balance
                    // Opening balance from VendOpeningBalanceTbl (Credit=+, Debit=-)
                    $signedOpening = ($vend->OpeningBalType === 'Credit')
                        ? (float)$vend->OpeningBalance
                        : -(float)$vend->OpeningBalance;

                    // net = opening + purchased - paid - returned
                    $signedBalance  = round($signedOpening + $totalPurchased - $totalPaid - $totalReturned, 2);
                    $newBalance     = abs($signedBalance);
                    $newBalanceType = ($signedBalance >= 0) ? 'Credit' : 'Debit';

                    // Step 4: Persist — update ledger current balance + VendOpeningBalanceTbl pending balance.
                    if (!empty($vend->LedgerUID)) {
                        $this->vendors_model->updateVendorBalanceInLedger(
                            $vend->LedgerUID, $newBalance, $newBalanceType, $userUID
                        );
                    }

                    $this->vendors_model->updateVendorPendingBalance(
                        $orgUID, $vendUID, $newBalance, $newBalanceType, $userUID
                    );

                    $updated++;

                } catch (Exception $innerEx) {
                    $errors[] = 'VendorUID ' . $vend->VendorUID . ': ' . $innerEx->getMessage();
                    $skipped++;
                }
            }

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = "Balance recalculated: {$updated} updated, {$skipped} skipped.";
            $this->EndReturnData->Updated = $updated;
            $this->EndReturnData->Skipped = $skipped;
            if (!empty($errors)) {
                $this->EndReturnData->Errors = $errors;
            }

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

}
