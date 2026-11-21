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

            $orgInfo  = $this->_getOrgInfo($orgUID)->Data ?? null;
            $this->pageData['OrgName']        = $orgInfo->BrandName ?? $orgInfo->Name ?? '';
            $this->pageData['OrgMobile']      = $orgInfo->MobileNumber ?? '';
            $baListResp                       = services_get('api/transactions/getOrgBankAccounts?orgId=' . $orgUID, withJwt: true);
            $this->pageData['BankAccounts']   = (!empty($baListResp) && !$baListResp->Error) ? (array)($baListResp->Data ?? []) : [];
            $ptResp                           = services_get('api/transactions/getPaymentTypesList', withJwt: true);
            $this->pageData['PaymentTypes']   = (!empty($ptResp) && !$ptResp->Error) ? (array)($ptResp->Data ?? []) : [];
            $orgUsersResp                     = services_get('api/users/getOrgUsers?orgId=' . $orgUID, withJwt: true);
            $this->pageData['OrgUsers']       = (array)($orgUsersResp->Data->Users ?? []);
            $this->pageData['SavedDateRange'] = $datePref['range'];
            $this->pageData['SavedDateLabel'] = $datePref['label'];
            $this->pageData['InitDateFrom']   = $datePref['from'];
            $this->pageData['InitDateTo']     = $datePref['to'];

            $tabSlugMap = ['all' => '', 'cancelled' => 'Cancelled'];
            $tabSlug    = strtolower(trim($this->input->get('tab') ?: 'all'));
            $this->pageData['InitTab']    = $tabSlugMap[$tabSlug] ?? '';
            $this->pageData['InitSearch'] = trim($this->input->get('search') ?: '');

            $this->load->view('transactions/payments/view', $this->pageData);

        } catch (ValidationException $e) {
            redirect('dashboard', 'refresh');
        } catch (Exception $e) {
            notifyError('Payments::index', $e);
            redirect('dashboard', 'refresh');
        }

    }

}