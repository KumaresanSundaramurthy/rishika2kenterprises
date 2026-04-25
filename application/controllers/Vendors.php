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

            $this->load->model('vendors_model');
            $this->pageData['VendStats'] = $this->vendors_model->getVendorStats($this->pageData['JwtData']->User->OrgUID);

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

}
