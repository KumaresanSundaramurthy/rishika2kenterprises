<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Settings extends MY_Controller {

    public  $pageData = [];
    private $EndReturnData;

    /** Returns [ModuleUID => Name] map from Modules.ModuleTbl where IsThermalPrint = 1 */
    private function getThermalTransTypes() {
        $this->load->model('organisation_model');
        $result = $this->organisation_model->getThermalPrintModules();
        $types  = [];
        foreach ($result->Data ?? [] as $row) {
            $types[(int)$row->ModuleUID] = $row->Name;
        }
        return $types;
    }

    /** Returns [ModuleUID => Name] map from Modules.ModuleTbl where IsPrefix = 1 */
    private function getPrefixModulesList() {
        $this->load->model('organisation_model');
        $result = $this->organisation_model->getPrefixModules();
        $types  = [];
        foreach ($result->Data ?? [] as $row) {
            $types[(int)$row->ModuleUID] = $row->Name;
        }
        return $types;
    }

    public function __construct() {
        parent::__construct();
    }

    // ── General Settings page ────────────────────────────────────────────────

    public function generalsettings() {
        $this->_loadPageTitle();
        if (empty($this->pageData['PageTitle'])) $this->pageData['PageTitle'] = 'Settings';
        try {
            $orgUID  = (int) $this->pageData['JwtData']->User->OrgUID;
            $userUID = (int) $this->pageData['JwtData']->User->UserUID;

            $this->load->model('login_model');
            $loginExpiry = (int) getenv('LOGIN_EXPIRE_SECS') ?: 86400;

            // ── General Settings — read from JWT payload (no Redis lookup) ───
            $genSettings = $this->pageData['JwtData']->GenSettings ?? null;
            if (empty($genSettings)) {
                // Fallback: DB read if session predates this change
                $result      = $this->login_model->getOrgGeneralSettings($orgUID);
                $genSettings = (!$result->Error && !empty($result->Data)) ? $result->Data[0] : new stdClass();
            }
            $this->pageData['GenSettings'] = $genSettings;

            // ── Product Settings — read from JWT payload (no Redis lookup) ───
            $prodSettings = $this->pageData['JwtData']->ProdSettings ?? null;
            if (empty($prodSettings)) {
                // Fallback: DB read if session predates this change
                $result       = $this->login_model->getProductSettings($orgUID);
                $prodSettings = (!$result->Error && !empty($result->Data)) ? $result->Data[0] : new stdClass();
            }
            $this->pageData['ProdSettings'] = $prodSettings;

            // ── Lookup dropdowns for Product Settings ─────────────────────────
            $this->load->model('global_model');
            $this->pageData['DiscTypeInfo'] = $this->global_model->getDiscountTypeInfo()->Data ?? [];
            $this->pageData['ProdTypeInfo'] = $this->global_model->getProductTypeInfo()->Data  ?? [];
            $this->pageData['ProdTaxInfo']  = $this->global_model->getProductTaxInfo()->Data   ?? [];
            $this->pageData['TaxDetInfo']   = $this->global_model->getTaxDetailsInfo()->Data   ?? [];

            $this->load->view('settings/generalsettings/view', $this->pageData);
        } catch (Exception $e) {
            redirect('dashboard', 'refresh');
        }
    }

    /** AJAX POST: save Product Settings (OrgProductSettingsTbl) */
    public function updateProductSettings() {

        $this->EndReturnData = new stdClass();
        try {

            $orgUID  = (int) $this->pageData['JwtData']->User->OrgUID;
            $userUID = (int) $this->pageData['JwtData']->User->UserUID;
            $post    = $this->input->post();

            $productTypeUID  = (int) getPostValue($post, 'DefaultProductTypeUID');
            $discountTypeUID = (int) getPostValue($post, 'DefaultDiscountTypeUID');
            $productTaxUID   = (int) getPostValue($post, 'DefaultProductTaxUID');
            $taxDetailUID    = (int) getPostValue($post, 'DefaultTaxDetailUID');

            if ($productTypeUID  <= 0) throw new Exception('Please select a default product type.');
            if ($discountTypeUID <= 0) throw new Exception('Please select a default discount type.');
            if ($productTaxUID   <= 0) throw new Exception('Please select a default product tax.');
            if ($taxDetailUID    <= 0) throw new Exception('Please select a default tax percentage.');

            $data = [
                'DefaultProductTypeUID'  => $productTypeUID,
                'DefaultDiscountTypeUID' => $discountTypeUID,
                'DefaultProductTaxUID'   => $productTaxUID,
                'DefaultTaxDetailUID'    => $taxDetailUID,
                'UpdatedBy'              => $userUID,
            ];

            // Upsert via INSERT ... ON DUPLICATE KEY UPDATE
            // OrgUID is the PRIMARY KEY — single safe query, no existence check needed
            $writeDB = $this->load->database('WriteDB', TRUE);
            $writeDB->db_debug = FALSE;
            $sql = "INSERT INTO Settings.OrgProductSettingsTbl
                        (OrgUID, DefaultProductTypeUID, DefaultDiscountTypeUID, DefaultProductTaxUID, DefaultTaxDetailUID, UpdatedBy)
                    VALUES (?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        DefaultProductTypeUID  = VALUES(DefaultProductTypeUID),
                        DefaultDiscountTypeUID = VALUES(DefaultDiscountTypeUID),
                        DefaultProductTaxUID   = VALUES(DefaultProductTaxUID),
                        DefaultTaxDetailUID    = VALUES(DefaultTaxDetailUID),
                        UpdatedBy              = VALUES(UpdatedBy)";

            $ok = $writeDB->query($sql, [
                $orgUID, $productTypeUID, $discountTypeUID, $productTaxUID, $taxDetailUID, $userUID
            ]);
            if (!$ok) {
                $err = $writeDB->error();
                throw new Exception($err['message'] ?? 'Failed to save product settings.');
            }

            // Patch ONLY ProdSettings in the main JWT payload — takes effect on very next request
            $this->load->model('login_model');
            $fresh = $this->login_model->getProductSettings($orgUID);
            if (!$fresh->Error && !empty($fresh->Data)) {
                $jwtKey      = $this->pageData['JwtUserKey'] ?? null;
                $redisPayload = $jwtKey ? $this->redisservice->getCache($jwtKey) : null;
                if ($redisPayload && !$redisPayload->Error && !empty($redisPayload->Value)) {
                    $redisPayload->Value->ProdSettings = $fresh->Data[0];
                    $this->redisservice->setCache($jwtKey, $redisPayload->Value, $redisPayload->TTL);
                }
            }

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Product settings saved successfully.';

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    /** AJAX POST: save General Settings (OrgSettingsTbl) */
    public function updateGeneralSettings() {

        $this->EndReturnData = new stdClass();
        try {

            $orgUID  = (int) $this->pageData['JwtData']->User->OrgUID;
            $userUID = (int) $this->pageData['JwtData']->User->UserUID;
            $post    = $this->input->post();

            // Validate & sanitize
            $decimalPoints = in_array((int)getPostValue($post, 'DecimalPoints'), [0, 2, 3])
                ? (int)getPostValue($post, 'DecimalPoints') : 2;

            // Currency: exactly 1 character
            $currencySymbol = trim(getPostValue($post, 'CurrenySymbol') ?: '₹');
            $currencySymbol = mb_substr($currencySymbol, 0, 1);
            if (!$currencySymbol) throw new Exception('Currency symbol is required (1 character).');

            $fyStartMonth = (int)getPostValue($post, 'FYStartMonth');
            if ($fyStartMonth < 1 || $fyStartMonth > 12) $fyStartMonth = 4;

            $rowLimit = (int)getPostValue($post, 'RowLimit');
            if (!in_array($rowLimit, [10, 25, 50, 100])) $rowLimit = 10;

            $qtyMaxLength = (int)getPostValue($post, 'QtyMaxLength');
            if ($qtyMaxLength < 1 || $qtyMaxLength > 15) $qtyMaxLength = 6;

            $priceMaxLength = (int)getPostValue($post, 'PriceMaxLength');
            if ($priceMaxLength < 1 || $priceMaxLength > 20) $priceMaxLength = 12;

            $serialNoDisplay  = getPostValue($post, 'SerialNoDisplay')  ? 1 : 0;
            $enableStorage    = getPostValue($post, 'EnableStorage')    ? 1 : 0;
            $mandatoryStorage = getPostValue($post, 'MandatoryStorage') ? 1 : 0;
            // MandatoryStorage only valid if EnableStorage is on
            if (!$enableStorage) $mandatoryStorage = 0;

            $data = [
                'DecimalPoints'   => $decimalPoints,
                'CurrenySymbol'   => $currencySymbol,
                'SerialNoDisplay' => $serialNoDisplay,
                'FYStartMonth'    => $fyStartMonth,
                'RowLimit'        => $rowLimit,
                'QtyMaxLength'    => $qtyMaxLength,
                'PriceMaxLength'  => $priceMaxLength,
                'EnableStorage'   => $enableStorage,
                'MandatoryStorage'=> $mandatoryStorage,
            ];

            $this->load->model('dbwrite_model');
            $resp = $this->dbwrite_model->updateData(
                'Settings', 'OrgSettingsTbl',   // moved from Organisation → Settings
                $data,
                ['OrgUID' => $orgUID]
            );
            if ($resp->Error) throw new Exception($resp->Message);

            // Patch ONLY GenSettings in the main JWT payload — takes effect on very next request
            $this->load->model('login_model');
            $freshSettings = $this->login_model->getOrgGeneralSettings($orgUID);
            if (!$freshSettings->Error && !empty($freshSettings->Data)) {
                $jwtKey      = $this->pageData['JwtUserKey'] ?? null;
                $redisPayload = $jwtKey ? $this->redisservice->getCache($jwtKey) : null;
                if ($redisPayload && !$redisPayload->Error && !empty($redisPayload->Value)) {
                    $redisPayload->Value->GenSettings = $freshSettings->Data[0];
                    $this->redisservice->setCache($jwtKey, $redisPayload->Value, $redisPayload->TTL);
                }
            }

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Settings saved successfully.';

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    // ── Separate settings pages ──────────────────────────────────────────────

    public function thermalconfig() {
        $this->pageData['PageTitle'] = 'Thermal Print Config';
        try {
            $this->load->model('organisation_model');
            $orgUID = $this->pageData['JwtData']->User->OrgUID;
            $this->pageData['OrgPreviewData']   = $this->organisation_model->getOrgInfoCached($orgUID)->Data;
            $this->pageData['ThermalTypeCount'] = count($this->getThermalTransTypes());
            $this->load->view('settings/thermalconfig/view', $this->pageData);
        } catch (Exception $e) {
            redirect('dashboard', 'refresh');
        }
    }

    public function banks() {
        $this->pageData['PageTitle'] = 'Bank Accounts';
        try {
            $this->load->view('settings/banks/view', $this->pageData);
        } catch (Exception $e) {
            redirect('dashboard', 'refresh');
        }
    }

    public function msgtemplates() {
        $this->pageData['PageTitle'] = 'Message Templates';
        try {
            $this->load->view('settings/msgtemplates/view', $this->pageData);
        } catch (Exception $e) {
            redirect('dashboard', 'refresh');
        }
    }

    // ── Thermal Print Config ─────────────────────────────────────────────────

    /** AJAX: return table rows HTML + used types list */
    public function getThermalConfigList() {

        $this->EndReturnData = new stdClass();
        try {

            $orgUID = $this->pageData['JwtData']->User->OrgUID;

            $this->load->model('organisation_model');
            $result = $this->organisation_model->getThermalPrintConfigList($orgUID);
            $rows   = $result->Error === FALSE ? $result->Data : [];
            $transTypes = $this->getThermalTransTypes();

            $rowHtml = $this->load->view('settings/thermalconfig/list', [
                'DataLists'    => $rows,
                'TransTypes'   => $transTypes,
                'JwtData'      => $this->pageData['JwtData'],
            ], TRUE);

            $usedTypes = array_map(fn($r) => (int)$r->ModuleUID, $rows);

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->RecordHtmlData = $rowHtml;
            $this->EndReturnData->UsedTypes      = $usedTypes;
            $this->EndReturnData->TotalCount     = count($rows);
            $this->EndReturnData->TransTypes     = $transTypes;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    /** AJAX POST: insert or update a thermal config row */
    public function saveThermalConfig() {

        $this->EndReturnData = new stdClass();
        try {

            $PostData    = $this->input->post();
            $orgUID      = $this->pageData['JwtData']->User->OrgUID;
            $userUID     = $this->pageData['JwtData']->User->UserUID;
            $configUID = (int) getPostValue($PostData, 'ThermalConfigUID');
            $moduleUID = (int) getPostValue($PostData, 'ModuleUID');

            if (!array_key_exists($moduleUID, $this->getThermalTransTypes())) {
                throw new Exception('Invalid module / transaction type.');
            }

            // Duplicate check — only one config per module per org
            if ($configUID <= 0) {
                $this->load->model('organisation_model');
                $existing = $this->organisation_model->getThermalPrintConfigByModule($orgUID, $moduleUID);
                if (!empty($existing->Data)) {
                    $typeName = $this->getThermalTransTypes()[$moduleUID] ?? $moduleUID;
                    throw new Exception('A thermal config for "' . $typeName . '" already exists. Please edit it instead.');
                }
            }

            $paperWidth = in_array(getPostValue($PostData, 'PaperWidth'), ['58mm', '80mm']) ? getPostValue($PostData, 'PaperWidth') : '80mm';
            $orgSize    = max(8, min(40, (int)(getPostValue($PostData, 'OrgNameFontSize') ?: 16)));
            $coSize     = max(8, min(40, (int)(getPostValue($PostData, 'CompanyNameFontSize') ?: 14)));
            $prodSize   = max(8, min(40, (int)(getPostValue($PostData, 'ProductInfoFontSize') ?: 12)));

            $configData = [
                // Printer
                'PaperWidth'            => $paperWidth,
                // Footer
                'FooterMessage'         => substr(getPostValue($PostData, 'FooterMessage') ?: '', 0, 500) ?: NULL,
                // Receipt Elements
                'ShowTerms'             => (int)(bool)getPostValue($PostData, 'ShowTerms'),
                'ShowCompanyDetails'    => (int)(bool)getPostValue($PostData, 'ShowCompanyDetails'),
                'ShowItemDescription'   => in_array($moduleUID, [110, 111]) ? 0 : (int)(bool)getPostValue($PostData, 'ShowItemDescription'),
                'ShowTaxableAmount'     => in_array($moduleUID, [110, 111]) ? 0 : (int)(bool)getPostValue($PostData, 'ShowTaxableAmount'),
                'ShowHSN'               => in_array($moduleUID, [110, 111]) ? 0 : (int)(bool)getPostValue($PostData, 'ShowHSN'),
                'ShowTaxBreakdown'      => in_array($moduleUID, [110, 111]) ? 0 : (int)(bool)getPostValue($PostData, 'ShowTaxBreakdown'),
                'ShowGSTIN'             => (int)(bool)getPostValue($PostData, 'ShowGSTIN'),
                'ShowMobile'            => (int)(bool)getPostValue($PostData, 'ShowMobile'),
                'ShowCashReceived'      => (int)(bool)getPostValue($PostData, 'ShowCashReceived'),
                'ShowLogo'              => (int)(bool)getPostValue($PostData, 'ShowLogo'),
                // QR Codes
                'ShowGoogleReviewQR'    => (int)(bool)getPostValue($PostData, 'ShowGoogleReviewQR'),
                'ShowPaymentQR'         => (int)(bool)getPostValue($PostData, 'ShowPaymentQR'),
                // Branding
                'OrgNameFontSize'       => $orgSize,
                'CompanyNameFontSize'   => $coSize,
                'ProductInfoFontSize'   => $prodSize,
                'UpdatedBy'             => $userUID,
            ];

            $this->load->model('dbwrite_model');

            if ($configUID > 0) {
                // Update existing row
                $this->dbwrite_model->updateData('Organisation', 'ThermalPrintConfigTbl', $configData, ['ThermalConfigUID' => $configUID, 'OrgUID' => $orgUID,'IsDeleted' => 0]);
                $this->EndReturnData->Message = 'Thermal print config updated.';
            } else {
                // Insert new row
                $configData['OrgUID']           = $orgUID;
                $configData['ModuleUID']         = $moduleUID;
                $configData['TransactionType']   = $this->getThermalTransTypes()[$moduleUID] ?? 'Unknown';
                $configData['CreatedBy']         = $userUID;
                $configData['IsActive']          = 1;
                $configData['IsDeleted']         = 0;
                $resp = $this->dbwrite_model->insertData('Organisation', 'ThermalPrintConfigTbl', $configData);
                $this->EndReturnData->Message = 'Thermal print config saved.';
            }

            // Return updated list inline — no second AJAX call needed
            $updatedRows = $this->organisation_model->getThermalPrintConfigList($orgUID);
            $updatedData = $updatedRows->Error === FALSE ? $updatedRows->Data : [];
            $transTypes  = $this->getThermalTransTypes();

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->RecordHtmlData = $this->load->view('settings/thermalconfig/list', [
                'DataLists'  => $updatedData,
                'TransTypes' => $transTypes,
                'JwtData'    => $this->pageData['JwtData'],
            ], TRUE);
            $this->EndReturnData->UsedTypes  = array_map(fn($r) => (int)$r->ModuleUID, $updatedData);
            $this->EndReturnData->TransTypes = $transTypes;
            $this->EndReturnData->TotalCount = count($updatedData);

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    // ── Bank Accounts ────────────────────────────────────────────────────────

    /** AJAX: return bank list HTML */
    public function getBankList() {

        $this->EndReturnData = new stdClass();
        try {

            $orgUID = $this->pageData['JwtData']->User->OrgUID;
            $this->load->model('organisation_model');
            $result = $this->organisation_model->getBankAccountList($orgUID);
            $rows   = $result->Error === FALSE ? $result->Data : [];

            $rowHtml = $this->load->view('settings/banks/list', [
                'DataLists' => $rows,
                'JwtData'   => $this->pageData['JwtData'],
            ], TRUE);

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->RecordHtmlData = $rowHtml;
            $this->EndReturnData->TotalCount     = count($rows);

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    /** AJAX POST: return calculated current balance for a bank account */
    public function getBankBalance() {

        $this->EndReturnData = new stdClass();
        try {

            $PostData = $this->input->post();
            $orgUID   = $this->pageData['JwtData']->User->OrgUID;
            $bankUID  = (int) getPostValue($PostData, 'BankAccountUID');

            if ($bankUID <= 0) throw new Exception('Invalid bank account ID.');

            $this->load->model('organisation_model');
            $result = $this->organisation_model->getBankBalance($bankUID, $orgUID);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Balance = $result->Balance;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    /** AJAX POST: get a single bank account for editing */
    public function getBankDetail() {

        $this->EndReturnData = new stdClass();
        try {

            $PostData = $this->input->post();
            $orgUID   = $this->pageData['JwtData']->User->OrgUID;
            $bankUID  = (int) getPostValue($PostData, 'BankAccountUID');

            if ($bankUID <= 0) throw new Exception('Invalid bank account ID.');

            $this->load->model('organisation_model');
            $result = $this->organisation_model->getBankAccountByUID($bankUID, $orgUID);
            if (!$result->Data) throw new Exception('Bank account not found.');

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $result->Data;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    /** AJAX POST: create or update a bank account */
    public function saveBankDetail() {

        $this->EndReturnData = new stdClass();
        try {

            $PostData     = $this->input->post();
            $orgUID       = $this->pageData['JwtData']->User->OrgUID;
            $userUID      = $this->pageData['JwtData']->User->UserUID;
            $bankUID      = (int) getPostValue($PostData, 'BankAccountUID');
            $accountName  = trim(getPostValue($PostData, 'AccountName') ?: '');
            $accountNo    = trim(getPostValue($PostData, 'AccountNumber') ?: '');
            $confirmNo    = trim(getPostValue($PostData, 'ConfirmAccountNumber') ?: '');
            $ifsc         = strtoupper(trim(getPostValue($PostData, 'IFSC') ?: ''));
            $bankName     = trim(getPostValue($PostData, 'BankName') ?: '');
            $branchName   = trim(getPostValue($PostData, 'BranchName') ?: '');
            $upiId        = trim(getPostValue($PostData, 'UPIId') ?: '') ?: NULL;
            $upiNumber    = trim(getPostValue($PostData, 'UPINumber') ?: '') ?: NULL;
            $openingBal   = (float) (getPostValue($PostData, 'OpeningBalance') ?: 0);
            $notes        = trim(getPostValue($PostData, 'Notes') ?: '') ?: NULL;
            $isDefault    = (int)(bool) getPostValue($PostData, 'IsDefault');

            if (!$accountName) throw new Exception('Account Holder Name is required.');
            if (!$accountNo)   throw new Exception('Account Number is required.');
            if ($bankUID <= 0 && $accountNo !== $confirmNo) throw new Exception('Account numbers do not match.');
            if (!$ifsc)        throw new Exception('IFSC Code is required.');
            if (!$bankName)    throw new Exception('Bank Name is required.');
            if (!$branchName)  throw new Exception('Branch Name is required.');

            $this->load->model('dbwrite_model');

            if ($isDefault) {
                $this->dbwrite_model->updateData(
                    'Transaction', 'OrgBankAccountsTbl',
                    ['IsDefault' => 0, 'UpdatedBy' => $userUID],
                    ['OrgUID' => $orgUID, 'IsDeleted' => 0]
                );
            }

            $data = [
                'AccountName'    => $accountName,
                'AccountNumber'  => $accountNo,
                'IFSC'           => $ifsc,
                'BankName'       => $bankName,
                'BranchName'     => $branchName,
                'UPIId'          => $upiId,
                'UPINumber'      => $upiNumber,
                'OpeningBalance' => $openingBal,
                'Notes'          => $notes,
                'IsDefault'      => $isDefault,
                'UpdatedBy'      => $userUID,
            ];

            if ($bankUID > 0) {
                $this->dbwrite_model->updateData(
                    'Transaction', 'OrgBankAccountsTbl', $data,
                    ['BankAccountUID' => $bankUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0, 'IsCash' => 0]
                );
                $this->EndReturnData->Message = 'Bank account updated successfully.';
            } else {
                $data['OrgUID']    = $orgUID;
                $data['IsCash']    = 0;
                $data['IsActive']  = 1;
                $data['IsDeleted'] = 0;
                $data['CreatedBy'] = $userUID;
                $this->dbwrite_model->insertData('Transaction', 'OrgBankAccountsTbl', $data);
                $this->EndReturnData->Message = 'Bank account added successfully.';
            }

            $this->EndReturnData->Error = FALSE;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    /** AJAX POST: soft-delete a bank account */
    public function deleteBankDetail() {

        $this->EndReturnData = new stdClass();
        try {

            $PostData = $this->input->post();
            $orgUID   = $this->pageData['JwtData']->User->OrgUID;
            $userUID  = $this->pageData['JwtData']->User->UserUID;
            $bankUID  = (int) getPostValue($PostData, 'BankAccountUID');

            if ($bankUID <= 0) throw new Exception('Invalid bank account ID.');

            $this->load->model('organisation_model');
            $row = $this->organisation_model->getBankAccountByUID($bankUID, $orgUID);
            if (!$row->Data) throw new Exception('Bank account not found.');
            if ($row->Data->IsCash) throw new Exception('Cash account cannot be deleted.');

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->updateData(
                'Transaction', 'OrgBankAccountsTbl',
                ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID],
                ['BankAccountUID' => $bankUID, 'OrgUID' => $orgUID]
            );

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Bank account deleted successfully.';

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    /** AJAX POST: set a bank as the default */
    public function setDefaultBank() {

        $this->EndReturnData = new stdClass();
        try {

            $PostData = $this->input->post();
            $orgUID   = $this->pageData['JwtData']->User->OrgUID;
            $userUID  = $this->pageData['JwtData']->User->UserUID;
            $bankUID  = (int) getPostValue($PostData, 'BankAccountUID');

            if ($bankUID <= 0) throw new Exception('Invalid bank account ID.');

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->updateData(
                'Transaction', 'OrgBankAccountsTbl',
                ['IsDefault' => 0, 'UpdatedBy' => $userUID],
                ['OrgUID' => $orgUID, 'IsDeleted' => 0]
            );
            $this->dbwrite_model->updateData(
                'Transaction', 'OrgBankAccountsTbl',
                ['IsDefault' => 1, 'UpdatedBy' => $userUID],
                ['BankAccountUID' => $bankUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Default bank updated.';

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    /** AJAX POST: internal fund transfer between bank accounts */
    public function transferFunds() {

        $this->EndReturnData = new stdClass();
        try {

            $PostData    = $this->input->post();
            $orgUID      = $this->pageData['JwtData']->User->OrgUID;
            $userUID     = $this->pageData['JwtData']->User->UserUID;
            $fromUID     = (int) getPostValue($PostData, 'FromBankUID');
            $toUID       = (int) getPostValue($PostData, 'ToBankUID');
            $amount      = (float) getPostValue($PostData, 'Amount');
            $transDate   = trim(getPostValue($PostData, 'TransferDate') ?: date('Y-m-d'));
            $referenceNo = trim(getPostValue($PostData, 'ReferenceNo') ?: '') ?: NULL;
            $notes       = trim(getPostValue($PostData, 'Notes') ?: '') ?: NULL;

            if ($fromUID <= 0) throw new Exception('Please select source account.');
            if ($toUID <= 0)   throw new Exception('Please select destination account.');
            if ($fromUID === $toUID) throw new Exception('Source and destination cannot be the same.');
            if ($amount <= 0)  throw new Exception('Transfer amount must be greater than zero.');

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->insertData('Transaction', 'FundTransfersTbl', [
                'OrgUID'       => $orgUID,
                'FromBankUID'  => $fromUID,
                'ToBankUID'    => $toUID,
                'Amount'       => $amount,
                'TransferDate' => $transDate,
                'ReferenceNo'  => $referenceNo,
                'Notes'        => $notes,
                'IsActive'     => 1,
                'IsDeleted'    => 0,
                'CreatedBy'    => $userUID,
                'UpdatedBy'    => $userUID,
            ]);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Funds transferred successfully.';

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    // ── Message Templates ────────────────────────────────────────────────────

    private static $MSG_TOKENS = [
        // Common
        '{{PARTY_NAME}}'      => 'Customer / Vendor full name',
        '{{DOC_NUMBER}}'      => 'Document number (e.g. INV-001)',
        '{{DOC_DATE}}'        => 'Document date',
        '{{DOC_TYPE}}'        => 'Document type (Invoice, Quotation…)',
        '{{AMOUNT}}'          => 'Total amount with currency',
        '{{CURRENCY}}'        => 'Currency symbol',
        // Payment specific
        '{{RECEIPT_NUMBER}}'  => 'Payment receipt number',
        '{{PAYMENT_MODE}}'    => 'Payment mode (Cash, UPI…)',
        '{{PAYMENT_STATUS}}'  => 'Payment status (Paid / Partially Paid)',
        '{{RECEIPT_LINK}}'    => 'Public receipt link URL',
        // Org
        '{{ORG_NAME}}'        => 'Organisation / Brand name',
        '{{ORG_PHONE}}'       => 'Organisation phone number',
        '{{ORG_EMAIL}}'       => 'Organisation email address',
        '{{ORG_ADDRESS}}'     => 'Organisation address',
        '{{ORG_GSTIN}}'       => 'Organisation GSTIN',
        // Validity
        '{{VALID_UNTIL}}'     => 'Validity / due date',
        '{{BALANCE_AMOUNT}}'  => 'Pending / balance amount',
    ];

    public function getMsgTemplateDetail() {
        $this->EndReturnData = new stdClass();
        try {
            $orgUID      = $this->pageData['JwtData']->User->OrgUID;
            $templateUID = (int) $this->input->get_post('TemplateUID');
            if ($templateUID <= 0) throw new Exception('Invalid template.');

            $this->load->model('organisation_model');
            $getData = $this->organisation_model->getMessageTemplateByUID($templateUID, $orgUID);
            if ($getData->Error) throw new Exception('Template not found.');
            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $getData->Data;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function getMsgTemplateList() {
        $this->EndReturnData = new stdClass();
        try {
            $orgUID  = $this->pageData['JwtData']->User->OrgUID;
            $this->load->model('organisation_model');
            $result  = $this->organisation_model->getMessageTemplates($orgUID);
            $rows    = $result->Error === FALSE ? $result->Data : [];
            $modules = $this->getThermalTransTypes();

            $rowHtml = $this->load->view('settings/msgtemplates/list', [
                'DataLists' => $rows,
                'Modules'   => $modules,
                'JwtData'   => $this->pageData['JwtData'],
            ], TRUE);

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->RecordHtmlData = $rowHtml;
            $this->EndReturnData->TotalCount     = count($rows);
            $this->EndReturnData->Modules        = $modules;
            $this->EndReturnData->Tokens         = self::$MSG_TOKENS;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function saveMsgTemplate() {
        $this->EndReturnData = new stdClass();
        try {
            $PostData    = $this->input->post();
            $orgUID      = $this->pageData['JwtData']->User->OrgUID;
            $userUID     = $this->pageData['JwtData']->User->UserUID;
            $templateUID = (int) getPostValue($PostData, 'TemplateUID');
            $moduleUID   = (int) getPostValue($PostData, 'ModuleUID');
            $channel     = getPostValue($PostData, 'Channel');
            $subject     = trim(getPostValue($PostData, 'Subject') ?: '') ?: NULL;
            $body        = trim(getPostValue($PostData, 'Body') ?: '');

            if (!in_array($channel, ['Email', 'WhatsApp', 'SMS'])) throw new Exception('Invalid channel.');
            if (!$moduleUID) throw new Exception('Please select a transaction type.');
            if (!$body)      throw new Exception('Template body is required.');

            $this->load->model('dbwrite_model');
            $data = [
                'ModuleUID'  => $moduleUID,
                'Channel'    => $channel,
                'Subject'    => $subject,
                'Body'       => $body,
                'IsActive'   => 1,
                'IsDeleted'  => 0,
                'UpdatedBy'  => $userUID,
            ];

            if ($templateUID > 0) {
                $this->dbwrite_model->updateData('Organisation', 'MessageTemplatesTbl', $data,
                    ['TemplateUID' => $templateUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]);
                $this->EndReturnData->Message = 'Template updated.';
            } else {
                $data['OrgUID']    = $orgUID;
                $data['CreatedBy'] = $userUID;
                $this->dbwrite_model->insertData('Organisation', 'MessageTemplatesTbl', $data);
                $this->EndReturnData->Message = 'Template saved.';
            }

            $this->load->model('organisation_model');
            $rows    = $this->organisation_model->getMessageTemplates($orgUID);
            $modules = $this->getThermalTransTypes();
            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->RecordHtmlData = $this->load->view('settings/msgtemplates/list', [
                'DataLists' => $rows->Data ?? [],
                'Modules'   => $modules,
                'JwtData'   => $this->pageData['JwtData'],
            ], TRUE);
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    public function deleteMsgTemplate() {
        $this->EndReturnData = new stdClass();
        try {
            $PostData    = $this->input->post();
            $orgUID      = $this->pageData['JwtData']->User->OrgUID;
            $userUID     = $this->pageData['JwtData']->User->UserUID;
            $templateUID = (int) getPostValue($PostData, 'TemplateUID');
            if ($templateUID <= 0) throw new Exception('Invalid template.');
            $this->load->model('dbwrite_model');
            $this->dbwrite_model->updateData('Organisation', 'MessageTemplatesTbl',
                ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID],
                ['TemplateUID' => $templateUID, 'OrgUID' => $orgUID]);
            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Template deleted.';
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    // ── Prefix Configuration page ────────────────────────────────────────────

    public function prefixconfig() {
        $this->pageData['PageTitle'] = 'Prefix Configuration';
        try {
            $this->load->model('organisation_model');
            $this->pageData['PrefixModuleCount'] = count($this->getPrefixModulesList());
            $this->load->view('settings/prefixconfig/view', $this->pageData);
        } catch (Exception $e) {
            redirect('dashboard', 'refresh');
        }
    }

    /** AJAX POST: return prefix list HTML + module map */
    public function getPrefixConfigList() {
        $this->EndReturnData = new stdClass();
        try {
            $orgUID  = $this->pageData['JwtData']->User->OrgUID;
            $this->load->model('organisation_model');
            $result  = $this->organisation_model->getPrefixConfigList($orgUID);
            $rows    = $result->Error === FALSE ? $result->Data : [];
            $modules = $this->getPrefixModulesList();

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->RecordHtmlData = $this->load->view('settings/prefixconfig/list', [
                'DataLists' => $rows,
                'Modules'   => $modules,
                'JwtData'   => $this->pageData['JwtData'],
            ], TRUE);
            $this->EndReturnData->TotalCount = count($rows);
            $this->EndReturnData->Modules    = $modules;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    /** AJAX POST: create or update a prefix configuration */
    public function savePrefixConfig() {
        $this->EndReturnData = new stdClass();
        try {
            $PostData  = $this->input->post();
            $orgUID    = $this->pageData['JwtData']->User->OrgUID;
            $userUID   = $this->pageData['JwtData']->User->UserUID;
            $prefixUID = (int) getPostValue($PostData, 'prePrefixUID');
            $moduleUID = (int) getPostValue($PostData, 'preModuleUID');
            $name      = strtoupper(trim(getPostValue($PostData, 'transPrefixName') ?: ''));

            if (!$name || strlen($name) < 2 || strlen($name) > 7 || !preg_match('/^[A-Z0-9]+$/', $name)) {
                throw new Exception('Prefix name must be 2–7 alphanumeric characters.');
            }
            if ($prefixUID <= 0 && !$moduleUID) {
                throw new Exception('Please select a module.');
            }

            $validSeps = ['-', '/', '|', '_', '.'];
            $sep = getPostValue($PostData, 'prefixSeparator') ?: '-';
            if (!in_array($sep, $validSeps)) $sep = '-';

            $validPads = ['1', '3', '5'];
            $pad = (string)(getPostValue($PostData, 'numberPadding') ?: '3');
            if (!in_array($pad, $validPads)) $pad = '3';

            $incFiscal = getPostValue($PostData, 'includeFiscalYear') ? 1 : 0;
            $fiscalFmt = in_array(getPostValue($PostData, 'fiscalYearFormat'), ['SHORT','LONG'])
                         ? getPostValue($PostData, 'fiscalYearFormat') : 'SHORT';
            $incShort  = getPostValue($PostData, 'includeShortName') ? 1 : 0;
            $shortName = strtoupper(substr(getPostValue($PostData, 'companyShortName') ?? '', 0, 20));
            if ($incShort && !$shortName) {
                throw new Exception('Company short name is required when enabled.');
            }

            $this->load->model('dbwrite_model');

            $data = [
                'Name'              => $name,
                'IncludeFiscalYear' => $incFiscal,
                'FiscalYearFormat'  => $fiscalFmt,
                'IncludeShortName'  => $incShort,
                'ShortName'         => $incShort ? $shortName : '',
                'Separator'         => $sep,
                'NumberPadding'     => (int)$pad,
                'UpdatedBy'         => $userUID,
            ];

            if ($prefixUID > 0) {
                $resp = $this->dbwrite_model->updateData(
                    'Transaction', 'TransactionPrefixTbl', $data,
                    ['PrefixUID' => $prefixUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
                );
                if ($resp->Error) throw new Exception($resp->Message);
                $this->EndReturnData->Message = 'Prefix updated successfully.';
            } else {
                $data['OrgUID']    = $orgUID;
                $data['ModuleUID'] = $moduleUID ?: null;
                $data['IsDefault'] = 0;
                $data['IsActive']  = 1;
                $data['IsDeleted'] = 0;
                $data['CreatedBy'] = $userUID;
                $resp = $this->dbwrite_model->insertData('Transaction', 'TransactionPrefixTbl', $data);
                if ($resp->Error) throw new Exception($resp->Message);
                $this->EndReturnData->Message = 'Prefix added successfully.';
            }

            $this->load->model('organisation_model');
            $result  = $this->organisation_model->getPrefixConfigList($orgUID);
            $rows    = $result->Error === FALSE ? $result->Data : [];
            $modules = $this->getPrefixModulesList();

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->RecordHtmlData = $this->load->view('settings/prefixconfig/list', [
                'DataLists' => $rows,
                'Modules'   => $modules,
                'JwtData'   => $this->pageData['JwtData'],
            ], TRUE);
            $this->EndReturnData->TotalCount = count($rows);
            $this->EndReturnData->Modules    = $modules;
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    /** AJAX POST: soft-delete a prefix (default prefix is protected) */
    public function deletePrefixConfig() {
        $this->EndReturnData = new stdClass();
        try {
            $PostData  = $this->input->post();
            $orgUID    = $this->pageData['JwtData']->User->OrgUID;
            $userUID   = $this->pageData['JwtData']->User->UserUID;
            $prefixUID = (int) getPostValue($PostData, 'prePrefixUID');
            if ($prefixUID <= 0) throw new Exception('Invalid prefix ID.');

            $this->load->model('organisation_model');
            $row = $this->organisation_model->getPrefixByUID($prefixUID, $orgUID);
            if (!$row->Data) throw new Exception('Prefix not found.');
            if ($row->Data->IsDefault) throw new Exception('Cannot delete the default prefix. Set another prefix as default first.');

            $this->load->model('dbwrite_model');
            $resp = $this->dbwrite_model->updateData(
                'Transaction', 'TransactionPrefixTbl',
                ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID, 'UpdatedOn' => time()],
                ['PrefixUID' => $prefixUID, 'OrgUID' => $orgUID]
            );
            if ($resp->Error) throw new Exception($resp->Message);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Prefix deleted successfully.';
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    /** AJAX POST: promote a prefix to the org-wide default */
    public function setDefaultPrefixConfig() {
        $this->EndReturnData = new stdClass();
        try {
            $PostData  = $this->input->post();
            $orgUID    = $this->pageData['JwtData']->User->OrgUID;
            $userUID   = $this->pageData['JwtData']->User->UserUID;
            $prefixUID = (int) getPostValue($PostData, 'prePrefixUID');
            if ($prefixUID <= 0) throw new Exception('Invalid prefix ID.');

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->updateData(
                'Transaction', 'TransactionPrefixTbl',
                ['IsDefault' => 0, 'UpdatedBy' => $userUID, 'UpdatedOn' => time()],
                ['OrgUID' => $orgUID, 'IsDeleted' => 0]
            );
            $resp = $this->dbwrite_model->updateData(
                'Transaction', 'TransactionPrefixTbl',
                ['IsDefault' => 1, 'UpdatedBy' => $userUID, 'UpdatedOn' => time()],
                ['PrefixUID' => $prefixUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );
            if ($resp->Error) throw new Exception($resp->Message);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Default prefix updated.';
        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }
        $this->globalservice->sendJsonResponse($this->EndReturnData);
    }

    /** AJAX POST: soft-delete a thermal config row */
    public function deleteThermalConfig() {

        $this->EndReturnData = new stdClass();
        try {

            $PostData  = $this->input->post();
            $orgUID    = $this->pageData['JwtData']->User->OrgUID;
            $userUID   = $this->pageData['JwtData']->User->UserUID;
            $configUID = (int) getPostValue($PostData, 'ThermalConfigUID');

            if ($configUID <= 0) throw new Exception('Invalid config ID.');

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->updateData(
                'Organisation', 'ThermalPrintConfigTbl',
                ['IsDeleted' => 1, 'IsActive' => 0, 'UpdatedBy' => $userUID],
                ['ThermalConfigUID' => $configUID, 'OrgUID' => $orgUID]
            );

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Thermal print config deleted.';

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

}
