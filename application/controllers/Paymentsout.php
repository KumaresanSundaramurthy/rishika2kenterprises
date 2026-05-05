<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Paymentsout extends CI_Controller {

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
            $limit  = $GeneralSettings->RowLimit ?? 10;
            $orgUID = $this->pageData['JwtData']->User->OrgUID;

            $filter = ['PartyType' => 'S', 'PaymentDirection' => 'Out'];

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
            $this->pageData['MethodSummary'] = $this->transactions_model->getPaymentMethodSummary($orgUID, array_merge($filter, []));
            $this->pageData['BankAccounts']  = $this->transactions_model->getOrgBankAccounts($orgUID);

            $this->load->view('transactions/paymentsout/view', $this->pageData);

        } catch (Exception $e) {
            redirect('dashboard', 'refresh');
        }

    }

    public function getPageDetails($pageNo = 0) {

        $this->EndReturnData = new stdClass();
        try {

            $pageNo = (int) $pageNo;
            if ($pageNo < 1) $pageNo = 1;

            $limit  = (int) $this->input->post('RowLimit') ?: 10;
            $offset = ($pageNo - 1) * $limit;
            $filter = $this->input->post('Filter') ?: [];

            // Always scope to vendor (payment out) only
            $filter['PartyType'] = 'S';

            $orgUID = $this->pageData['JwtData']->User->OrgUID;
            $this->pageData['JwtData']->GenSettings = ($this->redis_cache->get('Redis_UserGenSettings')->Value) ?? new stdClass();

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
