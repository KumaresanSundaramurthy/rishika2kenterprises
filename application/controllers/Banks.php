<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Banks extends CI_Controller {

    public $pageData = array();

    public function __construct() {
        parent::__construct();
    }

    public function index() {

        try {

            $this->load->model('transactions_model');
            $orgUID = $this->pageData['JwtData']->User->OrgUID;

            $this->pageData['BanksList'] = $this->transactions_model->getOrgBankAccounts($orgUID);

            $this->load->view('banks/view', $this->pageData);

        } catch (Exception $e) {
            redirect('dashboard', 'refresh');
        }

    }

}
