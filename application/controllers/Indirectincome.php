<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Indirectincome extends MY_Controller {

    public  $pageData      = [];
    protected $EndReturnData;
    protected $pageModuleUID = 115;

    public function __construct() {
        parent::__construct();
        $this->load->helper('transaction');
    }

    // List page 
    public function index(): void {
        if (!$this->_loadPageTitle($this->pageModuleUID)) {
            $this->load->view('common/module_error', $this->pageData);
            return;
        }
        try {
            $this->pageData['JwtData']->ModuleUID = $this->pageModuleUID;
            $orgUID = $this->pageData['JwtData']->Org->OrgUID;

            $datePref = $this->getDateFilterPreference('indirectincome');
            $this->pageData['SavedDateRange'] = $datePref['range'];
            $this->pageData['SavedDateLabel'] = $datePref['label'];
            $this->pageData['InitDateFrom']   = $datePref['from'];
            $this->pageData['InitDateTo']     = $datePref['to'];

            $userUID  = (int)$this->pageData['JwtData']->User->UserUID;
            $jwtToken = $_COOKIE[getenv('JWT_COOKIE_NAME')] ?? '';
            $pageResp = services_get(
                'api/indirectincome/getPageData?orgId=' . $orgUID
                    . '&branchId=' . $this->_branchUID()
                    . '&userId=' . $userUID,
                ['Authorization: Bearer ' . $jwtToken]
            );
            $this->pageData['Categories']     = $pageResp->Data->Categories  ?? [];
            $this->pageData['PaymentTypes']   = $pageResp->Data->PaymentTypes ?? [];
            $this->pageData['BankAccounts']   = $pageResp->Data->BankAccounts ?? [];
            $orgUsers                         = (array)($pageResp->Data->OrgUsers ?? []);
            $this->pageData['OrgUsers']       = $orgUsers;
            $this->pageData['ShowUserFilter'] = count($orgUsers) > 1;

            $this->load->view('transactions/indirectincome/view', $this->pageData);

        } catch (ValidationException $e) {
            redirect('dashboard', 'refresh');
        } catch (Throwable $e) {
            notifyError('Indirectincome::index', $e);
            redirect('dashboard', 'refresh');
        }
    }

}
