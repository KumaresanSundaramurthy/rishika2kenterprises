<?php defined('BASEPATH') OR exit('No direct script access allowed');

class PrintThemes extends MY_Controller {

    public $pageData = [];

    public function __construct() {
        parent::__construct();
    }

    public function index(): void {
        $this->pageData['PageTitle']       = 'Print Themes';
        $this->pageData['PageDescription'] = 'Configure print templates and themes for each transaction type.';
        $this->load->view('settings/printthemes/view', $this->pageData);
    }

}