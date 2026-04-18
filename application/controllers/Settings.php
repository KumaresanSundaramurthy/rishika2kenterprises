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
