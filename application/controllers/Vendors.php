<?php defined('BASEPATH') or exit('No direct script access allowed');

class Vendors extends CI_Controller {

    public $pageData = array();
    private $EndReturnData;

    public function __construct() {
        parent::__construct();
    }

    // ── Internal helpers ──────────────────────────────────────────────────────

    private function _initModule() {
        if (isset($this->pageData['ModuleId'])) return;

        $controllerName = strtolower($this->router->fetch_class());
        $getModuleInfo  = $this->redis_cache->get('Redis_UserModuleInfo')->Value ?? [];
        $ModuleInfo     = array_values(array_filter($getModuleInfo, fn($m) => $m->ControllerName === $controllerName));
        if (empty($ModuleInfo)) {
            throw new Exception("Module information not found for controller: {$controllerName}");
        }

        $this->pageData['ModuleId']              = $ModuleInfo[0]->ModuleUID;
        $this->pageData['ModColumnData']         = $ModuleInfo[0]->DispViewColumns ?? [];
        $this->pageData['DispSettColumnDetails'] = $ModuleInfo[0]->DispSettingsViewColumns ?? [];

        $GeneralSettings = ($this->redis_cache->get('Redis_UserGenSettings')->Value) ?? new stdClass();
        $this->pageData['JwtData']->GenSettings = $GeneralSettings;
        $this->pageData['Limit'] = $GeneralSettings->RowLimit ?? 10;
    }

    private function _fetchTableData($pageNo, $limit, $filter = []) {
        $orgUID = $this->pageData['JwtData']->User->OrgUID;
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
        try {

            $this->_initModule();
            $limit = $this->pageData['Limit'];

            $pageData = $this->_fetchTableData(1, $limit);
            $this->pageData['ModRowData']    = $pageData->RecordHtmlData;
            $this->pageData['ModPagination'] = $pageData->Pagination;

            $this->loadCountryStateCityData();

            $this->load->model('vendors_model');
            $this->pageData['VendStats'] = $this->vendors_model->getVendorStats($this->pageData['JwtData']->User->OrgUID);
            $this->pageData['Tags']      = $this->vendors_model->getVendorTags($this->pageData['JwtData']->User->OrgUID);

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

        $OrgCountryISO2 = $this->pageData['JwtData']->User->OrgCISO2;
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
            'OrgUID'            => $this->pageData['JwtData']->User->OrgUID,
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

            $this->globalservice->saveBankDetails($VendorUID, $this->input->post('BankDetailsJSON'), 'Vendors', 'VendBankDetailsTbl');

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

            $linkCustomer = $PostData['CustomerLinkingCheck'] ?? null;
            if (!empty($linkCustomer) && (string) $linkCustomer === 'NewCustomer') {
                $insCustDataResp = $this->dbwrite_model->insertData('Customers', 'CustomerTbl', $vendorFormData);
                if ($insCustDataResp->Error) throw new Exception($insCustDataResp->Message);

                $this->globalservice->saveBankDetails($insCustDataResp->ID, $this->input->post('BankDetailsJSON'), 'Customers', 'CustBankDetailsTbl');
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

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Created Successfully';

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

            $VendorUID      = getPostValue($PostData, 'VendorUID', 0);
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
            $this->globalservice->saveBankDetails($PostData['VendorUID'], $this->input->post('BankDetailsJSON'), 'Vendors', 'VendBankDetailsTbl');

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

            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Updated Successfully';

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


    public function getVendorTags() {
        $this->EndReturnData = new stdClass();
        try {
            $this->load->model('vendors_model');
            $tags = $this->vendors_model->getVendorTags($this->pageData['JwtData']->User->OrgUID);
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
            $stats = $this->vendors_model->getVendorStats($this->pageData['JwtData']->User->OrgUID);
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

            $this->EndReturnData->Error   = false;
            $this->EndReturnData->Message = 'Status updated successfully.';
            
            $this->load->model('vendors_model');
            $this->EndReturnData->Stats   = $this->vendors_model->getVendorStats($this->pageData['JwtData']->User->OrgUID);

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

            $pageNo   = (int) ($this->input->post('PageNo') ?: 1);
            $this->_initModule();
            $pageData = $this->_fetchTableData($pageNo, $this->pageData['Limit']);

            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Message    = 'Deleted Successfully';
            $this->EndReturnData->List       = $pageData->RecordHtmlData;
            $this->EndReturnData->Pagination = $pageData->Pagination;
            $this->EndReturnData->Stats      = $this->vendors_model->getVendorStats($this->pageData['JwtData']->User->OrgUID);

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

            $pageNo   = (int) ($this->input->post('PageNo') ?: 1);
            $this->_initModule();
            $pageData = $this->_fetchTableData($pageNo, $this->pageData['Limit']);

            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Message    = count($VendorUIDs) . ' vendor(s) deleted successfully';
            $this->EndReturnData->List       = $pageData->RecordHtmlData;
            $this->EndReturnData->Pagination = $pageData->Pagination;
            $this->EndReturnData->Stats      = $this->vendors_model->getVendorStats($this->pageData['JwtData']->User->OrgUID);

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

    // ── Send SMS / Email ─────────────────────────────────────────────────────
    public function sendCommunication() {

        $this->EndReturnData = new stdClass();
        try {

            $orgUID   = $this->pageData['JwtData']->User->OrgUID;
            $sentBy   = $this->pageData['JwtData']->User->UserUID;
            $commType = $this->input->post('CommType');
            $message  = trim($this->input->post('Message'));
            $subject  = trim($this->input->post('Subject') ?: '');
            $uids     = $this->input->post('UIDs');

            if (!in_array($commType, ['SMS', 'Email'])) throw new Exception('Invalid communication type.');
            if (empty($message))                         throw new Exception('Message cannot be empty.');
            if ($commType === 'Email' && empty($subject)) throw new Exception('Email subject is required.');
            if (empty($uids) || !is_array($uids))        throw new Exception('No recipients selected.');

            $uids = array_map('intval', $uids);

            $this->load->library('communicationservice');

            if ($commType === 'SMS') {
                $result = $this->communicationservice->sendSMS($orgUID, $sentBy, 'Vendor', $uids, $message);
            } else {
                $result = $this->communicationservice->sendEmail($orgUID, $sentBy, 'Vendor', $uids, $subject, $message);
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

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

}
