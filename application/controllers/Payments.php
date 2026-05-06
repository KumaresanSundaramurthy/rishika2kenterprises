<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Payments extends CI_Controller {

    public $pageData = array();
    private $EndReturnData;
    private $pageModuleUID;

    public function __construct() {
        parent::__construct();
        $this->pageModuleUID = 110;
    }

    public function index() {

        try {

            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;

            $GeneralSettings = ($this->redis_cache->get('Redis_UserGenSettings')->Value) ?? new stdClass();
            $this->pageData['JwtData']->GenSettings = $GeneralSettings;
            $limit = $GeneralSettings->RowLimit ?? 10;

            $orgUID = $this->pageData['JwtData']->User->OrgUID;

            $filter = ['PartyType' => 'C', 'PaymentDirection' => 'In'];

            $this->load->model('transactions_model');
            $allData      = $this->transactions_model->getPaymentsList($limit, 0, $orgUID, $filter);
            $allDataCount = $this->transactions_model->getPaymentsCount($orgUID, $filter);

            $this->pageData['ModRowData']    = $this->load->view('transactions/payments/list', [
                'DataLists'    => $allData,
                'SerialNumber' => 0,
                'JwtData'      => $this->pageData['JwtData'],
            ], TRUE);
            $this->pageData['ModPagination'] = $this->globalservice->buildPagePaginationHtml('/payments/getPaymentsPageDetails', $allDataCount, 1, $limit);
            $this->pageData['ModAllCount']   = $allDataCount;
            $this->pageData['MethodSummary'] = $this->transactions_model->getPaymentMethodSummary($orgUID);
            $this->pageData['Totals']        = $this->transactions_model->getPaymentsTotals($orgUID);
            $this->pageData['BankAccounts']  = $this->transactions_model->getOrgBankAccounts($orgUID);

            $this->load->view('transactions/payments/view', $this->pageData);

        } catch (Exception $e) {
            redirect('dashboard', 'refresh');
        }

    }

    public function getPaymentsPageDetails($pageNo = 0) {

        $this->EndReturnData = new stdClass();
        try {

            $pageNo = (int) $pageNo;
            if ($pageNo < 1) $pageNo = 1;

            $limit  = (int) $this->input->post('RowLimit') ?: 10;
            $offset = ($pageNo - 1) * $limit;
            $filter = $this->input->post('Filter') ?: [];

            $orgUID = $this->pageData['JwtData']->User->OrgUID;
            $this->pageData['JwtData']->GenSettings = ($this->redis_cache->get('Redis_UserGenSettings')->Value) ?? new stdClass();

            $this->load->model('transactions_model');
            $allData      = $this->transactions_model->getPaymentsList($limit, $offset, $orgUID, $filter);
            $allDataCount = $this->transactions_model->getPaymentsCount($orgUID, $filter);

            $rowHtml = $this->load->view('transactions/payments/list', [
                'DataLists'    => $allData,
                'SerialNumber' => ($pageNo - 1) * $limit,
                'JwtData'      => $this->pageData['JwtData'],
            ], true);

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->RecordHtmlData = $rowHtml;
            $this->EndReturnData->Pagination     = $this->globalservice->buildPagePaginationHtml('/payments/getPaymentsPageDetails', $allDataCount, $pageNo, $limit);
            $this->EndReturnData->TotalCount     = $allDataCount;
            $this->EndReturnData->Totals         = $this->transactions_model->getPaymentsTotals($orgUID, $filter);

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function addPayment() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');
            $this->dbwrite_model->startTransaction();

            $PostData = $this->input->post();
            $userUID  = $this->pageData['JwtData']->User->UserUID;
            $orgUID   = $this->pageData['JwtData']->User->OrgUID;

            $transUID       = (int)   getPostValue($PostData, 'TransUID');
            $moduleUID      = (int)   getPostValue($PostData, 'ModuleUID');
            $paymentTypeUID = (int)   getPostValue($PostData, 'PaymentTypeUID');
            $amount         = (float) getPostValue($PostData, 'Amount', 'Array', 0);
            $billTotal      = (float) getPostValue($PostData, 'BillTotal', 'Array', 0);
            $bankAccountUID = (int)   getPostValue($PostData, 'BankAccountUID') ?: NULL;
            $referenceNo    =         getPostValue($PostData, 'ReferenceNo') ?: NULL;
            $notes          =         getPostValue($PostData, 'Notes') ?: NULL;
            $isFullyPaid    = (int)   getPostValue($PostData, 'IsFullyPaid') === 1 ? 1 : 0;
            $partyType      =         getPostValue($PostData, 'PartyType') ?: 'C';
            $partyUID       = (int)   getPostValue($PostData, 'PartyUID');

            if ($transUID <= 0)  throw new Exception('Invalid transaction.');
            if ($paymentTypeUID <= 0) throw new Exception('Please select a payment type.');
            if ($amount <= 0)    throw new Exception('Payment amount must be greater than 0.');

            $excessAmount = $billTotal > 0 ? max(0, $amount - $billTotal) : 0;

            $paymentData = [
                'OrgUID'            => $orgUID,
                'TransUID'          => $transUID,
                'ModuleUID'         => $moduleUID > 0 ? $moduleUID : $this->pageModuleUID,
                'PartyType'         => $partyType,
                'PartyUID'          => $partyUID,
                'PaymentTypeUID'    => $paymentTypeUID,
                'Amount'            => $amount,
                'BankAccountUID'    => $bankAccountUID,
                'ReferenceNo'       => $referenceNo,
                'Notes'             => $notes,
                'IsFullyPaid'       => $isFullyPaid,
                'ExcessAmount'      => $excessAmount,
                'AppliedToTransUID' => NULL,
                'IsActive'          => 1,
                'IsDeleted'         => 0,
                'CreatedBy'         => $userUID,
                'UpdatedBy'         => $userUID,
            ];

            $resp = $this->dbwrite_model->insertData('Transaction', 'PaymentsTbl', $paymentData);
            if ($resp->Error) throw new Exception($resp->Message);

            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Message    = 'Payment recorded successfully.';
            $this->EndReturnData->PaymentUID = $resp->ID;

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function getPaymentsByTransaction() {

        $this->EndReturnData = new stdClass();
        try {

            $transUID = (int) $this->input->get_post('TransUID');
            $orgUID   = $this->pageData['JwtData']->User->OrgUID;

            if ($transUID <= 0) throw new Exception('Invalid transaction.');

            $this->load->model('transactions_model');
            $payments    = $this->transactions_model->getTransactionPayments($transUID, $orgUID);
            $paidTotal   = array_sum(array_column((array) $payments, 'Amount'));

            $this->EndReturnData->Error      = FALSE;
            $this->EndReturnData->Payments   = $payments;
            $this->EndReturnData->PaidTotal  = $paidTotal;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function getPaymentDetail() {

        $this->EndReturnData = new stdClass();
        try {

            $PostData   = $this->input->post();
            $paymentUID = (int) getPostValue($PostData, 'PaymentUID');
            $orgUID     = $this->pageData['JwtData']->User->OrgUID;

            if ($paymentUID <= 0) throw new Exception('Invalid payment record.');

            $this->load->model('transactions_model');
            $record = $this->transactions_model->getPaymentDetailById($paymentUID, $orgUID);
            if (!$record) throw new Exception('Payment record not found.');

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $record;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function deletePayment() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');
            $this->load->model('transactions_model');

            $PostData   = $this->input->post();
            $paymentUID = (int) getPostValue($PostData, 'PaymentUID');
            $userUID    = $this->pageData['JwtData']->User->UserUID;
            $orgUID     = $this->pageData['JwtData']->User->OrgUID;

            if ($paymentUID <= 0) throw new Exception('Invalid payment record.');

            // 1. Fetch payment + existing paid total BEFORE deletion (avoids read-replica lag)
            $payment = $this->transactions_model->getPaymentRow($paymentUID, $orgUID);
            if (!$payment) throw new Exception('Payment record not found or already deleted.');

            $transUID     = (int) $payment->TransUID;
            $existingPaid = ($transUID > 0)
                ? $this->transactions_model->getSumPaidForTransaction($transUID, $orgUID)
                : 0;

            $this->dbwrite_model->startTransaction();

            // 2. Soft-delete the payment
            $deleteData = $this->globalservice->baseDeleteArrayDetails();
            $deleteData['IsActive'] = 0;
            $resp = $this->dbwrite_model->updateData(
                'Transaction', 'PaymentsTbl', $deleteData,
                ['PaymentUID' => $paymentUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );
            if ($resp->Error) throw new Exception($resp->Message);

            // 3. Recalculate and persist updated transaction balance
            if ($transUID > 0) {
                $newTotalPaid = max(0, round($existingPaid - (float) $payment->Amount, 2));

                $trans = $this->transactions_model->getTransactionBasicInfo($transUID, $orgUID);
                if ($trans) {
                    $netAmount     = (float) $trans->NetAmount;
                    $balanceAmount = max(0, round($netAmount - $newTotalPaid, 2));
                    $isFullyPaid   = ($netAmount > 0 && $balanceAmount <= 0) ? 1 : 0;

                    $this->dbwrite_model->updateTransIsFullyPaid($transUID, $isFullyPaid, $newTotalPaid, $balanceAmount, $userUID);

                    if ($newTotalPaid <= 0)  $newStatus = 'Unpaid';
                    elseif ($isFullyPaid)    $newStatus = 'Paid';
                    else                     $newStatus = 'Partial';

                    $this->dbwrite_model->updateTransDocStatus($transUID, $orgUID, $newStatus, $userUID);

                    $this->EndReturnData->NewPaidAmount    = round($newTotalPaid, 2);
                    $this->EndReturnData->NewBalanceAmount = $balanceAmount;
                    $this->EndReturnData->NewStatus        = $newStatus;
                }
            }

            $this->dbwrite_model->commitTransaction();

            // 4. Reverse customer ledger entry outside transaction (non-fatal on failure)
            if ($transUID > 0 && $payment->PartyType === 'C' && (int)$payment->PartyUID > 0) {
                try {
                    $this->load->library('accountledger');
                    $this->accountledger->applyLedgerEntry(
                        (int) $payment->PartyUID, 'Customer', (float) $payment->Amount, 'Debit', $transUID
                    );
                } catch (Exception $ledgerEx) {
                    log_message('error', 'Ledger reversal failed after payment deletion PaymentUID=' . $paymentUID . ': ' . $ledgerEx->getMessage());
                }
            }

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Payment deleted.';

        } catch (Exception $e) {
            if (isset($this->dbwrite_model)) $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function getPaymentTypes() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('transactions_model');
            $types = $this->transactions_model->getPaymentTypesList();

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $types;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function getBankAccounts() {

        $this->EndReturnData = new stdClass();
        try {

            $orgUID = $this->pageData['JwtData']->User->OrgUID;
            $this->load->model('transactions_model');
            $accounts = $this->transactions_model->getOrgBankAccounts($orgUID);

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $accounts;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function saveBankAccount() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');

            $PostData    = $this->input->post();
            $orgUID      = $this->pageData['JwtData']->User->OrgUID;
            $userUID     = $this->pageData['JwtData']->User->UserUID;
            $accountUID  = (int) getPostValue($PostData, 'BankAccountUID');

            $isDefault     = (int) getPostValue($PostData, 'IsDefault') === 1 ? 1 : 0;
            $accountNumber = trim(getPostValue($PostData, 'AccountNumber'));
            $confirmNumber = trim(getPostValue($PostData, 'ConfirmAccountNumber'));

            $accountData = [
                'OrgUID'        => $orgUID,
                'AccountName'   => trim(getPostValue($PostData, 'AccountName')),
                'BankName'      => trim(getPostValue($PostData, 'BankName')),
                'AccountNumber' => $accountNumber,
                'IFSC'          => strtoupper(trim(getPostValue($PostData, 'IFSC'))) ?: NULL,
                'BranchName'    => trim(getPostValue($PostData, 'BranchName')) ?: NULL,
                'UPIId'         => trim(getPostValue($PostData, 'UPIId')) ?: NULL,
                'UPINumber'     => trim(getPostValue($PostData, 'UPINumber')) ?: NULL,
                'IsDefault'     => $isDefault,
                'IsActive'      => 1,
                'IsDeleted'     => 0,
                'UpdatedBy'     => $userUID,
            ];

            if (empty($accountData['AccountName']))   throw new Exception('Account holder name is required.');
            if (empty($accountData['BankName']))      throw new Exception('Bank name is required.');
            if (empty($accountData['AccountNumber'])) throw new Exception('Account number is required.');
            if ($accountUID <= 0 && $accountNumber !== $confirmNumber) throw new Exception('Account number and confirmation do not match.');

            $this->dbwrite_model->startTransaction();

            // If set as default, clear other defaults first
            if ($isDefault) {
                $this->dbwrite_model->updateData(
                    'Transaction', 'OrgBankAccountsTbl',
                    ['IsDefault' => 0, 'UpdatedBy' => $userUID],
                    ['OrgUID' => $orgUID, 'IsDeleted' => 0]
                );
            }

            if ($accountUID > 0) {
                $resp = $this->dbwrite_model->updateData(
                    'Transaction', 'OrgBankAccountsTbl', $accountData,
                    ['BankAccountUID' => $accountUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
                );
            } else {
                $accountData['CreatedBy'] = $userUID;
                $resp = $this->dbwrite_model->insertData('Transaction', 'OrgBankAccountsTbl', $accountData);
                if (!$resp->Error) $this->EndReturnData->BankAccountUID = $resp->ID;
            }

            if ($resp->Error) throw new Exception($resp->Message);

            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = $accountUID > 0 ? 'Bank account updated.' : 'Bank account added.';

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function getBankDetails() {

        $this->EndReturnData = new stdClass();
        try {

            $PostData      = $this->input->post();
            $orgUID        = $this->pageData['JwtData']->User->OrgUID;
            $bankAccountUID = (int) getPostValue($PostData, 'BankAccountUID');
            if ($bankAccountUID <= 0) throw new Exception('Bank account ID is required.');

            $this->load->model('transactions_model');

            $this->transactions_model->ReadDb->db_debug = FALSE;
            $this->transactions_model->ReadDb->select('BankAccountUID, AccountName, BankName, AccountNumber, IFSC, BranchName, UPIId, UPINumber, IsDefault');
            $this->transactions_model->ReadDb->from('Transaction.OrgBankAccountsTbl');
            $this->transactions_model->ReadDb->where(['BankAccountUID' => $bankAccountUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]);
            $query  = $this->transactions_model->ReadDb->get();
            $record = $query ? $query->row() : null;
            if (!$record) throw new Exception('Bank account not found.');

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $record;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function setDefaultBank() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');

            $PostData       = $this->input->post();
            $orgUID         = $this->pageData['JwtData']->User->OrgUID;
            $userUID        = $this->pageData['JwtData']->User->UserUID;
            $bankAccountUID = (int) getPostValue($PostData, 'BankAccountUID');
            if ($bankAccountUID <= 0) throw new Exception('Bank account ID is required.');

            $this->dbwrite_model->startTransaction();

            // Clear all defaults
            $this->dbwrite_model->updateData(
                'Transaction', 'OrgBankAccountsTbl',
                ['IsDefault' => 0, 'UpdatedBy' => $userUID],
                ['OrgUID' => $orgUID, 'IsDeleted' => 0]
            );

            // Set the selected one
            $resp = $this->dbwrite_model->updateData(
                'Transaction', 'OrgBankAccountsTbl',
                ['IsDefault' => 1, 'UpdatedBy' => $userUID],
                ['BankAccountUID' => $bankAccountUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );
            if ($resp->Error) throw new Exception($resp->Message);

            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Default bank updated.';

        } catch (Exception $e) {
            $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function deleteBankAccount() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');

            $PostData       = $this->input->post();
            $orgUID         = $this->pageData['JwtData']->User->OrgUID;
            $userUID        = $this->pageData['JwtData']->User->UserUID;
            $bankAccountUID = (int) getPostValue($PostData, 'BankAccountUID');
            if ($bankAccountUID <= 0) throw new Exception('Bank account ID is required.');

            $deleteData = $this->globalservice->baseDeleteArrayDetails();
            $deleteData['IsActive'] = 0;

            $resp = $this->dbwrite_model->updateData(
                'Transaction', 'OrgBankAccountsTbl', $deleteData,
                ['BankAccountUID' => $bankAccountUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );
            if ($resp->Error) throw new Exception($resp->Message);

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Bank account deleted.';

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function getPaymentPrintDetail() {

        $this->EndReturnData = new stdClass();
        try {

            $paymentUID = (int) $this->input->get_post('PaymentUID');
            $orgUID     = $this->pageData['JwtData']->User->OrgUID;

            if ($paymentUID <= 0) throw new Exception('Invalid payment.');

            $this->load->model('transactions_model');
            $payment = $this->transactions_model->getPaymentDetailById($paymentUID, $orgUID);
            if (!$payment) throw new Exception('Payment not found.');

            $this->load->model('organisation_model');
            $orgInfo      = $this->organisation_model->getOrgForReceipt($orgUID);
            $org          = $orgInfo->Data ?? null;
            $thermalCfg   = $this->organisation_model->getThermalPrintConfigByModule($orgUID, $this->pageModuleUID);
            $printTheme   = $this->organisation_model->getPrintThemeByType($orgUID, 'Payment');

            $this->EndReturnData->Error         = FALSE;
            $this->EndReturnData->Payment       = $payment;
            $this->EndReturnData->OrgInfo       = $org;
            $this->EndReturnData->ThermalConfig = $thermalCfg->Data ?? null;
            $this->EndReturnData->PrintTheme    = $printTheme->Data ?? null;
            $this->EndReturnData->PrintHtml     = $this->_renderPaymentReceiptHtml($payment, $org, $printTheme->Data ?? null);

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    private function _renderPaymentReceiptHtml($p, $org, $theme) {
        $org   = $org   ?? new stdClass();
        $theme = $theme ?? new stdClass();
        $e     = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES);
        $fmt   = function($d) { if (!$d) return '—'; $dt = date_create($d); return $dt ? date_format($dt, 'd M Y') : $d; };
        $cur   = $org->CurrenySymbol ?? '₹';
        $dec   = 2;
        $fmtAmt = fn($v) => $cur . ' ' . number_format((float)$v, $dec, '.', ',');

        $primary = $theme->PrimaryColor ?? '#1a3c6e';
        $accent  = $theme->AccentColor  ?? '#f59e0b';
        $font    = $theme->FontFamily   ?? 'Arial';
        $footer  = $theme->FooterText   ?? 'Thank you for your business!';

        $direction = ($p->PartyType === 'C') ? 'Payment Received' : 'Payment Made';
        $partyLabel = ($p->PartyType === 'C') ? 'Customer' : 'Vendor';

        $orgName    = $e($org->BrandName ?? $org->Name ?? '');
        $orgAddr    = implode(', ', array_filter([$org->Line1 ?? '', $org->Line2 ?? '', $org->CityText ?? '', $org->StateText ?? '', $org->Pincode ?? '']));
        $orgPhone   = $e($org->MobileNumber ?? '');
        $orgGstin   = $e($org->GSTIN ?? '');

        $bankLine = '';
        if (!$p->IsCash && !empty($p->BankName)) {
            $bankLine = $e($p->BankName) . (!empty($p->AccountName) ? ' (' . $e($p->AccountName) . ')' : '');
        }

        $logoHtml = '';
        if (!empty($org->Logo)) {
            $logoHtml = '<img src="' . $e($org->Logo) . '" style="max-height:60px;max-width:120px;object-fit:contain;" alt="Logo">';
        }

        return '<!DOCTYPE html><html><head><meta charset="UTF-8">'
            . '<style>'
            . '@page{size:A4;margin:0;}'
            . 'body{font-family:\'' . $e($font) . '\',Arial,sans-serif;font-size:11px;margin:0;padding:0;background:#fff;}'
            . '.page{padding:12mm 14mm;}'
            . '.header{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:3px solid ' . $primary . ';padding-bottom:10px;margin-bottom:14px;}'
            . '.org-name{font-size:16px;font-weight:700;color:' . $primary . ';}'
            . '.org-sub{font-size:9px;color:#555;margin-top:3px;}'
            . '.receipt-title{font-size:20px;font-weight:800;color:' . $primary . ';text-align:right;}'
            . '.receipt-num{font-size:11px;color:#555;text-align:right;margin-top:4px;}'
            . '.amount-box{background:' . $primary . ';color:#fff;border-radius:8px;padding:14px 20px;text-align:center;margin:16px 0;}'
            . '.amount-label{font-size:10px;opacity:.85;text-transform:uppercase;letter-spacing:.5px;}'
            . '.amount-val{font-size:26px;font-weight:800;margin-top:4px;}'
            . '.info-grid{display:flex;gap:16px;margin-bottom:14px;}'
            . '.info-card{flex:1;border:1px solid #e5e7eb;border-radius:6px;padding:10px 12px;}'
            . '.info-card-label{font-size:9px;text-transform:uppercase;color:#888;letter-spacing:.4px;margin-bottom:6px;}'
            . '.info-row{display:flex;justify-content:space-between;margin-bottom:4px;font-size:10px;}'
            . '.info-key{color:#666;}'
            . '.info-val{font-weight:600;color:#111;text-align:right;max-width:60%;}'
            . '.mode-badge{display:inline-block;background:' . $accent . '22;color:' . $accent . ';border:1px solid ' . $accent . '44;border-radius:4px;padding:2px 8px;font-size:10px;font-weight:600;}'
            . '.footer-bar{border-top:1px solid #e5e7eb;margin-top:20px;padding-top:10px;text-align:center;font-size:9px;color:#888;}'
            . '@media print{body{background:#fff;}}'
            . '</style></head><body><div class="page">'
            . '<div class="header">'
                . '<div>' . ($logoHtml ? $logoHtml . '<br>' : '') . '<div class="org-name">' . $orgName . '</div>'
                . '<div class="org-sub">' . $e($orgAddr) . '</div>'
                . (!empty($orgPhone) ? '<div class="org-sub">Ph: ' . $orgPhone . '</div>' : '')
                . (!empty($orgGstin) ? '<div class="org-sub">GSTIN: ' . $orgGstin . '</div>' : '') . '</div>'
                . '<div><div class="receipt-title">' . $e($direction) . '</div>'
                . '<div class="receipt-num">' . $e($p->UniqueNumber ?? ('PMT-' . $p->PaymentUID)) . '</div>'
                . '<div class="receipt-num">' . $fmt($p->PaymentDate ?? $p->CreatedOn) . '</div></div>'
            . '</div>'
            . '<div class="amount-box">'
                . '<div class="amount-label">Amount ' . ($p->PartyType === 'C' ? 'Received' : 'Paid') . '</div>'
                . '<div class="amount-val">' . $fmtAmt($p->Amount) . '</div>'
            . '</div>'
            . '<div class="info-grid">'
                . '<div class="info-card">'
                    . '<div class="info-card-label">' . $e($partyLabel) . ' Details</div>'
                    . '<div class="info-row"><span class="info-key">' . $e($partyLabel) . '</span><span class="info-val">' . $e($p->PartyName ?? '—') . '</span></div>'
                    . (!empty($p->PartyMobile) ? '<div class="info-row"><span class="info-key">Mobile</span><span class="info-val">' . $e($p->PartyMobile) . '</span></div>' : '')
                    . (!empty($p->TransNumber) ? '<div class="info-row"><span class="info-key">Linked Doc</span><span class="info-val">' . $e($p->TransNumber) . '</span></div>' : '')
                    . (!empty($p->BillAmount) ? '<div class="info-row"><span class="info-key">Bill Amount</span><span class="info-val">' . $fmtAmt($p->BillAmount) . '</span></div>' : '')
                . '</div>'
                . '<div class="info-card">'
                    . '<div class="info-card-label">Payment Details</div>'
                    . '<div class="info-row"><span class="info-key">Mode</span><span class="info-val"><span class="mode-badge">' . $e($p->PaymentTypeName ?? '—') . '</span></span></div>'
                    . ($bankLine ? '<div class="info-row"><span class="info-key">Bank</span><span class="info-val">' . $bankLine . '</span></div>' : '')
                    . (!empty($p->AccountNumber) ? '<div class="info-row"><span class="info-key">A/C No</span><span class="info-val">' . $e($p->AccountNumber) . '</span></div>' : '')
                    . (!empty($p->ReferenceNo) ? '<div class="info-row"><span class="info-key">Reference</span><span class="info-val">' . $e($p->ReferenceNo) . '</span></div>' : '')
                    . '<div class="info-row"><span class="info-key">Recorded By</span><span class="info-val">' . $e($p->CreatedByName ?? '—') . '</span></div>'
                . '</div>'
            . '</div>'
            . (!empty($p->Notes) ? '<div style="border:1px solid #e5e7eb;border-radius:6px;padding:8px 12px;font-size:10px;color:#555;"><strong>Notes:</strong> ' . $e($p->Notes) . '</div>' : '')
            . '<div class="footer-bar">' . $e($footer) . '</div>'
            . '</div></body></html>';
    }

    public function downloadPaymentPdf() {

        try {

            $paymentUID = (int) $this->input->get_post('PaymentUID');
            $paperSize  = strtoupper(trim($this->input->get_post('PaperSize') ?: 'A4'));
            $orgUID     = $this->pageData['JwtData']->User->OrgUID;

            if ($paymentUID <= 0) throw new Exception('Invalid payment.');

            $this->load->model('transactions_model');
            $payment = $this->transactions_model->getPaymentDetailById($paymentUID, $orgUID);
            if (!$payment) throw new Exception('Payment not found.');

            $this->load->model('organisation_model');
            $orgInfo    = $this->organisation_model->getOrgForReceipt($orgUID);
            $printTheme = $this->organisation_model->getPrintThemeByType($orgUID, 'Payment');
            // Note: thermal config not needed for PDF download (uses A4 print theme)

            $html = $this->_renderPaymentReceiptHtml($payment, $orgInfo->Data ?? null, $printTheme->Data ?? null);

            // PDF-specific fixes
            $html = preg_replace('/\bdisplay\s*:\s*flex\s*;?/i',          'display:block;', $html);
            $html = preg_replace('/\bjustify-content\s*:[^;"}]+;?/i',     '', $html);
            $html = preg_replace('/\balign-items\s*:[^;"}]+;?/i',         '', $html);
            $html = preg_replace('/@page\s*\{[^}]*\}/', "@page{size:{$paperSize};margin:10mm 5mm;}", $html);

            require_once FCPATH . 'vendor/autoload.php';
            $options = new \Dompdf\Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'Arial');
            $options->set('chroot', FCPATH);

            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper(strtolower($paperSize), 'portrait');
            $dompdf->render();

            $filename = preg_replace('/[^A-Za-z0-9_\-]/', '_', $payment->UniqueNumber ?? ('Payment_' . $paymentUID)) . '.pdf';

            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: private, max-age=0, must-revalidate');
            echo $dompdf->output();
            exit;

        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['Error' => true, 'Message' => $e->getMessage()]);
            exit;
        }

    }

    public function getBanksList() {

        $this->EndReturnData = new stdClass();
        try {

            $orgUID = $this->pageData['JwtData']->User->OrgUID;
            $this->load->model('transactions_model');
            $accounts = $this->transactions_model->getOrgBankAccounts($orgUID);

            $this->EndReturnData->Error = FALSE;
            $this->EndReturnData->Data  = $accounts;

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

}
