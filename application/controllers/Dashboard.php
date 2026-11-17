<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends MY_Controller {

    public $pageData = array();

    public function __construct() {
        parent::__construct();
    }

    public function index(): void {
        try {
            $this->pageData['PageTitle'] = 'Dashboard';
            $this->load->view('dashboard/view', $this->pageData);
        } catch (Exception $e) {
            notifyError('Dashboard::index', $e);
            redirect('login', 'refresh');
        }
    }
}