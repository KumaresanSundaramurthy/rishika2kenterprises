<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Settings extends CI_Controller {

    public  $pageData = [];
    private $EndReturnData;

    /** All supported transaction types for thermal print config */
    public static $THERMAL_TRANS_TYPES = [
        'Quotation'      => 'Quotation',
        'Invoice'        => 'Invoice',
        'SalesOrder'     => 'Sales Order',
        'PurchaseOrder'  => 'Purchase Order',
        'Purchase'       => 'Purchase',
        'SalesReturn'    => 'Sales Return',
        'PurchaseReturn' => 'Purchase Return',
        'CreditNote'     => 'Credit Note',
        'DebitNote'      => 'Debit Note',
    ];

    public function __construct() {
        parent::__construct();
    }

    // ── General Settings page ────────────────────────────────────────────────

    public function generalsettings() {
        try {
            $this->load->model('organisation_model');
            $orgUID = $this->pageData['JwtData']->User->OrgUID;
            $this->pageData['OrgPreviewData'] = $this->organisation_model->getOrgForReceipt($orgUID)->Data;
            $this->load->view('settings/generalsettings/view', $this->pageData);
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

            $rowHtml = $this->load->view('settings/thermalconfig/list', [
                'DataLists'    => $rows,
                'TransTypes'   => self::$THERMAL_TRANS_TYPES,
                'JwtData'      => $this->pageData['JwtData'],
            ], TRUE);

            $usedTypes = array_map(fn($r) => $r->TransactionType, $rows);

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->RecordHtmlData = $rowHtml;
            $this->EndReturnData->UsedTypes      = $usedTypes;
            $this->EndReturnData->TotalCount     = count($rows);

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
            $configUID   = (int) getPostValue($PostData, 'ThermalConfigUID');
            $transType   = trim(getPostValue($PostData, 'TransactionType') ?: '');

            if (!array_key_exists($transType, self::$THERMAL_TRANS_TYPES)) {
                throw new Exception('Invalid transaction type.');
            }

            $paperWidth = in_array(getPostValue($PostData, 'PaperWidth'), ['58mm', '80mm'])
                            ? getPostValue($PostData, 'PaperWidth') : '80mm';
            $orgSize    = max(8, min(40, (int)(getPostValue($PostData, 'OrgNameFontSize') ?: 22)));
            $coSize     = max(8, min(40, (int)(getPostValue($PostData, 'CompanyNameFontSize') ?: 18)));
            $prodSize   = max(8, min(40, (int)(getPostValue($PostData, 'ProductInfoFontSize') ?: 12)));

            $configData = [
                // Printer
                'PaperWidth'            => $paperWidth,
                // Header / Footer
                'HeaderMessage'         => NULL,
                'FooterMessage'         => substr(getPostValue($PostData, 'FooterMessage') ?: '', 0, 500) ?: NULL,
                // Receipt Elements
                'ShowTerms'             => (int)(bool)getPostValue($PostData, 'ShowTerms'),
                'ShowCompanyDetails'    => (int)(bool)getPostValue($PostData, 'ShowCompanyDetails'),
                'ShowItemDescription'   => (int)(bool)getPostValue($PostData, 'ShowItemDescription'),
                'ShowTaxableAmount'     => (int)(bool)getPostValue($PostData, 'ShowTaxableAmount'),
                'ShowHSN'               => (int)(bool)getPostValue($PostData, 'ShowHSN'),
                'ShowTaxBreakdown'      => (int)(bool)getPostValue($PostData, 'ShowTaxBreakdown'),
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
                $this->dbwrite_model->updateData(
                    'Organisation', 'ThermalPrintConfigTbl', $configData,
                    ['ThermalConfigUID' => $configUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
                );
                $this->EndReturnData->Message = 'Thermal print config updated.';
            } else {
                // Insert new row — enforce no duplicate per type
                $configData['OrgUID']           = $orgUID;
                $configData['TransactionType']  = $transType;
                $configData['CreatedBy']        = $userUID;
                $configData['IsActive']         = 1;
                $configData['IsDeleted']        = 0;
                $this->dbwrite_model->insertData('Organisation', 'ThermalPrintConfigTbl', $configData);
                $this->EndReturnData->Message = 'Thermal print config saved.';
            }

            $this->EndReturnData->Error = FALSE;

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
