<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller {

    public $pageData = array();

    public function __construct() {
        parent::__construct();

    }

    public function index() {
        $this->load->view('dashboard/view', $this->pageData);
    }

}