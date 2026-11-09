<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Indirectincome extends MY_Controller {

    public  $pageData      = [];
    protected $EndReturnData;
    protected $pageModuleUID = 115;

    public function __construct() {
        parent::__construct();
        $this->load->helper('transaction');
        $this->load->model('indirectincome_model');
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

            $this->pageData['Categories']   = $this->indirectincome_model->getCategories($orgUID);
            $this->pageData['PaymentTypes'] = $this->indirectincome_model->getPaymentTypes();
            $this->pageData['BankAccounts'] = $this->indirectincome_model->getBankAccounts($orgUID);

            $orgUsers = $this->_requireCache($this->redisservice->orgKey('org-users'));
            if (!$orgUsers) return;
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
