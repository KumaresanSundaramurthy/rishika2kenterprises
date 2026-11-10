<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Payments extends MY_Controller {

    public $pageData = array();
    protected $pageModuleUID;

    public function __construct() {
        parent::__construct();
        $this->pageModuleUID = 110;
    }

    public function index(): void {

        if (!$this->_loadPageTitle($this->pageModuleUID)) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }

        try {

            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;

            $orgUID   = $this->pageData['JwtData']->Org->OrgUID;
            $datePref = $this->getDateFilterPreference('payments');

            $this->load->model('transactions_model');

            $orgInfo  = $this->_getOrgInfo($orgUID)->Data ?? null;
            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';

            $this->pageData['OrgName']        = $orgInfo->BrandName ?? $orgInfo->Name ?? '';
            $this->pageData['OrgMobile']      = $orgInfo->MobileNumber ?? '';
            $this->pageData['BankAccounts']   = $this->transactions_model->getOrgBankAccounts($orgUID);
            $this->pageData['PaymentTypes']   = $this->transactions_model->getPaymentTypesList();
            $orgUsersResp                     = services_get('api/users/getOrgUsers?orgId=' . $orgUID, ['Authorization: Bearer ' . $jwtToken]);
            $this->pageData['OrgUsers']       = (array)($orgUsersResp->Data->Users ?? []);
            $this->pageData['SavedDateRange'] = $datePref['range'];
            $this->pageData['SavedDateLabel'] = $datePref['label'];
            $this->pageData['InitDateFrom']   = $datePref['from'];
            $this->pageData['InitDateTo']     = $datePref['to'];

            $this->load->view('transactions/payments/view', $this->pageData);

        } catch (ValidationException $e) {
            redirect('dashboard', 'refresh');
        } catch (Exception $e) {
            notifyError('Payments::index', $e);
            redirect('dashboard', 'refresh');
        }

    }

}