<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Paymentsout extends MY_Controller {

    public $pageData = array();
    private $EndReturnData;
    private $pageModuleUID;

    public function __construct() {
        parent::__construct();
        $this->pageModuleUID = 111;
    }

    public function index() {

        if (!$this->_loadPageTitle($this->pageModuleUID)) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }

        try {

            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;

            $GeneralSettings = ($this->redisservice->getUserCache('settings')) ?? new stdClass();
            $this->pageData['JwtData']->GenSettings = $GeneralSettings;
            $limit  = $GeneralSettings->RowLimit ?? 10;
            $orgUID = $this->pageData['JwtData']->User->OrgUID;

            $filter = ['ModuleUID' => 111, 'PaymentDirection' => 'Out', 'PaymentSource' => 'Record'];

            $this->load->model('transactions_model');
            $allData      = $this->transactions_model->getPaymentsList($limit, 0, $orgUID, $filter);
            $allDataCount = $this->transactions_model->getPaymentsCount($orgUID, $filter);

            $this->pageData['ModRowData']    = $this->load->view('transactions/paymentsout/list', [
                'DataLists'    => $allData,
                'SerialNumber' => 0,
                'JwtData'      => $this->pageData['JwtData'],
            ], TRUE);
            $this->pageData['ModPagination'] = $this->globalservice->buildPagePaginationHtml('/paymentsout/getPageDetails', $allDataCount, 1, $limit);
            $this->pageData['ModAllCount']   = $allDataCount;
            $this->pageData['Totals']        = $this->transactions_model->getPaymentsTotals($orgUID, $filter);
            $this->pageData['MethodSummary'] = $this->transactions_model->getPaymentMethodSummary($orgUID, $filter);
            $this->pageData['BankAccounts']  = $this->transactions_model->getOrgBankAccounts($orgUID);

            $this->load->view('transactions/paymentsout/view', $this->pageData);

        } catch (Exception $e) {
            redirect('dashboard', 'refresh');
        }

    }

    public function cancelPayment() {

        $this->EndReturnData = new stdClass();
        try {

            $this->load->model('dbwrite_model');
            $this->load->model('transactions_model');

            $PostData   = $this->input->post();
            $paymentUID = (int) getPostValue($PostData, 'PaymentUID');
            $userUID    = $this->pageData['JwtData']->User->UserUID;
            $orgUID     = $this->pageData['JwtData']->User->OrgUID;

            if ($paymentUID <= 0) throw new Exception('Invalid payment record.');

            $payment = $this->transactions_model->getPaymentRow($paymentUID, $orgUID);
            if (!$payment) throw new Exception('Payment record not found or already cancelled.');

            $transUID     = (int) $payment->TransUID;
            $existingPaid = $transUID > 0
                ? $this->transactions_model->getSumPaidForTransaction($transUID, $orgUID)
                : 0;

            $this->dbwrite_model->startTransaction();

            // Soft-delete the payment (IsDeleted=1 = Cancelled tab)
            $cancelData             = $this->globalservice->baseDeleteArrayDetails();
            $cancelData['IsActive'] = 0;
            $resp = $this->dbwrite_model->updateData(
                'Transaction', 'PaymentsTbl', $cancelData,
                ['PaymentUID' => $paymentUID, 'OrgUID' => $orgUID, 'IsDeleted' => 0]
            );
            if ($resp->Error) throw new Exception($resp->Message);

            // Recalculate linked Sales Return balance
            if ($transUID > 0) {
                $newTotalPaid  = max(0, round($existingPaid - (float) $payment->Amount, 2));
                $trans         = $this->transactions_model->getTransactionBasicInfo($transUID, $orgUID);
                if ($trans) {
                    $netAmount     = (float) $trans->NetAmount;
                    $balanceAmount = max(0, round($netAmount - $newTotalPaid, 2));
                    $isFullyPaid   = ($netAmount > 0 && $balanceAmount <= 0) ? 1 : 0;
                    $this->dbwrite_model->updateTransIsFullyPaid($transUID, $isFullyPaid, $newTotalPaid, $balanceAmount, $userUID);
                    $newStatus = $newTotalPaid <= 0 ? 'Approved' : ($isFullyPaid ? 'Paid' : 'Partial');
                    $this->dbwrite_model->updateTransDocStatus($transUID, $orgUID, $newStatus, $userUID);
                }
            }

            $this->dbwrite_model->commitTransaction();

            $this->EndReturnData->Error   = FALSE;
            $this->EndReturnData->Message = 'Payment cancelled successfully.';

        } catch (Exception $e) {
            if (isset($this->dbwrite_model)) $this->dbwrite_model->rollbackTransaction();
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

    public function getPageDetails($pageNo = 0) {

        $this->EndReturnData = new stdClass();
        try {

            $pageNo = (int) $pageNo;
            if ($pageNo < 1) $pageNo = 1;

            $limit  = (int) $this->input->post('RowLimit') ?: 10;
            $offset = ($pageNo - 1) * $limit;
            $filter = $this->input->post('Filter') ?: [];

            // Always scope to PaymentsOut module with outgoing direction
            $filter['ModuleUID']        = 111;
            $filter['PaymentDirection'] = 'Out';
            $filter['PaymentSource']    = 'Record';
            unset($filter['PartyType']);

            $orgUID = $this->pageData['JwtData']->User->OrgUID;
            $this->pageData['JwtData']->GenSettings = ($this->redisservice->getUserCache('settings')) ?? new stdClass();

            $this->load->model('transactions_model');
            $allData      = $this->transactions_model->getPaymentsList($limit, $offset, $orgUID, $filter);
            $allDataCount = $this->transactions_model->getPaymentsCount($orgUID, $filter);

            $rowHtml = $this->load->view('transactions/paymentsout/list', [
                'DataLists'    => $allData,
                'SerialNumber' => ($pageNo - 1) * $limit,
                'JwtData'      => $this->pageData['JwtData'],
            ], true);

            $this->EndReturnData->Error          = FALSE;
            $this->EndReturnData->RecordHtmlData = $rowHtml;
            $this->EndReturnData->Pagination     = $this->globalservice->buildPagePaginationHtml('/paymentsout/getPageDetails', $allDataCount, $pageNo, $limit);
            $this->EndReturnData->TotalCount     = $allDataCount;
            $this->EndReturnData->Totals         = $this->transactions_model->getPaymentsTotals($orgUID, $filter);

        } catch (Exception $e) {
            $this->EndReturnData->Error   = TRUE;
            $this->EndReturnData->Message = $e->getMessage();
        }

        $this->globalservice->sendJsonResponse($this->EndReturnData);

    }

}
