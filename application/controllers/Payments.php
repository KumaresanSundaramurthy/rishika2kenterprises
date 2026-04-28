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

            $this->load->model('transactions_model');
            $allData      = $this->transactions_model->getPaymentsList($limit, 0, $orgUID, []);
            $allDataCount = $this->transactions_model->getPaymentsCount($orgUID, []);

            $this->pageData['ModRowData']    = $this->load->view('transactions/payments/list', [
                'DataLists'    => $allData,
                'SerialNumber' => 0,
                'JwtData'      => $this->pageData['JwtData'],
            ], TRUE);
            $this->pageData['ModPagination'] = $this->globalservice->buildPagePaginationHtml('/payments/getPaymentsPageDetails', $allDataCount, 1, $limit);
            $this->pageData['ModAllCount']   = $allDataCount;
            $this->pageData['MethodSummary'] = $this->transactions_model->getPaymentMethodSummary($orgUID);
            $this->pageData['Totals']        = $this->transactions_model->getPaymentsTotals($orgUID);

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
