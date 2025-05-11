<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Organisation extends CI_Controller {

    public $pageData = array();

    public function __construct() {
        parent::__construct();

    }

    public function index() {
        
        $this->load->library('curlservice');
        $CountryResp = $this->curlservice->retrieve('https://r2k-enterprises.s3.ap-south-1.amazonaws.com/global/countrydetails.json', 'GET', []);

        $Countries = $CountryResp->Data;
        usort($Countries, function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        $this->pageData['CountryInfo'] = $Countries;

        // $this->load->model('user_model');
        // $this->pageData['MainModule'] = $this->user_model->getUserRightsMainModule($this->pageData['JwtData']->User->UserUID);
        // $this->pageData['SubModule'] = $this->user_model->getUserRightsSubModule($this->pageData['JwtData']->User->UserUID);
        
        $this->load->view('organisation/view', $this->pageData);

    }

}