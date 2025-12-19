<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Users extends CI_Controller {

    public $pageData = array();
    private $EndReturnData;

    public function __construct() {
        parent::__construct();

    }

    public function index() {
        
        try {

            $this->load->view('users/view', $this->pageData);

        } catch (Exception $e) {
            redirect('dashboard', 'refresh');
        }

    }

}